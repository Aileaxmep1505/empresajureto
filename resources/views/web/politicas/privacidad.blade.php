@extends('layouts.web')
@section('title','Aviso de Privacidad')

@section('content')
<style>
/* ========= NAMESPACE AISLADO ========= */
#privacy{
  --ink:#1b2550; --muted:#6b7280; --line:#e7ecf5; --bg1:#f7fbff; --bg2:#fff6ef;
  --chip:#f4f7ff; --chip-ink:#11316a; --brand:#6ea8fe; --ok:#16a34a; --warn:#eab308; --danger:#ef4444;
  --radius:18px; --shadow:0 12px 28px rgba(2,8,23,.06);
}
#privacy{ position:relative; width:100%; color:var(--ink); }
#privacy::before{
  content:""; position:fixed; inset:0; z-index:-1; pointer-events:none;
  background:
    radial-gradient(900px 520px at 100% -10%, var(--bg1) 0%, transparent 55%),
    radial-gradient(1200px 700px at -10% 0%, var(--bg2) 0%, transparent 55%),
    #ffffff;
}
#privacy .wrap{ max-width:1200px; margin:0 auto; padding: clamp(20px,2.8vw,34px) 16px 80px; }

/* ===== Layout con sidebar ===== */
#privacy .grid{
  display:grid; gap: clamp(18px,2vw,28px);
  grid-template-columns: 300px 1fr;
}
@media (max-width: 980px){
  #privacy .grid{ grid-template-columns: 1fr; }
}

/* ===== Hero ===== */
#privacy .hero{ margin-bottom: clamp(12px,2vw,18px); }
#privacy .title{ font-size: clamp(26px,4.8vw,54px); line-height:1.05; margin:0; letter-spacing:-.02em; }
#privacy .sub{ color:var(--muted); max-width:860px; margin:8px 0 0; }
#privacy .chips{ display:flex; gap:10px; flex-wrap:wrap; margin-top:12px }
#privacy .chip{ background:var(--chip); color:var(--chip-ink); border:1px solid var(--line); border-radius:999px; padding:8px 12px; font-weight:600; }

/* ===== Sidebar (Índice) ===== */
#privacy .sidebar{
  position: sticky; top: 90px; align-self:start;
  background:#fff; border:1px solid var(--line); border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 16px 14px;
}
#privacy .sidebar h3{ font-size:16px; margin: 4px 10px 10px; color:#0b1530; letter-spacing:.2px; }
#privacy .toc{ list-style:none; margin:0; padding:0; }
#privacy .toc li{ margin:2px 0; }
#privacy .toc a{
  display:flex; gap:10px; align-items:center;
  padding:10px 12px; border-radius:12px;
  color:#162447; text-decoration:none; font-weight:600;
  border:1px solid transparent;
}
#privacy .toc a:hover{ background:#f9fbff; border-color:var(--line); }
#privacy .toc a.active{ background:#eef4ff; border-color:#cfe0ff; color:#0b1530; }

/* Niveles del índice */
#privacy .toc .lvl1{ font-size:14px; }
#privacy .toc .lvl2{ font-size:13px; padding-left:28px; opacity:.95; }

/* ===== Contenido ===== */
#privacy .content{
  background:#fff; border:1px solid var(--line); border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: clamp(16px,2.2vw,26px);
}
#privacy section{ padding-top: clamp(14px,1.8vw,20px); }
#privacy h2{ font-size: clamp(20px,3.2vw,32px); margin: 4px 0 8px; letter-spacing:-.01em; }
#privacy h3{ font-size: clamp(16px,2.2vw,20px); margin: 14px 0 6px; color:#0f1a3a; }
#privacy p{ color:#2b3357; margin-bottom:10px; line-height:1.65; }
#privacy ul{ padding-left: 22px; margin: 8px 0; }
#privacy li{ margin:6px 0 }
#privacy .note{ background:#fff; border:1px dashed var(--line); border-radius:12px; padding:12px 14px; color:#374151; }
#privacy .hr{ height:1px; background:linear-gradient(90deg, #fff, var(--line), #fff); margin: 18px 0; }

/* Anchor scroll safe */
#privacy .anchor{ position:relative; scroll-margin-top:110px; }

/* ===== Móvil: ocultar sidebar ===== */
@media (max-width: 980px){
  #privacy .sidebar{ display:none; }
}

/* ===== Botón flotante (móvil) ===== */
#privacy .fab{
  position: fixed; right: 18px; bottom: 18px; z-index: 50;
  width: 56px; height:56px; border-radius: 999px;
  display: grid; place-items: center;
  border:1px solid var(--line); background:#fff; box-shadow: var(--shadow);
  font-weight:700; text-decoration:none; color:#0b1530;
  transform: translateY(20px); opacity:0; pointer-events:none;
  transition: .25s ease;
}
#privacy .fab.show{ transform: translateY(0); opacity:1; pointer-events:auto; }
#privacy .fab span{ font-size:22px; line-height:1; }

/* ===== Mini índice flotante (overlay móvil) ===== */
#privacy .mtoc-backdrop{
  position: fixed; inset:0; background: rgba(10,18,40,.38);
  backdrop-filter: blur(2px); z-index: 49; opacity:0; pointer-events:none; transition:.2s;
}
#privacy .mtoc-backdrop.open{ opacity:1; pointer-events:auto; }

#privacy .mtoc{
  position: fixed; left: 12px; right:12px; bottom:12px; z-index: 50;
  background:#fff; border:1px solid var(--line); border-radius: 16px;
  box-shadow: 0 20px 60px rgba(2,8,23,.20);
  transform: translateY(18px); opacity:0; pointer-events:none; transition:.22s ease;
  max-height: 60vh; overflow:auto;
}
#privacy .mtoc.open{ transform: translateY(0); opacity:1; pointer-events:auto; }
#privacy .mtoc header{ display:flex; align-items:center; justify-content:space-between; padding:12px 14px; border-bottom:1px solid var(--line); }
#privacy .mtoc header h4{ margin:0; font-size:15px; }
#privacy .mtoc header button{ background:#f6f8fc; border:1px solid var(--line); border-radius:10px; padding:6px 10px; cursor:pointer; }
#privacy .mtoc ul{ list-style:none; margin:0; padding:8px; }
#privacy .mtoc li a{ display:block; padding:10px 12px; border-radius:10px; color:#0b1530; text-decoration:none; border:1px solid transparent; }
#privacy .mtoc li a:hover{ background:#f9fbff; border-color:var(--line); }

/* Scroll suave */
html{ scroll-behavior: smooth; }
</style>

<div id="privacy">
  <div class="wrap">

    <!-- HERO -->
    <header class="hero">
      <h1 class="title">Aviso de Privacidad</h1>
      <p class="sub">Este aviso describe cómo <strong>Jureto</strong>, empresa comercializadora de productos de papelería, recaba, usa, protege y, en su caso, transfiere tus datos personales.</p>
      <div class="chips">
        <span class="chip">📅 Última actualización: 21 de octubre de 2025</span>
        <span class="chip">🔒 Protección de datos</span>
      </div>
    </header>

    <div class="grid">
      <!-- ===== SIDEBAR (ÍNDICE) — se oculta en móvil ===== -->
      <aside class="sidebar">
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

      <!-- ===== CONTENIDO ===== -->
      <main class="content">
        <section id="responsable" class="anchor">
          <h2>1) Responsable y contacto</h2>
          <p><strong>Jureto</strong> es responsable del tratamiento de tus datos personales. Puedes contactarnos para cualquier tema relacionado con privacidad a través de los medios indicados en la sección <a href="#contacto">Contacto</a>.</p>
        </section>

        <section id="datos" class="anchor">
          <h2>2) Datos personales que recabamos</h2>
          <ul>
            <li>Identificación y contacto: nombre, correo, teléfono, dirección de envío/facturación.</li>
            <li>Transaccionales: productos comprados, montos, métodos de pago (referencias no sensibles).</li>
            <li>Soporte y posventa: números de serie, evidencias de garantía y comunicaciones.</li>
            <li>Navegación: IP, dispositivo, páginas visitadas (ver <a href="#cookies">Cookies</a>).</li>
          </ul>
          <p><em>No solicitamos datos sensibles</em>. Si excepcionalmente llegaran a ser necesarios, lo comunicaremos y pediremos consentimiento expreso.</p>
        </section>

        <section id="finalidades" class="anchor">
          <h2>3) Finalidades del tratamiento</h2>
          <ul>
            <li><strong>Primarias:</strong> gestionar compras, pagos, envíos, garantías, devoluciones y facturación; soporte al cliente; cumplimiento legal.</li>
            <li><strong>Secundarias (opcionales):</strong> encuestas de satisfacción, comunicaciones comerciales y promociones. Puedes oponerte en cualquier momento.</li>
          </ul>
        </section>

        <section id="bases" class="anchor">
          <h2>4) Bases de licitud y consentimiento</h2>
          <p>Tratamos tus datos con base en: (i) ejecución de una relación contractual; (ii) cumplimiento de obligaciones legales; (iii) interés legítimo para mejorar seguridad, prevención de fraude y experiencia; y (iv) tu consentimiento cuando sea requerido.</p>
        </section>

        <section id="fuentes" class="anchor">
          <h2>5) Fuentes de obtención</h2>
          <p>Obtenemos datos directamente de ti (formularios y compras), de manera automática por el uso del sitio y, en su caso, de terceros proveedores de pago/paquetería estrictamente para completar tus pedidos.</p>
        </section>

        <section id="cookies" class="anchor">
          <h2>6) Cookies y tecnologías similares</h2>
          <p>Utilizamos cookies para recordar tu sesión, analizar el tráfico y mejorar el contenido. Puedes administrar tus preferencias desde la configuración de tu navegador o, cuando esté disponible, en nuestro banner/centro de preferencias.</p>
        </section>

        <section id="transferencias" class="anchor">
          <h2>7) Transferencias y encargados</h2>
          <p>No vendemos tus datos. Compartimos información con encargados que nos prestan servicios (pasarelas de pago, logística, hospedaje, soporte) bajo contratos de confidencialidad y privacidad. En caso de requerimientos de autoridad, compartiremos solo lo estrictamente necesario.</p>
        </section>

        <section id="conservacion" class="anchor">
          <h2>8) Conservación y eliminación</h2>
          <p>Conservamos tus datos por el tiempo necesario para cumplir las finalidades y obligaciones legales (p. ej., fiscales). Posteriormente los eliminamos o anonimizamos de forma segura.</p>
        </section>

        <section id="seguridad" class="anchor">
          <h2>9) Medidas de seguridad</h2>
          <p>Implementamos controles administrativos, técnicos y físicos razonables para proteger tus datos contra acceso, uso o divulgación no autorizados.</p>
        </section>

        <section id="derechos" class="anchor">
          <h2>10) Derechos ARCO y revocación</h2>
          <p>Puedes ejercer tus derechos de <strong>Acceso, Rectificación, Cancelación y Oposición</strong>, así como revocar tu consentimiento para finalidades secundarias, enviando una solicitud con identificación a los medios de <a href="#contacto">Contacto</a>. Te responderemos en los plazos legales aplicables.</p>
        </section>

        <section id="limitacion" class="anchor">
          <h2>11) Limitación del uso y divulgación</h2>
          <p>Puedes inscribirte en listados de exclusión propios y solicitar dejar de recibir comunicaciones promocionales. Cada correo incluirá un mecanismo para cancelar la suscripción.</p>
        </section>

        <section id="menores" class="anchor">
          <h2>12) Tratamiento de menores</h2>
          <p>Nuestros productos y servicios no están dirigidos a menores de 18 años. Si identificamos datos de menores sin autorización, procederemos a su supresión segura.</p>
        </section>

        <section id="cambios" class="anchor">
          <h2>13) Cambios al aviso</h2>
          <p>Podremos actualizar este Aviso. Publicaremos la versión vigente y, si el cambio afecta finalidades que requieran consentimiento, te lo solicitaremos nuevamente.</p>
        </section>

        <section id="contacto" class="anchor">
          <h2>14) Contacto</h2>
          <ul>
            <li><strong>Email:</strong> <a href="mailto:rtort@jureto.com.mx" style="color:var(--brand);text-decoration:none;font-weight:600;">rtort@jureto.com.mx</a></li>
            <li><strong>Teléfono:</strong> <a href="tel:+525541937243" style="color:var(--brand);text-decoration:none;font-weight:600;">+52 55 4193 7243</a></li>
            <li><strong>Ubicación:</strong> 7CP5+34M San Jerónimo Chicahualco, Estado de México &amp; UAE</li>
          </ul>
          <div class="hr"></div>
          <h3 id="vinculos">Vínculos útiles</h3>
          <ul>
            <li><a href="{{ url('/terminos-y-condiciones') }}" style="color:var(--brand);text-decoration:none;font-weight:600;">Términos y Condiciones</a></li>
            <li><a href="{{ url('/garantias-y-devoluciones') }}" style="color:var(--brand);text-decoration:none;font-weight:600;">Garantías y Devoluciones</a></li>
          </ul>
          <p class="note">Este aviso es informativo y no constituye asesoría legal. Adáptalo a tu operación y consulta la normativa aplicable en tu jurisdicción.</p>
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
  const links = Array.from(document.querySelectorAll('#privacy .toc a'));
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
    const nearBottom = (scrollY + viewport) >= (docH - 600);
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
