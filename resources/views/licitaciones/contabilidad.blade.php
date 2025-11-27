@extends('layouts.app')
@section('title','Contabilidad de la licitación')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">

<style>
:root{
  --mint:#48cfad;
  --mint-dark:#34c29e;
  --ink:#111827;
  --muted:#6b7280;
  --line:#e6eef6;
  --card:#ffffff;
  --danger:#ef4444;
  --success:#16a34a;
  --shadow:0 12px 34px rgba(12,18,30,0.06);
}
*{box-sizing:border-box}
body{font-family:"Open Sans",sans-serif;background:#f3f5f7;color:var(--ink);margin:0;padding:0}

/* Wrapper */
.wizard-wrap{max-width:1000px;margin:56px auto;padding:18px;}
.panel{background:var(--card);border-radius:16px;box-shadow:var(--shadow);overflow:hidden;}
.panel-head{padding:20px 22px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:16px;}
.hgroup h2{margin:0;font-weight:700;font-size:20px;}
.hgroup p{margin:4px 0 0;color:var(--muted);font-size:13px;max-width:520px;}
.step-tag{font-size:11px;text-transform:uppercase;letter-spacing:.14em;color:var(--mint-dark);font-weight:700;margin-bottom:4px;}
.back-link{display:inline-flex;align-items:center;gap:8px;color:var(--muted);text-decoration:none;padding:8px 12px;border-radius:999px;border:1px solid var(--line);background:#fff;font-size:12px;}
.back-link:hover{border-color:#dbe7ef;color:var(--ink);}

/* Form container */
.form{padding:20px;}
.grid{display:grid;grid-template-columns:1fr;gap:18px;}
.grid-2{grid-template-columns:repeat(2,minmax(0,1fr));}
.grid-3{grid-template-columns:repeat(3,minmax(0,1fr));}
.grid-4{grid-template-columns:repeat(4,minmax(0,1fr));}
@media(max-width:1024px){ .grid-4{grid-template-columns:repeat(2,minmax(0,1fr));} }
@media(max-width:800px){
  .grid-3{grid-template-columns:1fr;}
  .grid-2{grid-template-columns:1fr;}
}

/* Cards */
.subcard{
  border-radius:14px;
  border:1px solid var(--line);
  padding:14px 14px 16px;
  background:#fff;
}
.subcard-title{
  font-size:13px;
  font-weight:600;
  color:var(--ink);
  margin-bottom:4px;
}
.subcard-sub{
  font-size:11px;
  color:var(--muted);
  margin-bottom:10px;
}

/* Alerts */
.alert-success{
  border-radius:12px;
  background:#ecfdf3;
  border:1px solid #bbf7d0;
  padding:10px 12px;
  font-size:13px;
  color:#166534;
  margin:16px 20px 0 20px;
}
.alert-error{
  border-radius:12px;
  background:#fef2f2;
  border:1px solid #fecaca;
  padding:10px 12px;
  font-size:13px;
  color:#b91c1c;
  margin:16px 20px 0 20px;
}
.alert-error ul{margin:0;padding-left:18px;}
.alert-error li{margin:2px 0;}

/* Inputs */
.field-label{
  display:block;
  font-size:12px;
  font-weight:500;
  color:var(--ink);
  margin-bottom:2px;
}
.field-input,
.field-textarea,
.field-readonly{
  width:100%;
  border-radius:10px;
  border:1px solid #e5e7eb;
  padding:7px 10px;
  font-size:13px;
  outline:none;
  font-family:inherit;
}
.field-input:focus,
.field-textarea:focus{
  border-color:#c7d2fe;
  box-shadow:0 0 0 1px rgba(79,70,229,0.16);
}
.field-readonly{
  background:#f9fafb;
  color:var(--muted);
}
.field-textarea{
  resize:vertical;
  min-height:70px;
}
.field-hint{
  font-size:11px;
  color:var(--muted);
  margin-bottom:4px;
}

/* Estado financiero */
.fin-card{
  border-radius:14px;
  border:1px solid var(--line);
  padding:14px 14px 12px;
  background:#f9fafb;
}
.fin-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:10px;
  margin-bottom:8px;
}
.fin-title{
  font-size:13px;
  font-weight:600;
}
.fin-pill{
  display:inline-flex;
  align-items:center;
  gap:6px;
  font-size:11px;
  padding:3px 8px;
  border-radius:999px;
  border:1px solid #d1fae5;
  background:#ecfdf3;
  color:#166534;
}
.fin-pill.neg{
  border-color:#fee2e2;
  background:#fef2f2;
  color:#b91c1c;
}
.fin-rows{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:6px 16px;
  font-size:12px;
}
@media(max-width:700px){
  .fin-rows{grid-template-columns:1fr;}
}
.fin-row-label{
  color:var(--muted);
}
.fin-row-value{
  font-weight:600;
}

/* Charts */
.charts-wrap{
  margin-top:14px;
  display:grid;
  grid-template-columns:minmax(0,1.2fr) minmax(0,1fr);
  gap:16px;
}
@media(max-width:900px){
  .charts-wrap{grid-template-columns:1fr;}
}
.chart-box{
  border-radius:14px;
  border:1px solid var(--line);
  background:#fff;
  padding:12px;
}
.chart-title{
  font-size:12px;
  font-weight:600;
  margin-bottom:4px;
}

/* Actions */
.actions-line{
  margin-top:18px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
}
.actions-right{
  display:flex;
  gap:12px;
  align-items:center;
}
.link-back{
  font-size:12px;
  color:var(--muted);
  text-decoration:none;
}
.link-back:hover{color:var(--ink);text-decoration:underline;}
.btn{
  border:0;
  border-radius:10px;
  padding:9px 15px;
  font-weight:700;
  cursor:pointer;
  font-size:13px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  white-space:nowrap;
  font-family:inherit;
}
.btn-primary{
  background:var(--mint);
  color:#fff;
  box-shadow:0 8px 20px rgba(52,194,158,0.12);
}
.btn-primary:hover{background:var(--mint-dark);}
.btn-ghost{
  background:#fff;
  border:1px solid var(--line);
  color:var(--ink);
}
.btn-ghost:hover{border-color:#dbe7ef;}
.btn-secondary{
  background:#111827;
  color:#fff;
}
.btn-secondary:hover{
  background:#020617;
}

@media(max-width:540px){
  .actions-line{flex-direction:column;align-items:flex-start;}
  .actions-right{width:100%;justify-content:flex-end;flex-wrap:wrap;}
}
</style>

@php
    /** @var \App\Models\LicitacionContabilidad|null $contabilidad */
    $cont = $contabilidad ?? null;
    $dc = $cont && is_array($cont->detalle_costos ?? null) ? $cont->detalle_costos : [];
@endphp

<div class="wizard-wrap">
    <div class="panel">
        <div class="panel-head">
            <div class="hgroup">
                <div class="step-tag">Paso 12 de 12</div>
                <h2>Contabilidad de la licitación</h2>
                <p>
                    Registra el monto licitado, la inversión en productos y todos los gastos relacionados
                    (renta, nóminas, gasolina, etc.). El sistema te mostrará en tiempo real el estado financiero,
                    el porcentaje de ganancia y gráficas.
                </p>
            </div>

            <a href="{{ route('licitaciones.checklist.facturacion.edit', $licitacion) }}" class="back-link" title="Volver al paso anterior">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Paso anterior
            </a>
        </div>

        @if(session('success'))
            <div class="alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert-error">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="form" action="{{ route('licitaciones.contabilidad.store', $licitacion) }}" method="POST" novalidate>
            @csrf

            {{-- ====== BLOQUE 1: MONTOS PRINCIPALES ====== --}}
            <div class="subcard">
                <div class="subcard-title">Montos principales</div>
                <div class="subcard-sub">
                    Aquí defines de cuánto fue la licitación ganada, cuánto invertirás en productos
                    y el sistema calculará la utilidad estimada.
                </div>

                <div class="grid grid-3">
                    {{-- Monto licitado / ofertado --}}
                    <div>
                        <label class="field-label" for="monto_inversion_estimado">
                            Monto licitado / oferta ganadora (ingreso)
                        </label>
                        <input
                            id="monto_inversion_estimado"
                            type="number"
                            step="0.01"
                            min="0"
                            name="monto_inversion_estimado"
                            value="{{ old('monto_inversion_estimado', $cont->monto_inversion_estimado ?? null) }}"
                            class="field-input js-money js-refresh"
                            placeholder="0.00"
                        >
                        <div class="field-hint">Ejemplo: 8000000 (8 millones)</div>
                    </div>

                    {{-- Inversión en productos --}}
                    <div>
                        <label class="field-label" for="dc_productos">
                            Inversión en productos (costo directo)
                        </label>
                        <input
                            id="dc_productos"
                            type="number"
                            step="0.01"
                            min="0"
                            name="detalle_costos[productos]"
                            value="{{ old('detalle_costos.productos', $dc['productos'] ?? null) }}"
                            class="field-input js-money js-refresh"
                            data-role="costo"
                            data-tipo="productos"
                            placeholder="0.00"
                        >
                        <div class="field-hint">Ejemplo: 6000000</div>
                    </div>

                    {{-- Costo total (auto) --}}
                    <div>
                        <label class="field-label" for="costo_total_vis">
                            Costo total estimado (productos + gastos)
                        </label>
                        <input
                            id="costo_total_vis"
                            type="text"
                            class="field-readonly"
                            readonly
                            value=""
                        >
                        {{-- real que se envía al backend --}}
                        <input
                            id="costo_total"
                            type="hidden"
                            name="costo_total"
                            value="{{ old('costo_total', $cont->costo_total ?? 0) }}"
                        >
                        {{-- utilidad estimada real --}}
                        <input
                            id="utilidad_estimada"
                            type="hidden"
                            name="utilidad_estimada"
                            value="{{ old('utilidad_estimada', $cont->utilidad_estimada ?? 0) }}"
                        >
                        <div class="field-hint">Se actualiza con la suma de todos los gastos.</div>
                    </div>
                </div>
            </div>

            {{-- ====== BLOQUE 2: GASTOS OPERATIVOS ====== --}}
            <div class="subcard" style="margin-top:16px;">
                <div class="subcard-title">Gastos operativos del mes</div>
                <div class="subcard-sub">
                    Registra pagos como renta, servicios, nóminas, gasolina, viáticos, casetas, declaraciones y otros.
                    Todo se suma automáticamente a los costos para calcular la utilidad.
                </div>

                <div class="grid grid-4">
                    {{-- Renta --}}
                    <div>
                        <label class="field-label" for="dc_renta">Renta</label>
                        <input
                            id="dc_renta"
                            type="number"
                            step="0.01"
                            min="0"
                            name="detalle_costos[renta]"
                            value="{{ old('detalle_costos.renta', $dc['renta'] ?? null) }}"
                            class="field-input js-money js-refresh"
                            data-role="costo"
                            data-tipo="operativo"
                            placeholder="0.00"
                        >
                    </div>

                    {{-- Luz --}}
                    <div>
                        <label class="field-label" for="dc_luz">Luz</label>
                        <input
                            id="dc_luz"
                            type="number"
                            step="0.01"
                            min="0"
                            name="detalle_costos[luz]"
                            value="{{ old('detalle_costos.luz', $dc['luz'] ?? null) }}"
                            class="field-input js-money js-refresh"
                            data-role="costo"
                            data-tipo="operativo"
                            placeholder="0.00"
                        >
                    </div>

                    {{-- Agua --}}
                    <div>
                        <label class="field-label" for="dc_agua">Agua</label>
                        <input
                            id="dc_agua"
                            type="number"
                            step="0.01"
                            min="0"
                            name="detalle_costos[agua]"
                            value="{{ old('detalle_costos.agua', $dc['agua'] ?? null) }}"
                            class="field-input js-money js-refresh"
                            data-role="costo"
                            data-tipo="operativo"
                            placeholder="0.00"
                        >
                    </div>

                    {{-- Nóminas --}}
                    <div>
                        <label class="field-label" for="dc_nominas">Nóminas</label>
                        <input
                            id="dc_nominas"
                            type="number"
                            step="0.01"
                            min="0"
                            name="detalle_costos[nominas]"
                            value="{{ old('detalle_costos.nominas', $dc['nominas'] ?? null) }}"
                            class="field-input js-money js-refresh"
                            data-role="costo"
                            data-tipo="operativo"
                            placeholder="0.00"
                        >
                    </div>

                    {{-- IMSS --}}
                    <div>
                        <label class="field-label" for="dc_imss">IMSS</label>
                        <input
                            id="dc_imss"
                            type="number"
                            step="0.01"
                            min="0"
                            name="detalle_costos[imss]"
                            value="{{ old('detalle_costos.imss', $dc['imss'] ?? null) }}"
                            class="field-input js-money js-refresh"
                            data-role="costo"
                            data-tipo="operativo"
                            placeholder="0.00"
                        >
                    </div>

                    {{-- Gasolina --}}
                    <div>
                        <label class="field-label" for="dc_gasolina">Gasolina</label>
                        <input
                            id="dc_gasolina"
                            type="number"
                            step="0.01"
                            min="0"
                            name="detalle_costos[gasolina]"
                            value="{{ old('detalle_costos.gasolina', $dc['gasolina'] ?? null) }}"
                            class="field-input js-money js-refresh"
                            data-role="costo"
                            data-tipo="operativo"
                            placeholder="0.00"
                        >
                    </div>

                    {{-- Viáticos --}}
                    <div>
                        <label class="field-label" for="dc_viaticos">Viáticos</label>
                        <input
                            id="dc_viaticos"
                            type="number"
                            step="0.01"
                            min="0"
                            name="detalle_costos[viaticos]"
                            value="{{ old('detalle_costos.viaticos', $dc['viaticos'] ?? null) }}"
                            class="field-input js-money js-refresh"
                            data-role="costo"
                            data-tipo="operativo"
                            placeholder="0.00"
                        >
                    </div>

                    {{-- Casetas --}}
                    <div>
                        <label class="field-label" for="dc_casetas">Casetas</label>
                        <input
                            id="dc_casetas"
                            type="number"
                            step="0.01"
                            min="0"
                            name="detalle_costos[casetas]"
                            value="{{ old('detalle_costos.casetas', $dc['casetas'] ?? null) }}"
                            class="field-input js-money js-refresh"
                            data-role="costo"
                            data-tipo="operativo"
                            placeholder="0.00"
                        >
                    </div>

                    {{-- Pagos gobierno / declaraciones --}}
                    <div>
                        <label class="field-label" for="dc_pagos_gob">Pagos gobierno / declaraciones</label>
                        <input
                            id="dc_pagos_gob"
                            type="number"
                            step="0.01"
                            min="0"
                            name="detalle_costos[pagos_gobierno]"
                            value="{{ old('detalle_costos.pagos_gobierno', $dc['pagos_gobierno'] ?? null) }}"
                            class="field-input js-money js-refresh"
                            data-role="costo"
                            data-tipo="operativo"
                            placeholder="0.00"
                        >
                    </div>

                    {{-- Mantenimiento camionetas --}}
                    <div>
                        <label class="field-label" for="dc_mantenimiento">Mantenimiento camionetas</label>
                        <input
                            id="dc_mantenimiento"
                            type="number"
                            step="0.01"
                            min="0"
                            name="detalle_costos[mantenimiento_camionetas]"
                            value="{{ old('detalle_costos.mantenimiento_camionetas', $dc['mantenimiento_camionetas'] ?? null) }}"
                            class="field-input js-money js-refresh"
                            data-role="costo"
                            data-tipo="operativo"
                            placeholder="0.00"
                        >
                    </div>

                    {{-- Libre 1 --}}
                    <div>
                        <label class="field-label" for="dc_libre1_monto">
                            Otro gasto 1
                        </label>
                        <input
                            id="dc_libre1_label"
                            type="text"
                            name="detalle_costos[libre_1_label]"
                            value="{{ old('detalle_costos.libre_1_label', $dc['libre_1_label'] ?? 'Otro gasto 1') }}"
                            class="field-input"
                            style="margin-bottom:4px;font-size:12px;"
                        >
                        <input
                            id="dc_libre1_monto"
                            type="number"
                            step="0.01"
                            min="0"
                            name="detalle_costos[libre_1]"
                            value="{{ old('detalle_costos.libre_1', $dc['libre_1'] ?? null) }}"
                            class="field-input js-money js-refresh"
                            data-role="costo"
                            data-tipo="operativo"
                            placeholder="0.00"
                        >
                    </div>

                    {{-- Libre 2 --}}
                    <div>
                        <label class="field-label" for="dc_libre2_monto">
                            Otro gasto 2
                        </label>
                        <input
                            id="dc_libre2_label"
                            type="text"
                            name="detalle_costos[libre_2_label]"
                            value="{{ old('detalle_costos.libre_2_label', $dc['libre_2_label'] ?? 'Otro gasto 2') }}"
                            class="field-input"
                            style="margin-bottom:4px;font-size:12px;"
                        >
                        <input
                            id="dc_libre2_monto"
                            type="number"
                            step="0.01"
                            min="0"
                            name="detalle_costos[libre_2]"
                            value="{{ old('detalle_costos.libre_2', $dc['libre_2'] ?? null) }}"
                            class="field-input js-money js-refresh"
                            data-role="costo"
                            data-tipo="operativo"
                            placeholder="0.00"
                        >
                    </div>
                </div>
            </div>

            {{-- ====== BLOQUE 3: NOTAS Y ESTADO FINANCIERO ====== --}}
            <div class="grid grid-2" style="margin-top:16px;">
                {{-- Notas contables --}}
                <div class="subcard">
                    <div class="subcard-title">Notas contables</div>
                    <div class="subcard-sub">
                        Observaciones adicionales, condiciones de pago, retenciones, comentarios de facturación, etc.
                    </div>
                    <textarea
                        id="notas"
                        name="notas"
                        rows="6"
                        class="field-textarea"
                        placeholder="Ej. Esta licitación incluye retención del 4% de IVA; pagos a 30 días; etc."
                    >{{ old('notas', $cont->notas ?? '') }}</textarea>
                </div>

                {{-- Estado financiero en tiempo real --}}
                <div class="fin-card">
                    <div class="fin-header">
                        <div class="fin-title">Estado financiero (en tiempo real)</div>
                        <div id="finPill" class="fin-pill">
                            <span id="finPillText">Sin calcular</span>
                        </div>
                    </div>
                    <div class="fin-rows">
                        <div>
                            <div class="fin-row-label">Monto licitado / ingreso</div>
                            <div class="fin-row-value" id="sfMontoLicitado">$0.00</div>
                        </div>
                        <div>
                            <div class="fin-row-label">Inversión en productos</div>
                            <div class="fin-row-value" id="sfGastoProductos">$0.00</div>
                        </div>
                        <div>
                            <div class="fin-row-label">Gastos operativos</div>
                            <div class="fin-row-value" id="sfGastosOperativos">$0.00</div>
                        </div>
                        <div>
                            <div class="fin-row-label">Costo total</div>
                            <div class="fin-row-value" id="sfCostoTotal">$0.00</div>
                        </div>
                        <div>
                            <div class="fin-row-label">Resultado</div>
                            <div class="fin-row-value" id="sfUtilidad">$0.00</div>
                        </div>
                        <div>
                            <div class="fin-row-label">Margen sobre licitación</div>
                            <div class="fin-row-value" id="sfMargen">0.0 %</div>
                        </div>
                    </div>

                    <div class="charts-wrap">
                        <div class="chart-box">
                            <div class="chart-title">Distribución de costos vs utilidad (barras)</div>
                            <canvas id="chartBarras" height="150"></canvas>
                        </div>
                        <div class="chart-box">
                            <div class="chart-title">Participación de cada rubro (pastel)</div>
                            <canvas id="chartPie" height="150"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Acciones --}}
            <div class="actions-line">
                <a href="{{ route('licitaciones.checklist.facturacion.edit', $licitacion) }}" class="link-back">
                    ← Volver al paso anterior
                </a>
                <div class="actions-right">
                    {{-- Botón para descargar PDF del estado financiero (debes crear esta ruta/controlador) --}}
                    <button
                        type="button"
                        class="btn btn-secondary"
                        onclick="window.open('{{ route('licitaciones.contabilidad.pdf', $licitacion) }}','_blank')"
                    >
                        Descargar estado financiero (PDF)
                    </button>

                    <a href="{{ route('licitaciones.index') }}" class="btn btn-ghost">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Guardar y cerrar licitación
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
(function(){
    'use strict';

    function parseNumber(v){
        if(v === undefined || v === null) return 0;
        const n = parseFloat(String(v).replace(/,/g,''));
        return isNaN(n) ? 0 : n;
    }
    function formatoMoneda(n){
        return n.toLocaleString('es-MX', { style:'currency', currency:'MXN', maximumFractionDigits:2 });
    }
    function formatoPorcentaje(p){
        if(!isFinite(p)) return '0.0 %';
        return p.toFixed(1) + ' %';
    }

    const inputMontoLicitado = document.getElementById('monto_inversion_estimado');
    const inputCostoTotalHidden = document.getElementById('costo_total');
    const inputUtilidadHidden   = document.getElementById('utilidad_estimada');
    const inputCostoVis         = document.getElementById('costo_total_vis');

    const finPill     = document.getElementById('finPill');
    const finPillText = document.getElementById('finPillText');
    const sfMontoLicitado   = document.getElementById('sfMontoLicitado');
    const sfGastoProductos  = document.getElementById('sfGastoProductos');
    const sfGastosOperativos= document.getElementById('sfGastosOperativos');
    const sfCostoTotal      = document.getElementById('sfCostoTotal');
    const sfUtilidad        = document.getElementById('sfUtilidad');
    const sfMargen          = document.getElementById('sfMargen');

    const camposCosto = document.querySelectorAll('input[data-role="costo"]');

    // Charts
    const ctxBar = document.getElementById('chartBarras');
    const ctxPie = document.getElementById('chartPie');
    let barChart = null;
    let pieChart = null;

    function updateCharts(gastoProd, gastosOp, utilidad){
        if(!ctxBar || !ctxPie) return;

        const etiquetaUtilidad = utilidad >= 0 ? 'Utilidad' : 'Pérdida';
        const valorUtilidad = Math.abs(utilidad);

        const barData = {
            labels: ['Productos', 'Gastos operativos', etiquetaUtilidad],
            datasets: [{
                label: 'Monto',
                data: [gastoProd, gastosOp, valorUtilidad],
                borderWidth: 1
            }]
        };

        const pieData = {
            labels: ['Productos', 'Gastos operativos', etiquetaUtilidad],
            datasets: [{
                data: [gastoProd, gastosOp, valorUtilidad],
            }]
        };

        if(!barChart){
            barChart = new Chart(ctxBar, {
                type: 'bar',
                data: barData,
                options: {
                    responsive:true,
                    plugins:{ legend:{ display:false } },
                    scales:{
                        y:{ beginAtZero:true }
                    }
                }
            });
        }else{
            barChart.data = barData;
            barChart.update();
        }

        if(!pieChart){
            pieChart = new Chart(ctxPie, {
                type: 'pie',
                data: pieData,
                options:{
                    responsive:true,
                    plugins:{ legend:{ position:'bottom' } }
                }
            });
        }else{
            pieChart.data = pieData;
            pieChart.update();
        }
    }

    function refreshEstado(){
        const montoLicitado = parseNumber(inputMontoLicitado ? inputMontoLicitado.value : 0);

        let gastoProductos = 0;
        let gastosOperativos = 0;

        camposCosto.forEach(function(el){
            const val = parseNumber(el.value);
            const tipo = el.dataset.tipo;
            if(tipo === 'productos'){
                gastoProductos += val;
            }else{
                gastosOperativos += val;
            }
        });

        const costoTotal = gastoProductos + gastosOperativos;
        const utilidad   = montoLicitado - costoTotal;
        const margen     = montoLicitado > 0 ? (utilidad / montoLicitado) * 100 : 0;

        // Actualizar campos hidden que se van al backend
        if(inputCostoTotalHidden){
            inputCostoTotalHidden.value = costoTotal.toFixed(2);
        }
        if(inputUtilidadHidden){
            inputUtilidadHidden.value = utilidad.toFixed(2);
        }
        if(inputCostoVis){
            inputCostoVis.value = formatoMoneda(costoTotal);
        }

        // Actualizar resumen
        sfMontoLicitado.textContent    = formatoMoneda(montoLicitado);
        sfGastoProductos.textContent   = formatoMoneda(gastoProductos);
        sfGastosOperativos.textContent = formatoMoneda(gastosOperativos);
        sfCostoTotal.textContent       = formatoMoneda(costoTotal);
        sfUtilidad.textContent         = formatoMoneda(utilidad);
        sfMargen.textContent           = formatoPorcentaje(margen);

        // Pill de estado
        finPill.classList.remove('neg');
        if(!montoLicitado && !costoTotal){
            finPillText.textContent = 'Sin calcular';
        }else if(utilidad >= 0){
            finPillText.textContent = 'Ganancia estimada';
        }else{
            finPillText.textContent = 'Pérdida estimada';
            finPill.classList.add('neg');
        }

        // Actualizar gráficas
        updateCharts(gastoProductos, gastosOperativos, utilidad);
    }

    // Escuchar cambios en todos los inputs relevantes
    const inputsRefresh = document.querySelectorAll('.js-refresh');
    inputsRefresh.forEach(function(el){
        el.addEventListener('input', refreshEstado);
        el.addEventListener('change', refreshEstado);
    });

    // Primera carga
    refreshEstado();
})();
</script>
@endsection
