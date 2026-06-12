{{-- resources/views/web/home.blade.php --}}
@extends('layouts.web') 
@section('title','Inicio')

@section('content')

  {{-- ====== Fuentes + GSAP para el slider 3D ====== --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300..700" />
  <link rel="stylesheet" href="{{ asset('css/nelo.css') }}?v={{ time() }}">

  {{-- ====== SLIDER INFINITO DE MARCAS ====== --}}
  @php
    $brands = [
      asset('images/brands/aink.jpg'),
      asset('images/brands/azor.jpg'),
      asset('images/brands/barrilito.png'),
      asset('images/brands/kronaline.png'),
      asset('images/brands/kyma.png'),
      asset('images/brands/mae.png'),
      asset('images/brands/pascua.png'),
      asset('images/brands/scribe.png'),
    ];
  @endphp

  <section class="full-bleed" aria-label="Nuestras marcas">
    <div class="brands-marquee" role="region" aria-roledescription="carrusel" aria-label="Marcas" tabindex="0">
      <div class="brands-track">
        @foreach($brands as $logo)
          <div class="brand-item">
            <img src="{{ $logo }}" alt="Logotipo de marca" loading="lazy">
          </div>
        @endforeach
        @foreach($brands as $logo)
          <div class="brand-item" aria-hidden="true">
            <img src="{{ $logo }}" alt="" loading="lazy">
          </div>
        @endforeach
      </div>
    </div>
  </section>

  <link rel="stylesheet" href="https://unpkg.com/swiper@9/swiper-bundle.min.css">
  <script src="https://unpkg.com/swiper@9/swiper-bundle.min.js"></script>

  <div id="morning-steps">
    <div class="wrapper">
      <section class="section">
        <h2 class="section-title reveal video-text"
            data-text="Papelería a Mayoreo y Menudeo"
            data-video="https://cdn.magicui.design/ocean-small.webm">
          <span class="sr-only">Papelería a Mayoreo y Menudeo</span>
        </h2>

        <script>
          (function () {
            const els = document.querySelectorAll(".video-text");

            function setMask(el){
              const text = el.dataset.text || el.textContent.trim();
              const fontFamily = getComputedStyle(el).fontFamily || "sans-serif";
              const fontWeight = getComputedStyle(el).fontWeight || "800";
              const cssFont = getComputedStyle(el).getPropertyValue("--vt-font").trim();
              const fontSize = cssFont || el.dataset.fontsize || "64";

              const svg = `<svg xmlns='http://www.w3.org/2000/svg' width='100%' height='100%'>
                <text x='50%' y='50%'
                  font-size='${fontSize}'
                  font-weight='${fontWeight}'
                  text-anchor='middle'
                  dominant-baseline='middle'
                  font-family='${fontFamily}'>${text}</text>
              </svg>`;

              el.style.setProperty("--mask", `url("data:image/svg+xml,${encodeURIComponent(svg)}")`);
            }

            els.forEach(el => {
              setMask(el);

              if (!el.querySelector("video")) {
                const v = document.createElement("video");
                v.className = "video-text__video";
                v.autoplay = true;
                v.muted = true;
                v.loop = true;
                v.playsInline = true;

                const s = document.createElement("source");
                s.src = el.dataset.video || "";
                v.appendChild(s);

                el.appendChild(v);
              }
            });

            window.addEventListener("resize", () => els.forEach(setMask));
          })();
        </script>

        <p class="section-desc reveal delay-1">
          Factura al instante, envíos rápidos y seguros, y surtido inteligente. Todo en un solo lugar.
        </p>

        <div class="swiper steps reveal delay-2">
          <div class="swiper-wrapper">
            <div class="swiper-slide">
              <article class="card-wrapper">
                <div class="card-circle">1</div>
                <div class="card reveal">
                  <h3 class="card-title">Factura al instante (CFDI 4.0)</h3>
                  <p class="card-desc">Genera tu CFDI al momento. Datos guardados y complemento de pago.</p>
                  <figure class="card-figure">
                    <video class="card-video"
                           preload="auto" muted playsinline webkit-playsinline
                           autoplay loop
                           poster="https://images.unsplash.com/photo-1519074069444-1ba4fff66d16?q=80&w=1200&auto=format&fit=crop">
                      <source src="/videos/factura.mp4" type="video/mp4">
                    </video>
                  </figure>
                </div>
              </article>
            </div>

            <div class="swiper-slide">
              <article class="card-wrapper">
                <div class="card-circle">2</div>
                <div class="card reveal">
                  <h3 class="card-title">Envíos rápidos y seguros</h3>
                  <p class="card-desc">Cobertura nacional, guía de rastreo y opciones express.</p>
                  <figure class="card-figure">
                    <video class="card-video"
                           preload="auto" muted playsinline webkit-playsinline
                           autoplay loop
                           poster="https://images.unsplash.com/photo-1547407139-3c03a4b5498c?q=80&w=1200&auto=format&fit=crop">
                      <source src="/videos/paqueteria.mp4" type="video/mp4">
                    </video>
                  </figure>
                </div>
              </article>
            </div>

            <div class="swiper-slide">
              <article class="card-wrapper">
                <div class="card-circle">3</div>
                <div class="card reveal">
                  <h3 class="card-title">Surtido inteligente</h3>
                  <p class="card-desc">Básicos de oficina y escolares con stock en tiempo real y precios de volumen.</p>
                  <figure class="card-figure">
                    <video class="card-video"
                           preload="auto" muted playsinline webkit-playsinline
                           autoplay loop
                           poster="https://images.unsplash.com/photo-1516383607781-913a19294fd1?q=80&w=1200&auto=format&fit=crop">
                      <source src="/videos/envio.mp4" type="video/mp4">
                    </video>
                  </figure>
                </div>
              </article>
            </div>
          </div>

          <div class="swiper-pagination"></div>
        </div>
      </section>
    </div>

    <div class="no-support">
      <h2>Tu navegador no soporta <code>shape()</code> aún.</h2>
      <p>Para ver los ejemplos, usa un navegador compatible.</p>
    </div>
  </div>

  <script>
    const msSwiper = new Swiper('#morning-steps .steps', {
      slidesPerView: 1,
      spaceBetween: 20,
      autoHeight: true,
      pagination: { el: '#morning-steps .swiper-pagination', clickable: true },
      breakpoints: {
        768:  { slidesPerView: 2, spaceBetween: 24 },
        1024: { slidesPerView: 3, spaceBetween: 28 }
      },
      on: { init: ensureAllPlaying, slideChange: ensureAllPlaying }
    });

    function forcePlay(v){
      if (!v) return;
      v.muted = true;
      const tryPlay = () => v.play().catch(()=>{});
      if (v.readyState >= 2) { tryPlay(); }
      else {
        v.addEventListener('canplay', tryPlay, { once:true });
        setTimeout(tryPlay, 700);
      }
    }

    function ensureAllPlaying(){
      document.querySelectorAll('#morning-steps .card-video').forEach(v => forcePlay(v));
    }

    window.addEventListener('load', ensureAllPlaying);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) ensureAllPlaying(); });
    window.addEventListener('focus', ensureAllPlaying);

    let retries = 0;
    const retryTimer = setInterval(()=>{
      ensureAllPlaying();
      if (++retries >= 4) clearInterval(retryTimer);
    }, 1200);

    (function(){
      const els = document.querySelectorAll('#morning-steps .reveal');
      const io = new IntersectionObserver((entries, obs)=>{
        entries.forEach(entry=>{
          if (entry.isIntersecting){
            const el = entry.target;
            el.classList.add('in-view');
            el.addEventListener('animationend', ()=>{
              el.classList.remove('in-view');
              el.classList.add('played');
            }, { once:true });
            obs.unobserve(el);
          }
        });
      }, { threshold: 0.12, rootMargin: '0px 0px -5% 0px' });
      els.forEach(el=>io.observe(el));
    })();
  </script>

  {{-- ======= Secciones administrables (LandingSection) ======= --}}
  @php
    $sections = \App\Models\LandingSection::with('items')
                ->where('is_active',true)->orderBy('sort_order')->get();
  @endphp
  @foreach($sections as $section)
    @includeFirst(['landing.render','panel.landing.render'], ['section'=>$section])
  @endforeach

  {{-- ===================== PRODUCTOS ESTILO NEL0 ===================== --}}
  <style>
    .nelo-shop{
      --ns-bg:#ffffff; /* Fondo blanco estilo Nelo */
      --ns-text:#333333;
      --ns-primary:#ff4a4a;
      --ns-blue:#e6f0ff;
      --ns-blue-text:#1677ff;
      --ns-green:#e6ffe6;
      --ns-green-text:#15803d;
      --ns-star:#1677ff;
    }

    .nelo-shop,
    .nelo-shop *{ box-sizing:border-box; }

    .nelo-shop{
      width:100vw;
      margin-left:calc(50% - 50vw);
      margin-right:calc(50% - 50vw);
      background:var(--ns-bg);
      padding:40px 0;
      overflow:hidden;
      font-family: inherit;
    }

    .nelo-shop .ns-container{
      width:min(100%, 1400px);
      margin:0 auto;
      padding:0 24px;
    }

    .nelo-shop .ns-section + .ns-section{
      margin-top:40px;
    }

    .nelo-shop .ns-head{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      margin-bottom:24px;
      padding: 0 10px;
    }

    .nelo-shop .ns-title{
      margin:0;
      color:var(--ns-text);
      font-size:32px;
      line-height:1.2;
      font-weight:600;
    }

    .nelo-shop .ns-more{
      display:inline-flex;
      align-items:center;
      gap:4px;
      color:var(--ns-blue-text);
      text-decoration:none;
      font-weight:400;
      font-size:16px;
      white-space:nowrap;
    }

    .nelo-shop .ns-more svg{
      width:16px;
      height:16px;
      stroke:currentColor;
    }

    .nelo-shop .ns-slider-wrap{
      position:relative;
    }

    .nelo-shop .ns-nav{
      position:absolute;
      top:35%; /* Centrado relativo a la imagen */
      transform:translateY(-50%);
      z-index:5;
      width:52px;
      height:52px;
      border-radius:50%;
      border:0;
      background:#ffffff;
      box-shadow:0 4px 12px rgba(0,0,0,0.08);
      display:flex;
      align-items:center;
      justify-content:center;
      cursor:pointer;
      color:#333;
      transition:all .2s ease;
    }

    .nelo-shop .ns-nav:hover{
      box-shadow:0 6px 16px rgba(0,0,0,0.12);
      transform:translateY(-50%) scale(1.02);
    }

    .nelo-shop .ns-nav:disabled{
      opacity:0;
      pointer-events: none;
    }

    .nelo-shop .ns-nav--prev{ left:-20px; }
    .nelo-shop .ns-nav--next{ right:-20px; }

    .nelo-shop .ns-nav svg{
      width:24px;
      height:24px;
      stroke:currentColor;
      stroke-width:1.5;
    }

    .nelo-shop .ns-track{
      display:flex;
      gap:20px;
      overflow-x:auto;
      scroll-behavior:smooth;
      scrollbar-width:none;
      padding:10px;
    }

    .nelo-shop .ns-track::-webkit-scrollbar{ display:none; }

    .nelo-shop .ns-card{
      flex:0 0 280px;
      min-width:280px;
      background:#ffffff;
      text-decoration:none;
      color:inherit;
      position:relative;
      display:flex;
      flex-direction:column;
    }

    .nelo-shop .ns-discount{
      position:absolute;
      top:10px;
      right:10px; /* Alineado a la derecha como en Nelo */
      z-index:3;
      background:var(--ns-primary);
      color:#fff;
      font-weight:600;
      font-size:12px;
      line-height:1;
      padding:4px 10px;
      border-radius:12px;
    }

    .nelo-shop .ns-imagebox{
      height:220px;
      display:flex;
      align-items:center;
      justify-content:center;
      margin-bottom:12px;
    }

    .nelo-shop .ns-imagebox img{
      max-width:100%;
      max-height:100%;
      object-fit:contain;
      display:block;
      transition:transform .2s ease;
    }

    .nelo-shop .ns-card:hover .ns-imagebox img{
      transform:scale(1.03);
    }

    .nelo-shop .ns-price{
      display:flex;
      align-items:center;
      gap:8px;
      flex-wrap:wrap;
      margin-bottom:8px;
    }

    .nelo-shop .ns-price-now{
      font-size:16px;
      font-weight:700;
      color:var(--ns-primary);
    }

    .nelo-shop .ns-price-old{
      font-size:14px;
      color:#a1a1aa;
      text-decoration:line-through;
    }

    .nelo-shop .ns-tags{
      display:flex;
      gap:6px;
      flex-wrap:wrap;
      margin-bottom:8px;
    }

    .nelo-shop .ns-tag{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:3px 6px;
      border-radius:4px;
      font-size:11px;
      font-weight:500;
    }

    .nelo-shop .ns-tag--blue{
      background:var(--ns-blue);
      color:var(--ns-blue-text);
    }

    .nelo-shop .ns-tag--green{
      background:var(--ns-green);
      color:var(--ns-green-text);
    }

    .nelo-shop .ns-name{
      font-size:14px;
      line-height:1.4;
      color:#666666;
      font-weight:400;
      margin:0 0 4px;
      display:-webkit-box;
      -webkit-line-clamp:2;
      -webkit-box-orient:vertical;
      overflow:hidden;
      min-height:39px;
    }

    /* NUEVO ESTILO DE PRESENTACIÓN */
    .nelo-shop .ns-presentation {
      font-size: 12px;
      color: var(--ns-blue-text);
      font-weight: 600;
      margin-bottom: 8px;
    }

    .nelo-shop .ns-rating{
      display:flex;
      align-items:center;
      gap:4px;
      color:#666666;
      font-size:13px;
    }

    .nelo-shop .ns-stars{
      display:inline-flex;
      align-items:center;
      gap:1px;
    }

    .nelo-shop .ns-stars svg{
      width:14px;
      height:14px;
      fill:var(--ns-star);
    }

    @media (max-width: 1200px){
      .nelo-shop .ns-card{
        flex-basis:240px;
        min-width:240px;
      }
      .nelo-shop .ns-imagebox{
        height:190px;
      }
    }

    @media (max-width: 768px){
      .nelo-shop{
        padding:24px 0;
      }
      .nelo-shop .ns-container{
        padding:0;
      }
      .nelo-shop .ns-head{
        padding: 0 16px;
      }
      .nelo-shop .ns-title{
        font-size:20px;
      }
      .nelo-shop .ns-more{
        font-size:14px;
      }
      .nelo-shop .ns-track{
        gap:12px;
        padding:10px 16px;
        scroll-snap-type:x mandatory;
      }
      .nelo-shop .ns-card{
        flex:0 0 160px;
        min-width:160px;
        scroll-snap-align:start;
      }
      .nelo-shop .ns-imagebox{
        height:140px;
      }
      .nelo-shop .ns-nav{
        display:none;
      }
      .nelo-shop .ns-name{
        font-size:13px;
      }
    }
  </style>

  @php
    // ========================================================
    // FUNCIONES PARA ARMAR TEXTO DE PRESENTACIÓN Y UTILIDADES
    // ========================================================
    $unitLabels = [
      'pieza'   => ['sing' => 'pieza',   'plur' => 'piezas'],
      'caja'    => ['sing' => 'caja',    'plur' => 'cajas'],
      'paquete' => ['sing' => 'paquete', 'plur' => 'paquetes'],
      'rollo'   => ['sing' => 'rollo',   'plur' => 'rollos'],
      'juego'   => ['sing' => 'juego',   'plur' => 'juegos'],
      'kit'     => ['sing' => 'kit',     'plur' => 'kits'],
      'bolsa'   => ['sing' => 'bolsa',   'plur' => 'bolsas'],
      'par'     => ['sing' => 'par',     'plur' => 'pares'],
      'set'     => ['sing' => 'set',     'plur' => 'sets'],
      'display' => ['sing' => 'display', 'plur' => 'displays'],
      'docena'  => ['sing' => 'docena',  'plur' => 'docenas'],
      'metro'   => ['sing' => 'metro',   'plur' => 'metros'],
      'litro'   => ['sing' => 'litro',   'plur' => 'litros'],
    ];

    $getPresentation = function($product) use ($unitLabels) {
      $unitKey = strtolower(trim((string)($product->unit_measure ?? 'pieza')));
      $contentQty = (int)($product->content_quantity ?? 1);
      if ($contentQty < 1) { $contentQty = 1; }
      $contentUnitKey = strtolower(trim((string)($product->content_unit_measure ?? 'pieza')));

      $unitSing = $unitLabels[$unitKey]['sing'] ?? ($unitKey !== '' ? $unitKey : 'pieza');
      $contentUnitSing = $unitLabels[$contentUnitKey]['sing'] ?? ($contentUnitKey !== '' ? $contentUnitKey : 'pieza');
      $contentUnitPlur = $unitLabels[$contentUnitKey]['plur'] ?? ($contentUnitSing . 's');

      if ($unitKey !== 'pieza') {
        return ucfirst($unitSing) . ' con ' . $contentQty . ' ' . ($contentQty === 1 ? $contentUnitSing : $contentUnitPlur);
      }
      return '1 Pieza';
    };

    $catalogProducts = \App\Models\CatalogItem::with('category')
      ->published()
      ->ordered()
      ->take(120)
      ->get();

    $pickPhotoUrl = function($p){
      $candidates = [
        $p->photo_1 ?? null,
        $p->photo_2 ?? null,
        $p->photo_3 ?? null,
      ];

      $raw = collect($candidates)
        ->filter(fn($v) => is_string($v) && trim($v) !== '')
        ->first();

      if(!$raw){
        return asset('images/placeholder.png');
      }

      $raw = trim($raw);

      if (\Illuminate\Support\Str::startsWith($raw, ['http://','https://'])) {
        return $raw;
      }

      if (\Illuminate\Support\Str::startsWith($raw, ['storage/'])) {
        return asset($raw);
      }

      return \Illuminate\Support\Facades\Storage::url($raw);
    };

    $discountPct = function($p){
      return (!is_null($p->sale_price) && (float)$p->sale_price > 0 && (float)$p->sale_price < (float)$p->price)
        ? max(1, round(100 - (((float)$p->sale_price / (float)$p->price) * 100)))
        : null;
    };

    // ========================================================
    // ARMADO DE FILAS — cada fila con productos DISTINTOS (sin repetir)
    // ========================================================
    $productSections = collect();
    $usedIds = [];

    // Toma N productos de una colección, EXCLUYENDO los ya usados en filas anteriores
    $take = function ($collection, $limit = 18) use (&$usedIds) {
      $picked = $collection
        ->reject(fn($p) => in_array($p->id, $usedIds))
        ->take($limit)
        ->values();
      foreach ($picked as $p) { $usedIds[] = $p->id; }
      return $picked;
    };

    if ($catalogProducts->count()) {

      // 1) PARA TI → destacados (o lo mejor disponible si no hay destacados)
      $featured = $catalogProducts->filter(fn($p) => (bool)($p->is_featured ?? false))->values();
      $paraTi   = $take($featured->count() ? $featured : $catalogProducts, 18);
      if ($paraTi->count()) {
        $productSections->push([
          'title' => 'Para ti',
          'url'   => route('web.catalog.index'),
          'items' => $paraTi,
        ]);
      }

      // 2) OFERTAS → productos con descuento real (sin repetir los de arriba)
      $ofertas = $take(
        $catalogProducts->filter(fn($p) =>
          !is_null($p->sale_price)
          && (float)$p->sale_price > 0
          && (float)$p->sale_price < (float)$p->price
        )->values(),
        18
      );
      if ($ofertas->count()) {
        $productSections->push([
          'title' => 'Ofertas',
          'url'   => route('web.catalog.index', ['order' => 'price_asc']),
          'items' => $ofertas,
        ]);
      }

      // 3) POR CATEGORÍA → solo categorías reales, sin repetir productos
      $grouped = $catalogProducts
        ->filter(fn($p) => !empty($p->category?->name))
        ->groupBy(fn($p) => trim($p->category->name));

      foreach ($grouped as $categoryName => $items) {
        $picked = $take($items->values(), 18);
        if ($picked->count()) {
          $productSections->push([
            'title' => $categoryName,
            'url'   => route('web.catalog.index', ['s' => $categoryName]),
            'items' => $picked,
          ]);
        }
      }

      // 4) EL RESTO → se reparte en filas nuevas, cada una con productos DISTINTOS
      $restTitles = ['Otros', 'Más productos', 'También te puede interesar', 'Explora más', 'Recomendados'];
      foreach ($restTitles as $restTitle) {
        $picked = $take($catalogProducts, 18);
        if (!$picked->count()) break; // ya no quedan productos
        $productSections->push([
          'title' => $restTitle,
          'url'   => route('web.catalog.index'),
          'items' => $picked,
        ]);
      }
    }
  @endphp

  @if($productSections->count())
    <section class="nelo-shop" aria-label="Productos">
      <div class="ns-container">
        @foreach($productSections as $sectionIndex => $section)
          @if(($section['items'] ?? collect())->count())
            <div class="ns-section">
              <div class="ns-head">
                <h2 class="ns-title">{{ $section['title'] }}</h2>

                <a href="{{ $section['url'] }}" class="ns-more">
                  Ver Todo
                  <svg viewBox="0 0 24 24" fill="none">
                    <path d="M9 6l6 6-6 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </a>
              </div>

              <div class="ns-slider-wrap">
                <button class="ns-nav ns-nav--prev" type="button" data-ns-prev="ns-track-{{ $sectionIndex }}" aria-label="Anterior">
                  <svg viewBox="0 0 24 24" fill="none">
                    <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </button>

                <div class="ns-track" id="ns-track-{{ $sectionIndex }}">
                  @foreach($section['items'] as $p)
                    @php
                      $img = $pickPhotoUrl($p);
                      $hasSale = !is_null($p->sale_price) && (float)$p->sale_price > 0 && (float)$p->sale_price < (float)$p->price;
                      $discount = $discountPct($p);
                      $presentation = $getPresentation($p); // <-- SE ARMA EL TEXTO AQUÍ

                      $ratingCount = 0;
                      if (isset($p->reviews_count) && (int)$p->reviews_count > 0) {
                        $ratingCount = (int) $p->reviews_count;
                      } elseif (isset($p->rating_count) && (int)$p->rating_count > 0) {
                        $ratingCount = (int) $p->rating_count;
                      }
                    @endphp

                    <a href="{{ route('web.catalog.show', $p) }}" class="ns-card" aria-label="Ver {{ $p->name }}">
                      @if($discount)
                        <span class="ns-discount">-{{ $discount }}%</span>
                      @endif

                      <div class="ns-imagebox">
                        <img src="{{ $img }}"
                             alt="{{ $p->name }}"
                             loading="lazy"
                             onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
                      </div>

                      <div class="ns-price">
                        <span class="ns-price-now">
                          ${{ number_format($hasSale ? $p->sale_price : $p->price, 2) }}
                        </span>

                        @if($hasSale)
                          <span class="ns-price-old">${{ number_format($p->price, 2) }}</span>
                        @endif
                      </div>

                      <div class="ns-tags">
                        <span class="ns-tag ns-tag--blue">Sin intereses</span>
                        @if($hasSale && $discount >= 20)
                          <span class="ns-tag ns-tag--green">Envío gratis</span>
                        @endif
                      </div>

                      <h3 class="ns-name">{{ $p->name }}</h3>
                      
                      {{-- SE IMPRIME EL TEXTO DE PRESENTACIÓN --}}
                      <div class="ns-presentation">{{ $presentation }}</div>

                      <div class="ns-rating">
                        <span class="ns-stars" aria-hidden="true">
                          <svg viewBox="0 0 24 24"><path d="M12 3.7l2.6 5.28 5.83.85-4.22 4.11 1 5.8L12 16.97 6.79 19.74l1-5.8-4.22-4.11 5.83-.85L12 3.7z"/></svg>
                          <svg viewBox="0 0 24 24"><path d="M12 3.7l2.6 5.28 5.83.85-4.22 4.11 1 5.8L12 16.97 6.79 19.74l1-5.8-4.22-4.11 5.83-.85L12 3.7z"/></svg>
                          <svg viewBox="0 0 24 24"><path d="M12 3.7l2.6 5.28 5.83.85-4.22 4.11 1 5.8L12 16.97 6.79 19.74l1-5.8-4.22-4.11 5.83-.85L12 3.7z"/></svg>
                          <svg viewBox="0 0 24 24"><path d="M12 3.7l2.6 5.28 5.83.85-4.22 4.11 1 5.8L12 16.97 6.79 19.74l1-5.8-4.22-4.11 5.83-.85L12 3.7z"/></svg>
                          <svg viewBox="0 0 24 24"><path d="M12 3.7l2.6 5.28 5.83.85-4.22 4.11 1 5.8L12 16.97 6.79 19.74l1-5.8-4.22-4.11 5.83-.85L12 3.7z"/></svg>
                        </span>

                        @if($ratingCount > 0)
                          <span class="ns-count">({{ number_format($ratingCount) }})</span>
                        @endif
                      </div>
                    </a>
                  @endforeach
                </div>

                <button class="ns-nav ns-nav--next" type="button" data-ns-next="ns-track-{{ $sectionIndex }}" aria-label="Siguiente">
                  <svg viewBox="0 0 24 24" fill="none">
                    <path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </button>
              </div>
            </div>
          @endif
        @endforeach
      </div>
    </section>
  @endif

  <script>
    (function () {
      const isMobile = () => window.matchMedia('(max-width: 768px)').matches;

      function getStep(track){
        const firstCard = track.querySelector('.ns-card');
        if (!firstCard) return 260;
        const styles = window.getComputedStyle(track);
        const gap = parseFloat(styles.columnGap || styles.gap || 0);
        return firstCard.getBoundingClientRect().width + gap;
      }

      function updateButtons(track){
        const wrap = track.closest('.ns-slider-wrap');
        if (!wrap) return;

        const prev = wrap.querySelector('.ns-nav--prev');
        const next = wrap.querySelector('.ns-nav--next');
        if (!prev || !next) return;

        const maxScroll = track.scrollWidth - track.clientWidth;
        prev.disabled = track.scrollLeft <= 4;
        next.disabled = track.scrollLeft >= (maxScroll - 4);
      }

      document.querySelectorAll('.ns-track').forEach(track => {
        updateButtons(track);
        track.addEventListener('scroll', () => updateButtons(track), { passive:true });
        window.addEventListener('resize', () => updateButtons(track));
      });

      document.querySelectorAll('[data-ns-prev]').forEach(btn => {
        btn.addEventListener('click', () => {
          const id = btn.getAttribute('data-ns-prev');
          const track = document.getElementById(id);
          if (!track) return;
          track.scrollBy({ left: -getStep(track) * (isMobile() ? 2 : 4), behavior: 'smooth' });
        });
      });

      document.querySelectorAll('[data-ns-next]').forEach(btn => {
        btn.addEventListener('click', () => {
          const id = btn.getAttribute('data-ns-next');
          const track = document.getElementById(id);
          if (!track) return;
          track.scrollBy({ left: getStep(track) * (isMobile() ? 2 : 4), behavior: 'smooth' });
        });
      });
    })();
  </script>

  {{-- ======= Hero ======= --}}
  <section id="hero-full-pap">
    <div class="wrap">
      <div class="grid">
        <div>
          <h1>Soluciones en <span class="grad">papelería y oficina</span> listas para trabajar</h1>
          <p class="lead">Cotiza, compra y recibe con garantía. Atención personalizada, precios de mayoreo y entregas rápidas para empresas, escuelas y oficinas.</p>

          <div class="chips">
            <span class="chip"><span class="dot ok"></span> Factura CFDI 4.0</span>
            <span class="chip"><span class="dot brand"></span> Envío a todo México</span>
            <span class="chip"><span class="dot accent"></span> Mayoreo desde 5 piezas</span>
          </div>

          <div class="bullets">
            <div class="bullet"><i></i> Papelería escolar y de oficina (cuadernos, bolígrafos, folders, archivadores)</div>
            <div class="bullet"><i></i> Tintas y tóner para HP, Epson, Brother</div>
            <div class="bullet"><i></i> Mobiliario, organización y kits corporativos con tu logotipo</div>
          </div>

          <div class="cta">
            <a href="{{ route('web.ventas.index') }}" class="btn btn-primary">Ver catálogo</a>
            <a href="{{ route('web.contacto') }}" class="btn btn-ghost">Cotizar pedido</a>
          </div>

          <div class="trust">
            <small>Distribuimos marcas como</small>
            <img class="logo" src="{{ asset('images/brands/hp.png') }}" alt="HP" loading="lazy">
            <img class="logo" src="{{ asset('images/brands/epson.png') }}" alt="Epson" loading="lazy">
            <img class="logo" src="{{ asset('images/brands/acer.png') }}" alt="BIC" loading="lazy">
            <img class="logo" src="{{ asset('images/brands/asus.png') }}" alt="Pilot" loading="lazy">
          </div>
        </div>

        <div class="gallery">
          <figure class="card-img top">
            <img src="{{ asset('images/hero/papeleria.jpg') }}" alt="Estante con útiles escolares" loading="eager" decoding="async">
            <span class="tag">Listo para despacho</span>
            <span class="note">Stock continuo + reposición</span>
          </figure>
          <figure class="card-img bottom">
            <img src="{{ asset('images/hero/oficina.jpg') }}" alt="Escritorio de oficina con insumos" loading="lazy" decoding="async">
            <span class="tag">Oficina & Corporativo</span>
            <span class="note">Kits personalizados por área</span>
          </figure>
        </div>
      </div>
    </div>
  </section>

  {{-- ===================== SLIDER CENTER-MODE: PAPELERÍA ===================== --}}
  <section class="stc-wrap" aria-label="Explora categorías de papelería">
    <div class="stc-head">
      <h2>Todo para tu papelería y oficina</h2>
      <div class="stc-ctrls">
        <button id="stc-prev" class="stc-nav" aria-label="Anterior">‹</button>
        <button id="stc-next" class="stc-nav" aria-label="Siguiente">›</button>
      </div>
    </div>

    <div class="stc-slider">
      <div class="stc-track" id="stc-track">

        <article class="stc-card" active>
          <img class="stc-bg" src="https://i.pinimg.com/736x/ee/88/f7/ee88f7cf19772f93fa8b2f0f3c61217c.jpg" alt="">
          <div class="stc-content">
            <img class="stc-thumb" src="https://i.pinimg.com/736x/1c/c7/ce/1cc7cebc4cfd9bc33642930bdaf458d7.jpg" alt="">
            <div>
              <h3 class="stc-title">Cuadernos</h3>
              <p class="stc-desc">A4/A5, rayado, cuadriculado y profesionales. Marcas Scribe, Norma y más.</p>
              <a class="stc-btn" href="{{ route('web.catalog.index', ['q'=>'cuaderno']) }}">Ver catálogo</a>
            </div>
          </div>
        </article>

        <article class="stc-card">
          <img class="stc-bg" src="https://i.pinimg.com/736x/8d/c4/76/8dc476ecf6d000208851de99f0695c90.jpg" alt="">
          <div class="stc-content">
            <img class="stc-thumb" src="https://ss327.liverpool.com.mx/xl/1135049880.jpg" alt="">
            <div>
              <h3 class="stc-title">Plumas & Marcatextos</h3>
              <p class="stc-desc">Gel, roller, fineliner y permanentes. Sets escolares y de oficina.</p>
              <a class="stc-btn" href="{{ route('web.catalog.index', ['q'=>'pluma']) }}">Ver escritura</a>
            </div>
          </div>
        </article>

        <article class="stc-card">
          <img class="stc-bg" src="https://i.pinimg.com/736x/d1/57/db/d157dbf11fa154179a4a7cce84e9ae6d.jpg" alt="">
          <div class="stc-content">
            <img class="stc-thumb" src="https://i.pinimg.com/736x/d9/ff/b8/d9ffb8f88d3121a96df6c8ccfe40f287.jpg" alt="">
            <div>
              <h3 class="stc-title">Arte & Dibujo</h3>
              <p class="stc-desc">Acuarelas, pinceles, papeles artísticos, lápices y marcadores.</p>
              <a class="stc-btn" href="{{ route('web.catalog.index', ['q'=>'arte']) }}">Explorar arte</a>
            </div>
          </div>
        </article>

        <article class="stc-card">
          <img class="stc-bg" src="https://i.pinimg.com/736x/5f/80/fd/5f80fdf7dfd375a1f81ab5760abfec7b.jpg" alt="">
          <div class="stc-content">
            <img class="stc-thumb" src="https://i.pinimg.com/1200x/5e/53/ff/5e53ff058344dc39bd500c5951ec4c3c.jpg" alt="">
            <div>
              <h3 class="stc-title">Organización</h3>
              <p class="stc-desc">Folders, carpetas, clips, archiveros y todo para tu escritorio.</p>
              <a class="stc-btn" href="{{ route('web.catalog.index', ['q'=>'folder']) }}">Ordenar ahora</a>
            </div>
          </div>
        </article>

        <article class="stc-card">
          <img class="stc-bg" src="https://i.pinimg.com/736x/43/76/9e/43769eee587938a55b30b52cdabb985b.jpg" alt="">
          <div class="stc-content">
            <img class="stc-thumb" src="https://i.pinimg.com/1200x/56/59/42/5659427eb0c1eabfdf9907d23ef2a3ed.jpg" alt="">
            <div>
              <h3 class="stc-title">Impresión & Tintas</h3>
              <p class="stc-desc">Cartuchos y tóner originales. Asesoría para tu modelo y marca.</p>
              <a class="stc-btn" href="{{ route('web.catalog.index', ['q'=>'tinta']) }}">Ver tintas</a>
            </div>
          </div>
        </article>

      </div>
    </div>

    <div class="stc-dots" id="stc-dots"></div>
  </section>

  <style>
    .stc-wrap{width:100vw;margin-left:calc(50% - 50vw);margin-right:calc(50% - 50vw);background:#fff;padding:24px 14px}
    .stc-head{max-width:1400px;margin:auto;display:flex;gap:.75rem;align-items:flex-start;justify-content:space-between}
    .stc-head h2{margin:0;color:#0f172a;font-weight:900;font-size:clamp(20px,4.5vw,28px)}
    .stc-ctrls{display:flex;gap:.5rem}
    .stc-nav{width:40px;height:40px;border:1px solid #e5e7eb;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;color:#0f172a;cursor:pointer;transition:.2s}
    .stc-nav:disabled{opacity:.4;cursor:default}
    .stc-nav:hover{background:#0ea5e9;color:#fff;border-color:#0ea5e9}
    .stc-slider{max-width:1400px;margin:auto;overflow:hidden;padding-top:10px}
    .stc-track{display:flex;flex-direction:column;gap:12px;scroll-snap-type:y mandatory}
    .stc-track::-webkit-scrollbar{display:none}

    :root{--stc-closed:100%;--stc-open:100%;--stc-ease:.55s cubic-bezier(.25,.46,.45,.94)}
    .stc-card{position:relative;flex:0 0 auto;width:100%;min-height:260px;border-radius:16px;overflow:hidden;border:1px solid #e5e7eb;background:#fff;scroll-snap-align:start;transition:transform var(--stc-ease)}
    .stc-card[active]{box-shadow:0 14px 34px rgba(2,6,23,.12)}
    .stc-bg{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;filter:brightness(.95) saturate(98%);transition:filter .3s,transform var(--stc-ease)}
    .stc-card:hover .stc-bg{transform:scale(1.02)}
    .stc-content{position:absolute;inset:0;display:flex;align-items:flex-end;gap:.8rem;padding:1rem;background:linear-gradient(transparent 40%, rgba(0,0,0,.55) 90%);z-index:2}
    .stc-title{color:#fff;font-weight:900;font-size:1.15rem;margin-right:auto}
    .stc-thumb,.stc-desc,.stc-btn{display:none}
    .stc-card[active] .stc-content{align-items:flex-end;gap:1rem}
    .stc-card[active] .stc-thumb{display:block;width:110px;height:170px;border-radius:10px;object-fit:cover;box-shadow:0 6px 16px rgba(0,0,0,.28)}
    .stc-card[active] .stc-desc{display:block;color:#f1f5f9;line-height:1.45;max-width:100%}
    .stc-card[active] .stc-btn{display:inline-block;background:#0ea5e9;border:0;color:#fff;padding:.55rem 1rem;border-radius:999px;font-weight:800;text-decoration:none;box-shadow:0 10px 24px rgba(14,165,233,.28)}
    .stc-dots{display:none}

    @media (min-width:768px){
      .stc-wrap{padding: clamp(28px,4vw,44px) clamp(12px,2.4vw,28px)}
      .stc-head{align-items:flex-end}
      .stc-head h2{font-size:clamp(22px,3vw,34px)}
      .stc-track{flex-direction:row;gap:1rem;align-items:flex-start;justify-content:center;scroll-snap-type:x mandatory;padding-bottom:12px}
      :root{--stc-closed:5rem;--stc-open:30rem}
      .stc-card{flex:0 0 var(--stc-closed);height:26rem;border-radius:18px}
      .stc-card[active]{flex-basis:var(--stc-open);transform:translateY(-4px)}
      .stc-content{flex-direction:column;justify-content:center;align-items:center;background:linear-gradient(transparent 45%, rgba(0,0,0,.55) 100%)}
      .stc-title{writing-mode:vertical-rl;transform:rotate(180deg);font-size:1.2rem;margin:0}
      .stc-thumb,.stc-desc,.stc-btn{display:none}
      .stc-card[active] .stc-content{flex-direction:row;gap:1rem;padding:1.2rem 1.6rem;background:linear-gradient(transparent 10%, rgba(0,0,0,.65) 85%)}
      .stc-card[active] .stc-title{writing-mode:horizontal-tb;transform:none;font-size:1.9rem}
      .stc-card[active] .stc-thumb,.stc-card[active] .stc-desc,.stc-card[active] .stc-btn{display:block}
      .stc-thumb{width:130px;height:210px}
      .stc-desc{max-width:18rem}
      .stc-dots{display:flex;gap:.45rem;justify-content:center;padding:6px 0}
      .stc-dot{width:11px;height:11px;border-radius:50%;background:#cbd5e1;cursor:pointer;transition:.2s}
      .stc-dot.active{background:#0ea5e9;transform:scale(1.15)}
    }
  </style>

  <script>
    (() => {
      const track = document.getElementById('stc-track');
      const wrap  = track.parentElement;
      const cards = Array.from(track.children);
      const prev  = document.getElementById('stc-prev');
      const next  = document.getElementById('stc-next');
      const dotsBox = document.getElementById('stc-dots');

      cards.forEach((_, i) => {
        const d = document.createElement('span');
        d.className = 'stc-dot';
        d.addEventListener('click', () => activate(i, true));
        dotsBox.appendChild(d);
      });
      const dots = Array.from(dotsBox.children);

      const isMobile = () => matchMedia('(max-width:767px)').matches;
      let current = 0;

      function center(i){
        const card  = cards[i];
        const axis  = isMobile() ? 'top' : 'left';
        const size  = isMobile() ? 'clientHeight' : 'clientWidth';
        const start = isMobile() ? card.offsetTop : card.offsetLeft;
        wrap.scrollTo({ [axis]: start - (wrap[size]/2 - card[size]/2), behavior:'smooth' });
      }

      function toggleUI(i){
        cards.forEach((c,k) => c.toggleAttribute('active', k === i));
        dots.forEach((d,k) => d.classList.toggle('active', k === i));
        prev.disabled = (i === 0);
        next.disabled = (i === cards.length - 1);
      }

      function activate(i, scroll){
        if(i === current) return;
        current = i;
        toggleUI(i);
        if(scroll) center(i);
      }

      function go(step){
        activate(Math.min(Math.max(current + step, 0), cards.length - 1), true);
      }

      prev.addEventListener('click', () => go(-1));
      next.addEventListener('click', () => go(1));

      cards.forEach((card, i) => {
        card.addEventListener('mouseenter', () => matchMedia('(hover:hover)').matches && activate(i, true));
        card.addEventListener('click', () => activate(i, true));
      });

      let sx=0, sy=0;
      track.addEventListener('touchstart', e => { sx=e.touches[0].clientX; sy=e.touches[0].clientY; }, {passive:true});
      track.addEventListener('touchend', e => {
        const dx = e.changedTouches[0].clientX - sx;
        const dy = e.changedTouches[0].clientY - sy;
        const dist = isMobile()? Math.abs(dy) : Math.abs(dx);
        if(dist > 60) go((isMobile()? dy : dx) > 0 ? -1 : 1);
      }, {passive:true});

      addEventListener('keydown', e => {
        if(['ArrowRight','ArrowDown'].includes(e.key)) go(1);
        if(['ArrowLeft','ArrowUp'].includes(e.key)) go(-1);
      }, {passive:true});

      addEventListener('resize', () => center(current));
      toggleUI(0);
      center(0);
    })();
  </script>

  {{-- ======= 3 valores rápidos ======= --}}
  <div class="container">
    <div class="grid" style="margin-top: 18px;">
      <div class="col-4">
        <div class="feature">
          <div>
            <div class="feature__chip">Logística</div>
            <div class="feature__title">Envíos nacionales</div>
            <p class="feature__desc">Cobertura en todo México con aliados confiables.</p>
          </div>
        </div>
      </div>
      <div class="col-4">
        <div class="feature">
          <div>
            <div class="feature__chip">Garantía</div>
            <div class="feature__title">Soporte técnico</div>
            <p class="feature__desc">Acompañamiento posventa y pólizas disponibles.</p>
          </div>
        </div>
      </div>
      <div class="col-4">
        <div class="feature">
          <div>
            <div class="feature__chip">Pagos</div>
            <div class="feature__title">Pagos seguros</div>
            <p class="feature__desc">Opciones a meses y comprobantes al instante.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    (function(){
      const obs = 'IntersectionObserver' in window ? new IntersectionObserver((entries, io)=>{
        entries.forEach(e=>{
          if(e.isIntersecting){
            e.target.classList.add('in');
            io.unobserve(e.target);
          }
        });
      }, {rootMargin:'0px 0px -10% 0px'}) : null;

      document.querySelectorAll('.lp-card.ao').forEach(el=>{
        if(obs) obs.observe(el); else el.classList.add('in');
      });
    })();
  </script>

  @if(isset($marqueeComments) && $marqueeComments->count())
    <section id="home-cmt-marquee">

      {{-- ===================== DISEÑO NELO + APPLE ===================== --}}
      <style>
        #home-cmt-marquee{
          /* 🎨 PALETA — cambia estos valores para otro color */
          --cmt-bg-1:#fbfbfd;        /* fondo superior (gris Apple) */
          --cmt-bg-2:#f5f5f7;        /* fondo inferior */
          --cmt-card:#ffffff;
          --cmt-border:#ededf2;
          --cmt-ink:#1d1d1f;         /* texto principal (negro Apple) */
          --cmt-sub:#6e6e73;         /* texto secundario */
          --cmt-body:#3a3a3c;        /* cuerpo del comentario */
          --cmt-accent:#0071e3;      /* azul Apple */
          --cmt-accent-2:#00c2c7;    /* cian acento */
          --cmt-pill-bg:#f0f4ff;
          --cmt-pill-ink:#0071e3;
          --cmt-speed:60s;           /* velocidad del carrusel */

          /* ===== FULL-BLEED ROBUSTO (no empuja) ===== */
          position:relative;
          left:50%;
          transform:translateX(-50%);
          width:100vw;
          max-width:100vw;
          margin-top:40px;
          margin-left:0;
          margin-right:0;

          background:linear-gradient(180deg, var(--cmt-bg-1), var(--cmt-bg-2));
          padding:72px 0 84px;
          overflow:hidden;
          font-family:-apple-system, BlinkMacSystemFont, "Segoe UI", "Poppins", Roboto, sans-serif;
          -webkit-font-smoothing:antialiased;
        }

        /* ---- Encabezado estilo Apple (centrado, grande, limpio) ---- */
        #home-cmt-marquee .wrap{
          max-width:760px; margin:0 auto 52px; padding:0 24px; text-align:center;
        }
        #home-cmt-marquee header h2{
          margin:0 0 14px; font-weight:700; letter-spacing:-.02em;
          color:var(--cmt-ink); font-size:clamp(30px,5vw,52px); line-height:1.08;
        }
        #home-cmt-marquee header h2 .grad{
          background:linear-gradient(90deg, var(--cmt-accent), var(--cmt-accent-2));
          -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent;
        }
        #home-cmt-marquee header .lead{
          margin:0 auto 26px; color:var(--cmt-sub);
          font-size:clamp(16px,2.2vw,20px); line-height:1.5; max-width:600px; font-weight:400;
        }
        #home-cmt-marquee .header-actions{
          display:flex; gap:12px; justify-content:center; align-items:center; flex-wrap:wrap;
        }
        #home-cmt-marquee .tagline{
          display:inline-flex; align-items:center; gap:8px;
          background:rgba(255,255,255,.7); backdrop-filter:blur(10px);
          border:1px solid var(--cmt-border);
          color:var(--cmt-ink); font-weight:500; font-size:13px;
          padding:8px 15px; border-radius:999px;
        }
        #home-cmt-marquee .tagline .dot{
          width:8px; height:8px; border-radius:50%; background:#34c759;
          box-shadow:0 0 0 4px rgba(52,199,89,.16);
        }
        #home-cmt-marquee .btn-ghost{
          display:inline-flex; align-items:center;
          background:var(--cmt-accent); color:#fff !important;
          font-weight:500; font-size:14px; text-decoration:none;
          padding:10px 20px; border-radius:999px; border:0;
          transition:transform .18s ease, box-shadow .18s ease, background .18s ease;
          box-shadow:0 6px 18px rgba(0,113,227,.28);
        }
        #home-cmt-marquee .btn-ghost:hover{
          background:#0077ed; transform:translateY(-1px);
          box-shadow:0 8px 22px rgba(0,113,227,.34);
        }

        /* ---- Carrusel infinito (Nelo) ---- */
        #home-cmt-marquee .rows{ display:flex; flex-direction:column; gap:24px; }
        #home-cmt-marquee .row{ position:relative; overflow:hidden; }
        #home-cmt-marquee .row .track{
          display:flex; flex-wrap:nowrap; width:max-content; gap:20px; will-change:transform;
        }
        #home-cmt-marquee .row-1 .track{ animation:cmt-left var(--cmt-speed) linear infinite; }
        #home-cmt-marquee .row-2 .track{ animation:cmt-right var(--cmt-speed) linear infinite; }
        #home-cmt-marquee .row:hover .track{ animation-play-state:paused; }

        @keyframes cmt-left  { from{transform:translateX(0)}     to{transform:translateX(-50%)} }
        @keyframes cmt-right { from{transform:translateX(-50%)}  to{transform:translateX(0)} }

        /* Difuminado lateral */
        #home-cmt-marquee .row::before,
        #home-cmt-marquee .row::after{
          content:""; position:absolute; top:0; bottom:0; width:120px; z-index:3; pointer-events:none;
        }
        #home-cmt-marquee .row::before{ left:0;  background:linear-gradient(90deg, var(--cmt-bg-2), transparent); }
        #home-cmt-marquee .row::after { right:0; background:linear-gradient(270deg, var(--cmt-bg-2), transparent); }

        /* ---- Tarjetas estilo Apple (limpias, mucho aire) ---- */
        #home-cmt-marquee .card{
          flex:0 0 320px; width:320px;
          background:var(--cmt-card);
          border:1px solid var(--cmt-border);
          border-radius:22px;
          padding:24px;
          display:flex; flex-direction:column; gap:16px;
          box-shadow:0 2px 10px rgba(0,0,0,.03);
          transition:transform .25s cubic-bezier(.2,.7,.2,1), box-shadow .25s ease;
        }
        #home-cmt-marquee .card:hover{
          transform:translateY(-5px);
          box-shadow:0 18px 40px rgba(0,0,0,.10);
        }

        #home-cmt-marquee .card-head{ display:flex; align-items:center; gap:12px; }
        #home-cmt-marquee .avatar{
          width:46px; height:46px; border-radius:50%;
          display:flex; align-items:center; justify-content:center;
          font-weight:600; font-size:15px; color:#fff; flex:0 0 auto; letter-spacing:.02em;
        }
        #home-cmt-marquee .avatar.c0{ background:linear-gradient(135deg,#0071e3,#42a5ff); }
        #home-cmt-marquee .avatar.c1{ background:linear-gradient(135deg,#00c2c7,#34d2b4); }
        #home-cmt-marquee .avatar.c2{ background:linear-gradient(135deg,#ff9f0a,#ff6b3d); }
        #home-cmt-marquee .avatar.c3{ background:linear-gradient(135deg,#bf5af2,#ff4f9a); }

        #home-cmt-marquee .meta .name{ font-weight:600; color:var(--cmt-ink); font-size:15px; line-height:1.2; }
        #home-cmt-marquee .meta .user{ color:var(--cmt-sub); font-size:13px; margin-top:2px; }

        #home-cmt-marquee .body{
          color:var(--cmt-body); font-size:15px; line-height:1.6; letter-spacing:-.01em;
          display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden;
        }

        #home-cmt-marquee .pill{
          display:inline-flex; align-items:center; gap:6px; align-self:flex-start;
          background:var(--cmt-pill-bg); color:var(--cmt-pill-ink);
          font-weight:500; font-size:12.5px;
          padding:7px 13px; border-radius:999px;
        }

        @media (prefers-reduced-motion: reduce){
          #home-cmt-marquee .row .track{ animation:none; }
        }

        @media (max-width:768px){
          #home-cmt-marquee{ padding:52px 0 60px; }
          #home-cmt-marquee .wrap{ margin-bottom:36px; }
          #home-cmt-marquee .card{ flex-basis:280px; width:280px; padding:20px; border-radius:18px; }
          #home-cmt-marquee .row::before,
          #home-cmt-marquee .row::after{ width:56px; }
        }
      </style>
      {{-- ===================== FIN DISEÑO ===================== --}}

     
      @php
        $items = $marqueeComments->unique('id')->values();
        $count = $items->count();
        $half  = (int) ceil($count / 2);
        $row1  = $items->slice(0, $half)->values();
        $row2  = $items->slice($half)->values();

        $cardWidth    = 296;
        $virtualWidth = 2600;

        $seg1 = max(1, $row1->count()) * $cardWidth;
        $rep1 = (int) ceil($virtualWidth / $seg1);
        if ($rep1 < 2) $rep1 = 2;
        if ($rep1 % 2 === 1) $rep1++;

        $seg2 = max(1, $row2->count()) * $cardWidth;
        $rep2 = (int) ceil($virtualWidth / $seg2);
        if ($rep2 < 2) $rep2 = 2;
        if ($rep2 % 2 === 1) $rep2++;

        $colorIndex1 = 0;
        $colorIndex2 = 0;

        $badges = [
          ['icon' => '📦', 'text' => 'Entrega rápida'],
          ['icon' => '💳', 'text' => 'Pago seguro'],
          ['icon' => '🧾', 'text' => 'Factura CFDI 4.0'],
          ['icon' => '📞', 'text' => 'Atención personalizada'],
          ['icon' => '🏫', 'text' => 'Ideal para escuelas'],
          ['icon' => '🏢', 'text' => 'Soluciones corporativas'],
          ['icon' => '💼', 'text' => 'Proveedores para empresas'],
          ['icon' => '🎯', 'text' => 'Pedidos a la medida'],
          ['icon' => '🛠️', 'text' => 'Soporte postventa'],
          ['icon' => '🚚', 'text' => 'Envío a todo México'],
          ['icon' => '💰', 'text' => 'Precios de mayoreo'],
          ['icon' => '⭐', 'text' => 'Clientes recurrentes'],
          ['icon' => '🕒', 'text' => 'Respuesta rápida'],
          ['icon' => '📚', 'text' => 'Papelería completa'],
          ['icon' => '🧃', 'text' => 'Kits armados para oficina'],
        ];
        $badgeIndex1 = 0;
        $badgeIndex2 = 0;
      @endphp

      <div class="rows">
        <div class="row row-1">
          <div class="track">
            @for($r = 0; $r < $rep1; $r++)
              @foreach($row1 as $comment)
                @php
                  $name = $comment->nombre
                    ?? ($comment->user->name ?? ($comment->user->email ?? 'Cliente'));

                  $parts = preg_split('/\s+/', trim($name));
                  $initials = '';
                  if ($parts) {
                    foreach (array_slice($parts, 0, 2) as $p) {
                      $initials .= mb_strtoupper(mb_substr($p, 0, 1));
                    }
                  }

                  $email = $comment->email ?? optional($comment->user)->email;
                  $username = $email ? '@'.\Illuminate\Support\Str::before($email, '@') : '@cliente';
                  $body = \Illuminate\Support\Str::limit($comment->contenido, 140);
                  $colorClass = 'c'.($colorIndex1 % 4);
                  $colorIndex1++;
                  $badge = $badges[$badgeIndex1 % count($badges)];
                  $badgeIndex1++;
                @endphp

                <article class="card">
                  <div class="card-head">
                    <div class="avatar {{ $colorClass }}">{{ $initials ?: 'CL' }}</div>
                    <div class="meta">
                      <div class="name">{{ $name }}</div>
                      <div class="user">{{ $username }}</div>
                    </div>
                  </div>
                  <div class="body">“{{ $body }}”</div>
                  <div class="pill">
                    <span>{{ $badge['icon'] }}</span>
                    <span>{{ $badge['text'] }}</span>
                  </div>
                </article>
              @endforeach
            @endfor
          </div>
        </div>

        @if($row2->count())
          <div class="row row-2">
            <div class="track">
              @for($r = 0; $r < $rep2; $r++)
                @foreach($row2 as $comment)
                  @php
                    $name = $comment->nombre
                      ?? ($comment->user->name ?? ($comment->user->email ?? 'Cliente'));

                    $parts = preg_split('/\s+/', trim($name));
                    $initials = '';
                    if ($parts) {
                      foreach (array_slice($parts, 0, 2) as $p) {
                        $initials .= mb_strtoupper(mb_substr($p, 0, 1));
                      }
                    }

                    $email = $comment->email ?? optional($comment->user)->email;
                    $username = $email ? '@'.\Illuminate\Support\Str::before($email, '@') : '@cliente';
                    $body = \Illuminate\Support\Str::limit($comment->contenido, 140);
                    $colorClass = 'c'.($colorIndex2 % 4);
                    $colorIndex2++;
                    $badge = $badges[$badgeIndex2 % count($badges)];
                    $badgeIndex2++;
                  @endphp

                  <article class="card">
                    <div class="card-head">
                      <div class="avatar {{ $colorClass }}">{{ $initials ?: 'CL' }}</div>
                      <div class="meta">
                        <div class="name">{{ $name }}</div>
                        <div class="user">{{ $username }}</div>
                      </div>
                    </div>
                    <div class="body">“{{ $body }}”</div>
                    <div class="pill">
                      <span>{{ $badge['icon'] }}</span>
                      <span>{{ $badge['text'] }}</span>
                    </div>
                  </article>
                @endforeach
              @endfor
            </div>
          </div>
        @endif
      </div>
    </section>
  @endif

@endsection