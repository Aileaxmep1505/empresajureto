@extends('layouts.app')

@section('title', 'WMS · Auditoría Inteligente')

@section('content')
@php
    use Illuminate\Support\Collection;
    use Illuminate\Support\Facades\Route;

    $routeFirst = function (array $names, $fallback = '#') {
        foreach ($names as $name) {
            if (Route::has($name)) {
                return route($name);
            }
        }
        return $fallback;
    };

    $toArrayList = function ($value): array {
        if ($value instanceof Collection) return $value->values()->all();
        if (is_array($value)) return array_values($value);
        if (is_iterable($value)) return collect($value)->values()->all();
        return [];
    };

    $filters = $filters ?? [
        'warehouse_id' => (int) request('warehouse_id', 0),
        'period' => (int) request('period', 30),
        'q' => trim((string) request('q', '')),
        'group' => trim((string) request('group', '')),
        'source' => trim((string) request('source', '')),
        'type' => trim((string) request('type', '')),
    ];

    $auditRows = $toArrayList($auditRows ?? []);
    $activityBySource = $toArrayList($activityBySource ?? []);
    $activityByGroup = $toArrayList($activityByGroup ?? []);
    $lowStockProducts = $toArrayList($lowStockProducts ?? []);
    $fastFlowItems = $toArrayList($fastFlowItems ?? []);
    $warehouses = $toArrayList($warehouses ?? []);
    $auditSummary = $auditSummary ?? [];

    $auditUrl = Route::has('admin.wms.audit') ? route('admin.wms.audit') : '#';
    $aiUrl = Route::has('admin.wms.audit.ai') ? route('admin.wms.audit.ai') : '#';
    $pdfUrl = Route::has('admin.wms.audit.pdf') ? route('admin.wms.audit.pdf') : '#';
    $backUrl = $routeFirst(['admin.wms.home', 'admin.wms.analytics', 'admin.wms.analytics.v2']);

    $cards = [
        ['label' => 'Eventos auditados', 'value' => number_format((int)($auditSummary['total'] ?? count($auditRows))), 'tone' => 'blue'],
        ['label' => 'Entradas', 'value' => number_format((int)($auditSummary['entries'] ?? 0)), 'tone' => 'green'],
        ['label' => 'Salidas', 'value' => number_format((int)($auditSummary['exits'] ?? 0)), 'tone' => 'red'],
        ['label' => 'Transferencias', 'value' => number_format((int)($auditSummary['transfers'] ?? 0)), 'tone' => 'purple'],
        ['label' => 'Ajustes', 'value' => number_format((int)($auditSummary['adjustments'] ?? 0)), 'tone' => 'amber'],
        ['label' => 'Picking', 'value' => number_format((int)($auditSummary['pickings'] ?? 0)), 'tone' => 'cyan'],
        ['label' => 'Fast Flow activo', 'value' => number_format((int)($fastFlowCount ?? 0)), 'tone' => 'teal'],
        ['label' => 'Cantidad movida', 'value' => number_format((int)($auditSummary['total_qty'] ?? 0)), 'tone' => 'slate'],
    ];
@endphp

<div class="n-wrap">
    <header class="n-head fade-in">
        <div class="n-head-brand">
            <div class="n-logo-box">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <div>
                <h1 class="n-title">NEXUS <span class="n-gradient-text">AI ENGINE</span></h1>
                <div class="n-sub">Auditoría de alta frecuencia · WMS · Picking · Fast Flow</div>
            </div>
        </div>

        <div class="n-head-actions">
            <a href="{{ $backUrl }}" class="n-btn n-btn-outline">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Regresar
            </a>
        </div>
    </header>

    <form method="GET" action="{{ $auditUrl }}" class="n-command-bar fade-in" style="animation-delay: 0.08s;">
        <div class="n-filter-grid">
            <div class="n-field n-field-search">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Buscar por SKU, usuario, folio, referencia...">
            </div>

            <div class="n-field">
                <select name="warehouse_id">
                    <option value="0" @selected((int)($filters['warehouse_id'] ?? 0) === 0)>Todas las bodegas</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ (int) data_get($wh, 'id') }}" @selected((int)($filters['warehouse_id'] ?? 0) === (int) data_get($wh, 'id'))>
                            Bodega: {{ data_get($wh, 'code', data_get($wh, 'name')) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="n-field">
                <select name="period">
                    <option value="7" @selected((int)($filters['period'] ?? 30) === 7)>Últimos 7 días</option>
                    <option value="30" @selected((int)($filters['period'] ?? 30) === 30)>Últimos 30 días</option>
                    <option value="90" @selected((int)($filters['period'] ?? 30) === 90)>Últimos 90 días</option>
                    <option value="180" @selected((int)($filters['period'] ?? 30) === 180)>Últimos 180 días</option>
                    <option value="365" @selected((int)($filters['period'] ?? 30) === 365)>Último año</option>
                </select>
            </div>

            <div class="n-field">
                <select name="group">
                    <option value="">Grupo: Todos</option>
                    <option value="entry" @selected(($filters['group'] ?? '') === 'entry')>Entradas</option>
                    <option value="exit" @selected(($filters['group'] ?? '') === 'exit')>Salidas</option>
                    <option value="transfer" @selected(($filters['group'] ?? '') === 'transfer')>Transferencias</option>
                    <option value="adjustment" @selected(($filters['group'] ?? '') === 'adjustment')>Ajustes</option>
                    <option value="picking" @selected(($filters['group'] ?? '') === 'picking')>Picking</option>
                </select>
            </div>

            <div class="n-filter-actions">
                <button type="submit" class="n-btn n-btn-primary">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="n-bento-cards fade-in" style="animation-delay: 0.14s;">
        @foreach($cards as $card)
            <div class="n-card tone-{{ $card['tone'] }}">
                <div class="n-card-glow"></div>
                <div class="n-card-content">
                    <div class="n-card-label">{{ $card['label'] }}</div>
                    <div class="n-card-value">{{ $card['value'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="n-main-layout fade-in" style="animation-delay: 0.2s;">
        <div class="n-col-main">

            <div class="n-panel n-panel-ai">
                <div class="n-panel-head">
                    <div>
                        <div class="n-panel-title">Interfaz de Consulta Natural</div>
                        <div class="n-panel-sub">Consulta libre sobre el DataGrid activo y obtiene respuesta contextual del motor IA.</div>
                    </div>

                    <form id="auditPdfForm" method="POST" action="{{ $pdfUrl }}" target="_blank">
                        @csrf
                        <input type="hidden" name="warehouse_id" value="{{ (int) ($filters['warehouse_id'] ?? 0) }}">
                        <input type="hidden" name="period" value="{{ (int) ($filters['period'] ?? 30) }}">
                        <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
                        <input type="hidden" name="group" value="{{ $filters['group'] ?? '' }}">
                        <input type="hidden" name="source" value="{{ $filters['source'] ?? '' }}">
                        <input type="hidden" name="type" value="{{ $filters['type'] ?? '' }}">
                        <input type="hidden" name="ai_payload" id="aiPayloadInput">
                        <input type="hidden" name="ai_text" id="aiTextInput">
                        <button type="submit" class="n-btn n-btn-outline n-btn-sm">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                            </svg>
                            Exportar PDF
                        </button>
                    </form>
                </div>

                <div class="n-ai-grid">
                    <div class="n-ai-prompt-area">
                        <div class="n-textarea-wrapper">
                            <textarea id="auditAiPrompt" class="n-textarea" placeholder="¿Qué patrones anómalos existen en las salidas de esta semana? o ¿Hay riesgos en Fast Flow?"></textarea>
                            <div class="n-textarea-glow"></div>
                        </div>

                        <div class="n-ai-toolbar">
                            <button type="button" id="runAuditAiBtn" class="n-btn n-btn-gradient">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/>
                                </svg>
                                Analizar con IA
                            </button>
                            <span id="auditAiStatus" class="n-ai-status">Motor en espera...</span>
                        </div>
                    </div>

                    <div class="n-ai-result-area">
                        <div id="aiEmptyState" class="n-empty-state">
                            <div class="n-empty-icon">
                                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M12 3l2.2 4.8L19 10l-4.8 2.2L12 17l-2.2-4.8L5 10l4.8-2.2L12 3z"/>
                                </svg>
                            </div>
                            Los resultados del análisis se mostrarán aquí.
                        </div>

                        <div id="aiResultWrap" class="n-result-box" style="display:none;">
                            <div class="n-result-header">
                                <div>
                                    <h3 id="aiHeadline" class="n-result-title"></h3>
                                    <p id="aiDirectAnswer" class="n-result-sub"></p>
                                </div>

                                <div class="n-score-badge">
                                    <span id="aiRiskLevel" class="n-score-lbl"></span>
                                    <span id="aiScore" class="n-score-val"></span>
                                </div>
                            </div>

                            <div class="n-result-body">
                                <div class="n-result-col">
                                    <div class="n-section-title">Insights Clave</div>
                                    <ul id="aiSummaryPoints" class="n-list"></ul>
                                </div>

                                <div class="n-result-col">
                                    <div class="n-section-title">Evidencia Detectada</div>
                                    <div id="aiEvidence" class="n-evidence-grid"></div>
                                </div>
                            </div>

                            <div class="n-section-title" style="margin-top:1.35rem;">Estructuras de Datos</div>
                            <div id="aiTables" class="n-tables-stack"></div>

                            <div class="n-section-title" style="margin-top:1.35rem;">Matriz de Acción</div>
                            <div id="aiActions" class="n-actions-stack"></div>

                            <div class="n-section-title" style="margin-top:1.35rem;">Requerimientos Futuros</div>
                            <ul id="aiFollowUp" class="n-list n-list-muted"></ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="n-panel">
                <div class="n-panel-head">
                    <div>
                        <div class="n-panel-title">DataGrid · Movimientos Base</div>
                    </div>
                    <div class="n-badge-counter">{{ number_format(count($auditRows)) }} ROWS</div>
                </div>

                @if(count($auditRows))
                    <div class="n-table-container">
                        <table class="n-table">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Contexto</th>
                                    <th>Producto / SKU</th>
                                    <th class="ta-right">Qty</th>
                                    <th>Trayectoria</th>
                                    <th>Operador</th>
                                    <th>Ref</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($auditRows as $row)
                                    @php
                                        $group = strtolower((string) data_get($row, 'group', 'other'));
                                        $groupClass = match($group){
                                            'entry' => 'c-green',
                                            'exit' => 'c-red',
                                            'transfer' => 'c-purple',
                                            'adjustment' => 'c-amber',
                                            'picking' => 'c-cyan',
                                            default => 'c-slate',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="t-mono t-muted">{{ data_get($row, 'when', '—') }}</td>
                                        <td>
                                            <span class="n-tag {{ $groupClass }}">{{ strtoupper($group) }}</span>
                                            <div class="t-micro t-muted mt-1">{{ data_get($row, 'type', '—') }}</div>
                                        </td>
                                        <td>
                                            <div class="t-strong">{{ data_get($row, 'name', '—') }}</div>
                                            <div class="t-mono t-micro t-muted">{{ data_get($row, 'sku', '—') }}</div>
                                        </td>
                                        <td class="ta-right t-nums t-strong">{{ number_format((int) data_get($row, 'qty', 0)) }}</td>
                                        <td class="t-mono t-micro">
                                            <span class="n-loc">{{ data_get($row, 'from_location', '—') ?: '—' }}</span>
                                            <span class="t-muted mx-1">&rarr;</span>
                                            <span class="n-loc">{{ data_get($row, 'to_location', '—') ?: '—' }}</span>
                                        </td>
                                        <td class="t-micro t-strong">{{ data_get($row, 'user_name', '—') ?: '—' }}</td>
                                        <td class="t-mono t-micro t-muted">{{ data_get($row, 'reference', '—') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="n-empty-state border-none">No existen registros bajo estos parámetros de filtro.</div>
                @endif
            </div>
        </div>

        <div class="n-col-side">
            <div class="n-panel n-panel-side">
                <div class="n-panel-head n-panel-head-side">
                    <div class="n-panel-title">Agrupación de actividad</div>
                </div>

                <div class="n-side-list">
                    @forelse($activityByGroup as $item)
                        <div class="n-side-row minimal">
                            <div class="n-side-left">
                                <span class="n-side-primary">{{ strtoupper((string) data_get($item, 'name', '—')) }}</span>
                                <span class="n-side-secondary">Eventos detectados</span>
                            </div>
                            <span class="n-side-value">{{ number_format((int) data_get($item, 'count', 0)) }}</span>
                        </div>
                    @empty
                        <div class="n-empty-mini">N/A</div>
                    @endforelse
                </div>
            </div>

            <div class="n-panel n-panel-side">
                <div class="n-panel-head n-panel-head-side">
                    <div class="n-panel-title">Alerta stock bajo</div>
                </div>

                <div class="n-side-list">
                    @forelse(array_slice($lowStockProducts, 0, 6) as $item)
                        <div class="n-side-row minimal">
                            <div class="n-side-left">
                                <span class="n-side-primary truncate" style="max-width:170px;">{{ data_get($item, 'name', '—') }}</span>
                                <span class="n-side-secondary t-mono">{{ data_get($item, 'sku', '—') }}</span>
                            </div>
                            <span class="n-side-pill danger">{{ (int) data_get($item, 'stock', 0) }}/{{ (int) data_get($item, 'min_stock', 0) }}</span>
                        </div>
                    @empty
                        <div class="n-empty-mini">Inventario estable.</div>
                    @endforelse
                </div>
            </div>

            <div class="n-panel n-panel-side">
                <div class="n-panel-head n-panel-head-side">
                    <div class="n-panel-title">Fast Flow activo</div>
                </div>

                <div class="n-side-list">
                    @forelse(array_slice($fastFlowItems, 0, 6) as $item)
                        <div class="n-side-row minimal">
                            <div class="n-side-left">
                                <span class="n-side-primary t-mono">{{ data_get($item, 'batch_code', '—') }}</span>
                                <span class="n-side-secondary truncate" style="max-width:170px;">{{ data_get($item, 'product_name', '—') }}</span>
                            </div>
                            <span class="n-side-pill teal">{{ (int) data_get($item, 'available_units', 0) }}</span>
                        </div>
                    @empty
                        <div class="n-empty-mini">Sin lotes en Fast Flow.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap');

    :root {
        --bg-body: #f4f7fb;
        --bg-panel: #ffffff;
        --bg-soft: #f8fafc;
        --border-soft: #e2e8f0;
        --border-hard: #cbd5e1;

        --text-main: #0f172a;
        --text-muted: #64748b;
        --text-soft: #94a3b8;
        --text-inverse: #ffffff;

        --v-blue: #2563eb;   --bg-blue: #eff6ff;   --glow-blue: rgba(37,99,235,.20);
        --v-green: #059669;  --bg-green: #ecfdf5;  --glow-green: rgba(5,150,105,.18);
        --v-red: #dc2626;    --bg-red: #fef2f2;    --glow-red: rgba(220,38,38,.18);
        --v-purple: #7c3aed; --bg-purple: #f5f3ff; --glow-purple: rgba(124,58,237,.18);
        --v-amber: #d97706;  --bg-amber: #fffbeb;  --glow-amber: rgba(217,119,6,.18);
        --v-cyan: #0891b2;   --bg-cyan: #ecfeff;   --glow-cyan: rgba(8,145,178,.18);
        --v-teal: #0f766e;   --bg-teal: #f0fdfa;   --glow-teal: rgba(15,118,110,.18);
        --v-slate: #334155;  --bg-slate: #f8fafc;  --glow-slate: rgba(51,65,85,.16);

        --ai-gradient: linear-gradient(135deg, #0f172a 0%, #1d4ed8 58%, #0ea5e9 100%);
        --ai-gradient-soft: linear-gradient(135deg, rgba(15,23,42,.02), rgba(37,99,235,.05), rgba(14,165,233,.06));

        --shadow-sm: 0 2px 6px rgba(15, 23, 42, 0.04);
        --shadow-md: 0 16px 32px rgba(15, 23, 42, 0.06);
        --shadow-lg: 0 22px 40px rgba(15, 23, 42, 0.08);

        --font-sans: 'Inter', sans-serif;
        --font-mono: 'JetBrains Mono', monospace;
    }

    body {
        background: var(--bg-body);
        color: var(--text-main);
        font-family: var(--font-sans);
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .fade-in {
        animation: slideUp 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        opacity: 0;
    }

    .t-mono { font-family: var(--font-mono); }
    .t-nums { font-feature-settings: "tnum"; font-variant-numeric: tabular-nums; }
    .t-micro { font-size: 0.75rem; }
    .t-strong { font-weight: 600; }
    .t-muted { color: var(--text-muted); }
    .ta-right { text-align: right; }
    .mt-1 { margin-top: 0.25rem; }
    .mx-1 { margin: 0 0.25rem; }
    .uppercase { text-transform: uppercase; letter-spacing: 0.05em; }
    .truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
    .flex-col { display: flex; flex-direction: column; }

    .n-wrap {
        max-width: 1560px;
        margin: 0 auto;
        padding: 2rem 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .n-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .n-head-brand {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .n-logo-box {
        background: linear-gradient(135deg, #0f172a, #1e293b);
        color: white;
        border-radius: 14px;
        padding: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 20px rgba(15,23,42,.12);
    }

    .n-title {
        font-size: 1.52rem;
        font-weight: 800;
        margin: 0;
        letter-spacing: -0.02em;
    }

    .n-gradient-text {
        background: var(--ai-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .n-sub {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-top: 0.2rem;
    }

    .n-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.2rem;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s ease;
        border: 1px solid transparent;
        text-decoration: none;
    }

    .n-btn:active { transform: scale(0.98); }

    .n-btn-outline {
        background: var(--bg-panel);
        border-color: var(--border-soft);
        color: var(--text-main);
    }

    .n-btn-outline:hover {
        background: var(--bg-soft);
        border-color: var(--border-hard);
    }

    .n-btn-primary {
        background: var(--text-main);
        color: var(--text-inverse);
        box-shadow: 0 8px 18px rgba(15,23,42,.10);
    }

    .n-btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 22px rgba(15,23,42,.14);
    }

    .n-btn-gradient {
        background: var(--ai-gradient);
        color: white;
        border: none;
        box-shadow: 0 12px 24px rgba(29,78,216,.22);
    }

    .n-btn-gradient:hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 30px rgba(29,78,216,.28);
    }

    .n-btn-gradient:disabled {
        opacity: .75;
        cursor: wait;
    }

    .n-btn-sm {
        padding: 0.45rem 0.85rem;
        font-size: 0.76rem;
    }

    .n-command-bar {
        background: var(--bg-panel);
        border: 1px solid var(--border-soft);
        border-radius: 16px;
        padding: 0.55rem 1rem;
        box-shadow: var(--shadow-sm);
    }

    .n-filter-grid {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .n-field {
        display: flex;
        align-items: center;
        position: relative;
    }

    .n-field::after {
        content: '';
        position: absolute;
        right: -0.5rem;
        top: 18%;
        height: 64%;
        width: 1px;
        background: var(--border-soft);
    }

    .n-field:last-of-type::after { display: none; }

    .n-field select,
    .n-field input {
        border: none;
        background: transparent;
        padding: 0.65rem 0.6rem;
        font-size: 0.85rem;
        color: var(--text-main);
        outline: none;
        cursor: pointer;
        font-family: var(--font-sans);
    }

    .n-field-search {
        flex: 1;
        min-width: 250px;
        color: var(--text-muted);
    }

    .n-field-search input {
        width: 100%;
        padding-left: 0.5rem;
        cursor: text;
    }

    .n-filter-actions {
        margin-left: auto;
    }

    .n-bento-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(185px, 1fr));
        gap: 1rem;
    }

    .n-card {
        position: relative;
        background: var(--bg-panel);
        border: 1px solid var(--border-soft);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        transition: transform 0.22s ease, box-shadow 0.22s ease;
        min-height: 118px;
    }

    .n-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
    }

    .n-card-glow {
        position: absolute;
        inset: 0;
        opacity: .85;
        z-index: 1;
        transition: opacity .25s ease;
    }

    .n-card:hover .n-card-glow {
        opacity: 1;
    }

    .n-card-content {
        position: relative;
        z-index: 2;
        padding: 1.3rem 1.35rem;
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
    }

    .n-card-label {
        font-size: 0.77rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .n-card-value {
        font-size: 1.9rem;
        font-weight: 800;
        font-family: var(--font-mono);
        color: var(--text-main);
        line-height: 1;
    }

    .tone-blue .n-card-glow { background: linear-gradient(135deg, rgba(37,99,235,.08), rgba(37,99,235,.02) 55%, transparent 100%); }
    .tone-green .n-card-glow { background: linear-gradient(135deg, rgba(5,150,105,.08), rgba(5,150,105,.02) 55%, transparent 100%); }
    .tone-red .n-card-glow { background: linear-gradient(135deg, rgba(220,38,38,.08), rgba(220,38,38,.02) 55%, transparent 100%); }
    .tone-purple .n-card-glow { background: linear-gradient(135deg, rgba(124,58,237,.08), rgba(124,58,237,.02) 55%, transparent 100%); }
    .tone-amber .n-card-glow { background: linear-gradient(135deg, rgba(217,119,6,.08), rgba(217,119,6,.02) 55%, transparent 100%); }
    .tone-cyan .n-card-glow { background: linear-gradient(135deg, rgba(8,145,178,.08), rgba(8,145,178,.02) 55%, transparent 100%); }
    .tone-teal .n-card-glow { background: linear-gradient(135deg, rgba(15,118,110,.08), rgba(15,118,110,.02) 55%, transparent 100%); }
    .tone-slate .n-card-glow { background: linear-gradient(135deg, rgba(51,65,85,.08), rgba(51,65,85,.02) 55%, transparent 100%); }

    .n-main-layout {
        display: grid;
        grid-template-columns: minmax(0, 3fr) 0.98fr;
        gap: 1.5rem;
        align-items: start;
    }

    .n-col-main,
    .n-col-side {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .n-panel {
        background: var(--bg-panel);
        border: 1px solid var(--border-soft);
        border-radius: 18px;
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }

    .n-panel-head {
        padding: 1.2rem 1.4rem;
        border-bottom: 1px solid var(--border-soft);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        background: #fff;
    }

    .n-panel-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-main);
    }

    .n-panel-sub {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-top: 0.2rem;
    }

    .n-badge-counter {
        font-family: var(--font-mono);
        font-size: 0.7rem;
        font-weight: 700;
        background: var(--bg-slate);
        border: 1px solid var(--border-soft);
        padding: 0.25rem 0.6rem;
        border-radius: 999px;
        color: var(--text-main);
    }

    .n-panel-ai {
        border: 1px solid rgba(37,99,235,.18);
        background:
            linear-gradient(var(--bg-panel), var(--bg-panel)) padding-box,
            linear-gradient(135deg, rgba(15,23,42,.24), rgba(37,99,235,.28), rgba(14,165,233,.20)) border-box;
    }

    .n-ai-grid {
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        gap: 0;
    }

    .n-ai-prompt-area {
        padding: 1.45rem;
        border-right: 1px solid var(--border-soft);
        display: flex;
        flex-direction: column;
        gap: 1rem;
        background: var(--ai-gradient-soft);
    }

    .n-textarea-wrapper {
        position: relative;
        flex: 1;
        display: flex;
    }

    .n-textarea {
        width: 100%;
        min-height: 125px;
        padding: 1rem;
        border-radius: 14px;
        border: 1px solid var(--border-soft);
        background: rgba(255,255,255,0.94);
        font-family: var(--font-sans);
        font-size: 0.92rem;
        resize: none;
        outline: none;
        z-index: 2;
        transition: border 0.2s ease, box-shadow 0.2s ease;
        backdrop-filter: blur(4px);
    }

    .n-textarea:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 4px rgba(37,99,235,.10);
    }

    .n-textarea-glow {
        position: absolute;
        inset: -2px;
        background: linear-gradient(135deg, rgba(15,23,42,.25), rgba(37,99,235,.35), rgba(14,165,233,.25));
        filter: blur(14px);
        opacity: 0.08;
        z-index: 1;
        transition: opacity 0.3s ease;
        border-radius: 16px;
    }

    .n-textarea:focus + .n-textarea-glow {
        opacity: 0.22;
    }

    .n-ai-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .n-ai-status {
        font-size: 0.76rem;
        color: var(--text-muted);
        font-family: var(--font-mono);
    }

    .n-ai-result-area {
        padding: 1.45rem;
        background: var(--bg-soft);
    }

    .n-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 220px;
        color: var(--text-muted);
        font-size: 0.92rem;
        text-align: center;
        border: 1px dashed var(--border-hard);
        border-radius: 14px;
        background: rgba(255,255,255,.55);
    }

    .border-none { border: none !important; }

    .n-empty-icon {
        margin-bottom: 0.7rem;
        opacity: 0.55;
        color: #2563eb;
    }

    .n-result-box {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .n-result-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 0.2rem;
    }

    .n-result-title {
        font-size: 1.14rem;
        font-weight: 800;
        margin: 0;
        color: var(--text-main);
    }

    .n-result-sub {
        font-size: 0.9rem;
        color: var(--text-muted);
        margin: 0.5rem 0 0 0;
        line-height: 1.6;
    }

    .n-score-badge {
        min-width: 104px;
        background: #fff;
        border: 1px solid var(--border-soft);
        padding: 0.65rem 0.95rem;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        box-shadow: var(--shadow-sm);
    }

    .n-score-val {
        font-size: 1.45rem;
        font-weight: 800;
        color: var(--v-blue);
        font-family: var(--font-mono);
        line-height: 1;
        margin-top: 0.25rem;
    }

    .n-score-lbl {
        font-size: 0.66rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: 0.05em;
    }

    .n-result-body {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.4rem;
    }

    .n-section-title {
        font-size: 0.76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-main);
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .n-section-title::before {
        content: '';
        width: 6px;
        height: 6px;
        background: var(--ai-gradient);
        border-radius: 50%;
    }

    .n-list {
        margin: 0;
        padding-left: 1.2rem;
        font-size: 0.86rem;
        color: var(--text-main);
        line-height: 1.6;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

    .n-list-muted { color: var(--text-muted); }

    .n-evidence-grid {
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
    }

    .audit-evidence-card {
        background: #fff;
        border: 1px solid var(--border-soft);
        padding: 0.7rem 0.85rem;
        border-radius: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .8rem;
    }

    .audit-evidence-label {
        font-size: 0.76rem;
        color: var(--text-muted);
    }

    .audit-evidence-value {
        font-size: 0.85rem;
        font-weight: 600;
        font-family: var(--font-mono);
        color: var(--text-main);
        text-align: right;
    }

    .n-tables-stack,
    .n-actions-stack {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .audit-ai-table-card,
    .audit-action-card {
        background: #fff;
        border: 1px solid var(--border-soft);
        border-radius: 12px;
        padding: 1rem;
        overflow: hidden;
    }

    .audit-answer-section-title {
        font-size: 0.8rem;
        font-weight: 600;
        margin-bottom: 0.8rem;
        color: var(--text-main);
    }

    .audit-subtable {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 0.8rem;
    }

    .audit-subtable th {
        background: var(--bg-soft);
        padding: 0.55rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        font-size: 0.7rem;
        border-bottom: 1px solid var(--border-soft);
    }

    .audit-subtable td {
        padding: 0.65rem 0.55rem;
        border-bottom: 1px solid var(--border-soft);
        font-family: var(--font-mono);
    }

    .audit-action-title {
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--text-main);
    }

    .audit-action-priority {
        display: inline-block;
        padding: 0.24rem 0.55rem;
        border-radius: 999px;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        margin-top: 0.45rem;
    }

    .audit-action-priority.alta { background: var(--bg-red); color: var(--v-red); }
    .audit-action-priority.media { background: var(--bg-amber); color: var(--v-amber); }
    .audit-action-priority.baja { background: var(--bg-green); color: var(--v-green); }

    .audit-action-detail {
        margin-top: 0.55rem;
        font-size: 0.85rem;
        color: var(--text-muted);
        line-height: 1.55;
    }

    .n-table-container {
        overflow-x: auto;
        max-height: 600px;
    }

    .n-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        min-width: 900px;
        text-align: left;
    }

    .n-table th {
        position: sticky;
        top: 0;
        background: rgba(255,255,255,.96);
        backdrop-filter: blur(8px);
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted);
        padding: 0.85rem 1.25rem;
        border-bottom: 1px solid var(--border-soft);
        z-index: 10;
        font-weight: 600;
    }

    .n-table td {
        padding: 0.82rem 1.25rem;
        border-bottom: 1px solid var(--border-soft);
        font-size: 0.85rem;
        vertical-align: middle;
        background: #fff;
    }

    .n-table tbody tr:hover td {
        background-color: #f8fbff;
    }

    .n-loc {
        background: var(--bg-slate);
        border: 1px solid var(--border-soft);
        padding: 0.12rem 0.42rem;
        border-radius: 6px;
    }

    .n-tag {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.25rem 0.6rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
    }

    .n-tag-outline {
        background: transparent;
        border: 1px solid var(--border-soft);
        color: var(--text-main);
    }

    .c-green { background: var(--bg-green); color: var(--v-green); border: 1px solid #a7f3d0; }
    .c-red { background: var(--bg-red); color: var(--v-red); border: 1px solid #fecaca; }
    .c-purple { background: var(--bg-purple); color: var(--v-purple); border: 1px solid #ddd6fe; }
    .c-amber { background: var(--bg-amber); color: var(--v-amber); border: 1px solid #fde68a; }
    .c-cyan { background: var(--bg-cyan); color: var(--v-cyan); border: 1px solid #a5f3fc; }
    .c-teal { background: var(--bg-teal); color: var(--v-teal); border: 1px solid #99f6e4; }
    .c-slate { background: var(--bg-slate); color: var(--v-slate); border: 1px solid #e2e8f0; }

    .n-panel-side {
        border-radius: 16px;
        box-shadow: var(--shadow-sm);
    }

    .n-panel-head-side {
        padding: 1rem 1.15rem;
        background: #fff;
    }

    .n-side-list {
        display: flex;
        flex-direction: column;
        padding: 0.25rem 0;
    }

    .n-side-row.minimal {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 0.9rem 1.15rem;
        border-bottom: 1px solid #eef2f7;
        background: #fff;
        transition: background .18s ease;
    }

    .n-side-row.minimal:hover {
        background: #fafcff;
    }

    .n-side-row.minimal:last-child {
        border-bottom: none;
    }

    .n-side-left {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 0.18rem;
    }

    .n-side-primary {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--text-main);
        line-height: 1.35;
    }

    .n-side-secondary {
        font-size: 0.7rem;
        color: var(--text-muted);
        line-height: 1.3;
    }

    .n-side-value {
        min-width: 34px;
        height: 30px;
        padding: 0 0.65rem;
        border-radius: 10px;
        background: #f8fafc;
        border: 1px solid var(--border-soft);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--text-main);
        font-family: var(--font-mono);
    }

    .n-side-pill {
        min-width: 54px;
        height: 30px;
        padding: 0 0.7rem;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.76rem;
        font-weight: 700;
        font-family: var(--font-mono);
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .n-side-pill.danger {
        background: var(--bg-red);
        color: var(--v-red);
        border-color: #fecaca;
    }

    .n-side-pill.teal {
        background: var(--bg-teal);
        color: var(--v-teal);
        border-color: #99f6e4;
    }

    .n-empty-mini {
        padding: 1rem 1.15rem;
        font-size: 0.8rem;
        color: var(--text-muted);
    }

    @media (max-width: 1200px) {
        .n-main-layout { grid-template-columns: 1fr; }
    }

    @media (max-width: 992px) {
        .n-ai-grid { grid-template-columns: 1fr; }
        .n-ai-prompt-area {
            border-right: none;
            border-bottom: 1px solid var(--border-soft);
        }
    }

    @media (max-width: 768px) {
        .n-result-body { grid-template-columns: 1fr; }
        .n-filter-grid {
            flex-direction: column;
            align-items: stretch;
        }
        .n-field::after { display: none; }
        .n-field {
            border-bottom: 1px solid var(--border-soft);
        }
        .n-filter-actions {
            margin-top: 1rem;
            margin-left: 0;
        }
        .n-head {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>
@endpush

@push('scripts')
<script>
(function(){
    const aiUrl = @json($aiUrl);
    const csrf = @json(csrf_token());

    const promptEl = document.getElementById('auditAiPrompt');
    const runBtn = document.getElementById('runAuditAiBtn');
    const statusEl = document.getElementById('auditAiStatus');

    const emptyEl = document.getElementById('aiEmptyState');
    const resultWrap = document.getElementById('aiResultWrap');

    const headlineEl = document.getElementById('aiHeadline');
    const directAnswerEl = document.getElementById('aiDirectAnswer');
    const scoreEl = document.getElementById('aiScore');
    const riskLevelEl = document.getElementById('aiRiskLevel');
    const summaryEl = document.getElementById('aiSummaryPoints');
    const evidenceEl = document.getElementById('aiEvidence');
    const tablesEl = document.getElementById('aiTables');
    const actionsEl = document.getElementById('aiActions');
    const followUpEl = document.getElementById('aiFollowUp');

    const aiPayloadInput = document.getElementById('aiPayloadInput');
    const aiTextInput = document.getElementById('aiTextInput');

    function esc(str){
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderList(el, items, emptyText){
        el.innerHTML = '';
        if (!Array.isArray(items) || !items.length) {
            el.innerHTML = '<li>' + esc(emptyText) + '</li>';
            return;
        }
        items.forEach(item => {
            const li = document.createElement('li');
            li.textContent = item || '';
            el.appendChild(li);
        });
    }

    function renderEvidence(rows){
        evidenceEl.innerHTML = '';
        if (!Array.isArray(rows) || !rows.length) {
            evidenceEl.innerHTML = '<div class="n-empty-mini" style="padding:0;">Sin evidencia extraída.</div>';
            return;
        }

        rows.forEach(row => {
            evidenceEl.insertAdjacentHTML('beforeend', `
                <div class="audit-evidence-card">
                    <div class="audit-evidence-label">${esc(row.label || 'Dato')}</div>
                    <div class="audit-evidence-value">${esc(row.value || '')}</div>
                </div>
            `);
        });
    }

    function renderTables(tables){
        tablesEl.innerHTML = '';

        if (!Array.isArray(tables) || !tables.length) {
            tablesEl.innerHTML = '<div class="n-empty-mini" style="padding:0;">Sin estructuras de datos anexas.</div>';
            return;
        }

        tables.forEach(table => {
            const columns = Array.isArray(table.columns) ? table.columns : [];
            const rows = Array.isArray(table.rows) ? table.rows : [];

            let headHtml = '';
            columns.forEach(col => { headHtml += `<th>${esc(col)}</th>`; });

            let bodyHtml = '';
            rows.forEach(row => {
                bodyHtml += '<tr>';
                (Array.isArray(row) ? row : []).forEach(cell => { bodyHtml += `<td>${esc(cell)}</td>`; });
                bodyHtml += '</tr>';
            });

            tablesEl.insertAdjacentHTML('beforeend', `
                <div class="audit-ai-table-card">
                    <div class="audit-answer-section-title">${esc(table.title || 'Tabla DataGrid')}</div>
                    <div style="overflow-x:auto;">
                        <table class="audit-subtable">
                            <thead><tr>${headHtml}</tr></thead>
                            <tbody>${bodyHtml}</tbody>
                        </table>
                    </div>
                </div>
            `);
        });
    }

    function renderActions(actions){
        actionsEl.innerHTML = '';

        if (!Array.isArray(actions) || !actions.length) {
            actionsEl.innerHTML = '<div class="n-empty-mini" style="padding:0;">Operación estable. No requiere intervención inmediata.</div>';
            return;
        }

        actions.forEach(action => {
            const priority = String(action.priority || 'media').toLowerCase();
            actionsEl.insertAdjacentHTML('beforeend', `
                <div class="audit-action-card">
                    <div class="audit-action-title">${esc(action.title || 'Acción Estratégica')}</div>
                    <div class="audit-action-priority ${esc(priority)}">${esc(priority)}</div>
                    <div class="audit-action-detail">${esc(action.detail || '')}</div>
                </div>
            `);
        });
    }

    function renderAiResult(data){
        emptyEl.style.display = 'none';
        resultWrap.style.display = 'flex';

        headlineEl.textContent = data.headline || 'Análisis completado';
        directAnswerEl.textContent = data.direct_answer || '';
        scoreEl.textContent = Number(data.score || 0);
        riskLevelEl.textContent = String(data.risk_level || 'informativo').toUpperCase();

        renderList(summaryEl, data.summary_points || [], 'Sin insights clave.');
        renderEvidence(data.evidence || []);
        renderTables(data.tables || []);
        renderActions(data.actions || []);
        renderList(followUpEl, data.follow_up_data_needed || [], 'Datos suficientes analizados.');

        aiPayloadInput.value = JSON.stringify(data);
        aiTextInput.value = data.raw_text || '';
    }

    if (runBtn) {
        runBtn.addEventListener('click', async function(){
            const prompt = (promptEl.value || '').trim();
            if (!prompt) {
                statusEl.textContent = 'Requiere input (Prompt) para ejecutar.';
                promptEl.focus();
                return;
            }

            runBtn.disabled = true;
            statusEl.textContent = 'Procesando algoritmo...';
            statusEl.style.color = 'var(--v-blue)';

            const formData = new FormData();
            formData.append('_token', csrf);
            formData.append('warehouse_id', @json((int)($filters['warehouse_id'] ?? 0)));
            formData.append('period', @json((int)($filters['period'] ?? 30)));
            formData.append('q', @json($filters['q'] ?? ''));
            formData.append('group', @json($filters['group'] ?? ''));
            formData.append('source', @json($filters['source'] ?? ''));
            formData.append('type', @json($filters['type'] ?? ''));
            formData.append('prompt', prompt);

            try {
                const response = await fetch(aiUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const json = await response.json();

                if (!response.ok || !json.ok) {
                    throw new Error(json.error || 'Fallo en la respuesta del motor IA.');
                }

                renderAiResult(json.data || {});
                statusEl.textContent = 'Ejecución exitosa.';
                statusEl.style.color = 'var(--v-green)';
            } catch (error) {
                statusEl.textContent = error.message || 'Error de procesamiento.';
                statusEl.style.color = 'var(--v-red)';
            } finally {
                runBtn.disabled = false;
            }
        });
    }
})();
</script>
@endpush