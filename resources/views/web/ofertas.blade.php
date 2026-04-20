{{-- resources/views/web/ofertas.blade.php --}}
@extends('layouts.web')
@section('title','Ofertas')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400..700&display=swap"/>

@php
  use Illuminate\Support\Str;

  $brandCol  = \Schema::hasColumn('catalog_items','brand') ? 'brand'
             : (\Schema::hasColumn('catalog_items','marca') ? 'marca' : null);
  $hasColor  = \Schema::hasColumn('catalog_items','color');
  $hasSize   = \Schema::hasColumn('catalog_items','size');
  $hasTamano = \Schema::hasColumn('catalog_items','tamano');
  $sizeCol   = $hasSize ? 'size' : ($hasTamano ? 'tamano' : null);

  $q       = trim(request('q',''));
  $orden   = request('orden','mejor_descuento');
  $minOff  = (int) request('min_off', 0);
  $inStock = (int) request('stock', 0);

  $brandsSel = array_filter((array) request('brand', []), fn($v) => $v !== '');
  $colorsSel = array_filter((array) request('color', []), fn($v) => $v !== '');
  $sizesSel  = array_filter((array) request('size',  []), fn($v) => $v !== '');
  $pmin      = request('pmin') !== null ? max(0, (float) request('pmin')) : null;
  $pmax      = request('pmax') !== null ? max(0, (float) request('pmax')) : null;

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

  $imgUrl = function($raw){
    if(!$raw || !is_string($raw) || trim($raw)==='') return null;
    $raw = trim($raw);
    if (\Illuminate\Support\Str::startsWith($raw, ['http://','https://'])) return $raw;
    if (\Illuminate\Support\Str::startsWith($raw, ['storage/'])) return asset($raw);
    return \Illuminate\Support\Facades\Storage::url($raw);
  };

  $pickPhotoUrl = function($p) use ($imgUrl){
    foreach([$p->photo_1 ?? null, $p->photo_2 ?? null, $p->photo_3 ?? null] as $c){
      $u = $imgUrl($c);
      if($u) return $u;
    }
    if(!empty($p->image_url)) return $p->image_url;
    return asset('images/placeholder.png');
  };
@endphp

<div id="ofr" style="font-family:'Outfit', system-ui, -apple-system, Segoe UI, Roboto, 'Helvetica Neue', Arial, 'Noto Sans', 'Apple Color Emoji','Segoe UI Emoji';">

  <section id="ofr-hero" class="ofr-hero">
    <div class="ofr-wrap ofr-wrap--topless">
      <p class="ofr-eyebrow">OFERTAS</p>
      <h1 class="ofr-display">Las <span class="accent">ofertas</span> no deberían esperar</h1>
      <p class="ofr-sub">Descuentos en papelería y oficina con un diseño limpio y claro.</p>
    </div>
  </section>

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

  <section id="ofr-layout">
    <div class="ofr-wrap ofr-grid-layout">
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

      <div class="ofr-grid-area">
        @if($ofertas->count())
          <div class="ofr-grid-cards">
            @foreach($ofertas as $p)
              @php
                $img = $pickPhotoUrl($p);
                $off = $pct($p);
                $showUrl = route('web.catalog.show', $p);
              @endphp

              <article class="ofr-card nelo-offer-card">
                <a class="ofr-card-hit" href="{{ $showUrl }}" aria-label="Ver {{ $p->name }}"></a>

                @if($off)
                  <span class="nelo-offer-discount">-{{ $off }}%</span>
                @endif

                <div class="nelo-offer-media">
                  <img src="{{ $img }}"
                       alt="{{ $p->name }}"
                       loading="lazy"
                       onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
                </div>

                <div class="nelo-offer-body">
                  <div class="nelo-offer-price">
                    <span class="nelo-offer-price-now">${{ number_format($p->sale_price, 2) }}</span>
                    <span class="nelo-offer-price-old">${{ number_format($p->price, 2) }}</span>
                  </div>

                  <div class="nelo-offer-tags">
                    <span class="nelo-offer-tag nelo-offer-tag--blue">Sin intereses</span>
                    @if($off >= 20)
                      <span class="nelo-offer-tag nelo-offer-tag--green">Oferta</span>
                    @endif
                  </div>

                  <h3 class="nelo-offer-name">{{ $p->name }}</h3>

                  <div class="nelo-offer-rating">
                    <span class="nelo-offer-stars" aria-hidden="true">
                      <svg viewBox="0 0 24 24"><path d="M12 3.7l2.6 5.28 5.83.85-4.22 4.11 1 5.8L12 16.97 6.79 19.74l1-5.8-4.22-4.11 5.83-.85L12 3.7z"/></svg>
                      <svg viewBox="0 0 24 24"><path d="M12 3.7l2.6 5.28 5.83.85-4.22 4.11 1 5.8L12 16.97 6.79 19.74l1-5.8-4.22-4.11 5.83-.85L12 3.7z"/></svg>
                      <svg viewBox="0 0 24 24"><path d="M12 3.7l2.6 5.28 5.83.85-4.22 4.11 1 5.8L12 16.97 6.79 19.74l1-5.8-4.22-4.11 5.83-.85L12 3.7z"/></svg>
                      <svg viewBox="0 0 24 24"><path d="M12 3.7l2.6 5.28 5.83.85-4.22 4.11 1 5.8L12 16.97 6.79 19.74l1-5.8-4.22-4.11 5.83-.85L12 3.7z"/></svg>
                      <svg viewBox="0 0 24 24"><path d="M12 3.7l2.6 5.28 5.83.85-4.22 4.11 1 5.8L12 16.97 6.79 19.74l1-5.8-4.22-4.11 5.83-.85L12 3.7z"/></svg>
                    </span>
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

<style>
  body.ofertas-bg{ position:relative; }
  body.ofertas-bg::before{
    content:"";
    position:fixed;
    inset:0;
    z-index:-1;
    pointer-events:none;
    background:
      radial-gradient(1200px 700px at 15% -10%, rgba(148,163,184,.45), transparent 60%),
      radial-gradient(1200px 700px at 90% -20%, rgba(148,163,184,.35), transparent 60%),
      linear-gradient(180deg, #cfd6df 0%, #e2d8cf 45%, #f6c2a2 70%, #f0a472 100%);
  }

  #ofr{
    --ofr-ink:#0e1726;
    --ofr-muted:#6b7280;
    --ofr-line:#e8eef6;
    --ofr-shadow:0 20px 50px rgba(2,8,23,.10);
    --ofr-radius:22px;
  }

  #ofr .material-symbols-outlined{ font-size:20px; color:#64748b }
  #ofr-hero, #ofr-controls, #ofr-layout{ width:100vw; margin-left:calc(50% - 50vw); margin-right:calc(50% - 50vw) }
  #ofr .ofr-wrap{ max-width:1200px; margin:0 auto; padding:clamp(18px,2.2vw,28px) }
  #ofr .ofr-wrap--topless{ padding-top:0 !important }

  #ofr .ofr-eyebrow{ letter-spacing:.38em; font-weight:700; color:#1f2937; opacity:.75; margin:4px 0 20px }
  #ofr .ofr-display{ margin:0; font-weight:900; letter-spacing:-.02em; color:#0b1220; font-size:clamp(40px,6vw,86px); line-height:1.02 }
  #ofr .ofr-display .accent{ color:#eaff8f }
  #ofr .ofr-sub{ margin:16px 0 22px; color:#374151; font-size:clamp(15px,1.2vw,18px) }

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

  #ofr .ofr-grid-layout{ display:grid; grid-template-columns:300px 1fr; gap:clamp(16px,2vw,22px) }
  @media (max-width:1024px){
    #ofr .ofr-grid-layout{ grid-template-columns:1fr }
    #ofr .ofr-sidebar{ display:none }
  }

  #ofr .ofr-sidebar{
    background:#fff;
    border:1px solid var(--ofr-line);
    border-radius:var(--ofr-radius);
    padding:14px;
    box-shadow:var(--ofr-shadow);
    position:sticky;
    top:16px;
    height:max-content;
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
    display:inline-flex;
    align-items:center;
    gap:8px;
    font-weight:900;
    border-radius:999px;
    padding:10px 16px;
    border:1px solid var(--ofr-line);
    background:#fff;
    color:#0b1220;
    cursor:pointer;
    text-decoration:none;
    transition:transform .12s ease, box-shadow .2s ease, background .2s ease, color .2s ease;
  }

  #ofr .ofr-btn:hover{ background:#fff; color:#000; transform:translateY(-1px); box-shadow:0 10px 22px rgba(2,8,23,.10) }
  #ofr .ofr-btn-ghost{ background:#fff }
  #ofr .ofr-btn-apply{ border:2px solid #d1fae5; background:#ecfdf5; color:#065f46; box-shadow:0 10px 22px rgba(16,185,129,.18) }
  #ofr .ofr-btn-clear{ background:#fef3c7; border-color:#fde68a }

  #ofr .ofr-grid-area{ min-width:0 }

  /* GRID: no se estira si sólo hay 1 producto */
  #ofr .ofr-grid-cards{
    display:grid;
    gap:20px;
    grid-template-columns:repeat(auto-fill, minmax(240px, 280px));
    justify-content:start;
  }

  #ofr .nelo-offer-card{
    position:relative;
    width:100%;
    max-width:280px;
    background:#fff;
    border:none;
    border-radius:0;
    box-shadow:none;
    overflow:visible;
    display:flex;
    flex-direction:column;
    min-width:0;
    transition:transform .2s ease;
  }

  #ofr .nelo-offer-card:hover{
    transform:translateY(-2px);
  }

  #ofr .ofr-card-hit{
    position:absolute;
    inset:0;
    z-index:1;
  }

  #ofr .nelo-offer-discount{
    position:absolute;
    top:10px;
    right:10px;
    z-index:3;
    background:#ff4a4a;
    color:#fff;
    font-weight:600;
    font-size:12px;
    line-height:1;
    padding:4px 10px;
    border-radius:12px;
  }

  #ofr .nelo-offer-media{
    height:220px;
    display:flex;
    align-items:center;
    justify-content:center;
    margin-bottom:12px;
    position:relative;
    background:#fff;
  }

  #ofr .nelo-offer-media img{
    max-width:100%;
    max-height:100%;
    object-fit:contain;
    display:block;
    transition:transform .2s ease;
  }

  #ofr .nelo-offer-card:hover .nelo-offer-media img{
    transform:scale(1.03);
  }

  #ofr .nelo-offer-body{
    position:relative;
    z-index:2;
    display:flex;
    flex-direction:column;
  }

  #ofr .nelo-offer-price{
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
    margin-bottom:8px;
  }

  #ofr .nelo-offer-price-now{
    font-size:16px;
    font-weight:700;
    color:#ff4a4a;
  }

  #ofr .nelo-offer-price-old{
    font-size:14px;
    color:#a1a1aa;
    text-decoration:line-through;
  }

  #ofr .nelo-offer-tags{
    display:flex;
    gap:6px;
    flex-wrap:wrap;
    margin-bottom:8px;
  }

  #ofr .nelo-offer-tag{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:3px 6px;
    border-radius:4px;
    font-size:11px;
    font-weight:500;
  }

  #ofr .nelo-offer-tag--blue{
    background:#e6f0ff;
    color:#1677ff;
  }

  #ofr .nelo-offer-tag--green{
    background:#e6ffe6;
    color:#15803d;
  }

  #ofr .nelo-offer-name{
    font-size:14px;
    line-height:1.4;
    color:#666666;
    font-weight:400;
    margin:0 0 10px;
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
    min-height:39px;
  }

  #ofr .nelo-offer-rating{
    display:flex;
    align-items:center;
    gap:4px;
    color:#666666;
    font-size:13px;
    margin-bottom:0;
  }

  #ofr .nelo-offer-stars{
    display:inline-flex;
    align-items:center;
    gap:1px;
  }

  #ofr .nelo-offer-stars svg{
    width:14px;
    height:14px;
    fill:#1677ff;
  }

  #ofr .ofr-empty{
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    gap:10px;
    border:1px dashed var(--ofr-line);
    border-radius:var(--ofr-radius);
    padding:24px;
    color:#6b7280;
    background:#f9fafb;
  }

  #ofr .ofr-pagination{ display:flex; justify-content:center; padding:14px 0 }
  #ofr .ofr-pagination nav{ display:inline-block }
  #ofr .ofr-pagination .hidden{ display:none }

  @media (max-width:1200px){
    #ofr .ofr-grid-cards{
      grid-template-columns:repeat(auto-fill, minmax(220px, 240px));
    }

    #ofr .nelo-offer-card{
      max-width:240px;
    }

    #ofr .nelo-offer-media{
      height:190px;
    }
  }

  @media (max-width:768px){
    #ofr .ofr-grid-cards{
      gap:12px;
      grid-template-columns:repeat(2, minmax(0, 1fr));
      justify-content:stretch;
    }

    #ofr .nelo-offer-card{
      max-width:none;
    }

    #ofr .nelo-offer-media{
      height:140px;
    }

    #ofr .nelo-offer-name{
      font-size:13px;
    }
  }
</style>

<script>
(function(){
  function setOfertasBg(on){
    const b = document.body;
    if(!b) return;
    if(on) b.classList.add('ofertas-bg');
    else b.classList.remove('ofertas-bg');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ()=>setOfertasBg(true));
  } else {
    setOfertasBg(true);
  }

  window.addEventListener('pageshow', ()=>setOfertasBg(true));
  window.addEventListener('pagehide', ()=>setOfertasBg(false));
})();
</script>
@endsection