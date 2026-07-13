@extends('layouts.web')
@section('title','Centro de Ayuda IA')

@section('content')
<style>
/* ==================== Centro de Ayuda IA IA - AISLADO ==================== */
#helpdesk{
  --bg1:#f8fbff;
  --bg2:#edf5ff;
  --surface:rgba(255,255,255,.88);
  --surface2:#ffffff;
  --line:#dbe7f5;
  --ink:#07111f;
  --muted:#64748b;
  --brand:#2563eb;
  --brand2:#38bdf8;
  --soft:#eff6ff;
  --radius:30px;
  --shadow:0 28px 80px rgba(15,23,42,.12);
  min-height:calc(100vh - 160px);
  margin:-28px -20px;
  padding:64px 20px;
  background:
    radial-gradient(circle at 18% 12%, rgba(56,189,248,.24), transparent 32%),
    radial-gradient(circle at 82% 18%, rgba(37,99,235,.14), transparent 32%),
    linear-gradient(180deg,var(--bg1),var(--bg2));
}

#helpdesk *{box-sizing:border-box}

#helpdesk .wrap{
  max-width:980px;
  margin:0 auto;
}

#helpdesk .card{
  position:relative;
  overflow:hidden;
  border:1px solid rgba(148,163,184,.22);
  border-radius:var(--radius);
  background:var(--surface);
  box-shadow:var(--shadow);
  backdrop-filter:blur(18px);
}

#helpdesk .card::before{
  content:"";
  position:absolute;
  inset:0 0 auto 0;
  height:4px;
  background:linear-gradient(90deg,var(--brand),var(--brand2),#93c5fd);
}

#helpdesk .head{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:16px;
  padding:26px 30px 18px;
  border-bottom:1px solid rgba(219,231,245,.75);
}

#helpdesk .h1{
  margin:0;
  display:flex;
  align-items:center;
  gap:13px;
  font-size:clamp(24px,3vw,34px);
  font-weight:950;
  letter-spacing:-.055em;
  color:var(--ink);
}

#helpdesk .h1::before{
  content:"AI";
  width:46px;
  height:46px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  border-radius:18px;
  font-size:14px;
  font-weight:950;
  color:#1d4ed8;
  background:linear-gradient(135deg,#eff6ff,#dbeafe);
  border:1px solid rgba(37,99,235,.15);
  box-shadow:0 16px 35px rgba(37,99,235,.16);
}

#helpdesk .status{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:8px 12px;
  border-radius:999px;
  border:1px solid rgba(37,99,235,.14);
  background:rgba(239,246,255,.9);
  color:#2563eb;
  font-size:12px;
  font-weight:850;
  white-space:nowrap;
}

#helpdesk .status::before{
  content:"";
  width:8px;
  height:8px;
  border-radius:50%;
  background:#22c55e;
  box-shadow:0 0 0 5px rgba(34,197,94,.12);
}

#helpdesk .body{
  display:block;
}

/* Formulario inicial como prompt IA */
#helpdesk .new{
  padding:30px;
  background:
    linear-gradient(180deg,rgba(255,255,255,.95),rgba(248,251,255,.92));
}

#helpdesk .new::before{
  content:"Asistente inteligente";
  display:inline-flex;
  align-items:center;
  gap:8px;
  margin-bottom:18px;
  padding:8px 13px;
  border-radius:999px;
  color:#2563eb;
  background:rgba(37,99,235,.08);
  border:1px solid rgba(37,99,235,.12);
  font-size:12px;
  font-weight:950;
  letter-spacing:.02em;
  text-transform:uppercase;
}

/* Ocultar tema y categoría visualmente */
#helpdesk .new .grid{
  display:none !important;
}

#helpdesk label{
  display:block;
  margin:0 0 10px;
  color:var(--muted);
  font-size:13px;
  font-weight:850;
}

#helpdesk textarea,
#helpdesk input[type=text],
#helpdesk select{
  width:100%;
  border:1px solid var(--line);
  border-radius:22px;
  background:#fff;
  color:var(--ink);
  outline:none;
  font-size:15px;
}

#helpdesk textarea{
  min-height:245px;
  padding:22px 24px;
  resize:vertical;
  line-height:1.75;
  background:
    linear-gradient(#fff,#fff) padding-box,
    linear-gradient(135deg,rgba(37,99,235,.55),rgba(56,189,248,.38)) border-box;
  border:1px solid transparent;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.8), 0 18px 45px rgba(15,23,42,.055);
}

#helpdesk textarea::placeholder{
  color:#94a3b8;
}

#helpdesk textarea:focus,
#helpdesk input[type=text]:focus,
#helpdesk select:focus{
  border-color:#60a5fa;
  box-shadow:0 0 0 5px rgba(96,165,250,.18), 0 20px 50px rgba(37,99,235,.10);
}

#helpdesk .btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  min-height:50px;
  padding:0 22px;
  border:0;
  border-radius:18px;
  background:linear-gradient(135deg,var(--brand),var(--brand2));
  color:#fff;
  font-weight:950;
  cursor:pointer;
  box-shadow:0 18px 38px rgba(37,99,235,.28);
  transition:transform .18s ease, box-shadow .18s ease, filter .18s ease;
}

#helpdesk .btn:hover{
  transform:translateY(-1px);
  filter:brightness(1.03);
  box-shadow:0 24px 48px rgba(37,99,235,.36);
}

#helpdesk .btn:disabled{
  opacity:.55;
  cursor:not-allowed;
  transform:none;
  box-shadow:none;
}

#helpdesk .btn-ghost{
  background:#fff;
  color:#2563eb;
  border:1px solid rgba(37,99,235,.18);
  box-shadow:0 12px 28px rgba(15,23,42,.07);
}

#helpdesk .new > div:last-child{
  margin-top:18px !important;
}

#helpdesk .new > div:last-child span{
  max-width:560px;
  color:var(--muted) !important;
  font-size:13px !important;
}

/* Chat cuando ya existe ticket */
#helpdesk .chat{
  padding:24px 30px;
  height:clamp(360px,58vh,650px);
  overflow:auto;
  background:
    radial-gradient(circle at 10% 0%, rgba(59,130,246,.08), transparent 28%),
    repeating-linear-gradient(-45deg,#fbfdff,#fbfdff 20px,#f7fbff 20px,#f7fbff 40px);
}

#helpdesk .msg{
  display:flex;
  gap:10px;
  margin:12px 0;
}

#helpdesk .bubble{
  max-width:min(82%,680px);
  padding:13px 15px;
  border-radius:18px;
  border:1px solid rgba(219,231,245,.9);
  background:#fff;
  color:var(--ink);
  box-shadow:0 12px 28px rgba(15,23,42,.06);
  word-wrap:break-word;
  overflow-wrap:anywhere;
}

#helpdesk .me{justify-content:flex-end}
#helpdesk .me .bubble{
  background:linear-gradient(135deg,#eff6ff,#e0f2fe);
  border-color:rgba(96,165,250,.28);
}

#helpdesk .ai .bubble{
  background:#f8fbff;
}

#helpdesk .agent .bubble{
  background:#ecfdf5;
}

#helpdesk .system .bubble{
  background:#fff7ed;
}

#helpdesk .meta{
  margin-top:7px;
  color:var(--muted);
  font-size:11px;
}

#helpdesk .composer{
  display:flex;
  gap:10px;
  padding:16px;
  border-top:1px solid rgba(219,231,245,.8);
  background:#fff;
}

#helpdesk .composer textarea{
  flex:1;
  min-height:50px;
  max-height:150px;
  padding:14px 16px;
  border-radius:18px;
  resize:vertical;
}

#helpdesk .typing .bubble{
  display:flex;
  align-items:center;
  gap:8px;
}

#helpdesk .dot{
  width:6px;
  height:6px;
  display:inline-block;
  border-radius:50%;
  background:#94a3b8;
  animation:helpBlink 1s infinite ease-in-out;
}

#helpdesk .dot:nth-child(2){animation-delay:.15s}
#helpdesk .dot:nth-child(3){animation-delay:.3s}

@keyframes helpBlink{
  0%,80%,100%{opacity:.2}
  40%{opacity:1}
}

@media (max-width:720px){
  #helpdesk{
    margin:-28px -20px;
    padding:34px 14px;
  }

  #helpdesk .head{
    padding:22px 20px 16px;
    align-items:flex-start;
  }

  #helpdesk .new{
    padding:22px 18px;
  }

  #helpdesk textarea{
    min-height:230px;
  }

  #helpdesk .composer{
    position:sticky;
    bottom:0;
    z-index:5;
    flex-wrap:wrap;
  }

  #helpdesk .composer textarea{
    flex-basis:100%;
  }
}
</style>

@php
  $hasTicket = isset($ticket) && $ticket;
@endphp

<div id="helpdesk" data-user-name="{{ auth()->user()->name ?? 'Tú' }}">
  <div class="wrap">
        {{-- Chats guardados IA --}}
        @php
          $savedTickets = $tickets ?? collect();
          $activeTicketId = $ticket->id ?? null;
        @endphp

        <aside class="saved-chats">
          <div class="saved-chats-head">
            <span>Chats</span>
            <a href="{{ route('help.create') }}">+ Nuevo</a>
          </div>

          @if($savedTickets->count())
            <div class="saved-chats-list">
              @foreach($savedTickets as $saved)
                @php
                  $savedDate = $saved->last_activity_at ?: $saved->created_at;
                  $statusText = strtoupper((string) $saved->status);
                  $firstUserMessage = $saved->messages->first();
                  $chatTitle = $firstUserMessage?->body ?: ($saved->subject ?: 'Consulta de ayuda');
                @endphp

                <div class="saved-chat-row {{ (int)$activeTicketId === (int)$saved->id ? 'active' : '' }}">
                  <a class="saved-chat"
                     href="{{ url('/ayuda/t/' . $saved->id) }}">
                    <span class="saved-chat-title">
                      {{ \Illuminate\Support\Str::limit($saved->chat_title ?? $saved->subject ?? 'Consulta de ayuda', 34) }}
                    </span>
                    <span class="saved-chat-meta">
                      {{ $statusText }}
                      @if($savedDate)
                        · {{ \Illuminate\Support\Carbon::parse($savedDate)->format('d/m') }}
                      @endif
                    </span>
                  </a>

                  <form class="saved-chat-delete-form"
                        action="{{ url('/ayuda/t/' . $saved->id) }}"
                        method="POST"
                        onsubmit="return confirm('�?Borrar este chat?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="saved-chat-delete" title="Borrar chat">×</button>
                  </form>
                </div>
              @endforeach
            </div>
          @else
            <div class="saved-empty">
              Aún no tienes chats guardados.
            </div>
          @endif
        </aside>
        {{-- Fin chats guardados IA --}}
<div class="card">
      <div class="head">
        <h1 class="h1">Centro de Ayuda IA</h1>
        <span class="status" id="statusChip">
          @if($hasTicket) Estatus: {{ strtoupper($ticket->status) }} @else Nuevo @endif
        </span>
      </div>

      {{-- 👇 Clase extra para controlar grid desktop --}}
      <div class="body {{ $hasTicket ? '' : 'no-ticket' }}">
        {{-- Formulario “nuevo ticket” (AJAX) si no hay ticket aún --}}
        @unless($ticket)
          <form class="new" id="newTicketForm" action="{{ route('help.start') }}" method="POST">
            @csrf
            <div class="grid">
              <div>
                <label>Tema</label>
                <input type="text" name="subject" placeholder="Ej. Problema al iniciar sesión" required>
              </div>
              <div>
                <label>Categoría</label>
                <select name="category">
                  <option value="">—</option>
                  <option>Cuenta</option>
                  <option>Pagos</option>
                  <option>Pedidos</option>
                  <option>Soporte técnico</option>
                </select>
              </div>
            </div>

            <div style="margin-top:10px">
              <label>Cuéntanos qué sucede</label>
              <textarea name="message" placeholder="Describe el problema con el mayor detalle posible…" required></textarea>
            </div>

            <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center">
              <button class="btn" type="submit">Enviar al asistente IA ✨</button>
              <span style="font-size:12px;color:var(--muted);line-height:1.35">
                La IA responde primero; si no resuelve, podrás escalar a un agente.
              </span>
            </div>
          </form>
        @endunless

        {{-- Chat del ticket --}}
        <div class="chat" id="chatbox" data-ticket-id="{{ $ticket->id ?? '' }}" style="@unless($ticket) display:none @endunless">
          @isset($ticket)
            @foreach($ticket->messages as $m)
              @php
                $cls = match($m->sender_type){ 'user'=>'me', 'ai'=>'ai', 'agent'=>'agent', 'system'=>'system', default=>'' };
              @endphp
              <div class="msg {{ $cls }}">
                <div class="bubble">
                  {!! nl2br(e($m->body)) !!}
                  <div class="meta">
                    {{ $m->sender_type === 'user' ? 'Tú' : ucfirst($m->sender_type) }}
                    • {{ $m->created_at->format('d/m/Y H:i') }}
                    @if($m->is_solution) • ✅ Solución @endif
                  </div>
                </div>
              </div>
            @endforeach
          @endisset
        </div>

        {{-- Composer dinámico --}}
        <div id="composerWrap" class="composer" style="@unless($ticket) display:none @endunless">
          <textarea id="composerInput" placeholder="Escribe un mensaje…" @isset($ticket) @if($ticket->status==='closed') disabled @endif @endisset></textarea>
          <button type="button" id="expandComposerBtn" class="mini-expand" title="Agrandar caja">⤢</button>
          <button class="btn" id="sendBtn" @isset($ticket) @if($ticket->status==='closed') disabled @endif @endisset>Enviar</button>
          <button class="btn btn-ghost" id="escalarBtn" @isset($ticket) @if($ticket->status==='closed') disabled @endif @endisset>Contactar a un humano</button>
        </div>

        {{-- Nota cuando cerrado --}}
        @isset($ticket)
          @if($ticket->status==='closed')
            <div class="composer" style="justify-content:center;color:var(--muted)">Ticket cerrado ✅</div>
          @endif
        @endisset
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const root     = document.getElementById('helpdesk');
  const chatbox  = document.getElementById('chatbox');
  const statusEl = document.getElementById('statusChip');
  const composer = document.getElementById('composerWrap');
  const input    = document.getElementById('composerInput');
  const sendBtn  = document.getElementById('sendBtn');
  const escBtn   = document.getElementById('escalarBtn');
  const newForm  = document.getElementById('newTicketForm');

  const routes = {
    start: "{{ route('help.start') }}",
    tBase: "{{ url('/ayuda/t') }}",
  };

  function csrf(){
    const m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }

  function scrollBottom(){
    if (!chatbox) return;
    chatbox.scrollTop = chatbox.scrollHeight;
  }

  function timeNow(){
    const d=new Date();
    const pad=n=> String(n).padStart(2,'0');
    return `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
  }

  function bubble(type, body, created, isSolution){
    const cls = type==='user' ? 'me' : (type==='ai' ? 'ai' : (type==='agent' ? 'agent' : 'system'));
    const div = document.createElement('div');
    div.className = `msg ${cls}`;
    div.innerHTML = `
      <div class="bubble">
        ${escapeHtml(body).replace(/\n/g,'<br>')}
        <div class="meta">
          ${type==='user' ? 'Tú' : capitalize(type)} • ${created || timeNow()} ${isSolution?' • ✅ Solución':''}
        </div>
      </div>`;
    return div;
  }

  function typingBubble(){
    const b = document.createElement('div');
    b.className = 'msg ai typing';
    b.innerHTML = `
      <div class="bubble">
        <span class="dot"></span><span class="dot"></span><span class="dot"></span>
        <div class="meta">IA está escribiendo…</div>
      </div>`;
    b.dataset.typing = '1';
    return b;
  }

  function removeTyping(){
    const t = chatbox.querySelector('.msg.typing');
    if (t) t.remove();
  }

  function setStatus(text){ if(statusEl) statusEl.textContent = text; }

  function disableComposer(disabled){
    if (!composer) return;
    if (input)  input.disabled  = disabled;
    if (sendBtn) sendBtn.disabled = disabled;
    if (escBtn)  escBtn.disabled  = disabled;
  }

  function ticketId(){
    return chatbox?.dataset?.ticketId || '';
  }
  function setTicketId(id){
    if (chatbox) chatbox.dataset.ticketId = id;
    if (chatbox && chatbox.style.display==='none') chatbox.style.display='';
    if (composer && composer.style.display==='none') composer.style.display='';
  }

  function escapeHtml(str){
    const div=document.createElement('div'); div.textContent = str ?? ''; return div.innerHTML;
  }
  function capitalize(s){ return (s||'').charAt(0).toUpperCase() + (s||'').slice(1); }

  // === Enviar mensaje (sin recargar)
  if (sendBtn && input && chatbox) {
    sendBtn.addEventListener('click', async () => {
      const text = (input.value||'').trim();
      if (!text) return;
      if (!ticketId()) return;
      disableComposer(true);

      input.value = '';

      const tBub = typingBubble();
      chatbox.appendChild(tBub);
      scrollBottom();

      try {
        const url = `${routes.tBase}/${ticketId()}/message`;
        const res = await fetch(url, {
          method: 'POST',
          headers: {
            'Content-Type':'application/json',
            'Accept':'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With':'XMLHttpRequest',
          },
          body: JSON.stringify({ message: text }),
        });
        const data = await res.json();

        removeTyping();
        if (!data?.ok) throw new Error('Error en respuesta');

        if (data.appended && Array.isArray(data.appended)) {
          data.appended.forEach(m => {
            chatbox.appendChild(bubble(m.type, m.body, m.created_at, m.is_solution));
          });
          scrollBottom();
        }
        if (data.status) setStatus('Estatus: ' + data.status.toUpperCase());
      } catch (e) {
        removeTyping();
        chatbox.appendChild(bubble('system', 'No se pudo enviar. Intenta de nuevo o escala a humano.', timeNow(), false));
        scrollBottom();
      } finally {
        disableComposer(false);
      }
    });
  }

  // === Escalar a humano (sin recargar)
  if (escBtn && chatbox) {
    escBtn.addEventListener('click', async () => {
      if (!ticketId()) return;
      disableComposer(true);
      const url = `${routes.tBase}/${ticketId()}/escalar`;
      try {
        const res = await fetch(url, {
          method: 'POST',
          headers: {
            'Content-Type':'application/json',
            'Accept':'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With':'XMLHttpRequest',
          },
          body: JSON.stringify({ escalate: true }),
        });
        const data = await res.json();
        if (data?.ok && data.appended) {
          data.appended.forEach(m => {
            chatbox.appendChild(bubble(m.type, m.body, m.created_at, m.is_solution));
          });
          if (data.status) setStatus('Estatus: ' + data.status.toUpperCase());
          scrollBottom();
        } else {
          chatbox.appendChild(bubble('system','No se pudo escalar, intenta más tarde.', timeNow(), false));
        }
      } catch (e) {
        chatbox.appendChild(bubble('system','No se pudo escalar, intenta más tarde.', timeNow(), false));
      } finally {
        disableComposer(false);
      }
    });
  }

  // === Crear ticket (AJAX) para no recargar
  if (newForm) {
    newForm.addEventListener('submit', async (ev) => {
      ev.preventDefault();
      const form = new FormData(newForm);
      const btn = newForm.querySelector('button[type=submit]');
      if (btn) { btn.disabled = true; btn.textContent = 'Preguntando a la IA…'; }

      try {
        const res = await fetch(newForm.action, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: form,
        });
        const data = await res.json();
        if (!data?.ok) throw new Error('Error al crear ticket');

        newForm.style.display = 'none';
        setTicketId(data.ticket.id);
        setStatus('Estatus: ' + (data.ticket.status||'').toUpperCase());

        chatbox.innerHTML = '';
        chatbox.style.display = '';
        composer.style.display = '';

        (data.messages || []).forEach(m => {
          chatbox.appendChild(bubble(m.type, m.body, m.created_at, m.is_solution));
        });
        scrollBottom();
      } catch (e) {
        alert('No se pudo crear el ticket. Intenta de nuevo.');
      } finally {
        if (btn) { btn.disabled = false; btn.textContent = 'Enviar al asistente IA ✨'; }
      }
    });
  }

  scrollBottom();
})();
</script>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function () {
  const root = document.getElementById('helpdesk');
  if (!root) return;

  const subject = root.querySelector('input[name="subject"]');
  if (subject && !subject.value.trim()) {
    subject.value = 'Consulta desde Centro de Ayuda IA';
  }

  const category = root.querySelector('select[name="category"]');
  if (category && !category.value) {
    const option = Array.from(category.options).find(o => o.value && o.value.trim() !== '');
    if (option) category.value = option.value;
  }

  const textarea = root.querySelector('textarea[name="message"]');
  if (textarea) {
    textarea.placeholder = 'Escribe tu problema o pregunta. Ejemplo: No puedo completar mi pago, ya revisé mi dirección y el envío aparece correcto...';
  }
});
</script>

<style>
/* Ajuste compacto Centro de Ayuda IA */
#helpdesk{
  padding:38px 20px !important;
  min-height:auto !important;
}

#helpdesk .wrap{
  max-width:860px !important;
}

#helpdesk .head{
  padding:20px 24px 14px !important;
}

#helpdesk .h1{
  font-size:clamp(22px,2.4vw,28px) !important;
}

#helpdesk .h1::before{
  width:40px !important;
  height:40px !important;
  border-radius:15px !important;
}

#helpdesk .new{
  padding:22px 24px 24px !important;
}

#helpdesk textarea{
  min-height:180px !important;
  padding:18px 20px !important;
  border-radius:20px !important;
}

#helpdesk .btn{
  min-height:46px !important;
  padding:0 18px !important;
  border-radius:16px !important;
}

#helpdesk .new > div:last-child{
  margin-top:14px !important;
}

#helpdesk .card{
  border-radius:24px !important;
}

@media (max-width:720px){
  #helpdesk{
    padding:24px 12px !important;
  }

  #helpdesk .new{
    padding:18px !important;
  }

  #helpdesk textarea{
    min-height:170px !important;
  }
}
</style>

<style>
/* Botones compactos del chat IA */
#helpdesk .composer{
  align-items:center !important;
  gap:8px !important;
  padding:12px 14px !important;
}

#helpdesk .composer .btn,
#helpdesk #sendBtn,
#helpdesk #escalarBtn{
  width:auto !important;
  height:38px !important;
  min-height:38px !important;
  padding:0 14px !important;
  border-radius:12px !important;
  font-size:13px !important;
  line-height:1 !important;
  white-space:nowrap !important;
  box-shadow:0 8px 18px rgba(37,99,235,.14) !important;
}

#helpdesk #sendBtn{
  min-width:82px !important;
}

#helpdesk #escalarBtn{
  min-width:auto !important;
  background:#ffffff !important;
  color:#2563eb !important;
  border:1px solid rgba(37,99,235,.18) !important;
}

#helpdesk .composer textarea{
  min-height:42px !important;
  height:42px !important;
  padding:11px 14px !important;
  border-radius:14px !important;
  font-size:14px !important;
}

@media (max-width:720px){
  #helpdesk .composer{
    flex-wrap:wrap !important;
  }

  #helpdesk .composer textarea{
    flex-basis:100% !important;
  }

  #helpdesk .composer .btn,
  #helpdesk #sendBtn,
  #helpdesk #escalarBtn{
    height:38px !important;
    min-height:38px !important;
  }
}
</style>

<style>
/* Botón único para agrandar/reducir caja de texto */
#helpdesk .mini-expand{
  width:30px !important;
  height:30px !important;
  min-width:30px !important;
  padding:0 !important;
  border-radius:10px !important;
  border:1px solid rgba(37,99,235,.20) !important;
  background:#fff !important;
  color:#2563eb !important;
  font-size:14px !important;
  font-weight:900 !important;
  line-height:1 !important;
  cursor:pointer !important;
  box-shadow:0 6px 14px rgba(15,23,42,.08) !important;
}

#helpdesk .mini-expand:hover{
  background:#eff6ff !important;
}

#helpdesk .composer.is-expanded{
  align-items:flex-end !important;
}

#helpdesk .composer.is-expanded textarea{
  height:130px !important;
  min-height:130px !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const root = document.getElementById('helpdesk');
  if (!root) return;

  const composer = root.querySelector('#composerWrap');
  const textarea = root.querySelector('#composerInput');
  const btn = root.querySelector('#expandComposerBtn');

  if (!composer || !textarea || !btn) return;

  btn.addEventListener('click', function () {
    const expanded = composer.classList.toggle('is-expanded');
    btn.textContent = expanded ? '⤡' : '⤢';
    btn.title = expanded ? 'Reducir caja' : 'Agrandar caja';
    textarea.focus();
  });
});
</script>

<style>
/* Quitar resize nativo y flechas/scroll visibles del textarea */
#helpdesk textarea,
#helpdesk #composerInput{
  resize:none !important;
  overflow:hidden !important;
  scrollbar-width:none !important;
}

#helpdesk textarea::-webkit-scrollbar,
#helpdesk #composerInput::-webkit-scrollbar{
  width:0 !important;
  height:0 !important;
  display:none !important;
}
</style>

<style>
/* Barra de chats guardados IA */
#helpdesk .saved-chats{
  margin:0 0 14px 0 !important;
  padding:12px !important;
  border:1px solid rgba(37,99,235,.12) !important;
  border-radius:22px !important;
  background:rgba(255,255,255,.82) !important;
  box-shadow:0 12px 28px rgba(15,23,42,.07) !important;
  backdrop-filter:blur(12px) !important;
}

#helpdesk .saved-chats-head{
  display:flex !important;
  align-items:center !important;
  justify-content:space-between !important;
  gap:12px !important;
  margin-bottom:10px !important;
  padding:0 4px !important;
  font-size:13px !important;
  font-weight:900 !important;
  color:#0f172a !important;
}

#helpdesk .saved-chats-head a{
  font-size:12px !important;
  font-weight:900 !important;
  color:#2563eb !important;
  text-decoration:none !important;
}

#helpdesk .saved-chats-list{
  display:flex !important;
  gap:8px !important;
  overflow-x:auto !important;
  padding-bottom:2px !important;
  scrollbar-width:none !important;
}

#helpdesk .saved-chats-list::-webkit-scrollbar{
  display:none !important;
}

#helpdesk .saved-chat{
  flex:0 0 auto !important;
  min-width:180px !important;
  max-width:220px !important;
  padding:10px 12px !important;
  border-radius:16px !important;
  border:1px solid rgba(15,23,42,.08) !important;
  background:#fff !important;
  text-decoration:none !important;
  color:#0f172a !important;
  transition:.18s ease !important;
}

#helpdesk .saved-chat:hover{
  transform:translateY(-1px) !important;
  border-color:rgba(37,99,235,.28) !important;
  box-shadow:0 10px 22px rgba(37,99,235,.10) !important;
}

#helpdesk .saved-chat.active{
  background:linear-gradient(135deg,#2563eb,#7c3aed) !important;
  color:#fff !important;
  border-color:transparent !important;
  box-shadow:0 12px 24px rgba(37,99,235,.22) !important;
}

#helpdesk .saved-chat-main{
  display:flex !important;
  flex-direction:column !important;
  gap:4px !important;
}

#helpdesk .saved-chat-title{
  font-size:13px !important;
  font-weight:900 !important;
  line-height:1.2 !important;
  white-space:nowrap !important;
  overflow:hidden !important;
  text-overflow:ellipsis !important;
}

#helpdesk .saved-chat-meta{
  font-size:10px !important;
  font-weight:800 !important;
  letter-spacing:.04em !important;
  color:#64748b !important;
}

#helpdesk .saved-chat.active .saved-chat-meta{
  color:rgba(255,255,255,.78) !important;
}

@media(max-width:720px){
  #helpdesk .saved-chats{
    border-radius:18px !important;
    padding:10px !important;
  }

  #helpdesk .saved-chat{
    min-width:155px !important;
    max-width:175px !important;
    padding:9px 10px !important;
  }
}
</style>

<style>
/* Layout lateral de chats guardados IA */
#helpdesk .wrap{
  max-width:1180px !important;
  display:grid !important;
  grid-template-columns:270px minmax(0,1fr) !important;
  gap:18px !important;
  align-items:start !important;
}

#helpdesk .head{
  grid-column:1 / -1 !important;
}

#helpdesk .saved-chats{
  position:sticky !important;
  top:92px !important;
  min-height:420px !important;
  max-height:calc(100vh - 130px) !important;
  overflow:hidden !important;
  padding:14px !important;
  border:1px solid rgba(37,99,235,.12) !important;
  border-radius:24px !important;
  background:rgba(255,255,255,.86) !important;
  box-shadow:0 18px 42px rgba(15,23,42,.08) !important;
  backdrop-filter:blur(14px) !important;
}

#helpdesk .saved-chats-head{
  display:flex !important;
  align-items:center !important;
  justify-content:space-between !important;
  gap:10px !important;
  margin-bottom:12px !important;
  padding:2px 2px 10px !important;
  border-bottom:1px solid rgba(15,23,42,.07) !important;
  font-size:15px !important;
  font-weight:950 !important;
  color:#0f172a !important;
}

#helpdesk .saved-chats-head a{
  display:inline-flex !important;
  align-items:center !important;
  justify-content:center !important;
  min-height:30px !important;
  padding:0 10px !important;
  border-radius:12px !important;
  background:#eff6ff !important;
  color:#2563eb !important;
  text-decoration:none !important;
  font-size:12px !important;
  font-weight:950 !important;
}

#helpdesk .saved-chats-list{
  display:flex !important;
  flex-direction:column !important;
  gap:8px !important;
  max-height:calc(100vh - 210px) !important;
  overflow-y:auto !important;
  padding-right:3px !important;
  scrollbar-width:none !important;
}

#helpdesk .saved-chats-list::-webkit-scrollbar{
  display:none !important;
}

#helpdesk .saved-chat{
  display:flex !important;
  flex-direction:column !important;
  gap:5px !important;
  padding:12px 13px !important;
  border-radius:17px !important;
  border:1px solid rgba(15,23,42,.08) !important;
  background:#fff !important;
  color:#0f172a !important;
  text-decoration:none !important;
  transition:.18s ease !important;
}

#helpdesk .saved-chat:hover{
  transform:translateX(2px) !important;
  border-color:rgba(37,99,235,.28) !important;
  box-shadow:0 10px 22px rgba(37,99,235,.10) !important;
}

#helpdesk .saved-chat.active{
  background:linear-gradient(135deg,#2563eb,#7c3aed) !important;
  color:#fff !important;
  border-color:transparent !important;
  box-shadow:0 14px 28px rgba(37,99,235,.22) !important;
}

#helpdesk .saved-chat-title{
  display:block !important;
  font-size:13px !important;
  font-weight:950 !important;
  line-height:1.25 !important;
  white-space:nowrap !important;
  overflow:hidden !important;
  text-overflow:ellipsis !important;
}

#helpdesk .saved-chat-meta{
  display:block !important;
  font-size:10px !important;
  font-weight:850 !important;
  letter-spacing:.04em !important;
  color:#64748b !important;
}

#helpdesk .saved-chat.active .saved-chat-meta{
  color:rgba(255,255,255,.78) !important;
}

#helpdesk .saved-empty{
  padding:14px 10px !important;
  border-radius:16px !important;
  background:#f8fafc !important;
  color:#64748b !important;
  font-size:13px !important;
  font-weight:750 !important;
  line-height:1.35 !important;
}

#helpdesk .card{
  min-width:0 !important;
}

@media(max-width:900px){
  #helpdesk .wrap{
    display:block !important;
    max-width:860px !important;
  }

  #helpdesk .saved-chats{
    position:relative !important;
    top:auto !important;
    min-height:auto !important;
    max-height:none !important;
    margin-bottom:14px !important;
    padding:12px !important;
    border-radius:20px !important;
  }

  #helpdesk .saved-chats-list{
    flex-direction:row !important;
    overflow-x:auto !important;
    overflow-y:hidden !important;
    max-height:none !important;
    padding-bottom:2px !important;
  }

  #helpdesk .saved-chat{
    flex:0 0 170px !important;
  }
}
</style>

<style>
/* Borrar chat lateral */
#helpdesk .saved-chat-row{
  position:relative !important;
  display:block !important;
}

#helpdesk .saved-chat-row .saved-chat{
  padding-right:42px !important;
}

#helpdesk .saved-chat-row.active .saved-chat{
  background:linear-gradient(135deg,#2563eb,#7c3aed) !important;
  color:#fff !important;
  border-color:transparent !important;
  box-shadow:0 14px 28px rgba(37,99,235,.22) !important;
}

#helpdesk .saved-chat-row.active .saved-chat-meta{
  color:rgba(255,255,255,.78) !important;
}

#helpdesk .saved-chat-delete-form{
  position:absolute !important;
  top:50% !important;
  right:8px !important;
  transform:translateY(-50%) !important;
  margin:0 !important;
  z-index:3 !important;
}

#helpdesk .saved-chat-delete{
  width:24px !important;
  height:24px !important;
  min-width:24px !important;
  border:0 !important;
  border-radius:9px !important;
  background:rgba(239,68,68,.10) !important;
  color:#ef4444 !important;
  font-size:17px !important;
  font-weight:950 !important;
  line-height:1 !important;
  cursor:pointer !important;
}

#helpdesk .saved-chat-delete:hover{
  background:#ef4444 !important;
  color:#fff !important;
}

#helpdesk .saved-chat-row.active .saved-chat-delete{
  background:rgba(255,255,255,.18) !important;
  color:#fff !important;
}

#helpdesk .saved-chat-row.active .saved-chat-delete:hover{
  background:#fff !important;
  color:#ef4444 !important;
}
</style>

<style>
/* =========================================================
   JURETO IMMERSIVE HELP UI
   Visual only. No toca l�gica IA / carrito / tickets.
========================================================= */

:root {
  --jureto-bg: #05080d;
  --jureto-bg-2: #07111f;
  --jureto-blue: #0a7dda;
  --jureto-blue-2: #159bff;
  --jureto-cyan: #62c7ff;
  --jureto-ink: #eaf6ff;
  --jureto-muted: #9fb3c8;
  --jureto-line: rgba(98,199,255,.22);
  --jureto-glass: rgba(7,17,31,.72);
  --jureto-glow: 0 0 28px rgba(21,155,255,.32);
}

/* Fondo inmersivo */
body {
  background:
    radial-gradient(circle at 12% 10%, rgba(21,155,255,.22), transparent 28%),
    radial-gradient(circle at 88% 16%, rgba(10,125,218,.18), transparent 30%),
    linear-gradient(135deg, #030509 0%, #07111f 48%, #020409 100%) !important;
  color: var(--jureto-ink);
}

/* Red tipo mol�culas / conexiones */
body::before {
  content: "";
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 0;
  background-image:
    linear-gradient(115deg, transparent 0 13%, rgba(98,199,255,.17) 13.2%, transparent 13.6%),
    linear-gradient(35deg, transparent 0 62%, rgba(98,199,255,.12) 62.2%, transparent 62.6%),
    radial-gradient(circle, rgba(255,255,255,.75) 0 2px, transparent 3px);
  background-size: 460px 260px, 520px 320px, 140px 140px;
  opacity: .32;
  animation: juretoNetMove 18s linear infinite;
}

@keyframes juretoNetMove {
  from { background-position: 0 0, 0 0, 0 0; }
  to { background-position: 160px 90px, -120px 80px, 90px -60px; }
}

/* Que el contenido quede encima del fondo */
main,
.page,
.container,
.help-page,
.help-wrap,
.ai-shell,
.chat-shell,
section {
  position: relative;
  z-index: 1;
}

/* Tarjetas estilo vidrio */
.card,
.panel,
.ticket-card,
.help-card,
.chat-card,
.ai-card,
.chat-shell,
.help-wrap,
form,
aside {
  background: linear-gradient(145deg, rgba(7,17,31,.88), rgba(4,8,15,.76)) !important;
  border: 1px solid var(--jureto-line) !important;
  box-shadow: 0 20px 60px rgba(0,0,0,.34), var(--jureto-glow) !important;
  backdrop-filter: blur(14px);
}

/* T�tulos con esencia del logo */
h1, h2, h3,
.help-title,
.chat-title,
.ai-title {
  color: var(--jureto-ink) !important;
  letter-spacing: .02em;
  text-shadow: 0 0 18px rgba(21,155,255,.35);
}

/* Texto secundario */
p,
.muted,
.text-muted,
small,
.help-subtitle {
  color: var(--jureto-muted) !important;
}

/* Inputs y textarea */
input,
textarea,
select {
  background: rgba(2,8,18,.86) !important;
  color: var(--jureto-ink) !important;
  border: 1px solid rgba(98,199,255,.24) !important;
  box-shadow: inset 0 0 0 1px rgba(21,155,255,.04);
}

input:focus,
textarea:focus,
select:focus {
  border-color: var(--jureto-blue-2) !important;
  box-shadow: 0 0 0 3px rgba(21,155,255,.18), 0 0 24px rgba(21,155,255,.22) !important;
  outline: none !important;
}

/* Botones premium */
button,
.btn,
[type="submit"] {
  border-radius: 14px !important;
  border: 1px solid rgba(98,199,255,.35) !important;
  transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}

button:hover,
.btn:hover,
[type="submit"]:hover {
  transform: translateY(-1px);
  box-shadow: 0 0 24px rgba(21,155,255,.35) !important;
  border-color: var(--jureto-blue-2) !important;
}

/* Bot�n principal azul Jureto */
button[type="submit"],
.btn-primary,
.bg-blue-600,
.bg-indigo-600 {
  background: linear-gradient(135deg, #0758b8, #159bff) !important;
  color: white !important;
}

/* Mensajes tipo chat */
.message,
.chat-message,
.bubble,
.ai-message,
.user-message {
  border-radius: 18px !important;
  border: 1px solid rgba(98,199,255,.18);
}

.user-message,
.message.user,
.bubble.user {
  background: linear-gradient(135deg, #0758b8, #159bff) !important;
  color: #fff !important;
  box-shadow: 0 0 24px rgba(21,155,255,.24);
}

.ai-message,
.message.ai,
.bubble.ai,
.message.assistant,
.bubble.assistant {
  background: rgba(3,10,20,.84) !important;
  color: var(--jureto-ink) !important;
}

/* Links */
a {
  color: var(--jureto-cyan);
}

a:hover {
  color: #ffffff;
  text-shadow: 0 0 12px rgba(21,155,255,.55);
}

/* Scrollbar tech */
*::-webkit-scrollbar {
  width: 9px;
  height: 9px;
}

*::-webkit-scrollbar-track {
  background: rgba(255,255,255,.04);
}

*::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #0a7dda, #62c7ff);
  border-radius: 999px;
}

/* Detalle de marca flotante */
body::after {
  content: "SOLUCIONES JURETO";
  position: fixed;
  right: 24px;
  bottom: 18px;
  z-index: 0;
  color: rgba(98,199,255,.08);
  font-size: clamp(22px, 4vw, 56px);
  font-weight: 900;
  letter-spacing: .14em;
  pointer-events: none;
}
</style>


<style>
/* FIX REAL: barra #composerInput con tama�o actual y contorno luminoso */
#helpdesk #composerWrap {
  position: relative !important;
}

#helpdesk #composerInput {
  height: 42px !important;
  min-height: 42px !important;
  max-height: 42px !important;
  padding: 11px 14px !important;
  border-radius: 14px !important;
  resize: none !important;
  overflow: hidden !important;

  background:
    linear-gradient(135deg, #07111f, #0b1628) padding-box,
    linear-gradient(90deg, #0a7dda, #62c7ff, #ffffff, #159bff, #0a7dda) border-box !important;

  border: 2px solid transparent !important;
  color: #eaf6ff !important;
  box-shadow:
    inset 0 0 14px rgba(21,155,255,.12),
    0 0 18px rgba(21,155,255,.22) !important;

  background-size: 100% 100%, 260% 260% !important;
  transition: box-shadow .2s ease, filter .2s ease !important;
}

#helpdesk #composerInput::placeholder {
  color: rgba(190,220,255,.62) !important;
}

#helpdesk #composerInput:focus {
  animation: juretoComposerBorderFlow 1.25s linear infinite !important;
  box-shadow:
    inset 0 0 16px rgba(21,155,255,.18),
    0 0 0 2px rgba(98,199,255,.18),
    0 0 28px rgba(21,155,255,.42) !important;
  outline: none !important;
}

#helpdesk #composerInput.jureto-thinking {
  animation: juretoComposerBorderFlow .75s linear infinite, juretoComposerPulse .75s ease-in-out infinite !important;
}

@keyframes juretoComposerBorderFlow {
  0% {
    background-position: 0 0, 0% 50%;
  }
  100% {
    background-position: 0 0, 260% 50%;
  }
}

@keyframes juretoComposerPulse {
  0%, 100% {
    filter: brightness(1);
    box-shadow:
      inset 0 0 16px rgba(21,155,255,.16),
      0 0 18px rgba(21,155,255,.28) !important;
  }
  50% {
    filter: brightness(1.18);
    box-shadow:
      inset 0 0 22px rgba(98,199,255,.22),
      0 0 0 2px rgba(98,199,255,.22),
      0 0 36px rgba(98,199,255,.60) !important;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const input = document.getElementById('composerInput');
  const sendBtn = document.getElementById('sendBtn');

  if (!input || !sendBtn) return;

  sendBtn.addEventListener('click', function () {
    input.classList.add('jureto-thinking');

    clearTimeout(input._juretoThinkingTimer);
    input._juretoThinkingTimer = setTimeout(function () {
      input.classList.remove('jureto-thinking');
    }, 9000);
  });
});
</script>


<style>
/* Movimiento visible de la l�nea iluminada del composer */
#helpdesk #composerInput {
  background:
    linear-gradient(135deg, #07111f, #0b1628) padding-box,
    conic-gradient(
      from var(--jureto-angle, 0deg),
      #0a7dda,
      #62c7ff,
      #ffffff,
      #159bff,
      #003f8c,
      #0a7dda
    ) border-box !important;

  border: 2px solid transparent !important;
  animation: juretoComposerRotateLine 2.2s linear infinite !important;
}

#helpdesk #composerInput:focus,
#helpdesk #composerInput.jureto-thinking {
  animation:
    juretoComposerRotateLine .9s linear infinite,
    juretoComposerPulse .75s ease-in-out infinite !important;
}

@property --jureto-angle {
  syntax: "<angle>";
  initial-value: 0deg;
  inherits: false;
}

@keyframes juretoComposerRotateLine {
  from {
    --jureto-angle: 0deg;
  }
  to {
    --jureto-angle: 360deg;
  }
}
</style>


<style>
/* FIX: permitir agrandar caja sin perder l�nea animada */
#helpdesk #composerWrap.is-expanded #composerInput {
  height: 130px !important;
  min-height: 130px !important;
  max-height: 130px !important;
  padding: 14px 16px !important;
  overflow: auto !important;
  resize: none !important;
  line-height: 1.55 !important;
}

/* Tama�o normal cuando NO est� expandida */
#helpdesk #composerWrap:not(.is-expanded) #composerInput {
  height: 42px !important;
  min-height: 42px !important;
  max-height: 42px !important;
  overflow: hidden !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const composer = document.getElementById('composerWrap');
  const input = document.getElementById('composerInput');
  const btn = document.getElementById('expandComposerBtn');

  if (!composer || !input || !btn) return;

  btn.addEventListener('click', function () {
    setTimeout(function () {
      if (composer.classList.contains('is-expanded')) {
        input.style.height = '130px';
        input.style.minHeight = '130px';
        input.style.maxHeight = '130px';
      } else {
        input.style.height = '42px';
        input.style.minHeight = '42px';
        input.style.maxHeight = '42px';
      }
      input.focus();
    }, 10);
  });
});
</script>


<style>
/* =====================================================
   JURETO: l�nea animada en chats pasados y botones
===================================================== */

@property --jureto-border-angle {
  syntax: "<angle>";
  initial-value: 0deg;
  inherits: false;
}

@keyframes juretoBorderRotate {
  from { --jureto-border-angle: 0deg; }
  to   { --jureto-border-angle: 360deg; }
}

/* CHATS PASADOS */
#helpdesk .saved-chat-row,
#helpdesk .saved-chat,
#helpdesk .saved-chat-row.active {
  position: relative !important;
  border-radius: 18px !important;
}

#helpdesk .saved-chat-row {
  padding: 2px !important;
  background:
    conic-gradient(
      from var(--jureto-border-angle),
      #0a7dda,
      #62c7ff,
      #ffffff,
      #159bff,
      #6d28d9,
      #0a7dda
    ) !important;
  animation: juretoBorderRotate 2.4s linear infinite !important;
}

/* Solo se nota fuerte en activo o hover */
#helpdesk .saved-chat-row:not(.active):not(:hover) {
  background: transparent !important;
  animation: none !important;
}

#helpdesk .saved-chat-row.active,
#helpdesk .saved-chat-row:hover {
  box-shadow:
    0 0 0 1px rgba(98,199,255,.16),
    0 0 22px rgba(21,155,255,.26) !important;
}

/* Interior del chat guardado */
#helpdesk .saved-chat-row .saved-chat {
  display: block !important;
  border-radius: 16px !important;
  background:
    linear-gradient(135deg, rgba(7,17,31,.96), rgba(13,35,64,.94)) !important;
  color: #eaf6ff !important;
  border: 0 !important;
}

/* Mantener el activo con vibra azul/morada */
#helpdesk .saved-chat-row.active .saved-chat {
  background:
    radial-gradient(circle at 12% 20%, rgba(98,199,255,.22), transparent 34%),
    linear-gradient(135deg, #1d4ed8, #2563eb, #6d28d9) !important;
  box-shadow:
    inset 0 0 18px rgba(255,255,255,.08),
    0 0 24px rgba(21,155,255,.28) !important;
}

/* BOTONES CON BORDE EN MOVIMIENTO */
#helpdesk .btn,
#helpdesk #sendBtn,
#helpdesk #escalarBtn,
#helpdesk #expandComposerBtn,
#helpdesk .mini-expand,
#helpdesk a[href*="ayuda"] {
  position: relative !important;
  isolation: isolate !important;
  border: 2px solid transparent !important;
  background:
    linear-gradient(135deg, #0758b8, #159bff) padding-box,
    conic-gradient(
      from var(--jureto-border-angle),
      #0a7dda,
      #62c7ff,
      #ffffff,
      #159bff,
      #6d28d9,
      #0a7dda
    ) border-box !important;
  animation: juretoBorderRotate 2.2s linear infinite !important;
  box-shadow:
    0 0 16px rgba(21,155,255,.24),
    inset 0 0 12px rgba(255,255,255,.08) !important;
}

/* Bot�n fantasma: conservar blanco pero con l�nea animada */
#helpdesk .btn-ghost,
#helpdesk #escalarBtn {
  color: #2563eb !important;
  background:
    linear-gradient(#ffffff, #ffffff) padding-box,
    conic-gradient(
      from var(--jureto-border-angle),
      #0a7dda,
      #62c7ff,
      #ffffff,
      #159bff,
      #6d28d9,
      #0a7dda
    ) border-box !important;
}

/* Hover m�s premium */
#helpdesk .btn:hover,
#helpdesk #sendBtn:hover,
#helpdesk #escalarBtn:hover,
#helpdesk #expandComposerBtn:hover,
#helpdesk .mini-expand:hover,
#helpdesk .saved-chat-row:hover {
  filter: brightness(1.08) !important;
  transform: translateY(-1px) !important;
  box-shadow:
    0 0 0 2px rgba(98,199,255,.18),
    0 0 28px rgba(98,199,255,.42) !important;
}

/* Botones desactivados */
#helpdesk .btn:disabled,
#helpdesk #sendBtn:disabled,
#helpdesk #escalarBtn:disabled,
#helpdesk #expandComposerBtn:disabled {
  opacity: .55 !important;
  animation: none !important;
  filter: grayscale(.2) !important;
}
</style>


<style>
/* FIX: que + Nuevo y agrandar caja sigan vi�ndose como botones */
#helpdesk #expandComposerBtn,
#helpdesk .mini-expand {
  width: 38px !important;
  min-width: 38px !important;
  height: 38px !important;
  min-height: 38px !important;
  padding: 0 !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  border-radius: 13px !important;
  color: #2563eb !important;
  font-weight: 900 !important;
  font-size: 15px !important;

  background:
    linear-gradient(#ffffff, #ffffff) padding-box,
    conic-gradient(
      from var(--jureto-border-angle),
      #0a7dda,
      #62c7ff,
      #ffffff,
      #159bff,
      #6d28d9,
      #0a7dda
    ) border-box !important;

  border: 2px solid transparent !important;
  animation: juretoBorderRotate 2s linear infinite !important;
  box-shadow:
    0 0 14px rgba(21,155,255,.24),
    inset 0 0 10px rgba(21,155,255,.06) !important;
}

/* Bot�n + Nuevo */
#helpdesk a[href="{{ route('help.create') }}"],
#helpdesk a[href*="/ayuda"]:has(+ *),
#helpdesk .saved-chats-head a,
#helpdesk .saved-chats-header a {
  min-height: 34px !important;
  padding: 7px 13px !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 5px !important;
  border-radius: 999px !important;
  color: #2563eb !important;
  font-weight: 900 !important;
  font-size: 13px !important;
  text-decoration: none !important;

  background:
    linear-gradient(#ffffff, #ffffff) padding-box,
    conic-gradient(
      from var(--jureto-border-angle),
      #0a7dda,
      #62c7ff,
      #ffffff,
      #159bff,
      #6d28d9,
      #0a7dda
    ) border-box !important;

  border: 2px solid transparent !important;
  animation: juretoBorderRotate 2.2s linear infinite !important;
  box-shadow:
    0 0 16px rgba(21,155,255,.20),
    inset 0 0 10px rgba(21,155,255,.05) !important;
}

#helpdesk #expandComposerBtn:hover,
#helpdesk .mini-expand:hover,
#helpdesk .saved-chats-head a:hover,
#helpdesk .saved-chats-header a:hover {
  color: #0758b8 !important;
  transform: translateY(-1px) scale(1.03) !important;
  box-shadow:
    0 0 0 2px rgba(98,199,255,.16),
    0 0 26px rgba(98,199,255,.42) !important;
}
</style>


<style>
/* FIX: dejar el buscador superior como estaba */
#helpdesk input[placeholder*="Qu� quieres encontrar"],
#helpdesk input[placeholder*="quieres encontrar"],
input[placeholder*="Qu� quieres encontrar"],
input[placeholder*="quieres encontrar"] {
  animation: none !important;
  background: transparent !important;
  border: 0 !important;
  border-radius: 0 !important;
  box-shadow: none !important;
  color: inherit !important;
  padding: inherit !important;
  height: auto !important;
  min-height: unset !important;
  max-height: unset !important;
}

/* Evitar que el efecto de botones afecte el bot�n AI del buscador */
#helpdesk input[placeholder*="Qu� quieres encontrar"] ~ button,
#helpdesk input[placeholder*="quieres encontrar"] ~ button,
input[placeholder*="Qu� quieres encontrar"] ~ button,
input[placeholder*="quieres encontrar"] ~ button {
  animation: none !important;
  transform: none !important;
  box-shadow: none !important;
}

/* Si el contenedor del buscador recibi� borde animado, apagarlo */
#helpdesk input[placeholder*="Qu� quieres encontrar"]::before,
#helpdesk input[placeholder*="Qu� quieres encontrar"]::after,
#helpdesk input[placeholder*="quieres encontrar"]::before,
#helpdesk input[placeholder*="quieres encontrar"]::after {
  display: none !important;
}
</style>


<style>
/* RESTAURAR BUSCADOR SUPERIOR BLANCO */
#helpdesk input[placeholder*="Qu� quieres encontrar"],
#helpdesk input[placeholder*="quieres encontrar"],
input[placeholder*="Qu� quieres encontrar"],
input[placeholder*="quieres encontrar"] {
  background: transparent !important;
  background-image: none !important;
  border: 0 !important;
  outline: none !important;
  box-shadow: none !important;
  color: #374151 !important;
  height: auto !important;
  min-height: 0 !important;
  max-height: none !important;
  padding: 0 12px !important;
  font-size: 16px !important;
  animation: none !important;
}

/* Contenedor blanco del buscador */
#helpdesk input[placeholder*="Qu� quieres encontrar"]:parent,
#helpdesk input[placeholder*="quieres encontrar"]:parent {
  background: #ffffff !important;
}

/* Apagar efectos en el input y en su bot�n AI */
#helpdesk input[placeholder*="Qu� quieres encontrar"],
#helpdesk input[placeholder*="Qu� quieres encontrar"] + *,
#helpdesk input[placeholder*="quieres encontrar"],
#helpdesk input[placeholder*="quieres encontrar"] + * {
  animation: none !important;
  filter: none !important;
}

/* Bot�n AI del buscador como antes */
#helpdesk input[placeholder*="Qu� quieres encontrar"] ~ button,
#helpdesk input[placeholder*="quieres encontrar"] ~ button,
input[placeholder*="Qu� quieres encontrar"] ~ button,
input[placeholder*="quieres encontrar"] ~ button {
  width: 42px !important;
  height: 42px !important;
  min-width: 42px !important;
  min-height: 42px !important;
  border-radius: 999px !important;
  background: #ffffff !important;
  color: #315be8 !important;
  border: 1.5px solid rgba(49,91,232,.45) !important;
  box-shadow: none !important;
  animation: none !important;
  transform: none !important;
  font-weight: 900 !important;
}

/* Buscar el contenedor m�s cercano y devolverlo visualmente */
#helpdesk form:has(input[placeholder*="Qu� quieres encontrar"]),
#helpdesk div:has(> input[placeholder*="Qu� quieres encontrar"]),
#helpdesk div:has(input[placeholder*="Qu� quieres encontrar"]) {
  background: #ffffff !important;
  border: 1px solid rgba(15,23,42,.08) !important;
  border-radius: 999px !important;
  box-shadow: 0 8px 22px rgba(15,23,42,.06) !important;
  animation: none !important;
}

#helpdesk form:has(input[placeholder*="quieres encontrar"]),
#helpdesk div:has(> input[placeholder*="quieres encontrar"]),
#helpdesk div:has(input[placeholder*="quieres encontrar"]) {
  background: #ffffff !important;
  border: 1px solid rgba(15,23,42,.08) !important;
  border-radius: 999px !important;
  box-shadow: 0 8px 22px rgba(15,23,42,.06) !important;
  animation: none !important;
}
</style>


<style>
/* BUSCADOR SUPERIOR RESTAURADO POR CLASES JS */
.jureto-search-clean-box {
  background: #ffffff !important;
  border: 1px solid rgba(15,23,42,.08) !important;
  border-radius: 999px !important;
  box-shadow: 0 8px 22px rgba(15,23,42,.06) !important;
  animation: none !important;
  filter: none !important;
  overflow: hidden !important;
}

.jureto-search-clean-box::before,
.jureto-search-clean-box::after {
  display: none !important;
  content: none !important;
}

.jureto-search-clean-input {
  background: transparent !important;
  background-image: none !important;
  color: #374151 !important;
  border: 0 !important;
  box-shadow: none !important;
  outline: none !important;
  animation: none !important;
  filter: none !important;
}

.jureto-search-clean-input::placeholder {
  color: #7b8190 !important;
}

.jureto-search-clean-ai {
  background: #ffffff !important;
  color: #315be8 !important;
  border: 1.5px solid rgba(49,91,232,.45) !important;
  box-shadow: none !important;
  animation: none !important;
  filter: none !important;
  transform: none !important;
  border-radius: 999px !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  function restoreSearchBar() {
    const input = Array.from(document.querySelectorAll('input')).find(function (el) {
      return (el.getAttribute('placeholder') || '').toLowerCase().includes('qu� quieres encontrar') ||
             (el.getAttribute('placeholder') || '').toLowerCase().includes('que quieres encontrar') ||
             (el.getAttribute('placeholder') || '').toLowerCase().includes('quieres encontrar');
    });

    if (!input) return;

    input.classList.add('jureto-search-clean-input');

    let box = input.parentElement;

    for (let i = 0; i < 4; i++) {
      if (!box) break;

      const rect = box.getBoundingClientRect();

      if (rect.width > 250 && rect.height >= 38 && rect.height <= 80) {
        box.classList.add('jureto-search-clean-box');
        break;
      }

      box = box.parentElement;
    }

    const container = input.closest('.jureto-search-clean-box') || input.parentElement;
    if (container) {
      const aiButton = Array.from(container.querySelectorAll('button, a, span, div')).find(function (el) {
        return (el.textContent || '').trim().toLowerCase() === 'ai';
      });

      if (aiButton) {
        aiButton.classList.add('jureto-search-clean-ai');
      }
    }
  }

  restoreSearchBar();
  setTimeout(restoreSearchBar, 500);
  setTimeout(restoreSearchBar, 1500);
  setTimeout(restoreSearchBar, 3000);
});
</script>


<style>
/* =====================================================
   JURETO: burbujas del chat flotantes / glass
===================================================== */

/* Separaci�n general para que respiren */
#helpdesk .chat {
  padding: 18px 14px 24px !important;
}

/* Caja base de mensaje */
#helpdesk .msg .bubble {
  position: relative !important;
  border-radius: 20px !important;
  border: 1px solid rgba(98,199,255,.18) !important;
  backdrop-filter: blur(14px) saturate(140%) !important;
  -webkit-backdrop-filter: blur(14px) saturate(140%) !important;
  box-shadow:
    0 18px 45px rgba(15,23,42,.14),
    0 6px 18px rgba(21,155,255,.08),
    inset 0 1px 0 rgba(255,255,255,.55) !important;
  transform: translateY(0) !important;
  transition:
    transform .22s ease,
    box-shadow .22s ease,
    border-color .22s ease,
    background .22s ease !important;
  overflow: hidden !important;
}

/* Brillo interior sutil */
#helpdesk .msg .bubble::before {
  content: "" !important;
  position: absolute !important;
  inset: 0 !important;
  pointer-events: none !important;
  border-radius: inherit !important;
  background:
    radial-gradient(circle at 18% 12%, rgba(255,255,255,.50), transparent 26%),
    linear-gradient(135deg, rgba(255,255,255,.20), transparent 42%) !important;
  opacity: .55 !important;
}

/* Mensajes de IA: vidrio claro flotante */
#helpdesk .msg.ai .bubble,
#helpdesk .msg.agent .bubble,
#helpdesk .msg.system .bubble {
  background:
    linear-gradient(135deg, rgba(255,255,255,.92), rgba(240,248,255,.78)) !important;
  color: #263244 !important;
}

/* Mensajes del usuario: vidrio azul flotante */
#helpdesk .msg.me .bubble {
  background:
    radial-gradient(circle at 16% 12%, rgba(255,255,255,.38), transparent 28%),
    linear-gradient(135deg, rgba(219,239,255,.94), rgba(191,226,255,.82)) !important;
  color: #1f2d3d !important;
  border-color: rgba(98,199,255,.36) !important;
  box-shadow:
    0 18px 45px rgba(21,155,255,.16),
    0 6px 18px rgba(37,99,235,.12),
    inset 0 1px 0 rgba(255,255,255,.70) !important;
}

/* Hover: se elevan como tarjetas flotantes */
#helpdesk .msg .bubble:hover {
  transform: translateY(-3px) !important;
  border-color: rgba(98,199,255,.46) !important;
  box-shadow:
    0 24px 58px rgba(15,23,42,.18),
    0 10px 26px rgba(21,155,255,.18),
    inset 0 1px 0 rgba(255,255,255,.70) !important;
}

/* Meta m�s limpio */
#helpdesk .msg .bubble .meta {
  position: relative !important;
  z-index: 2 !important;
  opacity: .72 !important;
  font-weight: 800 !important;
}

/* Texto encima del brillo */
#helpdesk .msg .bubble > * {
  position: relative !important;
  z-index: 2 !important;
}

/* Peque�a sombra bajo cada burbuja */
#helpdesk .msg {
  filter: drop-shadow(0 12px 16px rgba(15,23,42,.06)) !important;
}
</style>


<style>
/* CHAT NUEVO: contraste blanco/glass */
#helpdesk #newTicketForm,
#helpdesk form.new {
  background:
    radial-gradient(circle at 12% 8%, rgba(98,199,255,.18), transparent 30%),
    linear-gradient(135deg, rgba(255,255,255,.96), rgba(240,248,255,.90)) !important;
  border: 1px solid rgba(98,199,255,.28) !important;
  box-shadow:
    0 22px 60px rgba(15,23,42,.14),
    0 8px 22px rgba(21,155,255,.10),
    inset 0 1px 0 rgba(255,255,255,.90) !important;
  backdrop-filter: blur(16px) saturate(140%) !important;
  -webkit-backdrop-filter: blur(16px) saturate(140%) !important;
  color: #1f2d3d !important;
}

/* Textos dentro del inicio de chat */
#helpdesk #newTicketForm label,
#helpdesk form.new label {
  color: #344256 !important;
}

#helpdesk #newTicketForm span,
#helpdesk form.new span {
  color: #64748b !important;
}

/* Badge ASISTENTE INTELIGENTE con contraste */
#helpdesk #newTicketForm .tag,
#helpdesk form.new .tag,
#helpdesk .new .tag {
  background: rgba(21,155,255,.10) !important;
  color: #0758b8 !important;
  border: 1px solid rgba(21,155,255,.22) !important;
}

/* Textarea de inicio claro pero premium */
#helpdesk #newTicketForm textarea[name="message"],
#helpdesk form.new textarea[name="message"] {
  background:
    linear-gradient(135deg, rgba(255,255,255,.98), rgba(246,251,255,.94)) padding-box,
    conic-gradient(
      from var(--jureto-angle, 0deg),
      #0a7dda,
      #62c7ff,
      #ffffff,
      #159bff,
      #0a7dda
    ) border-box !important;
  border: 1.5px solid transparent !important;
  color: #263244 !important;
  box-shadow:
    inset 0 1px 0 rgba(255,255,255,.90),
    0 12px 34px rgba(15,23,42,.08) !important;
}

#helpdesk #newTicketForm textarea[name="message"]::placeholder,
#helpdesk form.new textarea[name="message"]::placeholder {
  color: #64748b !important;
}

/* Inputs del formulario nuevo */
#helpdesk #newTicketForm input,
#helpdesk #newTicketForm select,
#helpdesk form.new input,
#helpdesk form.new select {
  background: rgba(255,255,255,.95) !important;
  color: #263244 !important;
  border: 1px solid rgba(37,99,235,.18) !important;
  box-shadow: 0 10px 24px rgba(15,23,42,.06) !important;
}

/* Al enfocar, l�nea luminosa suave */
#helpdesk #newTicketForm textarea[name="message"]:focus,
#helpdesk form.new textarea[name="message"]:focus {
  animation: juretoComposerRotateLine 1.5s linear infinite !important;
  box-shadow:
    0 0 0 3px rgba(98,199,255,.16),
    0 0 28px rgba(21,155,255,.24),
    inset 0 1px 0 rgba(255,255,255,.90) !important;
}
</style>


<style>
/* FIX: botones borrar chats - dejar siempre estilo hover */
#helpdesk .saved-chat-row button,
#helpdesk .saved-chat-row .delete-chat,
#helpdesk .saved-chat-row .delete-chat-btn,
#helpdesk .saved-chat-row form button,
#helpdesk .saved-chat-row [type="submit"] {
  width: 34px !important;
  height: 34px !important;
  min-width: 34px !important;
  min-height: 34px !important;
  padding: 0 !important;

  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;

  border-radius: 12px !important;
  border: 1px solid rgba(239,68,68,.35) !important;
  background: linear-gradient(135deg, rgba(255,255,255,.96), rgba(255,235,235,.92)) !important;
  color: #ef4444 !important;

  font-size: 16px !important;
  font-weight: 950 !important;
  line-height: 1 !important;

  box-shadow:
    0 8px 20px rgba(239,68,68,.16),
    inset 0 1px 0 rgba(255,255,255,.85) !important;

  animation: none !important;
  transform: none !important;
  filter: none !important;
}

/* Que al pasar el cursor no cambie a otra forma rara */
#helpdesk .saved-chat-row button:hover,
#helpdesk .saved-chat-row .delete-chat:hover,
#helpdesk .saved-chat-row .delete-chat-btn:hover,
#helpdesk .saved-chat-row form button:hover,
#helpdesk .saved-chat-row [type="submit"]:hover {
  width: 34px !important;
  height: 34px !important;
  border-radius: 12px !important;
  background: linear-gradient(135deg, rgba(255,255,255,.96), rgba(255,235,235,.92)) !important;
  color: #ef4444 !important;
  box-shadow:
    0 8px 20px rgba(239,68,68,.16),
    inset 0 1px 0 rgba(255,255,255,.85) !important;
  transform: none !important;
  filter: none !important;
}

/* Evitar que hereden el borde animado de botones generales */
#helpdesk .saved-chat-row button::before,
#helpdesk .saved-chat-row button::after {
  display: none !important;
  content: none !important;
}
</style>


<style>
/* FIX FINAL: borrar chat solo con X y movimiento */
#helpdesk .saved-chat-row button,
#helpdesk .saved-chat-row form button,
#helpdesk .saved-chat-row [type="submit"] {
  width: 26px !important;
  height: 26px !important;
  min-width: 26px !important;
  min-height: 26px !important;
  padding: 0 !important;
  margin: 0 !important;

  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;

  background: transparent !important;
  background-image: none !important;
  border: 0 !important;
  box-shadow: none !important;
  outline: none !important;

  color: #ef4444 !important;
  font-size: 18px !important;
  font-weight: 950 !important;
  line-height: 1 !important;

  animation: juretoXFloat 1.8s ease-in-out infinite !important;
  transform-origin: center !important;
  filter: drop-shadow(0 0 5px rgba(239,68,68,.28)) !important;
}

/* Sin caja ni antes/despu�s */
#helpdesk .saved-chat-row button::before,
#helpdesk .saved-chat-row button::after,
#helpdesk .saved-chat-row form button::before,
#helpdesk .saved-chat-row form button::after {
  display: none !important;
  content: none !important;
  background: none !important;
  box-shadow: none !important;
}

/* Hover: solo la X se agranda un poco */
#helpdesk .saved-chat-row button:hover,
#helpdesk .saved-chat-row form button:hover,
#helpdesk .saved-chat-row [type="submit"]:hover {
  background: transparent !important;
  border: 0 !important;
  box-shadow: none !important;
  color: #dc2626 !important;
  transform: scale(1.22) rotate(8deg) !important;
  filter: drop-shadow(0 0 9px rgba(239,68,68,.55)) !important;
}

/* Movimiento sutil de la X */
@keyframes juretoXFloat {
  0%, 100% {
    transform: translateY(0) rotate(0deg);
  }
  50% {
    transform: translateY(-1px) rotate(4deg);
  }
}
</style>


<style>
/* X de borrar chats: texto puro, sin cuadro */
#helpdesk .saved-chat-row .jureto-delete-x-only {
  all: unset !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;

  width: 22px !important;
  height: 22px !important;
  cursor: pointer !important;

  color: #ef4444 !important;
  font-size: 20px !important;
  font-weight: 950 !important;
  line-height: 1 !important;
  text-align: center !important;

  background: transparent !important;
  border: 0 !important;
  box-shadow: none !important;
  outline: none !important;
  border-radius: 0 !important;

  animation: juretoXOnlyMove 1.6s ease-in-out infinite !important;
  filter: drop-shadow(0 0 4px rgba(239,68,68,.35)) !important;
}

/* matar cualquier fondo/cuadro heredado */
#helpdesk .saved-chat-row .jureto-delete-x-only::before,
#helpdesk .saved-chat-row .jureto-delete-x-only::after {
  display: none !important;
  content: none !important;
}

#helpdesk .saved-chat-row .jureto-delete-x-only:hover {
  color: #dc2626 !important;
  transform: scale(1.22) rotate(8deg) !important;
  filter: drop-shadow(0 0 9px rgba(239,68,68,.65)) !important;
}

@keyframes juretoXOnlyMove {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  50% { transform: translateY(-1px) rotate(5deg); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  function cleanDeleteButtons() {
    document.querySelectorAll('#helpdesk .saved-chat-row button').forEach(function (btn) {
      const text = (btn.textContent || '').trim();

      if (text === '�' || text === 'x' || text === 'X' || text.includes('�')) {
        btn.classList.add('jureto-delete-x-only');
        btn.textContent = '�';

        btn.removeAttribute('style');
      }
    });
  }

  cleanDeleteButtons();
  setTimeout(cleanDeleteButtons, 500);
  setTimeout(cleanDeleteButtons, 1500);
});
</script>


<style>
/* SOLO X DE BORRAR CHAT - sin cuadro */
#helpdesk .saved-chat-delete-form {
  background: transparent !important;
  background-image: none !important;
  border: 0 !important;
  box-shadow: none !important;
  backdrop-filter: none !important;
  padding: 0 !important;
  margin: 0 !important;
}

#helpdesk .saved-chat-delete-form .saved-chat-delete {
  all: unset !important;

  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;

  width: 24px !important;
  height: 24px !important;
  min-width: 24px !important;
  min-height: 24px !important;

  background: transparent !important;
  background-color: transparent !important;
  background-image: none !important;
  border: 0 !important;
  box-shadow: none !important;
  outline: none !important;
  border-radius: 0 !important;

  color: #ef4444 !important;
  font-size: 22px !important;
  font-weight: 950 !important;
  line-height: 1 !important;
  cursor: pointer !important;

  animation: juretoSoloXMove 1.5s ease-in-out infinite !important;
  filter: drop-shadow(0 0 6px rgba(239,68,68,.45)) !important;
}

#helpdesk .saved-chat-delete-form .saved-chat-delete::before,
#helpdesk .saved-chat-delete-form .saved-chat-delete::after {
  display: none !important;
  content: none !important;
}

#helpdesk .saved-chat-delete-form .saved-chat-delete:hover {
  background: transparent !important;
  border: 0 !important;
  box-shadow: none !important;
  color: #dc2626 !important;
  transform: scale(1.25) rotate(8deg) !important;
  filter: drop-shadow(0 0 11px rgba(239,68,68,.75)) !important;
}

@keyframes juretoSoloXMove {
  0%, 100% {
    transform: translateY(0) rotate(0deg);
  }
  50% {
    transform: translateY(-1px) rotate(5deg);
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('#helpdesk .saved-chat-delete-form .saved-chat-delete').forEach(function (btn) {
    btn.innerHTML = '&times;';
  });
});
</script>


<style>
/* JURETO: fondo seguro con movimiento m�s visible */
#helpdesk .jureto-safe-fluid-bg::before {
  inset: -35% !important;
  opacity: .95 !important;
  filter: blur(18px) saturate(150%) !important;

  background:
    radial-gradient(circle at 15% 20%, rgba(21,155,255,.52), transparent 24%),
    radial-gradient(circle at 82% 18%, rgba(98,199,255,.38), transparent 28%),
    radial-gradient(circle at 42% 80%, rgba(14,165,233,.42), transparent 30%),
    radial-gradient(circle at 72% 72%, rgba(255,255,255,.80), transparent 28%),
    linear-gradient(135deg, #e9f7ff 0%, #f8fbff 42%, #e6f3ff 100%) !important;

  animation: juretoSafeFluidMoveVisible 8s ease-in-out infinite alternate !important;
  will-change: transform !important;
}

#helpdesk .jureto-safe-fluid-bg::after {
  opacity: .62 !important;
  background-size: 22px 22px, 28px 28px, 26px 26px !important;
  animation: juretoSafeTextureMoveVisible 6s linear infinite !important;
  will-change: background-position !important;
}

/* Movimiento m�s amplio y notorio */
@keyframes juretoSafeFluidMoveVisible {
  0% {
    transform: translate3d(-8%, -5%, 0) scale(1.04) rotate(0deg);
  }

  25% {
    transform: translate3d(6%, 4%, 0) scale(1.12) rotate(4deg);
  }

  50% {
    transform: translate3d(-4%, 8%, 0) scale(1.07) rotate(-3deg);
  }

  75% {
    transform: translate3d(8%, -3%, 0) scale(1.13) rotate(3deg);
  }

  100% {
    transform: translate3d(-5%, -8%, 0) scale(1.08) rotate(-2deg);
  }
}

@keyframes juretoSafeTextureMoveVisible {
  0% {
    background-position: 0 0, 0 0, 0 0;
  }

  100% {
    background-position: 160px 95px, -140px 150px, 190px 190px;
  }
}
</style>


<style>
/* JURETO: fondo con tono m�s fuerte para notar mejor el movimiento */
#helpdesk .jureto-safe-fluid-bg::before {
  opacity: 1 !important;
  filter: blur(16px) saturate(185%) contrast(1.08) !important;

  background:
    radial-gradient(circle at 15% 20%, rgba(0,132,255,.68), transparent 24%),
    radial-gradient(circle at 82% 18%, rgba(56,189,248,.58), transparent 28%),
    radial-gradient(circle at 42% 80%, rgba(14,165,233,.56), transparent 30%),
    radial-gradient(circle at 72% 72%, rgba(255,255,255,.82), transparent 28%),
    linear-gradient(135deg, #dff3ff 0%, #f8fbff 42%, #dcefff 100%) !important;
}

#helpdesk .jureto-safe-fluid-bg::after {
  opacity: .78 !important;

  background-image:
    radial-gradient(circle at 25% 20%, rgba(255,255,255,.62) 0 1px, transparent 1.4px),
    radial-gradient(circle at 75% 65%, rgba(0,132,255,.20) 0 1px, transparent 1.5px),
    repeating-linear-gradient(
      135deg,
      rgba(255,255,255,.26) 0px,
      rgba(255,255,255,.26) 1px,
      rgba(0,132,255,.055) 1px,
      rgba(0,132,255,.055) 18px
    ) !important;
}
</style>


<style>
/* TEST VISIBLE: confirmar que la vista s� est� actualizando */
#helpdesk {
  outline: 6px solid #159bff !important;
  box-shadow: inset 0 0 120px rgba(0,132,255,.35) !important;
}
</style>


<style>
/* Quitar prueba azul */
#helpdesk {
  outline: none !important;
  box-shadow: none !important;
}

/* Fondo fluido visible y seguro */
#helpdesk {
  position: relative !important;
  isolation: isolate !important;
  overflow: hidden !important;
  background:
    radial-gradient(circle at 18% 18%, rgba(0,132,255,.38), transparent 28%),
    radial-gradient(circle at 82% 20%, rgba(56,189,248,.32), transparent 30%),
    radial-gradient(circle at 48% 82%, rgba(14,165,233,.30), transparent 34%),
    linear-gradient(135deg, #dff3ff 0%, #f8fbff 45%, #dcefff 100%) !important;
}

/* Capa de fluido encima del fondo, pero debajo del contenido */
#helpdesk::before {
  content: "" !important;
  position: absolute !important;
  inset: -30% !important;
  z-index: 0 !important;
  pointer-events: none !important;

  background:
    radial-gradient(circle at 16% 22%, rgba(0,132,255,.62), transparent 24%),
    radial-gradient(circle at 78% 26%, rgba(98,199,255,.56), transparent 28%),
    radial-gradient(circle at 44% 78%, rgba(14,165,233,.50), transparent 32%),
    radial-gradient(circle at 76% 74%, rgba(255,255,255,.72), transparent 28%) !important;

  filter: blur(18px) saturate(170%) !important;
  opacity: .78 !important;
  animation: juretoFondoFluidoVisible 8s ease-in-out infinite alternate !important;
}

/* Textura sutil */
#helpdesk::after {
  content: "" !important;
  position: absolute !important;
  inset: 0 !important;
  z-index: 0 !important;
  pointer-events: none !important;

  background-image:
    radial-gradient(circle at 25% 20%, rgba(255,255,255,.55) 0 1px, transparent 1.4px),
    radial-gradient(circle at 75% 65%, rgba(0,132,255,.16) 0 1px, transparent 1.5px),
    repeating-linear-gradient(
      135deg,
      rgba(255,255,255,.18) 0px,
      rgba(255,255,255,.18) 1px,
      rgba(0,132,255,.045) 1px,
      rgba(0,132,255,.045) 18px
    ) !important;

  background-size: 24px 24px, 30px 30px, 28px 28px !important;
  opacity: .55 !important;
  animation: juretoFondoTexturaVisible 7s linear infinite !important;
}

/* Todo el contenido arriba del fondo */
#helpdesk > * {
  position: relative !important;
  z-index: 2 !important;
}

@keyframes juretoFondoFluidoVisible {
  0% {
    transform: translate3d(-8%, -5%, 0) scale(1.04) rotate(0deg);
  }
  25% {
    transform: translate3d(6%, 4%, 0) scale(1.12) rotate(4deg);
  }
  50% {
    transform: translate3d(-4%, 8%, 0) scale(1.07) rotate(-3deg);
  }
  75% {
    transform: translate3d(8%, -3%, 0) scale(1.13) rotate(3deg);
  }
  100% {
    transform: translate3d(-5%, -8%, 0) scale(1.08) rotate(-2deg);
  }
}

@keyframes juretoFondoTexturaVisible {
  0% {
    background-position: 0 0, 0 0, 0 0;
  }
  100% {
    background-position: 160px 95px, -140px 150px, 190px 190px;
  }
}
</style>


<style>
/* JURETO: fondo fluido azul pastel */
#helpdesk {
  background:
    radial-gradient(circle at 18% 18%, rgba(147,197,253,.24), transparent 30%),
    radial-gradient(circle at 82% 20%, rgba(186,230,253,.22), transparent 32%),
    radial-gradient(circle at 48% 82%, rgba(125,211,252,.20), transparent 36%),
    linear-gradient(135deg, #edf8ff 0%, #fbfdff 48%, #eaf6ff 100%) !important;
}

#helpdesk::before {
  background:
    radial-gradient(circle at 16% 22%, rgba(147,197,253,.42), transparent 25%),
    radial-gradient(circle at 78% 26%, rgba(186,230,253,.40), transparent 30%),
    radial-gradient(circle at 44% 78%, rgba(125,211,252,.34), transparent 34%),
    radial-gradient(circle at 76% 74%, rgba(255,255,255,.76), transparent 30%) !important;

  opacity: .62 !important;
  filter: blur(22px) saturate(120%) !important;
}

#helpdesk::after {
  opacity: .38 !important;

  background-image:
    radial-gradient(circle at 25% 20%, rgba(255,255,255,.48) 0 1px, transparent 1.4px),
    radial-gradient(circle at 75% 65%, rgba(147,197,253,.12) 0 1px, transparent 1.5px),
    repeating-linear-gradient(
      135deg,
      rgba(255,255,255,.16) 0px,
      rgba(255,255,255,.16) 1px,
      rgba(147,197,253,.030) 1px,
      rgba(147,197,253,.030) 18px
    ) !important;
}
</style>


<style>
/* JURETO: fondo azul pastel un poco m�s alto */
#helpdesk {
  background:
    radial-gradient(circle at 18% 18%, rgba(96,165,250,.30), transparent 30%),
    radial-gradient(circle at 82% 20%, rgba(125,211,252,.28), transparent 32%),
    radial-gradient(circle at 48% 82%, rgba(56,189,248,.25), transparent 36%),
    linear-gradient(135deg, #e6f5ff 0%, #fbfdff 48%, #e1f2ff 100%) !important;
}

#helpdesk::before {
  background:
    radial-gradient(circle at 16% 22%, rgba(96,165,250,.50), transparent 25%),
    radial-gradient(circle at 78% 26%, rgba(125,211,252,.48), transparent 30%),
    radial-gradient(circle at 44% 78%, rgba(56,189,248,.42), transparent 34%),
    radial-gradient(circle at 76% 74%, rgba(255,255,255,.74), transparent 30%) !important;

  opacity: .70 !important;
  filter: blur(20px) saturate(135%) !important;
}

#helpdesk::after {
  opacity: .45 !important;

  background-image:
    radial-gradient(circle at 25% 20%, rgba(255,255,255,.50) 0 1px, transparent 1.4px),
    radial-gradient(circle at 75% 65%, rgba(96,165,250,.16) 0 1px, transparent 1.5px),
    repeating-linear-gradient(
      135deg,
      rgba(255,255,255,.18) 0px,
      rgba(255,255,255,.18) 1px,
      rgba(96,165,250,.040) 1px,
      rgba(96,165,250,.040) 18px
    ) !important;
}
</style>


<style>
/* =====================================================
   JURETO BALANCE PREMIUM FINAL
   Fondo suave + botones vivos + tarjetas claras
===================================================== */

/* 1) Fondo suave, azul pastel con movimiento visible pero no invasivo */
#helpdesk {
  background:
    radial-gradient(circle at 18% 18%, rgba(96,165,250,.22), transparent 32%),
    radial-gradient(circle at 82% 20%, rgba(125,211,252,.20), transparent 34%),
    radial-gradient(circle at 48% 82%, rgba(56,189,248,.18), transparent 38%),
    linear-gradient(135deg, #eef8ff 0%, #fbfdff 48%, #eaf6ff 100%) !important;
}

#helpdesk::before {
  background:
    radial-gradient(circle at 16% 22%, rgba(96,165,250,.38), transparent 26%),
    radial-gradient(circle at 78% 26%, rgba(125,211,252,.34), transparent 31%),
    radial-gradient(circle at 44% 78%, rgba(56,189,248,.30), transparent 35%),
    radial-gradient(circle at 76% 74%, rgba(255,255,255,.78), transparent 31%) !important;

  opacity: .58 !important;
  filter: blur(22px) saturate(120%) !important;
  animation: juretoFondoFluidoVisible 10s ease-in-out infinite alternate !important;
}

#helpdesk::after {
  opacity: .34 !important;
}

/* 2) Tarjetas claras/glass para que contrasten con el fondo */
#helpdesk .new,
#helpdesk #newTicketForm,
#helpdesk aside,
#helpdesk .chat,
#helpdesk .composer,
#helpdesk .saved-chats-list,
#helpdesk .saved-empty {
  background:
    radial-gradient(circle at 12% 8%, rgba(98,199,255,.12), transparent 32%),
    linear-gradient(135deg, rgba(255,255,255,.94), rgba(242,248,255,.86)) !important;
  border: 1px solid rgba(98,199,255,.20) !important;
  box-shadow:
    0 22px 58px rgba(15,23,42,.11),
    0 8px 22px rgba(21,155,255,.07),
    inset 0 1px 0 rgba(255,255,255,.80) !important;
  backdrop-filter: blur(14px) saturate(135%) !important;
  -webkit-backdrop-filter: blur(14px) saturate(135%) !important;
}

/* 3) Bot�n principal m�s vivo */
#helpdesk #sendBtn,
#helpdesk #newTicketForm button[type="submit"] {
  background:
    linear-gradient(135deg, #0758b8, #159bff) padding-box,
    linear-gradient(135deg, rgba(98,199,255,.90), rgba(255,255,255,.65), rgba(21,155,255,.90)) border-box !important;
  color: #ffffff !important;
  border: 1.5px solid transparent !important;
  box-shadow:
    0 16px 34px rgba(21,155,255,.28),
    inset 0 1px 0 rgba(255,255,255,.20) !important;
  text-shadow: 0 1px 0 rgba(0,0,0,.14) !important;
}

/* 4) Bot�n secundario claro y limpio */
#helpdesk #escalarBtn,
#helpdesk .btn-ghost {
  background:
    linear-gradient(135deg, rgba(255,255,255,.98), rgba(242,248,255,.94)) padding-box,
    linear-gradient(135deg, rgba(98,199,255,.52), rgba(255,255,255,.70), rgba(37,99,235,.35)) border-box !important;
  color: #2563eb !important;
  border: 1.5px solid transparent !important;
  box-shadow:
    0 12px 28px rgba(15,23,42,.08),
    inset 0 1px 0 rgba(255,255,255,.90) !important;
}

/* 5) Bot�n agrandar caja claro, sin deformarse */
#helpdesk #expandComposerBtn,
#helpdesk .mini-expand {
  background:
    linear-gradient(135deg, rgba(255,255,255,.98), rgba(242,248,255,.94)) padding-box,
    linear-gradient(135deg, rgba(98,199,255,.50), rgba(255,255,255,.70), rgba(37,99,235,.30)) border-box !important;
  color: #2563eb !important;
  border: 1.5px solid transparent !important;
  box-shadow:
    0 10px 22px rgba(15,23,42,.08),
    inset 0 1px 0 rgba(255,255,255,.90) !important;
}

/* 6) Movimiento hover premium pero controlado */
#helpdesk #sendBtn:hover,
#helpdesk #newTicketForm button[type="submit"]:hover,
#helpdesk #escalarBtn:hover,
#helpdesk #expandComposerBtn:hover,
#helpdesk .mini-expand:hover {
  transform: translateY(-1px) scale(1.015) !important;
  filter: brightness(1.04) !important;
  box-shadow:
    0 0 0 2px rgba(98,199,255,.14),
    0 0 26px rgba(21,155,255,.26),
    0 16px 34px rgba(15,23,42,.10) !important;
}

/* 7) Chats pasados claros, pero activo con color vivo */
#helpdesk .saved-chat-row .saved-chat {
  background:
    linear-gradient(135deg, rgba(255,255,255,.92), rgba(240,248,255,.82)) !important;
  color: #263244 !important;
  border: 1px solid rgba(98,199,255,.18) !important;
  box-shadow: 0 10px 22px rgba(15,23,42,.06) !important;
}

#helpdesk .saved-chat-row.active .saved-chat {
  background:
    radial-gradient(circle at 12% 20%, rgba(98,199,255,.25), transparent 34%),
    linear-gradient(135deg, #0758b8, #159bff) !important;
  color: #ffffff !important;
  border-color: transparent !important;
  box-shadow:
    0 14px 30px rgba(21,155,255,.28),
    inset 0 1px 0 rgba(255,255,255,.18) !important;
}

#helpdesk .saved-chat-row.active .saved-chat-meta {
  color: rgba(255,255,255,.78) !important;
}

/* 8) Burbujas flotantes claras */
#helpdesk .msg .bubble {
  background:
    linear-gradient(135deg, rgba(255,255,255,.92), rgba(240,248,255,.80)) !important;
  border: 1px solid rgba(98,199,255,.18) !important;
  box-shadow:
    0 18px 42px rgba(15,23,42,.11),
    0 6px 18px rgba(21,155,255,.06),
    inset 0 1px 0 rgba(255,255,255,.68) !important;
}

#helpdesk .msg.me .bubble {
  background:
    radial-gradient(circle at 16% 12%, rgba(255,255,255,.38), transparent 28%),
    linear-gradient(135deg, rgba(219,239,255,.94), rgba(191,226,255,.82)) !important;
  color: #1f2d3d !important;
  border-color: rgba(98,199,255,.32) !important;
}
</style>

