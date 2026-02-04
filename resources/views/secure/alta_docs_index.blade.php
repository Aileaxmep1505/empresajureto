@extends('layouts.app')
@section('title','Documentación confidencial para altas')

@php
  use Illuminate\Support\Str;
  use App\Models\AltaDoc;

  $q         = $q ?? request('q', '');
  $category  = $category ?? request('category', '');
  $catLabels = $catLabels ?? AltaDoc::categoryLabels();

  $categoriesUi = [
    '' => 'Todas',
    AltaDoc::CATEGORY_CEDULA_ESTADO      => $catLabels[AltaDoc::CATEGORY_CEDULA_ESTADO] ?? 'Cédula por estado',
    AltaDoc::CATEGORY_CEDULA_MUNICIPIO   => $catLabels[AltaDoc::CATEGORY_CEDULA_MUNICIPIO] ?? 'Cédula por municipio',
    AltaDoc::CATEGORY_CEDULA_UNIVERSIDAD => $catLabels[AltaDoc::CATEGORY_CEDULA_UNIVERSIDAD] ?? 'Cédula por universidad',
  ];
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

    {{-- Toolbar --}}
    <div class="toolbar">
      <div class="segmented" role="tablist" aria-label="Filtros por tipo">
        @foreach($categoriesUi as $key => $label)
          @php
            $isActive = ($category === (string)$key);
            $params = request()->query();

            // al cambiar filtro, reset page
            unset($params['page']);

            if($key === '') unset($params['category']); else $params['category'] = $key;

            $url = url()->current() . (count($params) ? ('?' . http_build_query($params)) : '');
          @endphp

          <a href="{{ $url }}"
             class="seg-btn {{ $isActive ? 'active' : '' }}"
             aria-pressed="{{ $isActive ? 'true' : 'false' }}">
            {{ $label }}
          </a>
        @endforeach
      </div>

      <form method="GET" action="{{ url()->current() }}" class="search-form" id="searchForm" autocomplete="off">
        <input type="hidden" name="category" value="{{ $category }}">
        <div class="search-pill" title="Buscar por título, notas o nombre de archivo">
          <svg class="s-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M21 21l-4.35-4.35M10 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <input id="q" name="q" value="{{ $q }}" type="text" placeholder="Buscar…" aria-label="Buscar">
          <button type="button" class="x-btn" id="clearSearch" aria-label="Limpiar búsqueda">×</button>

          <button type="submit" class="sr-only" tabindex="-1" aria-hidden="true">Buscar</button>
        </div>
      </form>
    </div>

    {{-- Grid --}}
    <div class="docs-grid" aria-live="polite">
      @forelse($docs as $doc)
        @php
          $filename = $doc->original_name ?? basename($doc->path ?? '');
          if (!Str::contains((string)$filename, '.')) {
            $extFromPath = pathinfo($doc->path ?? '', PATHINFO_EXTENSION);
            if ($extFromPath) $filename .= '.' . $extFromPath;
          }

          $ext  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
          $mime = $doc->mime ?? null;

          $isPdf   = ($ext === 'pdf');
          $isImage = $mime ? Str::startsWith($mime, 'image/') : in_array($ext, ['jpg','jpeg','png','gif','webp','svg']);
          $isVideo = $mime ? Str::startsWith($mime, 'video/') : in_array($ext, ['mp4','mov','webm','mkv']);

          $title = $doc->title ?: ($doc->original_name ?? 'Documento');
          $meta  = $doc->notes ?: ($doc->friendly_type ?? 'Documento');

          $dateLabel = $doc->doc_date
            ? \Carbon\Carbon::parse($doc->doc_date)->format('d M Y')
            : (optional($doc->created_at)->format('d M Y') ?? '');

          $previewUrl  = route('alta.docs.preview', $doc);
          $downloadUrl = route('alta.docs.download', $doc);

          $categoryLabel = $doc->category_label ?? 'Documento';
        @endphp

        <article class="doc-card" data-id="{{ $doc->id }}" tabindex="0" aria-label="{{ $title }}">

          <button
            type="button"
            class="doc-hit js-open-preview"
            data-title="{{ $title }}"
            data-filename="{{ $filename }}"
            data-ext="{{ $ext }}"
            data-url="{{ $previewUrl }}"
            data-download="{{ $downloadUrl }}"
            aria-label="Ver {{ $title }}"
          ></button>

          <div class="doc-hero">
            <div class="doc-hero-top">
              <div class="badges">
                <span class="doc-pill doc-pill-type">{{ $categoryLabel }}</span>
                <span class="doc-pill {{ $isPdf ? 'doc-pill-pdf' : 'doc-pill-file' }}">{{ strtoupper($ext ?: 'FILE') }}</span>
              </div>

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
              @if($isImage)
                <div class="doc-placeholder">
                  <div class="doc-placeholder-inner">
                    <div class="doc-big-ext">IMG</div>
                    <div class="doc-fileinfo">
                      <div class="doc-filetitle">{{ Str::limit($title, 80) }}</div>
                      <div class="doc-filemeta">{{ $dateLabel }} • {{ $meta }}</div>
                    </div>
                  </div>
                </div>
              @elseif($isVideo)
                <div class="doc-placeholder">
                  <div class="doc-placeholder-inner">
                    <div class="doc-big-ext">VID</div>
                    <div class="doc-fileinfo">
                      <div class="doc-filetitle">{{ Str::limit($title, 80) }}</div>
                      <div class="doc-filemeta">{{ $dateLabel }} • {{ $meta }}</div>
                    </div>
                  </div>
                </div>
              @elseif($isPdf)
                <div class="doc-placeholder">
                  <div class="doc-placeholder-inner">
                    <div class="doc-big-ext doc-big-ext-pdf">PDF</div>
                    <div class="doc-fileinfo">
                      <div class="doc-filetitle">{{ Str::limit($title, 80) }}</div>
                      <div class="doc-filemeta">{{ $dateLabel }} • {{ $meta }}</div>
                    </div>
                  </div>
                </div>
              @else
                <div class="doc-placeholder">
                  <div class="doc-placeholder-inner">
                    <div class="doc-big-ext">{{ strtoupper($ext ?: 'FILE') }}</div>
                    <div class="doc-fileinfo">
                      <div class="doc-filetitle">{{ Str::limit($title, 80) }}</div>
                      <div class="doc-filemeta">{{ $dateLabel }} • {{ $meta }}</div>
                    </div>
                  </div>
                </div>
              @endif
            </div>

            <div class="doc-title">{{ Str::limit($title, 60) }}</div>
            <div class="doc-date">Fecha: <strong>{{ $dateLabel ?: '—' }}</strong></div>
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
                <div class="doc-footer-name">{{ Str::limit($doc->original_name ?? $title, 40) }}</div>
                <div class="doc-footer-sub">{{ $categoryLabel }} • {{ $doc->human_size ?? '—' }}</div>
              </div>
            </div>

            <a class="icon-chip icon-chip-blue js-stop" href="{{ $downloadUrl }}" aria-label="Descargar {{ $title }}">
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

{{-- MODAL: Subir --}}
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

        <label class="lbl">Tipo</label>
        <select name="category" class="inp" required>
          <option value="" disabled {{ old('category') ? '' : 'selected' }}>Selecciona…</option>
          <option value="{{ AltaDoc::CATEGORY_CEDULA_ESTADO }}" {{ old('category')===AltaDoc::CATEGORY_CEDULA_ESTADO ? 'selected' : '' }}>Cédula por estado</option>
          <option value="{{ AltaDoc::CATEGORY_CEDULA_MUNICIPIO }}" {{ old('category')===AltaDoc::CATEGORY_CEDULA_MUNICIPIO ? 'selected' : '' }}>Cédula por municipio</option>
          <option value="{{ AltaDoc::CATEGORY_CEDULA_UNIVERSIDAD }}" {{ old('category')===AltaDoc::CATEGORY_CEDULA_UNIVERSIDAD ? 'selected' : '' }}>Cédula por universidad</option>
        </select>

        <label class="lbl">Título</label>
        <input type="text" name="title" class="inp" maxlength="160" required
               placeholder="Ej: Cédula 2026 · Estado de Sonora"
               value="{{ old('title') }}">

        <label class="lbl">Fecha</label>
        <input type="date" name="doc_date" class="inp" required value="{{ old('doc_date') }}">

        <label class="lbl">Archivos</label>
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
          <input id="files_input" name="files[]" type="file" multiple class="drop-input"
                 accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.xml,.txt">
        </div>

        <div id="files_chips" class="chips"></div>

        <label class="lbl">Notas (opcional)</label>
        <input type="text" name="notes" class="inp" maxlength="500"
               placeholder="Ej: Vigencia, observaciones, referencia interna"
               value="{{ old('notes') }}">

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
      <div class="preview-loading" id="preview-loading">Cargando…</div>
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
#app, .app, .wrapper, .main, main, .content, .content-wrapper, .page-content, .layout-content{
  background:transparent !important;
}

.alta-page{ min-height:100vh; width:100%; background:transparent !important; }
.alta-wrap{ max-width:1200px; margin:0 auto; padding:22px 18px 30px; }
.alta-header{ display:flex; align-items:flex-start; justify-content:space-between; gap:14px; flex-wrap:wrap; margin-bottom:12px; }
.alta-title{ margin:0; font-size:28px; font-weight:900; letter-spacing:-.02em; color:var(--ink); }
.alta-sub{ margin:6px 0 0; color:var(--muted); font-weight:600; }
.alta-head-actions{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.inline{ margin:0; }

.mini-pill{
  display:inline-flex; align-items:center; gap:10px;
  padding:9px 14px; border-radius:999px; border:0;
  background:#ffffff; color:#0b1220; font-weight:700;
  cursor:pointer; text-decoration:none;
  box-shadow:0 10px 22px rgba(2,6,23,.08);
  transition:transform .14s ease, box-shadow .14s ease;
  white-space:nowrap;
}
.mini-pill:hover{ transform:translateY(-2px); box-shadow:var(--hover-blue); }
.mini-pill-ico{
  width:30px;height:30px; border-radius:999px;
  display:inline-flex; align-items:center; justify-content:center;
  background:rgba(15,23,42,.06); color:#0b1220;
}
.mini-pill-ico svg{ width:16px;height:16px; stroke:currentColor; stroke-width:1.9; fill:none; stroke-linecap:round; stroke-linejoin:round; }
.mini-pill-muted{ background:#ffffff; color:#0b1220; }

.flash{ border-radius:14px; padding:10px 12px; margin:10px 0 14px; font-weight:700; backdrop-filter:blur(6px); }
.flash-ok{ background:rgba(220,252,231,.9); color:#166534; }
.flash-err{ background:rgba(254,242,242,.92); color:#991b1b; }

.toolbar{
  display:flex; align-items:center; justify-content:space-between;
  gap:12px; flex-wrap:wrap;
  margin:10px 0 12px;
}
.segmented{ display:flex; flex-wrap:wrap; gap:8px; }
.seg-btn{
  display:inline-flex; align-items:center;
  border:0; text-decoration:none;
  padding:8px 12px;
  border-radius:999px;
  background:rgba(255,255,255,.9);
  color:#0b1220; font-weight:700;
  box-shadow:0 10px 22px rgba(2,6,23,.06);
  transition:transform .14s ease, box-shadow .14s ease;
}
.seg-btn:hover{ transform:translateY(-2px); box-shadow:var(--hover-blue); }
.seg-btn.active{ background:#0b1220; color:#ffffff; }

.search-form{ margin:0; }
.search-pill{
  display:flex; align-items:center; gap:10px;
  padding:8px 12px;
  border-radius:999px;
  background:rgba(255,255,255,.92);
  box-shadow:0 10px 22px rgba(2,6,23,.06);
  min-width:320px;
}
.search-pill .s-ico{ width:18px;height:18px; color:#64748b; }
.search-pill input{
  border:0; outline:0; width:100%;
  background:transparent;
  font-weight:600;
  color:#0b1220;
  font-size:.92rem;
}
.search-pill input::placeholder{ color:#94a3b8; }
.x-btn{
  border:0; background:transparent;
  width:28px; height:28px;
  border-radius:999px;
  font-size:20px;
  line-height:1;
  color:#94a3b8;
  cursor:pointer;
  transition:transform .14s ease, box-shadow .14s ease, background .14s ease;
}
.x-btn:hover{ background:rgba(15,23,42,.06); transform:translateY(-1px); }

.docs-grid{ display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:16px; align-items:start; margin-top:10px; }
@media(max-width: 980px){ .docs-grid{ grid-template-columns:repeat(2, minmax(0, 1fr)); } .search-pill{ min-width:260px; } }
@media(max-width: 640px){ .docs-grid{ grid-template-columns:1fr; } .search-pill{ width:100%; min-width:unset; } }

.doc-card{
  background:rgba(255,255,255,.85);
  border:1px solid rgba(15,23,42,.06);
  border-radius:18px;
  box-shadow:var(--shadow-soft);
  overflow:hidden;
  position:relative;
  transition:transform .14s ease, box-shadow .14s ease;
  backdrop-filter: blur(8px);
  cursor:pointer;
}
.doc-card:hover{ transform:translateY(-6px); box-shadow:var(--shadow); }
.doc-hit{ position:absolute; inset:0; background:transparent; border:0; cursor:pointer; z-index:1; }
.doc-card a, .doc-card button, .doc-card form{ cursor:default; }

.doc-hero{ padding:14px; position:relative; z-index:2; display:flex; flex-direction:column; gap:10px; }
.doc-hero-top{ display:flex; align-items:center; justify-content:space-between; gap:10px; }
.badges{ display:flex; gap:8px; flex-wrap:wrap; }

.doc-pill{
  display:inline-flex; align-items:center;
  padding:7px 10px;
  border-radius:999px;
  font-weight:900;
  font-size:12px;
  border:0;
  box-shadow:0 10px 22px rgba(2,6,23,.06);
}
.doc-pill-type{ background:#ffffff; color:#0b1220; border:1px solid rgba(15,23,42,.08); }
.doc-pill-file{ background:#fff7ed; color:#b45309; }
.doc-pill-pdf{ background:var(--pdf-soft); color:#b91c1c; }

.icon-chip{
  width:42px;height:42px; border-radius:14px; border:0;
  background:#ffffff;
  display:inline-flex; align-items:center; justify-content:center;
  cursor:pointer; text-decoration:none;
  transition:transform .14s ease, box-shadow .14s ease;
  box-shadow:0 10px 22px rgba(2,6,23,.06);
  position:relative; z-index:4;
}
.icon-chip:hover{ transform:translateY(-2px); box-shadow:var(--hover-blue); }
.icon-chip svg{ width:18px;height:18px; stroke:currentColor; stroke-width:1.7; fill:none; stroke-linecap:round; stroke-linejoin:round; }
.icon-chip-danger{ background:#fff1f2; color:#e11d48; }
.icon-chip-blue{ background:#eff6ff; color:#1d4ed8; }

.doc-media{
  border-radius:14px;
  overflow:hidden;
  background:linear-gradient(180deg,#f8fafc,#ffffff);
  border:1px solid rgba(15,23,42,.06);
  min-height:170px;
  display:flex;
  align-items:center;
  justify-content:center;
}
.doc-placeholder{ width:100%; height:100%; display:flex; align-items:center; justify-content:center; padding:18px; }
.doc-placeholder-inner{ display:flex; gap:12px; align-items:center; width:100%; }
.doc-big-ext{
  min-width:86px; height:64px; border-radius:14px;
  display:flex; align-items:center; justify-content:center;
  font-weight:900; font-size:22px;
  background:#e2e8f0; color:#0f172a;
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
.doc-date{ margin-top:-4px; color:#64748b; font-weight:700; font-size:13px; }

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
.preview-loading{
  display:none;
  font-weight:800;
  color:#64748b;
  padding:10px 12px;
  border-radius:14px;
  background:#ffffff;
  border:1px solid rgba(15,23,42,.08);
  margin-bottom:10px;
}
.preview-frame{ width:100%; height:65vh; border:1px solid rgba(15,23,42,.10); border-radius:14px; background:#f9fafb; display:none; }
.preview-fallback{ display:none; font-weight:700; color:#64748b; padding:12px; border-radius:14px; background:#f9fafb; border:1px dashed rgba(15,23,42,.18); }
.preview-footer{ justify-content:flex-end; }

@media(max-width:640px){
  .alta-wrap{ padding:18px 14px 26px; }
  .modal{ align-items:flex-end; }
  .modal__panel{ max-width:100%; border-radius:18px 18px 0 0; max-height:82vh; margin:0 0 env(safe-area-inset-bottom,0); }
  .preview-frame{ height:60vh; }
}

.lbl{ display:block; margin:12px 0 6px; font-weight:900; color:#0f172a; }
.inp{
  width:100%;
  padding:10px 12px;
  border-radius:14px;
  border:1px solid rgba(15,23,42,.10);
  background:#ffffff;
  font-weight:700;
}
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

.sr-only{
  position:absolute !important;
  width:1px !important;
  height:1px !important;
  padding:0 !important;
  margin:-1px !important;
  overflow:hidden !important;
  clip:rect(0,0,0,0) !important;
  white-space:nowrap !important;
  border:0 !important;
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
        const loading = document.getElementById('preview-loading');
        if(frame){ frame.src=''; frame.style.display='none'; }
        if(fallback){ fallback.style.display='none'; }
        if(loading){ loading.style.display='none'; }
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

    const previewFrame = document.getElementById('preview-frame');
    const previewFallback = document.getElementById('preview-fallback');
    const previewLoading = document.getElementById('preview-loading');

    function setPreviewLoading(on){
      if(!previewLoading) return;
      previewLoading.style.display = on ? 'block' : 'none';
    }

    function openPreview(btn){
      const title = btn.dataset.title || 'Documento';
      const url   = btn.dataset.url || '#';
      const durl  = btn.dataset.download || url;
      const ext   = (btn.dataset.ext || (btn.dataset.filename || '').split('.').pop() || '').toLowerCase();

      const titleEl = document.getElementById('preview-title');
      const dl      = document.getElementById('preview-download');

      if(titleEl) titleEl.textContent = title;
      if(dl) dl.href = durl;

      const canEmbed = ['pdf','png','jpg','jpeg','gif','webp'].includes(ext);

      if(previewFallback) previewFallback.style.display = 'none';
      if(previewFrame){ previewFrame.style.display='none'; previewFrame.src=''; }

      setPreviewLoading(true);

      if(canEmbed && previewFrame){
        previewFrame.src = url;
        openModal('preview-modal');
      } else {
        setPreviewLoading(false);
        if(previewFallback) previewFallback.style.display = 'block';
        openModal('preview-modal');
      }
    }

    document.querySelectorAll('.js-open-preview').forEach(btn=>{
      btn.addEventListener('click', (e)=>{
        e.preventDefault();
        e.stopPropagation();
        openPreview(btn);
      });
    });

    document.querySelectorAll('.doc-card').forEach(card=>{
      card.addEventListener('click', (e)=>{
        if(e.target.closest('.js-stop')) return;
        if(e.target.closest('a')) return;
        if(e.target.closest('button') && !e.target.closest('.doc-hit')) return;
        if(e.target.closest('form')) return;

        const btn = card.querySelector('.js-open-preview');
        if(btn) openPreview(btn);
      });
    });

    if(previewFrame){
      previewFrame.addEventListener('load', ()=>{
        setPreviewLoading(false);
        previewFrame.style.display = 'block';
        if(previewFallback) previewFallback.style.display = 'none';
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

    const form = document.getElementById('searchForm');
    const qInput = document.getElementById('q');
    const clear = document.getElementById('clearSearch');

    let t = null;
    function submitSearch(){
      if(!form) return;
      const page = form.querySelector('input[name="page"]');
      if(page) page.remove();
      form.submit();
    }

    if(qInput && form){
      qInput.addEventListener('keydown', function(e){
        if(e.key === 'Enter'){
          e.preventDefault();
          submitSearch();
        }
      });

      qInput.addEventListener('input', function(){
        clearTimeout(t);
        t = setTimeout(submitSearch, 450);
      });
    }

    if(clear && qInput){
      clear.addEventListener('click', function(){
        qInput.value = '';
        submitSearch();
      });
    }
  });
})();
</script>
