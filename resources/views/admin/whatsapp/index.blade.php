@extends('layouts.app')
@section('title', 'WhatsApp · Conversaciones')

@section('content')
<div class="container py-4">
  <h1 class="mb-4">Conversaciones WhatsApp</h1>

  @if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
  @endif

  <div class="card">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Teléfono</th>
            <th>Usuario</th>
            <th>Estado</th>
            <th>Agente</th>
            <th>Mensajes</th>
            <th>Último mensaje</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($conversations as $c)
            <tr>
              <td>{{ $c->id }}</td>
              <td>{{ $c->phone }}</td>
              <td>{{ $c->user->name ?? 'Sin vincular' }}</td>
              <td>{{ $c->status }}</td>
              <td>{{ $c->agent->name ?? '—' }}</td>
              <td>{{ $c->messages_count }}</td>
              <td>{{ optional($c->last_message_at)->format('d/m/Y h:i A') }}</td>
              <td>
                <a href="{{ route('admin.whatsapp.conversations.show', $c) }}" class="btn btn-sm btn-primary">Abrir</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-4">Sin conversaciones.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    {{ $conversations->links() }}
  </div>
</div>
@endsection