@extends('layouts.web')
@section('title','Formas de Envío')

@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

<style>
  /* ========= NAMESPACE AISLADO ========= */
  #ship {
    --bg-pure: #ffffff;
    --text-dark: #0f172a;
    --text-gray: #475569;
    --border-light: #e2e8f0;
    --accent: #0f172a; /* Tono corporativo oscuro */
    --link: #2563eb; /* Azul profesional para enlaces */
    --radius: 12px;
  }

  #ship {
    position: relative;
    width: 100%;
    background-color: var(--bg-pure);
    color: var(--text-dark);
    font-family: 'Inter', system-ui, sans-serif;
  }

  #ship .wrap {
    max-width: 1200px;
    margin: 0 auto;
    padding: clamp(40px, 5vw, 80px) 24px;
  }

  /* ===== Hero ===== */
  #ship .hero { margin-bottom: 60px; }
  #ship .title {
    font-size: clamp(2.5rem, 4vw, 3.5rem);
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.03em;
    color: var(--text-dark);
    margin: 0 0 16px 0;
  }
  
  #ship .sub {
    font-size: 1.125rem;
    color: var(--text-gray);
    line-height: 1.7;
    max-width: 860px;
    margin: 0;
  }
  
  #ship .chips { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px; }
  #ship .chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #f8fafc;
    border: 1px solid var(--border-light);
    color: var(--text-gray);
    padding: 6px 16px;
    border-radius: 999px;
    font-size: 0.875rem;
    font-weight: 500;
  }
  #ship .chip svg {
    width: 16px;
    height: 16px;
    stroke-width: 2;
  }

  /* ===== Layout con sidebar ===== */
  #ship .grid {
    display: grid;
    gap: 60px;
    grid-template-columns: 280px 1fr;
    align-items: start;
  }

  @media (max-width: 980px) {
    #ship .grid { grid-template-columns: 1fr; gap: 40px; }
  }

  /* ===== Sidebar (Índice) ===== */
  #ship .sidebar {
    position: sticky;
    top: 120px;
    border-right: 1px solid var(--border-light);
    padding-right: 24px;
  }
  
  #ship .sidebar h3 {
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-gray);
    margin: 0 0 16px 0;
  }
  
  #ship .toc { list-style: none; margin: 0; padding: 0; }
  #ship .toc li { margin: 4px 0; }
  #ship .toc a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    border-radius: 6px;
    color: var(--text-gray);
    text-decoration: none;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    border-left: 2px solid transparent;
  }
  #ship .toc a svg { width: 16px; height: 16px; stroke-width: 2; opacity: 0.7; }
  
  #ship .toc a:hover {
    color: var(--text-dark);
    background: #f8fafc;
  }
  
  #ship .toc a.active {
    color: var(--accent);
    font-weight: 600;
    border-left-color: var(--accent);
    background: #f8fafc;
  }
  #ship .toc a.active svg { opacity: 1; }

  /* ===== Contenido ===== */
  #ship .content { padding-bottom: 80px; }
  
  #ship section {
    padding-top: 60px;
    margin-top: -40px; /* Compensa el padding para scroll-margin */
    scroll-margin-top: 100px;
  }
  
  #ship h2 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-dark);
    border-bottom: 1px solid var(--border-light);
    padding-bottom: 16px;
    margin: 0 0 24px 0;
    letter-spacing: -0.02em;
  }
  
  #ship h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 32px 0 16px 0;
  }
  
  #ship p {
    color: var(--text-gray);
    line-height: 1.7;
    font-size: 1.05rem;
    margin: 0 0 16px 0;
  }
  
  #ship ul, #ship ol {
    color: var(--text-gray);
    line-height: 1.7;
    font-size: 1.05rem;
    padding-left: 24px;
    margin: 0 0 24px 0;
  }
  
  #ship li { margin-bottom: 8px; }
  
  #ship .content a {
    color: var(--link);
    text-decoration: none;
    font-weight: 500;
  }
  
  #ship .content a:hover { text-decoration: underline; }
  
  #ship .note {
    background: #f8fafc;
    border-left: 4px solid var(--accent);
    padding: 16px 20px;
    border-radius: 0 8px 8px 0;
    margin: 24px 0;
    font-size: 1rem;
    color: var(--text-dark);
  }

  #ship .hr {
    height: 1px;
    background: var(--border-light);
    margin: 40px 0;
  }

  /* ===== Móvil: ocultar sidebar ===== */
  @media (max-width: 980px) {
    #ship .sidebar { display: none; }
  }

  /* ===== Botón flotante corporativo (móvil) ===== */
  #ship .fab {
    position: fixed; right: 24px; bottom: 24px; z-index: 50;
    width: 56px; height: 56px; border-radius: 50%;
    display: grid; place-items: center;
    background: var(--accent); color: #fff;
    border: none; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.2);
    cursor: pointer;
    transform: translateY(20px) scale(0.9); opacity: 0; pointer-events: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  #ship .fab svg { width: 24px; height: 24px; stroke-width: 2; }
  
  #ship .fab.show {
    transform: translateY(0) scale(1); opacity: 1; pointer-events: auto;
  }

  /* ===== Mini índice flotante (overlay móvil) ===== */
  #ship .mtoc-backdrop {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px); z-index: 49; opacity: 0; pointer-events: none; transition: .3s;
  }
  #ship .mtoc-backdrop.open { opacity: 1; pointer-events: auto; }

  #ship .mtoc {
    position: fixed; left: 0; right: 0; bottom: 0; z-index: 50;
    background: var(--bg-pure); border-radius: 24px 24px 0 0;
    box-shadow: 0 -10px 40px rgba(0,0,0,0.1);
    transform: translateY(100%); opacity: 0; pointer-events: none; transition: transform .4s cubic-bezier(0.16, 1, 0.3, 1), opacity .4s;
    max-height: 70vh; overflow: auto;
    padding-bottom: 24px;
  }
  #ship .mtoc.open { transform: translateY(0); opacity: 1; pointer-events: auto; }
  
  #ship .mtoc header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 20px 24px; border-bottom: 1px solid var(--border-light);
    position: sticky; top: 0; background: var(--bg-pure);
  }
  
  #ship .mtoc header h4 { margin: 0; font-size: 1.1rem; color: var(--text-dark); }
  #ship .mtoc header button {
    display: flex; align-items: center; justify-content: center;
    background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%;
    color: var(--text-dark); cursor: pointer; transition: background 0.2s;
  }
  #ship .mtoc header button:hover { background: #e2e8f0; }
  
  #ship .mtoc ul { list-style: none; margin: 0; padding: 16px 24px; }
  #ship .mtoc li a {
    display: flex; align-items: center; gap: 10px; padding: 12px 0; color: var(--text-gray); text-decoration: none; font-size: 1rem; border-bottom: 1px solid #f1f5f9;
  }
  #ship .mtoc li a svg { width: 18px; height: 18px; stroke-width: 2; opacity: 0.7; }
  #ship .mtoc li a:hover, #ship .mtoc li a.active { color: var(--accent); font-weight: 600; }
  #ship .mtoc li a.active svg { opacity: 1; }

  html { scroll-behavior: smooth; }
</style>

<div id="ship">
  <div class="wrap">

    <header class="hero gsap-hero">
      <h1 class="title">Formas de Envío</h1>
      <p class="sub">En <strong>Jureto</strong> integramos proveedores de paquetería a través de nuestra plataforma. <strong>Solo realizamos envíos salientes</strong> de pedidos; <strong>no ofrecemos recolección en domicilio</strong> ni <strong>entrega/pick-up en tienda</strong>, salvo <strong>recolección por devolución autorizada</strong>.</p>
      <div class="chips">
        <span class="chip">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <rect x="1" y="3" width="15" height="13"></rect>
            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
            <circle cx="5.5" cy="18.5" r="2.5"></circle>
            <circle cx="18.5" cy="18.5" r="2.5"></circle>
          </svg>
          Envíos salientes
        </span>
        <span class="chip">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
            <line x1="7" y1="7" x2="7.01" y2="7"></line>
          </svg>
          Elección de paquetería
        </span>
        <span class="chip">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
            <line x1="12" y1="22.08" x2="12" y2="12"></line>
          </svg>
          Rastreo en nuestro portal
        </span>
      </div>
    </header>

    <div class="grid">
      <aside class="sidebar gsap-sidebar">
        <h3>Índice</h3>
        <ul class="toc" id="toc-desktop">
          <li>
            <a class="lvl1" href="#como-enviamos">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
              1. ¿Cómo enviamos?
            </a>
          </li>
          <li>
            <a class="lvl1" href="#eleccion">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
              2. Elección de paquetería
            </a>
          </li>
          <li>
            <a class="lvl1" href="#tarifas">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
              3. Tarifas y tiempos
            </a>
          </li>
          <li>
            <a class="lvl1" href="#entrega">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
              4. Entrega y políticas
            </a>
          </li>
          <li>
            <a class="lvl1" href="#devoluciones">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>
              5. Recolección solo por devolución
            </a>
          </li>
          <li>
            <a class="lvl1" href="#rastreo">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              6. Rastreo en nuestro sitio
            </a>
          </li>
          <li>
            <a class="lvl1" href="#cobertura">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
              7. Cobertura y restricciones
            </a>
          </li>
          <li>
            <a class="lvl1" href="#contacto">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
              8. Contacto
            </a>
          </li>
        </ul>
      </aside>

      <main class="content">
        <section id="como-enviamos" class="gsap-section">
          <h2>1. ¿Cómo enviamos?</h2>
          <ul>
            <li><strong>Solo envíos salientes:</strong> gestionamos el despacho de tus pedidos desde nuestros almacenes.</li>
            <li><strong>Sin pick-up en tienda:</strong> no contamos con mostrador para entrega local.</li>
            <li><strong>Sin recolección en domicilio:</strong> no retiramos paquetes del cliente, excepto devoluciones autorizadas (ver punto 5).</li>
          </ul>
        </section>

        <section id="eleccion" class="gsap-section">
          <h2>2. Elección de paquetería</h2>
          <p>Al finalizar tu compra, nuestro sistema te muestra varias opciones de mensajería (p. ej., DHL, FedEx, Estafeta, UPS, Redpack, Paquetexpress, 99minutos, entre otras). <strong>Tú eliges</strong> la que más te convenga según precio, tiempo estimado y cobertura.</p>
          <div class="note">La disponibilidad depende del origen/destino, dimensiones/peso y políticas del transportista.</div>
        </section>

        <section id="tarifas" class="gsap-section">
          <h2>3. Tarifas y tiempos</h2>
          <ul>
            <li><strong>Consulta en tiempo real:</strong> mostramos precios y ventanas de entrega estimadas por cada paquetería.</li>
            <li><strong>Promociones/convenios:</strong> cuando aplican, se reflejan automáticamente.</li>
            <li><strong>Estimaciones:</strong> pueden variar por fechas pico, zonas extendidas o incidencias operativas.</li>
          </ul>
        </section>

        <section id="entrega" class="gsap-section">
          <h2>4. Entrega y políticas</h2>
          <ul>
            <li><strong>Entrega a domicilio o sucursal de paquetería:</strong> según disponibilidad de la opción elegida.</li>
            <li><strong>Firma/identificación:</strong> puede requerirse por el transportista.</li>
            <li><strong>Reintentos/redirecciones:</strong> sujetos a políticas y posibles cargos del transportista.</li>
          </ul>
        </section>

        <section id="devoluciones" class="gsap-section">
          <h2>5. Recolección solo por devolución</h2>
          <p>Si tu devolución es <strong>autorizada</strong>, podemos <strong>programar la recolección</strong> con la paquetería. Para ello:</p>
          <ul>
            <li>Solicita tu devolución vía correo a <a href="mailto:rtort@jureto.com.mx">rtort@jureto.com.mx</a> indicando número de pedido, motivo y evidencias.</li>
            <li>Te enviaremos la <strong>guía</strong> y la <strong>ventana de recolección</strong> (o instrucciones para entregar en sucursal).</li>
            <li>Prepara el paquete con <strong>embalaje adecuado</strong> y la etiqueta visible. Algunas paqueterías realizan 1–2 intentos.</li>
          </ul>
          <div class="note">Las devoluciones se rigen por nuestra <a href="{{ route('policy.shipping') }}">política de Envíos, Devoluciones y Cancelaciones</a>.</div>
        </section>

        <section id="rastreo" class="gsap-section">
          <h2>6. Rastreo en nuestro sitio</h2>
          <p>Tras generar la guía, te compartimos el <strong>número de rastreo</strong>. Puedes consultar el estatus directamente en tu cuenta o en la sección de rastreo de nuestro sitio.</p>
        </section>

        <section id="cobertura" class="gsap-section">
          <h2>7. Cobertura y restricciones</h2>
          <ul>
            <li><strong>Nacional:</strong> envíos a todo México; en ciertas zonas hay servicios exprés o última milla.</li>
            <li><strong>Internacional:</strong> sujeto a disponibilidad y regulaciones aduanales.</li>
            <li><strong>Restricciones:</strong> artículos prohibidos o restringidos se validan según la paquetería; nuestro sistema bloquea opciones cuando aplican.</li>
          </ul>
        </section>

        <section id="contacto" class="gsap-section">
          <h2>8. Contacto</h2>
          <ul>
            <li><strong>Email:</strong> <a href="mailto:rtort@jureto.com.mx">rtort@jureto.com.mx</a></li>
            <li><strong>Teléfono:</strong> <a href="tel:+525541937243">+52 55 4193 7243</a></li>
            <li><strong>Ubicación:</strong> 7CP5+34M San Jerónimo Chicahualco, Estado de México &amp; UAE</li>
          </ul>
          <div class="hr"></div>
          <ul>
            <li><a href="{{ route('policy.shipping') }}">Envíos, Devoluciones y Cancelaciones</a></li>
            <li><a href="{{ route('policy.payments') }}">Formas de Pago</a></li>
            <li><a href="{{ route('policy.terms') }}">Términos y Condiciones</a></li>
          </ul>
        </section>
      </main>
    </div>
  </div>

  <button id="fab" class="fab" aria-label="Índice">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
      <line x1="3" y1="12" x2="21" y2="12"></line>
      <line x1="3" y1="6" x2="21" y2="6"></line>
      <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
  </button>

  <div id="mtoc-backdrop" class="mtoc-backdrop"></div>
  <nav id="mtoc" class="mtoc" aria-label="Índice móvil">
    <header>
      <h4>Navegación</h4>
      <button type="button" id="mtoc-close" aria-label="Cerrar">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </header>
    <ul id="toc-mobile"></ul>
  </nav>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  // 1. Inicializar animaciones GSAP
  gsap.registerPlugin(ScrollTrigger);

  // Animación de entrada para el Hero
  gsap.fromTo(".gsap-hero > *", 
    { opacity: 0, y: 20 }, 
    { opacity: 1, y: 0, duration: 0.8, stagger: 0.1, ease: "power3.out" }
  );

  // Animación de entrada para el Sidebar
  gsap.fromTo(".gsap-sidebar", 
    { opacity: 0, x: -20 }, 
    { opacity: 1, x: 0, duration: 0.8, delay: 0.3, ease: "power3.out" }
  );

  // Animación de aparición fluida para cada sección de contenido
  gsap.utils.toArray('.gsap-section').forEach((sec) => {
    gsap.fromTo(sec, 
      { opacity: 0, y: 30 }, 
      {
        scrollTrigger: {
          trigger: sec,
          start: "top 85%",
          toggleActions: "play none none reverse"
        },
        opacity: 1, y: 0, duration: 0.8, ease: "power3.out"
      }
    );
  });

  // 2. Scrollspy robusto + offset en clic
  const root = document.getElementById('ship');
  if (!root) return;

  const OFFSET = 120; // Ajuste para navbar fija
  const links = Array.from(document.querySelectorAll('#ship .toc a'));
  const targets = links.map(a => document.querySelector(a.getAttribute('href'))).filter(Boolean);

  function smoothTo(id){
    const el = document.querySelector(id); 
    if(!el) return;
    const top = window.scrollY + el.getBoundingClientRect().top - OFFSET;
    window.scrollTo({top, behavior:'smooth'});
  }

  document.querySelectorAll('#ship .toc a, #toc-mobile a').forEach(a => {
    a.addEventListener('click', e => {
      const href = a.getAttribute('href'); 
      if(!href?.startsWith('#')) return;
      e.preventDefault(); 
      smoothTo(href);
    });
  });

  let tops = [];
  function compute(){ 
    tops = targets.map(el => ({
      id: '#' + el.id, 
      top: window.scrollY + el.getBoundingClientRect().top - OFFSET - 1
    })).sort((a,b) => a.top - b.top);
  }

  function setActive(){
    const y = window.scrollY; 
    let current = tops[0]?.id || null;
    for (let i = 0; i < tops.length; i++){ 
      if (y >= tops[i].top) current = tops[i].id; 
      else break; 
    }
    
    links.forEach(a => a.classList.toggle('active', a.getAttribute('href') === current));
    
    // Actualizar también el índice móvil
    document.querySelectorAll('#toc-mobile a').forEach(a => {
      a.classList.toggle('active', a.getAttribute('href') === current);
    });
  }

  window.addEventListener('scroll', setActive, {passive: true});
  window.addEventListener('resize', () => { compute(); setActive(); });
  window.addEventListener('load', () => { compute(); setActive(); });
  setTimeout(() => { compute(); setActive(); }, 250);

  // 3. Lógica móvil (Botón flotante y menú)
  const fab = document.getElementById('fab');
  const mtoc = document.getElementById('mtoc');
  const backdrop = document.getElementById('mtoc-backdrop');
  const closeBtn = document.getElementById('mtoc-close');
  const tocDesktop = document.getElementById('toc-desktop');
  const tocMobile = document.querySelector('#mtoc #toc-mobile');

  // Clonar índice
  if (tocDesktop && tocMobile) {
    tocMobile.innerHTML = tocDesktop.innerHTML;
    tocMobile.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', (e) => {
        const href = a.getAttribute('href');
        if(href?.startsWith('#')) {
          e.preventDefault();
          toggleMTOC(false);
          smoothTo(href);
        }
      });
    });
  }

  function isMobile(){ return window.matchMedia('(max-width: 980px)').matches; }
  
  function toggleMTOC(force){
    const open = (typeof force === 'boolean') ? force : !mtoc.classList.contains('open');
    mtoc.classList.toggle('open', open); 
    backdrop.classList.toggle('open', open);
  }

  function onScroll(){
    if (!isMobile()) { fab.classList.remove('show'); toggleMTOC(false); return; }
    const y = window.scrollY, h = window.innerHeight;
    const docH = Math.max(document.body.scrollHeight, document.documentElement.scrollHeight);
    fab.classList.toggle('show', (y + h) >= (docH - 600));
  }

  window.addEventListener('scroll', onScroll, {passive:true});
  window.addEventListener('resize', onScroll);
  onScroll();

  fab.addEventListener('click', () => toggleMTOC(true));
  backdrop.addEventListener('click', () => toggleMTOC(false));
  closeBtn.addEventListener('click', () => toggleMTOC(false));
});
</script>
@endsection