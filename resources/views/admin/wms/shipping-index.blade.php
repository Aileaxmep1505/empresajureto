@extends('layouts.app')

@section('title', 'WMS · Control de Embarques Premium')

@section('content')
@php
    use Illuminate\Support\Collection;
    use Illuminate\Support\Facades\Route;

    $homeUrl = Route::has('admin.wms.home') ? route('admin.wms.home') : '#';

    $badgeMap = [
        'draft' => 'is-draft',
        'loading' => 'is-loading',
        'loaded_complete' => 'is-complete',
        'loaded_partial' => 'is-partial',
        'dispatched' => 'is-dispatched',
        'cancelled' => 'is-cancelled',
        'pending' => 'is-draft',
        'partial' => 'is-partial',
        'complete' => 'is-complete',
    ];

    $statusLabel = function ($status) {
        return match((string) $status) {
            'draft' => 'Borrador',
            'loading' => 'Cargando',
            'loaded_complete' => 'Cerrado completo',
            'loaded_partial' => 'Cerrado parcial',
            'dispatched' => 'Despachado',
            'cancelled' => 'Cancelado',
            'pending' => 'Pendiente',
            'partial' => 'Parcial',
            'complete' => 'Completo',
            default => ucfirst((string) $status),
        };
    };

    $progressPct = function ($loaded, $expected) {
        $expected = max(1, (int) $expected);
        return min(100, (int) round(((int) $loaded / $expected) * 100));
    };

    $toArray = function ($value): array {
        if ($value instanceof Collection) return $value->values()->all();
        if (is_array($value)) return array_values($value);
        if (is_object($value)) return json_decode(json_encode($value, JSON_UNESCAPED_UNICODE), true) ?: [];
        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? array_values($decoded) : [];
        }
        return [];
    };

    $toAssoc = function ($value): array {
        if ($value instanceof Collection) return $value->toArray();
        if (is_array($value)) return $value;
        if (is_object($value)) return json_decode(json_encode($value, JSON_UNESCAPED_UNICODE), true) ?: [];
        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    };

    $drawerDetails = [];
@endphp

<div class="ship-wrap fade-in-up">
    <header class="ship-head">
        <div class="ship-head-left">
            <a href="{{ $homeUrl }}" class="ship-btn ship-btn-icon ship-btn-ghost">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
            </a>

            <div>
                <h1 class="ship-title">Control de Embarques</h1>
                <p class="ship-sub">Panel de gestión logística avanzada para validación y despacho.</p>
            </div>
        </div>

        <div class="ship-head-actions">
            <button class="ship-btn ship-btn-pulse" onclick="window.location.reload()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin-icon">
                    <path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/>
                    <path d="M21 3v5h-5"/>
                </svg>
                Actualizar
            </button>
        </div>
    </header>

    <div class="ship-kpi-bento">
        <div class="ship-kpi ship-kpi-1 stagger-1">
            <div class="ship-kpi-header">
                <span class="ship-kpi-label">Volumen Total</span>
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2"/>
                    <path d="M8 21h8"/>
                    <path d="M12 17v4"/>
                </svg>
            </div>
            <div class="ship-kpi-value">{{ number_format((int) ($stats['total'] ?? 0)) }}</div>
            <div class="ship-kpi-trend text-indigo">+12% este mes</div>
        </div>

        <div class="ship-kpi ship-kpi-2 stagger-2">
            <div class="ship-kpi-header">
                <span class="ship-kpi-label">En Bahía de Carga</span>
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2v20"/>
                    <path d="m17 5-5-3-5 3"/>
                    <path d="m17 19-5 3-5-3"/>
                </svg>
            </div>
            <div class="ship-kpi-value">{{ number_format((int) ($stats['loading'] ?? 0)) }}</div>
            <div class="ship-kpi-trend text-cyan">Unidades activas</div>
        </div>

        <div class="ship-kpi ship-kpi-3 stagger-3">
            <div class="ship-kpi-header">
                <span class="ship-kpi-label">Cierres Parciales</span>
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 8v4l3 3"/>
                </svg>
            </div>
            <div class="ship-kpi-value">{{ number_format((int) ($stats['loaded_partial'] ?? 0)) }}</div>
            <div class="ship-kpi-trend alert-trend text-amber">Atención requerida</div>
        </div>

        <div class="ship-kpi ship-kpi-4 stagger-4">
            <div class="ship-kpi-header">
                <span class="ship-kpi-label">Despachos Exitosos</span>
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <path d="m9 11 3 3L22 4"/>
                </svg>
            </div>
            <div class="ship-kpi-value">{{ number_format((int) ($stats['dispatched'] ?? 0)) }}</div>
            <div class="ship-kpi-trend success-trend text-emerald">Completados hoy</div>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.wms.shipping.index') }}" class="ship-toolbar">
        <div class="ship-toolbar-search">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="search-icon">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.3-4.3"/>
            </svg>
            <input
                type="text"
                name="s"
                class="ship-input search-input"
                value="{{ $filters['s'] ?? '' }}"
                placeholder="Buscar por ID, pedido, picking o matrícula..."
            >
        </div>

        <div class="ship-toolbar-filters">
            <select name="status" class="ship-input select-input">
                <option value="">Cualquier Estado</option>
                <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Borrador</option>
                <option value="loading" @selected(($filters['status'] ?? '') === 'loading')>Cargando</option>
                <option value="loaded_complete" @selected(($filters['status'] ?? '') === 'loaded_complete')>Cerrado completo</option>
                <option value="loaded_partial" @selected(($filters['status'] ?? '') === 'loaded_partial')>Cerrado parcial</option>
                <option value="dispatched" @selected(($filters['status'] ?? '') === 'dispatched')>Despachado</option>
                <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Cancelado</option>
            </select>

            <button class="ship-btn ship-btn-vibrant" type="submit">Aplicar</button>

            @if(array_filter($filters ?? []))
                <a href="{{ route('admin.wms.shipping.index') }}" class="ship-btn ship-btn-ghost">Limpiar</a>
            @endif
        </div>
    </form>

    <div class="ship-card">
        @if($shipments->count() > 0)
            <div class="ship-table-container">
                <table class="ship-table">
                    <thead>
                        <tr>
                            <th>Referencia</th>
                            <th>Operación</th>
                            <th>Unidad / Chofer</th>
                            <th>Progreso</th>
                            <th>Estado</th>
                            <th class="text-right">Gestión</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shipments as $index => $row)
                            @php
                                $qtyPct = $progressPct($row->loaded_qty, $row->expected_qty);
                                $status = (string) $row->status;

                                $lines = $toArray($row->lines ?? ($row->items ?? []));
                                $scans = $toArray($row->scans ?? []);
                                $meta = $toAssoc($row->meta ?? []);

                                $drawerDetails[(string) $row->id] = [
                                    'id' => (int) $row->id,
                                    'shipment_number' => (string) ($row->shipment_number ?? ''),
                                    'status' => $status,
                                    'status_label' => $statusLabel($status),
                                    'order_number' => (string) ($row->order_number ?? ''),
                                    'task_number' => (string) ($row->task_number ?? ''),
                                    'vehicle_name' => (string) ($row->vehicle_name ?? ''),
                                    'vehicle_plate' => (string) ($row->vehicle_plate ?? ''),
                                    'driver_name' => (string) ($row->driver_name ?? ($row->delivery_user_name ?? '')),
                                    'driver_phone' => (string) ($row->driver_phone ?? ''),
                                    'route_name' => (string) ($row->route_name ?? ''),
                                    'loaded_qty' => (int) ($row->loaded_qty ?? 0),
                                    'expected_qty' => (int) ($row->expected_qty ?? 0),
                                    'missing_qty' => (int) ($row->missing_qty ?? max(0, ((int) ($row->expected_qty ?? 0) - (int) ($row->loaded_qty ?? 0)))),
                                    'loaded_boxes' => (int) ($row->loaded_boxes ?? 0),
                                    'expected_boxes' => (int) ($row->expected_boxes ?? 0),
                                    'missing_boxes' => (int) ($row->missing_boxes ?? max(0, ((int) ($row->expected_boxes ?? 0) - (int) ($row->loaded_boxes ?? 0)))),
                                    'scanned_lines' => (int) ($row->scanned_lines ?? 0),
                                    'expected_lines' => (int) ($row->expected_lines ?? count($lines)),
                                    'signed_by_name' => (string) ($row->signed_by_name ?? ''),
                                    'signed_by_role' => (string) ($row->signed_by_role ?? ''),
                                    'signature_data' => (string) ($row->signature_data ?? ''),
                                    'notes' => (string) ($row->notes ?? ''),
                                    'created_at' => (string) ($row->created_at ?? ''),
                                    'updated_at' => (string) ($row->updated_at ?? ''),
                                    'closed_at' => (string) ($row->closed_at ?? ($row->validated_at ?? ($row->completed_at ?? ''))),
                                    'dispatched_at' => (string) ($row->dispatched_at ?? ''),
                                    'cancelled_at' => (string) ($row->cancelled_at ?? ''),
                                    'scanner_url' => route('admin.wms.shipping.scanner', $row->id),
                                    'lines' => $lines,
                                    'scans' => $scans,
                                    'meta' => $meta,
                                ];
                            @endphp

                            <tr class="stagger-row" style="animation-delay: {{ $index * 50 }}ms">
                                <td>
                                    <div class="cell-primary font-mono text-cyan">{{ $row->shipment_number }}</div>
                                    <div class="cell-secondary">ID: {{ $row->id }}</div>
                                </td>

                                <td>
                                    <div class="cell-primary text-indigo">Ped: {{ $row->order_number ?: '---' }}</div>
                                    <div class="cell-secondary">Pick: {{ $row->task_number ?: 'N/A' }}</div>
                                </td>

                                <td>
                                    <div class="cell-primary">{{ $row->vehicle_name ?: 'Sin vehículo' }} ({{ $row->vehicle_plate ?: 'S/P' }})</div>
                                    <div class="cell-secondary text-muted">Cond: {{ $row->driver_name ?: 'Sin asignar' }}</div>
                                </td>

                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: {{ $qtyPct }}%"></div>
                                    </div>
                                    <div class="progress-labels">
                                        <span class="pct text-indigo">{{ $qtyPct }}% Unds.</span>
                                        <span class="unds text-muted">({{ number_format((int) $row->loaded_qty) }}/{{ number_format((int) $row->expected_qty) }})</span>
                                    </div>
                                </td>

                                <td>
                                    <span class="ship-status {{ $badgeMap[$status] ?? 'is-draft' }}">
                                        {{ $statusLabel($status) }}
                                    </span>
                                </td>

                                <td class="text-right actions-column">
                                    <button
                                        type="button"
                                        class="ship-btn-icon ship-btn-ghost js-open-drawer"
                                        data-shipment-id="{{ $row->id }}"
                                        title="Ver detalle"
                                    >
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <path d="M12 16v-4"/>
                                            <path d="M12 8h.01"/>
                                        </svg>
                                    </button>

                                    <a href="{{ route('admin.wms.shipping.scanner', $row->id) }}" class="ship-btn-icon ship-btn-vibrant" title="Ir al scanner">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                            <path d="M22 6l-10 7L2 6"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ship-pagination">
                {{ $shipments->links() }}
            </div>
        @else
            <div class="ship-empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                    <path d="m3.3 7 8.7 5 8.7-5"/>
                    <path d="M12 22V12"/>
                </svg>
                <h3>Registros No Encontrados</h3>
                <p>Intente ajustar los filtros o sincronice para nuevos embarques.</p>
            </div>
        @endif
    </div>
</div>

<div class="ship-drawer-backdrop" id="shipDrawerBackdrop"></div>

<aside class="ship-drawer" id="shipDrawer" aria-hidden="true">
    <div class="drawer-header">
        <div class="drawer-header-info">
            <span class="drawer-id font-mono text-cyan" id="drawerShipmentId"></span>
            <h2 class="drawer-title" id="drawerTitle">Cargando operación...</h2>
            <p class="drawer-sub" id="drawerSubtitle">---</p>

            <div id="drawerStatusBadge" class="mt-2"></div>
            <p id="drawerStatusHelp" class="drawer-sub mt-2">---</p>
        </div>

        <div class="drawer-actions">
            <a href="#" id="drawerScannerLink" class="ship-btn ship-btn-pulse">Ir al Scanner</a>
            <button type="button" class="ship-btn-icon ship-btn-ghost" id="shipDrawerClose">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="drawer-body">
        <div class="drawer-grid mt-4" id="drawerContent"></div>
    </div>
</aside>

<style>
:root {
    --foreground: #09090b;
    --background: #fafafa;
    --primary: #18181b;
    --border: #e4e4e7;
    --input: #e4e4e7;
    --muted: #f4f4f5;
    --muted-foreground: #71717a;

    --vibrant-indigo: #6366f1;
    --vibrant-cyan: #22d3ee;
    --vibrant-amber: #f59e0b;
    --vibrant-emerald: #10b981;
    --vibrant-crimson: #ef4444;

    --radius-lg: 1rem;
    --radius-md: 0.75rem;
    --radius-sm: 0.5rem;
    --radius-pill: 99px;

    --font-sans: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
    --font-mono: 'JetBrains Mono', 'Fira Code', monospace;

    --shadow-md: 0 10px 30px -10px rgba(0,0,0,0.1);
    --shadow-lg: 0 20px 40px -15px rgba(0,0,0,0.15);

    --transition-smooth: cubic-bezier(0.4, 0, 0.2, 1);
    --transition-spring: cubic-bezier(0.175, 0.885, 0.32, 1.15);
}

body {
    font-family: var(--font-sans);
    color: var(--foreground);
    background-color: var(--background);
    -webkit-font-smoothing: antialiased;
}

.font-mono {
    font-family: var(--font-mono);
    font-size: 0.85rem;
    letter-spacing: -0.01rem;
}

.text-right { text-align: right; }
.text-muted { color: var(--muted-foreground); }
.mt-2 { margin-top: 0.5rem; }
.mt-3 { margin-top: 0.75rem; }
.mt-4 { margin-top: 1rem; }

.ship-wrap {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.ship-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ship-head-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.ship-title {
    font-size: 2rem;
    font-weight: 800;
    letter-spacing: -0.05rem;
    line-height: 1.1;
    margin: 0;
}

.ship-sub {
    font-size: 0.875rem;
    color: var(--muted-foreground);
    margin: 0.25rem 0 0 0;
}

.ship-head-actions {
    display: flex;
    gap: 0.5rem;
}

.ship-kpi-bento {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
}

.ship-kpi {
    background: #fff;
    padding: 1.5rem;
    border-radius: var(--radius-lg);
    border: 1px solid var(--border);
    box-shadow: var(--shadow-md);
    transition: transform 0.2s, box-shadow 0.2s;
}

.ship-kpi:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.ship-kpi-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
    color: var(--muted-foreground);
}

.ship-kpi-label {
    font-size: 0.875rem;
    font-weight: 600;
}

.ship-kpi-value {
    font-size: 2.25rem;
    font-weight: 800;
    letter-spacing: -0.06rem;
    line-height: 1;
}

.ship-kpi-trend {
    font-size: 0.75rem;
    font-weight: 600;
    margin-top: 0.5rem;
}

.text-indigo { color: var(--vibrant-indigo); }
.text-cyan { color: var(--vibrant-cyan); }
.text-amber { color: var(--vibrant-amber); }
.text-emerald { color: var(--vibrant-emerald); }

.ship-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.ship-toolbar-search {
    position: relative;
    flex: 1;
    min-width: 300px;
}

.search-icon {
    position: absolute;
    left: 0.875rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted-foreground);
    pointer-events: none;
}

.search-input {
    padding-left: 2.75rem !important;
    width: 100%;
    max-width: 400px;
}

.ship-toolbar-filters {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.ship-input {
    height: 2.5rem;
    padding: 0 1rem;
    font-size: 0.875rem;
    font-family: var(--font-sans);
    background: #fff;
    border: 1px solid var(--input);
    border-radius: var(--radius-sm);
    color: var(--foreground);
    transition: border-color 0.15s;
    outline: none;
}

.ship-input:focus {
    border-color: var(--vibrant-indigo);
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
}

.select-input {
    appearance: none;
    padding-right: 2rem;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1em;
}

.ship-btn,
.ship-btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    white-space: nowrap;
    height: 2.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    font-family: var(--font-sans);
    border-radius: var(--radius-sm);
    border: none;
    cursor: pointer;
    transition: all 0.15s var(--transition-smooth);
}

.ship-btn { padding: 0 1.25rem; }

.ship-btn:active,
.ship-btn-icon:active {
    transform: scale(0.96);
}

.ship-btn {
    background: var(--primary);
    color: #fff;
}

.ship-btn:hover {
    background: #27272a;
}

.ship-btn-vibrant {
    background: var(--vibrant-indigo);
    color: #fff;
}

.ship-btn-vibrant:hover {
    background: #4f46e5;
}

.ship-btn-ghost {
    background: transparent;
    color: var(--foreground);
}

.ship-btn-ghost:hover {
    background: var(--muted);
}

.ship-btn-icon {
    padding: 0;
    width: 2.5rem;
    flex-shrink: 0;
}

.ship-btn-sm {
    height: 2.25rem;
    padding: 0 1rem;
    font-size: 0.8125rem;
}

.ship-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

.ship-table-container {
    width: 100%;
    overflow-x: auto;
}

.ship-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

.ship-table th {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--muted-foreground);
    text-transform: uppercase;
    letter-spacing: 0.05rem;
    padding: 1rem;
    border-bottom: 1px solid var(--border);
    background: var(--background);
}

.ship-table td {
    padding: 1.25rem 1rem;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
    transition: background 0.15s;
}

.ship-table tbody tr:hover td {
    background: var(--muted);
}

.ship-table tbody tr:last-child td {
    border-bottom: none;
}

.cell-primary {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--foreground);
}

.cell-secondary {
    font-size: 0.8125rem;
    color: var(--muted-foreground);
    margin-top: 0.125rem;
}

.progress-bar {
    height: 5px;
    width: 100%;
    max-width: 180px;
    background: var(--muted);
    border-radius: 99px;
    overflow: hidden;
    margin-bottom: 0.375rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--vibrant-indigo), var(--vibrant-cyan));
    transition: width 0.8s var(--transition-smooth);
}

.progress-labels {
    display: flex;
    gap: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    font-family: var(--font-mono);
}

.ship-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 1.75rem;
    padding: 0 0.875rem;
    border-radius: var(--radius-pill);
    font-size: 0.75rem;
    font-weight: 700;
    color: #fff;
}

.ship-status-sm {
    height: 1.5rem;
    padding: 0 0.625rem;
    font-size: 0.6875rem;
}

.is-draft { background: var(--vibrant-crimson); }
.is-loading { background: var(--vibrant-amber); }
.is-complete { background: var(--vibrant-emerald); }
.is-dispatched { background: var(--vibrant-indigo); }
.is-partial { background: #f97316; }
.is-cancelled { background: var(--vibrant-crimson); }

.actions-column {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.ship-pagination {
    padding: 1rem;
    border-top: 1px solid var(--border);
    background: #fff;
}

.ship-empty-state {
    padding: 5rem 2rem;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--muted-foreground);
}

.ship-empty-state h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--foreground);
    margin: 1rem 0 0.5rem 0;
}

.ship-empty-state p {
    font-size: 0.875rem;
    margin: 0;
    max-width: 320px;
}

.ship-drawer-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.25);
    backdrop-filter: blur(4px);
    z-index: 50;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s var(--transition-smooth);
}

.ship-drawer-backdrop.is-open {
    opacity: 1;
    pointer-events: auto;
}

.ship-drawer {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    max-width: 620px;
    background: #fff;
    border-left: 1px solid var(--border);
    box-shadow: -20px 0 60px rgba(0,0,0,0.1);
    z-index: 100;
    transform: translateX(100%);
    transition: transform 0.4s var(--transition-spring);
    display: flex;
    flex-direction: column;
}

.ship-drawer.is-open {
    transform: translateX(0);
}

.drawer-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(6px);
    position: sticky;
    top: 0;
    z-index: 10;
    gap: 1rem;
}

.drawer-id {
    font-size: 0.75rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    display: block;
}

.drawer-title {
    font-size: 1.5rem;
    font-weight: 800;
    margin: 0;
    letter-spacing: -0.04rem;
}

.drawer-sub {
    font-size: 0.875rem;
    color: var(--muted-foreground);
    margin: 0.25rem 0 0 0;
}

.drawer-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-shrink: 0;
}

.drawer-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.drawer-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.drawer-card {
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 1.25rem;
    background: #fff;
    box-shadow: var(--shadow-md);
}

.full-span {
    grid-column: 1 / -1;
}

.card-title {
    font-size: 0.8125rem;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--muted-foreground);
    margin: 0 0 1rem 0;
    letter-spacing: 0.05rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.data-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.data-item {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    font-size: 0.875rem;
    border-bottom: 1px solid var(--muted);
    padding-bottom: 0.5rem;
}

.data-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.data-item .label {
    font-weight: 500;
    color: var(--muted-foreground);
}

.data-item .value {
    font-weight: 600;
    text-align: right;
    color: var(--foreground);
}

.drawer-kpi-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.mini-kpi {
    background: var(--muted);
    padding: 0.75rem;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.mini-kpi .label {
    font-size: 0.75rem;
    color: var(--muted-foreground);
    font-weight: 600;
}

.mini-kpi .value {
    font-size: 1.25rem;
    font-weight: 800;
    font-family: var(--font-mono);
    color: var(--foreground);
}

.timeline {
    position: relative;
    padding-left: 1.25rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 0.25rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--border);
}

.timeline-item {
    position: relative;
    margin-bottom: 1.25rem;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -1.3rem;
    top: 0.2rem;
    width: 0.65rem;
    height: 0.65rem;
    border-radius: 50%;
    background: #fff;
    border: 2px solid var(--vibrant-indigo);
}

.timeline-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--foreground);
}

.timeline-date {
    font-size: 0.75rem;
    color: var(--muted-foreground);
    font-family: var(--font-mono);
    margin-top: 0.125rem;
}

.line-card {
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 1rem;
    margin-bottom: 0.75rem;
    background: var(--muted);
}

.line-card:last-child {
    margin-bottom: 0;
}

.line-card-header {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.line-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--foreground);
}

.line-sku {
    font-size: 0.75rem;
    color: var(--muted-foreground);
    font-family: var(--font-mono);
}

.line-metrics {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
    margin-top: 0.75rem;
}

.line-metric {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 0.25rem;
    padding: 0.5rem;
    text-align: center;
}

.line-metric .lbl {
    font-size: 0.625rem;
    text-transform: uppercase;
    color: var(--muted-foreground);
    font-weight: 700;
    letter-spacing: 0.02rem;
}

.line-metric .val {
    font-size: 0.875rem;
    font-weight: 700;
    font-family: var(--font-mono);
    margin-top: 0.125rem;
    color: var(--foreground);
}

.signature-box {
    height: 120px;
    border: 1px dashed var(--border);
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    overflow: hidden;
}

.signature-box img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.note-box {
    font-size: 0.875rem;
    color: var(--foreground);
    background: var(--muted);
    padding: 1rem;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border);
    min-height: 120px;
}

.ship-empty-mini {
    padding: 1rem;
    border: 1px dashed var(--border);
    border-radius: var(--radius-sm);
    background: #fff;
    color: var(--muted-foreground);
    font-size: 0.875rem;
}

body.has-drawer-open {
    overflow: hidden;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUpFade {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.2); }
    70% { box-shadow: 0 0 0 6px rgba(99, 102, 241, 0); }
    100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.fade-in-up { animation: slideUpFade 0.4s ease-out forwards; }
.stagger-row { opacity: 0; animation: slideUpFade 0.35s var(--transition-smooth) forwards; }
.ship-btn-pulse { animation: pulse 2s infinite; }
.ship-head-actions button:hover svg { animation: spin 1s linear infinite; }

.stagger-1 { animation-delay: 50ms; }
.stagger-2 { animation-delay: 100ms; }
.stagger-3 { animation-delay: 150ms; }
.stagger-4 { animation-delay: 200ms; }

@media (max-width: 1024px) {
    .ship-wrap { padding: 1.5rem; }
    .ship-kpi-bento { grid-template-columns: 1fr 1fr; }
    .drawer-grid { grid-template-columns: 1fr; }
    .ship-title { font-size: 1.75rem; }
}

@media (max-width: 640px) {
    .ship-wrap { padding: 1rem; }
    .ship-head { flex-direction: column; align-items: stretch; gap: 1rem; }
    .ship-kpi-bento { grid-template-columns: 1fr; }
    .ship-toolbar { flex-direction: column; align-items: stretch; }
    .ship-toolbar-search { min-width: 100%; }
    .actions-column { flex-direction: column; align-items: flex-end; }
    .drawer-header { flex-direction: column; align-items: stretch; }
    .drawer-actions { justify-content: space-between; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const shipmentDetails = @json($drawerDetails, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    const drawer = document.getElementById('shipDrawer');
    const backdrop = document.getElementById('shipDrawerBackdrop');
    const closeBtn = document.getElementById('shipDrawerClose');
    const drawerContent = document.getElementById('drawerContent');

    const els = {
        kicker: document.getElementById('drawerShipmentId'),
        title: document.getElementById('drawerTitle'),
        subtitle: document.getElementById('drawerSubtitle'),
        statusBadge: document.getElementById('drawerStatusBadge'),
        statusHelp: document.getElementById('drawerStatusHelp'),
        scannerLink: document.getElementById('drawerScannerLink')
    };

    function escapeHtml(v) {
        return String(v ?? '').replace(/[&<>"']/g, function (m) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[m];
        });
    }

    function formatDate(v) {
        if (!v) return '—';

        const d = new Date(v);

        return isNaN(d.getTime())
            ? String(v)
            : new Intl.DateTimeFormat('es-MX', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }).format(d);
    }

    function ucFirst(v) {
        v = String(v ?? '');
        return v ? v.charAt(0).toUpperCase() + v.slice(1) : '';
    }

    function getStatusClass(s) {
        const m = {
            draft: 'is-draft',
            loading: 'is-loading',
            loaded_complete: 'is-complete',
            loaded_partial: 'is-partial',
            dispatched: 'is-dispatched',
            cancelled: 'is-cancelled',
            pending: 'is-draft',
            partial: 'is-partial',
            complete: 'is-complete'
        };

        return m[s] || 'is-draft';
    }

    function statusLabel(s) {
        const m = {
            draft: 'Borrador',
            loading: 'Cargando',
            loaded_complete: 'Cerrado completo',
            loaded_partial: 'Cerrado parcial',
            dispatched: 'Despachado',
            cancelled: 'Cancelado',
            pending: 'Pendiente',
            partial: 'Parcial',
            complete: 'Completo'
        };

        return m[s] || ucFirst(s);
    }

    function pct(loaded, expected) {
        const e = Math.max(1, Number(expected || 0));
        return Math.max(0, Math.min(100, Math.round((Number(loaded || 0) / e) * 100)));
    }

    function openDrawer(id) {
        const data = shipmentDetails[id];
        if (!data) return;

        drawerContent.style.opacity = '0';
        drawerContent.style.transform = 'translateY(8px)';

        els.kicker.textContent = data.shipment_number || ('EMB-' + data.id);
        els.title.textContent = 'Embarque ' + (data.shipment_number || data.id);
        els.subtitle.textContent = 'ID Operativo: ' + data.id + ' • Picking: ' + (data.task_number || 'N/A');
        els.scannerLink.href = data.scanner_url || '#';

        if (els.statusBadge) {
            els.statusBadge.innerHTML = `
                <span class="ship-status ${getStatusClass(data.status)}">
                    ${escapeHtml(data.status_label || statusLabel(data.status))}
                </span>
            `;
        }

        if (els.statusHelp) {
            if (data.cancelled_at) {
                els.statusHelp.textContent = 'Cancelado el ' + formatDate(data.cancelled_at);
            } else if (data.dispatched_at) {
                els.statusHelp.textContent = 'Despachado el ' + formatDate(data.dispatched_at);
            } else if (data.closed_at) {
                els.statusHelp.textContent = 'Cierre validado el ' + formatDate(data.closed_at);
            } else {
                els.statusHelp.textContent = 'Operación logística en curso.';
            }
        }

        const missingQty = Math.max(0, Number(data.expected_qty || 0) - Number(data.loaded_qty || 0));
        const progress = pct(data.loaded_qty, data.expected_qty);

        drawerContent.innerHTML = `
            <div class="drawer-kpi-group">
                <div class="mini-kpi">
                    <span class="label">Piezas validadas</span>
                    <span class="value text-emerald">${Number(data.loaded_qty || 0)}/${Number(data.expected_qty || 0)}</span>
                </div>
                <div class="mini-kpi">
                    <span class="label">Cajas totales</span>
                    <span class="value text-indigo">${Number(data.loaded_boxes || 0)}/${Number(data.expected_boxes || 0)}</span>
                </div>
                <div class="mini-kpi">
                    <span class="label">Líneas procesadas</span>
                    <span class="value">${Number(data.scanned_lines || 0)}/${Number(data.expected_lines || 0)}</span>
                </div>
                <div class="mini-kpi">
                    <span class="label">Piezas faltantes</span>
                    <span class="value text-amber">${missingQty}</span>
                </div>
                <div class="mini-kpi">
                    <span class="label">Avance operación</span>
                    <span class="value">${progress}%</span>
                </div>
            </div>

            <div class="drawer-card">
                <h3 class="card-title">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    Información general
                </h3>

                <div class="data-list mt-3">
                    <div class="data-item"><span class="label">Vehículo</span><span class="value">${escapeHtml(data.vehicle_name || 'Sin unidad')}</span></div>
                    <div class="data-item"><span class="label">Matrícula</span><span class="value font-mono">${escapeHtml(data.vehicle_plate || '---')}</span></div>
                    <div class="data-item"><span class="label">Responsable</span><span class="value">${escapeHtml(data.driver_name || 'Sin chofer')}</span></div>
                    <div class="data-item"><span class="label">Teléfono</span><span class="value">${escapeHtml(data.driver_phone || '---')}</span></div>
                    <div class="data-item"><span class="label">Ruta operativa</span><span class="value">${escapeHtml(data.route_name || '---')}</span></div>
                    <div class="data-item"><span class="label">Picking task</span><span class="value">${escapeHtml(data.task_number || 'N/A')}</span></div>
                    <div class="data-item"><span class="label">Pedido ref.</span><span class="value">${escapeHtml(data.order_number || '---')}</span></div>
                </div>
            </div>

            <div class="drawer-card">
                <h3 class="card-title">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 8v4l3 3"/>
                    </svg>
                    Trazabilidad
                </h3>

                <div class="timeline mt-3">
                    ${data.created_at ? `<div class="timeline-item"><div class="timeline-title">Creación</div><div class="timeline-date">${formatDate(data.created_at)}</div></div>` : ''}
                    ${data.closed_at ? `<div class="timeline-item"><div class="timeline-title">Cierre de validación</div><div class="timeline-date">${formatDate(data.closed_at)}</div></div>` : ''}
                    ${data.dispatched_at ? `<div class="timeline-item"><div class="timeline-title">Salida confirmada</div><div class="timeline-date">${formatDate(data.dispatched_at)}</div></div>` : ''}
                    ${data.cancelled_at ? `<div class="timeline-item"><div class="timeline-title">Cancelado</div><div class="timeline-date">${formatDate(data.cancelled_at)}</div></div>` : ''}
                </div>
            </div>

            <div class="drawer-card">
                <h3 class="card-title">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    Registros de firma digital
                </h3>

                <div class="data-list mt-3">
                    <div class="data-item"><span class="label">Responsable</span><span class="value">${escapeHtml(data.signed_by_name || 'Sin capturar')}</span></div>
                    <div class="data-item"><span class="label">Rol</span><span class="value text-muted">${escapeHtml(data.signed_by_role || '---')}</span></div>
                </div>

                <div class="signature-box mt-3">
                    ${data.signature_data
                        ? `<img src="${data.signature_data}" alt="Firma digital">`
                        : '<span class="text-muted">Firma digital no disponible</span>'
                    }
                </div>
            </div>

            <div class="drawer-card full-span">
                <h3 class="card-title">Líneas de embarque</h3>

                <div class="mt-4">
                    ${
                        Array.isArray(data.lines) && data.lines.length
                            ? data.lines.map(function (l) {
                                return `
                                    <div class="line-card">
                                        <div class="line-card-header">
                                            <span class="line-title">${escapeHtml(l.product_name || 'Producto')}</span>
                                            <span class="ship-status ship-status-sm ${getStatusClass(l.status || 'draft')}">
                                                ${escapeHtml(statusLabel(l.status || 'draft'))}
                                            </span>
                                        </div>

                                        <div class="line-sku">SKU: ${escapeHtml(l.product_sku || l.sku || '---')}</div>

                                        <div class="line-metrics">
                                            <div class="line-metric">
                                                <div class="lbl">Unds.</div>
                                                <div class="val">${Number(l.loaded_qty || 0)} / ${Number(l.expected_qty || 0)}</div>
                                            </div>
                                            <div class="line-metric">
                                                <div class="lbl">Cajas</div>
                                                <div class="val">${Number(l.loaded_boxes || 0)} / ${Number(l.expected_boxes || 0)}</div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }).join('')
                            : '<div class="ship-empty-mini">Sin líneas cargadas.</div>'
                    }
                </div>
            </div>
        `;

        requestAnimationFrame(function () {
            drawerContent.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            drawerContent.style.opacity = '1';
            drawerContent.style.transform = 'translateY(0)';
        });

        drawer.classList.add('is-open');
        backdrop.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
        document.body.classList.add('has-drawer-open');
    }

    function closeDrawer() {
        drawer.classList.remove('is-open');
        backdrop.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('has-drawer-open');
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.js-open-drawer');

        if (btn) {
            e.preventDefault();
            openDrawer(btn.dataset.shipmentId);
            return;
        }

        if (e.target === backdrop) {
            closeDrawer();
        }
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closeDrawer);
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeDrawer();
        }
    });
});
</script>
@endsection