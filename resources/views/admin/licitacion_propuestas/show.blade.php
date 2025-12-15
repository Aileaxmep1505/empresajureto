@extends('layouts.app')

@section('title', $propuesta->codigo.' - Propuesta económica')

@section('content')
@php
  use Illuminate\Support\Facades\Route;

  // ✅ Fallbacks para rutas (evita RouteNotFoundException)
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

  // ✅ AJAX (fallback con/sin admin.)
  $routeProductsSearch = Route::has('admin.products.search')
    ? 'admin.products.search'
    : (Route::has('products.search') ? 'products.search' : null);

  $routeApplyAjaxName  = Route::has('admin.licitacion-propuesta-items.apply-product')
    ? 'admin.licitacion-propuesta-items.apply-product'
    : (Route::has('licitacion-propuesta-items.apply-product') ? 'licitacion-propuesta-items.apply-product' : null);

  // Status UI
  $statusClass = match($propuesta->status) {
      'draft' => 'st-draft',
      'revisar' => 'st-revisar',
      'enviada' => 'st-enviada',
      'adjudicada' => 'st-adjudicada',
      'no_adjudicada' => 'st-no_adjudicada',
      default => 'st-draft',
  };

  $statusLabels = [
      'draft' => 'Borrador',
      'revisar' => 'En revisión',
      'enviada' => 'Enviada',
      'adjudicada' => 'Adjudicada',
      'no_adjudicada' => 'No adjudicada',
  ];

  // All splits processed?
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

  // ✅ Mapas para el modal: solicitado + producto actual (si existe)
  $solicitadoByItem = [];
  $preselectedByItem = [];

  foreach ($propuesta->items as $it) {
    $req = $it->requestItem;
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
          // 'cost'  => (float)($p->cost ?? 0),
        ],
      ];
    } else {
      $preselectedByItem[$it->id] = null;
    }
  }
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- ✅ Tom Select --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<style>
  :root{
    --ink:#0f172a;
    --muted:#6b7280;
    --border:#e5e7eb;
    --soft:#f8fafc;
    --success:#16a34a;
    --success-soft:#dcfce7;
    --warning:#f59e0b;
    --warning-soft:#fffbeb;
    --danger:#ef4444;
    --danger-soft:#fef2f2;
    --radius:18px;
    --shadow:0 18px 44px rgba(15,23,42,.10);
  }

  .pe-wrap{
    color:var(--ink);
    max-width:1180px;
    margin:0 auto;
    padding:16px;
    display:flex;
    flex-direction:column;
    gap:14px;
  }

  .pe-top{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    flex-wrap:wrap;
  }

  .pe-title{
    display:flex;
    align-items:flex-start;
    gap:12px;
    min-width:0;
  }

  .pe-icon{
    width:44px; height:44px;
    border-radius:14px;
    display:grid; place-items:center;
    background:linear-gradient(135deg,#f1f5f9,#fff);
    border:1px solid var(--border);
    box-shadow:0 10px 26px rgba(15,23,42,.06);
    flex:0 0 auto;
  }

  .pe-h1{
    font-weight:900;
    font-size:1.05rem;
    line-height:1.2;
    margin:0;
    word-break:break-word;
  }

  .pe-sub{
    margin-top:6px;
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    color:var(--muted);
    font-size:.82rem;
  }

  .pe-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:4px 10px;
    border-radius:999px;
    border:1px solid var(--border);
    background:#fff;
    font-size:.78rem;
    color:var(--muted);
  }

  .pe-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:center;
  }

  /* ===================== BOTONES (MINIMAL) ===================== */
  .pe-btn{
    appearance:none;
    border-radius:12px;
    padding:8px 11px;
    font-weight:800;
    font-size:.82rem;
    display:inline-flex;
    align-items:center;
    gap:8px;
    cursor:pointer;
    text-decoration:none;
    border:1px solid rgba(15,23,42,.12);
    background:#fff;
    color:var(--ink);
    transition: background .16s ease, color .16s ease, border-color .16s ease, transform .12s ease;
    white-space:nowrap;
  }
  .pe-btn:hover{
    background:#0f172a;
    color:#fff;
    border-color:#0f172a;
    transform: translateY(-1px);
  }
  .pe-btn:active{ transform: translateY(0); }
  .pe-btn[disabled], .pe-btn[aria-disabled="true"]{ opacity:.55; cursor:not-allowed; transform:none !important; }

  .pe-btn-mini{ padding:7px 10px; font-size:.78rem; border-radius:11px; }

  .pe-btn--black{ background:#0f172a; color:#fff; border-color:#0f172a; }
  .pe-btn--black:hover{ background:#111827; border-color:#111827; }

  .pe-btn--danger{
    background:#fff;
    color:#991b1b;
    border-color:#fecaca;
  }
  .pe-btn--danger:hover{
    background:#991b1b;
    border-color:#991b1b;
    color:#fff;
  }

  .pe-link{
    text-decoration:none;
    border-radius:12px;
    padding:8px 11px;
    font-size:.82rem;
    font-weight:800;
    border:1px solid rgba(15,23,42,.12);
    background:#fff;
    color:var(--ink);
    transition: background .16s ease, color .16s ease, border-color .16s ease, transform .12s ease;
    white-space:nowrap;
  }
  .pe-link:hover{
    background:#0f172a;
    color:#fff;
    border-color:#0f172a;
    transform: translateY(-1px);
  }

  /* ===================== STATUS ===================== */
  .pe-status{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:6px 12px;
    border-radius:999px;
    font-size:.8rem;
    font-weight:900;
    border:1px solid var(--border);
    background:#fff;
    color:var(--muted);
  }
  .pe-dot{ width:8px; height:8px; border-radius:999px; background:currentColor; }
  .st-draft{ color:var(--warning); background:var(--warning-soft); border-color:#fde68a; }
  .st-revisar{ color:#1d4ed8; background:#eff6ff; border-color:#bfdbfe; }
  .st-enviada{ color:#0284c7; background:#e0f2fe; border-color:#bae6fd; }
  .st-adjudicada{ color:var(--success); background:var(--success-soft); border-color:#bbf7d0; }
  .st-no_adjudicada{ color:var(--danger); background:var(--danger-soft); border-color:#fecaca; }

  /* ===================== CARDS ===================== */
  .pe-card{
    border-radius:var(--radius);
    background:#fff;
    border:1px solid var(--border);
    box-shadow:var(--shadow);
    overflow:hidden;
  }
  .pe-card-h{
    padding:12px 14px;
    border-bottom:1px solid rgba(229,231,235,.85);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    flex-wrap:wrap;
  }
  .pe-card-title{
    display:flex;
    align-items:center;
    gap:10px;
    font-weight:900;
    font-size:.92rem;
  }
  .pe-card-b{ padding:14px; }

  /* ===================== SPLITS ===================== */
  .pe-splits{ display:flex; flex-direction:column; gap:10px; margin-top:12px; }
  .pe-split{
    border:1px solid rgba(229,231,235,.9);
    border-radius:14px;
    background:#fff;
    padding:10px 12px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
  }
  .pe-split-left{ display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
  .pe-pill{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:5px 10px;
    border-radius:999px;
    border:1px solid var(--border);
    background:var(--soft);
    font-size:.78rem;
    color:var(--muted);
    white-space:nowrap;
  }
  .pe-pill strong{ color:var(--ink); }

  .pe-state{ border:1px solid transparent; font-weight:900; }
  .pe-state--done{ background:var(--success-soft); border-color:#bbf7d0; color:#166534; }
  .pe-state--pending{ background:var(--warning-soft); border-color:#fde68a; color:#92400e; }
  .pe-state--current{ background:#dbeafe; border-color:#bfdbfe; color:#1d4ed8; }

  /* ===================== TOTALES ===================== */
  .pe-summary-row{ display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
  .pe-summary{ display:flex; gap:10px 18px; flex-wrap:wrap; align-items:center; font-size:.88rem; color:var(--muted); }
  .pe-summary strong{ color:var(--ink); }
  .pe-total{
    display:inline-flex;
    align-items:center;
    gap:10px;
    padding:8px 12px;
    border-radius:999px;
    background:#0f172a;
    color:#fff;
    font-weight:900;
    font-size:.9rem;
  }

  /* ===================== TABLA ===================== */
  .pe-table{ width:100%; border-collapse:separate; border-spacing:0; font-size:.88rem; }
  .pe-table thead th{
    position:sticky; top:0; z-index:2;
    background:#f8fafc;
    border-bottom:1px solid var(--border);
    font-size:.78rem;
    color:var(--muted);
    text-align:left;
    padding:10px 12px;
    white-space:nowrap;
  }
  .pe-table td{
    border-bottom:1px solid rgba(229,231,235,.85);
    padding:10px 12px;
    vertical-align:top;
  }
  .pe-table tbody tr:hover{ background:#fbfdff; }

  .pe-req{ white-space:pre-wrap; color:var(--ink); line-height:1.35; font-size:.9rem; }
  .pe-mini{ margin-top:4px; font-size:.78rem; color:var(--muted); }

  .pe-prod{ font-weight:900; color:var(--ink); font-size:.9rem; line-height:1.25; }
  .pe-prod-meta{ margin-top:4px; font-size:.78rem; color:var(--muted); line-height:1.25; }

  .pe-amount{ text-align:right; white-space:nowrap; }

  .pe-match{ display:flex; align-items:center; gap:8px; font-size:.82rem; color:var(--muted); white-space:nowrap; }
  .pe-bar{ width:54px; height:6px; border-radius:999px; background:#e5e7eb; overflow:hidden; }
  .pe-bar > div{ height:100%; border-radius:999px; background:linear-gradient(90deg,#22c55e,#4ade80); transform-origin:left; }

  .pe-tag{
    display:inline-flex;
    align-items:center;
    gap:6px;
    border-radius:999px;
    padding:5px 10px;
    font-size:.78rem;
    border:1px solid rgba(15,23,42,.12);
    background:#fff;
    color:var(--ink);
    font-weight:900;
    white-space:nowrap;
  }

  /* ✅ badge de match para candidatos */
  .pe-matchbadge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:6px 10px;
    border-radius:999px;
    border:1px solid rgba(15,23,42,.12);
    background:#fff;
    font-size:.78rem;
    font-weight:950;
    color:var(--ink);
    white-space:nowrap;
  }
  .pe-matchbadge small{ font-weight:900; color:var(--muted); }
  .pe-mini-reason{
    margin-top:6px;
    font-size:.76rem;
    color:#64748b;
    line-height:1.25;
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
  }

  .pe-suggest{
    margin-top:8px;
    padding:10px 10px;
    border-radius:14px;
    border:1px dashed rgba(15,23,42,.18);
    background:#fafafa;
  }

  .pe-suggest-row{ display:flex; gap:10px; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; }
  .pe-suggest-left{ min-width:260px; flex:1 1 420px; }

  /* ===================== MODAL (ALTURA PRO) ===================== */
  .modal-backdrop-j{
    position:fixed; inset:0;
    background:rgba(2,6,23,.58);
    display:none;
    align-items:center; justify-content:center;
    padding:18px;
    z-index:9999;
  }
  .modal-j{
    width:min(980px, 96vw);
    height:min(78vh, 760px);
    min-height:520px;
    background:#fff;
    border-radius:18px;
    box-shadow:0 28px 80px rgba(0,0,0,.35);
    overflow:hidden;
    display:flex;
    flex-direction:column;
    border:1px solid rgba(229,231,235,.9);
  }
  .modal-head{
    padding:14px 16px;
    border-bottom:1px solid rgba(229,231,235,.9);
    display:flex;
    justify-content:space-between;
    gap:12px;
  }
  .modal-title{ font-weight:950; font-size:1.05rem; letter-spacing:-.01em; }
  .modal-subtitle{
    margin-top:6px;
    color:#64748b;
    font-size:.86rem;
    line-height:1.25;
    max-width:820px;
    display:-webkit-box;
    -webkit-line-clamp:3;
    -webkit-box-orient:vertical;
    overflow:hidden;
  }

  .btn-x{
    border:1px solid rgba(15,23,42,.12);
    background:#fff;
    border-radius:10px;
    font-weight:800;
    cursor:pointer;
    padding:8px 10px;
    transition: background .16s ease, color .16s ease, border-color .16s ease, transform .12s ease;
  }
  .btn-x:hover{ background:#0f172a; color:#fff; border-color:#0f172a; transform: translateY(-1px); }

  .modal-body{
    padding:16px;
    overflow:auto;
    flex:1 1 auto;
  }
  .modal-label{
    font-weight:900;
    font-size:.85rem;
    margin-bottom:10px;
    display:block;
  }

  .picked-card{
    margin-top:14px;
    border:1px solid rgba(229,231,235,.9);
    background:#f8fafc;
    border-radius:16px;
    padding:12px 14px;
  }
  .picked-card strong{ display:block; font-weight:950; margin-bottom:6px; }
  .picked-card .mut{ color:#64748b; font-size:.85rem; line-height:1.25; }

  .modal-foot{
    padding:12px 16px;
    border-top:1px solid rgba(229,231,235,.9);
    display:flex;
    justify-content:flex-end;
    gap:8px;
  }
  .btn-ghost, .btn-solid{
    border-radius:999px;
    padding:9px 14px;
    font-weight:900;
    font-size:.85rem;
    cursor:pointer;
    transition: background .16s ease, color .16s ease, border-color .16s ease, transform .12s ease;
  }
  .btn-ghost{
    background:#fff;
    color:var(--ink);
    border:1px solid rgba(15,23,42,.12);
  }
  .btn-ghost:hover{ background:#0f172a; color:#fff; border-color:#0f172a; transform: translateY(-1px); }
  .btn-solid{
    background:#0f172a;
    color:#fff;
    border:1px solid #0f172a;
  }
  .btn-solid:hover{ background:#111827; border-color:#111827; transform: translateY(-1px); }

  /* ===================== TOM SELECT ===================== */
  .ts-wrapper{ width:100%; }
  .ts-control{
    min-height:32px !important;
    border-radius:14px !important;
    border:1px solid rgba(15,23,42,.14) !important;
    padding:12px 12px !important;
    box-shadow:none !important;
  }
  .ts-control input{ font-size:.95rem !important; }
  .ts-dropdown{
    border-radius:14px !important;
    border:1px solid rgba(15,23,42,.14) !important;
    box-shadow:0 18px 50px rgba(2,6,23,.18) !important;
    overflow:hidden;
  }
  .ts-dropdown .ts-dropdown-content{ max-height:250px !important; }
  .ts-dropdown .option{
    padding:10px 10px !important;
    border-bottom:1px solid rgba(229,231,235,.7);
    font-size:.92rem;
    line-height:1.25;
  }
  .ts-dropdown .option:last-child{ border-bottom:none; }
  .ts-dropdown .option.active{ background:#0f172a !important; color:#fff !important; }
  .ts-dropdown .opt-title{ font-weight:950; }
  .ts-dropdown .opt-sub{ font-size:.82rem; opacity:.8; margin-top:4px; }
  .ts-dropdown .opt-meta{ font-size:.82rem; opacity:.85; margin-top:4px; }

  @media (max-width: 860px){
    .pe-table thead{ display:none; }
    .pe-table, .pe-table tbody, .pe-table tr, .pe-table td{ display:block; width:100%; }
    .pe-table tr{ border-bottom:1px solid var(--border); padding:10px 0; }
    .pe-table td{ border:none; padding:6px 12px; }
    .pe-table td::before{
      content:attr(data-label);
      display:block;
      font-size:.74rem;
      color:var(--muted);
      font-weight:900;
      margin-bottom:4px;
    }
    .pe-amount{ text-align:left; }
    .modal-j{ height: min(85vh, 860px); min-height: 520px; }
  }
</style>

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

  {{-- Totales + Merge --}}
  <div class="pe-card">
    <div class="pe-card-h">
      <div class="pe-card-title">Totales</div>

      @if($routeMerge)
        <form method="POST" action="{{ route($routeMerge, $propuesta) }}">
          @csrf
          <button type="submit" class="pe-btn pe-btn--black" {{ !$allSplitsProcessed ? 'disabled' : '' }}>
            Merge global
          </button>
        </form>
      @endif
    </div>

    <div class="pe-card-b">
      <div class="pe-summary-row">
        <div class="pe-summary">
          <div>
            Subtotal:
            <strong id="total-subtotal">{{ $propuesta->moneda ?? 'MXN' }} {{ number_format((float)$propuesta->subtotal,2) }}</strong>
          </div>
          <div>
            IVA:
            <strong id="total-iva">{{ $propuesta->moneda ?? 'MXN' }} {{ number_format((float)$propuesta->iva,2) }}</strong>
          </div>
          <div class="pe-total">
            Total:
            <span id="total-total">{{ $propuesta->moneda ?? 'MXN' }} {{ number_format((float)$propuesta->total,2) }}</span>
          </div>
        </div>

        @if(!$allSplitsProcessed)
          <div class="pe-badge" style="border-color:#fde68a; background:var(--warning-soft); color:#92400e;">
            Falta procesar requisiciones antes del merge.
          </div>
        @endif
      </div>
    </div>
  </div>

  {{-- Tabla --}}
  <div class="pe-card">
    <div class="pe-card-h">
      <div class="pe-card-title">Comparativo</div>
      <span class="pe-badge">{{ $propuesta->items->count() }} renglones</span>
    </div>

    <div class="pe-card-b" style="padding:0;">
      <div style="overflow:auto; max-height:70vh;">
        <table class="pe-table">
          <thead>
            <tr>
              <th style="width:56px;">#</th>
              <th>Solicitado</th>
              <th>Producto ofertado / Coincidencias</th>
              <th style="width:150px;">Match</th>
              <th style="width:90px; text-align:right;">Cant.</th>
              <th style="width:140px; text-align:right;">Precio unit.</th>
              <th style="width:140px; text-align:right;">Subtotal</th>
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

                // ✅ Score “previo” (si el item no tiene match_score, muestra el top candidato)
                $topCandidateScore = null;
                if (is_null($scorePercent) && $candidates instanceof \Illuminate\Support\Collection && $candidates->isNotEmpty()) {
                  $topCandidateScore = (int) max(0, min(100, (int)($candidates->first()->match_score ?? 0)));
                }

                $hasSuggestedCol = array_key_exists('suggested_product_id', $item->getAttributes());
                $suggested = null;
                if ($hasSuggestedCol) {
                  $suggested = method_exists($item, 'suggestedProduct') ? $item->suggestedProduct : null;
                }
              @endphp

              <tr id="row-{{ $item->id }}">
                <td data-label="#" style="color:var(--muted); font-weight:900;">
                  {{ $req?->renglon ?? $loop->iteration }}
                </td>

                <td data-label="Solicitado">
                  @if($req)
                    <div class="pe-req">{{ $req->line_raw }}</div>
                    <div class="pe-mini">Página {{ $req->page?->page_number ?? '—' }}</div>
                  @else
                    <div class="pe-req">{{ $item->descripcion_raw }}</div>
                    <div class="pe-mini">Extraído por IA</div>
                  @endif
                </td>

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

                  {{-- 5 coincidencias (con score + razón) --}}
                  @if(empty($item->product_id))
                    @if($candidates->isNotEmpty())
                      <div style="margin-top:10px; display:flex; flex-direction:column; gap:10px;">
                        @foreach($candidates as $cand)
                          @php
                            $cScore = (int) max(0, min(100, (int)($cand->match_score ?? 0)));
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
                                <button type="button" class="pe-btn pe-btn--black pe-btn-mini" onclick="applyProductToItem({{ $item->id }}, {{ $cand->id }}, false)">
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
                    {{-- ✅ Si el item no tiene match_score, al menos muestra el top de coincidencias --}}
                    <div class="pe-match">
                      <div class="pe-bar"><div style="transform:scaleX({{ $topCandidateScore/100 }});"></div></div>
                      <strong style="color:var(--ink);">{{ $topCandidateScore }}%</strong>
                    </div>
                    <div class="pe-mini" style="margin-top:6px;">Top sugerencia (aún no aplicado)</div>
                  @else
                    <span class="pe-tag">IA pendiente</span>
                  @endif
                </td>

                <td data-label="Cant." class="pe-amount">
                  {{ $qtyInt > 0 ? $qtyInt : '—' }}
                </td>

                <td data-label="Precio unit." class="pe-amount">
                  <span id="pu-{{ $item->id }}">
                    @if($item->precio_unitario)
                      {{ $propuesta->moneda ?? 'MXN' }} {{ number_format((float)$item->precio_unitario,2) }}
                    @else
                      —
                    @endif
                  </span>
                </td>

                <td data-label="Subtotal" class="pe-amount">
                  <span id="sub-{{ $item->id }}">
                    @if($item->subtotal)
                      {{ $propuesta->moneda ?? 'MXN' }} {{ number_format((float)$item->subtotal,2) }}
                    @else
                      —
                    @endif
                  </span>
                </td>
              </tr>
            @endforeach

            @if($propuesta->items->isEmpty())
              <tr>
                <td colspan="7" style="text-align:center; padding:16px; color:var(--muted);">
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

{{-- ===================== MODAL PICKER (BUSCADOR) ===================== --}}
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

<script>
  const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  const CURRENCY = @json($propuesta->moneda ?? 'MXN');
  const routeProductsSearch = @json($routeProductsSearch ? route($routeProductsSearch) : null);
  const routeApplyAjaxTemplate = @json($routeApplyAjaxName ? route($routeApplyAjaxName, ['item' => '__ID__']) : null);

  const solicitadoByItem = @json($solicitadoByItem);
  const preselectedByItem = @json($preselectedByItem);

  let picker = null;
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

    const sku = meta?.sku ? (meta.sku + ' — ') : '';
    const name = meta?.name || 'Producto';
    const brand = meta?.brand || '—';
    const unit = meta?.unit || '—';
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

    current.itemId = itemId;
    current.selectedId = null;

    const solicitado = (solicitadoByItem[itemId] || '—').toString();
    document.getElementById('pickerSubtitle').textContent = solicitado;

    const backdrop = document.getElementById('pickerBackdrop');
    backdrop.style.display = 'flex';
    backdrop.setAttribute('aria-hidden', 'false');

    const metaBox = document.getElementById('pickedMeta');
    if(metaBox){ metaBox.style.display = 'none'; metaBox.innerHTML = ''; }

    if(!picker){
      picker = new TomSelect('#productPicker', {
        valueField: 'id',
        labelField: 'text',
        searchField: ['text'],
        preload: 'focus',
        maxOptions: 50,
        render: {
          option: function(data, escape){
            const m = data.meta || {};
            const t = escape(data.text || '');
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

  function applyProductToItem(itemId, productId, closeAfter=false){
    const url = routeApplyAjaxTemplate.replace('__ID__', itemId);

    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type':'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN': CSRF
      },
      body: JSON.stringify({ product_id: productId })
    })
    .then(async (r) => {
      const json = await r.json().catch(() => ({}));
      if(!r.ok) throw json;
      return json;
    })
    .then(({row, totals}) => {
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

      const puEl  = document.getElementById('pu-' + row.item_id);
      const subEl = document.getElementById('sub-' + row.item_id);
      if(puEl)  puEl.textContent  = money(row.precio_unitario || 0);
      if(subEl) subEl.textContent = money(row.subtotal || 0);

      const tSub = document.getElementById('total-subtotal');
      const tIva = document.getElementById('total-iva');
      const tTot = document.getElementById('total-total');
      if(tSub) tSub.textContent = money(totals?.subtotal || 0);
      if(tIva) tIva.textContent = money(totals?.iva || 0);
      if(tTot) tTot.textContent = money(totals?.total || 0);

      if(closeAfter) closePicker();
    })
    .catch((err) => {
      console.error(err);
      alert('No se pudo aplicar el producto. Revisa consola y laravel.log.');
    });
  }

  // ✅ cerrar con ESC + click fuera
  document.addEventListener('keydown', (e) => { if(e.key === 'Escape') closePicker(); });
  document.getElementById('pickerBackdrop')?.addEventListener('click', (e) => {
    if(e.target?.id === 'pickerBackdrop') closePicker();
  });
</script>
@endsection
