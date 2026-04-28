@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

    :root {
        --primary: #007AFF;
        --primary-light: #EBF5FF;
        --accent: #8B5CF6;
        --text-main: #1F2937;
        --text-muted: #6B7280;
        --glass-bg: rgba(255, 255, 255, 0.75);
        --glass-border: rgba(255, 255, 255, 0.5);
    }

    .ai-wrapper {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background: #f8fafc;
        /* Fondo con gradiente dinámico y suave */
        background: radial-gradient(at 0% 0%, rgba(235, 245, 255, 1) 0, transparent 50%), 
                    radial-gradient(at 100% 100%, rgba(245, 243, 255, 1) 0, transparent 50%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
    }

    /* --- BLOBS DE FONDO ANIMADOS --- */
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

    /* --- TARJETA PREMIUM CLARA --- */
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
        animation: cardEnter 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
    }

    @keyframes cardEnter {
        from { opacity: 0; transform: translateY(30px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* --- ESTADO INICIAL (SUBIDA) --- */
    .upload-area { transition: all 0.5s ease; }
    
    .icon-box {
        width: 80px; height: 80px;
        background: white;
        border-radius: 24px;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 24px;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
        color: var(--primary);
        animation: floatIcon 3s infinite ease-in-out;
    }

    @keyframes floatIcon {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }

    .title { font-size: 26px; font-weight: 700; color: var(--text-main); margin-bottom: 12px; }
    .desc { color: var(--text-muted); font-size: 15px; line-height: 1.6; margin-bottom: 32px; }

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
    }
    .file-btn:hover { background: #0066d6; transform: scale(1.05); box-shadow: 0 15px 25px rgba(0, 122, 255, 0.3); }

    /* --- ESTADO DE CARGA (IA) --- */
    .ai-processing { display: none; }
    .ai-processing.active { display: block; animation: fadeIn 0.5s ease forwards; }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    /* Barra de progreso moderna */
    .progress-section { margin: 32px 0; }
    
    .meta-data {
        display: flex; justify-content: space-between;
        margin-bottom: 12px; font-size: 14px; font-weight: 700; color: var(--text-main);
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
        background: linear-gradient(90deg, #007AFF, #8B5CF6, #007AFF);
        background-size: 200% 100%;
        border-radius: 100px;
        transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    /* Brillo moviéndose en la barra */
    .bar-fill::after {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        animation: shimmer 1.5s infinite;
    }

    @keyframes shimmer { 
        0% { transform: translateX(-100%); } 
        100% { transform: translateX(100%); } 
    }

    /* Texto de acciones de IA */
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

    .ai-message {
        font-size: 16px;
        color: var(--text-main);
        font-weight: 500;
        height: 24px;
    }

    .timer-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #F3F4F6;
        padding: 4px 12px;
        border-radius: 100px;
        color: var(--text-muted);
        font-size: 12px;
        margin-top: 24px;
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
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span id="elapsedTime">00:00</span>
            </div>
        </div>

    </div>
</div>

<script>
    const fileInput = document.getElementById('mainPdfInput');
    const uploadArea = document.getElementById('step-upload');
    const aiArea = document.getElementById('step-ai');
    
    const barFill = document.getElementById('mainBarFill');
    const percentLabel = document.getElementById('percentLabel');
    const dynamicMessage = document.getElementById('dynamicMessage');
    const elapsedTime = document.getElementById('elapsedTime');

    // Mensajes de IA "Conocedores"
    const aiFlow = [
        "Escaneando capas del documento...",
        "Detectando tablas y celdas complejas...",
        "Extrayendo texto mediante OCR Azure...",
        "Identificando entidades: Cantidad y Unidad...",
        "Cruzando información con el glosario...",
        "Validando coherencia de las partidas...",
        "Finalizando estructura de datos..."
    ];

    let seconds = 0;
    let timer;

    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            // Transición suave
            uploadArea.style.opacity = '0';
            uploadArea.style.transform = 'translateY(-20px)';
            
            setTimeout(() => {
                uploadArea.classList.add('hidden');
                aiArea.classList.add('active');
                startExperience();
            }, 500);
        }
    });

    function startExperience() {
        // Iniciar cronómetro
        timer = setInterval(() => {
            seconds++;
            let m = Math.floor(seconds / 60).toString().padStart(2, '0');
            let s = (seconds % 60).toString().padStart(2, '0');
            elapsedTime.innerText = `${m}:${s}`;
        }, 1000);

        // Simulación de carga (Aquí conectarías con tu polling real)
        let progress = 0;
        let messageIndex = 0;

        const loadInterval = setInterval(() => {
            progress += Math.random() * 4; // Carga irregular para parecer real
            
            if (progress >= 100) {
                progress = 100;
                clearInterval(loadInterval);
                clearInterval(timer);
                dynamicMessage.innerText = "¡Análisis finalizado!";
                dynamicMessage.style.color = "#10B981";
                
                setTimeout(() => {
                    alert("Carga completa. Redirigiendo a resultados...");
                }, 1000);
            }

            barFill.style.width = progress + '%';
            percentLabel.innerText = Math.floor(progress) + '%';

            // Cambiar mensaje cada 15% de progreso aprox
            if (Math.floor(progress / 15) > messageIndex && messageIndex < aiFlow.length - 1) {
                messageIndex++;
                dynamicMessage.style.opacity = '0';
                setTimeout(() => {
                    dynamicMessage.innerText = aiFlow[messageIndex];
                    dynamicMessage.style.opacity = '1';
                }, 300);
            }
        }, 600);
    }
</script>
@endsection