<?php
require_once __DIR__ . '/lib.php';
$me = page_guard('admin');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ZAS Canteen — Admin</title>
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
.btn.step{padding:0;width:36px;height:34px;font-size:19px;line-height:1;background:rgba(255,255,255,.08);color:#EDF1F8;vertical-align:middle}
.btn.step:hover{background:rgba(255,176,32,.2);color:#FFB020;filter:none}
.btn.step.minus:hover{background:rgba(255,92,92,.2);color:#FF8080}
.btn.step[disabled]{opacity:.3;cursor:not-allowed;pointer-events:none}
.qv{font-weight:700;font-size:16px;font-variant-numeric:tabular-nums;display:inline-block;min-width:44px}
.qv.low{color:#FF5C5C}
.qv.busy{color:#FFB020}
.btn.ghost.on{background:rgba(255,176,32,.15);color:#FFB020;box-shadow:inset 0 0 0 1px #FFB020}
.btn.red{background:rgba(255,92,92,.15);color:#FF8080}
input,select,textarea{padding:10px 12px;border-radius:10px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);color:#EDF1F8;font-family:inherit;font-size:14px;outline:none}
input:focus,select:focus{border-color:#FFB020}
select option{background:#0B0F1A}
.bar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:16px}
.pill{padding:3px 10px;border-radius:99px;font-size:12px;font-weight:700}
.p-pending{background:rgba(255,176,32,.15);color:#FFB020}.p-preparing{background:rgba(79,163,255,.15);color:#4FA3FF}
.p-delivered{background:rgba(61,214,140,.15);color:#3DD68C}.p-cancelled,.p-reversed{background:rgba(255,92,92,.15);color:#FF8080}
dialog{background:#0F1524;color:#EDF1F8;border:1px solid rgba(255,255,255,.12);border-radius:18px;padding:24px;width:min(440px,92vw)}
dialog[open]{animation:dlgin .18s ease}
@keyframes dlgin{from{opacity:0;transform:translateY(10px) scale(.98)}to{opacity:1;transform:none}}
main{animation:mfade .25s ease}
@keyframes mfade{from{opacity:0}to{opacity:1}}
@media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}
@media (max-width:860px){body{flex-direction:column}aside{width:100%;height:auto;position:static;flex-direction:row;flex-wrap:wrap;padding:10px}aside a{font-size:13px;padding:8px 10px}.logo{padding:6px 10px}aside a[style*="margin-top"]{margin-top:0!important}main{padding:18px 14px}table{display:block;overflow-x:auto;white-space:nowrap}}
dialog::backdrop{background:rgba(0,0,0,.6)}
dialog h3{margin-bottom:14px}
dialog .row{display:grid;gap:6px;margin-bottom:12px}
dialog label{font-size:12px;color:#8A93A6;letter-spacing:1px;text-transform:uppercase}
.spark{height:36px;margin-top:8px}
</style>
</head>
<body>
<aside>
  <div class="logo"><b>ZC</b><span style="font-weight:700">ZAS Canteen</span></div>
  <a data-s="dash" class="on">📊 Dashboard</a>
  <a data-s="users">👥 Users</a>
  <a data-s="customers">🧑 Customers</a>
  <a data-s="kitchens">🏭 Kitchens</a>
  <a data-s="vendors">🚚 Vendors</a>
  <a data-s="items">📦 Item Master</a>
  <a data-s="menu">🍛 Menu Items</a>
  <a data-s="orders">🧾 Orders</a>
  <a data-s="inventory">🥬 Inventory</a>
  <a data-s="salesstock">🛒 Sales Stock</a>
  <a data-s="wallets">💰 Wallets</a>
  <a data-s="comp">🎁 Complimentary</a>
  <a data-s="reports">📈 Reports</a>
  <a data-s="audit">🕓 Audit Log</a>
  <a data-s="account">⚙️ My Account</a>
  <a onclick="logout()" style="margin-top:auto;color:#FF8080">⏻ Logout</a>
</aside>
<main id="main"></main>
<dialog id="dlg"></dialog>
<script src="app.js"></script>
<script src="report.js"></script>
<script>
var M = document.getElementById('main'), DLG = document.getElementById('dlg');
var CACHE = {};

function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
function nav(s){
  document.querySelectorAll('aside a[data-s]').forEach(function(a){a.classList.toggle('on',a.dataset.s===s)});
  ({dash:dash,users:function(){users()},customers:function(){users('customer')},kitchens:kitchens,vendors:vendors,
    items:items,menu:menu,orders:orders,inventory:inventory,salesstock:salesstock,wallets:wallets,comp:comp,
    reports:reports,audit:auditLog,account:account})[s]();
}
document.querySelectorAll('aside a[data-s]').forEach(function(a){a.onclick=function(){sfx.tick();nav(a.dataset.s)}});

function openDlg(html){DLG.innerHTML=html;DLG.showModal()}
function closeDlg(){DLG.close()}

// Photo picker: accepts ANY image format (webp/jpg/png/heic…), force-compresses
// client-side to a 256×256 webp (~5-15 KB) before upload.
window.PHOTO='';
window.pickPhoto=function(inp){
  var f=inp.files[0];if(!f)return;
  var img=new Image();
  img.onload=function(){
    var s=256,c=document.createElement('canvas');c.width=c.height=s;
    var x=c.getContext('2d'),m=Math.min(img.width,img.height);
    x.drawImage(img,(img.width-m)/2,(img.height-m)/2,m,m,0,0,s,s);
    PHOTO=c.toDataURL('image/webp',0.6);
    if(PHOTO.indexOf('image/webp')<0)PHOTO=c.toDataURL('image/jpeg',0.6); // Safari fallback
    var p=document.getElementById('pprev');p.src=PHOTO;p.style.display='block';
    toast('Photo compressed to '+Math.round(PHOTO.length*0.75/1024)+' KB');
  };
  img.onerror=function(){toast('Could not read this image',true)};
  img.src=URL.createObjectURL(f);
};

// ---------- Dashboard ----------
async function dash(){
  M.innerHTML='<h1>Dashboard</h1><div class="tiles" id="t"></div>';
  var s=await api('stats');
  var t=document.getElementById('t');
  [['Pending Orders',s.pending,''],['Today\'s Sales',Math.round(s.sales_today),'PKR '],
   ['Customers',s.customers,''],['Menu Items',s.menu_items,'']].forEach(function(d,i){
    var el=document.createElement('div');el.className='tile';
    el.innerHTML='<div class="k">'+d[0]+'</div><div class="v" id="v'+i+'"></div>'+(i===1?'<svg class="spark" id="spark" width="100%" height="36"></svg>':'');
    t.appendChild(el);countUp(el.querySelector('.v'),d[1],d[2]);
  });
  // sparkline
  var sp=s.spark||[];if(sp.length>1){
    var svg=document.getElementById('spark'),W=svg.clientWidth||180,H=36,max=Math.max.apply(null,sp.map(function(p){return+p.t}))||1;
    var pts=sp.map(function(p,i){return (i/(sp.length-1)*W)+','+(H-4-(+p.t/max)*(H-8))}).join(' ');
    svg.innerHTML='<polyline points="'+pts+'" fill="none" stroke="#FFB020" stroke-width="2" stroke-linecap="round"/>';
  }
}

// ---------- Users / Customers ----------
async function users(roleFilter){
  var isCust=roleFilter==='customer';
  M.innerHTML='<h1>'+(isCust?'Customers':'Users')+'</h1><div class="bar"><button class="btn" onclick="userForm(null,'+(isCust?'1':'0')+')">+ New '+(isCust?'Customer':'User')+'</button></div><div id="tbl"></div>';
  var j=await api('users_list',null,{qs:roleFilter?'role='+roleFilter:''});
  CACHE.users=j.rows;
  var h='<table><tr><th>Name</th><th>Username</th><th>Role</th><th>Kitchen</th><th>Status</th><th></th></tr>';
  j.rows.forEach(function(u,i){
    var av=u.photo_url?'<img src="'+esc(u.photo_url)+'" style="width:34px;height:34px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:8px">':'';
    h+='<tr><td>'+av+esc(u.name)+(u.name_urdu?' <span class="urdu" style="color:#8A93A6">'+esc(u.name_urdu)+'</span>':'')+'</td><td>'+esc(u.username)+'</td><td>'+u.role+'</td><td>'+esc(u.kitchen||'—')+'</td>'+
      '<td>'+(+u.is_active?'<span class="pill p-delivered">Active</span>':'<span class="pill p-cancelled">Disabled</span>')+'</td>'+
      '<td style="text-align:right"><button class="btn ghost" onclick="userForm('+i+','+(isCust?'1':'0')+')">Edit</button> <button class="btn red" onclick="userDel('+u.id+')">Delete</button></td></tr>';
  });
  document.getElementById('tbl').innerHTML=h+'</table>';
}
window.userForm=async function(i,isCust){
  var u=i==null?{}:CACHE.users[i];
  PHOTO='';
  var ks=(await api('kitchens_list')).rows.filter(function(k){return+k.is_active});
  openDlg('<h3>'+(u.id?'Edit':'New')+(isCust?' Customer':' User')+'</h3>'+
    '<div class="row"><label>Name</label><input id="fn" value="'+esc(u.name||'')+'"></div>'+
    '<div class="row"><label>Name (Urdu, optional)</label><input id="fnu" class="urdu" dir="rtl" value="'+esc(u.name_urdu||'')+'"></div>'+
    '<div class="row"><label>Username</label><input id="fu" value="'+esc(u.username||'')+'"></div>'+
    '<div class="row"><label>Role</label><select id="fr" '+(isCust?'disabled':'')+'>'+['admin','accounts','chef','sales','customer'].map(function(r){return '<option '+(r===(isCust?'customer':u.role)?'selected':'')+'>'+r+'</option>'}).join('')+'</select></div>'+
    '<div class="row"><label>Kitchen (chef/sales only)</label><select id="fk"><option value="">—</option>'+ks.map(function(k){return '<option value="'+k.id+'" '+(+u.kitchen_id===+k.id?'selected':'')+'>'+esc(k.name)+' ('+k.type+')</option>'}).join('')+'</select></div>'+
    '<div class="row"><label>Photo (any format — auto-compressed very small)</label><input type="file" accept="image/*" onchange="pickPhoto(this)">'+
    '<img id="pprev" src="'+esc(u.photo_url||'')+'" style="width:64px;height:64px;border-radius:50%;object-fit:cover;margin-top:8px;border:2px solid rgba(255,176,32,.5);'+(u.photo_url?'':'display:none')+'"></div>'+
    '<div class="row"><label>'+(u.id?'Reset Password (leave blank to keep)':'Password')+'</label><input id="fp" type="password"></div>'+
    '<div class="row"><label>Status</label><select id="fa"><option value="1">Active</option><option value="0" '+(u.id&&!+u.is_active?'selected':'')+'>Disabled</option></select></div>'+
    (isCust?'<div class="row"><label>Menu Access (tap to allow — any combination)</label><div style="display:flex;gap:6px;flex-wrap:wrap">'+[['standard','Standard'],['special','⭐ Special'],['executive','👑 Executive']].map(function(g){var on=((u.id?(u.menu_access||''):'standard').split(',').indexOf(g[0])>=0);return '<button type="button" class="btn ghost mab'+(on?' on':'')+'" data-v="'+g[0]+'" onclick="this.classList.toggle(\'on\')" style="padding:7px 13px">'+g[1]+'</button>'}).join('')+'</div></div>':'')+
    '<div style="display:flex;gap:10px;justify-content:flex-end"><button class="btn ghost" onclick="closeDlg()">Cancel</button><button class="btn" onclick="userSave('+(u.id||0)+','+isCust+')">Save</button></div>');
};
window.userSave=async function(id,isCust){
  var p={id:id,name:fn.value,name_urdu:fnu.value,username:fu.value,
    role:isCust?'customer':fr.value,kitchen_id:fk.value,new_password:fp.value,is_active:fa.value,photo_data:PHOTO};
  if(isCust){
    var acc=Array.prototype.slice.call(document.querySelectorAll('.mab.on')).map(function(b){return b.dataset.v});
    if(!acc.length){alert('Select at least one menu access type (Standard, Special or Executive)');return}
    p.menu_access=acc.join(',');
  }
  await api('user_save',p);
  closeDlg();toast('Saved');sfx.whoosh();nav(isCust?'customers':'users');
};
window.userDel=async function(id){
  if(!confirm('Delete this user? (If they have order/wallet history they will be disabled instead, so records stay intact.)'))return;
  var j=await api('user_delete',{id:id});toast(j.msg||'Deleted');nav('users');
};

// ---------- Kitchens ----------
async function kitchens(){
  M.innerHTML='<h1>Kitchens</h1><div class="bar"><button class="btn" onclick="kForm(null)">+ New Kitchen</button></div><div id="tbl"></div>';
  var j=await api('kitchens_list');CACHE.k=j.rows;
  var h='<table><tr><th>Name</th><th>Type</th><th>Status</th><th></th></tr>';
  j.rows.forEach(function(k,i){h+='<tr><td>'+esc(k.name)+'</td><td>'+k.type+'</td><td>'+(+k.is_active?'<span class="pill p-delivered">Active</span>':'<span class="pill p-cancelled">Disabled</span>')+'</td><td style="text-align:right"><button class="btn ghost" onclick="kForm('+i+')">Edit</button></td></tr>'});
  document.getElementById('tbl').innerHTML=h+'</table>';
}
window.kForm=function(i){
  var k=i==null?{}:CACHE.k[i];
  openDlg('<h3>'+(k.id?'Edit':'New')+' Kitchen</h3>'+
    '<div class="row"><label>Name</label><input id="fn" value="'+esc(k.name||'')+'"></div>'+
    '<div class="row"><label>Type</label><select id="ft"><option '+(k.type==='chef'?'selected':'')+'>chef</option><option '+(k.type==='sales'?'selected':'')+'>sales</option></select></div>'+
    '<div class="row"><label>Status</label><select id="fa"><option value="1">Active</option><option value="0" '+(k.id&&!+k.is_active?'selected':'')+'>Disabled</option></select></div>'+
    '<div style="display:flex;gap:10px;justify-content:flex-end"><button class="btn ghost" onclick="closeDlg()">Cancel</button><button class="btn" onclick="kSave('+(k.id||0)+')">Save</button></div>');
};
window.kSave=async function(id){await api('kitchen_save',{id:id,name:fn.value,type:ft.value,is_active:fa.value});closeDlg();toast('Saved');nav('kitchens')};

// ---------- Vendors ----------
async function vendors(){
  M.innerHTML='<h1>Vendors</h1><div class="bar"><button class="btn" onclick="vForm(null)">+ New Vendor</button></div><div id="tbl"></div>';
  var j=await api('vendors_list');CACHE.v=j.rows;
  var h='<table><tr><th>Name</th><th>Phone</th><th>Status</th><th></th></tr>';
  j.rows.forEach(function(v,i){h+='<tr><td>'+esc(v.name)+'</td><td>'+esc(v.phone||'—')+'</td><td>'+(+v.is_active?'<span class="pill p-delivered">Active</span>':'<span class="pill p-cancelled">Disabled</span>')+'</td><td style="text-align:right"><button class="btn ghost" onclick="vForm('+i+')">Edit</button></td></tr>'});
  document.getElementById('tbl').innerHTML=h+'</table>';
}
window.vForm=function(i){
  var v=i==null?{}:CACHE.v[i];
  openDlg('<h3>'+(v.id?'Edit':'New')+' Vendor</h3>'+
    '<div class="row"><label>Name</label><input id="fn" value="'+esc(v.name||'')+'"></div>'+
    '<div class="row"><label>Phone</label><input id="fp2" value="'+esc(v.phone||'')+'"></div>'+
    '<div class="row"><label>Status</label><select id="fa"><option value="1">Active</option><option value="0" '+(v.id&&!+v.is_active?'selected':'')+'>Disabled</option></select></div>'+
    '<div style="display:flex;gap:10px;justify-content:flex-end"><button class="btn ghost" onclick="closeDlg()">Cancel</button><button class="btn" onclick="vSave('+(v.id||0)+')">Save</button></div>');
};
window.vSave=async function(id){await api('vendor_save',{id:id,name:fn.value,phone:fp2.value,is_active:fa.value});closeDlg();toast('Saved');nav('vendors')};

// ---------- Item Master ----------
async function items(){
  M.innerHTML='<h1>Item Master</h1><div class="bar"><button class="btn" onclick="iForm(null)">+ New Item</button></div><div id="tbl"></div>';
  var j=await api('items_list');CACHE.i=j.rows;
  var h='<table><tr><th>Item</th><th>Unit</th><th>Status</th><th></th></tr>';
  j.rows.forEach(function(it,i){h+='<tr><td>'+esc(it.name)+'</td><td>'+esc(it.unit)+'</td><td>'+(+it.is_active?'<span class="pill p-delivered">Active</span>':'<span class="pill p-cancelled">Disabled</span>')+'</td><td style="text-align:right"><button class="btn ghost" onclick="iForm('+i+')">Edit</button> <button class="btn red" onclick="iDel('+it.id+')">Delete</button></td></tr>'});
  document.getElementById('tbl').innerHTML=h+'</table>';
}
window.iForm=function(i){
  var it=i==null?{}:CACHE.i[i];
  openDlg('<h3>'+(it.id?'Edit':'New')+' Item</h3>'+
    '<div class="row"><label>Name</label><input id="fn" value="'+esc(it.name||'')+'"></div>'+
    '<div class="row"><label>Unit (kg / litre / dozen…)</label><input id="fu2" value="'+esc(it.unit||'')+'"></div>'+
    '<div class="row"><label>Status</label><select id="fa"><option value="1">Active</option><option value="0" '+(it.id&&!+it.is_active?'selected':'')+'>Disabled</option></select></div>'+
    '<div style="display:flex;gap:10px;justify-content:flex-end"><button class="btn ghost" onclick="closeDlg()">Cancel</button><button class="btn" onclick="iSave('+(it.id||0)+')">Save</button></div>');
};
window.iSave=async function(id){await api('item_save',{id:id,name:fn.value,unit:fu2.value,is_active:fa.value});closeDlg();toast('Saved');nav('items')};
window.iDel=async function(id){
  if(!confirm('Delete this item? (If it has purchase/stock history it will be disabled instead.)'))return;
  var j=await api('item_delete',{id:id});toast(j.msg||'Deleted');nav('items');
};

// ---------- Menu ----------
async function menu(){
  M.innerHTML='<h1>Menu Items</h1><div class="bar"><button class="btn" onclick="mForm(null)">+ New Menu Item</button>'+
    '<span style="color:#8A93A6;font-size:13px">📷 sets the dish photo customers see &mdash; any picture, resized automatically</span></div><div id="tbl"></div>';
  var j=await api('menu_list');CACHE.m=j.rows;
  var ph={};try{ph=(await api('dish_photo_list')).photos||{}}catch(e){}
  CACHE.ph=ph;
  var h='<table><tr><th>Photo</th><th>Item</th><th>Category</th><th>Price</th><th>Type</th><th>Status</th><th></th></tr>';
  var GB={standard:'',special:'<span class="pill p-pending">⭐ Special</span>',executive:'<span class="pill p-delivered">👑 Executive</span>'};
  j.rows.forEach(function(m,i){
    var p=ph[String(m.id)];
    h+='<tr><td>'+(p?'<img src="'+p.url+'?v='+Date.now()+'" alt="" style="width:44px;height:55px;object-fit:cover;border-radius:8px;display:block">'
      :'<div style="width:44px;height:55px;border-radius:8px;border:1px dashed rgba(255,255,255,.2);display:grid;place-items:center;font-size:20px">'+(m.emoji||'🍽')+'</div>')+'</td>'+
      '<td>'+(m.emoji||'')+' '+esc(m.name)+(m.name_urdu?' <span class="urdu" style="color:#8A93A6">'+esc(m.name_urdu)+'</span>':'')+
      (p?'<div style="font-size:11px;color:#3DD68C">photo '+p.kb+' KB</div>':'')+'</td>'+
      '<td>'+esc(m.category)+'</td><td>'+fmt(m.price)+'</td><td>'+(GB[m.menu_group||'standard']||'Standard')+'</td>'+
      '<td>'+(+m.is_active?'<span class="pill p-delivered">Active</span>':'<span class="pill p-cancelled">Disabled</span>')+'</td>'+
      '<td style="text-align:right;white-space:nowrap"><button class="btn ghost" onclick="dishPhoto('+i+')">📷</button> '+
      '<button class="btn ghost" onclick="mForm('+i+')">Edit</button> <button class="btn red" onclick="mDel('+m.id+')">Delete</button></td></tr>';
  });
  document.getElementById('tbl').innerHTML=h+'</table>';
}

// ---------- Dish photo ----------
// Any picture is cover-cropped to 4:5 and re-encoded to a 512×640 WebP in the
// browser, so a 4 MB camera shot arrives around 30 KB and the spec is enforced
// here rather than by whoever prepares the files.
window.DISHPHOTO='';
window.dishPhoto=function(i){
  var m=CACHE.m[i],p=(CACHE.ph||{})[String(m.id)];
  DISHPHOTO='';
  openDlg('<h3>Photo — '+esc(m.name)+'</h3>'+
    '<p style="color:#8A93A6;margin:-6px 0 14px;font-size:13px">Shown to customers on the menu. '+
    'Pick any photo; it is resized to 512×640 WebP automatically. No photo means the '+(m.emoji||'🍽')+' emoji is used.</p>'+
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
    closeDlg();toast('Photo saved — '+j.kb+' KB');sfx.whoosh();nav('menu');
  }catch(e){alert('Upload failed: '+(e&&e.message?e.message:e))}
};
window.dishPhotoDel=async function(id){
  if(!confirm('Remove this photo? The menu will show the emoji again.'))return;
  await api('dish_photo_delete',{menu_item_id:id});
  closeDlg();toast('Photo removed');nav('menu');
};
window.mForm=function(i){
  var m=i==null?{}:CACHE.m[i];
  openDlg('<h3>'+(m.id?'Edit':'New')+' Menu Item</h3>'+
    '<div class="row"><label>Name</label><input id="fn" value="'+esc(m.name||'')+'"></div>'+
    '<div class="row"><label>Name (Urdu)</label><input id="fnu" class="urdu" dir="rtl" value="'+esc(m.name_urdu||'')+'"></div>'+
    '<div class="row"><label>Category</label><input id="fc" value="'+esc(m.category||'')+'"></div>'+
    '<div class="row"><label>Price (PKR)</label><input id="fpr" type="number" step="0.01" value="'+(m.price||'')+'"></div>'+
    '<div class="row"><label>Emoji</label><input id="fe" value="'+esc(m.emoji||'')+'"></div>'+
    '<div class="row"><label>Menu Type</label><select id="fg">'+[['standard','Standard — visible to everyone'],['special','Special — only "Special" customers'],['executive','Executive — only "Executive" customers']].map(function(g){return '<option value="'+g[0]+'" '+((m.menu_group||'standard')===g[0]?'selected':'')+'>'+g[1]+'</option>'}).join('')+'</select></div>'+
    '<div class="row"><label>Status</label><select id="fa"><option value="1">Active</option><option value="0" '+(m.id&&!+m.is_active?'selected':'')+'>Disabled</option></select></div>'+
    '<div style="display:flex;gap:10px;justify-content:flex-end"><button class="btn ghost" onclick="closeDlg()">Cancel</button><button class="btn" onclick="mSave('+(m.id||0)+')">Save</button></div>');
};
window.mSave=async function(id){await api('menu_save',{id:id,name:fn.value,name_urdu:fnu.value,category:fc.value,price:fpr.value,emoji:fe.value,is_active:fa.value,menu_group:fg.value});closeDlg();toast('Saved');nav('menu')};
window.mDel=async function(id){
  if(!confirm('Delete this menu item? (If it appears in past orders it will be disabled instead.)'))return;
  var j=await api('menu_delete',{id:id});toast(j.msg||'Deleted');nav('menu');
};

// ---------- Orders ----------
async function orders(){
  M.innerHTML='<h1>Orders</h1><div id="tbl"></div>';
  var j=await api('orders_list');
  var h='<table><tr><th>#</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Placed</th><th></th></tr>';
  j.rows.forEach(function(o){
    var its=(o.items||[]).map(function(i){return i.qty+'× '+esc(i.name_snapshot)+(+i.is_comp?' (FREE)':'')}).join(', ');
    var act='';
    if(o.status==='pending'||o.status==='preparing')act='<button class="btn red" onclick="oCancel('+o.id+')">Cancel</button>';
    if(o.status==='delivered')act='<button class="btn ghost" onclick="oReverse('+o.id+')">Reverse</button>';
    h+='<tr><td>'+esc(o.token)+'</td><td>'+esc(o.customer)+'</td><td style="max-width:280px">'+its+'</td><td>'+fmt(o.total)+'</td>'+
      '<td><span class="pill p-'+o.status+'">'+o.status+'</span>'+(o.cancel_reason?'<div style="font-size:12px;color:#8A93A6">'+esc(o.cancel_reason)+'</div>':'')+'</td>'+
      '<td style="font-size:12px;color:#8A93A6">'+o.placed_at+'</td><td style="text-align:right">'+act+'</td></tr>';
  });
  document.getElementById('tbl').innerHTML=h+'</table>';
}
window.oCancel=function(id){
  openDlg('<h3>Cancel Order</h3><div class="row"><label>Reason (required — wallet refunded, stock restored)</label><textarea id="frs" rows="3" style="width:100%"></textarea></div>'+
    '<div style="display:flex;gap:10px;justify-content:flex-end"><button class="btn ghost" onclick="closeDlg()">Back</button><button class="btn red" onclick="oCancelGo('+id+')">Cancel Order</button></div>');
};
window.oCancelGo=async function(id){await api('order_cancel',{id:id,reason:frs.value});closeDlg();toast('Cancelled + refunded');nav('orders')};
window.oReverse=async function(id){if(!confirm('Reverse this delivered order? Wallet refunded, stock NOT restored.'))return;await api('order_reverse',{id:id});toast('Reversed + refunded');nav('orders')};

// ---------- Inventory ----------
async function inventory(){
  M.innerHTML='<h1>Inventory (Chef Kitchens)</h1><div id="tbl"></div>';
  var j=await api('inventory_list');CACHE.inv=j.rows;
  var h='<table><tr><th>Kitchen</th><th>Item</th><th>Qty</th><th></th></tr>';
  j.rows.forEach(function(r,i){h+='<tr><td>'+esc(r.kitchen)+'</td><td>'+esc(r.name)+'</td><td>'+(+r.qty).toFixed(2)+' '+esc(r.unit)+'</td><td style="text-align:right"><button class="btn ghost" onclick="invAdj('+i+')">Adjust</button></td></tr>'});
  document.getElementById('tbl').innerHTML=h+'</table>';
}
window.invAdj=function(i){
  var r=CACHE.inv[i];
  openDlg('<h3>Adjust: '+esc(r.name)+'</h3><div class="row"><label>New Quantity ('+esc(r.unit)+')</label><input id="fq" type="number" step="0.001" value="'+r.qty+'"></div>'+
    '<div style="display:flex;gap:10px;justify-content:flex-end"><button class="btn ghost" onclick="closeDlg()">Cancel</button><button class="btn" onclick="invSave('+r.item_id+')">Save</button></div>');
};
window.invSave=async function(iid){await api('inventory_adjust',{item_id:iid,qty:fq.value});closeDlg();toast('Adjusted');nav('inventory')};

// ---------- Sales Stock ----------
async function salesstock(){
  M.innerHTML='<h1>Sales Stock (Today)</h1><div class="bar"><button class="btn" onclick="allocForm()">+ Allocate</button>'+
    '<span style="color:#8A93A6;font-size:13px">Tap − / + to change by 1 · use Adjust for a bigger change</span></div><div id="tbl"></div>';
  var j=await api('salesstock_list');CACHE.ss=j.rows;STEP={};
  var h='<table><tr><th>Kitchen</th><th>Menu Item</th><th>Remaining Qty</th><th style="text-align:right">Adjust Stock</th></tr>';
  j.rows.forEach(function(r,i){
    h+='<tr><td>'+esc(r.kitchen)+'</td><td>'+(r.emoji||'')+' '+esc(r.name)+'</td>'+
       '<td><span class="qv" id="q'+i+'"></span></td>'+
       '<td style="text-align:right;white-space:nowrap">'+
         '<button class="btn step minus" id="m'+i+'" onclick="stepQty('+i+',-1)" title="Reduce by 1">−</button> '+
         '<button class="btn step" onclick="stepQty('+i+',1)" title="Add 1">+</button> '+
         '<button class="btn ghost" onclick="adjustForm('+i+')">Adjust</button>'+
       '</td></tr>';
  });
  document.getElementById('tbl').innerHTML=h+'</table>';
  j.rows.forEach(function(r,i){paintQty(i)});
}

// Inline − / + steppers. Taps are applied to the screen straight away and the
// accumulated change is sent as ONE request once tapping stops, so holding −
// five times is a single -5 (one audit row) instead of five separate calls.
var STEP={};
function paintQty(i){
  var el=document.getElementById('q'+i); if(!el)return;
  var r=CACHE.ss[i], q=+r.qty, s=STEP[i];
  el.className='qv'+(q<=5?' low':'')+(s&&s.delta?' busy':'');
  el.textContent=q+(q<=5?' ⚠':'');
  var m=document.getElementById('m'+i); if(m)m.disabled=(q<=0);
}
window.stepQty=function(i,d){
  var r=CACHE.ss[i], next=(+r.qty)+d;
  if(next<0){toast('Already at zero',true);return}
  r.qty=next;                                  // show it immediately
  var s=STEP[i]||(STEP[i]={delta:0,timer:null});
  s.delta+=d;
  paintQty(i);
  sfx.tick();
  clearTimeout(s.timer);
  s.timer=setTimeout(function(){flushStep(i)},700);
};
async function flushStep(i){
  var s=STEP[i]; if(!s||!s.delta)return;
  var d=s.delta, r=CACHE.ss[i];
  s.delta=0;
  try{
    await api('allocate',{kitchen_id:r.kitchen_id,menu_item_id:r.menu_item_id,qty:d});
    paintQty(i);
    toast(esc(r.name)+' '+(d>0?'+':'')+d+' → '+r.qty);
    sfx.whoosh();
  }catch(e){
    toast('Could not save — reloading stock',true);
    nav('salesstock');                         // server is the truth; resync
  }
}
window.adjustForm=function(i){
  var r=CACHE.ss[i];
  openDlg('<h3>Adjust Stock</h3><p style="color:#8A93A6;margin-top:0">'+(r.emoji||'')+' <b>'+esc(r.name)+'</b> — '+esc(r.kitchen)+' · current <b>'+r.qty+'</b></p>'+
    '<div class="row"><label>Change quantity (use − to reduce, + to add)</label><input id="aq" type="number" placeholder="e.g. -5 or 10"></div>'+
    '<div style="display:flex;gap:10px;justify-content:flex-end"><button class="btn ghost" onclick="closeDlg()">Cancel</button><button class="btn" onclick="adjustGo('+r.kitchen_id+','+r.menu_item_id+')">Apply</button></div>');
};
window.adjustGo=async function(kid,mid){
  var q=parseInt(document.getElementById('aq').value,10);
  if(!q){alert('Enter a non-zero change, e.g. -5 to reduce or 10 to add');return}
  try{await api('allocate',{kitchen_id:kid,menu_item_id:mid,qty:q});closeDlg();toast(q<0?'Stock reduced':'Stock added');sfx.whoosh();nav('salesstock')}
  catch(e){alert('Adjust failed: '+(e&&e.message?e.message:e))}
};
window.allocForm=async function(){
  var ms=(await api('menu_list')).rows.filter(function(m){return+m.is_active});
  var ks=(await api('kitchens_list')).rows.filter(function(k){return k.type==='sales'&&+k.is_active});
  openDlg('<h3>Allocate to Sales Kitchen</h3>'+
    '<div class="row"><label>Sales Kitchen</label><select id="fk2">'+ks.map(function(k){return '<option value="'+k.id+'">'+esc(k.name)+'</option>'}).join('')+'</select></div>'+
    '<div class="row"><label>Menu Item</label><select id="fm">'+ms.map(function(m){return '<option value="'+m.id+'">'+esc(m.name)+'</option>'}).join('')+'</select></div>'+
    '<div class="row"><label>Quantity</label><input id="fq" type="number" min="1"></div>'+
    '<div style="display:flex;gap:10px;justify-content:flex-end"><button class="btn ghost" onclick="closeDlg()">Cancel</button><button class="btn" onclick="allocGo()">Allocate</button></div>');
};
window.allocGo=async function(){await api('allocate',{kitchen_id:fk2.value,menu_item_id:fm.value,qty:fq.value});closeDlg();toast('Allocated');sfx.whoosh();nav('salesstock')};

// ---------- Wallets ----------
async function wallets(){
  M.innerHTML='<h1>Wallets</h1><div id="tbl"></div>';
  var j=await api('wallets_list');CACHE.w=j.rows;
  var h='<table><tr><th>Customer</th><th>Username</th><th>Balance</th><th></th></tr>';
  j.rows.forEach(function(w,i){h+='<tr><td>'+esc(w.name)+'</td><td>'+esc(w.username)+'</td><td style="font-weight:700">'+fmt(w.balance)+'</td><td style="text-align:right"><button class="btn ghost" onclick="wAdj('+i+',1)">Top-Up</button> <button class="btn red" onclick="wAdj('+i+',-1)">Deduct</button></td></tr>'});
  document.getElementById('tbl').innerHTML=h+'</table>';
}
window.wAdj=function(i,sign){
  var w=CACHE.w[i];
  openDlg('<h3>'+(sign>0?'Top-Up':'Deduct')+': '+esc(w.name)+'</h3>'+
    '<div class="row"><label>Amount (PKR)</label><input id="fq" type="number" min="1"></div>'+
    '<div class="row"><label>Remarks</label><input id="frm"></div>'+
    '<div style="display:flex;gap:10px;justify-content:flex-end"><button class="btn ghost" onclick="closeDlg()">Cancel</button><button class="btn" onclick="wGo('+w.id+','+sign+')">Save</button></div>');
};
window.wGo=async function(id,sign){await api('topup',{customer_id:id,amount:sign*Math.abs(+fq.value),remarks:frm.value});closeDlg();toast('Wallet updated');sfx.chime();nav('wallets')};

// ---------- Complimentary ----------
async function comp(){
  M.innerHTML='<h1>Complimentary Items</h1><div class="bar"><button class="btn" onclick="compForm(null)">+ Assign Free Item</button></div><div id="tbl"></div>';
  var j=await api('comp_list');CACHE.comp=(j.rows||[]).filter(function(r){return r.state!=='removed'});
  if(!CACHE.comp.length){document.getElementById('tbl').innerHTML='<p style="color:#8A93A6">No complimentary allocations yet. Assign a free menu item to a customer to get started.</p>';return}
  var pill={active:'p-delivered','used up':'p-pending',expired:'p-cancelled',removed:'p-cancelled'};
  function schedTxt(r){
    var D={'1':'Mon','2':'Tue','3':'Wed','4':'Thu','5':'Fri','6':'Sat','7':'Sun'},t=[];
    if(r.days_of_week)t.push(r.days_of_week.split(',').map(function(v){return D[v]}).join(' '));
    if(r.time_from)t.push(r.time_from.slice(0,5)+'–'+r.time_to.slice(0,5));
    return t.join(' · ')||'Any time';
  }
  var h='<table><tr><th>Customer</th><th>Item</th><th>Used / Limit</th><th>Schedule</th><th>Expiry</th><th>Assigned By</th><th>Status</th><th></th></tr>';
  j.rows.forEach(function(r,i){
    if(r.state==='removed')return;    h+='<tr><td>'+esc(r.customer)+'</td><td>'+(r.emoji||'')+' '+esc(r.item)+'</td>'+
      '<td>'+r.qty_used+' / '+r.qty_allowed+(+r.repeat_daily?'<div style="font-size:11px;color:#3DD68C">resets daily</div>':'')+'</td>'+
      '<td style="font-size:12px;color:#8A93A6">'+schedTxt(r)+'</td><td>'+(r.expiry_date||'—')+'</td>'+
      '<td>'+esc(r.assigned_by)+'</td>'+
      '<td><span class="pill '+(pill[r.state]||'p-pending')+'">'+r.state+'</span></td>'+
      '<td style="text-align:right;white-space:nowrap">'+(r.state==='active'||r.state==='used up'?
        '<button class="btn ghost" onclick="compForm('+i+')">Edit</button> <button class="btn red" onclick="compDel('+r.id+')">Remove</button>':'')+'</td></tr>';
  });
  document.getElementById('tbl').innerHTML=h+'</table>';
}
window.compForm=async function(i){
  var r=i==null?{}:CACHE.comp[i];
  var today=new Date().toISOString().slice(0,10);
  var DAYS=['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
  var sel=(r.days_of_week||'').split(',');
  var sched='<div class="row"><label>Days (none = every day)</label><div style="display:flex;gap:6px;flex-wrap:wrap">'+
      DAYS.map(function(d,ix){var v=ix+1;return '<button type="button" class="btn ghost dayb'+(sel.indexOf(String(v))>=0?' on':'')+'" data-v="'+v+'" onclick="this.classList.toggle(\'on\')" style="padding:6px 11px">'+d+'</button>'}).join('')+'</div></div>'+
    '<div class="row"><label>Time window (optional — offer shows only in this window)</label><div style="display:flex;gap:8px;align-items:center"><input id="ctf" type="time" value="'+(r.time_from?r.time_from.slice(0,5):'')+'"><span style="color:#8A93A6">to</span><input id="ctt" type="time" value="'+(r.time_to?r.time_to.slice(0,5):'')+'"></div></div>'+
    '<div class="row"><label>Limit type</label><select id="crd"><option value="0">Total — one-time allowance</option><option value="1" '+(+(r.repeat_daily||0)?'selected':'')+'>Daily — limit resets every day</option></select></div>'+
    '<div class="row"><label>Expiry Date (blank = no expiry)</label><input id="ce" type="date" min="'+today+'" value="'+(r.expiry_date||'')+'"></div>';
  if(r.id){
    openDlg('<h3>Edit: '+esc(r.item)+' → '+esc(r.customer)+'</h3>'+
      '<div class="row"><label>Quantity Limit (used: '+r.qty_used+')</label><input id="cq" type="number" min="1" max="100" value="'+r.qty_allowed+'"></div>'+sched+
      '<div style="display:flex;gap:10px;justify-content:flex-end"><button class="btn ghost" onclick="closeDlg()">Cancel</button><button class="btn" onclick="compSave('+r.id+')">Save</button></div>');
    return;
  }
  var cs=(await api('wallets_list')).rows;
  var ms=(await api('menu_list')).rows.filter(function(m){return+m.is_active});
  function pickList(id,rows,lbl){
    return '<div class="row"><label>'+lbl+' <span style="color:#FFB020;font-size:11px">(tap to select several)</span></label>'+
      '<input placeholder="Search…" style="margin-bottom:6px" oninput="pickFilter(\''+id+'\',this.value)">'+
      '<div id="'+id+'" style="max-height:150px;overflow:auto;border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:4px">'+
      rows.map(function(r){return '<div class="pk" data-v="'+r.id+'" data-n="'+esc((r.name+' '+(r.username||'')).toLowerCase())+'" onclick="this.classList.toggle(\'on\')" style="padding:8px 10px;border-radius:8px;cursor:pointer">'+r.label+'</div>'}).join('')+'</div></div>';
  }
  openDlg('<style>.pk.on{background:rgba(255,176,32,.18);box-shadow:inset 0 0 0 1px #FFB020}.pk:hover{background:rgba(255,255,255,.05)}</style>'+
    '<h3>Assign Complimentary Item(s)</h3>'+
    pickList('pkc',cs.map(function(c){return{id:c.id,name:c.name,username:c.username,label:esc(c.name)+' <span style="color:#8A93A6;font-size:12px">('+esc(c.username)+')</span>'}}),'Customers')+
    pickList('pkm',ms.map(function(m){return{id:m.id,name:m.name,label:(m.emoji||'')+' '+esc(m.name)+' <span style="color:#8A93A6;font-size:12px">'+fmt(m.price)+'</span>'}}),'Menu Items')+
    '<div class="row"><label>Quantity Limit (free units, each)</label><input id="cq" type="number" min="1" max="100" value="1"></div>'+sched+
    '<div style="display:flex;gap:10px;justify-content:flex-end"><button class="btn ghost" onclick="closeDlg()">Cancel</button><button class="btn" onclick="compSave(0)">Assign</button></div>');
};
window.pickFilter=function(id,q){q=q.toLowerCase();document.querySelectorAll('#'+id+' .pk').forEach(function(el){el.style.display=el.dataset.n.indexOf(q)>=0?'':'none'})};
window.compSave=async function(id){
  try{
    var days=Array.prototype.slice.call(document.querySelectorAll('.dayb.on')).map(function(b){return b.dataset.v}).join(',');
    var p={id:id,qty_allowed:cq.value,expiry_date:ce.value,days:days,time_from:ctf.value,time_to:ctt.value,repeat_daily:crd.value==='1'?1:0};
    if(!id){
      p.customer_ids=Array.prototype.slice.call(document.querySelectorAll('#pkc .pk.on')).map(function(el){return+el.dataset.v});
      p.menu_item_ids=Array.prototype.slice.call(document.querySelectorAll('#pkm .pk.on')).map(function(el){return+el.dataset.v});
      if(!p.customer_ids.length||!p.menu_item_ids.length){alert('Select at least one customer and one menu item');return}
    }
    var j=await api('comp_save',p);closeDlg();
    toast(id?'Saved':('Created '+j.created+(j.skipped?' · skipped '+j.skipped+' duplicate(s)':'')));
    sfx.whoosh();nav('comp');
  }catch(e){alert('Assign failed: '+(e&&e.message?e.message:e))}
};
window.compDel=async function(id){
  if(!confirm('Remove this complimentary allocation? Usage history stays in the list and audit log.'))return;
  await api('comp_remove',{id:id});toast('Removed');nav('comp');
};

// ---------- Reports ----------
// Rendered by report.js, shared with the Accounts app so both roles see the same figures.
function reports(){ Report.render(M); }

// ---------- Audit ----------
async function auditLog(){
  M.innerHTML='<h1>Audit Log</h1><div id="tbl"></div>';
  var j=await api('audit_list');
  var h='<table><tr><th>When</th><th>User</th><th>Action</th><th>Entity</th><th>Details</th></tr>';
  j.rows.forEach(function(a){h+='<tr><td style="white-space:nowrap;font-size:12px;color:#8A93A6">'+a.created_at+'</td><td>'+esc(a.user)+'</td><td><b>'+esc(a.action)+'</b></td><td>'+esc(a.entity)+(a.entity_id?' #'+a.entity_id:'')+'</td><td style="font-size:12px;color:#8A93A6;max-width:340px;overflow:hidden;text-overflow:ellipsis">'+esc(a.after_json||'')+'</td></tr>'});
  document.getElementById('tbl').innerHTML=h+'</table>';
}

// ---------- My Account ----------
async function account(){
  M.innerHTML='<h1>My Account</h1><div class="tile" style="max-width:420px">'+
    '<div class="row" style="display:grid;gap:6px;margin-bottom:12px"><label style="font-size:12px;color:#8A93A6">NAME</label><input id="an" value="'+esc(ME.name)+'"></div>'+
    '<div class="row" style="display:grid;gap:6px;margin-bottom:12px"><label style="font-size:12px;color:#8A93A6">USERNAME</label><input id="au" value="'+esc(ME.username||'')+'"></div>'+
    '<div class="row" style="display:grid;gap:6px;margin-bottom:12px"><label style="font-size:12px;color:#8A93A6">NEW PASSWORD (blank = keep)</label><input id="ap" type="password"></div>'+
    '<button class="btn" onclick="accSave()">Save</button></div>';
}
window.accSave=async function(){
  var payload={name:an.value};
  if(au.value)payload.username=au.value;
  if(ap.value)payload.new_password=ap.value;
  await api('my_account',payload);toast('Account updated');
};

boot().then(function(){nav('dash')});
</script>
</body>
</html>
