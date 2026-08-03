<?php
require_once __DIR__ . '/lib.php';
$u = current_user();
if ($u) {
  $dest = ['admin'=>'admin.php','accounts'=>'accounts.php','chef'=>'chef.php','sales'=>'sales.php','customer'=>'customer.php'][$u['role']];
  header('Location: '.$dest); exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>ZAS Canteen — Login</title>
<meta name="theme-color" content="#06080F">
<link rel="manifest" href="manifest.json">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Noto+Naskh+Arabic:wght@400;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0}
body{min-height:100vh;background:#06080F;color:#EDF1F8;font-family:'Space Grotesk',sans-serif;display:grid;place-items:center;overflow:hidden}
canvas#stars{position:fixed;inset:0;z-index:0}
.card{position:relative;z-index:1;width:min(380px,92vw);background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);border-radius:24px;padding:36px 30px;backdrop-filter:blur(18px);box-shadow:0 8px 40px rgba(0,0,0,.5)}
.logo{width:64px;height:64px;border-radius:18px;background:linear-gradient(135deg,#FFB020,#F07818);display:grid;place-items:center;font-weight:700;font-size:24px;color:#1A1205;margin:0 auto 16px}
h1{font-size:22px;text-align:center;margin-bottom:4px}
.sub{color:#8A93A6;font-size:13px;text-align:center;margin-bottom:26px}
label{display:block;font-size:12px;letter-spacing:1px;color:#8A93A6;margin:14px 0 6px;text-transform:uppercase}
input{width:100%;padding:14px 16px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);color:#EDF1F8;font-size:16px;font-family:inherit;outline:none}
input:focus{border-color:#FFB020}
button{width:100%;margin-top:24px;padding:15px;border:none;border-radius:14px;background:linear-gradient(135deg,#FFB020,#F07818);color:#1A1205;font-weight:700;font-size:16px;font-family:inherit;cursor:pointer;position:relative;overflow:hidden}
button::after{content:'';position:absolute;top:0;left:-60%;width:40%;height:100%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.5),transparent);animation:sweep 2.8s infinite}
@keyframes sweep{to{left:130%}}
.err{margin-top:14px;color:#FF5C5C;font-size:14px;text-align:center;min-height:18px}
</style>
</head>
<body>
<canvas id="stars"></canvas>
<form class="card" id="f">
  <div class="logo">ZC</div>
  <h1>ZAS Canteen</h1>
  <div class="sub">canteen.zas.deals</div>
  <label>Username</label>
  <input name="username" autocomplete="username" required autofocus>
  <label>Password</label>
  <input name="password" type="password" autocomplete="current-password" required>
  <button type="submit">Sign In</button>
  <div class="err" id="err"></div>
</form>
<script src="app.js"></script>
<script>
// starfield
(function(){
  var c=document.getElementById('stars'),x=c.getContext('2d'),W,H,S=[];
  function rs(){W=c.width=innerWidth;H=c.height=innerHeight}
  rs();addEventListener('resize',rs);
  for(var i=0;i<140;i++)S.push({x:Math.random(),y:Math.random(),r:Math.random()*1.4+.3,p:Math.random()*6.28,s:.4+Math.random()*.9});
  (function t(n){x.clearRect(0,0,W,H);
    var g=x.createRadialGradient(W*.7,H*.3,0,W*.7,H*.3,W*.6);
    g.addColorStop(0,'rgba(255,176,32,.06)');g.addColorStop(1,'transparent');
    x.fillStyle=g;x.fillRect(0,0,W,H);
    S.forEach(function(s){var a=.3+.7*Math.abs(Math.sin(n*.001*s.s+s.p));
      x.globalAlpha=a;x.fillStyle='#EDF1F8';x.beginPath();x.arc(s.x*W,s.y*H,s.r,0,6.28);x.fill()});
    x.globalAlpha=1;requestAnimationFrame(t)})(0);
})();
document.getElementById('f').addEventListener('submit', async function(ev){
  ev.preventDefault();
  var fd=new FormData(this);
  var r=await fetch('api.php?a=login',{method:'POST',headers:{'Content-Type':'application/json'},
    body:JSON.stringify({username:fd.get('username'),password:fd.get('password')})});
  var j=await r.json();
  if(!j.ok){document.getElementById('err').textContent=j.error||'Login failed';return}
  location.href={admin:'admin.php',accounts:'accounts.php',chef:'chef.php',sales:'sales.php',customer:'customer.php'}[j.role];
});
</script>
</body>
</html>
