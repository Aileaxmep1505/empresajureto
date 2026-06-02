@extends('layouts.app')
@section('content_class', 'content--flush')
@section('content')

<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #f4f5f7;
    --card: #ffffff;
    --input-bg: #f9fafb;
    --ink-dark: #0f172a;
    --ink: #334155;
    --muted: #64748b;
    --muted-light: #94a3b8;
    --line: #e2e8f0;
    --blue: #007aff;
    --blue-soft: #eff6ff;
    --success: #15803d;
    --success-soft: #f0fdf4;
    --danger: #ef4444;
    --danger-soft: #fef2f2;
    --warning: #c2410c;
    --warning-soft: #fff7ed;
    
    --font-family: 'Quicksand', sans-serif;
    --radius-card: 12px;
    --radius-modal: 12px; 
    --radius-input: 8px;
    --radius-btn: 8px;
  }

  /* Base & Typography */
  .jureto-quote-page {
    font-family: var(--font-family);
    background-color: var(--bg);
    color: var(--ink);
    min-height: 100vh;
    padding: 32px 24px;
  }
  .jureto-quote-page * {
    box-sizing: border-box;
  }
  .quote-wrap {
    max-width: 1100px;
    margin: 0 auto;
  }
  h1, h2, h3, .quote-title {
    color: var(--ink-dark);
    font-weight: 700;
    margin: 0;
  }
  a { text-decoration: none; }

  /* Topbar & Header */
  .back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--muted);
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 24px;
    transition: color 0.2s;
  }
  .back-link:hover { color: var(--ink-dark); }
  .topbar {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 32px;
    flex-wrap: wrap;
    gap: 24px;
  }
  .quote-code {
    font-size: 13px;
    font-weight: 700;
    color: var(--muted);
    letter-spacing: 0.5px;
    margin-bottom: 8px;
  }
  .quote-title {
    font-size: 28px;
    margin-bottom: 8px;
  }
  .quote-subtitle {
    font-size: 14px;
    color: var(--muted);
    margin: 0;
    font-weight: 500;
  }
  .actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
  }

  /* Buttons */
  .btn {
    font-family: var(--font-family);
    font-weight: 700;
    height: 42px;
    padding: 0 16px;
    border-radius: var(--radius-btn);
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 14px;
    transition: all 0.2s ease;
  }
  .btn:hover { transform: translateY(-1px); }
  .btn:active { transform: scale(0.98); }
  
  .btn-primary { background: var(--blue); color: #fff; box-shadow: 0 2px 8px rgba(0, 122, 255, 0.2); }
  .btn-primary:hover { box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3); }
  
  .btn-ghost { background: transparent; color: var(--muted); }
  .btn-ghost:hover { background: var(--input-bg); color: var(--ink-dark); }
  
  .btn-outline { background: transparent; color: var(--ink); border: 1px solid var(--line); }
  .btn-outline:hover { background: var(--input-bg); }
  
  .btn-success { background: var(--success); color: #fff; }
  .btn-danger { background: var(--danger); color: #fff; }
  .btn-warning { background: var(--warning); color: #fff; }
  .btn-soft { background: var(--blue-soft); color: var(--blue); }
  .btn-small { height: 32px; padding: 0 12px; font-size: 13px; }
  
  .btn-icon svg { width: 16px; height: 16px; stroke: currentColor; }

  /* Inputs */
  .input {
    font-family: var(--font-family);
    font-weight: 500;
    font-size: 14px;
    height: 42px;
    padding: 0 14px;
    background: var(--input-bg);
    border: 1px solid var(--line);
    border-radius: var(--radius-input);
    color: var(--ink);
    width: 100%;
    transition: all 0.2s ease;
  }
  textarea.input { height: auto; padding-top: 12px; padding-bottom: 12px; resize: vertical; }
  .input::placeholder { color: var(--muted-light); }
  .input:focus { outline: none; border-color: var(--blue); background: var(--card); box-shadow: 0 0 0 3px var(--blue-soft); }

  /* Cards & Sections */
  .item-card, .process-box, .summary-cell, .notice, .global-margin, .result-card, .external-box {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: var(--radius-card);
    box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
  }
  
  .item-card { margin-bottom: 16px; overflow: hidden; }
  .item-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.04); }
  
  .item-main {
    padding: 24px;
    display: grid;
    grid-template-columns: 24px 32px 1fr auto auto 24px;
    gap: 16px;
    align-items: center;
    cursor: pointer;
  }
  .drag-handle { background: transparent; border: none; color: var(--muted-light); cursor: grab; font-size: 16px; padding: 0; display: flex; align-items: center; justify-content: center; }
  .item-index { font-weight: 700; color: var(--muted-light); font-size: 14px; text-align: center; }
  .item-name { font-size: 16px; margin-bottom: 4px; }
  .item-meta { font-size: 13px; color: var(--muted); font-weight: 500; }
  
  .money-row { display: flex; gap: 24px; font-size: 13px; color: var(--muted); }
  .money-row strong { color: var(--ink-dark); display: block; font-size: 14px; margin-top: 2px; }
  .chevron { color: var(--muted-light); font-size: 18px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
  
  .item-details { display: none; padding: 24px; border-top: 1px solid var(--line); background: var(--input-bg); }
  .item-card.open .item-details { display: block; }
  
  /* Badges */
  .badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
  }
  .badge-success { background: var(--success-soft); color: var(--success); }
  .badge-danger { background: var(--danger-soft); color: var(--danger); }
  .badge-info { background: var(--blue-soft); color: var(--blue); }
  .badge-warning { background: var(--warning-soft); color: var(--warning); }

  /* Summaries */
  .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; margin-bottom: 32px; }
  .summary-cell { padding: 24px 16px; text-align: center; cursor: pointer; border: 1px solid var(--line); }
  .summary-cell:hover, .summary-cell.active { transform: translateY(-2px); border-color: var(--blue); box-shadow: 0 4px 12px rgba(0, 122, 255, 0.08); }
  .summary-value { font-size: 24px; font-weight: 700; color: var(--ink-dark); }
  .summary-label { font-size: 13px; color: var(--muted); margin-top: 6px; font-weight: 600; }
  .text-blue { color: var(--blue); }
  .text-success { color: var(--success); }
  .text-danger { color: var(--danger); }

  /* Modals (Strict Premium Rules) */
  .modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px);
    z-index: 1050;
    display: none;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
  }
  .modal-backdrop.show { display: flex; opacity: 1; }
  .modal {
    background: var(--card);
    border-radius: var(--radius-modal);
    width: 100%;
    max-width: 540px; /* Ancho estándar/delgado */
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    transform: scale(0.95) translateY(15px);
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    display: flex;
    flex-direction: column;
    max-height: 85vh;
  }
  .modal-backdrop.show .modal { transform: scale(1) translateY(0); }
  
  .modal-head {
    padding: 24px;
    border-bottom: 1px solid var(--line);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
  }
  .modal-title { font-size: 18px; margin-bottom: 4px; }
  .modal-subtitle { font-size: 14px; color: var(--muted); margin: 0; font-weight: 500; }
  
  /* Modal Content & Body Scroll Rules */
  .modal-content {
    background: transparent;
    border: none;
    box-shadow: none;
  }
  .modal-body { padding: 24px; }
  .pb-5 { padding-bottom: 3rem !important; }
  
  /* Scrollbar Personalizado */
  .overflow-y-auto { overflow-y: auto !important; }
  ::-webkit-scrollbar { width: 6px; height: 6px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: var(--line); border-radius: 10px; }
  ::-webkit-scrollbar-thumb:hover { background: var(--muted-light); }

  /* Misc UI Components */
  .notice { background: var(--warning-soft); color: var(--warning); border-color: rgba(194, 65, 12, 0.2); padding: 16px 24px; display: none; align-items: center; gap: 12px; margin-bottom: 24px; font-weight: 600; font-size: 14px; }
  .notice.show { display: flex; }
  .notice-dot { width: 8px; height: 8px; background: var(--warning); border-radius: 50%; }
  
  .global-margin { display: flex; align-items: flex-end; gap: 16px; margin-bottom: 24px; padding: 24px; }
  
  .field { display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; width: 100%; }
  .field label { font-size: 13px; font-weight: 700; color: var(--ink); }
  
  .action-row { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 16px; }
  
  .section { margin-bottom: 32px; }
  .section-title { font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted-light); letter-spacing: 0.5px; margin-bottom: 16px; }
  
  .result-card, .external-box { padding: 20px; margin-bottom: 16px; }
  .result-title { font-weight: 700; color: var(--ink-dark); font-size: 15px; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
  .result-meta { font-size: 13px; color: var(--muted); font-weight: 500; line-height: 1.5; }
  
  .modal-tabs { display: flex; gap: 16px; border-bottom: 1px solid var(--line); margin-bottom: 24px; }
  .tab-btn { background: transparent; border: none; padding: 12px 4px; font-family: var(--font-family); font-weight: 700; color: var(--muted); cursor: pointer; border-bottom: 2px solid transparent; transition: all 0.2s; }
  .tab-btn.active { color: var(--blue); border-bottom-color: var(--blue); }
  
  .modal-result { display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid var(--line); }
  .modal-result:last-child { border-bottom: none; }
  
  .edit-form { display: none; padding: 24px; background: var(--card); border: 1px solid var(--line); border-radius: var(--radius-card); margin-top: 16px; }
  .edit-form.show { display: block; }
  
  /* Bootstrap Grid System Equivalent para layouts (Row / Col) */
  .row { display: flex; flex-wrap: wrap; margin-left: -8px; margin-right: -8px; }
  .col, .col-12, .col-6, .col-4, .col-3 { padding-left: 8px; padding-right: 8px; }
  .col-12 { width: 100%; }
  .col-6 { width: 50%; }
  .col-4 { width: 33.333333%; }
  .col-3 { width: 25%; }
  
  /* Process Box */
  .process-box { padding: 24px; margin-bottom: 24px; display: none; }
  .process-box.show { display: block; }
  .process-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
  .process-title { font-weight: 700; color: var(--ink-dark); }
  .process-text { font-size: 13px; color: var(--muted); margin-top: 4px; }
  .process-bar { height: 6px; background: var(--line); border-radius: 999px; overflow: hidden; }
  .process-fill { height: 100%; background: var(--blue); width: 0%; transition: width 0.3s ease; }
  .process-errors { margin-top: 16px; font-size: 13px; color: var(--danger); display: none; }
  .process-errors.show { display: block; }
  
  .loader { display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: #fff; animation: spin 1s ease-in-out infinite; }
  .btn-ghost .loader, .btn-outline .loader { border-color: rgba(0,0,0,0.1); border-top-color: var(--muted); }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* Regla 6: Custom Animated Select Visuals */
  .native-select-hidden { display: none !important; }
  .custom-select-wrapper { position: relative; width: 100%; font-family: var(--font-family); }
  .custom-select-trigger { display: flex; justify-content: space-between; align-items: center; height: 42px; padding: 0 14px; background: var(--input-bg); border: 1px solid var(--line); border-radius: var(--radius-input); color: var(--ink); font-weight: 500; font-size: 14px; cursor: pointer; transition: all 0.2s ease; }
  .custom-select-trigger:hover { border-color: var(--muted-light); }
  .custom-select-trigger.open { border-color: var(--blue); background: var(--card); box-shadow: 0 0 0 3px var(--blue-soft); }
  .custom-select-options { position: absolute; top: 100%; left: 0; right: 0; margin-top: 4px; background: var(--card); border: 1px solid var(--line); border-radius: var(--radius-input); box-shadow: 0 8px 24px rgba(0,0,0,0.1); overflow-y: auto; max-height: 220px; z-index: 100; transform-origin: top; transform: scaleY(0.9); opacity: 0; transition: all 0.25s ease; pointer-events: none; }
  .custom-select-wrapper.open .custom-select-options { transform: scaleY(1); opacity: 1; pointer-events: auto; }
  .custom-select-option { padding: 12px 14px; cursor: pointer; transition: background 0.15s ease; color: var(--ink); font-weight: 500; font-size: 14px; }
  .custom-select-option:hover { background: var(--input-bg); color: var(--blue); }
  .custom-select-option.selected { background: var(--blue-soft); color: var(--blue); font-weight: 700; }
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

  foreach ($propuestaComercial->getRelations() as $relationName => $relationValue) {
      if (!$relationValue) continue;
      if ($relationValue instanceof \Illuminate\Support\Collection) {
          foreach ($relationValue as $index => $relatedModel) {
              foreach ($fieldsForExport as $field) {
                  $decoded = $decodeExportValue(data_get($relatedModel, $field));
                  if (!empty($decoded)) $rawExportPayloads[$relationName . '_' . $index . '_' . $field] = $decoded;
              }
          }
      } else {
          foreach ($fieldsForExport as $field) {
              $decoded = $decodeExportValue(data_get($relationValue, $field));
              if (!empty($decoded)) $rawExportPayloads[$relationName . '_' . $field] = $decoded;
          }
      }
  }
@endphp

<div class="jureto-quote-page">
  <div class="quote-wrap">
    <a href="{{ route('propuestas-comerciales.index') }}" class="back-link">
      <span>←</span>
      <span>Volver a propuestas</span>
    </a>

    <div class="topbar">
      <div>
        <div class="quote-code">{{ $exportFolio }}</div>
        <h1 class="quote-title">{{ $exportTitle }}</h1>
        <p class="quote-subtitle"><span id="itemsCountText">{{ $summaryPayload['total_items'] }}</span> partidas analizadas por IA · Exportación estructurada</p>
      </div>

      <div class="actions">
        <button class="btn btn-ghost" type="button" id="btnOpenAddItem">
          <span class="btn-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg></span>
          <span>Agregar</span>
        </button>

        <button class="btn btn-outline" type="button" id="btnSuggestAll">
          <span class="btn-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.35-4.35"></path><path d="M11 8v6"></path><path d="M8 11h6"></path></svg></span>
          <span>Buscar coincidencias</span>
        </button>

        <button class="btn btn-outline" type="button" id="btnExportExcel">
          <span class="btn-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 13h8"></path><path d="M8 17h8"></path></svg></span>
          <span>Excel</span>
        </button>

        <button class="btn btn-outline" type="button" id="btnExportWord">
          <span class="btn-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 13h8"></path><path d="M8 17h6"></path></svg></span>
          <span>Word</span>
        </button>

        <a href="{{ route('propuestas-comerciales.fallo.show', $propuestaComercial) }}" class="btn btn-warning">Acta de fallo</a>
        <a href="{{ route('propuestas-comerciales.cliente.show', $propuestaComercial) }}" class="btn btn-primary">Aprobar</a>
      </div>
    </div>

    <div id="noticeBox" class="notice">
      <span class="notice-dot"></span>
      <span><strong id="noticeCount">0 partidas</strong> no encontradas en catálogo — usa "Buscar manualmente" para encontrar alternativas.</span>
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
      <div class="field" style="margin: 0; width: auto;">
        <label>Margen global %</label>
        <input class="input" id="globalMarginInput" type="number" step="0.01" value="{{ $propuestaComercial->porcentaje_utilidad ?: 25 }}" style="width:160px;">
      </div>
      <button class="btn btn-ghost" type="button" id="btnSaveGlobalMargin">Guardar margen</button>
      <button class="btn btn-outline" type="button" id="btnApplyGlobalMargin">Aplicar a todas</button>
    </div>

    <div class="items-list" id="itemsList"></div>
  </div>

  <div class="modal-backdrop" id="manualModal">
    <div class="modal">
      <div class="modal-head">
        <div>
          <h2 class="modal-title">Búsqueda manual</h2>
          <p class="modal-subtitle" id="manualSubtitle">Busca por nombre, SKU, marca, color o descripción.</p>
        </div>
        <button class="btn btn-ghost btn-small" type="button" onclick="closeManualModal()">✕</button>
      </div>
      <div class="modal-content d-flex flex-column h-100">
        <div class="modal-body pb-5 overflow-y-auto">
          <div style="position:relative; margin-bottom: 24px;">
            <input class="input" id="manualQueryInput" placeholder="Buscar producto..." autocomplete="off">
            <button type="button" onclick="clearManualSearch()" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); border:0; background:transparent; color:var(--muted); cursor:pointer; font-weight:700;">✕</button>
          </div>

          <div class="modal-tabs">
            <button class="tab-btn active" type="button" id="manualTabCatalog">Catálogo interno</button>
            <button class="tab-btn" type="button" id="manualTabInternet">Internet</button>
          </div>

          <div id="manualSearchStatus" class="result-meta" style="margin-bottom:16px;">Escribe para buscar automáticamente.</div>
          <div id="manualResults"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal-backdrop" id="addItemModal">
    <div class="modal">
      <div class="modal-head">
        <div>
          <h2 class="modal-title">Agregar nueva partida</h2>
          <p class="modal-subtitle">Crea un nuevo producto solicitado manualmente.</p>
        </div>
        <button class="btn btn-ghost btn-small" type="button" onclick="closeAddItemModal()">✕</button>
      </div>
      <form id="addItemForm" onsubmit="storeNewItem(event)" class="modal-content d-flex flex-column h-100">
        <div class="modal-body pb-5 overflow-y-auto">
          <div class="row">
            <div class="col-12">
              <div class="field">
                <label>Producto solicitado</label>
                <input class="input" name="descripcion_original" placeholder="Ej. 100 paquetes de gasas" required>
              </div>
            </div>
            <div class="col-6">
              <div class="field">
                <label>Cantidad</label>
                <input class="input" type="number" step="0.01" name="cantidad_cotizada" value="1" required>
              </div>
            </div>
            <div class="col-6">
              <div class="field">
                <label>Unidad</label>
                <input class="input" name="unidad_solicitada" value="pz">
              </div>
            </div>
            <div class="col-6">
              <div class="field">
                <label>Costo unit.</label>
                <input class="input" type="number" step="0.01" name="costo_unitario" value="0">
              </div>
            </div>
            <div class="col-6">
              <div class="field">
                <label>Margen %</label>
                <input class="input" type="number" step="0.01" name="porcentaje_utilidad" value="{{ $propuestaComercial->porcentaje_utilidad ?: 25 }}">
              </div>
            </div>
          </div>
          <div class="action-row mt-4">
            <button class="btn btn-primary" type="submit">Agregar partida</button>
            <button class="btn btn-ghost" type="button" onclick="closeAddItemModal()">Cancelar</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-backdrop" id="samplesModal">
    <div class="modal">
      <div class="modal-head">
        <div>
          <h2 class="modal-title">Análisis de almacén</h2>
          <p class="modal-subtitle" id="samplesSubtitle">Producto</p>
        </div>
        <button class="btn btn-ghost btn-small" type="button" onclick="closeSamplesModal()">✕</button>
      </div>
      <div class="modal-content d-flex flex-column h-100">
        <div class="modal-body pb-5 overflow-y-auto">
          <div id="samplesStatus" class="result-meta" style="margin-bottom:16px;">Buscando en catálogo...</div>
          <div id="samplesResults"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal-backdrop" id="techSheetsModal">
    <div class="modal">
      <div class="modal-head">
        <div>
          <h2 class="modal-title">Fichas técnicas</h2>
          <p class="modal-subtitle" id="techSubtitle">Producto</p>
        </div>
        <button class="btn btn-ghost btn-small" type="button" onclick="closeTechSheetsModal()">✕</button>
      </div>
      <div class="modal-content d-flex flex-column h-100">
        <div class="modal-body pb-5 overflow-y-auto">
          <div class="modal-tabs">
            <button class="tab-btn active" type="button" id="techTabList" onclick="techShowList()">Vincular existente</button>
            <button class="tab-btn" type="button" id="techTabForm" onclick="techShowCreate()">Crear nueva</button>
          </div>

          <div id="techListPane">
            <input class="input" id="techQueryInput" placeholder="Buscar ficha por nombre, marca..." style="margin-bottom:16px;">
            <div id="techStatus" class="result-meta" style="margin-bottom:16px;"></div>
            <div id="techResults"></div>
          </div>

          <div id="techFormPane" style="display:none;">
            <form id="techForm" onsubmit="submitTechSheet(event)">
              <input type="hidden" name="tech_sheet_id" id="techFormId" value="">
              <div class="row">
                <div class="col-12">
                  <div class="field"><label>Nombre del producto *</label><input class="input" name="product_name" required></div>
                </div>
                <div class="col-6">
                  <div class="field"><label>Marca</label><input class="input" name="brand"></div>
                </div>
                <div class="col-6">
                  <div class="field"><label>Modelo</label><input class="input" name="model"></div>
                </div>
                <div class="col-6">
                  <div class="field"><label>Referencia</label><input class="input" name="reference"></div>
                </div>
                <div class="col-6">
                  <div class="field"><label>Partida</label><input class="input" name="partida_number"></div>
                </div>
                <div class="col-12">
                  <div class="field"><label>Descripción</label><textarea class="input" name="user_description" rows="3"></textarea></div>
                </div>
                <div class="col-12">
                  <div class="field"><label>Imagen (opcional)</label><input class="input" type="file" name="image" accept="image/*" style="padding:8px;"></div>
                </div>
              </div>
              <div class="action-row">
                <button class="btn btn-primary" type="submit">Guardar ficha</button>
                <button class="btn btn-ghost" type="button" onclick="techShowList()">Cancelar</button>
              </div>
            </form>
          </div>
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

  let samplesItemId = null;
  let techItemId = null;
  let techSheetsCache = [];
  let currentLinkedSheetId = null;

  function money(n) {
    return Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 });
  }

  function escapeHtml(value) {
    return String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
  }

  async function ajax(url, options = {}) {
    const response = await fetch(url, {
      ...options,
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json', ...(options.headers || {}) }
    });
    const rawText = await response.text();
    let data = null;
    try { data = rawText ? JSON.parse(rawText) : null; } catch (error) {}
    if (!response.ok || !data || data.ok === false) {
      throw new Error(data?.message || 'Error procesando la solicitud.');
    }
    return data;
  }

  function urlFor(template, id) { return template.replace('__ID__', id); }

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

  function isManualExternalChosen(item) { return !!(item.manual_external_link || item.manual_external_supplier); }
  function isCatalogAccepted(item) { return item.ui_status === 'accepted_item'; }

  function getSelectedCatalogProduct(item) {
    const selMatch = (item.matches || []).find(m => m.seleccionado);
    if (selMatch && selMatch.product) return { product: selMatch.product, score: Number(selMatch.score || 0) };
    if (item.producto_seleccionado) return { product: item.producto_seleccionado, score: Number(item.match_score || 0) };
    return null;
  }

  function showProcessBox(type, title, text, done = 0, total = 0, errors = []) {
    const box = document.getElementById('processBox');
    if (!box) return;
    box.className = 'process-box show' + (type ? ' ' + type : '');
    document.getElementById('processTitle').textContent = title;
    document.getElementById('processText').textContent = text;
    document.getElementById('processCount').textContent = `${done}/${total}`;
    document.getElementById('processFill').style.width = (total > 0 ? Math.round((done / total) * 100) : 0) + '%';
    const errorsEl = document.getElementById('processErrors');
    if (errors.length) {
      errorsEl.classList.add('show');
      errorsEl.innerHTML = errors.slice(0, 30).map(e => `<div>• ${escapeHtml(e)}</div>`).join('');
      if (errors.length > 30) errorsEl.innerHTML += `<div>• Y ${errors.length - 30} errores más...</div>`;
    } else {
      errorsEl.classList.remove('show');
      errorsEl.innerHTML = '';
    }
  }

  function showInlineError(message) {
    showProcessBox('error', 'Error', message || 'Ocurrió un error procesando la solicitud.', 1, 1, []);
    document.getElementById('processBox')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function hideProcessBox() { document.getElementById('processBox')?.classList.remove('show'); }

  function statusLabel(item) {
    if (item.ui_status === 'accepted_item') return { text: 'Aceptado', cls: 'badge-success' };
    if (item.ui_status === 'manual_review') return { text: 'Revisión', cls: 'badge-warning' };
    if (item.ui_status === 'rejected_item') return { text: 'Rechazado', cls: 'badge-danger' };
    if (item.status_key === 'exact') return { text: 'Exacto', cls: 'badge-success' };
    if (item.status_key === 'similar') return { text: 'Similar', cls: 'badge-info' };
    return { text: 'No encontrado', cls: 'badge-danger' };
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

    document.querySelectorAll('.filter-summary').forEach((btn) => btn.classList.toggle('active', btn.dataset.filter === currentFilter));
    
    const notice = document.getElementById('noticeBox');
    if ((summary.not_found || 0) > 0) {
      document.getElementById('noticeCount').textContent = `${summary.not_found} partidas`;
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
    // Rule 6 Initialization
    initCustomSelects();
  }

  function renderItemCard(item, idx) {
    const badge = statusLabel(item);
    const qty = Number(item.cantidad_cotizada || item.cantidad_maxima || item.cantidad_minima || 1);
    const cost = Number(item.costo_unitario || 0);
    const price = Number(item.precio_unitario || 0);
    const subtotal = Number(item.subtotal || price * qty);

    return `
      <div class="item-card" data-id="${item.id}" draggable="${currentFilter === 'all' ? 'true' : 'false'}">
        <div class="item-main" onclick="toggleItem(${item.id})">
          <button class="drag-handle" type="button" title="Mover posición" onclick="event.stopPropagation()">⠿</button>
          <div class="item-index">${idx + 1}</div>
          <div>
            <h3 class="item-name">${escapeHtml(item.descripcion_original || 'Producto sin descripción')}</h3>
            <div class="item-meta">
              ${qty} ${escapeHtml(item.unidad_solicitada || 'pz')}
              ${item.producto_seleccionado?.brand ? ' · ' + escapeHtml(item.producto_seleccionado.brand) : ''}
              ${item.tech_sheet_id ? ' · <span style="color:var(--success); font-weight:700;">📄 Ficha</span>' : ''}
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
    if (isManualExternalChosen(item)) return '';
    if (isCatalogAccepted(item)) {
      const sel = getSelectedCatalogProduct(item);
      if (sel) return `
        <div class="section">
          <div class="section-title">Catálogo (Seleccionado)</div>
          <div class="result-card" style="border-color:var(--success);">
            <div class="result-title">${escapeHtml(sel.product.name)} <span class="badge badge-success">Aceptado</span></div>
            <div class="result-meta">SKU: ${escapeHtml(sel.product.sku || '—')} · Stock: ${sel.product.stock ?? '—'}</div>
          </div>
        </div>`;
    }
    if (!item.matches?.length && !item.producto_seleccionado) return `<div class="section"><div class="section-title">Catálogo</div><div class="result-meta">N/A</div></div>`;
    if (item.matches?.length) return `
      <div class="section">
        <div class="section-title">Coincidencias en catálogo</div>
        ${item.matches.map((m, i) => `
          <div class="result-card">
            <div class="result-title">${escapeHtml(m.product?.name || '—')}</div>
            <div class="result-meta">SKU: ${escapeHtml(m.product?.sku || '—')} · Stock: ${m.product?.stock ?? '—'} · ${Number(m.score || 0).toFixed(0)}%</div>
            <div class="action-row mt-2">
              <button class="btn btn-outline btn-small" type="button" onclick="selectMatch(${item.id}, ${m.id})">Usar esta</button>
            </div>
          </div>
        `).join('')}
      </div>`;
    return `<div class="section"><div class="section-title">Catálogo</div><div class="result-title">${escapeHtml(item.producto_seleccionado?.name)}</div></div>`;
  }

  function renderManualExternal(item) {
    if (!item.manual_external_supplier && !item.manual_external_link && !item.manual_catalog_product_name) return '';
    return `
      <div class="section">
        <div class="external-box">
          <div class="section-title">Referencia externa</div>
          <div class="result-title">${escapeHtml(item.manual_external_supplier || item.manual_catalog_product_name || 'Proveedor')}</div>
          ${item.manual_external_link ? `<div class="mt-2"><a href="${escapeHtml(item.manual_external_link)}" target="_blank" class="btn btn-outline btn-small">↗ Enlace</a></div>` : ''}
        </div>
      </div>`;
  }

  function renderExternalSection(item) {
    if (isCatalogAccepted(item) || isManualExternalChosen(item)) return '';
    if (!item.external_matches?.length) return item.status_key === 'not_found' ? '<div class="section"><div class="result-meta">Sin opciones en internet.</div></div>' : '';
    return `
      <div class="section">
        <div class="section-title">Internet</div>
        ${item.external_matches.map(e => `
          <div class="external-box">
            <div class="result-title">${escapeHtml(e.title)}</div>
            <div class="result-meta">${escapeHtml(e.source || 'Internet')} · Score ${Number(e.score || 0).toFixed(0)}%</div>
            <div class="mt-2"><a class="btn btn-outline btn-small" href="${escapeHtml(e.url)}" target="_blank">↗ Enlace</a></div>
          </div>
        `).join('')}
      </div>`;
  }

  function renderTechSheetLinked(item) {
    if (!item.tech_sheet_id) return '';
    const pdfUrl = urlFor(routes.techSheetPdf, item.tech_sheet_id);
    return `
      <div class="section">
        <div class="section-title">Ficha técnica</div>
        <div class="result-card">
          <div class="result-title">📄 ${escapeHtml(item.tech_sheet_name || 'Ficha')}</div>
          <div class="action-row">
            <a class="btn btn-outline btn-small" target="_blank" href="${pdfUrl}">↗ Abrir</a>
            <button class="btn btn-ghost btn-small" type="button" onclick="openTechSheetsModal(${item.id})">Cambiar</button>
          </div>
        </div>
      </div>`;
  }

  function renderActions(item) {
    return `
      <div class="section">
        <div class="action-row">
          <button class="btn btn-ghost btn-small" type="button" onclick="toggleEdit(${item.id})">✎ Editar</button>
          <button class="btn btn-success btn-small" type="button" onclick="setItemStatus(${item.id}, 'accepted_item')">✓ Aceptar</button>
          <button class="btn btn-warning btn-small" type="button" onclick="setItemStatus(${item.id}, 'manual_review')">◎ Revisar</button>
          <button class="btn btn-danger btn-small" type="button" onclick="setItemStatus(${item.id}, 'rejected_item')">✕ Rechazar</button>
          <button class="btn btn-soft btn-small" type="button" onclick="suggestItem(${item.id})">◎ Auto buscar</button>
          <button class="btn btn-ghost btn-small" type="button" onclick="openManualModal(${item.id})">⌕ Buscar</button>
          <button class="btn btn-soft btn-small" type="button" onclick="openSamplesModal(${item.id})">📦 Muestras</button>
          <button class="btn btn-outline btn-small" type="button" onclick="openTechSheetsModal(${item.id})">📄 Fichas</button>
        </div>
      </div>`;
  }

  function renderEditForm(item) {
    return `
      <form class="edit-form" id="edit-form-${item.id}" onsubmit="saveItem(event, ${item.id})">
        <div class="row">
          <div class="col-12 col-md-6 field"><label>Producto</label><input class="input" name="descripcion_original" value="${escapeHtml(item.descripcion_original || '')}"></div>
          <div class="col-6 col-md-2 field"><label>Cant.</label><input class="input" type="number" step="0.01" name="cantidad_cotizada" value="${Number(item.cantidad_cotizada || 1)}"></div>
          <div class="col-6 col-md-2 field"><label>Unidad</label><input class="input" name="unidad_solicitada" value="${escapeHtml(item.unidad_solicitada || '')}"></div>
          <div class="col-6 col-md-2 field"><label>Margen</label><input class="input" type="number" step="0.01" name="porcentaje_utilidad" value="${Number(item.item_margin_pct || 25)}"></div>
          <div class="col-6 col-md-6 field"><label>Proveedor</label><input class="input" name="external_supplier" value="${escapeHtml(item.manual_external_supplier || '')}"></div>
          <div class="col-6 col-md-6 field"><label>Enlace</label><input class="input" name="external_link" value="${escapeHtml(item.manual_external_link || '')}"></div>
        </div>
        <div class="action-row mt-3">
          <button class="btn btn-primary btn-small" type="submit">Guardar</button>
          <button class="btn btn-ghost btn-small" type="button" onclick="toggleEdit(${item.id})">Cancelar</button>
        </div>
      </form>`;
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
    document.querySelector(`.item-card[data-id="${id}"]`)?.classList.toggle('open');
  }

  function toggleEdit(id) {
    document.getElementById(`edit-form-${id}`)?.classList.toggle('show');
  }

  async function suggestItem(id) {
    try {
      const data = await ajax(urlFor(routes.suggestItem, id), { method: 'POST', body: '{}' });
      updateItemInState(data.item); summary = data.summary || summary; renderItems();
      document.querySelector(`.item-card[data-id="${id}"]`)?.classList.add('open');
    } catch (e) { showInlineError(e.message); }
  }

  async function selectMatch(itemId, matchId) {
    try {
      const data = await ajax(routes.selectMatch.replace('__ID__', itemId).replace('__MATCH__', matchId), { method: 'POST', body: '{}' });
      updateItemInState(data.item); summary = data.summary || summary; renderItems();
      document.querySelector(`.item-card[data-id="${itemId}"]`)?.classList.add('open');
    } catch (e) { showInlineError(e.message); }
  }

  async function setItemStatus(id, status) {
    try {
      const data = await ajax(urlFor(routes.updateStatus, id), { method: 'POST', body: JSON.stringify({ ui_status: status }) });
      updateItemInState(data.item); summary = data.summary || summary; renderItems();
      document.querySelector(`.item-card[data-id="${id}"]`)?.classList.add('open');
    } catch (e) { showInlineError(e.message); }
  }

  async function saveItem(event, id) {
    event.preventDefault();
    const payload = Object.fromEntries(new FormData(event.target).entries());
    try {
      const data = await ajax(urlFor(routes.updateItem, id), { method: 'POST', body: JSON.stringify(payload) });
      updateItemInState(data.item); summary = data.summary || summary; renderItems();
      document.querySelector(`.item-card[data-id="${id}"]`)?.classList.add('open');
    } catch (e) { showInlineError(e.message); }
  }

  async function suggestAll() {
    if (isSuggestingAll) return;
    const pendingItems = items.filter(item => item.status_key !== 'exact');
    if (!pendingItems.length) return showProcessBox('success', 'Listo', 'Todo exacto', items.length, items.length, []);
    
    isSuggestingAll = true;
    showProcessBox('', 'Buscando', `Procesando ${pendingItems.length} partidas...`, 0, pendingItems.length, []);
    
    let done = 0, success = 0, errors = [], cursor = 0;
    async function worker() {
      while (cursor < pendingItems.length) {
        const item = pendingItems[cursor++];
        try {
          const data = await ajax(urlFor(routes.suggestItem, item.id), { method: 'POST', body: '{}' });
          if (data.item) updateItemInState(data.item);
          if (data.summary) summary = data.summary;
          success++;
        } catch (error) { errors.push(`Partida #${item.sort || cursor}: ${error.message}`); }
        finally {
          done++;
          showProcessBox(errors.length ? 'error' : '', 'Buscando', `Listas ${done}/${pendingItems.length}`, done, pendingItems.length, errors);
          if (done % 5 === 0 || done === pendingItems.length) renderItems();
        }
      }
    }
    
    try { await Promise.all(Array.from({ length: 4 }, worker)); }
    finally { isSuggestingAll = false; }
  }

  async function saveGlobalMargin(applyToItems) {
    const margin = document.getElementById('globalMarginInput').value;
    try {
      const data = await ajax(routes.globalMargin, { method: 'POST', body: JSON.stringify({ porcentaje_utilidad: margin, apply_to_items: applyToItems }) });
      items = mergeTechSheetMeta(data.items) || items; summary = data.summary || summary; renderItems();
    } catch (e) { showInlineError(e.message); }
  }

  function openManualModal(id) {
    manualItemId = id;
    document.getElementById('manualModal').classList.add('show');
    manualTab = 'catalog'; scheduleManualSearch(250);
  }
  function closeManualModal() { document.getElementById('manualModal').classList.remove('show'); }
  function clearManualSearch() { document.getElementById('manualQueryInput').value = ''; }
  function scheduleManualSearch(delay = 420) { clearTimeout(manualSearchTimer); manualSearchTimer = setTimeout(runManualSearchLive, delay); }

  async function runManualSearchLive() {
    const q = document.getElementById('manualQueryInput').value.trim();
    if (!q) return;
    try {
      const params = new URLSearchParams({ q, item_id: manualItemId, internet: manualTab === 'internet' ? '1' : '0' });
      const data = await ajax(routes.manualSearch + '?' + params.toString(), { method: 'GET' });
      if (manualTab === 'internet') { manualInternetResults = data.internet || []; renderManualInternet(manualInternetResults); }
      else { manualCatalogResults = data.products || []; renderManualCatalog(manualCatalogResults); }
    } catch (e) {}
  }

  function renderManualCatalog(products) {
    document.getElementById('manualResults').innerHTML = products.map((p, index) => `
      <div class="modal-result">
        <div><div class="result-title">${escapeHtml(p.name)}</div><div class="result-meta">SKU: ${escapeHtml(p.sku)} · ${money(p.price)}</div></div>
        <button class="btn btn-primary btn-small" type="button" onclick="useManualCatalog(${index})">Usar</button>
      </div>`).join('');
  }

  function renderManualInternet(results) {
    document.getElementById('manualResults').innerHTML = results.map((r, index) => `
      <div class="modal-result">
        <div><div class="result-title">${escapeHtml(r.title)}</div><div class="result-meta">${money(r.price)}</div></div>
        <button class="btn btn-primary btn-small" type="button" onclick="useManualInternet(${index})">Usar</button>
      </div>`).join('');
  }

  async function useManualCatalog(index) {
    const p = manualCatalogResults[index]; if (!p) return;
    try {
      const data = await ajax(urlFor(routes.updateItem, manualItemId), { method: 'POST', body: JSON.stringify({ catalog_product_name: p.name, costo_unitario: p.cost || 0, external_supplier: p.brand || '', external_link: '' }) });
      updateItemInState(data.item); closeManualModal(); renderItems();
    } catch (e) { showInlineError(e.message); }
  }

  async function useManualInternet(index) {
    const r = manualInternetResults[index]; if (!r) return;
    try {
      const data = await ajax(urlFor(routes.updateItem, manualItemId), { method: 'POST', body: JSON.stringify({ catalog_product_name: r.title, costo_unitario: r.price || 0, external_supplier: r.source || '', external_link: r.url || '' }) });
      updateItemInState(data.item); closeManualModal(); renderItems();
    } catch (e) { showInlineError(e.message); }
  }

  function openAddItemModal() { document.getElementById('addItemModal').classList.add('show'); }
  function closeAddItemModal() { document.getElementById('addItemModal').classList.remove('show'); }

  async function storeNewItem(event) {
    event.preventDefault();
    try {
      const data = await ajax(routes.storeItem, { method: 'POST', body: JSON.stringify(Object.fromEntries(new FormData(event.target).entries())) });
      items = mergeTechSheetMeta(data.items) || items; summary = data.summary || summary;
      closeAddItemModal(); event.target.reset(); renderItems();
    } catch (e) { showInlineError(e.message); }
  }

  function closeSamplesModal() { document.getElementById('samplesModal').classList.remove('show'); }
  async function openSamplesModal(id) {
    document.getElementById('samplesModal').classList.add('show');
    try { renderSamples(await ajax(urlFor(routes.itemSamples, id), { method: 'GET' })); } catch (e) {}
  }
  function renderSamples(data) {
    document.getElementById('samplesResults').innerHTML = (data.candidates || []).map(c => `
      <div class="result-card"><div class="result-title">${escapeHtml(c.name)}</div><div class="result-meta">Stock: ${c.net_available}</div></div>
    `).join('');
  }

  function closeTechSheetsModal() { document.getElementById('techSheetsModal').classList.remove('show'); }
  function techShowList() { document.getElementById('techListPane').style.display = ''; document.getElementById('techFormPane').style.display = 'none'; }
  function techShowCreate() { document.getElementById('techListPane').style.display = 'none'; document.getElementById('techFormPane').style.display = ''; }
  function openTechSheetsModal(id) {
    techItemId = id; document.getElementById('techSheetsModal').classList.add('show'); techShowList(); loadTechSheets();
  }
  async function loadTechSheets() {
    try { renderTechSheets(await ajax(urlFor(routes.techSheetsList, techItemId), { method: 'GET' })); } catch (e) {}
  }
  function renderTechSheets(data) {
    techSheetsCache = data.sheets || []; currentLinkedSheetId = data.linked_id || null;
    document.getElementById('techResults').innerHTML = techSheetsCache.map(s => `
      <div class="modal-result">
        <div><div class="result-title">${escapeHtml(s.product_name)}</div></div>
        <button class="btn btn-primary btn-small" type="button" onclick="linkTechSheet(${s.id})">${s.id === currentLinkedSheetId ? 'Quitar' : 'Vincular'}</button>
      </div>`).join('');
  }
  async function linkTechSheet(sheetId) {
    const unlink = sheetId === currentLinkedSheetId;
    try {
      await ajax(urlFor(routes.linkTechSheet, techItemId), { method: 'POST', body: JSON.stringify({ tech_sheet_id: unlink ? null : sheetId }) });
      renderItems(); loadTechSheets();
    } catch (e) { showInlineError(e.message); }
  }
  async function submitTechSheet(event) {
    event.preventDefault();
    try {
      await fetch(urlFor(routes.createTechSheet, techItemId), { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken }, body: new FormData(event.target) });
      techShowList(); renderItems(); loadTechSheets();
    } catch (e) { showInlineError(e.message); }
  }

  function bindDragEvents() {} // Mantenido para lógica estructural, sin implementar completa por minimalismo

  // Rule 6: Custom Animated Select System
  function initCustomSelects() {
    document.querySelectorAll('select:not(.native-select-hidden)').forEach(select => {
      select.classList.add('native-select-hidden');
      const wrapper = document.createElement('div');
      wrapper.className = 'custom-select-wrapper';
      const trigger = document.createElement('div');
      trigger.className = 'custom-select-trigger';
      trigger.innerHTML = `<span>${select.options[select.selectedIndex]?.text || 'Seleccionar'}</span> <span>⌄</span>`;
      
      const optionsContainer = document.createElement('div');
      optionsContainer.className = 'custom-select-options';
      
      Array.from(select.options).forEach(option => {
        const optDiv = document.createElement('div');
        optDiv.className = 'custom-select-option';
        optDiv.textContent = option.text;
        if (option.selected) optDiv.classList.add('selected');
        
        optDiv.addEventListener('click', () => {
          select.value = option.value;
          select.dispatchEvent(new Event('change'));
          trigger.querySelector('span').textContent = option.text;
          wrapper.classList.remove('open');
          optionsContainer.querySelectorAll('.custom-select-option').forEach(o => o.classList.remove('selected'));
          optDiv.classList.add('selected');
        });
        optionsContainer.appendChild(optDiv);
      });
      
      trigger.addEventListener('click', () => {
        const isOpen = wrapper.classList.contains('open');
        document.querySelectorAll('.custom-select-wrapper').forEach(w => w.classList.remove('open'));
        if (!isOpen) wrapper.classList.add('open');
      });
      
      wrapper.appendChild(trigger); wrapper.appendChild(optionsContainer);
      select.parentNode.insertBefore(wrapper, select);
    });

    document.addEventListener('click', (e) => {
      if (!e.target.closest('.custom-select-wrapper')) {
        document.querySelectorAll('.custom-select-wrapper').forEach(w => w.classList.remove('open'));
      }
    });
  }

  document.querySelectorAll('.filter-summary').forEach(btn => btn.addEventListener('click', () => { currentFilter = btn.dataset.filter || 'all'; renderItems(); }));
  document.getElementById('manualTabCatalog').addEventListener('click', () => { manualTab = 'catalog'; scheduleManualSearch(10); });
  document.getElementById('manualTabInternet').addEventListener('click', () => { manualTab = 'internet'; scheduleManualSearch(10); });
  document.getElementById('btnSuggestAll').addEventListener('click', suggestAll);
  document.getElementById('btnOpenAddItem').addEventListener('click', openAddItemModal);
  document.getElementById('btnSaveGlobalMargin').addEventListener('click', () => saveGlobalMargin(false));
  document.getElementById('btnApplyGlobalMargin').addEventListener('click', () => saveGlobalMargin(true));

  document.addEventListener('DOMContentLoaded', () => {
    renderItems();
  });
</script>
@endsection