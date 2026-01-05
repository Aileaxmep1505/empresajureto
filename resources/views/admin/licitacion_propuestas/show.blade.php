@extends('layouts.app')

@section('title', $propuesta->codigo.' - Propuesta económica')

@section('content')
@php
  use Illuminate\Support\Facades\Route;

  // ===================== RUTAS =====================
  $routeShow = Route::has('admin.licitacion-propuestas.show')
    ? 'admin.licitacion-propuestas.show'
    : (Route::has('licitacion-propuestas.show') ? 'licitacion-propuestas.show' : null);

  $routeApply = Route::has('admin.licitacion-propuestas.items.apply')
    ? 'admin.licitacion-propuestas.items.apply'
    : (Route::has('licitacion-propuestas.items.apply') ? 'licitacion-propuestas.items.apply' : null);

  $routeReject = Route::has('admin.licitacion-propuestas.items.reject')
    ? 'admin.licitacion-propuestas.items.reject'
    : (Route::has('licitacion-propuestas.items.reject') ? 'licitacion-propuestas.items.reject' : null);

  $routeMerge = Route::has('admin.licitacion-propuestas.merge')
    ? 'admin.licitacion-propuestas.merge'
    : (Route::has('licitacion-propuestas.merge') ? 'licitacion-propuestas.merge' : null);

  $routeProcessSplit = Route::has('admin.licitacion-propuestas.splits.process')
    ? 'admin.licitacion-propuestas.splits.process'
    : (Route::has('licitacion-propuestas.splits.process') ? 'licitacion-propuestas.splits.process' : null);

  // AJAX productos
  $routeProductsSearch = Route::has('admin.products.search')
    ? 'admin.products.search'
    : (Route::has('products.search') ? 'products.search' : null);

  $routeApplyAjaxName = Route::has('admin.licitacion-propuesta-items.apply-product')
    ? 'admin.licitacion-propuesta-items.apply-product'
    : (Route::has('licitacion-propuesta-items.apply-product') ? 'licitacion-propuesta-items.apply-product' : null);

  // AJAX utilidad por renglón
  $routeUtilityAjaxName = Route::has('admin.licitacion-propuesta-items.update-utility')
    ? 'admin.licitacion-propuesta-items.update-utility'
    : (Route::has('licitacion-propuesta-items.update-utility') ? 'licitacion-propuesta-items.update-utility' : null);

  // CRUD renglones (para modal)
  $routeItemStore = Route::has('admin.licitacion-propuestas.items.store')
    ? 'admin.licitacion-propuestas.items.store'
    : (Route::has('licitacion-propuestas.items.store') ? 'licitacion-propuestas.items.store' : null);

  $routeItemUpdate = Route::has('admin.licitacion-propuesta-items.update')
    ? 'admin.licitacion-propuesta-items.update'
    : (Route::has('licitacion-propuesta-items.update') ? 'licitacion-propuesta-items.update' : null);

  $routeItemDestroy = Route::has('admin.licitacion-propuesta-items.destroy')
    ? 'admin.licitacion-propuesta-items.destroy'
    : (Route::has('licitacion-propuesta-items.destroy') ? 'licitacion-propuesta-items.destroy' : null);

  // Exportar PDF (soporta nombres tipo export.pdf y export-pdf)
  $routeExportPdf = null;
  if (Route::has('admin.licitacion-propuestas.export.pdf')) {
      $routeExportPdf = 'admin.licitacion-propuestas.export.pdf';
  } elseif (Route::has('admin.licitacion-propuestas.export-pdf')) {
      $routeExportPdf = 'admin.licitacion-propuestas.export-pdf';
  } elseif (Route::has('licitacion-propuestas.export.pdf')) {
      $routeExportPdf = 'licitacion-propuestas.export.pdf';
  } elseif (Route::has('licitacion-propuestas.export-pdf')) {
      $routeExportPdf = 'licitacion-propuestas.export-pdf';
  }

  // Exportar Word
  $routeExportWord = null;
  if (Route::has('admin.licitacion-propuestas.export.word')) {
      $routeExportWord = 'admin.licitacion-propuestas.export.word';
  } elseif (Route::has('admin.licitacion-propuestas.export-word')) {
      $routeExportWord = 'admin.licitacion-propuestas.export-word';
  } elseif (Route::has('licitacion-propuestas.export.word')) {
      $routeExportWord = 'licitacion-propuestas.export.word';
  } elseif (Route::has('licitacion-propuestas.export-word')) {
      $routeExportWord = 'licitacion-propuestas.export-word';
  }

  // ✅ Exportar Excel (nuevo)
  $routeExportExcel = null;
  if (Route::has('admin.licitacion-propuestas.export.excel')) {
      $routeExportExcel = 'admin.licitacion-propuestas.export.excel';
  } elseif (Route::has('admin.licitacion-propuestas.export-excel')) {
      $routeExportExcel = 'admin.licitacion-propuestas.export-excel';
  } elseif (Route::has('licitacion-propuestas.export.excel')) {
      $routeExportExcel = 'licitacion-propuestas.export.excel';
  } elseif (Route::has('licitacion-propuestas.export-excel')) {
      $routeExportExcel = 'licitacion-propuestas.export-excel';
  }

  // ===================== STATUS =====================
  $statusClass = match($propuesta->status) {
      'draft'         => 'st-draft',
      'revisar'       => 'st-revisar',
      'enviada'       => 'st-enviada',
      'adjudicada'    => 'st-adjudicada',
      'no_adjudicada' => 'st-no_adjudicada',
      default         => 'st-draft',
  };

  $statusLabels = [
      'draft'         => 'Borrador',
      'revisar'       => 'En revisión',
      'enviada'       => 'Enviada',
      'adjudicada'    => 'Adjudicada',
      'no_adjudicada' => 'No adjudicada',
  ];

  // Splits completos?
  $allSplitsProcessed = false;
  if (!empty($splitsInfo) && is_array($splitsInfo)) {
      $allSplitsProcessed = true;
      foreach ($splitsInfo as $s) {
          if (!in_array($s['state'] ?? null, ['done', 'done-current'], true)) {
              $allSplitsProcessed = false;
              break;
          }
      }
  }

  // Mapas para modal de buscador
  $solicitadoByItem  = [];
  $preselectedByItem = [];

  foreach ($propuesta->items as $it) {
    $req    = $it->requestItem;
    $solTxt = trim((string)($req?->line_raw ?: ($it->descripcion_raw ?: '—')));
    $solicitadoByItem[$it->id] = $solTxt;

    $p = $it->product;
    if ($p) {
      $text = trim((($p->sku ?? '') ? ($p->sku.' — ') : '').($p->name ?? ''));
      $preselectedByItem[$it->id] = [
        'id'   => $p->id,
        'text' => $text !== '' ? $text : ('Producto #'.$p->id),
        'meta' => [
          'sku'   => $p->sku ?? null,
          'name'  => $p->name ?? null,
          'brand' => $p->brand ?? null,
          'unit'  => $p->unit ?? null,
          'price' => (float)($p->price ?? 0),
        ],
      ];
    } else {
      $preselectedByItem[$it->id] = null;
    }
  }
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- Tom Select --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<link rel="stylesheet" href="{{ asset('css/propuestas.css') }}?v={{ time() }}">

<div class="pe-wrap">

  {{-- Header --}}
  <div class="pe-top">
    <div class="pe-title">
      <div class="pe-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M4 19V5"/><path d="M20 19V5"/><path d="M8 17V9"/><path d="M12 17V7"/><path d="M16 17V11"/>
        </svg>
      </div>
      <div style="min-width:0;">
        <h1 class="pe-h1">{{ $propuesta->codigo }} · {{ $propuesta->titulo }}</h1>
        <div class="pe-sub">
          <span class="pe-badge">Fecha: <strong style="color:var(--ink);">{{ $propuesta->fecha?->format('d/m/Y') }}</strong></span>
          @if($propuesta->licitacion_id)
            <span class="pe-badge">Licitación: <strong style="color:var(--ink);">#{{ $propuesta->licitacion_id }}</strong></span>
          @endif
          @if($propuesta->requisicion_id)
            <span class="pe-badge">Requisición: <strong style="color:var(--ink);">#{{ $propuesta->requisicion_id }}</strong></span>
          @endif
          <span class="pe-badge">Renglones: <strong style="color:var(--ink);">{{ $propuesta->items->count() }}</strong></span>
        </div>
      </div>
    </div>

    <div class="pe-actions">
      <span class="pe-status {{ $statusClass }}">
        <span class="pe-dot"></span>
        {{ $statusLabels[$propuesta->status] ?? $propuesta->status }}
      </span>

      @if($routeExportPdf)
        <a href="{{ route($routeExportPdf, $propuesta) }}" class="pe-btn pe-btn-mini">
          PDF
        </a>
      @endif

      @if($routeExportWord)
        <a href="{{ route($routeExportWord, $propuesta) }}" class="pe-btn pe-btn-mini">
          Word
        </a>
      @endif

      {{-- ✅ Excel --}}
      @if($routeExportExcel)
        <a href="{{ route($routeExportExcel, $propuesta) }}" class="pe-btn pe-btn-mini">
          Excel
        </a>
      @endif

      <a href="{{ url()->previous() }}" class="pe-btn pe-btn-mini">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M15 18l-6-6 6-6"/>
        </svg>
        Volver
      </a>
    </div>
  </div>

  {{-- Documento + splits --}}
  @if($licitacionPdf)
    <div class="pe-card">
      <div class="pe-card-h">
        <div class="pe-card-title">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <rect x="4" y="3" width="16" height="18" rx="2"/>
            <path d="M8 7h8M8 11h6M8 15h8"/>
          </svg>
          Documento base
        </div>

        <a class="pe-link" href="{{ route('admin.licitacion-pdfs.preview', $licitacionPdf) }}" target="_blank">
          Ver PDF completo
        </a>
      </div>

      <div class="pe-card-b">
        <div>
          <div class="pe-prod">{{ $licitacionPdf->id }}. {{ $licitacionPdf->original_filename ?? 'Archivo de licitación' }}</div>
          <div class="pe-mini">Procesa cada requisición para generar renglones y luego realiza el merge global.</div>
        </div>

        @if(!empty($splitsInfo))
          <div class="pe-splits">
            @foreach($splitsInfo as $i => $s)
              @php
                $stateClass = match($s['state']) {
                  'done', 'done-current' => 'pe-state--done',
                  'current'              => 'pe-state--current',
                  default                => 'pe-state--pending',
                };

                $label = match($s['state']) {
                  'done', 'done-current' => 'Procesada',
                  'current'              => 'En curso',
                  default                => 'Pendiente',
                };
              @endphp

              <div class="pe-split">
                <div class="pe-split-left">
                  <span class="pe-pill pe-state {{ $stateClass }}">
                    <strong>Req. {{ $i+1 }}</strong> · {{ $label }}
                  </span>

                  @if(($s['from'] ?? null) && ($s['to'] ?? null))
                    <span class="pe-pill"><strong>Págs</strong> {{ $s['from'] }}–{{ $s['to'] }}</span>
                  @endif

                  @if(($s['pages'] ?? null))
                    <span class="pe-pill"><strong>{{ $s['pages'] }}</strong> pág.</span>
                  @endif
                </div>

                @if($routeProcessSplit)
                  <form method="POST" action="{{ route($routeProcessSplit, ['licitacionPropuesta'=>$propuesta->id,'splitIndex'=>$s['index']]) }}">
                    @csrf
                    <button type="submit" class="pe-btn pe-btn--black pe-btn-mini">
                      @if(in_array($s['state'], ['done','done-current'], true))
                        Reprocesar con IA
                      @else
                        Procesar con IA
                      @endif
                    </button>
                  </form>
                @endif
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>
  @endif

  {{-- Totales + Merge + utilidad global --}}
  <div class="pe-card">
    <div class="pe-card-h">
      <div class="pe-card-title">Totales</div>

      <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
        @if($routeMerge)
          <form method="POST" action="{{ route($routeMerge, $propuesta) }}">
            @csrf
            <button type="submit" class="pe-btn pe-btn--black" {{ !$allSplitsProcessed ? 'disabled' : '' }}>
              Merge global
            </button>
          </form>
        @endif
      </div>
    </div>

    <div class="pe-card-b">
      <div class="pe-summary-row">
        <div class="pe-summary">
          <div>
            Subtotal base:
            <strong id="total-subtotal-base">
              {{ $propuesta->moneda ?? 'MXN' }}
              {{ number_format((float)($propuesta->subtotal_base ?? $propuesta->items->sum('subtotal')),2) }}
            </strong>
          </div>
          <div>
            Utilidad total:
            <strong id="total-utilidad">
              {{ $propuesta->moneda ?? 'MXN' }}
              {{ number_format((float)($propuesta->utilidad_total ?? 0),2) }}
            </strong>
          </div>
          <div>
            Subtotal + utl.:
            <strong id="total-subtotal">
              {{ $propuesta->moneda ?? 'MXN' }}
              {{ number_format((float)$propuesta->subtotal,2) }}
            </strong>
          </div>
          <div>
            IVA:
            <strong id="total-iva">
              {{ $propuesta->moneda ?? 'MXN' }}
              {{ number_format((float)$propuesta->iva,2) }}
            </strong>
          </div>
          <div class="pe-total">
            Total:
            <span id="total-total">
              {{ $propuesta->moneda ?? 'MXN' }}
              {{ number_format((float)$propuesta->total,2) }}
            </span>
          </div>
        </div>

        @if(!$allSplitsProcessed)
          <div class="pe-badge" style="border-color:#fde68a; background:var(--warning-soft); color:#92400e;">
            Falta procesar requisiciones antes del merge.
          </div>
        @endif
      </div>

      {{-- utilidad global --}}
      <div class="pe-global-util">
        <label for="global-util-input">% utilidad global:</label>
        <input id="global-util-input" type="number" step="0.01" min="0" class="pe-input-util-global" placeholder="10">
        <button type="button" class="pe-btn pe-btn-mini" onclick="applyGlobalUtility()">
          Aplicar a todos
        </button>
      </div>
    </div>
  </div>

  {{-- Tabla --}}
  <div class="pe-card">
    <div class="pe-card-h">
      <div class="pe-card-title">Comparativo</div>
      <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <span class="pe-badge">{{ $propuesta->items->count() }} renglones</span>

        @if($routeItemStore)
          <button type="button" class="pe-btn pe-btn-mini" onclick="openRowModal(null)">
            + Agregar renglón
          </button>
        @endif
      </div>
    </div>

    <div class="pe-card-b" style="padding:0;">
      <div style="overflow:auto; max-height:70vh;">
        <table class="pe-table">
          <thead>
            <tr>
              <th style="width:80px;">#</th>
              <th>Solicitado</th>
              <th>Producto ofertado / Coincidencias</th>
              <th style="width:150px;">Match</th>
              <th style="width:90px; text-align:right;">Cant.</th>
              <th style="width:140px; text-align:right;">Precio unit.</th>
              <th style="width:90px; text-align:right;">Utl. %</th>
              <th style="width:120px; text-align:right;">Utilidad</th>
              <th style="width:140px; text-align:right;">Subt. + utl.</th>
            </tr>
          </thead>

          <tbody>
            @foreach($propuesta->items as $item)
              @php
                $req  = $item->requestItem;
                $prod = $item->product;

                $score = $item->match_score ?? null;
                $scorePercent = is_null($score) ? null : max(0, min(100, (int)$score));
                $qtyInt = (int)($item->cantidad_propuesta ?? ($req?->cantidad ?? 0));

                $candidates = $candidatesByItem[$item->id] ?? collect();

                $topCandidateScore = null;
                if (is_null($scorePercent) && $candidates instanceof \Illuminate\Support\Collection && $candidates->isNotEmpty()) {
                  $topCandidateScore = (int) max(0, min(100, (int)($candidates->first()->match_score ?? 0)));
                }

                $utilPct   = (float)($item->utilidad_pct ?? 0);
                $utilMonto = (float)($item->utilidad_monto ?? 0);
                $hasUtil   = $utilPct > 0 && ($item->subtotal ?? 0) > 0;
                $subtotalConUtil = $hasUtil
                  ? (float)($item->subtotal_con_utilidad ?? (($item->subtotal ?? 0) + $utilMonto))
                  : 0.0;
              @endphp

              <tr id="row-{{ $item->id }}">
                {{-- # + acciones (editar/eliminar) --}}
                <td data-label="#" style="color:var(--muted); font-weight:900;">
                  <div style="display:flex; flex-direction:column; gap:4px; align-items:flex-start;">
                    <span>{{ $req?->renglon ?? $loop->iteration }}</span>

                    <div style="display:flex; gap:4px; flex-wrap:wrap; margin-top:4px;">
                      {{-- Editar en modal (usando data-* para evitar JSON en el onclick) --}}
                      <button
                        type="button"
                        class="pe-btn pe-btn-mini"
                        style="padding:3px 8px; font-size:.7rem;"
                        onclick="openRowModalFromButton(this)"
                        data-item-id="{{ $item->id }}"
                        data-descripcion="{{ $item->descripcion_raw ?? ($req?->line_raw ?? '') }}"
                        data-unidad="{{ $item->unidad_propuesta ?? '' }}"
                        data-cantidad="{{ (float)($item->cantidad_propuesta ?? ($req?->cantidad ?? 0)) }}"
                        data-precio="{{ (float)($item->precio_unitario ?? 0) }}"
                        data-utilidad="{{ (float)($item->utilidad_pct ?? 0) }}"
                      >
                        Editar
                      </button>

                      {{-- Eliminar --}}
                      @if($routeItemDestroy)
                        <form method="POST"
                              action="{{ route($routeItemDestroy, $item) }}"
                              onsubmit="return confirm('¿Eliminar este renglón?');">
                          @csrf
                          @method('DELETE')
                          <button type="submit"
                                  class="pe-btn pe-btn-mini pe-btn--danger"
                                  style="padding:3px 8px; font-size:.7rem;">
                            Eliminar
                          </button>
                        </form>
                      @endif
                    </div>
                  </div>
                </td>

                {{-- Solicitado --}}
                <td data-label="Solicitado">
                  @if($req)
                    <div class="pe-req">{{ $req->line_raw }}</div>
                    <div class="pe-mini">Página {{ $req->page?->page_number ?? '—' }}</div>
                  @else
                    <div class="pe-req">{{ $item->descripcion_raw }}</div>
                    <div class="pe-mini">Extraído por IA</div>
                  @endif
                </td>

                {{-- Producto + coincidencias --}}
                <td data-label="Producto ofertado / Coincidencias">
                  <div id="prod-block-{{ $item->id }}">
                    @if($prod)
                      <div class="pe-prod" id="prod-name-{{ $item->id }}">{{ trim(($prod->sku ?? '').' '.($prod->name ?? '')) }}</div>
                      <div class="pe-prod-meta" id="prod-meta-{{ $item->id }}">
                        @if(!empty($prod->brand)) Marca: {{ $prod->brand }} · @endif
                        Unidad: {{ $item->unidad_propuesta ?? ($prod->unit ?? '—') }}
                      </div>
                    @else
                      <span class="pe-tag" id="prod-tag-{{ $item->id }}">Sin producto (pendiente)</span>
                      <div class="pe-prod" id="prod-name-{{ $item->id }}" style="display:none;"></div>
                      <div class="pe-prod-meta" id="prod-meta-{{ $item->id }}" style="display:none;"></div>
                    @endif
                  </div>

                  <div style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                    @if($routeProductsSearch && $routeApplyAjaxName)
                      <button type="button" class="pe-btn pe-btn-mini" onclick="openPicker({{ $item->id }})">
                        Buscar / Cambiar
                      </button>
                    @else
                      <span class="pe-mini" style="color:#b91c1c; font-weight:900;">
                        Falta ruta AJAX (products.search o apply-product)
                      </span>
                    @endif

                    @if($prod && $routeReject)
                      <form method="POST" action="{{ route($routeReject, $item) }}">
                        @csrf
                        <button class="pe-btn pe-btn--danger pe-btn-mini" type="submit">Quitar</button>
                      </form>
                    @endif
                  </div>

                  {{-- Coincidencias sugeridas (solo si aún no hay producto) --}}
                  @if(empty($item->product_id))
                    @if($candidates->isNotEmpty())
                      <div style="margin-top:10px; display:flex; flex-direction:column; gap:10px;">
                        @foreach($candidates as $cand)
                          @php
                            $cScore  = (int) max(0, min(100, (int)($cand->match_score ?? 0)));
                            $cReason = $cand->match_reason ?? null;
                          @endphp

                          <div style="border:1px solid rgba(229,231,235,.9); border-radius:14px; padding:10px 12px; background:#fff; display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                            <div style="min-width:240px; flex:1 1 420px;">
                              <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                                <div class="pe-prod">{{ trim(($cand->sku ?? '').' '.($cand->name ?? '')) }}</div>

                                <span class="pe-matchbadge" title="{{ $cReason ? $cReason : '' }}">
                                  <span>Coincide {{ $cScore }}%</span>
                                  <span class="pe-bar" style="width:64px; height:7px;">
                                    <div style="transform:scaleX({{ $cScore/100 }});"></div>
                                  </span>
                                </span>
                              </div>

                              <div class="pe-prod-meta">
                                @if(!empty($cand->brand)) Marca: {{ $cand->brand }} · @endif
                                Unidad: {{ $cand->unit ?? '—' }}
                                @if(!is_null($cand->price)) · Precio: {{ $propuesta->moneda ?? 'MXN' }} {{ number_format((float)$cand->price,2) }} @endif
                              </div>

                              @if($cReason)
                                <div class="pe-mini-reason">{{ $cReason }}</div>
                              @endif
                            </div>

                            <div class="pe-cand-actions" style="display:flex; gap:8px; align-items:center;">
                              @if($routeProductsSearch && $routeApplyAjaxName)
                                <button type="button" class="pe-btn pe-btn--black pe-btn-mini" onclick="applyProductToItem({{ $item->id }}, {{ $cand->id }})">
                                  Elegir
                                </button>
                              @elseif($routeApply)
                                <form method="POST" action="{{ route($routeApply, $item) }}">
                                  @csrf
                                  <input type="hidden" name="product_id" value="{{ $cand->id }}">
                                  <button class="pe-btn pe-btn--black pe-btn-mini" type="submit">Elegir</button>
                                </form>
                              @endif
                            </div>
                          </div>
                        @endforeach
                      </div>
                    @else
                      <div class="pe-mini" style="margin-top:8px;">Sin coincidencias encontradas.</div>
                    @endif
                  @endif
                </td>

                {{-- Match --}}
                <td data-label="Match">
                  @if(!is_null($scorePercent))
                    <div class="pe-match">
                      <div class="pe-bar"><div style="transform:scaleX({{ $scorePercent/100 }});"></div></div>
                      <strong style="color:var(--ink);">{{ $scorePercent }}%</strong>
                    </div>
                    @if($item->motivo_seleccion)
                      <div class="pe-mini" style="margin-top:6px;">{{ $item->motivo_seleccion }}</div>
                    @endif
                  @elseif(!is_null($topCandidateScore))
                    <div class="pe-match">
                      <div class="pe-bar"><div style="transform:scaleX({{ $topCandidateScore/100 }});"></div></div>
                      <strong style="color:var(--ink);">{{ $topCandidateScore }}%</strong>
                    </div>
                    <div class="pe-mini" style="margin-top:6px;">Top sugerencia (aún no aplicado)</div>
                  @else
                    <span class="pe-tag">IA pendiente</span>
                  @endif
                </td>

                {{-- Cantidad --}}
                <td data-label="Cant." class="pe-amount">
                  {{ $qtyInt > 0 ? $qtyInt : '—' }}
                </td>

                {{-- Precio unitario --}}
                <td data-label="Precio unit." class="pe-amount">
                  <span id="pu-{{ $item->id }}">
                    @if($item->precio_unitario)
                      {{ $propuesta->moneda ?? 'MXN' }} {{ number_format((float)$item->precio_unitario,2) }}
                    @else
                      —
                    @endif
                  </span>
                </td>

                {{-- Utilidad % (input AJAX) --}}
                <td data-label="Utl. %" class="pe-amount">
                  @if($routeUtilityAjaxName)
                    <input
                      type="number"
                      step="0.01"
                      min="0"
                      class="pe-input-util js-util-input"
                      data-item-id="{{ $item->id }}"
                      value="{{ $utilPct > 0 ? $utilPct : '' }}"
                      placeholder="0">
                  @else
                    —
                  @endif
                </td>

                {{-- Utilidad monto --}}
                <td data-label="Utilidad" class="pe-amount">
                  <span id="util-monto-{{ $item->id }}">
                    @if($hasUtil && $utilMonto > 0)
                      {{ $propuesta->moneda ?? 'MXN' }} {{ number_format($utilMonto,2) }}
                    @else
                      —
                    @endif
                  </span>
                </td>

                {{-- Subtotal + utilidad --}}
                <td data-label="Subt. + utl." class="pe-amount">
                  <span id="sub-totalutil-{{ $item->id }}">
                    @if($hasUtil && $subtotalConUtil > 0)
                      {{ $propuesta->moneda ?? 'MXN' }} {{ number_format($subtotalConUtil,2) }}
                    @else
                      —
                    @endif
                  </span>
                </td>
              </tr>
            @endforeach

            @if($propuesta->items->isEmpty())
              <tr>
                <td colspan="9" style="text-align:center; padding:16px; color:var(--muted);">
                  No hay renglones aún. Procesa requisiciones con IA desde el bloque superior.
                </td>
              </tr>
            @endif
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

{{-- MODAL PICKER PRODUCTOS --}}
<div class="modal-backdrop-j" id="pickerBackdrop" aria-hidden="true">
  <div class="modal-j">
    <header class="modal-head">
      <div style="min-width:0;">
        <div class="modal-title">Seleccionar producto</div>
        <div class="modal-subtitle" id="pickerSubtitle">—</div>
      </div>
      <button class="btn-x" type="button" onclick="closePicker()">Cerrar</button>
    </header>

    <div class="modal-body">
      @if(!$routeProductsSearch)
        <div class="pe-badge" style="border-color:#fecaca; background:#fff1f2; color:#b91c1c;">
          Falta ruta <strong>products.search</strong>
        </div>
      @else
        <label class="modal-label">Buscar producto</label>
        <select id="productPicker" placeholder="Escribe nombre, SKU o marca…"></select>

        <div class="picked-card" id="pickedMeta" style="display:none;"></div>
      @endif
    </div>

    <div class="modal-foot">
      <button class="btn-ghost" type="button" onclick="closePicker()">Cancelar</button>
      <button class="btn-solid" type="button" onclick="confirmPicker()">Aplicar</button>
    </div>
  </div>
</div>

{{-- MODAL RENGLÓN (agregar / editar) --}}
<div class="modal-backdrop-j" id="rowBackdrop" aria-hidden="true">
  <div class="modal-j">
    <header class="modal-head">
      <div style="min-width:0;">
        <div class="modal-title" id="rowModalTitle">Renglón</div>
        <div class="modal-subtitle" id="rowModalSubtitle">
          Captura o edita la descripción, cantidad, precio y utilidad de este renglón.
        </div>
      </div>
      <button class="btn-x" type="button" onclick="closeRowModal()">Cerrar</button>
    </header>

    <form id="rowForm" method="POST" action="">
      @csrf
      <input type="hidden" name="_method" id="rowFormMethod" value="POST">
      <input type="hidden" name="item_id" id="rowItemId" value="">

      <div class="modal-body">
        <div style="display:flex; flex-direction:column; gap:10px;">
          <div>
            <label class="modal-label" for="rowDescripcion">Descripción</label>
            <textarea id="rowDescripcion" name="descripcion_raw" rows="3"
                      style="width:100%; border-radius:12px; border:1px solid var(--border); padding:8px; font-size:.9rem;"></textarea>
          </div>

          <div style="display:flex; flex-wrap:wrap; gap:12px;">
            <div style="flex:1 1 120px;">
              <label class="modal-label" for="rowUnidad">Unidad</label>
              <input id="rowUnidad" name="unidad_propuesta" type="text"
                     style="width:100%; border-radius:12px; border:1px solid var(--border); padding:6px 8px; font-size:.9rem;">
            </div>
            <div style="flex:1 1 120px;">
              <label class="modal-label" for="rowCantidad">Cantidad</label>
              <input id="rowCantidad" name="cantidad_propuesta" type="number" step="0.01" min="0"
                     style="width:100%; border-radius:12px; border:1px solid var(--border); padding:6px 8px; font-size:.9rem;">
            </div>
            <div style="flex:1 1 120px;">
              <label class="modal-label" for="rowPrecio">Precio unitario</label>
              <input id="rowPrecio" name="precio_unitario" type="number" step="0.0001" min="0"
                     style="width:100%; border-radius:12px; border:1px solid var(--border); padding:6px 8px; font-size:.9rem;">
            </div>
            <div style="flex:1 1 120px;">
              <label class="modal-label" for="rowUtilidad">% utilidad</label>
              <input id="rowUtilidad" name="utilidad_pct" type="number" step="0.01" min="0"
                     style="width:100%; border-radius:12px; border:1px solid var(--border); padding:6px 8px; font-size:.9rem;">
            </div>
          </div>
        </div>
      </div>

      <footer class="modal-foot">
        <button type="button" class="btn-ghost" onclick="closeRowModal()">Cancelar</button>
        <button type="submit" class="btn-solid">Guardar renglón</button>
      </footer>
    </form>
  </div>
</div>

<script>
  const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  const CURRENCY = @json($propuesta->moneda ?? 'MXN');

  const routeProductsSearch      = "{{ $routeProductsSearch      ? route($routeProductsSearch)                             : '' }}";
  const routeApplyAjaxTemplate   = "{{ $routeApplyAjaxName       ? route($routeApplyAjaxName,       ['item' => 'ITEM_ID']) : '' }}";
  const routeUtilityAjaxTemplate = "{{ $routeUtilityAjaxName     ? route($routeUtilityAjaxName,     ['item' => 'ITEM_ID']) : '' }}";
  const routeItemStore           = "{{ $routeItemStore           ? route($routeItemStore,           $propuesta)           : '' }}";
  const routeItemUpdateTemplate  = "{{ $routeItemUpdate          ? route($routeItemUpdate,          ['item' => 'ITEM_ID']) : '' }}";

  const solicitadoByItem  = @json($solicitadoByItem);
  const preselectedByItem = @json($preselectedByItem);

  let picker  = null;
  let current = { itemId: null, selectedId: null };

  function money(n){
    try {
      return new Intl.NumberFormat('es-MX', { style:'currency', currency: CURRENCY }).format(Number(n||0));
    } catch(e) {
      return (CURRENCY || 'MXN') + ' ' + (Number(n||0).toFixed(2));
    }
  }

  function showPicked(meta){
    const box = document.getElementById('pickedMeta');
    if(!box) return;

    const sku   = meta?.sku ? (meta.sku + ' — ') : '';
    const name  = meta?.name || 'Producto';
    const brand = meta?.brand || '—';
    const unit  = meta?.unit || '—';
    const price = money(meta?.price || 0);

    box.style.display = 'block';
    box.innerHTML = `
      <strong>${(sku + name).trim()}</strong>
      <div class="mut">Marca: ${brand} · Unidad: ${unit}</div>
      <div class="mut" style="margin-top:6px;">Precio: <b>${price}</b></div>
    `;
  }

  function openPicker(itemId){
    if(!routeProductsSearch || !routeApplyAjaxTemplate){
      alert('Faltan rutas AJAX (products.search o apply-product).');
      return;
    }

    current.itemId     = itemId;
    current.selectedId = null;

    const solicitado = (solicitadoByItem[itemId] || '—').toString();
    const subtitleEl = document.getElementById('pickerSubtitle');
    if(subtitleEl) subtitleEl.textContent = solicitado;

    const backdrop = document.getElementById('pickerBackdrop');
    if(!backdrop) return;
    backdrop.style.display = 'flex';
    backdrop.setAttribute('aria-hidden', 'false');

    const metaBox = document.getElementById('pickedMeta');
    if(metaBox){
      metaBox.style.display = 'none';
      metaBox.innerHTML = '';
    }

    if(!picker){
      picker = new TomSelect('#productPicker', {
        valueField: 'id',
        labelField: 'text',
        searchField: ['text'],
        preload: 'focus',
        maxOptions: 50,
        render: {
          option: function(data, escape){
            const m     = data.meta || {};
            const t     = escape(data.text || '');
            const brand = escape(m.brand || '—');
            const unit  = escape(m.unit || '—');
            const price = money(m.price || 0);

            return `
              <div>
                <div class="opt-title">${t}</div>
                <div class="opt-sub">Marca: ${brand} · Unidad: ${unit}</div>
                <div class="opt-meta">Precio: <b>${escape(price)}</b></div>
              </div>
            `;
          },
          item: function(data, escape){
            return `<div>${escape(data.text || '')}</div>`;
          }
        },
        load: function(query, callback){
          const url = new URL(routeProductsSearch, window.location.origin);
          url.searchParams.set('q', query || '');
          url.searchParams.set('limit', '20');

          fetch(url.toString(), { headers: { 'Accept':'application/json' }})
            .then(r => r.json())
            .then(json => callback(json.results || []))
            .catch(() => callback());
        },
        onChange: function(val){
          current.selectedId = val ? Number(val) : null;
          const opt = picker.options[val];
          if(opt?.meta) showPicked(opt.meta);
        }
      });
    } else {
      picker.clear(true);
      picker.clearOptions();
    }

    const pre = preselectedByItem[itemId] || null;
    if(pre && pre.id){
      picker.addOption(pre);
      picker.setValue(pre.id, true);
      if(pre.meta) showPicked(pre.meta);
      current.selectedId = Number(pre.id);
    } else {
      picker.clear(true);
      picker.load('');
    }
  }

  function closePicker(){
    const backdrop = document.getElementById('pickerBackdrop');
    if(!backdrop) return;
    backdrop.style.display = 'none';
    backdrop.setAttribute('aria-hidden', 'true');
  }

  function confirmPicker(){
    if(!current.selectedId){
      alert('Selecciona un producto.');
      return;
    }
    applyProductToItem(current.itemId, current.selectedId, true);
  }

  function applyProductToItem(itemId, productId, closeAfter = false){
    if(!routeApplyAjaxTemplate){
      alert('No hay ruta configurada para apply-product AJAX.');
      return;
    }

    const url = routeApplyAjaxTemplate.replace('ITEM_ID', itemId);

    // Leer utilidad % del input de ese renglón (si ya la capturaste)
    let utilidadPct = null;
    const utlInput = document.querySelector('.js-util-input[data-item-id="'+itemId+'"]');
    if(utlInput && utlInput.value !== ''){
      const v = parseFloat(utlInput.value);
      if(!isNaN(v)) utilidadPct = v;
    }

    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type':'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN': CSRF
      },
      body: JSON.stringify({
        product_id: productId,
        utilidad_pct: utilidadPct
      })
    })
    .then(async (r) => {
      const json = await r.json().catch(() => ({}));
      if(!r.ok) throw json;
      return json;
    })
    .then(({row, totals}) => {
      // Bloque producto
      const tagEl  = document.getElementById('prod-tag-' + row.item_id);
      const nameEl = document.getElementById('prod-name-' + row.item_id);
      const metaEl = document.getElementById('prod-meta-' + row.item_id);

      if(tagEl) tagEl.style.display = 'none';

      if(nameEl){
        nameEl.style.display = 'block';
        const sku = row.sku ? (row.sku + ' ') : '';
        nameEl.textContent = (sku + (row.name || 'Producto')).trim();
      }
      if(metaEl){
        metaEl.style.display = 'block';
        metaEl.textContent = `Marca: ${row.brand || '—'} · Unidad: ${row.unit || '—'}`;
      }

      // Precios y utilidades
      const puEl    = document.getElementById('pu-' + row.item_id);
      const utilEl  = document.getElementById('util-monto-' + row.item_id);
      const subEl   = document.getElementById('sub-totalutil-' + row.item_id);
      const utlInp  = document.querySelector('.js-util-input[data-item-id="'+row.item_id+'"]');

      if(puEl) puEl.textContent = money(row.precio_unitario || 0);

      const hasUtil = (row.utilidad_monto || 0) > 0;
      if(utilEl){
        utilEl.textContent = hasUtil ? money(row.utilidad_monto || 0) : '—';
      }
      if(subEl){
        const val = hasUtil
          ? (row.subtotal_con_utilidad || (row.subtotal + (row.utilidad_monto || 0)))
          : 0;
        subEl.textContent = val > 0 ? money(val) : '—';
      }
      if(utlInp && typeof row.utilidad_pct !== 'undefined' && row.utilidad_pct !== null){
        utlInp.value = row.utilidad_pct;
      }

      updateTotalsFromResponse(totals);

      if(closeAfter) closePicker();
    })
    .catch((err) => {
      console.error(err);
      alert('No se pudo aplicar el producto. Revisa consola y laravel.log.');
    });
  }

  // ===== UTILIDAD POR RENGLÓN (AJAX) =====
  function sendUtilityUpdate(itemId, pct){
    if(!routeUtilityAjaxTemplate){
      alert('Falta ruta AJAX para actualizar utilidad (update-utility).');
      return;
    }

    const url = routeUtilityAjaxTemplate.replace('ITEM_ID', itemId);

    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type':'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN': CSRF
      },
      body: JSON.stringify({ utilidad_pct: pct })
    })
    .then(async (r) => {
      const json = await r.json().catch(() => ({}));
      if(!r.ok) throw json;
      return json;
    })
    .then(({ row, totals }) => {
      const utilEl    = document.getElementById('util-monto-' + row.item_id);
      const subUtilEl = document.getElementById('sub-totalutil-' + row.item_id);

      const hasUtil = (row.utilidad_monto || 0) > 0;

      if(utilEl){
        utilEl.textContent = hasUtil ? money(row.utilidad_monto || 0) : '—';
      }
      if(subUtilEl){
        const val = hasUtil
          ? (row.subtotal_con_utilidad || (row.subtotal_base + (row.utilidad_monto || 0)))
          : 0;
        subUtilEl.textContent = val > 0 ? money(val) : '—';
      }

      updateTotalsFromResponse(totals);
    })
    .catch(err => {
      console.error(err);
      alert('No se pudo actualizar la utilidad. Revisa consola y laravel.log.');
    });
  }

  function updateTotalsFromResponse(totals){
    if(!totals) return;

    const tSubBaseEl = document.getElementById('total-subtotal-base');
    const tUtilEl    = document.getElementById('total-utilidad');
    const tSubUtilEl = document.getElementById('total-subtotal');
    const tIvaEl     = document.getElementById('total-iva');
    const tTotalEl   = document.getElementById('total-total');

    if(tSubBaseEl && typeof totals.subtotal_base !== 'undefined'){
      tSubBaseEl.textContent = money(totals.subtotal_base || 0);
    }
    if(tUtilEl && typeof totals.utilidad !== 'undefined'){
      tUtilEl.textContent = money(totals.utilidad || totals.utilidad_total || 0);
    }
    if(tSubUtilEl && typeof totals.subtotal_con_utilidad !== 'undefined'){
      tSubUtilEl.textContent = money(totals.subtotal_con_utilidad || 0);
    } else if(tSubUtilEl && typeof totals.subtotal !== 'undefined'){
      tSubUtilEl.textContent = money(totals.subtotal || 0);
    }
    if(tIvaEl && typeof totals.iva !== 'undefined'){
      tIvaEl.textContent = money(totals.iva || 0);
    }
    if(tTotalEl && typeof totals.total !== 'undefined'){
      tTotalEl.textContent = money(totals.total || 0);
    }
  }

  // Utilidad global -> aplica al input de todos los renglones + AJAX
  function applyGlobalUtility(){
    const input = document.getElementById('global-util-input');
    if(!input) return;
    const val = parseFloat(input.value);
    if(isNaN(val) || val < 0){
      alert('Introduce un porcentaje de utilidad válido.');
      return;
    }

    document.querySelectorAll('.js-util-input').forEach(el => {
      el.value = val.toString();
      const id = el.dataset.itemId;
      if(id) sendUtilityUpdate(id, val);
    });
  }

  // ===== MODAL RENGLÓN (AGREGAR / EDITAR) =====

  // Helper: toma los data-* del botón y arma el objeto para openRowModal
  function openRowModalFromButton(btn){
    const d = btn.dataset;

    openRowModal({
      id: d.itemId ? Number(d.itemId) : null,
      descripcion: d.descripcion || '',
      unidad: d.unidad || '',
      cantidad: d.cantidad || '',
      precio: d.precio || '',
      utilidad: d.utilidad || '',
    });
  }

  function openRowModal(data){
    const backdrop = document.getElementById('rowBackdrop');
    const titleEl  = document.getElementById('rowModalTitle');
    const idInput  = document.getElementById('rowItemId');
    const methodEl = document.getElementById('rowFormMethod');
    const formEl   = document.getElementById('rowForm');

    const descEl   = document.getElementById('rowDescripcion');
    const unidadEl = document.getElementById('rowUnidad');
    const cantEl   = document.getElementById('rowCantidad');
    const precioEl = document.getElementById('rowPrecio');
    const utilEl   = document.getElementById('rowUtilidad');

    if(data && data.id){
      // Editar
      titleEl.textContent = 'Editar renglón';
      idInput.value       = data.id;
      methodEl.value      = 'PUT';

      if(routeItemUpdateTemplate){
        formEl.action = routeItemUpdateTemplate.replace('ITEM_ID', data.id);
      } else {
        alert('No hay ruta definida para actualizar renglones.');
      }

      descEl.value   = data.descripcion || '';
      unidadEl.value = data.unidad      || '';
      cantEl.value   = data.cantidad    || '';
      precioEl.value = data.precio      || '';
      utilEl.value   = data.utilidad    || '';
    } else {
      // Nuevo
      titleEl.textContent = 'Nuevo renglón';
      idInput.value       = '';
      methodEl.value      = 'POST';

      if(routeItemStore){
        formEl.action = routeItemStore;
      } else {
        alert('No hay ruta definida para crear renglones.');
      }

      descEl.value   = '';
      unidadEl.value = '';
      cantEl.value   = '';
      precioEl.value = '';
      utilEl.value   = '';
    }

    backdrop.style.display = 'flex';
    backdrop.setAttribute('aria-hidden', 'false');
  }

  function closeRowModal(){
    const backdrop = document.getElementById('rowBackdrop');
    if(!backdrop) return;
    backdrop.style.display = 'none';
    backdrop.setAttribute('aria-hidden', 'true');
  }

  // Eventos DOM
  document.addEventListener('DOMContentLoaded', () => {
    // change / blur en inputs de utilidad
    document.querySelectorAll('.js-util-input').forEach(el => {
      el.addEventListener('change', () => {
        const id = el.dataset.itemId;
        if(!id) return;
        const val = parseFloat(el.value);
        if(isNaN(val) || val < 0){
          el.value = '';
          sendUtilityUpdate(id, 0);
        } else {
          sendUtilityUpdate(id, val);
        }
      });
      el.addEventListener('blur', () => {
        const id = el.dataset.itemId;
        if(!id) return;
        const val = parseFloat(el.value);
        if(isNaN(val) || val < 0){
          el.value = '';
          sendUtilityUpdate(id, 0);
        }
      });
    });

    // Cerrar modales con click fuera
    document.getElementById('pickerBackdrop')?.addEventListener('click', (e) => {
      if(e.target?.id === 'pickerBackdrop') closePicker();
    });
    document.getElementById('rowBackdrop')?.addEventListener('click', (e) => {
      if(e.target?.id === 'rowBackdrop') closeRowModal();
    });
  });

  // Cerrar con ESC ambos modales
  document.addEventListener('keydown', (e) => {
    if(e.key === 'Escape'){
      closePicker();
      closeRowModal();
    }
  });
</script>
@endsection
