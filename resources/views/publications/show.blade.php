@extends('layouts.app')

@section('title', $publication->title)

@section('content')
<div class="container py-5" id="pub-show">
  <style>
    #pub-show{
      --ink: #0f172a;
      --muted: #64748b;
      --line: #e2e8f0;
      --card: #ffffff;
      --bg-page: #f8fafc;
      --brand: #3b82f6; /* Azul consistente */
      --danger: #ef4444;
      --radius: 16px;
      --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    /* Layout Principal */
    #pub-show .wrap{
      display: grid;
      grid-template-columns: 2fr 1fr; /* 2/3 para vista previa, 1/3 para detalles */
      gap: 24px;
      align-items: start;
    }
    @media (max-width: 992px){ #pub-show .wrap{ grid-template-columns: 1fr; } }

    /* Paneles (Tarjetas) */
    #pub-show .panel{
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    /* Área de Previsualización */
    #pub-show .media-container {
      background: #f1f5f9;
      min-height: 400px;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }
    #pub-show .media-container img, 
    #pub-show .media-container video, 
    #pub-show .media-container iframe {
      width: 100%;
      height: 100%;
      min-height: 500px; /* Altura fija para PDFs e Iframe */
      object-fit: contain;
      border: 0;
      display: block;
    }
    #pub-show .no-preview {
      text-align: center;
      padding: 40px;
      color: var(--muted);
    }
    #pub-show .file-icon-lg {
        width: 80px; height: 80px; margin-bottom: 15px; color: var(--muted);
    }

    /* Área de Información (Sidebar) */
    #pub-show .info-header {
      padding: 24px;
      border-bottom: 1px solid var(--line);
    }
    #pub-show h1 {
      margin: 0 0 8px 0;
      font-size: 20px;
      font-weight: 700;
      color: var(--ink);
      line-height: 1.4;
    }
    #pub-show .kind-badge {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 4px 10px; border-radius: 6px;
      background: #eff6ff; color: var(--brand);
      font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
    }

    #pub-show .info-body {
      padding: 24px;
    }
    #pub-show .label {
      font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--muted); letter-spacing: 0.5px; margin-bottom: 6px;
    }
    #pub-show .value {
      font-size: 14px; color: var(--ink); margin-bottom: 20px; line-height: 1.5;
    }
    #pub-show .desc-box {
      background: #f8fafc; border-radius: 8px; padding: 15px; color: #475569; font-size: 14px;
    }

    /* Botones de Acción */
    #pub-show .actions {
      padding: 20px 24px;
      background: #f8fafc;
      border-top: 1px solid var(--line);
      display: flex; flex-direction: column; gap: 10px;
    }

    /* Estilos de Botones */
    .btn-action {
      display: flex; align-items: center; justify-content: center; gap: 8px;
      width: 100%; padding: 12px; border-radius: 10px;
      font-size: 14px; font-weight: 600; text-decoration: none;
      transition: all 0.2s ease; border: 1px solid transparent; cursor: pointer;
    }
    .btn-primary {
      background: var(--brand); color: white;
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
    }
    .btn-primary:hover { background: #2563eb; transform: translateY(-1px); }
    
    .btn-outline {
      background: white; border-color: var(--line); color: var(--ink);
    }
    .btn-outline:hover { background: #f1f5f9; border-color: #cbd5e1; }

    .btn-danger-ghost {
      background: transparent; color: var(--danger);
    }
    .btn-danger-ghost:hover { background: #fef2f2; }

    /* Iconos SVG */
    .icon { width: 18px; height: 18px; stroke-width: 2; }
  </style>

  {{-- Botón Volver Flotante --}}
  <div style="margin-bottom: 20px;">
    <a href="{{ route('publications.index') }}" style="display:inline-flex; align-items:center; gap:6px; color:var(--muted); text-decoration:none; font-size:14px; font-weight:500; transition:color .2s;">
      <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"></path></svg>
      Volver al listado
    </a>
  </div>

  <div class="wrap">
    
    {{-- COLUMNA IZQUIERDA: VISUALIZADOR --}}
    <div class="panel">
      <div class="media-container">
        @if($publication->is_image)
          <img src="{{ $publication->url }}" alt="{{ $publication->title }}">
        @elseif($publication->is_video)
          <video controls>
            <source src="{{ $publication->url }}" type="{{ $publication->mime_type }}">
            Tu navegador no soporta la reproducción de video.
          </video>
        @elseif($publication->kind === 'pdf' || $publication->extension === 'pdf')
          {{-- Iframe para PDF --}}
          <iframe src="{{ $publication->url }}"></iframe>
        @else
          {{-- Fallback para archivos sin preview --}}
          <div class="no-preview">
            <svg class="file-icon-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <h3 style="margin:0; font-size:18px; color:var(--ink);">Vista previa no disponible</h3>
            <p style="margin:8px 0 0; font-size:14px;">El archivo <b>{{ strtoupper($publication->extension) }}</b> no se puede visualizar aquí.</p>
          </div>
        @endif
      </div>
    </div>

    {{-- COLUMNA DERECHA: DETALLES --}}
    <div class="panel">
      <div class="info-header">
        <span class="kind-badge">
           @if($publication->is_image)
             <svg class="icon" style="width:14px; height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
           @elseif($publication->kind === 'pdf')
             <svg class="icon" style="width:14px; height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
           @else
             <svg class="icon" style="width:14px; height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
           @endif
           {{ strtoupper($publication->extension ?: 'ARCHIVO') }}
        </span>
        <h1 style="margin-top:10px;">{{ $publication->title }}</h1>
      </div>

      <div class="info-body">
        <div class="label">Descripción</div>
        <div class="value desc-box">
          {{ $publication->description ?: 'Sin descripción proporcionada.' }}
        </div>

        <div class="label">Detalles del Archivo</div>
        <div class="value" style="margin-bottom:0;">
          <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f1f5f9;">
            <span style="color:var(--muted);">Nombre original</span>
            <span style="font-weight:500; text-align:right; max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $publication->original_name }}">{{ $publication->original_name }}</span>
          </div>
          <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f1f5f9;">
            <span style="color:var(--muted);">Tamaño</span>
            <span style="font-weight:500;">{{ $publication->nice_size }}</span>
          </div>
          <div style="display:flex; justify-content:space-between; padding:8px 0;">
            <span style="color:var(--muted);">Subido</span>
            <span style="font-weight:500;">{{ $publication->created_at->format('d M, Y') }}</span>
          </div>
        </div>
      </div>

      <div class="actions">
        <a href="{{ route('publications.download', $publication) }}" class="btn-action btn-primary">
          <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M12 12.75l-3-3m0 0l3-3m-3 3h7.5"></path></svg>
          Descargar Archivo
        </a>

        @auth
          <form action="{{ route('publications.destroy', $publication) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este archivo permanentemente?');" style="width:100%;">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-action btn-danger-ghost">
              <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"></path></svg>
              Eliminar
            </button>
          </form>
        @endauth
      </div>
    </div>

  </div>
</div>
@endsection