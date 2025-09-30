<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Crear cuenta</title>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/login-ring.css') }}?v={{ time() }}">
</head>
<body>
  <div class="ring">
    <i style="--clr:#00ff0a;"></i>
    <i style="--clr:#ff0057;"></i>
    <i style="--clr:#fffd44;"></i>

    <div class="login">
      <h2>Crear cuenta</h2>

      {{-- SIN bloque de errores globales para evitar duplicados --}}

      <form method="POST" action="{{ route('register.post') }}" class="form" novalidate>
        @csrf

        {{-- Nombre (error arriba) --}}
        @if ($errors->has('name'))
          <div class="field-error">{{ $errors->first('name') }}</div>
        @endif
        <div class="inputBx @error('name') invalid @enderror">
          <input type="text" name="name" placeholder="Nombre completo" value="{{ old('name') }}" required>
        </div>

        {{-- Correo (error arriba) --}}
        @if ($errors->has('email'))
          <div class="field-error">{{ $errors->first('email') }}</div>
        @endif
        <div class="inputBx @error('email') invalid @enderror">
          <input type="email" name="email" placeholder="Correo" value="{{ old('email') }}" required autocomplete="email">
        </div>

        {{-- Contraseña (error arriba) --}}
        @if ($errors->has('password'))
          <div class="field-error">{{ $errors->first('password') }}</div>
        @endif
        <div class="inputBx has-toggle @error('password') invalid @enderror">
          <input id="password" type="password" name="password" placeholder="Contraseña" required autocomplete="new-password">
          <button type="button" class="toggle-visibility" aria-label="Mostrar u ocultar contraseña" data-target="#password">
            <svg class="icon-eye" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/>
            </svg>
            <svg class="icon-eye-off" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M3 3l18 18M10.58 10.58a3 3 0 104.24 4.24M9.88 4.24A10.94 10.94 0 0121 12c0 .48-.06.95-.18 1.4M6.1 6.1A10.94 10.94 0 003 12c0 .48.06.95.18 1.4"/>
            </svg>
          </button>
        </div>

        {{-- Confirmación (error arriba) --}}
        @if ($errors->has('password_confirmation'))
          <div class="field-error">{{ $errors->first('password_confirmation') }}</div>
        @endif
        <div class="inputBx has-toggle @error('password_confirmation') invalid @enderror">
          <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Confirmar contraseña" required autocomplete="new-password">
          <button type="button" class="toggle-visibility" aria-label="Mostrar u ocultar confirmación" data-target="#password_confirmation">
            <svg class="icon-eye" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/>
            </svg>
            <svg class="icon-eye-off" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M3 3l18 18M10.58 10.58a3 3 0 104.24 4.24M9.88 4.24A10.94 10.94 0 0121 12c0 .48-.06.95-.18 1.4M6.1 6.1A10.94 10.94 0 003 12c0 .48.06.95.18 1.4"/>
            </svg>
          </button>
        </div>

        <div class="links" style="margin-bottom:4px">
          <a href="{{ route('login') }}">Volver al login</a>
        </div>

        <div class="inputBx">
          <input type="submit" value="Crear cuenta">
        </div>
      </form>
    </div>
  </div>

  <!-- JS para los toggles -->
  <script>
    (function(){
      document.querySelectorAll('.toggle-visibility').forEach(function(btn){
        const sel = btn.getAttribute('data-target');
        const input = document.querySelector(sel);
        const eye = btn.querySelector('.icon-eye');
        const eyeOff = btn.querySelector('.icon-eye-off');
        if (eyeOff) eyeOff.style.display = 'none';
        btn.addEventListener('click', function(){
          if (!input) return;
          const isPwd = input.type === 'password';
          input.type = isPwd ? 'text' : 'password';
          if (eye && eyeOff){
            eye.style.display = isPwd ? 'none' : 'inline';
            eyeOff.style.display = isPwd ? 'inline' : 'none';
          }
        });
      });
    })();
  </script>
</body>
</html>
