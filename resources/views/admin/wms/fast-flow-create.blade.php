@extends('layouts.app')

@section('title', 'WMS · Registrar Entrada')

@section('content')
@php
  $productsJs = collect($products ?? [])->map(function($p){
      return [
          'id'         => $p->id,
          'name'       => $p->name ?? '',
          'sku'        => $p->sku ?? '',
          'stock'      => (int) ($p->stock ?? 0),
          'brand_name' => $p->brand_name ?? '',
          'model_name' => $p->model_name ?? '',
      ];
  })->values()->all();

  $selectedProductId = old('catalog_item_id');
@endphp

<div class="ffcreate-page">
  <div class="ffcreate-head">
    <div class="ffcreate-head-left">
      <a href="{{ route('admin.wms.fastflow.index') }}" class="ffcreate-back" aria-label="Volver">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M15 18l-6-6 6-6"/>
        </svg>
      </a>

      <div class="ffcreate-brand-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.15" stroke-linecap="round" stroke-linejoin="round">
          <path d="M13 2L4 14h6l-1 8 9-12h-6l1-8Z"/>
        </svg>
      </div>

      <div>
        <h1 class="ffcreate-title">Registrar Entrada</h1>
        <div class="ffcreate-sub">Cross Dock · Fast Flow</div>
      </div>
    </div>
  </div>

  @if(session('ok'))
    <div class="ffcreate-alert ffcreate-alert-ok">{{ session('ok') }}</div>
  @endif

  @if($errors->any())
    <div class="ffcreate-alert ffcreate-alert-err">
      @foreach($errors->all() as $error)
        <div>{{ $error }}</div>
      @endforeach
    </div>
  @endif

  <section class="ffcreate-card">
    <div class="ffcreate-fixed-warehouse">
      <div class="ffcreate-fixed-label">Almacén fijo</div>
      <div class="ffcreate-fixed-pill">
        {{ $defaultWarehouse->name ?? 'Principal' }}{{ !empty($defaultWarehouse->code) ? ' · '.$defaultWarehouse->code : '' }}
      </div>
    </div>

    <form method="POST" action="{{ route('admin.wms.fastflow.inbound') }}" id="ffInboundForm" novalidate>
      @csrf

      <div class="ffcreate-grid">
        <div class="ffcreate-field">
          <label>Producto *</label>
          <div class="ffcreate-combo" id="productCombo">
            <input type="text"
                   id="productSearch"
                   placeholder="Nombre del producto"
                   autocomplete="off">
            <input type="hidden"
                   name="catalog_item_id"
                   id="catalog_item_id"
                   value="{{ $selectedProductId }}"
                   required>
            <div class="ffcreate-combo-menu" id="productMenu"></div>
          </div>
          <div class="ffcreate-help" id="productHelp">Busca por nombre, SKU, marca o modelo.</div>
        </div>

        <div class="ffcreate-field">
          <label>SKU</label>
          <input type="text" id="sku_preview" placeholder="Código SKU (automático)" readonly>
        </div>

        <div class="ffcreate-field">
          <label>Marca</label>
          <input type="text" id="brand_preview" placeholder="Marca del producto" readonly>
        </div>

        <div class="ffcreate-field">
          <label>Modelo</label>
          <input type="text" id="model_preview" placeholder="Modelo del producto" readonly>
        </div>

        <div class="ffcreate-field">
          <label>Cajas Recibidas *</label>
          <input type="number"
                 name="boxes_count"
                 id="boxes_count"
                 min="1"
                 max="5000"
                 value="{{ old('boxes_count') }}"
                 placeholder="Ej: 30"
                 required>
        </div>

        <div class="ffcreate-field">
          <label>Piezas por Caja *</label>
          <input type="number"
                 name="units_per_box"
                 id="units_per_box"
                 min="1"
                 max="100000"
                 value="{{ old('units_per_box') }}"
                 placeholder="Ej: 24"
                 required>
        </div>

        <div class="ffcreate-field ffcreate-span-2">
          <label>Referencia</label>
          <input type="text"
                 name="reference"
                 value="{{ old('reference') }}"
                 placeholder="Factura, remisión, OC...">
        </div>
      </div>

      <div class="ffcreate-summary" id="inboundSummary">
        <div class="ffcreate-summary-item">
          <div class="ffcreate-summary-value" id="summaryBoxes">0</div>
          <div class="ffcreate-summary-label">Cajas</div>
        </div>

        <div class="ffcreate-summary-item">
          <div class="ffcreate-summary-value" id="summaryUnits">×0</div>
          <div class="ffcreate-summary-label">Piezas/Caja</div>
        </div>

        <div class="ffcreate-summary-item">
          <div class="ffcreate-summary-value" id="summaryTotal">0</div>
          <div class="ffcreate-summary-label">Total Piezas</div>
        </div>
      </div>

      <div class="ffcreate-grid ffcreate-grid-bottom">
        <div class="ffcreate-field ffcreate-span-2">
          <label>Notas</label>
          <textarea name="notes"
                    rows="4"
                    placeholder="Observaciones opcionales...">{{ old('notes') }}</textarea>
        </div>
      </div>

      <button type="submit" class="ffcreate-submit ffcreate-submit-inbound" id="inboundSubmit" disabled>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2l8 4.5v11L12 22 4 17.5v-11L12 2z"/>
          <path d="M12 22v-9.5"/>
          <path d="M20 6.5L12 11 4 6.5"/>
          <path d="M18 9v5"/>
          <path d="M15.5 11.5H20.5"/>
        </svg>
        <span id="inboundSubmitText">Registrar Entrada (0 piezas → FAST-FLOW)</span>
      </button>
    </form>
  </section>

  @if($lastBatchCode)
    <div class="ffcreate-footer-link">
      <a href="{{ route('admin.wms.fastflow.labels', $lastBatchCode) }}" target="_blank">
        Imprimir etiquetas del último lote
      </a>
    </div>
  @endif
</div>
@endsection

@push('styles')
<style>
  :root{
    --ffc-bg:#f6f8fc;
    --ffc-card:#ffffff;
    --ffc-line:#dfe5ee;
    --ffc-line-soft:#ebf0f6;
    --ffc-ink:#102547;
    --ffc-muted:#6c7c95;
    --ffc-green:#0f9d6c;
    --ffc-green-2:#109f6f;
    --ffc-green-soft:#eaf8f1;
    --ffc-green-soft-2:#cfeee0;
    --ffc-shadow:0 18px 42px rgba(15,35,69,.06);
  }

  .ffcreate-page{
    max-width:980px;
    margin:0 auto;
    padding:14px 14px 34px;
    color:var(--ffc-ink);
  }

  .ffcreate-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:18px;
    flex-wrap:wrap;
    margin-bottom:18px;
  }

  .ffcreate-head-left{
    display:flex;
    align-items:center;
    gap:14px;
  }

  .ffcreate-back{
    width:38px;
    height:38px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:var(--ffc-ink);
    text-decoration:none;
    transition:.18s ease;
  }

  .ffcreate-back:hover{ background:#eef3f9; }

  .ffcreate-back svg{
    width:20px;
    height:20px;
  }

  .ffcreate-brand-icon{
    width:46px;
    height:46px;
    border-radius:15px;
    background:var(--ffc-green);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 14px 28px rgba(15,157,108,.18);
  }

  .ffcreate-brand-icon svg{
    width:22px;
    height:22px;
  }

  .ffcreate-title{
    margin:0;
    font-size:2rem;
    line-height:1.04;
    font-weight:950;
    letter-spacing:-.03em;
    color:var(--ffc-ink);
  }

  .ffcreate-sub{
    margin-top:4px;
    color:var(--ffc-muted);
    font-size:1rem;
  }

  .ffcreate-alert{
    margin-bottom:14px;
    border-radius:16px;
    padding:13px 16px;
    font-weight:800;
    box-shadow:var(--ffc-shadow);
  }

  .ffcreate-alert-ok{
    background:#edfdf5;
    color:#166534;
    border:1px solid #b7efcf;
  }

  .ffcreate-alert-err{
    background:#fff1f2;
    color:#be123c;
    border:1px solid #fecdd3;
  }

  .ffcreate-card{
    background:var(--ffc-card);
    border:1px solid var(--ffc-line);
    border-radius:28px;
    box-shadow:var(--ffc-shadow);
    padding:26px 26px 24px;
  }

  .ffcreate-fixed-warehouse{
    margin-bottom:22px;
  }

  .ffcreate-fixed-label{
    font-size:.84rem;
    font-weight:900;
    color:#64748b;
    margin-bottom:8px;
  }

  .ffcreate-fixed-pill{
    display:inline-flex;
    align-items:center;
    min-height:42px;
    padding:10px 14px;
    border-radius:999px;
    background:#edf9f2;
    color:#0f8f63;
    border:1px solid #bce8d2;
    font-weight:900;
  }

  .ffcreate-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:18px 22px;
  }

  .ffcreate-grid-bottom{
    margin-top:18px;
  }

  .ffcreate-field label{
    display:block;
    margin-bottom:9px;
    font-size:.98rem;
    font-weight:900;
    color:#111827;
  }

  .ffcreate-field input,
  .ffcreate-field textarea{
    width:100%;
    border:1px solid #d8dee8;
    border-radius:16px;
    background:#fff;
    color:var(--ffc-ink);
    padding:15px 16px;
    outline:none;
    font-size:1rem;
    transition:.18s ease;
    box-shadow:0 1px 0 rgba(15,35,69,.01);
  }

  .ffcreate-field input::placeholder,
  .ffcreate-field textarea::placeholder{
    color:#8b98ad;
  }

  .ffcreate-field input:focus,
  .ffcreate-field textarea:focus{
    border-color:#a8c9bc;
    box-shadow:0 0 0 4px rgba(15,157,108,.08);
  }

  .ffcreate-field input[readonly]{
    background:#fbfcfe;
    color:#3b4a63;
  }

  .ffcreate-span-2{
    grid-column:1 / -1;
  }

  .ffcreate-help{
    margin-top:7px;
    font-size:.8rem;
    color:var(--ffc-muted);
  }

  .ffcreate-combo{
    position:relative;
  }

  .ffcreate-combo-menu{
    position:absolute;
    left:0;
    right:0;
    top:calc(100% + 8px);
    background:#fff;
    border:1px solid var(--ffc-line);
    border-radius:18px;
    box-shadow:0 24px 42px rgba(15,35,69,.12);
    max-height:280px;
    overflow:auto;
    display:none;
    z-index:70;
  }

  .ffcreate-combo.is-open .ffcreate-combo-menu{
    display:block;
  }

  .ffcreate-combo-item{
    padding:13px 14px;
    cursor:pointer;
    border-bottom:1px solid var(--ffc-line-soft);
    transition:.15s ease;
  }

  .ffcreate-combo-item:last-child{
    border-bottom:0;
  }

  .ffcreate-combo-item:hover{
    background:#f7fbff;
  }

  .ffcreate-combo-main{
    font-weight:900;
    color:var(--ffc-ink);
  }

  .ffcreate-combo-sub{
    margin-top:5px;
    font-size:.82rem;
    color:var(--ffc-muted);
  }

  .ffcreate-summary{
    margin-top:26px;
    border:1px solid #dbe7e1;
    border-radius:22px;
    background:#f7faf8;
    padding:18px;
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:12px;
    transition:
      transform .24s ease,
      box-shadow .24s ease,
      border-color .24s ease,
      background .24s ease;
  }

  .ffcreate-summary-item{
    text-align:center;
    padding:8px 6px;
  }

  .ffcreate-summary-value{
    font-size:2.05rem;
    line-height:1;
    font-weight:950;
    letter-spacing:-.04em;
    color:#84a096;
    transition:color .2s ease, transform .2s ease;
  }

  .ffcreate-summary-label{
    margin-top:7px;
    font-size:.94rem;
    color:#789084;
    transition:color .2s ease;
  }

  .ffcreate-summary.is-ready{
    background:linear-gradient(180deg,#edf9f2 0%, #e3f7ec 100%);
    border-color:#96ebc0;
    box-shadow:0 16px 36px rgba(15,157,108,.10);
    transform:translateY(-2px) scale(1.005);
    animation:ffPulseReady .42s ease;
  }

  .ffcreate-summary.is-ready .ffcreate-summary-value{
    color:#0b7e59;
    transform:scale(1.03);
  }

  .ffcreate-summary.is-ready .ffcreate-summary-label{
    color:#0f8f63;
  }

  .ffcreate-submit{
    width:100%;
    margin-top:22px;
    border:0;
    border-radius:18px;
    min-height:62px;
    padding:14px 16px;
    font-size:1.1rem;
    font-weight:950;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:12px;
    cursor:pointer;
    transition:
      transform .18s ease,
      box-shadow .18s ease,
      background .18s ease,
      opacity .18s ease;
    opacity:.52;
    pointer-events:none;
    box-shadow:none;
  }

  .ffcreate-submit svg{
    width:22px;
    height:22px;
    flex:0 0 22px;
  }

  .ffcreate-submit.is-ready{
    opacity:1;
    pointer-events:auto;
    animation:ffPopIn .34s ease;
  }

  .ffcreate-submit.is-ready:hover{
    transform:translateY(-1px);
  }

  .ffcreate-submit-inbound{
    background:#7dc7af;
    color:#fff;
  }

  .ffcreate-submit-inbound.is-ready{
    background:linear-gradient(180deg,#109f6f 0%, #0f996b 100%);
    box-shadow:0 18px 28px rgba(15,157,108,.18);
  }

  .ffcreate-footer-link{
    margin-top:14px;
    text-align:center;
  }

  .ffcreate-footer-link a{
    color:#0f8e62;
    font-weight:900;
    text-decoration:none;
  }

  .ffcreate-footer-link a:hover{
    text-decoration:underline;
  }

  @keyframes ffPulseReady{
    0%{ transform:scale(.992); }
    60%{ transform:scale(1.01); }
    100%{ transform:scale(1.005); }
  }

  @keyframes ffPopIn{
    0%{ transform:scale(.985); }
    100%{ transform:scale(1); }
  }

  @media (max-width: 860px){
    .ffcreate-grid{
      grid-template-columns:1fr;
    }

    .ffcreate-span-2{
      grid-column:auto;
    }

    .ffcreate-summary{
      grid-template-columns:1fr;
      gap:8px;
    }
  }

  @media (max-width: 640px){
    .ffcreate-page{
      padding:12px 10px 28px;
    }

    .ffcreate-card{
      padding:18px 16px 18px;
      border-radius:22px;
    }

    .ffcreate-title{
      font-size:1.65rem;
    }
  }
</style>
@endpush

@push('scripts')
<script>
(function(){
  const products = @json($productsJs);

  function escapeHtml(value){
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function formatNumber(value){
    return Number(value || 0).toLocaleString();
  }

  const combo = document.getElementById('productCombo');
  const productSearch = document.getElementById('productSearch');
  const hiddenId = document.getElementById('catalog_item_id');
  const menu = document.getElementById('productMenu');
  const help = document.getElementById('productHelp');

  const skuPreview = document.getElementById('sku_preview');
  const brandPreview = document.getElementById('brand_preview');
  const modelPreview = document.getElementById('model_preview');
  const boxesCount = document.getElementById('boxes_count');
  const unitsPerBox = document.getElementById('units_per_box');

  const summary = document.getElementById('inboundSummary');
  const summaryBoxes = document.getElementById('summaryBoxes');
  const summaryUnits = document.getElementById('summaryUnits');
  const summaryTotal = document.getElementById('summaryTotal');
  const submit = document.getElementById('inboundSubmit');
  const submitText = document.getElementById('inboundSubmitText');

  function clearProductPreview(){
    skuPreview.value = '';
    brandPreview.value = '';
    modelPreview.value = '';
    if (help) {
      help.textContent = 'Busca por nombre, SKU, marca o modelo.';
    }
  }

  function renderMenu(query){
    const q = String(query || '').trim().toLowerCase();

    const rows = products.filter(function(p){
      const text = [
        p.name || '',
        p.sku || '',
        p.brand_name || '',
        p.model_name || ''
      ].join(' ').toLowerCase();

      return !q || text.includes(q);
    }).slice(0, 60);

    if (!rows.length) {
      menu.innerHTML = '<div class="ffcreate-combo-item"><div class="ffcreate-combo-main">Sin resultados</div></div>';
      return;
    }

    menu.innerHTML = rows.map(function(p){
      return '' +
        '<div class="ffcreate-combo-item" data-id="' + escapeHtml(p.id) + '">' +
          '<div class="ffcreate-combo-main">' + escapeHtml(p.name || '—') + '</div>' +
          '<div class="ffcreate-combo-sub">' +
            'SKU: ' + escapeHtml(p.sku || '—') +
            (p.brand_name ? ' · Marca: ' + escapeHtml(p.brand_name) : '') +
            (p.model_name ? ' · Modelo: ' + escapeHtml(p.model_name) : '') +
            ' · Stock: ' + formatNumber(p.stock || 0) +
          '</div>' +
        '</div>';
    }).join('');
  }

  function openMenu(){
    combo.classList.add('is-open');
    renderMenu(productSearch.value);
  }

  function closeMenu(){
    combo.classList.remove('is-open');
  }

  function pickProduct(id){
    const product = products.find(function(p){
      return Number(p.id) === Number(id);
    });

    if (!product) return;

    hiddenId.value = product.id;
    productSearch.value = product.name || '';
    skuPreview.value = product.sku || '';
    brandPreview.value = product.brand_name || '';
    modelPreview.value = product.model_name || '';

    if (help) {
      help.textContent = 'Producto seleccionado. SKU: ' + (product.sku || '—') + ' · Stock actual: ' + formatNumber(product.stock || 0);
    }

    closeMenu();
    updateInboundState();
  }

  function updateInboundState(){
    const boxes = Number(boxesCount.value || 0);
    const units = Number(unitsPerBox.value || 0);
    const total = boxes * units;

    summaryBoxes.textContent = formatNumber(boxes);
    summaryUnits.textContent = '×' + formatNumber(units);
    summaryTotal.textContent = formatNumber(total);

    const ready = !!hiddenId.value && boxes > 0 && units > 0;

    summary.classList.toggle('is-ready', ready);
    submit.classList.toggle('is-ready', ready);
    submit.disabled = !ready;

    submitText.textContent = 'Registrar Entrada (' + formatNumber(total) + ' piezas → FAST-FLOW)';
  }

  productSearch.addEventListener('focus', openMenu);

  productSearch.addEventListener('input', function(){
    hiddenId.value = '';
    clearProductPreview();
    openMenu();
    updateInboundState();
  });

  menu.addEventListener('click', function(e){
    const item = e.target.closest('[data-id]');
    if (!item) return;
    pickProduct(item.dataset.id);
  });

  document.addEventListener('click', function(e){
    if (!combo.contains(e.target)) {
      closeMenu();
    }
  });

  [boxesCount, unitsPerBox].forEach(function(el){
    el.addEventListener('input', updateInboundState);
    el.addEventListener('change', updateInboundState);
  });

  if (hiddenId.value) {
    pickProduct(hiddenId.value);
  } else {
    updateInboundState();
  }
})();
</script>
@endpush