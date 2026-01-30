@extends('layouts.app')
@section('title', 'Chat IA del PDF')

@section('content')
<style>
  :root{
    --ink:#0b1220; --muted:#667085; --muted2:#94a3b8;
    --line:#e6eaf2; --line2:#eef2f7;
    --card:#fff; --shadow:0 18px 55px rgba(2,6,23,.08);
    --radius:18px;
    --black:#0b1220; --black2:#0f172a;
    --soft:#f8fafc;
    --ease:cubic-bezier(.2,.8,.2,1);
    --blue:#2563eb;
  }

  .aiWrap{max-width:1200px;margin:0 auto;padding:18px 14px 26px}
  .aiTop{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;margin-bottom:12px}
  .aiTitle{margin:0;font-size:18px;font-weight:950;color:var(--ink);letter-spacing:-.02em}
  .aiSub{margin:6px 0 0;color:var(--muted);font-size:13px;max-width:90ch}

  .aiBtns{display:flex;gap:10px;flex-wrap:wrap}
  .aiBtn{
    border-radius:999px;border:1px solid var(--line);background:#fff;color:var(--ink);
    font-weight:900;font-size:13px;padding:10px 14px;display:inline-flex;gap:10px;align-items:center;
    text-decoration:none;cursor:pointer;transition:transform .18s var(--ease), box-shadow .18s var(--ease);
    user-select:none;
  }
  .aiBtn:hover{transform:translateY(-1px);box-shadow:0 14px 34px rgba(2,6,23,.10)}
  .aiBtn:disabled{opacity:.65;cursor:not-allowed;transform:none;box-shadow:none}
  .aiBtnBlack{background:linear-gradient(180deg,var(--black),var(--black2));color:#fff;border-color:transparent;box-shadow:0 16px 40px rgba(2,6,23,.20)}
  .aiBtnBlack:hover{box-shadow:0 22px 56px rgba(2,6,23,.26)}
  .ico{width:18px;height:18px;display:inline-block;flex:0 0 auto}

  .aiGrid{
    display:grid;gap:12px;
    grid-template-columns: minmax(0, 1fr) minmax(0, 360px);
    align-items:start;
  }
  @media (max-width: 980px){
    .aiGrid{grid-template-columns:1fr}
  }

  .aiCard{
    border:1px solid var(--line);
    border-radius:var(--radius);
    background:linear-gradient(180deg,#fff,#fcfdff);
    box-shadow:var(--shadow);
    overflow:hidden;
  }

  /* CHAT */
  .aiChatBody{height:min(70vh,720px);overflow:auto;padding:14px}
  .row{display:flex;margin:12px 0}
  .bubble{
    max-width:min(820px,96%);
    padding:12px 12px;
    border-radius:16px;
    border:1px solid var(--line2);
    white-space:pre-wrap;
    position:relative;
    animation: pop .22s var(--ease) both;
  }
  @keyframes pop{from{transform:translateY(6px);opacity:0}to{transform:translateY(0);opacity:1}}
  .me{justify-content:flex-end}
  .me .bubble{background:var(--black);color:#fff;border-color:var(--black)}
  .ai{justify-content:flex-start}
  .ai .bubble{background:var(--soft);color:var(--ink)}

  .metaActions{
    margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end
  }
  .miniBtn{
    border-radius:999px;border:1px solid var(--line);
    background:#fff;color:var(--ink);
    font-weight:900;font-size:12px;padding:8px 10px;
    display:inline-flex;gap:8px;align-items:center;cursor:pointer;
    transition:transform .18s var(--ease), box-shadow .18s var(--ease);
    user-select:none;
  }
  .miniBtn:hover{transform:translateY(-1px);box-shadow:0 12px 26px rgba(2,6,23,.10)}
  .miniBtnBlack{background:linear-gradient(180deg,var(--black),var(--black2));color:#fff;border-color:transparent}

  /* typing indicator */
  .typing{display:inline-flex;gap:8px;align-items:center}
  .dot{
    width:7px;height:7px;border-radius:999px;background:currentColor;opacity:.35;
    animation: bounce 1.1s infinite;
  }
  .dot:nth-child(2){animation-delay:.15s}
  .dot:nth-child(3){animation-delay:.30s}
  @keyframes bounce{
    0%, 80%, 100%{transform:translateY(0);opacity:.35}
    40%{transform:translateY(-5px);opacity:.9}
  }

  /* Input bar */
  .aiBar{display:flex;gap:10px;padding:12px;border-top:1px solid var(--line);background:#fff;align-items:center}
  .aiInput{flex:1;border:1px solid var(--line2);border-radius:14px;padding:12px 12px;font-size:14px;outline:none}
  .aiInput:focus{border-color:rgba(37,99,235,.35);box-shadow:0 0 0 4px rgba(37,99,235,.10)}
  .aiSend{
    border:none;border-radius:999px;padding:11px 14px;font-weight:950;
    background:var(--black);color:#fff;cursor:pointer;
    display:inline-flex;gap:10px;align-items:center;
  }
  .aiSend:disabled{opacity:.6;cursor:not-allowed}

  /* sources */
  .srcBox{margin-top:10px;border-top:1px dashed rgba(148,163,184,.45);padding-top:10px}
  .srcTitle{font-size:12px;font-weight:950;color:var(--ink);display:flex;gap:8px;align-items:center}
  .srcItem{
    margin-top:8px;background:#fff;border:1px solid var(--line2);
    border-radius:14px;padding:10px;
  }
  .srcTop{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap}
  .srcName{font-weight:950;font-size:12px;color:#1d4ed8;display:flex;gap:8px;align-items:center}
  .srcScore{font-size:12px;color:var(--muted)}
  .srcSnippet{margin-top:6px;font-size:12px;color:var(--ink);opacity:.9}

  /* NOTES */
  .notesHead{padding:12px 12px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap}
  .notesTitle{margin:0;font-weight:950;font-size:14px;color:var(--ink);display:flex;gap:8px;align-items:center}
  .notesSub{margin:6px 0 0;color:var(--muted);font-size:12px}
  .notesBody{padding:12px}
  .notesArea{
    width:100%;min-height:320px;height:calc(min(70vh,720px) - 120px);
    border:1px solid var(--line2);border-radius:14px;padding:12px;font-size:13px;outline:none;
    resize:vertical;
  }
  .notesArea:focus{border-color:rgba(37,99,235,.35);box-shadow:0 0 0 4px rgba(37,99,235,.10)}
  .notesActions{margin-top:10px;display:flex;gap:10px;flex-wrap:wrap}

  /* modal */
  .mOverlay{position:fixed;inset:0;background:rgba(2,6,23,.55);display:flex;align-items:center;justify-content:center;z-index:200;padding:16px}
  .mOverlay[hidden]{display:none!important}
  .mBox{width:min(1100px,100%);height:min(85vh,820px);background:#0b1220;border-radius:18px;overflow:hidden;border:1px solid rgba(255,255,255,.10);box-shadow:0 26px 60px rgba(2,6,23,.55);display:flex;flex-direction:column}
  .mHead{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 12px;color:#e5e7eb;border-bottom:1px solid rgba(255,255,255,.10)}
  .mIfr{flex:1;border:none;width:100%;background:#0b1220}
</style>

@php
  // Mensajes existentes (sin sources guardados). Las respuestas nuevas sí pueden traer sources desde el backend.
  $items = $messages->map(fn($m)=>[
    'role'    => $m->role,
    'content' => $m->content,
    'sources' => [], // si guardas sources en BD, aquí pon $m->sources ?? []
  ])->values();
@endphp

<div class="aiWrap" id="pdfChatRoot"
  data-pdf-id="{{ (int)$pdf->id }}"
  data-send-url="{{ route('admin.licitacion-pdfs.ai.message', $pdf) }}"
  data-notes-pdf-url="{{ route('admin.licitacion-pdfs.ai.notes.pdf', $pdf) }}"
  data-preview-url="{{ route('admin.licitacion-pdfs.preview', ['licitacionPdf' => $pdf->id]) }}"
  data-viewer-url="{{ route('admin.licitacion-pdfs.ai.viewer', ['licitacionPdf' => $pdf->id]) }}"
  data-checklist-url="{{ route('admin.licitacion-pdfs.ai.checklist', $pdf) }}"
  data-checklist-generate-url="{{ route('admin.licitacion-pdfs.ai.checklist.generate', $pdf) }}"
  data-filename="{{ e($pdf->original_filename) }}"
  data-initial='@json($items)'
>
  <div class="aiTop">
    <div>
      <h1 class="aiTitle">Chat IA — {{ $pdf->original_filename }}</h1>
      <p class="aiSub">Pregúntale lo que sea de este PDF. La IA solo usa este documento como contexto.</p>
    </div>

    <div class="aiBtns">
      <button class="aiBtn aiBtnBlack" type="button" id="btnOpenPdf">
        <svg class="ico" viewBox="0 0 24 24" fill="none">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2"/>
          <path d="M14 2v6h6" stroke="currentColor" stroke-width="2"/>
        </svg>
        Ver PDF
      </button>

      <button class="aiBtn" type="button" id="btnChecklist">
        <svg class="ico" viewBox="0 0 24 24" fill="none">
          <path d="M9 11l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M20 6v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h10" stroke="currentColor" stroke-width="2"/>
          <path d="M14 2v6h6" stroke="currentColor" stroke-width="2"/>
        </svg>
        Generar checklist
      </button>

      <a class="aiBtn" href="{{ route('admin.licitacion-pdfs.index') }}">
        <svg class="ico" viewBox="0 0 24 24" fill="none">
          <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        Volver
      </a>
    </div>
  </div>

  <div class="aiGrid">
    <!-- CHAT -->
    <div class="aiCard">
      <div class="aiChatBody" id="chatBody"></div>

      <div class="aiBar">
        <input class="aiInput" id="chatInput" type="text" placeholder="Escribe tu pregunta…">
        <button class="aiSend" id="chatSend" type="button">
          <svg class="ico" viewBox="0 0 24 24" fill="none">
            <path d="M22 2L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M22 2l-7 20-4-9-9-4 20-7z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
          </svg>
          <span id="chatSendText">Enviar</span>
        </button>
      </div>
    </div>

    <!-- NOTAS -->
    <div class="aiCard">
      <div class="notesHead">
        <div>
          <h3 class="notesTitle">
            <svg class="ico" viewBox="0 0 24 24" fill="none">
              <path d="M4 4h16v16H4z" stroke="currentColor" stroke-width="2"/>
              <path d="M8 8h8M8 12h8M8 16h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Notas
          </h3>
          <div class="notesSub">Guarda hallazgos y luego descárgalos en PDF.</div>
        </div>
      </div>

      <div class="notesBody">
        <textarea class="notesArea" id="notesArea" placeholder="Aquí puedes escribir o ir agregando notas desde las respuestas..."></textarea>

        <div class="notesActions">
          <button type="button" class="aiBtn" id="btnClearNotes">
            <svg class="ico" viewBox="0 0 24 24" fill="none">
              <path d="M3 6h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="M8 6V4h8v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="M8 10v8M12 10v8M16 10v8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="M6 6l1 16h10l1-16" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
            </svg>
            Limpiar
          </button>

          <button type="button" class="aiBtn aiBtnBlack" id="btnDownloadNotes">
            <svg class="ico" viewBox="0 0 24 24" fill="none">
              <path d="M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="M5 21h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Descargar PDF
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL PDF (PDF.js viewer con highlight) -->
  <div class="mOverlay" id="pdfModal" hidden>
    <div class="mBox" role="dialog" aria-modal="true" aria-label="Vista previa PDF">
      <div class="mHead">
        <div style="font-weight:900;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
          {{ $pdf->original_filename }}
          <span id="modalHint" style="font-weight:600;opacity:.8;margin-left:10px;display:none"></span>
        </div>
        <button class="aiBtn" type="button" id="btnClosePdf">Cerrar</button>
      </div>

      <iframe class="mIfr" id="pdfFrame" src="{{ route('admin.licitacion-pdfs.ai.viewer', ['licitacionPdf' => $pdf->id]) }}"></iframe>
    </div>
  </div>
</div>

<script>
(function(){
  const root = document.getElementById('pdfChatRoot');
  if(!root) return;

  const pdfId       = root.getAttribute('data-pdf-id');
  const sendUrl     = root.getAttribute('data-send-url');
  const notesPdfUrl = root.getAttribute('data-notes-pdf-url');
  const previewUrl  = root.getAttribute('data-preview-url');
  const viewerUrl   = root.getAttribute('data-viewer-url');
  const checklistUrl = root.getAttribute('data-checklist-url');
  const checklistGenerateUrl = root.getAttribute('data-checklist-generate-url');
  const filename    = root.getAttribute('data-filename') || 'PDF';

  const csrf = (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')) || @json(csrf_token());

  const chatBody = document.getElementById('chatBody');
  const input    = document.getElementById('chatInput');
  const btnSend  = document.getElementById('chatSend');
  const btnSendText = document.getElementById('chatSendText');

  const notesArea = document.getElementById('notesArea');
  const btnClearNotes = document.getElementById('btnClearNotes');
  const btnDownloadNotes = document.getElementById('btnDownloadNotes');

  const btnOpenPdf = document.getElementById('btnOpenPdf');
  const btnChecklist = document.getElementById('btnChecklist');

  const modal = document.getElementById('pdfModal');
  const btnClosePdf = document.getElementById('btnClosePdf');
  const pdfFrame = document.getElementById('pdfFrame');
  const modalHint = document.getElementById('modalHint');

  let sending = false;
  let typingEl = null;

  let items = [];
  try{ items = JSON.parse(root.getAttribute('data-initial') || '[]') || []; }catch(e){ items = []; }

  const notesKey = `pdf_notes_${pdfId}`;
  notesArea.value = localStorage.getItem(notesKey) || '';
  notesArea.addEventListener('input', () => localStorage.setItem(notesKey, notesArea.value));

  function scrollBottom(){ chatBody.scrollTop = chatBody.scrollHeight; }

  function escapeHtml(str){
    return String(str ?? '')
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  function copyText(text){
    const t = String(text ?? '');
    if(navigator.clipboard?.writeText){
      navigator.clipboard.writeText(t);
      return;
    }
    const ta = document.createElement('textarea');
    ta.value = t;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    ta.remove();
  }

  function addNote(text){
    const v = String(text ?? '').trim();
    if(!v) return;
    const cur = notesArea.value.trim();
    notesArea.value = cur ? (cur + "\n\n" + v) : v;
    localStorage.setItem(notesKey, notesArea.value);
  }

  function openModalWithSource(source){
    let hint = '';
    let url = viewerUrl;

    if(source){
      const page = source.page || 1;
      const q = (source.highlight || source.q || source.snippet || '').toString().trim();

      const params = new URLSearchParams();
      if(page) params.set('page', page);
      if(q) params.set('q', q);

      url = viewerUrl + (params.toString() ? ('?' + params.toString()) : '');
      hint = page ? (`página ${page}`) : 'fuente';
      if(q) hint += ' — resaltando';
    }

    modalHint.textContent = hint ? ('(' + hint + ')') : '';
    modalHint.style.display = hint ? 'inline' : 'none';

    pdfFrame.src = url;
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
  }

  function closeModal(){
    modal.hidden = true;
    document.body.style.overflow = '';
  }

  function renderTyping(show){
    if(show){
      if(typingEl) return;
      typingEl = document.createElement('div');
      typingEl.className = 'row ai';
      typingEl.innerHTML = `
        <div class="bubble">
          <div class="typing" style="color:var(--ink)">
            <span style="font-weight:900">La IA está escribiendo</span>
            <span class="dot"></span><span class="dot"></span><span class="dot"></span>
          </div>
        </div>
      `;
      chatBody.appendChild(typingEl);
      scrollBottom();
      return;
    }
    if(typingEl){
      typingEl.remove();
      typingEl = null;
    }
  }

  function renderOneMessage(it){
    const row = document.createElement('div');
    row.className = 'row ' + (it.role === 'user' ? 'me' : 'ai');

    const bubble = document.createElement('div');
    bubble.className = 'bubble';

    const contentDiv = document.createElement('div');
    contentDiv.textContent = it.content ?? '';
    bubble.appendChild(contentDiv);

    if(it.role === 'assistant'){
      const actions = document.createElement('div');
      actions.className = 'metaActions';

      const btnNote = document.createElement('button');
      btnNote.type = 'button';
      btnNote.className = 'miniBtn';
      btnNote.innerHTML = `
        <svg class="ico" viewBox="0 0 24 24" fill="none">
          <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        Nota
      `;
      btnNote.addEventListener('click', () => addNote(it.content));
      actions.appendChild(btnNote);

      const btnCopy = document.createElement('button');
      btnCopy.type = 'button';
      btnCopy.className = 'miniBtn';
      btnCopy.innerHTML = `
        <svg class="ico" viewBox="0 0 24 24" fill="none">
          <path d="M9 9h10v10H9z" stroke="currentColor" stroke-width="2"/>
          <path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1" stroke="currentColor" stroke-width="2"/>
        </svg>
        Copiar
      `;
      btnCopy.addEventListener('click', () => copyText(it.content));
      actions.appendChild(btnCopy);

      bubble.appendChild(actions);

      const sourcesArr = Array.isArray(it.sources) ? it.sources : [];
      if(sourcesArr.length){
        const srcBox = document.createElement('div');
        srcBox.className = 'srcBox';

        const title = document.createElement('div');
        title.className = 'srcTitle';
        title.innerHTML = `
          <svg class="ico" viewBox="0 0 24 24" fill="none">
            <path d="M10 14a5 5 0 0 1 0-7l1-1a5 5 0 0 1 7 7l-1 1" stroke="currentColor" stroke-width="2"/>
            <path d="M14 10a5 5 0 0 1 0 7l-1 1a5 5 0 0 1-7-7l1-1" stroke="currentColor" stroke-width="2"/>
          </svg>
          Fuente del PDF
        `;
        srcBox.appendChild(title);

        const s = sourcesArr[0];

        const item = document.createElement('div');
        item.className = 'srcItem';

        const score = (s && s.score != null) ? `score: ${Number(s.score).toFixed(3)}` : '';
        const name = s?.filename || s?.label || filename;

        const top = document.createElement('div');
        top.className = 'srcTop';
        top.innerHTML = `
          <div class="srcName">
            <svg class="ico" viewBox="0 0 24 24" fill="none">
              <path d="M14 3h7v7" stroke="currentColor" stroke-width="2"/>
              <path d="M10 14L21 3" stroke="currentColor" stroke-width="2"/>
              <path d="M21 14v6a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h6" stroke="currentColor" stroke-width="2"/>
            </svg>
            <span>${escapeHtml(name)}</span>
          </div>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <div class="srcScore">${escapeHtml(score)}</div>
            <button type="button" class="miniBtn miniBtnBlack">Ver</button>
          </div>
        `;
        item.appendChild(top);

        const btnView = top.querySelector('button');
        btnView.addEventListener('click', () => openModalWithSource(s));

        const snippet = s?.excerpt || s?.snippet || '';
        if(snippet){
          const sn = document.createElement('div');
          sn.className = 'srcSnippet';
          sn.textContent = snippet;
          item.appendChild(sn);
        }

        const bottom = document.createElement('div');
        bottom.style.cssText = 'margin-top:8px;display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end';

        const btnNoteSnippet = document.createElement('button');
        btnNoteSnippet.type = 'button';
        btnNoteSnippet.className = 'miniBtn';
        btnNoteSnippet.textContent = '+ Nota (extracto)';
        btnNoteSnippet.addEventListener('click', () => {
          const val = String(snippet || '').trim();
          if(!val) return;
          addNote((name ? (name + ': ') : '') + val);
        });

        const btnCopySnippet = document.createElement('button');
        btnCopySnippet.type = 'button';
        btnCopySnippet.className = 'miniBtn';
        btnCopySnippet.textContent = 'Copiar extracto';
        btnCopySnippet.addEventListener('click', () => copyText(snippet || ''));

        bottom.appendChild(btnNoteSnippet);
        bottom.appendChild(btnCopySnippet);
        item.appendChild(bottom);

        srcBox.appendChild(item);
        bubble.appendChild(srcBox);
      }
    }

    row.appendChild(bubble);
    chatBody.appendChild(row);
  }

  function renderAll(){
    chatBody.innerHTML = '';
    items.forEach(renderOneMessage);
    scrollBottom();
  }

  async function send(){
    const t = (input.value || '').trim();
    if(!t || sending) return;

    items.push({role:'user', content:t, sources:[]});
    input.value = '';
    renderAll();

    sending = true;
    btnSend.disabled = true;
    btnSendText.textContent = 'Enviando…';
    renderTyping(true);

    try{
      const res = await fetch(sendUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers:{
          'Content-Type':'application/json',
          'Accept':'application/json',
          'X-Requested-With':'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({ message: t })
      });

      const raw = await res.text();
      let j = null;
      try{ j = JSON.parse(raw); }catch(e){}

      if(!res.ok){
        const msg = (j && (j.message || j.error)) ? (j.message || j.error) : raw;
        items.push({role:'assistant', content:`Error (${res.status}): ${msg}`, sources:[]});
        return;
      }

      const one = j?.source ? [j.source] : (Array.isArray(j?.sources) ? j.sources : []);

      items.push({
        role:'assistant',
        content: (j?.answer || 'No pude responder.'),
        sources: one
      });
    }catch(e){
      items.push({role:'assistant', content:'Error enviando mensaje (fetch). Revisa consola / logs.', sources:[]});
    }finally{
      renderTyping(false);
      sending = false;
      btnSend.disabled = false;
      btnSendText.textContent = 'Enviar';
      renderAll();
      input.focus();
    }
  }

  async function generateChecklist(){
    if(sending) return;

    btnChecklist.disabled = true;

    items.push({role:'assistant', content:'Generando checklist disciplinado a partir del PDF…', sources:[]});
    renderAll();

    try{
      const res = await fetch(checklistGenerateUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers:{
          'Content-Type':'application/json',
          'Accept':'application/json',
          'X-Requested-With':'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({ action: 'generate_checklist' })
      });

      const raw = await res.text();
      let j = null;
      try{ j = JSON.parse(raw); }catch(e){}

      if(!res.ok || !j?.ok){
        const msg = (j && (j.message || j.error)) ? (j.message || j.error) : raw;
        items.push({role:'assistant', content:`Error generando checklist: ${msg}`, sources:[]});
        renderAll();
        return;
      }

      window.location.href = j.redirect || checklistUrl;
    }catch(e){
      items.push({role:'assistant', content:'Error generando checklist (fetch). Revisa logs.', sources:[]});
      renderAll();
    }finally{
      btnChecklist.disabled = false;
    }
  }

  // Eventos UI
  btnSend.addEventListener('click', send);
  input.addEventListener('keydown', (e) => {
    if(e.key === 'Enter'){
      e.preventDefault();
      send();
    }
  });

  btnChecklist.addEventListener('click', generateChecklist);

  btnClearNotes.addEventListener('click', () => {
    notesArea.value = '';
    localStorage.setItem(notesKey, '');
  });

  btnDownloadNotes.addEventListener('click', async () => {
    try{
      const res = await fetch(notesPdfUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers:{
          'Content-Type':'application/json',
          'Accept':'application/pdf',
          'X-Requested-With':'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({ notes: notesArea.value })
      });

      if(!res.ok){
        const t = await res.text();
        alert('No se pudo generar el PDF. ' + t);
        return;
      }

      const blob = await res.blob();
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `notas_pdf_${pdfId}.pdf`;
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
    }catch(e){
      alert('Error descargando PDF de notas.');
    }
  });

  btnOpenPdf.addEventListener('click', () => openModalWithSource(null));
  btnClosePdf.addEventListener('click', closeModal);

  modal.addEventListener('click', (e) => {
    if(e.target === modal) closeModal();
  });

  window.addEventListener('keydown', (e) => {
    if(e.key === 'Escape' && !modal.hidden) closeModal();
  });

  // Inicial
  renderAll();
  input.focus();
})();
</script>
@endsection
