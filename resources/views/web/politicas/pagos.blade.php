@extends('layouts.web')
@section('title','Formas de Pago')

@section('content')
<style>
/* ========= NAMESPACE AISLADO ========= */
#pay{
  --ink:#1b2550; --muted:#6b7280; --line:#e7ecf5; --bg1:#f7fbff; --bg2:#fff6ef;
  --chip:#f4f7ff; --chip-ink:#11316a; --brand:#6ea8fe; --ok:#16a34a; --warn:#eab308; --danger:#ef4444;
  --radius:18px; --shadow:0 12px 28px rgba(2,8,23,.06);
}
#pay{ position:relative; width:100%; color:var(--ink); }
#pay::before{
  content:""; position:fixed; inset:0; z-index:-1; pointer-events:none;
  background:
    radial-gradient(900px 520px at 100% -10%, var(--bg1) 0%, transparent 55%),
    radial-gradient(1200px 700px at -10% 0%, var(--bg2) 0%, transparent 55%),
    #ffffff;
}
#pay .wrap{ max-width:1200px; margin:0 auto; padding: clamp(20px,2.8vw,34px) 16px 80px; }

/* ===== Layout con sidebar ===== */
#pay .grid{
  display:grid; gap: clamp(18px,2vw,28px);
  grid-template-columns: 300px 1fr;
}
@media (max-width: 980px){
  #pay .grid{ grid-template-columns: 1fr; }
}

/* ===== Hero ===== */
#pay .hero{ margin-bottom: clamp(12px,2vw,18px); }
#pay .title{ font-size: clamp(26px,4.8vw,54px); line-height:1.05; margin:0; letter-spacing:-.02em; }
#pay .sub{ color:var(--muted); max-width:860px; margin:8px 0 0; }
#pay .chips{ display:flex; gap:10px; flex-wrap:wrap; margin-top:12px }
#pay .chip{ background:var(--chip); color:var(--chip-ink); border:1px solid var(--line); border-radius:999px; padding:8px 12px; font-weight:600; }

/* ===== Sidebar (Índice) ===== */
#pay .sidebar{
  position: sticky; top: 90px; align-self:start;
  background:#fff; border:1px solid var(--line); border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 16px 14px;
}
#pay .sidebar h3{ font-size:16px; margin: 4px 10px 10px; color:#0b1530; letter-spacing:.2px; }
#pay .toc{ list-style:none; margin:0; padding:0; }
#pay .toc li{ margin:2px 0; }
#pay .toc a{
  display:flex; gap:10px; align-items:center;
  padding:10px 12px; border-radius:12px;
  color:#162447; text-decoration:none; font-weight:600;
  border:1px solid transparent;
}
#pay .toc a:hover{ background:#f9fbff; border-color:var(--line); }
#pay .toc a.active{ background:#eef4ff; border-color:#cfe0ff; color:#0b1530; }

/* ===== Contenido ===== */
#pay .content{
  background:#fff; border:1px solid var(--line); border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: clamp(16px,2.2vw,26px);
}
#pay section{ padding-top: clamp(14px,1.8vw,20px); }
#pay h2{ font-size: clamp(20px,3.2vw,32px); margin: 4px 0 8px; letter-spacing:-.01em; }
#pay h3{ font-size: clamp(16px,2.2vw,20px); margin: 14px 0 6px; color:#0f1a3a; }
#pay p{ color:#2b3357; margin-bottom:10px; line-height:1.65; }
#pay ul{ padding-left: 22px; margin: 8px 0; }
#pay li{ margin:6px 0 }
#pay .note{ background:#fff; border:1px dashed var(--line); border-radius:12px; padding:12px 14px; color:#374151; }

/* ===== Móvil: ocultar sidebar ===== */
@media (max-width: 980px){
  #pay .sidebar{ display:none; }
}

/* ===== Botón flotante (móvil) ===== */
#pay .fab{
  position: fixed; right: 18px; bottom: 18px; z-index: 50;
  width: 56px; height:56px; border-radius: 999px;
  display: grid; place-items: center;
  border:1px solid var(--line); background:#fff; box-shadow: var(--shadow);
  font-weight:700; text-decoration:none; color:#0b1530;
  transform: translateY(20px); opacity:0; pointer-events:none;
  transition: .25s ease;
}
#pay .fab.show{ transform: translateY(0); opacity:1; pointer-events:auto; }
#pay .fab span{ font-size:22px; line-height:1; }

/* ===== Mini índice flotante (overlay móvil) ===== */
#pay .mtoc-backdrop{
  position: fixed; inset:0; background: rgba(10,18,40,.38);
  backdrop-filter: blur(2px); z-index: 49; opacity:0; pointer-events:none; transition:.2s;
}
#pay .mtoc-backdrop.open{ opacity:1; pointer-events:auto; }

#pay .mtoc{
  position: fixed; left: 12px; right:12px; bottom:12px; z-index: 50;
  background:#fff; border:1px solid var(--line); border-radius: 16px;
  box-shadow: 0 20px 60px rgba(2,8,23,.20);
  transform: translateY(18px); opacity:0; pointer-events:none; transition:.22s ease;
  max-height: 60vh; overflow:auto;
}
#pay .mtoc.open{ transform: translateY(0); opacity:1; pointer-events:auto; }
#pay .mtoc header{ display:flex; align-items:center; justify-content:space-between; padding:12px 14px; border-bottom:1px solid var(--line); }
#pay .mtoc header h4{ margin:0; font-size:15px; }
#pay .mtoc header button{ background:#f6f8fc; border:1px solid var(--line); border-radius:10px; padding:6px 10px; cursor:pointer; }
#pay .mtoc ul{ list-style:none; margin:0; padding:8px; }
#pay .mtoc li a{ display:block; padding:10px 12px; border-radius:10px; color:#0b1530; text-decoration:none; border:1px solid transparent; }
#pay .mtoc li a:hover{ background:#f9fbff; border-color:var(--line); }

/* Scroll suave */
html{ scroll-behavior: smooth; }
</style>

<div id="pay">
  <div class="wrap">

    <!-- HERO -->
    <header class="hero">
      <h1 class="title">Formas de Pago</h1>
      <p class="sub">En <strong>Jureto</strong> ofrecemos métodos de pago seguros y flexibles según el tipo de compra y volumen de pedido.</p>
      <div class="chips">
        <span class="chip">💳 Stripe</span>
        <span class="chip">🏪 Transferencia / Efectivo</span>
      </div>
    </header>

    <div class="grid">
      <aside class="sidebar">
        <h3>Índice</h3>
        <ul class="toc" id="toc-desktop">
          <li><a class="lvl1" href="#stripe">1. Pagos en línea con Stripe</a></li>
          <li><a class="lvl1" href="#mayoreo">2. Ventas de mayoreo</a></li>
          <li><a class="lvl2" href="#transferencia">• Transferencia bancaria</a></li>
          <li><a class="lvl2" href="#efectivo">• Efectivo contra entrega</a></li>
          <li><a class="lvl1" href="#anticipo">3. Anticipos y confirmación</a></li>
          <li><a class="lvl1" href="#seguridad">4. Seguridad de la información</a></li>
          <li><a class="lvl1" href="#contacto">5. Contacto</a></li>
        </ul>
      </aside>

      <!-- ===== CONTENIDO ===== -->
      <main class="content">
        <section id="stripe" class="anchor">
          <h2>1) Pagos en línea con Stripe</h2>
          <p>Para compras minoristas o pedidos realizados directamente en nuestro sitio web, utilizamos <strong>Stripe</strong> como procesador de pagos seguro y certificado.</p>
          <ul>
            <li>Stripe permite pagar con tarjetas de crédito y débito Visa, Mastercard y American Express.</li>
            <li>Todas las transacciones son cifradas mediante <strong>SSL/TLS</strong> y procesadas sin que Jureto almacene información sensible de las tarjetas.</li>
            <li>El comprobante de pago se genera automáticamente y se envía por correo electrónico una vez confirmada la operación.</li>
          </ul>
          <p class="note">Stripe es una plataforma global reconocida por su cumplimiento con PCI DSS, garantizando máxima seguridad en tus transacciones.</p>
        </section>

        <section id="mayoreo" class="anchor">
          <h2>2) Ventas de mayoreo</h2>
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

        <section id="anticipo" class="anchor">
          <h2>3) Anticipos y confirmación</h2>
          <p>Todo pedido de mayoreo o especial debe contar con un anticipo para garantizar su preparación y reserva de inventario. El anticipo no es reembolsable si el cliente cancela una vez iniciado el proceso de compra o surtido.</p>
        </section>

        <section id="seguridad" class="anchor">
          <h2>4) Seguridad de la información</h2>
          <p><strong>Jureto</strong> no almacena números de tarjeta ni claves bancarias. Toda la información sensible se maneja exclusivamente por los sistemas cifrados de Stripe o por instituciones bancarias reguladas. Nuestro sitio cuenta con certificado SSL vigente y auditorías periódicas de seguridad.</p>
        </section>

        <section id="contacto" class="anchor">
          <h2>5) Contacto</h2>
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
    <ul id="toc-mobile"></ul>
  </nav>
</div>

<script>
// ===== Scrollspy robusto por posición (sin fallos con headers fijos) =====
(function(){
  const root = document.getElementById('pay');
  if (!root) return;
  const links = Array.from(document.querySelectorAll('#pay .toc a'));
  const targets = links
    .map(a => document.querySelector(a.getAttribute('href')))
    .filter(Boolean);

  let tops = [];
  const OFFSET = 120; // ajusta si tu navbar fija es más alta

  function computeTops(){
    tops = targets.map(el => {
      const rect = el.getBoundingClientRect();
      return { id:'#'+el.id, top: window.scrollY + rect.top - OFFSET };
    }).sort((a,b)=> a.top - b.top);
  }

  function onScroll(){
    const y = window.scrollY;
    let current = tops[0]?.id || null;
    for (let i=0;i<tops.length;i++){
      if (y >= tops[i].top) current = tops[i].id; else break;
    }
    links.forEach(a => a.classList.toggle('active', a.getAttribute('href') === current));
  }

  window.addEventListener('scroll', onScroll, {passive:true});
  window.addEventListener('resize', ()=>{ computeTops(); onScroll(); });
  document.addEventListener('DOMContentLoaded', ()=>{ computeTops(); onScroll(); });
  setTimeout(()=>{ computeTops(); onScroll(); }, 200); // por carga de fuentes/imagenes
})();

// ===== FAB y mini índice móvil =====
(function(){
  const fab = document.getElementById('fab');
  const mtoc = document.getElementById('mtoc');
  const backdrop = document.getElementById('mtoc-backdrop');
  const closeBtn = document.getElementById('mtoc-close');
  const tocDesktop = document.getElementById('toc-desktop');
  const tocMobile = document.getElementById('toc-mobile');

  // Clona el índice de desktop al mini índice móvil
  if (tocDesktop && tocMobile) {
    tocMobile.innerHTML = tocDesktop.innerHTML;
    tocMobile.querySelectorAll('a').forEach(a=>{
      a.addEventListener('click', ()=> toggleMTOC(false));
    });
  }

  function toggleMTOC(force){
    const open = (typeof force === 'boolean') ? force : !mtoc.classList.contains('open');
    mtoc.classList.toggle('open', open);
    backdrop.classList.toggle('open', open);
  }

  function onScroll(){
    const mobile = window.matchMedia('(max-width: 980px)').matches;
    if (!mobile) { fab.classList.remove('show'); toggleMTOC(false); return; }
    const scrollY = window.scrollY, view = window.innerHeight;
    const docH = Math.max(document.body.scrollHeight, document.documentElement.scrollHeight);
    const nearBottom = (scrollY + view) >= (docH - 600); // umbral
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
