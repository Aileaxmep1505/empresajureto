@extends('layouts.app')
@section('content_class', 'content--flush')
@section('content')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  .jureto-quote-page {
    --bg: #ffffff;
    --card: #ffffff;
    --ink: #171717;
    --muted: #737373;
    --line: #ececec;
    --blue: #007aff;
    --blue-soft: #eef6ff;
    --success: #15803d;
    --success-soft: #ecfdf3;
    --danger: #dc2626;
    --danger-soft: #fef2f2;
    --warning: #a16207;
    --warning-soft: #fff7d6;

    min-height: 100vh;
    width: 100%;
    background: #ffffff;
    color: var(--ink);
    font-family: 'Quicksand', sans-serif;
    padding: 24px 0 48px;
  }

  .jureto-quote-page,
  .jureto-quote-page * {
    box-sizing: border-box;
  }

  .jureto-quote-page a,
  .jureto-quote-page button,
  .jureto-quote-page input,
  .jureto-quote-page textarea {
    font-family: 'Quicksand', sans-serif;
  }

  .jureto-quote-page .quote-wrap {
    width: min(96vw, 1560px);
    margin: 0 auto;
  }

  .jureto-quote-page .back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #6b7280;
    text-decoration: none;
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 18px;
    transition: .18s ease;
  }

  .jureto-quote-page .back-link:hover {
    color: var(--blue);
  }

  .jureto-quote-page .topbar {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 20px;
    align-items: start;
    margin-bottom: 22px;
    padding: 0;
    background: transparent;
    border: 0;
    border-radius: 0;
    box-shadow: none;
  }

  .jureto-quote-page .topbar-main {
    min-width: 0;
    max-width: 1120px;
  }

  .jureto-quote-page .quote-code {
    font-size: 11px;
    color: #8a8a8a;
    font-weight: 700;
    letter-spacing: .18em;
    text-transform: uppercase;
    margin-bottom: 8px;
  }

  .jureto-quote-page .quote-title {
    margin: 0;
    color: #111827;
    font-size: clamp(18px, 2vw, 28px);
    line-height: 1.24;
    font-weight: 700;
    letter-spacing: -.03em;
    max-width: 100%;
    word-break: break-word;
    overflow-wrap: anywhere;
  }

  .jureto-quote-page .quote-subtitle {
    margin: 10px 0 0;
    color: #8b8b8b;
    font-size: 14px;
    font-weight: 600;
  }

  .jureto-quote-page .actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: stretch;
    width: 280px;
    flex: 0 0 280px;
  }

  .jureto-quote-page .btn {
    appearance: none;
    border: 1px solid transparent;
    border-radius: 16px;
    min-height: 46px;
    padding: 0 16px;
    background: transparent;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: .18s ease;
    white-space: nowrap;
  }

  .jureto-quote-page .btn:active {
    transform: scale(.985);
  }

  .jureto-quote-page .btn-icon {
    width: 18px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 18px;
  }

  .jureto-quote-page .btn-icon svg {
    width: 18px;
    height: 18px;
    stroke: currentColor;
  }

  .jureto-quote-page .btn-primary {
    background: var(--blue);
    border-color: var(--blue);
    color: #fff;
    box-shadow: 0 10px 22px rgba(0, 122, 255, .14);
  }

  .jureto-quote-page .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 28px rgba(0, 122, 255, .18);
  }

  .jureto-quote-page .btn-ghost {
    background: #fff;
    border-color: var(--line);
    color: #5b6470;
  }

  .jureto-quote-page .btn-ghost:hover {
    background: #fafafa;
    border-color: #dddddd;
  }

  .jureto-quote-page .btn-soft {
    background: var(--blue-soft);
    color: var(--blue);
  }

  .jureto-quote-page .btn-soft:hover {
    background: #e4f0ff;
  }

  .jureto-quote-page .btn-outline {
    background: #fff;
    border-color: rgba(0,122,255,.35);
    color: var(--blue);
  }

  .jureto-quote-page .btn-outline:hover {
    background: var(--blue-soft);
  }

  .jureto-quote-page .btn-success {
    color: var(--success);
    background: #fff;
    border-color: rgba(21,128,61,.28);
  }

  .jureto-quote-page .btn-success:hover {
    background: var(--success-soft);
  }

  .jureto-quote-page .btn-danger {
    color: var(--danger);
    background: #fff;
    border-color: rgba(220,38,38,.24);
  }

  .jureto-quote-page .btn-danger:hover {
    background: var(--danger-soft);
  }

  .jureto-quote-page .btn-warning {
    color: var(--warning);
    background: #fff;
    border-color: rgba(161,98,7,.28);
  }

  .jureto-quote-page .btn-warning:hover {
    background: var(--warning-soft);
  }

  .jureto-quote-page .btn-small {
    min-height: 38px;
    padding: 0 13px;
    font-size: 13px;
    border-radius: 13px;
  }

  .jureto-quote-page .btn[disabled] {
    opacity: .55;
    cursor: not-allowed;
  }

  .jureto-quote-page .notice {
    display: none;
    align-items: center;
    gap: 12px;
    border-radius: 14px;
    border: 1px solid #fde68a;
    background: #fffbeb;
    color: #945d00;
    padding: 15px 18px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 22px;
  }

  .jureto-quote-page .notice.show {
    display: flex;
  }

  .jureto-quote-page .notice-dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: #d4a214;
    flex: 0 0 auto;
  }

  .jureto-quote-page .summary-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 26px;
  }

  .jureto-quote-page .summary-cell {
    appearance: none;
    border: 1px solid var(--line);
    min-width: 0;
    background: #fff;
    text-align: center;
    padding: 16px 12px 14px;
    cursor: pointer;
    transition: .2s ease;
    position: relative;
    border-radius: 16px;
    box-shadow: 0 4px 14px rgba(0,0,0,.025);
  }

  .jureto-quote-page .summary-cell:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 22px rgba(0,0,0,.04);
    border-color: rgba(0,122,255,.18);
  }

  .jureto-quote-page .summary-cell.active {
    background: var(--blue);
    border-color: var(--blue);
    box-shadow: 0 12px 24px rgba(0,122,255,.16);
  }

  .jureto-quote-page .summary-cell.active .summary-value,
  .jureto-quote-page .summary-cell.active .summary-label {
    color: #fff !important;
  }

  .jureto-quote-page .summary-value {
    font-size: 18px;
    font-weight: 700;
    color: #111;
  }

  .jureto-quote-page .summary-label {
    margin-top: 8px;
    color: var(--muted);
    font-size: 12px;
    font-weight: 700;
  }

  .jureto-quote-page .text-success { color: var(--success); }
  .jureto-quote-page .text-danger { color: var(--danger); }
  .jureto-quote-page .text-blue { color: var(--blue); }

  .jureto-quote-page .global-margin {
    display: flex;
    justify-content: flex-end;
    align-items: end;
    gap: 10px;
    margin-bottom: 24px;
    flex-wrap: wrap;
  }

  .jureto-quote-page .global-margin label,
  .jureto-quote-page .field label {
    display: block;
    color: var(--muted);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .11em;
    margin-bottom: 7px;
  }

  .jureto-quote-page .input {
    width: 100%;
    height: 42px;
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 0 12px;
    outline: none;
    font-weight: 600;
    color: #111;
    background: #fff;
    transition: .2s ease;
  }

  .jureto-quote-page .input:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .jureto-quote-page .items-list {
    display: grid;
    gap: 12px;
  }

  .jureto-quote-page .item-card {
    background: #fff;
    border: 1px solid var(--line);
    border-left: 3px solid var(--line);
    border-radius: 14px;
    overflow: hidden;
    transition: .2s ease;
    box-shadow: 0 4px 14px rgba(0,0,0,.02);
  }

  .jureto-quote-page .item-card:hover {
    box-shadow: 0 12px 28px rgba(0,0,0,.035);
    transform: translateY(-1px);
  }

  .jureto-quote-page .item-card.dragging {
    opacity: .55;
    transform: scale(.995);
  }

  .jureto-quote-page .item-card.status-exact { border-left-color: #22c55e; }
  .jureto-quote-page .item-card.status-similar { border-left-color: var(--blue); }
  .jureto-quote-page .item-card.status-not_found { border-left-color: var(--danger); }

  .jureto-quote-page .item-main {
    display: grid;
    grid-template-columns: 28px 34px minmax(0, 1fr) auto auto auto;
    align-items: center;
    gap: 14px;
    padding: 18px 22px;
    cursor: pointer;
  }

  .jureto-quote-page .drag-handle {
    width: 26px;
    height: 32px;
    border: 0;
    background: transparent;
    cursor: grab;
    color: #999;
    font-size: 17px;
    line-height: 1;
  }

  .jureto-quote-page .drag-handle:active { cursor: grabbing; }

  .jureto-quote-page .item-index {
    color: #777;
    font-size: 12px;
    font-weight: 700;
    text-align: center;
  }

  .jureto-quote-page .item-name {
    margin: 0;
    color: #111;
    font-size: 16px;
    font-weight: 700;
    line-height: 1.35;
  }

  .jureto-quote-page .item-meta {
    color: var(--muted);
    font-size: 13px;
    margin-top: 6px;
    font-weight: 500;
  }

  .jureto-quote-page .badge {
    display: inline-flex;
    align-items: center;
    min-height: 28px;
    padding: 6px 13px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
  }

  .jureto-quote-page .badge-success { background: var(--success-soft); color: var(--success); }
  .jureto-quote-page .badge-danger { background: var(--danger-soft); color: var(--danger); }
  .jureto-quote-page .badge-info { background: var(--blue-soft); color: var(--blue); }
  .jureto-quote-page .badge-warning { background: var(--warning-soft); color: var(--warning); }
  .jureto-quote-page .badge-muted { background: #f3f4f6; color: #777; }

  .jureto-quote-page .money-row {
    display: flex;
    gap: 18px;
    font-size: 13px;
    color: var(--muted);
    white-space: nowrap;
  }

  .jureto-quote-page .money-row strong {
    color: #111;
    font-weight: 700;
  }

  .jureto-quote-page .chevron {
    color: var(--muted);
    transition: .2s ease;
  }

  .jureto-quote-page .item-card.open .chevron {
    transform: rotate(180deg);
  }

  .jureto-quote-page .item-details {
    display: none;
    border-top: 1px solid var(--line);
    background: #fafafa;
    padding: 24px 26px 28px;
  }

  .jureto-quote-page .item-card.open .item-details {
    display: block;
  }

  .jureto-quote-page .section { margin-bottom: 22px; }
  .jureto-quote-page .section:last-child { margin-bottom: 0; }

  .jureto-quote-page .section-title {
    color: var(--muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .14em;
    text-transform: uppercase;
    margin-bottom: 10px;
  }

  .jureto-quote-page .result-card,
  .jureto-quote-page .external-box {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 10px;
  }

  .jureto-quote-page .result-title {
    color: #111;
    font-size: 15px;
    line-height: 1.4;
    font-weight: 700;
  }

  .jureto-quote-page .result-meta {
    color: var(--muted);
    font-size: 13px;
    margin-top: 6px;
    line-height: 1.6;
  }

  .jureto-quote-page .warning-line {
    color: var(--warning);
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 7px;
    margin-top: 8px;
  }

  .jureto-quote-page .action-row {
    display: flex;
    gap: 9px;
    flex-wrap: wrap;
    align-items: center;
  }

  .jureto-quote-page .edit-form {
    display: none;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1.4fr 1.6fr;
    gap: 12px;
    margin-top: 22px;
  }

  .jureto-quote-page .edit-form.show { display: grid; }

  .jureto-quote-page .modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0,0,0,.16);
    backdrop-filter: blur(6px);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 18px;
  }

  .jureto-quote-page .modal-backdrop.show { display: flex; }

  .jureto-quote-page .modal {
    width: min(820px, 100%);
    max-height: 86vh;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 24px 80px rgba(0,0,0,.12);
  }

  .jureto-quote-page .modal-head {
    padding: 18px 22px;
    border-bottom: 1px solid var(--line);
    display: flex;
    justify-content: space-between;
    gap: 18px;
    align-items: flex-start;
  }

  .jureto-quote-page .modal-title {
    margin: 0;
    color: #111;
    font-size: 17px;
    font-weight: 700;
  }

  .jureto-quote-page .modal-subtitle {
    margin: 5px 0 0;
    color: var(--muted);
    font-size: 13px;
  }

  .jureto-quote-page .modal-body {
    padding: 18px 22px 22px;
    overflow-y: auto;
    max-height: 68vh;
  }

  .jureto-quote-page .modal-tabs {
    display: flex;
    gap: 8px;
    margin: 14px 0;
  }

  .jureto-quote-page .tab-btn {
    border: 1px solid transparent;
    background: #f3f4f6;
    color: #777;
    border-radius: 14px;
    padding: 12px 16px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s ease;
  }

  .jureto-quote-page .tab-btn.active {
    background: var(--blue);
    color: #fff;
  }

  .jureto-quote-page .modal-result {
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 14px;
    display: flex;
    gap: 14px;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
  }

  .jureto-quote-page .loader {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(0,122,255,.25);
    border-top-color: var(--blue);
    border-radius: 999px;
    animation: juretoSpin .8s linear infinite;
  }

  @keyframes juretoSpin {
    to { transform: rotate(360deg); }
  }

  .jureto-quote-page .process-box {
    display: none;
    border: 1px solid var(--line);
    background: #fff;
    border-radius: 16px;
    padding: 16px 18px;
    margin-bottom: 24px;
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
  }

  .jureto-quote-page .process-box.show { display: block; }

  .jureto-quote-page .process-head {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    align-items: center;
    margin-bottom: 10px;
  }

  .jureto-quote-page .process-title {
    font-size: 14px;
    font-weight: 800;
    color: #111;
  }

  .jureto-quote-page .process-text {
    color: var(--muted);
    font-size: 13px;
    font-weight: 600;
    line-height: 1.5;
  }

  .jureto-quote-page .process-bar {
    height: 10px;
    background: #eef2f7;
    border-radius: 999px;
    overflow: hidden;
    margin-top: 12px;
  }

  .jureto-quote-page .process-fill {
    height: 100%;
    width: 0%;
    background: var(--blue);
    border-radius: 999px;
    transition: width .25s ease;
  }

  .jureto-quote-page .process-box.error {
    border-color: rgba(220,38,38,.28);
    background: var(--danger-soft);
  }

  .jureto-quote-page .process-box.error .process-title {
    color: var(--danger);
  }

  .jureto-quote-page .process-box.success {
    border-color: rgba(21,128,61,.22);
    background: var(--success-soft);
  }

  .jureto-quote-page .process-box.success .process-title {
    color: var(--success);
  }

  .jureto-quote-page .process-errors {
    display: none;
    margin-top: 12px;
    max-height: 180px;
    overflow: auto;
    border-radius: 12px;
    background: rgba(255,255,255,.75);
    padding: 10px 12px;
    color: var(--danger);
    font-size: 12px;
    line-height: 1.5;
    font-weight: 600;
  }

  .jureto-quote-page .process-errors.show {
    display: block;
  }

  @media (max-width: 1300px) {
    .jureto-quote-page .summary-grid {
      grid-template-columns: repeat(4, minmax(0, 1fr));
    }
  }

  @media (max-width: 1100px) {
    .jureto-quote-page .topbar {
      grid-template-columns: 1fr;
    }

    .jureto-quote-page .actions {
      width: 100%;
      flex: 1 1 auto;
      flex-direction: row;
      flex-wrap: wrap;
      justify-content: flex-start;
    }

    .jureto-quote-page .item-main {
      grid-template-columns: 28px 30px minmax(0,1fr) auto;
    }

    .jureto-quote-page .money-row {
      grid-column: 3 / -1;
      flex-wrap: wrap;
    }

    .jureto-quote-page .edit-form {
      grid-template-columns: repeat(2, minmax(0,1fr));
    }
  }

  @media (max-width: 760px) {
    .jureto-quote-page {
      padding: 18px 0 40px;
    }

    .jureto-quote-page .quote-wrap {
      width: calc(100vw - 20px);
    }

    .jureto-quote-page .quote-title {
      font-size: 20px;
      line-height: 1.28;
    }

    .jureto-quote-page .actions {
      flex-direction: column;
    }

    .jureto-quote-page .summary-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 10px;
    }

    .jureto-quote-page .item-main {
      padding: 16px;
      gap: 10px;
    }

    .jureto-quote-page .money-row {
      grid-column: 1 / -1;
    }

    .jureto-quote-page .item-details {
      padding: 18px 16px;
    }

    .jureto-quote-page .edit-form {
      grid-template-columns: 1fr;
    }

    .jureto-quote-page .btn {
      width: 100%;
    }

    .jureto-quote-page .modal-result {
      flex-direction: column;
    }
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
          <span id="itemsCountText">{{ $summaryPayload['total_items'] }}</span> partidas analizadas por IA
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
  let manualCatalogResults = [];
  let manualInternetResults = [];
  let isSuggestingAll = false;

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

      items = data.items || items;
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

      items = data.items || items;
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

      items = data.items || items;
      summary = data.summary || summary;
      renderItems();
    } catch (e) {
      showInlineError(e.message);
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

  document.querySelectorAll('.filter-summary').forEach(btn => {
    btn.addEventListener('click', () => {
      currentFilter = btn.dataset.filter || 'all';
      renderItems();
    });
  });

  renderItems();
</script>
@endsection
