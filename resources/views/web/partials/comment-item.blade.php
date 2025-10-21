@php
  // Variables requeridas:
  // $comment  (App\Models\Comment con ->user y ->replies)
  // $initials (callable)
  // $fmtTime  (callable)
@endphp

<style>
  .c-item{display:grid; grid-template-columns:auto 1fr; gap:12px; padding:16px}
  .c-item + .c-item{border-top:1px dashed var(--line)}
  .avatar{
    width:44px; height:44px; border-radius:50%; background:#e9f1ff; color:#0b1a3a;
    display:flex; align-items:center; justify-content:center; font-weight:800; letter-spacing:.02em
  }
  .meta{display:flex; flex-wrap:wrap; gap:8px; align-items:center}
  .name{font-weight:700; color:#0b1a3a}
  .time{font-size:12px; color:var(--muted)}
  .text{margin-top:6px; color:#0b1a3a; line-height:1.6}
  .actions{margin-top:10px; display:flex; gap:10px}
  .link{background:transparent; border:0; padding:0; color:#1e88e5; cursor:pointer}
  .link:hover{text-decoration:underline}

  .replies{margin-left:56px; border-left:2px solid var(--line)}
  .replies .c-item{border-top:0; padding-left:14px}

  .c-form{background:#fff; border:1px solid var(--line); border-radius:14px; padding:12px; margin-top:10px}
  .c-form label{display:block; font-size:12px; color:var(--muted); margin-bottom:6px}
  .c-form textarea{width:100%; min-height:100px; border:1px solid var(--line); border-radius:12px; padding:10px}
  .btn{display:inline-flex; align-items:center; gap:8px; border-radius:14px; border:1px solid #cfe1ff; padding:10px 14px; background: var(--brand); color:#04122b; font-weight:700; box-shadow: 0 10px 24px rgba(110,168,254,.35); cursor:pointer; text-decoration:none}
</style>

<article class="c-item" id="c-{{ $comment->id }}" data-reveal>
  <div class="avatar" aria-hidden="true">
    {{ $initials($comment->nombre ?? ($comment->user->name ?? $comment->user->email ?? 'U')) }}
  </div>
  <div>
    <div class="meta">
      <span class="name">
        {{ $comment->nombre ?? ($comment->user->name ?? 'Usuario') }}
      </span>
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

    {{-- Formulario de respuesta (solo 1 nivel) --}}
    @auth
      <div class="c-form" id="reply-{{ $comment->id }}" style="display:none" data-open="0">
        <form method="POST" action="{{ route('comments.reply', $comment->id) }}">
          @csrf
          <label>Tu respuesta</label>
          <textarea name="contenido" required placeholder="Escribe tu respuesta…"></textarea>
          <div style="margin-top:10px; display:flex; justify-content:flex-end">
            <button class="btn" type="submit">Responder</button>
          </div>
        </form>
      </div>
    @endauth

    {{-- Respuestas (nivel 1) --}}
    @if($comment->replies && $comment->replies->count())
      <div class="replies" style="margin-top:8px">
        @foreach($comment->replies as $reply)
          <div class="c-item" id="c-{{ $reply->id }}" data-reveal>
            <div class="avatar">
              {{ $initials($reply->nombre ?? ($reply->user->name ?? $reply->user->email ?? 'U')) }}
            </div>
            <div>
              <div class="meta">
                <span class="name">{{ $reply->nombre ?? ($reply->user->name ?? 'Usuario') }}</span>
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
