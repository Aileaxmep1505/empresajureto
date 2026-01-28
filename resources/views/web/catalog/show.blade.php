@extends('layouts.web')
@section('title', $item->name)

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
/* ================= BASE / TOKENS (SIN “CARDS” EXTRA) ================= */
#product{
  --ink:#0b1220;
  --muted:#64748b;
  --line:#e8eef6;
  --ok:#16a34a;
  --warn:#f59e0b;
  --accent:#0f172a;

  margin:0;
  font-family:"Quicksand", system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, "Helvetica Neue", Arial;
  color:var(--ink);
}
#product a{ text-decoration:none; color:inherit; }

#product .bg-grad{
  position:fixed; inset:0; z-index:-1; pointer-events:none;
  background:
    radial-gradient(900px 560px at 50% -210px, rgba(255,255,255,.72), transparent 60%),
    linear-gradient(180deg,#f3faea 0%, #eef5ff 48%, #ecebff 100%);
  filter:saturate(1.02);
}
#product .container{
  width:100%;
  max-width:1180px;
  padding-inline:clamp(16px,3vw,28px);
  margin-inline:auto;
}

/* ================= LAYOUT ================= */
#hero{
  padding-top:clamp(16px,2.2vw,24px);
  padding-bottom:clamp(22px,3.2vw,34px);
}
#hero .topbar{
  display:flex; align-items:center; gap:10px;
  margin:0 0 14px;
}
#hero .back{
  display:inline-flex; align-items:center; gap:8px;
  padding:10px 14px;
  border-radius:999px;
  background:rgba(255,255,255,.70);
  border:1px solid rgba(232,238,246,.95);
  backdrop-filter: blur(10px);
  font-weight:800;
  transition:.16s ease;
}
#hero .back:hover{ transform:translateY(-1px); box-shadow:0 12px 28px rgba(2,8,23,.08); }

#hero .grid{
  display:grid;
  grid-template-columns: 1.05fr .95fr;
  gap:clamp(16px,2.4vw,28px);
  align-items:start;
}
@media (max-width: 980px){
  #hero .grid{ grid-template-columns:1fr; }
}

/* ================= GALERÍA (MISMO CONTENEDOR, MINIMAL) ================= */
#hero .media{
  border-radius:22px;
  overflow:hidden;
  border:1px solid rgba(232,238,246,.95);
  background:#fff;
  box-shadow:0 18px 44px rgba(2,8,23,.10);
}
#hero .stage{
  position:relative;
  width:100%;
  aspect-ratio:4/3;
  overflow:hidden;
  background:#f6f8fc;
}
#hero .stage img{
  position:absolute; inset:0;
  width:100%; height:100%;
  object-fit:cover;
  opacity:1;
  transform:translateX(0);
  transition:opacity .22s ease, transform .34s cubic-bezier(.2,.9,.2,1);
  will-change:opacity, transform;
}

/* transición */
#hero .stage img.outL{ opacity:0; transform:translateX(-18px) scale(.995); }
#hero .stage img.outR{ opacity:0; transform:translateX( 18px) scale(.995); }
#hero .stage img.inL{ opacity:0; transform:translateX(-18px) scale(.995); }
#hero .stage img.inR{ opacity:0; transform:translateX( 18px) scale(.995); }

#hero .pill{
  position:absolute;
  left:14px; bottom:14px;
  display:inline-flex; align-items:center; gap:8px;
  padding:8px 12px;
  border-radius:999px;
  background:rgba(255,255,255,.88);
  border:1px solid rgba(232,238,246,.95);
  backdrop-filter: blur(10px);
  font-weight:800;
  font-size:.82rem;
  box-shadow:0 10px 24px rgba(2,8,23,.10);
}
#hero .pill .dot{ width:8px; height:8px; border-radius:999px; background:#22c55e; }

#hero .nav{
  position:absolute; inset:0;
  display:flex; align-items:center; justify-content:space-between;
  padding:0 10px;
  pointer-events:none;
}
#hero .nav button{
  pointer-events:auto;
  width:42px; height:42px;
  border-radius:999px;
  border:1px solid rgba(232,238,246,.95);
  background:rgba(255,255,255,.86);
  backdrop-filter: blur(10px);
  box-shadow:0 16px 30px rgba(2,8,23,.18);
  cursor:pointer;
  display:flex; align-items:center; justify-content:center;
  transition:transform .14s ease, background .14s ease;
}
#hero .nav button:hover{ transform:scale(1.05); background:#fff; }
#hero .nav button:active{ transform:scale(.98); }
#hero .nav svg{ width:22px; height:22px; color:#0b1f33; }

#hero .thumbs{
  display:flex; gap:10px; flex-wrap:wrap;
  padding:12px 14px 14px;
  border-top:1px solid var(--line);
  background:#fff;
}
#hero .thumb{
  width:78px; height:78px;
  border-radius:14px;
  border:1px solid var(--line);
  object-fit:cover;
  cursor:pointer;
  opacity:.9;
  background:#f6f8fc;
  transition:transform .14s ease, opacity .14s ease;
}
#hero .thumb:hover{ transform:translateY(-2px); opacity:1; }
#hero .thumb.active{ outline:2px solid rgba(34,197,94,.35); opacity:1; }

/* ================= INFO (SIN CONTENEDOR EXTRA) ================= */
#hero .info{ padding-top:4px; }
#hero h1{
  margin:0 0 8px;
  font-weight:800;
  letter-spacing:-.2px;
  font-size:clamp(22px,3.4vw,42px);
  line-height:1.12;
}
#hero .meta{
  display:flex; flex-wrap:wrap; gap:10px; align-items:center;
  color:var(--muted);
  font-weight:700;
  margin:0 0 12px;
}
#hero .meta b{ color:#0f172a; font-weight:800; }
#hero .meta .dot{ width:4px; height:4px; border-radius:999px; background:#94a3b8; opacity:.9; display:inline-block; }

#hero .chips{ display:flex; flex-wrap:wrap; gap:10px; margin:10px 0 12px; }
#hero .chip{
  display:inline-flex; align-items:center; gap:8px;
  padding:10px 12px;
  border-radius:999px;
  background:rgba(255,255,255,.60);
  border:1px solid rgba(232,238,246,.95);
  backdrop-filter: blur(10px);
  font-weight:800;
  font-size:.86rem;
}
#hero .chip svg{ width:18px; height:18px; }

#hero .badges{ display:flex; flex-wrap:wrap; gap:8px; margin:6px 0 10px; }
#hero .badge{
  font-weight:800; font-size:.78rem;
  padding:7px 12px;
  border-radius:999px;
  border:1px solid rgba(232,238,246,.95);
  background:rgba(255,255,255,.60);
  backdrop-filter: blur(10px);
}
#hero .badge.ok{ border-color:rgba(34,197,94,.25); color:#166534; }
#hero .badge.warn{ border-color:rgba(245,158,11,.25); color:#9a3412; }

#hero .priceRow{
  margin:14px 0 8px;
  display:flex; align-items:baseline; gap:10px; flex-wrap:wrap;
}
#hero .price{ font-weight:900; font-size:clamp(22px,3vw,34px); }
#hero .sale{ font-weight:900; font-size:clamp(22px,3vw,34px); color:var(--ok); }
#hero .old{ color:var(--muted); text-decoration:line-through; font-weight:800; }
#hero .save{
  color:#166534; background:#ecfdf5; border:1px solid #bbf7d0;
  font-weight:800; border-radius:999px; padding:5px 10px; font-size:.82rem;
}
#hero .sub{
  color:var(--muted);
  font-weight:700;
  margin:0 0 12px;
}

#hero .details{
  margin-top:14px;
  padding-top:14px;
  border-top:1px solid rgba(232,238,246,.95);
}
#hero .details h3{
  margin:0 0 10px;
  font-weight:800;
  letter-spacing:-.1px;
  font-size:1rem;
}
#hero .bullets{
  margin:0;
  padding:0;
  list-style:none;
  display:grid;
  gap:8px;
}
#hero .bullets li{
  display:flex; gap:10px; align-items:flex-start;
  color:#0f172a; font-weight:700; line-height:1.45;
}
#hero .bullets li:before{
  content:"";
  width:7px; height:7px; border-radius:999px;
  margin-top:6px;
  background:#22c55e;
  box-shadow:0 8px 16px rgba(2,8,23,.12);
  flex:0 0 7px;
}

#hero .actions{
  margin-top:16px;
  display:flex;
  gap:12px;
  flex-wrap:wrap;
  align-items:center;
}
#hero label{ color:var(--muted); font-weight:800; }
#hero .qty{
  width:140px;
  border:1px solid rgba(232,238,246,.95);
  border-radius:14px;
  padding:10px 12px;
  min-height:44px;
  background:#fff;
  font-weight:800;
}
@media (max-width: 520px){
  #hero .qty{ width:120px; }
}

#hero .favWrap{ display:flex; align-items:center; }

/* ================== ADD TO CART (MISMO BOTÓN ANIMADO) ================== */
.add-to-cart{
  --background-default:#17171B; --background-hover:#0A0A0C; --background-scale:1;
  --text-color:#fff; --text-o:1; --text-x:12px;
  --cart:#fff; --cart-x:-48px; --cart-y:0px; --cart-rotate:0deg; --cart-scale:.75;
  --cart-clip:0px; --cart-clip-x:0px; --cart-tick-offset:10px; --cart-tick-color:#FF328B;
  --shirt-y:-16px; --shirt-scale:0; --shirt-color:#17171B; --shirt-logo:#fff;
  --shirt-second-y:24px; --shirt-second-color:#fff; --shirt-second-logo:#17171B;
  -webkit-tap-highlight-color:transparent; appearance:none; outline:0; background:none; border:0;
  padding:12px 0; width:184px; margin:0; cursor:pointer; position:relative; font:inherit;
  border-radius:16px;
}
.add-to-cart:before{
  content:""; position:absolute; inset:0; border-radius:16px; transition:background .25s;
  background:var(--background,var(--background-default));
  transform:scaleX(var(--background-scale)) translateZ(0);
}
.add-to-cart:not(.active):hover{ --background:var(--background-hover); }
.add-to-cart>span{
  position:relative; z-index:1; display:block; text-align:center;
  font-size:14px; font-weight:900; line-height:24px; color:var(--text-color);
  opacity:var(--text-o); transform:translateX(var(--text-x)) translateZ(0);
}
.add-to-cart svg{ display:block; stroke-linecap:round; stroke-linejoin:round; }
.add-to-cart .morph{
  width:64px; height:13px; position:absolute; left:50%; top:-12px; margin-left:-32px;
  fill:var(--background,var(--background-default)); transition:fill .25s; pointer-events:none;
}
.add-to-cart .shirt, .add-to-cart .cart{ pointer-events:none; position:absolute; left:50%; }
.add-to-cart .shirt{
  top:0; margin:-12px 0 0 -12px; transform-origin:50% 100%;
  transform:translateY(var(--shirt-y)) scale(var(--shirt-scale));
}
.add-to-cart .shirt svg{ width:24px; height:24px; }
.add-to-cart .shirt svg path{ fill:var(--shirt-color); }
.add-to-cart .shirt svg g path{ fill:var(--shirt-logo); }
.add-to-cart .shirt svg.second{
  position:absolute; top:0; left:0;
  clip-path:polygon(0 var(--shirt-second-y),24px var(--shirt-second-y),24px 24px,0 24px);
}
.add-to-cart .shirt svg.second path{ fill:var(--shirt-second-color); }
.add-to-cart .shirt svg.second g path{ fill:var(--shirt-second-logo); }
.add-to-cart .cart{
  width:36px; height:26px; top:10px; margin-left:-18px;
  transform:translate(var(--cart-x),var(--cart-y)) rotate(var(--cart-rotate)) scale(var(--cart-scale)) translateZ(0);
}
.add-to-cart .cart:before{
  content:""; width:22px; height:12px; position:absolute; left:7px; top:7px; background:var(--cart);
  clip-path:polygon(0 0,22px 0, calc(22px - var(--cart-clip-x)) var(--cart-clip), var(--cart-clip-x) var(--cart-clip));
}
.add-to-cart .cart .shape{ fill:none; stroke:var(--cart); stroke-width:2; }
.add-to-cart .cart .wheel{ fill:none; stroke:var(--cart); stroke-width:1.5; }
.add-to-cart .cart .tick{
  fill:none; stroke:var(--cart-tick-color); stroke-width:2;
  stroke-dasharray:10px; stroke-dashoffset:var(--cart-tick-offset);
}

/* ===== Toast minimal ===== */
#product .toast{
  position:fixed;
  left:50%;
  bottom:16px;
  transform:translateX(-50%);
  z-index:9999;
  display:flex;
  align-items:center;
  gap:10px;
  padding:12px 14px;
  border-radius:999px;
  background:rgba(17,24,39,.92);
  color:#fff;
  border:1px solid rgba(255,255,255,.10);
  box-shadow:0 20px 50px rgba(0,0,0,.25);
  opacity:0;
  pointer-events:none;
  transition:opacity .22s ease, transform .22s ease;
}
#product .toast.show{ opacity:1; transform:translateX(-50%) translateY(-6px); }
#product .toast .dot{ width:10px; height:10px; border-radius:999px; background:#22c55e; }
#product .toast .dot.warn{ background:#f59e0b; }
#product .toast b{ font-weight:900; }

/* ================= SIMILARES (IGUAL, NO CAMBIO A CARDS EXTRA) ================= */
#sim{
  background:#0b1f33; color:#eaf2ff; padding:clamp(24px,4vw,42px) 0;
  position:relative; left:50%; right:50%; margin-left:-50vw; margin-right:-50vw; width:100vw;
}
#sim .container{ position:relative; }
#sim .sim-head{ display:flex; align-items:flex-end; justify-content:space-between; gap:10px; margin-bottom:12px }
#sim .sim-title{ margin:0; font-weight:800; color:#eaf2ff; font-size:clamp(18px,3vw,24px) }
#sim .more{ color:#fff; font-weight:800; }

#sim .row{ display:flex; gap:14px; overflow-x:auto; scroll-snap-type:x mandatory; padding-bottom:12px }
#sim .row::-webkit-scrollbar{ height:8px }
#sim .row::-webkit-scrollbar-thumb{ background:#204463; border-radius:999px }

#sim .card{
  min-width:240px; max-width:260px; scroll-snap-align:start;
  background:#fff; border:1px solid #e6edf6;
  border-radius:16px; overflow:hidden;
  box-shadow:0 12px 30px rgba(0,0,0,.08);
  display:flex; flex-direction:column;
}
#sim .sim-img{ width:100%; aspect-ratio:4/3; object-fit:cover; background:#f6f8fc }
#sim .sim-body{ padding:12px; display:flex; flex-direction:column; gap:8px; color:#0f172a }
#sim .sim-name{ font-weight:800; line-height:1.2; margin:0 }
#sim .sim-price{ font-weight:900 }
#sim .sim-old{ color:#6b7280; text-decoration:line-through; margin-left:6px; font-weight:800 }
#sim .sim-actions{ display:flex; gap:8px; align-items:center; margin-top:auto; flex-wrap:wrap }

/* Flechas navegación similares */
#sim .sim-nav{ position:absolute; inset:0; pointer-events:none; }
#sim .sim-nav button{
  pointer-events:auto; position:absolute; top:50%; transform:translateY(-50%);
  border:0; width:44px; height:44px; border-radius:999px; cursor:pointer;
  background:#fff; box-shadow:0 16px 30px rgba(2,8,23,.35);
  display:flex; align-items:center; justify-content:center;
}
#sim .sim-nav .prev{ left: clamp(6px,2vw,16px); }
#sim .sim-nav .next{ right: clamp(6px,2vw,16px); }
#sim .sim-nav button svg{ width:22px; height:22px; color:#0b1f33 }
#sim .sim-nav button:hover{ transform:translateY(-50%) scale(1.04) }
@media (max-width: 980px){ #sim .sim-nav{ display:none; } }
</style>

@php
  $price   = (float)($item->price ?? 0);
  $sale    = !is_null($item->sale_price) ? (float)$item->sale_price : null;
  $final   = $sale ?? $price;
  $savePct = ($sale && $price>0) ? max(1, round(100 - (($sale/$price)*100))) : null;
  $monthly = $final > 0 ? round($final/12, 2) : 0;

  // === IMÁGENES (solo 3: photo_1/2/3) ===
  $imgUrl = function($raw){
    if(!$raw || !is_string($raw) || trim($raw)==='') return null;
    $raw = trim($raw);
    if (\Illuminate\Support\Str::startsWith($raw, ['http://','https://'])) return $raw;
    if (\Illuminate\Support\Str::startsWith($raw, ['storage/'])) return asset($raw);
    return \Illuminate\Support\Facades\Storage::url($raw);
  };

  $images = array_values(array_filter(array_unique([
    $imgUrl($item->photo_1 ?? null),
    $imgUrl($item->photo_2 ?? null),
    $imgUrl($item->photo_3 ?? null),
  ])));
  if(empty($images)) $images = [asset('images/placeholder.png')];

  // Detalles en bullets (sin “ficha técnica”)
  $lines = [];
  if(!empty($item->description)){
    $lines = preg_split("/\r\n|\n|\r/", strip_tags($item->description));
    $lines = array_values(array_filter(array_map('trim', $lines)));
  }
  if(empty($lines) && !empty($item->excerpt)){
    $lines = [trim(strip_tags($item->excerpt))];
  }

  $similars = \App\Models\CatalogItem::published()
      ->where('id','!=',$item->id)
      ->when(($item->category_id ?? null), fn($q)=>$q->where('category_id',$item->category_id),
             function($q) use ($item){ if(!empty($item->brand)) $q->where('brand',$item->brand); })
      ->ordered()->take(12)->get();

  if($similars->isEmpty()){
      $similars = \App\Models\CatalogItem::published()->ordered()->take(12)->get();
  }

  $discountPct = function($p){
      if(is_null($p->sale_price) || !$p->price || $p->sale_price >= $p->price) return null;
      return max(1, round(100 - (($p->sale_price / $p->price) * 100)));
  };

  $pickPhotoUrl = function($p) use ($imgUrl){
    foreach([$p->photo_1 ?? null, $p->photo_2 ?? null, $p->photo_3 ?? null] as $c){
      $u = $imgUrl($c);
      if($u) return $u;
    }
    return asset('images/placeholder.png');
  };
@endphp

<div id="product">
  <div class="bg-grad" aria-hidden="true"></div>

  <div class="toast" id="pcToast" role="status" aria-live="polite">
    <span class="dot" id="pcToastDot"></span>
    <div id="pcToastMsg"><b>Listo</b> · Se agregó al carrito</div>
  </div>

  <section id="hero">
    <div class="container">
      <div class="topbar">
        <a class="back" href="{{ route('web.catalog.index') }}">← Volver al catálogo</a>
      </div>

      <div class="grid">
        {{-- Galería --}}
        <div class="media">
          <div class="stage" data-gallery>
            <img id="galMain"
                 src="{{ $images[0] }}"
                 alt="{{ $item->name }}"
                 loading="eager"
                 onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">

            <div class="pill" aria-hidden="true"><span class="dot"></span> Fotos reales del producto</div>

            @if(count($images) > 1)
              <div class="nav" aria-hidden="false">
                <button type="button" id="galPrev" aria-label="Foto anterior">
                  <svg viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button type="button" id="galNext" aria-label="Siguiente foto">
                  <svg viewBox="0 0 24 24" fill="none"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
              </div>
            @endif
          </div>

          @if(count($images) > 1)
            <div class="thumbs" id="galThumbs">
              @foreach($images as $i => $u)
                <img class="thumb {{ $i===0 ? 'active' : '' }}"
                     src="{{ $u }}"
                     alt="Vista {{ $i+1 }}"
                     loading="lazy"
                     data-idx="{{ $i }}"
                     onerror="this.style.display='none'">
              @endforeach
            </div>
          @endif
        </div>

        {{-- Info (sin contenedor extra) --}}
        <div class="info">
          <h1>{{ $item->name }}</h1>

          <div class="meta">
            <span>SKU: <b>{{ $item->sku ?: '—' }}</b></span>
            <span class="dot" aria-hidden="true"></span>
            <span>Marca: <b>{{ $item->brand ?? '—' }}</b></span>
          </div>

          <div class="chips">
            <span class="chip">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h10M4 17h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
              Factura inmediata
            </span>
            <span class="chip">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 13h15l3-5H6L3 13Zm3 5a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm12 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>
              Envío rápido
            </span>
          </div>

          <div class="badges">
            @if(($item->stock ?? 0) > 0)
              <span class="badge ok">En stock ({{ $item->stock }})</span>
            @else
              <span class="badge warn">Sobre pedido</span>
            @endif
            <span class="badge">Soporte técnico</span>
            <span class="badge">Pagos con meses</span>
          </div>

          <div class="priceRow">
            @if($sale)
              <div class="sale">${{ number_format($sale,2) }}</div>
              <div class="old">${{ number_format($price,2) }}</div>
              @if($savePct)<span class="save">Ahorra {{ $savePct }}%</span>@endif
            @else
              <div class="price">${{ number_format($price,2) }}</div>
            @endif
          </div>

          <p class="sub">Paga a 12 meses desde <b style="color:#0f172a">${{ number_format($monthly,2) }}</b>*</p>

          @if(!empty($lines))
            <div class="details" aria-label="Información del producto">
              <h3>Información</h3>
              <ul class="bullets">
                @foreach(array_slice($lines, 0, 10) as $ln)
                  <li>{{ $ln }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="actions">
            <form action="{{ route('web.cart.add') }}" method="POST" class="pcAddForm" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
              @csrf
              <input type="hidden" name="catalog_item_id" value="{{ $item->id }}">

              <label for="qty">Cantidad</label>
              <input id="qty" class="qty" type="number" name="qty" min="1" value="1">

              <button type="submit" class="add-to-cart" data-submit-delay="850">
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

            <div class="favWrap">
              @includeIf('web.favoritos.button', ['item'=>$item])
            </div>
          </div>

        </div>
      </div>
    </div>
  </section>

  {{-- ================= SIMILARES ================= --}}
  @if($similars->count())
    <section id="sim">
      <div class="container">
        <div class="sim-head">
          <h2 class="sim-title">Productos similares</h2>
          <a class="more" href="{{ route('web.catalog.index') }}">Ver más</a>
        </div>

        <div class="row" id="simRow">
          @foreach($similars as $p)
            @php
              $off = $discountPct($p);
              $simImg = $pickPhotoUrl($p);
            @endphp
            <article class="card">
              <a href="{{ route('web.catalog.show', $p) }}" aria-label="Ver {{ $p->name }}">
                <img class="sim-img"
                     src="{{ $simImg }}"
                     alt="{{ $p->name }}"
                     loading="lazy"
                     onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
              </a>

              <div class="sim-body">
                <h3 class="sim-name">{{ $p->name }}</h3>

                <div>
                  @if(!is_null($p->sale_price))
                    <span class="sim-price" style="color:var(--ok)">${{ number_format($p->sale_price,2) }}</span>
                    <span class="sim-old">${{ number_format($p->price,2) }}</span>
                    @if($off)
                      <span class="badge warn" style="margin-left:6px;">-{{ $off }}%</span>
                    @endif
                  @else
                    <span class="sim-price">${{ number_format($p->price,2) }}</span>
                  @endif
                </div>

                <div class="sim-actions">
                  @includeIf('web.favoritos.button', ['item'=>$p])

                  <form action="{{ route('web.cart.add') }}" method="POST" class="pcAddForm" style="display:inline-flex;gap:6px;align-items:center">
                    @csrf
                    <input type="hidden" name="catalog_item_id" value="{{ $p->id }}">
                    <button type="submit" class="add-to-cart" style="width:164px;border-radius:14px" data-submit-delay="800">
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

        <div class="sim-nav" aria-hidden="true">
          <button class="prev" id="simPrev" type="button" title="Anterior">
            <svg viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          <button class="next" id="simNext" type="button" title="Siguiente">
            <svg viewBox="0 0 24 24" fill="none"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
        </div>
      </div>
    </section>
  @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
<script>
(() => {
  /* ================== GALERÍA < > con transición ================== */
  const images = @json($images);
  let idx = 0;

  const stage = document.querySelector('#hero .stage');
  const main = document.getElementById('galMain');
  const thumbs = document.getElementById('galThumbs');
  const prevBtn = document.getElementById('galPrev');
  const nextBtn = document.getElementById('galNext');

  function setThumb(next){
    if(!thumbs) return;
    thumbs.querySelectorAll('.thumb').forEach(t=>t.classList.remove('active'));
    const el = thumbs.querySelector(`.thumb[data-idx="${next}"]`);
    if(el) el.classList.add('active');
  }

  function swapTo(next, dir){
    if(!main || next === idx) return;

    const outgoing = main;
    const incoming = outgoing.cloneNode(true);
    incoming.removeAttribute('id');
    incoming.src = images[next] || outgoing.src;

    incoming.classList.add(dir > 0 ? 'inR' : 'inL');
    stage.appendChild(incoming);

    requestAnimationFrame(()=>{
      outgoing.classList.add(dir > 0 ? 'outL' : 'outR');
      incoming.classList.remove('inL','inR');
    });

    incoming.addEventListener('transitionend', ()=>{
      outgoing.src = incoming.src;
      outgoing.classList.remove('outL','outR');
      incoming.remove();
    }, { once:true });

    idx = next;
    setThumb(idx);
  }

  thumbs?.addEventListener('click', (e)=>{
    const t = e.target.closest('.thumb');
    if(!t) return;
    const next = parseInt(t.dataset.idx || '0', 10);
    swapTo(next, next > idx ? 1 : -1);
  });

  prevBtn?.addEventListener('click', ()=>{
    const next = (idx - 1 + images.length) % images.length;
    swapTo(next, -1);
  });
  nextBtn?.addEventListener('click', ()=>{
    const next = (idx + 1) % images.length;
    swapTo(next, 1);
  });

  window.addEventListener('keydown', (e)=>{
    if(images.length < 2) return;
    if(e.key === 'ArrowLeft') prevBtn?.click();
    if(e.key === 'ArrowRight') nextBtn?.click();
  });

  /* ================== Cantidad mínima ================== */
  const qty = document.getElementById('qty');
  if(qty){ qty.addEventListener('input', ()=>{ if(!qty.value || qty.value < 1) qty.value = 1; }); }

  /* ================== Toast ================== */
  const toast = document.getElementById('pcToast');
  const toastMsg = document.getElementById('pcToastMsg');
  const toastDot = document.getElementById('pcToastDot');
  let timer = null;

  function showToast(html, type='ok'){
    if(!toast) return;
    toastMsg.innerHTML = html;
    toastDot.classList.toggle('warn', type !== 'ok');
    toast.classList.add('show');
    clearTimeout(timer);
    timer = setTimeout(()=>toast.classList.remove('show'), 2100);
  }

  /* ================== POST carrito sin recarga ================== */
  async function ajaxSubmit(form){
    const action = form.getAttribute('action');
    const fd = new FormData(form);

    const res = await fetch(action, {
      method:'POST',
      body:fd,
      headers:{
        'X-Requested-With':'XMLHttpRequest',
        'Accept':'application/json'
      },
      credentials:'same-origin'
    });

    let data = null;
    try{ data = await res.clone().json(); }catch(e){}
    let ok = res.ok || res.status === 302 || res.status === 301;
    return { ok, data, status: res.status };
  }

  /* ================== Animación botón + submit AJAX ================== */
  document.querySelectorAll('.add-to-cart').forEach(btn=>{
    const submitDelay = parseInt(btn.dataset.submitDelay || '850', 10);

    btn.addEventListener('pointerdown', ()=>{
      if(btn.classList.contains('active')) return;
      gsap.to(btn, { '--background-scale': .97, duration: .12 });
    });

    btn.addEventListener('click', (e)=>{
      const form = btn.closest('form');
      if(!form) return;

      e.preventDefault();
      if(btn.classList.contains('active')) return;

      btn.classList.add('active');
      btn.style.pointerEvents = 'none';
      btn.style.setProperty('--text-o', 0);

      gsap.to(btn, {
        keyframes: [
          { '--background-scale': .97, duration: .10 },
          { '--background-scale': 1, duration: 1.0, ease: 'elastic.out(1,.6)' }
        ]
      });

      gsap.to(btn, {
        keyframes: [
          { '--shirt-scale': 1, '--shirt-y': '-42px', '--cart-x': '0px', '--cart-scale': 1, duration: .35, ease: 'power1.in' },
          { '--shirt-y': '16px', '--shirt-scale': .9, duration: .25 },
          { '--shirt-scale': 0, duration: .25 }
        ]
      });
      gsap.to(btn, { '--shirt-second-y': '0px', delay: .7, duration: .1 });

      gsap.to(btn, {
        keyframes: [
          { '--cart-clip': '12px', '--cart-clip-x': '3px', delay: .78, duration: .06 },
          { '--cart-y': '2px', duration: .10 },
          { '--cart-tick-offset': '0px', '--cart-y': '0px', duration: .18 },
          { '--cart-x': '52px', '--cart-rotate': '-15deg', duration: .16 },
          { '--cart-x': '104px', '--cart-rotate': '0deg', duration: .16, onComplete(){ btn.style.setProperty('--cart-x', '-104px'); } },
          { '--text-o': 1, '--text-x': '12px', '--cart-x': '-48px', '--cart-scale': .75, duration: .22,
            onComplete(){
              btn.classList.remove('active');
              btn.style.pointerEvents = '';
            }
          }
        ]
      });

      setTimeout(async ()=>{
        try{
          const { ok } = await ajaxSubmit(form);
          if(ok) showToast('<b>Listo</b> · Se agregó al carrito', 'ok');
          else showToast('<b>Ups</b> · No se pudo agregar', 'warn');
        }catch(err){
          showToast('<b>Error</b> · Intenta de nuevo', 'warn');
        }
      }, submitDelay);
    });
  });

  /* ================== SIMILARES: scroll infinito + flechas ================== */
  (function(){
    const row = document.getElementById('simRow');
    if(!row) return;

    let isDown=false,startX,scrollLeft;
    row.addEventListener('mousedown',e=>{isDown=true;startX=e.pageX-row.offsetLeft;scrollLeft=row.scrollLeft;});
    row.addEventListener('mouseleave',()=>isDown=false);
    row.addEventListener('mouseup',()=>isDown=false);
    row.addEventListener('mousemove',e=>{ if(!isDown) return; e.preventDefault(); const x=e.pageX-row.offsetLeft; const walk=(x-startX); row.scrollLeft=scrollLeft-walk;});
    row.addEventListener('touchstart',e=>{isDown=true;startX=e.touches[0].pageX-row.offsetLeft;scrollLeft=row.scrollLeft;},{passive:true});
    row.addEventListener('touchend',()=>isDown=false);

    const originals = Array.from(row.children);
    originals.forEach(el=>row.appendChild(el.cloneNode(true)));

    requestAnimationFrame(()=>{
      const first = row.children[0];
      const w = first.getBoundingClientRect().width + 14;
      row.scrollLeft = originals.length * w;
    });

    row.addEventListener('scroll', ()=>{
      const first = row.children[0]; if(!first) return;
      const w = first.getBoundingClientRect().width + 14;
      const mid = originals.length * w;
      const total = row.scrollWidth;
      if(row.scrollLeft <= 0){ row.scrollLeft += mid; }
      else if(row.scrollLeft + row.clientWidth >= total-2){ row.scrollLeft -= mid; }
    }, {passive:true});

    const prev = document.getElementById('simPrev');
    const next = document.getElementById('simNext');
    const stepFn = ()=> {
      const c = row.children[0];
      const w = (c ? c.getBoundingClientRect().width : 260) + 14;
      return w * 2;
    }
    prev?.addEventListener('click', ()=> row.scrollBy({left: -stepFn(), behavior:'smooth'}));
    next?.addEventListener('click', ()=> row.scrollBy({left:  stepFn(), behavior:'smooth'}));
  })();
})();
</script>
@endsection
