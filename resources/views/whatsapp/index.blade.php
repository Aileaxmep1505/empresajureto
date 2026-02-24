@extends('layouts.app')

@section('title','WhatsApp | Inbox')

@section('content')
<div class="container py-4" id="waInbox">
  <style>
    #waInbox{
      --ink:#0f172a; --muted:#64748b; --line:rgba(15,23,42,.10);
      --card:#fff; --bg:#f6f8fc; --r:16px;
      --shadow:0 10px 28px rgba(2,6,23,.06);
      --blue:#2563eb;
    }
    #waInbox .wa-card{background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow)}
    #waInbox .wa-top{display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap}
    #waInbox .wa-title{font-weight:900;font-size:18px;color:var(--ink);margin:0}
    #waInbox .wa-search{display:flex;gap:8px;align-items:center}
    #waInbox input{border:1px solid var(--line);border-radius:12px;padding:10px 12px;min-width:260px}
    #waInbox .wa-list{margin-top:14px}
    #waInbox .wa-item{display:flex;gap:12px;align-items:center;justify-content:space-between;padding:12px 14px;border-top:1px solid var(--line);text-decoration:none;color:inherit}
    #waInbox .wa-item:hover{background:rgba(37,99,235,.04)}
    #waInbox .wa-left{display:flex;gap:12px;align-items:center;min-width:0}
    #waInbox .wa-avatar{width:40px;height:40px;border-radius:14px;background:rgba(37,99,235,.10);display:grid;place-items:center;font-weight:900;color:var(--blue)}
    #waInbox .wa-meta{min-width:0}
    #waInbox .wa-name{font-weight:900;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    #waInbox .wa-preview{color:var(--muted);font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:520px}
    #waInbox .wa-right{text-align:right;display:flex;flex-direction:column;gap:6px;align-items:flex-end}
    #waInbox .wa-time{color:var(--muted);font-size:12px;font-weight:700}
    #waInbox .wa-badge{background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.25);color:#065f46;
      font-weight:900;border-radius:999px;padding:4px 10px;font-size:12px}
  </style>

  <div class="wa-card p-3">
    <div class="wa-top">
      <h1 class="wa-title">WhatsApp Inbox (Jureto)</h1>

      <form class="wa-search" method="GET" action="{{ route('whatsapp.index') }}">
        <input name="q" value="{{ $q }}" placeholder="Buscar por nombre o número…" />
        <button class="btn btn-primary" type="submit">Buscar</button>
      </form>
    </div>

    <div class="wa-list">
      @forelse($convs as $c)
        <a class="wa-item" href="{{ route('whatsapp.show',$c->id) }}">
          <div class="wa-left">
            <div class="wa-avatar">{{ strtoupper(mb_substr($c->name ?: $c->wa_id,0,1)) }}</div>
            <div class="wa-meta">
              <div class="wa-name">{{ $c->name ?: $c->wa_id }}</div>
              <div class="wa-preview">{{ $c->last_message_preview ?: '—' }}</div>
            </div>
          </div>
          <div class="wa-right">
            <div class="wa-time">{{ optional($c->last_message_at)->format('d/m/Y H:i') }}</div>
            @if($c->unread_count > 0)
              <div class="wa-badge">{{ $c->unread_count }} nuevo(s)</div>
            @endif
          </div>
        </a>
      @empty
        <div class="p-3 text-muted">Aún no hay conversaciones.</div>
      @endforelse
    </div>

    <div class="mt-3">
      {{ $convs->withQueryString()->links() }}
    </div>
  </div>
</div>
@endsection