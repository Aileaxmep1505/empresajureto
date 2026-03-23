@extends('layouts.app')

@section('title', 'WMS · Analytics & KPIs')

@section('content')
@php
  $period = (int)($period ?? request('period', 30));

  $productsCollection  = collect($products ?? []);
  $movementsCollection = collect($movements ?? []);
  $ordersCollection    = collect($orders ?? []);
  $locationsCollection = collect($locations ?? []);
  $returnsCollection   = collect($returns ?? []);

  $totalStock = (int)($totalStock ?? $productsCollection->sum(fn($p) => (int) data_get($p, 'current_stock', data_get($p, 'stock', 0))));
  $totalEntries = (int)($totalEntries ?? $movementsCollection->where('type', 'entry')->sum(fn($m) => (int) data_get($m, 'quantity', data_get($m, 'qty', 0))));
  $totalExits = (int)($totalExits ?? $movementsCollection->where('type', 'exit')->sum(fn($m) => (int) data_get($m, 'quantity', data_get($m, 'qty', 0))));

  $pendingOrdersCount   = (int)($pendingOrdersCount ?? $ordersCollection->where('status', 'pending')->count());
  $completedOrdersCount = (int)($completedOrdersCount ?? $ordersCollection->where('status', 'completed')->count());

  $totalLocations = (int)($totalLocations ?? $locationsCollection->count());
  $usedLocations  = (int)($usedLocations ?? $locationsCollection->filter(function ($l) {
      return data_get($l, 'status') === 'full' || (int) data_get($l, 'current_occupancy', 0) > 0;
  })->count());
  $occupancyRate = (int)($occupancyRate ?? ($totalLocations > 0 ? round(($usedLocations / $totalLocations) * 100) : 0));

  if (empty($categoryChartData) && $productsCollection->count()) {
      $categoryChartData = $productsCollection
          ->groupBy(fn($p) => data_get($p, 'category', 'other') ?: 'other')
          ->map(fn($items, $name) => [
              'name'  => (string) $name,
              'value' => (int) collect($items)->sum(fn($p) => (int) data_get($p, 'current_stock', data_get($p, 'stock', 0))),
          ])
          ->values()
          ->all();
  }
  $categoryChartData = $categoryChartData ?? [];

  if (empty($trendData)) {
      $trendData = collect(range(6, 0))->map(function ($daysAgo) use ($movementsCollection) {
          $date = now()->subDays($daysAgo)->toDateString();

          $dayMovements = $movementsCollection->filter(function ($m) use ($date) {
              $created = data_get($m, 'created_date', data_get($m, 'created_at'));
              return $created ? \Carbon\Carbon::parse($created)->toDateString() === $date : false;
          });

          return [
              'day'      => \Carbon\Carbon::parse($date)->locale('es')->translatedFormat('D'),
              'entradas' => (int) $dayMovements->where('type', 'entry')->sum(fn($m) => (int) data_get($m, 'quantity', data_get($m, 'qty', 0))),
              'salidas'  => (int) $dayMovements->where('type', 'exit')->sum(fn($m) => (int) data_get($m, 'quantity', data_get($m, 'qty', 0))),
          ];
      })->values()->all();
  }

  if (empty($topProducts) && $productsCollection->count()) {
      $topProducts = $productsCollection
          ->sortByDesc(fn($p) => (int) data_get($p, 'current_stock', data_get($p, 'stock', 0)))
          ->take(8)
          ->map(function ($p) {
              $name = (string) data_get($p, 'name', '—');
              return [
                  'name'  => mb_strlen($name) > 18 ? mb_substr($name, 0, 18).'…' : $name,
                  'stock' => (int) data_get($p, 'current_stock', data_get($p, 'stock', 0)),
              ];
          })
          ->values()
          ->all();
  }
  $topProducts = $topProducts ?? [];

  if (empty($movTypeData)) {
      $movTypeData = collect([
          ['key' => 'entry',      'name' => 'Entradas'],
          ['key' => 'exit',       'name' => 'Salidas'],
          ['key' => 'transfer',   'name' => 'Transferencias'],
          ['key' => 'adjustment', 'name' => 'Ajustes'],
      ])->map(function ($row) use ($movementsCollection) {
          return [
              'name'     => $row['name'],
              'cantidad' => (int) $movementsCollection->where('type', $row['key'])->count(),
          ];
      })->all();
  }

  if (empty($orderStatusData)) {
      $orderStatusData = collect([
          ['name' => 'Pendiente',  'value' => (int) $ordersCollection->where('status', 'pending')->count()],
          ['name' => 'En Proceso', 'value' => (int) $ordersCollection->where('status', 'in_progress')->count()],
          ['name' => 'Completada', 'value' => (int) $ordersCollection->where('status', 'completed')->count()],
          ['name' => 'Cancelada',  'value' => (int) $ordersCollection->where('status', 'cancelled')->count()],
      ])->filter(fn($x) => (int) $x['value'] > 0)->values()->all();
  }

  if (empty($lowStockProducts) && $productsCollection->count()) {
      $lowStockProducts = $productsCollection
          ->filter(function ($p) {
              $min = (int) data_get($p, 'min_stock', 0);
              $cur = (int) data_get($p, 'current_stock', data_get($p, 'stock', 0));
              return $min > 0 && $cur <= $min;
          })
          ->map(function ($p) {
              $stock = (int) data_get($p, 'current_stock', data_get($p, 'stock', 0));
              $min   = (int) data_get($p, 'min_stock', 0);
              return [
                  'id'        => data_get($p, 'id'),
                  'name'      => data_get($p, 'name', '—'),
                  'sku'       => data_get($p, 'sku', '—'),
                  'stock'     => $stock,
                  'min_stock' => $min,
                  'deficit'   => max(0, $min - $stock),
              ];
          })
          ->values()
          ->all();
  }
  $lowStockProducts = $lowStockProducts ?? [];
  $lowStockCount = (int)($lowStockCount ?? count($lowStockProducts));

  $returnsSummary = [
      ['label' => 'Total Devoluciones', 'value' => (int)($returnsSummary['total'] ?? $returnsCollection->count()), 'tone' => 'slate'],
      ['label' => 'Pendientes',         'value' => (int)($returnsSummary['pending'] ?? $returnsCollection->where('status', 'pending')->count()), 'tone' => 'amber'],
      ['label' => 'Procesadas',         'value' => (int)($returnsSummary['processed'] ?? $returnsCollection->where('status', 'processed')->count()), 'tone' => 'emerald'],
      ['label' => 'De Clientes',        'value' => (int)($returnsSummary['customer_return'] ?? $returnsCollection->where('type', 'customer_return')->count()), 'tone' => 'blue'],
      ['label' => 'A Proveedores',      'value' => (int)($returnsSummary['supplier_return'] ?? $returnsCollection->where('type', 'supplier_return')->count()), 'tone' => 'purple'],
      ['label' => 'Por Defecto',        'value' => (int)($returnsSummary['defective'] ?? $returnsCollection->where('reason', 'defective')->count()), 'tone' => 'rose'],
  ];

  $kpis = [
      [
          'title' => 'Total Stock',
          'value' => number_format($totalStock),
          'subtitle' => 'unidades en almacén',
          'icon' => 'package',
          'color' => 'blue',
      ],
      [
          'title' => 'Entradas',
          'value' => number_format($totalEntries),
          'subtitle' => 'últimos '.$period.' días',
          'icon' => 'trend-down',
          'color' => 'emerald',
      ],
      [
          'title' => 'Salidas',
          'value' => number_format($totalExits),
          'subtitle' => 'últimos '.$period.' días',
          'icon' => 'trend-up',
          'color' => 'purple',
      ],
      [
          'title' => 'Stock Bajo',
          'value' => number_format($lowStockCount),
          'subtitle' => 'productos con alerta',
          'icon' => 'alert',
          'color' => 'amber',
      ],
      [
          'title' => 'Órdenes Pendientes',
          'value' => number_format($pendingOrdersCount),
          'subtitle' => $completedOrdersCount.' completadas',
          'icon' => 'clipboard',
          'color' => 'rose',
      ],
      [
          'title' => 'Ocupación Almacén',
          'value' => $occupancyRate.'%',
          'subtitle' => $usedLocations.' / '.$totalLocations.' ubicaciones',
          'icon' => 'swap',
          'color' => 'cyan',
      ],
  ];
@endphp

<div class="an-wrap">
  <div class="an-head">
    <div>
      <div class="an-title">Analytics & KPIs</div>
      <div class="an-sub">Métricas avanzadas del almacén</div>
    </div>

    <div class="an-actions">
      <a href="{{ route('admin.wms.home') }}" class="an-btn an-btn-ghost">← WMS</a>

      <form method="GET" action="{{ route('admin.wms.analytics') }}">
        <select name="period" class="an-inp" onchange="this.form.submit()">
          <option value="7"  @selected($period === 7)>Últimos 7 días</option>
          <option value="30" @selected($period === 30)>Últimos 30 días</option>
          <option value="90" @selected($period === 90)>Últimos 90 días</option>
        </select>
      </form>
    </div>
  </div>

  <div class="kpi-grid">
    @foreach($kpis as $kpi)
      <div class="kpi-card">
        <div class="kpi-ico kpi-{{ $kpi['color'] }}">
          @switch($kpi['icon'])
            @case('package')
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 7l9-4 9 4-9 4-9-4z"/>
                <path d="M3 7v10l9 4 9-4V7"/>
                <path d="M12 11v10"/>
              </svg>
            @break

            @case('trend-down')
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 7h4l3 6 4-8 7 12"/>
              </svg>
            @break

            @case('trend-up')
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 17l6-6 4 4 7-9"/>
              </svg>
            @break

            @case('alert')
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 9v4"/>
                <path d="M12 17h.01"/>
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
              </svg>
            @break

            @case('clipboard')
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="6" y="4" width="12" height="16" rx="2"/>
                <path d="M9 4.5h6"/>
                <path d="M9 10h6"/>
                <path d="M9 14h6"/>
              </svg>
            @break

            @case('swap')
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M7 7h11l-3-3"/>
                <path d="M17 17H6l3 3"/>
              </svg>
            @break
          @endswitch
        </div>

        <div class="kpi-value">{{ $kpi['value'] }}</div>
        <div class="kpi-title">{{ $kpi['title'] }}</div>
        <div class="kpi-sub">{{ $kpi['subtitle'] }}</div>
      </div>
    @endforeach
  </div>

  <div class="chart-grid">
    <div class="card">
      <div class="card-h">
        <div class="card-tt">Tendencia de Movimientos (7 días)</div>
      </div>
      <div class="card-b">
        <div id="chartTrend" class="chart-box"></div>
      </div>
    </div>

    <div class="card">
      <div class="card-h">
        <div class="card-tt">Stock por Categoría</div>
      </div>
      <div class="card-b">
        <div id="chartCategory" class="chart-box"></div>
      </div>
    </div>
  </div>

  <div class="chart-grid">
    <div class="card">
      <div class="card-h">
        <div class="card-tt">Top Productos por Stock</div>
      </div>
      <div class="card-b">
        <div id="chartTopProducts" class="chart-box"></div>
      </div>
    </div>

    <div class="card">
      <div class="card-h">
        <div class="card-tt">Movimientos por Tipo</div>
      </div>
      <div class="card-b">
        <div id="chartMovTypes" class="chart-box"></div>
      </div>
    </div>
  </div>

  <div class="chart-grid chart-grid-3">
    <div class="card">
      <div class="card-h">
        <div class="card-tt">Estado de Órdenes</div>
      </div>
      <div class="card-b">
        <div id="chartOrders" class="chart-box chart-box-sm"></div>
      </div>
    </div>

    <div class="card card-span-2">
      <div class="card-h">
        <div class="card-tt">Resumen de Devoluciones</div>
      </div>
      <div class="card-b">
        <div class="returns-grid">
          @foreach($returnsSummary as $item)
            <div class="return-box return-{{ $item['tone'] }}">
              <div class="return-value">{{ $item['value'] }}</div>
              <div class="return-label">{{ $item['label'] }}</div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  @if($lowStockCount > 0)
    <div class="card card-alert">
      <div class="card-h">
        <div class="card-tt card-tt-inline">
          <svg class="alert-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 9v4"/>
            <path d="M12 17h.01"/>
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
          </svg>
          Productos con Stock Bajo ({{ $lowStockCount }})
        </div>
      </div>

      <div class="table-wrap">
        <table class="tbl">
          <thead>
            <tr>
              <th>Producto</th>
              <th>SKU</th>
              <th class="t-right">Stock Actual</th>
              <th class="t-right">Mínimo</th>
              <th class="t-right">Déficit</th>
            </tr>
          </thead>
          <tbody>
            @foreach($lowStockProducts as $p)
              <tr>
                <td>{{ $p['name'] ?? '—' }}</td>
                <td class="mono">{{ $p['sku'] ?? '—' }}</td>
                <td class="t-right danger"><b>{{ (int)($p['stock'] ?? 0) }}</b></td>
                <td class="t-right">{{ (int)($p['min_stock'] ?? 0) }}</td>
                <td class="t-right">
                  <span class="badge-deficit">-{{ (int)($p['deficit'] ?? 0) }}</span>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif
</div>
@endsection

@push('styles')
<style>
  :root{
    --bg:#f8fafc;
    --card:#ffffff;
    --ink:#0f172a;
    --muted:#64748b;
    --line:#e5e7eb;
    --line2:#eef2f7;
    --shadow:0 8px 24px rgba(15,23,42,.06);
    --r:18px;

    --blue:#3b82f6;
    --emerald:#10b981;
    --purple:#8b5cf6;
    --amber:#f59e0b;
    --rose:#f43f5e;
    --cyan:#06b6d4;
    --red:#ef4444;
  }

  .an-wrap{max-width:1280px;margin:0 auto;padding:18px 14px 28px}
  .an-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:24px}
  .an-title{font-size:2rem;font-weight:900;color:var(--ink);letter-spacing:-.03em;line-height:1.05}
  .an-sub{margin-top:6px;color:var(--muted);font-size:.96rem}
  .an-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}

  .an-btn{
    border:0;border-radius:999px;padding:10px 14px;font-weight:800;
    display:inline-flex;align-items:center;gap:8px;cursor:pointer;text-decoration:none;
    transition:transform .14s ease, box-shadow .14s ease;
  }
  .an-btn:hover{transform:translateY(-1px)}
  .an-btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink);box-shadow:0 8px 20px rgba(2,6,23,.04)}

  .an-inp{
    min-width:180px;min-height:44px;border:1px solid var(--line);border-radius:12px;
    background:#fff;padding:10px 12px;color:var(--ink);
    box-shadow:0 8px 20px rgba(2,6,23,.04);
  }

  .kpi-grid{
    display:grid;
    grid-template-columns:repeat(6,minmax(0,1fr));
    gap:16px;
    margin-bottom:26px;
  }

  .kpi-card{
    background:var(--card);
    border:1px solid var(--line);
    border-radius:16px;
    box-shadow:var(--shadow);
    padding:16px;
    min-height:138px;
  }

  .kpi-ico{
    width:42px;height:42px;border-radius:14px;
    display:flex;align-items:center;justify-content:center;
    color:#fff;margin-bottom:14px;
  }
  .kpi-ico svg{width:20px;height:20px}
  .kpi-blue{background:linear-gradient(135deg,#3b82f6,#2563eb)}
  .kpi-emerald{background:linear-gradient(135deg,#10b981,#059669)}
  .kpi-purple{background:linear-gradient(135deg,#a855f7,#7c3aed)}
  .kpi-amber{background:linear-gradient(135deg,#f59e0b,#d97706)}
  .kpi-rose{background:linear-gradient(135deg,#f43f5e,#e11d48)}
  .kpi-cyan{background:linear-gradient(135deg,#06b6d4,#0891b2)}

  .kpi-value{font-size:1.9rem;font-weight:900;color:var(--ink);line-height:1}
  .kpi-title{margin-top:8px;font-size:.82rem;font-weight:800;color:#475569}
  .kpi-sub{margin-top:4px;font-size:.76rem;color:#94a3b8;line-height:1.25}

  .chart-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:24px;
    margin-bottom:24px;
  }

  .chart-grid-3{
    grid-template-columns:1fr 2fr;
  }

  .card{
    background:var(--card);
    border:1px solid var(--line);
    border-radius:16px;
    box-shadow:var(--shadow);
    overflow:hidden;
  }

  .card-span-2{grid-column:span 1}
  .card-h{padding:18px 18px 0}
  .card-tt{font-size:1rem;font-weight:800;color:var(--ink)}
  .card-tt-inline{display:flex;align-items:center;gap:8px}
  .alert-ico{width:18px;height:18px;color:#f59e0b}
  .card-b{padding:10px 14px 14px}
  .chart-box{width:100%;min-height:250px}
  .chart-box-sm{min-height:200px}

  .returns-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:14px;
  }

  .return-box{
    border-radius:14px;
    padding:18px 16px;
    background:#f8fafc;
    border:1px solid #eef2f7;
  }

  .return-value{
    font-size:1.9rem;
    font-weight:900;
    line-height:1;
  }

  .return-label{
    margin-top:8px;
    font-size:.92rem;
    color:#64748b;
  }

  .return-slate .return-value{color:#0f172a}
  .return-amber .return-value{color:#d97706}
  .return-emerald .return-value{color:#059669}
  .return-blue .return-value{color:#2563eb}
  .return-purple .return-value{color:#7c3aed}
  .return-rose .return-value{color:#e11d48}

  .card-alert{
    border-left:4px solid #f59e0b;
  }

  .table-wrap{padding:8px 14px 16px;overflow:auto}
  .tbl{width:100%;border-collapse:collapse;font-size:.92rem}
  .tbl th,.tbl td{padding:12px 10px;border-bottom:1px solid var(--line2);vertical-align:middle}
  .tbl th{text-align:left;font-weight:800;color:#64748b;background:#fff}
  .tbl tbody tr:hover{background:#fafbff}
  .t-right{text-align:right}
  .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}
  .danger{color:#e11d48}
  .badge-deficit{
    display:inline-flex;align-items:center;justify-content:center;
    padding:5px 10px;border-radius:999px;
    background:#ffe4e6;color:#be123c;border:1px solid #fecdd3;font-weight:800;font-size:.8rem;
  }

  @media (max-width: 1200px){
    .kpi-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
  }
  @media (max-width: 980px){
    .chart-grid,.chart-grid-3{grid-template-columns:1fr}
    .returns-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
  }
  @media (max-width: 720px){
    .kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .an-title{font-size:1.55rem}
  }
  @media (max-width: 520px){
    .kpi-grid,.returns-grid{grid-template-columns:1fr}
    .an-actions{width:100%}
    .an-actions form,.an-inp{width:100%}
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
(function(){
  const trendData = @json($trendData ?? []);
  const categoryData = @json($categoryChartData ?? []);
  const topProducts = @json($topProducts ?? []);
  const movTypeData = @json($movTypeData ?? []);
  const orderStatusData = @json($orderStatusData ?? []);

  const slateGrid = '#eef2f7';
  const slateText = '#64748b';
  const colors = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'];

  if (document.getElementById('chartTrend')) {
    new ApexCharts(document.querySelector('#chartTrend'), {
      chart: {
        type: 'area',
        height: 250,
        toolbar: { show: false },
        fontFamily: 'inherit'
      },
      series: [
        { name: 'Entradas', data: trendData.map(x => Number(x.entradas || 0)) },
        { name: 'Salidas',  data: trendData.map(x => Number(x.salidas || 0)) }
      ],
      xaxis: {
        categories: trendData.map(x => x.day || ''),
        labels: { style: { colors: slateText, fontSize: '12px' } },
        axisBorder: { show: false },
        axisTicks: { show: false }
      },
      yaxis: {
        labels: { style: { colors: slateText, fontSize: '12px' } }
      },
      colors: ['#10b981', '#ef4444'],
      stroke: { curve: 'smooth', width: 2.5 },
      fill: {
        type: 'gradient',
        gradient: {
          shadeIntensity: 1,
          opacityFrom: 0.20,
          opacityTo: 0.02,
          stops: [0, 90, 100]
        }
      },
      dataLabels: { enabled: false },
      grid: { borderColor: slateGrid, strokeDashArray: 4 },
      legend: {
        position: 'bottom',
        labels: { colors: slateText }
      },
      tooltip: { shared: true, intersect: false }
    }).render();
  }

  if (document.getElementById('chartCategory')) {
    new ApexCharts(document.querySelector('#chartCategory'), {
      chart: {
        type: 'donut',
        height: 250,
        toolbar: { show: false },
        fontFamily: 'inherit'
      },
      series: categoryData.map(x => Number(x.value || 0)),
      labels: categoryData.map(x => x.name || '—'),
      colors: colors,
      legend: {
        position: 'bottom',
        fontSize: '12px',
        labels: { colors: slateText }
      },
      dataLabels: {
        enabled: true,
        formatter: function (val) {
          return Math.round(val) + '%';
        }
      },
      stroke: { colors: ['#fff'] },
      plotOptions: {
        pie: {
          donut: { size: '58%' }
        }
      },
      tooltip: {
        y: {
          formatter: function (val) { return Number(val || 0).toLocaleString(); }
        }
      }
    }).render();
  }

  if (document.getElementById('chartTopProducts')) {
    new ApexCharts(document.querySelector('#chartTopProducts'), {
      chart: {
        type: 'bar',
        height: 250,
        toolbar: { show: false },
        fontFamily: 'inherit'
      },
      series: [{
        name: 'Stock',
        data: topProducts.map(x => Number(x.stock || 0))
      }],
      xaxis: {
        categories: topProducts.map(x => x.name || ''),
        labels: { style: { colors: slateText, fontSize: '12px' } }
      },
      yaxis: {
        labels: { style: { colors: slateText, fontSize: '11px' } }
      },
      plotOptions: {
        bar: {
          horizontal: true,
          borderRadius: 4,
          barHeight: '70%'
        }
      },
      colors: ['#3b82f6'],
      dataLabels: { enabled: false },
      grid: { borderColor: slateGrid, strokeDashArray: 4 },
      tooltip: {
        y: {
          formatter: function (val) { return Number(val || 0).toLocaleString(); }
        }
      }
    }).render();
  }

  if (document.getElementById('chartMovTypes')) {
    new ApexCharts(document.querySelector('#chartMovTypes'), {
      chart: {
        type: 'bar',
        height: 250,
        toolbar: { show: false },
        fontFamily: 'inherit'
      },
      series: [{
        name: 'Cantidad',
        data: movTypeData.map(x => Number(x.cantidad || 0))
      }],
      xaxis: {
        categories: movTypeData.map(x => x.name || ''),
        labels: { style: { colors: slateText, fontSize: '12px' } }
      },
      yaxis: {
        labels: { style: { colors: slateText, fontSize: '12px' } }
      },
      plotOptions: {
        bar: {
          borderRadius: 4,
          columnWidth: '48%',
          distributed: true
        }
      },
      colors: ['#3b82f6','#10b981','#f59e0b','#ef4444'],
      dataLabels: { enabled: false },
      grid: { borderColor: slateGrid, strokeDashArray: 4 },
      legend: { show: false }
    }).render();
  }

  if (document.getElementById('chartOrders')) {
    new ApexCharts(document.querySelector('#chartOrders'), {
      chart: {
        type: 'pie',
        height: 200,
        toolbar: { show: false },
        fontFamily: 'inherit'
      },
      series: orderStatusData.map(x => Number(x.value || 0)),
      labels: orderStatusData.map(x => x.name || '—'),
      colors: colors,
      legend: {
        position: 'bottom',
        fontSize: '12px',
        labels: { colors: slateText }
      },
      dataLabels: {
        enabled: true,
        formatter: function (val) {
          return Math.round(val) + '%';
        }
      },
      stroke: { colors: ['#fff'] },
      tooltip: {
        y: {
          formatter: function (val) { return Number(val || 0).toLocaleString(); }
        }
      }
    }).render();
  }
})();
</script>
@endpush