@extends('layouts.app')

@section('title', 'Tabla global de items AI')

@section('content')
<div class="page-tabla-global">
    <style>
        .page-tabla-global {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 16px 40px;
        }
        .top-bar {
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:12px;
            margin-bottom:16px;
        }
        .top-bar h1 {
            font-size:1.4rem;
            font-weight:600;
        }
        .link-simple {
            color:#2563eb;
            text-decoration:none;
            font-size:0.85rem;
        }
        .link-simple:hover {
            text-decoration:underline;
        }
        .table-card {
            background:#f6f8ff;
            border-radius:18px;
            padding:16px;
            border:1px solid #d6def0;
        }
        .table-card table {
            width:100%;
            border-collapse:collapse;
            font-size:0.78rem;
        }
        .table-card th,
        .table-card td {
            padding:6px 7px;
            border-bottom:1px solid #e3e7f3;
            vertical-align:top;
        }
        .table-card th {
            background:#e9eef6;
            font-weight:600;
        }
        .input-mini {
            width: 100%;
            padding:4px 6px;
            border-radius:6px;
            border:1px solid #cbd5e1;
            font-size:0.78rem;
        }
        .btn-save {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:4px 8px;
            border-radius:999px;
            border:none;
            background:#22c55e;
            color:#fff;
            font-size:0.75rem;
            cursor:pointer;
        }
        .btn-save:hover {
            opacity:.9;
        }
        .tag-reqs {
            font-size:0.7rem;
            color:#0369a1;
        }
    </style>

    <div class="top-bar">
        <div>
            <a href="{{ route('licitaciones-ai.index') }}" class="link-simple">&larr; Volver a archivos AI</a>
            <h1 style="margin-top:6px;">Tabla global de items (AI)</h1>
            <p style="font-size:0.8rem;color:#6b7280;margin-top:4px;">
                Items fusionados por clave / descripción. Aquí puedes capturar MARCA y MODELO de cada producto.
            </p>
        </div>
        {{-- Botones de exportarán a Excel / PDF más adelante --}}
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Clave</th>
                    <th>Descripción / Especificaciones</th>
                    <th>Unidad</th>
                    <th>Cantidad total</th>
                    <th>Requisiciones</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($itemsGlobales as $item)
                <tr>
                    <td>{{ $item->clave_verificacion }}</td>
                    <td>
                        <strong>{{ $item->descripcion_global }}</strong><br>
                        <span style="font-size:0.7rem;color:#6b7280;">
                            {{ $item->especificaciones_global }}
                        </span>
                    </td>
                    <td>{{ $item->unidad_medida }}</td>
                    <td>{{ $item->cantidad_total }}</td>
                    <td class="tag-reqs">
                        @if(is_array($item->requisiciones))
                            {{ implode(', ', $item->requisiciones) }}
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('licitaciones-ai.tabla-global.update', $item) }}" method="POST">
                            @csrf
                            <input
                                type="text"
                                name="marca"
                                class="input-mini"
                                value="{{ old('marca', $item->marca) }}"
                                placeholder="Marca">
                    </td>
                    <td>
                            <input
                                type="text"
                                name="modelo"
                                class="input-mini"
                                value="{{ old('modelo', $item->modelo) }}"
                                placeholder="Modelo">
                    </td>
                    <td>
                            <button type="submit" class="btn-save">
                                Guardar
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="padding:16px;text-align:center;color:#6b7280;">
                        Aún no hay items globales en el módulo AI.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
