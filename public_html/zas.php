<?php
// ============================================================================
//  ZAS -> canteen: the four weekly addresses
// ============================================================================
//  ZAS's server calls these when Accounts press "Get canteen charges" on
//  Prepare pay. Nobody else ever calls them: there is no session, no cookie and
//  no screen here, only the shared charges key, checked with hash_equals.
//
//      https://canteen.thqlinen.com/zas.php?a=charges
//      https://canteen.thqlinen.com/zas.php?a=reserve
//      https://canteen.thqlinen.com/zas.php?a=settle
//      https://canteen.thqlinen.com/zas.php?a=release
//
//  Every amount leaving this file is whole paisa in an integer, as ZAS expects.
//  The canteen's own columns stay DECIMAL(10,2) rupees and are converted here,
//  exactly, with no float arithmetic in between.
//
//  Every call is safe to repeat: the second identical call changes nothing.
//  That is what lets Accounts press the button twice without charging twice.
// ============================================================================

require_once __DIR__ . '/zas_link.php';

header('Cache-Control: no-store');

// --- the key, before anything else. Wrong key: 403 and nothing else. --------
if (!defined('ZAS_CHARGES_KEY') || ZAS_CHARGES_KEY === '') {
  error_log('ZAS link: ZAS_CHARGES_KEY missing from config.php');
  http_response_code(503);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => false, 'error' => 'Canteen link not configured']);
  exit;
}
$sent = (string)($_SERVER['HTTP_X_LINK_KEY'] ?? '');
if ($sent === '' || !hash_equals(ZAS_CHARGES_KEY, $sent)) {
  http_response_code(403);
  exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  http_response_code(405);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => false, 'error' => 'POST only']);
  exit;
}

$a = $_GET['a'] ?? '';
$b = body();

/** The ids ZAS sent, as a clean list of positive integers. */
function zas_ids($v): array {
  if (!is_array($v)) return [];
  $out = [];
  foreach ($v as $x) { $n = (int)$x; if ($n > 0) $out[$n] = $n; }
  if (count($out) > 5000) fail('Too many ids in one call — send at most 5000');
  return array_values($out);
}
/** A placeholder list for an IN (...) clause. */
function zas_marks(array $ids): string { return implode(',', array_fill(0, count($ids), '?')); }

function zas_sheet(array $b): string {
  $s = trim((string)($b['sheet_ref'] ?? ''));
  if ($s === '' || strlen($s) > 40 || !preg_match('/^[A-Za-z0-9 ._\/-]+$/', $s)) fail('Bad sheet_ref');
  return $s;
}

switch ($a) {

// ---------------------------------------------------------------------------
//  1. charges — read only. Everything not yet on a pay sheet, up to and
//     including up_to (a Thursday). Older unsettled rows come too, so a week
//     that was missed is never left behind.
//
//     An order that was cancelled is never charged, so it is left out here
//     whatever its settle_state. An unsettled order that already carries a
//     refund appears in both lists; the two net out to what is owed.
// ---------------------------------------------------------------------------
case 'charges': {
  $upto = (string)($b['up_to'] ?? '');
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $upto)) fail('up_to must be YYYY-MM-DD');
  $d = db();

  $st = $d->prepare("SELECT id, zas_emp_id, created_at, total, week_from
                     FROM orders
                     WHERE zas_emp_id IS NOT NULL AND settle_state = 'unsettled'
                       AND status <> 'Cancelled' AND DATE(created_at) <= ?
                     ORDER BY id");
  $st->execute([$upto]);
  $rows = $st->fetchAll();

  $orders = [];
  if ($rows) {
    $ids = array_map(fn($r)=>(int)$r['id'], $rows);
    $it = $d->prepare("SELECT order_id, item_name, quantity, subtotal
                       FROM order_items WHERE order_id IN (".zas_marks($ids).") ORDER BY id");
    $it->execute($ids);
    $byOrder = [];
    foreach ($it->fetchAll() as $l) {
      $byOrder[(int)$l['order_id']][] = [
        'name'   => $l['item_name'],
        'qty'    => (int)$l['quantity'],
        'amount' => zas_paisa($l['subtotal']),
      ];
    }
    foreach ($rows as $r) {
      $orders[] = [
        'order_id'    => (int)$r['id'],
        'employee_id' => (int)$r['zas_emp_id'],
        'ordered_at'  => $r['created_at'],
        'week_from'   => $r['week_from'],
        'total'       => zas_paisa($r['total']),
        'items'       => $byOrder[(int)$r['id']] ?? [],
      ];
    }
  }

  $st = $d->prepare("SELECT r.id, r.order_id, r.zas_emp_id, r.created_at, r.amount, r.reason
                     FROM order_refunds r
                     WHERE r.settle_state = 'unsettled' AND DATE(r.created_at) <= ?
                     ORDER BY r.id");
  $st->execute([$upto]);
  $refunds = [];
  foreach ($st->fetchAll() as $r) {
    $refunds[] = [
      'refund_id'   => (int)$r['id'],
      'order_id'    => (int)$r['order_id'],
      'employee_id' => (int)$r['zas_emp_id'],
      'created_at'  => $r['created_at'],
      'amount'      => zas_paisa($r['amount']),
      'reason'      => $r['reason'],
    ];
  }

  json_out(['ok'=>true,'up_to'=>$upto,'currency'=>'PKR-paisa','orders'=>$orders,'refunds'=>$refunds]);
}

// ---------------------------------------------------------------------------
//  2. reserve — claim these rows for one pay sheet, in one transaction.
//     unsettled           -> reserved, stamped with the sheet
//     reserved, same sheet -> left alone and counted under "already", so a
//                             repeated call changes nothing
//     anything else        -> refused, with its id, and never moved: already on
//                             another sheet, already settled, cancelled since
//                             the charges call, or no such row
// ---------------------------------------------------------------------------
case 'reserve': {
  $sheet = zas_sheet($b);
  $oids  = zas_ids($b['order_ids']  ?? []);
  $rids  = zas_ids($b['refund_ids'] ?? []);
  $d = db(); $d->beginTransaction();
  try {
    $res = ['orders'=>0,'refunds'=>0]; $already = ['orders'=>0,'refunds'=>0];
    $refused = ['order_ids'=>[],'refund_ids'=>[]];

    if ($oids) {
      $m = zas_marks($oids);
      $up = $d->prepare("UPDATE orders SET settle_state='reserved', sheet_ref=?
                         WHERE id IN ($m) AND zas_emp_id IS NOT NULL
                           AND settle_state='unsettled' AND status <> 'Cancelled'");
      $up->execute(array_merge([$sheet], $oids));
      $res['orders'] = $up->rowCount();
      $ok = $d->prepare("SELECT id FROM orders WHERE id IN ($m) AND settle_state='reserved' AND sheet_ref=?");
      $ok->execute(array_merge($oids, [$sheet]));
      $held = array_map('intval', $ok->fetchAll(PDO::FETCH_COLUMN));
      $already['orders'] = count($held) - $res['orders'];
      $refused['order_ids'] = array_values(array_diff($oids, $held));
    }
    if ($rids) {
      $m = zas_marks($rids);
      $up = $d->prepare("UPDATE order_refunds SET settle_state='reserved', sheet_ref=?
                         WHERE id IN ($m) AND settle_state='unsettled'");
      $up->execute(array_merge([$sheet], $rids));
      $res['refunds'] = $up->rowCount();
      $ok = $d->prepare("SELECT id FROM order_refunds WHERE id IN ($m) AND settle_state='reserved' AND sheet_ref=?");
      $ok->execute(array_merge($rids, [$sheet]));
      $held = array_map('intval', $ok->fetchAll(PDO::FETCH_COLUMN));
      $already['refunds'] = count($held) - $res['refunds'];
      $refused['refund_ids'] = array_values(array_diff($rids, $held));
    }
    $d->commit();
    json_out(['ok'=>true,'sheet_ref'=>$sheet,'reserved'=>$res,'already'=>$already,'refused'=>$refused]);
  } catch (Throwable $ex) {
    if ($d->inTransaction()) $d->rollBack();
    error_log('ZAS reserve failed: '.$ex->getMessage());
    fail('Reserve failed', 500);
  }
}

// ---------------------------------------------------------------------------
//  3. settle — the pay sheet went out. Rows reserved on it become settled.
//     Nothing else is touched, so a second call settles nothing.
// ---------------------------------------------------------------------------
case 'settle': {
  $sheet = zas_sheet($b);
  $d = db(); $d->beginTransaction();
  try {
    $o = $d->prepare("UPDATE orders SET settle_state='settled', settled_at=NOW()
                      WHERE settle_state='reserved' AND sheet_ref=?");
    $o->execute([$sheet]);
    $r = $d->prepare("UPDATE order_refunds SET settle_state='settled', settled_at=NOW()
                      WHERE settle_state='reserved' AND sheet_ref=?");
    $r->execute([$sheet]);
    $d->commit();
    json_out(['ok'=>true,'sheet_ref'=>$sheet,
              'settled'=>['orders'=>$o->rowCount(),'refunds'=>$r->rowCount()]]);
  } catch (Throwable $ex) {
    if ($d->inTransaction()) $d->rollBack();
    error_log('ZAS settle failed: '.$ex->getMessage());
    fail('Settle failed', 500);
  }
}

// ---------------------------------------------------------------------------
//  4. release — the pay sheet was sent back. Its rows go back to unsettled and
//     come again on the next charges call. Settled rows are never released;
//     those are undone with a refund.
// ---------------------------------------------------------------------------
case 'release': {
  $sheet = zas_sheet($b);
  $d = db(); $d->beginTransaction();
  try {
    $o = $d->prepare("UPDATE orders SET settle_state='unsettled', sheet_ref=NULL
                      WHERE settle_state='reserved' AND sheet_ref=?");
    $o->execute([$sheet]);
    $r = $d->prepare("UPDATE order_refunds SET settle_state='unsettled', sheet_ref=NULL
                      WHERE settle_state='reserved' AND sheet_ref=?");
    $r->execute([$sheet]);
    $d->commit();
    json_out(['ok'=>true,'sheet_ref'=>$sheet,
              'released'=>['orders'=>$o->rowCount(),'refunds'=>$r->rowCount()]]);
  } catch (Throwable $ex) {
    if ($d->inTransaction()) $d->rollBack();
    error_log('ZAS release failed: '.$ex->getMessage());
    fail('Release failed', 500);
  }
}

default: fail('Unknown action', 404);
}
