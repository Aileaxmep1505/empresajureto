{{-- resources/views/web/categorias/show.blade.php --}}
@extends('layouts.web')
@section('title', $category->name)
@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400..700&display=swap"/>
<style>
  /* Fondo suave */
  body.categorias-bg{ position:relative; }
  body.categorias-bg::before{
    content:""; position:fixed; inset:0; z-index:-1; pointer-events:none;
    background:
      radial-gradient(950px 560px at -10% 8%, rgba(59,130,246,.20), transparent 60%),
      radial-gradient(900px 560px at 110% -6%, rgba(99,102,241,.18), transparent 60%),
      linear-gradient(180deg, #f6fbff 0%, #ecf3ff 100%);
  }
  #cat{
    --cat-ink:#0e1726; --cat-muted:#6b7280; --cat-line:#e8eef6;
    --cat-shadow:0 14px 28px rgba(2,8,23,.08); --cat-radius:14px;
    --card-min:190px; --card-max:260px; --card-gap:12px; --media-bg:#f7f9fc;

    /* tokens favoritos */
    --fav-on-bg:#fff1f2; --fav-on-border:#fecdd3; --fav-on-ink:#be123c;
    --fav-off-bg:#ffffff; --fav-off-border:var(--cat-line); --fav-off-ink:#0b1220;
  }
  #cat .material-symbols-outlined{ font-size:18px; color:#64748b }
  #cat-hero, #cat-controls, #cat-layout{ width:100vw; margin-left:calc(50% - 50vw); margin-right:calc(50% - 50vw) }
  #cat .cat-wrap{ max-width:1200px; margin:0 auto; padding: clamp(14px,1.8vw,22px) }
  #cat .cat-wrap--topless{ padding-top:0 !important }
  #cat .cat-eyebrow{ letter-spacing:.32em; font-weight:700; color:#1f2937; opacity:.75; margin: 2px 0 14px }
  #cat .cat-display{ margin:0; font-weight:900; letter-spacing:-.02em; color:#0b1220; font-size:clamp(32px,5.2vw,58px); line-height:1.05 }
  #cat .cat-display .accent{ color:#eaff8f }
  #cat .cat-sub{ margin:10px 0 14px; color:#374151; font-size:clamp(14px,1.05vw,16px) }

  #cat-controls{ background:#ffffffcc; backdrop-filter:saturate(1.05) blur(5px); border-bottom:1px solid var(--cat-line) }
  #cat .cat-controls .cat-row{ display:flex; gap:8px; align-items:center; flex-wrap:wrap }
  #cat .cat-field{ display:flex; align-items:center; gap:6px; background:#fff; border:1px solid var(--cat-line); border-radius:10px; padding:6px 8px }
  #cat .cat-field input{ border:0; outline:0; background:transparent; font-weight:600; font-size:.92rem }
  #cat .cat-lbl{ font-weight:800; color:#0b1220; font-size:.85rem }
  #cat .cat-field select{ border:0; outline:0; background:transparent; font-weight:700; font-size:.92rem }
  #cat .cat-btn{
    display:inline-flex; align-items:center; gap:6px; font-weight:900; border-radius:999px; padding:8px 12px;
    border:1px solid var(--cat-line); background:#fff; color:#0b1220; cursor:pointer; text-decoration:none;
    transition: transform .12s ease, box-shadow .2s ease, background .2s ease, color .2s ease; font-size:.92rem
  }
  #cat .cat-btn-ghost{ background:#fff }
  #cat .cat-btn-apply{ border:2px solid #d1fae5; background:#ecfdf5; color:#065f46; box-shadow:0 8px 18px rgba(16,185,129,.16) }
  #cat .cat-btn:hover{ transform: translateY(-1px); box-shadow:0 8px 18px rgba(2,8,23,.08) }

  #cat .cat-grid-layout{ display:grid; grid-template-columns: 280px 1fr; gap: clamp(12px,1.6vw,18px) }
  @media (max-width: 1024px){ #cat .cat-grid-layout{ grid-template-columns: 1fr } #cat .cat-sidebar{ display:none } }
  #cat .cat-sidebar{
    background:#fff; border:1px solid var(--cat-line); border-radius:var(--cat-radius); padding:12px; box-shadow:var(--cat-shadow);
    position:sticky; top:14px; height:max-content;
  }

  #cat .cat-grid-area{ min-width:0 }
  #cat .cat-grid-cards{
    display:grid;
    gap: var(--card-gap);
    grid-template-columns: repeat(auto-fill, minmax(var(--card-min), var(--card-max)));
    justify-content: center;
    align-items: start;
  }

  /* ====== Card ====== */
  #cat .cat-card{
    width:min(100%, var(--card-max));
    margin:0 auto;
    position:relative; border:1px solid var(--cat-line); border-radius:var(--cat-radius); background:#fff; box-shadow:0 10px 24px rgba(2,8,23,.06);
    display:flex; flex-direction:column; overflow:hidden; transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
  }
  #cat .cat-card:hover{ transform: translateY(-3px); box-shadow:0 16px 36px rgba(2,8,23,.10); border-color:#e2e8f0 }
  #cat .cat-card-hit{ position:absolute; inset:0; z-index:0 } /* debajo de acciones */
  #cat .cat-media{
    position:relative; background:var(--media-bg); aspect-ratio:1/1; overflow:hidden; max-height: var(--card-max);
  }
  #cat .cat-media img{
    width:100%; height:100%; object-fit: contain !important; object-position:center; transition: transform .25s ease;
  }
  #cat .cat-card:hover .cat-media img{ transform: scale(1.02) }

  #cat .cat-ribbon{
    position:absolute; top:8px; left:8px; z-index:2; background:#dcfce7; color:#14532d;
    border:1px solid rgba(20,83,45,.22); padding:.28rem .5rem; font-weight:900; font-size:.75rem; border-radius:999px;
  }
  #cat .cat-body{ position:relative; z-index:1; display:flex; flex-direction:column; gap:6px; padding:10px }
  #cat .cat-title{ margin:0; color:#0e1726; font-weight:800; line-height:1.2; font-size:.95rem }
  #cat .cat-sku{ color:#64748b; font-size:.78rem }
  #cat .cat-desc{ color:#6b7280; margin:0; font-size:.86rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden }

  #cat .cat-price{ display:flex; align-items:baseline; gap:8px; margin-top:2px }
  #cat .cat-now{ color:#16a34a; font-weight:900; font-size:1rem }
  #cat .cat-old{ color:#94a3b8; text-decoration:line-through; font-weight:700; font-size:.9rem }

  /* --- Acciones: evitar conflictos y cortar overflow del botón negro --- */
  #cat .cat-cta{
    position: relative;
    z-index: 3;             /* sobre .cat-card-hit */
    isolation: isolate;     /* aísla efectos del grupo */
    display: flex;
    flex-wrap: wrap;        /* <<< clave: si no cabe, baja a segunda línea */
    gap: 6px;
    align-items: center;
    margin-top:6px;
  }
  #cat .cat-btn-sm{ padding:7px 10px; font-size:.9rem }
  #cat .cat-fav, 
  #cat .add-to-cart{ flex: 0 0 auto }
  /* cuando el ancho es pequeño, permite que cada control ocupe línea completa si lo necesita */
  @media (max-width: 420px){
    #cat .cat-cta > *{ max-width:100% }
  }

  #cat .cat-empty{ display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px;
    border:1px dashed var(--cat-line); border-radius:var(--cat-radius); padding:18px; color:#6b7280; background:#f9fafb; font-size:.95rem }
  #cat .cat-pagination{ display:flex; justify-content:center; padding:12px 0 }
  #cat .cat-pagination nav{ display:inline-block }
  #cat .cat-pagination .hidden{ display:none }

  /* ====== Botón ANIMADO (no tocado) ====== */
  #cat .add-to-cart{
    --background-default:#17171B; --background-hover:#0A0A0C; --background-scale:1;
    --text-color:#fff; --text-o:1; --text-x:10px;
    --cart:#fff; --cart-x:-44px; --cart-y:0px; --cart-rotate:0deg; --cart-scale:.72;
    --cart-clip:0px; --cart-clip-x:0px; --cart-tick-offset:10px; --cart-tick-color:#22c55e;
    --shirt-y:-14px; --shirt-scale:0; --shirt-color:#ffffff; --shirt-logo:#17171B;
    --shirt-second-y:22px; --shirt-second-color:#ffffff; --shirt-second-logo:#17171B;

    position:relative; display:inline-flex; align-items:center; justify-content:center;
    width:148px; height:42px; padding:0; margin:0; border:0; border-radius:10px;
    appearance:none; outline:0; cursor:pointer; overflow:hidden; -webkit-tap-highlight-color:transparent;
    background:none; box-sizing:border-box; vertical-align:middle;
  }
  #cat .add-to-cart:before{
    content:""; position:absolute; inset:0; border-radius:inherit; transition:background .25s;
    background:var(--background,var(--background-default)); transform:scaleX(var(--background-scale)) translateZ(0)
  }
  #cat .add-to-cart:not(.active):hover{ --background:var(--background-hover); }
  #cat .add-to-cart>span{
    position:relative; z-index:1; display:block; text-align:center; font-size:14px; font-weight:800; line-height:1;
    color:var(--text-color); opacity:var(--text-o); transform:translateX(var(--text-x)) translateZ(0);
    pointer-events:none;
  }
  #cat .add-to-cart svg{ display:block; width:auto; height:auto; }
  #cat .add-to-cart .morph{
    width:64px; height:13px; position:absolute; left:50%; top:-10px; margin-left:-32px;
    fill:var(--background,var(--background-default)); transition:fill .25s; pointer-events:none
  }
  #cat .add-to-cart .adc-shirt,
  #cat .add-to-cart .adc-cart{ pointer-events:none; position:absolute; left:50%; }
  #cat .add-to-cart .adc-shirt{ top:0; margin:-10px 0 0 -12px; transform-origin:50% 100%; transform:translateY(var(--shirt-y)) scale(var(--shirt-scale)) }
  #cat .add-to-cart .adc-shirt svg{ width:24px !important; height:24px !important }
  #cat .add-to-cart .adc-shirt svg path{ fill:var(--shirt-color) }
  #cat .add-to-cart .adc-shirt svg g path{ fill:var(--shirt-logo) }
  #cat .add-to-cart .adc-shirt svg.second{ position:absolute; top:0; left:0; clip-path:polygon(0 var(--shirt-second-y),24px var(--shirt-second-y),24px 24px,0 24px) }
  #cat .add-to-cart .adc-shirt svg.second path{ fill:var(--shirt-second-color) }
  #cat .add-to-cart .adc-shirt svg.second g path{ fill:var(--shirt-second-logo) }

  #cat .add-to-cart .adc-cart{
    width:36px; height:26px; top:8px; margin-left:-18px;
    transform:translate(var(--cart-x),var(--cart-y)) rotate(var(--cart-rotate)) scale(var(--cart-scale)) translateZ(0)
  }
  #cat .add-to-cart .adc-cart:before{
    content:""; width:22px; height:12px; position:absolute; left:7px; top:7px; background:var(--cart);
    clip-path:polygon(0 0,22px 0, calc(22px - var(--cart-clip-x)) var(--cart-clip), var(--cart-clip-x) var(--cart-clip));
  }
  #cat .add-to-cart .adc-cart .shape{ fill:none; stroke:var(--cart); stroke-width:2 }
  #cat .add-to-cart .adc-cart .wheel{ fill:none; stroke:var(--cart); stroke-width:1.5 }
  #cat .add-to-cart .adc-cart .tick{ fill:none; stroke:var(--cart-tick-color); stroke-width:2; stroke-dasharray:10px; stroke-dashoffset:var(--cart-tick-offset) }

  #cat .add-to-cart--sm{ transform:scale(.92); transform-origin:left center }

  /* ====== FAVORITOS (toggle) ====== */
  #cat .cat-fav{
    border:1px solid var(--fav-off-border);
    background:var(--fav-off-bg);
    color:var(--fav-off-ink);
    display:inline-flex; align-items:center; gap:6px;
    padding:7px 10px; border-radius:999px; font-weight:900; font-size:.9rem;
    cursor:pointer; user-select:none;
    transition:transform .12s ease, box-shadow .2s ease, background .2s ease, border-color .2s ease, color .2s ease;
  }
  #cat .cat-fav:hover{ transform:translateY(-1px); box-shadow:0 8px 18px rgba(2,8,23,.08) }
  #cat .cat-fav .ic{ font-size:18px; line-height:1; font-variation-settings: 'FILL' 0, 'wght' 600, 'opsz' 24; }
  #cat .cat-fav.is-on{
    border-color:var(--fav-on-border);
    background:var(--fav-on-bg);
    color:var(--fav-on-ink);
  }
  #cat .cat-fav.is-on .ic{ font-variation-settings: 'FILL' 1, 'wght' 700, 'opsz' 24; }
</style>

@php
  $pct = fn($p) => ($p->price > 0 && $p->sale_price !== null && $p->sale_price < $p->price)
      ? max(1, round(100 - (($p->sale_price / $p->price) * 100))) : null;

  /** @var array<int> $favIds */
  $favIds = isset($favIds) && is_array($favIds) ? $favIds : [];
@endphp

<div id="cat"
     data-fav-url-template="{{ route('favoritos.toggle', ['item' => '__ID__']) }}"
     data-auth="{{ auth()->check() ? '1' : '0' }}"
     data-login-url="{{ route('login') }}"
     style="font-family:'Outfit', system-ui, -apple-system, Segoe UI, Roboto, 'Helvetica Neue', Arial, 'Noto Sans', 'Apple Color Emoji','Segoe UI Emoji';">

  {{-- HERO --}}
  <section id="cat-hero" class="cat-hero">
    <div class="cat-wrap cat-wrap--topless">
      <p class="cat-eyebrow">{{ strtoupper($category->name) }}</p>
      <h1 class="cat-display">Explora <span class="accent">{{ $category->name }}</span></h1>
      <p class="cat-sub">Productos de {{ strtolower($category->name) }} con estilo enterprise.</p>
    </div>
  </section>

  {{-- CONTROLES --}}
  <section id="cat-controls">
    <form class="cat-controls cat-wrap" method="GET" action="{{ route('web.categorias.show', $category->slug) }}">
      <div class="cat-row">
        <div class="cat-field">
          <span class="material-symbols-outlined">search</span>
          <input type="text" name="q" value="{{ $q }}" placeholder="Buscar en {{ strtolower($category->name) }}">
        </div>
        <div class="cat-field"><label class="cat-lbl">Min</label><input type="number" step="0.01" min="0" name="min" value="{{ $min }}"></div>
        <div class="cat-field"><label class="cat-lbl">Max</label><input type="number" step="0.01" min="0" name="max" value="{{ $max }}"></div>
        <div class="cat-field">
          <label class="cat-lbl">Ordenar</label>
          <select name="orden">
            <option value="relevancia"  @selected($orderBy==='relevancia')>Más relevantes</option>
            <option value="nuevo"       @selected($orderBy==='nuevo')>Más nuevos</option>
            <option value="precio_asc"  @selected($orderBy==='precio_asc')>Precio: menor a mayor</option>
            <option value="precio_desc" @selected($orderBy==='precio_desc')>Precio: mayor a menor</option>
          </select>
        </div>
        <button class="cat-btn cat-btn-apply" type="submit">
          <span class="material-symbols-outlined">tune</span> Aplicar
        </button>
        @if(request()->query())
          <a class="cat-btn cat-btn-ghost" href="{{ route('web.categorias.show', $category->slug) }}">Limpiar</a>
        @endif
      </div>
    </form>
  </section>

  {{-- LAYOUT --}}
  <section id="cat-layout">
    <div class="cat-wrap cat-grid-layout">
      <aside class="cat-sidebar">@include('web.partials.menu-categorias', ['primary' => $primary])</aside>

      <main class="cat-grid-area">
        @if ($items->count() === 0)
          <div class="cat-empty">
            <span class="material-symbols-outlined">inventory_2</span>
            <p>No hay productos que coincidan con tu búsqueda.</p>
            <a class="cat-btn cat-btn-ghost" href="{{ route('web.categorias.show', $category->slug) }}">Ver todos</a>
          </div>
        @else
          <div class="cat-grid-cards">
            @foreach ($items as $it)
              @php
                $img  = $it->image_url ?: 'https://placehold.co/600x600?text='.urlencode($it->name);
                $off  = $pct($it);
                $desc = \Illuminate\Support\Str::limit(strip_tags($it->description ?? ''), 110);
                $url  = url('/producto/'.$it->slug);
                $isFav = in_array($it->id, $favIds, true);
              @endphp

              <article class="cat-card cat-ao">
                <a class="cat-card-hit" href="{{ $url }}" aria-label="Ver {{ $it->name }}"></a>

                <div class="cat-media">
                  @if($off)<span class="cat-ribbon">-{{ $off }}%</span>@endif
                  <img src="{{ $img }}" alt="{{ $it->name }}" loading="lazy"
                       onerror="this.onerror=null;this.src='https://placehold.co/600x600?text={{ urlencode($it->name) }}'">
                </div>

                <div class="cat-body">
                  <h3 class="cat-title">{{ $it->name }}</h3>
                  @if(!empty($it->sku))<div class="cat-sku">SKU: {{ $it->sku }}</div>@endif
                  @if($desc)<p class="cat-desc">{{ $desc }}</p>@endif
                  <div class="cat-price">
                    <span class="cat-now">${{ number_format((float)($it->sale_price ?? $it->price), 2) }}</span>
                    @if($it->sale_price !== null && $it->price > 0 && $it->sale_price < $it->price)
                      <span class="cat-old">${{ number_format((float)$it->price, 2) }}</span>
                    @endif
                  </div>

                  <div class="cat-cta" role="group" aria-label="Acciones" onclick="event.stopPropagation()">
                    {{-- Favoritos --}}
                    <button
                      type="button"
                      class="cat-fav {{ $isFav ? 'is-on' : '' }}"
                      data-id="{{ $it->id }}"
                      data-faved="{{ $isFav ? '1' : '0' }}"
                      aria-pressed="{{ $isFav ? 'true' : 'false' }}"
                      title="{{ $isFav ? 'Quitar de favoritos' : 'Agregar a favoritos' }}"
                    >
                      <span class="material-symbols-outlined ic">favorite</span>
                      <span class="txt">Favoritos</span>
                    </button>

                    {{-- Agregar --}}
                    <form class="cat-cart" action="{{ route('web.cart.add') }}" method="POST" style="display:inline-flex;gap:6px;align-items:center">
                      @csrf
                      <input type="hidden" name="catalog_item_id" value="{{ $it->id }}">
                      <input type="hidden" name="qty" value="1">
                      <button type="button" class="add-to-cart add-to-cart--sm" title="Agregar al carrito">
                        <span>Agregar</span>
                        <svg class="morph" viewBox="0 0 64 13" aria-hidden="true">
                          <path d="M0 12C6 12 17 12 32 12C47.9024 12 58 12 64 12V13H0V12Z" />
                        </svg>
                        <div class="adc-shirt" aria-hidden="true">
                          <svg class="first" viewBox="0 0 24 24"><path d="M5 3L9 1.5C9 1.5 10.69 3 12 3C13.31 3 15 1.5 15 1.5L19 3L22.5 8L19.5 10.5L19 9.5L17.18 18.61C17.06 19.19 16.78 19.72 16.34 20.12C15.43 20.92 13.71 22.31 12 23C10.29 22.31 8.57 20.92 7.66 20.12C7.22 19.72 6.94 19.19 6.82 18.61L5 9.5L4.5 10.5L1.5 8L5 3Z"/></svg>
                          <svg class="second" viewBox="0 0 24 24"><path d="M5 3L9 1.5C9 1.5 10.69 3 12 3C13.31 3 15 1.5 15 1.5L19 3L22.5 8L19.5 10.5L19 9.5L17.18 18.61C17.06 19.19 16.78 19.72 16.34 20.12C15.43 20.92 13.71 22.31 12 23C10.29 22.31 8.57 20.92 7.66 20.12C7.22 19.72 6.94 19.19 6.82 18.61L5 9.5L4.5 10.5L1.5 8L5 3Z"/></svg>
                        </div>
                        <div class="adc-cart" aria-hidden="true">
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
          <div class="cat-pagination">
            {{ $items->onEachSide(1)->links() }}
          </div>
        @endif
      </main>
    </div>
  </section>
</div>

{{-- Estilos y scripts específicos --}}
<script defer src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
<script>
(function(){
  const root = document.getElementById('cat');
  if(!root) return;

  // fondo solo en esta página
  const setBg = on => document.body.classList.toggle('categorias-bg', !!on);
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ()=>setBg(true)); else setBg(true);
  window.addEventListener('pageshow', ()=>setBg(true));
  window.addEventListener('pagehide', ()=>setBg(false));

  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const updateBadge = (c)=>{ if(window.updateCartBadge) return window.updateCartBadge(c); const el=document.querySelector('[data-cart-badge]'); if(el) el.textContent=String(c||0); };

  // -------- Add to cart (frenos para no chocar con otros clicks) --------
  function bindAnimated(){
    root.querySelectorAll('#cat .add-to-cart').forEach(btn=>{
      if(btn.dataset.bound) return; btn.dataset.bound='1';
      btn.addEventListener('click', (e)=>{
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        if(btn.dataset.busy==='1') return; btn.dataset.busy='1';
        const form = btn.closest('form'); if(!form){ btn.dataset.busy=''; return; }

        if(window.gsap){
          btn.classList.add('active'); btn.style.pointerEvents='none'; btn.style.setProperty('--text-o', 0);
          gsap.to(btn,{keyframes:[{'--background-scale':.97,duration:.10},{'--background-scale':1,duration:.9,ease:'elastic.out(1,.6)'}]});
          gsap.to(btn,{keyframes:[{'--shirt-scale':1,'--shirt-y':'-38px','--cart-x':'0px','--cart-scale':1,duration:.32,ease:'power1.in'},{'--shirt-y':'14px','--shirt-scale':.9,duration:.22},{'--shirt-scale':0,duration:.22}]});
          gsap.to(btn,{'--shirt-second-y':'0px',delay:.64,duration:.1});
          gsap.to(btn,{keyframes:[{'--cart-clip':'12px','--cart-clip-x':'3px',delay:.72,duration:.06},{'--cart-y':'2px',duration:.10},{'--cart-tick-offset':'0px','--cart-y':'0px',duration:.16},{'--cart-x':'48px','--cart-rotate':'-15deg',duration:.14},{'--cart-x':'96px','--cart-rotate':'0deg',duration:.14,onComplete(){btn.style.setProperty('--cart-x','-96px');}},{'--text-o':1,'--text-x':'10px','--cart-x':'-44px','--cart-scale':.72,duration:.20}] ,onComplete(){ btn.classList.remove('active'); btn.style.pointerEvents=''; btn.dataset.busy=''; }});
        }

        (async ()=>{
          try{
            const res = await fetch(form.action,{
              method:'POST',
              headers:{'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':csrf(),'Accept':'application/json'},
              body:new FormData(form)
            });
            const data = await res.json().catch(()=>({}));
            if(res.ok && data.ok!==false){
              updateBadge(data?.totals?.count ?? null);
              if(window.showToast){ window.showToast({title:'Agregado',message:'Producto añadido.',kind:'success',duration:2500}); }
            }
          }finally{ btn.dataset.busy=''; }
        })();
      });
    });
  }

  // -------- Favoritos (toggle) --------
  function favUrl(id){
    const tpl = root.dataset.favUrlTemplate || '';
    return tpl.replace('__ID__', String(id));
  }
  async function toggleFav(btn){
    if(btn.dataset.busy==='1') return;
    const isAuth = root.dataset.auth === '1';
    if(!isAuth){
      if(window.showToast) window.showToast({title:'Inicia sesión', message:'Necesitas iniciar sesión para usar Favoritos.', kind:'info'});
      try{ window.location.assign(root.dataset.loginUrl || '/login'); }catch(_) {}
      return;
    }
    btn.dataset.busy='1';
    const willOn = btn.getAttribute('data-faved') !== '1';
    try{
      const res = await fetch(favUrl(btn.dataset.id),{
        method:'POST',
        headers:{'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':csrf(),'Accept':'application/json','Content-Type':'application/json'},
        body: JSON.stringify({ desired: willOn ? 'on' : 'off' })
      });
      const data = await res.json().catch(()=>({}));
      if(!res.ok) throw new Error((data && (data.message||data.error)) || 'Error');

      const on = (typeof data.on === 'boolean') ? data.on : willOn;
      btn.classList.toggle('is-on', on);
      btn.dataset.faved = on ? '1' : '0';
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
      btn.title = on ? 'Quitar de favoritos' : 'Agregar a favoritos';

      if(window.updateFavBadge && typeof data.count === 'number') window.updateFavBadge(data.count);
      if(window.showToast) window.showToast({title: on ? 'Añadido a favoritos' : 'Quitado de favoritos', kind: on?'success':'neutral', duration:2200});
    }catch(err){
      if(window.showToast) window.showToast({title:'Ups', message:String(err?.message||'No se pudo actualizar'), kind:'error'});
    }finally{ btn.dataset.busy=''; }
  }
  function bindFavs(){
    root.querySelectorAll('.cat-fav').forEach(btn=>{
      if(btn.dataset.bound) return; btn.dataset.bound='1';
      btn.addEventListener('click', (e)=>{
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        toggleFav(btn);
      });
    });
  }

  function init(){ bindAnimated(); bindFavs(); }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', init); else init();
  document.addEventListener('turbo:load', init);
  document.addEventListener('livewire:load', init);
})();
</script>
@endsection
