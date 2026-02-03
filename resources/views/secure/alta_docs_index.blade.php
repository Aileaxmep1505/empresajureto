@extends('layouts.app')

@section('title','Documentación confidencial para altas')

@php
  use Illuminate\Support\Str;
  use Illuminate\Support\Facades\Storage;
@endphp

@section('content')
<div class="alta-page">
  <div class="alta-wrap">
    {{-- Header --}}
    <div class="alta-header">
      <div class="alta-head-left">
        <h1 class="alta-title">Documentación para altas</h1>
        <p class="alta-sub">Gestión de contratos, formatos y políticas protegidas por NIP.</p>
      </div>

      <div class="alta-head-actions">
        {{-- Subir --}}
        <button type="button" class="mini-pill" data-modal-target="upload-modal" aria-label="Subir documento">
          <span class="mini-pill-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24">
              <path d="M12 16V4"/>
              <path d="M8 8l4-4 4 4"/>
              <rect x="4" y="14" width="16" height="6" rx="2"/>
            </svg>
          </span>
          <span class="mini-pill-txt">Subir documento</span>
        </button>

        {{-- Cerrar sesión --}}
        <form action="{{ route('secure.alta-docs.logout') }}" method="POST" class="inline">
          @csrf
          <button type="submit" class="mini-pill" aria-label="Cerrar sesión">
            <span class="mini-pill-ico" aria-hidden="true">
              <svg viewBox="0 0 24 24">
                <path d="M10 7V6a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-6a2 2 0 0 1-2-2v-1"/>
                <path d="M14 12H3"/>
                <path d="M6 9l-3 3 3 3"/>
              </svg>
            </span>
            <span class="mini-pill-txt">Cerrar sesión</span>
          </button>
        </form>
      </div>
    </div>

    {{-- Mensajes --}}
    @if(session('ok'))
      <div class="flash flash-ok">{{ session('ok') }}</div>
    @endif
    @if(session('error'))
      <div class="flash flash-err">{{ session('error') }}</div>
    @endif
    @if($errors->any())
      <div class="flash flash-err">
        @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
      </div>
    @endif

    {{-- Grid --}}
    <div class="docs-grid" aria-live="polite">
      @forelse($docs as $doc)
        @php
          $filename = $doc->original_name ?? basename($doc->file_path ?? '');
          if (!Str::contains((string)$filename, '.')) {
            $extFromPath = pathinfo($doc->file_path ?? '', PATHINFO_EXTENSION);
            if ($extFromPath) $filename .= '.' . $extFromPath;
          }

          $mime = $doc->mime_type ?? null;
          $ext  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
          $isPdf = ($ext === 'pdf');

          $isImage = $mime ? Str::startsWith($mime, 'image/') : in_array($ext, ['jpg','jpeg','png','gif','webp','svg']);
          $isVideo = $mime ? Str::startsWith($mime, 'video/') : in_array($ext, ['mp4','mov','webm','mkv']);

          $title = $doc->title ?? $doc->original_name ?? 'Documento';
          $meta  = ($doc->notes ?: ($doc->friendly_type ?? 'Documento'));

          $dateLabel = $doc->date
            ? \Carbon\Carbon::parse($doc->date)->format('d M Y')
            : (optional($doc->created_at)->format('d M Y') ?? '');

          $previewUrl  = route('alta.docs.preview', $doc);
          $downloadUrl = route('alta.docs.download', $doc);

          $displayUrl = null;
          if (!empty($doc->url) && Str::startsWith($doc->url, ['http://','https://'])) {
            $displayUrl = $doc->url;
          } elseif (!empty($doc->file_path) && Storage::disk('public')->exists($doc->file_path)) {
            $displayUrl = Storage::disk('public')->url($doc->file_path);
            if (!$mime) {
              try { $mime = Storage::disk('public')->mimeType($doc->file_path); }
              catch (\Throwable $_) { $mime = $doc->mime_type ?? null; }
            }
          } else {
            $displayUrl = $doc->url ?? '';
          }
        @endphp

        <article class="doc-card" data-id="{{ $doc->id }}" tabindex="0" aria-label="{{ $title }}">
          {{-- Capa clickeable SOLO para abrir preview --}}
          <button
            type="button"
            class="doc-hit js-open-preview"
            data-name="{{ $title }}"
            data-url="{{ $previewUrl }}"
            data-download="{{ $downloadUrl }}"
            aria-label="Ver {{ $title }}"
          ></button>

          <div class="doc-hero">
            <div class="doc-hero-top">
              <span class="doc-pill {{ $isPdf ? 'doc-pill-pdf' : 'doc-pill-file' }}">
                {{ strtoupper($ext ?: 'FILE') }}
              </span>

              <form method="POST" action="{{ route('alta.docs.destroy', $doc) }}" class="doc-del-form" aria-label="Eliminar">
                @csrf
                @method('DELETE')
                <button type="submit" class="icon-chip icon-chip-danger js-stop" aria-label="Eliminar {{ $title }}">
                  <svg viewBox="0 0 24 24">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                    <path d="M10 11v6"></path>
                    <path d="M14 11v6"></path>
                    <path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"></path>
                  </svg>
                </button>
              </form>
            </div>

            <div class="doc-media" role="img" aria-label="{{ $title }}">
              @if($isImage && !empty($doc->file_path))
                <img src="{{ asset('storage/' . $doc->file_path) }}" alt="{{ $title }}">
              @elseif($isVideo && !empty($doc->file_path))
                <video controls class="js-stop">
                  <source src="{{ asset('storage/' . $doc->file_path) }}" type="{{ $mime ?: 'video/mp4' }}">
                </video>
              @elseif($isPdf)
                <div class="doc-placeholder">
                  <div class="doc-placeholder-inner">
                    <div class="doc-big-ext doc-big-ext-pdf">PDF</div>
                    <div class="doc-fileinfo">
                      <div class="doc-filetitle">{{ \Illuminate\Support\Str::limit($title, 80) }}</div>
                      <div class="doc-filemeta">{{ $dateLabel }} • {{ $meta }}</div>
                    </div>
                  </div>
                </div>
              @else
                <div class="doc-placeholder">
                  <div class="doc-placeholder-inner">
                    <div class="doc-big-ext">{{ strtoupper($ext ?: 'FILE') }}</div>
                    <div class="doc-fileinfo">
                      <div class="doc-filetitle">{{ \Illuminate\Support\Str::limit($title, 80) }}</div>
                      <div class="doc-filemeta">{{ $dateLabel }} • {{ $meta }}</div>
                    </div>
                  </div>
                </div>
              @endif
            </div>

            <div class="doc-title">{{ \Illuminate\Support\Str::limit($title, 60) }}</div>
          </div>

          <div class="doc-footer">
            <div class="doc-footer-left">
              <div class="doc-type-dot {{ $isPdf ? 'dot-pdf' : 'dot-file' }}" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                  <path d="M14 2v6h6"></path>
                  <path d="M8 13h8"></path>
                  <path d="M8 17h8"></path>
                </svg>
              </div>

              <div class="doc-footer-meta">
                <div class="doc-footer-name">{{ \Illuminate\Support\Str::limit($title, 40) }}</div>
                <div class="doc-footer-sub">{{ $dateLabel }} • {{ $meta }}</div>
              </div>
            </div>

            <a class="icon-chip icon-chip-blue js-stop"
               href="{{ $downloadUrl }}"
               aria-label="Descargar {{ $title }}">
              <svg viewBox="0 0 24 24">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
              </svg>
            </a>
          </div>
        </article>
      @empty
        <div class="empty">
          No hay documentos. Usa “Subir documento” para agregar el primero.
        </div>
      @endforelse
    </div>

    <div class="pager">
      {{ $docs->links() }}
    </div>
  </div>
</div>

{{-- MODAL: Subir documentación --}}
<div id="upload-modal" class="modal" aria-hidden="true">
  <div class="modal__overlay" data-modal-close></div>
  <div class="modal__panel">
    <div class="modal__header">
      <h2 class="modal__title">Subir documento</h2>
      <button type="button" class="modal__close" data-modal-close aria-label="Cerrar">
        <svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
    </div>

    <div class="modal__body">
      <form action="{{ route('alta.docs.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="dropzone">
          <div class="drop-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24">
              <path d="M12 16V4"/>
              <path d="M8 8l4-4 4 4"/>
              <rect x="4" y="14" width="16" height="6" rx="2"/>
            </svg>
          </div>
          <div class="drop-body">
            <div class="drop-title">Arrastra o haz clic para escoger</div>
            <div class="drop-hint">PDF, Word, Excel, CSV, XML, TXT · Máx. 20 MB.</div>
          </div>
          <input
            id="files_input"
            name="files[]"
            type="file"
            multiple
            class="drop-input"
            accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.xml,.txt"
          >
        </div>

        <div id="files_chips" class="chips"></div>

        <label class="lbl">Notas (opcional)</label>
        <input type="text" name="notes" class="inp" maxlength="500" placeholder="Ej: Alta de nuevos proveedores" value="{{ old('notes') }}">

        <div class="modal__footer">
          <button type="button" class="mini-pill mini-pill-muted" data-modal-close>Cancelar</button>
          <button type="submit" class="mini-pill">Subir</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- MODAL: Preview --}}
<div id="preview-modal" class="modal" aria-hidden="true">
  <div class="modal__overlay" data-modal-close></div>
  <div class="modal__panel modal__panel--wide">
    <div class="modal__header">
      <h2 class="modal__title" id="preview-title">Documento</h2>
      <button type="button" class="modal__close" data-modal-close aria-label="Cerrar">
        <svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
    </div>

    <div class="modal__body preview-body">
      <iframe id="preview-frame" class="preview-frame" src="" title="Previsualización"></iframe>
      <div id="preview-fallback" class="preview-fallback">
        Este tipo de archivo no se puede previsualizar aquí. Descárgalo directamente.
      </div>
    </div>

    <div class="modal__footer preview-footer">
      <a id="preview-download" href="#" class="mini-pill">Descargar</a>
    </div>
  </div>
</div>
@endsection

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
:root{
  --ink:#0b1220;
  --muted:#667085;

  --pdf:#dc2626;
  --pdf-soft:#fee2e2;

  --shadow-soft:0 14px 34px rgba(2,6,23,.06);
  --shadow:0 18px 44px rgba(2,6,23,.08);
  --hover-blue:0 16px 34px rgba(29,78,216,.26);

  font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial;
}

/* =========================================================
   FONDO: arriba 100% blanco (#fff), y desde la mitad empieza verde
   + full-bleed (sin bordes grises del layout)
   ========================================================= */
html, body{
  height:100%;
  margin:0;
  background: linear-gradient(
    180deg,
    #ffffff 0%,
    #ffffff 52%,
    #fbfff6 68%,
    #f7feec 82%,
    #f0ffe0 100%
  ) !important;
}
body{ background-color:transparent !important; }

/* wrappers típicos de layouts admin (no pasa nada si no existen) */
#app, .app, .wrapper, .main, main, .content, .content-wrapper, .page-content, .layout-content{
  background:transparent !important;
}

/* quita paddings laterales del contenedor principal (si tu layout los mete) */
.content-wrapper, .page-content, main, .layout-content{
  padding-left:0 !important;
  padding-right:0 !important;
}

/* bootstrap container puede dejar márgenes/padding */
.container, .container-fluid{
  max-width:none !important;
  padding-left:0 !important;
  padding-right:0 !important;
  background:transparent !important;
}

.alta-page{
  min-height:100vh;
  width:100%;
  background:transparent !important; /* el fondo vive en body */
}

.alta-wrap{ max-width:1200px; margin:0 auto; padding:22px 18px 30px; }
.alta-header{ display:flex; align-items:flex-start; justify-content:space-between; gap:14px; flex-wrap:wrap; margin-bottom:14px; }
.alta-title{ margin:0; font-size:28px; font-weight:900; letter-spacing:-.02em; color:var(--ink); }
.alta-sub{ margin:6px 0 0; color:var(--muted); font-weight:600; }
.alta-head-actions{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.inline{ margin:0; }

/* =========================================================
   BOTONES ARRIBA: fondo blanco, letra negra
   ========================================================= */
.mini-pill{
  display:inline-flex;
  align-items:center;
  gap:10px;
  padding:9px 14px;
  border-radius:999px;
  border:0;
  background:#ffffff;
  color:#0b1220;
  font-weight:700;
  cursor:pointer;
  text-decoration:none;
  box-shadow:0 10px 22px rgba(2,6,23,.08);
  transition:transform .14s ease, box-shadow .14s ease, background .14s ease;
  white-space:nowrap;
}
.mini-pill:hover{ transform:translateY(-2px); box-shadow:var(--hover-blue); }

.mini-pill-ico{
  width:30px;height:30px;
  border-radius:999px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  background:rgba(15,23,42,.06);
  color:#0b1220;
}
.mini-pill-ico svg{
  width:16px;height:16px;
  stroke:currentColor;
  stroke-width:1.9;
  fill:none;
  stroke-linecap:round;
  stroke-linejoin:round;
}

.mini-pill-muted{
  background:#ffffff;
  color:#0b1220;
  box-shadow:0 10px 22px rgba(2,6,23,.06);
}
.mini-pill-muted:hover{ box-shadow:var(--hover-blue); }

/* Flash */
.flash{ border-radius:14px; padding:10px 12px; margin:10px 0 14px; font-weight:700; backdrop-filter:blur(6px); }
.flash-ok{ background:rgba(220,252,231,.9); color:#166534; }
.flash-err{ background:rgba(254,242,242,.92); color:#991b1b; }

/* Grid responsive */
.docs-grid{ display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:16px; align-items:start; margin-top:10px; }
@media(max-width: 980px){ .docs-grid{ grid-template-columns:repeat(2, minmax(0, 1fr)); } }
@media(max-width: 640px){ .docs-grid{ grid-template-columns:1fr; } }

/* Card */
.doc-card{
  background:rgba(255,255,255,.85);
  border:1px solid rgba(15,23,42,.06);
  border-radius:18px;
  box-shadow:var(--shadow-soft);
  overflow:hidden;
  position:relative;
  transition:transform .14s ease, box-shadow .14s ease;
  backdrop-filter: blur(8px);
}
.doc-card:hover{ transform:translateY(-6px); box-shadow:var(--shadow); }

/* Hit layer */
.doc-hit{
  position:absolute; inset:0;
  background:transparent;
  border:0;
  cursor:pointer;
  z-index:1;
}

/* Hero */
.doc-hero{ padding:14px; position:relative; z-index:2; display:flex; flex-direction:column; gap:12px; }
.doc-hero-top{ display:flex; align-items:center; justify-content:space-between; gap:10px; }

.doc-pill{
  display:inline-flex;
  align-items:center;
  padding:7px 10px;
  border-radius:999px;
  font-weight:900;
  font-size:12px;
  border:0;
  box-shadow:0 10px 22px rgba(2,6,23,.06);
}
.doc-pill-file{ background:#fff7ed; color:#b45309; }
.doc-pill-pdf{ background:var(--pdf-soft); color:#b91c1c; }

/* Icon chips */
.icon-chip{
  width:42px;height:42px;
  border-radius:14px;
  border:0;
  background:#ffffff;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  text-decoration:none;
  transition:transform .14s ease, box-shadow .14s ease;
  box-shadow:0 10px 22px rgba(2,6,23,.06);
  position:relative;
  z-index:4;
}
.icon-chip:hover{ transform:translateY(-2px); box-shadow:var(--hover-blue); }
.icon-chip svg{
  width:18px;height:18px;
  stroke:currentColor;
  stroke-width:1.7;
  fill:none;
  stroke-linecap:round;
  stroke-linejoin:round;
}
.icon-chip-danger{ background:#fff1f2; color:#e11d48; }
.icon-chip-blue{ background:#eff6ff; color:#1d4ed8; }

/* Media */
.doc-media{
  border-radius:14px;
  overflow:hidden;
  background:linear-gradient(180deg,#f8fafc,#ffffff);
  border:1px solid rgba(15,23,42,.06);
  min-height:170px;
  max-height:320px;
  display:flex;
  align-items:center;
  justify-content:center;
}
.doc-media img, .doc-media video{ width:100%; height:100%; object-fit:cover; display:block; }
.doc-media video{ background:#000; }

/* Placeholder */
.doc-placeholder{ width:100%; height:100%; display:flex; align-items:center; justify-content:center; padding:18px; }
.doc-placeholder-inner{ display:flex; gap:12px; align-items:center; width:100%; }
.doc-big-ext{
  min-width:86px;
  height:64px;
  border-radius:14px;
  display:flex;
  align-items:center;
  justify-content:center;
  font-weight:900;
  font-size:22px;
  background:#e2e8f0;
  color:#0f172a;
}
.doc-big-ext-pdf{ background:var(--pdf); color:#fff; }
.doc-fileinfo{ min-width:0; }
.doc-filetitle{
  font-weight:900; color:#0b1220; line-height:1.1;
  display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
}
.doc-filemeta{ margin-top:6px; color:#64748b; font-weight:700; font-size:13px; }
.doc-title{
  font-weight:900; color:#0b1220;
  display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
}

/* Footer */
.doc-footer{
  padding:12px 14px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  border-top:1px solid rgba(15,23,42,.06);
  background:rgba(255,255,255,.60);
  position:relative;
  z-index:3;
}
.doc-footer-left{ display:flex; align-items:center; gap:12px; min-width:0; }
.doc-type-dot{
  width:44px;height:44px;border-radius:999px;
  display:flex;align-items:center;justify-content:center;
  color:#fff; flex:0 0 44px;
  box-shadow:0 10px 22px rgba(2,6,23,.08);
}
.doc-type-dot svg{ width:18px;height:18px; stroke:white; stroke-width:1.7; fill:none; stroke-linecap:round; stroke-linejoin:round; }
.dot-pdf{ background:#fca5a5; }
.dot-file{ background:#86efac; }

.doc-footer-meta{ min-width:0; }
.doc-footer-name{
  font-weight:900; color:#0b1220;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:260px;
}
.doc-footer-sub{
  margin-top:2px; color:#64748b; font-weight:700; font-size:13px;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:260px;
}

/* Empty & pager */
.empty{
  grid-column:1/-1;
  background:rgba(255,255,255,.75);
  border:1px dashed rgba(15,23,42,.16);
  border-radius:18px;
  padding:18px;
  color:#64748b;
  font-weight:800;
  text-align:center;
}
.pager{ margin-top:16px; display:flex; justify-content:flex-end; }

/* Modals */
.modal{ position:fixed; inset:0; display:none; align-items:center; justify-content:center; z-index:60; }
.modal.is-open{ display:flex; }
.modal__overlay{ position:absolute; inset:0; background:rgba(15,23,42,.32); backdrop-filter:blur(8px); }
.modal__panel{
  position:relative; z-index:1;
  width:100%;
  max-width:560px;
  max-height:90vh;
  border-radius:20px;
  background:rgba(255,255,255,.92);
  border:1px solid rgba(15,23,42,.08);
  box-shadow:0 24px 70px rgba(2,6,23,.25);
  padding:12px 14px 12px;
  display:flex; flex-direction:column;
  transform:translateY(8px);
  animation:modal-in .18s ease-out;
}
.modal__panel--wide{ max-width:980px; }
.modal__header{ display:flex; align-items:center; justify-content:space-between; gap:8px; padding:2px 2px 10px; border-bottom:1px solid rgba(15,23,42,.08); }
.modal__title{ margin:0; font-size:1rem; font-weight:900; color:#0f172a; }
.modal__close{
  width:38px;height:38px;border-radius:999px;border:0;background:#ffffff;
  display:flex;align-items:center;justify-content:center;cursor:pointer;
  box-shadow:0 10px 22px rgba(2,6,23,.06);
  transition:transform .14s ease, box-shadow .14s ease;
}
.modal__close:hover{ transform:translateY(-2px); box-shadow:var(--hover-blue); }
.modal__close svg{ width:16px;height:16px; stroke:#475569; stroke-width:1.7; fill:none; stroke-linecap:round; stroke-linejoin:round; }
.modal__body{ padding:10px 2px 10px; overflow:auto; }
.modal__footer{ padding:10px 2px 2px; border-top:1px solid rgba(15,23,42,.08); display:flex; gap:10px; justify-content:flex-end; margin-top:6px; }
@keyframes modal-in{ from{opacity:0; transform:translateY(16px);} to{opacity:1; transform:translateY(8px);} }

.preview-body{ padding:10px 0 6px; }
.preview-frame{ width:100%; height:65vh; border:1px solid rgba(15,23,42,.10); border-radius:14px; background:#f9fafb; display:none; }
.preview-fallback{ display:none; font-weight:700; color:#64748b; padding:12px; border-radius:14px; background:#f9fafb; border:1px dashed rgba(15,23,42,.18); }
.preview-footer{ justify-content:flex-end; }

@media(max-width:640px){
  .alta-wrap{ padding:18px 14px 26px; }
  .modal{ align-items:flex-end; }
  .modal__panel{ max-width:100%; border-radius:18px 18px 0 0; max-height:82vh; margin:0 0 env(safe-area-inset-bottom,0); }
  .preview-frame{ height:60vh; }
}

/* Dropzone */
.lbl{ display:block; margin:12px 0 6px; font-weight:900; color:#0f172a; }
.inp{ width:100%; padding:10px 12px; border-radius:14px; border:1px solid rgba(15,23,42,.10); background:#ffffff; font-weight:700; }
.inp:focus{ outline:none; box-shadow:0 0 0 3px rgba(29,78,216,.18); border-color:rgba(29,78,216,.28); }
.dropzone{
  position:relative;
  border-radius:16px;
  border:1.6px dashed rgba(148,163,184,.9);
  background:linear-gradient(135deg, rgba(255,255,255,.9), rgba(251,255,246,.9));
  padding:14px;
  display:flex;
  gap:12px;
  align-items:center;
  cursor:pointer;
  transition:transform .12s ease, box-shadow .12s ease, border-color .12s ease;
}
.dropzone:hover{ transform:translateY(-1px); box-shadow:var(--hover-blue); border-color:#93c5fd; }
.drop-ico{
  width:44px;height:44px;border-radius:999px;
  display:flex;align-items:center;justify-content:center;
  background:rgba(15,23,42,.06);
  color:#0b1220;
}
.drop-ico svg{ width:22px;height:22px; stroke:currentColor; stroke-width:1.8; fill:none; stroke-linecap:round; stroke-linejoin:round; }
.drop-title{ font-weight:900; color:#0b1220; }
.drop-hint{ margin-top:2px; color:#64748b; font-weight:700; font-size:13px; }
.drop-input{ position:absolute; inset:0; opacity:0; cursor:pointer; }

.chips{ margin-top:10px; display:flex; flex-wrap:wrap; gap:8px; }
.chips .chip{
  display:inline-flex; gap:8px; align-items:center;
  padding:5px 10px;
  border-radius:999px;
  background:#ffffff;
  color:#0b1220;
  border:1px solid rgba(15,23,42,.08);
  font-weight:800;
  font-size:13px;
}
</style>

<script>
(function(){
  'use strict';

  document.addEventListener('DOMContentLoaded', function(){
    const body = document.body;

    function openModal(id){
      const modal = document.getElementById(id);
      if(!modal) return;
      modal.classList.add('is-open');
      body.style.overflow = 'hidden';
    }

    function closeModal(modal){
      if(!modal) return;
      modal.classList.remove('is-open');
      body.style.overflow = '';
      if(modal.id === 'preview-modal'){
        const frame = document.getElementById('preview-frame');
        const fallback = document.getElementById('preview-fallback');
        if(frame){ frame.src=''; frame.style.display='none'; }
        if(fallback){ fallback.style.display='none'; }
      }
    }

    document.querySelectorAll('[data-modal-target]').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const id = btn.getAttribute('data-modal-target');
        if(id) openModal(id);
      });
    });

    document.addEventListener('click', (e)=>{
      if(e.target.closest('[data-modal-close]')){
        const modal = e.target.closest('.modal') || document.querySelector('.modal.is-open');
        if(modal) closeModal(modal);
      }
    });

    document.addEventListener('keydown', (e)=>{
      if(e.key === 'Escape'){
        const modal = document.querySelector('.modal.is-open');
        if(modal) closeModal(modal);
      }
    });

    document.querySelectorAll('.js-stop').forEach(el=>{
      el.addEventListener('click', (e)=>{ e.stopPropagation(); });
      el.addEventListener('pointerdown', (e)=>{ e.stopPropagation(); });
    });

    function openPreview(btn){
      const name = btn.dataset.name || 'Documento';
      const url  = btn.dataset.url || '#';
      const durl = btn.dataset.download || url;

      const titleEl = document.getElementById('preview-title');
      const frame   = document.getElementById('preview-frame');
      const fallback= document.getElementById('preview-fallback');
      const dl      = document.getElementById('preview-download');

      if(titleEl) titleEl.textContent = name;
      if(dl) dl.href = durl;

      const ext = (name.split('.').pop() || '').toLowerCase();
      const canEmbed = ['pdf','png','jpg','jpeg','gif','webp'].includes(ext);

      if(canEmbed && frame){
        if(fallback) fallback.style.display='none';
        frame.style.display='none';
        frame.src = url;
      } else {
        if(frame){ frame.style.display='none'; frame.src=''; }
        if(fallback) fallback.style.display='block';
      }

      openModal('preview-modal');
    }

    document.querySelectorAll('.js-open-preview').forEach(btn=>{
      btn.addEventListener('click', (e)=>{
        if(e.target && e.target.closest('.js-stop')) return;
        openPreview(btn);
      });
    });

    const previewFrame = document.getElementById('preview-frame');
    if(previewFrame){
      previewFrame.addEventListener('load', ()=>{
        previewFrame.style.display='block';
        const fallback= document.getElementById('preview-fallback');
        if(fallback) fallback.style.display='none';
      });
    }

    const input = document.getElementById('files_input');
    const chipsBox = document.getElementById('files_chips');

    function refreshChips(files){
      if(!chipsBox) return;
      chipsBox.innerHTML = '';
      if(!files || !files.length) return;

      Array.from(files).forEach(file=>{
        const chip = document.createElement('div');
        chip.className = 'chip';
        chip.innerHTML = `<span>${file.name}</span><small style="opacity:.65;font-weight:900;">${Math.round(file.size/1024)} KB</small>`;
        chipsBox.appendChild(chip);
      });
    }
    if(input){ input.addEventListener('change', function(){ refreshChips(input.files); }); }

    const swalBase = Swal.mixin({
      customClass: {
        popup: 'swal2-mini-popup',
        title: 'swal2-mini-title',
        htmlContainer: 'swal2-mini-text',
        confirmButton: 'swal2-mini-confirm',
        cancelButton: 'swal2-mini-cancel'
      },
      buttonsStyling: false
    });

    document.querySelectorAll('.doc-del-form').forEach(form=>{
      form.addEventListener('submit', function(ev){
        ev.preventDefault();
        ev.stopPropagation();

        swalBase.fire({
          title: 'Eliminar documento',
          text: 'Esta acción no se puede deshacer.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Eliminar',
          cancelButtonText: 'Cancelar'
        }).then((result)=>{
          if(!result.isConfirmed) return;

          const url = form.action;
          const token = form.querySelector('input[name="_token"]').value;
          const methodInput = form.querySelector('input[name="_method"]');
          const method = methodInput ? methodInput.value : 'DELETE';

          fetch(url, {
            method:'POST',
            headers:{
              'X-CSRF-TOKEN': token,
              'X-Requested-With': 'XMLHttpRequest',
              'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: new URLSearchParams({'_method': method})
          }).then(async res=>{
            if(res.ok){
              const card = form.closest('.doc-card');
              if(card) card.remove();

              swalBase.fire({
                toast:true,
                position:'top-end',
                icon:'success',
                title:'Eliminado',
                showConfirmButton:false,
                timer:1600,
                timerProgressBar:true
              });
            }else{
              let msg='No se pudo eliminar.';
              try{ const j = await res.json(); if(j && j.message) msg=j.message; }catch(e){}
              swalBase.fire({ icon:'error', title:'Error', text:msg });
            }
          }).catch(()=>{
            swalBase.fire({ icon:'error', title:'Error', text:'Error de red.' });
          });
        });
      });
    });

    document.querySelectorAll('.doc-card').forEach(card=>{
      card.addEventListener('keydown', function(e){
        const active = document.activeElement;
        if(active && (active.closest('.doc-del-form') || active.closest('.icon-chip') || active.closest('a'))) return;
        if(e.key === 'Enter' || e.key === ' '){
          const btn = card.querySelector('.js-open-preview');
          if(btn){ btn.click(); e.preventDefault(); }
        }
      });
    });
  });
})();
</script>

<style>
/* SweetAlert2 minimal/pro */
.swal2-mini-popup{
  border-radius:18px !important;
  padding:14px 14px 12px !important;
  border:1px solid rgba(15,23,42,.08) !important;
  box-shadow:0 22px 70px rgba(2,6,23,.22) !important;
  background:rgba(255,255,255,.94) !important;
  backdrop-filter: blur(10px) !important;
}
.swal2-mini-title{
  font-weight:900 !important;
  font-size:16px !important;
  color:#0b1220 !important;
  margin:0 0 6px !important;
}
.swal2-mini-text{
  color:#667085 !important;
  font-weight:700 !important;
  font-size:13px !important;
  margin:0 !important;
}
.swal2-mini-confirm{
  border-radius:999px !important;
  padding:10px 14px !important;
  border:0 !important;
  background:#fff1f2 !important;
  color:#e11d48 !important;
  font-weight:900 !important;
  cursor:pointer !important;
  box-shadow:0 10px 22px rgba(2,6,23,.08) !important;
}
.swal2-mini-confirm:hover{
  box-shadow:0 16px 34px rgba(29,78,216,.26) !important;
  transform:translateY(-2px);
}
.swal2-mini-cancel{
  border-radius:999px !important;
  padding:10px 14px !important;
  border:0 !important;
  background:#ffffff !important;
  color:#0b1220 !important;
  font-weight:900 !important;
  cursor:pointer !important;
  box-shadow:0 10px 22px rgba(2,6,23,.06) !important;
}
.swal2-mini-cancel:hover{
  box-shadow:0 16px 34px rgba(29,78,216,.26) !important;
  transform:translateY(-2px);
}
.swal2-actions{ gap:10px !important; margin-top:12px !important; }
.swal2-icon{ transform:scale(.92); }

</style>
