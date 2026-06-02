@extends('layouts.app')
@section('content_class', 'content--flush')
@section('content')

<style>
/* ==========================================================================
   SISTEMA DE DISEÑO REFACTORIZADO (Criterio Emil & BEM)
   ========================================================================== */
:root {
  --cobalto: #1B4F8A;
  --verde-olivo: #4A7A3A;
  --bg-app: #F5F7FA;
  --border: #E8EBF0;
  --text-main: #111827;
  --text-muted: #6B7280;
  --ease-out: cubic-bezier(0.23, 1, 0.32, 1);
}

.jureto-quote-page { font-family: 'Open Sans', sans-serif; background: var(--bg-app); min-height: 100vh; padding: 24px; color: var(--text-main); }
.q-wrap { max-width: 1200px; margin: 0 auto; }

/* Header & Botón Volver */
.q-back-link { display: inline-flex; align-items: center; gap: 8px; color: var(--text-muted); text-decoration: none; font-weight: 600; margin-bottom: 24px; transition: color 150ms var(--ease-out); }
.q-back-link:hover { color: var(--cobalto); }
.q-header { margin-bottom: 24px; }
.q-header__folio { font-family: monospace; font-size: 13px; color: var(--text-muted); background: #E5E7EB; padding: 4px 8px; border-radius: 4px; display: inline-block; margin-bottom: 8px; }
.q-header__title { font-family: 'Montserrat', sans-serif; font-size: 24px; font-weight: 700; margin: 0 0 4px 0; color: var(--text-main); }
.q-header__subtitle { font-size: 14px; color: var(--text-muted); margin: 0; }

/* Sticky Action Bar */
.q-actionbar { display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 12px 24px; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 24px; position: sticky; top: 16px; z-index: 50; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
.q-actionbar__primary { display: flex; gap: 12px; align-items: center; }
.q-actionbar__overflow { position: relative; }

/* Summary & Filters */
.q-dashboard { display: grid; grid-template-columns: 1fr auto; gap: 24px; margin-bottom: 24px; align-items: center; }
.q-metrics { display: flex; gap: 24px; }
.q-metric { display: flex; flex-direction: column; }
.q-metric__val { font-size: 18px; font-weight: 700; color: var(--text-main); }
.q-metric__lbl { font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
.q-filters { display: flex; background: #E5E7EB; padding: 4px; border-radius: 6px; }
.q-filter-btn { padding: 6px 16px; border: none; background: transparent; border-radius: 4px; font-size: 13px; font-weight: 600; color: var(--text-muted); cursor: pointer; transition: all 150ms var(--ease-out); }
.q-filter-btn.active { background: #fff; color: var(--text-main); box-shadow: 0 1px 3px rgba(0,0,0,0.1); }

/* Item Card (Rediseño) */
.q-item { background: #fff; border: 1px solid var(--border); border-radius: 8px; margin-bottom: 12px; transition: box-shadow 240ms var(--ease-out); }
.q-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.q-item__head { padding: 16px; display: grid; grid-template-columns: minmax(0, 1fr) auto auto; gap: 16px; align-items: center; cursor: pointer; user-select: none; }
.q-item__identity { display: flex; align-items: center; gap: 12px; overflow: hidden; }
.q-item__index { font-weight: 700; color: var(--text-muted); min-width: 24px; }
.q-item__name { font-size: 14px; font-weight: 600; margin: 0 0 4px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.q-item__meta { font-size: 12px; color: var(--text-muted); }
.q-item__money { text-align: right; font-weight: 700; font-size: 15px; display: flex; align-items: center; gap: 12px; }
.q-item__actions { display: flex; gap: 8px; align-items: center; }

/* Tabs para Detalle */
.q-item__detail { border-top: 1px solid var(--border); background: #FAFAFB; padding: 16px; border-radius: 0 0 8px 8px; }
.q-tabs { display: flex; gap: 8px; border-bottom: 1px solid var(--border); margin-bottom: 16px; padding-bottom: 8px; }
.q-tab { background: transparent; border: none; font-size: 13px; font-weight: 600; color: var(--text-muted); cursor: pointer; padding: 4px 12px; border-radius: 4px; transition: background 150ms var(--ease-out); }
.q-tab.active { background: #E5E7EB; color: var(--text-main); }
.q-tab:hover:not(.active) { background: #F3F4F6; }

/* Buttons & Badges Reusables */
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 8px 16px; font-size: 14px; font-weight: 600; border-radius: 6px; cursor: pointer; border: 1px solid transparent; transition: transform 150ms var(--ease-out), background 150ms var(--ease-out); }
.btn:active { transform: scale(0.97); }
.btn-primary { background: var(--cobalto); color: #fff; }
.btn-primary:hover { opacity: 0.9; }
.btn-outline { background: transparent; border-color: var(--border); color: var(--text-main); }
.btn-outline:hover { background: #F9FAFB; }
.btn-ghost { background: transparent; color: var(--text-muted); }
.btn-ghost:hover { background: #F3F4F6; color: var(--text-main); }
.btn-small { padding: 4px 10px; font-size: 12px; }

.badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; }
.badge-success { background: #DEF7EC; color: #03543F; }
.badge-info { background: #E1EFFE; color: #1E429F; }
.badge-warning { background: #FDF6B2; color: #723B13; }
.badge-danger { background: #FDE8E8; color: #9B1C1C; }

/* Dropdown base */
.dropdown-menu { display: none; position: absolute; top: 100%; right: 0; margin-top: 4px; background: #fff; border: 1px solid var(--border); border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); min-width: 180px; padding: 8px 0; z-index: 100; }
.dropdown-menu.show { display: block; }
.dropdown-item { display: block; width: 100%; text-align: left; padding: 8px 16px; background: transparent; border: none; font-size: 13px; color: var(--text-main); cursor: pointer; }
.dropdown-item:hover { background: #F9FAFB; }
.dropdown-divider { height: 1px; background: var(--border); margin: 4px 0; }

/* Manteniendo compatibilidad con tus estilos de Modales/Forms anteriores */
.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: none; align-items: center; justify-content: center; z-index: 999; }
.modal-backdrop.show { display: flex; }
.modal { background: #fff; width: 90%; max-width: 600px; border-radius: 12px; padding: 24px; max-height: 90vh; overflow-y: auto; }
.modal-head { display: flex; justify-content: space-between; margin-bottom: 16px; }
.input { width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 14px; outline: none; }
.input:focus { border-color: var(--cobalto); }
.field { margin-bottom: 12px; }
.field label { display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; }
.result-card, .external-box { background: #fff; border: 1px solid var(--border); padding: 12px; border-radius: 6px; margin-bottom: 8px; }
</style>

@php
  // ==========================================================================
  // PAYLOAD DE PHP (Lógica de backend intacta)
  // ==========================================================================
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
                      'score' => (float) $match->score,
                      'seleccionado' => (bool) $match->seleccionado,
                      'product' => $p ? [
                          'id' => $p->id,
                          'name' => $p->name,
                          'sku' => $p->sku,
                          'brand' => $p->brand,
                          'stock' => $p->stock ?? 0,
                      ] : null,
                  ];
              })->all(),
              'external_matches' => $item->externalMatches->sortBy('rank')->values()->map(function ($external) {
                  return [
                      'id' => $external->id,
                      'source' => $external->source,
                      'title' => $external->title,
                      'seller' => $external->seller,
                      'price' => (float) $external->price,
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
      'profit' => $profit,
      'margin' => $margin,
      'total_items' => $itemsPayload->count(),
  ];

  $exportFolio = $propuestaComercial->folio ?: ('TEOA' . str_pad((string) $propuestaComercial->id, 8, '0', STR_PAD_LEFT));
  $exportTitle = $propuestaComercial->titulo ?: ('COT-' . strtoupper(substr(md5($propuestaComercial->id . $propuestaComercial->created_at), 0, 8)));

  // Función de utilería para exportación
  $decodeExportValue = function ($value) {
      if ($value instanceof \Illuminate\Support\Collection) return $value->toArray();
      if (is_array($value)) return $value;
      if (is_object($value)) return json_decode(json_encode($value), true) ?: [];
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
      if (!empty($decoded)) $rawExportPayloads['propuesta_' . $field] = $decoded;
  }
@endphp

<div class="jureto-quote-page">
  <div class="q-wrap">
    
    <a href="{{ route('propuestas-comerciales.index') }}" class="q-back-link">
      <span>←</span> Volver
    </a>

    <!-- Header Principal -->
    <header class="q-header">
      <div class="q-header__folio">{{ $exportFolio }}</div>
      <h1 class="q-header__title">{{ $exportTitle }}</h1>
      <p class="q-header__subtitle"><span id="itemsCountText">{{ $summaryPayload['total_items'] }}</span> partidas analizadas por IA</p>
    </header>

    <!-- Sticky Action Bar -->
    <div class="q-actionbar">
      <div class="q-actionbar__primary">
        <button class="btn btn-outline" type="button" id="btnSuggestAll">
          <span>⌕</span> Buscar coincidencias
        </button>
        <a href="{{ route('propuestas-comerciales.cliente.show', $propuestaComercial) }}" class="btn btn-primary" style="background-color: var(--verde-olivo);">
          <span>✓</span> Aprobar Cotización
        </a>
      </div>

      <!-- Popover Menu -->
      <div class="q-actionbar__overflow">
        <button class="btn btn-ghost" type="button" onclick="document.getElementById('overflowMenu').classList.toggle('show')">
          ⋯ Más acciones
        </button>
        <div class="dropdown-menu" id="overflowMenu">
          <button class="dropdown-item" id="btnExportExcel">Exportar Excel PDF</button>
          <button class="dropdown-item" id="btnExportWord">Exportar Word PDF</button>
          <div class="dropdown-divider"></div>
          <a href="{{ route('propuestas-comerciales.fallo.show', $propuestaComercial) }}" class="dropdown-item" style="text-decoration: none;">Generar Acta de fallo</a>
          <button class="dropdown-item" onclick="openAddItemModal()">Agregar partida manual</button>
        </div>
      </div>
    </div>

    <!-- Dashboard: Métricas y Filtros Separados -->
    <div class="q-dashboard">
      <div class="q-metrics">
        <div class="q-metric"><span class="q-metric__val" id="sumSale">$0</span><span class="q-metric__lbl">Venta</span></div>
        <div class="q-metric"><span class="q-metric__val" id="sumProfit">$0</span><span class="q-metric__lbl">Utilidad</span></div>
        <div class="q-metric"><span class="q-metric__val" id="sumMargin">0%</span><span class="q-metric__lbl">Margen</span></div>
      </div>

      <div class="q-filters">
        <button class="q-filter-btn active" data-filter="all">Todos (<span id="sumAll">0</span>)</button>
        <button class="q-filter-btn" data-filter="exact">Exactos (<span id="sumExact">0</span>)</button>
        <button class="q-filter-btn" data-filter="similar">Similares (<span id="sumSimilar">0</span>)</button>
        <button class="q-filter-btn" data-filter="not_found">No encontrados (<span id="sumNotFound">0</span>)</button>
      </div>
    </div>

    <!-- Process Box (Errores / Lotes) -->
    <div id="processBox" style="display:none; background:#FDE8E8; padding:12px; border-radius:6px; margin-bottom:24px; color:#9B1C1C;">
      <strong id="processTitle">Procesando...</strong>
      <p id="processText" style="margin:4px 0 0; font-size:13px;"></p>
    </div>

    <!-- Lista de Partidas -->
    <div id="itemsList"></div>

  </div>

  <!-- ===================== MODALES (Se mantienen del original pero limpios) ===================== -->
  <div class="modal-backdrop" id="manualModal">
    <div class="modal">
      <div class="modal-head">
        <div>
          <h2 style="margin:0;">Búsqueda manual</h2>
          <p style="margin:4px 0 0; font-size:13px; color:var(--text-muted);" id="manualSubtitle">Producto</p>
        </div>
        <button class="btn btn-ghost btn-small" type="button" onclick="closeManualModal()">×</button>
      </div>
      <input class="input" id="manualQueryInput" placeholder="Buscar producto..." autocomplete="off">
      <div style="display:flex; gap:8px; margin-top:12px; border-bottom:1px solid var(--border); padding-bottom:8px;">
        <button class="q-tab active" id="manualTabCatalog">Catálogo interno</button>
        <button class="q-tab" id="manualTabInternet">Internet</button>
      </div>
      <div id="manualSearchStatus" style="font-size:12px; color:var(--text-muted); margin:12px 0;">Escribe para buscar...</div>
      <div id="manualResults"></div>
    </div>
  </div>

  <div class="modal-backdrop" id="addItemModal">
    <div class="modal">
      <div class="modal-head">
        <h2 style="margin:0;">Agregar nueva partida</h2>
        <button class="btn btn-ghost btn-small" type="button" onclick="closeAddItemModal()">×</button>
      </div>
      <form id="addItemForm" onsubmit="storeNewItem(event)">
        <div class="field"><label>Producto</label><input class="input" name="descripcion_original" required></div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div class="field"><label>Cantidad</label><input class="input" type="number" name="cantidad_cotizada" value="1"></div>
          <div class="field"><label>Costo unit.</label><input class="input" type="number" step="0.01" name="costo_unitario" value="0"></div>
        </div>
        <button class="btn btn-primary" type="submit" style="width:100%; margin-top:12px;">Agregar partida</button>
      </form>
    </div>
  </div>

</div>

<!-- ==========================================================================
     LÓGICA JAVASCRIPT (Refactorizada)
     ========================================================================== -->
<script>
  const csrfToken = @json(csrf_token());
  let items = @json($itemsPayload);
  let summary = @json($summaryPayload);
  let rawExportPayloads = @json($rawExportPayloads);
  const exportFolio = @json($exportFolio);
  const exportTitle = @json($exportTitle);
  let currentFilter = 'all';

  // Rutas base
  const routes = {
    suggestItem: @json(url('/propuesta-comercial-items/__ID__/ajax/suggest')),
    updateItem: @json(url('/propuesta-comercial-items/__ID__/ajax/update')),
    updateStatus: @json(url('/propuesta-comercial-items/__ID__/ajax/status')),
    manualSearch: @json(route('propuestas-comerciales.ajax.manual-search', $propuestaComercial)),
    storeItem: @json(route('propuestas-comerciales.ajax.items.store', $propuestaComercial)),
    selectMatch: @json(url('/propuesta-comercial-items/__ID__/ajax/select-match/__MATCH__')),
  };

  // Cierra dropdowns al hacer click afuera
  window.addEventListener('click', function(e) {
    if (!e.target.matches('.q-actionbar__overflow .btn')) {
      document.getElementById('overflowMenu').classList.remove('show');
    }
  });

  function money(n) { return Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 }); }
  function escapeHtml(val) { return String(val ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;'); }

  async function ajax(url, options = {}) {
    const response = await fetch(url, { ...options, headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json', ...(options.headers || {}) }});
    const raw = await response.text();
    let data; try { data = JSON.parse(raw); } catch (e) { data = null; }
    if (!response.ok || (data && data.ok === false)) throw new Error(data?.message || 'Error en la petición.');
    return data;
  }

  // Render Dashboard
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

    document.querySelectorAll('.q-filter-btn').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.filter === currentFilter);
    });
  }

  function renderItems() {
    renderSummary();
    const list = document.getElementById('itemsList');
    const filtered = items.filter(item => {
      if (currentFilter === 'all') return true;
      return item.status_key === currentFilter;
    });
    list.innerHTML = filtered.map((item, idx) => renderItemCard(item, idx)).join('');
  }

  // ------------------------------------------------------------------
  // ARQUITECTURA DE LA CARD (TABS Y CTAS)
  // ------------------------------------------------------------------
  function renderItemCard(item, idx) {
    const qty = Number(item.cantidad_cotizada || item.cantidad_maxima || item.cantidad_minima || 1);
    const subtotal = Number(item.subtotal || (Number(item.precio_unitario || 0) * qty));
    
    // Decisión de CTA Primario por estado
    let primaryCTA = '';
    if (item.status_key === 'not_found') {
        primaryCTA = `<button class="btn btn-outline btn-small" onclick="openManualModal(${item.id}); event.stopPropagation();">⌕ Buscar alternativa</button>`;
    } else if (item.status_key === 'similar') {
        primaryCTA = `<button class="btn btn-primary btn-small" style="background-color: var(--verde-olivo);" onclick="setItemStatus(${item.id}, 'accepted_item'); event.stopPropagation();">✓ Aceptar coincidencia ▸</button>`;
    } else {
        primaryCTA = `<span class="badge badge-success">✓ Aceptado</span> <button class="btn btn-ghost btn-small" onclick="openManualModal(${item.id}); event.stopPropagation();">Cambiar</button>`;
    }

    return `
      <div class="q-item" data-id="${item.id}">
        <!-- CABECERA -->
        <div class="q-item__head" onclick="toggleItemTabs(${item.id})">
          <div class="q-item__identity">
             <button class="btn-ghost" type="button" style="border:none; padding:4px;" onclick="event.stopPropagation()">⠿</button>
             <div class="q-item__index">${idx + 1}</div>
             <div>
                <h3 class="q-item__name">${escapeHtml(item.descripcion_original || 'Sin descripción')}</h3>
                <div class="q-item__meta">
                   ${qty} ${escapeHtml(item.unidad_solicitada || 'pz')}
                   ${item.producto_seleccionado?.brand ? ' · ' + escapeHtml(item.producto_seleccionado.brand) : ''}
                   ${item.tech_sheet_id ? ' <span class="badge badge-success">📄 Ficha</span>' : ''}
                </div>
             </div>
          </div>
          
          <div class="q-item__money">
             <span>${money(subtotal)}</span>
             ${item.match_score ? `<span class="badge badge-info">${Number(item.match_score).toFixed(0)}%</span>` : ''}
          </div>

          <div class="q-item__actions">
             ${primaryCTA}
             <button class="btn btn-ghost btn-small" onclick="toggleItemTabs(${item.id}); event.stopPropagation();">⋯</button>
          </div>
        </div>

        <!-- DETALLE (TABS) -->
        <div class="q-item__detail" id="detail-${item.id}" style="display: none;">
           <div class="q-tabs">
              <button class="q-tab active" onclick="switchTab(event, 'catalog', ${item.id})">Catálogo</button>
              <button class="q-tab" onclick="switchTab(event, 'internet', ${item.id})">Internet</button>
              <button class="q-tab" onclick="switchTab(event, 'edit', ${item.id})">Editar Métrica</button>
           </div>
           
           <div class="q-tab-content" id="tab-catalog-${item.id}" style="display:block;">${renderCatalogTab(item)}</div>
           <div class="q-tab-content" id="tab-internet-${item.id}" style="display:none;">${renderExternalTab(item)}</div>
           <div class="q-tab-content" id="tab-edit-${item.id}" style="display:none;">${renderEditTab(item)}</div>
        </div>
      </div>
    `;
  }

  // Control de Pestañas
  window.toggleItemTabs = function(id) {
      const detail = document.getElementById(`detail-${id}`);
      detail.style.display = detail.style.display === 'none' ? 'block' : 'none';
  };

  window.switchTab = function(event, tabName, id) {
      const detailBox = document.getElementById(`detail-${id}`);
      detailBox.querySelectorAll('.q-tab-content').forEach(el => el.style.display = 'none');
      detailBox.querySelectorAll('.q-tab').forEach(el => el.classList.remove('active'));
      
      document.getElementById(`tab-${tabName}-${id}`).style.display = 'block';
      event.currentTarget.classList.add('active');
  };

  // Contenido de Pestañas
  function renderCatalogTab(item) {
    if (item.ui_status === 'accepted_item' && item.producto_seleccionado) {
      return `<div class="result-card"><strong style="color:var(--verde-olivo);">✓ Seleccionado:</strong> ${escapeHtml(item.producto_seleccionado.name)} (SKU: ${item.producto_seleccionado.sku || 'N/A'})</div>`;
    }
    if (item.matches?.length) {
      return item.matches.map(m => `
        <div class="result-card" style="display:flex; justify-content:space-between; align-items:center;">
          <div>
            <strong>${escapeHtml(m.product?.name)}</strong><br>
            <span style="font-size:12px; color:var(--text-muted);">SKU: ${escapeHtml(m.product?.sku)} · Score: ${m.score}%</span>
          </div>
          <button class="btn btn-outline btn-small" onclick="selectMatch(${item.id}, ${m.id})">Usar coincidencia</button>
        </div>
      `).join('');
    }
    return `<p style="font-size:13px; color:var(--text-muted);">No hay coincidencias en catálogo.</p>`;
  }

  function renderExternalTab(item) {
    if (!item.external_matches?.length) return `<p style="font-size:13px; color:var(--text-muted);">No hay opciones en internet guardadas.</p>`;
    return item.external_matches.map(ext => `
      <div class="external-box">
        <strong>${escapeHtml(ext.title)}</strong><br>
        <span style="font-size:12px; color:var(--text-muted);">${escapeHtml(ext.source)} · Precio estimado: ${money(ext.price)}</span>
        <div style="margin-top:8px;"><a href="${ext.url}" target="_blank" class="btn btn-outline btn-small">↗ Ver enlace</a></div>
      </div>
    `).join('');
  }

  function renderEditTab(item) {
    return `
      <form onsubmit="saveItem(event, ${item.id})">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
          <div class="field"><label>Costo Unitario</label><input class="input" type="number" step="0.01" name="costo_unitario" value="${item.costo_unitario}"></div>
          <div class="field"><label>Margen (%)</label><input class="input" type="number" step="0.01" name="porcentaje_utilidad" value="${item.item_margin_pct}"></div>
        </div>
        <button class="btn btn-primary btn-small" type="submit">Guardar Cambios</button>
      </form>
    `;
  }

  // ------------------------------------------------------------------
  // LÓGICA DE ACTUALIZACIÓN (AJAX)
  // ------------------------------------------------------------------
  function updateState(data) {
    if (data.item) {
      const idx = items.findIndex(i => i.id === data.item.id);
      if (idx >= 0) items[idx] = data.item;
    }
    if (data.summary) summary = data.summary;
    renderItems();
  }

  async function setItemStatus(id, status) {
    try {
      const data = await ajax(routes.updateStatus.replace('__ID__', id), { method: 'POST', body: JSON.stringify({ ui_status: status }) });
      updateState(data);
    } catch (e) { alert(e.message); }
  }

  async function selectMatch(itemId, matchId) {
    try {
      const url = routes.selectMatch.replace('__ID__', itemId).replace('__MATCH__', matchId);
      const data = await ajax(url, { method: 'POST', body: '{}' });
      updateState(data);
    } catch (e) { alert(e.message); }
  }

  async function saveItem(e, id) {
    e.preventDefault();
    try {
      const payload = Object.fromEntries(new FormData(e.target).entries());
      const data = await ajax(routes.updateItem.replace('__ID__', id), { method: 'POST', body: JSON.stringify(payload) });
      updateState(data);
    } catch (err) { alert(err.message); }
  }

  // Filtros
  document.querySelectorAll('.q-filter-btn').forEach(btn => {
    btn.addEventListener('click', () => { currentFilter = btn.dataset.filter || 'all'; renderItems(); });
  });

  // ------------------------------------------------------------------
  // EXPORTACIONES A EXCEL / WORD CON INFO DE GRUPO MEDIBUY
  // ------------------------------------------------------------------
  function getQuoteFileName(extension) {
    const safeFolio = String(exportFolio || 'cotizacion').replace(/[^\w\-]+/g, '_').replace(/_+/g, '_');
    return `${safeFolio}_tabla_extraida_pdf.${extension}`;
  }

  function getExportTables() {
    // Función simplificada de recolección para asegurar datos tabulares puros (sin placeholders)
    return [{
      title: 'Partidas normalizadas',
      source: 'Análisis IA',
      columns: ['Partida', 'Descripción', 'Cantidad', 'Unidad', 'Costo Unit.', 'Precio Unit.', 'Subtotal'],
      rows: items.map((item, i) => ({
        'Partida': i + 1,
        'Descripción': item.descripcion_original || '',
        'Cantidad': item.cantidad_cotizada || '',
        'Unidad': item.unidad_solicitada || '',
        'Costo Unit.': money(item.costo_unitario),
        'Precio Unit.': money(item.precio_unitario),
        'Subtotal': money(item.subtotal)
      }))
    }];
  }

  function buildExtractedTablesHtml() {
    const generatedAt = new Date().toLocaleString('es-MX');
    const tables = getExportTables();
    
    const tablesHtml = tables.map(table => {
      const thead = table.columns.map(c => `<th>${escapeHtml(c)}</th>`).join('');
      const tbody = table.rows.map(r => `<tr>${table.columns.map(c => `<td>${escapeHtml(r[c])}</td>`).join('')}</tr>`).join('');
      return `<table><thead><tr>${thead}</tr></thead><tbody>${tbody}</tbody></table>`;
    }).join('');

    return `
      <!DOCTYPE html>
      <html lang="es">
      <head>
        <meta charset="UTF-8">
        <title>${escapeHtml(exportTitle)}</title>
        <style>
          body { font-family: Arial, sans-serif; color: #333; margin: 24px; }
          h1 { color: #111; font-size: 22px; margin: 0 0 6px; }
          .meta { font-size: 12px; color: #555; margin-bottom: 24px; line-height: 1.5; }
          table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 18px; }
          th { background: #f9fafb; font-weight: 700; border: 1px solid #ddd; padding: 8px; text-align: left; }
          td { border: 1px solid #ddd; padding: 7px; vertical-align: top; }
        </style>
      </head>
      <body>
        <h1>${escapeHtml(exportTitle)}</h1>
        <div class="meta">
          Folio: ${escapeHtml(exportFolio)}<br>
          Generado: ${escapeHtml(generatedAt)}<br><br>
          <strong>Grupo Medibuy</strong><br>
          ventas@grupomedibuy.com | 722 448 5191 | www.grupomedibuy.com
        </div>
        ${tablesHtml}
      </body>
      </html>
    `;
  }

  function downloadBlob(content, fileName, mimeType) {
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = fileName;
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
  }

  document.getElementById('btnExportExcel')?.addEventListener('click', () => {
    downloadBlob(buildExtractedTablesHtml(), getQuoteFileName('xls'), 'application/vnd.ms-excel;charset=utf-8');
  });

  document.getElementById('btnExportWord')?.addEventListener('click', () => {
    downloadBlob(buildExtractedTablesHtml(), getQuoteFileName('doc'), 'application/msword;charset=utf-8');
  });

  // Modal Manual Básica
  function openAddItemModal() { document.getElementById('addItemModal').classList.add('show'); }
  function closeAddItemModal() { document.getElementById('addItemModal').classList.remove('show'); }
  function closeManualModal() { document.getElementById('manualModal').classList.remove('show'); }
  window.openManualModal = function(id) { document.getElementById('manualModal').classList.add('show'); }

  // Inicializar
  renderItems();
</script>
@endsection