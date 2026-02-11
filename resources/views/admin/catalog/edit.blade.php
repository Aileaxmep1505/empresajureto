@extends('layouts.app')
@section('title','Editar producto web')

@push('styles')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400..700&display=swap"/>

<style>
  :root{
    --ink:#0e1726;
    --muted:#6b7280;
    --line:#e5e7eb;
    --card:#ffffff;

    --blue:#6ea8fe;
    --blue-ink:#0b1220;

    --green:#22c55e;
    --green-soft: rgba(34,197,94,.14);
    --green-ink:#065f46;

    --amber:#f59e0b;
    --amber-soft: rgba(245,158,11,.16);
    --amber-ink:#92400e;

    --red:#ef4444;

    --shadow:0 12px 30px rgba(13,23,38,.06);
    --r:16px;
    --rbtn:12px;
  }

  .wrap-page{max-width:1100px;margin-inline:auto;padding:10px 0;}

  .btn{
    display:inline-flex;align-items:center;gap:8px;
    border-radius:var(--rbtn);
    padding:9px 14px;
    font-weight:700;
    border:1px solid var(--line);
    background:#fff;
    cursor:pointer;
    text-decoration:none;
    font-size:.9rem;
    transition:transform .12s ease, box-shadow .12s ease, background .15s ease, border-color .15s ease, opacity .15s ease;
    white-space:nowrap;
  }
  .btn:hover{transform:translateY(-1px);box-shadow:0 10px 22px rgba(15,23,42,.08);}
  .btn:active{transform:translateY(0);box-shadow:none;}
  .btn[disabled], .btn[aria-disabled="true"]{opacity:.55;cursor:not-allowed;pointer-events:none;}

  .btn-primary{
    background:var(--blue);
    border-color:var(--blue);
    color:var(--blue-ink);
    box-shadow:0 10px 22px rgba(37,99,235,.16);
  }
  .btn-primary:hover{box-shadow:0 14px 26px rgba(37,99,235,.18);}

  .btn-ghost{
    background:#fff;
    border-color:var(--line);
    color:#111827;
  }

  .btn-danger{
    background:var(--red);
    border-color:var(--red);
    color:#fff;
    box-shadow:0 10px 22px rgba(239,68,68,.16);
  }
  .btn-danger:hover{box-shadow:0 14px 26px rgba(239,68,68,.18);}

  .btn-soft{
    background:#fff;
    border-color:var(--line);
    color:#111827;
  }

  .btn-ml{
    background:linear-gradient(135deg, rgba(16,185,129,.18), rgba(16,185,129,.08));
    color:var(--green-ink);
    border:1px solid rgba(16,185,129,.35);
    box-shadow:0 10px 22px rgba(16,185,129,.14);
  }
  .btn-ml:hover{box-shadow:0 14px 26px rgba(16,185,129,.18);}

  .btn-ml-soft{
    background:rgba(16,185,129,.10);
    color:var(--green-ink);
    border:1px solid rgba(16,185,129,.25);
  }

  .btn-amz{
    background:linear-gradient(135deg, rgba(245,158,11,.22), rgba(245,158,11,.10));
    color:var(--amber-ink);
    border:1px solid rgba(245,158,11,.35);
    box-shadow:0 10px 22px rgba(245,158,11,.14);
  }
  .btn-amz:hover{box-shadow:0 14px 26px rgba(245,158,11,.18);}

  .btn-amz-soft{
    background:rgba(245,158,11,.12);
    color:var(--amber-ink);
    border:1px solid rgba(245,158,11,.25);
  }

  .i.material-symbols-outlined{font-size:18px;line-height:1;}

  .alert{
    padding:10px 12px;border-radius:12px;border:1px solid var(--line);
    margin-bottom:12px;font-size:.9rem;background:#fff;
  }
  .alert-success{background:#f8fffb;color:#0b6b3a;border-color:rgba(34,197,94,.20);}
  .alert-error{background:#fff4f4;color:#b91c1c;border-color:rgba(239,68,68,.25);}
  .alert-ml{background:#fbfeff;color:#0b4b6b;border-color:var(--line);}
  .alert-amz{background:#fffaf2;color:#6b3d0b;border-color:rgba(245,158,11,.28);}

  .head{
    display:flex;justify-content:space-between;align-items:flex-start;
    gap:12px;flex-wrap:wrap;margin:14px 0 10px;
  }
  .head h1{font-weight:800;margin:0;color:#0b1220;font-size:1.15rem;}
  .head p{margin:4px 0 0;font-size:.9rem;color:var(--muted);}

  .actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;align-items:center;}

  .card{
    background:var(--card);
    border:1px solid #e8eef6;
    border-radius:var(--r);
    box-shadow:var(--shadow);
    padding:16px;
  }

  @media (max-width: 720px){
    .btn{width:100%;justify-content:center;}
    .actions{width:100%;}
  }
</style>
@endpush

@section('content')
@php
  use Illuminate\Support\Str;

  // Bandera simple: Amazon requiere SKU para operar
  $hasSku = !empty($item->sku ?? null);

  $mlErrText  = Str::lower((string)($item->meli_last_error ?? ''));
  $amzErrText = Str::lower((string)($item->amz_last_error ?? '')); // opcional si existe en tu BD
@endphp

<div class="wrap-page">
  <div class="head">
    <div>
      <h1>Editar: {{ $item->name }}</h1>
      <p>Ajusta la información del producto y revisa el estado de sincronización con Mercado Libre y Amazon.</p>
    </div>

    <div class="actions">
      <a class="btn btn-ghost" href="{{ route('admin.catalog.index') }}">
        <span class="i material-symbols-outlined" aria-hidden="true">arrow_back</span>
        Volver
      </a>

      {{-- Toggle publicar/ocultar (WEB) --}}
      <form action="{{ route('admin.catalog.toggle', $item) }}" method="POST"
            onsubmit="return confirm('¿Cambiar estado de publicación en el sitio web?')">
        @csrf @method('PATCH')
        <button class="btn btn-ghost" type="submit">
          <span class="i material-symbols-outlined" aria-hidden="true">{{ $item->status == 1 ? 'visibility_off' : 'visibility' }}</span>
          {{ $item->status == 1 ? 'Ocultar' : 'Publicar' }}
        </button>
      </form>

      {{-- Mercado Libre: acciones rápidas --}}
      <form method="POST" action="{{ route('admin.catalog.meli.publish', $item) }}">
        @csrf
        <button class="btn btn-ml" type="submit">
          <span class="i material-symbols-outlined" aria-hidden="true">cloud_upload</span>
          ML: Publicar/Actualizar
        </button>
      </form>

      @if($item->meli_item_id)
        <form method="POST" action="{{ route('admin.catalog.meli.pause', $item) }}">
          @csrf
          <button class="btn btn-ml-soft" type="submit">
            <span class="i material-symbols-outlined" aria-hidden="true">pause_circle</span>
            ML: Pausar
          </button>
        </form>
        <form method="POST" action="{{ route('admin.catalog.meli.activate', $item) }}">
          @csrf
          <button class="btn btn-ml-soft" type="submit">
            <span class="i material-symbols-outlined" aria-hidden="true">play_circle</span>
            ML: Activar
          </button>
        </form>
        <a class="btn btn-ml-soft" href="{{ route('admin.catalog.meli.view', $item) }}" target="_blank" rel="noopener">
          <span class="i material-symbols-outlined" aria-hidden="true">open_in_new</span>
          ML: Ver
        </a>
      @endif

      {{-- Amazon: acciones rápidas (SP-API) --}}
      <form method="POST" action="{{ route('admin.catalog.amazon.publish', $item) }}">
        @csrf
        <button class="btn btn-amz" type="submit" @disabled(!$hasSku)>
          <span class="i material-symbols-outlined" aria-hidden="true">cloud_upload</span>
          Amazon: Publicar/Actualizar
        </button>
      </form>

      <form method="POST" action="{{ route('admin.catalog.amazon.pause', $item) }}">
        @csrf
        <button class="btn btn-amz-soft" type="submit" @disabled(!$hasSku)>
          <span class="i material-symbols-outlined" aria-hidden="true">pause_circle</span>
          Amazon: Pausar
        </button>
      </form>

      <form method="POST" action="{{ route('admin.catalog.amazon.activate', $item) }}">
        @csrf
        <button class="btn btn-amz-soft" type="submit" @disabled(!$hasSku)>
          <span class="i material-symbols-outlined" aria-hidden="true">play_circle</span>
          Amazon: Activar
        </button>
      </form>

      <a class="btn btn-amz-soft"
         href="{{ route('admin.catalog.amazon.view', $item) }}"
         target="_blank" rel="noopener"
         @if(!$hasSku) aria-disabled="true" onclick="return false;" @endif>
        <span class="i material-symbols-outlined" aria-hidden="true">open_in_new</span>
        Amazon: Ver
      </a>

      {{-- Eliminar --}}
      <form action="{{ route('admin.catalog.destroy', $item) }}" method="POST"
            onsubmit="return confirm('¿Eliminar este producto del catálogo web? Esta acción no se puede deshacer.')">
        @csrf @method('DELETE')
        <button class="btn btn-danger" type="submit">
          <span class="i material-symbols-outlined" aria-hidden="true">delete</span>
          Eliminar
        </button>
      </form>
    </div>
  </div>

  {{-- Banner de resultado/errores global --}}
  @if(session('ok'))
    <div class="alert alert-success">
      {{ session('ok') }}
    </div>
  @endif

  {{-- Advertencia SKU para Amazon --}}
  @if(!$hasSku)
    <div class="alert alert-amz">
      <strong>Amazon:</strong> Para usar las acciones de Amazon necesitas un <strong>SKU</strong>.
      Guarda el producto con un SKU y vuelve a intentar.
    </div>
  @endif

  {{-- Panel de estado Mercado Libre --}}
  @if($item->meli_item_id || $item->meli_last_error)
    <div class="alert alert-ml">
      <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div style="flex:1 1 260px;">
          <strong>Mercado Libre</strong><br>
          @if($item->meli_item_id)
            <span style="font-size:.88rem;">
              ID: {{ $item->meli_item_id }} ·
              Estado:
              @if($item->meli_status === 'active')
                <span style="font-weight:800;color:#166534;">Activo</span>
              @elseif($item->meli_status === 'paused')
                <span style="font-weight:800;color:#854d0e;">Pausado</span>
              @elseif($item->meli_status === 'error')
                <span style="font-weight:800;color:#b91c1c;">Error</span>
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

        <div style="flex:1 1 260px;font-size:.86rem;color:#4b5563;">
          <strong>Qué revisar en este producto:</strong>
          <ul style="margin:4px 0 0 18px;padding:0;">
            @if(Str::contains($mlErrText, 'gtin'))
              <li>Completa <strong>GTIN / código de barras</strong>. Es obligatorio para esta categoría.</li>
            @endif
            @if(Str::contains($mlErrText, 'title') || Str::contains($mlErrText, 'título'))
              <li>Mejora el <strong>Nombre</strong>: incluye tipo de producto, marca y modelo.</li>
            @endif
            @if(Str::contains($mlErrText, 'price'))
              <li>Ajusta el <strong>Precio</strong> para cumplir mínimos de la categoría.</li>
            @endif
            <li>Verifica que existan <strong>imágenes</strong> (tus 3 fotos).</li>
            <li>Después de corregir, pulsa <strong>“ML: Publicar/Actualizar”</strong>.</li>
          </ul>
        </div>
      </div>

      @if($item->meli_last_error)
        <div style="color:#b91c1c;margin-top:8px;white-space:normal;font-size:.86rem;">
          <strong>Mensaje técnico de Mercado Libre:</strong><br>
          {{ $item->meli_last_error }}
        </div>
      @endif
    </div>
  @endif

  {{-- Panel de estado Amazon (si tienes campos en BD; si no existen, no rompe) --}}
  @if(!empty($item->amz_sku ?? null) || !empty($item->amz_last_error ?? null) || !empty($item->amz_synced_at ?? null))
    <div class="alert alert-amz">
      <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div style="flex:1 1 260px;">
          <strong>Amazon</strong><br>
          <span style="font-size:.88rem;">
            SKU: {{ $item->sku ?? ($item->amz_sku ?? '—') }} ·
            Estado:
            @php $amzStatus = $item->amz_status ?? null; @endphp
            @if($amzStatus === 'active')
              <span style="font-weight:800;color:#166534;">Activo</span>
            @elseif($amzStatus === 'inactive' || $amzStatus === 'paused')
              <span style="font-weight:800;color:#854d0e;">Pausado</span>
            @elseif($amzStatus === 'error')
              <span style="font-weight:800;color:#b91c1c;">Error</span>
            @else
              <span>{{ $amzStatus ?: '—' }}</span>
            @endif
          </span>

          @if(!empty($item->amz_synced_at ?? null))
            <div style="margin-top:4px;font-size:.8rem;color:#6b7280;">
              Última sincronización: {{ \Carbon\Carbon::parse($item->amz_synced_at)->format('Y-m-d H:i') }}
            </div>
          @endif
        </div>

        <div style="flex:1 1 260px;font-size:.86rem;color:#4b5563;">
          <strong>Qué revisar para Amazon:</strong>
          <ul style="margin:4px 0 0 18px;padding:0;">
            @if(Str::contains($amzErrText, 'sku'))
              <li>Confirma que el <strong>SKU</strong> esté lleno y sea único.</li>
            @endif
            @if(Str::contains($amzErrText, 'gtin') || Str::contains($amzErrText, 'upc') || Str::contains($amzErrText, 'ean'))
              <li>Completa <strong>GTIN (EAN/UPC)</strong> si tu productType lo exige.</li>
            @endif
            @if(Str::contains($amzErrText, 'image') || Str::contains($amzErrText, 'imagen'))
              <li>Verifica que existan imágenes (tus 3 fotos).</li>
            @endif
            <li>Después de corregir, pulsa <strong>“Amazon: Publicar/Actualizar”</strong>.</li>
          </ul>
        </div>
      </div>

      @if(!empty($item->amz_last_error ?? null))
        <div style="color:#b91c1c;margin-top:8px;white-space:normal;font-size:.86rem;">
          <strong>Mensaje técnico de Amazon:</strong><br>
          {{ $item->amz_last_error }}
        </div>
      @endif
    </div>
  @endif

  {{-- Errores de validación de formulario --}}
  @if($errors->any())
    <div class="alert alert-error">
      <strong>Revisa estos campos antes de guardar:</strong>
      <ul style="margin:6px 0 0 18px;">
        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <form class="card" action="{{ route('admin.catalog.update', $item) }}" method="POST" enctype="multipart/form-data">
    @include('admin.catalog._form', ['item' => $item])
  </form>
</div>
@endsection
