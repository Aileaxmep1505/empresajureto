@extends('layouts.web')
@section('title','Garantías y Devoluciones')

@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

<style>
  /* ========= NAMESPACE AISLADO ========= */
  #policy {
    --bg-pure: #ffffff;
    --text-dark: #0f172a;
    --text-gray: #475569;
    --border-light: #e2e8f0;
    --accent: #0f172a; /* Tono corporativo oscuro */
    --link: #2563eb; /* Azul profesional para enlaces */
    --radius: 12px;
  }

  #policy {
    position: relative;
    width: 100%;
    background-color: var(--bg-pure);
    color: var(--text-dark);
    font-family: 'Inter', system-ui, sans-serif;
  }

  #policy .wrap {
    max-width: 1200px;
    margin: 0 auto;
    padding: clamp(40px, 5vw, 80px) 24px;
  }

  /* ===== Hero ===== */
  #policy .hero { margin-bottom: 60px; }
  #policy .title {
    font-size: clamp(2.5rem, 4vw, 3.5rem);
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.03em;
    color: var(--text-dark);
    margin: 0 0 16px 0;
  }
  
  #policy .sub {
    font-size: 1.125rem;
    color: var(--text-gray);
    line-height: 1.7;
    max-width: 800px;
    margin: 0;
  }
  
  #policy .chips { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px; }
  #policy .chip {
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
  #policy .chip svg {
    width: 16px;
    height: 16px;
    stroke-width: 2;
  }

  /* ===== Layout con sidebar ===== */
  #policy .grid {
    display: grid;
    gap: 60px;
    grid-template-columns: 280px 1fr;
    align-items: start;
  }

  @media (max-width: 980px) {
    #policy .grid { grid-template-columns: 1fr; gap: 40px; }
  }

  /* ===== Sidebar (Índice) ===== */
  #policy .sidebar {
    position: sticky;
    top: 120px;
    border-right: 1px solid var(--border-light);
    padding-right: 24px;
  }
  
  #policy .sidebar h3 {
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-gray);
    margin: 0 0 16px 0;
  }
  
  #policy .toc { list-style: none; margin: 0; padding: 0; }
  #policy .toc li { margin: 4px 0; }
  #policy .toc a {
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
  #policy .toc a svg { width: 16px; height: 16px; stroke-width: 2; opacity: 0.7; }
  
  #policy .toc a:hover {
    color: var(--text-dark);
    background: #f8fafc;
  }
  
  #policy .toc a.active {
    color: var(--accent);
    font-weight: 600;
    border-left-color: var(--accent);
    background: #f8fafc;
  }
  #policy .toc a.active svg { opacity: 1; }

  /* ===== Contenido ===== */
  #policy .content { padding-bottom: 80px; }
  
  #policy section {
    padding-top: 60px;
    margin-top: -40px; /* Compensa el padding para scroll-margin */
    scroll-margin-top: 100px;
  }
  
  #policy h2 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-dark);
    border-bottom: 1px solid var(--border-light);
    padding-bottom: 16px;
    margin: 0 0 24px 0;
    letter-spacing: -0.02em;
  }
  
  #policy h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 32px 0 16px 0;
  }
  
  #policy p {
    color: var(--text-gray);
    line-height: 1.7;
    font-size: 1.05rem;
    margin: 0 0 16px 0;
  }
  
  #policy ul, #policy ol {
    color: var(--text-gray);
    line-height: 1.7;
    font-size: 1.05rem;
    padding-left: 24px;
    margin: 0 0 24px 0;
  }
  
  #policy li { margin-bottom: 8px; }
  
  #policy .content a {
    color: var(--link);
    text-decoration: none;
    font-weight: 500;
  }
  
  #policy .content a:hover { text-decoration: underline; }
  
  #policy .note {
    background: #f8fafc;
    border-left: 4px solid var(--accent);
    padding: 16px 20px;
    border-radius: 0 8px 8px 0;
    margin: 24px 0;
    font-size: 1rem;
    color: var(--text-dark);
  }

  /* ====== BOTÓN CTA (ALTAMENTE ENCAPSULADO Y FORZADO) ====== */
  div#policy .cta { 
    margin-top: 40px; 
    display: flex; 
    gap: 16px; 
    flex-wrap: wrap; 
    align-items: center; 
  }
  
  div#policy .cta a.btn-formal {
    display: inline-flex !important; 
    align-items: center !important; 
    justify-content: center !important;
    padding: 14px 28px !important; 
    background-color: #007aff !important; /* Azul forzado */
    color: #ffffff !important; /* Texto blanco forzado */
    font-weight: 600 !important; 
    font-size: 1rem !important; 
    border-radius: 8px !important; 
    border: none !important; 
    outline: none !important;
    text-decoration: none !important; 
    transition: all 0.3s ease !important;
    box-shadow: 0 4px 12px rgba(0, 122, 255, 0.2) !important;
  }
  
  div#policy .cta a.btn-formal:hover {
    background-color: #0062cc !important; /* Azul oscuro hover */
    color: #ffffff !important; 
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 16px rgba(0, 122, 255, 0.3) !important;
  }
  
  #policy .muted { color: var(--text-gray); font-size: 0.9rem; }

  /* ===== Móvil: ocultar sidebar ===== */
  @media (max-width: 980px) {
    #policy .sidebar { display: none; }
  }

  /* ===== Botón flotante corporativo (móvil) ===== */
  #policy .fab {
    position: fixed; right: 24px; bottom: 24px; z-index: 50;
    width: 56px; height: 56px; border-radius: 50%;
    display: grid; place-items: center;
    background: var(--accent); color: #fff;
    border: none; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.2);
    cursor: pointer;
    transform: translateY(20px) scale(0.9); opacity: 0; pointer-events: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  #policy .fab svg { width: 24px; height: 24px; stroke-width: 2; }
  
  #policy .fab.show {
    transform: translateY(0) scale(1); opacity: 1; pointer-events: auto;
  }

  /* ===== Mini índice flotante (overlay móvil) ===== */
  #policy .mtoc-backdrop {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px); z-index: 49; opacity: 0; pointer-events: none; transition: .3s;
  }
  #policy .mtoc-backdrop.open { opacity: 1; pointer-events: auto; }

  #policy .mtoc {
    position: fixed; left: 0; right: 0; bottom: 0; z-index: 50;
    background: var(--bg-pure); border-radius: 24px 24px 0 0;
    box-shadow: 0 -10px 40px rgba(0,0,0,0.1);
    transform: translateY(100%); opacity: 0; pointer-events: none; transition: transform .4s cubic-bezier(0.16, 1, 0.3, 1), opacity .4s;
    max-height: 70vh; overflow: auto;
    padding-bottom: 24px;
  }
  #policy .mtoc.open { transform: translateY(0); opacity: 1; pointer-events: auto; }
  
  #policy .mtoc header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 20px 24px; border-bottom: 1px solid var(--border-light);
    position: sticky; top: 0; background: var(--bg-pure);
  }
  
  #policy .mtoc header h4 { margin: 0; font-size: 1.1rem; color: var(--text-dark); }
  #policy .mtoc header button {
    display: flex; align-items: center; justify-content: center;
    background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%;
    color: var(--text-dark); cursor: pointer; transition: background 0.2s;
  }
  #policy .mtoc header button:hover { background: #e2e8f0; }
  
  #policy .mtoc ul { list-style: none; margin: 0; padding: 16px 24px; }
  #policy .mtoc li a {
    display: flex; align-items: center; gap: 10px; padding: 12px 0; color: var(--text-gray); text-decoration: none; font-size: 1rem; border-bottom: 1px solid #f1f5f9;
  }
  #policy .mtoc li a svg { width: 18px; height: 18px; stroke-width: 2; opacity: 0.7; }
  #policy .mtoc li a:hover, #policy .mtoc li a.active { color: var(--accent); font-weight: 600; }
  #policy .mtoc li a.active svg { opacity: 1; }

  html { scroll-behavior: smooth; }
</style>

<div id="policy">
  <div class="wrap">

    <header class="hero gsap-hero">
      <h1 class="title">Garantías y Devoluciones</h1>
      <p class="sub">
        Aquí encontrarás cómo solicitar una devolución, los requisitos para hacer válida una garantía
        y los tiempos estimados de respuesta. Hemos simplificado el lenguaje para que todo sea claro y directo.
      </p>
      
      <div class="chips">
        <span class="chip">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
          Devolución hasta 30 días hábiles
        </span>
        <span class="chip">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
            <line x1="12" y1="22.08" x2="12" y2="12"></line>
          </svg>
          Empaque original y accesorios
        </span>
        <span class="chip">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
          </svg>
          Garantía sujeta a fabricante
        </span>
      </div>
    </header>

    <div class="grid">
      <aside class="sidebar gsap-sidebar">
        <h3>Índice</h3>
        <ul class="toc" id="toc-desktop">
          <li>
            <a class="lvl1" href="#alcance">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7l8 4 8-4M4 7v10l8 4 8-4V7"/></svg>
              1. Alcance
            </a>
          </li>
          <li>
            <a class="lvl1" href="#devoluciones">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h14m0 0l-3-3m3 3l-3 3M8 7V5a3 3 0 013-3h7"/></svg>
              2. Devoluciones
            </a>
          </li>
          <li>
            <a class="lvl1" href="#pasos">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
              3. Pasos a seguir
            </a>
          </li>
          <li>
            <a class="lvl1" href="#garantias">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              4. Garantías
            </a>
          </li>
          <li>
            <a class="lvl1" href="#excepciones">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
              5. Excepciones
            </a>
          </li>
          <li>
            <a class="lvl1" href="#tiempos">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              6. Tiempos y reembolsos
            </a>
          </li>
        </ul>
      </aside>

      <main class="content">
        <section id="alcance" class="gsap-section">
          <h2>1. Alcance de la política</h2>
          <p>
            Esta guía aplica a compras realizadas en nuestro sitio para productos de papelería, consumibles, equipo de oficina y accesorios.
            Los procedimientos pueden variar según la marca o tipo de producto cuando el fabricante así lo establece.
          </p>
          <ul>
            <li>Las devoluciones se aceptan durante <strong>30 días hábiles</strong> desde la recepción del pedido.</li>
            <li>Las garantías se gestionan por <em>defecto de fabricación</em> dentro del plazo otorgado por cada marca.</li>
            <li>El trámite puede requerir número de serie, pruebas de falla y empaque original.</li>
          </ul>
        </section>

        <section id="devoluciones" class="gsap-section">
          <h2>2. Devoluciones</h2>
          <p>Puedes solicitar una devolución cuando se presente uno de estos escenarios:</p>
          <ul>
            <li>El artículo recibido <strong>no corresponde</strong> al solicitado.</li>
            <li>El producto <strong>no coincide</strong> con las especificaciones publicadas.</li>
            <li>Se enviaron <strong>unidades de más</strong> por error.</li>
            <li>El paquete llega íntegro y <strong>sin señales de apertura</strong> o daño externo.</li>
          </ul>
          <div class="note"><strong>Importante:</strong> utiliza el mismo embalaje o uno equivalente que proteja el contenido. No coloques cintas o etiquetas directamente sobre la caja original del fabricante.</div>
        </section>

        <section id="pasos" class="gsap-section">
          <h2>3. ¿Cómo iniciar el trámite?</h2>
          <h3>Devolución</h3>
          <ol>
            <li>Contáctanos dentro de los <strong>15 días hábiles</strong> posteriores a la entrega para reportar el caso.</li>
            <li>Te enviaremos el <strong>folio y formato</strong> con instrucciones de envío.</li>
            <li>Empaca el producto <strong>con todos sus accesorios</strong>, manuales y obsequios incluidos.</li>
            <li>Remite el paquete con la guía indicada. Conserva tu comprobante.</li>
          </ol>

          <h3>Garantía</h3>
          <ol>
            <li>Ten a la mano la <strong>factura</strong>, número de serie y una descripción clara de la falla.</li>
            <li>Para consumibles (tinta/tóner) adjunta prueba de impresión o carta descriptiva de la anomalía.</li>
            <li>Algunas marcas requieren <strong>póliza original</strong> o diagnóstico en centro de servicio autorizado.</li>
          </ol>
        </section>

        <section id="garantias" class="gsap-section">
          <h2>4. Cobertura de Garantías</h2>
          <p>Tramitamos la garantía cuando exista un defecto de fabricación que afecte el funcionamiento normal.</p>
          <ul>
            <li><strong>Equipo y componentes</strong>: hasta 12 meses según marca (algunos componentes 3 meses, fuentes 6 meses).</li>
            <li><strong>Consumibles y papelería</strong>: 3 meses; algunas marcas corporativas pueden otorgar hasta 12 meses.</li>
            <li><strong>Mobiliario de oficina</strong>: 12 meses (año comercial de 360 días).</li>
          </ul>
          <p>El fabricante determina el resultado del dictamen (reparación, sustitución o nota de crédito cuando aplique).</p>
        </section>

        <section id="excepciones" class="gsap-section">
          <h2>5. Exclusiones y casos no cubiertos</h2>
          <ul>
            <li>Daño físico, golpes, humedad, quemaduras o intervención por terceros no autorizados.</li>
            <li>Etiquetas o sellos alterados/removidos; códigos de barras ilegibles.</li>
            <li>Consumibles <strong>caducados</strong> o con menos del 50% de contenido.</li>
            <li>Rendimiento de tinta/tóner (varía según uso). Para pantallas LCD, se aplican políticas de píxeles por marca.</li>
            <li>Software por licencia/código: <strong>no es retornable</strong> una vez entregado el código o activación.</li>
          </ul>
          <div class="note">
            Algunas categorías corporativas (p.ej. accesorios específicos o productos bajo pedido) pueden tener políticas especiales. Te confirmaremos el criterio antes de iniciar el trámite.
          </div>
        </section>

        <section id="tiempos" class="gsap-section">
          <h2>6. Tiempos de respuesta y reembolsos</h2>
          <ul>
            <li><strong>Cancelación del pedido:</strong> puedes solicitarla en cualquier momento previo al envío. Si ya está pagado, el reembolso se procesa en un máximo de <strong>10 días hábiles</strong>.</li>
            <li><strong>Garantía express:</strong> para ciertos productos se emite dictamen acelerado sujeto a revisión del proveedor.</li>
            <li><strong>Garantía estándar:</strong> dentro de los primeros 30 días, si procede, se resuelve en ~72h (excepto equipos que requieren centro de servicio).</li>
            <li><strong>Posterior a 30 días:</strong> la resolución puede tomar entre <strong>7 y 15 días hábiles</strong> según proveedor y categoría.</li>
          </ul>
          
          <div class="chips" style="margin-top: 24px;">
            <span class="chip">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
              </svg>
              Reembolso vía nota o medio original
            </span>
            <span class="chip">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
              </svg>
              Tiempos sujetos a revisión
            </span>
          </div>

          <div class="cta">
            <a class="btn-formal" href="{{ url('/contacto') }}">Abrir solicitud</a>
            <span class="muted">¿Dudas? Escríbenos y te ayudamos a elegir el proceso correcto.</span>
          </div>
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

  // 2. Scrollspy simple con IntersectionObserver (Actualiza el índice activo)
  const links = Array.from(document.querySelectorAll('#policy .toc a'));
  const sections = links.map(a => document.querySelector(a.getAttribute('href'))).filter(Boolean);

  const obs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if(entry.isIntersecting){
        const id = '#' + entry.target.id;
        links.forEach(l => l.classList.toggle('active', l.getAttribute('href') === id));
        
        // Actualizar también en móvil si existe
        const mobileLinks = document.querySelectorAll('#toc-mobile a');
        if(mobileLinks.length) {
            mobileLinks.forEach(l => l.classList.toggle('active', l.getAttribute('href') === id));
        }
      }
    });
  }, { rootMargin: '-20% 0px -75% 0px' });

  sections.forEach(sec => obs.observe(sec));

  // 3. Lógica móvil (Botón flotante y menú)
  const fab = document.getElementById('fab');
  const mtoc = document.getElementById('mtoc');
  const backdrop = document.getElementById('mtoc-backdrop');
  const closeBtn = document.getElementById('mtoc-close');
  const tocDesktop = document.getElementById('toc-desktop');
  const tocMobile = document.getElementById('toc-mobile');

  if (tocDesktop && tocMobile) {
    tocMobile.innerHTML = tocDesktop.innerHTML;
    tocMobile.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => toggleMTOC(false));
    });
  }

  function isMobile(){
    return window.matchMedia('(max-width: 980px)').matches;
  }

  function toggleMTOC(force){
    const open = (typeof force === 'boolean') ? force : !mtoc.classList.contains('open');
    mtoc.classList.toggle('open', open);
    backdrop.classList.toggle('open', open);
  }

  function onScroll(){
    if (!isMobile()) { fab.classList.remove('show'); toggleMTOC(false); return; }
    const scrollY = window.scrollY || window.pageYOffset;
    // Mostrar el FAB si scrolleamos más de 300px hacia abajo
    fab.classList.toggle('show', scrollY > 300);
  }

  window.addEventListener('scroll', onScroll, {passive: true});
  window.addEventListener('resize', onScroll);
  onScroll();

  fab.addEventListener('click', () => toggleMTOC(true));
  backdrop.addEventListener('click', () => toggleMTOC(false));
  closeBtn.addEventListener('click', () => toggleMTOC(false));
});
</script>
@endsection