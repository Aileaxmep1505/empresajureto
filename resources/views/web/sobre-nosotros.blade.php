@extends('layouts.web') 
@section('title','Sobre Nosotros')

@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

<style>
  :root {
    --bg-pure: #ffffff;
    --text-dark: #0f172a;
    --text-gray: #475569;
    --border-light: #e2e8f0;
    --accent: #0f172a;
    --t1: .45s cubic-bezier(.2,.7,.2,1);
  }

  body, html {
    background-color: var(--bg-pure);
    margin: 0;
    padding: 0;
    font-family: 'Inter', system-ui, sans-serif;
    overflow-x: hidden;
  }

  .formal-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 60px 24px;
    overflow: hidden;
  }

  /* ===== HERO: SOBRE NOSOTROS ===== */
  .about-hero {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 60px;
    min-height: 70vh;
  }

  .hero-visuals {
    flex: 1;
    position: relative;
    height: 500px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .img-card {
    position: absolute;
    border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
    object-fit: cover;
    will-change: transform;
  }
  
  .img-back {
    width: 280px;
    height: 380px;
    left: 10%;
    top: 5%;
    z-index: 1;
    filter: grayscale(20%);
  }

  .img-front {
    width: 320px;
    height: 420px;
    right: 5%;
    bottom: 5%;
    z-index: 2;
    border: 8px solid var(--bg-pure);
  }

  .hero-text {
    flex: 1;
    max-width: 500px;
  }

  .hero-text h1 {
    font-size: clamp(2.5rem, 4vw, 3.5rem);
    color: var(--text-dark);
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.03em;
    margin-bottom: 24px;
  }

  .hero-text p {
    font-size: 1.125rem;
    color: var(--text-gray);
    line-height: 1.7;
    margin-bottom: 40px;
  }

  .btn-formal {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 16px 32px;
    background-color: var(--accent);
    color: #ffffff;
    font-weight: 600;
    font-size: 1rem;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
    border: 1px solid var(--accent);
  }

  .btn-formal:hover {
    background-color: transparent;
    color: var(--accent);
    transform: translateY(-2px);
  }

  /* ===== MISIÓN Y VISIÓN ===== */
  .mv-section {
    margin-top: 100px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
  }

  .mv-card {
    background: var(--bg-pure);
    border: 1px solid var(--border-light);
    border-radius: 16px;
    padding: 48px;
    transition: box-shadow 0.4s ease, transform 0.4s ease;
    position: relative;
    overflow: hidden;
  }

  .mv-card:hover {
    box-shadow: 0 20px 40px -10px rgba(0,0,0,0.08);
    transform: translateY(-5px);
    border-color: transparent;
  }

  .mv-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 0;
    height: 4px;
    background: var(--text-dark);
    transition: width 0.4s ease;
  }

  .mv-card:hover::before {
    width: 100%;
  }

  .mv-icon {
    font-size: 2rem;
    margin-bottom: 24px;
    color: var(--text-dark);
  }

  .mv-card h2 {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 16px;
    letter-spacing: -0.02em;
  }

  .mv-card p {
    color: var(--text-gray);
    line-height: 1.7;
    font-size: 1.05rem;
    margin: 0;
  }

  /* ===== VALORES (CARRUSEL) ===== */
  .values-wrap { margin-top: 120px; padding: 20px 0; position: relative; }
  .values-title {
    font-size: clamp(36px, 5vw, 64px); font-weight: 900; text-transform: uppercase;
    letter-spacing: -0.02em; text-align: center; line-height: 1; margin: 0 0 12px;
    color: var(--text-dark);
  }
  
  .values-carousel { width: 100%; max-width: 1200px; height: 450px; position: relative; perspective: 1000px; margin: 40px auto 0; }
  .values-track { width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; position: relative; transform-style: preserve-3d; transition: transform .8s cubic-bezier(.25,.46,.45,.94); }
  
  .vcard {
    position: absolute; width: 280px; height: 380px; background: #fff; border-radius: 16px; overflow: hidden;
    box-shadow: 0 20px 40px rgba(0,0,0,.10); transition: all .8s cubic-bezier(.25,.46,.45,.94); cursor: pointer;
  }
  .vcard img { width: 100%; height: 100%; object-fit: cover; transition: inherit; }
  
  .vcard.center { z-index: 10; transform: scale(1.1) translateZ(0); }
  .vcard.left-2 { z-index: 1; transform: translateX(-400px) scale(.8) translateZ(-300px); opacity: .6; filter: grayscale(100%); }
  .vcard.left-1 { z-index: 5; transform: translateX(-200px) scale(.9) translateZ(-100px); opacity: .8; filter: grayscale(100%); }
  .vcard.right-1 { z-index: 5; transform: translateX(200px) scale(.9) translateZ(-100px); opacity: .8; filter: grayscale(100%); }
  .vcard.right-2 { z-index: 1; transform: translateX(400px) scale(.8) translateZ(-300px); opacity: .6; filter: grayscale(100%); }
  .vcard.hidden { opacity: 0; pointer-events: none; }

  .values-info { text-align: center; margin-top: 36px; transition: all .5s ease-out; }
  .value-name { color: var(--text-dark); font-size: clamp(22px, 3vw, 28px); font-weight: 700; margin: 0 0 8px; position: relative; display: inline-block; }
  .value-desc { color: var(--text-gray); font-size: clamp(14px, 2.2vw, 16px); max-width: 600px; margin: 0 auto; line-height: 1.6; }

  .values-dots { display: flex; justify-content: center; gap: 10px; margin-top: 26px; }
  .values-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--border-light); cursor: pointer; transition: all .3s ease; }
  .values-dot.active { background: var(--text-dark); transform: scale(1.3); }

  .values-arrow {
    position: absolute; top: 50%; transform: translateY(-50%); background: var(--bg-pure); color: var(--text-dark);
    width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
    cursor: pointer; z-index: 20; transition: all .3s ease; font-size: 1.5rem; border: 1px solid var(--border-light); outline: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  }
  .values-arrow:hover { background: var(--text-dark); color: #fff; transform: translateY(-50%) scale(1.05); border-color: transparent; }
  .values-arrow.left { left: 10px; } .values-arrow.right { right: 10px; }

  /* Responsive general */
  @media (max-width: 968px) {
    .about-hero { flex-direction: column-reverse; text-align: center; gap: 40px; }
    .hero-visuals { width: 100%; height: 400px; }
    .img-back { width: 220px; height: 300px; left: 5%; }
    .img-front { width: 260px; height: 340px; right: 5%; }
    .mv-section { grid-template-columns: 1fr; }
    
    .values-carousel { height: 320px; margin-top: 20px;}
    .vcard { width: 200px; height: 280px; }
    .vcard.left-2 { transform: translateX(-200px) scale(.8) translateZ(-300px); }
    .vcard.left-1 { transform: translateX(-100px) scale(.9) translateZ(-100px); }
    .vcard.right-1 { transform: translateX(100px) scale(.9) translateZ(-100px); }
    .vcard.right-2 { transform: translateX(200px) scale(.8) translateZ(-300px); }
  }
</style>

<div class="formal-container">
  
  <section class="about-hero">
    <div class="hero-visuals">
      <img src="https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=1200&auto=format&fit=crop" alt="Oficina corporativa" class="img-card img-back">
      <img src="https://images.unsplash.com/photo-1600880292203-757bb62b4baf?q=80&w=1200&auto=format&fit=crop" alt="Equipo de trabajo" class="img-card img-front">
    </div>
    
    <div class="hero-text">
      <h1 class="gsap-title">Sobre Nosotros</h1>
      <p class="gsap-text">
        Somos tu aliado estratégico en abastecimiento. Ofrecemos soluciones integrales con precios competitivos, logística eficiente y un catálogo optimizado para satisfacer las exigencias de tu empresa o negocio, al nivel de las grandes plataformas corporativas.
      </p>
      <a href="{{ url('/contacto') }}" class="btn-formal gsap-btn">Centro de Ayuda</a>
    </div>
  </section>

  <section class="mv-section">
    <article class="mv-card gsap-card">
      <div class="mv-icon">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 16 16 12 12 8"></polyline><line x1="8" y1="12" x2="16" y2="12"></line></svg>
      </div>
      <h2>Nuestra Misión</h2>
      <p>Abastecer al sector corporativo y educativo con productos de la más alta calidad, garantizando eficiencia en la entrega y estructuras de costos escalonadas que protejan y potencien la rentabilidad de nuestros clientes.</p>
    </article>

    <article class="mv-card gsap-card">
      <div class="mv-icon">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h4l3-9 5 18 3-9h5"></path></svg>
      </div>
      <h2>Nuestra Visión</h2>
      <p>Posicionarnos como el principal referente nacional en la distribución inteligente de suministros, destacando por nuestra plataforma tecnológica ágil, transparencia operativa y compromiso inquebrantable con la excelencia comercial.</p>
    </article>
  </section>

  <section class="values-wrap gsap-values">
    <h1 class="values-title">Nuestros Valores</h1>

    <div class="values-carousel">
      <button class="values-arrow left">‹</button>

      <div class="values-track">
        <div class="vcard" data-index="0">
          <img src="https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1600&q=80" alt="Innovación" loading="lazy">
        </div>
        <div class="vcard" data-index="1">
          <img src="https://images.unsplash.com/photo-1556761175-4b46a572b786?auto=format&fit=crop&w=1600&q=80" alt="Integridad" loading="lazy">
        </div>
        <div class="vcard" data-index="2">
          <img src="https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=1600&q=80" alt="Excelencia" loading="lazy">
        </div>
        <div class="vcard" data-index="3">
          <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1600&q=80" alt="Compromiso" loading="lazy">
        </div>
        <div class="vcard" data-index="4">
          <img src="https://images.unsplash.com/photo-1600880292203-757bb62b4baf?auto=format&fit=crop&w=1600&q=80" alt="Colaboración" loading="lazy">
        </div>
        <div class="vcard" data-index="5">
          <img src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1600&q=80" alt="Transparencia" loading="lazy">
        </div>
      </div>

      <button class="values-arrow right">›</button>
    </div>

    <div class="values-info">
      <h2 class="value-name">Innovación</h2>
      <p class="value-desc">Adoptamos la tecnología para superar las expectativas operativas.</p>
    </div>

    <div class="values-dots">
      <div class="values-dot active" data-index="0"></div>
      <div class="values-dot" data-index="1"></div>
      <div class="values-dot" data-index="2"></div>
      <div class="values-dot" data-index="3"></div>
      <div class="values-dot" data-index="4"></div>
      <div class="values-dot" data-index="5"></div>
    </div>
  </section>

</div>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    // 1. GSAP ScrollTrigger Init
    gsap.registerPlugin(ScrollTrigger);

    // Animación de entrada Hero
    const tlHero = gsap.timeline();
    tlHero.fromTo(".gsap-title", 
      { opacity: 0, y: 30 }, 
      { opacity: 1, y: 0, duration: 0.8, ease: "power3.out" }
    )
    .fromTo(".gsap-text", 
      { opacity: 0, y: 20 }, 
      { opacity: 1, y: 0, duration: 0.8, ease: "power3.out" },
      "-=0.5"
    )
    .fromTo(".gsap-btn", 
      { opacity: 0, y: 20 }, 
      { opacity: 1, y: 0, duration: 0.8, ease: "power3.out" },
      "-=0.5"
    );

    // Levitación sutil de imágenes Hero
    gsap.to(".img-back", { y: -15, duration: 3, repeat: -1, yoyo: true, ease: "sine.inOut" });
    gsap.to(".img-front", { y: 15, duration: 4, repeat: -1, yoyo: true, ease: "sine.inOut" });

    // Animación Misión/Visión al hacer scroll
    gsap.utils.toArray('.gsap-card').forEach((card, i) => {
      gsap.fromTo(card, 
        { opacity: 0, y: 50 }, 
        {
          scrollTrigger: {
            trigger: card,
            start: "top 85%",
            toggleActions: "play none none reverse"
          },
          opacity: 1, y: 0, duration: 0.8, delay: i * 0.2, ease: "power3.out"
        }
      );
    });

    // Animación Sección Valores al hacer scroll
    gsap.fromTo(".gsap-values", 
      { opacity: 0, y: 60 }, 
      {
        scrollTrigger: {
          trigger: ".gsap-values",
          start: "top 80%",
        },
        opacity: 1, y: 0, duration: 0.8, ease: "power3.out"
      }
    );

    // 2. Lógica del Carrusel de Valores
    const companyValues = [
      { name: "Innovación", desc: "Adoptamos la tecnología para superar las expectativas operativas." },
      { name: "Integridad", desc: "Actuamos con rectitud en cada uno de nuestros procesos corporativos." },
      { name: "Excelencia", desc: "Diseñamos soluciones con el más alto estándar de calidad del mercado." },
      { name: "Compromiso", desc: "Nos dedicamos al éxito a largo plazo de los proyectos de nuestros clientes." },
      { name: "Colaboración", desc: "Trabajamos en sinergia para alcanzar objetivos estratégicos comunes." },
      { name: "Transparencia", desc: "Comunicación clara, honesta y directa en todos los niveles comerciales." }
    ];

    const cards = document.querySelectorAll(".vcard");
    const dots = document.querySelectorAll(".values-dot");
    const nameEl = document.querySelector(".value-name");
    const descEl = document.querySelector(".value-desc");
    const leftArrow = document.querySelector(".values-arrow.left");
    const rightArrow = document.querySelector(".values-arrow.right");
    let currentIndex = 0, isAnimating = false, autoplayId = null;
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function updateCarousel(newIndex){
      if(isAnimating) return; isAnimating = true;
      currentIndex = (newIndex + cards.length) % cards.length;

      cards.forEach((card, i) => {
        const offset = (i - currentIndex + cards.length) % cards.length;
        card.classList.remove("center","left-1","left-2","right-1","right-2","hidden");
        if(offset===0) card.classList.add("center");
        else if(offset===1) card.classList.add("right-1");
        else if(offset===2) card.classList.add("right-2");
        else if(offset===cards.length-1) card.classList.add("left-1");
        else if(offset===cards.length-2) card.classList.add("left-2");
        else card.classList.add("hidden");
      });

      dots.forEach((d, i) => d.classList.toggle("active", i === currentIndex));

      if(nameEl && descEl){
        gsap.to([nameEl, descEl], { opacity: 0, y: 10, duration: 0.2, onComplete: () => {
          nameEl.textContent = companyValues[currentIndex].name;
          descEl.textContent = companyValues[currentIndex].desc;
          gsap.to([nameEl, descEl], { opacity: 1, y: 0, duration: 0.3 });
          isAnimating = false;
        }});
      } else {
        isAnimating = false;
      }
    }

    function startAutoplay(){
      if(prefersReducedMotion) return;
      stopAutoplay();
      autoplayId = setInterval(() => updateCarousel(currentIndex + 1), 4000);
    }
    function stopAutoplay(){ if(autoplayId){ clearInterval(autoplayId); autoplayId = null; } }

    leftArrow?.addEventListener("click", () => { stopAutoplay(); updateCarousel(currentIndex - 1); startAutoplay(); });
    rightArrow?.addEventListener("click", () => { stopAutoplay(); updateCarousel(currentIndex + 1); startAutoplay(); });
    dots.forEach((dot, i) => dot.addEventListener("click", () => { stopAutoplay(); updateCarousel(i); startAutoplay(); }));
    cards.forEach((card, i) => card.addEventListener("click", () => { stopAutoplay(); updateCarousel(i); startAutoplay(); }));
    
    document.addEventListener("keydown", e => {
      if(e.key === "ArrowLeft") { stopAutoplay(); updateCarousel(currentIndex - 1); startAutoplay(); }
      if(e.key === "ArrowRight"){ stopAutoplay(); updateCarousel(currentIndex + 1); startAutoplay(); }
    });

    // Swipe para móviles
    let touchStartX = 0, touchEndX = 0;
    document.querySelector('.values-carousel').addEventListener("touchstart", e => { touchStartX = e.changedTouches[0].screenX; }, {passive:true});
    document.querySelector('.values-carousel').addEventListener("touchend", e => {
      touchEndX = e.changedTouches[0].screenX;
      const diff = touchStartX - touchEndX;
      if(Math.abs(diff) > 50){ diff > 0 ? updateCarousel(currentIndex + 1) : updateCarousel(currentIndex - 1); stopAutoplay(); startAutoplay(); }
    });

    // Iniciar
    updateCarousel(0);
    startAutoplay();
  });
</script>
@endsection