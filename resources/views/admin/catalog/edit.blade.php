@extends('layouts.app')
@section('title','Editar producto web')

@section('content')
<div class="wrap" style="max-width:1100px;margin-inline:auto;">
  <div class="head" style="display:flex;justify-content:space-between;align-items:center;margin:14px 0 10px;">
    <h1 style="font-weight:800;margin:0;">Editar: {{ $item->name }}</h1>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <a class="btn btn-ghost" href="{{ route('admin.catalog.index') }}">← Volver</a>

      {{-- Toggle publicar/ocultar --}}
      <form action="{{ route('admin.catalog.toggle', $item) }}" method="POST"
            onsubmit="return confirm('¿Cambiar estado de publicación?')">
        @csrf @method('PATCH')
        <button class="btn btn-ghost" type="submit">
          {{ $item->status == 1 ? 'Ocultar' : 'Publicar' }}
        </button>
      </form>

      {{-- Mercado Libre: acciones rápidas --}}
      <form method="POST" action="{{ route('admin.catalog.meli.publish', $item) }}">
        @csrf
        <button class="btn btn-primary" type="submit">ML: Publicar/Actualizar</button>
      </form>

      @if($item->meli_item_id)
        <form method="POST" action="{{ route('admin.catalog.meli.pause', $item) }}">
          @csrf
          <button class="btn btn-ghost" type="submit">ML: Pausar</button>
        </form>
        <form method="POST" action="{{ route('admin.catalog.meli.activate', $item) }}">
          @csrf
          <button class="btn btn-ghost" type="submit">ML: Activar</button>
        </form>
        @if($item->meli_item_id)
  <a class="btn btn-ghost" href="{{ route('admin.catalog.meli.view', $item) }}">ML: Ver</a>
@endif

      @endif

      {{-- Eliminar --}}
      <form action="{{ route('admin.catalog.destroy', $item) }}" method="POST"
            onsubmit="return confirm('¿Eliminar este producto?')">
        @csrf @method('DELETE')
        <button class="btn" style="background:#ef4444;color:#fff;">Eliminar</button>
      </form>
    </div>
  </div>

  {{-- Banner de resultado/errores --}}
  @if(session('ok'))
    <div class="alert" style="padding:10px 12px;border:1px solid #e8eef6;border-radius:12px;background:#f8fffb;color:#0b6b3a;margin-bottom:12px;">
      {{ session('ok') }}
    </div>
  @endif

  @if($item->meli_item_id || $item->meli_last_error)
    <div class="alert" style="padding:10px 12px;border:1px solid #e8eef6;border-radius:12px;background:#fbfeff;color:#0b4b6b;margin-bottom:12px;">
      <strong>Mercado Libre</strong>:
      @if($item->meli_item_id)
        ID: {{ $item->meli_item_id }} · Estado: {{ $item->meli_status ?: '—' }}
      @else
        Sin publicación en ML.
      @endif
      @if($item->meli_last_error)
        <div style="color:#b91c1c;margin-top:6px;white-space:normal;">Último error: {{ $item->meli_last_error }}</div>
      @endif
    </div>
  @endif

  {{-- Errores de validación --}}
  @if($errors->any())
    <div class="alert" style="padding:10px 12px;border:1px solid #e8eef6;border-radius:12px;background:#fff4f4;color:#991b1b;margin-bottom:12px;">
      <strong>Corrige los siguientes campos:</strong>
      <ul style="margin:6px 0 0 18px;">
        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <form class="card" style="background:#fff;border:1px solid #e8eef6;border-radius:16px;box-shadow:0 12px 30px rgba(13,23,38,.06);padding:16px;"
        action="{{ route('admin.catalog.update', $item) }}" method="POST">
    @include('admin.catalog._form', ['item' => $item])
  </form>
</div>
@endsection
