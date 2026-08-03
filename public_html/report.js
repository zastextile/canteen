// ZAS Canteen — shared Reports module.
// Loaded by BOTH admin.php and accounts.php so the two roles see an identical report.
// Renders into any container: Report.render(document.getElementById('view')).
// Styles are scoped under .zrpt so neither page's own CSS is affected.
(function () {

  var TYPES = [
    { v: 'sales',     t: '📈 Sales Totals' },
    { v: 'items',     t: '🍛 Item-wise Sales' },
    { v: 'customers', t: '🧑 Customer Spending' },
    { v: 'free',      t: '🎁 Free Items Given' },
    { v: 'wallets',   t: '💰 Wallet Ledger' },
    { v: 'purchases', t: '🚚 Purchase History' }
  ];

  // report-specific column headings; anything not listed is title-cased
  var LABELS = {
    day: 'Date', orders: 'Orders', revenue: 'Paid Sales', free_value: 'Free Value',
    total_value: 'Total Value', item: 'Item', paid_qty: 'Paid Qty', free_qty: 'Free Qty',
    customer: 'Customer', spent: 'Spent', balance: 'Wallet', qty: 'Qty', value: 'Value',
    topups: 'Top-Ups', refunds: 'Refunds', deducted: 'Deducted', rate: 'Rate',
    total: 'Total', vendor: 'Vendor', kitchen: 'Kitchen', purchase_date: 'Date',
    payment_type: 'Payment', bill_number: 'Bill #'
  };

  var CSS = ''
    + '.zrpt{animation:zrfade .25s ease}'
    + '@keyframes zrfade{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}'
    + '.zrpt h2{font-size:20px;margin:0 0 4px;display:flex;align-items:center;gap:8px;flex-wrap:wrap}'
    + '.zrpt .zsub{font-size:13px;color:#8A93A6;margin-bottom:16px}'
    + '.zrpt .zbar{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:14px}'
    + '.zrpt select,.zrpt input[type=date]{padding:9px 11px;border-radius:11px;border:1px solid rgba(255,255,255,.12);'
    + 'background:rgba(255,255,255,.06);color:#EDF1F8;font-family:inherit;font-size:14px;outline:none;width:auto;margin:0}'
    + '.zrpt select:focus,.zrpt input:focus{border-color:#FFB020}'
    + '.zrpt select option{background:#0B0F1A}'
    + '.zrpt .zb{padding:9px 15px;border:none;border-radius:11px;background:linear-gradient(135deg,#FFB020,#F07818);'
    + 'color:#1A1205;font-weight:700;font-family:inherit;font-size:13px;cursor:pointer;width:auto;margin:0}'
    + '.zrpt .zb.g{background:rgba(255,255,255,.07);color:#EDF1F8}'
    + '.zrpt .zb.g.on{background:rgba(255,176,32,.16);color:#FFB020;box-shadow:inset 0 0 0 1px #FFB020}'
    + '.zrpt .zb:active{transform:scale(.96)}'
    + '.zrpt .zpre{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px}'
    + '.zrpt .zpre .zb{font-size:12px;padding:7px 12px}'
    + '.zrpt .ztiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(148px,1fr));gap:10px;margin-bottom:18px}'
    + '.zrpt .zt{background:rgba(255,255,255,.045);border:1px solid rgba(255,255,255,.08);border-radius:15px;padding:14px}'
    + '.zrpt .zt .k{font-size:11px;letter-spacing:.8px;color:#8A93A6;text-transform:uppercase}'
    + '.zrpt .zt .v{font-size:23px;font-weight:700;margin-top:5px;line-height:1.15;word-break:break-word}'
    + '.zrpt .zt .n{font-size:11px;color:#8A93A6;margin-top:3px}'
    + '.zrpt .zt.acc .v{color:#FFB020}.zrpt .zt.grn .v{color:#3DD68C}.zrpt .zt.red .v{color:#FF8080}'
    + '.zrpt .zt.blu .v{color:#4FA3FF}'
    + '.zrpt .zcard{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);'
    + 'border-radius:15px;padding:15px;margin-bottom:16px}'
    + '.zrpt .zcard .h{font-size:11px;letter-spacing:.8px;color:#8A93A6;text-transform:uppercase;margin-bottom:12px}'
    + '.zrpt .zrow{display:grid;grid-template-columns:74px 1fr auto;gap:9px;align-items:center;margin-bottom:8px;font-size:13px}'
    + '.zrpt .zrow .d{color:#8A93A6;font-size:12px;white-space:nowrap}'
    + '.zrpt .ztrk{background:rgba(255,255,255,.05);border-radius:6px;height:15px;overflow:hidden;display:flex}'
    + '.zrpt .ztrk i{display:block;height:100%}'
    + '.zrpt .ztrk .paid{background:linear-gradient(90deg,#FFB020,#F07818)}'
    + '.zrpt .ztrk .free{background:rgba(61,214,140,.7)}'
    + '.zrpt .zamt{font-variant-numeric:tabular-nums;white-space:nowrap;font-weight:700;font-size:12px}'
    + '.zrpt .zleg{display:flex;gap:14px;font-size:11px;color:#8A93A6;margin-top:10px}'
    + '.zrpt .zleg s{width:9px;height:9px;border-radius:3px;display:inline-block;margin-right:5px;text-decoration:none}'
    + '.zrpt .zwrap{overflow-x:auto;border:1px solid rgba(255,255,255,.07);border-radius:15px;'
    + 'background:rgba(255,255,255,.03);-webkit-overflow-scrolling:touch}'
    + '.zrpt table{width:100%;border-collapse:collapse;font-size:13px;min-width:max-content;background:none;border:none}'
    + '.zrpt th{text-align:left;padding:11px 13px;background:rgba(255,255,255,.05);color:#8A93A6;'
    + 'font-size:11px;letter-spacing:.8px;text-transform:uppercase;white-space:nowrap;position:sticky;top:0}'
    + '.zrpt td{padding:10px 13px;border-top:1px solid rgba(255,255,255,.05);white-space:nowrap}'
    + '.zrpt tr:hover td{background:rgba(255,255,255,.02)}'
    + '.zrpt td.num,.zrpt th.num{text-align:right;font-variant-numeric:tabular-nums}'
    + '.zrpt td.free{color:#3DD68C}'
    + '.zrpt tr.tot td{border-top:1px solid rgba(255,176,32,.35);background:rgba(255,176,32,.07);'
    + 'font-weight:700;color:#FFB020;position:sticky;bottom:0}'
    + '.zrpt .zempty{padding:34px 16px;text-align:center;color:#8A93A6;font-size:14px}'
    + '.zrpt .zhint{display:none;font-size:11px;color:#8A93A6;margin:7px 2px 0}'
    + '@media (min-width:900px){.zrpt .ztiles{grid-template-columns:repeat(4,1fr)}}'
    + '@media (max-width:760px){.zrpt .zhint{display:block}}'
    + '@media (max-width:560px){.zrpt .ztiles{grid-template-columns:repeat(2,1fr)}'
    + '.zrpt .zt .v{font-size:19px}.zrpt .zbar select,.zrpt .zbar input[type=date]{flex:1;min-width:120px}}'
    + '@media (prefers-reduced-motion:reduce){.zrpt,.zrpt *{animation:none!important;transition:none!important}}';

  function css() {
    if (document.getElementById('zrpt-css')) return;
    var s = document.createElement('style');
    s.id = 'zrpt-css'; s.textContent = CSS;
    document.head.appendChild(s);
  }

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function num(n) { return Number(n || 0).toLocaleString('en-PK', { maximumFractionDigits: 0 }); }
  function iso(d) { return new Date(d.getTime() - d.getTimezoneOffset() * 6e4).toISOString().slice(0, 10); }
  function label(k) {
    return LABELS[k] || k.replace(/_/g, ' ').replace(/\b\w/g, function (m) { return m.toUpperCase(); });
  }
  function dayLabel(s) {
    var d = new Date(s + 'T00:00:00');
    if (isNaN(d)) return s;
    return ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][d.getDay()] + ' ' + s.slice(8) + '/' + s.slice(5, 7);
  }

  var host = null, last = null;
  var S = { type: 'sales', from: '', to: '' };

  function shell() {
    var opts = TYPES.map(function (t) {
      return '<option value="' + t.v + '"' + (t.v === S.type ? ' selected' : '') + '>' + t.t + '</option>';
    }).join('');
    host.innerHTML = '<div class="zrpt">'
      + '<h2>📊 Reports</h2>'
      + '<div class="zsub" id="zsub">Loading…</div>'
      + '<div class="zpre">'
      +   '<button class="zb g" data-p="today">Today</button>'
      +   '<button class="zb g" data-p="7">7 Days</button>'
      +   '<button class="zb g" data-p="30">30 Days</button>'
      +   '<button class="zb g" data-p="month">This Month</button>'
      +   '<button class="zb g" data-p="last">Last Month</button>'
      + '</div>'
      + '<div class="zbar">'
      +   '<select id="ztype">' + opts + '</select>'
      +   '<input type="date" id="zfrom" value="' + S.from + '">'
      +   '<input type="date" id="zto" value="' + S.to + '">'
      +   '<button class="zb" id="zrun">Run</button>'
      +   '<button class="zb g" id="zcsv">⬇ CSV</button>'
      + '</div>'
      + '<div id="zbody"></div></div>';

    host.querySelector('#ztype').onchange = function () { S.type = this.value; load(); };
    host.querySelector('#zrun').onclick = function () { if (window.sfx) sfx.tick(); read(); load(); };
    host.querySelector('#zcsv').onclick = csv;
    host.querySelectorAll('.zpre .zb').forEach(function (b) {
      b.onclick = function () { if (window.sfx) sfx.tick(); preset(b.dataset.p); };
    });
  }

  function read() {
    S.from = host.querySelector('#zfrom').value || S.from;
    S.to = host.querySelector('#zto').value || S.to;
  }

  function preset(p) {
    var now = new Date(), a = new Date(), b = new Date();
    if (p === 'today') { /* a = b = today */ }
    else if (p === '7') { a.setDate(now.getDate() - 6); }
    else if (p === '30') { a.setDate(now.getDate() - 29); }
    else if (p === 'month') { a = new Date(now.getFullYear(), now.getMonth(), 1); }
    else if (p === 'last') {
      a = new Date(now.getFullYear(), now.getMonth() - 1, 1);
      b = new Date(now.getFullYear(), now.getMonth(), 0);
    }
    S.from = iso(a); S.to = iso(b);
    host.querySelector('#zfrom').value = S.from;
    host.querySelector('#zto').value = S.to;
    host.querySelectorAll('.zpre .zb').forEach(function (x) { x.classList.toggle('on', x.dataset.p === p); });
    load();
  }

  async function load() {
    var body = host.querySelector('#zbody');
    body.innerHTML = '<div class="zempty">Loading…</div>';
    try {
      last = await window.api('report', null,
        { qs: 'type=' + encodeURIComponent(S.type) + '&from=' + S.from + '&to=' + S.to });
    } catch (e) {
      body.innerHTML = '<div class="zempty">Could not load the report.</div>';
      return;
    }
    draw();
  }

  function draw() {
    var s = last.summary;
    host.querySelector('#zsub').textContent =
      S.from === S.to ? S.from : S.from + '  →  ' + S.to;

    var h = '<div class="ztiles">'
      + tile('Paid Sales', 'PKR ' + num(s.revenue), s.orders + ' orders', 'acc')
      + tile('Free Given', 'PKR ' + num(s.free_value), s.free_units + ' units', 'grn')
      + tile('Total Served', 'PKR ' + num(s.revenue + s.free_value), 'paid + free', '')
      + tile('Avg Order', 'PKR ' + num(s.avg_order), s.customers + ' customers', '')
      + tile('Purchases', 'PKR ' + num(s.purchases), s.bills + ' bills', 'blu')
      + tile('Gross Margin', 'PKR ' + num(s.margin), 'sales − purchases', s.margin < 0 ? 'red' : 'grn')
      + tile('Cancelled', 'PKR ' + num(s.lost_value), s.lost_orders + ' refunded', 'red')
      + tile('Top-Ups', 'PKR ' + num(s.topups), 'wallet held ' + num(s.liability), '')
      + '</div>';

    if (S.type === 'sales' && last.rows.length) h += chart(last.rows);
    h += table(last.rows, last.money || []);
    host.querySelector('#zbody').innerHTML = h;
  }

  function tile(k, v, n, cls) {
    return '<div class="zt ' + cls + '"><div class="k">' + esc(k) + '</div>'
      + '<div class="v">' + esc(v) + '</div><div class="n">' + esc(n) + '</div></div>';
  }

  function chart(rows) {
    var max = 0;
    rows.forEach(function (r) { max = Math.max(max, (+r.revenue || 0) + (+r.free_value || 0)); });
    if (!max) return '';
    var h = '<div class="zcard"><div class="h">Daily Trend — paid vs free</div>';
    rows.slice().reverse().forEach(function (r) {
      var paid = +r.revenue || 0, free = +r.free_value || 0;
      h += '<div class="zrow"><div class="d">' + esc(dayLabel(r.day)) + '</div>'
        + '<div class="ztrk"><i class="paid" style="width:' + (paid / max * 100) + '%"></i>'
        + '<i class="free" style="width:' + (free / max * 100) + '%"></i></div>'
        + '<div class="zamt">' + num(paid + free) + '</div></div>';
    });
    return h + '<div class="zleg"><span><s style="background:#FFB020"></s>Paid</span>'
      + '<span><s style="background:rgba(61,214,140,.7)"></s>Free</span></div></div>';
  }

  function table(rows, money) {
    if (!rows.length) return '<div class="zwrap"><div class="zempty">No data in this date range.</div></div>';
    var cols = Object.keys(rows[0]);
    var isNum = {}, sums = {};
    cols.forEach(function (c) {
      var n = rows.every(function (r) { return r[c] === null || r[c] === '' || !isNaN(+r[c]); });
      isNum[c] = n && c !== 'day' && c !== 'purchase_date' && c !== 'bill_number';
      if (isNum[c]) sums[c] = rows.reduce(function (a, r) { return a + (+r[c] || 0); }, 0);
    });

    var h = '<div class="zwrap"><table><tr>';
    cols.forEach(function (c) {
      h += '<th class="' + (isNum[c] ? 'num' : '') + '">' + esc(label(c))
        + (money.indexOf(c) >= 0 ? ' <span style="opacity:.6">PKR</span>' : '') + '</th>';
    });
    h += '</tr>';

    rows.forEach(function (r) {
      h += '<tr>';
      cols.forEach(function (c) {
        var v = r[c], cls = (isNum[c] ? 'num ' : '') + (/free/.test(c) ? 'free' : '');
        var txt = isNum[c] ? num(v) : (c === 'day' ? dayLabel(v) : v);
        h += '<td class="' + cls.trim() + '">' + esc(txt) + '</td>';
      });
      h += '</tr>';
    });

    h += '<tr class="tot">';
    cols.forEach(function (c, i) {
      h += '<td class="' + (isNum[c] ? 'num' : '') + '">'
        + (isNum[c] ? num(sums[c]) : (i === 0 ? 'TOTAL — ' + rows.length + ' rows' : '')) + '</td>';
    });
    return h + '</tr></table></div>'
      + '<div class="zhint">← swipe the table sideways for the remaining columns</div>';
  }

  function csv() {
    if (!last || !last.rows.length) { if (window.toast) toast('Nothing to export', true); return; }
    var cols = Object.keys(last.rows[0]);
    var lines = [cols.map(label).join(',')];
    last.rows.forEach(function (r) {
      lines.push(cols.map(function (c) {
        var v = r[c] == null ? '' : String(r[c]);
        return /[",\n]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v;
      }).join(','));
    });
    var name = 'canteen-' + S.type + '-' + S.from + '-to-' + S.to + '.csv';
    var url = URL.createObjectURL(new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' }));
    var a = document.createElement('a');
    a.href = url; a.download = name; a.click();
    setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
    if (window.toast) toast('Exported ' + last.rows.length + ' rows');
  }

  window.Report = {
    render: function (container) {
      css();
      host = container;
      var fresh = !S.from;
      if (fresh) {
        var n = new Date();
        S.from = iso(new Date(n.getFullYear(), n.getMonth(), 1));
        S.to = iso(n);
      }
      shell();
      if (fresh) {
        var b = host.querySelector('.zpre .zb[data-p="month"]');
        if (b) b.classList.add('on');
      }
      load();
    }
  };
})();
