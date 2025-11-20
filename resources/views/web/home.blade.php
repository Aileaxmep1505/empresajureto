{{-- resources/views/web/home.blade.php (completo con slider 3D + cards con círculo) --}}
@extends('layouts.web') 
@section('title','Inicio')

@section('content')

  {{-- ====== Fuentes + GSAP para el slider 3D ====== --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300..700" />


  {{-- ====== Estilos locales (lo que ya tenías) ====== --}}
  <style>
    :root{
      --ink:#0e1726; --muted:#6b7280; --bg:#f7fafc;
      --brand:#6ea8fe; --brand-ink:#1d4ed8; --accent:#9ae6b4;
      --surface:#ffffff; --line:#e8eef6; --chip:#eef2ff; --chip-ink:#3730a3;
      --shadow:0 12px 30px rgba(13, 23, 38, .06);
      --danger:#ef4444; --success:#16a34a; --warn:#eab308;
    }

    .container{max-width:1100px;margin-inline:auto;padding-inline:16px}

    .hero{
      position: relative; padding: clamp(36px, 4vw, 64px) 0;
      border-radius: 20px; background: linear-gradient(180deg, #f5f9ff, #f9fffb);
      overflow: hidden; box-shadow: var(--shadow);
    }
    .hero:before{
      content:""; position:absolute; inset:-20% -10% auto auto;
      width:420px; height:420px; border-radius:50%;
      background: radial-gradient(closest-side, rgba(110,168,254,.18), transparent);
      filter: blur(6px);
    }
    .hero h1{ color: var(--ink); font-weight: 800; letter-spacing: -.02em;
      font-size: clamp(28px, 4vw, 46px); margin: 0 0 10px; }
    .hero p{ color: var(--muted); font-size: clamp(14px, 1.6vw, 18px); max-width: 720px; margin: 0; }

    /* Botones base */
    .btn{ display:inline-flex; align-items:center; justify-content:center; gap:10px;
      border:0; cursor:pointer; text-decoration:none; font-weight:700; border-radius:14px; padding:12px 18px;
      transition: transform .12s ease, box-shadow .2s ease, background .2s ease, color .2s ease;
      box-shadow: 0 8px 18px rgba(29,78,216,.12); }
    .btn-primary{ background: var(--brand); color: #0b1220; }
    .btn-primary:hover{ background:#84b7ff; transform: translateY(-1px); }
    .btn-ghost{ background:#fff; color:var(--ink); border:1px solid var(--line); box-shadow:none; }
    .btn-ghost:hover{ background:#f8fafc; transform: translateY(-1px); }

    /* Grid utilitario */
    .grid{ display:grid; gap:16px; grid-template-columns: repeat(12, 1fr); }
    .col-4{ grid-column: span 4 / span 4; }
    .col-3{ grid-column: span 3 / span 3; }
    @media (max-width: 1100px){ .col-3{ grid-column: span 4 / span 4; } }
    @media (max-width: 900px){ .col-4{ grid-column: span 12 / span 12; } }
    @media (max-width: 780px){ .col-3{ grid-column: span 6 / span 6; } }
    @media (max-width: 520px){ .col-3{ grid-column: span 12 / span 12; } }

    /* Tarjetas de valores */
    .feature{ background: var(--surface); border:1px solid var(--line); border-radius:16px; padding:18px;
      box-shadow: var(--shadow); display:flex; align-items:center; gap:12px; min-height:88px; }
    .feature__chip{ padding:6px 10px; border-radius:999px; background:var(--chip); color:var(--chip-ink);
      font-weight:700; font-size:12px; letter-spacing:.02em; }
    .feature__title{ color:var(--ink); font-weight:800; }
    .feature__desc{ color:var(--muted); margin:2px 0 0; font-size:.95rem; }

    /* ====== SLIDER INFINITO DE MARCAS ====== */
    .full-bleed{ width:100vw; margin-left:calc(50% - 50vw); margin-right:calc(50% - 50vw); }
    .brands-marquee{ position:relative; width:100vw; overflow:hidden; padding:14px 0; background:transparent; }
    .brands-track{ display:flex; align-items:center; width:max-content; gap:clamp(36px, 6vw, 72px);
      animation: brands-scroll 30s linear infinite; }
    @keyframes brands-scroll { from{transform:translateX(0)} to{transform:translateX(-50%)} }
    .brands-marquee:hover .brands-track,
    .brands-marquee:focus-within .brands-track { animation-play-state: paused; }
    @media (prefers-reduced-motion: reduce){ .brands-track{ animation:none; } }
    .brand-item{ display:flex; align-items:center; justify-content:center;
      min-width:clamp(90px, 10vw, 140px); height:clamp(36px, 4.2vw, 50px);
      opacity:.85; filter:grayscale(100%) contrast(105%); transition:opacity .2s, filter .2s, transform .2s; }
    .brand-item:hover{ opacity:1; filter:grayscale(0%); transform:translateY(-1px); }
    .brand-item img{ max-height:100%; max-width:140px; object-fit:contain; display:block; }

 </style>

  
  {{-- ====== /GRID con cards de círculo ====== --}}

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
      <h2 class="section-title reveal">Papelería a Mayoreo y Menudeo</h2>
      <p class="section-desc reveal delay-1">
        Factura al instante, envíos rápidos y seguros, y surtido inteligente. Todo en un solo lugar.
      </p>

      <div class="swiper steps reveal delay-2">
        <div class="swiper-wrapper">
          <!-- 1) Factura al instante -->
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

          <!-- 2) Envíos rápidos y seguros -->
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

          <!-- 3) Surtido inteligente -->
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

<style>
  /* ===== SCOPE: #morning-steps (Base44 + reveal 1 vez) ===== */
  #morning-steps *{ box-sizing:border-box }
  #morning-steps h1,#morning-steps h2,#morning-steps h3,#morning-steps p{ margin:0 }

  #morning-steps{
    --ink:#0e1726; --muted:#5b6b7a;
    --sky-top:#cde9f4; --sky-mid:#e6f1f5; --sky-bot:#f6f8f9;
    --radial-a: color-mix(in oklab, #a7d3e7 18%, transparent);
    --radial-b: color-mix(in oklab, #d9d7ff 14%, transparent);
    font-family:"Inter", system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
    color:var(--ink);
  }

  /* Full-bleed centrado */
  #morning-steps .section{
    width:100vw;
    margin-left:calc(50% - 50vw);
    margin-right:calc(50% - 50vw);
    padding:56px clamp(16px,5vw,56px);
    text-align:center;
    position:relative; overflow:hidden;
    background:
      radial-gradient(1200px 600px at 50% -10%, var(--radial-a) 0%, transparent 70%),
      radial-gradient(1000px 520px at 80% 90%, var(--radial-b) 0%, transparent 70%),
      linear-gradient(180deg, var(--sky-top) 0%, var(--sky-mid) 45%, var(--sky-bot) 100%);
  }
  #morning-steps .section::before{
    content:""; position:absolute; inset:0; pointer-events:none; opacity:.05;
    background-image: repeating-linear-gradient(0deg, rgba(0,0,0,.02) 0, rgba(0,0,0,.02) 1px, transparent 1px, transparent 2px);
    mask-image: linear-gradient(180deg, #000 0%, #000 70%, transparent 100%);
  }

  #morning-steps .wrapper{ width:100%; min-height:100vh; display:flex; align-items:center; }

  /* Tipos */
  #morning-steps .section-title{ font-size:clamp(1.9rem, 4.5vw, 3.2rem); line-height:1.2; font-weight:800; margin-bottom:12px; }
  #morning-steps .section-desc{ color:var(--muted); font-size:clamp(1rem,1.3vw,1.125rem); line-height:1.65; margin-bottom:40px; max-width:980px; margin-inline:auto; }

  /* Swiper */
  #morning-steps .steps{ width:100%; }
  #morning-steps .swiper-wrapper{ align-items:stretch; }
  #morning-steps .swiper-slide{ height:auto; display:flex; }

  /* Tarjetas */
  #morning-steps .card-wrapper{ position:relative; height:100%; width:100%; }
  #morning-steps .card{
    --r:30px; --s:40px;
    background:#fff; padding:clamp(18px,2vw,24px);
    width:100%; text-align:left; border-radius:30px; height:100%;
    box-shadow: 0 12px 36px rgba(2, 8, 23, 0.06);
    clip-path: shape(
      from 0 0,
      hline to calc(100% - var(--s) - 2 * var(--r)),
      arc by var(--r) var(--r) of var(--r) cw,
      arc by var(--s) var(--s) of var(--s),
      arc by var(--r) var(--r) of var(--r) cw,
      vline to 100%,
      hline to 0
    );
  }
  #morning-steps .card-circle{
    width:60px;height:60px;background:#fff;position:absolute;top:0;right:0;
    border-radius:50%;font-size:1.5rem;display:flex;justify-content:center;align-items:center;font-weight:700;
    box-shadow: 0 6px 20px rgba(2, 8, 23, 0.08);
  }
  #morning-steps .card-title{ font-size:clamp(1.05rem,1.4vw,1.25rem);font-weight:700;margin-bottom:10px;padding-right:70px; }
  #morning-steps .card-desc{ font-size:.95rem;line-height:1.6;color:#475569;margin-bottom:14px;padding-right:65px; }

  /* Media */
  #morning-steps .card-figure{ height:clamp(180px,24vw,240px); background:#eef2f7; border-radius:20px; position:relative; overflow:hidden; }
  #morning-steps .card-video{ width:100%; height:100%; object-fit:cover; display:block; background:#000; }

  #morning-steps .swiper-pagination{ position:static; margin-top:24px; }

  /* Reveal una vez (suave) */
  @keyframes fadeUpOnce{ from{opacity:0; transform: translateY(12px) scale(.995);} to{opacity:1; transform: translateY(0) scale(1);} }
  #morning-steps .reveal{ opacity:0; transform: translateY(8px) scale(.997); }
  #morning-steps .reveal.played{ opacity:1; transform:none; animation:none; }
  #morning-steps .reveal.in-view{ animation: fadeUpOnce .5s cubic-bezier(.22,.8,.2,1) forwards; }
  #morning-steps .delay-1.in-view{ animation-delay:.06s; }
  #morning-steps .delay-2.in-view{ animation-delay:.12s; }

  @media (prefers-reduced-motion: reduce){
    #morning-steps .reveal{ opacity:1 !important; transform:none !important; animation:none !important; }
  }

  @supports not (background: color-mix(in oklab, #fff 50%, #000 50%)) {
    #morning-steps{ --radial-a: rgba(141,192,216,.18); --radial-b: rgba(198,195,255,.14); }
  }

  #morning-steps .no-support{
    position:fixed; inset:0; background:#000d; display:grid; place-items:center; align-content:center; gap:1em;
    z-index:9999; color:#fff; text-align:center; padding:24px;
  }
  @supports (clip-path: shape(from 0 0, move to 0 0)) { #morning-steps .no-support{ display:none; } }
</style>

<script>
  // ===== Swiper =====
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

  // ===== Robust autoplay muted & loop (siempre activos) =====
  function forcePlay(v){
    if (!v) return;
    v.muted = true; // clave para autoplay en móviles
    const tryPlay = () => v.play().catch(()=>{ /* reintenta luego */ });
    if (v.readyState >= 2) { tryPlay(); }
    else {
      v.addEventListener('canplay', tryPlay, { once:true });
      // fallback si nunca dispara canplay
      setTimeout(tryPlay, 700);
    }
  }

  function ensureAllPlaying(){
    document.querySelectorAll('#morning-steps .card-video').forEach(v => forcePlay(v));
  }

  // Reintentos extra: al cargar, al recuperar foco y visibilidad
  window.addEventListener('load', ensureAllPlaying);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) ensureAllPlaying(); });
  window.addEventListener('focus', ensureAllPlaying);

  // Pequeño retry inicial (por si el navegador bloquea el 1er intento)
  let retries = 0;
  const retryTimer = setInterval(()=>{
    ensureAllPlaying();
    if (++retries >= 4) clearInterval(retryTimer);
  }, 1200);

  // ===== Reveal una sola vez =====
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

{{-- ===================== PRODUCT CARDS (Novedades & Ofertas) + ESQUINA REVELABLE ===================== --}}
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400&display=swap"/>

<style>
/* === NAMESPACE: .pcards === */
.pcards{
  --ink:#0f172a; --muted:#64748b; --line:#e5e7eb; --ok:#16a34a;
  --shadow:0 14px 32px rgba(2,6,23,.08);
  --chip-new:rgba(59,130,246,.18); --chip-new-ink:#1d4ed8;
  --chip-sale:rgba(34,197,94,.18);  --chip-sale-ink:#166534;
  --chip-off:rgba(234,179,8,.18);   --chip-off-ink:#854d0e;
}
.pcards *{box-sizing:border-box}
.pcards h1,.pcards h2,.pcards h3,.pcards h4,.pcards p{margin:0}

/* Wrap + Head */
.pcards .pcards-wrap{width:100vw;margin-left:calc(50% - 50vw);margin-right:calc(50% - 50vw);padding:20px 14px;background:#fff}
.pcards .pcards-head{max-width:1400px;margin:auto;display:flex;justify-content:space-between;align-items:end;gap:10px}
.pcards .pcards-head h2{color:var(--ink);font-weight:900;font-size:clamp(22px,3vw,32px)}

/* Grid */
.pcards .pcards-grid{max-width:1400px;margin:12px auto 6px;display:grid;gap:16px;grid-template-columns:repeat(12,1fr)}
.pcards .pc-col{grid-column: span 3 / span 3}
@media (max-width:1100px){ .pcards .pc-col{grid-column: span 4 / span 4} }
@media (max-width:780px){  .pcards .pc-col{grid-column: span 6 / span 6} }
@media (max-width:520px){  .pcards .pc-col{grid-column: span 12 / span 12} }

/* Card */
.pcards .pcard{
  position:relative; width:100%; height:360px; border-radius:16px; overflow:hidden;
  border:1px solid var(--line); background:#fff; box-shadow:var(--shadow); display:flex; flex-direction:column;
}

/* TOP (imagen) */
.pcards .pc-top{position:relative; flex:1 1 auto; min-height:72%; background:#f6f8fc; overflow:hidden}
.pcards .pc-top img{position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:block}
/* Si NO quieres la “mordida” blanca inferior, la quitamos: */
.pcards .pc-top::after{ content:none !important; }

/* Badges */
.pcards .pc-badges{position:absolute; top:10px; left:10px; display:flex; gap:6px; z-index:3}
.pcards .pc-badge{font-size:.72rem; font-weight:800; padding:.35rem .55rem; border-radius:999px; backdrop-filter:saturate(1.3) blur(4px); border:1px solid rgba(255,255,255,.55)}
.pcards .pc-new{  background:var(--chip-new);  color:var(--chip-new-ink) }
.pcards .pc-sale{ background:var(--chip-sale); color:var(--chip-sale-ink)}
.pcards .pc-off{  background:var(--chip-off);  color:var(--chip-off-ink) }

/* ====== ESQUINA REVELABLE (arriba derecha) ====== */
.pcards .pc-corner{
  position:absolute; top:-70px; right:-70px; width:140px; height:140px; background:#92879B;
  border-radius:0 0 200px 200px; z-index:4;
  transition: all .45s ease, border-radius 1.2s ease, top .7s ease;
  overflow:hidden;
}
.pcards .pc-corner__icon{ position:absolute; right:85px; top:85px; color:#fff; opacity:1; pointer-events:none; }
.pcards .pc-corner__panel{
  position:absolute; inset:0; padding:12px 14px 14px; color:#fff; opacity:0; transform:translateY(-40%);
  transition:opacity .25s ease, transform .5s cubic-bezier(.2,.7,.2,1);
  display:flex; flex-direction:column; gap:6px;
}
.pcards .pc-corner__panel h4{font-size:1rem;font-weight:800}
.pcards .pc-corner__list{font-size:.85rem; line-height:1.35; margin:0; padding-left:1rem}
.pcards .pc-corner__close{
  position:absolute; top:8px; right:8px; background:rgba(255,255,255,.18); border:0; color:#fff; border-radius:50%;
  width:28px; height:28px; display:flex; align-items:center; justify-content:center; cursor:pointer;
}

/* hover/focus abre */
.pcards .pcard:hover .pc-corner,
.pcards .pcard:focus-within .pc-corner,
.pcards .pc-corner.is-open{ top:0; right:0; width:100%; height:78%; border-radius:0; }
.pcards .pcard:hover .pc-corner__icon,
.pcards .pcard:focus-within .pc-corner__icon,
.pcards .pc-corner.is-open .pc-corner__icon{ opacity:0; right:15px; top:15px }
.pcards .pcard:hover .pc-corner__panel,
.pcards .pcard:focus-within .pc-corner__panel,
.pcards .pc-corner.is-open .pc-corner__panel{ opacity:1; transform:translateY(0) }

/* BOTTOM (slide) */
.pcards .pc-bottom{position:relative; height:28%; background:#fff; border-top:1px solid var(--line); width:200%; transition:transform .45s cubic-bezier(.2,.7,.2,1)}
.pcards .pc-bottom.is-added{ transform: translateX(-50%) }
.pcards .pc-left,.pcards .pc-right{position:relative; width:50%; height:100%; float:left; display:flex}
.pcards .pc-left{ background:#f8fafc }
.pcards .pc-right{ background:#dcfce7; color:#14532d }

/* Left content */
.pcards .pc-details{padding:10px; flex:1 1 auto; display:flex; flex-direction:column; gap:4px}
.pcards .pc-name{font-weight:900; color:var(--ink); font-size:.98rem; line-height:1.25; display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden}
.pcards .pc-desc{font-size:.86rem; color:#475569; line-height:1.35; display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden}
.pcards .pc-price{margin-top:.25rem; display:flex; align-items:baseline; gap:6px}
.pcards .pc-price .now{font-weight:900; color:var(--ok)}
.pcards .pc-price .old{color:#94a3b8; text-decoration:line-through; font-weight:700; font-size:.9rem}

/* Botón Agregar */
.pcards .pc-buy{width:30%; min-width:76px; border-left:1px solid var(--line); display:flex; align-items:center; justify-content:center; background:#fff}
.pcards .pc-btn{border:0; background:#0f172a; color:#fff; width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 10px 24px rgba(2,6,23,.18); transition:transform .2s ease}
.pcards .pc-btn:active{ transform: scale(.96) }
.pcards .material-symbols-outlined{ font-size:26px; line-height:1 }

/* Right (added) */
.pcards .pc-done,.pcards .pc-remove{width:30%; min-width:86px; display:flex; align-items:center; justify-content:center}
.pcards .pc-done{ border-right:1px solid rgba(20,83,45,.22) }
.pcards .pc-remove{ background:#fecaca; color:#7f1d1d; border:0; cursor:pointer }
.pcards .pc-remove:hover{ background:#fca5a5 }
.pcards .pc-right .pc-details{padding:14px; flex:1 1 auto; display:flex; flex-direction:column; justify-content:center; gap:6px}
.pcards .pc-right .pc-title{font-weight:900; font-size:1rem}
.pcards .pc-right .pc-msg{font-size:.92rem}

/* Responsive tweaks */
@media (max-width:640px){
  .pcards .pcard{height:520px}
  .pcards .pc-desc{-webkit-line-clamp:2}
  .pcards .pcard:hover .pc-corner,
  .pcards .pcard:focus-within .pc-corner,
  .pcards .pc-corner.is-open{ height:70% }
}

/* Evita reclics mientras se envía */
.pcards .pc-btn[disabled]{ opacity:.55; cursor:not-allowed; pointer-events:none; }

/* === BLOQUEO ANTI-REAPERTURA DE ESQUINA (hover/focus) === */
.pcards .pcard.corner-closed .pc-corner{
  top:-70px; right:-70px; width:140px; height:140px; border-radius:0 0 200px 200px;
}
.pcards .pcard.corner-closed .pc-corner__icon{ opacity:1; right:85px; top:85px; }
.pcards .pcard.corner-closed .pc-corner__panel{ opacity:0; transform:translateY(-40%); }

/* Mantener cerrada incluso con :hover / :focus-within */
.pcards .pcard.corner-closed:hover .pc-corner,
.pcards .pcard.corner-closed:focus-within .pc-corner{
  top:-70px; right:-70px; width:140px; height:140px; border-radius:0 0 200px 200px;
}

</style>

@php
  // Colecciones
  $novedades = \App\Models\CatalogItem::published()
                ->when(\Schema::hasColumn('catalog_items','published_at'),
                       fn($q) => $q->orderByDesc('published_at'))
                ->ordered()->take(12)->get();

  $ofertas = \App\Models\CatalogItem::published()
              ->whereNotNull('sale_price')
              ->whereColumn('sale_price','<','price')
              ->ordered()->take(12)->get();

  $isNew = fn($i) => ($i->published_at ?? null) && \Illuminate\Support\Carbon::parse($i->published_at)->gte(now()->subDays(30));
  $discountPct = function($i){
    return (!is_null($i->sale_price) && $i->price && $i->sale_price < $i->price)
      ? max(1, round(100 - (($i->sale_price/$i->price)*100)))
      : null;
  };
@endphp

@if($novedades->count() || $ofertas->count())
<section class="pcards">
  <div class="pcards-wrap">

    {{-- ===== Novedades ===== --}}
    @if($novedades->count())
      <div class="pcards-head">
        <h2>Novedades</h2>
        <a href="{{ route('web.catalog.index') }}" class="btn btn-ghost">Ver todo</a>
      </div>
      <div class="pcards-grid">
        @foreach($novedades as $p)
          @php
            $off  = $discountPct($p);
            $img  = $p->image_url ?: asset('images/placeholder.png');
            $desc = $p->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($p->description ?? ''), 120);
          @endphp
          <div class="pc-col">
            <article class="pcard">
              <div class="pc-top">
                <div class="pc-badges">
                  @if($isNew($p))               <span class="pc-badge pc-new">Nuevo</span> @endif
                  @if(!is_null($p->sale_price))  <span class="pc-badge pc-sale">Oferta</span> @endif
                  @if($off)                      <span class="pc-badge pc-off">-{{ $off }}%</span> @endif
                </div>
                <a href="{{ route('web.catalog.show',$p) }}">
                  <img src="{{ $img }}" alt="{{ $p->name }}" loading="lazy"
                       onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
                </a>

                {{-- Esquina revelable --}}
                <aside class="pc-corner" data-corner>
                  <div class="pc-corner__icon"><span class="material-symbols-outlined">info</span></div>
                  <div class="pc-corner__panel">
                    <button class="pc-corner__close" type="button" title="Cerrar" data-corner-close>
                      <span class="material-symbols-outlined" style="font-size:18px">close</span>
                    </button>
                    <h4>{{ $p->brand ?: 'Detalles' }}</h4>
                    <ul class="pc-corner__list">
                      @if($p->sku)<li><strong>SKU:</strong> {{ $p->sku }}</li>@endif
                      @if(!empty($p->category?->name))<li><strong>Categoría:</strong> {{ $p->category->name }}</li>@endif
                      @if(!is_null($p->stock))<li><strong>Stock:</strong> {{ $p->stock }}</li>@endif
                      <li><strong>Precio:</strong>
                        @if(!is_null($p->sale_price))
                          ${{ number_format($p->sale_price,2) }} <small style="opacity:.8">({{ number_format($p->price,2) }} antes)</small>
                        @else
                          ${{ number_format($p->price,2) }}
                        @endif
                      </li>
                    </ul>
                  </div>
                </aside>
              </div>

              <div class="pc-bottom" id="pcb-nov-{{ $p->id }}">
                <div class="pc-left">
                  <div class="pc-details">
                    <h3 class="pc-name"><a href="{{ route('web.catalog.show',$p) }}" style="color:inherit;text-decoration:none">{{ $p->name }}</a></h3>
                    @if($desc)<p class="pc-desc">{{ $desc }}</p>@endif
                    <div class="pc-price">
                      @if(!is_null($p->sale_price))
                        <span class="now">${{ number_format($p->sale_price,2) }}</span>
                        <span class="old">${{ number_format($p->price,2) }}</span>
                      @else
                        <span class="now" style="color:var(--ink)">${{ number_format($p->price,2) }}</span>
                      @endif
                    </div>
                  </div>
                  <div class="pc-buy">
                    <form action="{{ route('web.cart.add') }}" method="POST" data-pc-cart data-target="pcb-nov-{{ $p->id }}">
                      @csrf
                      <input type="hidden" name="catalog_item_id" value="{{ $p->id }}">
                      <button type="submit" class="pc-btn" aria-label="Agregar {{ $p->name }} al carrito">
                        <span class="material-symbols-outlined">add_shopping_cart</span>
                      </button>
                    </form>
                  </div>
                </div>

                <div class="pc-right">
                  <div class="pc-done"><span class="material-symbols-outlined">check</span></div>
                  <div class="pc-details">
                    <h4 class="pc-title">¡Listo!</h4>
                    <p class="pc-msg">Se agregó al carrito</p>
                  </div>
                  <button type="button" class="pc-remove" data-pc-close data-target="pcb-nov-{{ $p->id }}">
                    <span class="material-symbols-outlined">close</span>
                  </button>
                </div>
              </div>
            </article>
          </div>
        @endforeach
      </div>
    @endif

    {{-- ===== Ofertas ===== --}}
    @if($ofertas->count())
      <div class="pcards-head" style="margin-top:10px">
        <h2>Ofertas</h2>
        <a href="{{ route('web.catalog.index', ['q'=>'oferta']) }}" class="btn btn-ghost">Ver todas</a>
      </div>
      <div class="pcards-grid">
        @foreach($ofertas as $p)
          @php
            $off  = $discountPct($p);
            $img  = $p->image_url ?: asset('images/placeholder.png');
            $desc = $p->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($p->description ?? ''), 120);
          @endphp
          <div class="pc-col">
            <article class="pcard">
              <div class="pc-top">
                <div class="pc-badges">
                  @if($isNew($p))               <span class="pc-badge pc-new">Nuevo</span> @endif
                  @if(!is_null($p->sale_price))  <span class="pc-badge pc-sale">Oferta</span> @endif
                  @if($off)                      <span class="pc-badge pc-off">-{{ $off }}%</span> @endif
                </div>
                <a href="{{ route('web.catalog.show',$p) }}">
                  <img src="{{ $img }}" alt="{{ $p->name }}" loading="lazy"
                       onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
                </a>

                <aside class="pc-corner" data-corner>
                  <div class="pc-corner__icon"><span class="material-symbols-outlined">info</span></div>
                  <div class="pc-corner__panel">
                    <button class="pc-corner__close" type="button" title="Cerrar" data-corner-close>
                      <span class="material-symbols-outlined" style="font-size:18px">close</span>
                    </button>
                    <h4>{{ $p->brand ?: 'Detalles' }}</h4>
                    <ul class="pc-corner__list">
                      @if($p->sku)<li><strong>SKU:</strong> {{ $p->sku }}</li>@endif
                      @if(!empty($p->category?->name))<li><strong>Categoría:</strong> {{ $p->category->name }}</li>@endif
                      @if(!is_null($p->stock))<li><strong>Stock:</strong> {{ $p->stock }}</li>@endif
                      <li><strong>Precio:</strong>
                        @if(!is_null($p->sale_price))
                          ${{ number_format($p->sale_price,2) }} <small style="opacity:.8">({{ number_format($p->price,2) }} antes)</small>
                        @else
                          ${{ number_format($p->price,2) }}
                        @endif
                      </li>
                    </ul>
                  </div>
                </aside>
              </div>

              <div class="pc-bottom" id="pcb-off-{{ $p->id }}">
                <div class="pc-left">
                  <div class="pc-details">
                    <h3 class="pc-name"><a href="{{ route('web.catalog.show',$p) }}" style="color:inherit;text-decoration:none">{{ $p->name }}</a></h3>
                    @if($desc)<p class="pc-desc">{{ $desc }}</p>@endif
                    <div class="pc-price">
                      @if(!is_null($p->sale_price))
                        <span class="now">${{ number_format($p->sale_price,2) }}</span>
                        <span class="old">${{ number_format($p->price,2) }}</span>
                      @else
                        <span class="now" style="color:var(--ink)">${{ number_format($p->price,2) }}</span>
                      @endif
                    </div>
                  </div>
                  <div class="pc-buy">
                    <form action="{{ route('web.cart.add') }}" method="POST" data-pc-cart data-target="pcb-off-{{ $p->id }}">
                      @csrf
                      <input type="hidden" name="catalog_item_id" value="{{ $p->id }}">
                      <button type="submit" class="pc-btn" aria-label="Agregar {{ $p->name }} al carrito">
                        <span class="material-symbols-outlined">add_shopping_cart</span>
                      </button>
                    </form>
                  </div>
                </div>

                <div class="pc-right">
                  <div class="pc-done"><span class="material-symbols-outlined">check</span></div>
                  <div class="pc-details">
                    <h4 class="pc-title">¡Listo!</h4>
                    <p class="pc-msg">Se agregó al carrito</p>
                  </div>
                  <button type="button" class="pc-remove" data-pc-close data-target="pcb-off-{{ $p->id }}">
                    <span class="material-symbols-outlined">close</span>
                  </button>
                </div>
              </div>
            </article>
          </div>
        @endforeach
      </div>
    @endif

  </div>
</section>
@endif
{{-- ======= Hero (tu bloque original, ajustado para móvil) ======= --}}
<section id="hero-full-pap">
  <style>
    /* ===== HERO PAPELERÍA FULL-BLEED (aislado por #hero-full-pap) ===== */
    #hero-full-pap{
      position: relative;
      left: 50%; right: 50%;
      margin-left: -50vw; margin-right: -50vw;
      width: 100vw;
      border: 0; border-radius: 0; box-shadow: none;

      --ink:#0e1726; --muted:#6b7280; --bg:#f6f8fc; --line:#e8eef6;
      --brand:#6ea8fe; --accent:#ffc9de; --ok:#16a34a;
      background:
        radial-gradient(1200px 400px at 10% 0%, rgba(110,168,254,.25), transparent 60%),
        radial-gradient(800px 300px at 90% 20%, rgba(255,201,222,.22), transparent 60%),
        var(--bg);
    }
    #hero-full-pap .wrap{max-width:1200px;margin:0 auto;padding:clamp(26px,3vw,44px)}
    #hero-full-pap .grid{display:grid;grid-template-columns:1.05fr .95fr;gap:clamp(18px,3vw,36px);align-items:center}

    #hero-full-pap h1{margin:0 0 12px; color:var(--ink); letter-spacing:-.02em; line-height:1.05;
      font-size:clamp(28px,4.2vw,52px)}
    #hero-full-pap .grad{background:linear-gradient(90deg,var(--brand),var(--accent));
      -webkit-background-clip:text;background-clip:text;color:transparent}
    #hero-full-pap p.lead{color:var(--muted); font-size:clamp(15px,1.5vw,18px); line-height:1.6; margin:0 0 16px}

    #hero-full-pap .chips{display:flex;flex-wrap:wrap;gap:10px;margin:14px 0 18px}
    #hero-full-pap .chip{background:#fff;border:1px dashed var(--line);padding:8px 12px;border-radius:999px;
      font-size:13px;color:#0f172a;display:inline-flex;align-items:center;gap:8px;box-shadow:0 8px 24px rgba(2,8,23,.04)}
    #hero-full-pap .dot{width:8px;height:8px;border-radius:50%}
    #hero-full-pap .ok{background:var(--ok)} .brand{background:var(--brand)} .accent{background:var(--accent)}

    #hero-full-pap .bullets{display:grid;gap:10px;margin:16px 0 8px}
    #hero-full-pap .bullet{display:flex;gap:10px;align-items:flex-start;color:var(--ink);font-size:15px}
    #hero-full-pap .bullet i{flex:0 0 18px;height:18px;border-radius:6px;background:rgba(110,168,254,.25);
      box-shadow:inset 0 0 0 2px rgba(110,168,254,.55)}

    #hero-full-pap .cta{display:flex;gap:12px;flex-wrap:wrap;margin-top:16px}

    /* tarjetas derechas */
    #hero-full-pap .gallery{display:grid;gap:16px}
    #hero-full-pap .card-img{position:relative;border-radius:22px;overflow:hidden;background:#fff;border:1px solid var(--line);
      box-shadow:0 24px 60px rgba(2,8,23,.10);transition:transform .4s ease, box-shadow .4s ease}
    #hero-full-pap .card-img:hover{transform:translateY(-4px);box-shadow:0 28px 70px rgba(2,8,23,.18)}
    #hero-full-pap .card-img img{width:100%;height:100%;object-fit:cover;display:block}
    #hero-full-pap .tag{position:absolute;top:12px;left:12px;background:rgba(255,255,255,.65);backdrop-filter:blur(8px);
      border:1px solid rgba(255,255,255,.6);padding:6px 10px;border-radius:999px;font-size:12px;font-weight:600;color:#0b1220}
    #hero-full-pap .note{position:absolute;right:12px;bottom:12px;background:#fff;border:1px dashed var(--line);
      border-radius:12px;padding:8px 10px;font-size:12px;color:#6b7280}
    #hero-full-pap .top{aspect-ratio:4/3;min-height:220px}
    #hero-full-pap .bottom{aspect-ratio:4/3;min-height:220px}

    /* trust bar */
    #hero-full-pap .trust{display:flex;gap:18px;flex-wrap:wrap;align-items:center;margin-top:16px}
    #hero-full-pap .trust small{color:#6b7280}
    #hero-full-pap .logo{height:22px;opacity:.75;filter:grayscale(1);transition:opacity .25s ease,filter .25s ease}
    #hero-full-pap .logo:hover{opacity:1;filter:grayscale(0)}

    /* ===== Responsive =====
       - En móvil: texto arriba (order 1), galería abajo (order 2).
       - Se oculta la imagen "top" (Listo para despacho).
       - Se mantiene visible la imagen "bottom" (Oficina & Corporativo). */
@media (max-width:980px){
  #hero-full-pap .grid{ grid-template-columns:1fr }
  #hero-full-pap .grid > :first-child{ order:1 }   /* texto arriba */
  #hero-full-pap .gallery{ display:none !important } /* ocultar toda la galería (ambas fotos) */
}

    
  </style>

  <div class="wrap">
    <div class="grid">
      <!-- Texto -->
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

      <!-- Galería -->
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


<script>
(() => {
  const root = document.querySelector('.pcards');
  if (!root) return;

  // --- Patch suave: si existe window.showToast, emite evento para sincronizar UI local ---
  (function patchToast(){
    if (typeof window.showToast === 'function' && !window._toastPatchedForPcards){
      const orig = window.showToast;
      window.showToast = function(opts = {}){
        const out = orig.call(this, opts);
        try { window.dispatchEvent(new CustomEvent('toast:show', { detail: opts })); } catch(_) {}
        return out;
      };
      window._toastPatchedForPcards = true;
    }
  })();

  // === Cerrar slide verde (botón tache) ===
  root.addEventListener('click', e => {
    const closeBtn = e.target.closest('[data-pc-close]');
    if (!closeBtn) return;
    const id = closeBtn.getAttribute('data-target');
    const panel = document.getElementById(id);
    if (panel) panel.classList.remove('is-added');

    // Rehabilitar botón de su form asociado
    const form = root.querySelector(`form[data-pc-cart][data-target="${CSS.escape(id)}"]`);
    if (form) {
      form.dataset.pcardsBusy = '0';
      const btn = form.querySelector('.pc-btn');
      btn && btn.removeAttribute('disabled');
    }
  });

  // === Esquina revelable (abrir/cerrar con bloqueo anti-reapertura) ===
  root.addEventListener('click', e => {
    // Cerrar con el tache
    const close = e.target.closest('[data-corner-close]');
    if (close) {
      e.stopPropagation();

      const corner = close.closest('[data-corner]');
      const card   = close.closest('.pcard');

      // Quitar estado abierto
      corner && corner.classList.remove('is-open');

      // Quitar foco para evitar :focus-within
      try { close.blur(); } catch(_) {}
      try {
        card.querySelectorAll('a,button,input,textarea,select,[tabindex]')
            .forEach(el => el.blur && el.blur());
      } catch(_) {}

      // Bloqueo temporal para que hover/focus no reabran de inmediato
      if (card) {
        card.classList.add('corner-closed');
        const unlock = () => {
          card.classList.remove('corner-closed');
          card.removeEventListener('mouseleave', unlock);
        };
        card.addEventListener('mouseleave', unlock, { once:true });
        setTimeout(unlock, 900); // fallback en touch
      }
      return;
    }

    // Toggle por click en la esquina (respetando bloqueo)
    const corner = e.target.closest('[data-corner]');
    if (corner) {
      const card = corner.closest('.pcard');
      if (card && card.classList.contains('corner-closed')) return;
      corner.classList.toggle('is-open');
    }
  });

  // === Integración con GLOBAL: NO interceptamos submit; SOLO UI + gating ===
  root.querySelectorAll('form[data-pc-cart]').forEach(form => {
    const btn   = form.querySelector('.pc-btn');
    const id    = form.getAttribute('data-target');
    const panel = id ? document.getElementById(id) : null;

    // Bloquear reclics mientras está ocupado (captura, antes de global)
    btn?.addEventListener('click', (e) => {
      if (form.dataset.pcardsBusy === '1') {
        e.preventDefault(); e.stopImmediatePropagation(); e.stopPropagation();
      }
    }, { capture:true });

    // En submit: NO preventDefault -> deja que el GLOBAL haga el fetch
    form.addEventListener('submit', () => {
      if (form.dataset.pcardsBusy === '1') return;
      form.dataset.pcardsBusy = '1';
      btn && btn.setAttribute('disabled','disabled');

      // Mostrar "añadido" optimista; si falla, lo revertimos al escuchar el toast warning/error
      panel && panel.classList.add('is-added');
    });
  });

  // === Escucha el toast del GLOBAL para confirmar/revertir UI ===
  window.addEventListener('toast:show', (e) => {
    const kind = (e.detail && e.detail.kind) || 'info';
    // Tomamos el primer form ocupado dentro del grid (lo normal es 1 por el gating)
    const busyForm = root.querySelector('form[data-pc-cart][data-pcards-busy="1"]');
    if (!busyForm) return;

    const btn   = busyForm.querySelector('.pc-btn');
    const id    = busyForm.getAttribute('data-target');
    const panel = id ? document.getElementById(id) : null;

    if (kind === 'warning' || kind === 'error') {
      // Revertir slide si el GLOBAL reporta problema
      panel && panel.classList.remove('is-added');
    }
    // Liberar gating un instante después del toast para evitar reclic por inercia
    setTimeout(() => {
      busyForm.dataset.pcardsBusy = '0';
      btn && btn.removeAttribute('disabled');
    }, 500);
  });

  // === Cerrar esquinas con ESC (opcional) ===
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      root.querySelectorAll('.pc-corner.is-open').forEach(c => c.classList.remove('is-open'));
    }
  });
})();
</script>



 {{-- ===== Tarjetas de producto (full-width) sin estrellas + con descripción ===== --}}

<style>
  :root{
    --ink:#0f172a; --muted:#6b7280; --line:#e5e7eb; --surface:#fff;
    --accent:#f59e0b; --accent-ink:#9a3412;
  }

  /* Full-bleed: el contenedor ocupa todo el ancho de la pantalla */
  .products-fullbleed{
    width:100vw;
    margin-left:calc(50% - 50vw);
    margin-right:calc(50% - 50vw);
    padding: 8px clamp(12px, 2.2vw, 28px);
  }

  /* Grid fluido: tantas columnas como quepan */
  .grid-prods{
    display:grid;
    gap:18px;
    grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));
  }

  /* Tarjeta */
  .pc2{
    display:flex; flex-direction:column; height:100%;
    background:var(--surface);
    border:1px solid var(--line);
    border-radius:14px;
    box-shadow:0 6px 18px rgba(2,6,23,.06);
    padding:14px;
    box-sizing:border-box; /* evita que algo se “salga” */
    overflow:hidden;
  }

  .pc2-figure{
    display:flex; justify-content:center; align-items:center;
    height:170px; border-radius:10px; background:#f8fafc;
    margin-bottom:10px; overflow:hidden;
  }
  .pc2-figure img{max-width:100%;max-height:100%;object-fit:contain;display:block}

  .pc2-brand{font-weight:900;text-transform:uppercase;color:#1d4ed8;font-size:.95rem;margin:4px 0 2px;text-decoration:none}
  .pc2-title{font-weight:800;color:var(--ink);line-height:1.25;margin:0}
  .pc2-title a{color:inherit;text-decoration:none}
  .pc2-desc{
    color:var(--muted); font-size:.95rem; margin:.45rem 0 .2rem;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
  }

  .pc2-price{margin:.4rem 0 0.9rem;display:flex;align-items:baseline;gap:10px}
  .pc2-price .now{font-weight:900;color:var(--ink);font-size:1.15rem}
  .pc2-price .now--sale{color:#16a34a}
  .pc2-price .old{color:#94a3b8;text-decoration:line-through}

  /* Pie: se queda pegado abajo para que el botón no se salga */
  .pc2-footer{margin-top:auto; display:flex; align-items:center; gap:10px; flex-wrap:nowrap}

  .pc2-qty{display:flex; align-items:center; gap:8px; color:var(--muted); font-weight:700}
  .pc2-qty input[type="number"]{
    width:80px; padding:10px 12px; border:1px solid var(--line); border-radius:10px; background:#fff; color:var(--ink);
    appearance:textfield; outline:none;
  }
  .pc2-qty input::-webkit-outer-spin-button,
  .pc2-qty input::-webkit-inner-spin-button{ -webkit-appearance: none; margin: 0; }

  .pc2-add{
    display:inline-flex; align-items:center; gap:10px; white-space:nowrap;
    border:2px solid #fde68a; background:#fff7ed; color:var(--accent-ink);
    padding:12px 18px; border-radius:999px; font-weight:900; cursor:pointer;
    box-shadow:0 8px 18px rgba(245,158,11,.18);
    transition:transform .12s ease, box-shadow .2s ease, background .2s ease;
  }
  .pc2-add:hover{ transform:translateY(-1px); box-shadow:0 10px 22px rgba(245,158,11,.28) }
  .pc2-add svg{width:20px;height:20px}

  /* Un poco más compacto en pantallas muy pequeñas */
  @media (max-width:420px){
    .pc2-figure{height:150px}
    .pc2-add{flex:1; justify-content:center}
  }
</style>

@php
  $products = \App\Models\CatalogItem::published()->ordered()->take(12)->get();
@endphp

@if($products->count())
  <section class="products-fullbleed" aria-label="Productos">
    <div class="grid-prods">
      @foreach($products as $p)
        @php
          $img = $p->image_url ?: asset('images/placeholder.png');
          $hasSale = !is_null($p->sale_price) && $p->sale_price > 0 && $p->sale_price < $p->price;
          // descripción breve (2 líneas). Usa excerpt si existe; si no, recorta description sin HTML.
          $desc = $p->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($p->description ?? ''), 110);
        @endphp

        <article class="pc2">
          <a class="pc2-figure" href="{{ route('web.catalog.show', $p) }}" aria-label="Ver {{ $p->name }}">
            <img src="{{ $img }}" alt="{{ $p->name }}"
                 onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
          </a>

          <a class="pc2-brand" href="{{ route('web.catalog.show', $p) }}">{{ $p->brand ?? '—' }}</a>
          <h3 class="pc2-title"><a href="{{ route('web.catalog.show', $p) }}">{{ $p->name }}</a></h3>

          @if($desc)
            <p class="pc2-desc">{{ $desc }}</p>
          @endif

          <div class="pc2-price">
            @if($hasSale)
              <span class="now now--sale">${{ number_format($p->sale_price,2) }}</span>
              <span class="old">${{ number_format($p->price,2) }}</span>
            @else
              <span class="now">${{ number_format($p->price,2) }}</span>
            @endif
          </div>

          <form class="pc2-footer" action="{{ route('web.cart.add') }}" method="POST">
            @csrf
            <input type="hidden" name="catalog_item_id" value="{{ $p->id }}">
            <div class="pc2-qty">
              <label for="qty-{{ $p->id }}" style="font-weight:700">Cant.</label>
              <input id="qty-{{ $p->id }}" name="qty" type="number" min="1" value="1">
            </div>

            <button class="pc2-add" type="submit" aria-label="Agregar {{ $p->name }} al carrito">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="color:var(--accent-ink)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 12.39a2 2 0 0 0 2 1.61h7.72a2 2 0 0 0 2-1.61L21 7H6"></path>
              </svg>
              Agregar
            </button>
          </form>
        </article>
      @endforeach
    </div>
  </section>
@endif
{{-- ===================== SLIDER CENTER-MODE: PAPELERÍA (FULL WIDTH + MOBILE-FIRST) ===================== --}}
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

      {{-- 1. Cuadernos --}}
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

      {{-- 2. Escritura --}}
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

      {{-- 3. Arte & Dibujo --}}
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

      {{-- 4. Organización --}}
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

      {{-- 5. Impresión & Tintas --}}
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
  /* ====== MOBILE-FIRST ====== */
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

  /* Contenido móvil: fila, título SIEMPRE visible horizontal */
  .stc-content{position:absolute;inset:0;display:flex;align-items:flex-end;gap:.8rem;padding:1rem;background:linear-gradient(transparent 40%, rgba(0,0,0,.55) 90%);z-index:2}
  .stc-title{color:#fff;font-weight:900;font-size:1.15rem;margin-right:auto}
  .stc-thumb,.stc-desc,.stc-btn{display:none}

  /* Al estar activa, mostramos más info en móvil */
  .stc-card[active] .stc-content{align-items:flex-end;gap:1rem}
  .stc-card[active] .stc-thumb{display:block;width:110px;height:170px;border-radius:10px;object-fit:cover;box-shadow:0 6px 16px rgba(0,0,0,.28)}
  .stc-card[active] .stc-desc{display:block;color:#f1f5f9;line-height:1.45;max-width:100%}
  .stc-card[active] .stc-btn{display:inline-block;background:#0ea5e9;border:0;color:#fff;padding:.55rem 1rem;border-radius:999px;font-weight:800;text-decoration:none;box-shadow:0 10px 24px rgba(14,165,233,.28)}

  .stc-dots{display:none} /* en móvil ocultamos los dots para dejar limpio */

  /* ====== DESKTOP / TABLET (mejoras visuales y center-mode real) ====== */
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

  // Crear dots (sólo visibles en >=768px por CSS)
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
  function go(step){ activate(Math.min(Math.max(current + step, 0), cards.length - 1), true); }

  prev.addEventListener('click', () => go(-1));
  next.addEventListener('click', () => go(1));

  // Hover / click
  cards.forEach((card, i) => {
    card.addEventListener('mouseenter', () => matchMedia('(hover:hover)').matches && activate(i, true));
    card.addEventListener('click', () => activate(i, true));
  });

  // Swipe táctil
  let sx=0, sy=0;
  track.addEventListener('touchstart', e => { sx=e.touches[0].clientX; sy=e.touches[0].clientY; }, {passive:true});
  track.addEventListener('touchend', e => {
    const dx = e.changedTouches[0].clientX - sx;
    const dy = e.changedTouches[0].clientY - sy;
    const dist = isMobile()? Math.abs(dy) : Math.abs(dx);
    if(dist > 60) go((isMobile()? dy : dx) > 0 ? -1 : 1);
  }, {passive:true});

  // Teclado
  addEventListener('keydown', e => {
    if(['ArrowRight','ArrowDown'].includes(e.key)) go(1);
    if(['ArrowLeft','ArrowUp'].includes(e.key)) go(-1);
  }, {passive:true});

  addEventListener('resize', () => center(current));
  toggleUI(0); center(0);
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

  {{-- ======= JS mínimo (feedback al añadir) ======= --}}
  <script>
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.pc-btn');
      if(!btn) return;
      setTimeout(() => btn.classList.add('is-added'), 150);
    });
  </script>
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
  <style>
    /* ====== TESTIMONIOS FULL-WIDTH (aislado por #home-cmt-marquee) ====== */
    #home-cmt-marquee{
      position:relative;
      left:50%; right:50%;
      margin-left:-50vw; margin-right:-50vw;
      width:100vw;
      padding:40px 0 46px;
      background:
        radial-gradient(1200px 420px at 50% 0%, #fefce8 0%, rgba(254,252,232,0) 60%),
        linear-gradient(to bottom, #fdfdfb 0%, #f4ffe8 40%, #fdfdfb 100%);
      border-top:1px solid #e5e7eb;
      border-bottom:1px solid #e5e7eb;
      --ink:#020617;
      --muted:#6b7280;
      --brand:#22c55e;
    }
    #home-cmt-marquee .wrap{
      max-width:1200px;
      margin:0 auto;
      padding:0 18px;
    }

    /* ===== HEADER ===== */
    #home-cmt-marquee header{
      text-align:center;
      max-width:640px;
      margin:0 auto 20px;
    }
    #home-cmt-marquee h2{
      margin:0 0 4px;
      font-size:clamp(26px,3.1vw,34px);
      letter-spacing:-.03em;
      color:var(--ink);
    }
    #home-cmt-marquee .grad{
      background:linear-gradient(90deg,#22c55e,#0ea5e9);
      -webkit-background-clip:text;
      background-clip:text;
      color:transparent;
    }
    #home-cmt-marquee p.lead{
      margin:0;
      font-size:14px;
      color:var(--muted);
    }
    #home-cmt-marquee .header-actions{
      margin-top:14px;
      display:flex;
      justify-content:center;
      gap:10px;
      flex-wrap:wrap;
    }
    #home-cmt-marquee .tagline{
      font-size:12px;
      padding:7px 11px;
      border-radius:999px;
      border:1px dashed rgba(148,163,184,.7);
      background:rgba(255,255,255,.9);
      color:#0f172a;
      display:inline-flex;
      align-items:center;
      gap:6px;
    }
    #home-cmt-marquee .tagline span.dot{
      width:7px;height:7px;border-radius:999px;
      background:#22c55e;
    }
    #home-cmt-marquee .btn-ghost{
      font-size:13px;
      padding:8px 14px;
      border-radius:999px;
      border:1px solid #020617;
      background:#020617;
      color:#f9fafb;
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      gap:6px;
      transition:background .15s ease,color .15s ease,transform .15s ease,box-shadow .15s ease;
      box-shadow:0 14px 30px rgba(15,23,42,.25);
    }
    #home-cmt-marquee .btn-ghost:hover{
      background:#f9fafb;
      color:#020617;
      transform:translateY(-1px);
      box-shadow:0 18px 40px rgba(15,23,42,.3);
    }

    /* ===== FAJA DEL MARQUEE ===== */
    #home-cmt-marquee .rows{
      position:relative;
      overflow:hidden;
      margin-top:18px;
    }
    #home-cmt-marquee .rows::before,
    #home-cmt-marquee .rows::after{
      content:"";
      position:absolute;
      inset-y:0;
      width:80px;
      z-index:2;
      pointer-events:none;
    }
    #home-cmt-marquee .rows::before{
      left:0;
      background:linear-gradient(to right, #f4ffe8 0%, rgba(244,255,232,0) 80%);
    }
    #home-cmt-marquee .rows::after{
      right:0;
      background:linear-gradient(to left, #f4ffe8 0%, rgba(244,255,232,0) 80%);
    }

    #home-cmt-marquee .row{
      display:flex;
      align-items:center;
      padding-block:7px;
      will-change:transform;
    }
    #home-cmt-marquee .track{
      display:flex;
      align-items:stretch;
      gap:16px;
      animation: home-cmt-left 32s linear infinite;
    }
    #home-cmt-marquee .row-2 .track{
      animation-name: home-cmt-right;
      animation-duration: 38s;
    }
    #home-cmt-marquee .row:hover .track{
      animation-play-state:paused;
    }

    /* loop suave: del 0% al -50% de su propio ancho (2 copias) */
    @keyframes home-cmt-left{
      0%{ transform:translateX(0); }
      100%{ transform:translateX(-50%); }
    }
    @keyframes home-cmt-right{
      0%{ transform:translateX(0); }
      100%{ transform:translateX(50%); }
    }

    /* ===== TARJETAS (todas mismo tamaño) ===== */
    #home-cmt-marquee .card{
      flex:0 0 280px;          /* ancho fijo para todas */
      width:280px;
      min-height:150px;        /* alto mínimo */
      padding:14px 16px 12px;
      border-radius:22px;
      background:#ffffff;
      border:1px solid rgba(148,163,184,.35);
      box-shadow:
        0 18px 45px rgba(15,23,42,.08),
        0 0 0 1px rgba(255,255,255,.8) inset;
      display:flex;
      flex-direction:column;
      gap:6px;
    }
    #home-cmt-marquee .card-head{
      display:flex;
      align-items:center;
      gap:8px;
    }

    /* ===== AVATARES PASTELES ===== */
    #home-cmt-marquee .avatar{
      flex:0 0 32px;
      width:32px;height:32px;
      border-radius:999px;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:13px;
      font-weight:700;
      color:#f9fafb;
    }
    #home-cmt-marquee .avatar.c0{
      background:radial-gradient(circle at 30% 0%,#fee2e2 0,#f9a8d4 30%,#a5b4fc 85%);
    }
    #home-cmt-marquee .avatar.c1{
      background:radial-gradient(circle at 30% 0%,#dbeafe 0,#bfdbfe 35%,#bbf7d0 90%);
    }
    #home-cmt-marquee .avatar.c2{
      background:radial-gradient(circle at 30% 0%,#fef3c7 0,#fdba74 35%,#fbcfe8 90%);
    }
    #home-cmt-marquee .avatar.c3{
      background:radial-gradient(circle at 30% 0%,#ede9fe 0,#c4b5fd 35%,#a7f3d0 90%);
    }

    #home-cmt-marquee .meta{
      display:flex;
      flex-direction:column;
      gap:1px;
    }
    #home-cmt-marquee .name{
      font-size:13px;
      font-weight:700;
      color:var(--ink);
    }
    #home-cmt-marquee .user{
      font-size:11px;
      color:var(--muted);
    }
    #home-cmt-marquee .body{
      margin-top:4px;
      font-size:13px;
      line-height:1.55;
      color:#0f172a;
      flex:1;
      max-height:3.4em;       /* 2 líneas aprox */
      overflow:hidden;
    }
    #home-cmt-marquee .pill{
      margin-top:4px;
      align-self:flex-start;
      font-size:11px;
      padding:4px 8px;
      border-radius:999px;
      border:1px solid rgba(37,99,235,.25);
      background:linear-gradient(90deg,rgba(219,234,254,.9),rgba(187,247,208,.9));
      color:#1d4ed8;
      display:inline-flex;
      align-items:center;
      gap:5px;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width:960px){
      #home-cmt-marquee{
        padding:28px 0 32px;
      }
    }
    @media (max-width:768px){
      #home-cmt-marquee .card{
        flex:0 0 80vw;
        width:80vw;
      }
      #home-cmt-marquee .row-2{
        display:none; /* en móvil solo una fila */
      }
      #home-cmt-marquee .track{
        animation-duration:40s;
      }
    }
  </style>

  <div class="wrap">
    <header>
      <h2>Lo que <span class="grad">dicen de nosotros</span></h2>
      <p class="lead">Comentarios reales de clientes que ya compran con Jureto.</p>
      <div class="header-actions">
        <span class="tagline">
          <span class="dot"></span> Comentarios verificados
        </span>
        <a href="{{ route('comments.index') }}" class="btn-ghost">
          Ver todos los comentarios
        </a>
      </div>
    </header>

    @php
      // colección única y dividida en 2 filas
      $items = $marqueeComments->unique('id')->values();
      $count = $items->count();
      $half  = (int) ceil($count / 2);
      $row1  = $items->slice(0, $half)->values();
      $row2  = $items->slice($half)->values();
      $colorIndex1 = 0;
      $colorIndex2 = 0;
    @endphp

    <div class="rows">
      {{-- ===== FILA 1 ===== --}}
      <div class="row row-1">
        <div class="track">
          {{-- Dos copias del mismo orden → loop continuo sin cortes visibles --}}
          @foreach([$row1, $row1] as $set)
            @foreach($set as $comment)
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
                  <span>📦</span>
                  <span>Pedido reciente</span>
                </div>
              </article>
            @endforeach
          @endforeach
        </div>
      </div>

      {{-- ===== FILA 2 (si hay más comentarios) ===== --}}
      @if($row2->count())
      <div class="row row-2">
        <div class="track">
          @foreach([$row2, $row2] as $set)
            @foreach($set as $comment)
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
                  <span>🧾</span>
                  <span>Factura incluida</span>
                </div>
              </article>
            @endforeach
          @endforeach
        </div>
      </div>
      @endif
    </div>
  </div>
</section>
@endif

@endsection
