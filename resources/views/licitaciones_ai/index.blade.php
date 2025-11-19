@extends('layouts.app')

@section('title', 'Licitaciones AI')

@section('content')
<div class="la-page">
    <style>
        /* ====== Layout general ====== */
        .la-page {
            max-width: 1200px;
            margin: 18px auto 40px;
            padding: 0 16px;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        /* ====== Animaciones ====== */
        @keyframes la-fade-in-up {
            from {
                opacity: 0;
                transform: translateY(6px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes la-soft-pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.20);
            }
            70% {
                box-shadow: 0 0 0 12px rgba(37, 99, 235, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(37, 99, 235, 0);
            }
        }

        /* ====== Cabecera ====== */
        .la-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 18px;
            animation: la-fade-in-up 0.35s ease-out both;
        }

        .la-header-title {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .la-title-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .la-title {
            font-size: 1.55rem;
            font-weight: 650;
            color: #0f172a;
        }

        .la-badge-ai {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 0.75rem;
            background: #e0f2fe;
            color: #0369a1;
        }

        .la-badge-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #0ea5e9;
        }

        .la-subtitle {
            font-size: 0.88rem;
            color: #64748b;
            max-width: 520px;
        }

        .la-header-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }

        /* ====== Botones ====== */
        .la-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 999px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: #ffffff;
            font-size: 0.88rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.12s ease-out, box-shadow 0.12s ease-out, opacity 0.12s ease-out;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.25);
            animation: la-fade-in-up 0.35s ease-out both, la-soft-pulse 1.8s ease-out 0.4s 2;
        }

        .la-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.32);
            opacity: 0.96;
        }

        .la-btn-primary-icon {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .la-top-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.82rem;
            color: #2563eb;
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.05);
            transition: background 0.12s ease-out, transform 0.12s ease-out;
        }

        .la-top-link:hover {
            background: rgba(37, 99, 235, 0.12);
            transform: translateY(-1px);
        }

        .la-top-link span.icon {
            font-size: 0.85rem;
        }

        /* ====== Alertas ====== */
        .la-alert {
            margin-bottom: 12px;
            padding: 9px 12px;
            border-radius: 12px;
            font-size: 0.82rem;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            animation: la-fade-in-up 0.25s ease-out both;
        }

        .la-alert-success {
            background: #ecfdf3;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .la-alert-icon {
            margin-top: 2px;
            font-size: 1rem;
        }

        /* ====== Tarjeta principal y toolbar ====== */
        .la-toolbar-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
            animation: la-fade-in-up 0.35s ease-out;
        }

        .la-toolbar-left {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .la-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.78rem;
            background: #eff4ff;
            color: #1d4ed8;
        }

        .la-chip-dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: #22c55e;
        }

        .la-toolbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .la-search {
            position: relative;
        }

        .la-search input {
            height: 32px;
            padding: 0 9px 0 26px;
            border-radius: 999px;
            border: 1px solid #d0d7e7;
            background: #f9fafb;
            font-size: 0.8rem;
            color: #111827;
            outline: none;
            transition: border 0.12s ease-out, box-shadow 0.12s ease-out, background 0.12s ease-out;
        }

        .la-search input:focus {
            border-color: #2563eb;
            background: #ffffff;
            box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.35);
        }

        .la-search-icon {
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.75rem;
            color: #94a3b8;
        }

        /* ====== Tarjeta tabla ====== */
        .la-card {
            background: #f6f8ff;
            border-radius: 22px;
            padding: 14px 14px 6px;
            border: 1px solid #d6def0;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
            animation: la-fade-in-up 0.38s ease-out;
        }

        .la-table-wrapper {
            border-radius: 16px;
            overflow: hidden;
        }

        .la-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }

        .la-table thead {
            background: #e9eef6;
        }

        .la-table th,
        .la-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e0e7ff;
            text-align: left;
        }

        .la-table th {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .la-table tr:last-child td {
            border-bottom: none;
        }

        .la-table tbody tr {
            background: #ffffff;
            transition: background 0.12s ease-out, transform 0.08s ease-out, box-shadow 0.08s ease-out;
        }

        .la-table tbody tr:hover {
            background: #eef2ff;
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(148, 163, 184, 0.4);
        }

        .la-file-name {
            font-weight: 500;
            color: #0f172a;
        }

        .la-file-meta {
            font-size: 0.72rem;
            color: #6b7280;
        }

        .la-col-actions {
            text-align: right;
        }

        .la-link-row {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.8rem;
            color: #2563eb;
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.04);
            transition: background 0.12s ease-out, transform 0.12s ease-out;
        }

        .la-link-row:hover {
            background: rgba(37, 99, 235, 0.14);
            transform: translateY(-1px);
        }

        .la-link-row .icon {
            font-size: 0.86rem;
        }

        /* ====== Badges de estado ====== */
        .la-badge-state {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 500;
        }

        .la-badge-dot-sm {
            width: 7px;
            height: 7px;
            border-radius: 999px;
        }

        .la-badge-pendiente {
            background: #fef3c7;
            color: #92400e;
        }
        .la-badge-pendiente .la-badge-dot-sm {
            background: #facc15;
        }

        .la-badge-procesando {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .la-badge-procesando .la-badge-dot-sm {
            background: #2563eb;
        }

        .la-badge-procesado {
            background: #dcfce7;
            color: #166534;
        }
        .la-badge-procesado .la-badge-dot-sm {
            background: #22c55e;
        }

        .la-badge-error {
            background: #fee2e2;
            color: #b91c1c;
        }
        .la-badge-error .la-badge-dot-sm {
            background: #ef4444;
        }

        /* ====== Paginación ====== */
        .la-pagination-wrapper {
            margin: 12px 4px 4px;
            font-size: 0.8rem;
        }

        /* ====== Estado vacío ====== */
        .la-empty {
            padding: 26px 10px 28px;
            text-align: center;
            font-size: 0.86rem;
            color: #64748b;
        }

        .la-empty-icon {
            width: 44px;
            height: 44px;
            margin: 0 auto 8px;
            border-radius: 16px;
            background: #e0e7ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #4f46e5;
            animation: la-fade-in-up 0.4s ease-out;
        }

        .la-empty strong {
            display: block;
            margin-bottom: 4px;
            color: #0f172a;
        }

        .la-empty-sub {
            font-size: 0.78rem;
        }

        .la-empty-cta {
            margin-top: 10px;
        }

        /* ====== Responsivo ====== */
        @media (max-width: 768px) {
            .la-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .la-header-actions {
                width: 100%;
                align-items: flex-start;
            }
            .la-header-actions .la-btn-primary {
                width: 100%;
                justify-content: center;
            }
            .la-toolbar-row {
                flex-direction: column;
                align-items: flex-start;
            }
            .la-toolbar-right {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>

    {{-- Alert de éxito --}}
    @if (session('success'))
        <div class="la-alert la-alert-success">
            <div class="la-alert-icon">✅</div>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    {{-- Header --}}
    <div class="la-header">
        <div class="la-header-title">
            <div class="la-title-row">
                <h1 class="la-title">Licitaciones AI</h1>
                <span class="la-badge-ai">
                    <span class="la-badge-dot"></span>
                    Módulo inteligente
                </span>
            </div>
            <p class="la-subtitle">
                Sube tus archivos de licitación en PDF o Word, deja que la IA extraiga las partidas
                y consulta la tabla global para capturar MARCA y MODELO.
            </p>
        </div>

        <div class="la-header-actions">
            <a href="{{ route('licitaciones-ai.create') }}" class="la-btn-primary">
                <span class="la-btn-primary-icon">＋</span>
                <span>Nueva licitación AI</span>
            </a>

            <a href="{{ route('licitaciones-ai.tabla-global') }}" class="la-top-link">
                <span class="icon">📊</span>
                <span>Ver tabla global de ítems</span>
            </a>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="la-toolbar-row">
        <div class="la-toolbar-left">
            <div class="la-chip">
                <span class="la-chip-dot"></span>
                {{ $licitaciones->total() }} archivo(s) procesados
            </div>
        </div>

        <div class="la-toolbar-right">
            <div class="la-search">
                {{-- Sólo UI por ahora, sin lógica de backend --}}
                <span class="la-search-icon">🔍</span>
                <input type="text" placeholder="Buscar por nombre..." disabled>
            </div>
        </div>
    </div>

    {{-- Tabla principal --}}
    <div class="la-card">
        <div class="la-table-wrapper">
            <table class="la-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Archivo</th>
                        <th style="width: 140px;">Estado</th>
                        <th style="width: 110px;">Total ítems</th>
                        <th style="width: 160px;">Fecha</th>
                        <th class="la-col-actions" style="width: 120px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($licitaciones as $archivo)
                    <tr>
                        <td>#{{ $archivo->id }}</td>
                        <td>
                            <div class="la-file-name">{{ $archivo->nombre_original }}</div>
                            <div class="la-file-meta">
                                {{ $archivo->mime_type ?? '—' }}
                            </div>
                        </td>
                        <td>
                            @php
                                $estado = $archivo->estado;
                                $clase = match($estado) {
                                    'pendiente'  => 'la-badge-pendiente',
                                    'procesando' => 'la-badge-procesando',
                                    'procesado'  => 'la-badge-procesado',
                                    'error'      => 'la-badge-error',
                                    default      => 'la-badge-pendiente',
                                };
                            @endphp
                            <span class="la-badge-state {{ $clase }}">
                                <span class="la-badge-dot-sm"></span>
                                {{ ucfirst($estado) }}
                            </span>
                            @if ($estado === 'error' && $archivo->error_mensaje)
                                <div class="la-file-meta" style="color:#b91c1c; margin-top:2px;">
                                    {{ \Illuminate\Support\Str::limit($archivo->error_mensaje, 55) }}
                                </div>
                            @endif
                        </td>
                        <td>{{ $archivo->total_items }}</td>
                        <td>
                            {{ $archivo->created_at?->format('d/m/Y H:i') }}<br>
                            <span class="la-file-meta">
                                Actualizado: {{ $archivo->updated_at?->format('d/m/Y H:i') }}
                            </span>
                        </td>
                        <td class="la-col-actions">
                            <a href="{{ route('licitaciones-ai.show', $archivo) }}" class="la-link-row">
                                <span class="icon">👁</span>
                                <span>Ver detalle</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="la-empty">
                                <div class="la-empty-icon">📄</div>
                                <strong>No hay licitaciones en el módulo AI todavía.</strong>
                                <div class="la-empty-sub">
                                    Sube tu primer archivo para comenzar a extraer las partidas automáticamente.
                                </div>
                                <div class="la-empty-cta">
                                    <a href="{{ route('licitaciones-ai.create') }}" class="la-btn-primary" style="padding:7px 14px; box-shadow:none; animation:none;">
                                        <span class="la-btn-primary-icon">＋</span>
                                        <span>Subir primera licitación</span>
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        @if ($licitaciones->hasPages())
            <div class="la-pagination-wrapper">
                {{ $licitaciones->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
