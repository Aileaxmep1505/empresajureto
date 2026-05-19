{{-- resources/views/admin/analytics.blade.php --}}
@extends('layouts.app')
@section('title','Analíticas y Control de Inventario')

@push('styles')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700;800&display=swap');

  :root {
    --bg: #f4f7f9;
    --card: #ffffff;
    --ink: #1e293b;
    --muted: #64748b;
    --line: #e2e8f0;
    --blue: #0ea5e9;
    --blue-soft: #e0f2fe;
    --success: #10b981;
    --success-soft: #d1fae5;
    --warning: #f59e0b;
    --warning-soft: #fef3c7;
    --danger: #ef4444;
    --danger-soft: #fee2e2;
  }

  html, body { background: var(--bg); }
  body {
    font-family: 'Quicksand', system-ui, -apple-system, sans-serif;
    color: var(--ink);
    overflow-x: hidden;
  }

  .analytics-wrap {
    max-width: 1360px;
    margin: 0 auto;
    padding: 32px 20px 60px;
  }

  /* ====== Animaciones de Scroll ====== */
  .reveal {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.6s cubic-bezier(0.25, 0.8, 0.25, 1), 
                transform 0.6s cubic-bezier(0.25, 0.8, 0.25, 1);
    will-change: opacity, transform;
  }
  .reveal.active { opacity: 1; transform: translateY(0); }
  .delay-1 { transition-delay: 0.1s; }
  .delay-2 { transition-delay: 0.2s; }
  .delay-3 { transition-delay: 0.3s; }

  .page-head {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 18px; margin-bottom: 24px;
  }
  .page-title {
    margin: 0; color: #0f172a; font-size: clamp(1.8rem, 3vw, 2.5rem);
    font-weight: 800; letter-spacing: -0.04em;
  }
  .page-subtitle {
    margin: 8px 0 0; color: var(--muted); font-size: 1rem;
    max-width: 820px; line-height: 1.6; font-weight: 600;
  }

  .actions { display: flex; gap: 12px; flex-wrap: wrap; justify-content: flex-end; }
  .btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    min-height: 42px; padding: 10px 20px; border-radius: 999px;
    text-decoration: none; border: 0; cursor: pointer; font-weight: 700;
    transition: transform .14s ease, box-shadow .2s ease, background .2s ease;
  }
  .btn:active { transform: scale(.97); }
  .btn-primary { background: var(--blue); color: #ffffff; box-shadow: 0 8px 20px rgba(14, 165, 233, 0.25); }
  .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(14, 165, 233, 0.35); }
  .btn-ghost { background: #ffffff; color: #475569; border: 1px solid var(--line); box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
  .btn-ghost:hover { background: #f8fafc; transform: translateY(-2px); box-shadow: 0 8px 15px rgba(0,0,0,0.05); }

  .filters-card, .card {
    background: var(--card); border: 1px solid var(--line); border-radius: 20px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04); transition: box-shadow .3s ease;
  }
  .card:hover { box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08); }
  .filters-card { padding: 16px 20px; margin-bottom: 24px; }
  .filters-form { display: grid; grid-template-columns: 1fr 200px auto auto; gap: 14px; align-items: center; }

  .input, .select {
    width: 100%; min-height: 44px; border: 1px solid var(--line); border-radius: 10px;
    background: #f8fafc; color: var(--ink); padding: 0 14px; font-weight: 600; outline: none;
    transition: border-color .2s ease, box-shadow .2s ease, background .2s;
  }
  .input:focus, .select:focus { background: #ffffff; border-color: var(--blue); box-shadow: 0 0 0 4px var(--blue-soft); }

  .check { display: inline-flex; align-items: center; gap: 8px; color: #475569; font-weight: 700; cursor: pointer; }
  .check input { width: 18px; height: 18px; accent-color: var(--blue); }

  .kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 20px; margin-bottom: 20px; }
  .kpi { padding: 24px; display: flex; flex-direction: column; justify-content: center; }
  .kpi-label { color: var(--muted); font-size: .9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; }
  .kpi-value { color: #0f172a; font-size: 2.2rem; font-weight: 800; letter-spacing: -0.04em; line-height: 1.1; }
  .kpi-note { margin-top: 12px; color: #94a3b8; font-size: .85rem; font-weight: 600; display: flex; align-items: center; gap: 6px; }

  .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
  .panel { padding: 24px; display: flex; flex-direction: column; }
  .panel-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 20px; }
  .panel-title { margin: 0; color: #0f172a; font-size: 1.2rem; font-weight: 800; letter-spacing: -0.02em; }
  .panel-sub { margin: 6px 0 0; color: var(--muted); font-size: .85rem; line-height: 1.5; font-weight: 600; }

  /* ====== Master Table con Scroll ====== */
  .master-table-wrap {
    width: 100%;
    overflow-x: auto;
    overflow-y: auto;
    max-height: 600px;
    border-radius: 12px;
    border: 1px solid var(--line);
  }
  .master-table { width: 100%; border-collapse: collapse; min-width: 900px; }
  .master-table th {
    background: #f8fafc; color: #475569; font-weight: 800; font-size: .8rem;
    text-transform: uppercase; letter-spacing: 0.5px; padding: 16px 14px;
    text-align: left; border-bottom: 2px solid var(--line);
    position: sticky; top: 0; z-index: 2;
  }
  .master-table td { padding: 14px; border-bottom: 1px solid var(--line); color: #1e293b; font-size: .9rem; font-weight: 600; vertical-align: middle; transition: background .2s; }
  .master-table tbody tr:hover td { background: #f1f5f9; }
  .master-table tbody tr:last-child td { border-bottom: none; }

  .prod-info { display: flex; flex-direction: column; gap: 4px; }
  .prod-info strong { color: #0f172a; font-weight: 800; font-size: .95rem; }
  .prod-info span { color: var(--muted); font-size: .8rem; }

  .status-pill {
    display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px;
    border-radius: 999px; font-size: .75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;
  }
  .pill-ok { background: var(--success-soft); color: var(--success); }
  .pill-warn { background: var(--warning-soft); color: var(--warning); }
  .pill-crit { background: var(--danger-soft); color: var(--danger); }
  .pill-draft { background: #f1f5f9; color: #64748b; }

  .empty { padding: 30px; border: 2px dashed var(--line); border-radius: 16px; color: var(--muted); font-weight: 700; text-align: center; background: #f8fafc; margin-top: 10px; }

  /* ApexCharts Adjustments */
  .apexcharts-tooltip { font-family: 'Quicksand', sans-serif !important; border-radius: 12px !important; box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important; border: 1px solid var(--line) !important; }
  .apexcharts-text { font-family: 'Quicksand', sans-serif !important; font-weight: 600; }

  /* ====== RESPONSIVE ====== */
  @media(max-width: 1100px) {
    .kpi-grid { grid-template-columns: 1fr 1fr; }
  }

  @media(max-width: 768px) {
    .analytics-wrap { padding: 16px 12px 40px; }
    .kpi-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
    .grid-2 { grid-template-columns: 1fr; }
    .filters-form { grid-template-columns: 1fr; }
    .page-head { flex-direction: column; }
    .actions { width: 100%; justify-content: flex-start; }
    .kpi-value { font-size: 1.6rem; }
    .kpi { padding: 16px; }
    .panel { padding: 16px; }
    /* En móvil la tabla solo hace scroll horizontal, sin límite de altura */
    .master-table-wrap { max-height: none; overflow-y: visible; }
    .master-table th { position: static; }
  }

  @media(max-width: 480px) {
    .kpi-grid { grid-template-columns: 1fr; }
    .btn { font-size: .85rem; padding: 8px 14px; }
    .page-title { font-size: 1.5rem; }
  }
</style>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endpush

@section('content')
@php
  // ==========================================
  // PREPARACIÓN DE DATOS (Mantenemos tus cálculos)
  // ==========================================
  $maxTopStock = max(1, (float) $topStock->max(fn($it) => (float)($it->stock ?? 0)));
  $maxCategoryStock = max(1, (float) $categoryStats->max('stock'));

  $pctPublished = $summary['total_products'] > 0 ? round(($summary['published'] / $summary['total_products']) * 100) : 0;
  $pctMeli = $summary['total_products'] > 0 ? round(($summary['meli_published'] / $summary['total_products']) * 100) : 0;

  $catNames = $categoryStats->pluck('category')->values()->toJson();
  $catValues = $categoryStats->pluck('value')->values()->toJson();

  $scatterItems = collect(array_merge($topStock->all(), $lowStock->all(), $expensiveItems->all(), $cheapItems->all()))->unique('id')->values();
  $scatterData = $scatterItems->map(function($it) use ($effectivePrice) {
      return ['x' => (float) $effectivePrice($it), 'y' => (float) ($it->stock ?? 0), 'name' => $it->name];
  })->toJson();

  $optimoCount = max(0, $summary['total_products'] - $summary['critical'] - $summary['no_stock']);
  $healthData = [$optimoCount, $summary['critical'], $summary['no_stock']];

  $unitLabel = function ($item, $value = null) {
      if (!$item) return $value === 1 ? 'pieza' : 'pzas';
      $unit = strtolower(trim((string) ($item->unit_measure ?? 'pieza')));
      $labels = [
          'pieza'   => ['sing' => 'pieza', 'plur' => 'pzas'],
          'caja'    => ['sing' => 'caja', 'plur' => 'cajas'],
          'paquete' => ['sing' => 'paquete', 'plur' => 'paquetes'],
          'rollo'   => ['sing' => 'rollo', 'plur' => 'rollos'],
      ];
      $unitData = $labels[$unit] ?? ['sing' => $unit ?: 'pieza', 'plur' => ($unit ?: 'pieza') . 's'];
      if ($value === null) return $unitData['plur'];
      return ((float) $value) == 1.0 ? $unitData['sing'] : $unitData['plur'];
  };

  // ==========================================
  // CONSULTA PARA EL CONTROL MAESTRO (Sin paginación, scroll en tabla)
  // ==========================================
  $query = \App\Models\CatalogItem::with('category');
  
  if(!empty($filters['s'])) {
      $query->where(function($q) use ($filters) {
          $q->where('name', 'like', '%'.$filters['s'].'%')
            ->orWhere('sku', 'like', '%'.$filters['s'].'%');
      });
  }
  if(isset($filters['status']) && $filters['status'] !== '') {
      $query->where('status', $filters['status']);
  }
  if(!empty($filters['featured_only'])) {
      $query->where('is_featured', 1);
  }

  $allProducts = $query->orderBy('name')->get();
@endphp

<div class="analytics-wrap">

  <div class="page-head reveal">
    <div>
      <h1 class="page-title">Analíticas y Control de Inventario</h1>
      <p class="page-subtitle">
        Control maestro de tu almacén: cruce de datos, salud del stock y reportes económicos detallados.
      </p>
    </div>

    <div class="actions">
      <a href="{{ route('admin.catalog.index') }}" class="btn btn-ghost">Volver al catálogo</a>
      <a href="{{ route('admin.catalog.analytics.pdf', request()->query()) }}" class="btn btn-primary">Descargar PDF</a>
    </div>
  </div>

  <div class="filters-card reveal delay-1">
    <form method="GET" action="{{ route('admin.catalog.analytics') }}" class="filters-form">
      <input class="input" type="search" name="s" value="{{ $filters['s'] ?? '' }}" placeholder="Buscar por nombre o SKU...">
      <select class="select" name="status">
        <option value="">Todos los estados</option>
        <option value="1" @selected((string)($filters['status'] ?? '') === '1')>Publicados</option>
        <option value="0" @selected((string)($filters['status'] ?? '') === '0')>Borradores</option>
        <option value="2" @selected((string)($filters['status'] ?? '') === '2')>Ocultos</option>
      </select>
      <label class="check">
        <input type="checkbox" name="featured_only" value="1" @checked($filters['featured_only'] ?? false)>
        Solo destacados
      </label>
      <button class="btn btn-primary" type="submit">Aplicar filtros</button>
    </form>
  </div>

  {{-- KPIs --}}
  <div class="kpi-grid">
    <div class="card kpi reveal delay-1">
      <div class="kpi-label">Valor en Bodega</div>
      <div class="kpi-value">${{ number_format($summary['total_money'], 2) }}</div>
      <div class="kpi-note"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0ea5e9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg> Capital total invertido</div>
    </div>
    <div class="card kpi reveal delay-2">
      <div class="kpi-label">Volumen de Artículos</div>
      <div class="kpi-value">{{ number_format($summary['total_stock']) }}</div>
      <div class="kpi-note"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg> Repartidos en {{ number_format($summary['total_products']) }} SKUs</div>
    </div>
    <div class="card kpi reveal delay-3">
      <div class="kpi-label">Faltantes (Sin Stock)</div>
      <div class="kpi-value" style="color: var(--danger)">{{ number_format($summary['no_stock']) }}</div>
      <div class="kpi-note"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg> Requieren resurtido urgente</div>
    </div>
    <div class="card kpi reveal delay-3">
      <div class="kpi-label">Alertas de Mínimo</div>
      <div class="kpi-value" style="color: var(--warning)">{{ number_format($summary['critical']) }}</div>
      <div class="kpi-note"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg> Próximos a agotarse</div>
    </div>
  </div>

  {{-- GRÁFICOS GRANDES --}}
  <div class="grid-2">
    <!-- Dona: Salud del Inventario -->
    <div class="card panel reveal delay-1">
      <div class="panel-head">
        <div>
          <h2 class="panel-title">Salud del Inventario</h2>
          <p class="panel-sub">Distribución basada en tus niveles de stock mínimo.</p>
        </div>
      </div>
      <div id="chart-health" style="display:flex; justify-content:center; min-height:300px; align-items:center;"></div>
    </div>

    <!-- Barras: Valor por Categoría -->
    <div class="card panel reveal delay-2">
      <div class="panel-head">
        <div>
          <h2 class="panel-title">Inversión por Categoría</h2>
          <p class="panel-sub">Dónde está concentrado el valor económico del almacén.</p>
        </div>
      </div>
      <div id="chart-categories" style="min-height: 300px;"></div>
    </div>
  </div>

  <div class="grid-2">
    <!-- Dispersión: Stock vs Precio -->
    <div class="card panel reveal delay-1" style="grid-column: 1 / -1;">
      <div class="panel-head">
        <div>
          <h2 class="panel-title">Matriz de Precios vs Existencias</h2>
          <p class="panel-sub">Pasa el cursor sobre los puntos para identificar qué productos caros tienen mucho stock (Riesgo) o qué baratos tienen poco.</p>
        </div>
      </div>
      <div id="chart-scatter" style="min-height: 320px;"></div>
    </div>
  </div>

  {{-- VELOCÍMETROS DE PUBLICACIÓN --}}
  <div class="grid-2">
    <div class="card panel reveal delay-1">
      <div class="panel-head">
        <div>
          <h2 class="panel-title">Cobertura Web</h2>
          <p class="panel-sub">Porcentaje de productos visibles al cliente.</p>
        </div>
      </div>
      <div id="chart-web-gauge" style="display:flex; justify-content:center;"></div>
    </div>

    <div class="card panel reveal delay-2">
      <div class="panel-head">
        <div>
          <h2 class="panel-title">Sincronización Mercado Libre</h2>
          <p class="panel-sub">Productos con ID de publicación activo.</p>
        </div>
      </div>
      <div id="chart-meli-gauge" style="display:flex; justify-content:center;"></div>
    </div>
  </div>

  {{-- CONTROL MAESTRO (TABLA COMPLETA CON SCROLL) --}}
  <div class="card panel reveal delay-1" style="margin-top: 20px;">
    <div class="panel-head" style="margin-bottom: 24px;">
      <div>
        <h2 class="panel-title" style="font-size: 1.5rem;">Control Maestro de Inventario</h2>
        <p class="panel-sub">Tabla detallada de todos los productos registrados. Útil para auditoría y revisión de costos totales.</p>
      </div>
    </div>

    <div class="master-table-wrap">
      <table class="master-table">
        <thead>
          <tr>
            <th>Producto & SKU</th>
            <th>Categoría</th>
            <th>Precio Venta</th>
            <th>Stock Actual</th>
            <th>Mínimo</th>
            <th>Valor en Bodega</th>
            <th>Salud</th>
            <th>Estado Web</th>
          </tr>
        </thead>
        <tbody>
          @forelse($allProducts as $p)
            @php
              $stock = (float)($p->stock ?? 0);
              $min = $p->stock_min !== null ? (float)$p->stock_min : 0;
              $price = (float)$effectivePrice($p);
              $totalValue = $stock * $price;
              
              // Lógica de salud
              if ($stock <= 0) {
                  $healthClass = 'pill-crit'; $healthText = 'Agotado';
              } elseif ($min > 0 && $stock <= $min) {
                  $healthClass = 'pill-warn'; $healthText = 'Crítico';
              } else {
                  $healthClass = 'pill-ok'; $healthText = 'Óptimo';
              }

              // Estado Web
              $webStatus = 'Borrador';
              if ($p->status == 1) $webStatus = 'Público';
              if ($p->status == 2) $webStatus = 'Oculto';
            @endphp
            <tr>
              <td>
                <div class="prod-info">
                  <strong>{{ \Illuminate\Support\Str::limit($p->name, 45) }}</strong>
                  <span>SKU: {{ $p->sku ?: 'N/A' }}</span>
                </div>
              </td>
              <td>{{ $p->category->name ?? 'Sin categoría' }}</td>
              <td>${{ number_format($price, 2) }}</td>
              <td style="font-weight: 800; font-size:1rem;">{{ number_format($stock, 0) }}</td>
              <td style="color: var(--muted)">{{ number_format($min, 0) }}</td>
              <td style="color: var(--blue); font-weight:800;">${{ number_format($totalValue, 2) }}</td>
              <td><span class="status-pill {{ $healthClass }}">{{ $healthText }}</span></td>
              <td>
                <span class="status-pill {{ $p->status == 1 ? 'pill-ok' : 'pill-draft' }}">{{ $webStatus }}</span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8"><div class="empty">No se encontraron productos con estos filtros.</div></td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

  </div>

</div>

{{-- Scripts de ApexCharts y Scroll --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  
  // 1. ANIMACIÓN DE SCROLL
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('active');
      }
    });
  }, { threshold: 0.1, rootMargin: "0px 0px -50px 0px" });

  document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

  // ==========================================
  // 2. CONFIGURACIÓN DE APEXCHARTS
  // ==========================================

  // --- Dona: Salud del Inventario ---
  const healthData = {!! json_encode($healthData) !!};
  const healthOptions = {
    series: healthData, // [Optimo, Crítico, Agotado]
    labels: ['Stock Óptimo', 'Stock Crítico', 'Agotados'],
    chart: { type: 'donut', height: 320, fontFamily: 'Quicksand, sans-serif' },
    colors: ['#10b981', '#f59e0b', '#ef4444'],
    plotOptions: {
      pie: {
        donut: { size: '70%', labels: { show: true, name: { fontWeight: 800 }, value: { fontWeight: 800, fontSize: '24px' }, total: { show: true, showAlways: true, label: 'Total SKUs', fontWeight: 800 } } }
      }
    },
    stroke: { width: 0 },
    dataLabels: { enabled: false },
    legend: { position: 'bottom', fontWeight: 700 }
  };
  new ApexCharts(document.querySelector("#chart-health"), healthOptions).render();

  // --- Gráfica de Barras: Categorías ---
  const catNames = {!! $catNames !!};
  const catValues = {!! $catValues !!};

  if(catNames.length > 0) {
    const barOptions = {
      series: [{ name: 'Inversión MXN', data: catValues }],
      chart: { type: 'bar', height: 320, fontFamily: 'Quicksand, sans-serif', toolbar: { show: false } },
      plotOptions: { bar: { borderRadius: 6, horizontal: true, distributed: true } },
      colors: ['#0ea5e9', '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#f43f5e'],
      dataLabels: { enabled: false },
      xaxis: { categories: catNames, labels: { formatter: (val) => '$' + val.toLocaleString() } },
      yaxis: { labels: { style: { fontWeight: 700, colors: '#475569' } } },
      tooltip: { y: { formatter: function (val) { return "$" + val.toLocaleString() + " MXN" } } }
    };
    new ApexCharts(document.querySelector("#chart-categories"), barOptions).render();
  }

  // --- Gráfica de Dispersión (Scatter) ---
  const scatterRawData = {!! $scatterData !!};
  if(scatterRawData.length > 0) {
    const scatterSeriesData = scatterRawData.map(item => { return { x: item.x, y: item.y, name: item.name }; });
    const scatterOptions = {
      series: [{ name: "Productos", data: scatterSeriesData }],
      chart: { height: 350, type: 'scatter', fontFamily: 'Quicksand, sans-serif', toolbar: { show: true }, zoom: { type: 'xy' } },
      colors: ['#0ea5e9'],
      markers: { size: 8, strokeWidth: 2, strokeColors: '#ffffff', hover: { size: 10 } },
      xaxis: { title: { text: 'Precio Venta (MXN)', style: { fontWeight: 800, color: '#64748b' } }, labels: { formatter: (val) => '$' + val.toLocaleString() } },
      yaxis: { title: { text: 'Unidades en Stock', style: { fontWeight: 800, color: '#64748b' } } },
      tooltip: {
        custom: function({series, seriesIndex, dataPointIndex, w}) {
          const data = w.config.series[seriesIndex].data[dataPointIndex];
          return '<div style="padding:12px;">' +
                 '<div style="font-weight:800; color:#0f172a; margin-bottom:6px; max-width:220px; white-space:normal;">' + data.name + '</div>' +
                 '<div style="font-size:13px; color:#475569;">Precio: <b>$' + data.x.toLocaleString() + '</b></div>' +
                 '<div style="font-size:13px; color:#475569;">Stock: <b>' + data.y + ' unids.</b></div>' +
                 '<div style="font-size:13px; color:#0ea5e9; margin-top:4px;">Valor Total: <b>$' + (data.x * data.y).toLocaleString() + '</b></div>' +
                 '</div>';
        }
      }
    };
    new ApexCharts(document.querySelector("#chart-scatter"), scatterOptions).render();
  }

  // --- Velocímetro: Web ---
  const pctWeb = {{ $pctPublished }};
  const gaugeWebOptions = {
    series: [pctWeb],
    chart: { type: 'radialBar', height: 280, fontFamily: 'Quicksand, sans-serif' },
    plotOptions: {
      radialBar: {
        startAngle: -100, endAngle: 100, hollow: { size: '65%' }, track: { background: '#e2e8f0', strokeWidth: '100%', margin: 5 },
        dataLabels: { name: { show: true, fontSize: '14px', fontWeight: 700, color: '#64748b', offsetY: 60 }, value: { offsetY: 10, fontSize: '40px', fontWeight: 800, color: '#0f172a', formatter: function (val) { return val + "%"; } } }
      }
    },
    fill: { type: 'gradient', gradient: { shade: 'dark', type: 'horizontal', gradientToColors: ['#10b981'], stops: [0, 100] } },
    stroke: { lineCap: 'round' }, colors: ['#0ea5e9'], labels: ['Cobertura Web'],
  };
  new ApexCharts(document.querySelector("#chart-web-gauge"), gaugeWebOptions).render();

  // --- Velocímetro: Mercado Libre ---
  const pctMeli = {{ $pctMeli }};
  const gaugeMeliOptions = {
    series: [pctMeli],
    chart: { type: 'radialBar', height: 280, fontFamily: 'Quicksand, sans-serif' },
    plotOptions: {
      radialBar: {
        startAngle: -100, endAngle: 100, hollow: { size: '65%' }, track: { background: '#e2e8f0', strokeWidth: '100%', margin: 5 },
        dataLabels: { name: { show: true, fontSize: '14px', fontWeight: 700, color: '#64748b', offsetY: 60 }, value: { offsetY: 10, fontSize: '40px', fontWeight: 800, color: '#0f172a', formatter: function (val) { return val + "%"; } } }
      }
    },
    fill: { type: 'gradient', gradient: { shade: 'dark', type: 'horizontal', gradientToColors: ['#f59e0b'], stops: [0, 100] } },
    stroke: { lineCap: 'round' }, colors: ['#fde047'], labels: ['Catálogo ML'],
  };
  new ApexCharts(document.querySelector("#chart-meli-gauge"), gaugeMeliOptions).render();

});
</script>
@endsection