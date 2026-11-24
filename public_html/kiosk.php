<?php
// ============================================================================
//  ZAS Canteen — RFID counter (kiosk POS)
// ============================================================================
//  One full-screen page for the counter PC. The EMPLOYEE never logs in: they
//  tap a card, the counter sees their name and what is left of their weekly
//  limit, and the order is charged to their ZAS pay. The CASHIER signs in once
//  on this PC, which is why the sign-in below lives on this same page — the
//  kiosk browser can open this URL and nothing else.
//
//  The card number is read by the keyboard-wedge reader, turned into a SHA-256
//  fingerprint in this page, and thrown away. It is never shown, never printed,
//  never logged and never sent anywhere — only the fingerprint goes to our own
//  server, which asks ZAS about it.
//
//  The order itself is an ordinary canteen order: it lands in the sales queue,
//  in Admin, and in every report exactly like a phone order, with sales-kitchen
//  stock coming down the same way.
//
//  NOTHING THAT IS ALREADY RUNNING IS TOUCHED. This page talks to its own
//  endpoint, kiosk_api.php, not to api.php, so the file every live screen
//  depends on stays exactly as it is. Deleting this page and kiosk_api.php
//  leaves the canteen as it is today.
// ============================================================================
require_once __DIR__ . '/lib.php';

$me = current_user();
$ok = $me && in_array($me['role'], ['sales','admin','accounts'], true);
$sup = $me && in_array($me['role'], ['admin','accounts'], true);   // may cancel / refund
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>ZAS Canteen — Counter</title>
<meta name="theme-color" content="#06080F">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Noto+Naskh+Arabic:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0}
html,body{height:100%}
body{background:#06080F;color:#EDF1F8;font-family:'Space Grotesk',sans-serif;overflow:hidden}
.urdu{font-family:'Noto Naskh Arabic',serif}
button{font-family:inherit;cursor:pointer}

/* ---------- counter sign-in (shown only when nobody is signed in) ---------- */
.signin{height:100%;display:grid;place-items:center;padding:20px}
.sicard{width:min(380px,92vw);background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);border-radius:24px;padding:34px 30px;backdrop-filter:blur(18px);box-shadow:0 8px 40px rgba(0,0,0,.5)}
.logo{width:60px;height:60px;border-radius:18px;background:linear-gradient(135deg,#FFB020,#F07818);display:grid;place-items:center;font-weight:700;font-size:22px;color:#1A1205;margin:0 auto 14px}
.sicard h1{font-size:21px;text-align:center}
.sicard .sub{color:#8A93A6;font-size:13px;text-align:center;margin-bottom:22px}
.sicard label{display:block;font-size:11px;letter-spacing:1px;color:#8A93A6;margin:14px 0 6px;text-transform:uppercase}
.sicard input{width:100%;padding:14px 16px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);color:#EDF1F8;font-size:16px;font-family:inherit;outline:none}
.sicard input:focus{border-color:#FFB020}
.sicard button{width:100%;margin-top:22px;padding:15px;border:none;border-radius:14px;background:linear-gradient(135deg,#FFB020,#F07818);color:#1A1205;font-weight:700;font-size:16px}
.sicard .err{margin-top:12px;color:#FF5C5C;font-size:14px;text-align:center;min-height:18px}

/* ---------- counter layout ---------- */
.wrap{height:100%;display:grid;grid-template-columns:1fr 410px}
@media (max-width:900px){.wrap{grid-template-columns:1fr;grid-template-rows:1fr auto}}

.left{display:flex;flex-direction:column;min-height:0}
.bar{display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid rgba(255,255,255,.07)}
.bar .brand{font-weight:700;font-size:17px;letter-spacing:.3px}
.bar .brand span{color:#FFB020}
.bar .who{margin-left:auto;color:#8A93A6;font-size:13px}
.chip{padding:9px 16px;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);color:#EDF1F8;font-size:13px;font-weight:500;white-space:nowrap}
.chip:hover{border-color:rgba(255,176,32,.5)}
/* On a tablet the counter still needs one clean line of buttons, so the
   cashier's name — which it can do without — is the thing that goes. */
@media (max-width:900px){
  .bar{padding:10px 14px;gap:8px}
  .bar .brand{font-size:15px}
  .bar .who{display:none}
  .chip{padding:8px 12px;font-size:12px}
}

.dishes{flex:1;overflow-y:auto;padding:16px 20px 24px}
.dishes::-webkit-scrollbar{width:10px}
.dishes::-webkit-scrollbar-thumb{background:rgba(255,255,255,.12);border-radius:8px}
.cat{font-size:12px;letter-spacing:1.6px;text-transform:uppercase;color:#8A93A6;margin:18px 0 10px}
.cat:first-child{margin-top:0}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(168px,1fr));gap:12px}
.dish{position:relative;text-align:left;padding:0;overflow:hidden;border-radius:20px;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.05);color:#EDF1F8;transition:transform .12s,border-color .12s}
.dish:active{transform:scale(.97)}
.dish.pick{border-color:#FFB020;box-shadow:0 0 0 1px #FFB020 inset}
.dish:disabled{opacity:.34;cursor:not-allowed}
.dish .ph{width:100%;height:96px;object-fit:cover;display:block;background:#141A29}
.dish .em{height:96px;display:grid;place-items:center;font-size:46px;background:#141A29}
.dish .nm{padding:9px 12px 2px;font-size:15px;font-weight:500;line-height:1.3}
.dish .pr{padding:0 12px 11px;font-size:17px;font-weight:700;color:#FFB020}
.dish .bg{position:absolute;top:8px;right:8px;padding:3px 9px;border-radius:9px;font-size:11px;font-weight:700;background:rgba(6,8,15,.82);backdrop-filter:blur(6px)}
.dish .bg.low{color:#FF8080}
.dish .bg.have{background:#FFB020;color:#1A1205}

/* ---------- right pane ---------- */
.right{background:#0B0F1A;border-left:1px solid rgba(255,255,255,.07);display:flex;flex-direction:column;min-height:0}
@media (max-width:900px){.right{border-left:none;border-top:1px solid rgba(255,255,255,.07);max-height:52vh}}

.tap{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:30px 26px}
.tapcard{width:132px;height:88px;border-radius:16px;background:linear-gradient(135deg,#1B2538,#111828);border:1px solid rgba(255,176,32,.45);position:relative;animation:float 2.6s ease-in-out infinite}
.tapcard::before{content:'';position:absolute;left:14px;top:16px;width:26px;height:19px;border-radius:4px;background:linear-gradient(135deg,#FFB020,#F07818)}
.tapcard::after{content:'';position:absolute;right:14px;bottom:14px;left:14px;height:8px;border-radius:4px;background:rgba(255,255,255,.14)}
@keyframes float{50%{transform:translateY(-9px)}}
.ring{position:absolute;inset:-18px;border-radius:26px;border:1px solid rgba(255,176,32,.35);animation:pulse 2.6s ease-out infinite}
@keyframes pulse{0%{transform:scale(.9);opacity:.9}100%{transform:scale(1.25);opacity:0}}
.tap h2{font-size:26px;margin-top:34px}
.tap p{color:#8A93A6;font-size:15px;margin-top:8px;line-height:1.6}
.tap .hint{margin-top:18px;min-height:22px;font-size:14px;color:#FF8080;font-weight:500}

.person{padding:16px 20px;border-bottom:1px solid rgba(255,255,255,.07)}
.person .nm{font-size:23px;font-weight:700;line-height:1.3}
.person .meta{color:#8A93A6;font-size:13px;margin-top:3px}
.person .lim{margin-top:13px;font-size:14px;display:flex;justify-content:space-between;align-items:baseline}
.person .lim b{font-size:19px;color:#3DD68C}
.person .lim b.tight{color:#FFB020}
.person .lim b.out{color:#FF5C5C}
.track{height:7px;border-radius:5px;background:rgba(255,255,255,.1);margin-top:8px;overflow:hidden}
.track i{display:block;height:100%;border-radius:5px;background:linear-gradient(90deg,#FFB020,#F07818);transition:width .35s}

.cart{flex:1;overflow-y:auto;padding:8px 20px}
.cart .none{color:#8A93A6;text-align:center;padding:40px 10px;font-size:15px}
.line{display:flex;align-items:center;gap:10px;padding:11px 0;border-bottom:1px solid rgba(255,255,255,.06)}
.line .t{flex:1;min-width:0}
.line .t .n{font-size:15px;font-weight:500}
.line .t .s{font-size:12px;color:#8A93A6;margin-top:2px}
.step{display:flex;align-items:center;gap:2px}
.step button{width:34px;height:34px;border-radius:10px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.07);color:#EDF1F8;font-size:19px;font-weight:700;line-height:1}
.step .q{min-width:34px;text-align:center;font-size:18px;font-weight:700}
.line .amt{min-width:72px;text-align:right;font-size:16px;font-weight:700}

.foot{padding:14px 20px max(14px,env(safe-area-inset-bottom));border-top:1px solid rgba(255,255,255,.07);background:rgba(255,255,255,.02)}
.tot{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:12px}
.tot span{color:#8A93A6;font-size:14px}
.tot b{font-size:30px;color:#FFB020}
.acts{display:flex;gap:10px}
.acts button{flex:1;padding:17px;border-radius:15px;border:none;font-size:17px;font-weight:700}
.acts .go{background:linear-gradient(135deg,#FFB020,#F07818);color:#1A1205;flex:2}
.acts .go:disabled{background:rgba(255,255,255,.08);color:#5A6478;cursor:not-allowed}
.acts .clr{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.14);color:#EDF1F8}
.warn{margin-top:10px;font-size:13px;color:#FFB020;text-align:center;min-height:17px}

/* ---------- receipt + sheets ---------- */
.veil{position:fixed;inset:0;z-index:50;background:rgba(3,5,10,.9);backdrop-filter:blur(10px);display:grid;place-items:center;padding:24px}
.rcpt{width:min(420px,94vw);background:#0B0F1A;border:1px solid rgba(61,214,140,.35);border-radius:26px;padding:28px 26px;box-shadow:0 16px 60px rgba(0,0,0,.6)}
.rcpt .tick{width:62px;height:62px;border-radius:50%;background:rgba(61,214,140,.16);border:1px solid rgba(61,214,140,.5);color:#3DD68C;display:grid;place-items:center;font-size:30px;margin:0 auto 14px}
.rcpt h3{text-align:center;font-size:21px}
.rcpt .on{text-align:center;color:#8A93A6;font-size:13px;margin-top:4px}
.rcpt .ln{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.06);font-size:15px}
.rcpt .items{margin:18px 0 6px}
.rcpt .big{display:flex;justify-content:space-between;align-items:baseline;margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,.12)}
.rcpt .big b{font-size:26px;color:#FFB020}
.rcpt .left{margin-top:12px;text-align:center;font-size:15px;color:#3DD68C}
.rcpt .next{margin-top:18px;text-align:center;color:#5A6478;font-size:13px}

.sheet{width:min(720px,96vw);max-height:86vh;overflow-y:auto;background:#0B0F1A;border:1px solid rgba(255,255,255,.1);border-radius:24px;padding:22px}
.sheet h3{font-size:19px;margin-bottom:4px}
.sheet .sub{color:#8A93A6;font-size:13px;margin-bottom:16px}
.ord{border:1px solid rgba(255,255,255,.09);border-radius:16px;padding:13px 15px;margin-bottom:10px}
.ord .top{display:flex;align-items:baseline;gap:10px}
.ord .top .id{font-weight:700;font-size:16px}
.ord .top .amt{margin-left:auto;font-weight:700;color:#FFB020;font-size:17px}
.ord .who{font-size:14px;margin-top:3px}
.ord .its{color:#8A93A6;font-size:13px;margin-top:5px;line-height:1.5}
.ord .row2{display:flex;gap:8px;margin-top:10px;align-items:center;flex-wrap:wrap}
.pill{padding:3px 11px;border-radius:99px;font-size:11px;font-weight:700;letter-spacing:.4px;text-transform:uppercase}
.p-unsettled{background:rgba(255,176,32,.18);color:#FFB020}
.p-reserved{background:rgba(79,163,255,.18);color:#4FA3FF}
.p-settled{background:rgba(61,214,140,.16);color:#3DD68C}
.p-cancelled{background:rgba(255,92,92,.16);color:#FF8080}
.mini{padding:8px 14px;border-radius:11px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);color:#EDF1F8;font-size:13px;font-weight:600}
.mini.red{border-color:rgba(255,92,92,.4);color:#FF8080}
.mini.blue{border-color:rgba(79,163,255,.4);color:#4FA3FF}
.sheet .close{width:100%;margin-top:6px;padding:14px;border-radius:14px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);color:#EDF1F8;font-size:15px;font-weight:600}
.frm{margin-top:10px;display:flex;gap:8px;flex-wrap:wrap}
.frm input{flex:1;min-width:140px;padding:11px 13px;border-radius:11px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);color:#EDF1F8;font-size:14px;font-family:inherit;outline:none}
.frm input:focus{border-color:#FFB020}
.spin{position:fixed;inset:0;z-index:60;display:grid;place-items:center;background:rgba(3,5,10,.55)}
.spin i{width:46px;height:46px;border-radius:50%;border:3px solid rgba(255,255,255,.15);border-top-color:#FFB020;animation:sp .8s linear infinite;display:block}
@keyframes sp{to{transform:rotate(360deg)}}
[hidden]{display:none !important}
</style>
</head>
<body>

<?php if (!$ok): ?>
<!-- Nobody is signed in on this PC (or the signed-in user is not counter staff). -->
<div class="signin">
  <form class="sicard" id="si">
    <div class="logo">ZC</div>
    <h1>Counter sign-in</h1>
    <div class="sub">
      <?= $me ? 'This screen is for counter staff — sign in as sales, accounts or admin.' : 'Sign in once on this kiosk PC. Employees do not sign in; they tap a card.' ?>
    </div>
    <label>Username</label>
    <input name="username" autocomplete="username" required autofocus>
    <label>Password</label>
    <input name="password" type="password" autocomplete="current-password" required>
    <button type="submit">Sign In</button>
    <div class="err" id="sierr"></div>
  </form>
</div>
<script>
document.getElementById('si').addEventListener('submit', async function (ev) {
  ev.preventDefault();
  var fd = new FormData(this);
  var r = await fetch('api.php?a=login', { method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ username: fd.get('username'), password: fd.get('password') }) });
  var j = await r.json();
  if (!j.ok) { document.getElementById('sierr').textContent = j.error || 'Sign-in failed'; return; }
  if (['sales','admin','accounts'].indexOf(j.role) < 0) {
    document.getElementById('sierr').textContent = 'That account cannot run the counter';
    return;
  }
  location.reload();
});
</script>

<?php else: ?>
<div class="wrap">
  <div class="left">
    <div class="bar">
      <div class="brand">ZAS Canteen <span>· Counter</span></div>
      <div class="who"><?= e($me['name']) ?> · <?= e($me['role']) ?></div>
      <button class="chip" onclick="openRecent()">Today's card orders</button>
      <button class="chip" onclick="logout()">Sign out</button>
    </div>
    <div class="dishes" id="dishes"></div>
  </div>

  <div class="right">
    <!-- waiting for a card -->
    <div class="tap" id="tap">
      <div style="position:relative"><div class="ring"></div><div class="tapcard"></div></div>
      <h2>Tap your card</h2>
      <p>Hold the card on the reader<br><span class="urdu">اپنا کارڈ ریڈر پر رکھیں</span></p>
      <div class="hint" id="hint"></div>
    </div>

    <!-- card accepted -->
    <div id="live" hidden style="display:flex;flex-direction:column;min-height:0;flex:1">
      <div class="person">
        <div class="nm" id="pnm"></div>
        <div class="meta" id="pmeta"></div>
        <div class="lim"><span>Left this week</span><b id="plim"></b></div>
        <div class="track"><i id="pbar"></i></div>
      </div>
      <div class="cart" id="cart"></div>
      <div class="foot">
        <div class="tot"><span>Total</span><b id="tot">PKR 0</b></div>
        <div class="acts">
          <button class="go" id="go" onclick="placeOrder()" disabled>Confirm</button>
          <button class="clr" onclick="clearAll()">Clear</button>
        </div>
        <div class="warn" id="warn"></div>
      </div>
    </div>
  </div>
</div>

<div class="spin" id="spin" hidden><i></i></div>
<div class="veil" id="veil" hidden></div>

<script src="<?= asset('app.js') ?>"></script>
<script>
var SUP = <?= $sup ? 'true' : 'false' ?>;
var MENU = [], PHOTOS = {}, CART = [], PERSON = null, FP = '', IDLE = null, RCPT = null;

// Same contract as app.js's api(), pointed at the kiosk's own endpoint so that
// api.php — which every live screen uses — needed no change at all.
async function kapi(action, data) {
  var init = data
    ? { method:'POST', headers:{'Content-Type':'application/json','X-CSRF':window.CSRF||''},
        body: JSON.stringify(data) }
    : { method:'GET' };
  var r = await fetch('kiosk_api.php?a=' + action, init);
  if (r.status === 401) { location.reload(); throw new Error('signed out') }
  var j = await r.json();
  if (!j.ok) { toast(j.error || 'Error', true); throw new Error(j.error || 'kiosk api error') }
  return j;
}

function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){
  return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
function money(n){return 'PKR ' + Math.round(Number(n)).toLocaleString('en-PK')}
function busy(on){document.getElementById('spin').hidden = !on}
function hint(msg){
  var h = document.getElementById('hint');
  h.textContent = msg || '';
  if (msg) setTimeout(function(){ if (h.textContent === msg) h.textContent = '' }, 5000);
}

// ---------------------------------------------------------------------------
//  The reader. A 125 kHz keyboard-wedge reader types the digits and presses
//  Enter, far faster than any hand: a tap arrives within a few milliseconds
//  per digit. Anything slower is somebody at the keyboard and is not taken for
//  a card.
//
//  The digits are collected whatever their speed and judged only when Enter
//  arrives, so the two refusals say different things: too few digits, or digits
//  that came in too slowly. That matters on the day a reader is genuinely slow
//  — the screen names the real reason instead of silently doing nothing, and
//  GAP below is the one number to raise.
// ---------------------------------------------------------------------------
var GAP = 80, STALE = 3000, BUF = '', WORST = 0, TL = 0;
document.addEventListener('keydown', function (e) {
  if (RCPT) return;                                               // receipt is up
  var t = e.target.tagName;
  if (t === 'INPUT' || t === 'TEXTAREA') return;                  // a reason is being typed
  var now = Date.now();
  if (e.key.length === 1 && e.key >= '0' && e.key <= '9') {
    var gap = TL ? now - TL : 0;
    if (!BUF.length || gap > STALE) { BUF = ''; WORST = 0; }      // a fresh read
    else if (gap > WORST) WORST = gap;
    BUF += e.key; TL = now;
    e.preventDefault();
    return;
  }
  if (e.key === 'Enter') {
    var digits = BUF, worst = WORST;
    BUF = ''; WORST = 0;
    if (!digits.length) return;
    e.preventDefault();
    if (digits.length < 6)  { hint('Card read incomplete — tap again'); return }
    if (worst > GAP)        { hint('Card read too slow — tap the card on the reader'); return }
    onCard(digits);
    digits = null;
    return;
  }
  BUF = ''; WORST = 0;            // any other key: that was not a card
});

// SHA-256 in this page, so the number itself never leaves the kiosk.
async function fingerprint(digits) {
  var buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(digits));
  return Array.prototype.map.call(new Uint8Array(buf), function (b) {
    return ('0' + b.toString(16)).slice(-2) }).join('');
}

async function onCard(digits) {
  if (CART.length) { hint('Finish or clear this order first'); return }
  if (!(window.crypto && crypto.subtle)) {
    hint('Open this kiosk over https:// — card reading needs it'); return;
  }
  var f;
  try { f = await fingerprint(digits) } catch (e) { hint('Card could not be read — tap again'); return }
  digits = null;
  busy(true);
  try {
    var j = await kapi('kiosk_card', { card: f });
    if (!j.allowed) { reset(); sfx.tick(); hint(j.message); return }
    FP = f; PERSON = j; CART = [];
    paint(); resetIdle(); sfx.whoosh();
  } catch (e) { reset() }
  finally { busy(false) }
}

function reset() {
  FP = ''; PERSON = null; CART = [];
  document.getElementById('tap').hidden = false;
  document.getElementById('live').hidden = true;
  clearTimeout(IDLE);
  paintDishes();
}
function resetIdle() {
  clearTimeout(IDLE);
  // Back to "Tap your card" if nobody touches the screen — the next customer
  // must never be able to add to the last one's order.
  IDLE = setTimeout(function () { reset() }, 90000);
}
['pointerdown','keydown'].forEach(function (ev) {
  addEventListener(ev, function () { if (PERSON) resetIdle() }, { passive: true });
});

// ---------------------------------------------------------------------------
//  Menu
// ---------------------------------------------------------------------------
async function loadMenu() {
  var j = await kapi('kiosk_menu');
  MENU = j.rows || [];
  PHOTOS = j.photos || {};
  paintDishes();
}
function inCart(id) { var c = CART.find(function (x) { return x.id === id }); return c ? c.qty : 0 }

function paintDishes() {
  var box = document.getElementById('dishes');
  if (!MENU.length) { box.innerHTML = '<div class="cat">No menu items</div>'; return }
  var cats = [], by = {};
  MENU.forEach(function (m) {
    if (!by[m.category]) { by[m.category] = []; cats.push(m.category) }
    by[m.category].push(m);
  });
  var h = '';
  cats.forEach(function (c) {
    h += '<div class="cat">' + esc(c) + '</div><div class="grid">';
    by[c].forEach(function (m) {
      var stock = Math.floor(+m.stock), picked = inCart(m.id);
      var off = !PERSON || stock - picked <= 0;
      var p = PHOTOS[String(m.id)];
      h += '<button class="dish' + (picked ? ' pick' : '') + '"' + (off ? ' disabled' : '') +
        ' onclick="add(' + m.id + ')">' +
        (p ? '<img class="ph" src="' + esc(p) + '" alt="">'
           : '<div class="em">' + esc(m.emoji || '🍽') + '</div>') +
        '<div class="bg' + (picked ? ' have' : (stock <= 5 ? ' low' : '')) + '">' +
          (picked ? picked + ' added' : stock + ' left') + '</div>' +
        '<div class="nm">' + esc(m.name) + '</div>' +
        '<div class="pr">' + money(m.price) + '</div>' +
        '</button>';
    });
    h += '</div>';
  });
  box.innerHTML = h;
}

// ---------------------------------------------------------------------------
//  Cart
// ---------------------------------------------------------------------------
window.add = function (id) {
  if (!PERSON) { hint('Tap a card first'); return }
  var m = MENU.find(function (x) { return +x.id === id });
  if (!m) return;
  var c = CART.find(function (x) { return x.id === id });
  if (Math.floor(+m.stock) - (c ? c.qty : 0) <= 0) return;
  if (c) c.qty++; else CART.push({ id: id, name: m.name, price: +m.price, qty: 1 });
  sfx.tick(); resetIdle(); paint();
};
window.step = function (id, by) {
  var i = CART.findIndex(function (x) { return x.id === id });
  if (i < 0) return;
  var m = MENU.find(function (x) { return +x.id === id });
  if (by > 0 && m && Math.floor(+m.stock) - CART[i].qty <= 0) return;
  CART[i].qty += by;
  if (CART[i].qty <= 0) CART.splice(i, 1);
  sfx.tick(); resetIdle(); paint();
};
window.clearAll = function () { CART = []; sfx.tick(); resetIdle(); paint() };

function cartTotal() { return CART.reduce(function (s, c) { return s + c.price * c.qty }, 0) }

function paint() {
  document.getElementById('tap').hidden = !!PERSON;
  document.getElementById('live').hidden = !PERSON;
  if (!PERSON) { paintDishes(); return }

  document.getElementById('pnm').textContent = PERSON.name;
  document.getElementById('pmeta').textContent =
    [PERSON.code ? 'Code ' + PERSON.code : '', PERSON.department || ''].filter(Boolean).join(' · ');

  var total = cartTotal(), left = +PERSON.remaining - total, limit = +PERSON.limit || 1;
  var lb = document.getElementById('plim');
  lb.textContent = money(Math.max(0, left)) + ' of ' + money(limit);
  lb.className = left <= 0 ? 'out' : (left < limit * 0.25 ? 'tight' : '');
  document.getElementById('pbar').style.width =
    Math.max(0, Math.min(100, (Math.max(0, left) / limit) * 100)) + '%';

  var box = document.getElementById('cart');
  if (!CART.length) {
    box.innerHTML = '<div class="none">Tap the dishes on the left<br><span class="urdu">کھانا منتخب کریں</span></div>';
  } else {
    box.innerHTML = CART.map(function (c) {
      return '<div class="line">' +
        '<div class="t"><div class="n">' + esc(c.name) + '</div>' +
        '<div class="s">' + money(c.price) + ' each</div></div>' +
        '<div class="step"><button onclick="step(' + c.id + ',-1)">−</button>' +
        '<div class="q">' + c.qty + '</div>' +
        '<button onclick="step(' + c.id + ',1)">+</button></div>' +
        '<div class="amt">' + money(c.price * c.qty) + '</div></div>';
    }).join('');
  }

  document.getElementById('tot').textContent = money(total);
  var over = total > +PERSON.remaining + 0.001;
  document.getElementById('go').disabled = !CART.length || over;
  document.getElementById('warn').textContent = over
    ? 'Over the weekly limit by ' + money(total - PERSON.remaining)
    : '';
  paintDishes();
}

// ---------------------------------------------------------------------------
//  Confirm. The limit is checked again on the server, inside the transaction
//  that saves the order — this screen's check is only to keep the cashier from
//  ringing up something that cannot go through.
// ---------------------------------------------------------------------------
window.placeOrder = async function () {
  if (!PERSON || !CART.length) return;
  busy(true);
  try {
    var j = await kapi('kiosk_order', {
      card: FP,
      items: CART.map(function (c) { return { menu_item_id: c.id, qty: c.qty } })
    });
    showReceipt(j);
    sfx.chime();
    FP = ''; PERSON = null; CART = [];
    loadMenu();                       // stock just came down
  } catch (e) {
    // The error is already on screen. The cart is kept so the cashier can take
    // a line off and try again rather than start over.
  } finally { busy(false) }
};

function showReceipt(j) {
  var v = document.getElementById('veil'), n = 5;
  v.innerHTML =
    '<div class="rcpt"><div class="tick">✓</div>' +
    '<h3>Order #' + j.order_id + '</h3>' +
    '<div class="on">' + esc(j.name) + '</div>' +
    '<div class="items">' + (j.items || []).map(function (i) {
      return '<div class="ln"><span>' + i.qty + ' × ' + esc(i.name) + '</span><span>' + money(i.amount) + '</span></div>';
    }).join('') + '</div>' +
    '<div class="big"><span>Charged to pay</span><b>' + money(j.total) + '</b></div>' +
    '<div class="left">' + money(j.remaining) + ' left this week</div>' +
    '<div class="next">Back to “Tap your card” in <span id="cd">5</span> s — tap to close</div></div>';
  v.hidden = false;
  v.onclick = done;
  RCPT = setInterval(function () {
    n--;
    var el = document.getElementById('cd');
    if (el) el.textContent = n;
    if (n <= 0) done();
  }, 1000);
  function done() {
    clearInterval(RCPT); RCPT = null;
    v.hidden = true; v.onclick = null; v.innerHTML = '';
    reset();
  }
}

// ---------------------------------------------------------------------------
//  Today's card orders — and, for a supervisor, Cancel or Refund.
//  Which one is offered depends only on where the order has got to:
//  not yet on a pay sheet and the food has not gone out -> Cancel, nothing is
//  ever charged. Otherwise -> Refund, which ZAS takes off the next pay line.
// ---------------------------------------------------------------------------
window.openRecent = async function () {
  busy(true);
  var j;
  try { j = await kapi('kiosk_orders') } catch (e) { busy(false); return }
  busy(false);
  var v = document.getElementById('veil');
  var rows = j.rows || [];
  var h = '<div class="sheet"><h3>Today\'s card orders</h3>' +
    '<div class="sub">' + rows.length + ' order' + (rows.length === 1 ? '' : 's') +
    (SUP ? '' : ' · only accounts or admin can cancel or refund') + '</div>';
  if (!rows.length) h += '<div class="sub">Nothing yet today.</div>';
  rows.forEach(function (o) {
    var state = o.status === 'cancelled' ? 'cancelled' : (o.settle_state || 'unsettled');
    var canCancel = SUP && o.status !== 'cancelled' && o.settle_state === 'unsettled' &&
                    (o.status === 'pending' || o.status === 'preparing');
    var canRefund = SUP && o.status !== 'cancelled' && +o.left_to_refund > 0 && !canCancel;
    h += '<div class="ord"><div class="top"><div class="id">#' + o.id + '</div>' +
      '<span class="pill p-' + state + '">' + state + '</span>' +
      (o.sheet_ref ? '<span class="pill p-reserved">' + esc(o.sheet_ref) + '</span>' : '') +
      '<div class="amt">' + money(o.total) + '</div></div>' +
      '<div class="who">' + esc(o.customer) + (o.code ? ' · ' + esc(o.code) : '') + '</div>' +
      '<div class="its">' + (o.items || []).map(function (i) {
        return i.qty + ' × ' + esc(i.name) }).join(', ') +
      ' · ' + esc(String(o.placed_at).slice(11, 16)) + '</div>' +
      (+o.refunded > 0 ? '<div class="its">Refunded ' + money(o.refunded) + '</div>' : '') +
      (o.cancel_reason ? '<div class="its">' + esc(o.cancel_reason) + '</div>' : '') +
      (canCancel ? '<div class="row2"><button class="mini red" onclick="askCancel(' + o.id + ')">Cancel — nothing charged</button></div>' +
                   '<div id="f' + o.id + '"></div>' : '') +
      (canRefund ? '<div class="row2"><button class="mini blue" onclick="askRefund(' + o.id + ',' + (+o.left_to_refund) + ')">Refund — up to ' + money(o.left_to_refund) + '</button></div>' +
                   '<div id="f' + o.id + '"></div>' : '') +
      '</div>';
  });
  h += '<button class="close" onclick="closeSheet()">Close</button></div>';
  v.innerHTML = h; v.hidden = false;
};
window.closeSheet = function () {
  var v = document.getElementById('veil');
  v.hidden = true; v.innerHTML = '';
};
window.askCancel = function (id) {
  document.getElementById('f' + id).innerHTML =
    '<div class="frm"><input id="cr' + id + '" placeholder="Why is it being cancelled?" maxlength="200">' +
    '<button class="mini red" onclick="doCancel(' + id + ')">Cancel order</button></div>';
  document.getElementById('cr' + id).focus();
};
window.doCancel = async function (id) {
  var r = document.getElementById('cr' + id).value.trim();
  if (!r) { toast('Give a reason', true); return }
  busy(true);
  try { await kapi('kiosk_cancel', { id: id, reason: r }); toast('Order #' + id + ' cancelled'); closeSheet(); loadMenu() }
  catch (e) {} finally { busy(false) }
};
window.askRefund = function (id, max) {
  document.getElementById('f' + id).innerHTML =
    '<div class="frm"><input id="ra' + id + '" type="number" min="1" step="1" max="' + max + '" value="' + Math.round(max) + '" style="max-width:120px">' +
    '<input id="rr' + id + '" placeholder="Why is it being refunded?" maxlength="200">' +
    '<button class="mini blue" onclick="doRefund(' + id + ')">Refund</button></div>';
  document.getElementById('rr' + id).focus();
};
window.doRefund = async function (id) {
  var a = +document.getElementById('ra' + id).value;
  var r = document.getElementById('rr' + id).value.trim();
  if (!(a > 0)) { toast('Give an amount', true); return }
  if (!r) { toast('Give a reason', true); return }
  busy(true);
  try { await kapi('kiosk_refund', { id: id, amount: a, reason: r }); toast('Refunded ' + money(a)); closeSheet() }
  catch (e) {} finally { busy(false) }
};

// ---------------------------------------------------------------------------
boot().then(loadMenu).then(function () { reset() });
</script>
<?php endif; ?>
</body>
</html>
