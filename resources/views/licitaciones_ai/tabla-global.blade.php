@extends('layouts.app')

@section('title', 'Tabla global AI')

@section('content')
<div class="page-licitaciones-global">
    <style>
        .page-licitaciones-global{
            max-width: 1180px;
            margin: 20px auto 40px;
            padding: 0 16px;
        }
        .lg-top-bar{
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:12px;
            margin-bottom:16px;
        }
        .lg-link-back{
            color:#2563eb;
            text-decoration:none;
            font-size:0.85rem;
        }
        .lg-link-back:hover{ text-decoration:underline; }

        .lg-title{
            font-size:1.35rem;
            font-weight:600;
            margin-top:4px;
        }

        .lg-pill-info{
            font-size:0.8rem;
            color:#6b7280;
            background:#eef2ff;
            padding:6px 12px;
            border-radius:999px;
        }

        .lg-card{
            background:#f6f8ff;
            border-radius:18px;
            padding:18px 16px 10px;
            border:1px solid #d6def0;
        }

        .lg-card-header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            flex-wrap:wrap;
            gap:12px;
            margin-bottom:10px;
        }

        .lg-card-header h2{
            font-size:1.05rem;
            font-weight:600;
        }

        .lg-meta{
            font-size:0.8rem;
            color:#6b7280;
        }

        /* Botones cabecera */
        .btn-regenerar-global,
        .btn-excel-global{
            border:none;
            border-radius:999px;
            padding:6px 14px;
            font-size:0.8rem;
            display:inline-flex;
            align-items:center;
            gap:6px;
            cursor:pointer;
            color:#fff;
            box-shadow:0 6px 14px rgba(15,118,110,0.25);
            transition:transform .15s ease, box-shadow .15s ease, filter .15s ease;
            text-decoration:none;
        }
        .btn-regenerar-global svg,
        .btn-excel-global svg{
            width:14px;
            height:14px;
        }
        .btn-regenerar-global{
            background:linear-gradient(135deg,#0ea5e9,#6366f1);
        }
        .btn-excel-global{
            background:linear-gradient(135deg,#16a34a,#22c55e);
            box-shadow:0 6px 14px rgba(22,163,74,0.25);
        }
        .btn-regenerar-global:hover,
        .btn-excel-global:hover{
            transform:translateY(-1px);
            filter:brightness(1.05);
            box-shadow:0 10px 20px rgba(37,99,235,0.28);
        }
        .btn-regenerar-global:active,
        .btn-excel-global:active{
            transform:scale(.97) translateY(0);
            box-shadow:0 3px 8px rgba(37,99,235,0.25);
        }

        /* Buscador píldora */
        .lg-search-pill{
            display:flex;
            align-items:center;
            gap:6px;
            padding:6px 12px;
            border-radius:999px;
            background:#ffffff;
            border:1px solid #d4d4d8;
            font-size:0.78rem;
            min-width:230px;
        }
        .lg-search-pill svg{
            width:14px;
            height:14px;
            color:#9ca3af;
            flex-shrink:0;
        }
        .lg-search-input{
            border:none;
            outline:none;
            font-size:0.78rem;
            background:transparent;
            width:100%;
            color:#111827;
        }
        .lg-search-input::placeholder{
            color:#9ca3af;
        }

        /* Tabla */
        .lg-table{
            width:100%;
            border-collapse:collapse;
            font-size:0.78rem;
            margin-top:8px;
        }
        .lg-table thead th{
            background:#e9eef6;
            padding:8px 10px;
            text-align:left;
            font-weight:600;
            border-bottom:1px solid #d6def0;
            white-space:nowrap;
        }
        .lg-table tbody tr:nth-child(odd){
            background:#f9fbff;
        }
        .lg-table tbody tr:nth-child(even){
            background:#ffffff;
        }
        .lg-table td{
            padding:9px 10px;
            vertical-align:top;
            border-bottom:1px solid #e3e7f3;
        }

        .lg-tag-req{
            display:inline-block;
            padding:2px 8px;
            border-radius:999px;
            background:#e0f2fe;
            color:#0369a1;
            font-size:0.7rem;
            margin-right:4px;
            margin-bottom:4px;
        }

        .lg-clave{
            font-weight:700;
            letter-spacing:.02em;
        }

        .lg-desc-ttl{
            font-weight:600;
            font-size:0.8rem;
            margin-bottom:2px;
        }
        .lg-desc-body{
            font-size:0.75rem;
            color:#6b7280;
            line-height:1.35;
        }

        .lg-mm-wrap{
            display:flex;
            flex-direction:column;
            gap:6px;
            align-items:flex-start;
        }
        .lg-mm-inputs{
            display:flex;
            gap:6px;
            flex-wrap:wrap;
        }
        .lg-mm-field{
            border-radius:999px;
            border:1px solid #d4d4d8;
            padding:5px 10px;
            font-size:0.75rem;
            min-width:110px;
            outline:none;
            background:#fdfdfd;
        }
        .lg-mm-field:focus{
            border-color:#4f46e5;
            box-shadow:0 0 0 1px rgba(79,70,229,0.18);
        }

        /* Botón guardar (sin emojis) */
        .btn-save{
            border:none;
            border-radius:999px;
            padding:6px 14px;
            font-size:0.78rem;
            display:inline-flex;
            align-items:center;
            gap:6px;
            cursor:pointer;
            background:linear-gradient(135deg,#22c55e,#16a34a);
            color:#fff;
            box-shadow:0 6px 14px rgba(22,163,74,0.25);
            transition:transform .15s ease, box-shadow .15s ease, filter .15s ease;
        }
        .btn-save:hover{
            transform:translateY(-1px);
            filter:brightness(1.06);
            box-shadow:0 10px 22px rgba(22,163,74,0.3);
        }
        .btn-save:active{
            transform:scale(.97) translateY(0);
            box-shadow:0 4px 10px rgba(22,163,74,0.3);
        }
        .btn-save svg{
            width:14px;
            height:14px;
        }

        .lg-small-muted{
            font-size:0.7rem;
            color:#9ca3af;
        }

        @media (max-width:900px){
            .lg-table thead{
                display:none;
            }
            .lg-table, .lg-table tbody, .lg-table tr, .lg-table td{
                display:block;
                width:100%;
            }
            .lg-table tr{
                margin-bottom:12px;
                border-radius:14px;
                overflow:hidden;
                box-shadow:0 4px 12px rgba(15,23,42,.04);
            }
            .lg-table td{
                border-bottom:1px solid #edf2ff;
            }
        }
    </style>

    <div class="lg-top-bar">
        <div>
            <a href="{{ route('licitaciones-ai.index') }}" class="lg-link-back">&larr; Volver al listado AI</a>
            <h1 class="lg-title">Items globales consolidados</h1>
        </div>
        <div class="lg-pill-info">
            Fuente: todos los items originales procesados por IA
        </div>
    </div>

    <div class="lg-card">
        <div class="lg-card-header">
            <div class="lg-meta">
                Total registros: <strong id="lg-total-count">{{ $itemsGlobales->count() }}</strong>
            </div>

            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                @if (session('success'))
                    <span class="lg-small-muted">{{ session('success') }}</span>
                @endif

                {{-- Buscador píldora --}}
                <div class="lg-search-pill">
                    <svg viewBox="0 0 20 20" fill="none">
                        <circle cx="9" cy="9" r="4.5" stroke="currentColor" stroke-width="1.4" />
                        <line x1="12.5" y1="12.5" x2="16" y2="16" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
                    </svg>
                    <input
                        id="lg-search-input"
                        type="text"
                        class="lg-search-input"
                        placeholder="Buscar en clave, requisición, descripción, marca..."
                    >
                </div>

                {{-- Botón Excel global --}}
                <a href="{{ route('licitaciones-ai.tabla-global.excel') }}" class="btn-excel-global">
                    <svg viewBox="0 0 20 20" fill="none">
                        <path d="M10 3.5v8.25m0 0L6.75 8.5M10 11.75l3.25-3.25M4.5 13.5v1.25A1.75 1.75 0 006.25 16.5h7.5A1.75 1.75 0 0015.5 14.75V13.5"
                              stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Descargar Excel global</span>
                </a>

                {{-- Botón regenerar tabla global --}}
                <form method="POST" action="{{ route('licitaciones-ai.tabla-global.regenerar') }}">
                    @csrf
                    <button type="submit" class="btn-regenerar-global">
                        <svg viewBox="0 0 20 20" fill="none">
                            <path d="M4.5 4.5v4h4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M4.5 8.5A5.5 5.5 0 0115 6.5L16.5 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M15.5 15.5v-4h-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M15.5 11.5A5.5 5.5 0 015 13.5L3.5 15" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Regenerar tabla global</span>
                    </button>
                </form>
            </div>
        </div>

        <table class="lg-table" id="lg-table">
            <thead>
                <tr>
                    <th>Clave verificación</th>
                    <th>Requisiciones</th>
                    <th>Partida(s)</th>
                    <th>Descripción / Especificaciones</th>
                    <th>Cantidad total</th>
                    <th>Unidad</th>
                    <th>Marca / Modelo</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($itemsGlobales as $item)
                @php
                    $requisiciones = is_array($item->requisiciones)
                        ? $item->requisiciones
                        : (array) json_decode($item->requisiciones ?? '[]', true);

                    $partidas = method_exists($item, 'itemsOriginales')
                        ? $item->itemsOriginales->pluck('partida')->filter()->unique()->values()->all()
                        : [];
                @endphp
                <tr>
                    <td class="lg-clave">
                        {{ $item->clave_verificacion ?: 'SIN-CLAVE' }}
                    </td>

                    <td>
                        @forelse($requisiciones as $req)
                            <span class="lg-tag-req">{{ $req }}</span>
                        @empty
                            <span class="lg-small-muted">Sin requisición</span>
                        @endforelse
                    </td>

                    <td>
                        @if(!empty($partidas))
                            <span>{{ implode(', ', $partidas) }}</span>
                        @else
                            <span class="lg-small-muted">Sin partida</span>
                        @endif
                    </td>

                    <td>
                        <div class="lg-desc-ttl">{{ $item->descripcion_global }}</div>
                        @if($item->especificaciones_global)
                            <div class="lg-desc-body">{{ $item->especificaciones_global }}</div>
                        @endif
                    </td>

                    <td>{{ number_format((int) $item->cantidad_total, 0) }}</td>
                    <td>{{ $item->unidad_medida }}</td>

                    <td>
                        <form method="POST" action="{{ route('licitaciones-ai.tabla-global.update', $item) }}" class="lg-mm-wrap">
                            @csrf
                            <div class="lg-mm-inputs">
                                <input
                                    type="text"
                                    name="marca"
                                    value="{{ old('marca', $item->marca) }}"
                                    placeholder="Marca"
                                    class="lg-mm-field"
                                >
                                <input
                                    type="text"
                                    name="modelo"
                                    value="{{ old('modelo', $item->modelo) }}"
                                    placeholder="Modelo"
                                    class="lg-mm-field"
                                >
                            </div>
                            <button type="submit" class="btn-save">
                                <svg viewBox="0 0 20 20" fill="none">
                                    <path d="M5 3.5h8.5L16.5 6.5v10H5z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                                    <path d="M7 3.5v4h5V3.5" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                                    <rect x="7" y="11" width="6" height="4.5" rx="0.8" stroke="currentColor" stroke-width="1.4"/>
                                </svg>
                                <span>Guardar</span>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="padding:16px;text-align:center;color:#6b7280;">
                        No hay items globales aún. Procesa primero uno o más archivos con IA.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Buscador front-end por coincidencia en todo el renglón --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('lg-search-input');
        const table = document.getElementById('lg-table');
        const rows  = Array.from(table.querySelectorAll('tbody tr'));
        const totalLabel = document.getElementById('lg-total-count');

        if (!input) return;

        input.addEventListener('input', function () {
            const term = this.value.trim().toLowerCase();
            let visibleCount = 0;

            rows.forEach(row => {
                // Ignorar fila vacía (caso "no hay items")
                if (!row.cells.length) return;

                const text = row.innerText.toLowerCase();
                const show = term === '' || text.includes(term);
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            if (totalLabel) {
                totalLabel.textContent = visibleCount;
            }
        });
    });
</script>
@endsection
