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
        .link-simple:hover { text-decoration:underline; }

        .btn-primary-soft {
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:6px 12px;
            border-radius:999px;
            border:1px solid #2563eb;
            background:rgba(37,99,235,0.06);
            color:#1d4ed8;
            font-size:0.8rem;
            text-decoration:none;
            cursor:pointer;
        }
        .btn-primary-soft:hover { background:rgba(37,99,235,0.12); }
        .btn-primary-soft svg { width:14px;height:14px; }

        /* Botón agregar */
        .btn-add{
            display:inline-flex;
            align-items:center;
            gap:6px;
            border:none;
            border-radius:999px;
            padding:6px 12px;
            font-size:0.8rem;
            cursor:pointer;
            background:linear-gradient(135deg,#22c55e,#16a34a);
            color:#fff;
            box-shadow:0 6px 16px rgba(22,163,74,.25);
            transition:transform .12s ease, filter .12s ease, box-shadow .12s ease;
        }
        .btn-add:hover{
            transform:translateY(-1px);
            filter:brightness(1.05);
            box-shadow:0 9px 20px rgba(22,163,74,.3);
        }
        .btn-add svg{ width:14px;height:14px; }

        .badge {
            display:inline-block;
            padding:3px 10px;
            border-radius:999px;
            font-size:0.7rem;
        }
        .badge-pendiente { background:#fef3c7; color:#92400e; }
        .badge-procesando { background:#dbeafe; color:#1d4ed8; }
        .badge-procesado { background:#dcfce7; color:#166534; }
        .badge-parcial { background:#e0f2fe; color:#0369a1; }
        .badge-error { background:#fee2e2; color:#b91c1c; }

        .info-card {
            background:#f6f8ff;
            border-radius:18px;
            padding:14px 16px;
            border:1px solid #d6def0;
            margin-bottom:12px;
        }
        .info-row {
            display:flex;
            flex-wrap:wrap;
            gap:16px;
            font-size:0.85rem;
        }
        .info-row div span.label {
            display:block;
            color:#6b7280;
            font-size:0.78rem;
            margin-bottom:2px;
        }
        .info-row div span.value { display:block; }

        .alert-success {
            margin: 0 0 14px;
            padding: 8px 10px;
            border-radius: 10px;
            background:#ecfdf3;
            color:#166534;
            border:1px solid #bbf7d0;
            font-size:0.8rem;
        }

        .table-card {
            background:#f6f8ff;
            border-radius:18px;
            padding:16px;
            border:1px solid #d6def0;
        }
        .table-card-header {
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:10px;
            margin-bottom:10px;
            flex-wrap:wrap;
        }
        .table-card h2 {
            font-size:1.1rem;
            font-weight:600;
            margin:0;
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

        /* Buscador tipo píldora */
        .table-tools {
            display:flex;
            align-items:center;
            gap:10px;
            flex-wrap:wrap;
            justify-content:flex-end;
        }
        .search-help {
            font-size:0.73rem;
            color:#6b7280;
        }
        .search-pill {
            display:flex;
            align-items:center;
            gap:6px;
            padding:5px 12px;
            border-radius:999px;
            border:1px solid #d4d4d8;
            background:#ffffff;
            box-shadow:0 1px 2px rgba(15,23,42,0.04);
        }
        .search-pill svg {
            width:14px;
            height:14px;
            color:#6b7280;
            flex-shrink:0;
        }
        .search-pill-input {
            border:none;
            outline:none;
            background:transparent;
            font-size:0.8rem;
            min-width:180px;
        }
        .search-pill-input::placeholder { color:#9ca3af; }
        .search-pill:focus-within {
            border-color:#4f46e5;
            box-shadow:0 0 0 1px rgba(79,70,229,0.16);
        }

        /* Botón editar fila */
        .btn-row-edit{
            border:none;
            border-radius:999px;
            padding:5px 9px;
            font-size:0.76rem;
            display:inline-flex;
            align-items:center;
            gap:4px;
            cursor:pointer;
            background:#e0f2fe;
            color:#0369a1;
            transition:background .12s ease, transform .1s ease;
        }
        .btn-row-edit:hover{
            background:#bfdbfe;
            transform:translateY(-1px);
        }
        .btn-row-edit svg{ width:13px;height:13px; }

        /* Botón eliminar fila */
        .btn-row-del{
            border:none;
            border-radius:999px;
            padding:5px 9px;
            font-size:0.76rem;
            display:inline-flex;
            align-items:center;
            gap:4px;
            cursor:pointer;
            background:#fee2e2;
            color:#b91c1c;
            transition:background .12s ease, transform .1s ease;
        }
        .btn-row-del:hover{
            background:#fecaca;
            transform:translateY(-1px);
        }
        .btn-row-del svg{ width:13px;height:13px; }

        /* ===== Modal (reuso para add/edit) ===== */
        .ia-modal{
            position:fixed; inset:0; display:none;
            align-items:center; justify-content:center;
            z-index:40;
        }
        .ia-modal.is-open{ display:flex; }
        .ia-modal-backdrop{
            position:absolute; inset:0;
            background:rgba(15,23,42,0.5);
        }
        .ia-modal-panel{
            position:relative; z-index:50;
            width:100%; max-width:620px;
            background:#ffffff; border-radius:18px;
            box-shadow:0 25px 60px rgba(15,23,42,0.35);
            padding:16px 18px 14px;
        }
        .ia-modal-header{
            display:flex; justify-content:space-between;
            align-items:flex-start; gap:8px;
            margin-bottom:10px;
        }
        .ia-modal-title{
            font-size:1rem; font-weight:600; color:#0f172a;
        }
        .ia-modal-sub{ font-size:0.78rem;color:#6b7280; }
        .ia-modal-close{
            border:none; background:transparent; cursor:pointer;
            border-radius:999px; padding:4px; color:#6b7280;
        }
        .ia-modal-close:hover{ background:#f3f4f6;color:#111827; }
        .ia-modal-close svg{ width:16px;height:16px; }

        .ia-modal-grid{
            display:grid;
            grid-template-columns:repeat(2, minmax(0,1fr));
            gap:10px 12px; font-size:0.78rem;
        }
        .ia-modal-field label{
            display:block; margin-bottom:2px;
            color:#4b5563; font-size:0.76rem;
        }
        .ia-input{
            width:100%;
            border-radius:999px; border:1px solid #d4d4d8;
            padding:5px 9px; font-size:0.78rem;
            background:#ffffff; outline:none;
        }
        .ia-input:focus{
            border-color:#4f46e5;
            box-shadow:0 0 0 1px rgba(79,70,229,0.12);
        }
        .ia-textarea{
            width:100%;
            border-radius:10px; border:1px solid #d4d4d8;
            padding:5px 9px; font-size:0.78rem;
            background:#ffffff; outline:none;
            resize:vertical; min-height:46px;
        }
        .ia-textarea:focus{
            border-color:#4f46e5;
            box-shadow:0 0 0 1px rgba(79,70,229,0.12);
        }

        .ia-modal-footer{
            display:flex; justify-content:flex-end;
            gap:8px; margin-top:14px;
        }
        .btn-secondary{
            border-radius:999px; border:1px solid #d4d4d8;
            background:#f9fafb; padding:6px 12px;
            font-size:0.8rem; cursor:pointer; color:#374151;
        }
        .btn-secondary:hover{ background:#f3f4f6; }
        .btn-primary{
            border:none; border-radius:999px;
            padding:6px 14px; font-size:0.8rem;
            display:inline-flex; align-items:center; gap:6px;
            cursor:pointer;
            background:linear-gradient(135deg,#2563eb,#3b82f6);
            color:#fff; box-shadow:0 6px 16px rgba(37,99,235,0.35);
            transition:transform .12s ease, box-shadow .12s ease, filter .12s ease;
        }
        .btn-primary:hover{
            transform:translateY(-1px);
            filter:brightness(1.05);
            box-shadow:0 9px 20px rgba(37,99,235,0.4);
        }
        .btn-primary svg{ width:14px;height:14px; }

        @media (max-width: 640px) {
            .top-actions { flex-direction:column; align-items:flex-start; }
            .table-card-header{ align-items:flex-start; }
            .ia-modal-panel{ margin:0 12px; }
            .ia-modal-grid{ grid-template-columns:1fr; }
        }
    </style>

    <div class="top-actions">
        <div>
            <a href="{{ route('licitaciones-ai.index') }}" class="link-simple">&larr; Volver al listado AI</a>
            <h1 style="font-size:1.4rem;font-weight:600;margin-top:6px;">
                Archivo AI #{{ $licitacionFile->id }} — {{ $licitacionFile->nombre_original }}
            </h1>
        </div>

        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
            <a href="{{ route('licitaciones-ai.tabla-global') }}" class="link-simple">
                Ver tabla global
            </a>

            <a href="{{ route('licitaciones-ai.excel', $licitacionFile) }}" class="btn-primary-soft">
                <svg viewBox="0 0 20 20" fill="none">
                    <path d="M10 3.5v8.25m0 0L6.75 8.5M10 11.75l3.25-3.25M4.5 13.5v1.25A1.75 1.75 0 006.25 16.5h7.5A1.75 1.75 0 0015.5 14.75V13.5"
                          stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Descargar Excel</span>
            </a>

            {{-- Agregar ítem manual --}}
            <button type="button" class="btn-add" id="btn-open-add-modal">
                <svg viewBox="0 0 20 20" fill="none">
                    <path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
                <span>Agregar ítem manual</span>
            </button>
        </div>
    </div>

    <div class="info-card">
        <div class="info-row">
            <div>
                <span class="label">Estado</span>
                @php
                    $estado = $licitacionFile->estado;
                    $clase = match($estado) {
                        'pendiente'          => 'badge-pendiente',
                        'procesando'         => 'badge-procesando',
                        'procesado'          => 'badge-procesado',
                        'procesado_parcial'  => 'badge-parcial',
                        'error'              => 'badge-error',
                        default              => 'badge-pendiente',
                    };
                    $textoEstado = ucwords(str_replace('_', ' ', $estado));
                @endphp
                <span class="badge {{ $clase }}">{{ $textoEstado }}</span>
            </div>
            <div>
                <span class="label">Total items extraídos</span>
                <span class="value">{{ $licitacionFile->total_items ?? 0 }}</span>
            </div>
            <div>
                <span class="label">Creado</span>
                <span class="value">{{ $licitacionFile->created_at?->format('d/m/Y H:i') }}</span>
            </div>
            <div>
                <span class="label">Actualizado</span>
                <span class="value">{{ $licitacionFile->updated_at?->format('d/m/Y H:i') }}</span>
            </div>
        </div>

        @if($licitacionFile->error_mensaje)
            <div style="margin-top:8px;font-size:0.8rem;color:#b91c1c;">
                Error: {{ $licitacionFile->error_mensaje }}
            </div>
        @endif
    </div>

    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-card">
        <div class="table-card-header">
            <div>
                <h2>Items extraídos del archivo (IA)</h2>
                <span style="font-size:0.75rem;color:#6b7280;">
                    Puedes editar, agregar o eliminar ítems.
                </span>
            </div>

            <div class="table-tools">
                <span class="search-help">
                    Buscar por requisición, partida, clave, descripción, unidad, etc.
                </span>
                <div class="search-pill">
                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M13.5 13.5L17 17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="9" cy="9" r="4.8" stroke="currentColor" stroke-width="1.6"/>
                    </svg>
                    <input
                        id="ia-search-input"
                        type="search"
                        class="search-pill-input"
                        placeholder="Filtrar ítems..."
                        autocomplete="off"
                    >
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Requisición</th>
                    <th>Partida</th>
                    <th>Clave verificación</th>
                    <th>Descripción / Especificaciones</th>
                    <th>Cantidad</th>
                    <th>Unidad</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="ia-items-body">
            @if($items->isEmpty())
                <tr>
                    <td colspan="7" style="padding:16px;text-align:center;color:#6b7280;">
                        No se encontraron items para este archivo en el módulo IA.
                    </td>
                </tr>
            @else
                @foreach ($items as $item)
                    @php
                        $searchString = strtolower(trim(
                            ($item->requisicion ?? '') .' '.
                            ($item->partida ?? '') .' '.
                            ($item->clave_verificacion ?? '') .' '.
                            ($item->descripcion_bien ?? '') .' '.
                            ($item->especificaciones ?? '') .' '.
                            ($item->cantidad ?? '') .' '.
                            ($item->unidad_medida ?? '')
                        ));
                    @endphp
                    <tr data-ia-row="item" data-search="{{ $searchString }}">
                        <td><span class="tag-req">{{ $item->requisicion }}</span></td>
                        <td>{{ $item->partida }}</td>
                        <td>{{ $item->clave_verificacion }}</td>
                        <td>
                            <strong>{{ $item->descripcion_bien }}</strong><br>
                            @if($item->especificaciones)
                                <span style="font-size:0.75rem;color:#6b7280;">
                                    {{ $item->especificaciones }}
                                </span>
                            @endif
                        </td>
                        <td>{{ (int) $item->cantidad }}</td>
                        <td>{{ $item->unidad_medida }}</td>
                        <td style="white-space:nowrap;display:flex;gap:6px;flex-wrap:wrap;">
                            {{-- EDITAR --}}
                            <button
                                type="button"
                                class="btn-row-edit"
                                data-action="{{ route('licitaciones-ai.items.update', $item) }}"
                                data-requisicion="{{ $item->requisicion }}"
                                data-partida="{{ $item->partida }}"
                                data-clave_verificacion="{{ $item->clave_verificacion }}"
                                data-descripcion_bien="{{ $item->descripcion_bien }}"
                                data-especificaciones="{{ $item->especificaciones }}"
                                data-cantidad="{{ $item->cantidad }}"
                                data-unidad_medida="{{ $item->unidad_medida }}"
                            >
                                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                    <path d="M4 13.5V16h2.5L15 7.5 12.5 5 4 13.5z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                                    <path d="M11 5.5L14.5 9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                                </svg>
                                <span>Editar</span>
                            </button>

                            {{-- ELIMINAR --}}
                            <form method="POST"
                                  action="{{ route('licitaciones-ai.items.destroy', $item) }}"
                                  onsubmit="return confirm('¿Seguro que quieres eliminar este ítem?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-row-del">
                                    <svg viewBox="0 0 20 20" fill="none">
                                        <path d="M6 6l8 8M14 6l-8 8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                    </svg>
                                    <span>Eliminar</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach

                <tr id="ia-no-results-row" style="display:none;">
                    <td colspan="7" style="padding:16px;text-align:center;color:#6b7280;">
                        No hay resultados que coincidan con tu búsqueda.
                    </td>
                </tr>
            @endif
            </tbody>
        </table>
    </div>
</div>

{{-- ===================== MODAL EDITAR ===================== --}}
<div class="ia-modal" id="ia-edit-modal">
    <div class="ia-modal-backdrop" data-ia-close-modal></div>
    <div class="ia-modal-panel">
        <div class="ia-modal-header">
            <div>
                <div class="ia-modal-title">Editar ítem</div>
                <div class="ia-modal-sub">
                    Ajusta los campos necesarios. Al guardar, se reubicará por partida.
                </div>
            </div>
            <button type="button" class="ia-modal-close" data-ia-close-modal>
                <svg viewBox="0 0 20 20" fill="none">
                    <path d="M5 5l10 10M15 5L5 15" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        {{-- ✅ OJO: tu ruta items.update es POST, NO PUT --}}
        <form method="POST" id="ia-edit-form" action="">
            @csrf

            <div class="ia-modal-grid">
                <div class="ia-modal-field">
                    <label for="ia-edit-requisicion">Requisición</label>
                    <input type="text" id="ia-edit-requisicion" name="requisicion" class="ia-input">
                </div>
                <div class="ia-modal-field">
                    <label for="ia-edit-partida">Partida</label>
                    <input type="text" id="ia-edit-partida" name="partida" class="ia-input">
                </div>
                <div class="ia-modal-field">
                    <label for="ia-edit-clave">Clave verificación</label>
                    <input type="text" id="ia-edit-clave" name="clave_verificacion" class="ia-input">
                </div>
                <div class="ia-modal-field">
                    <label for="ia-edit-unidad">Unidad</label>
                    <input type="text" id="ia-edit-unidad" name="unidad_medida" class="ia-input" required>
                </div>
                <div class="ia-modal-field">
                    <label for="ia-edit-cantidad">Cantidad</label>
                    <input type="number" step="1" min="0" id="ia-edit-cantidad" name="cantidad" class="ia-input" required>
                </div>
            </div>

            <div style="margin-top:10px;">
                <div class="ia-modal-field" style="margin-bottom:8px;">
                    <label for="ia-edit-descripcion">Descripción del bien</label>
                    <textarea id="ia-edit-descripcion" name="descripcion_bien" class="ia-textarea" required></textarea>
                </div>
                <div class="ia-modal-field">
                    <label for="ia-edit-especificaciones">Especificaciones (opcional)</label>
                    <textarea id="ia-edit-especificaciones" name="especificaciones" class="ia-textarea"></textarea>
                </div>
            </div>

            <div class="ia-modal-footer">
                <button type="button" class="btn-secondary" data-ia-close-modal>Cancelar</button>
                <button type="submit" class="btn-primary">
                    <svg viewBox="0 0 20 20" fill="none">
                        <path d="M5 10.5l3 3 7-7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Guardar cambios</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===================== MODAL AGREGAR ===================== --}}
<div class="ia-modal" id="ia-add-modal">
    <div class="ia-modal-backdrop" data-ia-close-modal-add></div>
    <div class="ia-modal-panel">
        <div class="ia-modal-header">
            <div>
                <div class="ia-modal-title">Agregar ítem manual</div>
                <div class="ia-modal-sub">
                    Este ítem se incluirá en la licitación y en la tabla global.
                </div>
            </div>
            <button type="button" class="ia-modal-close" data-ia-close-modal-add>
                <svg viewBox="0 0 20 20" fill="none">
                    <path d="M5 5l10 10M15 5L5 15" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('licitaciones-ai.items.store', $licitacionFile) }}">
            @csrf

            <div class="ia-modal-grid">
                <div class="ia-modal-field">
                    <label>Requisición</label>
                    <input type="text" name="requisicion" class="ia-input">
                </div>
                <div class="ia-modal-field">
                    <label>Partida</label>
                    <input type="text" name="partida" class="ia-input">
                </div>
                <div class="ia-modal-field">
                    <label>Clave verificación</label>
                    <input type="text" name="clave_verificacion" class="ia-input">
                </div>
                <div class="ia-modal-field">
                    <label>Unidad</label>
                    <input type="text" name="unidad_medida" class="ia-input" required>
                </div>
                <div class="ia-modal-field">
                    <label>Cantidad</label>
                    <input type="number" step="1" min="0" name="cantidad" class="ia-input" required>
                </div>
            </div>

            <div style="margin-top:10px;">
                <div class="ia-modal-field" style="margin-bottom:8px;">
                    <label>Descripción del bien</label>
                    <textarea name="descripcion_bien" class="ia-textarea" required></textarea>
                </div>
                <div class="ia-modal-field">
                    <label>Especificaciones (opcional)</label>
                    <textarea name="especificaciones" class="ia-textarea"></textarea>
                </div>
            </div>

            <div class="ia-modal-footer">
                <button type="button" class="btn-secondary" data-ia-close-modal-add>Cancelar</button>
                <button type="submit" class="btn-primary">
                    <svg viewBox="0 0 20 20" fill="none">
                        <path d="M5 10.5l3 3 7-7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Agregar</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ===== Filtro =====
    const input = document.getElementById('ia-search-input');
    const tbody = document.getElementById('ia-items-body');
    if (input && tbody) {
        const rows = Array.from(tbody.querySelectorAll('tr[data-ia-row="item"]'));
        const emptyRow = document.getElementById('ia-no-results-row');

        function applyFilter() {
            const term = (input.value || '').trim().toLowerCase();
            let visibleCount = 0;

            rows.forEach(row => {
                const haystack = (row.dataset.search || row.textContent || '').toLowerCase();
                const match = !term || haystack.includes(term);
                row.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });

            if (emptyRow) {
                emptyRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
            }
        }
        input.addEventListener('input', applyFilter);
    }

    // ===== Modal EDITAR =====
    const modalEdit = document.getElementById('ia-edit-modal');
    const formEdit  = document.getElementById('ia-edit-form');

    const fieldReq   = document.getElementById('ia-edit-requisicion');
    const fieldPart  = document.getElementById('ia-edit-partida');
    const fieldClave = document.getElementById('ia-edit-clave');
    const fieldDesc  = document.getElementById('ia-edit-descripcion');
    const fieldEsp   = document.getElementById('ia-edit-especificaciones');
    const fieldCant  = document.getElementById('ia-edit-cantidad');
    const fieldUni   = document.getElementById('ia-edit-unidad');

    function openEdit(btn) {
        if(!modalEdit || !formEdit) return;

        const action = btn.getAttribute('data-action');
        formEdit.setAttribute('action', action || '');

        fieldReq.value   = btn.getAttribute('data-requisicion')        || '';
        fieldPart.value  = btn.getAttribute('data-partida')            || '';
        fieldClave.value = btn.getAttribute('data-clave_verificacion') || '';
        fieldDesc.value  = btn.getAttribute('data-descripcion_bien')   || '';
        fieldEsp.value   = btn.getAttribute('data-especificaciones')   || '';
        // quita decimales visualmente
        const cantRaw = btn.getAttribute('data-cantidad') || '';
        fieldCant.value = cantRaw !== '' ? parseInt(cantRaw, 10) : '';
        fieldUni.value   = btn.getAttribute('data-unidad_medida')      || '';

        modalEdit.classList.add('is-open');
    }
    function closeEdit(){ modalEdit?.classList.remove('is-open'); }

    document.querySelectorAll('.btn-row-edit').forEach(btn=>{
        btn.addEventListener('click', ()=>openEdit(btn));
    });
    document.querySelectorAll('[data-ia-close-modal]').forEach(el=>{
        el.addEventListener('click', closeEdit);
    });

    // ===== Modal AGREGAR =====
    const modalAdd = document.getElementById('ia-add-modal');
    const btnOpenAdd = document.getElementById('btn-open-add-modal');
    function openAdd(){ modalAdd?.classList.add('is-open'); }
    function closeAdd(){ modalAdd?.classList.remove('is-open'); }

    if(btnOpenAdd){
        btnOpenAdd.addEventListener('click', openAdd);
    }
    document.querySelectorAll('[data-ia-close-modal-add]').forEach(el=>{
        el.addEventListener('click', closeAdd);
    });

    // ESC cierra ambos
    document.addEventListener('keydown', (e)=>{
        if(e.key === 'Escape'){
            closeEdit(); closeAdd();
        }
    });
});
</script>
@endsection
