@extends('layouts.web')
@section('title','Preguntas Frecuentes')

@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

<style>
  /* ========= NAMESPACE AISLADO Y REDISEÑO ULTRA MINIMALISTA ========= */
  #faq {
    --bg-pure: #ffffff;
    --text-dark: #0f172a; /* Navy muy oscuro para títulos */
    --text-gray: #475569;
    --border-light: #e2e8f0;
    --border-focus: #cbd5e1;
    --accent: #0f172a; 
    --link: #007aff; /* Azul para enlaces y el banner final */
  }

  #faq {
    position: relative;
    width: 100%;
    background-color: var(--bg-pure);
    color: var(--text-dark);
    font-family: 'Inter', system-ui, sans-serif;
  }

  #faq .wrap {
    max-width: 1200px;
    margin: 0 auto;
    padding: clamp(40px, 5vw, 80px) 24px;
  }

  /* ===== Hero ===== */
  #faq .hero { margin-bottom: 60px; }
  #faq .title {
    font-size: clamp(2.5rem, 4vw, 3.5rem);
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.03em;
    color: var(--text-dark);
    margin: 0 0 16px 0;
  }
  
  #faq .sub {
    font-size: 1.125rem;
    color: var(--text-gray);
    line-height: 1.6;
    max-width: 860px;
    margin: 0;
  }
  
  /* ===== Chips Minimalistas ===== */
  #faq .chips { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 24px; }
  #faq .chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: transparent;
    border: 1px solid var(--border-light);
    color: var(--text-gray);
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 500;
    transition: border-color 0.2s ease;
  }
  #faq .chip:hover { border-color: var(--text-gray); }
  #faq .chip svg { width: 14px; height: 14px; stroke-width: 2; }

  /* ===== Layout ===== */
  #faq .grid {
    display: grid;
    gap: 60px;
    grid-template-columns: 280px 1fr;
    align-items: start;
  }

  @media (max-width: 980px) {
    #faq .grid { grid-template-columns: 1fr; gap: 40px; }
  }

  /* ===== Sidebar ===== */
  #faq .sidebar {
    position: sticky;
    top: 120px;
    border-right: 1px solid var(--border-light);
    padding-right: 24px;
  }
  
  #faq .toc { list-style: none; margin: 0; padding: 0; }
  #faq .toc li { margin: 2px 0; }
  #faq .toc a {
    display: flex;
    align-items: center;
    padding: 10px 12px;
    border-radius: 6px;
    color: var(--text-gray);
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
    transition: all 0.2s ease;
    border-left: 2px solid transparent;
  }
  
  #faq .toc a:hover { color: var(--text-dark); background: #f8fafc; }
  
  #faq .toc a.active {
    color: var(--text-dark);
    font-weight: 700;
    border-left-color: var(--text-dark);
    background: transparent;
  }

  /* ===== Contenido ===== */
  #faq .content { padding-bottom: 40px; }
  
  #faq section {
    padding-top: 60px;
    margin-top: -40px; 
    scroll-margin-top: 100px;
  }
  
  #faq h2 {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--text-dark);
    border-bottom: 1px solid var(--border-light);
    padding-bottom: 16px;
    margin: 0 0 24px 0;
    letter-spacing: -0.02em;
  }

  /* ===== Acordeones Ultra Minimalistas (Inspirados en la captura) ===== */
  .faq-accordion {
    border: 1px solid var(--border-light);
    border-radius: 8px;
    margin-bottom: 12px;
    background: var(--bg-pure);
    overflow: hidden;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
  }

  /* Efecto sutil al abrir */
  .faq-accordion.is-open {
    border-color: var(--border-focus);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
  }

  .faq-trigger {
    width: 100%;
    text-align: left;
    background: none;
    border: none;
    padding: 20px 24px;
    font-size: 1.05rem;
    font-weight: 600;
    color: var(--text-dark);
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background-color 0.2s ease;
  }

  .faq-trigger:hover { background-color: #f8fafc; }

  /* Icono Chevron fino */
  .faq-chevron {
    width: 20px;
    height: 20px;
    stroke: var(--text-dark);
    stroke-width: 1.5;
    fill: none;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .faq-accordion.is-open .faq-chevron {
    transform: rotate(180deg);
  }

  /* Contenedor de la respuesta (para animar altura) */
  .faq-content {
    display: grid;
    grid-template-rows: 0fr;
    transition: grid-template-rows 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .faq-accordion.is-open .faq-content {
    grid-template-rows: 1fr;
  }

  .faq-inner {
    overflow: hidden;
  }

  .faq-text {
    padding: 0 24px 24px 24px;
    color: var(--text-gray);
    line-height: 1.6;
    font-size: 0.95rem;
    margin: 0;
  }
  
  #faq .faq-text a {
    color: var(--link);
    text-decoration: none;
    font-weight: 500;
  }
  #faq .faq-text a:hover { text-decoration: underline; }
  
  #faq .faq-text ul { padding-left: 20px; margin: 10px 0 0 0; }
  #faq .faq-text li { margin-bottom: 6px; }

  /* ===== Banner Centro de Ayuda (Inspirado en la primera captura) ===== */
  .help-banner {
    margin-top: 60px;
    border-radius: 20px;
    background: linear-gradient(135deg, #a0c4ff 0%, #007aff 100%);
    padding: 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 40px;
    color: #ffffff;
    box-shadow: 0 20px 40px rgba(0, 122, 255, 0.15);
    overflow: hidden;
    position: relative;
  }

  /* Patrón decorativo de fondo sutil */
  .help-banner::after {
    content: "";
    position: absolute;
    right: -10%;
    top: -20%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 60%);
    pointer-events: none;
  }

  .help-banner-content {
    position: relative;
    z-index: 1;
    max-width: 600px;
  }

  .help-banner h3 {
    font-size: clamp(1.5rem, 3vw, 2rem);
    font-weight: 600;
    margin: 0 0 24px 0;
    line-height: 1.2;
    letter-spacing: -0.01em;
  }

  .btn-white {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 28px;
    background-color: #ffffff;
    color: #007aff;
    font-weight: 600;
    font-size: 0.95rem;
    border-radius: 999px; /* Botón píldora como en la imagen */
    text-decoration: none;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .btn-white:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
  }

  /* ===== Móvil: ocultar sidebar y ajustar banner ===== */
  @media (max-width: 980px) {
    #faq .sidebar { display: none; }
    .faq-trigger { padding: 16px; font-size: 1rem; }
    .faq-text { padding: 0 16px 16px 16px; }
    .help-banner { flex-direction: column; text-align: center; padding: 30px 20px; }
  }

  /* ===== Botón flotante corporativo (móvil) ===== */
  #faq .fab {
    position: fixed; right: 24px; bottom: 24px; z-index: 50;
    width: 56px; height: 56px; border-radius: 50%;
    display: grid; place-items: center;
    background: var(--accent); color: #fff;
    border: none; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.2);
    cursor: pointer;
    transform: translateY(20px) scale(0.9); opacity: 0; pointer-events: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  #faq .fab svg { width: 24px; height: 24px; stroke-width: 2; }
  #faq .fab.show { transform: translateY(0) scale(1); opacity: 1; pointer-events: auto; }

  /* ===== Mini índice flotante (overlay móvil) ===== */
  #faq .mtoc-backdrop {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px); z-index: 49; opacity: 0; pointer-events: none; transition: .3s;
  }
  #faq .mtoc-backdrop.open { opacity: 1; pointer-events: auto; }

  #faq .mtoc {
    position: fixed; left: 0; right: 0; bottom: 0; z-index: 50;
    background: var(--bg-pure); border-radius: 24px 24px 0 0;
    box-shadow: 0 -10px 40px rgba(0,0,0,0.1);
    transform: translateY(100%); opacity: 0; pointer-events: none; transition: transform .4s cubic-bezier(0.16, 1, 0.3, 1), opacity .4s;
    max-height: 70vh; overflow: auto;
    padding-bottom: 24px;
  }
  #faq .mtoc.open { transform: translateY(0); opacity: 1; pointer-events: auto; }
  
  #faq .mtoc header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 20px 24px; border-bottom: 1px solid var(--border-light);
    position: sticky; top: 0; background: var(--bg-pure);
  }
  
  #faq .mtoc header h4 { margin: 0; font-size: 1.1rem; color: var(--text-dark); }
  #faq .mtoc header button {
    display: flex; align-items: center; justify-content: center;
    background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%;
    color: var(--text-dark); cursor: pointer; transition: background 0.2s;
  }
  #faq .mtoc header button:hover { background: #e2e8f0; }
  
  #faq .mtoc ul { list-style: none; margin: 0; padding: 16px 24px; }
  #faq .mtoc li a {
    display: block; padding: 12px 0; color: var(--text-gray); text-decoration: none; font-size: 1rem; border-bottom: 1px solid #f1f5f9;
  }
  #faq .mtoc li a:hover, #faq .mtoc li a.active { color: var(--text-dark); font-weight: 600; }

  html { scroll-behavior: smooth; }
</style>

<div id="faq">
  <div class="wrap">

    <header class="hero gsap-hero">
      <h1 class="title">Preguntas Frecuentes</h1>
      <p class="sub">Encuentra respuestas rápidas y claras sobre el funcionamiento de nuestra plataforma.</p>
      
      <div class="chips">
        <span class="chip">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
          Pagos
        </span>
        <span class="chip">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
          Envíos
        </span>
        <span class="chip">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 14 4 9 9 4"></polyline><path d="M20 20v-7a4 4 0 0 0-4-4H4"></path></svg>
          Devoluciones
        </span>
      </div>
    </header>

    <div class="grid">
      <aside class="sidebar gsap-sidebar">
        <ul class="toc" id="toc-desktop">
          <li><a href="#pagos">1. Pagos</a></li>
          <li><a href="#mayoreo">2. Mayoreo</a></li>
          <li><a href="#envios">3. Envíos</a></li>
          <li><a href="#devoluciones">4. Devoluciones</a></li>
          <li><a href="#garantias">5. Garantías</a></li>
          <li><a href="#cuenta">6. Cuenta y pedidos</a></li>
          <li><a href="#facturacion">7. Facturación</a></li>
        </ul>
      </aside>

      <main class="content">
        
        <section id="pagos" class="gsap-section">
          <h2>1. Pagos</h2>

          <div class="faq-accordion">
            <button class="faq-trigger" aria-expanded="false">
              ¿Qué métodos de pago aceptan?
              <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="faq-content">
              <div class="faq-inner">
                <div class="faq-text">
                  <p>En compras en línea aceptamos pagos con tarjetas de crédito y débito mediante la plataforma <strong>Stripe</strong> (Visa, Mastercard, American Express). Para compras de mayoreo, por favor consulta la sección correspondiente para opciones de transferencia bancaria.</p>
                </div>
              </div>
            </div>
          </div>

          <div class="faq-accordion">
            <button class="faq-trigger" aria-expanded="false">
              ¿Es seguro pagar con Stripe?
              <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="faq-content">
              <div class="faq-inner">
                <div class="faq-text">
                  <p>Totalmente. Stripe procesa las transacciones con cifrado de grado militar y cumple con los estándares <strong>PCI DSS</strong>. En ningún momento almacenamos los datos sensibles de tus tarjetas en nuestros servidores.</p>
                </div>
              </div>
            </div>
          </div>

          <div class="faq-accordion">
            <button class="faq-trigger" aria-expanded="false">
              Mi pago falló, ¿qué puedo hacer?
              <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="faq-content">
              <div class="faq-inner">
                <div class="faq-text">
                  <ul>
                    <li>Verifica contar con fondos suficientes y que tu banco tenga habilitadas las compras en línea.</li>
                    <li>Revisa que los datos ingresados (CVV, fecha de vencimiento) sean correctos.</li>
                    <li>Si el error persiste, intenta con otra tarjeta o contáctanos a <a href="mailto:rtort@jureto.com.mx">rtort@jureto.com.mx</a>.</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section id="mayoreo" class="gsap-section">
          <h2>2. Mayoreo</h2>

          <div class="faq-accordion">
            <button class="faq-trigger" aria-expanded="false">
              ¿Cómo pago pedidos de mayoreo?
              <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="faq-content">
              <div class="faq-inner">
                <div class="faq-text">
                  <p>Operamos mediante <strong>transferencia bancaria</strong> institucional o <strong>efectivo contra entrega</strong> (solo en zonas locales autorizadas). Requerimos un anticipo para asegurar el inventario y programar la logística de envío.</p>
                </div>
              </div>
            </div>
          </div>

          <div class="faq-accordion">
            <button class="faq-trigger" aria-expanded="false">
              ¿De cuánto es el anticipo?
              <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="faq-content">
              <div class="faq-inner">
                <div class="faq-text">
                  <p>Por regla general solicitamos el <strong>30%</strong> de anticipo, aunque este porcentaje puede ajustarse dependiendo del volumen y categoría del producto. Los detalles te serán confirmados por tu asesor comercial.</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section id="envios" class="gsap-section">
          <h2>3. Envíos</h2>

          <div class="faq-accordion">
            <button class="faq-trigger" aria-expanded="false">
              ¿Cuánto tarda mi pedido en llegar?
              <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="faq-content">
              <div class="faq-inner">
                <div class="faq-text">
                  <p>El procesamiento en almacén toma entre 24 y 48 horas hábiles. Posteriormente, el tiempo de tránsito es de 1 a 5 días hábiles para zonas metropolitanas y de 3 a 8 días para zonas extendidas. Recibirás tu guía de rastreo al despachar.</p>
                </div>
              </div>
            </div>
          </div>

          <div class="faq-accordion">
            <button class="faq-trigger" aria-expanded="false">
              ¿Tienen envío gratis?
              <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="faq-content">
              <div class="faq-inner">
                <div class="faq-text">
                  <p>Sí, aplicable a pedidos que superen el monto mínimo establecido en nuestras promociones vigentes. El descuento se reflejará automáticamente en tu carrito de compras.</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section id="devoluciones" class="gsap-section">
          <h2>4. Devoluciones</h2>

          <div class="faq-accordion">
            <button class="faq-trigger" aria-expanded="false">
              ¿Puedo devolver un producto?
              <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="faq-content">
              <div class="faq-inner">
                <div class="faq-text">
                  <p>Sí, dispones de <strong>30 días naturales</strong> tras la recepción. El artículo debe retornar sin uso, en su empaque original impecable y con todos sus manuales y accesorios. Es indispensable solicitar autorización previa.</p>
                </div>
              </div>
            </div>
          </div>

          <div class="faq-accordion">
            <button class="faq-trigger" aria-expanded="false">
              ¿Cómo tramito un reembolso?
              <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="faq-content">
              <div class="faq-inner">
                <div class="faq-text">
                  <p>Envía un correo a <a href="mailto:rtort@jureto.com.mx">rtort@jureto.com.mx</a> indicando tu número de pedido, el motivo y adjuntando evidencia fotográfica. Tras la validación, el reembolso se procesará a tu método de pago original o mediante nota de crédito.</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section id="garantias" class="gsap-section">
          <h2>5. Garantías</h2>

          <div class="faq-accordion">
            <button class="faq-trigger" aria-expanded="false">
              Mi producto llegó dañado, ¿qué hago?
              <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="faq-content">
              <div class="faq-inner">
                <div class="faq-text">
                  <p>Repórtalo máximo 48 horas después de haberlo recibido. Es crucial adjuntar fotografías claras del daño en el producto y del estado de la caja exterior para iniciar la reclamación con la paquetería.</p>
                </div>
              </div>
            </div>
          </div>

          <div class="faq-accordion">
            <button class="faq-trigger" aria-expanded="false">
              ¿Cómo funciona el proceso de garantía?
              <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="faq-content">
              <div class="faq-inner">
                <div class="faq-text">
                  <p>El trámite se basa en las políticas del fabricante. Requiere revisión del número de serie y dictamen técnico para determinar si procede una reparación, reemplazo o emisión de saldo a favor.</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section id="cuenta" class="gsap-section">
          <h2>6. Cuenta y pedidos</h2>

          <div class="faq-accordion">
            <button class="faq-trigger" aria-expanded="false">
              ¿Necesito crear una cuenta para comprar?
              <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="faq-content">
              <div class="faq-inner">
                <div class="faq-text">
                  <p>No es obligatorio. Puedes realizar tus compras como invitado. Sin embargo, registrarte te facilita el seguimiento de tus paquetes, descarga de facturas y agiliza futuros pedidos corporativos.</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section id="facturacion" class="gsap-section">
          <h2>7. Facturación</h2>

          <div class="faq-accordion">
            <button class="faq-trigger" aria-expanded="false">
              ¿Emiten factura fiscal (CFDI)?
              <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="faq-content">
              <div class="faq-inner">
                <div class="faq-text">
                  <p>Sí, emitimos facturación válida en México. Puedes ingresar tus datos fiscales y uso de CFDI durante el proceso de compra, o solicitarla al correo de contacto dentro del mismo mes calendario de tu compra.</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <div class="help-banner gsap-section">
          <div class="help-banner-content">
            <h3>¿Más preguntas? visita nuestro Centro de Ayuda</h3>
            <a href="{{ url('/contacto') }}" class="btn-white">CENTRO DE AYUDA</a>
          </div>
        </div>

      </main>
    </div>
  </div>

  <button id="fab" class="fab" aria-label="Índice">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
  </button>

  <div id="mtoc-backdrop" class="mtoc-backdrop"></div>
  <nav id="mtoc" class="mtoc" aria-label="Índice móvil">
    <header>
      <h4>Navegación</h4>
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

  // 2. Lógica de los Acordeones (Custom)
  const accordions = document.querySelectorAll('.faq-accordion');
  
  accordions.forEach(acc => {
    const trigger = acc.querySelector('.faq-trigger');
    
    trigger.addEventListener('click', () => {
      const isOpen = acc.classList.contains('is-open');
      
      // Cerrar todos los demás
      accordions.forEach(otherAcc => {
        otherAcc.classList.remove('is-open');
        otherAcc.querySelector('.faq-trigger').setAttribute('aria-expanded', 'false');
      });

      // Alternar el actual
      if (!isOpen) {
        acc.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
      }
      
      // Recalcular Scrollspy
      setTimeout(computeTops, 350);
    });
  });

  // 3. Scrollspy 
  const root = document.getElementById('faq');
  if (!root) return;

  const OFFSET = 120;
  const links = Array.from(document.querySelectorAll('#faq .toc a'));
  const targets = links.map(a => document.querySelector(a.getAttribute('href'))).filter(Boolean);

  function smoothTo(id){
    const el = document.querySelector(id); 
    if(!el) return;
    const top = window.scrollY + el.getBoundingClientRect().top - OFFSET;
    window.scrollTo({top, behavior:'smooth'});
  }

  document.querySelectorAll('#faq .toc a, #toc-mobile a').forEach(a => {
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

  // 4. Móvil (Menú Flotante)
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