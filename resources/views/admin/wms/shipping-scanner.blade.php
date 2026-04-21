@extends('layouts.app')

@section('title', 'WMS · Scanner de embarque')

@section('content')
@php
    $indexUrl        = route('admin.wms.shipping.index');
    $scanUrl         = route('admin.wms.shipping.scan', $shipment['id']);
    $assignUrl       = route('admin.wms.shipping.assignment', $shipment['id']);
    $closeUrl        = route('admin.wms.shipping.close', $shipment['id']);
    $dispatchUrl     = route('admin.wms.shipping.dispatch', $shipment['id']);
    $cancelUrl       = route('admin.wms.shipping.cancel', $shipment['id']);
    $reopenUrl       = route('admin.wms.shipping.reopen', $shipment['id']);
    $routeCreateUrl  = route('routes.create');
@endphp

<div class="ss-wrap">
    <div class="ss-head">
        <div class="ss-head-left">
            <div class="ss-breadcrumb">
                <a href="{{ $indexUrl }}">Embarques</a>
                <span class="divider">/</span>
                <span class="current">Scanner</span>
            </div>

            <div class="ss-head-titles">
                <div class="ss-title">Validación de salida</div>
                <div class="ss-sub">
                    Envío {{ $shipment['shipment_number'] ?? '—' }}
                    <span class="dot">•</span>
                    Pedido {{ $shipment['order_number'] ?: '—' }}
                    <span class="dot">•</span>
                    Tarea {{ $shipment['task_number'] ?: '—' }}
                </div>
            </div>
        </div>

        <div class="ss-head-right">
            <div class="ss-pill">
                <span class="pill-label">Operador en turno</span>
                <span class="pill-value">{{ $operatorName ?? 'Operador' }}</span>
            </div>

            <div class="ss-pill primary-pill">
                <span class="pill-label">Estado actual</span>
                <span id="ship-status-text" class="pill-value">—</span>
            </div>
        </div>
    </div>

    <div class="ss-grid-top">
        <div class="ss-panel">
            <div class="ss-panel-title">Información de ruta</div>

            <div class="ss-form-grid">
                <div class="ss-field">
                    <label class="ss-label">Usuario responsable</label>
                    <select id="delivery-user-id" class="ss-input">
                        <option value="">Seleccionar...</option>
                        @foreach(($users ?? []) as $user)
                            <option
                                value="{{ $user['id'] }}"
                                data-phone="{{ $user['phone'] ?? '' }}"
                                data-name="{{ $user['name'] ?? '' }}"
                            >
                                {{ $user['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="ss-field">
                    <label class="ss-label">Vehículo asignado</label>
                    <select id="vehicle-id" class="ss-input">
                        <option value="">Seleccionar...</option>
                        @foreach(($vehicles ?? []) as $vehicle)
                            <option value="{{ $vehicle['id'] }}">{{ $vehicle['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="ss-field">
                    <label class="ss-label">Plan de ruta</label>
                    <div class="ss-inline-pair">
                        <select id="route-plan-id" class="ss-input">
                            <option value="">Seleccionar...</option>
                            @foreach(($routes ?? []) as $route)
                                <option value="{{ $route['id'] }}">
                                    {{ $route['name'] }}{{ !empty($route['driver_name']) ? ' · '.$route['driver_name'] : '' }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="ss-btn ss-btn-outline" id="create-route-btn">Nueva</button>
                    </div>
                </div>

                <div class="ss-field">
                    <label class="ss-label">Contacto chofer</label>
                    <input id="driver-phone" type="text" class="ss-input" placeholder="+52 ...">
                </div>
            </div>

            <div class="ss-inline-actions mt-4">
                <button type="button" class="ss-btn ss-btn-primary" id="save-assignment-btn">Actualizar datos</button>
            </div>
        </div>

        <div class="ss-panel">
            <div class="ss-panel-head">
                <div>
                    <div class="ss-panel-title">Escaneo</div>
                    <div class="ss-panel-sub">Escanea y se procesa automáticamente.</div>
                </div>
                <span class="ss-soft-badge">Autolectura</span>
            </div>

            <div class="ss-scan-box">
                <div class="ss-field ss-field-grow">
                    <label class="ss-label">Código de barras / SKU / caja</label>
                    <div class="ss-scan-input-wrap">
                        <span class="ss-scan-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M4 7h2v10H4V7zm3-2h1v14H7V5zm3 2h2v10h-2V7zm3-4h1v18h-1V3zm3 4h2v10h-2V7zm3-2h1v14h-1V5z"></path>
                            </svg>
                        </span>
                        <input
                            id="scan-code"
                            type="text"
                            class="ss-input ss-input-scan"
                            placeholder="Escanea aquí..."
                            autocomplete="off"
                            autofocus
                        >
                    </div>
                </div>

                <div class="ss-field ss-field-qty">
                    <label class="ss-label">Cantidad</label>
                    <input id="scan-qty" type="number" min="1" step="1" value="1" class="ss-input ss-input-qty">
                </div>
            </div>

            <div class="ss-scan-hint">
                Cuando el lector termine de enviar el código, se validará solo.
            </div>

            <div class="ss-action-link mt-4 text-right ss-link-actions">
                <a href="javascript:void(0)" id="cancel-btn" class="text-danger">Cancelar proceso de embarque</a>
                <a href="javascript:void(0)" id="reopen-btn" class="text-primary" style="display:none;">Reabrir proceso</a>
            </div>
        </div>
    </div>

    <div class="ss-kpis" id="kpi-grid"></div>

    <div class="ss-main-grid">
        <div class="ss-panel">
            <div class="ss-panel-head">
                <div>
                    <div class="ss-panel-title">Progreso de carga</div>
                    <div class="ss-panel-sub">Validación física de productos en la unidad.</div>
                </div>
            </div>

            <div id="phase-lines"></div>
        </div>

        <div class="ss-side-stack">
            <div class="ss-panel">
                <div class="ss-panel-title">Resumen</div>
                <div id="summary-grid" class="ss-summary-grid mt-3"></div>
            </div>

            <div class="ss-panel">
                <div class="ss-panel-head">
                    <div>
                        <div class="ss-panel-title">Firma de conformidad</div>
                        <div class="ss-panel-sub">El nombre se toma automáticamente.</div>
                    </div>

                    <button type="button" id="clear-sign-btn" class="ss-btn ss-btn-ghost">
                        Borrar firma
                    </button>
                </div>

                <div class="ss-field mt-3">
                    <label class="ss-label">Nombre de conformidad</label>
                    <input
                        id="sign-name"
                        type="text"
                        class="ss-input ss-input-readonly"
                        placeholder="Se llena automáticamente"
                        readonly
                    >
                    <div class="ss-helper">Este campo siempre será el usuario responsable seleccionado.</div>
                </div>

                <div class="ss-field mt-3">
                    <div class="ss-sign-wrap" id="signature-shell">
                        <canvas id="signature-pad" width="700" height="190"></canvas>
                        <div class="ss-sign-line"></div>
                    </div>
                </div>
            </div>

            <div class="ss-panel">
                <div class="ss-panel-title">Finalizar proceso</div>

                <div class="ss-field mt-3">
                    <label class="ss-label">Comentarios finales (opcional)</label>
                    <textarea id="close-notes" class="ss-textarea" placeholder="Observaciones sobre la carga..."></textarea>
                </div>

                <div class="ss-action-stack mt-4">
                    <button type="button" class="ss-btn ss-btn-dark" id="close-btn">Cerrar validación</button>
                    <button type="button" class="ss-btn ss-btn-success" id="dispatch-btn">Aprobar salida de unidad</button>
                </div>
            </div>

            <div class="ss-panel">
                <div class="ss-panel-title">Registro de lectura</div>
                <div id="recent-scans" class="ss-scans mt-3"></div>
            </div>
        </div>
    </div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

:root {
  --bg: #f9fafb; 
  --card: #ffffff; 
  --ink: #333333; 
  --muted: #888888; 
  --line: #ebebeb; 
  --blue: #007aff; 
  --blue-soft: #e6f0ff; 
  --success: #15803d; 
  --success-soft: #e6ffe6; 
  --danger: #ff4a4a; 
  --danger-soft: #ffebeb;
}

* { box-sizing: border-box; }

.ss-wrap {
    font-family: 'Quicksand', system-ui, sans-serif;
    padding: 32px;
    display: grid;
    gap: 24px;
    color: var(--ink);
    background: var(--bg);
    font-weight: 500;
}

h1, h2, h3, h4, h5, h6 { color: #111111; }

.mt-3 { margin-top: 12px; }
.mt-4 { margin-top: 16px; }
.text-right { text-align: right; }

.ss-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 18px;
    flex-wrap: wrap;
}

.ss-head-left {
    flex: 1 1 520px;
    min-width: 280px;
}

.ss-head-right {
    flex: 1 1 420px;
    min-width: 320px;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.ss-breadcrumb {
    font-size: 14px;
    color: var(--muted);
    margin-bottom: 8px;
    display: flex;
    gap: 6px;
    align-items: center;
    font-weight: 600;
}

.ss-breadcrumb a {
    color: var(--blue);
    text-decoration: none;
}

.ss-breadcrumb .divider { color: var(--line); }
.ss-breadcrumb .current { color: var(--muted); }

.ss-title {
    font-size: 28px;
    font-weight: 700;
    color: #111111;
    letter-spacing: -0.02em;
}

.ss-sub {
    margin-top: 6px;
    color: var(--muted);
    font-size: 15px;
}

.dot {
    color: var(--line);
    margin: 0 8px;
}

.ss-pill {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 16px 20px;
    min-height: 88px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
}

.ss-pill.primary-pill {
    border-color: var(--blue-soft);
    background: var(--blue-soft);
}

.pill-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .08em;
}

.pill-value {
    margin-top: 6px;
    font-size: 18px;
    font-weight: 700;
    color: #111111;
    line-height: 1.35;
}

.primary-pill .pill-label { color: var(--blue); }
.primary-pill .pill-value { color: var(--blue); }

.ss-grid-top {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.ss-main-grid {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 24px;
    align-items: start;
}

.ss-side-stack {
    display: grid;
    gap: 24px;
}

.ss-panel {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.ss-panel:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.04);
}

.ss-panel-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.ss-panel-title {
    font-size: 18px;
    font-weight: 700;
    color: #111111;
}

.ss-panel-sub {
    margin-top: 4px;
    color: var(--muted);
    font-size: 14px;
}

.ss-soft-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 14px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 700;
    background: var(--blue-soft);
    color: var(--blue);
    white-space: nowrap;
}

.ss-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.ss-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ss-field-grow {
    flex: 1 1 auto;
    min-width: 0;
}

.ss-field-qty {
    width: 120px;
    min-width: 120px;
}

.ss-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--ink);
}

.ss-helper {
    font-size: 12px;
    color: var(--muted);
}

.ss-input,
.ss-textarea {
    width: 100%;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: var(--card);
    color: var(--ink);
    font-family: inherit;
    font-size: 14px;
    font-weight: 500;
    transition: all .2s ease;
    padding: 0 16px;
}

.ss-input {
    height: 48px;
}

.ss-input-readonly {
    background: var(--bg);
    color: var(--muted);
    border-color: transparent;
}

.ss-textarea {
    min-height: 100px;
    padding: 16px;
    resize: vertical;
}

.ss-input:focus,
.ss-textarea:focus {
    outline: none;
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
}

.ss-inline-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.ss-inline-pair {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 12px;
    align-items: center;
}

.ss-action-stack {
    display: grid;
    gap: 12px;
}

/* Botones rediseñados minimalistas */
.ss-btn {
    height: 48px;
    border: none;
    border-radius: 8px;
    padding: 0 20px;
    font-size: 15px;
    font-weight: 700;
    font-family: inherit;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform .15s ease, background .2s ease, box-shadow .2s ease;
}

.ss-btn:active {
    transform: scale(0.98);
}

.ss-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.ss-btn:disabled {
    opacity: .5;
    cursor: not-allowed;
    pointer-events: none;
}

.ss-btn-primary {
    background: var(--blue);
    color: #ffffff;
}

.ss-btn-primary:hover {
    background: #0062cc;
}

.ss-btn-ghost {
    background: transparent;
    color: var(--muted);
}

.ss-btn-ghost:hover {
    background: var(--bg);
    color: var(--ink);
    box-shadow: none;
}

/* El anterior dark lo pasamos a un estilo ghost o secundario limpio */
.ss-btn-dark {
    background: transparent;
    color: var(--ink);
    border: 1px solid var(--line);
}

.ss-btn-dark:hover {
    background: var(--bg);
}

.ss-btn-success {
    background: var(--success);
    color: #ffffff;
}

.ss-btn-success:hover {
    background: #106630;
}

.ss-btn-outline {
    background: var(--card);
    color: var(--blue);
    border: 1px solid var(--blue);
}

.ss-btn-outline:hover {
    background: var(--blue-soft);
    box-shadow: none;
}

.text-danger {
    color: var(--danger);
    font-size: 14px;
    text-decoration: none;
    font-weight: 600;
    transition: opacity 0.2s;
}

.text-danger:hover { opacity: 0.8; }

.text-primary {
    color: var(--blue);
    font-size: 14px;
    text-decoration: none;
    font-weight: 600;
    transition: opacity 0.2s;
}

.text-primary:hover { opacity: 0.8; }

.ss-link-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.ss-scan-box {
    display: flex;
    align-items: end;
    gap: 16px;
    margin-top: 12px;
}

.ss-scan-input-wrap {
    position: relative;
}

.ss-scan-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    color: var(--muted);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.ss-scan-icon svg {
    width: 20px;
    height: 20px;
    fill: currentColor;
}

.ss-input-scan {
    height: 56px;
    padding-left: 48px;
    font-size: 18px;
    font-weight: 700;
}

.ss-input-qty {
    height: 56px;
    text-align: center;
    font-size: 18px;
    font-weight: 700;
}

.ss-scan-hint {
    margin-top: 12px;
    color: var(--muted);
    font-size: 13px;
}

.ss-summary-grid {
    display: grid;
    gap: 0;
}

.ss-summary-item {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--line);
    font-size: 14px;
}

.ss-summary-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.ss-summary-item span {
    color: var(--muted);
}

.ss-summary-item .val {
    color: #111111;
    font-weight: 700;
    text-align: right;
}

.ss-kpis {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 20px;
}

.ss-kpi {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    transition: transform 0.2s ease;
}

.ss-kpi:hover {
    transform: translateY(-2px);
}

.ss-kpi-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .06em;
}

.ss-kpi-value {
    margin-top: 8px;
    font-size: 26px;
    font-weight: 700;
    color: #111111;
    line-height: 1.2;
}

.ss-kpi-value.accent {
    color: var(--blue);
}

.ss-kpi-foot {
    margin-top: 6px;
    color: var(--muted);
    font-size: 13px;
}

.ss-phase {
    padding-top: 20px;
}

.ss-phase + .ss-phase {
    margin-top: 20px;
    border-top: 1px solid var(--line);
}

.ss-phase-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.ss-phase-title {
    font-size: 16px;
    font-weight: 700;
    color: #111111;
}

.ss-phase-sub {
    font-size: 13px;
    color: var(--muted);
    background: var(--bg);
    border: 1px solid var(--line);
    padding: 4px 12px;
    border-radius: 999px;
    font-weight: 600;
}

.ss-lines {
    display: grid;
    gap: 16px;
}

.ss-line {
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 20px;
    background: var(--card);
}

.ss-line-top {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
}

.ss-line-title {
    font-size: 16px;
    font-weight: 700;
    color: #111111;
}

.ss-line-sub {
    margin-top: 6px;
    color: var(--muted);
    font-size: 13px;
}

/* Badges estilo Nelo/Apple */
.ss-badge {
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
}

.ss-badge.pending { background: var(--bg); color: var(--muted); border: 1px solid var(--line); }
.ss-badge.partial { background: var(--blue-soft); color: var(--blue); }
.ss-badge.complete { background: var(--success-soft); color: var(--success); }

.ss-line-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-top: 18px;
}

.ss-line-stat {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.ss-line-stat span {
    font-size: 12px;
    color: var(--muted);
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.02em;
}

.ss-line-stat .val {
    color: #111111;
    font-size: 15px;
    font-weight: 700;
    text-transform: none;
    letter-spacing: 0;
}

.ss-progress {
    margin-top: 18px;
    height: 8px;
    background: var(--bg);
    border-radius: 999px;
    overflow: hidden;
}

.ss-progress > span {
    display: block;
    height: 100%;
    border-radius: 999px;
    background: var(--blue);
    transition: width .3s ease;
}

.ss-reason-grid {
    margin-top: 18px;
    display: grid;
    grid-template-columns: 240px minmax(0, 1fr);
    gap: 16px;
    padding-top: 18px;
    border-top: 1px dashed var(--line);
}

.ss-reason-caption {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.ss-reason-caption .title {
    font-size: 14px;
    font-weight: 700;
    color: #111111;
}

.ss-reason-caption .sub {
    font-size: 13px;
    color: var(--muted);
}

.ss-reason-fields {
    display: grid;
    gap: 12px;
}

.ss-sign-wrap {
    position: relative;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: var(--card);
    overflow: hidden;
    min-height: 190px;
}

.ss-sign-wrap::after {
    content: 'Firma aquí';
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--muted);
    font-size: 18px;
    font-weight: 700;
    pointer-events: none;
    opacity: 0.5;
}

.ss-sign-wrap.drawing::after {
    opacity: 0;
}

#signature-pad {
    width: 100%;
    height: 190px;
    display: block;
    cursor: crosshair;
    position: relative;
    z-index: 2;
    background: transparent;
    touch-action: none;
}

.ss-sign-line {
    position: absolute;
    left: 24px;
    right: 24px;
    bottom: 28px;
    border-top: 2px dashed var(--line);
    pointer-events: none;
    z-index: 1;
}

.ss-scans {
    display: grid;
    gap: 12px;
    max-height: 320px;
    overflow-y: auto;
    padding-right: 6px;
}

.ss-scans::-webkit-scrollbar {
    width: 6px;
}

.ss-scans::-webkit-scrollbar-thumb {
    background: var(--line);
    border-radius: 10px;
}

.ss-scan {
    padding: 14px;
    border-radius: 12px;
    background: var(--bg);
    border: 1px solid var(--line);
}

.ss-scan-top {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
}

.ss-scan-code {
    font-weight: 700;
    color: #111111;
    font-size: 14px;
}

.ss-scan-result {
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.ss-scan-result.accepted { background: var(--success-soft); color: var(--success); }
.ss-scan-result.rejected,
.ss-scan-result.duplicate { background: var(--danger-soft); color: var(--danger); }

.ss-scan-sub {
    margin-top: 6px;
    color: var(--muted);
    font-size: 13px;
}

@media (max-width: 1200px) {
    .ss-grid-top,
    .ss-main-grid {
        grid-template-columns: 1fr;
    }

    .ss-kpis {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 960px) {
    .ss-head-right,
    .ss-form-grid,
    .ss-reason-grid,
    .ss-inline-pair {
        grid-template-columns: 1fr;
    }

    .ss-scan-box {
        flex-direction: column;
        align-items: stretch;
    }

    .ss-field-qty {
        width: 100%;
        min-width: 0;
    }
}

@media (max-width: 768px) {
    .ss-wrap {
        padding: 20px;
        gap: 20px;
    }

    .ss-kpis,
    .ss-line-stats {
        grid-template-columns: 1fr;
    }

    .ss-panel {
        padding: 20px;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = @json(csrf_token());
    let shipment = @json($shipment);
    const routeCreateBaseUrl = @json($routeCreateUrl);

    const scanUrl = @json($scanUrl);
    const assignUrl = @json($assignUrl);
    const closeUrl = @json($closeUrl);
    const dispatchUrl = @json($dispatchUrl);
    const cancelUrl = @json($cancelUrl);
    const reopenUrl = @json($reopenUrl);

    const codeInput = document.getElementById('scan-code');
    const qtyInput = document.getElementById('scan-qty');
    const cancelBtn = document.getElementById('cancel-btn');
    const reopenBtn = document.getElementById('reopen-btn');
    const closeBtn = document.getElementById('close-btn');
    const dispatchBtn = document.getElementById('dispatch-btn');
    const createRouteBtn = document.getElementById('create-route-btn');

    const saveAssignmentBtn = document.getElementById('save-assignment-btn');
    const deliveryUserSelect = document.getElementById('delivery-user-id');
    const vehicleSelect = document.getElementById('vehicle-id');
    const routeSelect = document.getElementById('route-plan-id');
    const driverPhoneInput = document.getElementById('driver-phone');

    const summaryGrid = document.getElementById('summary-grid');
    const kpiGrid = document.getElementById('kpi-grid');
    const phaseLines = document.getElementById('phase-lines');
    const recentScans = document.getElementById('recent-scans');
    const shipStatusText = document.getElementById('ship-status-text');

    const signName = document.getElementById('sign-name');
    const closeNotes = document.getElementById('close-notes');

    const canvas = document.getElementById('signature-pad');
    const signShell = document.getElementById('signature-shell');
    const ctx = canvas.getContext('2d');

    let scanTimer = null;
    let scanLock = false;
    let canvasRatio = Math.max(window.devicePixelRatio || 1, 1);

    const signatureState = {
        drawing: false,
        hasContent: false,
        moved: false,
        lastX: 0,
        lastY: 0,
        pointerId: null
    };

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2600,
        timerProgressBar: true
    });

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function toast(message, icon = 'success', timer = 2600) {
        Toast.fire({ icon, title: message, timer });
    }

    async function swalInfo(title, text, icon = 'info') {
        return Swal.fire({
            icon,
            title,
            text,
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#007aff'
        });
    }

    async function swalConfirm(title, text, confirmText = 'Sí, continuar') {
        return Swal.fire({
            icon: 'warning',
            title,
            text,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ff4a4a',
            cancelButtonColor: '#888888'
        });
    }

    function statusLabel(status) {
        switch (String(status || 'draft')) {
            case 'draft': return 'Borrador';
            case 'loading': return 'En proceso';
            case 'loaded_complete': return 'Finalizado';
            case 'loaded_partial': return 'Cierre parcial';
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

    function applyUserPhone(force = false) {
        const option = deliveryUserSelect.options[deliveryUserSelect.selectedIndex];
        const phone = option ? String(option.dataset.phone || '').trim() : '';
        if (phone && (force || !driverPhoneInput.value.trim())) {
            driverPhoneInput.value = phone;
        }
    }

    function syncSignatoryFromResponsible(force = true) {
        const option = deliveryUserSelect.options[deliveryUserSelect.selectedIndex];
        const selectedName = option ? String(option.dataset.name || option.textContent || '').trim() : '';
        const fallbackName = String(shipment.delivery_user_name || shipment.driver_name || '').trim();
        const resolvedName = selectedName || fallbackName || '';

        if (force || !signName.value.trim()) {
            signName.value = resolvedName;
        }
    }

    function buildCreateRouteUrl() {
        const params = new URLSearchParams();
        params.set('shipment_id', String(shipment.id || ''));
        params.set('back_url', window.location.href.split('#')[0]);
        if (deliveryUserSelect.value) params.set('driver_id', deliveryUserSelect.value);
        return routeCreateBaseUrl + '?' + params.toString();
    }

    function hydrateSelectors(data) {
        deliveryUserSelect.value = data.delivery_user_id || '';
        vehicleSelect.value = data.vehicle_id || '';
        routeSelect.value = data.route_plan_id || '';
        driverPhoneInput.value = data.driver_phone || '';
        applyUserPhone(false);
        syncSignatoryFromResponsible(true);
    }

    function renderSummary(data) {
        const items = [
            ['Responsable', data.delivery_user_name || data.driver_name || 'Sin asignar'],
            ['Vehículo', data.vehicle_label || data.vehicle_name || '—'],
            ['Placa', data.vehicle_plate || '—'],
            ['Ruta', data.route_plan_name || data.route_name || '—'],
            ['Teléfono', data.driver_phone || '—'],
            ['Firma', data.signed_by_name || 'Pendiente'],
            ['Despacho', data.dispatched_at || 'Pendiente']
        ];

        summaryGrid.innerHTML = items.map(([label, value]) => `
            <div class="ss-summary-item">
                <span>${escapeHtml(label)}</span>
                <span class="val">${escapeHtml(value)}</span>
            </div>
        `).join('');
    }

    function renderKpis(data) {
        const kpis = [
            { label: 'Piezas validadas', val: data.loaded_qty, sub: `de ${Number(data.expected_qty || 0).toLocaleString()} uds` },
            { label: 'Faltantes', val: data.missing_qty, sub: 'Requiere justificación' },
            { label: 'Cajas escaneadas', val: data.loaded_boxes, sub: `de ${Number(data.expected_boxes || 0).toLocaleString()} uds` },
            { label: 'Cajas faltantes', val: data.missing_boxes, sub: 'Requiere justificación' },
            { label: 'Líneas procesadas', val: data.scanned_lines, sub: `de ${Number(data.expected_lines || 0).toLocaleString()} líneas` },
            { label: 'Estatus', val: statusLabel(data.status), sub: 'Operación actual', accent: true }
        ];

        kpiGrid.innerHTML = kpis.map(k => `
            <div class="ss-kpi">
                <div class="ss-kpi-label">${k.label}</div>
                <div class="ss-kpi-value ${k.accent ? 'accent' : ''}" ${k.accent ? 'style="font-size:20px;"' : ''}>
                    ${k.accent ? escapeHtml(k.val) : Number(k.val || 0).toLocaleString()}
                </div>
                <div class="ss-kpi-foot">${k.sub}</div>
            </div>
        `).join('');
    }

    function renderLines(data) {
        const groups = {};

        (data.lines || []).forEach(line => {
            const key = parseInt(line.phase || 1, 10);
            if (!groups[key]) groups[key] = [];
            groups[key].push(line);
        });

        const phases = Object.keys(groups)
            .map(Number)
            .sort((a, b) => a - b)
            .map(phase => ({ phase, lines: groups[phase] }));

        phaseLines.innerHTML = phases.map(group => `
            <div class="ss-phase">
                <div class="ss-phase-head">
                    <div class="ss-phase-title">Fase ${group.phase}</div>
                    <div class="ss-phase-sub">${group.lines.length} docs</div>
                </div>

                <div class="ss-lines">
                    ${group.lines.map(line => {
                        const progress = pct(line.loaded_qty, line.expected_qty);
                        const missing = Number(line.missing_qty || 0) > 0 || Number(line.missing_boxes || 0) > 0;

                        return `
                            <div class="ss-line">
                                <div class="ss-line-top">
                                    <div>
                                        <div class="ss-line-title">${escapeHtml(line.product_name || 'Producto')}</div>
                                        <div class="ss-line-sub">
                                            SKU ${escapeHtml(line.product_sku || '—')}
                                            ${line.location_code ? ' · ' + escapeHtml(line.location_code) : ''}
                                        </div>
                                    </div>
                                    <span class="ss-badge ${escapeHtml(line.status || 'pending')}">
                                        ${escapeHtml((line.status || 'pending').replace('_', ' '))}
                                    </span>
                                </div>

                                <div class="ss-line-stats">
                                    <div class="ss-line-stat">
                                        <span>Piezas</span>
                                        <span class="val">${Number(line.loaded_qty || 0).toLocaleString()} / ${Number(line.expected_qty || 0).toLocaleString()}</span>
                                    </div>
                                    <div class="ss-line-stat">
                                        <span>Cajas</span>
                                        <span class="val">${Number(line.loaded_boxes || 0).toLocaleString()} / ${Number(line.expected_boxes || 0).toLocaleString()}</span>
                                    </div>
                                    <div class="ss-line-stat">
                                        <span>Faltante</span>
                                        <span class="val">
                                            ${Number(line.missing_qty || 0).toLocaleString()} pzas ·
                                            ${Number(line.missing_boxes || 0).toLocaleString()} cajas
                                        </span>
                                    </div>
                                </div>

                                <div class="ss-progress">
                                    <span style="width:${progress}%"></span>
                                </div>

                                ${missing ? `
                                    <div class="ss-reason-grid">
                                        <div class="ss-reason-caption">
                                            <div class="title">Motivo por el que no se lleva completo</div>
                                            <div class="sub">Obligatorio cuando el producto no se carga completo.</div>
                                        </div>

                                        <div class="ss-reason-fields">
                                            <select class="ss-input reason-code" data-line-id="${line.id}">
                                                <option value="">Seleccionar motivo...</option>
                                                <option value="damaged" ${String(line.reason_code || '') === 'damaged' ? 'selected' : ''}>Dañado</option>
                                                <option value="not_found" ${String(line.reason_code || '') === 'not_found' ? 'selected' : ''}>No encontrado</option>
                                                <option value="incomplete_load" ${String(line.reason_code || '') === 'incomplete_load' ? 'selected' : ''}>Carga incompleta</option>
                                                <option value="other" ${String(line.reason_code || '') === 'other' ? 'selected' : ''}>Otro</option>
                                            </select>

                                            <input
                                                type="text"
                                                class="ss-input reason-note"
                                                data-line-id="${line.id}"
                                                placeholder="Escribe la justificación..."
                                                value="${escapeHtml(line.reason_note || '')}"
                                            >
                                        </div>
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
        const rows = Array.isArray(data.scans) ? data.scans.slice(0, 15) : [];

        recentScans.innerHTML = rows.length
            ? rows.map(scan => `
                <div class="ss-scan">
                    <div class="ss-scan-top">
                        <div class="ss-scan-code">${escapeHtml(scan.scan_value || '—')}</div>
                        <div class="ss-scan-result ${escapeHtml(scan.result || 'accepted')}">${escapeHtml(scan.result || 'accepted')}</div>
                    </div>
                    <div class="ss-scan-sub">${escapeHtml(scan.message || 'Procesado')}</div>
                </div>
            `).join('')
            : `<div class="ss-scan"><div class="ss-scan-sub">Esperando escaneos...</div></div>`;
    }

    function renderAll(data) {
        shipment = data;
        shipStatusText.textContent = statusLabel(data.status);
        hydrateSelectors(data);
        renderSummary(data);
        renderKpis(data);
        renderLines(data);
        renderScans(data);

        if (data.signed_by_name) {
            signName.value = data.signed_by_name;
        } else {
            syncSignatoryFromResponsible(true);
        }

        if (!closeNotes.value && data.notes) {
            closeNotes.value = data.notes;
        }

        const currentStatus = String(data.status || '');
        const isCancelled = currentStatus === 'cancelled';
        const isFrozen = ['loaded_complete', 'loaded_partial', 'dispatched'].includes(currentStatus);

        codeInput.disabled = isCancelled || isFrozen;
        qtyInput.disabled = isCancelled || isFrozen;
        closeBtn.disabled = isCancelled || isFrozen;
        saveAssignmentBtn.disabled = isCancelled || isFrozen;
        dispatchBtn.disabled = isCancelled || !['loaded_complete', 'loaded_partial'].includes(currentStatus) || currentStatus === 'dispatched';

        if (cancelBtn) {
            cancelBtn.style.display = (!isCancelled && currentStatus !== 'dispatched') ? 'inline-block' : 'none';
        }

        if (reopenBtn) {
            reopenBtn.style.display = isCancelled ? 'inline-block' : 'none';
        }
    }

    function collectLineReasons() {
        const reasons = [];
        const codes = document.querySelectorAll('.reason-code');

        codes.forEach(select => {
            const lineId = select.dataset.lineId;
            const reasonCode = String(select.value || '').trim();
            const noteInput = document.querySelector('.reason-note[data-line-id="' + lineId + '"]');
            const note = noteInput ? String(noteInput.value || '').trim() : '';

            reasons.push({
                line_id: lineId,
                reason_code: reasonCode || null,
                reason_note: note || null
            });
        });

        return reasons;
    }

    function validateMissingReasons() {
        const codes = document.querySelectorAll('.reason-code');

        for (const select of codes) {
            const lineId = select.dataset.lineId;
            const reasonCode = String(select.value || '').trim();
            const noteInput = document.querySelector('.reason-note[data-line-id="' + lineId + '"]');
            const note = noteInput ? String(noteInput.value || '').trim() : '';

            if (!reasonCode && !note) {
                return {
                    ok: false,
                    lineId
                };
            }
        }

        return { ok: true };
    }

    async function postJson(url, body, method = 'POST') {
        const res = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(body || {})
        });

        const json = await res.json().catch(() => ({}));

        if (!res.ok) {
            throw new Error(json.message || 'Error en la solicitud.');
        }

        return json;
    }

    function configureCanvasStyles() {
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.lineWidth = 3;
        ctx.strokeStyle = '#111111';
        ctx.fillStyle = '#111111';
    }

    function resizeCanvas() {
        const rect = canvas.getBoundingClientRect();
        const snapshot = hasSignature() ? canvas.toDataURL('image/png') : null;

        canvasRatio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = Math.max(1, Math.floor(rect.width * canvasRatio));
        canvas.height = Math.max(1, Math.floor(rect.height * canvasRatio));

        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.scale(canvasRatio, canvasRatio);
        configureCanvasStyles();

        if (snapshot) {
            const img = new Image();
            img.onload = function () {
                ctx.setTransform(1, 0, 0, 1, 0, 0);
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.scale(canvasRatio, canvasRatio);
                configureCanvasStyles();
                ctx.drawImage(img, 0, 0, rect.width, rect.height);
                signatureState.hasContent = true;
                signShell.classList.add('drawing');
            };
            img.src = snapshot;
        } else {
            signatureState.hasContent = false;
            signShell.classList.remove('drawing');
        }
    }

    function getCanvasPos(e) {
        const rect = canvas.getBoundingClientRect();
        return {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top
        };
    }

    function beginStroke(e) {
        const pos = getCanvasPos(e);
        signatureState.drawing = true;
        signatureState.moved = false;
        signatureState.lastX = pos.x;
        signatureState.lastY = pos.y;
        signatureState.pointerId = e.pointerId;

        signShell.classList.add('drawing');
        canvas.setPointerCapture(e.pointerId);

        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
    }

    function moveStroke(e) {
        if (!signatureState.drawing) return;

        const pos = getCanvasPos(e);
        const dx = pos.x - signatureState.lastX;
        const dy = pos.y - signatureState.lastY;
        const distance = Math.sqrt((dx * dx) + (dy * dy));

        if (distance > 0.2) {
            signatureState.moved = true;
            signatureState.hasContent = true;
            signShell.classList.add('drawing');

            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();

            signatureState.lastX = pos.x;
            signatureState.lastY = pos.y;
        }
    }

    function endStroke(e) {
        if (!signatureState.drawing) return;

        if (!signatureState.moved) {
            const pos = getCanvasPos(e);
            signatureState.hasContent = true;
            signShell.classList.add('drawing');

            ctx.beginPath();
            ctx.arc(pos.x, pos.y, 1.5, 0, Math.PI * 2);
            ctx.fill();
            ctx.closePath();
        } else {
            ctx.closePath();
        }

        signatureState.drawing = false;
        signatureState.pointerId = null;
    }

    function clearSignature(showToast = true) {
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.scale(canvasRatio, canvasRatio);
        configureCanvasStyles();

        signatureState.drawing = false;
        signatureState.hasContent = false;
        signatureState.moved = false;
        signatureState.pointerId = null;
        signShell.classList.remove('drawing');

        if (showToast) {
            toast('Firma borrada.', 'info', 1800);
        }
    }

    function hasSignature() {
        if (signatureState.hasContent === true) {
            return true;
        }

        try {
            const pixels = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
            for (let i = 3; i < pixels.length; i += 4) {
                if (pixels[i] !== 0) {
                    return true;
                }
            }
        } catch (e) {
            return signatureState.hasContent === true;
        }

        return false;
    }

    async function doScan() {
        if (scanLock) return;

        const code = codeInput.value.trim();
        const qty = Math.max(1, parseInt(qtyInput.value || '1', 10));

        if (!code) return;

        scanLock = true;
        codeInput.disabled = true;

        try {
            const response = await postJson(scanUrl, { code, qty }, 'POST');

            if (response.shipment) {
                renderAll(response.shipment);
            }

            toast('Lectura procesada correctamente.', 'success');
            codeInput.value = '';
            qtyInput.value = '1';
        } catch (error) {
            toast(error.message || 'Error en lectura.', 'error', 3200);
            codeInput.select();
        } finally {
            scanLock = false;
            codeInput.disabled = false;
            codeInput.focus();
        }
    }

    async function doSaveAssignment() {
        try {
            const response = await postJson(assignUrl, {
                delivery_user_id: deliveryUserSelect.value || null,
                vehicle_id: vehicleSelect.value || null,
                route_plan_id: routeSelect.value || null,
                driver_phone: driverPhoneInput.value.trim() || null
            }, 'PATCH');

            if (response.shipment) {
                renderAll(response.shipment);
            }

            toast('Datos de ruta actualizados.', 'success');
        } catch (error) {
            toast(error.message || 'No se pudo actualizar la asignación.', 'error', 3200);
        }
    }

    async function doClose() {
        syncSignatoryFromResponsible(true);

        if (!deliveryUserSelect.value) {
            await swalInfo('Responsable requerido', 'Debes seleccionar el usuario responsable antes de cerrar la validación.', 'warning');
            deliveryUserSelect.focus();
            return;
        }

        if (!signName.value.trim()) {
            await swalInfo('Nombre requerido', 'No se pudo obtener el nombre de conformidad desde el usuario responsable.', 'warning');
            deliveryUserSelect.focus();
            return;
        }

        const reasonValidation = validateMissingReasons();
        if (!reasonValidation.ok) {
            await swalInfo('Falta justificación', 'Todo producto que no se esté llevando completo debe tener una justificación.', 'warning');
            const select = document.querySelector('.reason-code[data-line-id="' + reasonValidation.lineId + '"]');
            if (select) select.focus();
            return;
        }

        if (!hasSignature()) {
            await swalInfo('Firma requerida', 'Debes firmar antes de cerrar la validación.', 'warning');
            return;
        }

        closeBtn.disabled = true;

        try {
            const response = await postJson(closeUrl, {
                signed_by_name: signName.value.trim(),
                signed_by_role: 'Responsable',
                signature_data: canvas.toDataURL('image/png'),
                notes: closeNotes.value.trim(),
                line_reasons: collectLineReasons()
            }, 'PATCH');

            if (response.shipment) {
                renderAll(response.shipment);
            }

            toast('Validación cerrada exitosamente.', 'success');
        } catch (error) {
            await swalInfo('Error', error.message || 'Error al cerrar la validación.', 'error');
        } finally {
            closeBtn.disabled = false;
        }
    }

    async function doDispatch() {
        const currentStatus = String(shipment.status || '');

        if (!['loaded_complete', 'loaded_partial'].includes(currentStatus)) {
            await swalInfo('Cierre pendiente', 'Primero debes cerrar la validación antes de aprobar la salida.', 'warning');
            return;
        }

        syncSignatoryFromResponsible(true);

        const savedSignature = String(shipment.signature_data || '').trim();
        const signatureToSend = hasSignature() ? canvas.toDataURL('image/png') : savedSignature;

        if (!signatureToSend) {
            await swalInfo('Firma requerida', 'Debes capturar la firma antes de despachar.', 'warning');
            return;
        }

        dispatchBtn.disabled = true;

        try {
            const response = await postJson(dispatchUrl, {
                notes: closeNotes.value.trim(),
                signed_by_name: signName.value.trim(),
                signed_by_role: 'Responsable',
                signature_data: signatureToSend
            }, 'PATCH');

            if (response.shipment) {
                renderAll(response.shipment);
            }

            toast('Salida de unidad aprobada.', 'success');
        } catch (error) {
            await swalInfo('Error', error.message || 'No se pudo aprobar la salida.', 'error');
        } finally {
            dispatchBtn.disabled = false;
        }
    }

    async function doCancel() {
        const result = await swalConfirm(
            'Cancelar proceso',
            '¿Deseas cancelar este proceso de embarque?',
            'Sí, cancelar'
        );

        if (!result.isConfirmed) return;

        try {
            const response = await postJson(cancelUrl, {}, 'PATCH');

            if (response.shipment) {
                renderAll(response.shipment);
            }

            toast('Proceso cancelado.', 'info');
        } catch (error) {
            await swalInfo('Error', error.message || 'No se pudo cancelar el proceso.', 'error');
        }
    }

    async function doReopen() {
        const result = await Swal.fire({
            icon: 'question',
            title: 'Reabrir proceso',
            text: '¿Deseas volver a abrir este embarque cancelado?',
            showCancelButton: true,
            confirmButtonText: 'Sí, reabrir',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#007aff',
            cancelButtonColor: '#888888'
        });

        if (!result.isConfirmed) return;

        reopenBtn.style.pointerEvents = 'none';
        reopenBtn.style.opacity = '.6';

        try {
            const response = await postJson(reopenUrl, {}, 'PATCH');

            if (response.shipment) {
                renderAll(response.shipment);
            }

            toast(response.message || 'Proceso reabierto correctamente.', 'success');
        } catch (error) {
            await swalInfo('Error', error.message || 'No se pudo reabrir el proceso.', 'error');
        } finally {
            reopenBtn.style.pointerEvents = '';
            reopenBtn.style.opacity = '';
        }
    }

    deliveryUserSelect.addEventListener('change', function () {
        applyUserPhone(false);
        syncSignatoryFromResponsible(true);
    });

    saveAssignmentBtn.addEventListener('click', doSaveAssignment);

    createRouteBtn.addEventListener('click', function () {
        window.location.href = buildCreateRouteUrl();
    });

    closeBtn.addEventListener('click', doClose);
    dispatchBtn.addEventListener('click', doDispatch);
    cancelBtn.addEventListener('click', doCancel);
    reopenBtn.addEventListener('click', doReopen);

    document.getElementById('clear-sign-btn').addEventListener('click', function () {
        clearSignature(true);
    });

    codeInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (scanTimer) clearTimeout(scanTimer);
            doScan();
        }
    });

    codeInput.addEventListener('input', function () {
        if (scanTimer) clearTimeout(scanTimer);

        const value = codeInput.value.trim();
        if (!value || value.length < 2) return;

        scanTimer = setTimeout(() => {
            doScan();
        }, 220);
    });

    qtyInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            doScan();
        }
    });

    canvas.addEventListener('pointerdown', function (e) {
        e.preventDefault();
        beginStroke(e);
    });

    canvas.addEventListener('pointermove', function (e) {
        e.preventDefault();
        moveStroke(e);
    });

    canvas.addEventListener('pointerup', function (e) {
        e.preventDefault();
        endStroke(e);
    });

    canvas.addEventListener('pointerleave', function (e) {
        if (!signatureState.drawing) return;
        e.preventDefault();
        endStroke(e);
    });

    canvas.addEventListener('pointercancel', function (e) {
        if (!signatureState.drawing) return;
        e.preventDefault();
        endStroke(e);
    });

    document.querySelector('.ss-wrap').addEventListener('click', function (e) {
        const tag = e.target.tagName;
        if (!['INPUT', 'BUTTON', 'SELECT', 'TEXTAREA', 'A', 'CANVAS', 'SVG', 'PATH'].includes(tag)) {
            codeInput.focus();
        }
    });

    window.addEventListener('resize', resizeCanvas);

    resizeCanvas();
    renderAll(shipment);
    codeInput.focus();
});
</script>
@endsection