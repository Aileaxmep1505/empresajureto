{{-- COLUMNA IZQUIERDA: CHAT --}}
    <div class="pjd-left">
      <div class="pjd-chat-head">
        <button type="button" class="pjd-chat-reset" id="pjdChatReset" title="Reiniciar chat">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 3v6h6"/></svg>
          Reiniciar
        </button>
      </div>

      <div class="pjd-chat-list" id="pjdChatList">
        @forelse($project->chatMessages as $m)
          <div class="pjd-msg {{ $m->role === 'user' ? 'is-user' : 'is-assistant' }}">
            @if($m->role === 'assistant')
              <div class="pjd-msg-avatar">j</div>
              <div style="flex:1;min-width:0;">
                <div class="pjd-msg-meta">sam · {{ $m->created_at->format('H:i') }}</div>
                <div class="pjd-msg-body" data-raw="{{ $m->content }}">{!! nl2br(e($m->content)) !!}</div>
              </div>
            @else
              <div class="pjd-msg-body">{{ $m->content }}</div>
            @endif
          </div>
        @empty
          <div class="pjd-msg is-assistant">
            <div class="pjd-msg-avatar">j</div>
            <div>
              <div class="pjd-msg-meta">sam</div>
              <div class="pjd-msg-body">Hola, soy tu asistente del proyecto. Puedes pedirme un resumen de las bases, los requisitos clave, las fechas importantes o cualquier duda sobre la licitación.</div>
            </div>
          </div>
        @endforelse
      </div>

      <form class="pjd-chat-input" id="pjdChatForm" autocomplete="off">
        @csrf
        <input type="text" name="message" id="pjdChatInput" placeholder="Pregunta a sam (Shift+Enter para salto de línea)">
        <button type="submit" class="pjd-chat-send" id="pjdChatSend" aria-label="Enviar">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M2 21l21-9L2 3v7l15 2-15 2z"/></svg>
        </button>
      </form>
    </div>
