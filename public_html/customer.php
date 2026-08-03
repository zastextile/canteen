<?php
require_once __DIR__ . '/lib.php';
$me = page_guard('customer');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>ZAS Canteen</title>
<meta name="theme-color" content="#06080F">
<link rel="manifest" href="manifest.json">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Noto+Naskh+Arabic:wght@400;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0}
body{background:#06080F;color:#EDF1F8;font-family:'Space Grotesk',sans-serif;min-height:100vh;padding-bottom:86px;overflow-x:hidden}
.urdu{font-family:'Noto Naskh Arabic',serif}
canvas#stars{position:fixed;inset:0;z-index:0;pointer-events:none}
#view{position:relative;z-index:1}
header{padding:18px 20px 4px;display:flex;justify-content:space-between;align-items:center;position:relative;z-index:1}
h1{font-size:20px}
.wallet{margin:14px 16px;padding:22px;border-radius:24px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);backdrop-filter:blur(18px);box-shadow:0 12px 40px rgba(0,0,0,.5);position:relative;overflow:hidden}
.wallet::before{content:'';position:absolute;top:-60%;right:-30%;width:70%;height:200%;background:conic-gradient(from 0deg,transparent,rgba(255,176,32,.18),transparent);animation:spin 7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.wallet .k{font-size:12px;letter-spacing:2px;color:#8A93A6}
.wallet .v{font-size:36px;font-weight:700;color:#FFB020;margin-top:4px}
.cats{display:flex;gap:8px;overflow-x:auto;padding:8px 16px}
.cats button{flex:none;padding:9px 18px;border-radius:99px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);color:#8A93A6;font-family:inherit;font-size:14px;cursor:pointer}
.cats button.on{background:rgba(255,176,32,.15);border-color:#FFB020;color:#FFB020}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:10px 16px}
.mi{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);border-radius:20px;padding:14px;transition:transform .15s;will-change:transform}
.mi .em{font-size:38px}
.mi .nm{font-weight:700;margin-top:6px;font-size:15px}
.mi .ur{color:#8A93A6;font-size:15px}
.mi .pr{color:#FFB020;font-weight:700;margin-top:4px}
.mi .low{color:#FF5C5C;font-size:12px;font-weight:700;margin-top:2px}
.mi button{width:100%;margin-top:10px;padding:11px;border:none;border-radius:12px;background:rgba(255,176,32,.15);color:#FFB020;font-weight:700;font-family:inherit;font-size:14px;cursor:pointer;min-height:44px;transition:transform .12s,background .15s}
.mi button:active{transform:scale(.95)}
.mi.free{border-color:rgba(61,214,140,.45);background:rgba(61,214,140,.06)}
.mi.free button{background:rgba(61,214,140,.16);color:#3DD68C}
.freetag{display:inline-block;background:rgba(61,214,140,.18);color:#3DD68C;border:1px solid rgba(61,214,140,.4);font-size:11px;font-weight:700;letter-spacing:1px;padding:2px 10px;border-radius:99px;margin-top:4px}
.sect{font-size:13px;letter-spacing:2px;color:#8A93A6;text-transform:uppercase;padding:14px 20px 0}
.gift{margin:12px 16px 0;padding:14px 18px;border-radius:18px;background:rgba(61,214,140,.08);border:1px solid rgba(61,214,140,.3);display:flex;align-items:center;gap:12px;font-size:14px}
.mi.out{opacity:.45}
.mi.out button{background:rgba(255,255,255,.06);color:#8A93A6;cursor:not-allowed}
.btn{width:calc(100% - 32px);margin:12px 16px 0;padding:16px;border:none;border-radius:14px;background:linear-gradient(135deg,#FFB020,#F07818);color:#1A1205;font-weight:700;font-size:16px;font-family:inherit;cursor:pointer;position:relative;overflow:hidden}
.btn::after{content:'';position:absolute;top:0;left:-60%;width:40%;height:100%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.45),transparent);animation:sw 3s infinite}
@keyframes sw{to{left:130%}}
.btn:disabled{filter:grayscale(1);opacity:.5}
.btn:active{transform:scale(.98)}
#view{animation:fadein .25s ease}
@keyframes fadein{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
@media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}
.card{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);border-radius:20px;padding:16px 18px;margin:10px 16px}
.line{display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid rgba(255,255,255,.06)}
.line:last-child{border-bottom:none}
.stepper{display:flex;align-items:center;gap:10px}
.stepper button{width:36px;height:36px;border-radius:10px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.07);color:#EDF1F8;font-size:18px;cursor:pointer}
.pill{padding:3px 12px;border-radius:99px;font-size:12px;font-weight:700}
.p-pending{background:rgba(255,176,32,.15);color:#FFB020}.p-preparing{background:rgba(79,163,255,.15);color:#4FA3FF}
.p-delivered{background:rgba(61,214,140,.15);color:#3DD68C}.p-cancelled,.p-reversed{background:rgba(255,92,92,.15);color:#FF8080}
nav{position:fixed;bottom:0;left:0;right:0;display:flex;background:rgba(11,15,26,.92);backdrop-filter:blur(16px);border-top:1px solid rgba(255,255,255,.08);padding:8px 0 max(8px, env(safe-area-inset-bottom));z-index:10}
nav a{flex:1;text-align:center;color:#8A93A6;text-decoration:none;font-size:11px;padding:6px 0;cursor:pointer;position:relative}
nav a.on{color:#FFB020}
nav a span{display:block;font-size:22px;margin-bottom:2px}
.badge{position:absolute;top:0;right:22%;background:#FF5C5C;color:#fff;font-size:11px;font-weight:700;border-radius:99px;padding:1px 7px}
.skel{height:110px;border-radius:20px;background:linear-gradient(100deg,rgba(255,255,255,.04) 40%,rgba(255,255,255,.09) 50%,rgba(255,255,255,.04) 60%);background-size:200% 100%;animation:sh 1.2s infinite}
@keyframes sh{to{background-position:-200% 0}}
.empty{text-align:center;color:#8A93A6;padding:50px 30px;position:relative;z-index:1}
</style>
</head>
<body>
<canvas id="stars"></canvas>
<header><h1>🍛 ZAS Canteen</h1><a onclick="logout()" style="color:#FF8080;cursor:pointer;font-size:14px">Logout</a></header>
<div id="view"></div>
<nav>
  <a data-t="home" class="on"><span>🏠</span>Home</a>
  <a data-t="menu"><span>🍛</span>Order Food</a>
  <a data-t="cart"><span>🛒</span>Cart<b class="badge" id="cb" style="display:none"></b></a>
  <a data-t="orders"><span>🧾</span>My Orders</a>
</nav>
<script src="app.js"></script>
<script>
var V=document.getElementById('view'),MENU=[],COMPS=[],CART={},CARTC={},BAL=0,CAT='All';
function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
document.querySelectorAll('nav a').forEach(function(a){a.onclick=function(){sfx.tick();
  document.querySelectorAll('nav a').forEach(function(x){x.classList.toggle('on',x===a)});
  ({home:home,menu:menuTab,cart:cart,orders:orders})[a.dataset.t]()}});
function cartCount(){var n=0;Object.keys(CART).forEach(function(k){n+=CART[k]});Object.keys(CARTC).forEach(function(k){n+=CARTC[k]});return n}
function updateBadge(){var b=document.getElementById('cb'),n=cartCount();b.style.display=n?'block':'none';b.textContent=n}

// starfield
(function(){var c=document.getElementById('stars'),x=c.getContext('2d'),W,H,S=[];
function rs(){W=c.width=innerWidth;H=c.height=innerHeight}rs();addEventListener('resize',rs);
for(var i=0;i<110;i++)S.push({x:Math.random(),y:Math.random(),r:Math.random()*1.3+.3,p:Math.random()*6.28,s:.4+Math.random()*.8});
(function t(n){x.clearRect(0,0,W,H);
var g=x.createRadialGradient(W*.75,H*.2,0,W*.75,H*.2,W*.7);
g.addColorStop(0,'rgba(255,176,32,.05)');g.addColorStop(1,'transparent');x.fillStyle=g;x.fillRect(0,0,W,H);
S.forEach(function(s){x.globalAlpha=.25+.65*Math.abs(Math.sin(n*.001*s.s+s.p));x.fillStyle='#EDF1F8';
x.beginPath();x.arc(s.x*W,s.y*H,s.r,0,6.28);x.fill()});x.globalAlpha=1;requestAnimationFrame(t)})(0);
})();

async function loadMenu(){var j=await api('customer_menu');MENU=j.rows;COMPS=j.comps||[];BAL=+j.balance;
  Object.keys(CARTC).forEach(function(id){var c=compFor(id);if(!c)delete CARTC[id];else if(CARTC[id]>+c.remaining)CARTC[id]=+c.remaining});
  updateBadge();return j}
function compFor(id){return COMPS.find(function(c){return +c.menu_item_id===+id})}
// resolve item details: paid items come from the access-filtered MENU; a complimentary item
// allocated OUTSIDE the customer's access tiers isn't in MENU, so fall back to its comp row.
function citem(id){var m=MENU.find(function(x){return+x.id===+id});if(m)return m;var c=compFor(id);return c?{id:+c.menu_item_id,name:c.name,emoji:c.emoji||'🍽',price:+c.price,stock:+c.stock,menu_group:c.menu_group}:null}

async function home(){
  V.innerHTML='<div class="skel" style="margin:14px 16px"></div>';
  await loadMenu();
  var gift=COMPS.length?'<div class="gift">🎁<div><b style="color:#3DD68C">You have '+COMPS.reduce(function(n,c){return n+ +c.remaining},0)+' complimentary item(s)</b><div style="color:#8A93A6;font-size:12px">Free of charge — find them at the top of the menu</div></div></div>':'';
  V.innerHTML='<div class="wallet"><div class="k">WALLET BALANCE</div><div class="v" id="wb"></div></div>'+gift+
    '<button class="btn" onclick="document.querySelector(\'nav a[data-t=menu]\').click()">Order Food →</button>';
  countUp(document.getElementById('wb'),BAL,'PKR ');
}

async function menuTab(){
  V.innerHTML='<div class="grid"><div class="skel"></div><div class="skel"></div><div class="skel"></div><div class="skel"></div></div>';
  await loadMenu();
  var cats=['All'].concat(MENU.map(function(m){return m.category}).filter(function(v,i,a){return a.indexOf(v)===i}));
  var h='';
  if(COMPS.length){
    h+='<div class="sect">🎁 Complimentary for you</div><div class="grid">';
    COMPS.forEach(function(c){
      var m=citem(c.menu_item_id);if(!m)return;
      var out=+m.stock<=0,left=+c.remaining;
      var hints=[];
      if(+c.repeat_daily)hints.push('daily');
      if(c.time_to)hints.push('until '+c.time_to.slice(0,5));
      if(c.expiry_date)hints.push('till '+c.expiry_date);
      h+='<div class="mi free'+(out?' out':'')+'"><div class="em">'+(m.emoji||'🍽')+'</div>'+
        '<div class="nm">'+esc(m.name)+'</div><div class="freetag">FREE · '+left+' left'+(+c.repeat_daily?' today':'')+'</div>'+
        (hints.length?'<div style="font-size:11px;color:#8A93A6;margin-top:3px">'+hints.join(' · ')+'</div>':'')+
        (out?'<div class="low">Out of Stock</div>':'')+
        '<button '+(out?'disabled':'')+' onclick="addComp('+m.id+')">'+(out?'Out of Stock':'+ Add Free')+'</button></div>';
    });
    h+='</div>';
  }
  h+='<div class="cats">'+cats.map(function(c){return '<button class="'+(c===CAT?'on':'')+'" onclick="setCat(\''+esc(c)+'\')">'+esc(c)+'</button>'}).join('')+'</div><div class="grid">';
  MENU.filter(function(m){return CAT==='All'||m.category===CAT}).forEach(function(m){
    var out=+m.stock<=0;
    h+='<div class="mi'+(out?' out':'')+'" data-tilt><div class="em">'+(m.emoji||'🍽')+'</div>'+
      '<div class="nm">'+esc(m.name)+'</div><div class="ur urdu">'+esc(m.name_urdu||'')+'</div>'+
      '<div class="pr">'+fmt(m.price)+'</div>'+
      (m.menu_group==='special'?'<div style="font-size:11px;color:#FFB020;margin-top:2px">⭐ Special</div>':(m.menu_group==='executive'?'<div style="font-size:11px;color:#FFB020;margin-top:2px">👑 Executive</div>':''))+
      (out?'<div class="low">Out of Stock</div>':(+m.stock<=10?'<div class="low">Only '+m.stock+' left</div>':''))+
      '<button '+(out?'disabled':'')+' onclick="add('+m.id+')">'+(out?'Out of Stock':'+ Add')+'</button></div>';
  });
  V.innerHTML=h+'</div>';
  // 3D tilt
  document.querySelectorAll('[data-tilt]').forEach(function(el){
    function mv(e){var r=el.getBoundingClientRect(),p=e.touches?e.touches[0]:e;
      var dx=(p.clientX-r.left)/r.width-.5,dy=(p.clientY-r.top)/r.height-.5;
      el.style.transform='perspective(500px) rotateY('+dx*7+'deg) rotateX('+(-dy*7)+'deg)'}
    function lv(){el.style.transform=''}
    el.addEventListener('mousemove',mv);el.addEventListener('mouseleave',lv);
    el.addEventListener('touchmove',mv,{passive:true});el.addEventListener('touchend',lv);
  });
}
window.setCat=function(c){CAT=c;sfx.tick();menuTab()};
window.add=function(id){
  var m=MENU.find(function(x){return+x.id===id});
  var cur=(CART[id]||0)+(CARTC[id]||0);
  if(cur+1>+m.stock){toast('Only '+m.stock+' available',true);return}
  CART[id]=(CART[id]||0)+1;sfx.tick();updateBadge();toast(esc(m.name)+' added');
};
window.addComp=function(id){
  var c=compFor(id),m=citem(id);
  if(!c||!m){toast('Not available',true);return}
  var cur=CARTC[id]||0;
  if(cur+1>+c.remaining){toast('Only '+c.remaining+' free allowed',true);return}
  if(cur+1+(CART[id]||0)>+m.stock){toast('Only '+m.stock+' in stock',true);return}
  CARTC[id]=cur+1;sfx.tick();updateBadge();toast(esc(m.name)+' added — FREE');
};

async function cart(){
  await loadMenu();
  var ids=Object.keys(CART).filter(function(k){return CART[k]>0});
  var cids=Object.keys(CARTC).filter(function(k){return CARTC[k]>0});
  if(!ids.length&&!cids.length){V.innerHTML='<div class="empty">🛒<br>Cart is empty</div>';return}
  var total=0,h='<div class="card">';
  cids.forEach(function(id){
    var m=citem(+id);if(!m)return;
    h+='<div class="line"><div><b>'+esc(m.name)+'</b> <span class="freetag" style="margin:0">FREE</span></div>'+
      '<div class="stepper"><button onclick="decC('+id+')">−</button><b>'+CARTC[id]+'</b><button onclick="incC('+id+')">+</button></div>'+
      '<div style="min-width:80px;text-align:right;font-weight:700;color:#3DD68C">PKR 0</div></div>';
  });
  ids.forEach(function(id){
    var m=MENU.find(function(x){return+x.id===+id});if(!m)return;
    var line=m.price*CART[id];total+=line;
    h+='<div class="line"><div><b>'+esc(m.name)+'</b><div style="font-size:12px;color:#8A93A6">'+fmt(m.price)+' each</div></div>'+
      '<div class="stepper"><button onclick="dec('+id+')">−</button><b>'+CART[id]+'</b><button onclick="inc('+id+')">+</button></div>'+
      '<div style="min-width:80px;text-align:right;font-weight:700">'+fmt(line)+'</div></div>';
  });
  h+='<div class="line" style="font-size:18px"><b>Total to pay</b><b style="color:#FFB020">'+fmt(total)+'</b></div>'+
     (total>0?'<div class="line"><span style="color:#8A93A6">Wallet</span><span '+(BAL<total?'style="color:#FF5C5C;font-weight:700"':'')+'>'+fmt(BAL)+(BAL<total?' — insufficient':'')+'</span></div>':'')+'</div>';
  h+='<button class="btn" id="cfm" '+(BAL<total?'disabled':'')+' onclick="confirmOrder()">Confirm Order'+(total>0?' — '+fmt(total):' — FREE')+'</button>';
  V.innerHTML=h;
}
window.inc=function(id){add(id);cart()};
window.dec=function(id){CART[id]=Math.max(0,(CART[id]||0)-1);if(!CART[id])delete CART[id];sfx.tick();updateBadge();cart()};
window.incC=function(id){addComp(id);cart()};
window.decC=function(id){CARTC[id]=Math.max(0,(CARTC[id]||0)-1);if(!CARTC[id])delete CARTC[id];sfx.tick();updateBadge();cart()};
var ORDERING=false;   // double-tap guard — prevents duplicate orders
window.confirmOrder=async function(){
  if(ORDERING)return;ORDERING=true;
  var btn=document.getElementById('cfm');if(btn){btn.disabled=true;btn.textContent='Placing order…'}
  var items=Object.keys(CART).map(function(id){return{menu_item_id:+id,qty:CART[id]}})
    .concat(Object.keys(CARTC).map(function(id){return{menu_item_id:+id,qty:CARTC[id],comp:true}}));
  try{
    var j=await api('place_order',{items:items});
    CART={};CARTC={};updateBadge();BAL=+j.balance;
    sfx.chime();celebrate();
    V.innerHTML='<div class="empty" style="color:#EDF1F8"><div style="font-size:60px">✅</div>'+
      '<h2 style="margin:10px 0 4px">Order Placed!</h2>'+
      '<div style="color:#FFB020;font-size:40px;font-weight:700">Token '+j.token+'</div>'+
      '<div style="color:#8A93A6;margin-top:6px">'+(+j.total>0?fmt(j.total)+' paid from wallet · New balance '+fmt(j.balance):'Complimentary — nothing deducted · Balance '+fmt(j.balance))+'</div>'+
      '<button class="btn" style="margin-top:24px;width:auto;padding:14px 34px" onclick="document.querySelector(\'nav a[data-t=orders]\').click()">Track Order →</button></div>';
  }catch(e){cart()}
  finally{ORDERING=false}
};

async function orders(){
  V.innerHTML='<div class="skel" style="margin:14px 16px"></div>';
  var j=await api('my_orders');BAL=+j.balance;
  if(!j.rows.length){V.innerHTML='<div class="empty">🧾<br>No orders yet</div>';return}
  var st={pending:'Pending',preparing:'Preparing',delivered:'Delivered',cancelled:'Cancelled',reversed:'Reversed'};
  var h='';
  j.rows.forEach(function(o){
    h+='<div class="card"><div class="line" style="border:none"><b>Token '+esc(o.token)+'</b>'+
      '<span class="pill p-'+o.status+'">'+st[o.status]+'</span></div>'+
      (o.items||[]).map(function(i){return '<div class="line" style="font-size:14px;color:#8A93A6">'+i.qty+'× '+esc(i.name_snapshot)+(+i.is_comp?' <span class="freetag" style="margin:0;font-size:10px">FREE</span>':'')+'<span>'+(+i.is_comp?'PKR 0':fmt(i.qty*i.price_snapshot))+'</span></div>'}).join('')+
      '<div class="line"><span style="color:#8A93A6;font-size:12px">'+o.placed_at+'</span><b>'+fmt(o.total)+'</b></div>'+
      (o.cancel_reason?'<div style="color:#FF8080;font-size:13px">Reason: '+esc(o.cancel_reason)+'</div>':'')+'</div>';
  });
  V.innerHTML=h;
}

boot().then(home);
</script>
</body>
</html>
