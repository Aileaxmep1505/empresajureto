@extends('layouts.app')

@section('title', 'Subir archivo de licitación (AI)')

@push('styles')
<style>
  body {
      background: #f3f4f6;
  }

  .ai-page {
      max-width: 960px;
      margin: 24px auto 32px;
      padding: 0 16px;
  }

  .ai-card {
      background: #ffffff;
      border-radius: 24px;
      padding: 24px 24px 28px;
      box-shadow:
          0 18px 40px rgba(15, 23, 42, 0.08),
          0 0 0 1px rgba(148, 163, 184, 0.15);
  }

  .ai-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      margin-bottom: 24px;
  }

  .ai-title {
      font-size: 1.4rem;
      font-weight: 600;
      color: #111827;
  }

  .ai-subtitle {
      font-size: 0.9rem;
      color: #6b7280;
      max-width: 520px;
  }

  .ai-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 500;
      background: #eef2ff;
      color: #3730a3;
  }

  .ai-badge span.dot {
      width: 8px;
      height: 8px;
      border-radius: 999px;
      background: #4f46e5;
  }

  .ai-form {
      margin-top: 8px;
  }

  .ai-form-group {
      margin-bottom: 18px;
  }

  .ai-label {
      display: block;
      font-size: 0.82rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: #6b7280;
      margin-bottom: 6px;
  }

  .ai-input-file {
      border-radius: 16px;
      border: 1px dashed #cbd5f5;
      background: #f9fafb;
      padding: 16px;
      display: flex;
      align-items: center;
      gap: 12px;
      cursor: pointer;
      transition: all 0.18s ease;
  }

  .ai-input-file:hover {
      border-color: #4f46e5;
      background: #f5f3ff;
      box-shadow: 0 0 0 1px rgba(79, 70, 229, 0.14);
  }

  .ai-input-file-icon {
      width: 40px;
      height: 40px;
      border-radius: 999px;
      background: #eef2ff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      color: #4f46e5;
  }

  .ai-input-file-text-main {
      font-size: 0.95rem;
      font-weight: 500;
      color: #111827;
  }

  .ai-input-file-text-sub {
      font-size: 0.8rem;
      color: #6b7280;
  }

  .ai-input-file input[type="file"] {
      display: none;
  }

  .ai-footer {
      margin-top: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 10px;
  }

  .ai-hint {
      font-size: 0.8rem;
      color: #9ca3af;
  }

  .ai-btn-primary {
      border: none;
      border-radius: 999px;
      padding: 10px 22px;
      font-size: 0.9rem;
      font-weight: 600;
      letter-spacing: 0.03em;
      text-transform: uppercase;
      background: linear-gradient(135deg, #4f46e5, #6366f1);
      color: #ffffff;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      box-shadow:
          0 14px 30px rgba(79, 70, 229, 0.3),
          0 0 0 1px rgba(129, 140, 248, 0.4);
      transition: all 0.18s ease;
  }

  .ai-btn-primary:hover {
      transform: translateY(-1px);
      box-shadow:
          0 18px 40px rgba(79, 70, 229, 0.38),
          0 0 0 1px rgba(129, 140, 248, 0.7);
  }

  .ai-btn-primary:disabled {
      opacity: 0.6;
      cursor: default;
      box-shadow: 0 0 0 1px rgba(148, 163, 184, 0.25);
      transform: none;
  }

  .ai-btn-primary span.icon {
      font-size: 1rem;
  }

  /* ====== Overlay del loader ====== */
  .ai-loader-overlay {
      position: fixed;
      inset: 0;
      background: rgba(255, 255, 255, 0.94);
      backdrop-filter: blur(4px);
      -webkit-backdrop-filter: blur(4px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
  }

  .ai-loader-overlay.visible {
      display: flex;
  }

  .ai-loader-box {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
  }

  .ai-loader-text {
      margin-top: 18px;
      font-size: 0.9rem;
      color: #4b5563;
      min-height: 1.2em;
  }

  /* ====== Loader con degradado IA ====== */
  .loader-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 180px;
    height: 180px;
    font-family: "Inter", sans-serif;
    font-size: 1.2em;
    font-weight: 300;
    color: #0f172a;
    border-radius: 50%;
    background-color: transparent;
    user-select: none;
  }

  .loader {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    aspect-ratio: 1 / 1;
    border-radius: 50%;
    background-color: transparent;
    animation: loader-rotate 2s linear infinite;
    z-index: 0;
  }

  @keyframes loader-rotate {
    0% {
      transform: rotate(90deg);
      box-shadow:
        0 10px 20px 0 #e0f2fe inset,   /* azul muy claro */
        0 20px 30px 0 #6366f1 inset,   /* índigo */
        0 60px 60px 0 #7c3aed inset;   /* púrpura */
    }
    50% {
      transform: rotate(270deg);
      box-shadow:
        0 10px 20px 0 #fdf2ff inset,   /* lila súper claro */
        0 20px 10px 0 #22d3ee inset,   /* cyan */
        0 40px 60px 0 #4f46e5 inset;   /* índigo intenso */
    }
    100% {
      transform: rotate(450deg);
      box-shadow:
        0 10px 20px 0 #e0f2fe inset,
        0 20px 30px 0 #6366f1 inset,
        0 60px 60px 0 #7c3aed inset;
    }
  }

  .loader-letter {
    display: inline-block;
    opacity: 0.6;
    transform: translateY(0);
    animation: loader-letter-anim 2s infinite;
    z-index: 1;
    border-radius: 50ch;
    border: none;
    padding: 0 1px;
  }

  .loader-letter:nth-child(1)  { animation-delay: 0s; }
  .loader-letter:nth-child(2)  { animation-delay: 0.1s; }
  .loader-letter:nth-child(3)  { animation-delay: 0.2s; }
  .loader-letter:nth-child(4)  { animation-delay: 0.3s; }
  .loader-letter:nth-child(5)  { animation-delay: 0.4s; }
  .loader-letter:nth-child(6)  { animation-delay: 0.5s; }
  .loader-letter:nth-child(7)  { animation-delay: 0.6s; }
  .loader-letter:nth-child(8)  { animation-delay: 0.7s; }
  .loader-letter:nth-child(9)  { animation-delay: 0.8s; }

  @keyframes loader-letter-anim {
    0%,
    100% {
      opacity: 0.5;
      transform: translateY(0);
    }
    20% {
      opacity: 1;
      transform: scale(1.15);
    }
    40% {
      opacity: 0.7;
      transform: translateY(0);
    }
  }
</style>
@endpush

@section('content')
<div class="ai-page">
    <div class="ai-card">
        <div class="ai-header">
            <div>
                <h1 class="ai-title">Subir archivo de licitación con IA</h1>
                <p class="ai-subtitle">
                    Sube un archivo en PDF o Word con las bases / anexos de la licitación.
                    El sistema aplicará modelos de IA para detectar tablas, extraer ítems,
                    unificar requisiciones y construir una tabla global lista para trabajar.
                </p>
            </div>
            <div class="ai-badge">
                <span class="dot"></span>
                Módulo AI · Beta
            </div>
        </div>

        <form id="form-licitacion-ai" class="ai-form" method="POST"
              action="{{ route('licitaciones-ai.store') }}"
              enctype="multipart/form-data">
            @csrf

            <div class="ai-form-group">
                <label class="ai-label">Archivo de licitación</label>

                <label class="ai-input-file">
                    <div class="ai-input-file-icon">
                        📄
                    </div>
                    <div>
                        <div class="ai-input-file-text-main">
                            Selecciona un archivo PDF, DOC o DOCX
                        </div>
                        <div class="ai-input-file-text-sub" id="ai-file-name">
                            Ningún archivo seleccionado
                        </div>
                    </div>
                    <input type="file" name="file" id="file-input" required>
                </label>

                @error('file')
                    <p style="color:#b91c1c; font-size:0.8rem; margin-top:6px;">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="ai-footer">
                <p class="ai-hint">
                    El procesamiento usa modelos de IA y puede tardar varios minutos
                    según el tamaño del documento y la cantidad de tablas.
                </p>

                <button type="submit" class="ai-btn-primary" id="btn-submit-ai">
                    <span class="icon">⚙️</span>
                    <span>Procesar con IA</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Overlay de carga centrado con el loader IA --}}
<div class="ai-loader-overlay" id="ai-loader-overlay">
    <div class="ai-loader-box">
        <div class="loader-wrapper">
            {{-- Texto del círculo: “Generando” --}}
            <span class="loader-letter">G</span>
            <span class="loader-letter">e</span>
            <span class="loader-letter">n</span>
            <span class="loader-letter">e</span>
            <span class="loader-letter">r</span>
            <span class="loader-letter">a</span>
            <span class="loader-letter">n</span>
            <span class="loader-letter">d</span>
            <span class="loader-letter">o</span>

            <div class="loader"></div>
        </div>
        <div class="ai-loader-text" id="ai-loader-text">
            Analizando el documento con redes neuronales...
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form          = document.getElementById('form-licitacion-ai');
    const overlay       = document.getElementById('ai-loader-overlay');
    const btn           = document.getElementById('btn-submit-ai');
    const fileInput     = document.getElementById('file-input');
    const fileNameLabel = document.getElementById('ai-file-name');
    const loaderText    = document.getElementById('ai-loader-text');

    // Frases "más padres" de lo que realmente hace la IA
    const frases = [
        'Analizando el documento con redes neuronales...',
        'Detectando tablas y patrones de licitación...',
        'Extrayendo ítems y columnas clave automáticamente...',
        'Normalizando cantidades, unidades y descripciones...',
        'Agrupando productos similares por similitud semántica...',
        'Generando una tabla global optimizada para tu catálogo...',
        'Verificando consistencia de datos entre requisiciones...',
        'Preparando la información para exportar a Excel y PDF...'
    ];
    let fraseIndex = 0;

    // Cambia la frase cada 2.5s si el overlay está visible
    setInterval(function () {
        if (!overlay.classList.contains('visible')) return;
        fraseIndex = (fraseIndex + 1) % frases.length;
        loaderText.textContent = frases[fraseIndex];
    }, 2500);

    // Mostrar nombre del archivo seleccionado
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            if (fileInput.files && fileInput.files.length > 0) {
                fileNameLabel.textContent = fileInput.files[0].name;
            } else {
                fileNameLabel.textContent = 'Ningún archivo seleccionado';
            }
        });
    }

    // Mostrar overlay al enviar el formulario
    if (form && overlay && btn) {
        form.addEventListener('submit', function () {
            overlay.classList.add('visible');
            btn.disabled = true;
        });
    }
});
</script>
@endpush
