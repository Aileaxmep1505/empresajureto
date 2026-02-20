@extends('layouts.app')
@section('title','Supervisor · Ruta')

@section('content')
<div id="rp-supervisor" class="container-fluid p-0">
  <style>
    #rp-supervisor{
      --ink:#0e1726; --muted:#6b7280; --line:#e5e7eb; --bg:#f6f8fb; --card:#fff;
      --brand:#a6d3ff; --mint:#bff3e7; --shadow:0 16px 40px rgba(2,8,23,.08);
      --danger:#ef4444; --warn:#f59e0b; --ok:#22c55e;
      color:var(--ink);
      background:linear-gradient(180deg,#fbfdff,#f6f8fb);
      min-height:calc(100vh - 56px);
      padding:16px 0;
    }
    #rp-supervisor *{box-sizing:border-box}
    #rp-supervisor a{color:inherit; text-decoration:none}
    #rp-supervisor a:hover{text-decoration:underline}

    .wrap{max-width:1400px; margin:0 auto; padding:0 16px}
    .grid{display:grid; gap:14px}
    @media(min-width:992px){ .grid{grid-template-columns:400px 1fr} }

    .cardx{
      background:rgba(255,255,255,.92);
      border:1px solid var(--line);
      border-radius:18px;
      box-shadow:0 12px 28px rgba(2,8,23,.06);
      overflow:hidden;
      backdrop-filter:saturate(1.1) blur(6px);
    }
    .hd{padding:12px 14px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center; gap:10px}
    .bd{padding:14px}

    .kpis{display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px}
    .kpi{border:1px solid var(--line); border-radius:14px; padding:10px 12px; background:#fff}
    .kpi .l{font-size:.74rem; color:var(--muted); text-transform:uppercase; letter-spacing:.04em}
    .kpi .v{font-weight:900; font-size:1.15rem}

    #mapSup{height:calc(100vh - 160px); min-height:560px; border-radius:14px; border:1px solid var(--line); overflow:hidden; background:#e9eef8}

    .list{list-style:none; margin:0; padding:0}
    .rowx{border:1px solid var(--line); border-radius:14px; padding:10px 12px; background:#fff; display:flex; justify-content:space-between; gap:10px; align-items:flex-start}
    .rowx + .rowx{margin-top:10px}

    .badge{border-radius:999px; padding:.16rem .6rem; font-weight:900; font-size:.75rem; border:1px solid var(--line); background:#f3f4f6}
    .badge.done{background:#d7f9e1; border-color:#a8f0bd; color:#064e3b}
    .badge.pending{background:#fff; color:#111827}
    .badge.off{background:#fee2e2; border-color:#fecaca; color:#7f1d1d}
    .badge.warn{background:#ffedd5; border-color:#fed7aa; color:#7c2d12}

    .muted{color:var(--muted); font-size:.88rem}
    .small{font-size:.86rem}

    .dot{width:10px;height:10px;border-radius:999px;background:#94a3b8; display:inline-block; margin-right:6px}
    .dot.live{background:var(--ok)}
    .dot.off{background:var(--danger)}
    .dot.warn{background:var(--warn)}

    .topline{display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:14px}
    .pill{
      border:1px solid var(--line);
      background:#fff;
      border-radius:999px;
      padding:.42rem .7rem;
      font-weight:900;
      display:inline-flex;
      align-items:center;
      gap:.45rem;
      box-shadow:0 8px 22px rgba(2,8,23,.05);
    }
    .pill.soft{background:linear-gradient(180deg,#ecfffa,#fff); border-color:#c9f1e8}
    .pill.brand{background:linear-gradient(180deg,#eaf4ff,#fff); border-color:#d6e6ff}
    .pill.danger{background:linear-gradient(180deg,#fff1f2,#fff); border-color:#fecdd3}
    .pill.warn{background:linear-gradient(180deg,#fff7ed,#fff); border-color:#fed7aa}

    .meta{
      border:1px solid var(--line);
      background:#fff;
      border-radius:14px;
      padding:10px 12px;
      display:grid;
      gap:8px;
      margin-bottom:12px;
    }
    .meta .ttl{font-weight:900}
    .meta .grid2{display:grid; grid-template-columns:1fr 1fr; gap:10px}
    .kv{border:1px dashed rgba(15,23,42,.14); border-radius:12px; padding:8px 10px}
    .kv .k{font-size:.72rem; color:var(--muted); text-transform:uppercase; letter-spacing:.04em}
    .kv .v{font-weight:900}
    .kv .v.mutedv{font-weight:800; color:#334155}

    .hint{
      font-size:.84rem;
      color:rgba(2,8,23,.65);
      border-left:3px solid rgba(59,130,246,.35);
      padding-left:10px;
      margin-top:8px;
      line-height:1.25rem;
    }
  </style>

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <div class="wrap">
    <div class="topline">
      <a href="{{ route('routes.index') }}" class="pill">← Volver</a>

      <div class="pill soft" id="livePill">
        <span class="dot" id="liveDot"></span>
        <span id="liveTxt">Conectando…</span>
      </div>

      <div class="pill brand">Ruta: <strong>{{ $routePlan->name ?? ('#'.$routePlan->id) }}</strong></div>
      <div class="pill">Chofer: <strong>{{ $routePlan->driver?->name ?? '—' }}</strong></div>

      <div class="pill" id="lastSeenPill" style="display:none">
        Última señal: <strong id="lastSeenTxt">—</strong>
      </div>
    </div>

    <div class="grid">
      <div class="cardx">
        <div class="hd">
          <div style="font-weight:900">Progreso</div>
          <small class="muted" id="serverTime">—</small>
        </div>

        <div class="bd">
          <div class="meta">
            <div class="ttl">Estado del chofer</div>
            <div class="grid2">
              <div class="kv">
                <div class="k">Ubicación</div>
                <div class="v" id="posTxt">—</div>
                <div class="muted small" id="posHint">—</div>
              </div>
              <div class="kv">
                <div class="k">Precisión</div>
                <div class="v" id="accTxt">—</div>
                <div class="muted small" id="accHint">—</div>
              </div>

              <div class="kv">
                <div class="k">Velocidad</div>
                <div class="v" id="speedTxt">—</div>
                <div class="muted small" id="headingTxt">—</div>
              </div>

              <div class="kv">
                <div class="k">App / Red / Batería</div>
                <div class="v mutedv" id="deviceTxt">—</div>
                <div class="muted small" id="deviceHint">—</div>
              </div>
            </div>

            <div class="hint">
              Tip: si ves <strong>accuracy</strong> alto (ej. 150–300m), Android/iOS están dando ubicación aproximada.
              Con <strong>snap to road</strong> el marcador se “pega” a la calle para verse mejor.
            </div>
          </div>

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
          <small class="muted">Actualiza cada 5s</small>
        </div>
        <div class="bd">
          <div id="mapSup"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const PLAN_ID  = @json($routePlan->id);
  const POLL_URL = @json(route('api.supervisor.routes.poll', $routePlan));

  let map, driverMarker=null, accuracyCircle=null;
  let stopMarkers=[];
  let poly=null;
  let didFitOnce=false;

  function toNum(v){ const n=Number(v); return Number.isFinite(n)?n:null; }
  function isValid(lat,lng){
    if(lat==null||lng==null) return false;
    if(Math.abs(lat)<0.000001 && Math.abs(lng)<0.000001) return false;
    return Math.abs(lat)<=90 && Math.abs(lng)<=180;
  }

  function fmtAgo(seconds){
    if(seconds==null) return '—';
    const s=Math.max(0, Math.floor(seconds));
    if(s < 60) return `${s}s`;
    const m=Math.floor(s/60);
    if(m < 60) return `${m}m`;
    const h=Math.floor(m/60);
    if(h < 24) return `${h}h ${m%60}m`;
    const d=Math.floor(h/24);
    return `${d}d ${h%24}h`;
  }

  function initMap(){
    map = L.map('mapSup',{ zoomSnap:0.5 }).setView([19.4326,-99.1332], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{ attribution:'© OpenStreetMap' }).addTo(map);
    setTimeout(()=>{ try{ map.invalidateSize(true); }catch(e){} }, 200);
    window.addEventListener('resize', ()=>{ try{ map.invalidateSize(true); }catch(e){} });
  }

  function renderStops(stops){
    const ul=document.getElementById('stopsList');
    ul.innerHTML='';

    (stops||[]).forEach((s, idx)=>{
      const badge = s.status==='done'
        ? `<span class="badge done">hecho</span>`
        : `<span class="badge pending">pendiente</span>`;

      const lat=toNum(s.lat), lng=toNum(s.lng);
      const coord = isValid(lat,lng) ? `(${lat.toFixed(5)}, ${lng.toFixed(5)})` : '(—)';

      ul.insertAdjacentHTML('beforeend', `
        <li class="rowx">
          <div>
            <div style="font-weight:900">#${idx+1}. ${s.name || 'Punto'}</div>
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

  function setLive(state, text){
    const dot=document.getElementById('liveDot');
    const t=document.getElementById('liveTxt');
    const pill=document.getElementById('livePill');

    dot.classList.remove('live','off','warn');
    pill.classList.remove('danger','warn');
    if(state === 'online'){
      dot.classList.add('live');
      t.textContent = text || 'En vivo';
    } else if(state === 'warn'){
      dot.classList.add('warn');
      pill.classList.add('warn');
      t.textContent = text || 'Señal débil';
    } else {
      dot.classList.add('off');
      pill.classList.add('danger');
      t.textContent = text || 'Sin conexión';
    }
  }

  function setLastSeen(presence){
    const pill=document.getElementById('lastSeenPill');
    const txt=document.getElementById('lastSeenTxt');

    if(!presence){
      pill.style.display='none';
      return;
    }
    pill.style.display='inline-flex';

    const age = presence.stale_seconds ?? null;
    if(presence.state === 'online'){
      txt.textContent = `hace ${fmtAgo(age)}`;
    }else{
      txt.textContent = `hace ${fmtAgo(age)} (desconectado)`;
    }
  }

  function renderDriverMeta(driver){
    // Soporta ambos formatos:
    // - data.driver.last_position (tu poll actual)
    // - data.driver_last + data.presence (live mejorado)
    const lp = driver?.last_position || driver?.driver_last || null;

    // preferir snap si existe
    const rawLat = toNum(lp?.lat);
    const rawLng = toNum(lp?.lng);
    const snapLat = toNum(lp?.snap_lat);
    const snapLng = toNum(lp?.snap_lng);

    const lat = isValid(snapLat, snapLng) ? snapLat : rawLat;
    const lng = isValid(snapLat, snapLng) ? snapLng : rawLng;

    const acc = toNum(lp?.accuracy);
    const speed = toNum(lp?.speed);
    const heading = toNum(lp?.heading);

    const posTxt = document.getElementById('posTxt');
    const posHint = document.getElementById('posHint');
    const accTxt = document.getElementById('accTxt');
    const accHint = document.getElementById('accHint');
    const speedTxt = document.getElementById('speedTxt');
    const headingTxt = document.getElementById('headingTxt');
    const deviceTxt = document.getElementById('deviceTxt');
    const deviceHint = document.getElementById('deviceHint');

    if(isValid(lat,lng)){
      posTxt.textContent = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
      if(isValid(snapLat, snapLng)){
        const dm = lp?.snap_distance_m != null ? `${lp.snap_distance_m}m` : '—';
        posHint.textContent = `snap: sí • distancia a calle: ${dm}`;
      }else{
        posHint.textContent = `snap: no`;
      }
    } else {
      posTxt.textContent = '—';
      posHint.textContent = 'Sin coordenadas válidas';
    }

    accTxt.textContent = acc != null ? `${Math.round(acc)} m` : '—';
    if(acc != null){
      accHint.textContent = acc <= 20 ? 'Excelente'
        : acc <= 60 ? 'Buena'
        : acc <= 120 ? 'Regular'
        : 'Mala (GPS aproximado)';
    } else {
      accHint.textContent = '—';
    }

    const sp = speed != null ? (speed * 3.6) : null; // m/s -> km/h (si tú guardas m/s)
    speedTxt.textContent = sp != null ? `${Math.round(sp)} km/h` : '—';
    headingTxt.textContent = heading != null ? `rumbo: ${Math.round(heading)}°` : '—';

    const appState = lp?.app_state || '—';
    const net = lp?.network || '—';
    const bat = (lp?.battery != null) ? `${lp.battery}%` : '—';
    deviceTxt.textContent = `${appState} • ${net} • ${bat}`;

    const cap = lp?.captured_at || null;
    const rec = lp?.received_at || null;
    deviceHint.textContent = (rec ? `recibido: ${rec}` : (cap ? `capturado: ${cap}` : '—'));
  }

  function renderMap(stops, driver){
    clearStopMarkers();

    const pts=[];
    (stops||[]).forEach((s, i)=>{
      const lat=toNum(s.lat), lng=toNum(s.lng);
      if(!isValid(lat,lng)) return;
      pts.push([lat,lng]);

      const html = `<div style="background:#111827;color:#fff;font-weight:900;font-size:.78rem;border-radius:10px 10px 2px 10px;padding:.15rem .45rem;border:2px solid #fff;box-shadow:0 6px 14px rgba(2,8,23,.25)">${i+1}</div>`;
      const icon = L.divIcon({ html, className:'', iconAnchor:[10,18] });

      const m=L.marker([lat,lng],{icon}).addTo(map);
      m.bindPopup((s.name||'Punto') + (s.status==='done'?' • hecho':''));
      stopMarkers.push(m);
    });

    const lp = driver?.last_position || driver?.driver_last || null;

    const rawLat = toNum(lp?.lat);
    const rawLng = toNum(lp?.lng);
    const snapLat = toNum(lp?.snap_lat);
    const snapLng = toNum(lp?.snap_lng);

    const dlat = isValid(snapLat, snapLng) ? snapLat : rawLat;
    const dlng = isValid(snapLat, snapLng) ? snapLng : rawLng;

    // marcador chofer + círculo de precisión
    if(isValid(dlat,dlng)){
      const acc = toNum(lp?.accuracy);

      if(driverMarker) { try{ map.removeLayer(driverMarker); }catch(e){} driverMarker=null; }
      driverMarker = L.circleMarker([dlat,dlng],{
        radius:9,
        color:'#111827',
        fillColor:'#22c55e',
        fillOpacity:.9
      }).addTo(map);

      driverMarker.bindTooltip(`Chofer • ${dlat.toFixed(5)}, ${dlng.toFixed(5)}`,{permanent:false});

      if(accuracyCircle){ try{ map.removeLayer(accuracyCircle); }catch(e){} accuracyCircle=null; }
      if(acc != null && acc > 5 && acc < 1000){
        accuracyCircle = L.circle([dlat,dlng],{
          radius: acc,
          weight:1,
          opacity:.4,
          fillOpacity:.08
        }).addTo(map);
      }
    } else {
      if(driverMarker) { try{ map.removeLayer(driverMarker); }catch(e){} driverMarker=null; }
      if(accuracyCircle){ try{ map.removeLayer(accuracyCircle); }catch(e){} accuracyCircle=null; }
    }

    if(poly) { try{ map.removeLayer(poly); }catch(e){} poly=null; }
    if(pts.length >= 2){
      poly = L.polyline(pts, { weight:4, opacity:.7 }).addTo(map);
    }

    // Fit bounds inteligente:
    // - 1era vez: sí
    // - después: solo si el chofer queda fuera del viewport
    const layers=[];
    if(poly) layers.push(poly);
    if(driverMarker) layers.push(driverMarker);
    stopMarkers.forEach(m=>layers.push(m));

    if(layers.length){
      try{
        if(!didFitOnce){
          const grp=L.featureGroup(layers);
          map.fitBounds(grp.getBounds().pad(0.18));
          didFitOnce=true;
        } else if(driverMarker){
          const b = map.getBounds();
          const ll = driverMarker.getLatLng();
          if(!b.contains(ll)){
            map.panTo(ll, { animate:true, duration:0.5 });
          }
        }
      }catch(e){}
    }
  }

  function fallbackPresenceFromDriver(driver, serverIso){
    // Si tu poll viejo no manda presence, calculamos con server_time y captured_at
    try{
      const lp = driver?.last_position || null;
      const t = lp?.received_at || lp?.captured_at || null;
      if(!t || !serverIso) return null;
      const s = Date.parse(serverIso);
      const l = Date.parse(t);
      if(!Number.isFinite(s) || !Number.isFinite(l)) return null;
      const age = Math.max(0, Math.floor((s - l)/1000));
      const online = age <= 120;
      return {
        state: online ? 'online' : 'offline',
        stale_seconds: age,
        warn: age >= 45,
        last_seen_at: t,
        disconnected_at: online ? null : t,
      };
    }catch(e){
      return null;
    }
  }

  async function poll(){
    try{
      const res = await fetch(POLL_URL, { headers:{'Accept':'application/json'}, credentials:'include' });
      const data = await res.json().catch(()=>null);

      if(!res.ok || !data){
        setLive('offline', 'Error API ('+res.status+')');
        return;
      }

      const serverTime = data.server_time || null;
      document.getElementById('serverTime').textContent = serverTime || '—';

      // KPIs
      document.getElementById('kTotal').textContent = data.kpis?.total ?? '—';
      document.getElementById('kDone').textContent = data.kpis?.done ?? '—';
      document.getElementById('kPending').textContent = data.kpis?.pending ?? '—';

      // Presence: nuevo (presence) o fallback calculado
      let presence = data.presence || null;
      if(!presence){
        presence = fallbackPresenceFromDriver(data.driver || {}, serverTime);
      }

      if(presence){
        if(presence.state === 'online'){
          setLive(presence.warn ? 'warn' : 'online', presence.warn ? 'Señal débil' : 'En vivo');
        }else{
          setLive('offline', 'Sin conexión');
        }
        setLastSeen(presence);
      } else {
        setLive('online', 'En vivo');
        document.getElementById('lastSeenPill').style.display='none';
      }

      renderStops(data.stops || []);

      // driver meta + mapa
      renderDriverMeta({
        last_position: data.driver?.last_position,
        driver_last: data.driver_last,
      });

      renderMap(data.stops || [], {
        last_position: data.driver?.last_position,
        driver_last: data.driver_last,
      });

    }catch(e){
      setLive('offline', 'Sin conexión');
    }
  }

  initMap();
  poll();
  setInterval(poll, 5000);
</script>
@endsection