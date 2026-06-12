{{-- resources/views/web/catalog/index.blade.php --}}
@extends('layouts.web')
@section('title', 'Catálogo')

@section('content')

<style>
#catalog {
  --ink: #333333;
  --muted: #888888;
  --line: #e5e7eb;
  --primary: #df2020; /* Rojo para descuentos y precios rebajados */
  --blue: #0066ff;    /* Azul para enlaces, botones y checks */
  --blue-bg: #f0f7ff;
  --green-bg: #f0fdf4;
  --green-tx: #16a34a;
  --star: #0066ff;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  color: var(--ink);
  /* Full-bleed: ocupa todo el ancho del viewport, sin marco del layout */
  background: #fff;
  width: 100vw;
  margin-left: calc(50% - 50vw);
  padding: 10px 0 50px;
}
#catalog * { box-sizing: border-box; }
#catalog a { text-decoration: none; color: inherit; }
#catalog .container { width: 100%; max-width: 1400px; padding-inline: clamp(16px, 3vw, 32px); margin-inline: auto; }

/* Encabezado y Herramientas */
#catalog .head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 22px; }
#catalog .head-title .eyebrow { display: block; font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #94a3b8; margin-bottom: 2px; }
#catalog .head-title h1 { margin: 0; font-size: clamp(19px, 2.2vw, 24px); font-weight: 600; color: #1e293b; letter-spacing: -0.3px; display: flex; align-items: baseline; gap: 8px; }
#catalog .head-title .count { font-size: 13px; font-weight: 400; color: #94a3b8; }
#catalog .head-tools { display: flex; gap: 10px; position: relative; }
#catalog .tool-btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 8px 16px; border: 1px solid #cbd5e1; border-radius: 999px;
  background: #fff; color: #333; font: inherit; font-weight: 500; font-size: 14px;
  cursor: pointer; transition: all .2s ease;
}
#catalog .tool-btn:hover { border-color: #94a3b8; }
#catalog .tool-btn.is-active, #catalog .tool-btn:focus-within { border-color: var(--blue); color: var(--blue); box-shadow: 0 0 0 1px var(--blue); }
#catalog .tool-btn svg { width: 16px; height: 16px; color: currentcolor; }

/* Dropdown Ordenar */
#catalog .panel {
  position: absolute; top: calc(100% + 10px); right: 0; z-index: 30;
  background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0,0,0,.08); padding: 12px 8px; min-width: 220px;
  opacity: 0; transform: translateY(-10px) scale(0.98); pointer-events: none;
  transition: opacity .25s ease, transform .25s cubic-bezier(0.16, 1, 0.3, 1);
  transform-origin: top right;
}
#catalog .panel.open { opacity: 1; transform: translateY(0) scale(1); pointer-events: auto; }
#catalog .panel a {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 16px; border-radius: 8px; font-size: 15px; font-weight: 400; color: #475569;
  transition: background .15s ease, color .15s ease;
}
#catalog .panel a:hover { background: #f8fafc; color: #0f172a; }
#catalog .panel a.active { color: var(--blue); }
#catalog .panel a .check-icon { display: none; width: 18px; height: 18px; fill: none; stroke: var(--blue); stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
#catalog .panel a.active .check-icon { display: block; }

/* Grid de Productos — las tarjetas no se estiran cuando hay pocos productos */
#catalog .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(245px, 1fr)); gap: 22px 18px; }
#catalog .card { position: relative; background: transparent; display: flex; flex-direction: column; padding: 8px; max-width: 360px; width: 100%; transition: transform .2s ease; }
#catalog .card:hover { transform: translateY(-4px); }

#catalog .imagebox { position: relative; width: 100%; aspect-ratio: 1/1; background: #fff; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; overflow: hidden; mix-blend-mode: multiply; }
#catalog .imagebox img { max-width: 85%; max-height: 85%; object-fit: contain; transition: transform .3s ease; }
#catalog .card:hover .imagebox img { transform: scale(1.06); }

#catalog .discount { position: absolute; top: 10px; right: 10px; z-index: 3; background: var(--primary); color: #fff; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; padding: 5px 9px; border-radius: 999px; }

#catalog .price { display: flex; align-items: baseline; gap: 7px; flex-wrap: wrap; margin-bottom: 2px; }
#catalog .price-now { font-size: 18px; font-weight: 700; color: var(--primary); }
#catalog .price-plain { font-size: 18px; font-weight: 700; color: #1e293b; }
#catalog .price-old { font-size: 14px; color: #94a3b8; text-decoration: line-through; font-weight: 500; }
#catalog .installments { font-size: 13.5px; color: var(--blue); font-weight: 500; margin-bottom: 8px; }

#catalog .tags { display: flex; gap: 5px; flex-wrap: wrap; margin-bottom: 8px; }
#catalog .tag { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 600; }
#catalog .tag.blue { background: var(--blue-bg); color: var(--blue); }
#catalog .tag.green { background: var(--green-bg); color: var(--green-tx); }
#catalog .name { font-size: 14px; line-height: 1.4; color: #475569; font-weight: 400; margin: 0 0 6px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 39px; }

#catalog .rating { display: flex; align-items: center; gap: 5px; color: #64748b; font-size: 12.5px; }
#catalog .stars { display: inline-flex; gap: 2px; }
#catalog .stars svg { width: 13.5px; height: 13.5px; fill: var(--star); }
#catalog .stars svg.half, #catalog .stars svg.empty { fill: #cbd5e1; }

/* ===================== DRAWER DE FILTROS (estilo referencia) ===================== */
#filterOverlay {
  position: fixed; inset: 0; background: rgba(15,23,42,.45);
  opacity: 0; visibility: hidden;
  transition: opacity .4s cubic-bezier(0.32, 0.72, 0, 1), visibility .4s;
  z-index: 1000; backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px);
}
#filterOverlay.open { opacity: 1; visibility: visible; }

#filterDrawer {
  position: fixed; top: 0; left: 0; height: 100dvh; width: 420px; max-width: 92vw;
  background: #fff; z-index: 1001;
  transform: translateX(-100%);
  transition: transform .5s cubic-bezier(0.32, 0.72, 0, 1), box-shadow .5s ease;
  display: flex; flex-direction: column;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}
#filterDrawer.open { transform: translateX(0); box-shadow: 24px 0 60px rgba(15,23,42,.18); }
#filterDrawer form { display: flex; flex-direction: column; height: 100%; min-height: 0; }

/* Cabecera */
#filterDrawer .drawer-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 26px 28px 20px; border-bottom: 1px solid #eef1f5; flex-shrink: 0;
}
#filterDrawer .drawer-head h2 { margin: 0; font-size: 26px; font-weight: 600; color: #1e293b; letter-spacing: -0.3px; }
#filterDrawer .drawer-close {
  width: 42px; height: 42px; border-radius: 50%; border: 0; background: #f1f5f9;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  color: var(--blue); transition: background .2s ease, transform .2s ease;
}
#filterDrawer .drawer-close:hover { background: #e2e8f0; transform: scale(1.05); }
#filterDrawer .drawer-close:active { transform: scale(0.95); }
#filterDrawer .drawer-close svg { width: 18px; height: 18px; stroke-width: 2.5; }

/* Cuerpo con scrollbar custom */
#filterDrawer .drawer-body { flex: 1; min-height: 0; overflow-y: auto; padding: 8px 20px 28px; overscroll-behavior: contain; scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
#filterDrawer .drawer-body::-webkit-scrollbar { width: 8px; }
#filterDrawer .drawer-body::-webkit-scrollbar-track { background: transparent; }
#filterDrawer .drawer-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
#filterDrawer .drawer-body::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

/* Grupos — animación de entrada escalonada */
#filterDrawer .fgroup {
  padding: 22px 0 8px;
  opacity: 0; transform: translateY(18px);
  transition: opacity .45s ease, transform .55s cubic-bezier(0.22, 1, 0.36, 1);
}
#filterDrawer.open .fgroup { opacity: 1; transform: translateY(0); }
#filterDrawer.open .fgroup:nth-child(1) { transition-delay: .12s; }
#filterDrawer.open .fgroup:nth-child(2) { transition-delay: .2s; }
#filterDrawer.open .fgroup:nth-child(3) { transition-delay: .28s; }
#filterDrawer.open .fgroup:nth-child(4) { transition-delay: .36s; }
#filterDrawer .fgroup h3 { margin: 0 0 10px; padding-inline: 8px; font-size: 22px; font-weight: 500; color: #1e293b; letter-spacing: -0.2px; }

/* Opciones — fila completa con hover gris redondeado, check a la izquierda */
#filterDrawer .fopt {
  display: flex; align-items: center;
  padding: 13px 14px 13px 12px; margin: 2px 0;
  border-radius: 10px; cursor: pointer; position: relative;
  transition: background .18s ease;
}
#filterDrawer .fopt:hover { background: #f5f6f8; }
#filterDrawer .fopt input { position: absolute; opacity: 0; width: 0; height: 0; }

/* Gutter fijo para el check: el texto siempre queda alineado */
#filterDrawer .fopt .check-mark {
  width: 34px; flex-shrink: 0; display: flex; align-items: center;
  color: var(--blue); font-size: 0;
}
#filterDrawer .fopt .check-mark svg {
  width: 20px; height: 20px; fill: none; stroke: currentColor;
  stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round;
  opacity: 0; transform: scale(.5);
  transition: opacity .2s ease, transform .25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
#filterDrawer .fopt input:checked ~ .check-mark svg { opacity: 1; transform: scale(1); }

#filterDrawer .fopt .fopt-label { font-size: 18px; color: #475569; font-weight: 400; transition: color .15s ease; line-height: 1.3; }
#filterDrawer .fopt:hover .fopt-label { color: #1e293b; }
#filterDrawer .fopt input:checked ~ .fopt-label { color: var(--blue); font-weight: 500; }

/* Ver más / Ver menos */
#filterDrawer .cat-extra { display: none; }
#filterDrawer .fgroup.show-all .cat-extra { display: flex; }
#filterDrawer .ver-mas {
  margin: 8px 0 6px; padding: 6px 0 6px 46px;
  background: none; border: 0; font: inherit; font-weight: 700; font-size: 17px;
  color: #1e293b; text-decoration: underline; text-underline-offset: 3px; cursor: pointer;
  transition: color .15s ease;
}
#filterDrawer .ver-mas:hover { color: var(--blue); }

/* Pie */
#filterDrawer .drawer-foot {
  padding: 18px 24px calc(18px + env(safe-area-inset-bottom)); border-top: 1px solid #eef1f5; background: #fff; flex-shrink: 0;
  opacity: 0; transform: translateY(14px);
  transition: opacity .4s ease .3s, transform .5s cubic-bezier(0.22, 1, 0.36, 1) .3s;
}
#filterDrawer.open .drawer-foot { opacity: 1; transform: translateY(0); }
#filterDrawer .ver-resultados {
  width: 100%; border: 0; border-radius: 999px; background: var(--blue); color: #fff;
  font: inherit; font-weight: 600; font-size: 17px; padding: 16px 0; cursor: pointer;
  transition: background .2s ease, transform .15s ease, box-shadow .2s ease;
  box-shadow: 0 6px 18px rgba(0, 102, 255, 0.25);
}
#filterDrawer .ver-resultados:hover { background: #005ce6; box-shadow: 0 8px 22px rgba(0, 102, 255, 0.32); }
#filterDrawer .ver-resultados:active { transform: scale(0.98); }

/* Animación de salida: los grupos bajan sutilmente mientras el drawer se va */
#filterDrawer.closing .fgroup, #filterDrawer.closing .drawer-foot {
  opacity: 0; transform: translateY(10px);
  transition: opacity .2s ease, transform .25s ease;
  transition-delay: 0s !important;
}

@media (prefers-reduced-motion: reduce) {
  #filterDrawer, #filterOverlay, #filterDrawer .fgroup, #filterDrawer .drawer-foot { transition-duration: .01s !important; transition-delay: 0s !important; }
}

@media (max-width: 768px) {
  #catalog .grid { grid-template-columns: repeat(2, 1fr); gap: 16px 12px; }
  #catalog .tool-btn { padding: 8px 16px; font-size: 14px; }
  #filterDrawer { width: 100vw; max-width: 100vw; }
  #filterDrawer .drawer-head h2 { font-size: 23px; }
  #filterDrawer .fgroup h3 { font-size: 20px; }
  #filterDrawer .fopt .fopt-label { font-size: 17px; }
}
</style>

@php
  $imgUrl = function($raw){
    if(!$raw || !is_string($raw) || trim($raw)==='') return null;
    $raw = trim($raw);
    if (\Illuminate\Support\Str::startsWith($raw, ['http://','https://'])) return $raw;
    if (\Illuminate\Support\Str::startsWith($raw, ['storage/'])) return asset($raw);
    return \Illuminate\Support\Facades\Storage::url($raw);
  };
  $pickPhotoUrl = function($p) use ($imgUrl){
    foreach([$p->photo_1 ?? null, $p->photo_2 ?? null, $p->photo_3 ?? null] as $c){
      $u = $imgUrl($c); if($u) return $u;
    }
    return asset('images/placeholder.png');
  };

  $order = request('order', 'relevante');
  $s     = request('s', '');
  $orderLabels = [
      'relevante'  => 'Más relevante',
      'price_asc'  => 'Menor precio',
      'price_desc' => 'Mayor precio'
  ];
  $priceOpts = [
      ''          => 'Todos los precios',
      'lt500'     => 'Menos de $500',
      '500-2000'  => '$500 - $2,000',
      '2000-5000' => '$2,000 - $5,000',
      'gt5000'    => 'Más de $5,000'
  ];
  $cats = $categories ?? collect();

  $activeCat   = request('category') ? $cats->firstWhere('id', (int)request('category')) : null;
  $headTitle   = $activeCat->name ?? ($s !== '' ? 'Resultados para "'.$s.'"' : 'Todos los productos');
  $totalItems  = method_exists($items, 'total') ? $items->total() : $items->count();
@endphp

<div id="catalog">
  <div class="container">

    {{-- Herramientas de Cabecera --}}
    <div class="head">
      <div class="head-title">
        <span class="eyebrow">Categoría</span>
        <h1>{{ $headTitle }} <span class="count">({{ number_format($totalItems) }})</span></h1>
      </div>
      <div class="head-tools">
        <button type="button" class="tool-btn" data-panel="panelOrder">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M7 16V4M7 4L3 8M7 4L11 8M17 8V20M17 20L13 16M17 20L21 16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Ordenar
        </button>

        <div class="panel" id="panelOrder">
          @foreach($orderLabels as $key => $label)
            <a href="{{ route('web.catalog.index', array_filter(['s'=>$s,'order'=>$key,'category'=>request('category'),'price'=>request('price'),'stock'=>request('stock')])) }}"
               class="{{ $order===$key ? 'active' : '' }}">
               {{ $label }}
               <svg class="check-icon" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
            </a>
          @endforeach
        </div>

        <button type="button" class="tool-btn" data-open-filter>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 21V14M4 10V3M12 21V12M12 8V3M20 21V16M20 12V3M1 14H7M9 8H15M17 16H23" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Filtrar
        </button>
      </div>
    </div>

    {{-- Grid de Productos --}}
    @if($items->count())
      <div class="grid">
        @foreach($items as $p)
          @php
            $price = (float)($p->price ?? 0);
            $sale  = !is_null($p->sale_price) ? (float)$p->sale_price : null;
            $hasOffer = $sale && $price > 0 && $sale < $price;
            $savePct  = $hasOffer ? max(1, round(100 - (($sale/$price)*100))) : null;
            $final    = $sale ?? $price;
            $monthly  = $final > 0 ? round($final/4, 2) : 0;
            $img      = $pickPhotoUrl($p);
            $ratingCount = 0;
            if (isset($p->reviews_count) && (int)$p->reviews_count > 0) $ratingCount = (int)$p->reviews_count;
            elseif (isset($p->rating_count) && (int)$p->rating_count > 0) $ratingCount = (int)$p->rating_count;
          @endphp

          <a href="{{ route('web.catalog.show', $p) }}" class="card" aria-label="Ver {{ $p->name }}">
            <div class="imagebox">
              @if($hasOffer)<span class="discount">-{{ $savePct }}%</span>@endif
              <img src="{{ $img }}" alt="{{ $p->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
            </div>

            <div class="price">
              <span class="{{ $hasOffer ? 'price-now' : 'price-plain' }}">${{ number_format($final,2) }}</span>
              @if($hasOffer)<span class="price-old">${{ number_format($price,2) }}</span>@endif
            </div>

            @if($final > 0)<div class="installments">4 pagos de ${{ number_format($monthly,2) }}</div>@endif

            <div class="tags">
              <span class="tag blue">Sin intereses</span>
              @if($hasOffer && $savePct >= 20)<span class="tag green">Envío gratis</span>@endif
            </div>

            <h3 class="name">{{ $p->name }}</h3>

            <div class="rating">
              <span class="stars" aria-hidden="true">
                @for($i=0;$i<5;$i++)
                  <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                @endfor
              </span>
              @if($ratingCount > 0)<span>({{ number_format($ratingCount) }})</span>@endif
            </div>
          </a>
        @endforeach
      </div>

    @else
      <div style="text-align:center; padding: 80px 20px; color: #94a3b8;">
        <h3>Sin resultados</h3>
      </div>
    @endif

  </div>

  {{-- ===================== DRAWER FILTROS ===================== --}}
  <div id="filterOverlay"></div>

  <aside id="filterDrawer" aria-label="Filtros">
    <form method="GET" action="{{ route('web.catalog.index') }}">
      <input type="hidden" name="s" value="{{ $s }}">
      <input type="hidden" name="order" value="{{ $order }}">

      <div class="drawer-head">
        <h2>Filtros</h2>
        <button type="button" class="drawer-close" data-close-filter aria-label="Cerrar filtros">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
      </div>

      <div class="drawer-body">

        {{-- Categorías --}}
        @if($cats->count())
          <div class="fgroup" id="catGroup">
            <h3>Categorías</h3>
            <label class="fopt">
              <input type="radio" name="category" value="" {{ !request('category') ? 'checked' : '' }}>
              <span class="check-mark"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></span>
              <span class="fopt-label">Todas las categorías</span>
            </label>
            @foreach($cats as $c)
              <label class="fopt {{ $loop->index >= 5 ? 'cat-extra' : '' }}">
                <input type="radio" name="category" value="{{ $c->id }}" {{ (string)request('category')===(string)$c->id ? 'checked' : '' }}>
                <span class="check-mark"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></span>
                <span class="fopt-label">{{ $c->name }}</span>
              </label>
            @endforeach
            @if($cats->count() > 5)
              <button type="button" class="ver-mas" data-vermas>Ver más</button>
            @endif
          </div>
        @endif

        {{-- Disponibilidad --}}
        <div class="fgroup">
          <h3>Disponibilidad</h3>
          <label class="fopt">
            <input type="checkbox" name="stock" value="1" {{ request('stock') ? 'checked' : '' }}>
            <span class="check-mark"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></span>
            <span class="fopt-label">En Stock</span>
          </label>
        </div>

        {{-- Precio --}}
        <div class="fgroup">
          <h3>Precio</h3>
          @foreach($priceOpts as $val => $label)
            <label class="fopt">
              <input type="radio" name="price" value="{{ $val }}" {{ (string)request('price')===(string)$val ? 'checked' : '' }}>
              <span class="check-mark"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></span>
              <span class="fopt-label">{{ $label }}</span>
            </label>
          @endforeach
        </div>

      </div>

      <div class="drawer-foot">
        <button type="submit" class="ver-resultados">Ver resultados</button>
      </div>
    </form>
  </aside>
</div>

<script>
  (function(){
    /* Animación y Toggle de Ordenar */
    const tools  = document.querySelectorAll('#catalog .tool-btn[data-panel]');
    const panels = document.querySelectorAll('#catalog .panel');
    const closeAll = () => panels.forEach(p => p.classList.remove('open'));

    tools.forEach(btn => btn.addEventListener('click', e => {
      e.stopPropagation();
      const panel = document.getElementById(btn.dataset.panel);
      const open = panel.classList.contains('open');
      closeAll();
      if(!open) panel.classList.add('open');
    }));
    document.addEventListener('click', e => { if(!e.target.closest('#catalog .head-tools')) closeAll(); });

    /* Drawer de Filtros — entrada y salida coordinadas */
    const overlay = document.getElementById('filterOverlay');
    const drawer  = document.getElementById('filterDrawer');
    let closingTimer = null;

    const openFilter = () => {
      clearTimeout(closingTimer);
      drawer.classList.remove('closing');
      overlay.classList.add('open');
      // forzar reflow para que el stagger de los grupos siempre se dispare
      void drawer.offsetWidth;
      drawer.classList.add('open');
      document.body.style.overflow = 'hidden';
    };

    const closeFilter = () => {
      drawer.classList.add('closing');           // los grupos se desvanecen primero
      overlay.classList.remove('open');
      closingTimer = setTimeout(() => {
        drawer.classList.remove('open');         // luego el drawer sale deslizando
        drawer.classList.remove('closing');
      }, 120);
      document.body.style.overflow = '';
    };

    document.querySelectorAll('[data-open-filter]').forEach(b => b.addEventListener('click', openFilter));
    document.querySelectorAll('[data-close-filter]').forEach(b => b.addEventListener('click', closeFilter));
    overlay.addEventListener('click', closeFilter);
    document.addEventListener('keydown', e => { if(e.key === 'Escape' && drawer.classList.contains('open')) closeFilter(); });

    /* Mostrar / Ocultar categorías */
    const vermas = document.querySelector('[data-vermas]');
    if(vermas){
      vermas.addEventListener('click', () => {
        const g = document.getElementById('catGroup');
        g.classList.toggle('show-all');
        vermas.textContent = g.classList.contains('show-all') ? 'Ver menos' : 'Ver más';
      });
    }

    /* Auto-aplicar filtros: al seleccionar cualquier opción, busca solo */
    const form = drawer.querySelector('form');
    const submitBtn = form.querySelector('.ver-resultados');
    let submitting = false;
    form.addEventListener('change', e => {
      if(!e.target.matches('input[type="radio"], input[type="checkbox"]')) return;
      if(submitting) return;
      submitting = true;
      if(submitBtn){ submitBtn.textContent = 'Buscando…'; submitBtn.disabled = true; submitBtn.style.opacity = '.7'; }
      // pequeño delay para que se vea la animación del check antes de recargar
      setTimeout(() => form.submit(), 280);
    });
  })();
</script>
@endsection