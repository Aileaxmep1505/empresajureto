@extends('layouts.app')

@section('title', 'WMS · Nueva tarea picking')

@php
  $indexUrl = route('admin.wms.picking.v2');
  $storeUrl = route('admin.wms.picking.v2.store');
  $pickingCssVersion = file_exists(public_path('css/picking-create.css'))
      ? filemtime(public_path('css/picking-create.css'))
      : time();

  $usersCatalog = collect($users ?? [])->map(fn($u) => [
      'id'   => data_get($u, 'id'),
      'name' => (string) data_get($u, 'name', 'Usuario'),
  ])->values();

  $productsCatalog = collect($products ?? [])->map(fn($p) => [
      'id'            => data_get($p, 'id'),
      'name'          => (string) data_get($p, 'name', data_get($p, 'product_name', 'Producto')),
      'sku'           => (string) data_get($p, 'sku', ''),
      'brand'         => (string) data_get($p, 'brand', data_get($p, 'marca', '')),
      'model'         => (string) data_get($p, 'model', data_get($p, 'modelo', '')),
      'description'   => (string) data_get($p, 'description', data_get($p, 'descripcion', '')),
      'location_code' => (string) data_get($p, 'location_code', data_get($p, 'default_location_code', data_get($p, 'location', data_get($p, 'ubicacion', '')))),
      'image'         => (string) data_get($p, 'image', data_get($p, 'image_url', data_get($p, 'photo', data_get($p, 'thumbnail', '')))),
      'price'         => (float) data_get($p, 'price', data_get($p, 'precio', 0)),
      'stock'         => (int) data_get($p, 'stock', data_get($p, 'current_stock', data_get($p, 'existencia', data_get($p, 'qty', 0)))),
      'available_stock' => (int) data_get($p, 'available_stock', data_get($p, 'stock', data_get($p, 'current_stock', data_get($p, 'existencia', data_get($p, 'qty', 0))))),
      'is_fastflow'   => (bool) data_get($p, 'is_fastflow', false),
  ])->values();

  $fastFlowCards = collect($recentBatches ?? [])->map(fn($b) => [
      'batch_code'      => (string) data_get($b, 'batch_code', ''),
      'product_name'    => (string) data_get($b, 'product_name', 'Producto'),
      'sku'             => (string) data_get($b, 'sku', '—'),
      'warehouse_name'  => (string) data_get($b, 'warehouse_name', '—'),
      'available_boxes' => (int) data_get($b, 'available_boxes', 0),
      'available_units' => (int) data_get($b, 'available_units', 0),
  ])->values();
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('css/piking-create.css') }}?v={{ $pickingCssVersion }}">
@endpush

@section('content')
@if ($errors->any())
  <div style="max-width:1100px;margin:0 auto 16px auto;padding:14px 16px;border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;border-radius:12px;">
    <div style="font-weight:700;margin-bottom:8px;">No se pudo guardar la tarea:</div>
    <ul style="margin:0;padding-left:18px;">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="pkf-canvas">
  <form method="POST" action="{{ $storeUrl }}" id="createTaskForm" class="pkf-container">
    @csrf

    <header class="pkf-header">
      <a href="{{ $indexUrl }}" class="pkf-btn-back" title="Volver al listado">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
      </a>
      <div class="pkf-header-text">
        <h1 class="pkf-title">Crear tarea de picking</h1>
        <p class="pkf-subtitle">Configuración de surtido, reparto por fases y asignación de operario.</p>
      </div>
    </header>

    <section class="pkf-card">
      <div class="pkf-card-header">
        <div class="pkf-icon-box">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
        </div>
        <h2>Detalles de la operación</h2>
      </div>

      <div class="pkf-grid">
        <div class="pkf-field">
          <label for="taskNumberInput">Número de tarea <span class="pkf-required">*</span></label>
          <input type="text" name="task_number" id="taskNumberInput" value="{{ old('task_number', $nextTaskNumber ?? '') }}" required autofocus>
        </div>

        <div class="pkf-field">
          <label for="orderNumberInput">Orden de referencia</label>
          <input type="text" name="order_number" id="orderNumberInput" value="{{ old('order_number') }}" placeholder="Ej. SO-2024-001">
        </div>

        <div class="pkf-field">
          <label for="assignedUserIdInput">Operario asignado <span class="pkf-required">*</span></label>
          <div class="pkf-select-wrapper">
            <select name="assigned_user_id" id="assignedUserIdInput" required>
              <option value="" disabled selected>Seleccione un operario...</option>
              @foreach($usersCatalog as $user)
                <option value="{{ $user['id'] }}" @selected(old('assigned_user_id') == $user['id'])>{{ $user['name'] }}</option>
              @endforeach
            </select>
            <svg class="pkf-select-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
          </div>
        </div>

        <div class="pkf-field">
          <label for="priorityInput">Nivel de prioridad</label>
          <div class="pkf-select-wrapper">
            <select name="priority" id="priorityInput">
              <option value="normal" @selected(old('priority', 'normal') === 'normal')>Normal</option>
              <option value="low" @selected(old('priority') === 'low')>Baja</option>
              <option value="high" @selected(old('priority') === 'high')>Alta</option>
              <option value="urgent" @selected(old('priority') === 'urgent')>Urgente</option>
            </select>
            <svg class="pkf-select-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
          </div>
        </div>
      </div>
    </section>

    <section class="pkf-card">
      <div class="pkf-card-header">
        <div class="pkf-icon-box">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/></svg>
        </div>
        <h2>Logística y fases</h2>
      </div>

      <div class="pkf-grid pkf-grid-cols-2">
        <div class="pkf-field">
          <label>Fase inicial de entrega</label>

          <div class="pkf-stepper" data-stepper>
            <button
              type="button"
              class="pkf-stepper-btn"
              data-stepper-action="decrease"
              data-stepper-target="deliveryPhaseInput"
              aria-label="Disminuir fase inicial"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                <path d="M5 12h14"/>
              </svg>
            </button>

            <input
              type="number"
              name="delivery_phase"
              id="deliveryPhaseInput"
              min="1"
              value="{{ old('delivery_phase', 1) }}"
              class="pkf-stepper-input"
            >

            <button
              type="button"
              class="pkf-stepper-btn"
              data-stepper-action="increase"
              data-stepper-target="deliveryPhaseInput"
              aria-label="Aumentar fase inicial"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                <path d="M12 5v14"/>
                <path d="M5 12h14"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="pkf-field">
          <label>Total de entregas programadas</label>

          <div class="pkf-stepper" data-stepper>
            <button
              type="button"
              class="pkf-stepper-btn"
              data-stepper-action="decrease"
              data-stepper-target="totalPhasesInput"
              aria-label="Disminuir total de entregas"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                <path d="M5 12h14"/>
              </svg>
            </button>

            <input
              type="number"
              name="total_phases"
              id="totalPhasesInput"
              min="1"
              value="{{ old('total_phases', 1) }}"
              class="pkf-stepper-input"
            >

            <button
              type="button"
              class="pkf-stepper-btn"
              data-stepper-action="increase"
              data-stepper-target="totalPhasesInput"
              aria-label="Aumentar total de entregas"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                <path d="M12 5v14"/>
                <path d="M5 12h14"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div class="pkf-phase-tip">
        Si una tarea tiene varias fases, al agregar un producto podrás asignarlo a una sola entrega o repartirlo entre varias según la cantidad.
      </div>

      <div id="deliveriesContainer" class="pkf-deliveries-wrapper"></div>
    </section>

    <section class="pkf-card pkf-card-seamless">
      <div class="pkf-card-header pkf-flex-between">
        <div class="pkf-flex-start">
          <div class="pkf-icon-box">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
          </div>
          <div>
            <h2>Líneas de surtido</h2>
            <p class="pkf-subtitle-sm">Busca productos en un catálogo visual y decide si van en una sola fase o repartidos.</p>
          </div>
        </div>
        <div class="pkf-counter" id="productsCountBadge">0 ítems</div>
      </div>

      <div class="pkf-search-panel">
        <div class="pkf-search-row pkf-search-row-advanced">
          <div class="pkf-field pkf-field-grow">
            <label>Producto</label>

            <div class="pkf-product-select" id="productSelect">
              <input type="hidden" id="selectedProductId">
              <input type="hidden" id="selectedProductFastFlow" value="0">
              <input type="hidden" id="selectedFastFlowBatch" value="">

              <div class="pkf-product-select-trigger" id="productSelectTrigger">
                <div class="pkf-product-select-placeholder" id="productSelectPlaceholder">
                  Buscar producto...
                </div>
                <svg class="pkf-product-select-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="m6 9 6 6 6-6"/>
                </svg>
              </div>

              <div class="pkf-product-dropdown" id="productDropdown">
                <div class="pkf-product-search-box">
                  <input type="text" id="productSearchInput" placeholder="Buscar producto..." autocomplete="off">
                </div>

                <div class="pkf-product-results" id="productResults"></div>
              </div>
            </div>
          </div>

          <div class="pkf-field pkf-field-qty">
            <label>Cantidad</label>
            <input type="number" id="productQtyInput" min="1" max="1" value="1" title="Cantidad">
          </div>

          <div class="pkf-field pkf-field-loc">
            <label>Ubicación</label>
            <input type="text" id="productLocationInput" placeholder="Ubicación">
          </div>

          <button type="button" id="addProductBtn" class="pkf-btn pkf-btn-secondary">
            <span>Agregar</span>
            <kbd class="pkf-kbd">Enter</kbd>
          </button>
        </div>

        <div class="pkf-preview-ribbon">
          <div class="pkf-ribbon-item"><span class="pkf-ribbon-label">SKU</span> <span class="pkf-ribbon-val" id="previewSkuText">—</span></div>
          <div class="pkf-ribbon-item"><span class="pkf-ribbon-label">Marca</span> <span class="pkf-ribbon-val" id="previewBrandText">—</span></div>
          <div class="pkf-ribbon-item"><span class="pkf-ribbon-label">Ubicación</span> <span class="pkf-ribbon-val" id="previewLocationText">—</span></div>
          <div class="pkf-ribbon-item pkf-ribbon-desc"><span class="pkf-ribbon-val pkf-text-muted" id="previewDescriptionText">Esperando selección...</span></div>
        </div>
      </div>

      @if($fastFlowCards->count())
        <div class="pkf-fast-flow-wrapper">
          <div class="pkf-fast-flow-header">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="pkf-text-amber"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            <span>Sugerencias Fast Flow</span>
          </div>

          <div class="pkf-fast-scroll">
            @foreach($fastFlowCards as $card)
              <button type="button"
                      class="pkf-fast-pill"
                      data-fast-batch="{{ $card['batch_code'] }}"
                      data-fast-sku="{{ $card['sku'] }}"
                      data-fast-name="{{ $card['product_name'] }}"
                      data-fast-location="{{ $card['warehouse_name'] }}"
                      data-fast-boxes="{{ $card['available_boxes'] }}"
                      data-fast-units="{{ $card['available_units'] }}">
                <span class="pkf-fast-pill-name" title="{{ $card['product_name'] }}">{{ \Illuminate\Support\Str::limit($card['product_name'], 25) }}</span>
                <span class="pkf-fast-pill-badge">Stock: {{ $card['available_units'] }}</span>
              </button>
            @endforeach
          </div>

          <div class="pkf-fastflow-inspector" id="fastFlowInspector">
            <div class="pkf-fastflow-empty" id="fastFlowEmpty">
              <div class="pkf-fastflow-empty-title">Selecciona una sugerencia Fast Flow</div>
              <div class="pkf-fastflow-empty-sub">
                Aquí verás el lote, contenido, stock disponible e instrucciones para surtir rápido desde Fast Flow.
              </div>
            </div>

            <div class="pkf-fastflow-content" id="fastFlowContent" style="display:none;">
              <div class="pkf-fastflow-top">
                <div>
                  <div class="pkf-fastflow-kicker">Detalle Fast Flow</div>
                  <div class="pkf-fastflow-title" id="ffiProductName">—</div>
                  <div class="pkf-fastflow-sub" id="ffiSkuLine">—</div>
                </div>
                <div class="pkf-fastflow-badge" id="ffiStatusBadge">Listo para surtir</div>
              </div>

              <div class="pkf-fastflow-grid">
                <div class="pkf-fastflow-card">
                  <div class="pkf-fastflow-card-label">Batch / Lote</div>
                  <div class="pkf-fastflow-card-value" id="ffiBatchCode">—</div>
                </div>

                <div class="pkf-fastflow-card">
                  <div class="pkf-fastflow-card-label">Almacén</div>
                  <div class="pkf-fastflow-card-value" id="ffiWarehouse">—</div>
                </div>

                <div class="pkf-fastflow-card">
                  <div class="pkf-fastflow-card-label">Cajas disponibles</div>
                  <div class="pkf-fastflow-card-value" id="ffiBoxes">0</div>
                </div>

                <div class="pkf-fastflow-card">
                  <div class="pkf-fastflow-card-label">Stock disponible</div>
                  <div class="pkf-fastflow-card-value" id="ffiUnits">0</div>
                </div>
              </div>

              <div class="pkf-fastflow-notes">
                <div class="pkf-fastflow-notes-title">Qué contiene</div>
                <div class="pkf-fastflow-notes-text" id="ffiContainsText">—</div>
              </div>

              <div class="pkf-fastflow-tips">
                <div class="pkf-fastflow-tip">
                  <strong>Ubicación automática:</strong>
                  <span>Se asignará FAST FLOW al agregar este producto.</span>
                </div>
                <div class="pkf-fastflow-tip">
                  <strong>Cantidad máxima:</strong>
                  <span id="ffiMaxQtyText">Solo podrás surtir hasta el stock disponible.</span>
                </div>
                <div class="pkf-fastflow-tip">
                  <strong>Sugerencia operativa:</strong>
                  <span id="ffiAdviceText">Valida lote y unidades antes de confirmar la distribución por fase.</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      @endif

      <div class="pkf-items-container" id="itemsContainer">
        <div class="pkf-empty-state" id="emptyProductsState">
          <div class="pkf-empty-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
          </div>
          <h3>Lista de surtido vacía</h3>
          <p>Busca un producto y al agregarlo podrás decidir si va en una sola fase o repartido entre varias.</p>
        </div>
      </div>
    </section>

    <section class="pkf-card">
      <div class="pkf-field">
        <label for="notesInput">Instrucciones u observaciones (Opcional)</label>
        <textarea name="notes" id="notesInput" placeholder="Añade comentarios para el operario...">{{ old('notes') }}</textarea>
      </div>
    </section>

    <div class="pkf-action-bar">
      <div class="pkf-action-inner">
        <span class="pkf-status-text">Proceso sin guardar</span>
        <div class="pkf-action-buttons">
          <a href="{{ $indexUrl }}" class="pkf-btn pkf-btn-ghost">Descartar</a>
          <button type="submit" class="pkf-btn pkf-btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/></svg>
            Crear Tarea
          </button>
        </div>
      </div>
    </div>
  </form>
</div>

<div class="pkf-modal" id="phaseAllocationModal" aria-hidden="true">
  <div class="pkf-modal-backdrop" data-close-modal></div>

  <div class="pkf-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="phaseAllocationTitle">
    <div class="pkf-modal-header">
      <div>
        <h3 id="phaseAllocationTitle">Distribución por entregas</h3>
        <p id="phaseAllocationSubtitle">Decide si el producto va en una sola fase o repartido entre varias.</p>
      </div>
      <button type="button" class="pkf-modal-close" data-close-modal title="Cerrar">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
      </button>
    </div>

    <div class="pkf-modal-body">
      <div class="pkf-modal-summary" id="phaseAllocationSummary"></div>

      <div class="pkf-mode-switch">
        <label class="pkf-mode-card is-active" data-mode-card="single">
          <input type="radio" name="phaseAllocationMode" value="single" checked>
          <div class="pkf-mode-card-title">Una sola fase</div>
          <div class="pkf-mode-card-sub">Todo el producto se asigna a una única entrega.</div>
        </label>

        <label class="pkf-mode-card" data-mode-card="split">
          <input type="radio" name="phaseAllocationMode" value="split">
          <div class="pkf-mode-card-title">Repartir entre fases</div>
          <div class="pkf-mode-card-sub">Divide la cantidad entre varias entregas.</div>
        </label>
      </div>

      <div class="pkf-modal-section" id="singlePhaseSection">
        <div class="pkf-field">
          <label for="singlePhaseSelect">Asignar todo a la fase</label>
          <select id="singlePhaseSelect"></select>
        </div>
      </div>

      <div class="pkf-modal-section" id="splitPhaseSection" style="display:none;">
        <div class="pkf-split-head">
          <div class="pkf-split-title">Cantidad por fase</div>
          <div class="pkf-split-counter" id="splitCounterText">0 / 0 asignado</div>
        </div>

        <div id="phaseSplitInputs" class="pkf-split-grid"></div>

        <div class="pkf-split-help">
          La suma de todas las fases debe ser exactamente igual a la cantidad total del producto.
        </div>
      </div>

      <div class="pkf-modal-error" id="phaseAllocationError" style="display:none;"></div>
    </div>

    <div class="pkf-modal-footer">
      <button type="button" class="pkf-btn pkf-btn-ghost" data-close-modal>Cancelar</button>
      <button type="button" class="pkf-btn pkf-btn-primary" id="confirmPhaseAllocationBtn">Confirmar distribución</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('createTaskForm');

  const productSelect = document.getElementById('productSelect');
  const productSelectTrigger = document.getElementById('productSelectTrigger');
  const productSelectPlaceholder = document.getElementById('productSelectPlaceholder');
  const productDropdown = document.getElementById('productDropdown');
  const productResults = document.getElementById('productResults');
  const searchInput = document.getElementById('productSearchInput');

  const selectedProductIdInput = document.getElementById('selectedProductId');
  const selectedProductFastFlowInput = document.getElementById('selectedProductFastFlow');
  const selectedFastFlowBatchInput = document.getElementById('selectedFastFlowBatch');

  const qtyInput = document.getElementById('productQtyInput');
  const locInput = document.getElementById('productLocationInput');
  const addBtn = document.getElementById('addProductBtn');

  const itemsContainer = document.getElementById('itemsContainer');
  const emptyState = document.getElementById('emptyProductsState');
  const badge = document.getElementById('productsCountBadge');
  const deliveriesContainer = document.getElementById('deliveriesContainer');
  const totalPhasesInput = document.getElementById('totalPhasesInput');
  const phaseInput = document.getElementById('deliveryPhaseInput');
  const statusText = document.querySelector('.pkf-status-text');

  const pSku = document.getElementById('previewSkuText');
  const pBrand = document.getElementById('previewBrandText');
  const pLoc = document.getElementById('previewLocationText');
  const pDesc = document.getElementById('previewDescriptionText');

  const fastFlowInspector = document.getElementById('fastFlowInspector');
  const fastFlowEmpty = document.getElementById('fastFlowEmpty');
  const fastFlowContent = document.getElementById('fastFlowContent');
  const ffiProductName = document.getElementById('ffiProductName');
  const ffiSkuLine = document.getElementById('ffiSkuLine');
  const ffiBatchCode = document.getElementById('ffiBatchCode');
  const ffiWarehouse = document.getElementById('ffiWarehouse');
  const ffiBoxes = document.getElementById('ffiBoxes');
  const ffiUnits = document.getElementById('ffiUnits');
  const ffiContainsText = document.getElementById('ffiContainsText');
  const ffiMaxQtyText = document.getElementById('ffiMaxQtyText');
  const ffiAdviceText = document.getElementById('ffiAdviceText');

  const modal = document.getElementById('phaseAllocationModal');
  const modalSummary = document.getElementById('phaseAllocationSummary');
  const modalError = document.getElementById('phaseAllocationError');
  const singlePhaseSelect = document.getElementById('singlePhaseSelect');
  const splitPhaseSection = document.getElementById('splitPhaseSection');
  const singlePhaseSection = document.getElementById('singlePhaseSection');
  const phaseSplitInputs = document.getElementById('phaseSplitInputs');
  const splitCounterText = document.getElementById('splitCounterText');
  const confirmPhaseAllocationBtn = document.getElementById('confirmPhaseAllocationBtn');
  const modeCards = Array.from(document.querySelectorAll('[data-mode-card]'));
  const modeRadios = Array.from(document.querySelectorAll('input[name="phaseAllocationMode"]'));

  const catalog = @json($productsCatalog ?? []);
  const fastFlowCards = @json($fastFlowCards ?? []);

  let itemIndex = 0;
  let filteredProducts = [...catalog];
  let highlightedIndex = -1;
  let selectedProduct = null;
  let pendingProduct = null;
  let selectedFastFlowCard = null;

  const esc = str => String(str || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
  const normalize = str => String(str || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
  const money = value => '$' + Number(value || 0).toFixed(2);
  const makeLineId = () => {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
      return window.crypto.randomUUID();
    }
    return 'line-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);
  };

  const getTotalPhases = () => Math.max(1, Number(totalPhasesInput.value) || 1);

  const getInitialPhase = () => {
    const total = getTotalPhases();
    const value = Math.max(1, Number(phaseInput.value) || 1);
    return value > total ? total : value;
  };

  const clampStepperValue = (input) => {
    if (!input) return 1;

    const min = Number(input.min || 1);
    const max = input.max ? Number(input.max) : null;
    let value = Number(input.value || min);

    if (!Number.isFinite(value) || value < min) value = min;
    if (max !== null && Number.isFinite(max) && value > max) value = max;

    input.value = value;
    return value;
  };

  const syncPhaseLimits = () => {
    const total = Math.max(1, Number(totalPhasesInput.value) || 1);
    totalPhasesInput.value = total;

    phaseInput.max = total;

    if ((Number(phaseInput.value) || 1) > total) {
      phaseInput.value = total;
      phaseInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
  };

  const applyStepperChange = (input, delta) => {
    if (!input) return;

    const min = Number(input.min || 1);
    const max = input.max ? Number(input.max) : null;
    let value = Number(input.value || min);

    if (!Number.isFinite(value)) value = min;

    value += delta;

    if (value < min) value = min;
    if (max !== null && Number.isFinite(max) && value > max) value = max;

    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  };

  const resetPreview = () => {
    pSku.textContent = '—';
    pBrand.textContent = '—';
    pLoc.textContent = '—';
    pDesc.textContent = 'Esperando selección...';
    pDesc.classList.add('pkf-text-muted');
  };

  const updatePreview = p => {
    if (!p) return resetPreview();
    pSku.textContent = p.sku || '—';
    pBrand.textContent = p.brand || 'FAST FLOW';
    pLoc.textContent = p.location_code || '—';
    pDesc.textContent = p.description || 'Producto listo para surtir.';
    pDesc.classList.remove('pkf-text-muted');
  };

  const matchFastFlowByProduct = product => {
    if (!product) return null;

    return fastFlowCards.find(card => {
      const sameSku = normalize(card.sku) && normalize(card.sku) === normalize(product.sku);
      const sameName = normalize(card.product_name) === normalize(product.name);
      return sameSku || sameName;
    }) || null;
  };

  const getAvailableStock = (product) => {
    if (!product) return 0;

    const card = selectedFastFlowCard && (
      normalize(selectedFastFlowCard.sku) === normalize(product.sku) ||
      normalize(selectedFastFlowCard.product_name) === normalize(product.name)
    )
      ? selectedFastFlowCard
      : matchFastFlowByProduct(product);

    if (card) {
      return Math.max(0, Number(card.available_units) || 0);
    }

    return Math.max(0, Number(product.available_stock ?? product.stock) || 0);
  };

  const clearFastFlowSelectionVisual = () => {
    document.querySelectorAll('.pkf-fast-pill').forEach(btn => btn.classList.remove('is-selected'));
  };

  const markFastFlowSelectionVisual = (batchCode) => {
    clearFastFlowSelectionVisual();
    document.querySelectorAll('.pkf-fast-pill').forEach(btn => {
      if ((btn.dataset.fastBatch || '') === String(batchCode || '')) {
        btn.classList.add('is-selected');
      }
    });
  };

  const clearFastFlowInspector = () => {
    selectedFastFlowCard = null;
    selectedFastFlowBatchInput.value = '';
    clearFastFlowSelectionVisual();

    if (!fastFlowInspector) return;
    fastFlowEmpty.style.display = '';
    fastFlowContent.style.display = 'none';
  };

  const renderFastFlowInspector = (card, product = null) => {
    if (!card || !fastFlowInspector) {
      clearFastFlowInspector();
      return;
    }

    selectedFastFlowCard = card;
    selectedFastFlowBatchInput.value = card.batch_code || '';
    markFastFlowSelectionVisual(card.batch_code || '');

    const units = Math.max(0, Number(card.available_units) || 0);
    const boxes = Math.max(0, Number(card.available_boxes) || 0);

    ffiProductName.textContent = product?.name || card.product_name || 'Producto Fast Flow';
    ffiSkuLine.textContent = (card.sku || product?.sku || 'SKU sin registro') + ' · Ubicación automática: FAST FLOW';
    ffiBatchCode.textContent = card.batch_code || '—';
    ffiWarehouse.textContent = card.warehouse_name || '—';
    ffiBoxes.textContent = String(boxes);
    ffiUnits.textContent = String(units);

    ffiContainsText.textContent =
      `Este batch contiene ${boxes} caja${boxes === 1 ? '' : 's'} activas y ${units} unidad${units === 1 ? '' : 'es'} disponibles para surtido inmediato.`;

    ffiMaxQtyText.textContent =
      `Máximo permitido en esta selección: ${units} ${units === 1 ? 'unidad' : 'unidades'}.`;

    ffiAdviceText.textContent =
      units > 0
        ? 'Surtir primero desde Fast Flow reduce recorrido y acelera el picking. Verifica lote y después distribuye por fase.'
        : 'Este batch ya no tiene stock disponible. Selecciona otro batch o usa producto desde ubicación regular.';

    fastFlowEmpty.style.display = 'none';
    fastFlowContent.style.display = '';
  };

  const openDropdown = () => {
    productSelect.classList.add('is-open');
    renderProductOptions(searchInput.value);
    setTimeout(() => searchInput.focus(), 30);
  };

  const closeDropdown = () => {
    productSelect.classList.remove('is-open');
    highlightedIndex = -1;
  };

  const findCatalogProductByFastFlow = (card) => {
    if (!card) return null;

    const bySku = catalog.find(p => normalize(p.sku) && normalize(p.sku) === normalize(card.sku));
    if (bySku) return bySku;

    const byName = catalog.find(p => normalize(p.name) === normalize(card.product_name));
    if (byName) return byName;

    const fuzzy = catalog.find(p => {
      const name = normalize(p.name);
      const target = normalize(card.product_name);
      return name.includes(target) || target.includes(name);
    });

    return fuzzy || null;
  };

  const setSelectedProduct = (product, options = {}) => {
    selectedProduct = product || null;
    selectedProductIdInput.value = product?.id || '';
    selectedProductFastFlowInput.value = options.fastflow ? '1' : (product?.is_fastflow ? '1' : '0');

    if (product) {
      const availableStock = options.fastflowCard
        ? Math.max(0, Number(options.fastflowCard.available_units) || 0)
        : getAvailableStock(product);

      productSelect.classList.add('has-value');
      productSelectPlaceholder.textContent = product.sku
        ? `${product.sku} - ${product.name}`
        : product.name;

      searchInput.value = product.sku
        ? `${product.sku} ${product.name}`
        : product.name;

      updatePreview(product);

      if (options.fastflow || product.is_fastflow) {
        locInput.value = 'FAST FLOW';
        pLoc.textContent = 'FAST FLOW';
      } else if (!locInput.value) {
        locInput.value = product.location_code || '';
      }

      qtyInput.max = availableStock > 0 ? availableStock : 1;
      qtyInput.value = availableStock > 0 ? 1 : 0;
      qtyInput.disabled = availableStock <= 0;

      if (options.fastflowCard) {
        renderFastFlowInspector(options.fastflowCard, product);
      } else {
        const card = matchFastFlowByProduct(product);
        if (card) {
          renderFastFlowInspector(card, product);
          selectedProductFastFlowInput.value = '1';
          locInput.value = 'FAST FLOW';
          pLoc.textContent = 'FAST FLOW';
        } else {
          clearFastFlowInspector();
        }
      }
    } else {
      productSelect.classList.remove('has-value');
      productSelectPlaceholder.textContent = 'Buscar producto...';
      qtyInput.max = 1;
      qtyInput.value = 1;
      qtyInput.disabled = false;
      resetPreview();
      clearFastFlowInspector();
    }

    closeDropdown();
  };

  const setFastFlowSelection = (card) => {
    if (!card) return;

    const matchedProduct = findCatalogProductByFastFlow(card);
    const fallbackProduct = {
      id: '',
      name: card.product_name || 'Producto Fast Flow',
      sku: card.sku || '',
      brand: 'FAST FLOW',
      model: card.batch_code || '',
      description: `Batch ${card.batch_code || 'sin código'} disponible en ${card.warehouse_name || 'almacén'} con ${Number(card.available_boxes || 0)} cajas y ${Number(card.available_units || 0)} unidades.`,
      location_code: 'FAST FLOW',
      image: '',
      price: 0,
      stock: Math.max(0, Number(card.available_units) || 0),
      available_stock: Math.max(0, Number(card.available_units) || 0),
      is_fastflow: true,
    };

    const finalProduct = matchedProduct ? {
      ...matchedProduct,
      is_fastflow: true,
      stock: Math.max(0, Number(card.available_units) || 0),
      available_stock: Math.max(0, Number(card.available_units) || 0),
      location_code: 'FAST FLOW',
      description: matchedProduct.description || fallbackProduct.description,
    } : fallbackProduct;

    setSelectedProduct(finalProduct, {
      fastflow: true,
      fastflowCard: card,
    });

    locInput.value = 'FAST FLOW';
    pLoc.textContent = 'FAST FLOW';

    const availableStock = Math.max(0, Number(card.available_units) || 0);
    qtyInput.max = availableStock > 0 ? availableStock : 1;
    qtyInput.value = availableStock > 0 ? 1 : 0;
    qtyInput.disabled = availableStock <= 0;
  };

  const clearSelectedProduct = () => {
    selectedProduct = null;
    selectedProductIdInput.value = '';
    selectedProductFastFlowInput.value = '0';
    selectedFastFlowBatchInput.value = '';
    productSelect.classList.remove('has-value');
    productSelectPlaceholder.textContent = 'Buscar producto...';
    searchInput.value = '';
    locInput.value = '';
    qtyInput.max = 1;
    qtyInput.value = 1;
    qtyInput.disabled = false;
    resetPreview();
    clearFastFlowInspector();
  };

  const renderProductOptions = (query = '') => {
    const q = normalize(query);

    filteredProducts = catalog.filter(p => {
      if (!q) return true;
      return [
        normalize(p.name),
        normalize(p.sku),
        normalize(p.brand),
        normalize(p.model),
        normalize(p.description),
        normalize(`${p.sku} ${p.name}`),
        normalize(`${p.name} ${p.sku}`)
      ].some(v => v.includes(q));
    });

    if (!filteredProducts.length) {
      productResults.innerHTML = `<div class="pkf-product-empty">No se encontraron productos.</div>`;
      highlightedIndex = -1;
      return;
    }

    productResults.innerHTML = filteredProducts.map((p, index) => {
      const thumb = p.image
        ? `<img src="${esc(p.image)}" alt="${esc(p.name)}">`
        : `<div class="pkf-product-thumb-empty">IMG</div>`;

      const fastFlowMatch = matchFastFlowByProduct(p);
      const availableStock = getAvailableStock(p);
      const isOutOfStock = availableStock <= 0;

      return `
        <div class="pkf-product-option ${index === highlightedIndex ? 'is-active' : ''} ${isOutOfStock ? 'is-disabled' : ''}" data-index="${index}">
          <div class="pkf-product-thumb">${thumb}</div>

          <div class="pkf-product-main">
            <div class="pkf-product-title">
              <strong>${esc(p.sku || '')}</strong>${p.sku ? ' - ' : ''}${esc(p.name)}
            </div>

            <div class="pkf-product-price">${p.price ? money(p.price) : ''}</div>

            <div class="pkf-product-sub">
              ${p.brand ? `<span>${esc(p.brand)}</span>` : ''}
              ${p.model ? `<span>${esc(p.model)}</span>` : ''}
              ${p.location_code ? `<span>${esc(p.location_code)}</span>` : ''}
            </div>
          </div>

          <div class="pkf-product-side">
            ${fastFlowMatch || p.is_fastflow ? `<span class="pkf-product-badge pkf-product-badge-fastflow">Fast Flow</span>` : ''}
            <span class="pkf-product-badge ${isOutOfStock ? 'pkf-product-badge-stock-empty' : ''}">
              Stock: ${esc(availableStock)}
            </span>
          </div>
        </div>
      `;
    }).join('');

    productResults.querySelectorAll('.pkf-product-option').forEach(option => {
      option.addEventListener('click', () => {
        const index = Number(option.dataset.index);
        const product = filteredProducts[index];
        const availableStock = getAvailableStock(product);

        if (availableStock <= 0) return;

        const fastFlowMatch = matchFastFlowByProduct(product);
        setSelectedProduct(product, {
          fastflow: !!fastFlowMatch,
          fastflowCard: fastFlowMatch || null
        });
      });
    });
  };

  const updateCount = () => {
    const count = itemsContainer.querySelectorAll('.pkf-item-row').length;
    badge.textContent = `${count} ítem${count !== 1 ? 's' : ''}`;
    emptyState.style.display = count ? 'none' : 'flex';

    if (statusText) {
      if (count > 0) {
        statusText.textContent = `Lista lista para guardar (${count} ítems)`;
        statusText.style.color = '#059669';
      } else {
        statusText.textContent = `Proceso sin guardar`;
        statusText.style.color = 'var(--text-muted)';
      }
    }
  };

  const createItemRow = (data) => {
    const idx = itemIndex++;
    const row = document.createElement('div');
    row.className = 'pkf-item-row';

    const lineId = data.line_id || makeLineId();
    const trashSvg = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>`;

    row.innerHTML = `
      <div class="pkf-item-info">
        <span class="pkf-item-name" title="${esc(data.name)}">${esc(data.name || 'Producto')}</span>
        <span class="pkf-item-sku">${esc(data.sku || 'N/A')}</span>
        <div class="pkf-item-meta">
          <span class="pkf-item-tag pkf-item-tag-phase">Fase ${esc(data.phase)}</span>
          <span class="pkf-item-tag">${esc(data.loc || 'Sin ubicación')}</span>
          ${data.is_fastflow ? `<span class="pkf-item-tag pkf-item-tag-fastflow">FAST FLOW</span>` : ''}
          ${data.batch_code ? `<span class="pkf-item-tag">Batch ${esc(data.batch_code)}</span>` : ''}
        </div>

        <input type="hidden" name="items[${idx}][line_id]" value="${esc(lineId)}">
        <input type="hidden" name="items[${idx}][product_id]" value="${esc(data.id || '')}">
        <input type="hidden" name="items[${idx}][product_name]" value="${esc(data.name)}">
        <input type="hidden" name="items[${idx}][product_sku]" value="${esc(data.sku)}">
        <input type="hidden" name="items[${idx}][is_fastflow]" value="${data.is_fastflow ? 1 : 0}">
        <input type="hidden" name="items[${idx}][phase]" value="${Number(data.phase) || 1}">
        <input type="hidden" name="items[${idx}][batch_code]" value="${esc(data.batch_code || '')}">
        <input type="hidden" name="items[${idx}][available_stock]" value="${Number(data.available_stock || 0)}">
        <input type="hidden" name="items[${idx}][quantity_picked]" value="0">
      </div>

      <input class="pkf-invisible-input" type="number" min="1" name="items[${idx}][quantity_required]" value="${Number(data.qty) || 1}" title="Cantidad">
      <input class="pkf-invisible-input" type="text" name="items[${idx}][location_code]" value="${esc(data.loc)}" placeholder="Loc." title="Ubicación">
      <input class="pkf-invisible-input pkf-delivery-phase-input" type="number" min="1" max="${getTotalPhases()}" name="items[${idx}][delivery_phase]" value="${Number(data.phase) || 1}" title="Fase">
      <input class="pkf-invisible-input" type="text" name="items[${idx}][brand]" value="${esc(data.brand)}" placeholder="Marca">
      <input class="pkf-invisible-input" type="text" name="items[${idx}][model]" value="${esc(data.model)}" placeholder="Modelo">
      <input class="pkf-invisible-input" type="text" name="items[${idx}][description]" value="${esc(data.desc)}" placeholder="Descripción">
      <button type="button" class="pkf-btn-delete" title="Quitar">${trashSvg}</button>
    `;

    row.querySelector('.pkf-btn-delete').addEventListener('click', () => {
      row.remove();
      updateCount();
    });

    const deliveryPhaseInput = row.querySelector(`input[name="items[${idx}][delivery_phase]"]`);
    const hiddenPhaseInput = row.querySelector(`input[name="items[${idx}][phase]"]`);
    const phaseTag = row.querySelector('.pkf-item-tag-phase');

    deliveryPhaseInput.addEventListener('input', () => {
      let val = Number(deliveryPhaseInput.value) || 1;
      const total = getTotalPhases();

      if (val < 1) val = 1;
      if (val > total) val = total;

      deliveryPhaseInput.value = val;
      hiddenPhaseInput.value = val;
      phaseTag.textContent = `Fase ${val}`;
    });

    itemsContainer.appendChild(row);
    updateCount();
  };

  const addProductRowsByAllocation = (product, allocations) => {
    allocations
      .filter(a => Number(a.qty) > 0)
      .forEach(a => {
        createItemRow({
          id: product.id,
          line_id: makeLineId(),
          name: product.name,
          sku: product.sku,
          loc: product.loc,
          qty: Number(a.qty),
          phase: Number(a.phase),
          brand: product.brand || '',
          model: product.model || '',
          desc: product.desc || '',
          batch_code: product.batch_code || '',
          available_stock: Number(product.available_stock || 0),
          is_fastflow: !!product.is_fastflow
        });
      });
  };

  const openModal = () => {
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  };

  const closeModal = () => {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    pendingProduct = null;
    hideModalError();
  };

  const showModalError = message => {
    modalError.style.display = 'block';
    modalError.textContent = message;
  };

  const hideModalError = () => {
    modalError.style.display = 'none';
    modalError.textContent = '';
  };

  const setAllocationMode = mode => {
    modeRadios.forEach(r => r.checked = r.value === mode);
    modeCards.forEach(card => card.classList.toggle('is-active', card.dataset.modeCard === mode));
    singlePhaseSection.style.display = mode === 'single' ? '' : 'none';
    splitPhaseSection.style.display = mode === 'split' ? '' : 'none';
    hideModalError();
    updateSplitCounter();
  };

  const renderPhaseOptions = (total, selectedPhase) => {
    singlePhaseSelect.innerHTML = '';
    for (let i = 1; i <= total; i++) {
      const option = document.createElement('option');
      option.value = i;
      option.textContent = `Fase ${i}`;
      if (Number(selectedPhase) === i) option.selected = true;
      singlePhaseSelect.appendChild(option);
    }
  };

  const renderSplitInputs = (total, qty, initialPhase) => {
    phaseSplitInputs.innerHTML = '';

    for (let i = 1; i <= total; i++) {
      const defaultQty = i === Number(initialPhase) ? Number(qty) : 0;

      const card = document.createElement('div');
      card.className = 'pkf-split-card';
      card.innerHTML = `
        <div class="pkf-split-phase">Fase ${i}</div>
        <div class="pkf-field">
          <input type="number" min="0" step="1" value="${defaultQty}" data-split-phase="${i}" class="pkf-split-qty-input">
        </div>
      `;
      phaseSplitInputs.appendChild(card);
    }

    phaseSplitInputs.querySelectorAll('.pkf-split-qty-input').forEach(input => {
      input.addEventListener('input', updateSplitCounter);
    });

    updateSplitCounter();
  };

  const updateSplitCounter = () => {
    if (!pendingProduct) {
      splitCounterText.textContent = '0 / 0 asignado';
      splitCounterText.classList.remove('ok', 'error');
      return;
    }

    const total = Number(pendingProduct.qty) || 0;
    const assigned = Array.from(phaseSplitInputs.querySelectorAll('.pkf-split-qty-input'))
      .reduce((sum, input) => sum + (Number(input.value) || 0), 0);

    splitCounterText.textContent = `${assigned} / ${total} asignado`;
    splitCounterText.classList.remove('ok', 'error');

    if (assigned === total) splitCounterText.classList.add('ok');
    else splitCounterText.classList.add('error');
  };

  const prepareAllocationModal = (product) => {
    const totalPhases = getTotalPhases();
    const initialPhase = getInitialPhase();

    pendingProduct = product;

    modalSummary.innerHTML = `
      <strong>${esc(product.name)}</strong><br>
      SKU: ${esc(product.sku || 'N/A')} · Cantidad total: <strong>${esc(product.qty)}</strong> · Ubicación: <strong>${esc(product.loc || 'Sin ubicación')}</strong> · Stock disponible: <strong>${esc(product.available_stock ?? 0)}</strong>
      ${product.batch_code ? ` · Batch: <strong>${esc(product.batch_code)}</strong>` : ''}
      ${product.is_fastflow ? ` · <strong>FAST FLOW</strong>` : ''}
    `;

    renderPhaseOptions(totalPhases, initialPhase);
    renderSplitInputs(totalPhases, product.qty, initialPhase);
    setAllocationMode('single');
    openModal();
  };

  const getSingleAllocation = () => {
    return [{
      phase: Number(singlePhaseSelect.value) || 1,
      qty: Number(pendingProduct.qty) || 1
    }];
  };

  const getSplitAllocations = () => {
    return Array.from(phaseSplitInputs.querySelectorAll('.pkf-split-qty-input'))
      .map(input => ({
        phase: Number(input.dataset.splitPhase),
        qty: Number(input.value) || 0
      }));
  };

  const validateSplitAllocations = allocations => {
    const total = Number(pendingProduct?.qty) || 0;
    const sum = allocations.reduce((acc, item) => acc + (Number(item.qty) || 0), 0);

    if (!allocations.some(item => Number(item.qty) > 0)) {
      return 'Debes asignar cantidad a por lo menos una fase.';
    }

    if (sum !== total) {
      return `La suma por fases debe ser exactamente ${total}. Actualmente llevas ${sum}.`;
    }

    if (allocations.some(item => Number(item.qty) < 0)) {
      return 'No se permiten cantidades negativas.';
    }

    return null;
  };

  const buildPendingProduct = () => {
    if (!selectedProduct) return null;

    const availableStock = selectedFastFlowCard
      ? Math.max(0, Number(selectedFastFlowCard.available_units) || 0)
      : getAvailableStock(selectedProduct);

    if (availableStock <= 0) {
      alert('Este producto no tiene stock disponible.');
      return null;
    }

    let qty = Math.max(1, Number(qtyInput.value) || 1);

    if (qty > availableStock) {
      qty = availableStock;
      qtyInput.value = availableStock;
    }

    const isFastFlow = selectedProductFastFlowInput.value === '1' || !!selectedProduct.is_fastflow;
    const location = isFastFlow ? 'FAST FLOW' : (locInput.value.trim() || selectedProduct.location_code || '');

    return {
      id: selectedProduct.id,
      name: selectedProduct.name,
      sku: selectedProduct.sku,
      loc: location,
      qty,
      brand: selectedProduct.brand || '',
      model: selectedProduct.model || '',
      desc: selectedProduct.description || '',
      batch_code: selectedFastFlowCard?.batch_code || '',
      is_fastflow: isFastFlow,
      available_stock: availableStock
    };
  };

  const clearAfterAdd = () => {
    qtyInput.value = 1;
    clearSelectedProduct();
  };

  const handleAdd = () => {
    if (!selectedProduct) {
      openDropdown();
      return;
    }

    const availableStock = selectedFastFlowCard
      ? Math.max(0, Number(selectedFastFlowCard.available_units) || 0)
      : getAvailableStock(selectedProduct);

    if (availableStock <= 0) {
      alert('Este producto no tiene stock disponible.');
      return;
    }

    const product = buildPendingProduct();
    if (!product) return;

    prepareAllocationModal(product);
  };

  const renderDeliveries = total => {
    const normalizedTotal = Math.max(1, Number(total) || 1);
    deliveriesContainer.innerHTML = '';

    for (let i = 1; i <= normalizedTotal; i++) {
      deliveriesContainer.insertAdjacentHTML('beforeend', `
        <div class="pkf-delivery-row">
          <div class="pkf-delivery-badge">Fase ${i}</div>
          <div class="pkf-field">
            <input type="datetime-local" name="deliveries[${i - 1}][scheduled_for]" class="pkf-invisible-input" style="border-color:var(--border-color); background:#fff;">
            <input type="hidden" name="deliveries[${i - 1}][phase]" value="${i}">
            <input type="hidden" name="deliveries[${i - 1}][title]" value="Entrega ${i}">
          </div>
          <div class="pkf-field">
            <input type="text" name="deliveries[${i - 1}][notes]" placeholder="Notas de entrega..." class="pkf-invisible-input" style="border-color:var(--border-color); background:#fff;">
          </div>
        </div>
      `);
    }

    const currentStart = Number(phaseInput.value) || 1;
    if (currentStart > normalizedTotal) phaseInput.value = normalizedTotal;
    if (currentStart < 1) phaseInput.value = 1;

    itemsContainer.querySelectorAll('input[name$="[delivery_phase]"]').forEach(input => {
      let val = Number(input.value) || 1;
      if (val > normalizedTotal) input.value = normalizedTotal;
      if (val < 1) input.value = 1;

      const row = input.closest('.pkf-item-row');
      const phaseTag = row?.querySelector('.pkf-item-tag-phase');
      const hiddenPhase = row?.querySelector('input[name$="[phase]"]');

      if (phaseTag) phaseTag.textContent = `Fase ${input.value}`;
      if (hiddenPhase) hiddenPhase.value = input.value;
      input.setAttribute('max', normalizedTotal);
    });
  };

  document.querySelectorAll('[data-stepper-action]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const targetId = btn.dataset.stepperTarget;
      const action = btn.dataset.stepperAction;
      const input = document.getElementById(targetId);

      if (!input) return;

      if (action === 'decrease') applyStepperChange(input, -1);
      if (action === 'increase') applyStepperChange(input, 1);
    });
  });

  [phaseInput, totalPhasesInput].forEach((input) => {
    if (!input) return;

    input.addEventListener('input', () => {
      clampStepperValue(input);
      if (input === totalPhasesInput) syncPhaseLimits();
    });

    input.addEventListener('blur', () => {
      clampStepperValue(input);
      if (input === totalPhasesInput) syncPhaseLimits();
    });
  });

  syncPhaseLimits();

  productSelectTrigger.addEventListener('click', () => {
    if (productSelect.classList.contains('is-open')) closeDropdown();
    else openDropdown();
  });

  searchInput.addEventListener('input', (e) => {
    highlightedIndex = 0;
    renderProductOptions(e.target.value);
  });

  searchInput.addEventListener('keydown', (e) => {
    if (!productSelect.classList.contains('is-open')) return;
    if (!filteredProducts.length) return;

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      highlightedIndex = Math.min(highlightedIndex + 1, filteredProducts.length - 1);
      renderProductOptions(searchInput.value);
    }

    if (e.key === 'ArrowUp') {
      e.preventDefault();
      highlightedIndex = Math.max(highlightedIndex - 1, 0);
      renderProductOptions(searchInput.value);
    }

    if (e.key === 'Enter') {
      e.preventDefault();
      const index = highlightedIndex >= 0 ? highlightedIndex : 0;
      const product = filteredProducts[index];
      const availableStock = getAvailableStock(product);

      if (availableStock <= 0) return;

      const fastFlowMatch = matchFastFlowByProduct(product);
      setSelectedProduct(product, {
        fastflow: !!fastFlowMatch,
        fastflowCard: fastFlowMatch || null
      });
    }

    if (e.key === 'Escape') {
      closeDropdown();
    }
  });

  qtyInput.addEventListener('input', () => {
    if (!selectedProduct) return;

    const availableStock = selectedFastFlowCard
      ? Math.max(0, Number(selectedFastFlowCard.available_units) || 0)
      : getAvailableStock(selectedProduct);

    let value = Number(qtyInput.value) || 1;

    if (value < 1) value = 1;
    if (value > availableStock) value = availableStock;

    qtyInput.value = value;
  });

  addBtn.addEventListener('click', handleAdd);

  document.addEventListener('click', (e) => {
    if (!productSelect.contains(e.target)) closeDropdown();
  });

  document.querySelectorAll('.pkf-fast-pill').forEach(btn => {
    btn.addEventListener('click', function() {
      const card = {
        batch_code: this.dataset.fastBatch || '',
        sku: this.dataset.fastSku || '',
        product_name: this.dataset.fastName || '',
        warehouse_name: this.dataset.fastLocation || '',
        available_boxes: Number(this.dataset.fastBoxes || 0),
        available_units: Number(this.dataset.fastUnits || 0),
      };

      if ((Number(card.available_units) || 0) <= 0) {
        renderFastFlowInspector(card, null);
        qtyInput.value = 0;
        qtyInput.disabled = true;
        return;
      }

      setFastFlowSelection(card);
    });
  });

  confirmPhaseAllocationBtn.addEventListener('click', () => {
    if (!pendingProduct) return;

    hideModalError();

    const selectedMode = modeRadios.find(r => r.checked)?.value || 'single';

    if (selectedMode === 'single') {
      const allocations = getSingleAllocation();
      addProductRowsByAllocation(pendingProduct, allocations);
      closeModal();
      clearAfterAdd();
      return;
    }

    const allocations = getSplitAllocations();
    const validationError = validateSplitAllocations(allocations);

    if (validationError) {
      showModalError(validationError);
      return;
    }

    addProductRowsByAllocation(pendingProduct, allocations);
    closeModal();
    clearAfterAdd();
  });

  modeCards.forEach(card => {
    card.addEventListener('click', () => setAllocationMode(card.dataset.modeCard));
  });

  modal.querySelectorAll('[data-close-modal]').forEach(el => {
    el.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
  });

  totalPhasesInput.addEventListener('input', () => {
    syncPhaseLimits();
    renderDeliveries(totalPhasesInput.value);

    if (modal.classList.contains('is-open') && pendingProduct) {
      prepareAllocationModal(pendingProduct);
    }
  });

  phaseInput.addEventListener('input', () => {
    clampStepperValue(phaseInput);
    syncPhaseLimits();
  });

  form.addEventListener('submit', e => {
    const rows = itemsContainer.querySelectorAll('.pkf-item-row');

    if (!rows.length) {
      e.preventDefault();
      alert('Debe agregar al menos un producto a la lista de surtido.');
      return;
    }

    const totalPhases = getTotalPhases();
    let invalidPhase = false;

    rows.forEach(row => {
      const deliveryPhaseInput = row.querySelector('input[name$="[delivery_phase]"]');
      if (!deliveryPhaseInput) return;

      const val = Number(deliveryPhaseInput.value) || 1;
      if (val < 1 || val > totalPhases) invalidPhase = true;
    });

    if (invalidPhase) {
      e.preventDefault();
      alert(`Hay productos asignados a fases inválidas. Verifica que todas estén entre 1 y ${totalPhases}.`);
    }
  });

  renderDeliveries(totalPhasesInput.value || 1);
  renderProductOptions('');
  resetPreview();
  clearFastFlowInspector();

  const params = new URLSearchParams(window.location.search);
  const skuP = params.get('sku');
  const nameP = params.get('name');
  const batchP = params.get('batch');

  if (batchP) {
    const fastCardByBatch = fastFlowCards.find(card => normalize(card.batch_code) === normalize(batchP));
    if (fastCardByBatch && Number(fastCardByBatch.available_units || 0) > 0) {
      setFastFlowSelection(fastCardByBatch);
      return;
    }
  }

  if (skuP || nameP) {
    const fastCard = fastFlowCards.find(card => {
      const sameSku = skuP && normalize(card.sku) === normalize(skuP);
      const sameName = nameP && normalize(card.product_name) === normalize(nameP);
      return sameSku || sameName;
    });

    if (fastCard && Number(fastCard.available_units || 0) > 0) {
      setFastFlowSelection(fastCard);
      return;
    }

    const product = catalog.find(p => {
      const sameSku = skuP && normalize(p.sku) === normalize(skuP);
      const sameName = nameP && normalize(p.name) === normalize(nameP);
      return sameSku || sameName;
    });

    if (product) {
      const availableStock = getAvailableStock(product);
      if (availableStock > 0) {
        const fastFlowMatch = matchFastFlowByProduct(product);
        setSelectedProduct(product, {
          fastflow: !!fastFlowMatch,
          fastflowCard: fastFlowMatch || null
        });
      }
    }
  }
});
</script>
@endpush