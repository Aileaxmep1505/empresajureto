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

<script>
/* Namespace .pcards – sin jQuery */
(() => {
  const root = document.querySelector('.pcards');
  if(!root) return;

  // Slide added/close
  root.addEventListener('click', e => {
    const closeBtn = e.target.closest('[data-pc-close]');
    if(closeBtn){
      const id = closeBtn.getAttribute('data-target');
      const panel = root.querySelector('#'+CSS.escape(id));
      if(panel) panel.classList.remove('is-added');
    }
  });
  root.querySelectorAll('form[data-pc-cart]').forEach(form => {
    form.addEventListener('submit', e => {
      e.preventDefault();
      const id = form.getAttribute('data-target');
      const panel = root.querySelector('#'+CSS.escape(id));
      if(panel) panel.classList.add('is-added');
      setTimeout(() => form.submit(), 320);
    });
  });

  // Esquina revelable: click/focus en móvil, hover en desktop
  root.addEventListener('click', e => {
    const corner = e.target.closest('[data-corner]');
    const close  = e.target.closest('[data-corner-close]');
    if(corner && !close){
      corner.classList.toggle('is-open');
    }
    if(close){
      const cc = close.closest('[data-corner]');
      cc && cc.classList.remove('is-open');
    }
  });

  // Cerrar esquina con ESC
  root.addEventListener('keydown', e => {
    if(e.key === 'Escape'){
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

@endsection
