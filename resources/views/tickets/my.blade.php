@extends('layouts.app')
@section('title','Mis tareas')
@section('content')
<div class="container py-4" id="mytasks">
  <h1 class="h">Tareas asignadas</h1>
  <ul>
    @forelse($stages as $st)
      <li>
        <strong>{{ $st->ticket->title }}</strong> · Paso {{ $st->position }}: {{ $st->name }}
        — Estado: {{ ucfirst($st->status) }}
        — <a href="{{ route('tickets.wizard.show',[$st->ticket,$st->position]) }}">Abrir</a>
      </li>
    @empty
      <li>No tienes tareas pendientes.</li>
    @endforelse
  </ul>
</div>
@endsection
