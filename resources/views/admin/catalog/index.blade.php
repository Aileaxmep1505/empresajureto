@extends('layouts.app')
@section('title','Productos Web')

@push('styles')
<style>
  :root{
    --ink:#0e1726; --muted:#6b7280; --bg:#f7fafc;
    --brand:#6ea8fe; --accent:#9ae6b4; --danger:#fecaca; --line:#e8eef6;
    --surface:#ffffff; --chip:#eef2ff; --chip-ink:#3730a3;
    --shadow:0 12px 30px rgba(13, 23, 38, .06);
    --radius:16px;
  }
  html,body{background:var(--bg)}
  .wrap{max-width:1200px; margin-inline:auto;}
  .card{background:var(--surface); border:1px solid var(--line); border-radius:var(--radius); box-shadow:var(--shadow);}
  .head{display:flex; gap:12px; align-items:center; justify-content:space-between; flex-wrap:wrap;}
  .title{font-weight:800; color:var(--ink); letter-spacing:-.01em; margin:0}
  .muted{color:var(--muted)}
  .btn{display:inline-flex; align-items:center; gap:10px; border:0; cursor:pointer; text-decoration:none; font-weight:700; border-radius:12px; padding:10px 14px; transition:.15s transform,.15s background,.15s box-shadow;}
  .btn:hover{transform:translateY(-1px)}
  .btn-primary{background:var(--brand); color:#0b1220; box-shadow:0 8px 18px rgba(29,78,216,.12);}
  .btn-ghost{background:#fff; color:var(--ink); border:1px solid var(--line)}
  .btn-danger{background:#ef4444; color:#fff}
  .btn-small{padding:8px 12px; font-size:.92rem; border-radius:10px}
  .toolbar{display:flex; gap:10px; align-items:center; flex-wrap:wrap}
  .input, .select{
    background:#fff; border:1px solid var(--line); border-radius:12px; padding:10px 12px;
    outline:0; min-height:40px; color:var(--ink);
  }
  .checkbox{width:18px; height:18px}
  .table-wrap{overflow:auto; border-radius:14px; border:1px solid var(--line)}
  table{width:100%; border-collapse:collapse; font-size:.95rem; background:#fff}
  th, td{padding:12px 12px; border-bottom:1px solid var(--line); vertical-align:middle; white-space:nowrap;}
  th{font-weight:800; text-align:left; color:var(--ink); background:#fbfdff}
  tr:hover td{background:#fcfdfd}
  .thumb{width:56px; height:56px; border-radius:10px; object-fit:cover; border:1px solid var(--line); background:#f6f8fc}
  .badge{display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; font-weight:700; font-size:.78rem;}
  .badge-live{background:#dcfce7; color:#166534}
  .badge-draft{background:#f1f5f9; color:#334155}
  .badge-hidden{background:#fee2e2; color:#991b1b}
  .chip{background:var(--chip); color:var(--chip-ink); border-radius:999px; padding:6px 10px; font-weight:700; font-size:.78rem}
  .price{font-weight:800; color:var(--ink)}
  .sale{color:#16a34a; font-weight:800}
  .sku{color:var(--muted); font-size:.85rem}
  .actions{display:flex; gap:8px; flex-wrap:wrap}
  .sticky-tools{position:sticky; top:0; z-index:5; background:linear-gradient(#ffffff, #ffffffcc 60%, transparent); padding-bottom:6px}
  .alert{padding:10px 12px; border-radius:12px; border:1px solid var(--line); background:#f8fffb; color:#0b6b3a; font-size:.9rem}

  .alert-ml-summary{
    margin:8px 0 16px;
    display:flex;
    gap:12px;
    align-items:flex-start;
    padding:12px 14px;
    border-radius:14px;
    border:1px solid #fee2e2;
    background:#fef2f2;
    color:#991b1b;
    font-size:.9rem;
  }
  .alert-ml-summary-title{font-weight:700; margin-bottom:4px;}
  .alert-ml-summary ul{margin:4px 0 0 18px; padding:0; font-size:.86rem;}

  .ml-pill-error{
    display:inline-flex;
    align-items:center;
    padding:4px 10px;
    border-radius:999px;
    background:#fee2e2;
    color:#991b1b;
    font-size:.75rem;
    font-weight:700;
    margin-top:4px;
  }
  .ml-error-text{
    margin-top:4px;
    font-size:.78rem;
    color:#991b1b;
    white-space:normal;
    max-width:560px;
  }
  .ml-error-hints{
    margin:4px 0 0;
    padding:0;
    list-style:disc;
    padding-left:16px;
    font-size:.76rem;
    color:#6b7280;
  }

  @media (max-width: 780px){
    th:nth-child(5), td:nth-child(5),
    th:nth-child(7), td:nth-child(7){ display:none; }
    .alert-ml-summary{flex-direction:column;}
  }
</style>
@endpush

@section('content')
@php
  use Illuminate\Support\Str;
@endphp

<div class="wrap">
  <div class="head" style="margin: 14px 0 10px;">
    <div>
      <h1 class="title">Productos Web <span class="muted" style="font-weight:600;">(Catálogo público)</span></h1>
      <p class="muted" style="margin-top:4px;font-size:.9rem;">
        Gestiona el catálogo que se muestra en tu sitio y sincronízalo con Mercado Libre desde un solo lugar.
      </p>
    </div>
    <div class="toolbar">
      <a href="{{ route('admin.catalog.create') }}" class="btn btn-primary">+ Nuevo producto</a>
    </div>
  </div>

  @if(session('ok'))
    <div class="alert" style="margin:10px 0 12px;">
      {{ session('ok') }}
    </div>
  @endif

  {{-- Resumen general de errores de ML en la página --}}
  @php
    $firstMeliErrorItem = $items->first(function ($row) {
        return !empty($row->meli_last_error);
    });
  @endphp

  @if($firstMeliErrorItem)
    <div class="alert-ml-summary">
      <div>
        <div class="alert-ml-summary-title">Algunos productos no se pudieron sincronizar con Mercado Libre.</div>
        <p style="margin:0 0 4px;">
          Revisa la columna de “Nombre” para ver el detalle del error en cada producto y ajusta los campos sugeridos.
        </p>
        <ul>
          <li><strong>Título demasiado corto o genérico:</strong> incluye tipo de producto, marca y modelo (ejemplo: “Lapicero bolígrafo azul Bic 0.7mm”).</li>
          <li><strong>Precio insuficiente:</strong> algunas categorías exigen un precio mínimo. Si el mensaje menciona un monto, ajusta el precio para superarlo.</li>
          <li><strong>Falta GTIN / código de barras:</strong> en varias categorías es obligatorio. Captura el GTIN en la edición del producto.</li>
          <li><strong>Publicación cerrada:</strong> si Mercado Libre ya cerró la publicación, será necesario crear una nueva desde este panel.</li>
        </ul>
      </div>
    </div>
  @endif

  {{-- ======= Filtros / Buscador ======= --}}
  <div class="card sticky-tools" style="padding:12px;">
    <form method="GET" class="toolbar" action="{{ route('admin.catalog.index') }}">
      <input class="input" type="search" name="s" placeholder="Buscar por nombre o SKU…" value="{{ request('s') }}" />
      <select class="select" name="status" aria-label="Filtro de estado">
        <option value="">Estado: todos</option>
        <option value="1" @selected(request('status')==='1')>Publicado</option>
        <option value="0" @selected(request('status')==='0')>Borrador</option>
        <option value="2" @selected(request('status')==='2')>Oculto</option>
      </select>

      <label style="display:flex; align-items:center; gap:8px;" class="muted">
        <input class="checkbox" type="checkbox" name="featured_only" value="1" @checked(request()->boolean('featured_only'))>
        Solo destacados
      </label>

      <button class="btn btn-ghost btn-small" type="submit">Aplicar</button>
      @if(request()->hasAny(['s','status','featured_only']))
        <a class="btn btn-ghost btn-small" href="{{ route('admin.catalog.index') }}">Limpiar</a>
      @endif
    </form>
  </div>

  {{-- ======= Tabla ======= --}}
  <div class="table-wrap card" style="margin-top:12px;">
    <table>
      <thead>
        <tr>
          <th style="width:64px;">Img</th>
          <th>Nombre</th>
          <th>Precio</th>
          <th>Estado</th>
          <th>Destacado</th>
          <th>Publicado</th>
          <th style="text-align:right;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $it)
          <tr>
            <td>
              @php $src = $it->image_url; @endphp
              @if($src)
                <img class="thumb" src="{{ $src }}" alt="Imagen de {{ $it->name }}"
                     onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}';">
              @else
                <img class="thumb" src="{{ asset('images/placeholder.png') }}" alt="Sin imagen">
              @endif
            </td>

            <td>
              <div style="display:flex; flex-direction:column; gap:4px;">
                <strong style="color:var(--ink)">{{ $it->name }}</strong>
                <span class="sku">SKU: {{ $it->sku ?: '—' }}</span>
                <span class="muted" style="font-size:.82rem;">Slug: {{ $it->slug }}</span>

                {{-- Info de Mercado Libre --}}
                @if($it->meli_item_id || $it->meli_status)
                  <div class="muted" style="font-size:.82rem;display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                    <span>ML ID: {{ $it->meli_item_id ?: '—' }}</span>
                    @if($it->meli_status === 'active')
                      <span class="badge badge-live">ML: Activo</span>
                    @elseif($it->meli_status === 'paused')
                      <span class="badge badge-draft" style="background:#fef9c3;color:#854d0e;">ML: Pausado</span>
                    @elseif($it->meli_status === 'error')
                      <span class="badge badge-hidden">ML: Error</span>
                    @elseif($it->meli_status)
                      <span class="badge badge-draft">ML: {{ ucfirst($it->meli_status) }}</span>
                    @endif
                  </div>
                @endif

                @if($it->meli_last_error)
                  @php
                    $errText = Str::lower($it->meli_last_error);
                  @endphp
                  <div class="ml-pill-error">
                    Problema al sincronizar con Mercado Libre
                  </div>
                  <div class="ml-error-text">
                    {{ $it->meli_last_error }}
                  </div>
                  <ul class="ml-error-hints">
                    @if(Str::contains($errText, 'gtin'))
                      <li>El mensaje indica que falta el <strong>GTIN/código de barras</strong>. Edita el producto y captura el código impreso en el empaque.</li>
                    @endif

                    @if(Str::contains($errText, 'title') || Str::contains($errText, 'título'))
                      <li>El <strong>Nombre</strong> es demasiado corto o genérico. Incluye tipo de producto, marca y modelo (por ejemplo: “Lapicero bolígrafo azul Bic 0.7mm”).</li>
                    @endif

                    @if(Str::contains($errText, 'price'))
                      <li>El <strong>Precio</strong> está por debajo del mínimo de la categoría. Sube el precio hasta el mínimo que menciona el mensaje.</li>
                    @endif

                    <li>Después de corregir, vuelve a pulsar <strong>“ML: Publicar/Actualizar”</strong> para reintentar el envío.</li>
                  </ul>
                @endif
              </div>
            </td>

            <td>
              @if(!is_null($it->sale_price))
                <div class="sale">${{ number_format($it->sale_price,2) }}</div>
                <div class="muted" style="text-decoration:line-through;">${{ number_format($it->price,2) }}</div>
              @else
                <div class="price">${{ number_format($it->price,2) }}</div>
              @endif
            </td>

            <td>
              @if($it->status === 1)
                <span class="badge badge-live">Publicado</span>
              @elseif($it->status === 2)
                <span class="badge badge-hidden">Oculto</span>
              @else
                <span class="badge badge-draft">Borrador</span>
              @endif
            </td>

            <td>
              @if($it->is_featured)
                <span class="chip">Destacado</span>
              @else
                <span class="muted">—</span>
              @endif
            </td>

            <td>
              <span class="muted">{{ $it->published_at ? $it->published_at->format('Y-m-d H:i') : '—' }}</span>
            </td>

            <td style="text-align:right;">
              <div class="actions">
                <a class="btn btn-ghost btn-small" href="{{ route('admin.catalog.edit', $it) }}">Editar</a>

                {{-- Publicar / Ocultar (toggle) --}}
                <form method="POST" action="{{ route('admin.catalog.toggle', $it) }}"
                      onsubmit="return confirm('¿Cambiar estado de publicación en el sitio web?')">
                  @csrf
                  @method('PATCH')
                  <button class="btn btn-ghost btn-small" type="submit">
                    {{ $it->status == 1 ? 'Ocultar' : 'Publicar' }}
                  </button>
                </form>

                {{-- Mercado Libre --}}
                <form method="POST" action="{{ route('admin.catalog.meli.publish', $it) }}">
                  @csrf
                  <button class="btn btn-primary btn-small" type="submit">ML: Publicar/Actualizar</button>
                </form>

                @if($it->meli_item_id)
                  <a class="btn btn-ghost btn-small" href="{{ route('admin.catalog.meli.view', $it) }}">ML: Ver</a>
                  <form method="POST" action="{{ route('admin.catalog.meli.pause', $it) }}">
                    @csrf
                    <button class="btn btn-ghost btn-small" type="submit">ML: Pausar</button>
                  </form>
                  <form method="POST" action="{{ route('admin.catalog.meli.activate', $it) }}">
                    @csrf
                    <button class="btn btn-ghost btn-small" type="submit">ML: Activar</button>
                  </form>
                @endif

                {{-- Eliminar --}}
                <form method="POST" action="{{ route('admin.catalog.destroy', $it) }}"
                      onsubmit="return confirm('¿Eliminar este producto del catálogo web? Esta acción no se puede deshacer.')">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-danger btn-small" type="submit">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="muted" style="text-align:center; padding:28px;">
              No hay productos que coincidan con el filtro.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- ======= Paginación + contador ======= --}}
  <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin:16px 4px;">
    <div class="muted">
      Mostrando {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} de {{ $items->total() }} registros
    </div>
    <div>
      {{ $items->onEachSide(1)->links() }}
    </div>
  </div>
</div>
@endsection
