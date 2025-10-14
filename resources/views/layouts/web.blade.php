<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>@yield('title','Jureto')</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="{{ asset('css/app.css') }}" rel="stylesheet">
  <style>
    :root { --ink:#14206a; --accent:#e4ba16; --bg:#f7f7fb; }
    body { font-family: ui-sans-serif, system-ui, -apple-system; background: var(--bg); color:#111; }
    header, footer { background:white; box-shadow: 0 8px 24px rgba(0,0,0,.06); }
    .container { max-width:1100px; margin:0 auto; padding: 20px; }
    .nav a { color: var(--ink); text-decoration:none; margin-right:16px; font-weight:600; }
    .btn { background: var(--ink); color:white; padding:10px 16px; border-radius:12px; border:none; }
    .btn-line { border:1px solid var(--ink); color:var(--ink); background:transparent; padding:10px 16px; border-radius:12px; }
    .card { background:white; border-radius:16px; padding:16px; box-shadow: 0 10px 30px rgba(20,32,106,.08); }
    .grid { display:grid; gap:16px; grid-template-columns: repeat(12, 1fr); }
    .col-12 { grid-column: span 12 / span 12; }
    .col-4 { grid-column: span 4 / span 4; }
    .col-6 { grid-column: span 6 / span 6; }
    @media (max-width: 900px){ .col-4, .col-6 { grid-column: span 12; } }
    .hero { padding: 48px 0; display:flex; align-items:center; }
    .hero h1 { color: var(--ink); font-size: clamp(28px, 3.6vw, 48px); margin:0 0 8px; }
    .hero p { max-width: 680px; font-size: 1.1rem; opacity:.85; }
  </style>
</head>
<body>
<header>
  <div class="container" style="display:flex; align-items:center; justify-content:space-between;">
    <a href="{{ route('web.home') }}" class="nav-brand" style="display:flex; align-items:center; gap:10px;">
      <img src="{{ asset('images/logo.png') }}" alt="Logo" style="height:38px;">
      <strong style="color:var(--ink)">Jureto</strong>
    </a>
    <nav class="nav" aria-label="Main">
      <a href="{{ route('web.home') }}">Inicio</a>
      <a href="{{ route('web.ventas.index') }}">Ventas</a>
      <a href="{{ route('web.contacto') }}">Contacto</a>

      @auth('customer')
        <form action="{{ route('customer.logout') }}" method="POST" style="display:inline">@csrf
          <button class="btn-line" style="margin-left:8px">Salir</button>
        </form>
      @else
        <a href="{{ route('customer.login') }}">Entrar</a>
        <a class="btn" href="{{ route('customer.register') }}">Crear cuenta</a>
      @endauth
    </nav>
  </div>
</header>

<main class="container" style="padding:28px 20px;">
  @if(session('ok'))
    <div class="card" style="border-left:4px solid var(--accent); margin-bottom:16px;">{{ session('ok') }}</div>
  @endif
  @yield('content')
</main>

<footer>
  <div class="container" style="display:flex; justify-content:space-between; align-items:center; padding:16px 0;">
    <small>© {{ date('Y') }} Jureto</small>
    <small style="opacity:.7">Hecho con Laravel</small>
  </div>
</footer>
</body>
</html>
