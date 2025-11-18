@extends('layouts.app')

@section('content')
<style>
    /* --- Layout general de la página --- */
    .page-container {
        max-width: 1120px;
        margin: 0 auto;
        padding: 40px 16px;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        color: #111827;
    }

    .page-header {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    @media (min-width: 768px) {
        .page-header {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }
    }

    .page-title {
        font-size: 26px;
        font-weight: 600;
        letter-spacing: -0.02em;
        color: #111827;
        margin: 0;
    }

    .page-subtitle {
        margin-top: 6px;
        font-size: 13px;
        color: #6b7280;
    }

    .badge-counter {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        border: 1px solid #e5e7eb;
        padding: 4px 10px;
        font-size: 11px;
        color: #6b7280;
        background-color: #f9fafb;
    }

    .badge-counter span {
        margin-left: 4px;
        font-weight: 500;
        color: #374151;
    }

    /* --- Botones --- */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        border: 1px solid transparent;
        font-size: 13px;
        font-weight: 500;
        padding: 8px 16px;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
        white-space: nowrap;
    }

    .btn-primary {
        background-color: #4f46e5;
        color: #ffffff;
        box-shadow: 0 8px 20px rgba(79, 70, 229, 0.15);
    }

    .btn-primary:hover {
        background-color: #4338ca;
        border-color: #4338ca;
    }

    .btn-primary:active {
        background-color: #3730a3;
        border-color: #3730a3;
        box-shadow: 0 3px 10px rgba(79, 70, 229, 0.25);
    }

    .btn-outline {
        background-color: #ffffff;
        color: #374151;
        border-color: #e5e7eb;
    }

    .btn-outline:hover {
        background-color: #eef2ff;
        color: #4338ca;
        border-color: #c7d2fe;
    }

    .btn-xs {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 999px;
    }

    .btn-icon-plus {
        font-size: 18px;
        line-height: 1;
        margin-right: 6px;
    }

    /* --- Alertas --- */
    .alert {
        border-radius: 12px;
        padding: 10px 12px;
        font-size: 13px;
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin-top: 16px;
    }

    .alert-success {
        background-color: #ecfdf3;
        border: 1px solid #bbf7d0;
        color: #166534;
    }

    .alert-success-icon {
        margin-top: 2px;
    }

    /* --- Card principal --- */
    .card {
        margin-top: 24px;
        background-color: #ffffff;
        border-radius: 18px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }

    .card-header {
        padding: 12px 18px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    @media (min-width: 640px) {
        .card-header {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }
    }

    .chip {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        color: #6b7280;
        background-color: #f3f4f6;
        border: 1px dashed #e5e7eb;
    }

    .dot {
        width: 7px;
        height: 7px;
        border-radius: 999px;
        margin-right: 6px;
        background-color: #10b981;
    }

    .toolbar-right {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .search-input-wrapper {
        position: relative;
    }

    .search-input {
        width: 220px;
        max-width: 260px;
        border-radius: 999px;
        border: 1px solid #e5e7eb;
        background-color: #f9fafb;
        font-size: 12px;
        color: #6b7280;
        padding: 7px 10px 7px 26px;
        outline: none;
        transition: background-color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
    }

    .search-input:focus {
        background-color: #ffffff;
        border-color: #4f46e5;
        box-shadow: 0 0 0 1px rgba(79, 70, 229, 0.2);
    }

    .search-input[disabled] {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .search-input-icon {
        position: absolute;
        top: 50%;
        left: 8px;
        transform: translateY(-50%);
        font-size: 13px;
        color: #9ca3af;
    }

    /* --- Tabla --- */
    .table-wrapper {
        overflow-x: auto;
    }

    .table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 13px;
    }

    .table thead {
        background-color: rgba(249, 250, 251, 0.94);
    }

    .table thead th {
        padding: 10px 20px;
        text-align: left;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 600;
        color: #6b7280;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
    }

    .table tbody tr {
        transition: background-color 0.15s ease;
    }

    .table tbody tr:hover {
        background-color: #f9fafb;
    }

    .table tbody td {
        padding: 14px 20px;
        border-top: 1px solid #f3f4f6;
        vertical-align: top;
    }

    .licitacion-main {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .licitacion-header {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .avatar-circle {
        width: 26px;
        height: 26px;
        border-radius: 999px;
        background-color: #eef2ff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 600;
        color: #4f46e5;
    }

    .licitacion-title {
        font-weight: 500;
        color: #111827;
        max-width: 360px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .licitacion-description {
        padding-left: 34px;
        font-size: 11px;
        color: #6b7280;
        max-width: 380px;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        overflow: hidden;
    }

    .text-muted {
        font-size: 11px;
        color: #9ca3af;
        margin-top: 2px;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 500;
        padding: 3px 9px;
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .status-modalidad-presencial {
        background-color: #eff6ff;
        color: #1d4ed8;
        border-color: #dbeafe;
    }

    .status-modalidad-linea {
        background-color: #ecfdf3;
        color: #047857;
        border-color: #bbf7d0;
    }

    .status-dot {
        width: 6px;
        height: 6px;
        border-radius: 999px;
        margin-right: 5px;
    }

    .status-dot-blue {
        background-color: #3b82f6;
    }

    .status-dot-green {
        background-color: #10b981;
    }

    .status-general-borrador {
        background-color: #fffbeb;
        color: #92400e;
        border-color: #fde68a;
    }

    .status-general-proceso {
        background-color: #eef2ff;
        color: #4338ca;
        border-color: #c7d2fe;
    }

    .status-general-cerrado {
        background-color: #f3f4f6;
        color: #4b5563;
        border-color: #d1d5db;
    }

    .progress-container {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .progress-header {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        color: #6b7280;
    }

    .progress-bar-bg {
        width: 100%;
        height: 6px;
        border-radius: 999px;
        background-color: #f3f4f6;
        overflow: hidden;
    }

    .progress-bar-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #4f46e5, #0ea5e9, #10b981);
        transition: width 0.18s ease-out;
    }

    .actions-cell {
        text-align: right;
    }

    /* --- Estado vacío --- */
    .empty-state {
        padding: 40px 20px;
        text-align: center;
        color: #9ca3af;
    }

    .empty-icon {
        width: 40px;
        height: 40px;
        border-radius: 999px;
        border: 1px dashed #d1d5db;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        margin-bottom: 10px;
        background-color: #f9fafb;
    }

    .empty-title {
        font-size: 13px;
        margin-bottom: 4px;
        color: #6b7280;
    }

    /* --- Paginación wrapper --- */
    .card-footer {
        padding: 10px 18px 12px 18px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        flex-direction: column;
        gap: 8px;
        font-size: 11px;
        color: #6b7280;
    }

    @media (min-width: 640px) {
        .card-footer {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }
    }

    .pagination-wrapper {
        text-align: right;
    }
</style>

<div class="page-container">

    {{-- Encabezado --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Licitaciones</h1>
            <p class="page-subtitle">
                Administra el ciclo completo de tus licitaciones: convocatoria, aclaraciones, fallo y seguimiento.
            </p>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            @if(method_exists($licitaciones, 'total'))
                <span class="badge-counter">
                    Total: <span>{{ $licitaciones->total() }}</span>
                </span>
            @endif
            <a href="{{ route('licitaciones.create.step1') }}" class="btn btn-primary">
                <span class="btn-icon-plus">＋</span>
                Nueva licitación
            </a>
        </div>
    </div>

    {{-- Alerta de éxito --}}
    @if(session('success'))
        <div class="alert alert-success">
            <span class="alert-success-icon">✅</span>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    {{-- Card principal --}}
    <div class="card">

        {{-- Toolbar superior --}}
        <div class="card-header">
            <div>
                <span class="chip">
                    <span class="dot"></span>
                    Flujo activo de licitaciones
                </span>
            </div>

            <div class="toolbar-right">
                <div class="search-input-wrapper">
                    <input
                        type="text"
                        class="search-input"
                        placeholder="Buscar (visual, aún no funcional)"
                        disabled
                    >
                    <span class="search-input-icon">🔍</span>
                </div>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Licitación</th>
                        <th>Convocatoria</th>
                        <th>Modalidad</th>
                        <th>Estatus</th>
                        <th>Progreso</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($licitaciones as $licitacion)
                        @php
                            $totalSteps = 12;
                            $step = $licitacion->current_step ?? 0;
                            $progress = $totalSteps > 0 ? min(100, max(0, ($step / $totalSteps) * 100)) : 0;
                            $status = $licitacion->estatus;
                            $statusLabel = ucfirst(str_replace('_', ' ', $status));
                            $statusClass = '';

                            if ($status === 'borrador') {
                                $statusClass = 'status-general-borrador';
                            } elseif ($status === 'en_proceso') {
                                $statusClass = 'status-general-proceso';
                            } elseif ($status === 'cerrado') {
                                $statusClass = 'status-general-cerrado';
                            } else {
                                $statusClass = '';
                            }
                        @endphp
                        <tr>
                            {{-- Licitación --}}
                            <td>
                                <div class="licitacion-main">
                                    <div class="licitacion-header">
                                        <span class="avatar-circle">
                                            {{ strtoupper(substr($licitacion->titulo, 0, 2)) }}
                                        </span>
                                        <div class="licitacion-title" title="{{ $licitacion->titulo }}">
                                            {{ $licitacion->titulo }}
                                        </div>
                                    </div>
                                    <div class="licitacion-description">
                                        {{ $licitacion->descripcion }}
                                    </div>
                                </div>
                            </td>

                            {{-- Convocatoria --}}
                            <td>
                                @if($licitacion->fecha_convocatoria)
                                    <div style="font-size: 13px; font-weight: 500; color: #111827;">
                                        {{ $licitacion->fecha_convocatoria->format('d/m/Y') }}
                                    </div>
                                    <div class="text-muted">
                                        {{ $licitacion->fecha_convocatoria->diffForHumans() }}
                                    </div>
                                @else
                                    <span class="text-muted">Sin fecha</span>
                                @endif
                            </td>

                            {{-- Modalidad --}}
                            <td>
                                @php
                                    $isPresencial = $licitacion->modalidad === 'presencial';
                                @endphp
                                <span class="status-pill {{ $isPresencial ? 'status-modalidad-presencial' : 'status-modalidad-linea' }}">
                                    <span class="status-dot {{ $isPresencial ? 'status-dot-blue' : 'status-dot-green' }}"></span>
                                    {{ $licitacion->modalidad === 'en_linea' ? 'En línea' : 'Presencial' }}
                                </span>
                            </td>

                            {{-- Estatus --}}
                            <td>
                                <span class="status-pill {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            {{-- Progreso --}}
                            <td>
                                <div class="progress-container">
                                    <div class="progress-header">
                                        <span>Paso {{ $step ?: '—' }} / {{ $totalSteps }}</span>
                                        <span>{{ number_format($progress, 0) }}%</span>
                                    </div>
                                    <div class="progress-bar-bg">
                                        <div class="progress-bar-fill" style="width: {{ $progress }}%;"></div>
                                    </div>
                                </div>
                            </td>

                            {{-- Acciones --}}
                            <td class="actions-cell">
                                <a href="{{ route('licitaciones.show', $licitacion) }}" class="btn btn-outline btn-xs">
                                    Ver detalle
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-icon">📄</div>
                                    <div class="empty-title">Aún no hay licitaciones registradas.</div>
                                    <a href="{{ route('licitaciones.create.step1') }}" class="btn btn-primary btn-xs" style="margin-top: 8px;">
                                        Crear la primera licitación
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer con paginación --}}
        <div class="card-footer">
            <div>
                @if(method_exists($licitaciones, 'firstItem') && $licitaciones->total() > 0)
                    Mostrando
                    <span style="font-weight: 500; color: #374151;">{{ $licitaciones->firstItem() }}</span>
                    –
                    <span style="font-weight: 500; color: #374151;">{{ $licitaciones->lastItem() }}</span>
                    de
                    <span style="font-weight: 500; color: #374151;">{{ $licitaciones->total() }}</span>
                    licitación(es)
                @endif
            </div>
            <div class="pagination-wrapper">
                {{ $licitaciones->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
