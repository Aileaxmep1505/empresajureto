@extends('layouts.web')
@section('title','Envíos, Devoluciones y Cancelaciones')

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
    max-width: 800px;
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
    display: block;
    padding: 8px 12px;
    border-radius: 6px;
    color: var(--text-gray);
    text-decoration: none;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    border-left: 2px solid transparent;
  }
  
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
  
  #ship .toc .lvl2 { padding-left: 24px; font-size: 0.875rem; }

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
    display: block; padding: 12px 0; color: var(--text-gray); text-decoration: none; font-size: 1rem; border-bottom: 1px solid #f1f5f9;
  }
  #ship .mtoc li a:hover, #ship .mtoc li a.active { color: var(--accent); font-weight: 600; }

  html { scroll-behavior: smooth; }
</style>

<div id="ship">
  <div class="wrap">

    <header class="hero gsap-hero">
      <h1 class="title">Envíos, Devoluciones y Cancelaciones</h1>
      <p class="sub">Políticas aplicables a pedidos realizados con <strong>Jureto</strong>, empresa comercializadora de productos de papelería para el sector corporativo y educativo.</p>
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
            <rect x="1" y="3" width="15" height="13"></rect>
            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
            <circle cx="5.5" cy="18.5" r="2.5"></circle>
            <circle cx="18.5" cy="18.5" r="2.5"></circle>
          </svg>
          Logística y postventa
        </span>
      </div>
    </header>

    <div class="grid">
      <aside class="sidebar gsap-sidebar">
        <h3>Índice</h3>
        <ul class="toc" id="toc-desktop">
          <li><a class="lvl1" href="#envios">1. Envíos</a></li>
          <li><a class="lvl2" href="#plazos">• Plazos de preparación y tránsito</a></li>
          <li><a class="lvl2" href="#costos-zonas">• Costos y zonas de cobertura</a></li>
          <li><a class="lvl2" href="#rastreo">• Rastreo y entrega</a></li>
          <li><a class="lvl2" href="#riesgo">• Transferencia de riesgo e inspección</a></li>

          <li style="margin-top: 12px;"><a class="lvl1" href="#devoluciones">2. Devoluciones y Reembolsos</a></li>
          <li><a class="lvl2" href="#condiciones">• Condiciones para aceptar devoluciones</a></li>
          <li><a class="lvl2" href="#pasos">• Pasos para solicitar devolución</a></li>
          <li><a class="lvl2" href="#reembolsos">• Formas y tiempos de reembolso</a></li>
          <li><a class="lvl2" href="#no-retornables">• Productos no retornables</a></li>

          <li style="margin-top: 12px;"><a class="lvl1" href="#cancelaciones">3. Cancelaciones</a></li>
          <li><a class="lvl2" href="#antes-envio">• Antes del envío</a></li>
          <li><a class="lvl2" href="#despues-envio">• Después del envío</a></li>

          <li style="margin-top: 12px;"><a class="lvl1" href="#excepciones">4. Excepciones y casos especiales</a></li>
          <li style="margin-top: 12px;"><a class="lvl1" href="#contacto">5. Contacto</a></li>
        </ul>
      </aside>

      <main class="content">
        <section id="envios" class="gsap-section">
          <h2>1. Envíos</h2>
          <p>Gestionamos envíos a nivel nacional y, en ciertos casos, internacionalmente mediante paqueterías certificadas. El domicilio de entrega se toma del proceso de compra; es responsabilidad del cliente verificar que sea correcto.</p>

          <h3 id="plazos">Plazos de preparación y tránsito</h3>
          <ul>
            <li><strong>Preparación:</strong> 24–48 h hábiles para surtido y embalaje de artículos en stock.</li>
            <li><strong>Tránsito:</strong> 1–5 días hábiles en zonas metropolitanas; 3–8 días hábiles en zonas extendidas.</li>
            <li>Pedidos con artículos “bajo pedido” adicionan el tiempo del proveedor; te lo comunicaremos en la confirmación.</li>
          </ul>

          <h3 id="costos-zonas">Costos y zonas de cobertura</h3>
          <ul>
            <li>El costo se calcula por peso/volumen y destino. Podrán existir cargos adicionales por zonas extendidas.</li>
            <li>Promociones de envío gratis aplican a carritos que cumplan el mínimo establecido y condiciones anunciadas.</li>
          </ul>

          <h3 id="rastreo">Rastreo y entrega</h3>
          <ul>
            <li>Al despachar, recibirás tu <strong>número de guía</strong> para rastreo.</li>
            <li>La entrega puede requerir identificación y firma. Si no hay quien reciba, la paquetería realizará intentos adicionales o resguardo en sucursal.</li>
          </ul>

          <h3 id="riesgo">Transferencia de riesgo e inspección</h3>
          <p>El riesgo se transfiere al cliente al momento de la entrega registrada por la guía. Te pedimos <strong>revisar el paquete</strong> al recibir y anotar cualquier daño visible en la boleta del repartidor. Reportes de daño deben hacerse dentro de las <strong>24–48 h</strong> posteriores a la entrega con fotos del empaque y del producto.</p>
        </section>

        <section id="devoluciones" class="gsap-section">
          <h2>2. Devoluciones y Reembolsos</h2>
          <h3 id="condiciones">Condiciones para aceptar devoluciones</h3>
          <ul>
            <li>Plazo general: <strong>30 días naturales</strong> desde la entrega.</li>
            <li>El artículo debe estar <strong>sin uso</strong>, en su <strong>empaque original</strong>, con accesorios, manuales y obsequios.</li>
            <li>Debes <strong>notificar</strong> y obtener autorización previa; sin autorización no se reciben paquetes.</li>
          </ul>

          <h3 id="pasos">Pasos para solicitar devolución</h3>
          <ol>
            <li>Escríbenos a <a href="mailto:rtort@jureto.com.mx">rtort@jureto.com.mx</a> con número de pedido, fotos y motivo.</li>
            <li>Te enviaremos instrucciones y, cuando aplique, una guía de retorno o dirección de destino.</li>
            <li>Una vez recibido y validado el estado del producto, procesaremos el reembolso según corresponda.</li>
          </ol>

          <h3 id="reembolsos">Formas y tiempos de reembolso</h3>
          <ul>
            <li><strong>Medio original</strong> de pago o <strong>nota de crédito</strong>, según disponibilidad.</li>
            <li>Los plazos bancarios pueden ser de <strong>5–15 días hábiles</strong> tras la validación.</li>
          </ul>

          <h3 id="no-retornables">Productos no retornables</h3>
          <ul>
            <li>Consumibles abiertos (tintas, pegamentos, papeles especiales), artículos personalizados o bajo pedido.</li>
            <li>Artículos dañados por uso inadecuado, instalación incorrecta o sin accesorios.</li>
          </ul>

          <div class="note">Para defectos de fábrica fuera del periodo de devolución, aplica la <a href="{{ url('/garantias-y-devoluciones') }}">política de garantías</a> cuando corresponda.</div>
        </section>

        <section id="cancelaciones" class="gsap-section">
          <h2>3. Cancelaciones</h2>
          <h3 id="antes-envio">Antes del envío</h3>
          <p>Podrás solicitar cancelación <strong>antes</strong> de que el pedido sea despachado. Si el cobro ya se procesó, se hará reembolso al mismo medio de pago o nota de crédito.</p>

          <h3 id="despues-envio">Después del envío</h3>
          <p>Si el pedido ya fue despachado, la solicitud se tramita como <strong>devolución</strong> (ver sección 2). Los costos de retorno pueden aplicar salvo error atribuible a Jureto.</p>
        </section>

        <section id="excepciones" class="gsap-section">
          <h2>4. Excepciones y casos especiales</h2>
          <ul>
            <li><strong>Dirección incorrecta:</strong> reexpedición con costo adicional cuando aplique.</li>
            <li><strong>Paquete perdido:</strong> gestionamos aclaración con la paquetería; se repone o reembolsa según dictamen.</li>
            <li><strong>Daño en tránsito:</strong> reporta dentro de 24–48 h con evidencia fotográfica para proceder con la reclamación.</li>
          </ul>
        </section>

        <section id="contacto" class="gsap-section">
          <h2>5. Contacto</h2>
          <ul>
            <li><strong>Email:</strong> <a href="mailto:rtort@jureto.com.mx">rtort@jureto.com.mx</a></li>
            <li><strong>Teléfono:</strong> <a href="tel:+525541937243">+52 55 4193 7243</a></li>
            <li><strong>Ubicación:</strong> 7CP5+34M San Jerónimo Chicahualco, Estado de México &amp; UAE</li>
          </ul>
          
          <div class="hr"></div>
          
          <h3>Documentos relacionados</h3>
          <ul>
            <li><a href="{{ route('policy.terms') }}">Términos y Condiciones</a></li>
            <li><a href="{{ route('policy.privacy') }}">Aviso de Privacidad</a></li>
            @if(Route::has('policy.warranty'))
              <li><a href="{{ route('policy.warranty') }}">Garantías</a></li>
            @endif
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
  const links = Array.from(document.querySelectorAll('#ship .toc a'));
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