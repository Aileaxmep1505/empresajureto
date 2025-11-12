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
  .alert{padding:10px 12px; border-radius:12px; border:1px solid var(--line); background:#f8fffb; color:#0b6b3a}
  @media (max-width: 780px){
    th:nth-child(5), td:nth-child(5),
    th:nth-child(7), td:nth-child(7){ display:none; }
  }
</style>
@endpush

@section('content')
<div class="wrap">
  <div class="head" style="margin: 14px 0 10px;">
    <h1 class="title">Productos Web <span class="muted" style="font-weight:600;">(Catálogo público)</span></h1>
    <div class="toolbar">
      <a href="{{ route('admin.catalog.create') }}" class="btn btn-primary">+ Nuevo producto</a>
    </div>
  </div>

  @if(session('ok'))
    <div class="alert" style="margin:10px 0 16px;">{{ session('ok') }}</div>
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
                @if($it->meli_item_id)
                  <div class="muted" style="font-size:.82rem;">
                    ML ID: {{ $it->meli_item_id }} · Estado ML: {{ $it->meli_status ?: '—' }}
                  </div>
                @endif
                @if($it->meli_last_error)
                  <div class="muted" style="font-size:.78rem;color:#b91c1c;max-width:560px;white-space:normal;">
                    Último error ML: {{ $it->meli_last_error }}
                  </div>
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
                      onsubmit="return confirm('¿Cambiar estado de publicación?')">
                  @csrf
                  @method('PATCH')
                  <button class="btn btn-ghost btn-small" type="submit">
                    {{ $it->status == 1 ? 'Ocultar' : 'Publicar' }}
                  </button>
                </form>

                {{-- ===== Mercado Libre ===== --}}
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
                      onsubmit="return confirm('¿Eliminar este producto?')">
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
