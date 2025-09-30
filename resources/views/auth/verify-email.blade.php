<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Verifica tu correo</title>
  <link rel="stylesheet" href="{{ asset('css/login-ring.css') }}?v={{ time() }}">
</head>
<body>
  <div class="ring">
    <i style="--clr:#00ff0a;"></i>
    <i style="--clr:#ff0057;"></i>
    <i style="--clr:#fffd44;"></i>
    <div class="login">
      <h2>Verifica tu correo</h2>

      @if (session('status'))
        <div class="alert success">{{ session('status') }}</div>
      @endif

      <p style="color:#fff; opacity:.9; text-align:center">
        Te enviamos un enlace a tu correo. Si no lo ves, revisa spam.
      </p>

      <form method="POST" action="{{ route('verification.send') }}" class="form">
        @csrf
        <div class="inputBx">
          <input type="submit" value="Reenviar enlace">
        </div>
      </form>

      <div class="links">
        <a href="{{ route('logout') }}"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
          Cerrar sesión
        </a>
      </div>
      <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display:none;">
        @csrf
      </form>
    </div>
  </div>
</body>
</html>
