@extends('layouts.app')

@section('content')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  :root {
    --bg: #f9fafb;
    --card: #ffffff;
    --ink: #333333;
    --muted: #888888;
    --line: #ebebeb;
    --blue: #007aff;
    --blue-soft: #e6f0ff;
    --success: #15803d;
    --success-soft: #e6ffe6;
    --danger: #ff4a4a;
    --danger-soft: #ffebeb;
    --warning: #a16207;
    --warning-soft: #fff7d6;
  }

  * { box-sizing: border-box; }

  .quote-page {
    min-height: 100vh;
    background: var(--bg);
    font-family: 'Quicksand', sans-serif;
    color: var(--ink);
    padding: 34px 20px 60px;
  }

  .quote-wrap {
    max-width: 1180px;
    margin: 0 auto;
  }

  .topbar {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 24px;
    margin-bottom: 28px;
  }

  .back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #777;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 34px;
  }

  .back-link:hover { color: var(--blue); }

  .quote-code {
    font-size: 12px;
    color: var(--muted);
    font-weight: 700;
    letter-spacing: .13em;
    text-transform: uppercase;
    margin-bottom: 9px;
  }

  .quote-title {
    margin: 0;
    color: #111;
    font-size: 30px;
    line-height: 1.1;
    font-weight: 700;
  }

  .quote-subtitle {
    margin: 10px 0 0;
    color: var(--muted);
    font-size: 15px;
    font-weight: 500;
  }

  .actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
    padding-top: 68px;
  }

  .btn {
    border: 1px solid transparent;
    border-radius: 14px;
    min-height: 40px;
    padding: 0 15px;
    background: transparent;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-family: 'Quicksand', sans-serif;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: .2s ease;
    white-space: nowrap;
  }

  .btn:active { transform: scale(.98); }

  .btn-primary {
    background: var(--blue);
    border-color: var(--blue);
    color: #fff;
    box-shadow: 0 8px 18px rgba(0,122,255,.12);
  }

  .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px rgba(0,122,255,.18);
  }

  .btn-ghost {
    background: #fff;
    border-color: var(--line);
    color: #666;
  }

  .btn-ghost:hover { background: #f9fafb; }

  .btn-soft {
    background: var(--blue-soft);
    color: var(--blue);
  }

  .btn-soft:hover { background: #dceaff; }

  .btn-outline {
    background: #fff;
    border-color: var(--blue);
    color: var(--blue);
  }

  .btn-outline:hover { background: var(--blue-soft); }

  .btn-success {
    color: var(--success);
    background: #fff;
    border-color: rgba(21,128,61,.35);
  }

  .btn-success:hover { background: var(--success-soft); }

  .btn-danger {
    color: var(--danger);
    background: #fff;
    border-color: rgba(255,74,74,.35);
  }

  .btn-danger:hover { background: var(--danger-soft); }

  .btn-warning {
    color: var(--warning);
    background: #fff;
    border-color: rgba(161,98,7,.35);
  }

  .btn-warning:hover { background: var(--warning-soft); }

  .btn-small {
    min-height: 38px;
    padding: 0 13px;
    font-size: 13px;
    border-radius: 13px;
  }

  .btn[disabled] {
    opacity: .55;
    cursor: not-allowed;
  }

  .notice {
    display: none;
    align-items: center;
    gap: 12px;
    border-radius: 14px;
    border: 1px solid #facc15;
    background: #fffbeb;
    color: #945d00;
    padding: 15px 18px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 28px;
  }

  .notice.show { display: flex; }

  .notice-dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: #c69200;
  }

  .summary-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 1px;
    background: var(--line);
    border: 1px solid var(--line);
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 38px;
  }

  .summary-cell {
    background: #fff;
    text-align: center;
    padding: 18px 12px 16px;
  }

  .summary-value {
    font-size: 19px;
    font-weight: 700;
    color: #111;
  }

  .summary-label {
    margin-top: 8px;
    color: var(--muted);
    font-size: 12px;
    font-weight: 600;
  }

  .text-success { color: var(--success); }
  .text-danger { color: var(--danger); }
  .text-blue { color: var(--blue); }

  .global-margin {
    display: flex;
    justify-content: flex-end;
    align-items: end;
    gap: 10px;
    margin-bottom: 24px;
    flex-wrap: wrap;
  }

  .global-margin label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: var(--muted);
    letter-spacing: .1em;
    text-transform: uppercase;
    margin-bottom: 7px;
  }

  .input {
    width: 100%;
    height: 40px;
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 0 12px;
    outline: none;
    font-family: 'Quicksand', sans-serif;
    font-weight: 600;
    color: #111;
    background: #fff;
    transition: .2s ease;
  }

  .input:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .tabs {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 24px;
  }

  .tab-btn {
    border: 1px solid transparent;
    background: #f3f4f6;
    color: #777;
    border-radius: 14px;
    padding: 12px 16px;
    font-size: 14px;
    font-weight: 700;
    font-family: 'Quicksand', sans-serif;
    cursor: pointer;
    transition: .2s ease;
  }

  .tab-btn.active {
    background: var(--blue);
    color: #fff;
  }

  .items-list {
    display: grid;
    gap: 12px;
  }

  .item-card {
    background: #fff;
    border: 1px solid var(--line);
    border-left: 3px solid var(--line);
    border-radius: 14px;
    overflow: hidden;
    transition: .2s ease;
  }

  .item-card.dragging {
    opacity: .55;
    transform: scale(.995);
  }

  .item-card.status-exact { border-left-color: #22c55e; }
  .item-card.status-similar { border-left-color: var(--blue); }
  .item-card.status-not_found { border-left-color: var(--danger); }

  .item-main {
    display: grid;
    grid-template-columns: 28px 34px minmax(0, 1fr) auto auto auto;
    align-items: center;
    gap: 14px;
    padding: 18px 24px;
    cursor: pointer;
  }

  .drag-handle {
    width: 26px;
    height: 32px;
    border: 0;
    background: transparent;
    cursor: grab;
    color: #999;
    font-size: 17px;
    line-height: 1;
  }

  .drag-handle:active { cursor: grabbing; }

  .item-index {
    color: #777;
    font-size: 12px;
    font-weight: 700;
    text-align: center;
  }

  .item-name {
    margin: 0;
    color: #111;
    font-size: 16px;
    font-weight: 700;
    line-height: 1.35;
  }

  .item-meta {
    color: var(--muted);
    font-size: 13px;
    margin-top: 6px;
    font-weight: 500;
  }

  .badge {
    display: inline-flex;
    align-items: center;
    min-height: 28px;
    padding: 6px 13px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
  }

  .badge-success { background: var(--success-soft); color: var(--success); }
  .badge-danger { background: var(--danger-soft); color: var(--danger); }
  .badge-info { background: var(--blue-soft); color: var(--blue); }
  .badge-warning { background: var(--warning-soft); color: var(--warning); }
  .badge-muted { background: #f3f4f6; color: #777; }

  .money-row {
    display: flex;
    gap: 20px;
    font-size: 13px;
    color: var(--muted);
    white-space: nowrap;
  }

  .money-row strong {
    color: #111;
    font-weight: 700;
  }

  .chevron {
    color: var(--muted);
    transition: .2s ease;
  }

  .item-card.open .chevron {
    transform: rotate(180deg);
  }

  .item-details {
    display: none;
    border-top: 1px solid var(--line);
    background: rgba(249,250,251,.65);
    padding: 24px 26px 28px;
  }

  .item-card.open .item-details {
    display: block;
  }

  .section { margin-bottom: 22px; }
  .section:last-child { margin-bottom: 0; }

  .section-title {
    color: var(--muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .14em;
    text-transform: uppercase;
    margin-bottom: 10px;
  }

  .result-card {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 10px;
  }

  .result-title {
    color: #111;
    font-size: 15px;
    line-height: 1.4;
    font-weight: 700;
  }

  .result-meta {
    color: var(--muted);
    font-size: 13px;
    margin-top: 6px;
    line-height: 1.6;
  }

  .external-box {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 10px;
  }

  .warning-line {
    color: var(--warning);
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 7px;
    margin-top: 8px;
  }

  .action-row {
    display: flex;
    gap: 9px;
    flex-wrap: wrap;
    align-items: center;
  }

  .edit-form {
    display: none;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1.4fr 1.6fr;
    gap: 12px;
    margin-top: 22px;
  }

  .edit-form.show { display: grid; }

  .field label {
    display: block;
    color: var(--muted);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .11em;
    margin-bottom: 7px;
  }

  .modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0,0,0,.18);
    backdrop-filter: blur(6px);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 18px;
  }

  .modal-backdrop.show { display: flex; }

  .modal {
    width: min(820px, 100%);
    max-height: 86vh;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 24px 80px rgba(0,0,0,.12);
  }

  .modal-head {
    padding: 18px 22px;
    border-bottom: 1px solid var(--line);
    display: flex;
    justify-content: space-between;
    gap: 18px;
    align-items: flex-start;
  }

  .modal-title {
    margin: 0;
    color: #111;
    font-size: 17px;
    font-weight: 700;
  }

  .modal-subtitle {
    margin: 5px 0 0;
    color: var(--muted);
    font-size: 13px;
  }

  .modal-body {
    padding: 18px 22px 22px;
    overflow-y: auto;
    max-height: 68vh;
  }

  .modal-tabs {
    display: flex;
    gap: 8px;
    margin: 14px 0;
  }

  .modal-result {
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 14px;
    display: flex;
    gap: 14px;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
  }

  .loader {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(0,122,255,.25);
    border-top-color: var(--blue);
    border-radius: 999px;
    animation: spin .8s linear infinite;
  }

  @keyframes spin { to { transform: rotate(360deg); } }

  @media (max-width: 1100px) {
    .topbar { flex-direction: column; }
    .actions { padding-top: 0; justify-content: flex-start; }
    .summary-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .item-main { grid-template-columns: 28px 30px minmax(0,1fr) auto; }
    .money-row { grid-column: 3 / -1; flex-wrap: wrap; }
    .edit-form { grid-template-columns: repeat(2, minmax(0,1fr)); }
  }

  @media (max-width: 680px) {
    .quote-page { padding: 24px 14px 40px; }
    .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .item-main { padding: 16px; gap: 10px; }
    .money-row { grid-column: 1 / -1; }
    .item-details { padding: 18px 16px; }
    .edit-form { grid-template-columns: 1fr; }
    .btn { width: 100%; }
  }
</style>

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
@endphp

<div class="quote-page">
  <div class="quote-wrap">
    <div class="topbar">
      <div>
        <a href="{{ route('propuestas-comerciales.index') }}" class="back-link">← Volver</a>

        <div class="quote-code">
          {{ $propuestaComercial->folio ?: ('TEOA' . str_pad((string) $propuestaComercial->id, 8, '0', STR_PAD_LEFT)) }}
        </div>

        <h1 class="quote-title">
          {{ $propuestaComercial->titulo ?: ('COT-' . strtoupper(substr(md5($propuestaComercial->id . $propuestaComercial->created_at), 0, 8))) }}
        </h1>

        <p class="quote-subtitle">
          <span id="itemsCountText">{{ $summaryPayload['total_items'] }}</span> partidas analizadas por IA
        </p>
      </div>

      <div class="actions">
        <button class="btn btn-ghost" type="button" id="btnOpenAddItem">＋ Agregar</button>

        <button class="btn btn-outline" type="button" id="btnSuggestAll">
          ◎ Buscar coincidencias de todos
        </button>

        <a href="{{ route('propuestas-comerciales.export.word', $propuestaComercial) }}" class="btn btn-primary">
          ▣ Aprobar
        </a>
      </div>
    </div>

    <div id="noticeBox" class="notice">
      <span class="notice-dot"></span>
      <span><strong id="noticeCount">0 partidas</strong> no encontradas en catálogo — usa “Buscar en internet” para encontrar alternativas.</span>
    </div>

    <div class="summary-grid">
      <div class="summary-cell"><div class="summary-value text-success" id="sumExact">0</div><div class="summary-label">Exactos</div></div>
      <div class="summary-cell"><div class="summary-value text-blue" id="sumSimilar">0</div><div class="summary-label">Similares</div></div>
      <div class="summary-cell"><div class="summary-value text-danger" id="sumNotFound">0</div><div class="summary-label">No encontrados</div></div>
      <div class="summary-cell"><div class="summary-value" id="sumSale">$0</div><div class="summary-label">Subtotal venta</div></div>
      <div class="summary-cell"><div class="summary-value text-success" id="sumProfit">$0</div><div class="summary-label">Utilidad</div></div>
      <div class="summary-cell"><div class="summary-value" id="sumMargin">0%</div><div class="summary-label">Margen</div></div>
    </div>

    <div class="global-margin">
      <div>
        <label>Margen global %</label>
        <input class="input" id="globalMarginInput" type="number" step="0.01" value="{{ $propuestaComercial->porcentaje_utilidad ?: 25 }}" style="width:150px;">
      </div>

      <button class="btn btn-ghost" type="button" id="btnSaveGlobalMargin">Guardar margen global</button>
      <button class="btn btn-outline" type="button" id="btnApplyGlobalMargin">Aplicar a partidas</button>
    </div>

    <div class="tabs">
      <button class="tab-btn active" type="button" data-filter="all">Todos <span id="tabAll">0</span></button>
      <button class="tab-btn" type="button" data-filter="exact">Exactos <span id="tabExact">0</span></button>
      <button class="tab-btn" type="button" data-filter="not_found">No encontrados <span id="tabNotFound">0</span></button>
    </div>

    <div class="items-list" id="itemsList"></div>
  </div>
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
  };

  let items = @json($itemsPayload);
  let summary = @json($summaryPayload);
  let currentFilter = 'all';
  let manualItemId = null;
  let manualTab = 'catalog';
  let manualSearchTimer = null;
  let manualLastQuery = '';

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

    const data = await response.json().catch(() => null);

    if (!response.ok || !data || data.ok === false) {
      throw new Error(data?.message || 'Error procesando la solicitud.');
    }

    return data;
  }

  function urlFor(template, id) {
    return template.replace('__ID__', id);
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
    document.getElementById('sumExact').textContent = summary.exact || 0;
    document.getElementById('sumSimilar').textContent = summary.similar || 0;
    document.getElementById('sumNotFound').textContent = summary.not_found || 0;
    document.getElementById('sumSale').textContent = money(summary.subtotal_sale);
    document.getElementById('sumProfit').textContent = money(summary.profit);
    document.getElementById('sumMargin').textContent = `${summary.margin || 0}%`;

    document.getElementById('tabAll').textContent = summary.total_items || items.length;
    document.getElementById('tabExact').textContent = summary.exact || 0;
    document.getElementById('tabNotFound').textContent = summary.not_found || 0;
    document.getElementById('itemsCountText').textContent = summary.total_items || items.length;

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
    const filtered = items.filter(item => currentFilter === 'all' || item.status_key === currentFilter);

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
      <div class="item-card ${statusCardClass(item)}" data-id="${item.id}" draggable="true">
        <div class="item-main" onclick="toggleItem(${item.id})">
          <button class="drag-handle" type="button" title="Mover posición" onclick="event.stopPropagation()">⠿</button>
          <div class="item-index">${idx + 1}</div>

          <div>
            <h3 class="item-name">${escapeHtml(item.descripcion_original || 'Producto sin descripción')}</h3>
            <div class="item-meta">
              ${qty} ${escapeHtml(item.unidad_solicitada || 'pz')}
              ${item.producto_seleccionado?.brand ? ' · ' + escapeHtml(item.producto_seleccionado.brand) : ''}
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
    if (idx >= 0) items[idx] = item;
  }

  function toggleItem(id) {
    const card = document.querySelector(`.item-card[data-id="${id}"]`);
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

      const card = document.querySelector(`.item-card[data-id="${id}"]`);
      if (card) card.classList.add('open');
    } catch (e) {
      alert(e.message);
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

      const card = document.querySelector(`.item-card[data-id="${itemId}"]`);
      if (card) card.classList.add('open');
    } catch (e) {
      alert(e.message);
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

      const card = document.querySelector(`.item-card[data-id="${id}"]`);
      if (card) card.classList.add('open');
    } catch (e) {
      alert(e.message);
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

      const card = document.querySelector(`.item-card[data-id="${id}"]`);
      if (card) card.classList.add('open');
    } catch (e) {
      alert(e.message);
    }
  }

  async function suggestAll() {
    const button = document.getElementById('btnSuggestAll');
    const old = button.innerHTML;

    button.disabled = true;
    button.innerHTML = '<span class="loader"></span> Buscando todos...';

    try {
      const data = await ajax(routes.suggestAll, { method: 'POST', body: '{}' });
      items = data.items || items;
      summary = data.summary || summary;
      renderItems();
    } catch (e) {
      alert(e.message);
    } finally {
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

      items = data.items || items;
      summary = data.summary || summary;
      renderItems();
    } catch (e) {
      alert(e.message);
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
        statusBox.textContent = `${(data.internet || []).length} referencias externas encontradas`;
        renderManualInternet(data.internet || []);
      } else {
        statusBox.textContent = `${(data.products || []).length} productos similares encontrados`;
        renderManualCatalog(data.products || []);
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

    box.innerHTML = products.map(p => `
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

        <button class="btn btn-primary btn-small" type="button" onclick='useManualCatalog(${JSON.stringify(p)})'>
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

    box.innerHTML = results.map(r => `
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

        <button class="btn btn-primary btn-small" type="button" onclick='useManualInternet(${JSON.stringify(r)})'>
          Usar
        </button>
      </div>
    `).join('');
  }

  async function useManualCatalog(product) {
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

      const card = document.querySelector(`.item-card[data-id="${manualItemId}"]`);
      if (card) card.classList.add('open');
    } catch (e) {
      alert(e.message);
    }
  }

  async function useManualInternet(result) {
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

      const card = document.querySelector(`.item-card[data-id="${manualItemId}"]`);
      if (card) card.classList.add('open');
    } catch (e) {
      alert(e.message);
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

      items = data.items || items;
      summary = data.summary || summary;

      closeAddItemModal();
      form.reset();

      renderItems();
    } catch (e) {
      alert(e.message);
    } finally {
      submit.disabled = false;
      submit.innerHTML = old;
    }
  }

  function bindDragEvents() {
    document.querySelectorAll('.item-card').forEach(card => {
      card.addEventListener('dragstart', () => {
        card.classList.add('dragging');
      });

      card.addEventListener('dragend', () => {
        card.classList.remove('dragging');
        saveOrder();
      });

      card.addEventListener('dragover', (e) => {
        e.preventDefault();

        const list = document.getElementById('itemsList');
        const dragging = document.querySelector('.dragging');
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
    const ids = [...document.querySelectorAll('#itemsList .item-card')]
      .map(card => Number(card.dataset.id));

    if (!ids.length) return;

    try {
      const data = await ajax(routes.reorder, {
        method: 'POST',
        body: JSON.stringify({ items: ids })
      });

      items = data.items || items;
      summary = data.summary || summary;
      renderItems();
    } catch (e) {
      alert(e.message);
    }
  }

  document.getElementById('btnSuggestAll').addEventListener('click', suggestAll);
  document.getElementById('btnOpenAddItem').addEventListener('click', openAddItemModal);
  document.getElementById('btnSaveGlobalMargin').addEventListener('click', () => saveGlobalMargin(false));
  document.getElementById('btnApplyGlobalMargin').addEventListener('click', () => saveGlobalMargin(true));

  document.getElementById('manualQueryInput').addEventListener('input', () => {
    manualLastQuery = '';
    scheduleManualSearch(420);
  });

  document.getElementById('manualQueryInput').addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      manualLastQuery = '';
      scheduleManualSearch(10);
    }
  });

  document.getElementById('manualTabCatalog').addEventListener('click', () => {
    manualTab = 'catalog';
    manualLastQuery = '';
    document.getElementById('manualTabCatalog').classList.add('active');
    document.getElementById('manualTabInternet').classList.remove('active');
    scheduleManualSearch(10);
  });

  document.getElementById('manualTabInternet').addEventListener('click', () => {
    manualTab = 'internet';
    manualLastQuery = '';
    document.getElementById('manualTabInternet').classList.add('active');
    document.getElementById('manualTabCatalog').classList.remove('active');
    scheduleManualSearch(10);
  });

  document.querySelectorAll('.tab-btn[data-filter]').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn[data-filter]').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentFilter = btn.dataset.filter;
      renderItems();
    });
  });

  renderItems();
</script>
@endsection