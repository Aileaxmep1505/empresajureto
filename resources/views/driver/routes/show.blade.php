{{-- resources/views/ruta/show.blade.php --}}
@extends('layouts.app')
@section('title','Mi ruta')

@section('content')
@php
  /**
   * ✅ Debug server-side (Laravel log)
   */
  try {
    \Illuminate\Support\Facades\Log::info('driver.routes.show view boot', [
      'plan_id' => $routePlan->id ?? null,
      'stops_count' => isset($stops) ? (is_countable($stops) ? count($stops) : null) : null,
      'has_driver' => isset($routePlan->driver),
      'sequence_locked' => (bool)($routePlan->sequence_locked ?? false),
      'start' => [
        'lat' => $routePlan->start_lat ?? null,
        'lng' => $routePlan->start_lng ?? null,
      ],
    ]);
  } catch (\Throwable $e) {}
@endphp

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
      min-height:calc(100vh - 56px);
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

    /* Skeleton */
    #rp-driver-pro .sk{
      background:linear-gradient(90deg,#eef2ff 0%, #f1f5f9 50%, #eef2ff 100%);
      background-size:200% 100%;
      animation: shimmer 1.2s infinite linear;
    }
    @keyframes shimmer{
      0%{background-position:0% 0%}
      100%{background-position:-200% 0%}
    }

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

    /* ===== Timeline ===== */
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
      background:#111827;color:#fff;padding:.7rem 1rem;border-radius:12px;z-index:2000;display:none;
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

    /* DEBUG chip */
    #rp-driver-pro .dbg{
      position:absolute; right:16px; top:16px; z-index:650;
      background:rgba(17,24,39,.92); color:#fff;
      border-radius:999px; padding:.35rem .7rem;
      font-weight:800; font-size:.8rem;
      border:1px solid rgba(255,255,255,.16);
      box-shadow:0 14px 34px rgba(2,8,23,.22);
      display:none;
      max-width:min(92vw, 560px);
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
  </style>

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>

  <div class="row g-0">
    {{-- IZQUIERDA --}}
    <div class="col-lg-3 side">
      <div class="toolbar p-3 d-flex align-items-center justify-content-between">
        <div>
          <div class="fw-bold">{{ $routePlan->name ?? ('Ruta #'.$routePlan->id) }}</div>
          <div class="text-muted small">Chofer: {{ $routePlan->driver->name ?? '—' }}</div>

          {{-- ✅ Controles GPS (solo si no se ha iniciado / no está bloqueado) --}}
          <div id="gpsControls" class="d-flex gap-2 mt-2" style="display:none">
            <button id="btnStart" class="btn btn-sm btn-primary" type="button">
              <i class="bi bi-play-circle"></i> Iniciar ruta (usar mi ubicación)
            </button>
            <button id="btnRecalc" class="btn btn-sm btn-outline-primary" type="button" disabled>
              <i class="bi bi-arrow-repeat"></i> Recalcular
            </button>
          </div>

          {{-- ✅ Estado / lock --}}
          <div class="small mt-2" id="lockBadgeWrap" style="display:none">
            <span class="badge-ok" id="lockBadge"><i class="bi bi-shield-check"></i> Orden bloqueado</span>
          </div>
        </div>

        <div class="d-flex align-items-center"
             style="gap:.5rem;background:#f3f6fb;border:1px dashed var(--line);border-radius:999px;padding:.4rem .8rem;font-weight:700">
          <i class="bi bi-stopwatch"></i> <span id="kpiTotal">—</span>
        </div>
      </div>

      <div class="p-3 grid g3">
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

        <div class="card metric">
          <div class="card-body">
            <div class="label">Fin estimado</div>
            <div class="value" id="etaFinish">—</div>
            <div class="muted small" id="etaFinishHint">Cuando completes todas</div>
          </div>
        </div>

        <div class="card metric">
          <div class="card-body">
            <div class="label">Pendientes</div>
            <div class="value"><span id="pendingCount">—</span>/<span id="totalCount">—</span></div>
            <div class="muted small">Paradas</div>
          </div>
        </div>

        <div class="card metric">
          <div class="card-body">
            <div class="label">Distancia</div>
            <div class="value"><span id="totalKm">—</span> km</div>
            <div class="muted small">Ruta activa</div>
          </div>
        </div>

        <div class="card" style="grid-column:1/-1">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="m-0">Paradas</h6>
              <div class="muted small">Marca “Hecho” al llegar.</div>
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

        <div class="card" style="grid-column:1/-1">
          <div class="card-body">
            <h6 class="mb-2">Instrucciones por calles</h6>
            <ul id="steps" class="steps"></ul>
            <div class="muted small" id="stepsHint">Si no ves pasos, usa Google/Waze con los botones del mapa.</div>
          </div>
        </div>

        <div class="card" style="grid-column:1/-1">
          <div class="card-body">
            <h6 class="mb-2">Recomendación IA</h6>
            <div id="advice" class="small"></div>
          </div>
        </div>
      </div>
    </div>

    {{-- DERECHA --}}
    <div class="col-lg-9">
      <div class="map-card">
        <div id="map" class="map"></div>

        {{-- DEBUG chip --}}
        <div id="dbgChip" class="dbg">debug</div>

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
          </div>
          <div id="routesEmpty" class="small-muted" style="display:none;margin-top:.45rem">
            Solo llegó una ruta. Recalcula o abre en Google/Waze.
          </div>

          <div class="d-flex gap-2 mt-2">
            <a id="linkGmaps" href="#" target="_blank" class="btn btn-outline-primary disabled"><i class="bi bi-map"></i> Google Maps</a>
            <a id="linkWaze" href="#" target="_blank" class="btn btn-outline-dark disabled"><i class="bi bi-sign-turn-right"></i> Waze</a>
          </div>

          {{-- ✅ info de roundtrip (siempre) --}}
          <div class="small-muted mt-2">
            <i class="bi bi-arrow-90deg-left"></i> Cierre: regresa al inicio (roundtrip)
          </div>
        </div>

        <div class="map-hud"><div id="navToast" class="nav-toast">Listo</div></div>
      </div>
    </div>
  </div>

  <button class="btn btn-primary btn-fab" id="fabDone" style="display:none" type="button">
    <i class="bi bi-check2-circle"></i> Marcar punto actual como hecho
  </button>
  <div id="toast" class="toastx">Listo</div>
</div>

<script>
  /* =========================
   * CONFIG
   * ========================= */
  const DEBUG_ROUTE = true;

  function dlog(label, payload){
    if (!DEBUG_ROUTE) return;
    try { console.log('%c[ROUTE_DEBUG] ' + label, 'font-weight:800', payload ?? ''); } catch(e){}
  }
  function dwarn(label, payload){
    if (!DEBUG_ROUTE) return;
    try { console.warn('[ROUTE_DEBUG] ' + label, payload ?? ''); } catch(e){}
  }

  const DBG_URL = @json(url('/api/client-log'));

  async function sendClientLog(level, message, meta){
    if (!DEBUG_ROUTE) return;
    if (typeof window.csrfFetch !== 'function') return;
    try{
      await window.csrfFetch(DBG_URL, {
        method:'POST',
        headers:{ 'Content-Type':'application/json', 'Accept':'application/json' },
        body: JSON.stringify({
          scope: 'route_driver',
          level: level || 'info',
          message: String(message || ''),
          meta: meta || {}
        })
      });
    }catch(e){}
  }

  function dbgChip(text, isError=false){
    const el = document.getElementById('dbgChip');
    if (!el) return;
    el.style.display = 'block';
    el.textContent = text || 'debug';
    el.style.background = isError ? 'rgba(153,27,27,.92)' : 'rgba(17,24,39,.92)';
    clearTimeout(el._t);
    el._t = setTimeout(()=>{ el.style.display='none'; }, 5000);
  }

  const REQUEST_ALTS = { include_alternatives: false, max_alternatives: 0, steps: true };
  const USE_CREDENTIALS = true;

  /* ===== Datos servidor ===== */
  const planId        = {{ $routePlan->id }};
  const initialStops  = @json($stops);
  const csrf          = @json(csrf_token());

  // ✅ Nuevas rutas: start bloquea el orden 1 vez
  const URL_START     = @json(route('api.routes.start', $routePlan));
  const URL_COMPUTE   = @json(route('api.routes.compute', $routePlan));
  const URL_RECOMPUTE = @json(route('api.routes.recompute', $routePlan));
  const URL_DONE_BASE = @json(url('/api/routes/'.$routePlan->id.'/stops'));
  const URL_SAVE_LOC  = @json(route('api.driver.location.save'));
  const URL_LAST_LOC  = @json(route('api.driver.location.last'));
  const URL_LIVE      = @json(route('api.routes.live', $routePlan));

  dlog('boot', { planId, initialStopsCount: (initialStops||[]).length, URL_START, URL_COMPUTE, URL_RECOMPUTE });

  /* ===== Estado ===== */
  let map, base, meMarker, mainLine, alt1Line, alt2Line, segLines = [];
  let currentPos = null, lastPayload = null, watcherId = null;
  let routeSteps = [], stepIdx = 0, didAutoZoom = false, followMode = true;

  // ✅ Estado de lock (server)
  let serverLocked = false;

  /* ===== Utils ===== */
  const mm = (s)=> Math.round((s||0)/60);
  const km = (m)=> (m||0)/1000;
  const fmtClock = (d)=> `${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`;
  const mdToHtml = (md)=> (md? String(md)
    .replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
    .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>').replace(/\n/g,'<br>')
    : ''
  );

  const toNum = (v)=> {
    const n = Number(v);
    return Number.isFinite(n) ? n : null;
  };
  const isValidLatLng = (lat,lng)=> {
    if (lat === null || lng === null) return false;
    if (Math.abs(lat) < 0.000001 && Math.abs(lng) < 0.000001) return false;
    return Math.abs(lat) <= 90 && Math.abs(lng) <= 180;
  };

  function showToast(text, ok=true){
    const t=document.getElementById('toast');
    t.textContent=text|| (ok?'Listo':'Error');
    t.style.background= ok?'#111827':'#991b1b';
    t.classList.add('show');
    setTimeout(()=>t.classList.remove('show'),2400);
  }
  function mapToast(html){
    const el=document.getElementById('navToast');
    el.innerHTML=html||'Listo';
    el.classList.add('show');
    clearTimeout(el._t);
    el._t=setTimeout(()=>el.classList.remove('show'),4000);
  }

  function fopts(extra={}){
    const base = USE_CREDENTIALS ? { credentials:'include' } : {};
    return Object.assign(base, extra);
  }

  async function safeJsonFetch(url, options){
    let res;
    try{
      res = await fetch(url, options);
    }catch(e){
      dwarn('fetch network error', { url, err: String(e) });
      dbgChip('Network error en fetch()', true);
      await sendClientLog('error', 'fetch network error', { url, err: String(e) });
      return { ok:false, status:0, data:null };
    }

    const ct = (res.headers.get('content-type')||'').toLowerCase();

    if (!ct.includes('application/json')){
      const text = await res.text().catch(()=> '');
      const isLogin = text.includes('<html') || text.includes('<!doctype') || text.includes('login');
      const code = res.status;

      const hint =
        code === 401 ? '401 (sesión)' :
        code === 403 ? '403 (permiso)' :
        code === 419 ? '419 (CSRF)' :
        isLogin ? 'HTML (login?)' :
        'non-json';

      dwarn('non-json response', { url, status: code, ct, hint, sample: text.slice(0,220) });
      await sendClientLog('error', 'non-json response', { url, status: code, ct, hint, sample: text.slice(0,220) });

      if (code === 401) showToast('Sesión no válida (401).', false);
      else if (code === 419) showToast('CSRF expirado (419). Recarga.', false);
      else if (code === 403) showToast('No tienes permiso (403).', false);
      else showToast('Respuesta no-JSON del servidor ('+code+').', false);

      dbgChip('API non-json: ' + hint, true);
      return { ok:false, status:code, data:null };
    }

    const data = await res.json().catch(()=>null);

    if (!res.ok){
      const msg = data?.message || ('Error HTTP '+res.status);
      dwarn('api error', { url, status: res.status, data });
      await sendClientLog('error', 'api error', { url, status: res.status, data });
      dbgChip('API error ' + res.status + ': ' + (data?.message || 'sin mensaje'), true);
      showToast(msg, false);
      return { ok:false, status:res.status, data };
    }

    return { ok:true, status:res.status, data };
  }

  /* ===== Mostrar/ocultar controles GPS ===== */
  function setLockUI(locked){
    serverLocked = !!locked;
    const wrap = document.getElementById('lockBadgeWrap');
    if (wrap) wrap.style.display = locked ? 'block' : 'none';

    // si está locked, ocultamos "Iniciar" y dejamos "Recalcular"
    const controls = document.getElementById('gpsControls');
    if (!controls) return;

    // si ya está locked, no mostramos iniciar (pero recalc queda disponible si hay GPS)
    const btnStart = document.getElementById('btnStart');
    if (btnStart) btnStart.style.display = locked ? 'none' : 'inline-flex';
  }

  function showGpsControls(show){
    const el = document.getElementById('gpsControls');
    if (!el) return;
    el.style.display = show ? 'flex' : 'none';
  }

  /* ===== Mapa ===== */
  const stopMarkers = [];
  function addStopFlags(stops){
    stopMarkers.forEach(m=>{ try{ map.removeLayer(m); }catch(e){} });
    stopMarkers.length = 0;

    // ✅ Numeración usando sequence_index (1..N) si existe
    const ordered = (stops||[]).slice().sort((a,b)=>
      (a.sequence_index??999999)-(b.sequence_index??999999) || (a.id-b.id)
    );

    ordered.forEach((s, idx) => {
      const lat = toNum(s.lat), lng = toNum(s.lng);
      if (!isValidLatLng(lat,lng)) return;

      const n = (s.sequence_index != null && Number.isFinite(Number(s.sequence_index)))
        ? (Number(s.sequence_index)) // ya viene 1..N en el controller
        : (idx+1);

      const html = `<div class="flagpin ${s.status==='done' ? 'done' : ''}">${n}</div>`;
      const icon = L.divIcon({ html, className:'', iconAnchor:[10, 18] });
      const m = L.marker([lat, lng], { icon }).addTo(map);
      stopMarkers.push(m);
      m.bindPopup((s.name||'Punto') + (s.status==='done' ? ' • hecho' : ''));
    });
  }

  function initMap(){
    map = L.map('map', { zoomSnap: 0.5 }).setView([20.6736,-103.344], 12);
    base = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{ attribution:'© OpenStreetMap' }).addTo(map);

    setTimeout(()=>{ try{ map.invalidateSize(true); }catch(e){} }, 200);
    window.addEventListener('resize', ()=>{ try{ map.invalidateSize(true); }catch(e){} });

    const validStops = (initialStops||[])
      .map(s=>({ ...s, lat: toNum(s.lat), lng: toNum(s.lng) }))
      .filter(s=> isValidLatLng(s.lat, s.lng));

    if (validStops.length){
      addStopFlags(validStops);
      const grp = L.featureGroup(validStops.map(s => L.marker([s.lat, s.lng])));
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
    if (!route) return null;
    const geo = route.geometry;
    if (!geo) return null;

    const style = { color, weight, opacity:0.92 };
    if (dashed) style.dashArray = '8 10';

    try{
      return L.geoJSON(geo, { style }).addTo(map);
    }catch(e){
      dwarn('drawGeo failed', { err: String(e), geoType: geo?.type });
      sendClientLog('error', 'drawGeo failed', { err: String(e), geo });
      return null;
    }
  }

  function fitAll(){
    const layers=[];
    if (mainLine) layers.push(mainLine);
    if (meMarker) layers.push(meMarker);
    if (!layers.length) return;

    try{
      const grp=L.featureGroup(layers);
      map.fitBounds(grp.getBounds().pad(0.18));
    }catch(e){}
  }

  /* ===== Render UI ===== */
  function renderRoutesCards(payload){
    const wrap=document.getElementById('routesCards');
    const empty=document.getElementById('routesEmpty');

    wrap.innerHTML=''; empty.style.display='none';
    const routes = payload.routes||[];
    if (!routes.length){ empty.style.display='block'; return; }

    routes.forEach((r,i)=>{
      const cls = 'route-card active';
      const badge = 'rb-blue';
      const mins = Math.round((r.total_sec||0)/60), h=Math.floor(mins/60), m=mins%60;
      const time = h? `${h} h ${m} min`:`${m} min`;
      const dist = `${km(r.total_m||0).toFixed(1)} km`;

      wrap.insertAdjacentHTML('beforeend', `
        <div class="${cls}">
          <div class="route-head">
            <span class="route-badge ${badge}"><i class="bi bi-route"></i> Principal</span>
            <span class="small-muted"><i class="bi bi-signpost-2"></i> ${dist}</span>
          </div>
          <div class="small-muted" style="margin-top:.25rem">
            <i class="bi bi-stopwatch"></i> <strong>${time}</strong> · Roundtrip
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
      const instr = st.instruction || '';
      const dist = st.distance ? (st.distance/1000).toFixed(1)+' km' : '';
      list.insertAdjacentHTML('beforeend', `<li>${idx+1}. ${instr} <span class="muted">${name ? ' • '+name : ''} ${dist ? ' • '+dist : ''}</span></li>`);
    });

    mapToast('Empezamos • ' + (routeSteps[0]?.instruction || 'Sigue la ruta'));
  }

  function renderTimeline(payload){
    lastPayload = payload;

    const ordered=(payload.ordered_stops||[]).slice()
      .sort((a,b)=> (a.sequence_index??999999)-(b.sequence_index??999999) || (a.id-b.id));

    const tl=document.getElementById('timeline'); tl.innerHTML='';

    // ✅ usar eta_seconds ya calculado por backend (parejo y consistente)
    const now=new Date();

    ordered.forEach((s)=>{
      const dotCls=s.status==='done'?'dot done':'dot';

      let etaMinTxt='—', arriveTxt='—';
      if (s.status!=='done'){
        const sec=Number(s.eta_seconds||0)||0;
        const at=new Date(now.getTime()+sec*1000);
        etaMinTxt=`${mm(sec)} min`;
        arriveTxt=fmtClock(at);
      }

      const seq = (s.sequence_index != null ? Number(s.sequence_index) : null);

      const statusChip = s.status==='done'
        ? '<span class="badge-ok">hecho</span>'
        : '<span class="badge-pending">pendiente</span>';

      const button = s.status==='done'
        ? '<button class="btn btn-sm btn-success" type="button" disabled><i class="bi bi-check2-circle"></i> Hecho</button>'
        : `<button class="btn btn-sm btn-outline-success tl-btn" type="button" data-done="${s.id}"><i class="bi bi-check2"></i> Hecho</button>`;

      const lat = toNum(s.lat), lng = toNum(s.lng);
      const coord = (isValidLatLng(lat,lng)) ? `(${lat.toFixed(5)}, ${lng.toFixed(5)})` : '(—)';

      tl.insertAdjacentHTML('beforeend', `
        <li class="tl-item">
          <div class="${dotCls}"></div>
          <div class="tl-card">
            <div class="tl-top">
              <div class="tl-title">${seq ? '#'+seq+'. ' : ''}${ (s.name||'Punto') }</div>
              <div class="tl-badges">${statusChip}</div>
              ${button}
            </div>
            <div class="tl-meta">
              <div class="muted">${coord}</div>
              <div class="muted"><strong>ETA</strong>: ${etaMinTxt} • <strong>llegada</strong>: ${arriveTxt}</div>
            </div>
          </div>
        </li>
      `);
    });

    const pending=ordered.filter(s=>s.status!=='done');
    document.getElementById('totalCount').textContent=ordered.length;
    document.getElementById('pendingCount').textContent=pending.length;

    if (pending.length){
      const first=pending[0];
      const sec=Number(first.eta_seconds||0)||0;
      const at=new Date(now.getTime()+sec*1000);

      document.getElementById('nextName').textContent=first.name||'Punto';
      document.getElementById('nextEta').textContent=`${mm(sec)} min`;
      document.getElementById('nextAt').textContent=fmtClock(at);

      const fab=document.getElementById('fabDone');
      fab.style.display='inline-block';
      fab.setAttribute('data-done', first.id);
    } else {
      document.getElementById('nextName').textContent='—';
      document.getElementById('nextEta').textContent='—';
      document.getElementById('nextAt').textContent='—';
      document.getElementById('fabDone').style.display='none';
    }

    renderStepsFromPayload(payload);
    updateNavLinks();
  }

  function renderAdvice(md){ document.getElementById('advice').innerHTML = mdToHtml(md||'Sin observaciones.'); }
  function renderKPIsDistance(payload){
    const m=Number(payload?.routes?.[0]?.total_m||0);
    document.getElementById('totalKm').textContent=m?(m/1000).toFixed(1):'—';
    const mins = Math.max(1, Math.round(Number(payload?.routes?.[0]?.total_sec||0)/60));
    document.getElementById('kpiTotal').textContent = `${mins} min`;
  }

  function drawAll(payload){
    clearRouteLayers();

    const R = payload.routes||[];

    if (R[0]) mainLine = drawGeo(R[0], '#2563eb', 6, false);

    if (currentPos && isValidLatLng(currentPos.lat, currentPos.lng)){
      if (meMarker) map.removeLayer(meMarker);
      meMarker = L.circleMarker([currentPos.lat, currentPos.lng], { radius:8, color:'#1d4ed8', fillColor:'#60a5fa', fillOpacity:.9 }).addTo(map);
    }

    if (Array.isArray(payload.ordered_stops) && payload.ordered_stops.length){
      addStopFlags(payload.ordered_stops);
    }

    if (mainLine) fitAll();

    // ✅ lock UI
    setLockUI(!!payload.sequence_locked);

    renderRoutesCards(payload);
    renderTimeline(payload);
    renderAdvice(payload.advice_md);
    renderKPIsDistance(payload);

    setTimeout(()=>{ try{ map.invalidateSize(true); }catch(e){} }, 60);
  }

  /* ===== nav links ===== */
  function nextPendingStop(payload){
    const ordered=(payload?.ordered_stops||[]).slice()
      .sort((a,b)=> (a.sequence_index??999999)-(b.sequence_index??999999) || (a.id-b.id));
    return ordered.find(s=>s.status!=='done')||null;
  }

  function updateNavLinks(){
    const g=document.getElementById('linkGmaps');
    const w=document.getElementById('linkWaze');

    let dest=null;
    const stop = nextPendingStop(lastPayload);
    if (stop && stop.lat != null && stop.lng != null){
      dest=`${stop.lat},${stop.lng}`;
    }
    const origin = currentPos ? `${currentPos.lat},${currentPos.lng}` : null;

    if (!dest || !origin){
      g.classList.add('disabled'); w.classList.add('disabled');
      return;
    }

    g.href=`https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(dest)}&travelmode=driving&dir_action=navigate`;
    w.href=`https://waze.com/ul?ll=${encodeURIComponent(dest)}&from=${encodeURIComponent(origin)}&navigate=yes&zoom=17`;

    g.classList.remove('disabled');
    w.classList.remove('disabled');
  }

  /* ===== Persistencia ===== */
  async function saveDriverLocation(pos){
    const payload = { lat: pos.lat, lng: pos.lng, captured_at: new Date().toISOString() };

    const r = await safeJsonFetch(URL_SAVE_LOC, fopts({
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN': csrf
      },
      body: JSON.stringify(payload)
    }));

    if (!r.ok){
      await sendClientLog('error', 'saveDriverLocation failed', { r });
    }
  }

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
          try{ await recompute(); }catch(e){
            await sendClientLog('error', 'recompute failed in watcher', { err: String(e) });
          }
        }

        updateNavLinks();
      },
      async (err)=>{
        const msg =
          err.code===1 ? 'Permiso de ubicación denegado. Actívalo en tu navegador.' :
          err.code===2 ? 'No se pudo obtener señal GPS.' :
          err.code===3 ? 'El GPS tardó demasiado (timeout).' :
          (err.message || 'Error de GPS');

        showToast(msg, false);
        dbgChip('GPS: ' + msg, true);
        await sendClientLog('error', 'gps error', { code: err.code, message: err.message });
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

  async function requestGpsOnce(){
    if (!navigator.geolocation){
      showToast('Tu dispositivo no soporta GPS', false);
      return null;
    }
    if (!window.isSecureContext){
      showToast('El GPS requiere HTTPS', false);
      return null;
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
      dbgChip('GPS listo: ' + pos.lat.toFixed(5) + ', ' + pos.lng.toFixed(5));
      await saveDriverLocation(pos);
      return pos;
    }catch(err){
      const msg =
        err?.code===1 ? 'Permiso denegado. Actívalo desde el candado.' :
        err?.code===2 ? 'No se pudo obtener señal GPS.' :
        err?.code===3 ? 'El GPS tardó demasiado.' :
        (err?.message || 'No se pudo obtener ubicación');

      showToast(msg, false);
      dbgChip('GPS error: ' + msg, true);
      await sendClientLog('error', 'requestGpsOnce failed', { err: String(err), msg });
      return null;
    }
  }

  /* ===== API ===== */
  async function startRoute(start){
    if (!start || !isValidLatLng(toNum(start.lat), toNum(start.lng))){
      showToast('Ubicación inválida para iniciar.', false);
      return false;
    }

    const payloadOut = { start_lat:start.lat, start_lng:start.lng };
    dlog('start request', payloadOut);

    const r = await safeJsonFetch(URL_START, fopts({
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN': csrf
      },
      body: JSON.stringify(payloadOut)
    }));

    if (!r.ok) return false;

    showToast('Ruta iniciada. Orden bloqueado.');
    setLockUI(true);
    return true;
  }

  async function compute(start){
    if (!start || !isValidLatLng(toNum(start.lat), toNum(start.lng))){
      showToast('Ubicación inválida para calcular.', false);
      return;
    }

    const payloadOut = { start_lat:start.lat, start_lng:start.lng, ...REQUEST_ALTS };

    const r = await safeJsonFetch(URL_COMPUTE, fopts({
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN': csrf
      },
      body: JSON.stringify(payloadOut)
    }));

    if (!r.ok || !r.data) return;

    drawAll(r.data);
    lastPayload=r.data;
    document.getElementById('btnRecalc')?.removeAttribute('disabled');
  }

  async function recompute(){
    if(!currentPos) return;

    const payloadOut = { start_lat: currentPos.lat, start_lng: currentPos.lng, ...REQUEST_ALTS };

    const r = await safeJsonFetch(URL_RECOMPUTE, fopts({
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN': csrf
      },
      body: JSON.stringify(payloadOut)
    }));

    if (!r.ok || !r.data) return;

    drawAll(r.data);
    lastPayload=r.data;
  }

  async function fetchLive(){
    const r = await safeJsonFetch(URL_LIVE, fopts({ headers:{ 'Accept':'application/json' } }));
    if (!r.ok || !r.data) return null;
    return r.data;
  }

  /* ===== Auto-boot ===== */
  async function autoBoot(){
    // 1) preguntar al server si ya está bloqueado y si hay start guardado
    try{
      const live = await fetchLive();
      if (live?.sequence_locked != null) setLockUI(!!live.sequence_locked);
    }catch(e){}

    // 2) última ubicación guardada
    try{
      const r = await safeJsonFetch(URL_LAST_LOC, fopts({ headers:{'Accept':'application/json'} }));
      if (r.ok && r.data?.lat && r.data?.lng){
        currentPos={ lat:Number(r.data.lat), lng:Number(r.data.lng) };
        await compute(currentPos);
      }
    }catch(e){}

    // 3) permisos
    try{
      if (navigator.permissions?.query){
        const p = await navigator.permissions.query({ name:'geolocation' });

        const applyState = async ()=>{
          if (p.state === 'prompt'){
            // si NO está bloqueado, mostramos iniciar
            showGpsControls(true);
          } else if (p.state === 'granted'){
            showGpsControls(true); // deja visible recalc; iniciar se oculta si locked
            startWatching();
            if (!currentPos){
              const pos = await requestGpsOnce();
              if (pos) await compute(pos);
            }
          } else {
            showGpsControls(false);
            showToast('Permiso de ubicación denegado.', false);
            dbgChip('GPS denied', true);
          }
        };

        await applyState();
        p.onchange = applyState;
        return;
      }
    }catch(e){}

    showGpsControls(true);
  }

  /* ===== Eventos ===== */
  document.addEventListener('click', async (e)=>{
    const btn=e.target.closest('[data-done]');
    if(!btn) return;

    const doneId=btn.getAttribute('data-done');
    const url=`${URL_DONE_BASE}/${doneId}/done`;

    const r = await safeJsonFetch(url, fopts({
      method:'POST',
      headers:{
        'Accept':'application/json',
        'X-CSRF-TOKEN': csrf
      }
    }));

    if (r.ok && r.data?.ok){
      await recompute();
      showToast('Punto marcado como hecho');
      dbgChip('Punto marcado ✓');
    }else{
      showToast(r.data?.message||'No se pudo marcar', false);
      dbgChip('No se pudo marcar', true);
    }
  });

  // ✅ Iniciar ruta: bloquea orden 1 vez en backend
  document.getElementById('btnStart')?.addEventListener('click', async ()=>{
    const pos = await requestGpsOnce();
    if (!pos) return;

    const ok = await startRoute(pos);
    if (!ok) return;

    await compute(pos);
    startWatching();
    mapToast('Ruta iniciada (orden fijo)');
  });

  document.getElementById('btnRecalc')?.addEventListener('click', async ()=>{
    if (!currentPos){
      const pos = await requestGpsOnce();
      if (!pos) return;
      await compute(pos);
      startWatching();
      return;
    }
    await recompute();
    showToast('Ruta actualizada');
  });

  // FAB marca el "siguiente" directamente
  document.getElementById('fabDone')?.addEventListener('click', async (e)=>{
    const id = e.currentTarget.getAttribute('data-done');
    if (!id) return;
    const url=`${URL_DONE_BASE}/${id}/done`;

    const r = await safeJsonFetch(url, fopts({
      method:'POST',
      headers:{ 'Accept':'application/json', 'X-CSRF-TOKEN': csrf }
    }));

    if (r.ok && r.data?.ok){
      await recompute();
      showToast('Punto marcado como hecho');
      dbgChip('Punto marcado ✓');
    }else{
      showToast(r.data?.message||'No se pudo marcar', false);
      dbgChip('No se pudo marcar', true);
    }
  });

  window.addEventListener('beforeunload', stopWatching);

  // ✅ refresco suave
  setInterval(async ()=>{ if(currentPos){ await recompute(); } }, 60000);

  // Init
  initMap();
  autoBoot();
</script>

{{-- ============================
   ✅ SNIPPET BACKEND (OPCIONAL PARA LOGS DEL CLIENTE)
   Pégalo en routes/api.php
============================ --}}
{{--
Route::middleware(['auth'])->post('/client-log', function (\Illuminate\Http\Request $r) {
    $data = $r->validate([
        'scope' => ['nullable','string','max:80'],
        'level' => ['nullable','string','max:20'],
        'message' => ['required','string','max:1000'],
        'meta' => ['nullable','array'],
    ]);

    $scope = $data['scope'] ?? 'client';
    $level = strtolower($data['level'] ?? 'info');
    $msg   = "[CLIENT_LOG][$scope] ".$data['message'];

    $ctx = [
        'user_id' => auth()->id(),
        'meta' => $data['meta'] ?? [],
        'ip' => $r->ip(),
        'ua' => substr((string)$r->userAgent(), 0, 240),
    ];

    if ($level === 'error') \Log::error($msg, $ctx);
    elseif ($level === 'warning' || $level === 'warn') \Log::warning($msg, $ctx);
    else \Log::info($msg, $ctx);

    return response()->json(['ok'=>true]);
});
--}}
@endsection
