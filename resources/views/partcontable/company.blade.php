@extends('layouts.app') 
@section('title',$company->name.' - Parte contable')

@php
  use Illuminate\Support\Str;
  use Illuminate\Support\Facades\Storage;
@endphp

@section('content')
<div class="pc-wrap">
  <div class="pc-header">
    <a href="{{ route('partcontable.index') }}" class="pc-back">← Volver</a>
    <h1 class="pc-title">{{ $company->name }}</h1>
  </div>

  @if(session('success'))
    <div class="pc-flash pc-flash-success">{{ session('success') }}</div>
  @endif
  @if(session('warning'))
    <div class="pc-flash pc-flash-warning">{{ session('warning') }}</div>
  @endif

  {{-- Menu de secciones --}}
  <nav class="pc-sections" aria-label="Secciones">
    @forelse($sections as $s)
      @php
        $url = route('partcontable.company', $company->slug) . '?section=' . $s->key
             . ($year ? '&year=' . $year : '') . ($month ? '&month=' . $month : '');
      @endphp
      <a href="{{ $url }}" class="pc-section-item {{ ($section && $section->id == $s->id) ? 'active' : '' }}">
        {{ $s->name }}
      </a>
    @empty
      <div class="pc-no-sections">No hay secciones configuradas.</div>
    @endforelse
  </nav>

  {{-- Controles: filtros + subir --}}
  <div class="pc-controls">
    <form method="GET" id="pc-filter-form" action="{{ route('partcontable.company', $company->slug) }}" class="pc-filter-form" role="search">
      <input type="hidden" name="section" value="{{ $section?->key ?? '' }}">
      <input type="number" name="year" placeholder="Año" min="2000" max="2099" value="{{ $year ?? '' }}">
      <input type="number" name="month" placeholder="Mes" min="1" max="12" value="{{ $month ?? '' }}">
      <button type="submit" class="pc-btn pc-btn-filter">Filtrar</button>
      <a href="{{ route('partcontable.company', $company->slug) . ($section ? '?section='.$section->key : '') }}" class="pc-btn pc-btn-reset">Limpiar</a>
    </form>

    <div>
      {{-- Botón SUBIR estilo Btn (píldora negra animada) --}}
      <a href="{{ route('partcontable.documents.create', $company->slug) }}" 
         class="Btn pc-upload-btn"
         aria-label="Subir {{ $section?->name ?? 'documento' }}">
        <div class="sign">+</div>
        <div class="text">
          Subir {{ $section?->name ?? 'documento' }}
        </div>
      </a>
    </div>
  </div>

  {{-- Grid documentos --}}
  <div class="pc-grid" aria-live="polite">
    @forelse($documents as $doc)
      @php
        // Nombre y extensión
        $filename = $doc->filename ?? basename($doc->file_path ?? '');
        if (!Str::contains($filename, '.')) {
          $extFromPath = pathinfo($doc->file_path ?? '', PATHINFO_EXTENSION);
          if ($extFromPath) $filename .= '.' . $extFromPath;
        }

        // decidir displayUrl y mime
        $displayUrl = null;
        $mime = $doc->mime_type ?? null;

        if (!empty($doc->url) && Str::startsWith($doc->url, ['http://','https://'])) {
          // URL externa (CDN)
          $displayUrl = $doc->url;
        } elseif ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
          $displayUrl = Storage::disk('public')->url($doc->file_path);
          if (!$mime) {
            try { $mime = Storage::disk('public')->mimeType($doc->file_path); } catch (\Throwable $_) { $mime = $doc->mime_type ?? null; }
          }
        } else {
          // fallback: usar doc->url o vacío
          $displayUrl = $doc->url ?? '';
        }

        $dateLabel = $doc->date ? \Carbon\Carbon::parse($doc->date)->format('d M Y') : \Carbon\Carbon::parse($doc->created_at)->format('d M Y');

        // helper booleans
        $isImage = $mime ? Str::startsWith($mime, 'image/') : in_array(strtolower($ext ?? ''), ['jpg','jpeg','png','gif','webp','svg']);
        $isVideo = $mime ? Str::startsWith($mime, 'video/') : in_array(strtolower($ext ?? ''), ['mp4','mov','webm','mkv']);
        $isPdf   = Str::lower(pathinfo($filename, PATHINFO_EXTENSION)) === 'pdf';
      @endphp

      <article class="card pc-doc-card" aria-labelledby="doc-{{ $doc->id }}" data-id="{{ $doc->id }}" tabindex="0">
        {{-- overlay non-interactive --}}
        <a class="card__link" href="{{ route('partcontable.documents.preview', $doc) }}" target="_blank" rel="noopener"></a>

        <div class="card__hero">
          <header class="card__hero-header">
            <span class="pc-doc-badge">{{ strtoupper(pathinfo($filename, PATHINFO_EXTENSION) ?: ($doc->file_type ?? 'FILE')) }}</span>

            {{-- eliminar en esquina superior derecha --}}
            <div class="pc-card-top-actions" role="group" aria-label="Acciones del documento">
              <form method="POST" action="{{ route('partcontable.documents.destroy', $doc) }}" class="pc-delete-form-inline" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="pc-icon-btn pc-btn-delete" aria-label="Eliminar {{ $doc->title }}">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                    <path d="M10 11v6"></path>
                    <path d="M14 11v6"></path>
                    <path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"></path>
                  </svg>
                </button>
              </form>
            </div>
          </header>

          {{-- preview media --}}
          <div class="pc-hero-media" role="img" aria-label="{{ $doc->title }}">
            @php
              $url = $displayUrl ?? '';
            @endphp

            @if($isImage)
              <img src="{{ asset('storage/' . $doc->file_path) }}" alt="{{ $doc->title }}" style="max-width:100%;">
            @elseif($isVideo)
              <video controls style="max-width:100%;">
                <source src="{{ asset('storage/' . $doc->file_path) }}" type="{{ $doc->mime_type }}">
                Tu navegador no soporta video.
              </video>
            @elseif($isPdf && $url)
              {{-- PDF: placeholder --}}
              <div class="pc-doc-placeholder" title="{{ $doc->title }}">
                <div class="pc-doc-placeholder-inner">
                  <strong class="pc-doc-ext">PDF</strong>
                  <div class="pc-doc-fileinfo">
                    <div class="pc-doc-title-ellipsis">{{ \Illuminate\Support\Str::limit($doc->title, 80) }}</div>
                    <div class="pc-doc-meta small-muted">{{ $dateLabel }} • {{ $doc->subtype?->name ?? ($section?->name ?? '') }}</div>
                  </div>
                </div>
              </div>
            @else
              {{-- Otros tipos: placeholder --}}
              <div class="pc-doc-placeholder" title="{{ $doc->title }}">
                <div class="pc-doc-placeholder-inner">
                  <strong class="pc-doc-ext">{{ strtoupper(pathinfo($filename, PATHINFO_EXTENSION) ?: 'FILE') }}</strong>
                  <div class="pc-doc-fileinfo">
                    <div class="pc-doc-title-ellipsis">{{ \Illuminate\Support\Str::limit($doc->title, 80) }}</div>
                    <div class="pc-doc-meta small-muted">{{ $dateLabel }} • {{ $doc->subtype?->name ?? ($section?->name ?? '') }}</div>
                  </div>
                </div>
              </div>
            @endif
          </div>

          <p id="doc-{{ $doc->id }}" class="card__job-title pc-doc-hero-title">{{ \Illuminate\Support\Str::limit($doc->title, 80) }}</p>
        </div>

        <footer class="card__footer">
          <div class="card__job-summary">
            {{-- icono circular según tipo --}}
            <div class="pc-small-logo" aria-hidden="true">
              @if($isImage)
                <span class="pc-type-badge pc-type-img" aria-hidden="true">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 7h3l2-3h6l2 3h3v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"></path>
                    <circle cx="12" cy="13" r="3"></circle>
                  </svg>
                </span>
              @elseif($isVideo)
                <span class="pc-type-badge pc-type-video" aria-hidden="true">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M23 7l-7 5 7 5V7z"></path>
                    <rect x="1" y="5" width="15" height="14" rx="2"></rect>
                  </svg>
                </span>
              @else
                <span class="pc-type-badge pc-type-doc" aria-hidden="true">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <path d="M14 2v6h6"></path>
                    <path d="M8 13h8"></path>
                    <path d="M8 17h8"></path>
                  </svg>
                </span>
              @endif
            </div>

            <div class="card__job">
              <p class="card__job-title pc-doc-title">{{ \Illuminate\Support\Str::limit($doc->title, 48) }}</p>
              <div class="pc-doc-meta small-muted">{{ $dateLabel }} • {{ $doc->subtype?->name ?? ($section?->name ?? '') }}</div>
            </div>
          </div>

          <div class="pc-company-actions">
            <a class="pc-icon-btn pc-btn-download"
               href="{{ route('partcontable.documents.download', $doc) }}"
               aria-label="Descargar {{ $doc->title }}">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
              </svg>
            </a>
          </div>
        </footer>
      </article>
    @empty
      <div class="pc-empty">No hay documentos en esta sección.</div>
    @endforelse
  </div>

  {{-- paginación --}}
  <div class="pc-pagination" style="margin-top:18px;">
    {{ $documents->withQueryString()->links() }}
  </div>
</div>

{{-- Modal upload --}}
<div class="pc-modal" id="pcUploadModal" aria-hidden="true" aria-labelledby="pcUploadTitle" role="dialog">
  <div class="pc-modal-backdrop" id="pcModalClose" data-action="close"></div>
  <div class="pc-modal-panel" role="document">
    <h3 id="pcUploadTitle">Subir documento - <span id="pcModalSectionName">{{ $section?->name ?? '' }}</span></h3>

    <form id="pcUploadForm" method="POST" enctype="multipart/form-data" action="{{ route('partcontable.documents.store', $company->slug) }}">
      @csrf
      <input type="hidden" name="section_id" value="{{ $section->id ?? '' }}">
      <label>Título</label>
      <input type="text" name="title" placeholder="Nombre del documento" class="pc-input">

      <label>Subcategoría (opcional)</label>
      <select name="subtype_id" id="pcSubtypeSelect" class="pc-input">
        <option value="">-- Seleccionar --</option>
        @foreach($subtypes as $st)
          <option value="{{ $st->id }}">{{ $st->name }}</option>
        @endforeach
      </select>

      <label>Fecha (año-mes-día)</label>
      <input type="date" name="date" class="pc-input">

      <label>Descripción</label>
      <textarea name="description" rows="3" placeholder="Descripción (opcional)" class="pc-input"></textarea>

      <label>Archivo</label>
      <input type="file" name="file" accept=".jpg,.jpeg,.png,.gif,.webp,.svg,.mp4,.mov,.pdf,.doc,.docx,.xls,.xlsx" required class="pc-input">

      <div style="display:flex;gap:10px;margin-top:12px;">
        <button type="submit" class="pc-btn-primary">Subir</button>
        <button type="button" class="pc-btn-secondary" id="pcModalCancel">Cancelar</button>
      </div>
    </form>
  </div>
</div>
@endsection

{{-- Estilos actualizados --}}
<style>
:root{
  --bg:#ffffff;
  --muted:#6b7280;
  --accent1:linear-gradient(90deg,#6b9cff,#7ee7c6);
  --card-shadow: 0 12px 30px rgba(20,24,40,0.06);
  --radius:12px;
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
}

/* Page */
.pc-wrap{padding:18px; position: relative; z-index:2; color:#0b1220;}
.pc-header{display:flex;align-items:center;gap:12px;margin-bottom:12px;flex-wrap:wrap;}
.pc-back{color:var(--muted);text-decoration:none;font-weight:600;}
.pc-title{font-size:26px;margin:0;font-weight:800;color:#111827;}
.small-muted{font-size:13px;color:var(--muted);}

/* Sections + Controls */
.pc-sections{
  display:flex;
  gap:10px;
  margin-bottom:14px;
  flex-wrap:wrap;
}

/* BOTONES DE ARRIBA: blancos, hover/activo negro */
.pc-section-item{
  position:relative;
  padding:8px 16px;
  border-radius:999px;
  border:1px solid #e5e7eb;
  text-decoration:none;
  color:#111827;
  background:#ffffff;
  transition:all .25s ease;
  font-weight:700;
  font-size:14px;
  overflow:hidden;
  z-index:0;
}

.pc-section-item::before{
  content:"";
  position:absolute;
  inset:0;
  width:0;
  height:100%;
  background:#111827;
  border-radius:999px;
  z-index:-1;
  transition:width .25s ease;
}

.pc-section-item:hover{
  color:#ffffff;
}

.pc-section-item:hover::before{
  width:100%;
}

.pc-section-item.active{
  color:#ffffff;
  border-color:#111827;
}

.pc-section-item.active::before{
  width:100%;
  background:#111827;
}

.pc-controls{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px;flex-wrap:wrap;}
.pc-filter-form{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
.pc-filter-form input{padding:8px;border-radius:8px;border:1px solid #e5e7eb;width:110px;}

/* Botones simples Filtrar / Limpiar */
.pc-btn{
  padding:8px 14px;
  border-radius:999px;
  border:1px solid #e5e7eb;
  background:#ffffff;
  font-size:14px;
  font-weight:700;
  cursor:pointer;
  text-decoration:none;
  color:#111827;
  transition:all .2s ease;
}
.pc-btn:hover{
  background:#111827;
  color:#ffffff;
}

/* variantes */
.pc-btn-filter{}
.pc-btn-reset{}

/* Subir: sólo para ajustar que se vea inline con Btn */
.pc-upload-btn{
  text-decoration:none;
}

/* GRID DOCUMENTOS */
.pc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;align-items:start;}

/* Card */
.card{background:var(--bg);border-radius:var(--radius);padding:0;border:1px solid rgba(15,23,42,0.04);box-shadow:var(--card-shadow);overflow:hidden;display:block;text-decoration:none;color:inherit;transition:transform .14s ease,box-shadow .14s ease;position:relative;}
.card:focus-within, .card:hover{transform:translateY(-6px);box-shadow:0 20px 40px rgba(2,6,23,0.08);}

/* overlay link */
.card__link{position:absolute;inset:0;z-index:1;pointer-events:none;}

/* Hero */
.card__hero{padding:14px;display:flex;flex-direction:column;gap:10px;position:relative;z-index:2;}
.card__hero-header{display:flex;justify-content:space-between;align-items:center;gap:8px;}
.pc-doc-badge{display:inline-block;background:rgba(255,255,255,0.95);padding:6px 8px;border-radius:999px;font-weight:800;font-size:12px;color:#0f172a;border:1px solid rgba(15,23,42,0.04);}

/* top actions */
.card .pc-card-top-actions{ position:absolute; top:12px; right:12px; z-index:6; }

/* Media preview */
.pc-hero-media{width:100%;border-radius:10px;overflow:hidden;background:#f8fafc;display:flex;align-items:center;justify-content:center;min-height:160px;max-height:300px;position:relative;}
.pc-hero-media img, .pc-hero-media video{width:100%;height:100%;object-fit:cover;display:block;}
.pc-hero-media video{background:#000;}
.pc-doc-placeholder{display:flex;align-items:center;justify-content:center;padding:18px;background:linear-gradient(180deg,#f8fafc,#ffffff);height:100%;}
.pc-doc-placeholder-inner{display:flex;gap:12px;align-items:center;}
.pc-doc-ext{display:block;font-size:22px;font-weight:900;padding:10px 14px;border-radius:10px;background:#0b1220;color:#fff;}
.pc-doc-fileinfo{max-width:calc(100% - 80px);}
.pc-doc-title-ellipsis{font-weight:700;line-height:1.1;font-size:15px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;text-overflow:ellipsis;}
.pc-doc-meta{font-weight:600;color:var(--muted);font-size:13px;margin-top:6px;}

/* Title */
.card__job-title{margin:0;font-size:1rem;font-weight:800;color:#0b1220;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;text-overflow:ellipsis;}

/* Footer */
.card__footer{display:flex;flex-direction:column;padding:12px 14px;border-top:1px solid rgba(15,23,42,0.03);gap:10px;z-index:2;background:transparent;}
@media(min-width:520px){ .card__footer{flex-direction:row;align-items:center;justify-content:space-between;} }

.card__job-summary{display:flex;align-items:center;gap:12px;}
.pc-small-logo{width:44px;height:44px;border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:transparent;flex:0 0 44px;}

/* circular type badge */
.pc-type-badge{width:44px;height:44px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 6px 18px rgba(2,6,23,0.06);}
.pc-type-doc{background:#1f6feb;}
.pc-type-img{background:#b48f00;}
.pc-type-video{background:#0ea5a4;}
.pc-type-badge svg{display:block;}

/* Job/title */
.card__job .pc-doc-title{margin:0;font-weight:800;font-size:14px;color:#0b1220;}
.card__job .pc-doc-meta{font-weight:600;color:var(--muted);font-size:13px;}

/* Icon-only buttons */
.pc-icon-btn{display:inline-flex;align-items:center;justify-content:center;padding:8px;border-radius:10px;border:none;background:transparent;color:inherit;cursor:pointer;text-decoration:none;z-index:4;}
.pc-icon-btn svg{display:block;}

/* download button (negro) */
.pc-btn-download{background:#111827;color:#fff;padding:8px;border-radius:10px;width:40px;height:40px;display:inline-flex;align-items:center;justify-content:center;}
.pc-btn-download svg{stroke:#fff;width:18px;height:18px;}

/* delete button top-right (rojo) */
.pc-delete-form-inline{margin:0;}
.pc-btn-delete{background:transparent;border-radius:8px;width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;color:#ef4444;border:1px solid rgba(239,68,68,0.12);box-shadow:0 4px 10px rgba(239,68,68,0.06);}

/* Empty */
.pc-empty{grid-column:1/-1;background:#fff;border-radius:12px;padding:20px;border:1px dashed #eee;text-align:center;color:#666;}

/* Responsive tweaks */
@media(max-width:900px){ .pc-grid{grid-template-columns:repeat(2,1fr);} }
@media(max-width:560px){ .pc-grid{grid-template-columns:1fr;} .pc-hero-media{min-height:120px;max-height:220px;} }

/* Focus */
.card:focus-within{outline:3px solid rgba(99,102,241,0.12);}

/* Modal */
.pc-modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:60;}
.pc-modal[aria-hidden="false"]{display:flex;}
.pc-modal-backdrop{position:absolute;inset:0;background:rgba(0,0,0,0.45);}
.pc-modal-panel{position:relative;background:#fff;padding:18px;border-radius:12px;max-width:640px;width:100%;z-index:80;box-shadow:0 20px 50px rgba(0,0,0,0.25);}
.pc-input{width:100%;padding:8px;border-radius:8px;border:1px solid #ddd;margin-bottom:10px;}
.pc-btn-primary{background:#007BFF;color:#fff;padding:8px 12px;border-radius:8px;border:none;cursor:pointer;}
.pc-btn-secondary{background:#eee;padding:8px 12px;border-radius:8px;border:none;cursor:pointer;}

/* ============================= */
/*   BOTÓN SUBIR estilo Btn      */
/* ============================= */
.Btn { 
  display:flex;
  align-items:center;
  justify-content:flex-start;
  width: 46px;
  height: 46px;
  border: none;
  border-radius: 999px;           /* píldora */
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition-duration: .3s;
  box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.199);
  background-color: black;
}

/* plus sign */
.sign {
  width: 100%;
  font-size: 2em;
  color: white;
  transition-duration: .3s;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* text */
.text {
  position: absolute;
  right: 0%;
  width: 0%;
  opacity: 0;
  color: white;
  font-size: 0.9em;
  font-weight: 500;
  transition-duration: .3s;
  white-space: nowrap;
}

/* hover effect on button width */
.Btn:hover {
  width: 190px;              /* ancho suficiente para "Subir Declaración Anual" */
  border-radius: 999px;
  transition-duration: .3s;
}

.Btn:hover .sign {
  width: 30%;
  transition-duration: .3s;
  padding-left: 16px;
}

/* hover effect button's text */
.Btn:hover .text {
  opacity: 1;
  width: 70%;
  transition-duration: .3s;
  padding-right: 16px;
}

/* button click effect*/
.Btn:active {
  transform: translate(2px ,2px);
}
</style>

{{-- JS: delete + click en tarjeta --}}
<script>
(function(){
  'use strict';

  document.addEventListener('DOMContentLoaded', function(){
    // stopPropagation para botones/links de acción
    document.querySelectorAll('.pc-btn-download, .pc-btn-delete, .pc-icon-btn').forEach((el) => {
      el.addEventListener('click', function(e){
        e.stopPropagation();
      });
    });

    // Delete via AJAX
    document.querySelectorAll('.pc-delete-form-inline').forEach(function(form){
      form.addEventListener('submit', function(ev){
        ev.preventDefault();
        if(!confirm('¿Eliminar este documento? Esta acción no se puede deshacer.')) return;

        const url = form.action;
        const token = form.querySelector('input[name="_token"]').value;
        const methodInput = form.querySelector('input[name="_method"]');
        const method = methodInput ? methodInput.value : 'DELETE';

        fetch(url, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
          },
          body: new URLSearchParams({'_method': method})
        }).then(res => {
          if(res.ok){
            const card = form.closest('.pc-doc-card');
            if(card) card.remove();
          } else {
            res.json().then(j => alert(j.message || 'No se pudo eliminar el documento.')).catch(()=>alert('No se pudo eliminar el documento.'));
          }
        }).catch(()=> alert('Error de red al intentar eliminar.'));
      });
    });

    // Abrir preview al click en la tarjeta
    document.querySelectorAll('.pc-doc-card').forEach(card => {
      card.setAttribute('tabindex','0');

      card.addEventListener('keydown', function(e){
        const active = document.activeElement;
        if(active && (active.closest('.pc-company-actions') || active.closest('.pc-card-top-actions'))) return;
        if(e.key === 'Enter' || e.key === ' '){
          const link = card.querySelector('.card__link');
          if(link) { window.open(link.href, '_blank'); e.preventDefault(); }
        }
      });

      card.addEventListener('click', function(e){
        const link = card.querySelector('.card__link');
        if(link) window.open(link.href, '_blank');
      });
    });
  });
})();
</script>
