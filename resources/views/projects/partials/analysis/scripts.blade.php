@once
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
    t.textContent = String(msg || '').trim();
    document.body.appendChild(t);
    setTimeout(() => t.classList.add('is-leaving'), 2200);
    setTimeout(() => t.remove(), 2480);
  }

  // ============ MODALES NATIVOS REEMPLAZADOS ============
  const PJD_MODAL_ICONS = {
    info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v5h1"/></svg>',
    danger: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 4.3 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/></svg>'
  };

  function pjdCloseUiModal(modal, value, resolve) {
    modal.classList.add('is-closing');
    window.setTimeout(() => {
      modal.remove();
      resolve(value);
    }, 190);
  }

  function pjdConfirm(message, opts = {}) {
    return new Promise((resolve) => {
      const tone = opts.tone || 'info';
      const modal = document.createElement('div');
      modal.className = 'pjd-ui-modal';
      modal.setAttribute('role', 'dialog');
      modal.setAttribute('aria-modal', 'true');
      modal.innerHTML = `
        <div class="pjd-ui-modal-backdrop" data-modal-cancel></div>
        <div class="pjd-ui-modal-card">
          <div class="pjd-ui-modal-head">
            <div class="pjd-ui-modal-icon ${tone === 'danger' ? 'is-danger' : ''}">${PJD_MODAL_ICONS[tone === 'danger' ? 'danger' : 'info']}</div>
            <div>
              <h3 class="pjd-ui-modal-title">${opts.title || 'Confirmar acción'}</h3>
              <p class="pjd-ui-modal-text">${message}</p>
            </div>
          </div>
          <div class="pjd-ui-modal-actions">
            <button type="button" class="pjd-ui-modal-btn" data-modal-cancel>${opts.cancelText || 'Cancelar'}</button>
            <button type="button" class="pjd-ui-modal-btn ${tone === 'danger' ? 'is-danger' : 'is-primary'}" data-modal-ok>${opts.okText || 'Continuar'}</button>
          </div>
        </div>`;
      document.body.appendChild(modal);
      requestAnimationFrame(() => modal.classList.add('is-open'));
      const ok = modal.querySelector('[data-modal-ok]');
      const cancel = modal.querySelectorAll('[data-modal-cancel]');
      ok?.focus();
      ok?.addEventListener('click', () => pjdCloseUiModal(modal, true, resolve));
      cancel.forEach(el => el.addEventListener('click', () => pjdCloseUiModal(modal, false, resolve)));
      modal.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') pjdCloseUiModal(modal, false, resolve);
        if (e.key === 'Enter') pjdCloseUiModal(modal, true, resolve);
      });
    });
  }

  function pjdPrompt(label, defaultValue = '', opts = {}) {
    return new Promise((resolve) => {
      const modal = document.createElement('div');
      modal.className = 'pjd-ui-modal';
      modal.setAttribute('role', 'dialog');
      modal.setAttribute('aria-modal', 'true');
      modal.innerHTML = `
        <div class="pjd-ui-modal-backdrop" data-modal-cancel></div>
        <div class="pjd-ui-modal-card">
          <div class="pjd-ui-modal-head">
            <div class="pjd-ui-modal-icon">${PJD_MODAL_ICONS.info}</div>
            <div>
              <h3 class="pjd-ui-modal-title">${opts.title || label}</h3>
              <p class="pjd-ui-modal-text">${opts.description || 'Completa el campo para continuar.'}</p>
            </div>
          </div>
          <div class="pjd-ui-modal-body">
            <input class="pjd-ui-modal-input" type="${opts.type || 'text'}" value="${String(defaultValue || '').replace(/"/g, '&quot;')}" placeholder="${opts.placeholder || ''}" ${opts.inputmode ? `inputmode="${opts.inputmode}"` : ''}>
          </div>
          <div class="pjd-ui-modal-actions">
            <button type="button" class="pjd-ui-modal-btn" data-modal-cancel>${opts.cancelText || 'Cancelar'}</button>
            <button type="button" class="pjd-ui-modal-btn is-primary" data-modal-ok>${opts.okText || 'Aceptar'}</button>
          </div>
        </div>`;
      document.body.appendChild(modal);
      requestAnimationFrame(() => modal.classList.add('is-open'));
      const input = modal.querySelector('.pjd-ui-modal-input');
      const ok = modal.querySelector('[data-modal-ok]');
      const cancel = modal.querySelectorAll('[data-modal-cancel]');
      setTimeout(() => { input?.focus(); input?.select(); }, 30);
      ok?.addEventListener('click', () => pjdCloseUiModal(modal, input?.value ?? '', resolve));
      cancel.forEach(el => el.addEventListener('click', () => pjdCloseUiModal(modal, null, resolve)));
      modal.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') pjdCloseUiModal(modal, null, resolve);
        if (e.key === 'Enter') pjdCloseUiModal(modal, input?.value ?? '', resolve);
      });
    });
  }

  // ============ TABS ============
  const tabs = document.querySelectorAll('.pjd-tab');
  const panes = document.querySelectorAll('.pjd-pane');

  const PJD_TAB_ALIASES = {
    '#inicio': 'inicio',
    '#ficha': 'ficha',
    '#resumen': 'resumen',
    '#resumen-ejecutivo': 'resumen',
    '#checklist': 'checklist',
    '#armado-propuesta': 'checklist',
    '#propuesta': 'checklist',
    '#borrador': 'borrador',
    '#reporte': 'borrador',
    '#documentos': 'documentos',
  };

  function normalizePjdTabHash(hash) {
    const cleanHash = String(hash || '').trim().toLowerCase();
    return PJD_TAB_ALIASES[cleanHash] || null;
  }

  function activateTab(name, opts = {}) {
    const hasTab = Array.from(tabs).some(t => t.dataset.tab === name);
    if (!hasTab) name = 'ficha';

    tabs.forEach(t => t.classList.toggle('is-active', t.dataset.tab === name));
    panes.forEach(p => p.classList.toggle('is-active', p.dataset.pane === name));

    if (opts.updateHash && window.location.hash !== `#${name}`) {
      history.replaceState(null, '', `${window.location.pathname}${window.location.search}#${name}`);
    }

    if (name === 'checklist') {
      setTimeout(() => document.querySelector('[data-pane="checklist"]')?.scrollIntoView({ block: 'start', behavior: 'smooth' }), 60);
    }
  }

  function activateTabFromUrl() {
    activateTab(normalizePjdTabHash(window.location.hash) || 'ficha');
  }

  tabs.forEach(t => t.addEventListener('click', () => activateTab(t.dataset.tab, { updateHash: true })));
  window.addEventListener('hashchange', activateTabFromUrl);
  activateTabFromUrl();

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

  function triggerChatSendFx() {
    if (!chatForm) return;
    chatForm.classList.remove('is-sending');
    void chatForm.offsetWidth;
    chatForm.classList.add('is-sending');
    window.setTimeout(() => chatForm.classList.remove('is-sending'), 760);
  }

  function playChatSendSound() {
    try {
      const AudioContext = window.AudioContext || window.webkitAudioContext;
      if (!AudioContext) return;
      const ctx = new AudioContext();
      const now = ctx.currentTime;
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      const filter = ctx.createBiquadFilter();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(620, now);
      osc.frequency.exponentialRampToValueAtTime(980, now + .07);
      filter.type = 'highpass';
      filter.frequency.value = 420;
      gain.gain.setValueAtTime(0.0001, now);
      gain.gain.exponentialRampToValueAtTime(0.045, now + .012);
      gain.gain.exponentialRampToValueAtTime(0.0001, now + .11);
      osc.connect(filter);
      filter.connect(gain);
      gain.connect(ctx.destination);
      osc.start(now);
      osc.stop(now + .12);
      window.setTimeout(() => ctx.close?.(), 180);
    } catch (_) {}
  }

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
        showToast('Tabla copiada (pégala donde quieras)', 'success'); return;
      }
    } catch (_) {}
    try { await navigator.clipboard.writeText(tsv); showToast('Tabla copiada como texto', 'success'); }
    catch (e) { const ta = document.createElement('textarea'); ta.value = tsv; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); showToast('Tabla copiada', 'success'); }
  }

  function copyTableToBorrador(data) {
    const editor = document.getElementById('pjdDraftEditor');
    if (!editor) { showToast('No se encontró el borrador', 'error'); return; }
    editor.innerHTML += buildTableHtmlInline(data) + '<p><br></p>';
    document.querySelector('.pjd-tab[data-tab="borrador"]')?.click();
    document.querySelector('.pjd-borrador-tab[data-section="borrador"]')?.click();
    setTimeout(() => { scheduleDraftAutoSave(true); }, 200);
    showToast('Tabla agregada al borrador', 'success');
  }

  function downloadTableAsExcel(data) {
    if (typeof XLSX === 'undefined') { showToast('La librería de Excel no se cargó. Recarga la página.', 'error'); return; }
    const ws = XLSX.utils.aoa_to_sheet([data.headers, ...data.rows]);
    ws['!cols'] = data.headers.map((h, i) => { let max = (h||'').toString().length; data.rows.forEach(r => { const len = (r[i]||'').toString().length; if (len > max) max = len; }); return { wch: Math.min(Math.max(max + 2, 14), 70) }; });
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Tabla');
    const ts = new Date().toISOString().slice(0,19).replace(/[:T]/g,'-');
    XLSX.writeFile(wb, `tabla-${PROJECT_SLUG}-${ts}.xlsx`);
    showToast('Excel descargado', 'success');
  }

  function appendMsg(role, content, time = '') {
    const wrap = document.createElement('div');
    wrap.className = `pjd-msg ${role === 'user' ? 'is-user' : 'is-assistant'} pjd-msg-enter`;
    if (role === 'user') { wrap.innerHTML = `<div class="pjd-msg-body">${escapeHtml(content)}</div>`; chatList.appendChild(wrap); scrollChatToBottom(); return wrap; }

    const tableData = extractMarkdownTable(content);
    let bodyHtml;
    if (tableData) {
      const tableHtml = renderTableHtml(tableData);
      const textBefore = tableData.before ? `<div class="pjd-msg-body" style="margin-bottom:8px">${renderMarkdown(tableData.before)}</div>` : '';
      const textAfter  = tableData.after  ? `<div class="pjd-msg-body" style="margin-top:8px">${renderMarkdown(tableData.after)}</div>` : '';
      bodyHtml = `${textBefore}<div class="pjd-chat-table-wrap"><div class="pjd-chat-table-actions"><button type="button" class="pjd-chat-table-btn js-copy-table">Copiar tabla</button><button type="button" class="pjd-chat-table-btn js-copy-to-draft">Pasar al borrador</button><button type="button" class="pjd-chat-table-btn is-primary js-download-excel">Descargar Excel</button></div>${tableHtml}</div>${textAfter}`;
    } else {
      bodyHtml = `<div class="pjd-msg-body">${renderMarkdown(content)}</div>`;
    }
    wrap.innerHTML = `<div class="pjd-msg-avatar">j</div><div style="flex:1;min-width:0;"><div class="pjd-msg-meta">sam${time ? ' · ' + time : ''}</div>${bodyHtml}</div>`;
    chatList.appendChild(wrap);
    if (tableData) {
      wrap.querySelector('.js-copy-table')?.addEventListener('click', () => copyTableToClipboard(tableData));
      wrap.querySelector('.js-copy-to-draft')?.addEventListener('click', () => copyTableToBorrador(tableData));
      wrap.querySelector('.js-download-excel')?.addEventListener('click', () => downloadTableAsExcel(tableData));
    }
    scrollChatToBottom();
    return wrap;
  }

  document.querySelectorAll('.pjd-msg.is-assistant .pjd-msg-body[data-raw]').forEach(el => {
    const raw = el.getAttribute('data-raw') || '';
    const tableData = extractMarkdownTable(raw);
    if (!tableData) { el.innerHTML = renderMarkdown(raw); return; }
    const container = el.parentElement;
    const tableHtml = renderTableHtml(tableData);
    const textBefore = tableData.before ? `<div class="pjd-msg-body" style="margin-bottom:8px">${renderMarkdown(tableData.before)}</div>` : '';
    const textAfter  = tableData.after  ? `<div class="pjd-msg-body" style="margin-top:8px">${renderMarkdown(tableData.after)}</div>` : '';
    el.outerHTML = `${textBefore}<div class="pjd-chat-table-wrap"><div class="pjd-chat-table-actions"><button type="button" class="pjd-chat-table-btn js-copy-table">Copiar tabla</button><button type="button" class="pjd-chat-table-btn js-copy-to-draft">Pasar al borrador</button><button type="button" class="pjd-chat-table-btn is-primary js-download-excel">Descargar Excel</button></div>${tableHtml}</div>${textAfter}`;
    container.querySelector('.js-copy-table')?.addEventListener('click', () => copyTableToClipboard(tableData));
    container.querySelector('.js-copy-to-draft')?.addEventListener('click', () => copyTableToBorrador(tableData));
    container.querySelector('.js-download-excel')?.addEventListener('click', () => downloadTableAsExcel(tableData));
  });

  function appendLoading() {
    const wrap = document.createElement('div');
    wrap.className = 'pjd-msg is-assistant pjd-loading-enter'; wrap.id = 'pjdLoadingMsg';
    wrap.innerHTML = `<div class="pjd-msg-avatar">j</div><div><div class="pjd-msg-meta">sam</div><div class="pjd-msg-body"><span class="pjd-loading-dots"><span></span><span></span><span></span></span></div></div>`;
    chatList.appendChild(wrap); scrollChatToBottom();
  }

  chatForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = chatInput.value.trim(); if (!msg) return;
    triggerChatSendFx();
    playChatSendSound();
    chatInput.value = ''; chatSend.disabled = true;
    appendMsg('user', msg); appendLoading();
    try {
      const fd = new FormData(); fd.append('_token', CSRF); fd.append('message', msg);
      const res = await fetch(CHAT_URL, { method:'POST', headers:{'Accept':'application/json'}, body: fd, credentials:'same-origin' });
      const json = await res.json();
      document.getElementById('pjdLoadingMsg')?.remove();
      if (json.ok && json.assistant_message) appendMsg('assistant', json.assistant_message.content, json.assistant_message.time);
      else appendMsg('assistant', json.message || 'Hubo un error.');
    } catch (err) { document.getElementById('pjdLoadingMsg')?.remove(); appendMsg('assistant', 'Error de red.'); }
    finally { chatSend.disabled = false; chatInput.focus(); }
  });

  chatReset?.addEventListener('click', async () => {
    if (!await pjdConfirm('¿Borrar todo el historial?', { title: 'Reiniciar chat', okText: 'Reiniciar', tone: 'danger' })) return;
    try {
      await fetch(CHAT_RESET_URL, { method:'DELETE', headers:{'X-CSRF-TOKEN': CSRF, 'Accept':'application/json'} });
      chatList.innerHTML = '';
      appendMsg('assistant', 'Hola, soy tu asistente del proyecto. ¿En qué puedo ayudarte? Puedo resumir las bases, listar requisitos o aclararte cualquier punto de la licitación.');
    } catch (_) {}
  });

  // ============ CHECKLIST ============
  const clBody = document.getElementById('pjdClBody');
  const clSearch = document.getElementById('pjdClSearch');
  const cumpPop = document.getElementById('pjdClCumpPop');
  const statPop = document.getElementById('pjdClStatusPop');
  let activeCumpRow = null, activeStatusRow = null;

  function updateCounters() {
    const rows = clBody.querySelectorAll('tr[data-row]');
    const total = rows.length;
    const counts = { sin_revisar:0, no_cumple:0, parcial:0, cumple:0, pendiente:0, revision:0, aprobado:0 };
    rows.forEach(r => {
      const c = r.dataset.cumplimiento, s = r.dataset.status;
      if (c === 'Cumple') counts.cumple++; else if (c === 'Parcial') counts.parcial++; else if (c === 'No Cumple') counts.no_cumple++; else counts.sin_revisar++;
      if (s === 'En revisión') counts.revision++; else if (s === 'Aprobado') counts.aprobado++; else counts.pendiente++;
    });
    document.getElementById('pjdClTotalNum').textContent = total;
    Object.keys(counts).forEach(k => {
      const numEl = document.querySelector(`[data-counter="${k}"]`);
      const pctEl = document.querySelector(`[data-pct="${k}"]`);
      const barEl = document.querySelector(`[data-bar="${k}"]`);
      const pct = total > 0 ? Math.round((counts[k]/total)*100) : 0;
      if (numEl) numEl.textContent = counts[k];
      if (pctEl) pctEl.textContent = pct + '%';
      if (barEl) barEl.style.width = pct + '%';
    });
  }

  function renderChecklistStatusMarkup(val) {
    const status = val || 'Pendiente';
    const icon = status === 'Aprobado'
      ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M8 12l2.5 2.5L16 9"></path></svg>'
      : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>';
    return `<span class="pjd-cl-status-icon">${icon}</span><span class="pjd-cl-status-text">${status}</span>`;
  }

  function applyChecklistCumplimiento(row, val) {
    if (!row) return;
    const dot = row.querySelector('.pjd-cl-cumple-dot');
    const label = row.querySelector('.pjd-cl-cumple-text');
    row.dataset.cumplimiento = val;
    if (dot) {
      dot.className = 'pjd-cl-cumple-dot';
      if (val === 'Cumple') dot.classList.add('is-cumple');
      else if (val === 'Parcial') dot.classList.add('is-parcial');
      else if (val === 'No Cumple') dot.classList.add('is-nocumple');
    }
    if (label) {
      label.className = 'pjd-cl-cumple-text';
      label.textContent = val || '-';
      if (val === 'Cumple') label.classList.add('is-cumple');
      else if (val === 'Parcial') label.classList.add('is-parcial');
      else if (val === 'No Cumple') label.classList.add('is-nocumple');
    }
  }

  function applyChecklistStatus(row, val) {
    if (!row) return;
    const pill = row.querySelector('.pjd-cl-status');
    if (!pill) return;
    row.dataset.status = val;
    pill.className = 'pjd-cl-status';
    const cls = {'Pendiente':'is-pendiente','En revisión':'is-revision','Aprobado':'is-aprobado'};
    pill.classList.add(cls[val] || 'is-pendiente');
    pill.innerHTML = renderChecklistStatusMarkup(val);
  }

  updateCounters();

  async function postChecklistBackend(action, payload = {}) {
    const fd = new FormData();
    fd.append('_token', CSRF);
    fd.append('action', action);

    Object.entries(payload).forEach(([key, value]) => {
      if (value === undefined || value === null) return;
      if (key === 'item') fd.append('item', JSON.stringify(value));
      else fd.append(key, value);
    });

    const res = await fetch(CHECKLIST_URL, {
      method: 'POST',
      headers: { 'Accept': 'application/json' },
      body: fd,
      credentials: 'same-origin'
    });

    const json = await res.json().catch(() => ({}));
    if (!res.ok || json.ok === false) {
      throw new Error(json.message || json.error || 'No se pudo guardar el checklist.');
    }
    return json;
  }

  function updateRowDatasetFromItem(row, item) {
    if (!row || !item) return;
    row.dataset.itemId = item.id || row.dataset.itemId || '';
    row.dataset.requisito = item.requisito || '';
    row.dataset.formato = item.formato || 'No aplica';
    row.dataset.descripcion = item.descripcion || '';
    row.dataset.cumplimiento = item.cumplimiento || '-';
    row.dataset.status = item.status || 'Pendiente';
    row.dataset.prioridad = item.prioridad || 'Media';
    row.dataset.fechaLimite = item.fecha_limite || '';
    row.dataset.responsable = item.responsable || '';
    row.dataset.revisor = item.revisor || '';
    row.dataset.notas = Array.isArray(item.notas) ? item.notas.join('\n') : (item.notas || '');
    row.dataset.adjuntos = JSON.stringify(item.adjuntos || []);
  }

  const clFiltersBtn = document.getElementById('pjdClFiltersBtn');
  const clColumnsBtn = document.getElementById('pjdClColumnsBtn');
  const clExportBtn = document.getElementById('pjdClExportBtn');
  const clDownloadBtn = document.getElementById('pjdClDownload');
  const clFiltersMenu = document.getElementById('pjdClFiltersMenu');
  const clColumnsMenu = document.getElementById('pjdClColumnsMenu');
  const clExportMenu = document.getElementById('pjdClExportMenu');
  const clDownloadMenu = document.getElementById('pjdClDownloadMenu');
  const clRowMenu = document.getElementById('pjdClRowMenu');
  const clHiddenCount = document.getElementById('pjdClHiddenCount');
  const CL_COL_STORAGE = `pjd-checklist-columns-${PROJECT_SLUG}`;
  const clFilterState = { cumplimiento: new Set(['__all']), status: new Set(['__all']), prioridad: new Set(['__all']) };
  let activeOptionsRow = null;
  let editingChecklistRow = null;

  function positionChecklistMenu(btn, menu) {
    if (!btn || !menu) return;
    closeChecklistMenus(menu);
    menu.classList.add('is-open');
    menu.setAttribute('aria-hidden', 'false');
    btn.setAttribute('aria-expanded', 'true');
    const rect = btn.getBoundingClientRect();
    const menuWidth = menu.offsetWidth || 260;
    const left = Math.max(12, Math.min(rect.right - menuWidth, window.innerWidth - menuWidth - 12));
    menu.style.left = left + 'px';
    menu.style.top = Math.min(rect.bottom + 8, window.innerHeight - 80) + 'px';
  }
  function closeChecklistRowMenu() {
    if (!clRowMenu) return;
    clRowMenu.classList.remove('is-open');
    clRowMenu.setAttribute('aria-hidden', 'true');
    activeOptionsRow = null;
  }
  function positionChecklistRowMenu(btn, idx) {
    if (!btn || !clRowMenu) return;
    closeChecklistMenus();
    activeOptionsRow = idx;
    clRowMenu.classList.add('is-open');
    clRowMenu.setAttribute('aria-hidden', 'false');
    const rect = btn.getBoundingClientRect();
    const menuWidth = clRowMenu.offsetWidth || 218;
    const left = Math.max(12, Math.min(rect.right - menuWidth, window.innerWidth - menuWidth - 12));
    clRowMenu.style.left = left + 'px';
    clRowMenu.style.top = Math.min(rect.bottom + 6, window.innerHeight - 170) + 'px';
  }

  function closeChecklistMenus(except = null) {
    [clFiltersMenu, clColumnsMenu, clExportMenu, clDownloadMenu].forEach(menu => {
      if (!menu || menu === except) return;
      menu.classList.remove('is-open');
      menu.setAttribute('aria-hidden', 'true');
    });
    [clFiltersBtn, clColumnsBtn, clExportBtn, clDownloadBtn].forEach(btn => btn?.setAttribute('aria-expanded', 'false'));
    closeChecklistRowMenu();
  }

  clFiltersBtn?.addEventListener('click', (e) => { e.stopPropagation(); positionChecklistMenu(clFiltersBtn, clFiltersMenu); });
  clColumnsBtn?.addEventListener('click', (e) => { e.stopPropagation(); positionChecklistMenu(clColumnsBtn, clColumnsMenu); });
  clExportBtn?.addEventListener('click', (e) => { e.stopPropagation(); positionChecklistMenu(clExportBtn, clExportMenu); });
  clDownloadBtn?.addEventListener('click', (e) => { e.stopPropagation(); positionChecklistMenu(clDownloadBtn, clDownloadMenu); });
  [clFiltersMenu, clColumnsMenu, clExportMenu, clDownloadMenu, clRowMenu].forEach(menu => menu?.addEventListener('click', e => e.stopPropagation()));

  function getHiddenColumns() {
    try { return new Set(JSON.parse(localStorage.getItem(CL_COL_STORAGE) || '[]')); }
    catch (_) { return new Set(); }
  }
  function setHiddenColumns(cols) {
    localStorage.setItem(CL_COL_STORAGE, JSON.stringify([...cols]));
  }
  function applyChecklistColumns() {
    const hidden = getHiddenColumns();
    document.querySelectorAll('.pjd-cl-table [data-col]').forEach(el => {
      el.classList.toggle('is-hidden-col', hidden.has(el.dataset.col));
    });
    document.querySelectorAll('[data-column-toggle]').forEach(btn => {
      const col = btn.dataset.columnToggle;
      const active = !hidden.has(col);
      btn.classList.toggle('is-active', active);
      const check = btn.querySelector('.pjd-cl-menu-check');
      if (check) check.textContent = active ? '' : '';
    });
    if (clHiddenCount) {
      clHiddenCount.textContent = hidden.size;
      clHiddenCount.classList.toggle('is-visible', hidden.size > 0);
    }
  }

  clColumnsMenu?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-column-toggle]');
    if (!btn) return;
    const col = btn.dataset.columnToggle;
    const hidden = getHiddenColumns();
    if (hidden.has(col)) hidden.delete(col); else hidden.add(col);
    setHiddenColumns(hidden);
    applyChecklistColumns();
  });
  document.getElementById('pjdClShowAllColumns')?.addEventListener('click', () => { setHiddenColumns(new Set()); applyChecklistColumns(); });
  document.getElementById('pjdClCloseColumns')?.addEventListener('click', () => closeChecklistMenus());
  applyChecklistColumns();

  function updateFilterMenuChecks(group) {
    const selected = clFilterState[group];
    document.querySelectorAll(`[data-filter-group="${group}"]`).forEach(btn => {
      const active = selected.has(btn.dataset.filterValue);
      btn.classList.toggle('is-active', active);
      const sq = btn.querySelector('.pjd-cl-menu-square');
      if (sq) sq.textContent = active ? '' : '';
    });
  }
  function applyChecklistFilters() {
    const q = (clSearch?.value || '').trim().toLowerCase();
    let visibleCount = 0;
    clBody.querySelectorAll('tr[data-row]').forEach(r => {
      const textMatch = !q || r.textContent.toLowerCase().includes(q);
      const c = r.dataset.cumplimiento || '-';
      const s = r.dataset.status || 'Pendiente';
      const p = r.dataset.prioridad || 'Media';
      const cMatch = clFilterState.cumplimiento.has('__all') || clFilterState.cumplimiento.has(c);
      const sMatch = clFilterState.status.has('__all') || clFilterState.status.has(s);
      const pMatch = clFilterState.prioridad.has('__all') || clFilterState.prioridad.has(p);
      const match = textMatch && cMatch && sMatch && pMatch;
      r.style.display = match ? '' : 'none';
      if (match) visibleCount++;
      const detail = clBody.querySelector(`tr[data-detail="${r.dataset.row}"]`);
      if (detail && !match) { detail.style.display = 'none'; r.classList.remove('is-expanded'); }
    });
    let empty = document.getElementById('pjdClNoFilterResults');
    if (!empty) {
      empty = document.createElement('tr');
      empty.id = 'pjdClNoFilterResults';
      empty.className = 'pjd-cl-no-filter-results';
      empty.innerHTML = '<td colspan="9">No hay requisitos que coincidan con los filtros.</td>';
      clBody.appendChild(empty);
    }
    empty.style.display = visibleCount === 0 ? '' : 'none';
  }

  clFiltersMenu?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-filter-group]');
    if (!btn) return;
    const group = btn.dataset.filterGroup;
    const value = btn.dataset.filterValue;
    const selected = clFilterState[group];
    if (value === '__all') {
      selected.clear(); selected.add('__all');
    } else {
      selected.delete('__all');
      if (selected.has(value)) selected.delete(value); else selected.add(value);
      if (!selected.size) selected.add('__all');
    }
    updateFilterMenuChecks(group);
    applyChecklistFilters();
  });
  document.getElementById('pjdClClearFilters')?.addEventListener('click', () => {
    Object.keys(clFilterState).forEach(group => { clFilterState[group].clear(); clFilterState[group].add('__all'); updateFilterMenuChecks(group); });
    if (clSearch) clSearch.value = '';
    applyChecklistFilters();
  });
  document.getElementById('pjdClCloseFilters')?.addEventListener('click', () => closeChecklistMenus());

  function getChecklistExportRows(onlyVisible = true) {
    const rows = [];
    clBody.querySelectorAll('tr[data-row]').forEach(r => {
      if (onlyVisible && r.style.display === 'none') return;
      rows.push({
        requisito: r.querySelector('.pjd-cl-requisito-text')?.textContent.trim() || '',
        formato: r.querySelector('[data-col="formato"]')?.textContent.trim() || '',
        categoria: r.querySelector('[data-col="categoria"]')?.textContent.trim() || '',
        aplicabilidad: r.querySelector('[data-col="aplicabilidad"]')?.textContent.trim() || '',
        obligatorio: r.querySelector('[data-col="obligatorio"]')?.textContent.trim() || '',
        cumplimiento: r.dataset.cumplimiento || '-',
        status: r.dataset.status || 'Pendiente',
        prioridad: r.dataset.prioridad || 'Media'
      });
    });
    return rows;
  }
  function downloadChecklistCsv() {
    const headers = ['Requisito','Formato','Categoría','Aplicabilidad','Obligatorio','Cumplimiento','Status','Prioridad'];
    const rows = getChecklistExportRows(true).map(r => [r.requisito,r.formato,r.categoria,r.aplicabilidad,r.obligatorio,r.cumplimiento,r.status,r.prioridad]);
    const csv = [headers, ...rows].map(row => row.map(v => `"${String(v).replace(/"/g,'""')}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob); const a = document.createElement('a');
    a.href = url; a.download = `checklist-${PROJECT_SLUG}.csv`; a.click(); URL.revokeObjectURL(url);
    showToast('CSV exportado', 'success');
  }
  function printChecklistPdf() {
    const rows = getChecklistExportRows(true);
    const htmlRows = rows.map(r => `<tr><td>${escapeHtml(r.requisito)}</td><td>${escapeHtml(r.formato)}</td><td>${escapeHtml(r.categoria)}</td><td>${escapeHtml(r.aplicabilidad)}</td><td>${escapeHtml(r.obligatorio)}</td><td>${escapeHtml(r.cumplimiento)}</td><td>${escapeHtml(r.status)}</td><td>${escapeHtml(r.prioridad)}</td></tr>`).join('');
    const w = window.open('', '_blank');
    if (!w) { window.print(); return; }
    w.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>Checklist - ${escapeHtml(PROJECT_NAME)}</title><style>body{font-family:Arial,sans-serif;padding:24px;color:#111}h1{font-size:20px;margin:0 0 18px}table{width:100%;border-collapse:collapse;font-size:12px}th,td{border:1px solid #e5e7eb;padding:8px;text-align:left;vertical-align:top}th{background:#f9fafb}</style></head><body><h1>Checklist - ${escapeHtml(PROJECT_NAME)}</h1><table><thead><tr><th>Requisito</th><th>Formato</th><th>Categoría</th><th>Aplicabilidad</th><th>Oblig.</th><th>Cumpl.</th><th>Status</th><th>Prioridad</th></tr></thead><tbody>${htmlRows}</tbody></table></body></html>`);
    w.document.close(); w.focus(); setTimeout(() => w.print(), 250);
  }
  clExportMenu?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-export]');
    if (!btn) return;
    closeChecklistMenus();
    if (btn.dataset.export === 'csv') downloadChecklistCsv();
    if (btn.dataset.export === 'pdf') printChecklistPdf();
  });

  function toggleChecklistDetail(idx, forceOpen = null) {
    const tr = clBody.querySelector(`tr[data-row="${idx}"]`);
    const detail = clBody.querySelector(`tr[data-detail="${idx}"]`);
    if (!tr || !detail) return;
    const shouldOpen = forceOpen === null ? !tr.classList.contains('is-expanded') : !!forceOpen;
    tr.classList.toggle('is-expanded', shouldOpen);
    detail.style.display = shouldOpen ? '' : 'none';
  }

  function checklistRowIconSvg() {
    return '<svg class="pjd-cl-row-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="7" x2="19" y2="7"></line><line x1="5" y1="12" x2="19" y2="12"></line><line x1="5" y1="17" x2="14" y2="17"></line></svg>';
  }
  function checklistChevronSvg() {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>';
  }
  function checklistOptionsSvg() {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="5" cy="12" r="1.5"></circle><circle cx="12" cy="12" r="1.5"></circle><circle cx="19" cy="12" r="1.5"></circle></svg>';
  }
  function nextChecklistRowId() {
    const nums = Array.from(clBody.querySelectorAll('tr[data-row]'))
      .map(r => parseInt(r.dataset.row, 10))
      .filter(n => Number.isFinite(n));
    return nums.length ? String(Math.max(...nums) + 1) : '0';
  }
  function clearChecklistAddForm() {
    document.getElementById('pjdClNewReq').value = '';
    document.getElementById('pjdClNewFormato').value = '';
    document.getElementById('pjdClNewDesc').value = '';
  }
  function closeChecklistAddForm() {
    document.getElementById('pjdClAddForm')?.classList.remove('is-open');
    clearChecklistAddForm();
    setChecklistAddMode('add');
  }
  function setChecklistAddMode(mode, row = null) {
    editingChecklistRow = mode === 'edit' && row ? row.dataset.row : null;
    const form = document.getElementById('pjdClAddForm');
    const title = document.getElementById('pjdClAddTitle');
    const save = document.getElementById('pjdClAddSave');
    form?.classList.toggle('is-editing', !!editingChecklistRow);
    if (title) title.textContent = editingChecklistRow ? 'Editar requisito' : 'Agregar nuevo requisito';
    if (save) save.textContent = editingChecklistRow ? 'Guardar cambios' : 'Guardar';
  }
  function getChecklistRowData(row) {
    if (!row) return null;
    const idx = row.dataset.row;
    const detail = clBody.querySelector(`tr[data-detail="${idx}"]`);
    let desc = row.dataset.descripcion || '';
    if (!desc && detail) {
      const descEl = detail.querySelector('.pjd-cl-detail-description');
      if (descEl) desc = descEl.textContent.trim();
    }
    return {
      requisito: row.dataset.requisito || row.querySelector('.pjd-cl-requisito-text')?.textContent.trim() || '',
      formato: row.dataset.formato || row.querySelector('[data-col="formato"]')?.textContent.trim() || 'No aplica',
      descripcion: desc && desc !== 'Sin descripción adicional.' ? desc : '',
      categoria: row.querySelector('[data-col="categoria"]')?.textContent.trim() || '-',
      aplicabilidad: row.querySelector('[data-col="aplicabilidad"]')?.textContent.trim() || '-',
      obligatorio: row.querySelector('[data-col="obligatorio"]')?.textContent.trim() || '-',
      cumplimiento: row.dataset.cumplimiento || '-',
      status: row.dataset.status || 'Pendiente',
      prioridad: row.dataset.prioridad || 'Media',
      fecha_limite: row.dataset.fechaLimite || '',
      responsable: row.dataset.responsable || '',
      revisor: row.dataset.revisor || '',
      notas: row.dataset.notas ? row.dataset.notas.split('\n').filter(Boolean) : [],
      adjuntos: (() => { try { return JSON.parse(row.dataset.adjuntos || '[]'); } catch (_) { return []; } })()
    };
  }
  function openChecklistAddForm(mode = 'add', row = null) {
    setChecklistAddMode(mode, row);
    if (mode === 'edit' && row) {
      const data = getChecklistRowData(row);
      document.getElementById('pjdClNewReq').value = data.requisito || '';
      document.getElementById('pjdClNewFormato').value = data.formato === '-' ? '' : (data.formato || '');
      document.getElementById('pjdClNewDesc').value = data.descripcion || '';
    } else {
      clearChecklistAddForm();
    }
    const form = document.getElementById('pjdClAddForm');
    form?.classList.add('is-open');
    setTimeout(() => document.getElementById('pjdClNewReq')?.focus(), 80);
  }
  function updateChecklistDomItem(idx, item) {
    const row = clBody.querySelector(`tr[data-row="${idx}"]`);
    const detail = clBody.querySelector(`tr[data-detail="${idx}"]`);
    if (!row) return;

    const requisito = item.requisito || '';
    const formato = item.formato || 'No aplica';
    const descripcion = item.descripcion || '';
    const categoria = item.categoria || row.querySelector('[data-col="categoria"]')?.textContent.trim() || '-';
    const aplicabilidad = item.aplicabilidad || row.querySelector('[data-col="aplicabilidad"]')?.textContent.trim() || '-';
    const obligatorio = item.obligatorio || row.querySelector('[data-col="obligatorio"]')?.textContent.trim() || '-';

    updateRowDatasetFromItem(row, { ...getChecklistRowData(row), ...item, requisito, formato, descripcion });

    const reqText = row.querySelector('.pjd-cl-requisito-text');
    if (reqText) { reqText.textContent = requisito; reqText.title = requisito; }
    const formatoCell = row.querySelector('[data-col="formato"]');
    if (formatoCell) formatoCell.textContent = formato;
    const categoriaCell = row.querySelector('[data-col="categoria"]');
    if (categoriaCell) categoriaCell.textContent = categoria;
    const aplicabilidadCell = row.querySelector('[data-col="aplicabilidad"]');
    if (aplicabilidadCell) aplicabilidadCell.textContent = aplicabilidad;
    const obligatorioCell = row.querySelector('[data-col="obligatorio"]');
    if (obligatorioCell) obligatorioCell.textContent = obligatorio;

    applyChecklistCumplimiento(row, item.cumplimiento || row.dataset.cumplimiento || '-');
    applyChecklistStatus(row, item.status || row.dataset.status || 'Pendiente');

    if (detail) {
      detail.innerHTML = checklistDetailHtml({
        idx,
        descripcion,
        prioridad: item.prioridad || row.dataset.prioridad || 'Media'
      });
    }
  }

  function createChecklistDomItem({ id = '', requisito, formato, descripcion, categoria = '-', aplicabilidad = '-', obligatorio = '-', cumplimiento = '-', status = 'Pendiente', prioridad = 'Media', fecha_limite = '', responsable = '', revisor = '', notas = [], adjuntos = [] }, skipSave = false) {
    const idx = id || nextChecklistRowId();
    const safeReq = escapeHtml(requisito);
    const safeFormato = escapeHtml(formato || 'No aplica');
    const safeDesc = escapeHtml(descripcion || 'Sin descripción adicional.');
    const tr = document.createElement('tr');
    tr.dataset.row = idx;
    tr.dataset.itemId = id || '';
    tr.dataset.cumplimiento = cumplimiento || '-';
    tr.dataset.status = status || 'Pendiente';
    tr.dataset.prioridad = prioridad || 'Media';
    tr.dataset.requisito = requisito;
    tr.dataset.formato = formato || 'No aplica';
    tr.dataset.descripcion = descripcion || '';
    tr.dataset.fechaLimite = fecha_limite || '';
    tr.dataset.responsable = responsable || '';
    tr.dataset.revisor = revisor || '';
    tr.dataset.notas = Array.isArray(notas) ? notas.join('\n') : (notas || '');
    tr.dataset.adjuntos = JSON.stringify(adjuntos || []);
    tr.dataset.added = '1';
    tr.innerHTML = `
      <td class="pjd-cl-check-cell"><button type="button" class="pjd-cl-row-toggle" data-toggle="${idx}" title="Ver fuente y detalle">${checklistChevronSvg()}</button></td>
      <td data-col="requisito"><div class="pjd-cl-requisito">${checklistRowIconSvg()}<span class="pjd-cl-requisito-text" title="${safeReq}">${safeReq}</span></div></td>
      <td class="pjd-cl-cell-muted" data-col="formato">${safeFormato}</td>
      <td class="pjd-cl-cell-muted" data-col="categoria">${escapeHtml(categoria || '-')}</td>
      <td class="pjd-cl-cell-muted" data-col="aplicabilidad">${escapeHtml(aplicabilidad || '-')}</td>
      <td class="pjd-cl-cell-center" data-col="obligatorio">${escapeHtml(obligatorio || '-')}</td>
      <td data-col="cumplimiento"><button type="button" class="pjd-cl-cumplimiento-btn" data-cumplimiento-toggle="${idx}" title="Cambiar cumplimiento"><span class="pjd-cl-cumple-dot"></span><span class="pjd-cl-cumple-text">-</span></button></td>
      <td data-col="status"><button type="button" class="pjd-cl-status is-pendiente" data-status-toggle="${idx}">${renderChecklistStatusMarkup('Pendiente')}</button></td>
      <td class="pjd-cl-cell-center" data-col="opciones"><button type="button" class="pjd-cl-options" data-options="${idx}" title="Opciones">${checklistOptionsSvg()}</button></td>`;

    const detail = document.createElement('tr');
    detail.className = 'pjd-cl-detail-row';
    detail.dataset.detail = idx;
    detail.style.display = 'none';
    detail.innerHTML = checklistDetailHtml({ idx, descripcion: descripcion || '', prioridad: prioridad || 'Media' });

    const empty = clBody.querySelector('.pjd-cl-no-results')?.closest('tr');
    if (empty) empty.remove();
    clBody.appendChild(tr);
    clBody.appendChild(detail);
    applyChecklistCumplimiento(tr, cumplimiento || '-');
    applyChecklistStatus(tr, status || 'Pendiente');
    applyChecklistColumns();
    updateCounters();
    applyChecklistFilters();
    toggleChecklistDetail(idx, true);
    tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
    if (!skipSave) saveChecklist();
  }

  clRowMenu?.addEventListener('click', async (e) => {
    const actionBtn = e.target.closest('[data-row-action]');
    if (!actionBtn || activeOptionsRow === null) return;
    const action = actionBtn.dataset.rowAction;
    const row = clBody.querySelector(`tr[data-row="${activeOptionsRow}"]`);
    const detail = clBody.querySelector(`tr[data-detail="${activeOptionsRow}"]`);
    if (!row) { closeChecklistRowMenu(); return; }

    if (action === 'edit') {
      openChecklistAddForm('edit', row);
      closeChecklistRowMenu();
      return;
    }

    if (action === 'duplicate') {
      try {
        const json = await postChecklistBackend('duplicate', { id: activeOptionsRow, idx: activeOptionsRow });
        const item = json.item || { ...getChecklistRowData(row), requisito: `${getChecklistRowData(row).requisito} copia` };
        createChecklistDomItem(item, true);
        closeChecklistRowMenu();
        updateCounters();
        applyChecklistFilters();
        showToast('Requisito duplicado', 'success');
      } catch (err) {
        showToast(err.message || 'Error al duplicar', 'error');
      }
      return;
    }

    if (action === 'delete') {
      if (!await pjdConfirm('¿Eliminar este requisito?', { title: 'Eliminar requisito', okText: 'Eliminar', tone: 'danger' })) return;
      try {
        await postChecklistBackend('delete', { id: activeOptionsRow, idx: activeOptionsRow });
        detail?.remove();
        row.remove();
        closeChecklistRowMenu();
        updateCounters();
        applyChecklistFilters();
        showToast('Requisito eliminado', 'success');
      } catch (err) {
        showToast(err.message || 'Error al eliminar', 'error');
      }
    }
  });


  function positionSmallChecklistPopover(pop, rect) {
    if (!pop || !rect) return;
    pop.classList.add('is-open');
    const popWidth = pop.offsetWidth || 260;
    const popHeight = pop.offsetHeight || 260;
    const left = Math.max(12, Math.min(rect.left, window.innerWidth - popWidth - 12));
    const spaceBelow = window.innerHeight - rect.bottom;
    const top = spaceBelow >= Math.min(popHeight, 220)
      ? rect.bottom + 6
      : Math.max(12, rect.top - popHeight - 6);
    pop.style.left = left + 'px';
    pop.style.top = Math.max(12, Math.min(top, window.innerHeight - popHeight - 12)) + 'px';
  }

  function checklistDetailHtml({ idx, descripcion = '', prioridad = 'Media' }) {
    const safeDesc = escapeHtml(descripcion || 'Sin descripción adicional.');
    const descClass = descripcion ? 'pjd-cl-detail-text pjd-cl-detail-description' : 'pjd-cl-detail-text is-muted pjd-cl-detail-description';
    const p = prioridad || 'Media';
    return `<td colspan="9" style="padding:0"><div class="pjd-cl-detail"><div class="pjd-cl-detail-panel"><div class="pjd-cl-detail-section"><div class="pjd-cl-detail-label">Descripción:</div><p class="${descClass}">${safeDesc}</p></div><div class="pjd-cl-detail-section"><div class="pjd-cl-detail-controls"><div class="pjd-cl-detail-control-row"><span class="pjd-cl-detail-label" style="margin:0;">Prioridad:</span><div class="pjd-cl-priority-group" data-priority-group="${idx}"><button type="button" class="pjd-cl-priority-btn ${p === 'Alta' ? 'is-active' : ''}" data-priority-set="Alta">Alta</button><button type="button" class="pjd-cl-priority-btn ${p === 'Media' ? 'is-active' : ''}" data-priority-set="Media">Media</button><button type="button" class="pjd-cl-priority-btn ${p === 'Baja' ? 'is-active' : ''}" data-priority-set="Baja">Baja</button></div></div><div class="pjd-cl-detail-control-row"><span class="pjd-cl-detail-label" style="margin:0;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path></svg>Fecha límite:</span><input type="date" class="pjd-cl-detail-date" data-detail-date="${idx}"></div><div class="pjd-cl-detail-control-row"><span class="pjd-cl-detail-label" style="margin:0;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"></path><circle cx="12" cy="7" r="4"></circle></svg>Responsable:</span><select class="pjd-cl-detail-select" data-detail-responsable="${idx}"><option>Sin asignar</option></select></div><div class="pjd-cl-detail-control-row"><span class="pjd-cl-detail-label" style="margin:0;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"></path><circle cx="12" cy="7" r="4"></circle></svg>Revisor:</span><select class="pjd-cl-detail-select" data-detail-revisor="${idx}"><option>Sin asignar</option></select></div></div></div><div class="pjd-cl-detail-section"><div class="pjd-cl-detail-control-row"><span class="pjd-cl-detail-label" style="margin:0;">Notas:</span><button type="button" class="pjd-cl-detail-link" data-detail-note="${idx}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>Agregar</button></div><p class="pjd-cl-detail-empty">No hay notas agregadas.</p></div><div class="pjd-cl-detail-section"><div class="pjd-cl-detail-control-row"><span class="pjd-cl-detail-label" style="margin:0;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg>Documentos Adjuntos:</span><button type="button" class="pjd-cl-detail-link" data-detail-attach="${idx}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05 12 20.49a6 6 0 0 1-8.49-8.49l9.44-9.44a4 4 0 0 1 5.66 5.66L9.17 17.66a2 2 0 0 1-2.83-2.83l8.49-8.49"></path></svg>Adjuntar</button></div><p class="pjd-cl-detail-empty">No hay documentos adjuntos. Haz clic en "Adjuntar Documento" para agregar.</p></div></div></div></td>`;
  }

  clBody?.addEventListener('click', (e) => {
    const optBtn = e.target.closest('[data-options]');
    if (optBtn) { positionChecklistRowMenu(optBtn, optBtn.dataset.options); e.stopPropagation(); return; }

    const tBtn = e.target.closest('[data-toggle]');
    if (tBtn) { toggleChecklistDetail(tBtn.dataset.toggle); e.stopPropagation(); return; }

    const priorityBtn = e.target.closest('[data-priority-set]');
    if (priorityBtn) {
      const detailRow = priorityBtn.closest('tr[data-detail]');
      const idx = detailRow?.dataset.detail;
      const row = idx ? clBody.querySelector(`tr[data-row="${idx}"]`) : null;
      if (row) {
        row.dataset.prioridad = priorityBtn.dataset.prioritySet;
        detailRow.querySelectorAll('[data-priority-set]').forEach(btn => btn.classList.toggle('is-active', btn === priorityBtn));
        saveChecklist();
      }
      e.stopPropagation();
      return;
    }

    const sourceBtn = e.target.closest('.pjd-cl-source-btn[data-cita]');
    if (sourceBtn) { openCita(sourceBtn.getAttribute('data-cita')); e.stopPropagation(); return; }

    const sourceLink = e.target.closest('a.pjd-cl-source-btn');
    if (sourceLink) { e.stopPropagation(); return; }

    const row = e.target.closest('tr[data-row]');
    const clickedControl = e.target.closest('[data-cumplimiento-toggle], [data-status-toggle], [data-options]');
    if (row && !clickedControl) { toggleChecklistDetail(row.dataset.row); e.stopPropagation(); return; }

    const cumpBtn = e.target.closest('[data-cumplimiento-toggle]');
    if (cumpBtn) {
      activeCumpRow = cumpBtn.dataset.cumplimientoToggle;
      const rect = cumpBtn.getBoundingClientRect();
      positionSmallChecklistPopover(cumpPop, rect);
      cumpPop.classList.add('is-open'); statPop.classList.remove('is-open');
      e.stopPropagation(); return;
    }
    const statBtn = e.target.closest('[data-status-toggle]');
    if (statBtn) {
      activeStatusRow = statBtn.dataset.statusToggle;
      const rect = statBtn.getBoundingClientRect();
      positionSmallChecklistPopover(statPop, rect);
      statPop.classList.add('is-open'); cumpPop.classList.remove('is-open');
      e.stopPropagation();
    }
  });

  cumpPop?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-set-cumplimiento]');
    if (!btn || activeCumpRow === null) return;
    const val = btn.dataset.setCumplimiento;
    const row = clBody.querySelector(`tr[data-row="${activeCumpRow}"]`);
    if (!row) return;
    applyChecklistCumplimiento(row, val);
    cumpPop.classList.remove('is-open'); updateCounters(); applyChecklistFilters(); saveChecklist();
  });

  statPop?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-set-status]');
    if (!btn || activeStatusRow === null) return;
    const val = btn.dataset.setStatus;
    const row = clBody.querySelector(`tr[data-row="${activeStatusRow}"]`);
    if (!row) return;
    applyChecklistStatus(row, val);
    statPop.classList.remove('is-open'); updateCounters(); applyChecklistFilters(); saveChecklist();
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('[data-cumplimiento-toggle]') && !e.target.closest('#pjdClCumpPop')) cumpPop?.classList.remove('is-open');
    if (!e.target.closest('[data-status-toggle]') && !e.target.closest('#pjdClStatusPop')) statPop?.classList.remove('is-open');
    if (!e.target.closest('.pjd-cl-menu') && !e.target.closest('.pjd-cl-row-menu') && !e.target.closest('#pjdClFiltersBtn') && !e.target.closest('#pjdClColumnsBtn') && !e.target.closest('#pjdClExportBtn') && !e.target.closest('#pjdClDownload')) closeChecklistMenus();
  });

  clSearch?.addEventListener('input', applyChecklistFilters);

  async function saveChecklist() {
    const rows = Array.from(clBody.querySelectorAll('tr[data-row]')).map(r => {
      const data = getChecklistRowData(r);
      return {
        id: r.dataset.row,
        idx: r.dataset.row,
        requisito: data.requisito,
        descripcion: data.descripcion,
        formato: data.formato,
        categoria: data.categoria,
        aplicabilidad: data.aplicabilidad,
        obligatorio: data.obligatorio,
        cumplimiento: data.cumplimiento,
        status: data.status,
        prioridad: data.prioridad,
        fecha_limite: data.fecha_limite,
        responsable: data.responsable,
        revisor: data.revisor,
        notas: data.notas
      };
    });
    try { const fd = new FormData(); fd.append('_token', CSRF); fd.append('items', JSON.stringify(rows)); await fetch(CHECKLIST_URL, { method:'POST', headers:{'Accept':'application/json'}, body: fd, credentials:'same-origin' }); } catch (_) {}
  }

  document.getElementById('pjdClReanalisis')?.addEventListener('click', async () => {
    if (!await pjdConfirm('Esto regenerará TODO el checklist con IA. ¿Continuar?', { title: 'Regenerar checklist', okText: 'Regenerar', tone: 'danger' })) return;
    const btn = document.getElementById('pjdClReanalisis');
    const original = btn.innerHTML; btn.disabled = true; btn.innerHTML = ' Generando…';
    try { const fd = new FormData(); fd.append('_token', CSRF); fd.append('regenerate', '1'); const res = await fetch(CHECKLIST_URL, { method:'POST', headers:{'Accept':'application/json'}, body: fd, credentials:'same-origin' }); if (res.ok) location.reload(); else alert('Error al regenerar'); }
    catch (e) { alert('Error de red'); } finally { btn.disabled = false; btn.innerHTML = original; }
  });

  document.getElementById('pjdClAddBtn')?.addEventListener('click', () => openChecklistAddForm('add'));
  document.getElementById('pjdClAddCancel')?.addEventListener('click', closeChecklistAddForm);
  document.getElementById('pjdClAddForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const requisito = document.getElementById('pjdClNewReq')?.value.trim() || '';
    const formato = document.getElementById('pjdClNewFormato')?.value.trim() || 'No aplica';
    const descripcion = document.getElementById('pjdClNewDesc')?.value.trim() || '';
    if (!requisito) { document.getElementById('pjdClNewReq')?.focus(); return; }

    const item = {
      requisito,
      formato,
      descripcion,
      categoria: 'Legal-Administrativo',
      aplicabilidad: 'Único',
      obligatorio: 'Sí',
      cumplimiento: '-',
      status: 'Pendiente',
      prioridad: 'Media'
    };

    const saveBtn = document.getElementById('pjdClAddSave');
    const original = saveBtn?.textContent || 'Guardar';
    if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Guardando...'; }

    try {
      if (editingChecklistRow !== null) {
        const currentRow = clBody.querySelector(`tr[data-row="${editingChecklistRow}"]`);
        const currentData = getChecklistRowData(currentRow) || {};
        const payload = { ...currentData, ...item };
        const json = await postChecklistBackend('update', { id: editingChecklistRow, idx: editingChecklistRow, item: payload });
        updateChecklistDomItem(editingChecklistRow, json.item || payload);
        closeChecklistAddForm();
        updateCounters();
        applyChecklistFilters();
        showToast('Requisito actualizado', 'success');
        return;
      }

      const json = await postChecklistBackend('create', { item });
      createChecklistDomItem(json.item || item, true);
      closeChecklistAddForm();
      updateCounters();
      applyChecklistFilters();
      showToast('Requisito agregado', 'success');
    } catch (err) {
      showToast(err.message || 'Error al guardar requisito', 'error');
    } finally {
      if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = original; }
    }
  });


  function downloadChecklistExcel() {
    if (typeof XLSX === 'undefined') { showToast('Excel no disponible', 'error'); return; }
    const headers = ['Requisito','Formato','Categoría','Aplicabilidad','Obligatorio','Cumplimiento','Status','Prioridad'];
    const rows = getChecklistExportRows(true).map(r => [r.requisito,r.formato,r.categoria,r.aplicabilidad,r.obligatorio,r.cumplimiento,r.status,r.prioridad]);
    const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
    ws['!cols'] = headers.map((h, i) => { let max = h.length; rows.forEach(r => { const len = (r[i]||'').toString().length; if (len > max) max = len; }); return { wch: Math.min(Math.max(max + 2, 14), 70) }; });
    const wb = XLSX.utils.book_new(); XLSX.utils.book_append_sheet(wb, ws, 'Checklist');
    XLSX.writeFile(wb, `checklist-${PROJECT_SLUG}.xlsx`);
    showToast('Excel descargado', 'success');
  }
  clDownloadMenu?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-download-list]');
    if (!btn) return;
    closeChecklistMenus();
    if (btn.dataset.downloadList === 'excel') window.location.href = `${CHECKLIST_EXPORT_BASE_URL}/excel`;
    if (btn.dataset.downloadList === 'pdf') window.location.href = `${CHECKLIST_EXPORT_BASE_URL}/pdf`;
  });

  clBody?.addEventListener('change', async (e) => {
    const input = e.target.closest('[data-detail-date], [data-detail-responsable], [data-detail-revisor]');
    if (!input) return;
    const detail = input.closest('tr[data-detail]');
    const idx = detail?.dataset.detail;
    const row = idx ? clBody.querySelector(`tr[data-row="${idx}"]`) : null;
    if (!row) return;

    if (input.matches('[data-detail-date]')) row.dataset.fechaLimite = input.value || '';
    if (input.matches('[data-detail-responsable]')) row.dataset.responsable = input.value || '';
    if (input.matches('[data-detail-revisor]')) row.dataset.revisor = input.value || '';

    try {
      await postChecklistBackend('update', { id: idx, idx, item: getChecklistRowData(row) });
      showToast('Checklist guardado', 'success');
    } catch (err) {
      showToast(err.message || 'Error al guardar detalle', 'error');
    }
  });

  clBody?.addEventListener('click', async (e) => {
    const noteBtn = e.target.closest('[data-detail-note]');
    if (!noteBtn) return;
    e.preventDefault();
    const idx = noteBtn.dataset.detailNote;
    const row = clBody.querySelector(`tr[data-row="${idx}"]`);
    if (!row) return;

    const body = await pjdPrompt('Agregar nota', '', { title: 'Agregar nota', placeholder: 'Escribe la nota…', okText: 'Agregar' });
    if (!body || !body.trim()) return;

    try {
      const json = await postChecklistBackend('note', { id: idx, idx, body: body.trim() });
      const notes = Array.isArray(json.item?.notas) ? json.item.notas : [];
      row.dataset.notas = notes.map(n => typeof n === 'object' ? (n.body || '') : n).filter(Boolean).join('\n');
      showToast('Nota agregada', 'success');
    } catch (err) {
      showToast(err.message || 'Error al agregar nota', 'error');
    }
  });

  clBody?.addEventListener('click', async (e) => {
    const attachBtn = e.target.closest('[data-detail-attach]');
    if (!attachBtn) return;
    e.preventDefault();
    const idx = attachBtn.dataset.detailAttach;
    const row = clBody.querySelector(`tr[data-row="${idx}"]`);
    if (!row) return;

    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.multiple = true;
    fileInput.onchange = async () => {
      if (!fileInput.files.length) return;
      const fd = new FormData();
      fd.append('_token', CSRF);
      fd.append('id', idx);
      fd.append('idx', idx);
      Array.from(fileInput.files).forEach(file => fd.append('files[]', file));

      try {
        const res = await fetch(CHECKLIST_ATTACH_URL, { method: 'POST', headers: { 'Accept': 'application/json' }, body: fd, credentials: 'same-origin' });
        const json = await res.json();
        if (!res.ok || json.ok === false) throw new Error(json.message || 'No se pudo adjuntar el documento.');
        row.dataset.adjuntos = JSON.stringify(json.item?.adjuntos || json.adjuntos || []);
        showToast('Documento adjuntado', 'success');
      } catch (err) {
        showToast(err.message || 'Error al adjuntar', 'error');
      }
    };
    fileInput.click();
  });


  // ============ BORRADOR / REPORTE ============
  const borradorTabs = document.querySelectorAll('.pjd-borrador-tab');
  const borradorSections = document.querySelectorAll('[data-section-pane]');
  const draftDownloadBtn = document.getElementById('pjdDownloadDraft');
  const draftExpandBtn = document.getElementById('pjdBorradorExpand');

  borradorTabs.forEach(t => t.addEventListener('click', () => {
    const sec = t.dataset.section;
    borradorTabs.forEach(x => x.classList.toggle('is-active', x.dataset.section === sec));

    // Borrador y Reporte usan el mismo editor y el mismo contenido.
    // El tab Reporte solo solicita al backend generar/actualizar el reporte ejecutivo
    // y lo inserta dentro del editor principal para poder editarlo en línea.
    borradorSections.forEach(s => s.classList.toggle('is-active', s.dataset.sectionPane === 'borrador'));
    if (draftDownloadBtn) draftDownloadBtn.style.display = 'inline-flex';

    if (sec === 'reporte') {
      generateReport();
    }
  }));

  draftExpandBtn?.addEventListener('click', () => {
    const wrap = document.querySelector('.pjd-wrap');
    if (!wrap) return;
    const expanded = wrap.classList.toggle('is-conversation-collapsed');
    draftExpandBtn.setAttribute('title', expanded ? 'Mostrar conversación' : 'Ocultar conversación');
  });

  const draftEditor = document.getElementById('pjdDraftEditor');
  const draftToolbar = document.getElementById('pjdDraftToolbar');
  const draftStatus = document.getElementById('pjdDraftStatus');
  let draftSaveTimer = null;
  let draftIsSaving = false;

  function focusDraftEditor() {
    draftEditor?.focus({ preventScroll: true });
  }

  function runDraftCommand(cmd, value = null) {
    if (!draftEditor) return;
    focusDraftEditor();
    document.execCommand(cmd, false, value);
    refreshDraftToolbarState();
    scheduleDraftAutoSave();
  }

  function refreshDraftToolbarState() {
    if (!draftToolbar) return;
    ['bold', 'italic', 'underline', 'strikeThrough', 'insertUnorderedList', 'insertOrderedList'].forEach(cmd => {
      draftToolbar.querySelectorAll(`[data-draft-cmd="${cmd}"]`).forEach(btn => {
        try { btn.classList.toggle('is-active', document.queryCommandState(cmd)); } catch (e) {}
      });
    });
  }

  async function insertDraftTable() {
    const rows = Math.max(1, Math.min(20, parseInt(await pjdPrompt('Filas de la tabla', '3', { title: 'Filas de la tabla', type: 'number', inputmode: 'numeric' }) || '0', 10)));
    const cols = Math.max(1, Math.min(12, parseInt(await pjdPrompt('Columnas de la tabla', '3', { title: 'Columnas de la tabla', type: 'number', inputmode: 'numeric' }) || '0', 10)));
    if (!rows || !cols) return;
    let html = '<table><thead><tr>';
    for (let c = 0; c < cols; c++) html += `<th>Encabezado ${c + 1}</th>`;
    html += '</tr></thead><tbody>';
    for (let r = 0; r < rows; r++) {
      html += '<tr>';
      for (let c = 0; c < cols; c++) html += '<td>&nbsp;</td>';
      html += '</tr>';
    }
    html += '</tbody></table><p><br></p>';
    runDraftCommand('insertHTML', html);
  }

  draftToolbar?.addEventListener('click', async (e) => {
    const cmdBtn = e.target.closest('[data-draft-cmd]');
    const actionBtn = e.target.closest('[data-draft-action]');

    if (cmdBtn) {
      runDraftCommand(cmdBtn.dataset.draftCmd);
      return;
    }

    if (!actionBtn) return;
    const action = actionBtn.dataset.draftAction;

    if (action === 'link') {
      const url = await pjdPrompt('Pega la URL del enlace', '', { title: 'Insertar enlace', placeholder: 'https://…' });
      if (url) runDraftCommand('createLink', url);
    }

    if (action === 'image') {
      const url = await pjdPrompt('Pega la URL de la imagen', '', { title: 'Insertar imagen', placeholder: 'https://…' });
      if (url) runDraftCommand('insertImage', url);
    }

    if (action === 'table') {
      await insertDraftTable();
    }
  });

  draftToolbar?.querySelector('[data-draft-block]')?.addEventListener('change', (e) => {
    const value = e.target.value;
    if (value === 'BLOCKQUOTE') runDraftCommand('formatBlock', 'BLOCKQUOTE');
    else runDraftCommand('formatBlock', value);
  });

  draftToolbar?.querySelector('[data-draft-font]')?.addEventListener('change', (e) => {
    runDraftCommand('fontName', e.target.value);
  });

  draftToolbar?.querySelector('[data-draft-size]')?.addEventListener('change', (e) => {
    runDraftCommand('fontSize', e.target.value);
  });

  draftToolbar?.querySelectorAll('[data-draft-color]').forEach(input => {
    input.addEventListener('input', () => {
      runDraftCommand(input.dataset.draftColor, input.value);
    });
  });


  draftEditor?.addEventListener('keyup', refreshDraftToolbarState);
  draftEditor?.addEventListener('mouseup', refreshDraftToolbarState);

  async function saveDraftNow() {
    if (!draftEditor || draftIsSaving) return;
    draftIsSaving = true;
    if (draftStatus) draftStatus.textContent = 'Guardando...';
    const fd = new FormData();
    fd.append('_token', CSRF);
    fd.append('draft_content', draftEditor.innerHTML);
    try {
      const res = await fetch(DRAFT_URL, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: fd,
        credentials: 'same-origin'
      });
      if (draftStatus) draftStatus.textContent = res.ok ? 'Guardado ' + new Date().toLocaleTimeString() : 'Error al guardar';
    } catch (e) {
      if (draftStatus) draftStatus.textContent = 'Error de red';
    } finally {
      draftIsSaving = false;
    }
  }

  function scheduleDraftAutoSave(immediate = false) {
    clearTimeout(draftSaveTimer);
    if (immediate) { saveDraftNow(); return; }
    if (draftStatus) draftStatus.textContent = 'Cambios pendientes...';
    draftSaveTimer = setTimeout(saveDraftNow, 700);
  }

  draftEditor?.addEventListener('input', () => scheduleDraftAutoSave());
  draftEditor?.addEventListener('paste', () => setTimeout(() => scheduleDraftAutoSave(), 80));
  draftEditor?.addEventListener('blur', () => scheduleDraftAutoSave(true));

  function buildWordDocumentHtml(title, bodyHtml) {
    return `<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta charset="utf-8">
<title>${title}</title>
<!--[if gte mso 9]>
<xml>
  <w:WordDocument>
    <w:View>Print</w:View>
    <w:Zoom>100</w:Zoom>
    <w:DoNotOptimizeForBrowser/>
  </w:WordDocument>
</xml>
<![endif]-->
<style>
  @page WordSection1 { size: 8.5in 11.0in; margin: 0.75in 0.75in 0.75in 0.75in; }
  div.WordSection1 { page: WordSection1; }
  body { font-family: Arial, sans-serif; color: #111111; line-height: 1.55; }
  h1 { font-size: 22pt; margin: 0 0 14pt; }
  h2 { font-size: 18pt; margin: 14pt 0 8pt; }
  h3 { font-size: 14pt; margin: 12pt 0 6pt; }
  p { font-size: 11pt; margin: 0 0 8pt; }
  table { border-collapse: collapse; width: 100%; margin: 12pt 0; }
  th, td { border: 1px solid #d9d9d9; padding: 7pt 8pt; text-align: left; vertical-align: top; }
  th { background: #f3f4f6; font-weight: 700; }
  blockquote { border: 1px solid #e6e9ee; margin: 10pt 0; padding: 6pt 12pt; color: #333333; }
</style>
</head>
<body>
<div class="WordSection1">${bodyHtml || '<p></p>'}</div>
</body>
</html>`;
  }

  function downloadWordDocument(filename, title, bodyHtml) {
    const html = buildWordDocumentHtml(title, bodyHtml);
    const blob = new Blob(['\ufeff', html], { type: 'application/msword;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  }

  document.getElementById('pjdDownloadDraft')?.addEventListener('click', () => {
    downloadWordDocument(`reporte-ejecutivo-${PROJECT_SLUG}.doc`, `Reporte Ejecutivo - ${PROJECT_NAME}`, draftEditor?.innerHTML || '');
  });

  async function generateReport() {
    const reportTab = document.querySelector('.pjd-borrador-tab[data-section="reporte"]');
    const originalLabel = reportTab ? reportTab.innerHTML : '';

    if (!draftEditor) return;

    if (reportTab) {
      reportTab.disabled = true;
      reportTab.classList.add('is-loading');
    }

    if (draftStatus) draftStatus.textContent = 'Generando reporte ejecutivo...';

    try {
      const fd = new FormData();
      fd.append('_token', CSRF);
      fd.append('action', 'generate');

      const res = await fetch(REPORT_URL, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: fd,
        credentials: 'same-origin'
      });

      const json = await res.json();

      if (!res.ok || !json.ok || !json.html) {
        throw new Error(json.message || 'Error al generar reporte');
      }

      draftEditor.innerHTML = json.html;
      if (draftStatus) draftStatus.textContent = 'Reporte generado y guardado ' + new Date().toLocaleTimeString();
      scheduleDraftAutoSave(true);
      draftEditor.focus({ preventScroll: true });
    } catch (e) {
      if (draftStatus) draftStatus.textContent = e.message || 'Error de red';
      alert(e.message || 'Error de red');
    } finally {
      if (reportTab) {
        reportTab.disabled = false;
        reportTab.innerHTML = originalLabel;
        reportTab.classList.remove('is-loading');
      }
    }
  }

  // ============ DOCUMENTOS DEL PROYECTO ============
  const docSearch = document.getElementById('pjdDocSearch');
  const docCount = document.getElementById('pjdDocCount');
  const docCards = Array.from(document.querySelectorAll('[data-doc-card]'));

  function closeDocMenus(except = null) {
    document.querySelectorAll('[data-doc-menu].is-open').forEach(menu => {
      if (menu !== except) menu.classList.remove('is-open');
    });
  }

  function filterDocuments() {
    const q = (docSearch?.value || '').trim().toLowerCase();
    let visible = 0;

    docCards.forEach(card => {
      const text = card.dataset.docText || '';
      const match = !q || text.includes(q);
      card.style.display = match ? '' : 'none';
      if (match) visible++;
    });

    if (docCount) docCount.textContent = visible;
  }

  docSearch?.addEventListener('input', filterDocuments);

  document.addEventListener('click', async (e) => {
    const toggle = e.target.closest('[data-doc-toggle]');
    const menuBtn = e.target.closest('[data-doc-menu-btn]');
    const deleteBtn = e.target.closest('[data-doc-delete]');

    if (toggle) {
      const card = toggle.closest('[data-doc-card]');
      card?.classList.toggle('is-open');
      return;
    }

    if (menuBtn) {
      const card = menuBtn.closest('[data-doc-card]');
      const menu = card?.querySelector('[data-doc-menu]');
      if (!menu) return;
      const isOpen = menu.classList.contains('is-open');
      closeDocMenus(menu);
      menu.classList.toggle('is-open', !isOpen);
      return;
    }

    if (deleteBtn) {
      e.preventDefault();
      const card = deleteBtn.closest('[data-doc-card]');
      if (!await pjdConfirm('¿Eliminar este documento?', { title: 'Eliminar documento', okText: 'Eliminar', tone: 'danger' })) return;

      try {
        const res = await fetch(deleteBtn.dataset.docDelete, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json'
          }
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok || json.ok === false) throw new Error(json.message || 'No se pudo eliminar el documento');
        card?.remove();
        closeDocMenus();
        filterDocuments();
        showToast('Documento eliminado', 'success');
      } catch (err) {
        showToast(err.message || 'Error al eliminar documento', 'error');
      }
      return;
    }

    if (!e.target.closest('[data-doc-menu]')) {
      closeDocMenus();
    }
  });


  // ============ CITAS (drawer lateral con PDF.js + resaltado exacto) ============
  const docDrawer    = document.getElementById('pjdDocDrawer');
  const drawerFile   = document.getElementById('pjdDrawerFile');
  const drawerOpen   = document.getElementById('pjdDrawerOpen');
  const drawerQuote  = document.getElementById('pjdDrawerQuote');
  const drawerQText  = document.getElementById('pjdDrawerQuoteText');
  const drawerQMeta  = document.getElementById('pjdDrawerQuoteMeta');
  const drawerTransBtn = document.getElementById('pjdDrawerTranscript');
  const pdfScroll    = document.getElementById('pjdPdfScroll');
  const pdfContainer = document.getElementById('pjdPdfContainer');
  const pdfCanvas    = document.getElementById('pjdPdfCanvas');
  const pdfHl        = document.getElementById('pjdPdfHighlights');
  const pdfLoading   = document.getElementById('pjdPdfLoading');
  const pdfPageInd   = document.getElementById('pjdPdfPageInd');

  if (window.pdfjsLib) {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js';
  }

  const DOC_LOOKUP = {};
  (PROJECT_DOCS_LIST || []).forEach(d => {
    if (d.filename) DOC_LOOKUP[d.filename.toLowerCase()] = d;
    if (d.stored)   DOC_LOOKUP[d.stored.toLowerCase()]   = d;
  });
  function resolveDoc(name) {
    if (!name) return null;
    const n = String(name).toLowerCase();
    if (DOC_LOOKUP[n]) return DOC_LOOKUP[n];
    const base = n.split('/').pop().split('\\').pop();
    if (DOC_LOOKUP[base]) return DOC_LOOKUP[base];
    return (PROJECT_DOCS_LIST || []).find(d => (d.filename && d.filename.toLowerCase().includes(base)) || (d.stored && base.includes(d.stored.toLowerCase()))) || null;
  }

  function normPdf(s){
    return (s||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,' ').replace(/\s+/g,' ').trim();
  }

  // Busca el rango (inicio,fin) de la cita dentro del texto concatenado de la pagina.
  function findQuoteSpan(concat, quote) {
    if (!concat || !quote || quote.length < 4) return null;
    let s = concat.indexOf(quote);
    if (s !== -1) return [s, s + quote.length];

    const words = quote.split(' ').filter(Boolean);
    if (words.length < 2) return null;

    // recorta desde el final
    for (let n = words.length - 1; n >= Math.min(3, words.length); n--) {
      const sub = words.slice(0, n).join(' ');
      s = concat.indexOf(sub);
      if (s !== -1) return [s, s + sub.length];
    }
    // recorta desde el inicio
    for (let i = 1; i <= words.length - 3; i++) {
      const sub = words.slice(i).join(' ');
      s = concat.indexOf(sub);
      if (s !== -1) return [s, s + sub.length];
    }
    // bloque contiguo mas largo presente en la pagina
    let best = null;
    for (let i = 0; i < words.length - 1; i++) {
      for (let j = words.length; j > i + 1; j--) {
        const sub = words.slice(i, j).join(' ');
        const pos = concat.indexOf(sub);
        if (pos !== -1) {
          if (!best || sub.length > best.len) best = { start: pos, end: pos + sub.length, len: sub.length };
          break;
        }
      }
    }
    return best ? [best.start, best.end] : null;
  }

  // Concatena el texto de la pagina y mapea cada caracter al item que lo origina.
  function buildPageIndex(tc) {
    const items = [];
    let concat = '';
    const map = [];
    tc.items.forEach(it => {
      const norm = normPdf(it.str);
      if (!norm) return;
      const idx = items.length;
      items.push(it);
      if (concat.length) { concat += ' '; map.push(-1); }
      for (let k = 0; k < norm.length; k++) map.push(idx);
      concat += norm;
    });
    return { items, concat, map };
  }

  // Conjunto de palabras significativas de la cita (ignora conectores cortos).
  function quoteWordSet(quote) {
    const STOP = new Set(['para','como','esta','este','esto','estos','estas','sobre','entre','cuando','donde','todo','toda','todos','todas','cada','mas','segun','sera','seran','desde','hasta','pero','solo','tambien','dicha','dicho','dichos','dichas','sino','aquel','ello','ella','ellos','unos','unas','una','del','las','los','con','por','que']);
    return new Set(normPdf(quote).split(' ').filter(w => w.length >= 4 && !STOP.has(w)));
  }

  // Encuentra el grupo de items mas denso en palabras de la cita (para citas parafraseadas).
  function matchingCluster(items, quoteSet) {
    if (!quoteSet || !quoteSet.size) return { idxs: [], score: 0 };
    const matched = [];
    items.forEach((it, idx) => {
      const ws = normPdf(it.str).split(' ').filter(Boolean);
      let c = 0; ws.forEach(w => { if (quoteSet.has(w)) c++; });
      if (c > 0) matched.push({ idx, c });
    });
    if (!matched.length) return { idxs: [], score: 0 };

    const groupScore = g => g.reduce((s, m) => s + m.c, 0);
    let best = [], cur = [matched[0]];
    for (let k = 1; k < matched.length; k++) {
      if (matched[k].idx - cur[cur.length - 1].idx <= 6) cur.push(matched[k]);
      else { if (groupScore(cur) > groupScore(best)) best = cur; cur = [matched[k]]; }
    }
    if (groupScore(cur) > groupScore(best)) best = cur;
    return { idxs: best.map(m => m.idx), score: groupScore(best) };
  }

  // Pinta el resaltado de la cita sobre la pagina renderizada. Devuelve el primer rect (para scroll).
  async function paintHighlights(page, viewport, scale) {
    pdfHl.innerHTML = '';
    const quote = normPdf(PDF_STATE.quote);
    if (!quote || quote.length < 4) return null;

    const tc = await page.getTextContent();
    const { items, concat, map } = buildPageIndex(tc);

    // 1) match exacto / contiguo
    let idxs = [];
    const span = findQuoteSpan(concat, quote);
    if (span) {
      const set = new Set();
      for (let i = span[0]; i < span[1] && i < map.length; i++) if (map[i] >= 0) set.add(map[i]);
      idxs = [...set];
    }

    // 2) fallback por palabras clave si el match exacto fue pobre (cita parafraseada)
    if (idxs.length < 3) {
      const cluster = matchingCluster(items, quoteWordSet(PDF_STATE.quote));
      if (cluster.idxs.length > idxs.length) idxs = cluster.idxs;
    }
    if (!idxs.length) return null;

    let first = null, firstTop = Infinity;
    idxs.forEach(idx => {
      const it = items[idx];
      const t = pdfjsLib.Util.transform(viewport.transform, it.transform);
      const fh = Math.hypot(t[2], t[3]);
      const top = t[5] - fh;
      const div = document.createElement('div');
      div.className = 'pjd-pdf-hl';
      div.style.left   = t[4] + 'px';
      div.style.top    = top + 'px';
      div.style.width  = ((it.width || 0) * scale) + 'px';
      div.style.height = (fh * 1.2) + 'px';
      pdfHl.appendChild(div);
      if (top < firstTop) { firstTop = top; first = div; }
    });
    return first;
  }

  // Elige la pagina con la cita: prioriza match exacto, si no, la de mayor coincidencia de palabras.
  async function findQuotePage(quote, preferida) {
    const Q = normPdf(quote);
    if (!Q || Q.length < 4 || !PDF_STATE.doc) return preferida;
    const qset = quoteWordSet(quote);
    const minScore = Math.max(3, Math.ceil(qset.size * 0.4));

    const scorePage = async (p) => {
      try {
        const page = await PDF_STATE.doc.getPage(p);
        const tc = await page.getTextContent();
        const { items, concat } = buildPageIndex(tc);
        if (findQuoteSpan(concat, Q)) return 1e6; // el match exacto siempre gana
        return matchingCluster(items, qset).score;
      } catch (e) { return 0; }
    };

    const prefScore = await scorePage(preferida);
    if (prefScore >= minScore) return preferida;

    let bestP = preferida, bestS = prefScore;
    for (let p = 1; p <= PDF_STATE.total; p++) {
      if (p === preferida) continue;
      const sc = await scorePage(p);
      if (sc > bestS) { bestS = sc; bestP = p; }
    }
    return bestS >= minScore ? bestP : preferida;
  }

  const PDF_STATE = { doc:null, url:null, page:1, total:1, quote:'', citaPage:1 };

  async function renderPdfPage() {
    if (!PDF_STATE.doc || !window.pdfjsLib) return;
    const num = Math.min(Math.max(1, PDF_STATE.page), PDF_STATE.total);
    PDF_STATE.page = num;
    const page = await PDF_STATE.doc.getPage(num);
    const cw = (pdfScroll.clientWidth || 760) - 34;
    const base = page.getViewport({ scale: 1 });
    const scale = Math.max(0.6, Math.min(2.4, cw / base.width));
    const viewport = page.getViewport({ scale });

    const ctx = pdfCanvas.getContext('2d');
    pdfCanvas.width = viewport.width; pdfCanvas.height = viewport.height;
    pdfCanvas.style.width = viewport.width + 'px'; pdfCanvas.style.height = viewport.height + 'px';
    pdfHl.style.width = viewport.width + 'px'; pdfHl.style.height = viewport.height + 'px';
    pdfHl.innerHTML = '';

    await page.render({ canvasContext: ctx, viewport }).promise;

    if (num === PDF_STATE.citaPage && PDF_STATE.quote) {
      const first = await paintHighlights(page, viewport, scale);
      if (first) setTimeout(() => first.scrollIntoView({ block:'center', behavior:'smooth' }), 120);
    }

    pdfPageInd.textContent = num + ' / ' + PDF_STATE.total;
  }

  async function openCita(payload) {
    if (!payload) return;
    let data;
    try { data = typeof payload === 'string' ? JSON.parse(payload) : payload; } catch (e) { return; }

    const doc = resolveDoc(data.fuente);
    const pageNum = data.pagina ? parseInt(String(data.pagina).match(/\d+/)?.[0] || '1', 10) : 1;

    drawerFile.textContent = doc ? doc.filename : (data.fuente || 'Documento');
    drawerQText.textContent = data.cita || 'No hay transcripción textual guardada para esta cita.';
    drawerQMeta.innerHTML = (doc ? `<strong>Fuente:</strong> ${escapeHtml(doc.filename)}` : (data.fuente ? `<strong>Fuente:</strong> ${escapeHtml(data.fuente)}` : '')) + (data.pagina ? ` &middot; Página ${pageNum}` : '');

    docDrawer.classList.add('is-open');
    docDrawer.setAttribute('aria-hidden','false');
    document.body.style.overflow = 'hidden';

    if (doc) { drawerOpen.href = doc.url; drawerOpen.style.display = ''; } else { drawerOpen.style.display = 'none'; }

    if (!doc || !window.pdfjsLib) {
      pdfContainer.style.display = 'none';
      pdfLoading.style.display = '';
      pdfLoading.textContent = doc ? 'El visor PDF no se cargó. Usa el botón Abrir.' : 'No se encontró el archivo fuente.';
      return;
    }

    pdfContainer.style.display = '';
    pdfLoading.style.display = '';
    pdfLoading.textContent = 'Cargando documento…';

    try {
      if (PDF_STATE.url !== doc.url) {
        const task = pdfjsLib.getDocument(doc.url);
        PDF_STATE.doc = await task.promise;
        PDF_STATE.url = doc.url;
        PDF_STATE.total = PDF_STATE.doc.numPages;
      }
      PDF_STATE.quote = data.cita || '';
      const preferida = Math.min(Math.max(1, pageNum), PDF_STATE.total);
      const target = await findQuotePage(PDF_STATE.quote, preferida);
      PDF_STATE.citaPage = target;
      PDF_STATE.page = target;
      pdfLoading.style.display = 'none';
      await renderPdfPage();
    } catch (err) {
      pdfContainer.style.display = 'none';
      pdfLoading.style.display = '';
      pdfLoading.textContent = 'No se pudo abrir el documento. Usa el botón Abrir.';
    }
  }

  function closeCita() {
    docDrawer.classList.remove('is-open');
    docDrawer.setAttribute('aria-hidden','true');
    document.body.style.overflow = '';
  }

  document.getElementById('pjdPdfPrev')?.addEventListener('click', () => { if (PDF_STATE.page > 1) { PDF_STATE.page--; renderPdfPage(); } });
  document.getElementById('pjdPdfNext')?.addEventListener('click', () => { if (PDF_STATE.page < PDF_STATE.total) { PDF_STATE.page++; renderPdfPage(); } });
  drawerTransBtn?.addEventListener('click', () => {
    const hidden = drawerQuote.hasAttribute('hidden');
    if (hidden) { drawerQuote.removeAttribute('hidden'); drawerTransBtn.classList.add('is-active'); }
    else { drawerQuote.setAttribute('hidden',''); drawerTransBtn.classList.remove('is-active'); }
  });
  document.querySelectorAll('[data-drawer-close]').forEach(el => el.addEventListener('click', closeCita));

  document.addEventListener('click', (e) => {
    const openBtn = e.target.closest('[data-cita]');
    if (openBtn && e.target.closest('.js-open-cita, .pjd-source-btn, .pjd-cl-source-btn')) {
      e.preventDefault(); e.stopPropagation();
      openCita(openBtn.getAttribute('data-cita'));
      return;
    }

    const closeBtn = e.target.closest('.pjd-source-close');
    if (closeBtn) {
      e.preventDefault(); e.stopPropagation();
      const item = closeBtn.closest('.pjd-field, .pjd-qa');
      item?.classList.remove('is-source-open');
      const panel = item?.querySelector('.pjd-source-panel'); if (panel) panel.hidden = true;
      return;
    }

    if (e.target.closest('.pjd-source-btn, .pjd-cl-source-btn')) return;

    const sourceItem = e.target.closest('.pjd-field, .pjd-qa');
    if (sourceItem && !e.target.closest('.js-card-toggle')) {
      const panel = sourceItem.querySelector('.pjd-source-panel'); if (!panel) return;
      const willOpen = !sourceItem.classList.contains('is-source-open');
      sourceItem.closest('.pjd-card-body')?.querySelectorAll('.pjd-field.is-source-open, .pjd-qa.is-source-open').forEach(oi => {
        if (oi !== sourceItem) { oi.classList.remove('is-source-open'); const op = oi.querySelector('.pjd-source-panel'); if (op) op.hidden = true; }
      });
      sourceItem.classList.toggle('is-source-open', willOpen);
      panel.hidden = !willOpen;
      return;
    }
  });

  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeCita(); });

  let pdfResizeTimer = null;
  window.addEventListener('resize', () => {
    if (!docDrawer.classList.contains('is-open') || !PDF_STATE.doc) return;
    clearTimeout(pdfResizeTimer);
    pdfResizeTimer = setTimeout(() => renderPdfPage(), 200);
  });

})();


  document.querySelectorAll('.js-ficha-rebuscar-form').forEach(form => {
    form.addEventListener('submit', () => {
      const btn = form.querySelector('.js-ficha-rebuscar-btn');
      if (!btn) return;
      btn.classList.add('is-loading');
      btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/></svg> Rebuscando...';
    });
  });
</script>
@endpush
@endonce
