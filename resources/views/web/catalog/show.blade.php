{{-- resources/views/web/catalog/show.blade.php --}}
@extends('layouts.web')
@section('title', $item->name)

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ================= BASE / TOKENS ================= */
#product{
  --ink: #333333;
  --muted: #888888;
  --line: #ebebeb;
  --ok: #15803d;
  --warn: #ff4a4a;
  --blue: #007aff;
  --bg: #ffffff;

  margin:0;
  font-family:"Quicksand", system-ui, -apple-system, sans-serif;
  color:var(--ink);
  background: var(--bg);
}
#product a{ text-decoration:none; color:inherit; }

#product .container{
  width:100%;
  max-width:1100px;
  padding-inline:clamp(16px, 3vw, 24px);
  margin-inline:auto;
}

/* ================= TOPBAR ================= */
#hero{
  padding-top: 20px;
  padding-bottom: 40px;
}
#hero .topbar{
  margin-bottom: 20px;
}
#hero .back{
  display:inline-flex; align-items:center; gap:6px;
  font-weight:600;
  color: var(--muted);
  font-size: 14px;
  transition:color .2s ease;
}
#hero .back:hover{ color: var(--blue); }
#hero .back svg { width: 16px; height: 16px; }

/* ================= GRID ================= */
#hero .grid{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap:clamp(30px, 5vw, 60px);
  align-items:start;
}
@media (max-width: 860px){
  #hero .grid{ grid-template-columns:1fr; }
}

/* ================= GALERÍA (IZQUIERDA) ================= */
#hero .media{
  display: flex;
  flex-direction: column;
  gap: 16px;
}
#hero .stage{
  position:relative;
  width:100%;
  aspect-ratio: 1 / 1.1;
  background:#fff;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}
#hero .stage img{
  width:100%; height:100%;
  object-fit:contain;
  opacity:1;
  transform:scale(1);
  filter:blur(0px);
  will-change:opacity, transform, filter;
}

/* OVERLAY FAVORITOS (CORAZÓN PRINCIPAL) */
.fav-overlay {
  position: absolute;
  top: 14px;
  right: 14px;
  z-index: 10;
}
.fav-btn {
  width: 42px; height: 42px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(6px);
  border: 1px solid rgba(0,0,0,0.05);
  display: flex; align-items: center; justify-content: center;
  color: #a1a1aa;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  transition: all 0.2s ease;
}
.fav-btn svg { width: 22px; height: 22px; transition: fill 0.2s; }
.fav-btn:hover { background: #fff; transform: scale(1.06); color: #ff4a4a; }
.fav-btn.active { color: #ff4a4a; }
.fav-btn.active svg { fill: #ff4a4a; }

#hero .nav{
  position:absolute; inset:0;
  display:flex; align-items:center; justify-content:space-between;
  padding:0 10px;
  pointer-events:none;
}
#hero .nav button{
  pointer-events:auto;
  width:40px; height:40px;
  border-radius:50%;
  border:1px solid var(--line);
  background:#fff;
  box-shadow:0 4px 12px rgba(0,0,0,.08);
  cursor:pointer;
  display:flex; align-items:center; justify-content:center;
  transition:transform .14s ease, box-shadow .14s ease;
}
#hero .nav button:hover{ transform:scale(1.05); box-shadow:0 6px 16px rgba(0,0,0,.12); }
#hero .nav button:active{ transform:scale(.98); }
#hero .nav svg{ width:20px; height:20px; color:#333; }

#hero .thumbs{
  display:flex; gap:12px; flex-wrap:wrap;
  justify-content: center;
}
#hero .thumb{
  width:64px; height:64px;
  border-radius:8px;
  border:1px solid transparent;
  object-fit:cover;
  cursor:pointer;
  background:#fff;
  transition:all .2s ease;
}
#hero .thumb:hover{ border-color: #ccc; }
#hero .thumb.active{ border-color: var(--blue); }

/* ================= INFO (DERECHA) ================= */
#hero .info{ 
  padding-top: 10px; 
  display: flex;
  flex-direction: column;
}

/* Etiquetas superiores */
#hero .tags{
  display:flex; flex-wrap:wrap; gap:8px; margin-bottom:12px;
}
#hero .tag{
  font-size: 11px;
  font-weight: 700;
  padding: 4px 8px;
  border-radius: 4px;
}
#hero .tag.oferta{ background: #ffebeb; color: var(--warn); }
#hero .tag.intereses{ background: #e6f0ff; color: var(--blue); }
#hero .tag.envio{ background: #e6ffe6; color: var(--ok); }

/* Título y Vendedor */
#hero h1{
  margin:0 0 8px;
  font-weight:600;
  font-size: 22px;
  line-height:1.3;
  color: #111;
}
#hero .vendor{
  font-size: 13px;
  color: var(--muted);
  margin-bottom: 8px;
}
#hero .vendor b{ color: #333; font-weight: 600; }

/* Estrellas */
#hero .rating{
  display: flex; align-items: center; gap: 6px;
  margin-bottom: 16px;
}
#hero .stars{ display: flex; gap: 2px; color: var(--blue); }
#hero .stars svg{ width: 14px; height: 14px; fill: currentColor; }
#hero .reviews-count{ font-size: 13px; color: var(--muted); }

/* Precios */
#hero .pricing{
  display:flex; align-items:baseline; gap:8px; flex-wrap:wrap;
  margin-bottom: 8px;
}
#hero .price-now{ font-weight:700; font-size:28px; color:var(--warn); }
#hero .price-old{ color:#a1a1aa; text-decoration:line-through; font-size: 16px; font-weight:500; }

/* Mensualidades */
#hero .installments{
  color: var(--blue);
  font-weight: 700;
  font-size: 15px;
  margin-bottom: 24px;
}

/* ================= BOTÓN ANIMADO ADAPTADO ================= */
.add-to-cart{
  --background-default: #007aff; 
  --background-hover: #0062cc; 
  --background-scale: 1;
  --text-color: #fff; 
  --text-o: 1; 
  --text-x: 12px;
  --cart: #fff; 
  --cart-x: -48px; 
  --cart-y: 0px; 
  --cart-rotate: 0deg; 
  --cart-scale: .75;
  --cart-clip: 0px; 
  --cart-clip-x: 0px; 
  --cart-tick-offset: 10px; 
  --cart-tick-color: #34d399; 
  --shirt-y: -16px; 
  --shirt-scale: 0; 
  --shirt-color: #003d82; 
  --shirt-logo: #fff;
  --shirt-second-y: 24px; 
  --shirt-second-color: #fff; 
  --shirt-second-logo: #007aff;
  
  -webkit-tap-highlight-color: transparent; 
  appearance: none; outline: 0; background: none; border: 0;
  padding: 14px 0; 
  width: 100%; 
  margin: 0; cursor: pointer; position: relative; font: inherit;
  border-radius: 999px; 
}
.add-to-cart:before{
  content:""; position:absolute; inset:0; border-radius:999px; transition:background .25s;
  background:var(--background,var(--background-default));
  transform:scaleX(var(--background-scale)) translateZ(0);
}
.add-to-cart:not(.active):hover{ --background:var(--background-hover); }
.add-to-cart>span{
  position:relative; z-index:1; display:block; text-align:center;
  font-size:15px; font-weight:700; line-height:24px; color:var(--text-color);
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

/* ================= ACORDEÓN & LISTA DE CARACTERÍSTICAS ================= */
#hero .features-list{
  margin-top: 30px;
  display: flex;
  flex-direction: column;
}
.details-accordion {
  border-bottom: 1px solid var(--line);
}
.accordion-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 16px 0; cursor: pointer; color: #444; font-weight: 500; font-size: 14px;
}
#accordionIcon { width: 16px; height: 16px; color: #999; transition: transform 0.3s ease; }
.accordion-content {
  max-height: 0; overflow: hidden; transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.accordion-content.open { max-height: 1000px; }
.content-inner {
  padding-bottom: 20px; color: #64748b; font-size: 14px; line-height: 1.6;
}
.content-inner ul { padding-left: 20px; margin: 0; }
.content-inner ul li { margin-bottom: 6px; }

#hero .feature-item{
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 0;
  border-bottom: 1px solid var(--line);
  font-size: 14px;
  font-weight: 500;
  color: #444;
}
#hero .feature-icon-text{
  display: flex; align-items: center; gap: 12px;
}
#hero .feature-icon-text svg{
  width: 20px; height: 20px; color: var(--blue);
}

/* ================= COMO COMPRAR ================= */
#hero .how-to-buy{
  margin-top: 30px;
  padding-top: 20px;
}
#hero .how-to-buy-header{
  display: flex; justify-content: space-between; align-items: center;
  font-size: 15px; font-weight: 600; margin-bottom: 24px;
}
#hero .how-to-buy-header svg{ width: 16px; height: 16px; color: #999; }
#hero .steps{
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  text-align: center;
}
#hero .step-icon{
  width: 48px; height: 48px;
  border-radius: 50%;
  border: 1px solid var(--line);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 12px;
}
#hero .step-icon svg{ width: 20px; height: 20px; color: var(--blue); }
#hero .step-text{
  font-size: 10px;
  color: var(--muted);
  line-height: 1.4;
}

/* ===== Toast minimal ===== */
#product .toast{
  position:fixed; left:50%; bottom:16px; transform:translateX(-50%);
  z-index:9999; display:flex; align-items:center; gap:10px;
  padding:12px 14px; border-radius:999px; background:rgba(17,24,39,.92);
  color:#fff; border:1px solid rgba(255,255,255,.10);
  box-shadow:0 20px 50px rgba(0,0,0,.25);
  opacity:0; pointer-events:none; transition:opacity .22s ease, transform .22s ease;
}
#product .toast.show{ opacity:1; transform:translateX(-50%) translateY(-6px); }
#product .toast .dot{ width:10px; height:10px; border-radius:999px; background:#22c55e; }
#product .toast .dot.warn{ background:#f59e0b; }
#product .toast b{ font-weight:900; }

/* ================= SIMILARES ================= */
#sim{
  background: #f9fafb; 
  padding: 60px 0;
  border-top: 1px solid var(--line);
}
#sim .sim-head{ display:flex; align-items:center; justify-content:space-between; margin-bottom:24px }
#sim .sim-title{ margin:0; font-weight:700; color:#111; font-size:20px }

#sim .row{ display:flex; gap:20px; overflow-x:auto; scroll-snap-type:x mandatory; padding-bottom:20px }
#sim .row::-webkit-scrollbar{ display: none; }

#sim .card{
  min-width:200px; max-width:220px; scroll-snap-align:start;
  background:#fff;
  border-radius:12px;
  border: 1px solid #f1f5f9;
  display:flex; flex-direction:column;
  text-decoration: none;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
#sim .card:hover{
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
}

#sim .sim-img{ 
  width:100%; aspect-ratio:1/1; object-fit:contain; 
  padding: 15px;
  margin-bottom: 4px; 
  transition: transform 0.3s ease;
}
#sim .card:hover .sim-img{
  transform: scale(1.04);
}
#sim .sim-body{ display:flex; flex-direction:column; gap:6px; color:#333; padding: 0 16px 16px 16px; }
#sim .sim-name{ font-weight:500; font-size: 14px; line-height:1.4; margin:0; color: #475569; }
#sim .sim-price{ font-weight:700; font-size: 16px; color: #1e293b; }
#sim .sim-old{ color:#a1a1aa; text-decoration:line-through; margin-left:6px; font-size: 13px; font-weight:500; }
</style>

@php
  $price   = (float)($item->price ?? 0);
  $sale    = !is_null($item->sale_price) ? (float)$item->sale_price : null;
  $final   = $sale ?? $price;
  $savePct = ($sale && $price>0) ? max(1, round(100 - (($sale/$price)*100))) : null;
  $monthly = $final > 0 ? round($final/4, 2) : 0; 

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

  <div class="toast" id="pcToast" role="status" aria-live="polite">
    <span class="dot" id="pcToastDot"></span>
    <div id="pcToastMsg"><b>Listo</b> · Se agregó al carrito</div>
  </div>

  <section id="hero">
    <div class="container">
      <div class="topbar">
        <a class="back" href="{{ route('web.catalog.index') }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
          Volver
        </a>
      </div>

      <div class="grid">
        {{-- ================= GALERÍA (IZQUIERDA) ================= --}}
        <div class="media">
          <div class="stage" data-gallery>
            
            {{-- BOTÓN DE FAVORITOS (IMAGEN PRINCIPAL) --}}
            <div class="fav-overlay">
              <button type="button" class="fav-btn js-fav-toggle" aria-label="Favoritos">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
              </button>
              {{-- Formulario oculto para aprovechar lógica de backend existente --}}
              <div style="display:none;">@includeIf('web.favoritos.button', ['item'=>$item])</div>
            </div>

            <img id="galMain"
                 src="{{ $images[0] }}"
                 alt="{{ $item->name }}"
                 loading="eager"
                 onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">

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

        {{-- ================= INFO (DERECHA) ================= --}}
        <div class="info">
          
          <div class="tags">
            @if($sale && $savePct)
              <span class="tag oferta">Oferta</span>
            @endif
            <span class="tag intereses">Sin intereses</span>
            <span class="tag envio">Envío gratis</span>
          </div>

          <h1>{{ $item->name }}</h1>
          
          <div class="vendor">
            Vendido por <b>Jureto</b>
          </div>

          <div class="rating">
            <span class="stars" aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
              <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
              <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
              <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
              <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            </span>
            <span class="reviews-count">(124)</span>
          </div>

          <div class="pricing">
            @if($sale)
              <div class="price-now">${{ number_format($sale,2) }}</div>
              <div class="price-old">${{ number_format($price,2) }}</div>
            @else
              <div class="price-now">${{ number_format($price,2) }}</div>
            @endif
          </div>

          <div class="installments">
            4 pagos sin intereses de ${{ number_format($monthly,2) }}
          </div>

          <div class="actions">
            <form action="{{ route('web.cart.add') }}" method="POST" class="pcAddForm">
              @csrf
              <input type="hidden" name="catalog_item_id" value="{{ $item->id }}">
              <input type="hidden" name="qty" value="1">

              <button type="submit" class="add-to-cart" data-submit-delay="850">
                <span>Ir a checkout</span>
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

          <div class="features-list">
            @if(!empty($lines))
              <div class="details-accordion">
                <div class="accordion-header" onclick="toggleAccordion()">
                  <span>Descripción del producto</span>
                  <svg id="accordionIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 9l6 6 6-6"/>
                  </svg>
                </div>
                <div class="accordion-content" id="accordionContent">
                  <div class="content-inner">
                    <ul class="bullets">
                      @foreach($lines as $ln)
                        <li>{{ $ln }}</li>
                      @endforeach
                    </ul>
                  </div>
                </div>
              </div>
            @endif
            
            <div class="feature-item">
              <div class="feature-icon-text">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Devoluciones 30 días después de tu compra
              </div>
            </div>

            <div class="feature-item">
              <div class="feature-icon-text">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                Envío gratuito
              </div>
            </div>

            <div class="feature-item">
              <div class="feature-icon-text">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Tu compra es segura
              </div>
            </div>
          </div>

          <div class="how-to-buy">
            <div class="how-to-buy-header">
              <span>¿Cómo comprar con Jureto?</span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 15l-6-6-6 6"/></svg>
            </div>
            
            <div class="steps">
              <div class="step">
                <div class="step-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </div>
                <div class="step-text">Regístrate y agrega tus productos</div>
              </div>
              <div class="step">
                <div class="step-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </div>
                <div class="step-text">Elige tu compra y haz checkout</div>
              </div>
              <div class="step">
                <div class="step-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                </div>
                <div class="step-text">Recibe tu compra en tu domicilio</div>
              </div>
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
        </div>

        <div class="row" id="simRow">
          @foreach($similars as $p)
            @php
              $off = $discountPct($p);
              $simImg = $pickPhotoUrl($p);
            @endphp
            <a href="{{ route('web.catalog.show', $p) }}" class="card" aria-label="Ver {{ $p->name }}">
              <img class="sim-img"
                   src="{{ $simImg }}"
                   alt="{{ $p->name }}"
                   loading="lazy"
                   onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">

              <div class="sim-body">
                <h3 class="sim-name">{{ \Illuminate\Support\Str::limit($p->name, 50) }}</h3>

                <div>
                  @if(!is_null($p->sale_price))
                    <span class="sim-price">${{ number_format($p->sale_price,2) }}</span>
                    <span class="sim-old">${{ number_format($p->price,2) }}</span>
                  @else
                    <span class="sim-price">${{ number_format($p->price,2) }}</span>
                  @endif
                </div>
              </div>
            </a>
          @endforeach
        </div>
      </div>
    </section>
  @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
<script>
/* ================== Acordeón ================== */
function toggleAccordion() {
  const content = document.getElementById('accordionContent');
  const icon = document.getElementById('accordionIcon');
  
  content.classList.toggle('open');
  if (content.classList.contains('open')) {
    icon.style.transform = 'rotate(180deg)';
  } else {
    icon.style.transform = 'rotate(0deg)';
  }
}

/* ================== Actualizar Carrito UI Global ================== */
function updateCartUI(newCount) {
  // Buscamos clases e IDs comunes que los e-commerce suelen tener en el header
  const cartBadges = document.querySelectorAll('.cart-count, .cart-badge, #cart-count, #cart-badge, .header-cart-count');
  
  cartBadges.forEach(badge => {
    if (newCount !== undefined && newCount !== null) {
      badge.textContent = newCount;
    } else {
      // Si el servidor no devolvió un count exacto, sumamos 1 por asunción
      const current = parseInt(badge.textContent || '0', 10);
      if (!isNaN(current)) badge.textContent = current + 1;
    }
    // Animamos el contador visualmente para que el usuario sepa que sumó
    gsap.fromTo(badge, { scale: 1.5 }, { scale: 1, duration: 0.4, ease: "back.out(1.5)" });
  });

  // Emitimos un evento global por si el Navbar usa Vue/React o Livewire
  window.dispatchEvent(new CustomEvent('cartUpdated', { detail: { count: newCount } }));
}

(() => {
  /* ================== FUNCIONALIDAD FAVORITOS AJAX ================== */
  document.querySelectorAll('.js-fav-toggle').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      
      // Toggle visual y animación GSAP ("Latido")
      const isActive = btn.classList.toggle('active');
      gsap.fromTo(btn, { scale: 0.7 }, { scale: 1, duration: 0.5, ease: "elastic.out(1, 0.4)" });

      // Busca el formulario original escondido que incluiste por backend
      const container = btn.closest('.fav-overlay');
      const hiddenForm = container.querySelector('form');
      
      if(hiddenForm) {
        try {
          const fd = new FormData(hiddenForm);
          await fetch(hiddenForm.getAttribute('action'), {
            method: hiddenForm.getAttribute('method') || 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
          });
        } catch (error) {
          // Si falla la petición, revertimos el estado visual del corazón
          btn.classList.toggle('active');
          console.error("Error al actualizar favoritos", error);
        }
      }
    });
  });

  /* ================== GALERÍA < > ================== */
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

  function swapTo(next){
    if(!main || next === idx || swapTo._busy) return;
    swapTo._busy = true;

    const outgoing = main;
    const incoming = outgoing.cloneNode(true);
    incoming.removeAttribute('id');
    incoming.src = images[next] || outgoing.src;
    stage.appendChild(incoming);

    gsap.set(incoming, { opacity: 0, scale: 0.985, filter: "blur(10px)" });

    const tl = gsap.timeline({
      defaults:{ ease:"power3.out" },
      onComplete: () => {
        outgoing.src = incoming.src;
        gsap.set(outgoing, { opacity: 1, scale: 1, filter: "blur(0px)" });
        incoming.remove();
        idx = next;
        setThumb(idx);
        swapTo._busy = false;
      }
    });

    tl.to(outgoing, { opacity: 0, scale: 1.015, filter: "blur(8px)", duration: 0.32, ease: "power2.out" }, 0);
    tl.to(incoming, { opacity: 1, scale: 1, filter: "blur(0px)", duration: 0.42, ease: "power3.out" }, 0.04);
    tl.to(incoming, { scale: 1.002, duration: 0.18, ease: "sine.out" }, ">-0.10");
  }

  thumbs?.addEventListener('click', (e)=>{
    const t = e.target.closest('.thumb');
    if(!t) return;
    const next = parseInt(t.dataset.idx || '0', 10);
    swapTo(next);
  });

  prevBtn?.addEventListener('click', ()=> swapTo((idx - 1 + images.length) % images.length) );
  nextBtn?.addEventListener('click', ()=> swapTo((idx + 1) % images.length) );

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

  /* ================== POST carrito ================== */
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

  /* ================== Animación botón + AJAX Carrito ================== */
  document.querySelectorAll('.add-to-cart').forEach(btn=>{
    const submitDelay = parseInt(btn.dataset.submitDelay || '850', 10);

    btn.addEventListener('pointerdown', ()=>{
      if(btn.classList.contains('active')) return;
      gsap.to(btn, { '--background-scale': .98, duration: .12 });
    });

    btn.addEventListener('click', (e)=>{
      const form = btn.closest('form');
      if(!form) return;

      e.preventDefault();
      if(btn.classList.contains('active')) return;

      btn.classList.add('active');
      btn.style.pointerEvents = 'none';
      btn.style.setProperty('--text-o', 0);

      // 1. Efecto presionar botón
      gsap.to(btn, {
        keyframes: [
          { '--background-scale': .98, duration: .10 },
          { '--background-scale': 1, duration: 1.0, ease: 'elastic.out(1,.6)' }
        ]
      });

      // 2. Efecto tirar a carrito
      gsap.to(btn, {
        keyframes: [
          { '--shirt-scale': 1, '--shirt-y': '-42px', '--cart-x': '0px', '--cart-scale': 1, duration: .35, ease: 'power1.in' },
          { '--shirt-y': '16px', '--shirt-scale': .9, duration: .25 },
          { '--shirt-scale': 0, duration: .25 }
        ]
      });
      gsap.to(btn, { '--shirt-second-y': '0px', delay: .7, duration: .1 });

      // 3. Efecto salida de carrito
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

      // Llamada AJAX sincronizada con el delay de la animación visual
      setTimeout(async ()=>{
        try{
          const { ok, data } = await ajaxSubmit(form);
          if(ok) {
            showToast('<b>Listo</b> · Se agregó al carrito', 'ok');
            // SE ACTUALIZA LA INTERFAZ DEL CARRITO EN TIEMPO REAL
            updateCartUI(data?.cart_count ?? data?.count ?? data?.total_items); 
          }
          else {
            showToast('<b>Ups</b> · No se pudo agregar', 'warn');
          }
        }catch(err){
          showToast('<b>Error</b> · Intenta de nuevo', 'warn');
        }
      }, submitDelay);
    });
  });

  /* ================== Drag para SIMILARES ================== */
  (function(){
    const row = document.getElementById('simRow');
    if(!row) return;

    let isDown=false,startX,scrollLeft;
    row.addEventListener('mousedown',e=>{isDown=true;startX=e.pageX-row.offsetLeft;scrollLeft=row.scrollLeft;});
    row.addEventListener('mouseleave',()=>isDown=false);
    row.addEventListener('mouseup',()=>isDown=false);
    row.addEventListener('mousemove',e=>{ if(!isDown) return; e.preventDefault(); const x=e.pageX-row.offsetLeft; const walk=(x-startX); row.scrollLeft=scrollLeft-walk;});
  })();
})();
</script>
@endsection