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
    
    // Asumiendo que tu modelo Document tiene la relación 'user'
    $uploaderName = $document->user->name ?? 'Usuario del sistema'; 
@endphp

@section('content')
<style>
    :root {
        /* Color "Café Crema" inspirado en tu referencia */
        --ui-bg-coffee: #F4F1EA; 
        --ui-surface: #FFFFFF;
        --ui-border: #E5E0D8;
        --ui-text-main: #1C1917; /* Un gris casi negro más elegante */
        --ui-text-muted: #78716C;
        --ui-accent: #111827; /* Negro/Gris oscuro para botones premium */
        --radius-lg: 24px;
        --radius-md: 12px;
        --shadow-soft: 0 10px 40px -10px rgba(0,0,0,0.08);
    }

    /* * TRUCO BREAKOUT: 
     * Esto fuerza al contenedor a ignorar los paddings/containers de layouts.app 
     * y ocupar el 100% del ancho de la pantalla (100vw).
     */
    .doc-viewer-breakout {
        width: 100vw;
        position: relative;
        left: 50%;
        right: 50%;
        margin-left: -50vw;
        margin-right: -50vw;
        /* Sobrescribir padding por si el contenedor padre tiene */
        padding: 0 !important; 
    }

    /* Contenedor Principal */
    .doc-viewer-layout {
        display: flex;
        height: calc(100vh - 64px); /* Ajusta este 64px al alto de tu navbar principal */
        min-height: 100vh;
        background-color: var(--ui-bg-coffee);
        font-family: 'Inter', system-ui, sans-serif;
        color: var(--ui-text-main);
    }

    /* Panel Lateral (Información y Acciones) */
    .doc-sidebar {
        width: 340px;
        background-color: var(--ui-surface);
        border-right: 1px solid var(--ui-border);
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        z-index: 10;
    }

    .sidebar-header {
        padding: 32px 24px 24px;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--ui-text-muted);
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        margin-bottom: 32px;
        transition: color 0.2s;
    }
    .btn-back:hover { color: var(--ui-text-main); }

    .doc-title {
        font-size: 24px;
        font-weight: 700;
        line-height: 1.2;
        margin: 0 0 12px 0;
        letter-spacing: -0.02em;
    }

    .doc-badge {
        display: inline-block;
        padding: 6px 12px;
        background: #F3F4F6;
        color: var(--ui-text-main);
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .sidebar-body {
        padding: 0 24px 24px;
        flex-grow: 1;
        overflow-y: auto;
    }

    .meta-card {
        background: #FAFAFA;
        border: 1px solid var(--ui-border);
        border-radius: var(--radius-md);
        padding: 16px;
        margin-bottom: 24px;
    }

    .meta-group {
        margin-bottom: 16px;
    }
    .meta-group:last-child {
        margin-bottom: 0;
    }
    .meta-label {
        font-size: 11px;
        text-transform: uppercase;
        color: var(--ui-text-muted);
        font-weight: 600;
        margin-bottom: 4px;
        letter-spacing: 0.5px;
    }
    .meta-value {
        font-size: 14px;
        font-weight: 500;
        color: var(--ui-text-main);
        word-break: break-all;
    }

    .sidebar-footer {
        padding: 24px;
        background: var(--ui-surface);
        border-top: 1px solid var(--ui-border);
    }

    /* Botones Profesionales */
    .btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 14px 16px;
        border-radius: 12px;
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
    }
    .btn-primary:hover {
        background-color: #000000;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .btn-secondary {
        background-color: var(--ui-surface);
        color: var(--ui-text-main);
        border-color: #D6D3D1;
        margin-top: 12px;
    }
    .btn-secondary:hover {
        background-color: #F5F5F4;
    }

    /* Área del Visor (Derecha con fondo café) */
    .doc-stage {
        flex-grow: 1;
        padding: 40px;
        display: flex;
        flex-direction: column;
        align-items: center;
        overflow: hidden;
    }

    .viewer-frame {
        width: 100%;
        max-width: 1100px; /* Limita lo ancho para que no se vea desproporcionado en monitores gigantes */
        height: 100%;
        background: #FFFFFF; /* Marco blanco para el documento */
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-soft);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .viewer-content {
        width: 100%;
        height: 100%;
        border: none;
        background: #1C1917; /* Fondo oscuro detrás del PDF/Video se ve más pro */
    }

    /* Diseño Responsivo */
    @media (max-width: 1024px) {
        .doc-viewer-layout { flex-direction: column; height: auto; display: block; }
        .doc-sidebar { width: 100%; border-right: none; border-bottom: 1px solid var(--ui-border); }
        .doc-stage { height: 85vh; padding: 20px; }
    }
</style>

{{-- APLICAMOS EL CONTENEDOR BREAKOUT AQUÍ --}}
<div class="doc-viewer-breakout">
    <div class="doc-viewer-layout">
        
        {{-- Panel Lateral --}}
        <aside class="doc-sidebar">
            <div class="sidebar-header">
                <a href="{{ url()->previous() }}" class="btn-back">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Volver al listado
                </a>
                <h1 class="doc-title">{{ $document->title }}</h1>
                <span class="doc-badge">{{ strtoupper(explode('/', $mime)[1] ?? 'FILE') }}</span>
            </div>

            <div class="sidebar-body">
                <div class="meta-card">
                    {{-- AQUÍ VA EL NOMBRE DEL USUARIO --}}
                    <div class="meta-group">
                        <div class="meta-label">Subido por</div>
                        <div class="meta-value" style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 24px; height: 24px; background: #E5E0D8; border-radius: 50%; display: grid; place-items: center; font-size: 10px; font-weight: bold;">
                                {{ substr($uploaderName, 0, 1) }}
                            </div>
                            {{ $uploaderName }}
                        </div>
                    </div>

                    <div class="meta-group">
                        <div class="meta-label">Fecha de subida</div>
                        <div class="meta-value">{{ $document->created_at->format('d/m/Y h:i A') }}</div>
                    </div>
                </div>

                <div class="meta-group">
                    <div class="meta-label">Nombre original</div>
                    <div class="meta-value" style="font-size: 13px;">{{ $fileName }}</div>
                </div>

                @if($document->description)
                    <div class="meta-group" style="margin-top: 24px;">
                        <div class="meta-label">Descripción</div>
                        <p style="font-size: 13px; line-height: 1.6; color: var(--ui-text-muted); margin: 0;">
                            {{ $document->description }}
                        </p>
                    </div>
                @endif
            </div>

            <div class="sidebar-footer">
                <a href="{{ route('partcontable.documents.download', $document) }}" class="btn btn-primary">
                    Descargar Documento
                </a>
                <a href="{{ $url }}" target="_blank" class="btn btn-secondary">
                    Abrir en nueva ventana
                </a>
            </div>
        </aside>

        {{-- Área Principal del Visor --}}
        <main class="doc-stage">
            <div class="viewer-frame">
                @if($isImage)
                    <img src="{{ $url }}" alt="{{ $document->title }}" class="viewer-content" style="object-fit: contain; background: #ffffff;">
                @elseif($isVideo)
                    <video controls class="viewer-content">
                        <source src="{{ $url }}" type="{{ $mime }}">
                    </video>
                @elseif($isPdf)
                    <iframe src="{{ $url }}#toolbar=0" class="viewer-content" title="Visor PDF"></iframe>
                @else
                    <div style="text-align: center; color: var(--ui-text-muted);">
                        <svg style="width: 48px; height: 48px; margin: 0 auto 16px auto; opacity: 0.5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                        <p>No hay vista previa disponible.</p>
                    </div>
                @endif
            </div>
        </main>

    </div>
</div>
@endsection