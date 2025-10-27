<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>@yield('title','Jureto')</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  {{-- CSRF para formularios y fetch --}}
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <script>
    (function () {
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      window.csrf = token;
      window.csrfFetch = function(url, opts = {}) {
        const headers = Object.assign({'X-CSRF-TOKEN': token,'X-Requested-With':'XMLHttpRequest'}, opts.headers || {});
        return fetch(url, Object.assign({}, opts, { headers }));
      };
      if (window.axios) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
      }
    })();
  </script>

  <link href="{{ asset('css/app.css') }}" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  @stack('styles')

  <style>
    :root{
      --ink:#0f172a; --muted:#475569; --line:#e5e9f2; --bg:#ffffff;
      --pill:#b6332f; --pill-hover:#a02a27; --shadow:0 8px 24px rgba(2,8,23,.06);
      --container:1180px;
      --sheet-bg:#ffffff; --sheet-radius:20px; --sheet-shadow: 0 18px 60px rgba(2,8,23,.22);
      --backdrop: rgba(15,23,42,.38);
      --brand:#6ea8fe;

      --header-solid-bg:#ffffff;
      --header-glass-bg: rgba(255,255,255,.42);
      --header-glass-border: rgba(15,23,42,.05);
      --header-transition: background-color .25s ease, backdrop-filter .25s ease, box-shadow .25s ease, border-color .25s ease;
    }
    *{ box-sizing:border-box }
    html,body{ margin:0; padding:0 }
    body{ font-family: ui-sans-serif, system-ui, -apple-system; background:var(--bg); color:var(--ink); overflow-x:hidden }

    /* ===== Header base ===== */
    header.header{
      position:sticky; top:0; left:0; right:0; width:100%;
      background:var(--header-solid-bg);
      box-shadow:var(--shadow);
      z-index:40;
      border-bottom:1px solid transparent;
      transition: var(--header-transition);
    }
    header.header.header--glass{
      background:var(--header-glass-bg);
      backdrop-filter: saturate(120%) blur(8px);
      -webkit-backdrop-filter: saturate(120%) blur(8px);
      border-bottom-color: var(--header-glass-border);
      box-shadow: 0 10px 28px rgba(2,8,23,.10);
    }
    @media (prefers-reduced-transparency: reduce) {
      header.header.header--glass{ backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px); }
    }

    .wrap{ max-width:var(--container); margin:0 auto; padding:14px 20px }
    .navbar{ display:flex; align-items:center; gap:18px }
    .brand{ display:flex; align-items:center; gap:12px; white-space:nowrap; text-decoration:none; color:var(--ink) }
    .brand img{ height:34px; display:block }

    .nav-center{ display:flex; justify-content:center; align-items:center; gap:32px; }
    .nav-link{ position:relative; text-decoration:none; color:var(--ink); font-weight:700; padding:8px 4px; display:inline-flex; align-items:center }
    .nav-link::after{ content:""; position:absolute; left:0; right:0; bottom:-6px; height:3px; border-radius:3px; background:transparent; transform:scaleX(0); transition:transform .18s ease, background .18s ease }
    .nav-link:hover::after{ background:#000; transform:scaleX(1) }
    .nav-link.is-active::after{ background:#000; transform:scaleX(1) }

    .right-tools{ display:flex; align-items:center; gap:12px }
    .icon-btn{
      position:relative; display:inline-flex; align-items:center; justify-content:center;
      width:36px; height:36px; border-radius:999px; border:1px solid #dfe6ee; background:#fff;
      transition:transform .08s, box-shadow .2s;
    }
    .icon-btn:hover{transform:translateY(-1px); box-shadow:0 6px 18px rgba(15,23,42,.08)}
    .icon-btn svg{width:20px;height:20px; stroke:#111; fill:none; stroke-width:2}

    .cart-badge{
      position:absolute; top:-8px; right:-10px;
      min-width:18px; height:18px; line-height:18px;
      text-align:center; font-size:.72rem; background:#ef4444; color:#fff; border-radius:999px; padding:0 6px;
      box-shadow:0 2px 8px rgba(239,68,68,.25);
    }

    .btn-pill{ appearance:none; border:0; border-radius:999px; padding:12px 24px; font-weight:700; background:var(--pill); color:#fff; cursor:pointer; transition:background .2s, transform .1s }
    .btn-pill:hover{ background:var(--pill-hover); transform: translateY(-1px) }

    /* ===== Mobile topbar ===== */
    .mobile-topbar{ display:none; align-items:center; justify-content:space-between; max-width:var(--container); margin:0 auto; padding:10px 16px }
    .m-brand{ display:flex; align-items:center; gap:8px; text-decoration:none; color:var(--ink) }
    .m-logo{ height:26px; width:auto; display:block }
    .m-right{ display:flex; align-items:center; gap:10px } /* NUEVO: grupo carrito + burger a la derecha */
    .burger{ display:none; background:transparent; border:0; padding:6px }
    .burger svg{ width:24px; height:24px }

    @media (max-width: 1180px){
      .wrap.navbar{ padding:12px 16px }
    }
    @media (max-width: 980px){
      .wrap.navbar{ display:none }
      .mobile-topbar{ display:flex }
      .nav-center{ display:none }
      .burger{ display:inline-flex }
    }

    /* ===== Bottom Sheet (móvil) ===== */
    .sheet-backdrop{ position:fixed; inset:0; background:var(--backdrop); opacity:0; pointer-events:none; transition:opacity .2s ease; backdrop-filter: blur(2px); z-index:49 }
    .sheet{ position:fixed; left:0; right:0; bottom:0; z-index:50; transform: translateY(100%); background:var(--sheet-bg); border-top-left-radius: var(--sheet-radius); border-top-right-radius: var(--sheet-radius); box-shadow: var(--sheet-shadow); transition: transform .26s ease; will-change: transform; touch-action: none }
    .sheet__drag{ display:flex; justify-content:center; padding-top:10px }
    .sheet__handle{ width:48px; height:5px; border-radius:999px; background:#d1d5db }
    .sheet__content{ padding:14px 18px 18px }
    .sheet__grid{ display:grid; gap:16px }
    .sheet__nav a{ display:block; text-decoration:none; color:var(--ink); font-weight:800; font-size:1.05rem; padding:10px 6px; border-bottom:1px solid var(--line) }
    .sheet__footer{ display:flex; align-items:center; justify-content:space-between; gap:12px }
    .sheet__icons{ display:flex; align-items:center; gap:12px }
    .sheet-open .sheet{ transform: translateY(0) }
    .sheet-open .sheet-backdrop{ opacity:1; pointer-events:auto }
    @media (max-height: 700px){ .sheet{ max-height: 86vh; overflow:auto } }

    /* ===== Footer ===== */
    .ft{ background:#fff; border-top:1px solid #e9eef6; margin-top:30px }
    .ft__wrap{ max-width:1180px; margin:0 auto; padding:24px 20px 36px; display:flex; flex-direction:column; align-items:center; }
    .ft__head{ width:100%; display:flex; flex-direction:column; align-items:center; text-align:center; gap:16px; }
    .ft__brand{ display:flex; align-items:center; gap:12px; text-decoration:none; color:#0f172a }
    .ft__logo{ height:38px; width:auto; display:block } /* desktop */
    .ft__slogan{ font-size:.95rem; color:#6b7280; max-width:720px }
    .ft__divider{ border:0; border-top:1px solid #e9eef6; margin:18px 0 12px }

    .ft__grid{ display:grid; gap:24px; grid-template-columns: repeat(4, minmax(220px,260px)); justify-content:center }
    .ft__title{ background:transparent; border:0; padding:0; margin:0 0 10px 0; font-weight:800; color:#0f172a; font-size:1rem; display:flex; align-items:center; justify-content:space-between; width:100% }
    .ft__chev{ width:16px; height:16px; border-right:2px solid #6b7280; border-bottom:2px solid #6b7280; transform: rotate(-45deg); opacity:0; transition:transform .2s, opacity .2s }
    .ft__list{ list-style:none; padding:0; margin:0; display:grid; gap:10px }
    .ft__list a{ color:#0f172a; text-decoration:none }
    .ft__list a:hover{ text-decoration:underline }

    .ft__payments{ display:flex; gap:18px; align-items:center; flex-wrap:wrap; border-top:1px solid #e9eef6; margin-top:22px; padding-top:16px; justify-content:center }
    .ft__payments img{ height:26px; width:auto }
    .ft__copy{ margin-top:16px; color:#6b7280 }

    .ft__social{ display:flex; gap:14px; align-items:center; margin-top:8px }
    .ft__social a{ color:inherit; opacity:.9; transition:opacity .2s, transform .1s }
    .ft__social a:hover{ opacity:1; transform:translateY(-1px) }

    /* Solo móvil: logo del footer más compacto y centrado */
    @media (max-width: 980px){
      .ft__wrap{ padding:22px 16px 28px; align-items:stretch }
      .ft__head{ align-items:flex-start; text-align:left }
      .ft__grid{ grid-template-columns: 1fr; gap:0; border-top:1px solid #e9eef6; margin-top:10px }
      .ft__col{ border-bottom:1px solid #e9eef6; padding:10px 0 }
      .ft__title{ padding:12px 4px; cursor:pointer; }
      .ft__chev{ opacity:1 }
      .ft__col:not(.open) .ft__list{ display:none }
      .ft__col.open .ft__list{ display:grid }
      .ft__col.open .ft__chev{ transform: rotate(45deg); }
      .ft__payments{ justify-content:center }
      .ft__copy{ text-align:center }
      .ft__logo{ height:28px; max-width:180px; margin:0 auto 8px; display:block } /* <= ajuste pedido */
    }

    /* ===== Search & user (existente) ===== */
    .searchbar-wrap{ position:relative; flex:1; max-width:720px; }
    .searchbar{
      display:flex; align-items:center; gap:10px;
      background:#fff; border:1px solid var(--line); border-radius:999px;
      padding:10px 14px; box-shadow:0 8px 22px rgba(2,8,23,.06);
    }
    .searchbar .s-ico{width:20px;height:20px;display:inline-flex}
    .searchbar input{ flex:1; border:0; outline:0; background:transparent; font-size:1rem; color:var(--ink); }
    .searchbar .vdiv{width:1px; height:22px; background:#d9e0ec}
    .searchbar .chip{ display:inline-flex; align-items:center; justify-content:center; font-weight:800; font-size:.9rem; color:#2f4fb8; border:2px solid #2f4fb8; border-radius:999px; width:34px; height:34px; }

    .user-wrap{position:relative}
    .avatar-btn{
      width:38px;height:38px;border-radius:999px;border:2px solid #dfe6ee;background:#eef2ff;
      color:#2f3e7d; font-weight:900; display:inline-flex; align-items:center; justify-content:center;
      cursor:pointer;
    }
    .user-menu{
      position:absolute; right:0; top:48px; min-width:220px; background:#fff; border:1px solid var(--line);
      border-radius:14px; box-shadow:0 18px 46px rgba(2,8,23,.18); padding:8px; display:none; z-index:60;
    }
    .user-menu.open{display:block}
    .user-menu a, .user-menu form button{
      display:flex; align-items:center; gap:10px; width:100%; text-align:left;
      background:#fff; border:0; padding:10px 10px; border-radius:10px; cursor:pointer;
      color:var(--ink); text-decoration:none; font-weight:700;
    }
    .user-menu a:hover, .user-menu form button:hover{background:#f7f9fe}

    html.sheet-open{ overflow:hidden; }
  </style>
</head>
<body>

<header class="header">
  {{-- Topbar móvil --}}
  <div class="mobile-topbar">
    <!-- Izquierda: logo -->
    <a href="{{ route('web.home') }}" class="m-brand" aria-label="Ir a inicio">
      <img class="m-logo" src="{{ asset('images/logo-mail.png') }}" alt="Jureto" onerror="this.style.opacity=.2">
    </a>
    <!-- Derecha: carrito + burger (NUEVO ORDEN) -->
    <div class="m-right">
      @php
        $cart = session('cart', []);
        $cartCount = is_array($cart) ? array_sum(array_map(fn($r)=> (int)($r['qty'] ?? 0), $cart)) : 0;
      @endphp
      <a class="icon-btn" href="{{ route('web.cart.index') }}" aria-label="Carrito">
        <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39A2 2 0 0 0 9.63 16h7.52a2 2 0 0 0 2-.79L23 12H6"/></svg>
        <span class="cart-badge" data-cart-badge>{{ $cartCount }}</span>
      </a>
      <button class="burger" id="burger" aria-label="Abrir menú">
        <svg viewBox="0 0 24 24"><path d="M3 6h18M3 12h18M3 18h18" stroke="#111" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
  </div>

  {{-- Navbar desktop (SIN CAMBIOS) --}}
  @php
    $cart = session('cart', []);
    $cartCount = is_array($cart) ? array_sum(array_map(fn($r)=> (int)($r['qty'] ?? 0), $cart)) : 0;
    $user = Auth::user();
    $initial = $user ? mb_substr($user->name ?? ($user->email ?? 'U'), 0, 1, 'UTF-8') : null;
  @endphp

  <div class="wrap navbar">
    <a href="{{ route('web.home') }}" class="brand">
      <img src="{{ asset('images/logo-mail.png') }}" alt="Jureto">
    </a>

    <nav class="nav-center" aria-label="Principal">
      <a href="{{ route('web.home') }}" class="nav-link {{ request()->routeIs('web.home') ? 'is-active' : '' }}">Inicio</a>
      <a href="{{ route('web.ofertas') }}" class="nav-link {{ request()->routeIs('web.ofertas.*') ? 'is-active' : '' }}">Ofertas</a>
      <a href="{{ url('/servicios') }}" class="nav-link {{ request()->is('servicios') ? 'is-active' : '' }}">Servicios</a>
    </nav>

    <div class="searchbar-wrap" id="searchWrap">
      <form class="searchbar" action="{{ route('search.index') }}" method="get" role="search" aria-label="Buscar" id="searchForm">
        <span class="s-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg>
        </span>
        <input type="search" name="q" id="qInput" value="{{ request('q') }}" placeholder="¿Qué quieres encontrar?" autocomplete="off" aria-autocomplete="list" aria-controls="suggList">
        <div class="vdiv" aria-hidden="true"></div>
        <span class="chip" title="Asistente">AI</span>
      </form>

      <div class="sugg" id="sugg" hidden>
        <div id="suggItems"></div>
      </div>
    </div>

    <div class="right-tools">
      <a class="icon-btn" href="{{ url('/favoritos') }}" title="Favoritos" aria-label="Favoritos">
        <svg viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 22l7.8-8.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
      </a>
      <a class="icon-btn" href="{{ url('/ayuda') }}" title="Ayuda" aria-label="Ayuda">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9 9a3 3 0 1 1 4.5 2.6c-.9.5-1.5 1.4-1.5 2.4v1"/><circle cx="12" cy="18" r="1"/></svg>
      </a>
      <a class="icon-btn" href="{{ route('web.cart.index') }}" title="Carrito" aria-label="Carrito">
        <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39A2 2 0 0 0 9.63 16h7.52a2 2 0 0 0 2-.79L23 12H6"/></svg>
        <span class="cart-badge" data-cart-badge>{{ $cartCount }}</span>
      </a>

      @if($user)
        <div class="user-wrap">
          <button type="button" class="avatar-btn" id="avatarBtn" aria-haspopup="menu" aria-expanded="false" title="{{ $user->name ?? 'Mi cuenta' }}">
            {{ strtoupper($initial) }}
          </button>
          <div class="user-menu" id="userMenu" role="menu" aria-label="Menú de usuario">
            <div style="padding:8px 10px 4px">
              <div style="font-weight:900">{{ $user->name ?? 'Mi cuenta' }}</div>
              <small>{{ $user->email }}</small>
            </div>
            <a href="{{ route('customer.welcome') }}" role="menuitem">
              <svg viewBox="0 0 24 24" style="width:18px;height:18px"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              Mi perfil
            </a>
            <a href="{{ route('web.cart.index') }}" role="menuitem">
              <svg viewBox="0 0 24 24" style="width:18px;height:18px"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39A2 2 0 0 0 9.63 16h7.52a2 2 0 0 0 2-.79L23 12H6"/></svg>
              Mis pedidos
            </a>
            <form method="POST" action="{{ route('logout') }}" role="none">
              @csrf
              <button type="submit" role="menuitem">
                <svg viewBox="0 0 24 24" style="width:18px;height:18px"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y1="12"/></svg>
                Cerrar sesión
              </button>
            </form>
          </div>
        </div>
      @else
        <a href="{{ route('login') }}" class="btn-pill">Ingresar</a>
      @endif
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
        <a href="{{ route('web.ofertas') }}">Ofertas</a>
        <a href="{{ route('web.ventas.index') }}">Ventas</a>
        <a href="{{ route('web.contacto') }}">Contacto</a>
        <a href="{{ route('favoritos.index') }}">Favoritos</a>
      </nav>
      <div class="sheet__footer">
        <div class="sheet__icons">
          <a class="icon-btn" href="https://facebook.com" target="_blank" aria-label="Facebook" rel="noopener">
            <svg viewBox="0 0 24 24"><path d="M15 3h-3a4 4 0 0 0-4 4v3H5v4h3v7h4v-7h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
          </a>
          <a class="icon-btn" href="https://instagram.com" target="_blank" aria-label="Instagram" rel="noopener">
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><circle cx="17.5" cy="6.5" r="1"/></svg>
          </a>
        </div>
        @auth
          <a href="{{ route('customer.welcome') }}" class="btn-pill">Mi cuenta</a>
        @else
          <a href="{{ route('login') }}" class="btn-pill">Ingresar</a>
        @endauth
      </div>
    </div>
  </div>
</section>

<main class="container" style="padding:28px 20px;">

  @yield('content')
</main>

<!-- ===== FOOTER CENTRADO ===== -->
<footer class="ft">
  <div class="ft__wrap">
    <img src="{{ asset('images/logo-mail.png') }}" alt="Jureto" class="ft__logo">
    <div class="ft__head">
      <div>
        <a href="{{ route('web.home') }}" class="ft__brand" aria-label="Jureto inicio" style="gap:14px;">
          <span class="ft__slogan">
            Jureto es el aliado B2B para equipar oficinas y dependencias públicas con soluciones integrales:
            papelería, cómputo, y muebles. Sin fricción, sin complicaciones.
          </span>
        </a>
      </div>
    </div>

    <hr class="ft__divider">

    <div class="ft__grid" id="ft-accordion">
      <section class="ft__col open">
        <button class="ft__title" type="button" data-acc>Conócenos <span class="ft__chev"></span></button>
        <ul class="ft__list" id="ft-about">
          <li><a href="{{ url('/sobre-nosotros') }}">¿Quiénes somos?</a></li>
          <li><a href="{{ url('/comentarios') }}">Comentarios</a></li>
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
        <button class="ft__title" type="button" data-acc>Políticas <span class="ft__chev"></span></button>
        <ul class="ft__list">
          <li><a href="{{ url('/envios-devoluciones-cancelaciones') }}">Envíos, devoluciones y cancelaciones</a></li>
          <li><a href="{{ url('/terminos-y-condiciones') }}">Términos y Condiciones</a></li>
          <li><a href="{{ url('/aviso-de-privacidad') }}">Aviso de Privacidad</a></li>
        </ul>
      </section>

      <section class="ft__col">
        <button class="ft__title" type="button" data-acc>Ayuda <span class="ft__chev"></span></button>
        <ul class="ft__list">
          <li><a href="{{ url('/preguntas-frecuentes') }}">Preguntas frecuentes</a></li>
          <li><a href="{{ url('/contacto') }}">Contacto</a></li>
          <li><a href="{{ url('/formas-de-pago') }}">Formas de Pago</a></li>
          <li><a href="{{ url('/formas-de-envio') }}">Formas de Envío</a></li>
          <li><a href="{{ url('/garantias-y-devoluciones') }}">Garantías & devoluciones</a></li>
        </ul>
      </section>
    </div>

    <div class="ft__payments">
      <img src="{{ asset('images/payments/visa.png') }}" alt="Visa">
      <img src="{{ asset('images/payments/mastercard.jpg') }}" alt="Mastercard">
      <img src="{{ asset('images/payments/amex.png') }}" alt="American Express">
    </div>

    <div class="ft__copy" style="margin-top:18px;display:flex;justify-content:center;flex-wrap:wrap;align-items:center;gap:16px;text-align:center;">
      <small>© {{ date('Y') }} Jureto — Todos los derechos reservados.</small>
      <div style="display:flex; gap:16px; align-items:center;">
        <a href="https://x.com" target="_blank" aria-label="X / Twitter" style="color:inherit;"><i class="fa-brands fa-x-twitter"></i></a>
        <a href="https://discord.com" target="_blank" aria-label="Discord" style="color:inherit;"><i class="fa-brands fa-discord"></i></a>
        <a href="https://linkedin.com" target="_blank" aria-label="LinkedIn" style="color:inherit;"><i class="fa-brands fa-linkedin-in"></i></a>
        <a href="https://reddit.com" target="_blank" aria-label="Reddit" style="color:inherit;"><i class="fa-brands fa-reddit-alien"></i></a>
      </div>
    </div>
  </div>
</footer>

{{-- ===== Scripts ===== --}}
<script>
  (function(){
    const header = document.querySelector('header.header');
    if(!header) return;
    const THRESHOLD = 32;
    function applyGlass(){
      if(window.scrollY > THRESHOLD){ header.classList.add('header--glass'); }
      else{ header.classList.remove('header--glass'); }
    }
    applyGlass();
    window.addEventListener('scroll', applyGlass, { passive:true });
    window.addEventListener('resize', applyGlass);
    window.addEventListener('pageshow', applyGlass);
  })();

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

  (function(){
    const btn = document.getElementById('avatarBtn');
    const menu = document.getElementById('userMenu');
    if(!btn || !menu) return;
    function toggle(open){
      if(open===undefined) open = !menu.classList.contains('open');
      menu.classList.toggle('open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    btn.addEventListener('click', ()=> toggle());
    document.addEventListener('click', (e)=>{
      if(!menu.classList.contains('open')) return;
      if(!e.target.closest('.user-wrap')) toggle(false);
    });
    document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') toggle(false); });
  })();

  (function(){
    const input = document.getElementById('qInput');
    const panel = document.getElementById('sugg');
    const list  = document.getElementById('suggItems');
    const form  = document.getElementById('searchForm');
    if(!input || !panel || !list) return;

    let timer = null;
    const SUGG_URL = @json(route('search.suggest'));

    function hide(){ panel.hidden = true; list.innerHTML = ''; }
    function show(){ panel.hidden = false; }

    input.addEventListener('input', ()=>{
      const q = input.value.trim();
      if(timer) clearTimeout(timer);
      if(q.length < 2){ hide(); return; }
      timer = setTimeout(async ()=>{
        try{
          const url = new URL(SUGG_URL, window.location.origin);
          url.searchParams.set('term', q);
          const res = await fetch(url.toString(), { headers: { 'Accept':'application/json' } });
          const data = await res.json();
          const terms = Array.isArray(data.terms) ? data.terms : [];
          const products = Array.isArray(data.products) ? data.products : [];
          let html = '';
          if(terms.length){
            html += terms.slice(0,6).map(t => `
              <div class="sugg-item" role="option" onclick="(function(){document.getElementById('qInput').value=${JSON.stringify(t)}; document.getElementById('sugg').hidden=true; document.getElementById('searchForm').submit();})()">
                <svg viewBox="0 0 24 24" style="width:18px;height:18px;stroke:#111;fill:none;stroke-width:2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg>
                <span>${t}</span>
              </div>
            `).join('');
          }
          if(products.length){
            html += `<div class="sugg-item" style="cursor:default;opacity:.65"><small>Productos</small></div>`;
            html += products.slice(0,5).map(p => `
              <a class="sugg-item" href="{{ url('/producto') }}/${p.id}">
                <svg viewBox="0 0 24 24" style="width:18px;height:18px;stroke:#111;fill:none;stroke-width:2"><path d="M20 7H4"/><path d="M6 7v13a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V7"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/></svg>
                <span>${p.name.replace(/</g,'&lt;')}</span>
              </a>
            `).join('');
          }
          if(!html) html = `<div class="sugg-empty">Sin sugerencias</div>`;
          list.innerHTML = html;
          show();
        }catch(_){}
      }, 180);
    });

    input.addEventListener('focus', ()=>{ if(list.children.length) panel.hidden = false; });
    document.addEventListener('click', (e)=>{ if(!e.target.closest('#searchWrap')) panel.hidden = true; });
    document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') panel.hidden = true; });
  })();

  (function(){
    function initFooterAccordion(){
      const root = document.getElementById('ft-accordion');
      if(!root) return;

      root.querySelectorAll('.ft__col').forEach((col, i) => {
        const btn  = col.querySelector('[data-acc]');
        const list = col.querySelector('.ft__list');
        if(!btn || !list) return;
        if(!list.id) list.id = 'ft-list-' + i;
        btn.setAttribute('aria-controls', list.id);
        btn.setAttribute('aria-expanded', col.classList.contains('open') ? 'true' : 'false');
      });

      root.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-acc]');
        if(!btn || !root.contains(btn)) return;

        const col    = btn.closest('.ft__col');
        const isOpen = col.classList.contains('open');

        if (window.matchMedia('(max-width:980px)').matches) {
          root.querySelectorAll('.ft__col.open').forEach(c => {
            if(c !== col){
              c.classList.remove('open');
              const b = c.querySelector('[data-acc]');
              if(b) b.setAttribute('aria-expanded','false');
            }
          });
        }

        col.classList.toggle('open', !isOpen);
        btn.setAttribute('aria-expanded', !isOpen ? 'true' : 'false');
      });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initFooterAccordion);
    } else {
      initFooterAccordion();
    }
    document.addEventListener('turbo:load', initFooterAccordion);
    document.addEventListener('livewire:load', initFooterAccordion);
  })();

  window.updateCartBadge = function(count){
    const el = document.querySelector('[data-cart-badge]');
    if (!el) return;
    el.textContent = String(count||0);
  };
</script>
<!-- ========== TOAST + AJAX CART (pegar antes de </body>) ========== -->
<style>
  #toaststack{position:fixed;top:14px;right:14px;z-index:9999;display:flex;flex-direction:column;gap:10px}
  .toast2{
    background:#fff;color:#111827;border:1px solid #e6eaf2;border-radius:14px;
    box-shadow:0 16px 40px rgba(2,8,23,.08);
    padding:12px 14px;min-width:280px;max-width:360px;
    display:grid;grid-template-columns:28px 1fr auto;gap:10px;
    animation:toast2in .2s ease-out both
  }
  .toast2__ring{width:26px;height:26px;border-radius:999px;border:3px solid #d1e7dd;box-sizing:border-box}
  .toast2--success .toast2__ring{border-color:#86efac}
  .toast2--warning .toast2__ring{border-color:#fcd34d}
  .toast2--info    .toast2__ring{border-color:#93c5fd}
  .toast2__title{font-weight:800;margin-top:2px}
  .toast2__msg{font-size:.92rem;color:#334155}
  .toast2__close{border:0;background:transparent;width:30px;height:30px;border-radius:10px;cursor:pointer}
  .toast2__close:hover{background:#f3f4f6}
  .toast2__bar{grid-column:1/-1;height:3px;border-radius:999px;background:#34d399;transform-origin:left;animation:toast2bar var(--dur,3200ms) linear forwards}
  .toast2--warning .toast2__bar{background:#f59e0b}
  .toast2--info .toast2__bar{background:#60a5fa}
  @keyframes toast2in{from{transform:translateY(-6px);opacity:0}to{transform:translateY(0);opacity:1}}
  @keyframes toast2out{to{transform:translateY(-6px);opacity:0}}
  @keyframes toast2bar{from{transform:scaleX(1)}to{transform:scaleX(0)}}
</style>

<div id="toaststack" aria-live="polite" aria-atomic="true"></div>

<script>
(function(){
  // ===== Toast minimal tipo "Agregado"
  const stack = document.getElementById('toaststack');
  window.showToast = function({title='Agregado', message='', kind='success', duration=3200}={}){
    const el = document.createElement('div');
    el.className = `toast2 toast2--${kind}`;
    el.style.setProperty('--dur', duration+'ms');
    el.innerHTML = `
      <div class="toast2__ring" aria-hidden="true"></div>
      <div>
        <div class="toast2__title">${title}</div>
        ${message ? `<div class="toast2__msg">${message}</div>` : ''}
      </div>
      <button class="toast2__close" type="button" aria-label="Cerrar">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="#0f172a" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
      <div class="toast2__bar"></div>
    `;
    stack.appendChild(el);
    const close = () => { el.style.animation = 'toast2out .18s ease-in forwards'; setTimeout(()=> el.remove(), 160); };
    const timer = setTimeout(close, duration);
    el.querySelector('.toast2__close').addEventListener('click', ()=>{ clearTimeout(timer); close(); });
  };

  // ===== Actualiza TODAS las badgets de carrito
  window.updateCartBadge = function(count){
    document.querySelectorAll('[data-cart-badge]').forEach(b=> b.textContent = String(count||0));
  };

  // ===== Interceptar "Agregar al carrito" SIN recargar
  const RUTA_ADD = @json(route('web.cart.add'));
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

  function esFormCartAdd(form){
    try{
      const a = new URL(form.getAttribute('action') || form.action, location.origin).href;
      const b = new URL(RUTA_ADD, location.origin).href;
      return a === b;
    }catch(_){ return false; }
  }

  // Delegación global: vale para TODAS las vistas y para formularios dinámicos
  document.addEventListener('submit', async (e)=>{
    const form = e.target;
    if(!(form instanceof HTMLFormElement)) return;
    if(!esFormCartAdd(form)) return;

    e.preventDefault(); // <- evita recarga

    try{
      const fd = new FormData(form);
      const res = await fetch(form.action, {
        method:'POST',
        headers:{ 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF },
        body: fd,
        credentials:'same-origin'
      });

      // Si el backend redirige (302) por algo, intenta leer JSON igualmente
      const data = await res.json().catch(()=> ({}));

      if(!res.ok || !data.ok){
        const msg = (data && (data.msg || data.message)) || 'No se pudo agregar.';
        throw new Error(msg);
      }

      // OK: actualiza badge y muestra toast
      window.updateCartBadge(data?.totals?.count || 0);
      window.showToast({ title:'Agregado', message:'El producto se añadió al carrito.', kind:'success', duration:3000 });
    }catch(err){
      window.showToast({ title:'Ups', message: String(err.message||'Error inesperado'), kind:'warning', duration:3500 });
      console.error('Cart add error:', err);
    }
  }, true); // useCapture para interceptar antes de que algo más lo procese

  // Si llega un flash desde el servidor, muéstralo como toast (opcional)
  @if(session('ok'))
    window.addEventListener('DOMContentLoaded', ()=>{
      window.showToast({ title:'Aviso', message: @json(session('ok')), kind:'info', duration:3000 });
    });
  @endif
})();
</script>
<!-- ========== /TOAST + AJAX CART ========== -->

@stack('scripts')

</body>
</html>
