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

{{-- ✅ Overrides puntuales solicitados (sin romper tu CSS global) --}}
<style>
  /* Botones de descarga lado a lado */
  .pc-company-actions{
    display:flex !important;
    flex-direction: row !important;
    align-items:center !important;
    gap:10px !important;
    justify-content:flex-end;
  }
  .pc-company-actions .pc-icon-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
  }

  /* Animación real de descarga (estado loading) */
  .pc-icon-btn.is-downloading{
    pointer-events:none;
    opacity:.78;
    transform: translateZ(0);
  }
  .pc-icon-btn .pc-ic-default{ display:inline-flex; }
  .pc-icon-btn .pc-ic-spin{
    display:none;
    width:16px; height:16px;
    align-items:center; justify-content:center;
  }
  .pc-icon-btn.is-downloading .pc-ic-default{ display:none; }
  .pc-icon-btn.is-downloading .pc-ic-spin{
    display:inline-flex;
    animation: pcSpin .85s linear infinite;
  }
  @keyframes pcSpin{ to{ transform: rotate(360deg);} }
</style>

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

        $returnUrl = request()->fullUrl();
        $previewUrl = route('partcontable.documents.preview', $doc) . '?open_ficticio=1&return=' . urlencode($returnUrl);

        // filename sugerido para descarga ficticio
        $fExt = strtolower(pathinfo($doc->ficticio_file_path ?? '', PATHINFO_EXTENSION));
        if (!$fExt) $fExt = ($ext ?: 'pdf');
        $baseName = pathinfo($filename, PATHINFO_FILENAME) ?: ('documento-'.$doc->id);
        $ficticioSuggestedName = $baseName . '-ficticio.' . $fExt;
      @endphp

      <article class="card pc-doc-card" aria-labelledby="doc-{{ $doc->id }}" data-id="{{ $doc->id }}" tabindex="0">
        <a class="card__link" href="{{ route('partcontable.documents.preview', $doc) }}" target="_blank" rel="noopener"></a>

        <div class="card__hero">
          <header class="card__hero-header">
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
              <span class="pc-doc-badge {{ $isPdf ? 'pc-doc-badge-pdf' : '' }}">
                {{ strtoupper($ext ?: ($doc->file_type ?? 'FILE')) }}
              </span>

              {{-- ✅ QUITADO: badge "✓ COMPLETO" (solo dejamos FICTICIO) --}}

              @if($hasFicticio)
                <span class="pc-doc-badge pc-doc-badge-ficticio" title="Tiene ficticio">FICTICIO</span>
              @endif
            </div>

            <div class="pc-card-top-actions" role="group" aria-label="Acciones del documento">
              {{-- ✅ YA NO HAY MODAL AQUÍ: ahora manda al PREVIEW y abre modal allá --}}
              @if($allowFicticioHere && !$hasFicticio)
                <a
                  href="{{ $previewUrl }}"
                  class="pc-icon-btn pc-btn-ficticio"
                  aria-label="Subir ficticio para {{ $doc->title }}"
                  title="Subir ficticio (en previsualización)"
                  onclick="event.stopPropagation();"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <path d="M14 2v6h6"></path>
                    <path d="M12 18v-6"></path>
                    <path d="M9 15h6"></path>
                  </svg>
                </a>
              @endif

              <form method="POST" action="{{ route('partcontable.documents.destroy', $doc) }}" class="pc-delete-form-inline" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="pc-icon-btn pc-btn-delete" aria-label="Eliminar {{ $doc->title }}" title="Eliminar" onclick="event.stopPropagation();">
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
               data-filename="{{ $filename }}"
               aria-label="Descargar {{ $doc->title }}"
               title="Descargar"
               onclick="event.stopPropagation();">
              <span class="pc-ic-default" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="7 10 12 15 17 10"></polyline>
                  <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
              </span>
              <span class="pc-ic-spin" aria-hidden="true">
                {{-- spinner --}}
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none">
                  <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.2" opacity=".25"></circle>
                  <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"></path>
                </svg>
              </span>
            </a>

            @if(!empty($doc->ficticio_file_path))
              <a class="pc-icon-btn pc-btn-download pc-btn-download-ficticio"
                 href="{{ route('partcontable.documents.ficticio.download', $doc) }}"
                 data-filename="{{ $ficticioSuggestedName }}"
                 aria-label="Descargar ficticio de {{ $doc->title }}"
                 title="Descargar ficticio"
                 onclick="event.stopPropagation();">
                <span class="pc-ic-default" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <path d="M14 2v6h6"></path>
                    <path d="M12 18v-6"></path>
                    <path d="M9 15h6"></path>
                  </svg>
                </span>
                <span class="pc-ic-spin" aria-hidden="true">
                  {{-- spinner --}}
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.2" opacity=".25"></circle>
                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"></path>
                  </svg>
                </span>
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

@endsection

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="{{ asset('css/company.css') }}?v={{ time() }}">

<script>
(function(){
  'use strict';

  if (window.__pcCompanyBound) return;
  window.__pcCompanyBound = true;

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

  function parseFilenameFromContentDisposition(cd){
    // Maneja: filename="x.pdf" y filename*=UTF-8''x.pdf
    if(!cd) return null;
    try{
      const mStar = cd.match(/filename\*\s*=\s*UTF-8''([^;]+)/i);
      if(mStar && mStar[1]) return decodeURIComponent(mStar[1].replace(/["']/g,'').trim());
      const m = cd.match(/filename\s*=\s*("?)([^";]+)\1/i);
      if(m && m[2]) return m[2].trim();
    }catch(_){}
    return null;
  }

  // ✅ Descarga con animación REAL (espera respuesta y luego dispara download)
  async function handleDownloadClick(e){
    e.preventDefault();
    e.stopPropagation();

    const btn = e.currentTarget;
    if(!btn || btn.classList.contains('is-downloading')) return;

    const url = btn.getAttribute('href');
    if(!url) return;

    const suggested = (btn.getAttribute('data-filename') || '').trim();
    btn.classList.add('is-downloading');

    showToast('info','Descargando','Preparando descarga…');

    try{
      const res = await fetch(url, { credentials:'same-origin' });
      if(!res.ok) throw new Error('HTTP ' + res.status);

      const cd = res.headers.get('Content-Disposition') || res.headers.get('content-disposition');
      const fromHeader = parseFilenameFromContentDisposition(cd);
      const filename = fromHeader || suggested || 'archivo';

      const blob = await res.blob();
      const blobUrl = URL.createObjectURL(blob);

      const a = document.createElement('a');
      a.href = blobUrl;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      a.remove();

      setTimeout(() => URL.revokeObjectURL(blobUrl), 30000);
      showToast('ok','Listo','Descarga iniciada.');
    }catch(err){
      showToast('err','No se pudo','Error al descargar el archivo.');
    }finally{
      btn.classList.remove('is-downloading');
    }
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

    // ✅ Bind descarga con animación real
    document.querySelectorAll('a.pc-btn-download').forEach(btn => {
      btn.addEventListener('click', handleDownloadClick, { passive:false });
    });

    // Evitar propagación si el click fue en acciones
    document.addEventListener('click', (e) => {
      if (e.target.closest('.pc-company-actions, .pc-card-top-actions')) {
        e.stopPropagation();
      }
    }, true);

  });
})();
</script>
