{{-- resources/views/dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300..700&display=swap"/>

    <style>
      :root{
        --dm-bg:#f4f7fc;
        --dm-surface:rgba(255,255,255,.72);
        --dm-surface-strong:rgba(255,255,255,.88);
        --dm-border:#e6edf7;
        --dm-border-strong:#d8e2f0;
        --dm-text:#1f2a44;
        --dm-muted:#90a0b8;
        --dm-muted-2:#64748b;
        --dm-blue:#1877f2;
        --dm-blue-2:#2563eb;
        --dm-blue-3:#60a5fa;
        --dm-indigo:#7c6cf2;
        --dm-cyan:#0ea5e9;
        --dm-shadow:0 10px 28px rgba(80,104,140,.08);
        --dm-shadow-hover:0 22px 40px rgba(24,119,242,.16);

        --dm-name-1:#6d28d9;
        --dm-name-2:#9333ea;
        --dm-name-3:#2563eb;
        --dm-name-4:#06b6d4;
      }

      *{
        box-sizing:border-box;
      }

      html,body{
        margin:0;
        padding:0;
        min-height:100%;
        font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
        background:var(--dm-bg);
        color:var(--dm-text);
      }

      .menu-page{
        position:relative;
        width:100%;
        min-height:100vh;
        padding:28px 34px 38px 24px;
        overflow:hidden;
        background:
          radial-gradient(circle at left bottom, rgba(191,219,254,.42) 0%, transparent 24%),
          radial-gradient(circle at 78% 10%, rgba(219,234,254,.55) 0%, transparent 26%),
          linear-gradient(135deg, #f4f7fc 0%, #f7f9fc 52%, #f3f6fb 100%);
      }

      .menu-page::before{
        content:"";
        position:absolute;
        inset:0;
        pointer-events:none;
        background-image:radial-gradient(circle, rgba(148,163,184,.14) 1px, transparent 1px);
        background-size:44px 44px;
        opacity:.75;
      }

      .menu-glow{
        position:absolute;
        border-radius:999px;
        pointer-events:none;
        filter:blur(10px);
        opacity:.75;
      }

      .menu-glow.g1{
        width:440px;
        height:440px;
        left:-140px;
        bottom:-140px;
        background:radial-gradient(circle, rgba(191,219,254,.60) 0%, transparent 70%);
      }

      .menu-glow.g2{
        width:520px;
        height:520px;
        right:10%;
        top:-180px;
        background:radial-gradient(circle, rgba(219,234,254,.72) 0%, transparent 72%);
      }

      .menu-wrap{
        position:relative;
        z-index:2;
        width:min(100%, 1720px);
        margin:0 auto;
        padding-left:44px;
        padding-right:44px;
      }

      .menu-hero{
        text-align:center;
        margin-bottom:28px;
      }

      .menu-date{
        margin:0 0 10px;
        font-size:12px;
        font-weight:600;
        letter-spacing:.16em;
        text-transform:uppercase;
        color:var(--dm-blue);
      }

      .menu-title{
        margin:0;
        font-size:clamp(2rem, 2.5vw, 3rem);
        line-height:1.05;
        font-family:inherit;
        font-weight:800;
        letter-spacing:-0.02em;
        color:var(--dm-text);
        display:flex;
        align-items:baseline;
        justify-content:center;
        gap:10px;
        flex-wrap:wrap;
      }

      .menu-greeting{
        display:inline-flex;
        align-items:baseline;
        color:var(--dm-text);
      }

      .menu-name{
        display:inline-flex;
        align-items:baseline;
      }

      .menu-sub{
        margin:14px auto 0;
        max-width:860px;
        color:#7084a3;
        font-size:15px;
        line-height:1.6;
        font-weight:600;
        min-height:26px;
      }

      .animated-gradient-text{
        position:relative;
        display:inline-flex;
        align-items:baseline;
        justify-content:center;
        vertical-align:baseline;
      }

      .animated-gradient-text .text-content{
        position:relative;
        z-index:2;
        display:inline-block;
        background-image:linear-gradient(90deg, var(--dm-name-1), var(--dm-name-2), var(--dm-name-3), var(--dm-name-4), var(--dm-name-1));
        background-size:320% 100%;
        background-repeat:repeat;
        -webkit-background-clip:text;
        background-clip:text;
        -webkit-text-fill-color:transparent;
        animation:gradientTextMove 5s linear infinite;
        will-change:background-position;
        filter:drop-shadow(0 4px 12px rgba(109,40,217,.18));
      }

      @keyframes gradientTextMove{
        0%   { background-position:0% 50%; }
        100% { background-position:100% 50%; }
      }

      .menu-search-wrap{
        display:flex;
        justify-content:center;
        margin-top:22px;
      }

      .menu-search{
        position:relative;
        width:min(100%, 780px);
      }

      .menu-search::before{
        content:"";
        position:absolute;
        inset:0;
        border-radius:999px;
        background:linear-gradient(135deg, rgba(255,255,255,.92), rgba(248,251,255,.80));
        border:1px solid rgba(213,224,241,.95);
        box-shadow:
          0 16px 36px rgba(85,108,144,.07),
          inset 0 1px 0 rgba(255,255,255,.9);
        backdrop-filter:blur(10px);
      }

      .menu-search .icon{
        position:absolute;
        left:18px;
        top:50%;
        transform:translateY(-50%);
        width:36px;
        height:36px;
        border-radius:999px;
        display:flex;
        align-items:center;
        justify-content:center;
        color:#5f7290;
        background:linear-gradient(180deg, #eef4ff 0%, #dfeaff 100%);
        box-shadow:inset 0 1px 0 rgba(255,255,255,.8);
        font-size:20px;
        pointer-events:none;
        z-index:2;
      }

      .menu-search input{
        position:relative;
        z-index:2;
        width:100%;
        height:62px;
        border:none;
        outline:none;
        border-radius:999px;
        padding:0 58px 0 68px;
        background:transparent;
        color:#5d6f8b;
        font-size:15px;
        font-weight:500;
        letter-spacing:.01em;
      }

      .menu-search input::placeholder{
        color:#93a3ba;
        font-weight:500;
      }

      .menu-search:focus-within::before{
        border-color:#bfd7ff;
        box-shadow:
          0 18px 40px rgba(24,119,242,.10),
          0 0 0 4px rgba(24,119,242,.07),
          inset 0 1px 0 rgba(255,255,255,.9);
      }

      .menu-clear{
        position:absolute;
        right:12px;
        top:50%;
        transform:translateY(-50%);
        z-index:2;
        width:38px;
        height:38px;
        border:none;
        border-radius:999px;
        background:transparent;
        color:#8da0bc;
        display:none;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        transition:.18s ease;
      }

      .menu-clear.show{
        display:flex;
      }

      .menu-clear:hover{
        background:#edf4ff;
        color:#45648d;
      }

      .menu-sections{
        display:grid;
        gap:34px;
        width:100%;
        margin:0;
      }

      .menu-section{
        display:grid;
        gap:14px;
      }

      .menu-section-title{
        margin:0;
        padding-left:8px;
        font-size:13px;
        font-weight:600;
        letter-spacing:.15em;
        text-transform:uppercase;
        color:#8fa1bb;
      }

      .menu-grid{
        display:grid;
        gap:22px;
        width:100%;
      }

      .menu-card{
        position:relative;
        display:block;
        min-height:150px;
        padding:20px 16px 18px;
        text-decoration:none;
        border-radius:24px;
        background:rgba(255,255,255,.70);
        border:1px solid rgba(227,235,245,.95);
        box-shadow:var(--dm-shadow);
        backdrop-filter:blur(10px);
        overflow:hidden;
        isolation:isolate;
        outline:none;
        transition:
          transform .18s cubic-bezier(.2,.8,.2,1),
          box-shadow .18s cubic-bezier(.2,.8,.2,1),
          border-color .14s ease,
          color .14s ease,
          background .14s ease;
        animation:menuCardIn .25s cubic-bezier(.25,.46,.45,.94) both;
        width:100%;
      }

      .menu-card::before{
        content:"";
        position:absolute;
        top:50%;
        left:50%;
        width:180%;
        aspect-ratio:1/1;
        border-radius:50%;
        background:var(--dm-blue);
        transform:translate(-50%, -50%) scale(0);
        transform-origin:center;
        transition:transform .58s cubic-bezier(.1,.4,.2,1);
        z-index:0;
        pointer-events:none;
      }

      .menu-card::after{
        content:"";
        position:absolute;
        inset:0;
        border-radius:24px;
        background:linear-gradient(120deg, rgba(255,255,255,.12), rgba(255,255,255,0));
        opacity:0;
        transition:opacity .28s ease;
        z-index:0;
        pointer-events:none;
      }

      .menu-card:hover,
      .menu-card:focus-visible{
        transform:translateY(-4px);
        box-shadow:var(--dm-shadow-hover);
        border-color:rgb(24, 119, 242);
      }

      .menu-card:hover::before,
      .menu-card:focus-visible::before{
        transform:translate(-50%, -50%) scale(1);
      }

      .menu-card:hover::after,
      .menu-card:focus-visible::after{
        opacity:1;
      }

      .menu-card-body{
        position:relative;
        z-index:2;
        min-height:110px;
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        gap:14px;
        text-align:center;
      }

      .menu-icon{
        width:64px;
        height:64px;
        border-radius:18px;
        display:flex;
        align-items:center;
        justify-content:center;
        background:linear-gradient(180deg, #edf3ff 0%, #dfeaff 100%);
        color:var(--dm-blue);
        transition:
          color .34s ease,
          background .34s ease,
          transform .28s ease,
          box-shadow .28s ease;
      }

      .menu-label{
        color:#617590;
        font-size:15px;
        line-height:1.3;
        font-weight:500;
        transition:color .34s ease;
      }

      .menu-card:hover .menu-label,
      .menu-card:focus-visible .menu-label{
        color:#ffffff;
      }

      .menu-card:hover .menu-icon,
      .menu-card:focus-visible .menu-icon{
        background:rgba(255,255,255,.12);
        color:#ffffff;
        transform:scale(1.08);
        box-shadow:inset 0 0 0 1px rgba(255,255,255,.18);
      }

      .menu-badge{
        position:absolute;
        top:12px;
        right:12px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-height:24px;
        padding:0 10px;
        border-radius:999px;
        background:linear-gradient(90deg, #3b82f6, #6366f1);
        color:#fff;
        font-size:10px;
        font-weight:600;
        letter-spacing:.08em;
        text-transform:uppercase;
        box-shadow:0 8px 18px rgba(79,70,229,.24);
        z-index:3;
      }

      .menu-empty{
        display:none;
        padding:26px 18px;
        border-radius:20px;
        background:rgba(255,255,255,.56);
        border:1px dashed var(--dm-border-strong);
        text-align:center;
        color:#7d90ac;
        font-size:14px;
        font-weight:500;
      }

      .menu-empty.show{
        display:block;
      }

      .msi{
        font-family:'Material Symbols Outlined';
        font-weight:400;
        font-style:normal;
        font-size:24px;
        line-height:1;
        letter-spacing:normal;
        text-transform:none;
        display:inline-block;
        white-space:nowrap;
        word-wrap:normal;
        direction:ltr;
        -webkit-font-feature-settings:'liga';
        -webkit-font-smoothing:antialiased;
      }

      @keyframes menuCardIn{
        from{ opacity:0; transform:translateY(12px); }
        to{ opacity:1; transform:translateY(0); }
      }

      @media (min-width:1200px){
        .menu-wrap{
          padding-left:58px;
          padding-right:58px;
        }

        .menu-grid{
          grid-template-columns:repeat(5, minmax(0, 1fr));
        }

        .menu-grid.is-single{
          grid-template-columns:minmax(260px, 540px);
          justify-content:center;
        }

        .menu-grid.is-double{
          grid-template-columns:repeat(2, minmax(260px, 540px));
          justify-content:center;
        }

        .menu-grid.is-triple{
          grid-template-columns:repeat(3, minmax(260px, 1fr));
          justify-content:center;
        }

        .menu-grid.is-quad{
          grid-template-columns:repeat(4, minmax(220px, 1fr));
        }
      }

      @media (min-width:1500px){
        .menu-wrap{
          padding-left:76px;
          padding-right:76px;
        }
      }

      @media (max-width:1100px){
        .menu-wrap{
          padding-left:20px;
          padding-right:20px;
        }

        .menu-grid{
          grid-template-columns:repeat(4, minmax(0, 1fr));
          gap:16px;
        }
      }

      @media (max-width:820px){
        .menu-page{
          padding:22px 14px 30px;
        }

        .menu-wrap{
          padding-left:0;
          padding-right:0;
          margin:0 auto;
        }

        .menu-hero{
          margin-bottom:24px;
        }

        .menu-title{
          font-size:clamp(1.9rem, 7vw, 2.4rem);
          line-height:1.08;
          gap:8px;
        }

        .menu-sub{
          font-size:13px;
          max-width:430px;
        }

        .menu-search-wrap{
          margin-top:18px;
        }

        .menu-search{
          width:min(100%, 100%);
        }

        .menu-search input{
          height:58px;
          font-size:14px;
        }

        .menu-sections{
          gap:26px;
        }

        .menu-grid{
          grid-template-columns:repeat(3, minmax(0, 1fr));
          gap:14px;
        }

        .menu-card{
          min-height:132px;
          padding:18px 10px 14px;
          border-radius:22px;
        }

        .menu-card::after{
          border-radius:22px;
        }

        .menu-card-body{
          min-height:96px;
          gap:12px;
        }

        .menu-icon{
          width:56px;
          height:56px;
          border-radius:16px;
        }

        .menu-label{
          font-size:14px;
          line-height:1.22;
        }

        .menu-badge{
          top:8px;
          right:8px;
          min-height:20px;
          padding:0 8px;
          font-size:9px;
        }
      }

      @media (max-width:520px){
        .menu-page{
          padding:18px 12px 28px;
        }

        .menu-wrap{
          max-width:100%;
        }

        .menu-hero{
          margin-bottom:20px;
        }

        .menu-date{
          font-size:11px;
          margin-bottom:10px;
        }

        .menu-title{
          font-size:clamp(1.55rem, 8vw, 2.1rem);
          line-height:1.1;
          gap:6px;
        }

        .menu-sub{
          margin-top:10px;
          font-size:12px;
          line-height:1.5;
          max-width:320px;
        }

        .menu-search input{
          height:56px;
          font-size:14px;
          padding-left:64px;
          padding-right:46px;
        }

        .menu-search .icon{
          left:14px;
          width:34px;
          height:34px;
          font-size:19px;
        }

        .menu-section-title{
          padding-left:2px;
          font-size:12px;
          letter-spacing:.13em;
        }

        .menu-grid{
          grid-template-columns:repeat(2, minmax(0, 1fr));
          gap:14px;
        }

        .menu-card{
          min-height:130px;
          padding:16px 8px 12px;
          border-radius:22px;
        }

        .menu-card::after{
          border-radius:22px;
        }

        .menu-card-body{
          min-height:92px;
          gap:10px;
        }

        .menu-icon{
          width:52px;
          height:52px;
          border-radius:16px;
        }

        .menu-label{
          font-size:12px;
          line-height:1.2;
          font-weight:500;
        }

        .menu-badge{
          top:8px;
          right:8px;
          min-height:19px;
          padding:0 7px;
          font-size:8px;
          letter-spacing:.07em;
        }
      }
    </style>
</head>
<body>
@php
    $userName = auth()->user()->name ?? 'Usuario';
    $firstName = explode(' ', trim($userName))[0] ?? 'Usuario';

    \Carbon\Carbon::setLocale('es');

    $now = now()->timezone(config('app.timezone', 'America/Mexico_City'));
    $today = $now->translatedFormat('l, j \d\e F');
    $hour = $now->hour;

    if ($hour >= 5 && $hour < 12) {
        $greeting = 'Buenos días,';
    } elseif ($hour >= 12 && $hour < 19) {
        $greeting = 'Buenas tardes,';
    } else {
        $greeting = 'Buenas noches,';
    }

    $safePhrase = !empty($inspirationalPhrase)
        ? $inspirationalPhrase
        : 'Hoy es un gran día para avanzar con enfoque, claridad y determinación.';

    $u  = auth()->user();
    $isAdmin   = $u && method_exists($u,'hasRole') ? $u->hasRole('admin') : false;
    $isManager = $u && method_exists($u,'hasRole') ? $u->hasRole('manager') : false;
    $restrictManager = $isManager && !$isAdmin;

    $sections = [];

    $makeItem = function ($label, $icon, $routeName = null, $url = null, $badge = null) {
        if ($routeName && \Illuminate\Support\Facades\Route::has($routeName)) {
            return [
                'label' => $label,
                'icon'  => $icon,
                'url'   => route($routeName),
                'badge' => $badge,
            ];
        }

        if (!$routeName && $url) {
            return [
                'label' => $label,
                'icon'  => $icon,
                'url'   => $url,
                'badge' => $badge,
            ];
        }

        return null;
    };

    if ($restrictManager) {
        $managerSection = array_values(array_filter([
            $makeItem('Mi Perfil', 'account_circle', 'profile.show'),
            $makeItem('Part. contable', 'monitoring', 'partcontable.index'),
            $makeItem('Documentación de altas', 'description', 'alta.docs.index'),
        ]));

        if (count($managerSection)) {
            $sections[] = ['title' => 'Accesos', 'items' => $managerSection];
        }
    } else {
        $finanzasVentas = array_values(array_filter([
            $makeItem('Cotizaciones', 'calculate', 'cotizaciones.index'),
            $makeItem('Ventas', 'shopping_cart', 'ventas.index'),
            $makeItem('Facturas', 'receipt_long', 'manual_invoices.index'),
            $makeItem('Compras y Ventas', 'article', 'publications.index'),
            $makeItem('Part. contable', 'monitoring', 'partcontable.index'),
            $makeItem('Gastos', 'receipt', 'expenses.index'),
        ]));

        $inventarioProductos = array_values(array_filter([
            $makeItem('Productos', 'deployed_code', 'admin.catalog.index'),
            $makeItem('Catálogo', 'view_in_ar', 'products.index'),
            $makeItem('Fichas técnicas', 'list_alt', 'tech-sheets.index'),
            $makeItem('Almacén', 'warehouse', 'admin.wms.home'),
        ]));

        $operaciones = array_values(array_filter([
            $makeItem('Vehículos', 'local_shipping', 'vehicles.index'),
            $makeItem('Logística', 'alt_route', 'routes.index'),
            $makeItem('Agenda', 'calendar_month', 'agenda.calendar'),
        ]));

        $clientesComunicacion = array_values(array_filter([
            $makeItem('Clientes', 'groups', 'clients.index'),
            $makeItem('Proveedores', 'domain', 'providers.index'),
            $makeItem('WhatsApp', 'chat', 'admin.whatsapp.conversations', null, 'Nuevo'),
            $makeItem('Help Desk', 'support_agent', 'admin.help.index'),
            $makeItem('Correo', 'mail', 'mail.index'),
            $makeItem('Mi Perfil', 'account_circle', 'profile.show'),
        ]));

        $licitaciones = array_values(array_filter([
            $makeItem('Licitaciones', 'gavel', 'licitaciones.index'),
            $makeItem('Nueva licitación', 'post_add', 'licitaciones.create.step1'),
            $makeItem('Licitaciones IA', 'neurology', 'licitaciones-ai.index'),
            $makeItem('Tabla global IA', 'table_chart', 'licitaciones-ai.tabla-global'),
            $makeItem('PDFs / Bases', 'attach_file', 'admin.licitacion-pdfs.index'),
            $makeItem('Propuestas / comparativas', 'query_stats', 'admin.licitacion-propuestas.index'),
        ]));

        $tickets = array_values(array_filter([
            $makeItem('Tickets', 'confirmation_number', 'tickets.index'),
            $makeItem('Mis tickets', 'person', 'tickets.my'),
        ]));

        $contabilidadAdmin = array_values(array_filter([
            $makeItem('Contabilidad', 'monitoring', 'accounting.dashboard', null, 'Nuevo'),
            $makeItem('Documentación', 'folder_open', null, url('/confidential/vault/6')),
            $makeItem('Documentación de altas', 'folder_managed', 'alta.docs.index'),
            $makeItem('Landing', 'language', 'panel.landing.index'),
            $makeItem('Usuarios', 'manage_accounts', 'admin.users.index'),
            $makeItem('Pedidos web', 'shopping_bag', 'admin.orders.index'),
        ]));

        if (count($finanzasVentas))       $sections[] = ['title' => 'Finanzas y Ventas', 'items' => $finanzasVentas];
        if (count($inventarioProductos))  $sections[] = ['title' => 'Inventario y Productos', 'items' => $inventarioProductos];
        if (count($operaciones))          $sections[] = ['title' => 'Operaciones', 'items' => $operaciones];
        if (count($clientesComunicacion)) $sections[] = ['title' => 'Clientes y Comunicación', 'items' => $clientesComunicacion];
        if (count($licitaciones))         $sections[] = ['title' => 'Licitaciones', 'items' => $licitaciones];
        if (count($tickets))              $sections[] = ['title' => 'Tickets', 'items' => $tickets];
        if (count($contabilidadAdmin))    $sections[] = ['title' => 'Administración y Control', 'items' => $contabilidadAdmin];
    }
@endphp

<div class="menu-page">
    <div class="menu-glow g1"></div>
    <div class="menu-glow g2"></div>

    <div class="menu-wrap">
        <div class="menu-hero">
            <p class="menu-date">{{ mb_strtoupper($today) }}</p>

            <h1 class="menu-title">
                <span class="menu-greeting">{{ $greeting }}</span>
                <span class="animated-gradient-text menu-name">
                    <span class="text-content">{{ $firstName }}</span>
                </span>
            </h1>

            <p class="menu-sub">
                {{ $safePhrase }}
            </p>

            <div class="menu-search-wrap">
                <div class="menu-search">
                    <span class="msi icon">search</span>
                    <input type="text" id="menuSearch" placeholder="Buscar módulo..." autocomplete="off">
                    <button type="button" id="menuSearchClear" class="menu-clear" aria-label="Limpiar búsqueda">
                        <span class="msi" style="font-size:18px;">close</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="menu-sections" id="menuSections">
            @php $anim = 0; @endphp

            @foreach($sections as $section)
                <section class="menu-section js-menu-section" data-section="{{ \Illuminate\Support\Str::lower($section['title']) }}">
                    <h3 class="menu-section-title">{{ $section['title'] }}</h3>

                    <div class="menu-grid js-menu-grid">
                        @foreach($section['items'] as $item)
                            @php $anim++; @endphp
                            <a
                                href="{{ $item['url'] }}"
                                class="menu-card js-menu-item"
                                data-label="{{ \Illuminate\Support\Str::lower($item['label']) }}"
                                data-section="{{ \Illuminate\Support\Str::lower($section['title']) }}"
                                style="animation-delay: {{ $anim * 0.025 }}s;"
                                title="{{ $item['label'] }}"
                            >
                                @if(!empty($item['badge']))
                                    <span class="menu-badge">{{ $item['badge'] }}</span>
                                @endif

                                <div class="menu-card-body">
                                    <div class="menu-icon">
                                        <span class="msi">{{ $item['icon'] }}</span>
                                    </div>
                                    <div class="menu-label">{{ $item['label'] }}</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <div class="menu-empty" id="menuEmpty">
                No se encontraron módulos con esa búsqueda.
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('menuSearch');
    const clear = document.getElementById('menuSearchClear');
    const items = Array.from(document.querySelectorAll('.js-menu-item'));
    const sections = Array.from(document.querySelectorAll('.js-menu-section'));
    const empty = document.getElementById('menuEmpty');

    function setGridState(grid, count) {
        if (!grid) return;

        grid.classList.remove('is-single', 'is-double', 'is-triple', 'is-quad');

        if (window.innerWidth < 1200) return;

        if (count === 1) grid.classList.add('is-single');
        else if (count === 2) grid.classList.add('is-double');
        else if (count === 3) grid.classList.add('is-triple');
        else if (count === 4) grid.classList.add('is-quad');
    }

    function applyFilter() {
        const term = (input.value || '').trim().toLowerCase();
        let visible = 0;

        clear.classList.toggle('show', term.length > 0);

        items.forEach((item) => {
            const label = item.dataset.label || '';
            const section = item.dataset.section || '';
            const match = !term || label.includes(term) || section.includes(term);

            item.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        sections.forEach((section) => {
            const visibleItems = Array.from(section.querySelectorAll('.js-menu-item')).filter(item => item.style.display !== 'none');
            const grid = section.querySelector('.js-menu-grid');

            section.style.display = visibleItems.length ? '' : 'none';
            setGridState(grid, visibleItems.length);
        });

        empty.classList.toggle('show', visible === 0);
    }

    input.addEventListener('input', applyFilter);

    clear.addEventListener('click', function () {
        input.value = '';
        input.focus();
        applyFilter();
    });

    window.addEventListener('resize', applyFilter);

    applyFilter();
});
</script>
</body>
</html>