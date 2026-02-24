@extends('layouts.app')

@section('title','WhatsApp | Chat')

@section('content')
<div class="container py-4" id="waChat">
  <style>
    #waChat{
      --ink:#0f172a; --muted:#64748b; --line:rgba(15,23,42,.10);
      --card:#fff; --bg:#f6f8fc; --r:16px;
      --shadow:0 10px 28px rgba(2,6,23,.06);
      --mine:rgba(37,99,235,.10);
      --theirs:rgba(2,6,23,.04);
    }
    #waChat .wa-card{background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow)}
    #waChat .wa-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px;border-bottom:1px solid var(--line)}
    #waChat .wa-title{font-weight:900;color:var(--ink);margin:0}
    #waChat .wa-sub{color:var(--muted);font-weight:700;font-size:12px}
    #waChat .wa-body{padding:14px;max-height:62vh;overflow:auto;background:linear-gradient(180deg, rgba(37,99,235,.03), transparent)}
    #waChat .bubble{max-width:70%;padding:10px 12px;border-radius:14px;border:1px solid var(--line);box-shadow:0 8px 20px rgba(2,6,23,.05)}
    #waChat .rowmsg{display:flex;margin-bottom:10px}
    #waChat .rowmsg.mine{justify-content:flex-end}
    #waChat .rowmsg.mine .bubble{background:var(--mine)}
    #waChat .rowmsg.theirs{justify-content:flex-start}
    #waChat .rowmsg.theirs .bubble{background:var(--theirs)}
    #waChat .meta{margin-top:6px;font-size:11px;color:var(--muted);font-weight:700;display:flex;gap:10px;align-items:center;justify-content:flex-end}
    #waChat .composer{display:flex;gap:10px;align-items:flex-end;padding:12px 14px;border-top:1px solid var(--line)}
    #waChat textarea{flex:1;border:1px solid var(--line);border-radius:14px;padding:10px 12px;min-height:44px;max-height:140px}
  </style>

  <div class="wa-card">
    <div class="wa-head">
      <div>
        <h2 class="wa-title">{{ $conversation->name ?: $conversation->wa_id }}</h2>
        <div class="wa-sub">wa_id: {{ $conversation->wa_id }}</div>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('whatsapp.index') }}" class="btn btn-outline-secondary">← Inbox</a>
        <button class="btn btn-outline-primary" type="button" onclick="location.reload()">Actualizar</button>
      </div>
    </div>

    <div class="wa-body" id="waScroll">
      @foreach($messages as $m)
        <div class="rowmsg {{ $m->direction === 'out' ? 'mine' : 'theirs' }}">
          <div class="bubble">
            <div style="white-space:pre-wrap">{{ $m->body ?: ($m->type ? '['.strtoupper($m->type).']' : '—') }}</div>
            <div class="meta">
              <span>{{ $m->created_at->format('H:i') }}</span>
              @if($m->direction === 'out')
                <span>• {{ $m->status ?: 'sent' }}</span>
              @endif
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <form class="composer" method="POST" action="{{ route('whatsapp.send',$conversation->id) }}">
      @csrf
      <textarea name="message" placeholder="Escribe un mensaje…">{{ old('message') }}</textarea>
      <button class="btn btn-primary" type="submit">Enviar</button>
    </form>
  </div>

  <script>
    // baja al final
    (function(){
      const el = document.getElementById('waScroll');
      if(el) el.scrollTop = el.scrollHeight;
    })();

    // auto-refresh simple cada 6s (modo “mini whatsapp”)
    setInterval(() => {
      // recarga sin perder tanto (simple)
      location.reload();
    }, 6000);
  </script>
</div>
@endsection