@extends('layouts.app')
@section('content_class', 'content--flush')
@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

    :root {
        --primary: #007AFF;
        --primary-light: #EBF5FF;
        --accent: #8B5CF6;
        --success: #00a650;
        --danger: #ef4444;
        --warning: #b45309;
        --text-main: #1F2937;
        --text-muted: #6B7280;
        --glass-bg: rgba(255, 255, 255, 0.85);
        --glass-border: rgba(255, 255, 255, 0.5);
    }

    .ai-wrapper {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background: #f8fafc;
        background: radial-gradient(at 0% 0%, rgba(235, 245, 255, 1) 0, transparent 50%),
                    radial-gradient(at 100% 100%, rgba(245, 243, 255, 1) 0, transparent 50%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
        padding: 24px;
    }

    .orb {
        position: absolute;
        width: 500px;
        height: 500px;
        border-radius: 50%;
        filter: blur(100px);
        z-index: 0;
        opacity: 0.4;
        animation: orbRotate 20s infinite alternate;
    }

    .orb-blue {
        background: #60a5fa;
        top: -10%;
        left: -10%;
    }

    .orb-purple {
        background: #c084fc;
        bottom: -10%;
        right: -10%;
        animation-delay: -5s;
    }

    @keyframes orbRotate {
        from {
            transform: translate(0, 0) scale(1);
        }

        to {
            transform: translate(50px, 100px) scale(1.2);
        }
    }

    .glass-container {
        position: relative;
        z-index: 10;
        width: 100%;
        max-width: 550px;
        background: var(--glass-bg);
        backdrop-filter: blur(30px) saturate(150%);
        -webkit-backdrop-filter: blur(30px) saturate(150%);
        border: 1px solid var(--glass-border);
        border-radius: 32px;
        padding: 48px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.02);
        text-align: center;
        transition: all 0.5s ease;
    }

    .title {
        font-size: 26px;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 12px;
    }

    .desc {
        color: var(--text-muted);
        font-size: 15px;
        line-height: 1.6;
        margin-bottom: 32px;
    }

    .upload-area {
        transition: all 0.4s ease;
    }

    .icon-box {
        width: 80px;
        height: 80px;
        background: white;
        border-radius: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
        color: var(--primary);
        animation: floatIcon 3s infinite ease-in-out;
    }

    @keyframes floatIcon {
        0%, 100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    .file-btn {
        background: var(--primary);
        color: white;
        padding: 16px 40px;
        border-radius: 100px;
        font-weight: 600;
        font-size: 16px;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 10px 20px rgba(0, 122, 255, 0.2);
        display: inline-block;
    }

    .file-btn:hover {
        background: #0066d6;
        transform: scale(1.05);
        box-shadow: 0 15px 25px rgba(0, 122, 255, 0.3);
    }

    .file-btn.disabled {
        opacity: .6;
        cursor: not-allowed;
        pointer-events: none;
    }

    .ai-processing {
        display: none;
        opacity: 0;
        transition: opacity 0.5s ease;
    }

    .ai-processing.active {
        display: block;
        opacity: 1;
    }

    .ai-badge {
        display: inline-block;
        padding: 6px 16px;
        background: var(--primary-light);
        color: var(--primary);
        border-radius: 100px;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 16px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .progress-section {
        margin: 32px 0;
    }

    .meta-data {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        font-size: 14px;
        font-weight: 700;
        color: var(--text-main);
    }

    .bar-track {
        height: 12px;
        background: #f1f5f9;
        border-radius: 100px;
        overflow: hidden;
        position: relative;
    }

    .bar-fill {
        height: 100%;
        width: 0%;
        border-radius: 100px;
        position: relative;
        background: linear-gradient(90deg, #007AFF, #8B5CF6, #007AFF);
        background-size: 200% 100%;
        transition: width 0.35s ease;
    }

    .bar-fill::after {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        animation: shimmer 1.5s infinite;
    }

    @keyframes shimmer {
        0% {
            transform: translateX(-100%);
        }

        100% {
            transform: translateX(100%);
        }
    }

    .ai-message {
        font-size: 16px;
        color: var(--text-main);
        font-weight: 500;
        min-height: 24px;
        transition: opacity 0.3s ease;
    }

    .timer-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #F3F4F6;
        padding: 6px 16px;
        border-radius: 100px;
        color: var(--text-muted);
        font-size: 13px;
        margin-top: 24px;
    }

    .status-card {
        display: none;
        margin-top: 26px;
        border-radius: 18px;
        padding: 16px;
        text-align: left;
        border: 1px solid transparent;
        font-size: 14px;
        line-height: 1.55;
    }

    .status-card.show {
        display: block;
    }

    .status-card.error {
        background: #fef2f2;
        border-color: #fecaca;
        color: #b91c1c;
    }

    .status-card.warning {
        background: #fffbeb;
        border-color: #fde68a;
        color: #92400e;
    }

    .status-card-title {
        display: block;
        font-weight: 800;
        margin-bottom: 5px;
    }

    .status-card-message {
        display: block;
        word-break: break-word;
    }

    .retry-btn {
        margin-top: 14px;
        border: 0;
        background: #fff;
        color: var(--primary);
        border-radius: 12px;
        padding: 10px 14px;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 6px 14px rgba(0,0,0,.04);
    }

    .retry-btn:hover {
        background: #f8fafc;
    }

    .success-area {
        display: none;
        text-align: center;
    }

    .success-area.active {
        display: block;
        animation: popIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    }

    @keyframes popIn {
        0% {
            opacity: 0;
            transform: scale(0.8);
        }

        100% {
            opacity: 1;
            transform: scale(1);
        }
    }

    .check-circle {
        width: 90px;
        height: 90px;
        background: var(--success);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
        box-shadow: 0 15px 30px rgba(0, 166, 80, 0.3);
    }

    .check-circle svg {
        width: 45px;
        height: 45px;
        color: white;
        stroke-dasharray: 100;
        stroke-dashoffset: 100;
        animation: drawCheck 0.6s ease forwards 0.3s;
    }

    @keyframes drawCheck {
        to {
            stroke-dashoffset: 0;
        }
    }

    .hidden {
        display: none !important;
    }

    @media (max-width: 640px) {
        .glass-container {
            padding: 32px 22px;
            border-radius: 26px;
        }

        .title {
            font-size: 23px;
        }

        .file-btn {
            width: 100%;
            padding: 15px 20px;
        }
    }
</style>

<div class="ai-wrapper">
    <div class="orb orb-blue"></div>
    <div class="orb orb-purple"></div>

    <div class="glass-container">

        <div id="step-upload" class="upload-area">
            <div class="icon-box">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
            </div>

            <h1 class="title">Análisis Inteligente</h1>

            <p class="desc">
                Sube tu licitación en PDF. Nuestra IA estructurará partidas, cantidades y especificaciones automáticamente.
            </p>

            <label class="file-btn" id="selectFileBtn">
                <span>Seleccionar Archivo</span>
                <input type="file" id="mainPdfInput" class="hidden" accept="application/pdf">
            </label>

            <div id="uploadStatusCard" class="status-card"></div>
        </div>

        <div id="step-ai" class="ai-processing">
            <div class="ai-badge">IA Activa</div>

            <h1 class="title">Estructurando Datos</h1>

            <div class="progress-section">
                <div class="meta-data">
                    <span id="aiActionStatus">Preparando...</span>
                    <span id="percentLabel">0%</span>
                </div>

                <div class="bar-track">
                    <div class="bar-fill" id="mainBarFill"></div>
                </div>
            </div>

            <p class="ai-message" id="dynamicMessage">Esperando documento...</p>

            <div class="timer-pill">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>

                <span id="elapsedTime">00:00</span>
            </div>

            <div id="processingStatusCard" class="status-card"></div>
        </div>

        <div id="step-success" class="success-area">
            <div class="check-circle">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>

            <h1 class="title" style="color: var(--text-main);">¡Listo!</h1>

            <p class="desc" style="margin-bottom: 0;">
                Análisis completado. Redirigiendo a los resultados...
            </p>

            <div id="successStatusCard" class="status-card"></div>
        </div>

    </div>
</div>

<script>
    window.documentAiStartUrl = @json(route('document-ai.start'));
    window.documentAiShowDebugBase = @json(url('/document-ai-debug'));
    window.storeProposalUrl = @json(route('propuestas-comerciales.store-from-run-manual'));
    window.csrfToken = @json(csrf_token());

    const fileInput = document.getElementById('mainPdfInput');
    const selectFileBtn = document.getElementById('selectFileBtn');

    const uploadArea = document.getElementById('step-upload');
    const aiArea = document.getElementById('step-ai');
    const successArea = document.getElementById('step-success');

    const barFill = document.getElementById('mainBarFill');
    const percentLabel = document.getElementById('percentLabel');
    const dynamicMessage = document.getElementById('dynamicMessage');
    const elapsedTime = document.getElementById('elapsedTime');
    const aiActionStatus = document.getElementById('aiActionStatus');

    const uploadStatusCard = document.getElementById('uploadStatusCard');
    const processingStatusCard = document.getElementById('processingStatusCard');
    const successStatusCard = document.getElementById('successStatusCard');

    let seconds = 0;
    let timer = null;
    let pollingTimer = null;
    let currentRunId = null;
    let latestRunPayload = null;
    let isCreatingProposal = false;

    function showErrorCard(card, title, message, showRetry = true) {
        card.className = 'status-card show error';

        card.innerHTML = `
            <span class="status-card-title">${escapeHtml(title)}</span>
            <span class="status-card-message">${escapeHtml(message)}</span>
            ${showRetry ? '<button type="button" class="retry-btn" onclick="resetUploadView()">Intentar de nuevo</button>' : ''}
        `;
    }

    function showWarningCard(card, title, message, showRetry = true) {
        card.className = 'status-card show warning';

        card.innerHTML = `
            <span class="status-card-title">${escapeHtml(title)}</span>
            <span class="status-card-message">${escapeHtml(message)}</span>
            ${showRetry ? '<button type="button" class="retry-btn" onclick="resetUploadView()">Intentar de nuevo</button>' : ''}
        `;
    }

    function hideCard(card) {
        card.className = 'status-card';
        card.innerHTML = '';
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    async function safeJson(response) {
        const text = await response.text();

        try {
            return JSON.parse(text);
        } catch (error) {
            return {
                ok: false,
                message: 'El servidor respondió algo que no es JSON válido.',
                raw_text: text,
                parse_error: error.message,
                status_code: response.status
            };
        }
    }

    function setProgress(percent, label = null, message = null) {
        const cleanPercent = Math.max(0, Math.min(100, Number(percent || 0)));

        barFill.style.width = cleanPercent + '%';
        percentLabel.innerText = Math.floor(cleanPercent) + '%';

        if (label) {
            aiActionStatus.innerText = label;
        }

        if (message) {
            dynamicMessage.style.opacity = '0';

            setTimeout(() => {
                dynamicMessage.innerText = message;
                dynamicMessage.style.opacity = '1';
            }, 180);
        }
    }

    function startTimer() {
        seconds = 0;
        elapsedTime.innerText = '00:00';

        if (timer) {
            clearInterval(timer);
        }

        timer = setInterval(() => {
            seconds++;

            const m = Math.floor(seconds / 60).toString().padStart(2, '0');
            const s = (seconds % 60).toString().padStart(2, '0');

            elapsedTime.innerText = `${m}:${s}`;
        }, 1000);
    }

    function stopTimer() {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    }

    function clearPolling() {
        if (pollingTimer) {
            clearTimeout(pollingTimer);
            pollingTimer = null;
        }
    }

    function goToProcessingView() {
        uploadArea.style.opacity = '0';
        uploadArea.style.transform = 'translateY(-20px)';

        setTimeout(() => {
            uploadArea.classList.add('hidden');
            aiArea.classList.add('active');
            startTimer();
        }, 400);
    }

    function resetUploadView() {
        clearPolling();
        stopTimer();

        currentRunId = null;
        latestRunPayload = null;
        isCreatingProposal = false;

        fileInput.value = '';
        selectFileBtn.classList.remove('disabled');

        hideCard(uploadStatusCard);
        hideCard(processingStatusCard);
        hideCard(successStatusCard);

        setProgress(0, 'Preparando...', 'Esperando documento...');

        aiArea.classList.remove('active');
        aiArea.classList.add('hidden');
        aiArea.style.opacity = '1';

        successArea.classList.remove('active');
        successArea.classList.add('hidden');

        uploadArea.classList.remove('hidden');
        uploadArea.style.opacity = '1';
        uploadArea.style.transform = 'translateY(0)';
    }

    fileInput.addEventListener('change', function () {
        if (!this.files || !this.files.length) {
            return;
        }

        const file = this.files[0];

        if (file.type !== 'application/pdf') {
            showErrorCard(uploadStatusCard, 'Archivo no válido', 'Selecciona un archivo PDF.', false);
            fileInput.value = '';
            return;
        }

        hideCard(uploadStatusCard);
        hideCard(processingStatusCard);
        hideCard(successStatusCard);

        startRealProcess(file);
    });

    async function startRealProcess(file) {
        clearPolling();
        stopTimer();

        currentRunId = null;
        latestRunPayload = null;
        isCreatingProposal = false;

        selectFileBtn.classList.add('disabled');

        goToProcessingView();

        setProgress(8, 'Subiendo...', 'Subiendo documento al servidor...');

        try {
            const formData = new FormData();

            formData.append('file', file);

            /*
             * Si tienes un expediente real, cambia este 1 por tu ID dinámico.
             */
            formData.append('licitacion_pdf_id', '1');
            formData.append('pages_per_chunk', '5');

            const response = await fetch(window.documentAiStartUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await safeJson(response);

            if (!response.ok || !data.ok) {
                let msg = data.message || 'No se pudo iniciar el análisis del documento.';

                if (data.raw_text) {
                    msg += ' | Respuesta: ' + String(data.raw_text).slice(0, 500);
                }

                throw new Error(msg);
            }

            currentRunId = data.document_ai_run_id;

            setProgress(18, 'OCR iniciado', 'Documento recibido. Iniciando análisis inteligente...');

            pollRun();
        } catch (error) {
            stopTimer();
            clearPolling();

            setProgress(100, 'Error', 'No se pudo iniciar el proceso.');

            showErrorCard(
                processingStatusCard,
                'No se pudo procesar el PDF',
                error.message || 'Ocurrió un error al enviar el archivo.',
                true
            );
        }
    }

    async function pollRun() {
        if (!currentRunId || isCreatingProposal) {
            return;
        }

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
                    msg += ' | Respuesta: ' + String(data.raw_text).slice(0, 500);
                }

                throw new Error(msg);
            }

            latestRunPayload = data;

            const run = data.run || {};
            const status = run.status || 'queued';

            if (status === 'queued') {
                setProgress(25, 'En cola', 'Preparando OCR...');
                pollingTimer = setTimeout(pollRun, 7000);
                return;
            }

            if (status === 'processing') {
                setProgress(62, 'Analizando...', 'Extrayendo partidas, cantidades y especificaciones...');
                pollingTimer = setTimeout(pollRun, 7000);
                return;
            }

            if (status === 'completed') {
                setProgress(88, 'Creando propuesta', 'Análisis completado. Creando propuesta comercial...');

                const itemsResult = run.items_json || {};
                const items = Array.isArray(itemsResult.items) ? itemsResult.items : [];

                if (!items.length) {
                    stopTimer();

                    setProgress(100, 'Sin partidas', 'El OCR terminó, pero no se detectaron partidas.');

                    showWarningCard(
                        processingStatusCard,
                        'No se pudo crear la propuesta',
                        run.error || 'El análisis terminó, pero no se detectaron partidas válidas para cotizar. Revisa que el PDF contenga productos, servicios o conceptos claros.',
                        true
                    );

                    return;
                }

                await createProposalFromRun(run);
                return;
            }

            if (status === 'failed') {
                stopTimer();

                setProgress(100, 'Falló', 'El análisis falló.');

                showErrorCard(
                    processingStatusCard,
                    'El análisis falló',
                    run.error || 'No se pudo completar el procesamiento del documento.',
                    true
                );

                return;
            }

            setProgress(45, 'Procesando...', `Estado actual: ${String(status).toUpperCase()}`);
            pollingTimer = setTimeout(pollRun, 7000);
        } catch (error) {
            stopTimer();
            clearPolling();

            setProgress(100, 'Error', 'No se pudo consultar el análisis.');

            showErrorCard(
                processingStatusCard,
                'Error consultando el análisis',
                error.message || 'Ocurrió un error consultando el estado del documento.',
                true
            );
        }
    }

    async function createProposalFromRun(run) {
        if (isCreatingProposal) {
            return;
        }

        isCreatingProposal = true;
        clearPolling();

        try {
            setProgress(92, 'Guardando...', 'Creando propuesta comercial...');

            const structured = run.structured_json || {};

            const payload = {
                document_ai_run_id: run.id,
                titulo: structured.objeto || structured.titulo || `Propuesta comercial ${run.filename || ''}`,
                cliente: structured.dependencia || structured.cliente || structured.razon_social || '',
                folio: structured.numero_procedimiento || structured.folio || '',
                porcentaje_utilidad: 0,
                porcentaje_descuento: 0,
                porcentaje_impuesto: 16
            };

            const response = await fetch(window.storeProposalUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await safeJson(response);

            if (!response.ok || !data.ok) {
                let msg = data.message || 'No se pudo crear la propuesta comercial.';

                if (data.raw_text) {
                    msg += ' | Respuesta: ' + String(data.raw_text).slice(0, 500);
                }

                throw new Error(msg);
            }

            if (!data.redirect_url) {
                throw new Error('La propuesta se creó, pero el servidor no devolvió la URL de redirección.');
            }

            setProgress(100, 'Completado', '¡Completado!');

            stopTimer();

            setTimeout(() => {
                triggerSuccessState(data.redirect_url);
            }, 900);
        } catch (error) {
            isCreatingProposal = false;
            stopTimer();

            setProgress(100, 'Error', 'No se pudo crear la propuesta.');

            showErrorCard(
                processingStatusCard,
                'No se pudo crear la propuesta',
                error.message || 'El análisis terminó, pero falló la creación de la propuesta comercial.',
                true
            );
        }
    }

    function triggerSuccessState(redirectUrl) {
        hideCard(processingStatusCard);
        hideCard(successStatusCard);

        aiArea.style.opacity = '0';

        setTimeout(() => {
            aiArea.classList.remove('active');
            aiArea.classList.add('hidden');

            successArea.classList.remove('hidden');
            successArea.classList.add('active');

            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 1800);
        }, 400);
    }
</script>
@endsection