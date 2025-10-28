@extends('layouts.web')
@section('title','Centro de Ayuda')

@section('content')
<style>
/* ==================== NAMESPACE AISLADO ==================== */
#helpdesk{
  --bg:#f6f8fc; --surface:#ffffff; --line:#e8eef6; --ink:#0e1726; --muted:#6b7280;
  --brand:#a3d5ff; --brand-ink:#0b1220; --ok:#16a34a; --warn:#f59e0b; --bad:#ef4444;
  --radius:18px; --shadow:0 16px 40px rgba(2,8,23,.08);
}
#helpdesk .wrap{max-width:980px;margin:clamp(62px,6vw,96px) auto 56px; padding:0 16px}
#helpdesk .card{background:var(--surface); border:1px solid var(--line); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden}
#helpdesk .head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 20px;border-bottom:1px solid var(--line)}
#helpdesk .h1{font-size:clamp(18px,2.2vw,22px);font-weight:800;color:var(--ink);margin:0}
#helpdesk .status{padding:6px 10px;border:1px solid var(--line);border-radius:999px;font-size:12px;color:var(--muted)}
#helpdesk .body{display:grid;grid-template-columns:1fr; gap:0}
#helpdesk .new{padding:18px 18px;border-bottom:1px solid var(--line);background:linear-gradient(180deg,#fff, #fbfdff)}
#helpdesk .grid{display:grid; grid-template-columns:1fr 1fr; gap:12px}
#helpdesk input[type=text], #helpdesk select, #helpdesk textarea{
  width:100%; padding:12px 14px; border:1px solid var(--line); border-radius:14px; font-size:14px; color:var(--ink); background:#fff;
}
#helpdesk textarea{min-height:110px; resize:vertical}
#helpdesk .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 16px;border-radius:14px;border:1px solid var(--line); background:var(--brand); color:var(--brand-ink); font-weight:700; cursor:pointer; transition:.2s}
#helpdesk .btn:hover{background:#fff;color:#000; box-shadow:0 10px 24px rgba(2,8,23,.10)}
#helpdesk .btn-ghost{background:#fff;color:var(--ink)}
#helpdesk .btn-danger{background:#ffe4e6;color:#7f1d1d;border-color:#ffd5da}
#helpdesk .chat{padding:18px; max-height:56vh; overflow:auto; background:repeating-linear-gradient( -45deg, #fafcff, #fafcff 18px, #f7fbff 18px, #f7fbff 36px);}
#helpdesk .msg{display:flex; gap:10px; margin:10px 0}
#helpdesk .bubble{max-width:82%; padding:12px 14px; border-radius:16px; border:1px solid var(--line); background:#fff; color:var(--ink); box-shadow:0 10px 24px rgba(2,8,23,.06)}
#helpdesk .me   {justify-content:flex-end}
#helpdesk .me .bubble{background:#eef6ff}
#helpdesk .ai .bubble{background:#f3f7ff}
#helpdesk .agent .bubble{background:#eaffe7}
#helpdesk .system .bubble{background:#fff8db}
#helpdesk .meta{font-size:11px;color:var(--muted); margin-top:6px}
#helpdesk .composer{display:flex;gap:10px; border-top:1px solid var(--line); padding:12px; background:#fff}
#helpdesk .composer textarea{flex:1}

/* Typing bubble */
#helpdesk .typing .bubble{display:flex;align-items:center;gap:6px}
#helpdesk .dot{width:6px;height:6px;border-radius:50%;background:#9aa7b1;display:inline-block;animation:blink 1s infinite ease-in-out}
#helpdesk .dot:nth-child(2){animation-delay:.15s}
#helpdesk .dot:nth-child(3){animation-delay:.3s}
@keyframes blink{0%,80%,100%{opacity:.2}40%{opacity:1}}

@media (max-width:800px){
  #helpdesk .grid{grid-template-columns:1fr}
  #helpdesk .chat{max-height:52vh}
}
</style>

<div id="helpdesk" data-user-name="{{ auth()->user()->name ?? 'Tú' }}">
  <div class="wrap">
    <div class="card">
      <div class="head">
        <h1 class="h1">Centro de Ayuda</h1>
        <span class="status" id="statusChip">
          @if(isset($ticket)) Estatus: {{ strtoupper($ticket->status) }} @else Nuevo @endif
        </span>
      </div>

      <div class="body">
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
            <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap">
              <button class="btn" type="submit">Preguntar a la IA</button>
              <span style="font-size:12px;color:var(--muted)">La IA responde primero; si no resuelve, podrás escalar a un agente.</span>
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
    tBase: "{{ url('/ayuda/t') }}", // usa: `${routes.tBase}/${id}/message` y `/escalar`
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
    if (chatbox)           chatbox.dataset.ticketId = id;
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
      if (!ticketId()) return; // no hay ticket aún
      disableComposer(true);

      // Añade tu mensaje en UI
      chatbox.appendChild(bubble('user', text, timeNow(), false));
      scrollBottom();
      input.value = '';

      // Añade typing bubble
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
      // feedback visual simple
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

        // Oculta formulario, muestra chat y composer
        newForm.style.display = 'none';
        setTicketId(data.ticket.id);
        setStatus('Estatus: ' + (data.ticket.status||'').toUpperCase());

        // Renderiza mensajes iniciales (usuario + IA)
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
        if (btn) { btn.disabled = false; btn.textContent = 'Preguntar a la IA'; }
      }
    });
  }

  // Auto-scroll on load
  scrollBottom();

})();
</script>
@endsection
