<?php
// ===== ZAS Canteen shared library — built for the database.sql schema =====
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/redis.php';

redis_session_boot(); // routes sessions to Redis if reachable; silently no-ops otherwise (file sessions)
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => isset($_SERVER['HTTPS'])]);
session_start();

function db(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
       // Pakistan Standard Time. The server's MySQL runs on UTC, so without this every
       // DEFAULT current_timestamp, NOW() and CURDATE() lands 5 hours behind the real
       // Karachi time that PHP already uses (config.php sets Asia/Karachi). A fixed
       // +05:00 offset is used rather than the name 'Asia/Karachi' because shared hosts
       // usually have MySQL's named-timezone tables unloaded; Pakistan has no DST, so
       // the offset never changes.
       PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+05:00'"]);
  }
  return $pdo;
}

function json_out($data, int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}
function fail(string $msg, int $code = 400): void { json_out(['ok' => false, 'error' => $msg], $code); }

function current_user(): ?array {
  if (empty($_SESSION['uid'])) return null;
  static $u = null; // in-process memo — still just one query even without Redis
  if ($u !== null) return $u;
  $uid = (int)$_SESSION['uid'];
  $key = 'user:v' . cache_version('users') . ":$uid";
  $u = cache_remember($key, 20, function () use ($uid) {
    $st = db()->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
    $st->execute([$uid]);
    return $st->fetch() ?: false; // false (not null) so cache_remember treats "no such user" as a real, cacheable result
  });
  if ($u === false) $u = null;
  return $u;
}

function require_login(): array {
  $u = current_user();
  if (!$u) fail('Not logged in', 401);
  return $u;
}
function require_role(string ...$roles): array {
  $u = require_login();
  if (!in_array($u['role'], $roles, true)) fail('Forbidden', 403);
  return $u;
}
function page_guard(string ...$roles): array {
  $u = current_user();
  if (!$u) { header('Location: index.php'); exit; }
  if ($roles && !in_array($u['role'], $roles, true)) { header('Location: index.php?e=role'); exit; }
  return $u;
}

// bcrypt login; 32-char hex MD5 seeds auto-upgrade on first success
function attempt_login(string $username, string $password): ?array {
  $st = db()->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
  $st->execute([$username]);
  $u = $st->fetch();
  if (!$u) return null;
  $ok = false;
  if (preg_match('/^[a-f0-9]{32}$/i', $u['password'])) {
    if (md5($password) === strtolower($u['password'])) {
      $ok = true;
      db()->prepare('UPDATE users SET password = ? WHERE id = ?')
        ->execute([password_hash($password, PASSWORD_BCRYPT), $u['id']]);
    }
  } else {
    $ok = password_verify($password, $u['password']);
  }
  if (!$ok) return null;
  session_regenerate_id(true);
  $_SESSION['uid'] = (int)$u['id'];
  audit((int)$u['id'], $u['name'], 'Login', 'users', (int)$u['id'], null);
  return $u;
}

function audit(int $userId, string $userName, string $action, ?string $target, ?int $targetId, ?string $reason): void {
  db()->prepare('INSERT INTO audit_log (user_id, user_name, action, target, target_id, reason) VALUES (?,?,?,?,?,?)')
    ->execute([$userId, $userName, $action, $target, $targetId, $reason]);
}

// wallet balance lives on users.wallet (kept in sync with wallet_topups log)
function wallet_balance(int $customerId): float {
  $st = db()->prepare('SELECT wallet FROM users WHERE id = ?');
  $st->execute([$customerId]);
  return (float)$st->fetchColumn();
}

function body(): array {
  $raw = file_get_contents('php://input');
  if ($raw) { $j = json_decode($raw, true); if (is_array($j)) return $j; }
  return $_POST;
}

function csrf_token(): string {
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
  return $_SESSION['csrf'];
}
function check_csrf(): void {
  $t = $_SERVER['HTTP_X_CSRF'] ?? (body()['csrf'] ?? '');
  if (!$t || !hash_equals($_SESSION['csrf'] ?? '', $t)) fail('Bad CSRF token', 403);
}

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// Cache-busting asset URL: app.js?v=<file modification time>.
// The browser and the service worker key their cache on the full URL, so a
// re-uploaded file gets a new URL and is fetched immediately, while an
// unchanged file keeps the same URL and stays cached. Without this a changed
// .js keeps serving from cache until every phone is cleared by hand.
function asset(string $file): string {
  $path = __DIR__ . '/' . $file;
  return $file . '?v=' . (is_file($path) ? filemtime($path) : '1');
}

// first active sales kitchen
function sales_kitchen_id(): int {
  return (int)db()->query("SELECT id FROM kitchens WHERE type='sales' AND status='active' ORDER BY id LIMIT 1")->fetchColumn();
}
