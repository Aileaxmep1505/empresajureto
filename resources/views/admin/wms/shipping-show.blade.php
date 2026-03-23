@extends('layouts.app')

@section('title', 'WMS · Detalle de embarque')

@section('content')
@php
    $homeUrl    = \Illuminate\Support\Facades\Route::has('admin.wms.home') ? route('admin.wms.home') : '#';
    $indexUrl   = route('admin.wms.shipping.index');
    $scannerUrl = route('admin.wms.shipping.scanner', $shipment['id']);

    $statusLabel = match((string) ($shipment['status'] ?? 'draft')) {
        'draft' => 'Borrador',
        'loading' => 'Cargando',
        'loaded_complete' => 'Cerrado completo',
        'loaded_partial' => 'Cerrado parcial',
        'dispatched' => 'Despachado',
        'cancelled' => 'Cancelado',
        default => ucfirst((string) ($shipment['status'] ?? 'draft')),
    };

    $statusClass = match((string) ($shipment['status'] ?? 'draft')) {
        'draft' => 'is-draft',
        'loading' => 'is-loading',
        'loaded_complete' => 'is-complete',
        'loaded_partial' => 'is-partial',
        'dispatched' => 'is-dispatched',
        'cancelled' => 'is-cancelled',
        default => 'is-draft',
    };

    $linesByPhase = collect($shipment['lines'] ?? [])->groupBy(function ($line) {
        return (int) ($line['phase'] ?? 1);
    })->sortKeys();

    $qtyPct = max(0, min(100, (int) round(((int) ($shipment['loaded_qty'] ?? 0) / max(1, (int) ($shipment['expected_qty'] ?? 0))) * 100)));
    $boxPct = max(0, min(100, (int) round(((int) ($shipment['loaded_boxes'] ?? 0) / max(1, (int) ($shipment['expected_boxes'] ?? 0))) * 100)));
@endphp

<div class="shipd-wrap">
    <div class="shipd-head">
        <div class="shipd-head-left">
            <a href="{{ $homeUrl }}" class="shipd-btn shipd-btn-ghost">← WMS</a>
            <a href="{{ $indexUrl }}" class="shipd-btn shipd-btn-ghost">Embarques</a>
            <div>
                <div class="shipd-title">{{ $shipment['shipment_number'] ?? 'Embarque' }}</div>
                <div class="shipd-sub">
                    Pedido {{ $shipment['order_number'] ?: 'Sin pedido' }} · Picking {{ $shipment['task_number'] ?: 'Sin task number' }}
                </div>
            </div>
        </div>

        <div class="shipd-head-right">
            <span class="shipd-badge {{ $statusClass }}">{{ $statusLabel }}</span>
            <a href="{{ $scannerUrl }}" class="shipd-btn">Abrir scanner</a>
        </div>
    </div>

    <div class="shipd-kpis">
        <div class="shipd-kpi">
            <div class="shipd-kpi-label">Piezas</div>
            <div class="shipd-kpi-value">{{ number_format((int) ($shipment['loaded_qty'] ?? 0)) }} / {{ number_format((int) ($shipment['expected_qty'] ?? 0)) }}</div>
            <div class="shipd-progress"><span style="width: {{ $qtyPct }}%"></span></div>
        </div>

        <div class="shipd-kpi">
            <div class="shipd-kpi-label">Cajas</div>
            <div class="shipd-kpi-value">{{ number_format((int) ($shipment['loaded_boxes'] ?? 0)) }} / {{ number_format((int) ($shipment['expected_boxes'] ?? 0)) }}</div>
            <div class="shipd-progress"><span style="width: {{ $boxPct }}%"></span></div>
        </div>

        <div class="shipd-kpi">
            <div class="shipd-kpi-label">Faltantes</div>
            <div class="shipd-kpi-value">{{ number_format((int) ($shipment['missing_qty'] ?? 0)) }}</div>
            <div class="shipd-kpi-foot">Piezas pendientes</div>
        </div>

        <div class="shipd-kpi">
            <div class="shipd-kpi-label">Líneas</div>
            <div class="shipd-kpi-value">{{ number_format((int) ($shipment['scanned_lines'] ?? 0)) }} / {{ number_format((int) ($shipment['expected_lines'] ?? 0)) }}</div>
            <div class="shipd-kpi-foot">Con avance de carga</div>
        </div>
    </div>

    <div class="shipd-grid">
        <div class="shipd-panel">
            <div class="shipd-panel-title">Resumen de unidad</div>
            <div class="shipd-info-grid">
                <div class="shipd-info"><span>Unidad</span><strong>{{ $shipment['vehicle_name'] ?: 'Sin unidad' }}</strong></div>
                <div class="shipd-info"><span>Placa</span><strong>{{ $shipment['vehicle_plate'] ?: 'Sin placa' }}</strong></div>
                <div class="shipd-info"><span>Chofer</span><strong>{{ $shipment['driver_name'] ?: 'Sin chofer' }}</strong></div>
                <div class="shipd-info"><span>Teléfono</span><strong>{{ $shipment['driver_phone'] ?: 'Sin teléfono' }}</strong></div>
                <div class="shipd-info"><span>Ruta</span><strong>{{ $shipment['route_name'] ?: 'Sin ruta' }}</strong></div>
                <div class="shipd-info"><span>Operador</span><strong>{{ $shipment['operator_name'] ?: 'Sin operador' }}</strong></div>
                <div class="shipd-info"><span>Inicio carga</span><strong>{{ $shipment['loading_started_at'] ?: '—' }}</strong></div>
                <div class="shipd-info"><span>Cierre carga</span><strong>{{ $shipment['loading_completed_at'] ?: '—' }}</strong></div>
                <div class="shipd-info"><span>Despacho</span><strong>{{ $shipment['dispatched_at'] ?: '—' }}</strong></div>
                <div class="shipd-info"><span>Firma</span><strong>{{ $shipment['signed_by_name'] ?: 'Pendiente' }}</strong></div>
            </div>

            @if(!empty($shipment['notes']))
                <div class="shipd-note">
                    <div class="shipd-note-label">Observaciones</div>
                    <div class="shipd-note-body">{{ $shipment['notes'] }}</div>
                </div>
            @endif
        </div>

        <div class="shipd-panel">
            <div class="shipd-panel-title">Últimos escaneos</div>
            @if(!empty($shipment['scans']))
                <div class="shipd-scans">
                    @foreach(collect($shipment['scans'])->take(20) as $scan)
                        <div class="shipd-scan">
                            <div class="shipd-scan-main">
                                <strong>{{ $scan['scan_value'] }}</strong>
                                <span class="shipd-scan-type">{{ strtoupper($scan['scan_type']) }}</span>
                            </div>
                            <div class="shipd-scan-sub">
                                {{ $scan['message'] ?: 'Sin mensaje' }} · {{ $scan['created_at'] ?: '—' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="shipd-empty">Todavía no hay escaneos registrados.</div>
            @endif
        </div>
    </div>

    <div class="shipd-panel">
        <div class="shipd-panel-title">Líneas por fase</div>

        @forelse($linesByPhase as $phase => $phaseLines)
            <div class="shipd-phase">
                <div class="shipd-phase-head">
                    <div class="shipd-phase-title">Fase {{ $phase }}</div>
                    <div class="shipd-phase-sub">{{ $phaseLines->count() }} líneas</div>
                </div>

                <div class="shipd-lines">
                    @foreach($phaseLines as $line)
                        @php
                            $linePct = max(0, min(100, (int) round(((int) ($line['loaded_qty'] ?? 0) / max(1, (int) ($line['expected_qty'] ?? 0))) * 100)));
                            $lineStatus = (string) ($line['status'] ?? 'pending');
                        @endphp

                        <div class="shipd-line">
                            <div class="shipd-line-top">
                                <div>
                                    <div class="shipd-line-title">{{ $line['product_name'] ?: 'Producto' }}</div>
                                    <div class="shipd-line-sub">
                                        SKU {{ $line['product_sku'] ?: '—' }}
                                        @if(!empty($line['batch_code'])) · Lote {{ $line['batch_code'] }} @endif
                                        @if(!empty($line['location_code'])) · Ubicación {{ $line['location_code'] }} @endif
                                        @if(!empty($line['staging_location_code'])) · Staging {{ $line['staging_location_code'] }} @endif
                                    </div>
                                </div>

                                <span class="shipd-line-badge {{ $lineStatus }}">
                                    {{ ucfirst($lineStatus) }}
                                </span>
                            </div>

                            <div class="shipd-line-stats">
                                <div><span>Piezas</span><strong>{{ number_format((int) ($line['loaded_qty'] ?? 0)) }} / {{ number_format((int) ($line['expected_qty'] ?? 0)) }}</strong></div>
                                <div><span>Cajas</span><strong>{{ number_format((int) ($line['loaded_boxes'] ?? 0)) }} / {{ number_format((int) ($line['expected_boxes'] ?? 0)) }}</strong></div>
                                <div><span>Faltante</span><strong>{{ number_format((int) ($line['missing_qty'] ?? 0)) }}</strong></div>
                                <div><span>Fast Flow</span><strong>{{ !empty($line['is_fastflow']) ? 'Sí' : 'No' }}</strong></div>
                            </div>

                            <div class="shipd-progress shipd-progress-lg">
                                <span style="width: {{ $linePct }}%"></span>
                            </div>

                            @if(!empty($line['loaded_boxes_json']))
                                <div class="shipd-tags">
                                    @foreach($line['loaded_boxes_json'] as $label)
                                        <span>{{ $label }}</span>
                                    @endforeach
                                </div>
                            @endif

                            @if(!empty($line['reason_code']) || !empty($line['reason_note']))
                                <div class="shipd-reason">
                                    <strong>Motivo:</strong>
                                    {{ $line['reason_code'] ?: '—' }}
                                    @if(!empty($line['reason_note']))
                                        · {{ $line['reason_note'] }}
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="shipd-empty">Este embarque no tiene líneas.</div>
        @endforelse
    </div>
</div>

<style>
.shipd-wrap{ padding:24px; display:grid; gap:18px; }
.shipd-head{
    display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;
}
.shipd-head-left{ display:flex; gap:10px; align-items:flex-start; flex-wrap:wrap; }
.shipd-head-right{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.shipd-title{ font-size:28px; font-weight:800; letter-spacing:-.03em; color:#0f172a; }
.shipd-sub{ margin-top:6px; color:#64748b; }
.shipd-btn{
    height:44px; padding:0 16px; border-radius:12px; border:none; background:#0f172a; color:#fff;
    display:inline-flex; align-items:center; justify-content:center; text-decoration:none; font-weight:700;
}
.shipd-btn:hover{ color:#fff; opacity:.95; }
.shipd-btn-ghost{ background:#fff; border:1px solid #cbd5e1; color:#0f172a; }
.shipd-badge{
    min-height:34px; padding:7px 12px; border-radius:999px; font-size:12px; font-weight:800;
    display:inline-flex; align-items:center; justify-content:center; border:1px solid transparent;
}
.is-draft{ background:#f8fafc; color:#475569; border-color:#cbd5e1; }
.is-loading{ background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
.is-complete{ background:#ecfdf5; color:#047857; border-color:#a7f3d0; }
.is-partial{ background:#fff7ed; color:#c2410c; border-color:#fdba74; }
.is-dispatched{ background:#eef2ff; color:#4338ca; border-color:#c7d2fe; }
.is-cancelled{ background:#fef2f2; color:#b91c1c; border-color:#fecaca; }

.shipd-kpis{
    display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px;
}
.shipd-kpi{
    background:#fff; border:1px solid #e2e8f0; border-radius:18px; padding:16px;
    box-shadow:0 10px 30px rgba(15,23,42,.05);
}
.shipd-kpi-label{
    font-size:12px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.08em;
}
.shipd-kpi-value{
    margin-top:8px; font-size:24px; font-weight:800; color:#0f172a;
}
.shipd-kpi-foot{ margin-top:6px; color:#64748b; font-size:13px; }

.shipd-grid{
    display:grid; grid-template-columns:1.25fr .95fr; gap:18px;
}
.shipd-panel{
    background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:18px;
    box-shadow:0 12px 34px rgba(15,23,42,.06);
}
.shipd-panel-title{
    font-size:18px; font-weight:800; color:#0f172a; margin-bottom:14px;
}
.shipd-info-grid{
    display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px;
}
.shipd-info{
    border:1px solid #e2e8f0; border-radius:14px; padding:12px;
    background:#f8fafc; display:grid; gap:6px;
}
.shipd-info span{
    font-size:12px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.08em;
}
.shipd-info strong{
    color:#0f172a; font-size:15px;
}
.shipd-note{
    margin-top:16px; border-top:1px solid #e2e8f0; padding-top:16px;
}
.shipd-note-label{
    font-size:12px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.08em;
}
.shipd-note-body{
    margin-top:8px; color:#334155; line-height:1.6;
}

.shipd-scans{ display:grid; gap:10px; }
.shipd-scan{
    border:1px solid #e2e8f0; border-radius:14px; padding:12px; background:#f8fafc;
}
.shipd-scan-main{ display:flex; justify-content:space-between; gap:10px; align-items:center; color:#0f172a; }
.shipd-scan-type{
    font-size:11px; font-weight:800; color:#475569; background:#fff; border:1px solid #cbd5e1;
    border-radius:999px; padding:4px 8px;
}
.shipd-scan-sub{ margin-top:6px; color:#64748b; font-size:13px; }
.shipd-empty{
    padding:24px; border:1px dashed #cbd5e1; border-radius:16px; text-align:center; color:#64748b;
}

.shipd-phase + .shipd-phase{
    margin-top:20px; padding-top:20px; border-top:1px solid #e2e8f0;
}
.shipd-phase-head{
    display:flex; justify-content:space-between; gap:10px; align-items:center; margin-bottom:12px;
}
.shipd-phase-title{ font-size:17px; font-weight:800; color:#0f172a; }
.shipd-phase-sub{ color:#64748b; }
.shipd-lines{ display:grid; gap:12px; }
.shipd-line{
    border:1px solid #e2e8f0; border-radius:18px; padding:16px; background:#fff;
}
.shipd-line-top{
    display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap;
}
.shipd-line-title{ font-size:16px; font-weight:800; color:#0f172a; }
.shipd-line-sub{ margin-top:5px; color:#64748b; font-size:13px; }
.shipd-line-badge{
    min-height:30px; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:800;
    border:1px solid #cbd5e1; color:#475569; background:#f8fafc;
}
.shipd-line-badge.complete{ background:#ecfdf5; color:#047857; border-color:#a7f3d0; }
.shipd-line-badge.partial{ background:#fff7ed; color:#c2410c; border-color:#fdba74; }
.shipd-line-badge.pending{ background:#f8fafc; color:#475569; border-color:#cbd5e1; }
.shipd-line-stats{
    display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin-top:14px;
}
.shipd-line-stats > div{
    border:1px solid #e2e8f0; border-radius:12px; padding:10px; background:#f8fafc;
}
.shipd-line-stats span{
    display:block; font-size:12px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.08em;
}
.shipd-line-stats strong{
    display:block; margin-top:6px; color:#0f172a; font-size:15px;
}
.shipd-progress{
    height:8px; background:#e2e8f0; border-radius:999px; overflow:hidden;
}
.shipd-progress span{
    display:block; height:100%; border-radius:999px; background:linear-gradient(90deg, #0f172a, #334155);
}
.shipd-progress-lg{ margin-top:14px; height:10px; }
.shipd-tags{
    margin-top:12px; display:flex; gap:8px; flex-wrap:wrap;
}
.shipd-tags span{
    padding:7px 10px; border-radius:999px; background:#eff6ff; color:#1d4ed8; font-size:12px; font-weight:800;
    border:1px solid #bfdbfe;
}
.shipd-reason{
    margin-top:12px; padding:12px; border-radius:12px; background:#fff7ed; color:#9a3412; border:1px solid #fdba74;
}
@media (max-width: 1100px){
    .shipd-kpis{ grid-template-columns:repeat(2,minmax(0,1fr)); }
    .shipd-grid{ grid-template-columns:1fr; }
}
@media (max-width: 768px){
    .shipd-wrap{ padding:16px; }
    .shipd-info-grid,
    .shipd-line-stats{ grid-template-columns:1fr; }
    .shipd-kpis{ grid-template-columns:1fr; }
}
</style>
@endsection