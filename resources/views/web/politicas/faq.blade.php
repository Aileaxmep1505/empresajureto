@extends('layouts.web')
@section('title','Preguntas Frecuentes')

@section('content')
<style>
/* ========= NAMESPACE AISLADO ========= */
#faq{
  --ink:#1b2550; --muted:#6b7280; --line:#e7ecf5; --bg1:#f7fbff; --bg2:#fff6ef;
  --chip:#f4f7ff; --chip-ink:#11316a; --brand:#6ea8fe; --ok:#16a34a; --warn:#eab308; --danger:#ef4444;
  --radius:18px; --shadow:0 12px 28px rgba(2,8,23,.06);
}
#faq{ position:relative; width:100%; color:var(--ink); }
#faq::before{
  content:""; position:fixed; inset:0; z-index:-1; pointer-events:none;
  background:
    radial-gradient(900px 520px at 100% -10%, var(--bg1) 0%, transparent 55%),
    radial-gradient(1200px 700px at -10% 0%, var(--bg2) 0%, transparent 55%),
    #ffffff;
}
#faq .wrap{ max-width:1200px; margin:0 auto; padding: clamp(20px,2.8vw,34px) 16px 80px; }

/* ===== Grid con sidebar ===== */
#faq .grid{
  display:grid; gap: clamp(18px,2vw,28px);
  grid-template-columns: 300px 1fr;
}
@media (max-width: 980px){
  #faq .grid{ grid-template-columns: 1fr; }
}

/* ===== Hero ===== */
#faq .hero{ margin-bottom: clamp(12px,2vw,18px); }
#faq .title{ font-size: clamp(26px,4.8vw,54px); line-height:1.05; margin:0; letter-spacing:-.02em; }
#faq .sub{ color:var(--muted); max-width:860px; margin:8px 0 0; }
#faq .chips{ display:flex; gap:10px; flex-wrap:wrap; margin-top:12px }
#faq .chip{ background:var(--chip); color:var(--chip-ink); border:1px solid var(--line); border-radius:999px; padding:8px 12px; font-weight:600; }

/* ===== Sidebar ===== */
#faq .sidebar{
  position: sticky; top: 90px; align-self:start;
  background:#fff; border:1px solid var(--line); border-radius: var(--radius);
  box-shadow: var(--shadow); padding:16px 14px;
}
#faq .sidebar h3{ font-size:16px; margin: 4px 10px 10px; color:#0b1530; letter-spacing:.2px; }
#faq .toc{ list-style:none; margin:0; padding:0; }
#faq .toc li{ margin:2px 0; }
#faq .toc a{
  display:flex; gap:10px; align-items:center;
  padding:10px 12px; border-radius:12px;
  color:#162447; text-decoration:none; font-weight:600;
  border:1px solid transparent;
}
#faq .toc a:hover{ background:#f9fbff; border-color:var(--line); }
#faq .toc a.active{ background:#eef4ff; border-color:#cfe0ff; color:#0b1530; }

/* ===== Contenido ===== */
#faq .content{
  background:#fff; border:1px solid var(--line); border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: clamp(16px,2.2vw,26px);
}
#faq section{ padding-top: clamp(14px,1.8vw,20px); }
#faq h2{ font-size: clamp(20px,3.2vw,32px); margin: 4px 0 8px; letter-spacing:-.01em; }
#faq h3{ font-size: clamp(16px,2.2vw,20px); margin: 14px 0 6px; color:#0f1a3a; }

/* ===== Acordeones accesibles (sin JS) ===== */
#faq details{
  border:1px solid var(--line); border-radius:14px; padding:12px 14px; margin:10px 0;
  background:#fff; transition: box-shadow .2s ease;
}
#faq details[open]{ box-shadow: 0 6px 16px rgba(2,8,23,.06); }
#faq summary{
  list-style:none; cursor:pointer; font-weight:700; color:#0b1530;
  display:flex; align-items:center; justify-content:space-between; gap:10px;
}
#faq summary::-webkit-details-marker{ display:none; }
#faq .qa{ color:#2b3357; line-height:1.65; padding-top:8px; }

/* ===== Móvil ===== */
@media (max-width: 980px){
  #faq .sidebar{ display:none; }
}

/* ===== FAB (móvil) ===== */
#faq .fab{
  position: fixed; right: 18px; bottom: 18px; z-index: 50;
  width: 56px; height:56px; border-radius: 999px;
  display: grid; place-items: center;
  border:1px solid var(--line); background:#fff; box-shadow: var(--shadow);
  font-weight:700; text-decoration:none; color:#0b1530;
  transform: translateY(20px); opacity:0; pointer-events:none; transition:.25s ease;
}
#faq .fab.show{ transform: translateY(0); opacity:1; pointer-events:auto; }
#faq .fab span{ font-size:22px; line-height:1; }

/* ===== Mini índice móvil ===== */
#faq .mtoc-backdrop{
  position: fixed; inset:0; background: rgba(10,18,40,.38);
  backdrop-filter: blur(2px); z-index: 49; opacity:0; pointer-events:none; transition:.2s;
}
#faq .mtoc-backdrop.open{ opacity:1; pointer-events:auto; }
#faq .mtoc{
  position: fixed; left: 12px; right:12px; bottom:12px; z-index: 50;
  background:#fff; border:1px solid var(--line); border-radius: 16px;
  box-shadow: 0 20px 60px rgba(2,8,23,.20);
  transform: translateY(18px); opacity:0; pointer-events:none; transition:.22s ease;
  max-height: 60vh; overflow:auto;
}
#faq .mtoc.open{ transform: translateY(0); opacity:1; pointer-events:auto; }
#faq .mtoc header{ display:flex; align-items:center; justify-content:space-between; padding:12px 14px; border-bottom:1px solid var(--line); }
#faq .mtoc header h4{ margin:0; font-size:15px; }
#faq .mtoc header button{ background:#f6f8fc; border:1px solid var(--line); border-radius:10px; padding:6px 10px; cursor:pointer; }
#faq .mtoc ul{ list-style:none; margin:0; padding:8px; }
#faq .mtoc li a{ display:block; padding:10px 12px; border-radius:10px; color:#0b1530; text-decoration:none; border:1px solid transparent; }
#faq .mtoc li a:hover{ background:#f9fbff; border-color:var(--line); }

/* Scroll suave */
html{ scroll-behavior: smooth; }
</style>

<div id="faq">
  <div class="wrap">

    <header class="hero">
      <h1 class="title">Preguntas Frecuentes</h1>
      <p class="sub">Respuestas rápidas sobre compras, pagos, envíos, devoluciones, garantías y facturación en <strong>Jureto</strong>.</p>
      <div class="chips">
        <span class="chip">💳 Pagos</span>
        <span class="chip">🚚 Envíos</span>
        <span class="chip">↩️ Devoluciones</span>
        <span class="chip">🛠️ Garantías</span>
      </div>
    </header>

    <div class="grid">
      <aside class="sidebar">
        <h3>Índice</h3>
        <ul class="toc" id="toc-desktop">
          <li><a class="lvl1" href="#pagos">1. Pagos</a></li>
          <li><a class="lvl1" href="#mayoreo">2. Mayoreo</a></li>
          <li><a class="lvl1" href="#envios">3. Envíos</a></li>
          <li><a class="lvl1" href="#devoluciones">4. Devoluciones</a></li>
          <li><a class="lvl1" href="#garantias">5. Garantías</a></li>
          <li><a class="lvl1" href="#cuenta">6. Cuenta y pedidos</a></li>
          <li><a class="lvl1" href="#facturacion">7. Facturación</a></li>
          <li><a class="lvl1" href="#contacto">8. Contacto</a></li>
        </ul>
      </aside>

      <main class="content">
        <!-- PAGOS -->
        <section id="pagos" class="anchor">
          <h2>1) Pagos</h2>

          <details>
            <summary>¿Qué métodos de pago aceptan?</summary>
            <div class="qa">
              <p>En compras en línea aceptamos pagos con tarjetas vía <strong>Stripe</strong> (Visa, Mastercard, American Express). Para mayoreo consulta la sección correspondiente.</p>
            </div>
          </details>

          <details>
            <summary>¿Es seguro pagar con Stripe?</summary>
            <div class="qa">
              <p>Sí. Stripe procesa las transacciones con cifrado SSL/TLS y cumple con el estándar <strong>PCI DSS</strong>. Jureto no almacena datos sensibles de tarjetas.</p>
            </div>
          </details>

          <details>
            <summary>Mi pago falló, ¿qué puedo hacer?</summary>
            <div class="qa">
              <ul>
                <li>Verifica fondos y que tu banco permita compras en línea.</li>
                <li>Intenta nuevamente o usa otra tarjeta.</li>
                <li>Si persiste, escríbenos a <a href="mailto:rtort@jureto.com.mx">rtort@jureto.com.mx</a>.</li>
              </ul>
            </div>
          </details>
        </section>

        <!-- MAYOREO -->
        <section id="mayoreo" class="anchor">
          <h2>2) Mayoreo</h2>

          <details>
            <summary>¿Cómo pago pedidos de mayoreo?</summary>
            <div class="qa">
              <p>Mediante <strong>transferencia bancaria</strong> o <strong>efectivo contra entrega</strong> en entregas locales verificadas. Es obligatorio un <strong>anticipo mínimo</strong> para apartar inventario.</p>
            </div>
          </details>

          <details>
            <summary>¿De cuánto es el anticipo?</summary>
            <div class="qa">
              <p>El estándar es <strong>30&nbsp;%</strong>, aunque puede variar según el tipo de producto y disponibilidad. Te lo confirmaremos por correo institucional.</p>
            </div>
          </details>
        </section>

        <!-- ENVIOS -->
        <section id="envios" class="anchor">
          <h2>3) Envíos</h2>

          <details>
            <summary>¿Cuánto tarda mi pedido?</summary>
            <div class="qa">
              <p>Preparación 24–48 h hábiles; tránsito 1–5 días hábiles (zonas metro) y 3–8 días (zonas extendidas). Te compartimos guía de rastreo al despachar.</p>
            </div>
          </details>

          <details>
            <summary>¿Tienen envío gratis?</summary>
            <div class="qa">
              <p>Sí, en compras que cumplan el mínimo y condiciones vigentes. Los detalles se muestran en el carrito antes de pagar.</p>
            </div>
          </details>
        </section>

        <!-- DEVOLUCIONES -->
        <section id="devoluciones" class="anchor">
          <h2>4) Devoluciones</h2>

          <details>
            <summary>¿Puedo devolver un producto?</summary>
            <div class="qa">
              <p>Sí, dentro de <strong>30 días naturales</strong> desde la entrega, en su empaque original, sin uso y con accesorios. Solicita autorización previa por correo.</p>
            </div>
          </details>

          <details>
            <summary>¿Cómo tramito un reembolso?</summary>
            <div class="qa">
              <p>Escríbenos a <a href="mailto:rtort@jureto.com.mx">rtort@jureto.com.mx</a> con número de pedido, fotos y motivo. Procesamos al medio original o nota de crédito según proceda.</p>
            </div>
          </details>
        </section>

        <!-- GARANTIAS -->
        <section id="garantias" class="anchor">
          <h2>5) Garantías</h2>

          <details>
            <summary>Mi producto llegó dañado o defectuoso</summary>
            <div class="qa">
              <p>Reporta dentro de 24–48 h con fotos del empaque y del artículo. Gestionamos reclamación con paquetería o garantía con proveedor.</p>
            </div>
          </details>

        <details>
            <summary>¿Cómo funciona la garantía?</summary>
            <div class="qa">
              <p>Según dictamen del proveedor: reparación, reemplazo o nota de crédito. Aplica revisión de número de serie y condiciones de uso.</p>
            </div>
          </details>
        </section>

        <!-- CUENTA -->
        <section id="cuenta" class="anchor">
          <h2>6) Cuenta y pedidos</h2>

          <details>
            <summary>¿Necesito cuenta para comprar?</summary>
            <div class="qa">
              <p>No es obligatorio, pero tener cuenta te permite ver historial, facturas y agiliza futuras compras.</p>
            </div>
          </details>

          <details>
            <summary>¿Cómo veo el estado de mi pedido?</summary>
            <div class="qa">
              <p>Revisa el correo de confirmación con tu número de guía o ingresa a tu panel si tienes cuenta.</p>
            </div>
          </details>
        </section>

        <!-- FACTURACION -->
        <section id="facturacion" class="anchor">
          <h2>7) Facturación</h2>

          <details>
            <summary>¿Emiten factura?</summary>
            <div class="qa">
              <p>Sí. Comparte tus datos fiscales al finalizar la compra o vía correo dentro del periodo fiscal aplicable.</p>
            </div>
          </details>

          <details>
            <summary>Cometí un error en mis datos fiscales</summary>
            <div class="qa">
              <p>Escríbenos lo antes posible a <a href="mailto:rtort@jureto.com.mx">rtort@jureto.com.mx</a>. Algunas correcciones pueden requerir cancelación y reexpedición según normativa.</p>
            </div>
          </details>
        </section>

        <!-- CONTACTO -->
        <section id="contacto" class="anchor">
          <h2>8) Contacto</h2>
          <ul>
            <li><strong>Email:</strong> <a href="mailto:rtort@jureto.com.mx" style="color:var(--brand);text-decoration:none;font-weight:600;">rtort@jureto.com.mx</a></li>
            <li><strong>Teléfono:</strong> <a href="tel:+525541937243" style="color:var(--brand);text-decoration:none;font-weight:600;">+52 55 4193 7243</a></li>
            <li><strong>Ubicación:</strong> 7CP5+34M San Jerónimo Chicahualco, Estado de México &amp; UAE</li>
          </ul>
          <div class="hr"></div>
          <ul>
            <li><a href="{{ route('policy.terms') }}" style="color:var(--brand);text-decoration:none;font-weight:600;">Términos y Condiciones</a></li>
            <li><a href="{{ route('policy.privacy') }}" style="color:var(--brand);text-decoration:none;font-weight:600;">Aviso de Privacidad</a></li>
            <li><a href="{{ route('policy.shipping') }}" style="color:var(--brand);text-decoration:none;font-weight:600;">Envíos y Devoluciones</a></li>
            <li><a href="{{ route('policy.payments') }}" style="color:var(--brand);text-decoration:none;font-weight:600;">Formas de Pago</a></li>
          </ul>
        </section>
      </main>
    </div>
  </div>

  <!-- FAB -->
  <button id="fab" class="fab" aria-label="Índice">
    <span>☰</span>
  </button>

  <!-- Mini índice móvil -->
  <div id="mtoc-backdrop" class="mtoc-backdrop"></div>
  <nav id="mtoc" class="mtoc" aria-label="Índice móvil">
    <header>
      <h4>Navegación</h4>
      <button type="button" id="mtoc-close">Cerrar</button>
    </header>
    <ul id="toc-mobile"></ul>
  </nav>
</div>

<script>
// ===== Scrollspy robusto + scroll con offset en clic =====
(function(){
  const root = document.getElementById('faq');
  if (!root) return;

  const OFFSET = 120; // ajusta si tu navbar fija mide más/menos
  const links = Array.from(document.querySelectorAll('#faq .toc a'));
  const targets = links.map(a => document.querySelector(a.getAttribute('href'))).filter(Boolean);

  // Navegación con offset en clic (desktop y mini-índice)
  function smoothScrollTo(id){
    const el = document.querySelector(id);
    if (!el) return;
    const top = window.scrollY + el.getBoundingClientRect().top - OFFSET;
    window.scrollTo({ top, behavior:'smooth' });
  }
  function onLinkClick(e){
    const href = e.currentTarget.getAttribute('href');
    if (!href || !href.startsWith('#')) return;
    e.preventDefault();
    smoothScrollTo(href);
  }
  document.querySelectorAll('#faq .toc a, #toc-mobile a').forEach(a => {
    a.addEventListener('click', onLinkClick);
  });

  // Scrollspy por posición (evita falsos positivos)
  let tops = [];
  function computeTops(){
    tops = targets.map(el => {
      const rect = el.getBoundingClientRect();
      return { id:'#'+el.id, top: window.scrollY + rect.top - OFFSET - 1 };
    }).sort((a,b)=> a.top - b.top);
  }
  function setActive(){
    const y = window.scrollY;
    let current = tops[0]?.id || null;
    for (let i=0;i<tops.length;i++){
      if (y >= tops[i].top) current = tops[i].id; else break;
    }
    links.forEach(a => a.classList.toggle('active', a.getAttribute('href') === current));
    // también sincroniza el mini-índice si está abierto
    document.querySelectorAll('#toc-mobile a').forEach(a => a.classList.toggle('active', a.getAttribute('href') === current));
  }

  // Recalcular en eventos comunes
  window.addEventListener('scroll', setActive, {passive:true});
  window.addEventListener('resize', ()=>{ computeTops(); setActive(); });
  document.addEventListener('DOMContentLoaded', ()=>{ computeTops(); setActive(); });
  window.addEventListener('load', ()=>{ computeTops(); setActive(); });
  setTimeout(()=>{ computeTops(); setActive(); }, 250);

  // Recalcular cuando se abren/cerran <details>
  document.querySelectorAll('#faq details').forEach(d=>{
    d.addEventListener('toggle', ()=>{ computeTops(); setActive(); });
  });

  // Observa cambios de tamaño en el contenedor principal (fuentes, imágenes, etc.)
  new ResizeObserver(()=>{ computeTops(); setActive(); }).observe(root);
})();

// ===== FAB y mini índice móvil =====
(function(){
  const fab = document.getElementById('fab');
  const mtoc = document.getElementById('mtoc');
  const backdrop = document.getElementById('mtoc-backdrop');
  const closeBtn = document.getElementById('mtoc-close');
  const tocDesktop = document.getElementById('toc-desktop');
  const tocMobile = document.querySelector('#mtoc #toc-mobile');

  // Clona entradas al mini índice
  if (tocDesktop && tocMobile) {
    tocMobile.innerHTML = tocDesktop.innerHTML;
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
    const nearBottom = (y + h) >= (docH - 600);
    fab.classList.toggle('show', nearBottom);
  }

  window.addEventListener('scroll', onScroll, {passive:true});
  window.addEventListener('resize', onScroll);
  document.addEventListener('DOMContentLoaded', onScroll);

  fab.addEventListener('click', ()=> toggleMTOC(true));
  backdrop.addEventListener('click', ()=> toggleMTOC(false));
  closeBtn.addEventListener('click', ()=> toggleMTOC(false));
})();
</script>
@endsection
