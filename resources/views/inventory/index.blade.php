@extends('layouts.app')

@section('title','Activos Internos')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
:root{
  --bg:#f7fafc;
  --bg-2:#f1f5f9;
  --card:#ffffff;
  --text:#163047;
  --muted:#6b7c93;
  --line:#e6edf5;

  --primary:#3b82f6;
  --primary-soft:#eef5ff;

  --teal:#14b8a6;
  --teal-soft:#ecfdfb;

  --blue:#4f8cff;
  --blue-soft:#eff6ff;

  --violet:#8b5cf6;
  --violet-soft:#f5f3ff;

  --rose:#f97393;
  --rose-soft:#fff1f5;

  --green:#22c55e;
  --green-soft:#edfdf3;

  --amber:#f59e0b;
  --amber-soft:#fff8eb;

  --slate:#64748b;
  --slate-soft:#f8fafc;

  --radius-xl:22px;
  --radius-lg:18px;
  --radius-md:14px;

  --shadow:0 10px 30px rgba(148,163,184,.10);
  --shadow-hover:0 18px 38px rgba(148,163,184,.16);
  --ease:cubic-bezier(.2,.8,.2,1);
}

html,body{
  width:100%;
  overflow-x:hidden;
}

body{
  background:
    radial-gradient(circle at top left, rgba(59,130,246,.06), transparent 18%),
    radial-gradient(circle at top right, rgba(20,184,166,.05), transparent 16%),
    linear-gradient(180deg,#f9fbfd 0%, #f4f8fb 100%);
  color:var(--text);
  font-family:'Plus Jakarta Sans',system-ui,-apple-system,sans-serif;
  letter-spacing:-.02em;
}

@keyframes fadeUp{
  from{ opacity:0; transform:translateY(20px); }
  to{ opacity:1; transform:none; }
}

.reveal{
  animation:fadeUp .55s var(--ease) both;
}

.page{
  width:100%;
  max-width:none;
  margin:28px 0 40px;
  padding:0 28px;
}

.head{
  display:flex;
  align-items:flex-end;
  justify-content:space-between;
  gap:16px;
  margin-bottom:22px;
}

.head-title{
  margin:0;
  font-size:32px;
  font-weight:800;
  color:var(--text);
  letter-spacing:-.04em;
}

.head-sub{
  margin:6px 0 0;
  color:var(--muted);
  font-size:14px;
  font-weight:500;
}

.btn-main{
  display:inline-flex;
  align-items:center;
  gap:10px;
  border:none;
  border-radius:14px;
  background:var(--primary);
  color:#fff;
  text-decoration:none;
  padding:12px 18px;
  font-weight:700;
  font-size:14px;
  box-shadow:0 12px 24px rgba(59,130,246,.20);
  transition:transform .18s var(--ease), box-shadow .18s var(--ease), opacity .18s, background .18s;
}
.btn-main:hover{
  color:#fff;
  background:#2563eb;
  transform:translateY(-1px);
  box-shadow:0 16px 28px rgba(59,130,246,.24);
}

.flash{
  border-radius:16px;
  padding:13px 16px;
  margin-bottom:16px;
  font-size:14px;
  font-weight:600;
  border:1px solid transparent;
}
.flash.ok{
  background:#eefbf3;
  color:#15803d;
  border-color:#ccefd8;
}
.flash.bad{
  background:#fff4f6;
  color:#be185d;
  border-color:#ffd4de;
}

/* KPI cards */
.kpis{
  display:grid;
  grid-template-columns:repeat(4,minmax(0,1fr));
  gap:18px;
  margin-bottom:22px;
}

.kpi-link{
  display:block;
  text-decoration:none;
  color:inherit;
}

.kpi-link:hover{
  text-decoration:none;
  color:inherit;
}

.kpi{
  position:relative;
  background:rgba(255,255,255,.96);
  border:1px solid var(--line);
  border-radius:var(--radius-xl);
  padding:18px 18px;
  box-shadow:var(--shadow);
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:14px;
  min-height:108px;
  transition:
    transform .22s var(--ease),
    box-shadow .22s var(--ease),
    border-color .22s var(--ease),
    background .22s var(--ease);
  overflow:hidden;
  cursor:pointer;
}

.kpi-link:hover .kpi,
.kpi-link:focus-visible .kpi{
  transform:translateY(-4px) scale(1.03);
  box-shadow:var(--shadow-hover);
}

.kpi-link:focus-visible{
  outline:none;
}

.kpi-label{
  font-size:13px;
  font-weight:700;
  color:#698097;
  margin-bottom:8px;
  transition:color .22s var(--ease);
}

.kpi-value{
  font-size:34px;
  line-height:1;
  font-weight:800;
  color:var(--text);
  transition:color .22s var(--ease);
}

.kpi-sub{
  margin-top:8px;
  font-size:12px;
  font-weight:600;
  color:#94a3b8;
  transition:color .22s var(--ease);
}

.kpi-icon{
  width:54px;
  height:54px;
  border-radius:16px;
  display:grid;
  place-items:center;
  font-size:22px;
  border:1px solid transparent;
  flex:0 0 auto;
  transition:
    transform .22s var(--ease),
    background .22s var(--ease),
    border-color .22s var(--ease),
    color .22s var(--ease),
    box-shadow .22s var(--ease);
}

.kpi-link:hover .kpi-icon,
.kpi-link:focus-visible .kpi-icon{
  transform:scale(1.10);
}

.kpi.teal .kpi-icon{ background:var(--teal-soft); color:var(--teal); border-color:#c8f3ee; }
.kpi.blue .kpi-icon{ background:var(--blue-soft); color:var(--blue); border-color:#dceafe; }
.kpi.violet .kpi-icon{ background:var(--violet-soft); color:var(--violet); border-color:#ebe4ff; }
.kpi.rose .kpi-icon{ background:var(--rose-soft); color:var(--rose); border-color:#ffdbe5; }

.kpi.teal:hover,
.kpi-link:focus-visible .kpi.teal{
  background:var(--teal-soft);
  border-color:#bfeee7;
}
.kpi.teal:hover .kpi-label,
.kpi.teal:hover .kpi-sub,
.kpi-link:focus-visible .kpi.teal .kpi-label,
.kpi-link:focus-visible .kpi.teal .kpi-sub{
  color:#44737a;
}
.kpi.teal:hover .kpi-icon,
.kpi-link:focus-visible .kpi.teal .kpi-icon{
  background:#ffffff;
  box-shadow:0 10px 22px rgba(20,184,166,.14);
}

.kpi.blue:hover,
.kpi-link:focus-visible .kpi.blue{
  background:var(--blue-soft);
  border-color:#d4e5ff;
}
.kpi.blue:hover .kpi-label,
.kpi.blue:hover .kpi-sub,
.kpi-link:focus-visible .kpi.blue .kpi-label,
.kpi-link:focus-visible .kpi.blue .kpi-sub{
  color:#557399;
}
.kpi.blue:hover .kpi-icon,
.kpi-link:focus-visible .kpi.blue .kpi-icon{
  background:#ffffff;
  box-shadow:0 10px 22px rgba(79,140,255,.14);
}

.kpi.violet:hover,
.kpi-link:focus-visible .kpi.violet{
  background:var(--violet-soft);
  border-color:#e7dcff;
}
.kpi.violet:hover .kpi-label,
.kpi.violet:hover .kpi-sub,
.kpi-link:focus-visible .kpi.violet .kpi-label,
.kpi-link:focus-visible .kpi.violet .kpi-sub{
  color:#6d6696;
}
.kpi.violet:hover .kpi-icon,
.kpi-link:focus-visible .kpi.violet .kpi-icon{
  background:#ffffff;
  box-shadow:0 10px 22px rgba(139,92,246,.14);
}

.kpi.rose:hover,
.kpi-link:focus-visible .kpi.rose{
  background:var(--rose-soft);
  border-color:#ffd5e2;
}
.kpi.rose:hover .kpi-label,
.kpi.rose:hover .kpi-sub,
.kpi-link:focus-visible .kpi.rose .kpi-label,
.kpi-link:focus-visible .kpi.rose .kpi-sub{
  color:#8e6674;
}
.kpi.rose:hover .kpi-icon,
.kpi-link:focus-visible .kpi.rose .kpi-icon{
  background:#ffffff;
  box-shadow:0 10px 22px rgba(249,115,147,.14);
}

/* top panels */
.top-panels{
  display:grid;
  grid-template-columns:1.08fr 1.08fr .84fr;
  gap:18px;
  margin-bottom:22px;
}

.panel{
  background:rgba(255,255,255,.97);
  border:1px solid var(--line);
  border-radius:var(--radius-xl);
  box-shadow:var(--shadow);
  padding:20px;
  min-height:340px;
}

.panel-title{
  margin:0 0 12px;
  font-size:15px;
  font-weight:800;
  color:var(--text);
}

.chart-wrap{
  position:relative;
  height:255px;
}

.alerts{
  display:flex;
  flex-direction:column;
  gap:12px;
  margin-top:4px;
}

.alert-item{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  padding:12px 12px;
  border-radius:14px;
  background:#fbfdff;
  border:1px solid #edf3f8;
}

.alert-left{
  display:flex;
  align-items:flex-start;
  gap:10px;
  min-width:0;
}

.alert-icon{
  width:30px;
  height:30px;
  border-radius:999px;
  display:grid;
  place-items:center;
  background:#fff5f5;
  color:#ef4444;
  font-size:13px;
  flex:0 0 auto;
  border:1px solid #ffe1e1;
}

.alert-name{
  margin:0;
  font-size:13px;
  font-weight:700;
  color:#32506a;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}

.alert-meta{
  margin:3px 0 0;
  font-size:11px;
  color:#8ea0b5;
  font-weight:600;
}

.badge-state{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding:5px 10px;
  border-radius:999px;
  font-size:11px;
  font-weight:800;
  white-space:nowrap;
}
.badge-state.low{
  background:#fff1f2;
  color:#e11d48;
}
.badge-state.ok{
  background:#ecfdf3;
  color:#15803d;
}
.badge-state.gray{
  background:#f1f5f9;
  color:#475569;
}

/* table section */
.table-panel{
  background:rgba(255,255,255,.97);
  border:1px solid var(--line);
  border-radius:var(--radius-xl);
  box-shadow:var(--shadow);
  padding:20px;
}

.table-wrap{
  overflow-x:auto;
}

.table-clean{
  width:100%;
  border-collapse:separate;
  border-spacing:0;
  min-width:920px;
}

.table-clean thead th{
  font-size:12px;
  color:#6a7f95;
  font-weight:800;
  padding:12px 14px;
  border-bottom:1px solid #e7eef5;
  background:#fbfdff;
}

.table-clean tbody td{
  padding:14px;
  font-size:13px;
  color:#3b536a;
  border-bottom:1px solid #f1f5f9;
  vertical-align:middle;
}

.table-clean tbody tr:hover{
  background:#f9fbfe;
}

.asset-line{
  display:flex;
  flex-direction:column;
  gap:4px;
}

.asset-name{
  font-weight:800;
  color:var(--text);
}

.asset-tag{
  font-size:11px;
  color:#9aa9b8;
  font-weight:700;
}

.empty-box{
  min-height:230px;
  display:flex;
  align-items:center;
  justify-content:center;
  text-align:center;
  color:#97a6b5;
  font-size:13px;
  font-weight:700;
}

@media (max-width: 1400px){
  .top-panels{
    grid-template-columns:1fr 1fr;
  }

  .top-panels .panel:last-child{
    grid-column:1 / -1;
  }
}

@media (max-width: 1200px){
  .kpis{
    grid-template-columns:repeat(2,minmax(0,1fr));
  }

  .top-panels{
    grid-template-columns:1fr;
  }

  .top-panels .panel:last-child{
    grid-column:auto;
  }
}

@media (max-width: 768px){
  .page{
    padding:0 12px;
  }

  .head{
    flex-direction:column;
    align-items:stretch;
  }

  .btn-main{
    justify-content:center;
  }

  .kpis{
    grid-template-columns:1fr;
  }

  .head-title{
    font-size:25px;
  }

  .table-clean{
    min-width:780px;
  }
}
</style>

@php
  $categoryLabels = collect($byCategory ?? [])->pluck('name')->values();
  $categoryCounts = collect($byCategory ?? [])->pluck('count')->values();

  $statusLabels = array_keys($assetStatusChart ?? []);
  $statusCounts = array_values($assetStatusChart ?? []);

  $categoryColors = [
    '#14b8a6', '#60a5fa', '#f59e0b', '#fb7185', '#8b5cf6',
    '#22c55e', '#06b6d4', '#f97316', '#a78bfa', '#38bdf8'
  ];

  $boardBase = route('assets.board');
  $newAssetUrl = $boardBase . '?new=1';
  $totalAssetsUrl = $boardBase . '?tab=activos';
  $assignedAssetsUrl = url('/internal-assets/assignments');
  $consumiblesUrl = $boardBase . '?tab=consumibles';
  $lowStockUrl = $boardBase . '?tab=consumibles&filter=low_stock';
@endphp

<div class="page">

  <div class="head reveal">
    <div>
      <h1 class="head-title">Panel de Control</h1>
      <p class="head-sub">Resumen general de activos e inventario</p>
    </div>

    <a href="{{ $newAssetUrl }}" class="btn-main">
      <i class="bi bi-plus-lg"></i>
      <span>Nuevo Activo</span>
    </a>
  </div>

  @if(session('ok'))
    <div class="flash ok reveal">{{ session('ok') }}</div>
  @endif

  @if(session('success'))
    <div class="flash ok reveal">{{ session('success') }}</div>
  @endif

  @if(session('bad'))
    <div class="flash bad reveal">{{ session('bad') }}</div>
  @endif

  @if(session('error'))
    <div class="flash bad reveal">{{ session('error') }}</div>
  @endif

  <div class="kpis">

    <a href="{{ $totalAssetsUrl }}" class="kpi-link reveal" style="animation-delay:.05s">
      <div class="kpi teal">
        <div>
          <div class="kpi-label">Total Activos Fijos</div>
          <div class="kpi-value">{{ $totalAssets ?? 0 }}</div>
        </div>
        <div class="kpi-icon">
          <i class="bi bi-box-seam"></i>
        </div>
      </div>
    </a>

    <a href="{{ $assignedAssetsUrl }}" class="kpi-link reveal" style="animation-delay:.10s">
      <div class="kpi blue">
        <div>
          <div class="kpi-label">Asignados</div>
          <div class="kpi-value">{{ $assignedAssets ?? 0 }}</div>
          <div class="kpi-sub">{{ $activeAssignmentsCount ?? 0 }} asignaciones activas</div>
        </div>
        <div class="kpi-icon">
          <i class="bi bi-people"></i>
        </div>
      </div>
    </a>

    <a href="{{ $consumiblesUrl }}" class="kpi-link reveal" style="animation-delay:.15s">
      <div class="kpi violet">
        <div>
          <div class="kpi-label">Consumibles</div>
          <div class="kpi-value">{{ $consumiblesCount ?? 0 }}</div>
          <div class="kpi-sub">{{ $lowStockCount ?? 0 }} bajo mínimo</div>
        </div>
        <div class="kpi-icon">
          <i class="bi bi-box2"></i>
        </div>
      </div>
    </a>

    <a href="{{ $lowStockUrl }}" class="kpi-link reveal" style="animation-delay:.20s">
      <div class="kpi rose">
        <div>
          <div class="kpi-label">Alertas Stock</div>
          <div class="kpi-value">{{ $lowStockCount ?? 0 }}</div>
          <div class="kpi-sub">
            @if(($lowStockCount ?? 0) > 0)
              Artículos bajo mínimo
            @else
              Todo en orden
            @endif
          </div>
        </div>
        <div class="kpi-icon">
          <i class="bi bi-exclamation-triangle"></i>
        </div>
      </div>
    </a>

  </div>

  <div class="top-panels">
    <div class="panel reveal" style="animation-delay:.25s">
      <h3 class="panel-title">Activos por Categoría</h3>
      @if(collect($byCategory ?? [])->count())
        <div class="chart-wrap">
          <canvas id="chartByCategory"></canvas>
        </div>
      @else
        <div class="empty-box">No hay datos de categorías todavía.</div>
      @endif
    </div>

    <div class="panel reveal" style="animation-delay:.30s">
      <h3 class="panel-title">Estado de Activos</h3>
      <div class="chart-wrap">
        <canvas id="chartAssetStatus"></canvas>
      </div>
    </div>

    <div class="panel reveal" style="animation-delay:.35s">
      <h3 class="panel-title">Alertas de Inventario</h3>

      @if(collect($inventoryAlerts ?? [])->count())
        <div class="alerts">
          @foreach($inventoryAlerts as $item)
            <div class="alert-item">
              <div class="alert-left">
                <div class="alert-icon">
                  <i class="bi bi-arrow-down"></i>
                </div>

                <div style="min-width:0;">
                  <p class="alert-name">{{ $item->name }}</p>
                  <p class="alert-meta">
                    {{ (int) $item->stock }} {{ ((int) $item->stock) === 1 ? 'pieza' : 'piezas' }} / Min: {{ (int) $item->stock_min }}
                  </p>
                </div>
              </div>

              <span class="badge-state low">Bajo</span>
            </div>
          @endforeach
        </div>
      @else
        <div class="empty-box" style="min-height:250px;">No hay alertas de inventario.</div>
      @endif
    </div>
  </div>

  <div class="table-panel reveal" style="animation-delay:.40s">
    <h3 class="panel-title">Asignaciones Recientes</h3>

    @if(collect($recentAssignments ?? [])->isEmpty())
      <div class="empty-box">No hay asignaciones aún.</div>
    @else
      <div class="table-wrap">
        <table class="table-clean">
          <thead>
            <tr>
              <th>Activo</th>
              <th>Responsable</th>
              <th>Fecha</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            @foreach($recentAssignments as $a)
              @php
                $assignmentStatus = strtolower((string)($a->status ?? 'activa'));
                $stateClass = $assignmentStatus === 'activa'
                  ? 'ok'
                  : ($assignmentStatus === 'devuelto' ? 'gray' : 'low');

                $stateLabel = $assignmentStatus === 'activa'
                  ? 'Activa'
                  : ($assignmentStatus === 'devuelto'
                      ? 'Devuelto'
                      : ($assignmentStatus === 'perdido'
                          ? 'Perdido'
                          : ($assignmentStatus === 'dañado' || $assignmentStatus === 'danado'
                              ? 'Dañado'
                              : ucfirst($assignmentStatus))));
              @endphp

              <tr>
                <td>
                  <div class="asset-line">
                    <span class="asset-name">{{ $a->item->name ?? 'Activo eliminado' }}</span>
                    <span class="asset-tag">#ACT-{{ str_pad((int)($a->inventory_item_id ?? 0), 3, '0', STR_PAD_LEFT) }}</span>
                  </div>
                </td>
                <td>{{ $a->assigned_user_name ?? 'Sin responsable' }}</td>
                <td>
                  {{ !empty($a->assigned_at) ? \Carbon\Carbon::parse($a->assigned_at)->format('Y-m-d') : '—' }}
                </td>
                <td>
                  <span class="badge-state {{ $stateClass }}">{{ $stateLabel }}</span>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

</div>

<script>
const categoryLabels = @json($categoryLabels);
const categoryCounts = @json($categoryCounts);
const categoryColors = @json($categoryColors);

if (document.getElementById('chartByCategory') && categoryLabels.length) {
  new Chart(document.getElementById('chartByCategory'), {
    type: 'doughnut',
    data: {
      labels: categoryLabels,
      datasets: [{
        data: categoryCounts,
        backgroundColor: categoryColors.slice(0, categoryLabels.length),
        borderColor: '#ffffff',
        borderWidth: 4,
        hoverOffset: 6
      }]
    },
    options: {
      maintainAspectRatio: false,
      cutout: '62%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            usePointStyle: true,
            pointStyle: 'rect',
            boxWidth: 10,
            boxHeight: 10,
            padding: 14,
            color: '#64748b',
            font: {
              size: 11,
              weight: '600'
            }
          }
        },
        tooltip: {
          backgroundColor: '#163047',
          titleColor: '#fff',
          bodyColor: '#fff',
          padding: 12
        }
      }
    }
  });
}

const assetStatusLabels = @json($statusLabels);
const assetStatusCounts = @json($statusCounts);

if (document.getElementById('chartAssetStatus')) {
  new Chart(document.getElementById('chartAssetStatus'), {
    type: 'bar',
    data: {
      labels: assetStatusLabels,
      datasets: [{
        label: 'Cantidad',
        data: assetStatusCounts,
        backgroundColor: '#60a5fa',
        borderRadius: 8,
        borderSkipped: false,
        maxBarThickness: 56
      }]
    },
    options: {
      maintainAspectRatio: false,
      scales: {
        x: {
          grid: { display: false },
          ticks: {
            color: '#6b7c93',
            font: { size: 11, weight: '600' }
          }
        },
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0,
            color: '#6b7c93',
            font: { size: 11, weight: '600' }
          },
          grid: {
            color: '#edf2f7',
            drawBorder: false
          }
        }
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#163047',
          titleColor: '#fff',
          bodyColor: '#fff',
          padding: 12
        }
      }
    }
  });
}
</script>
@endsection