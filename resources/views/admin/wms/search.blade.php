@extends('layouts.app')

@section('title', 'WMS · Buscar producto')

@section('content')
<div class="wms-wrap fade-in-up delay-1">
  <div class="wms-header">
    <div class="wms-header-left">
      <a href="{{ route('admin.wms.home') }}" class="btn btn-icon btn-ghost" aria-label="Volver a WMS">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      </a>
      <div class="wms-head-mid">
        <div class="wms-head-title">Búsqueda Rápida</div>
        <div class="wms-head-sub">Encuentra productos por Nombre, SKU o GTIN y obtén la ruta óptima.</div>
      </div>
    </div>

    <div class="wms-head-actions">
      <button class="btn btn-primary btn-lg shadow-hover" type="button" id="btnOpenScanner">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V6a2 2 0 0 1 2-2h1M20 7V6a2 2 0 0 0-2-2h-1M4 17v1a2 2 0 0 0 2 2h1M20 17v1a2 2 0 0 1-2 2h-1"/><path d="M7 12h10"/></svg>
        Escanear Código
      </button>
    </div>
  </div>

  <div class="wms-grid">
    {{-- Columna Izquierda: Ubicación --}}
    <section class="card fade-in-up delay-2">
      <div class="card-h">
        <div>
          <div class="card-tt">Punto de Partida</div>
          <div class="card-tx">Fija tu ubicación actual para ordenar los resultados por cercanía.</div>
        </div>
        <span class="badge-status" id="chipFrom">No definida</span>
      </div>

      <div class="wms-form">
        <label class="wms-lbl" for="from_code">Código de ubicación (QR)</label>
        <div class="wms-row">
          <input class="inp" id="from_code" placeholder="Ej: A-03-S2" autocomplete="off" inputmode="text">
        </div>
        <div class="wms-row-actions">
          <button class="btn btn-outline" type="button" id="btnSetFrom">Fijar posición</button>
          <button class="btn btn-ghost" type="button" id="btnClearFrom">Limpiar</button>
        </div>
        <div class="hint">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
          Escanea el bin físico o pega el código manualmente.
        </div>
      </div>
    </section>

    {{-- Columna Derecha: Buscador --}}
    <section class="card wms-card-span fade-in-up delay-3">
      <div class="card-h">
        <div>
          <div class="card-tt">Directorio de Inventario</div>
          <div class="card-tx">Te sugeriremos la ubicación más óptima para recolectar el stock.</div>
        </div>
        <span class="badge-soft" id="chipCount">0 resultados</span>
      </div>

      <div class="wms-form">
        <div class="input-icon-large">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <input class="inp inp-lg" id="q" placeholder="Ej: Bic azul 0.7 / SKU / GTIN..." autocomplete="off" inputmode="search">
          <button class="btn btn-primary" type="button" id="btnSearch">
            <span class="wms-spin" id="spinSearch"></span>
            <span>Buscar</span>
          </button>
        </div>
      </div>

      <div class="wms-results" id="results">
        <div class="empty-state">
          <div class="empty-state-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
          </div>
          <span>Esperando consulta de inventario.</span>
        </div>
      </div>
    </section>
  </div>
</div>

{{-- Modal: Ruta “Llévame” --}}
<div class="modal" id="navModal" aria-hidden="true">
  <div class="back" data-close="1"></div>
  <div class="mcard">
    <div class="mh">
      <div>
        <div class="mtt">Ruta Sugerida</div>
        <div class="msub" id="navSubtitle">—</div>
      </div>
      <button class="x" type="button" data-close="1" aria-label="Cerrar"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="wms-steps" id="navSteps"></div>
    <div class="mf">
      <button class="btn btn-ghost" type="button" data-close="1">Cerrar</button>
      <button class="btn btn-primary" type="button" id="btnMarkHere">Confirmar Llegada</button>
    </div>
  </div>
</div>

{{-- Modal: Scanner --}}
<div class="modal" id="scanModal" aria-hidden="true">
  <div class="back" data-close="1"></div>
  <div class="mcard mcard-wide">
    <div class="mh">
      <div>
        <div class="mtt">Escáner Activo</div>
        <div class="msub">Apunta al código QR o código de barras. La detección es automática.</div>
      </div>
      <button class="x" type="button" data-close="1" aria-label="Cerrar"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>

    <div class="wms-scan">
      <div class="wms-scan-cam">
        <video id="video" playsinline webkit-playsinline muted autoplay></video>
        <div class="wms-scan-overlay" aria-hidden="true">
          <div class="wms-scan-frame"></div>
          <div class="wms-scan-hint">Alinea el código en el recuadro</div>
        </div>
      </div>

      <aside class="wms-scan-side">
        <div class="wms-mini">
          <div class="wms-mini-tt">Modo de Escaneo</div>
          <div class="wms-mini-row">
            <button class="btn btn-ghost btn-sm flex-1" type="button" id="scanModeLoc">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> Ubicación
            </button>
            <button class="btn btn-ghost btn-sm flex-1" type="button" id="scanModeItem">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10V7a2 2 0 0 0-2-2h-3l-2-2H8a2 2 0 0 0-2 2v3"/><path d="M4 10h16v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8z"/><path d="M9 14h6"/></svg> Producto
            </button>
          </div>
        </div>

        <div class="wms-mini">
          <div class="wms-mini-tt">Lectura Actual</div>
          <div class="badge-status block-text" id="lastScan">Esperando código...</div>
          <div class="wms-mini-row mt-3">
            <button class="btn btn-primary flex-1" type="button" id="btnUseScan">Procesar</button>
            <button class="btn btn-outline flex-1" type="button" id="btnStopScan">Pausar</button>
          </div>
          <div class="hint mt-2" id="scanTech">Motor: Iniciando...</div>
        </div>
      </aside>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

  :root {
    --bg-page: #f8fafc;
    --surface: #ffffff;
    --ink: #0f172a;
    --muted: #64748b;
    --line: #e2e8f0;
    --line-soft: #f1f5f9;
    --brand: #0f172a; 
    --brand-hover: #1e293b;
    --accent: #2563eb;
    
    --radius-lg: 16px;
    --radius-md: 10px;
    --shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.06);
    --shadow-md: 0 4px 12px -2px rgba(15, 23, 42, 0.08);
    --shadow-modal: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
    --bezier: cubic-bezier(0.16, 1, 0.3, 1);
  }

  body { font-family: 'Inter', system-ui, sans-serif; background-color: var(--bg-page); color: var(--ink); }

  /* Animaciones Base */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .fade-in-up { opacity: 0; animation: fadeUp 0.6s var(--bezier) forwards; }
  .delay-1 { animation-delay: 0.1s; }
  .delay-2 { animation-delay: 0.2s; }
  .delay-3 { animation-delay: 0.3s; }

  /* Layout */
  .wms-wrap { max-width: 1200px; margin: 0 auto; padding: 32px 20px; }
  
  .wms-header { display: flex; gap: 16px; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; margin-bottom: 24px; }
  .wms-header-left { display: flex; gap: 16px; align-items: center; }
  .wms-head-mid { flex: 1 1 auto; }
  .wms-head-title { font-weight: 700; color: var(--ink); font-size: 1.5rem; letter-spacing: -0.02em; }
  .wms-head-sub { color: var(--muted); font-size: 0.95rem; margin-top: 4px; }

  .wms-grid { display: grid; grid-template-columns: 360px 1fr; gap: 24px; align-items: start; }
  
  /* Tarjetas */
  .card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; gap: 16px; }
  .wms-card-span { min-width: 0; }
  .card-h { display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; border-bottom: 1px solid var(--line-soft); padding-bottom: 16px; }
  .card-tt { font-weight: 600; color: var(--ink); font-size: 1.1rem; }
  .card-tx { color: var(--muted); font-size: 0.85rem; margin-top: 4px; line-height: 1.4; }

  /* Botones Premium */
  .btn { 
    border: 0; border-radius: 8px; padding: 10px 16px; font-weight: 500; font-size: 0.875rem;
    display: inline-flex; gap: 8px; align-items: center; justify-content: center; cursor: pointer; 
    transition: all 0.2s var(--bezier); font-family: inherit; white-space: nowrap; text-decoration: none;
  }
  .btn-lg { padding: 0 20px; min-height: 48px; font-size: 0.95rem; }
  .btn-sm { padding: 8px 12px; font-size: 0.8rem; }
  .btn-primary { background: var(--brand); color: #fff; }
  .btn-primary:hover { background: var(--brand-hover); transform: translateY(-1px); box-shadow: var(--shadow-md); }
  .btn-primary:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
  .btn-ghost { background: var(--surface); border: 1px solid var(--line); color: var(--ink); box-shadow: var(--shadow-sm); }
  .btn-ghost:hover { background: var(--line-soft); border-color: #cbd5e1; }
  .btn-outline { background: transparent; border: 1px solid var(--line); color: var(--ink); }
  .btn-outline:hover { background: var(--line-soft); }
  .btn-icon { padding: 12px; border-radius: 12px; }
  .flex-1 { flex: 1; }

  /* Formularios e Inputs */
  .wms-lbl { display: block; font-weight: 500; color: var(--ink); font-size: 0.85rem; margin-bottom: 8px; }
  .wms-row { display: flex; gap: 12px; align-items: center; margin-bottom: 12px; }
  .wms-row-actions { display: flex; gap: 8px; }
  
  .inp { width: 100%; min-height: 44px; border: 1px solid var(--line); border-radius: var(--radius-md); padding: 10px 14px; background: var(--surface); color: var(--ink); font-family: inherit; font-size: 0.95rem; transition: all 0.2s ease; box-shadow: inset 0 2px 4px 0 rgb(0 0 0 / 0.02); }
  .inp:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }

  .input-icon-large { position: relative; display: flex; gap: 12px; align-items: center; }
  .input-icon-large > svg { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
  .inp-lg { min-height: 52px; padding-left: 48px; font-size: 1.05rem; }

  .hint { display: flex; gap: 8px; align-items: flex-start; color: var(--muted); font-size: 0.8rem; line-height: 1.4; }
  .hint svg { flex-shrink: 0; margin-top: 2px; color: #94a3b8; }
  .mt-2 { margin-top: 8px; }
  .mt-3 { margin-top: 12px; }

  /* Badges & Pills */
  .badge-status { font-size: 0.75rem; font-weight: 600; padding: 6px 12px; border-radius: 999px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; white-space: nowrap; }
  .badge-soft { font-size: 0.75rem; font-weight: 600; padding: 6px 12px; border-radius: 999px; background: #eff6ff; color: #1e40af; border: 1px solid #dbeafe; white-space: nowrap; }
  .block-text { display: block; white-space: normal; word-break: break-word; text-align: center; }

  /* Spinner */
  .wms-spin { width: 16px; height: 16px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; display: none; animation: spin 0.8s linear infinite; }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* Resultados Inyectados por JS */
  .wms-results { display: flex; flex-direction: column; gap: 16px; margin-top: 16px; }
  
  .result-card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-md); padding: 20px; box-shadow: 0 2px 4px rgba(15,23,42,0.04); transition: border-color 0.2s ease, box-shadow 0.2s ease; }
  .result-card:hover { border-color: #cbd5e1; box-shadow: var(--shadow-sm); }
  
  .result-top { display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; flex-wrap: wrap; margin-bottom: 16px; border-bottom: 1px dashed var(--line-soft); padding-bottom: 16px; }
  .result-title { font-weight: 600; color: var(--ink); font-size: 1.1rem; line-height: 1.3; }
  .result-meta { color: var(--muted); font-size: 0.85rem; margin-top: 6px; font-family: ui-monospace, monospace; }
  
  .result-actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
  .result-badge { font-size: 0.75rem; font-weight: 600; padding: 6px 12px; border-radius: 999px; white-space: nowrap; }
  .bg-ok { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
  .bg-warn { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
  .bg-bad { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
  .bg-outline { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }

  .result-locs { display: flex; gap: 10px; flex-wrap: wrap; }
  .loc-chip { display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 8px; border: 1px solid var(--line); background: var(--bg-page); font-size: 0.85rem; font-family: ui-monospace, monospace; }
  .loc-chip strong { color: var(--ink); font-weight: 600; }
  .loc-chip small { color: var(--muted); font-weight: 500; font-family: 'Inter', sans-serif; }

  /* Empty State */
  .empty-state { display: flex; flex-direction: column; align-items: center; gap: 12px; color: #94a3b8; padding: 40px 20px; text-align: center; border: 2px dashed var(--line); border-radius: var(--radius-md); }
  .empty-state-icon { background: #f1f5f9; padding: 16px; border-radius: 50%; color: #94a3b8; }

  /* Modales Estilo Framer Motion */
  .modal { position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px; opacity: 0; visibility: hidden; transition: opacity 0.3s var(--bezier), visibility 0.3s; }
  .modal[aria-hidden="false"] { opacity: 1; visibility: visible; }
  .back { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); }
  
  .mcard { position: relative; width: 100%; max-width: 600px; background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-lg); box-shadow: var(--shadow-modal); overflow: hidden; transform: scale(0.95) translateY(10px); transition: transform 0.4s var(--bezier); }
  .mcard-wide { max-width: 900px; }
  .modal[aria-hidden="false"] .mcard { transform: scale(1) translateY(0); }
  
  .mh { display: flex; justify-content: space-between; align-items: flex-start; padding: 20px 24px; border-bottom: 1px solid var(--line-soft); }
  .mtt { font-weight: 600; color: var(--ink); font-size: 1.1rem; }
  .msub { color: var(--muted); font-size: 0.85rem; margin-top: 4px; }
  .x { border: 0; background: transparent; color: var(--muted); cursor: pointer; padding: 6px; border-radius: 8px; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
  .x:hover { background: var(--line-soft); color: var(--ink); }
  
  .mf { display: flex; justify-content: flex-end; gap: 12px; padding: 16px 24px; background: #fafafa; border-top: 1px solid var(--line-soft); }

  /* Pasos de Ruta */
  .wms-steps { padding: 24px; display: flex; flex-direction: column; gap: 12px; }
  .wms-step { display: flex; gap: 16px; align-items: flex-start; padding: 16px; border: 1px solid var(--line); border-radius: var(--radius-md); background: var(--bg-page); }
  .wms-dot { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #e0e7ff; color: #4338ca; font-weight: 600; font-size: 0.9rem; flex: 0 0 auto; }
  .wms-step .tx { font-weight: 600; color: var(--ink); line-height: 1.4; }
  .wms-step .sm { color: var(--muted); font-size: 0.8rem; margin-top: 4px; }

  /* Escáner Premium */
  .wms-scan { display: grid; grid-template-columns: 1.4fr 0.6fr; gap: 24px; padding: 24px; }
  .wms-scan-cam { position: relative; border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--line); background: #000; min-height: 380px; }
  #video { width: 100%; height: 100%; object-fit: cover; display: block; }
  
  .wms-scan-overlay { position: absolute; inset: 0; display: flex; flex-direction: column; justify-content: center; align-items: center; pointer-events: none; background: rgba(0,0,0,0.2); }
  .wms-scan-frame { width: min(300px, 80%); height: min(200px, 50%); border-radius: 12px; border: 2px solid rgba(255,255,255,0.8); box-shadow: 0 0 0 9999px rgba(0,0,0,0.4); }
  .wms-scan-hint { margin-top: 16px; color: #fff; font-weight: 500; font-size: 0.9rem; text-shadow: 0 1px 2px rgba(0,0,0,0.5); }
  
  .wms-scan-side { display: flex; flex-direction: column; gap: 16px; }
  .wms-mini { border: 1px solid var(--line); border-radius: var(--radius-md); padding: 16px; background: var(--bg-page); }
  .wms-mini-tt { font-weight: 600; color: var(--ink); font-size: 0.9rem; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.05em; }
  .wms-mini-row { display: flex; gap: 8px; flex-wrap: wrap; }

  @media (max-width: 980px) {
    .wms-grid { grid-template-columns: 1fr; }
    .wms-scan { grid-template-columns: 1fr; gap: 16px; padding: 16px; }
    .wms-scan-cam { min-height: 300px; }
  }
  @media (max-width: 600px) {
    .wms-header-left { flex-direction: column; align-items: flex-start; gap: 12px; }
    .wms-head-actions { width: 100%; }
    .wms-head-actions button { width: 100%; }
    .wms-row { flex-direction: column; align-items: stretch; }
  }
</style>
@endpush

@push('scripts')
<script>
  // La lógica base de JavaScript se mantiene intacta para la cámara y consultas.
  // Modificamos únicamente el string de HTML renderizado en runSearch() para 
  // que coincida con la nueva estructura CSS minimalista.

  const API_SEARCH    = @json(route('admin.wms.search.products'));
  const API_LOC_SCAN  = @json(route('admin.wms.locations.scan'));
  const API_ITEM_SCAN = @json(route('admin.wms.products.scan'));
  const LS_FROM       = 'wms_from_code';

  function beep(ok=true){
    try{
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const o = ctx.createOscillator();
      const g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.value = ok ? 880 : 220;
      g.gain.value = 0.04;
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

  document.addEventListener('click', (e)=>{
    const close = e.target?.closest('[data-close]');
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
    if(total <= 0) return `<span class="result-badge bg-bad">Sin stock</span>`;
    if(total <= 3) return `<span class="result-badge bg-warn">Stock Bajo (${total})</span>`;
    return `<span class="result-badge bg-ok">En Stock (${total})</span>`;
  }

  const chipFrom = document.getElementById('chipFrom');
  const fromInp  = document.getElementById('from_code');

  function loadFrom(){
    const v = localStorage.getItem(LS_FROM) || '';
    fromInp.value = v;
    chipFrom.textContent = v ? v : 'No definida';
    chipFrom.className = v ? 'badge-soft' : 'badge-status';
  }
  function saveFrom(v){
    v = (v||'').trim();
    if(v) localStorage.setItem(LS_FROM, v);
    else localStorage.removeItem(LS_FROM);
    loadFrom();
  }

  function extractLocationToken(raw){
    const v = String(raw || '').trim();
    if(!v) return {type:'empty', value:''};
    const mCode = v.match(/([A-Z0-9]+(?:-[A-Z0-9]+){3,10})/i);
    if(mCode) return {type:'code', value:mCode[1].toUpperCase()};
    const mId = v.match(/\/locations\/(\d+)(?:\/page)?/i);
    if(mId) return {type:'id', value:mId[1]};
    return {type:'raw', value:v};
  }

  async function validateLocationAny(raw){
    const tok = extractLocationToken(raw);
    if(tok.type === 'empty') return {ok:true, code:''};

    const url = new URL(API_LOC_SCAN, window.location.origin);
    if(tok.type === 'code') url.searchParams.set('code', tok.value);
    else if(tok.type === 'id') url.searchParams.set('id', tok.value);
    else url.searchParams.set('raw', tok.value);

    const res = await fetch(url.toString(), {headers:{'Accept':'application/json'}});
    if(!res.ok) return {ok:false, error:'Ubicación no encontrada.'};

    const data = await res.json();
    if(!data.ok) return {ok:false, error:data.error || 'Ubicación inválida'};

    return {ok:true, code:data.location?.code || ''};
  }

  async function resolveProductFromRaw(raw){
    const v = String(raw || '').trim();
    if(!v) return {ok:false, error:'Lectura vacía.'};

    const url = new URL(API_ITEM_SCAN, window.location.origin);
    url.searchParams.set('raw', v);

    const res = await fetch(url.toString(), {headers:{'Accept':'application/json'}});
    if(!res.ok) return {ok:false, error:'Producto no encontrado.'};

    const data = await res.json();
    if(!data.ok) return {ok:false, error:data.error || 'Producto no encontrado.'};

    const item = data.item || {};
    const token = (item.gtin || item.sku || item.name || v);
    return {ok:true, token, item};
  }

  const qInp       = document.getElementById('q');
  const btnSearch  = document.getElementById('btnSearch');
  const spinSearch = document.getElementById('spinSearch');
  const resultsEl  = document.getElementById('results');
  const chipCount  = document.getElementById('chipCount');

  function setLoading(on){
    spinSearch.style.display = on ? 'inline-block' : 'none';
    btnSearch.disabled = !!on;
  }

  function getEmptyStateHtml(text) {
    return `<div class="empty-state"><div class="empty-state-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg></div><span>${text}</span></div>`;
  }

  async function runSearch(){
    const q = (qInp.value||'').trim();
    if(!q){
      resultsEl.innerHTML = getEmptyStateHtml('Escribe un nombre o código para buscar.');
      chipCount.textContent = '0 resultados';
      return;
    }

    const from = (localStorage.getItem(LS_FROM) || '').trim();
    setLoading(true);
    resultsEl.innerHTML = getEmptyStateHtml('Buscando en inventario...');

    const url = new URL(API_SEARCH, window.location.origin);
    url.searchParams.set('q', q);
    if(from) url.searchParams.set('from_code', from);
    url.searchParams.set('limit', '20');

    try{
      const res = await fetch(url.toString(), {headers:{'Accept':'application/json'}});
      const data = await res.json();

      if(!data.ok){
        resultsEl.innerHTML = getEmptyStateHtml(`Error: ${escapeHtml(data.error||'No se pudo buscar')}`);
        chipCount.textContent = '0 resultados';
        beep(false); vibrate(80);
        return;
      }

      const list = Array.isArray(data.results) ? data.results : [];
      chipCount.textContent = list.length + ' resultados';

      if(!list.length){
        resultsEl.innerHTML = getEmptyStateHtml('No se encontraron coincidencias.');
        beep(false);
        return;
      }

      // Nuevo renderizado HTML inyectado
      resultsEl.innerHTML = list.map((r, index) => {
        const rec = r.recommended_location;
        const nav = r.nav;
        const recCode = rec?.code ? escapeHtml(rec.code) : '—';

        const locs = (r.locations||[]).slice(0, 8).map(l =>
          `<span class="loc-chip"><strong>${escapeHtml(l.code||'—')}</strong> <small>x${Number(l.qty||0)}</small></span>`
        ).join('');

        const metaBits = [];
        if(r.sku) metaBits.push('SKU: ' + escapeHtml(r.sku));
        if(r.meli_gtin) metaBits.push('GTIN: ' + escapeHtml(r.meli_gtin));
        if(r.primary_location?.code) metaBits.push('Ref: ' + escapeHtml(r.primary_location.code));

        // Añadimos delay progresivo a cada resultado para el efecto cascada
        const animDelay = `animation-delay: ${0.1 * (index + 1)}s`;

        return `
          <div class="result-card fade-in-up" style="${animDelay}">
            <div class="result-top">
              <div style="flex: 1 1 240px">
                <div class="result-title">${escapeHtml(r.name||'—')}</div>
                <div class="result-meta">${metaBits.join(' &middot; ') || '—'}</div>
              </div>
              <div class="result-actions">
                ${qtyBadge(r.total_qty)}
                ${rec?.code ? `<span class="result-badge bg-outline">Sugerencia: <b>${recCode}</b></span>` : `<span class="result-badge bg-outline">Sin sugerencia</span>`}
                ${nav?.steps?.length ? `<button class="btn btn-primary btn-sm" type="button" data-nav='${escapeHtml(JSON.stringify(nav))}'>📍 Ruta Llévame</button>` : ``}
              </div>
            </div>
            <div class="result-locs">${locs || `<div class="hint">Sin ubicaciones con stock físico asignado.</div>`}</div>
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
      console.error(e);
      resultsEl.innerHTML = getEmptyStateHtml('Error de conexión con el servidor.');
      beep(false); vibrate(80);
    }finally{
      setLoading(false);
    }
  }

  btnSearch.addEventListener('click', runSearch);
  qInp.addEventListener('keydown', (e)=>{ if(e.key === 'Enter') runSearch(); });

  let currentNav = null;
  function openNav(nav){
    currentNav = nav;
    const sub = document.getElementById('navSubtitle');
    const steps = document.getElementById('navSteps');

    sub.textContent = `Origen: ${nav.from?.code || '—'} → Destino: ${nav.to?.code || '—'}`;
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

    const v = await validateLocationAny(val);
    if(!v.ok){
      chipFrom.textContent = 'Inválida';
      chipFrom.className = 'badge-status';
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

  // Scanner Logic
  const btnOpenScanner = document.getElementById('btnOpenScanner');
  const video = document.getElementById('video');
  const lastScan = document.getElementById('lastScan');
  const btnUseScan = document.getElementById('btnUseScan');
  const btnStopScan = document.getElementById('btnStopScan');
  const scanTech = document.getElementById('scanTech');

  let scanMode = 'loc'; 
  let stream = null;
  let scanning = false;
  let lastValue = '';
  let autoApplied = false;

  let zxingControls = null; 
  let zxingReader = null;

  function setScanMode(m){
    scanMode = m;
    const bLoc = document.getElementById('scanModeLoc');
    const bIt  = document.getElementById('scanModeItem');

    bLoc.className = m === 'loc' ? 'btn btn-primary btn-sm flex-1' : 'btn btn-ghost btn-sm flex-1';
    bIt.className = m === 'item' ? 'btn btn-primary btn-sm flex-1' : 'btn btn-ghost btn-sm flex-1';
  }
  
  document.getElementById('scanModeLoc')?.addEventListener('click', ()=>setScanMode('loc'));
  document.getElementById('scanModeItem')?.addEventListener('click', ()=>setScanMode('item'));

  function setScanStatus(msg, ok=true){
    lastScan.textContent = msg;
    lastScan.className = ok ? 'badge-soft block-text' : 'badge-status block-text';
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
            s.src = src; s.async = true; s.onload = res; s.onerror = rej;
            document.head.appendChild(s);
          });
          return resolve(true);
        }catch(e){ console.warn('No se pudo cargar', src, e); }
      }
      reject(new Error('No se pudo cargar ZXing'));
    });
  }

  async function startCamera(){
    if(!isSecureContextOk()){
      setScanStatus('La cámara requiere HTTPS.', false);
      throw new Error('InsecureContext');
    }
    if(!navigator.mediaDevices?.getUserMedia){
      setScanStatus('Navegador sin soporte de cámara.', false);
      throw new Error('NoGetUserMedia');
    }

    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
      audio: false
    });

    video.srcObject = stream;
    await Promise.race([
      new Promise(res => video.addEventListener('loadedmetadata', res, {once:true})),
      new Promise(res => setTimeout(res, 900))
    ]);
    try{ await video.play(); }catch(e){}
    setScanStatus('Escaneando...', true);
  }

  function stopCamera(){
    scanning = false;
    if(zxingControls && typeof zxingControls.stop === 'function'){
      try{ zxingControls.stop(); }catch(e){}
    }
    zxingControls = null; zxingReader = null;
    if(stream){ stream.getTracks().forEach(t=>t.stop()); stream = null; }
    video.srcObject = null;
  }

  async function applyDecodedNow(raw){
    if(autoApplied) return;
    autoApplied = true;

    if(scanMode === 'loc'){
      const v = await validateLocationAny(raw);
      if(!v.ok){
        autoApplied = false;
        beep(false); vibrate(80);
        alert(v.error || 'Ubicación inválida');
        return;
      }
      saveFrom(v.code);
      setModal('scanModal', false);
      stopCamera();
      beep(true); vibrate(30);
      return;
    }

    const prod = await resolveProductFromRaw(raw);
    if(!prod.ok){
      autoApplied = false;
      beep(false); vibrate(80);
      alert(prod.error || 'Producto no encontrado.');
      return;
    }

    qInp.value = prod.token;
    setModal('scanModal', false);
    stopCamera();
    beep(true); vibrate(30);
    runSearch();
  }

  function onDecoded(val){
    val = (val || '').trim();
    if(!val || val === lastValue) return;
    
    lastValue = val;
    setScanStatus(val, true);
    beep(true); vibrate(25);
    applyDecodedNow(val);
  }

  async function loopScanBarcodeDetector(){
    if(!('BarcodeDetector' in window)) return false;
    let detector = null;
    try{ detector = new BarcodeDetector({formats:['qr_code','ean_13','ean_8','code_128','upc_a','upc_e','code_39','itf','pdf417','data_matrix']}); }
    catch(e){ try{ detector = new BarcodeDetector(); } catch(_e){ return false; } }

    scanTech.textContent = 'Motor: BarcodeDetector Nativo';
    scanning = true;
    while(scanning){
      try{
        const codes = await detector.detect(video);
        if(codes && codes.length){ onDecoded(codes[0]?.rawValue || ''); }
      }catch(e){ return false; }
      await new Promise(r=>setTimeout(r, 120));
    }
    return true;
  }

  async function startZXingFallback(){
    try{
      if(!window.ZXingBrowser && !window.ZXing){
        const localSrc = @json(asset('vendor/zxing/index.min.js'));
        await loadScriptTry([localSrc, 'https://cdn.jsdelivr.net/npm/@zxing/browser@0.1.5/umd/index.min.js']);
      }

      const ns = window.ZXingBrowser || window.ZXing;
      if(!ns) return false;
      const Reader = ns.BrowserMultiFormatReader || ns.BrowserQRCodeReader;
      if(!Reader) return false;

      zxingReader = new Reader();
      scanning = true;
      scanTech.textContent = 'Motor: ZXing Fallback';

      if(typeof zxingReader.decodeFromVideoDevice === 'function'){
        zxingControls = await zxingReader.decodeFromVideoDevice(null, 'video', (result, err) => {
          if(!scanning) return;
          if(result && result.getText) onDecoded(result.getText());
        });
      }else if(typeof zxingReader.decodeFromVideoElementContinuously === 'function'){
        zxingReader.decodeFromVideoElementContinuously(video, (result, err) => {
          if(!scanning) return;
          if(result && result.getText) onDecoded(result.getText());
        });
      }else{ return false; }

      return true;
    }catch(e){ return false; }
  }

  async function startScanner(){
    setModal('scanModal', true);
    setScanMode('loc');
    lastValue = ''; autoApplied = false;
    scanTech.textContent = 'Motor: Iniciando...';
    setScanStatus('Solicitando permisos de cámara...', true);

    try{ await startCamera(); } catch(e){ return; }

    const okNative = await loopScanBarcodeDetector();
    if(okNative) return;

    const okZX = await startZXingFallback();
    if(okZX){ setScanStatus('Escaneando...', true); return; }

    scanTech.textContent = 'Motor: No Disponible';
    setScanStatus('Error en lector. Usa búsqueda manual.', false);
  }

  btnOpenScanner.addEventListener('click', startScanner);
  btnStopScan.addEventListener('click', ()=>{ stopCamera(); setScanStatus('Pausado', false); beep(true); });

  btnUseScan.addEventListener('click', async ()=>{
    const val = (lastValue || lastScan.textContent || '').trim();
    if(!val || val.includes('...') || val === 'Pausado'){ beep(false); vibrate(80); return; }
    autoApplied = false;
    await applyDecodedNow(val);
  });

  loadFrom();
</script>
@endpush