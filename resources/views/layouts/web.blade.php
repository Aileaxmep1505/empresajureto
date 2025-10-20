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
  @stack('styles')

  <style>
    :root{
      --ink:#0f172a; --muted:#475569; --line:#e5e9f2; --bg:#ffffff;
      --pill:#b6332f; --pill-hover:#a02a27; --shadow:0 8px 24px rgba(2,8,23,.06);
      --container:1180px;
      --sheet-bg:#ffffff; --sheet-radius:20px; --sheet-shadow: 0 18px 60px rgba(2,8,23,.22);
      --backdrop: rgba(15,23,42,.38);
      --brand:#6ea8fe;
    }
    *{ box-sizing:border-box }
    html,body{ margin:0; padding:0 }
    body{ font-family: ui-sans-serif, system-ui, -apple-system; background:var(--bg); color:var(--ink); overflow-x:hidden }

    /* ===== Header base ===== */
    header.header{ position:sticky; top:0; left:0; right:0; width:100%; background:#fff; box-shadow:var(--shadow); z-index:40 }
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
    .m-left{ display:flex; align-items:center; gap:12px }
    .m-brand{ display:flex; align-items:center; gap:8px; text-decoration:none; color:var(--ink) }
    .m-logo{ height:26px; width:auto; display:block }
    .m-icons{ display:flex; align-items:center; gap:10px; margin-left:8px }
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
    .sheet__icons .ico svg{ stroke:#111 }
    .sheet-open .sheet{ transform: translateY(0) }
    .sheet-open .sheet-backdrop{ opacity:1; pointer-events:auto }
    @media (max-height: 700px){ .sheet{ max-height: 86vh; overflow:auto } }

    /* ===== Footer ===== */
    .ft{ background:#fff; border-top:1px solid #e9eef6; margin-top:30px }
    .ft__wrap{ max-width:1180px; margin:0 auto; padding:24px 20px 36px }
    .ft__head{ display:flex; align-items:center; justify-content:space-between; gap:16px }
    .ft__brand{ display:flex; align-items:center; gap:12px; text-decoration:none; color:#0f172a }
    .ft__logo{ height:38px; width:auto; display:block }
    .ft__slogan{ font-size:.95rem; color:#6b7280 }
    .ft__cta{ display:inline-flex; align-items:center; justify-content:center; background:#3b5bcc; color:#fff; font-weight:800; text-decoration:none; padding:12px 22px; border-radius:999px; box-shadow:0 10px 28px rgba(59,91,204,.22); transition:transform .12s, box-shadow .2s, background .2s }
    .ft__cta:hover{ transform:translateY(-1px); background:#4b69d6 }
    .ft__divider{ border:0; border-top:1px solid #e9eef6; margin:18px 0 12px }

    .ft__grid{ display:grid; gap:24px; grid-template-columns: repeat(6, minmax(160px,1fr)) }
    .ft__title{ background:transparent; border:0; padding:0; margin:0 0 10px 0; font-weight:800; color:#0f172a; font-size:1rem; display:flex; align-items:center; justify-content:space-between; width:100% }
    .ft__chev{ width:16px; height:16px; border-right:2px solid #6b7280; border-bottom:2px solid #6b7280; transform: rotate(-45deg); opacity:0; transition:transform .2s, opacity .2s }
    .ft__list{ list-style:none; padding:0; margin:0; display:grid; gap:10px }
    .ft__list a{ color:#0f172a; text-decoration:none }
    .ft__list a:hover{ text-decoration:underline }

    .ft__payments{ display:flex; gap:18px; align-items:center; flex-wrap:wrap; border-top:1px solid #e9eef6; margin-top:22px; padding-top:16px }
    .ft__payments img{ height:26px; width:auto }
    .ft__copy{ margin-top:16px; color:#6b7280 }

    @media (max-width: 980px){
      .ft__wrap{ padding:22px 16px 28px }
      .ft__head{ flex-direction:column; align-items:center; text-align:center }
      .ft__brand{ flex-direction:column }
      .ft__cta{ margin-top:10px }
      .ft__grid{ grid-template-columns: 1fr; gap:0; border-top:1px solid #e9eef6; margin-top:10px }
      .ft__col{ border-bottom:1px solid #e9eef6; padding:10px 0 }
      .ft__title{ padding:12px 4px }
      .ft__chev{ opacity:1 }
      .ft__col:not(.open) .ft__list{ display:none }
      .ft__payments{ justify-content:center }
      .ft__copy{ text-align:center }
    }

    /* ===== Search pill + sugerencias + avatar ===== */
    .searchbar-wrap{ position:relative; flex:1; max-width:720px; }
    .searchbar{
      display:flex; align-items:center; gap:10px;
      background:#fff; border:1px solid var(--line); border-radius:999px;
      padding:10px 14px; box-shadow:0 8px 22px rgba(2,8,23,.06);
    }
    .searchbar .s-ico{width:20px;height:20px;display:inline-flex}
    .searchbar input{
      flex:1; border:0; outline:0; background:transparent;
      font-size:1rem; color:var(--ink);
    }
    .searchbar .vdiv{width:1px; height:22px; background:#d9e0ec}
    .searchbar .chip{
      display:inline-flex; align-items:center; justify-content:center;
      font-weight:800; font-size:.9rem; color:#2f4fb8;
      border:2px solid #2f4fb8; border-radius:999px; width:34px; height:34px;
    }
    .sugg{
      position:absolute; left:0; right:0; top:calc(100% + 8px);
      background:#fff; border:1px solid var(--line); border-radius:14px;
      box-shadow:0 18px 46px rgba(2,8,23,.12); padding:6px; z-index:55;
    }
    .sugg-item{
      display:flex; align-items:center; gap:8px; padding:10px 12px; border-radius:10px; cursor:pointer; text-decoration:none; color:var(--ink);
    }
    .sugg-item:hover{ background:#f7f9fe }
    .sugg-empty{ color:var(--muted); padding:8px 12px }

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
    .user-menu small{color:var(--muted); font-weight:600}

    @media (max-width:980px){
      .searchbar-wrap{max-width:none}
    }
  </style>
</head>
<body>

<header class="header">
  {{-- Topbar móvil --}}
  <div class="mobile-topbar">
    <div class="m-left">
      <a href="{{ route('web.home') }}" class="m-brand" aria-label="Ir a inicio">
        <img class="m-logo" src="{{ asset('images/logo-mail.png') }}" alt="Jureto" onerror="this.style.opacity=.2">
      </a>
      <div class="m-icons">
        @php
          $cart = session('cart', []);
          $cartCount = is_array($cart) ? array_sum(array_map(fn($r)=> (int)($r['qty'] ?? 0), $cart)) : 0;
        @endphp
        <a class="icon-btn" href="{{ route('web.cart.index') }}" aria-label="Carrito">
          <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39A2 2 0 0 0 9.63 16h7.52a2 2 0 0 0 2-.79L23 12H6"/></svg>
          <span class="cart-badge" data-cart-badge>{{ $cartCount }}</span>
        </a>
      </div>
    </div>
    <button class="burger" id="burger" aria-label="Abrir menú">
      <svg viewBox="0 0 24 24"><path d="M3 6h18M3 12h18M3 18h18" stroke="#111" stroke-width="2" stroke-linecap="round"/></svg>
    </button>
  </div>

  {{-- Navbar desktop con buscador + avatar --}}
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
      <a href="{{ route('web.catalog.index') }}" class="nav-link {{ request()->routeIs('web.catalog.*') ? 'is-active' : '' }}">Categorías</a>
      <a href="{{ route('web.ventas.index') }}" class="nav-link {{ request()->routeIs('web.ventas.*') ? 'is-active' : '' }}">Ofertas</a>
      <a href="{{ url('/servicios') }}" class="nav-link {{ request()->is('servicios') ? 'is-active' : '' }}">Servicios</a>
    </nav>

    {{-- Buscador + sugerencias --}}
    <div class="searchbar-wrap" id="searchWrap">
      <form class="searchbar" action="{{ route('search.index') }}" method="get" role="search" aria-label="Buscar" id="searchForm">
        <span class="s-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg>
        </span>
        <input type="search" name="q" id="qInput" value="{{ request('q') }}" placeholder="¿Qué quieres encontrar?" autocomplete="off" aria-autocomplete="list" aria-controls="suggList">
        <div class="vdiv" aria-hidden="true"></div>
        <span class="chip" title="Asistente">AI</span>
      </form>

      {{-- Panel de sugerencias --}}
      <div class="sugg" id="sugg" hidden>
        <div id="suggItems"></div>
      </div>
    </div>

    {{-- Acciones laterales --}}
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
                <svg viewBox="0 0 24 24" style="width:18px;height:18px"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
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
        <a href="{{ route('web.catalog.index') }}">Catálogo</a>
        <a href="{{ route('web.ventas.index') }}">Ventas</a>
        <a href="{{ route('web.contacto') }}">Contacto</a>
        <a href="{{ route('web.cart.index') }}">Carrito</a>
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

  // Avatar: abrir/cerrar menú
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

  // Buscador: sugerencias con IA (SearchController@suggest)
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
    function pick(term){
      input.value = term;
      hide();
      form.submit();
    }

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
          render(terms, products);
        }catch(_){ /* no-op */ }
      }, 180);
    });

    input.addEventListener('focus', ()=>{
      if(list.children.length) show();
    });
    document.addEventListener('click', (e)=>{
      if(!e.target.closest('#searchWrap')) hide();
    });
    document.addEventListener('keydown', (e)=>{
      if(e.key === 'Escape') hide();
    });

    function render(terms, products){
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
    }
  })();

  // Utilidad: actualizar badge del carrito desde JS
  window.updateCartBadge = function(count){
    const el = document.querySelector('[data-cart-badge]');
    if (!el) return;
    el.textContent = String(count||0);
  };
</script>

<main class="container" style="padding:28px 20px;">
  @if(session('ok'))
    <div class="wrap" style="max-width:var(--container);">
      <div class="card" style="border-left:4px solid #e4ba16; margin-bottom:16px; padding:12px 14px;">{{ session('ok') }}</div>
    </div>
  @endif
  @yield('content')
</main>

<footer class="ft">
  <div class="ft__wrap">
    <div class="ft__head">
      <a href="{{ route('web.home') }}" class="ft__brand" aria-label="Jureto inicio">
        <img src="{{ asset('images/logo-mail.png') }}" alt="Jureto" class="ft__logo">
        <span class="ft__slogan">soluciones para tu espacio de trabajo.</span>
      </a>
      @if(Route::has('register'))
        <a href="{{ route('register') }}" class="ft__cta">Regístrate</a>
      @endif
    </div>

    <hr class="ft__divider">

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

@stack('scripts')
</body>
</html>
