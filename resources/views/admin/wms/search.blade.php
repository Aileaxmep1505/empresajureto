@extends('layouts.app')

@section('title', 'WMS · Buscar producto')

@section('content')
<div class="wms-page">
  <div class="wms-shell">

    {{-- Header --}}
    <div class="wms-header">
      <a href="{{ route('admin.wms.home') }}" class="wms-back" aria-label="Volver a WMS">
        <svg class="ico" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>WMS</span>
      </a>

      <div class="wms-head-mid">
        <div class="wms-head-title">Buscar producto</div>
        <div class="wms-head-sub">Nombre / SKU / GTIN · Resultados con ubicación y ruta “Llévame”.</div>
      </div>

      <div class="wms-head-actions">
        <button class="wms-btn wms-btn-ghost" type="button" id="btnOpenScanner">
          <svg class="ico" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 7V6a2 2 0 0 1 2-2h1M20 7V6a2 2 0 0 0-2-2h-1M4 17v1a2 2 0 0 0 2 2h1M20 17v1a2 2 0 0 1-2 2h-1" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M7 12h10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <span>Escanear</span>
        </button>
      </div>
    </div>

    {{-- Cards --}}
    <div class="wms-grid">

      {{-- Ubicación actual --}}
      <section class="wms-card">
        <div class="wms-card-h">
          <div>
            <div class="wms-card-tt">Tu ubicación actual</div>
            <div class="wms-card-tx">Escanea un QR de stand/bin para fijar “dónde estás” y ordenar resultados por cercanía.</div>
          </div>
          <span class="wms-pill" id="chipFrom">No definida</span>
        </div>

        <div class="wms-form">
          <label class="wms-lbl" for="from_code">Código ubicación (QR)</label>
          <div class="wms-row">
            <input class="wms-inp" id="from_code" placeholder="Ej: A-03-S2-R1-N4-B07" autocomplete="off" inputmode="text">
            <button class="wms-btn wms-btn-ghost" type="button" id="btnSetFrom">Fijar</button>
            <button class="wms-btn wms-btn-ghost" type="button" id="btnClearFrom">Limpiar</button>
          </div>
          <div class="wms-hint">Tip: puedes pegar el código si no tienes cámara.</div>
        </div>
      </section>

      {{-- Buscador --}}
      <section class="wms-card wms-card-span">
        <div class="wms-card-h">
          <div>
            <div class="wms-card-tt">Buscador</div>
            <div class="wms-card-tx">Escribe o escanea un SKU/GTIN. Te sugerimos la mejor ubicación con stock.</div>
          </div>
          <span class="wms-pill wms-pill-soft" id="chipCount">0 resultados</span>
        </div>

        <div class="wms-form">
          <label class="wms-lbl" for="q">Buscar</label>
          <div class="wms-row">
            <input class="wms-inp" id="q" placeholder="Ej: Bic azul 0.7 / SKU / GTIN" autocomplete="off" inputmode="search">
            <button class="wms-btn wms-btn-primary" type="button" id="btnSearch">
              <span class="wms-spin" id="spinSearch"></span>
              <span>Buscar</span>
            </button>
          </div>
          <div class="wms-hint">Enter también funciona. Resultado muestra stock total y ubicaciones.</div>
        </div>

        <div class="wms-results" id="results"></div>
      </section>

    </div>
  </div>
</div>

{{-- Modal: Ruta “Llévame” --}}
<div class="wms-modal" id="navModal" aria-hidden="true">
  <div class="wms-modal-backdrop" data-close="1"></div>
  <div class="wms-modal-card">
    <div class="wms-modal-h">
      <div>
        <div class="wms-modal-tt">Ruta “Llévame”</div>
        <div class="wms-modal-tx" id="navSubtitle">—</div>
      </div>
      <button class="wms-x" type="button" data-close="1" aria-label="Cerrar">
        <svg class="ico" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
    </div>

    <div class="wms-steps" id="navSteps"></div>

    <div class="wms-modal-ft">
      <button class="wms-btn wms-btn-ghost" type="button" data-close="1">Cerrar</button>
      <button class="wms-btn wms-btn-primary" type="button" id="btnMarkHere">Ya estoy aquí</button>
    </div>
  </div>
</div>

{{-- Modal: Scanner --}}
<div class="wms-modal" id="scanModal" aria-hidden="true">
  <div class="wms-modal-backdrop" data-close="1"></div>
  <div class="wms-modal-card wms-modal-wide">
    <div class="wms-modal-h">
      <div>
        <div class="wms-modal-tt">Escanear</div>
        <div class="wms-modal-tx">Apunta al QR de ubicación o al código del producto.</div>
      </div>
      <button class="wms-x" type="button" data-close="1" aria-label="Cerrar">
        <svg class="ico" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
    </div>

    <div class="wms-scan">
      <div class="wms-scan-cam">
        <video id="video" playsinline webkit-playsinline muted autoplay></video>
        <div class="wms-scan-overlay" aria-hidden="true">
          <div class="wms-scan-frame"></div>
          <div class="wms-scan-hint">Centra el QR/código dentro del cuadro</div>
        </div>
      </div>

      <aside class="wms-scan-side">
        <div class="wms-mini">
          <div class="wms-mini-tt">Modo</div>
          <div class="wms-mini-row">
            <button class="wms-btn wms-btn-ghost wms-btn-sm" type="button" id="scanModeLoc">
              <svg class="ico" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11z" fill="none" stroke="currentColor" stroke-width="2"/>
                <path d="M12 10.5a2.2 2.2 0 1 0 0 .1" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
              <span>Ubicación</span>
            </button>
            <button class="wms-btn wms-btn-ghost wms-btn-sm" type="button" id="scanModeItem">
              <svg class="ico" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M20 10V7a2 2 0 0 0-2-2h-3l-2-2H8a2 2 0 0 0-2 2v3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M4 10h16v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8z" fill="none" stroke="currentColor" stroke-width="2"/>
                <path d="M9 14h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
              <span>Producto</span>
            </button>
          </div>
          <div class="wms-hint">Ubicación llena “Tu ubicación actual”. Producto llena el buscador.</div>
        </div>

        <div class="wms-mini">
          <div class="wms-mini-tt">Estado / última lectura</div>
          <div class="wms-pill wms-pill-block" id="lastScan">Listo para escanear</div>
          <div class="wms-mini-row">
            <button class="wms-btn wms-btn-primary" type="button" id="btnUseScan">Usar</button>
            <button class="wms-btn wms-btn-ghost" type="button" id="btnStopScan">Detener</button>
          </div>
        </div>

        <div class="wms-mini">
          <div class="wms-mini-tt">Consejo</div>
          <div class="wms-hint">Si no detecta automáticamente (por navegador/CSP), puedes copiar/pegar el valor manualmente.</div>
        </div>
      </aside>
    </div>

    <div class="wms-modal-ft">
      <button class="wms-btn wms-btn-ghost" type="button" data-close="1">Cerrar</button>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  :root{
    --ink:#0b1220;
    --muted:#64748b;
    --line:#e6eaf2;
    --line2:#eef2f7;
    --brand:#2563eb;
    --brand2:#1d4ed8;
    --shadow:0 18px 55px rgba(2,6,23,.08);
    --radius:18px;
    --ease:cubic-bezier(.2,.8,.2,1);
  }

  .ico{width:18px;height:18px;display:inline-block}

  .wms-page{
    background:
      radial-gradient(520px 260px at 0% 0%, rgba(37,99,235,.14) 0%, rgba(37,99,235,0) 72%),
      radial-gradient(520px 260px at 100% 0%, rgba(16,185,129,.10) 0%, rgba(16,185,129,0) 72%),
      linear-gradient(180deg, #f3f7ff 0%, #f6f8fc 100%);
    min-height: calc(100vh - 1px);
  }
  .wms-shell{max-width:1120px;margin:0 auto;padding:18px 14px 30px}

  .wms-header{
    display:flex;gap:12px;align-items:center;justify-content:space-between;
    flex-wrap:wrap;margin-bottom:12px;
  }
  .wms-back{
    display:inline-flex;gap:10px;align-items:center;
    padding:10px 12px;border-radius:999px;
    background:rgba(255,255,255,.85);
    border:1px solid rgba(226,232,240,.95);
    color:var(--ink);font-weight:950;text-decoration:none;
    box-shadow:0 10px 22px rgba(2,6,23,.06);
    transition:transform .14s var(--ease), box-shadow .14s var(--ease), background .14s var(--ease);
  }
  .wms-back:hover{transform:translateY(-1px);box-shadow:0 16px 34px rgba(2,6,23,.10);background:#fff}

  .wms-head-mid{flex:1 1 340px;min-width:240px}
  .wms-head-title{font-weight:1000;color:var(--ink);font-size:1.12rem;letter-spacing:.2px}
  .wms-head-sub{color:var(--muted);font-size:.92rem;margin-top:2px;line-height:1.3}
  .wms-head-actions{display:flex;gap:10px;flex-wrap:wrap}

  .wms-grid{display:grid;grid-template-columns:360px 1fr;gap:12px;align-items:start}
  .wms-card{
    background:rgba(255,255,255,.88);
    border:1px solid rgba(226,232,240,.95);
    border-radius:22px;
    padding:14px 14px;
    box-shadow:0 16px 38px rgba(2,6,23,.06);
    backdrop-filter: blur(8px);
  }
  .wms-card-span{min-width:0}
  .wms-card-h{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;margin-bottom:12px}
  .wms-card-tt{font-weight:1000;color:var(--ink);letter-spacing:.1px}
  .wms-card-tx{color:var(--muted);font-size:.88rem;margin-top:4px;line-height:1.35}

  .wms-lbl{display:block;font-weight:950;color:var(--ink);font-size:.86rem;margin-bottom:7px}
  .wms-row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
  .wms-inp{
    flex:1 1 280px;
    width:100%;
    min-height:46px;
    border:1px solid rgba(226,232,240,.95);
    border-radius:16px;
    padding:11px 12px;
    background:rgba(248,250,252,.92);
    color:#0f172a;
    transition:border-color .14s var(--ease), box-shadow .14s var(--ease), background .14s var(--ease);
  }
  .wms-inp:focus{
    outline:none;border-color:rgba(147,197,253,.95);
    box-shadow:0 0 0 4px rgba(147,197,253,.30);
    background:#fff;
  }
  .wms-hint{color:var(--muted);font-size:.78rem;margin-top:8px;line-height:1.35}

  .wms-btn{
    border:0;border-radius:999px;padding:11px 14px;font-weight:950;
    display:inline-flex;gap:8px;align-items:center;cursor:pointer;
    transition:transform .14s var(--ease), box-shadow .14s var(--ease), background .14s var(--ease), border-color .14s var(--ease), opacity .14s var(--ease);
    white-space:nowrap;
  }
  .wms-btn-primary{
    background:linear-gradient(180deg, var(--brand) 0%, var(--brand2) 100%);
    color:#eff6ff;
    box-shadow:0 18px 40px rgba(37,99,235,.28);
  }
  .wms-btn-primary:hover{transform:translateY(-1px);box-shadow:0 24px 55px rgba(37,99,235,.34)}
  .wms-btn-primary:disabled{opacity:.7;cursor:not-allowed;transform:none}
  .wms-btn-ghost{
    background:rgba(255,255,255,.92);
    border:1px solid rgba(226,232,240,.95);
    color:var(--ink);
    box-shadow:0 10px 22px rgba(2,6,23,.05);
  }
  .wms-btn-ghost:hover{transform:translateY(-1px);box-shadow:0 16px 34px rgba(2,6,23,.09);background:#fff}
  .wms-btn-sm{padding:9px 10px;font-size:.82rem}

  .wms-pill{
    font-size:.78rem;font-weight:1000;
    padding:7px 11px;border-radius:999px;
    background:#dcfce7;color:#166534;border:1px solid #bbf7d0;
    white-space:nowrap;
  }
  .wms-pill-soft{
    background:#eff6ff;color:#1e40af;border:1px solid #dbeafe;
  }
  .wms-pill-block{display:block;white-space:normal;word-break:break-word}

  .wms-spin{
    width:16px;height:16px;border-radius:999px;
    border:2px solid rgba(255,255,255,.45);
    border-top-color:#fff;display:none;
    animation:wmsSp .8s linear infinite;
  }
  @keyframes wmsSp{to{transform:rotate(360deg)}}

  .wms-results{display:flex;flex-direction:column;gap:10px;margin-top:12px}
  .wms-r{
    border:1px solid rgba(226,232,240,.95);
    border-radius:20px;
    padding:12px 12px;
    background:#fff;
    box-shadow:0 10px 22px rgba(2,6,23,.05);
    transition:transform .14s var(--ease), box-shadow .14s var(--ease), border-color .14s var(--ease);
  }
  .wms-r:hover{transform:translateY(-1px);box-shadow:0 18px 42px rgba(2,6,23,.10);border-color:#dbeafe}
  .wms-r-top{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;flex-wrap:wrap}
  .wms-r-name{font-weight:1000;color:var(--ink);line-height:1.2}
  .wms-r-meta{color:var(--muted);font-size:.82rem;margin-top:4px;line-height:1.35}
  .wms-r-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}

  .wms-badge{
    font-size:.75rem;font-weight:1000;padding:6px 10px;border-radius:999px;white-space:nowrap;
    border:1px solid var(--line2);background:#f8fafc;color:#0f172a;
  }
  .wms-badge-ok{background:#dcfce7;border-color:#bbf7d0;color:#166534}
  .wms-badge-warn{background:#fef3c7;border-color:#fde68a;color:#92400e}
  .wms-badge-bad{background:#fee2e2;border-color:#fecaca;color:#991b1b}

  .wms-locs{margin-top:10px;display:flex;gap:8px;flex-wrap:wrap}
  .wms-locchip{
    display:inline-flex;gap:6px;align-items:center;
    padding:7px 11px;border-radius:999px;border:1px solid var(--line2);
    background:#fff;font-size:.78rem;font-weight:1000;color:#0f172a;
  }
  .wms-locchip small{font-weight:950;color:var(--muted)}
  .wms-locchip strong{color:var(--brand2)}

  .wms-modal{position:fixed;inset:0;display:none;z-index:9999}
  .wms-modal[aria-hidden="false"]{display:block}
  .wms-modal-backdrop{position:absolute;inset:0;background:rgba(2,6,23,.55);backdrop-filter:blur(12px)}
  .wms-modal-card{
    position:relative;
    max-width:820px;margin:40px auto;
    background:#fff;border:1px solid rgba(226,232,240,.9);
    border-radius:24px;box-shadow:0 40px 110px rgba(2,6,23,.40);
    overflow:hidden;
  }
  .wms-modal-wide{max-width:1020px}
  .wms-modal-h{
    display:flex;justify-content:space-between;gap:10px;align-items:flex-start;
    padding:14px 16px;border-bottom:1px solid rgba(226,232,240,.9);
  }
  .wms-modal-tt{font-weight:1000;color:var(--ink)}
  .wms-modal-tx{color:var(--muted);font-size:.86rem;margin-top:2px}
  .wms-x{border:0;background:transparent;cursor:pointer;padding:6px 10px;border-radius:12px;color:var(--ink)}
  .wms-x:hover{background:#f1f5f9}
  .wms-modal-ft{
    display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;
    padding:12px 16px;border-top:1px solid rgba(226,232,240,.9);
  }

  .wms-steps{padding:12px 16px;display:flex;flex-direction:column;gap:8px}
  .wms-step{
    display:flex;gap:10px;align-items:flex-start;
    padding:11px 12px;border:1px solid rgba(226,232,240,.9);border-radius:18px;background:#f8fafc;
  }
  .wms-dot{
    width:28px;height:28px;border-radius:999px;display:flex;align-items:center;justify-content:center;
    background:#dbeafe;color:#1e40af;font-weight:1000;flex:0 0 auto;
  }
  .wms-step .tx{font-weight:950;color:#0f172a}
  .wms-step .sm{color:var(--muted);font-size:.82rem;margin-top:2px}

  .wms-scan{display:grid;grid-template-columns:1.25fr .75fr;gap:12px;padding:12px 16px}
  .wms-scan-cam{
    position:relative;border-radius:20px;overflow:hidden;border:1px solid rgba(226,232,240,.9);
    background:#0b1220;min-height:380px;
  }
  #video{width:100%;height:100%;object-fit:cover;display:block}
  .wms-scan-overlay{position:absolute;inset:0;display:flex;flex-direction:column;justify-content:center;align-items:center;pointer-events:none}
  .wms-scan-frame{
    width:min(380px, 86%);height:min(250px, 58%);
    border-radius:18px;border:2px solid rgba(255,255,255,.78);
    box-shadow:0 0 0 999px rgba(2,6,23,.28) inset;
  }
  .wms-scan-hint{margin-top:10px;color:#e2e8f0;font-weight:900;font-size:.86rem}
  .wms-scan-side{display:flex;flex-direction:column;gap:10px}
  .wms-mini{border:1px solid rgba(226,232,240,.9);border-radius:20px;padding:10px 10px;background:#fff}
  .wms-mini-tt{font-weight:1000;color:var(--ink);margin-bottom:6px}
  .wms-mini-row{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}

  @media (max-width: 980px){
    .wms-grid{grid-template-columns:1fr}
    .wms-modal-card{margin:16px 10px}
    .wms-scan{grid-template-columns:1fr}
    .wms-scan-cam{min-height:320px}
    .wms-head-actions{width:100%}
    #btnOpenScanner{width:100%;justify-content:center}
  }
  @media (max-width: 520px){
    .wms-shell{padding:14px 10px 24px}
    .wms-card{padding:12px 12px}
    .wms-btn{width:100%;justify-content:center}
    .wms-row .wms-inp{flex:1 1 100%}
  }
</style>
@endpush

@push('scripts')
<script>
  // ----------------------------
  // Config
  // ----------------------------
  const API_SEARCH   = @json(route('admin.wms.search.products'));
  const API_LOC_SCAN = @json(route('admin.wms.locations.scan'));
  const LS_FROM      = 'wms_from_code';

  // ----------------------------
  // Helpers UX
  // ----------------------------
  function beep(ok=true){
    try{
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const o = ctx.createOscillator();
      const g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.value = ok ? 880 : 220;
      g.gain.value = 0.06;
      o.start();
      setTimeout(()=>{o.stop();ctx.close();}, ok ? 80 : 140);
    }catch(e){}
  }
  function vibrate(ms=40){ try{ if(navigator.vibrate) navigator.vibrate(ms);}catch(e){} }

  function setModal(id, open){
    const m = document.getElementById(id);
    if(!m) return;
    m.setAttribute('aria-hidden', open ? 'false' : 'true');
  }

  // Cerrar modales (backdrop o botones con data-close)
  document.addEventListener('click', (e)=>{
    const close = e.target?.getAttribute?.('data-close');
    if(close){
      setModal('navModal', false);
      setModal('scanModal', false);
      stopCamera();
    }
  });

  function escapeHtml(str){
    if(str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
      .replace(/"/g,"&quot;").replace(/'/g,"&#039;");
  }

  function qtyBadge(total){
    total = Number(total||0);
    if(total <= 0) return `<span class="wms-badge wms-badge-bad">Sin stock</span>`;
    if(total <= 3) return `<span class="wms-badge wms-badge-warn">Bajo (${total})</span>`;
    return `<span class="wms-badge wms-badge-ok">Stock (${total})</span>`;
  }

  // ----------------------------
  // State: from_code
  // ----------------------------
  const chipFrom = document.getElementById('chipFrom');
  const fromInp  = document.getElementById('from_code');

  function loadFrom(){
    const v = localStorage.getItem(LS_FROM) || '';
    fromInp.value = v;
    chipFrom.textContent = v ? v : 'No definida';
    chipFrom.classList.toggle('wms-pill-soft', !v);
  }
  function saveFrom(v){
    v = (v||'').trim();
    if(v) localStorage.setItem(LS_FROM, v);
    else localStorage.removeItem(LS_FROM);
    loadFrom();
  }

  async function validateLocationCode(code){
    code = (code||'').trim();
    if(!code) return {ok:true, code:''};

    const url = API_LOC_SCAN + '?code=' + encodeURIComponent(code);
    const res = await fetch(url, {headers:{'Accept':'application/json'}});
    if(!res.ok) return {ok:false, error:'Ubicación no encontrada.'};

    const data = await res.json();
    if(!data.ok) return {ok:false, error:data.error||'Ubicación inválida'};

    return {ok:true, code:data.location?.code || code};
  }

  // ----------------------------
  // Search
  // ----------------------------
  const qInp       = document.getElementById('q');
  const btnSearch  = document.getElementById('btnSearch');
  const spinSearch = document.getElementById('spinSearch');
  const resultsEl  = document.getElementById('results');
  const chipCount  = document.getElementById('chipCount');

  function setLoading(on){
    spinSearch.style.display = on ? 'inline-block' : 'none';
    btnSearch.disabled = !!on;
  }

  async function runSearch(){
    const q = (qInp.value||'').trim();
    if(!q){
      resultsEl.innerHTML = `<div class="wms-hint">Escribe algo para buscar.</div>`;
      chipCount.textContent = '0 resultados';
      return;
    }

    const from = (localStorage.getItem(LS_FROM) || '').trim();
    setLoading(true);
    resultsEl.innerHTML = `<div class="wms-hint">Buscando…</div>`;

    const url = new URL(API_SEARCH, window.location.origin);
    url.searchParams.set('q', q);
    if(from) url.searchParams.set('from_code', from);
    url.searchParams.set('limit', '20');

    try{
      const res = await fetch(url.toString(), {headers:{'Accept':'application/json'}});
      const data = await res.json();

      if(!data.ok){
        resultsEl.innerHTML = `<div class="wms-hint">Error: ${escapeHtml(data.error||'No se pudo buscar')}</div>`;
        chipCount.textContent = '0 resultados';
        beep(false); vibrate(80);
        return;
      }

      const list = Array.isArray(data.results) ? data.results : [];
      chipCount.textContent = list.length + ' resultados';

      if(!list.length){
        resultsEl.innerHTML = `<div class="wms-hint">Sin resultados.</div>`;
        beep(false);
        return;
      }

      resultsEl.innerHTML = list.map(r => {
        const rec = r.recommended_location;
        const nav = r.nav;
        const recCode = rec?.code ? escapeHtml(rec.code) : '—';

        const locs = (r.locations||[]).slice(0, 8).map(l =>
          `<span class="wms-locchip"><strong>${escapeHtml(l.code||'—')}</strong> <small>x${Number(l.qty||0)}</small></span>`
        ).join('');

        const metaBits = [];
        if(r.sku) metaBits.push('SKU: ' + escapeHtml(r.sku));
        if(r.meli_gtin) metaBits.push('GTIN: ' + escapeHtml(r.meli_gtin));
        if(r.primary_location?.code) metaBits.push('Principal: ' + escapeHtml(r.primary_location.code));

        return `
          <div class="wms-r">
            <div class="wms-r-top">
              <div style="min-width:240px">
                <div class="wms-r-name">${escapeHtml(r.name||'—')}</div>
                <div class="wms-r-meta">${metaBits.join(' · ') || '—'}</div>
              </div>
              <div class="wms-r-actions">
                ${qtyBadge(r.total_qty)}
                ${rec?.code ? `<span class="wms-badge">Sugerida: <b>${recCode}</b></span>` : `<span class="wms-badge">Sin sugerencia</span>`}
                ${nav?.steps?.length ? `<button class="wms-btn wms-btn-primary" type="button" data-nav='${escapeHtml(JSON.stringify(nav))}'>Llévame</button>` : ``}
              </div>
            </div>
            <div class="wms-locs">${locs || `<span class="wms-hint">Sin ubicaciones con stock.</span>`}</div>
          </div>
        `;
      }).join('');

      resultsEl.querySelectorAll('button[data-nav]').forEach(btn=>{
        btn.addEventListener('click', ()=>{
          try{
            const nav = JSON.parse(btn.getAttribute('data-nav'));
            openNav(nav);
          }catch(e){}
        });
      });

      beep(true); vibrate(30);
    }catch(e){
      resultsEl.innerHTML = `<div class="wms-hint">Error de conexión.</div>`;
      beep(false); vibrate(80);
    }finally{
      setLoading(false);
    }
  }

  btnSearch.addEventListener('click', runSearch);
  qInp.addEventListener('keydown', (e)=>{ if(e.key === 'Enter') runSearch(); });

  // ----------------------------
  // Nav modal
  // ----------------------------
  let currentNav = null;
  function openNav(nav){
    currentNav = nav;
    const sub = document.getElementById('navSubtitle');
    const steps = document.getElementById('navSteps');

    sub.textContent = `${nav.from?.code || '—'} → ${nav.to?.code || '—'}`;
    steps.innerHTML = (nav.steps||[]).map((s,i)=>`
      <div class="wms-step">
        <div class="wms-dot">${i+1}</div>
        <div>
          <div class="tx">${escapeHtml(s)}</div>
          <div class="sm">Paso ${i+1} de ${(nav.steps||[]).length}</div>
        </div>
      </div>
    `).join('');

    setModal('navModal', true);
  }

  document.getElementById('btnMarkHere')?.addEventListener('click', ()=>{
    if(currentNav?.to?.code){
      saveFrom(currentNav.to.code);
      setModal('navModal', false);
      beep(true); vibrate(35);
    }
  });

  document.getElementById('btnSetFrom')?.addEventListener('click', async ()=>{
    const val = (fromInp.value||'').trim();
    if(!val){ saveFrom(''); return; }

    const v = await validateLocationCode(val);
    if(!v.ok){
      chipFrom.textContent = 'Inválida';
      chipFrom.classList.add('wms-pill-soft');
      beep(false); vibrate(90);
      alert(v.error || 'Ubicación inválida');
      return;
    }
    saveFrom(v.code);
    beep(true); vibrate(30);
  });

  document.getElementById('btnClearFrom')?.addEventListener('click', ()=>{
    saveFrom('');
    beep(true); vibrate(20);
  });

  // ----------------------------
  // Scanner (NO se cae si ZXing está bloqueado por CSP)
  // ----------------------------
  const btnOpenScanner = document.getElementById('btnOpenScanner');
  const video = document.getElementById('video');
  const lastScan = document.getElementById('lastScan');
  const btnUseScan = document.getElementById('btnUseScan');
  const btnStopScan = document.getElementById('btnStopScan');

  let scanMode = 'loc'; // loc|item
  let stream = null;
  let scanning = false;
  let lastValue = '';

  // ZXing fallback
  let zxingReader = null;
  let usingZXing = false;

  function setScanMode(m){
    scanMode = m;

    const bLoc = document.getElementById('scanModeLoc');
    const bIt  = document.getElementById('scanModeItem');

    bLoc.classList.toggle('wms-btn-primary', m==='loc');
    bLoc.classList.toggle('wms-btn-ghost', m!=='loc');

    bIt.classList.toggle('wms-btn-primary', m==='item');
    bIt.classList.toggle('wms-btn-ghost', m!=='item');
  }
  document.getElementById('scanModeLoc')?.addEventListener('click', ()=>setScanMode('loc'));
  document.getElementById('scanModeItem')?.addEventListener('click', ()=>setScanMode('item'));

  function setScanStatus(msg, ok=true){
    lastScan.textContent = msg;
    lastScan.classList.toggle('wms-pill-soft', !ok);
  }

  function isSecureContextOk(){
    return window.isSecureContext || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
  }

  function loadScriptTry(srcs){
    return new Promise(async (resolve, reject)=>{
      for(const src of srcs){
        try{
          await new Promise((res, rej)=>{
            const s = document.createElement('script');
            s.src = src;
            s.async = true;
            s.onload = res;
            s.onerror = rej;
            document.head.appendChild(s);
          });
          return resolve(true);
        }catch(e){}
      }
      reject(new Error('No se pudo cargar script externo (CSP/red/URL).'));
    });
  }

  async function startCamera(){
    if(!isSecureContextOk()){
      setScanStatus('La cámara requiere HTTPS.', false);
      throw new Error('InsecureContext');
    }
    if(!navigator.mediaDevices?.getUserMedia){
      setScanStatus('Este navegador no soporta cámara.', false);
      throw new Error('NoGetUserMedia');
    }

    stream = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: { ideal: 'environment' },
        width: { ideal: 1280 },
        height: { ideal: 720 }
      },
      audio: false
    });

    video.srcObject = stream;

    // En algunos navegadores no dispara loadedmetadata; damos timeout.
    await Promise.race([
      new Promise(res => video.addEventListener('loadedmetadata', res, {once:true})),
      new Promise(res => setTimeout(res, 900))
    ]);

    // Si play() falla por política, NO tumbamos la cámara; solo seguimos (el stream ya está).
    try{ await video.play(); }catch(e){}

    setScanStatus('Escaneando…', true);
  }

  function stopCamera(){
    scanning = false;

    try{
      if(usingZXing && zxingReader){
        zxingReader.reset?.();
      }
    }catch(e){}

    usingZXing = false;
    zxingReader = null;

    if(stream){
      stream.getTracks().forEach(t=>t.stop());
      stream = null;
    }
    video.srcObject = null;
  }

  function onDecoded(val){
    val = (val || '').trim();
    if(!val) return;
    if(val === lastValue) return;
    lastValue = val;
    setScanStatus(val, true);
    beep(true); vibrate(25);
  }

  async function loopScanBarcodeDetector(){
    if(!('BarcodeDetector' in window)) return false;

    let detector = null;
    try{
      detector = new BarcodeDetector({formats:['qr_code','ean_13','ean_8','code_128','upc_a','upc_e','code_39','itf']});
    }catch(e){
      try{ detector = new BarcodeDetector(); }
      catch(_e){ return false; }
    }

    scanning = true;

    // Si BarcodeDetector lanza error por implementación, regresamos false para intentar ZXing,
    // pero NO tumbamos la cámara.
    while(scanning){
      try{
        const codes = await detector.detect(video);
        if(codes && codes.length) onDecoded(codes[0]?.rawValue || '');
      }catch(e){
        return false;
      }
      await new Promise(r=>setTimeout(r, 120));
    }
    return true;
  }

  async function startZXingFallback(){
    // Si CSP bloquea, regresamos false SIN tirar la cámara.
    try{
      if(!window.ZXingBrowser){
        await loadScriptTry([
          'https://cdn.jsdelivr.net/npm/@zxing/browser@0.1.5/umd/index.min.js',
          'https://unpkg.com/@zxing/browser@0.1.5/umd/index.min.js'
        ]);
      }
      const Reader = window.ZXingBrowser?.BrowserMultiFormatReader;
      if(!Reader) return false;

      usingZXing = true;
      zxingReader = new Reader();
      scanning = true;

      if(zxingReader.decodeFromVideoElementContinuously){
        zxingReader.decodeFromVideoElementContinuously(video, (result, err) => {
          if(!scanning) return;
          if(result?.getText) onDecoded(result.getText());
        });
        return true;
      }

      // Fallback loop
      (async ()=>{
        while(scanning){
          try{
            const result = await zxingReader.decodeFromVideoElement(video);
            if(result?.getText) onDecoded(result.getText());
          }catch(e){}
          await new Promise(r=>setTimeout(r, 150));
        }
      })();

      return true;
    }catch(e){
      return false;
    }
  }

  async function startScanner(){
    setModal('scanModal', true);
    setScanMode('loc');
    lastValue = '';
    setScanStatus('Solicitando cámara…', true);

    // 1) cámara
    try{
      await startCamera();
    }catch(e){
      // Si el usuario negó permisos, aquí cae. No tumbamos modal, solo mostramos estado.
      console.warn('Camera error:', e);
      return;
    }

    // 2) Detector nativo
    const okNative = await loopScanBarcodeDetector();
    if(okNative) return;

    // 3) Fallback ZXing (si CSP lo bloquea, NO rompemos; solo mostramos aviso)
    const okZX = await startZXingFallback();
    if(okZX){
      setScanStatus('Escaneando…', true);
      return;
    }

    setScanStatus('No se pudo cargar el lector (CSP/navegador). Puedes copiar/pegar el código.', false);
  }

  btnOpenScanner.addEventListener('click', startScanner);

  btnStopScan.addEventListener('click', ()=>{
    stopCamera();
    setScanStatus('Detenido', false);
    beep(true);
  });

  btnUseScan.addEventListener('click', async ()=>{
    const val = (lastValue || lastScan.textContent || '').trim();
    if(!val || val === 'Listo para escanear' || val === 'Escaneando…' || val === 'Detenido'){
      beep(false); vibrate(80);
      return;
    }

    if(scanMode === 'loc'){
      const v = await validateLocationCode(val);
      if(!v.ok){
        beep(false); vibrate(90);
        alert(v.error || 'Ubicación inválida');
        return;
      }
      saveFrom(v.code);
      setModal('scanModal', false);
      stopCamera();
      beep(true); vibrate(30);
      return;
    }

    qInp.value = val;
    setModal('scanModal', false);
    stopCamera();
    beep(true); vibrate(30);
    runSearch();
  });

  // init
  loadFrom();
</script>
@endpush
