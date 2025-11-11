@extends('layouts.web') 
@section('title','Sobre Nosotros')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
<style>
  :root{
    --ink:#0e1726; --muted:#6b7280; --bg:#f6f8fc;
    --brand:#6ea8fe; --brand-ink:#0b1220;
    --surface:#ffffff; --line:#e8eef6;
    --ok:#16a34a; --warn:#eab308;
    --radius:22px; --shadow:0 20px 60px rgba(2,8,23,.10);
  }
  html,body{background:var(--bg)}
  .container{max-width:1200px;margin:0 auto;padding: clamp(18px,2.2vw,28px)}

  /* ===== Motion tokens ===== */
  @media (prefers-reduced-motion:no-preference){
    :root{
      --t1:.45s cubic-bezier(.2,.7,.2,1);
      --t2:.7s cubic-bezier(.2,.7,.2,1);
      --t3:.9s cubic-bezier(.16,.84,.3,1);
      --stagger: .06s;
    }
  }

  /* ===== Page enter ===== */
  .page-enter *{ transform: none }
  .page-enter [data-enter]{ opacity:0; transform: translateY(18px); }
  .page-enter [data-enter="up"]{ transform: translateY(18px) }
  .page-enter [data-enter="down"]{ transform: translateY(-18px) }
  .page-enter [data-enter="left"]{ transform: translateX(24px) }
  .page-enter [data-enter="right"]{ transform: translateX(-24px) }
  .page-enter [data-enter="zoom"]{ transform: scale(.96); filter: blur(2px) }
  .is-loaded [data-enter]{ 
    opacity:1; transform: none; 
    transition: transform var(--t2), opacity var(--t2), filter var(--t2);
  }

  /* ===== Reveal on scroll ===== */
  [data-reveal]{ opacity:0; transform: translateY(22px); will-change: transform,opacity; }
  [data-reveal="left"]{ transform: translateX(-28px) }
  [data-reveal="right"]{ transform: translateX(28px) }
  [data-reveal="zoom"]{ transform: scale(.96); filter: blur(2px) }
  .in-view{ opacity:1 !important; transform:none !important; filter:none !important; transition: transform var(--t2), opacity var(--t2), filter var(--t2) }

  /* Stagger helper */
  [data-stagger] > *{ transition-delay: calc(var(--i, 0) * var(--stagger, .06s)) }

  /* ===== Hero ===== */
  .hero{
    border-radius: 28px;
    background:
      radial-gradient(1200px 500px at -10% -20%, #eaf2ff 0%, transparent 85%),
      radial-gradient(900px 500px at 110% 10%, #ffe9f0 0%, transparent 85%),
      #fff;
    padding: clamp(28px,5vw,60px);
    box-shadow: var(--shadow);
    border:1px solid var(--line);
    text-align:center;
    position: relative;
    overflow: hidden;
  }
  /* Parallax glow */
  .hero::after{
    content:""; position:absolute; inset:auto -20% -40% -20%;
    height: 60%; background: radial-gradient(60% 80% at 50% 0%, rgba(110,168,254,.25), transparent 70%);
    transform: translateY(var(--parallax,0px));
    transition: transform .12s linear;
    pointer-events:none;
  }
  .hero h1{
    font-size: clamp(28px,4vw,46px);
    line-height: 1.1;
    letter-spacing:-.02em;
    color:#0b1a3a;
    margin:0 0 10px;
  }
  .hero p{
    color:var(--muted);
    font-size: clamp(14px,1.5vw,18px);
    margin: 0 auto;
    max-width: 880px;
  }

  /* ===== Cards ===== */
  .grid{display:grid; gap: clamp(18px,2.2vw,26px)}
  @media(min-width: 960px){ .grid{grid-template-columns: 1fr 1fr;} }

  .card{
    background:var(--surface); border:1px solid var(--line);
    border-radius: var(--radius); overflow:hidden; box-shadow: var(--shadow);
    display:flex; flex-direction:column;
    transform-origin: center top;
  }
  .media{ position:relative; aspect-ratio: 16/10; overflow:hidden; }
  .media img{ width:100%; height:100%; object-fit:cover; transform: scale(1.02); transition: transform .6s ease; }
  .card:hover .media img{ transform: scale(1.06); }
  .pill{
    position:absolute; inset:auto 14px 14px auto;
    background:rgba(255,255,255,.85); backdrop-filter: blur(8px);
    border:1px solid var(--line);
    padding:8px 12px; border-radius: 999px; font-weight:600; color:#0b1a3a;
    font-size:14px;
    transform: translateY(8px); opacity:.0; transition: transform var(--t1), opacity var(--t1);
  }
  .card:hover .pill{ transform: translateY(0); opacity:1 }
  .body{ padding: clamp(16px,2.2vw,22px) clamp(16px,2.2vw,22px) 26px; }
  .body h3{ font-size: clamp(20px,2.4vw,24px); margin:0 0 8px; color:#0b1a3a; letter-spacing:-.02em; }
  .body p{ color:var(--muted); margin:0; line-height:1.6; }

  /* ===== Extras ===== */
  .values{
    margin-top: clamp(18px,3vw,30px);
    display:grid; gap:12px;
    grid-template-columns: repeat(2,minmax(0,1fr));
  }
  @media(min-width: 880px){ .values{grid-template-columns: repeat(4,1fr);} }
  .chip{
    background:#f9fbff; border:1px solid var(--line); border-radius:14px;
    padding:14px 16px; text-align:center; color:#0b1a3a; font-weight:600;
    transition: transform var(--t1), box-shadow var(--t1);
  }
  .chip small{display:block; color:var(--muted); font-weight:500; margin-top:4px}
  .chip:hover{ transform: translateY(-2px); box-shadow: 0 10px 24px rgba(2,8,23,.10) }

  .cta{ margin-top: clamp(28px,4vw,44px); display:flex; gap:12px; justify-content:center; flex-wrap:wrap; }
  .btn{
    appearance:none; border:1px solid #cfe1ff; background: var(--brand);
    color:#04122b; font-weight:700; letter-spacing:.01em;
    padding:12px 18px; border-radius:14px; cursor:pointer;
    box-shadow: 0 10px 24px rgba(110,168,254,.35);
    transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
    text-decoration:none; display:inline-flex; align-items:center; gap:8px;
  }
  .btn:hover{ transform: translateY(-2px); box-shadow: 0 16px 34px rgba(110,168,254,.45); filter: brightness(1.02) }
  .btn-outline{ background:#fff; color:#0b1a3a; border-color:var(--line); box-shadow: none; }

  /* ===== TEAM (namespaced .team-*) ===== */
  .team-wrap{ margin-top: clamp(40px,6vw,70px); padding: clamp(10px,2vw,14px) 0 6px; position:relative; }
  .team-title{
    font-size: clamp(36px,6vw,92px); font-weight:900; text-transform:uppercase;
    letter-spacing:-.02em; text-align:center; line-height:1; margin:0 0 12px;
    background: linear-gradient(to bottom, rgb(8 42 123 / 35%) 30%, rgb(255 255 255 / 0%) 76%);
    -webkit-background-clip:text; background-clip:text; color:transparent;
  }
  .team-carousel{ width:100%; max-width:1200px; height:450px; position:relative; perspective:1000px; margin: 30px auto 0; }
  .team-track{ width:100%; height:100%; display:flex; justify-content:center; align-items:center; position:relative; transform-style:preserve-3d; transition: transform .8s cubic-bezier(.25,.46,.45,.94); }
  .tcard{
    position:absolute; width:280px; height:380px; background:#fff; border-radius:20px; overflow:hidden;
    box-shadow:0 20px 40px rgba(0,0,0,.15); transition: all .8s cubic-bezier(.25,.46,.45,.94); cursor:pointer;
  }
  .tcard img{ width:100%; height:100%; object-fit:cover; transition: inherit; }
  .tcard.center{ z-index:10; transform: scale(1.1) translateZ(0); }
  .tcard.left-2{ z-index:1; transform: translateX(-400px) scale(.8) translateZ(-300px); opacity:.7; filter: grayscale(100%); }
  .tcard.left-1{ z-index:5; transform: translateX(-200px) scale(.9) translateZ(-100px); opacity:.9; filter: grayscale(100%); }
  .tcard.right-1{ z-index:5; transform: translateX(200px) scale(.9) translateZ(-100px); opacity:.9; filter: grayscale(100%); }
  .tcard.right-2{ z-index:1; transform: translateX(400px) scale(.8) translateZ(-300px); opacity:.7; filter: grayscale(100%); }
  .tcard.hidden{ opacity:0; pointer-events:none; }

  .team-info{ text-align:center; margin-top: 26px; transition: all .5s ease-out; }
  .team-name{ color: rgb(8,42,123); font-size: clamp(22px,3vw,32px); font-weight:700; margin:0 0 6px; position:relative; display:inline-block; }
  .team-name::before,.team-name::after{ content:""; position:absolute; top:100%; width:100px; height:2px; background: rgb(8,42,123); }
  .team-name::before{ left:-120px; } .team-name::after{ right:-120px; }
  .team-role{ color:#848696; font-size: clamp(14px,2.2vw,18px); font-weight:500; opacity:.85; text-transform:uppercase; letter-spacing:.1em; margin-top:2px; }

  .team-dots{ display:flex; justify-content:center; gap:10px; margin-top: 26px; }
  .team-dot{ width:12px; height:12px; border-radius:50%; background: rgba(8,42,123,.2); cursor:pointer; transition: all .3s ease; }
  .team-dot.active{ background: rgb(8,42,123); transform: scale(1.2); }

  .team-arrow{
    position:absolute; top:50%; transform: translateY(-50%); background: rgba(8,42,123,.6); color:#fff;
    width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    cursor:pointer; z-index:20; transition: all .3s ease; font-size:1.5rem; border:none; outline:none;
  }
  .team-arrow:hover{ background: rgba(0,0,0,.8); transform: translateY(-50%) scale(1.1); }
  .team-arrow.left{ left:20px; } .team-arrow.right{ right:20px; }
.hero{ position: relative; }
.hero::after{ z-index: 0; }           /* glow detrás */
.hero > *{ position: relative; z-index: 1; }  /* contenido delante */

  @media (max-width: 768px){
    .team-carousel{ height:320px; }
    .tcard{ width:200px; height:280px; }
    .tcard.left-2{ transform: translateX(-250px) scale(.8) translateZ(-300px); }
    .tcard.left-1{ transform: translateX(-120px) scale(.9) translateZ(-100px); }
    .tcard.right-1{ transform: translateX(120px) scale(.9) translateZ(-100px); }
    .tcard.right-2{ transform: translateX(250px) scale(.8) translateZ(-300px); }
    .team-name::before,.team-name::after{ width:50px; }
    .team-name::before{ left:-70px; } .team-name::after{ right:-70px; }
  }
</style>

<div class="container page-enter">
  <!-- HERO -->
  <section class="hero" data-enter="zoom">
    <h1 data-enter="up" style="transition-delay:.05s">Sobre Nosotros</h1>
    <p data-enter="up" style="transition-delay:.12s">
      Somos tu aliado en <strong>papelería</strong> para <strong>mayoreo y menudeo</strong>.
      Ofrecemos <strong>precios accesibles</strong>, <strong>ofertas semanales</strong>, 
      <strong>paquetes para oficinas y escuelas</strong>, y <strong>asesoría personalizada</strong> 
      para que compres fácil, rápido y al mejor costo—al estilo de las grandes plataformas de pedidos.
    </p>
    <div class="values" data-stagger>
      <div class="chip" data-enter="up" style="--i:0">+9,000<small>productos en catálogo</small></div>
      <div class="chip" data-enter="up" style="--i:1">Mayoreo & Menudeo<small>precios escalonados</small></div>
      <div class="chip" data-enter="up" style="--i:2">Ofertas<small>promos cada semana</small></div>
      <div class="chip" data-enter="up" style="--i:3">Envíos MX<small>48–72h según zona</small></div>
    </div>
  </section>

@php
  $misionImg = asset('images/linea.jpg');
  $visionImg = asset('images/mayoreo.jpg');
@endphp

  <!-- Misión & Visión -->
  <section class="grid" style="margin-top: clamp(22px,3vw,36px)">
    <article class="card" data-reveal="zoom">
      <figure class="media">
        <img src="{{ $misionImg }}" alt="Nuestra Misión en Papelería" loading="lazy">
        <span class="pill">Misión</span>
      </figure>
      <div class="body">
        <h3>Misión</h3>
        <p>
          Abastecer a negocios, escuelas y oficinas con papelería de calidad a 
          <strong>precios competitivos</strong>, ofreciendo <strong>paquetes armados</strong>,
          <strong>descuentos por volumen</strong> y <strong>asesoría</strong> para optimizar su compra.
        </p>
      </div>
    </article>

    <article class="card" data-reveal="zoom" style="transition-delay:.08s">
      <figure class="media">
        <img src="{{ $visionImg }}" alt="Nuestra Visión en Papelería" loading="lazy">
        <span class="pill">Visión</span>
      </figure>
      <div class="body">
        <h3>Visión</h3>
        <p>
          Ser la plataforma de referencia en México para comprar papelería 
          en <strong>mayoreo y menudeo</strong>, con experiencia tipo <em>pedidos.com</em>,
          logística eficiente y una oferta constante de <strong>promociones</strong> y <strong>packs</strong>.
        </p>
      </div>
    </article>
  </section>

  <!-- CTA -->
  <div class="cta" data-reveal="up">
    <a href="/catalogo" class="btn">Ver Catálogo</a>
    <a href="/paquetes" class="btn btn-outline">Paquetes Oficina/Escuela</a>
    <a href="/contacto" class="btn btn-outline">Hablar con un Asesor</a>
  </div>

{{-- ====== SLIDER 3D: Papelería (FULL-BLEED y full-height) ====== --}}
<style>
  /* Full-bleed + buen responsive */
  .cs-wrap{ font-family:"Poppins",system-ui,Segoe UI,Arial; background:#fafafa; }
  .cs-wrap.full-bleed{
    width:100vw; margin-left:calc(50% - 50vw); margin-right:calc(50% - 50vw);
    padding-left:clamp(8px,2.5vw,32px); padding-right:clamp(8px,2.5vw,32px);
    padding-top:40px; padding-bottom:20px; min-height:100vh; /* alto “pantalla” */
    display:flex; flex-direction:column; align-items:center; justify-content:flex-start;
  }
  .cs-header{ text-align:center; margin-bottom: clamp(24px,5vw,48px); }
  .cs-subtitle{ color:#ff6b35; font-size:14px; font-weight:700; text-transform:uppercase; letter-spacing:2px; margin-bottom:12px; }
  .cs-title{ font-size: clamp(28px, 6vw, 56px); font-weight:900; color:#0a0a0a; line-height:1.1; margin:0; }

  .cs-slider{ perspective:1500px; perspective-origin:50% 50%; cursor:grab; width:100%; max-width:none; overflow:hidden; }
  .cs-slider.dragging{ cursor:grabbing; }
  .cs-track{ display:flex; align-items:center; justify-content:center; gap:8px; transform-style:preserve-3d; }

  .cs-card{
    flex-shrink:0; width:clamp(160px, 16vw, 240px); background:#fff; overflow:hidden;
    transform-style:preserve-3d; position:relative; cursor:pointer; border-radius:8px;
  }
  .cs-card::before{ content:""; position:absolute; inset:0; background:linear-gradient(to right, rgba(0,0,0,.15), transparent 30%, transparent 70%, rgba(0,0,0,.15)); transform: translateZ(-8px); pointer-events:none; }
  .cs-card::after{ content:""; position:absolute; inset:0; background:#e0e0e0; transform: translateZ(-16px); box-shadow:0 0 40px rgba(0,0,0,.3); pointer-events:none; }
  .cs-card img{ width:100%; height:100%; object-fit:cover; display:block; pointer-events:none; position:relative; z-index:1; }
  .cs-card .cs-hover{ position:absolute; inset:0; background:rgba(0,0,0,.7); display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity .3s ease; z-index:2; }
  .cs-card:hover .cs-hover{ opacity:1; }
  .cs-hover span{ color:#fff; font-size:16px; font-weight:600; text-transform:uppercase; letter-spacing:1px; }
  .cs-track.blurred .cs-card:not(.expanded){ filter: blur(8px); transition: filter .6s ease; }
  .cs-card.expanded{ z-index:1000 !important; }
  .cs-info{ position:fixed; bottom: clamp(16px,4vw,80px); left:50%; transform:translateX(-50%); text-align:center; opacity:0; pointer-events:none; transition:opacity .6s ease; z-index:1001; max-width:min(600px, 90vw); padding:1.25rem; background:#ff6b35; box-shadow:4px 3px 18px 4px #b7b7b721; border-radius:12px; }
  .cs-info.visible{ opacity:1; pointer-events:auto; }
  .cs-info h2{ font-size:clamp(20px,3.2vw,32px); font-weight:900; color:#0a0a0a; margin:0 0 8px; }
  .cs-info p{ font-size:clamp(14px,2.6vw,18px); color:#080808; line-height:1.6; margin:0; }
  .cs-close{ position:fixed; top:clamp(12px,3vw,40px); right:clamp(12px,3vw,40px); width:clamp(44px,3.6vw,60px); height:clamp(44px,3.6vw,60px); background:#fff; border:0; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; z-index:1002; opacity:0; pointer-events:none; transition: all .3s ease; box-shadow:0 8px 25px rgba(0,0,0,.2); }
  .cs-close.visible{ opacity:1; pointer-events:auto; }
  .cs-close:hover{ background:#ff6b35; color:#fff; transform: rotate(90deg) scale(1.05); }
  .cs-close svg{ width:24px; height:24px; }

  /* ===== Landing Sections (público) ===== */
  :root{
    --lp-ink:#0e1726; --lp-muted:#6b7280; --lp-line:#e8eef6;
    --lp-radius:16px; --lp-shadow:0 18px 50px rgba(2,8,23,.10);
  }

  .lp-wrap{margin:clamp(24px,5vw,48px) auto; padding:0 clamp(12px,3vw,16px); max-width:1200px}
  .lp-head{margin-bottom:12px}
  .lp-head h2{font-size:clamp(18px,2vw,22px); color:var(--lp-ink); margin:0}

  .lp-stage{
    border:1px solid var(--lp-line);
    border-radius:16px;
    background:
      radial-gradient(800px 400px at 0% 0%, #f1f6ff 0%, transparent 40%),
      radial-gradient(800px 400px at 120% -20%, #fff0f5 0%, transparent 40%),
      #fff;
    padding:12px;
  }

  .lp-grid{display:grid; gap:12px}
  /* Plantillas de grid (igual que preview) */
  .lp-grid-banner{grid-template-columns:1fr}
  .lp-grid-1{grid-template-columns:1fr}
  .lp-grid-2{grid-template-columns:repeat(2,1fr)}
  .lp-grid-3{grid-template-columns:repeat(3,1fr)}
  @media (max-width:980px){
    .lp-grid-2{grid-template-columns:1fr}
    .lp-grid-3{grid-template-columns:repeat(2,1fr)}
  }
  @media (max-width:560px){
    .lp-grid-3{grid-template-columns:1fr}
  }

  /* Card (igual que preview) */
  .lp-card{
    position:relative; border-radius:16px; overflow:hidden; background:#fff;
    border:1px solid #e9eef7; transform:translateZ(0);
    transition: transform .18s ease, box-shadow .22s ease;
  }
  .lp-card:hover{ transform: translateY(-3px) scale(1.01); box-shadow: var(--lp-shadow); }
  .lp-card .img{
    width:100%; aspect-ratio:16/9; object-fit:cover; display:block; background:#eef2f7;
  }

  /* Overlay texto */
  .lp-card .txt{
    position:absolute; left:0; right:0; bottom:0;
    padding:16px; color:#fff;
    background:linear-gradient(180deg, transparent, rgba(0,0,0,.45));
  }
  .lp-card .t1{font-weight:700; font-size:clamp(14px,1.2vw,16px)}
  .lp-card .t2{opacity:.9; font-size:13px; margin-top:2px}

  /* CTA pill */
  .lp-card .cta{
    display:inline-flex; gap:6px; align-items:center;
    background:rgba(255,255,255,.95); color:#0b1220;
    border-radius:999px; padding:6px 10px; margin-top:10px; font-size:13px;
    text-decoration:none; border:1px solid #e5e7eb;
    transition:transform .15s ease, box-shadow .2s ease, background .2s;
  }
  .lp-card .cta:hover{ transform:translateY(-1px); box-shadow:0 8px 20px rgba(2,8,23,.12); background:#fff }

  /* Estado vacío */
  .lp-empty{
    padding:18px; color:var(--lp-muted); text-align:center; border:1px dashed var(--lp-line);
    border-radius:12px; background:#f9fafb;
  }

  /* Aparecer al hacer scroll (Intersection Observer) */
  .ao{ opacity:0; transform:translateY(8px); transition: opacity .45s ease, transform .45s ease }
  .ao.in{ opacity:1; transform:none }

  /* Material Symbols minimal si la usas */
  .mi{ font-family:'Material Symbols Outlined', sans-serif; font-variation-settings: 'wght' 500; vertical-align:-2px }

  /* 1) Permitir scroll vertical natural en el área del slider por defecto */
  .cs-slider{
    touch-action: pan-y;          /* clave para que el navegador maneje scroll vertical */
    -ms-touch-action: pan-y;
  }
  /* 2) Opcional: evitar rebotes extraños si el slider estuviera dentro de un contenedor con scroll propio */
  .cs-wrap{
    overscroll-behavior-y: contain; /* no propagues el "pull to refresh" dentro del slider */
  }
  /* 3) Bloquear scroll del fondo cuando una tarjeta está expandida */
  .cs-lock{
    overflow: hidden;              /* bloquea scroll en HTML */
    touch-action: none;            /* evita gestos mientras está el modal/clone abierto */
  }
</style>

<section class="cs-wrap full-bleed">
  <div class="cs-header">
    <p class="cs-subtitle">Papelería & Oficina</p>
    <h1 class="cs-title">Descubre todo lo que tenemos para tu día a día</h1>
  </div>

  <div class="cs-slider" id="sliderContainer">
    <div class="cs-track" id="sliderTrack">
      {{-- Tarjetas de papelería (11) con imágenes de internet (Unsplash) --}}
      <div class="cs-card" data-title="Cuadernos & libretas" data-desc="Tamaños A4/A5, rayado y cuadriculado. Marcas originales.">
        <img src="https://cdn5.coppel.com/mkp/17561629-1.jpg?iresize=width:846,height:677" alt="Cuadernos y libretas">
        <div class="cs-hover"><span>Ver más</span></div>
      </div>
      <div class="cs-card" data-title="Plumas & marcatextos" data-desc="Tinta gel, roller y permanentes. Sets escolares y de oficina.">
        <img src="https://i.pinimg.com/736x/f9/c4/58/f9c458188e6b55170ba586aff21ddab3.jpg" alt="Plumas y marcatextos">
        <div class="cs-hover"><span>Ver más</span></div>
      </div>
      <div class="cs-card" data-title="Engrapadoras & perforadoras" data-desc="Metálicas de alto rendimiento para oficina.">
        <img src="https://i.pinimg.com/1200x/45/65/57/4565575ad836ed53d1b104fb5ac3f401.jpg" alt="Engrapadora de oficina">
        <div class="cs-hover"><span>Ver más</span></div>
      </div>
      <div class="cs-card" data-title="Organización" data-desc="Folders, clips y archiveros para mantener todo en orden">
        <img src="https://i.pinimg.com/736x/8b/8a/05/8b8a054b0b816d11d774dbf08b731560.jpg" alt="Folders y archivos">
        <div class="cs-hover"><span>Ver más</span></div>
      </div>
      <div class="cs-card" data-title="Arte & dibujo" data-desc="Acuarelas, pinceles y papeles artísticos.">
        <img src="https://i.pinimg.com/1200x/da/36/b5/da36b5b96325da41d86166af49c7bf2d.jpg" alt="Material de arte y dibujo">
        <div class="cs-hover"><span>Ver más</span></div>
      </div>
      <div class="cs-card" data-title="Impresión & tintas" data-desc="Cartuchos y tóner originales. Asesoría sin costo.">
        <img src="https://i.pinimg.com/1200x/d3/6b/49/d36b49eb68359dd27b2abc6235155fc7.jpg" alt="Tintas y cartuchos para impresora">
        <div class="cs-hover"><span>Ver más</span></div>
      </div>
      <div class="cs-card" data-title="Escritorios & accesorios" data-desc="Pads, organizadores y gadgets para productividad.">
        <img src="https://i.pinimg.com/736x/de/6b/51/de6b51ba741fb89dc4296e6bc4aa309f.jpg" alt="Accesorios de escritorio y organización">
        <div class="cs-hover"><span>Ver más</span></div>
      </div>
      <div class="cs-card" data-title="Listas escolares" data-desc="Armamos tu lista completa con entrega rápida.">
        <img src="https://i.pinimg.com/736x/80/02/06/800206a7c0c1d577c26eaa740920bd72.jpg" alt="Surtido de útiles escolares">
        <div class="cs-hover"><span>Ver más</span></div>
      </div>
      <div class="cs-card" data-title="Ofertas de temporada" data-desc="Descuentos semanales en papelería y oficina.">
        <img src="https://i.pinimg.com/1200x/c0/cf/68/c0cf68f4508dad4537ade17e062bfb44.jpg" alt="Anuncio de ofertas y rebajas">
        <div class="cs-hover"><span>Ver más</span></div>
      </div>
      <div class="cs-card" data-title="Envío hoy en Toluca*" data-desc="Pedidos antes de la 1:00 pm. Cobertura sujeta a zona.">
        <img src="https://i.pinimg.com/736x/99/73/ae/9973ae95495ad119d5ee1894ee121f1c.jpg" alt="Mensajero entregando paquete a domicilio">
        <div class="cs-hover"><span>Ver más</span></div>
      </div>
      <div class="cs-card" data-title="Mayoristas & empresas" data-desc="Precios por volumen y facturación inmediata.">
        <img src="https://i.pinimg.com/1200x/c1/d9/50/c1d9506263dc9c5f9fe4710ec3343de5.jpg" alt="Bodega con cajas para mayoreo">
        <div class="cs-hover"><span>Ver más</span></div>
      </div>
    </div>
  </div>

  <button class="cs-close" id="closeBtn" aria-label="Cerrar">
    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
    </svg>
  </button>

  <div class="cs-info" id="cardInfo" aria-live="polite">
    <h2 id="cardTitle"></h2>
    <p id="cardDesc"></p>
  </div>
</section>

<script>
  // ====== Posiciones y animación del carrusel 3D (idénticas a las tuyas) ======
  const csPositions = [
    { height:620, z:220, rotateY:48,  y:0, clip:"polygon(0px 0px, 100% 10%, 100% 90%, 0px 100%)" },
    { height:580, z:165, rotateY:35,  y:0, clip:"polygon(0px 0px, 100% 8%, 100% 92%, 0px 100%)" },
    { height:495, z:110, rotateY:15,  y:0, clip:"polygon(0px 0px, 100% 7%, 100% 93%, 0px 100%)" },
    { height:420, z:66,  rotateY:15,  y:0, clip:"polygon(0px 0px, 100% 7%, 100% 93%, 0px 100%)" },
    { height:353, z:46,  rotateY:6,   y:0, clip:"polygon(0px 0px, 100% 7%, 100% 93%, 0px 100%)" },
    { height:310, z:0,   rotateY:0,   y:0, clip:"polygon(0 0, 100% 0, 100% 100%, 0 100%)" },
    { height:353, z:54,  rotateY:348, y:0, clip:"polygon(0px 7%, 100% 0px, 100% 100%, 0px 93%)" },
    { height:420, z:89,  rotateY:-15, y:0, clip:"polygon(0px 7%, 100% 0px, 100% 100%, 0px 93%)" },
    { height:495, z:135, rotateY:-15, y:1, clip:"polygon(0px 7%, 100% 0px, 100% 100%, 0px 93%)" },
    { height:580, z:195, rotateY:325, y:0, clip:"polygon(0px 8%, 100% 0px, 100% 100%, 0px 92%)" },
    { height:620, z:240, rotateY:312, y:0, clip:"polygon(0px 10%, 100% 0px, 100% 100%, 0px 90%)" }
  ];

  class CircularSlider {
    constructor(){
      this.container   = document.getElementById('sliderContainer');
      this.track       = document.getElementById('sliderTrack');
      this.cards       = Array.from(document.querySelectorAll('.cs-card'));
      this.total       = this.cards.length;

      // Estado de drag
      this.pointerId   = null;
      this.axisLocked  = null; // 'x' o 'y'
      this.isDragging  = false;
      this.startX      = 0;
      this.startY      = 0;
      this.dragDistance= 0;
      this.threshold   = 60;   // distancia para avanzar 1 tarjeta
      this.processedSteps = 0;
      this.expandedCard   = null;

      // UI info/close
      this.cardInfo   = document.getElementById('cardInfo');
      this.cardTitle  = document.getElementById('cardTitle');
      this.cardDesc   = document.getElementById('cardDesc');
      this.closeBtn   = document.getElementById('closeBtn');

      // ===== AUTOPLAY (añadido, no invasivo) =====
      this.autoplayMs      = 3500;  // cambia el intervalo si quieres
      this.autoTimer       = null;
      this.resumeTimeout   = null;
      this.autoplayEnabled = true;

      this.init();
    }

    init(){
      this.applyPositions();
      this.attachEvents();
      this.startAutoplay(); // inicia autoplay
    }

    applyPositions(){
      this.cards.forEach((card, i) => {
        const pos = csPositions[i % csPositions.length];
        gsap.set(card, {
          height: pos.height,
          clipPath: pos.clip,
          transform: `translateZ(${pos.z}px) rotateY(${pos.rotateY}deg) translateY(${pos.y}px)`
        });
      });
    }

    expandCard(card){
      if(this.expandedCard) return;

      // Pausa autoplay durante expandido
      this.stopAutoplay();

      this.expandedCard = card;
      this.cardTitle.textContent = card.dataset.title || '';
      this.cardDesc.textContent  = card.dataset.desc  || '';

      const rect   = card.getBoundingClientRect();
      const clone  = card.cloneNode(true);
      const hover  = clone.querySelector('.cs-hover'); if(hover) hover.remove();

      Object.assign(clone.style, {
        position:'fixed', left: rect.left+'px', top: rect.top+'px',
        width: rect.width+'px', height: rect.height+'px', margin:'0', zIndex:'1000'
      });
      clone.classList.add('clone');
      document.body.appendChild(clone);
      this.cardClone = clone;

      gsap.set(card, { opacity:0 });
      this.track.classList.add('blurred');

      // Bloquear scroll del fondo mientras está expandida
      document.documentElement.classList.add('cs-lock');

      const maxHeight   = window.innerHeight * 0.8;
      const finalWidth  = Math.min(520, window.innerWidth - 32);
      const finalHeight = Math.min(650, maxHeight);
      const centerX     = window.innerWidth / 2;
      const centerY     = window.innerHeight / 2;

      gsap.to(clone, {
        width: finalWidth, height: finalHeight,
        left: centerX - finalWidth/2, top: centerY - finalHeight/2,
        clipPath: 'polygon(0 0, 100% 0, 100% 100%, 0 100%)',
        transform: 'translateZ(0) rotateY(0deg)',
        duration: .8, ease: 'power2.out',
        onComplete: () => {
          this.cardInfo.classList.add('visible');
          this.closeBtn.classList.add('visible');
        }
      });
    }

    closeCard(){
      if(!this.expandedCard) return;
      this.cardInfo.classList.remove('visible');
      this.closeBtn.classList.remove('visible');

      const card = this.expandedCard;
      const clone= this.cardClone;
      const rect = card.getBoundingClientRect();
      const index= this.cards.indexOf(card);
      const pos  = csPositions[index % csPositions.length];

      gsap.to(clone, {
        width: rect.width, height: rect.height, left: rect.left, top: rect.top, clipPath: pos.clip,
        duration:.8, ease:'power2.out',
        onComplete: () => {
          clone.remove();
          gsap.set(card, { opacity:1 });
          this.track.classList.remove('blurred');
          this.expandedCard=null; this.cardClone=null;
          // Rehabilitar scroll del fondo
          document.documentElement.classList.remove('cs-lock');
          // Reanudar autoplay suave
          this.resetAutoplay(800);
        }
      });
    }

    rotate(direction){
      if(this.expandedCard) return;

      this.cards.forEach((card, index) => {
        const newIndex = direction === 'next'
          ? (index - 1 + this.total) % this.total
          : (index + 1) % this.total;
        const pos = csPositions[newIndex];

        gsap.set(card, { clipPath: pos.clip });
        gsap.to(card, { height: pos.height, duration:.5, ease:'power2.out' });
        gsap.to(card, { transform: `translateZ(${pos.z}px) rotateY(${pos.rotateY}deg) translateY(${pos.y}px)`, duration:.5, ease:'power2.out' });
      });

      if(direction === 'next'){
        const first = this.cards.shift(); this.cards.push(first); this.track.appendChild(first);
      } else {
        const last = this.cards.pop(); this.cards.unshift(last); this.track.prepend(last);
      }
    }

    // ===== AUTOPLAY: helpers (no tocan tu lógica 3D) =====
    startAutoplay(){
      if(!this.autoplayEnabled || this.autoTimer || this.expandedCard) return;
      this.autoTimer = setInterval(() => {
        if (document.visibilityState !== 'visible') return;
        if (this.isDragging || this.expandedCard || this.axisLocked === 'x') return;
        this.rotate('next');
      }, this.autoplayMs);
    }

    stopAutoplay(){
      if(this.autoTimer){ clearInterval(this.autoTimer); this.autoTimer = null; }
      if(this.resumeTimeout){ clearTimeout(this.resumeTimeout); this.resumeTimeout = null; }
    }

    resetAutoplay(delay = 1600){
      this.stopAutoplay();
      this.resumeTimeout = setTimeout(() => this.startAutoplay(), delay);
    }

    attachEvents(){
      // Click en tarjetas para expandir
      this.cards.forEach(card => {
        card.addEventListener('click', () => {
          if(!this.isDragging && !this.expandedCard){ this.expandCard(card); }
        });
      });

      this.closeBtn.addEventListener('click', () => this.closeCard());

      // Autoplay pausado por interacción del usuario (hover / drag / visibilidad)
      this.container.addEventListener('mouseenter', () => this.stopAutoplay());
      this.container.addEventListener('mouseleave', () => this.resetAutoplay(600));
      this.container.addEventListener('pointerdown', () => this.stopAutoplay());
      this.container.addEventListener('pointerup',   () => this.resetAutoplay());

      document.addEventListener('visibilitychange', () => {
        if(document.visibilityState === 'visible') this.resetAutoplay(800);
        else this.stopAutoplay();
      });
      window.addEventListener('blur',  () => this.stopAutoplay());
      window.addEventListener('focus', () => this.resetAutoplay(800));

      // ====== Pointer Events con bloqueo de eje (como lo tenías) ======
      this.container.addEventListener('pointerdown', (e) => this.onPointerDown(e));
      this.container.addEventListener('pointermove', (e) => this.onPointerMove(e));
      this.container.addEventListener('pointerup',   ()  => this.onPointerUp());
      this.container.addEventListener('pointercancel', () => this.onPointerUp());

      // Teclado
      document.addEventListener('keydown', e => {
        if(e.key === 'Escape' && this.expandedCard) this.closeCard();
        else if(e.key === 'ArrowLeft' && !this.expandedCard){ this.rotate('prev');  this.resetAutoplay(); }
        else if(e.key === 'ArrowRight' && !this.expandedCard){ this.rotate('next');  this.resetAutoplay(); }
      });
    }

    onPointerDown(e){
      if(this.expandedCard) return;
      this.pointerId  = e.pointerId;
      this.axisLocked = null;
      this.isDragging = false;
      this.startX     = e.clientX;
      this.startY     = e.clientY;
      this.dragDistance = 0;
      this.processedSteps=0;
      // No capturamos aún: dejamos que el scroll vertical fluya hasta detectar gesto horizontal
    }

    onPointerMove(e){
      if(this.expandedCard || e.pointerId !== this.pointerId) return;

      const dx = e.clientX - this.startX;
      const dy = e.clientY - this.startY;

      if(this.axisLocked === null){
        const min = 8; // px
        if(Math.abs(dx) < min && Math.abs(dy) < min) return;

        if(Math.abs(dx) > Math.abs(dy) + 4){
          this.axisLocked = 'x';
          this.isDragging = true;
          this.container.classList.add('dragging');
          this.container.setPointerCapture(this.pointerId);
          this.container.style.touchAction = 'none';
          // pausamos autoplay durante el drag
          this.stopAutoplay();
        } else {
          this.axisLocked = 'y';
          this.isDragging = false;
          return;
        }
      }

      if(this.axisLocked === 'x' && this.isDragging){
        this.dragDistance = dx;
        const steps = Math.floor(Math.abs(this.dragDistance) / this.threshold);
        if(steps > this.processedSteps){
          const dir = this.dragDistance > 0 ? 'prev' : 'next';
          this.rotate(dir);
          this.processedSteps = steps;
        }
      }
    }

    onPointerUp(){
      if(this.axisLocked === 'x'){
        try { this.container.releasePointerCapture(this.pointerId); } catch(_) {}
      }
      this.pointerId = null;
      this.axisLocked = null;
      this.isDragging = false;
      this.container.classList.remove('dragging');
      this.container.style.touchAction = '';
      // reanudar autoplay al soltar
      this.resetAutoplay();
    }
  }

  // Inicialización (igual que tenías). Exponemos la instancia para depurar si quieres.
  document.addEventListener('DOMContentLoaded', () => { window.__cs = new CircularSlider(); });
</script>

  <!-- ===== OUR TEAM (abajo) ===== -->
  <section class="team-wrap" data-reveal="up">
    <h1 class="team-title" data-reveal="up">Nuestro Equipo</h1>

    <div class="team-carousel" data-reveal="zoom">
      <button class="team-arrow left">‹</button>

      <div class="team-track">
        <div class="tcard" data-index="0">
          <img src="https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?q=80&w=1600&auto=format&fit=crop" alt="Team Member 1" loading="lazy">
        </div>
        <div class="tcard" data-index="1">
          <img src="https://images.unsplash.com/photo-1568602471122-7832951cc4c5?q=80&w=1600&auto=format&fit=crop" alt="Team Member 2" loading="lazy">
        </div>
        <div class="tcard" data-index="2">
          <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=1600&auto=format&fit=crop&q=60" alt="Team Member 3" loading="lazy">
        </div>
        <div class="tcard" data-index="3">
          <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=1600&auto=format&fit=crop&q=60" alt="Team Member 4" loading="lazy">
        </div>
        <div class="tcard" data-index="4">
          <img src="https://images.unsplash.com/photo-1655249481446-25d575f1c054?w=1600&auto=format&fit=crop&q=60" alt="Team Member 5" loading="lazy">
        </div>
        <div class="tcard" data-index="5">
          <img src="https://images.unsplash.com/photo-1655249481446-25d575f1c054?w=1600&auto=format&fit=crop&q=60" alt="Team Member 6" loading="lazy">
        </div>
      </div>

      <button class="team-arrow right">›</button>
    </div>

    <div class="team-info" data-reveal="up">
      <h2 class="team-name">Rene Rtort</h2>
      <p class="team-role">Fundador</p>
    </div>

    <div class="team-dots" data-reveal="up" data-stagger>
      <div class="team-dot active" data-index="0" style="--i:0"></div>
      <div class="team-dot" data-index="1" style="--i:1"></div>
      <div class="team-dot" data-index="2" style="--i:2"></div>
      <div class="team-dot" data-index="3" style="--i:3"></div>
      <div class="team-dot" data-index="4" style="--i:4"></div>
      <div class="team-dot" data-index="5" style="--i:5"></div>
    </div>
  </section>
</div>

<script>
  // ===== Respect reduced motion
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ===== Page enter: mark loaded to trigger CSS transitions
  document.addEventListener('DOMContentLoaded', () => {
    requestAnimationFrame(() => document.querySelector('.container')?.classList.remove('page-enter'));
    requestAnimationFrame(() => document.body.classList.add('is-loaded'));
  });

  // ===== Parallax for hero background (very subtle)
  const hero = document.querySelector('.hero');
  if(hero && !reduce){
    window.addEventListener('scroll', () => {
      const y = Math.min(40, window.scrollY * 0.06);
      hero.style.setProperty('--parallax', y + 'px');
    }, {passive:true});
  }

  // ===== Reveal on scroll with IntersectionObserver
  const io = (!reduce && 'IntersectionObserver' in window) ? new IntersectionObserver((entries)=>{
    entries.forEach(e=>{
      if(e.isIntersecting){
        e.target.classList.add('in-view');
        io.unobserve(e.target);
      }
    });
  }, { rootMargin:'-5% 0px -5% 0px', threshold: .08 }) : null;

  if(io){
    document.querySelectorAll('[data-reveal],[data-enter]').forEach(el=> io.observe(el));
    // Stagger inside containers
    document.querySelectorAll('[data-stagger]').forEach(parent=>{
      [...parent.children].forEach((child, i)=> child.style.setProperty('--i', i));
    });
  }else{
    // Fallback: show everything
    document.querySelectorAll('[data-reveal],[data-enter]').forEach(el=> el.classList.add('in-view'));
    document.body.classList.add('is-loaded');
  }

  // ===== Scroll direction detection (optional micro-interactions)
  let lastY = window.scrollY;
  document.addEventListener('scroll', ()=>{
    const y = window.scrollY;
    document.body.classList.toggle('scrolling-down', y > lastY);
    document.body.classList.toggle('scrolling-up', y < lastY);
    lastY = y;
  }, {passive:true});

  // ===== Team carousel logic (tu existente + pequeña auto-animación de entrada)
  const teamMembers = [
    { name: "Rene Rtor", role: "Fundador" },
    { name: "Giovanni", role: "Administrativo" },
    { name: "Samantha", role: "Gerente" },
    { name: "Magali", role: "Mercadologa" },
    { name: "Lisa Anderson", role: "Marketing Manager" },
    { name: "James Wilson", role: "Product Manager" }
  ];

  const cards = document.querySelectorAll(".tcard");
  const dots = document.querySelectorAll(".team-dot");
  const nameEl = document.querySelector(".team-name");
  const roleEl = document.querySelector(".team-role");
  const leftArrow = document.querySelector(".team-arrow.left");
  const rightArrow = document.querySelector(".team-arrow.right");
  let currentIndex = 0, isAnimating = false, autoplayId = null;

  function updateCarousel(newIndex){
    if(isAnimating) return; isAnimating = true;
    currentIndex = (newIndex + cards.length) % cards.length;

    cards.forEach((card,i)=>{
      const offset = (i - currentIndex + cards.length) % cards.length;
      card.classList.remove("center","left-1","left-2","right-1","right-2","hidden");
      if(offset===0) card.classList.add("center");
      else if(offset===1) card.classList.add("right-1");
      else if(offset===2) card.classList.add("right-2");
      else if(offset===cards.length-1) card.classList.add("left-1");
      else if(offset===cards.length-2) card.classList.add("left-2");
      else card.classList.add("hidden");
    });

    dots.forEach((d,i)=> d.classList.toggle("active", i===currentIndex));

    if(nameEl && roleEl){
      nameEl.style.opacity="0"; roleEl.style.opacity="0";
      setTimeout(()=>{
        nameEl.textContent = teamMembers[currentIndex].name;
        roleEl.textContent = teamMembers[currentIndex].role;
        nameEl.style.opacity="1"; roleEl.style.opacity="1";
        isAnimating=false;
      },300);
    }else{
      isAnimating=false;
    }
  }

  function startAutoplay(){
    if(reduce) return;
    stopAutoplay();
    autoplayId = setInterval(()=> updateCarousel(currentIndex+1), 4000);
  }
  function stopAutoplay(){ if(autoplayId){ clearInterval(autoplayId); autoplayId=null; } }

  leftArrow?.addEventListener("click", ()=> { stopAutoplay(); updateCarousel(currentIndex-1); startAutoplay(); });
  rightArrow?.addEventListener("click", ()=> { stopAutoplay(); updateCarousel(currentIndex+1); startAutoplay(); });
  dots.forEach((dot,i)=> dot.addEventListener("click", ()=> { stopAutoplay(); updateCarousel(i); startAutoplay(); }));
  cards.forEach((card,i)=> card.addEventListener("click", ()=> { stopAutoplay(); updateCarousel(i); startAutoplay(); }));
  document.addEventListener("keydown", e=>{
    if(e.key==="ArrowLeft") { stopAutoplay(); updateCarousel(currentIndex-1); startAutoplay(); }
    if(e.key==="ArrowRight"){ stopAutoplay(); updateCarousel(currentIndex+1); startAutoplay(); }
  });

  // Swipe
  let touchStartX=0, touchEndX=0;
  document.addEventListener("touchstart", e=>{ touchStartX = e.changedTouches[0].screenX; }, {passive:true});
  document.addEventListener("touchend", e=>{
    touchEndX = e.changedTouches[0].screenX;
    const diff = touchStartX - touchEndX;
    if(Math.abs(diff)>50){ diff>0 ? updateCarousel(currentIndex+1) : updateCarousel(currentIndex-1); stopAutoplay(); startAutoplay(); }
  });

  // Init
  updateCarousel(0);
  startAutoplay();
</script>
@endsection
