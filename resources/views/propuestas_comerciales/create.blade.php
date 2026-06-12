@extends('layouts.app')
@section('content_class', 'content--flush')
@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

    :root {
        /* Paleta moderna y corporativa (estilo SaaS) */
        --primary: #2563eb; /* Azul Royal */
        --primary-light: #eff6ff;
        --accent: #6366f1; /* Índigo */
        --success: #10b981; /* Esmeralda */
        --danger: #ef4444;
        --warning: #f59e0b;
        --text-main: #0f172a; /* Slate 900 */
        --text-muted: #64748b; /* Slate 500 */
        --glass-bg: rgba(255, 255, 255, 0.75);
        --glass-border: rgba(255, 255, 255, 0.6);
    }

    @property --btn-angle {
        syntax: '<angle>';
        initial-value: 0deg;
        inherits: false;
    }

    .ai-wrapper {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background: #f1f5f9; /* Fondo gris/azulado muy suave y profesional */
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
        padding: 24px;
    }

    #gridCanvas {
        position: absolute;
        inset: 0;
        z-index: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
    }

    /* ============ Tarjeta ============ */
    .glass-container {
        position: relative;
        z-index: 10;
        width: 100%;
        max-width: 550px;
        background: var(--glass-bg);
        backdrop-filter: blur(24px) saturate(120%);
        -webkit-backdrop-filter: blur(24px) saturate(120%);
        border: 1px solid var(--glass-border);
        border-radius: 28px;
        padding: 48px;
        box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.08), 0 0 0 1px rgba(15, 23, 42, 0.02);
        text-align: center;
        transition: all 0.5s ease;
    }

    .title { font-size: 26px; font-weight: 700; color: var(--text-main); margin-bottom: 12px; letter-spacing: -0.02em; }
    .desc { color: var(--text-muted); font-size: 15px; line-height: 1.6; margin-bottom: 32px; }
    .upload-area { transition: all 0.4s ease; }

    .icon-box {
        width: 80px; height: 80px; background: white; border-radius: 24px;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 24px; box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.1);
        color: var(--primary); animation: floatIcon 3s infinite ease-in-out;
    }

    @keyframes floatIcon { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }

    /* ============ Botón ============ */
    .file-btn {
        position: relative;
        background: var(--primary);
        color: white;
        padding: 16px 40px;
        border-radius: 100px;
        font-weight: 600;
        font-size: 16px;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.3);
        display: inline-block;
        isolation: isolate;
    }

    /* Anillo delgado de gradiente más profesional */
    .file-btn::before {
        content: '';
        position: absolute;
        inset: -2px;
        border-radius: 100px;
        padding: 2px;
        background: conic-gradient(from var(--btn-angle),
            #2563eb, #38bdf8, #6366f1, #2563eb);
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        animation: btnSpin 3s linear infinite;
        pointer-events: none;
    }

    @keyframes btnSpin {
        to { --btn-angle: 360deg; }
    }

    .file-btn:hover { background: #1d4ed8; transform: translateY(-1px); box-shadow: 0 15px 25px -5px rgba(37, 99, 235, 0.4); }
    .file-btn.disabled { opacity: .6; cursor: not-allowed; pointer-events: none; }

    @media (prefers-reduced-motion: reduce) {
        .file-btn::before { animation: none; }
    }

    .ai-processing { display: none; opacity: 0; transition: opacity 0.5s ease; }
    .ai-processing.active { display: block; opacity: 1; }

    .ai-badge {
        display: inline-block; padding: 6px 16px; background: var(--primary-light);
        color: var(--primary); border-radius: 100px; font-size: 13px; font-weight: 700;
        margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.5px;
    }

    .progress-section { margin: 32px 0; }

    .meta-data {
        display: flex; justify-content: space-between; margin-bottom: 12px;
        font-size: 14px; font-weight: 600; color: var(--text-main);
    }

    .bar-track { height: 10px; background: #e2e8f0; border-radius: 100px; overflow: hidden; position: relative; }

    .bar-fill {
        height: 100%; width: 0%; border-radius: 100px; position: relative;
        background: linear-gradient(90deg, var(--primary), var(--accent), var(--primary));
        background-size: 200% 100%; transition: width 0.4s ease;
    }

    .bar-fill::after {
        content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        animation: shimmer 1.5s infinite;
    }

    @keyframes shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }

    .ai-message { font-size: 15px; color: var(--text-main); font-weight: 500; min-height: 24px; transition: opacity 0.3s ease; }

    .timer-pill {
        display: inline-flex; align-items: center; gap: 6px; background: #e2e8f0;
        padding: 6px 16px; border-radius: 100px; color: var(--text-muted); font-size: 13px; margin-top: 24px; font-weight: 500;
    }

    .status-card {
        display: none; margin-top: 26px; border-radius: 16px; padding: 16px; text-align: left;
        border: 1px solid transparent; font-size: 14px; line-height: 1.55;
    }

    .status-card.show { display: block; }
    .status-card.error { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
    .status-card.warning { background: #fffbeb; border-color: #fde68a; color: #92400e; }
    .status-card-title { display: block; font-weight: 700; margin-bottom: 4px; }
    .status-card-message { display: block; word-break: break-word; }

    .retry-btn {
        margin-top: 12px; border: 0; background: #fff; color: var(--primary); border-radius: 10px;
        padding: 8px 14px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0,0,0,.05); transition: background 0.2s;
    }

    .retry-btn:hover { background: #f8fafc; }

    .success-area { display: none; text-align: center; }
    .success-area.active { display: block; animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }

    @keyframes popIn { 0% { opacity: 0; transform: scale(0.9); } 100% { opacity: 1; transform: scale(1); } }

    .check-circle {
        width: 80px; height: 80px; background: var(--success); border-radius: 50%;
        display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;
        box-shadow: 0 15px 30px -5px rgba(16, 185, 129, 0.3);
    }

    .check-circle svg {
        width: 40px; height: 40px; color: white; stroke-dasharray: 100; stroke-dashoffset: 100;
        animation: drawCheck 0.5s ease forwards 0.2s;
    }

    @keyframes drawCheck { to { stroke-dashoffset: 0; } }
    .hidden { display: none !important; }

    @media (max-width: 640px) {
        .glass-container { padding: 32px 20px; border-radius: 24px; }
        .title { font-size: 22px; }
        .file-btn { width: 100%; padding: 15px 20px; }
    }
</style>

<div class="ai-wrapper">
    <canvas id="gridCanvas"></canvas>

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
    /* ============================================================
     * Cuadrícula viva: muy sutil y casi invisible en reposo.
     * ============================================================ */
    (function () {
        const canvas = document.getElementById('gridCanvas');
        const ctx = canvas.getContext('2d');

        const SPACING = 42;          // separación de la cuadrícula
        const RIPPLE_SPEED = 220;    // px por segundo
        const RIPPLE_WIDTH = 90;     // grosor de la onda
        const RIPPLE_LIFE = 4.5;     // segundos de vida de cada onda
        const RIPPLE_EVERY = 2.2;    // segundos entre gotas
        const MAX_DISPLACE = 14;     // cuánto se deforma la cuadrícula
        const HUE_SPEED = 18;        // grados de tono por segundo

        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        let width = 0, height = 0, dpr = 1;
        let cols = 0, rows = 0;
        let ripples = [];
        let lastRipple = 0;
        let startTime = performance.now();

        function resize() {
            dpr = Math.min(window.devicePixelRatio || 1, 2);
            width = canvas.offsetWidth;
            height = canvas.offsetHeight;
            canvas.width = width * dpr;
            canvas.height = height * dpr;
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            cols = Math.ceil(width / SPACING) + 2;
            rows = Math.ceil(height / SPACING) + 2;
        }

        function spawnRipple(x, y, t) {
            ripples.push({ x, y, born: t });
            if (ripples.length > 6) ripples.shift();
        }

        function waveAt(px, py, t) {
            let dx = 0, dy = 0, energy = 0;

            for (const r of ripples) {
                const age = t - r.born;
                if (age < 0 || age > RIPPLE_LIFE) continue;

                const ddx = px - r.x;
                const ddy = py - r.y;
                const dist = Math.sqrt(ddx * ddx + ddy * ddy) || 1;

                const front = age * RIPPLE_SPEED;
                const gap = dist - front;
                const fall = Math.exp(-(gap * gap) / (2 * RIPPLE_WIDTH * RIPPLE_WIDTH));
                const decay = 1 - age / RIPPLE_LIFE;

                const force = fall * decay;
                if (force < 0.01) continue;

                dx += (ddx / dist) * force * MAX_DISPLACE;
                dy += (ddy / dist) * force * MAX_DISPLACE;
                energy += force;
            }

            return { dx, dy, energy: Math.min(energy, 1) };
        }

        function draw(now) {
            const t = (now - startTime) / 1000;
            ctx.clearRect(0, 0, width, height);

            if (t - lastRipple > RIPPLE_EVERY) {
                spawnRipple(
                    width * (0.15 + Math.random() * 0.7),
                    height * (0.15 + Math.random() * 0.7),
                    t
                );
                lastRipple = t;
            }

            const hue = (210 + t * HUE_SPEED) % 360; 

            const cx = width / 2, cy = height / 2;
            const maxR = Math.sqrt(cx * cx + cy * cy);

            ctx.lineWidth = 1;

            /* Líneas casi imperceptibles */
            for (let j = 0; j <= rows; j++) {
                ctx.beginPath();
                for (let i = 0; i <= cols; i++) {
                    const gx = i * SPACING;
                    const gy = j * SPACING;
                    const w = waveAt(gx, gy, t);
                    const x = gx + w.dx;
                    const y = gy + w.dy;
                    if (i === 0) ctx.moveTo(x, y);
                    else ctx.lineTo(x, y);
                }
                ctx.strokeStyle = `rgba(148, 163, 184, 0.04)`; /* Slate muy tenue */
                ctx.stroke();
            }

            for (let i = 0; i <= cols; i++) {
                ctx.beginPath();
                for (let j = 0; j <= rows; j++) {
                    const gx = i * SPACING;
                    const gy = j * SPACING;
                    const w = waveAt(gx, gy, t);
                    const x = gx + w.dx;
                    const y = gy + w.dy;
                    if (j === 0) ctx.moveTo(x, y);
                    else ctx.lineTo(x, y);
                }
                ctx.strokeStyle = `rgba(148, 163, 184, 0.04)`; /* Slate muy tenue */
                ctx.stroke();
            }

            /* Puntos invisibles en reposo, brillan con la onda */
            for (let i = 0; i <= cols; i++) {
                for (let j = 0; j <= rows; j++) {
                    const gx = i * SPACING;
                    const gy = j * SPACING;
                    const w = waveAt(gx, gy, t);

                    // Si no hay energía, no gastamos recursos dibujando el punto
                    if (w.energy < 0.01) continue; 

                    const distC = Math.sqrt((gx - cx) ** 2 + (gy - cy) ** 2);
                    const edgeFade = Math.max(0, 1 - distC / maxR);

                    // La opacidad depende completamente de la energía de la gota
                    const alpha = (w.energy * 0.85) * (0.35 + edgeFade * 0.65);
                    const size = 1.2 + w.energy * 2.2;
                    const dotHue = (hue + w.energy * 60) % 360;

                    ctx.beginPath();
                    ctx.arc(gx + w.dx, gy + w.dy, size, 0, Math.PI * 2);
                    ctx.fillStyle = `hsla(${dotHue}, 85%, 55%, ${alpha})`;
                    ctx.fill();
                }
            }

            requestAnimationFrame(draw);
        }

        function drawStatic() {
            ctx.clearRect(0, 0, width, height);
            ctx.strokeStyle = 'rgba(148, 163, 184, 0.03)'; /* Casi invisible */
            ctx.lineWidth = 1;

            for (let j = 0; j <= rows; j++) {
                ctx.beginPath();
                ctx.moveTo(0, j * SPACING);
                ctx.lineTo(width, j * SPACING);
                ctx.stroke();
            }

            for (let i = 0; i <= cols; i++) {
                ctx.beginPath();
                ctx.moveTo(i * SPACING, 0);
                ctx.lineTo(i * SPACING, height);
                ctx.stroke();
            }
        }

        resize();
        window.addEventListener('resize', () => {
            resize();
            if (reducedMotion) drawStatic();
        });

        if (reducedMotion) {
            drawStatic();
        } else {
            spawnRipple(width / 2, height / 2, 0.4); 
            requestAnimationFrame(draw);
        }
    })();
</script>

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

    function hideCard(card) { card.className = 'status-card'; card.innerHTML = ''; }

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
        if (label) { aiActionStatus.innerText = label; }
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
        if (timer) { clearInterval(timer); }
        timer = setInterval(() => {
            seconds++;
            const m = Math.floor(seconds / 60).toString().padStart(2, '0');
            const s = (seconds % 60).toString().padStart(2, '0');
            elapsedTime.innerText = `${m}:${s}`;
        }, 1000);
    }

    function stopTimer() { if (timer) { clearInterval(timer); timer = null; } }
    function clearPolling() { if (pollingTimer) { clearTimeout(pollingTimer); pollingTimer = null; } }

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
        if (!this.files || !this.files.length) { return; }
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
                if (data.raw_text) { msg += ' | Respuesta: ' + String(data.raw_text).slice(0, 500); }
                throw new Error(msg);
            }

            currentRunId = data.document_ai_run_id;
            setProgress(12, 'En cola', 'Documento recibido. Iniciando análisis...');
            pollRun();
        } catch (error) {
            stopTimer();
            clearPolling();
            setProgress(100, 'Error', 'No se pudo iniciar el proceso.');
            showErrorCard(processingStatusCard, 'No se pudo procesar el PDF',
                error.message || 'Ocurrió un error al enviar el archivo.', true);
        }
    }

    async function pollRun() {
        if (!currentRunId || isCreatingProposal) { return; }

        try {
            const response = await fetch(`${window.documentAiShowDebugBase}/${currentRunId}`, {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });

            const data = await safeJson(response);

            if (!response.ok || !data.ok) {
                let msg = data.message || 'No se pudo consultar el estado del análisis.';
                if (data.raw_text) { msg += ' | Respuesta: ' + String(data.raw_text).slice(0, 500); }
                throw new Error(msg);
            }

            latestRunPayload = data;

            const run = data.run || {};
            const status = run.status || 'queued';

            // ── PROGRESO REAL (escrito por el backend en cada etapa) ──
            if (status === 'queued' || status === 'processing') {
                const prog = run.progress;
                if (prog && typeof prog.pct === 'number') {
                    setProgress(
                        Math.min(prog.pct, 97),
                        prog.etapa || 'Analizando...',
                        prog.detalle || ''
                    );
                } else {
                    setProgress(10, 'En cola', 'Preparando análisis...');
                }
                pollingTimer = setTimeout(pollRun, 1500);
                return;
            }

            if (status === 'completed') {
                setProgress(98, 'Creando propuesta', 'Análisis completado. Creando propuesta comercial...');

                const itemsResult = run.items_json || {};
                const items = Array.isArray(itemsResult.items) ? itemsResult.items : [];

                if (!items.length) {
                    stopTimer();
                    setProgress(100, 'Sin partidas', 'El análisis terminó, pero no se detectaron partidas.');
                    showWarningCard(processingStatusCard, 'No se pudo crear la propuesta',
                        run.error || 'El análisis terminó, pero no se detectaron partidas válidas para cotizar. Revisa que el PDF contenga productos o servicios claros.',
                        true);
                    return;
                }

                await createProposalFromRun(run);
                return;
            }

            if (status === 'failed') {
                stopTimer();
                setProgress(100, 'Falló', 'El análisis falló.');
                showErrorCard(processingStatusCard, 'El análisis falló',
                    run.error || 'No se pudo completar el procesamiento del documento.', true);
                return;
            }

            setProgress(45, 'Procesando...', `Estado actual: ${String(status).toUpperCase()}`);
            pollingTimer = setTimeout(pollRun, 1500);
        } catch (error) {
            stopTimer();
            clearPolling();
            setProgress(100, 'Error', 'No se pudo consultar el análisis.');
            showErrorCard(processingStatusCard, 'Error consultando el análisis',
                error.message || 'Ocurrió un error consultando el estado del documento.', true);
        }
    }

    async function createProposalFromRun(run) {
        if (isCreatingProposal) { return; }
        isCreatingProposal = true;
        clearPolling();

        try {
            setProgress(99, 'Guardando...', 'Creando propuesta comercial...');

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
                if (data.raw_text) { msg += ' | Respuesta: ' + String(data.raw_text).slice(0, 500); }
                throw new Error(msg);
            }

            if (!data.redirect_url) {
                throw new Error('La propuesta se creó, pero el servidor no devolvió la URL de redirección.');
            }

            setProgress(100, 'Completado', '¡Completado!');
            stopTimer();
            setTimeout(() => { triggerSuccessState(data.redirect_url); }, 900);
        } catch (error) {
            isCreatingProposal = false;
            stopTimer();
            setProgress(100, 'Error', 'No se pudo crear la propuesta.');
            showErrorCard(processingStatusCard, 'No se pudo crear la propuesta',
                error.message || 'El análisis terminó, pero falló la creación de la propuesta comercial.', true);
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
            setTimeout(() => { window.location.href = redirectUrl; }, 1800);
        }, 400);
    }
</script>
@endsection