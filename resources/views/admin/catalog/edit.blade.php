@extends('layouts.app')
@section('title','Editar producto web')

@section('content')
<div class="wrap" style="max-width:1100px;margin-inline:auto;">
  <div class="head" style="display:flex;justify-content:space-between;align-items:center;margin:14px 0 10px;">
    <div>
      <h1 style="font-weight:800;margin:0;">Editar: {{ $item->name }}</h1>
      <p style="margin:4px 0 0;font-size:.9rem;color:#6b7280;">
        Ajusta la información del producto y revisa el estado de la sincronización con Mercado Libre.
      </p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <a class="btn btn-ghost" href="{{ route('admin.catalog.index') }}">← Volver</a>

      {{-- Toggle publicar/ocultar --}}
      <form action="{{ route('admin.catalog.toggle', $item) }}" method="POST"
            onsubmit="return confirm('¿Cambiar estado de publicación en el sitio web?')">
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
        <a class="btn btn-ghost" href="{{ route('admin.catalog.meli.view', $item) }}">ML: Ver</a>
      @endif

      {{-- Eliminar --}}
      <form action="{{ route('admin.catalog.destroy', $item) }}" method="POST"
            onsubmit="return confirm('¿Eliminar este producto del catálogo web? Esta acción no se puede deshacer.')">
        @csrf @method('DELETE')
        <button class="btn" style="background:#ef4444;color:#fff;">Eliminar</button>
      </form>
    </div>
  </div>

  {{-- Banner de resultado/errores global --}}
  @if(session('ok'))
    <div class="alert" style="padding:10px 12px;border:1px solid #e8eef6;border-radius:12px;background:#f8fffb;color:#0b6b3a;margin-bottom:12px;font-size:.9rem;">
      {{ session('ok') }}
    </div>
  @endif

  {{-- Panel de estado Mercado Libre --}}
  @if($item->meli_item_id || $item->meli_last_error)
    <div class="alert" style="padding:12px 14px;border:1px solid #e8eef6;border-radius:14px;background:#fbfeff;color:#0b4b6b;margin-bottom:12px;font-size:.9rem;">
      <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div style="flex:1 1 260px;">
          <strong>Mercado Libre</strong><br>
          @if($item->meli_item_id)
            <span style="font-size:.88rem;">
              ID: {{ $item->meli_item_id }} ·
              Estado:
              @if($item->meli_status === 'active')
                <span style="font-weight:700;color:#166534;">Activo</span>
              @elseif($item->meli_status === 'paused')
                <span style="font-weight:700;color:#854d0e;">Pausado</span>
              @elseif($item->meli_status === 'error')
                <span style="font-weight:700;color:#b91c1c;">Error</span>
              @else
                <span>{{ $item->meli_status ?: '—' }}</span>
              @endif
            </span>
          @else
            <span style="font-size:.88rem;">Sin publicación en Mercado Libre.</span>
          @endif

          @if($item->meli_synced_at)
            <div style="margin-top:4px;font-size:.8rem;color:#6b7280;">
              Última sincronización: {{ $item->meli_synced_at->format('Y-m-d H:i') }}
            </div>
          @endif
        </div>

        <div style="flex:1 1 260px;">
          <div style="font-size:.86rem;color:#4b5563;">
            <strong>Consejos rápidos</strong>
            <ul style="margin:4px 0 0 18px;padding:0;">
              <li>Asegúrate de que el <strong>Nombre</strong> incluya tipo de producto, marca y modelo.</li>
              <li>Verifica que el <strong>Precio</strong> sea suficiente para la categoría (Ej.: mínimo 35 MXN en algunas categorías).</li>
              <li>Si la publicación está cerrada, vuelve a publicar desde el botón “ML: Publicar/Actualizar”.</li>
            </ul>
          </div>
        </div>
      </div>

      @if($item->meli_last_error)
        <div style="color:#b91c1c;margin-top:8px;white-space:normal;font-size:.86rem;">
          <strong>Último error detectado:</strong><br>
          {{ $item->meli_last_error }}
        </div>
      @endif
    </div>
  @endif

  {{-- Errores de validación de formulario --}}
  @if($errors->any())
    <div class="alert" style="padding:10px 12px;border:1px solid #e8eef6;border-radius:12px;background:#fff4f4;color:#991b1b;margin-bottom:12px;font-size:.9rem;">
      <strong>Revisa estos campos antes de guardar:</strong>
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
