@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

    :root {
        --primary: #007AFF;
        --primary-light: #EBF5FF;
        --accent: #8B5CF6;
        --success: #00a650; /* Verde estilo Mercado Libre */
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
    .orb-blue { background: #60a5fa; top: -10%; left: -10%; }
    .orb-purple { background: #c084fc; bottom: -10%; right: -10%; animation-delay: -5s; }

    @keyframes orbRotate {
        from { transform: translate(0, 0) scale(1); }
        to { transform: translate(50px, 100px) scale(1.2); }
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

    .title { font-size: 26px; font-weight: 700; color: var(--text-main); margin-bottom: 12px; }
    .desc { color: var(--text-muted); font-size: 15px; line-height: 1.6; margin-bottom: 32px; }

    /* --- ESTADO 1: SUBIDA --- */
    .upload-area { transition: all 0.4s ease; }
    .icon-box {
        width: 80px; height: 80px; background: white; border-radius: 24px;
        display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); color: var(--primary);
        animation: floatIcon 3s infinite ease-in-out;
    }
    @keyframes floatIcon { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
    
    .file-btn {
        background: var(--primary); color: white; padding: 16px 40px; border-radius: 100px;
        font-weight: 600; font-size: 16px; border: none; cursor: pointer; transition: all 0.3s;
        box-shadow: 0 10px 20px rgba(0, 122, 255, 0.2); display: inline-block;
    }
    .file-btn:hover { background: #0066d6; transform: scale(1.05); box-shadow: 0 15px 25px rgba(0, 122, 255, 0.3); }

    /* --- ESTADO 2: CARGA IA --- */
    .ai-processing { display: none; opacity: 0; transition: opacity 0.5s ease; }
    .ai-processing.active { display: block; opacity: 1; }

    .ai-badge {
        display: inline-block; padding: 6px 16px; background: var(--primary-light);
        color: var(--primary); border-radius: 100px; font-size: 13px; font-weight: 700;
        margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.5px;
    }
    
    .progress-section { margin: 32px 0; }
    .meta-data { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; font-weight: 700; color: var(--text-main); }
    .bar-track { height: 12px; background: #f1f5f9; border-radius: 100px; overflow: hidden; position: relative; }
    .bar-fill {
        height: 100%; width: 0%; border-radius: 100px; position: relative;
        background: linear-gradient(90deg, #007AFF, #8B5CF6, #007AFF);
        background-size: 200% 100%; transition: width 0.3s ease;
    }
    .bar-fill::after {
        content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        animation: shimmer 1.5s infinite;
    }
    @keyframes shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }

    .ai-message { font-size: 16px; color: var(--text-main); font-weight: 500; height: 24px; transition: opacity 0.3s ease; }
    .timer-pill {
        display: inline-flex; align-items: center; gap: 6px; background: #F3F4F6;
        padding: 6px 16px; border-radius: 100px; color: var(--text-muted); font-size: 13px; margin-top: 24px;
    }

    /* --- ESTADO 3: ÉXITO ESTILO MERCADO LIBRE --- */
    .success-area { display: none; text-align: center; }
    .success-area.active { display: block; animation: popIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
    
    @keyframes popIn {
        0% { opacity: 0; transform: scale(0.8); }
        100% { opacity: 1; transform: scale(1); }
    }

    .check-circle {
        width: 90px; height: 90px; background: var(--success); border-radius: 50%;
        display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;
        box-shadow: 0 15px 30px rgba(0, 166, 80, 0.3);
    }

    .check-circle svg {
        width: 45px; height: 45px; color: white;
        /* Animación de dibujado del path */
        stroke-dasharray: 100; stroke-dashoffset: 100;
        animation: drawCheck 0.6s ease forwards 0.3s;
    }

    @keyframes drawCheck {
        to { stroke-dashoffset: 0; }
    }

    .hidden { display: none !important; }
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
            <p class="desc">Sube tu licitación en PDF. Nuestra IA estructurará partidas, cantidades y especificaciones automáticamente.</p>
            <label class="file-btn">
                <span>Seleccionar Archivo</span>
                <input type="file" id="mainPdfInput" class="hidden" accept="application/pdf">
            </label>
        </div>

        <div id="step-ai" class="ai-processing">
            <div class="ai-badge">IA Activa</div>
            <h1 class="title">Estructurando Datos</h1>
            
            <div class="progress-section">
                <div class="meta-data">
                    <span id="aiActionStatus">Analizando...</span>
                    <span id="percentLabel">0%</span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill" id="mainBarFill"></div>
                </div>
            </div>

            <p class="ai-message" id="dynamicMessage">Iniciando reconocimiento visual...</p>

            <div class="timer-pill">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span id="elapsedTime">00:00</span>
            </div>
        </div>

        <div id="step-success" class="success-area">
            <div class="check-circle">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <h1 class="title" style="color: var(--text-main);">¡Listo!</h1>
            <p class="desc" style="margin-bottom: 0;">Análisis completado. Redirigiendo a los resultados...</p>
        </div>

    </div>
</div>

<script>
    const fileInput = document.getElementById('mainPdfInput');
    const uploadArea = document.getElementById('step-upload');
    const aiArea = document.getElementById('step-ai');
    const successArea = document.getElementById('step-success');
    
    const barFill = document.getElementById('mainBarFill');
    const percentLabel = document.getElementById('percentLabel');
    const dynamicMessage = document.getElementById('dynamicMessage');
    const elapsedTime = document.getElementById('elapsedTime');

    const aiFlow = [
        "Detectando tablas y celdas complejas...",
        "Extrayendo texto mediante OCR...",
        "Identificando entidades y cantidades...",
        "Validando coherencia de las partidas...",
        "Finalizando estructura de datos..."
    ];

    let seconds = 0;
    let timer;

    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            // Transición 1 -> 2
            uploadArea.style.opacity = '0';
            uploadArea.style.transform = 'translateY(-20px)';
            
            setTimeout(() => {
                uploadArea.classList.add('hidden');
                aiArea.classList.add('active');
                startExperience();
            }, 400);
        }
    });

    function startExperience() {
        timer = setInterval(() => {
            seconds++;
            let m = Math.floor(seconds / 60).toString().padStart(2, '0');
            let s = (seconds % 60).toString().padStart(2, '0');
            elapsedTime.innerText = `${m}:${s}`;
        }, 1000);

        let progress = 0;
        let messageIndex = 0;

        // ESTO SIMULA TU CONEXIÓN AL BACKEND
        const loadInterval = setInterval(() => {
            progress += Math.random() * 5; 
            
            // --- CUANDO LLEGA AL 100% ---
            if (progress >= 100) {
                progress = 100;
                clearInterval(loadInterval);
                clearInterval(timer);
                
                barFill.style.width = '100%';
                percentLabel.innerText = '100%';
                dynamicMessage.innerText = "¡Completado!";
                dynamicMessage.style.color = "var(--success)";

                // Darle 0.8 segundos al usuario para ver el 100% antes de cambiar pantalla
                setTimeout(() => {
                    triggerSuccessState();
                }, 800);
            } else {
                barFill.style.width = progress + '%';
                percentLabel.innerText = Math.floor(progress) + '%';
            }

            // Cambiar textos dinámicos
            if (Math.floor(progress / 20) > messageIndex && messageIndex < aiFlow.length - 1) {
                messageIndex++;
                dynamicMessage.style.opacity = '0';
                setTimeout(() => {
                    dynamicMessage.innerText = aiFlow[messageIndex];
                    dynamicMessage.style.opacity = '1';
                }, 300);
            }
        }, 500);
    }

    function triggerSuccessState() {
        // Transición 2 -> 3
        aiArea.style.opacity = '0';
        
        setTimeout(() => {
            aiArea.classList.remove('active');
            aiArea.classList.add('hidden');
            successArea.classList.add('active'); // Aquí se dispara la animación de la palomita

            // Esperar 2.5 segundos para que vean la animación bonita y redirigir
            setTimeout(() => {
                // AQUÍ PONES TU RUTA DE LARAVEL HACIA LA VISTA SHOW
                // Ejemplo: window.location.href = "{{ route('propuestas-comerciales.show', 1) }}";
                
                window.location.href = "/propuestas-comerciales/1"; // <-- CAMBIAR POR TU RUTA REAL
            }, 2500);

        }, 400);
    }
</script>
@endsection