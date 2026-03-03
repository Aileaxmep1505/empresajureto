{{-- resources/views/confidential/preview.blade.php --}}
@extends('layouts.app')
@section('title', ($doc->title ?: 'Documento') . ' | Preview')

@php
  use Illuminate\Support\Str;
  use Illuminate\Support\Facades\Storage;

  $backUrl     = route('confidential.vault', $owner->id);
  $downloadUrl = route('confidential.documents.download', $doc->id);

  // Para PDF e imágenes normalmente Storage::url abre inline
  $previewInlineUrl = Storage::disk('public')->url($doc->file_path);

  $title    = $doc->title ?: ($doc->original_name ?: 'Documento');
  $filename = $doc->original_name ?: basename((string)$doc->file_path);

  $ext  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
  $mime = strtolower((string)($doc->mime_type ?? ''));

  $isPdf   = ($ext === 'pdf') || ($mime === 'application/pdf');
  $isImage = $mime ? Str::startsWith($mime, 'image/') : in_array($ext, ['jpg','jpeg','png','gif','webp','svg'], true);
  $isVideo = $mime ? Str::startsWith($mime, 'video/') : in_array($ext, ['mp4','mov','webm','mkv'], true);

  $extBadge = strtoupper($ext ?: 'FILE');

  $exists = isset($exists) ? (bool)$exists : true;

  $uploaderName = optional($doc->uploader)->name ?? '—';

  $dateLabel = $doc->date
    ? \Carbon\Carbon::parse($doc->date)->format('d M Y')
    : (optional($doc->created_at)->format('d M Y') ?? '—');

  $accessLabel = $doc->access_level ?? 'alto';
@endphp

@section('content')
<style>
  :root{
    --bg:#f4f1ea;
    --surface:#ffffff;
    --surface-2:#fbfbfb;
    --border:rgba(15,23,42,.10);
    --ink:#1c1917;
    --muted:#78716c;
    --accent:#111827;

    --ok-bg: rgba(34,197,94,.10);
    --ok-ink:#166534;
    --warn-bg: rgba(245,158,11,.12);
    --warn-ink:#92400e;
    --bad-bg: rgba(239,68,68,.12);
    --bad-ink:#991b1b;
    --none-bg: rgba(100,116,139,.10);
    --none-ink:#475569;

    --r-lg:22px;
    --r-md:14px;

    --shadow: 0 14px 40px rgba(2,6,23,.08);
    --shadow-soft: 0 10px 30px rgba(2,6,23,.06);
  }

  body{ background: var(--bg) !important; }
  #app, main, .content, .container, .container-fluid, .page-content, .app-content{ background: transparent !important; }

  .doc-viewer-breakout{
    width:100vw; position:relative; left:50%; right:50%;
    margin-left:-50vw; margin-right:-50vw;
    padding:0 !important;
    background: var(--bg);
    min-height: calc(100vh - 64px);
  }

  .doc-viewer-layout{
    display:flex;
    min-height: calc(100vh - 64px);
    background: var(--bg);
    font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
    color: var(--ink);
  }

  /* Sidebar */
  .doc-sidebar{
    width: 360px;
    background: var(--surface);
    border-right: 1px solid var(--border);
    display:flex;
    flex-direction:column;
    flex-shrink:0;
    z-index: 10;
  }

  .sidebar-header{ padding: 28px 22px 16px; }

  .btn-back{
    display:inline-flex; align-items:center; gap:8px;
    color: var(--muted);
    font-size:14px;
    font-weight:500;
    text-decoration:none;
    margin-bottom: 18px;
    transition: opacity .18s ease, color .18s ease;
  }
  .btn-back:hover{ opacity:.85; color: var(--ink); }
  .btn-back svg{ width:20px; height:20px; stroke: currentColor; fill:none; }

  .doc-title{
    font-size: 20px;
    font-weight:600;
    line-height: 1.25;
    margin: 0 0 10px 0;
    letter-spacing: -.01em;
  }

  .badge-row{ display:flex; flex-wrap:wrap; gap:8px; }

  .doc-badge{
    display:inline-flex; align-items:center;
    padding:6px 10px;
    border-radius: 999px;
    font-size:12px;
    font-weight:500;
    letter-spacing:.2px;
    border:1px solid var(--border);
    background: var(--surface-2);
    color: var(--ink);
  }
  .doc-badge.soft{ background: #fff; }
  .doc-badge.none{ background: var(--none-bg); color: var(--none-ink); border-color: rgba(71,85,105,.14); }

  .sidebar-body{
    padding: 0 22px 18px;
    flex-grow:1;
    overflow: auto;
  }

  .meta-card{
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: var(--r-md);
    padding: 14px 14px;
    margin-bottom: 12px;
  }

  .meta-grid{ display:grid; gap: 12px; }
  .meta-row{ display:grid; gap: 4px; }

  .meta-label{
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--muted);
    font-weight:500;
  }
  .meta-value{
    font-size: 14px;
    color: var(--ink);
    font-weight:400;
    word-break: break-word;
    line-height: 1.35;
  }

  .uploader{ display:flex; align-items:center; gap:10px; }
  .avatar{
    width:26px;height:26px;border-radius:999px;
    display:grid;place-items:center;
    background: rgba(15,23,42,.08);
    color: var(--ink);
    font-size: 12px;
    font-weight:500;
    flex: 0 0 26px;
  }

  .notes-box{
    background:#fff;
    border:1px solid var(--border);
    border-radius: var(--r-md);
    padding: 12px 12px;
  }
  .notes-text{
    margin:0;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.65;
    font-weight:400;
    white-space: pre-wrap;
  }

  .sidebar-footer{
    padding: 14px 22px 22px;
    background: var(--surface);
    border-top: 1px solid var(--border);
    display:flex;
    flex-direction:column;
    gap:10px;
  }

  .btn{
    display:flex; align-items:center; justify-content:center; gap:8px;
    width:100%;
    padding: 12px 14px;
    border-radius: 12px;
    font-size: 14px;
    font-weight:500;
    text-decoration:none;
    cursor:pointer;
    border:1px solid var(--border);
    background: #fff;
    color: var(--ink);
    transition: transform .14s ease, box-shadow .14s ease, border-color .14s ease;
    user-select:none;
  }
  .btn:hover{ transform: translateY(-1px); box-shadow: var(--shadow-soft); border-color: rgba(15,23,42,.16); }
  .btn svg{ width:18px; height:18px; stroke: currentColor; fill:none; stroke-width: 1.9; stroke-linecap:round; stroke-linejoin:round; }

  .btn-primary{ background: var(--accent); color:#fff; border-color: transparent; }
  .btn-primary:hover{ box-shadow: 0 14px 34px rgba(17,24,39,.20); }

  .btn-secondary{ background:#fff; color: var(--ink); }

  .btn-danger{
    background:#fff;
    color:#b91c1c;
    border-color: rgba(185,28,28,.22);
  }
  .btn-danger:hover{ border-color: rgba(185,28,28,.30); box-shadow: 0 14px 34px rgba(185,28,28,.10); }

  .btn-row{ display:flex; gap:10px; width:100%; }
  .btn-row .btn{ flex: 1 1 0; width:auto; white-space:nowrap; }

  /* Stage */
  .doc-stage{
    flex-grow:1;
    padding: 36px;
    display:flex;
    flex-direction:column;
    align-items:center;
    overflow:hidden;
  }

  .viewer-frame{
    width:100%;
    max-width: 1100px;
    height: calc(100vh - 64px - 72px);
    min-height: 520px;
    background:#fff;
    border-radius: var(--r-lg);
    box-shadow: var(--shadow);
    overflow:hidden;
    border: 1px solid rgba(0,0,0,.06);
    position:relative;
  }

  .viewer-layer{ position:absolute; inset:0; }
  .viewer-layer > iframe,
  .viewer-layer > img,
  .viewer-layer > video{ width:100%; height:100%; border:0; display:block; }
  .viewer-layer > img{ object-fit: contain; background:#fff; }
  .viewer-layer > video{ background:#111; object-fit: contain; }

  .viewer-empty{
    width:100%; height:100%;
    display:flex; align-items:center; justify-content:center;
    text-align:center;
    color: var(--muted);
    padding: 24px;
    background: #fafafa;
  }
  .viewer-empty .box{ max-width: 420px; }
  .viewer-empty .ic{
    width:56px; height:56px;
    border-radius: 18px;
    display:grid; place-items:center;
    margin: 0 auto 14px auto;
    background: rgba(15,23,42,.06);
    color:#111827;
    font-weight:500;
    letter-spacing:.08em;
  }
  .viewer-empty h3{ margin:0 0 6px 0; color: var(--ink); font-size: 15px; font-weight:500; }
  .viewer-empty p{ margin:0; font-size: 13px; font-weight:400; }

  @media (max-width: 1024px){
    .doc-viewer-layout{ flex-direction: column; display:block; }
    .doc-sidebar{ width:100%; border-right:none; border-bottom: 1px solid var(--border); }
    .doc-stage{ padding: 18px; }
    .viewer-frame{ height: 70vh; min-height: 420px; border-radius: 18px; }
    .doc-viewer-breakout{ min-height: 100vh; }
    .sidebar-body{ overflow: visible; }
  }
</style>

<div class="doc-viewer-breakout" style="margin-top:-30px;">
  <div class="doc-viewer-layout">

    <aside class="doc-sidebar">

      <div class="sidebar-header">
        <a href="{{ $backUrl }}" class="btn-back" aria-label="Volver al vault">
          <svg viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          Volver al vault
        </a>

        <h1 class="doc-title"><strong>{{ Str::limit($title, 80) }}</strong></h1>

        <div class="badge-row">
          <span class="doc-badge">{{ $extBadge }}</span>
          <span class="doc-badge soft">doc_key: {{ $doc->doc_key }}</span>
          <span class="doc-badge none">Vault protegido</span>
        </div>
      </div>

      <div class="sidebar-body">
        @if(session('ok'))
          <div class="meta-card" style="background: rgba(220,252,231,.85); border-color: rgba(22,101,52,.14); color:#166534;">
            {{ session('ok') }}
          </div>
        @endif
        @if(session('error'))
          <div class="meta-card" style="background: rgba(254,242,242,.90); border-color: rgba(153,27,27,.14); color:#991b1b;">
            {{ session('error') }}
          </div>
        @endif

        <div class="meta-card">
          <div class="meta-grid">

            <div class="meta-row">
              <div class="meta-label">Fecha</div>
              <div class="meta-value">{{ $dateLabel }}</div>
            </div>

            <div class="meta-row">
              <div class="meta-label">Nivel de acceso</div>
              <div class="meta-value">{{ $accessLabel }}</div>
            </div>

            <div class="meta-row">
              <div class="meta-label">Requiere PIN</div>
              <div class="meta-value">{{ $doc->requires_pin ? 'Sí' : 'No' }}</div>
            </div>

            <div class="meta-row">
              <div class="meta-label">Subido por</div>
              <div class="meta-value uploader">
                <span class="avatar">{{ mb_substr($uploaderName, 0, 1) }}</span>
                <span>{{ $uploaderName }}</span>
              </div>
            </div>

            <div class="meta-row">
              <div class="meta-label">Ruta</div>
              <div class="meta-value">{{ $doc->file_path }}</div>
            </div>

          </div>
        </div>

        @if($doc->description)
          <div class="meta-row" style="margin-top:10px;">
            <div class="meta-label">Descripción</div>
            <div class="notes-box">
              <p class="notes-text">{{ $doc->description }}</p>
            </div>
          </div>
        @endif

        @if(!$exists)
          <div class="meta-card" style="border-color: rgba(239,68,68,.20); background: rgba(254,242,242,.75);">
            <div class="meta-row">
              <div class="meta-label" style="color:#991b1b;">Archivo no disponible</div>
              <div class="meta-value" style="color:#991b1b;">
                El archivo ya no está disponible en el servidor.
              </div>
            </div>
          </div>
        @endif
      </div>

      <div class="sidebar-footer">
        @if($exists)
          <a href="{{ $downloadUrl }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
              <polyline points="7 10 12 15 17 10"></polyline>
              <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            Descargar documento
          </a>
        @endif

        <div class="btn-row">
          <a href="{{ $backUrl }}" class="btn btn-secondary">
            <svg viewBox="0 0 24 24">
              <path d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Vault
          </a>

          <button type="button" class="btn btn-secondary js-copy" data-copy="{{ $doc->file_path }}">
            <svg viewBox="0 0 24 24">
              <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
              <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
            </svg>
            Copiar ruta
          </button>
        </div>

        <form method="POST" action="{{ route('confidential.documents.destroy', $doc->id) }}" id="delForm">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger js-del">
            <svg viewBox="0 0 24 24">
              <polyline points="3 6 5 6 21 6"></polyline>
              <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
              <path d="M10 11v6"></path>
              <path d="M14 11v6"></path>
              <path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"></path>
            </svg>
            Eliminar
          </button>
        </form>
      </div>

    </aside>

    <main class="doc-stage">
      <div class="viewer-frame">
        <div class="viewer-layer">
          @if(!$exists)
            <div class="viewer-empty">
              <div class="box">
                <div class="ic">{{ $extBadge }}</div>
                <h3>Archivo no disponible</h3>
                <p>El archivo ya no está disponible en el servidor.</p>
              </div>
            </div>

          @elseif($isImage)
            <img src="{{ $previewInlineUrl }}" alt="{{ $title }}">

          @elseif($isVideo)
            <video controls>
              <source src="{{ $previewInlineUrl }}" type="{{ $mime ?: 'video/mp4' }}">
            </video>

          @elseif($isPdf)
            <iframe src="{{ $previewInlineUrl }}#toolbar=0" title="Visor PDF"></iframe>

          @else
            <div class="viewer-empty">
              <div class="box">
                <div class="ic">{{ $extBadge }}</div>
                <h3>Vista previa no disponible</h3>
                <p>Este formato requiere descargar para visualizarse.</p>
                <div style="margin-top:14px;">
                  <a href="{{ $downloadUrl }}" class="btn btn-primary" style="display:inline-flex;width:auto;padding:12px 16px;">
                    Descargar
                  </a>
                </div>
              </div>
            </div>
          @endif
        </div>
      </div>
    </main>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function(){
  'use strict';

  function toast(type, title, message){
    const wrapId = 'pvToastWrap';
    let wrap = document.getElementById(wrapId);
    if(!wrap){
      wrap = document.createElement('div');
      wrap.id = wrapId;
      wrap.setAttribute('aria-live','polite');
      wrap.setAttribute('aria-atomic','true');
      wrap.style.cssText = 'position:fixed;top:14px;right:14px;z-index:99999;display:flex;flex-direction:column;gap:10px;';
      document.body.appendChild(wrap);
    }

    const t = document.createElement('div');
    t.style.cssText = 'background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:14px;box-shadow:0 18px 50px rgba(16,24,40,.16);padding:10px 12px;display:flex;gap:10px;align-items:flex-start;transform:translateY(-6px);opacity:0;pointer-events:none;transition:transform .18s ease, opacity .18s ease;min-width:260px;max-width:380px;';
    const icBg = type==='ok' ? 'rgba(16,185,129,.14)' : (type==='err' ? 'rgba(239,68,68,.14)' : 'rgba(59,130,246,.14)');
    const icCo = type==='ok' ? '#065f46' : (type==='err' ? '#991b1b' : '#1d4ed8');

    t.innerHTML = `
      <div style="width:30px;height:30px;border-radius:10px;display:grid;place-items:center;background:${icBg};color:${icCo};font-weight:600;flex:0 0 30px;">
        ${type==='err' ? '!' : (type==='ok' ? '✓' : 'i')}
      </div>
      <div style="flex:1;">
        <div style="font-weight:600;color:#0f172a;font-size:13px;line-height:1.15;">${title || ''}</div>
        <div style="color:#667085;font-size:12px;margin-top:2px;white-space:pre-line;">${message || ''}</div>
      </div>
      <button type="button" aria-label="Cerrar" style="border:none;background:transparent;cursor:pointer;padding:6px;border-radius:10px;color:#667085;">✕</button>
    `;

    wrap.appendChild(t);
    requestAnimationFrame(() => {
      t.style.transform = 'translateY(0)';
      t.style.opacity = '1';
      t.style.pointerEvents = 'auto';
    });

    const kill = () => {
      t.style.opacity = '0';
      t.style.transform = 'translateY(-6px)';
      setTimeout(()=> t.remove(), 220);
    };
    t.querySelector('button').addEventListener('click', kill);
    setTimeout(kill, type === 'info' ? 3800 : 3200);
  }

  document.addEventListener('DOMContentLoaded', function(){

    // Copiar ruta
    const copyBtn = document.querySelector('.js-copy');
    if(copyBtn){
      copyBtn.addEventListener('click', async function(e){
        e.preventDefault();
        const val = copyBtn.getAttribute('data-copy') || '';
        if(!val) return;

        try{
          await navigator.clipboard.writeText(val);
          toast('ok','Copiado','Ruta copiada al portapapeles.');
        }catch(err){
          toast('err','No se pudo copiar','Tu navegador bloqueó el portapapeles.');
        }
      });
    }

    // Confirmación eliminar
    const delBtn = document.querySelector('.js-del');
    if(delBtn){
      delBtn.addEventListener('click', function(e){
        e.preventDefault();

        Swal.fire({
          title: 'Eliminar documento',
          text: 'Esta acción no se puede deshacer.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#b91c1c',
          cancelButtonColor: '#e5e5ea',
          confirmButtonText: 'Eliminar',
          cancelButtonText: 'Cancelar'
        }).then((r)=>{
          if(r.isConfirmed){
            delBtn.closest('form').submit();
          }
        });
      });
    }

  });
})();
</script>
@endsection