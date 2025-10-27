@extends('layouts.web')
@section('title', $item->name)

@section('content')
<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700,800&display=swap" rel="stylesheet">

<style>
/* ================= TOKENS / BASE (sin cambios visuales fuera de botones) ================= */
#product{
  --ink:#0e1726; --muted:#6b7280; --line:#e8eef6;
  --brand:#a3d5ff; --brand-ink:#0b1220; --ok:#16a34a;
  --chip:#f7fbff;
  font-family:"Open Sans",sans-serif; color:var(--ink);
}
#product a{ text-decoration:none }          /* sin subrayado en esta vista */
#product .bg-grad{
  position:fixed; inset:0; z-index:-1; pointer-events:none;
  background:
    radial-gradient(1100px 650px at 50% -220px, rgba(255,255,255,.65), transparent 60%),
    linear-gradient(180deg,#f3faea 0%, #eef5ff 48%, #ecebff 100%);
  filter:saturate(1.02);
}
#product .container{ width:100%; padding-inline:clamp(16px,3vw,28px); margin-inline:auto; }

/* ================= HERO (sin cambios de layout) ================= */
#hero{ padding-top:clamp(20px,2.5vw,28px); padding-bottom:clamp(28px,4vw,40px); }
#hero .grid{ display:grid; grid-template-columns: 1fr 1fr; gap:clamp(16px,2.4vw,28px); }
@media (max-width: 980px){ #hero .grid{ grid-template-columns:1fr; } }

#hero .media{ border-radius:20px; overflow:hidden; }
#hero .gal-main{ width:100%; aspect-ratio:4/3; object-fit:cover; background:#f6f8fc; }
#hero .thumbs{ display:flex; gap:10px; flex-wrap:wrap; padding:12px 0; }
#hero .thumb{ width:88px; height:88px; border-radius:12px; border:1px solid var(--line);
  object-fit:cover; cursor:pointer; opacity:.9; transition:transform .15s,opacity .15s }
#hero .thumb:hover{ transform:translateY(-2px); opacity:1 }
#hero .thumb.is-active{ outline:2px solid var(--brand); opacity:1 }

#hero .info h1{ margin:0 0 8px; font-weight:800; letter-spacing:-.2px; font-size:clamp(22px,3.5vw,42px) }
#hero .muted{ color:var(--muted) }

#hero .kv{ display:flex; flex-wrap:wrap; gap:10px; margin:10px 0 14px }
#hero .kv .chip{
  display:inline-flex; align-items:center; gap:8px; padding:10px 12px; border-radius:999px;
  background:var(--chip); border:1px solid var(--line); font-weight:800; font-size:.86rem;
}
#hero .kv svg{ width:18px; height:18px }

#hero .price{ font-weight:900; color:var(--ink); font-size:clamp(22px,3vw,30px) }
#hero .sale{ color:var(--ok); font-weight:900; font-size:clamp(22px,3vw,30px) }
#hero .old{ color:var(--muted); text-decoration:line-through; margin-left:8px }
#hero .save{ color:#166534; background:#ecfdf5; border:1px solid #bbf7d0; font-weight:800; border-radius:999px; padding:4px 10px; font-size:.82rem }

#hero .qty{ width:120px; border:1px solid var(--line); border-radius:12px; padding:10px 12px; min-height:44px }

#hero .btn{ display:inline-flex; align-items:center; gap:8px; border:1px solid transparent;
  border-radius:12px; padding:12px 16px; font-weight:800; background:var(--brand); color:var(--brand-ink);
  text-decoration:none; cursor:pointer; min-height:44px; transition:.18s ease }
#hero .btn:hover{ background:#fff; color:#000; border-color:var(--line); box-shadow:0 10px 28px rgba(2,8,23,.10) }
#hero .btn-ghost{ background:#fff; border:1px solid var(--line); color:var(--ink) }

#hero .badges{ display:flex; flex-wrap:wrap; gap:8px; margin:4px 0 6px }
#hero .badge{ font-weight:800; font-size:.78rem; padding:6px 10px; border:1px solid var(--line); background:#f8fafc }
#hero .badge--stock{ background:#ecfdf5; border-color:#bbf7d0; color:#166534 }

/* ================= SIMILARES (sin cambios de layout) ================= */
#sim{
  background:#0b1f33; color:#eaf2ff; padding:clamp(24px,4vw,42px) 0;
  position:relative; left:50%; right:50%; margin-left:-50vw; margin-right:-50vw; width:100vw;
}
#sim .container{ position:relative; }
#sim .sim-head{ display:flex; align-items:flex-end; justify-content:space-between; gap:10px; margin-bottom:12px }
#sim .sim-title{ margin:0; font-weight:800; color:#eaf2ff; font-size:clamp(18px,3vw,24px) }
#sim .more{ color:#fff; font-weight:800; }     /* sin subrayado */

#sim .row{ display:flex; gap:14px; overflow-x:auto; scroll-snap-type:x mandatory; padding-bottom:12px }
#sim .row::-webkit-scrollbar{ height:8px } 
#sim .row::-webkit-scrollbar-thumb{ background:#204463; border-radius:999px }

#sim .card{ min-width:240px; max-width:260px; scroll-snap-align:start; background:#fff; border:1px solid #e6edf6;
  border-radius:14px; overflow:hidden; box-shadow:0 12px 30px rgba(0,0,0,.08); display:flex; flex-direction:column }
#sim .sim-img{ width:100%; aspect-ratio:4/3; object-fit:cover; background:#f6f8fc }
#sim .sim-body{ padding:12px; display:flex; flex-direction:column; gap:8px; color:#0f172a }
#sim .sim-name{ font-weight:800; line-height:1.2; margin:0 }
#sim .sim-price{ font-weight:900 }
#sim .sim-old{ color:#6b7280; text-decoration:line-through; margin-left:6px }
#sim .sim-actions{ display:flex; gap:8px; align-items:center; margin-top:auto; flex-wrap:wrap }

/* ================== BOTÓN ANIMADO (SOLO BOTONES) ================== */
/* Aspecto oscuro como tu captura */
.add-to-cart{
  --background-default:#17171B; --background-hover:#0A0A0C; --background-scale:1;
  --text-color:#fff; --text-o:1; --text-x:12px;
  --cart:#fff; --cart-x:-48px; --cart-y:0px; --cart-rotate:0deg; --cart-scale:.75;
  --cart-clip:0px; --cart-clip-x:0px; --cart-tick-offset:10px; --cart-tick-color:#FF328B;
  --shirt-y:-16px; --shirt-scale:0; --shirt-color:#17171B; --shirt-logo:#fff;
  --shirt-second-y:24px; --shirt-second-color:#fff; --shirt-second-logo:#17171B;
  -webkit-tap-highlight-color:transparent; appearance:none; outline:0; background:none; border:0;
  padding:12px 0; width:164px; margin:0; cursor:pointer; position:relative; font:inherit; border-radius:12px;
}
.add-to-cart:before{
  content:""; position:absolute; inset:0; border-radius:12px; transition:background .25s;
  background:var(--background,var(--background-default)); transform:scaleX(var(--background-scale)) translateZ(0);
}
.add-to-cart:not(.active):hover{ --background:var(--background-hover); }
.add-to-cart>span{
  position:relative; z-index:1; display:block; text-align:center;
  font-size:14px; font-weight:800; line-height:24px; color:var(--text-color);
  opacity:var(--text-o); transform:translateX(var(--text-x)) translateZ(0);
}
.add-to-cart svg{ display:block; stroke-linecap:round; stroke-linejoin:round; }
.add-to-cart .morph{ width:64px; height:13px; position:absolute; left:50%; top:-12px; margin-left:-32px;
  fill:var(--background,var(--background-default)); transition:fill .25s; pointer-events:none; }
.add-to-cart .shirt, .add-to-cart .cart{ pointer-events:none; position:absolute; left:50%; }
.add-to-cart .shirt{ top:0; margin:-12px 0 0 -12px; transform-origin:50% 100%;
  transform:translateY(var(--shirt-y)) scale(var(--shirt-scale)); }
.add-to-cart .shirt svg{ width:24px; height:24px; }
.add-to-cart .shirt svg path{ fill:var(--shirt-color); }
.add-to-cart .shirt svg g path{ fill:var(--shirt-logo); }
.add-to-cart .shirt svg.second{ position:absolute; top:0; left:0;
  clip-path:polygon(0 var(--shirt-second-y),24px var(--shirt-second-y),24px 24px,0 24px); }
.add-to-cart .shirt svg.second path{ fill:var(--shirt-second-color); }
.add-to-cart .shirt svg.second g path{ fill:var(--shirt-second-logo); }
.add-to-cart .cart{ width:36px; height:26px; top:10px; margin-left:-18px;
  transform:translate(var(--cart-x),var(--cart-y)) rotate(var(--cart-rotate)) scale(var(--cart-scale)) translateZ(0); }
.add-to-cart .cart:before{
  content:""; width:22px; height:12px; position:absolute; left:7px; top:7px; background:var(--cart);
  clip-path:polygon(0 0,22px 0, calc(22px - var(--cart-clip-x)) var(--cart-clip), var(--cart-clip-x) var(--cart-clip)); }
.add-to-cart .cart .shape{ fill:none; stroke:var(--cart); stroke-width:2; }
.add-to-cart .cart .wheel{ fill:none; stroke:var(--cart); stroke-width:1.5; }
.add-to-cart .cart .tick{ fill:none; stroke:var(--cart-tick-color); stroke-width:2; stroke-dasharray:10px; stroke-dashoffset:var(--cart-tick-offset); }

/* Opcional: versión un poco más compacta para cards (mismo estilo) */
.add-to-cart--sm{ transform:scale(.92); transform-origin:left center; }

/* Flechas navegación (ya lo tenías) */
#sim .sim-nav{ position:absolute; inset:0; pointer-events:none; }
#sim .sim-nav button{
  pointer-events:auto; position:absolute; top:50%; transform:translateY(-50%);
  border:0; width:44px; height:44px; border-radius:999px; cursor:pointer;
  background:#fff; box-shadow:0 16px 30px rgba(2,8,23,.35); display:flex; align-items:center; justify-content:center;
}
#sim .sim-nav .prev{ left: clamp(6px,2vw,16px); }
#sim .sim-nav .next{ right: clamp(6px,2vw,16px); }
#sim .sim-nav button svg{ width:22px; height:22px; color:#0b1f33 }
#sim .sim-nav button:hover{ transform:translateY(-50%) scale(1.04) }

@media (max-width: 980px){
  #sim .sim-nav{ display:none; }
}
</style>

@php
  $price   = (float)($item->price ?? 0);
  $sale    = !is_null($item->sale_price) ? (float)$item->sale_price : null;
  $final   = $sale ?? $price;
  $savePct = ($sale && $price>0) ? max(1, round(100 - (($sale/$price)*100))) : null;
  $monthly = $final > 0 ? round($final/12, 2) : 0;

  $images = [];
  if(is_array($item->images ?? null) && count($item->images)) $images = $item->images;
  if($item->image_url) array_unshift($images, $item->image_url);
  $images = array_values(array_unique(array_filter($images)));

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
@endphp

<div id="product">
  <div class="bg-grad" aria-hidden="true"></div>

  {{-- ================= HERO ================= --}}
  <section id="hero">
    <div class="container">
      <div style="display:flex;align-items:center;gap:10px;margin:0 0 12px;">
        <a class="btn-ghost btn" href="{{ route('web.catalog.index') }}">← Volver al catálogo</a>
      </div>

      <div class="grid">
        {{-- Galería --}}
        <div class="media">
          <img id="galMain" class="gal-main"
               src="{{ $images[0] ?? asset('images/placeholder.png') }}"
               alt="{{ $item->name }}"
               onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
          @if(count($images) > 1)
            <div class="thumbs">
              @foreach($images as $i => $u)
                <img class="thumb {{ $i===0 ? 'is-active' : '' }}"
                     src="{{ $u }}" alt="Vista {{ $i+1 }}"
                     onerror="this.style.display='none'">
              @endforeach
            </div>
          @endif
        </div>

        {{-- Info --}}
        <div class="info">
          <h1>{{ $item->name }}</h1>
          <div class="muted">SKU: {{ $item->sku ?: '—' }} &middot; Marca: {{ $item->brand ?? '—' }}</div>

          <div class="kv" aria-label="Fortalezas">
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
              <span class="badge badge--stock">En stock ({{ $item->stock }})</span>
            @else
              <span class="badge" style="background:#fff7ed;border-color:#fed7aa;color:#9a3412">Sobre pedido</span>
            @endif
            <span class="badge">Soporte técnico</span>
            <span class="badge">Pagos con meses</span>
          </div>

          <div style="margin:12px 0 14px; display:flex; align-items:baseline; gap:8px; flex-wrap:wrap;">
            @if($sale)
              <div class="sale">${{ number_format($sale,2) }}</div>
              <div class="old">${{ number_format($price,2) }}</div>
              @if($savePct)<span class="save">Ahorra {{ $savePct }}%</span>@endif
            @else
              <div class="price">${{ number_format($price,2) }}</div>
            @endif
          </div>

          <div class="muted" style="margin-bottom:10px;">
            Paga a 12 meses desde <strong>${{ number_format($monthly,2) }}</strong>*
          </div>

          @if($item->excerpt)<p class="muted" style="margin:0 0 8px">{{ $item->excerpt }}</p>@endif
          @if($item->description)<div style="margin-top:6px;">{!! nl2br(e($item->description)) !!}</div>@endif

          <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;align-items:center">
            <form action="{{ route('web.cart.add') }}" method="POST" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
              @csrf
              <input type="hidden" name="catalog_item_id" value="{{ $item->id }}">
              <label class="muted" for="qty">Cantidad</label>
              <input id="qty" class="qty" type="number" name="qty" min="1" value="1">

              {{-- BOTÓN OSCURO ANIMADO (hero) --}}
              <button type="submit" class="add-to-cart" data-submit-delay="950">
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

            {{-- Favoritos principal (sin cambios) --}}
            @includeIf('web.favoritos.button', ['item'=>$item])

            <a class="btn-ghost btn" href="{{ route('web.contacto') }}">Cotizar / Asesoría</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ================= ESPECIFICACIONES ================= --}}
  @php $specs = is_array($item->specs ?? null) ? $item->specs : []; @endphp
  @if(count($specs))
    <section>
      <div class="container">
        <h2 style="margin:8px 0 10px;font-weight:800;">Especificaciones</h2>
        <table style="width:100%;border-collapse:collapse;background:transparent">
          <tbody>
          @foreach($specs as $k => $v)
            <tr style="border-bottom:1px solid var(--line)">
              <th style="width:38%;padding:10px 12px;color:#334155;font-weight:700;text-align:left">{{ $k }}</th>
              <td style="padding:10px 12px">{{ is_array($v) ? implode(', ', $v) : $v }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
        <p class="muted" style="margin-top:10px;font-size:.9rem">*Tiempos sujetos a cobertura y horario de corte.</p>
      </div>
    </section>
  @endif

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
            @php $off = $discountPct($p); @endphp
            <article class="card">
              <a href="{{ route('web.catalog.show', $p) }}" aria-label="Ver {{ $p->name }}">
                <img class="sim-img"
                     src="{{ $p->image_url ?: asset('images/placeholder.png') }}"
                     alt="{{ $p->name }}"
                     loading="lazy"
                     onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
              </a>
              <div class="sim-body">
                <h3 class="sim-name"><a href="{{ route('web.catalog.show', $p) }}" class="sim-link">{{ $p->name }}</a></h3>
                <div>
                  @if(!is_null($p->sale_price))
                    <span class="sim-price" style="color:var(--ok)">${{ number_format($p->sale_price,2) }}</span>
                    <span class="sim-old">${{ number_format($p->price,2) }}</span>
                    @if($off)<span class="badge" style="margin-left:6px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412">-{{ $off }}%</span>@endif
                  @else
                    <span class="sim-price">${{ number_format($p->price,2) }}</span>
                  @endif
                </div>

                <div class="sim-actions">
                  {{-- Favoritos compacto --}}
                  @includeIf('web.favoritos.button', ['item'=>$p])

                  <form action="{{ route('web.cart.add') }}" method="POST" style="display:inline-flex;gap:6px;align-items:center">
                    @csrf
                    <input type="hidden" name="catalog_item_id" value="{{ $p->id }}">

                    {{-- MISMO BOTÓN OSCURO (cards) --}}
                    <button type="submit" class="add-to-cart add-to-cart--sm" data-submit-delay="900">
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

        {{-- Flechas (funcionan en desktop) --}}
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

{{-- ================= JS ================= --}}
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
<script>
  // Thumbs
  document.querySelectorAll('.thumb').forEach((t)=>{
    t.addEventListener('click', ()=>{
      const main = document.getElementById('galMain');
      document.querySelectorAll('.thumb').forEach(x=>x.classList.remove('is-active'));
      t.classList.add('is-active'); main.src = t.src;
    });
  });

  // Cantidad mínima 1
  const qty = document.getElementById('qty');
  if(qty){ qty.addEventListener('input', ()=>{ if(!qty.value || qty.value < 1) qty.value = 1; }); }

  // Scroll similares + flechas
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

  // ====== Animación botón + envío del form ======
  document.querySelectorAll('.add-to-cart').forEach(btn => {
    const submitDelay = parseInt(btn.dataset.submitDelay || '900', 10);

    btn.addEventListener('pointerdown', () => {
      if (btn.classList.contains('active')) return;
      gsap.to(btn, { '--background-scale': .97, duration: .12 });
    });

    btn.addEventListener('click', (e) => {
      const form = btn.closest('form');
      e.preventDefault();
      if (btn.classList.contains('active')) return;
      btn.classList.add('active');
      btn.style.pointerEvents = 'none';

      // Oculta el texto inmediatamente (resuelve “no se quita el texto”)
      btn.style.setProperty('--text-o', 0);

      // Fondo rebota
      gsap.to(btn, {
        keyframes: [
          { '--background-scale': .97, duration: .10 },
          { '--background-scale': 1, duration: 1.0, ease: 'elastic.out(1,.6)' }
        ]
      });

      // Playera sube y cae
      gsap.to(btn, {
        keyframes: [
          { '--shirt-scale': 1, '--shirt-y': '-42px', '--cart-x': '0px', '--cart-scale': 1, duration: .35, ease: 'power1.in' },
          { '--shirt-y': '16px', '--shirt-scale': .9, duration: .25 },
          { '--shirt-scale': 0, duration: .25 }
        ]
      });
      gsap.to(btn, { '--shirt-second-y': '0px', delay: .7, duration: .1 });

      // Carrito tick + salida y reset
      gsap.to(btn, {
        keyframes: [
          { '--cart-clip': '12px', '--cart-clip-x': '3px', delay: .78, duration: .06 },
          { '--cart-y': '2px', duration: .10 },
          { '--cart-tick-offset': '0px', '--cart-y': '0px', duration: .18 },
          { '--cart-x': '52px', '--cart-rotate': '-15deg', duration: .16 },
          { '--cart-x': '104px', '--cart-rotate': '0deg', duration: .16,
            onComplete() {
              btn.style.setProperty('--cart-x', '-104px');  // prepara reset
            }
          },
          { '--text-o': 1, '--text-x': '12px', '--cart-x': '-48px', '--cart-scale': .75, duration: .22,
            onComplete() {
              btn.classList.remove('active');
              btn.style.pointerEvents = '';
            }
          }
        ]
      });

      // Enviar el form tras la animación
      // DESPUÉS (dispara el submit "real" para que lo intercepte tu AJAX global)
if (form) setTimeout(() => {
  if (typeof form.requestSubmit === 'function') {
    form.requestSubmit(); // dispara el evento submit (tu listener global lo captura y hace fetch)
  } else {
    // fallback para navegadores viejos
    form.dispatchEvent(new Event('submit', { cancelable:true, bubbles:true }));
  }
}, submitDelay);

    });
  });
</script>
@endsection
