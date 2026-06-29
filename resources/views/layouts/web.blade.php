<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>@yield('title','Jureto')</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

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
      --ink:#333333; --muted:#767676; --line:#ebebeb;
      --pill:#0071df; --pill-hover:#005bb5; 
      --container:1180px;
      --sheet-bg:#ffffff; --sheet-radius:20px; --sheet-shadow: none;
      --backdrop: rgba(15,23,42,.38);
      --brand:#0071df;

      --header-solid-bg:#ffffff;
      --header-glass-bg: rgba(255,255,255,.9);
      /* Se agregó box-shadow a la transición para que el sombreado sea suave */
      --header-transition: background-color .25s ease, backdrop-filter .25s ease, box-shadow .25s ease;

      --dd-bg: rgba(255,255,255,.98);
      --dd-border: #ebebeb;
      --dd-shadow: 0 10px 25px rgba(0,0,0,.05);
      --dd-radius: 12px;
    }
    *{ box-sizing:border-box }
    html,body{ margin:0; padding:0 }
    
    body{ font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; color:var(--ink); overflow-x:hidden; }
    html.sheet-open{ overflow:hidden; }
    .jrt-scroll-lock{ overflow:hidden; padding-right: var(--jrt-pr, 0px); }

    /* Estado inicial: Hasta arriba, sin borde y sin sombra */
    header.header{
      position:sticky; top:0; left:0; right:0; width:100%;
      background:var(--header-solid-bg);
      z-index:10000;
      border-bottom: none; 
      box-shadow: none; 
      transition: var(--header-transition);
    }
    
    /* Estado con scroll: Solo sombreado, sin borde */
    header.header.header--glass{
      background:var(--header-glass-bg);
      backdrop-filter: saturate(120%) blur(8px);
      -webkit-backdrop-filter: saturate(120%) blur(8px);
      border-bottom: none;
      box-shadow: 0 8px 30px rgba(0,0,0,0.08); /* Aquí está el sombreado suave */
    }

    .wrap{ max-width:var(--container); margin:0 auto; padding:14px 20px }
    .navbar{ display:flex; align-items:center; gap:18px }

    .brand{
      display:flex; align-items:center; gap:12px; white-space:nowrap;
      text-decoration:none; color:var(--ink);
      -webkit-tap-highlight-color: transparent;
    }
    .brand img{ height:34px; display:block }
    .brand:focus,
    .brand:focus-visible{
      outline: none !important;
    }

    .nav-center{ display:flex; justify-content:center; align-items:center; gap:32px; }
    .nav-link{
      position:relative; text-decoration:none; color:var(--ink);
      font-weight:400; 
      padding:8px 4px; display:inline-flex; align-items:center;
      transition: color .2s ease;
    }
    .nav-link:hover, .nav-link.is-active {
      color: var(--brand); 
    }

    .nav-dd{ position:relative; display:inline-flex; align-items:center; z-index:10060; }
    .nav-dd__trigger{
      background:transparent; border:0; cursor:pointer;
      gap:8px; font-weight:400; font-family: inherit; font-size: 1rem; color: var(--ink);
    }
    .nav-dd__trigger:focus-visible{
      outline:none;
      color: var(--brand);
    }

    .nav-dd__caret{
      width:8px; height:8px; display:inline-block;
      border-right:2px solid currentColor;
      border-bottom:2px solid currentColor;
      transform: rotate(45deg);
      margin-top:-2px;
      opacity:.75;
      transition: transform .22s ease, opacity .22s ease;
    }

    .nav-dd__menu{
      position:absolute;
      top:calc(100% + 12px);
      left:-14px;
      width:min(560px, 72vw);
      background:var(--dd-bg);
      border:1px solid var(--dd-border);
      border-radius: var(--dd-radius);
      box-shadow: var(--dd-shadow);
      padding:12px;
      z-index:10070;
      opacity:0;
      visibility:hidden;
      pointer-events:none;
      transform: translateY(10px) scale(.985);
      transform-origin: top left;
      transition: opacity .16s ease, transform .18s ease, visibility .16s ease;
    }

    .nav-dd__head{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      padding:8px 10px 10px;
    }
    .nav-dd__title{
      font-weight:600;
      font-size:.98rem;
      margin:0;
    }
    .nav-dd__meta{
      font-size:.82rem;
      color:var(--muted);
      margin-top:2px;
    }
    .nav-dd__all{
      display:inline-flex;
      align-items:center;
      gap:8px;
      text-decoration:none;
      font-weight:500;
      font-size:.9rem;
      color:#0f172a;
      border:1px solid #ebebeb;
      background:#f9f9f9;
      padding:6px 12px;
      border-radius:999px;
      transition: background .18s ease;
      white-space:nowrap;
    }
    .nav-dd__all:hover{
      background:#fff;
    }

    .nav-dd__divider{
      height:1px;
      background:var(--line);
      margin:2px 8px 10px;
    }

    .nav-dd__list{
      list-style:none;
      padding:0; margin:0;
      display:grid;
      grid-template-columns: 1fr 1fr;
      gap:6px 10px;
      padding: 0 6px 6px;
    }

    .nav-dd__item a{
      display:flex; align-items:center; gap:10px;
      padding:8px 10px;
      border-radius:8px; text-decoration:none;
      color:var(--ink); font-weight:400; font-size: 0.95rem;
      transition: background .14s ease;
    }
    .nav-dd__item a:hover{
      background:#f1f5f9;
      color: var(--brand);
    }

    .nav-dd:hover .nav-dd__menu,
    .nav-dd:focus-within .nav-dd__menu,
    .nav-dd.open .nav-dd__menu{
      opacity:1; visibility:visible; pointer-events:auto; transform: translateY(0) scale(1);
    }
    .nav-dd:hover .nav-dd__caret,
    .nav-dd.open .nav-dd__caret{
      transform: rotate(225deg); opacity:.95;
    }

    @media (max-width: 980px){
      .nav-dd{ display:none !important; }
    }

    .right-tools{ display:flex; align-items:center; gap:12px; z-index:95 }
    .icon-btn{
      position:relative; display:inline-flex; align-items:center; justify-content:center;
      width:36px; height:36px; border-radius:999px; border:none; background:transparent;
      transition:background .2s; color: var(--ink);
    }
    .icon-btn:hover{ background: #f1f5f9; }
    .icon-btn svg{width:22px;height:22px; stroke:currentColor; fill:none; stroke-width:1.5}

    .cart-badge{
      position:absolute; top:-2px; right:-4px;
      min-width:16px; height:16px; line-height:16px;
      text-align:center; font-size:.65rem; background:#ff3b30; color:#fff; border-radius:999px; padding:0 4px;
    }

    .btn-pill{
      appearance:none; border:0; border-radius:999px; padding:10px 20px; font-weight:500;
      background:var(--pill); color:#fff; cursor:pointer; transition:background .2s;
      text-decoration:none !important;
      display:inline-flex; align-items:center; justify-content:center;
    }
    .btn-pill:hover{
      background:var(--pill-hover);
    }

    .mobile-topbar{ display:none; align-items:center; justify-content:space-between; max-width:var(--container); margin:0 auto; padding:10px 16px }
    .m-brand{ display:flex; align-items:center; gap:8px; text-decoration:none; color:var(--ink) }
    .m-logo{ height:26px; width:auto; display:block }
    .m-right{ display:flex; align-items:center; gap:6px }
    .burger{ display:none; background:transparent; border:0; padding:6px; cursor: pointer; }
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

    .sheet-backdrop{ position:fixed; inset:0; background:rgba(0,0,0,.3); opacity:0; pointer-events:none; transition:opacity .2s ease; z-index:49 }
    .sheet{ position:fixed; left:0; right:0; bottom:0; z-index:50; transform: translateY(100%); background:#fff; border-top-left-radius: 20px; border-top-right-radius: 20px; box-shadow: 0 -4px 20px rgba(0,0,0,.05); transition: transform .26s ease; will-change: transform; touch-action: none }
    .sheet__drag{ display:flex; justify-content:center; padding-top:10px }
    .sheet__handle{ width:48px; height:5px; border-radius:999px; background:#e0e0e0 }
    .sheet__content{ padding:14px 18px 18px }
    .sheet__grid{ display:grid; gap:16px }
    .sheet__nav a{ display:block; text-decoration:none; color:var(--ink); font-weight:500; font-size:1.05rem; padding:12px 6px; border-bottom:1px solid var(--line) }
    .sheet__footer{ display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top: 10px; }
    .sheet-open .sheet{ transform: translateY(0) }
    .sheet-open .sheet-backdrop{ opacity:1; pointer-events:auto }

    .ft{ background:#fff; border-top:1px solid var(--line); margin-top:30px }
    .ft__wrap{ max-width:1180px; margin:0 auto; padding:36px 20px; display:flex; flex-direction:column; align-items:center; }
    .ft__head{ width:100%; display:flex; flex-direction:column; align-items:center; text-align:center; gap:16px; }
    .ft__brand{ display:flex; align-items:center; gap:12px; text-decoration:none; color:var(--ink) }
    .ft__logo{ height:38px; width:auto; display:block }
    .ft__slogan{ font-size:.95rem; color:var(--muted); max-width:720px; font-weight: 400; line-height: 1.5; }
    .ft__divider{ border:0; border-top:1px solid var(--line); margin:24px 0; width: 100%; }

    .ft__grid{ display:grid; gap:32px; grid-template-columns: repeat(4, minmax(200px, 1fr)); width: 100%; }
    .ft__title{ background:transparent; border:0; padding:0; margin:0 0 16px 0; font-weight:600; color:var(--ink); font-size:1rem; display:flex; align-items:center; justify-content:space-between; width:100% }
    .ft__chev{ width:12px; height:12px; border-right:2px solid var(--muted); border-bottom:2px solid var(--muted); transform: rotate(-45deg); display: none; }
    .ft__list{ list-style:none; padding:0; margin:0; display:grid; gap:12px }
    .ft__list a{ color:var(--muted); text-decoration:none; font-size: 0.95rem; }
    .ft__list a:hover{ color: var(--brand); }

    @media (max-width: 980px){
      .ft__wrap{ padding:32px 16px; align-items:flex-start }
      .ft__head{ align-items:flex-start; text-align:left }
      .ft__grid{ grid-template-columns: 1fr; gap:0; }
      .ft__col{ border-bottom:1px solid var(--line); padding:16px 0 }
      .ft__title{ margin: 0; cursor:pointer; }
      .ft__chev{ display: block; opacity:1; transition:transform .2s }
      .ft__col:not(.open) .ft__list{ display:none }
      .ft__col.open .ft__list{ display:grid; margin-top: 16px; }
      .ft__col.open .ft__chev{ transform: rotate(45deg); }
      .ft__logo{ max-width:140px; margin:0 0 16px; }
    }

    .searchbar-wrap{ position:relative; flex:1 1 720px; max-width:720px; z-index:100 }
    .searchbar{
      display:flex; align-items:center; gap:8px;
      background:#f1f5f9; 
      border: none; 
      border-radius:999px;
      padding:6px 6px 6px 18px; 
      transition: background .18s;
    }
    .searchbar .s-ico{ width:20px; height:20px; display:inline-flex; color: var(--muted); }
    .searchbar input{ flex:1; border:0; outline:0; background:transparent; font-size:.95rem; color:var(--ink); font-family: inherit; font-weight: 400; }
    .searchbar input::placeholder{ color: #9ca3af; }
    
    .searchbar .chip{ 
      display:inline-flex; align-items:center; justify-content:center; 
      font-weight:500; font-size:.85rem; color:#fff; background: var(--brand); 
      border-radius:999px; width:34px; height:34px; cursor: pointer;
    }
    .searchbar:focus-within{ background: #e2e8f0; } 

    .sugg-backdrop{
      position:fixed; inset:0; background:rgba(0,0,0,.2);
      opacity:0; pointer-events:none; transition:opacity .18s; z-index:8000;
    }
    .sugg-backdrop.is-open{ opacity:1; pointer-events:auto }

    #sugg{
      position:absolute; top:calc(100% + 8px); left:0; right:0;
      background:#fff; border:1px solid var(--line); border-radius:12px;
      box-shadow: 0 4px 15px rgba(0,0,0,.05); padding:8px; z-index:8100;
      max-height:420px; overflow:auto;
      opacity:0; transform: translateY(-4px); transition: opacity .16s ease, transform .16s ease;
    }
    #sugg.is-open{ opacity:1; transform: translateY(0) }
    #sugg[hidden]{ display:none !important }
    #sugg .sugg-section{ padding:8px 10px 4px; color:var(--muted); font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.04em }
    #sugg .sugg-item{
      display:flex; gap:10px; align-items:center; padding:10px 12px;
      border-radius:8px; text-decoration:none; color:var(--ink); cursor:pointer; font-size: 0.95rem;
    }
    #sugg .sugg-item:hover{ background:#f1f5f9 }
    #sugg .sugg-item[aria-selected="true"]{ background:#eef2ff }
    #sugg .sugg-empty{ padding:12px; color:var(--muted); text-align:center; font-size: 0.95rem; }
    #sugg .sugg-item strong{ font-weight:700; color:#111; }
    #sugg .sugg-item small{ display:block; color:var(--muted); font-size:.78rem; margin-top:2px; }
    #sugg .sugg-action{ background:#f8fbff; color:var(--brand); font-weight:700; }

    @media (max-width:980px){
      #sugg{
        position:fixed; left:12px; right:12px; top:calc(var(--hdr-h,64px) + 8px);
        border-radius:14px; max-height:70vh;
      }
    }

    .user-wrap{position:relative; z-index:100}
    .avatar-btn{
      width:36px;height:36px;border-radius:999px;border:none;background:#f1f5f9;
      color:var(--ink); font-weight:600; display:inline-flex; align-items:center; justify-content:center;
      cursor:pointer; transition: background .2s;
    }
    .avatar-btn:hover { background: #e2e8f0; }
    .user-menu{
      position:absolute; right:0; top:48px; min-width:200px; background:#fff; border:1px solid var(--line);
      border-radius:12px; box-shadow: 0 4px 15px rgba(0,0,0,.05); padding:8px; display:none; z-index:105;
    }
    .user-menu.open{display:block}
    .user-menu a, .user-menu form button{
      display:flex; align-items:center; gap:10px; width:100%; text-align:left; font-family: inherit;
      background:#fff; border:0; padding:10px 12px; border-radius:8px; cursor:pointer;
      color:var(--ink); text-decoration:none; font-weight:500; font-size: 0.95rem;
    }
    .user-menu a:hover, .user-menu form button:hover{background:#f1f5f9; color: var(--brand); }


    /* FIX: el dropdown de Productos debe abrir aunque el buscador haya quedado activo */
    header.header{
      isolation:isolate;
    }

    #prodDD{
      z-index:10060;
    }

    #prodDD .nav-dd__menu{
      z-index:10070 !important;
    }

    #suggBackdrop{
      z-index:8000 !important;
    }

    #sugg{
      z-index:8100 !important;
    }

    .jrt-search-force-closed #sugg{
      display:none !important;
    }

  </style>
</head>
<body>

@php
  /*
  |--------------------------------------------------------------------------
  | Categorías reales para header, menú móvil y footer
  |--------------------------------------------------------------------------
  | Se toman primero desde category_products porque ahí están las categorías
  | reales nuevas del catálogo. Solo muestra categorías activas con productos
  | publicados. Si no encuentra, hace fallback a categories.
  |
  | Links:
  | - category_products => /catalogo?category_product=ID
  | - categories        => /catalogo?category=ID
  */
  $jrtHeaderCategories = collect();

  try {
      if (class_exists(\App\Models\CategoryProduct::class)) {
          $jrtHeaderCategories = \App\Models\CategoryProduct::query()
              ->active()
              ->withPublishedProducts()
              ->orderBy('sort_order')
              ->orderBy('name')
              ->limit(12)
              ->get()
              ->map(function ($category) {
                  return (object) [
                      'id' => $category->id,
                      'name' => $category->name,
                      'full_path' => $category->full_path,
                      'type' => 'category_product',
                  ];
              });
      }

      if ($jrtHeaderCategories->isEmpty() && class_exists(\App\Models\Category::class)) {
          $jrtCategoryQuery = \App\Models\Category::query()
              ->withPublishedProducts();

          if (method_exists(\App\Models\Category::class, 'scopePrimary')) {
              $jrtCategoryQuery->primary();
          } else {
              $jrtCategoryQuery->orderBy('position')->orderBy('name');
          }

          $jrtHeaderCategories = $jrtCategoryQuery
              ->limit(12)
              ->get()
              ->map(function ($category) {
                  return (object) [
                      'id' => $category->id,
                      'name' => $category->name,
                      'full_path' => $category->name,
                      'type' => 'category',
                  ];
              });
      }
  } catch (\Throwable $e) {
      $jrtHeaderCategories = collect();
  }

  $jrtFooterCategories = $jrtHeaderCategories->take(6);

  $jrtHeaderSearchProducts = collect();

  try {
      if (class_exists(\App\Models\CatalogItem::class)) {
          $jrtHeaderSearchProducts = \App\Models\CatalogItem::published()
              ->with(['category', 'categoryProduct'])
              ->ordered()
              ->limit(40)
              ->get();
      }
  } catch (\Throwable $e) {
      $jrtHeaderSearchProducts = collect();
  }

  $jrtCategoryUrl = function ($category) {
      if (($category->type ?? 'category') === 'category_product') {
          return route('web.catalog.index', ['category_product' => $category->id]);
      }

      return route('web.catalog.index', ['category' => $category->id]);
  };

  $jrtCategoryIsActive = function ($category) {
      if (($category->type ?? 'category') === 'category_product') {
          return (string) request('category_product') === (string) $category->id;
      }

      return (string) request('category') === (string) $category->id;
  };

  $jrtHeaderSearchCategoriesPayload = $jrtHeaderCategories
      ->map(function ($category) use ($jrtCategoryUrl) {
          return [
              'name' => $category->name,
              'path' => $category->full_path ?? $category->name,
              'url' => $jrtCategoryUrl($category),
              'type' => $category->type ?? 'category',
          ];
      })
      ->values();

  $jrtHeaderSearchProductsPayload = $jrtHeaderSearchProducts
      ->map(function ($product) {
          $categoryName = '';

          if ($product->categoryProduct) {
              $categoryName = $product->categoryProduct->full_path ?? $product->categoryProduct->name;
          } elseif ($product->category) {
              $categoryName = $product->category->name;
          }

          return [
              'name' => $product->name,
              'sku' => $product->sku,
              'category' => $categoryName,
              'url' => route('web.catalog.show', $product),
          ];
      })
      ->values();
@endphp

<script>
  window.__JRT_HEADER_SEARCH__ = {
    catalogUrl: @json(route('web.catalog.index')),
    categories: @json($jrtHeaderSearchCategoriesPayload),
    products: @json($jrtHeaderSearchProductsPayload),
  };
</script>

<header class="header">
  <div class="mobile-topbar">
    <a href="{{ route('web.home') }}" class="m-brand" aria-label="Ir a inicio">
      <img class="m-logo" src="{{ asset('images/logo-mail.png') }}" alt="Jureto" onerror="this.style.opacity=.2">
    </a>
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
        <svg viewBox="0 0 24 24"><path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
  </div>

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

      <div class="nav-dd" id="prodDD">
        <button
          type="button"
          class="nav-link nav-dd__trigger {{ request()->routeIs('web.catalog.*') || request()->filled('category') || request()->is('categoria/*') ? 'is-active' : '' }}"
          id="prodTrigger"
          aria-haspopup="menu"
          aria-expanded="false"
        >
          Productos <span class="nav-dd__caret" aria-hidden="true"></span>
        </button>

        <div class="nav-dd__menu" role="menu" aria-labelledby="prodTrigger">
          <div class="nav-dd__head">
            <div>
              <div class="nav-dd__title">Principales</div>
              <div class="nav-dd__meta">Categorías reales del catálogo</div>
            </div>
            <a class="nav-dd__all" href="{{ route('web.catalog.index') }}">
              Ver todo
              <span aria-hidden="true" style="display:inline-block;transform:translateY(-1px)">→</span>
            </a>
          </div>

          <div class="nav-dd__divider" aria-hidden="true"></div>

          <ul class="nav-dd__list">
            @forelse($jrtHeaderCategories as $headerCategory)
              <li class="nav-dd__item">
                <a
                  href="{{ $jrtCategoryUrl($headerCategory) }}"
                  class="{{ $jrtCategoryIsActive($headerCategory) ? 'is-active' : '' }}"
                >
                  {{ $headerCategory->name }}
                </a>
              </li>
            @empty
              <li class="nav-dd__item">
                <a href="{{ route('web.catalog.index') }}">Todos los productos</a>
              </li>
            @endforelse
          </ul>
        </div>
      </div>
    </nav>

    <div class="searchbar-wrap" id="searchWrap">
      <form class="searchbar" action="{{ route('web.catalog.index') }}" method="get" role="search" aria-label="Buscar" id="searchForm">
        <input type="search" name="s" id="qInput" value="{{ request('s', request('q')) }}" placeholder="¿Qué quieres encontrar?" autocomplete="off" aria-autocomplete="list" aria-controls="sugg" style="padding-left: 8px;">
        <button type="submit" class="chip" title="Buscar" style="border:none;">
            <svg viewBox="0 0 24 24" style="width:18px;height:18px;stroke:#fff;fill:none;stroke-width:2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg>
        </button>
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
            <div style="padding:8px 10px 10px; border-bottom: 1px solid var(--line); margin-bottom: 4px;">
              <div style="font-weight:600">{{ $user->name ?? 'Mi cuenta' }}</div>
              <small style="color:var(--muted)">{{ $user->email }}</small>
            </div>
            <a href="{{ route('customer.profile') }}" role="menuitem">Mi cuenta</a>
            <a href="{{ url('/mi-cuenta#t-pedidos') }}" role="menuitem">Mis pedidos</a>
            <form method="POST" action="{{ route('logout') }}" role="none" style="margin:0;">
              @csrf
              <button type="submit" role="menuitem">Cerrar sesión</button>
            </form>
          </div>
        </div>
      @else
        <a href="{{ route('login') }}" class="btn-pill" style="background:transparent; color:var(--ink);">Regístrate</a>
      @endif
    </div>
  </div>
</header>

<div class="sugg-backdrop" id="suggBackdrop" hidden></div>

<div class="sheet-backdrop" id="sheet-backdrop" hidden></div>
<section class="sheet" id="sheet" role="dialog" aria-modal="true" aria-label="Menú" tabindex="-1">
  <div class="sheet__drag"><div class="sheet__handle" aria-hidden="true"></div></div>
  <div class="sheet__content">
    <div class="sheet__grid">
      <nav class="sheet__nav" aria-label="Menú móvil">
        <a href="{{ route('web.home') }}">Inicio</a>
        <a href="{{ route('web.ofertas') }}">Ofertas</a>
        <a href="{{ route('web.catalog.index') }}">Todos los productos</a>

        @foreach($jrtHeaderCategories->take(8) as $mobileCategory)
          <a href="{{ $jrtCategoryUrl($mobileCategory) }}">
            {{ $mobileCategory->name }}
          </a>
        @endforeach

        <a href="{{ route('web.ventas.index') }}">Ventas</a>
        <a href="{{ route('web.contacto') }}">Contacto</a>
        <a href="{{ route('favoritos.index') }}">Favoritos</a>
      </nav>
      <div class="sheet__footer">
        <div class="sheet__icons">
          <a class="icon-btn" href="https://facebook.com" target="_blank" aria-label="Facebook" rel="noopener">F</a>
          <a class="icon-btn" href="https://instagram.com" target="_blank" aria-label="Instagram" rel="noopener">I</a>
        </div>
        @auth
          <a href="{{ route('customer.welcome') }}" class="btn-pill">Mi cuenta</a>
        @else
          <a href="{{ route('login') }}" class="btn-pill">Regístrate</a>
        @endauth
      </div>
    </div>
  </div>
</section>

<main class="container" style="padding:28px 20px;">
  @yield('content')
</main>

<footer class="ft">
  <div class="ft__wrap">
    <div class="ft__head">
      <img src="{{ asset('images/logo-mail.png') }}" alt="Jureto" class="ft__logo">
      <span class="ft__slogan">
        Jureto es el aliado B2B para equipar oficinas y dependencias públicas con soluciones integrales: papelería, cómputo, y muebles. Sin fricción, sin complicaciones.
      </span>
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
          @forelse($jrtFooterCategories as $footerCategory)
            <li>
              <a href="{{ $jrtCategoryUrl($footerCategory) }}">
                {{ $footerCategory->name }}
              </a>
            </li>
          @empty
            <li><a href="{{ route('web.catalog.index') }}">Todos los productos</a></li>
          @endforelse
        </ul>
      </section>

      <section class="ft__col">
        <button class="ft__title" type="button" data-acc>Políticas <span class="ft__chev"></span></button>
        <ul class="ft__list">
          <li><a href="{{ url('/envios-devoluciones-cancelaciones') }}">Envíos y devoluciones</a></li>
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
          <li><a href="{{ url('/garantias-y-devoluciones') }}">Garantías</a></li>
        </ul>
      </section>
    </div>
  </div>
</footer>

<script>
  (function(){
    const header = document.querySelector('header.header');
    if(!header) return;
    const THRESHOLD = 32;
    function applyGlass(){
      if(window.scrollY > THRESHOLD){ header.classList.add('header--glass'); }
      else{ header.classList.remove('header--glass'); }
    }
    function setHdrVar(){
      document.documentElement.style.setProperty('--hdr-h', (header.offsetHeight || 64) + 'px');
    }
    applyGlass(); setHdrVar();
    window.addEventListener('scroll', applyGlass, { passive:true });
    window.addEventListener('resize', ()=>{ applyGlass(); setHdrVar(); }, { passive:true });
  })();

  (function(){
    const dd = document.getElementById('prodDD');
    const btn = document.getElementById('prodTrigger');
    if(!dd || !btn) return;

    function isDesktop(){ return window.matchMedia('(min-width:981px)').matches; }

    function closeSearchUI(){
      const panel = document.getElementById('sugg');
      const list = document.getElementById('suggItems');
      const input = document.getElementById('qInput');
      const backdrop = document.getElementById('suggBackdrop');
      const body = document.body;

      document.documentElement.classList.add('jrt-search-force-closed');

      if(panel){
        panel.classList.remove('is-open');
        panel.hidden = true;
      }

      if(list){
        list.innerHTML = '';
      }

      if(input){
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        input.blur();
      }

      if(backdrop){
        backdrop.classList.remove('is-open');
        backdrop.hidden = true;
      }

      body.classList.remove('jrt-scroll-lock');
      body.style.removeProperty('--jrt-pr');

      window.setTimeout(function(){
        document.documentElement.classList.remove('jrt-search-force-closed');
      }, 180);
    }

    function closeMobileSheet(){
      const html = document.documentElement;
      const sheetBackdrop = document.getElementById('sheet-backdrop');
      const sheet = document.getElementById('sheet');

      html.classList.remove('sheet-open');

      if(sheetBackdrop){
        sheetBackdrop.hidden = true;
      }

      if(sheet){
        sheet.setAttribute('inert', '');
      }
    }

    function setOpen(open){
      if(open){
        closeSearchUI();
        closeMobileSheet();
      }

      dd.classList.toggle('open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    btn.addEventListener('click', (e)=>{
      if(!isDesktop()) return;
      e.preventDefault();
      e.stopPropagation();

      const willOpen = !dd.classList.contains('open');
      setOpen(willOpen);
    });

    dd.addEventListener('mouseenter', ()=>{
      if(!isDesktop()) return;
      closeSearchUI();
    });

    document.addEventListener('click', (e)=>{
      if(!isDesktop()) return;
      if(!dd.classList.contains('open')) return;
      if(!e.target.closest('#prodDD')) setOpen(false);
    });

    document.addEventListener('keydown', (e)=>{
      if(!isDesktop()) return;
      if(e.key === 'Escape') setOpen(false);
    });
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
    const wrap  = document.getElementById('searchWrap');
    const backdrop = document.getElementById('suggBackdrop');
    const sheetBackdrop = document.getElementById('sheet-backdrop');
    const body = document.body;

    if(!input || !panel || !list || !wrap || !form) return;

    let savedScroll = 0;
    const SEARCH_DATA = window.__JRT_HEADER_SEARCH__ || { catalogUrl: '/catalogo', categories: [], products: [] };

    function normalizeText(value){
      return String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
    }

    function escapeHtml(value){
      return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function catalogSearchUrl(term){
      const url = new URL(SEARCH_DATA.catalogUrl || '/catalogo', window.location.origin);
      url.searchParams.set('s', term);
      return url.toString();
    }

    function scrollbarWidth(){
      return window.innerWidth - document.documentElement.clientWidth;
    }

    function lock(){
      if(body.classList.contains('jrt-scroll-lock')) return;
      savedScroll = window.scrollY || 0;
      body.style.setProperty('--jrt-pr', scrollbarWidth() + 'px');
      body.classList.add('jrt-scroll-lock');
    }

    function unlock(){
      if(!body.classList.contains('jrt-scroll-lock')) return;
      body.classList.remove('jrt-scroll-lock');
      body.style.removeProperty('--jrt-pr');
      window.scrollTo(0, savedScroll || 0);
    }

    function closeProductsDropdown(){
      const dd = document.getElementById('prodDD');
      const btn = document.getElementById('prodTrigger');

      if(dd) dd.classList.remove('open');
      if(btn) btn.setAttribute('aria-expanded', 'false');
    }

    function openUI(){
      const html = document.documentElement;

      closeProductsDropdown();

      if(html.classList.contains('sheet-open')){
        html.classList.remove('sheet-open');
        if(sheetBackdrop) sheetBackdrop.hidden = true;
      }

      panel.hidden = false;
      panel.classList.add('is-open');

      if(backdrop){
        backdrop.hidden = false;
        backdrop.classList.add('is-open');
      }

      input.setAttribute('aria-expanded','true');
      lock();
    }

    function closeUI(){
      panel.classList.remove('is-open');
      panel.hidden = true;
      list.innerHTML = '';
      input.setAttribute('aria-expanded','false');
      input.removeAttribute('aria-activedescendant');

      if(backdrop){
        backdrop.classList.remove('is-open');
        backdrop.hidden = true;
      }

      unlock();
    }

    function renderSuggestions(term){
      const cleanTerm = normalizeText(term);

      if(cleanTerm.length < 1){
        closeUI();
        return;
      }

      const categories = Array.isArray(SEARCH_DATA.categories) ? SEARCH_DATA.categories : [];
      const products = Array.isArray(SEARCH_DATA.products) ? SEARCH_DATA.products : [];

      const matchedCategories = categories.filter(function(category){
        const haystack = normalizeText([category.name, category.path].join(' '));
        return haystack.includes(cleanTerm);
      }).slice(0, 6);

      const matchedProducts = products.filter(function(product){
        const haystack = normalizeText([product.name, product.sku, product.category].join(' '));
        return haystack.includes(cleanTerm);
      }).slice(0, 6);

      let html = '';

      html += `
        <a class="sugg-item sugg-action" role="option" tabindex="-1" href="${catalogSearchUrl(term)}">
          <svg viewBox="0 0 24 24" style="width:16px;height:16px;stroke:var(--brand);fill:none;stroke-width:2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg>
          <span>Buscar <strong>${escapeHtml(term)}</strong> en catálogo</span>
        </a>
      `;

      if(matchedCategories.length){
        html += `<div class="sugg-section">Categorías</div>`;
        html += matchedCategories.map(function(category){
          return `
            <a class="sugg-item" role="option" tabindex="-1" href="${category.url}">
              <svg viewBox="0 0 24 24" style="width:16px;height:16px;stroke:var(--muted);fill:none;stroke-width:2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
              <span><strong>${escapeHtml(category.name)}</strong><small>${escapeHtml(category.path || 'Categoría')}</small></span>
            </a>
          `;
        }).join('');
      }

      if(matchedProducts.length){
        html += `<div class="sugg-section">Productos</div>`;
        html += matchedProducts.map(function(product){
          return `
            <a class="sugg-item" role="option" tabindex="-1" href="${product.url}">
              <svg viewBox="0 0 24 24" style="width:16px;height:16px;stroke:var(--muted);fill:none;stroke-width:2"><path d="M20 7H4"/><path d="M6 7v13a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V7"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/></svg>
              <span><strong>${escapeHtml(product.name || 'Producto')}</strong><small>${escapeHtml([product.sku, product.category].filter(Boolean).join(' · '))}</small></span>
            </a>
          `;
        }).join('');
      }

      if(!matchedCategories.length && !matchedProducts.length){
        html += `<div class="sugg-empty">No hay coincidencias rápidas. Presiona Enter o toca “Buscar” para ver resultados.</div>`;
      }

      list.innerHTML = html;
      openUI();
    }

    input.setAttribute('role','combobox');
    input.setAttribute('aria-autocomplete','list');
    input.setAttribute('aria-expanded','false');
    input.setAttribute('aria-controls','sugg');

    input.addEventListener('input', function(){
      renderSuggestions(input.value.trim());
    });

    input.addEventListener('focus', function(){
      if(input.value.trim().length >= 1){
        renderSuggestions(input.value.trim());
      }
    });

    form.addEventListener('submit', function(){
      closeUI();
    });

    document.addEventListener('click', function(e){
      if(panel.hidden) return;
      if(!e.target.closest('#searchWrap') && !e.target.closest('#sugg')) closeUI();
    });

    backdrop?.addEventListener('click', closeUI);

    document.addEventListener('keydown', function(e){
      if(e.key === 'Escape'){
        closeUI();
        input.blur();
      }
    });

    panel.addEventListener('click', function(e){
      const item = e.target.closest('.sugg-item');
      if(!item) return;
      closeUI();
    });
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
  })();

  window.updateCartBadge = function(count){
    document.querySelectorAll('[data-cart-badge]').forEach(b=> b.textContent = String(count||0));
  };
</script>

@stack('scripts')

</body>
</html>