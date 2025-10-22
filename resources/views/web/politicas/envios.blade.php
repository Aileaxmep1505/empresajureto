@extends('layouts.web')
@section('title','Envíos, Devoluciones y Cancelaciones')

@section('content')
<style>
/* ========= NAMESPACE AISLADO ========= */
#ship{
  --ink:#1b2550; --muted:#6b7280; --line:#e7ecf5; --bg1:#f7fbff; --bg2:#fff6ef;
  --chip:#f4f7ff; --chip-ink:#11316a; --brand:#6ea8fe; --ok:#16a34a; --warn:#eab308; --danger:#ef4444;
  --radius:18px; --shadow:0 12px 28px rgba(2,8,23,.06);
}
#ship{ position:relative; width:100%; color:var(--ink); }
#ship::before{
  content:""; position:fixed; inset:0; z-index:-1; pointer-events:none;
  background:
    radial-gradient(900px 520px at 100% -10%, var(--bg1) 0%, transparent 55%),
    radial-gradient(1200px 700px at -10% 0%, var(--bg2) 0%, transparent 55%),
    #ffffff;
}
#ship .wrap{ max-width:1200px; margin:0 auto; padding: clamp(20px,2.8vw,34px) 16px 80px; }

/* ===== Layout con sidebar ===== */
#ship .grid{
  display:grid; gap: clamp(18px,2vw,28px);
  grid-template-columns: 300px 1fr;
}
@media (max-width: 980px){
  #ship .grid{ grid-template-columns: 1fr; }
}

/* ===== Hero ===== */
#ship .hero{ margin-bottom: clamp(12px,2vw,18px); }
#ship .title{ font-size: clamp(26px,4.8vw,54px); line-height:1.05; margin:0; letter-spacing:-.02em; }
#ship .sub{ color:var(--muted); max-width:860px; margin:8px 0 0; }
#ship .chips{ display:flex; gap:10px; flex-wrap:wrap; margin-top:12px }
#ship .chip{ background:var(--chip); color:var(--chip-ink); border:1px solid var(--line); border-radius:999px; padding:8px 12px; font-weight:600; }

/* ===== Sidebar (Índice) ===== */
#ship .sidebar{
  position: sticky; top: 90px; align-self:start;
  background:#fff; border:1px solid var(--line); border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 16px 14px;
}
#ship .sidebar h3{ font-size:16px; margin: 4px 10px 10px; color:#0b1530; letter-spacing:.2px; }
#ship .toc{ list-style:none; margin:0; padding:0; }
#ship .toc li{ margin:2px 0; }
#ship .toc a{
  display:flex; gap:10px; align-items:center;
  padding:10px 12px; border-radius:12px;
  color:#162447; text-decoration:none; font-weight:600;
  border:1px solid transparent;
}
#ship .toc a:hover{ background:#f9fbff; border-color:var(--line); }
#ship .toc a.active{ background:#eef4ff; border-color:#cfe0ff; color:#0b1530; }

/* Niveles del índice */
#ship .toc .lvl1{ font-size:14px; }
#ship .toc .lvl2{ font-size:13px; padding-left:28px; opacity:.95; }

/* ===== Contenido ===== */
#ship .content{
  background:#fff; border:1px solid var(--line); border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: clamp(16px,2.2vw,26px);
}
#ship section{ padding-top: clamp(14px,1.8vw,20px); }
#ship h2{ font-size: clamp(20px,3.2vw,32px); margin: 4px 0 8px; letter-spacing:-.01em; }
#ship h3{ font-size: clamp(16px,2.2vw,20px); margin: 14px 0 6px; color:#0f1a3a; }
#ship p{ color:#2b3357; margin-bottom:10px; line-height:1.65; }
#ship ul{ padding-left: 22px; margin: 8px 0; }
#ship li{ margin:6px 0 }
#ship .note{ background:#fff; border:1px dashed var(--line); border-radius:12px; padding:12px 14px; color:#374151; }
#ship .hr{ height:1px; background:linear-gradient(90deg, #fff, var(--line), #fff); margin: 18px 0; }

/* Anchor scroll safe */
#ship .anchor{ position:relative; scroll-margin-top:110px; }

/* ===== Móvil: ocultar sidebar ===== */
@media (max-width: 980px){
  #ship .sidebar{ display:none; }
}

/* ===== Botón flotante (móvil) ===== */
#ship .fab{
  position: fixed; right: 18px; bottom: 18px; z-index: 50;
  width: 56px; height:56px; border-radius: 999px;
  display: grid; place-items: center;
  border:1px solid var(--line); background:#fff; box-shadow: var(--shadow);
  font-weight:700; text-decoration:none; color:#0b1530;
  transform: translateY(20px); opacity:0; pointer-events:none;
  transition: .25s ease;
}
#ship .fab.show{ transform: translateY(0); opacity:1; pointer-events:auto; }
#ship .fab span{ font-size:22px; line-height:1; }

/* ===== Mini índice flotante (overlay móvil) ===== */
#ship .mtoc-backdrop{
  position: fixed; inset:0; background: rgba(10,18,40,.38);
  backdrop-filter: blur(2px); z-index: 49; opacity:0; pointer-events:none; transition:.2s;
}
#ship .mtoc-backdrop.open{ opacity:1; pointer-events:auto; }

#ship .mtoc{
  position: fixed; left: 12px; right:12px; bottom:12px; z-index: 50;
  background:#fff; border:1px solid var(--line); border-radius: 16px;
  box-shadow: 0 20px 60px rgba(2,8,23,.20);
  transform: translateY(18px); opacity:0; pointer-events:none; transition:.22s ease;
  max-height: 60vh; overflow:auto;
}
#ship .mtoc.open{ transform: translateY(0); opacity:1; pointer-events:auto; }
#ship .mtoc header{ display:flex; align-items:center; justify-content:space-between; padding:12px 14px; border-bottom:1px solid var(--line); }
#ship .mtoc header h4{ margin:0; font-size:15px; }
#ship .mtoc header button{ background:#f6f8fc; border:1px solid var(--line); border-radius:10px; padding:6px 10px; cursor:pointer; }
#ship .mtoc ul{ list-style:none; margin:0; padding:8px; }
#ship .mtoc li a{ display:block; padding:10px 12px; border-radius:10px; color:#0b1530; text-decoration:none; border:1px solid transparent; }
#ship .mtoc li a:hover{ background:#f9fbff; border-color:var(--line); }

/* Scroll suave */
html{ scroll-behavior: smooth; }
</style>

<div id="ship">
  <div class="wrap">

    <!-- HERO -->
    <header class="hero">
      <h1 class="title">Envíos, Devoluciones y Cancelaciones</h1>
      <p class="sub">Políticas aplicables a pedidos realizados con <strong>Jureto</strong>, empresa comercializadora de productos de papelería.</p>
      <div class="chips">
        <span class="chip">📅 Última actualización: 21 de octubre de 2025</span>
        <span class="chip">🚚 Logística y postventa</span>
      </div>
    </header>

    <div class="grid">
      <!-- ===== SIDEBAR (Índice) — se oculta en móvil ===== -->
      <aside class="sidebar">
        <h3>Índice</h3>
        <ul class="toc" id="toc-desktop">
          <li><a class="lvl1" href="#envios">1. Envíos</a></li>
          <li><a class="lvl2" href="#plazos">• Plazos de preparación y tránsito</a></li>
          <li><a class="lvl2" href="#costos-zonas">• Costos y zonas de cobertura</a></li>
          <li><a class="lvl2" href="#rastreo">• Rastreo y entrega</a></li>
          <li><a class="lvl2" href="#riesgo">• Transferencia de riesgo e inspección</a></li>

          <li><a class="lvl1" href="#devoluciones">2. Devoluciones y Reembolsos</a></li>
          <li><a class="lvl2" href="#condiciones">• Condiciones para aceptar devoluciones</a></li>
          <li><a class="lvl2" href="#pasos">• Pasos para solicitar devolución</a></li>
          <li><a class="lvl2" href="#reembolsos">• Formas y tiempos de reembolso</a></li>
          <li><a class="lvl2" href="#no-retornables">• Productos no retornables</a></li>

          <li><a class="lvl1" href="#cancelaciones">3. Cancelaciones</a></li>
          <li><a class="lvl2" href="#antes-envio">• Antes del envío</a></li>
          <li><a class="lvl2" href="#despues-envio">• Después del envío</a></li>

          <li><a class="lvl1" href="#excepciones">4. Excepciones y casos especiales</a></li>
          <li><a class="lvl1" href="#contacto">5. Contacto</a></li>
        </ul>
      </aside>

      <!-- ===== CONTENIDO ===== -->
      <main class="content">
        <section id="envios" class="anchor">
          <h2>1) Envíos</h2>
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

        <section id="devoluciones" class="anchor">
          <h2>2) Devoluciones y Reembolsos</h2>
          <h3 id="condiciones">Condiciones para aceptar devoluciones</h3>
          <ul>
            <li>Plazo general: <strong>30 días naturales</strong> desde la entrega.</li>
            <li>El artículo debe estar <strong>sin uso</strong>, en su <strong>empaque original</strong>, con accesorios, manuales y obsequios.</li>
            <li>Debes <strong>notificar</strong> y obtener autorización previa; sin autorización no se reciben paquetes.</li>
          </ul>

          <h3 id="pasos">Pasos para solicitar devolución</h3>
          <ol>
            <li>Escríbenos a <a href="mailto:rtort@jureto.com.mx" style="color:var(--brand);text-decoration:none;font-weight:600;">rtort@jureto.com.mx</a> con número de pedido, fotos y motivo.</li>
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

          <p class="note">Para defectos de fábrica fuera del periodo de devolución, aplica la <a href="{{ url('/garantias-y-devoluciones') }}" style="color:var(--brand);text-decoration:none;font-weight:600;">política de garantías</a> cuando corresponda.</p>
        </section>

        <section id="cancelaciones" class="anchor">
          <h2>3) Cancelaciones</h2>
          <h3 id="antes-envio">Antes del envío</h3>
          <p>Podrás solicitar cancelación <strong>antes</strong> de que el pedido sea despachado. Si el cobro ya se procesó, se hará reembolso al mismo medio de pago o nota de crédito.</p>

          <h3 id="despues-envio">Después del envío</h3>
          <p>Si el pedido ya fue despachado, la solicitud se tramita como <strong>devolución</strong> (ver sección 2). Los costos de retorno pueden aplicar salvo error atribuible a Jureto.</p>
        </section>

        <section id="excepciones" class="anchor">
          <h2>4) Excepciones y casos especiales</h2>
          <ul>
            <li><strong>Dirección incorrecta:</strong> reexpedición con costo adicional cuando aplique.</li>
            <li><strong>Paquete perdido:</strong> gestionamos aclaración con la paquetería; se repone o reembolsa según dictamen.</li>
            <li><strong>Daño en tránsito:</strong> reporta dentro de 24–48 h con evidencia fotográfica para proceder con la reclamación.</li>
          </ul>
        </section>

        <section id="contacto" class="anchor">
          <h2>5) Contacto</h2>
          <ul>
            <li><strong>Email:</strong> <a href="mailto:rtort@jureto.com.mx" style="color:var(--brand);text-decoration:none;font-weight:600;">rtort@jureto.com.mx</a></li>
            <li><strong>Teléfono:</strong> <a href="tel:+525541937243" style="color:var(--brand);text-decoration:none;font-weight:600;">+52 55 4193 7243</a></li>
            <li><strong>Ubicación:</strong> 7CP5+34M San Jerónimo Chicahualco, Estado de México &amp; UAE</li>
          </ul>
          <div class="hr"></div>
          <h3>Documentos relacionados</h3>
          <ul>
            <li><a href="{{ route('policy.terms') }}" style="color:var(--brand);text-decoration:none;font-weight:600;">Términos y Condiciones</a></li>
            <li><a href="{{ route('policy.privacy') }}" style="color:var(--brand);text-decoration:none;font-weight:600;">Aviso de Privacidad</a></li>
            @if(Route::has('policy.warranty'))
              <li><a href="{{ route('policy.warranty') }}" style="color:var(--brand);text-decoration:none;font-weight:600;">Garantías</a></li>
            @endif
          </ul>
        </section>
      </main>
    </div>
  </div>

  <!-- Botón flotante (solo móvil) -->
  <button id="fab" class="fab" aria-label="Índice">
    <span>☰</span>
  </button>

  <!-- Overlay mini índice (móvil) -->
  <div id="mtoc-backdrop" class="mtoc-backdrop"></div>
  <nav id="mtoc" class="mtoc" aria-label="Índice móvil">
    <header>
      <h4>Navegación</h4>
      <button type="button" id="mtoc-close">Cerrar</button>
    </header>
    <ul id="toc-mobile"><!-- se clona desde el desktop --></ul>
  </nav>
</div>

<script>
// ===== Scrollspy simple con IntersectionObserver =====
(function(){
  const links = Array.from(document.querySelectorAll('#ship .toc a'));
  const sections = links.map(a => document.querySelector(a.getAttribute('href'))).filter(Boolean);

  const obs = new IntersectionObserver((entries)=>{
    entries.forEach(entry=>{
      if(entry.isIntersecting){
        const id = '#' + entry.target.id;
        links.forEach(l => l.classList.toggle('active', l.getAttribute('href') === id));
      }
    });
  }, { rootMargin: '-40% 0px -55% 0px', threshold: [0, 0.3, 0.6, 1] });

  sections.forEach(sec => obs.observe(sec));
})();

// ===== Botón flotante que aparece "hasta abajo" (solo móvil) =====
(function(){
  const fab = document.getElementById('fab');
  const mtoc = document.getElementById('mtoc');
  const backdrop = document.getElementById('mtoc-backdrop');
  const closeBtn = document.getElementById('mtoc-close');
  const tocDesktop = document.getElementById('toc-desktop');
  const tocMobile = document.getElementById('toc-mobile');

  // Clona las entradas del índice al mini índice móvil
  if (tocDesktop && tocMobile) {
    tocMobile.innerHTML = tocDesktop.innerHTML;
    tocMobile.querySelectorAll('a').forEach(a=>{
      a.addEventListener('click', ()=> toggleMTOC(false));
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

  // Mostrar FAB solo cuando el usuario está casi hasta abajo
  function onScroll(){
    if (!isMobile()) { fab.classList.remove('show'); toggleMTOC(false); return; }
    const scrollY = window.scrollY || window.pageYOffset;
    const viewport = window.innerHeight;
    const docH = Math.max(document.body.scrollHeight, document.documentElement.scrollHeight);
    const nearBottom = (scrollY + viewport) >= (docH - 600); // umbral
    fab.classList.toggle('show', nearBottom);
  }

  window.addEventListener('scroll', onScroll, {passive:true});
  window.addEventListener('resize', onScroll);
  document.addEventListener('DOMContentLoaded', onScroll);

  // Interacciones mini índice
  fab.addEventListener('click', ()=> toggleMTOC(true));
  backdrop.addEventListener('click', ()=> toggleMTOC(false));
  closeBtn.addEventListener('click', ()=> toggleMTOC(false));
})();
</script>
@endsection
