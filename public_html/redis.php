<?php
/*
  ZAS Canteen — Redis integration, isolated in this one file.
  Two independent things, both fully optional at runtime:
   1. redis_session_boot() — routes PHP sessions to Redis if it's reachable,
      otherwise leaves PHP's default file sessions untouched. Must be called
      BEFORE session_start().
   2. cache_get()/cache_set()/cache_remember() — a tiny Redis-backed cache.
      Every function fails soft: if the redis extension isn't loaded, or the
      server isn't reachable, callers just always get a cache miss and fall
      through to the normal DB query. Nothing here can break a page.

  Cloudways runs Redis with ACL enabled: this app is only allowed to touch
  keys starting with REDIS_PREFIX (enforced server-side — any other key is
  rejected with NOPERM). Every key this file writes or reads is prefixed
  automatically via rk(), so callers elsewhere in the app never need to
  think about the prefix.

  Reversible: delete this file and remove the two lines in lib.php that
  reference it, and the app is back to exactly how it was.
*/

const REDIS_HOST = '127.0.0.1';
const REDIS_PORT = 6379;
// From Cloudways: Server > Access Details > Redis
const REDIS_USERNAME = 'zfpvkgkjhr';
const REDIS_PASSWORD = '342QWzDx34';
const REDIS_PREFIX = 'zfpvkgkjhr:'; // ACL restricts this app's user to keys under this prefix only
const REDIS_TIMEOUT = 0.2; // seconds — fail fast, never hang a page on a dead Redis

function rk(string $key): string { return REDIS_PREFIX . $key; }

/* Single shared connection per request. Connection is attempted at most once
   per request (the $tried latch) — if it fails, every subsequent cache_get/
   cache_set call in the same request is a cheap no-op instead of retrying.
   A bare TCP connect can succeed even when the server demands auth, so this
   also authenticates and pings before trusting the connection — otherwise a
   wrong/missing credential would look "reachable" here but fail later on
   the first real command. */
function redis_conn(): ?Redis {
  static $r = null;
  static $tried = false;
  if ($tried) return $r;
  $tried = true;
  if (!extension_loaded('redis')) return null;
  try {
    $c = new Redis();
    if (!@$c->connect(REDIS_HOST, REDIS_PORT, REDIS_TIMEOUT)) return null;
    if (!@$c->auth(['user' => REDIS_USERNAME, 'pass' => REDIS_PASSWORD])) return null;
    if (!@$c->ping()) return null;
    $r = $c;
  } catch (Throwable $e) {
    $r = null;
  }
  return $r;
}

/* ---------------- PHP session -> Redis (with automatic fallback) ---------------- */
function redis_session_boot(): void {
  if (session_status() !== PHP_SESSION_NONE) return; // already started elsewhere — don't interfere
  if (!redis_conn()) return; // unreachable/missing extension/bad auth: default file sessions apply, untouched
  // ini_set only takes effect if set before session_start() — caller must
  // call this first. auth[user]/auth[pass] + prefix are required by Cloudways' ACL setup.
  $path = 'tcp://' . REDIS_HOST . ':' . REDIS_PORT
    . '?timeout=' . REDIS_TIMEOUT . '&read_timeout=' . REDIS_TIMEOUT
    . '&auth[user]=' . rawurlencode(REDIS_USERNAME) . '&auth[pass]=' . rawurlencode(REDIS_PASSWORD)
    . '&prefix=' . rawurlencode(REDIS_PREFIX . 'PHPREDIS_SESSION:');
  @ini_set('session.save_handler', 'redis');
  @ini_set('session.save_path', $path);
}

/* ---------------- tiny cache helper ---------------- */
function cache_get(string $key) {
  $r = redis_conn();
  if (!$r) return null;
  try {
    $v = $r->get(rk($key));
    return $v === false ? null : json_decode($v, true);
  } catch (Throwable $e) { return null; }
}
function cache_set(string $key, $value, int $ttl = 30): void {
  $r = redis_conn();
  if (!$r) return;
  try { $r->setex(rk($key), $ttl, json_encode($value)); } catch (Throwable $e) {}
}

/* Get-or-compute: returns the cached value if present, otherwise runs $fn(),
   caches its result, and returns it. If Redis is down, $fn() just runs every
   time — same behaviour as before this feature existed. */
function cache_remember(string $key, int $ttl, callable $fn) {
  $v = cache_get($key);
  if ($v !== null) return $v;
  $v = $fn();
  cache_set($key, $v, $ttl);
  return $v;
}

/* ---------------- version-tag invalidation ----------------
   Several different write actions (purchase, allocate, consume, adjust,
   place/cancel/reverse order) all affect "inventory" reads. Rather than
   have every write site reconstruct and delete exact cache keys (easy to
   miss one), each read embeds the current version number of the tag(s) it
   depends on; any write just bumps the tag, which invalidates every key
   that embeds it — instantly, with one call, regardless of how many
   different key variants (per kitchen, per menu group, etc.) exist. */
function cache_version(string $tag): int {
  $r = redis_conn();
  if (!$r) return 0;
  try {
    $v = $r->get(rk("ver:$tag"));
    return $v === false ? 0 : (int)$v;
  } catch (Throwable $e) { return 0; }
}
function cache_bump(string $tag): void {
  $r = redis_conn();
  if (!$r) return;
  try { $r->incr(rk("ver:$tag")); } catch (Throwable $e) {}
}
