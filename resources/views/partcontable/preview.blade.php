@extends('layouts.app')

@section('title', 'Vista previa: ' . $document->title)

@section('content')
@php
    use Illuminate\Support\Str;

    $url     = asset('storage/' . $document->file_path);
    $mime    = $document->mime_type ?? '';
    $mimeMain = Str::before($mime, '/');
    $mimeSub  = Str::after($mime, '/');
    $isPdf    = $mime && str_contains($mime, 'pdf');
@endphp

<style>
:root {
    --pv-page-bg: #e9eef6;
    --pv-surface: #ffffff;
    --pv-border-subtle: #d1d5db;
    --pv-muted: #6b7280;
    --pv-accent: #111827;
    --pv-radius-lg: 18px;
    --pv-radius-pill: 999px;
    --pv-shadow-soft: 0 18px 45px rgba(15, 23, 42, 0.10);
    --pv-shadow-subtle: 0 8px 18px rgba(15, 23, 42, 0.06);
    --pv-font: system-ui, -apple-system, BlinkMacSystemFont, "SF Pro Text",
               "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

/* Página */
.preview-page {
    min-height: calc(100vh - 80px);
    padding: 24px;
    background: radial-gradient(circle at top left, #ffffff 0, #e9eef6 40%, #e9eef6 100%);
    display: flex;
    justify-content: center;
    align-items: flex-start;
    font-family: var(--pv-font);
}

/* Contenedor */
.preview-shell {
    width: 100%;
    max-width: 1080px;
    color: var(--pv-accent);
}

/* Topbar */
.preview-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}

/* Botón volver */
.preview-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: var(--pv-radius-pill);
    border: 1px solid rgba(148, 163, 184, 0.7);
    background: #ffffff;
    color: #374151;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    transition: background .16s ease, transform .12s ease, box-shadow .16s ease,
                border-color .16s ease;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
}
.preview-back:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
    transform: translateY(-1px);
}

/* Título + descripción */
.preview-header-main {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 18px;
}
.preview-title {
    margin: 0;
    font-size: 2rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    color: #111827;
}

/* Chips metadatos */
.preview-meta-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 13px;
    color: var(--pv-muted);
}
.preview-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: var(--pv-radius-pill);
    border: 1px solid rgba(148, 163, 184, 0.7);
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(6px);
}
.preview-chip-dot {
    width: 7px;
    height: 7px;
    border-radius: 999px;
    background: #22c55e;
}
.preview-chip-label {
    font-weight: 600;
    color: #111827;
}
.preview-chip-muted {
    color: #6b7280;
}

/* Descripción */
.preview-description-block { margin-bottom: 18px; }
.preview-description-label {
    font-size: 12px;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #9ca3af;
    margin-bottom: 4px;
}
.preview-description {
    margin: 0;
    padding: 10px 12px;
    border-radius: 12px;
    border: 1px dashed rgba(148, 163, 184, 0.7);
    background: #f9fafb;
    color: #374151;
    font-size: 14px;
    line-height: 1.5;
}

/* Card principal */
.preview-card {
    background: var(--pv-surface);
    border-radius: var(--pv-radius-lg);
    box-shadow: var(--pv-shadow-soft);
    border: 1px solid var(--pv-border-subtle);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    margin-bottom: 18px;
}

/* Header card */
.preview-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid rgba(226, 232, 240, 0.9);
    background: linear-gradient(90deg, #ffffff 0, #edf2ff 40%, #ffffff 100%);
}
.preview-card-title {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.preview-card-title-main {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: #111827;
}
.preview-card-sub {
    font-size: 12px;
    color: #6b7280;
}

/* Pill mini en header */
.preview-card-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}
.pv-icon-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 14px;
    border-radius: var(--pv-radius-pill);
    border: 1px solid rgba(209, 213, 219, 0.9);
    background: #ffffff;
    font-size: 12px;
    color: #111827;
    font-weight: 600;
}

/* Área media */
.preview-media-shell {
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px;
}

/* Frame genérico (imagen / video / otros) */
.preview-media-frame {
    width: 100%;
    max-height: 70vh;
    height: 70vh;
    border-radius: 14px;
    overflow: hidden;
    background: #111827;
    box-shadow: var(--pv-shadow-subtle);
}

/* Frame para PDF: más largo y visualmente más “delgado” (menos margen alrededor) */
.preview-media-frame--pdf {
    max-height: 88vh;
    height: 88vh;
}

/* Contenido dentro del frame */
.preview-media-frame img,
.preview-media-frame video,
.preview-media-frame embed,
.preview-media-frame iframe {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: contain;
    background: #111827;
}

/* Archivo no soportado */
.unsupported-file {
    padding: 40px 24px;
    text-align: center;
    color: #f9fafb;
}
.unsupported-file p {
    margin-bottom: 10px;
    font-size: 14px;
}
.unsupported-file strong {
    color: #fbbf24;
}
.unsupported-file-hint {
    font-size: 13px;
    color: #e5e7eb;
    margin-bottom: 18px;
}

/* Acciones inferiores */
.preview-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 10px;
}

/* Botones píldora */
.pv-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 8px 18px;
    border-radius: var(--pv-radius-pill);
    border: 1px solid transparent;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: background .16s ease, color .16s ease, border-color .16s ease,
                transform .12s ease, box-shadow .16s ease;
}
.pv-btn-primary {
    background: #111827;
    color: #f9fafb;
    border-color: #111827;
    box-shadow: 0 10px 25px rgba(15,23,42,0.28);
}
.pv-btn-primary:hover {
    background: #000000;
    border-color: #000000;
    transform: translateY(-1px);
    box-shadow: 0 16px 35px rgba(15,23,42,0.40);
}
.pv-btn-secondary {
    background: #ffffff;
    color: #111827;
    border-color: #d1d5db;
}
.pv-btn-secondary:hover {
    background: #e5e7eb;
    border-color: #9ca3af;
    transform: translateY(-1px);
}
.pv-btn-ghost {
    background: transparent;
    color: #374151;
    border-color: #d1d5db;
}
.pv-btn-ghost:hover {
    background: #e5e7eb;
    border-color: #9ca3af;
    transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 768px) {
    .preview-page { padding: 16px; }
    .preview-card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    .preview-card-title-main { font-size: 14px; }
    .preview-title { font-size: 1.6rem; }
    .preview-actions { justify-content: flex-start; }
}
</style>

<div class="preview-page">
    <div class="preview-shell">

        {{-- Top bar --}}
        <div class="preview-topbar">
            <a href="{{ url()->previous() }}" class="preview-back">
                <span>←</span>
                <span>Volver</span>
            </a>

            <div class="preview-meta-row">
                @if($mime)
                    <span class="preview-chip">
                        <span class="preview-chip-dot"></span>
                        <span class="preview-chip-label">
                            {{ strtoupper($mimeMain) }}
                        </span>
                        <span class="preview-chip-muted">
                            {{ $mimeSub }}
                        </span>
                    </span>
                @endif

                @if($document->created_at)
                    <span class="preview-chip">
                        <span class="preview-chip-label">Creado</span>
                        <span class="preview-chip-muted">
                            {{ $document->created_at->format('d M Y') }}
                        </span>
                    </span>
                @endif
            </div>
        </div>

        {{-- Título + descripción --}}
        <div class="preview-header-main">
            <h1 class="preview-title">{{ $document->title }}</h1>

            @if($document->description)
                <div class="preview-description-block">
                    <div class="preview-description-label">Descripción</div>
                    <p class="preview-description">{{ $document->description }}</p>
                </div>
            @endif
        </div>

        {{-- Card principal --}}
        <div class="preview-card">
            <div class="preview-card-header">
                <div class="preview-card-title">
                    <p class="preview-card-title-main">
                        Vista previa del archivo
                    </p>
                    <p class="preview-card-sub">
                        {{ $document->mime_type }} · {{ $document->file_path }}
                    </p>
                </div>

                <div class="preview-card-actions">
                    <div class="pv-icon-pill">
                        Descargar
                    </div>
                </div>
            </div>

            <div class="preview-media-shell">
                <div class="preview-media-frame {{ $isPdf ? 'preview-media-frame--pdf' : '' }}">
                    @if($mime && str_contains($mime, 'image'))
                        <img src="{{ $url }}" alt="{{ $document->title }}">

                    @elseif($mime && str_contains($mime, 'video'))
                        <video controls>
                            <source src="{{ $url }}" type="{{ $document->mime_type }}">
                            Tu navegador no soporta este video.
                        </video>

                    @elseif($isPdf)
                        <embed src="{{ $url }}" type="application/pdf" />

                    @else
                        <div class="unsupported-file">
                            <p>Tipo de archivo no soportado en vista previa: <strong>{{ $document->mime_type }}</strong></p>
                            <p class="unsupported-file-hint">
                                Puedes descargarlo o abrirlo directamente en tu dispositivo para verlo con la aplicación correspondiente.
                            </p>
                            <a href="{{ $url }}" target="_blank" class="pv-btn pv-btn-secondary">
                                Abrir en nueva pestaña
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Acciones inferiores --}}
        <div class="preview-actions">
            <a href="{{ route('partcontable.documents.download', $document) }}" class="pv-btn pv-btn-primary">
                Descargar archivo
            </a>

            <a href="{{ $url }}" target="_blank" class="pv-btn pv-btn-secondary">
                Abrir en nueva pestaña
            </a>

            <a href="{{ url()->previous() }}" class="pv-btn pv-btn-ghost">
                Cerrar vista previa
            </a>
        </div>
    </div>
</div>
@endsection
