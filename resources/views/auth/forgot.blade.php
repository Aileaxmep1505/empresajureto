<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Recuperar contraseña</title>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/login-ring.css') }}?v={{ time() }}">
</head>
<body>
  <div class="ring">
    <i style="--clr:#00ff0a;"></i>
    <i style="--clr:#ff0057;"></i>
    <i style="--clr:#fffd44;"></i>

    <div class="login">
      <h2>Recuperar contraseña</h2>

      @if (session('status'))
        <div class="alert success">{{ session('status') }}</div>
      @endif

      <form method="POST" action="{{ route('password.email') }}" class="form" novalidate>
        @csrf

        {{-- Correo (error arriba) --}}
        @if ($errors->has('email'))
          <div class="field-error">{{ $errors->first('email') }}</div>
        @endif
        <div class="inputBx @error('email') invalid @enderror">
          <input type="email" name="email" placeholder="Correo" value="{{ old('email') }}" required autocomplete="email" autofocus>
        </div>

        <div class="links" style="margin-bottom:4px">
          <a href="{{ route('login') }}">Volver al login</a>
          <a href="{{ route('register') }}">Crear cuenta</a>
        </div>

        <div class="inputBx">
          <input type="submit" value="Enviar enlace de reinicio">
        </div>
      </form>
    </div>
  </div>
</body>
</html>
