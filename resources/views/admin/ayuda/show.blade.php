{{-- resources/views/admin/ayuda/show.blade.php --}}
@extends('layouts.app')
@section('title','Ticket #'.$ticket->id)
@section('titulo','Ticket #'.$ticket->id.' — '.$ticket->subject)

@section('content')
<style>
#staff-ticket{ --line:#e8eef6; --ink:#0e1726; --muted:#6b7280; --surface:#fff; --radius:16px }
#staff-ticket .wrap{max-width:1100px;margin:40px auto;padding:0 16px}
#staff-ticket .card{border:1px solid var(--line);border-radius:var(--radius);background:var(--surface)}
#staff-ticket .chat{max-height:60vh; overflow:auto; padding:14px}
#staff-ticket .msg{margin:10px 0}
#staff-ticket .bubble{display:inline-block; max-width:80%; padding:10px 12px; border:1px solid var(--line); border-radius:14px}
#staff-ticket .user .bubble{background:#eef6ff}
#staff-ticket .ai .bubble{background:#f3f7ff}
#staff-ticket .agent .bubble{background:#eaffe7}
#staff-ticket .meta{font-size:11px;color:var(--muted); margin-top:4px}
#staff-ticket form{display:flex; gap:10px; margin-top:12px}
#staff-ticket textarea{flex:1; min-height:80px}
#staff-ticket .btn{padding:10px 14px; border:1px solid var(--line); border-radius:12px; background:#fff; cursor:pointer}
#staff-ticket .btn:hover{box-shadow:0 8px 20px rgba(2,8,23,.08)}
</style>

<div id="staff-ticket">
  <div class="wrap">
    <div style="margin-bottom:10px;color:#475569">
      Usuario: <strong>{{ $ticket->user?->name ?? 'N/D' }}</strong> |
      Estatus: <strong>{{ $ticket->status }}</strong> |
      Prioridad: <strong>{{ $ticket->priority }}</strong> |
      Categoría: <strong>{{ $ticket->category ?? '—' }}</strong>
    </div>

    <div class="card">
      <div class="chat" id="chatbox">
        @foreach($ticket->messages as $m)
          <div class="msg {{ $m->sender_type }}">
            <div class="bubble">
              {!! nl2br(e($m->body)) !!}
              <div class="meta">
                {{ ucfirst($m->sender_type) }}
                @if($m->sender) — {{ $m->sender->name }} @endif
                • {{ $m->created_at->format('d/m/Y H:i') }}
                @if($m->is_solution) • ✅ Solución @endif
              </div>
            </div>
          </div>
        @endforeach
      </div>

      @if($ticket->status !== 'closed')
      <form method="POST" action="{{ route('admin.help.reply',$ticket) }}">
        @csrf
        <textarea name="body" placeholder="Responder al usuario…" required></textarea>
        <label style="display:flex;align-items:center;gap:6px"><input type="checkbox" name="solve" value="1"> Marcar como solucionado</label>
        <button class="btn" type="submit">Enviar</button>
      </form>
      <form method="POST" action="{{ route('admin.help.close',$ticket) }}" style="margin:10px 0 12px">
        @csrf
        <button class="btn" type="submit">Cerrar ticket</button>
      </form>
      @else
      <div style="padding:12px; color:#16a34a">Ticket cerrado ✅</div>
      @endif
    </div>
  </div>
</div>

<script>
  (function(){ const box = document.getElementById('chatbox'); if (box) box.scrollTop = box.scrollHeight; })();
</script>
@endsection
