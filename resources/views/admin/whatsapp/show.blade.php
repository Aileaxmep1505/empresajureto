@extends('layouts.app')
@section('title', 'WhatsApp · Conversación #'.$conversation->id)

@section('content')
<div class="container py-4">
  <h1 class="mb-3">Conversación #{{ $conversation->id }}</h1>

  @if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
  @endif

  @if(session('err'))
    <div class="alert alert-danger">{{ session('err') }}</div>
  @endif

  <div class="card mb-3">
    <div class="card-body">
      <div><strong>Teléfono:</strong> {{ $conversation->phone }}</div>
      <div><strong>Usuario:</strong> {{ $conversation->user->name ?? 'Sin vincular' }}</div>
      <div><strong>Estado:</strong> {{ $conversation->status }}</div>
      <div><strong>Agente:</strong> {{ $conversation->agent->name ?? '—' }}</div>
    </div>
  </div>

  <div class="d-flex gap-2 mb-3">
    <form method="POST" action="{{ route('admin.whatsapp.conversations.take', $conversation) }}">
      @csrf
      <button class="btn btn-warning">Tomar conversación</button>
    </form>

    <form method="POST" action="{{ route('admin.whatsapp.conversations.close', $conversation) }}">
      @csrf
      <button class="btn btn-secondary">Cerrar</button>
    </form>
  </div>

  <div class="card mb-3">
    <div class="card-body" style="max-height: 500px; overflow:auto;">
      @forelse($conversation->messages as $m)
        <div class="mb-3 p-3 border rounded">
          <div class="small text-muted mb-1">
            {{ strtoupper($m->direction) }} · {{ $m->message_type }} · {{ $m->status }} · {{ $m->created_at?->format('d/m/Y h:i A') }}
          </div>
          <div>{{ $m->text ?: '—' }}</div>
        </div>
      @empty
        <div class="text-muted">Sin mensajes.</div>
      @endforelse
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('admin.whatsapp.conversations.reply', $conversation) }}">
        @csrf
        <div class="mb-3">
          <label class="form-label">Responder</label>
          <textarea name="text" class="form-control" rows="4" required></textarea>
        </div>
        <button class="btn btn-primary">Enviar respuesta</button>
      </form>
    </div>
  </div>
</div>
@endsection