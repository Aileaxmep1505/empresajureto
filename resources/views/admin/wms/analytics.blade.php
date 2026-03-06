@extends('layouts.app')

@section('title', 'WMS · Analytics & KPIs')

@section('content')
@php
  $period = (int)($period ?? 30);
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

  {{-- KPI Cards --}}
  <div class="kpi-grid">
    @foreach(($kpis ?? []) as $kpi)
      <div class="kpi-card">
        <div class="kpi-ico kpi-{{ $kpi['color'] ?? 'blue' }}">
          @switch($kpi['icon'] ?? '')
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

        <div class="kpi-value">{{ $kpi['value'] ?? '0' }}</div>
        <div class="kpi-title">{{ $kpi['title'] ?? '—' }}</div>
        <div class="kpi-sub">{{ $kpi['subtitle'] ?? '—' }}</div>
      </div>
    @endforeach
  </div>

  {{-- Charts row 1 --}}
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

  {{-- Charts row 2 --}}
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

  {{-- Low stock --}}
  @if(($lowStockCount ?? 0) > 0)
    <div class="card card-alert">
      <div class="card-h">
        <div class="card-tt">Productos con Stock Bajo ({{ $lowStockCount }})</div>
      </div>

      <div class="table-wrap">
        <table class="tbl">
          <thead>
            <tr>
              <th>Producto</th>
              <th>SKU</th>
              <th class="t-right">Stock actual</th>
              <th class="t-right">Mínimo</th>
              <th class="t-right">Déficit</th>
            </tr>
          </thead>
          <tbody>
            @foreach(($lowStockProducts ?? []) as $p)
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
    --bg:#f5f7fb;
    --card:#ffffff;
    --ink:#0f172a;
    --muted:#64748b;
    --line:#e5e7eb;
    --line2:#eef2f7;
    --shadow:0 14px 36px rgba(2,6,23,.06);
    --r:18px;

    --blue:#3b82f6;
    --green:#10b981;
    --purple:#8b5cf6;
    --amber:#f59e0b;
    --rose:#f43f5e;
    --cyan:#06b6d4;
  }

  .an-wrap{max-width:1280px;margin:0 auto;padding:18px 14px 28px}
  .an-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:18px}
  .an-title{font-size:1.95rem;font-weight:950;color:var(--ink);letter-spacing:-.03em}
  .an-sub{margin-top:4px;color:var(--muted);font-size:.95rem}
  .an-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}

  .an-btn{
    border:0;border-radius:999px;padding:10px 14px;font-weight:900;
    display:inline-flex;align-items:center;gap:8px;cursor:pointer;text-decoration:none;
    transition:transform .14s ease, box-shadow .14s ease;
  }
  .an-btn:hover{transform:translateY(-1px)}
  .an-btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink);box-shadow:0 10px 24px rgba(2,6,23,.04)}

  .an-inp{
    min-width:180px;min-height:44px;border:1px solid var(--line);border-radius:12px;
    background:#fff;padding:10px 12px;color:var(--ink);
    box-shadow:0 10px 24px rgba(2,6,23,.04);
  }

  .kpi-grid{
    display:grid;
    grid-template-columns:repeat(6,minmax(0,1fr));
    gap:14px;
    margin-bottom:26px;
  }

  .kpi-card{
    background:var(--card);
    border:1px solid var(--line);
    border-radius:18px;
    box-shadow:var(--shadow);
    padding:14px 14px 12px;
    min-height:126px;
  }

  .kpi-ico{
    width:38px;height:38px;border-radius:12px;
    display:flex;align-items:center;justify-content:center;
    color:#fff;margin-bottom:12px;
  }
  .kpi-ico svg{width:18px;height:18px}
  .kpi-blue{background:linear-gradient(135deg,#3b82f6,#2563eb)}
  .kpi-green{background:linear-gradient(135deg,#10b981,#059669)}
  .kpi-purple{background:linear-gradient(135deg,#a855f7,#7c3aed)}
  .kpi-amber{background:linear-gradient(135deg,#f59e0b,#d97706)}
  .kpi-rose{background:linear-gradient(135deg,#f43f5e,#e11d48)}
  .kpi-cyan{background:linear-gradient(135deg,#06b6d4,#0891b2)}

  .kpi-value{font-size:2rem;font-weight:950;color:var(--ink);line-height:1}
  .kpi-title{margin-top:8px;font-size:.84rem;font-weight:800;color:#334155}
  .kpi-sub{margin-top:4px;font-size:.76rem;color:#94a3b8;line-height:1.2}

  .chart-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
    margin-bottom:26px;
  }

  .card{
    background:var(--card);
    border:1px solid var(--line);
    border-radius:18px;
    box-shadow:var(--shadow);
    overflow:hidden;
  }

  .card-h{padding:18px 18px 0}
  .card-tt{font-size:1.05rem;font-weight:900;color:var(--ink)}
  .card-b{padding:8px 12px 12px}
  .chart-box{width:100%;min-height:260px}

  .card-alert{
    border-left:4px solid #f59e0b;
  }

  .table-wrap{padding:8px 14px 16px;overflow:auto}
  .tbl{width:100%;border-collapse:collapse;font-size:.92rem}
  .tbl th,.tbl td{padding:12px 10px;border-bottom:1px solid var(--line2);vertical-align:middle}
  .tbl th{text-align:left;font-weight:900;color:#64748b;background:#fff}
  .tbl tbody tr:hover{background:#fafbff}
  .t-right{text-align:right}
  .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}
  .danger{color:#e11d48}
  .badge-deficit{
    display:inline-flex;align-items:center;justify-content:center;
    padding:5px 10px;border-radius:999px;
    background:#ffe4e6;color:#be123c;border:1px solid #fecdd3;font-weight:900;font-size:.8rem;
  }

  @media (max-width: 1200px){
    .kpi-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
  }
  @media (max-width: 900px){
    .chart-grid{grid-template-columns:1fr}
  }
  @media (max-width: 720px){
    .kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .an-title{font-size:1.45rem}
  }
  @media (max-width: 520px){
    .kpi-grid{grid-template-columns:1fr}
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

  const slateGrid = '#eef2f7';
  const slateText = '#64748b';
  const colors = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'];

  // Trend
  if (document.getElementById('chartTrend')) {
    new ApexCharts(document.querySelector('#chartTrend'), {
      chart: {
        type: 'area',
        height: 260,
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
          opacityFrom: 0.18,
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

  // Category donut
  if (document.getElementById('chartCategory')) {
    new ApexCharts(document.querySelector('#chartCategory'), {
      chart: {
        type: 'donut',
        height: 260,
        toolbar: { show: false },
        fontFamily: 'inherit'
      },
      series: categoryData.map(x => Number(x.value || 0)),
      labels: categoryData.map(x => x.name || '—'),
      colors: colors,
      legend: {
        position: 'right',
        fontSize: '13px',
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
          donut: {
            size: '58%'
          }
        }
      },
      tooltip: {
        y: {
          formatter: function (val) { return val.toLocaleString(); }
        }
      }
    }).render();
  }

  // Top products horizontal bar
  if (document.getElementById('chartTopProducts')) {
    new ApexCharts(document.querySelector('#chartTopProducts'), {
      chart: {
        type: 'bar',
        height: 260,
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
          formatter: function (val) { return val.toLocaleString(); }
        }
      }
    }).render();
  }

  // Movements by type
  if (document.getElementById('chartMovTypes')) {
    new ApexCharts(document.querySelector('#chartMovTypes'), {
      chart: {
        type: 'bar',
        height: 260,
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
          columnWidth: '45%',
          distributed: true
        }
      },
      colors: ['#3b82f6','#10b981','#f59e0b','#ef4444'],
      dataLabels: { enabled: false },
      grid: { borderColor: slateGrid, strokeDashArray: 4 },
      legend: { show: false }
    }).render();
  }
})();
</script>
@endpush