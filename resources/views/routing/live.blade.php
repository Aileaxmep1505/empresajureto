{{-- resources/views/routing/live.blade.php --}}
@extends('layouts.app')
@section('title','Planificador de Rutas')

@section('content')
<div id="routing">
  <style>
    :root{
      --ink:#0e1726; --muted:#6b7280; --line:#e5e7eb; --surface:#ffffff;
      --bg:#f6f9fc; --accent:#a3d5ff; --accent-ink:#0b1220;
      --ok:#16a34a; --warn:#eab308; --bad:#ef4444;
      --radius:16px; --radius-sm:12px;
      --shadow:0 16px 40px rgba(2,8,23,.08); --shadow-sm:0 8px 22px rgba(2,8,23,.06);
    }
    #routing{
      font-family: system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial;
      color:var(--ink);
      min-height: calc(100vh - 80px);
      background: radial-gradient(800px 400px at 20% -120px, #fff, transparent 60%), var(--bg);
      display:grid; grid-template-columns: 420px 1fr; gap:16px; padding:16px;
    }
    @media (max-width: 1024px){
      #routing{grid-template-columns: 1fr; grid-template-rows: 460px 1fr}
    }

    /* Panel lateral */
    .panel{
      background: var(--surface);
      border: 1px solid var(--line);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      overflow: hidden;
      display:flex; flex-direction:column;
    }
    .panel .head{
      display:flex; align-items:center; justify-content:space-between;
      padding:14px 16px; border-bottom:1px solid var(--line);
      background: linear-gradient(0deg,#fff, #fafcff);
    }
    .panel .head .title{
      font-weight:800; letter-spacing:.2px;
      display:flex; gap:10px; align-items:center;
    }
    .panel .body{ padding:12px 16px; overflow:auto; }

    /* Inputs / botones */
    .field{display:flex; gap:8px; margin-bottom:12px}
    .field input, .field select{
      flex:1; border:1px solid var(--line); border-radius:12px; padding:10px 12px; outline:none;
      background:#fff;
    }
    .btn{
      display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:12px;
      border:1px solid var(--line); background:#fff; cursor:pointer; transition:transform .06s ease;
    }
    .btn:active{ transform: translateY(1px) }
    .btn.brand{ background:var(--accent); color:var(--accent-ink); border-color:transparent; font-weight:700 }
    .btn.ghost{ background:#fff }
    .btn.icon{ padding:8px 10px; border-radius:10px }

    /* Tags / chips */
    .chip{
      display:inline-flex; align-items:center; gap:8px;
      padding:6px 10px; background:#f7fbff; border:1px solid var(--line); border-radius:999px;
      font-size:.85rem; margin:4px 6px 0 0;
    }
    .chip .dot{ width:10px; height:10px; border-radius:999px; }
    .muted{ color:var(--muted) }

    /* Bloques */
    .block{ margin-bottom:14px }
    .block h4{ margin:0 0 8px; font-size:1rem; letter-spacing:.15px }

    /* Listas */
    .list{ border-top:1px dashed var(--line); padding-top:8px }
    .row{ display:flex; align-items:center; justify-content:space-between; gap:10px; padding:6px 0 }
    .row small{ color:var(--muted) }
    .row .x{ cursor:pointer; color:var(--bad) }

    /* Notificaciones */
    .toast{
      position: fixed; right:18px; bottom:18px; z-index:9999;
      background:#101828; color:#fff; border-radius:12px; box-shadow: var(--shadow);
      padding:12px 14px; display:none; max-width:360px
    }

    /* Loader */
    .skeleton{ position:relative; overflow:hidden; background:#f4f7fb; border-radius:10px; height:34px }
    .skeleton::after{
      content:""; position:absolute; inset:0;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,.65), transparent);
      animation: shimmer 1.3s infinite; transform: translateX(-100%);
    }
    @keyframes shimmer{ 100%{ transform: translateX(100%);} }

    /* Mapa */
    #map{ width:100%; height:100%; border-radius:var(--radius); overflow:hidden; border:1px solid var(--line); box-shadow: var(--shadow) }
    .map-tools{
      position:absolute; right:16px; top:16px; display:flex; gap:8px; z-index:500;
    }
    .legend{
      position:absolute; left:16px; bottom:16px; z-index:500;
      background:rgba(255,255,255,.9); border:1px solid var(--line); border-radius:12px; padding:10px 12px; backdrop-filter: blur(6px);
    }
    .legend h5{ margin:0 0 6px; font-size:.92rem }
  </style>

  <!-- ===== Sidebar ===== -->
  <aside class="panel">
    <div class="head">
      <div class="title">
        <span style="font-size:20px">🗺️</span> Planificador de rutas
      </div>
      <div style="display:flex; gap:8px">
        <button class="btn icon" id="btnCenter" title="Centrar mapa">📍</button>
      </div>
    </div>

    <div class="body">
      {{-- Origen --}}
      <div class="block">
        <h4>Origen</h4>
        <div class="field">
          <input id="origin_lat" placeholder="Latitud (opcional)">
          <input id="origin_lng" placeholder="Longitud (opcional)">
        </div>
        <div class="field" style="align-items:center">
          <button class="btn ghost" id="btnGeo">Usar mi ubicación</button>
          <label style="display:flex; gap:8px; align-items:center">
            <input type="checkbox" id="use_common_origin" checked>
            <span class="muted">Usar como origen común</span>
          </label>
        </div>
      </div>

      {{-- Choferes --}}
      <div class="block">
        <h4>Choferes <small class="muted" id="driversCount"></small></h4>
        <div id="driversBox" class="list">
          <div class="skeleton"></div>
        </div>
      </div>

      {{-- Proveedores --}}
      <div class="block">
        <h4>Proveedores</h4>
        <div class="field">
          <input id="qprov" placeholder="Nombre, ciudad, RFC…">
          <button class="btn" id="btnProv">Buscar</button>
        </div>
        <div id="provResults" class="list"></div>
      </div>

      {{-- Paradas --}}
      <div class="block">
        <h4>Paradas <small class="muted" id="stopsCount"></small></h4>
        <div class="muted" style="margin-bottom:6px">Tip: haz click en el mapa para agregar una parada manual.</div>
        <div id="stopsBox" class="list"></div>
      </div>

      {{-- Acciones --}}
      <div class="block" style="display:flex; flex-wrap:wrap; gap:8px">
        <button class="btn brand" id="btnPlanAI">Planear con IA</button>
        <button class="btn" id="btnPlanLocal">Planear (rápido)</button>
        <button class="btn" id="btnSave">Guardar plan</button>
      </div>

      {{-- Métricas --}}
      <div class="block">
        <h4>Resumen</h4>
        <div id="metrics" style="display:grid; grid-template-columns:1fr 1fr; gap:8px"></div>
      </div>
    </div>
  </aside>

  <!-- ===== Mapa ===== -->
  <div style="position:relative">
    <div id="map"></div>

    <!-- Herramientas sobre mapa -->
    <div class="map-tools">
      <button class="btn icon" id="btnClearRoutes" title="Limpiar rutas">🧹</button>
      <button class="btn icon" id="btnClearStops" title="Limpiar paradas">🗑️</button>
    </div>

    <!-- Leyenda de choferes -->
    <div class="legend" id="legend">
      <h5>Choferes</h5>
      <div id="legendBody" class="muted">Sin datos</div>
    </div>
  </div>

  <!-- Toast -->
  <div class="toast" id="toast"></div>
</div>

{{-- Leaflet --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
(function(){
  /* ===== Config ===== */
  const BASE  = `{{ url('/routing') }}`;
  const CSRF  = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const COLORS= ['#2563eb','#16a34a','#f59e0b','#ef4444','#7c3aed','#0ea5e9'];

  /* ===== DOM ===== */
  const $driversBox  = document.getElementById('driversBox');
  const $driversCount= document.getElementById('driversCount');
  const $provResults = document.getElementById('provResults');
  const $stopsBox    = document.getElementById('stopsBox');
  const $stopsCount  = document.getElementById('stopsCount');
  const $metrics     = document.getElementById('metrics');
  const $legendBody  = document.getElementById('legendBody');
  const $qprov       = document.getElementById('qprov');

  const $btnProv     = document.getElementById('btnProv');
  const $btnPlanAI   = document.getElementById('btnPlanAI');
  const $btnPlanLocal= document.getElementById('btnPlanLocal');
  const $btnSave     = document.getElementById('btnSave');
  const $btnGeo      = document.getElementById('btnGeo');
  const $btnCenter   = document.getElementById('btnCenter');
  const $btnClearRoutes = document.getElementById('btnClearRoutes');
  const $btnClearStops  = document.getElementById('btnClearStops');

  const $originLat   = document.getElementById('origin_lat');
  const $originLng   = document.getElementById('origin_lng');
  const $useCommon   = document.getElementById('use_common_origin');

  const $toast = document.getElementById('toast');

  /* ===== Estado ===== */
  let map = L.map('map', { zoomControl:true }).setView([19.433, -99.133], 11);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    maxZoom:19, attribution:'&copy; OpenStreetMap'
  }).addTo(map);

  let routeLayers = [];
  let stopMarkers = [];
  let drivers = [];   // [{id,name,email,lat,lng,color,checked}]
  let stops   = [];   // [{id,name,lat,lng,type,city,ciudad}]
  let plan    = null;

  /* ===== Utils ===== */
  function toast(msg){
    $toast.textContent = msg; $toast.style.display='block';
    clearTimeout(window.__toastTimer);
    window.__toastTimer = setTimeout(() => $toast.style.display='none', 2400);
  }
  async function fetchJSON(url, opts = {}){
    const isPost = (opts.method||'GET').toUpperCase() !== 'GET';
    const headers = Object.assign(
      {'Accept':'application/json'},
      isPost ? {'Content-Type':'application/json','X-CSRF-TOKEN': CSRF} : {}
    );
    const res = await fetch(url, Object.assign({headers}, opts));
    const type = res.headers.get('content-type') || '';
    if (!res.ok) {
      const txt = await res.text();
      console.error('HTTP',res.status,url,txt);
      toast(`Error ${res.status}`);
      throw new Error('HTTP '+res.status);
    }
    if (type.includes('application/json')) return res.json();
    const txt = await res.text();
    throw new Error('Respuesta no JSON: '+txt.slice(0,140));
  }
  function fitAll(){
    const all = [...stops.map(s=>[s.lat,s.lng])];
    drivers.filter(d=>d.checked && d.lat && d.lng).forEach(d=>all.push([d.lat,d.lng]));
    if (all.length) try{ map.fitBounds(all,{padding:[30,30]}); }catch(e){}
  }
  function clearRoutes(){
    routeLayers.forEach(l => map.removeLayer(l));
    routeLayers = [];
    $metrics.innerHTML = '';
    plan=null;
  }
  function clearStops(){
    stopMarkers.forEach(m => map.removeLayer(m));
    stopMarkers = [];
    stops = [];
    renderStops();
  }

  /* ===== Render UI ===== */
  function renderDrivers(){
    $driversBox.innerHTML = '';
    if (!drivers.length){
      $driversBox.innerHTML = `<div class="muted">No se encontraron usuarios internos.</div>`;
      $driversCount.textContent = '';
      $legendBody.textContent = 'Sin datos';
      return;
    }
    drivers.forEach((d)=> {
      const row = document.createElement('div');
      row.className = 'row';
      row.innerHTML = `
        <div class="chip">
          <span class="dot" style="background:${d.color}"></span>
          <label style="display:flex; align-items:center; gap:8px">
            <input type="checkbox" ${d.checked?'checked':''} data-id="${d.id}">
            <strong>${d.name}</strong>
          </label>
        </div>
        <small>${(d.lat&&d.lng)?`${d.lat.toFixed(4)}, ${d.lng.toFixed(4)}`:'sin origen'}</small>
      `;
      row.querySelector('input').onchange = (e)=>{ d.checked = e.target.checked; updateLegend(); };
      $driversBox.appendChild(row);
    });
    $driversCount.textContent = `(${drivers.length})`;
    updateLegend();
  }
  function updateLegend(){
    const active = drivers.filter(d=>d.checked);
    if (!active.length){ $legendBody.textContent='Activa choferes para ver colores.'; return; }
    $legendBody.innerHTML = active.map(d => `
      <div class="chip" style="margin:4px 6px 0 0">
        <span class="dot" style="background:${d.color}"></span>${d.name}
      </div>`).join('');
  }

  function renderStops(){
    $stopsBox.innerHTML = '';
    $stopsCount.textContent = stops.length ? `(${stops.length})` : '';
    if (!stops.length){
      $stopsBox.innerHTML = `<div class="muted">Aún no hay paradas. Agrega desde proveedores o clic en el mapa.</div>`;
      return;
    }
    stops.forEach((s)=>{
      const row = document.createElement('div');
      row.className = 'row';
      row.innerHTML = `
        <div>
          <div><strong>${s.name}</strong></div>
          <small class="muted">${(s.type||'custom')} · ${s.lat.toFixed(4)}, ${s.lng.toFixed(4)}</small>
        </div>
        <span class="x" title="Quitar">&times;</span>
      `;
      row.querySelector('.x').onclick = ()=>{
        stops = stops.filter(x=>x.id!==s.id);
        drawStops(); renderStops();
      };
      $stopsBox.appendChild(row);
    });
  }
  function drawStops(){
    stopMarkers.forEach(m => map.removeLayer(m));
    stopMarkers = [];
    stops.forEach((s)=> {
      const m = L.marker([s.lat, s.lng], {title:s.name}).addTo(map);
      m.bindPopup(`<strong>${s.name}</strong><br>${s.type||'custom'}`);
      stopMarkers.push(m);
    });
  }

  function paintPlan(resp, label){
    clearRoutes();
    const p = resp.plan || resp;
    if (!p.routes || !p.routes.length){ toast('No se generaron rutas'); return; }

    p.routes.forEach((r, idx)=>{
      const d = drivers.find(x=>x.id===r.driver.id) || { color: COLORS[idx % COLORS.length], name: `Chofer ${idx+1}` };
      const coords = r.coords.map(c => [c.lat, c.lng]);
      const poly = L.polyline(coords, { color: d.color, weight: 5, opacity: .85 }).addTo(map);
      routeLayers.push(poly);

      // Tarjeta métrica
      const card = document.createElement('div');
      card.style.border='1px solid var(--line)';
      card.style.borderRadius='12px';
      card.style.padding='10px';
      card.style.background='#fff';
      card.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:center">
          <strong style="color:${d.color}">${d.name}</strong>
          <small class="muted">${label || (p.engine?.type||'')}</small>
        </div>
        <div style="margin-top:6px; font-size:.95rem">
          Distancia: <strong>${r.metrics.distance_km} km</strong><br>
          Manejo: <strong>${r.metrics.drive_minutes} min</strong><br>
          Total: <strong>${r.metrics.total_minutes} min</strong>
        </div>
      `;
      $metrics.appendChild(card);
    });
    fitAll();
    plan = p;
  }

  /* ===== Build payload ===== */
  function buildPayload(){
    const selDrivers = drivers.filter(d=>d.checked).map(d=>({
      id: d.id, name: d.name,
      lat: ($useCommon.checked && $originLat.value && $originLng.value)
              ? parseFloat($originLat.value) : (d.lat ?? parseFloat($originLat.value)||0),
      lng: ($useCommon.checked && $originLat.value && $originLng.value)
              ? parseFloat($originLng.value) : (d.lng ?? parseFloat($originLng.value)||0),
      color: d.color
    }));
    return {
      origin: ($originLat.value && $originLng.value) ? {lat:parseFloat($originLat.value), lng:parseFloat($originLng.value)} : null,
      drivers: selDrivers,
      stops,
      opts: { use_common_origin: $useCommon.checked, avoid_zone_overlap:true, balance_load:true }
    };
  }

  /* ===== Eventos ===== */
  $btnGeo.onclick = ()=>{
    if (!navigator.geolocation) return toast('Tu navegador no soporta geolocalización');
    navigator.geolocation.getCurrentPosition((p)=>{
      $originLat.value = p.coords.latitude.toFixed(6);
      $originLng.value = p.coords.longitude.toFixed(6);
      map.setView([p.coords.latitude, p.coords.longitude], 13);
      toast('Origen establecido con tu ubicación');
    }, (err)=> {
      console.warn(err);
      toast('No se pudo obtener ubicación (usa https o localhost)');
      // Fallback CDMX centro
      $originLat.value = '19.433000'; $originLng.value='-99.133000';
    }, {enableHighAccuracy:true, timeout:8000});
  };

  $btnCenter.onclick = ()=> fitAll();

  $btnClearRoutes.onclick = ()=>{ clearRoutes(); toast('Rutas limpiadas'); };
  $btnClearStops.onclick  = ()=>{ clearStops(); toast('Paradas limpiadas'); };

  $btnProv.onclick = async ()=>{
    const q = $qprov.value.trim();
    $provResults.innerHTML = '<div class="skeleton"></div>';
    try{
      const res = await fetchJSON(`${BASE}/providers${q?`?q=${encodeURIComponent(q)}`:''}`);
      $provResults.innerHTML = '';
      if (!res.length){ $provResults.innerHTML = '<div class="muted">Sin resultados</div>'; return; }
      res.forEach(p=>{
        const row = document.createElement('div');
        row.className='row';
        row.innerHTML = `
          <div>
            <div><strong>${p.nombre}</strong></div>
            <small class="muted">${(p.colonia||'')}, ${(p.ciudad||'')}, ${(p.estado||'')}</small>
          </div>
          <button class="btn">Agregar</button>
        `;
        row.querySelector('.btn').onclick = ()=>{
          if (p.lat && p.lng) {
            if (!stops.find(s=>s.id===p.id)) {
              stops.push({id:p.id, name:p.nombre, lat:p.lat, lng:p.lng, type:'provider', ciudad:p.ciudad, city:p.ciudad});
              renderStops(); drawStops(); fitAll();
              toast('Parada agregada');
            }
          } else {
            toast('El proveedor no tiene coordenadas (lat/lng)');
          }
        };
        $provResults.appendChild(row);
      });
    }catch(e){}
  };

  // Agregar parada manual
  map.on('click', (e)=>{
    const name = prompt('Nombre de la parada:');
    if (!name) return;
    const id = (Date.now()*-1) + Math.floor(Math.random()*1000);
    stops.push({id, name, lat:e.latlng.lat, lng:e.latlng.lng, type:'custom'});
    renderStops(); drawStops(); fitAll(); toast('Parada manual agregada');
  });

  $btnPlanLocal.onclick = async ()=>{
    const payload = buildPayload();
    if (!payload.drivers.length) return toast('Selecciona al menos un chofer');
    if (!payload.stops.length)   return toast('Agrega al menos una parada');
    try{
      const res = await fetchJSON(`${BASE}/plan`, {method:'POST', body:JSON.stringify(payload)});
      paintPlan(res, 'Local');
      toast('Plan local generado');
    }catch(e){}
  };

  $btnPlanAI.onclick = async ()=>{
    const payload = buildPayload();
    if (!payload.drivers.length) return toast('Selecciona al menos un chofer');
    if (!payload.stops.length)   return toast('Agrega al menos una parada');
    try{
      const res = await fetchJSON(`${BASE}/ai-suggest`, {method:'POST', body:JSON.stringify(payload)});
      paintPlan(res, 'IA');
      toast('Sugerencia IA generada');
    }catch(e){}
  };

  $btnSave.onclick = async ()=>{
    if (!plan || !plan.routes) return toast('Primero genera un plan');
    const plan_date = new Date().toISOString().slice(0,10);
    try{
      const res = await fetchJSON(`${BASE}/save`, {
        method:'POST',
        body: JSON.stringify({ plan_date, engine: plan.engine||{type:'unknown'}, routes: plan.routes, stops_snapshot: stops })
      });
      if (res.ok){ toast('Plan guardado'); }
      else toast('No se pudo guardar');
    }catch(e){}
  };

  /* ===== Init ===== */
  (async function init(){
    // Drivers
    try{
      const raw = await fetchJSON(`${BASE}/drivers`);
      drivers = raw.map((u,i)=>({
        id:u.id, name:u.name, email:u.email,
        lat:u.lat ?? u.last_lat ?? null, lng:u.lng ?? u.last_lng ?? null,
        color: COLORS[i % COLORS.length], checked: i < 2
      }));
      renderDrivers();
      const pts = drivers.filter(d=>d.lat && d.lng).map(d=>[d.lat,d.lng]);
      if (pts.length) map.fitBounds(pts,{padding:[30,30]});
    }catch(e){
      $driversBox.innerHTML = '<div class="muted">No se pudieron cargar choferes</div>';
    }
  })();

})();
</script>
@endsection
