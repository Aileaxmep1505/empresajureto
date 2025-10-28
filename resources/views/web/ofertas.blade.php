{{-- resources/views/web/ofertas.blade.php --}}
@extends('layouts.web')
@section('title','Ofertas')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400..700&display=swap"/>

@php
  use Illuminate\Support\Str;

  // ===== Detección de columnas =====
  $brandCol  = \Schema::hasColumn('catalog_items','brand') ? 'brand'
             : (\Schema::hasColumn('catalog_items','marca') ? 'marca' : null);
  $hasColor  = \Schema::hasColumn('catalog_items','color');
  $hasSize   = \Schema::hasColumn('catalog_items','size');
  $hasTamano = \Schema::hasColumn('catalog_items','tamano');
  $sizeCol   = $hasSize ? 'size' : ($hasTamano ? 'tamano' : null);

  // ===== Parámetros =====
  $q       = trim(request('q',''));
  $orden   = request('orden','mejor_descuento');
  $minOff  = (int) request('min_off', 0);
  $inStock = (int) request('stock', 0);

  // Filtros avanzados
  $brandsSel = array_filter((array) request('brand', []), fn($v) => $v !== '');
  $colorsSel = array_filter((array) request('color', []), fn($v) => $v !== '');
  $sizesSel  = array_filter((array) request('size',  []), fn($v) => $v !== '');
  $pmin      = request('pmin') !== null ? max(0, (float) request('pmin')) : null;
  $pmax      = request('pmax') !== null ? max(0, (float) request('pmax')) : null;

  // ===== Query base: SOLO OFERTAS =====
  $ofertasQuery = \App\Models\CatalogItem::published()
      ->whereNotNull('sale_price')
      ->whereColumn('sale_price','<','price');

  if($q !== ''){
    $ofertasQuery->where(function($qq) use ($q, $brandCol){
      $qq->where('name','like',"%{$q}%")
         ->orWhere('sku','like',"%{$q}%");
      if($brandCol){ $qq->orWhere($brandCol,'like',"%{$q}%"); }
    });
  }

  if($minOff > 0){
    $ratio = 1 - ($minOff/100);
    $ofertasQuery->whereRaw('sale_price / price <= ?', [$ratio]);
  }

  if($inStock && \Schema::hasColumn('catalog_items','stock')){
    $ofertasQuery->where('stock','>',0);
  }

  if(!is_null($pmin)){ $ofertasQuery->where('sale_price','>=',$pmin); }
  if(!is_null($pmax) && $pmax > 0){ $ofertasQuery->where('sale_price','<=',$pmax); }

  if($brandCol && $brandsSel){ $ofertasQuery->whereIn($brandCol, $brandsSel); }
  if($sizeCol  && $sizesSel){  $ofertasQuery->whereIn($sizeCol,  $sizesSel);  }
  if($hasColor && $colorsSel){ $ofertasQuery->whereIn('color',   $colorsSel); }

  // ===== Orden =====
  switch($orden){
    case 'precio_asc':  $ofertasQuery->orderBy('sale_price','asc'); break;
    case 'precio_desc': $ofertasQuery->orderBy('sale_price','desc'); break;
    case 'recientes':
      if(\Schema::hasColumn('catalog_items','published_at')) $ofertasQuery->orderByDesc('published_at');
      else $ofertasQuery->latest('id');
      break;
    case 'nombre': $ofertasQuery->orderBy('name'); break;
    case 'mejor_descuento':
    default:
      $ofertasQuery->orderByRaw("
        CASE WHEN price IS NULL OR price = 0 THEN 1 ELSE 0 END ASC,
        (1 - (sale_price / NULLIF(price,0))) DESC
      ");
      break;
  }

  // ===== Datos para filtros =====
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

  $pricesAgg = \App\Models\CatalogItem::query()
      ->whereNotNull('sale_price')->whereColumn('sale_price','<','price')
      ->selectRaw('MIN(sale_price) as minp, MAX(sale_price) as maxp')->first();
  $suggestMin = $pricesAgg?->minp ? floor($pricesAgg->minp) : 0;
  $suggestMax = $pricesAgg?->maxp ? ceil($pricesAgg->maxp)  : 0;

  $ofertas = $ofertasQuery->paginate(24)->withQueryString();

  $pct = fn($p) => ($p->price > 0 && $p->sale_price !== null && $p->sale_price < $p->price)
      ? max(1, round(100 - (($p->sale_price / $p->price) * 100))) : null;
@endphp

{{-- DATA para AJAX favoritos: plantilla de URL --}}
<div id="ofr"
     data-fav-url-template="{{ route('favoritos.toggle', ['item' => '__ID__']) }}"
     style="font-family:'Outfit', system-ui, -apple-system, Segoe UI, Roboto, 'Helvetica Neue', Arial, 'Noto Sans', 'Apple Color Emoji','Segoe UI Emoji';">

  {{-- HERO --}}
  <section id="ofr-hero" class="ofr-hero">
    <div class="ofr-wrap ofr-wrap--topless">
      <p class="ofr-eyebrow">OFERTAS</p>
      <h1 class="ofr-display">Las <span class="accent">ofertas</span> no deberían esperar</h1>
      <p class="ofr-sub">Descuentos en papelería y oficina con estilo enterprise: rápidos, claros y listos para tu carrito.</p>
      <a href="#ofr-layout" class="ofr-cta-hero">Hablemos de tu proyecto</a>
    </div>
  </section>

  {{-- CONTROLES SUPERIORES --}}
  <section id="ofr-controls">
    <form class="ofr-controls ofr-wrap" method="GET" action="{{ url()->current() }}">
      <div class="ofr-row">
        <div class="ofr-field">
          <label class="ofr-lbl">Ordenar</label>
          <select name="orden" onchange="this.form.submit()">
            <option value="mejor_descuento" @selected($orden==='mejor_descuento')>Mejor descuento</option>
            <option value="precio_asc" @selected($orden==='precio_asc')>Precio: menor a mayor</option>
            <option value="precio_desc" @selected($orden==='precio_desc')>Precio: mayor a menor</option>
            <option value="recientes" @selected($orden==='recientes')>Más recientes</option>
            <option value="nombre" @selected($orden==='nombre')>Nombre (A-Z)</option>
          </select>
        </div>

        <div class="ofr-field">
          <label class="ofr-lbl">% mín.</label>
          <select name="min_off" onchange="this.form.submit()">
            @foreach([0,5,10,15,20,25,30,40,50] as $opt)
              <option value="{{ $opt }}" @selected($minOff===$opt)>{{ $opt }}%</option>
            @endforeach
          </select>
        </div>

        <label class="ofr-switch">
          <input type="checkbox" name="stock" value="1" @checked($inStock) onchange="this.form.submit()">
          <span class="ofr-slider"></span>
          <span class="ofr-txt">Sólo con stock</span>
        </label>

        @if(request()->query())
          <a class="ofr-btn ofr-btn-clear" href="{{ url()->current() }}">Limpiar</a>
        @endif
      </div>
    </form>
  </section>

  {{-- LAYOUT: Sidebar + Grid --}}
  <section id="ofr-layout">
    <div class="ofr-wrap ofr-grid-layout">
      {{-- Sidebar (desktop) --}}
      <aside class="ofr-sidebar">
        <form id="ofr-filters-form" method="GET" action="{{ url()->current() }}">
          <input type="hidden" name="q" value="{{ $q }}">
          <input type="hidden" name="orden" value="{{ $orden }}">
          <input type="hidden" name="min_off" value="{{ $minOff }}">
          @if($inStock) <input type="hidden" name="stock" value="1">@endif

          <div class="ofr-fblock">
            <h4>Precio</h4>
            <div class="ofr-price-range">
              <div><label>Min</label><input type="number" name="pmin" min="0" step="1" placeholder="{{ $suggestMin }}" value="{{ request('pmin') }}"></div>
              <div><label>Max</label><input type="number" name="pmax" min="0" step="1" placeholder="{{ $suggestMax }}" value="{{ request('pmax') }}"></div>
            </div>
          </div>

          @if($brandCol && $brands->count())
          <div class="ofr-fblock">
            <h4>{{ Str::ucfirst($brandCol) }}</h4>
            <div class="ofr-chips">
              @foreach($brands as $b)
                @php $checked = in_array($b, $brandsSel); @endphp
                <label class="ofr-chip {{ $checked ? 'on' : '' }}">
                  <input type="checkbox" name="brand[]" value="{{ $b }}" @checked($checked)><span>{{ $b }}</span>
                </label>
              @endforeach
            </div>
          </div>
          @endif

          @if($sizeCol && $sizes->count())
          <div class="ofr-fblock">
            <h4>{{ $sizeCol === 'tamano' ? 'Tamaño' : 'Size' }}</h4>
            <div class="ofr-chips">
              @foreach($sizes as $s)
                @php $checked = in_array($s, $sizesSel); @endphp
                <label class="ofr-chip {{ $checked ? 'on' : '' }}">
                  <input type="checkbox" name="size[]" value="{{ $s }}" @checked($checked)><span>{{ $s }}</span>
                </label>
              @endforeach
            </div>
          </div>
          @endif

          @if($hasColor && $colors->count())
          <div class="ofr-fblock">
            <h4>Color</h4>
            <div class="ofr-chips ofr-colors">
              @foreach($colors as $c)
                @php $checked = in_array($c, $colorsSel); @endphp
                <label class="ofr-chip ofr-color {{ $checked ? 'on' : '' }}">
                  <input type="checkbox" name="color[]" value="{{ $c }}" @checked($checked)>
                  <i style="--ofr-swatch: {{ Str::startsWith($c,'#') ? $c : '' }}"></i><span>{{ $c }}</span>
                </label>
              @endforeach
            </div>
          </div>
          @endif

          <div class="ofr-factions">
            <button class="ofr-btn ofr-btn-apply" type="submit"><span class="material-symbols-outlined">tune</span> Aplicar filtros</button>
            @if(request()->query()) <a class="ofr-btn ofr-btn-ghost" href="{{ url()->current() }}">Limpiar</a> @endif
          </div>
        </form>
      </aside>

      {{-- Grid --}}
      <div class="ofr-grid-area">
        @if($ofertas->count())
          <div class="ofr-grid-cards">
            @foreach($ofertas as $p)
              @php
                $img = $p->image_url ?: asset('images/placeholder.png');
                $off = $pct($p);
                $desc = $p->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($p->description ?? ''), 110);
                $showUrl = route('web.catalog.show', $p);
                $brandValue = $brandCol ? ($p->{$brandCol} ?? null) : null;
                $isFav = auth()->check() ? (bool) data_get($p,'is_favorite', false) : false;
              @endphp
              <article class="ofr-card ofr-ao">
                <a class="ofr-card-hit" href="{{ $showUrl }}" aria-label="Ver {{ $p->name }}"></a>

                <div class="ofr-media">
                  @if($off)<span class="ofr-ribbon">-{{ $off }}%</span>@endif
                  <img src="{{ $img }}" alt="{{ $p->name }}" loading="lazy"
                       onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
                </div>

                <div class="ofr-body">
                  <div class="ofr-topline">
                    <span class="ofr-brand">{{ $brandCol ? ($brandValue ?: '—') : '—' }}</span>
                    @if(!empty($p->sku))<span class="ofr-sku">SKU: {{ $p->sku }}</span>@endif
                  </div>

                  <h3 class="ofr-title">{{ $p->name }}</h3>
                  @if($desc)<p class="ofr-desc">{{ $desc }}</p>@endif

                  <div class="ofr-price">
                    <span class="ofr-now">${{ number_format($p->sale_price,2) }}</span>
                    <span class="ofr-old">${{ number_format($p->price,2) }}</span>
                  </div>

                  <div class="ofr-cta" onclick="event.stopPropagation()">
                    {{-- Favoritos --}}
                    <button type="button" class="fav-btn" data-id="{{ $p->id }}"
                            aria-pressed="{{ $isFav ? 'true':'false' }}" aria-label="Favorito">
                      <svg class="heart" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 21s-6.2-3.65-9.33-7.06C.48 11.57 1.02 8.5 3.3 6.9a5 5 0 0 1 6.2.44L12 9.7l2.5-2.36a5 5 0 0 1 6.2-.44c2.28 1.6 2.82 4.67.63 7.04C18.2 17.35 12 21 12 21z"/>
                      </svg>
                    </button>

                    {{-- Carrito (AJAX + animado) --}}
                    <form class="ofr-cart" action="{{ route('web.cart.add') }}" method="POST" style="display:inline-flex;gap:6px;align-items:center">
                      @csrf
                      <input type="hidden" name="catalog_item_id" value="{{ $p->id }}">

                      <button type="submit" class="add-to-cart add-to-cart--sm" data-submit-delay="900" title="Agregar al carrito">
                        <span>Agregar</span>
                        <svg class="morph" viewBox="0 0 64 13" aria-hidden="true">
                          <path d="M0 12C6 12 17 12 32 12C47.9024 12 58 12 64 12V13H0V12Z" />
                        </svg>
                        <div class="shirt" aria-hidden="true">
                          {{-- Camisa BLANCA --}}
                          <svg class="first" viewBox="0 0 24 24"><path d="M5 3L9 1.5C9 1.5 10.69 3 12 3C13.31 3 15 1.5 15 1.5L19 3L22.5 8L19.5 10.5L19 9.5L17.18 18.61C17.06 19.19 16.78 19.72 16.34 20.12C15.43 20.92 13.71 22.31 12 23C10.29 22.31 8.57 20.92 7.66 20.12C7.22 19.72 6.94 19.19 6.82 18.61L5 9.5L4.5 10.5L1.5 8L5 3Z"/></svg>
                          <svg class="second" viewBox="0 0 24 24"><path d="M5 3L9 1.5C9 1.5 10.69 3 12 3C13.31 3 15 1.5 15 1.5L19 3L22.5 8L19.5 10.5L19 9.5L17.18 18.61C17.06 19.19 16.78 19.72 16.34 20.12C15.43 20.92 13.71 22.31 12 23C10.29 22.31 8.57 20.92 7.66 20.12C7.22 19.72 6.94 19.19 6.82 18.61L5 9.5L4.5 10.5L1.5 8L5 3Z"/></svg>
                        </div>
                        <div class="cart" aria-hidden="true">
                          <svg viewBox="0 0 36 26">
                            <path d="M1 2.5H6L10 18.5H25.5L28.5 7.5L7.5 7.5" class="shape"/>
                            <path d="M11.5 25C12.6046 25 13.5 24.1046 13.5 23C13.5 21.8954 12.6046 21 11.5 21C10.3954 21 9.5 21.8954 9.5 23C9.5 24.1046 10.3954 25 11.5 25Z" class="wheel"/>
                            <path d="M24 25C25.1046 25 26 24.1046 26 23C26 21.8954 25.1046 21 24 21C22.8954 21 22 21.8954 22 23C22 24.1046 22.8954 25 24 25Z" class="wheel"/>
                            <path d="M14.5 13.5L16.5 15.5L21.5 10.5" class="tick"/>
                          </svg>
                        </div>
                      </button>
                    </form>
                  </div>
                </div>
              </article>
            @endforeach
          </div>

          <div class="ofr-pagination">
            {{ $ofertas->onEachSide(1)->links() }}
          </div>
        @else
          <div class="ofr-empty">
            <span class="material-symbols-outlined">local_offer</span>
            <p>No encontramos ofertas con esos filtros.</p>
            <a class="ofr-btn ofr-btn-ghost" href="{{ url()->current() }}">Ver todas las ofertas</a>
          </div>
        @endif
      </div>
    </div>
  </section>
</div>

{{-- GSAP sólo para la animación del botón (no genera toasts) --}}
<script defer src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>

<style>
  /* ===== Fondo de toda la página SIN romper tu layout ===== */
  body.ofertas-bg{ position:relative; }
  body.ofertas-bg::before{
    content:""; position:fixed; inset:0; z-index:-1; pointer-events:none;
    background:
      radial-gradient(1200px 700px at 15% -10%, rgba(148,163,184,.45), transparent 60%),
      radial-gradient(1200px 700px at 90% -20%, rgba(148,163,184,.35), transparent 60%),
      linear-gradient(180deg, #cfd6df 0%, #e2d8cf 45%, #f6c2a2 70%, #f0a472 100%);
  }

  /* === Encapsulado en #ofr === */
  #ofr{
    --ofr-ink:#0e1726; --ofr-muted:#6b7280; --ofr-line:#e8eef6;
    --ofr-shadow:0 20px 50px rgba(2,8,23,.10); --ofr-radius:22px;
  }
  #ofr .material-symbols-outlined{ font-size:20px; color:#64748b }
  #ofr-hero, #ofr-controls, #ofr-layout{ width:100vw; margin-left:calc(50% - 50vw); margin-right:calc(50% - 50vw) }
  #ofr .ofr-wrap{ max-width:1200px; margin:0 auto; padding: clamp(18px,2.2vw,28px) }
  #ofr .ofr-wrap--topless{ padding-top:0 !important }

  /* Hero */
  #ofr .ofr-eyebrow{ letter-spacing:.38em; font-weight:700; color:#1f2937; opacity:.75; margin: 4px 0 20px }
  #ofr .ofr-display{ margin:0; font-weight:900; letter-spacing:-.02em; color:#0b1220; font-size:clamp(40px,6vw,86px); line-height:1.02 }
  #ofr .ofr-display .accent{ color:#eaff8f }
  #ofr .ofr-sub{ margin:16px 0 22px; color:#374151; font-size:clamp(15px,1.2vw,18px) }
  #ofr .ofr-cta-hero{
    display:inline-flex; align-items:center; gap:10px; padding:14px 22px; border-radius:999px;
    background:#eaff8f; color:#0b1220; font-weight:900; text-decoration:none; border:2px solid rgba(0,0,0,.08);
    box-shadow:0 16px 40px rgba(2,8,23,.10);
  }
  #ofr .ofr-cta-hero:hover{ background:#fff; }

  /* Controles */
  #ofr-controls{ background:#ffffffb8; backdrop-filter:saturate(1.1) blur(6px); border-bottom:1px solid var(--ofr-line) }
  #ofr .ofr-controls .ofr-row{ display:flex; gap:10px; align-items:center; flex-wrap:wrap }
  #ofr .ofr-field{ display:flex; align-items:center; gap:8px; background:#fff; border:1px solid var(--ofr-line); border-radius:12px; padding:8px 10px }
  #ofr .ofr-lbl{ font-weight:800; color:#0b1220; font-size:.9rem }
  #ofr .ofr-field select{ border:0; outline:0; background:transparent; font-weight:700 }
  #ofr .ofr-switch{ display:inline-flex; align-items:center; gap:10px; padding:8px 10px; border:1px solid var(--ofr-line); border-radius:12px; background:#fff }
  #ofr .ofr-switch .ofr-txt{ font-weight:800; color:#0b1220; font-size:.92rem }
  #ofr .ofr-switch input{ display:none }
  #ofr .ofr-slider{ width:46px; height:28px; background:#e5e7eb; border-radius:999px; position:relative; cursor:pointer; transition:.2s }
  #ofr .ofr-slider::after{ content:""; position:absolute; top:3px; left:3px; width:22px; height:22px; background:#fff; border-radius:50%; box-shadow:0 1px 4px rgba(0,0,0,.18); transition:.2s }
  #ofr .ofr-switch input:checked + .ofr-slider{ background:#a7f3d0 }
  #ofr .ofr-switch input:checked + .ofr-slider::after{ transform:translateX(18px) }

  /* Layout */
  #ofr .ofr-grid-layout{ display:grid; grid-template-columns: 300px 1fr; gap: clamp(16px,2vw,22px) }
  @media (max-width: 1024px){ #ofr .ofr-grid-layout{ grid-template-columns: 1fr } #ofr .ofr-sidebar{ display:none } }

  #ofr .ofr-sidebar{
    background:#fff; border:1px solid var(--ofr-line); border-radius:var(--ofr-radius); padding:14px; box-shadow:var(--ofr-shadow);
    position:sticky; top:16px; height:max-content;
  }
  #ofr .ofr-fblock{ margin-bottom:14px }
  #ofr .ofr-fblock h4{ margin:0 0 8px; color:#0b1220; font-weight:900; font-size:1rem }
  #ofr .ofr-price-range{ display:grid; grid-template-columns:1fr 1fr; gap:10px }
  #ofr .ofr-price-range label{ display:block; font-size:.85rem; color:#475569; margin-bottom:4px; font-weight:700 }
  #ofr .ofr-price-range input{ width:100%; padding:10px 12px; border:1px solid var(--ofr-line); border-radius:12px; outline:0; background:#fff }

  #ofr .ofr-chips{ display:flex; flex-wrap:wrap; gap:8px }
  #ofr .ofr-chip{ display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border:1px dashed var(--ofr-line); border-radius:999px; background:#fff; cursor:pointer; user-select:none; box-shadow:0 8px 20px rgba(2,8,23,.04) }
  #ofr .ofr-chip.on{ background:#eef2ff; border-style:solid; border-color:#c7d2fe }
  #ofr .ofr-chip input{ display:none }
  #ofr .ofr-color i{ width:14px; height:14px; border-radius:50%; background:var(--ofr-swatch, #e5e7eb); border:1px solid #e5e7eb }

  #ofr .ofr-factions{ display:flex; gap:8px; margin-top:10px }

  #ofr .ofr-btn{
    display:inline-flex; align-items:center; gap:8px; font-weight:900; border-radius:999px; padding:10px 16px;
    border:1px solid var(--ofr-line); background:#fff; color:#0b1220; cursor:pointer; text-decoration:none;
    transition: transform .12s ease, box-shadow .2s ease, background .2s ease, color .2s ease;
  }
  #ofr .ofr-btn:hover{ background:#fff; color:#000; transform: translateY(-1px); box-shadow:0 10px 22px rgba(2,8,23,.10) }
  #ofr .ofr-btn-ghost{ background:#fff }
  #ofr .ofr-btn-apply{ border:2px solid #d1fae5; background:#ecfdf5; color:#065f46; box-shadow:0 10px 22px rgba(16,185,129,.18) }
  #ofr .ofr-btn-clear{ background:#fef3c7; border-color:#fde68a }

  #ofr .ofr-grid-area{ min-width:0 }
  #ofr .ofr-grid-cards{ display:grid; gap:16px; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)) }

  /* Card */
  #ofr .ofr-card{
    position:relative; border:1px solid var(--ofr-line); border-radius:var(--ofr-radius); background:#fff; box-shadow:0 14px 34px rgba(2,8,23,.06);
    display:flex; flex-direction:column; overflow:hidden; transform: perspective(900px) translateZ(0);
    transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
  }
  #ofr .ofr-card:hover{ transform: perspective(900px) translateY(-4px) rotateX(1.2deg) rotateY(.8deg); box-shadow:0 22px 52px rgba(2,8,23,.12); border-color:#e2e8f0 }
  #ofr .ofr-card-hit{ position:absolute; inset:0; z-index:1 }
  #ofr .ofr-media{ position:relative; background:#f6f8fc; aspect-ratio:1/1; overflow:hidden }
  #ofr .ofr-media img{ width:100%; height:100%; object-fit:contain; transition: transform .35s ease }
  #ofr .ofr-card:hover .ofr-media img{ transform: scale(1.06) }
  #ofr .ofr-ribbon{ position:absolute; top:10px; left:10px; z-index:2; background:#dcfce7; color:#14532d; border:1px solid rgba(20,83,45,.25);
    padding:.35rem .55rem; font-weight:900; font-size:.85rem; border-radius:999px; backdrop-filter:saturate(1.2) blur(4px) }

  #ofr .ofr-body{ position:relative; z-index:2; display:flex; flex-direction:column; gap:8px; padding:12px }
  #ofr .ofr-topline{ display:flex; justify-content:space-between; align-items:center; gap:8px }
  #ofr .ofr-brand{ font-weight:900; color:#1d4ed8; font-size:.9rem }
  #ofr .ofr-sku{ color:#64748b; font-size:.85rem }

  #ofr .ofr-title{ margin:0; color:#0e1726; font-weight:900; line-height:1.25 }
  #ofr .ofr-desc{ color:#6b7280; margin:2px 0 4px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden }

  #ofr .ofr-price{ display:flex; align-items:baseline; gap:10px; margin-top:2px }
  #ofr .ofr-now{ color:#16a34a; font-weight:900; font-size:1.08rem }
  #ofr .ofr-old{ color:#94a3b8; text-decoration:line-through; font-weight:700 }

  #ofr .ofr-cta{ display:flex; gap:8px; align-items:center; margin-top:8px }

  /* Empty / Pagination */
  #ofr .ofr-empty{ display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px;
    border:1px dashed var(--ofr-line); border-radius:var(--ofr-radius); padding:24px; color:#6b7280; background:#f9fafb }
  #ofr .ofr-pagination{ display:flex; justify-content:center; padding:14px 0 }
  #ofr .ofr-pagination nav{ display:inline-block }
  #ofr .ofr-pagination .hidden{ display:none }

  /* === Botón "add-to-cart" animado (camisa BLANCA) === */
  #ofr .add-to-cart{
    --background-default:#17171B; --background-hover:#0A0A0C; --background-scale:1;
    --text-color:#fff; --text-o:1; --text-x:12px;
    --cart:#fff; --cart-x:-48px; --cart-y:0px; --cart-rotate:0deg; --cart-scale:.75;
    --cart-clip:0px; --cart-clip-x:0px; --cart-tick-offset:10px; --cart-tick-color:#22c55e;
    --shirt-y:-16px; --shirt-scale:0; --shirt-color:#ffffff; --shirt-logo:#17171B;
    --shirt-second-y:24px; --shirt-second-color:#ffffff; --shirt-second-logo:#17171B;
    -webkit-tap-highlight-color:transparent; appearance:none; outline:0; background:none; border:0;
    padding:12px 0; width:164px; margin:0; cursor:pointer; position:relative; font:inherit; border-radius:12px;
  }
  #ofr .add-to-cart:before{ content:""; position:absolute; inset:0; border-radius:12px; transition:background .25s;
    background:var(--background,var(--background-default)); transform:scaleX(var(--background-scale)) translateZ(0) }
  #ofr .add-to-cart:not(.active):hover{ --background:var(--background-hover); }
  #ofr .add-to-cart>span{ position:relative; z-index:1; display:block; text-align:center; font-size:14px; font-weight:800; line-height:24px; color:var(--text-color);
    opacity:var(--text-o); transform:translateX(var(--text-x)) translateZ(0) }
  #ofr .add-to-cart svg{ display:block; stroke-linecap:round; stroke-linejoin:round }
  #ofr .add-to-cart .morph{ width:64px; height:13px; position:absolute; left:50%; top:-12px; margin-left:-32px;
    fill:var(--background,var(--background-default)); transition:fill .25s; pointer-events:none }
  #ofr .add-to-cart .shirt, #ofr .add-to-cart .cart{ pointer-events:none; position:absolute; left:50% }
  #ofr .add-to-cart .shirt{ top:0; margin:-12px 0 0 -12px; transform-origin:50% 100%; transform:translateY(var(--shirt-y)) scale(var(--shirt-scale)) }
  #ofr .add-to-cart .shirt svg{ width:24px; height:24px }
  #ofr .add-to-cart .shirt svg path{ fill:var(--shirt-color) }
  #ofr .add-to-cart .shirt svg g path{ fill:var(--shirt-logo) }
  #ofr .add-to-cart .shirt svg.second{ position:absolute; top:0; left:0; clip-path:polygon(0 var(--shirt-second-y),24px var(--shirt-second-y),24px 24px,0 24px) }
  #ofr .add-to-cart .shirt svg.second path{ fill:var(--shirt-second-color) }
  #ofr .add-to-cart .shirt svg.second g path{ fill:var(--shirt-second-logo) }
  #ofr .add-to-cart .cart{ width:36px; height:26px; top:10px; margin-left:-18px;
    transform:translate(var(--cart-x),var(--cart-y)) rotate(var(--cart-rotate)) scale(var(--cart-scale)) translateZ(0) }
  #ofr .add-to-cart .cart:before{
    content:""; width:22px; height:12px; position:absolute; left:7px; top:7px; background:var(--cart);
    clip-path:polygon(0 0,22px 0, calc(22px - var(--cart-clip-x)) var(--cart-clip), var(--cart-clip-x) var(--cart-clip));
  }
  #ofr .add-to-cart .cart .shape{ fill:none; stroke:var(--cart); stroke-width:2 }
  #ofr .add-to-cart .cart .wheel{ fill:none; stroke:var(--cart); stroke-width:1.5 }
  #ofr .add-to-cart .cart .tick{ fill:none; stroke:var(--cart-tick-color); stroke-width:2; stroke-dasharray:10px; stroke-dashoffset:var(--cart-tick-offset) }
  #ofr .add-to-cart--sm{ transform:scale(.92); transform-origin:left center }

  /* Favoritos (SVG) */
  .fav-btn{
    position:relative; display:inline-grid; place-items:center;
    width:42px; height:40px; border-radius:12px; border:1px solid #e5e7eb;
    background:#fff; box-shadow:0 8px 20px rgba(2,8,23,.06); cursor:pointer; padding:0;
  }
  .fav-btn .heart{ width:22px; height:22px; display:block;
    fill:#fff; stroke:#ef4444; stroke-width:2; transition:transform .12s ease, fill .15s ease, stroke .15s ease }
  .fav-btn[aria-pressed="true"] .heart{ fill:#ef4444; stroke:#ef4444 }
  .fav-btn:active .heart{ transform:scale(.92) }
</style>

<script>
(function(){
  const root = document.getElementById('ofr');
  if(!root) return;

  /* ===== Fondo a toda la página sin afectar tu footer/header ===== */
  function setOfertasBg(on){
    const b = document.body; if(!b) return;
    if(on) b.classList.add('ofertas-bg'); else b.classList.remove('ofertas-bg');
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ()=>setOfertasBg(true));
  else setOfertasBg(true);
  window.addEventListener('pageshow', ()=>setOfertasBg(true));
  window.addEventListener('pagehide', ()=>setOfertasBg(false));

  /* ===== Helpers ===== */
  function csrf(){ return (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')) || '' }
  function updateBadge(count){
    if (window.updateCartBadge) { window.updateCartBadge(count); return; }
    const el = document.querySelector('[data-cart-badge]'); if (el) el.textContent = String(count||0);
  }

  /* ===== Botón animado (evita doble click) ===== */
  function bindAnimatedCartButtons(){
    root.querySelectorAll('#ofr .add-to-cart').forEach(btn=>{
      if (btn.dataset.bound) return; btn.dataset.bound = "1";

      btn.addEventListener('click', (e) => {
        e.preventDefault(); e.stopPropagation();
        if (btn.dataset.busy === '1') return;        // evita doble click
        btn.dataset.busy = '1';

        const form = btn.closest('form'); if(!form) return;

        // Animación
        if (window.gsap){
          btn.classList.add('active'); btn.style.pointerEvents = 'none'; btn.style.setProperty('--text-o', 0);
          gsap.to(btn, { keyframes:[
            { '--background-scale': .97, duration: .10 },
            { '--background-scale': 1, duration: 1.0, ease: 'elastic.out(1,.6)' }
          ]});
          gsap.to(btn, { keyframes:[
            { '--shirt-scale': 1, '--shirt-y': '-42px', '--cart-x': '0px', '--cart-scale': 1, duration: .35, ease: 'power1.in' },
            { '--shirt-y': '16px', '--shirt-scale': .9, duration: .25 },
            { '--shirt-scale': 0, duration: .25 }
          ]});
          gsap.to(btn, { '--shirt-second-y': '0px', delay: .7, duration: .1 });
          gsap.to(btn, { keyframes:[
            { '--cart-clip': '12px', '--cart-clip-x': '3px', delay: .78, duration: .06 },
            { '--cart-y': '2px', duration: .10 },
            { '--cart-tick-offset': '0px', '--cart-y': '0px', duration: .18 },
            { '--cart-x': '52px', '--cart-rotate': '-15deg', duration: .16 },
            { '--cart-x': '104px', '--cart-rotate': '0deg', duration: .16, onComplete(){ btn.style.setProperty('--cart-x','-104px'); } },
            { '--text-o': 1, '--text-x': '12px', '--cart-x': '-48px', '--cart-scale': .75, duration: .22 }
          ], onComplete(){ btn.classList.remove('active'); btn.style.pointerEvents=''; }});
        }

        // Dispara submit (interceptado por AJAX abajo)
        if (typeof form.requestSubmit === 'function') form.requestSubmit();
        else form.dispatchEvent(new Event('submit', { cancelable:true, bubbles:true }));
      });
    });
  }

  /* ===== Carrito AJAX (sin toasts locales) ===== */
  function bindAjaxCart(){
    root.querySelectorAll('form.ofr-cart').forEach(form=>{
      if (form.dataset.bound) return; form.dataset.bound = "1";

      form.addEventListener('submit', async (ev)=>{
        ev.preventDefault(); ev.stopPropagation();

        if (form.dataset.submitting === '1') return;   // evita duplicado
        form.dataset.submitting = '1';

        const submitBtn = form.querySelector('.add-to-cart');
        try{
          const fd = new FormData(form);
          const res = await fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf(), 'Accept':'application/json' },
            body: fd,
          });

          const data = await res.json().catch(()=>({}));
          if (res.ok && data.ok !== false){
            updateBadge(data?.totals?.count ?? null);
            // Aquí no mostramos ningún toast local a propósito
            if (window.onCartAdd) window.onCartAdd(data); // opcional: hook global si lo tienes
          }else{
            if (window.onCartError) window.onCartError(data?.msg || 'Ocurrió un problema.');
            // Sin toast local
          }
        }catch(e){
          if (window.onCartError) window.onCartError('Error de red.');
        }finally{
          form.dataset.submitting = '';          // libera el submit
          if (submitBtn) submitBtn.dataset.busy = ''; // libera el botón
        }
      });
    });
  }

  /* ===== Favoritos AJAX ===== */
  function bindFavButtons(){
    const tpl = root.dataset.favUrlTemplate || '';
    root.querySelectorAll('.fav-btn').forEach(btn=>{
      if (btn.dataset.bound) return; btn.dataset.bound = "1";

      btn.addEventListener('click', async (e)=>{
        e.preventDefault(); e.stopPropagation();
        if (btn.dataset.busy === '1') return;
        btn.dataset.busy = '1';

        const id = btn.dataset.id; if(!id){ btn.dataset.busy=''; return; }
        const url = tpl.replace('__ID__', id);

        try{
          const res = await fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf(), 'Accept':'application/json' },
          });

          if (res.status === 401){ window.location.href = "{{ route('login') }}"; return; }
          const data = await res.json().catch(()=>({}));
          const added = (typeof data.favorited !== 'undefined') ? !!data.favorited
                        : (btn.getAttribute('aria-pressed') !== 'true'); // fallback
          btn.setAttribute('aria-pressed', added ? 'true':'false');
          // Sin toast local
        }catch(e){
          // Sin toast local
        }finally{
          btn.dataset.busy = '';
        }
      });
    });
  }

  /* ===== Fade-in de tarjetas ===== */
  const io = 'IntersectionObserver' in window ? new IntersectionObserver((entries, obs)=>{
    entries.forEach(e=>{ if(e.isIntersecting){ e.target.classList.add('in'); obs.unobserve(e.target); } });
  }, {rootMargin:'0px 0px -10% 0px'}) : null;
  root.querySelectorAll('.ofr-grid-cards .ofr-card').forEach(el=>{ if(io) io.observe(el); });

  function init(){ bindAnimatedCartButtons(); bindAjaxCart(); bindFavButtons(); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
  document.addEventListener('turbo:load', init);
  document.addEventListener('livewire:load', init);
})();
</script>
@endsection
