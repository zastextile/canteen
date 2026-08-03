<?php
require_once __DIR__ . '/lib.php';
$me = page_guard('accounts');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>ZAS Canteen — Accounts</title>
<meta name="theme-color" content="#06080F">
<link rel="manifest" href="manifest.json">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Noto+Naskh+Arabic:wght@400;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0}
body{background:#06080F;color:#EDF1F8;font-family:'Space Grotesk',sans-serif;min-height:100vh;padding-bottom:86px}
.urdu{font-family:'Noto Naskh Arabic',serif}
header{padding:18px 20px 8px;display:flex;justify-content:space-between;align-items:center}
h1{font-size:20px}
.card{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);border-radius:20px;padding:18px;margin:12px 16px;backdrop-filter:blur(14px)}
label{display:block;font-size:12px;letter-spacing:1px;color:#8A93A6;margin:12px 0 6px;text-transform:uppercase}
input,select,textarea{width:100%;padding:14px 16px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);color:#EDF1F8;font-size:16px;font-family:inherit;outline:none}
input:focus,select:focus{border-color:#FFB020}
select option{background:#0B0F1A}
.btn{width:100%;margin-top:18px;padding:16px;border:none;border-radius:14px;background:linear-gradient(135deg,#FFB020,#F07818);color:#1A1205;font-weight:700;font-size:16px;font-family:inherit;cursor:pointer;position:relative;overflow:hidden}
.btn::after{content:'';position:absolute;top:0;left:-60%;width:40%;height:100%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.45),transparent);animation:sw 3s infinite}
@keyframes sw{to{left:130%}}
.btn:active{transform:scale(.98)}
#view{animation:fadein .25s ease}
@keyframes fadein{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
@media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}
.crow{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.06);font-size:14px}
.crow:last-child{border-bottom:none}
.freetag{display:inline-block;background:rgba(61,214,140,.18);color:#3DD68C;border:1px solid rgba(61,214,140,.4);font-size:11px;font-weight:700;padding:1px 8px;border-radius:99px}
.rm{padding:8px 14px;border-radius:10px;border:1px solid rgba(255,92,92,.4);background:rgba(255,92,92,.12);color:#FF8080;font-family:inherit;font-size:13px;font-weight:700;cursor:pointer}
nav{position:fixed;bottom:0;left:0;right:0;display:flex;background:rgba(11,15,26,.92);backdrop-filter:blur(16px);border-top:1px solid rgba(255,255,255,.08);padding:8px 0 max(8px, env(safe-area-inset-bottom))}
nav a{flex:1;text-align:center;color:#8A93A6;text-decoration:none;font-size:11px;padding:6px 0;cursor:pointer}
nav a.on{color:#FFB020}
nav a span{display:block;font-size:22px;margin-bottom:2px}
.quick{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:8px}
.quick button{padding:12px 0;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);color:#EDF1F8;font-family:inherit;font-weight:700;cursor:pointer}
.quick button.on{background:rgba(255,176,32,.16);border-color:#FFB020;color:#FFB020}
.total{font-size:26px;font-weight:700;color:#FFB020;text-align:center;margin-top:10px}
</style>
</head>
<body>
<header><h1>💼 Accounts</h1><a onclick="logout()" style="color:#FF8080;cursor:pointer;font-size:14px">Logout</a></header>
<div id="view"></div>
<nav>
  <a data-t="purchase" class="on"><span>🧾</span>Purchase</a>
  <a data-t="alloc"><span>🛒</span>Allocation</a>
  <a data-t="topup"><span>💰</span>Top-Up</a>
  <a data-t="comp"><span>🎁</span>Free Items</a>
  <a data-t="reports"><span>📊</span>Reports</a>
</nav>
<script src="app.js"></script>
<script src="report.js"></script>
<script>
var V=document.getElementById('view'),DATA={};
function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
document.querySelectorAll('nav a').forEach(function(a){a.onclick=function(){sfx.tick();
  document.querySelectorAll('nav a').forEach(function(x){x.classList.toggle('on',x===a)});
  ({purchase:purchase,alloc:alloc,topup:topup,comp:comp,reports:reports})[a.dataset.t]()}});

// Reports — same module, same numbers as the Admin dashboard (report.js).
// Wrapped in a padded div because this page's cards carry their own side margin.
function reports(){
  V.innerHTML='<div id="rpthost" style="padding:4px 16px"></div>';
  Report.render(document.getElementById('rpthost'));
}

async function loadRefs(){
  if(DATA.done)return;
  var r=await Promise.all([api('kitchens_list'),api('vendors_list'),api('items_list'),api('menu_list'),api('wallets_list')]);
  DATA.kitchens=r[0].rows;DATA.vendors=r[1].rows.filter(function(v){return+v.is_active});
  DATA.items=r[2].rows.filter(function(i){return+i.is_active});DATA.menu=r[3].rows.filter(function(m){return+m.is_active});
  DATA.customers=r[4].rows;DATA.done=true;
}

// ---------- New Purchase ----------
async function purchase(){
  V.innerHTML='<div class="card">Loading…</div>';
  await loadRefs();
  var chefs=DATA.kitchens.filter(function(k){return k.type==='chef'&&+k.is_active});
  V.innerHTML='<form class="card" id="pf">'+
    '<label>Chef Kitchen</label><select name="kitchen_id" required>'+chefs.map(function(k){return '<option value="'+k.id+'">'+esc(k.name)+'</option>'}).join('')+'</select>'+
    '<label>Item (from Item Master)</label><select name="item_id" id="pit" required>'+DATA.items.map(function(i){return '<option value="'+i.id+'" data-unit="'+esc(i.unit)+'">'+esc(i.name)+'</option>'}).join('')+'</select>'+
    '<label>Quantity <span id="punit" style="color:#FFB020"></span></label><input name="qty" id="pq" type="number" step="0.001" min="0.001" required>'+
    '<label>Rate (PKR per unit)</label><input name="rate" id="pr" type="number" step="0.01" min="0.01" required>'+
    '<div class="total" id="ptot">PKR 0</div>'+
    '<label>Vendor</label><select name="vendor_id" required>'+DATA.vendors.map(function(v){return '<option value="'+v.id+'">'+esc(v.name)+'</option>'}).join('')+'</select>'+
    '<label>Bill Number</label><input name="bill_number">'+
    '<label>Payment Type</label><select name="payment_type"><option value="cash">Cash</option><option value="bank">Bank</option><option value="credit">Credit</option></select>'+
    '<label>Bill Image (photo/pdf)</label><input name="bill_image" type="file" accept="image/*,.pdf">'+
    '<label>Remarks</label><textarea name="remarks" rows="2"></textarea>'+
    '<button class="btn" type="submit">Save Purchase → Stock Added</button></form>';
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

// ---------- Sales Allocation ----------
async function alloc(){
  V.innerHTML='<div class="card">Loading…</div>';
  await loadRefs();
  var sales=DATA.kitchens.filter(function(k){return k.type==='sales'&&+k.is_active});
  V.innerHTML='<form class="card" id="af">'+
    '<label>Sales Kitchen</label><select name="kitchen_id">'+sales.map(function(k){return '<option value="'+k.id+'">'+esc(k.name)+'</option>'}).join('')+'</select>'+
    '<label>Menu Item</label><select name="menu_item_id">'+DATA.menu.map(function(m){return '<option value="'+m.id+'">'+(m.emoji||'')+' '+esc(m.name)+'</option>'}).join('')+'</select>'+
    '<label>Quantity</label><input name="qty" type="number" min="1" required>'+
    '<button class="btn" type="submit">Allocate to Sales Stock</button></form>';
  document.getElementById('af').onsubmit=async function(ev){
    ev.preventDefault();var fd=new FormData(this);
    await api('allocate',{kitchen_id:fd.get('kitchen_id'),menu_item_id:fd.get('menu_item_id'),qty:fd.get('qty')});
    sfx.whoosh();toast('Allocated ✓');this.reset();
  };
}

// ---------- Wallet Top-Up ----------
async function topup(){
  V.innerHTML='<div class="card">Loading…</div>';
  DATA.done=false;await loadRefs();      // refresh balances
  V.innerHTML='<form class="card" id="tf">'+
    '<label>Customer</label><select name="customer_id" id="tc">'+DATA.customers.map(function(c){return '<option value="'+c.id+'">'+esc(c.name)+' — '+fmt(c.balance)+'</option>'}).join('')+'</select>'+
    '<label>Amount (PKR)</label><div class="quick" id="tq">'+[500,1000,2000,5000].map(function(a){return '<button type="button" data-a="'+a+'">'+a+'</button>'}).join('')+'</div>'+
    '<input name="amount" id="ta" type="number" min="1" placeholder="Custom amount" style="margin-top:10px" required>'+
    '<button class="btn" type="submit">Add Balance</button></form>';
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

// ---------- Complimentary (free items) ----------
async function comp(){
  V.innerHTML='<div class="card">Loading…</div>';
  await loadRefs();
  var j=await api('comp_list');
  var h='<form class="card" id="cf2">'+
    '<label>Customer</label><select name="customer_id">'+DATA.customers.map(function(c){return '<option value="'+c.id+'">'+esc(c.name)+'</option>'}).join('')+'</select>'+
    '<label>Menu Item (given free)</label><select name="menu_item_id">'+DATA.menu.map(function(m){return '<option value="'+m.id+'">'+(m.emoji||'')+' '+esc(m.name)+'</option>'}).join('')+'</select>'+
    '<label>Quantity Limit</label><input name="qty_allowed" type="number" min="1" max="100" value="1" required>'+
    '<label>Limit Type</label><select name="repeat_daily"><option value="0">Total — one-time allowance</option><option value="1">Daily — resets every day</option></select>'+
    '<label>Days (none = every day)</label><div class="quick" style="grid-template-columns:repeat(7,1fr)" id="cdays">'+['Mo','Tu','We','Th','Fr','Sa','Su'].map(function(d,ix){return '<button type="button" data-v="'+(ix+1)+'" onclick="this.classList.toggle(\'on\')">'+d+'</button>'}).join('')+'</div>'+
    '<label>Time Window (optional)</label><div style="display:flex;gap:8px;align-items:center"><input name="time_from" type="time"><span style="color:#8A93A6">to</span><input name="time_to" type="time"></div>'+
    '<label>Expiry Date (optional)</label><input name="expiry_date" type="date" min="'+new Date().toISOString().slice(0,10)+'">'+
    '<button class="btn" type="submit">Assign Free Item</button></form>';
  var act=j.rows.filter(function(r){return r.state==='active'||r.state==='used up'});
  if(act.length){
    h+='<div class="card"><label style="margin-top:0">Current Allocations</label>';
    act.forEach(function(r){
      var D={'1':'Mon','2':'Tue','3':'Wed','4':'Thu','5':'Fri','6':'Sat','7':'Sun'},sch=[];
      if(r.days_of_week)sch.push(r.days_of_week.split(',').map(function(v){return D[v]}).join(' '));
      if(r.time_from)sch.push(r.time_from.slice(0,5)+'–'+r.time_to.slice(0,5));
      if(+r.repeat_daily)sch.push('daily reset');
      h+='<div class="crow"><div><b>'+esc(r.customer)+'</b> — '+(r.emoji||'')+' '+esc(r.item)+' <span class="freetag">'+r.qty_used+'/'+r.qty_allowed+'</span>'+
         '<div style="font-size:12px;color:#8A93A6">'+(sch.join(' · ')||'any time')+' · '+(r.expiry_date?'till '+r.expiry_date:'no expiry')+' · by '+esc(r.assigned_by)+'</div></div>'+
         '<button type="button" class="rm" onclick="compRm('+r.id+')">Remove</button></div>';
    });
    h+='</div>';
  }
  V.innerHTML=h;
  document.getElementById('cf2').onsubmit=async function(ev){
    ev.preventDefault();var fd=new FormData(this);
    var days=Array.prototype.slice.call(document.querySelectorAll('#cdays button.on')).map(function(b){return b.dataset.v}).join(',');
    var j=await api('comp_save',{customer_ids:[+fd.get('customer_id')],menu_item_ids:[+fd.get('menu_item_id')],
      qty_allowed:fd.get('qty_allowed'),expiry_date:fd.get('expiry_date'),days:days,
      time_from:fd.get('time_from'),time_to:fd.get('time_to'),repeat_daily:fd.get('repeat_daily')==='1'?1:0});
    sfx.chime();toast(j.created?'Free item assigned ✓':'Already assigned — skipped');comp();
  };
}
window.compRm=async function(id){
  if(!confirm('Remove this free-item allocation?'))return;
  await api('comp_remove',{id:id});toast('Removed');comp();
};

boot().then(purchase);
</script>
</body>
</html>
