{{-- resources/views/web/ofertas.blade.php --}}
@extends('layouts.web')
@section('title','Ofertas')

@section('content')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400..700&display=swap"/>

@php
  use Illuminate\Support\Str;

  // ====== Detección de columnas disponibles ======
  $brandCol = \Schema::hasColumn('catalog_items','brand') ? 'brand'
            : (\Schema::hasColumn('catalog_items','marca') ? 'marca' : null);

  $hasColor = \Schema::hasColumn('catalog_items','color');
  $hasSizeCol = \Schema::hasColumn('catalog_items','size');
  $hasTamano = \Schema::hasColumn('catalog_items','tamano');
  $sizeCol   = $hasSizeCol ? 'size' : ($hasTamano ? 'tamano' : null);

  // ====== Parámetros de filtro/orden por GET ======
  $q         = trim(request('q',''));
  $orden     = request('orden','mejor_descuento'); // mejor_descuento|precio_asc|precio_desc|recientes|nombre
  $minOff    = (int) request('min_off', 0);        // % mínimo de descuento
  $inStock   = (int) request('stock', 0);          // 1 = sólo con stock (>0)

  // Filtros avanzados
  $brandsSel = array_filter((array) request('brand', []), fn($v) => $v !== '');
  $colorsSel = array_filter((array) request('color', []), fn($v) => $v !== '');
  $sizesSel  = array_filter((array) request('size',  []), fn($v) => $v !== '');
  $pmin      = request('pmin') !== null ? max(0, (float) request('pmin')) : null;
  $pmax      = request('pmax') !== null ? max(0, (float) request('pmax')) : null;

  // ====== Query base: SOLO OFERTAS ======
  $ofertasQuery = \App\Models\CatalogItem::published()
      ->whereNotNull('sale_price')
      ->whereColumn('sale_price','<','price');

  // Texto libre (por si llega desde header)
  if($q !== ''){
    $ofertasQuery->where(function($qq) use ($q, $brandCol){
      $qq->where('name','like',"%{$q}%")
         ->orWhere('sku','like',"%{$q}%");
      if($brandCol){
        $qq->orWhere($brandCol,'like',"%{$q}%");
      }
    });
  }

  // % mínimo
  if($minOff > 0){
    $ratio = 1 - ($minOff/100); // sale/price <= ratio
    $ofertasQuery->whereRaw('sale_price / price <= ?', [$ratio]);
  }

  // Stock
  if($inStock && \Schema::hasColumn('catalog_items','stock')){
    $ofertasQuery->where('stock','>',0);
  }

  // Precio min/max (sobre sale_price para ofertas)
  if(!is_null($pmin)){ $ofertasQuery->where('sale_price','>=',$pmin); }
  if(!is_null($pmax) && $pmax > 0){ $ofertasQuery->where('sale_price','<=',$pmax); }

  // Marca / Tamaño / Color
  if($brandCol && $brandsSel){ $ofertasQuery->whereIn($brandCol, $brandsSel); }
  if($sizeCol  && $sizesSel){  $ofertasQuery->whereIn($sizeCol,  $sizesSel);  }
  if($hasColor && $colorsSel){ $ofertasQuery->whereIn('color',   $colorsSel); }

  // ====== Orden (compatible MySQL/MariaDB) ======
  switch($orden){
    case 'precio_asc':  $ofertasQuery->orderBy('sale_price','asc'); break;
    case 'precio_desc': $ofertasQuery->orderBy('sale_price','desc'); break;
    case 'recientes':
      if(\Schema::hasColumn('catalog_items','published_at')) $ofertasQuery->orderByDesc('published_at');
      else $ofertasQuery->latest('id');
      break;
    case 'nombre':      $ofertasQuery->orderBy('name'); break;
    case 'mejor_descuento':
    default:
      // Empuja price nulo/0 al final y ordena por % de descuento desc
      $ofertasQuery->orderByRaw("
        CASE WHEN price IS NULL OR price = 0 THEN 1 ELSE 0 END ASC,
        (1 - (sale_price / NULLIF(price,0))) DESC
      ");
      break;
  }

  // ====== Datos para filtros (sidebar/sheet) ======
  $brands = $brandCol
    ? \App\Models\CatalogItem::query()->select($brandCol)
        ->whereNotNull($brandCol)->where($brandCol,'<>','')
        ->distinct()->orderBy($brandCol)->pluck($brandCol)->values()
    : collect([]);

  $colors = $hasColor
    ? \App\Models\CatalogItem::query()->select('color')
        ->whereNotNull('color')->where('color','<>','')
        ->distinct()->orderBy('color')->pluck('color')->values()
    : collect([]);

  $sizes = $sizeCol
    ? \App\Models\CatalogItem::query()->select($sizeCol)
        ->whereNotNull($sizeCol)->where($sizeCol,'<>','')
        ->distinct()->orderBy($sizeCol)->pluck($sizeCol)->values()
    : collect([]);

  // Rango de precios sugerido
  $pricesAgg = \App\Models\CatalogItem::query()
      ->whereNotNull('sale_price')->whereColumn('sale_price','<','price')
      ->selectRaw('MIN(sale_price) as minp, MAX(sale_price) as maxp')->first();
  $suggestMin = $pricesAgg?->minp ? floor($pricesAgg->minp) : 0;
  $suggestMax = $pricesAgg?->maxp ? ceil($pricesAgg->maxp)  : 0;

  // ====== Paginación ======
  $ofertas = $ofertasQuery->paginate(24)->withQueryString();

  // Helper % off
  $pct = fn($p) => ($p->price > 0 && $p->sale_price !== null && $p->sale_price < $p->price)
      ? max(1, round(100 - (($p->sale_price / $p->price) * 100))) : null;
@endphp

{{-- ===== HERO sin margen ni padding superior ===== --}}
<section id="offers-hero" class="hero--compact">
  <div class="wrap wrap--topless">
    <h1>🔖 Ofertas activas</h1>
    <p>Descuentos en papelería y oficina. Filtra por {{ $brandCol ? 'marca, ' : '' }}precio, {{ $sizeCol ? 'tamaño, ' : '' }}{{ $hasColor ? 'color, ' : '' }}y más.</p>
  </div>
</section>

{{-- ========== CONTROLES SUPERIORES (sin buscador) ========== --}}
<section id="offers-controls">
  <form class="controls wrap" method="GET" action="{{ url()->current() }}">
    <div class="row">
      <div class="field">
        <label class="lbl">Ordenar</label>
        <select name="orden" onchange="this.form.submit()">
          <option value="mejor_descuento" @selected($orden==='mejor_descuento')>Mejor descuento</option>
          <option value="precio_asc" @selected($orden==='precio_asc')>Precio: menor a mayor</option>
          <option value="precio_desc" @selected($orden==='precio_desc')>Precio: mayor a menor</option>
          <option value="recientes" @selected($orden==='recientes')>Más recientes</option>
          <option value="nombre" @selected($orden==='nombre')>Nombre (A-Z)</option>
        </select>
      </div>

      <div class="field">
        <label class="lbl">% mín.</label>
        <select name="min_off" onchange="this.form.submit()">
          @foreach([0,5,10,15,20,25,30,40,50] as $opt)
            <option value="{{ $opt }}" @selected($minOff===$opt)>{{ $opt }}%</option>
          @endforeach
        </select>
      </div>

      <label class="switch">
        <input type="checkbox" name="stock" value="1" @checked($inStock) onchange="this.form.submit()">
        <span class="slider"></span>
        <span class="txt">Sólo con stock</span>
      </label>

      @if(request()->query())
        <a class="btn btn-clear" href="{{ url()->current() }}">Limpiar</a>
      @endif
    </div>
  </form>
</section>

{{-- ========== LAYOUT: SIDEBAR + GRID ========== --}}
<section id="offers-layout">
  <div class="wrap grid-layout">
    {{-- ===== Sidebar filtros (DESKTOP) ===== --}}
    <aside class="sidebar">
      <form id="filters-form" method="GET" action="{{ url()->current() }}">
        {{-- Preserva controles superiores al aplicar filtros --}}
        <input type="hidden" name="q" value="{{ $q }}">
        <input type="hidden" name="orden" value="{{ $orden }}">
        <input type="hidden" name="min_off" value="{{ $minOff }}">
        @if($inStock) <input type="hidden" name="stock" value="1">@endif

        {{-- RANGO DE PRECIO --}}
        <div class="fblock">
          <h4>Precio</h4>
          <div class="price-range">
            <div>
              <label>Min</label>
              <input type="number" name="pmin" min="0" step="1" placeholder="{{ $suggestMin }}" value="{{ request('pmin') }}">
            </div>
            <div>
              <label>Max</label>
              <input type="number" name="pmax" min="0" step="1" placeholder="{{ $suggestMax }}" value="{{ request('pmax') }}">
            </div>
          </div>
        </div>

        {{-- MARCA --}}
        @if($brandCol && $brands->count())
        <div class="fblock">
          <h4>{{ Str::ucfirst($brandCol) }}</h4>
          <div class="chips">
            @foreach($brands as $b)
              @php $checked = in_array($b, $brandsSel); @endphp
              <label class="chip {{ $checked ? 'on' : '' }}">
                <input type="checkbox" name="brand[]" value="{{ $b }}" @checked($checked)>
                <span>{{ $b }}</span>
              </label>
            @endforeach
          </div>
        </div>
        @endif

        {{-- TAMAÑO --}}
        @if($sizeCol && $sizes->count())
        <div class="fblock">
          <h4>{{ $sizeCol === 'tamano' ? 'Tamaño' : 'Size' }}</h4>
          <div class="chips">
            @foreach($sizes as $s)
              @php $checked = in_array($s, $sizesSel); @endphp
              <label class="chip {{ $checked ? 'on' : '' }}">
                <input type="checkbox" name="size[]" value="{{ $s }}" @checked($checked)>
                <span>{{ $s }}</span>
              </label>
            @endforeach
          </div>
        </div>
        @endif

        {{-- COLOR --}}
        @if($hasColor && $colors->count())
        <div class="fblock">
          <h4>Color</h4>
          <div class="chips colors">
            @foreach($colors as $c)
              @php $checked = in_array($c, $colorsSel); @endphp
              <label class="chip color {{ $checked ? 'on' : '' }}">
                <input type="checkbox" name="color[]" value="{{ $c }}" @checked($checked)>
                <i style="--swatch: {{ Str::startsWith($c,'#') ? $c : '' }}"></i>
                <span>{{ $c }}</span>
              </label>
            @endforeach
          </div>
        </div>
        @endif

        <div class="factions">
          <button class="btn btn-apply" type="submit">
            <span class="material-symbols-outlined">tune</span> Aplicar filtros
          </button>
          @if(request()->query())
          <a class="btn btn-ghost" href="{{ url()->current() }}">Limpiar</a>
          @endif
        </div>
      </form>
    </aside>

    {{-- ===== GRID de ofertas ===== --}}
    <div class="grid-area">
      @if($ofertas->count())
        <div class="grid-cards">
          @foreach($ofertas as $p)
            @php
              $img = $p->image_url ?: asset('images/placeholder.png');
              $off = $pct($p);
              $desc = $p->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($p->description ?? ''), 110);
              $showUrl = route('web.catalog.show', $p);
              $brandValue = $brandCol ? ($p->{$brandCol} ?? null) : null;
            @endphp
            <article class="card ao">
              <a class="card-hit" href="{{ $showUrl }}" aria-label="Ver {{ $p->name }}"></a>

              <div class="media">
                @if($off)<span class="ribbon">-{{ $off }}%</span>@endif
                <img src="{{ $img }}" alt="{{ $p->name }}" loading="lazy"
                     onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
              </div>

              <div class="body">
                <div class="topline">
                  <span class="brand">{{ $brandCol ? ($brandValue ?: '—') : '—' }}</span>
                  @if(!empty($p->sku))<span class="sku">SKU: {{ $p->sku }}</span>@endif
                </div>

                <h3 class="title">{{ $p->name }}</h3>
                @if($desc)<p class="desc">{{ $desc }}</p>@endif

                <div class="price">
                  <span class="now">${{ number_format($p->sale_price,2) }}</span>
                  <span class="old">${{ number_format($p->price,2) }}</span>
                </div>

                <div class="cta">
                  <form action="{{ route('web.cart.add') }}" method="POST" onclick="event.stopPropagation()">
                    @csrf
                    <input type="hidden" name="catalog_item_id" value="{{ $p->id }}">
                    <button class="btn btn-add" type="submit" title="Agregar al carrito">
                      <span class="material-symbols-outlined">add_shopping_cart</span>
                      Agregar
                    </button>
                  </form>
                </div>
              </div>
            </article>
          @endforeach
        </div>

        {{-- Paginación --}}
        <div class="pagination">
          {{ $ofertas->onEachSide(1)->links() }}
        </div>
      @else
        <div class="empty">
          <span class="material-symbols-outlined">local_offer</span>
          <p>No encontramos ofertas con esos filtros.</p>
          <a class="btn btn-ghost" href="{{ url()->current() }}">Ver todas las ofertas</a>
        </div>
      @endif
    </div>
  </div>
</section>

{{-- ======= BOTÓN FLOTANTE (MÓVIL) para abrir filtros ======= --}}
<button id="open-filters" class="filters-fab">
  <span class="material-symbols-outlined">tune</span> Filtros
</button>

{{-- ======= BOTTOM SHEET (MÓVIL) con IDs únicos para no chocar ======= --}}
<div id="offers-sheet" class="sheet" aria-hidden="true">
  <div class="sheet-backdrop" data-offers-sheet-close></div>
  <div class="sheet-panel" role="dialog" aria-modal="true" aria-labelledby="offers-sheet-title">
    <div class="sheet-handle" aria-hidden="true"></div>
    <div class="sheet-head">
      <h3 id="offers-sheet-title">Filtros</h3>
      <button class="sheet-close" type="button" title="Cerrar" data-offers-sheet-close>
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>

    <form class="sheet-form" method="GET" action="{{ url()->current() }}">
      {{-- preserva controles top --}}
      <input type="hidden" name="q" value="{{ $q }}">
      <input type="hidden" name="orden" value="{{ $orden }}">
      <input type="hidden" name="min_off" value="{{ $minOff }}">
      @if($inStock) <input type="hidden" name="stock" value="1">@endif

      <div class="fblock">
        <h4>Precio</h4>
        <div class="price-range">
          <div>
            <label>Min</label>
            <input type="number" name="pmin" min="0" step="1" placeholder="{{ $suggestMin }}" value="{{ request('pmin') }}">
          </div>
          <div>
            <label>Max</label>
            <input type="number" name="pmax" min="0" step="1" placeholder="{{ $suggestMax }}" value="{{ request('pmax') }}">
          </div>
        </div>
      </div>

      @if($brandCol && $brands->count())
      <div class="fblock">
        <h4>{{ Str::ucfirst($brandCol) }}</h4>
        <div class="chips">
          @foreach($brands as $b)
            @php $checked = in_array($b, $brandsSel); @endphp
            <label class="chip {{ $checked ? 'on' : '' }}">
              <input type="checkbox" name="brand[]" value="{{ $b }}" @checked($checked)>
              <span>{{ $b }}</span>
            </label>
          @endforeach
        </div>
      </div>
      @endif

      @if($sizeCol && $sizes->count())
      <div class="fblock">
        <h4>{{ $sizeCol === 'tamano' ? 'Tamaño' : 'Size' }}</h4>
        <div class="chips">
          @foreach($sizes as $s)
            @php $checked = in_array($s, $sizesSel); @endphp
            <label class="chip {{ $checked ? 'on' : '' }}">
              <input type="checkbox" name="size[]" value="{{ $s }}" @checked($checked)>
              <span>{{ $s }}</span>
            </label>
          @endforeach
        </div>
      </div>
      @endif

      @if($hasColor && $colors->count())
      <div class="fblock">
        <h4>Color</h4>
        <div class="chips colors">
          @foreach($colors as $c)
            @php $checked = in_array($c, $colorsSel); @endphp
            <label class="chip color {{ $checked ? 'on' : '' }}">
              <input type="checkbox" name="color[]" value="{{ $c }}" @checked($checked)>
              <i style="--swatch: {{ Str::startsWith($c,'#') ? $c : '' }}"></i>
              <span>{{ $c }}</span>
            </label>
          @endforeach
        </div>
      </div>
      @endif

      <div class="sheet-actions">
        <button class="btn btn-apply" type="submit">
          <span class="material-symbols-outlined">check</span> Aplicar
        </button>
        <button class="btn btn-ghost" type="button" data-offers-sheet-close>Cancelar</button>
      </div>
    </form>
  </div>
</div>

<style>
  /* ===== Namespace ===== */
  :root{
    --ink:#0e1726; --muted:#6b7280; --bg:#f6f8fc; --line:#e8eef6;
    --brand:#6ea8fe; --ok:#16a34a; --shadow:0 20px 50px rgba(2,8,23,.10);
  }
  #offers-hero, #offers-controls, #offers-layout{ width:100vw; margin-left:calc(50% - 50vw); margin-right:calc(50% - 50vw) }
  .wrap{ max-width:1200px; margin:0 auto; padding: clamp(18px,2.2vw,28px) }
  .wrap--topless{ padding-top:0 !important }           /* sin padding arriba */
  #offers-hero{ margin-top:0 !important }              /* sin margen arriba */

  /* Hero */
  .hero--compact{
    background:
      radial-gradient(1000px 380px at 10% -10%, rgba(110,168,254,.25), transparent 60%),
      radial-gradient(900px 360px at 90% -20%, rgba(255,201,222,.25), transparent 60%),
      var(--bg);
    border-bottom:1px solid var(--line);
  }
  .hero--compact h1{ margin:0 0 6px; font-weight:900; letter-spacing:-.02em; color:var(--ink); font-size:clamp(26px,4vw,40px) }
  .hero--compact p{ margin:0; color:var(--muted) }

  /* Controles superiores */
  #offers-controls{ background:#fff; border-bottom:1px solid var(--line) }
  .controls .row{ display:flex; gap:10px; align-items:center; flex-wrap:wrap }
  .field{ display:flex; align-items:center; gap:8px; background:#fff; border:1px solid var(--line); border-radius:12px; padding:8px 10px }
  .field .lbl{ font-weight:800; color:#0b1220; font-size:.9rem }
  .field select{ border:0; outline:0; background:transparent; font-weight:700 }
  .material-symbols-outlined{ font-size:20px; color:#64748b }
  .switch{ display:inline-flex; align-items:center; gap:10px; padding:8px 10px; border:1px solid var(--line); border-radius:12px; background:#fff }
  .switch .txt{ font-weight:800; color:#0b1220; font-size:.92rem }
  .switch input{ display:none }
  .switch .slider{ width:46px; height:28px; background:#e5e7eb; border-radius:999px; position:relative; cursor:pointer; transition:.2s }
  .switch .slider::after{ content:""; position:absolute; top:3px; left:3px; width:22px; height:22px; background:#fff; border-radius:50%; box-shadow:0 1px 4px rgba(0,0,0,.18); transition:.2s }
  .switch input:checked + .slider{ background:#a7f3d0 }
  .switch input:checked + .slider::after{ transform:translateX(18px) }

  /* Layout: sidebar + grid */
  .grid-layout{ display:grid; grid-template-columns: 280px 1fr; gap: clamp(16px,2vw,22px) }
  @media (max-width: 1024px){ .grid-layout{ grid-template-columns: 1fr } .sidebar{ display:none } }

  .sidebar{
    background:#fff; border:1px solid var(--line); border-radius:16px; padding:14px; box-shadow:var(--shadow);
    position:sticky; top:16px; height:max-content;
  }
  .fblock{ margin-bottom:14px }
  .fblock h4{ margin:0 0 8px; color:#0b1220; font-weight:900; font-size:1rem }
  .price-range{ display:grid; grid-template-columns:1fr 1fr; gap:10px }
  .price-range label{ display:block; font-size:.85rem; color:#475569; margin-bottom:4px; font-weight:700 }
  .price-range input{ width:100%; padding:10px 12px; border:1px solid var(--line); border-radius:10px; outline:0 }

  .chips{ display:flex; flex-wrap:wrap; gap:8px }
  .chip{ display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border:1px dashed var(--line); border-radius:999px; background:#fff; cursor:pointer; user-select:none; box-shadow:0 8px 20px rgba(2,8,23,.04) }
  .chip.on{ background:#eef2ff; border-style:solid; border-color:#c7d2fe }
  .chip input{ display:none }
  .chip.color i{ width:14px; height:14px; border-radius:50%; background:var(--swatch, #e5e7eb); border:1px solid #e5e7eb }

  .factions{ display:flex; gap:8px; margin-top:10px }
  .btn{ display:inline-flex; align-items:center; gap:8px; font-weight:900; border-radius:999px; padding:10px 16px; border:1px solid var(--line); background:#fff; color:#0b1220; cursor:pointer; text-decoration:none; transition: transform .12s ease, box-shadow .2s ease, background .2s ease, color .2s ease }
  .btn:hover{ background:#fff; color:#000; transform: translateY(-1px); box-shadow:0 10px 22px rgba(2,8,23,.10) }
  .btn-ghost{ background:#fff }
  .btn-apply{ border:2px solid #d1fae5; background:#ecfdf5; color:#065f46; box-shadow:0 10px 22px rgba(16,185,129,.18) }
  .btn-clear{ background:#fef3c7; border-color:#fde68a }

  .grid-area{ min-width:0 }
  .grid-cards{ display:grid; gap:16px; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)) }

  /* Card + ANIMACIONES hover */
  .card{
    position:relative;
    border:1px solid var(--line); border-radius:16px; background:#fff; box-shadow:0 14px 34px rgba(2,8,23,.06);
    display:flex; flex-direction:column; overflow:hidden;
    transform: perspective(900px) translateZ(0);
    transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
  }
  .card:hover{
    transform: perspective(900px) translateY(-4px) rotateX(1.2deg) rotateY(.8deg);
    box-shadow:0 22px 52px rgba(2,8,23,.12);
    border-color:#e2e8f0;
  }
  .card-hit{ position:absolute; inset:0; z-index:1 } /* Card clicable */

  .media{ position:relative; background:#f6f8fc; aspect-ratio:1/1; overflow:hidden }
  .media img{ width:100%; height:100%; object-fit:contain; transform: scale(1); transition: transform .35s ease }
  .card:hover .media img{ transform: scale(1.06) }

  .ribbon{
    position:absolute; top:10px; left:10px; z-index:2;
    background:#dcfce7; color:#14532d; border:1px solid rgba(20,83,45,.25);
    padding:.35rem .55rem; font-weight:900; font-size:.85rem; border-radius:999px;
    backdrop-filter:saturate(1.2) blur(4px);
    animation: floaty 3.2s ease-in-out infinite;
  }
  @keyframes floaty{ 0%,100%{ transform:translateY(0) } 50%{ transform:translateY(-3px) } }

  .body{ position:relative; z-index:2; display:flex; flex-direction:column; gap:8px; padding:12px }
  .topline{ display:flex; justify-content:space-between; align-items:center; gap:8px }
  .brand{ font-weight:900; color:#1d4ed8; font-size:.9rem }
  .sku{ color:#64748b; font-size:.85rem }

  .title{ margin:0; color:var(--ink); font-weight:900; line-height:1.25 }
  .desc{ color:#6b7280; margin:2px 0 4px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden }

  .price{ display:flex; align-items:baseline; gap:10px; margin-top:2px }
  .price .now{ color:#16a34a; font-weight:900; font-size:1.08rem }
  .price .old{ color:#94a3b8; text-decoration:line-through; font-weight:700 }

  .cta{ display:flex; gap:8px; align-items:center; margin-top:6px }
  .btn-add{ border:2px solid #d1fae5; background:#ecfdf5; color:#065f46; box-shadow:0 10px 22px rgba(16,185,129,.18) }
  .btn-add:hover{ background:#fff; color:#000 }

  /* Estado vacío y paginación */
  .empty{ display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; border:1px dashed var(--line); border-radius:14px; padding:24px; color:#6b7280; background:#f9fafb }
  .pagination{ display:flex; justify-content:center; padding:14px 0 }
  .pagination nav{ display:inline-block }
  .pagination .hidden{ display:none }

  /* Aparecer al hacer scroll */
  .ao{ opacity:0; transform:translateY(8px); transition: opacity .45s ease, transform .45s ease }
  .ao.in{ opacity:1; transform:none }

  /* FAB móvil */
  .filters-fab{
    position:fixed; right:14px; bottom:14px; z-index:1060;
    display:none; align-items:center; gap:8px;
    padding:12px 16px; border-radius:999px; border:1px solid #e2e8f0;
    background:#ffffff; color:#0b1220; font-weight:900; box-shadow:0 14px 34px rgba(2,8,23,.14);
  }
  @media (max-width:1024px){ .filters-fab{ display:inline-flex } }

  /* Bottom Sheet (namespaced) */
  .sheet{ position:fixed; inset:0; z-index:1100; display:grid; grid-template-rows:1fr auto; pointer-events:none }
  .sheet[aria-hidden="true"]{ display:none }
  .sheet-backdrop{ background:rgba(15,23,42,.35); opacity:0; transition:.25s }
  .sheet-panel{
    align-self:end; background:#fff; border-radius:18px 18px 0 0; border:1px solid #e5e7eb; box-shadow:0 -18px 40px rgba(2,8,23,.22);
    transform: translateY(100%); transition: transform .35s cubic-bezier(.25,.46,.45,.94);
    max-height: 78vh; overflow:auto;
  }
  .sheet[aria-hidden="false"] .sheet-backdrop{ opacity:1 }
  .sheet[aria-hidden="false"] .sheet-panel{ transform: translateY(0) }

  .sheet-handle{ width:50px; height:5px; background:#e5e7eb; border-radius:999px; margin:10px auto 4px }
  .sheet-head{ display:flex; align-items:center; justify-content:space-between; padding: 6px 12px 2px 12px }
  .sheet-head h3{ margin:0; font-weight:900 }
  .sheet-close{ border:0; background:transparent; padding:8px; border-radius:10px }

  .sheet-form{ padding: 8px 12px 16px 12px }
  .sheet-form .fblock{ margin:12px 0 }
  .sheet-actions{ display:flex; gap:10px; padding-top:8px }

  @media (min-width:1025px){ .sheet{ display:none !important } }
</style>

<script>
  // Intersection Observer para fade-in
  (function(){
    const io = 'IntersectionObserver' in window ? new IntersectionObserver((entries, obs)=>{
      entries.forEach(e=>{ if(e.isIntersecting){ e.target.classList.add('in'); obs.unobserve(e.target); } });
    }, {rootMargin:'0px 0px -10% 0px'}) : null;
    document.querySelectorAll('.grid-cards .card.ao').forEach(el=>{ if(io) io.observe(el); else el.classList.add('in'); });
  })();

  // Bottom sheet (móvil) con IDs únicos (sin conflicto con otros sheets del sitio)
  (function(){
    const html    = document.documentElement;
    const openBtn = document.getElementById('open-filters');
    const sheet   = document.getElementById('offers-sheet');
    if(!openBtn || !sheet) return;

    const closeEls = sheet.querySelectorAll('[data-offers-sheet-close]');

    function openSheet(){
      sheet.setAttribute('aria-hidden','false');
      html.style.overflow = 'hidden';
    }
    function closeSheet(){
      sheet.setAttribute('aria-hidden','true');
      html.style.overflow = '';
    }

    openBtn.addEventListener('click', openSheet);
    closeEls.forEach(el => el.addEventListener('click', closeSheet));
    sheet.addEventListener('click', e => {
      if(e.target.classList.contains('sheet-backdrop')) closeSheet();
    });
    document.addEventListener('keydown', e => {
      if(e.key === 'Escape' && sheet.getAttribute('aria-hidden') === 'false') closeSheet();
    });
  })();
</script>
@endsection
