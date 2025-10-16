{{-- resources/views/web/home.blade.php (completo con slider 3D full-bleed) --}}
@extends('layouts.web') 
@section('title','Inicio')

@section('content')

  {{-- ====== Fuentes + GSAP para el slider 3D ====== --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>

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

    /* ===== PRODUCTO: Card estilo "Paul Atreides" ===== */
    .product-card{
      --bg:#fff; --title:#fff; --title-hover:#000; --text:#666;
      --btn:#eee; --btn-hover:#ddd;
      background:var(--bg); border-radius:24px; padding:8px; height:30rem; width:100%;
      position:relative; overflow:clip; box-shadow:var(--shadow); display:block;
      font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      outline:none;
    }
    .product-card.dark{ --bg:#222; --title:#fff; --title-hover:#fff; --text:#ccc; --btn:#555; --btn-hover:#444; }
    .product-card::before{
      content:""; position:absolute; width:calc(100% - 1rem); height:30%;
      bottom:.5rem; left:.5rem; border-radius:0 0 24px 24px;
      mask:linear-gradient(#0000, #000f 80%); -webkit-mask:linear-gradient(#0000, #000f 80%);
      backdrop-filter: blur(16px); translate:0 0; transition: translate .25s;
    }
    .product-card > .pc-img{
      width:100%; aspect-ratio:2/3; object-fit:cover; object-position:50% 5%;
      border-radius:20px; display:block; transition: aspect-ratio .25s, object-position .5s;
      background:#f6f8fc;
    }
    .pc-badges{ position:absolute; top:12px; left:12px; display:flex; gap:8px; z-index:2; }
    .pc-badge{
      font-weight:800; font-size:12px; padding:6px 10px; border-radius:999px;
      backdrop-filter:saturate(1.6) blur(4px);
      border:1px solid rgba(255,255,255,.6); box-shadow:0 2px 10px rgba(0,0,0,.05);
    }
    .pc-badge--new{ background:rgba(110,168,254,.18); color:#1d4ed8; }
    .pc-badge--sale{ background:rgba(22,163,74,.18); color:#166534; }
    .pc-badge--off{ background:rgba(234,179,8,.18); color:#854d0e; }
    .product-card > section{ margin:1rem; height:calc(33.333% - 1rem); display:flex; flex-direction:column; }
    .product-card h3{
      margin:0 0 1rem 0; font-size:1.15rem; line-height:1.2; font-weight:900;
      color:var(--title); translate:0 -200%; opacity:1; transition: color .5s, margin .25s, opacity 1s, translate .25s;
      display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
    }
    .product-card p{
      font-size:.95rem; line-height:1.35; color:var(--text); margin:0;
      translate:0 100%; opacity:0; transition: margin .25s, opacity 1s .2s, translate .25s .2s;
    }
    .pc-foot{ flex:1; display:flex; justify-content:space-between; align-items:flex-end; gap:8px;
      translate:0 100%; opacity:0; transition: translate .25s .2s, opacity 1s; }
    .pc-tag{ align-self:center; color:var(--title-hover); font-weight:800; font-size:.9rem; }
    .pc-btn{
      border:1px solid transparent; border-radius:20px 20px 24px 20px; font-weight:800;
      font-size:1rem; padding:1rem 1.5rem 1rem 2.75rem; background:var(--btn);
      transition: background .33s; outline-offset:2px; position:relative; color:var(--title-hover);
    }
    .pc-btn::before, .pc-btn::after{
      content:""; width:.85rem; height:.1rem; background:currentColor; position:absolute; top:50%; left:1.33rem; border-radius:1rem;
    }
    .pc-btn::after{ rotate:90deg; transition: rotate .15s; }
    .pc-btn.is-added::after{ rotate:0deg; }
    .pc-btn:hover{ background:var(--btn-hover); }
    .product-card:hover::before, .product-card:focus-within::before{ translate:0 100%; }
    .product-card:hover > .pc-img, .product-card:focus-within > .pc-img{ aspect-ratio:1/1; object-position:50% 10%; }
    .product-card:hover h3, .product-card:hover p,
    .product-card:focus-within h3, .product-card:focus-within p{ translate:0 0; margin-bottom:.5rem; opacity:1; }
    .product-card:hover h3, .product-card:focus-within h3{ color:var(--title-hover); }
    .product-card:hover .pc-foot, .product-card:focus-within .pc-foot{ translate:0 0; opacity:1; }
    .pc-price{ font-weight:900; }
    .pc-price--old{ color:var(--muted); text-decoration:line-through; font-weight:700; margin-left:6px; font-size:.9rem; }
  </style>

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
  <img src="https://unsplash.com/photos/m_qYW5r5iWw/download?force=true&w=1200&h=800&fit=crop" alt="Cuadernos y libretas">
  <div class="cs-hover"><span>Ver más</span></div>
</div>

<div class="cs-card" data-title="Plumas & marcatextos" data-desc="Tinta gel, roller y permanentes. Sets escolares y de oficina.">
  <img src="https://unsplash.com/photos/VK620qNCUKo/download?force=true&w=1200&h=800&fit=crop" alt="Plumas y marcatextos">
  <div class="cs-hover"><span>Ver más</span></div>
</div>

<div class="cs-card" data-title="Engrapadoras & perforadoras" data-desc="Metálicas de alto rendimiento para oficina.">
  <img src="https://unsplash.com/photos/6WLcOFn4HKE/download?force=true&w=1200&h=800&fit=crop" alt="Engrapadora de oficina">
  <div class="cs-hover"><span>Ver más</span></div>
</div>

<div class="cs-card" data-title="Organización" data-desc="Folders, clips y archiveros para mantener todo en orden">
  <img src="https://unsplash.com/photos/SiJt15u6Yw4/download?force=true&w=1200&h=800&fit=crop" alt="Folders y archivos">
  <div class="cs-hover"><span>Ver más</span></div>
</div>

<div class="cs-card" data-title="Arte & dibujo" data-desc="Acuarelas, pinceles y papeles artísticos.">
  <img src="https://unsplash.com/photos/W_6LrBZhLJY/download?force=true&w=1200&h=800&fit=crop" alt="Material de arte y dibujo">
  <div class="cs-hover"><span>Ver más</span></div>
</div>

<div class="cs-card" data-title="Impresión & tintas" data-desc="Cartuchos y tóner originales. Asesoría sin costo.">
  <img src="https://unsplash.com/photos/wONAIYtLfPc/download?force=true&w=1200&h=800&fit=crop" alt="Tintas y cartuchos para impresora">
  <div class="cs-hover"><span>Ver más</span></div>
</div>

<div class="cs-card" data-title="Escritorios & accesorios" data-desc="Pads, organizadores y gadgets para productividad.">
  <img src="https://unsplash.com/photos/df9SD08fQfQ/download?force=true&w=1200&h=800&fit=crop" alt="Accesorios de escritorio y organización">
  <div class="cs-hover"><span>Ver más</span></div>
</div>

<div class="cs-card" data-title="Listas escolares" data-desc="Armamos tu lista completa con entrega rápida.">
  <img src="https://unsplash.com/photos/yg4cdXN_6P0/download?force=true&w=1200&h=800&fit=crop" alt="Surtido de útiles escolares">
  <div class="cs-hover"><span>Ver más</span></div>
</div>

<div class="cs-card" data-title="Ofertas de temporada" data-desc="Descuentos semanales en papelería y oficina.">
  <img src="https://unsplash.com/photos/pZiZyRuXJFE/download?force=true&w=1200&h=800&fit=crop" alt="Anuncio de ofertas y rebajas">
  <div class="cs-hover"><span>Ver más</span></div>
</div>

<div class="cs-card" data-title="Envío hoy en Toluca*" data-desc="Pedidos antes de la 1:00 pm. Cobertura sujeta a zona.">
  <img src="https://unsplash.com/photos/kkeHKhLNSXk/download?force=true&w=1200&h=800&fit=crop" alt="Mensajero entregando paquete a domicilio">
  <div class="cs-hover"><span>Ver más</span></div>
</div>

<div class="cs-card" data-title="Mayoristas & empresas" data-desc="Precios por volumen y facturación inmediata.">
  <img src="https://unsplash.com/photos/28b8xlTT5t4/download?force=true&w=1200&h=800&fit=crop" alt="Bodega con cajas para mayoreo">
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
    // Posiciones y animación del carrusel 3D
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
        this.container = document.getElementById('sliderContainer');
        this.track     = document.getElementById('sliderTrack');
        this.cards     = Array.from(document.querySelectorAll('.cs-card'));
        this.total     = this.cards.length;
        this.isDragging=false; this.startX=0; this.dragDistance=0; this.threshold=60; this.processedSteps=0;
        this.expandedCard=null; this.cardInfo=document.getElementById('cardInfo');
        this.cardTitle=document.getElementById('cardTitle'); this.cardDesc=document.getElementById('cardDesc');
        this.closeBtn=document.getElementById('closeBtn');
        this.init();
      }
      init(){ this.applyPositions(); this.attachEvents(); }
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
        this.expandedCard = card;
        this.cardTitle.textContent = card.dataset.title || '';
        this.cardDesc.textContent  = card.dataset.desc  || '';

        const rect  = card.getBoundingClientRect();
        const clone = card.cloneNode(true);
        const overlay = clone.querySelector('.cs-hover'); if(overlay) overlay.remove();

        Object.assign(clone.style, {
          position:'fixed', left: rect.left+'px', top: rect.top+'px',
          width: rect.width+'px', height: rect.height+'px', margin:'0', zIndex:'1000'
        });
        clone.classList.add('clone');
        document.body.appendChild(clone);
        this.cardClone = clone;

        gsap.set(card, { opacity:0 });
        this.track.classList.add('blurred');

        const maxHeight = window.innerHeight * 0.8;
        const finalWidth = Math.min(520, window.innerWidth - 32);
        const finalHeight = Math.min(650, maxHeight);
        const centerX = window.innerWidth / 2;
        const centerY = window.innerHeight / 2;

        gsap.to(clone, {
          width: finalWidth, height: finalHeight,
          left: centerX - finalWidth/2, top: centerY - finalHeight/2,
          clipPath: 'polygon(0 0, 100% 0, 100% 100%, 0 100%)',
          transform: 'translateZ(0) rotateY(0deg)',
          duration: .8, ease: 'power2.out',
          onComplete: () => { this.cardInfo.classList.add('visible'); this.closeBtn.classList.add('visible'); }
        });
      }
      closeCard(){
        if(!this.expandedCard) return;
        this.cardInfo.classList.remove('visible'); this.closeBtn.classList.remove('visible');
        const card = this.expandedCard; const clone = this.cardClone;
        const rect = card.getBoundingClientRect();
        const index = this.cards.indexOf(card);
        const pos = csPositions[index % csPositions.length];

        gsap.to(clone, {
          width: rect.width, height: rect.height, left: rect.left, top: rect.top, clipPath: pos.clip,
          duration:.8, ease:'power2.out',
          onComplete: () => {
            clone.remove(); gsap.set(card, { opacity:1 }); this.track.classList.remove('blurred');
            this.expandedCard=null; this.cardClone=null;
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
        }else{
          const last = this.cards.pop(); this.cards.unshift(last); this.track.prepend(last);
        }
      }
      attachEvents(){
        this.cards.forEach(card => {
          card.addEventListener('click', () => { if(!this.isDragging && !this.expandedCard){ this.expandCard(card); } });
        });
        this.closeBtn.addEventListener('click', () => this.closeCard());

        this.container.addEventListener('mousedown', e => this.handleDragStart(e));
        this.container.addEventListener('touchstart', e => this.handleDragStart(e), { passive:false });

        document.addEventListener('mousemove', e => this.handleDragMove(e));
        document.addEventListener('touchmove', e => this.handleDragMove(e), { passive:false });

        document.addEventListener('mouseup', () => this.handleDragEnd());
        document.addEventListener('touchend', () => this.handleDragEnd());

        document.addEventListener('keydown', e => {
          if(e.key === 'Escape' && this.expandedCard) this.closeCard();
          else if(e.key === 'ArrowLeft' && !this.expandedCard) this.rotate('prev');
          else if(e.key === 'ArrowRight' && !this.expandedCard) this.rotate('next');
        });
      }
      handleDragStart(e){
        if(this.expandedCard) return;
        this.isDragging = true; this.container.classList.add('dragging');
        this.startX = e.type.includes('mouse') ? e.clientX : e.touches[0].clientX;
        this.dragDistance = 0; this.processedSteps = 0;
      }
      handleDragMove(e){
        if(!this.isDragging) return;
        e.preventDefault();
        const currentX = e.type.includes('mouse') ? e.clientX : e.touches[0].clientX;
        this.dragDistance = currentX - this.startX;
        const steps = Math.floor(Math.abs(this.dragDistance) / this.threshold);
        if(steps > this.processedSteps){
          const dir = this.dragDistance > 0 ? 'prev' : 'next';
          this.rotate(dir); this.processedSteps = steps;
        }
      }
      handleDragEnd(){
        if(!this.isDragging) return;
        this.isDragging = false; this.container.classList.remove('dragging');
      }
    }

    document.addEventListener('DOMContentLoaded', () => new CircularSlider());
  </script>

  {{-- ======= Hero (tu bloque original) ======= --}}
  <section class="hero card">
    <div class="container" style="position:relative; z-index:2;">
      <h1>Equipo médico y soluciones profesionales</h1>
      <p>Plataforma moderna para cotizar y comprar con confianza. Atención personalizada y soporte técnico.</p>
      <div style="margin-top:18px; display:flex; gap:10px; flex-wrap:wrap;">
        <a href="{{ route('web.ventas.index') }}" class="btn btn-primary">Ver ventas</a>
        <a href="{{ route('web.contacto') }}" class="btn btn-ghost">Contáctanos</a>
      </div>
    </div>
  </section>

  {{-- ======= Secciones administrables (LandingSection) ======= --}}
  @php
    $sections = \App\Models\LandingSection::with('items')
                ->where('is_active',true)->orderBy('sort_order')->get();
  @endphp
  @foreach($sections as $section)
    @includeFirst(['landing.render','panel.landing.render'], ['section'=>$section])
  @endforeach

  {{-- ======= Productos del catálogo (Destacados + Novedades) ======= --}}
  @php
    $featured = \App\Models\CatalogItem::published()->featured()->ordered()->take(8)->get();
    $latest   = \App\Models\CatalogItem::published()->ordered()->take(12)->get();

    $isNew = function($item){
      try{
        return $item->published_at && \Illuminate\Support\Carbon::parse($item->published_at)->gte(now()->subDays(30));
      }catch(\Throwable $e){ return false; }
    };
    $discountPct = function($item){
      if(is_null($item->sale_price) || !$item->price || $item->sale_price >= $item->price) return null;
      $pct = round(100 - (($item->sale_price / $item->price) * 100));
      return max(1, $pct);
    };
  @endphp

  @if($featured->count())
    <div class="container" style="margin-top:24px;">
      <div style="display:flex;justify-content:space-between;align-items:end;gap:10px;flex-wrap:wrap;">
        <h2 style="margin:0;font-weight:800;color:var(--ink);">Destacados</h2>
        <a class="btn btn-ghost" href="{{ route('web.catalog.index') }}">Ver catálogo</a>
      </div>

      <div class="grid" style="margin-top:12px;">
        @foreach($featured as $p)
          @php $off = $discountPct($p); @endphp
          <div class="col-3">
            <article class="product-card" tabindex="0">
              <div class="pc-badges">
                @if($isNew($p)) <span class="pc-badge pc-badge--new">Nuevo</span> @endif
                @if(!is_null($p->sale_price)) <span class="pc-badge pc-badge--sale">Oferta</span> @endif
                @if($off) <span class="pc-badge pc-badge--off">-{{ $off }}%</span> @endif
              </div>

              <a href="{{ route('web.catalog.show', $p) }}" aria-label="Ver {{ $p->name }}">
                <img class="pc-img"
                     src="{{ $p->image_url ?: asset('images/placeholder.png') }}"
                     alt="{{ $p->name }}"
                     loading="lazy"
                     onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
              </a>

              <section>
                <h3 title="{{ $p->name }}">
                  <a href="{{ route('web.catalog.show', $p) }}" style="color:inherit; text-decoration:none;">{{ $p->name }}</a>
                </h3>

                <p>
                  @if(!is_null($p->sale_price))
                    <span class="pc-price" style="color:var(--success)">${{ number_format($p->sale_price,2) }}</span>
                    <span class="pc-price--old">${{ number_format($p->price,2) }}</span>
                  @else
                    <span class="pc-price">${{ number_format($p->price,2) }}</span>
                  @endif
                </p>

                <div class="pc-foot">
                  <div class="pc-tag">SKU: {{ $p->sku ?: '—' }}</div>
                  <form action="{{ route('web.cart.add') }}" method="POST"
                        onsubmit="this.querySelector('.pc-btn').classList.add('is-added')">
                    @csrf
                    <input type="hidden" name="catalog_item_id" value="{{ $p->id }}">
                    <button type="submit" class="pc-btn" aria-label="Añadir {{ $p->name }} al carrito">
                      Añadir
                    </button>
                  </form>
                </div>
              </section>
            </article>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  @if($latest->count())
    <div class="container" style="margin-top:26px;">
      <h2 style="margin:0 0 12px;font-weight:800;color:var(--ink);">Novedades</h2>
      <div class="grid">
        @foreach($latest as $p)
          @php $off = $discountPct($p); @endphp
          <div class="col-3">
            <article class="product-card" tabindex="0">
              <div class="pc-badges">
                @if($isNew($p)) <span class="pc-badge pc-badge--new">Nuevo</span> @endif
                @if(!is_null($p->sale_price)) <span class="pc-badge pc-badge--sale">Oferta</span> @endif
                @if($off) <span class="pc-badge pc-badge--off">-{{ $off }}%</span> @endif
              </div>

              <a href="{{ route('web.catalog.show', $p) }}" aria-label="Ver {{ $p->name }}">
                <img class="pc-img"
                     src="{{ $p->image_url ?: asset('images/placeholder.png') }}"
                     alt="{{ $p->name }}"
                     loading="lazy"
                     onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
              </a>

              <section>
                <h3 title="{{ $p->name }}">
                  <a href="{{ route('web.catalog.show', $p) }}" style="color:inherit; text-decoration:none;">{{ $p->name }}</a>
                </h3>

                <p>
                  @if(!is_null($p->sale_price))
                    <span class="pc-price" style="color:var(--success)">${{ number_format($p->sale_price,2) }}</span>
                    <span class="pc-price--old">${{ number_format($p->price,2) }}</span>
                  @else
                    <span class="pc-price">${{ number_format($p->price,2) }}</span>
                  @endif
                </p>

                <div class="pc-foot">
                  <div class="pc-tag">SKU: {{ $p->sku ?: '—' }}</div>
                  <form action="{{ route('web.cart.add') }}" method="POST"
                        onsubmit="this.querySelector('.pc-btn').classList.add('is-added')">
                    @csrf
                    <input type="hidden" name="catalog_item_id" value="{{ $p->id }}">
                    <button type="submit" class="pc-btn" aria-label="Añadir {{ $p->name }} al carrito">
                      Añadir
                    </button>
                  </form>
                </div>
              </section>
            </article>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  {{-- ======= SLIDER INFINITO DE MARCAS ======= --}}
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
@endsection
