@extends('layouts.app')
@section('title', $ticket->folio.' · Paso '.$stage->position)
@section('content')
<div id="tkt-step" class="container py-4">
  <style>
    #tkt-step{--ink:#0e1726;--muted:#64748b;--line:#e5e7eb;--card:#fff}
    #tkt-step .grid{display:grid;grid-template-columns:1.1fr .9fr;gap:16px}
    #tkt-step .card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px}
    @media (max-width:1000px){#tkt-step .grid{grid-template-columns:1fr}}
  </style>

  <h1 class="h">{{ $ticket->title }} <span class="muted">({{ $ticket->folio }})</span></h1>
  <p class="muted">Paso {{ $stage->position }}: <strong>{{ $stage->name }}</strong> — Estado: {{ ucfirst($stage->status) }}</p>

  <div class="grid">
    {{-- Columna izquierda: actividades/checklist --}}
    <div class="card">
      <x-ticket.step :ticket="$ticket" :stage="$stage" />
      <hr>
      <x-ticket.checklist :stage="$stage" />
    </div>

    {{-- Columna derecha: evidencias en tiempo real --}}
    <div class="card">
      <h3 class="h" style="font-weight:800">Evidencias</h3>
      <form method="POST" action="{{ route('tickets.wizard.evidence',[$ticket,$stage]) }}" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file" required>
        <button class="btn">Subir</button>
      </form>

      <ul id="evidences" style="margin-top:10px">
        @foreach($ticket->documents as $d)
          <li>📎 {{ $d->name }} — <a href="{{ route('tickets.documents.download',[$ticket,$d]) }}">Descargar</a></li>
        @endforeach
      </ul>
    </div>
  </div>
</div>

{{-- Echo para tiempo real: escucha actualizaciones del ticket --}}
@auth
  <script src="https://js.pusher.com/8.2/pusher.min.js"></script>
  <script>
    // si ya usas Echo, puedes sustituir por window.Echo.private(`tickets.${{ $ticket->id }}`)
    const pusher = new Pusher("{{ env('PUSHER_APP_KEY','app-key') }}", {cluster:"{{ env('PUSHER_APP_CLUSTER','mt1') }}", authEndpoint:"/broadcasting/auth"});
    pusher.subscribe('private-tickets.{{ $ticket->id }}')
      .bind('TicketStageUpdated', function(data){
        // Opcional: recargar o pedir via AJAX fragmentos (evidencias / checklist)
        location.reload();
      });
  </script>
@endauth
@endsection
