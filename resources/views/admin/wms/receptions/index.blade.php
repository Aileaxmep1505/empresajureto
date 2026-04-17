@extends('layouts.app')

@section('title', 'Recepciones')
@section('titulo', 'Recepciones')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
  /* Paleta moderna (Zinc/Slate) */
  --bg-app: #f4f4f5;
  --bg-card: #ffffff;
  --text-main: #18181b;
  --text-muted: #71717a;
  --border-light: #e4e4e7;
  
  /* Nuevo color principal (Azul iOS / Moderno) */
  --primary: #007aff;
  --primary-hover: #005bb5; /* Un tono más oscuro para el hover del botón */
  --primary-soft: #e6f0ff;  /* Azul pastel para el fondo de la card activa */
  
  /* Estados Operativos */
  --success: #16a34a;
  --success-bg: #dcfce7;
  --warning: #d97706;
  --warning-bg: #fef3c7;
  --danger: #dc2626;
  --danger-bg: #fee2e2;
  --info: #0284c7;
  --info-bg: #e0f2fe;

  /* Sombras */
  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05);
  --shadow-hover: 0 10px 15px -3px rgb(0 0 0 / 0.08), 0 4px 6px -4px rgb(0 0 0 / 0.04);
  /* Sombra para el estado activo con el nuevo color azul */
  --shadow-active: 0 0 0 2px var(--primary), 0 4px 12px rgba(0, 122, 255, 0.15);
}

* { box-sizing: border-box; }

body {
  background: var(--bg-app);
  font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
  color: var(--text-main);
  -webkit-font-smoothing: antialiased;
}

/* --- Animaciones SPA --- */
@keyframes slideUpFade {
  0% { opacity: 0; transform: translateY(16px); }
  100% { opacity: 1; transform: translateY(0); }
}

.animate-entrance {
  animation: slideUpFade 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  opacity: 0;
}
.delay-1 { animation-delay: 0.1s; }
.delay-2 { animation-delay: 0.2s; }
.delay-3 { animation-delay: 0.3s; }

/* --- Layout --- */
.receptions-shell {
  max-width: 1200px;
  margin: 0 auto;
  padding: 40px 24px 80px;
}

.page-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 16px;
}

.page-head h1 {
  margin: 0;
  font-size: 28px;
  font-weight: 700;
  letter-spacing: -0.03em;
}

.page-head p {
  margin: 6px 0 0;
  color: var(--text-muted);
  font-size: 15px;
  font-weight: 500;
}

/* --- Cards --- */
.card {
  background: var(--bg-card);
  border: 1px solid var(--border-light);
  border-radius: 16px;
  box-shadow: var(--shadow-md);
  margin-bottom: 24px;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover { box-shadow: var(--shadow-hover); }
.card-body { padding: 24px; }

/* --- Formularios --- */
.filters-grid {
  display: grid;
  grid-template-columns: 2fr 1fr auto;
  gap: 16px;
  align-items: end;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  font-size: 13px;
  font-weight: 600;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.form-input {
  width: 100%;
  height: 44px;
  border: 1px solid var(--border-light);
  border-radius: 10px;
  background: #fafafa;
  padding: 0 16px;
  font-family: inherit;
  font-size: 14px;
  font-weight: 500;
  color: var(--text-main);
  transition: all 0.2s ease;
  outline: none;
}

.form-input:hover { background: #fff; border-color: #d4d4d8; }
.form-input:focus { background: #fff; border-color: var(--primary); box-shadow: 0 0 0 4px var(--primary-soft); }

/* --- Botones --- */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: 44px;
  padding: 0 20px;
  border-radius: 10px;
  font-family: inherit;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  text-decoration: none;
  border: none;
}

.btn:active { transform: scale(0.96); }

.btn-primary {
  background: var(--primary);
  color: #fff;
  box-shadow: var(--shadow-sm);
}

.btn-primary:hover {
  background: var(--primary-hover);
  box-shadow: 0 6px 12px rgba(0, 122, 255, 0.2);
}

.btn-outline {
  background: #fff;
  border: 1px solid var(--border-light);
  color: var(--text-main);
}

.btn-outline:hover { background: #f4f4f5; border-color: #d4d4d8; }

/* --- Stats Grid Interactivo --- */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0,1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.stat-card {
  background: var(--bg-card);
  border: 1px solid var(--border-light);
  border-radius: 16px;
  padding: 24px;
  box-shadow: var(--shadow-sm);
  transition: all 0.25s ease;
  text-decoration: none;
  display: block;
  position: relative;
  overflow: hidden;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-hover);
  border-color: #d4d4d8;
}

/* AQUI ESTÁ LA MAGIA DEL FONDO PASTEL */
.stat-card.is-active {
  border-color: var(--primary);
  box-shadow: var(--shadow-active);
  background: var(--primary-soft); /* El fondo se vuelve azul pastel claro */
}

.stat-label {
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 12px;
  transition: color 0.2s;
}

.stat-card.is-active .stat-label { color: var(--primary); }

.stat-value {
  color: var(--text-main);
  font-size: 32px;
  font-weight: 700;
  line-height: 1;
  letter-spacing: -0.03em;
}

/* Opcional: Si quieres que el número también se pinte de azul al estar activo */
.stat-card.is-active .stat-value {
  color: var(--primary);
}

.stat-sub {
  margin-top: 8px;
  font-size: 13px;
  color: var(--text-muted);
  font-weight: 500;
}

/* --- Tabla --- */
.table-wrap { overflow-x: auto; }

.table-clean {
  width: 100%;
  border-collapse: collapse;
  min-width: 1050px;
}

.table-clean th {
  text-align: left;
  font-size: 12px;
  color: var(--text-muted);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 16px 24px;
  border-bottom: 1px solid var(--border-light);
  background: rgba(250, 250, 250, 0.8);
  backdrop-filter: blur(8px);
}

.table-clean td {
  padding: 20px 24px;
  border-bottom: 1px solid #f4f4f5;
  vertical-align: middle;
  font-size: 14px;
  transition: background 0.2s ease;
}

.table-clean tbody tr { transition: all 0.2s ease; }
.table-clean tbody tr:hover { background: #fafafa; }

.table-title-main { display: block; color: var(--text-main); font-weight: 700; font-size: 15px; }
.table-title-sub { display: block; color: var(--text-muted); font-size: 13px; font-weight: 500; margin-top: 4px; }

/* --- Badges --- */
.badge, .badge-soft {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.02em;
}

.badge-pending { background: var(--warning-bg); color: var(--warning); }
.badge-signed { background: var(--success-bg); color: var(--success); }
.badge-cancel { background: var(--danger-bg); color: var(--danger); }
.badge-soft.info { background: var(--info-bg); color: var(--info); }

/* Dot indicator for status badges */
.badge::before {
  content: '';
  display: block;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

/* --- Botones de Tabla --- */
.btn-table {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 34px;
  padding: 0 14px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s ease;
  border: 1px solid transparent;
}

.btn-table.primary { background: var(--primary-soft); color: var(--primary); border: 1px solid var(--border-light); }
.btn-table.primary:hover { background: #dcebff; border-color: #b3d4ff; }
.btn-table.neutral { background: #fff; color: var(--text-muted); border: 1px solid var(--border-light); }
.btn-table.neutral:hover { background: var(--bg-app); color: var(--text-main); }

/* --- Estados Globales --- */
.flash-ok {
  background: var(--success-bg);
  border: 1px solid #bbf7d0;
  color: var(--success);
  padding: 16px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 24px;
}

.empty-state { text-align: center; padding: 80px 20px; }
.empty-icon {
  width: 80px; height: 80px; border-radius: 24px;
  background: #f4f4f5; color: #a1a1aa;
  display: inline-flex; align-items: center; justify-content: center;
  margin: 0 auto 24px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
}
.empty-state h3 { margin: 0 0 10px; font-size: 20px; font-weight: 700; }
.empty-state p { margin: 0; color: var(--text-muted); font-size: 15px; }

/* Responsive */
@media (max-width: 980px) {
  .filters-grid { grid-template-columns: 1fr; }
  .stats-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
}
@media (max-width: 640px) {
  .stats-grid { grid-template-columns: 1fr; }
  .page-head { flex-direction: column; align-items: flex-start; }
  .btn { width: 100%; }
}
</style>

@php
  $activeStatus = request('status', '');
@endphp

<div class="receptions-shell">
  <div class="page-head animate-entrance">
    <div>
      <h1>Recepciones WMS</h1>
      <p>Historial y control de ingresos al almacén.</p>
    </div>
    <div class="head-actions">
      <a href="{{ route('admin.wms.receptions.create') }}" class="btn btn-primary">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
        </svg>
        Nueva Recepción
      </a>
    </div>
  </div>

  @if(session('ok'))
    <div class="flash-ok animate-entrance">{{ session('ok') }}</div>
  @endif

  <div class="card animate-entrance delay-1">
    <div class="card-body">
      <form method="GET" action="" class="filters-grid">
        <div>
          <label class="form-label">Búsqueda Global</label>
          <input type="text" name="q" class="form-input" value="{{ request('q') }}" placeholder="Folio, responsable, proveedor...">
        </div>
        <div>
          <label class="form-label">Fecha de Ingreso</label>
          <input type="date" name="date" class="form-input" value="{{ request('date') }}">
        </div>
        <div style="display:flex; gap:12px;">
          <button type="submit" class="btn btn-primary">Filtrar</button>
          <a href="{{ route('admin.wms.receptions.index') }}" class="btn btn-outline">Limpiar</a>
        </div>
      </form>
    </div>
  </div>

  <div class="stats-grid animate-entrance delay-2">
    <a href="{{ route('admin.wms.receptions.index', array_filter(request()->except('page', 'status'))) }}"
       class="stat-card {{ $activeStatus === '' ? 'is-active' : '' }}">
      <div class="stat-label">Total Listado</div>
      <div class="stat-value">{{ number_format($totalReceptions ?? 0) }}</div>
      <div class="stat-sub">Registros encontrados</div>
    </a>

    <a href="{{ route('admin.wms.receptions.index', array_filter(array_merge(request()->except('page'), ['status' => 'pendiente']))) }}"
       class="stat-card {{ $activeStatus === 'pendiente' ? 'is-active' : '' }}">
      <div class="stat-label">Pendientes</div>
      <div class="stat-value" style="color: var(--warning);">{{ number_format($pendingCount ?? 0) }}</div>
      <div class="stat-sub">Requieren atención</div>
    </a>

    <a href="{{ route('admin.wms.receptions.index', array_filter(array_merge(request()->except('page'), ['status' => 'firmado']))) }}"
       class="stat-card {{ $activeStatus === 'firmado' ? 'is-active' : '' }}">
      <div class="stat-label">Completadas</div>
      <div class="stat-value" style="color: var(--success);">{{ number_format($signedCount ?? 0) }}</div>
      <div class="stat-sub">Operación cerrada</div>
    </a>

    <a href="{{ route('admin.wms.receptions.index', array_filter(request()->except('page', 'status'))) }}"
       class="stat-card">
      <div class="stat-label">Unidades Físicas</div>
      <div class="stat-value">{{ number_format($totalUnits ?? 0) }}</div>
      <div class="stat-sub">Volumen total listado</div>
    </a>
  </div>

  <div class="card animate-entrance delay-3">
    <div class="card-body" style="padding:0;">
      @if(($receptions ?? collect())->count())
        <div class="table-wrap">
          <table class="table-clean">
            <thead>
              <tr>
                <th>Identificador</th>
                <th>Involucrados</th>
                <th>Estatus</th>
                <th>Productos</th>
                <th>Volumen</th>
                <th>Fecha Reg.</th>
                <th style="text-align:right;">Acciones</th>
              </tr>
            </thead>
            <tbody>
              @foreach($receptions as $reception)
                @php
                  $status = (string) ($reception->status ?? 'pendiente');
                  $statusClass = match($status) {
                    'firmado' => 'badge-signed',
                    'cancelado' => 'badge-cancel',
                    default => 'badge-pending',
                  };
                  $statusText = match($status) {
                    'firmado' => 'Completada',
                    'cancelado' => 'Cancelada',
                    default => 'Pendiente',
                  };

                  $productsCount = (int) ($reception->lines_count ?? 0);
                  $unitsCount = (int) ($reception->lines_sum_quantity ?? 0);

                  $dateText = null;
                  $rawDate = $reception->reception_date ?: $reception->created_at;
                  if (!empty($rawDate)) {
                    try {
                      $dateText = \Carbon\Carbon::parse($rawDate)->format('d/m/Y - H:i');
                    } catch (\Throwable $e) {
                      $dateText = $rawDate;
                    }
                  }
                @endphp
                <tr>
                  <td>
                    <span class="table-title-main">{{ $reception->folio ?? ('REC-' . str_pad($reception->id, 5, '0', STR_PAD_LEFT)) }}</span>
                    <span class="table-title-sub">ID: #{{ $reception->id }}</span>
                  </td>
                  <td>
                    <div style="display:flex; flex-direction:column; gap:4px;">
                      <span style="font-weight:600; color:var(--text-main);">E: {{ $reception->deliverer_name ?? 'N/D' }}</span>
                      <span style="color:var(--text-muted); font-size:13px; font-weight:500;">R: {{ $reception->receiver_name ?? 'N/D' }}</span>
                    </div>
                  </td>
                  <td>
                    <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                  </td>
                  <td>
                    <span class="badge-soft info">{{ number_format($productsCount) }} Items</span>
                  </td>
                  <td>
                    <span class="badge-soft info" style="background: #f4f4f5; color: var(--text-main);">{{ number_format($unitsCount) }} U.</span>
                  </td>
                  <td>
                    <span style="color:var(--text-main); font-weight:500;">{{ $dateText ?? '—' }}</span>
                  </td>
                  <td style="text-align:right;">
                    <div style="display:flex; gap:8px; justify-content:flex-end;">
                      <a href="{{ route('admin.wms.receptions.show', $reception->id) }}" class="btn-table primary">Detalle</a>
                      @if(Route::has('admin.wms.receptions.pdf'))
                        <a href="{{ route('admin.wms.receptions.pdf', $reception->id) }}" class="btn-table neutral" target="_blank">PDF</a>
                      @endif
                      @if(Route::has('admin.wms.receptions.labels'))
                        <a href="{{ route('admin.wms.receptions.labels', $reception->id) }}" class="btn-table neutral" target="_blank">Tags</a>
                      @endif
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        @if(method_exists($receptions, 'links'))
          <div style="padding: 24px; border-top: 1px solid var(--border-light);">
            {{ $receptions->withQueryString()->links() }}
          </div>
        @endif
      @else
        <div class="empty-state">
          <div class="empty-icon">
            <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V7a2 2 0 00-1-1.732l-7-4a2 2 0 00-2 0l-7 4A2 2 0 002 7v10a2 2 0 001 1.732l7 4a2 2 0 002 0l7-4A2 2 0 0020 17v-4"></path>
              <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 4.21l9 5.19m-9 5.19l9-5.19M12 22V12"></path>
            </svg>
          </div>
          <h3>No hay recepciones registradas</h3>
          <p>Los ingresos al almacén aparecerán aquí una vez capturados.</p>
          <div style="margin-top:24px;">
            <a href="{{ route('admin.wms.receptions.create') }}" class="btn btn-primary">Crear la primera recepción</a>
          </div>
        </div>
      @endif
    </div>
  </div>
</div>
@endsection