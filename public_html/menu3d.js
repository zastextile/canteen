// ZAS Canteen — 3D photo menu for the customer app.
// Loaded by customer.php. Falls back to a swipe rail when WebGL is unavailable,
// and to the existing emoji when a dish has no photo in dish/<id>.webp.
// Styles are scoped under .m3d so customer.php's own CSS is untouched.
(function () {

  var PHOTO_DIR = 'dish/';           // dish/7.webp = Tea, dish/4.webp = Biryani Chicken
  var CW = 512, CH = 640, PHOTO_H = 410;

  var TINT = {
    "Lunch":     "#FF9A2E",
    "Snacks":    "#FFC24D",
    "Beverages": "#5FA0FF",
    "Breakfast": "#FFA04D"
  };
  function tint(c){ return TINT[c] || "#FF9A2E"; }

  var CSS = ''
    + '.m3d{animation:m3dfade .25s ease}'
    + '@keyframes m3dfade{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}'
    + '.m3d .m3dbar{display:flex;gap:7px;align-items:center;padding:8px 16px 4px;overflow-x:auto;scrollbar-width:none}'
    + '.m3d .m3dbar::-webkit-scrollbar{display:none}'
    + '.m3d .m3dc{flex:none;padding:9px 18px;border-radius:99px;border:1px solid rgba(255,255,255,.12);'
    + 'background:rgba(255,255,255,.06);color:#8A93A6;font-family:inherit;font-size:14px;cursor:pointer;white-space:nowrap}'
    + '.m3d .m3dc.on{background:rgba(255,176,32,.15);border-color:#FFB020;color:#FFB020;font-weight:700}'
    + '.m3d .stage{position:relative;margin:8px 16px;border-radius:22px;overflow:hidden;'
    + 'border:1px solid rgba(255,255,255,.1);background:radial-gradient(120% 90% at 50% 8%,rgba(255,176,32,.13),transparent 62%),'
    + 'linear-gradient(180deg,#0A0F1A,#06080F 78%)}'
    + '.m3d canvas.gl{display:block;width:100%;height:360px;touch-action:pan-y;cursor:grab}'
    + '.m3d .rail{display:flex;gap:16px;height:360px;align-items:center;overflow-x:auto;overflow-y:hidden;'
    + 'scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;padding:0 calc(50% - 88px);scrollbar-width:none}'
    + '.m3d .rail[hidden]{display:none}'
    + '.m3d .rail::-webkit-scrollbar{display:none}'
    + '.m3d .rail img{width:176px;flex:none;display:block;scroll-snap-align:center;border-radius:17px;'
    + 'opacity:.42;transform:scale(.86);transition:opacity .3s,transform .3s;cursor:pointer}'
    + '.m3d .rail img.on{opacity:1;transform:scale(1);box-shadow:0 16px 40px rgba(0,0,0,.55)}'
    + '.m3d .tagline{position:absolute;left:14px;top:12px;z-index:3;font-size:10px;letter-spacing:.9px;'
    + 'text-transform:uppercase;font-weight:700;padding:5px 10px;border-radius:7px;color:#8A93A6;'
    + 'background:rgba(11,15,26,.82);border:1px solid rgba(255,255,255,.1)}'
    + '.m3d .nav{position:absolute;right:12px;bottom:12px;z-index:3;display:flex;gap:7px}'
    + '.m3d .nav button{width:38px;height:38px;border-radius:12px;border:1px solid rgba(255,255,255,.12);'
    + 'background:rgba(11,15,26,.8);color:#EDF1F8;font-family:inherit;font-size:18px;cursor:pointer}'
    + '.m3d .det{margin:10px 16px 0;padding:16px 18px;border-radius:20px;'
    + 'background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1)}'
    + '.m3d .det .nm{font-size:19px;font-weight:700}'
    + '.m3d .det .ur{font-family:"Noto Naskh Arabic",serif;font-size:17px;color:#FFB020;direction:rtl;margin-top:2px}'
    + '.m3d .det .meta{display:flex;flex-wrap:wrap;gap:7px;margin-top:9px}'
    + '.m3d .t{font-size:11px;letter-spacing:.8px;text-transform:uppercase;font-weight:700;padding:4px 10px;'
    + 'border-radius:6px;background:rgba(255,255,255,.07);color:#8A93A6}'
    + '.m3d .t.ok{background:rgba(61,214,140,.13);color:#3DD68C}'
    + '.m3d .t.low{background:rgba(255,176,32,.14);color:#FFB020}'
    + '.m3d .t.out{background:rgba(255,92,92,.14);color:#FF8080}'
    + '.m3d .buy{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-top:14px;flex-wrap:wrap}'
    + '.m3d .pr{font-size:25px;font-weight:700;color:#FFB020;line-height:1}'
    + '.m3d .pr.free{color:#3DD68C}'
    + '.m3d .pr small{font-size:12px;font-weight:500;color:#8A93A6}'
    + '.m3d .addb{flex:1;min-width:150px;padding:14px;border:none;border-radius:13px;cursor:pointer;'
    + 'background:linear-gradient(135deg,#FFB020,#F07818);color:#1A1205;font-family:inherit;font-weight:700;font-size:15px}'
    + '.m3d .addb.free{background:linear-gradient(135deg,#5BE39D,#25A870);color:#04240F}'
    + '.m3d .addb:disabled{background:rgba(255,255,255,.07);color:#8A93A6;cursor:not-allowed}'
    + '.m3d .addb:active:not(:disabled){transform:scale(.97)}'
    + '.m3d .none{padding:120px 20px;text-align:center;color:#8A93A6;font-size:14px}'
    + '@media (prefers-reduced-motion:reduce){.m3d,.m3d *{animation:none!important;transition:none!important}}';

  function css(){
    if(document.getElementById('m3d-css'))return;
    var s=document.createElement('style'); s.id='m3d-css'; s.textContent=CSS;
    document.head.appendChild(s);
  }
  function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){
    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
  function rr(g,x,y,w,h,r){
    if(g.roundRect){g.beginPath();g.roundRect(x,y,w,h,r);return}
    g.beginPath();g.moveTo(x+r,y);g.arcTo(x+w,y,x+w,y+h,r);g.arcTo(x+w,y+h,x,y+h,r);
    g.arcTo(x,y+h,x,y,r);g.arcTo(x,y,x+w,y,r);g.closePath();
  }
  function wrapTxt(g,text,maxW,maxLines){
    var words=String(text).split(' '),lines=[],cur='';
    for(var i=0;i<words.length;i++){
      var t=cur?cur+' '+words[i]:words[i];
      if(g.measureText(t).width>maxW&&cur){lines.push(cur);cur=words[i]}else cur=t;
      if(lines.length===maxLines-1&&g.measureText(cur).width>maxW){
        while(cur.length>3&&g.measureText(cur+'…').width>maxW)cur=cur.slice(0,-1);
        cur+='…';break;
      }
    }
    if(cur)lines.push(cur);
    return lines.slice(0,maxLines);
  }

  /* One card: real photo when dish/<id>.webp exists, otherwise the dish's emoji. */
  function drawCard(d,img){
    var c=document.createElement('canvas'); c.width=CW; c.height=CH;
    var g=c.getContext('2d'), out=+d.stock<=0, hue=tint(d.category);
    g.save(); rr(g,0,0,CW,CH,34); g.clip();
    g.fillStyle='#0B111C'; g.fillRect(0,0,CW,CH);

    if(img){
      var s=Math.max(CW/img.width,PHOTO_H/img.height), w=img.width*s, h=img.height*s;
      g.drawImage(img,(CW-w)/2,(PHOTO_H-h)/2,w,h);
      var sc=g.createLinearGradient(0,PHOTO_H-150,0,PHOTO_H);
      sc.addColorStop(0,'rgba(11,17,28,0)'); sc.addColorStop(1,'#0B111C');
      g.fillStyle=sc; g.fillRect(0,PHOTO_H-150,CW,150);
    }else{
      var wash=g.createLinearGradient(0,0,CW,PHOTO_H);
      wash.addColorStop(0,'#141C2B'); wash.addColorStop(1,'#0C121D');
      g.fillStyle=wash; g.fillRect(0,0,CW,PHOTO_H);
      var gl=g.createRadialGradient(CW/2,PHOTO_H*.44,20,CW/2,PHOTO_H*.44,CW*.6);
      gl.addColorStop(0,hue+'30'); gl.addColorStop(1,'rgba(0,0,0,0)');
      g.fillStyle=gl; g.fillRect(0,0,CW,PHOTO_H);
      g.textAlign='center'; g.font='150px serif';
      g.fillText(d.emoji||'🍽',CW/2,PHOTO_H*.56);
    }
    if(out){ g.fillStyle='rgba(6,8,15,.62)'; g.fillRect(0,0,CW,PHOTO_H); }

    g.fillStyle='#0B111C'; g.fillRect(0,PHOTO_H,CW,CH-PHOTO_H);
    g.strokeStyle='rgba(255,255,255,.07)'; g.lineWidth=2;
    g.beginPath(); g.moveTo(34,PHOTO_H); g.lineTo(CW-34,PHOTO_H); g.stroke();

    g.textAlign='left'; g.fillStyle='#7E879B'; g.font="700 17px 'Space Grotesk',sans-serif";
    var eb=String(d.category||'').toUpperCase(), ex=34;
    for(var i=0;i<eb.length;i++){ g.fillText(eb[i],ex,PHOTO_H+46); ex+=g.measureText(eb[i]).width+2.6; }

    g.fillStyle=out?'#7A8296':'#F3F6FC'; g.font="700 38px 'Space Grotesk',sans-serif";
    var lines=wrapTxt(g,d.name,CW-68,2), y=PHOTO_H+96;
    for(var L=0;L<lines.length;L++){ g.fillText(lines[L],34,y); y+=44; }

    if(d.name_urdu){
      g.textAlign='right'; g.fillStyle='#FFB020'; g.font="700 30px 'Noto Naskh Arabic',serif";
      g.fillText(d.name_urdu,CW-34,CH-40);
    }

    var bx=CW-84, by=84, br=58;
    g.beginPath(); g.arc(bx,by,br+7,0,6.283); g.fillStyle='rgba(11,17,28,.72)'; g.fill();
    var pg=g.createLinearGradient(bx-br,by-br,bx+br,by+br);
    if(d.isFree){pg.addColorStop(0,'#5BE39D');pg.addColorStop(1,'#25A870')}
    else{pg.addColorStop(0,'#FFB020');pg.addColorStop(1,'#F07818')}
    g.beginPath(); g.arc(bx,by,br,0,6.283); g.fillStyle=pg; g.fill();
    g.textAlign='center'; g.fillStyle=d.isFree?'#04240F':'#1A1205';
    if(d.isFree){ g.font="700 26px 'Space Grotesk',sans-serif"; g.fillText('FREE',bx,by+9); }
    else{
      g.font="700 15px 'Space Grotesk',sans-serif"; g.fillText('PKR',bx,by-14);
      var p=Math.round(+d.price);
      g.font='700 '+(p>=1000?31:38)+"px 'Space Grotesk',sans-serif"; g.fillText(String(p),bx,by+22);
    }
    if(out){
      g.fillStyle='rgba(255,92,92,.16)'; g.fillRect(0,PHOTO_H*.44-34,CW,68);
      g.fillStyle='#FF8080'; g.textAlign='center'; g.font="700 30px 'Space Grotesk',sans-serif";
      g.fillText('OUT OF STOCK',CW/2,PHOTO_H*.44+11);
    }
    g.restore();
    g.strokeStyle=out?'rgba(255,92,92,.24)':(d.isFree?'rgba(61,214,140,.4)':'rgba(255,255,255,.14)');
    g.lineWidth=3; rr(g,1.5,1.5,CW-3,CH-3,34); g.stroke();
    return c;
  }

  /* dish/<id>.webp — resolved once per dish, cached, null when absent. */
  var photos={};
  function loadPhoto(id,cb){
    if(photos[id]!==undefined){cb(photos[id]);return}
    var im=new Image();
    im.onload=function(){photos[id]=im;cb(im)};
    im.onerror=function(){photos[id]=null;cb(null)};
    im.src=PHOTO_DIR+id+'.webp';
  }

  window.Menu3D = { render: render };

  var host,opts,items=[],focus=0,cat='All',auto=true;
  var reduce=matchMedia('(prefers-reduced-motion: reduce)').matches;
  var renderer=null,scene,camera,ring,meshes=[],step=0,R=9,rot=0,target=0,MODE='2d',looping=false;
  var glCanvas,railEl,detEl,tagEl;

  function render(container,o){
    css(); host=container; opts=o||{}; auto=!reduce; focus=0;
    host.innerHTML='<div class="m3d">'
      + '<div class="m3dbar" id="m3dcats"></div>'
      + '<div class="stage">'
      +   '<div class="tagline" id="m3dtag">Showcase</div>'
      +   '<canvas class="gl" id="m3dgl"></canvas>'
      +   '<div class="rail" id="m3drail" hidden></div>'
      +   '<div class="nav"><button type="button" id="m3dprev" aria-label="Previous">&#8249;</button>'
      +   '<button type="button" id="m3dnext" aria-label="Next">&#8250;</button></div>'
      + '</div>'
      + '<div class="det" id="m3ddet"></div></div>';
    glCanvas=document.getElementById('m3dgl');
    railEl=document.getElementById('m3drail');
    detEl=document.getElementById('m3ddet');
    tagEl=document.getElementById('m3dtag');
    initGL(); bindStage(); buildCats(); rebuild();
  }

  function initGL(){
    if(renderer){ try{renderer.dispose()}catch(e){} renderer=null; meshes=[]; }
    try{
      if(typeof THREE==='undefined') throw new Error('three.js missing');
      scene=new THREE.Scene();
      camera=new THREE.PerspectiveCamera(42,1,.1,120);
      renderer=new THREE.WebGLRenderer({canvas:glCanvas,antialias:true,alpha:true});
      renderer.setPixelRatio(Math.min(devicePixelRatio,2));
      if(THREE.sRGBEncoding!==undefined)renderer.outputEncoding=THREE.sRGBEncoding;
      ring=new THREE.Group(); scene.add(ring);
      MODE='3d';
    }catch(e){ renderer=null; MODE='2d'; }
    if(MODE==='2d'){ glCanvas.style.display='none'; railEl.hidden=false; tagEl.textContent='Swipe view'; }
    else{ glCanvas.style.display='block'; railEl.hidden=true; tagEl.textContent='3D view'; }
  }

  function list(){
    var rows=(opts.items||[]).slice();
    var comps=opts.comps||[];
    var free=[];
    comps.forEach(function(c){
      var m=null;
      rows.forEach(function(r){ if(+r.id===+c.menu_item_id) m=r; });
      var base=m||{id:+c.menu_item_id,name:c.name,name_urdu:c.name_urdu,category:c.category||'Complimentary',
                   price:+c.price||0,emoji:c.emoji,stock:+c.stock,menu_group:c.menu_group};
      var d={}; for(var k in base)d[k]=base[k];
      d.isFree=true; d.remaining=+c.remaining;
      free.push(d);
    });
    var paid=rows.filter(function(r){
      if(cat!=='All'&&r.category!==cat)return false;
      return true;
    });
    if(cat!=='All') free=free.filter(function(f){return f.category===cat});
    return free.concat(paid);
  }

  function buildCats(){
    var el=document.getElementById('m3dcats');
    var cats=['All'];
    (opts.items||[]).forEach(function(r){ if(cats.indexOf(r.category)<0)cats.push(r.category) });
    el.innerHTML=cats.map(function(c){
      return '<button type="button" class="m3dc'+(c===cat?' on':'')+'" data-c="'+esc(c)+'">'+esc(c)+'</button>';
    }).join('');
    [].forEach.call(el.children,function(b){
      b.onclick=function(){
        cat=b.dataset.c; auto=false;
        [].forEach.call(el.children,function(x){x.classList.toggle('on',x===b)});
        rebuild();
      };
    });
  }

  function rebuild(){
    items=list(); focus=0;
    if(!items.length){
      if(MODE==='3d'){ meshes.forEach(function(m){ring.remove(m)}); meshes=[]; }
      else railEl.innerHTML='<div class="none">Nothing on this menu right now.</div>';
      detEl.innerHTML='<div style="color:#8A93A6;font-size:14px">No dishes in this category.</div>';
      return;
    }
    var pending=items.length;
    items.forEach(function(d){
      loadPhoto(d.id,function(img){ d._img=img; if(--pending===0) paint(); });
    });
  }

  function paint(){
    if(MODE==='3d'){
      meshes.forEach(function(m){ring.remove(m);m.geometry.dispose();m.material.map.dispose();m.material.dispose()});
      meshes=[];
      step=(Math.PI*2)/Math.max(items.length,8);
      R=Math.max(6,(items.length*2.45)/(Math.PI*2));
      items.forEach(function(d){
        var tex=new THREE.CanvasTexture(drawCard(d,d._img));
        tex.anisotropy=4;
        if(THREE.sRGBEncoding!==undefined)tex.encoding=THREE.sRGBEncoding;
        var m=new THREE.Mesh(new THREE.PlaneGeometry(2.0,2.5),
          new THREE.MeshBasicMaterial({map:tex,transparent:true,depthWrite:false}));
        meshes.push(m); ring.add(m);
      });
      rot=0; target=0; layout();
      camera.position.set(0,.15,R*.42+3.4); camera.lookAt(0,0,0);
      sizeGL(); startLoop();
    }else{
      railEl.innerHTML='';
      items.forEach(function(d,i){
        var im=new Image();
        im.src=drawCard(d,d._img).toDataURL('image/png');
        im.alt=d.name;
        im.onclick=function(){auto=false;go(i)};
        railEl.appendChild(im);
      });
      setTimeout(function(){paint2d();go(0,true)},30);
    }
    detail();
  }

  function layout(){
    for(var i=0;i<meshes.length;i++){
      var a=i*step-rot;
      while(a>Math.PI)a-=Math.PI*2; while(a<-Math.PI)a+=Math.PI*2;
      var m=meshes[i];
      m.position.set(Math.sin(a)*R,0,-R+Math.cos(a)*R);
      m.rotation.y=a;
      var near=Math.max(0,1-Math.abs(a)/(step*3.2));
      m.material.opacity=.14+near*.86;
      var s=.78+near*.30; m.scale.set(s,s,1);
      m.position.y=near*.10;
      m.visible=Math.abs(a)<step*4.6;
      m.renderOrder=Math.round(near*100);
    }
  }
  function paint2d(){ for(var i=0;i<railEl.children.length;i++) railEl.children[i].classList.toggle('on',i===focus); }

  function detail(){
    var d=items[focus]; if(!d){detEl.innerHTML='';return}
    var out=+d.stock<=0, left=+d.stock;
    var h='<div class="nm">'+esc(d.name)+'</div>';
    if(d.name_urdu)h+='<div class="ur">'+esc(d.name_urdu)+'</div>';
    h+='<div class="meta"><span class="t">'+esc(d.category||'')+'</span>';
    h+= out?'<span class="t out">Out of stock</span>'
       : left<=10?'<span class="t low">Only '+left+' left</span>'
       :'<span class="t ok">'+left+' in stock</span>';
    if(d.isFree)h+='<span class="t ok">Free · '+d.remaining+' left</span>';
    if(d.menu_group==='special')h+='<span class="t">⭐ Special</span>';
    if(d.menu_group==='executive')h+='<span class="t">👑 Executive</span>';
    h+='</div><div class="buy">';
    h+= d.isFree ? '<div class="pr free">FREE</div>'
                 : '<div class="pr">PKR '+Math.round(+d.price)+' <small>each</small></div>';
    h+='<button type="button" class="addb'+(d.isFree?' free':'')+'" id="m3dadd"'+(out?' disabled':'')+'>'
      + (out?'Out of stock':(d.isFree?'+ Add Free':'+ Add to cart'))+'</button></div>';
    detEl.innerHTML=h;
    var b=document.getElementById('m3dadd');
    if(b) b.onclick=function(){ if(opts.onAdd) opts.onAdd(d.id,!!d.isFree); };
  }

  function go(n,instant){
    if(!items.length)return;
    focus=(n%items.length+items.length)%items.length;
    if(MODE==='3d') target=focus*step;
    else{
      paint2d();
      var el=railEl.children[focus];
      if(el&&el.tagName==='IMG') railEl.scrollTo({left:el.offsetLeft-(railEl.clientWidth-el.clientWidth)/2,
        behavior:(instant||reduce)?'auto':'smooth'});
    }
    detail();
  }

  function sizeGL(){
    if(!renderer)return;
    var w=glCanvas.clientWidth,h=glCanvas.clientHeight; if(!w||!h)return;
    renderer.setSize(w,h,false); camera.aspect=w/h; camera.updateProjectionMatrix();
  }
  function startLoop(){
    if(looping||MODE!=='3d')return; looping=true;
    var t0=performance.now();
    (function loop(now){
      if(MODE!=='3d'||!renderer){looping=false;return}
      requestAnimationFrame(loop);
      if(auto&&items.length&&now-t0>2800){ target+=step; focus=(focus+1)%items.length; detail(); t0=now; }
      if(!dragging){
        var diff=target-rot;
        while(diff>Math.PI)diff-=Math.PI*2; while(diff<-Math.PI)diff+=Math.PI*2;
        if(Math.abs(diff)>.0004){ rot+=diff*(reduce?1:.10); layout(); }
      }
      renderer.render(scene,camera);
    })(performance.now());
  }

  var dragging=false,px=0,st=null;
  function bindStage(){
    glCanvas.addEventListener('pointerdown',function(e){
      if(MODE!=='3d')return; dragging=true; px=e.clientX; auto=false; glCanvas.setPointerCapture(e.pointerId);
    });
    glCanvas.addEventListener('pointermove',function(e){
      if(!dragging||MODE!=='3d')return;
      var dx=e.clientX-px; px=e.clientX; rot-=dx*.006; layout();
    });
    glCanvas.addEventListener('pointerup',function(){
      if(!dragging)return; dragging=false; go(Math.round(rot/step));
    });
    railEl.addEventListener('pointerdown',function(){auto=false});
    railEl.addEventListener('scroll',function(){
      clearTimeout(st);
      st=setTimeout(function(){
        if(MODE!=='2d'||!railEl.children.length)return;
        var r=railEl.getBoundingClientRect(),mid=r.left+r.width/2,best=focus,bd=1e9;
        for(var i=0;i<railEl.children.length;i++){
          var el=railEl.children[i]; if(el.tagName!=='IMG')continue;
          var b=el.getBoundingClientRect(),dd=Math.abs(b.left+b.width/2-mid);
          if(dd<bd){bd=dd;best=i}
        }
        if(best!==focus){focus=best;paint2d();detail()}
      },90);
    });
    document.getElementById('m3dprev').onclick=function(){auto=false;go(focus-1)};
    document.getElementById('m3dnext').onclick=function(){auto=false;go(focus+1)};
    addEventListener('resize',sizeGL);
  }

  /* customer.php calls this after an order so stock counts refresh */
  window.Menu3D.refresh=function(o){
    if(!host)return;
    opts.items=o.items; opts.comps=o.comps;
    rebuild();
  };
})();
