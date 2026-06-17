<div class="pjd-pane" data-pane="documentos">
        <div class="pjd-documents-card">
          <h3 class="pjd-documents-title">Gestión de documentos</h3>

          <div class="pjd-documents-search-row">
            <label class="pjd-documents-search" for="pjdDocSearch">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
              <input type="search" id="pjdDocSearch" placeholder="Buscar documentos...">
            </label>
            <span class="pjd-documents-count" id="pjdDocCount">{{ $docsForView->count() }}</span>
          </div>

          <div class="pjd-doc-list" id="pjdDocList">
            @forelse($docsForView as $doc)
              @php
                $docName = $doc['filename'] ?? 'Documento';
                $docExt = strtoupper($doc['extension'] ?? pathinfo($docName, PATHINFO_EXTENSION) ?: 'FILE');
                $docUrl = $doc['url'] ?? '#';
                $docDownload = $doc['download_url'] ?? $docUrl;
                $docDelete = $doc['delete_url'] ?? null;
                $docStatus = $doc['status'] ?? 'completed';
                $docText = trim(($docName . ' ' . ($doc['summary'] ?? '') . ' ' . ($doc['match_label'] ?? '') . ' ' . ($doc['requirement'] ?? '')));
              @endphp
              <article class="pjd-doc-card" data-doc-card data-doc-url="{{ $docUrl }}" data-doc-name="{{ e($docName) }}" data-doc-text="{{ e(\Illuminate\Support\Str::lower($docText)) }}">
                <div class="pjd-doc-main">
                  <div class="pjd-doc-file-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7z"/><path d="M14 2v5h5"/><path d="M9 13h6"/><path d="M9 17h4"/></svg>
                  </div>

                  <div class="pjd-doc-content">
                    <div class="pjd-doc-title-line">
                      <span class="pjd-doc-name">{{ $docName }}</span>
                      <span class="pjd-doc-badge {{ $docExt === 'PDF' ? 'is-pdf' : 'is-file' }}">{{ $docExt }}</span>
                      <span class="pjd-doc-badge pjd-doc-status {{ in_array($docStatus, ['processing']) ? 'is-processing' : (in_array($docStatus, ['failed','error']) ? 'is-error' : '') }}">{{ $doc['status_label'] ?? 'Completado' }}</span>
                    </div>
                    <div class="pjd-doc-sub">
                      {{ $doc['size_label'] ?? '0 B' }}
                      @if(!empty($doc['date_label'])) · {{ $doc['date_label'] }} @endif
                      @if(!empty($doc['pages_label'])) · {{ $doc['pages_label'] }} @endif
                    </div>
                    @if(!empty($doc['match_label']))
                      <div class="pjd-doc-match">{{ $doc['match_label'] }}</div>
                    @endif
                  </div>

                  <div class="pjd-doc-actions">
                    <button type="button" class="pjd-doc-icon-btn pjd-doc-toggle" title="Ver información" data-doc-toggle>
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <button type="button" class="pjd-doc-icon-btn" title="Opciones" data-doc-menu-btn>
                      <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
                    </button>
                  </div>
                </div>

                <div class="pjd-doc-details">
                  <h4>Información del documento</h4>
                  <p>{{ $doc['summary'] ?? 'Sin descripción disponible.' }}</p>
                  @if(!empty($doc['requirement']))
                    <div class="pjd-doc-detail-meta">Relacionado con checklist: {{ $doc['requirement'] }}</div>
                  @endif
                </div>

                <div class="pjd-doc-menu" data-doc-menu>
                  <a href="{{ $docUrl }}" target="_blank" data-doc-preview>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                    Vista previa
                  </a>
                  <a href="{{ $docDownload }}" download>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                    Descargar
                  </a>
                  @if($docDelete)
                    <button type="button" class="is-danger" data-doc-delete="{{ $docDelete }}">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                      Eliminar
                    </button>
                  @endif
                </div>
              </article>
            @empty
              <div class="pjd-doc-empty">Este proyecto todavía no tiene documentos ni evidencias adjuntas.</div>
            @endforelse
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<div class="pjd-cl-popover" id="pjdClCumpPop">
  <button data-set-cumplimiento="-"><span class="pjd-cl-choice-icon pjd-cl-choice-muted"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg></span> Sin revisar</button>
  <button data-set-cumplimiento="Cumple"><span class="pjd-cl-choice-icon pjd-cl-choice-success"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 12l2.6 2.6L16.5 9"/></svg></span> Cumple</button>
  <button data-set-cumplimiento="Parcial"><span class="pjd-cl-choice-icon pjd-cl-choice-warning"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 7v6"/><path d="M12 17h.01"/></svg></span> Parcial</button>
  <button data-set-cumplimiento="No Cumple"><span class="pjd-cl-choice-icon pjd-cl-choice-danger"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6"/><path d="M9 9l6 6"/></svg></span> No Cumple</button>
</div>

<div class="pjd-cl-popover" id="pjdClStatusPop">
  <button data-set-status="Pendiente"><span class="dot" style="background:var(--warning)"></span> Pendiente</button>
  <button data-set-status="En revisión"><span class="dot" style="background:var(--blue)"></span> En revisión</button>
  <button data-set-status="Aprobado"><span class="dot" style="background:var(--success)"></span> Aprobado</button>
</div>

<div class="pjd-cl-row-menu" id="pjdClRowMenu" aria-hidden="true">
  <button type="button" class="pjd-cl-row-action" data-row-action="edit">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
    Editar
  </button>
  <button type="button" class="pjd-cl-row-action" data-row-action="duplicate">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
    Duplicar
  </button>
  <button type="button" class="pjd-cl-row-action is-danger" data-row-action="delete">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
    Eliminar
  </button>
</div>

{{-- DRAWER LATERAL: VISTA PREVIA DEL PDF CON RESALTADO --}}
<div class="pjd-doc-drawer" id="pjdDocDrawer" aria-hidden="true">
  <div class="pjd-doc-drawer-backdrop" data-drawer-close></div>
  <div class="pjd-doc-drawer-panel">
    <div class="pjd-doc-drawer-head">
      <div class="pjd-doc-drawer-file" id="pjdDrawerFile">documento.pdf</div>
      <button type="button" class="pjd-doc-drawer-toolbtn is-active" id="pjdDrawerTranscript">Transcripción</button>
      <a href="#" target="_blank" class="pjd-doc-drawer-toolbtn" id="pjdDrawerOpen">Abrir</a>
      <button type="button" class="pjd-doc-drawer-close" data-drawer-close aria-label="Cerrar">✕</button>
    </div>
    <div class="pjd-doc-drawer-nav">
      <button type="button" id="pjdPdfPrev" title="Anterior">‹</button>
      <span class="pjd-doc-drawer-pageind" id="pjdPdfPageInd">1 / 1</span>
      <button type="button" id="pjdPdfNext" title="Siguiente">›</button>
    </div>
    <div class="pjd-pdf-scroll" id="pjdPdfScroll">
      <div class="pjd-pdf-container" id="pjdPdfContainer">
        <canvas id="pjdPdfCanvas"></canvas>
        <div class="pjd-pdf-highlights" id="pjdPdfHighlights"></div>
      </div>
      <div class="pjd-pdf-loading" id="pjdPdfLoading" style="display:none;">Cargando documento…</div>
    </div>
    <div class="pjd-doc-drawer-quote" id="pjdDrawerQuote">
      <div class="pjd-doc-drawer-quote-kicker">Transcripción de la cita</div>
      <div class="pjd-doc-drawer-quote-text" id="pjdDrawerQuoteText">—</div>
      <div class="pjd-doc-drawer-quote-meta" id="pjdDrawerQuoteMeta"></div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
{{-- SheetJS para Excel real (.xlsx) --}}
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
{{-- PDF.js para renderizar el PDF y resaltar la cita --}}
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js"></script>
@php
  $pjdDocsList = collect($documentLibrary ?? [])->map(fn($d) => [
    'filename' => $d['filename'] ?? '',
    'stored'   => basename($d['url'] ?? ''),
    'url'      => $d['url'] ?? '',
  ])->values();
@endphp
<script>
(function(){
  'use strict';

  const PROJECT_SLUG    = @json($project->slug);
  const PROJECT_NAME    = @json($project->name);
  const CHAT_URL        = @json(route('projects.chat', $project));
  const CHAT_RESET_URL  = @json(route('projects.chat.reset', $project));
  const DRAFT_URL       = @json(route('projects.draft', $project));
  const CHECKLIST_URL   = @json(route('projects.checklist', $project));
  const CHECKLIST_ATTACH_URL = @json(route('projects.checklist.attach', $project));
  const CHECKLIST_EXPORT_BASE_URL = @json(url('/projects/' . $project->slug . '/checklist/export'));
  const REPORT_URL      = @json(route('projects.report', $project));
  const CSRF            = '{{ csrf_token() }}';
  const PROJECT_DOCS_LIST = @json($pjdDocsList);

  function escapeHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

  // ============ MARKDOWN (chat) ============
  function renderMarkdown(text) {
    if (!text) return '';
    let s = escapeHtml(text.trim());
    s = s.replace(/`([^`]+)`/g, '<code class="pjd-md-code">$1</code>');
    s = s.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    s = s.replace(/__([^_]+)__/g, '<strong>$1</strong>');
    s = s.replace(/(^|[^*])\*([^*\n]+)\*(?!\*)/g, '$1<em>$2</em>');

    const lines = s.split('\n');
    let html = '';
    let listType = null;
    const closeList = () => { if (listType) { html += `</${listType}>`; listType = null; } };

    for (let raw of lines) {
      const t = raw.trim();
      if (t === '') { closeList(); continue; }
      let m;
      if ((m = t.match(/^###\s+(.*)$/))) { closeList(); html += `<h4 class="pjd-md-h">${m[1]}</h4>`; continue; }
      if ((m = t.match(/^##\s+(.*)$/)))  { closeList(); html += `<h3 class="pjd-md-h">${m[1]}</h3>`; continue; }
      if ((m = t.match(/^#\s+(.*)$/)))   { closeList(); html += `<h3 class="pjd-md-h">${m[1]}</h3>`; continue; }
      if ((m = t.match(/^[-•*]\s+(.*)$/))) {
        if (listType !== 'ul') { closeList(); html += '<ul class="pjd-md-ul">'; listType = 'ul'; }
        html += `<li>${m[1]}</li>`; continue;
      }
      if ((m = t.match(/^\d+[.)]\s+(.*)$/))) {
        if (listType !== 'ol') { closeList(); html += '<ol class="pjd-md-ol">'; listType = 'ol'; }
        html += `<li>${m[1]}</li>`; continue;
      }
      closeList();
      html += `<p class="pjd-md-p">${t}</p>`;
    }
    closeList();
    return html;
  }

  // ============ TOAST ============
  function showToast(msg, type) {
    const t = document.createElement('div');
    t.className = 'pjd-toast' + (type === 'success' ? ' is-success' : type === 'error' ? ' is-error' : '');
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; }, 2200);
    setTimeout(() => t.remove(), 2600);
  }

  // ============ TABS ============
  const tabs = document.querySelectorAll('.pjd-tab');
  const panes = document.querySelectorAll('.pjd-pane');
  function activateTab(name) {
    tabs.forEach(t => t.classList.toggle('is-active', t.dataset.tab === name));
    panes.forEach(p => p.classList.toggle('is-active', p.dataset.pane === name));
  }
  tabs.forEach(t => t.addEventListener('click', () => activateTab(t.dataset.tab)));
  activateTab('ficha');

  // ============ SPLIT VIEW REDIMENSIONABLE (aplica a todas las pestañas) ============
  const pjdWrap = document.querySelector('.pjd-wrap');
  const pjdBody = document.querySelector('.pjd-body');
  const pjdResizer = document.getElementById('pjdResizer');
  const PJD_SPLIT_KEY = 'pjd:project-detail:left-width';

  function clampSplit(px) {
    const bodyRect = pjdBody.getBoundingClientRect();
    const minLeft = 300;
    const minRight = 360;
    const maxLeft = Math.max(minLeft, bodyRect.width - minRight - 10);
    return Math.min(Math.max(px, minLeft), maxLeft);
  }

  function setSplitWidth(px, persist = true) {
    if (!pjdWrap || !pjdBody) return;
    const bodyRect = pjdBody.getBoundingClientRect();
    if (!bodyRect.width) return;
    const safePx = clampSplit(px);
    pjdWrap.style.setProperty('--pjd-left-width', safePx + 'px');
    pjdResizer?.setAttribute('aria-valuenow', String(Math.round(safePx)));
    if (persist) localStorage.setItem(PJD_SPLIT_KEY, String(Math.round(safePx)));
  }

  function restoreSplitWidth() {
    if (!pjdBody || window.matchMedia('(max-width: 1100px)').matches) return;
    const saved = parseInt(localStorage.getItem(PJD_SPLIT_KEY) || '', 10);
    const bodyRect = pjdBody.getBoundingClientRect();
    const fallback = bodyRect.width * 0.44;
    setSplitWidth(Number.isFinite(saved) && saved > 0 ? saved : fallback, false);
  }

  restoreSplitWidth();
  window.addEventListener('resize', () => {
    if (window.matchMedia('(max-width: 1100px)').matches) return;
    const current = parseInt(getComputedStyle(pjdWrap).getPropertyValue('--pjd-left-width'), 10);
    setSplitWidth(Number.isFinite(current) ? current : pjdBody.getBoundingClientRect().width * 0.44, false);
  });

  pjdResizer?.addEventListener('pointerdown', (e) => {
    if (window.matchMedia('(max-width: 1100px)').matches) return;
    e.preventDefault();
    pjdResizer.setPointerCapture(e.pointerId);
    document.body.classList.add('is-pjd-resizing');

    const onMove = (ev) => {
      const bodyRect = pjdBody.getBoundingClientRect();
      setSplitWidth(ev.clientX - bodyRect.left);
    };

    const onUp = () => {
      document.body.classList.remove('is-pjd-resizing');
      pjdResizer.removeEventListener('pointermove', onMove);
      pjdResizer.removeEventListener('pointerup', onUp);
      pjdResizer.removeEventListener('pointercancel', onUp);
    };

    pjdResizer.addEventListener('pointermove', onMove);
    pjdResizer.addEventListener('pointerup', onUp);
    pjdResizer.addEventListener('pointercancel', onUp);
  });

  pjdResizer?.addEventListener('dblclick', () => {
    localStorage.removeItem(PJD_SPLIT_KEY);
    setSplitWidth(pjdBody.getBoundingClientRect().width * 0.44);
  });

  pjdResizer?.addEventListener('keydown', (e) => {
    if (!['ArrowLeft','ArrowRight','Home','End'].includes(e.key)) return;
    e.preventDefault();
    const bodyRect = pjdBody.getBoundingClientRect();
    const current = parseInt(getComputedStyle(pjdWrap).getPropertyValue('--pjd-left-width'), 10) || bodyRect.width * 0.44;
    if (e.key === 'Home') return setSplitWidth(300);
    if (e.key === 'End') return setSplitWidth(bodyRect.width - 360 - 10);
    setSplitWidth(current + (e.key === 'ArrowRight' ? 32 : -32));
  });

  document.querySelectorAll('.js-card-toggle').forEach(head => {
    head.addEventListener('click', () => head.closest('.pjd-card').classList.toggle('is-open'));
  });

  // ============ CHAT ============
  const chatForm = document.getElementById('pjdChatForm');
  const chatInput = document.getElementById('pjdChatInput');
  const chatSend = document.getElementById('pjdChatSend');
  const chatList = document.getElementById('pjdChatList');
  const chatReset = document.getElementById('pjdChatReset');

  function scrollChatToBottom() { chatList.scrollTop = chatList.scrollHeight; }
  scrollChatToBottom();

  function extractMarkdownTable(text) {
    const lines = text.split('\n');
    let start = -1, end = -1;
    for (let i = 0; i < lines.length; i++) {
      const l = lines[i].trim();
      if (l.startsWith('|') && l.endsWith('|') && lines[i+1] && /^\|[\s:|\-]+\|$/.test(lines[i+1].trim())) {
        start = i;
        for (let j = i + 2; j < lines.length; j++) {
          const lj = lines[j].trim();
          if (lj.startsWith('|') && lj.endsWith('|')) end = j;
          else break;
        }
        break;
      }
    }
    if (start === -1 || end === -1 || end <= start + 1) return null;
    const parseRow = (line) => line.trim().replace(/^\||\|$/g, '').split('|').map(c => c.trim());
    const headers = parseRow(lines[start]);
    const rows = [];
    for (let i = start + 2; i <= end; i++) {
      const r = parseRow(lines[i]);
      if (r.length) rows.push(r);
    }
    return { headers, rows, before: lines.slice(0, start).join('\n').trim(), after: lines.slice(end + 1).join('\n').trim() };
  }

  function renderTableHtml(data) {
    const head = '<tr>' + data.headers.map(h => `<th>${escapeHtml(h)}</th>`).join('') + '</tr>';
    const body = data.rows.map(r => '<tr>' + r.map(c => `<td>${escapeHtml(c).replace(/\n/g,'<br>')}</td>`).join('') + '</tr>').join('');
    return `<table class="pjd-chat-table"><thead>${head}</thead><tbody>${body}</tbody></table>`;
  }

  function buildTableHtmlInline(data) {
    let html = '<table style="width:100%;border-collapse:collapse;margin:14px 0;border:1px solid #e5e7eb;font-family:Quicksand,Arial,sans-serif">';
    html += '<thead><tr>';
    data.headers.forEach(h => { html += `<th style="background:#f3f4f6;color:#111;padding:14px 16px;border:1px solid #e5e7eb;text-align:left;font-weight:700;font-size:14px">${escapeHtml(h)}</th>`; });
    html += '</tr></thead><tbody>';
    data.rows.forEach(r => { html += '<tr>'; r.forEach(c => { html += `<td style="padding:14px 16px;border:1px solid #e5e7eb;vertical-align:top;color:#333;line-height:1.55;font-size:14px">${escapeHtml(c).replace(/\n/g, '<br>')}</td>`; }); html += '</tr>'; });
    html += '</tbody></table>';
    return html;
  }

  async function copyTableToClipboard(data) {
    const tsv = [data.headers.join('\t'), ...data.rows.map(r => r.join('\t'))].join('\n');
    const html = buildTableHtmlInline(data);
    try {
      if (typeof ClipboardItem !== 'undefined' && navigator.clipboard?.write) {
        await navigator.clipboard.write([ new ClipboardItem({ 'text/html': new Blob([html],{type:'text/html'}), 'text/plain': new Blob([tsv],{type:'text/plain'}) }) ]);
        showToast(' Tabla copiada (pégala donde quieras)', 'success'); return;
      }
    } catch (_) {}
    try { await navigator.clipboard.writeText(tsv); showToast(' Tabla copiada com