@extends('layouts.web')
@section('title','Términos y Condiciones')

@section('content')
<style>
/* ========= NAMESPACE AISLADO ========= */
#terms{
  --ink:#1b2550; --muted:#6b7280; --line:#e7ecf5; --bg1:#f7fbff; --bg2:#fff6ef;
  --chip:#f4f7ff; --chip-ink:#11316a; --brand:#6ea8fe; --ok:#16a34a; --warn:#eab308; --danger:#ef4444;
  --radius:18px; --shadow:0 12px 28px rgba(2,8,23,.06);
}
#terms{ position:relative; width:100%; color:var(--ink); }
#terms::before{
  content:""; position:fixed; inset:0; z-index:-1; pointer-events:none;
  background:
    radial-gradient(900px 520px at 100% -10%, var(--bg1) 0%, transparent 55%),
    radial-gradient(1200px 700px at -10% 0%, var(--bg2) 0%, transparent 55%),
    #ffffff;
}
#terms .wrap{ max-width:1200px; margin:0 auto; padding: clamp(20px,2.8vw,34px) 16px 80px; }

/* ===== Layout con sidebar ===== */
#terms .grid{
  display:grid; gap: clamp(18px,2vw,28px);
  grid-template-columns: 300px 1fr;
}
@media (max-width: 980px){
  #terms .grid{ grid-template-columns: 1fr; }
}

/* ===== Hero ===== */
#terms .hero{ margin-bottom: clamp(12px,2vw,18px); }
#terms .title{ font-size: clamp(26px,4.8vw,54px); line-height:1.05; margin:0; letter-spacing:-.02em; }
#terms .sub{ color:var(--muted); max-width:860px; margin:8px 0 0; }
#terms .chips{ display:flex; gap:10px; flex-wrap:wrap; margin-top:12px }
#terms .chip{ background:var(--chip); color:var(--chip-ink); border:1px solid var(--line); border-radius:999px; padding:8px 12px; font-weight:600; }

/* ===== Sidebar (Índice) ===== */
#terms .sidebar{
  position: sticky; top: 90px; align-self:start;
  background:#fff; border:1px solid var(--line); border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 16px 14px;
}
#terms .sidebar h3{ font-size:16px; margin: 4px 10px 10px; color:#0b1530; letter-spacing:.2px; }
#terms .toc{ list-style:none; margin:0; padding:0; }
#terms .toc li{ margin:2px 0; }
#terms .toc a{
  display:flex; gap:10px; align-items:center;
  padding:10px 12px; border-radius:12px;
  color:#162447; text-decoration:none; font-weight:600;
  border:1px solid transparent;
}
#terms .toc a:hover{ background:#f9fbff; border-color:var(--line); }
#terms .toc a.active{ background:#eef4ff; border-color:#cfe0ff; color:#0b1530; }

/* Niveles del índice */
#terms .toc .lvl1{ font-size:14px; }
#terms .toc .lvl2{ font-size:13px; padding-left:28px; opacity:.95; }

/* ===== Contenido ===== */
#terms .content{
  background:#fff; border:1px solid var(--line); border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: clamp(16px,2.2vw,26px);
}
#terms section{ padding-top: clamp(14px,1.8vw,20px); }
#terms h2{ font-size: clamp(20px,3.2vw,32px); margin: 4px 0 8px; letter-spacing:-.01em; }
#terms h3{ font-size: clamp(16px,2.2vw,20px); margin: 14px 0 6px; color:#0f1a3a; }
#terms p{ color:#2b3357; margin-bottom:10px; line-height:1.65; }
#terms ul{ padding-left: 22px; margin: 8px 0; }
#terms li{ margin:6px 0 }
#terms .note{ background:#fff; border:1px dashed var(--line); border-radius:12px; padding:12px 14px; color:#374151; }
#terms .hr{ height:1px; background:linear-gradient(90deg, #fff, var(--line), #fff); margin: 18px 0; }

/* Anchor scroll safe */
#terms .anchor{ position:relative; scroll-margin-top:110px; }

/* ===== Móvil: ocultar sidebar ===== */
@media (max-width: 980px){
  #terms .sidebar{ display:none; }
}

/* ===== Botón flotante (móvil) ===== */
#terms .fab{
  position: fixed; right: 18px; bottom: 18px; z-index: 50;
  width: 56px; height:56px; border-radius: 999px;
  display: grid; place-items: center;
  border:1px solid var(--line); background:#fff; box-shadow: var(--shadow);
  font-weight:700; text-decoration:none; color:#0b1530;
  transform: translateY(20px); opacity:0; pointer-events:none;
  transition: .25s ease;
}
#terms .fab.show{ transform: translateY(0); opacity:1; pointer-events:auto; }
#terms .fab span{ font-size:22px; line-height:1; }

/* ===== Mini índice flotante (overlay móvil) ===== */
#terms .mtoc-backdrop{
  position: fixed; inset:0; background: rgba(10,18,40,.38);
  backdrop-filter: blur(2px); z-index: 49; opacity:0; pointer-events:none; transition:.2s;
}
#terms .mtoc-backdrop.open{ opacity:1; pointer-events:auto; }

#terms .mtoc{
  position: fixed; left: 12px; right:12px; bottom:12px; z-index: 50;
  background:#fff; border:1px solid var(--line); border-radius: 16px;
  box-shadow: 0 20px 60px rgba(2,8,23,.20);
  transform: translateY(18px); opacity:0; pointer-events:none; transition:.22s ease;
  max-height: 60vh; overflow:auto;
}
#terms .mtoc.open{ transform: translateY(0); opacity:1; pointer-events:auto; }
#terms .mtoc header{ display:flex; align-items:center; justify-content:space-between; padding:12px 14px; border-bottom:1px solid var(--line); }
#terms .mtoc header h4{ margin:0; font-size:15px; }
#terms .mtoc header button{ background:#f6f8fc; border:1px solid var(--line); border-radius:10px; padding:6px 10px; cursor:pointer; }
#terms .mtoc ul{ list-style:none; margin:0; padding:8px; }
#terms .mtoc li a{ display:block; padding:10px 12px; border-radius:10px; color:#0b1530; text-decoration:none; border:1px solid transparent; }
#terms .mtoc li a:hover{ background:#f9fbff; border-color:var(--line); }

/* Scroll suave */
html{ scroll-behavior: smooth; }
</style>

<div id="terms">
  <div class="wrap">

    <!-- HERO -->
    <header class="hero">
      <h1 class="title">Términos y Condiciones</h1>
      <p class="sub">Documento para el uso del sitio y compras de <strong>Jureto</strong>, empresa comercializadora de productos de papelería.</p>
      <div class="chips">
        <span class="chip">📅 Última actualización: 21 de octubre de 2025</span>
        <span class="chip">📜 Documento legal vigente</span>
      </div>
    </header>

    <div class="grid">
      <!-- ===== SIDEBAR (ÍNDICE) — se oculta en móvil ===== -->
      <aside class="sidebar">
        <h3>Índice</h3>
        <ul class="toc" id="toc-desktop">
          <li><a class="lvl1" href="#general">1. Información general</a></li>
          <li><a class="lvl1" href="#uso">2. Uso del sitio web</a></li>
          <li><a class="lvl1" href="#cuentas">3. Cuentas y seguridad</a></li>
          <li><a class="lvl1" href="#contenido-prohibido">4. Contenido y actividades prohibidas</a></li>
          <li><a class="lvl1" href="#compras">5. Proceso de compra</a></li>
          <li><a class="lvl1" href="#precios">6. Precios, disponibilidad y errores tipográficos</a></li>
          <li><a class="lvl1" href="#pagos">7. Métodos de pago y facturación</a></li>
          <li><a class="lvl1" href="#promos">8. Promociones, cupones y MSI</a></li>
          <li><a class="lvl1" href="#envios">9. Envíos, entrega y transferencia de riesgo</a></li>
          <li><a class="lvl1" href="#devoluciones">10. Devoluciones y reembolsos</a></li>
          <li><a class="lvl1" href="#garantias">11. Garantías y soporte técnico</a></li>
          <li><a class="lvl1" href="#propiedad">12. Propiedad intelectual y licencias</a></li>
          <li><a class="lvl1" href="#privacidad">13. Protección de datos personales</a></li>
          <li><a class="lvl1" href="#responsabilidad">14. Limitación de responsabilidad</a></li>
          <li><a class="lvl1" href="#fuerza-mayor">15. Fuerza mayor</a></li>
          <li><a class="lvl1" href="#modificaciones">16. Modificaciones y terminación</a></li>
          <li><a class="lvl1" href="#menores">17. Menores de edad</a></li>
          <li><a class="lvl1" href="#ley">18. Legislación aplicable y jurisdicción</a></li>
          <li><a class="lvl1" href="#contacto">19. Contacto</a></li>
          <li><a class="lvl2" href="#metodos-reembolso">• Métodos de reembolso</a></li>
          <li><a class="lvl2" href="#tiempos-servicio">• Tiempos de servicio/postventa</a></li>
        </ul>
      </aside>

      <!-- ===== CONTENIDO ===== -->
      <main class="content">
        <section id="general" class="anchor">
          <h2>1) Información general</h2>
          <p>Este sitio web es operado por <strong>Jureto</strong>. Al acceder o utilizar nuestros servicios, el usuario acepta íntegramente estos Términos y Condiciones. Podremos actualizar este documento en cualquier momento, publicando la versión vigente en el sitio.</p>
        </section>

        <section id="uso" class="anchor">
          <h2>2) Uso del sitio web</h2>
          <p>Te comprometes a usar el sitio de forma lícita, sin vulnerar derechos de terceros, ni afectar la operación de la plataforma. Queda prohibido el uso automatizado no autorizado (scraping, bots agresivos) que degrade el servicio.</p>
        </section>

        <section id="cuentas" class="anchor">
          <h2>3) Cuentas y seguridad</h2>
          <ul>
            <li>Eres responsable de la confidencialidad de tus credenciales y de toda actividad realizada desde tu cuenta.</li>
            <li>Debes notificarnos inmediatamente ante accesos no autorizados o sospecha de uso indebido.</li>
            <li>Podemos suspender o cerrar cuentas que incumplan estos términos o representen riesgo para otros usuarios.</li>
          </ul>
        </section>

        <section id="contenido-prohibido" class="anchor">
          <h2>4) Contenido y actividades prohibidas</h2>
          <ul>
            <li>Subir o distribuir malware, spam, contenido difamatorio o ilegal.</li>
            <li>Eludir medidas técnicas de seguridad o realizar pruebas de penetración sin autorización previa y por escrito.</li>
            <li>Suplantar identidades o manipular pedidos/precios mediante vulneraciones técnicas.</li>
          </ul>
        </section>

        <section id="compras" class="anchor">
          <h2>5) Proceso de compra</h2>
          <p>Las compras se realizan agregando productos al carrito y confirmando el pedido. Toda orden implica la aceptación de precios, características y condiciones vigentes al momento de la transacción. Los correos de confirmación no constituyen aceptación irrevocable si se detectan anomalías evidentes.</p>
        </section>

        <section id="precios" class="anchor">
          <h2>6) Precios, disponibilidad y errores tipográficos</h2>
          <p>Los precios están en MXN e incluyen impuestos salvo indicación expresa. La disponibilidad depende de inventario y proveedor. En casos de error manifiesto (p.ej., precio de $1.00 por un equipo de alto valor), podremos cancelar la orden y reembolsar íntegramente.</p>
        </section>

        <section id="pagos" class="anchor">
          <h2>7) Métodos de pago y facturación</h2>
          <p>Aceptamos tarjetas, transferencias y métodos digitales seguros. El procesamiento es realizado por plataformas certificadas con cifrado. La facturación se emite con datos correctos proporcionados por el cliente y dentro del periodo fiscal aplicable.</p>
        </section>

        <section id="promos" class="anchor">
          <h2>8) Promociones, cupones y MSI</h2>
          <ul>
            <li>Las promociones y cupones tienen vigencia y condiciones específicas; no son acumulables salvo indicación.</li>
            <li>Meses sin intereses (MSI) y financiamiento están sujetos a aprobación del emisor y montos mínimos.</li>
            <li>Los programas de lealtad o puntos se rigen por sus propias reglas.</li>
          </ul>
        </section>

        <section id="envios" class="anchor">
          <h2>9) Envíos, entrega y transferencia de riesgo</h2>
          <p>El tiempo de entrega varía por destino y disponibilidad. El riesgo se transfiere al cliente al momento de la entrega según guía/carta porte. Es responsabilidad del receptor revisar el paquete y reportar daños visibles al momento.</p>
        </section>

        <section id="devoluciones" class="anchor">
          <h2>10) Devoluciones y reembolsos</h2>
          <p>Se rigen por nuestra <a href="{{ url('/garantias-y-devoluciones') }}" style="color:var(--brand);text-decoration:none;font-weight:600;">Política de Garantías y Devoluciones</a>. No se aceptan devoluciones fuera de plazo o sin empaque/accesorios.</p>
          <h3 id="metodos-reembolso">Métodos de reembolso</h3>
          <ul>
            <li>Nota de crédito o medio original de pago, según disponibilidad del proveedor.</li>
            <li>Los tiempos bancarios pueden extenderse por procesos ajenos a nosotros.</li>
          </ul>
        </section>

        <section id="garantias" class="anchor">
          <h2>11) Garantías y soporte técnico</h2>
          <p>Los productos cuentan con garantía del fabricante o distribuidor. El dictamen (reparación, sustitución o nota de crédito) depende del proveedor, previa revisión de número de serie, evidencia de falla y condiciones de uso.</p>
          <h3 id="tiempos-servicio">Tiempos de servicio y postventa</h3>
          <p>El plazo estimado se comunicará caso por caso. Equipos con diagnóstico en centro de servicio pueden requerir tiempos adicionales.</p>
        </section>

        <section id="propiedad" class="anchor">
          <h2>12) Propiedad intelectual y licencias</h2>
          <p>Todos los contenidos, marcas, logotipos, diseños, textos, imágenes y software del sitio están protegidos por derechos de autor y propiedad industrial. No se concede licencia alguna salvo uso personal y no comercial del sitio.</p>
        </section>

        <section id="privacidad" class="anchor">
          <h2>13) Protección de datos personales</h2>
          <p>El tratamiento de datos se apega a nuestro <a href="{{ url('/aviso-de-privacidad') }}" style="color:var(--brand);text-decoration:none;font-weight:600;">Aviso de Privacidad</a>. Puedes ejercer tus derechos de acceso, rectificación, cancelación y oposición conforme a la normativa aplicable.</p>
        </section>

        <section id="responsabilidad" class="anchor">
          <h2>14) Limitación de responsabilidad</h2>
          <p>No seremos responsables por daños indirectos, pérdida de datos o lucro cesante derivados del uso del sitio. En ningún caso nuestra responsabilidad total excederá el monto efectivamente pagado por el producto o servicio en controversia.</p>
        </section>

        <section id="fuerza-mayor" class="anchor">
          <h2>15) Fuerza mayor</h2>
          <p>No seremos responsables por incumplimientos causados por eventos fuera de nuestro control razonable, incluyendo desastres naturales, fallas generalizadas de internet, actos gubernamentales, conflictos laborales o pandemias.</p>
        </section>

        <section id="modificaciones" class="anchor">
          <h2>16) Modificaciones y terminación</h2>
          <p>Podemos modificar estos términos y/o suspender el servicio, notificando mediante su publicación en el sitio. El uso continuado implica aceptación de la versión vigente.</p>
        </section>

        <section id="menores" class="anchor">
          <h2>17) Menores de edad</h2>
          <p>El sitio no está dirigido a menores de 18 años. Las compras deben ser realizadas por mayores de edad con plena capacidad legal.</p>
        </section>

        <section id="ley" class="anchor">
          <h2>18) Legislación aplicable y jurisdicción</h2>
          <p>Estos términos se rigen por las leyes de los Estados Unidos Mexicanos. Cualquier disputa se someterá a los tribunales competentes de la Ciudad de México.</p>
        </section>

        <section id="contacto" class="anchor">
          <h2>19) Contacto</h2>
          <ul>
            <li><strong>Email:</strong> <a href="mailto:rtort@jureto.com.mx" style="color:var(--brand);text-decoration:none;font-weight:600;">rtort@jureto.com.mx</a></li>
            <li><strong>Teléfono:</strong> <a href="tel:+525541937243" style="color:var(--brand);text-decoration:none;font-weight:600;">+52 55 4193 7243</a></li>
            <li><strong>Ubicación:</strong> 7CP5+34M San Jerónimo Chicahualco, Estado de México &amp; UAE</li>
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
  const links = Array.from(document.querySelectorAll('#terms .toc a'));
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
    // cerrar mini índice al hacer click en un enlace
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
