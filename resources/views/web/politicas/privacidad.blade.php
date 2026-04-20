@extends('layouts.web')
@section('title','Aviso de Privacidad')

@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

<style>
  /* ========= NAMESPACE AISLADO ========= */
  #privacy {
    --bg-pure: #ffffff;
    --text-dark: #0f172a;
    --text-gray: #475569;
    --border-light: #e2e8f0;
    --accent: #0f172a; /* Tono corporativo oscuro */
    --link: #2563eb; /* Azul profesional para enlaces */
    --radius: 12px;
  }

  #privacy {
    position: relative;
    width: 100%;
    background-color: var(--bg-pure);
    color: var(--text-dark);
    font-family: 'Inter', system-ui, sans-serif;
  }

  #privacy .wrap {
    max-width: 1200px;
    margin: 0 auto;
    padding: clamp(40px, 5vw, 80px) 24px;
  }

  /* ===== Hero ===== */
  #privacy .hero { margin-bottom: 60px; }
  #privacy .title {
    font-size: clamp(2.5rem, 4vw, 3.5rem);
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.03em;
    color: var(--text-dark);
    margin: 0 0 16px 0;
  }
  
  #privacy .sub {
    font-size: 1.125rem;
    color: var(--text-gray);
    line-height: 1.7;
    max-width: 800px;
    margin: 0;
  }
  
  #privacy .chips { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px; }
  #privacy .chip {
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
  #privacy .chip svg {
    width: 16px;
    height: 16px;
    stroke-width: 2;
  }

  /* ===== Layout con sidebar ===== */
  #privacy .grid {
    display: grid;
    gap: 60px;
    grid-template-columns: 280px 1fr;
    align-items: start;
  }

  @media (max-width: 980px) {
    #privacy .grid { grid-template-columns: 1fr; gap: 40px; }
  }

  /* ===== Sidebar (Índice) ===== */
  #privacy .sidebar {
    position: sticky;
    top: 120px;
    border-right: 1px solid var(--border-light);
    padding-right: 24px;
  }
  
  #privacy .sidebar h3 {
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-gray);
    margin: 0 0 16px 0;
  }
  
  #privacy .toc { list-style: none; margin: 0; padding: 0; }
  #privacy .toc li { margin: 4px 0; }
  #privacy .toc a {
    display: block;
    padding: 8px 12px;
    border-radius: 6px;
    color: var(--text-gray);
    text-decoration: none;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    border-left: 2px solid transparent;
  }
  
  #privacy .toc a:hover {
    color: var(--text-dark);
    background: #f8fafc;
  }
  
  #privacy .toc a.active {
    color: var(--accent);
    font-weight: 600;
    border-left-color: var(--accent);
    background: #f8fafc;
  }
  
  #privacy .toc .lvl2 { padding-left: 24px; font-size: 0.875rem; }

  /* ===== Contenido ===== */
  #privacy .content { padding-bottom: 80px; }
  
  #privacy section {
    padding-top: 60px;
    margin-top: -40px; /* Compensa el padding para scroll-margin */
    scroll-margin-top: 100px;
  }
  
  #privacy h2 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-dark);
    border-bottom: 1px solid var(--border-light);
    padding-bottom: 16px;
    margin: 0 0 24px 0;
    letter-spacing: -0.02em;
  }
  
  #privacy h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 32px 0 16px 0;
  }
  
  #privacy p {
    color: var(--text-gray);
    line-height: 1.7;
    font-size: 1.05rem;
    margin: 0 0 16px 0;
  }
  
  #privacy ul, #privacy ol {
    color: var(--text-gray);
    line-height: 1.7;
    font-size: 1.05rem;
    padding-left: 24px;
    margin: 0 0 24px 0;
  }
  
  #privacy li { margin-bottom: 8px; }
  
  #privacy .content a {
    color: var(--link);
    text-decoration: none;
    font-weight: 500;
  }
  
  #privacy .content a:hover { text-decoration: underline; }
  
  #privacy .note {
    background: #f8fafc;
    border-left: 4px solid var(--accent);
    padding: 16px 20px;
    border-radius: 0 8px 8px 0;
    margin: 24px 0;
    font-size: 1rem;
    color: var(--text-dark);
  }

  #privacy .hr {
    height: 1px;
    background: var(--border-light);
    margin: 40px 0;
  }

  /* ===== Móvil: ocultar sidebar ===== */
  @media (max-width: 980px) {
    #privacy .sidebar { display: none; }
  }

  /* ===== Botón flotante corporativo (móvil) ===== */
  #privacy .fab {
    position: fixed; right: 24px; bottom: 24px; z-index: 50;
    width: 56px; height: 56px; border-radius: 50%;
    display: grid; place-items: center;
    background: var(--accent); color: #fff;
    border: none; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.2);
    cursor: pointer;
    transform: translateY(20px) scale(0.9); opacity: 0; pointer-events: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  #privacy .fab svg { width: 24px; height: 24px; stroke-width: 2; }
  
  #privacy .fab.show {
    transform: translateY(0) scale(1); opacity: 1; pointer-events: auto;
  }

  /* ===== Mini índice flotante (overlay móvil) ===== */
  #privacy .mtoc-backdrop {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px); z-index: 49; opacity: 0; pointer-events: none; transition: .3s;
  }
  #privacy .mtoc-backdrop.open { opacity: 1; pointer-events: auto; }

  #privacy .mtoc {
    position: fixed; left: 0; right: 0; bottom: 0; z-index: 50;
    background: var(--bg-pure); border-radius: 24px 24px 0 0;
    box-shadow: 0 -10px 40px rgba(0,0,0,0.1);
    transform: translateY(100%); opacity: 0; pointer-events: none; transition: transform .4s cubic-bezier(0.16, 1, 0.3, 1), opacity .4s;
    max-height: 70vh; overflow: auto;
    padding-bottom: 24px;
  }
  #privacy .mtoc.open { transform: translateY(0); opacity: 1; pointer-events: auto; }
  
  #privacy .mtoc header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 20px 24px; border-bottom: 1px solid var(--border-light);
    position: sticky; top: 0; background: var(--bg-pure);
  }
  
  #privacy .mtoc header h4 { margin: 0; font-size: 1.1rem; color: var(--text-dark); }
  #privacy .mtoc header button {
    display: flex; align-items: center; justify-content: center;
    background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%;
    color: var(--text-dark); cursor: pointer; transition: background 0.2s;
  }
  #privacy .mtoc header button:hover { background: #e2e8f0; }
  
  #privacy .mtoc ul { list-style: none; margin: 0; padding: 16px 24px; }
  #privacy .mtoc li a {
    display: block; padding: 12px 0; color: var(--text-gray); text-decoration: none; font-size: 1rem; border-bottom: 1px solid #f1f5f9;
  }
  #privacy .mtoc li a:hover, #privacy .mtoc li a.active { color: var(--accent); font-weight: 600; }

  html { scroll-behavior: smooth; }
</style>

<div id="privacy">
  <div class="wrap">

    <header class="hero gsap-hero">
      <h1 class="title">Aviso de Privacidad</h1>
      <p class="sub">Este aviso describe cómo <strong>Jureto</strong>, empresa comercializadora de productos de papelería, recaba, usa, protege y, en su caso, transfiere tus datos personales.</p>
      <div class="chips">
        <span class="chip">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
          Última actualización: 21 de octubre de 2025
        </span>
        <span class="chip">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
          </svg>
          Protección de datos
        </span>
      </div>
    </header>

    <div class="grid">
      <aside class="sidebar gsap-sidebar">
        <h3>Índice</h3>
        <ul class="toc" id="toc-desktop">
          <li><a class="lvl1" href="#responsable">1. Responsable y contacto</a></li>
          <li><a class="lvl1" href="#datos">2. Datos personales que recabamos</a></li>
          <li><a class="lvl1" href="#finalidades">3. Finalidades del tratamiento</a></li>
          <li><a class="lvl1" href="#bases">4. Bases de licitud y consentimiento</a></li>
          <li><a class="lvl1" href="#fuentes">5. Fuentes de obtención</a></li>
          <li><a class="lvl1" href="#cookies">6. Cookies y tecnologías similares</a></li>
          <li><a class="lvl1" href="#transferencias">7. Transferencias y encargados</a></li>
          <li><a class="lvl1" href="#conservacion">8. Conservación y eliminación</a></li>
          <li><a class="lvl1" href="#seguridad">9. Medidas de seguridad</a></li>
          <li><a class="lvl1" href="#derechos">10. Derechos ARCO y revocación</a></li>
          <li><a class="lvl1" href="#limitacion">11. Limitación del uso y divulgación</a></li>
          <li><a class="lvl1" href="#menores">12. Tratamiento de menores</a></li>
          <li><a class="lvl1" href="#cambios">13. Cambios al aviso</a></li>
          <li><a class="lvl1" href="#contacto">14. Contacto</a></li>
          <li><a class="lvl2" href="#vinculos">• Vínculos útiles</a></li>
        </ul>
      </aside>

      <main class="content">
        <section id="responsable" class="gsap-section">
          <h2>1. Responsable y contacto</h2>
          <p><strong>Jureto</strong> es responsable del tratamiento de tus datos personales. Puedes contactarnos para cualquier tema relacionado con privacidad a través de los medios indicados en la sección <a href="#contacto">Contacto</a>.</p>
        </section>

        <section id="datos" class="gsap-section">
          <h2>2. Datos personales que recabamos</h2>
          <ul>
            <li>Identificación y contacto: nombre, correo, teléfono, dirección de envío/facturación.</li>
            <li>Transaccionales: productos comprados, montos, métodos de pago (referencias no sensibles).</li>
            <li>Soporte y posventa: números de serie, evidencias de garantía y comunicaciones.</li>
            <li>Navegación: IP, dispositivo, páginas visitadas (ver <a href="#cookies">Cookies</a>).</li>
          </ul>
          <p><em>No solicitamos datos sensibles</em>. Si excepcionalmente llegaran a ser necesarios, lo comunicaremos y pediremos consentimiento expreso.</p>
        </section>

        <section id="finalidades" class="gsap-section">
          <h2>3. Finalidades del tratamiento</h2>
          <ul>
            <li><strong>Primarias:</strong> gestionar compras, pagos, envíos, garantías, devoluciones y facturación; soporte al cliente; cumplimiento legal.</li>
            <li><strong>Secundarias (opcionales):</strong> encuestas de satisfacción, comunicaciones comerciales y promociones. Puedes oponerte en cualquier momento.</li>
          </ul>
        </section>

        <section id="bases" class="gsap-section">
          <h2>4. Bases de licitud y consentimiento</h2>
          <p>Tratamos tus datos con base en: (i) ejecución de una relación contractual; (ii) cumplimiento de obligaciones legales; (iii) interés legítimo para mejorar seguridad, prevención de fraude y experiencia; y (iv) tu consentimiento cuando sea requerido.</p>
        </section>

        <section id="fuentes" class="gsap-section">
          <h2>5. Fuentes de obtención</h2>
          <p>Obtenemos datos directamente de ti (formularios y compras), de manera automática por el uso del sitio y, en su caso, de terceros proveedores de pago/paquetería estrictamente para completar tus pedidos.</p>
        </section>

        <section id="cookies" class="gsap-section">
          <h2>6. Cookies y tecnologías similares</h2>
          <p>Utilizamos cookies para recordar tu sesión, analizar el tráfico y mejorar el contenido. Puedes administrar tus preferencias desde la configuración de tu navegador o, cuando esté disponible, en nuestro banner/centro de preferencias.</p>
        </section>

        <section id="transferencias" class="gsap-section">
          <h2>7. Transferencias y encargados</h2>
          <p>No vendemos tus datos. Compartimos información con encargados que nos prestan servicios (pasarelas de pago, logística, hospedaje, soporte) bajo contratos de confidencialidad y privacidad. En caso de requerimientos de autoridad, compartiremos solo lo estrictamente necesario.</p>
        </section>

        <section id="conservacion" class="gsap-section">
          <h2>8. Conservación y eliminación</h2>
          <p>Conservamos tus datos por el tiempo necesario para cumplir las finalidades y obligaciones legales (p. ej., fiscales). Posteriormente los eliminamos o anonimizamos de forma segura.</p>
        </section>

        <section id="seguridad" class="gsap-section">
          <h2>9. Medidas de seguridad</h2>
          <p>Implementamos controles administrativos, técnicos y físicos razonables para proteger tus datos contra acceso, uso o divulgación no autorizados.</p>
        </section>

        <section id="derechos" class="gsap-section">
          <h2>10. Derechos ARCO y revocación</h2>
          <p>Puedes ejercer tus derechos de <strong>Acceso, Rectificación, Cancelación y Oposición</strong>, así como revocar tu consentimiento para finalidades secundarias, enviando una solicitud con identificación a los medios de <a href="#contacto">Contacto</a>. Te responderemos en los plazos legales aplicables.</p>
        </section>

        <section id="limitacion" class="gsap-section">
          <h2>11. Limitación del uso y divulgación</h2>
          <p>Puedes inscribirte en listados de exclusión propios y solicitar dejar de recibir comunicaciones promocionales. Cada correo incluirá un mecanismo para cancelar la suscripción.</p>
        </section>

        <section id="menores" class="gsap-section">
          <h2>12. Tratamiento de menores</h2>
          <p>Nuestros productos y servicios no están dirigidos a menores de 18 años. Si identificamos datos de menores sin autorización, procederemos a su supresión segura.</p>
        </section>

        <section id="cambios" class="gsap-section">
          <h2>13. Cambios al aviso</h2>
          <p>Podremos actualizar este Aviso. Publicaremos la versión vigente y, si el cambio afecta finalidades que requieran consentimiento, te lo solicitaremos nuevamente.</p>
        </section>

        <section id="contacto" class="gsap-section">
          <h2>14. Contacto</h2>
          <ul>
            <li><strong>Email:</strong> <a href="mailto:rtort@jureto.com.mx">rtort@jureto.com.mx</a></li>
            <li><strong>Teléfono:</strong> <a href="tel:+525541937243">+52 55 4193 7243</a></li>
            <li><strong>Ubicación:</strong> 7CP5+34M San Jerónimo Chicahualco, Estado de México &amp; UAE</li>
          </ul>
          <div class="hr"></div>
          <h3 id="vinculos">Vínculos útiles</h3>
          <ul>
            <li><a href="{{ url('/terminos-y-condiciones') }}">Términos y Condiciones</a></li>
            <li><a href="{{ url('/garantias-y-devoluciones') }}">Garantías y Devoluciones</a></li>
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

  // 2. Scrollspy simple con IntersectionObserver (Actualiza el índice activo)
  const links = Array.from(document.querySelectorAll('#privacy .toc a'));
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