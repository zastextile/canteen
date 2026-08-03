<?php
require_once __DIR__ . '/lib.php';
$me = page_guard('sales');
?><!DOCTYPE html>
<html lang="ur" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>ZAS Canteen — سیلز</title>
<meta name="theme-color" content="#06080F">
<link rel="manifest" href="manifest.json">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Noto+Naskh+Arabic:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0}
body{background:#06080F;color:#EDF1F8;font-family:'Noto Naskh Arabic',serif;min-height:100vh;padding-bottom:86px;overflow-x:hidden}
.latin{font-family:'Space Grotesk',sans-serif}
header{padding:10px 20px 2px;display:flex;justify-content:space-between;align-items:center}
h1{font-size:22px}
.mute{width:44px;height:44px;border-radius:50%;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);color:#EDF1F8;font-size:20px;cursor:pointer}
/* Carousel — each card fixed to one screen height, no vertical scroll */
.rail{display:flex;gap:14px;overflow-x:auto;scroll-snap-type:x mandatory;padding:6px 12px 8px;-webkit-overflow-scrolling:touch}
.rail::-webkit-scrollbar{display:none}
.oc{flex:none;width:min(94vw,420px);height:calc(100vh - 148px);max-height:780px;display:flex;flex-direction:column;scroll-snap-align:center;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:28px;overflow:hidden;backdrop-filter:blur(14px);box-shadow:0 8px 32px rgba(0,0,0,.4)}
.oc.preparing{border-color:rgba(79,163,255,.45)}
/* HERO face — big but capped so food + buttons always stay visible */
.hero{position:relative;width:100%;flex:none;height:33%;min-height:150px;background:#1A2338}
.hero img{width:100%;height:100%;object-fit:contain;display:block}
.hero .noface{width:100%;height:100%;display:grid;place-items:center;font-size:90px}
.hero .tok{position:absolute;top:12px;left:12px;background:rgba(255,176,32,.92);color:#1A1205;font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:28px;padding:2px 14px;border-radius:14px;z-index:2}
.hero .st{position:absolute;top:12px;right:12px;z-index:2}
/* bottom of hero: scrim + speaker (one side) + amount (other side) */
.hero .scrim{position:absolute;left:0;right:0;bottom:0;height:70px;background:linear-gradient(transparent,rgba(6,8,15,.88));z-index:1;pointer-events:none}
.hero .spk{position:absolute;bottom:10px;inset-inline-end:10px;z-index:2;display:flex;align-items:center;gap:8px;padding:11px 20px;border:none;border-radius:99px;background:linear-gradient(135deg,#FFB020,#F07818);color:#1A1205;font-weight:700;font-size:19px;font-family:inherit;box-shadow:0 6px 20px rgba(240,120,24,.5);cursor:pointer}
.hero .amt{position:absolute;bottom:10px;inset-inline-start:14px;z-index:2;font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:32px;color:#FFB020;text-shadow:0 2px 10px rgba(0,0,0,.7);line-height:1.1}
.hero .amt span{font-family:'Noto Naskh Arabic',serif;font-size:16px;color:#EDF1F8;margin-right:6px}
.body{padding:10px 18px 14px;background:#0B0F1A;flex:1;display:flex;flex-direction:column;min-height:0}
.nmrow{display:flex;align-items:center;justify-content:space-between;gap:10px}
.big{font-size:27px;font-weight:700;line-height:1.4}
.timer{font-size:15px;color:#8A93A6}
.timer.late{color:#FF5C5C;font-weight:700}
/* big food tiles */
.foods{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:8px 0;border-top:1px solid rgba(255,255,255,.08);padding-top:10px;flex:1;min-height:96px;align-content:start;overflow-y:auto}
.fd{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.09);border-radius:18px;padding:12px 6px;text-align:center}
.fd .em{font-size:52px;line-height:1.1}
.fd .qn{font-size:20px;font-weight:700;margin-top:4px}
.acts{display:flex;gap:8px;margin-top:8px}
.acts button{flex:1;padding:14px;border-radius:13px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.07);color:#EDF1F8;font-size:18px;font-family:inherit;cursor:pointer}
.acts .prep{border-color:rgba(79,163,255,.5);color:#4FA3FF}
.acts .done{border-color:rgba(61,214,140,.5);color:#3DD68C}
.pill{display:inline-block;padding:4px 16px;border-radius:99px;font-size:16px;font-weight:700}
.p-pending{background:rgba(255,176,32,.2);color:#FFB020}.p-preparing{background:rgba(79,163,255,.2);color:#4FA3FF}
/* Stock tab */
.card{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);border-radius:20px;padding:14px 18px;margin:10px 16px}
.row{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.06);font-size:20px}
.row:last-child{border-bottom:none}
.low{color:#FF5C5C;font-weight:700}
/* stock rows (read-only for the counter) */
.srow{padding:12px 0;gap:10px}
.sname{font-size:20px;flex:1;min-width:0}
.sqty{min-width:54px;text-align:center;font-size:26px;font-weight:700}
/* empty-queue pledge */
.pledge{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:34px 20px 20px}
.pl-icon{font-size:62px;line-height:1}
.pl-none{color:#8A93A6;font-size:20px;margin-top:8px}
.pl-card{margin-top:24px;background:linear-gradient(160deg,rgba(255,176,32,.10),rgba(255,255,255,.035));border:1px solid rgba(255,176,32,.28);border-radius:26px;padding:26px 22px;max-width:430px;box-shadow:0 8px 32px rgba(0,0,0,.35)}
.pl-main{font-size:23px;font-weight:700;line-height:2;color:#FFD98A}
.pl-sub{margin-top:18px;padding-top:16px;border-top:1px solid rgba(255,176,32,.2);font-size:18px;line-height:2.1;color:#B9C2D4}
nav{position:fixed;bottom:0;left:0;right:0;display:flex;background:rgba(11,15,26,.92);backdrop-filter:blur(16px);border-top:1px solid rgba(255,255,255,.08);padding:8px 0 max(8px, env(safe-area-inset-bottom));z-index:10}
nav a{flex:1;text-align:center;color:#8A93A6;text-decoration:none;font-size:13px;padding:6px 0;cursor:pointer}
nav a.on{color:#FFB020}
nav a span{display:block;font-size:24px;margin-bottom:2px}
.empty{text-align:center;color:#8A93A6;padding:60px 30px;font-size:22px}
</style>
</head>
<body>
<header>
  <h1>🧾 آرڈر قطار</h1>
  <div style="display:flex;gap:8px">
    <button class="mute" id="mute">🔊</button>
    <button class="mute" onclick="logout()" title="Logout">⏻</button>
  </div>
</header>
<div id="view"></div>
<nav>
  <a data-t="queue" class="on"><span>🧾</span>آرڈر</a>
  <a data-t="stock"><span>🛒</span>آج کا اسٹاک</a>
</nav>
<script src="app.js"></script>
<script>
var V=document.getElementById('view'),TAB='queue',TIMER=null,AUDIO={};
function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
var muteBtn=document.getElementById('mute');
muteBtn.textContent=sfx.muted?'🔇':'🔊';
muteBtn.onclick=function(){muteBtn.textContent=sfx.toggle()?'🔇':'🔊'};
document.querySelectorAll('nav a').forEach(function(a){a.onclick=function(){sfx.tick();TAB=a.dataset.t;
  document.querySelectorAll('nav a').forEach(function(x){x.classList.toggle('on',x===a)});
  TAB==='queue'?queue():stock()}});

var urduNum={1:'ایک',2:'دو',3:'تین',4:'چار',5:'پانچ',6:'چھ',7:'سات',8:'آٹھ',9:'نو',10:'دس'};
function ago(ts){
  var s=Math.max(0,(Date.now()-new Date(ts.replace(' ','T')))/1000);
  var m=Math.floor(s/60);
  return {txt:m<1?'ابھی':m+' منٹ',late:m>=10};
}
// clock time the order was placed — e.g. "1:04"
function clock(ts){
  var d=new Date(ts.replace(' ','T')),h=d.getHours(),m=d.getMinutes();
  h=h%12||12;
  return h+':'+(m<10?'0':'')+m;
}

var lastIds='';
async function queue(){
  var j=await api('queue');
  var ids=j.rows.map(function(o){return o.id}).join(',');
  if(ids!==lastIds && lastIds!=='' && TAB==='queue') sfx.whoosh();  // new order arrived
  lastIds=ids;
  if(TAB!=='queue')return;
  if(!j.rows.length){
    V.innerHTML='<div class="pledge">'+
      '<div class="pl-icon">🤲</div>'+
      '<div class="pl-none">کوئی آرڈر نہیں</div>'+
      '<div class="pl-card">'+
        '<div class="pl-main">میں اللہ کو حاضر ناظر جان کر عہد کرتا ہوں کہ اپنے ادارے اور اپنے مالک کے ساتھ کبھی خیانت نہیں کروں گا</div>'+
        '<div class="pl-sub">اللہ ہر وقت دیکھ رہا ہے<br>رزقِ حلال میں ہی برکت ہے</div>'+
      '</div></div>';
    return;
  }
  var h='<div class="rail">';
  j.rows.forEach(function(o){
    var t=ago(o.placed_at);
    h+='<div class="oc '+o.status+'">'+
      '<div class="hero">'+
        (o.photo_url?'<img src="'+esc(o.photo_url)+'" alt="">':'<div class="noface">👤</div>')+
        '<div class="tok latin">'+esc(o.token)+'</div>'+
        '<div class="st"><span class="pill p-'+o.status+'">'+(o.status==='pending'?'⏳ نیا آرڈر':'تیار ہو رہا ہے')+'</span></div>'+
        '<div class="scrim"></div>'+
        '<div class="amt latin">'+Math.round(o.total)+'<span>روپے</span></div>'+
        '<button class="spk" onclick="speakOrder('+o.id+')">🔊 سنیں</button>'+
      '</div>'+
      '<div class="body">'+
        '<div class="nmrow"><div class="big">'+esc(o.customer_urdu||o.customer)+'</div>'+
        '<div class="timer latin '+(t.late?'late':'')+'">⏱ '+t.txt+' · 🕐 '+clock(o.placed_at)+'</div></div>'+
        '<div class="foods">'+(o.items||[]).map(function(i){
          return '<div class="fd"><div class="em">'+esc(i.emoji||'🍽')+'</div>'+
                 '<div class="qn">'+(urduNum[i.qty]||i.qty)+' '+esc(i.name_urdu_snapshot||i.name_snapshot)+'</div></div>';
        }).join('')+'</div>'+
        '<div class="acts">'+
          (o.status==='pending'?'<button class="prep" onclick="setStatus('+o.id+',\'preparing\')">تیار کریں</button>':'')+
          '<button class="done" onclick="setStatus('+o.id+',\'delivered\')">حوالے کر دیا ✓</button>'+
        '</div>'+
      '</div></div>';
  });
  V.innerHTML=h+'</div>';
}

window.setStatus=async function(id,st){
  await api('order_status',{id:id,status:st});
  if(st==='delivered'){sfx.chime();celebrate();delete AUDIO[id]}
  else sfx.whoosh();
  queue();
};

window.speakOrder=async function(id){
  sfx.tick();
  if(AUDIO[id]){AUDIO[id].currentTime=0;AUDIO[id].play();return}   // replay cached, no cost
  var j=await api('order_voice',{id:id});
  if(j.url){var a=new Audio(j.url);AUDIO[id]=a;a.play();}
  else if(j.text){speakLocal(j.text)}                              // no API key: device voice
};
function speakLocal(text){
  if(!('speechSynthesis' in window)){toast('اس فون پر آواز دستیاب نہیں',true);return}
  var vs=speechSynthesis.getVoices();
  var v=vs.find(function(x){return /^ur/i.test(x.lang)})||vs.find(function(x){return /^hi/i.test(x.lang)})||vs.find(function(x){return /^ar/i.test(x.lang)})||null;
  var u=new SpeechSynthesisUtterance(text);
  if(v){u.voice=v;u.lang=v.lang}else u.lang='ur-PK';
  u.rate=.85;speechSynthesis.cancel();
  setTimeout(function(){speechSynthesis.speak(u)},60);
  window.__u=u;
}

// Read-only for the counter — only Accounts/Admin can change quantities.
// Finished items (0) are hidden so staff only ever see what they can actually sell.
async function stock(){
  var j=await api('sales_today');
  if(TAB!=='stock')return;
  var rows=(j.rows||[]).filter(function(r){return +r.qty>0});
  if(!rows.length){V.innerHTML='<div class="empty">🛒<br>آج کوئی اسٹاک نہیں</div>';return}
  var h='<div class="card">';
  rows.forEach(function(r){
    var q=Math.round(+r.qty);
    h+='<div class="row srow"><div class="sname">'+(r.emoji||'🍽')+' '+esc(r.name_urdu||r.name)+'</div>'+
       '<div class="sqty latin '+(q<=5?'low':'')+'">'+q+(q<=5?' ⚠':'')+'</div></div>';
  });
  V.innerHTML=h+'</div>';
}

boot().then(function(){
  queue();
  TIMER=setInterval(function(){TAB==='queue'?queue():stock()},8000);  // live refresh
});
</script>
</body>
</html>
