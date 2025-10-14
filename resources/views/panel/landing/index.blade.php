@extends('layouts.app')
@section('title','Secciones de Inicio')

@section('content')
<div class="container">
  @if(session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Secciones</h2>
    <a class="btn btn-primary" href="{{ route('panel.landing.create') }}">Nueva sección</a>
  </div>

  <div class="row">
    @forelse($sections as $s)
      <div class="col-md-6 mb-3">
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <strong>{{ $s->name }}</strong>
                <div class="text-muted small">{{ $s->layout }} · {{ $s->items_count }} items</div>
                <div class="small">{{ $s->is_active ? 'Activo' : 'Inactivo' }}</div>
              </div>
              <div class="text-end">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('panel.landing.edit',$s) }}">Editar</a>
                <form action="{{ route('panel.landing.toggle',$s) }}" method="POST" class="d-inline">@csrf
                  <button class="btn btn-sm btn-outline-warning">{{ $s->is_active ? 'Desactivar' : 'Activar' }}</button>
                </form>
                <form action="{{ route('panel.landing.destroy',$s) }}" method="POST" class="d-inline" onsubmit="return confirm('Eliminar sección?');">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12"><div class="alert alert-info">Sin secciones.</div></div>
    @endforelse
  </div>
</div>
@endsection
