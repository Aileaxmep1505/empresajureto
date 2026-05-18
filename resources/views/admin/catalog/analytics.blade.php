@extends('layouts.app')
@section('title','Analíticas de Inventario')

@push('styles')
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
  }

  html, body {
    background: var(--bg);
  }

  body {
    font-family: 'Quicksand', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    color: var(--ink);
  }

  .analytics-wrap {
    max-width: 1280px;
    margin: 0 auto;
    padding: 24px 16px 44px;
  }

  .page-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 18px;
  }

  .page-title {
    margin: 0;
    color: #111111;
    font-size: clamp(1.7rem, 3vw, 2.45rem);
    font-weight: 700;
    letter-spacing: -0.04em;
  }

  .page-subtitle {
    margin: 8px 0 0;
    color: var(--muted);
    font-size: .98rem;
    max-width: 820px;
    line-height: 1.55;
    font-weight: 600;
  }

  .actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
  }

  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    padding: 10px 15px;
    border-radius: 999px;
    text-decoration: none;
    border: 0;
    cursor: pointer;
    font-weight: 700;
    transition: transform .14s ease, box-shadow .14s ease, background .14s ease, border-color .14s ease;
  }

  .btn:active {
    transform: scale(.98);
  }

  .btn-primary {
    background: var(--blue);
    color: #ffffff;
    box-shadow: 0 8px 20px rgba(0,122,255,.12);
  }

  .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 28px rgba(0,122,255,.16);
  }

  .btn-ghost {
    background: transparent;
    color: #555555;
    border: 1px solid var(--line);
  }

  .btn-ghost:hover {
    background: #f9fafb;
    transform: translateY(-1px);
  }

  .filters-card,
  .card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
  }

  .filters-card {
    padding: 14px;
    margin-bottom: 18px;
  }

  .filters-form {
    display: grid;
    grid-template-columns: 1fr 180px auto auto;
    gap: 10px;
    align-items: center;
  }

  .input,
  .select {
    width: 100%;
    min-height: 42px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #ffffff;
    color: var(--ink);
    padding: 0 12px;
    font-weight: 600;
    outline: none;
    transition: border-color .14s ease, box-shadow .14s ease;
  }

  .input:focus,
  .select:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .check {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #555555;
    font-weight: 700;
    white-space: nowrap;
  }

  .check input {
    width: 16px;
    height: 16px;
    accent-color: var(--blue);
  }

  .kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 14px;
  }

  .kpi {
    padding: 18px;
    transition: transform .14s ease, box-shadow .14s ease;
  }

  .kpi:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 26px rgba(0,0,0,.04);
  }

  .kpi-label {
    color: var(--muted);
    font-size: .84rem;
    font-weight: 700;
    margin-bottom: 8px;
  }

  .kpi-value {
    color: #111111;
    font-size: 1.75rem;
    font-weight: 700;
    letter-spacing: -0.04em;
  }

  .kpi-note {
    margin-top: 8px;
    color: var(--muted);
    font-size: .82rem;
    font-weight: 600;
  }

  .grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 14px;
  }

  .grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 14px;
    margin-bottom: 14px;
  }

  .panel {
    padding: 18px;
  }

  .panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
  }

  .panel-title {
    margin: 0;
    color: #111111;
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: -0.02em;
  }

  .panel-sub {
    margin: 4px 0 0;
    color: var(--muted);
    font-size: .82rem;
    line-height: 1.4;
    font-weight: 600;
  }

  .badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
    border-radius: 999px;
    font-weight: 700;
    font-size: .78rem;
  }

  .badge-info {
    background: var(--blue-soft);
    color: var(--blue);
  }

  .badge-success {
    background: var(--success-soft);
    color: var(--success);
  }

  .badge-danger {
    background: var(--danger-soft);
    color: var(--danger);
  }

  .bar-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .bar-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 96px;
    gap: 12px;
    align-items: center;
  }

  .bar-name {
    min-width: 0;
    font-weight: 700;
    color: #333333;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .bar-track {
    height: 9px;
    background: #f3f4f6;
    border-radius: 999px;
    overflow: hidden;
    margin-top: 7px;
  }

  .bar-fill {
    height: 100%;
    width: var(--w);
    background: var(--blue);
    border-radius: 999px;
  }

  .bar-value {
    text-align: right;
    color: #111111;
    font-weight: 700;
    font-size: .86rem;
  }

  .mini-table {
    width: 100%;
    border-collapse: collapse;
  }

  .mini-table th,
  .mini-table td {
    padding: 11px 8px;
    border-bottom: 1px solid var(--line);
    text-align: left;
    vertical-align: middle;
  }

  .mini-table th {
    color: #111111;
    font-weight: 700;
    font-size: .78rem;
    background: #ffffff;
  }

  .mini-table td {
    color: #333333;
    font-size: .86rem;
    font-weight: 600;
  }

  .muted {
    color: var(--muted);
  }

  .product-name {
    max-width: 280px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 700;
    color: #111111;
  }

  .empty {
    padding: 18px;
    border: 1px dashed var(--line);
    border-radius: 12px;
    color: var(--muted);
    font-weight: 700;
    text-align: center;
    background: #ffffff;
  }

  @media(max-width: 980px) {
    .kpi-grid,
    .grid-2,
    .grid-3 {
      grid-template-columns: 1fr 1fr;
    }

    .filters-form {
      grid-template-columns: 1fr 1fr;
    }
  }

  @media(max-width: 680px) {
    .page-head {
      flex-direction: column;
    }

    .actions {
      width: 100%;
      justify-content: flex-start;
    }

    .kpi-grid,
    .grid-2,
    .grid-3,
    .filters-form {
      grid-template-columns: 1fr;
    }
  }
</style>
@endpush

@section('content')
@php
  $maxTopStock = max(1, (float) $topStock->max(fn($it) => (float)($it->stock ?? 0)));
  $maxCategoryStock = max(1, (float) $categoryStats->max('stock'));
@endphp

<div class="analytics-wrap">

  <div class="page-head">
    <div>
      <h1 class="page-title">Analíticas de inventario</h1>
      <p class="page-subtitle">
        Resumen profesional del catálogo: dinero total en inventario, productos con más y menos stock,
        stock crítico, precios, publicación, destacados y comportamiento de movimiento cuando exista historial.
      </p>
    </div>

    <div class="actions">
      <a href="{{ route('admin.catalog.index') }}" class="btn btn-ghost">Volver al catálogo</a>
      <a href="{{ route('admin.catalog.analytics.pdf', request()->query()) }}" class="btn btn-primary">Descargar PDF</a>
    </div>
  </div>

  <div class="filters-card">
    <form method="GET" action="{{ route('admin.catalog.analytics') }}" class="filters-form">
      <input class="input" type="search" name="s" value="{{ $filters['s'] ?? '' }}" placeholder="Buscar por nombre, SKU o slug...">

      <select class="select" name="status">
        <option value="">Todos</option>
        <option value="1" @selected((string)($filters['status'] ?? '') === '1')>Publicados</option>
        <option value="0" @selected((string)($filters['status'] ?? '') === '0')>Borradores</option>
        <option value="2" @selected((string)($filters['status'] ?? '') === '2')>Ocultos</option>
      </select>

      <label class="check">
        <input type="checkbox" name="featured_only" value="1" @checked($filters['featured_only'] ?? false)>
        Solo destacados
      </label>

      <button class="btn btn-primary" type="submit">Filtrar</button>
    </form>
  </div>

  <div class="kpi-grid">
    <div class="card kpi">
      <div class="kpi-label">Valor total de inventario</div>
      <div class="kpi-value">${{ number_format($summary['total_money'], 2) }}</div>
      <div class="kpi-note">Stock × precio vigente</div>
    </div>

    <div class="card kpi">
      <div class="kpi-label">Productos registrados</div>
      <div class="kpi-value">{{ number_format($summary['total_products']) }}</div>
      <div class="kpi-note">{{ number_format($summary['total_stock']) }} piezas totales</div>
    </div>

    <div class="card kpi">
      <div class="kpi-label">Stock crítico</div>
      <div class="kpi-value">{{ number_format($summary['critical']) }}</div>
      <div class="kpi-note">{{ number_format($summary['no_stock']) }} sin stock</div>
    </div>

    <div class="card kpi">
      <div class="kpi-label">Publicación</div>
      <div class="kpi-value">{{ number_format($summary['published']) }}</div>
      <div class="kpi-note">{{ number_format($summary['draft']) }} borrador · {{ number_format($summary['hidden']) }} ocultos</div>
    </div>
  </div>

  <div class="grid-3">
    <div class="card panel">
      <div class="panel-head">
        <div>
          <h2 class="panel-title">Publicado en web</h2>
          <p class="panel-sub">Estado del catálogo interno.</p>
        </div>
        <span class="badge badge-info">{{ $summary['total_products'] > 0 ? round(($summary['published'] / $summary['total_products']) * 100) : 0 }}%</span>
      </div>

      <div class="bar-list">
        <div class="bar-row">
          <div>
            <div class="bar-name">Publicados</div>
            <div class="bar-track">
              <div class="bar-fill" style="--w:{{ $summary['total_products'] > 0 ? ($summary['published'] / $summary['total_products']) * 100 : 0 }}%"></div>
            </div>
          </div>
          <div class="bar-value">{{ $summary['published'] }}</div>
        </div>

        <div class="bar-row">
          <div>
            <div class="bar-name">No publicados</div>
            <div class="bar-track">
              <div class="bar-fill" style="--w:{{ $summary['total_products'] > 0 ? (($summary['draft'] + $summary['hidden']) / $summary['total_products']) * 100 : 0 }}%"></div>
            </div>
          </div>
          <div class="bar-value">{{ $summary['draft'] + $summary['hidden'] }}</div>
        </div>
      </div>
    </div>

    <div class="card panel">
      <div class="panel-head">
        <div>
          <h2 class="panel-title">Mercado Libre</h2>
          <p class="panel-sub">Productos con ID de publicación.</p>
        </div>
        <span class="badge badge-info">{{ $summary['total_products'] > 0 ? round(($summary['meli_published'] / $summary['total_products']) * 100) : 0 }}%</span>
      </div>

      <div class="bar-list">
        <div class="bar-row">
          <div>
            <div class="bar-name">Con publicación</div>
            <div class="bar-track">
              <div class="bar-fill" style="--w:{{ $summary['total_products'] > 0 ? ($summary['meli_published'] / $summary['total_products']) * 100 : 0 }}%"></div>
            </div>
          </div>
          <div class="bar-value">{{ $summary['meli_published'] }}</div>
        </div>

        <div class="bar-row">
          <div>
            <div class="bar-name">Pendientes</div>
            <div class="bar-track">
              <div class="bar-fill" style="--w:{{ $summary['total_products'] > 0 ? ($summary['meli_pending'] / $summary['total_products']) * 100 : 0 }}%"></div>
            </div>
          </div>
          <div class="bar-value">{{ $summary['meli_pending'] }}</div>
        </div>
      </div>
    </div>

    <div class="card panel">
      <div class="panel-head">
        <div>
          <h2 class="panel-title">Destacados</h2>
          <p class="panel-sub">Productos marcados como importantes.</p>
        </div>
        <span class="badge badge-success">{{ $summary['featured'] }}</span>
      </div>

      <div class="bar-list">
        <div class="bar-row">
          <div>
            <div class="bar-name">Destacados</div>
            <div class="bar-track">
              <div class="bar-fill" style="--w:{{ $summary['total_products'] > 0 ? ($summary['featured'] / $summary['total_products']) * 100 : 0 }}%"></div>
            </div>
          </div>
          <div class="bar-value">{{ $summary['featured'] }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="grid-2">
    <div class="card panel">
      <div class="panel-head">
        <div>
          <h2 class="panel-title">Productos con más stock</h2>
          <p class="panel-sub">Lo que más tenemos físicamente.</p>
        </div>
      </div>

      <div class="bar-list">
        @forelse($topStock as $it)
          @php $w = min(100, ((float)($it->stock ?? 0) / $maxTopStock) * 100); @endphp
          <div class="bar-row">
            <div>
              <div class="bar-name">{{ $it->name }}</div>
              <div class="bar-track">
                <div class="bar-fill" style="--w:{{ $w }}%"></div>
              </div>
            </div>
            <div class="bar-value">{{ number_format((float)($it->stock ?? 0), 0) }}</div>
          </div>
        @empty
          <div class="empty">No hay productos para mostrar.</div>
        @endforelse
      </div>
    </div>

    <div class="card panel">
      <div class="panel-head">
        <div>
          <h2 class="panel-title">Productos con menos stock</h2>
          <p class="panel-sub">Prioridad para revisión o compra.</p>
        </div>
      </div>

      <table class="mini-table">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Stock</th>
            <th>Mín.</th>
          </tr>
        </thead>
        <tbody>
          @forelse($lowStock as $it)
            <tr>
              <td><div class="product-name">{{ $it->name }}</div></td>
              <td>{{ number_format((float)($it->stock ?? 0), 0) }}</td>
              <td>{{ $it->stock_min !== null ? number_format((float)$it->stock_min, 0) : '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="muted">No hay productos con stock positivo.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="grid-2">
    <div class="card panel">
      <div class="panel-head">
        <div>
          <h2 class="panel-title">Stock crítico</h2>
          <p class="panel-sub">Productos donde el stock actual está en mínimo o por debajo.</p>
        </div>
        <span class="badge badge-danger">{{ $summary['critical'] }}</span>
      </div>

      <table class="mini-table">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Stock</th>
            <th>Mín.</th>
          </tr>
        </thead>
        <tbody>
          @forelse($criticalItems as $it)
            <tr>
              <td><div class="product-name">{{ $it->name }}</div></td>
              <td>{{ number_format((float)($it->stock ?? 0), 0) }}</td>
              <td>{{ $it->stock_min !== null ? number_format((float)$it->stock_min, 0) : '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="muted">No hay productos en stock crítico.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card panel">
      <div class="panel-head">
        <div>
          <h2 class="panel-title">Valor por categoría</h2>
          <p class="panel-sub">Dónde está concentrado el inventario.</p>
        </div>
      </div>

      <div class="bar-list">
        @forelse($categoryStats as $cat)
          @php $w = min(100, ((float)$cat['stock'] / $maxCategoryStock) * 100); @endphp
          <div class="bar-row">
            <div>
              <div class="bar-name">{{ $cat['category'] }}</div>
              <div class="bar-track">
                <div class="bar-fill" style="--w:{{ $w }}%"></div>
              </div>
              <div class="panel-sub">${{ number_format($cat['value'], 2) }}</div>
            </div>
            <div class="bar-value">{{ number_format($cat['stock'], 0) }}</div>
          </div>
        @empty
          <div class="empty">No hay categorías para mostrar.</div>
        @endforelse
      </div>
    </div>
  </div>

  <div class="grid-2">
    <div class="card panel">
      <div class="panel-head">
        <div>
          <h2 class="panel-title">Productos más caros</h2>
          <p class="panel-sub">Precio vigente considerando oferta si existe.</p>
        </div>
      </div>

      <table class="mini-table">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Precio</th>
            <th>Stock</th>
          </tr>
        </thead>
        <tbody>
          @forelse($expensiveItems as $it)
            <tr>
              <td><div class="product-name">{{ $it->name }}</div></td>
              <td>${{ number_format($effectivePrice($it), 2) }}</td>
              <td>{{ number_format((float)($it->stock ?? 0), 0) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="muted">Sin productos.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card panel">
      <div class="panel-head">
        <div>
          <h2 class="panel-title">Productos más baratos</h2>
          <p class="panel-sub">Útil para promociones, kits o rotación.</p>
        </div>
      </div>

      <table class="mini-table">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Precio</th>
            <th>Stock</th>
          </tr>
        </thead>
        <tbody>
          @forelse($cheapItems as $it)
            <tr>
              <td><div class="product-name">{{ $it->name }}</div></td>
              <td>${{ number_format($effectivePrice($it), 2) }}</td>
              <td>{{ number_format((float)($it->stock ?? 0), 0) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="muted">Sin productos con precio.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="grid-2">
    <div class="card panel">
      <div class="panel-head">
        <div>
          <h2 class="panel-title">Más movimientos</h2>
          <p class="panel-sub">
            @if($movementSource)
              Basado en tabla: {{ $movementSource }}.
            @else
              No se detectó tabla de movimientos conectada.
            @endif
          </p>
        </div>
      </div>

      <table class="mini-table">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Mov.</th>
            <th>Salidas</th>
          </tr>
        </thead>
        <tbody>
          @forelse($topMovements as $row)
            <tr>
              <td><div class="product-name">{{ $row['item']->name }}</div></td>
              <td>{{ number_format($row['total_movements'], 0) }}</td>
              <td>{{ number_format($row['outgoing'], 0) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="muted">Sin historial de movimientos detectado.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card panel">
      <div class="panel-head">
        <div>
          <h2 class="panel-title">Se va más rápido</h2>
          <p class="panel-sub">
            @if($movementSource)
              Ordenado por salidas de inventario.
            @else
              Se mostrará cuando exista historial de movimientos conectado.
            @endif
          </p>
        </div>
      </div>

      <table class="mini-table">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Salidas</th>
            <th>Stock</th>
          </tr>
        </thead>
        <tbody>
          @forelse($fastMoving as $row)
            <tr>
              <td><div class="product-name">{{ $row['item']->name }}</div></td>
              <td>{{ $row['outgoing'] !== null ? number_format($row['outgoing'], 0) : '—' }}</td>
              <td>{{ number_format((float)($row['item']->stock ?? 0), 0) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="muted">Sin información suficiente.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection