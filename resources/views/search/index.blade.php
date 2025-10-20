@extends('layouts.web')
@section('title', 'Buscar')

@section('content')
<style>
  /* ===== Tokens pastel minimal ===== */
  :root{
    --bg:#f6f8fc;
    --surface:#ffffff;
    --ink:#0f172a;
    --muted:#64748b;
    --line:#e8eef6;
    --brand:#6ea8fe;
    --ok:#10b981;
    --warn:#eab308;
    --danger:#ef4444;
    --shadow:0 18px 60px rgba(2,8,23,.10);
    --radius:18px;
  }
  html,body{background:var(--bg)}
  .wrap{max-width:1180px;margin-inline:auto;padding:24px}

  /* ===== Head / searchbar ===== */
  .head{
    position:relative;
    padding:24px;
    border-radius:20px;
    background:
      radial-gradient(800px 400px at -10% -20%, #eaf2ff 0%, transparent 60%),
      radial-gradient(800px 400px at 120% 20%, #eafff3 0%, transparent 55%),
      var(--surface);
    border:1px solid var(--line);
    box-shadow: var(--shadow);
  }
  .title{font-size:clamp(22px,3vw,28px); color:var(--ink); font-weight:700; margin:0 0 6px}
  .subtitle{color:var(--muted); margin-bottom:18px}

  .searchbar{
    display:grid; gap:12px;
    grid-template-columns: 1fr auto;
    background:#fff;border:1px solid var(--line);border-radius:16px;padding:10px 10px 10px 14px;
  }
  .searchbar input[type="text"]{
    border:0; outline:0; font-size:16px; width:100%; color:var(--ink); background:transparent;
  }
  .btn{
    display:inline-flex;align-items:center;gap:8px;
    padding:10px 16px;border-radius:12px;border:1px solid var(--line);
    background:var(--brand);color:#0b1220;font-weight:600;cursor:pointer;text-decoration:none;
    box-shadow:0 8px 24px rgba(110,168,254,.35);
  }
  .btn-ghost{background:#fff;color:var(--ink);box-shadow:none}
  .btn:active{transform:translateY(1px)}

  /* autosuggest */
  .suggest{
    position:absolute;left:24px;right:24px;top:100%;margin-top:8px;z-index:30;
    background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:var(--shadow);display:none;
    overflow:hidden;
  }
  .suggest.show{display:block}
  .suggest .row{display:flex;gap:16px;padding:10px 12px;align-items:center;cursor:pointer}
  .suggest .row:hover{background:#f7faff}
  .suggest .term{flex:1;color:var(--ink)}
  .suggest .pill{font-size:12px;color:#1d4ed8;background:rgba(59,130,246,.16);padding:4px 8px;border-radius:999px}

  /* ===== Filters bar ===== */
  .filters{
    margin-top:16px;
    display:flex;flex-wrap:wrap;gap:8px;align-items:center
  }
  .chip{
    display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;
    background:#fff;border:1px solid var(--line);color:var(--ink);font-weight:600;
  }
  .chip input{accent-color:var(--brand)}
  .select{border:1px solid var(--line);background:#fff;border-radius:10px;padding:8px 10px;color:var(--ink)}

  /* ===== Content layout ===== */
  .layout{display:grid;grid-template-columns: 260px 1fr;gap:18px;margin-top:22px}
  @media (max-width: 980px){ .layout{grid-template-columns:1fr} }

  .side{
    background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);padding:16px
  }
  .side h4{margin:0 0 10px;color:var(--ink)}
  .side .group{border-top:1px dashed var(--line);padding-top:12px;margin-top:12px}
  .side label{display:flex;align-items:center;gap:10px;margin:8px 0;color:var(--ink)}
  .side small{color:var(--muted)}

  .content .meta{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:12px}
  .meta .count{color:var(--muted)}

  .grid{
    display:grid;gap:14px;
    grid-template-columns:repeat(4,1fr);
  }
  @media (max-width:1200px){ .grid{grid-template-columns:repeat(3,1fr)} }
  @media (max-width:820px){ .grid{grid-template-columns:repeat(2,1fr)} }
  @media (max-width:520px){ .grid{grid-template-columns:1fr} }

  /* ===== Product card ===== */
  .card{
    position:relative;background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);
    overflow:hidden;display:flex;flex-direction:column
  }
  .thumb{
    width:100%;aspect-ratio:1/1.0;object-fit:cover;background:#f2f5fb;border-bottom:1px solid var(--line)
  }
  .body{padding:12px 12px 14px}
  .brand{font-size:12px;color:var(--muted);margin-bottom:2px}
  .name{font-weight:700;color:var(--ink);line-height:1.3}
  .badges{display:flex;flex-wrap:wrap;gap:6px;margin:8px 0}
  .badge{font-size:12px;padding:4px 8px;border-radius:999px;border:1px solid var(--line);background:#fff;color:var(--ink)}
  .price{display:flex;align-items:baseline;gap:8px;margin-top:6px}
  .p-main{font-size:18px;font-weight:800;color:var(--ink)}
  .p-old{font-size:13px;color:var(--muted);text-decoration:line-through}
  .cta{display:flex;gap:8px;margin-top:10px}
  .btn-add{flex:1;background:var(--ink);color:#fff;border:1px solid var(--ink)}
  .btn-view{flex:1}

  .flag{
    position:absolute;left:10px;top:10px;padding:6px 10px;border-radius:999px;background:#0ea5e9;color:#00131a;font-weight:800;font-size:12px
  }

  /* ===== Empty state ===== */
  .empty{
    background:#fff;border:1px dashed var(--line);border-radius:16px;padding:30px;text-align:center;color:var(--muted)
  }

  /* ===== Pagination ===== */
  .pager{display:flex;justify-content:center;margin:22px 0}
  .pager .pagination{display:flex;gap:8px;list-style:none;padding:0}
  .pager .pagination li a, .pager .pagination li span{
    display:inline-flex;min-width:36px;height:36px;align-items:center;justify-content:center;
    border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--ink);text-decoration:none
  }
  .pager .pagination li.active span{background:var(--brand);color:#0b1220;font-weight:700;border-color:transparent}
</style>

<div class="wrap">
  <div class="head">
    <h1 class="title">Buscar productos</h1>
    <div class="subtitle">Encuentra equipos y suministros médicos con filtro por envío, disponibilidad y más.</div>

    <form id="searchForm" method="GET" action="{{ route('search.index') }}">
      <div class="searchbar">
        <input
          type="text"
          name="q"
          id="q"
          value="{{ old('q', $stats['q'] ?? request('q')) }}"
          placeholder="Escribe lo que buscas (p. ej. 'endoscopio', 'monitor de signos vitales')"
          autocomplete="off"
          />
        <button class="btn" type="submit">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M21 21l-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          Buscar
        </button>
      </div>

      {{-- Sugerencias --}}
      <div id="suggestBox" class="suggest"></div>

      {{-- Filtros rápidos arriba --}}
      <div class="filters">
        <label class="chip">
          <input type="checkbox" name="disponible" value="1" {{ ($filters['disponible'] ?? false) ? 'checked' : '' }}> Disponible
        </label>
        <label class="chip">
          <input type="checkbox" name="envio_gratis" value="1" {{ ($filters['envio'] ?? false) ? 'checked' : '' }}> Envío gratis
        </label>
        <label class="chip">
          <input type="checkbox" name="express" value="1" {{ ($filters['express'] ?? false) ? 'checked' : '' }}> Envío express
        </label>
        <label class="chip">
          <input type="checkbox" name="msi" value="1" {{ ($filters['msi'] ?? false) ? 'checked' : '' }}> Meses sin intereses
        </label>
        <label class="chip">
          <input type="checkbox" name="club" value="1" {{ ($filters['club'] ?? false) ? 'checked' : '' }}> Precio Club
        </label>

        <div style="margin-left:auto;display:flex;gap:8px;align-items:center">
          <span class="muted" style="color:var(--muted)">Ordenar:</span>
          <select class="select" name="order" id="order">
            @php $ord = $order ?? request('order','sugerido'); @endphp
            <option value="sugerido" {{ $ord==='sugerido' ? 'selected' : '' }}>Sugerido</option>
            <option value="precio_asc" {{ $ord==='precio_asc' ? 'selected' : '' }}>Precio: menor a mayor</option>
            <option value="precio_desc" {{ $ord==='precio_desc' ? 'selected' : '' }}>Precio: mayor a menor</option>
            <option value="nuevos" {{ $ord==='nuevos' ? 'selected' : '' }}>Novedades</option>
            <option value="ventas" {{ $ord==='ventas' ? 'selected' : '' }}>Más vendidos</option>
          </select>
          <button class="btn btn-ghost" type="submit">Aplicar</button>
        </div>
      </div>
    </form>
  </div>

  <div class="layout">
    {{-- Sidebar opcional con más filtros (rango de precio, marca, etc.) --}}
    <aside class="side">
      <h4>Refinar búsqueda</h4>
      <div class="group">
        <small>Rango de precio</small>
        <div style="display:flex; gap:8px; margin-top:8px">
          <input type="number" name="min" form="searchForm" placeholder="Mín" value="{{ request('min') }}" class="select" style="width:100%">
          <input type="number" name="max" form="searchForm" placeholder="Máx" value="{{ request('max') }}" class="select" style="width:100%">
        </div>
      </div>
      <div class="group">
        <small>Marca</small>
        <input type="text" name="brand" form="searchForm" placeholder="Ej. Mindray" value="{{ request('brand') }}" class="select" style="width:100%">
      </div>
      <div class="group">
        <small>Orden</small>
        <select name="order" form="searchForm" class="select" style="width:100%">
          <option value="sugerido" {{ $ord==='sugerido' ? 'selected' : '' }}>Sugerido</option>
          <option value="precio_asc" {{ $ord==='precio_asc' ? 'selected' : '' }}>Precio: menor a mayor</option>
          <option value="precio_desc" {{ $ord==='precio_desc' ? 'selected' : '' }}>Precio: mayor a menor</option>
          <option value="nuevos" {{ $ord==='nuevos' ? 'selected' : '' }}>Novedades</option>
          <option value="ventas" {{ $ord==='ventas' ? 'selected' : '' }}>Más vendidos</option>
        </select>
      </div>
      <div class="group">
        <button form="searchForm" class="btn" style="width:100%">Actualizar resultados</button>
      </div>
    </aside>

    <section class="content">
      <div class="meta">
        <div class="count">
          @php $q = $stats['q'] ?? request('q'); $count = $stats['count'] ?? ($results->total() ?? 0); @endphp
          <strong>{{ number_format($count) }}</strong> resultados
          @if($q) para “<strong>{{ $q }}</strong>” @endif
        </div>
      </div>

      @if(($results->count() ?? 0) === 0)
        <div class="empty">
          @if($q)
            No encontramos coincidencias para “<strong>{{ $q }}</strong>”. Prueba con términos similares:
            @if(!empty($stats['expanded']))
              <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;justify-content:center">
                @foreach($stats['expanded'] as $term)
                  <a class="badge" href="{{ route('search.index', array_merge(request()->query(), ['q'=>$term])) }}">{{ $term }}</a>
                @endforeach
              </div>
            @endif
          @else
            Escribe algo en el buscador para comenzar.
          @endif
        </div>
      @else
        <div class="grid">
          @foreach($results as $item)
            @php
              // Campos esperados (ajusta a tu modelo real)
              $id = $item->id ?? null;
              $name = $item->name ?? ($item->nombre ?? 'Producto');
              $brand = $item->brand ?? ($item->marca ?? null);
              $price = $item->price ?? ($item->precio ?? null);
              $old   = $item->old_price ?? ($item->precio_anterior ?? null);
              $img   = $item->image_url ?? $item->imagen_url ?? $item->cover ?? null;
              $envioGratis = ($item->free_shipping ?? $item->envio_gratis ?? false) ? true : false;
              $express = ($item->express ?? false) ? true : false;
              $msi = ($item->msi ?? false) ? true : false;
              $disponible = ($item->stock ?? 0) > 0;
            @endphp
            <article class="card">
              @if($envioGratis)<span class="flag">ENVÍO GRATIS</span>@endif
              <img class="thumb" src="{{ $img ?: asset('img/placeholder-1x1.png') }}"
                   alt="{{ $name }}" onerror="this.src='{{ asset('img/placeholder-1x1.png') }}'">
              <div class="body">
                @if($brand)<div class="brand">{{ $brand }}</div>@endif
                <div class="name">{{ $name }}</div>
                <div class="badges">
                  @if($disponible)<span class="badge">Disponible</span>@else<span class="badge" style="opacity:.7">Agotado</span>@endif
                  @if($express)<span class="badge">Express</span>@endif
                  @if($msi)<span class="badge">MSI</span>@endif
                </div>
                <div class="price">
                  @if(!is_null($price))<div class="p-main">${{ number_format($price,2) }} MXN</div>@endif
                  @if(!is_null($old) && $old > $price)<div class="p-old">${{ number_format($old,2) }}</div>@endif
                </div>
                <div class="cta">
                  <a class="btn btn-view" href="{{ route('catalog.show', $id ?? 0) }}">Ver</a>
                  <form method="POST" action="{{ route('cart.add') }}">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $id }}">
                    <button class="btn btn-add" type="submit">Agregar</button>
                  </form>
                </div>
              </div>
            </article>
          @endforeach
        </div>

        <div class="pager">
          {{-- Si usas Tailwind pagination, esto ya viene listo --}}
          {{ $results->withQueryString()->links() }}
        </div>
      @endif
    </section>
  </div>
</div>

<script>
  // ===== Auto-submit al cambiar filtros/orden =====
  document.querySelectorAll('.filters input[type="checkbox"], .filters select').forEach(el=>{
    el.addEventListener('change', ()=> document.getElementById('searchForm').submit());
  });

  // ===== Sugerencias (debounce) =====
  const q = document.getElementById('q');
  const box = document.getElementById('suggestBox');
  let t=null;

  function hideSuggest(){ box.classList.remove('show'); box.innerHTML=''; }
  function showSuggest(html){ box.innerHTML = html; box.classList.add('show'); }

  q.addEventListener('input', ()=>{
    clearTimeout(t);
    const term = q.value.trim();
    if(!term){ hideSuggest(); return; }
    t = setTimeout(async ()=>{
      try{
        const url = new URL("{{ route('search.suggest') }}", window.location.origin);
        url.searchParams.set('term', term);
        const res = await fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}});
        const data = await res.json();
        const terms = (data.terms || []).slice(0,6);
        const products = (data.products || []).slice(0,4);
        if(terms.length===0 && products.length===0){ hideSuggest(); return; }

        let html = '';
        terms.forEach(s=>{
          const href = new URL("{{ route('search.index') }}", window.location.origin);
          href.searchParams.set('q', s);
          html += `<div class="row" onclick="location.href='${href.toString()}'"><div class="term">${s}</div><span class="pill">término</span></div>`;
        });
        products.forEach(p=>{
          const href = "{{ route('catalog.show', ':id') }}".replace(':id', p.id);
          html += `<div class="row" onclick="location.href='${href}'"><div class="term">${p.name}</div><span class="pill">producto</span></div>`;
        });

        showSuggest(html);
      }catch(e){ hideSuggest(); }
    }, 200);
  });

  document.addEventListener('click', (e)=>{
    if(!e.target.closest('.searchbar') && !e.target.closest('#suggestBox')) hideSuggest();
  });
</script>
@endsection
