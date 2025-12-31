<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Panel')</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo-mail.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logo-mail.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/logo-mail.png') }}">

  <link rel="stylesheet" href="{{ asset('css/app-layout.css') }}?v={{ time() }}">
  @stack('styles')
  <style>
    :root{
      --bg: #e9eef6;
      --surface: #f6f8ff;
      --surface-2: #e7edfb;
      --border: #d6def0;
      --primary: #5b8def;
      --primary-600:#3b6fde;
      --accent: #ff7ab6;
      --text: #1f2a44;
      --muted:#6b7a99;
      --shadow: 0 12px 38px rgba(23,36,71,.14);
      --topbar-h: 56px;
      --sidebar-w: 310px;
      --fade-h: 16px;
    }

    *{box-sizing:border-box}
    html,body{height:100%}
    body.app{
      margin:0; font-family:"Quicksand", system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, "Helvetica Neue", Arial;
      background: radial-gradient(1200px 800px at 10% -10%, #f4f7ff 0%, var(--bg) 60%), linear-gradient(180deg, var(--bg) 0%, #e5ecf8 100%);
      color:var(--text);
    }
    body.lock-scroll{ overflow:hidden; }

    /* Avatares */
    .avatar, .avatar.avatar--sm { position: relative; overflow: hidden; border-radius: 50%; }
    .avatar img, .avatar.avatar--sm img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .avatar img + span, .avatar.avatar--sm img + span { display: none !important; }
    .avatar-link{ display:inline-block; border-radius:50%; text-decoration:none; line-height:0; }
    .avatar-link:focus{ outline:2px solid #7ea2ff; outline-offset:3px; }

    /* Shell */
    .shell{ min-height:100vh; display:flex; flex-direction:column; transition: filter .28s ease; }
    .shell.dimmed{ filter: blur(2px) saturate(.95); }

    /* Sidebar (overlay) */
    .sidebar{
      position:fixed; left:0; top:0; bottom:0; width:var(--sidebar-w);
      background:linear-gradient(180deg, #f7faff,#ecf1ff);
      border-right:1px solid var(--border);
      transform: translateX(-102%);
      transition: transform .45s cubic-bezier(.16,1,.3,1);
      z-index:60; box-shadow:var(--shadow);
      will-change: transform;
      display:flex; flex-direction:column;
    }
    .sidebar.is-open{ transform: translateX(0); }

    /* Backdrop */
    .backdrop{
      position:fixed; inset:0; background:rgba(7,12,24,.55);
      opacity:0; pointer-events:none; transition: opacity .28s ease;
      z-index:50;
    }
    .backdrop.is-show{ opacity:1; pointer-events:auto; }

    /* Head */
    .sidebar__head{ display:flex; align-items:flex-start; gap:12px; padding:18px 16px 12px; border-bottom:1px solid var(--border); }
    .sidebar__close{
      margin-left:auto; background:transparent; border:0; cursor:pointer; color:var(--muted);
      width:36px; height:36px; border-radius:12px; display:grid; place-items:center;
      transition:background .2s ease, transform .08s ease, color .2s ease;
    }
    .sidebar__close:hover{ background:rgba(91,141,239,.12); color:var(--primary); }
    .sidebar__close:active{ transform:scale(.98); }

    .user{ display:flex; gap:12px; align-items:center; }
    .avatar{
      width:44px; height:44px; border-radius:12px; background:linear-gradient(135deg,#7ea8ff,#a8c9ff);
      color:#fff; display:grid; place-items:center; font-weight:700; box-shadow:var(--shadow);
    }
    .avatar--sm{ width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,#7ea8ff,#a8c9ff); color:#fff; display:grid; place-items:center; font-weight:700; }

    .user__meta{ line-height:1.2 }
    .user__name{ font-weight:700 }
    .user__mail{ color:var(--muted); font-size:.92rem; }
    .user__roles{ margin-top:6px; display:flex; gap:6px; flex-wrap:wrap; }
    .chip{
      padding:4px 8px; border-radius:999px; background:#e7ecfb; color:#3b4a6b; font-size:.78rem; border:1px solid var(--border);
      transition: transform .12s ease;
    }
    .chip:hover{ transform: translateY(-1px); }

    /* Navegación con scroll (scrollbar oculto) + fades */
    .nav{
      position:relative;
      display:flex; flex-direction:column; gap:4px;
      padding:10px 10px 12px;
      overflow:auto;
      overscroll-behavior:contain;
      scrollbar-width:none;
      -ms-overflow-style:none;
      flex:1;
      -webkit-mask-image: linear-gradient(to bottom, transparent 0, #000 var(--fade-h), #000 calc(100% - var(--fade-h)), transparent 100%);
              mask-image: linear-gradient(to bottom, transparent 0, #000 var(--fade-h), #000 calc(100% - var(--fade-h)), transparent 100%);
    }
    .nav::-webkit-scrollbar{ display:none }

    .nav__link{
      display:flex; gap:10px; align-items:center; padding:12px 12px;
      border-radius:12px; color:#2b3756; text-decoration:none;
      transition:background .18s ease, color .18s ease, transform .08s ease;
    }
    .nav__link:hover{ background:#eaf0ff; color:var(--primary); transform: translateX(2px); }
    .nav__link.is-active{ background:#e3ebff; color:var(--primary-600); font-weight:600; }

    .nav .nav__group { margin: 4px 0; }
    .nav .nav__group > summary {
      list-style: none;
      display: flex; align-items: center; gap: 10px;
      padding: 10px 12px; border-radius: 10px;
      color: inherit; cursor: pointer; user-select: none;
      transition: background .18s ease, color .18s ease, transform .08s ease;
    }
    .nav .nav__group > summary::-webkit-details-marker{ display:none }
    .nav .nav__group > summary:hover{ background: rgba(126,162,255,.12); color: var(--primary); transform: translateX(1px); }
    .nav .nav__group[open] > summary { background: rgba(126,162,255,.12); }
    .nav .nav__chev { margin-left: auto; transition: transform .2s ease; }
    .nav .nav__group[open] .nav__chev { transform: rotate(90deg); }

    .nav__submenu{
      display:flex; flex-direction:column; gap:4px; padding:6px 0 6px 36px;
    }
    .nav__sublink{
      display:flex; align-items:center; gap:10px;
      padding:8px 12px; border-radius:8px; text-decoration:none; color:inherit;
      opacity:.95; transition: background .18s ease, color .18s ease, transform .08s ease;
    }
    .nav__sublink:hover{ background: rgba(126,162,255,.12); opacity:1; transform: translateX(2px); }
    .nav__sublink.is-active{ background: rgba(126,162,255,.2); font-weight:600; }

    .logout{ padding:10px; border-top:1px solid var(--border); }
    .btn-logout{
      width:100%; display:flex; align-items:center; gap:10px; padding:12px 12px; border-radius:12px;
      background:#ffe8f3; color:#7b294a; border:1px solid #ffd0e6; cursor:pointer; font-weight:600;
      transition:filter .18s ease, transform .06s ease;
    }
    .btn-logout:hover{ filter:brightness(1.03); }
    .btn-logout:active{ transform:scale(.99); }

    /* Topbar */
    .topbar{
      position:sticky; top:0; z-index:30;
      display:flex; align-items:center; gap:12px; padding:10px 14px;
      background:linear-gradient(180deg,#f3f6ff,#eaf1ff);
      border-bottom:1px solid var(--border);
      height: var(--topbar-h);
    }
    .icon-btn{
      background:transparent; border:0; cursor:pointer; color:#2b3756;
      width:40px; height:40px; border-radius:12px; display:grid; place-items:center;
      transition:background .18s ease, transform .06s ease, color .18s ease;
    }
    .icon-btn:hover{ background:rgba(91,141,239,.12); color:var(--primary); }
    .icon-btn:active{ transform:scale(.98); }

    .topbar__title{ font-weight:700; letter-spacing:.3px; }
    .topbar__right{ margin-left:auto; display:flex; align-items:center; gap:12px; }

    /* ========== Notificaciones ========== */
    .notif{ position:relative; }

    /* badge con número */
    .dot{
      position:absolute;
      top:6px;
      right:6px;
      min-width:18px;
      height:18px;
      padding:0 4px;
      background:var(--accent);
      border-radius:999px;
      box-shadow:0 0 0 3px #fff7;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:10px;
      font-weight:700;
      color:#fff;
      animation: dotPulse 2.2s infinite cubic-bezier(.66,0,0,1);
    }
    @keyframes dotPulse{
      0%,100%{ transform:scale(1); opacity:1 }
      50%{ transform:scale(1.25); opacity:.75 }
    }

    .notif__panel{
      position:absolute; right:0; top:48px; width:320px; max-width:92vw;
      background:var(--surface); border:1px solid var(--border); border-radius:16px; box-shadow:var(--shadow);
      opacity:0; transform: translateY(-8px) scale(.98); pointer-events:none;
      transition: opacity .18s ease, transform .22s cubic-bezier(.22,1,.36,1);
      overflow:hidden; z-index: 35;
    }
    .notif__panel.is-open{ opacity:1; transform: translateY(0) scale(1); pointer-events:auto; }
    .notif__head{
      display:flex; align-items:center; justify-content:space-between;
      padding:12px 12px; border-bottom:1px solid var(--border);
    }
    .notif__list{
      max-height:300px; overflow:auto; overscroll-behavior:contain;
      background:var(--surface);
    }

    .notif__item{
      position:relative;
      display:grid;
      grid-template-columns:auto 1fr;
      column-gap:8px;
      row-gap:2px;
      padding:8px 12px;
      border-bottom:1px solid rgba(214,222,240,.9);
      background:linear-gradient(180deg,#ffffff,#f9fbff);
      padding-right:32px; /* espacio para el botón X */
    }
    .notif__item:last-child{
      border-bottom:none;
    }
    .notif__item.is-unread{
      background:linear-gradient(180deg,#ffffff,#f0f4ff);
    }
    .notif__item.is-read{
      opacity:.9;
    }

    .notif__text{ color:#2b3756; }
    .notif__time{ color:#6b7a99; font-size:.85rem; grid-column:2; }

    .pill{
      padding:2px 8px; border-radius:999px; font-size:.72rem;
      border:1px solid var(--border); align-self:start;
    }
    .pill--info{ background:#e8f1ff; color:#2b4a7a; }
    .pill--warn{ background:#fff3cd; color:#8a6d1a; }
    .pill--error{ background:#fde2e1; color:#8a1f1f; }

    .notif__link{
      display:block; padding:10px 12px;
      text-decoration:none; color:#3b6fde;
      border-top:1px solid var(--border);
      font-weight:600; text-align:left;
      background:none; width:100%; cursor:pointer;
    }
    .notif__link:hover{ background:#eaf0ff; }
    .notif__empty{ padding:12px; font-size:.88rem; color:var(--muted); }

    /* botón para marcar una sola como leída */
    .notif__item-close{
      position:absolute;
      top:8px;
      right:8px;
      width:20px;
      height:20px;
      border-radius:999px;
      border:none;
      background:transparent;
      cursor:pointer;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:13px;
      color:var(--muted);
      transition:background .16s ease,color .16s ease,transform .08s ease;
    }
    .notif__item-close:hover{
      background:rgba(91,141,239,.08);
      color:var(--primary);
    }
    .notif__item-close:active{
      transform:scale(.94);
    }

    /* Contenido */
    .content{ padding:18px; min-height:calc(100vh - var(--topbar-h)); }

    .no-anim *{ transition:none !important; animation:none !important; }
  </style>
</head>
<body class="app">
  <!-- Sidebar (Hamburguesa) -->
  <aside id="sidebar" class="sidebar" aria-hidden="true" aria-label="Menú lateral">
    <div class="sidebar__head">
      <div class="user">
        @php
          $u  = auth()->user();
          $nm = $u?->name ?? 'Usuario';
          $ini = mb_strtoupper(mb_substr($nm,0,1));

          $baseAvatar = null;
          if ($u && !empty($u->avatar_url)) { $baseAvatar = $u->avatar_url; }
          if (!$baseAvatar && $u && !empty($u->email)) {
              $hash = md5(strtolower(trim($u->email)));
              $baseAvatar = "https://www.gravatar.com/avatar/{$hash}?s=300&d=mp";
          }
          $ver = null;
          if ($u && !empty($u->avatar_updated_at)) {
              $ver = $u->avatar_updated_at instanceof \Illuminate\Support\Carbon ? $u->avatar_updated_at->timestamp : strtotime($u->avatar_updated_at);
          } elseif ($u && !empty($u->updated_at)) {
              $ver = $u->updated_at instanceof \Illuminate\Support\Carbon ? $u->updated_at->timestamp : strtotime($u->updated_at);
          }
          $avatarSrc = null;
          if (!empty($baseAvatar)) {
              $sep = (strpos($baseAvatar, '?') !== false) ? '&' : '?';
              $avatarSrc = $baseAvatar . ($ver ? ($sep.'v='.$ver) : '');
          }
          $fallbackMp = ($u && !empty($u->email))
              ? "https://www.gravatar.com/avatar/".md5(strtolower(trim($u->email)))."?s=300&d=mp"
              : "https://www.gravatar.com/avatar/?s=300&d=mp";

          // Rutas al perfil usando la fachada con FQCN (sin use)
          if (\Illuminate\Support\Facades\Route::has('profile.show')) {
              $profileHref = route('profile.show');
          } elseif (\Illuminate\Support\Facades\Route::has('profile')) {
              $profileHref = route('profile');
          } else {
              $profileHref = url('/panel/perfil');
          }

          // URLs para el feed de notificaciones
          if (\Illuminate\Support\Facades\Route::has('notifications.feed')) {
              $notifFeedUrl = route('notifications.feed');
          } else {
              $notifFeedUrl = url('/notifications/feed');
          }
          if (\Illuminate\Support\Facades\Route::has('notifications.read-all')) {
              $notifReadAllUrl = route('notifications.read-all');
          } else {
              $notifReadAllUrl = url('/notifications/read-all');
          }

          if (\Illuminate\Support\Facades\Route::has('notifications.read-one')) {
              // usamos __ID__ como placeholder y lo sustituimos en JS
              $notifReadOneUrl = route('notifications.read-one', ['notification' => '__ID__']);
          } else {
              $notifReadOneUrl = url('/notifications/__ID__/read');
          }
        @endphp

        <a href="{{ $profileHref }}" class="avatar-link" title="Ver mi perfil">
          <div class="avatar" aria-hidden="true">
            @if($avatarSrc)
              <img src="{{ $avatarSrc }}" alt="Avatar de {{ $nm }}" onerror="this.onerror=null;this.src='{{ $fallbackMp }}';">
              <span>{{ $ini }}</span>
            @else
              <img src="{{ $fallbackMp }}" alt="Avatar de {{ $nm }}">
              <span>{{ $ini }}</span>
            @endif
          </div>
        </a>

        <div class="user__meta">
          <div class="user__name">{{ $nm }}</div>
          <div class="user__mail">{{ $u?->email ?? 'correo@dominio.com' }}</div>
          @if($u && method_exists($u,'getRoleNames'))
            <div class="user__roles">
              @foreach($u->getRoleNames() as $r)
                <span class="chip">{{ $r }}</span>
              @endforeach
            </div>
          @endif
        </div>
      </div>

      <button class="sidebar__close" id="btnCloseSidebar" aria-label="Cerrar menú">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 6L6 18M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <nav class="nav" id="sidebarNav">
      <!-- ===== SOLO RUTAS INTERNAS ===== -->

      <!-- Dashboard -->
      <a href="{{ route('dashboard') }}" class="nav__link {{ request()->routeIs('dashboard') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
          <path d="M3 12l9-9 9 9"/><path d="M9 21V9h6v12"/>
        </svg>
        <span>Dashboard</span>
      </a>

      <!-- Mi perfil -->
      <details class="nav__group" {{ request()->routeIs('profile.*') ? 'open' : '' }}>
        <summary class="{{ request()->routeIs('profile.*') ? 'is-active':'' }}">
          <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
            <circle cx="12" cy="7" r="4"/><path d="M6 21v-2a6 6 0 0 1 12 0v2"/>
          </svg>
          <span>Mi perfil</span>
          <svg class="nav__chev" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
        </summary>
        <div class="nav__submenu">
          <a href="{{ route('profile.show') }}" class="nav__sublink {{ request()->routeIs('profile.show') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M4 6h16"/></svg>
            <span>Ver perfil</span>
          </a>
        </div>
      </details>

      <!-- Productos -->
      <details class="nav__group" {{ request()->routeIs('products.*') ? 'open' : '' }}>
        <summary class="{{ request()->routeIs('products.*') ? 'is-active':'' }}">
          <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
            <rect x="3" y="4" width="18" height="14" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/>
          </svg>
          <span>Productos</span>
          <svg class="nav__chev" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
        </summary>
        <div class="nav__submenu">
          <a href="{{ route('products.index') }}" class="nav__sublink {{ request()->routeIs('products.index') || request()->routeIs('products.show') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
            <span>Listado</span>
          </a>
          <a href="{{ route('products.create') }}" class="nav__sublink {{ request()->routeIs('products.create') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg>
            <span>Nuevo producto</span>
          </a>
          <a href="{{ route('products.import.form') }}" class="nav__sublink {{ request()->routeIs('products.import.*') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M12 3v12m0 0l-4-4m4 4l4-4"/><path d="M5 21h14"/></svg>
            <span>Importar</span>
          </a>
          <a href="{{ route('products.export.pdf') }}" class="nav__sublink">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M12 3v12m0 0l-4-4m4 4l4-4"/><path d="M19 21H5"/></svg>
            <span>Exportar PDF</span>
          </a>
        </div>
      </details>

      <!-- Proveedores -->
      <a href="{{ route('providers.index') }}" class="nav__link {{ request()->routeIs('providers.*') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
          <path d="M3 7h18l-2 10a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3L3 7z"/>
          <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
        </svg>
        <span>Proveedores</span>
      </a>

      <!-- Clientes -->
      <a href="{{ route('clients.index') }}" class="nav__link {{ request()->routeIs('clients.*') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
          <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
        </svg>
        <span>Clientes</span>
      </a>

      <!-- Cotizaciones -->
      <details class="nav__group" {{ request()->routeIs('cotizaciones.*') ? 'open' : '' }}>
        <summary class="{{ request()->routeIs('cotizaciones.*') ? 'is-active':'' }}">

          <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
            <rect x="3" y="4" width="18" height="14" rx="2"/>
          </svg>
          <span>Cotizaciones</span>
          <svg class="nav__chev" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
        </summary>
        <div class="nav__submenu">
          <a href="{{ route('cotizaciones.index') }}" class="nav__sublink {{ request()->routeIs('cotizaciones.index') || request()->routeIs('cotizaciones.show') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
            <span>Listado</span>
          </a>
          <a href="{{ route('cotizaciones.create') }}" class="nav__sublink {{ request()->routeIs('cotizaciones.create') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg>
            <span>Nueva</span>
          </a>
          <a href="{{ route('cotizaciones.auto.form') }}" class="nav__sublink {{ request()->routeIs('cotizaciones.auto.*') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M3 12h7l-2 2m2-2l-2-2M14 7h7l-2 2m2-2l-2-2"/></svg>
            <span>Auto (asistida)</span>
          </a>
        </div>
      </details>

      <!-- Ventas -->
      <a href="{{ route('ventas.index') }}" class="nav__link {{ request()->routeIs('ventas.*') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
          <path d="M3 7h18l-2 10a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3L3 7z"/>
          <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
        </svg>
        <span>Ventas</span>
      </a>

      <!-- Facturas manuales -->
      <details class="nav__group" {{ request()->routeIs('manual_invoices.*') ? 'open' : '' }}>
        <summary class="{{ request()->routeIs('manual_invoices.*') ? 'is-active':'' }}">
          <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
            <rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 7h6M9 11h4M9 15h6"/>
          </svg>
          <span>Facturas</span>
          <svg class="nav__chev" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
        </summary>
        <div class="nav__submenu">
          <a href="{{ route('manual_invoices.index') }}" class="nav__sublink {{ request()->routeIs('manual_invoices.index') || request()->routeIs('manual_invoices.show') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
            <span>Listado</span>
          </a>
          <a href="{{ route('manual_invoices.create') }}" class="nav__sublink {{ request()->routeIs('manual_invoices.create') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg>
            <span>Nueva factura</span>
          </a>
        </div>
      </details>

      <!-- Publicaciones -->
      <details class="nav__group" {{ request()->routeIs('posts.*') ? 'open' : '' }}>
        <summary class="{{ request()->routeIs('posts.*') ? 'is-active':'' }}">
          <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
            <rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h8M8 11h5M8 15h8"/>
          </svg>
          <span>Publicaciones</span>
          <svg class="nav__chev" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
        </summary>
        <div class="nav__submenu">
          <a href="{{ route('posts.index') }}" class="nav__sublink {{ request()->routeIs('posts.index') || request()->routeIs('posts.show') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
            <span>Listado</span>
          </a>
          <a href="{{ route('posts.create') }}" class="nav__sublink {{ request()->routeIs('posts.create') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg>
            <span>Nueva publicación</span>
          </a>
        </div>
      </details>

      <!-- Part. contable (principal: listado) -->
      <a href="{{ route('partcontable.index') }}" class="nav__link {{ request()->routeIs('partcontable.*') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
          <circle cx="12" cy="12" r="9"/>
          <path d="M9 10h3.5a2 2 0 1 1 0 4H9m3-7v10"/>
        </svg>
        <span>Part. contable</span>
      </a>

      <!-- Licitaciones (incluye IA y admin PDFs / propuestas) -->
      <details class="nav__group"
        {{ request()->routeIs('licitaciones.*')
            || request()->routeIs('licitaciones-ai.*')
            || request()->routeIs('admin.licitacion-pdfs.*')
            || request()->routeIs('admin.licitacion-propuestas.*')
            ? 'open' : '' }}>
        <summary class="{{ request()->routeIs('licitaciones.*')
                          || request()->routeIs('licitaciones-ai.*')
                          || request()->routeIs('admin.licitacion-pdfs.*')
                          || request()->routeIs('admin.licitacion-propuestas.*')
                          ? 'is-active':'' }}">
          <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
            <rect x="4" y="3" width="16" height="18" rx="2"/>
            <path d="M8 7h8M8 11h8M8 15h5"/>
          </svg>
          <span>Licitaciones</span>
          <svg class="nav__chev" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2">
            <path d="M9 6l6 6-6 6"/>
          </svg>
        </summary>
        <div class="nav__submenu">
          {{-- Listado / detalle --}}
          <a href="{{ route('licitaciones.index') }}"
             class="nav__sublink {{ request()->routeIs('licitaciones.index') || request()->routeIs('licitaciones.show') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8">
              <path d="M4 6h16M4 12h16M4 18h10"/>
            </svg>
            <span>Listado</span>
          </a>

          {{-- Crear nueva (step 1 del wizard) --}}
          <a href="{{ route('licitaciones.create.step1') }}"
             class="nav__sublink {{ request()->routeIs('licitaciones.create.step1') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18"
                 stroke="currentColor" fill="none" stroke-width="1.8">
              <path d="M12 5v14M5 12h14"/>
            </svg>
            <span>Nueva licitación</span>
          </a>

          {{-- Licitaciones IA --}}
          <a href="{{ route('licitaciones-ai.index') }}"
             class="nav__sublink {{ request()->routeIs('licitaciones-ai.index') || request()->routeIs('licitaciones-ai.show') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18"
                 stroke="currentColor" fill="none" stroke-width="1.8">
              <path d="M4 5h16v6H4z"/><path d="M4 17h8v2H4z"/><path d="M14 15h6v4h-6z"/>
            </svg>
            <span>Licitaciones IA</span>
          </a>

          <a href="{{ route('licitaciones-ai.tabla-global') }}"
             class="nav__sublink {{ request()->routeIs('licitaciones-ai.tabla-global') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18"
                 stroke="currentColor" fill="none" stroke-width="1.8">
              <rect x="3" y="4" width="18" height="16" rx="2"/>
              <path d="M9 4v16M15 4v16M3 12h18"/>
            </svg>
            <span>Tabla global IA</span>
          </a>

          {{-- NUEVO: PDFs de requisiciones (admin.licitacion-pdfs.*) --}}
          <a href="{{ route('admin.licitacion-pdfs.index') }}"
             class="nav__sublink {{ request()->routeIs('admin.licitacion-pdfs.*') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18"
                 stroke="currentColor" fill="none" stroke-width="1.8">
              <rect x="5" y="3" width="14" height="18" rx="2"/>
              <path d="M9 7h6M9 11h6M9 15h4"/>
            </svg>
            <span>PDFs de requisiciones</span>
          </a>

          {{-- NUEVO: Propuestas / comparativas (admin.licitacion-propuestas.*) --}}
          <a href="{{ route('admin.licitacion-propuestas.index') }}"
             class="nav__sublink {{ request()->routeIs('admin.licitacion-propuestas.*') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18"
                 stroke="currentColor" fill="none" stroke-width="1.8">
              <rect x="3" y="4" width="18" height="16" rx="2"/>
              <path d="M7 14h3.5a2 2 0 1 0 0-4H7z"/>
              <path d="M14.5 10H17a2 2 0 1 1 0 4h-2.5z"/>
            </svg>
            <span>Propuestas / comparativas</span>
          </a>
        </div>
      </details>

      <!-- Agenda -->
      <a href="{{ route('agenda.calendar') }}" class="nav__link {{ request()->routeIs('agenda.*') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
          <rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4M16 2v4"/><path d="M3 10h18"/>
        </svg>
        <span>Agenda</span>
      </a>

      <!-- Tickets -->
      <details class="nav__group" {{ request()->routeIs('tickets.*') || request()->routeIs('tickets.dashboard') ? 'open' : '' }}>
        <summary class="{{ request()->routeIs('tickets.*') || request()->routeIs('tickets.dashboard') ? 'is-active':'' }}">
          <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
            <path d="M3 9a2 2 0 0 0 0 6h2a2 2 0 0 1 0 4h10a2 2 0 0 1 0-4h2a2 2 0 0 0 0-6h-2a2 2 0 0 1 0-4H5a2 2 0 0 1 0 4H3z"/>
            <path d="M9 9h6M9 15h6"/>
          </svg>
          <span>Tickets</span>
          <svg class="nav__chev" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
        </summary>
        <div class="nav__submenu">
          <a href="{{ route('tickets.dashboard') }}" class="nav__sublink {{ request()->routeIs('tickets.dashboard') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M3 3h18v6H3zM3 15h18v6H3z"/><path d="M7 9V3M17 21v-6"/></svg>
            <span>Dashboard</span>
          </a>
          <a href="{{ route('tickets.index') }}" class="nav__sublink {{ request()->routeIs('tickets.index') || request()->routeIs('tickets.show') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><rect x="3" y="4" width="18" height="14" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/></svg>
            <span>Lista de tickets</span>
          </a>
          <a href="{{ route('tickets.create') }}" class="nav__sublink {{ request()->routeIs('tickets.create') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg>
            <span>Nuevo ticket</span>
          </a>
        </div>
      </details>

      <!-- Logística / Rutas -->
      <details class="nav__group" {{ request()->routeIs('routes.*') || request()->routeIs('routing.demo') ? 'open' : '' }}>
        <summary class="{{ request()->routeIs('routes.*') || request()->routeIs('routing.demo') ? 'is-active':'' }}">
          <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
            <path d="M3 5h4l3 7 4-4 7 2"/><circle cx="6" cy="5" r="2"/><circle cx="14" cy="8" r="2"/><circle cx="21" cy="10" r="2"/>
          </svg>
          <span>Logística</span>
          <svg class="nav__chev" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
        </summary>
        <div class="nav__submenu">
          <a href="{{ route('routes.index') }}" class="nav__sublink {{ request()->routeIs('routes.index') || request()->routeIs('routes.show') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
            <span>Rutas programadas</span>
          </a>
          <a href="{{ route('routes.create') }}" class="nav__sublink {{ request()->routeIs('routes.create') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg>
            <span>Nueva ruta</span>
          </a>
          <a href="{{ route('routing.demo') }}" class="nav__sublink {{ request()->routeIs('routing.demo') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M3 12h18M12 3v18"/></svg>
            <span>Demo / pruebas</span>
          </a>
        </div>
      </details>

<!-- ✅ NUEVO: WMS (Bodega) -->
<details class="nav__group" {{ request()->routeIs('admin.wms.*') ? 'open' : '' }}>
  <summary class="{{ request()->routeIs('admin.wms.*') ? 'is-active':'' }}">
    <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
      <path d="M3 10l9-6 9 6v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V10z"/>
      <path d="M7 22v-8h10v8"/>
      <path d="M9 14h6"/>
    </svg>
    <span>WMS (Bodega)</span>
    <svg class="nav__chev" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2">
      <path d="M9 6l6 6-6 6"/>
    </svg>
  </summary>

  <div class="nav__submenu">

    <a href="{{ route('admin.wms.home') }}" class="nav__sublink {{ request()->routeIs('admin.wms.home') ? 'is-active':'' }}">
      <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8">
        <path d="M3 12l9-9 9 9"/><path d="M9 21V9h6v12"/>
      </svg>
      <span>Inicio WMS</span>
    </a>

    <a href="{{ route('admin.wms.search.view') }}" class="nav__sublink {{ request()->routeIs('admin.wms.search.view') ? 'is-active':'' }}">
      <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8">
        <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>
      </svg>
      <span>Buscar + Llévame</span>
    </a>

    <a href="{{ route('admin.wms.pick.waves.page') }}" class="nav__sublink {{ request()->routeIs('admin.wms.pick.waves.page') || request()->routeIs('admin.wms.pick.show') ? 'is-active':'' }}">
      <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8">
        <path d="M3 7h18l-2 10a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3L3 7z"/>
        <path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/>
      </svg>
      <span>Picking (Waves)</span>
    </a>

    <a href="{{ route('admin.wms.locations.index') }}" class="nav__sublink {{ request()->routeIs('admin.wms.locations.*') ? 'is-active':'' }}">
      <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8">
        <path d="M12 21s7-4.5 7-11a7 7 0 0 0-14 0c0 6.5 7 11 7 11z"/>
        <circle cx="12" cy="10" r="2.5"/>
      </svg>
      <span>Ubicaciones</span>
    </a>

    <a href="{{ route('admin.wms.qr.print.batch') }}" class="nav__sublink {{ request()->routeIs('admin.wms.qr.print.*') ? 'is-active':'' }}">
      <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8">
        <rect x="3" y="3" width="8" height="8" rx="2"/><path d="M7 7h.01"/>
        <rect x="13" y="3" width="8" height="8" rx="2"/><path d="M17 7h.01"/>
        <rect x="3" y="13" width="8" height="8" rx="2"/><path d="M7 17h.01"/>
        <path d="M13 13h8v8h-8z"/>
      </svg>
      <span>Imprimir QRs</span>
    </a>

    <!-- ✅ NUEVO: Layout Builder -->
    <a href="{{ route('admin.wms.layout.editor') }}"
       class="nav__sublink {{ request()->routeIs('admin.wms.layout.*') ? 'is-active':'' }}">
      <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8">
        <rect x="3" y="3" width="8" height="8" rx="2"/>
        <rect x="13" y="3" width="8" height="8" rx="2"/>
        <rect x="3" y="13" width="8" height="8" rx="2"/>
        <rect x="13" y="13" width="8" height="8" rx="2"/>
      </svg>
      <span>Layout (Editor)</span>
    </a>

    <!-- ✅ NUEVO: Heatmap -->
    <a href="{{ route('admin.wms.heatmap.view') }}"
       class="nav__sublink {{ request()->routeIs('admin.wms.heatmap.*') ? 'is-active':'' }}">
      <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8">
        <path d="M4 18V6"/><path d="M8 18V10"/><path d="M12 18V4"/><path d="M16 18V12"/><path d="M20 18V8"/>
      </svg>
      <span>Heatmap (Calor)</span>
    </a>

    <!-- ✅ NUEVO: Entradas / Salidas -->
    <a href="{{ route('admin.wms.move.view') }}"
       class="nav__sublink {{ request()->routeIs('admin.wms.move.*') ? 'is-active':'' }}">
      <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8">
        <path d="M7 7h10"/><path d="M7 12h10"/><path d="M7 17h10"/>
        <path d="M4 4h16v16H4z" opacity=".35"/>
        <path d="M12 6v12" opacity=".35"/>
      </svg>
      <span>Entradas / Salidas</span>
    </a>

    <!-- ✅ NUEVO: Historial (Kardex) -->
    <a href="{{ route('admin.wms.movements.view') }}"
       class="nav__sublink {{ request()->routeIs('admin.wms.movements.*') ? 'is-active':'' }}">
      <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8">
        <path d="M8 6h13"/><path d="M8 10h13"/><path d="M8 14h13"/><path d="M8 18h13"/>
        <path d="M3 6h2"/><path d="M3 10h2"/><path d="M3 14h2"/><path d="M3 18h2"/>
      </svg>
      <span>Historial (Kardex)</span>
    </a>

  </div>
</details>


      <!-- Help Desk -->
      <details class="nav__group" {{ request()->routeIs('admin.help.*') ? 'open' : '' }}>
        <summary class="{{ request()->routeIs('admin.help.*') ? 'is-active':'' }}">
          <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
            <circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 1 1 5.82 1c0 2-3 2-3 4"/><path d="M12 17h.01"/>
          </svg>
          <span>Help&nbsp;Desk</span>
          <svg class="nav__chev" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
        </summary>
        <div class="nav__submenu">
          <a href="{{ route('admin.help.index') }}" class="nav__sublink {{ request()->routeIs('admin.help.index') || request()->routeIs('admin.help.show') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
            <span>Tickets de usuarios</span>
          </a>
          <form action="{{ route('admin.help.sync') }}" method="POST" class="nav__sublink" style="padding:0">
            @csrf
            <button type="submit" class="nav__sublink" style="width:100%; text-align:left; background:transparent; border:none; cursor:pointer;">
              <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M21 12a9 9 0 1 1-9-9"/><path d="M21 3v9h-9"/></svg>
              <span>Reindexar conocimiento</span>
            </button>
          </form>
        </div>
      </details>

      <!-- Correo -->
      <details class="nav__group" {{ request()->routeIs('mail.*') ? 'open' : '' }}>
        <summary class="{{ request()->routeIs('mail.*') ? 'is-active':'' }}">
          <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
            <path d="M4 4h16v16H4z"/><path d="M22 6l-10 7L2 6"/>
          </svg>
          <span>Correo</span>
          <svg class="nav__chev" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
        </summary>
        <div class="nav__submenu">
          <a href="{{ route('mail.index') }}" class="nav__sublink {{ request()->routeIs('mail.index') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M3 8l9 6 9-6"/><path d="M21 8v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8"/></svg>
            <span>Bandeja</span>
          </a>
          <a href="{{ route('mail.compose') }}" class="nav__sublink {{ request()->routeIs('mail.compose') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5l4 4L7 21l-4 1 1-4 12.5-14.5z"/></svg>
            <span>Redactar</span>
          </a>
          <a href="{{ route('mail.folder','INBOX') }}" class="nav__sublink {{ request()->is('mail/folder/INBOX') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M4 4h16v13H4z"/><path d="M4 13l4 4h8l4-4"/></svg>
            <span>INBOX</span>
          </a>
          <a href="{{ route('mail.folder','Sent') }}" class="nav__sublink {{ request()->is('mail/folder/Sent') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M22 2L11 13"/><path d="M22 2l-6 20-5-9-9-5 20-6z"/></svg>
            <span>Enviados</span>
          </a>
          <a href="{{ route('mail.folder','Drafts') }}" class="nav__sublink {{ request()->is('mail/folder/Drafts') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M3 5h18v14H3z"/><path d="M7 9h10"/></svg>
            <span>Borradores</span>
          </a>
          <a href="{{ route('mail.folder','Spam') }}" class="nav__sublink {{ request()->is('mail/folder/Spam') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M12 2l9 9-9 9-9-9 9-9z"/><path d="M9 9h6v6H9z"/></svg>
            <span>Spam</span>
          </a>
        </div>
      </details>

      <!-- Landing del Panel -->
      <a href="{{ route('panel.landing.index') }}" class="nav__link {{ request()->routeIs('panel.landing.*') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
          <path d="M12 2l9 5-9 5-9-5 9-5z"/>
          <path d="M3 12l9 5 9-5"/>
          <path d="M3 17l9 5 9-5"/>
        </svg>
        <span>Landing (Inicio web)</span>
      </a>

      <!-- Administración -->
      <details class="nav__group" {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.catalog.*') ? 'open' : '' }}>
        <summary class="{{ request()->routeIs('admin.users.*') || request()->routeIs('admin.catalog.*') ? 'is-active':'' }}">
          <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
            <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
            <path d="M3 7l9-4 9 4-9 4-9-4z"/>
          </svg>
          <span>Administración</span>
          <svg class="nav__chev" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
        </summary>
        <div class="nav__submenu">
          <a href="{{ route('admin.users.index') }}" class="nav__sublink {{ request()->routeIs('admin.users.*') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8">
              <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
            </svg>
            <span>Usuarios</span>
          </a>
          <a href="{{ route('admin.catalog.index') }}" class="nav__sublink {{ request()->routeIs('admin.catalog.*') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8">
              <rect x="3" y="4" width="18" height="14" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/>
            </svg>
            <span>Catálogo (admin)</span>
          </a>
        </div>
      </details>

      <!-- Debug -->
      <details class="nav__group" {{ request()->is('diag/http') || request()->is('debug/*') ? 'open' : '' }}>
        <summary class="{{ request()->is('diag/http') || request()->is('debug/*') ? 'is-active':'' }}">
          <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
            <path d="M3 3h18v6H3zM3 15h18v6H3z"/><path d="M7 9V3M17 21v-6"/>
          </svg>
          <span>Diagnóstico</span>
          <svg class="nav__chev" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
        </summary>
        <div class="nav__submenu">
          <a href="{{ url('/diag/http') }}" class="nav__sublink {{ request()->is('diag/http') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M12 3v12m0 0l-4-4m4 4l4-4"/><path d="M5 21h14"/></svg>
            <span>Ping servicios</span>
          </a>
          <a href="{{ url('/debug/skydropx/carriers') }}" class="nav__sublink {{ request()->is('debug/skydropx/carriers') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M3 8h18l-2 10H5L3 8z"/><path d="M16 8V6a2 2 0 0 0-2-2H10a2 2 0 0 0-2 2v2"/></svg>
            <span>Carriers SkydropX</span>
          </a>
          <a href="{{ url('/debug/skydropx/quote') }}" class="nav__sublink {{ request()->is('debug/skydropx/quote') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8"><path d="M3 12h18M7 12l-4 4 4 4"/></svg>
            <span>Quote SkydropX</span>
          </a>
        </div>
      </details>
      <!-- ===== FIN SOLO RUTAS INTERNAS ===== -->
    </nav>

    <form method="POST" action="{{ route('logout') }}" class="logout">
      @csrf
      <button type="submit" class="btn-logout">
        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
          <path d="M16 17l5-5-5-5"/>
          <path d="M21 12H9"/>
        </svg>
        <span>Cerrar sesión</span>
      </button>
    </form>
  </aside>


  <!-- Backdrop -->
  <div id="backdrop" class="backdrop" tabindex="-1" aria-hidden="true"></div>

  <!-- Contenedor principal -->
  <div class="shell" id="shell">
    <header class="topbar">
      <button id="btnSidebar" class="icon-btn" aria-label="Abrir menú">
        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
          <path d="M3 6h18M3 12h18M3 18h18"/>
        </svg>
      </button>

      <div class="topbar__title">@yield('header','Panel')</div>

      <div class="topbar__right">
        <!-- Notificaciones -->
        <div class="notif">
          <button id="btnNotif" class="icon-btn" aria-haspopup="true" aria-expanded="false" aria-label="Notificaciones">
            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
              <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"/>
              <path d="M9 21h6"/>
            </svg>
            <span id="notifBadge" class="dot" aria-hidden="true" style="display:none;"></span>
          </button>

          <div id="notifPanel" class="notif__panel" role="menu" aria-label="Panel de notificaciones">
            <div class="notif__head">
              <strong>Notificaciones</strong>
              <button id="btnCloseNotif" class="icon-btn" aria-label="Cerrar notificaciones">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="2">
                  <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
              </button>
            </div>
            <div id="notifList" class="notif__list">
              <div class="notif__empty">Cargando…</div>
            </div>
            <button type="button" id="btnMarkAll" class="notif__link">Marcar todas como leídas</button>
          </div>
        </div>

        <!-- Avatar PEQUEÑO (Topbar) -->
        <a href="{{ $profileHref }}" class="avatar-link" title="Ver mi perfil">
          <div class="avatar avatar--sm" aria-hidden="true">
            @if($avatarSrc)
              <img src="{{ $avatarSrc }}" alt="Avatar de {{ $nm }}" onerror="this.onerror=null;this.src='{{ $fallbackMp }}';">
              <span>{{ $ini }}</span>
            @else
              <img src="{{ $fallbackMp }}" alt="Avatar de {{ $nm }}">
              <span>{{ $ini }}</span>
            @endif
          </div>
        </a>
      </div>
    </header>

    <main id="content" class="content">
      @yield('content')
    </main>
  </div>

@stack('scripts')
<script>
  (function(){
    const shell      = document.getElementById('shell');
    const sidebar    = document.getElementById('sidebar');
    const sidebarNav = document.getElementById('sidebarNav');
    const backdrop   = document.getElementById('backdrop');
    const btnOpen    = document.getElementById('btnSidebar');
    const btnClose   = document.getElementById('btnCloseSidebar');

    const notifBtn     = document.getElementById('btnNotif');
    const notifPane    = document.getElementById('notifPanel');
    const notifClose   = document.getElementById('btnCloseNotif');
    const notifList    = document.getElementById('notifList');
    const notifBadge   = document.getElementById('notifBadge');
    const notifMarkAll = document.getElementById('btnMarkAll');

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const NOTIF_FEED_URL    = @json($notifFeedUrl);
    const NOTIF_READALL_URL = @json($notifReadAllUrl);
    const NOTIF_READONE_URL = @json($notifReadOneUrl);

    let sidebarOpen    = false;
    let notifLoaded    = false;
    let lastPayloadKey = null;

    /* ========== Sidebar ========== */
    const applyOverlay = () => {
      if (!backdrop || !shell || !sidebar) return;
      backdrop.classList.toggle('is-show', sidebarOpen);
      shell.classList.toggle('dimmed', sidebarOpen);
      document.body.classList.toggle('lock-scroll', sidebarOpen);
      sidebar.setAttribute('aria-hidden', sidebarOpen ? 'false' : 'true');
    };

    const openSidebar = () => {
      if (!sidebar || sidebarOpen) return;
      sidebarOpen = true;
      sidebar.classList.add('is-open');
      applyOverlay();
    };
    const closeSidebar = () => {
      if (!sidebar || !sidebarOpen) return;
      sidebarOpen = false;
      sidebar.classList.remove('is-open');
      applyOverlay();
    };

    if (btnOpen)  btnOpen.addEventListener('click', openSidebar);
    if (btnClose) btnClose.addEventListener('click', closeSidebar);
    if (backdrop) backdrop.addEventListener('click', closeSidebar);

    if (sidebarNav){
      sidebarNav.addEventListener('click', (e)=>{
        const summary = e.target.closest('summary');
        if (summary) { return; }
        const link = e.target.closest('a');
        if (!link) return;

        const href = link.getAttribute('href') || '';
        const keep = link.hasAttribute('data-keep-open');
        const isAnchorOnly = href.startsWith('#') || href === '' || href.startsWith('javascript');

        if (!keep && !isAnchorOnly) { closeSidebar(); }
      });
    }

    /* ========== Notificaciones ========== */

    function buildPayloadKey(payload){
      if (!payload || !Array.isArray(payload.items)) return '';
      return payload.items.map(n => n.id + (n.read_at ? '1' : '0')).join('|') + '|' + (payload.unread || 0);
    }

    function renderNotifItems(payload){
      if (!notifList) return;
      const items  = (payload && payload.items)  ? payload.items  : [];
      const unread = (payload && payload.unread) ? payload.unread : 0;

      // Badge con número
      if (notifBadge){
        if (unread > 0){
          notifBadge.style.display = '';
          notifBadge.textContent = unread > 9 ? '9+' : unread;
        } else {
          notifBadge.style.display = 'none';
          notifBadge.textContent = '';
        }
      }

      notifList.innerHTML = '';

      if (!items.length){
        const empty = document.createElement('div');
        empty.className = 'notif__empty';
        empty.textContent = 'No tienes notificaciones.';
        notifList.appendChild(empty);
        return;
      }

      items.forEach(n => {
        const level = n.status || 'info';
        let pillClass = 'pill--info';
        let pillText  = 'Info';
        if (level === 'warn'){ pillClass = 'pill--warn'; pillText = 'Aviso'; }
        else if (level === 'error'){ pillClass = 'pill--error'; pillText = 'Alerta'; }

        const item = document.createElement('div');
        item.className = 'notif__item ' + (n.read_at ? 'is-read' : 'is-unread');
        item.dataset.id = n.id;
        if (n.url) {
          item.dataset.url = n.url;
        }

        item.innerHTML = `
          <div class="notif__body">
            <span class="pill ${pillClass}">${pillText}</span>
            <div class="notif__main">
              <div class="notif__text">${(n.title || 'Notificación')}</div>
              <div class="notif__msg">${(n.message || '')}</div>
              <div class="notif__time">${(n.time || '')}</div>
            </div>
          </div>
          <button type="button"
                  class="notif__item-close"
                  aria-label="Marcar como leída"
                  data-id="${n.id}">&times;</button>
        `;
        notifList.appendChild(item);
      });
    }

    async function loadNotifications(){
      if (!NOTIF_FEED_URL || !notifList) return;

      if (!notifLoaded){
        notifList.innerHTML = '<div class="notif__empty">Cargando…</div>';
      }

      try{
        const res  = await fetch(NOTIF_FEED_URL, { headers:{ 'Accept':'application/json' } });
        const json = await res.json();

        if (!res.ok){
          throw new Error(json.message || 'Error al cargar notificaciones');
        }

        const key = buildPayloadKey(json);
        if (key !== lastPayloadKey){
          lastPayloadKey = key;
          renderNotifItems(json);
        }
        notifLoaded = true;
      }catch(e){
        console.error(e);
        notifList.innerHTML = '<div class="notif__empty">No se pudieron cargar las notificaciones.</div>';
      }
    }

    async function markAllNotifications(){
      if (!NOTIF_READALL_URL || !csrf) return;
      try{
        await fetch(NOTIF_READALL_URL, {
          method:'POST',
          headers:{
            'X-CSRF-TOKEN': csrf,
            'Accept':'application/json'
          }
        });
        loadNotifications();
      }catch(e){
        console.error(e);
      }
    }

    async function markOneNotification(id, itemEl){
      if (!NOTIF_READONE_URL || !csrf || !id) return;
      try{
        const url = NOTIF_READONE_URL.replace('__ID__', encodeURIComponent(id));
        await fetch(url, {
          method:'POST',
          headers:{
            'X-CSRF-TOKEN': csrf,
            'Accept':'application/json'
          }
        });

        if (itemEl){
          itemEl.classList.remove('is-unread');
          itemEl.classList.add('is-read');
        }

        loadNotifications();
      }catch(e){
        console.error(e);
      }
    }

    function openNotifPanel(){
      if (!notifPane || !notifBtn) return;
      notifPane.classList.add('is-open');
      notifPane.setAttribute('aria-hidden','false');
      notifBtn.setAttribute('aria-expanded','true');
      loadNotifications();
    }
    function closeNotifPanel(){
      if (!notifPane || !notifBtn) return;
      notifPane.classList.remove('is-open');
      notifPane.setAttribute('aria-hidden','true');
      notifBtn.setAttribute('aria-expanded','false');
    }

    if (notifBtn){
      notifBtn.addEventListener('click', function(e){
        e.stopPropagation();
        if (!notifPane) return;
        const isOpen = notifPane.classList.contains('is-open');
        if (isOpen){
          closeNotifPanel();
        }else{
          openNotifPanel();
        }
      });
    }

    if (notifClose){
      notifClose.addEventListener('click', function(e){
        e.stopPropagation();
        closeNotifPanel();
      });
    }

    if (notifMarkAll){
      notifMarkAll.addEventListener('click', function(e){
        e.preventDefault();
        markAllNotifications();
      });
    }

    // Click delegado en la lista:
    //  - Click en la X => solo marcar como leída
    //  - Click en el resto del item => marcar como leída y navegar a n.url
    if (notifList){
      notifList.addEventListener('click', function(e){
        const closeBtn = e.target.closest('.notif__item-close');
        if (closeBtn){
          const id     = closeBtn.getAttribute('data-id');
          const itemEl = closeBtn.closest('.notif__item');
          if (id){
            markOneNotification(id, itemEl);
          }
          e.stopPropagation();
          return;
        }

        const row = e.target.closest('.notif__item');
        if (!row) return;

        const id  = row.dataset.id;
        const url = row.dataset.url || '';

        if (!url) return; // si no hay URL solo la marcamos desde la X

        e.preventDefault();
        (async () => {
          if (id) {
            await markOneNotification(id, row);
          }
          window.location.href = url;
        })();
      });
    }

    document.addEventListener('click', function(e){
      if (!notifPane || !notifBtn) return;
      if (!notifPane.contains(e.target) && !notifBtn.contains(e.target)){
        closeNotifPanel();
      }
    });

    window.addEventListener('keydown', function(e){
      if (e.key === 'Escape'){
        closeNotifPanel();
        closeSidebar();
      }
    });

    // Polling cada 10s para "tiempo real"
    if (NOTIF_FEED_URL){
      loadNotifications();
      setInterval(loadNotifications, 10000);
    }
  })();
</script>

</body>
</html>
