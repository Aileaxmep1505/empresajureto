<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ data_get($aiReport, 'pdf_title', 'Reporte IA WMS') }}</title>
    <style>
        /* Configuración base de la página (Se recomienda landscape si hay muchas columnas) */
        @page { margin: 30px 40px; }
        
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b; /* Slate 800 */
            font-size: 10px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        /* Utilidades de texto */
        .text-muted { color: #64748b; }
        .text-dark { color: #0f172a; font-weight: bold; }
        .text-xs { font-size: 8px; }
        .uppercase { text-transform: uppercase; letter-spacing: 0.05em; }
        .mono { font-family: 'Courier New', Courier, monospace; font-size: 9px; }
        .ta-right { text-align: right; }
        .ta-center { text-align: center; }

        /* Cabecera Ejecutiva */
        .header-banner {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header-title {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
            margin: 0;
        }
        .header-subtitle {
            font-size: 10px;
            color: #475569;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .meta-table {
            width: 100%;
            margin-top: 15px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        .meta-table td {
            padding: 8px 12px;
            font-size: 9px;
            color: #334155;
            border-right: 1px solid #e2e8f0;
        }
        .meta-table td:last-child { border-right: none; }

        /* KPI Grid (Safe Table Layout) */
        .kpi-container { width: 100%; margin-bottom: 20px; }
        .kpi-table { width: 100%; table-layout: fixed; border-spacing: 10px 0; border-collapse: separate; margin-left: -10px; margin-right: -10px; }
        .kpi-box {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 12px;
            border-radius: 6px;
            width: 25%;
        }
        .kpi-label { font-size: 8px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.05em; }
        .kpi-value { font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 4px; font-family: 'Helvetica Neue', sans-serif; }

        /* Secciones */
        .section { margin-bottom: 24px; page-break-inside: avoid; }
        .section-title {
            font-size: 12px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }
        
        /* Cajas de contenido */
        .content-box {
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            padding: 15px;
            border-radius: 6px;
        }
        
        .ai-headline { font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
        .ai-body { font-size: 11px; line-height: 1.6; color: #334155; margin-top: 10px; }

        /* Etiquetas (Pills) */
        .pill {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .pill.alto, .pill.alta, .pill.danger { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .pill.medio, .pill.media, .pill.warn { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .pill.bajo, .pill.baja, .pill.ok { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .pill.informativo, .pill.info { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; }
        .pill.score { background: #0f172a; color: #ffffff; border: 1px solid #0f172a; }

        /* Listas */
        ul.clean-list { margin: 0; padding-left: 15px; }
        ul.clean-list li { margin-bottom: 6px; color: #334155; line-height: 1.5; }

        /* Evidence Grid (Safe Table Layout) */
        .evidence-table { width: 100%; border-collapse: collapse; }
        .evidence-table td { width: 50%; padding: 4px 8px 4px 0; vertical-align: top; }
        .evidence-card { border: 1px solid #e2e8f0; padding: 10px; border-radius: 4px; background: #f8fafc; }
        .evidence-label { font-size: 8px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .evidence-value { font-size: 11px; font-weight: bold; color: #0f172a; margin-top: 3px; }

        /* Acciones */
        .action-item { padding: 10px 0; border-bottom: 1px solid #e2e8f0; }
        .action-item:last-child { border-bottom: none; padding-bottom: 0; }
        .action-header { margin-bottom: 4px; }

        /* Tablas de Datos */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 9px;
        }
        .data-table th, .data-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
            text-align: left;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .data-table tr:nth-child(even) td { background-color: #f8fafc; }
        
        /* Ajuste extremo para la tabla principal (13 columnas) */
        .main-grid th, .main-grid td {
            font-size: 8px; /* Forzado muy pequeño para que quepa en PDF vertical */
            padding: 6px 4px;
            word-wrap: break-word;
        }
        .main-grid th { font-size: 7px; }
    </style>
</head>
<body>

    <table class="header-banner">
        <tr>
            <td width="70%">
                <h1 class="header-title">NEXUS AI ENGINE</h1>
                <div class="header-subtitle">{{ data_get($aiReport, 'pdf_title', 'Auditoría Ejecutiva WMS') }}</div>
            </td>
            <td width="30%" class="ta-right text-muted text-xs uppercase" style="line-height: 1.5;">
                <strong>Fecha de Emisión:</strong><br>
                {{ optional($generatedAt)->format('d M Y · H:i') }}<br>
                <strong>ID de Reporte:</strong><br>
                <span class="mono">{{ strtoupper(uniqid('REP-')) }}</span>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td width="25%"><strong>PERIODO:</strong><br>Últimos {{ data_get($filters, 'period', 30) }} días</td>
            <td width="25%"><strong>BODEGA:</strong><br>{{ data_get($filters, 'warehouse_id', 0) ?: 'Todas las operativas' }}</td>
            <td width="25%"><strong>EVENTOS:</strong><br>{{ data_get($filters, 'group', 'Todos') ?: 'Todos' }} / {{ data_get($filters, 'type', 'Todos') ?: 'Todos' }}</td>
            <td width="25%"><strong>QUERY:</strong><br><span class="mono">{{ data_get($filters, 'q', 'N/A') ?: 'N/A' }}</span></td>
        </tr>
    </table>

    <br>

    <div class="kpi-container">
        <table class="kpi-table">
            <tr>
                <td>
                    <div class="kpi-box">
                        <div class="kpi-label">Stock Total</div>
                        <div class="kpi-value">{{ number_format((int) data_get($analytics, 'totalStock', 0)) }}</div>
                    </div>
                </td>
                <td>
                    <div class="kpi-box">
                        <div class="kpi-label">Entradas</div>
                        <div class="kpi-value">{{ number_format((int) data_get($analytics, 'totalEntries', 0)) }}</div>
                    </div>
                </td>
                <td>
                    <div class="kpi-box">
                        <div class="kpi-label">Salidas</div>
                        <div class="kpi-value">{{ number_format((int) data_get($analytics, 'totalExits', 0)) }}</div>
                    </div>
                </td>
                <td>
                    <div class="kpi-box">
                        <div class="kpi-label">Eventos Auditados</div>
                        <div class="kpi-value">{{ number_format((int) data_get($analytics, 'auditEventsCount', count($rows ?? []))) }}</div>
                    </div>
                </td>
            </tr>
        </table>
        
        <table class="kpi-table" style="margin-top: 10px;">
            <tr>
                <td>
                    <div class="kpi-box">
                        <div class="kpi-label">Stock Crítico</div>
                        <div class="kpi-value" style="color: #b91c1c;">{{ number_format((int) data_get($analytics, 'lowStockCount', 0)) }}</div>
                    </div>
                </td>
                <td>
                    <div class="kpi-box">
                        <div class="kpi-label">Picking Pendiente</div>
                        <div class="kpi-value">{{ number_format((int) data_get($analytics, 'pendingPickingCount', 0)) }}</div>
                    </div>
                </td>
                <td>
                    <div class="kpi-box">
                        <div class="kpi-label">Lotes Fast Flow</div>
                        <div class="kpi-value">{{ number_format((int) data_get($analytics, 'fastFlowCount', 0)) }}</div>
                    </div>
                </td>
                <td>
                    <div class="kpi-box">
                        <div class="kpi-label">Unidades Fast Flow</div>
                        <div class="kpi-value">{{ number_format((int) data_get($analytics, 'fastFlowAvailableUnits', 0)) }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Análisis Algorítmico</div>
        <div class="content-box" style="border-left: 4px solid #0f172a;">
            <table width="100%">
                <tr>
                    <td width="80%" valign="top">
                        <div class="ai-headline">{{ data_get($aiReport, 'headline', 'Dictamen IA') }}</div>
                    </td>
                    <td width="20%" valign="top" class="ta-right">
                        <span class="pill score">SCORE {{ (int) data_get($aiReport, 'score', 0) }}</span><br>
                        <div style="margin-top: 4px;">
                            <span class="pill {{ data_get($aiReport, 'risk_level', 'informativo') }}">
                                NIVEL: {{ data_get($aiReport, 'risk_level', 'INFO') }}
                            </span>
                        </div>
                    </td>
                </tr>
            </table>
            <div class="ai-body">
                {{ data_get($aiReport, 'direct_answer', $aiText ?: 'El motor de IA no arrojó un resultado directo para esta consulta.') }}
            </div>
        </div>
    </div>

    <table width="100%">
        <tr>
            <td width="50%" valign="top" style="padding-right: 10px;">
                <div class="section">
                    <div class="section-title">Insights Clave</div>
                    <div class="content-box" style="min-height: 120px;">
                        @if(!empty(data_get($aiReport, 'summary_points', [])))
                            <ul class="clean-list">
                                @foreach(data_get($aiReport, 'summary_points', []) as $point)
                                    <li>{{ $point }}</li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-muted mono">N/A</div>
                        @endforelse
                    </div>
                </div>
            </td>
            
            <td width="50%" valign="top" style="padding-left: 10px;">
                <div class="section">
                    <div class="section-title">Datos Probatorios Extraídos</div>
                    @if(!empty(data_get($aiReport, 'evidence', [])))
                        <table class="evidence-table">
                            @php $evidenceArray = data_get($aiReport, 'evidence', []); @endphp
                            @for ($i = 0; $i < count($evidenceArray); $i+=2)
                                <tr>
                                    <td>
                                        <div class="evidence-card">
                                            <div class="evidence-label">{{ data_get($evidenceArray[$i], 'label', 'Métrica') }}</div>
                                            <div class="evidence-value">{{ data_get($evidenceArray[$i], 'value', '') }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        @if(isset($evidenceArray[$i+1]))
                                            <div class="evidence-card">
                                                <div class="evidence-label">{{ data_get($evidenceArray[$i+1], 'label', 'Métrica') }}</div>
                                                <div class="evidence-value">{{ data_get($evidenceArray[$i+1], 'value', '') }}</div>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endfor
                        </table>
                    @else
                        <div class="content-box text-muted mono">SIN EVIDENCIA</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Matriz de Acción Sugerida</div>
        <div class="content-box">
            @if(!empty(data_get($aiReport, 'actions', [])))
                @foreach(data_get($aiReport, 'actions', []) as $row)
                    <div class="action-item">
                        <div class="action-header">
                            <span class="pill {{ data_get($row, 'priority', 'media') }}">{{ data_get($row, 'priority', 'MEDIA') }}</span>
                            <span class="text-dark" style="margin-left: 6px; font-size: 11px;">{{ data_get($row, 'title', 'Acción') }}</span>
                        </div>
                        <div class="text-muted" style="margin-top: 4px; font-size: 10px; margin-left: 2px;">
                            {{ data_get($row, 'detail', '') }}
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-muted mono">No se requiere intervención operativa inmediata.</div>
            @endif
        </div>
    </div>

    @if(!empty(data_get($aiReport, 'tables', [])))
        <div class="section">
            <div class="section-title">Estructuras de Datos (IA)</div>
            @foreach(data_get($aiReport, 'tables', []) as $table)
                <div style="margin-bottom:15px; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden;">
                    <div style="background: #f8fafc; padding: 8px 10px; font-weight: 800; font-size: 10px; border-bottom: 1px solid #e2e8f0; color: #0f172a;">
                        {{ data_get($table, 'title', 'Tabla Analítica') }}
                    </div>
                    <table class="data-table" style="margin-top: 0;">
                        <thead>
                            <tr>
                                @foreach(data_get($table, 'columns', []) as $col)
                                    <th>{{ $col }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(data_get($table, 'rows', []) as $row)
                                <tr>
                                    @foreach($row as $cell)
                                        <td class="mono">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    @endif

    @if(!empty(data_get($aiReport, 'follow_up_data_needed', [])))
        <div class="section">
            <div class="section-title">Requerimientos de Datos Adicionales</div>
            <div class="content-box text-muted" style="font-size: 10px; padding: 10px 15px;">
                <ul class="clean-list">
                    @foreach(data_get($aiReport, 'follow_up_data_needed', []) as $row)
                        <li>{{ $row }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div style="page-break-before: always;"></div>

    <div class="section">
        <div class="section-title">Registro Raw Base ({{ count($rows ?? []) }} Movimientos)</div>
        
        <table class="data-table main-grid">
            <thead>
                <tr>
                    <th width="9%">Timestamp</th>
                    <th width="6%">Grupo</th>
                    <th width="7%">Tipo</th>
                    <th width="7%">Origen</th>
                    <th width="15%">Item</th>
                    <th width="10%">SKU</th>
                    <th width="5%" class="ta-right">Qty</th>
                    <th width="8%">Fuente</th>
                    <th width="8%">Destino</th>
                    <th width="8%">Loc. Final</th>
                    <th width="7%">Usuario</th>
                    <th width="10%">Ref/Nota</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td class="mono text-muted">{{ data_get($row, 'when', '—') }}</td>
                        <td><strong>{{ strtoupper((string) data_get($row, 'group', '—')) }}</strong></td>
                        <td class="mono">{{ data_get($row, 'type', '—') }}</td>
                        <td class="mono text-muted">{{ data_get($row, 'source', '—') }}</td>
                        <td>{{ data_get($row, 'name', '—') }}</td>
                        <td class="mono">{{ data_get($row, 'sku', '—') }}</td>
                        <td class="ta-right mono" style="font-weight: bold; color: #0f172a;">{{ number_format((int) data_get($row, 'qty', 0)) }}</td>
                        <td class="mono">{{ data_get($row, 'from_location', '—') ?: '—' }}</td>
                        <td class="mono">{{ data_get($row, 'to_location', '—') ?: '—' }}</td>
                        <td class="mono">{{ data_get($row, 'location', '—') ?: '—' }}</td>
                        <td>{{ data_get($row, 'user_name', '—') ?: '—' }}</td>
                        <td class="text-xs text-muted">{{ data_get($row, 'reference', '—') }} <br> <i>{{ data_get($row, 'note', '') }}</i></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="ta-center text-muted" style="padding: 20px;">
                            Sin movimientos registrados en esta consulta.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</body>
</html>