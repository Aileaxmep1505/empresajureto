@extends('layouts.app')

@section('title', 'Detalle licitación AI #'.$licitacionFile->id)

@section('content')
<div class="page-licitaciones-show">
    <style>
        .page-licitaciones-show {
            max-width: 1100px;
            margin: 20px auto;
            padding: 0 16px 40px;
        }
        .top-actions {
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:12px;
            margin-bottom:16px;
        }
        .link-simple {
            color:#2563eb;
            text-decoration:none;
            font-size:0.85rem;
        }
        .link-simple:hover {
            text-decoration:underline;
        }
        .badge {
            display:inline-block;
            padding:3px 10px;
            border-radius:999px;
            font-size:0.7rem;
        }
        .badge-pendiente { background:#fef3c7; color:#92400e; }
        .badge-procesando { background:#dbeafe; color:#1d4ed8; }
        .badge-procesado { background:#dcfce7; color:#166534; }
        .badge-error { background:#fee2e2; color:#b91c1c; }
        .info-card {
            background:#f6f8ff;
            border-radius:18px;
            padding:14px 16px;
            border:1px solid #d6def0;
            margin-bottom:18px;
        }
        .info-row {
            display:flex;
            flex-wrap:wrap;
            gap:16px;
            font-size:0.85rem;
        }
        .info-row div span {
            display:block;
            color:#6b7280;
            font-size:0.78rem;
        }
        .table-card {
            background:#f6f8ff;
            border-radius:18px;
            padding:16px;
            border:1px solid #d6def0;
        }
        .table-card h2 {
            font-size:1.1rem;
            font-weight:600;
            margin-bottom:10px;
        }
        .table-card table {
            width:100%;
            border-collapse:collapse;
            font-size:0.8rem;
        }
        .table-card th,
        .table-card td {
            padding:6px 8px;
            border-bottom:1px solid #e3e7f3;
            vertical-align:top;
        }
        .table-card th {
            background:#e9eef6;
            font-weight:600;
        }
        .tag-req {
            display:inline-block;
            padding:2px 8px;
            border-radius:999px;
            background:#e0f2fe;
            color:#0369a1;
            font-size:0.7rem;
        }
    </style>

    <div class="top-actions">
        <div>
            <a href="{{ route('licitaciones-ai.index') }}" class="link-simple">&larr; Volver al listado AI</a>
            <h1 style="font-size:1.4rem;font-weight:600;margin-top:6px;">
                Archivo AI #{{ $licitacionFile->id }} — {{ $licitacionFile->nombre_original }}
            </h1>
        </div>
        <div>
            <a href="{{ route('licitaciones-ai.tabla-global') }}" class="link-simple">
                Ver tabla global
            </a>
        </div>
    </div>

    <div class="info-card">
        <div class="info-row">
            <div>
                <span>Estado</span>
                @php
                    $estado = $licitacionFile->estado;
                    $clase = match($estado) {
                        'pendiente'  => 'badge-pendiente',
                        'procesando' => 'badge-procesando',
                        'procesado'  => 'badge-procesado',
                        'error'      => 'badge-error',
                        default      => 'badge-pendiente',
                    };
                @endphp
                <span class="badge {{ $clase }}">{{ ucfirst($estado) }}</span>
            </div>
            <div>
                <span>Total items extraídos</span>
                {{ $licitacionFile->total_items }}
            </div>
            <div>
                <span>Creado</span>
                {{ $licitacionFile->created_at?->format('d/m/Y H:i') }}
            </div>
            <div>
                <span>Actualizado</span>
                {{ $licitacionFile->updated_at?->format('d/m/Y H:i') }}
            </div>
        </div>

        @if($licitacionFile->error_mensaje)
            <div style="margin-top:8px;font-size:0.8rem;color:#b91c1c;">
                Error: {{ $licitacionFile->error_mensaje }}
            </div>
        @endif
    </div>

    <div class="table-card">
        <h2>Items extraídos del archivo (AI)</h2>
        <table>
            <thead>
                <tr>
                    <th>Requisición</th>
                    <th>Partida</th>
                    <th>Clave verificación</th>
                    <th>Descripción / Especificaciones</th>
                    <th>Cantidad</th>
                    <th>Unidad</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>
                        <span class="tag-req">{{ $item->requisicion }}</span>
                    </td>
                    <td>{{ $item->partida }}</td>
                    <td>{{ $item->clave_verificacion }}</td>
                    <td>
                        <strong>{{ $item->descripcion_bien }}</strong><br>
                        <span style="font-size:0.75rem;color:#6b7280;">
                            {{ $item->especificaciones }}
                        </span>
                    </td>
                    <td>{{ $item->cantidad }}</td>
                    <td>{{ $item->unidad_medida }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="padding:16px;text-align:center;color:#6b7280;">
                        No se encontraron items para este archivo en el módulo AI.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
