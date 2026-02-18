{{-- resources/views/partcontable/company.blade.php --}}
@extends('layouts.app')
@section('title', $company->name.' - Parte contable')

@php
  use Illuminate\Support\Str;
  use Illuminate\Support\Facades\Storage;

  // ============================
  //   Configuración de tabs UI
  // ============================
  $pcTabs = [
    'declaracion_anual' => [
      'label'   => 'Declaración Anual',
      'subtabs' => [
        'acuse_anual'       => 'Acuse anual',
        'pago_anual'        => 'Pago anual',
        'declaracion_anual' => 'Declaración anual',
      ],
    ],
    'declaracion_mensual' => [
      'label'   => 'Declaración Mensual',
      'subtabs' => [
        'acuse_mensual'       => 'Acuse mensual',
        'pago_mensual'        => 'Pago mensual',
        'declaracion_mensual' => 'Declaración mensual',
      ],
    ],
    'constancias' => [
      'label'   => 'Constancias / Opiniones',
      'subtabs' => [
        'csf'            => 'Constancia de situación fiscal',
        'opinion_nl'     => 'Opinión estatal Nuevo León',
        'opinion_edomex' => 'Opinión estatal Estado de México',
        '32d_sat'        => '32-D SAT',
        'infonavit'      => 'INFONAVIT',
        'opinion_imss'   => 'Opinión IMSS',
      ],
    ],
    'estados_financieros' => [
      'label'   => 'Estados Financieros',
      'subtabs' => [
        'balance_general'   => 'Balance general',
        'estado_resultados' => 'Estado de resultados',
      ],
    ],
    'isn_3' => [
      'label'   => 'ISN-3%',
      'subtabs' => [
        'pago' => 'Pago',
      ],
    ],
  ];

  $year  = $year  ?? request('year');
  $month = $month ?? request('month');

  $currentSectionKey = $currentSectionKey ?? request('section', 'declaracion_anual');
  if (!isset($pcTabs[$currentSectionKey])) $currentSectionKey = 'declaracion_anual';

  $currentSubtabs = $pcTabs[$currentSectionKey]['subtabs'];

  $currentSubKey = $currentSubKey ?? request('subtipo', array_key_first($currentSubtabs));
  if (!isset($currentSubtabs[$currentSubKey])) $currentSubKey = array_key_first($currentSubtabs);

  $currentSubLabel = $currentSubLabel ?? $currentSubtabs[$currentSubKey];

  $welcomeSessionKey = "pc_welcome_{$company->id}";
  $welcomeData = session($welcomeSessionKey);
  $userName = auth()->user()->name ?? 'Usuario';

  $welcomeCloseKey = "pc_welcome_closed_{$company->id}";

  $ficticioAllowedSections = ['declaracion_anual', 'declaracion_mensual'];
  $ficticioAllowedSubtypes = [
    'acuse_anual','pago_anual','declaracion_anual',
    'acuse_mensual','pago_mensual','declaracion_mensual',
  ];
@endphp

@section('content')

<div class="pc-bg" aria-hidden="true"></div>

{{-- ✅ TOAST HOST (FUERA DEL WRAP para evitar stacking contexts) --}}
<div class="pc-toast-wrap" id="pcToastWrap" aria-live="polite" aria-atomic="true"></div>

<div class="pc-wrap">

  <div class="pc-header">
    <a href="{{ route('partcontable.index') }}" class="pc-back">← Volver</a>
    <a href="{{ route('partcontable.activity.all') }}" class="pc-btn">Bitácora</a>
    <h1 class="pc-title">{{ $company->name }}</h1>
  </div>

  @if(!empty($welcomeData))
    <div class="pc-welcome" id="pcWelcome" data-close-key="{{ $welcomeCloseKey }}">
      <div class="pc-welcome-left">
        <div class="pc-welcome-title">
          Bienvenido, accediste como <span class="pc-welcome-user">{{ $userName }}</span>
        </div>
        <div class="pc-welcome-sub">
          Acceso protegido por NIP · Tus acciones quedan registradas.
        </div>
      </div>
      <button type="button" class="pc-welcome-close" id="pcWelcomeClose" aria-label="Cerrar bienvenida">✕</button>
    </div>
  @endif

  @if(session('success'))
    <div class="pc-flash pc-flash-success" id="pcFlashSuccess">{{ session('success') }}</div>
  @endif
  @if(session('warning'))
    <div class="pc-flash pc-flash-warning" id="pcFlashWarning">{{ session('warning') }}</div>
  @endif

  {{-- Tabs principales --}}
  <nav class="pc-sections pc-main-tabs" aria-label="Secciones principales">
    @foreach($pcTabs as $key => $conf)
      @php
        $url = route('partcontable.company', $company->slug)
              . '?section='.$key
              . '&subtipo='.array_key_first($conf['subtabs'])
              . ($year ? '&year='.$year : '')
              . ($month ? '&month='.$month : '');
      @endphp
      <a href="{{ $url }}" class="pc-section-item {{ $currentSectionKey === $key ? 'active' : '' }}">
        {{ $conf['label'] }}
      </a>
    @endforeach
  </nav>

  {{-- Sub-tabs --}}
  <nav class="pc-subtabs" aria-label="Subtipo de documentos">
    @foreach($currentSubtabs as $subKey => $label)
      @php
        $url = route('partcontable.company', $company->slug)
              . '?section='.$currentSectionKey
              . '&subtipo='.$subKey
              . ($year ? '&year='.$year : '')
              . ($month ? '&month='.$month : '');
      @endphp
      <a href="{{ $url }}" class="pc-subtab-item {{ $currentSubKey === $subKey ? 'active' : '' }}">
        {{ $label }}
      </a>
    @endforeach
  </nav>

  {{-- Controles --}}
  <div class="pc-controls">
    <form method="GET" id="pc-filter-form"
          action="{{ route('partcontable.company', $company->slug) }}"
          class="pc-filter-form" role="search">

      <input type="hidden" name="section" value="{{ $currentSectionKey }}">
      <input type="hidden" name="subtipo" value="{{ $currentSubKey }}">

      <input type="number" name="year" placeholder="Año" min="2000" max="2099" value="{{ $year ?? '' }}">
      <input type="number" name="month" placeholder="Mes" min="1" max="12" value="{{ $month ?? '' }}">

      <button type="submit" class="pc-btn pc-btn-filter">Filtrar</button>
      <a href="{{ route('partcontable.company', $company->slug) . '?section='.$currentSectionKey.'&subtipo='.$currentSubKey }}" class="pc-btn pc-btn-reset">
        Limpiar
      </a>
    </form>

    <div>
      <a href="{{ route('partcontable.documents.create', $company->slug) }}?section={{ $currentSectionKey }}&subtipo={{ $currentSubKey }}"
         class="Btn pc-upload-btn"
         aria-label="Subir {{ $currentSubLabel }}">
        <div class="sign">+</div>
        <div class="text">Subir {{ $currentSubLabel }}</div>
      </a>
    </div>
  </div>

  {{-- Grid documentos --}}
  <div class="pc-grid" aria-live="polite">
    @forelse($documents as $doc)
      @php
        $filename = $doc->filename ?? basename($doc->file_path ?? '');
        if (!Str::contains($filename, '.')) {
          $extFromPath = pathinfo($doc->file_path ?? '', PATHINFO_EXTENSION);
          if ($extFromPath) $filename .= '.' . $extFromPath;
        }

        $displayUrl = null;
        $mime = $doc->mime_type ?? null;

        // ✅ Solo cuenta como principal si existe en storage (evita palomitas falsas)
        $hasMainStoredFile = (!empty($doc->file_path) && Storage::disk('public')->exists($doc->file_path));

        if ($hasMainStoredFile) {
          $displayUrl = Storage::disk('public')->url($doc->file_path);
          if (!$mime) {
            try { $mime = Storage::disk('public')->mimeType($doc->file_path); }
            catch (\Throwable $_) { $mime = $doc->mime_type ?? null; }
          }
        } else {
          $displayUrl = (!empty($doc->url) && Str::startsWith($doc->url, ['http://','https://']))
            ? $doc->url
            : ($doc->url ?? '');
        }

        $dateLabel = $doc->date
          ? \Carbon\Carbon::parse($doc->date)->format('d M Y')
          : \Carbon\Carbon::parse($doc->created_at)->format('d M Y');

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $isImage = $mime ? Str::startsWith($mime, 'image/') : in_array($ext, ['jpg','jpeg','png','gif','webp','svg'], true);
        $isVideo = $mime ? Str::startsWith($mime, 'video/') : in_array($ext, ['mp4','mov','webm','mkv'], true);
        $isPdf   = $ext === 'pdf';

        $allowFicticioHere = in_array($currentSectionKey, $ficticioAllowedSections, true)
          && in_array($currentSubKey, $ficticioAllowedSubtypes, true);

        $hasFicticio = !empty($doc->ficticio_file_path);

        // ✅ Palomita SOLO si hay DOBLE ARCHIVO
        $hasDoubleFile = $hasMainStoredFile && $hasFicticio;
      @endphp

      <article class="card pc-doc-card" aria-labelledby="doc-{{ $doc->id }}" data-id="{{ $doc->id }}" tabindex="0">
        <a class="card__link" href="{{ route('partcontable.documents.preview', $doc) }}" target="_blank" rel="noopener"></a>

        <div class="card__hero">
          <header class="card__hero-header">
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
              <span class="pc-doc-badge {{ $isPdf ? 'pc-doc-badge-pdf' : '' }}">
                {{ strtoupper($ext ?: ($doc->file_type ?? 'FILE')) }}
              </span>

              @if($hasDoubleFile)
                <button type="button"
                        class="pc-doc-badge pc-doc-badge-ok pc-badge-info"
                        data-badge-info="1"
                        aria-label="Documento completo"
                        title="Documento completo (principal + ficticio)">
                  ✓ COMPLETO
                </button>
              @endif

              @if($hasFicticio)
                <span class="pc-doc-badge pc-doc-badge-ficticio" title="Tiene ficticio">FICTICIO</span>
              @endif
            </div>

            <div class="pc-card-top-actions" role="group" aria-label="Acciones del documento">
              {{-- ✅ Botón subir ficticio SOLO si NO existe ficticio --}}
              @if($allowFicticioHere && !$hasFicticio)
                <button
                  type="button"
                  class="pc-icon-btn pc-btn-ficticio"
                  data-ficticio-trigger="{{ $doc->id }}"
                  data-doc-title="{{ $doc->title }}"
                  aria-label="Subir ficticio para {{ $doc->title }}"
                  title="Subir ficticio"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <path d="M14 2v6h6"></path>
                    <path d="M12 18v-6"></path>
                    <path d="M9 15h6"></path>
                  </svg>
                </button>
              @endif

              <form method="POST" action="{{ route('partcontable.documents.destroy', $doc) }}" class="pc-delete-form-inline" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="pc-icon-btn pc-btn-delete" aria-label="Eliminar {{ $doc->title }}" title="Eliminar">
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

          <div class="pc-hero-media" role="img" aria-label="{{ $doc->title }}">
            @php $url = $displayUrl ?? ''; @endphp

            @if($isImage && $url)
              <img src="{{ $url }}" alt="{{ $doc->title }}" style="max-width:100%;">
            @elseif($isVideo && $url)
              <video controls style="max-width:100%;">
                <source src="{{ $url }}" type="{{ $mime ?: $doc->mime_type }}">
                Tu navegador no soporta video.
              </video>
            @elseif($isPdf && $url)
              <div class="pc-doc-placeholder" title="{{ $doc->title }}">
                <div class="pc-doc-placeholder-inner">
                  <strong class="pc-doc-ext pc-doc-ext-pdf">PDF</strong>
                  <div class="pc-doc-fileinfo">
                    <div class="pc-doc-title-ellipsis">{{ \Illuminate\Support\Str::limit($doc->title, 80) }}</div>
                    <div class="pc-doc-meta small-muted">
                      {{ $dateLabel }} • {{ $doc->subtype?->name ?? $currentSubLabel }}
                    </div>
                  </div>
                </div>
              </div>
            @else
              <div class="pc-doc-placeholder" title="{{ $doc->title }}">
                <div class="pc-doc-placeholder-inner">
                  <strong class="pc-doc-ext">{{ strtoupper($ext ?: 'FILE') }}</strong>
                  <div class="pc-doc-fileinfo">
                    <div class="pc-doc-title-ellipsis">{{ \Illuminate\Support\Str::limit($doc->title, 80) }}</div>
                    <div class="pc-doc-meta small-muted">
                      {{ $dateLabel }} • {{ $doc->subtype?->name ?? $currentSubLabel }}
                    </div>
                  </div>
                </div>
              </div>
            @endif
          </div>

          <p id="doc-{{ $doc->id }}" class="card__job-title pc-doc-hero-title">
            {{ \Illuminate\Support\Str::limit($doc->title, 80) }}
          </p>
        </div>

        <footer class="card__footer">
          <div class="card__job-summary">
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
                <span class="pc-type-badge {{ $isPdf ? 'pc-type-doc-pdf' : 'pc-type-doc' }}" aria-hidden="true">
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
              <div class="pc-doc-meta small-muted">
                {{ $dateLabel }} • {{ $doc->subtype?->name ?? $currentSubLabel }}
              </div>
            </div>
          </div>

          <div class="pc-company-actions">
            <a class="pc-icon-btn pc-btn-download"
               href="{{ route('partcontable.documents.download', $doc) }}"
               aria-label="Descargar {{ $doc->title }}"
               title="Descargar">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
              </svg>
            </a>

            @if(!empty($doc->ficticio_file_path))
              <a class="pc-icon-btn pc-btn-download pc-btn-download-ficticio"
                 href="{{ route('partcontable.documents.ficticio.download', $doc) }}"
                 aria-label="Descargar ficticio de {{ $doc->title }}"
                 title="Descargar ficticio">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                  <path d="M14 2v6h6"></path>
                  <path d="M12 18v-6"></path>
                  <path d="M9 15h6"></path>
                </svg>
              </a>
            @endif
          </div>
        </footer>
      </article>
    @empty
      <div class="pc-empty">No hay documentos en esta sección.</div>
    @endforelse
  </div>

  <div class="pc-pagination" style="margin-top:18px;">
    {{ $documents->withQueryString()->links() }}
  </div>
</div>

{{-- Modal upload original (no lo toco) --}}
<div class="pc-modal" id="pcUploadModal" aria-hidden="true" aria-labelledby="pcUploadTitle" role="dialog">
  <div class="pc-modal-backdrop" id="pcModalClose" data-action="close"></div>
  <div class="pc-modal-panel" role="document">
    <h3 id="pcUploadTitle">Subir documento - <span id="pcModalSectionName">{{ $currentSubLabel }}</span></h3>

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

{{-- ✅ MODAL FICTICIO PRO --}}
<div class="pc-modal" id="pcFicticioModal" aria-hidden="true" aria-labelledby="pcFicticioTitle" role="dialog">
  <div class="pc-modal-backdrop" data-fm-close="1"></div>

  <div class="pc-modal-panel pc-modal-panel-pro" role="document">
    <div class="pc-modal-head-pro">
      <div class="pc-modal-head-left">
        <div class="pc-modal-kicker">Archivo adicional</div>
        <h3 id="pcFicticioTitle" class="pc-modal-title-pro">Subir ficticio</h3>
        <div class="pc-modal-sub-pro">
          Documento: <strong id="pcFicticioDocTitle">—</strong>
        </div>
      </div>
      <button type="button" class="pc-modal-x-pro" data-fm-close="1" aria-label="Cerrar">✕</button>
    </div>

    <form id="pcFicticioForm" enctype="multipart/form-data" class="pc-modal-form-pro">
      @csrf
      <input type="hidden" id="pcFicticioDocId" value="">

      {{-- ✅ Input REAL oculto (ya no está encima del dropzone) --}}
      <input
        id="pcFicticioFile"
        type="file"
        accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.svg,.mp4,.mov,.doc,.docx,.xls,.xlsx"
        style="display:none"
      >

      <div class="pc-dropzone" id="pcDropzone" role="button" tabindex="0" aria-label="Seleccionar archivo ficticio">
        <div class="pc-drop-ic" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="17 8 12 3 7 8"></polyline>
            <line x1="12" y1="3" x2="12" y2="15"></line>
          </svg>
        </div>

        <div class="pc-drop-txt">
          <div class="pc-drop-title">Arrastra tu archivo aquí o <span class="pc-drop-link">selecciónalo</span></div>
          <div class="pc-drop-hint">PDF, imagen, video u Office.</div>
          <div class="pc-file-name" id="pcFileName" style="display:none;"></div>
        </div>
      </div>

      <div class="pc-modal-foot-pro">
        <button type="button" class="pc-btn-ghost" data-fm-close="1" id="pcFicticioCancel">Cancelar</button>

        <button type="submit" class="pc-btn-solid" id="pcFicticioSubmit">
          Subir
        </button>

        <span class="pc-mini-loading" id="pcFicticioLoading" style="display:none;">Subiendo…</span>
      </div>
    </form>
  </div>
</div>

@endsection

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
:root{
  --bg:#ffffff;
  --muted:#6b7280;
  --accent1:linear-gradient(90deg,#6b9cff,#7ee7c6);
  --card-shadow: 0 12px 30px rgba(20,24,40,0.06);
  --radius:12px;
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
}

.pc-bg{
  position: fixed; inset: 0; z-index: 0; pointer-events: none;
  background:
    radial-gradient(1200px 420px at 50% 0%,
      rgba(191, 219, 254, 0.95) 0%,
      rgba(191, 219, 254, 0.55) 35%,
      rgba(255,255,255,0.0) 72%),
    linear-gradient(180deg,
      #bfdbfe 0%,
      #dbeafe 24%,
      #eff6ff 48%,
      #ffffff 78%);
  filter: saturate(1.05);
}
.pc-bg::after{
  content:"";
  position:absolute; inset:-20%;
  background: repeating-radial-gradient(circle at 20% 10%, rgba(0,0,0,0.02) 0 1px, rgba(0,0,0,0.00) 1px 6px);
  opacity: 0.15;
  transform: rotate(2deg);
}

.pc-wrap{ padding:18px; position: relative; z-index: 2; color:#0b1220; }

.pc-flash{
  padding:10px 12px; border-radius:12px;
  border:1px solid rgba(15,23,42,0.08);
  background: rgba(255,255,255,0.92);
  box-shadow: 0 10px 22px rgba(2,6,23,0.06);
  margin: 10px 0 14px;
  font-weight:700; font-size:13px;
}
.pc-flash-success{ color:#065f46; border-color:rgba(16,185,129,.25); }
.pc-flash-warning{ color:#92400e; border-color:rgba(245,158,11,.25); }

.pc-welcome{
  display:flex; align-items:flex-start; justify-content:space-between; gap:12px;
  padding:12px 14px; border-radius:14px;
  border:1px solid rgba(15,23,42,0.08);
  background: rgba(255,255,255,0.92);
  box-shadow: 0 10px 22px rgba(2,6,23,0.08);
  backdrop-filter: blur(6px);
  margin: 10px 0 14px;
  animation: pcWelcomeIn .22s ease both;
}
@keyframes pcWelcomeIn{
  from{opacity:0; transform: translateY(-6px) scale(.99);}
  to{opacity:1; transform: translateY(0) scale(1);}
}
.pc-welcome-title{ font-size: 13px; font-weight: 650; color:#111827; }
.pc-welcome-user{ font-weight: 750; }
.pc-welcome-sub{ font-size: 12px; color: var(--muted); margin-top: 2px; }
.pc-welcome-close{
  border:none; background:transparent; cursor:pointer; color:#6b7280;
  padding:6px 8px; border-radius:10px;
  transition: background .15s ease, color .15s ease, transform .12s ease;
}
.pc-welcome-close:hover{ background:#f3f4f6; color:#111827; transform: translateY(-1px); }

.pc-header{display:flex;align-items:center;gap:12px;margin-bottom:12px;flex-wrap:wrap;}
.pc-back{color:var(--muted);text-decoration:none;font-weight:600;}
.pc-title{font-size:26px;margin:0;font-weight:800;color:#111827;}
.small-muted{font-size:13px;color:var(--muted);}

.pc-sections{display:flex;gap:10px;margin-bottom:10px;flex-wrap:wrap;}
.pc-section-item{
  position:relative; padding:8px 16px; border-radius:999px; border:1px solid #e5e7eb;
  text-decoration:none; color:#111827; background:#ffffff; transition:all .25s ease;
  font-weight:700; font-size:14px; overflow:hidden; z-index:0;
}
.pc-section-item::before{
  content:""; position:absolute; inset:0; width:0; height:100%;
  background:#111827; border-radius:999px; z-index:-1; transition:width .25s ease;
}
.pc-section-item:hover{ color:#ffffff; }
.pc-section-item:hover::before{ width:100%; }
.pc-section-item.active{ color:#ffffff; border-color:#111827; }
.pc-section-item.active::before{ width:100%; background:#111827; }

.pc-subtabs{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;}
.pc-subtab-item{
  padding:6px 14px; border-radius:999px; border:1px solid #e5e7eb; background:#f9fafb;
  font-size:13px; font-weight:600; text-decoration:none; color:#374151; transition:all .18s ease;
}
.pc-subtab-item:hover{ background:#e5e7eb; }
.pc-subtab-item.active{ background:#111827; color:#ffffff; border-color:#111827; }

.pc-controls{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px;flex-wrap:wrap;}
.pc-filter-form{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
.pc-filter-form input{padding:8px;border-radius:8px;border:1px solid #e5e7eb;width:110px;}
.pc-btn{
  padding:8px 14px;border-radius:999px;border:1px solid #e5e7eb;background:#ffffff;
  font-size:14px;font-weight:700;cursor:pointer;text-decoration:none;color:#111827;
  transition:all .2s ease;
}
.pc-btn:hover{ background:#111827;color:#ffffff; }
.pc-upload-btn{ text-decoration:none; }

.pc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;align-items:start;}

.card{
  background:var(--bg);
  border-radius:var(--radius);
  padding:0;
  border:1px solid rgba(15,23,42,0.04);
  box-shadow:var(--card-shadow);
  overflow:hidden;
  display:block;
  text-decoration:none;
  color:inherit;
  transition:transform .14s ease,box-shadow .14s ease;
  position:relative;
}
.card:focus-within, .card:hover{
  transform:translateY(-6px);
  box-shadow:0 20px 40px rgba(2,6,23,0.08);
}
.card__link{position:absolute;inset:0;z-index:1;pointer-events:none;}

.card__hero{padding:14px;display:flex;flex-direction:column;gap:10px;position:relative;z-index:2;}
.card__hero-header{display:flex;justify-content:space-between;align-items:center;gap:8px;}

.pc-doc-badge{
  display:inline-block;
  background:rgba(255,255,255,0.95);
  padding:6px 8px;
  border-radius:999px;
  font-weight:800;
  font-size:12px;
  color:#0f172a;
  border:1px solid rgba(15,23,42,0.04);
}
.pc-doc-badge-pdf{ background:#fee2e2; color:#b91c1c; border-color:#fecaca; }
.pc-doc-badge-ficticio{ background:#e0e7ff; color:#3730a3; border-color:#c7d2fe; }

.pc-doc-badge-ok{
  background: rgba(16,185,129,.12);
  color:#065f46;
  border-color: rgba(16,185,129,.28);
  padding:6px 10px;
  font-weight:950;
  cursor:pointer;
}
.pc-doc-badge-ok:hover{ background: rgba(16,185,129,.18); }

.card .pc-card-top-actions{ position:absolute; top:12px; right:12px; z-index:6; display:flex; gap:8px; }

.pc-hero-media{
  width:100%; border-radius:10px; overflow:hidden; background:#f8fafc;
  display:flex; align-items:center; justify-content:center;
  min-height:160px; max-height:300px; position:relative;
}
.pc-hero-media img, .pc-hero-media video{width:100%;height:100%;object-fit:cover;display:block;}
.pc-hero-media video{background:#000;}
.pc-doc-placeholder{display:flex;align-items:center;justify-content:center;padding:18px;background:linear-gradient(180deg,#f8fafc,#ffffff);height:100%;}
.pc-doc-placeholder-inner{display:flex;gap:12px;align-items:center;}
.pc-doc-ext{
  display:block; font-size:22px; font-weight:900; padding:10px 14px; border-radius:10px;
  background:#0b1220; color:#fff;
}
.pc-doc-ext-pdf{ background:#dc2626; }
.pc-doc-fileinfo{max-width:calc(100% - 80px);}
.pc-doc-title-ellipsis{font-weight:700;line-height:1.1;font-size:15px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;text-overflow:ellipsis;}
.pc-doc-meta{font-weight:600;color:var(--muted);font-size:13px;margin-top:6px;}

.card__job-title{margin:0;font-size:1rem;font-weight:800;color:#0b1220;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;text-overflow:ellipsis;}

.card__footer{display:flex;flex-direction:column;padding:12px 14px;border-top:1px solid rgba(15,23,42,0.03);gap:10px;z-index:2;background:transparent;}
@media(min-width:520px){ .card__footer{flex-direction:row;align-items:center;justify-content:space-between;} }

.card__job-summary{display:flex;align-items:center;gap:12px;}
.pc-small-logo{width:44px;height:44px;border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:transparent;flex:0 0 44px;}
.pc-type-badge{width:44px;height:44px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 6px 18px rgba(2,6,23,0.06);}
.pc-type-doc{background:#1f6feb;}
.pc-type-img{background:#b48f00;}
.pc-type-video{background:#0ea5a4;}
.pc-type-doc-pdf{background:#dc2626;}
.pc-type-badge svg{display:block;}

.card__job .pc-doc-title{margin:0;font-weight:800;font-size:14px;color:#0b1220;}
.card__job .pc-doc-meta{font-weight:600;color:var(--muted);font-size:13px;}

.pc-icon-btn{display:inline-flex;align-items:center;justify-content:center;padding:8px;border-radius:10px;border:none;background:transparent;color:inherit;cursor:pointer;text-decoration:none;z-index:4;}
.pc-icon-btn svg{display:block;}

.pc-btn-download{background:#111827;color:#fff;padding:8px;border-radius:10px;width:40px;height:40px;display:inline-flex;align-items:center;justify-content:center;}
.pc-btn-download svg{stroke:#fff;width:18px;height:18px;}
.pc-btn-download-ficticio{ background:#3730a3; }

.pc-delete-form-inline{margin:0;}
.pc-btn-delete{
  background:transparent;border-radius:8px;width:36px;height:36px;
  display:inline-flex;align-items:center;justify-content:center;
  color:#ef4444;border:1px solid rgba(239,68,68,0.12);
  box-shadow:0 4px 10px rgba(239,68,68,0.06);
}
.pc-btn-ficticio{
  width:36px;height:36px;
  border:1px solid rgba(99,102,241,.18);
  box-shadow:0 4px 10px rgba(99,102,241,.10);
  color:#4f46e5;
}
.pc-btn-ficticio:hover{ background: rgba(99,102,241,.08); }

.pc-empty{grid-column:1/-1;background:#fff;border-radius:12px;padding:20px;border:1px dashed #eee;text-align:center;color:#666;}

@media(max-width:900px){ .pc-grid{grid-template-columns:repeat(2,1fr);} }
@media(max-width:560px){ .pc-grid{grid-template-columns:1fr;} .pc-hero-media{min-height:120px;max-height:220px;} }

/* ✅ MODAL z-index alto (y toast aún más alto) */
.pc-modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:50000;}
.pc-modal[aria-hidden="false"]{display:flex;}
.pc-modal-backdrop{position:absolute;inset:0;background:rgba(0,0,0,0.45);z-index:50001;}
.pc-modal-panel{position:relative;z-index:50002;}

.pc-modal-panel{
  background:#fff;padding:18px;border-radius:12px;max-width:640px;width:100%;
  box-shadow:0 20px 50px rgba(0,0,0,0.25);
}
.pc-input{width:100%;padding:8px;border-radius:8px;border:1px solid #ddd;margin-bottom:10px;}
.pc-btn-primary{background:#007BFF;color:#fff;padding:8px 12px;border-radius:8px;border:none;cursor:pointer;}
.pc-btn-secondary{background:#eee;padding:8px 12px;border-radius:8px;border:none;cursor:pointer;}

.Btn{
  display:flex; align-items:center; justify-content:flex-start;
  width: 46px; height: 46px;
  border: none; border-radius: 999px;
  cursor: pointer; position: relative; overflow: hidden;
  transition-duration: .3s;
  box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.199);
  background-color: black;
}
.sign{
  width: 100%;
  font-size: 2em;
  color: white;
  transition-duration: .3s;
  display: flex;
  align-items: center;
  justify-content: center;
}
.text{
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
.Btn:hover{ width: 190px; border-radius: 999px; }
.Btn:hover .sign{ width: 30%; transition-duration: .3s; padding-left: 16px; }
.Btn:hover .text{ opacity: 1; width: 70%; transition-duration: .3s; padding-right: 16px; }
.Btn:active{ transform: translate(2px ,2px); }

/* ✅ TOAST: arriba-derecha y SIEMPRE arriba del modal */
.pc-toast-wrap{
  position:fixed;
  top:16px;
  right:16px;
  z-index:60000; /* arriba del modal/backdrop */
  display:flex;
  flex-direction:column;
  gap:10px;
}
@media(max-width:640px){
  .pc-toast-wrap{ left:12px; right:12px; top:12px; }
}
.pc-toast{
  background:#fff;
  border:1px solid rgba(15,23,42,0.08);
  border-radius:14px;
  box-shadow:0 18px 50px rgba(16,24,40,.16);
  padding:10px 12px;
  display:flex;
  gap:10px;
  align-items:flex-start;
  transform: translateY(-6px);
  opacity:0;
  pointer-events:none;
  transition: transform .18s ease, opacity .18s ease;
}
.pc-toast.show{
  transform: translateY(0);
  opacity:1;
  pointer-events:auto;
}
.pc-toast .ic{
  width:30px;height:30px;
  border-radius:10px;
  display:grid;place-items:center;
  background:rgba(16,185,129,.14);
  color:#065f46;
  font-weight:900;
  flex:0 0 30px;
}
.pc-toast.info .ic{ background: rgba(59,130,246,.14); color:#1d4ed8; }
.pc-toast.err .ic{ background:rgba(239,68,68,.14); color:#991b1b; }
.pc-toast .twrap{ flex:1; }
.pc-toast .t1{ font-weight:900; color:#0f172a; font-size:13px; line-height:1.15; }
.pc-toast .t2{ color:#667085; font-size:12px; margin-top:2px; }
.pc-toast .x{
  border:none;
  background:transparent;
  cursor:pointer;
  padding:6px;
  border-radius:10px;
  color:#667085;
}
.pc-toast .x:hover{ background:#f2f4f7; color:#0f172a; }

/* ✅ SweetAlert2 minimal */
.swal2-popup{
  border-radius:18px !important;
  border:1px solid rgba(15,23,42,0.08) !important;
  box-shadow:0 28px 80px rgba(16,24,40,.18) !important;
}
.swal2-title{ font-weight:900 !important; }
.swal2-html-container{ color:#475467 !important; }
.swal2-confirm, .swal2-cancel{
  border-radius:999px !important;
  padding:10px 16px !important;
  font-weight:900 !important;
}

/* ===========================
   ✅ MODAL PRO (FICTICIO)
   =========================== */
.pc-modal-panel-pro{
  max-width: 720px;
  border-radius: 16px;
  padding: 16px;
  border: 1px solid rgba(15,23,42,0.08);
  background: rgba(255,255,255,0.98);
  backdrop-filter: blur(8px);
}
.pc-modal-head-pro{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:12px;
  padding-bottom: 10px;
  border-bottom: 1px solid rgba(15,23,42,0.06);
}
.pc-modal-kicker{
  font-size: 11px;
  font-weight: 900;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: #64748b;
}
.pc-modal-title-pro{
  margin: 2px 0 0;
  font-size: 18px;
  font-weight: 950;
  color:#0b1220;
}
.pc-modal-sub-pro{
  margin-top: 6px;
  font-size: 12px;
  color:#667085;
  font-weight: 700;
}
.pc-modal-x-pro{
  border:none;
  background: transparent;
  cursor: pointer;
  color:#6b7280;
  padding:6px 10px;
  border-radius: 12px;
  transition: background .15s ease, color .15s ease, transform .12s ease;
}
.pc-modal-x-pro:hover{ background:#f3f4f6; color:#111827; transform: translateY(-1px); }

.pc-modal-form-pro{ margin-top: 12px; }

.pc-dropzone{
  position:relative;
  display:flex;
  gap:12px;
  align-items:flex-start;
  padding:14px;
  border-radius: 14px;
  border: 1px dashed rgba(15,23,42,0.18);
  background: linear-gradient(180deg, rgba(248,250,252,0.9), rgba(255,255,255,1));
  transition: border-color .15s ease, transform .15s ease, box-shadow .15s ease;
  cursor:pointer;
}
.pc-dropzone:hover{
  border-color: rgba(99,102,241,.45);
  box-shadow: 0 18px 45px rgba(2,6,23,0.08);
  transform: translateY(-1px);
}
.pc-dropzone:focus-visible{
  outline:none;
  box-shadow: 0 0 0 4px rgba(59,130,246,.18), 0 18px 45px rgba(2,6,23,0.08);
  border-color: rgba(59,130,246,.55);
}
.pc-drop-ic{
  width:38px;height:38px;
  border-radius: 12px;
  display:grid;place-items:center;
  background: rgba(99,102,241,.10);
  color:#4f46e5;
  flex: 0 0 38px;
}
.pc-drop-title{ font-weight: 900; color:#0b1220; font-size: 13px; }
.pc-drop-link{ text-decoration: underline; text-underline-offset: 3px; }
.pc-drop-hint{ margin-top: 4px; font-size: 12px; color:#667085; font-weight: 650; }
.pc-file-name{
  margin-top: 8px;
  font-size: 12px;
  font-weight: 900;
  color:#065f46;
  background: rgba(16,185,129,.12);
  border: 1px solid rgba(16,185,129,.22);
  padding: 6px 8px;
  border-radius: 999px;
  display: inline-block;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.pc-modal-foot-pro{
  display:flex; gap:10px; align-items:center; justify-content:flex-end; margin-top: 12px;
}
.pc-btn-ghost{
  border: 1px solid rgba(15,23,42,0.10);
  background: #ffffff;
  color:#0b1220;
  border-radius: 999px;
  padding: 10px 14px;
  font-weight: 900;
  cursor: pointer;
  transition: background .15s ease, transform .12s ease, border-color .15s ease;
}
.pc-btn-ghost:hover{ background:#f8fafc; border-color: rgba(15,23,42,0.16); transform: translateY(-1px); }

.pc-btn-solid{
  border: none;
  background: #111827;
  color:#fff;
  border-radius: 999px;
  padding: 10px 16px;
  font-weight: 950;
  cursor: pointer;
  transition: transform .12s ease, box-shadow .15s ease, background .15s ease;
}
.pc-btn-solid:hover{ transform: translateY(-1px); box-shadow: 0 14px 30px rgba(2,6,23,0.18); background:#0b1220; }

.pc-mini-loading{font-size:12px;color:#475467;font-weight:900;}
</style>

<script>
(function(){
  'use strict';

  const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  if (window.__pcCompanyFicticioBound) return;
  window.__pcCompanyFicticioBound = true;

  function showToast(type, title, message){
    const wrap = document.getElementById('pcToastWrap');
    if(!wrap) return;

    const t = document.createElement('div');
    const isErr  = (type === 'err');
    const isInfo = (type === 'info');

    t.className = 'pc-toast' + (isErr ? ' err' : (isInfo ? ' info' : ''));
    t.innerHTML = `
      <div class="ic">${isErr ? '!' : (isInfo ? 'i' : '✓')}</div>
      <div class="twrap">
        <div class="t1"></div>
        <div class="t2"></div>
      </div>
      <button class="x" type="button" aria-label="Cerrar">✕</button>
    `;
    t.querySelector('.t1').textContent = title || (isErr ? 'Error' : (isInfo ? 'Info' : 'Listo'));
    t.querySelector('.t2').textContent = message || '';
    wrap.appendChild(t);

    requestAnimationFrame(() => t.classList.add('show'));

    const kill = () => {
      t.classList.remove('show');
      setTimeout(() => t.remove(), 220);
    };

    t.querySelector('.x').addEventListener('click', kill);
    setTimeout(kill, isInfo ? 3400 : 2800);
  }

  async function safeJson(res){
    const txt = await res.text().catch(()=> '');
    try { return { json: JSON.parse(txt), text: txt }; }
    catch(e){ return { json: null, text: txt }; }
  }

  document.addEventListener('DOMContentLoaded', function(){

    // Flash -> toast
    const fs = document.getElementById('pcFlashSuccess');
    const fw = document.getElementById('pcFlashWarning');
    if (fs && fs.textContent.trim()) showToast('ok', 'Guardado', fs.textContent.trim());
    if (fw && fw.textContent.trim()) showToast('err', 'Aviso', fw.textContent.trim());

    // Welcome persist
    const welcome = document.getElementById('pcWelcome');
    const closeBtn = document.getElementById('pcWelcomeClose');
    if (welcome) {
      const key = welcome.getAttribute('data-close-key') || 'pc_welcome_closed_global';
      const closed = localStorage.getItem(key);
      if (closed === '1') welcome.style.display = 'none';
      if (closeBtn) {
        closeBtn.addEventListener('click', function(){
          localStorage.setItem(key, '1');
          welcome.style.display = 'none';
        });
      }
    }

    // Delete AJAX con SweetAlert
    document.querySelectorAll('.pc-delete-form-inline').forEach(function(form){
      form.addEventListener('submit', async function(ev){
        ev.preventDefault();

        const result = await Swal.fire({
          title: '¿Eliminar documento?',
          text: 'Esta acción no se puede deshacer.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Sí, eliminar',
          cancelButtonText: 'Cancelar',
          reverseButtons: true,
          focusCancel: true,
        });

        if(!result.isConfirmed) return;

        const url = form.action;
        const token = form.querySelector('input[name="_token"]').value;
        const methodInput = form.querySelector('input[name="_method"]');
        const method = methodInput ? methodInput.value : 'DELETE';

        try{
          const res = await fetch(url, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': token,
              'X-Requested-With': 'XMLHttpRequest',
              'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            credentials: 'same-origin',
            body: new URLSearchParams({'_method': method})
          });

          if(res.ok){
            const card = form.closest('.pc-doc-card');
            if(card) card.remove();
            showToast('ok','Eliminado','Documento eliminado correctamente.');
          } else {
            const { json } = await safeJson(res);
            showToast('err','No se pudo', (json && json.message) ? json.message : 'No se pudo eliminar el documento.');
          }
        }catch(e){
          showToast('err','Error','Error de red al intentar eliminar.');
        }
      });
    });

    // Click tarjeta abre preview
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

      card.addEventListener('click', function(){
        const link = card.querySelector('.card__link');
        if(link) window.open(link.href, '_blank');
      });
    });

    // =========================
    // MODAL FICTICIO
    // =========================
    const fm         = document.getElementById('pcFicticioModal');
    const fmDocTitle = document.getElementById('pcFicticioDocTitle');
    const fmDocId    = document.getElementById('pcFicticioDocId');
    const fmFile     = document.getElementById('pcFicticioFile');
    const fmForm     = document.getElementById('pcFicticioForm');
    const fmLoading  = document.getElementById('pcFicticioLoading');
    const fmSubmit   = document.getElementById('pcFicticioSubmit');
    const dz         = document.getElementById('pcDropzone');
    const fileName   = document.getElementById('pcFileName');

    let isOpening = false;

    function refreshFileName(){
      const f = fmFile && fmFile.files && fmFile.files[0];
      if(!fileName) return;
      if(f){
        fileName.textContent = 'Seleccionado: ' + f.name;
        fileName.style.display = 'inline-block';
      } else {
        fileName.style.display = 'none';
        fileName.textContent = '';
      }
    }

    function openFicticioModal(docId, title){
      if(!fm || isOpening) return;
      isOpening = true;

      fmDocId.value = docId || '';
      fmDocTitle.textContent = title || '—';

      if(fmFile) fmFile.value = '';
      refreshFileName();

      fm.setAttribute('aria-hidden', 'false');

      setTimeout(() => {
        isOpening = false;
        if(dz) dz.focus();
      }, 80);
    }

    function closeFicticioModal(){
      if(!fm) return;
      fm.setAttribute('aria-hidden', 'true');
      if(fmFile) fmFile.value = '';
      refreshFileName();
      fmDocId.value = '';
    }

    // Cerrar modal
    document.querySelectorAll('[data-fm-close="1"]').forEach(el => {
      el.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        closeFicticioModal();
      });
    });

    document.addEventListener('keydown', (e) => {
      if(e.key === 'Escape' && fm && fm.getAttribute('aria-hidden') === 'false'){
        closeFicticioModal();
      }
    });

    // Abrir selector desde dropzone (100% gesto de usuario)
    if(dz && fmFile){
      dz.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        // rAF ayuda en algunos navegadores cuando hay overlay/animaciones
        requestAnimationFrame(() => fmFile.click());
      });

      dz.addEventListener('keydown', (e) => {
        if(e.key === 'Enter' || e.key === ' '){
          e.preventDefault();
          requestAnimationFrame(() => fmFile.click());
        }
      });
    }

    if(fmFile){
      fmFile.addEventListener('change', refreshFileName);
    }

    // Submit upload (incluye _token y credentials)
    if(fmForm){
      fmForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = fmDocId.value;
        const file = fmFile && fmFile.files && fmFile.files[0];

        if(!id){
          showToast('err','Falta documento','No se detectó el documento a ligar.');
          return;
        }
        if(!file){
          showToast('err','Falta archivo','Selecciona un archivo para subir.');
          return;
        }

        const uploadUrl = @json(url('/')) + '/partcontable/documents/' + encodeURIComponent(id) + '/ficticio';

        const fd = new FormData();
        fd.append('file', file);      // <- si tu backend usa otro nombre, dímelo y lo ajusto
        fd.append('_token', CSRF);    // <- por si tu ruta valida token en body

        try{
          if(fmLoading) fmLoading.style.display = 'inline';
          if(fmSubmit) fmSubmit.disabled = true;

          const res = await fetch(uploadUrl, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': CSRF,
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: fd
          });

          const { json, text } = await safeJson(res);

          if(!res.ok){
            const msg = (json && json.message)
              ? json.message
              : (text ? text.slice(0, 180) : `Error HTTP ${res.status}`);
            showToast('err','No se pudo', msg);
            return;
          }

          showToast('ok','Subido','Ficticio subido y ligado correctamente.');
          closeFicticioModal();
          setTimeout(() => window.location.reload(), 700);

        }catch(err){
          showToast('err','Error','Error de red al subir ficticio.');
        }finally{
          if(fmLoading) fmLoading.style.display = 'none';
          if(fmSubmit) fmSubmit.disabled = false;
        }
      });
    }

    // ✅ Delegación: badge info + botón ficticio
    document.addEventListener('click', (e) => {
      const badge = e.target.closest('[data-badge-info="1"]');
      if(badge){
        e.preventDefault();
        e.stopPropagation();
        showToast('info','Documento completo','Este documento tiene archivo principal y archivo ficticio (doble archivo).');
        return;
      }

      const btn = e.target.closest('[data-ficticio-trigger]');
      if(btn){
        e.preventDefault();
        e.stopPropagation();
        openFicticioModal(btn.getAttribute('data-ficticio-trigger'), btn.getAttribute('data-doc-title') || 'Documento');
        return;
      }

      // Evitar que acciones disparen la tarjeta
      if (e.target.closest('.pc-company-actions, .pc-card-top-actions')) {
        e.stopPropagation();
      }
    }, true);

  });
})();
</script>
