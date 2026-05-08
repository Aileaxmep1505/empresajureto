@extends('layouts.app')

@section('title', 'WMS · Picking & Packing')
@section('content_class', 'content--flush')
@section('content')
@php
  use Illuminate\Support\Facades\Route;

  $indexUrl      = route('admin.wms.picking.v2');
  $createUrl     = route('admin.wms.picking.v2.create');
  $scannerUrl    = route('admin.wms.picking.scanner.v2');
  $updateUrlBase = url('/admin/wms/picking-v2');

  $shippingIndexUrl = Route::has('admin.wms.shipping.index')
      ? route('admin.wms.shipping.index')
      : '#';

  $usersCatalog = collect($users ?? [])->map(function ($u) {
      return [
          'id'   => data_get($u, 'id'),
          'name' => (string) data_get($u, 'name', 'Usuario'),
      ];
  })->values();

  $productsCatalog = collect($products ?? [])->map(function ($p) {
      return [
          'id'              => data_get($p, 'id'),
          'name'            => (string) data_get($p, 'name', data_get($p, 'product_name', 'Producto')),
          'sku'             => (string) data_get($p, 'sku', ''),
          'barcode'         => (string) data_get($p, 'barcode', data_get($p, 'code', '')),
          'code'            => (string) data_get($p, 'code', ''),
          'brand'           => (string) data_get($p, 'brand', data_get($p, 'marca', '')),
          'model'           => (string) data_get($p, 'model', data_get($p, 'modelo', '')),
          'description'     => (string) data_get($p, 'description', data_get($p, 'descripcion', '')),
          'location_code'   => (string) data_get($p, 'location_code', data_get($p, 'location', data_get($p, 'ubicacion', ''))),
          'available_stock' => (int) data_get($p, 'available_stock', data_get($p, 'stock', 0)),
      ];
  })->values();

  $fastFlowCards = collect($recentBatches ?? [])->map(function($b){
      $batchCode       = (string) data_get($b, 'batch_code', '');
      $productName     = (string) data_get($b, 'product_name', 'Producto');
      $sku             = (string) data_get($b, 'sku', '—');
      $warehouseName   = (string) data_get($b, 'warehouse_name', '—');
      $boxesCount      = (int) data_get($b, 'boxes_count', 0);
      $unitsPerBox     = (int) data_get($b, 'units_per_box', 0);
      $totalUnits      = (int) data_get($b, 'total_units', ($boxesCount * $unitsPerBox));
      $availableBoxesB = (int) data_get($b, 'available_boxes', 0);
      $availableUnitsB = (int) data_get($b, 'available_units', 0);
      $reservedUnitsB  = (int) data_get($b, 'reserved_units', 0);
      $status          = $availableUnitsB > 0 ? 'active' : 'completed';
      $progress        = $boxesCount > 0 ? (int) round(($availableBoxesB / max(1, $boxesCount)) * 100) : 0;

      $searchBlob = implode(' ', [
          $batchCode,
          $productName,
          $sku,
          $warehouseName,
          $availableUnitsB,
          $reservedUnitsB,
      ]);

      return [
          'batch_code'      => $batchCode,
          'product_name'    => $productName,
          'sku'             => $sku,
          'warehouse_name'  => $warehouseName,
          'boxes_count'     => $boxesCount,
          'units_per_box'   => $unitsPerBox,
          'total_units'     => $totalUnits,
          'available_boxes' => $availableBoxesB,
          'available_units' => $availableUnitsB,
          'reserved_units'  => $reservedUnitsB,
          'status'          => $status,
          'progress'        => $progress,
          'show_url'        => $batchCode ? route('admin.wms.fastflow.show', $batchCode) : '#',
          'search_blob'     => $searchBlob,
      ];
  })->values();

  $fastFlowActiveCount   = (int) $fastFlowCards->where('status', 'active')->count();
  $fastFlowReservedUnits = (int) $fastFlowCards->sum('reserved_units');
@endphp

<div class="pk-wrap">
  <header class="pk-header pk-animate-up">
    <div class="pk-header-text">
      <h1 class="pk-title">Picking & Packing</h1>
      <p class="pk-sub">Planeación, surtido, ubicación en staging y transición directa a embarque</p>
    </div>
    <div class="pk-actions">
      <a href="{{ $shippingIndexUrl }}" class="pk-btn pk-btn-ghost">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pk-icon-sm">
          <path d="M3 7h13l3 4v6a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7z"></path>
          <path d="M16 7V5a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v2"></path>
          <circle cx="8.5" cy="18.5" r="1.5"></circle>
          <circle cx="15.5" cy="18.5" r="1.5"></circle>
        </svg>
        Embarques
      </a>

      <a href="{{ $createUrl }}" class="pk-btn pk-btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pk-icon-sm"><path d="M12 5v14M5 12h14"/></svg>
        Nueva Tarea
      </a>
    </div>
  </header>

  <div class="pk-toolbar pk-animate-up" style="animation-delay: 0.1s;">
    <div class="pk-search">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <path d="m21 21-4.3-4.3"></path>
      </svg>
      <input type="text" id="taskSearch" placeholder="Buscar por tarea, orden, operario, SKU, producto o lote..." />
    </div>

    <div class="pk-filter">
      <select id="statusFilter" class="pk-select">
        <option value="active" selected>Pendientes primero</option>
        <option value="all">Todos los estados</option>
        <option value="pending">Pendiente</option>
        <option value="in_progress">En proceso</option>
        <option value="completed">Completada</option>
        <option value="cancelled">Cancelada</option>
      </select>
      <svg class="pk-select-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
    </div>
  </div>

  <section class="pk-fast-wrap">
    <div class="pk-fast-head pk-animate-up" style="animation-delay: 0.15s;">
      <div>
        <h2 class="pk-fast-title">Disponibilidad Fast Flow</h2>
        <p class="pk-fast-sub">Lotes listos para integrarse a nuevas tareas de picking con stock libre real</p>
      </div>
      <div class="pk-fast-counters">
        <span class="pk-count pk-count-blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12l5 5L20 7"/></svg>
          {{ $fastFlowActiveCount }} lote{{ $fastFlowActiveCount === 1 ? '' : 's' }} activo{{ $fastFlowActiveCount === 1 ? '' : 's' }}
        </span>
        <span class="pk-count pk-count-amber">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          {{ number_format($fastFlowReservedUnits) }} uds reservadas
        </span>
      </div>
    </div>

    <div class="pk-fast-grid" id="fastFlowGrid">
      @forelse($fastFlowCards as $index => $card)
        <article
          class="pk-fast-card pk-animate-up {{ $card['status'] !== 'active' ? 'is-muted' : '' }} {{ $card['available_units'] <= 0 ? 'is-disabled' : '' }}"
          style="animation-delay: {{ 0.2 + (min($index, 10) * 0.05) }}s;"
          data-fast-search="{{ \Illuminate\Support\Str::lower($card['search_blob']) }}"
          data-fast-sku="{{ $card['sku'] }}"
          data-fast-name="{{ $card['product_name'] }}"
          data-fast-location="{{ $card['warehouse_name'] }}"
          data-fast-batch="{{ $card['batch_code'] }}"
          data-fast-show-url="{{ $card['show_url'] }}"
          data-fast-units="{{ $card['available_units'] }}"
        >
          <div class="pk-fast-top">
            <div class="pk-fast-info">
              <h3 class="pk-fast-name">{{ $card['product_name'] }}</h3>
              <div class="pk-fast-code">Lote: {{ $card['batch_code'] }}</div>
              <div class="pk-fast-subline">
                {{ $card['sku'] !== '—' ? $card['sku'] : $card['warehouse_name'] }}
                @if($card['units_per_box'] > 0)
                  <span class="pk-dot">·</span> {{ number_format($card['units_per_box']) }} por caja
                @endif
              </div>
            </div>

            @if($card['status'] === 'active')
              <span class="pk-badge is-green">Activo</span>
            @else
              <span class="pk-badge is-gray">Sin stock libre</span>
            @endif
          </div>

          <div class="pk-fast-metrics pk-fast-metrics-4">
            <div class="pk-fast-metric">
              <span class="pk-fast-metric-value">{{ number_format($card['available_boxes']) }}</span>
              <span class="pk-fast-metric-label">Cajas Libres</span>
            </div>
            <div class="pk-fast-metric">
              <span class="pk-fast-metric-value text-blue">{{ number_format($card['available_units']) }}</span>
              <span class="pk-fast-metric-label">Stock Libre</span>
            </div>
            <div class="pk-fast-metric">
              <span class="pk-fast-metric-value text-amber">{{ number_format($card['reserved_units']) }}</span>
              <span class="pk-fast-metric-label">Reservado</span>
            </div>
            <div class="pk-fast-metric">
              <span class="pk-fast-metric-value">{{ number_format($card['total_units']) }}</span>
              <span class="pk-fast-metric-label">Físico Total</span>
            </div>
          </div>

          <div class="pk-progress-bar">
            <div class="pk-progress-fill bg-success" style="width: {{ max(0, min(100, $card['progress'])) }}%"></div>
          </div>

          <div class="pk-fast-foot">
            <div class="pk-fast-location">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              {{ $card['warehouse_name'] }}
            </div>
            <div class="pk-fast-foot-actions">
              <a href="{{ $card['show_url'] }}" class="pk-link" onclick="event.stopPropagation();">Detalles</a>
              <button type="button" class="pk-btn pk-btn-sm pk-btn-secondary" {{ $card['available_units'] <= 0 ? 'disabled' : '' }}>
                Usar en picking
              </button>
            </div>
          </div>
        </article>
      @empty
        <div class="pk-empty-state pk-animate-up" style="animation-delay: 0.2s;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
          <p>No hay lotes de Fast Flow disponibles en este momento.</p>
        </div>
      @endforelse
    </div>

    @if($fastFlowCards->count())
      <div class="pk-empty-state" id="fastFlowEmpty" hidden>
        <p>No encontramos lotes que coincidan con tu búsqueda.</p>
      </div>
    @endif
  </section>

  <div id="taskGrid" class="pk-grid"></div>
</div>

<div class="pk-modal" id="detailModal" aria-hidden="true">
  <div class="pk-backdrop" data-close="detailModal"></div>
  <div class="pk-dialog pk-dialog-lg">
    <div class="pk-dialog-head">
      <div>
        <h2 class="pk-dialog-title" id="detailTitle">Tarea</h2>
        <p class="pk-dialog-sub" id="detailSub">Detalle general de la tarea</p>
      </div>
      <button type="button" class="pk-icon-btn" data-close="detailModal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="pk-detail" id="detailContent"></div>
  </div>
</div>
@endsection

@push('styles')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  :root {
    /* FONDO BLANCO PURO SOLICITADO */
    --bg: #ffffff; 
    --card: #ffffff;
    --title: #111111;
    --ink: #333333;
    --muted: #888888;
    --line: #ebebeb;
    --blue: #007aff;
    --blue-soft: #e6f0ff;
    --success: #15803d;
    --success-soft: #e6ffe6; 
    --danger: #ff4a4a;
    --danger-soft: #ffebeb;
    --warning: #f59e0b;
    --warning-soft: #fef3c7;
  }

  body {
    background-color: #ffffff;
    font-family: 'Quicksand', sans-serif;
    color: var(--ink);
  }

  /* ANIMACIONES DE ENTRADA Y MEJORAS DE MOVIMIENTO */
  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(24px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .pk-animate-up {
    opacity: 0;
    animation: fadeInUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  }

  .pk-wrap {
    max-width: 1320px;
    margin: 0 auto;
    padding: 48px 24px 80px;
  }

  .text-blue { color: var(--blue) !important; }
  .text-amber { color: var(--warning) !important; }
  .bg-success { background-color: var(--success) !important; }
  .pk-dot { margin: 0 4px; opacity: 0.5; color: var(--muted); }

  /* HEADER */
  .pk-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 40px;
  }

  .pk-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
  }

  .pk-title {
    margin: 0;
    font-size: 2.25rem;
    line-height: 1.1;
    font-weight: 700;
    color: var(--title);
    letter-spacing: -0.02em;
  }

  .pk-sub {
    margin: 8px 0 0 0;
    color: var(--muted);
    font-size: 1rem;
    font-weight: 500;
  }

  /* BOTONES Y MEJORAS DE HOVER */
  .pk-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: none;
    border-radius: 8px;
    padding: 12px 24px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    text-decoration: none;
    /* Transición suave premium */
    transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), 
                box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1), 
                background-color 0.3s ease,
                border-color 0.3s ease;
    font-family: 'Quicksand', sans-serif;
  }

  .pk-btn:active { 
    transform: translateY(1px) scale(0.97); 
    box-shadow: none;
  }
  
  .pk-btn-sm { padding: 8px 16px; font-size: 0.85rem; }

  .pk-btn-primary {
    background: var(--blue);
    color: #fff;
  }
  .pk-btn-primary:hover {
    background: #006ce6;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 122, 255, 0.25);
  }

  .pk-btn-secondary {
    background: var(--card);
    color: var(--blue);
    border: 1px solid var(--blue);
  }
  .pk-btn-secondary:hover {
    background: var(--blue-soft);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 122, 255, 0.1);
  }
  .pk-btn-secondary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: var(--bg);
    color: var(--muted);
    border-color: var(--line);
    transform: none;
    box-shadow: none;
  }

  .pk-btn-ghost {
    background: transparent;
    color: var(--muted);
  }
  .pk-btn-ghost:hover {
    background: #f9fafb;
    color: var(--ink);
    transform: translateY(-2px);
  }

  .pk-btn-dark {
    background: var(--blue);
    color: #fff;
  }
  .pk-btn-dark:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 122, 255, 0.25);
  }

  .pk-btn-disabled {
    background: var(--line);
    color: var(--muted);
    cursor: not-allowed;
  }

  .pk-btn-danger {
    background: var(--card);
    color: var(--danger);
    border: 1px solid var(--danger-soft);
  }
  .pk-btn-danger:hover {
    background: var(--danger-soft);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 74, 74, 0.15);
  }

  .pk-icon-sm { width: 18px; height: 18px; }

  .pk-icon-btn {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: var(--card);
    color: var(--muted);
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .pk-icon-btn:hover { 
    background: #f9fafb; 
    color: var(--ink); 
    transform: scale(1.05);
  }
  .pk-icon-btn:active {
    transform: scale(0.95);
  }

  /* TOOLBAR & INPUTS */
  .pk-toolbar {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 220px;
    gap: 16px;
    margin-bottom: 40px;
  }

  .pk-search {
    position: relative;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 8px;
    transition: box-shadow 0.3s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.3s ease;
  }
  .pk-search:focus-within {
    border-color: var(--blue);
    box-shadow: 0 0 0 4px var(--blue-soft);
    transform: translateY(-1px);
  }
  .pk-search svg {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: var(--muted);
    transition: color 0.3s ease;
  }
  .pk-search:focus-within svg { color: var(--blue); }
  
  .pk-search input {
    width: 100%;
    height: 52px;
    border: none;
    outline: none;
    background: transparent;
    padding: 0 16px 0 48px;
    color: var(--ink);
    font-size: 0.95rem;
    font-weight: 500;
    font-family: 'Quicksand', sans-serif;
  }

  .pk-filter { position: relative; }
  .pk-select {
    width: 100%;
    height: 52px;
    appearance: none;
    -webkit-appearance: none;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 8px;
    padding: 0 40px 0 16px;
    color: var(--ink);
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: box-shadow 0.3s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.3s ease, transform 0.3s ease;
    font-family: 'Quicksand', sans-serif;
  }
  .pk-select:focus {
    outline: none;
    border-color: var(--blue);
    box-shadow: 0 0 0 4px var(--blue-soft);
    transform: translateY(-1px);
  }
  .pk-select-icon {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: var(--muted);
    pointer-events: none;
  }

  /* FAST FLOW & CARDS */
  .pk-fast-wrap { margin-bottom: 48px; }
  .pk-fast-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 24px;
  }

  .pk-fast-title { font-size: 1.25rem; font-weight: 700; color: var(--title); margin: 0; }
  .pk-fast-sub { margin-top: 6px; color: var(--muted); font-size: 0.95rem; font-weight: 500; }
  
  .pk-fast-counters { display: flex; gap: 12px; flex-wrap: wrap; }
  .pk-count {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 999px;
    font-weight: 600;
    font-size: 0.85rem;
  }
  .pk-count svg { width: 14px; height: 14px; }
  .pk-count-blue { background: var(--blue-soft); color: var(--blue); }
  .pk-count-amber { background: var(--warning-soft); color: var(--warning); }

  .pk-fast-grid,
  .pk-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 24px;
  }

  .pk-fast-card,
  .pk-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    /* Transición súper fluida */
    transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1), 
                box-shadow 0.5s cubic-bezier(0.16, 1, 0.3, 1),
                border-color 0.4s ease;
    position: relative;
    overflow: hidden;
  }
  
  .pk-fast-card:hover,
  .pk-card:hover {
    transform: translateY(-4px) scale(1.005);
    box-shadow: 0 16px 40px rgba(0,0,0,0.06);
    border-color: #e0e0e0;
  }

  .pk-fast-card.is-muted { opacity: 0.6; filter: grayscale(100%); }
  .pk-fast-card.is-disabled { opacity: 0.5; pointer-events: none; }

  .pk-fast-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 24px; }
  .pk-fast-name { margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--title); line-height: 1.4; }
  .pk-fast-code { margin-top: 6px; color: var(--blue); font-size: 0.85rem; font-weight: 600; }
  .pk-fast-subline { margin-top: 6px; color: var(--muted); font-size: 0.85rem; font-weight: 500; }

  /* BADGES */
  .pk-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  .is-green { background: var(--success-soft); color: var(--success); }
  .is-gray { background: #f9fafb; color: var(--muted); border: 1px solid var(--line); }

  .pk-pr-low { background: #f9fafb; color: var(--muted); border: 1px solid var(--line); }
  .pk-pr-normal { background: var(--blue-soft); color: var(--blue); }
  .pk-pr-high { background: var(--warning-soft); color: var(--warning); }
  .pk-pr-urgent { background: var(--danger-soft); color: var(--danger); }

  /* METRICS */
  .pk-fast-metrics { display: grid; gap: 16px; margin-bottom: 24px; }
  .pk-fast-metrics-4 { grid-template-columns: repeat(4, 1fr); }

  .pk-fast-metric {
    text-align: center;
    transition: transform 0.3s ease;
  }
  .pk-fast-card:hover .pk-fast-metric {
    transform: translateY(-2px);
  }
  .pk-fast-metric-value { display: block; font-size: 1.25rem; font-weight: 700; color: var(--title); line-height: 1.2; }
  .pk-fast-metric-label { display: block; margin-top: 8px; font-size: 0.7rem; color: var(--muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; }

  /* PROGRESS BARS */
  .pk-progress-bar {
    height: 6px;
    border-radius: 999px;
    background: #f0f0f0;
    overflow: hidden;
    margin-top: 16px;
    border: 1px solid var(--line);
  }
  .pk-progress-fill {
    height: 100%;
    border-radius: 999px;
    background: var(--blue);
    transition: width 0.8s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .pk-fast-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 24px;
    border-top: 1px solid var(--line);
    padding-top: 24px;
  }
  .pk-fast-location {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--muted);
    font-size: 0.85rem;
    font-weight: 600;
  }
  .pk-fast-location svg { width: 16px; height: 16px; }
  
  .pk-fast-foot-actions { display: flex; align-items: center; gap: 16px; }

  .pk-link {
    color: var(--muted);
    font-weight: 600;
    text-decoration: none;
    font-size: 0.85rem;
    transition: color 0.3s ease;
  }
  .pk-link:hover { color: var(--blue); }

  /* CARDS: NORMAL TASKS */
  .pk-card-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; gap: 16px; }
  .pk-card-title { margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--title); }
  .pk-card-order { margin-top: 6px; color: var(--muted); font-size: 0.9rem; font-weight: 500; }

  .pk-card-phase {
    margin-top: 12px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--blue);
    font-size: 0.85rem;
    font-weight: 600;
    background: var(--blue-soft);
    padding: 6px 12px;
    border-radius: 6px;
  }
  .pk-card-fast {
    margin-top: 12px;
    color: var(--success);
    font-size: 0.85rem;
    font-weight: 600;
  }

  .pk-status-row { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; margin: 24px 0; }
  .pk-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 700;
  }
  .pk-status svg { width: 16px; height: 16px; }
  
  .pk-st-pending { background: var(--warning-soft); color: var(--warning); }
  .pk-st-in_progress { background: var(--blue-soft); color: var(--blue); }
  .pk-st-completed { background: var(--success-soft); color: var(--success); }
  .pk-st-cancelled { background: #f9fafb; color: var(--muted); border: 1px solid var(--line); }

  .pk-assigned {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    color: var(--muted);
    font-weight: 600;
  }
  .pk-assigned::before {
    content: '';
    display: block;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--line);
  }

  .pk-progress-row { display: flex; justify-content: space-between; margin-top: 20px; font-size: 0.85rem; color: var(--muted); font-weight: 600; }
  .pk-progress-row b { color: var(--title); font-size: 0.95rem; font-weight: 700; }

  .pk-ship-row {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid var(--line);
  }
  .pk-ship-note {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--muted);
  }
  .pk-ship-note.is-ready { color: var(--success); }

  /* EMPTY STATES */
  .pk-empty-state {
    grid-column: 1 / -1;
    background: transparent;
    border: 1px dashed var(--line);
    border-radius: 12px;
    padding: 80px 24px;
    text-align: center;
    color: var(--muted);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
  }
  .pk-empty-state svg { width: 48px; height: 48px; color: var(--line); }
  .pk-empty-state p { margin: 0; font-size: 1.1rem; font-weight: 600; }

  /* MODALS & ANIMACIONES PREMIUM */
  .pk-modal {
    position: fixed;
    inset: 0;
    z-index: 100;
    visibility: hidden;
    pointer-events: none;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
  }
  .pk-modal.is-open { 
    visibility: visible; 
    pointer-events: auto;
  }

  .pk-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(12px);
    opacity: 0;
    transition: opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .pk-modal.is-open .pk-backdrop {
    opacity: 1;
  }

  .pk-dialog {
    position: relative;
    z-index: 101;
    background: var(--card);
    border-radius: 8px; /* Bordes menos redondos como pedido anterior */
    border: 1px solid rgba(0,0,0,0.05);
    box-shadow: 0 24px 64px rgba(0,0,0,0.08);
    width: 100%;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    opacity: 0;
    transform: translateY(30px) scale(0.95);
    transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1), 
                opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .pk-modal.is-open .pk-dialog { 
    opacity: 1;
    transform: translateY(0) scale(1); 
  }
  .pk-dialog-lg { max-width: 900px; }

  .pk-dialog-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 32px;
    border-bottom: 1px solid var(--line);
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }
  .pk-dialog-title { margin: 0; font-size: 1.5rem; font-weight: 700; color: var(--title); }
  .pk-dialog-sub { margin: 8px 0 0; color: var(--muted); font-size: 0.95rem; font-weight: 500; }

  .pk-detail {
    padding: 32px;
    overflow-y: auto;
    flex: 1;
    background: var(--card);
  }

  .pk-detail-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 24px;
    margin-bottom: 32px;
    background: #f9fafb;
    padding: 24px;
    border-radius: 8px;
    border: 1px solid var(--line);
  }
  .pk-detail-meta p { margin: 0 0 12px; color: var(--muted); font-size: 0.95rem; font-weight: 500; }
  .pk-detail-meta b { color: var(--ink); font-weight: 700; }
  .pk-detail-meta p:last-child { margin: 0; }

  .pk-detail-progress-label {
    display: flex;
    justify-content: space-between;
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--title);
    margin-bottom: 12px;
  }

  .pk-detail-phase {
    margin-top: 32px;
    border: 1px solid var(--line);
    border-radius: 8px;
    overflow: hidden;
    background: var(--card);
  }
  .pk-detail-phase-head {
    padding: 24px;
    background: #f9fafb;
    border-bottom: 1px solid var(--line);
  }
  .pk-detail-phase-title { font-weight: 700; font-size: 1.15rem; color: var(--title); }
  .pk-detail-phase-sub { margin-top: 8px; color: var(--muted); font-size: 0.9rem; font-weight: 600; }

  .pk-detail-item {
    display: block;
    padding: 24px;
    border-bottom: 1px solid var(--line);
    transition: background 0.3s ease;
  }
  .pk-detail-item:last-child { border-bottom: none; }
  .pk-detail-item:hover { background: #f9fafb; }

  .pk-detail-item-main { flex: 1; }
  .pk-detail-item-name {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    font-weight: 700;
    color: var(--title);
    font-size: 1.05rem;
  }
  .pk-detail-item-sub {
    margin-top: 12px;
    color: var(--muted);
    font-size: 0.9rem;
    font-weight: 500;
    line-height: 1.6;
  }

  .pk-item-chip {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
  }
  .pk-item-chip-fast { background: var(--success-soft); color: var(--success); }
  .pk-item-chip-batch { background: var(--blue-soft); color: var(--blue); }
  .pk-item-chip-stage { background: #f9fafb; color: var(--muted); border: 1px solid var(--line); }
  .pk-item-chip-virtual { background: var(--blue-soft); color: var(--blue); }
  .pk-item-chip-pending { background: var(--danger-soft); color: var(--danger); }
  .pk-item-chip-ok { background: var(--success-soft); color: var(--success); }
  .pk-card-ops {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid var(--line);
  }

  .pk-detail-actions {
    display: flex;
    gap: 16px;
    margin-top: 40px;
    padding-top: 32px;
    border-top: 1px solid var(--line);
    flex-wrap: wrap;
  }

  @media (max-width: 1024px){
    .pk-fast-grid,
    .pk-grid { grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); }
  }

  @media (max-width: 768px){
    .pk-toolbar { grid-template-columns: 1fr; }
    .pk-fast-metrics-4 { grid-template-columns: repeat(2, 1fr); }
    .pk-title { font-size: 1.75rem; }
    .pk-wrap { padding: 24px 16px; }

    .pk-dialog {
      margin: auto;
      height: 100%;
      border-radius: 0;
      border: none;
      max-height: 100vh;
      transform: translateY(100%);
    }
    .pk-modal.is-open .pk-dialog { transform: translateY(0); }
    .pk-dialog-head { border-radius: 0; }
  }
</style>
@endpush

@push('scripts')
<script>
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

  const rawTasks = @json($tasks ?? []);
  const rawFastFlowBatches = @json($fastFlowCards ?? []);
  const usersCatalog = @json($usersCatalog ?? []);
  const updateUrlBase = @json($updateUrlBase);
  const createUrl = @json($createUrl);
  const scannerUrl = @json($scannerUrl);
  const shippingIndexUrl = @json($shippingIndexUrl);
  const shippingCreateBase = @json(url('/admin/wms/shipping/from-picking'));
  const shippingScannerBase = @json(url('/admin/wms/shipping'));
  const virtualPickupsUrl = @json(Route::has('admin.wms.virtual-pickups.index') ? route('admin.wms.virtual-pickups.index') : url('/admin/wms/virtual-pickups'));
  const generatedShipmentsStorageKey = 'wms:picking:v2:generated-shipment-task-ids';

  let tasks = Array.isArray(rawTasks) ? rawTasks : [];
  const fastFlowBatches = Array.isArray(rawFastFlowBatches) ? rawFastFlowBatches : [];
  const users = Array.isArray(usersCatalog) ? usersCatalog : [];

  const statusLabel = {
    pending: 'Pendiente',
    in_progress: 'En proceso',
    completed: 'Completada',
    cancelled: 'Cancelada'
  };

  const priorityLabel = {
    low: 'Baja',
    normal: 'Normal',
    high: 'Alta',
    urgent: 'Urgente'
  };

  const statusClass = {
    pending: 'pk-st-pending',
    in_progress: 'pk-st-in_progress',
    completed: 'pk-st-completed',
    cancelled: 'pk-st-cancelled'
  };

  const priorityClass = {
    low: 'pk-pr-low',
    normal: 'pk-pr-normal',
    high: 'pk-pr-high',
    urgent: 'pk-pr-urgent'
  };

  const taskGrid = document.getElementById('taskGrid');
  const searchInput = document.getElementById('taskSearch');
  const statusFilter = document.getElementById('statusFilter');
  const fastFlowGrid = document.getElementById('fastFlowGrid');
  const fastFlowEmpty = document.getElementById('fastFlowEmpty');

  const detailModal = document.getElementById('detailModal');
  const detailTitle = document.getElementById('detailTitle');
  const detailSub = document.getElementById('detailSub');
  const detailContent = document.getElementById('detailContent');

  let selectedTask = null;

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

  function ensureArray(value){
    return Array.isArray(value) ? value : [];
  }

  function asNumber(value){
    const n = Number(value || 0);
    return Number.isFinite(n) ? n : 0;
  }

  function boolish(value){
    if (value === true || value === 1) return true;
    const v = String(value ?? '').trim().toLowerCase();
    return ['1', 'true', 'yes', 'si', 'sí', 'on'].includes(v);
  }

  function cleanStatus(value){
    return normalize(value || '').replace(/\s+/g, '_');
  }

  function safeCssEscape(value){
    if (window.CSS && typeof window.CSS.escape === 'function') {
      return window.CSS.escape(String(value));
    }

    return String(value).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
  }


  function getGeneratedShipmentIds(){
    try {
      const value = window.localStorage.getItem(generatedShipmentsStorageKey);
      const parsed = value ? JSON.parse(value) : [];
      return new Set(Array.isArray(parsed) ? parsed.map(id => String(id)) : []);
    } catch (e) {
      return new Set();
    }
  }

  function markGeneratedShipment(taskId){
    if(!taskId) return;

    const ids = getGeneratedShipmentIds();
    ids.add(String(taskId));

    try {
      window.localStorage.setItem(generatedShipmentsStorageKey, JSON.stringify(Array.from(ids)));
    } catch (e) {}

    tasks = tasks.map(task => Number(task?.id) === Number(taskId)
      ? { ...task, shipment_generated: true, has_shipment: true }
      : task
    );

    if(selectedTask && Number(selectedTask?.id) === Number(taskId)){
      selectedTask = { ...selectedTask, shipment_generated: true, has_shipment: true };
    }

    document.dispatchEvent(new CustomEvent('picking:shipment-generated', { detail: { taskId } }));
  }

  function openModal(modal){
    if(!modal) return;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function closeModal(modal){
    if(!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  function formatSchedule(value){
    if(!value) return 'Sin fecha programada';
    try{
      return new Date(value).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' });
    }catch(e){
      return String(value);
    }
  }

  function statusIcon(status){
    if(status === 'completed'){
      return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>`;
    }
    if(status === 'in_progress'){
      return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>`;
    }
    if(status === 'cancelled'){
      return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6"/><path d="M9 9l6 6"/></svg>`;
    }
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><circle cx="12" cy="16" r="1"/></svg>`;
  }

  function ensureTaskShape(task){
    const items = ensureArray(task?.items).map(item => ({
      ...item,
      quantity_required: asNumber(item?.quantity_required),
      quantity_picked: asNumber(item?.quantity_picked),
      quantity_staged: asNumber(item?.quantity_staged),
    }));

    return {
      ...task,
      items
    };
  }

  tasks = tasks.map(ensureTaskShape);

  function getTaskItems(task){
    return ensureArray(task?.items);
  }

  function getTaskDeliveries(task){
    const deliveries = ensureArray(task?.deliveries);
    if (deliveries.length) return deliveries;
    return [{ phase: 1, title: 'Entrega 1', scheduled_for: null, notes: '' }];
  }

  function isVirtualItem(item){
    const source = normalize(item?.source_type || item?.origin_type || item?.type);
    const status = cleanStatus(item?.pickup_status || item?.virtual_status || '');
    const location = normalize(item?.location_code || item?.location || item?.from_location);
    const staging = normalize(item?.staging_location_code || item?.destination || item?.to_location);

    return source === 'virtual'
      || source === 'recoleccion_virtual'
      || source === 'recolección_virtual'
      || boolish(item?.is_virtual)
      || boolish(item?.requires_pickup)
      || boolish(item?.virtual)
      || location === 'recolectar'
      || location === 'recoleccion'
      || status === 'pending'
      || status === 'partial'
      || status === 'collected'
      || status === 'not_collected'
      || status === 'staged'
      || status === 'ready_to_ship'
      || (staging === 'picking' && (boolish(item?.requires_pickup) || source === 'virtual'));
  }

  function isPhysicalItem(item){
    return !isVirtualItem(item);
  }

  function getPhysicalItems(task){
    return getTaskItems(task).filter(isPhysicalItem);
  }

  function getVirtualItems(task){
    return getTaskItems(task).filter(isVirtualItem);
  }

  function getVirtualStatus(item){
    return cleanStatus(item?.pickup_status || item?.virtual_status || 'pending');
  }

  function getVirtualFlowMode(item){
    const mode = cleanStatus(item?.virtual_flow_mode || item?.pickup_flow_mode || 'staging_before_shipping');
    return mode === 'direct_to_delivery' ? 'direct_to_delivery' : 'staging_before_shipping';
  }

  function isVirtualDirectToDelivery(item){
    return getVirtualFlowMode(item) === 'direct_to_delivery'
      || boolish(item?.virtual_auto_loaded_to_shipment)
      || boolish(item?.auto_loaded_to_shipment);
  }

  function isVirtualStagingBeforeShipping(item){
    return !isVirtualDirectToDelivery(item);
  }

  function getVirtualCollectedQty(item){
    const required = asNumber(item?.quantity_required);
    const explicit = asNumber(item?.quantity_collected);
    const picked = asNumber(item?.quantity_picked);
    const staged = asNumber(item?.quantity_staged);
    const status = getVirtualStatus(item);

    if (explicit > 0) return explicit;
    if (['staged', 'ready_to_ship', 'collected'].includes(status)) return required;
    if (picked > 0) return picked;
    if (staged > 0) return staged;
    return 0;
  }

  function isVirtualCollected(item){
    const required = asNumber(item?.quantity_required);
    const status = getVirtualStatus(item);
    const collected = getVirtualCollectedQty(item);

    return ['collected', 'staged', 'ready_to_ship'].includes(status)
      || (required > 0 && collected >= required);
  }

  function isVirtualStaged(item){
    const required = asNumber(item?.quantity_required);
    const status = getVirtualStatus(item);
    const stagedQty = asNumber(item?.quantity_staged);

    return ['staged', 'ready_to_ship'].includes(status)
      || boolish(item?.staged)
      || (required > 0 && stagedQty >= required);
  }

  function isVirtualReadyForShipping(item){
    if (isVirtualDirectToDelivery(item)) {
      return isVirtualCollected(item);
    }

    return isVirtualStaged(item);
  }

  function virtualFlowLabel(item){
    return isVirtualDirectToDelivery(item)
      ? 'Entrega directa con recolector'
      : 'Traer a almacén / staging';
  }

  function hasVirtualItems(task){
    return getVirtualItems(task).length > 0;
  }

  function hasPhysicalItems(task){
    return getPhysicalItems(task).length > 0;
  }

  function hasPendingVirtualPickup(task){
    const virtualItems = getVirtualItems(task);
    return virtualItems.length > 0 && virtualItems.some(item => !isVirtualCollected(item));
  }

  function hasPendingVirtualStaging(task){
    const virtualItems = getVirtualItems(task);
    return virtualItems.length > 0 && virtualItems.some(item => isVirtualStagingBeforeShipping(item) && isVirtualCollected(item) && !isVirtualStaged(item));
  }

  function needsVirtualAction(task){
    const virtualItems = getVirtualItems(task);
    return virtualItems.length > 0 && virtualItems.some(item => !isVirtualReadyForShipping(item));
  }

  function isPhysicalCollectDone(task){
    const items = getPhysicalItems(task);
    if(!items.length) return true;
    return items.every(item => asNumber(item?.quantity_picked) >= asNumber(item?.quantity_required));
  }

  function isPhysicalStageDone(task){
    const items = getPhysicalItems(task);
    if(!items.length) return true;
    return items.every(item => asNumber(item?.quantity_staged) >= asNumber(item?.quantity_required));
  }

  function isVirtualFlowDone(task){
    const items = getVirtualItems(task);
    if(!items.length) return true;
    return items.every(isVirtualReadyForShipping);
  }

  function getRequiredQty(task){
    return getTaskItems(task).reduce((sum, i) => sum + asNumber(i?.quantity_required), 0);
  }

  function getPickedQty(task){
    return getTaskItems(task).reduce((sum, i) => {
      return sum + (isVirtualItem(i) ? getVirtualCollectedQty(i) : asNumber(i?.quantity_picked));
    }, 0);
  }

  function getStagedQty(task){
    return getTaskItems(task).reduce((sum, i) => {
      if(isVirtualItem(i)){
        return sum + (isVirtualReadyForShipping(i) ? asNumber(i?.quantity_required) : 0);
      }
      return sum + asNumber(i?.quantity_staged);
    }, 0);
  }

  function getPhysicalRequiredQty(task){
    return getPhysicalItems(task).reduce((sum, i) => sum + asNumber(i?.quantity_required), 0);
  }

  function getPhysicalStagedQty(task){
    return getPhysicalItems(task).reduce((sum, i) => sum + asNumber(i?.quantity_staged), 0);
  }

  function getVirtualRequiredQty(task){
    return getVirtualItems(task).reduce((sum, i) => sum + asNumber(i?.quantity_required), 0);
  }

  function getVirtualCollectedTotal(task){
    return getVirtualItems(task).reduce((sum, i) => sum + getVirtualCollectedQty(i), 0);
  }

  function getCollectProgress(task){
    const req = getRequiredQty(task);
    const qty = getPickedQty(task);
    return req > 0 ? Math.min(100, Math.round((qty / req) * 100)) : 0;
  }

  function getStageProgress(task){
    const req = getRequiredQty(task);
    const qty = getStagedQty(task);
    return req > 0 ? Math.min(100, Math.round((qty / req) * 100)) : 0;
  }

  function getVirtualPickupUrl(task){
    const pending = getVirtualItems(task).find(item => !isVirtualReadyForShipping(item)) || getVirtualItems(task)[0] || null;
    const lineId = pending?.line_id || pending?.id || pending?.uid || '';
    const base = `${virtualPickupsUrl}/${encodeURIComponent(task?.id || '')}`;

    return lineId ? `${base}?line_id=${encodeURIComponent(lineId)}` : base;
  }

  function getAssignedDisplay(task){
    if(task?.assigned_to) return task.assigned_to;
    const assignedId = Number(task?.assigned_user_id || 0);
    if(!assignedId) return '';
    const found = users.find(u => Number(u.id) === assignedId);
    return found ? found.name : '';
  }

  function taskHasGeneratedShipment(task){
    const localGeneratedIds = getGeneratedShipmentIds();
    if(task?.id && localGeneratedIds.has(String(task.id))) return true;

    const status = String(task?.status || '').toLowerCase();
    const shipmentStatus = String(task?.shipment_status || task?.shipping_status || task?.shipment?.status || task?.shipping?.status || '').toLowerCase();

    const directId = Number(
      task?.shipment_id
      || task?.shipping_id
      || task?.shipment?.id
      || task?.shipping?.id
      || 0
    );

    return Boolean(
      directId > 0
      || task?.has_shipment
      || task?.shipment_generated
      || task?.shipment_created
      || task?.shipping_created
      || task?.shipped_at
      || task?.shipment_created_at
      || task?.delivered_at
      || status === 'shipped'
      || status === 'delivered'
      || shipmentStatus === 'created'
      || shipmentStatus === 'generated'
      || shipmentStatus === 'in_transit'
      || shipmentStatus === 'shipped'
      || shipmentStatus === 'delivered'
    );
  }

  function isTaskReadyForShipping(task){
    if (taskHasGeneratedShipment(task)) return false;

    const items = getTaskItems(task);
    if (!items.length) return false;

    return isPhysicalStageDone(task) && isVirtualFlowDone(task);
  }

  function shippingReadyText(task){
    if (taskHasGeneratedShipment(task)) return 'Embarque generado';
    if (hasPendingVirtualPickup(task)) return 'Pendiente de recolección virtual';
    if (hasPendingVirtualStaging(task)) return 'Pendiente dejar virtual en staging / recepción';
    if (!isPhysicalCollectDone(task)) return 'Pendiente de picking escáner';
    if (!isPhysicalStageDone(task)) return 'Pendiente de área de picking';
    return isTaskReadyForShipping(task) ? 'Listo para abrir embarque' : 'Completa la tarea para embarcar';
  }

  function shipmentScannerUrlFromId(shipmentId){
    return `${shippingScannerBase}/${encodeURIComponent(shipmentId)}/scanner`;
  }

  function findShipmentUrlInText(text){
    const body = String(text || '');
    const scannerMatch = body.match(/\/admin\/wms\/shipping\/(\d+)\/scanner/);
    if(scannerMatch && scannerMatch[1]) return shipmentScannerUrlFromId(scannerMatch[1]);

    const idMatch = body.match(/"(?:shipment_id|shipping_id|id)"\s*:\s*(\d+)/);
    if(idMatch && idMatch[1]) return shipmentScannerUrlFromId(idMatch[1]);

    return '';
  }

  function shipmentUrlFromPayload(data, fallbackText = '', responseUrl = ''){
    const explicitUrl = data?.scanner_url
      || data?.shipment_scanner_url
      || data?.shipping_scanner_url
      || data?.redirect_url
      || data?.url
      || data?.shipment?.scanner_url
      || data?.shipping?.scanner_url
      || '';

    if(explicitUrl) return explicitUrl;

    const shipmentId = data?.shipment?.id
      || data?.shipping?.id
      || data?.shipment_id
      || data?.shipping_id
      || data?.id
      || null;

    if(shipmentId) return shipmentScannerUrlFromId(shipmentId);

    const fromText = findShipmentUrlInText(fallbackText);
    if(fromText) return fromText;

    if(responseUrl && /\/admin\/wms\/shipping\/\d+\/scanner/.test(responseUrl)) {
      return responseUrl;
    }

    return '';
  }

  async function createShipmentFromPicking(pickWaveId){
    if (!pickWaveId) {
      alert('No se encontró el ID de la tarea de picking.');
      return;
    }

    try {
      const response = await fetch(`${shippingCreateBase}/${pickWaveId}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json, text/html, */*',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          vehicle_plate: '',
          vehicle_name: '',
          driver_name: '',
          driver_phone: '',
          route_name: '',
          notes: ''
        })
      });

      const text = await response.text();
      let data = {};

      try {
        data = text ? JSON.parse(text) : {};
      } catch (e) {
        data = {};
      }

      const scannerUrl = shipmentUrlFromPayload(data, text, response.url);

      if (!response.ok && !scannerUrl) {
        throw new Error(data.message || 'No se pudo generar el embarque.');
      }

      markGeneratedShipment(pickWaveId);
      renderTasks({ soft: true });
      closeModal(detailModal);

      if (scannerUrl) {
        window.location.href = scannerUrl;
        return;
      }

      window.location.href = shippingIndexUrl || '/admin/wms/shipping';
    } catch (error) {
      alert(error.message || 'Ocurrió un error al generar el embarque.');
      renderTasks({ soft: true });
      if(selectedTask) renderDetail(selectedTask);
    }
  }

  function getFastFlowMatchesByValues(sku, name, batchCode = ''){
    const skuNorm = normalize(sku);
    const nameNorm = normalize(name);
    const batchNorm = normalize(batchCode);

    return fastFlowBatches.filter(batch => {
      if(String(batch?.status || '') !== 'active') return false;

      const batchSku = normalize(batch?.sku);
      const batchName = normalize(batch?.product_name);
      const batchCodeValue = normalize(batch?.batch_code);

      return (batchNorm && batchCodeValue && batchCodeValue === batchNorm)
        || (skuNorm && batchSku && batchSku === skuNorm)
        || (nameNorm && batchName && batchName === nameNorm);
    });
  }

  function getTaskFastFlowSummary(task){
    const seen = new Set();
    let lots = 0;
    let boxes = 0;
    let freeUnits = 0;

    getTaskItems(task).forEach(item => {
      const matches = getFastFlowMatchesByValues(item?.product_sku, item?.product_name, item?.batch_code);
      matches.forEach(batch => {
        const key = String(batch?.batch_code || '');
        if(!seen.has(key)){
          seen.add(key);
          lots += 1;
          boxes += Number(batch?.available_boxes || 0);
          freeUnits += Number(batch?.available_units || 0);
        }
      });
    });

    return { lots, boxes, freeUnits };
  }

  function applyFastFlowFilter(){
    if(!fastFlowGrid) return;

    const q = (searchInput?.value || '').trim().toLowerCase();
    const cards = Array.from(fastFlowGrid.querySelectorAll('.pk-fast-card'));
    let visible = 0;

    cards.forEach(card => {
      const haystack = (card.dataset.fastSearch || '').toLowerCase();
      const ok = !q || haystack.includes(q);
      card.hidden = !ok;
      if(ok) visible++;
    });

    if(fastFlowEmpty){
      fastFlowEmpty.hidden = visible > 0;
    }
  }

  let firstTaskRender = true;

  function taskSearchMatches(task, q){
    const items = getTaskItems(task);
    return normalize(task?.task_number).includes(q) ||
      normalize(task?.order_number).includes(q) ||
      normalize(getAssignedDisplay(task)).includes(q) ||
      items.some(item =>
        normalize(item?.product_name).includes(q) ||
        normalize(item?.product_sku).includes(q) ||
        normalize(item?.brand).includes(q) ||
        normalize(item?.model).includes(q) ||
        normalize(item?.batch_code).includes(q)
      );
  }

  function getFilteredTasks(){
    const q = normalize(searchInput?.value || '');
    const st = statusFilter?.value || 'all';

    const statusOrder = {
      pending: 1,
      in_progress: 2,
      completed: 3,
      cancelled: 4
    };

    return tasks.filter(task => {
      if (taskHasGeneratedShipment(task)) return false;

      const taskStatus = String(task?.status || 'pending');
      const matchesStatus = st === 'active'
        ? taskStatus !== 'cancelled'
        : (st === 'all' || taskStatus === st);

      return taskSearchMatches(task, q) && matchesStatus;
    }).sort((a, b) => {
      const aReady = isTaskReadyForShipping(a) ? 1 : 0;
      const bReady = isTaskReadyForShipping(b) ? 1 : 0;
      if (aReady !== bReady) return bReady - aReady;

      const aStatus = String(a?.status || 'pending');
      const bStatus = String(b?.status || 'pending');
      const byStatus = (statusOrder[aStatus] || 99) - (statusOrder[bStatus] || 99);
      if (byStatus !== 0) return byStatus;

      return Number(b?.id || 0) - Number(a?.id || 0);
    });
  }

  function renderTaskOperations(task, canShip, shipmentDone){
    if (shipmentDone) {
      return `
        <div class="pk-card-ops">
          <button type="button" class="pk-btn pk-btn-secondary pk-btn-sm" disabled>Todo entregado</button>
        </div>
        <div class="pk-ship-note is-ready">${shippingReadyText(task)}</div>
      `;
    }

    const actions = [];

    if (needsVirtualAction(task)) {
      actions.push(`<a href="${getVirtualPickupUrl(task)}" class="pk-btn pk-btn-primary pk-btn-sm" data-action="virtual" data-task-id="${esc(task?.id)}">Recolección virtual</a>`);
    }

    if (hasPhysicalItems(task) && !isPhysicalStageDone(task)) {
      actions.push(`<a href="${scannerUrl}?task_id=${encodeURIComponent(task?.id || '')}" class="pk-btn pk-btn-secondary pk-btn-sm" data-action="scanner" data-task-id="${esc(task?.id)}">Picking escáner</a>`);
    }

    if (canShip) {
      actions.push(`<button type="button" class="pk-btn pk-btn-primary pk-btn-sm pk-task-ship-btn" data-action="ship" data-task-id="${esc(task?.id)}">Generar / abrir embarque</button>`);
    }

    if (!actions.length) {
      actions.push(`<button type="button" class="pk-btn pk-btn-disabled pk-btn-sm" disabled>Generar embarque</button>`);
    }

    return `
      <div class="pk-card-ops">${actions.join('')}</div>
      <div class="pk-ship-note ${canShip ? 'is-ready' : ''}">${shippingReadyText(task)}</div>
    `;
  }

  function renderTaskCard(task, index, animate = true){
    const progress = getCollectProgress(task);
    const ff = getTaskFastFlowSummary(task);
    const assignedDisplay = getAssignedDisplay(task);
    const stageProgress = getStageProgress(task);
    const canShip = isTaskReadyForShipping(task);
    const shipmentDone = taskHasGeneratedShipment(task);
    const virtualCount = getVirtualItems(task).length;
    const physicalCount = getPhysicalItems(task).length;
    const delay = animate ? 0.1 + Math.min(index * 0.05, 0.5) : 0;
    const animationClass = animate ? 'pk-animate-up' : '';

    return `
      <article class="pk-card ${animationClass}" style="${animate ? `animation-delay: ${delay}s;` : 'opacity:1;'}" data-task-id="${esc(task?.id)}">
        <div class="pk-card-top">
          <div>
            <h3 class="pk-card-title">${esc(task?.task_number || 'Tarea sin folio')}</h3>
            ${task?.order_number ? `<div class="pk-card-order">Orden: ${esc(task.order_number)}</div>` : ''}
            ${Number(task?.total_phases || 1) > 1
              ? `<div class="pk-card-phase"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg> ${Number(task?.total_phases || 1)} entregas ligadas</div>`
              : `<div class="pk-card-phase"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> Entrega única</div>`}
            ${ff.lots ? `<div class="pk-card-fast">Fast Flow: ${ff.lots} lote(s) · ${ff.boxes} caja(s)</div>` : ``}
            ${virtualCount ? `<div class="pk-card-fast" style="color:var(--blue);">Virtual: ${virtualCount} línea(s) · ${getVirtualCollectedTotal(task)}/${getVirtualRequiredQty(task)} uds</div>` : ``}
          </div>

          <span class="pk-badge ${priorityClass[task?.priority] || priorityClass.normal}">
            ${priorityLabel[task?.priority] || 'Normal'}
          </span>
        </div>

        <div class="pk-status-row">
          <span class="pk-status ${statusClass[task?.status] || statusClass.pending}">
            ${statusIcon(task?.status)}
            ${statusLabel[task?.status] || 'Pendiente'}
          </span>
          ${assignedDisplay ? `<span class="pk-assigned">${esc(assignedDisplay)}</span>` : ''}
        </div>

        <div class="pk-progress-row" style="margin-top:0">
          <span>Recolección</span>
          <b>${progress}%</b>
        </div>
        <div class="pk-progress-bar">
          <div class="pk-progress-fill" style="width:${progress}%"></div>
        </div>

        <div class="pk-progress-row" style="margin-top:12px">
          <span>Área de picking</span>
          <b>${stageProgress}%</b>
        </div>
        <div class="pk-progress-bar">
          <div class="pk-progress-fill bg-success" style="width:${stageProgress}%"></div>
        </div>

        <div class="pk-progress-row">
          <span>${getTaskItems(task).length} producto(s)${physicalCount ? ` · ${physicalCount} scanner` : ''}${virtualCount ? ` · ${virtualCount} virtual` : ''}</span>
          <b>${getStagedQty(task)} / ${getRequiredQty(task)} ubicados</b>
        </div>

        ${renderTaskOperations(task, canShip, shipmentDone)}
      </article>
    `;
  }

  function renderTasks(options = {}){
    if(!taskGrid) return;

    const animate = firstTaskRender && !options.soft;
    const filtered = getFilteredTasks();

    if(!filtered.length){
      const emptyHtml = `
        <div class="pk-empty-state pk-animate-up" data-empty-state="1">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12"/></svg>
          <p>No se encontraron tareas con estos filtros.</p>
        </div>`;

      if(taskGrid.innerHTML.trim() !== emptyHtml.trim()) taskGrid.innerHTML = emptyHtml;
      firstTaskRender = false;
      applyFastFlowFilter();
      return;
    }

    const nextIds = filtered.map(task => String(task?.id || ''));
    const currentCards = Array.from(taskGrid.querySelectorAll('.pk-card[data-task-id]'));
    const currentIds = currentCards.map(card => String(card.dataset.taskId || ''));

    if(taskGrid.querySelector('[data-empty-state]')) taskGrid.innerHTML = '';

    if(firstTaskRender || currentIds.join('|') !== nextIds.join('|')){
      taskGrid.innerHTML = filtered.map((task, index) => renderTaskCard(task, index, animate)).join('');
    } else {
      filtered.forEach((task, index) => {
        const card = taskGrid.querySelector(`.pk-card[data-task-id="${safeCssEscape(String(task?.id || ''))}"]`);
        if(!card) return;
        const nextHtml = renderTaskCard(task, index, false);
        if(card.outerHTML !== nextHtml){
          card.outerHTML = nextHtml;
        }
      });
    }

    firstTaskRender = false;
    applyFastFlowFilter();
  }


  async function patchTask(id, payload){
    const response = await fetch(`${updateUrlBase}/${id}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(payload)
    });

    const text = await response.text();
    let data = {};

    try {
      data = text ? JSON.parse(text) : {};
    } catch (e) {
      throw new Error('La respuesta del servidor no fue válida.');
    }

    if(!response.ok || !data.ok){
      throw new Error(data.message || 'No se pudo actualizar la tarea.');
    }

    const updatedTask = ensureTaskShape(data.task);

    tasks = tasks.map(t => Number(t.id) === Number(id) ? updatedTask : t);

    if(selectedTask && Number(selectedTask.id) === Number(id)){
      selectedTask = updatedTask;
    }

    renderTasks({ soft: true });

    if(selectedTask){
      renderDetail(selectedTask);
    }
  }

  function itemsByPhase(task, phase){
    return getTaskItems(task).filter(item => Number(item?.delivery_phase || item?.phase || 1) === Number(phase));
  }

  function buildItemMeta(item){
    const parts = [];
    const virtual = isVirtualItem(item);

    if(item?.product_sku) parts.push(`<span class="text-blue">SKU: ${esc(item.product_sku)}</span>`);
    parts.push(`Ubicación: <b>${esc(item?.location_code || 'N/A')}</b>`);
    parts.push(`Requerido: <b>${Number(item?.quantity_required || 0)} uds</b>`);

    if(virtual){
      parts.push(`Virtual recolectado: <b>${getVirtualCollectedQty(item)} uds</b>`);
      parts.push(`Estado virtual: <b>${esc(getVirtualStatus(item) || 'pending')}</b>`);
      parts.push(`Flujo: <b>${esc(virtualFlowLabel(item))}</b>`);
      if(item?.virtual_sold_label) parts.push(`<b>${esc(item.virtual_sold_label)}</b>`);
      if(item?.virtual_order_number || selectedTask?.order_number) parts.push(`Orden: <b>${esc(item.virtual_order_number || selectedTask?.order_number || 'N/A')}</b>`);
      if(item?.virtual_pick_wave_number || selectedTask?.task_number) parts.push(`Picking: <b>${esc(item.virtual_pick_wave_number || selectedTask?.task_number || 'N/A')}</b>`);
      if(item?.staging_location_code && isVirtualStagingBeforeShipping(item)) parts.push(`Dónde dejar: <b>${esc(item.staging_location_code)}</b>`);
    } else {
      parts.push(`Recolectado: <b>${Number(item?.quantity_picked || 0)} uds</b>`);
      parts.push(`Ubicado: <b>${Number(item?.quantity_staged || 0)} uds</b>`);
    }

    if(item?.brand) parts.push(esc(item.brand));
    if(item?.model) parts.push(esc(item.model));

    return parts.join(' <span class="pk-dot">·</span> ');
  }


  function renderDetail(task){
    if(!task || !detailContent || !detailTitle || !detailModal) return;

    selectedTask = ensureTaskShape(task);

    detailTitle.textContent = `Tarea ${selectedTask?.task_number || ''}`;
    detailSub.textContent = 'Resumen de tarea';

    const progress = getCollectProgress(selectedTask);
    const deliveries = getTaskDeliveries(selectedTask);
    const assignedDisplay = getAssignedDisplay(selectedTask);
    const stageProgress = getStageProgress(selectedTask);
    const canShip = isTaskReadyForShipping(selectedTask);
    const shipmentDone = taskHasGeneratedShipment(selectedTask);
    const hasVirtual = hasVirtualItems(selectedTask);
    const hasPhysical = hasPhysicalItems(selectedTask);

    const detailActions = [];

    if (shipmentDone) {
      detailActions.push(`<button type="button" class="pk-btn pk-btn-secondary" disabled>Todo entregado</button>`);
    } else {
      if (needsVirtualAction(selectedTask)) {
        detailActions.push(`<a href="${getVirtualPickupUrl(selectedTask)}" class="pk-btn pk-btn-primary">Recolección virtual</a>`);
      }

      if (hasPhysical && !isPhysicalStageDone(selectedTask)) {
        detailActions.push(`<a href="${scannerUrl}?task_id=${encodeURIComponent(selectedTask?.id || '')}" class="pk-btn pk-btn-secondary">Ir a picking escáner</a>`);
      }

      if (canShip) {
        detailActions.push(`<button type="button" class="pk-btn pk-btn-primary" id="btnCreateShipment">Generar / abrir embarque</button>`);
      } else {
        detailActions.push(`<button type="button" class="pk-btn pk-btn-disabled" disabled>Generar embarque</button>`);
      }
    }

    detailActions.push(`<a href="${shippingIndexUrl}" class="pk-btn pk-btn-ghost">Ir a embarques</a>`);

    if (selectedTask?.status !== 'completed' && selectedTask?.status !== 'cancelled') {
      detailActions.push(`<button type="button" class="pk-btn pk-btn-danger" id="btnCancelTask">Cancelar tarea</button>`);
    }

    detailContent.innerHTML = `
      <div class="pk-detail-head">
        <div class="pk-detail-meta">
          <p>Operario Asignado: <b>${esc(assignedDisplay || 'Sin asignar')}</b></p>
          <p>Orden de Compra/Venta: <b>${esc(selectedTask?.order_number || 'N/A')}</b></p>
          <p>Total de Entregas: <b>${Number(selectedTask?.total_phases || deliveries.length || 1)}</b></p>
          <p>Estatus: <b>${esc(statusLabel[selectedTask?.status] || 'Pendiente')}</b></p>
          <p>Picking físico: <b>${getPhysicalStagedQty(selectedTask)} / ${getPhysicalRequiredQty(selectedTask)} ubicado(s)</b></p>
          <p>Recolección virtual: <b>${getVirtualCollectedTotal(selectedTask)} / ${getVirtualRequiredQty(selectedTask)} recolectado(s)</b></p>
          <p>Embarque: <b>${esc(shippingReadyText(selectedTask))}</b></p>
        </div>
        <span class="pk-badge ${priorityClass[selectedTask?.priority] || priorityClass.normal}">
          ${priorityLabel[selectedTask?.priority] || 'Normal'}
        </span>
      </div>

      <div class="pk-detail-progress-label">
        <span>Progreso de Recolección</span>
        <span>${progress}%</span>
      </div>
      <div class="pk-progress-bar" style="height: 8px; margin-top: 0; margin-bottom: 16px;">
        <div class="pk-progress-fill" style="width:${progress}%"></div>
      </div>

      <div class="pk-detail-progress-label">
        <span>Progreso de Área de Picking</span>
        <span>${stageProgress}%</span>
      </div>
      <div class="pk-progress-bar" style="height: 8px; margin-top: 0; margin-bottom: 32px;">
        <div class="pk-progress-fill bg-success" style="width:${stageProgress}%"></div>
      </div>

      ${deliveries.map(delivery => {
        const phaseItems = itemsByPhase(selectedTask, delivery?.phase);

        return `
          <div class="pk-detail-phase">
            <div class="pk-detail-phase-head">
              <div class="pk-detail-phase-title">${esc(delivery?.title || ('Entrega ' + delivery?.phase))}</div>
              <div class="pk-detail-phase-sub">
                ${esc(formatSchedule(delivery?.scheduled_for))}${delivery?.notes ? ' · ' + esc(delivery.notes) : ''}
                <span class="pk-dot">·</span> ${phaseItems.length} producto(s)
              </div>
            </div>

            ${
              phaseItems.length
                ? phaseItems.map((item) => {
                    const virtual = isVirtualItem(item);
                    const fastChips = `
                      ${item?.is_fastflow ? `<span class="pk-item-chip pk-item-chip-fast">FAST FLOW</span>` : ''}
                      ${virtual ? `<span class="pk-item-chip pk-item-chip-virtual">Virtual</span>` : ''}
                      ${virtual && !isVirtualCollected(item) ? `<span class="pk-item-chip pk-item-chip-pending">Recolectar</span>` : ''}
                      ${virtual && isVirtualDirectToDelivery(item) ? `<span class="pk-item-chip pk-item-chip-ok">Entrega directa</span>` : ''}
                      ${virtual && isVirtualStagingBeforeShipping(item) ? `<span class="pk-item-chip pk-item-chip-stage">Debe pasar recepción</span>` : ''}
                      ${virtual && isVirtualStaged(item) ? `<span class="pk-item-chip pk-item-chip-ok">Staging listo</span>` : ''}
                      ${item?.batch_code ? `<span class="pk-item-chip pk-item-chip-batch">Lote ${esc(item.batch_code)}</span>` : ''}
                      ${item?.staging_location_code ? `<span class="pk-item-chip pk-item-chip-stage">Área ${esc(item.staging_location_code)}</span>` : ''}
                    `;

                    return `
                      <div class="pk-detail-item">
                        <div class="pk-detail-item-main">
                          <div class="pk-detail-item-name">
                            <span>${esc(item?.product_name || 'Producto')}</span>
                            ${fastChips}
                          </div>
                          <div class="pk-detail-item-sub">
                            ${buildItemMeta(item)}
                          </div>
                        </div>
                      </div>
                    `;
                }).join('')
                : `<div class="pk-detail-item" style="color:var(--muted); text-align:center;">No hay productos asignados a esta entrega</div>`
            }
          </div>
        `;
      }).join('')}

      <div class="pk-detail-actions">
        ${detailActions.join('')}
      </div>
    `;

    openModal(detailModal);

    const btnCancel = document.getElementById('btnCancelTask');
    if(btnCancel){
      btnCancel.onclick = async () => {
        if(!confirm('¿Deseas cancelar esta tarea? Esto quitará la reserva y devolverá el stock a disponible.')) return;

        try {
          await patchTask(selectedTask.id, {
            status: 'cancelled',
            completed_at: null,
            release_reservation: true,
            restore_available: true
          });

          closeModal(detailModal);
        } catch (error) {
          alert(error.message || 'No se pudo cancelar la tarea.');
        }
      };
    }

    const btnCreateShipment = document.getElementById('btnCreateShipment');
    if(btnCreateShipment){
      btnCreateShipment.onclick = function(){
        btnCreateShipment.disabled = true;
        btnCreateShipment.textContent = 'Abriendo embarque...';
        createShipmentFromPicking(selectedTask?.id);
      };
    }
  }


  document.querySelectorAll('[data-close]').forEach(btn => {
    btn.addEventListener('click', function(){
      const modal = document.getElementById(this.dataset.close);
      closeModal(modal);
    });
  });

  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape'){
      if(detailModal?.classList.contains('is-open')) closeModal(detailModal);
    }
  });

  if(searchInput){
    searchInput.addEventListener('input', renderTasks);
  }

  if(statusFilter){
    statusFilter.addEventListener('change', renderTasks);
  }

  if(taskGrid){
    taskGrid.addEventListener('click', function(e){
      const shipBtn = e.target.closest('[data-action="ship"]');
      if (shipBtn) {
        e.preventDefault();
        e.stopPropagation();
        const taskId = Number(shipBtn.dataset.taskId || 0);
        if (taskId) {
          shipBtn.disabled = true;
          shipBtn.textContent = 'Abriendo embarque...';
          createShipmentFromPicking(taskId);
        }
        return;
      }

      const card = e.target.closest('.pk-card[data-task-id]');
      if(!card) return;

      const taskId = Number(card.dataset.taskId);
      const task = tasks.find(t => Number(t?.id) === taskId);

      if(task){
        renderDetail(task);
      }
    });
  }

  if(fastFlowGrid){
    fastFlowGrid.addEventListener('click', function(e){
      const card = e.target.closest('.pk-fast-card');
      if(!card) return;

      const availableUnits = Number(card.dataset.fastUnits || 0);
      if(availableUnits <= 0) return;

      const sku = card.dataset.fastSku || '';
      const name = card.dataset.fastName || '';
      const batch = card.dataset.fastBatch || '';

      const url = new URL(createUrl, window.location.origin);
      if(sku) url.searchParams.set('sku', sku);
      if(name) url.searchParams.set('name', name);
      if(batch) url.searchParams.set('batch', batch);

      window.location.href = url.toString();
    });
  }

  async function refreshTasksFromServer(){
    try {
      const response = await fetch(window.location.href, {
        method: 'GET',
        headers: {
          'Accept': 'text/html,application/xhtml+xml',
          'X-Requested-With': 'XMLHttpRequest'
        },
        cache: 'no-store'
      });

      if(!response.ok) return;

      const html = await response.text();
      const match = html.match(/const\s+rawTasks\s*=\s*(.*?);\s*const\s+rawFastFlowBatches/s);
      if(!match || !match[1]) return;

      const parsedTasks = JSON.parse(match[1]);
      if(!Array.isArray(parsedTasks)) return;

      tasks = parsedTasks.map(ensureTaskShape).map(task => {
        const localGeneratedIds = getGeneratedShipmentIds();
        return task?.id && localGeneratedIds.has(String(task.id))
          ? { ...task, shipment_generated: true, has_shipment: true }
          : task;
      });

      renderTasks({ soft: true });
    } catch (e) {}
  }

  document.addEventListener('picking:shipment-generated', () => renderTasks({ soft: true }));

  window.addEventListener('storage', function(e){
    if(e.key === generatedShipmentsStorageKey) renderTasks({ soft: true });
  });

  document.addEventListener('visibilitychange', function(){
    if(!document.hidden) refreshTasksFromServer();
  });

  renderTasks();
  setInterval(refreshTasksFromServer, 7000);
})();
</script>
@endpush