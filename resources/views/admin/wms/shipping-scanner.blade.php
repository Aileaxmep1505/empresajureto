@extends('layouts.app')

@section('title', 'WMS · Scanner de embarque')

@section('content')
@php
    $homeUrl    = \Illuminate\Support\Facades\Route::has('admin.wms.home') ? route('admin.wms.home') : '#';
    $indexUrl   = route('admin.wms.shipping.index');
    $detailUrl  = route('admin.wms.shipping.show', $shipment['id']);
    $scanUrl    = route('admin.wms.shipping.scan', $shipment['id']);
    $closeUrl   = route('admin.wms.shipping.close', $shipment['id']);
    $dispatchUrl= route('admin.wms.shipping.dispatch', $shipment['id']);
    $cancelUrl  = route('admin.wms.shipping.cancel', $shipment['id']);
@endphp

<div class="ss-wrap">
    <div class="ss-head">
        <div class="ss-head-left">
            <a href="{{ $homeUrl }}" class="ss-btn ss-btn-ghost">← WMS</a>
            <a href="{{ $indexUrl }}" class="ss-btn ss-btn-ghost">Embarques</a>
            <a href="{{ $detailUrl }}" class="ss-btn ss-btn-ghost">Detalle</a>

            <div>
                <div class="ss-title">Scanner de embarque</div>
                <div class="ss-sub">
                    {{ $shipment['shipment_number'] ?? 'Embarque' }} · Pedido {{ $shipment['order_number'] ?: 'Sin pedido' }} · Picking {{ $shipment['task_number'] ?: 'Sin task number' }}
                </div>
            </div>
        </div>

        <div class="ss-head-right">
            <div class="ss-pill">
                <span>Operador</span>
                <strong>{{ $operatorName ?? 'Operador' }}</strong>
            </div>
            <div class="ss-pill" id="ship-status-pill">
                <span>Estado</span>
                <strong id="ship-status-text">—</strong>
            </div>
        </div>
    </div>

    <div class="ss-top-grid">
        <div class="ss-panel">
            <div class="ss-panel-title">Escaneo de carga</div>

            <div class="ss-scan-form">
                <div class="ss-field ss-field-grow">
                    <label class="ss-label">Código</label>
                    <input
                        id="scan-code"
                        type="text"
                        class="ss-input ss-input-lg"
                        placeholder="Escanea etiqueta, SKU, lote o código">
                </div>

                <div class="ss-field ss-field-sm">
                    <label class="ss-label">Cantidad</label>
                    <input
                        id="scan-qty"
                        type="number"
                        min="1"
                        step="1"
                        value="1"
                        class="ss-input">
                </div>

                <div class="ss-form-actions">
                    <button type="button" class="ss-btn" id="scan-btn">Escanear</button>
                </div>
            </div>

            <div class="ss-inline-actions">
                <button type="button" class="ss-btn ss-btn-ghost" id="focus-btn">Enfocar scanner</button>
                <button type="button" class="ss-btn ss-btn-ghost" id="reload-btn">Recargar vista</button>
                <button type="button" class="ss-btn ss-btn-danger" id="cancel-btn">Cancelar embarque</button>
            </div>

            <div id="scan-message" class="ss-message" style="display:none;"></div>
        </div>

        <div class="ss-panel">
            <div class="ss-panel-title">Resumen de unidad</div>
            <div class="ss-summary-grid" id="summary-grid"></div>
        </div>
    </div>

    <div class="ss-kpis" id="kpi-grid"></div>

    <div class="ss-main-grid">
        <div class="ss-panel">
            <div class="ss-panel-head">
                <div>
                    <div class="ss-panel-title">Líneas por fase</div>
                    <div class="ss-panel-sub">Carga esperada contra carga real y faltantes justificables.</div>
                </div>
            </div>

            <div id="phase-lines"></div>
        </div>

        <div class="ss-side-stack">
            <div class="ss-panel">
                <div class="ss-panel-title">Firma de cierre</div>

                <div class="ss-field">
                    <label class="ss-label">Nombre de quien firma</label>
                    <input id="sign-name" type="text" class="ss-input" placeholder="Nombre completo">
                </div>

                <div class="ss-field">
                    <label class="ss-label">Rol / cargo</label>
                    <input id="sign-role" type="text" class="ss-input" placeholder="Supervisor de embarque / chofer / operador">
                </div>

                <div class="ss-field">
                    <label class="ss-label">Firma</label>
                    <div class="ss-sign-wrap">
                        <canvas id="signature-pad" width="700" height="240"></canvas>
                    </div>
                </div>

                <div class="ss-inline-actions">
                    <button type="button" class="ss-btn ss-btn-ghost" id="clear-sign-btn">Limpiar firma</button>
                </div>
            </div>

            <div class="ss-panel">
                <div class="ss-panel-title">Cierre y despacho</div>

                <div class="ss-field">
                    <label class="ss-label">Observaciones finales</label>
                    <textarea id="close-notes" class="ss-textarea" placeholder="Observaciones del cierre de embarque"></textarea>
                </div>

                <div class="ss-close-info">
                    Si existen faltantes, debes capturar un motivo por cada línea incompleta antes de cerrar.
                </div>

                <div class="ss-action-stack">
                    <button type="button" class="ss-btn" id="close-btn">Cerrar embarque</button>
                    <button type="button" class="ss-btn ss-btn-indigo" id="dispatch-btn">Marcar salida de unidad</button>
                </div>
            </div>

            <div class="ss-panel">
                <div class="ss-panel-title">Últimos escaneos</div>
                <div id="recent-scans" class="ss-scans"></div>
            </div>
        </div>
    </div>
</div>

<style>
.ss-wrap{ padding:24px; display:grid; gap:18px; }
.ss-head{
    display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;
}
.ss-head-left{ display:flex; gap:10px; align-items:flex-start; flex-wrap:wrap; }
.ss-head-right{ display:flex; gap:10px; flex-wrap:wrap; }
.ss-title{ font-size:28px; font-weight:800; letter-spacing:-.03em; color:#0f172a; }
.ss-sub{ margin-top:6px; color:#64748b; }
.ss-pill{
    min-width:180px; border:1px solid #e2e8f0; background:#fff; border-radius:16px; padding:12px 14px;
    box-shadow:0 10px 30px rgba(15,23,42,.05);
}
.ss-pill span{
    display:block; font-size:12px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.08em;
}
.ss-pill strong{
    display:block; margin-top:6px; color:#0f172a; font-size:15px;
}
.ss-top-grid{
    display:grid; grid-template-columns:1.2fr .8fr; gap:18px;
}
.ss-main-grid{
    display:grid; grid-template-columns:1.2fr .8fr; gap:18px;
    align-items:start;
}
.ss-side-stack{
    display:grid; gap:18px;
}
.ss-panel{
    background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:18px;
    box-shadow:0 12px 34px rgba(15,23,42,.06);
}
.ss-panel-head{
    display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:14px;
}
.ss-panel-title{ font-size:18px; font-weight:800; color:#0f172a; }
.ss-panel-sub{ margin-top:4px; color:#64748b; }
.ss-scan-form{
    display:grid; grid-template-columns:minmax(0,1fr) 130px auto; gap:12px; align-items:end;
}
.ss-form-actions, .ss-field{ display:grid; gap:8px; }
.ss-field-grow{ min-width:0; }
.ss-field-sm{ width:130px; }
.ss-label{
    font-size:12px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.08em;
}
.ss-input, .ss-textarea{
    width:100%; border:1px solid #cbd5e1; border-radius:12px; background:#fff; color:#0f172a; outline:none;
    padding:0 14px;
}
.ss-input{ height:46px; }
.ss-input-lg{ height:52px; font-size:18px; font-weight:700; }
.ss-textarea{ min-height:110px; padding:12px 14px; resize:vertical; }
.ss-input:focus, .ss-textarea:focus{
    border-color:#0f172a; box-shadow:0 0 0 4px rgba(15,23,42,.06);
}
.ss-inline-actions{
    margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;
}
.ss-action-stack{
    display:grid; gap:10px;
}
.ss-btn{
    height:46px; border:none; border-radius:12px; padding:0 16px; background:#0f172a; color:#fff;
    display:inline-flex; align-items:center; justify-content:center; gap:8px; text-decoration:none; font-weight:700; cursor:pointer;
}
.ss-btn:hover{ color:#fff; opacity:.95; }
.ss-btn-ghost{ background:#fff; border:1px solid #cbd5e1; color:#0f172a; }
.ss-btn-indigo{ background:#4338ca; }
.ss-btn-danger{ background:#b91c1c; }
.ss-message{
    margin-top:12px; padding:12px 14px; border-radius:12px; font-weight:700;
}
.ss-message.ok{ background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
.ss-message.err{ background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }

.ss-summary-grid{
    display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px;
}
.ss-summary-item{
    border:1px solid #e2e8f0; border-radius:14px; padding:12px; background:#f8fafc;
}
.ss-summary-item span{
    display:block; font-size:12px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.08em;
}
.ss-summary-item strong{
    display:block; margin-top:6px; color:#0f172a; font-size:15px;
}

.ss-kpis{
    display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:14px;
}
.ss-kpi{
    background:#fff; border:1px solid #e2e8f0; border-radius:18px; padding:16px;
    box-shadow:0 10px 30px rgba(15,23,42,.05);
}
.ss-kpi-label{
    font-size:12px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.08em;
}
.ss-kpi-value{
    margin-top:8px; font-size:26px; font-weight:800; color:#0f172a;
}
.ss-kpi-foot{ margin-top:6px; color:#64748b; font-size:13px; }

.ss-phase + .ss-phase{
    margin-top:18px; padding-top:18px; border-top:1px solid #e2e8f0;
}
.ss-phase-head{
    display:flex; justify-content:space-between; gap:12px; align-items:center; margin-bottom:12px;
}
.ss-phase-title{ font-size:17px; font-weight:800; color:#0f172a; }
.ss-phase-sub{ color:#64748b; }
.ss-lines{ display:grid; gap:12px; }
.ss-line{
    border:1px solid #e2e8f0; border-radius:18px; padding:16px; background:#fff;
}
.ss-line-top{
    display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap;
}
.ss-line-title{ font-size:16px; font-weight:800; color:#0f172a; }
.ss-line-sub{ margin-top:5px; color:#64748b; font-size:13px; }
.ss-badge{
    min-height:30px; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:800;
    display:inline-flex; align-items:center; justify-content:center; border:1px solid transparent;
}
.ss-badge.pending{ background:#f8fafc; color:#475569; border-color:#cbd5e1; }
.ss-badge.partial{ background:#fff7ed; color:#c2410c; border-color:#fdba74; }
.ss-badge.complete{ background:#ecfdf5; color:#047857; border-color:#a7f3d0; }
.ss-line-stats{
    display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin-top:14px;
}
.ss-line-stat{
    border:1px solid #e2e8f0; border-radius:12px; padding:10px; background:#f8fafc;
}
.ss-line-stat span{
    display:block; font-size:12px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.08em;
}
.ss-line-stat strong{
    display:block; margin-top:6px; color:#0f172a; font-size:15px;
}
.ss-progress{
    margin-top:14px; height:10px; background:#e2e8f0; border-radius:999px; overflow:hidden;
}
.ss-progress > span{
    display:block; height:100%; border-radius:999px; background:linear-gradient(90deg, #0f172a, #334155);
}
.ss-tags{
    margin-top:12px; display:flex; flex-wrap:wrap; gap:8px;
}
.ss-tags span{
    padding:7px 10px; border-radius:999px; font-size:12px; font-weight:800;
    background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;
}
.ss-reason-grid{
    margin-top:14px; display:grid; grid-template-columns:220px minmax(0,1fr); gap:10px;
}
.ss-select{
    width:100%; height:44px; border:1px solid #cbd5e1; border-radius:12px; padding:0 12px; background:#fff; outline:none;
}
.ss-select:focus{ border-color:#0f172a; box-shadow:0 0 0 4px rgba(15,23,42,.06); }

.ss-sign-wrap{
    border:1px dashed #94a3b8; border-radius:16px; background:#fff; overflow:hidden;
}
#signature-pad{
    width:100%; height:240px; display:block; background:#fff;
}
.ss-close-info{
    margin-top:6px; padding:12px; border-radius:12px; border:1px solid #fdba74; background:#fff7ed; color:#9a3412;
}
.ss-scans{ display:grid; gap:10px; }
.ss-scan{
    border:1px solid #e2e8f0; border-radius:14px; padding:12px; background:#f8fafc;
}
.ss-scan-top{
    display:flex; justify-content:space-between; gap:10px; align-items:center;
}
.ss-scan-code{ font-weight:800; color:#0f172a; }
.ss-scan-result{
    font-size:11px; font-weight:800; padding:5px 8px; border-radius:999px; border:1px solid transparent;
}
.ss-scan-result.accepted{ background:#ecfdf5; color:#047857; border-color:#a7f3d0; }
.ss-scan-result.rejected, .ss-scan-result.duplicate{ background:#fef2f2; color:#b91c1c; border-color:#fecaca; }
.ss-scan-sub{ margin-top:6px; color:#64748b; font-size:13px; }

@media (max-width: 1200px){
    .ss-kpis{ grid-template-columns:repeat(3,minmax(0,1fr)); }
    .ss-top-grid, .ss-main-grid{ grid-template-columns:1fr; }
}
@media (max-width: 900px){
    .ss-scan-form,
    .ss-reason-grid{ grid-template-columns:1fr; }
    .ss-field-sm{ width:auto; }
    .ss-line-stats,
    .ss-summary-grid{ grid-template-columns:1fr 1fr; }
}
@media (max-width: 768px){
    .ss-wrap{ padding:16px; }
    .ss-kpis,
    .ss-line-stats,
    .ss-summary-grid{ grid-template-columns:1fr; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = @json(csrf_token());
    let shipment = @json($shipment);

    const scanUrl = @json($scanUrl);
    const closeUrl = @json($closeUrl);
    const dispatchUrl = @json($dispatchUrl);
    const cancelUrl = @json($cancelUrl);

    const codeInput = document.getElementById('scan-code');
    const qtyInput = document.getElementById('scan-qty');
    const messageBox = document.getElementById('scan-message');
    const summaryGrid = document.getElementById('summary-grid');
    const kpiGrid = document.getElementById('kpi-grid');
    const phaseLines = document.getElementById('phase-lines');
    const recentScans = document.getElementById('recent-scans');
    const shipStatusText = document.getElementById('ship-status-text');

    const signName = document.getElementById('sign-name');
    const signRole = document.getElementById('sign-role');
    const closeNotes = document.getElementById('close-notes');

    const scanBtn = document.getElementById('scan-btn');
    const closeBtn = document.getElementById('close-btn');
    const dispatchBtn = document.getElementById('dispatch-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const reloadBtn = document.getElementById('reload-btn');
    const focusBtn = document.getElementById('focus-btn');
    const clearSignBtn = document.getElementById('clear-sign-btn');

    const canvas = document.getElementById('signature-pad');
    const ctx = canvas.getContext('2d');
    let drawing = false;
    let signed = false;

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function statusLabel(status) {
        switch (String(status || 'draft')) {
            case 'draft': return 'Borrador';
            case 'loading': return 'Cargando';
            case 'loaded_complete': return 'Cerrado completo';
            case 'loaded_partial': return 'Cerrado parcial';
            case 'dispatched': return 'Despachado';
            case 'cancelled': return 'Cancelado';
            default: return status || '—';
        }
    }

    function pct(loaded, expected) {
        const exp = Math.max(1, parseInt(expected || 0, 10));
        const val = Math.round((parseInt(loaded || 0, 10) / exp) * 100);
        return Math.max(0, Math.min(100, val));
    }

    function showMessage(text, ok = true) {
        messageBox.style.display = 'block';
        messageBox.className = 'ss-message ' + (ok ? 'ok' : 'err');
        messageBox.textContent = text;
    }

    function clearMessage() {
        messageBox.style.display = 'none';
        messageBox.textContent = '';
        messageBox.className = 'ss-message';
    }

    function reasonOptions(selected) {
        const options = [
            ['', 'Selecciona motivo'],
            ['not_found_in_staging', 'No se encontró en staging'],
            ['damaged_box', 'Caja dañada'],
            ['incomplete_product', 'Producto incompleto'],
            ['rescheduled', 'Se reprograma envío'],
            ['customer_partial_authorized', 'Cliente autorizó parcial'],
            ['no_space_in_vehicle', 'Sin espacio en unidad'],
            ['quality_hold', 'Retenido por calidad'],
            ['picking_error', 'Error de picking'],
            ['retained', 'Retenido'],
            ['other', 'Otro'],
        ];

        return options.map(([value, label]) => {
            const isSelected = String(selected || '') === String(value) ? 'selected' : '';
            return `<option value="${escapeHtml(value)}" ${isSelected}>${escapeHtml(label)}</option>`;
        }).join('');
    }

    function renderSummary(data) {
        const items = [
            ['Unidad', data.vehicle_name || 'Sin unidad'],
            ['Placa', data.vehicle_plate || 'Sin placa'],
            ['Chofer', data.driver_name || 'Sin chofer'],
            ['Teléfono', data.driver_phone || 'Sin teléfono'],
            ['Ruta', data.route_name || 'Sin ruta'],
            ['Operador', data.operator_name || 'Sin operador'],
            ['Firma', data.signed_by_name || 'Pendiente'],
            ['Despacho', data.dispatched_at || 'Pendiente'],
        ];

        summaryGrid.innerHTML = items.map(([label, value]) => `
            <div class="ss-summary-item">
                <span>${escapeHtml(label)}</span>
                <strong>${escapeHtml(value)}</strong>
            </div>
        `).join('');
    }

    function renderKpis(data) {
        kpiGrid.innerHTML = `
            <div class="ss-kpi">
                <div class="ss-kpi-label">Piezas cargadas</div>
                <div class="ss-kpi-value">${Number(data.loaded_qty || 0).toLocaleString()}</div>
                <div class="ss-kpi-foot">de ${Number(data.expected_qty || 0).toLocaleString()} esperadas</div>
            </div>
            <div class="ss-kpi">
                <div class="ss-kpi-label">Piezas faltantes</div>
                <div class="ss-kpi-value">${Number(data.missing_qty || 0).toLocaleString()}</div>
                <div class="ss-kpi-foot">Pendiente por justificar</div>
            </div>
            <div class="ss-kpi">
                <div class="ss-kpi-label">Cajas cargadas</div>
                <div class="ss-kpi-value">${Number(data.loaded_boxes || 0).toLocaleString()}</div>
                <div class="ss-kpi-foot">de ${Number(data.expected_boxes || 0).toLocaleString()} esperadas</div>
            </div>
            <div class="ss-kpi">
                <div class="ss-kpi-label">Cajas faltantes</div>
                <div class="ss-kpi-value">${Number(data.missing_boxes || 0).toLocaleString()}</div>
                <div class="ss-kpi-foot">Pendiente por justificar</div>
            </div>
            <div class="ss-kpi">
                <div class="ss-kpi-label">Líneas con avance</div>
                <div class="ss-kpi-value">${Number(data.scanned_lines || 0).toLocaleString()}</div>
                <div class="ss-kpi-foot">de ${Number(data.expected_lines || 0).toLocaleString()} líneas</div>
            </div>
            <div class="ss-kpi">
                <div class="ss-kpi-label">Estado</div>
                <div class="ss-kpi-value" style="font-size:20px;">${escapeHtml(statusLabel(data.status))}</div>
                <div class="ss-kpi-foot">Control de embarque</div>
            </div>
        `;
    }

    function groupLinesByPhase(lines) {
        const groups = {};
        (lines || []).forEach(line => {
            const key = parseInt(line.phase || 1, 10);
            if (!groups[key]) groups[key] = [];
            groups[key].push(line);
        });
        return Object.keys(groups)
            .map(Number)
            .sort((a, b) => a - b)
            .map(phase => ({ phase, lines: groups[phase] }));
    }

    function renderLines(data) {
        const phases = groupLinesByPhase(data.lines || []);

        phaseLines.innerHTML = phases.map(group => `
            <div class="ss-phase">
                <div class="ss-phase-head">
                    <div class="ss-phase-title">Fase ${group.phase}</div>
                    <div class="ss-phase-sub">${group.lines.length} líneas</div>
                </div>

                <div class="ss-lines">
                    ${group.lines.map(line => {
                        const progress = pct(line.loaded_qty, line.expected_qty);
                        const missing = Number(line.missing_qty || 0) > 0 || Number(line.missing_boxes || 0) > 0;
                        const loadedTags = Array.isArray(line.loaded_boxes_json) ? line.loaded_boxes_json : [];

                        return `
                            <div class="ss-line" data-line-id="${line.id}">
                                <div class="ss-line-top">
                                    <div>
                                        <div class="ss-line-title">${escapeHtml(line.product_name || 'Producto')}</div>
                                        <div class="ss-line-sub">
                                            SKU ${escapeHtml(line.product_sku || '—')}
                                            ${line.batch_code ? ' · Lote ' + escapeHtml(line.batch_code) : ''}
                                            ${line.location_code ? ' · Ubicación ' + escapeHtml(line.location_code) : ''}
                                            ${line.staging_location_code ? ' · Staging ' + escapeHtml(line.staging_location_code) : ''}
                                            ${line.is_fastflow ? ' · Fast Flow' : ''}
                                        </div>
                                    </div>

                                    <span class="ss-badge ${escapeHtml(line.status || 'pending')}">${escapeHtml((line.status || 'pending').replace('_', ' '))}</span>
                                </div>

                                <div class="ss-line-stats">
                                    <div class="ss-line-stat">
                                        <span>Piezas</span>
                                        <strong>${Number(line.loaded_qty || 0).toLocaleString()} / ${Number(line.expected_qty || 0).toLocaleString()}</strong>
                                    </div>
                                    <div class="ss-line-stat">
                                        <span>Cajas</span>
                                        <strong>${Number(line.loaded_boxes || 0).toLocaleString()} / ${Number(line.expected_boxes || 0).toLocaleString()}</strong>
                                    </div>
                                    <div class="ss-line-stat">
                                        <span>Faltante</span>
                                        <strong>${Number(line.missing_qty || 0).toLocaleString()}</strong>
                                    </div>
                                    <div class="ss-line-stat">
                                        <span>Excedente</span>
                                        <strong>${Number(line.extra_qty || 0).toLocaleString()}</strong>
                                    </div>
                                </div>

                                <div class="ss-progress">
                                    <span style="width:${progress}%"></span>
                                </div>

                                ${loadedTags.length ? `
                                    <div class="ss-tags">
                                        ${loadedTags.map(label => `<span>${escapeHtml(label)}</span>`).join('')}
                                    </div>
                                ` : ''}

                                ${missing ? `
                                    <div class="ss-reason-grid">
                                        <select class="ss-select reason-code" data-line-id="${line.id}">
                                            ${reasonOptions(line.reason_code || '')}
                                        </select>
                                        <input
                                            type="text"
                                            class="ss-input reason-note"
                                            data-line-id="${line.id}"
                                            placeholder="Nota del motivo"
                                            value="${escapeHtml(line.reason_note || '')}">
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `).join('');
    }

    function renderScans(data) {
        const rows = Array.isArray(data.scans) ? data.scans.slice(0, 25) : [];
        recentScans.innerHTML = rows.length
            ? rows.map(scan => `
                <div class="ss-scan">
                    <div class="ss-scan-top">
                        <div class="ss-scan-code">${escapeHtml(scan.scan_value || '—')}</div>
                        <div class="ss-scan-result ${escapeHtml(scan.result || 'accepted')}">${escapeHtml((scan.result || 'accepted').toUpperCase())}</div>
                    </div>
                    <div class="ss-scan-sub">
                        ${escapeHtml(scan.message || 'Sin mensaje')}
                        ${scan.qty ? ' · Cantidad ' + Number(scan.qty).toLocaleString() : ''}
                        ${scan.created_at ? ' · ' + escapeHtml(scan.created_at) : ''}
                    </div>
                </div>
            `).join('')
            : `<div class="ss-scan"><div class="ss-scan-sub">Aún no hay escaneos.</div></div>`;
    }

    function renderAll(data) {
        shipment = data;
        shipStatusText.textContent = statusLabel(data.status);
        renderSummary(data);
        renderKpis(data);
        renderLines(data);
        renderScans(data);

        if (!signName.value && data.signed_by_name) signName.value = data.signed_by_name;
        if (!signRole.value && data.signed_by_role) signRole.value = data.signed_by_role;
        if (!closeNotes.value && data.notes) closeNotes.value = data.notes;

        const closed = ['loaded_complete', 'loaded_partial', 'dispatched'].includes(String(data.status || ''));
        const cancelled = String(data.status || '') === 'cancelled';
        const dispatched = String(data.status || '') === 'dispatched';

        scanBtn.disabled = closed || cancelled;
        closeBtn.disabled = closed || cancelled;
        dispatchBtn.disabled = !(String(data.status || '') === 'loaded_complete' || String(data.status || '') === 'loaded_partial') || dispatched || cancelled;
        cancelBtn.disabled = dispatched || cancelled;
    }

    function getLineReasons() {
        return Array.from(document.querySelectorAll('.reason-code')).map(select => {
            const lineId = Number(select.dataset.lineId || 0);
            const noteInput = document.querySelector(`.reason-note[data-line-id="${lineId}"]`);
            return {
                line_id: lineId,
                reason_code: select.value || '',
                reason_note: noteInput ? noteInput.value.trim() : '',
            };
        });
    }

    async function postJson(url, body, method = 'POST') {
        const res = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body || {})
        });

        const json = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(json.message || 'Ocurrió un error al procesar la solicitud.');
        }
        return json;
    }

    async function doScan() {
        clearMessage();

        const code = codeInput.value.trim();
        const qty = Math.max(1, parseInt(qtyInput.value || '1', 10));

        if (!code) {
            showMessage('Debes capturar o escanear un código.', false);
            codeInput.focus();
            return;
        }

        scanBtn.disabled = true;
        try {
            const response = await postJson(scanUrl, { code, qty }, 'POST');
            if (response.shipment) renderAll(response.shipment);
            showMessage(response.message || 'Escaneo registrado.', true);
            codeInput.value = '';
            qtyInput.value = '1';
            codeInput.focus();
        } catch (error) {
            showMessage(error.message || 'No fue posible registrar el escaneo.', false);
            codeInput.select();
            codeInput.focus();
        } finally {
            scanBtn.disabled = false;
        }
    }

    async function doClose() {
        clearMessage();

        const name = signName.value.trim();
        const role = signRole.value.trim();
        const signature = getSignatureData();
        const lineReasons = getLineReasons();

        if (!name) {
            showMessage('Debes capturar el nombre de quien firma.', false);
            signName.focus();
            return;
        }

        if (!role) {
            showMessage('Debes capturar el rol o cargo.', false);
            signRole.focus();
            return;
        }

        if (!signature || !signed) {
            showMessage('Debes registrar la firma antes de cerrar el embarque.', false);
            return;
        }

        closeBtn.disabled = true;
        try {
            const response = await postJson(closeUrl, {
                signed_by_name: name,
                signed_by_role: role,
                signature_data: signature,
                notes: closeNotes.value.trim(),
                line_reasons: lineReasons,
            }, 'PATCH');

            if (response.shipment) renderAll(response.shipment);
            showMessage(response.message || 'Embarque cerrado correctamente.', true);
        } catch (error) {
            showMessage(error.message || 'No fue posible cerrar el embarque.', false);
        } finally {
            closeBtn.disabled = false;
        }
    }

    async function doDispatch() {
        clearMessage();
        dispatchBtn.disabled = true;
        try {
            const response = await postJson(dispatchUrl, {
                notes: closeNotes.value.trim(),
            }, 'PATCH');

            if (response.shipment) renderAll(response.shipment);
            showMessage(response.message || 'Unidad despachada correctamente.', true);
        } catch (error) {
            showMessage(error.message || 'No fue posible despachar la unidad.', false);
        } finally {
            dispatchBtn.disabled = false;
        }
    }

    async function doCancel() {
        clearMessage();
        const reason = window.prompt('Motivo de cancelación del embarque:');
        if (reason === null) return;

        cancelBtn.disabled = true;
        try {
            const response = await postJson(cancelUrl, { reason }, 'PATCH');
            if (response.shipment) renderAll(response.shipment);
            showMessage(response.message || 'Embarque cancelado.', true);
        } catch (error) {
            showMessage(error.message || 'No fue posible cancelar el embarque.', false);
        } finally {
            cancelBtn.disabled = false;
        }
    }

    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const rect = canvas.getBoundingClientRect();
        canvas.width = Math.floor(rect.width * ratio);
        canvas.height = Math.floor(rect.height * ratio);
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.lineWidth = 2.2;
        ctx.strokeStyle = '#0f172a';
    }

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        if (e.touches && e.touches[0]) {
            return {
                x: e.touches[0].clientX - rect.left,
                y: e.touches[0].clientY - rect.top,
            };
        }
        return {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top,
        };
    }

    function startDraw(e) {
        drawing = true;
        signed = true;
        const pos = getPos(e);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
    }

    function moveDraw(e) {
        if (!drawing) return;
        const pos = getPos(e);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
    }

    function endDraw() {
        drawing = false;
        ctx.closePath();
    }

    function clearSignature() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        signed = false;
    }

    function getSignatureData() {
        if (!signed) return '';
        return canvas.toDataURL('image/png');
    }

    scanBtn.addEventListener('click', doScan);
    closeBtn.addEventListener('click', doClose);
    dispatchBtn.addEventListener('click', doDispatch);
    cancelBtn.addEventListener('click', doCancel);

    reloadBtn.addEventListener('click', function () {
        renderAll(shipment);
        clearMessage();
        codeInput.focus();
    });

    focusBtn.addEventListener('click', function () {
        codeInput.focus();
        codeInput.select();
    });

    codeInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            doScan();
        }
    });

    qtyInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            doScan();
        }
    });

    clearSignBtn.addEventListener('click', clearSignature);

    canvas.addEventListener('mousedown', startDraw);
    canvas.addEventListener('mousemove', moveDraw);
    window.addEventListener('mouseup', endDraw);

    canvas.addEventListener('touchstart', function (e) {
        e.preventDefault();
        startDraw(e);
    }, { passive: false });

    canvas.addEventListener('touchmove', function (e) {
        e.preventDefault();
        moveDraw(e);
    }, { passive: false });

    canvas.addEventListener('touchend', function (e) {
        e.preventDefault();
        endDraw();
    }, { passive: false });

    window.addEventListener('resize', resizeCanvas);

    resizeCanvas();
    renderAll(shipment);
    codeInput.focus();
});
</script>
@endsection