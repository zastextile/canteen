<?php
require_once __DIR__ . '/lib.php';
$me = page_guard('chef');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>ZAS Canteen — Chef</title>
<meta name="theme-color" content="#06080F">
<link rel="manifest" href="manifest.json">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Noto+Naskh+Arabic:wght@400;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0}
body{background:#06080F;color:#EDF1F8;font-family:'Space Grotesk',sans-serif;min-height:100vh;padding-bottom:86px}
header{padding:18px 20px 8px;display:flex;justify-content:space-between;align-items:center}
h1{font-size:20px}
.card{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);border-radius:20px;padding:16px 18px;margin:10px 16px}
.row{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.06)}
.row:last-child{border-bottom:none}
.qty{font-weight:700;color:#FFB020}
input[type=number]{width:92px;padding:12px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);color:#EDF1F8;font-size:16px;font-family:inherit;text-align:center;outline:none}
input:focus{border-color:#FFB020}
.btn{width:calc(100% - 32px);margin:14px 16px 0;padding:16px;border:none;border-radius:14px;background:linear-gradient(135deg,#FFB020,#F07818);color:#1A1205;font-weight:700;font-size:16px;font-family:inherit;cursor:pointer;transition:transform .12s}
.btn:active{transform:scale(.98)}
#view{animation:fadein .25s ease}
@keyframes fadein{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
@media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}
nav{position:fixed;bottom:0;left:0;right:0;display:flex;background:rgba(11,15,26,.92);backdrop-filter:blur(16px);border-top:1px solid rgba(255,255,255,.08);padding:8px 0 max(8px, env(safe-area-inset-bottom))}
nav a{flex:1;text-align:center;color:#8A93A6;text-decoration:none;font-size:11px;padding:6px 0;cursor:pointer}
nav a.on{color:#FFB020}
nav a span{display:block;font-size:22px;margin-bottom:2px}
.hint{color:#8A93A6;font-size:13px;margin:4px 20px}
</style>
</head>
<body>
<header><h1>👨‍🍳 Chef Kitchen</h1><a onclick="logout()" style="color:#FF8080;cursor:pointer;font-size:14px">Logout</a></header>
<div id="view"></div>
<nav>
  <a data-t="stock" class="on"><span>🥬</span>Kitchen Stock</a>
  <a data-t="consume"><span>🍳</span>Consumption</a>
</nav>
<script src="<?= asset('app.js') ?>"></script>
<script>
var V=document.getElementById('view');
function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
document.querySelectorAll('nav a').forEach(function(a){a.onclick=function(){sfx.tick();
  document.querySelectorAll('nav a').forEach(function(x){x.classList.toggle('on',x===a)});
  ({stock:stock,consume:consume})[a.dataset.t]()}});

async function stock(){
  V.innerHTML='<div class="card">Loading…</div>';
  var j=await api('chef_stock');
  if(!j.rows.length){V.innerHTML='<div class="card" style="text-align:center;color:#8A93A6">No stock yet. Purchases by Accounts appear here automatically.</div>';return}
  var h='<div class="card">';
  j.rows.forEach(function(r){h+='<div class="row"><div>'+esc(r.name)+'</div><div class="qty">'+(+r.qty).toFixed(2)+' '+esc(r.unit)+'</div></div>'});
  V.innerHTML=h+'</div>';
}

async function consume(){
  V.innerHTML='<div class="card">Loading…</div>';
  var j=await api('chef_stock');
  if(!j.rows.length){V.innerHTML='<div class="card" style="text-align:center;color:#8A93A6">Nothing to consume — no stock.</div>';return}
  var h='<p class="hint">Enter quantities used today. They are deducted from your kitchen stock.</p><div class="card" id="cf">';
  j.rows.forEach(function(r){
    h+='<div class="row"><div>'+esc(r.name)+'<div style="font-size:12px;color:#8A93A6">have '+(+r.qty).toFixed(2)+' '+esc(r.unit)+'</div></div>'+
       '<input type="number" step="0.001" min="0" max="'+r.qty+'" placeholder="0" data-item="'+r.item_id+'"></div>';
  });
  V.innerHTML=h+'</div><button class="btn" onclick="saveConsume()">Save Consumption</button>';
}
window.saveConsume=async function(){
  var items=[];
  document.querySelectorAll('#cf input').forEach(function(i){
    if(+i.value>0)items.push({item_id:+i.dataset.item,qty:+i.value});
  });
  if(!items.length){toast('Enter at least one quantity',true);return}
  await api('consume',{items:items});
  sfx.chime();celebrate();toast('Recorded — stock deducted');
  consume();
};

boot().then(stock);
</script>
</body>
</html>
