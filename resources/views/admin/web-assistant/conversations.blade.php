@extends('layouts.app')

@section('title', 'Conversaciones del asistente')

@section('content')
<style>
  :root {
    --jrt-admin-bg: #f9fafb;
    --jrt-admin-card: #ffffff;
    --jrt-admin-ink: #111111;
    --jrt-admin-text: #333333;
    --jrt-admin-muted: #888888;
    --jrt-admin-line: #ebebeb;
    --jrt-admin-blue: #007aff;
    --jrt-admin-blue-soft: #e6f0ff;
    --jrt-admin-success: #15803d;
    --jrt-admin-success-soft: #e6ffe6;
    --jrt-admin-danger: #ff4a4a;
    --jrt-admin-danger-soft: #ffebeb;
    --jrt-admin-warn: #92400e;
    --jrt-admin-warn-soft: #fff7ed;
  }

  .jrt-advisor-page {
    min-height: calc(100vh - 80px);
    background: var(--jrt-admin-bg);
    padding: 28px;
    color: var(--jrt-admin-text);
    font-family: "Quicksand", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  }

  .jrt-advisor-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 22px;
  }

  .jrt-advisor-eyebrow {
    color: var(--jrt-admin-blue);
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    margin-bottom: 6px;
  }

  .jrt-advisor-title {
    margin: 0;
    color: var(--jrt-admin-ink);
    font-size: clamp(26px, 3vw, 38px);
    font-weight: 700;
    letter-spacing: -.04em;
    line-height: 1.05;
  }

  .jrt-advisor-subtitle {
    margin: 10px 0 0;
    color: var(--jrt-admin-muted);
    font-size: 15px;
    font-weight: 500;
    line-height: 1.5;
    max-width: 720px;
  }

  .jrt-advisor-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }

  .jrt-advisor-btn {
    border: 0;
    border-radius: 999px;
    height: 42px;
    padding: 0 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 700;
    font-size: 14px;
    text-decoration: none;
    cursor: pointer;
    transition: transform .16s ease, background .16s ease, border-color .16s ease, color .16s ease, opacity .16s ease;
    font-family: inherit;
  }

  .jrt-advisor-btn:active {
    transform: scale(.98);
  }

  .jrt-advisor-btn-primary {
    background: var(--jrt-admin-blue);
    color: #fff;
  }

  .jrt-advisor-btn-primary:hover {
    color: #fff;
    opacity: .92;
  }

  .jrt-advisor-btn-outline {
    background: #fff;
    color: var(--jrt-admin-blue);
    border: 1px solid var(--jrt-admin-blue);
  }

  .jrt-advisor-btn-ghost {
    background: transparent;
    color: #555;
  }

  .jrt-advisor-btn-ghost:hover {
    background: #f9fafb;
  }

  .jrt-advisor-shell {
    display: grid;
    grid-template-columns: minmax(310px, 380px) minmax(0, 1fr);
    gap: 18px;
    min-height: 680px;
  }

  .jrt-advisor-card {
    background: var(--jrt-admin-card);
    border: 1px solid var(--jrt-admin-line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
    overflow: hidden;
  }

  .jrt-advisor-sidebar {
    display: flex;
    flex-direction: column;
    min-height: 680px;
  }

  .jrt-advisor-filters {
    padding: 16px;
    border-bottom: 1px solid var(--jrt-admin-line);
    display: flex;
    gap: 8px;
    overflow: auto;
  }

  .jrt-advisor-filter {
    flex: 0 0 auto;
    border: 1px solid var(--jrt-admin-line);
    background: #fff;
    color: var(--jrt-admin-muted);
    border-radius: 999px;
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: background .16s ease, color .16s ease, border-color .16s ease;
  }

  .jrt-advisor-filter.is-active {
    border-color: var(--jrt-admin-blue);
    background: var(--jrt-admin-blue-soft);
    color: var(--jrt-admin-blue);
  }

  .jrt-advisor-list {
    padding: 10px;
    overflow: auto;
    flex: 1;
  }

  .jrt-advisor-item {
    width: 100%;
    border: 0;
    background: transparent;
    border-radius: 14px;
    padding: 14px;
    display: grid;
    grid-template-columns: 44px minmax(0, 1fr) auto;
    gap: 12px;
    text-align: left;
    cursor: pointer;
    transition: background .16s ease, transform .16s ease;
    font-family: inherit;
  }

  .jrt-advisor-item:hover {
    background: #f9fafb;
  }

  .jrt-advisor-item.is-active {
    background: var(--jrt-admin-blue-soft);
  }

  .jrt-advisor-avatar {
    width: 44px;
    height: 44px;
    border-radius: 999px;
    display: grid;
    place-items: center;
    background: #f3f4f6;
    color: var(--jrt-admin-blue);
    font-weight: 800;
  }

  .jrt-advisor-item-title {
    color: var(--jrt-admin-ink);
    font-size: 14px;
    font-weight: 800;
    line-height: 1.2;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  .jrt-advisor-item-meta {
    color: var(--jrt-admin-muted);
    font-size: 12px;
    font-weight: 600;
    margin-top: 4px;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  .jrt-advisor-item-last {
    color: #64748b;
    font-size: 12px;
    font-weight: 600;
    margin-top: 7px;
    line-height: 1.35;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .jrt-advisor-time {
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
  }

  .jrt-advisor-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 5px 9px;
    font-size: 11px;
    font-weight: 800;
    margin-top: 8px;
  }

  .jrt-advisor-badge.waiting {
    background: var(--jrt-admin-warn-soft);
    color: var(--jrt-admin-warn);
  }

  .jrt-advisor-badge.active {
    background: var(--jrt-admin-success-soft);
    color: var(--jrt-admin-success);
  }

  .jrt-advisor-badge.closed {
    background: var(--jrt-admin-danger-soft);
    color: var(--jrt-admin-danger);
  }

  .jrt-advisor-chat {
    min-height: 680px;
    display: flex;
    flex-direction: column;
  }

  .jrt-advisor-chat-head {
    padding: 18px 20px;
    border-bottom: 1px solid var(--jrt-admin-line);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
  }

  .jrt-advisor-customer {
    min-width: 0;
  }

  .jrt-advisor-customer-name {
    color: var(--jrt-admin-ink);
    font-size: 18px;
    font-weight: 800;
    margin: 0;
    line-height: 1.2;
  }

  .jrt-advisor-customer-meta {
    color: var(--jrt-admin-muted);
    font-size: 13px;
    font-weight: 600;
    margin-top: 4px;
  }

  .jrt-advisor-chat-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: flex-end;
  }

  .jrt-advisor-messages {
    flex: 1;
    min-height: 0;
    overflow: auto;
    padding: 22px;
    background: #fff;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .jrt-advisor-empty {
    height: 100%;
    min-height: 520px;
    display: grid;
    place-items: center;
    text-align: center;
    color: var(--jrt-admin-muted);
    padding: 32px;
  }

  .jrt-advisor-empty strong {
    display: block;
    color: var(--jrt-admin-ink);
    font-size: 18px;
    margin-bottom: 8px;
  }

  .jrt-advisor-msg {
    max-width: 72%;
    padding: 12px 15px;
    border-radius: 16px;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.45;
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
  }

  .jrt-advisor-msg.user {
    align-self: flex-start;
    background: #f9fafb;
    border: 1px solid var(--jrt-admin-line);
    color: var(--jrt-admin-text);
    border-bottom-left-radius: 6px;
  }

  .jrt-advisor-msg.assistant {
    align-self: flex-start;
    background: var(--jrt-admin-blue-soft);
    color: var(--jrt-admin-blue);
    border-bottom-left-radius: 6px;
  }

  .jrt-advisor-msg.advisor {
    align-self: flex-end;
    background: var(--jrt-admin-blue);
    color: #fff;
    border-bottom-right-radius: 6px;
  }

  .jrt-advisor-msg.system {
    align-self: center;
    max-width: 88%;
    background: #f9fafb;
    color: var(--jrt-admin-muted);
    border: 1px solid var(--jrt-admin-line);
    font-size: 12px;
    text-align: center;
  }

  .jrt-advisor-msg-time {
    display: block;
    margin-top: 6px;
    opacity: .72;
    font-size: 11px;
    font-weight: 700;
  }

  .jrt-advisor-reply {
    border-top: 1px solid var(--jrt-admin-line);
    padding: 16px;
    background: #fff;
  }

  .jrt-advisor-reply-box {
    display: flex;
    gap: 10px;
    align-items: flex-end;
    border: 1px solid var(--jrt-admin-line);
    background: #fff;
    border-radius: 16px;
    padding: 10px;
  }

  .jrt-advisor-textarea {
    flex: 1;
    border: 0;
    outline: 0;
    resize: none;
    min-height: 46px;
    max-height: 140px;
    padding: 10px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    color: var(--jrt-admin-text);
  }

  .jrt-advisor-textarea::placeholder {
    color: #aaa;
  }

  .jrt-advisor-loading {
    opacity: .65;
    pointer-events: none;
  }

  @media (max-width: 980px) {
    .jrt-advisor-page {
      padding: 18px;
    }

    .jrt-advisor-head {
      flex-direction: column;
    }

    .jrt-advisor-shell {
      grid-template-columns: 1fr;
    }

    .jrt-advisor-sidebar,
    .jrt-advisor-chat {
      min-height: auto;
    }

    .jrt-advisor-msg {
      max-width: 88%;
    }
  }
</style>

<div class="jrt-advisor-page">
  <div class="jrt-advisor-head">
    <div>
      <div class="jrt-advisor-eyebrow">Atención a clientes</div>
      <h1 class="jrt-advisor-title">Conversaciones del asistente</h1>
      <p class="jrt-advisor-subtitle">
        Aquí el asesor atiende las conversaciones cuando el cliente pide hablar con una persona. No se responde desde el drawer; se responde desde esta vista interna del sistema.
      </p>
    </div>

    <div class="jrt-advisor-actions">
      <a href="{{ route('admin.web-assistant.index', ['status' => 'open']) }}" class="jrt-advisor-btn jrt-advisor-btn-outline">Actualizar</a>
    </div>
  </div>

  <div class="jrt-advisor-shell">
    <aside class="jrt-advisor-card jrt-advisor-sidebar">
      <div class="jrt-advisor-filters">
        <a class="jrt-advisor-filter {{ $status === 'open' ? 'is-active' : '' }}" href="{{ route('admin.web-assistant.index', ['status' => 'open']) }}">Abiertas</a>
        <a class="jrt-advisor-filter {{ $status === 'waiting' ? 'is-active' : '' }}" href="{{ route('admin.web-assistant.index', ['status' => 'waiting']) }}">En espera</a>
        <a class="jrt-advisor-filter {{ $status === 'active' ? 'is-active' : '' }}" href="{{ route('admin.web-assistant.index', ['status' => 'active']) }}">Tomadas</a>
        <a class="jrt-advisor-filter {{ $status === 'closed' ? 'is-active' : '' }}" href="{{ route('admin.web-assistant.index', ['status' => 'closed']) }}">Cerradas</a>
      </div>

      <div class="jrt-advisor-list" id="advisorConversationList">
        @forelse($conversations as $conversation)
          @php
            $customerName = $conversation->customer?->name ?: 'Cliente invitado';
            $customerEmail = $conversation->customer?->email ?: ($conversation->session_id ? 'Sesión: '.$conversation->session_id : 'Sin correo');
            $latest = $conversation->latestMessage?->content ?: 'Sin mensajes recientes';
            $initial = mb_strtoupper(mb_substr($customerName, 0, 1, 'UTF-8'), 'UTF-8');
            $handoff = $conversation->handoff_status ?: 'waiting';
            $badgeText = $handoff === 'waiting' ? 'En espera' : ($handoff === 'active' ? 'Tomada' : 'Cerrada');
          @endphp

          <button type="button" class="jrt-advisor-item" data-conversation-id="{{ $conversation->id }}">
            <span class="jrt-advisor-avatar">{{ $initial }}</span>

            <span style="min-width:0;">
              <span class="jrt-advisor-item-title">{{ $customerName }}</span>
              <span class="jrt-advisor-item-meta">{{ $customerEmail }}</span>
              <span class="jrt-advisor-item-last">{{ $latest }}</span>
              <span class="jrt-advisor-badge {{ $handoff }}">{{ $badgeText }}</span>
            </span>

            <span class="jrt-advisor-time">{{ optional($conversation->updated_at)->format('H:i') }}</span>
          </button>
        @empty
          <div class="jrt-advisor-empty" style="min-height:360px;">
            <div>
              <strong>No hay conversaciones</strong>
              Cuando un cliente pida asesor, aparecerá aquí.
            </div>
          </div>
        @endforelse
      </div>
    </aside>

    <section class="jrt-advisor-card jrt-advisor-chat">
      <div class="jrt-advisor-chat-head">
        <div class="jrt-advisor-customer">
          <h2 class="jrt-advisor-customer-name" id="advisorChatTitle">Selecciona una conversación</h2>
          <div class="jrt-advisor-customer-meta" id="advisorChatMeta">El historial aparecerá aquí.</div>
        </div>

        <div class="jrt-advisor-chat-actions">
          <button type="button" class="jrt-advisor-btn jrt-advisor-btn-outline" id="advisorTakeBtn" disabled>Tomar chat</button>
          <button type="button" class="jrt-advisor-btn jrt-advisor-btn-ghost" id="advisorCloseBtn" disabled>Cerrar</button>
        </div>
      </div>

      <div class="jrt-advisor-messages" id="advisorMessages">
        <div class="jrt-advisor-empty">
          <div>
            <strong>Vista de asesor</strong>
            Selecciona una conversación del lado izquierdo para responderle al cliente.
          </div>
        </div>
      </div>

      <form class="jrt-advisor-reply" id="advisorReplyForm">
        @csrf
        <div class="jrt-advisor-reply-box">
          <textarea class="jrt-advisor-textarea" id="advisorReplyInput" placeholder="Escribe la respuesta para el cliente..." disabled></textarea>
          <button type="submit" class="jrt-advisor-btn jrt-advisor-btn-primary" id="advisorSendBtn" disabled>Enviar</button>
        </div>
      </form>
    </section>
  </div>
</div>

<script>
(function(){
  const list = document.getElementById('advisorConversationList');
  const messages = document.getElementById('advisorMessages');
  const title = document.getElementById('advisorChatTitle');
  const meta = document.getElementById('advisorChatMeta');
  const takeBtn = document.getElementById('advisorTakeBtn');
  const closeBtn = document.getElementById('advisorCloseBtn');
  const form = document.getElementById('advisorReplyForm');
  const input = document.getElementById('advisorReplyInput');
  const sendBtn = document.getElementById('advisorSendBtn');

  if (!list || !messages || !form || !input) return;

  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const SHOW_URL = @json(url('/admin/web-assistant/conversations/__ID__'));
  const TAKE_URL = @json(url('/admin/web-assistant/conversations/__ID__/take'));
  const REPLY_URL = @json(url('/admin/web-assistant/conversations/__ID__/reply'));
  const CLOSE_URL = @json(url('/admin/web-assistant/conversations/__ID__/close'));

  let activeId = null;
  let activeStatus = null;
  let pollTimer = null;

  function url(template, id){
    return template.replace('__ID__', encodeURIComponent(id));
  }

  function escapeHtml(text){
    return String(text || '')
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;')
      .replace(/'/g,'&#039;');
  }

  function roleLabel(role){
    if(role === 'user') return 'Cliente';
    if(role === 'advisor') return 'Asesor';
    if(role === 'assistant') return 'IA';
    return 'Sistema';
  }

 function renderMessages(items){
  if(!items || !items.length){
    messages.innerHTML = '<div class="jrt-advisor-empty"><div><strong>Sin mensajes</strong>Esta conversación aún no tiene mensajes.</div></div>';
    return;
  }

  messages.innerHTML = items.map(function(item){
    const role = ['user','advisor','assistant','system'].includes(item.role) ? item.role : 'system';

    const content = role === 'assistant'
      ? String(item.content || '').replace(/\n/g, '<br>')
      : escapeHtml(item.content).replace(/\n/g, '<br>');

    return `
      <div class="jrt-advisor-msg ${role}">
        <strong>${escapeHtml(roleLabel(role))}</strong><br>
        ${content}
        <span class="jrt-advisor-msg-time">${escapeHtml(item.time || '')}</span>
      </div>
    `;
  }).join('');

  messages.scrollTop = messages.scrollHeight;
}

  function setActiveButtons(conversation){
    activeStatus = conversation?.handoff_status || null;
    const closed = activeStatus === 'closed';

    takeBtn.disabled = !activeId || closed;
    closeBtn.disabled = !activeId || closed;
    input.disabled = !activeId || closed;
    sendBtn.disabled = !activeId || closed;

    if(activeStatus === 'waiting'){
      takeBtn.textContent = 'Tomar chat';
    } else if(activeStatus === 'active'){
      takeBtn.textContent = 'Chat tomado';
    } else {
      takeBtn.textContent = 'Cerrado';
    }
  }

  async function loadConversation(id, silent = false){
    activeId = id;

    if(!silent){
      messages.classList.add('jrt-advisor-loading');
    }

    try{
      const response = await fetch(url(SHOW_URL, id), {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const data = await response.json();

      if(!response.ok || !data.ok){
        throw new Error('No se pudo cargar');
      }

      document.querySelectorAll('.jrt-advisor-item').forEach(function(item){
        item.classList.toggle('is-active', item.getAttribute('data-conversation-id') === String(id));
      });

      const c = data.conversation || {};
      title.textContent = c.customer?.name || c.title || 'Cliente';
      meta.textContent = [
        c.customer?.email || 'Cliente invitado',
        c.handoff_status === 'waiting' ? 'En espera' : (c.handoff_status === 'active' ? 'Atendido por asesor' : 'Cerrado'),
        c.updated_at || ''
      ].filter(Boolean).join(' · ');

      renderMessages(data.messages || []);
      setActiveButtons(c);
    } catch(error){
      if(!silent){
        messages.innerHTML = '<div class="jrt-advisor-empty"><div><strong>Error</strong>No pude cargar la conversación.</div></div>';
      }
    } finally {
      messages.classList.remove('jrt-advisor-loading');
    }
  }

  async function postAction(endpoint, payload = {}) {
    if (!activeId) {
      throw new Error('No hay una conversación seleccionada.');
    }

    const response = await fetch(url(endpoint, activeId), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': CSRF,
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify(payload)
    });

    const rawResponse = await response.text();
    let data = {};

    try {
      data = rawResponse ? JSON.parse(rawResponse) : {};
    } catch (error) {
      console.error('Respuesta no JSON del servidor:', rawResponse);
      throw new Error(
        'Laravel devolvió una respuesta inválida. Revisa storage/logs/laravel.log. Código HTTP: ' + response.status
      );
    }

    if (!response.ok || data.ok !== true) {
      console.error('Error al ejecutar la acción:', {
        status: response.status,
        response: data
      });

      throw new Error(
        data.message
        || data.error
        || data.exception
        || `No se pudo completar la acción. Código HTTP: ${response.status}`
      );
    }

    return data;
  }

  list.addEventListener('click', function(event){
    const btn = event.target.closest('[data-conversation-id]');
    if(!btn) return;

    const id = btn.getAttribute('data-conversation-id');
    loadConversation(id);

    clearInterval(pollTimer);
    pollTimer = setInterval(function(){
      if(activeId) loadConversation(activeId, true);
    }, 5000);
  });

  takeBtn.addEventListener('click', async function(){
    if(!activeId) return;

    try{
      takeBtn.disabled = true;
      await postAction(TAKE_URL);
      await loadConversation(activeId);
    } catch(error){
      alert('No pude tomar el chat.');
    }
  });

  closeBtn.addEventListener('click', async function(){
    if(!activeId) return;

    const note = prompt('Mensaje final opcional para el cliente:');

    try{
      closeBtn.disabled = true;
      await postAction(CLOSE_URL, { note: note || '' });
      await loadConversation(activeId);
    } catch(error){
      alert('No pude cerrar el chat.');
    }
  });

  form.addEventListener('submit', async function(event) {
    event.preventDefault();

    const message = input.value.trim();

    if (!message) {
      alert('Escribe un mensaje antes de enviarlo.');
      input.focus();
      return;
    }

    if (!activeId) {
      alert('Selecciona una conversación.');
      return;
    }

    try {
      sendBtn.disabled = true;
      input.disabled = true;
      sendBtn.textContent = 'Enviando...';

      await postAction(REPLY_URL, { message: message });

      input.value = '';
      await loadConversation(activeId);
    } catch (error) {
      console.error(error);
      alert(error.message || 'No pude enviar el mensaje.');
    } finally {
      const closed = activeStatus === 'closed';
      sendBtn.disabled = closed;
      input.disabled = closed;
      sendBtn.textContent = 'Enviar';

      if (!closed) {
        input.focus();
      }
    }
  });

  const first = list.querySelector('[data-conversation-id]');
  if(first){
    loadConversation(first.getAttribute('data-conversation-id'));
    pollTimer = setInterval(function(){
      if(activeId) loadConversation(activeId, true);
    }, 5000);
  }
})();
</script>
@endsection
