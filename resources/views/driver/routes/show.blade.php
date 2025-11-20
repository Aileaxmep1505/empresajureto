{{-- resources/views/ruta/show.blade.php --}}
@extends('layouts.app')
@section('title','Mi ruta')

@section('content')
<div class="container-fluid p-0" id="rp-driver-pro">
  <style>
    /* =========================
       NAMESPACE #rp-driver-pro
       ========================= */
    #rp-driver-pro{
      --ink:#0e1726; --muted:#6b7280; --line:#e5e7eb; --bg:#f6f8fb; --card:#ffffff;
      --brand:#6ea8fe; --brand-ink:#0b1220;
      --ok:#86efac; --ok-ink:#064e3b;
      --amber:#fde68a; --amber-ink:#7c2d12;
      --red:#fecaca; --red-ink:#7f1d1d;
      color:var(--ink); background:linear-gradient(180deg,#fbfdff,#f6f8fb);
      font-synthesis-weight:none;
    }

    /* ====== 2 columnas en desktop + separación ====== */
    #rp-driver-pro .row.g-0{display:flex;flex-direction:column;gap:12px}
    #rp-driver-pro .col-lg-3,#rp-driver-pro .col-lg-9{min-width:0}
    @media (min-width: 992px){
      #rp-driver-pro .row.g-0{flex-direction:row;gap:16px}
      #rp-driver-pro .col-lg-3{flex:0 0 25%;max-width:25%}
      #rp-driver-pro .col-lg-9{flex:1 1 75%;max-width:75%}
    }

    /* Panel izquierdo */
    #rp-driver-pro .side{
      border-right:1px solid var(--line); background:var(--card);
      min-height:calc(100vh - 56px);
    }
    #rp-driver-pro .toolbar{
      position:sticky; top:56px; z-index:6; background:var(--card);
      border-bottom:1px solid var(--line); backdrop-filter:saturate(140%) blur(4px);
    }
    #rp-driver-pro .grid{display:grid; gap:12px}
    #rp-driver-pro .g3{grid-template-columns:repeat(3,minmax(0,1fr))}
    @media (max-width: 991.98px){ #rp-driver-pro .g3{grid-template-columns:1fr 1fr} }
    @media (max-width: 575.98px){ #rp-driver-pro .g3{grid-template-columns:1fr} }

    /* Tarjetas del panel */
    #rp-driver-pro .card{ border:1px solid var(--line); border-radius:16px; background:var(--card); }
    #rp-driver-pro .card-body{padding:12px 14px}
    #rp-driver-pro .next{
      border-left:4px solid var(--brand);
      background: radial-gradient(800px 300px at -10% -40%, rgba(110,168,254,.20), transparent 60%),
                  linear-gradient(180deg,#f5f9ff,transparent);
    }
    #rp-driver-pro .metric .label{font-size:.74rem;color:var(--muted);text-transform:uppercase;letter-spacing:.02em}
    #rp-driver-pro .metric .value{font-weight:800;font-size:1.2rem}

    /* ===== Mapa en tarjeta ===== */
    #rp-driver-pro .map-card{
      background:#fff; border:1px solid var(--line); border-radius:18px;
      padding:12px; box-shadow:0 16px 40px rgba(2,8,23,.08);
      height:calc(100vh - 56px); min-height:560px; position:relative;
    }
    #rp-driver-pro .map-card .map{
      width:100%; height:100%; border-radius:14px; overflow:hidden; background:#e9eef8;
    }

    /* Overlays dentro del mapa */
    #rp-driver-pro .map-legend{
      position:absolute; left:16px; top:16px; z-index:520;
      background:rgba(255,255,255,.9); backdrop-filter:blur(6px);
      border:1px solid var(--line); border-radius:14px; padding:8px 10px;
      display:flex; flex-wrap:wrap; gap:.5rem;
    }
    #rp-driver-pro .routes-panel{
      position:absolute; left:16px; bottom:16px; z-index:520;
      background:rgba(255,255,255,.95); backdrop-filter:blur(6px);
      border:1px solid var(--line); border-radius:14px; padding:10px; min-width:260px;
      max-width:min(90%,360px);
    }
    #rp-driver-pro .routes-list{display:grid; gap:.5rem}
    #rp-driver-pro .route-card{border:1px solid var(--line);border-radius:12px;padding:.55rem .7rem}
    #rp-driver-pro .route-card.active{border-color:var(--brand); box-shadow:0 10px 28px rgba(110,168,254,.18)}
    #rp-driver-pro .route-head{display:flex;justify-content:space-between;align-items:center}
    #rp-driver-pro .route-badge{display:inline-flex;align-items:center;gap:.35rem;border-radius:999px;padding:.15rem .55rem;font-weight:700;font-size:.8rem}
    #rp-driver-pro .rb-blue{background:#ecf3ff;color:#1e40af;border:1px solid #cfe0ff}
    #rp-driver-pro .rb-amber{background:#fff8e6;color:#92400e;border:1px solid #fed7aa}
    #rp-driver-pro .rb-red{background:#fff1f1;color:#7f1d1d;border:1px solid #fecaca}
    #rp-driver-pro .small-muted{font-size:.85rem;color:#6b7280}

    /* Chips leyenda */
    #rp-driver-pro .chip{ display:inline-flex; align-items:center; gap:.35rem;
      border:1px solid var(--line); background:#fff; border-radius:999px; padding:.18rem .6rem;
      font-weight:700; font-size:.85rem;
    }
    #rp-driver-pro .chip.alt{background:#f0fff8;border-color:#bbf7d0}
    #rp-driver-pro .chip.warn{background:#fff6f6;border-color:#fecaca}

    /* ===== Timeline de paradas (MEJORADO) ===== */
    #rp-driver-pro .timeline{list-style:none;margin:0;padding:0;position:relative}
    #rp-driver-pro .timeline:before{content:"";position:absolute;left:14px;top:0;bottom:0;width:2px;background:var(--line)}
    #rp-driver-pro .tl-item{display:grid;grid-template-columns:28px 1fr;gap:12px;padding:12px 0}
    #rp-driver-pro .dot{width:12px;height:12px;border-radius:50%;margin-top:8px;border:2px solid #2563eb;background:#fff}
    #rp-driver-pro .dot.done{border-color:var(--ok-ink);background:var(--ok)}
    #rp-driver-pro .tl-card{
      border:1px solid var(--line);border-radius:12px;background:#fff;padding:10px 12px;
      display:grid; gap:.4rem;
    }
    #rp-driver-pro .tl-top{
      display:grid; grid-template-columns:1fr auto auto; align-items:center; gap:.5rem;
    }
    #rp-driver-pro .tl-title{
      font-weight:800; font-size:.98rem; line-height:1.25;
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%;
    }
    #rp-driver-pro .tl-badges{display:flex; gap:.35rem; align-items:center}
    #rp-driver-pro .badge-ok{background:var(--ok);color:var(--ok-ink);border-radius:999px;padding:.15rem .55rem;font-weight:800;font-size:.75rem}
    #rp-driver-pro .badge-pending{background:#f3f4f6;color:#111827;border-radius:999px;padding:.15rem .55rem;font-weight:800;font-size:.75rem}
    #rp-driver-pro .tl-btn{ justify-self:end; white-space:nowrap; }
    #rp-driver-pro .tl-meta{
      display:grid; grid-template-columns:1fr 1fr; gap:.35rem .75rem; align-items:center;
    }
    #rp-driver-pro .tl-meta .muted{color:var(--muted); font-size:.85rem}
    #rp-driver-pro .tl-meta strong{font-weight:800}
    @media (max-width:575.98px){
      #rp-driver-pro .tl-top{grid-template-columns:1fr auto}
      #rp-driver-pro .tl-badges{display:none}
      #rp-driver-pro .tl-meta{grid-template-columns:1fr}
    }

    /* Toast + HUD */
    #rp-driver-pro .toastx{
      position:fixed;left:50%;transform:translateX(-50%);bottom:24px;
      background:#111827;color:#fff;padding:.7rem 1rem;border-radius:12px;z-index:20;display:none;
      box-shadow:0 14px 32px rgba(2,8,23,.22)
    }
    #rp-driver-pro .toastx.show{display:block}
    #rp-driver-pro .map-hud{position:absolute; left:50%; top:14px; transform:translateX(-50%); z-index:500; display:flex; gap:8px; pointer-events:none;}
    #rp-driver-pro .nav-toast{background:#111827; color:#fff; border-radius:12px; padding:.6rem .9rem; box-shadow:0 18px 40px rgba(2,8,23,.18); font-weight:700; display:none;}
    #rp-driver-pro .nav-toast.show{ display:block; }

    /* Botones */
    #rp-driver-pro .btn{ border-radius:12px; border:1px solid transparent; font-weight:700; box-shadow:0 6px 16px rgba(2,8,23,.06); transition:.18s; }
    #rp-driver-pro .btn:hover{
      background:#fff !important; color:#0b1220 !important;
      box-shadow:0 18px 42px rgba(2,8,23,.16) !important; transform:scale(1.03);
    }
    #rp-driver-pro .btn-fab{position:fixed; right:18px; bottom:18px; z-index:10}

    /* Flags numerados */
    .flagpin{
      --bg:#2563eb;
      background:var(--bg); color:#fff; font-weight:800; font-size:.78rem;
      border-radius:10px 10px 2px 10px; padding:.15rem .45rem; box-shadow:0 6px 14px rgba(2,8,23,.25);
      border:2px solid #fff;
    }
    .flagpin.done{ --bg:#6b7280; }
  </style>

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>

  <div class="row g-0">
    {{-- IZQUIERDA (25%): Panel --}}
    <div class="col-lg-3 side">
      <div class="toolbar p-3 d-flex align-items-center justify-content-between">
        <div>
          <div class="fw-bold">{{ $routePlan->name ?? ('Ruta #'.$routePlan->id) }}</div>
          <div class="text-muted small">Chofer: {{ $routePlan->driver->name ?? '—' }}</div>

          {{-- Controls GPS (solo se muestran si el navegador está en "prompt") --}}
          <div id="gpsControls" class="d-flex gap-2 mt-2" style="display:none">
            <button id="btnStart" class="btn btn-sm btn-primary">
              <i class="bi bi-crosshair"></i> Usar mi ubicación
            </button>
            <button id="btnRecalc" class="btn btn-sm btn-outline-primary" disabled>
              <i class="bi bi-arrow-repeat"></i> Recalcular
            </button>
          </div>
        </div>

        <div class="d-flex align-items-center"
             style="gap:.5rem;background:#f3f6fb;border:1px dashed var(--line);border-radius:999px;padding:.4rem .8rem;font-weight:700">
          <i class="bi bi-stopwatch"></i> <span id="kpiTotal">—</span>
        </div>
      </div>

      <div class="p-3 grid g3">
        {{-- Siguiente punto --}}
        <div class="card next" style="grid-column:1/-1">
          <div class="card-body d-flex justify-content-between align-items-center">
            <div>
              <div class="small text-uppercase muted mb-1">Siguiente punto</div>
              <div class="h5 m-0" id="nextName">—</div>
              <div class="small"><span id="nextEta">—</span> • llegada <strong id="nextAt">—</strong></div>
            </div>
            <span class="badge-ok"><i class="bi bi-lightning-charge"></i> Prioridad</span>
          </div>
        </div>

        {{-- Métricas --}}
        <div class="card metric"><div class="card-body"><div class="label">Fin estimado</div><div class="value" id="etaFinish">—</div><div class="muted small" id="etaFinishHint">Cuando completes todas</div></div></div>
        <div class="card metric"><div class="card-body"><div class="label">Pendientes</div><div class="value"><span id="pendingCount">—</span>/<span id="totalCount">—</span></div><div class="muted small">Paradas</div></div></div>
        <div class="card metric"><div class="card-body"><div class="label">Distancia</div><div class="value"><span id="totalKm">—</span> km</div><div class="muted small">Ruta activa</div></div></div>

        {{-- Timeline --}}
        <div class="card" style="grid-column:1/-1">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="m-0">Paradas</h6>
              <div class="muted small">Marca “Hecho” al llegar; se recalcula la agenda.</div>
            </div>
            <ul id="timeline" class="timeline">
              @for($i=0;$i<3;$i++)
                <li class="tl-item">
                  <div class="dot sk"></div>
                  <div class="tl-card">
                    <div class="sk" style="height:16px;width:60%;border-radius:6px"></div>
                    <div class="sk" style="height:12px;width:40%;border-radius:6px"></div>
                  </div>
                </li>
              @endfor
            </ul>
          </div>
        </div>

        {{-- Instrucciones --}}
        <div class="card" style="grid-column:1/-1">
          <div class="card-body">
            <h6 class="mb-2">Instrucciones por calles</h6>
            <ul id="steps" class="steps"></ul>
            <div class="muted small" id="stepsHint">Si no ves pasos, usa Google/Waze con los botones del mapa.</div>
          </div>
        </div>

        {{-- Recomendación IA --}}
        <div class="card" style="grid-column:1/-1">
          <div class="card-body">
            <h6 class="mb-2">Recomendación IA</h6>
            <div id="advice" class="small"></div>
          </div>
        </div>
      </div>
    </div>

    {{-- DERECHA (75%): Mapa --}}
    <div class="col-lg-9">
      <div class="map-card">
        <div id="map" class="map"></div>

        <div class="map-legend">
          <span class="chip"><i class="bi bi-square-fill" style="color:#2563eb"></i> Principal</span>
          <span class="chip alt"><i class="bi bi-square-fill" style="color:#10b981"></i> Alternativa</span>
          <span class="chip warn"><i class="bi bi-square-fill" style="color:#ef4444"></i> Evitar</span>
          <span class="chip"><i class="bi bi-circle-fill" style="color:#10b981"></i> Fluido</span>
          <span class="chip"><i class="bi bi-circle-fill" style="color:#f59e0b"></i> Lento</span>
          <span class="chip"><i class="bi bi-circle-fill" style="color:#ef4444"></i> Congestión</span>
        </div>

        <div class="routes-panel">
          <div style="font-weight:800; margin-bottom:.35rem">Rutas</div>
          <div id="routesCards" class="routes-list">
            <div class="sk" style="height:46px;border-radius:12px"></div>
            <div class="sk" style="height:46px;border-radius:12px"></div>
          </div>
          <div id="routesEmpty" class="small-muted" style="display:none;margin-top:.45rem">
            Solo llegó una ruta. Recalcula o abre en Google/Waze.
          </div>
          <div class="d-flex gap-2 mt-2">
            <a id="linkGmaps" href="#" target="_blank" class="btn btn-outline-primary disabled"><i class="bi bi-map"></i> Google Maps</a>
            <a id="linkWaze" href="#" target="_blank" class="btn btn-outline-dark disabled"><i class="bi bi-sign-turn-right"></i> Waze</a>
          </div>
        </div>

        <div class="map-hud"><div id="navToast" class="nav-toast">Listo</div></div>
      </div>
    </div>
  </div>

  <button class="btn btn-primary btn-fab" id="fabDone" style="display:none">
    <i class="bi bi-check2-circle"></i> Marcar punto actual como hecho
  </button>
  <div id="toast" class="toastx">Listo</div>
</div>

<script>
  /* ===== Config: pedir alternativas ===== */
  const REQUEST_ALTS = { include_alternatives: true, max_alternatives: 2, steps: true };

  /* ===== Si API está en otro subdominio, deja true ===== */
  const USE_CREDENTIALS = true;

  /* ===== Datos servidor ===== */
  const planId        = {{ $routePlan->id }};
  const initialStops  = @json($stops);
  const csrf          = @json(csrf_token());
  const URL_COMPUTE   = @json(route('api.routes.compute', $routePlan));
  const URL_RECOMPUTE = @json(route('api.routes.recompute', $routePlan));
  const URL_DONE_BASE = @json(url('/api/routes/'.$routePlan->id.'/stops'));
  const URL_SAVE_LOC  = @json(route('api.driver.location.save'));
  const URL_LAST_LOC  = @json(route('api.driver.location.last'));

  /* ===== Estado ===== */
  let map, base, meMarker, mainLine, alt1Line, alt2Line, segLines = [];
  let currentPos = null, lastPayload = null, watcherId = null;
  let routeSteps = [], stepIdx = 0, didAutoZoom = false, followMode = true;

  const mm = (s)=> Math.round((s||0)/60);
  const km = (m)=> (m||0)/1000;
  const fmtClock = (d)=> `${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`;
  const mdToHtml = (md)=> (md? String(md).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>').replace(/\n/g,'<br>') : '');

  function showToast(text, ok=true){
    const t=document.getElementById('toast');
    t.textContent=text|| (ok?'Listo':'Error');
    t.style.background= ok?'#111827':'#991b1b';
    t.classList.add('show');
    setTimeout(()=>t.classList.remove('show'),2000);
  }
  function mapToast(html){
    const el=document.getElementById('navToast');
    el.innerHTML=html||'Listo';
    el.classList.add('show');
    clearTimeout(el._t);
    el._t=setTimeout(()=>el.classList.remove('show'),4000);
  }

  /* ===== Helper fetch con credenciales opcionales ===== */
  function fopts(extra={}){
    const base = USE_CREDENTIALS ? { credentials:'include' } : {};
    return Object.assign(base, extra);
  }

  /* ===== Mostrar/ocultar controles GPS ===== */
  const gpsControls = () => document.getElementById('gpsControls');
  function showGpsControls(show){
    const el = gpsControls();
    if (!el) return;
    el.style.display = show ? 'flex' : 'none';
  }

  /* ===== Mapa ===== */
  function addStopFlags(stops){
    if (!Array.isArray(stops)) return;
    stops.forEach((s, idx) => {
      const n = (idx+1);
      const html = `<div class="flagpin ${s.status==='done' ? 'done' : ''}">${n}</div>`;
      const icon = L.divIcon({ html, className:'', iconAnchor:[10, 18] });
      const m = L.marker([s.lat, s.lng], { icon }).addTo(map);
      m.bindPopup((s.name||'Punto') + (s.status==='done' ? ' • hecho' : ''));
    });
  }

  function initMap(){
    map = L.map('map', { zoomSnap: 0.5 }).setView([20.6736,-103.344], 12);
    base = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{ attribution:'© OpenStreetMap' }).addTo(map);

    if (initialStops.length){
      addStopFlags(initialStops);
      const grp = L.featureGroup(initialStops.map(s => L.marker([s.lat, s.lng])));
      map.fitBounds(grp.getBounds().pad(0.2));
    }
    map.on('dragstart', ()=> followMode=false);
  }

  function clearRouteLayers(){
    if (mainLine) { map.removeLayer(mainLine); mainLine=null; }
    if (alt1Line){ map.removeLayer(alt1Line); alt1Line=null; }
    if (alt2Line){ map.removeLayer(alt2Line); alt2Line=null; }
    segLines.forEach(l=>map.removeLayer(l)); segLines=[];
  }
  function drawGeo(route, color, weight=6, dashed=false){
    if (!route?.geometry) return null;
    const style = { color, weight, opacity:0.92 }; if (dashed) style.dashArray = '8 10';
    return L.geoJSON(route.geometry, { style }).addTo(map);
  }
  function drawTrafficSegments(legs){
    (legs||[]).forEach((leg)=>{
      const from=leg.from, to=leg.to; if (!from||!to) return;
      const s=leg.severity||'ok';
      const color = s==='heavy' ? '#ef4444' : (s==='slow' ? '#f59e0b' : '#10b981');
      const line = L.polyline([[from.lat,from.lng],[to.lat,to.lng]], { color, weight:7, opacity:.5 }).addTo(map);
      segLines.push(line);
    });
  }
  function fitAll(){
    const layers=[]; if (mainLine) layers.push(mainLine); if (alt1Line) layers.push(alt1Line); if (alt2Line) layers.push(alt2Line);
    segLines.forEach(l=>layers.push(l)); if (meMarker) layers.push(meMarker);
    if (!layers.length) return; const grp=L.featureGroup(layers); map.fitBounds(grp.getBounds().pad(0.18));
  }

  /* ===== Render UI ===== */
  function renderRoutesCards(payload){
    const wrap=document.getElementById('routesCards'); const empty=document.getElementById('routesEmpty');
    wrap.innerHTML=''; empty.style.display='none';
    const routes = payload.routes||[];
    if (!routes.length){ empty.style.display='block'; return; }

    routes.forEach((r,i)=>{
      const cls = i===0 ? 'route-card active' : 'route-card';
      const badge = i===0 ? 'rb-blue' : (i===1 ? 'rb-amber' : 'rb-red');
      const mins = Math.round((r.total_sec||0)/60), h=Math.floor(mins/60), m=mins%60;
      const time = h? `${h} h ${m} min`:`${m} min`;
      const dist = `${km(r.total_m||0).toFixed(1)} km`;
      const toll = r.toll?.has_toll ? ` · Peaje: ~$${r.toll.estimated_mxn} MXN` : ' · Libre';
      wrap.insertAdjacentHTML('beforeend', `
        <div class="${cls}">
          <div class="route-head">
            <span class="route-badge ${badge}"><i class="bi bi-route"></i> ${i===0?'Principal':(i===1?'Alternativa':'Evitar')}</span>
            <span class="small-muted"><i class="bi bi-signpost-2"></i> ${dist}</span>
          </div>
          <div class="small-muted" style="margin-top:.25rem">
            <i class="bi bi-stopwatch"></i> <strong>${time}</strong>${toll}
          </div>
        </div>
      `);
    });

    if (routes.length === 1) empty.style.display='block';
  }

  function renderStepsFromPayload(payload){
    const list=document.getElementById('steps'); list.innerHTML='';
    const stepsHint=document.getElementById('stepsHint');
    routeSteps = Array.isArray(payload?.routes?.[0]?.steps) ? payload.routes[0].steps : [];
    stepIdx=0;
    if (!routeSteps.length){ stepsHint.style.display='block'; return; }
    stepsHint.style.display='none';
    routeSteps.forEach((st, idx)=>{
      const name = st.name || '';
      const instr = st.instruction || st.maneuver || '';
      const dist = st.distance ? (st.distance/1000).toFixed(1)+' km' : '';
      list.insertAdjacentHTML('beforeend', `<li>${idx+1}. ${instr} <span class="muted">${name ? ' • '+name : ''} ${dist ? ' • '+dist : ''}</span></li>`);
    });
    mapToast('Empezamos • ' + (routeSteps[0]?.instruction || 'Sigue la ruta'));
  }

  function renderTimeline(payload){
    lastPayload = payload;
    const ordered=(payload.ordered_stops||[]).slice().sort((a,b)=> (a.sequence_index??999999)-(b.sequence_index??999999) || (a.id-b.id));
    const tl=document.getElementById('timeline'); tl.innerHTML='';

    const legs=(payload.routes?.[0]?.legs||[]).map(l=>Number(l.adj_duration||l.duration||0));
    const perStopSec=[]; let legIdx=0;
    ordered.forEach(s=>{ if(s.status==='done'){ perStopSec.push(0); return; } const eta=Number(s.eta_seconds||0)||legs[legIdx]||0; perStopSec.push(eta); legIdx++; });

    const now=new Date(); let acc=0;
    ordered.forEach((s, idx)=>{
      const seq=(s.sequence_index??idx)+1;
      const dotCls=s.status==='done'?'dot done':'dot';

      let etaMinTxt='—', arriveTxt='—';
      if (s.status!=='done'){
        const sec=perStopSec[idx]||0; const at=new Date(now.getTime()+(acc+sec)*1000);
        etaMinTxt=`${mm(sec)} min`; arriveTxt=fmtClock(at); acc+=sec;
      }

      const statusChip = s.status==='done'
        ? '<span class="badge-ok">hecho</span>'
        : '<span class="badge-pending">pendiente</span>';

      const button = s.status==='done'
        ? '<button class="btn btn-sm btn-success" disabled><i class="bi bi-check2-circle"></i> Hecho</button>'
        : `<button class="btn btn-sm btn-outline-success tl-btn" data-done="${s.id}"><i class="bi bi-check2"></i> Hecho</button>`;

      tl.insertAdjacentHTML('beforeend', `
        <li class="tl-item">
          <div class="${dotCls}"></div>
          <div class="tl-card">
            <div class="tl-top">
              <div class="tl-title">#${seq}. ${ (s.name||'Punto') }</div>
              <div class="tl-badges">${statusChip}</div>
              ${button}
            </div>
            <div class="tl-meta">
              <div class="muted">(${Number(s.lat).toFixed(5)}, ${Number(s.lng).toFixed(5)})</div>
              <div class="muted"><strong>ETA</strong>: ${etaMinTxt} • <strong>llegada</strong>: ${arriveTxt}</div>
            </div>
          </div>
        </li>
      `);
    });

    const pending=ordered.filter(s=>s.status!=='done');
    const totalRemainingSec=pending.reduce((sum,s)=>{const idxO=ordered.indexOf(s);return sum+(perStopSec[idxO]||0);},0);
    const mins=Math.max(1,Math.round(totalRemainingSec/60));
    document.getElementById('kpiTotal').textContent=`${mins} min`;
    const finishAt=new Date(now.getTime()+totalRemainingSec*1000);
    document.getElementById('etaFinish').textContent=pending.length?fmtClock(finishAt):'Completado';
    document.getElementById('etaFinishHint').textContent=pending.length?`En ${mins} min aprox.`:'Todas las paradas hechas';
    document.getElementById('totalCount').textContent=ordered.length;
    document.getElementById('pendingCount').textContent=pending.length;

    if (pending.length){
      const first=pending[0]; const idxFirst=ordered.indexOf(first);
      const seg=perStopSec[idxFirst]||0; const at=new Date(now.getTime()+seg*1000);
      document.getElementById('nextName').textContent=first.name||'Punto';
      document.getElementById('nextEta').textContent=`${mm(seg)} min`;
      document.getElementById('nextAt').textContent=fmtClock(at);
      const fab=document.getElementById('fabDone'); fab.style.display='inline-block'; fab.setAttribute('data-done', first.id);
    }else{
      document.getElementById('nextName').textContent='—';
      document.getElementById('nextEta').textContent='—';
      document.getElementById('nextAt').textContent='—';
      document.getElementById('fabDone').style.display='none';
    }

    renderStepsFromPayload(payload);
    updateNavLinks();
  }

  function renderAdvice(md){ document.getElementById('advice').innerHTML = mdToHtml(md||'Sin observaciones.'); }
  function renderKPIsDistance(payload){ const m=Number(payload?.routes?.[0]?.total_m||0); document.getElementById('totalKm').textContent=m?(m/1000).toFixed(1):'—'; }

  function drawAll(payload){
    clearRouteLayers();
    const R = payload.routes||[];

    if (R[0]) mainLine = drawGeo(R[0], '#2563eb', 6, false);
    if (R[1]) alt1Line = drawGeo(R[1], '#10b981', 5, true);
    if (R[2]) alt2Line = drawGeo(R[2], '#ef4444', 5, true);

    if (R[0]?.legs?.length) drawTrafficSegments(R[0].legs);

    if (currentPos){
      if (meMarker) map.removeLayer(meMarker);
      meMarker = L.circleMarker([currentPos.lat, currentPos.lng], { radius:8, color:'#1d4ed8', fillColor:'#60a5fa', fillOpacity:.9 }).addTo(map);
    }

    fitAll();
    renderRoutesCards(payload);
    renderTimeline(payload);
    renderAdvice(payload.advice_md);
    renderKPIsDistance(payload);
  }

  /* ===== Turn-by-turn + nav links ===== */
  function nextPendingStop(payload){
    const ordered=(payload?.ordered_stops||[]).slice().sort((a,b)=> (a.sequence_index??999999)-(b.sequence_index??999999) || (a.id-b.id));
    return ordered.find(s=>s.status!=='done')||null;
  }
  function updateNavLinks(){
    const g=document.getElementById('linkGmaps'); const w=document.getElementById('linkWaze');
    let dest=null;
    if (routeSteps[stepIdx]?.point){ dest=`${routeSteps[stepIdx].point.lat},${routeSteps[stepIdx].point.lng}`; }
    else { const stop=nextPendingStop(lastPayload); if (stop) dest=`${stop.lat},${stop.lng}`; }
    const origin=currentPos ? `${currentPos.lat},${currentPos.lng}` : null;
    if (!dest || !origin){ g.classList.add('disabled'); w.classList.add('disabled'); return; }
    g.href=`https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(dest)}&travelmode=driving&dir_action=navigate`;
    w.href=`https://waze.com/ul?ll=${encodeURIComponent(dest)}&from=${encodeURIComponent(origin)}&navigate=yes&zoom=17`;
    g.classList.remove('disabled'); w.classList.remove('disabled');
  }

  /* ===== Persistencia ===== */
  async function saveDriverLocation(pos){
    try{
      await fetch(URL_SAVE_LOC, fopts({
        method:'POST',
        headers:{
          'Content-Type':'application/json',
          'Accept':'application/json',
          'X-CSRF-TOKEN': csrf
        },
        body: JSON.stringify({
          lat: pos.lat,
          lng: pos.lng,
          captured_at: new Date().toISOString()
        })
      }));
    }catch(e){
      console.warn('saveDriverLocation error', e);
    }
  }

  /* ===== Watcher continuo ===== */
  function startWatching(){
    if (!navigator.geolocation){
      showToast('Tu dispositivo no soporta GPS', false);
      return;
    }
    if (!window.isSecureContext){
      showToast('GPS bloqueado: el sitio debe estar en HTTPS', false);
      return;
    }
    if (watcherId !== null) return;

    let lastSent=0, lastPos=currentPos;

    watcherId = navigator.geolocation.watchPosition(
      async (p)=>{
        currentPos={ lat:p.coords.latitude, lng:p.coords.longitude };

        if (!didAutoZoom && lastPos){
          const toRad=d=>d*Math.PI/180, R=6371000;
          const dLat=toRad(currentPos.lat-lastPos.lat), dLon=toRad(currentPos.lng-lastPos.lng);
          const lat1=toRad(lastPos.lat), lat2=toRad(currentPos.lat);
          const x=Math.sin(dLat/2)**2 + Math.cos(lat1)*Math.cos(lat2)*Math.sin(dLon/2)**2;
          const d=2*R*Math.asin(Math.sqrt(x));
          if (d >= 30){
            didAutoZoom=true;
            try{ map.flyTo([currentPos.lat,currentPos.lng],15,{duration:.6}); }catch{}
          }
        }

        lastPos=currentPos;

        if (followMode && didAutoZoom){
          map.panTo([currentPos.lat,currentPos.lng],{animate:true,duration:.3});
        }

        if (meMarker) map.removeLayer(meMarker);
        meMarker=L.circleMarker([currentPos.lat,currentPos.lng],{
          radius:8, color:'#1d4ed8', fillColor:'#60a5fa', fillOpacity:.9
        }).addTo(map);

        const now=Date.now();
        if (now-lastSent>15000){
          lastSent=now;
          await saveDriverLocation(currentPos);
          try{ await recompute(); }catch{}
        }

        updateNavLinks();
      },
      (err)=>{
        console.warn("GPS ERROR", err.code, err.message);
        const msg =
          err.code===1 ? 'Permiso de ubicación denegado. Actívalo en tu navegador.' :
          err.code===2 ? 'No se pudo obtener señal GPS.' :
          err.code===3 ? 'El GPS tardó demasiado (timeout).' :
          (err.message || 'Error de GPS');
        showToast(msg, false);
      },
      { enableHighAccuracy:true, maximumAge:5000, timeout:20000 }
    );
  }

  function stopWatching(){
    if (watcherId !== null){
      navigator.geolocation.clearWatch(watcherId);
      watcherId=null;
    }
  }

  /* ===== Pedir GPS UNA sola vez (prompt) y luego ocultar controles ===== */
  async function requestGpsOnce(){
    if (!navigator.geolocation){
      showToast('Tu dispositivo no soporta GPS', false);
      return;
    }
    try{
      const pos = await new Promise((resolve, reject)=>{
        navigator.geolocation.getCurrentPosition(
          p=>resolve({lat:p.coords.latitude,lng:p.coords.longitude}),
          err=>reject(err),
          { enableHighAccuracy:true, timeout:12000, maximumAge:5000 }
        );
      });

      currentPos = pos;
      await saveDriverLocation(pos);
      await compute(pos);
      startWatching();
      showGpsControls(false);          // <- ya concedió, ocultamos
      showToast('GPS activado');
      mapToast('Navegación iniciada');
    }catch(err){
      const msg =
        err?.code===1 ? 'Permiso denegado. Actívalo desde el candado del navegador.' :
        err?.code===2 ? 'No se pudo obtener señal GPS.' :
        err?.code===3 ? 'El GPS tardó demasiado.' :
        (err?.message || 'No se pudo obtener ubicación');
      showToast(msg, false);
    }
  }

  /* ===== API ===== */
  async function compute(start){
    const res=await fetch(URL_COMPUTE, fopts({
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN': csrf
      },
      body: JSON.stringify({ start_lat:start.lat, start_lng:start.lng, ...REQUEST_ALTS })
    }));

    const ct=res.headers.get('content-type')||'';
    if(!ct.includes('application/json')){
      showToast('Respuesta inesperada del servidor', false);
      return;
    }

    const data=await res.json();
    if(!res.ok){
      showToast(data?.message||'No se pudo calcular la ruta', false);
      return;
    }

    drawAll(data);
    lastPayload=data;
    document.getElementById('btnRecalc')?.removeAttribute('disabled');
  }

  async function recompute(){
    if(!currentPos) return;

    const res=await fetch(URL_RECOMPUTE, fopts({
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN': csrf
      },
      body: JSON.stringify({ start_lat: currentPos.lat, start_lng: currentPos.lng, ...REQUEST_ALTS })
    }));

    const data=await res.json();
    if(!res.ok){
      showToast(data?.message||'No se pudo recalcular', false);
      return;
    }

    drawAll(data);
    lastPayload=data;
  }

  /* ===== Auto-boot:
     - Si permission = prompt -> muestra botones y DISPARA el prompt una vez.
     - Si = granted/denied -> oculta botones.
  ===== */
  async function autoBoot(){
    // 1) última ubicación guardada (por si ya estaba en ruta)
    try{
      const r=await fetch(URL_LAST_LOC, fopts({ headers:{'Accept':'application/json'} }));
      if (r.ok){
        const j=await r.json();
        if (j?.lat && j?.lng){
          currentPos={ lat:Number(j.lat), lng:Number(j.lng) };
          await compute(currentPos);
        }
      }
    }catch(e){}

    // 2) permisos
    try{
      if (navigator.permissions?.query){
        const p = await navigator.permissions.query({ name:'geolocation' });

        const applyState = async ()=>{
          if (p.state === 'prompt'){
            showGpsControls(true);

            // Dispara el prompt automáticamente SOLO la primera vez
            // (si el usuario lo cierra, puede dar click en el botón)
            setTimeout(()=> requestGpsOnce(), 600);
          } else if (p.state === 'granted'){
            showGpsControls(false);
            startWatching();

            // Si no había ubicación previa guardada, toma la actual sin prompt
            if (!currentPos){
              requestGpsOnce();
            }
          } else { // denied
            showGpsControls(false);
            showToast('Permiso de ubicación denegado. Actívalo en tu navegador.', false);
          }
        };

        await applyState();
        p.onchange = applyState;
        return;
      }
    }catch(e){}

    // Fallback si no hay permissions API
    showGpsControls(true);
  }

  /* ===== Eventos ===== */
  document.addEventListener('click', async (e)=>{
    const btn=e.target.closest('[data-done]');
    if(!btn) return;

    const doneId=btn.getAttribute('data-done');
    const url=`${URL_DONE_BASE}/${doneId}/done`;

    const res=await fetch(url, fopts({
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN': csrf
      }
    }));

    const data=await res.json();
    if(data.ok){
      await recompute();
      showToast('Punto marcado como hecho');
    }else{
      showToast(data?.message||'No se pudo marcar', false);
    }
  });

  document.getElementById('btnStart')?.addEventListener('click', async ()=>{
    await requestGpsOnce();
  });

  document.getElementById('btnRecalc')?.addEventListener('click', async ()=>{
    if (!currentPos){
      await requestGpsOnce();
      return;
    }
    await recompute();
    showToast('Ruta actualizada');
  });

  window.addEventListener('beforeunload', stopWatching);
  setInterval(async ()=>{ if(currentPos){ await recompute(); } }, 60000);

  // Init
  initMap();
  autoBoot();
</script>
@endsection
