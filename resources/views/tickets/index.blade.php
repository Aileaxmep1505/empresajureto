{{-- resources/views/tickets/index.blade.php --}}
@extends('layouts.app')
@section('title','Tickets')

@section('content')
<div id="tkt" class="container py-4">
  <style>
    #tkt{ --ink:#0e1726; --muted:#64748b; --line:#e5e7eb; --card:#fff; --bg:#f8fafc;
          --ok:#22c55e; --warn:#f59e0b; --danger:#ef4444; --brand:#9bd0ff; }
    #tkt .h{font-weight:800;color:var(--ink)}
    #tkt .table{width:100%;border-collapse:separate;border-spacing:0 10px}
    #tkt .row{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:12px 16px;display:grid;grid-template-columns:130px 1fr 120px 120px 120px;gap:12px;align-items:center}
    #tkt .chip{padding:.15rem .55rem;border-radius:999px;font-size:.78rem;border:1px solid var(--line);background:#fff}
    #tkt .chip.ok{background:#e9fbe9;border-color:#c0f2c0}
    #tkt .chip.warn{background:#fff7e6;border-color:#fde7b0}
    #tkt .chip.bad{background:#ffecec;border-color:#ffc9c9}
    #tkt .btn{background:linear-gradient(180deg,#fff,#f2f7ff);border:1px solid #dbe4ff;border-radius:10px;padding:.45rem .75rem}
    #tkt .btn:hover{transform:translateY(-1px)}
    #tkt .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
    #tkt .top a{font-weight:700}
  </style>

  <div class="top">
    <h1 class="h">Tickets</h1>
    <a class="btn" href="{{ route('tickets.create') }}">+ Nuevo</a>
  </div>

  <div class="table">
    @foreach($tickets as $tk)
      @php
        // Usar accessor como propiedad
        $sig = $tk->sla_signal;
        $signalClass = $sig==='overdue' ? 'bad' : ($sig==='due_soon' ? 'warn' : 'ok');
      @endphp
      <div class="row">
        <div>
          <strong>{{ $tk->folio }}</strong>
          <div class="muted">{{ strtoupper($tk->type) }}</div>
        </div>
        <div>
          <div>{{ $tk->client_name ?? ($tk->client->name ?? '—') }}</div>
          <div class="muted" style="font-size:.85rem">{{ ucfirst($tk->status) }} · {{ $tk->progress }}%</div>
        </div>
        <div><span class="chip {{ $signalClass }}">{{ $tk->due_at? $tk->due_at->format('d/m H:i'):'—' }}</span></div>
        <div><span class="chip">{{ ucfirst($tk->priority) }}</span></div>
        <div><a class="btn" href="{{ route('tickets.show',$tk) }}">Abrir</a></div>
      </div>
    @endforeach
  </div>

  <div class="mt-3">{{ $tickets->links() }}</div>
</div>
@endsection
