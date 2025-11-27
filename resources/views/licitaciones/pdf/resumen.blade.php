{{-- resources/views/licitaciones/pdf/resumen.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Resumen licitación #{{ $licitacion->id }}</title>
    <style>
        @page {
            size: letter;
            margin: 20mm 18mm 26mm 18mm;
        }

        * {
            box-sizing: border-box;
        }

        :root{
            --primary: #2563eb;
            --primary-soft: #e0edff;
            --primary-soft-2: #eff4ff;
            --accent: #22c55e;
            --danger: #ef4444;
            --text-main: #111827;
            --text-muted: #6b7280;
            --border-subtle: #e5e7eb;
            --bg-soft: #f9fafb;
            --chip-bg: #eef2ff;
        }

        body {
            font-family: "Söhne", "Circular Std", "Poppins",
                         system-ui, -apple-system, "Segoe UI",
                         "Helvetica Neue", Arial, sans-serif;
            font-size: 10.8px;
            color: var(--text-main);
            margin: 0;
            padding: 0;
        }

        h1, h2, h3, h4 {
            margin: 0;
            font-weight: 600;
            letter-spacing: -0.01em;
        }

        .muted {
            color: var(--text-muted);
        }

        .mb-xs { margin-bottom: 4px; }
        .mb-sm { margin-bottom: 8px; }
        .mb-md { margin-bottom: 12px; }
        .mb-lg { margin-bottom: 18px; }
        .mb-xl { margin-bottom: 22px; }

        .page {
            width: 100%;
        }

        /* Encabezado principal */
        .top-bar {
            width: 100%;
            padding: 6px 10px;
            border-radius: 10px;
            background: linear-gradient(90deg, #1d4ed8, #4f46e5);
            color: #f9fafb;
            margin-bottom: 12px;
        }
        .top-bar-title {
            font-size: 13px;
            font-weight: 600;
        }
        .top-bar-sub {
            font-size: 10px;
            opacity: .88;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 18px;
        }
        .header-left {
            display: table-cell;
            vertical-align: top;
            width: 68%;
        }
        .header-right {
            display: table-cell;
            vertical-align: top;
            text-align: right;
            width: 32%;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.4);
            font-size: 9px;
            color: #111827;
            background: #f9fafb;
        }

        .badge-soft {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            border: 1px solid var(--border-subtle);
            font-size: 9px;
            color: #374151;
            background: #ffffff;
        }

        .badge-status-abierto {
            background: #ecfdf3;
            border-color: #bbf7d0;
            color: #166534;
        }

        .badge-status-cerrado {
            background: #fef2f2;
            border-color: #fecaca;
            color: #b91c1c;
        }

        .pill {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 999px;
            background: var(--chip-bg);
            border: 1px solid #e5e7ff;
            font-size: 9px;
            color: #4338ca;
        }

        .pill-neutral {
            background: #f3f4f6;
            border-color: #e5e7eb;
            color: #4b5563;
        }

        /* Secciones */
        .section {
            margin-bottom: 14px;
        }
        .section-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .16em;
            color: var(--text-muted);
            margin-bottom: 5px;
        }
        .section-title span {
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 1px;
        }

        .card {
            border-radius: 10px;
            border: 1px solid var(--border-subtle);
            padding: 10px 12px;
            background: #ffffff;
        }

        .card-soft {
            border-radius: 10px;
            border: 1px solid #dbeafe;
            padding: 10px 12px;
            background: var(--primary-soft-2);
        }

        .card-muted {
            border-radius: 10px;
            border: 1px dashed #d1d5db;
            padding: 8px 10px;
            background: var(--bg-soft);
        }

        /* Layout columnas (sin abusar de tablas) */
        .cols-2 {
            display: table;
            width: 100%;
        }
        .col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .col + .col {
            padding-left: 10px;
        }

        /* Definition list estilizado */
        dl {
            margin: 0;
        }
        dt {
            font-size: 8.7px;
            text-transform: uppercase;
            color: #9ca3af;
        }
        dd {
            margin: 1px 0 6px 0;
            font-size: 10.5px;
            color: var(--text-main);
        }

        /* KPI grid */
        .kpi-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .kpi-cell {
            width: 33.33%;
            padding: 6px 8px;
            border-right: 1px solid rgba(148,163,184,.35);
        }
        .kpi-cell:last-child {
            border-right: none;
        }
        .kpi-label {
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: #6b7280;
            margin-bottom: 2px;
        }
        .kpi-value {
            font-size: 11px;
            font-weight: 600;
        }
        .kpi-chip {
            font-size: 8.5px;
            color: #4b5563;
        }

        /* Timeline */
        .timeline {
            padding: 0;
            margin: 0;
            list-style: none;
        }
        .timeline-item {
            display: table;
            width: 100%;
            margin-bottom: 6px;
        }
        .timeline-marker {
            display: table-cell;
            width: 14px;
            vertical-align: top;
        }
        .timeline-marker-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--primary);
            margin-top: 3px;
        }
        .timeline-content {
            display: table-cell;
            vertical-align: top;
            padding-left: 6px;
        }
        .timeline-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--text-muted);
        }
        .timeline-main {
            font-size: 10.5px;
        }
        .timeline-sub {
            font-size: 9px;
            color: var(--text-muted);
        }

        /* Tablas simples (las mínimas necesarias) */
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.8px;
        }
        .table th {
            text-align: left;
            padding: 4px 3px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 8.7px;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--text-muted);
        }
        .table td {
            padding: 3px 3px;
            border-bottom: 1px solid #f3f4f6;
        }

        .table-compact td {
            padding-top: 2px;
            padding-bottom: 2px;
        }

        .small {
            font-size: 8.7px;
            color: var(--text-muted);
        }

        .chips-row {
            margin-top: 2px;
        }
        .chips-row .pill {
            margin-right: 4px;
            margin-bottom: 4px;
        }

        /* Page breaks para repartir mejor la info */
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
@php
    $totalArchivos  = $licitacion->archivos->count();
    $totalPreguntas = $licitacion->preguntas->count();
    $conta          = $licitacion->contabilidad;
    $monto          = $conta?->monto_inversion_estimado ?? 0;
    $utilidad       = $conta?->utilidad_estimada ?? 0;
    $margen         = $monto > 0 ? ($utilidad / $monto) * 100 : null;

    $estatus = $licitacion->estatus ?? 'borrador';
    $resultado = $licitacion->resultado;
@endphp

<div class="page">
    {{-- BARRA SUPERIOR --}}
    <div class="top-bar">
        <div class="top-bar-title">
            Resumen ejecutivo de licitación
        </div>
        <div class="top-bar-sub">
            ID #{{ $licitacion->id }} · {{ $licitacion->titulo }}
        </div>
    </div>

    {{-- ENCABEZADO --}}
    <div class="header">
        <div class="header-left">
            <h1 style="font-size:15px; margin-bottom:4px;">
                {{ $licitacion->titulo }}
            </h1>
            <div class="mb-xs muted" style="font-size:10px;">
                Resumen general del procedimiento · Área de licitaciones
            </div>

            @if($licitacion->descripcion)
                <div class="small" style="margin-top:4px;">
                    {{ $licitacion->descripcion }}
                </div>
            @endif
        </div>
        <div class="header-right">
            <div class="mb-xs">
                @php
                    $statusClass = in_array($estatus, ['cerrado','cancelado'])
                        ? 'badge-status-cerrado'
                        : 'badge-status-abierto';
                @endphp
                <span class="badge-soft {{ $statusClass }}">
                    Estatus: {{ ucfirst(str_replace('_',' ',$estatus)) }}
                </span>
            </div>
            <div class="mb-xs">
                @if($resultado)
                    <span class="badge-soft" style="{{ $resultado === 'ganado' ? 'background:#ecfdf3;border-color:#bbf7d0;color:#166534;' : 'background:#fef2f2;border-color:#fecaca;color:#b91c1c;' }}">
                        Resultado: {{ ucfirst(str_replace('_',' ',$resultado)) }}
                    </span>
                @else
                    <span class="badge-soft">
                        Resultado: Pendiente
                    </span>
                @endif
            </div>
            <div class="small">
                Generado el {{ now()->format('d/m/Y H:i') }}<br>
                Paso actual del flujo: <strong>{{ $licitacion->current_step }}</strong>
            </div>
        </div>
    </div>

    {{-- RESUMEN EJECUTIVO RÁPIDO --}}
    <div class="section">
        <div class="section-title"><span>Resumen ejecutivo</span></div>
        <div class="card-soft">
            <div class="cols-2">
                <div class="col">
                    <dl>
                        <dt>Modalidad</dt>
                        <dd>
                            <span class="pill">
                                {{ ucfirst(str_replace('_',' ',$licitacion->modalidad)) }}
                            </span>
                        </dd>

                        <dt>Requiere muestras</dt>
                        <dd>
                            <span class="pill {{ $licitacion->requiere_muestras ? '' : 'pill-neutral' }}">
                                {{ $licitacion->requiere_muestras ? 'Sí requiere muestras' : 'No requiere muestras' }}
                            </span>
                        </dd>

                        <dt>Convocatoria principal</dt>
                        <dd>
                            {{ optional($licitacion->fecha_convocatoria)->format('d/m/Y') ?? '—' }}
                        </dd>
                    </dl>
                </div>
                <div class="col">
                    <dl>
                        <dt>Archivos vinculados</dt>
                        <dd>
                            {{ $totalArchivos }} archivo(s) en total
                            @if($totalArchivos === 0)
                                <span class="small">· pendiente de cargar documentación</span>
                            @endif
                        </dd>

                        <dt>Preguntas registradas</dt>
                        <dd>
                            {{ $totalPreguntas }} pregunta(s)
                            @if($totalPreguntas === 0)
                                <span class="small">· no se registraron dudas</span>
                            @endif
                        </dd>

                        <dt>Resultado actual</dt>
                        <dd>
                            <span class="pill {{ $resultado === 'ganado' ? '' : 'pill-neutral' }}">
                                {{ $resultado ? ucfirst(str_replace('_',' ',$resultado)) : 'Pendiente de fallo' }}
                            </span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    {{-- FECHAS CLAVE / LÍNEA DE TIEMPO --}}
    <div class="section">
        <div class="section-title"><span>Fechas clave del procedimiento</span></div>
        <div class="card">
            <ul class="timeline">
                <li class="timeline-item">
                    <div class="timeline-marker">
                        <div class="timeline-marker-dot"></div>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-label">Convocatoria</div>
                        <div class="timeline-main">
                            {{ optional($licitacion->fecha_convocatoria)->format('d/m/Y') ?? '—' }}
                        </div>
                        <div class="timeline-sub">
                            Fecha principal de la convocatoria.
                        </div>
                    </div>
                </li>

                <li class="timeline-item">
                    <div class="timeline-marker">
                        <div class="timeline-marker-dot"></div>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-label">Junta de aclaraciones</div>
                        <div class="timeline-main">
                            {{ optional($licitacion->fecha_junta_aclaraciones)->format('d/m/Y H:i') ?? '—' }}
                            @if($licitacion->lugar_junta)
                                · {{ $licitacion->lugar_junta }}
                            @endif
                        </div>
                        <div class="timeline-sub">
                            Límite de preguntas:
                            {{ optional($licitacion->fecha_limite_preguntas)->format('d/m/Y H:i') ?? '—' }}
                        </div>
                    </div>
                </li>

                <li class="timeline-item">
                    <div class="timeline-marker">
                        <div class="timeline-marker-dot"></div>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-label">Entrega de muestras</div>
                        <div class="timeline-main">
                            {{ optional($licitacion->fecha_entrega_muestras)->format('d/m/Y H:i') ?? '—' }}
                        </div>
                        <div class="timeline-sub">
                            Lugar:
                            {{ $licitacion->lugar_entrega_muestras ?: 'No especificado' }}
                        </div>
                    </div>
                </li>

                <li class="timeline-item">
                    <div class="timeline-marker">
                        <div class="timeline-marker-dot"></div>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-label">Apertura de propuesta</div>
                        <div class="timeline-main">
                            {{ optional($licitacion->fecha_apertura_propuesta)->format('d/m/Y H:i') ?? '—' }}
                        </div>
                        <div class="timeline-sub">
                            Acta de apertura:
                            {{ optional($licitacion->fecha_acta_apertura)->format('d/m/Y') ?? '—' }}
                        </div>
                    </div>
                </li>

                <li class="timeline-item">
                    <div class="timeline-marker">
                        <div class="timeline-marker-dot"></div>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-label">Fallo</div>
                        <div class="timeline-main">
                            {{ optional($licitacion->fecha_fallo)->format('d/m/Y') ?? '—' }}
                            @if($resultado)
                                · {{ ucfirst(str_replace('_',' ',$resultado)) }}
                            @endif
                        </div>
                        <div class="timeline-sub">
                            Presentación del fallo:
                            {{ optional($licitacion->fecha_presentacion_fallo)->format('d/m/Y H:i') ?? '—' }}
                        </div>
                    </div>
                </li>

                <li class="timeline-item">
                    <div class="timeline-marker">
                        <div class="timeline-marker-dot"></div>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-label">Contrato / Fianza</div>
                        <div class="timeline-main">
                            Emisión contrato:
                            {{ optional($licitacion->fecha_emision_contrato)->format('d/m/Y') ?? '—' }} ·
                            Fianza:
                            {{ optional($licitacion->fecha_fianza)->format('d/m/Y') ?? '—' }}
                        </div>
                        <div class="timeline-sub">
                            Seguimiento posterior a la adjudicación.
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>

    {{-- Pequeño checklist visual --}}
    <div class="section">
        <div class="section-title"><span>Checklist rápido de estado</span></div>
        <div class="card-muted">
            <div class="chips-row">
                <span class="pill {{ $totalArchivos > 0 ? '' : 'pill-neutral' }}">
                    Documentación cargada: {{ $totalArchivos }} archivo(s)
                </span>
                <span class="pill {{ $totalPreguntas > 0 ? '' : 'pill-neutral' }}">
                    Preguntas: {{ $totalPreguntas }}
                </span>
                <span class="pill {{ $licitacion->requiere_muestras ? '' : 'pill-neutral' }}">
                    {{ $licitacion->requiere_muestras ? 'Con muestras físicas' : 'Sin muestras físicas' }}
                </span>
                <span class="pill-neutral pill">
                    Paso actual: {{ $licitacion->current_step }}
                </span>
            </div>
        </div>
    </div>
</div>

{{-- NUEVA PÁGINA: BLOQUE CONTABLE + ARCHIVOS --}}
<div class="page-break"></div>
<div class="page">
    {{-- RESUMEN CONTABLE --}}
    <div class="section">
        <div class="section-title"><span>Resumen contable</span></div>

        @if($conta)
            <div class="card-soft">
                <table class="kpi-grid">
                    <tr>
                        <td class="kpi-cell">
                            <div class="kpi-label">Importe adjudicado / inversión estimada</div>
                            <div class="kpi-value">
                                ${{ number_format($conta->monto_inversion_estimado, 2) }}
                            </div>
                            <div class="kpi-chip">
                                Monto total comprometido o estimado.
                            </div>
                        </td>
                        <td class="kpi-cell">
                            <div class="kpi-label">Costo total estimado</div>
                            <div class="kpi-value">
                                ${{ number_format($conta->costo_total, 2) }}
                            </div>
                            <div class="kpi-chip">
                                Incluye costos directos e indirectos.
                            </div>
                        </td>
                        <td class="kpi-cell">
                            <div class="kpi-label">Utilidad estimada</div>
                            <div class="kpi-value">
                                ${{ number_format($conta->utilidad_estimada, 2) }}
                            </div>
                            <div class="kpi-chip">
                                @if(!is_null($margen))
                                    Margen aprox.: {{ number_format($margen, 1) }} %
                                @else
                                    Margen no calculable.
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        @else
            <div class="card-muted">
                <span class="small">
                    No se ha registrado información contable para esta licitación.
                    Se recomienda capturar al menos: monto adjudicado, costo total y utilidad estimada.
                </span>
            </div>
        @endif
    </div>

    {{-- ARCHIVOS PRINCIPALES --}}
    <div class="section">
        <div class="section-title"><span>Archivos vinculados</span></div>

        @if($totalArchivos)
            <div class="card">
                <table class="table table-compact">
                    <thead>
                        <tr>
                            <th style="width: 26%;">Tipo</th>
                            <th>Nombre de archivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($licitacion->archivos as $archivo)
                            <tr>
                                <td>
                                    <span class="pill-neutral pill">
                                        {{ ucfirst(str_replace('_',' ',$archivo->tipo)) }}
                                    </span>
                                </td>
                                <td>
                                    {{ $archivo->nombre_original }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="small" style="margin-top:6px;">
                    Este listado resume únicamente la documentación cargada en el sistema; los archivos se consultan desde el módulo web.
                </div>
            </div>
        @else
            <div class="card-muted">
                <span class="small">
                    Sin archivos cargados hasta el momento.
                </span>
            </div>
        @endif
    </div>

    {{-- DETALLE OPERATIVO (JUNTA / MUESTRAS / FALLO) --}}
    <div class="section">
        <div class="section-title"><span>Detalle operativo</span></div>
        <div class="card">
            <div class="cols-2">
                <div class="col">
                    <dl>
                        <dt>Junta de aclaraciones</dt>
                        <dd>
                            {{ optional($licitacion->fecha_junta_aclaraciones)->format('d/m/Y H:i') ?? '—' }}<br>
                            <span class="small">
                                Lugar: {{ $licitacion->lugar_junta ?: 'No definido' }}<br>
                                @if($licitacion->link_junta)
                                    Enlace: {{ $licitacion->link_junta }}
                                @else
                                    Enlace no registrado.
                                @endif
                            </span>
                        </dd>

                        <dt>Notificación de preguntas</dt>
                        <dd>
                            @if(!empty($licitacion->recordatorio_emails))
                                <span class="small">
                                    Correos configurados para recordatorio:
                                    {{ implode(', ', (array)$licitacion->recordatorio_emails) }}
                                </span>
                            @else
                                <span class="small">No se configuraron correos de recordatorio.</span>
                            @endif
                        </dd>

                        <dt>Resultado del fallo</dt>
                        <dd>
                            <span class="pill {{ $resultado === 'ganado' ? '' : 'pill-neutral' }}">
                                {{ $resultado ? ucfirst(str_replace('_',' ',$resultado)) : 'Pendiente' }}
                            </span><br>
                            <span class="small">
                                Fecha del fallo:
                                {{ optional($licitacion->fecha_fallo)->format('d/m/Y') ?? '—' }}
                            </span>
                        </dd>
                    </dl>
                </div>
                <div class="col">
                    <dl>
                        <dt>Muestras</dt>
                        <dd>
                            @if($licitacion->requiere_muestras)
                                Entrega:
                                {{ optional($licitacion->fecha_entrega_muestras)->format('d/m/Y H:i') ?? '—' }}<br>
                                <span class="small">
                                    {{ $licitacion->lugar_entrega_muestras ?: 'Lugar no especificado.' }}
                                </span>
                            @else
                                <span class="small">No se solicitaron muestras para este procedimiento.</span>
                            @endif
                        </dd>

                        <dt>Contrato y fianza</dt>
                        <dd>
                            <span class="small">
                                Emisión de contrato:
                                {{ optional($licitacion->fecha_emision_contrato)->format('d/m/Y') ?? '—' }}<br>
                                Fianza:
                                {{ optional($licitacion->fecha_fianza)->format('d/m/Y') ?? '—' }}
                            </span>
                        </dd>

                        <dt>Observaciones internas del fallo</dt>
                        <dd>
                            @if($licitacion->observaciones_fallo)
                                {{ $licitacion->observaciones_fallo }}
                            @else
                                <span class="small">Sin observaciones registradas.</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- NUEVA PÁGINA: PREGUNTAS --}}
<div class="page-break"></div>
<div class="page">
    {{-- PREGUNTAS REGISTRADAS --}}
    <div class="section">
        <div class="section-title"><span>Preguntas registradas</span></div>

        @php
            $preguntas = $licitacion->preguntas->sortBy('fecha_pregunta')->values();
        @endphp

        @if($preguntas->count())
            <div class="card">
                <table class="table">
                    <thead>
                    <tr>
                        <th style="width: 6%;">#</th>
                        <th style="width: 64%;">Pregunta</th>
                        <th style="width: 30%;">Fecha / Usuario</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($preguntas as $index => $pregunta)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $pregunta->texto_pregunta }}</td>
                            <td>
                                <div class="small">
                                    {{ optional($pregunta->fecha_pregunta)->format('d/m/Y H:i') ?? '—' }}<br>
                                    @if($pregunta->usuario)
                                        Registró: {{ $pregunta->usuario->name ?? $pregunta->usuario->email }}
                                    @else
                                        Usuario no especificado
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div class="small" style="margin-top:6px;">
                    Total de preguntas: {{ $preguntas->count() }}.
                    Este bloque refleja el historial de dudas y aclaraciones capturadas en el sistema.
                </div>
            </div>
        @else
            <div class="card-muted">
                <span class="small">
                    No se registraron preguntas para esta licitación.
                </span>
            </div>
        @endif
    </div>

    {{-- NOTA FINAL --}}
    <div class="section">
        <div class="section-title"><span>Notas adicionales</span></div>
        <div class="card-muted">
            <span class="small">
                Este resumen se genera de forma automática a partir de la información capturada en el módulo de licitaciones.
                Para revisar o modificar datos, accede al detalle de la licitación en el sistema.
            </span>
        </div>
    </div>
</div>

{{-- NUMERACIÓN DE PÁGINAS DOMPDF --}}
<script type="text/php">
if (isset($pdf)) {
    $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
    $size = 8;
    $font = $fontMetrics->get_font("Helvetica", "normal");
    $width = $fontMetrics->get_text_width($text, $font, $size);
    $x = ($pdf->get_width() - $width) / 2;
    $y = $pdf->get_height() - 22;
    $pdf->page_text($x, $y, $text, $font, $size, [0.55, 0.55, 0.55]);
}
</script>

</body>
</html>
