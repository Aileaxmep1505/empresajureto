<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Almacén · En vivo</title>
  <style>
    :root{
      /* Tema oscuro para TV (sala de espera) */
      --bg1:#090d15; --bg2:#0f1624;
      --card:#161d2b; --line:#28313f;
      --ink:#f3f6fb; --muted:#98a4b8;
      --blue:#5aa2ff; --blue-soft:rgba(90,162,255,.16);
      --green:#4ade80; --green-soft:rgba(74,222,128,.16);
      --amber:#fbbf24; --amber-soft:rgba(251,191,36,.16);
      --red:#f87171; --red-soft:rgba(248,113,113,.18);
      --shadow:0 14px 40px rgba(0,0,0,.4);
    }
    *{box-sizing:border-box;margin:0;padding:0}
    html,body{height:100%}
    body{
      font-family:-apple-system,BlinkMacSystemFont,"SF Pro Display","Segoe UI",system-ui,sans-serif;
      color:var(--ink);
      background:linear-gradient(160deg,var(--bg1),var(--bg2));
      -webkit-font-smoothing:antialiased;
      overflow:hidden;
    }
    .board{height:100vh;display:flex;flex-direction:column;padding:clamp(18px,2.2vw,40px);gap:clamp(14px,1.6vw,26px)}

    /* Header */
    header{display:flex;align-items:center;justify-content:space-between}
    .brand{display:flex;align-items:center;gap:16px}
    .brand .logo{width:clamp(44px,3.4vw,64px);height:clamp(44px,3.4vw,64px);border-radius:18px;
      background:linear-gradient(135deg,var(--blue),#0057d9);display:flex;align-items:center;justify-content:center;color:#fff;box-shadow:var(--shadow)}
    .brand .logo svg{width:56%;height:56%}
    .brand h1{font-size:clamp(20px,1.9vw,34px);font-weight:800;letter-spacing:-.02em}
    .brand p{color:var(--muted);font-weight:600;font-size:clamp(12px,.95vw,16px);margin-top:2px}
    .clock{display:flex;align-items:center;gap:18px}
    .clock .time{font-size:clamp(22px,2.3vw,40px);font-weight:800;letter-spacing:-.02em;font-variant-numeric:tabular-nums}
    .live{display:inline-flex;align-items:center;gap:8px;background:var(--card);border:1px solid var(--line);
      border-radius:999px;padding:8px 16px;font-weight:800;font-size:clamp(11px,.85vw,14px);color:var(--green);box-shadow:var(--shadow)}
    .live i{width:10px;height:10px;border-radius:50%;background:var(--green);box-shadow:0 0 0 0 rgba(22,163,74,.6);animation:pulse 1.8s infinite}
    @keyframes pulse{0%{box-shadow:0 0 0 0 rgba(22,163,74,.5)}70%{box-shadow:0 0 0 12px rgba(22,163,74,0)}100%{box-shadow:0 0 0 0 rgba(22,163,74,0)}}

    /* Tiles */
    .tiles{display:grid;grid-template-columns:repeat(3,1fr);gap:clamp(14px,1.6vw,26px)}
    .tile{background:var(--card);border:1px solid var(--line);border-radius:26px;padding:clamp(18px,1.8vw,32px);box-shadow:var(--shadow);
      position:relative;overflow:hidden;display:flex;flex-direction:column;justify-content:space-between}
    .tile .top{display:flex;align-items:center;gap:12px;color:var(--muted);font-weight:700;font-size:clamp(13px,1vw,18px);text-transform:uppercase;letter-spacing:.04em}
    .tile .ico{width:clamp(34px,2.6vw,48px);height:clamp(34px,2.6vw,48px);border-radius:14px;display:flex;align-items:center;justify-content:center}
    .tile .ico svg{width:56%;height:56%}
    .tile .big{font-size:clamp(56px,6vw,120px);font-weight:850;line-height:1;letter-spacing:-.03em;margin-top:10px}
    .tile .sub{margin-top:10px;color:var(--muted);font-weight:600;font-size:clamp(12px,1vw,17px)}
    .tile .sub b{color:var(--ink);font-weight:800}
    .t-blue .ico{background:var(--blue-soft);color:var(--blue)} .t-blue .big{color:var(--blue)}
    .t-amber .ico{background:var(--amber-soft);color:var(--amber)} .t-amber .big{color:var(--amber)}
    .t-green .ico{background:var(--green-soft);color:var(--green)} .t-green .big{color:var(--green)}

    /* Panels */
    .panels{flex:1;display:grid;grid-template-columns:1.15fr .85fr;gap:clamp(14px,1.6vw,26px);min-height:0}
    .panel{background:var(--card);border:1px solid var(--line);border-radius:26px;box-shadow:var(--shadow);display:flex;flex-direction:column;min-height:0;overflow:hidden}
    .panel h2{padding:clamp(16px,1.5vw,26px) clamp(18px,1.8vw,30px);font-size:clamp(15px,1.2vw,22px);font-weight:800;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:10px}
    .panel .list{flex:1;overflow:hidden;padding:6px clamp(10px,1vw,16px)}
    .row{display:flex;align-items:center;gap:16px;padding:clamp(12px,1.25vw,20px) clamp(10px,1vw,16px);border-bottom:1px solid var(--line)}
    .row:last-child{border-bottom:0}
    /* Actualización suave del número de un tile cuando cambia (sin parpadeo del resto) */
    .big.bump{animation:bump .5s ease}
    @keyframes bump{0%{transform:scale(1)}35%{transform:scale(1.12)}100%{transform:scale(1)}}
    .kind{font-size:clamp(10px,.72vw,12px);font-weight:800;padding:5px 10px;border-radius:8px;white-space:nowrap;letter-spacing:.02em}
    .k-blue{background:var(--blue-soft);color:var(--blue)} .k-amber{background:var(--amber-soft);color:var(--amber)} .k-green{background:var(--green-soft);color:var(--green)}
    .row .main{flex:1;min-width:0}
    .row .title{font-weight:800;font-size:clamp(16px,1.35vw,25px);letter-spacing:-.01em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .row .when{color:var(--muted);font-weight:600;font-size:clamp(11px,.85vw,14px);margin-top:2px}
    .pill{font-size:clamp(11px,.82vw,14px);font-weight:800;padding:6px 12px;border-radius:999px;white-space:nowrap}
    .p-ok{background:var(--green-soft);color:var(--green)} .p-warn{background:var(--amber-soft);color:var(--amber)} .p-bad{background:var(--red-soft);color:var(--red)}
    .qty{font-weight:850;font-size:clamp(16px,1.3vw,24px);font-variant-numeric:tabular-nums}
    .qty.in{color:var(--green)} .qty.out{color:var(--red)} .qty.move{color:var(--blue)}
    .empty{padding:40px;text-align:center;color:var(--muted);font-weight:600}
    .dir{width:30px;height:30px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .dir.in{background:var(--green-soft);color:var(--green)} .dir.out{background:var(--red-soft);color:var(--red)} .dir.move{background:var(--blue-soft);color:var(--blue)}
    .dir svg{width:60%;height:60%}
  </style>
</head>
<body>
  <div class="board">
    <header>
      <div class="brand">
        <div class="logo">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13V7a2 2 0 00-1-1.732l-7-4a2 2 0 00-2 0l-7 4A2 2 0 002 7v10a2 2 0 001 1.732l7 4a2 2 0 002 0l7-4A2 2 0 0020 17"/><path d="M7.5 4.21l9 5.19m-9 5.19l9-5.19M12 22V12"/></svg>
        </div>
        <div>
          <h1>Almacén Jureto</h1>
          <p>Estatus de operación en vivo</p>
        </div>
      </div>
      <div class="clock">
        <div class="time" id="clock">--:--:--</div>
        <span class="live"><i></i> EN VIVO</span>
      </div>
    </header>

    <section class="tiles">
      <div class="tile t-blue">
        <div class="top"><span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><path d="M3.27 6.96L12 12.01l8.73-5.05M12 22.08V12"/></svg></span> Recepciones</div>
        <div class="big" id="recPending">0</div>
        <div class="sub">recibiendo ahora · <b id="recToday">0</b> hoy</div>
      </div>
      <div class="tile t-amber">
        <div class="top"><span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg></span> Picking</div>
        <div class="big" id="pickProgress">0</div>
        <div class="sub">en proceso · <b id="pickDone">0</b> completadas</div>
      </div>
      <div class="tile t-green">
        <div class="top"><span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></span> Embarques</div>
        <div class="big" id="shipLoading">0</div>
        <div class="sub">cargando · <b id="shipReady">0</b> listos</div>
      </div>
    </section>

    <section class="panels">
      <div class="panel">
        <h2>Actividad reciente</h2>
        <div class="list" id="activity"><div class="empty">Cargando…</div></div>
      </div>
      <div class="panel">
        <h2>Movimientos</h2>
        <div class="list" id="movements"><div class="empty">Cargando…</div></div>
      </div>
    </section>
  </div>

  <script>
  (function(){
    var DATA_URL = @json(route('web.board.data'));
    var POLL = 8000;

    function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]); }); }
    function el(id){ return document.getElementById(id); }
    // Actualiza el número solo si cambió; a los tiles grandes les da un "bump" sutil.
    function setNum(id, v){
      var n=el(id); if(!n) return;
      var nv=String(v==null?0:v);
      if(n.textContent===nv) return;
      n.textContent=nv;
      if(n.classList.contains('big')){ n.classList.remove('bump'); void n.offsetWidth; n.classList.add('bump'); }
    }
    var lastSig={};
    function jsig(v){ try{ return JSON.stringify(v); }catch(e){ return String(v); } }

    function clock(){
      var d=new Date();
      var t=d.toLocaleTimeString('es-MX',{hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false});
      var c=el('clock'); if(c) c.textContent=t;
    }
    setInterval(clock,1000); clock();

    var PILL={ok:'p-ok',warn:'p-warn',bad:'p-bad'};
    var PICK_LABEL={pending:{label:'En cola',tone:'warn'},in_progress:{label:'Surtiendo',tone:'warn'},completed:{label:'Completada',tone:'ok'},cancelled:{label:'Cancelada',tone:'bad'}};

    function statusPill(st){
      if(!st) return '';
      var tone=st.tone||'warn', label=st.label||'';
      return '<span class="pill '+(PILL[tone]||'p-warn')+'">'+esc(label)+'</span>';
    }

    function activityRow(kind, kindClass, title, pillHtml, when){
      return '<div class="row">'
        + '<span class="kind '+kindClass+'">'+kind+'</span>'
        + '<div class="main"><div class="title">'+esc(title)+'</div><div class="when">'+esc(when)+'</div></div>'
        + pillHtml
        + '</div>';
    }

    var ARROW_IN='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>';
    var ARROW_OUT='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>';
    var ARROW_MOVE='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h16M14 6l6 6-6 6"/></svg>';

    function render(d){
      setNum('recPending', d.receptions.pending);
      setNum('recToday', d.receptions.today);
      setNum('pickProgress', d.picking.in_progress);
      setNum('pickDone', d.picking.completed);
      setNum('shipLoading', d.shipping.loading);
      setNum('shipReady', d.shipping.ready);

      // Actividad combinada — SOLO se re-dibuja si cambió (evita el parpadeo de "recarga").
      var actSig=jsig([d.receptions.recent,d.shipping.recent,d.picking.recent]);
      if(actSig!==lastSig.act){
        lastSig.act=actSig;
        var acts=[];
        (d.receptions.recent||[]).forEach(function(r){ acts.push(activityRow('RECEPCIÓN','k-blue', r.title, statusPill(r.status), r.when)); });
        (d.shipping.recent||[]).forEach(function(r){ acts.push(activityRow('EMBARQUE','k-green', r.title + (r.progress?(' · '+r.progress+'%'):''), statusPill(r.status), r.when)); });
        (d.picking.recent||[]).forEach(function(r){ var pl=PICK_LABEL[r.status]||{label:r.status,tone:'warn'}; acts.push(activityRow('PICKING','k-amber', r.title, statusPill(pl), r.when)); });
        el('activity').innerHTML = acts.length ? acts.slice(0,9).join('') : '<div class="empty">Sin actividad reciente.</div>';
      }

      // Movimientos — igual, solo se re-dibuja si cambió.
      var movSig=jsig(d.movements);
      if(movSig!==lastSig.mov){
        lastSig.mov=movSig;
        var mov=(d.movements||[]).map(function(m){
          var dir=(m.type&&m.type.dir)||'move';
          var arrow = dir==='in'?ARROW_IN : dir==='out'?ARROW_OUT : ARROW_MOVE;
          var sign = dir==='in'?'+' : dir==='out'?'−' : '';
          return '<div class="row">'
            + '<span class="dir '+dir+'">'+arrow+'</span>'
            + '<div class="main"><div class="title">'+esc(m.product)+'</div><div class="when">'+esc((m.type&&m.type.label)||'')+' · '+esc(m.when)+'</div></div>'
            + '<span class="qty '+dir+'">'+sign+Math.abs(m.qty||0)+'</span>'
            + '</div>';
        });
        el('movements').innerHTML = mov.length ? mov.join('') : '<div class="empty">Sin movimientos recientes.</div>';
      }
    }

    async function load(force){
      // La primera carga es forzada; el polling recurrente se pausa si la pestaña
      // está en segundo plano (para no consumir servidor con la pantalla apagada).
      if(!force && document.hidden) return;
      try{
        var res=await fetch(DATA_URL,{headers:{'Accept':'application/json'},cache:'no-store'});
        if(!res.ok) return;
        var d=await res.json();
        if(d && d.ok) render(d);
      }catch(e){ /* silencioso */ }
    }

    load(true);
    setInterval(load, POLL);
    // Al volver a mostrar la pestaña, refresca de inmediato.
    document.addEventListener('visibilitychange', function(){ if(!document.hidden) load(); });
  })();
  </script>
</body>
</html>
