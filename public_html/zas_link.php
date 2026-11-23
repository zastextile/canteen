<?php
// ============================================================================
//  ZAS link — the canteen's side of the invoice.thqlinen.com integration
// ============================================================================
//  Two secrets, each written into config.php on BOTH servers and never into a
//  page, into JavaScript, into git or into a chat message:
//
//    ZAS_CARD_KEY     the canteen SENDS this to ZAS on every card check.
//    ZAS_CHARGES_KEY  ZAS SENDS this to zas.php once a week; the canteen
//                     CHECKS it with hash_equals and answers 403 otherwise.
//
//  Neither app reads the other's database. Card numbers never reach this file:
//  the kiosk hashes the digits in the browser and sends only the 64-character
//  fingerprint, which is all ZAS wants.
// ============================================================================

require_once __DIR__ . '/lib.php';

// Rs 1,000 a week. Used only while ZAS has not started sending weekly_limit —
// their brief says to assume this until that update lands.
if (!defined('ZAS_DEFAULT_WEEKLY_LIMIT')) define('ZAS_DEFAULT_WEEKLY_LIMIT', 100000); // paisa

// ---------------------------------------------------------------------------
//  Money. ZAS speaks whole paisa in a BIGINT; the canteen's own columns are
//  DECIMAL(10,2) rupees and are left exactly as they are. Everything crossing
//  the link is converted here, and every limit comparison is done in integer
//  paisa, so no float rounding can ever creep into the money.
// ---------------------------------------------------------------------------
function zas_paisa($rupees): int { return (int)round((float)$rupees * 100); }
function zas_rupees(int $paisa): float { return $paisa / 100; }

// ---------------------------------------------------------------------------
//  The pay week: Friday 00:00 to Thursday 23:59:59. Returns the Friday that
//  starts the week containing $when, which is what orders.week_from holds.
//    date('N'): Mon=1 … Fri=5 … Sun=7, so Friday is 0 days back, Saturday 1,
//    Sunday 2, Monday 3 … Thursday 6.
// ---------------------------------------------------------------------------
function zas_week_from(?string $when = null): string {
  $t = $when === null ? time() : strtotime($when);
  $back = ((int)date('N', $t) + 2) % 7;
  return date('Y-m-d', strtotime("-$back days", $t));
}

// ---------------------------------------------------------------------------
//  What this employee has already spent in this pay week, in paisa.
//  Orders that are not cancelled, minus refunds raised against those orders.
//  A refund is matched to the week of the ORDER it reverses, not the week it
//  was raised in, so refunding Monday's cold tea on Saturday frees up Monday's
//  limit rather than next week's.
// ---------------------------------------------------------------------------
function zas_spent_paisa(PDO $d, int $emp, string $week): int {
  $st = $d->prepare("SELECT COALESCE(SUM(total),0) FROM orders
                     WHERE zas_emp_id = ? AND week_from = ? AND status <> 'Cancelled'");
  $st->execute([$emp, $week]);
  $spent = zas_paisa($st->fetchColumn());

  $st = $d->prepare("SELECT COALESCE(SUM(r.amount),0) FROM order_refunds r
                     JOIN orders o ON o.id = r.order_id
                     WHERE r.zas_emp_id = ? AND o.week_from = ?");
  $st->execute([$emp, $week]);
  return $spent - zas_paisa($st->fetchColumn());
}

// ---------------------------------------------------------------------------
//  Ask ZAS about one card. Called from the canteen SERVER on every tap, never
//  from the kiosk browser — the browser must never hold the link key.
//
//  Returns ['http' => int, 'body' => array|null, 'net' => string|null].
//  'net' is set only when the request never got an answer (timeout, DNS,
//  TLS, refused connection); per golden rule 12 that means no sale.
// ---------------------------------------------------------------------------
function zas_card_check(string $fp): array {
  if (!defined('ZAS_CARD_URL') || !defined('ZAS_CARD_KEY') || ZAS_CARD_KEY === '') {
    error_log('ZAS link: ZAS_CARD_URL / ZAS_CARD_KEY missing from config.php');
    return ['http' => 0, 'body' => null, 'net' => 'The card link is not configured on this server'];
  }
  $payload = json_encode(['card' => $fp]);
  $headers = ['Content-Type: application/json', 'Accept: application/json', 'X-Link-Key: ' . ZAS_CARD_KEY];

  $raw = null; $code = 0; $net = null;
  if (function_exists('curl_init')) {
    $ch = curl_init(ZAS_CARD_URL);
    curl_setopt_array($ch, [
      CURLOPT_POST           => true,
      CURLOPT_POSTFIELDS     => $payload,
      CURLOPT_HTTPHEADER     => $headers,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CONNECTTIMEOUT => 3,
      CURLOPT_TIMEOUT        => 5,    // golden rule: 5 seconds, then no sale
    ]);
    $raw = curl_exec($ch);
    if ($raw === false) $net = curl_error($ch) ?: 'could not reach ZAS';
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
  } else {
    // No curl on this PHP build — same request through the stream wrapper.
    $ctx = stream_context_create(['http' => [
      'method' => 'POST', 'header' => implode("\r\n", $headers), 'content' => $payload,
      'timeout' => 5, 'ignore_errors' => true,
    ]]);
    $raw = @file_get_contents(ZAS_CARD_URL, false, $ctx);
    if ($raw === false) $net = 'could not reach ZAS';
    foreach ($http_response_header ?? [] as $h)
      if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) $code = (int)$m[1];
  }

  if ($net !== null) {
    error_log('ZAS card check failed: ' . $net);              // never the fingerprint, never the card
    return ['http' => 0, 'body' => null, 'net' => $net];
  }
  $body = json_decode((string)$raw, true);
  return ['http' => $code, 'body' => is_array($body) ? $body : null, 'net' => null];
}

// ---------------------------------------------------------------------------
//  Turn a card check into one message for the counter screen. The reasons are
//  exactly the ones ZAS documents; anything else is reported as a link fault
//  rather than guessed at, so a cashier is never told "card not registered"
//  because of a bug.
// ---------------------------------------------------------------------------
function zas_card_message(array $r): string {
  if ($r['net'] !== null) return 'Could not reach ZAS — try again';
  switch ($r['http']) {
    case 403: error_log('ZAS card check: link key rejected (403)');
              return 'Card link key rejected — tell the administrator';
    case 422: error_log('ZAS card check: ZAS rejected the fingerprint (422)');
              return 'Card could not be read — tap again';
    case 503: return 'ZAS has not switched the canteen link on yet — try again';
    case 200: break;
    default:  error_log('ZAS card check: unexpected HTTP ' . $r['http']);
              return 'ZAS answered unexpectedly — try again';
  }
  $b = $r['body'];
  if (!is_array($b)) return 'ZAS answered unexpectedly — try again';
  if (!empty($b['allowed'])) return '';
  switch ((string)($b['reason'] ?? '')) {
    case 'unknown_card': return 'Card not registered. See HR.';
    case 'left':         return 'This card is no longer active.';
    case 'canteen_off':  return 'Canteen not allowed. See HR.';
  }
  return (string)($b['message'] ?? 'This card cannot be used right now.');
}

// ---------------------------------------------------------------------------
//  The canteen customer row that stands for a ZAS employee. Created the first
//  time their card is accepted, so the order looks like any other order to the
//  sales queue, to Admin and to every report: it has a real customer_id with a
//  real name on it. The row can never be logged into — its password is a random
//  string nobody holds.
//
//  Name and department are refreshed from ZAS when they change, so a transfer
//  in ZAS shows up at the counter without anybody retyping anything.
// ---------------------------------------------------------------------------
function zas_shadow_customer(PDO $d, array $emp): array {
  $id   = (int)$emp['employee_id'];
  $name = trim((string)($emp['name'] ?? '')) ?: ('Employee ' . $id);
  $code = trim((string)($emp['code'] ?? (string)$id));
  $dept = trim((string)($emp['department'] ?? '')) ?: null;

  $st = $d->prepare('SELECT * FROM users WHERE zas_emp_id = ?');
  $st->execute([$id]);
  $u = $st->fetch();

  if ($u) {
    if ($u['name'] !== $name || $u['department'] !== $dept || $u['employee_id'] !== $code
        || $u['status'] !== 'active') {
      $d->prepare("UPDATE users SET name=?, department=?, employee_id=?, status='active' WHERE id=?")
        ->execute([$name, $dept, $code, (int)$u['id']]);
      $u['name'] = $name; $u['department'] = $dept; $u['employee_id'] = $code; $u['status'] = 'active';
    }
    return $u;
  }

  $username = 'zas-' . $id;
  try {
    $d->prepare("INSERT INTO users (employee_id, zas_emp_id, name, username, password, role, department, wallet, status, menu_access)
                 VALUES (?,?,?,?,?,'customer',?,0,'active','standard')")
      ->execute([$code, $id, $name, $username, password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT), $dept]);
  } catch (PDOException $e) {
    // Only a username clash can land here: a canteen login already called
    // zas-<id>. Say so plainly rather than failing the sale with SQL text.
    $st = $d->prepare('SELECT * FROM users WHERE zas_emp_id = ?');
    $st->execute([$id]);
    if ($row = $st->fetch()) return $row;
    error_log('ZAS link: could not create customer row for employee ' . $id . ' — ' . $e->getMessage());
    fail('This employee could not be set up in the canteen — the username ' . $username . ' is already taken', 500);
  }
  $st->execute([$id]);
  return $st->fetch();
}
