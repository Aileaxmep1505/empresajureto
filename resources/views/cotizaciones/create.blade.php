@extends('layouts.app')
@section('title','Nueva cotización')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Quattrocento+Sans" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/cotizaciones.css') }}?v={{ time() }}">

<!-- ========== LOADER HTML ========== -->
<div id="appLoader" class="loading is-active" aria-hidden="true" aria-live="polite">
  <div class="loading-text" aria-label="Cargando">
    <span class="loading-text-words">L</span>
    <span class="loading-text-words">O</span>
    <span class="loading-text-words">A</span>
    <span class="loading-text-words">D</span>
    <span class="loading-text-words">I</span>
    <span class="loading-text-words">N</span>
    <span class="loading-text-words">G</span>
  </div>
</div>

<div class="wrap">
  <!-- Tabs -->
  <div class="tabs" role="tablist" aria-label="Modo de creación">
    <button class="tab is-active" id="tab-manual" role="tab" aria-controls="panel-manual" aria-selected="true">Manual</button>
    <button class="tab" id="tab-ai" role="tab" aria-controls="panel-ai" aria-selected="false">Desde PDF (IA)</button>
  </div>

  <div class="tab-panels">
    <!-- ========================= PANEL MANUAL ========================= -->
    <section id="panel-manual" role="tabpanel" aria-labelledby="tab-manual">
      <form method="POST" action="{{ route('cotizaciones.store') }}" id="form">
        @csrf
        <div class="layout">
          <!-- Columna izquierda -->
          <div class="panel">
            <div class="head"><h2>Nueva cotización</h2></div>
            <div class="body">

              {{-- 1) Productos --}}
              <h3 style="margin:0 0 8px 0">Productos</h3>
              <div class="sdrop" id="sd-producto">
                <input type="text" class="sdrop-input" id="producto_search" placeholder="Buscar producto..." autocomplete="off">
                <div class="sdrop-list" hidden></div>
              </div>

              <table class="table" id="items">
                <thead>
                  <tr>
                    <th style="width:34%">Producto</th>
                    <th style="width:10%">Cant.</th>
                    <th style="width:14%">P. Unit. (desde costo)</th>
                    <th style="width:12%">Desc.</th>
                    <th style="width:10%">IVA%</th>
                    <th style="width:14%">Importe</th>
                    <th style="width:6%"></th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>

              <div class="grid" style="margin-top:12px">
                <div>
                  <label>Descuento global</label>
                  <input class="input" type="number" step="0.01" name="descuento" id="desc_global" value="0">
                </div>
                <div>
                  <label>Envío</label>
                  <input class="input" type="number" step="0.01" name="envio" id="envio" value="0">
                </div>
                <div>
                  <label>Utilidad global (%)</label>
                  <input class="input" type="number" step="0.01" min="0" name="utilidad_global" id="utilidad_global" value="0" placeholder="Ej. 10">
                  <div class="small">Se aplica sobre el <strong>costo</strong> de cada producto. Ej: costo 20 con 10% ⇒ ganancia 2, P. Unit. = 22.</div>
                </div>
              </div>

              <hr class="sep">

              <div class="grid">
                <div>
                  <label>Validez (días)</label>
                  <input class="input" type="number" min="0" name="validez_dias" id="validez_dias" value="15">
                </div>
                <div></div>
              </div>

              <div style="margin-top:12px">
                <label>Notas</label>
                <textarea class="input" name="notas" id="notas" rows="3" placeholder="Notas visibles en la cotización"></textarea>
              </div>

              <div class="actions" style="margin-top:12px">
                <button class="btn save" type="submit">Guardar cotización</button>
              </div>

              <input type="hidden" name="items" id="items_json">
            </div>
          </div>

          <!-- Columna derecha -->
          <div class="aside-sticky">
            <!-- Cliente -->
            <div class="panel" style="margin-bottom:16px">
              <div class="head"><h3>Cliente</h3><span class="badge" id="cli-id">—</span></div>
              <div class="body">
                <div class="sdrop" id="sd-cliente-side" style="margin-bottom:10px">
                  <input type="text" class="sdrop-input" id="cliente_search" placeholder="Buscar cliente..." autocomplete="off">
                  <input type="hidden" name="cliente_id" id="cliente_id">
                  <div class="sdrop-list" hidden></div>
                </div>

                <div class="kv"><div class="muted">Nombre</div><div id="cli-nombre">—</div></div>
                <div class="kv"><div class="muted">Email</div><div id="cli-email">—</div></div>
                <div class="kv"><div class="muted">Teléfono</div><div id="cli-telefono">—</div></div>
                <div class="kv"><div class="muted">RFC/NIT</div><div id="cli-rfc">—</div></div>
                <div class="kv"><div class="muted">Dirección</div><div id="cli-direccion">—</div></div>
                <div class="kv"><div class="muted">Ciudad / Estado</div><div id="cli-ubicacion">—</div></div>
                <div class="kv"><div class="muted">CP</div><div id="cli-cp">—</div></div>
              </div>
            </div>

            <!-- Resumen -->
            <div class="panel totals" style="margin-bottom:16px">
              <div class="head"><h3>Resumen</h3></div>
              <div class="body">
                <table>
                  <tr><td>Inversión (costo)</td><td style="text-align:right" id="t_inversion">$0.00</td></tr>
                  <tr><td>Ganancia estimada</td><td style="text-align:right" id="t_ganancia">$0.00</td></tr>
                  <tr><td class="sum">Subtotal</td><td class="sum" style="text-align:right" id="t_subtotal">$0.00</td></tr>
                  <tr><td>IVA</td>     <td style="text-align:right" id="t_iva">$0.00</td></tr>
                  <tr><td>Descuento</td><td style="text-align:right" id="t_desc_global">$0.00</td></tr>
                  <tr><td>Envío</td>   <td style="text-align:right" id="t_envio">$0.00</td></tr>
                  <tr><td class="sum">TOTAL</td><td class="sum" style="text-align:right" id="t_total">$0.00</td></tr>
                </table>
              </div>
            </div>

            <!-- Financiamiento -->
            <div class="panel">
              <div class="head"><h3>Financiamiento</h3></div>
              <div class="body">
                <label style="display:flex;gap:8px;align-items:center;margin-bottom:10px">
                  <input type="checkbox" name="financiamiento[aplicar]" id="fin_aplicar" value="1">
                  Aplicar financiamiento
                </label>

                <div class="grid" style="margin-bottom:10px">
                  <div>
                    <label>Plazos</label>
                    <input class="input" type="number" min="1" id="fin_plazos" name="financiamiento[numero_plazos]" placeholder="Ej. 6" disabled>
                  </div>
                  <div>
                    <label>Enganche</label>
                    <input class="input" type="number" step="0.01" id="fin_enganche" name="financiamiento[enganche]" value="0" disabled>
                  </div>
                  <div>
                    <label>Tasa anual (%)</label>
                    <input class="input" type="number" step="0.01" id="fin_tasa" name="financiamiento[tasa_anual]" placeholder="Ej. 18" disabled>
                  </div>
                  <div>
                    <label>Primer vencimiento</label>
                    <input class="input" type="date" id="fin_inicio" name="financiamiento[primer_vencimiento]" disabled>
                  </div>
                </div>

                <div id="plan_wrap" style="display:none">
                  <table class="table" id="plan_table" style="margin-top:0">
                    <thead><tr><th>#</th><th>Vence</th><th>Monto</th></tr></thead>
                    <tbody></tbody>
                  </table>
                  <div class="small">Las cuotas se calculan como <em>(Total – Enganche) / Plazos</em>. La tasa es informativa (el backend actual no aplica interés).</div>
                </div>
              </div>
            </div>
          </div>
        </div> <!-- /layout -->
      </form>
    </section>

    <!-- ========================= PANEL IA DESDE PDF ========================= -->
    <section id="panel-ai" role="tabpanel" aria-labelledby="tab-ai" hidden>
      <div class="panel">
        <div class="head"><h2>Cargar PDF para generar cotización</h2></div>
        <div class="body">
          <div class="ai-note">
            La IA leerá el PDF (incluye OCR si hace falta), detectará páginas relevantes, extraerá conceptos y mapeará con tu catálogo. No toma precios del PDF.
          </div>

          <div class="ai-row" style="margin-top:12px">
            <div>
              <label>Archivo PDF</label>
              <input class="input" type="file" id="pdf_file" accept="application/pdf">
              <div class="small">Máximo 20 MB.</div>
            </div>
            <div>
              <label>Páginas a analizar (opcional)</label>
              <input class="input" type="text" id="pages_force" placeholder="Ej: 1,3-5,8">
            </div>
            <div>
              <button class="btn brand" id="btn_parse">Analizar PDF con IA</button>
            </div>
          </div>

          <hr class="sep">

          <div id="ai_result" style="display:none">
            <div class="grid">
              <div>
                <div class="badge">Resumen IA</div>
                <div class="kv"><div class="muted">Cliente/Entidad</div><div id="ai_cliente">—</div></div>
                <div class="kv"><div class="muted">Tipo de emisor</div><div id="ai_issuer_kind">—</div></div>
                <div class="kv"><div class="muted">Objeto / Título</div><div id="ai_objeto">—</div></div>
                <div class="kv"><div class="muted">Procedimiento</div><div id="ai_proc">—</div></div>
                <div class="kv"><div class="muted">Dependencia / Unidad</div><div id="ai_dep">—</div></div>
                <div class="kv"><div class="muted">Lugar de entrega</div><div id="ai_lugar">—</div></div>
                <div class="kv"><div class="muted">Condiciones de pago</div><div id="ai_pago">—</div></div>
                <div class="kv"><div class="muted">Moneda</div><div id="ai_moneda">—</div></div>

                <div class="badge" style="margin-top:10px">Fechas clave</div>
                <div class="kv"><div class="muted">Publicación</div><div id="ai_f_pub">—</div></div>
                <div class="kv"><div class="muted">Aclaraciones</div><div id="ai_f_acl">—</div></div>
                <div class="kv"><div class="muted">Presentación</div><div id="ai_f_pre">—</div></div>
                <div class="kv"><div class="muted">Fallo</div><div id="ai_f_fal">—</div></div>
                <div class="kv"><div class="muted">Vigencia cotización (días)</div><div id="ai_validez">—</div></div>

                <div class="badge" style="margin-top:10px">Conteo / Mapeo</div>
                <div class="kv"><div class="muted">Items detectados</div><div id="ai_items_count">0</div></div>
                <div class="kv"><div class="muted">Páginas relevantes</div><div id="ai_pages">—</div></div>
                <div class="kv"><div class="muted">Envío sugerido</div><div id="ai_envio_sug">$0.00</div></div>
              </div>

              <div>
                <div class="badge">Diagnóstico</div>
                <div class="small">OCR usado: <span id="ai_ocr" class="warn">no</span></div>
                <div class="small">Motivo selección de páginas:</div>
                <div class="ai-log" id="ai_reason">—</div>

                <div class="badge" style="margin-top:10px">Resumen por página</div>
                <div id="ai_pages_overview" class="ai-log" style="max-height:320px">—</div>
              </div>
            </div>

            <div id="ai_skipped" class="small warn" style="display:none;margin-top:8px"></div>

            <!-- ========== Pendientes IA ========== -->
            <div class="panel" style="margin-top:14px;border:1px dashed var(--line)">
              <div class="head" style="padding:10px 14px"><h3 style="font-size:16px">Pendientes IA</h3></div>
              <div class="body" id="ai_pendientes_wrap">
                <div class="small">No hay pendientes.</div>
              </div>
            </div>

            <div class="row" style="margin-top:12px;justify-content:flex-end">
              <button class="btn ghost" id="btn_apply">Aplicar al formulario</button>
              <button class="btn save" id="btn_apply_and_switch">Aplicar y pasar a Manual</button>
            </div>
          </div>

          <div id="ai_status" class="small" style="margin-top:12px;color:#334155"></div>
        </div>
      </div>
    </section>
  </div> <!-- /tab-panels -->
</div>

<!-- ================= MODAL: Buscar en catálogo ================= -->
<div class="modal" id="catalogModal" aria-hidden="true" aria-labelledby="catalogTitle">
  <div class="back" data-close-modal></div>
  <div class="card" role="dialog" aria-modal="true">
    <div class="head">
      <h3 id="catalogTitle" style="margin:0">Buscar en catálogo</h3>
    </div>
    <div class="content">
      <div class="row" style="margin:10px 0">
        <input id="catalogQuery" class="input" type="text" placeholder="Escribe para buscar...">
        <button class="btn brand" id="catalogSearchBtn">Buscar</button>
      </div>
      <div id="catalogResults"></div>
      <div class="small" id="catalogHint" style="margin-top:8px;color:#64748b"></div>
    </div>
  </div>
</div>

{{-- Bootstrap de datos para JS (sin lógica) --}}
<script id="cotiz-bootstrap" type="application/json">
{!! json_encode([
    'clientesInfo'   => $clientesInfo->keyBy('id'),
    'clientesSelect' => $clientesSelect,
    // IMPORTANTE: asegurar que cada producto traiga 'cost' (además de otros campos)
    'productos'      => $productos,
    'routes' => [
        'buscarProductos' => route('cotizaciones.buscar_productos'),
        'aiParse'         => route('cotizaciones.ai_parse'),
    ],
    'csrf' => csrf_token(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>

{{-- Tu JS externo (defer para asegurar DOM listo) --}}
<script defer src="{{ asset('js/cotizaciones.js') }}?v={{ time() }}"></script>
@endsection
