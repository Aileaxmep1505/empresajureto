@extends('layouts.web')
@section('title','Términos y Condiciones')

@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

<style>
  /* ========= NAMESPACE AISLADO Y REDISEÑO ULTRA MINIMALISTA ========= */
  #terms {
    --bg-pure: #ffffff;
    --text-dark: #0f172a; 
    --text-gray: #475569;
    --border-light: #e2e8f0;
    --accent: #0f172a; 
    --link: #007aff; 
    --radius: 12px;
  }

  #terms {
    position: relative;
    width: 100%;
    background-color: var(--bg-pure);
    color: var(--text-dark);
    font-family: 'Inter', system-ui, sans-serif;
  }

  #terms .wrap {
    max-width: 1200px;
    margin: 0 auto;
    padding: clamp(40px, 5vw, 80px) 24px;
  }

  /* ===== Hero ===== */
  #terms .hero { margin-bottom: 60px; }
  #terms .title {
    font-size: clamp(2.5rem, 4vw, 3.5rem);
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.03em;
    color: var(--text-dark);
    margin: 0 0 16px 0;
  }
  
  #terms .sub {
    font-size: 1.125rem;
    color: var(--text-gray);
    line-height: 1.7;
    max-width: 860px;
    margin: 0;
  }
  
  /* ===== Chips Minimalistas ===== */
  #terms .chips { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 24px; }
  #terms .chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #f8fafc;
    border: 1px solid var(--border-light);
    color: var(--text-gray);
    padding: 6px 16px;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 500;
  }
  #terms .chip svg { width: 14px; height: 14px; stroke-width: 2; }

  /* ===== Layout ===== */
  #terms .grid {
    display: grid;
    gap: 60px;
    grid-template-columns: 280px 1fr;
    align-items: start;
  }

  @media (max-width: 980px) {
    #terms .grid { grid-template-columns: 1fr; gap: 40px; }
  }

  /* ===== Sidebar ===== */
  #terms .sidebar {
    position: sticky;
    top: 120px;
    border-right: 1px solid var(--border-light);
    padding-right: 24px;
  }

  #terms .sidebar h3 {
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-gray);
    margin: 0 0 16px 0;
  }
  
  #terms .toc { list-style: none; margin: 0; padding: 0; }
  #terms .toc li { margin: 2px 0; }
  #terms .toc a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    border-radius: 6px;
    color: var(--text-gray);
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
    transition: all 0.2s ease;
    border-left: 2px solid transparent;
  }
  
  #terms .toc a svg { width: 16px; height: 16px; stroke-width: 2; opacity: 0.7; }
  
  #terms .toc a:hover { color: var(--text-dark); background: #f8fafc; }
  
  #terms .toc a.active {
    color: var(--accent);
    font-weight: 600;
    border-left-color: var(--accent);
    background: #f8fafc;
  }
  #terms .toc a.active svg { opacity: 1; }

  /* ===== Contenido ===== */
  #terms .content { padding-bottom: 40px; }
  
  #terms section {
    padding-top: 60px;
    margin-top: -40px; 
    scroll-margin-top: 100px;
  }
  
  #terms h2 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-dark);
    border-bottom: 1px solid var(--border-light);
    padding-bottom: 16px;
    margin: 0 0 24px 0;
    letter-spacing: -0.02em;
  }

  #terms h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 32px 0 16px 0;
  }
  
  #terms p {
    color: var(--text-gray);
    line-height: 1.7;
    font-size: 1.05rem;
    margin: 0 0 16px 0;
  }
  
  #terms ul, #terms ol {
    color: var(--text-gray);
    line-height: 1.7;
    font-size: 1.05rem;
    padding-left: 24px;
    margin: 0 0 24px 0;
  }
  
  #terms li { margin-bottom: 8px; }
  
  #terms .content a {
    color: var(--link);
    text-decoration: none;
    font-weight: 500;
  }
  
  #terms .content a:hover { text-decoration: underline; }
  
  #terms .note {
    background: #f8fafc;
    border-left: 4px solid var(--accent);
    padding: 16px 20px;
    border-radius: 0 8px 8px 0;
    margin: 24px 0;
    font-size: 1rem;
    color: var(--text-dark);
  }

  #terms .hr {
    height: 1px;
    background: var(--border-light);
    margin: 40px 0;
  }

  /* ===== Móvil: ocultar sidebar ===== */
  @media (max-width: 980px) {
    #terms .sidebar { display: none; }
  }

  /* ===== Botón flotante corporativo (móvil) ===== */
  #terms .fab {
    position: fixed; right: 24px; bottom: 24px; z-index: 50;
    width: 56px; height: 56px; border-radius: 50%;
    display: grid; place-items: center;
    background: var(--accent); color: #fff;
    border: none; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.2);
    cursor: pointer;
    transform: translateY(20px) scale(0.9); opacity: 0; pointer-events: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  #terms .fab svg { width: 24px; height: 24px; stroke-width: 2; }
  #terms .fab.show { transform: translateY(0) scale(1); opacity: 1; pointer-events: auto; }

  /* ===== Mini índice flotante (overlay móvil) ===== */
  #terms .mtoc-backdrop {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px); z-index: 49; opacity: 0; pointer-events: none; transition: .3s;
  }
  #terms .mtoc-backdrop.open { opacity: 1; pointer-events: auto; }

  #terms .mtoc {
    position: fixed; left: 0; right: 0; bottom: 0; z-index: 50;
    background: var(--bg-pure); border-radius: 24px 24px 0 0;
    box-shadow: 0 -10px 40px rgba(0,0,0,0.1);
    transform: translateY(100%); opacity: 0; pointer-events: none; transition: transform .4s cubic-bezier(0.16, 1, 0.3, 1), opacity .4s;
    max-height: 70vh; overflow: auto;
    padding-bottom: 24px;
  }
  #terms .mtoc.open { transform: translateY(0); opacity: 1; pointer-events: auto; }
  
  #terms .mtoc header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 20px 24px; border-bottom: 1px solid var(--border-light);
    position: sticky; top: 0; background: var(--bg-pure);
  }
  
  #terms .mtoc header h4 { margin: 0; font-size: 1.1rem; color: var(--text-dark); }
  #terms .mtoc header button {
    display: flex; align-items: center; justify-content: center;
    background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%;
    color: var(--text-dark); cursor: pointer; transition: background 0.2s;
  }
  #terms .mtoc header button:hover { background: #e2e8f0; }
  
  #terms .mtoc ul { list-style: none; margin: 0; padding: 16px 24px; }
  #terms .mtoc li a {
    display: flex; align-items: center; gap: 10px; padding: 12px 0; color: var(--text-gray); text-decoration: none; font-size: 1rem; border-bottom: 1px solid #f1f5f9;
  }
  #terms .mtoc li a svg { width: 18px; height: 18px; stroke-width: 2; opacity: 0.7; }
  #terms .mtoc li a:hover, #terms .mtoc li a.active { color: var(--accent); font-weight: 600; }
  #terms .mtoc li a.active svg { opacity: 1; }

  html { scroll-behavior: smooth; }
</style>

<div id="terms">
  <div class="wrap">

    <header class="hero gsap-hero">
      <h1 class="title">Términos y Condiciones</h1>
      <p class="sub">Reglas claras, transparentes y corporativas para el uso de nuestra plataforma y la adquisición de suministros en <strong>Jureto</strong>.</p>
      
      <div class="chips">
        <span class="chip">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
          Última actualización: 21 de octubre de 2025
        </span>
        <span class="chip">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          Información Legal
        </span>
      </div>
    </header>

    <div class="grid">
      <aside class="sidebar gsap-sidebar">
        <h3>Índice</h3>
        <ul class="toc" id="toc-desktop">
          <li>
            <a href="#general">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
              1. Información General
            </a>
          </li>
          <li>
            <a href="#uso">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
              2. Uso del Sitio
            </a>
          </li>
          <li>
            <a href="#precios">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
              3. Precios y Productos
            </a>
          </li>
          <li>
            <a href="#compras">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
              4. Proceso de Compra
            </a>
          </li>
          <li>
            <a href="#pagos">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
              5. Pagos y Facturación
            </a>
          </li>
          <li>
            <a href="#envios">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
              6. Envíos y Entregas
            </a>
          </li>
          <li>
            <a href="#garantias">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 14 4 9 9 4"></polyline><path d="M20 20v-7a4 4 0 0 0-4-4H4"></path></svg>
              7. Devoluciones
            </a>
          </li>
          <li>
            <a href="#propiedad">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
              8. Propiedad Intelectual
            </a>
          </li>
          <li>
            <a href="#responsabilidad">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
              9. Responsabilidad
            </a>
          </li>
          <li>
            <a href="#contacto">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
              10. Contacto
            </a>
          </li>
        </ul>
      </aside>

      <main class="content">
        
        <section id="general" class="gsap-section">
          <h2>1. Información General</h2>
          <p>Bienvenido a <strong>Jureto</strong>. Los presentes Términos y Condiciones regulan el acceso y uso de nuestro sitio web, así como la compra y venta de productos de papelería, mobiliario, equipo de oficina y suministros corporativos que ofrecemos.</p>
          <p>Al acceder, navegar o utilizar nuestra plataforma, usted confirma que ha leído, entendido y aceptado quedar sujeto a estos Términos. Si no está de acuerdo con alguna parte de los mismos, le solicitamos abstenerse de utilizar nuestros servicios.</p>
        </section>

        <section id="uso" class="gsap-section">
          <h2>2. Uso del Sitio y Cuenta de Usuario</h2>
          <p>Para realizar compras en Jureto, puede hacerlo como invitado o registrando una cuenta corporativa/personal. Al registrarse, usted se compromete a proporcionar información veraz, precisa y actualizada.</p>
          <ul>
            <li>Usted es responsable de mantener la confidencialidad de su contraseña y cuenta.</li>
            <li>Jureto no se hace responsable por accesos no autorizados resultantes de negligencia por parte del usuario.</li>
            <li>Nos reservamos el derecho de suspender o cancelar cuentas que violen estos términos, presenten actividad fraudulenta o proporcionen información falsa.</li>
          </ul>
        </section>

        <section id="precios" class="gsap-section">
          <h2>3. Precios y Disponibilidad de Productos</h2>
          <p>Todos los precios mostrados en el sitio web están expresados en Moneda Nacional (Pesos Mexicanos - MXN) e incluyen el Impuesto al Valor Agregado (IVA), salvo que se indique explícitamente lo contrario en esquemas de mayoreo.</p>
          <ul>
            <li><strong>Disponibilidad:</strong> Nuestro inventario se actualiza constantemente; sin embargo, en casos excepcionales de sobredemanda (especialmente en temporadas de regreso a clases o licitaciones), un producto podría agotarse tras realizar el pedido. En tal caso, nos comunicaremos para ofrecer un reemplazo o el reembolso íntegro.</li>
            <li><strong>Errores tipográficos:</strong> Si por un error del sistema un producto muestra un precio incorrecto de manera evidente (por ejemplo, un equipo de $10,000 MXN listado en $10 MXN), Jureto se reserva el derecho de cancelar dicho pedido, notificando al cliente y reembolsando cualquier cantidad pagada.</li>
          </ul>
        </section>

        <section id="compras" class="gsap-section">
          <h2>4. Proceso de Compra y Aceptación</h2>
          <p>La recepción de un correo de confirmación de pedido automático no significa la aceptación legal de su pedido ni confirma nuestra oferta de venta. Jureto se reserva el derecho, en cualquier momento tras recibir su pedido, de aceptarlo, rechazarlo o limitar las cantidades a surtir por cualquier motivo razonable, particularmente en compras de alto volumen o riesgos de reventa no autorizada.</p>
        </section>

        <section id="pagos" class="gsap-section">
          <h2>5. Pagos y Facturación</h2>
          <p>Los pagos en línea se procesan de manera segura a través de <strong>Stripe</strong>. No almacenamos información sensible de tarjetas de crédito o débito. Las compras a mayoreo pueden gestionarse vía transferencia bancaria institucional.</p>
          <div class="note">Para más detalles, consulte nuestra política de <a href="{{ route('policy.payments') }}">Formas de Pago</a>.</div>
          <p>Si requiere comprobante fiscal (CFDI), deberá solicitarlo durante el proceso de checkout o comunicándose a nuestro correo de contacto dentro del mismo mes calendario en que se realizó la compra, adjuntando su Constancia de Situación Fiscal actualizada.</p>
        </section>

        <section id="envios" class="gsap-section">
          <h2>6. Envíos y Entregas</h2>
          <p>Realizamos despachos a través de servicios de paquetería de terceros. El cliente es responsable de proporcionar una dirección de envío correcta y completa. Cualquier costo adicional generado por reexpediciones, direcciones incorrectas o rechazos de entrega correrá por cuenta del cliente.</p>
          <div class="note">Para conocer los tiempos de tránsito, costos y operativas, consulte nuestra política de <a href="{{ route('policy.shipping') }}">Formas de Envío</a>.</div>
        </section>

        <section id="garantias" class="gsap-section">
          <h2>7. Garantías y Devoluciones</h2>
          <p>En Jureto respaldamos la calidad de nuestros productos. Contamos con un periodo de devoluciones de 30 días hábiles bajo condiciones específicas (empaque cerrado, sin uso). Las garantías por defectos de fábrica se tramitan directamente conforme a las políticas de cada marca o fabricante.</p>
          <div class="note">Los procedimientos exactos y exclusiones (como consumibles o software) están detallados en nuestra política de <a href="{{ route('policy.shipping') }}">Garantías y Devoluciones</a>.</div>
        </section>

        <section id="propiedad" class="gsap-section">
          <h2>8. Propiedad Intelectual</h2>
          <p>Todo el contenido alojado o disponible en la plataforma Jureto, incluyendo texto, gráficos, logotipos, iconos, imágenes, clips de audio y descargas digitales, es propiedad de Jureto o de sus proveedores de contenido y está protegido por las leyes de propiedad intelectual e industrial aplicables en México e internacionalmente.</p>
          <p>No se permite la extracción, minería de datos, o reutilización del contenido de nuestro sitio sin consentimiento expreso y por escrito.</p>
        </section>

        <section id="responsabilidad" class="gsap-section">
          <h2>9. Limitación de Responsabilidad</h2>
          <p>Jureto opera la plataforma web en el estado en el que se encuentra y según disponibilidad. No garantizamos que el sitio estará ininterrumpido o libre de errores en todo momento.</p>
          <p>En la máxima medida permitida por la ley aplicable, Jureto no será responsable por daños directos, indirectos, incidentales, punitivos o consecuentes derivados del uso de nuestro sitio web o de la adquisición de nuestros productos, salvo en los casos en que la ley exija una cobertura específica.</p>
        </section>

        <section id="contacto" class="gsap-section">
          <h2>10. Modificaciones y Contacto</h2>
          <p>Jureto se reserva el derecho de realizar cambios en su sitio web, políticas y en estos Términos y Condiciones en cualquier momento. El uso continuado del sitio implicará su aceptación de dichos cambios.</p>
          <p>Para dudas, aclaraciones o requerimientos corporativos, nuestro equipo está a su entera disposición:</p>
          <ul>
            <li><strong>Email:</strong> <a href="mailto:rtort@jureto.com.mx">rtort@jureto.com.mx</a></li>
            <li><strong>Teléfono:</strong> <a href="tel:+525541937243">+52 55 4193 7243</a></li>
            <li><strong>Dirección:</strong> 7CP5+34M San Jerónimo Chicahualco, Estado de México &amp; UAE</li>
          </ul>
          
          <div class="hr"></div>
          
          <h3>Enlaces Rápidos</h3>
          <ul>
            <li><a href="{{ route('policy.privacy') }}">Aviso de Privacidad</a></li>
            <li><a href="{{ route('policy.shipping') }}">Garantías, Envíos y Devoluciones</a></li>
            <li><a href="{{ route('policy.payments') }}">Formas de Pago</a></li>
          </ul>
        </section>

      </main>
    </div>
  </div>

  <button id="fab" class="fab" aria-label="Índice">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
  </button>

  <div id="mtoc-backdrop" class="mtoc-backdrop"></div>
  <nav id="mtoc" class="mtoc" aria-label="Índice móvil">
    <header>
      <h4>Navegación Legal</h4>
      <button type="button" id="mtoc-close" aria-label="Cerrar">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </header>
    <ul id="toc-mobile"></ul>
  </nav>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  // 1. Inicializar GSAP
  gsap.registerPlugin(ScrollTrigger);

  // Animación Hero
  gsap.fromTo(".gsap-hero > *", 
    { opacity: 0, y: 20 }, 
    { opacity: 1, y: 0, duration: 0.8, stagger: 0.1, ease: "power3.out" }
  );

  // Animación Sidebar
  gsap.fromTo(".gsap-sidebar", 
    { opacity: 0, x: -20 }, 
    { opacity: 1, x: 0, duration: 0.8, delay: 0.3, ease: "power3.out" }
  );

  // Animación de secciones
  gsap.utils.toArray('.gsap-section').forEach((sec) => {
    gsap.fromTo(sec, 
      { opacity: 0, y: 30 }, 
      {
        scrollTrigger: { trigger: sec, start: "top 85%", toggleActions: "play none none reverse" },
        opacity: 1, y: 0, duration: 0.8, ease: "power3.out"
      }
    );
  });

  // 2. Scrollspy 
  const root = document.getElementById('terms');
  if (!root) return;

  const OFFSET = 120;
  const links = Array.from(document.querySelectorAll('#terms .toc a'));
  const targets = links.map(a => document.querySelector(a.getAttribute('href'))).filter(Boolean);

  function smoothTo(id){
    const el = document.querySelector(id); 
    if(!el) return;
    const top = window.scrollY + el.getBoundingClientRect().top - OFFSET;
    window.scrollTo({top, behavior:'smooth'});
  }

  document.querySelectorAll('#terms .toc a, #toc-mobile a').forEach(a => {
    a.addEventListener('click', e => {
      const href = a.getAttribute('href'); 
      if(!href?.startsWith('#')) return;
      e.preventDefault(); 
      smoothTo(href);
    });
  });

  let tops = [];
  function computeTops(){ 
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
    document.querySelectorAll('#toc-mobile a').forEach(a => {
      a.classList.toggle('active', a.getAttribute('href') === current);
    });
  }

  window.addEventListener('scroll', setActive, {passive: true});
  window.addEventListener('resize', () => { computeTops(); setActive(); });
  window.addEventListener('load', () => { computeTops(); setActive(); });
  setTimeout(() => { computeTops(); setActive(); }, 250);

  // 3. Móvil (Menú Flotante)
  const fab = document.getElementById('fab');
  const mtoc = document.getElementById('mtoc');
  const backdrop = document.getElementById('mtoc-backdrop');
  const closeBtn = document.getElementById('mtoc-close');
  const tocDesktop = document.getElementById('toc-desktop');
  const tocMobile = document.querySelector('#mtoc #toc-mobile');

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