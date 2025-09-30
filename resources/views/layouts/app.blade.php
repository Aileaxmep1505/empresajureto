<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Panel')</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/app-layout.css') }}?v={{ time() }}">
  @stack('styles')
</head>
<body class="app">
  <!-- Sidebar (Hamburguesa) -->
  <aside id="sidebar" class="sidebar" aria-hidden="true" aria-label="Menú lateral">
    <div class="sidebar__head">
      <div class="user">
        @php
          $u = auth()->user();
          $nm = $u->name ?? 'Usuario';
          $ini = mb_strtoupper(mb_substr($nm,0,1));
        @endphp
        <div class="avatar" aria-hidden="true"><span>{{ $ini }}</span></div>
        <div class="user__meta">
          <div class="user__name">{{ $nm }}</div>
          <div class="user__mail">{{ $u->email ?? 'correo@dominio.com' }}</div>
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

    <nav class="nav">
      <!-- Dashboard -->
      <a href="{{ route('dashboard') }}" class="nav__link {{ request()->routeIs('dashboard') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
          <path d="M3 12l9-9 9 9"/><path d="M9 21V9h6v12"/>
        </svg>
        <span>Dashboard</span>
      </a>

      <!-- Usuarios (solo admin) -->
      @role('admin')
      <a href="{{ route('admin.users.index') }}" class="nav__link {{ request()->routeIs('admin.users.*') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
          <path d="M16 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M22 21v-2a4 4 0 00-3-3.87"/>
          <path d="M16 3.13a4 4 0 010 7.75"/>
        </svg>
        <span>Usuarios</span>
      </a>
      @endrole

      <!-- Productos -->
      <a href="{{ route('products.index') }}" class="nav__link {{ request()->routeIs('products.*') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
          <rect x="3" y="4" width="18" height="14" rx="2"/>
          <path d="M7 8h10M7 12h10M7 16h6"/>
        </svg>
        <span>Productos</span>
      </a>

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
          <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        </svg>
        <span>Clientes</span>
      </a>

      <!-- Cotizaciones (lista) -->
      <a href="{{ route('cotizaciones.index') }}" class="nav__link {{ request()->routeIs('cotizaciones.*') && !request()->routeIs('cotizaciones.create') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8" aria-hidden="true">
          <rect x="3" y="4" width="18" height="14" rx="2"/>
          <path d="M7 8h10M7 12h10M7 16h6"/>
        </svg>
        <span>Cotizaciones</span>
      </a>

      <!-- Nueva cotización -->
      <a href="{{ route('cotizaciones.create') }}" class="nav__link {{ request()->routeIs('cotizaciones.create') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8" aria-hidden="true">
          <path d="M12 5v14M5 12h14"/>
        </svg>
        <span>Nueva cotización</span>
      </a>

      <!-- Ventas -->
      <a href="{{ route('ventas.index') }}" class="nav__link {{ request()->routeIs('ventas.*') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8" aria-hidden="true">
          <path d="M3 7h18l-2 10a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3L3 7z"/>
          <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
        </svg>
        <span>Ventas</span>
      </a>
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

  <!-- Backdrop (solo para SIDEBAR) -->
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
            <span class="dot" aria-hidden="true"></span>
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
            <div class="notif__list">
              <div class="notif__item">
                <span class="pill pill--info">Info</span>
                <div class="notif__text">Bienvenido, {{ $nm }}</div>
                <div class="notif__time">Ahora</div>
              </div>
              <div class="notif__item">
                <span class="pill pill--warn">Aviso</span>
                <div class="notif__text">Recuerda completar tu perfil.</div>
                <div class="notif__time">Hace 1 h</div>
              </div>
            </div>
            <a href="#" class="notif__link">Ver todas</a>
          </div>
        </div>

        <!-- Avatar pequeño -->
        <div class="avatar avatar--sm" title="{{ $nm }}" aria-hidden="true"><span>{{ $ini }}</span></div>
      </div>
    </header>

    <main id="content" class="content">
      @yield('content')
    </main>
  </div>

  @stack('scripts')
  <script>
    (function(){
      const mqSmall   = window.matchMedia('(max-width: 1023px)');
      const shell     = document.getElementById('shell');
      const sidebar   = document.getElementById('sidebar');
      const backdrop  = document.getElementById('backdrop');
      const btnOpen   = document.getElementById('btnSidebar');
      const btnClose  = document.getElementById('btnCloseSidebar');

      const notifBtn  = document.getElementById('btnNotif');
      const notifPane = document.getElementById('notifPanel');
      const notifClose= document.getElementById('btnCloseNotif');

      // Estado SOLO del sidebar; notificaciones no usan backdrop ni blur
      let sidebarOpen = false;

      const applyBackdropForSidebar = () => {
        backdrop.classList.toggle('is-show', sidebarOpen);
        // Blur del contenido SOLO en pantallas pequeñas cuando el SIDEBAR está abierto
        shell.classList.toggle('xs-blur', sidebarOpen && mqSmall.matches);
      };

      const openSidebar = () => {
        sidebarOpen = true;
        sidebar.classList.add('is-open');
        sidebar.setAttribute('aria-hidden','false');
        applyBackdropForSidebar();
      };
      const closeSidebar = () => {
        sidebarOpen = false;
        sidebar.classList.remove('is-open');
        sidebar.setAttribute('aria-hidden','true');
        applyBackdropForSidebar();
      };

      btnOpen.addEventListener('click', openSidebar);
      btnClose.addEventListener('click', closeSidebar);
      backdrop.addEventListener('click', closeSidebar);

      // Notificaciones: sin backdrop, sin blur
      const closeNotif = () => {
        notifPane.classList.remove('is-open');
        notifBtn.setAttribute('aria-expanded','false');
      };
      notifBtn.addEventListener('click', (e)=>{
        e.stopPropagation();
        const open = notifPane.classList.toggle('is-open');
        notifBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
      notifClose.addEventListener('click', (e)=>{
        e.stopPropagation();
        closeNotif();
      });

      // Click-away para notificaciones
      document.addEventListener('click', (e)=>{
        const withinPanel = notifPane.contains(e.target);
        const withinButton = notifBtn.contains(e.target);
        if (!withinPanel && !withinButton) closeNotif();
      });

      // Cerrar con ESC
      window.addEventListener('keydown', (e)=>{
        if (e.key === 'Escape') {
          closeNotif();
          closeSidebar();
        }
      });

      // Re-evaluar blur si cambia el tamaño
      mqSmall.addEventListener?.('change', applyBackdropForSidebar);
    })();
  </script>
</body>
</html>
