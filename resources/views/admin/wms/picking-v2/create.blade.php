@extends('layouts.app')

@section('title', 'WMS · Nueva Tarea de Picking')

@section('content')
@php
  $storeUrl = route('admin.wms.picking.v2.store');
  $aiImportUrl = route('admin.wms.picking.v2.ai-import');

  $fastFlowCards = collect($recentBatches ?? [])->map(function($b){
      $batchCode       = (string) data_get($b, 'batch_code', '');
      $productName     = (string) (data_get($b, 'product_name') ?? 'Producto');
      $sku             = (string) (data_get($b, 'sku') ?? '—');
      $warehouseName   = (string) (data_get($b, 'warehouse_name') ?? '—');
      $boxesCount      = (int) data_get($b, 'boxes_count', 0);
      $unitsPerBox     = (int) data_get($b, 'units_per_box', 0);
      $totalUnits      = (int) data_get($b, 'total_units', ($boxesCount * $unitsPerBox));
      $availableBoxesB = (int) data_get($b, 'available_boxes', 0);
      $availableUnitsB = (int) data_get($b, 'available_units', 0);
      $dispatchedBoxes = max(0, $boxesCount - $availableBoxesB);
      $status          = $availableBoxesB > 0 ? 'active' : 'completed';
      $progress        = $boxesCount > 0 ? (int) round(($availableBoxesB / max(1, $boxesCount)) * 100) : 0;

      $searchBlob = implode(' ', [
          $batchCode,
          $productName,
          $sku,
          $warehouseName,
      ]);

      return [
          'batch_code'       => $batchCode,
          'product_name'     => $productName,
          'sku'              => $sku,
          'warehouse_name'   => $warehouseName,
          'boxes_count'      => $boxesCount,
          'units_per_box'    => $unitsPerBox,
          'total_units'      => $totalUnits,
          'available_boxes'  => $availableBoxesB,
          'available_units'  => $availableUnitsB,
          'dispatched_boxes' => $dispatchedBoxes,
          'status'           => $status,
          'progress'         => $progress,
          'show_url'         => $batchCode ? route('admin.wms.fastflow.show', $batchCode) : '#',
          'search_blob'      => $searchBlob,
      ];
  })->values();

  $fastFlowActiveCount = (int) $fastFlowCards->where('status', 'active')->count();
@endphp

<div class="pkc-wrap">
  <div class="pkc-head">
    <div>
      <h1 class="pkc-title">Nueva Tarea de Picking</h1>
      <div class="pkc-sub">Crea una orden de surtido con entregas, productos y soporte de Fast Flow.</div>
    </div>

    <div class="pkc-actions">
      <a href="{{ route('admin.wms.picking.v2') }}" class="pkc-btn pkc-btn-ghost">← Volver</a>
      <div class="pkc-auto-number">
        Folio automático: <b>{{ $nextTaskNumber ?? 'PICK-001' }}</b>
      </div>
    </div>
  </div>

  @if(session('ok'))
    <div class="pkc-alert pkc-alert-ok">{{ session('ok') }}</div>
  @endif

  @if($errors->any())
    <div class="pkc-alert pkc-alert-err">
      @foreach($errors->all() as $error)
        <div>{{ $error }}</div>
      @endforeach
    </div>
  @endif

  <div class="pkc-layout">
    <div class="pkc-main">
      <form method="POST" action="{{ $storeUrl }}" id="createForm" class="pkc-form-card">
        @csrf

        <input type="hidden" name="items_json" id="itemsJsonInput">
        <input type="hidden" name="deliveries_json" id="deliveriesJsonInput">

        <div class="pkc-section">
          <div class="pkc-section-title">Datos generales</div>

          <div class="pkc-form-grid">
            <div class="pkc-field">
              <label>Orden de Referencia</label>
              <input type="text" name="order_number" id="order_number" placeholder="SO-2024-001">
            </div>

            <div class="pkc-field">
              <label>Asignado a</label>
              <input type="text" name="assigned_to" id="assigned_to" placeholder="Nombre del operario">
            </div>

            <div class="pkc-field">
              <label>Prioridad</label>
              <select name="priority" id="priority">
                <option value="low">Baja</option>
                <option value="normal" selected>Normal</option>
                <option value="high">Alta</option>
                <option value="urgent">Urgente</option>
              </select>
            </div>

            <div class="pkc-field">
              <label>Total de Entregas</label>
              <input type="number" name="total_phases" id="total_phases" min="1" max="12" value="1">
            </div>
          </div>
        </div>

        <div class="pkc-section">
          <div class="pkc-section-head">
            <div class="pkc-section-title">Planeación de Entregas</div>
            <span class="pkc-count" id="deliveryCounter">1 entrega</span>
          </div>

          <div id="deliveryPlans" class="pkc-deliveries"></div>
        </div>

        <div class="pkc-section">
          <div class="pkc-section-head">
            <div class="pkc-section-title">Lista de Productos a Surtir</div>
            <div class="pkc-section-actions">
              <span class="pkc-count" id="itemsCounter">0 productos</span>

              <button type="button" class="pkc-btn pkc-btn-ghost pkc-btn-sm" id="btnOpenAiImport">
                Importar PDF / Excel con IA
              </button>

              <input type="file" id="aiImportInput" class="pkc-hidden" accept=".pdf,.xlsx,.xls,.csv,.jpg,.jpeg,.png,.webp" multiple>
            </div>
          </div>

          <div id="aiImportStatus" class="pkc-import-status"></div>

          <div class="pkc-add-grid">
            <div class="pkc-col-4">
              <label class="pkc-inline-label">Producto</label>

              <div class="pkc-combo" id="productCombo">
                <input type="text" id="productSearchInput" placeholder="Buscar por SKU, nombre, marca o modelo">
                <input type="hidden" id="newItemSku">
                <div id="productSearchMenu" class="pkc-combo-menu"></div>
              </div>

              <div class="pkc-helper" id="newItemHelper">Selecciona un producto del inventario.</div>
              <div id="fastFlowMatchBox" class="pkc-fast-match" hidden></div>
            </div>

            <div class="pkc-col-2">
              <label class="pkc-inline-label">Cantidad</label>
              <input type="number" id="newItemQty" min="1" placeholder="Cant.">
            </div>

            <div class="pkc-col-3">
              <label class="pkc-inline-label">Ubicación WMS</label>
              <input type="text" id="newItemLocation" placeholder="Ubicación" readonly>
            </div>

            <div class="pkc-col-2">
              <label class="pkc-inline-label">Entrega</label>
              <select id="newItemDelivery"></select>
            </div>

            <div class="pkc-col-1">
              <label class="pkc-inline-label">&nbsp;</label>
              <button type="button" class="pkc-add-btn" id="btnAddItem">＋</button>
            </div>
          </div>

          <div id="createItemsList" class="pkc-item-list"></div>
        </div>

        <div class="pkc-section">
          <div class="pkc-field">
            <label>Notas generales</label>
            <textarea name="notes" id="notes" rows="4" placeholder="Instrucciones especiales..."></textarea>
          </div>
        </div>

        <div class="pkc-form-actions">
          <a href="{{ route('admin.wms.picking.v2') }}" class="pkc-btn pkc-btn-ghost">Cancelar</a>
          <button type="submit" class="pkc-btn pkc-btn-primary">Crear Tarea</button>
        </div>
      </form>
    </div>

    <aside class="pkc-side">
      <div class="pkc-side-card">
        <div class="pkc-side-head">
          <div>
            <div class="pkc-side-title">Fast Flow disponible</div>
            <div class="pkc-side-sub">Selecciona un lote para precargar producto.</div>
          </div>
          <span class="pkc-count">{{ $fastFlowActiveCount }} activo{{ $fastFlowActiveCount === 1 ? '' : 's' }}</span>
        </div>

        <div class="pkc-fast-grid" id="fastFlowGrid">
          @forelse($fastFlowCards as $card)
            <article
              class="pkc-fast-card {{ $card['status'] !== 'active' ? 'is-muted' : '' }}"
              data-fast-search="{{ \Illuminate\Support\Str::lower($card['search_blob']) }}"
              data-fast-sku="{{ $card['sku'] }}"
              data-fast-name="{{ $card['product_name'] }}"
            >
              <div class="pkc-fast-top">
                <div>
                  <div class="pkc-fast-name">{{ $card['product_name'] }}</div>
                  <div class="pkc-fast-code">{{ $card['batch_code'] }}</div>
                  <div class="pkc-fast-subline">
                    {{ $card['sku'] !== '—' ? $card['sku'] : $card['warehouse_name'] }}
                  </div>
                </div>

                @if($card['status'] === 'active')
                  <span class="pkc-fast-badge is-green">Activo</span>
                @else
                  <span class="pkc-fast-badge is-gray">Completado</span>
                @endif
              </div>

              <div class="pkc-fast-metrics">
                <div class="pkc-fast-metric">
                  <div class="pkc-fast-metric-value">{{ number_format($card['available_boxes']) }}</div>
                  <div class="pkc-fast-metric-label">Cajas</div>
                </div>
                <div class="pkc-fast-metric">
                  <div class="pkc-fast-metric-value">{{ number_format($card['available_units']) }}</div>
                  <div class="pkc-fast-metric-label">Piezas</div>
                </div>
              </div>

              <div class="pkc-fast-progress">
                <span style="width: {{ max(0, min(100, $card['progress'])) }}%"></span>
              </div>
            </article>
          @empty
            <div class="pkc-empty-mini">No hay lotes disponibles.</div>
          @endforelse
        </div>
      </div>
    </aside>
  </div>
</div>
@endsection

@push('styles')
<style>
  :root{
    --pkc-ink:#0f172a;
    --pkc-muted:#64748b;
    --pkc-line:#e5e7eb;
    --pkc-line2:#eef2f7;
    --pkc-card:#ffffff;
    --pkc-bg:#f8fafc;
    --pkc-shadow:0 14px 34px rgba(15,23,42,.06);
  }

  .pkc-wrap{max-width:1400px;margin:0 auto;padding:18px 14px 30px}
  .pkc-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:18px}
  .pkc-title{margin:0;font-size:2rem;font-weight:950;color:var(--pkc-ink);letter-spacing:-.03em}
  .pkc-sub{margin-top:4px;color:var(--pkc-muted)}
  .pkc-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
  .pkc-auto-number{padding:12px 14px;background:#fff;border:1px solid var(--pkc-line);border-radius:12px;color:#475569}
  .pkc-alert{border-radius:14px;padding:12px 14px;margin-bottom:14px;font-weight:800}
  .pkc-alert-ok{background:#ecfdf5;color:#166534;border:1px solid #bbf7d0}
  .pkc-alert-err{background:#fff1f2;color:#be123c;border:1px solid #fecdd3}

  .pkc-layout{
    display:grid;
    grid-template-columns:minmax(0,1.2fr) 380px;
    gap:18px;
    align-items:start;
  }

  .pkc-form-card,.pkc-side-card{
    background:#fff;
    border:1px solid var(--pkc-line);
    border-radius:22px;
    box-shadow:var(--pkc-shadow);
  }

  .pkc-form-card{padding:20px}
  .pkc-side-card{padding:18px}

  .pkc-section{margin-bottom:18px}
  .pkc-section:last-child{margin-bottom:0}
  .pkc-section-title{font-size:1rem;font-weight:950;color:var(--pkc-ink)}
  .pkc-section-head{
    display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px;
  }
  .pkc-section-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}

  .pkc-btn{
    border:0;border-radius:12px;padding:12px 16px;font-weight:900;
    display:inline-flex;align-items:center;gap:8px;text-decoration:none;cursor:pointer;
  }
  .pkc-btn-primary{background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff}
  .pkc-btn-ghost{background:#fff;color:var(--pkc-ink);border:1px solid var(--pkc-line)}
  .pkc-btn-sm{padding:10px 12px;font-size:.85rem}

  .pkc-count{
    display:inline-flex;align-items:center;justify-content:center;
    padding:6px 10px;border-radius:999px;border:1px solid #dbeafe;background:#eff6ff;color:#2563eb;font-weight:800;font-size:.78rem;
  }

  .pkc-form-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:14px;
    margin-top:12px;
  }

  .pkc-field label,
  .pkc-inline-label{
    display:block;margin-bottom:7px;font-size:.85rem;font-weight:800;color:#334155;
  }

  .pkc-field input,
  .pkc-field select,
  .pkc-field textarea,
  .pkc-add-grid input,
  .pkc-add-grid select,
  .pkc-combo input{
    width:100%;
    border:1px solid var(--pkc-line);
    border-radius:12px;
    background:#fff;
    color:var(--pkc-ink);
    padding:12px;
    outline:none;
  }

  .pkc-deliveries{display:grid;gap:12px}
  .pkc-delivery-card{background:#f8fafc;border:1px solid var(--pkc-line);border-radius:16px;padding:14px}
  .pkc-delivery-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px}
  .pkc-delivery-title{font-weight:900;color:#0f172a}
  .pkc-delivery-grid{display:grid;grid-template-columns:220px minmax(0,1fr);gap:12px}

  .pkc-add-grid{
    display:grid;
    grid-template-columns:4fr 2fr 3fr 2fr 1fr;
    gap:10px;
    margin-bottom:12px;
  }
  .pkc-col-1,.pkc-col-2,.pkc-col-3,.pkc-col-4{min-width:0}
  .pkc-add-btn{
    width:100%;height:46px;border:1px solid var(--pkc-line);border-radius:12px;background:#fff;cursor:pointer;font-size:1.2rem;font-weight:900;
  }

  .pkc-helper{margin-top:6px;font-size:.78rem;color:#64748b;min-height:18px}
  .pkc-hidden{display:none}

  .pkc-combo{position:relative}
  .pkc-combo-menu{
    position:absolute;left:0;right:0;top:calc(100% + 6px);
    background:#fff;border:1px solid var(--pkc-line);border-radius:14px;
    box-shadow:0 20px 38px rgba(15,23,42,.10);
    max-height:260px;overflow:auto;z-index:12;display:none;
  }
  .pkc-combo.is-open .pkc-combo-menu{display:block}
  .pkc-combo-item{padding:12px;border-bottom:1px solid #f1f5f9;cursor:pointer}
  .pkc-combo-item:last-child{border-bottom:0}
  .pkc-combo-item:hover{background:#f8fafc}
  .pkc-combo-item-main{font-weight:800;color:#0f172a}
  .pkc-combo-item-sub{margin-top:4px;font-size:.8rem;color:#64748b}

  .pkc-item-list{border:1px solid #e5e7eb;border-radius:14px;background:#fff;overflow:hidden}
  .pkc-phase-group{border-bottom:1px solid #eef2f7}
  .pkc-phase-group:last-child{border-bottom:0}
  .pkc-phase-head{
    padding:12px 16px;background:#f8fafc;border-bottom:1px solid #eef2f7;
    display:flex;align-items:center;justify-content:space-between;gap:12px;font-weight:900;color:#0f172a;
  }
  .pkc-item-row{
    display:flex;align-items:center;justify-content:space-between;gap:14px;padding:14px 16px;border-bottom:1px solid #f1f5f9;
  }
  .pkc-item-row:last-child{border-bottom:0}
  .pkc-item-name{font-weight:800;color:#0f172a}
  .pkc-item-meta{margin-top:4px;display:flex;gap:14px;flex-wrap:wrap;color:#64748b;font-size:.82rem}
  .pkc-item-right{display:flex;align-items:center;gap:10px}
  .pkc-qty{display:inline-flex;align-items:center;justify-content:center;padding:6px 10px;border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;font-weight:800;font-size:.82rem}
  .pkc-trash{width:34px;height:34px;border:0;background:#fff1f2;color:#e11d48;border-radius:10px;cursor:pointer}
  .pkc-empty-mini{padding:24px 14px;text-align:center;color:#64748b;font-size:.9rem}

  .pkc-fast-match{
    margin-top:10px;border:1px solid #bfdbfe;background:#eff6ff;border-radius:14px;padding:12px;
  }
  .pkc-fast-match-head{
    display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:8px;
  }
  .pkc-fast-match-title{font-weight:900;color:#1e3a8a}
  .pkc-fast-match-sub{color:#475569;font-size:.82rem}
  .pkc-fast-match-lots{display:grid;gap:8px}
  .pkc-fast-match-item{
    background:#fff;border:1px solid #dbeafe;border-radius:12px;padding:10px 12px;
    display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;
  }
  .pkc-fast-match-item-main{font-size:.84rem;color:#0f172a;font-weight:800}
  .pkc-fast-match-item-sub{font-size:.78rem;color:#64748b;margin-top:3px}
  .pkc-fast-pill{
    display:inline-flex;align-items:center;justify-content:center;padding:6px 10px;border-radius:999px;background:#dcfce7;color:#166534;font-size:.75rem;font-weight:900;
  }

  .pkc-side-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px}
  .pkc-side-title{font-size:1rem;font-weight:950;color:#0f172a}
  .pkc-side-sub{margin-top:4px;color:#64748b;font-size:.86rem}
  .pkc-fast-grid{display:grid;gap:12px}
  .pkc-fast-card{
    background:#fff;border:1px solid var(--pkc-line);border-radius:16px;padding:14px;cursor:pointer;
    transition:transform .16s ease, box-shadow .16s ease, border-color .16s ease;
  }
  .pkc-fast-card:hover{transform:translateY(-2px);box-shadow:0 14px 26px rgba(15,23,42,.08);border-color:#93c5fd}
  .pkc-fast-card.is-muted{opacity:.72}
  .pkc-fast-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:10px}
  .pkc-fast-name{font-size:.95rem;font-weight:900;color:#0f172a;line-height:1.2}
  .pkc-fast-code{margin-top:4px;color:#2563eb;font-size:.84rem;font-weight:800}
  .pkc-fast-subline{margin-top:3px;color:#64748b;font-size:.78rem}
  .pkc-fast-badge{
    display:inline-flex;align-items:center;justify-content:center;padding:6px 10px;border-radius:999px;font-size:.72rem;font-weight:900;white-space:nowrap;
  }
  .pkc-fast-badge.is-green{background:#d1fae5;color:#047857;border:1px solid #a7f3d0}
  .pkc-fast-badge.is-gray{background:#e2e8f0;color:#475569;border:1px solid #cbd5e1}
  .pkc-fast-metrics{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
  .pkc-fast-metric{background:#f8fafc;border:1px solid #eef2f7;border-radius:12px;padding:10px 8px;text-align:center}
  .pkc-fast-metric-value{font-size:1rem;font-weight:950;color:#0f172a;line-height:1}
  .pkc-fast-metric-label{margin-top:6px;font-size:.75rem;color:#64748b}
  .pkc-fast-progress{margin-top:12px;height:7px;border-radius:999px;background:#e5e7eb;overflow:hidden}
  .pkc-fast-progress>span{display:block;height:100%;border-radius:999px;background:linear-gradient(90deg,#10b981,#059669)}

  .pkc-form-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:18px}
  .pkc-import-status{margin-bottom:12px;font-size:.86rem;color:#475569;min-height:20px}
  .pkc-import-status.is-loading{color:#2563eb;font-weight:800}
  .pkc-import-status.is-error{color:#e11d48;font-weight:800}
  .pkc-import-status.is-ok{color:#059669;font-weight:800}

  @media (max-width: 1100px){
    .pkc-layout{grid-template-columns:1fr}
  }

  @media (max-width: 900px){
    .pkc-add-grid{grid-template-columns:1fr}
    .pkc-delivery-grid{grid-template-columns:1fr}
  }

  @media (max-width: 760px){
    .pkc-form-grid{grid-template-columns:1fr}
    .pkc-title{font-size:1.7rem}
    .pkc-form-actions{flex-direction:column}
  }
</style>
@endpush

@push('scripts')
<script>
(function(){
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
  const products = @json($products ?? []);
  const fastFlowBatches = @json($fastFlowCards ?? []);
  const aiImportUrl = @json($aiImportUrl);

  const createForm = document.getElementById('createForm');
  const itemsJsonInput = document.getElementById('itemsJsonInput');
  const deliveriesJsonInput = document.getElementById('deliveriesJsonInput');
  const itemsCounter = document.getElementById('itemsCounter');
  const deliveryCounter = document.getElementById('deliveryCounter');
  const createItemsList = document.getElementById('createItemsList');
  const deliveryPlans = document.getElementById('deliveryPlans');

  const totalPhasesInput = document.getElementById('total_phases');
  const newItemQty = document.getElementById('newItemQty');
  const newItemLocation = document.getElementById('newItemLocation');
  const newItemDelivery = document.getElementById('newItemDelivery');
  const newItemHelper = document.getElementById('newItemHelper');

  const productCombo = document.getElementById('productCombo');
  const productSearchInput = document.getElementById('productSearchInput');
  const productSearchMenu = document.getElementById('productSearchMenu');
  const newItemSku = document.getElementById('newItemSku');

  const btnOpenAiImport = document.getElementById('btnOpenAiImport');
  const aiImportInput = document.getElementById('aiImportInput');
  const aiImportStatus = document.getElementById('aiImportStatus');
  const fastFlowGrid = document.getElementById('fastFlowGrid');
  const fastFlowMatchBox = document.getElementById('fastFlowMatchBox');

  let formItems = [];
  let selectedProduct = null;

  function esc(str){
    return String(str ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function normalize(str){
    return String(str ?? '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .trim();
  }

  function getTotalPhases(){
    const n = Number(totalPhasesInput.value || 1);
    return Math.max(1, Math.min(12, n));
  }

  function setImportStatus(text, cls=''){
    aiImportStatus.className = 'pkc-import-status';
    if(cls) aiImportStatus.classList.add(cls);
    aiImportStatus.textContent = text || '';
  }

  function getFastFlowMatchesByValues(sku, name){
    const skuNorm = normalize(sku);
    const nameNorm = normalize(name);

    return fastFlowBatches.filter(batch => {
      if(String(batch.status || '') !== 'active') return false;

      const batchSku = normalize(batch.sku);
      const batchName = normalize(batch.product_name);

      return (skuNorm && batchSku && batchSku === skuNorm)
        || (nameNorm && batchName && batchName === nameNorm);
    });
  }

  function getFastFlowMatches(product){
    if(!product) return [];
    return getFastFlowMatchesByValues(product.sku, product.name);
  }

  function getFastFlowSummary(matches){
    return matches.reduce((acc, item) => {
      acc.lots += 1;
      acc.boxes += Number(item.available_boxes || 0);
      acc.units += Number(item.available_units || 0);
      return acc;
    }, { lots:0, boxes:0, units:0 });
  }

  function renderFastFlowMatch(product){
    const matches = getFastFlowMatches(product);

    if(!product || !matches.length){
      fastFlowMatchBox.hidden = true;
      fastFlowMatchBox.innerHTML = '';
      return;
    }

    const summary = getFastFlowSummary(matches);

    fastFlowMatchBox.hidden = false;
    fastFlowMatchBox.innerHTML = `
      <div class="pkc-fast-match-head">
        <div>
          <div class="pkc-fast-match-title">Disponible en Fast Flow</div>
          <div class="pkc-fast-match-sub">${summary.lots} lote(s) · ${summary.boxes} caja(s) · ${summary.units} pieza(s)</div>
        </div>
        <span class="pkc-fast-pill">Listo para surtir</span>
      </div>

      <div class="pkc-fast-match-lots">
        ${matches.map(batch => `
          <div class="pkc-fast-match-item">
            <div>
              <div class="pkc-fast-match-item-main">${esc(batch.batch_code)} · ${esc(batch.product_name)}</div>
              <div class="pkc-fast-match-item-sub">${esc(batch.warehouse_name || 'Almacén')} · ${Number(batch.available_boxes || 0)} caja(s) · ${Number(batch.available_units || 0)} pieza(s)</div>
            </div>
            <span class="pkc-fast-pill">${Number(batch.available_boxes || 0)} cajas</span>
          </div>
        `).join('')}
      </div>
    `;
  }

  function rebuildDeliverySelect(){
    const total = getTotalPhases();
    const options = [];
    for(let i = 1; i <= total; i++){
      options.push(`<option value="${i}">Entrega ${i}</option>`);
    }
    newItemDelivery.innerHTML = options.join('');
    deliveryCounter.textContent = `${total} entrega${total === 1 ? '' : 's'}`;
  }

  function renderDeliveryPlans(){
    const total = getTotalPhases();
    const html = [];

    for(let i = 1; i <= total; i++){
      html.push(`
        <div class="pkc-delivery-card">
          <div class="pkc-delivery-head">
            <div class="pkc-delivery-title">Entrega ${i}</div>
            <span class="pkc-count">Fase ${i}</span>
          </div>

          <div class="pkc-delivery-grid">
            <div class="pkc-field">
              <label>Fecha programada</label>
              <input type="datetime-local" data-delivery-date="${i}">
            </div>

            <div class="pkc-field">
              <label>Qué se va a llevar / notas de la entrega</label>
              <input type="text" data-delivery-notes="${i}" placeholder="Ej. primera parte del pedido, equipos urgentes, consumibles, etc.">
            </div>
          </div>
        </div>
      `);
    }

    deliveryPlans.innerHTML = html.join('');
  }

  function filterProducts(query){
    const q = String(query || '').trim().toLowerCase();
    if(!q){
      return products.slice(0, 40);
    }

    return products.filter(p => {
      const text = [
        p.sku,
        p.name,
        p.brand_name,
        p.model_name,
        p.default_location_code
      ].join(' ').toLowerCase();

      return text.includes(q);
    }).slice(0, 60);
  }

  function renderProductMenu(query=''){
    const rows = filterProducts(query);

    if(!rows.length){
      productSearchMenu.innerHTML = `<div class="pkc-combo-item"><div class="pkc-combo-item-main">Sin resultados</div></div>`;
      return;
    }

    productSearchMenu.innerHTML = rows.map((p) => {
      const matches = getFastFlowMatches(p);
      const summary = getFastFlowSummary(matches);

      return `
        <div class="pkc-combo-item" data-sku="${esc(p.sku)}">
          <div class="pkc-combo-item-main">${esc(p.sku)} · ${esc(p.name)}</div>
          <div class="pkc-combo-item-sub">
            ${p.brand_name ? esc(p.brand_name)+' · ' : ''}${p.model_name ? esc(p.model_name)+' · ' : ''}Stock ${Number(p.available_stock || 0)}${p.default_location_code ? ' · '+esc(p.default_location_code) : ''}${summary.lots ? ' · Fast Flow '+summary.boxes+' cajas' : ''}
          </div>
        </div>
      `;
    }).join('');
  }

  function openCombo(){
    productCombo.classList.add('is-open');
    renderProductMenu(productSearchInput.value);
  }

  function closeCombo(){
    productCombo.classList.remove('is-open');
  }

  function findProductBySku(sku){
    return products.find(p => String(p.sku || '').toUpperCase() === String(sku || '').toUpperCase()) || null;
  }

  function findProductByName(name){
    return products.find(p => normalize(p.name) === normalize(name)) || null;
  }

  function selectProduct(product){
    if(!product) return;

    selectedProduct = product;
    newItemSku.value = product.sku;
    productSearchInput.value = `${product.sku} · ${product.name}`;
    newItemLocation.value = product.default_location_code || '';

    const stock = Number(product.available_stock || 0);
    const matches = getFastFlowMatches(product);
    const summary = getFastFlowSummary(matches);

    newItemQty.max = stock > 0 ? String(stock) : '';
    newItemQty.placeholder = stock > 0 ? `Máx. ${stock}` : 'Sin stock';

    newItemHelper.textContent = stock > 0
      ? `Stock actual: ${stock}. Ubicación sugerida: ${product.default_location_code || 'Sin ubicación WMS'}${summary.lots ? `. Fast Flow: ${summary.boxes} cajas disponibles en ${summary.lots} lote(s).` : '.'}`
      : `Este producto no tiene stock disponible ahorita${summary.lots ? `, pero existe en Fast Flow con ${summary.boxes} caja(s).` : '.'}`;

    renderFastFlowMatch(product);
    closeCombo();
  }

  function clearSelectedProduct(){
    selectedProduct = null;
    newItemSku.value = '';
    newItemLocation.value = '';
    newItemQty.max = '';
    newItemHelper.textContent = 'Selecciona un producto del inventario.';
    renderFastFlowMatch(null);
  }

  function groupFormItemsByPhase(){
    const grouped = {};
    formItems.forEach(item => {
      const phase = Number(item.delivery_phase || 1);
      if(!grouped[phase]) grouped[phase] = [];
      grouped[phase].push(item);
    });
    return grouped;
  }

  function renderFormItems(){
    itemsCounter.textContent = `${formItems.length} producto${formItems.length === 1 ? '' : 's'}`;

    if(!formItems.length){
      createItemsList.innerHTML = `<div class="pkc-empty-mini">Agrega productos desde el buscador o importa un PDF / Excel con IA.</div>`;
      return;
    }

    const grouped = groupFormItemsByPhase();
    const phases = Object.keys(grouped).map(Number).sort((a,b) => a - b);

    createItemsList.innerHTML = phases.map(phase => `
      <div class="pkc-phase-group">
        <div class="pkc-phase-head">
          <span>Entrega ${phase}</span>
          <span>${grouped[phase].length} producto${grouped[phase].length === 1 ? '' : 's'}</span>
        </div>

        ${grouped[phase].map((item) => `
          <div class="pkc-item-row">
            <div>
              <div class="pkc-item-name">${esc(item.product_name)}</div>
              <div class="pkc-item-meta">
                ${item.product_sku ? `<span>${esc(item.product_sku)}</span>` : `<span>Sin SKU WMS</span>`}
                <span>${esc(item.location_code || 'Sin ubicación')}</span>
                ${item.brand_name ? `<span>${esc(item.brand_name)}</span>` : ''}
                ${item.model_name ? `<span>${esc(item.model_name)}</span>` : ''}
                ${item.ai_label ? `<span>${esc(item.ai_label)}</span>` : ''}
                ${item.source_ai ? `<span>IA</span>` : ''}
              </div>
            </div>
            <div class="pkc-item-right">
              <span class="pkc-qty">${Number(item.quantity_required || 0)} uds</span>
              <button type="button" class="pkc-trash" data-remove-index="${formItems.indexOf(item)}">🗑</button>
            </div>
          </div>
        `).join('')}
      </div>
    `).join('');
  }

  function buildDeliveriesPayload(){
    const total = getTotalPhases();
    const deliveries = [];

    for(let i = 1; i <= total; i++){
      const dateInput = document.querySelector(`[data-delivery-date="${i}"]`);
      const notesInput = document.querySelector(`[data-delivery-notes="${i}"]`);

      deliveries.push({
        phase: i,
        title: `Entrega ${i}`,
        scheduled_for: dateInput?.value || null,
        notes: notesInput?.value || ''
      });
    }

    return deliveries;
  }

  totalPhasesInput.addEventListener('input', function(){
    let value = Number(this.value || 1);
    if(value < 1) value = 1;
    if(value > 12) value = 12;
    this.value = value;

    rebuildDeliverySelect();
    renderDeliveryPlans();
    renderFormItems();
  });

  productSearchInput.addEventListener('focus', openCombo);
  productSearchInput.addEventListener('input', function(){
    if(!this.value.trim()){
      clearSelectedProduct();
    }
    openCombo();
  });

  document.addEventListener('click', function(e){
    if(!productCombo.contains(e.target)){
      closeCombo();
    }
  });

  productSearchMenu.addEventListener('click', function(e){
    const item = e.target.closest('[data-sku]');
    if(!item) return;
    const product = findProductBySku(item.dataset.sku);
    if(product){
      selectProduct(product);
    }
  });

  document.getElementById('btnAddItem').addEventListener('click', function(){
    const sku = String(newItemSku.value || '').toUpperCase().trim();
    const qty = Number(newItemQty.value || 0);
    const location = String(newItemLocation.value || '').trim();
    const deliveryPhase = Number(newItemDelivery.value || 1);

    if(!sku){
      alert('Selecciona un producto del buscador.');
      return;
    }

    const product = findProductBySku(sku);
    if(!product){
      alert('Producto no encontrado.');
      return;
    }

    const available = Number(product.available_stock || 0);
    if(available <= 0){
      alert('Ese producto no tiene stock disponible ahorita.');
      return;
    }

    if(qty < 1){
      alert(`La cantidad debe ser mayor a 0. Disponible: ${available}`);
      return;
    }

    if(qty > available){
      alert(`La cantidad no puede ser mayor al stock disponible (${available}).`);
      return;
    }

    formItems.push({
      product_id: product.id,
      product_sku: product.sku,
      product_name: product.name,
      location_code: location || product.default_location_code || '',
      quantity_required: qty,
      quantity_picked: 0,
      picked: false,
      delivery_phase: deliveryPhase,
      description: product.excerpt || '',
      brand_name: product.brand_name || '',
      model_name: product.model_name || '',
      ai_label: '',
      requested_quantity: qty,
      available_stock: available,
      source_ai: false,
      match_confidence: 100
    });

    productSearchInput.value = '';
    clearSelectedProduct();
    newItemQty.value = '';
    renderFormItems();
  });

  createItemsList.addEventListener('click', function(e){
    const btn = e.target.closest('[data-remove-index]');
    if(!btn) return;
    const index = Number(btn.dataset.removeIndex);
    if(index >= 0){
      formItems.splice(index, 1);
      renderFormItems();
    }
  });

  createForm.addEventListener('submit', function(e){
    if(!formItems.length){
      e.preventDefault();
      alert('Agrega al menos un producto.');
      return;
    }

    itemsJsonInput.value = JSON.stringify(formItems);
    deliveriesJsonInput.value = JSON.stringify(buildDeliveriesPayload());
  });

  btnOpenAiImport.addEventListener('click', function(){
    aiImportInput.click();
  });

  aiImportInput.addEventListener('change', async function(){
    const files = Array.from(this.files || []);
    if(!files.length) return;

    const fd = new FormData();
    files.forEach(file => fd.append('files[]', file));

    setImportStatus('Leyendo documentos con IA…', 'is-loading');

    try {
      const response = await fetch(aiImportUrl, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
        },
        body: fd
      });

      const data = await response.json();

      if(!response.ok || !data.ok){
        throw new Error(data.error || 'No se pudieron importar los productos.');
      }

      if(Array.isArray(data.items) && data.items.length){
        formItems.push(...data.items);
        renderFormItems();
      }

      let msg = `Se importaron ${Number(data.imported_count || 0)} producto(s).`;
      if(Array.isArray(data.warnings) && data.warnings.length){
        msg += ' Avisos: ' + data.warnings.join(' | ');
      }

      setImportStatus(msg, 'is-ok');
    } catch (error) {
      setImportStatus(error.message || 'Error al importar con IA.', 'is-error');
    } finally {
      aiImportInput.value = '';
    }
  });

  if(fastFlowGrid){
    fastFlowGrid.addEventListener('click', function(e){
      const card = e.target.closest('.pkc-fast-card');
      if(!card) return;

      const sku = card.dataset.fastSku || '';
      const name = card.dataset.fastName || '';

      const product = findProductBySku(sku) || findProductByName(name);
      if(product){
        selectProduct(product);
        newItemQty.focus();
      } else {
        productSearchInput.value = sku || name;
        openCombo();
      }
    });
  }

  rebuildDeliverySelect();
  renderDeliveryPlans();
  renderFormItems();
})();
</script>
@endpush