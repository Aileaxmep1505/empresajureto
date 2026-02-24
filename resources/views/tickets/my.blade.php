@extends('layouts.app')
@section('title','Mis tickets')

@section('content')
@php
  $statuses   = \App\Http\Controllers\Tickets\TicketController::STATUSES;
  $priorities = \App\Http\Controllers\Tickets\TicketController::PRIORITIES;
  $areas      = \App\Http\Controllers\Tickets\TicketController::AREAS;
@endphp

<div class="container py-4" id="tkMy">
  <style>
    #tkMy{
      --ink:#0b1220; --muted:rgba(15,23,42,.62); --line:rgba(15,23,42,.10);
      --card:rgba(255,255,255,.94); --radius:16px; --shadow:0 14px 44px rgba(2,6,23,.08);
      --blue:rgba(59,130,246,.13);
    }
    #tkMy .cardx{ background:var(--card); border:1px solid var(--line); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden; }
    #tkMy .head{ padding:16px 18px; display:flex; justify-content:space-between; align-items:center; gap:10px; border-bottom:1px solid var(--line); background:linear-gradient(180deg,#fff,#f7f9ff); }
    #tkMy .h-title{ margin:0; font-weight:1000; color:var(--ink); }
    #tkMy .btnx{ border:1px solid var(--line); border-radius:12px; padding:10px 12px; font-weight:900; background:#fff; color:var(--ink); text-decoration:none; }
    #tkMy .btnx.primary{ background:var(--blue); border-color:rgba(59,130,246,.25); }
    #tkMy .filters{ padding:14px 18px; display:grid; grid-template-columns: 2fr 1fr 1fr auto; gap:10px; border-bottom:1px solid var(--line); background:#fff; }
    #tkMy .input, #tkMy select{ width:100%; border:1px solid var(--line); border-radius:12px; padding:10px 12px; background:#fff; font-weight:800; color:var(--ink); }
    #tkMy .rowx{ padding:14px 18px; border-bottom:1px solid var(--line); background:#fff; display:flex; justify-content:space-between; gap:12px; }
    #tkMy .t-title{ font-weight:1000; color:var(--ink); text-decoration:none; }
    #tkMy .muted{ color:var(--muted); font-weight:800; font-size:12px; }
    #tkMy .foot{ padding:14px 18px; background:#fff; }
    @media(max-width: 992px){ #tkMy .filters{ grid-template-columns:1fr; } #tkMy .rowx{ flex-direction:column; } }
  </style>

  <div class="cardx">
    <div class="head">
      <h3 class="h-title">Mis tickets</h3>
      <div class="d-flex gap-2">
        <a class="btnx" href="{{ route('tickets.index') }}">← Todos</a>
        <a class="btnx primary" href="{{ route('tickets.create') }}">+ Nuevo</a>
      </div>
    </div>

    <form class="filters" method="GET" action="{{ route('tickets.my') }}">
      <input class="input" name="q" placeholder="Buscar…" value="{{ $q ?? request('q') }}">

      <select name="status">
        <option value="">Estatus</option>
        @foreach($statuses as $k => $label)
          <option value="{{ $k }}" @selected(($status ?? request('status'))===$k)>{{ $label }}</option>
        @endforeach
      </select>

      <select name="area">
        <option value="">Área</option>
        @foreach($areas as $k => $label)
          <option value="{{ $k }}" @selected(($area ?? request('area'))===$k)>{{ $label }}</option>
        @endforeach
      </select>

      <button class="btnx primary" type="submit">Filtrar</button>
    </form>

    @forelse($tickets as $t)
      <div class="rowx">
        <div style="min-width:0">
          <a class="t-title" href="{{ route('tickets.show',$t) }}">{{ $t->folio }} — {{ $t->title }}</a>
          <div class="muted">
            {{ $areas[$t->area] ?? ($t->area ?: 'Sin área') }}
            · {{ $priorities[$t->priority] ?? $t->priority }}
            · {{ $statuses[$t->status] ?? $t->status }}
            · Vence: {{ $t->due_at ? $t->due_at->format('Y-m-d H:i') : '—' }}
            · Score: {{ $t->score ?? '—' }}
          </div>
        </div>
        <div class="text-end">
          <a class="btnx" href="{{ route('tickets.show',$t) }}">Abrir</a>
        </div>
      </div>
    @empty
      <div class="p-4">
        <div class="muted">No tienes tickets asignados con esos filtros.</div>
      </div>
    @endforelse

    <div class="foot">
      {{ $tickets->links() }}
    </div>
  </div>
</div>
@endsection