<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>@yield('title','Jureto')</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="{{ asset('css/app.css') }}" rel="stylesheet">
  <style>
    :root{
      --ink:#0f172a; --muted:#475569; --line:#e5e9f2; --bg:#ffffff;
      --pill:#b6332f; --pill-hover:#a02a27; --shadow:0 8px 24px rgba(2,8,23,.06);
      --container:1180px;
      --sheet-bg:#ffffff; --sheet-radius:20px; --sheet-shadow: 0 18px 60px rgba(2,8,23,.22);
      --backdrop: rgba(15,23,42,.38);
    }

    /* === Reset / layout base === */
    *{ box-sizing: border-box; }
    html, body{ margin:0; padding:0; }
    body{ font-family: ui-sans-serif, system-ui, -apple-system; background:var(--bg); color:var(--ink); overflow-x:hidden; }

    /* ===== Header ===== */
    header.header{ position:sticky; top:0; left:0; right:0; width:100%; background:#fff; box-shadow:var(--shadow); z-index:40; }
    .wrap{ max-width:var(--container); margin:0 auto; padding:14px 20px; }
    .navbar{ display:flex; align-items:center; gap:18px; }
    .brand{ display:flex; align-items:center; gap:12px; white-space:nowrap; text-decoration:none; color:var(--ink); }
    .brand img{ height:34px; display:block; }

    .nav-center{ display:flex; justify-content:center; align-items:center; gap:32px; flex:1; }
    .nav-link{ position:relative; text-decoration:none; color:var(--ink); font-weight:700; padding:8px 4px; display:inline-flex; align-items:center; }
    .nav-link::after{ content:""; position:absolute; left:0; right:0; bottom:-6px; height:3px; border-radius:3px; background:transparent; transform:scaleX(0); transition:transform .18s ease, background .18s ease; }
    .nav-link:hover::after{ background:#000; transform:scaleX(1); }
    .nav-link.is-active::after{ background:#000; transform:scaleX(1); }

    .right-tools{ display:flex; align-items:center; gap:14px; }
    .ico{ width:22px; height:22px; display:inline-flex; align-items:center; justify-content:center; text-decoration:none; }
    .ico svg{ width:20px; height:20px; stroke:#111; fill:none; stroke-width:2; }
    .btn-pill{ appearance:none; border:0; border-radius:999px; padding:12px 24px; font-weight:700; background:var(--pill); color:#fff; cursor:pointer; transition:background .2s ease, transform .1s ease; }
    .btn-pill:hover{ background:var(--pill-hover); transform: translateY(-1px); }

    /* Mobile topbar */
    .mobile-topbar{ display:none; align-items:center; justify-content:space-between; max-width:var(--container); margin:0 auto; padding:10px 16px; }
    .m-left{ display:flex; align-items:center; gap:12px; }
    .m-brand{ display:flex; align-items:center; gap:8px; text-decoration:none; color:var(--ink); }
    .m-logo{ height:26px; width:auto; display:block; }
    .m-icons{ display:flex; align-items:center; gap:10px; margin-left:8px; }
    .burger{ display:none; background:transparent; border:0; padding:6px; }
    .burger svg{ width:24px; height:24px; }

    @media (max-width: 980px){
      .wrap.navbar{ display:none; }
      .mobile-topbar{ display:flex; }
      .nav-center{ display:none; }
      .burger{ display:inline-flex; }
    }

    /* ===== Bottom Sheet (móvil) ===== */
    .sheet-backdrop{ position:fixed; inset:0; background:var(--backdrop); opacity:0; pointer-events:none; transition:opacity .2s ease; backdrop-filter: blur(2px); z-index:49; }
    .sheet{ position:fixed; left:0; right:0; bottom:0; z-index:50; transform: translateY(100%); background:var(--sheet-bg); border-top-left-radius: var(--sheet-radius); border-top-right-radius: var(--sheet-radius); box-shadow: var(--sheet-shadow); transition: transform .26s ease; will-change: transform; touch-action: none; }
    .sheet__drag{ display:flex; justify-content:center; padding-top:10px; }
    .sheet__handle{ width:48px; height:5px; border-radius:999px; background:#d1d5db; }
    .sheet__content{ padding:14px 18px 18px; }
    .sheet__grid{ display:grid; gap:16px; }
    .sheet__nav a{ display:block; text-decoration:none; color:var(--ink); font-weight:800; font-size:1.05rem; padding:10px 6px; border-bottom:1px solid var(--line); }
    .sheet__footer{ display:flex; align-items:center; justify-content:space-between; gap:12px; padding-top:10px; }
    .sheet__icons{ display:flex; align-items:center; gap:12px; }
    .sheet__icons .ico svg{ stroke:#111; }
    .sheet-open .sheet{ transform: translateY(0); }
    .sheet-open .sheet-backdrop{ opacity:1; pointer-events:auto; }
    @media (max-height: 700px){ .sheet{ max-height: 86vh; overflow:auto; } }

    /* ===== FOOTER ===== */
    .ft{ background:#fff; border-top:1px solid #e9eef6; margin-top:30px; }
    .ft__wrap{ max-width:1180px; margin:0 auto; padding:24px 20px 36px; }
    .ft__head{ display:flex; align-items:center; justify-content:space-between; gap:16px; }
    .ft__brand{ display:flex; align-items:center; gap:12px; text-decoration:none; color:#0f172a; }
    .ft__logo{ height:38px; width:auto; display:block; }
    .ft__slogan{ font-size:.95rem; color:#6b7280; }
    .ft__cta{ display:inline-flex; align-items:center; justify-content:center; background:#3b5bcc; color:#fff; font-weight:800; text-decoration:none; padding:12px 22px; border-radius:999px; box-shadow:0 10px 28px rgba(59,91,204,.22); transition:transform .12s ease, box-shadow .2s ease, background .2s ease; }
    .ft__cta:hover{ transform:translateY(-1px); background:#4b69d6; }
    .ft__divider{ border:0; border-top:1px solid #e9eef6; margin:18px 0 12px; }

    .ft__grid{ display:grid; gap:24px; grid-template-columns: repeat(6, minmax(160px,1fr)); }
    .ft__title{ background:transparent; border:0; padding:0; margin:0 0 10px 0; font-weight:800; color:#0f172a; font-size:1rem; display:flex; align-items:center; justify-content:space-between; width:100%; }
    .ft__chev{ width:16px; height:16px; border-right:2px solid #6b7280; border-bottom:2px solid #6b7280; transform: rotate(-45deg); opacity:0; transition:transform .2s ease, opacity .2s ease; }
    .ft__list{ list-style:none; padding:0; margin:0; display:grid; gap:10px; }
    .ft__list a{ color:#0f172a; text-decoration:none; }
    .ft__list a:hover{ text-decoration:underline; }

    .ft__payments{ display:flex; gap:18px; align-items:center; flex-wrap:wrap; border-top:1px solid #e9eef6; margin-top:22px; padding-top:16px; }
    .ft__payments img{ height:26px; width:auto; }

    .ft__copy{ margin-top:16px; color:#6b7280; }

    /* === Móvil: centrar head y activar acordeones === */
    @media (max-width: 980px){
      .ft__wrap{ padding:22px 16px 28px; }
      .ft__head{ flex-direction:column; align-items:center; text-align:center; }
      .ft__brand{ flex-direction:column; }
      .ft__cta{ margin-top:10px; }
      .ft__grid{ grid-template-columns: 1fr; gap:0; border-top:1px solid #e9eef6; margin-top:10px; }
      .ft__col{ border-bottom:1px solid #e9eef6; padding:10px 0; }
      .ft__title{ padding:12px 4px; }
      .ft__chev{ opacity:1; }
      .ft__col:not(.open) .ft__list{ display:none; }
      .ft__col.open .ft__chev{ transform: rotate(45deg); }
      .ft__payments{ justify-content:center; }
      .ft__copy{ text-align:center; }
    }
  </style>
</head>
<body>

<header class="header">
  <!-- Mobile topbar -->
  <div class="mobile-topbar">
    <div class="m-left">
      <a href="{{ route('web.home') }}" class="m-brand" aria-label="Ir a inicio">
        <img class="m-logo" src="{{ asset('images/logo-mail.png') }}" alt="Jureto" onerror="this.style.opacity=.2">
      </a>
      <div class="m-icons">
        <a class="ico" href="https://facebook.com" target="_blank" aria-label="Facebook" rel="noopener">
          <svg viewBox="0 0 24 24"><path d="M15 3h-3a4 4 0 0 0-4 4v3H5v4h3v7h4v-7h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
        </a>
        <a class="ico" href="https://instagram.com" target="_blank" aria-label="Instagram" rel="noopener">
          <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><circle cx="17.5" cy="6.5" r="1"/></svg>
        </a>
      </div>
    </div>
    <button class="burger" id="burger" aria-label="Abrir menú">
      <svg viewBox="0 0 24 24"><path d="M3 6h18M3 12h18M3 18h18" stroke="#111" stroke-width="2" stroke-linecap="round"/></svg>
    </button>
  </div>

  <!-- Desktop navbar -->
  <div class="wrap navbar">
    <a href="{{ route('web.home') }}" class="brand">
      <img src="{{ asset('images/logo-mail.png') }}" alt="Jureto">
    </a>

    <nav class="nav-center" aria-label="Principal">
      <a href="{{ route('web.home') }}" class="nav-link {{ request()->routeIs('web.home') ? 'is-active' : '' }}">Inicio</a>
      <a href="{{ route('web.ventas.index') }}" class="nav-link {{ request()->routeIs('web.ventas.*') ? 'is-active' : '' }}">Ventas</a>
      <a href="{{ route('web.contacto') }}" class="nav-link {{ request()->routeIs('web.contacto') ? 'is-active' : '' }}">Contacto</a>
    </nav>

    <div class="right-tools">
      <a class="ico" href="https://facebook.com" target="_blank" aria-label="Facebook" rel="noopener">
        <svg viewBox="0 0 24 24"><path d="M15 3h-3a4 4 0 0 0-4 4v3H5v4h3v7h4v-7h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
      </a>
      <a class="ico" href="https://instagram.com" target="_blank" aria-label="Instagram" rel="noopener">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><circle cx="17.5" cy="6.5" r="1"/></svg>
      </a>
      <a href="{{ url('/login') }}" class="btn-pill">Sistema</a>
    </div>
  </div>
</header>

{{-- Backdrop + Bottom Sheet (móvil) --}}
<div class="sheet-backdrop" id="sheet-backdrop" hidden></div>
<section class="sheet" id="sheet" role="dialog" aria-modal="true" aria-label="Menú" tabindex="-1">
  <div class="sheet__drag"><div class="sheet__handle" aria-hidden="true"></div></div>
  <div class="sheet__content">
    <div class="sheet__grid">
      <nav class="sheet__nav" aria-label="Menú móvil">
        <a href="{{ route('web.home') }}">Inicio</a>
        <a href="{{ route('web.ventas.index') }}">Ventas</a>
        <a href="{{ route('web.contacto') }}">Contacto</a>
      </nav>
      <div class="sheet__footer">
        <div class="sheet__icons">
          <a class="ico" href="https://facebook.com" target="_blank" aria-label="Facebook" rel="noopener">
            <svg viewBox="0 0 24 24"><path d="M15 3h-3a4 4 0 0 0-4 4v3H5v4h3v7h4v-7h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
          </a>
          <a class="ico" href="https://instagram.com" target="_blank" aria-label="Instagram" rel="noopener">
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><circle cx="17.5" cy="6.5" r="1"/></svg>
          </a>
        </div>
        <a href="{{ url('/login') }}" class="btn-pill">Sistema</a>
      </div>
    </div>
  </div>
</section>

<script>
  // Bottom Sheet móvil
  (function(){
    const html = document.documentElement;
    const burger = document.getElementById('burger');
    const sheet = document.getElementById('sheet');
    const backdrop = document.getElementById('sheet-backdrop');
    let startY = 0, currentY = 0, dragging = false;

    function openSheet(){ html.classList.add('sheet-open'); backdrop.hidden = false; sheet.removeAttribute('inert'); sheet.focus(); }
    function closeSheet(){ html.classList.remove('sheet-open'); backdrop.hidden = true; sheet.setAttribute('inert',''); burger?.focus(); }

    burger?.addEventListener('click', openSheet);
    backdrop?.addEventListener('click', closeSheet);
    document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') closeSheet(); });

    sheet.addEventListener('touchstart', (e)=>{ if(e.touches.length !== 1) return; dragging = true; startY = e.touches[0].clientY; currentY = startY; }, {passive:true});
    sheet.addEventListener('touchmove', (e)=>{ if(!dragging) return; currentY = e.touches[0].clientY; const d = Math.max(0, currentY - startY); sheet.style.transform = `translateY(${d}px)`; }, {passive:true});
    sheet.addEventListener('touchend', ()=>{ if(!dragging) return; const d = Math.max(0, currentY - startY); dragging = false; sheet.style.transform = ''; if(d > 80) closeSheet(); });
    sheet.addEventListener('click', (e)=>{ const a = e.target.closest('a'); if(a && a.getAttribute('href')) closeSheet(); });

    sheet.setAttribute('inert','');
  })();

  // Footer acordeones (solo visible en móvil por CSS)
  (function(){
    const root = document.getElementById('ft-accordion');
    if(!root) return;
    root.querySelectorAll('[data-acc]').forEach(btn => {
      const col = btn.closest('.ft__col');
      btn.setAttribute('aria-expanded', 'false');
      btn.setAttribute('aria-controls', `${btn.textContent.trim().toLowerCase().replace(/\s+/g,'-')}-list`);
      const list = col.querySelector('.ft__list');
      list.id = btn.getAttribute('aria-controls');
      btn.addEventListener('click', () => {
        const isOpen = col.classList.toggle('open');
        btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        // cerrar otros
        root.querySelectorAll('.ft__col').forEach(other=>{
          if(other!==col){ other.classList.remove('open'); const b=other.querySelector('[data-acc]'); if(b) b.setAttribute('aria-expanded','false'); }
        });
      });
    });
  })();
</script>

<main class="container" style="padding:28px 20px;">
  @if(session('ok'))
    <div class="card" style="border-left:4px solid #e4ba16; margin-bottom:16px;">{{ session('ok') }}</div>
  @endif
  @yield('content')
</main>

<footer class="ft">
  <div class="ft__wrap">
    <!-- Cabezal: en móvil queda CENTRADO (logo + frase + botón) -->
    <div class="ft__head">
      <a href="{{ route('web.home') }}" class="ft__brand" aria-label="Jureto inicio">
        <img src="{{ asset('images/logo-mail.png') }}" alt="Jureto" class="ft__logo">
        <span class="ft__slogan">soluciones para tu espacio de trabajo.</span>
      </a>
      <a href="{{ route('customer.register') }}" class="ft__cta">Regístrate</a>
    </div>

    <hr class="ft__divider">

    <!-- Desktop: columnas / Móvil: acordeones -->
    <div class="ft__grid" id="ft-accordion">
      <section class="ft__col">
        <button class="ft__title" type="button" data-acc>Conócenos <span class="ft__chev"></span></button>
        <ul class="ft__list">
          <li><a href="{{ url('/nosotros') }}">¿Quiénes somos?</a></li>
          <li><a href="{{ url('/comentarios') }}">Comentarios</a></li>
        </ul>
      </section>

      <section class="ft__col">
        <button class="ft__title" type="button" data-acc>Servicios <span class="ft__chev"></span></button>
        <ul class="ft__list">
          <li><a href="{{ url('/servicios/pickup') }}">Pick Up Center</a></li>
          <li><a href="{{ url('/empresas') }}">Para empresas</a></li>
          <li><a href="{{ url('/proteccion') }}">Planes de protección</a></li>
          <li><a href="{{ url('/reciclaje') }}">Programa de reciclaje</a></li>
          <li><a href="{{ url('/imprime-gratis') }}">Imprime Gratis</a></li>
          <li><a href="{{ url('/club-precios') }}">Club de Precios</a></li>
        </ul>
      </section>

      <section class="ft__col">
        <button class="ft__title" type="button" data-acc>Principales <span class="ft__chev"></span></button>
        <ul class="ft__list">
          <li><a href="{{ url('/categoria/papeleria') }}">Artículos de Papelería</a></li>
          <li><a href="{{ url('/categoria/hojas') }}">Hojas para imprimir</a></li>
          <li><a href="{{ url('/categoria/hardware') }}">Hardware</a></li>
          <li><a href="{{ url('/categoria/laptops') }}">Computadoras Laptop</a></li>
          <li><a href="{{ url('/categoria/oficina') }}">Equipo de Cómputo para Oficina</a></li>
          <li><a href="{{ url('/categoria/desktop') }}">Computadoras de Escritorio</a></li>
          <li><a href="{{ url('/categoria/monitores') }}">Monitores</a></li>
          <li><a href="{{ url('/categoria/brother') }}">Impresoras Brother</a></li>
          <li><a href="{{ url('/categoria/epson') }}">Impresoras Epson</a></li>
          <li><a href="{{ url('/categoria/hp') }}">Tienda Oficial HP</a></li>
          <li><a href="{{ url('/categoria/productos-oficina') }}">Productos para Oficina</a></li>
          <li><a href="{{ url('/categoria/muebles') }}">Muebles para Oficina</a></li>
        </ul>
      </section>

      <section class="ft__col">
        <button class="ft__title" type="button" data-acc>Promociones <span class="ft__chev"></span></button>
        <ul class="ft__list">
          <li><a href="{{ url('/promos/hot-sale') }}">Hot Sale 2025</a></li>
          <li><a href="{{ url('/promos/buen-fin') }}">Buen Fin 2025</a></li>
          <li><a href="{{ url('/promos/cyberdays') }}">CyberDays</a></li>
        </ul>
      </section>

      <section class="ft__col">
        <button class="ft__title" type="button" data-acc>Políticas <span class="ft__chev"></span></button>
        <ul class="ft__list">
          <li><a href="{{ url('/politicas/envios') }}">Envíos, devoluciones y cancelaciones</a></li>
          <li><a href="{{ url('/politicas/terminos') }}">Términos y Condiciones</a></li>
          <li><a href="{{ url('/politicas/privacidad') }}">Aviso de Privacidad</a></li>
        </ul>
      </section>

      <section class="ft__col">
        <button class="ft__title" type="button" data-acc>Ayuda <span class="ft__chev"></span></button>
        <ul class="ft__list">
          <li><a href="{{ url('/ayuda/facturacion') }}">Facturación</a></li>
          <li><a href="{{ url('/ayuda/soporte') }}">Soporte Técnico</a></li>
          <li><a href="{{ url('/ayuda/pago') }}">Forma de Pago</a></li>
          <li><a href="{{ url('/ayuda/envios') }}">Forma de Envíos</a></li>
          <li><a href="{{ url('/ayuda/garantias') }}">Garantías & devoluciones</a></li>
          <li><a href="{{ url('/ayuda/elegir-computadora') }}">¿Cómo escoger mi computadora?</a></li>
        </ul>
      </section>
    </div>

    <div class="ft__payments">
      <img src="{{ asset('images/payments/paypal.svg') }}" alt="PayPal">
      <img src="{{ asset('images/payments/mercadopago.svg') }}" alt="Mercado Pago">
      <img src="{{ asset('images/payments/visa.svg') }}" alt="Visa">
      <img src="{{ asset('images/payments/mastercard.svg') }}" alt="Mastercard">
      <img src="{{ asset('images/payments/amex.svg') }}" alt="American Express">
    </div>

    <div class="ft__copy">
      <small>© {{ date('Y') }} Jureto — Todos los derechos reservados.</small>
    </div>
  </div>
</footer>

</body>
</html>
