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

  {{-- ====== ESTILOS RESPONSIVOS HERO (MÓVIL VS ESCRITORIO) ====== --}}
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    :root {
      --bg: #ffffff;
      --card: #ffffff;
      --ink: #333333;
      --blue: #4A90E2;
    }

    .mobile-steps-hero { display: block; }
    .desktop-promo-hero { display: none; }

    .desktop-promo-hero,
    .desktop-promo-hero * {
      box-sizing: border-box;
    }

    .desktop-promo-hero {
      font-family: 'Inter', sans-serif;
    }

    @media (min-width: 768px) {
      .mobile-steps-hero { display: none; }

      .desktop-promo-hero {
        display: block;
        width: 100vw;
        margin-left: calc(50% - 50vw);
        margin-right: calc(50% - 50vw);
        background: var(--bg);
        padding: 42px 0;
      }

      .promo-grid {
        width: min(100%, 1400px);
        margin: 0 auto;
        padding: 0 24px;
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        min-height: 380px;
      }

      .promo-main-slider {
        width: 100%;
        height: 100%;
        border-radius: 20px;
      }

      .promo-link-card {
        position: relative;
        overflow: hidden;
        min-height: 380px;
        display: flex;
        text-decoration: none;
        color: #ffffff; 
        background-color: var(--banner-color, #cccccc);
        border-radius: 20px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
      }

      .promo-link-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 28px rgba(0,0,0,0.12);
        color: #ffffff;
      }

      /* === LA IMAGEN ABARCA TODO SIN DESVANECIDO === */
      .promo-product-image {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover; /* Hace que llene todo sin deformarse */
        object-position: center; /* Centra la imagen perfectamente */
        z-index: 0;
        transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      }

      .promo-link-card:hover .promo-product-image {
        transform: scale(1.02); /* Animación de zoom muy sutil al pasar el mouse */
      }

      .promo-content {
        width: 60%;
        padding: 50px 40px;
        position: relative;
        z-index: 2; /* Por encima de la imagen */
        display: flex;
        flex-direction: column;
        justify-content: center;
        height: 100%;
        text-shadow: 0 2px 8px rgba(0,0,0,0.4); /* Sombra para que el texto sea legible si la imagen es clara */
      }

      .promo-content h2,
      .promo-content h3 {
        margin: 0 0 8px 0;
        color: #ffffff;
        font-weight: 600;
        line-height: 1.1;
      }

      .promo-content h2 {
        font-size: clamp(32px, 4vw, 48px);
      }

      .promo-content h3 {
        font-size: 28px;
      }

      .promo-content p {
        margin: 0 0 24px;
        color: rgba(255, 255, 255, 0.95);
        font-size: 16px;
        font-weight: 400;
        line-height: 1.5;
      }

      .promo-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        padding: 0 24px;
        border-radius: 999px;
        background: #ffffff;
        color: #333333; /* Texto del botón siempre oscuro para que contraste */
        font-size: 15px;
        font-weight: 700;
        align-self: flex-start;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        transition: transform 0.2s ease, filter 0.2s ease;
        text-shadow: none;
      }

      .promo-link-card:hover .promo-btn {
        transform: scale(1.02);
      }

      /* === AJUSTES PARA EL BANNER LATERAL === */
      .promo-side-banner .promo-content {
        width: 100%;
        justify-content: flex-end;
        padding: 30px;
      }

      .promo-main-slider .swiper-pagination {
        bottom: 20px !important;
        z-index: 10;
        text-align: left;
        padding-left: 40px;
      }

      .promo-main-slider .swiper-pagination-bullet {
        background: rgba(255, 255, 255, 0.5);
        opacity: 1;
        width: 8px;
        height: 8px;
        transition: 0.3s ease;
      }

      .promo-main-slider .swiper-pagination-bullet-active {
        background: #ffffff;
        width: 24px;
        border-radius: 999px;
      }
    }

    @media (min-width: 768px) and (max-width: 980px) {
      .promo-grid {
        grid-template-columns: 1fr;
      }
      .promo-side-banner {
        min-height: 260px;
      }
    }
  </style>

  {{-- ====== VISTA MÓVIL (Tarjetas de Pasos Originales) ====== --}}
  <div id="morning-steps" class="mobile-steps-hero">
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
                <text x='50%' y='50%' font-size='${fontSize}' font-weight='${fontWeight}' text-anchor='middle' dominant-baseline='middle' font-family='${fontFamily}'>${text}</text>
              </svg>`;
              el.style.setProperty("--mask", `url("data:image/svg+xml,${encodeURIComponent(svg)}")`);
            }
            els.forEach(el => {
              setMask(el);
              if (!el.querySelector("video")) {
                const v = document.createElement("video");
                v.className = "video-text__video";
                v.autoplay = true; v.muted = true; v.loop = true; v.playsInline = true;
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
                    <video class="card-video" preload="auto" muted playsinline autoplay loop poster="https://images.unsplash.com/photo-1519074069444-1ba4fff66d16?q=80&w=1200&auto=format&fit=crop">
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
                    <video class="card-video" preload="auto" muted playsinline autoplay loop poster="https://images.unsplash.com/photo-1547407139-3c03a4b5498c?q=80&w=1200&auto=format&fit=crop">
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
                    <video class="card-video" preload="auto" muted playsinline autoplay loop poster="https://images.unsplash.com/photo-1516383607781-913a19294fd1?q=80&w=1200&auto=format&fit=crop">
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
  </div>

  {{-- ====== VISTA ESCRITORIO / TABLET (Banners Administrables) ====== --}}
  @php
    $homeBanners = $homeBanners ?? collect();

    $mainBanners = $homeBanners
        ->where('position', 'main')
        ->values();

    $sideBanner = $homeBanners
        ->where('position', 'side')
        ->first();

    $fallbackMainBanners = collect([
        [
            'title' => 'Compra en Tienda Jureto',
            'description' => 'Tenemos grandiosas ofertas.',
            'button_text' => 'Crea tu cuenta',
            'button_url' => route('web.catalog.index', ['s' => 'Escolares']),
            'image_path' => null,
            'background_color' => '#112244', // Color de prueba
        ]
    ]);

    $fallbackSideBanner = (object) [
        'title' => 'Tecnología',
        'description' => 'Compra seguro',
        'button_text' => 'Ver más',
        'button_url' => route('web.catalog.index', ['s' => 'Tecnología']),
        'image_path' => null,
        'background_color' => '#333333',
    ];

    $renderMainBanners = $mainBanners->count() ? $mainBanners : $fallbackMainBanners;
    $renderSideBanner = $sideBanner ?: $fallbackSideBanner;

    $homeBannerLiveStats = \App\Models\HomeBanner::query()
        ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_total, MAX(updated_at) as latest_update')
        ->first();

    $homeBannerLiveVersion = sha1(implode('|', [
        $homeBannerLiveStats->total ?? 0,
        $homeBannerLiveStats->active_total ?? 0,
        $homeBannerLiveStats->latest_update ?? '',
    ]));
  @endphp

  <div class="desktop-promo-hero">
    <div class="promo-grid">

      <!-- BANNER PRINCIPAL (SLIDER) -->
      <div class="swiper promo-main-slider">
        <div class="swiper-wrapper">

          @foreach($renderMainBanners as $banner)
            @php
              $bannerObject = is_array($banner) ? (object) $banner : $banner;

              $bannerImage = !empty($bannerObject->image_path)
                  ? asset('storage/' . $bannerObject->image_path)
                  : null;

              $bannerUrl = !empty($bannerObject->button_url) ? $bannerObject->button_url : '#';
              $bannerColor = !empty($bannerObject->background_color) ? $bannerObject->background_color : '#cccccc';
            @endphp

            <a href="{{ $bannerUrl }}" 
               class="swiper-slide promo-link-card {{ $bannerImage ? 'has-image' : '' }}" 
               style="--banner-color: {{ $bannerColor }};">
              
              {{-- La imagen ahora cubre todo el fondo SIN degradados --}}
              @if($bannerImage)
                <img src="{{ $bannerImage }}" alt="{{ $bannerObject->title }}" class="promo-product-image" loading="eager">
              @endif

              <div class="promo-content">
                @if(!empty($bannerObject->title))
                  <h2>{{ $bannerObject->title }}</h2>
                @endif

                @if(!empty($bannerObject->description))
                  <p>{{ $bannerObject->description }}</p>
                @endif

                @if(!empty($bannerObject->button_text))
                  {{-- Si no quieres que se vea el botón porque tu imagen ya trae uno, puedes dejar este campo vacío en tu base de datos --}}
                  <span class="promo-btn">
                    {{ $bannerObject->button_text }}
                  </span>
                @endif
              </div>
            </a>
          @endforeach

        </div>
        <div class="swiper-pagination"></div>
      </div>

      <!-- BANNER LATERAL -->
      @php
        $sideImage = !empty($renderSideBanner->image_path) ? asset('storage/' . $renderSideBanner->image_path) : null;
        $sideUrl = !empty($renderSideBanner->button_url) ? $renderSideBanner->button_url : '#';
        $sideColor = !empty($renderSideBanner->background_color) ? $renderSideBanner->background_color : '#333333';
      @endphp

      <a href="{{ $sideUrl }}" 
         class="promo-link-card promo-side-banner {{ $sideImage ? 'has-image' : '' }}" 
         style="--banner-color: {{ $sideColor }};">
        
        @if($sideImage)
          <img src="{{ $sideImage }}" alt="{{ $renderSideBanner->title }}" class="promo-product-image" loading="eager">
        @endif

        <div class="promo-content">
          @if(!empty($renderSideBanner->title))
            <h3>{{ $renderSideBanner->title }}</h3>
          @endif

          @if(!empty($renderSideBanner->description))
            <p style="margin-bottom: 0;">{{ $renderSideBanner->description }}</p>
          @endif

          @if(!empty($renderSideBanner->button_text))
            <span class="promo-btn" style="margin-top: 15px;">
              {{ $renderSideBanner->button_text }}
            </span>
          @endif
        </div>
      </a>

    </div>
  </div>

  {{-- ====== INICIALIZACIÓN DE SCRIPTS HERO ====== --}}
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (document.querySelector('#morning-steps .steps') && typeof Swiper !== 'undefined') {
        new Swiper('#morning-steps .steps', {
          slidesPerView: 1, spaceBetween: 20, autoHeight: true,
          pagination: { el: '#morning-steps .swiper-pagination', clickable: true },
          on: { init: ensureAllPlaying, slideChange: ensureAllPlaying }
        });
      }

      if (document.querySelector('.promo-main-slider') && typeof Swiper !== 'undefined') {
        new Swiper('.promo-main-slider', {
          slidesPerView: 1,
          loop: true,
          autoplay: { delay: 6000, disableOnInteraction: false },
          pagination: { el: '.promo-main-slider .swiper-pagination', clickable: true },
        });
      }
      ensureAllPlaying();

      // ====== ACTUALIZACIÓN AUTOMÁTICA DE BANNERS ======
      // No cambia el diseño. Solo revisa si hubo cambios en banners y recarga la vista para mostrar la nueva imagen/texto.
      const bannerLiveUrl = @json(route('home-banners.live-version'));
      let currentBannerVersion = @json($homeBannerLiveVersion);
      let isCheckingBannerVersion = false;

      async function checkHomeBannerVersion() {
        if (isCheckingBannerVersion || document.hidden) return;

        isCheckingBannerVersion = true;

        try {
          const response = await fetch(`${bannerLiveUrl}?t=${Date.now()}`, {
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            cache: 'no-store'
          });

          if (!response.ok) return;

          const data = await response.json();

          if (data.version && data.version !== currentBannerVersion) {
            window.location.reload();
          }
        } catch (error) {
          // Evita romper la página si falla la red.
        } finally {
          isCheckingBannerVersion = false;
        }
      }

      if (document.querySelector('.desktop-promo-hero')) {
        setInterval(checkHomeBannerVersion, 5000);
        window.addEventListener('focus', checkHomeBannerVersion);
        document.addEventListener('visibilitychange', function () {
          if (!document.hidden) checkHomeBannerVersion();
        });
      }
    });

    function forcePlay(v){
      if (!v) return; v.muted = true;
      const tryPlay = () => v.play().catch(()=>{});
      if (v.readyState >= 2) { tryPlay(); } else {
        v.addEventListener('canplay', tryPlay, { once:true });
        setTimeout(tryPlay, 700);
      }
    }

    function ensureAllPlaying(){ document.querySelectorAll('#morning-steps .card-video').forEach(v => forcePlay(v)); }
    window.addEventListener('load', ensureAllPlaying);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) ensureAllPlaying(); });
    window.addEventListener('focus', ensureAllPlaying);
    let retries = 0;
    const retryTimer = setInterval(()=>{ ensureAllPlaying(); if (++retries >= 4) clearInterval(retryTimer); }, 1200);

    (function(){
      const els = document.querySelectorAll('#morning-steps .reveal');
      const io = new IntersectionObserver((entries, obs)=>{
        entries.forEach(entry=>{
          if (entry.isIntersecting){
            const el = entry.target;
            el.classList.add('in-view');
            el.addEventListener('animationend', ()=>{ el.classList.remove('in-view'); el.classList.add('played'); }, { once:true });
            obs.unobserve(el);
          }
        });
      }, { threshold: 0.12, rootMargin: '0px 0px -5% 0px' });
      els.forEach(el=>io.observe(el));
    })();
  </script>

  {{-- ======= Secciones administrables (LandingSection) ======= --}}
  @php
    $sections = \App\Models\LandingSection::with('items')->where('is_active',true)->orderBy('sort_order')->get();
  @endphp
  @foreach($sections as $section)
    @includeFirst(['landing.render','panel.landing.render'], ['section'=>$section])
  @endforeach

  {{-- ===================== PRODUCTOS ESTILO NEL0 ===================== --}}
  <style>
    .nelo-shop{
      --ns-bg:#ffffff; 
      --ns-text:#333333;
      --ns-primary:#ff4a4a;
      --ns-blue:#e6f0ff;
      --ns-blue-text:#1677ff;
      --ns-green:#e6ffe6;
      --ns-green-text:#15803d;
      --ns-star:#1677ff;
    }
    .nelo-shop, .nelo-shop *{ box-sizing:border-box; }
    .nelo-shop{ width:100vw; margin-left:calc(50% - 50vw); margin-right:calc(50% - 50vw); background:var(--ns-bg); padding:40px 0; overflow:hidden; font-family: inherit; }
    .nelo-shop .ns-container{ width:min(100%, 1400px); margin:0 auto; padding:0 24px; }
    .nelo-shop .ns-section + .ns-section{ margin-top:40px; }
    .nelo-shop .ns-head{ display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:24px; padding: 0 10px; }
    .nelo-shop .ns-title{ margin:0; color:var(--ns-text); font-size:32px; line-height:1.2; font-weight:600; }
    .nelo-shop .ns-more{ display:inline-flex; align-items:center; gap:4px; color:var(--ns-blue-text); text-decoration:none; font-weight:400; font-size:16px; white-space:nowrap; }
    .nelo-shop .ns-more svg{ width:16px; height:16px; stroke:currentColor; }
    .nelo-shop .ns-slider-wrap{ position:relative; }
    .nelo-shop .ns-nav{ position:absolute; top:35%; transform:translateY(-50%); z-index:5; width:52px; height:52px; border-radius:50%; border:0; background:#ffffff; box-shadow:0 4px 12px rgba(0,0,0,0.08); display:flex; align-items:center; justify-content:center; cursor:pointer; color:#333; transition:all .2s ease; }
    .nelo-shop .ns-nav:hover{ box-shadow:0 6px 16px rgba(0,0,0,0.12); transform:translateY(-50%) scale(1.02); }
    .nelo-shop .ns-nav:disabled{ opacity:0; pointer-events: none; }
    .nelo-shop .ns-nav--prev{ left:-20px; }
    .nelo-shop .ns-nav--next{ right:-20px; }
    .nelo-shop .ns-nav svg{ width:24px; height:24px; stroke:currentColor; stroke-width:1.5; }
    .nelo-shop .ns-track{ display:flex; gap:20px; overflow-x:auto; scroll-behavior:smooth; scrollbar-width:none; padding:10px; }
    .nelo-shop .ns-track::-webkit-scrollbar{ display:none; }
    .nelo-shop .ns-card{ flex:0 0 280px; min-width:280px; background:#ffffff; text-decoration:none; color:inherit; position:relative; display:flex; flex-direction:column; }
    .nelo-shop .ns-discount{ position:absolute; top:10px; right:10px; z-index:3; background:var(--ns-primary); color:#fff; font-weight:600; font-size:12px; line-height:1; padding:4px 10px; border-radius:12px; }
    .nelo-shop .ns-imagebox{ height:220px; display:flex; align-items:center; justify-content:center; margin-bottom:12px; }
    .nelo-shop .ns-imagebox img{ max-width:100%; max-height:100%; object-fit:contain; display:block; transition:transform .2s ease; }
    .nelo-shop .ns-card:hover .ns-imagebox img{ transform:scale(1.03); }
    .nelo-shop .ns-price{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:8px; }
    .nelo-shop .ns-price-now{ font-size:16px; font-weight:700; color:var(--ns-primary); }
    .nelo-shop .ns-price-old{ font-size:14px; color:#a1a1aa; text-decoration:line-through; }
    .nelo-shop .ns-tags{ display:flex; gap:6px; flex-wrap:wrap; margin-bottom:8px; }
    .nelo-shop .ns-tag{ display:inline-flex; align-items:center; justify-content:center; padding:3px 6px; border-radius:4px; font-size:11px; font-weight:500; }
    .nelo-shop .ns-tag--blue{ background:var(--ns-blue); color:var(--ns-blue-text); }
    .nelo-shop .ns-tag--green{ background:var(--ns-green); color:var(--ns-green-text); }
    .nelo-shop .ns-name{ font-size:14px; line-height:1.4; color:#666666; font-weight:400; margin:0 0 4px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:39px; }
    .nelo-shop .ns-presentation { font-size: 12px; color: var(--ns-blue-text); font-weight: 600; margin-bottom: 8px; }
    .nelo-shop .ns-rating{ display:flex; align-items:center; gap:4px; color:#666666; font-size:13px; }
    .nelo-shop .ns-stars{ display:inline-flex; align-items:center; gap:1px; }
    .nelo-shop .ns-stars svg{ width:14px; height:14px; fill:var(--ns-star); }
    @media (max-width: 1200px){ .nelo-shop .ns-card{ flex-basis:240px; min-width:240px; } .nelo-shop .ns-imagebox{ height:190px; } }
    @media (max-width: 768px){ .nelo-shop{ padding:24px 0; } .nelo-shop .ns-container{ padding:0; } .nelo-shop .ns-head{ padding: 0 16px; } .nelo-shop .ns-title{ font-size:20px; } .nelo-shop .ns-more{ font-size:14px; } .nelo-shop .ns-track{ gap:12px; padding:10px 16px; scroll-snap-type:x mandatory; } .nelo-shop .ns-card{ flex:0 0 160px; min-width:160px; scroll-snap-align:start; } .nelo-shop .ns-imagebox{ height:140px; } .nelo-shop .ns-nav{ display:none; } .nelo-shop .ns-name{ font-size:13px; } }
  </style>

  @php
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

    $catalogProducts = \App\Models\CatalogItem::with('category')->published()->ordered()->take(120)->get();

    $pickPhotoUrl = function($p){
      $candidates = [$p->photo_1 ?? null, $p->photo_2 ?? null, $p->photo_3 ?? null];
      $raw = collect($candidates)->filter(fn($v) => is_string($v) && trim($v) !== '')->first();
      if(!$raw){ return asset('images/placeholder.png'); }
      $raw = trim($raw);
      if (\Illuminate\Support\Str::startsWith($raw, ['http://','https://'])) { return $raw; }
      if (\Illuminate\Support\Str::startsWith($raw, ['storage/'])) { return asset($raw); }
      return \Illuminate\Support\Facades\Storage::url($raw);
    };

    $discountPct = function($p){
      return (!is_null($p->sale_price) && (float)$p->sale_price > 0 && (float)$p->sale_price < (float)$p->price)
        ? max(1, round(100 - (((float)$p->sale_price / (float)$p->price) * 100)))
        : null;
    };

    $productSections = collect();
    $usedIds = [];

    $take = function ($collection, $limit = 18) use (&$usedIds) {
      $picked = $collection->reject(fn($p) => in_array($p->id, $usedIds))->take($limit)->values();
      foreach ($picked as $p) { $usedIds[] = $p->id; }
      return $picked;
    };

    if ($catalogProducts->count()) {
      $featured = $catalogProducts->filter(fn($p) => (bool)($p->is_featured ?? false))->values();
      $paraTi   = $take($featured->count() ? $featured : $catalogProducts, 18);
      if ($paraTi->count()) { $productSections->push(['title' => 'Para ti', 'url' => route('web.catalog.index'), 'items' => $paraTi]); }

      $ofertas = $take($catalogProducts->filter(fn($p) => !is_null($p->sale_price) && (float)$p->sale_price > 0 && (float)$p->sale_price < (float)$p->price)->values(), 18);
      if ($ofertas->count()) { $productSections->push(['title' => 'Ofertas', 'url' => route('web.catalog.index', ['order' => 'price_asc']), 'items' => $ofertas]); }

      $grouped = $catalogProducts->filter(fn($p) => !empty($p->category?->name))->groupBy(fn($p) => trim($p->category->name));
      foreach ($grouped as $categoryName => $items) {
        $picked = $take($items->values(), 18);
        if ($picked->count()) { $productSections->push(['title' => $categoryName, 'url' => route('web.catalog.index', ['s' => $categoryName]), 'items' => $picked]); }
      }

      $restTitles = ['Otros', 'Más productos', 'También te puede interesar', 'Explora más', 'Recomendados'];
      foreach ($restTitles as $restTitle) {
        $picked = $take($catalogProducts, 18);
        if (!$picked->count()) break; 
        $productSections->push(['title' => $restTitle, 'url' => route('web.catalog.index'), 'items' => $picked]);
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
                  <svg viewBox="0 0 24 24" fill="none"><path d="M9 6l6 6-6 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
              </div>
              <div class="ns-slider-wrap">
                <button class="ns-nav ns-nav--prev" type="button" data-ns-prev="ns-track-{{ $sectionIndex }}" aria-label="Anterior">
                  <svg viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div class="ns-track" id="ns-track-{{ $sectionIndex }}">
                  @foreach($section['items'] as $p)
                    @php
                      $img = $pickPhotoUrl($p);
                      $hasSale = !is_null($p->sale_price) && (float)$p->sale_price > 0 && (float)$p->sale_price < (float)$p->price;
                      $discount = $discountPct($p);
                      $presentation = $getPresentation($p);
                      $ratingCount = 0;
                      if (isset($p->reviews_count) && (int)$p->reviews_count > 0) { $ratingCount = (int) $p->reviews_count; } 
                      elseif (isset($p->rating_count) && (int)$p->rating_count > 0) { $ratingCount = (int) $p->rating_count; }
                    @endphp
                    <a href="{{ route('web.catalog.show', $p) }}" class="ns-card" aria-label="Ver {{ $p->name }}">
                      @if($discount) <span class="ns-discount">-{{ $discount }}%</span> @endif
                      <div class="ns-imagebox">
                        <img src="{{ $img }}" alt="{{ $p->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
                      </div>
                      <div class="ns-price">
                        <span class="ns-price-now">${{ number_format($hasSale ? $p->sale_price : $p->price, 2) }}</span>
                        @if($hasSale) <span class="ns-price-old">${{ number_format($p->price, 2) }}</span> @endif
                      </div>
                      <div class="ns-tags">
                        <span class="ns-tag ns-tag--blue">Sin intereses</span>
                        @if($hasSale && $discount >= 20) <span class="ns-tag ns-tag--green">Envío gratis</span> @endif
                      </div>
                      <h3 class="ns-name">{{ $p->name }}</h3>
                      <div class="ns-presentation">{{ $presentation }}</div>
                      <div class="ns-rating">
                        <span class="ns-stars" aria-hidden="true">
                          <svg viewBox="0 0 24 24"><path d="M12 3.7l2.6 5.28 5.83.85-4.22 4.11 1 5.8L12 16.97 6.79 19.74l1-5.8-4.22-4.11 5.83-.85L12 3.7z"/></svg>
                          <svg viewBox="0 0 24 24"><path d="M12 3.7l2.6 5.28 5.83.85-4.22 4.11 1 5.8L12 16.97 6.79 19.74l1-5.8-4.22-4.11 5.83-.85L12 3.7z"/></svg>
                          <svg viewBox="0 0 24 24"><path d="M12 3.7l2.6 5.28 5.83.85-4.22 4.11 1 5.8L12 16.97 6.79 19.74l1-5.8-4.22-4.11 5.83-.85L12 3.7z"/></svg>
                          <svg viewBox="0 0 24 24"><path d="M12 3.7l2.6 5.28 5.83.85-4.22 4.11 1 5.8L12 16.97 6.79 19.74l1-5.8-4.22-4.11 5.83-.85L12 3.7z"/></svg>
                          <svg viewBox="0 0 24 24"><path d="M12 3.7l2.6 5.28 5.83.85-4.22 4.11 1 5.8L12 16.97 6.79 19.74l1-5.8-4.22-4.11 5.83-.85L12 3.7z"/></svg>
                        </span>
                        @if($ratingCount > 0) <span class="ns-count">({{ number_format($ratingCount) }})</span> @endif
                      </div>
                    </a>
                  @endforeach
                </div>
                <button class="ns-nav ns-nav--next" type="button" data-ns-next="ns-track-{{ $sectionIndex }}" aria-label="Siguiente">
                  <svg viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
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

  {{-- ===== EL RESTO DE TUS SCRIPTS (stc-wrap y Comentarios Apple) SIGUE INTACTO ABAJO ===== --}}
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
      const wrap  = track?.parentElement;
      if(!track) return;
      const cards = Array.from(track.children);
      const prev  = document.getElementById('stc-prev');
      const next  = document.getElementById('stc-next');
      const dotsBox = document.getElementById('stc-dots');
      cards.forEach((_, i) => { const d = document.createElement('span'); d.className = 'stc-dot'; d.addEventListener('click', () => activate(i, true)); dotsBox.appendChild(d); });
      const dots = Array.from(dotsBox.children);
      const isMobile = () => matchMedia('(max-width:767px)').matches;
      let current = 0;
      function center(i){ const card = cards[i]; const axis = isMobile() ? 'top' : 'left'; const size = isMobile() ? 'clientHeight' : 'clientWidth'; const start = isMobile() ? card.offsetTop : card.offsetLeft; wrap.scrollTo({ [axis]: start - (wrap[size]/2 - card[size]/2), behavior:'smooth' }); }
      function toggleUI(i){ cards.forEach((c,k) => c.toggleAttribute('active', k === i)); dots.forEach((d,k) => d.classList.toggle('active', k === i)); if(prev) prev.disabled = (i === 0); if(next) next.disabled = (i === cards.length - 1); }
      function activate(i, scroll){ if(i === current) return; current = i; toggleUI(i); if(scroll) center(i); }
      function go(step){ activate(Math.min(Math.max(current + step, 0), cards.length - 1), true); }
      if(prev) prev.addEventListener('click', () => go(-1));
      if(next) next.addEventListener('click', () => go(1));
      cards.forEach((card, i) => { card.addEventListener('mouseenter', () => matchMedia('(hover:hover)').matches && activate(i, true)); card.addEventListener('click', () => activate(i, true)); });
      let sx=0, sy=0;
      track.addEventListener('touchstart', e => { sx=e.touches[0].clientX; sy=e.touches[0].clientY; }, {passive:true});
      track.addEventListener('touchend', e => { const dx = e.changedTouches[0].clientX - sx; const dy = e.changedTouches[0].clientY - sy; const dist = isMobile()? Math.abs(dy) : Math.abs(dx); if(dist > 60) go((isMobile()? dy : dx) > 0 ? -1 : 1); }, {passive:true});
      addEventListener('keydown', e => { if(['ArrowRight','ArrowDown'].includes(e.key)) go(1); if(['ArrowLeft','ArrowUp'].includes(e.key)) go(-1); }, {passive:true});
      addEventListener('resize', () => center(current));
      toggleUI(0); center(0);
    })();
  </script>

  <script>
    (function(){
      const obs = 'IntersectionObserver' in window ? new IntersectionObserver((entries, io)=>{ entries.forEach(e=>{ if(e.isIntersecting){ e.target.classList.add('in'); io.unobserve(e.target); } }); }, {rootMargin:'0px 0px -10% 0px'}) : null;
      document.querySelectorAll('.lp-card.ao').forEach(el=>{ if(obs) obs.observe(el); else el.classList.add('in'); });
    })();
  </script>

  @if(isset($marqueeComments) && $marqueeComments->count())
    <section id="home-cmt-marquee">
      <style>
        #home-cmt-marquee{
          --cmt-bg-1:#fbfbfd; 
          --cmt-bg-2:#f5f5f7; 
          --cmt-card:#ffffff;
          --cmt-border:#ededf2;
          --cmt-ink:#1d1d1f; 
          --cmt-sub:#6e6e73; 
          --cmt-body:#3a3a3c;
          --cmt-accent:#0071e3;
          --cmt-accent-2:#00c2c7;
          --cmt-pill-bg:#f0f4ff;
          --cmt-pill-ink:#0071e3;
          --cmt-speed:60s; 
          position:relative; left:50%; transform:translateX(-50%); width:100vw; max-width:100vw; margin-top:40px; margin-left:0; margin-right:0; background:linear-gradient(180deg, var(--cmt-bg-1), var(--cmt-bg-2)); padding:72px 0 84px; overflow:hidden; font-family:-apple-system, BlinkMacSystemFont, "Segoe UI", "Poppins", Roboto, sans-serif; -webkit-font-smoothing:antialiased;
        }
        #home-cmt-marquee .wrap{ max-width:760px; margin:0 auto 52px; padding:0 24px; text-align:center; }
        #home-cmt-marquee header h2{ margin:0 0 14px; font-weight:700; letter-spacing:-.02em; color:var(--cmt-ink); font-size:clamp(30px,5vw,52px); line-height:1.08; }
        #home-cmt-marquee header h2 .grad{ background:linear-gradient(90deg, var(--cmt-accent), var(--cmt-accent-2)); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; }
        #home-cmt-marquee header .lead{ margin:0 auto 26px; color:var(--cmt-sub); font-size:clamp(16px,2.2vw,20px); line-height:1.5; max-width:600px; font-weight:400; }
        #home-cmt-marquee .header-actions{ display:flex; gap:12px; justify-content:center; align-items:center; flex-wrap:wrap; }
        #home-cmt-marquee .tagline{ display:inline-flex; align-items:center; gap:8px; background:rgba(255,255,255,.7); backdrop-filter:blur(10px); border:1px solid var(--cmt-border); color:var(--cmt-ink); font-weight:500; font-size:13px; padding:8px 15px; border-radius:999px; }
        #home-cmt-marquee .tagline .dot{ width:8px; height:8px; border-radius:50%; background:#34c759; box-shadow:0 0 0 4px rgba(52,199,89,.16); }
        #home-cmt-marquee .btn-ghost{ display:inline-flex; align-items:center; background:var(--cmt-accent); color:#fff !important; font-weight:500; font-size:14px; text-decoration:none; padding:10px 20px; border-radius:999px; border:0; transition:transform .18s ease, box-shadow .18s ease, background .18s ease; box-shadow:0 6px 18px rgba(0,113,227,.28); }
        #home-cmt-marquee .btn-ghost:hover{ background:#0077ed; transform:translateY(-1px); box-shadow:0 8px 22px rgba(0,113,227,.34); }
        #home-cmt-marquee .rows{ display:flex; flex-direction:column; gap:24px; }
        #home-cmt-marquee .row{ position:relative; overflow:hidden; }
        #home-cmt-marquee .row .track{ display:flex; flex-wrap:nowrap; width:max-content; gap:20px; will-change:transform; }
        #home-cmt-marquee .row-1 .track{ animation:cmt-left var(--cmt-speed) linear infinite; }
        #home-cmt-marquee .row-2 .track{ animation:cmt-right var(--cmt-speed) linear infinite; }
        #home-cmt-marquee .row:hover .track{ animation-play-state:paused; }
        @keyframes cmt-left  { from{transform:translateX(0)}     to{transform:translateX(-50%)} }
        @keyframes cmt-right { from{transform:translateX(-50%)}  to{transform:translateX(0)} }
        #home-cmt-marquee .row::before, #home-cmt-marquee .row::after{ content:""; position:absolute; top:0; bottom:0; width:120px; z-index:3; pointer-events:none; }
        #home-cmt-marquee .row::before{ left:0;  background:linear-gradient(90deg, var(--cmt-bg-2), transparent); }
        #home-cmt-marquee .row::after { right:0; background:linear-gradient(270deg, var(--cmt-bg-2), transparent); }
        #home-cmt-marquee .card{ flex:0 0 320px; width:320px; background:var(--cmt-card); border:1px solid var(--cmt-border); border-radius:22px; padding:24px; display:flex; flex-direction:column; gap:16px; box-shadow:0 2px 10px rgba(0,0,0,.03); transition:transform .25s cubic-bezier(.2,.7,.2,1), box-shadow .25s ease; }
        #home-cmt-marquee .card:hover{ transform:translateY(-5px); box-shadow:0 18px 40px rgba(0,0,0,.10); }
        #home-cmt-marquee .card-head{ display:flex; align-items:center; gap:12px; }
        #home-cmt-marquee .avatar{ width:46px; height:46px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:15px; color:#fff; flex:0 0 auto; letter-spacing:.02em; }
        #home-cmt-marquee .avatar.c0{ background:linear-gradient(135deg,#0071e3,#42a5ff); }
        #home-cmt-marquee .avatar.c1{ background:linear-gradient(135deg,#00c2c7,#34d2b4); }
        #home-cmt-marquee .avatar.c2{ background:linear-gradient(135deg,#ff9f0a,#ff6b3d); }
        #home-cmt-marquee .avatar.c3{ background:linear-gradient(135deg,#bf5af2,#ff4f9a); }
        #home-cmt-marquee .meta .name{ font-weight:600; color:var(--cmt-ink); font-size:15px; line-height:1.2; }
        #home-cmt-marquee .meta .user{ color:var(--cmt-sub); font-size:13px; margin-top:2px; }
        #home-cmt-marquee .body{ color:var(--cmt-body); font-size:15px; line-height:1.6; letter-spacing:-.01em; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
        #home-cmt-marquee .pill{ display:inline-flex; align-items:center; gap:6px; align-self:flex-start; background:var(--cmt-pill-bg); color:var(--cmt-pill-ink); font-weight:500; font-size:12.5px; padding:7px 13px; border-radius:999px; }
        @media (prefers-reduced-motion: reduce){ #home-cmt-marquee .row .track{ animation:none; } }
        @media (max-width:768px){ #home-cmt-marquee{ padding:52px 0 60px; } #home-cmt-marquee .wrap{ margin-bottom:36px; } #home-cmt-marquee .card{ flex-basis:280px; width:280px; padding:20px; border-radius:18px; } #home-cmt-marquee .row::before, #home-cmt-marquee .row::after{ width:56px; } }
      </style>
      <div class="wrap">
        <header>
          <h2>Lo que <span class="grad">dicen de nosotros</span></h2>
          <p class="lead">Comentarios reales de clientes que ya compran con Jureto.</p>
          <div class="header-actions"></div>
        </header>
      </div>
      @php
        $items = $marqueeComments->unique('id')->values();
        $count = $items->count();
        $half  = (int) ceil($count / 2);
        $row1  = $items->slice(0, $half)->values();
        $row2  = $items->slice($half)->values();
        $cardWidth    = 296;
        $virtualWidth = 2600;
        $seg1 = max(1, $row1->count()) * $cardWidth; $rep1 = (int) ceil($virtualWidth / $seg1); if ($rep1 < 2) $rep1 = 2; if ($rep1 % 2 === 1) $rep1++;
        $seg2 = max(1, $row2->count()) * $cardWidth; $rep2 = (int) ceil($virtualWidth / $seg2); if ($rep2 < 2) $rep2 = 2; if ($rep2 % 2 === 1) $rep2++;
        $colorIndex1 = 0; $colorIndex2 = 0;
        $badges = [ ['icon' => '📦', 'text' => 'Entrega rápida'], ['icon' => '💳', 'text' => 'Pago seguro'], ['icon' => '🧾', 'text' => 'Factura CFDI 4.0'], ['icon' => '📞', 'text' => 'Atención personalizada'], ['icon' => '🏫', 'text' => 'Ideal para escuelas'], ['icon' => '🏢', 'text' => 'Soluciones corporativas'], ['icon' => '💼', 'text' => 'Proveedores para empresas'], ['icon' => '🎯', 'text' => 'Pedidos a la medida'], ['icon' => '🛠️', 'text' => 'Soporte postventa'], ['icon' => '🚚', 'text' => 'Envío a todo México'], ['icon' => '💰', 'text' => 'Precios de mayoreo'], ['icon' => '⭐', 'text' => 'Clientes recurrentes'], ['icon' => '🕒', 'text' => 'Respuesta rápida'], ['icon' => '📚', 'text' => 'Papelería completa'], ['icon' => '🧃', 'text' => 'Kits armados para oficina'] ];
        $badgeIndex1 = 0; $badgeIndex2 = 0;
      @endphp
      <div class="rows">
        <div class="row row-1">
          <div class="track">
            @for($r = 0; $r < $rep1; $r++)
              @foreach($row1 as $comment)
                @php
                  $name = $comment->nombre ?? ($comment->user->name ?? ($comment->user->email ?? 'Cliente'));
                  $parts = preg_split('/\s+/', trim($name));
                  $initials = '';
                  if ($parts) { foreach (array_slice($parts, 0, 2) as $p) { $initials .= mb_strtoupper(mb_substr($p, 0, 1)); } }
                  $email = $comment->email ?? optional($comment->user)->email;
                  $username = $email ? '@'.\Illuminate\Support\Str::before($email, '@') : '@cliente';
                  $body = \Illuminate\Support\Str::limit($comment->contenido, 140);
                  $colorClass = 'c'.($colorIndex1 % 4); $colorIndex1++;
                  $badge = $badges[$badgeIndex1 % count($badges)]; $badgeIndex1++;
                @endphp
                <article class="card">
                  <div class="card-head">
                    <div class="avatar {{ $colorClass }}">{{ $initials ?: 'CL' }}</div>
                    <div class="meta"><div class="name">{{ $name }}</div><div class="user">{{ $username }}</div></div>
                  </div>
                  <div class="body">“{{ $body }}”</div>
                  <div class="pill"><span>{{ $badge['icon'] }}</span><span>{{ $badge['text'] }}</span></div>
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
                    $name = $comment->nombre ?? ($comment->user->name ?? ($comment->user->email ?? 'Cliente'));
                    $parts = preg_split('/\s+/', trim($name));
                    $initials = '';
                    if ($parts) { foreach (array_slice($parts, 0, 2) as $p) { $initials .= mb_strtoupper(mb_substr($p, 0, 1)); } }
                    $email = $comment->email ?? optional($comment->user)->email;
                    $username = $email ? '@'.\Illuminate\Support\Str::before($email, '@') : '@cliente';
                    $body = \Illuminate\Support\Str::limit($comment->contenido, 140);
                    $colorClass = 'c'.($colorIndex2 % 4); $colorIndex2++;
                    $badge = $badges[$badgeIndex2 % count($badges)]; $badgeIndex2++;
                  @endphp
                  <article class="card">
                    <div class="card-head">
                      <div class="avatar {{ $colorClass }}">{{ $initials ?: 'CL' }}</div>
                      <div class="meta"><div class="name">{{ $name }}</div><div class="user">{{ $username }}</div></div>
                    </div>
                    <div class="body">“{{ $body }}”</div>
                    <div class="pill"><span>{{ $badge['icon'] }}</span><span>{{ $badge['text'] }}</span></div>
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