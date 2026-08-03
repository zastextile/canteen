// ZAS Canteen — shared frontend helpers (vanilla JS, window.* globals)
window.CSRF = null;

window.api = async function (action, data, opts) {
  opts = opts || {};
  var url = 'api.php?a=' + action + (opts.qs ? '&' + opts.qs : '');
  var init;
  if (data instanceof FormData) {
    data.append('csrf', window.CSRF || '');
    init = { method: 'POST', body: data };
  } else if (data) {
    init = { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF': window.CSRF || '' },
             body: JSON.stringify(data) };
  } else {
    init = { method: 'GET' };
  }
  var r = await fetch(url, init);
  if (r.status === 401) { location.href = 'index.php'; throw new Error('logged out'); }
  var j = await r.json();
  if (!j.ok) { window.toast(j.error || 'Error', true); throw new Error(j.error || 'api error'); }
  return j;
};

window.boot = async function () {
  var me = await window.api('whoami');
  window.CSRF = me.csrf;
  window.ME = me;
  return me;
};

window.toast = function (msg, bad) {
  var t = document.createElement('div');
  t.textContent = msg;
  t.style.cssText = 'position:fixed;left:50%;bottom:88px;transform:translateX(-50%);z-index:999;' +
    'padding:12px 22px;border-radius:14px;font-size:15px;font-weight:600;max-width:86vw;text-align:center;' +
    'backdrop-filter:blur(12px);box-shadow:0 8px 30px rgba(0,0,0,.45);transition:opacity .3s;' +
    (bad ? 'background:rgba(255,92,92,.16);border:1px solid rgba(255,92,92,.4);color:#FF8080'
         : 'background:rgba(61,214,140,.14);border:1px solid rgba(61,214,140,.35);color:#3DD68C');
  document.body.appendChild(t);
  setTimeout(function () { t.style.opacity = '0'; setTimeout(function () { t.remove(); }, 350); }, 2400);
};

window.fmt = function (n) { return 'PKR ' + Number(n).toLocaleString('en-PK', { maximumFractionDigits: 0 }); };

window.logout = async function () { try { await window.api('logout', {}); } catch (e) {} location.href = 'index.php'; };

// ---- Web Audio sound engine (whoosh / tick / 4-note chime) with global mute ----
(function () {
  var ctx = null, muted = localStorage.getItem('zas_muted') === '1';
  function ac() { if (!ctx) ctx = new (window.AudioContext || window.webkitAudioContext)(); return ctx; }
  function env(g, t, a, d) { g.gain.setValueAtTime(0, t); g.gain.linearRampToValueAtTime(a, t + 0.01); g.gain.exponentialRampToValueAtTime(0.0001, t + d); }
  window.sfx = {
    get muted() { return muted; },
    toggle: function () { muted = !muted; localStorage.setItem('zas_muted', muted ? '1' : '0'); return muted; },
    tick: function () {
      if (muted) return; var c = ac(), o = c.createOscillator(), g = c.createGain();
      o.type = 'square'; o.frequency.value = 1800; env(g, c.currentTime, 0.06, 0.05);
      o.connect(g); g.connect(c.destination); o.start(); o.stop(c.currentTime + 0.06);
    },
    whoosh: function () {
      if (muted) return; var c = ac(), b = c.createBuffer(1, c.sampleRate * 0.3, c.sampleRate), d = b.getChannelData(0);
      for (var i = 0; i < d.length; i++) d[i] = (Math.random() * 2 - 1) * (1 - i / d.length);
      var s = c.createBufferSource(), f = c.createBiquadFilter(), g = c.createGain();
      s.buffer = b; f.type = 'bandpass'; f.frequency.setValueAtTime(300, c.currentTime);
      f.frequency.exponentialRampToValueAtTime(2400, c.currentTime + 0.25);
      env(g, c.currentTime, 0.18, 0.3); s.connect(f); f.connect(g); g.connect(c.destination); s.start();
    },
    chime: function () {
      if (muted) return; var c = ac(), notes = [523.25, 659.25, 783.99, 1046.5];
      notes.forEach(function (fq, i) {
        var o = c.createOscillator(), g = c.createGain(), t = c.currentTime + i * 0.12;
        o.type = 'sine'; o.frequency.value = fq; env(g, t, 0.14, 0.5);
        o.connect(g); g.connect(c.destination); o.start(t); o.stop(t + 0.55);
      });
    }
  };
})();

// ---- Flash + confetti burst ----
window.celebrate = function () {
  var f = document.createElement('div');
  f.style.cssText = 'position:fixed;inset:0;background:#fff;z-index:998;opacity:.85;pointer-events:none;transition:opacity .5s';
  document.body.appendChild(f);
  requestAnimationFrame(function () { f.style.opacity = '0'; setTimeout(function () { f.remove(); }, 550); });
  var cv = document.createElement('canvas');
  cv.style.cssText = 'position:fixed;inset:0;z-index:997;pointer-events:none';
  cv.width = innerWidth; cv.height = innerHeight;
  document.body.appendChild(cv);
  var x = cv.getContext('2d'), P = [], cols = ['#FFB020', '#F07818', '#3DD68C', '#4FA3FF', '#FF5C5C', '#EDF1F8'];
  for (var i = 0; i < 120; i++) P.push({ x: innerWidth / 2, y: innerHeight * 0.45,
    vx: (Math.random() - 0.5) * 16, vy: -Math.random() * 14 - 3, r: Math.random() * 5 + 2,
    c: cols[i % cols.length], a: 1, rot: Math.random() * 6.28, vr: (Math.random() - 0.5) * 0.3 });
  (function tick() {
    x.clearRect(0, 0, cv.width, cv.height); var live = 0;
    P.forEach(function (p) {
      p.x += p.vx; p.y += p.vy; p.vy += 0.35; p.a -= 0.011; p.rot += p.vr;
      if (p.a <= 0) return; live++;
      x.save(); x.translate(p.x, p.y); x.rotate(p.rot); x.globalAlpha = p.a;
      x.fillStyle = p.c; x.fillRect(-p.r, -p.r / 2, p.r * 2, p.r); x.restore();
    });
    if (live) requestAnimationFrame(tick); else cv.remove();
  })();
};

// ---- Animated counter ----
window.countUp = function (el, to, prefix) {
  var from = 0, t0 = null, dur = 700;
  function step(ts) {
    if (!t0) t0 = ts;
    var k = Math.min(1, (ts - t0) / dur), v = from + (to - from) * (1 - Math.pow(1 - k, 3));
    el.textContent = (prefix || '') + Math.round(v).toLocaleString('en-PK');
    if (k < 1) requestAnimationFrame(step);
  }
  requestAnimationFrame(step);
};

if ('serviceWorker' in navigator) navigator.serviceWorker.register('sw.js');

// ---- PWA install prompt: floating "Install App" pill when the browser allows it ----
window.addEventListener('beforeinstallprompt', function (e) {
  e.preventDefault();
  if (document.getElementById('pwa-install')) return;
  var b = document.createElement('button');
  b.id = 'pwa-install';
  b.textContent = '⬇ Install App';
  b.style.cssText = 'position:fixed;bottom:96px;left:50%;transform:translateX(-50%);z-index:996;' +
    'padding:12px 24px;border:none;border-radius:99px;background:linear-gradient(135deg,#FFB020,#F07818);' +
    'color:#1A1205;font-weight:700;font-size:15px;font-family:inherit;cursor:pointer;' +
    'box-shadow:0 8px 28px rgba(240,120,24,.45)';
  b.onclick = function () {
    e.prompt();
    e.userChoice.then(function () { b.remove(); });
  };
  document.body.appendChild(b);
});
