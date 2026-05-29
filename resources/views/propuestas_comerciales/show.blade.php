@extends('layouts.app')
@section('content_class', 'content--flush')
@section('content')


    <link rel="stylesheet" href="{{ asset('css/cotizacion.css') }}?v={{ time() }}">

@php
  $propuestaComercial->loadMissing([
      'items.matches.product',
      'items.externalMatches',
      'items.productoSeleccionado',
  ]);

  $itemsPayload = $propuestaComercial->items
      ->sortBy('sort')
      ->values()
      ->map(function ($item) use ($propuestaComercial) {
          $selectedMatch = $item->matches->firstWhere('seleccionado', true) ?: $item->matches->sortByDesc('score')->first();
          $score = (float) ($item->match_score ?: optional($selectedMatch)->score);

          if ($item->productoSeleccionado && $score >= 85) {
              $statusKey = 'exact';
          } elseif ($item->productoSeleccionado || $item->matches->count()) {
              $statusKey = 'similar';
          } else {
              $statusKey = 'not_found';
          }

          return [
              'id' => $item->id,
              'sort' => (int) $item->sort,
              'descripcion_original' => $item->descripcion_original,
              'unidad_solicitada' => $item->unidad_solicitada,
              'cantidad_minima' => (float) $item->cantidad_minima,
              'cantidad_maxima' => (float) $item->cantidad_maxima,
              'cantidad_cotizada' => (float) ($item->cantidad_cotizada ?: 1),
              'costo_unitario' => (float) $item->costo_unitario,
              'precio_unitario' => (float) $item->precio_unitario,
              'subtotal' => (float) $item->subtotal,
              'match_score' => $score,
              'status_key' => $statusKey,
              'ui_status' => data_get($item->meta, 'ui_status', 'pending'),
              'item_margin_pct' => (float) data_get($item->meta, 'item_margin_pct', $propuestaComercial->porcentaje_utilidad ?: 25),
              'manual_external_supplier' => data_get($item->meta, 'external_supplier'),
              'manual_external_link' => data_get($item->meta, 'external_link'),
              'manual_catalog_product_name' => data_get($item->meta, 'catalog_product_name_manual'),

              // 🔹 PASO 7: ficha técnica vinculada
              'tech_sheet_id' => data_get($item->meta, 'tech_sheet_id'),
              'tech_sheet_name' => data_get($item->meta, 'tech_sheet_name'),

              'producto_seleccionado' => $item->productoSeleccionado ? [
                  'id' => $item->productoSeleccionado->id,
                  'name' => $item->productoSeleccionado->name,
                  'sku' => $item->productoSeleccionado->sku,
                  'brand' => $item->productoSeleccionado->brand,
                  'stock' => $item->productoSeleccionado->stock ?? 0,
              ] : null,
              'matches' => $item->matches->sortBy('rank')->values()->map(function ($match) {
                  $p = $match->product;

                  return [
                      'id' => $match->id,
                      'rank' => $match->rank,
                      'score' => (float) $match->score,
                      'seleccionado' => (bool) $match->seleccionado,
                      'unidad_coincide' => (bool) $match->unidad_coincide,
                      'motivo' => $match->motivo,
                      'product' => $p ? [
                          'id' => $p->id,
                          'name' => $p->name,
                          'sku' => $p->sku,
                          'brand' => $p->brand,
                          'stock' => $p->stock ?? 0,
                          'cost' => (float) ($p->cost ?? $p->costo ?? $p->purchase_price ?? 0),
                          'price' => (float) ($p->price ?? $p->precio ?? $p->sale_price ?? 0),
                      ] : null,
                  ];
              })->all(),
              'external_matches' => $item->externalMatches->sortBy('rank')->values()->map(function ($external) {
                  return [
                      'id' => $external->id,
                      'rank' => $external->rank,
                      'source' => $external->source,
                      'title' => $external->title,
                      'seller' => $external->seller,
                      'price' => (float) $external->price,
                      'currency' => $external->currency,
                      'url' => $external->url,
                      'score' => (float) $external->score,
                  ];
              })->all(),
          ];
      });

  $subtotalSale = (float) $propuestaComercial->items->sum('subtotal');
  $subtotalCost = (float) $propuestaComercial->items->sum(fn($i) => ((float) $i->costo_unitario) * ((float) ($i->cantidad_cotizada ?: 0)));
  $profit = $subtotalSale - $subtotalCost;
  $margin = $subtotalCost > 0 ? round(($profit / $subtotalCost) * 100) : 0;

  $summaryPayload = [
      'exact' => $itemsPayload->where('status_key', 'exact')->count(),
      'similar' => $itemsPayload->where('status_key', 'similar')->count(),
      'not_found' => $itemsPayload->where('status_key', 'not_found')->count(),
      'subtotal_sale' => $subtotalSale,
      'subtotal_cost' => $subtotalCost,
      'profit' => $profit,
      'margin' => $margin,
      'total_items' => $itemsPayload->count(),
  ];


  $exportFolio = $propuestaComercial->folio ?: ('TEOA' . str_pad((string) $propuestaComercial->id, 8, '0', STR_PAD_LEFT));
  $exportTitle = $propuestaComercial->titulo ?: ('COT-' . strtoupper(substr(md5($propuestaComercial->id . $propuestaComercial->created_at), 0, 8)));

  $decodeExportValue = function ($value) {
      if ($value instanceof \Illuminate\Support\Collection) {
          return $value->toArray();
      }

      if (is_array($value)) {
          return $value;
      }

      if (is_object($value)) {
          return json_decode(json_encode($value), true) ?: [];
      }

      if (is_string($value) && trim($value) !== '') {
          $decoded = json_decode($value, true);
          return json_last_error() === JSON_ERROR_NONE ? $decoded : [];
      }

      return [];
  };

  $rawExportPayloads = [];
  $fieldsForExport = ['structured_json', 'items_json', 'result_json', 'raw_json', 'extracted_json', 'document_json', 'table_json', 'meta'];

  foreach ($fieldsForExport as $field) {
      $decoded = $decodeExportValue(data_get($propuestaComercial, $field));
      if (!empty($decoded)) {
          $rawExportPayloads['propuesta_' . $field] = $decoded;
      }
  }

  foreach ($propuestaComercial->getRelations() as $relationName => $relationValue) {
      if (!$relationValue) {
          continue;
      }

      if ($relationValue instanceof \Illuminate\Support\Collection) {
          foreach ($relationValue as $index => $relatedModel) {
              foreach ($fieldsForExport as $field) {
                  $decoded = $decodeExportValue(data_get($relatedModel, $field));
                  if (!empty($decoded)) {
                      $rawExportPayloads[$relationName . '_' . $index . '_' . $field] = $decoded;
                  }
              }
          }
      } else {
          foreach ($fieldsForExport as $field) {
              $decoded = $decodeExportValue(data_get($relationValue, $field));
              if (!empty($decoded)) {
                  $rawExportPayloads[$relationName . '_' . $field] = $decoded;
              }
          }
      }
  }
@endphp

<div class="jureto-quote-page">
  <div class="quote-wrap">
    <a href="{{ route('propuestas-comerciales.index') }}" class="back-link">
      <span>←</span>
      <span>Volver</span>
    </a>

    <div class="topbar">
      <div class="topbar-main">
        <div class="quote-code">
          {{ $propuestaComercial->folio ?: ('TEOA' . str_pad((string) $propuestaComercial->id, 8, '0', STR_PAD_LEFT)) }}
        </div>

        <h1 class="quote-title">
          {{ $propuestaComercial->titulo ?: ('COT-' . strtoupper(substr(md5($propuestaComercial->id . $propuestaComercial->created_at), 0, 8))) }}
        </h1>

        <p class="quote-subtitle">
          <span id="itemsCountText">{{ $summaryPayload['total_items'] }}</span> partidas analizadas por IA · Exportación desde PDF
        </p>
      </div>

      <div class="actions">
        <button class="btn btn-ghost" type="button" id="btnOpenAddItem">
          <span class="btn-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 5v14"></path>
              <path d="M5 12h14"></path>
            </svg>
          </span>
          <span>Agregar</span>
        </button>

        <button class="btn btn-outline" type="button" id="btnSuggestAll">
          <span class="btn-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="7"></circle>
              <path d="M21 21l-4.35-4.35"></path>
              <path d="M11 8v6"></path>
              <path d="M8 11h6"></path>
            </svg>
          </span>
          <span>Buscar coincidencias</span>
        </button>

        <button class="btn btn-export-excel" type="button" id="btnExportExcel">
          <span class="btn-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <path d="M14 2v6h6"></path>
              <path d="M8 13h8"></path>
              <path d="M8 17h8"></path>
            </svg>
          </span>
          <span>Excel PDF</span>
        </button>

        <button class="btn btn-export-word" type="button" id="btnExportWord">
          <span class="btn-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <path d="M14 2v6h6"></path>
              <path d="M8 13h8"></path>
              <path d="M8 17h6"></path>
            </svg>
          </span>
          <span>Word PDF</span>
        </button>

        <a href="{{ route('propuestas-comerciales.cliente.show', $propuestaComercial) }}" class="btn btn-primary">
          <span class="btn-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 6L9 17l-5-5"></path>
            </svg>
          </span>
          <span>Aprobar</span>
        </a>
      </div>
    </div>

    <div id="noticeBox" class="notice">
      <span class="notice-dot"></span>
      <span>
        <strong id="noticeCount">0 partidas</strong> no encontradas en catálogo — usa “Buscar en internet” para encontrar alternativas.
      </span>
    </div>

    <div id="processBox" class="process-box">
      <div class="process-head">
        <div>
          <div class="process-title" id="processTitle">Procesando coincidencias...</div>
          <div class="process-text" id="processText">Preparando partidas.</div>
        </div>

        <span class="badge badge-info" id="processCount">0/0</span>
      </div>

      <div class="process-bar">
        <div class="process-fill" id="processFill"></div>
      </div>

      <div id="processErrors" class="process-errors"></div>
    </div>

    <div class="summary-grid" id="summaryFilters">
      <button class="summary-cell filter-summary active" type="button" data-filter="all">
        <div class="summary-value text-blue" id="sumAll">0</div>
        <div class="summary-label">Todos</div>
      </button>

      <button class="summary-cell filter-summary" type="button" data-filter="exact">
        <div class="summary-value text-success" id="sumExact">0</div>
        <div class="summary-label">Exactos</div>
      </button>

      <button class="summary-cell filter-summary" type="button" data-filter="similar">
        <div class="summary-value text-blue" id="sumSimilar">0</div>
        <div class="summary-label">Similares</div>
      </button>

      <button class="summary-cell filter-summary" type="button" data-filter="not_found">
        <div class="summary-value text-danger" id="sumNotFound">0</div>
        <div class="summary-label">No encontrados</div>
      </button>

      <button class="summary-cell filter-summary" type="button" data-filter="priced">
        <div class="summary-value" id="sumSale">$0</div>
        <div class="summary-label">Subtotal venta</div>
      </button>

      <button class="summary-cell filter-summary" type="button" data-filter="profit">
        <div class="summary-value text-success" id="sumProfit">$0</div>
        <div class="summary-label">Utilidad</div>
      </button>

      <button class="summary-cell filter-summary" type="button" data-filter="margin">
        <div class="summary-value" id="sumMargin">0%</div>
        <div class="summary-label">Margen</div>
      </button>
    </div>

    <div class="global-margin">
      <div>
        <label>Margen global %</label>
        <input class="input" id="globalMarginInput" type="number" step="0.01" value="{{ $propuestaComercial->porcentaje_utilidad ?: 25 }}" style="width:150px;">
      </div>

      <button class="btn btn-ghost" type="button" id="btnSaveGlobalMargin">Guardar margen global</button>
      <button class="btn btn-outline" type="button" id="btnApplyGlobalMargin">Aplicar a partidas</button>
    </div>

    <div class="items-list" id="itemsList"></div>
  </div>

  <div class="modal-backdrop" id="manualModal">
    <div class="modal">
      <div class="modal-head">
        <div>
          <h2 class="modal-title">Búsqueda manual</h2>
          <p class="modal-subtitle" id="manualSubtitle">Busca por nombre, SKU, marca, color, unidad o descripción.</p>
        </div>

        <button class="btn btn-ghost btn-small" type="button" onclick="closeManualModal()">×</button>
      </div>

      <div class="modal-body">
        <div style="position:relative;">
          <input class="input" id="manualQueryInput" placeholder="Buscar producto..." autocomplete="off" style="padding-left:38px; padding-right:38px;">
          <span style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#888;">⌕</span>
          <button type="button" onclick="clearManualSearch()" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); border:0; background:transparent; color:#888; cursor:pointer; font-size:18px;">×</button>
        </div>

        <div class="modal-tabs">
          <button class="tab-btn active" type="button" id="manualTabCatalog">Catálogo interno</button>
          <button class="tab-btn" type="button" id="manualTabInternet">Internet</button>
        </div>

        <div id="manualSearchStatus" class="result-meta" style="margin-bottom:12px;">
          Escribe para buscar automáticamente.
        </div>

        <div id="manualResults"></div>
      </div>
    </div>
  </div>

  <div class="modal-backdrop" id="addItemModal">
    <div class="modal">
      <div class="modal-head">
        <div>
          <h2 class="modal-title">Agregar nueva partida</h2>
          <p class="modal-subtitle">Crea un nuevo producto solicitado y calcula costo, precio y subtotal.</p>
        </div>

        <button class="btn btn-ghost btn-small" type="button" onclick="closeAddItemModal()">×</button>
      </div>

      <div class="modal-body">
        <form id="addItemForm" onsubmit="storeNewItem(event)" style="display:grid; gap:14px;">
          <div class="field">
            <label>Producto solicitado</label>
            <input class="input" name="descripcion_original" placeholder="Ej. 100 paquetes de hojas blancas tamaño carta" required>
          </div>

          <div style="display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap:12px;">
            <div class="field">
              <label>Cantidad</label>
              <input class="input" type="number" step="0.01" name="cantidad_cotizada" value="1" required>
            </div>

            <div class="field">
              <label>Unidad</label>
              <input class="input" name="unidad_solicitada" value="pz">
            </div>

            <div class="field">
              <label>Costo unit.</label>
              <input class="input" type="number" step="0.01" name="costo_unitario" value="0">
            </div>

            <div class="field">
              <label>Margen %</label>
              <input class="input" type="number" step="0.01" name="porcentaje_utilidad" value="{{ $propuestaComercial->porcentaje_utilidad ?: 25 }}">
            </div>
          </div>

          <div class="action-row">
            <button class="btn btn-primary" type="submit">＋ Agregar partida</button>
            <button class="btn btn-ghost" type="button" onclick="closeAddItemModal()">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ===================== MODAL MUESTRAS (almacén) ===================== -->
  <div class="modal-backdrop" id="samplesModal">
    <div class="modal">
      <div class="modal-head">
        <div>
          <h2 class="modal-title">Muestras · Análisis de almacén</h2>
          <p class="modal-subtitle" id="samplesSubtitle">Producto</p>
        </div>
        <button class="btn btn-ghost btn-small" type="button" onclick="closeSamplesModal()">×</button>
      </div>
      <div class="modal-body">
        <div id="samplesStatus" class="result-meta" style="margin-bottom:12px;">Buscando en catálogo...</div>
        <div id="samplesResults"></div>
      </div>
    </div>
  </div>

  <!-- ===================== MODAL FICHAS TÉCNICAS ===================== -->
  <div class="modal-backdrop" id="techSheetsModal">
    <div class="modal">
      <div class="modal-head">
        <div>
          <h2 class="modal-title">Fichas técnicas</h2>
          <p class="modal-subtitle" id="techSubtitle">Producto</p>
        </div>
        <button class="btn btn-ghost btn-small" type="button" onclick="closeTechSheetsModal()">×</button>
      </div>
      <div class="modal-body">
        <div class="modal-tabs">
          <button class="tab-btn active" type="button" id="techTabList" onclick="techShowList()">Vincular existente</button>
          <button class="tab-btn" type="button" id="techTabForm" onclick="techShowCreate()">Crear nueva</button>
        </div>

        <div id="techListPane">
          <input class="input" id="techQueryInput" placeholder="Buscar ficha por nombre, marca, modelo..." style="margin-bottom:12px;">
          <div id="techStatus" class="result-meta" style="margin-bottom:12px;"></div>
          <div id="techResults"></div>
        </div>

        <div id="techFormPane" style="display:none;">
          <form id="techForm" onsubmit="submitTechSheet(event)" style="display:grid; gap:12px;">
            <input type="hidden" name="tech_sheet_id" id="techFormId" value="">
            <div class="field"><label>Nombre del producto *</label><input class="input" name="product_name" required></div>
            <div style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px;">
              <div class="field"><label>Marca</label><input class="input" name="brand"></div>
              <div class="field"><label>Modelo</label><input class="input" name="model"></div>
              <div class="field"><label>Referencia</label><input class="input" name="reference"></div>
              <div class="field"><label>Partida</label><input class="input" name="partida_number"></div>
            </div>
            <div class="field"><label>Descripción</label><textarea class="input" name="user_description" rows="3" style="height:auto; padding:10px 12px;"></textarea></div>
            <div class="field"><label>Imagen (opcional)</label><input class="input" type="file" name="image" accept="image/*" style="padding:8px;"></div>
            <div class="action-row">
              <button class="btn btn-primary btn-small" type="submit">Guardar ficha</button>
              <button class="btn btn-ghost btn-small" type="button" onclick="techShowList()">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const csrfToken = @json(csrf_token());

  const routes = {
    suggestAll: @json(route('propuestas-comerciales.ajax.suggest-all', $propuestaComercial)),
    suggestItem: @json(url('/propuesta-comercial-items/__ID__/ajax/suggest')),
    updateItem: @json(url('/propuesta-comercial-items/__ID__/ajax/update')),
    updateStatus: @json(url('/propuesta-comercial-items/__ID__/ajax/status')),
    manualSearch: @json(route('propuestas-comerciales.ajax.manual-search', $propuestaComercial)),
    reorder: @json(route('propuestas-comerciales.ajax.reorder-items', $propuestaComercial)),
    globalMargin: @json(route('propuestas-comerciales.ajax.global-margin', $propuestaComercial)),
    storeItem: @json(route('propuestas-comerciales.ajax.items.store', $propuestaComercial)),
    selectMatch: @json(url('/propuesta-comercial-items/__ID__/ajax/select-match/__MATCH__')),

    // 🔹 Muestras + Fichas
    itemSamples: @json(url('/propuesta-comercial-items/__ID__/ajax/samples')),
    techSheetsList: @json(url('/propuesta-comercial-items/__ID__/ajax/tech-sheets')),
    linkTechSheet: @json(url('/propuesta-comercial-items/__ID__/ajax/tech-sheets/link')),
    createTechSheet: @json(url('/propuesta-comercial-items/__ID__/ajax/tech-sheets/create')),
    updateTechSheet: @json(url('/propuesta-comercial-fichas/__ID__/update')),
    techSheetPdf: @json(url('/tech-sheets/__ID__/pdf')),
  };

  let items = @json($itemsPayload);
  let summary = @json($summaryPayload);
  let rawExportPayloads = @json($rawExportPayloads);
  const exportFolio = @json($exportFolio);
  const exportTitle = @json($exportTitle);
  let currentFilter = 'all';
  let manualItemId = null;
  let manualTab = 'catalog';
  let manualSearchTimer = null;
  let manualLastQuery = '';
  let manualCatalogResults = [];
  let manualInternetResults = [];
  let isSuggestingAll = false;

  // 🔹 Muestras + Fichas
  let samplesItemId = null;
  let techItemId = null;
  let techSheetsCache = [];
  let currentLinkedSheetId = null;

  function money(n) {
    n = Number(n || 0);
    return n.toLocaleString('es-MX', {
      style: 'currency',
      currency: 'MXN',
      maximumFractionDigits: 0
    });
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  async function ajax(url, options = {}) {
    const response = await fetch(url, {
      ...options,
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        ...(options.headers || {})
      }
    });

    const rawText = await response.text();
    let data = null;

    try {
      data = rawText ? JSON.parse(rawText) : null;
    } catch (error) {
      data = null;
    }

    if (!response.ok || !data || data.ok === false) {
      let message = data?.message || 'Error procesando la solicitud.';

      if (!data && rawText) {
        message += ' Respuesta del servidor: ' + String(rawText).slice(0, 300);
      }

      throw new Error(message);
    }

    return data;
  }

  function urlFor(template, id) {
    return template.replace('__ID__', id);
  }

  // 🔹 Conserva la ficha vinculada cuando el server reemplaza el item (no la incluye en su payload)
  function mergeTechSheetMeta(newItems) {
    if (!Array.isArray(newItems)) return newItems;

    const map = {};
    items.forEach(i => { map[i.id] = i; });

    newItems.forEach(ni => {
      const prev = map[ni.id];
      if (prev) {
        if (ni.tech_sheet_id === undefined) ni.tech_sheet_id = prev.tech_sheet_id ?? null;
        if (ni.tech_sheet_name === undefined) ni.tech_sheet_name = prev.tech_sheet_name ?? null;
      }
    });

    return newItems;
  }

  function showProcessBox(type, title, text, done = 0, total = 0, errors = []) {
    const box = document.getElementById('processBox');
    const titleEl = document.getElementById('processTitle');
    const textEl = document.getElementById('processText');
    const countEl = document.getElementById('processCount');
    const fillEl = document.getElementById('processFill');
    const errorsEl = document.getElementById('processErrors');

    if (!box || !titleEl || !textEl || !countEl || !fillEl || !errorsEl) {
      return;
    }

    box.className = 'process-box show' + (type ? ' ' + type : '');
    titleEl.textContent = title;
    textEl.textContent = text;
    countEl.textContent = `${done}/${total}`;

    const pct = total > 0 ? Math.round((done / total) * 100) : 0;
    fillEl.style.width = pct + '%';

    if (errors.length) {
      errorsEl.classList.add('show');
      errorsEl.innerHTML = errors
        .slice(0, 30)
        .map(error => `<div>• ${escapeHtml(error)}</div>`)
        .join('');

      if (errors.length > 30) {
        errorsEl.innerHTML += `<div>• Y ${errors.length - 30} errores más...</div>`;
      }
    } else {
      errorsEl.classList.remove('show');
      errorsEl.innerHTML = '';
    }
  }

  function showInlineError(message) {
    showProcessBox(
      'error',
      'No se pudo completar la acción',
      message || 'Ocurrió un error procesando la solicitud.',
      1,
      1,
      []
    );

    const box = document.getElementById('processBox');
    if (box) {
      box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  function hideProcessBox() {
    const box = document.getElementById('processBox');
    if (box) {
      box.classList.remove('show');
    }
  }

  function statusLabel(item) {
    if (item.ui_status === 'accepted_item') return { text: 'Aceptado', cls: 'badge-success' };
    if (item.ui_status === 'manual_review') return { text: 'Revisión', cls: 'badge-warning' };
    if (item.ui_status === 'rejected_item') return { text: 'Rechazado', cls: 'badge-danger' };

    if (item.status_key === 'exact') return { text: 'Coincidencia exacta', cls: 'badge-success' };
    if (item.status_key === 'similar') return { text: 'Similar', cls: 'badge-info' };

    return { text: 'No encontrado', cls: 'badge-danger' };
  }

  function statusCardClass(item) {
    if (item.status_key === 'exact') return 'status-exact';
    if (item.status_key === 'similar') return 'status-similar';
    return 'status-not_found';
  }

  function renderSummary() {
    const total = summary.total_items || items.length;

    document.getElementById('sumAll').textContent = total;
    document.getElementById('sumExact').textContent = summary.exact || 0;
    document.getElementById('sumSimilar').textContent = summary.similar || 0;
    document.getElementById('sumNotFound').textContent = summary.not_found || 0;
    document.getElementById('sumSale').textContent = money(summary.subtotal_sale);
    document.getElementById('sumProfit').textContent = money(summary.profit);
    document.getElementById('sumMargin').textContent = `${summary.margin || 0}%`;
    document.getElementById('itemsCountText').textContent = total;

    document.querySelectorAll('.filter-summary').forEach((btn) => {
      btn.classList.toggle('active', btn.dataset.filter === currentFilter);
    });

    const notice = document.getElementById('noticeBox');
    const count = Number(summary.not_found || 0);

    if (count > 0) {
      document.getElementById('noticeCount').textContent = `${count} partidas`;
      notice.classList.add('show');
    } else {
      notice.classList.remove('show');
    }
  }

  function renderItems() {
    renderSummary();

    const list = document.getElementById('itemsList');

    const filtered = items.filter(item => {
      if (currentFilter === 'all') return true;
      if (currentFilter === 'exact') return item.status_key === 'exact';
      if (currentFilter === 'similar') return item.status_key === 'similar';
      if (currentFilter === 'not_found') return item.status_key === 'not_found';
      if (currentFilter === 'priced') return Number(item.subtotal || 0) > 0;
      if (currentFilter === 'profit') return Number(item.precio_unitario || 0) > Number(item.costo_unitario || 0);
      if (currentFilter === 'margin') return Number(item.item_margin_pct || 0) > 0;
      return true;
    });

    list.innerHTML = filtered.map((item, idx) => renderItemCard(item, idx)).join('');
    bindDragEvents();
  }

  function renderItemCard(item, idx) {
    const badge = statusLabel(item);
    const qty = Number(item.cantidad_cotizada || item.cantidad_maxima || item.cantidad_minima || 1);
    const cost = Number(item.costo_unitario || 0);
    const price = Number(item.precio_unitario || 0);
    const subtotal = Number(item.subtotal || price * qty);

    return `
      <div class="item-card ${statusCardClass(item)}" data-id="${item.id}" draggable="${currentFilter === 'all' ? 'true' : 'false'}">
        <div class="item-main" onclick="toggleItem(${item.id})">
          <button class="drag-handle" type="button" title="Mover posición" onclick="event.stopPropagation()">⠿</button>
          <div class="item-index">${idx + 1}</div>
          <div>
            <h3 class="item-name">${escapeHtml(item.descripcion_original || 'Producto sin descripción')}</h3>
            <div class="item-meta">
              ${qty} ${escapeHtml(item.unidad_solicitada || 'pz')}
              ${item.producto_seleccionado?.brand ? ' · ' + escapeHtml(item.producto_seleccionado.brand) : ''}
              ${item.tech_sheet_id ? ' · <span style="color:var(--success); font-weight:700;">📄 Ficha vinculada</span>' : ''}
            </div>
          </div>
          <span class="badge ${badge.cls}">${badge.text}</span>
          <div class="money-row">
            <span>Costo <strong>${money(cost)}</strong></span>
            <span>Precio <strong>${money(price)}</strong></span>
            <span>Subtotal <strong>${money(subtotal)}</strong></span>
          </div>
          <div class="chevron">⌄</div>
        </div>

        <div class="item-details">
          ${renderCatalogSection(item)}
          ${renderManualExternal(item)}
          ${renderExternalSection(item)}
          ${renderTechSheetLinked(item)}
          ${renderActions(item)}
          ${renderEditForm(item)}
        </div>
      </div>
    `;
  }

  function renderCatalogSection(item) {
    if (!item.matches?.length && !item.producto_seleccionado) {
      return `
        <div class="section">
          <div class="section-title">Coincidencia en catálogo</div>
          <div class="result-title">N/A</div>
          <div class="result-meta">SKU: N/A · N/A · Stock: 0</div>
        </div>
      `;
    }

    if (item.matches?.length) {
      return `
        <div class="section">
          <div class="section-title">Coincidencias en catálogo</div>
          ${item.matches.map((match, i) => {
            const p = match.product || {};
            return `
              <div class="result-card">
                <div class="result-title">${escapeHtml(p.name || 'Producto sin nombre')}</div>
                <div class="result-meta">
                  SKU: ${escapeHtml(p.sku || '—')} · ${escapeHtml(p.brand || '—')} · Stock: ${p.stock ?? '—'} · ${Number(match.score || 0).toFixed(0)}%
                </div>
                <div class="action-row" style="margin-top:12px;">
                  <button class="btn btn-outline btn-small" type="button" onclick="selectMatch(${item.id}, ${match.id})">
                    ✓ Usar esta
                  </button>
                  ${i === 0 ? '<span class="badge badge-info">Principal</span>' : ''}
                </div>
              </div>
            `;
          }).join('')}
        </div>
      `;
    }

    return `
      <div class="section">
        <div class="section-title">Coincidencia en catálogo</div>
        <div class="result-title">${escapeHtml(item.producto_seleccionado?.name || 'N/A')}</div>
        <div class="result-meta">
          SKU: ${escapeHtml(item.producto_seleccionado?.sku || 'N/A')} · ${escapeHtml(item.producto_seleccionado?.brand || 'N/A')} · Stock: ${item.producto_seleccionado?.stock || 0}
        </div>
      </div>
    `;
  }

  function renderManualExternal(item) {
    if (!item.manual_external_supplier && !item.manual_external_link && !item.manual_catalog_product_name) {
      return '';
    }

    return `
      <div class="section">
        <div class="external-box">
          <div class="section-title">Referencia externa / manual</div>
          <div class="result-title">
            ${escapeHtml(item.manual_external_supplier || item.manual_catalog_product_name || 'Proveedor externo')}
            ${item.costo_unitario ? ' · ' + money(item.costo_unitario) : ''}
          </div>

          ${item.manual_external_link ? `
            <div style="margin-top:10px;">
              <a href="${escapeHtml(item.manual_external_link)}" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-small">↗ Ver referencia</a>
            </div>
          ` : ''}

          <div class="warning-line">ⓘ Precio estimado — validar antes de aprobar</div>
        </div>
      </div>
    `;
  }

  function renderExternalSection(item) {
    if (!item.external_matches?.length) {
      if (item.status_key === 'not_found') {
        return `
          <div class="section">
            <div class="warning-line">ⓘ Producto no disponible en catálogo interno.</div>
            <div class="warning-line">ⓘ Se sugiere adquisición con proveedor externo.</div>
          </div>
        `;
      }

      return '';
    }

    return `
      <div class="section">
        <div class="section-title">Opciones de internet</div>
        ${item.external_matches.map(external => `
          <div class="external-box">
            <div class="result-title">${escapeHtml(external.title)}</div>
            <div class="result-meta">
              ${escapeHtml(external.source || 'Internet')}
              ${external.seller ? ' · ' + escapeHtml(external.seller) : ''}
              · Score ${Number(external.score || 0).toFixed(0)}%
            </div>

            <div style="margin-top:10px;">
              <a class="btn btn-outline btn-small" href="${escapeHtml(external.url)}" target="_blank" rel="noopener noreferrer">↗ Ver referencia</a>
            </div>
          </div>
        `).join('')}
      </div>
    `;
  }

  // 🔹 PASO 7: sección de la ficha técnica vinculada
  function renderTechSheetLinked(item) {
    if (!item.tech_sheet_id) return '';

    const pdfUrl = urlFor(routes.techSheetPdf, item.tech_sheet_id);

    return `
      <div class="section">
        <div class="section-title">Ficha técnica vinculada</div>
        <div class="result-card">
          <div class="result-title">📄 ${escapeHtml(item.tech_sheet_name || 'Ficha técnica')}</div>
          <div class="action-row" style="margin-top:10px;">
            <a class="btn btn-outline btn-small" target="_blank" rel="noopener noreferrer" href="${pdfUrl}">↗ Ver PDF</a>
            <button class="btn btn-ghost btn-small" type="button" onclick="openTechSheetsModal(${item.id})">Cambiar / editar</button>
          </div>
        </div>
      </div>
    `;
  }

  function renderActions(item) {
    return `
      <div class="section">
        <div class="action-row">
          <button class="btn btn-ghost btn-small" type="button" onclick="toggleEdit(${item.id})">✎ Editar</button>
          <button class="btn btn-success btn-small" type="button" onclick="setItemStatus(${item.id}, 'accepted_item')">✓ Aceptar</button>
          <button class="btn btn-danger btn-small" type="button" onclick="setItemStatus(${item.id}, 'rejected_item')">× Rechazar</button>
          <button class="btn btn-warning btn-small" type="button" onclick="setItemStatus(${item.id}, 'manual_review')">◎ Revisión</button>
          <button class="btn btn-soft btn-small" type="button" onclick="suggestItem(${item.id})">◎ Buscar coincidencias</button>
          <button class="btn btn-ghost btn-small" type="button" onclick="openManualModal(${item.id})">⌕ Buscar manualmente</button>
          <button class="btn btn-soft btn-small" type="button" onclick="openSamplesModal(${item.id})">📦 Muestras</button>
          <button class="btn btn-outline btn-small" type="button" onclick="openTechSheetsModal(${item.id})">📄 Ficha técnica</button>
        </div>
      </div>
    `;
  }

  function renderEditForm(item) {
    return `
      <form class="edit-form" id="edit-form-${item.id}" onsubmit="saveItem(event, ${item.id})">
        <div class="field">
          <label>Producto</label>
          <input class="input" name="descripcion_original" value="${escapeHtml(item.descripcion_original || '')}">
        </div>

        <div class="field">
          <label>Cantidad</label>
          <input class="input" type="number" step="0.01" name="cantidad_cotizada" value="${Number(item.cantidad_cotizada || 1)}">
        </div>

        <div class="field">
          <label>Unidad</label>
          <input class="input" name="unidad_solicitada" value="${escapeHtml(item.unidad_solicitada || '')}">
        </div>

        <div class="field">
          <label>Costo unit.</label>
          <input class="input" type="number" step="0.01" name="costo_unitario" value="${Number(item.costo_unitario || 0)}">
        </div>

        <div class="field">
          <label>Margen %</label>
          <input class="input" type="number" step="0.01" name="porcentaje_utilidad" value="${Number(item.item_margin_pct || 25)}">
        </div>

        <div class="field">
          <label>Proveedor</label>
          <input class="input" name="external_supplier" value="${escapeHtml(item.manual_external_supplier || '')}">
        </div>

        <div class="field">
          <label>Link ref.</label>
          <input class="input" name="external_link" value="${escapeHtml(item.manual_external_link || '')}">
        </div>

        <div class="action-row" style="grid-column:1/-1;">
          <button class="btn btn-primary btn-small" type="submit">✓ Guardar</button>
          <button class="btn btn-ghost btn-small" type="button" onclick="toggleEdit(${item.id})">Cancelar</button>
        </div>
      </form>
    `;
  }

  function updateItemInState(item) {
    const idx = items.findIndex(i => i.id === item.id);
    if (idx >= 0) {
      const prev = items[idx] || {};
      if (item.tech_sheet_id === undefined) item.tech_sheet_id = prev.tech_sheet_id ?? null;
      if (item.tech_sheet_name === undefined) item.tech_sheet_name = prev.tech_sheet_name ?? null;
      items[idx] = item;
    }
  }

  function toggleItem(id) {
    const card = document.querySelector(`.jureto-quote-page .item-card[data-id="${id}"]`);
    if (card) card.classList.toggle('open');
  }

  function toggleEdit(id) {
    const form = document.getElementById(`edit-form-${id}`);
    if (form) form.classList.toggle('show');
  }

  async function suggestItem(id) {
    const button = event?.target;
    const old = button?.innerHTML;

    if (button) {
      button.disabled = true;
      button.innerHTML = '<span class="loader"></span> Buscando...';
    }

    try {
      const data = await ajax(urlFor(routes.suggestItem, id), { method: 'POST', body: '{}' });
      updateItemInState(data.item);
      summary = data.summary || summary;
      renderItems();

      const card = document.querySelector(`.jureto-quote-page .item-card[data-id="${id}"]`);
      if (card) card.classList.add('open');
    } catch (e) {
      showInlineError(e.message);
    } finally {
      if (button) {
        button.disabled = false;
        button.innerHTML = old;
      }
    }
  }

  async function selectMatch(itemId, matchId) {
    try {
      const url = routes.selectMatch
        .replace('__ID__', itemId)
        .replace('__MATCH__', matchId);

      const data = await ajax(url, { method: 'POST', body: '{}' });
      updateItemInState(data.item);
      summary = data.summary || summary;
      renderItems();

      const card = document.querySelector(`.jureto-quote-page .item-card[data-id="${itemId}"]`);
      if (card) card.classList.add('open');
    } catch (e) {
      showInlineError(e.message);
    }
  }

  async function setItemStatus(id, status) {
    try {
      const data = await ajax(urlFor(routes.updateStatus, id), {
        method: 'POST',
        body: JSON.stringify({ ui_status: status })
      });

      updateItemInState(data.item);
      summary = data.summary || summary;
      renderItems();

      const card = document.querySelector(`.jureto-quote-page .item-card[data-id="${id}"]`);
      if (card) card.classList.add('open');
    } catch (e) {
      showInlineError(e.message);
    }
  }

  async function saveItem(event, id) {
    event.preventDefault();

    const form = event.target;
    const payload = Object.fromEntries(new FormData(form).entries());

    try {
      const data = await ajax(urlFor(routes.updateItem, id), {
        method: 'POST',
        body: JSON.stringify(payload)
      });

      updateItemInState(data.item);
      summary = data.summary || summary;
      renderItems();

      const card = document.querySelector(`.jureto-quote-page .item-card[data-id="${id}"]`);
      if (card) card.classList.add('open');
    } catch (e) {
      showInlineError(e.message);
    }
  }

  async function suggestAll() {
    if (isSuggestingAll) return;

    const button = document.getElementById('btnSuggestAll');
    const old = button.innerHTML;
    const pendingItems = items.filter(item => item.status_key !== 'exact');

    if (!pendingItems.length) {
      showProcessBox(
        'success',
        'No hay partidas pendientes',
        'Todas las partidas ya tienen coincidencia exacta o ya fueron procesadas.',
        items.length,
        items.length,
        []
      );

      setTimeout(hideProcessBox, 3500);
      return;
    }

    isSuggestingAll = true;
    button.disabled = true;
    button.innerHTML = '<span class="loader"></span> Procesando...';

    const total = pendingItems.length;
    let done = 0;
    let success = 0;
    const errors = [];
    const concurrency = 4;
    let cursor = 0;

    showProcessBox(
      '',
      'Buscando coincidencias por lotes',
      `Procesando ${total} partidas sin saturar el servidor...`,
      done,
      total,
      errors
    );

    const box = document.getElementById('processBox');
    if (box) {
      box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    async function worker() {
      while (cursor < pendingItems.length) {
        const currentIndex = cursor++;
        const item = pendingItems[currentIndex];

        try {
          const data = await ajax(urlFor(routes.suggestItem, item.id), {
            method: 'POST',
            body: '{}'
          });

          if (data.item) {
            updateItemInState(data.item);
          }

          if (data.summary) {
            summary = data.summary;
          }

          success++;
        } catch (error) {
          errors.push(
            `Partida #${item.sort || currentIndex + 1}: ${error.message || 'No se pudo procesar.'}`
          );
        } finally {
          done++;

          showProcessBox(
            errors.length ? 'error' : '',
            'Buscando coincidencias por lotes',
            `Procesadas ${done} de ${total}. Correctas: ${success}. Errores: ${errors.length}.`,
            done,
            total,
            errors
          );

          if (done % 5 === 0 || done === total) {
            renderItems();
          }
        }
      }
    }

    try {
      await Promise.all(
        Array.from({ length: Math.min(concurrency, pendingItems.length) }, () => worker())
      );

      renderItems();

      if (errors.length) {
        showProcessBox(
          'error',
          'Proceso terminado con algunos errores',
          `Se procesaron ${success} partidas correctamente y ${errors.length} fallaron. Puedes volver a intentar; se saltarán las exactas.`,
          done,
          total,
          errors
        );
      } else {
        showProcessBox(
          'success',
          'Coincidencias completadas',
          `Se procesaron ${success} partidas correctamente.`,
          done,
          total,
          []
        );

        setTimeout(hideProcessBox, 3500);
      }
    } finally {
      isSuggestingAll = false;
      button.disabled = false;
      button.innerHTML = old;
    }
  }

  async function saveGlobalMargin(applyToItems) {
    const margin = document.getElementById('globalMarginInput').value;

    try {
      const data = await ajax(routes.globalMargin, {
        method: 'POST',
        body: JSON.stringify({
          porcentaje_utilidad: margin,
          apply_to_items: applyToItems
        })
      });

      items = mergeTechSheetMeta(data.items) || items;
      summary = data.summary || summary;
      renderItems();
    } catch (e) {
      showInlineError(e.message);
    }
  }

  function openManualModal(id) {
    manualItemId = id;
    const item = items.find(i => i.id === id);

    document.getElementById('manualSubtitle').textContent = item?.descripcion_original || 'Producto';
    document.getElementById('manualQueryInput').value = item?.descripcion_original || '';
    document.getElementById('manualResults').innerHTML = '';
    document.getElementById('manualSearchStatus').textContent = 'Buscando coincidencias...';
    document.getElementById('manualModal').classList.add('show');

    manualTab = 'catalog';
    document.getElementById('manualTabCatalog').classList.add('active');
    document.getElementById('manualTabInternet').classList.remove('active');

    manualLastQuery = '';
    scheduleManualSearch(250);
  }

  function closeManualModal() {
    document.getElementById('manualModal').classList.remove('show');
  }

  function clearManualSearch() {
    document.getElementById('manualQueryInput').value = '';
    document.getElementById('manualResults').innerHTML = '';
    document.getElementById('manualSearchStatus').textContent = 'Escribe para buscar automáticamente.';
  }

  function scheduleManualSearch(delay = 420) {
    clearTimeout(manualSearchTimer);
    manualSearchTimer = setTimeout(() => runManualSearchLive(), delay);
  }

  async function runManualSearchLive() {
    const q = document.getElementById('manualQueryInput').value.trim();
    const resultsBox = document.getElementById('manualResults');
    const statusBox = document.getElementById('manualSearchStatus');

    if (!q) {
      resultsBox.innerHTML = '';
      statusBox.textContent = 'Escribe para buscar automáticamente.';
      return;
    }

    const cacheKey = manualTab + '::' + q;
    if (cacheKey === manualLastQuery) return;

    manualLastQuery = cacheKey;
    statusBox.innerHTML = '<span class="loader"></span> Buscando similitudes...';

    try {
      const params = new URLSearchParams({
        q,
        item_id: manualItemId,
        internet: manualTab === 'internet' ? '1' : '0'
      });

      const data = await ajax(routes.manualSearch + '?' + params.toString(), {
        method: 'GET',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json'
        }
      });

      if (manualTab === 'internet') {
        manualInternetResults = data.internet || [];
        statusBox.textContent = `${manualInternetResults.length} referencias externas encontradas`;
        renderManualInternet(manualInternetResults);
      } else {
        manualCatalogResults = data.products || [];
        statusBox.textContent = `${manualCatalogResults.length} productos similares encontrados`;
        renderManualCatalog(manualCatalogResults);
      }
    } catch (e) {
      resultsBox.innerHTML = `<p class="result-meta">${escapeHtml(e.message)}</p>`;
      statusBox.textContent = 'No se pudo completar la búsqueda.';
    }
  }

  function renderManualCatalog(products) {
    const box = document.getElementById('manualResults');

    if (!products.length) {
      box.innerHTML = '<p class="result-meta">Sin resultados similares en catálogo.</p>';
      return;
    }

    box.innerHTML = products.map((p, index) => `
      <div class="modal-result">
        <div style="min-width:0;">
          <div class="result-title">${escapeHtml(p.name)}</div>
          <div class="result-meta">
            SKU: ${escapeHtml(p.sku || '—')}
            · ${escapeHtml(p.brand || '—')}
            · Stock: ${p.stock ?? 0}
            · ${Number(p.similarity_pct || 0).toFixed(0)}%
          </div>
          <div class="result-meta">
            ${p.unit ? `<strong>Unidad:</strong> ${escapeHtml(p.unit)} · ` : ''}
            ${p.color ? `<strong>Color:</strong> ${escapeHtml(p.color)} · ` : ''}
            ${p.category ? `<strong>Categoría:</strong> ${escapeHtml(p.category)} · ` : ''}
            Costo ${money(p.cost)} · Precio ${money(p.price)}
          </div>
        </div>

        <button class="btn btn-primary btn-small" type="button" onclick="useManualCatalog(${index})">
          Usar
        </button>
      </div>
    `).join('');
  }

  function renderManualInternet(results) {
    const box = document.getElementById('manualResults');

    if (!results.length) {
      box.innerHTML = '<p class="result-meta">Sin resultados de internet.</p>';
      return;
    }

    box.innerHTML = results.map((r, index) => `
      <div class="modal-result">
        <div style="min-width:0;">
          <div class="result-title">${escapeHtml(r.title)}</div>
          <div class="result-meta">
            ${escapeHtml(r.source || 'Internet')}
            ${r.seller ? '· ' + escapeHtml(r.seller) : ''}
            · Score ${Number(r.score || 0).toFixed(0)}%
          </div>
          <div class="result-meta">${r.price ? money(r.price) : 'Precio por validar'}</div>
          ${r.url ? `<a class="btn btn-outline btn-small" target="_blank" rel="noopener noreferrer" href="${escapeHtml(r.url)}">↗ Ver referencia</a>` : ''}
        </div>

        <button class="btn btn-primary btn-small" type="button" onclick="useManualInternet(${index})">
          Usar
        </button>
      </div>
    `).join('');
  }

  async function useManualCatalog(index) {
    const product = manualCatalogResults[index];
    if (!product) return;

    const item = items.find(i => i.id === manualItemId);
    const margin = Number(item?.item_margin_pct || 25);
    const cost = Number(product.cost || 0);

    try {
      const data = await ajax(urlFor(routes.updateItem, manualItemId), {
        method: 'POST',
        body: JSON.stringify({
          catalog_product_name: product.name,
          costo_unitario: cost,
          porcentaje_utilidad: margin,
          external_supplier: product.brand || '',
          external_link: ''
        })
      });

      updateItemInState(data.item);
      summary = data.summary || summary;
      closeManualModal();
      renderItems();

      const card = document.querySelector(`.jureto-quote-page .item-card[data-id="${manualItemId}"]`);
      if (card) card.classList.add('open');
    } catch (e) {
      showInlineError(e.message);
    }
  }

  async function useManualInternet(index) {
    const result = manualInternetResults[index];
    if (!result) return;

    const item = items.find(i => i.id === manualItemId);
    const margin = Number(item?.item_margin_pct || 25);
    const cost = Number(result.price || 0);

    try {
      const data = await ajax(urlFor(routes.updateItem, manualItemId), {
        method: 'POST',
        body: JSON.stringify({
          catalog_product_name: result.title,
          costo_unitario: cost,
          porcentaje_utilidad: margin,
          external_supplier: result.source || result.seller || 'Proveedor externo',
          external_link: result.url || ''
        })
      });

      updateItemInState(data.item);
      summary = data.summary || summary;
      closeManualModal();
      renderItems();

      const card = document.querySelector(`.jureto-quote-page .item-card[data-id="${manualItemId}"]`);
      if (card) card.classList.add('open');
    } catch (e) {
      showInlineError(e.message);
    }
  }

  function openAddItemModal() {
    document.getElementById('addItemModal').classList.add('show');
  }

  function closeAddItemModal() {
    document.getElementById('addItemModal').classList.remove('show');
  }

  async function storeNewItem(event) {
    event.preventDefault();

    const form = event.target;
    const payload = Object.fromEntries(new FormData(form).entries());
    const submit = form.querySelector('button[type="submit"]');
    const old = submit.innerHTML;

    submit.disabled = true;
    submit.innerHTML = '<span class="loader"></span> Agregando...';

    try {
      const data = await ajax(routes.storeItem, {
        method: 'POST',
        body: JSON.stringify(payload)
      });

      items = mergeTechSheetMeta(data.items) || items;
      summary = data.summary || summary;

      closeAddItemModal();
      form.reset();
      renderItems();
    } catch (e) {
      showInlineError(e.message);
    } finally {
      submit.disabled = false;
      submit.innerHTML = old;
    }
  }

  /* ===================== MUESTRAS (almacén) ===================== */
  function closeSamplesModal() {
    document.getElementById('samplesModal').classList.remove('show');
  }

  async function openSamplesModal(id) {
    samplesItemId = id;
    const item = items.find(i => i.id === id);

    document.getElementById('samplesSubtitle').textContent = item?.descripcion_original || 'Producto';
    document.getElementById('samplesResults').innerHTML = '';
    document.getElementById('samplesStatus').innerHTML = '<span class="loader"></span> Buscando en catálogo y almacén...';
    document.getElementById('samplesModal').classList.add('show');

    try {
      const data = await ajax(urlFor(routes.itemSamples, id), { method: 'GET' });
      renderSamples(data);
    } catch (e) {
      document.getElementById('samplesStatus').textContent = e.message;
    }
  }

  function renderSamples(data) {
    const needed = Number(data.needed_qty || 0);
    const cands = data.candidates || [];

    document.getElementById('samplesStatus').textContent =
      `Cantidad solicitada: ${needed} · ${cands.length} coincidencias en catálogo`;

    const box = document.getElementById('samplesResults');

    if (!cands.length) {
      box.innerHTML = '<p class="result-meta">No se encontraron productos similares en el catálogo interno.</p>';
      return;
    }

    box.innerHTML = cands.map(c => {
      const locs = (c.locations || [])
        .map(l => `${escapeHtml(l.location)}: ${l.qty}${l.reserved ? ' (apartado ' + l.reserved + ')' : ''}`)
        .join(' · ');

      const buyBadge = c.to_buy > 0
        ? `<span class="badge badge-danger">Comprar ${c.to_buy}</span>`
        : `<span class="badge badge-success">Stock suficiente</span>`;

      return `
        <div class="result-card">
          <div class="result-title">${escapeHtml(c.name)} ${buyBadge}</div>
          <div class="result-meta">
            SKU: ${escapeHtml(c.sku || '—')} · ${escapeHtml(c.unit || '')} · Similitud ${Number(c.similarity_pct || 0).toFixed(0)}%
          </div>
          <div class="result-meta">
            <strong>En almacén:</strong> ${c.net_available} ·
            <strong>Apartado:</strong> ${c.reserved} ·
            <strong>Necesario:</strong> ${needed} ·
            <strong>Faltan:</strong> ${c.to_buy}
          </div>
          ${locs
            ? `<div class="result-meta">Ubicaciones: ${locs}</div>`
            : `<div class="result-meta">Sin inventario por ubicación (se usó stock general: ${c.stock_field}).</div>`}
        </div>
      `;
    }).join('');
  }

  /* ===================== FICHAS TÉCNICAS ===================== */
  function closeTechSheetsModal() {
    document.getElementById('techSheetsModal').classList.remove('show');
  }

  function techShowList() {
    document.getElementById('techListPane').style.display = '';
    document.getElementById('techFormPane').style.display = 'none';
    document.getElementById('techTabList').classList.add('active');
    document.getElementById('techTabForm').classList.remove('active');
  }

  function techShowCreate(sheet = null) {
    document.getElementById('techListPane').style.display = 'none';
    document.getElementById('techFormPane').style.display = '';
    document.getElementById('techTabList').classList.remove('active');
    document.getElementById('techTabForm').classList.add('active');

    const form = document.getElementById('techForm');
    form.reset();
    document.getElementById('techFormId').value = sheet?.id || '';

    if (sheet) {
      form.product_name.value = sheet.product_name || '';
      form.brand.value = sheet.brand || '';
      form.model.value = sheet.model || '';
      form.reference.value = sheet.reference || '';
      form.partida_number.value = sheet.partida_number || '';
    } else {
      const item = items.find(i => i.id === techItemId);
      form.product_name.value = item?.descripcion_original || '';
    }
  }

  function openTechSheetsModal(id) {
    techItemId = id;
    const item = items.find(i => i.id === id);

    document.getElementById('techSubtitle').textContent = item?.descripcion_original || 'Producto';
    document.getElementById('techQueryInput').value = item?.descripcion_original || '';
    document.getElementById('techSheetsModal').classList.add('show');

    techShowList();
    loadTechSheets();
  }

  async function loadTechSheets() {
    const q = document.getElementById('techQueryInput').value.trim();
    document.getElementById('techStatus').innerHTML = '<span class="loader"></span> Buscando fichas...';

    try {
      const params = new URLSearchParams({ q });
      const data = await ajax(urlFor(routes.techSheetsList, techItemId) + '?' + params.toString(), { method: 'GET' });
      renderTechSheets(data);
    } catch (e) {
      document.getElementById('techStatus').textContent = e.message;
    }
  }

  function renderTechSheets(data) {
    techSheetsCache = data.sheets || [];
    currentLinkedSheetId = data.linked_id || null;

    document.getElementById('techStatus').textContent = `${techSheetsCache.length} fichas encontradas`;
    const box = document.getElementById('techResults');

    if (!techSheetsCache.length) {
      box.innerHTML = '<p class="result-meta">No hay fichas. Crea una nueva en la pestaña de arriba.</p>';
      return;
    }

    box.innerHTML = techSheetsCache.map((s, i) => `
      <div class="modal-result">
        <div style="min-width:0;">
          <div class="result-title">
            ${escapeHtml(s.product_name)}
            ${s.id === currentLinkedSheetId ? '<span class="badge badge-success">Vinculada</span>' : ''}
          </div>
          <div class="result-meta">
            ${escapeHtml(s.brand || '—')}
            ${s.model ? '· ' + escapeHtml(s.model) : ''}
            ${s.reference ? '· Ref ' + escapeHtml(s.reference) : ''}
          </div>
          <div class="action-row" style="margin-top:8px;">
            <a class="btn btn-outline btn-small" target="_blank" rel="noopener noreferrer" href="${s.urls.pdf}">↗ PDF</a>
            ${s.urls.public ? `<a class="btn btn-ghost btn-small" target="_blank" rel="noopener noreferrer" href="${s.urls.public}">Ficha pública</a>` : ''}
            <button class="btn btn-ghost btn-small" type="button" onclick="techEditInline(${i})">✎ Editar</button>
          </div>
        </div>
        <button class="btn btn-primary btn-small" type="button" onclick="linkTechSheet(${s.id})">
          ${s.id === currentLinkedSheetId ? 'Quitar' : 'Vincular'}
        </button>
      </div>
    `).join('');
  }

  function techEditInline(index) {
    techShowCreate(techSheetsCache[index]);
  }

  async function linkTechSheet(sheetId) {
    const unlink = sheetId === currentLinkedSheetId;

    try {
      const data = await ajax(urlFor(routes.linkTechSheet, techItemId), {
        method: 'POST',
        body: JSON.stringify({ tech_sheet_id: unlink ? null : sheetId })
      });

      const idx = items.findIndex(i => i.id === techItemId);
      if (idx >= 0) {
        if (unlink) {
          items[idx].tech_sheet_id = null;
          items[idx].tech_sheet_name = null;
        } else {
          const s = techSheetsCache.find(x => x.id === sheetId);
          items[idx].tech_sheet_id = sheetId;
          items[idx].tech_sheet_name = s ? s.product_name : items[idx].tech_sheet_name;
        }
      }

      renderItems();

      const card = document.querySelector(`.jureto-quote-page .item-card[data-id="${techItemId}"]`);
      if (card) card.classList.add('open');

      loadTechSheets();
    } catch (e) {
      showInlineError(e.message);
    }
  }

  async function submitTechSheet(event) {
    event.preventDefault();

    const form = event.target;
    const fd = new FormData(form);
    const id = document.getElementById('techFormId').value;

    const url = id
      ? routes.updateTechSheet.replace('__ID__', id)
      : urlFor(routes.createTechSheet, techItemId);

    const submit = form.querySelector('button[type="submit"]');
    const old = submit.innerHTML;
    submit.disabled = true;
    submit.innerHTML = '<span class="loader"></span> Guardando...';

    try {
      const resp = await fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: fd
      });

      const text = await resp.text();
      let data = null;
      try { data = JSON.parse(text); } catch (_) {}

      if (!resp.ok || !data || data.ok === false) {
        throw new Error((data && data.message) || ('Error al guardar la ficha. ' + text.slice(0, 200)));
      }

      // Al crear, el backend la vincula automáticamente a la partida actual
      if (!id) {
        const idx = items.findIndex(i => i.id === techItemId);
        if (idx >= 0 && data.sheet) {
          items[idx].tech_sheet_id = data.sheet.id;
          items[idx].tech_sheet_name = data.sheet.product_name;
        }
      }

      techShowList();
      document.getElementById('techQueryInput').value = (data.sheet && data.sheet.product_name) || '';
      renderItems();

      const card = document.querySelector(`.jureto-quote-page .item-card[data-id="${techItemId}"]`);
      if (card) card.classList.add('open');

      loadTechSheets();
    } catch (e) {
      showInlineError(e.message);
    } finally {
      submit.disabled = false;
      submit.innerHTML = old;
    }
  }

  function bindDragEvents() {
    document.querySelectorAll('.jureto-quote-page .item-card').forEach(card => {
      card.addEventListener('dragstart', () => {
        if (currentFilter !== 'all') return;
        card.classList.add('dragging');
      });

      card.addEventListener('dragend', () => {
        if (currentFilter !== 'all') return;
        card.classList.remove('dragging');
        saveOrder();
      });

      card.addEventListener('dragover', (e) => {
        if (currentFilter !== 'all') return;

        e.preventDefault();

        const list = document.getElementById('itemsList');
        const dragging = document.querySelector('.jureto-quote-page .dragging');
        const after = getDragAfterElement(list, e.clientY);

        if (!dragging) return;

        if (after == null) {
          list.appendChild(dragging);
        } else {
          list.insertBefore(dragging, after);
        }
      });
    });
  }

  function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.item-card:not(.dragging)')];

    return draggableElements.reduce((closest, child) => {
      const box = child.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;

      if (offset < 0 && offset > closest.offset) {
        return { offset, element: child };
      }

      return closest;
    }, { offset: Number.NEGATIVE_INFINITY }).element;
  }

  async function saveOrder() {
    if (currentFilter !== 'all') return;

    const ids = [...document.querySelectorAll('#itemsList .item-card')]
      .map(card => Number(card.dataset.id));

    if (!ids.length) return;

    try {
      const data = await ajax(routes.reorder, {
        method: 'POST',
        body: JSON.stringify({ items: ids })
      });

      items = mergeTechSheetMeta(data.items) || items;
      summary = data.summary || summary;
      renderItems();
    } catch (e) {
      showInlineError(e.message);
    }
  }


  function getQuoteFileName(extension) {
    const safeFolio = String(exportFolio || 'cotizacion')
      .replace(/[^\w\-]+/g, '_')
      .replace(/_+/g, '_');

    return `${safeFolio}_tabla_extraida_pdf.${extension}`;
  }

  function isPlainObject(value) {
    return value && typeof value === 'object' && !Array.isArray(value);
  }

  function normalizeCell(value) {
    if (value === null || value === undefined) return '';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value);
  }

  function normalizeRows(rows) {
    if (!Array.isArray(rows) || rows.length === 0) return null;

    if (isPlainObject(rows[0])) {
      const columns = [];
      rows.forEach(row => {
        if (!isPlainObject(row)) return;
        Object.keys(row).forEach(key => {
          if (!columns.includes(key)) columns.push(key);
        });
      });

      if (!columns.length) return null;

      return {
        columns,
        rows: rows.filter(isPlainObject).map(row => {
          const out = {};
          columns.forEach(column => out[column] = normalizeCell(row[column]));
          return out;
        })
      };
    }

    if (Array.isArray(rows[0])) {
      const max = rows.reduce((acc, row) => Array.isArray(row) ? Math.max(acc, row.length) : acc, 0);
      if (!max) return null;

      const columns = Array.from({ length: max }, (_, index) => `Columna ${index + 1}`);

      return {
        columns,
        rows: rows.filter(Array.isArray).map(row => {
          const out = {};
          columns.forEach((column, index) => out[column] = normalizeCell(row[index]));
          return out;
        })
      };
    }

    return null;
  }

  function collectExtractedTables(payload, source = 'PDF') {
    const tables = [];
    const tableKeys = ['tables', 'tablas', 'table', 'tabla', 'rows', 'filas', 'items', 'partidas', 'line_items', 'extracted_items', 'raw_items', 'original_items', 'data'];

    function walk(value, path = '') {
      if (!value || typeof value !== 'object') return;

      if (Array.isArray(value)) {
        const normalized = normalizeRows(value);

        if (normalized && normalized.rows.length) {
          tables.push({
            title: path || 'Tabla extraída',
            source,
            columns: normalized.columns,
            rows: normalized.rows
          });
        }

        value.forEach((child, index) => walk(child, `${path} ${index + 1}`.trim()));
        return;
      }

      tableKeys.forEach(key => {
        if (!Object.prototype.hasOwnProperty.call(value, key)) return;

        const candidate = value[key];

        if (candidate && typeof candidate === 'object') {
          if (isPlainObject(candidate) && Array.isArray(candidate.columns) && Array.isArray(candidate.rows)) {
            const rows = candidate.rows.map(row => {
              if (Array.isArray(row)) {
                const out = {};
                candidate.columns.forEach((column, index) => out[column] = normalizeCell(row[index]));
                return out;
              }

              if (isPlainObject(row)) return row;
              return null;
            }).filter(Boolean);

            const normalized = normalizeRows(rows);

            if (normalized) {
              tables.push({
                title: key,
                source,
                columns: normalized.columns,
                rows: normalized.rows
              });
            }
          } else {
            const normalized = normalizeRows(candidate);

            if (normalized) {
              tables.push({
                title: key,
                source,
                columns: normalized.columns,
                rows: normalized.rows
              });
            }
          }
        }
      });

      Object.entries(value).forEach(([key, child]) => walk(child, key));
    }

    walk(payload, source);

    const seen = new Set();

    return tables.filter(table => {
      const signature = JSON.stringify(table.columns) + JSON.stringify(table.rows.slice(0, 5));
      if (seen.has(signature)) return false;
      seen.add(signature);
      return true;
    });
  }

  function getExportTables() {
    const tables = [];

    Object.entries(rawExportPayloads || {}).forEach(([source, payload]) => {
      collectExtractedTables(payload, source).forEach(table => tables.push(table));
    });

    if (tables.length) return tables;

    return [{
      title: 'Partidas normalizadas',
      source: 'fallback_items',
      columns: ['descripcion_original', 'unidad_solicitada', 'cantidad_minima', 'cantidad_maxima', 'cantidad_cotizada', 'costo_unitario', 'precio_unitario', 'subtotal'],
      rows: items.map(item => ({
        descripcion_original: item.descripcion_original || '',
        unidad_solicitada: item.unidad_solicitada || '',
        cantidad_minima: item.cantidad_minima || '',
        cantidad_maxima: item.cantidad_maxima || '',
        cantidad_cotizada: item.cantidad_cotizada || '',
        costo_unitario: item.costo_unitario || '',
        precio_unitario: item.precio_unitario || '',
        subtotal: item.subtotal || ''
      }))
    }];
  }

  function buildExtractedTablesHtml() {
    const generatedAt = new Date().toLocaleString('es-MX');
    const tables = getExportTables();

    const tablesHtml = tables.map((table, tableIndex) => {
      const columns = Array.isArray(table.columns) ? table.columns : [];
      const rows = Array.isArray(table.rows) ? table.rows : [];
      const thead = columns.map(column => `<th>${escapeHtml(column)}</th>`).join('');
      const tbody = rows.map(row => `<tr>${columns.map(column => `<td>${escapeHtml(row?.[column] ?? '')}</td>`).join('')}</tr>`).join('');

      return `
        <div class="table-block">
          <h2>${escapeHtml(table.title || ('Tabla extraída ' + (tableIndex + 1)))}</h2>
          <div class="table-meta">Fuente: ${escapeHtml(table.source || 'PDF')} · Filas: ${rows.length} · Columnas: ${columns.length}</div>
          <table>
            <thead><tr>${thead}</tr></thead>
            <tbody>${tbody || `<tr><td colspan="${Math.max(columns.length, 1)}">Sin filas extraídas.</td></tr>`}</tbody>
          </table>
        </div>
      `;
    }).join('');

    return `
      <!DOCTYPE html>
      <html lang="es">
      <head>
        <meta charset="UTF-8">
        <title>${escapeHtml(exportTitle)}</title>
        <style>
          body { font-family: Arial, sans-serif; color: #333333; background: #ffffff; margin: 24px; }
          h1 { color: #111111; font-size: 22px; margin: 0 0 6px; }
          h2 { color: #111111; font-size: 16px; margin: 22px 0 6px; }
          .meta, .table-meta { color: #666666; font-size: 12px; margin-bottom: 12px; }
          .table-block { margin-top: 18px; page-break-inside: avoid; }
          table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 18px; }
          th { background: #f9fafb; color: #111111; font-weight: 700; border: 1px solid #ebebeb; padding: 8px; text-align: left; vertical-align: top; }
          td { border: 1px solid #ebebeb; padding: 7px; vertical-align: top; }
          tr:nth-child(even) td { background: #fcfcfc; }
        </style>
      </head>
      <body>
        <h1>${escapeHtml(exportTitle)}</h1>
        <div class="meta">Folio: ${escapeHtml(exportFolio)} · Generado: ${escapeHtml(generatedAt)} · Exportación basada en tabla extraída del PDF</div>
        ${tablesHtml || '<p>No se encontraron tablas para exportar.</p>'}
      </body>
      </html>
    `;
  }

  function downloadBlob(content, fileName, mimeType) {
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);

    const a = document.createElement('a');
    a.href = url;
    a.download = fileName;
    document.body.appendChild(a);
    a.click();

    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  function exportExtractedTablesToExcel() {
    const html = buildExtractedTablesHtml();

    downloadBlob(
      `<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>${html}</body></html>`,
      getQuoteFileName('xls'),
      'application/vnd.ms-excel;charset=utf-8'
    );
  }

function exportExtractedTablesToWord() {
  const title = @json($exportTitle);
  const folio = @json($exportFolio);
  const generatedAt = new Date().toLocaleString('es-MX');
  const tables = getExportTables();

  const tablesHtml = tables.map((table, tableIndex) => {
    const columns = Array.isArray(table.columns) ? table.columns : [];
    const rows = Array.isArray(table.rows) ? table.rows : [];

    const thead = columns.map(column => `
      <th>${escapeHtml(column)}</th>
    `).join('');

    const tbody = rows.map(row => `
      <tr>
        ${columns.map(column => `
          <td>${escapeHtml(row?.[column] ?? '')}</td>
        `).join('')}
      </tr>
    `).join('');

    return `
      <div class="table-block">
        <h2>${escapeHtml(table.title || ('Tabla extraída ' + (tableIndex + 1)))}</h2>

        <div class="table-meta">
          Fuente: ${escapeHtml(table.source || 'PDF')} · Filas: ${rows.length} · Columnas: ${columns.length}
        </div>

        <table>
          <thead>
            <tr>${thead}</tr>
          </thead>