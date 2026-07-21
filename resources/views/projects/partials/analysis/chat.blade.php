{{-- ============================================================
     resources/views/projects/partials/analysis/chat.blade.php
     COLUMNA IZQUIERDA: CHAT (sam)
     Misma estructura del diseño original — solo pulido y animado.
     Se conservan clases e ids:
       pjd-msg · is-user · is-assistant · pjd-msg-avatar · pjd-msg-meta · pjd-msg-body
       pjdChatReset · pjdChatList · pjdChatForm · pjdChatInput · pjdChatSend
     ============================================================ --}}

<div class="pjd-left">

  <div class="pjd-chat-head">
    <button type="button" class="pjd-chat-reset" id="pjdChatReset" title="Reiniciar chat">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>
      </svg>
      Reiniciar
    </button>
  </div>

  <div class="pjd-chat-list" id="pjdChatList">
    @forelse($project->chatMessages as $i => $m)
      <div class="pjd-msg {{ $m->role === 'user' ? 'is-user' : 'is-assistant' }}"
           style="animation-delay:{{ min($i * 55, 400) }}ms">
        @if($m->role === 'assistant')
          <div class="pjd-msg-avatar">j</div>
          <div class="pjd-msg-col">
            <div class="pjd-msg-meta">sam <span>·</span> {{ $m->created_at->format('H:i') }}</div>
            <div class="pjd-msg-body" data-raw="{{ $m->content }}">{!! nl2br(e($m->content)) !!}</div>
          </div>
        @else
          <div class="pjd-msg-body">{{ $m->content }}</div>
        @endif
      </div>
    @empty
      <div class="pjd-msg is-assistant">
        <div class="pjd-msg-avatar">j</div>
        <div class="pjd-msg-col">
          <div class="pjd-msg-meta">sam</div>
          <div class="pjd-msg-body">Hola, soy tu asistente del proyecto. Puedes pedirme un resumen de las bases, los requisitos clave, las fechas importantes o cualquier duda sobre la licitación.</div>
        </div>
      </div>
    @endforelse
  </div>

  <button type="button" class="pjd-chat-jump" id="pjdChatJump" aria-label="Ir al último mensaje">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 5v14M19 12l-7 7-7-7"/>
    </svg>
  </button>

  {{-- AJUSTA data-endpoint a tu ruta real de chat --}}
  <form class="pjd-chat-input" id="pjdChatForm" autocomplete="off"
        data-endpoint="{{ url('/projects/'.$project->id.'/chat') }}"
        data-project-id="{{ $project->id }}">
    @csrf
    <textarea name="message" id="pjdChatInput" rows="1" placeholder="Pregunta a sam…"></textarea>
    <button type="submit" class="pjd-chat-send" id="pjdChatSend" aria-label="Enviar" disabled>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M2 21l21-9L2 3v7l15 2-15 2z"/></svg>
    </button>
  </form>

</div>


@verbatim
<style id="pjdChatStyles">
/* ============================================================
   Chat — mismo layout, mejor ejecución
   ============================================================ */
.pjd-left{
  --pjdc-tinta:#111418;
  --pjdc-tinta-60:#5B6470;
  --pjdc-tinta-35:#98A0AC;
  --pjdc-papel:#FFFFFF;
  --pjdc-burbuja:#F1F2F4;
  --pjdc-niebla:#F5F6F8;
  --pjdc-borde:#E8EAEE;
  --pjdc-azul:#007aff;
  --pjdc-azul-oscuro:#0062cc;
  --pjdc-ease:cubic-bezier(.22,.61,.36,1);
  --pjdc-out:cubic-bezier(.16,1,.3,1);
  --pjdc-s1:0 1px 2px rgba(17,20,24,.05);
  --pjdc-s2:0 6px 20px -8px rgba(17,20,24,.16),0 1px 2px rgba(17,20,24,.05);
  --pjdc-s3:0 14px 34px -14px rgba(17,20,24,.3);

  position:relative !important;
  display:flex !important;
  flex-direction:column !important;
  min-width:0 !important;
  min-height:0 !important;
  background:var(--pjdc-papel) !important;
  color:var(--pjdc-tinta) !important;
  font-family:'Quicksand',system-ui,-apple-system,sans-serif !important;
}
.pjd-left *,.pjd-left *::before,.pjd-left *::after{box-sizing:border-box}
.pjd-left :focus-visible{outline:2px solid var(--pjdc-azul) !important;outline-offset:2px;border-radius:10px}
.pjd-left .pjd-chat-input textarea:focus-visible{outline:0 !important}

/* ---------- Reiniciar: pastilla flotante ---------- */
.pjd-left .pjd-chat-head{
  position:absolute !important;
  top:12px;right:16px;
  height:auto !important;
  padding:0 !important;
  border:0 !important;
  background:none !important;
  z-index:6;
}
.pjd-left .pjd-chat-reset{
  display:inline-flex !important;align-items:center;gap:7px;
  padding:9px 15px !important;
  border:1px solid var(--pjdc-borde) !important;
  border-radius:999px !important;
  background:rgba(255,255,255,.92) !important;
  backdrop-filter:blur(12px);
  -webkit-backdrop-filter:blur(12px);
  color:var(--pjdc-tinta) !important;
  font:inherit;font-size:13px;font-weight:600;
  cursor:pointer;
  box-shadow:var(--pjdc-s2);
  transition:transform .24s var(--pjdc-out),box-shadow .24s var(--pjdc-out),border-color .18s;
}
.pjd-left .pjd-chat-reset:hover{
  transform:translateY(-1px);
  box-shadow:var(--pjdc-s3);
  border-color:#DADDE3 !important;
}
.pjd-left .pjd-chat-reset:active{transform:translateY(0) scale(.98)}
.pjd-left .pjd-chat-reset svg{transition:transform .55s var(--pjdc-out)}
.pjd-left .pjd-chat-reset:hover svg{transform:rotate(-180deg)}

/* ---------- Lista ---------- */
.pjd-left .pjd-chat-list{
  flex:1 1 auto !important;
  min-height:0 !important;
  overflow-y:auto !important;
  padding:56px 22px 20px !important;
  scroll-behavior:smooth;
  scrollbar-width:thin;
  scrollbar-color:#DFE2E7 transparent;
}
.pjd-left .pjd-chat-list::-webkit-scrollbar{width:10px}
.pjd-left .pjd-chat-list::-webkit-scrollbar-thumb{
  background:#DFE2E7;border-radius:10px;border:3px solid var(--pjdc-papel);
}
.pjd-left .pjd-chat-list::-webkit-scrollbar-thumb:hover{background:#CBD0D8}

/* ---------- Mensaje ---------- */
.pjd-left .pjd-msg{
  display:flex !important;
  gap:13px !important;
  margin-bottom:30px !important;
  animation:pjdcSube .5s var(--pjdc-out) both;
}
.pjd-left .pjd-msg:last-child{margin-bottom:6px !important}
@keyframes pjdcSube{
  from{opacity:0;transform:translateY(12px)}
  to{opacity:1;transform:none}
}

/* asistente */
.pjd-left .pjd-msg.is-assistant{justify-content:flex-start}
.pjd-left .pjd-msg-avatar{
  width:26px;height:26px;flex:none;
  border-radius:8px;
  background:var(--pjdc-niebla) !important;
  color:var(--pjdc-tinta-60) !important;
  display:grid !important;place-items:center !important;
  font-weight:700;font-size:14px;line-height:1;
  margin-top:2px;
  transition:background .22s,color .22s,transform .22s var(--pjdc-out);
}
.pjd-left .pjd-msg.is-assistant:hover .pjd-msg-avatar{
  background:var(--pjdc-tinta) !important;color:#fff !important;transform:scale(1.06);
}
.pjd-left .pjd-msg-col{flex:1;min-width:0}
.pjd-left .pjd-msg-meta{
  font-size:12.5px !important;
  font-weight:600 !important;
  color:var(--pjdc-tinta-35) !important;
  margin-bottom:6px !important;
  letter-spacing:.01em;
  font-variant-numeric:tabular-nums;
}
.pjd-left .pjd-msg-meta span{opacity:.55;margin:0 2px}
.pjd-left .pjd-msg.is-assistant .pjd-msg-body{
  background:none !important;
  padding:0 !important;
  border:0 !important;
  border-radius:0 !important;
  font-size:15.5px !important;
  line-height:1.7 !important;
  color:var(--pjdc-tinta) !important;
  word-break:break-word;
}
.pjd-left .pjd-msg-body strong{font-weight:700}
.pjd-left .pjd-msg-body p{margin:0 0 12px}
.pjd-left .pjd-msg-body p:last-child{margin-bottom:0}
.pjd-left .pjd-msg-body a{
  color:var(--pjdc-azul);text-decoration:none;
  border-bottom:1px solid rgba(0,122,255,.28);
  transition:border-color .18s;
}
.pjd-left .pjd-msg-body a:hover{border-bottom-color:var(--pjdc-azul)}

/* usuario */
.pjd-left .pjd-msg.is-user{justify-content:flex-end !important}
.pjd-left .pjd-msg.is-user .pjd-msg-body{
  max-width:78%;
  padding:11px 17px !important;
  background:var(--pjdc-burbuja) !important;
  color:var(--pjdc-tinta) !important;
  border-radius:20px 20px 5px 20px !important;   /* pestañita abajo a la derecha */
  border:0 !important;
  font-size:15px !important;
  line-height:1.6 !important;
  word-break:break-word;
  transition:background .2s;
}
.pjd-left .pjd-msg.is-user:hover .pjd-msg-body{background:#EAEBEE !important}

/* escribiendo / streaming */
.pjd-left .pjd-typing{display:inline-flex;gap:5px;align-items:center;height:26px}
.pjd-left .pjd-typing i{
  width:6px;height:6px;border-radius:50%;
  background:var(--pjdc-tinta-35);
  animation:pjdcBota 1.25s var(--pjdc-ease) infinite;
}
.pjd-left .pjd-typing i:nth-child(2){animation-delay:.16s}
.pjd-left .pjd-typing i:nth-child(3){animation-delay:.32s}
@keyframes pjdcBota{
  0%,60%,100%{transform:translateY(0);opacity:.35}
  30%{transform:translateY(-5px);opacity:1}
}
.pjd-left .pjd-caret{
  display:inline-block;width:2px;height:1.05em;
  background:var(--pjdc-azul);vertical-align:-3px;margin-left:2px;
  animation:pjdcParpadeo .9s steps(2) infinite;
}
@keyframes pjdcParpadeo{0%,50%{opacity:1}51%,100%{opacity:0}}
.pjd-left .pjd-error{color:#DC2626}
.pjd-left .pjd-retry{
  margin-left:8px;padding:5px 11px;
  border:1px solid var(--pjdc-borde) !important;border-radius:999px !important;
  background:#fff !important;color:var(--pjdc-tinta-60) !important;
  font:inherit;font-size:12.5px;font-weight:600;cursor:pointer;
  transition:background .18s,color .18s;
}
.pjd-left .pjd-retry:hover{background:var(--pjdc-niebla) !important;color:var(--pjdc-tinta) !important}

/* ---------- Bajar: círculo flotante ---------- */
.pjd-left .pjd-chat-jump{
  position:absolute;left:50%;bottom:96px;
  transform:translate(-50%,10px) scale(.9);
  width:38px;height:38px;
  display:grid !important;place-items:center !important;
  border:1px solid var(--pjdc-borde) !important;
  border-radius:50% !important;
  background:rgba(255,255,255,.94) !important;
  backdrop-filter:blur(12px);
  -webkit-backdrop-filter:blur(12px);
  color:var(--pjdc-tinta) !important;
  cursor:pointer;
  box-shadow:var(--pjdc-s3);
  opacity:0;pointer-events:none;
  transition:opacity .28s var(--pjdc-ease),transform .32s var(--pjdc-out),background .18s;
  z-index:5;
}
.pjd-left .pjd-chat-jump.is-on{opacity:1;pointer-events:auto;transform:translate(-50%,0) scale(1)}
.pjd-left .pjd-chat-jump:hover{background:#fff !important}
.pjd-left .pjd-chat-jump:active{transform:translate(-50%,0) scale(.92)}

/* ---------- Redactor ---------- */
.pjd-left .pjd-chat-input{
  flex:none !important;
  display:flex !important;
  align-items:flex-end !important;
  gap:8px !important;
  margin:0 22px 20px !important;
  padding:6px 6px 6px 18px !important;
  background:var(--pjdc-niebla) !important;
  border:1px solid transparent !important;
  border-radius:24px !important;
  box-shadow:none !important;
  outline:0 !important;
  transition:background .22s var(--pjdc-ease),border-color .22s var(--pjdc-ease),box-shadow .22s var(--pjdc-ease);
}
.pjd-left .pjd-chat-input:focus-within,
.pjd-left .pjd-chat-input:hover{
  background:#fff !important;
  border-color:#DFE2E7 !important;
  box-shadow:0 2px 10px -4px rgba(17,20,24,.14) !important;
  outline:0 !important;
}
/* mata cualquier anillo heredado del CSS anterior */
.pjd-left .pjd-chat-input *:focus,
.pjd-left .pjd-chat-input *:focus-visible,
.pjd-left .pjd-chat-input textarea:focus{
  box-shadow:none !important;
  outline:0 !important;
  border:0 !important;
}
.pjd-left .pjd-chat-input textarea{
  flex:1 !important;min-width:0 !important;
  border:0 !important;outline:0 !important;
  background:none !important;box-shadow:none !important;
  resize:none !important;
  font:inherit;font-size:15px;
  line-height:22px !important;
  color:var(--pjdc-tinta) !important;
  padding:5px 0 !important;
  height:32px;              /* una línea */
  min-height:32px;
  max-height:150px;
  overflow-y:hidden !important;   /* el JS lo pasa a auto al llegar al tope */
  scrollbar-width:none;
}
.pjd-left .pjd-chat-input textarea::-webkit-scrollbar{width:0;height:0;display:none}
.pjd-left .pjd-chat-input textarea::placeholder{color:var(--pjdc-tinta-35);opacity:1}

.pjd-left .pjd-chat-send{
  width:32px !important;height:32px !important;flex:none !important;
  border:0 !important;border-radius:50% !important;
  background:var(--pjdc-azul) !important;color:#fff !important;
  display:grid !important;place-items:center !important;
  cursor:pointer;
  transition:transform .25s var(--pjdc-out),background .22s,opacity .22s;
}
.pjd-left .pjd-chat-send svg{transform:translateX(1px)}
.pjd-left .pjd-chat-send:disabled{opacity:.32;cursor:default}
.pjd-left .pjd-chat-send:not(:disabled):hover{background:var(--pjdc-azul-oscuro) !important;transform:scale(1.07)}
.pjd-left .pjd-chat-send:not(:disabled):active{transform:scale(.93)}
.pjd-left .pjd-chat-send.is-loading{animation:pjdcPulso 1.2s var(--pjdc-ease) infinite}
@keyframes pjdcPulso{
  0%,100%{transform:scale(1);opacity:1}
  50%{transform:scale(.92);opacity:.68}
}

/* ---------- Responsivo ---------- */
@media (max-width:640px){
  .pjd-left .pjd-chat-list{padding:52px 15px 16px !important}
  .pjd-left .pjd-chat-input{margin:0 15px 15px !important}
  .pjd-left .pjd-msg.is-user .pjd-msg-body{max-width:88%}
  .pjd-left .pjd-chat-head{top:10px;right:12px}
}
@media (prefers-reduced-motion:reduce){
  .pjd-left *,.pjd-left *::before,.pjd-left *::after{
    animation-duration:.01ms !important;
    animation-iteration-count:1 !important;
    transition-duration:.01ms !important;
  }
  .pjd-left .pjd-chat-list{scroll-behavior:auto}
}
</style>


<script>
(function () {
  'use strict';
  if (window.__pjdChatBooted) return;   // evita doble binding con scripts.blade.php
  window.__pjdChatBooted = true;

  var lista  = document.getElementById('pjdChatList');
  var forma  = document.getElementById('pjdChatForm');
  var campo  = document.getElementById('pjdChatInput');
  var enviar = document.getElementById('pjdChatSend');
  var jump   = document.getElementById('pjdChatJump');
  var reset  = document.getElementById('pjdChatReset');
  if (!lista || !forma || !campo) return;

  var ENDPOINT   = forma.dataset.endpoint || '';
  var PROJECT_ID = forma.dataset.projectId || '';
  var tokenEl    = forma.querySelector('input[name="_token"]');
  var TOKEN      = tokenEl ? tokenEl.value : '';
  var quieto     = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function alFinal(){ lista.scrollTo({ top: lista.scrollHeight, behavior: quieto ? 'auto' : 'smooth' }); }
  function hora(){ return new Date().toLocaleTimeString('es-MX',{hour:'2-digit',minute:'2-digit',hour12:false}); }
  function esc(t){ var d=document.createElement('div'); d.textContent = t==null?'':String(t); return d.innerHTML; }

  /* textarea que crece */
  var ALTO_MIN = 32;    // una línea
  var ALTO_MAX = 150;   // a partir de aquí aparece el scroll

  function ajustar(){
    campo.style.height='auto';
    var alto = Math.max(ALTO_MIN, Math.min(campo.scrollHeight, ALTO_MAX));
    campo.style.height = alto + 'px';
    campo.style.overflowY = campo.scrollHeight > ALTO_MAX ? 'auto' : 'hidden';
    enviar.disabled = campo.value.trim()==='';
  }
  campo.addEventListener('input', ajustar);
  ajustar();

  /* Enter envía · Shift+Enter salta */
  campo.addEventListener('keydown', function(e){
    if(e.key==='Enter' && !e.shiftKey){
      e.preventDefault();
      if(!enviar.disabled) forma.requestSubmit();
    }
  });

  /* botón bajar */
  lista.addEventListener('scroll', function(){
    var lejos = lista.scrollHeight - lista.scrollTop - lista.clientHeight > 180;
    if(jump) jump.classList.toggle('is-on', lejos);
  });
  if(jump) jump.addEventListener('click', alFinal);

  /* reintentar */
  lista.addEventListener('click', function(e){
    if(!e.target.closest('.pjd-retry')) return;
    var msg = e.target.closest('.pjd-msg');
    if(msg) msg.remove();
    mandar(ultimo);
  });

  /* pintado */
  function pintarMio(texto){
    var el=document.createElement('div');
    el.className='pjd-msg is-user';
    el.innerHTML='<div class="pjd-msg-body"></div>';
    el.firstChild.textContent=texto;
    lista.appendChild(el);
    alFinal();
  }

  function pintarSam(){
    var el=document.createElement('div');
    el.className='pjd-msg is-assistant';
    el.innerHTML=
      '<div class="pjd-msg-avatar">j</div>'+
      '<div class="pjd-msg-col">'+
        '<div class="pjd-msg-meta">sam <span>·</span> '+hora()+'</div>'+
        '<div class="pjd-msg-body"><span class="pjd-typing"><i></i><i></i><i></i></span></div>'+
      '</div>';
    lista.appendChild(el);
    alFinal();
    return el.querySelector('.pjd-msg-body');
  }

  function teclear(cuerpo, texto){
    cuerpo.dataset.raw = texto;
    if(quieto){ cuerpo.innerHTML = esc(texto).replace(/\n/g,'<br>'); return Promise.resolve(); }
    cuerpo.innerHTML='<span class="pjd-stream"></span><span class="pjd-caret"></span>';
    var destino=cuerpo.querySelector('.pjd-stream');
    var caret=cuerpo.querySelector('.pjd-caret');
    return new Promise(function(listo){
      var i=0;
      var t=setInterval(function(){
        i+=2;
        destino.innerHTML=esc(texto.slice(0,i)).replace(/\n/g,'<br>');
        if(i%24===0) alFinal();
        if(i>=texto.length){ clearInterval(t); caret.remove(); alFinal(); listo(); }
      },16);
    });
  }

  /* envío */
  var ocupado=false, ultimo='';

  function mandar(texto){
    if(ocupado || !texto) return;
    ocupado=true; ultimo=texto;

    pintarMio(texto);
    campo.value=''; ajustar();
    enviar.disabled=true;
    enviar.classList.add('is-loading');

    var cuerpo=pintarSam();

    fetch(ENDPOINT,{
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN':TOKEN,
        'X-Requested-With':'XMLHttpRequest'
      },
      body:JSON.stringify({ message:texto, project_id:PROJECT_ID })
    })
    .then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
    .then(function(data){
      return teclear(cuerpo, data.reply || data.message || data.content || '');
    })
    .catch(function(err){
      console.error('[pjd-chat]', err);
      cuerpo.innerHTML='<span class="pjd-error">No pude responder. Revisa la conexión y vuelve a intentarlo.</span>'+
                       '<button type="button" class="pjd-retry">Reintentar</button>';
    })
    .then(function(){
      ocupado=false;
      enviar.classList.remove('is-loading');
      ajustar();
      campo.focus();
    });
  }

  forma.addEventListener('submit', function(e){
    e.preventDefault();
    mandar(campo.value.trim());
  });

  /* reiniciar */
  if(reset){
    reset.addEventListener('click', function(){
      if(!confirm('¿Reiniciar la conversación? Se borrarán los mensajes de este chat.')) return;
      lista.innerHTML='';
      campo.focus();
      // Si tienes endpoint de reset, llámalo aquí.
    });
  }

  alFinal();
})();
</script>
@endverbatim