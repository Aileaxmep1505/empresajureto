@extends('layouts.web')
@section('title','Comentarios')

@section('content')
<style>
/* ========= NAMESPACE AISLADO ========= */
#cmt { --ink:#0e1726; --muted:#6b7280; --line:#e8eef6;
       --brand:#9ec5fe; /* azul pastel */
       --brand-ink:#0b1a3a; --chip:#eef4ff; --chip-ink:#102a5f; }

/* Layout general del módulo */
#cmt{
  position: relative;
  width:100%;
  margin: clamp(24px,3vw,44px) auto 0;
  padding: 0 16px;
  color: var(--ink);
}

/* ====== FONDO DEGRADADO FIJO ====== */
#cmt::before{
  content:"";
  position: fixed;
  z-index: -2;
  top:0; left:50%; width:100vw; height:100vh; margin-left:-50vw;
  background:
    radial-gradient(1200px 700px at 85% 5%, rgba(255,255,255,.85) 0%, rgba(255,255,255,0) 55%),
    radial-gradient(900px 600px at 10% 18%, rgba(255,255,255,.75) 0%, rgba(255,255,255,0) 60%),
    linear-gradient(to bottom,
      #f3f4f6 0%,
      #f7f7f8 18%,
      #fff6e9 48%,
      #ffe8c9 70%,
      #ffd9a6 100%
    );
  box-shadow: inset 0 -120px 200px rgba(255,153,51,.08);
  pointer-events:none;
}
#cmt::after{
  content:"";
  position: fixed;
  z-index: -1;
  top:0; left:50%; width:100vw; height:100vh; margin-left:-50vw;
  opacity:.06; pointer-events:none; mix-blend-mode: multiply;
  background-image: url("data:image/svg+xml;utf8,\
  <svg xmlns='http://www.w3.org/2000/svg' width='160' height='160' viewBox='0 0 160 160'>\
    <filter id='n'><feTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='2' stitchTiles='stitch'/><feColorMatrix type='saturate' values='0'/><feComponentTransfer><feFuncA type='table' tableValues='0 0 .8 0'/></feComponentTransfer></filter>\
    <rect width='160' height='160' filter='url(%23n)' opacity='0.55'/>\
  </svg>");
  background-size: 220px 220px;
}

/* Contenedor de ancho máximo (solo para centrar texto/campos) */
#cmt .container{ max-width:1100px; margin:0 auto; }

/* ===== Header (SIN TARJETA) ===== */
#cmt .head{ text-align:center; margin-bottom:16px; }
#cmt .head h1{ margin:0 0 8px; letter-spacing:-.02em; font-size: clamp(28px,4vw,48px); color:#0b1a3a; }
#cmt .head p{ margin:0 auto; color:var(--muted); max-width: 880px; }
#cmt .metrics{ margin-top:14px; display:flex; gap:10px; justify-content:center; flex-wrap:wrap; }
#cmt .chip{ padding:8px 12px; border-radius:999px; background:var(--chip); color:var(--chip-ink); border:1px solid var(--line); font-weight:600; font-size:13px; }

#cmt .toolbar{ margin-top:14px; display:flex; justify-content:space-between; align-items:center; gap:12px; }
#cmt .sort{ display:flex; align-items:center; gap:8px; font-size:14px; color:var(--muted); }
#cmt select{ border:1px solid var(--line); border-radius:12px; padding:8px 12px; background:#fff; color:#0b1a3a; }
#cmt .badge{ font-size:12px; padding:6px 10px; border-radius:999px; background:#f6f8fc; border:1px solid var(--line); color:#334155; }

/* ===== Composer (sin tarjeta) ===== */
#cmt .composer{ margin: 18px 0 10px; }
#cmt .userline{ display:grid; grid-template-columns:1fr 1fr; gap:10px; }
#cmt .userline input{
  width:100%; padding:10px 12px; border-radius:12px; border:1px dashed var(--line); background:#fafcff; color:var(--ink);
}
#cmt .textwrap{ margin-top:10px; }
#cmt textarea{
  width:100%; min-height:120px; resize:vertical; padding:14px; border-radius:16px; border:1px solid var(--line);
  outline:none; color:var(--ink); background:#fff; transition: box-shadow .2s ease, border-color .2s ease;
}
#cmt textarea:focus{ border-color:#cfe1ff; box-shadow: 0 0 0 4px rgba(110,168,254,.15); }
#cmt .meta-row{ display:flex; justify-content:space-between; align-items:center; margin-top:8px; }
#cmt .counter{ font-size:12px; color:var(--muted) }

/* ===== BOTÓN (pedido) ===== */
#cmt .btn,
#cmt a.btn,
#cmt .btn:link,
#cmt .btn:visited{
  appearance:none;
  border:none;                    /* sin borde */
  background: var(--brand);       /* azul pastel */
  color:#fff;                     /* letras blancas */
  font-weight:800;
  padding:10px 16px;
  border-radius:14px;
  cursor:pointer;
  display:inline-flex; align-items:center; gap:8px;
  text-decoration:none !important;/* sin subrayado incluso si es <a> */
  box-shadow: 0 10px 24px rgba(110,168,254,.35);
  transition: transform .15s ease, box-shadow .15s ease, background .15s ease, color .15s ease;
}
#cmt .btn:hover,
#cmt .btn:focus{
  background:#fff;                /* se hace blanco */
  color: var(--brand-ink);        /* texto oscuro para contraste */
  border:none;                    /* sin bordes en hover */
  box-shadow: 0 0 0 8px rgba(110,168,254,.22); /* halo alrededor */
  transform: translateY(-1px);
}
#cmt .btn:disabled{ opacity:.6; cursor:not-allowed; box-shadow:none }

/* ===== Alerts (texto suelto) ===== */
#cmt .alerts{ margin-top:12px; }
#cmt .alert{ padding:10px 12px; border-radius:12px; font-size:14px; }
#cmt .ok{ background:#ecfdf5; border:1px solid #bbf7d0; color:#065f46 }
#cmt .bad{ background:#fef2f2; border:1px solid #fecaca; color:#7f1d1d }

/* ===== Lista (sin tarjeta; solo separadores) ===== */
#cmt .list{ margin-top:18px; }
#cmt .item{ display:grid; grid-template-columns:auto 1fr; gap:12px; padding:16px 0; }
#cmt .item + .item{ border-top:1px dashed var(--line); }
#cmt .avatar{
  width:44px; height:44px; border-radius:50%; background:#e9f1ff; color:#0b1a3a;
  display:flex; align-items:center; justify-content:center; font-weight:800; letter-spacing:.02em;
  box-shadow: inset 0 0 0 2px #dbe7ff;
}
#cmt .meta{ display:flex; flex-wrap:wrap; gap:8px; align-items:center }
#cmt .name{ font-weight:800; color:#0b1a3a }
#cmt .role{ font-size:12px; background:#f1f5ff; color:#1d3a8a; padding:2px 8px; border-radius:999px; border:1px solid #dde6ff }
#cmt .time{ font-size:12px; color:var(--muted) }
#cmt .text{ margin-top:6px; color:#0b1a3a; line-height:1.7 }
#cmt .actions{ margin-top:10px; display:flex; gap:12px; flex-wrap:wrap }
#cmt .link{ background:transparent; border:0; color:#1e88e5; padding:0; cursor:pointer; font-weight:600; text-decoration:none; }
#cmt .link:hover{ text-decoration: underline; }

#cmt .replies{ margin-left:56px; border-left:2px solid var(--line) }
#cmt .replies .item{ border-top:0; padding-left:12px }

#cmt .reply-form{ background:transparent; border:0; margin-top:10px; display:none }
#cmt .reply-form textarea{ width:100%; min-height:100px; border:1px solid var(--line); border-radius:12px; padding:10px }
#cmt .reply-actions{ display:flex; justify-content:flex-end; margin-top:8px }

#cmt .pager{ display:flex; justify-content:center; padding: 10px 0; }

/* Reveal sutil */
#cmt [data-reveal]{ opacity:0; transform: translateY(10px); }
#cmt .in-view{ opacity:1; transform:none; transition: all .5s cubic-bezier(.2,.7,.2,1) }

@media (max-width: 720px){
  #cmt .userline{ grid-template-columns:1fr; }
  #cmt .toolbar{ flex-direction: column; align-items: center; gap:8px; }
}
</style>

<div id="cmt">
  <div class="container">
    <!-- HEADER -->
    <section class="head" data-reveal>
      <h1>Comentarios</h1>
      <p>Tu opinión nos ayuda a mejorar. Deja tu comentario o resuelve dudas con la comunidad.</p>

      <div class="metrics">
        <span class="chip">🔒 Solo usuarios registrados</span>
        <span class="chip">🛡️ Anti-spam activo</span>
        <span class="chip">💬 Respuestas 1 nivel</span>
      </div>

      <div class="toolbar">
        <div class="sort">
          Ordenar por:
          <form method="GET" action="{{ route('comments.index') }}">
            @php $ord = request('ord','recientes'); @endphp
            <select name="ord" onchange="this.form.submit()">
              <option value="recientes" {{ $ord==='recientes'?'selected':'' }}>Más recientes</option>
              <option value="antiguos"  {{ $ord==='antiguos'?'selected':''  }}>Más antiguos</option>
            </select>
          </form>
        </div>
        @if(method_exists($comments,'total'))
          <span class="badge">Total: {{ $comments->total() }}</span>
        @endif
      </div>
    </section>

    <!-- ALERTAS -->
    <div class="alerts">
      @if (session('status'))
        <div class="alert ok" data-reveal>{{ session('status') }}</div>
      @endif
      @if ($errors->any())
        <div class="alert bad" data-reveal>
          @foreach ($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
        </div>
      @endif
    </div>

    <!-- COMPOSER -->
    @auth
    <section class="composer" data-reveal>
      <form method="POST" action="{{ route('comments.store') }}">
        @csrf
        <div class="userline">
          <input value="{{ auth()->user()->name ?? auth()->user()->email }}" readonly aria-label="Usuario">
          <input value="{{ auth()->user()->email }}" readonly aria-label="Correo">
        </div>
        <div class="textwrap">
          <textarea id="cmt-new" name="contenido" placeholder="Escribe tu comentario…" required>{{ old('contenido') }}</textarea>
        </div>
        <div class="meta-row">
          <div class="counter"><span id="cmt-count">0</span>/4000</div>
          <button class="btn" id="cmt-send" type="submit" disabled>Publicar comentario</button>
        </div>
      </form>
    </section>
    @else
    <section class="composer" data-reveal>
      <div style="display:flex; align-items:center; justify-content:space-between; gap:12px">
        <div style="color:var(--muted)">Inicia sesión para participar en los comentarios.</div>
        <a class="btn" href="{{ url('/login') }}">Iniciar sesión</a>
      </div>
    </section>
    @endauth

    <!-- LISTA -->
    <section class="list" data-reveal>
      @php
        $fmtTime = static fn($dt) => optional($dt)->diffForHumans();
        $initials = static fn($name) => mb_strtoupper(collect(explode(' ', trim($name ?? 'U')))
                               ->take(2)->map(fn($p)=>mb_substr($p,0,1))->implode(''));
      @endphp

      @forelse ($comments as $comment)
        <article class="item" id="c-{{ $comment->id }}" data-reveal>
          <div class="avatar" aria-hidden="true">
            {{ $initials($comment->nombre ?? ($comment->user->name ?? $comment->user->email ?? 'U')) }}
          </div>
          <div>
            <div class="meta">
              <span class="name">{{ $comment->nombre ?? ($comment->user->name ?? 'Usuario') }}</span>
              @if(optional($comment->user)->email === optional(auth()->user())->email)
                <span class="role">Tú</span>
              @endif
              <span class="time">• {{ $fmtTime($comment->created_at) }}</span>
            </div>

            <div class="text">{!! nl2br(e($comment->contenido)) !!}</div>

            <div class="actions">
              @auth
                <button class="link" data-reply-toggle data-target="#reply-{{ $comment->id }}">Responder</button>
              @else
                <a class="link" href="{{ url('/login') }}">Inicia sesión para responder</a>
              @endauth
            </div>

            @auth
            <div class="reply-form" id="reply-{{ $comment->id }}" data-open="0">
              <form method="POST" action="{{ route('comments.reply', $comment->id) }}">
                @csrf
                <textarea name="contenido" required placeholder="Escribe tu respuesta…"></textarea>
                <div class="reply-actions">
                  <button class="btn" type="submit">Responder</button>
                </div>
              </form>
            </div>
            @endauth

            @if($comment->replies && $comment->replies->count())
              <div class="replies" style="margin-top:8px">
                @foreach($comment->replies as $reply)
                  <div class="item" id="c-{{ $reply->id }}" data-reveal>
                    <div class="avatar">
                      {{ $initials($reply->nombre ?? ($reply->user->name ?? $reply->user->email ?? 'U')) }}
                    </div>
                    <div>
                      <div class="meta">
                        <span class="name">{{ $reply->nombre ?? ($reply->user->name ?? 'Usuario') }}</span>
                        @if(optional($reply->user)->email === optional(auth()->user())->email)
                          <span class="role">Tú</span>
                        @endif
                        <span class="time">• {{ $fmtTime($reply->created_at) }}</span>
                      </div>
                      <div class="text">{!! nl2br(e($reply->contenido)) !!}</div>
                    </div>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        </article>
      @empty
        <div class="item" data-reveal>
          <div class="avatar">💬</div>
          <div>
            <div class="name" style="font-weight:800; color:#0b1a3a">Sé el primero en comentar</div>
            <div class="text" style="color:var(--muted)">Aún no hay comentarios. ¡Cuéntanos qué piensas!</div>
          </div>
        </div>
      @endforelse
    </section>

    @if(method_exists($comments,'links'))
      <div class="pager">
        {{ $comments->appends(['ord' => request('ord')])->links() }}
      </div>
    @endif
  </div>
</div>

<script>
(() => {
  const root = document.getElementById('cmt');
  if(!root) return;

  // Reveal
  const io = 'IntersectionObserver' in window
    ? new IntersectionObserver((entries)=>{
        entries.forEach(e=>{ if(e.isIntersecting){ e.target.classList.add('in-view'); io.unobserve(e.target); } })
      }, {threshold:.08, rootMargin:'0px 0px -5% 0px'})
    : null;
  (io ? root.querySelectorAll('[data-reveal]') : []).forEach(el=> io.observe(el));
  if(!io){ root.querySelectorAll('[data-reveal]').forEach(el=> el.classList.add('in-view')); }

  // Autosize + contador
  function autoGrow(t){ t.style.height='auto'; t.style.height = (t.scrollHeight+2)+'px'; }
  const area = root.querySelector('#cmt-new');
  const count = root.querySelector('#cmt-count');
  const send  = root.querySelector('#cmt-send');
  root.querySelectorAll('textarea').forEach(t=>{
    t.addEventListener('input', ()=>{ autoGrow(t); if(t===area) tick(); });
    autoGrow(t);
  });
  function tick(){
    if(!area || !count || !send) return;
    const len = (area.value || '').length;
    count.textContent = len;
    send.disabled = len < 2 || len > 4000;
  }
  tick();

  // Toggle replies
  root.addEventListener('click', (e)=>{
    const btn = e.target.closest('[data-reply-toggle]');
    if(!btn) return;
    const sel = btn.getAttribute('data-target');
    const el = root.querySelector(sel);
    if(!el) return;
    const open = el.getAttribute('data-open') === '1';
    el.style.display = open ? 'none' : 'block';
    el.setAttribute('data-open', open ? '0' : '1');
    if(!open){ el.querySelector('textarea')?.focus(); }
  });
})();
</script>
@endsection
