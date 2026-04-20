@extends('layouts.web')
@section('title','Formas de Pago')

@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

<style>
  /* ========= NAMESPACE AISLADO ========= */
  #pay {
    --bg-pure: #ffffff;
    --text-dark: #0f172a;
    --text-gray: #475569;
    --border-light: #e2e8f0;
    --accent: #0f172a; /* Tono corporativo oscuro */
    --link: #2563eb; /* Azul profesional para enlaces */
    --radius: 12px;
  }

  #pay {
    position: relative;
    width: 100%;
    background-color: var(--bg-pure);
    color: var(--text-dark);
    font-family: 'Inter', system-ui, sans-serif;
  }

  #pay .wrap {
    max-width: 1200px;
    margin: 0 auto;
    padding: clamp(40px, 5vw, 80px) 24px;
  }

  /* ===== Hero ===== */
  #pay .hero { margin-bottom: 60px; }
  #pay .title {
    font-size: clamp(2.5rem, 4vw, 3.5rem);
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.03em;
    color: var(--text-dark);
    margin: 0 0 16px 0;
  }
  
  #pay .sub {
    font-size: 1.125rem;
    color: var(--text-gray);
    line-height: 1.7;
    max-width: 800px;
    margin: 0;
  }
  
  #pay .chips { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px; }
  #pay .chip {
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
  #pay .chip svg {
    width: 16px;
    height: 16px;
    stroke-width: 2;
  }

  /* ===== Layout con sidebar ===== */
  #pay .grid {
    display: grid;
    gap: 60px;
    grid-template-columns: 280px 1fr;
    align-items: start;
  }

  @media (max-width: 980px) {
    #pay .grid { grid-template-columns: 1fr; gap: 40px; }
  }

  /* ===== Sidebar (Índice) ===== */
  #pay .sidebar {
    position: sticky;
    top: 120px;
    border-right: 1px solid var(--border-light);
    padding-right: 24px;
  }
  
  #pay .sidebar h3 {
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-gray);
    margin: 0 0 16px 0;
  }
  
  #pay .toc { list-style: none; margin: 0; padding: 0; }
  #pay .toc li { margin: 4px 0; }
  #pay .toc a {
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
  #pay .toc a svg { width: 16px; height: 16px; stroke-width: 2; opacity: 0.7; }
  
  #pay .toc a:hover {
    color: var(--text-dark);
    background: #f8fafc;
  }
  
  #pay .toc a.active {
    color: var(--accent);
    font-weight: 600;
    border-left-color: var(--accent);
    background: #f8fafc;
  }
  #pay .toc a.active svg { opacity: 1; }
  
  #pay .toc .lvl2 { padding-left: 36px; font-size: 0.875rem; }
  #pay .toc .lvl2 svg { display: none; } /* Ocultar icono en subniveles para mantenerlo limpio */

  /* ===== Contenido ===== */
  #pay .content { padding-bottom: 80px; }
  
  #pay section {
    padding-top: 60px;
    margin-top: -40px; /* Compensa el padding para scroll-margin */
    scroll-margin-top: 100px;
  }
  
  #pay h2 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-dark);
    border-bottom: 1px solid var(--border-light);
    padding-bottom: 16px;
    margin: 0 0 24px 0;
    letter-spacing: -0.02em;
  }
  
  #pay h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 32px 0 16px 0;
  }
  
  #pay p {
    color: var(--text-gray);
    line-height: 1.7;
    font-size: 1.05rem;
    margin: 0 0 16px 0;
  }
  
  #pay ul, #pay ol {
    color: var(--text-gray);
    line-height: 1.7;
    font-size: 1.05rem;
    padding-left: 24px;
    margin: 0 0 24px 0;
  }
  
  #pay li { margin-bottom: 8px; }
  
  #pay .content a {
    color: var(--link);
    text-decoration: none;
    font-weight: 500;
  }
  
  #pay .content a:hover { text-decoration: underline; }
  
  #pay .note {
    background: #f8fafc;
    border-left: 4px solid var(--accent);
    padding: 16px 20px;
    border-radius: 0 8px 8px 0;
    margin: 24px 0;
    font-size: 1rem;
    color: var(--text-dark);
  }

  #pay .hr {
    height: 1px;
    background: var(--border-light);
    margin: 40px 0;
  }

  /* ===== Móvil: ocultar sidebar ===== */
  @media (max-width: 980px) {
    #pay .sidebar { display: none; }
  }

  /* ===== Botón flotante corporativo (móvil) ===== */
  #pay .fab {
    position: fixed; right: 24px; bottom: 24px; z-index: 50;
    width: 56px; height: 56px; border-radius: 50%;
    display: grid; place-items: center;
    background: var(--accent); color: #fff;
    border: none; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.2);
    cursor: pointer;
    transform: translateY(20px) scale(0.9); opacity: 0; pointer-events: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  #pay .fab svg { width: 24px; height: 24px; stroke-width: 2; }
  
  #pay .fab.show {
    transform: translateY(0) scale(1); opacity: 1; pointer-events: auto;
  }

  /* ===== Mini índice flotante (overlay móvil) ===== */
  #pay .mtoc-backdrop {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px); z-index: 49; opacity: 0; pointer-events: none; transition: .3s;
  }
  #pay .mtoc-backdrop.open { opacity: 1; pointer-events: auto; }

  #pay .mtoc {
    position: fixed; left: 0; right: 0; bottom: 0; z-index: 50;
    background: var(--bg-pure); border-radius: 24px 24px 0 0;
    box-shadow: 0 -10px 40px rgba(0,0,0,0.1);
    transform: translateY(100%); opacity: 0; pointer-events: none; transition: transform .4s cubic-bezier(0.16, 1, 0.3, 1), opacity .4s;
    max-height: 70vh; overflow: auto;
    padding-bottom: 24px;
  }
  #pay .mtoc.open { transform: translateY(0); opacity: 1; pointer-events: auto; }
  
  #pay .mtoc header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 20px 24px; border-bottom: 1px solid var(--border-light);
    position: sticky; top: 0; background: var(--bg-pure);
  }
  
  #pay .mtoc header h4 { margin: 0; font-size: 1.1rem; color: var(--text-dark); }
  #pay .mtoc header button {
    display: flex; align-items: center; justify-content: center;
    background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%;
    color: var(--text-dark); cursor: pointer; transition: background 0.2s;
  }
  #pay .mtoc header button:hover { background: #e2e8f0; }
  
  #pay .mtoc ul { list-style: none; margin: 0; padding: 16px 24px; }
  #pay .mtoc li a {
    display: flex; align-items: center; gap: 10px; padding: 12px 0; color: var(--text-gray); text-decoration: none; font-size: 1rem; border-bottom: 1px solid #f1f5f9;
  }
  #pay .mtoc li a svg { width: 18px; height: 18px; stroke-width: 2; opacity: 0.7; }
  #pay .mtoc li a:hover, #pay .mtoc li a.active { color: var(--accent); font-weight: 600; }
  #pay .mtoc li a.active svg { opacity: 1; }

  html { scroll-behavior: smooth; }
</style>

<div id="pay">
  <div class="wrap">

    <header class="hero gsap-hero">
      <h1 class="title">Formas de Pago</h1>
      <p class="sub">En <strong>Jureto</strong> ofrecemos métodos de pago seguros y flexibles según el tipo de compra y volumen de pedido.</p>
      <div class="chips">
        <span class="chip">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
            <line x1="1" y1="10" x2="23" y2="10"></line>
          </svg>
          Stripe
        </span>
        <span class="chip">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
          </svg>
          Transferencia / Efectivo
        </span>
      </div>
    </header>

    <div class="grid">
      <aside class="sidebar gsap-sidebar">
        <h3>Índice</h3>
        <ul class="toc" id="toc-desktop">
          <li>
            <a class="lvl1" href="#stripe">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
              1. Pagos en línea
            </a>
          </li>
          <li>
            <a class="lvl1" href="#mayoreo">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
              2. Ventas de mayoreo
            </a>
          </li>
          <li><a class="lvl2" href="#transferencia">• Transferencia bancaria</a></li>
          <li><a class="lvl2" href="#efectivo">• Efectivo contra entrega</a></li>
          <li>
            <a class="lvl1" href="#anticipo">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
              3. Anticipos y confirmación
            </a>
          </li>
          <li>
            <a class="lvl1" href="#seguridad">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
              4. Seguridad de la información
            </a>
          </li>
          <li>
            <a class="lvl1" href="#contacto">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
              5. Contacto
            </a>
          </li>
        </ul>
      </aside>

      <main class="content">
        <section id="stripe" class="gsap-section">
          <h2>1. Pagos en línea con Stripe</h2>
          <p>Para compras minoristas o pedidos realizados directamente en nuestro sitio web, utilizamos <strong>Stripe</strong> como procesador de pagos seguro y certificado.</p>
          <ul>
            <li>Stripe permite pagar con tarjetas de crédito y débito Visa, Mastercard y American Express.</li>
            <li>Todas las transacciones son cifradas mediante <strong>SSL/TLS</strong> y procesadas sin que Jureto almacene información sensible de las tarjetas.</li>
            <li>El comprobante de pago se genera automáticamente y se envía por correo electrónico una vez confirmada la operación.</li>
          </ul>
          <div class="note">Stripe es una plataforma global reconocida por su cumplimiento con PCI DSS, garantizando máxima seguridad en tus transacciones.</div>
        </section>

        <section id="mayoreo" class="gsap-section">
          <h2>2. Ventas de mayoreo</h2>
          <p>Las compras a mayoreo o corporativas pueden acordarse directamente con nuestro equipo comercial. Aceptamos dos modalidades principales:</p>

          <h3 id="transferencia">Transferencia bancaria</h3>
          <ul>
            <li>Las operaciones se realizan mediante transferencia directa a la cuenta bancaria oficial de <strong>Jureto</strong>.</li>
            <li>Los datos bancarios se proporcionan al confirmar el pedido y se validan únicamente desde nuestro correo institucional (<strong>rtort@jureto.com.mx</strong>).</li>
            <li>El pedido se programa para surtido una vez reflejado el anticipo o pago total acordado.</li>
          </ul>

          <h3 id="efectivo">Efectivo contra entrega</h3>
          <ul>
            <li>Disponible solo para clientes frecuentes o en entregas locales verificadas.</li>
            <li>Se requiere un <strong>anticipo mínimo</strong> del 30 % para apartar el pedido antes del despacho.</li>
            <li>El resto podrá liquidarse en efectivo al momento de la entrega, previa firma de conformidad.</li>
          </ul>
        </section>

        <section id="anticipo" class="gsap-section">
          <h2>3. Anticipos y confirmación</h2>
          <p>Todo pedido de mayoreo o especial debe contar con un anticipo para garantizar su preparación y reserva de inventario. El anticipo no es reembolsable si el cliente cancela una vez iniciado el proceso de compra o surtido.</p>
        </section>

        <section id="seguridad" class="gsap-section">
          <h2>4. Seguridad de la información</h2>
          <p><strong>Jureto</strong> no almacena números de tarjeta ni claves bancarias. Toda la información sensible se maneja exclusivamente por los sistemas cifrados de Stripe o por instituciones bancarias reguladas. Nuestro sitio cuenta con certificado SSL vigente y auditorías periódicas de seguridad.</p>
        </section>

        <section id="contacto" class="gsap-section">
          <h2>5. Contacto</h2>
          <ul>
            <li><strong>Email:</strong> <a href="mailto:rtort@jureto.com.mx">rtort@jureto.com.mx</a></li>
            <li><strong>Teléfono:</strong> <a href="tel:+525541937243">+52 55 4193 7243</a></li>
            <li><strong>Ubicación:</strong> 7CP5+34M San Jerónimo Chicahualco, Estado de México &amp; UAE</li>
          </ul>
          <div class="hr"></div>
          <ul>
            <li><a href="{{ route('policy.terms') }}">Términos y Condiciones</a></li>
            <li><a href="{{ route('policy.privacy') }}">Aviso de Privacidad</a></li>
            <li><a href="{{ route('policy.shipping') }}">Envíos y Devoluciones</a></li>
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
  const links = Array.from(document.querySelectorAll('#pay .toc a'));
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