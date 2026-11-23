<?php
// ============================================================================
//  ZAS Canteen — kiosk endpoint (everything the counter screen calls)
// ============================================================================
//  This file exists so that api.php — the file every running screen depends on
//  — does not have to be touched at all. Nothing here is reachable from any
//  existing page, and nothing existing is reachable from here. If the kiosk is
//  ever switched off, deleting kiosk.php and this file leaves the canteen
//  exactly as it is today.
//
//  A card order is still an ORDINARY canteen order: a row in `orders` with rows
//  in `order_items`, and sales-kitchen stock coming down the same way. It shows
//  up in the sales queue, in Admin and in every report with no change to any of
//  them. What marks it as a card order is simply that orders.zas_emp_id is set.
//
//  Roles: the cashier signs in (sales, accounts or admin). The EMPLOYEE never
//  signs in — their card is checked with ZAS on every single tap.
// ============================================================================

require_once __DIR__ . '/zas_link.php';     // which requires lib.php

$a = $_GET['a'] ?? '';
$READ = ['kiosk_menu', 'kiosk_orders'];
if (!in_array($a, $READ, true)) check_csrf();
// Read-only calls never write to the session, so the session lock is let go
// early and the counter's polling cannot serialize behind itself.
if (in_array($a, $READ, true) && !empty($_SESSION['csrf'])) session_write_close();

switch ($a) {

// ---------------------------------------------------------------------------
//  The dish buttons: everything on the menu, with what the sales kitchen has
//  left and the dish photo if one has been uploaded.
// ---------------------------------------------------------------------------
case 'kiosk_menu': {
  require_role('sales', 'admin', 'accounts');
  $sk = sales_kitchen_id();
  $rows = [];
  if ($sk) {
    $st = db()->prepare("SELECT m.id, m.name, m.price, m.category, c.icon AS emoji,
                           COALESCE(i.quantity,0) AS stock
                         FROM menu_items m
                         LEFT JOIN categories c ON c.name = m.category
                         LEFT JOIN inventory i ON i.menu_item_id = m.id AND i.kitchen_id = ?
                         WHERE m.status = 'active'
                         ORDER BY m.category, m.name");
    $st->execute([$sk]);
    $rows = $st->fetchAll();
  }
  // Same convention as the rest of the app: dish/<id>.webp on disk, no column
  // in the database, and a missing file just falls back to the category emoji.
  $photos = [];
  foreach ($rows as $r) {
    foreach (['webp','jpg','png'] as $ext) {
      $f = __DIR__ . '/dish/' . $r['id'] . '.' . $ext;
      if (is_file($f)) { $photos[(string)$r['id']] = 'dish/' . $r['id'] . '.' . $ext; break; }
    }
  }
  json_out(['ok'=>true, 'rows'=>$rows, 'photos'=>$photos, 'kitchen_id'=>$sk]);
}

// ---------------------------------------------------------------------------
//  A card was tapped. Ask ZAS — every time, with no list of our own — and
//  answer with the name to show and what is left of this week's limit.
// ---------------------------------------------------------------------------
case 'kiosk_card': {
  require_role('sales', 'admin', 'accounts');
  $fp = strtolower(trim((string)(body()['card'] ?? '')));
  if (!preg_match('/^[0-9a-f]{64}$/', $fp)) fail('Card could not be read — tap again');

  $r   = zas_card_check($fp);
  $msg = zas_card_message($r);
  // A refusal is a normal answer, not an error: the screen shows ZAS's own
  // wording and goes back to waiting for the next card.
  if ($msg !== '') json_out(['ok'=>true, 'allowed'=>false, 'message'=>$msg]);

  $emp = $r['body'];
  $id  = (int)($emp['employee_id'] ?? 0);
  if ($id <= 0) { error_log('ZAS card check: allowed with no employee_id'); fail('ZAS answered unexpectedly — try again'); }
  $limit = (array_key_exists('weekly_limit', $emp) && $emp['weekly_limit'] !== null)
             ? (int)$emp['weekly_limit'] : ZAS_DEFAULT_WEEKLY_LIMIT;

  $d     = db();
  $cu    = zas_shadow_customer($d, $emp);
  $week  = zas_week_from();
  $spent = zas_spent_paisa($d, $id, $week);
  json_out(['ok'=>true, 'allowed'=>true, 'employee_id'=>$id, 'customer_id'=>(int)$cu['id'],
            'name'=>$cu['name'], 'code'=>(string)$cu['employee_id'], 'department'=>$cu['department'],
            'limit'=>zas_rupees($limit), 'spent'=>zas_rupees($spent),
            'remaining'=>zas_rupees(max(0, $limit - $spent)), 'week_from'=>$week]);
}

// ---------------------------------------------------------------------------
//  Confirm. Saves the order the same way place_order does, minus the wallet:
//  a card order is paid out of ZAS pay, so users.wallet is never touched and
//  no wallet_topups row is written. Your own wallet customers are unaffected.
// ---------------------------------------------------------------------------
case 'kiosk_order': {
  $me   = require_role('sales', 'admin', 'accounts');
  $b    = body();
  $fp   = strtolower(trim((string)($b['card'] ?? '')));
  $cart = $b['items'] ?? [];
  if (!preg_match('/^[0-9a-f]{64}$/', $fp)) fail('Tap the card again to confirm');
  if (!$cart || !is_array($cart)) fail('Nothing selected');
  if (count($cart) > 30) fail('Too many lines on one order');
  $sk = sales_kitchen_id();
  if (!$sk) fail('Canteen is closed (no active sales kitchen)');

  // Ask ZAS again at Confirm. The tap's answer covers this one order and
  // nothing more, and in the seconds since then HR may have switched the card
  // off or changed the limit.
  $r   = zas_card_check($fp);
  $msg = zas_card_message($r);
  if ($msg !== '') fail($msg);
  $emp = $r['body'];
  $eid = (int)($emp['employee_id'] ?? 0);
  if ($eid <= 0) fail('ZAS answered unexpectedly — try again');
  $limit = (array_key_exists('weekly_limit', $emp) && $emp['weekly_limit'] !== null)
             ? (int)$emp['weekly_limit'] : ZAS_DEFAULT_WEEKLY_LIMIT;

  $merged = [];
  foreach ($cart as $c) {
    $mid = (int)($c['menu_item_id'] ?? 0); $q = (int)($c['qty'] ?? 0);
    if (!$mid || $q <= 0 || $q > 50) fail('Bad order line');
    $merged[$mid] = ($merged[$mid] ?? 0) + $q;
    if ($merged[$mid] > 50) fail('Max 50 per item');
  }

  $d  = db();
  $cu = zas_shadow_customer($d, $emp);   // outside the transaction: it may insert a row
  $d->beginTransaction();
  try {
    // Lock the employee's own row first. Two counters ringing up the same card
    // in the same second then queue here instead of both passing the limit.
    $d->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE')->execute([(int)$cu['id']]);

    $sel = $d->prepare("SELECT m.id, m.name, m.price, i.id iid, i.quantity stock
                        FROM menu_items m
                        LEFT JOIN inventory i ON i.menu_item_id = m.id AND i.kitchen_id = ?
                        WHERE m.id = ? AND m.status = 'active' FOR UPDATE");
    $total = 0.0; $lines = [];
    foreach ($merged as $mid => $q) {
      $sel->execute([$sk, $mid]);
      $m = $sel->fetch();
      if (!$m) { $d->rollBack(); fail('That item is no longer on the menu'); }
      if ((float)($m['stock'] ?? 0) < $q) { $d->rollBack(); fail($m['name'] . ' is out of stock'); }
      $total += (float)$m['price'] * $q;
      $lines[] = ['m'=>$m, 'q'=>$q];
    }

    // The weekly limit, checked here inside the transaction and not only on the
    // screen, entirely in whole paisa so no rounding can let a rupee through.
    $week  = zas_week_from();
    $spent = zas_spent_paisa($d, $eid, $week);
    $cost  = zas_paisa($total);
    if ($spent + $cost > $limit) {
      $d->rollBack();
      $left = max(0, $limit - $spent);
      fail('Weekly limit reached — only ' . CURRENCY . ' ' . number_format(zas_rupees($left))
           . ' left of ' . CURRENCY . ' ' . number_format(zas_rupees($limit)));
    }

    $d->prepare("INSERT INTO orders (customer_id,zas_emp_id,week_from,total,status,settle_state)
                 VALUES (?,?,?,?,'Pending','unsettled')")
      ->execute([(int)$cu['id'], $eid, $week, $total]);
    $oid = (int)$d->lastInsertId();

    $insItem = $d->prepare('INSERT INTO order_items (order_id,menu_item_id,item_name,price,quantity,subtotal,is_comp) VALUES (?,?,?,?,?,?,0)');
    $updInv  = $d->prepare('UPDATE inventory SET quantity = quantity - ? WHERE id = ?');
    $out = [];
    foreach ($lines as $l) {
      $price = (float)$l['m']['price'];
      $insItem->execute([$oid, (int)$l['m']['id'], $l['m']['name'], $price, $l['q'], $price * $l['q']]);
      $updInv->execute([$l['q'], (int)$l['m']['iid']]);
      $out[] = ['name'=>$l['m']['name'], 'qty'=>$l['q'], 'amount'=>$price * $l['q']];
    }
    $d->commit();
    cache_bump('inventory');   // the sales kitchen just gave up stock
    audit((int)$me['id'], $me['name'], 'Kiosk order ' . CURRENCY . ' ' . $total . ' — employee ' . $eid, 'orders', $oid, null);
    json_out(['ok'=>true, 'order_id'=>$oid, 'total'=>$total, 'items'=>$out,
              'name'=>$cu['name'], 'remaining'=>zas_rupees(max(0, $limit - $spent - $cost))]);
  } catch (Throwable $ex) {
    if ($d->inTransaction()) $d->rollBack();
    fail('Order failed: ' . $ex->getMessage(), 500);
  }
}

// ---------------------------------------------------------------------------
//  Today's card orders, for the counter to look back at and for a supervisor
//  to put right. `left_to_refund` is what the screen uses to decide which of
//  the two fixes to offer.
// ---------------------------------------------------------------------------
case 'kiosk_orders': {
  require_role('sales', 'admin', 'accounts');
  $orders = db()->query("SELECT o.id, LOWER(o.status) AS status, o.total, o.created_at AS placed_at,
                           o.settle_state, o.sheet_ref, o.cancel_reason, o.zas_emp_id,
                           c.name AS customer, c.employee_id AS code,
                           COALESCE((SELECT SUM(r.amount) FROM order_refunds r WHERE r.order_id = o.id),0) AS refunded
                         FROM orders o JOIN users c ON c.id = o.customer_id
                         WHERE o.zas_emp_id IS NOT NULL AND o.created_at >= CURDATE()
                         ORDER BY o.id DESC LIMIT 30")->fetchAll();
  if ($orders) {
    $ids = implode(',', array_map(fn($o) => (int)$o['id'], $orders));
    $items = db()->query("SELECT order_id, item_name AS name, quantity AS qty, subtotal
                          FROM order_items WHERE order_id IN ($ids)")->fetchAll();
    foreach ($orders as &$o) {
      $o['items'] = array_values(array_filter($items, fn($i) => (int)$i['order_id'] === (int)$o['id']));
      $o['left_to_refund'] = max(0, (float)$o['total'] - (float)$o['refunded']);
    }
  }
  json_out(['ok'=>true, 'rows'=>$orders]);
}

// ---------------------------------------------------------------------------
//  Cancel — only while the order is not yet on a pay sheet AND the food has
//  not gone out. Nothing was ever charged, so there is nothing to refund: the
//  stock goes back and ZAS never sees the order at all.
// ---------------------------------------------------------------------------
case 'kiosk_cancel': {
  $me = require_role('admin', 'accounts');   // supervisor only, per the ZAS brief
  $b  = body(); $id = (int)($b['id'] ?? 0); $reason = trim((string)($b['reason'] ?? ''));
  if ($reason === '') fail('Cancel reason required');
  $sk = sales_kitchen_id();
  $d  = db(); $d->beginTransaction();
  try {
    $st = $d->prepare('SELECT * FROM orders WHERE id = ? AND zas_emp_id IS NOT NULL FOR UPDATE');
    $st->execute([$id]);
    $o = $st->fetch();
    if (!$o)                                { $d->rollBack(); fail('That is not a card order'); }
    if ($o['status'] === 'Cancelled')        { $d->rollBack(); fail('That order is already cancelled'); }
    if ($o['settle_state'] !== 'unsettled')  { $d->rollBack(); fail('Already on pay sheet ' . $o['sheet_ref'] . ' — use Refund instead'); }
    if (!in_array($o['status'], ['Pending','Preparing'], true))
                                             { $d->rollBack(); fail('The food has already been handed over — use Refund instead'); }
    $its = $d->prepare('SELECT menu_item_id, quantity FROM order_items WHERE order_id = ?');
    $its->execute([$id]);
    $back = $d->prepare('UPDATE inventory SET quantity = quantity + ? WHERE kitchen_id = ? AND menu_item_id = ?');
    foreach ($its->fetchAll() as $it) $back->execute([(int)$it['quantity'], $sk, (int)$it['menu_item_id']]);
    $d->prepare("UPDATE orders SET status='Cancelled', cancel_reason=? WHERE id=?")->execute([$reason, $id]);
    $d->commit();
    cache_bump('inventory');
    audit((int)$me['id'], $me['name'], 'Cancel kiosk order (nothing charged, stock restored)', 'orders', $id, $reason);
    json_out(['ok'=>true]);
  } catch (Throwable $ex) { if ($d->inTransaction()) $d->rollBack(); fail('Cancel failed: ' . $ex->getMessage(), 500); }
}

// ---------------------------------------------------------------------------
//  Refund — for an order already on a pay sheet, or already handed over. The
//  order itself is never edited: a refund is a new row, and ZAS takes it off
//  the next canteen pay line. It can never exceed what is left of the order.
// ---------------------------------------------------------------------------
case 'kiosk_refund': {
  $me = require_role('admin', 'accounts');   // supervisor only, per the ZAS brief
  $b  = body(); $id = (int)($b['id'] ?? 0);
  $amt = (float)($b['amount'] ?? 0); $reason = trim((string)($b['reason'] ?? ''));
  if ($reason === '') fail('Refund reason required');
  if ($amt <= 0) fail('Refund amount must be more than zero');
  $d = db(); $d->beginTransaction();
  try {
    $st = $d->prepare('SELECT * FROM orders WHERE id = ? AND zas_emp_id IS NOT NULL FOR UPDATE');
    $st->execute([$id]);
    $o = $st->fetch();
    if (!$o)                          { $d->rollBack(); fail('That is not a card order'); }
    if ($o['status'] === 'Cancelled') { $d->rollBack(); fail('That order was cancelled — nothing was ever charged'); }
    $done = $d->prepare('SELECT COALESCE(SUM(amount),0) FROM order_refunds WHERE order_id = ?');
    $done->execute([$id]);
    $left = zas_paisa($o['total']) - zas_paisa($done->fetchColumn());
    if (zas_paisa($amt) > $left) {
      $d->rollBack();
      fail('Only ' . CURRENCY . ' ' . number_format(zas_rupees($left), 2) . ' is left to refund on that order');
    }
    $d->prepare('INSERT INTO order_refunds (order_id,zas_emp_id,amount,reason,created_by) VALUES (?,?,?,?,?)')
      ->execute([$id, (int)$o['zas_emp_id'], $amt, $reason, (int)$me['id']]);
    $d->commit();
    audit((int)$me['id'], $me['name'], 'Refund kiosk order ' . CURRENCY . ' ' . $amt, 'orders', $id, $reason);
    json_out(['ok'=>true]);
  } catch (Throwable $ex) { if ($d->inTransaction()) $d->rollBack(); fail('Refund failed: ' . $ex->getMessage(), 500); }
}

default: fail('Unknown action', 404);
}
