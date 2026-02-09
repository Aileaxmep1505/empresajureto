@extends('layouts.app')
@section('title','Supervisor · Ruta')

@section('content')
<div id="rp-supervisor" class="container-fluid p-0">
  <style>
    #rp-supervisor{
      --ink:#0e1726; --muted:#6b7280; --line:#e5e7eb; --bg:#f6f8fb; --card:#fff;
      --brand:#6ea8fe; --ok:#86efac; --red:#fecaca;
      color:var(--ink); background:linear-gradient(180deg,#fbfdff,#f6f8fb);
      min-height:calc(100vh - 56px);
    }
    #rp-supervisor *{box-sizing:border-box}
    .wrap{max-width:1400px; margin:16px auto; padding:0 16px}
    .grid{display:grid; gap:14px}
    @media(min-width:992px){ .grid{grid-template-columns:380px 1fr} }

    .cardx{background:var(--card); border:1px solid var(--line); border-radius:16px; box-shadow:0 16px 40px rgba(2,8,23,.08); overflow:hidden}
    .hd{padding:12px 14px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center}
    .bd{padding:14px}

    .kpis{display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px}
    .kpi{border:1px solid var(--line); border-radius:14px; padding:10px 12px; background:#fff}
    .kpi .l{font-size:.74rem; color:var(--muted); text-transform:uppercase; letter-spacing:.04em}
    .kpi .v{font-weight:900; font-size:1.15rem}

    #mapSup{height:calc(100vh - 120px); min-height:560px; border-radius:14px; border:1px solid var(--line); overflow:hidden; background:#e9eef8}

    .list{list-style:none; margin:0; padding:0}
    .rowx{border:1px solid var(--line); border-radius:14px; padding:10px 12px; background:#fff; display:flex; justify-content:space-between; gap:10px; align-items:flex-start}
    .rowx + .rowx{margin-top:10px}
    .badge{border-radius:999px; padding:.16rem .6rem; font-weight:800; font-size:.75rem; border:1px solid var(--line); background:#f3f4f6}
    .badge.done{background:var(--ok); border-color:#bbf7d0; color:#064e3b}
    .badge.pending{background:#fff; color:#111827}
    .muted{color:var(--muted); font-size:.88rem}
    .small{font-size:.86rem}

    .dot{width:10px;height:10px;border-radius:999px;background:#94a3b8; display:inline-block; margin-right:6px}
    .dot.live{background:#22c55e}
    .topline{display:flex; align-items:center; gap:10px; flex-wrap:wrap}
    .pill{border:1px solid var(--line); background:#fff; border-radius:999px; padding:.35rem .65rem; font-weight:800}
  </style>

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <div class="wrap">
    <div class="topline mb-3">
      <a href="{{ route('routes.index') }}" class="pill">← Volver</a>
      <div class="pill"><span class="dot" id="liveDot"></span> <span id="liveTxt">Conectando…</span></div>
      <div class="pill">Ruta: <strong>{{ $routePlan->name ?? ('#'.$routePlan->id) }}</strong></div>
      <div class="pill">Chofer: <strong>{{ $routePlan->driver?->name ?? '—' }}</strong></div>
    </div>

    <div class="grid">
      <div class="cardx">
        <div class="hd">
          <div style="font-weight:900">Progreso</div>
          <small class="muted" id="serverTime">—</small>
        </div>
        <div class="bd">
          <div class="kpis mb-3">
            <div class="kpi"><div class="l">Total</div><div class="v" id="kTotal">—</div></div>
            <div class="kpi"><div class="l">Hechos</div><div class="v" id="kDone">—</div></div>
            <div class="kpi"><div class="l">Pendientes</div><div class="v" id="kPending">—</div></div>
          </div>

          <div class="muted small mb-2">Paradas</div>
          <ul class="list" id="stopsList"></ul>
        </div>
      </div>

      <div class="cardx">
        <div class="hd">
          <div style="font-weight:900">Mapa (tiempo real)</div>
          <small class="muted">Se actualiza automáticamente</small>
        </div>
        <div class="bd">
          <div id="mapSup"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const PLAN_ID = @json($routePlan->id);
  const POLL_URL = @json(route('api.supervisor.routes.poll', $routePlan));

  let map, base, driverMarker=null;
  let stopMarkers=[];
  let poly=null;

  // ✅ Evita que el mapa “brinque” en cada poll
  let didFit = false;

  function toNum(v){ const n=Number(v); return Number.isFinite(n)?n:null; }
  function isValid(lat,lng){
    if(lat==null||lng==null) return false;
    if(Math.abs(lat)<0.000001 && Math.abs(lng)<0.000001) return false;
    return Math.abs(lat)<=90 && Math.abs(lng)<=180;
  }

  // ✅ Orden consistente (usa sequence_index si existe)
  function sortStops(stops){
    return (stops||[]).slice().sort((a,b)=>
      (a.sequence_index ?? 999999) - (b.sequence_index ?? 999999) || ((a.id||0) - (b.id||0))
    );
  }

  function numForStop(s, fallback){
    const n = (s && s.sequence_index != null && Number.isFinite(Number(s.sequence_index)))
      ? Number(s.sequence_index)
      : fallback;
    return n;
  }

  function fmtTimeAgo(iso){
    if(!iso) return '';
    const t = new Date(iso).getTime();
    if(!Number.isFinite(t)) return '';
    const diff = Math.max(0, Date.now() - t);
    const sec = Math.round(diff/1000);
    if(sec < 60) return `hace ${sec}s`;
    const min = Math.round(sec/60);
    if(min < 60) return `hace ${min}m`;
    const hr = Math.round(min/60);
    return `hace ${hr}h`;
  }

  function initMap(){
    map = L.map('mapSup',{ zoomSnap:0.5 }).setView([19.4326,-99.1332], 11);
    base = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{ attribution:'© OpenStreetMap' }).addTo(map);
    setTimeout(()=>{ try{ map.invalidateSize(true); }catch(e){} }, 200);
    window.addEventListener('resize', ()=>{ try{ map.invalidateSize(true); }catch(e){} });
  }

  function renderStops(stops){
    stops = sortStops(stops);

    const ul=document.getElementById('stopsList');
    ul.innerHTML='';

    stops.forEach((s, idx)=>{
      const n = numForStop(s, idx+1);

      const badge = s.status==='done'
        ? `<span class="badge done">hecho</span>`
        : `<span class="badge pending">pendiente</span>`;

      const lat=toNum(s.lat), lng=toNum(s.lng);
      const coord = isValid(lat,lng) ? `(${lat.toFixed(5)}, ${lng.toFixed(5)})` : '(—)';

      ul.insertAdjacentHTML('beforeend', `
        <li class="rowx">
          <div>
            <div style="font-weight:900">#${n}. ${s.name || 'Punto'}</div>
            <div class="muted small">${coord}</div>
            ${s.done_at ? `<div class="muted small">done_at: ${s.done_at}</div>` : ``}
          </div>
          <div>${badge}</div>
        </li>
      `);
    });
  }

  function clearStopMarkers(){
    stopMarkers.forEach(m=>{ try{ map.removeLayer(m); }catch(e){} });
    stopMarkers=[];
  }

  function renderMap(stops, driver){
    stops = sortStops(stops);

    clearStopMarkers();
    const pts=[];

    (stops||[]).forEach((s, i)=>{
      const n = numForStop(s, i+1);

      const lat=toNum(s.lat), lng=toNum(s.lng);
      if(!isValid(lat,lng)) return;
      pts.push([lat,lng]);

      const html = `<div style="background:#2563eb;color:#fff;font-weight:900;font-size:.78rem;border-radius:10px 10px 2px 10px;padding:.15rem .45rem;border:2px solid #fff;box-shadow:0 6px 14px rgba(2,8,23,.25)">${n}</div>`;
      const icon = L.divIcon({ html, className:'', iconAnchor:[10,18] });

      const m=L.marker([lat,lng],{icon}).addTo(map);
      m.bindPopup((s.name||'Punto') + (s.status==='done'?' • hecho':''طور));
      stopMarkers.push(m);
    });

    const dlat=toNum(driver?.last_position?.lat), dlng=toNum(driver?.last_position?.lng);
    const cap = driver?.last_position?.captured_at || null;

    if(isValid(dlat,dlng)){
      if(driverMarker) map.removeLayer(driverMarker);
      driverMarker = L.circleMarker([dlat,dlng],{ radius:9, color:'#111827', fillColor:'#22c55e', fillOpacity:.9 }).addTo(map);

      const ago = fmtTimeAgo(cap);
      const tip = `Chofer • ${dlat.toFixed(5)}, ${dlng.toFixed(5)}${ago ? ' • ' + ago : ''}`;
      driverMarker.bindTooltip(tip,{permanent:false});
    }

    if(poly) { try{ map.removeLayer(poly); }catch(e){} poly=null; }
    if(pts.length >= 2){
      poly = L.polyline(pts, { weight:4, opacity:.7 }).addTo(map);
    }

    // ✅ fitBounds SOLO la primera vez (evita brincos)
    const layers=[];
    if(poly) layers.push(poly);
    if(driverMarker) layers.push(driverMarker);
    stopMarkers.forEach(m=>layers.push(m));

    if(!didFit && layers.length){
      didFit = true;
      try{
        const grp=L.featureGroup(layers);
        map.fitBounds(grp.getBounds().pad(0.18));
      }catch(e){}
    }
  }

  function setLive(ok, text){
    const dot=document.getElementById('liveDot');
    const t=document.getElementById('liveTxt');
    dot.classList.toggle('live', !!ok);
    t.textContent = text || (ok ? 'En vivo' : 'Sin conexión');
  }

  async function poll(){
    try{
      const res = await fetch(POLL_URL, { headers:{'Accept':'application/json'}, credentials:'include' });
      const data = await res.json().catch(()=>null);

      if(!res.ok || !data){
        setLive(false, 'Error API ('+res.status+')');
        return;
      }

      setLive(true, 'En vivo');
      document.getElementById('serverTime').textContent = data.server_time || '—';

      document.getElementById('kTotal').textContent = data.kpis?.total ?? '—';
      document.getElementById('kDone').textContent = data.kpis?.done ?? '—';
      document.getElementById('kPending').textContent = data.kpis?.pending ?? '—';

      renderStops(data.stops || []);
      renderMap(data.stops || [], data.driver || {});
    }catch(e){
      setLive(false, 'Sin conexión');
    }
  }

  initMap();
  poll();
  setInterval(poll, 5000); // supervisor cada 5s
</script>
@endsection
