@extends('layouts.web')
@section('title','Formas de Envío')

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

/* ===== Grid con sidebar ===== */
#ship .grid{ display:grid; gap: clamp(18px,2vw,28px); grid-template-columns: 300px 1fr; }
@media (max-width: 980px){ #ship .grid{ grid-template-columns: 1fr; } }

/* ===== Hero ===== */
#ship .hero{ margin-bottom: clamp(12px,2vw,18px); }
#ship .title{ font-size: clamp(26px,4.8vw,54px); line-height:1.05; margin:0; letter-spacing:-.02em; }
#ship .sub{ color:var(--muted); max-width:860px; margin:8px 0 0; }
#ship .chips{ display:flex; gap:10px; flex-wrap:wrap; margin-top:12px }
#ship .chip{ background:var(--chip); color:var(--chip-ink); border:1px solid var(--line); border-radius:999px; padding:8px 12px; font-weight:600; }

/* ===== Sidebar ===== */
#ship .sidebar{
  position: sticky; top: 90px; align-self:start;
  background:#fff; border:1px solid var(--line); border-radius: var(--radius);
  box-shadow: var(--shadow); padding:16px 14px;
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
#ship ul{ padding-left:22px; margin:8px 0; }
#ship li{ margin:6px 0 }
#ship .note{ background:#fff; border:1px dashed var(--line); border-radius:12px; padding:12px 14px; color:#374151; }
#ship .hr{ height:1px; background:linear-gradient(90deg,#fff,var(--line),#fff); margin:18px 0; }

/* ===== Móvil ===== */
@media (max-width: 980px){ #ship .sidebar{ display:none; } }

/* ===== FAB (móvil) ===== */
#ship .fab{
  position: fixed; right: 18px; bottom: 18px; z-index: 50;
  width: 56px; height:56px; border-radius: 999px;
  display: grid; place-items: center;
  border:1px solid var(--line); background:#fff; box-shadow: var(--shadow);
  font-weight:700; text-decoration:none; color:#0b1530;
  transform: translateY(20px); opacity:0; pointer-events:none; transition:.25s ease;
}
#ship .fab.show{ transform: translateY(0); opacity:1; pointer-events:auto; }
#ship .fab span{ font-size:22px; line-height:1; }

/* ===== Mini índice móvil ===== */
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

    <header class="hero">
      <h1 class="title">Formas de Envío</h1>
      <p class="sub">En <strong>Jureto</strong> integramos proveedores de paquetería a través de nuestra plataforma. <strong>Solo realizamos envíos salientes</strong> de pedidos; <strong>no ofrecemos recolección en domicilio</strong> ni <strong>entrega/pick-up en tienda</strong>, salvo <strong>recolección por devolución autorizada</strong>.</p>
      <div class="chips">
        <span class="chip">🚚 Envíos salientes</span>
        <span class="chip">🏷️ Elección de paquetería</span>
        <span class="chip">📦 Rastreo en nuestro portal</span>
      </div>
    </header>

    <div class="grid">
      <aside class="sidebar">
        <h3>Índice</h3>
        <ul class="toc" id="toc-desktop">
          <li><a class="lvl1" href="#como-enviamos">1. ¿Cómo enviamos?</a></li>
          <li><a class="lvl1" href="#eleccion">2. Elección de paquetería</a></li>
          <li><a class="lvl1" href="#tarifas">3. Tarifas y tiempos</a></li>
          <li><a class="lvl1" href="#entrega">4. Entrega y políticas</a></li>
          <li><a class="lvl1" href="#devoluciones">5. Recolección solo por devolución</a></li>
          <li><a class="lvl1" href="#rastreo">6. Rastreo en nuestro sitio</a></li>
          <li><a class="lvl1" href="#cobertura">7. Cobertura y restricciones</a></li>
          <li><a class="lvl1" href="#contacto">8. Contacto</a></li>
        </ul>
      </aside>

      <main class="content">
        <section id="como-enviamos" class="anchor">
          <h2>1) ¿Cómo enviamos?</h2>
          <ul>
            <li><strong>Solo envíos salientes:</strong> gestionamos el despacho de tus pedidos desde nuestros almacenes.</li>
            <li><strong>Sin pick-up en tienda:</strong> no contamos con mostrador para entrega local.</li>
            <li><strong>Sin recolección en domicilio:</strong> no retiramos paquetes del cliente, excepto devoluciones autorizadas (ver punto 5).</li>
          </ul>
        </section>

        <section id="eleccion" class="anchor">
          <h2>2) Elección de paquetería</h2>
          <p>Al finalizar tu compra, nuestro sistema te muestra varias opciones de mensajería (p. ej., DHL, FedEx, Estafeta, UPS, Redpack, Paquetexpress, 99minutos, entre otras). <strong>Tú eliges</strong> la que más te convenga según precio, tiempo estimado y cobertura.</p>
          <p class="note">La disponibilidad depende del origen/destino, dimensiones/peso y políticas del transportista.</p>
        </section>

        <section id="tarifas" class="anchor">
          <h2>3) Tarifas y tiempos</h2>
          <ul>
            <li><strong>Consulta en tiempo real:</strong> mostramos precios y ventanas de entrega estimadas por cada paquetería.</li>
            <li><strong>Promociones/convenios:</strong> cuando aplican, se reflejan automáticamente.</li>
            <li><strong>Estimaciones:</strong> pueden variar por fechas pico, zonas extendidas o incidencias operativas.</li>
          </ul>
        </section>

        <section id="entrega" class="anchor">
          <h2>4) Entrega y políticas</h2>
          <ul>
            <li><strong>Entrega a domicilio o sucursal de paquetería:</strong> según disponibilidad de la opción elegida.</li>
            <li><strong>Firma/identificación:</strong> puede requerirse por el transportista.</li>
            <li><strong>Reintentos/redirecciones:</strong> sujetos a políticas y posibles cargos del transportista.</li>
          </ul>
        </section>

        <section id="devoluciones" class="anchor">
          <h2>5) Recolección solo por devolución</h2>
          <p>Si tu devolución es <strong>autorizada</strong>, podemos <strong>programar la recolección</strong> con la paquetería. Para ello:</p>
          <ul>
            <li>Solicita tu devolución vía correo a <a href="mailto:rtort@jureto.com.mx">rtort@jureto.com.mx</a> indicando número de pedido, motivo y evidencias.</li>
            <li>Te enviaremos la <strong>guía</strong> y la <strong>ventana de recolección</strong> (o instrucciones para entregar en sucursal).</li>
            <li>Prepara el paquete con <strong>embalaje adecuado</strong> y la etiqueta visible. Algunas paqueterías realizan 1–2 intentos.</li>
          </ul>
          <p class="note">Las devoluciones se rigen por nuestra <a href="{{ route('policy.shipping') }}" style="color:var(--brand);text-decoration:none;font-weight:600;">política de Envíos, Devoluciones y Cancelaciones</a>.</p>
        </section>

        <section id="rastreo" class="anchor">
          <h2>6) Rastreo en nuestro sitio</h2>
          <p>Tras generar la guía, te compartimos el <strong>número de rastreo</strong>. Puedes consultar el estatus directamente en tu cuenta o en la sección de rastreo de nuestro sitio.</p>
        </section>

        <section id="cobertura" class="anchor">
          <h2>7) Cobertura y restricciones</h2>
          <ul>
            <li><strong>Nacional:</strong> envíos a todo México; en ciertas zonas hay servicios exprés o última milla.</li>
            <li><strong>Internacional:</strong> sujeto a disponibilidad y regulaciones aduanales.</li>
            <li><strong>Restricciones:</strong> artículos prohibidos o restringidos se validan según la paquetería; nuestro sistema bloquea opciones cuando aplican.</li>
          </ul>
        </section>

        <section id="contacto" class="anchor">
          <h2>8) Contacto</h2>
          <ul>
            <li><strong>Email:</strong> <a href="mailto:rtort@jureto.com.mx" style="color:var(--brand);text-decoration:none;font-weight:600;">rtort@jureto.com.mx</a></li>
            <li><strong>Teléfono:</strong> <a href="tel:+525541937243" style="color:var(--brand);text-decoration:none;font-weight:600;">+52 55 4193 7243</a></li>
            <li><strong>Ubicación:</strong> 7CP5+34M San Jerónimo Chicahualco, Estado de México &amp; UAE</li>
          </ul>
          <div class="hr"></div>
          <ul>
            <li><a href="{{ route('policy.shipping') }}" style="color:var(--brand);text-decoration:none;font-weight:600;">Envíos, Devoluciones y Cancelaciones</a></li>
            <li><a href="{{ route('policy.payments') }}" style="color:var(--brand);text-decoration:none;font-weight:600;">Formas de Pago</a></li>
            <li><a href="{{ route('policy.terms') }}" style="color:var(--brand);text-decoration:none;font-weight:600;">Términos y Condiciones</a></li>
          </ul>
        </section>
      </main>
    </div>
  </div>

  <!-- FAB -->
  <button id="fab" class="fab" aria-label="Índice"><span>☰</span></button>

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
// ===== Scrollspy robusto + offset en clic =====
(function(){
  const root = document.getElementById('ship');
  if (!root) return;

  const OFFSET = 120; // ajusta si tu navbar fija es más alta
  const links = Array.from(document.querySelectorAll('#ship .toc a'));
  const targets = links.map(a => document.querySelector(a.getAttribute('href'))).filter(Boolean);

  function smoothTo(id){
    const el = document.querySelector(id); if(!el) return;
    const top = window.scrollY + el.getBoundingClientRect().top - OFFSET;
    window.scrollTo({top, behavior:'smooth'});
  }
  document.querySelectorAll('#ship .toc a, #toc-mobile a').forEach(a=>{
    a.addEventListener('click', e=>{
      const href = a.getAttribute('href'); if(!href?.startsWith('#')) return;
      e.preventDefault(); smoothTo(href);
    });
  });

  let tops=[];
  function compute(){ 
    tops = targets.map(el=>({id:'#'+el.id, top: window.scrollY + el.getBoundingClientRect().top - OFFSET - 1}))
                  .sort((a,b)=>a.top-b.top);
  }
  function setActive(){
    const y = window.scrollY; let current = tops[0]?.id || null;
    for (let i=0;i<tops.length;i++){ if (y >= tops[i].top) current = tops[i].id; else break; }
    links.forEach(a=>a.classList.toggle('active', a.getAttribute('href')===current));
    document.querySelectorAll('#toc-mobile a').forEach(a=>a.classList.toggle('active', a.getAttribute('href')===current));
  }
  window.addEventListener('scroll', setActive, {passive:true});
  window.addEventListener('resize', ()=>{ compute(); setActive(); });
  document.addEventListener('DOMContentLoaded', ()=>{ compute(); setActive(); });
  window.addEventListener('load', ()=>{ compute(); setActive(); });
  setTimeout(()=>{ compute(); setActive(); }, 250);
  new ResizeObserver(()=>{ compute(); setActive(); }).observe(root);
})();

// ===== FAB y mini índice móvil =====
(function(){
  const fab = document.getElementById('fab');
  const mtoc = document.getElementById('mtoc');
  const backdrop = document.getElementById('mtoc-backdrop');
  const closeBtn = document.getElementById('mtoc-close');
  const tocDesktop = document.getElementById('toc-desktop');
  const tocMobile = document.querySelector('#mtoc #toc-mobile');

  if (tocDesktop && tocMobile) tocMobile.innerHTML = tocDesktop.innerHTML;

  function isMobile(){ return window.matchMedia('(max-width: 980px)').matches; }
  function toggleMTOC(force){
    const open = (typeof force === 'boolean') ? force : !mtoc.classList.contains('open');
    mtoc.classList.toggle('open', open); backdrop.classList.toggle('open', open);
  }
  function onScroll(){
    if (!isMobile()) { fab.classList.remove('show'); toggleMTOC(false); return; }
    const y = window.scrollY, h = window.innerHeight;
    const docH = Math.max(document.body.scrollHeight, document.documentElement.scrollHeight);
    fab.classList.toggle('show', (y + h) >= (docH - 600));
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
