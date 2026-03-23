@extends('layouts.app')

@section('title', 'WMS · Picking & Packing')

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
  <header class="pk-header">
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

  <div class="pk-toolbar">
    <div class="pk-search">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <path d="m21 21-4.3-4.3"></path>
      </svg>
      <input type="text" id="taskSearch" placeholder="Buscar por tarea, orden, operario, SKU, producto o lote..." />
    </div>

    <div class="pk-filter">
      <select id="statusFilter" class="pk-select">
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
    <div class="pk-fast-head">
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
      @forelse($fastFlowCards as $card)
        <article
          class="pk-fast-card {{ $card['status'] !== 'active' ? 'is-muted' : '' }} {{ $card['available_units'] <= 0 ? 'is-disabled' : '' }}"
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
            <div class="pk-progress-fill bg-emerald" style="width: {{ max(0, min(100, $card['progress'])) }}%"></div>
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
        <div class="pk-empty-state">
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
  :root {
    --font-sans: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    --color-bg: #f8fafc;
    --color-card: #ffffff;
    --color-ink: #0f172a;
    --color-muted: #64748b;
    --color-border: #e2e8f0;
    --color-border-soft: #f1f5f9;
    --color-primary: #3b82f6;
    --color-primary-dark: #2563eb;
    --color-primary-soft: #eff6ff;
    --color-success: #10b981;
    --color-success-dark: #059669;
    --color-success-soft: #d1fae5;
    --color-warning: #f59e0b;
    --color-warning-dark: #d97706;
    --color-warning-soft: #fef3c7;
    --color-danger: #ef4444;
    --color-danger-dark: #dc2626;
    --color-danger-soft: #fee2e2;
    --color-neutral: #475569;
    --color-neutral-soft: #f1f5f9;
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.05), 0 4px 6px -4px rgb(0 0 0 / 0.05);
    --shadow-hover: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
    --radius-md: 10px;
    --radius-lg: 16px;
    --radius-xl: 20px;
    --radius-full: 9999px;
  }

  body { background-color: var(--color-bg); }

  .pk-wrap {
    font-family: var(--font-sans);
    max-width: 1320px;
    margin: 0 auto;
    padding: 32px 24px 64px;
    color: var(--color-ink);
  }

  .text-blue { color: var(--color-primary-dark) !important; }
  .text-amber { color: var(--color-warning-dark) !important; }
  .bg-emerald { background-image: linear-gradient(to right, #34d399, #10b981) !important; }
  .pk-dot { margin: 0 4px; opacity: 0.5; }

  .pk-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 32px;
  }

  .pk-actions{
    display:flex;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
  }

  .pk-title {
    margin: 0;
    font-size: 2.25rem;
    line-height: 1.1;
    font-weight: 800;
    letter-spacing: -0.04em;
    color: var(--color-ink);
  }

  .pk-sub {
    margin: 8px 0 0 0;
    color: var(--color-muted);
    font-size: 1rem;
    font-weight: 400;
  }

  .pk-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: none;
    border-radius: var(--radius-md);
    padding: 12px 20px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .pk-btn:active { transform: scale(0.97); }
  .pk-btn-sm { padding: 8px 14px; font-size: 0.85rem; border-radius: 8px; }

  .pk-btn-primary {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
    color: #fff;
    box-shadow: 0 4px 12px rgba(37,99,235,0.25);
  }

  .pk-btn-primary:hover {
    box-shadow: 0 6px 16px rgba(37,99,235,0.35);
    transform: translateY(-1px);
  }

  .pk-btn-secondary {
    background: var(--color-primary-soft);
    color: var(--color-primary-dark);
  }

  .pk-btn-secondary:hover { background: #dbeafe; }
  .pk-btn-secondary:disabled { opacity: 0.5; cursor: not-allowed; background: var(--color-border-soft); color: var(--color-muted); }

  .pk-btn-ghost {
    background: var(--color-card);
    color: var(--color-ink);
    border: 1px solid var(--color-border);
    box-shadow: var(--shadow-sm);
  }

  .pk-btn-ghost:hover { border-color: #cbd5e1; background: #f8fafc; }

  .pk-btn-dark{
    background:#0f172a;
    color:#fff;
    box-shadow: 0 6px 18px rgba(15,23,42,.14);
  }

  .pk-btn-dark:hover{
    background:#111827;
    transform:translateY(-1px);
    box-shadow: 0 10px 20px rgba(15,23,42,.20);
  }

  .pk-btn-disabled{
    background:#cbd5e1;
    color:#475569;
    cursor:not-allowed;
    box-shadow:none;
  }

  .pk-btn-danger {
    background: #fff;
    color: var(--color-danger-dark);
    border: 1px solid #fecaca;
  }

  .pk-btn-danger:hover { background: #fff5f5; }

  .pk-icon-sm { width: 18px; height: 18px; }

  .pk-icon-btn {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--color-border);
    border-radius: 10px;
    background: var(--color-card);
    color: var(--color-muted);
    cursor: pointer;
    transition: all 0.2s;
  }

  .pk-icon-btn:hover { background: var(--color-border-soft); color: var(--color-ink); }

  .pk-toolbar {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 220px;
    gap: 16px;
    margin-bottom: 32px;
  }

  .pk-search {
    position: relative;
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
    transition: box-shadow 0.2s, border-color 0.2s;
  }

  .pk-search:focus-within {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px var(--color-primary-soft);
  }

  .pk-search svg {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: #94a3b8;
  }

  .pk-search input {
    width: 100%;
    height: 48px;
    border: none;
    outline: none;
    background: transparent;
    padding: 0 16px 0 42px;
    color: var(--color-ink);
    font-size: 0.95rem;
    border-radius: var(--radius-md);
  }

  .pk-filter { position: relative; }

  .pk-select {
    width: 100%;
    height: 48px;
    appearance: none;
    -webkit-appearance: none;
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: 0 40px 0 16px;
    color: var(--color-ink);
    font-size: 0.95rem;
    font-weight: 500;
    box-shadow: var(--shadow-sm);
    cursor: pointer;
    transition: border-color 0.2s, box-shadow 0.2s;
  }

  .pk-select:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px var(--color-primary-soft);
  }

  .pk-select-icon {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: var(--color-muted);
    pointer-events: none;
  }

  .pk-fast-wrap { margin-bottom: 40px; }

  .pk-fast-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 20px;
  }

  .pk-fast-title { font-size: 1.25rem; font-weight: 700; color: var(--color-ink); margin: 0; }
  .pk-fast-sub { margin-top: 4px; color: var(--color-muted); font-size: 0.9rem; }
  .pk-fast-counters { display: flex; gap: 12px; flex-wrap: wrap; }

  .pk-count {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: var(--radius-full);
    font-weight: 600;
    font-size: 0.85rem;
    border: 1px solid;
  }

  .pk-count svg { width: 14px; height: 14px; }
  .pk-count-blue { background: var(--color-primary-soft); color: var(--color-primary-dark); border-color: #bfdbfe; }
  .pk-count-amber { background: var(--color-warning-soft); color: var(--color-warning-dark); border-color: #fde68a; }

  .pk-fast-grid,
  .pk-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 20px;
  }

  .pk-fast-card,
  .pk-card {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: 24px;
    box-shadow: var(--shadow-sm);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
  }

  .pk-fast-card:hover,
  .pk-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-hover);
    border-color: #cbd5e1;
  }

  .pk-fast-card.is-muted { opacity: 0.7; filter: grayscale(30%); }
  .pk-fast-card.is-disabled { opacity: 0.5; pointer-events: none; }

  .pk-fast-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 20px; }
  .pk-fast-name { margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--color-ink); line-height: 1.3; }
  .pk-fast-code { margin-top: 6px; color: var(--color-primary-dark); font-size: 0.85rem; font-weight: 600; }
  .pk-fast-subline { margin-top: 4px; color: var(--color-muted); font-size: 0.85rem; }

  .pk-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.02em;
  }

  .is-green { background: var(--color-success-soft); color: var(--color-success-dark); border: 1px solid #a7f3d0; }
  .is-gray { background: var(--color-neutral-soft); color: var(--color-neutral); border: 1px solid #cbd5e1; }

  .pk-pr-low { background: var(--color-neutral-soft); color: var(--color-neutral); }
  .pk-pr-normal { background: var(--color-primary-soft); color: var(--color-primary-dark); }
  .pk-pr-high { background: var(--color-warning-soft); color: var(--color-warning-dark); }
  .pk-pr-urgent { background: var(--color-danger-soft); color: var(--color-danger-dark); }

  .pk-fast-metrics { display: grid; gap: 12px; margin-bottom: 20px; }
  .pk-fast-metrics-4 { grid-template-columns: repeat(4, 1fr); }

  .pk-fast-metric {
    background: #f8fafc;
    border: 1px solid var(--color-border-soft);
    border-radius: 10px;
    padding: 12px 8px;
    text-align: center;
  }

  .pk-fast-metric-value { display: block; font-size: 1.15rem; font-weight: 800; color: var(--color-ink); line-height: 1; }
  .pk-fast-metric-label { display: block; margin-top: 6px; font-size: 0.7rem; color: var(--color-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.03em; }

  .pk-progress-bar {
    height: 6px;
    border-radius: var(--radius-full);
    background: var(--color-border-soft);
    overflow: hidden;
    margin-top: 16px;
  }

  .pk-progress-fill {
    height: 100%;
    border-radius: var(--radius-full);
    background: var(--color-ink);
    transition: width 0.4s ease;
  }

  .pk-fast-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 16px;
    border-top: 1px solid var(--color-border-soft);
    padding-top: 16px;
  }

  .pk-fast-location {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--color-muted);
    font-size: 0.85rem;
    font-weight: 500;
  }

  .pk-fast-location svg { width: 14px; height: 14px; }
  .pk-fast-foot-actions { display: flex; align-items: center; gap: 12px; }

  .pk-link {
    color: var(--color-muted);
    font-weight: 600;
    text-decoration: none;
    font-size: 0.85rem;
    transition: color 0.2s;
  }

  .pk-link:hover { color: var(--color-primary-dark); }

  .pk-card-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; gap: 14px; }
  .pk-card-title { margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--color-ink); }
  .pk-card-order { margin-top: 4px; color: var(--color-muted); font-size: 0.9rem; }

  .pk-card-phase {
    margin-top: 8px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--color-primary-dark);
    font-size: 0.85rem;
    font-weight: 600;
    background: var(--color-primary-soft);
    padding: 4px 10px;
    border-radius: 6px;
  }

  .pk-card-fast {
    margin-top: 8px;
    color: var(--color-success-dark);
    font-size: 0.85rem;
    font-weight: 600;
  }

  .pk-status-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin: 16px 0; }

  .pk-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: var(--radius-full);
    font-size: 0.8rem;
    font-weight: 700;
  }

  .pk-status svg { width: 14px; height: 14px; }
  .pk-st-pending { background: var(--color-warning-soft); color: var(--color-warning-dark); }
  .pk-st-in_progress { background: var(--color-primary-soft); color: var(--color-primary-dark); }
  .pk-st-completed { background: var(--color-success-soft); color: var(--color-success-dark); }
  .pk-st-cancelled { background: var(--color-neutral-soft); color: var(--color-neutral); }

  .pk-assigned {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    color: var(--color-muted);
    font-weight: 500;
  }

  .pk-assigned::before {
    content: '';
    display: block;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #cbd5e1;
  }

  .pk-progress-row { display: flex; justify-content: space-between; margin-top: 16px; font-size: 0.85rem; color: var(--color-muted); font-weight: 500; }
  .pk-progress-row b { color: var(--color-ink); font-size: 0.95rem; font-weight: 700; }

  .pk-ship-row{
    display:flex;
    flex-direction:column;
    gap:8px;
    margin-top:16px;
    padding-top:14px;
    border-top:1px solid var(--color-border-soft);
  }

  .pk-ship-note{
    font-size:12px;
    font-weight:700;
    color:var(--color-muted);
  }

  .pk-ship-note.is-ready{
    color:var(--color-success-dark);
  }

  .pk-empty-state {
    grid-column: 1 / -1;
    background: transparent;
    border: 2px dashed #cbd5e1;
    border-radius: var(--radius-lg);
    padding: 60px 20px;
    text-align: center;
    color: var(--color-muted);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
  }

  .pk-empty-state svg { width: 48px; height: 48px; color: #94a3b8; }
  .pk-empty-state p { margin: 0; font-size: 1.05rem; font-weight: 500; }

  .pk-modal {
    position: fixed;
    inset: 0;
    z-index: 100;
    visibility: hidden;
    opacity: 0;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }

  .pk-modal.is-open { visibility: visible; opacity: 1; }

  .pk-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15,23,42,0.6);
    backdrop-filter: blur(4px);
  }

  .pk-dialog {
    position: relative;
    z-index: 101;
    background: var(--color-card);
    border-radius: var(--radius-xl);
    box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);
    width: 100%;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    transform: translateY(20px) scale(0.95);
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  }

  .pk-modal.is-open .pk-dialog { transform: translateY(0) scale(1); }
  .pk-dialog-lg { max-width: 980px; }

  .pk-dialog-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 24px;
    border-bottom: 1px solid var(--color-border);
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(8px);
    border-top-left-radius: var(--radius-xl);
    border-top-right-radius: var(--radius-xl);
  }

  .pk-dialog-title { margin: 0; font-size: 1.25rem; font-weight: 800; color: var(--color-ink); }
  .pk-dialog-sub { margin: 4px 0 0; color: var(--color-muted); font-size: 0.9rem; }

  .pk-detail {
    padding: 24px;
    overflow-y: auto;
    flex: 1;
    background: #fbfdff;
  }

  .pk-detail-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
    background: var(--color-bg);
    padding: 16px;
    border-radius: var(--radius-lg);
  }

  .pk-detail-meta p { margin: 0 0 8px; color: var(--color-muted); font-size: 0.95rem; }
  .pk-detail-meta b { color: var(--color-ink); font-weight: 600; }
  .pk-detail-meta p:last-child { margin: 0; }

  .pk-detail-progress-label {
    display: flex;
    justify-content: space-between;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--color-ink);
    margin-bottom: 8px;
  }

  .pk-detail-phase {
    margin-top: 24px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    background: var(--color-card);
    box-shadow: var(--shadow-sm);
  }

  .pk-detail-phase-head {
    padding: 16px;
    background: #f8fafc;
    border-bottom: 1px solid var(--color-border);
  }

  .pk-detail-phase-title { font-weight: 800; font-size: 1.05rem; color: var(--color-ink); }
  .pk-detail-phase-sub { margin-top: 4px; color: var(--color-muted); font-size: 0.85rem; font-weight: 500; }

  .pk-detail-item {
    display: block;
    padding: 16px;
    border-bottom: 1px solid var(--color-border-soft);
    transition: background 0.2s;
  }

  .pk-detail-item:last-child { border-bottom: none; }
  .pk-detail-item:hover { background: #f8fafc; }

  .pk-detail-item-main { flex: 1; }
  .pk-detail-item-name {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    font-weight: 700;
    color: var(--color-ink);
    font-size: 1rem;
  }

  .pk-detail-item-sub {
    margin-top: 6px;
    color: var(--color-muted);
    font-size: 0.85rem;
    line-height: 1.5;
  }

  .pk-item-chip {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    text-transform: uppercase;
  }

  .pk-item-chip-fast { background: #dcfce7; color: #166534; }
  .pk-item-chip-batch { background: var(--color-primary-soft); color: var(--color-primary-dark); }
  .pk-item-chip-stage { background: #ecfdf5; color: #047857; }

  .pk-detail-actions {
    display: flex;
    gap: 12px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid var(--color-border);
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
    .pk-wrap { padding: 20px 16px; }

    .pk-dialog {
      margin: auto;
      height: 100%;
      border-radius: 0;
      max-height: 100vh;
      transform: translateY(100%);
      transition: transform 0.3s ease-out;
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

  function getRequiredQty(task){
    return getTaskItems(task).reduce((sum, i) => sum + asNumber(i?.quantity_required), 0);
  }

  function getPickedQty(task){
    return getTaskItems(task).reduce((sum, i) => sum + asNumber(i?.quantity_picked), 0);
  }

  function getStagedQty(task){
    return getTaskItems(task).reduce((sum, i) => sum + asNumber(i?.quantity_staged), 0);
  }

  function getCollectProgress(task){
    const req = getRequiredQty(task);
    const qty = getPickedQty(task);
    return req > 0 ? Math.round((qty / req) * 100) : 0;
  }

  function getStageProgress(task){
    const req = getRequiredQty(task);
    const qty = getStagedQty(task);
    return req > 0 ? Math.round((qty / req) * 100) : 0;
  }

  function getAssignedDisplay(task){
    if(task?.assigned_to) return task.assigned_to;
    const assignedId = Number(task?.assigned_user_id || 0);
    if(!assignedId) return '';
    const found = users.find(u => Number(u.id) === assignedId);
    return found ? found.name : '';
  }

  function isTaskReadyForShipping(task){
    const status = String(task?.status || '');
    if (status !== 'completed') return false;

    const items = getTaskItems(task);
    if (!items.length) return false;

    return items.some(item => {
      const required = asNumber(item?.quantity_required);
      const staged = asNumber(item?.quantity_staged);
      const stagedFlag = Boolean(item?.staged);
      const stageBoxes = ensureArray(item?.staged_boxes);
      const stageAllocs = ensureArray(item?.stage_box_allocations);

      return stagedFlag || staged > 0 || stageBoxes.length > 0 || stageAllocs.length > 0 || (required > 0 && staged >= required);
    });
  }

  function shippingReadyText(task){
    return isTaskReadyForShipping(task)
      ? 'Listo para subir a unidad'
      : 'Completa y ubica la tarea para embarcar';
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
          'Accept': 'application/json',
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
        throw new Error('La respuesta del servidor no fue válida.');
      }

      if (!response.ok) {
        if (data.shipment && data.shipment.id) {
          window.location.href = `/admin/wms/shipping/${data.shipment.id}/scanner`;
          return;
        }

        throw new Error(data.message || 'No se pudo generar el embarque.');
      }

      if (!data.shipment || !data.shipment.id) {
        throw new Error('Se generó el embarque pero no llegó el ID de respuesta.');
      }

      window.location.href = `/admin/wms/shipping/${data.shipment.id}/scanner`;
    } catch (error) {
      alert(error.message || 'Ocurrió un error al generar el embarque.');
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

  function renderTasks(){
    if(!taskGrid) return;

    const q = normalize(searchInput?.value || '');
    const st = statusFilter?.value || 'all';

    const filtered = tasks.filter(task => {
      const items = getTaskItems(task);

      const matchesSearch =
        normalize(task?.task_number).includes(q) ||
        normalize(task?.order_number).includes(q) ||
        normalize(getAssignedDisplay(task)).includes(q) ||
        items.some(item =>
          normalize(item?.product_name).includes(q) ||
          normalize(item?.product_sku).includes(q) ||
          normalize(item?.brand).includes(q) ||
          normalize(item?.model).includes(q) ||
          normalize(item?.batch_code).includes(q)
        );

      const matchesStatus = st === 'all' || String(task?.status || 'pending') === st;

      return matchesSearch && matchesStatus;
    });

    if(!filtered.length){
      taskGrid.innerHTML = `
        <div class="pk-empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
          <p>No se encontraron tareas con estos filtros.</p>
        </div>`;
      applyFastFlowFilter();
      return;
    }

    taskGrid.innerHTML = filtered.map(task => {
      const progress = getCollectProgress(task);
      const ff = getTaskFastFlowSummary(task);
      const assignedDisplay = getAssignedDisplay(task);
      const stageProgress = getStageProgress(task);
      const canShip = isTaskReadyForShipping(task);

      return `
        <article class="pk-card" data-task-id="${esc(task?.id)}">
          <div class="pk-card-top">
            <div>
              <h3 class="pk-card-title">${esc(task?.task_number || 'Tarea sin folio')}</h3>
              ${task?.order_number ? `<div class="pk-card-order">Orden: ${esc(task.order_number)}</div>` : ''}
              ${Number(task?.total_phases || 1) > 1
                ? `<div class="pk-card-phase"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg> ${Number(task?.total_phases || 1)} entregas ligadas</div>`
                : `<div class="pk-card-phase"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> Entrega única</div>`}
              ${ff.lots ? `<div class="pk-card-fast">Fast Flow: ${ff.lots} lote(s) · ${ff.boxes} caja(s)</div>` : ``}
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
            <div class="pk-progress-fill bg-emerald" style="width:${stageProgress}%"></div>
          </div>

          <div class="pk-progress-row">
            <span>${getTaskItems(task).length} producto(s)</span>
            <b>${getStagedQty(task)} / ${getRequiredQty(task)} ubicados</b>
          </div>

          <div class="pk-ship-row">
            ${
              canShip
                ? `
                  <button type="button" class="pk-btn pk-btn-dark pk-btn-sm pk-task-ship-btn" data-action="ship" data-task-id="${esc(task?.id)}">
                    Generar embarque
                  </button>
                  <div class="pk-ship-note is-ready">${shippingReadyText(task)}</div>
                `
                : `
                  <button type="button" class="pk-btn pk-btn-disabled pk-btn-sm" disabled>
                    Generar embarque
                  </button>
                  <div class="pk-ship-note">${shippingReadyText(task)}</div>
                `
            }
          </div>
        </article>
      `;
    }).join('');

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

    renderTasks();

    if(selectedTask){
      renderDetail(selectedTask);
    }
  }

  function itemsByPhase(task, phase){
    return getTaskItems(task).filter(item => Number(item?.delivery_phase || item?.phase || 1) === Number(phase));
  }

  function buildItemMeta(item){
    const parts = [];

    if(item?.product_sku) parts.push(`<span class="text-blue">SKU: ${esc(item.product_sku)}</span>`);
    parts.push(`Ubicación: <b>${esc(item?.location_code || 'N/A')}</b>`);
    parts.push(`Requerido: <b>${Number(item?.quantity_required || 0)} uds</b>`);
    parts.push(`Recolectado: <b>${Number(item?.quantity_picked || 0)} uds</b>`);
    parts.push(`Ubicado: <b>${Number(item?.quantity_staged || 0)} uds</b>`);

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

    detailContent.innerHTML = `
      <div class="pk-detail-head">
        <div class="pk-detail-meta">
          <p>Operario Asignado: <b>${esc(assignedDisplay || 'Sin asignar')}</b></p>
          <p>Orden de Compra/Venta: <b>${esc(selectedTask?.order_number || 'N/A')}</b></p>
          <p>Total de Entregas: <b>${Number(selectedTask?.total_phases || deliveries.length || 1)}</b></p>
          <p>Estatus: <b>${esc(statusLabel[selectedTask?.status] || 'Pendiente')}</b></p>
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
      <div class="pk-progress-bar" style="height: 8px; margin-top: 0; margin-bottom: 14px;">
        <div class="pk-progress-fill" style="width:${progress}%"></div>
      </div>

      <div class="pk-detail-progress-label">
        <span>Progreso de Área de Picking</span>
        <span>${stageProgress}%</span>
      </div>
      <div class="pk-progress-bar" style="height: 8px; margin-top: 0; margin-bottom: 24px;">
        <div class="pk-progress-fill bg-emerald" style="width:${stageProgress}%"></div>
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
                    const fastChips = `
                      ${item?.is_fastflow ? `<span class="pk-item-chip pk-item-chip-fast">FAST FLOW</span>` : ''}
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
                : `<div class="pk-detail-item" style="color:var(--color-muted); text-align:center;">No hay productos asignados a esta entrega</div>`
            }
          </div>
        `;
      }).join('')}

      <div class="pk-detail-actions">
        ${
          canShip
            ? `<button type="button" class="pk-btn pk-btn-dark" id="btnCreateShipment">Generar embarque</button>`
            : `<button type="button" class="pk-btn pk-btn-disabled" disabled>Generar embarque</button>`
        }

        <a href="${shippingIndexUrl}" class="pk-btn pk-btn-ghost">Ir a embarques</a>

        ${
          selectedTask?.status !== 'completed' && selectedTask?.status !== 'cancelled'
            ? `<button type="button" class="pk-btn pk-btn-danger" id="btnCancelTask">Cancelar tarea</button>`
            : ''
        }

        <a href="${scannerUrl}?task_id=${encodeURIComponent(selectedTask?.id || '')}" class="pk-btn pk-btn-primary">Ir a picking escáner</a>
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

  renderTasks();
})();
</script>
@endpush