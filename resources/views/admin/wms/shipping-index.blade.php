@extends('layouts.app')

@section('title', 'WMS · Embarques')

@section('content')
@php
    $homeUrl = \Illuminate\Support\Facades\Route::has('admin.wms.home') ? route('admin.wms.home') : '#';

    $badgeMap = [
        'draft' => 'is-draft',
        'loading' => 'is-loading',
        'loaded_complete' => 'is-complete',
        'loaded_partial' => 'is-partial',
        'dispatched' => 'is-dispatched',
        'cancelled' => 'is-cancelled',
    ];

    $statusLabel = function ($status) {
        return match((string) $status) {
            'draft' => 'Borrador',
            'loading' => 'Cargando',
            'loaded_complete' => 'Cerrado completo',
            'loaded_partial' => 'Cerrado parcial',
            'dispatched' => 'Despachado',
            'cancelled' => 'Cancelado',
            default => ucfirst((string) $status),
        };
    };

    $progressPct = function ($loaded, $expected) {
        $expected = max(1, (int) $expected);
        return min(100, (int) round(((int) $loaded / $expected) * 100));
    };
@endphp

<div class="ship-wrap">
    <div class="ship-head">
        <div class="ship-head-left">
            <a href="{{ $homeUrl }}" class="ship-btn ship-btn-ghost">← WMS</a>
            <div>
                <div class="ship-title">Control de embarques</div>
                <div class="ship-sub">
                    Valida la carga contra picking, controla faltantes, registra firma y marca salida de unidad.
                </div>
            </div>
        </div>

        <div class="ship-head-right">
            <a href="{{ route('admin.wms.picking.v2') }}" class="ship-btn ship-btn-ghost">Picking</a>
        </div>
    </div>

    <div class="ship-kpis">
        <div class="ship-kpi">
            <div class="ship-kpi-label">Embarques</div>
            <div class="ship-kpi-value">{{ number_format((int) ($stats['total'] ?? 0)) }}</div>
            <div class="ship-kpi-foot">Total filtrado</div>
        </div>

        <div class="ship-kpi">
            <div class="ship-kpi-label">Cargando</div>
            <div class="ship-kpi-value">{{ number_format((int) ($stats['loading'] ?? 0)) }}</div>
            <div class="ship-kpi-foot">Unidades activas</div>
        </div>

        <div class="ship-kpi">
            <div class="ship-kpi-label">Cerrados parciales</div>
            <div class="ship-kpi-value">{{ number_format((int) ($stats['loaded_partial'] ?? 0)) }}</div>
            <div class="ship-kpi-foot">Con faltantes</div>
        </div>

        <div class="ship-kpi">
            <div class="ship-kpi-label">Despachados</div>
            <div class="ship-kpi-value">{{ number_format((int) ($stats['dispatched'] ?? 0)) }}</div>
            <div class="ship-kpi-foot">Salida confirmada</div>
        </div>

        <div class="ship-kpi">
            <div class="ship-kpi-label">Piezas cargadas</div>
            <div class="ship-kpi-value">{{ number_format((int) ($stats['loaded_qty'] ?? 0)) }}</div>
            <div class="ship-kpi-foot">
                de {{ number_format((int) ($stats['expected_qty'] ?? 0)) }} esperadas
            </div>
        </div>

        <div class="ship-kpi">
            <div class="ship-kpi-label">Cajas cargadas</div>
            <div class="ship-kpi-value">{{ number_format((int) ($stats['loaded_boxes'] ?? 0)) }}</div>
            <div class="ship-kpi-foot">
                de {{ number_format((int) ($stats['expected_boxes'] ?? 0)) }} esperadas
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.wms.shipping.index') }}" class="ship-filters">
        <div class="ship-field ship-field-grow">
            <label class="ship-label">Buscar</label>
            <input
                type="text"
                name="s"
                class="ship-input"
                value="{{ $filters['s'] ?? '' }}"
                placeholder="Embarque, pedido, picking, chofer, placa o ruta">
        </div>

        <div class="ship-field">
            <label class="ship-label">Estado</label>
            <select name="status" class="ship-input">
                <option value="">Todos</option>
                <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Borrador</option>
                <option value="loading" @selected(($filters['status'] ?? '') === 'loading')>Cargando</option>
                <option value="loaded_complete" @selected(($filters['status'] ?? '') === 'loaded_complete')>Cerrado completo</option>
                <option value="loaded_partial" @selected(($filters['status'] ?? '') === 'loaded_partial')>Cerrado parcial</option>
                <option value="dispatched" @selected(($filters['status'] ?? '') === 'dispatched')>Despachado</option>
                <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Cancelado</option>
            </select>
        </div>

        <div class="ship-actions">
            <button class="ship-btn" type="submit">Filtrar</button>
            <a href="{{ route('admin.wms.shipping.index') }}" class="ship-btn ship-btn-ghost">Limpiar</a>
        </div>
    </form>

    <div class="ship-panel">
        <div class="ship-panel-head">
            <div>
                <div class="ship-panel-title">Embarques registrados</div>
                <div class="ship-panel-sub">Seguimiento completo de carga, faltantes y despacho.</div>
            </div>
        </div>

        @if($shipments->count() > 0)
            <div class="ship-table-wrap">
                <table class="ship-table">
                    <thead>
                        <tr>
                            <th>Embarque</th>
                            <th>Pedido / Picking</th>
                            <th>Unidad</th>
                            <th>Chofer / Ruta</th>
                            <th>Carga</th>
                            <th>Cajas</th>
                            <th>Estado</th>
                            <th class="ship-col-actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shipments as $row)
                            @php
                                $qtyPct = $progressPct($row->loaded_qty, $row->expected_qty);
                                $boxPct = $progressPct($row->loaded_boxes, $row->expected_boxes);
                                $status = (string) $row->status;
                            @endphp
                            <tr>
                                <td>
                                    <div class="ship-cell-main">{{ $row->shipment_number }}</div>
                                    <div class="ship-cell-sub">ID {{ $row->id }}</div>
                                </td>

                                <td>
                                    <div class="ship-cell-main">{{ $row->order_number ?: 'Sin pedido' }}</div>
                                    <div class="ship-cell-sub">{{ $row->task_number ?: 'Sin task number' }}</div>
                                </td>

                                <td>
                                    <div class="ship-cell-main">{{ $row->vehicle_name ?: 'Unidad no asignada' }}</div>
                                    <div class="ship-cell-sub">{{ $row->vehicle_plate ?: 'Sin placa' }}</div>
                                </td>

                                <td>
                                    <div class="ship-cell-main">{{ $row->driver_name ?: 'Sin chofer' }}</div>
                                    <div class="ship-cell-sub">{{ $row->route_name ?: 'Sin ruta' }}</div>
                                </td>

                                <td>
                                    <div class="ship-metric-line">
                                        <span>{{ number_format((int) $row->loaded_qty) }} / {{ number_format((int) $row->expected_qty) }}</span>
                                        <span>{{ $qtyPct }}%</span>
                                    </div>
                                    <div class="ship-progress">
                                        <span style="width: {{ $qtyPct }}%"></span>
                                    </div>
                                    <div class="ship-mini-note">
                                        Faltantes: {{ number_format((int) $row->missing_qty) }}
                                    </div>
                                </td>

                                <td>
                                    <div class="ship-metric-line">
                                        <span>{{ number_format((int) $row->loaded_boxes) }} / {{ number_format((int) $row->expected_boxes) }}</span>
                                        <span>{{ $boxPct }}%</span>
                                    </div>
                                    <div class="ship-progress">
                                        <span style="width: {{ $boxPct }}%"></span>
                                    </div>
                                    <div class="ship-mini-note">
                                        Faltantes: {{ number_format((int) $row->missing_boxes) }}
                                    </div>
                                </td>

                                <td>
                                    <span class="ship-badge {{ $badgeMap[$status] ?? '' }}">
                                        {{ $statusLabel($status) }}
                                    </span>
                                </td>

                                <td class="ship-col-actions">
                                    <div class="ship-row-actions">
                                        <a href="{{ route('admin.wms.shipping.show', $row->id) }}" class="ship-btn ship-btn-ghost ship-btn-sm">Ver</a>
                                        <a href="{{ route('admin.wms.shipping.scanner', $row->id) }}" class="ship-btn ship-btn-sm">Scanner</a>
                                    </div>
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
            <div class="ship-empty">
                <div class="ship-empty-title">No hay embarques para mostrar</div>
                <div class="ship-empty-sub">
                    Crea embarques desde una tarea de picking y aquí aparecerán con su avance de carga.
                </div>
                <a href="{{ route('admin.wms.picking.v2') }}" class="ship-btn">Ir a Picking</a>
            </div>
        @endif
    </div>
</div>

<style>
.ship-wrap{
    padding:24px;
    display:grid;
    gap:18px;
}
.ship-head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:16px;
    flex-wrap:wrap;
}
.ship-head-left{
    display:flex;
    gap:14px;
    align-items:flex-start;
}
.ship-title{
    font-size:28px;
    font-weight:800;
    line-height:1.05;
    letter-spacing:-.03em;
    color:#0f172a;
}
.ship-sub{
    margin-top:6px;
    color:#64748b;
    max-width:860px;
}
.ship-kpis{
    display:grid;
    grid-template-columns:repeat(6, minmax(0,1fr));
    gap:14px;
}
.ship-kpi{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:18px;
    padding:16px;
    box-shadow:0 10px 30px rgba(15,23,42,.05);
}
.ship-kpi-label{
    font-size:12px;
    font-weight:700;
    color:#64748b;
    text-transform:uppercase;
    letter-spacing:.08em;
}
.ship-kpi-value{
    margin-top:8px;
    font-size:28px;
    font-weight:800;
    color:#0f172a;
}
.ship-kpi-foot{
    margin-top:6px;
    color:#64748b;
    font-size:13px;
}
.ship-filters{
    display:grid;
    grid-template-columns:minmax(0,1.5fr) 240px auto;
    gap:14px;
    align-items:end;
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:18px;
    padding:16px;
    box-shadow:0 10px 30px rgba(15,23,42,.05);
}
.ship-field{
    display:grid;
    gap:8px;
}
.ship-field-grow{
    min-width:0;
}
.ship-label{
    font-size:12px;
    font-weight:700;
    color:#475569;
    text-transform:uppercase;
    letter-spacing:.08em;
}
.ship-input{
    width:100%;
    height:46px;
    border:1px solid #cbd5e1;
    border-radius:12px;
    padding:0 14px;
    outline:none;
    background:#fff;
    color:#0f172a;
}
.ship-input:focus{
    border-color:#0f172a;
    box-shadow:0 0 0 4px rgba(15,23,42,.06);
}
.ship-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}
.ship-btn{
    height:46px;
    border:none;
    border-radius:12px;
    padding:0 16px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    text-decoration:none;
    background:#0f172a;
    color:#fff;
    font-weight:700;
    cursor:pointer;
}
.ship-btn:hover{ opacity:.95; color:#fff; }
.ship-btn-ghost{
    background:#fff;
    border:1px solid #cbd5e1;
    color:#0f172a;
}
.ship-btn-sm{
    height:38px;
    padding:0 12px;
    border-radius:10px;
    font-size:13px;
}
.ship-panel{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:20px;
    box-shadow:0 12px 34px rgba(15,23,42,.06);
    overflow:hidden;
}
.ship-panel-head{
    padding:18px 18px 8px;
}
.ship-panel-title{
    font-size:18px;
    font-weight:800;
    color:#0f172a;
}
.ship-panel-sub{
    margin-top:4px;
    color:#64748b;
}
.ship-table-wrap{
    overflow:auto;
}
.ship-table{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
}
.ship-table th,
.ship-table td{
    text-align:left;
    padding:16px 18px;
    border-top:1px solid #e2e8f0;
    vertical-align:top;
    background:#fff;
}
.ship-table th{
    font-size:12px;
    font-weight:800;
    color:#64748b;
    text-transform:uppercase;
    letter-spacing:.08em;
    white-space:nowrap;
}
.ship-cell-main{
    font-weight:800;
    color:#0f172a;
}
.ship-cell-sub{
    margin-top:4px;
    font-size:13px;
    color:#64748b;
}
.ship-metric-line{
    display:flex;
    justify-content:space-between;
    gap:12px;
    font-size:13px;
    font-weight:700;
    color:#334155;
}
.ship-progress{
    margin-top:8px;
    height:8px;
    background:#e2e8f0;
    border-radius:999px;
    overflow:hidden;
}
.ship-progress > span{
    display:block;
    height:100%;
    border-radius:999px;
    background:linear-gradient(90deg, #0f172a, #334155);
}
.ship-mini-note{
    margin-top:6px;
    font-size:12px;
    color:#64748b;
}
.ship-badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:32px;
    padding:6px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:800;
    border:1px solid transparent;
    white-space:nowrap;
}
.is-draft{ background:#f8fafc; color:#475569; border-color:#cbd5e1; }
.is-loading{ background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
.is-complete{ background:#ecfdf5; color:#047857; border-color:#a7f3d0; }
.is-partial{ background:#fff7ed; color:#c2410c; border-color:#fdba74; }
.is-dispatched{ background:#eef2ff; color:#4338ca; border-color:#c7d2fe; }
.is-cancelled{ background:#fef2f2; color:#b91c1c; border-color:#fecaca; }
.ship-row-actions{
    display:flex;
    gap:8px;
    justify-content:flex-end;
    flex-wrap:wrap;
}
.ship-col-actions{
    width:170px;
}
.ship-pagination{
    padding:18px;
    border-top:1px solid #e2e8f0;
}
.ship-empty{
    padding:40px 24px;
    text-align:center;
    display:grid;
    gap:10px;
}
.ship-empty-title{
    font-size:20px;
    font-weight:800;
    color:#0f172a;
}
.ship-empty-sub{
    color:#64748b;
    max-width:680px;
    margin:0 auto;
}
@media (max-width: 1200px){
    .ship-kpis{ grid-template-columns:repeat(3, minmax(0,1fr)); }
}
@media (max-width: 900px){
    .ship-filters{ grid-template-columns:1fr; }
}
@media (max-width: 768px){
    .ship-wrap{ padding:16px; }
    .ship-kpis{ grid-template-columns:repeat(2, minmax(0,1fr)); }
}
@media (max-width: 560px){
    .ship-kpis{ grid-template-columns:1fr; }
}
</style>
@endsection