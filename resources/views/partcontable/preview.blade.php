@extends('layouts.app')

@section('title', 'Previsualización | ' . $document->title)

@php
    use Illuminate\Support\Str;

    $url = asset('storage/' . $document->file_path);
    $mime = $document->mime_type ?? 'application/octet-stream';
    
    $isImage = Str::startsWith($mime, 'image/');
    $isVideo = Str::startsWith($mime, 'video/');
    $isPdf   = $mime === 'application/pdf';
    
    $fileName = basename($document->file_path);
    $fileSize = $document->file_size ? number_format($document->file_size / 1024, 2) . ' KB' : 'Desconocido';
@endphp

@section('content')
<style>
    :root {
        --ui-bg: #f8fafc;
        --ui-surface: #ffffff;
        --ui-border: #e2e8f0;
        --ui-text-main: #0f172a;
        --ui-text-muted: #64748b;
        --ui-accent: #4f46e5; /* Indigo premium */
        --ui-accent-hover: #4338ca;
        --ui-danger: #ef4444;
        --radius-md: 12px;
        --radius-lg: 16px;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    /* Contenedor Principal */
    .doc-viewer-layout {
        display: flex;
        height: calc(100vh - 64px); /* Ajusta según tu navbar principal */
        min-height: 600px;
        background-color: var(--ui-bg);
        font-family: 'Inter', system-ui, sans-serif;
        color: var(--ui-text-main);
    }

    /* Panel Lateral (Información y Acciones) */
    .doc-sidebar {
        width: 320px;
        background-color: var(--ui-surface);
        border-right: 1px solid var(--ui-border);
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        z-index: 10;
        box-shadow: var(--shadow-sm);
    }

    .sidebar-header {
        padding: 24px;
        border-bottom: 1px solid var(--ui-border);
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--ui-text-muted);
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        margin-bottom: 24px;
        transition: color 0.2s;
    }
    .btn-back:hover { color: var(--ui-text-main); }

    .doc-title {
        font-size: 20px;
        font-weight: 700;
        line-height: 1.3;
        margin: 0 0 8px 0;
        color: var(--ui-text-main);
    }

    .doc-badge {
        display: inline-block;
        padding: 4px 10px;
        background: #e0e7ff;
        color: var(--ui-accent);
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .sidebar-body {
        padding: 24px;
        flex-grow: 1;
        overflow-y: auto;
    }

    .meta-group {
        margin-bottom: 20px;
    }
    .meta-label {
        font-size: 12px;
        text-transform: uppercase;
        color: var(--ui-text-muted);
        font-weight: 600;
        margin-bottom: 6px;
        letter-spacing: 0.5px;
    }
    .meta-value {
        font-size: 14px;
        font-weight: 500;
        color: var(--ui-text-main);
        word-break: break-all;
    }

    .doc-description {
        font-size: 14px;
        line-height: 1.6;
        color: var(--ui-text-muted);
        background: var(--ui-bg);
        padding: 16px;
        border-radius: var(--radius-md);
        border: 1px solid var(--ui-border);
    }

    .sidebar-footer {
        padding: 24px;
        border-top: 1px solid var(--ui-border);
        background: #f8fafc;
    }

    /* Botones Profesionales */
    .btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
        cursor: pointer;
        border: 1px solid transparent;
    }
    .btn-primary {
        background-color: var(--ui-accent);
        color: white;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
    }
    .btn-primary:hover {
        background-color: var(--ui-accent-hover);
        transform: translateY(-1px);
    }
    .btn-secondary {
        background-color: var(--ui-surface);
        color: var(--ui-text-main);
        border-color: var(--ui-border);
        margin-top: 12px;
    }
    .btn-secondary:hover {
        background-color: var(--ui-bg);
        border-color: #cbd5e1;
    }

    /* Área del Visor (Derecha) */
    .doc-stage {
        flex-grow: 1;
        padding: 32px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .stage-toolbar {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 16px;
    }

    .viewer-frame {
        flex-grow: 1;
        background: var(--ui-surface);
        border-radius: var(--radius-lg);
        border: 1px solid var(--ui-border);
        box-shadow: var(--shadow-md);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .viewer-content {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: #f1f5f9; /* Fondo suave para resaltar el documento */
    }

    .unsupported-notice {
        text-align: center;
        max-width: 400px;
        padding: 40px;
    }
    .unsupported-icon {
        width: 64px;
        height: 64px;
        color: #cbd5e1;
        margin: 0 auto 16px auto;
    }

    /* Diseño Responsivo */
    @media (max-width: 1024px) {
        .doc-viewer-layout { flex-direction: column; height: auto; display: block; }
        .doc-sidebar { width: 100%; border-right: none; border-bottom: 1px solid var(--ui-border); }
        .doc-stage { height: 80vh; padding: 16px; }
    }
</style>

<div class="doc-viewer-layout">
    
    {{-- Panel Lateral --}}
    <aside class="doc-sidebar">
        <div class="sidebar-header">
            <a href="{{ url()->previous() }}" class="btn-back">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver al listado
            </a>
            <h1 class="doc-title">{{ $document->title }}</h1>
            <span class="doc-badge">{{ strtoupper(explode('/', $mime)[1] ?? 'FILE') }}</span>
        </div>

        <div class="sidebar-body">
            @if($document->description)
                <div class="meta-group">
                    <div class="meta-label">Descripción</div>
                    <div class="doc-description">{{ $document->description }}</div>
                </div>
            @endif

            <div class="meta-group">
                <div class="meta-label">Nombre del archivo</div>
                <div class="meta-value">{{ $fileName }}</div>
            </div>

            <div class="meta-group">
                <div class="meta-label">Fecha de subida</div>
                <div class="meta-value">{{ $document->created_at->format('d/m/Y h:i A') }}</div>
            </div>

            <div class="meta-group">
                <div class="meta-label">Tipo MIME</div>
                <div class="meta-value" style="font-family: monospace; font-size: 13px;">{{ $mime }}</div>
            </div>
        </div>

        <div class="sidebar-footer">
            <a href="{{ route('partcontable.documents.download', $document) }}" class="btn btn-primary">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Descargar Documento
            </a>
            <a href="{{ $url }}" target="_blank" class="btn btn-secondary">
                Abrir en nueva ventana
            </a>
        </div>
    </aside>

    {{-- Área Principal del Visor --}}
    <main class="doc-stage">
        <div class="stage-toolbar">
            {{-- Espacio para futuros controles: Zoom, Compartir, etc. --}}
            <span style="font-size: 13px; color: var(--ui-text-muted); font-weight: 500;">
                Vista Previa del Sistema
            </span>
        </div>

        <div class="viewer-frame">
            @if($isImage)
                <img src="{{ $url }}" alt="{{ $document->title }}" class="viewer-content">
            @elseif($isVideo)
                <video controls class="viewer-content" style="background: #0f172a;">
                    <source src="{{ $url }}" type="{{ $mime }}">
                </video>
            @elseif($isPdf)
                <iframe src="{{ $url }}#toolbar=0" class="viewer-content" title="Visor PDF" style="border: none;"></iframe>
            @else
                <div class="unsupported-notice">
                    <svg class="unsupported-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                    <h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 600;">No hay vista previa disponible</h3>
                    <p style="margin: 0; color: var(--ui-text-muted); font-size: 14px; line-height: 1.5;">
                        El formato <strong>{{ $mime }}</strong> requiere ser descargado para poder visualizarse correctamente en tu equipo.
                    </p>
                </div>
            @endif
        </div>
    </main>

</div>
@endsection