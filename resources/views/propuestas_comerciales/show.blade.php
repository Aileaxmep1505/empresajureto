@extends('layouts.app')
@section('content_class', 'content--flush')
@section('content')

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

<style>
/* ============================================================
   COTIZACIÓN · vista show rediseñada — Grupo MediBuy
   Todo scoped bajo .q-page (no necesita cotizacion.css)
   ============================================================ */
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Open+Sans:wght@400;500;600&display=swap');

.q-page{
  --cobalto:#1B4F8A; --olivo:#4A7A3A; --acero:#2E86C1;
  --danger:#C0392B; --warning:#B7791F;
  --bg:#F5F7FA; --surface:#FFFFFF; --line:#E6EAF0; --line-strong:#D5DBE5;
  --ink:#19212E; --ink-soft:#5A6678; --ink-faint:#8B97A8;
  --fh:'Montserrat',sans-serif; --fb:'Open Sans',sans-serif;
  --ease-out:cubic-bezier(0.23,1,0.32,1);
  --shadow-hover:0 6px 20px -8px rgba(27,79,138,.22);
  --r:14px;
  font-family:var(--fb); color:var(--ink); background:var(--bg);
  min-height:100vh; padding:26px 18px 90px; -webkit-font-smoothing:antialiased;
}
.q-page *{box-sizing:border-box}
.q-wrap{max-width:1080px;margin:0 auto}

.q-page .ico{width:16px;height:16px;display:inline-block;vertical-align:-3px;flex:none;
  stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.q-page .ico--sm{width:14px;height:14px}

.q-page .back{display:inline-flex;align-items:center;gap:7px;color:var(--ink-soft);
  text-decoration:none;font-size:13.5px;font-weight:600;margin-bottom:18px;
  transition:color 160ms ease}
.q-page .back:hover{color:var(--cobalto)}

/* ---------- HEADER ---------- */
.q-header{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:14px}
.q-header__folio{font-family:ui-monospace,Menlo,monospace;font-size:12px;font-weight:600;
  letter-spacing:.02em;color:var(--acero);background:rgba(46,134,193,.1);padding:3px 9px;border-radius:6px}
.q-header__meta{display:flex;align-items:center;gap:9px;color:var(--ink-faint);font-size:12.5px;margin-bottom:8px;flex-wrap:wrap}
.q-header__meta .dot{width:3px;height:3px;border-radius:50%;background:var(--ink-faint)}
.q-header__title{font-family:var(--fh);font-weight:800;font-size:24px;letter-spacing:-.02em;line-height:1.18}

/* ---------- BOTONES ---------- */
.q-page .btn{font-family:var(--fh);font-weight:600;font-size:13px;display:inline-flex;align-items:center;
  gap:7px;border:1px solid transparent;border-radius:10px;padding:9px 15px;cursor:pointer;background:none;
  color:var(--ink);text-decoration:none;white-space:nowrap;
  transition:transform 150ms var(--ease-out),background 160ms ease,border-color 160ms ease,box-shadow 160ms ease,opacity 160ms ease}
.q-page .btn:active{transform:scale(.97)}
.q-page .btn[disabled]{opacity:.6;cursor:default}
.q-page .btn-primary{background:var(--cobalto);color:#fff}
.q-page .btn-primary:hover{background:#16416f;box-shadow:var(--shadow-hover)}
.q-page .btn-success,.q-page .btn-accept,.q-page .btn-approve{background:var(--olivo);color:#fff}
.q-page .btn-success:hover,.q-page .btn-accept:hover,.q-page .btn-approve:hover{background:#3d6630}
.q-page .btn-danger{background:rgba(192,57,43,.1);color:var(--danger)}
.q-page .btn-danger:hover{background:rgba(192,57,43,.16)}
.q-page .btn-warning{background:rgba(183,121,31,.12);color:var(--warning)}
.q-page .btn-warning:hover{background:rgba(183,121,31,.2)}
.q-page .btn-outline,.q-page .btn-export-excel,.q-page .btn-export-word{background:var(--surface);color:var(--ink-soft);border-color:var(--line-strong)}
.q-page .btn-outline:hover,.q-page .btn-export-excel:hover,.q-page .btn-export-word:hover{border-color:var(--cobalto);color:var(--cobalto)}
.q-page .btn-ghost{background:transparent;color:var(--ink-soft)}
.q-page .btn-ghost:hover{background:var(--bg);color:var(--ink)}
.q-page .btn-soft{background:rgba(46,134,193,.1);color:var(--acero)}
.q-page .btn-soft:hover{background:rgba(46,134,193,.16)}
.q-page .btn-small{padding:6px 11px;font-size:12px;border-radius:8px}
.q-page .btn-icon{padding:8px;width:34px;justify-content:center}

/* ---------- STICKY ACTION BAR ---------- */
.q-actionbar{position:sticky;top:0;z-index:30;display:flex;align-items:center;justify-content:space-between;
  gap:12px;flex-wrap:wrap;background:rgba(255,255,255,.86);backdrop-filter:blur(10px);
  border:1px solid var(--line);border-radius:var(--r);padding:11px 14px;margin-bottom:12px}
.q-actionbar__group{display:flex;align-items:center;gap:9px;flex-wrap:wrap}

/* ---------- NOTICE ---------- */
.q-page .notice{display:none;align-items:center;gap:10px;background:rgba(192,57,43,.06);
  border:1px solid rgba(192,57,43,.18);color:var(--danger);border-radius:12px;padding:11px 14px;
  font-size:13px;margin-bottom:12px}
.q-page .notice.show{display:flex}
.q-page .notice .ico{stroke:var(--danger)}

/* ---------- PROCESS BOX ---------- */
.q-page .process-box{display:none;background:var(--surface);border:1px solid var(--line);
  border-radius:12px;padding:14px 16px;margin-bottom:12px}
.q-page .process-box.show{display:block}
.q-page .process-box.success{border-color:rgba(74,122,58,.4)}
.q-page .process-box.error{border-color:rgba(192,57,43,.4)}
.q-page .process-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
.q-page .process-title{font-family:var(--fh);font-weight:700;font-size:14px}
.q-page .process-text{font-size:12.5px;color:var(--ink-soft);margin-top:2px}
.q-page .process-bar{height:6px;background:var(--bg);border-radius:20px;margin-top:12px;overflow:hidden}
.q-page .process-fill{height:100%;width:0;background:var(--cobalto);border-radius:20px;transition:width 280ms var(--ease-out)}
.q-page .process-box.success .process-fill{background:var(--olivo)}
.q-page .process-box.error .process-fill{background:var(--danger)}
.q-page .process-errors{display:none;margin-top:10px;font-size:12px;color:var(--danger);max-height:160px;overflow:auto}
.q-page .process-errors.show{display:block}

/* ---------- SUMMARY (dato) ---------- */
.q-summary{display:flex;flex-wrap:wrap;align-items:center;gap:8px 22px;background:var(--surface);
  border:1px solid var(--line);border-radius:var(--r);padding:14px 18px;margin-bottom:12px}
.q-summary__stat{display:flex;align-items:baseline;gap:7px}
.q-summary__stat b{font-family:var(--fh);font-weight:700;font-size:16px}
.q-summary__stat span{font-size:12px;color:var(--ink-faint)}
.q-summary .sep{width:1px;height:18px;background:var(--line)}
.v-cobalto{color:var(--cobalto)} .v-olivo{color:var(--olivo)} .v-danger{color:var(--danger)}

/* ---------- FILTROS (segmented) ---------- */
.q-filters{display:inline-flex;background:var(--surface);border:1px solid var(--line);border-radius:11px;
  padding:4px;gap:2px;position:relative;margin-bottom:22px;max-width:100%;overflow:auto}
.q-filters .filter-summary{font-family:var(--fh);font-weight:600;font-size:12.5px;border:0;background:none;
  color:var(--ink-soft);padding:7px 14px;border-radius:8px;cursor:pointer;position:relative;z-index:2;
  white-space:nowrap;display:inline-flex;align-items:center;gap:6px;transition:color 180ms var(--ease-out)}
.q-filters .filter-summary.active{color:#fff}
.q-filters .filter-summary .summary-value{font-weight:700;opacity:.85}
.q-filters .filter-summary .summary-label{font-weight:600}
.q-filters__pill{position:absolute;top:4px;bottom:4px;left:4px;border-radius:8px;background:var(--cobalto);
  z-index:1;transition:transform 260ms var(--ease-out),width 260ms var(--ease-out)}

/* ---------- ITEM CARD ---------- */
.q-list{display:flex;flex-direction:column;gap:10px}
.q-item{background:var(--surface);border:1px solid var(--line);border-radius:var(--r);
  border-left:3px solid var(--line-strong);opacity:0;transform:translateY(8px);
  animation:qFadeUp 360ms var(--ease-out) forwards;transition:box-shadow 180ms ease,border-color 180ms ease}
.q-item:hover{box-shadow:var(--shadow-hover)}
.q-item.dragging{opacity:.55}
.q-item.status-exact{border-left-color:var(--olivo)}
.q-item.status-similar{border-left-color:var(--acero)}
.q-item.status-not_found{border-left-color:var(--danger)}
@keyframes qFadeUp{to{opacity:1;transform:translateY(0)}}

.q-item__head{display:grid;grid-template-columns:auto auto 1fr auto auto;align-items:center;gap:14px;padding:14px 16px;cursor:pointer}
.q-item__drag{color:var(--ink-faint);cursor:grab;border:0;background:none;padding:2px;display:flex}
.q-item__idx{width:26px;height:26px;border-radius:8px;background:var(--bg);color:var(--ink-soft);
  font-family:var(--fh);font-weight:700;font-size:12.5px;display:flex;align-items:center;justify-content:center}
.q-item__id{min-width:0}
.q-item__name{font-family:var(--fh);font-weight:600;font-size:14.5px;color:var(--ink);
  overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.q-item__sub{display:flex;align-items:center;gap:8px;margin-top:4px;font-size:12px;color:var(--ink-faint);flex-wrap:wrap}
.q-item__money{text-align:right;line-height:1.25}
.q-item__money b{font-family:var(--fh);font-weight:700;font-size:15px;color:var(--ink)}
.q-item__money span{display:block;font-size:10.5px;color:var(--ink-faint);text-transform:uppercase;letter-spacing:.04em}
.q-item__cta{display:flex;align-items:center;gap:6px}
.q-item__chev{color:var(--ink-faint);transition:transform 280ms var(--ease-out)}
.q-item.open .q-item__chev{transform:rotate(180deg)}

/* badges */
.q-page .badge{display:inline-flex;align-items:center;gap:4px;font-family:var(--fh);font-weight:600;font-size:10.5px;
  padding:3px 8px;border-radius:20px;letter-spacing:.01em;white-space:nowrap}
.q-page .badge-success{background:rgba(74,122,58,.12);color:var(--olivo)}
.q-page .badge-info{background:rgba(46,134,193,.12);color:var(--acero)}
.q-page .badge-warning{background:rgba(183,121,31,.14);color:var(--warning)}
.q-page .badge-danger{background:rgba(192,57,43,.1);color:var(--danger)}
.q-page .badge-soft{background:var(--bg);color:var(--ink-soft)}
.q-page .badge-score{background:rgba(27,79,138,.08);color:var(--cobalto);font-weight:700}

/* ---------- DETALLE (tabs) ---------- */
.q-item__detail{display:grid;grid-template-rows:0fr;transition:grid-template-rows 260ms var(--ease-out)}
.q-item.open .q-item__detail{grid-template-rows:1fr}
.q-item__detail-inner{overflow:hidden;border-top:1px solid var(--line)}
.q-detail{padding:16px}
.q-tabs{display:inline-flex;gap:4px;background:var(--bg);border-radius:10px;padding:4px;margin-bottom:14px;flex-wrap:wrap}
.q-tabs__btn{font-family:var(--fh);font-weight:600;font-size:12.5px;border:0;background:none;color:var(--ink-soft);
  padding:7px 13px;border-radius:7px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;
  transition:background 200ms var(--ease-out),color 160ms ease}
.q-tabs__btn.active{background:var(--surface);color:var(--cobalto);box-shadow:0 1px 3px rgba(25,33,46,.08)}
.q-panel{display:none;animation:qPanelIn 240ms var(--ease-out)}
.q-panel.active{display:block}
@keyframes qPanelIn{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}

.q-page .result-card{border:1px solid var(--line);border-radius:11px;padding:12px 14px;margin-bottom:8px;
  transition:border-color 160ms ease}
.q-page .result-card:hover{border-color:var(--line-strong)}
.q-page .external-box{border:1px dashed var(--line-strong);border-radius:11px;padding:12px 14px;margin-bottom:8px;background:#FBFCFE}
.q-page .result-title{font-family:var(--fh);font-weight:600;font-size:13.5px;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.q-page .result-meta{font-size:12px;color:var(--ink-faint);margin-top:4px;line-height:1.5}
.q-page .action-row{display:flex;gap:7px;margin-top:11px;flex-wrap:wrap;align-items:center}
.q-page .warning-line{display:flex;align-items:center;gap:7px;font-size:12.5px;color:var(--warning);margin-top:6px}
.q-page .warning-line .ico{stroke:var(--warning)}
.q-page .tech-preview iframe{width:100%;height:560px;border:1px solid var(--line);border-radius:12px;background:#fff}

/* forms */
.q-page .field{display:flex;flex-direction:column;gap:5px}
.q-page .field label,.q-page label{font-size:11.5px;font-weight:600;color:var(--ink-soft);font-family:var(--fh)}
.q-page .input{border:1px solid var(--line-strong);border-radius:9px;padding:9px 11px;font-family:var(--fb);
  font-size:13.5px;color:var(--ink);background:var(--surface);width:100%;
  transition:border-color 160ms ease,box-shadow 160ms ease}
.q-page .input:focus{outline:none;border-color:var(--cobalto);box-shadow:0 0 0 3px rgba(27,79,138,.12)}
.q-edit-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.q-edit-grid .field:first-child{grid-column:1/-1}
.q-live-margin{grid-column:1/-1;font-size:12.5px;color:var(--ink-soft);background:var(--bg);
  border-radius:9px;padding:10px 13px}
.q-live-margin b{color:var(--olivo);font-family:var(--fh)}

/* ---------- MENU overflow / popover ---------- */
.q-menu-host{position:relative;display:inline-flex}
.q-menu{position:absolute;top:calc(100% + 6px);right:0;min-width:214px;background:var(--surface);
  border:1px solid var(--line);border-radius:12px;box-shadow:0 12px 34px -10px rgba(25,33,46,.28);
  padding:6px;z-index:60;transform-origin:top right;opacity:0;transform:scale(.95);pointer-events:none;
  transition:opacity 150ms var(--ease-out),transform 150ms var(--ease-out)}
.q-menu-host.open .q-menu{opacity:1;transform:scale(1);pointer-events:auto}
.q-menu__item{display:flex;align-items:center;gap:10px;width:100%;border:0;background:none;font-family:var(--fb);
  font-size:13px;color:var(--ink);padding:9px 11px;border-radius:8px;cursor:pointer;text-align:left;text-decoration:none;
  transition:background 130ms ease}
.q-menu__item:hover{background:var(--bg)}
.q-menu__item.danger{color:var(--danger)}
.q-menu__sep{height:1px;background:var(--line);margin:5px 4px}
.q-menu__label{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--ink-faint);
  padding:8px 11px 4px;font-family:var(--fh)}
.q-pop{min-width:236px;padding:14px}
.q-pop h4{font-family:var(--fh);font-size:13px;margin-bottom:10px}
.q-pop .row{display:flex;gap:8px;margin-top:10px}

/* ---------- MODALES ---------- */
.q-page .modal-backdrop{display:none;position:fixed;inset:0;background:rgba(19,33,46,.45);
  z-index:100;align-items:flex-start;justify-content:center;padding:40px 18px;overflow:auto}
.q-page .modal-backdrop.show{display:flex}
.q-page .modal{background:var(--surface);border-radius:16px;width:100%;max-width:600px;
  box-shadow:0 24px 60px -18px rgba(19,33,46,.45);animation:qModalIn 220ms var(--ease-out)}
@keyframes qModalIn{from{opacity:0;transform:scale(.97) translateY(6px)}to{opacity:1;transform:none}}
.q-page .modal-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;
  padding:18px 20px;border-bottom:1px solid var(--line)}
.q-page .modal-title{font-family:var(--fh);font-weight:700;font-size:16px}
.q-page .modal-subtitle{font-size:12.5px;color:var(--ink-faint);margin-top:3px}
.q-page .modal-body{padding:18px 20px}
.q-page .modal-tabs{display:inline-flex;gap:4px;background:var(--bg);border-radius:10px;padding:4px;margin:4px 0 14px}
.q-page .tab-btn{font-family:var(--fh);font-weight:600;font-size:12.5px;border:0;background:none;color:var(--ink-soft);
  padding:7px 13px;border-radius:7px;cursor:pointer;transition:background 200ms var(--ease-out),color 160ms ease}
.q-page .tab-btn.active{background:var(--surface);color:var(--cobalto);box-shadow:0 1px 3px rgba(25,33,46,.08)}
.q-page .modal-result{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;
  border:1px solid var(--line);border-radius:11px;padding:12px 14px;margin-bottom:8px}
.q-search-field{position:relative}
.q-search-field .ico-lead{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--ink-faint)}
.q-search-field .ico-clear{position:absolute;right:10px;top:50%;transform:translateY(-50%);border:0;background:none;
  color:var(--ink-faint);cursor:pointer;display:flex;padding:2px}
.q-search-field .input{padding-left:38px;padding-right:38px}

/* loader */
.q-page .loader{width:13px;height:13px;border:2px solid currentColor;border-right-color:transparent;
  border-radius:50%;display:inline-block;animation:qSpin .7s linear infinite;vertical-align:-1px}
@keyframes qSpin{to{transform:rotate(360deg)}}

@media (max-width:680px){
  .q-item__head{grid-template-columns:auto 1fr auto;gap:10px}
  .q-item__drag{display:none}
  .q-item__money{grid-column:1/-1;text-align:left;display:flex;gap:6px;align-items:baseline}
  .q-item__money span{display:inline}
  .q-edit-grid{grid-template-columns:1fr 1fr}
  .q-header__title{font-size:20px}
}
@media (prefers-reduced-motion:reduce){
  .q-page *{animation-duration:.01ms!important;transition-duration:.01ms!important}
}
</style>

<div class="q-page">
  <div class="q-wrap">
    <a href="{{ route('propuestas-comerciales.index') }}" class="back">
      <svg class="ico" viewBox="0 0 24 24"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
      <span>Volver a propuestas</span>
    </a>

    <!-- HEADER limpio -->
    <header class="q-header">
      <div>
        <div class="q-header__meta">
          <span class="q-header__folio">{{ $exportFolio }}</span>
          <span class="dot"></span>
          <span><strong id="itemsCountText">{{ $summaryPayload['total_items'] }}</strong> partidas analizadas por IA</span>
          <span class="dot"></span>
          <span>Exportación desde PDF</span>
        </div>
        <h1 class="q-header__title">{{ $exportTitle }}</h1>
      </div>

      <div class="q-menu-host" id="moreMenu">
        <button class="btn btn-outline" type="button" onclick="toggleMenu('moreMenu')">
          <svg class="ico" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/></svg>
          <span>Más</span>
        </button>
        <div class="q-menu">
          <div class="q-menu__label">Partidas</div>
          <button class="q-menu__item" type="button" id="btnOpenAddItem">
            <svg class="ico" viewBox="0 0 24 24"><path d="M12 5v14"/><path d="M5 12h14"/></svg> Agregar partida
          </button>
          <div class="q-menu__sep"></div>
          <div class="q-menu__label">Exportar</div>
          <button class="q-menu__item" type="button" id="btnExportExcel">
            <svg class="ico" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h8"/></svg> Exportar Excel (PDF)
          </button>
          <button class="q-menu__item" type="button" id="btnExportWord">
            <svg class="ico" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h6"/><path d="M8 17h4"/></svg> Exportar Word (PDF)
          </button>
          <a class="q-menu__item" href="{{ route('propuestas-comerciales.fallo.show', $propuestaComercial) }}">
            <svg class="ico" viewBox="0 0 24 24"><path d="M12 3v18"/><path d="M5 7l7-4 7 4"/><path d="M4 7l-2 6a4 4 0 0 0 8 0L8 7"/><path d="M16 7l-2 6a4 4 0 0 0 8 0l-2-6"/></svg> Acta de fallo
          </a>
        </div>
      </div>
    </header>

    <!-- STICKY ACTION BAR -->
    <div class="q-actionbar">
      <div class="q-actionbar__group">
        <button class="btn btn-primary" type="button" id="btnSuggestAll">
          <svg class="ico" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/><path d="M11 8v6"/><path d="M8 11h6"/></svg>
          <span>Buscar coincidencias</span>
        </button>
        <div class="q-menu-host" id="marginMenu">
          <button class="btn btn-outline" type="button" onclick="toggleMenu('marginMenu')">
            <span>Margen global</span>
            <svg class="ico ico--sm" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
          </button>
          <div class="q-menu q-pop">
            <h4>Margen global</h4>
            <div class="field">
              <label>Porcentaje de utilidad</label>
              <input class="input" id="globalMarginInput" type="number" step="0.01" value="{{ $propuestaComercial->porcentaje_utilidad ?: 25 }}">
            </div>
            <div class="row">
              <button class="btn btn-outline btn-small" type="button" id="btnSaveGlobalMargin" style="flex:1">Guardar</button>
              <button class="btn btn-primary btn-small" type="button" id="btnApplyGlobalMargin" style="flex:1">Aplicar a todas</button>
            </div>
          </div>
        </div>
      </div>

      <a href="{{ route('propuestas-comerciales.cliente.show', $propuestaComercial) }}" class="btn btn-approve">
        <svg class="ico" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
        <span>Aprobar propuesta</span>
      </a>
    </div>

    <!-- NOTICE -->
    <div id="noticeBox" class="notice">
      <svg class="ico" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
      <span><strong id="noticeCount">0 partidas</strong> sin encontrar en catálogo — usa la pestaña Internet o búsqueda manual para hallar alternativas.</span>
    </div>

    <!-- PROCESS BOX -->
    <div id="processBox" class="process-box">
      <div class="process-head">
        <div>
          <div class="process-title" id="processTitle">Procesando coincidencias...</div>
          <div class="process-text" id="processText">Preparando partidas.</div>
        </div>
        <span class="badge badge-info" id="processCount">0/0</span>
      </div>
      <div class="process-bar"><div class="process-fill" id="processFill"></div></div>
      <div id="processErrors" class="process-errors"></div>
    </div>

    <!-- RESUMEN (dato) -->
    <div class="q-summary">
      <div class="q-summary__stat"><b id="sumTotal">{{ $summaryPayload['total_items'] }}</b><span>partidas</span></div>
      <div class="sep"></div>
      <div class="q-summary__stat"><b class="v-cobalto" id="sumSale">$0</b><span>venta</span></div>
      <div class="q-summary__stat"><b class="v-olivo" id="sumProfit">$0</b><span>utilidad</span></div>
      <div class="q-summary__stat"><b id="sumMargin">0%</b><span>margen</span></div>
    </div>

    <!-- FILTROS segmented -->
    <div class="q-filters" id="summaryFilters">
      <span class="q-filters__pill" id="filterPill"></span>
      <button class="filter-summary active" type="button" data-filter="all">
        <span class="summary-label">Todos</span><span class="summary-value" id="sumAll">0</span>
      </button>
      <button class="filter-summary" type="button" data-filter="exact">
        <span class="summary-label">Exactos</span><span class="summary-value" id="sumExact">0</span>
      </button>
      <button class="filter-summary" type="button" data-filter="similar">
        <span class="summary-label">Similares</span><span class="summary-value" id="sumSimilar">0</span>
      </button>
      <button class="filter-summary" type="button" data-filter="not_found">
        <span class="summary-label">No encontrados</span><span class="summary-value" id="sumNotFound">0</span>
      </button>
    </div>

    <div class="q-list" id="itemsList"></div>
  </div>

  <!-- ===================== MODAL BÚSQUEDA MANUAL ===================== -->
  <div class="modal-backdrop" id="manualModal">
    <div class="modal">
      <div class="modal-head">
        <div>
          <h2 class="modal-title">Búsqueda manual</h2>
          <p class="modal-subtitle" id="manualSubtitle">Busca por nombre, SKU, marca, color, unidad o descripción.</p>
        </div>
        <button class="btn btn-ghost btn-icon" type="button" onclick="closeManualModal()">
          <svg class="ico" viewBox="0 0 24 24"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="modal-body">
        <div class="q-search-field">
          <span class="ico-lead"><svg class="ico" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg></span>
          <input class="input" id="manualQueryInput" placeholder="Buscar producto..." autocomplete="off">
          <button type="button" class="ico-clear" onclick="clearManualSearch()">
            <svg class="ico" viewBox="0 0 24 24"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
          </button>
        </div>
        <div class="modal-tabs">
          <button class="tab-btn active" type="button" id="manualTabCatalog">Catálogo interno</button>
          <button class="tab-btn" type="button" id="manualTabInternet">Internet</button>
        </div>
        <div id="manualSearchStatus" class="result-meta" style="margin-bottom:12px;">Escribe para buscar automáticamente.</div>
        <div id="manualResults"></div>
      </div>
    </div>
  </div>

  <!-- ===================== MODAL AGREGAR PARTIDA ===================== -->
  <div class="modal-backdrop" id="addItemModal">
    <div class="modal">
      <div class="modal-head">
        <div>
          <h2 class="modal-title">Agregar nueva partida</h2>
          <p class="modal-subtitle">Crea un nuevo producto solicitado y calcula costo, precio y subtotal.</p>
        </div>
        <button class="btn btn-ghost btn-icon" type="button" onclick="closeAddItemModal()">
          <svg class="ico" viewBox="0 0 24 24"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="modal-body">
        <form id="addItemForm" onsubmit="storeNewItem(event)" style="display:grid; gap:14px;">
          <div class="field">
            <label>Producto solicitado</label>
            <input class="input" name="descripcion_original" placeholder="Ej. Videoendoscopio flexible Olympus" required>
          </div>
          <div style="display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px;">
            <div class="field"><label>Cantidad</label><input class="input" type="number" step="0.01" name="cantidad_cotizada" value="1" required></div>
            <div class="field"><label>Unidad</label><input class="input" name="unidad_solicitada" value="pz"></div>
            <div class="field"><label>Costo unit.</label><input class="input" type="number" step="0.01" name="costo_unitario" value="0"></div>
            <div class="field"><label>Margen %</label><input class="input" type="number" step="0.01" name="porcentaje_utilidad" value="{{ $propuestaComercial->porcentaje_utilidad ?: 25 }}"></div>
          </div>
          <div class="action-row">
            <button class="btn btn-primary" type="submit">
              <svg class="ico" viewBox="0 0 24 24"><path d="M12 5v14"/><path d="M5 12h14"/></svg> Agregar partida
            </button>
            <button class="btn btn-ghost" type="button" onclick="closeAddItemModal()">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ===================== MODAL MUESTRAS ===================== -->
  <div class="modal-backdrop" id="samplesModal">
    <div class="modal">
      <div class="modal-head">
        <div>
          <h2 class="modal-title">Muestras · Análisis de almacén</h2>
          <p class="modal-subtitle" id="samplesSubtitle">Producto</p>
        </div>
        <button class="btn btn-ghost btn-icon" type="button" onclick="closeSamplesModal()">
          <svg class="ico" viewBox="0 0 24 24"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
        </button>
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
        <button class="btn btn-ghost btn-icon" type="button" onclick="closeTechSheetsModal()">
          <svg class="ico" viewBox="0 0 24 24"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
        </button>
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
            <div class="field"><label>Descripción</label><textarea class="input" name="user_description" rows="3" style="height:auto;"></textarea></div>
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

  /* ============ ICONOS SVG (reemplazan a los emojis) ============ */
  const ICON_PATHS = {
    plus:'<path d="M12 5v14"/><path d="M5 12h14"/>',
    search:'<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/>',
    suggest:'<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/><path d="M11 8v6"/><path d="M8 11h6"/>',
    check:'<path d="M20 6L9 17l-5-5"/>',
    x:'<path d="M18 6L6 18"/><path d="M6 6l12 12"/>',
    edit:'<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/>',
    clock:'<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
    package:'<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.27 6.96L12 12.01l8.73-5.05"/><path d="M12 22.08V12"/>',
    file:'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
    external:'<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14L21 3"/>',
    eye:'<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
    info:'<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
    warning:'<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
    grip:'<path d="M9 5h.01"/><path d="M9 12h.01"/><path d="M9 19h.01"/><path d="M15 5h.01"/><path d="M15 12h.01"/><path d="M15 19h.01"/>',
    more:'<circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>',
    chevron:'<path d="M6 9l6 6 6-6"/>',
  };
  function icon(name, cls = '') {
    const sw = name === 'grip' ? '2.6' : '2';
    return `<svg class="ico ${cls}" viewBox="0 0 24 24" stroke-width="${sw}">${ICON_PATHS[name] || ''}</svg>`;
  }

  function money(n) {
    n = Number(n || 0);
    return n.toLocaleString('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 });
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
    try { data = rawText ? JSON.parse(rawText) : null; } catch (error) { data = null; }
    if (!response.ok || !data || data.ok === false) {
      let message = data?.message || 'Error procesando la solicitud.';
      if (!data && rawText) message += ' Respuesta del servidor: ' + String(rawText).slice(0, 300);
      throw new Error(message);
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
    const titleEl = document.getElementById('processTitle');
    const textEl = document.getElementById('processText');
    const countEl = document.getElementById('processCount');
    const fillEl = document.getElementById('processFill');
    const errorsEl = document.getElementById('processErrors');
    if (!box || !titleEl || !textEl || !countEl || !fillEl || !errorsEl) return;
    box.className = 'process-box show' + (type ? ' ' + type : '');
    titleEl.textContent = title;
    textEl.textContent = text;
    countEl.textContent = `${done}/${total}`;
    const pct = total > 0 ? Math.round((done / total) * 100) : 0;
    fillEl.style.width = pct + '%';
    if (errors.length) {
      errorsEl.classList.add('show');
      errorsEl.innerHTML = errors.slice(0, 30).map(error => `<div>· ${escapeHtml(error)}</div>`).join('');
      if (errors.length > 30) errorsEl.innerHTML += `<div>· Y ${errors.length - 30} errores más...</div>`;
    } else {
      errorsEl.classList.remove('show');
      errorsEl.innerHTML = '';
    }
  }
  function showInlineError(message) {
    showProcessBox('error', 'No se pudo completar la acción', message || 'Ocurrió un error procesando la solicitud.', 1, 1, []);
    const box = document.getElementById('processBox');
    if (box) box.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
  function hideProcessBox() {
    const box = document.getElementById('processBox');
    if (box) box.classList.remove('show');
  }

  function statusLabel(item) {
    if (item.ui_status === 'accepted_item') return { text: 'Aceptado', cls: 'badge-success' };
    if (item.ui_status === 'manual_review') return { text: 'En revisión', cls: 'badge-warning' };
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

  /* ============ CTA primario por estado ============ */
  function itemCTA(item) {
    if (item.ui_status === 'accepted_item') {
      return `<button class="btn btn-ghost btn-small" type="button" onclick="event.stopPropagation();openItemDetail(${item.id})">Ver detalle</button>`;
    }
    if (item.status_key === 'not_found') {
      return `<button class="btn btn-primary btn-small" type="button" onclick="event.stopPropagation();suggestItem(${item.id})">${icon('search','ico--sm')} Buscar alternativa</button>`;
    }
    return `<button class="btn btn-accept btn-small" type="button" onclick="event.stopPropagation();setItemStatus(${item.id}, 'accepted_item')">${icon('check','ico--sm')} Aceptar</button>`;
  }

  function renderSummary() {
    const total = summary.total_items || items.length;
    document.getElementById('sumAll').textContent = total;
    document.getElementById('sumExact').textContent = summary.exact || 0;
    document.getElementById('sumSimilar').textContent = summary.similar || 0;
    document.getElementById('sumNotFound').textContent = summary.not_found || 0;
    document.getElementById('sumTotal').textContent = total;
    document.getElementById('sumSale').textContent = money(summary.subtotal_sale);
    document.getElementById('sumProfit').textContent = money(summary.profit);
    document.getElementById('sumMargin').textContent = `${summary.margin || 0}%`;
    document.getElementById('itemsCountText').textContent = total;

    document.querySelectorAll('.filter-summary').forEach((btn) => {
      btn.classList.toggle('active', btn.dataset.filter === currentFilter);
    });
    positionFilterPill();

    const notice = document.getElementById('noticeBox');
    const count = Number(summary.not_found || 0);
    if (count > 0) {
      document.getElementById('noticeCount').textContent = `${count} partidas`;
      notice.classList.add('show');
    } else {
      notice.classList.remove('show');
    }
  }

  function positionFilterPill() {
    const pill = document.getElementById('filterPill');
    const active = document.querySelector('.filter-summary.active');
    if (!pill || !active) return;
    pill.style.width = active.offsetWidth + 'px';
    pill.style.transform = `translateX(${active.offsetLeft - 4}px)`;
  }

  function renderItems() {
    renderSummary();
    const list = document.getElementById('itemsList');
    const filtered = items.filter(item => {
      if (currentFilter === 'all') return true;
      if (currentFilter === 'exact') return item.status_key === 'exact';
      if (currentFilter === 'similar') return item.status_key === 'similar';
      if (currentFilter === 'not_found') return item.status_key === 'not_found';
      return true;
    });
    list.innerHTML = filtered.map((item, idx) => renderItemCard(item, idx)).join('');
    bindDragEvents();
  }

  /* ============ CARD ============ */
  function renderItemCard(item, idx) {
    const badge = statusLabel(item);
    const qty = Number(item.cantidad_cotizada || item.cantidad_maxima || item.cantidad_minima || 1);
    const price = Number(item.precio_unitario || 0);
    const subtotal = Number(item.subtotal || price * qty);
    const score = Number(item.match_score || 0);
    const showSale = subtotal > 0;

    return `
      <div class="q-item ${statusCardClass(item)}" data-id="${item.id}" draggable="${currentFilter === 'all' ? 'true' : 'false'}" style="animation-delay:${Math.min(idx, 12) * 45}ms">
        <div class="q-item__head" onclick="toggleItem(${item.id})">
          <button class="q-item__drag" type="button" title="Mover posición" onclick="event.stopPropagation()">${icon('grip')}</button>
          <div class="q-item__idx">${idx + 1}</div>
          <div class="q-item__id">
            <div class="q-item__name">${escapeHtml(item.descripcion_original || 'Producto sin descripción')}</div>
            <div class="q-item__sub">
              <span>${qty} ${escapeHtml(item.unidad_solicitada || 'pz')}</span>
              ${item.producto_seleccionado?.brand ? `<span>· ${escapeHtml(item.producto_seleccionado.brand)}</span>` : ''}
              ${item.tech_sheet_id ? `<span class="badge badge-soft">${icon('file','ico--sm')} Ficha</span>` : ''}
              <span class="badge ${badge.cls}">${badge.text}</span>
            </div>
          </div>
          <div class="q-item__money"><b>${showSale ? money(subtotal) : 'Por cotizar'}</b>${showSale ? '<span>venta</span>' : ''}</div>
          <div class="q-item__cta">
            ${itemCTA(item)}
            ${score ? `<span class="badge badge-score">${score.toFixed(0)}%</span>` : ''}
            <span class="q-menu-host">
              <button class="btn btn-ghost btn-icon" type="button" onclick="event.stopPropagation();toggleMenu(this.parentElement)">${icon('more')}</button>
              <span class="q-menu">
                <button class="q-menu__item" type="button" onclick="event.stopPropagation();openItemEdit(${item.id})">${icon('edit')} Editar partida</button>
                <button class="q-menu__item" type="button" onclick="event.stopPropagation();suggestItem(${item.id})">${icon('suggest')} Buscar coincidencias</button>
                <button class="q-menu__item" type="button" onclick="event.stopPropagation();openManualModal(${item.id})">${icon('search')} Buscar manualmente</button>
                <button class="q-menu__item" type="button" onclick="event.stopPropagation();openSamplesModal(${item.id})">${icon('package')} Muestras / stock</button>
                <button class="q-menu__item" type="button" onclick="event.stopPropagation();openTechSheetsModal(${item.id})">${icon('file')} Ficha técnica</button>
                <span class="q-menu__sep"></span>
                <button class="q-menu__item" type="button" onclick="event.stopPropagation();setItemStatus(${item.id}, 'manual_review')">${icon('clock')} Marcar en revisión</button>
                <button class="q-menu__item danger" type="button" onclick="event.stopPropagation();setItemStatus(${item.id}, 'rejected_item')">${icon('x')} Rechazar partida</button>
              </span>
            </span>
            <span class="q-item__chev">${icon('chevron','ico--sm')}</span>
          </div>
        </div>
        <div class="q-item__detail"><div class="q-item__detail-inner"><div class="q-detail">${renderItemDetail(item)}</div></div></div>
      </div>
    `;
  }

  /* ============ DETALLE por TABS ============ */
  function renderItemDetail(item) {
    return `
      <div class="q-tabs">
        <button class="q-tabs__btn active" type="button" data-tab="cat">Catálogo</button>
        <button class="q-tabs__btn" type="button" data-tab="net">Internet</button>
        <button class="q-tabs__btn" type="button" data-tab="ficha">Ficha técnica</button>
        <button class="q-tabs__btn" type="button" data-tab="edit">Editar</button>
      </div>
      <div class="q-panel active" data-panel="cat">${renderCatalogPanel(item)}</div>
      <div class="q-panel" data-panel="net">${renderInternetPanel(item)}</div>
      <div class="q-panel" data-panel="ficha">${renderFichaPanel(item)}</div>
      <div class="q-panel" data-panel="edit">${renderEditForm(item)}</div>
    `;
  }

  function renderCatalogPanel(item) {
    if (isManualExternalChosen(item)) {
      return `
        <div class="external-box">
          <div class="result-title">${escapeHtml(item.manual_external_supplier || item.manual_catalog_product_name || 'Proveedor externo')}${item.costo_unitario ? ' · ' + money(item.costo_unitario) : ''}
            <span class="badge badge-info">Referencia externa</span></div>
          ${item.manual_external_link ? `<div class="action-row"><a href="${escapeHtml(item.manual_external_link)}" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-small">${icon('external','ico--sm')} Ver referencia</a></div>` : ''}
          <div class="warning-line">${icon('info','ico--sm')} Precio estimado — validar antes de aprobar. Las demás coincidencias se ocultaron.</div>
        </div>`;
    }
    if (isCatalogAccepted(item)) {
      const sel = getSelectedCatalogProduct(item);
      if (sel) {
        const p = sel.product;
        return `
          <div class="result-card" style="border-color:rgba(74,122,58,.35);">
            <div class="result-title">${escapeHtml(p.name || 'Producto')} <span class="badge badge-success">Aceptado</span></div>
            <div class="result-meta">SKU: ${escapeHtml(p.sku || '—')} · ${escapeHtml(p.brand || '—')} · Stock: ${p.stock ?? '—'}${sel.score ? ' · ' + sel.score.toFixed(0) + '%' : ''}</div>
          </div>`;
      }
    }
    if (!item.matches?.length && !item.producto_seleccionado) {
      return `<div class="result-card"><div class="result-title">Sin coincidencias en catálogo</div>
        <div class="result-meta">Usa la pestaña Internet o la búsqueda manual para encontrar una alternativa.</div></div>`;
    }
    if (item.matches?.length) {
      return item.matches.map((match, i) => {
        const p = match.product || {};
        return `
          <div class="result-card">
            <div class="result-title">${escapeHtml(p.name || 'Producto sin nombre')}${i === 0 ? ' <span class="badge badge-info">Principal</span>' : ''}
              <span class="badge badge-score">${Number(match.score || 0).toFixed(0)}%</span></div>
            <div class="result-meta">SKU: ${escapeHtml(p.sku || '—')} · ${escapeHtml(p.brand || '—')} · Stock: ${p.stock ?? '—'}</div>
            <div class="action-row">
              <button class="btn btn-accept btn-small" type="button" onclick="selectMatch(${item.id}, ${match.id})">${icon('check','ico--sm')} Usar esta</button>
            </div>
          </div>`;
      }).join('');
    }
    return `
      <div class="result-card">
        <div class="result-title">${escapeHtml(item.producto_seleccionado?.name || 'Producto')}</div>
        <div class="result-meta">SKU: ${escapeHtml(item.producto_seleccionado?.sku || '—')} · ${escapeHtml(item.producto_seleccionado?.brand || '—')} · Stock: ${item.producto_seleccionado?.stock ?? 0}</div>
      </div>`;
  }

  function renderInternetPanel(item) {
    if (isCatalogAccepted(item) || isManualExternalChosen(item)) {
      return `<div class="result-meta">Esta partida ya tiene una decisión tomada. Las opciones de internet están ocultas.</div>`;
    }
    if (!item.external_matches?.length) {
      if (item.status_key === 'not_found') {
        return `
          <div class="warning-line">${icon('warning','ico--sm')} Producto no disponible en catálogo interno.</div>
          <div class="action-row"><button class="btn btn-outline btn-small" type="button" onclick="openManualModal(${item.id})">${icon('search','ico--sm')} Buscar en internet</button></div>`;
      }
      return `<div class="result-meta">Aún no hay referencias de internet para esta partida.</div>`;
    }
    return item.external_matches.map(external => `
      <div class="external-box">
        <div class="result-title">${escapeHtml(external.title)} <span class="badge badge-score">${Number(external.score || 0).toFixed(0)}%</span></div>
        <div class="result-meta">${escapeHtml(external.source || 'Internet')}${external.seller ? ' · ' + escapeHtml(external.seller) : ''}</div>
        <div class="action-row"><a class="btn btn-outline btn-small" href="${escapeHtml(external.url)}" target="_blank" rel="noopener noreferrer">${icon('external','ico--sm')} Ver referencia</a></div>
      </div>`).join('');
  }

  function renderFichaPanel(item) {
    if (!item.tech_sheet_id) {
      return `
        <div class="result-card">
          <div class="result-title">Sin ficha técnica vinculada</div>
          <div class="result-meta">Vincula una ficha existente o crea una nueva para esta partida.</div>
          <div class="action-row"><button class="btn btn-primary btn-small" type="button" onclick="openTechSheetsModal(${item.id})">${icon('file','ico--sm')} Vincular ficha</button></div>
        </div>`;
    }
    const pdfUrl = urlFor(routes.techSheetPdf, item.tech_sheet_id);
    return `
      <div class="result-card">
        <div class="result-title">${icon('file','ico--sm')} ${escapeHtml(item.tech_sheet_name || 'Ficha técnica')} <span class="badge badge-success">Vinculada</span></div>
        <div class="action-row">
          <button class="btn btn-soft btn-small" type="button" onclick="toggleTechPreview(${item.id}, '${pdfUrl}')">${icon('eye','ico--sm')} Ver ficha</button>
          <a class="btn btn-outline btn-small" target="_blank" rel="noopener noreferrer" href="${pdfUrl}">${icon('external','ico--sm')} Abrir PDF</a>
          <button class="btn btn-ghost btn-small" type="button" onclick="openTechSheetsModal(${item.id})">Cambiar / editar</button>
        </div>
        <div id="tech-preview-${item.id}" class="tech-preview" style="display:none; margin-top:12px;"></div>
      </div>`;
  }

  function toggleTechPreview(itemId, pdfUrl) {
    const box = document.getElementById(`tech-preview-${itemId}`);
    if (!box) return;
    const isHidden = box.style.display === 'none' || box.style.display === '';
    if (isHidden) {
      box.innerHTML = `<iframe src="${pdfUrl}" title="Ficha técnica"></iframe>`;
      box.style.display = 'block';
      box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } else {
      box.style.display = 'none';
      box.innerHTML = '';
    }
  }

  function renderEditForm(item) {
    return `
      <form class="q-edit-grid" id="edit-form-${item.id}" onsubmit="saveItem(event, ${item.id})">
        <div class="field"><label>Producto</label><input class="input" name="descripcion_original" value="${escapeHtml(item.descripcion_original || '')}"></div>
        <div class="field"><label>Cantidad</label><input class="input" type="number" step="0.01" name="cantidad_cotizada" value="${Number(item.cantidad_cotizada || 1)}"></div>
        <div class="field"><label>Unidad</label><input class="input" name="unidad_solicitada" value="${escapeHtml(item.unidad_solicitada || '')}"></div>
        <div class="field"><label>Costo unit.</label><input class="input" type="number" step="0.01" name="costo_unitario" value="${Number(item.costo_unitario || 0)}"></div>
        <div class="field"><label>Margen %</label><input class="input" type="number" step="0.01" name="porcentaje_utilidad" value="${Number(item.item_margin_pct || 25)}"></div>
        <div class="field" style="grid-column:span 2;"><label>Proveedor</label><input class="input" name="external_supplier" value="${escapeHtml(item.manual_external_supplier || '')}"></div>
        <div class="field" style="grid-column:span 2;"><label>Link de referencia</label><input class="input" name="external_link" value="${escapeHtml(item.manual_external_link || '')}"></div>
        <div class="action-row" style="grid-column:1/-1;">
          <button class="btn btn-primary btn-small" type="submit">${icon('check','ico--sm')} Guardar</button>
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
    const card = document.querySelector(`.q-page .q-item[data-id="${id}"]`);
    if (card) card.classList.toggle('open');
  }
  function openItemDetail(id) {
    const card = document.querySelector(`.q-page .q-item[data-id="${id}"]`);
    if (card) card.classList.add('open');
  }
  function setCardTab(id, tab) {
    const card = document.querySelector(`.q-page .q-item[data-id="${id}"]`);
    if (!card) return;
    card.querySelectorAll('.q-tabs__btn').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
    card.querySelectorAll('.q-panel').forEach(p => p.classList.toggle('active', p.dataset.panel === tab));
  }
  function openItemEdit(id) {
    openItemDetail(id);
    setCardTab(id, 'edit');
  }

  async function suggestItem(id) {
    const button = event?.target?.closest('button');
    const old = button?.innerHTML;
    if (button) { button.disabled = true; button.innerHTML = '<span class="loader"></span> Buscando...'; }
    try {
      const data = await ajax(urlFor(routes.suggestItem, id), { method: 'POST', body: '{}' });
      updateItemInState(data.item);
      summary = data.summary || summary;
      renderItems();
      openItemDetail(id);
    } catch (e) {
      showInlineError(e.message);
    } finally {
      if (button) { button.disabled = false; button.innerHTML = old; }
    }
  }

  async function selectMatch(itemId, matchId) {
    try {
      const url = routes.selectMatch.replace('__ID__', itemId).replace('__MATCH__', matchId);
      const data = await ajax(url, { method: 'POST', body: '{}' });
      updateItemInState(data.item);
      summary = data.summary || summary;
      renderItems();
      openItemDetail(itemId);
    } catch (e) { showInlineError(e.message); }
  }

  async function setItemStatus(id, status) {
    try {
      const data = await ajax(urlFor(routes.updateStatus, id), { method: 'POST', body: JSON.stringify({ ui_status: status }) });
      updateItemInState(data.item);
      summary = data.summary || summary;
      renderItems();
      openItemDetail(id);
    } catch (e) { showInlineError(e.message); }
  }

  async function saveItem(event, id) {
    event.preventDefault();
    const form = event.target;
    const payload = Object.fromEntries(new FormData(form).entries());
    try {
      const data = await ajax(urlFor(routes.updateItem, id), { method: 'POST', body: JSON.stringify(payload) });
      updateItemInState(data.item);
      summary = data.summary || summary;
      renderItems();
      openItemDetail(id);
    } catch (e) { showInlineError(e.message); }
  }

  async function suggestAll() {
    if (isSuggestingAll) return;
    const button = document.getElementById('btnSuggestAll');
    const old = button.innerHTML;
    const pendingItems = items.filter(item => item.status_key !== 'exact');
    if (!pendingItems.length) {
      showProcessBox('success', 'No hay partidas pendientes', 'Todas las partidas ya tienen coincidencia exacta o ya fueron procesadas.', items.length, items.length, []);
      setTimeout(hideProcessBox, 3500);
      return;
    }
    isSuggestingAll = true;
    button.disabled = true;
    button.innerHTML = '<span class="loader"></span> Procesando...';
    const total = pendingItems.length;
    let done = 0, success = 0, cursor = 0;
    const errors = [];
    const concurrency = 4;
    showProcessBox('', 'Buscando coincidencias por lotes', `Procesando ${total} partidas sin saturar el servidor...`, done, total, errors);
    const box = document.getElementById('processBox');
    if (box) box.scrollIntoView({ behavior: 'smooth', block: 'center' });

    async function worker() {
      while (cursor < pendingItems.length) {
        const currentIndex = cursor++;
        const item = pendingItems[currentIndex];
        try {
          const data = await ajax(urlFor(routes.suggestItem, item.id), { method: 'POST', body: '{}' });
          if (data.item) updateItemInState(data.item);
          if (data.summary) summary = data.summary;
          success++;
        } catch (error) {
          errors.push(`Partida #${item.sort || currentIndex + 1}: ${error.message || 'No se pudo procesar.'}`);
        } finally {
          done++;
          showProcessBox(errors.length ? 'error' : '', 'Buscando coincidencias por lotes', `Procesadas ${done} de ${total}. Correctas: ${success}. Errores: ${errors.length}.`, done, total, errors);
          if (done % 5 === 0 || done === total) renderItems();
        }
      }
    }
    try {
      await Promise.all(Array.from({ length: Math.min(concurrency, pendingItems.length) }, () => worker()));
      renderItems();
      if (errors.length) {
        showProcessBox('error', 'Proceso terminado con algunos errores', `Se procesaron ${success} partidas correctamente y ${errors.length} fallaron. Puedes volver a intentar; se saltarán las exactas.`, done, total, errors);
      } else {
        showProcessBox('success', 'Coincidencias completadas', `Se procesaron ${success} partidas correctamente.`, done, total, []);
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
      const data = await ajax(routes.globalMargin, { method: 'POST', body: JSON.stringify({ porcentaje_utilidad: margin, apply_to_items: applyToItems }) });
      items = mergeTechSheetMeta(data.items) || items;
      summary = data.summary || summary;
      renderItems();
    } catch (e) { showInlineError(e.message); }
  }

  /* ============ BÚSQUEDA MANUAL ============ */
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
  function closeManualModal() { document.getElementById('manualModal').classList.remove('show'); }
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
    if (!q) { resultsBox.innerHTML = ''; statusBox.textContent = 'Escribe para buscar automáticamente.'; return; }
    const cacheKey = manualTab + '::' + q;
    if (cacheKey === manualLastQuery) return;
    manualLastQuery = cacheKey;
    statusBox.innerHTML = '<span class="loader"></span> Buscando similitudes...';
    try {
      const params = new URLSearchParams({ q, item_id: manualItemId, internet: manualTab === 'internet' ? '1' : '0' });
      const data = await ajax(routes.manualSearch + '?' + params.toString(), { method: 'GET', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } });
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
    if (!products.length) { box.innerHTML = '<p class="result-meta">Sin resultados similares en catálogo.</p>'; return; }
    box.innerHTML = products.map((p, index) => `
      <div class="modal-result">
        <div style="min-width:0;">
          <div class="result-title">${escapeHtml(p.name)} <span class="badge badge-score">${Number(p.similarity_pct || 0).toFixed(0)}%</span></div>
          <div class="result-meta">SKU: ${escapeHtml(p.sku || '—')} · ${escapeHtml(p.brand || '—')} · Stock: ${p.stock ?? 0}</div>
          <div class="result-meta">${p.unit ? `Unidad: ${escapeHtml(p.unit)} · ` : ''}${p.color ? `Color: ${escapeHtml(p.color)} · ` : ''}${p.category ? `Categoría: ${escapeHtml(p.category)} · ` : ''}Costo ${money(p.cost)} · Precio ${money(p.price)}</div>
        </div>
        <button class="btn btn-primary btn-small" type="button" onclick="useManualCatalog(${index})">Usar</button>
      </div>`).join('');
  }
  function renderManualInternet(results) {
    const box = document.getElementById('manualResults');
    if (!results.length) { box.innerHTML = '<p class="result-meta">Sin resultados de internet.</p>'; return; }
    box.innerHTML = results.map((r, index) => `
      <div class="modal-result">
        <div style="min-width:0;">
          <div class="result-title">${escapeHtml(r.title)} <span class="badge badge-score">${Number(r.score || 0).toFixed(0)}%</span></div>
          <div class="result-meta">${escapeHtml(r.source || 'Internet')}${r.seller ? ' · ' + escapeHtml(r.seller) : ''}</div>
          <div class="result-meta">${r.price ? money(r.price) : 'Precio por validar'}</div>
          ${r.url ? `<div class="action-row"><a class="btn btn-outline btn-small" target="_blank" rel="noopener noreferrer" href="${escapeHtml(r.url)}">${icon('external','ico--sm')} Ver referencia</a></div>` : ''}
        </div>
        <button class="btn btn-primary btn-small" type="button" onclick="useManualInternet(${index})">Usar</button>
      </div>`).join('');
  }
  async function useManualCatalog(index) {
    const product = manualCatalogResults[index];
    if (!product) return;
    const item = items.find(i => i.id === manualItemId);
    const margin = Number(item?.item_margin_pct || 25);
    const cost = Number(product.cost || 0);
    try {
      const data = await ajax(urlFor(routes.updateItem, manualItemId), { method: 'POST', body: JSON.stringify({ catalog_product_name: product.name, costo_unitario: cost, porcentaje_utilidad: margin, external_supplier: product.brand || '', external_link: '' }) });
      updateItemInState(data.item);
      summary = data.summary || summary;
      closeManualModal();
      renderItems();
      openItemDetail(manualItemId);
    } catch (e) { showInlineError(e.message); }
  }
  async function useManualInternet(index) {
    const result = manualInternetResults[index];
    if (!result) return;
    const item = items.find(i => i.id === manualItemId);
    const margin = Number(item?.item_margin_pct || 25);
    const cost = Number(result.price || 0);
    try {
      const data = await ajax(urlFor(routes.updateItem, manualItemId), { method: 'POST', body: JSON.stringify({ catalog_product_name: result.title, costo_unitario: cost, porcentaje_utilidad: margin, external_supplier: result.source || result.seller || 'Proveedor externo', external_link: result.url || '' }) });
      updateItemInState(data.item);
      summary = data.summary || summary;
      closeManualModal();
      renderItems();
      openItemDetail(manualItemId);
    } catch (e) { showInlineError(e.message); }
  }

  /* ============ AGREGAR PARTIDA ============ */
  function openAddItemModal() { document.getElementById('addItemModal').classList.add('show'); }
  function closeAddItemModal() { document.getElementById('addItemModal').classList.remove('show'); }
  async function storeNewItem(event) {
    event.preventDefault();
    const form = event.target;
    const payload = Object.fromEntries(new FormData(form).entries());
    const submit = form.querySelector('button[type="submit"]');
    const old = submit.innerHTML;
    submit.disabled = true;
    submit.innerHTML = '<span class="loader"></span> Agregando...';
    try {
      const data = await ajax(routes.storeItem, { method: 'POST', body: JSON.stringify(payload) });
      items = mergeTechSheetMeta(data.items) || items;
      summary = data.summary || summary;
      closeAddItemModal();
      form.reset();
      renderItems();
    } catch (e) { showInlineError(e.message); }
    finally { submit.disabled = false; submit.innerHTML = old; }
  }

  /* ============ MUESTRAS ============ */
  function closeSamplesModal() { document.getElementById('samplesModal').classList.remove('show'); }
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
    } catch (e) { document.getElementById('samplesStatus').textContent = e.message; }
  }
  function renderSamples(data) {
    const needed = Number(data.needed_qty || 0);
    const cands = data.candidates || [];
    document.getElementById('samplesStatus').textContent = `Cantidad solicitada: ${needed} · ${cands.length} coincidencias en catálogo`;
    const box = document.getElementById('samplesResults');
    if (!cands.length) { box.innerHTML = '<p class="result-meta">No se encontraron productos similares en el catálogo interno.</p>'; return; }
    box.innerHTML = cands.map(c => {
      const locs = (c.locations || []).map(l => `${escapeHtml(l.location)}: ${l.qty}${l.reserved ? ' (apartado ' + l.reserved + ')' : ''}`).join(' · ');
      const buyBadge = c.to_buy > 0 ? `<span class="badge badge-danger">Comprar ${c.to_buy}</span>` : `<span class="badge badge-success">Stock suficiente</span>`;
      return `
        <div class="result-card">
          <div class="result-title">${escapeHtml(c.name)} ${buyBadge}</div>
          <div class="result-meta">SKU: ${escapeHtml(c.sku || '—')} · ${escapeHtml(c.unit || '')} · Similitud ${Number(c.similarity_pct || 0).toFixed(0)}%</div>
          <div class="result-meta">En almacén: ${c.net_available} · Apartado: ${c.reserved} · Necesario: ${needed} · Faltan: ${c.to_buy}</div>
          ${locs ? `<div class="result-meta">Ubicaciones: ${locs}</div>` : `<div class="result-meta">Sin inventario por ubicación (stock general: ${c.stock_field}).</div>`}
        </div>`;
    }).join('');
  }

  /* ============ FICHAS TÉCNICAS ============ */
  function closeTechSheetsModal() { document.getElementById('techSheetsModal').classList.remove('show'); }
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
    } catch (e) { document.getElementById('techStatus').textContent = e.message; }
  }
  function renderTechSheets(data) {
    techSheetsCache = data.sheets || [];
    currentLinkedSheetId = data.linked_id || null;
    document.getElementById('techStatus').textContent = `${techSheetsCache.length} fichas encontradas`;
    const box = document.getElementById('techResults');
    if (!techSheetsCache.length) { box.innerHTML = '<p class="result-meta">No hay fichas. Crea una nueva en la pestaña de arriba.</p>'; return; }
    box.innerHTML = techSheetsCache.map((s, i) => `
      <div class="modal-result">
        <div style="min-width:0;">
          <div class="result-title">${escapeHtml(s.product_name)}${s.id === currentLinkedSheetId ? ' <span class="badge badge-success">Vinculada</span>' : ''}</div>
          <div class="result-meta">${escapeHtml(s.brand || '—')}${s.model ? ' · ' + escapeHtml(s.model) : ''}${s.reference ? ' · Ref ' + escapeHtml(s.reference) : ''}</div>
          <div class="action-row">
            <a class="btn btn-outline btn-small" target="_blank" rel="noopener noreferrer" href="${s.urls.pdf}">${icon('external','ico--sm')} PDF</a>
            ${s.urls.public ? `<a class="btn btn-ghost btn-small" target="_blank" rel="noopener noreferrer" href="${s.urls.public}">Ficha pública</a>` : ''}
            <button class="btn btn-ghost btn-small" type="button" onclick="techEditInline(${i})">${icon('edit','ico--sm')} Editar</button>
          </div>
        </div>
        <button class="btn btn-primary btn-small" type="button" onclick="linkTechSheet(${s.id})">${s.id === currentLinkedSheetId ? 'Quitar' : 'Vincular'}</button>
      </div>`).join('');
  }
  function techEditInline(index) { techShowCreate(techSheetsCache[index]); }
  async function linkTechSheet(sheetId) {
    const unlink = sheetId === currentLinkedSheetId;
    try {
      const data = await ajax(urlFor(routes.linkTechSheet, techItemId), { method: 'POST', body: JSON.stringify({ tech_sheet_id: unlink ? null : sheetId }) });
      const idx = items.findIndex(i => i.id === techItemId);
      if (idx >= 0) {
        if (unlink) { items[idx].tech_sheet_id = null; items[idx].tech_sheet_name = null; }
        else {
          const s = techSheetsCache.find(x => x.id === sheetId);
          items[idx].tech_sheet_id = sheetId;
          items[idx].tech_sheet_name = s ? s.product_name : items[idx].tech_sheet_name;
        }
      }
      renderItems();
      openItemDetail(techItemId);
      setCardTab(techItemId, 'ficha');
      loadTechSheets();
    } catch (e) { showInlineError(e.message); }
  }
  async function submitTechSheet(event) {
    event.preventDefault();
    const form = event.target;
    const fd = new FormData(form);
    const id = document.getElementById('techFormId').value;
    const url = id ? routes.updateTechSheet.replace('__ID__', id) : urlFor(routes.createTechSheet, techItemId);
    const submit = form.querySelector('button[type="submit"]');
    const old = submit.innerHTML;
    submit.disabled = true;
    submit.innerHTML = '<span class="loader"></span> Guardando...';
    try {
      const resp = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: fd });
      const text = await resp.text();
      let data = null;
      try { data = JSON.parse(text); } catch (_) {}
      if (!resp.ok || !data || data.ok === false) throw new Error((data && data.message) || ('Error al guardar la ficha. ' + text.slice(0, 200)));
      if (!id) {
        const idx = items.findIndex(i => i.id === techItemId);
        if (idx >= 0 && data.sheet) { items[idx].tech_sheet_id = data.sheet.id; items[idx].tech_sheet_name = data.sheet.product_name; }
      }
      techShowList();
      document.getElementById('techQueryInput').value = (data.sheet && data.sheet.product_name) || '';
      renderItems();
      openItemDetail(techItemId);
      setCardTab(techItemId, 'ficha');
      loadTechSheets();
    } catch (e) { showInlineError(e.message); }
    finally { submit.disabled = false; submit.innerHTML = old; }
  }

  /* ============ DRAG & DROP ============ */
  function bindDragEvents() {
    document.querySelectorAll('.q-page .q-item').forEach(card => {
      card.addEventListener('dragstart', () => { if (currentFilter !== 'all') return; card.classList.add('dragging'); });
      card.addEventListener('dragend', () => { if (currentFilter !== 'all') return; card.classList.remove('dragging'); saveOrder(); });
      card.addEventListener('dragover', (e) => {
        if (currentFilter !== 'all') return;
        e.preventDefault();
        const list = document.getElementById('itemsList');
        const dragging = document.querySelector('.q-page .dragging');
        const after = getDragAfterElement(list, e.clientY);
        if (!dragging) return;
        if (after == null) list.appendChild(dragging); else list.insertBefore(dragging, after);
      });
    });
  }
  function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.q-item:not(.dragging)')];
    return draggableElements.reduce((closest, child) => {
      const box = child.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;
      if (offset < 0 && offset > closest.offset) return { offset, element: child };
      return closest;
    }, { offset: Number.NEGATIVE_INFINITY }).element;
  }
  async function saveOrder() {
    if (currentFilter !== 'all') return;
    const ids = [...document.querySelectorAll('#itemsList .q-item')].map(card => Number(card.dataset.id));
    if (!ids.length) return;
    try {
      const data = await ajax(routes.reorder, { method: 'POST', body: JSON.stringify({ items: ids }) });
      items = mergeTechSheetMeta(data.items) || items;
      summary = data.summary || summary;
      renderItems();
    } catch (e) { showInlineError(e.message); }
  }

  /* ============ EXPORTACIONES (sin cambios de lógica) ============ */
  function getQuoteFileName(extension) {
    const safeFolio = String(exportFolio || 'cotizacion').replace(/[^\w\-]+/g, '_').replace(/_+/g, '_');
    return `${safeFolio}_tabla_extraida_pdf.${extension}`;
  }
  function isPlainObject(value) { return value && typeof value === 'object' && !Array.isArray(value); }
  function normalizeCell(value) {
    if (value === null || value === undefined) return '';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value);
  }
  function normalizeRows(rows) {
    if (!Array.isArray(rows) || rows.length === 0) return null;
    if (isPlainObject(rows[0])) {
      const columns = [];
      rows.forEach(row => { if (!isPlainObject(row)) return; Object.keys(row).forEach(key => { if (!columns.includes(key)) columns.push(key); }); });
      if (!columns.length) return null;
      return { columns, rows: rows.filter(isPlainObject).map(row => { const out = {}; columns.forEach(column => out[column] = normalizeCell(row[column])); return out; }) };
    }
    if (Array.isArray(rows[0])) {
      const max = rows.reduce((acc, row) => Array.isArray(row) ? Math.max(acc, row.length) : acc, 0);
      if (!max) return null;
      const columns = Array.from({ length: max }, (_, index) => `Columna ${index + 1}`);
      return { columns, rows: rows.filter(Array.isArray).map(row => { const out = {}; columns.forEach((column, index) => out[column] = normalizeCell(row[index])); return out; }) };
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
        if (normalized && normalized.rows.length) tables.push({ title: path || 'Tabla extraída', source, columns: normalized.columns, rows: normalized.rows });
        value.forEach((child, index) => walk(child, `${path} ${index + 1}`.trim()));
        return;
      }
      tableKeys.forEach(key => {
        if (!Object.prototype.hasOwnProperty.call(value, key)) return;
        const candidate = value[key];
        if (candidate && typeof candidate === 'object') {
          if (isPlainObject(candidate) && Array.isArray(candidate.columns) && Array.isArray(candidate.rows)) {
            const rows = candidate.rows.map(row => {
              if (Array.isArray(row)) { const out = {}; candidate.columns.forEach((column, index) => out[column] = normalizeCell(row[index])); return out; }
              if (isPlainObject(row)) return row;
              return null;
            }).filter(Boolean);
            const normalized = normalizeRows(rows);
            if (normalized) tables.push({ title: key, source, columns: normalized.columns, rows: normalized.rows });
          } else {
            const normalized = normalizeRows(candidate);
            if (normalized) tables.push({ title: key, source, columns: normalized.columns, rows: normalized.rows });
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
    Object.entries(rawExportPayloads || {}).forEach(([source, payload]) => { collectExtractedTables(payload, source).forEach(table => tables.push(table)); });
    if (tables.length) return tables;
    return [{
      title: 'Partidas normalizadas', source: 'fallback_items',
      columns: ['descripcion_original', 'unidad_solicitada', 'cantidad_minima', 'cantidad_maxima', 'cantidad_cotizada', 'costo_unitario', 'precio_unitario', 'subtotal'],
      rows: items.map(item => ({
        descripcion_original: item.descripcion_original || '', unidad_solicitada: item.unidad_solicitada || '',
        cantidad_minima: item.cantidad_minima || '', cantidad_maxima: item.cantidad_maxima || '',
        cantidad_cotizada: item.cantidad_cotizada || '', costo_unitario: item.costo_unitario || '',
        precio_unitario: item.precio_unitario || '', subtotal: item.subtotal || ''
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
      return `<div class="table-block"><h2>${escapeHtml(table.title || ('Tabla extraída ' + (tableIndex + 1)))}</h2>
        <div class="table-meta">Fuente: ${escapeHtml(table.source || 'PDF')} · Filas: ${rows.length} · Columnas: ${columns.length}</div>
        <table><thead><tr>${thead}</tr></thead><tbody>${tbody || `<tr><td colspan="${Math.max(columns.length, 1)}">Sin filas extraídas.</td></tr>`}</tbody></table></div>`;
    }).join('');
    return `<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>${escapeHtml(exportTitle)}</title>
      <style>body{font-family:Arial,sans-serif;color:#333;background:#fff;margin:24px}h1{color:#111;font-size:22px;margin:0 0 6px}h2{color:#111;font-size:16px;margin:22px 0 6px}.meta,.table-meta{color:#666;font-size:12px;margin-bottom:12px}.table-block{margin-top:18px;page-break-inside:avoid}table{width:100%;border-collapse:collapse;font-size:11px;margin-bottom:18px}th{background:#f9fafb;color:#111;font-weight:700;border:1px solid #ebebeb;padding:8px;text-align:left;vertical-align:top}td{border:1px solid #ebebeb;padding:7px;vertical-align:top}tr:nth-child(even) td{background:#fcfcfc}</style></head>
      <body><h1>${escapeHtml(exportTitle)}</h1><div class="meta">Folio: ${escapeHtml(exportFolio)} · Generado: ${escapeHtml(generatedAt)} · Exportación basada en tabla extraída del PDF</div>${tablesHtml || '<p>No se encontraron tablas para exportar.</p>'}</body></html>`;
  }
  function downloadBlob(content, fileName, mimeType) {
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = fileName;
    document.body.appendChild(a); a.click();
    document.body.removeChild(a); URL.revokeObjectURL(url);
  }
  function exportExtractedTablesToExcel() {
    const html = buildExtractedTablesHtml();
    downloadBlob(`<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>${html}</body></html>`, getQuoteFileName('xls'), 'application/vnd.ms-excel;charset=utf-8');
  }
  function exportExtractedTablesToWord() {
    const title = exportTitle, folio = exportFolio;
    const generatedAt = new Date().toLocaleString('es-MX');
    const tables = getExportTables();
    const tablesHtml = tables.map((table, tableIndex) => {
      const columns = Array.isArray(table.columns) ? table.columns : [];
      const rows = Array.isArray(table.rows) ? table.rows : [];
      const thead = columns.map(column => `<th>${escapeHtml(column)}</th>`).join('');
      const tbody = rows.map(row => `<tr>${columns.map(column => `<td>${escapeHtml(row?.[column] ?? '')}</td>`).join('')}</tr>`).join('');
      return `<div class="table-block"><h2>${escapeHtml(table.title || ('Tabla extraída ' + (tableIndex + 1)))}</h2>
        <div class="table-meta">Fuente: ${escapeHtml(table.source || 'PDF')} · Filas: ${rows.length} · Columnas: ${columns.length}</div>
        <table><thead><tr>${thead}</tr></thead><tbody>${tbody || `<tr><td colspan="${Math.max(columns.length, 1)}">Sin filas extraídas.</td></tr>`}</tbody></table></div>`;
    }).join('');
    const wordContent = `<!DOCTYPE html><html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="UTF-8"><title>${escapeHtml(title)}</title>
      <style>@page WordSection1{size:11in 8.5in;mso-page-orientation:landscape;margin:0.35in}div.WordSection1{page:WordSection1}body{font-family:Arial,sans-serif;color:#333;background:#fff;margin:0}h1{color:#111;font-size:18pt;margin:0 0 4pt;font-weight:700}h2{color:#111;font-size:11pt;margin:14pt 0 4pt;font-weight:700}.meta,.table-meta{color:#666;font-size:8pt;margin-bottom:8pt}.table-block{margin-top:12pt;page-break-inside:avoid}table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:7pt;margin-bottom:12pt}th{background:#f3f4f6;color:#111;font-weight:700;border:1px solid #d9d9d9;padding:4pt;text-align:left;vertical-align:top;word-wrap:break-word}td{border:1px solid #e5e5e5;padding:3pt 4pt;vertical-align:top;word-wrap:break-word}tr:nth-child(even) td{background:#fafafa}</style></head>
      <body><div class="WordSection1"><h1>${escapeHtml(title)}</h1><div class="meta">Folio: ${escapeHtml(folio)} · Generado: ${escapeHtml(generatedAt)} · Exportación basada en tabla extraída del PDF</div>${tablesHtml || '<p>No se encontraron tablas para exportar.</p>'}</div></body></html>`;
    downloadBlob(wordContent, getQuoteFileName('doc'), 'application/msword;charset=utf-8');
  }

  /* ============ MENÚS (overflow / popover) ============ */
  function toggleMenu(ref) {
    const host = typeof ref === 'string' ? document.getElementById(ref) : ref;
    if (!host) return;
    const wasOpen = host.classList.contains('open');
    document.querySelectorAll('.q-menu-host.open').forEach(h => h.classList.remove('open'));
    if (!wasOpen) host.classList.add('open');
  }
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.q-menu-host')) document.querySelectorAll('.q-menu-host.open').forEach(h => h.classList.remove('open'));
  });

  /* ============ TABS dentro de la card (delegado) ============ */
  document.getElementById('itemsList').addEventListener('click', (e) => {
    const t = e.target.closest('.q-tabs__btn');
    if (!t) return;
    const detail = t.closest('.q-detail');
    detail.querySelectorAll('.q-tabs__btn').forEach(b => b.classList.toggle('active', b === t));
    detail.querySelectorAll('.q-panel').forEach(p => p.classList.toggle('active', p.dataset.panel === t.dataset.tab));
  });

  /* ============ LISTENERS GLOBALES ============ */
  document.getElementById('btnSuggestAll').addEventListener('click', suggestAll);
  document.getElementById('btnOpenAddItem').addEventListener('click', openAddItemModal);
  document.getElementById('btnSaveGlobalMargin').addEventListener('click', () => saveGlobalMargin(false));
  document.getElementById('btnApplyGlobalMargin').addEventListener('click', () => saveGlobalMargin(true));
  document.getElementById('btnExportExcel')?.addEventListener('click', exportExtractedTablesToExcel);
  document.getElementById('btnExportWord')?.addEventListener('click', exportExtractedTablesToWord);

  document.getElementById('manualQueryInput').addEventListener('input', () => { manualLastQuery = ''; scheduleManualSearch(420); });
  document.getElementById('manualQueryInput').addEventListener('keydown', (event) => {
    if (event.key === 'Enter') { event.preventDefault(); manualLastQuery = ''; scheduleManualSearch(10); }
  });
  document.getElementById('manualTabCatalog').addEventListener('click', () => {
    manualTab = 'catalog'; manualLastQuery = '';
    document.getElementById('manualTabCatalog').classList.add('active');
    document.getElementById('manualTabInternet').classList.remove('active');
    scheduleManualSearch(10);
  });
  document.getElementById('manualTabInternet').addEventListener('click', () => {
    manualTab = 'internet'; manualLastQuery = '';
    document.getElementById('manualTabInternet').classList.add('active');
    document.getElementById('manualTabCatalog').classList.remove('active');
    scheduleManualSearch(10);
  });
  document.getElementById('techQueryInput').addEventListener('input', () => {
    clearTimeout(window.__techTimer);
    window.__techTimer = setTimeout(loadTechSheets, 350);
  });

  document.querySelectorAll('.filter-summary').forEach(btn => {
    btn.addEventListener('click', () => { currentFilter = btn.dataset.filter || 'all'; renderItems(); });
  });
  window.addEventListener('resize', positionFilterPill);

  /* ============ ATAJOS DE TECLADO (sobre la card abierta) ============ */
  document.addEventListener('keydown', (e) => {
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) return;
    if (document.querySelector('.modal-backdrop.show')) return;
    const openCard = document.querySelector('.q-page .q-item.open');
    if (!openCard) return;
    const id = Number(openCard.dataset.id);
    const k = e.key.toLowerCase();
    if (k === 'a') { e.preventDefault(); setItemStatus(id, 'accepted_item'); }
    else if (k === 'r') { e.preventDefault(); setItemStatus(id, 'rejected_item'); }
    else if (k === 'e') { e.preventDefault(); openItemEdit(id); }
    else if (k === 'b') { e.preventDefault(); suggestItem(id); }
  });

  renderItems();
</script>
@endsection