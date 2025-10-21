@extends('layouts.web') 
@section('title','Sobre Nosotros')

@section('content')
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
