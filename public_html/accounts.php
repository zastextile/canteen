<?php
require_once __DIR__ . '/lib.php';
$me = page_guard('accounts');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ZAS Canteen — Accounts</title>
<meta name="theme-color" content="#06080F">
<link rel="manifest" href="manifest.json">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Noto+Naskh+Arabic:wght@400;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0}
body{background:#06080F;color:#EDF1F8;font-family:'Space Grotesk',sans-serif;display:flex;min-height:100vh}
.urdu{font-family:'Noto Naskh Arabic',serif}
aside{width:230px;flex:none;background:#0B0F1A;border-right:1px solid rgba(255,255,255,.07);padding:20px 12px;display:flex;flex-direction:column;gap:2px;position:sticky;top:0;height:100vh;overflow-y:auto}
.logo{display:flex;align-items:center;gap:10px;padding:6px 10px 18px}
.logo b{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#FFB020,#F07818);display:grid;place-items:center;color:#1A1205;font-size:15px}
aside a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:#8A93A6;text-decoration:none;font-size:14px;cursor:pointer}
aside a.on{background:rgba(255,176,32,.12);color:#FFB020}
aside a:hover{background:rgba(255,255,255,.05);color:#EDF1F8}
main{flex:1;padding:28px 32px;min-width:0}
h1{font-size:22px;margin-bottom:18px}
.tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:22px}
.tile{background:rgba(255,255,255,.045);border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:18px}
.tile .k{font-size:12px;letter-spacing:1px;color:#8A93A6;text-transform:uppercase}
.tile .v{font-size:30px;font-weight:700;margin-top:6px}
table{width:100%;border-collapse:collapse;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:14px;overflow:hidden;font-size:14px}
th{text-align:left;padding:11px 14px;background:rgba(255,255,255,.05);color:#8A93A6;font-size:12px;letter-spacing:1px;text-transform:uppercase}
tr:hover td{background:rgba(255,255,255,.02)}
td{padding:11px 14px;border-top:1px solid rgba(255,255,255,.05)}
.btn{padding:8px 16px;border:none;border-radius:10px;background:linear-gradient(135deg,#FFB020,#F07818);color:#1A1205;font-weight:700;font-family:inherit;font-size:13px;cursor:pointer;transition:transform .12s,filter .15s}
.btn:hover{filter:brightness(1.08)}
.btn:active{transform:scale(.96)}
.btn.ghost{background:rgba(255,255,255,.07);color:#EDF1F8}
.btn.red{background:rgba(255,92,92,.15);color:#FF8080}
.btn.step{padding:0;width:36px;height:34px;font-size:19px;line-height:1;background:rgba(255,255,255,.08);color:#EDF1F8;vertical-align:middle}
.btn.step:hover{background:rgba(255,176,32,.2);color:#FFB020;filter:none}
.btn.step.minus:hover{background:rgba(255,92,92,.2);color:#FF8080}
.btn.step[disabled]{opacity:.3;cursor:not-allowed;pointer-events:none}
.qv{font-weight:700;font-size:16px;font-variant-numeric:tabular-nums;display:inline-block;min-width:44px}
.qv.low{color:#FF5C5C}
.qv.busy{color:#FFB020}
input,select,textarea{padding:10px 12px;border-radius:10px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);color:#EDF1F8;font-family:inherit;font-size:14px;outline:none}
input:focus,select:focus{border-color:#FFB020}
select option{background:#0B0F1A}
.bar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:16px}
.form{display:grid;gap:14px;max-width:520px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:18px;padding:22px}
.form .row{display:grid;gap:6px}
.form label{font-size:12px;color:#8A93A6;letter-spacing:1px;text-transform:uppercase}
.form input,.form select,.form textarea{width:100%}
.total{font-size:26px;font-weight:700;color:#FFB020;text-align:center}
.quick{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}
.quick button{padding:12px 0;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);color:#EDF1F8;font-family:inherit;font-weight:700;cursor:pointer}
.quick button.on{background:rgba(255,176,32,.16);border-color:#FFB020;color:#FFB020}
.pill{padding:3px 10px;border-radius:99px;font-size:12px;font-weight:700}
.p-delivered{background:rgba(61,214,140,.15);color:#3DD68C}.p-cancelled{background:rgba(255,92,92,.15);color:#FF8080}
.freetag{display:inline-block;background:rgba(61,214,140,.18);color:#3DD68C;border:1px solid rgba(61,214,140,.4);font-size:11px;font-weight:700;padding:1px 8px;border-radius:99px}
dialog{background:#0F1524;color:#EDF1F8;border:1px solid rgba(255,255,255,.12);border-radius:18px;padding:24px;width:min(460px,92vw)}
dialog::backdrop{background:rgba(0,0,0,.6)}
dialog h3{margin-bottom:14px}
dialog .row{display:grid;gap:6px;margin-bottom:12px}
dialog label{font-size:12px;color:#8A93A6;letter-spacing:1px;text-transform:uppercase}
main{animation:mfade .25s ease}
@keyframes mfade{from{opacity:0}to{opacity:1}}
@media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}
@media (max-width:860px){
  body{flex-direction:column}
  aside{width:100%;height:auto;position:static;flex-direction:row;flex-wrap:wrap;padding:10px}
  aside a{font-size:13px;padding:8px 10px}
  .logo{padding:6px 10px}
  aside a[style*="margin-top"]{margin-top:0!important}
  main{padding:18px 14px}
  table{display:block;overflow-x:auto;white-space:nowrap}
}
</style>
</head>
<body>
<aside>
  <div class="logo"><b>ZC</b><span style="font-weight:700">Accounts</span></div>
  <a data-s="purchase" class="on">🧾 New Purchase</a>
  <a data-s="alloc">🛒 Allocate to Sales</a>
  <a data-s="salesstock">📦 Sales Stock</a>
  <a data-s="topup">💰 Wallet Top-Up</a>
  <a data-s="comp">🎁 Free Items</a>
  <a data-s="photos">📷 Dish Photos</a>
  <a data-s="reports">📈 Reports</a>
  <a data-s="account">⚙️ My Account</a>
  <a onclick="logout()" style="margin-top:auto;color:#FF8080">⏻ Logout</a>
</aside>
<main id="main"></main>
<dialog id="dlg"></dialog>
<script src="app.js"></script>
<script src="report.js"></script>
<script>
var M=document.getElementById('main'),DLG=document.getElementById('dlg'),DATA={},CACHE={};
function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
function openDlg(h){DLG.innerHTML=h;DLG.showModal()}
function closeDlg(){DLG.close()}
function nav(s){
  document.querySelectorAll('aside a[data-s]').forEach(function(a){a.classList.toggle('on',a.dataset.s===s)});
  ({purchase:purchase,alloc:alloc,salesstock:salesstock,topup:topup,comp:comp,
    photos:photos,reports:reports,account:account})[s]();
}
document.querySelectorAll('aside a[data-s]').forEach(function(a){a.onclick=function(){sfx.tick();nav(a.dataset.s)}});

async function loadRefs(force){
  if(DATA.done&&!force)return;
  var r=await Promise.all([api('kitchens_list'),api('vendors_list'),api('items_list'),api('menu_list'),api('wallets_list')]);
  DATA.kitchens=r[0].rows;DATA.vendors=r[1].rows.filter(function(v){return+v.is_active});
  DATA.items=r[2].rows.filter(function(i){return+i.is_active});DATA.menu=r[3].rows.filter(function(m){return+m.is_active});
  DATA.customers=r[4].rows;DATA.done=true;
}

// ---------- New Purchase ----------
async function purchase(){
  M.innerHTML='<h1>New Purchase</h1><div class="form">Loading…</div>';
  await loadRefs();
  var chefs=DATA.kitchens.filter(function(k){return k.type==='chef'&&+k.is_active});
  M.innerHTML='<h1>New Purchase</h1><form class="form" id="pf">'+
    '<div class="row"><label>Chef Kitchen</label><select name="kitchen_id" required>'+chefs.map(function(k){return '<option value="'+k.id+'">'+esc(k.name)+'</option>'}).join('')+'</select></div>'+
    '<div class="row"><label>Item (from Item Master)</label><select name="item_id" id="pit" required>'+DATA.items.map(function(i){return '<option value="'+i.id+'" data-unit="'+esc(i.unit)+'">'+esc(i.name)+'</option>'}).join('')+'</select></div>'+
    '<div class="row"><label>Quantity <span id="punit" style="color:#FFB020"></span></label><input name="qty" id="pq" type="number" step="0.001" min="0.001" required></div>'+
    '<div class="row"><label>Rate (PKR per unit)</label><input name="rate" id="pr" type="number" step="0.01" min="0.01" required></div>'+
    '<div class="total" id="ptot">PKR 0</div>'+
    '<div class="row"><label>Vendor</label><select name="vendor_id" required>'+DATA.vendors.map(function(v){return '<option value="'+v.id+'">'+esc(v.name)+'</option>'}).join('')+'</select></div>'+
    '<div class="row"><label>Bill Number</label><input name="bill_number"></div>'+
    '<div class="row"><label>Payment Type</label><select name="payment_type"><option value="cash">Cash</option><option value="bank">Bank</option><option value="credit">Credit</option></select></div>'+
    '<div class="row"><label>Bill Image (photo/pdf)</label><input name="bill_image" type="file" accept="image/*,.pdf"></div>'+
    '<div class="row"><label>Remarks</label><textarea name="remarks" rows="2"></textarea></div>'+
    '<button class="btn" type="submit" style="padding:14px">Save Purchase → Stock Added</button></form>';
  function unit(){var o=pit.selectedOptions[0];punit.textContent=o?'('+o.dataset.unit+')':''}
  function tot(){ptot.textContent=fmt((+pq.value||0)*(+pr.value||0))}
  pit.onchange=unit;pq.oninput=tot;pr.oninput=tot;unit();
  document.getElementById('pf').onsubmit=async function(ev){
    ev.preventDefault();
    var j=await api('purchase_save',new FormData(this));
    sfx.chime();celebrate();toast('Saved — '+fmt(j.total)+' · stock added to kitchen');
    this.reset();tot();unit();
  };
}

// ---------- Allocate to Sales ----------
async function alloc(){
  M.innerHTML='<h1>Allocate to Sales Kitchen</h1><div class="form">Loading…</div>';
  await loadRefs();
  var sales=DATA.kitchens.filter(function(k){return k.type==='sales'&&+k.is_active});
  M.innerHTML='<h1>Allocate to Sales Kitchen</h1><form class="form" id="af">'+
    '<div class="row"><label>Sales Kitchen</label><select name="kitchen_id">'+sales.map(function(k){return '<option value="'+k.id+'">'+esc(k.name)+'</option>'}).join('')+'</select></div>'+
    '<div class="row"><label>Menu Item</label><select name="menu_item_id">'+DATA.menu.map(function(m){return '<option value="'+m.id+'">'+(m.emoji||'')+' '+esc(m.name)+'</option>'}).join('')+'</select></div>'+
    '<div class="row"><label>Quantity</label><input name="qty" type="number" min="1" required></div>'+
    '<button class="btn" type="submit" style="padding:14px">Allocate to Sales Stock</button></form>';
  document.getElementById('af').onsubmit=async function(ev){
    ev.preventDefault();var fd=new FormData(this);
    await api('allocate',{kitchen_id:fd.get('kitchen_id'),menu_item_id:fd.get('menu_item_id'),qty:fd.get('qty')});
    sfx.whoosh();toast('Allocated ✓');this.reset();
  };
}

// ---------- Sales Stock ----------
async function salesstock(){
  M.innerHTML='<h1>Sales Stock (Today)</h1><div class="bar">'+
    '<span style="color:#8A93A6;font-size:13px">Tap − / + to change by 1. Dishes with no stock yet are listed too, so a new menu item shows up here straight away.</span></div><div id="tbl">Loading…</div>';
  var j=await api('salesstock_list');CACHE.ss=j.rows;STEP={};
  var h='<table><tr><th>Kitchen</th><th>Menu Item</th><th>Remaining Qty</th><th style="text-align:right">Adjust Stock</th></tr>';
  j.rows.forEach(function(r,i){
    h+='<tr><td>'+esc(r.kitchen)+'</td><td>'+(r.emoji||'')+' '+esc(r.name)+
       (+r.never_stocked?' <span class="pill" style="background:rgba(255,255,255,.07);color:#8A93A6">never stocked</span>':'')+'</td>'+
       '<td><span class="qv" id="q'+i+'"></span></td>'+
       '<td style="text-align:right;white-space:nowrap">'+
         '<button class="btn step minus" id="m'+i+'" onclick="stepQty('+i+',-1)" title="Reduce by 1">−</button> '+
         '<button class="btn step" onclick="stepQty('+i+',1)" title="Add 1">+</button> '+
         '<button class="btn ghost" onclick="bulkForm('+i+')">Set qty</button>'+
       '</td></tr>';
  });
  document.getElementById('tbl').innerHTML=h+'</table>';
  j.rows.forEach(function(r,i){paintQty(i)});
}
// Taps land on screen at once; the accumulated change is sent as ONE request
// after tapping stops, so five taps on − is a single -5 and one audit row.
var STEP={};
function paintQty(i){
  var el=document.getElementById('q'+i); if(!el)return;
  var r=CACHE.ss[i],q=+r.qty,s=STEP[i];
  el.className='qv'+(q<=5?' low':'')+(s&&s.delta?' busy':'');
  el.textContent=q+(q<=5?' ⚠':'');
  var m=document.getElementById('m'+i); if(m)m.disabled=(q<=0);
}
window.stepQty=function(i,d){
  var r=CACHE.ss[i],next=(+r.qty)+d;
  if(next<0){toast('Already at zero',true);return}
  r.qty=next;
  var s=STEP[i]||(STEP[i]={delta:0,timer:null});
  s.delta+=d;paintQty(i);sfx.tick();
  clearTimeout(s.timer);
  s.timer=setTimeout(function(){flushStep(i)},700);
};
async function flushStep(i){
  var s=STEP[i]; if(!s||!s.delta)return;
  var d=s.delta,r=CACHE.ss[i]; s.delta=0;
  try{
    await api('allocate',{kitchen_id:r.kitchen_id,menu_item_id:r.menu_item_id,qty:d});
    paintQty(i);toast(esc(r.name)+' '+(d>0?'+':'')+d+' → '+r.qty);sfx.whoosh();
  }catch(e){toast('Could not save — reloading stock',true);nav('salesstock')}
}
window.bulkForm=function(i){
  var r=CACHE.ss[i];
  openDlg('<h3>Set stock</h3><p style="color:#8A93A6;margin-top:0">'+(r.emoji||'')+' <b>'+esc(r.name)+'</b> — '+esc(r.kitchen)+' · now <b>'+r.qty+'</b></p>'+
    '<div class="row"><label>New quantity</label><input id="bq" type="number" min="0" value="'+r.qty+'"></div>'+
    '<div style="display:flex;gap:10px;justify-content:flex-end"><button class="btn ghost" onclick="closeDlg()">Cancel</button>'+
    '<button class="btn" onclick="bulkGo('+i+')">Apply</button></div>');
};
window.bulkGo=async function(i){
  var r=CACHE.ss[i],want=parseInt(document.getElementById('bq').value,10);
  if(isNaN(want)||want<0){alert('Enter a quantity of 0 or more');return}
  var d=want-(+r.qty);
  if(!d){closeDlg();return}
  try{
    await api('allocate',{kitchen_id:r.kitchen_id,menu_item_id:r.menu_item_id,qty:d});
    closeDlg();toast('Set to '+want);sfx.whoosh();nav('salesstock');
  }catch(e){alert('Could not set stock: '+(e&&e.message?e.message:e))}
};

// ---------- Wallet Top-Up ----------
async function topup(){
  M.innerHTML='<h1>Wallet Top-Up</h1><div class="form">Loading…</div>';
  await loadRefs(true);
  M.innerHTML='<h1>Wallet Top-Up</h1><form class="form" id="tf">'+
    '<div class="row"><label>Customer</label><select name="customer_id" id="tc">'+DATA.customers.map(function(c){return '<option value="'+c.id+'">'+esc(c.name)+' — '+fmt(c.balance)+'</option>'}).join('')+'</select></div>'+
    '<div class="row"><label>Amount (PKR)</label><div class="quick" id="tq">'+[500,1000,2000,5000].map(function(a){return '<button type="button" data-a="'+a+'">'+a+'</button>'}).join('')+'</div></div>'+
    '<div class="row"><input name="amount" id="ta" type="number" min="1" placeholder="Custom amount" required></div>'+
    '<button class="btn" type="submit" style="padding:14px">Add Balance</button></form>';
  document.querySelectorAll('#tq button').forEach(function(b){b.onclick=function(){sfx.tick();
    document.querySelectorAll('#tq button').forEach(function(x){x.classList.toggle('on',x===b)});
    ta.value=b.dataset.a}});
  document.getElementById('tf').onsubmit=async function(ev){
    ev.preventDefault();
    var j=await api('topup',{customer_id:tc.value,amount:ta.value});
    sfx.chime();celebrate();toast('New balance: '+fmt(j.balance));
    DATA.done=false;topup();
  };
}

// ---------- Free Items ----------
async function comp(){
  M.innerHTML='<h1>Free Items</h1><div class="form">Loading…</div>';
  await loadRefs();
  var j=await api('comp_list');
  var h='<h1>Free Items</h1><form class="form" id="cf2" style="margin-bottom:22px">'+
    '<div class="row"><label>Customer</label><select name="customer_id">'+DATA.customers.map(function(c){return '<option value="'+c.id+'">'+esc(c.name)+'</option>'}).join('')+'</select></div>'+
    '<div class="row"><label>Menu Item (given free)</label><select name="menu_item_id">'+DATA.menu.map(function(m){return '<option value="'+m.id+'">'+(m.emoji||'')+' '+esc(m.name)+'</option>'}).join('')+'</select></div>'+
    '<div class="row"><label>Quantity Limit</label><input name="qty_allowed" type="number" min="1" max="100" value="1" required></div>'+
    '<div class="row"><label>Limit Type</label><select name="repeat_daily"><option value="0">Total — one-time allowance</option><option value="1">Daily — resets every day</option></select></div>'+
    '<div class="row"><label>Days (none = every day)</label><div class="quick" style="grid-template-columns:repeat(7,1fr)" id="cdays">'+['Mo','Tu','We','Th','Fr','Sa','Su'].map(function(d,ix){return '<button type="button" data-v="'+(ix+1)+'" onclick="this.classList.toggle(\'on\')">'+d+'</button>'}).join('')+'</div></div>'+
    '<div class="row"><label>Time Window (optional)</label><div style="display:flex;gap:8px;align-items:center"><input name="time_from" type="time"><span style="color:#8A93A6">to</span><input name="time_to" type="time"></div></div>'+
    '<div class="row"><label>Expiry Date (optional)</label><input name="expiry_date" type="date" min="'+new Date().toISOString().slice(0,10)+'"></div>'+
    '<button class="btn" type="submit" style="padding:14px">Assign Free Item</button></form>';
  var act=j.rows.filter(function(r){return r.state==='active'||r.state==='used up'});
  if(act.length){
    h+='<table><tr><th>Customer</th><th>Item</th><th>Used</th><th>Schedule</th><th>Assigned by</th><th></th></tr>';
    act.forEach(function(r){
      var D={'1':'Mon','2':'Tue','3':'Wed','4':'Thu','5':'Fri','6':'Sat','7':'Sun'},sch=[];
      if(r.days_of_week)sch.push(r.days_of_week.split(',').map(function(v){return D[v]}).join(' '));
      if(r.time_from)sch.push(r.time_from.slice(0,5)+'–'+r.time_to.slice(0,5));
      if(+r.repeat_daily)sch.push('daily reset');
      h+='<tr><td>'+esc(r.customer)+'</td><td>'+(r.emoji||'')+' '+esc(r.item)+'</td>'+
         '<td><span class="freetag">'+r.qty_used+'/'+r.qty_allowed+'</span></td>'+
         '<td style="color:#8A93A6;font-size:13px">'+(sch.join(' · ')||'any time')+' · '+(r.expiry_date?'till '+r.expiry_date:'no expiry')+'</td>'+
         '<td style="color:#8A93A6">'+esc(r.assigned_by)+'</td>'+
         '<td style="text-align:right"><button class="btn red" onclick="compRm('+r.id+')">Remove</button></td></tr>';
    });
    h+='</table>';
  }
  M.innerHTML=h;
  document.getElementById('cf2').onsubmit=async function(ev){
    ev.preventDefault();var fd=new FormData(this);
    var days=Array.prototype.slice.call(document.querySelectorAll('#cdays button.on')).map(function(b){return b.dataset.v}).join(',');
    var j2=await api('comp_save',{customer_ids:[+fd.get('customer_id')],menu_item_ids:[+fd.get('menu_item_id')],
      qty_allowed:fd.get('qty_allowed'),expiry_date:fd.get('expiry_date'),days:days,
      time_from:fd.get('time_from'),time_to:fd.get('time_to'),repeat_daily:fd.get('repeat_daily')==='1'?1:0});
    sfx.chime();toast(j2.created?'Free item assigned ✓':'Already assigned — skipped');nav('comp');
  };
}
window.compRm=async function(id){
  if(!confirm('Remove this free-item allocation?'))return;
  await api('comp_remove',{id:id});toast('Removed');nav('comp');
};

// ---------- Dish Photos ----------
// Whatever picture is chosen is cover-cropped to 4:5 and re-encoded to a
// 512x640 WebP in the browser, so a 4 MB camera shot arrives around 30 KB.
async function photos(){
  M.innerHTML='<h1>Dish Photos</h1><div class="bar"><span style="color:#8A93A6;font-size:13px">'+
    'Customers see these instead of the emoji. Any picture works — it is resized automatically.</span></div><div id="tbl">Loading…</div>';
  await loadRefs(true);
  var ph={};try{ph=(await api('dish_photo_list')).photos||{}}catch(e){}
  CACHE.ph=ph;CACHE.m=DATA.menu;
  var h='<table><tr><th>Photo</th><th>Item</th><th>Category</th><th>Price</th><th style="text-align:right"></th></tr>';
  DATA.menu.forEach(function(m,i){
    var p=ph[String(m.id)];
    h+='<tr><td>'+(p?'<img src="'+p.url+'?v='+Date.now()+'" alt="" style="width:46px;height:58px;object-fit:cover;border-radius:8px;display:block">'
      :'<div style="width:46px;height:58px;border-radius:8px;border:1px dashed rgba(255,255,255,.2);display:grid;place-items:center;font-size:20px">'+(m.emoji||'🍽')+'</div>')+'</td>'+
      '<td>'+esc(m.name)+(p?'<div style="font-size:11px;color:#3DD68C">photo '+p.kb+' KB</div>':'')+'</td>'+
      '<td>'+esc(m.category)+'</td><td>'+fmt(m.price)+'</td>'+
      '<td style="text-align:right"><button class="btn ghost" onclick="dishPhoto('+i+')">📷 '+(p?'Replace':'Add photo')+'</button></td></tr>';
  });
  document.getElementById('tbl').innerHTML=h+'</table>';
}
window.DISHPHOTO='';
window.dishPhoto=function(i){
  var m=CACHE.m[i],p=(CACHE.ph||{})[String(m.id)];
  DISHPHOTO='';
  openDlg('<h3>Photo — '+esc(m.name)+'</h3>'+
    '<p style="color:#8A93A6;margin:-6px 0 14px;font-size:13px">Pick any photo; it is resized to 512×640 WebP automatically. '+
    'No photo means the '+(m.emoji||'🍽')+' emoji is used.</p>'+
    '<div style="display:flex;gap:16px;align-items:flex-start;margin-bottom:14px">'+
      '<img id="dprev" src="'+(p?p.url+'?v='+Date.now():'')+'" style="width:110px;height:138px;object-fit:cover;border-radius:12px;'+
      'background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);'+(p?'':'display:none')+'">'+
      '<div id="dnone" style="width:110px;height:138px;border-radius:12px;border:1px dashed rgba(255,255,255,.2);'+
      'display:'+(p?'none':'grid')+';place-items:center;font-size:40px">'+(m.emoji||'🍽')+'</div>'+
      '<div style="flex:1"><input type="file" accept="image/*" onchange="dishPick(this)" style="width:100%">'+
      '<div id="dinfo" style="color:#8A93A6;font-size:12px;margin-top:10px">'+(p?'Current photo '+p.kb+' KB':'No photo yet')+'</div></div></div>'+
    '<div style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap">'+
      (p?'<button class="btn red" onclick="dishPhotoDel('+m.id+')">Remove</button>':'')+
      '<button class="btn ghost" onclick="closeDlg()">Cancel</button>'+
      '<button class="btn" onclick="dishPhotoSave('+m.id+')">Save photo</button></div>');
};
window.dishPick=function(inp){
  var f=inp.files[0];if(!f)return;
  var img=new Image();
  img.onload=function(){
    var W=512,H=640,c=document.createElement('canvas');c.width=W;c.height=H;
    var x=c.getContext('2d'),s=Math.max(W/img.width,H/img.height),w=img.width*s,h=img.height*s;
    x.drawImage(img,(W-w)/2,(H-h)/2,w,h);
    DISHPHOTO=c.toDataURL('image/webp',0.75);
    if(DISHPHOTO.indexOf('image/webp')<0)DISHPHOTO=c.toDataURL('image/jpeg',0.8);
    var pv=document.getElementById('dprev');pv.src=DISHPHOTO;pv.style.display='block';
    document.getElementById('dnone').style.display='none';
    document.getElementById('dinfo').textContent='Ready to save — '+Math.round(DISHPHOTO.length*0.75/1024)+' KB, 512×640';
  };
  img.onerror=function(){toast('Could not read this image',true)};
  img.src=URL.createObjectURL(f);
};
window.dishPhotoSave=async function(id){
  if(!DISHPHOTO){toast('Pick a photo first',true);return}
  try{var j=await api('dish_photo_save',{menu_item_id:id,data:DISHPHOTO});
    closeDlg();toast('Photo saved — '+j.kb+' KB');sfx.whoosh();nav('photos');
  }catch(e){alert('Upload failed: '+(e&&e.message?e.message:e))}
};
window.dishPhotoDel=async function(id){
  if(!confirm('Remove this photo? The menu will show the emoji again.'))return;
  await api('dish_photo_delete',{menu_item_id:id});
  closeDlg();toast('Photo removed');nav('photos');
};

// ---------- Reports ----------
function reports(){ Report.render(M); }

// ---------- My Account ----------
async function account(){
  M.innerHTML='<h1>My Account</h1><div class="form">'+
    '<div class="row"><label>Name</label><input id="an" value="'+esc(ME.name)+'"></div>'+
    '<div class="row"><label>Username</label><input id="au" value="'+esc(ME.username||'')+'"></div>'+
    '<div class="row"><label>New Password (leave blank to keep)</label><input id="ap" type="password" placeholder="••••••"></div>'+
    '<button class="btn" style="padding:14px" onclick="saveAccount()">Save Changes</button></div>';
}
window.saveAccount=async function(){
  var b={name:an.value.trim(),username:au.value.trim()};
  if(ap.value)b.new_password=ap.value;
  await api('my_account',b);
  toast('Saved');sfx.chime();
  var me=await api('whoami');ME.name=me.name;ME.username=me.username;
  nav('account');
};

boot().then(function(){nav('purchase')});
</script>
</body>
</html>
