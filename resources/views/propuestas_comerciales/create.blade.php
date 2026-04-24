@extends('layouts.app')

@section('content')
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

  .pc-page {
    font-family: 'Quicksand', sans-serif;
    background: var(--bg);
    min-height: 100vh;
    padding: 32px;
    color: var(--ink);
  }

  .pc-wrap {
    max-width: 1480px;
    margin: 0 auto;
  }

  .pc-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 18px;
    margin-bottom: 28px;
  }

  .pc-title {
    margin: 0;
    font-size: 32px;
    font-weight: 700;
    color: #111111;
  }

  .pc-subtitle {
    margin: 8px 0 0;
    color: var(--muted);
    font-size: 15px;
    line-height: 1.7;
    max-width: 860px;
  }

  .pc-layout {
    display: grid;
    grid-template-columns: 420px 1fr;
    gap: 20px;
  }

  .pc-stack {
    display: grid;
    gap: 18px;
  }

  .pc-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    transition: .2s ease;
  }

  .pc-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(0,0,0,0.04);
  }

  .pc-card-head {
    padding: 18px 20px 10px;
    border-bottom: 1px solid rgba(235,235,235,.7);
  }

  .pc-card-title {
    margin: 0;
    font-size: 17px;
    font-weight: 700;
    color: #111111;
  }

  .pc-card-subtitle {
    margin: 8px 0 0;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.6;
  }

  .pc-card-body {
    padding: 20px;
  }

  .field {
    margin-bottom: 16px;
  }

  .field label {
    display: block;
    margin-bottom: 8px;
    color: var(--muted);
    font-size: 13px;
    font-weight: 700;
  }

  .field input,
  .field textarea,
  .field select {
    width: 100%;
    border: 1px solid var(--line);
    background: #fff;
    border-radius: 10px;
    padding: 12px 14px;
    font-family: 'Quicksand', sans-serif;
    font-size: 14px;
    color: var(--ink);
    outline: none;
    transition: .2s ease;
  }

  .field input:focus,
  .field textarea:focus,
  .field select:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .field-inline {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
  }

  .btn {
    appearance: none;
    border: 1px solid transparent;
    border-radius: 10px;
    padding: 12px 18px;
    font-family: 'Quicksand', sans-serif;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: .2s ease;
    width: 100%;
  }

  .btn:active { transform: scale(.98); }

  .btn-primary {
    background: var(--blue);
    color: #fff;
  }

  .btn-primary:hover {
    box-shadow: 0 8px 24px rgba(0,122,255,.12);
    transform: translateY(-1px);
  }

  .btn-outline {
    background: #fff;
    color: var(--blue);
    border-color: var(--blue);
  }

  .btn-outline:hover {
    background: var(--blue-soft);
    transform: translateY(-1px);
  }

  .btn-ghost {
    background: transparent;
    color: #555;
    border-color: var(--line);
  }

  .btn-ghost:hover {
    background: #f9fafb;
  }

  .btn[disabled] {
    opacity: .65;
    cursor: not-allowed;
    transform: none !important;
  }

  .status-box {
    display: none;
    margin-top: 14px;
    padding: 13px 14px;
    border-radius: 12px;
    border: 1px solid var(--line);
    font-size: 13px;
    font-weight: 700;
    line-height: 1.5;
    white-space: pre-wrap;
    word-break: break-word;
  }

  .status-box.show { display: block; }

  .status-box.info {
    background: var(--blue-soft);
    color: var(--blue);
  }

  .status-box.success {
    background: var(--success-soft);
    color: var(--success);
  }

  .status-box.error {
    background: var(--danger-soft);
    color: var(--danger);
  }

  .steps {
    display: grid;
    gap: 12px;
  }

  .step {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    padding: 12px;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: #fff;
  }

  .step-dot {
    width: 28px;
    height: 28px;
    min-width: 28px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    background: #f3f4f6;
    color: #666;
    margin-top: 1px;
  }

  .step.active .step-dot {
    background: var(--blue-soft);
    color: var(--blue);
  }

  .step.done .step-dot {
    background: var(--success-soft);
    color: var(--success);
  }

  .step.error .step-dot {
    background: var(--danger-soft);
    color: var(--danger);
  }

  .step-title {
    font-size: 14px;
    font-weight: 700;
    color: #111;
    margin-bottom: 4px;
  }

  .step-text {
    font-size: 12px;
    color: var(--muted);
    line-height: 1.6;
  }

  .progress-wrap {
    margin-top: 16px;
  }

  .progress-head {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 12px;
    color: var(--muted);
    font-weight: 700;
  }

  .progress {
    width: 100%;
    height: 10px;
    background: #eef2f7;
    border-radius: 999px;
    overflow: hidden;
  }

  .progress-bar {
    width: 0%;
    height: 100%;
    background: var(--blue);
    transition: width .35s ease;
  }

  .meta-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 18px;
  }

  .meta-box {
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 14px;
    background: #fff;
  }

  .meta-label {
    display: block;
    color: var(--muted);
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 6px;
  }

  .meta-value {
    color: #111;
    font-size: 14px;
    line-height: 1.6;
    font-weight: 600;
  }

  .preview-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 18px;
  }

  .list-box {
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 16px;
    background: #fff;
    height: auto;
    max-height: none;
    overflow: visible;
  }

  .list-box-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 14px;
  }

  .list-box-title {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #111;
  }

  .list-box-subtitle {
    margin: 5px 0 0;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.5;
  }

  .items-tools {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 10px;
    margin-bottom: 16px;
  }

  .items-search {
    width: 100%;
    border: 1px solid var(--line);
    background: #fff;
    border-radius: 10px;
    padding: 12px 14px;
    font-family: 'Quicksand', sans-serif;
    font-size: 14px;
    color: var(--ink);
    outline: none;
  }

  .items-search:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .items-counter {
    border: 1px solid var(--line);
    border-radius: 999px;
    padding: 10px 14px;
    background: #f9fafb;
    color: var(--muted);
    font-size: 13px;
    font-weight: 700;
    white-space: nowrap;
  }

  .items-list {
    display: grid;
    gap: 12px;
  }

  .partida-row {
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 16px;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    transition: .2s ease;
  }

  .partida-row:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 22px rgba(0,0,0,0.035);
  }

  .partida-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 10px;
  }

  .partida-title {
    font-size: 15px;
    font-weight: 700;
    color: #111;
  }

  .partida-badge {
    background: var(--blue-soft);
    color: var(--blue);
    border-radius: 999px;
    padding: 6px 10px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
  }

  .partida-desc {
    font-size: 14px;
    line-height: 1.8;
    color: var(--ink);
    margin-bottom: 12px;
    text-transform: none;
  }

  .partida-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  .partida-meta span {
    font-size: 12px;
    color: var(--muted);
    font-weight: 700;
    background: #f9fafb;
    border: 1px solid var(--line);
    border-radius: 999px;
    padding: 7px 10px;
  }

  .empty-note {
    color: var(--muted);
    font-size: 14px;
    line-height: 1.7;
  }

  .actions-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 22px;
  }

  .actions-row .btn {
    width: auto;
  }

  .preview-text {
    border: 1px solid var(--line);
    border-radius: 14px;
    background: #fff;
    padding: 16px;
    min-height: 100px;
    color: var(--ink);
    line-height: 1.7;
    font-size: 14px;
    white-space: pre-wrap;
  }

  .hidden {
    display: none !important;
  }

  @media (max-width: 1180px) {
    .pc-page { padding: 20px; }
    .pc-layout { grid-template-columns: 1fr; }
    .meta-grid { grid-template-columns: 1fr 1fr; }
  }

  @media (max-width: 720px) {
    .field-inline,
    .meta-grid,
    .items-tools {
      grid-template-columns: 1fr;
    }

    .pc-head {
      flex-direction: column;
    }

    .actions-row .btn {
      width: 100%;
    }
  }
</style>

<div class="pc-page">
  <div class="pc-wrap">
    <div class="pc-head">
      <div>
        <h1 class="pc-title">Nueva propuesta comercial</h1>
        <p class="pc-subtitle">
          Sube un PDF y el sistema extraerá todos los productos, equipos, servicios o alimentos solicitados para que puedas cotizarlos.
        </p>
      </div>

      <a href="{{ route('propuestas-comerciales.index') }}" class="btn btn-ghost" style="width:auto;">Volver</a>
    </div>

    @if(session('status'))
      <div class="status-box show success" style="margin-bottom:18px;">{{ session('status') }}</div>
    @endif

    @if(session('error'))
      <div class="status-box show error" style="margin-bottom:18px;">{{ session('error') }}</div>
    @endif

    <div class="pc-layout">
      <div class="pc-stack">
        <div class="pc-card">
          <div class="pc-card-head">
            <h3 class="pc-card-title">1. Subir documento</h3>
            <p class="pc-card-subtitle">
              El PDF se analiza para extraer las partidas reales solicitadas.
            </p>
          </div>

          <div class="pc-card-body">
            <form id="uploadForm" enctype="multipart/form-data">
              @csrf

              <div class="field">
                <label for="file">Archivo PDF</label>
                <input type="file" id="file" name="file" accept="application/pdf" required>
              </div>

              <div class="field">
                <label for="licitacion_pdf_id">ID del PDF / expediente</label>
                <input type="number" id="licitacion_pdf_id" name="licitacion_pdf_id" value="1" min="1" required>
              </div>

              <div class="field">
                <label for="pages_per_chunk">Páginas por bloque</label>
                <input type="number" id="pages_per_chunk" name="pages_per_chunk" value="5" min="1" max="10" required>
              </div>

              <button type="submit" id="uploadBtn" class="btn btn-primary">
                Subir y procesar PDF
              </button>
            </form>

            <div id="uploadStatus" class="status-box"></div>

            <div class="progress-wrap">
              <div class="progress-head">
                <span>Progreso del análisis</span>
                <span id="progressText">0%</span>
              </div>
              <div class="progress">
                <div class="progress-bar" id="progressBar"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="pc-card">
          <div class="pc-card-head">
            <h3 class="pc-card-title">2. Avance paso a paso</h3>
            <p class="pc-card-subtitle">
              Aquí verás el estado del proceso.
            </p>
          </div>

          <div class="pc-card-body">
            <div class="steps">
              <div class="step" id="step-upload">
                <div class="step-dot">1</div>
                <div>
                  <div class="step-title">Documento recibido</div>
                  <div class="step-text">Se guarda el PDF y se genera el registro inicial.</div>
                </div>
              </div>

              <div class="step" id="step-ocr">
                <div class="step-dot">2</div>
                <div>
                  <div class="step-title">OCR con Azure</div>
                  <div class="step-text">Se extrae texto, tablas y layout del documento.</div>
                </div>
              </div>

              <div class="step" id="step-structured">
                <div class="step-dot">3</div>
                <div>
                  <div class="step-title">Preparación</div>
                  <div class="step-text">Se limpia el resultado para encontrar lo cotizable.</div>
                </div>
              </div>

              <div class="step" id="step-items">
                <div class="step-dot">4</div>
                <div>
                  <div class="step-title">Extracción de partidas</div>
                  <div class="step-text">Se detectan productos, equipos, servicios o alimentos.</div>
                </div>
              </div>

              <div class="step" id="step-ready">
                <div class="step-dot">5</div>
                <div>
                  <div class="step-title">Listo para propuesta</div>
                  <div class="step-text">Ya puedes crear la propuesta comercial desde el resultado.</div>
                </div>
              </div>
            </div>

            <div id="jobMeta" class="status-box info" style="margin-top:16px;">
              Aún no se ha iniciado ningún proceso.
            </div>
          </div>
        </div>

        <div class="pc-card">
          <div class="pc-card-head">
            <h3 class="pc-card-title">3. Parámetros comerciales</h3>
            <p class="pc-card-subtitle">
              Estos valores se usarán al crear la propuesta.
            </p>
          </div>

          <div class="pc-card-body">
            <div class="field">
              <label for="titulo">Título de la propuesta</label>
              <input type="text" id="titulo" placeholder="Ej. Propuesta comercial">
            </div>

            <div class="field-inline">
              <div class="field">
                <label for="porcentaje_utilidad">Utilidad %</label>
                <input type="number" step="0.01" id="porcentaje_utilidad" value="0">
              </div>

              <div class="field">
                <label for="porcentaje_descuento">Descuento %</label>
                <input type="number" step="0.01" id="porcentaje_descuento" value="0">
              </div>

              <div class="field">
                <label for="porcentaje_impuesto">Impuesto %</label>
                <input type="number" step="0.01" id="porcentaje_impuesto" value="16">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="pc-stack">
        <div class="pc-card">
          <div class="pc-card-head">
            <h3 class="pc-card-title">Vista previa de partidas extraídas</h3>
            <p class="pc-card-subtitle">
              Aquí aparecerán todas las partidas detectadas, sin recortes.
            </p>
          </div>

          <div class="pc-card-body">
            <div class="meta-grid">
              <div class="meta-box">
                <span class="meta-label">Procedimiento</span>
                <div class="meta-value" id="metaFolio">—</div>
              </div>

              <div class="meta-box">
                <span class="meta-label">Dependencia</span>
                <div class="meta-value" id="metaDependencia">—</div>
              </div>

              <div class="meta-box">
                <span class="meta-label">Partidas detectadas</span>
                <div class="meta-value" id="metaPartidas">0</div>
              </div>

              <div class="meta-box">
                <span class="meta-label">Archivo</span>
                <div class="meta-value" id="metaArchivo">—</div>
              </div>
            </div>

            <div class="field">
              <label>Resumen</label>
              <div class="preview-text" id="metaObjeto">Aún no hay datos cargados.</div>
            </div>

            <div class="preview-grid">
              <div class="list-box">
                <div class="list-box-head">
                  <div>
                    <h4 class="list-box-title">Todas las partidas para cotizar</h4>
                    <p class="list-box-subtitle">Se muestran completas. Puedes buscar por número, unidad o descripción.</p>
                  </div>
                </div>

                <div class="items-tools">
                  <input type="search" id="itemsSearch" class="items-search" placeholder="Buscar partida, producto, unidad o descripción...">
                  <div class="items-counter" id="itemsCounter">0 partidas</div>
                </div>

                <div id="partidasBox" class="empty-note">Cuando termine el análisis aparecerán aquí.</div>
              </div>
            </div>

            <div class="actions-row">
              <button id="createProposalBtn" class="btn btn-primary" type="button" disabled>
                Crear propuesta comercial
              </button>
              <button id="refreshBtn" class="btn btn-outline" type="button" disabled>
                Actualizar estado
              </button>
            </div>

            <div id="createStatus" class="status-box"></div>
          </div>
        </div>
      </div>
    </div>

    <form id="createProposalForm" method="POST" action="{{ route('propuestas-comerciales.store-from-run-manual') }}" class="hidden">
      @csrf
      <input type="hidden" name="document_ai_run_id" id="form_document_ai_run_id">
      <input type="hidden" name="titulo" id="form_titulo">
      <input type="hidden" name="cliente" id="form_cliente">
      <input type="hidden" name="folio" id="form_folio">
      <input type="hidden" name="porcentaje_utilidad" id="form_utilidad">
      <input type="hidden" name="porcentaje_descuento" id="form_descuento">
      <input type="hidden" name="porcentaje_impuesto" id="form_impuesto">
    </form>
  </div>
</div>

<script>
  window.documentAiStartUrl = @json(route('document-ai.start'));
  window.documentAiShowDebugBase = @json(url('/document-ai-debug'));

  const uploadForm = document.getElementById('uploadForm');
  const uploadBtn = document.getElementById('uploadBtn');
  const uploadStatus = document.getElementById('uploadStatus');
  const jobMeta = document.getElementById('jobMeta');
  const refreshBtn = document.getElementById('refreshBtn');
  const createProposalBtn = document.getElementById('createProposalBtn');
  const createStatus = document.getElementById('createStatus');

  const progressBar = document.getElementById('progressBar');
  const progressText = document.getElementById('progressText');

  const metaFolio = document.getElementById('metaFolio');
  const metaDependencia = document.getElementById('metaDependencia');
  const metaPartidas = document.getElementById('metaPartidas');
  const metaArchivo = document.getElementById('metaArchivo');
  const metaObjeto = document.getElementById('metaObjeto');

  const partidasBox = document.getElementById('partidasBox');
  const itemsSearch = document.getElementById('itemsSearch');
  const itemsCounter = document.getElementById('itemsCounter');

  const stepUpload = document.getElementById('step-upload');
  const stepOcr = document.getElementById('step-ocr');
  const stepStructured = document.getElementById('step-structured');
  const stepItems = document.getElementById('step-items');
  const stepReady = document.getElementById('step-ready');

  let currentRunId = null;
  let pollingTimer = null;
  let latestRunPayload = null;
  let latestItems = [];

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function valueOrDash(value) {
    if (value === null || value === undefined || value === '') return '—';
    return value;
  }

  function getItemNumber(item) {
    return item?.subpartida ?? item?.partida ?? item?.numero ?? item?.num_prog ?? item?.renglon ?? item?.renglón ?? null;
  }

  function getItemLabel(item) {
    if (item?.subpartida !== null && item?.subpartida !== undefined && item?.subpartida !== '') return 'Subpartida';
    if (item?.partida !== null && item?.partida !== undefined && item?.partida !== '') return 'Partida';
    if (item?.numero !== null && item?.numero !== undefined && item?.numero !== '') return 'Número';
    if (item?.num_prog !== null && item?.num_prog !== undefined && item?.num_prog !== '') return 'Núm. prog.';
    return 'Número';
  }

  function getItemDescription(item) {
    return item?.descripcion ?? item?.nombre ?? item?.concepto ?? item?.producto ?? item?.servicio ?? item?.bien ?? 'Sin descripción';
  }

  function getItemSearchText(item) {
    return [
      getItemLabel(item),
      getItemNumber(item),
      getItemDescription(item),
      item?.unidad,
      item?.cantidad,
      item?.cantidad_minima,
      item?.cantidad_maxima,
      item?.presentar_muestra
    ].join(' ').toLowerCase();
  }

  function renderItems(items) {
    const query = (itemsSearch.value || '').trim().toLowerCase();

    const filtered = Array.isArray(items)
      ? items.filter(item => !query || getItemSearchText(item).includes(query))
      : [];

    itemsCounter.textContent = `${filtered.length} de ${Array.isArray(items) ? items.length : 0} partidas`;

    if (!filtered.length) {
      partidasBox.className = 'empty-note';
      partidasBox.innerHTML = query
        ? 'No hay partidas que coincidan con la búsqueda.'
        : 'No hay partidas extraídas todavía.';
      return;
    }

    partidasBox.className = 'items-list';

    let html = '';

    filtered.forEach((item, index) => {
      const label = getItemLabel(item);
      const number = getItemNumber(item);
      const description = getItemDescription(item);

      const unidad = item?.unidad;
      const cantidad = item?.cantidad;
      const min = item?.cantidad_minima;
      const max = item?.cantidad_maxima;
      const muestra = item?.presentar_muestra;

      html += `
        <div class="partida-row">
          <div class="partida-top">
            <div class="partida-title">${escapeHtml(label)} ${escapeHtml(valueOrDash(number))}</div>
            <div class="partida-badge">#${index + 1}</div>
          </div>

          <div class="partida-desc">${escapeHtml(description)}</div>

          <div class="partida-meta">
            <span>Unidad: ${escapeHtml(valueOrDash(unidad))}</span>
            ${cantidad !== null && cantidad !== undefined && cantidad !== '' ? `<span>Cantidad: ${escapeHtml(cantidad)}</span>` : ''}
            ${min !== null && min !== undefined && min !== '' ? `<span>Min: ${escapeHtml(min)}</span>` : ''}
            ${max !== null && max !== undefined && max !== '' ? `<span>Max: ${escapeHtml(max)}</span>` : ''}
            <span>Muestra: ${escapeHtml(valueOrDash(muestra))}</span>
          </div>
        </div>
      `;
    });

    partidasBox.innerHTML = html;
  }

  function setStatus(el, type, text) {
    el.className = 'status-box show ' + type;
    el.textContent = text;
  }

  function setProgress(percent) {
    progressBar.style.width = percent + '%';
    progressText.textContent = percent + '%';
  }

  function resetSteps() {
    [stepUpload, stepOcr, stepStructured, stepItems, stepReady].forEach(step => {
      step.classList.remove('active', 'done', 'error');
    });
  }

  function resetPreview() {
    metaFolio.textContent = '—';
    metaDependencia.textContent = '—';
    metaPartidas.textContent = '0';
    metaArchivo.textContent = '—';
    metaObjeto.textContent = 'Aún no hay datos cargados.';
    partidasBox.className = 'empty-note';
    partidasBox.innerHTML = 'Cuando termine el análisis aparecerán aquí.';
    itemsSearch.value = '';
    itemsCounter.textContent = '0 partidas';
    createProposalBtn.disabled = true;
    refreshBtn.disabled = true;
    latestRunPayload = null;
    latestItems = [];
  }

  function renderStructured(run) {
    const structured = run?.structured_json || null;
    const itemsResult = run?.items_json || null;
    const items = Array.isArray(itemsResult?.items) ? itemsResult.items : [];

    latestItems = items;

    metaArchivo.textContent = run?.filename || '—';
    metaFolio.textContent = structured?.numero_procedimiento || '—';
    metaDependencia.textContent = structured?.dependencia || '—';

    if (items.length) {
      metaObjeto.textContent = `Se detectaron ${items.length} partidas/productos para cotizar. Revisa la lista completa antes de crear la propuesta comercial.`;
    } else {
      metaObjeto.textContent = 'El OCR terminó, pero no se detectaron partidas/productos para cotizar.';
    }

    metaPartidas.textContent = items.length;
    renderItems(items);
  }

  async function safeJson(response) {
    const text = await response.text();

    try {
      return JSON.parse(text);
    } catch (e) {
      console.error('Respuesta no JSON:', text);

      return {
        ok: false,
        message: 'Respuesta no válida',
        raw_text: text,
        parse_error: e.message,
        status_code: response.status
      };
    }
  }

  async function pollRun() {
    if (!currentRunId) return;

    try {
      const response = await fetch(`${window.documentAiShowDebugBase}/${currentRunId}`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json'
        }
      });

      const data = await safeJson(response);

      if (!response.ok || !data.ok) {
        let msg = data.message || 'No se pudo consultar el estado del análisis.';

        if (data.raw_text) {
          msg += ' | RAW: ' + String(data.raw_text).slice(0, 800);
        }

        if (data.parse_error) {
          msg += ' | PARSE: ' + data.parse_error;
        }

        setStatus(uploadStatus, 'error', msg);
        stepUpload.classList.add('done');
        stepOcr.classList.add('error');
        setProgress(100);
        return;
      }

      latestRunPayload = data;
      const run = data.run || {};
      const status = run.status || 'queued';

      jobMeta.className = 'status-box show info';
      jobMeta.textContent = `Run #${run.id} · Archivo: ${run.filename || '—'} · Estado: ${String(status).toUpperCase()}`;

      if (status === 'queued') {
        resetSteps();
        stepUpload.classList.add('done');
        stepOcr.classList.add('active');
        setProgress(20);
        setStatus(uploadStatus, 'info', 'El documento ya se subió. Iniciando OCR...');
        refreshBtn.disabled = false;
        pollingTimer = setTimeout(pollRun, 7000);
        return;
      }

      if (status === 'processing') {
        resetSteps();
        stepUpload.classList.add('done');
        stepOcr.classList.add('done');
        stepStructured.classList.add('active');
        stepItems.classList.add('active');
        setProgress(60);
        setStatus(uploadStatus, 'info', 'Procesando OCR y extrayendo partidas para cotizar...');
        refreshBtn.disabled = false;
        pollingTimer = setTimeout(pollRun, 7000);
        return;
      }

      if (status === 'completed') {
        resetSteps();
        stepUpload.classList.add('done');
        stepOcr.classList.add('done');
        stepStructured.classList.add('done');
        stepItems.classList.add('done');
        stepReady.classList.add('done');
        setProgress(100);

        setStatus(uploadStatus, run.error ? 'info' : 'success', run.error || 'Análisis completado correctamente. Ya puedes crear la propuesta.');
        renderStructured(run);
        createProposalBtn.disabled = false;
        refreshBtn.disabled = false;
        return;
      }

      if (status === 'failed') {
        resetSteps();
        stepUpload.classList.add('done');
        stepOcr.classList.add('error');
        stepStructured.classList.add('error');
        stepItems.classList.add('error');
        setProgress(100);
        setStatus(uploadStatus, 'error', run.error || 'El análisis falló.');
        refreshBtn.disabled = false;
        return;
      }

      pollingTimer = setTimeout(pollRun, 7000);
    } catch (error) {
      console.error(error);
      setStatus(uploadStatus, 'error', error.message || 'Error consultando el análisis.');
    }
  }

  uploadForm.addEventListener('submit', async function (e) {
    e.preventDefault();

    if (pollingTimer) clearTimeout(pollingTimer);

    currentRunId = null;
    resetSteps();
    resetPreview();
    setProgress(8);

    uploadBtn.disabled = true;
    uploadBtn.textContent = 'Procesando...';

    stepUpload.classList.add('active');
    setStatus(uploadStatus, 'info', 'Subiendo documento...');

    try {
      const formData = new FormData(uploadForm);

      const response = await fetch(window.documentAiStartUrl, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
          'Accept': 'application/json'
        },
        body: formData
      });

      const data = await safeJson(response);

      if (!response.ok || !data.ok) {
        let msg = data.message || 'No se pudo iniciar el análisis.';

        if (data.raw_text) {
          msg += ' | RAW: ' + String(data.raw_text).slice(0, 800);
        }

        if (data.parse_error) {
          msg += ' | PARSE: ' + data.parse_error;
        }

        stepUpload.classList.remove('active');
        stepUpload.classList.add('error');
        setProgress(100);
        setStatus(uploadStatus, 'error', msg);
        return;
      }

      currentRunId = data.document_ai_run_id;
      stepUpload.classList.remove('active');
      stepUpload.classList.add('done');
      stepOcr.classList.add('active');
      setProgress(18);
      setStatus(uploadStatus, 'info', `Documento enviado correctamente. Run #${currentRunId}`);
      refreshBtn.disabled = false;

      pollRun();
    } catch (error) {
      console.error(error);
      stepUpload.classList.remove('active');
      stepUpload.classList.add('error');
      setProgress(100);
      setStatus(uploadStatus, 'error', error.message || 'Ocurrió un error al enviar el PDF.');
    } finally {
      uploadBtn.disabled = false;
      uploadBtn.textContent = 'Subir y procesar PDF';
    }
  });

  refreshBtn.addEventListener('click', function () {
    if (!currentRunId) return;
    if (pollingTimer) clearTimeout(pollingTimer);
    pollRun();
  });

  itemsSearch.addEventListener('input', function () {
    renderItems(latestItems);
  });

  createProposalBtn.addEventListener('click', function () {
    if (!latestRunPayload || !latestRunPayload.run) {
      setStatus(createStatus, 'error', 'Aún no hay un análisis listo para crear la propuesta.');
      return;
    }

    const run = latestRunPayload.run;
    const structured = run.structured_json || {};

    document.getElementById('form_document_ai_run_id').value = run.id;
    document.getElementById('form_titulo').value =
      document.getElementById('titulo').value || structured.objeto || `Propuesta comercial ${run.filename || ''}`;
    document.getElementById('form_cliente').value =
      structured.dependencia || '';
    document.getElementById('form_folio').value =
      structured.numero_procedimiento || '';
    document.getElementById('form_utilidad').value =
      document.getElementById('porcentaje_utilidad').value || 0;
    document.getElementById('form_descuento').value =
      document.getElementById('porcentaje_descuento').value || 0;
    document.getElementById('form_impuesto').value =
      document.getElementById('porcentaje_impuesto').value || 16;

    setStatus(createStatus, 'info', 'Creando propuesta comercial...');
    document.getElementById('createProposalForm').submit();
  });
</script>
@endsection