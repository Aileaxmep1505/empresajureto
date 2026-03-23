@extends('layouts.app')

@section('title', 'WMS · Búsqueda de producto')

@section('content')
<div class="pf-wrap">
  <div class="pf-head">
    <div class="pf-head-left">
      <a href="{{ url('/admin/wms') }}" class="pf-btn pf-btn-ghost">Volver al WMS</a>

      <div>
        <div class="pf-title">Búsqueda de producto</div>
        <div class="pf-sub">
          Consulta productos por nombre, SKU, ID o código escaneado. Visualiza ubicaciones, existencias y cajas en flujo rápido.
        </div>
      </div>
    </div>

    <div class="pf-head-actions">
      <a href="{{ url('/admin/wms/locations-view') }}" class="pf-btn pf-btn-ghost">Ubicaciones</a>
      <a href="{{ url('/admin/wms/layout-editor') }}" class="pf-btn pf-btn-ghost">Layout</a>
      <a href="{{ url('/admin/wms/heatmap') }}" class="pf-btn pf-btn-ghost">Heatmap</a>
    </div>
  </div>

  <div class="pf-search-card">
    <div class="pf-search-row">
      <div class="pf-search-wrap" id="searchWrap">
        <input
          type="text"
          id="q"
          class="pf-search-input"
          placeholder="Buscar por nombre, SKU, ID o código..."
          autocomplete="off"
          autofocus
        >

        <div class="pf-suggest" id="suggestBox" hidden></div>
      </div>

      <button type="button" class="pf-btn pf-btn-primary" id="btnSearch">Buscar</button>
      <button type="button" class="pf-btn pf-btn-ghost" id="btnClear">Limpiar</button>
    </div>

    <div class="pf-help">
      El buscador muestra sugerencias en tiempo real con coincidencias registradas en la base de datos.
    </div>
  </div>

  <div class="pf-kpis" id="kpis" style="display:none">
    <div class="pf-kpi">
      <div class="pf-kpi-label">Productos encontrados</div>
      <div class="pf-kpi-value" id="kpiItems">0</div>
    </div>

    <div class="pf-kpi">
      <div class="pf-kpi-label">Piezas en ubicaciones</div>
      <div class="pf-kpi-value" id="kpiUnits">0</div>
    </div>

    <div class="pf-kpi">
      <div class="pf-kpi-label">Ubicaciones activas</div>
      <div class="pf-kpi-value" id="kpiLocations">0</div>
    </div>

    <div class="pf-kpi">
      <div class="pf-kpi-label">Unidades en flujo rápido</div>
      <div class="pf-kpi-value" id="kpiFastFlow">0</div>
    </div>
  </div>

  <div id="results">
    <div class="pf-empty-main">
      Introduce un nombre, SKU o código escaneado para consultar el inventario.
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  :root{
    --pf-bg:#f6f8fc;
    --pf-card:#ffffff;
    --pf-ink:#0f172a;
    --pf-muted:#64748b;
    --pf-line:#e5e7eb;
    --pf-line2:#eef2f7;
    --pf-brand:#1d4ed8;
    --pf-brand-soft:#dbeafe;
    --pf-good:#166534;
    --pf-good-bg:#dcfce7;
    --pf-warn:#92400e;
    --pf-warn-bg:#fef3c7;
    --pf-bad:#b91c1c;
    --pf-bad-bg:#fee2e2;
    --pf-surface:#f8fafc;
    --pf-shadow:0 20px 60px rgba(15,23,42,.07);
    --pf-radius:22px;
  }

  .pf-wrap{
    max-width:1440px;
    margin:0 auto;
    padding:22px 16px 34px;
  }

  .pf-head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:16px;
    flex-wrap:wrap;
    margin-bottom:18px;
  }

  .pf-head-left{
    display:flex;
    gap:14px;
    align-items:flex-start;
    flex-wrap:wrap;
  }

  .pf-head-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
  }

  .pf-title{
    color:var(--pf-ink);
    font-size:2rem;
    line-height:1.05;
    letter-spacing:-.03em;
    font-weight:950;
  }

  .pf-sub{
    color:var(--pf-muted);
    font-size:.96rem;
    margin-top:6px;
    max-width:860px;
  }

  .pf-btn{
    border:1px solid var(--pf-line);
    background:#fff;
    color:var(--pf-ink);
    min-height:46px;
    padding:0 16px;
    border-radius:14px;
    font-weight:900;
    font-size:.92rem;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    transition:transform .12s ease, box-shadow .12s ease, border-color .12s ease;
    white-space:nowrap;
  }

  .pf-btn:hover{
    transform:translateY(-1px);
    box-shadow:0 10px 22px rgba(15,23,42,.05);
  }

  .pf-btn-primary{
    background:var(--pf-brand);
    color:#fff;
    border-color:var(--pf-brand);
    box-shadow:0 14px 28px rgba(29,78,216,.20);
  }

  .pf-search-card,
  .pf-result-card,
  .pf-kpi{
    background:var(--pf-card);
    border:1px solid var(--pf-line);
    box-shadow:var(--pf-shadow);
  }

  .pf-search-card{
    border-radius:var(--pf-radius);
    padding:16px;
    margin-bottom:16px;
  }

  .pf-search-row{
    display:flex;
    gap:12px;
    align-items:flex-start;
    flex-wrap:wrap;
  }

  .pf-search-wrap{
    position:relative;
    flex:1 1 520px;
  }

  .pf-search-input{
    width:100%;
    min-height:56px;
    border:1px solid var(--pf-line);
    background:var(--pf-surface);
    border-radius:16px;
    padding:0 16px;
    font-size:1rem;
    color:var(--pf-ink);
    outline:none;
    transition:border-color .12s ease, box-shadow .12s ease, background .12s ease;
  }

  .pf-search-input:focus{
    border-color:#93c5fd;
    box-shadow:0 0 0 4px rgba(147,197,253,.28);
    background:#fff;
  }

  .pf-help{
    margin-top:12px;
    color:var(--pf-muted);
    font-size:.84rem;
  }

  .pf-suggest{
    position:absolute;
    top:calc(100% + 8px);
    left:0;
    right:0;
    background:#fff;
    border:1px solid var(--pf-line);
    border-radius:18px;
    box-shadow:0 18px 40px rgba(15,23,42,.12);
    overflow:hidden;
    z-index:50;
    max-height:380px;
    overflow-y:auto;
  }

  .pf-suggest-empty{
    padding:14px 16px;
    color:var(--pf-muted);
    font-size:.9rem;
  }

  .pf-suggest-item{
    width:100%;
    border:0;
    background:#fff;
    text-align:left;
    padding:14px 16px;
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:14px;
    cursor:pointer;
    transition:background .12s ease;
    border-bottom:1px solid var(--pf-line2);
  }

  .pf-suggest-item:last-child{
    border-bottom:0;
  }

  .pf-suggest-item:hover,
  .pf-suggest-item.is-active{
    background:#f8fafc;
  }

  .pf-suggest-main{
    min-width:0;
  }

  .pf-suggest-name{
    color:var(--pf-ink);
    font-weight:900;
    font-size:.92rem;
    line-height:1.25;
  }

  .pf-suggest-sub{
    color:var(--pf-muted);
    font-size:.8rem;
    margin-top:4px;
    line-height:1.35;
    word-break:break-word;
  }

  .pf-suggest-meta{
    flex:none;
    color:#0f172a;
    background:#f8fafc;
    border:1px solid var(--pf-line);
    border-radius:999px;
    padding:7px 10px;
    font-size:.76rem;
    font-weight:900;
    white-space:nowrap;
  }

  .pf-kpis{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:14px;
    margin-bottom:16px;
  }

  .pf-kpi{
    border-radius:18px;
    padding:16px 18px;
  }

  .pf-kpi-label{
    color:var(--pf-muted);
    font-size:.82rem;
    font-weight:800;
  }

  .pf-kpi-value{
    color:var(--pf-ink);
    font-size:1.7rem;
    font-weight:950;
    margin-top:6px;
    letter-spacing:-.03em;
  }

  .pf-empty-main{
    background:#fff;
    border:1px dashed #d8e0ea;
    border-radius:24px;
    color:var(--pf-muted);
    text-align:center;
    padding:46px 20px;
    font-weight:800;
  }

  .pf-result-card{
    border-radius:24px;
    overflow:hidden;
    margin-bottom:18px;
  }

  .pf-result-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:16px;
    flex-wrap:wrap;
    padding:18px;
    border-bottom:1px solid var(--pf-line2);
  }

  .pf-product-title{
    font-size:1.22rem;
    line-height:1.2;
    color:var(--pf-ink);
    font-weight:950;
    letter-spacing:-.02em;
  }

  .pf-product-sub{
    color:var(--pf-muted);
    font-size:.88rem;
    margin-top:5px;
  }

  .pf-badges{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    margin-top:12px;
  }

  .pf-badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:32px;
    padding:0 11px;
    border-radius:999px;
    font-size:.76rem;
    font-weight:900;
    white-space:nowrap;
    border:1px solid transparent;
  }

  .pf-badge-soft{
    background:var(--pf-brand-soft);
    color:#1d4ed8;
  }

  .pf-badge-success{
    background:var(--pf-good-bg);
    color:var(--pf-good);
  }

  .pf-badge-warn{
    background:var(--pf-warn-bg);
    color:var(--pf-warn);
  }

  .pf-summary{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:12px;
    min-width:480px;
  }

  .pf-mini{
    background:var(--pf-surface);
    border:1px solid var(--pf-line2);
    border-radius:16px;
    padding:14px;
  }

  .pf-mini-label{
    color:var(--pf-muted);
    font-size:.78rem;
    font-weight:800;
  }

  .pf-mini-value{
    color:var(--pf-ink);
    font-size:1.08rem;
    font-weight:950;
    margin-top:6px;
  }

  .pf-grid{
    display:grid;
    grid-template-columns:1.15fr .95fr;
    gap:16px;
    padding:18px;
  }

  .pf-pane{
    border:1px solid var(--pf-line);
    border-radius:20px;
    overflow:hidden;
    background:#fff;
  }

  .pf-pane-head{
    padding:14px 16px;
    border-bottom:1px solid var(--pf-line2);
  }

  .pf-pane-title{
    color:var(--pf-ink);
    font-size:1rem;
    font-weight:950;
  }

  .pf-pane-sub{
    color:var(--pf-muted);
    font-size:.82rem;
    margin-top:3px;
  }

  .pf-pane-body{
    padding:14px 16px;
  }

  .pf-location-list,
  .pf-fastflow-list{
    display:flex;
    flex-direction:column;
    gap:12px;
  }

  .pf-location-card,
  .pf-fastflow-card{
    border:1px solid #e8edf4;
    background:var(--pf-surface);
    border-radius:18px;
    padding:14px;
  }

  .pf-loc-top,
  .pf-ff-top{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:10px;
    flex-wrap:wrap;
  }

  .pf-loc-code,
  .pf-ff-code{
    color:var(--pf-ink);
    font-size:1rem;
    font-weight:950;
  }

  .pf-loc-name,
  .pf-ff-name{
    color:var(--pf-muted);
    font-size:.84rem;
    margin-top:3px;
  }

  .pf-qty-badge{
    background:#fff;
    border:1px solid #dbe3ef;
    border-radius:999px;
    padding:8px 10px;
    font-size:.8rem;
    font-weight:900;
    color:var(--pf-ink);
    white-space:nowrap;
  }

  .pf-meta-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:10px;
    margin-top:12px;
  }

  .pf-meta{
    background:#fff;
    border:1px solid #edf2f7;
    border-radius:12px;
    padding:10px 12px;
  }

  .pf-meta-label{
    color:var(--pf-muted);
    font-size:.76rem;
    font-weight:800;
  }

  .pf-meta-value{
    color:var(--pf-ink);
    font-size:.88rem;
    font-weight:900;
    margin-top:4px;
    line-height:1.35;
  }

  .pf-status{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:28px;
    padding:0 10px;
    border-radius:999px;
    font-size:.75rem;
    font-weight:900;
    white-space:nowrap;
  }

  .pf-status-available{
    background:var(--pf-good-bg);
    color:var(--pf-good);
  }

  .pf-status-partial{
    background:var(--pf-warn-bg);
    color:var(--pf-warn);
  }

  .pf-status-shipped{
    background:var(--pf-bad-bg);
    color:var(--pf-bad);
  }

  .pf-empty-card{
    padding:18px;
    border-radius:16px;
    background:var(--pf-surface);
    border:1px dashed #d8e2ec;
    color:var(--pf-muted);
    font-weight:700;
  }

  @media (max-width: 1180px){
    .pf-grid{
      grid-template-columns:1fr;
    }

    .pf-summary{
      min-width:0;
      grid-template-columns:repeat(2,minmax(0,1fr));
    }
  }

  @media (max-width: 900px){
    .pf-kpis{
      grid-template-columns:repeat(2,minmax(0,1fr));
    }
  }

  @media (max-width: 760px){
    .pf-wrap{
      padding:14px 10px 24px;
    }

    .pf-title{
      font-size:1.7rem;
    }

    .pf-head-actions{
      width:100%;
    }

    .pf-head-actions > *{
      flex:1 1 100%;
    }

    .pf-search-row > *{
      width:100%;
    }

    .pf-kpis{
      grid-template-columns:1fr;
    }

    .pf-summary{
      grid-template-columns:1fr;
    }

    .pf-meta-grid{
      grid-template-columns:1fr;
    }
  }
</style>
@endpush

@push('scripts')
<script>
(() => {
  const API = @json(route('admin.wms.products.lookup'));

  const q = document.getElementById('q');
  const btnSearch = document.getElementById('btnSearch');
  const btnClear = document.getElementById('btnClear');
  const results = document.getElementById('results');

  const kpis = document.getElementById('kpis');
  const kpiItems = document.getElementById('kpiItems');
  const kpiUnits = document.getElementById('kpiUnits');
  const kpiLocations = document.getElementById('kpiLocations');
  const kpiFastFlow = document.getElementById('kpiFastFlow');

  const suggestBox = document.getElementById('suggestBox');
  const searchWrap = document.getElementById('searchWrap');

  let debounceTimer = null;
  let currentSuggestions = [];
  let activeSuggestionIndex = -1;

  function esc(s){
    return String(s ?? '').replace(/[&<>"']/g, m => ({
      '&':'&amp;',
      '<':'&lt;',
      '>':'&gt;',
      '"':'&quot;',
      "'":'&#039;'
    }[m]));
  }

  function ffStatusClass(status){
    const s = String(status || '').toLowerCase();
    if (s === 'available') return 'pf-status pf-status-available';
    if (s === 'partial') return 'pf-status pf-status-partial';
    return 'pf-status pf-status-shipped';
  }

  function ffStatusText(status){
    const s = String(status || '').toLowerCase();
    if (s === 'available') return 'Disponible';
    if (s === 'partial') return 'Parcial';
    if (s === 'shipped') return 'Enviado';
    return status || '—';
  }

  function showSuggestions(){
    suggestBox.hidden = false;
  }

  function hideSuggestions(){
    suggestBox.hidden = true;
    currentSuggestions = [];
    activeSuggestionIndex = -1;
  }

  function renderSuggestions(items){
    currentSuggestions = Array.isArray(items) ? items : [];
    activeSuggestionIndex = -1;

    if (!currentSuggestions.length) {
      suggestBox.innerHTML = `<div class="pf-suggest-empty">No se encontraron coincidencias.</div>`;
      showSuggestions();
      return;
    }

    suggestBox.innerHTML = currentSuggestions.map((item, index) => `
      <button
        type="button"
        class="pf-suggest-item"
        data-index="${index}"
        data-id="${esc(item.id)}"
        data-name="${esc(item.name || '')}"
        data-sku="${esc(item.sku || '')}"
      >
        <div class="pf-suggest-main">
          <div class="pf-suggest-name">${esc(item.name || 'Producto')}</div>
          <div class="pf-suggest-sub">
            SKU: ${esc(item.sku || '—')} &nbsp;&middot;&nbsp;
            ID: ${esc(item.id || '—')} &nbsp;&middot;&nbsp;
            ${esc(item.slug || '—')}
          </div>
        </div>

        <div class="pf-suggest-meta">
          ${Number(item.total_inventory_units || 0)} piezas
        </div>
      </button>
    `).join('');

    showSuggestions();
  }

  function setActiveSuggestion(index){
    const items = [...suggestBox.querySelectorAll('.pf-suggest-item')];
    items.forEach(el => el.classList.remove('is-active'));

    if (index < 0 || index >= items.length) {
      activeSuggestionIndex = -1;
      return;
    }

    activeSuggestionIndex = index;
    items[index].classList.add('is-active');
    items[index].scrollIntoView({ block: 'nearest' });
  }

  async function fetchLookup(term){
    const res = await fetch(`${API}?s=${encodeURIComponent(term)}`, {
      headers: { 'Accept':'application/json' }
    });

    return await res.json().catch(() => ({}));
  }

  async function fetchSuggestions(term){
    if (!term || term.length < 2) {
      hideSuggestions();
      return;
    }

    try {
      const data = await fetchLookup(term);
      const items = Array.isArray(data.items) ? data.items.slice(0, 8) : [];
      renderSuggestions(items);
    } catch (e) {
      hideSuggestions();
    }
  }

  function renderKpis(items){
    const totalItems = items.length;
    const totalUnits = items.reduce((a, x) => a + Number(x.total_inventory_units || 0), 0);
    const totalLocs = items.reduce((a, x) => a + Number(x.inventory_locations_count || 0), 0);
    const totalFast = items.reduce((a, x) => a + Number(x.fastflow_units_count || 0), 0);

    kpiItems.textContent = totalItems;
    kpiUnits.textContent = totalUnits;
    kpiLocations.textContent = totalLocs;
    kpiFastFlow.textContent = totalFast;
    kpis.style.display = 'grid';
  }

  function render(items){
    if (!items.length) {
      kpis.style.display = 'none';
      results.innerHTML = `<div class="pf-empty-main">No se encontraron productos con esa búsqueda.</div>`;
      return;
    }

    renderKpis(items);

    results.innerHTML = items.map(item => {
      const locations = Array.isArray(item.locations) ? item.locations : [];
      const fastflow = Array.isArray(item.fastflow) ? item.fastflow : [];

      const locationsHtml = locations.length
        ? `
          <div class="pf-location-list">
            ${locations.map(row => `
              <div class="pf-location-card">
                <div class="pf-loc-top">
                  <div>
                    <div class="pf-loc-code">${esc(row.location?.code || '—')}</div>
                    <div class="pf-loc-name">${esc(row.location?.name || 'Sin nombre')}</div>
                  </div>
                  <div class="pf-qty-badge">${Number(row.qty || 0)} piezas</div>
                </div>

                <div class="pf-meta-grid">
                  <div class="pf-meta">
                    <div class="pf-meta-label">Bodega</div>
                    <div class="pf-meta-value">${esc(row.location?.warehouse_name || '—')}</div>
                  </div>
                  <div class="pf-meta">
                    <div class="pf-meta-label">Zona</div>
                    <div class="pf-meta-value">${esc(row.location?.zone || row.location?.type || '—')}</div>
                  </div>
                  <div class="pf-meta">
                    <div class="pf-meta-label">Posición</div>
                    <div class="pf-meta-value">${esc(row.location?.position_label || '—')}</div>
                  </div>
                  <div class="pf-meta">
                    <div class="pf-meta-label">Mínimo</div>
                    <div class="pf-meta-value">${Number(row.min_qty || 0)}</div>
                  </div>
                </div>
              </div>
            `).join('')}
          </div>
        `
        : `<div class="pf-empty-card">Este producto no tiene inventario distribuido en ubicaciones.</div>`;

      const fastFlowHtml = fastflow.length
        ? `
          <div class="pf-fastflow-list">
            ${fastflow.map(box => `
              <div class="pf-fastflow-card">
                <div class="pf-ff-top">
                  <div>
                    <div class="pf-ff-code">${esc(box.label_code || '—')}</div>
                    <div class="pf-ff-name">Lote ${esc(box.batch_code || '—')} · Caja ${Number(box.box_number || 0)}/${Number(box.boxes_in_batch || 0)}</div>
                  </div>
                  <div class="pf-qty-badge">${Number(box.current_units || 0)} unidades</div>
                </div>

                <div class="pf-meta-grid">
                  <div class="pf-meta">
                    <div class="pf-meta-label">Estado</div>
                    <div class="pf-meta-value"><span class="${ffStatusClass(box.status)}">${ffStatusText(box.status)}</span></div>
                  </div>
                  <div class="pf-meta">
                    <div class="pf-meta-label">Ubicación</div>
                    <div class="pf-meta-value">${esc(box.location_code || '—')}</div>
                  </div>
                  <div class="pf-meta">
                    <div class="pf-meta-label">Bodega</div>
                    <div class="pf-meta-value">${esc(box.warehouse_name || '—')}</div>
                  </div>
                  <div class="pf-meta">
                    <div class="pf-meta-label">Recibido</div>
                    <div class="pf-meta-value">${esc(box.received_at || '—')}</div>
                  </div>
                </div>
              </div>
            `).join('')}
          </div>
        `
        : `<div class="pf-empty-card">No hay cajas activas de flujo rápido para este producto.</div>`;

      return `
        <div class="pf-result-card">
          <div class="pf-result-head">
            <div>
              <div class="pf-product-title">${esc(item.name || 'Producto')}</div>
              <div class="pf-product-sub">SKU: ${esc(item.sku || '—')} · ID: ${esc(item.id || '—')} · Slug: ${esc(item.slug || '—')}</div>

              <div class="pf-badges">
                <span class="pf-badge pf-badge-soft">Stock catálogo: ${Number(item.stock || 0)}</span>
                <span class="pf-badge pf-badge-success">Ubicaciones: ${Number(item.inventory_locations_count || 0)}</span>
                <span class="pf-badge pf-badge-warn">Flujo rápido: ${Number(item.fastflow_units_count || 0)} unidades</span>
              </div>
            </div>

            <div class="pf-summary">
              <div class="pf-mini">
                <div class="pf-mini-label">Piezas en ubicaciones</div>
                <div class="pf-mini-value">${Number(item.total_inventory_units || 0)}</div>
              </div>

              <div class="pf-mini">
                <div class="pf-mini-label">Ubicaciones</div>
                <div class="pf-mini-value">${Number(item.inventory_locations_count || 0)}</div>
              </div>

              <div class="pf-mini">
                <div class="pf-mini-label">Cajas de flujo rápido</div>
                <div class="pf-mini-value">${Number(item.fastflow_boxes_count || 0)}</div>
              </div>

              <div class="pf-mini">
                <div class="pf-mini-label">Precio</div>
                <div class="pf-mini-value">$${Number(item.price || 0).toFixed(2)}</div>
              </div>
            </div>
          </div>

          <div class="pf-grid">
            <div class="pf-pane">
              <div class="pf-pane-head">
                <div class="pf-pane-title">Ubicaciones del producto</div>
                <div class="pf-pane-sub">Distribución del inventario por ubicación física.</div>
              </div>
              <div class="pf-pane-body">
                ${locationsHtml}
              </div>
            </div>

            <div class="pf-pane">
              <div class="pf-pane-head">
                <div class="pf-pane-title">Flujo rápido</div>
                <div class="pf-pane-sub">Cajas activas, lote, etiqueta y unidades disponibles.</div>
              </div>
              <div class="pf-pane-body">
                ${fastFlowHtml}
              </div>
            </div>
          </div>
        </div>
      `;
    }).join('');
  }

  async function search(term = null){
    const finalTerm = (term ?? q.value ?? '').trim();

    if (!finalTerm) {
      kpis.style.display = 'none';
      results.innerHTML = `<div class="pf-empty-main">Introduce un nombre, SKU o código escaneado para consultar el inventario.</div>`;
      hideSuggestions();
      return;
    }

    results.innerHTML = `<div class="pf-empty-main">Buscando producto...</div>`;
    hideSuggestions();

    try{
      const data = await fetchLookup(finalTerm);
      render(Array.isArray(data.items) ? data.items : []);
    }catch(e){
      kpis.style.display = 'none';
      results.innerHTML = `<div class="pf-empty-main">No se pudo realizar la búsqueda.</div>`;
    }
  }

  q?.addEventListener('input', () => {
    const term = q.value.trim();

    clearTimeout(debounceTimer);

    debounceTimer = setTimeout(() => {
      fetchSuggestions(term);
    }, 220);
  });

  q?.addEventListener('keydown', (e) => {
    const items = [...suggestBox.querySelectorAll('.pf-suggest-item')];

    if (!suggestBox.hidden && items.length) {
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        setActiveSuggestion(activeSuggestionIndex + 1 >= items.length ? 0 : activeSuggestionIndex + 1);
        return;
      }

      if (e.key === 'ArrowUp') {
        e.preventDefault();
        setActiveSuggestion(activeSuggestionIndex - 1 < 0 ? items.length - 1 : activeSuggestionIndex - 1);
        return;
      }

      if (e.key === 'Enter' && activeSuggestionIndex >= 0) {
        e.preventDefault();
        const btn = items[activeSuggestionIndex];
        const value = btn?.dataset?.sku || btn?.dataset?.name || q.value;
        q.value = value;
        search(value);
        return;
      }

      if (e.key === 'Escape') {
        hideSuggestions();
        return;
      }
    }

    if (e.key === 'Enter') {
      e.preventDefault();
      search();
    }
  });

  suggestBox?.addEventListener('click', (e) => {
    const btn = e.target.closest('.pf-suggest-item');
    if (!btn) return;

    const value = btn.dataset.sku || btn.dataset.name || q.value;
    q.value = value;
    search(value);
  });

  btnSearch?.addEventListener('click', () => search());

  btnClear?.addEventListener('click', () => {
    q.value = '';
    kpis.style.display = 'none';
    hideSuggestions();
    results.innerHTML = `<div class="pf-empty-main">Introduce un nombre, SKU o código escaneado para consultar el inventario.</div>`;
    q.focus();
  });

  document.addEventListener('click', (e) => {
    if (!searchWrap.contains(e.target)) {
      hideSuggestions();
    }
  });
})();
</script>
@endpush