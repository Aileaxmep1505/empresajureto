{{-- resources/views/web/categorias/show.blade.php --}}
@extends('layouts.web')
@section('title', $category->name)

@section('content')
<div id="category">
  <style>
    #category{--ink:#0e1726;--muted:#6b7280;--line:#e8eef6;--card:#fff;
              --chip:#f7fbff;--radius:16px;--shadow:0 16px 40px rgba(2,8,23,.08)}
    #category *{box-sizing:border-box}
    #category .wrap{max-width:1200px;margin:clamp(48px,6vw,72px) auto;padding:0 16px}
    #category .grid{display:grid;grid-template-columns:260px 1fr;gap:28px}
    #category .side{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:16px}
    #category .head{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
    #category h1{margin:0;font-size:clamp(20px,3vw,28px);color:var(--ink)}
    #category .filters{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    #category .filters input[type="text"],
    #category .filters input[type="number"],
    #category .filters select{border:1px solid var(--line);border-radius:10px;padding:8px 10px;font:inherit}
    #category .filters button{border:1px solid var(--line);background:#f8fafc;border-radius:10px;padding:8px 12px;cursor:pointer}
    #category .cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
    #category .card{border:1px solid var(--line);background:var(--card);border-radius:14px;overflow:hidden;box-shadow:var(--shadow);display:flex;flex-direction:column}
    #category .card .img{aspect-ratio:1/1;background:#f3f5f8;display:flex;align-items:center;justify-content:center}
    #category .card .img img{max-width:90%;max-height:90%;object-fit:contain}
    #category .card .body{padding:12px}
    #category .name{color:var(--ink);font-weight:600;font-size:.96rem;line-height:1.25rem;min-height:2.5rem}
    #category .sku{color:var(--muted);font-size:.8rem}
    #category .price{margin-top:8px;font-weight:800}
    #category .cta{display:flex;gap:8px;margin-top:10px}
    #category .cta a,#category .cta button{flex:1;padding:8px 10px;border-radius:10px;border:1px solid var(--line);background:#f8fafc;cursor:pointer;text-align:center;text-decoration:none;color:var(--ink)}
    #category .empty{padding:28px;border:1px dashed var(--line);border-radius:14px;text-align:center;color:var(--muted)}
    @media (max-width:1024px){#category .cards{grid-template-columns:repeat(3,1fr)} .grid{grid-template-columns:1fr}}
    @media (max-width:640px){#category .cards{grid-template-columns:repeat(2,1fr)}}
  </style>

  <div class="wrap">
    <div class="head">
      <h1>{{ $category->name }}</h1>

      <form class="filters" method="GET" action="{{ route('web.categorias.show', $category->slug) }}">
        <input type="text" name="q"  value="{{ $q }}"  placeholder="Buscar en {{ strtolower($category->name) }}">
        <input type="number" step="0.01" min="0" name="min" value="{{ $min }}" placeholder="Min $">
        <input type="number" step="0.01" min="0" name="max" value="{{ $max }}" placeholder="Max $">
        <select name="orden">
          <option value="relevancia"  @selected($orderBy==='relevancia')>Más relevantes</option>
          <option value="nuevo"       @selected($orderBy==='nuevo')>Más nuevos</option>
          <option value="precio_asc"  @selected($orderBy==='precio_asc')>Precio: menor a mayor</option>
          <option value="precio_desc" @selected($orderBy==='precio_desc')>Precio: mayor a menor</option>
        </select>
        <button type="submit">Filtrar</button>
      </form>
    </div>

    <div class="grid">
      <aside class="side">
        @include('web.partials.menu-categorias', ['primary' => $primary])
      </aside>

      <main>
        @if ($items->count() === 0)
          <div class="empty">No hay productos que coincidan con tu búsqueda.</div>
        @else
          <div class="cards">
            @foreach ($items as $it)
              <article class="card">
                <a class="img" href="{{ url('/producto/'.$it->slug) }}">
                  @if($it->image_url)
                    <img src="{{ $it->image_url }}" alt="{{ $it->name }}">
                  @else
                    <img src="https://placehold.co/600x600?text={{ urlencode($it->name) }}" alt="{{ $it->name }}">
                  @endif
                </a>
                <div class="body">
                  <div class="name">
                    <a href="{{ url('/producto/'.$it->slug) }}" style="text-decoration:none;color:inherit">{{ $it->name }}</a>
                  </div>
                  @if(!empty($it->sku))
                    <div class="sku">SKU: {{ $it->sku }}</div>
                  @endif
                  <div class="price">${{ number_format((float)($it->sale_price ?? $it->price), 2) }}</div>

                  <div class="cta">
                    <a href="{{ url('/producto/'.$it->slug) }}">Ver</a>

                    {{-- Usa tus rutas: web.cart.add (POST) con catalog_item_id --}}
                    <form method="POST" action="{{ route('web.cart.add') }}">
                      @csrf
                      <input type="hidden" name="catalog_item_id" value="{{ $it->id }}">
                      <input type="hidden" name="qty" value="1">
                      <button type="submit">Agregar</button>
                    </form>
                  </div>
                </div>
              </article>
            @endforeach
          </div>

          <div style="margin-top:16px;">
            {{ $items->links() }}
          </div>
        @endif
      </main>
    </div>
  </div>
</div>
@endsection
