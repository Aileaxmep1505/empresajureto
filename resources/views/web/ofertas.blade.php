{{-- resources/views/web/ofertas.blade.php --}}
@extends('layouts.web')
@section('title','Ofertas')

@section('content')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Material+Symbols+Outlined:wght@400..700&display=swap"/>

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

{{-- Usa data-fav-url para AJAX favoritos --}}
<div id="ofr" data-fav-url-template="{{ route('favoritos.toggle', ['item' => '__ID__']) }}">

  {{-- HERO (nuevo estilo: desvanecido + tipografía Outfit) --}}
  <section id="ofr-hero" class="ofr-hero">
    <div class="ofr-wrap ofr-wrap--topless">
      <p class="ofr-eyebrow">OFERTAS</p>
      <h1 class="ofr-display">Las <span class="accent">ofertas</span> no deberían esperar</h1>
      <p class="ofr-sub">Descuentos en papelería y oficina con estilo enterprise: rápidos, claros y listos para tu carrito.</p>
      <a href="#ofr-layout" class="ofr-cta-hero">
        Hablemos de tu proyecto
      </a>
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
                    {{-- Favorito (AJAX) --}}
                    <button type="button" class="fav-btn" data-id="{{ $p->id }}" aria-pressed="false" title="Agregar a favoritos">
                      <span class="material-symbols-outlined filled">favorite</span>
                      <span class="material-symbols-outlined outline">favorite</span>
                    </button>

                    {{-- Carrito (sin recarga; animado) --}}
                    <form class="ofr-cart" action="{{ route('web.cart.add') }}" method="POST" style="display:inline-flex;gap:6px;align-items:center">
                      @csrf
                      <input type="hidden" name="catalog_item_id" value="{{ $p->id }}">

                      {{-- Botón animado (camisa blanca) --}}
                      <button type="button" class="add-to-cart add-to-cart--sm" data-submit-delay="900" title="Agregar al carrito">
                        <span>Agregar</span>
                        <svg class="morph" viewBox="0 0 64 13" aria-hidden="true">
                          <path d="M0 12C6 12 17 12 32 12C47.9024 12 58 12 64 12V13H0V12Z" />
                        </svg>
                        <div class="shirt" aria-hidden="true">
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

  {{-- FAB móvil --}}
  <button id="ofr-open" class="ofr-filters-fab">
    <span class="material-symbols-outlined">tune</span> Filtros
  </button>

  {{-- Bottom Sheet (móvil) --}}
  <div id="ofr-sheet" class="ofr-sheet" aria-hidden="true">
    <div class="ofr-sheet-backdrop" data-ofr-sheet-close></div>

    <div class="ofr-sheet-panel" role="dialog" aria-modal="true" aria-labelledby="ofr-sheet-title">
      <div class="ofr-sheet-handle" aria-hidden="true"></div>
      <div class="ofr-sheet-head">
        <h3 id="ofr-sheet-title">Filtros</h3>
        <button class="ofr-sheet-close" type="button" title="Cerrar" data-ofr-sheet-close>
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      {{-- Filtros móviles (mismos campos) --}}
      <form id="ofr-sheet-form" class="ofr-sheet-form" method="GET" action="{{ url()->current() }}">
        <div class="ofr-fblock">
          <h4>Ordenar</h4>
          <div class="ofr-row">
            <select name="orden">
              <option value="mejor_descuento" @selected($orden==='mejor_descuento')>Mejor descuento</option>
              <option value="precio_asc" @selected($orden==='precio_asc')>Precio: menor a mayor</option>
              <option value="precio_desc" @selected($orden==='precio_desc')>Precio: mayor a menor</option>
              <option value="recientes" @selected($orden==='recientes')>Más recientes</option>
              <option value="nombre" @selected($orden==='nombre')>Nombre (A-Z)</option>
            </select>
            <label class="ofr-switch" style="margin-left:auto">
              <input type="checkbox" name="stock" value="1" @checked($inStock)>
              <span class="ofr-slider"></span>
              <span class="ofr-txt">Sólo con stock</span>
            </label>
          </div>
        </div>

        <div class="ofr-fblock">
          <h4>% mínimo</h4>
          <select name="min_off">
            @foreach([0,5,10,15,20,25,30,40,50] as $opt)
              <option value="{{ $opt }}" @selected($minOff===$opt)>{{ $opt }}%</option>
            @endforeach
          </select>
        </div>

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

        <div class="ofr-sheet-actions">
          <button class="ofr-btn ofr-btn-apply" type="submit"><span class="material-symbols-outlined">check</span> Aplicar</button>
          <button class="ofr-btn ofr-btn-ghost" type="button" data-ofr-sheet-close>Cancelar</button>
          @if(request()->query())
            <a class="ofr-btn ofr-btn-clear" href="{{ url()->current() }}">Limpiar</a>
          @endif
        </div>
      </form>
    </div>
  </div>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script defer src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>

<style>
  /* === Encapsulado en #ofr === */
  #ofr{
    --ofr-ink:#0e1726; --ofr-muted:#6b7280; --ofr-bg:#f6f8fc; --ofr-line:#e8eef6;
    --ofr-ok:#16a34a; --ofr-shadow:0 20px 50px rgba(2,8,23,.10);
    --ofr-radius:22px;
    --accent-lime:#e7ff8c;
    --display-font:'Outfit', system-ui, -apple-system, Segoe UI, Roboto, 'Helvetica Neue', Arial, 'Noto Sans', 'Apple Color Emoji','Segoe UI Emoji', sans-serif;
  }
  #ofr .material-symbols-outlined{ font-size:20px; color:#64748b }

  /* Full-bleed */
  #ofr-hero, #ofr-controls, #ofr-layout{ width:100vw; margin-left:calc(50% - 50vw); margin-right:calc(50% - 50vw) }
  #ofr .ofr-wrap{ max-width:1200px; margin:0 auto; padding: clamp(18px,2.2vw,28px) }
  #ofr .ofr-wrap--topless{ padding-top:0 !important }

  /* ====== HERO nuevo (degradado tipo screenshot) ====== */
  #ofr .ofr-hero{
    margin-top:0 !important;
    /* degradado azul→melón, suave y desvanecido */
    background:
      radial-gradient(1200px 700px at 15% 0%, rgba(183,196,215,.85), transparent 60%),
      radial-gradient(1100px 800px at 85% 100%, rgba(245,177,137,.85), transparent 60%),
      linear-gradient(180deg, #b8c2cf 0%, #cfd6df 30%, #e2d7cf 65%, #f3b18d 100%);
    border-bottom:1px solid rgba(255,255,255,.15);
  }
  #ofr .ofr-eyebrow{
    font-family:var(--display-font); font-weight:600; letter-spacing:.24em;
    text-transform:uppercase; color:#1f2937; opacity:.7; margin:6px 0 14px;
  }
  #ofr .ofr-display{
    font-family:var(--display-font);
    font-weight:800; letter-spacing:-.02em; line-height:1.02;
    font-size:clamp(38px,7.8vw,96px);
    color:#0b1220; margin:0 0 12px;
  }
  #ofr .ofr-display .accent{ color:var(--accent-lime); }
  #ofr .ofr-sub{
    color:#334155; font-size:clamp(15px,1.2vw,18px); margin:0 0 22px;
    font-family:var(--display-font); font-weight:500;
  }
  #ofr .ofr-cta-hero{
    display:inline-flex; align-items:center; justify-content:center; gap:10px;
    padding:14px 22px; border-radius:999px; border:1px solid #d9f99d;
    background:var(--accent-lime); color:#0b1220; font-weight:800; text-decoration:none;
    box-shadow:0 14px 40px rgba(131,204,28,.22);
    transition:transform .15s ease, box-shadow .2s ease, background .2s ease, color .2s ease;
  }
  #ofr .ofr-cta-hero:hover{ background:#fff; color:#000; transform:translateY(-1px); box-shadow:0 18px 50px rgba(2,8,23,.18) }

  /* Controles */
  #ofr-controls{ background:#fff; border-bottom:1px solid var(--ofr-line) }
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

  /* Layout & Cards (sin cambios mayores) */
  #ofr .ofr-grid-layout{ display:grid; grid-template-columns: 300px 1fr; gap: clamp(16px,2vw,22px) }
  @media (max-width: 1024px){ #ofr .ofr-grid-layout{ grid-template-columns: 1fr } #ofr .ofr-sidebar{ display:none } }
  #ofr .ofr-sidebar{ background:#fff; border:1px solid var(--ofr-line); border-radius:var(--ofr-radius); padding:14px; box-shadow:var(--ofr-shadow); position:sticky; top:16px; height:max-content; }
  #ofr .ofr-fblock{ margin-bottom:14px }
  #ofr .ofr-fblock h4{ margin:0 0 8px; color:#0b1220; font-weight:900; font-size:1rem; font-family:var(--display-font) }
  #ofr .ofr-price-range{ display:grid; grid-template-columns:1fr 1fr; gap:10px }
  #ofr .ofr-price-range label{ display:block; font-size:.85rem; color:#475569; margin-bottom:4px; font-weight:700 }
  #ofr .ofr-price-range input, #ofr .ofr-sheet-form select{ width:100%; padding:10px 12px; border:1px solid var(--ofr-line); border-radius:12px; outline:0; background:#fff }
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
    font-family:var(--display-font);
  }
  #ofr .ofr-btn:hover{ background:#fff; color:#000; transform: translateY(-1px); box-shadow:0 10px 22px rgba(2,8,23,.10) }
  #ofr .ofr-btn-apply{ border:2px solid #d1fae5; background:#ecfdf5; color:#065f46; box-shadow:0 10px 22px rgba(16,185,129,.18) }
  #ofr .ofr-btn-clear{ background:#fef3c7; border-color:#fde68a }

  #ofr .ofr-grid-area{ min-width:0 }
  #ofr .ofr-grid-cards{ display:grid; gap:16px; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)) }

  #ofr .ofr-card{
    position:relative; border:1px solid var(--ofr-line); border-radius:var(--ofr-radius); background:#fff; box-shadow:0 14px 34px rgba(2,8,23,.06);
    display:flex; flex-direction:column; overflow:hidden; transform: perspective(900px) translateZ(0);
    transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
  }
  #ofr .ofr-card:hover{ transform: perspective(900px) translateY(-4px) rotateX(1.2deg) rotateY(.8deg); box-shadow:0 22px 52px rgba(2,8,23,.12); border-color:#e2e8f0; }
  #ofr .ofr-card-hit{ position:absolute; inset:0; z-index:1 }
  #ofr .ofr-media{ position:relative; background:#f6f8fc; aspect-ratio:1/1; overflow:hidden }
  #ofr .ofr-media img{ width:100%; height:100%; object-fit:contain; transform: scale(1); transition: transform .35s ease }
  #ofr .ofr-card:hover .ofr-media img{ transform: scale(1.06) }
  #ofr .ofr-ribbon{ position:absolute; top:10px; left:10px; z-index:2; background:#dcfce7; color:#14532d; border:1px solid rgba(20,83,45,.25); padding:.35rem .55rem; font-weight:900; font-size:.85rem; border-radius:999px; animation: ofr-floaty 3.2s ease-in-out infinite; }
  @keyframes ofr-floaty{ 0%,100%{ transform:translateY(0) } 50%{ transform:translateY(-3px) } }

  #ofr .ofr-body{ position:relative; z-index:2; display:flex; flex-direction:column; gap:8px; padding:12px }
  #ofr .ofr-topline{ display:flex; justify-content:space-between; align-items:center; gap:8px }
  #ofr .ofr-brand{ font-weight:900; color:#1d4ed8; font-size:.9rem }
  #ofr .ofr-sku{ color:#64748b; font-size:.85rem }
  #ofr .ofr-title{ margin:0; color:var(--ofr-ink); font-weight:900; line-height:1.25; font-family:var(--display-font) }
  #ofr .ofr-desc{ color:#6b7280; margin:2px 0 4px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden }
  #ofr .ofr-price{ display:flex; align-items:baseline; gap:10px; margin-top:2px }
  #ofr .ofr-now{ color:#16a34a; font-weight:900; font-size:1.08rem }
  #ofr .ofr-old{ color:#94a3b8; text-decoration:line-through; font-weight:700 }

  #ofr .ofr-cta{ display:flex; gap:8px; align-items:center; margin-top:6px }

  /* === Botón Favorito === */
  #ofr .fav-btn{
    position:relative; display:inline-flex; align-items:center; justify-content:center;
    width:40px; height:40px; border-radius:12px; border:1px solid #e5e7eb; background:#fff; cursor:pointer;
    transition:transform .12s ease, box-shadow .2s ease, background .2s ease, border-color .2s ease;
  }
  #ofr .fav-btn:hover{ transform: translateY(-1px); box-shadow:0 10px 22px rgba(2,8,23,.10) }
  #ofr .fav-btn .filled{ display:none; color:#ef4444 }
  #ofr .fav-btn[aria-pressed="true"]{ background:#ffe4e6; border-color:#fecdd3 }
  #ofr .fav-btn[aria-pressed="true"] .filled{ display:inline-block }
  #ofr .fav-btn[aria-pressed="true"] .outline{ display:none }

  /* Empty & Pagination */
  #ofr .ofr-empty{ display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; border:1px dashed var(--ofr-line); border-radius:var(--ofr-radius); padding:24px; color:#6b7280; background:#f9fafb }
  #ofr .ofr-pagination{ display:flex; justify-content:center; padding:14px 0 }
  #ofr .ofr-pagination nav{ display:inline-block }
  #ofr .ofr-pagination .hidden{ display:none }

  /* Fade-in */
  #ofr .ofr-ao{ opacity:0; transform:translateY(8px); transition: opacity .45s ease, transform .45s ease }
  #ofr .ofr-ao.in{ opacity:1; transform:none }

  /* FAB móvil */
  #ofr .ofr-filters-fab{
    position:fixed; right:14px; bottom:calc(14px + env(safe-area-inset-bottom));
    z-index:1060; display:none; align-items:center; gap:8px;
    padding:12px 16px; border-radius:999px; border:1px solid #e2e8f0;
    background:#ffffff; color:#0b1220; font-weight:900; box-shadow:0 14px 34px rgba(2,8,23,.14);
    transition:opacity .18s ease, transform .18s ease;
  }
  @media (max-width:1024px){ #ofr .ofr-filters-fab{ display:inline-flex } }
  #ofr .ofr-filters-fab.ofr-hide{ display:none !important }

  /* Bottom Sheet */
  #ofr .ofr-sheet{ position:fixed; inset:0; z-index:99998; display:grid; grid-template-rows:1fr auto; pointer-events:none }
  #ofr .ofr-sheet[aria-hidden="true"]{ display:none }
  #ofr .ofr-sheet-backdrop{ background:rgba(15,23,42,.35); -webkit-backdrop-filter: blur(10px); backdrop-filter: blur(10px); opacity:0; transition:opacity .25s ease; pointer-events:auto; z-index:99998; }
  #ofr .ofr-sheet-panel{
    width:100%; z-index:99999; align-self:end; background:#fff; border-top-left-radius:24px; border-top-right-radius:24px; border:1px solid #e5e7eb; border-bottom:0;
    box-shadow:0 -22px 60px rgba(2,8,23,.22); transform: translate3d(0,100%,0); transition: transform .35s cubic-bezier(.25,.46,.45,.94); max-height: 78vh; overflow:auto; pointer-events:auto;
  }
  #ofr .ofr-sheet[aria-hidden="false"] .ofr-sheet-backdrop{ opacity:1 }
  #ofr .ofr-sheet[aria-hidden="false"] .ofr-sheet-panel{ transform: translate3d(0,0,0) }
  #ofr .ofr-sheet-handle{ width:60px; height:6px; background:#e5e7eb; border-radius:999px; margin:12px auto 6px }
  #ofr .ofr-sheet-head{ display:flex; align-items:center; justify-content:space-between; padding: 8px 12px 4px 12px }
  #ofr .ofr-sheet-head h3{ margin:0; font-weight:900 }
  #ofr .ofr-sheet-close{ border:0; background:transparent; padding:8px; border-radius:10px }
  #ofr .ofr-sheet-form{ padding: 8px 12px 16px 12px }
  #ofr .ofr-sheet-form .ofr-fblock{ margin:12px 0 }
  #ofr .ofr-sheet-actions{ display:flex; gap:10px; padding-top:8px }
  @media (min-width:1025px){ #ofr .ofr-sheet{ display:none !important } }

  /* SweetAlert2 minimal encapsulado */
  .ofr-swal-popup{ border-radius:14px !important; border:1px solid #e5e7eb !important; box-shadow:0 18px 42px rgba(2,8,23,.14) !important; padding:14px 16px !important; font-family: inherit !important }
  .ofr-swal-title{ font-weight:900 !important; color:#0f172a !important; font-size:16px !important; margin:0 !important }
  .ofr-swal-html{ color:#475569 !important; font-size:13px !important; margin:6px 0 0 !important }
  .ofr-swal-confirm{ border-radius:999px !important; padding:8px 14px !important; background:#ecfdf5 !important; color:#065f46 !important; border:1px solid #d1fae5 !important; box-shadow:none !important; font-weight:800 !important }
  .ofr-swal-timer-progress-bar{ background:#a7f3d0 !important }

  /* === Botón "add-to-cart" animado (camisa blanca) === */
  #ofr .add-to-cart{
    --background-default:#17171B; --background-hover:#0A0A0C; --background-scale:1;
    --text-color:#fff; --text-o:1; --text-x:12px;
    --cart:#fff; --cart-x:-48px; --cart-y:0px; --cart-rotate:0deg; --cart-scale:.75;
    --cart-clip:0px; --cart-clip-x:0px; --cart-tick-offset:10px; --cart-tick-color:#22c55e;
    /* Camisa blanca (solicitado) */
    --shirt-y:-16px; --shirt-scale:0; --shirt-color:#ffffff; --shirt-logo:#17171B;
    --shirt-second-y:24px; --shirt-second-color:#ffffff; --shirt-second-logo:#17171B;
    -webkit-tap-highlight-color:transparent; appearance:none; outline:0; background:none; border:0;
    padding:12px 0; width:164px; margin:0; cursor:pointer; position:relative; font:inherit; border-radius:12px;
  }
  #ofr .add-to-cart:before{ content:""; position:absolute; inset:0; border-radius:12px; transition:background .25s; background:var(--background,var(--background-default)); transform:scaleX(var(--background-scale)) translateZ(0); }
  #ofr .add-to-cart:not(.active):hover{ --background:var(--background-hover); }
  #ofr .add-to-cart>span{ position:relative; z-index:1; display:block; text-align:center; font-size:14px; font-weight:800; line-height:24px; color:var(--text-color); opacity:var(--text-o); transform:translateX(var(--text-x)) translateZ(0); }
  #ofr .add-to-cart svg{ display:block; stroke-linecap:round; stroke-linejoin:round; }
  #ofr .add-to-cart .morph{ width:64px; height:13px; position:absolute; left:50%; top:-12px; margin-left:-32px; fill:var(--background,var(--background-default)); transition:fill .25s; pointer-events:none; }
  #ofr .add-to-cart .shirt, #ofr .add-to-cart .cart{ pointer-events:none; position:absolute; left:50%; }
  #ofr .add-to-cart .shirt{ top:0; margin:-12px 0 0 -12px; transform-origin:50% 100%; transform:translateY(var(--shirt-y)) scale(var(--shirt-scale)); }
  #ofr .add-to-cart .shirt svg{ width:24px; height:24px; }
  #ofr .add-to-cart .shirt svg path{ fill:var(--shirt-color); }
  #ofr .add-to-cart .shirt svg g path{ fill:var(--shirt-logo); }
  #ofr .add-to-cart .shirt svg.second{ position:absolute; top:0; left:0; clip-path:polygon(0 var(--shirt-second-y),24px var(--shirt-second-y),24px 24px,0 24px); }
  #ofr .add-to-cart .shirt svg.second path{ fill:var(--shirt-second-color); }
  #ofr .add-to-cart .shirt svg.second g path{ fill:var(--shirt-second-logo); }
  #ofr .add-to-cart .cart{ width:36px; height:26px; top:10px; margin-left:-18px; transform:translate(var(--cart-x),var(--cart-y)) rotate(var(--cart-rotate)) scale(var(--cart-scale)) translateZ(0); }
  #ofr .add-to-cart .cart:before{ content:""; width:22px; height:12px; position:absolute; left:7px; top:7px; background:var(--cart); clip-path:polygon(0 0,22px 0, calc(22px - var(--cart-clip-x)) var(--cart-clip), var(--cart-clip-x) var(--cart-clip)); }
  #ofr .add-to-cart .cart .shape{ fill:none; stroke:var(--cart); stroke-width:2; }
  #ofr .add-to-cart .cart .wheel{ fill:none; stroke:var(--cart); stroke-width:1.5; }
  #ofr .add-to-cart .cart .tick{ fill:none; stroke:var(--cart-tick-color); stroke-width:2; stroke-dasharray:10px; stroke-dashoffset:var(--cart-tick-offset); }
  #ofr .add-to-cart--sm{ transform:scale(.92); transform-origin:left center; }
</style>

<script>
(function(){
  const root = document.getElementById('ofr');
  if(!root) return;

  /* ========== UTILIDADES ========== */
  const csrfToken = () => (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')) || '';
  const updateBadge = (count) => {
    if (window.updateCartBadge) { window.updateCartBadge(count); return; }
    const el = document.querySelector('[data-cart-badge]');
    if (el) el.textContent = String(count||0);
  };
  const toast = (ok=true, msg='') => {
    if (!window.Swal) return;
    Swal.fire({
      title: ok ? 'Listo' : 'Error',
      text: msg || (ok ? 'Acción completada.' : 'Ocurrió un problema.'),
      icon: ok ? 'success' : 'error',
      toast: true, position: 'top-end', showConfirmButton: false,
      timer: 1400, timerProgressBar: true,
      customClass: {
        popup: 'ofr-swal-popup',
        title: 'ofr-swal-title',
        htmlContainer: 'ofr-swal-html',
        confirmButton: 'ofr-swal-confirm',
        timerProgressBar: 'ofr-swal-timer-progress-bar'
      }
    });
  };

  /* ====== Favoritos (AJAX) ====== */
  const favUrl = root.dataset.favUrl || '';
  function bindFavButtons(){
    root.querySelectorAll('.fav-btn').forEach(btn=>{
      if (btn.dataset.bound) return;
      btn.dataset.bound = '1';
      btn.addEventListener('click', async (e)=>{
        e.preventDefault(); e.stopPropagation();
        if(!favUrl) return;
        if (btn.dataset.loading === '1') return;
        btn.dataset.loading = '1';
        try{
          const res = await fetch(favUrl, {
            method:'POST',
            headers:{ 'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': csrfToken(),'Accept':'application/json','Content-Type':'application/json' },
            body: JSON.stringify({ catalog_item_id: btn.dataset.id })
          });
          if(res.status === 401){
            Swal.fire({
              title:'Inicia sesión',
              text:'Necesitas iniciar sesión para usar favoritos.',
              icon:'info',
              showConfirmButton:true,
              confirmButtonText:'Ir a iniciar sesión',
              customClass:{ popup:'ofr-swal-popup', confirmButton:'ofr-swal-confirm' }
            }).then(()=>{ window.location.href = "{{ route('login') }}"; });
            return;
          }
          const data = await res.json().catch(()=>({ok:false}));
          if(data.ok){
            const added = data.status === 'added';
            btn.setAttribute('aria-pressed', added ? 'true' : 'false');
            toast(true, added ? 'Agregado a favoritos' : 'Quitado de favoritos');
          }else{
            toast(false, data.msg || 'No fue posible cambiar el favorito.');
          }
        }catch(_e){
          toast(false, 'Error de red.');
        }finally{
          btn.dataset.loading = '';
        }
      });
    });
  }

  /* ====== Carrito AJAX (igual que antes, sin duplicados) ====== */
  async function ajaxAdd(form, btn){
    if (!form || form.dataset.submitting === '1') return;
    form.dataset.submitting = '1';
    try{
      const fd = new FormData(form);
      const res = await fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': csrfToken(),'Accept':'application/json' },
        body: fd
      });
      const data = await res.json().catch(()=>({ok:false}));
      if (!res.ok || data.ok === false){ toast(false, data.msg || 'Ocurrió un problema.'); return; }
      updateBadge(data?.totals?.count ?? null);
      toast(true, 'El producto se añadió al carrito.');
    }catch(_e){
      toast(false, 'Error de red.');
    }finally{
      setTimeout(()=>{ form.dataset.submitting = ''; if(btn){ btn.classList.remove('active'); btn.style.pointerEvents=''; } }, 200);
    }
  }

  /* Hero fade-in para cards */
  const io = 'IntersectionObserver' in window ? new IntersectionObserver((entries, obs)=>{
    entries.forEach(e=>{ if(e.isIntersecting){ e.target.classList.add('in'); obs.unobserve(e.target); } });
  }, {rootMargin:'0px 0px -10% 0px'}) : null;
  root.querySelectorAll('.ofr-grid-cards .ofr-card.ofr-ao').forEach(el=>{ if(io) io.observe(el); else el.classList.add('in'); });

  /* Bottom sheet + FAB (igual que tenías) */
  const html     = document.documentElement;
  const openBtn  = root.querySelector('#ofr-open');
  const sheet    = root.querySelector('#ofr-sheet');
  const panel    = root.querySelector('.ofr-sheet-panel');
  const backdrop = root.querySelector('.ofr-sheet-backdrop');
  const closeEls = sheet ? sheet.querySelectorAll('[data-ofr-sheet-close]') : [];

  function isLayoutSheetOpen(){ return html.classList.contains('sheet-open'); }
  function isOffersSheetOpen(){ return sheet && sheet.getAttribute('aria-hidden') === 'false'; }
  function setFabHidden(h){ openBtn?.classList.toggle('ofr-hide', !!h); }
  function updateFab(){ setFabHidden(isLayoutSheetOpen() || isOffersSheetOpen()); }

  const changed = new Set();
  function lockBackground(lock){
    if(lock){
      Array.from(document.body.children).forEach(el=>{
        if (el === sheet) return;
        if (el.contains(sheet)) return;
        if (!el.hasAttribute('inert')) { el.setAttribute('inert',''); changed.add(el); }
        if (el.getAttribute('aria-hidden') !== 'true') { el.setAttribute('aria-hidden','true'); changed.add(el); }
      });
      html.style.overscrollBehavior = 'none';
      html.style.overflow = 'hidden';
    }else{
      changed.forEach(el=>{ el.removeAttribute('inert'); el.removeAttribute('aria-hidden'); });
      changed.clear();
      html.style.overscrollBehavior = '';
      html.style.overflow = '';
    }
  }
  function trapFocus(e){
    if(!isOffersSheetOpen() || e.key !== 'Tab') return;
    const focusables = panel.querySelectorAll('a,button,input,select,textarea,[tabindex]:not([tabindex="-1"])');
    if(!focusables.length) return;
    const first = focusables[0], last = focusables[focusables.length - 1];
    if(e.shiftKey && document.activeElement === first){ last.focus(); e.preventDefault(); }
    else if(!e.shiftKey && document.activeElement === last){ first.focus(); e.preventDefault(); }
  }
  function openSheet(){ if(!sheet) return; sheet.setAttribute('aria-hidden','false'); lockBackground(true); updateFab(); panel.scrollTop = 0; setTimeout(()=>{ (panel.querySelector('select, input, button')||panel).focus({preventScroll:true}); },60); }
  function closeSheet(){ if(!sheet) return; sheet.setAttribute('aria-hidden','true'); lockBackground(false); panel.style.transform='translate3d(0,100%,0)'; updateFab(); }
  window.addEventListener('DOMContentLoaded', ()=>{ closeSheet(); updateFab(); });
  window.addEventListener('pageshow', updateFab);
  openBtn && openBtn.addEventListener('click', openSheet);
  closeEls.forEach(el => el.addEventListener('click', closeSheet));
  backdrop && backdrop.addEventListener('click', closeSheet);
  document.addEventListener('keydown', (e) => { if(e.key === 'Escape' && isOffersSheetOpen()) closeSheet(); });
  document.addEventListener('keydown', trapFocus);
  window.addEventListener('resize', () => { if(window.matchMedia('(min-width:1025px)').matches) closeSheet(); });
  const stopScroll = (e)=>{ if(isOffersSheetOpen()) e.preventDefault(); };
  backdrop?.addEventListener('touchmove', stopScroll, {passive:false});
  backdrop?.addEventListener('wheel',     stopScroll, {passive:false});
  root.addEventListener('click', e => { const a = e.target.closest('a'); if (!a) return; if (a.id === 'ofr-open') return; if (isOffersSheetOpen()) closeSheet(); });
  root.querySelector('#ofr-sheet-form')?.addEventListener('submit', () => { closeSheet(); });
  (new MutationObserver(updateFab)).observe(html, { attributes:true, attributeFilter:['class'] });

  // Drag to close
  let dragStartY = 0, dragging = false;
  const CLOSE_THRESHOLD = 90, MAX_PULL = 240;
  function canStartDrag() { return panel.scrollTop <= 0; }
  function onTouchStart(ev){ if(!isOffersSheetOpen() || ev.touches.length!==1 || !canStartDrag()) return; dragging=true; dragStartY=ev.touches[0].clientY; panel.style.transition='none'; }
  function onTouchMove(ev){ if(!dragging) return; const dy=Math.max(0, ev.touches[0].clientY-dragStartY); const pull=Math.min(dy,MAX_PULL); panel.style.transform=`translate3d(0, ${pull}px, 0)`; if(dy>0) ev.preventDefault(); }
  function onTouchEnd(){ if(!dragging) return; dragging=false; const m=panel.style.transform.match(/translate3d\(0,\s?([0-9.]+)px/i); const pulled=m?parseFloat(m[1]):0; panel.style.transition='transform .22s ease'; if(pulled>CLOSE_THRESHOLD){ panel.style.transform='translate3d(0,100%,0)'; setTimeout(closeSheet,200);}else{ panel.style.transform='translate3d(0,0,0)'; } }
  const handle = root.querySelector('.ofr-sheet-handle');
  [panel, handle].forEach(el=>{
    el?.addEventListener('touchstart', onTouchStart, {passive:false});
    el?.addEventListener('touchmove',  onTouchMove,  {passive:false});
    el?.addEventListener('touchend',   onTouchEnd,   {passive:true});
    el?.addEventListener('touchcancel',onTouchEnd,   {passive:true});
  });

  /* ===== Botón animado + ÚNICO envío AJAX ===== */
  function bindAnimatedCartButtons(){
    root.querySelectorAll('#ofr .add-to-cart').forEach(btn=>{
      if (btn.dataset.bound) return;
      btn.dataset.bound = "1";
      const submitDelay = parseInt(btn.dataset.submitDelay || '900', 10);

      btn.addEventListener('pointerdown', () => {
        if (btn.classList.contains('active')) return;
        if (window.gsap) gsap.to(btn, { '--background-scale': .97, duration: .12 });
      });

      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();

        const form = btn.closest('form.ofr-cart');
        if (!form) return;
        if (form.dataset.submitting === '1' || btn.classList.contains('active')) return;

        btn.classList.add('active');
        btn.style.pointerEvents = 'none';
        btn.style.setProperty('--text-o', 0);

        if (window.gsap){
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
          ]});
        }

        setTimeout(()=> ajaxAdd(form, btn), submitDelay);
      });
    });
  }

  // Fallback accesible (si alguien dispara submit del form)
  function bindFormSubmitFallback(){
    root.querySelectorAll('form.ofr-cart').forEach(form=>{
      if (form.dataset.boundSubmit) return;
      form.dataset.boundSubmit = "1";
      form.addEventListener('submit', (ev)=>{ ev.preventDefault(); ajaxAdd(form); });
    });
  }

  function init(){
    bindAnimatedCartButtons();
    bindFormSubmitFallback();
    bindFavButtons();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
  document.addEventListener('turbo:load', init);
  document.addEventListener('livewire:load', init);
})();
</script>
@endsection
