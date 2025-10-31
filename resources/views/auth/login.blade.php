<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/login-ring.css') }}?v={{ time() }}">
</head>
<body>
  <div class="ring">
    <i style="--clr:#00ff0a;"></i>
    <i style="--clr:#ff0057;"></i>
    <i style="--clr:#fffd44;"></i>

    <div class="login">
      <h2>Login</h2>

      @if (session('status'))
        <div class="alert success">{{ session('status') }}</div>
      @endif
      @if ($errors->any())
        <div class="alert error">
          @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
          @endforeach
        </div>
      @endif

      <form method="POST" action="{{ route('login.post') }}" class="form">
        @csrf

        <div class="inputBx">
          <input type="email" name="email" placeholder="Correo" value="{{ old('email') }}" required autocomplete="email" autofocus>
        </div>
        @error('email') <div class="field-error">{{ $message }}</div> @enderror

        <div class="inputBx has-toggle">
          <input id="password-input" type="password" name="password" placeholder="Contraseña" required autocomplete="current-password">
          <button type="button" class="toggle-visibility" aria-label="Mostrar u ocultar contraseña" data-target="#password-input">
            <svg class="icon-eye" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/>
            </svg>
            <svg class="icon-eye-off" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M3 3l18 18M10.58 10.58a3 3 0 104.24 4.24M9.88 4.24A10.94 10.94 0 0121 12c0 .48-.06.95-.18 1.4M6.1 6.1A10.94 10.94 0 003 12c0 .48.06.95.18 1.4"/>
            </svg>
          </button>
        </div>
        @error('password') <div class="field-error">{{ $message }}</div> @enderror

        <div class="inputBx">
          <input type="submit" value="Entrar">
        </div>

        <div class="links" style="display:flex; flex-direction:column; gap:6px; align-items:center;">
          <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:center;">
            <a href="{{ route('password.request') }}">Olvidaste tu contraseña</a>
            {{-- Link de registro (se actualizará a modo colaborador con JS) --}}
            <a id="link_register" href="{{ route('register') }}">Registrarse</a>
          </div>

          {{-- Footer mini: Modo Cliente / Colaborador (solo afecta el enlace de registro) --}}
          <div class="mini-footer" style="margin-top:6px; display:flex; align-items:center; gap:10px; font-size:12px; color:#64748b;">
            <span id="modo_chip_login" style="background:#eef6ff; border:1px solid #dbeafe; color:#0b1220; border-radius:999px; padding:4px 8px;">
              Modo: Cliente
            </span>
            <button type="button" id="btn_toggle_modo_login"
                    style="appearance:none; border:0; background:transparent; text-decoration:underline; cursor:pointer; color:#0b1220;">
              Soy colaborador
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <script>
    (function(){
      // Toggle ojos
      document.querySelectorAll('.toggle-visibility').forEach(function(btn){
        const targetSel = btn.getAttribute('data-target');
        const input = document.querySelector(targetSel);
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

      // Modo en LOGIN: solo cambia el enlace "Registrarse" para abrir el registro en modo Colaborador
      const chip  = document.getElementById('modo_chip_login');
      const btn   = document.getElementById('btn_toggle_modo_login');
      const link  = document.getElementById('link_register');
      if (chip && btn && link) {
        let isColab = false;
        function render(){
          chip.textContent = 'Modo: ' + (isColab ? 'Colaborador' : 'Cliente');
          btn.textContent  = isColab ? 'Usar registro de cliente' : 'Soy colaborador';
          const url = new URL(link.getAttribute('href'), location.origin);
          if (isColab) url.searchParams.set('internal', '1'); else url.searchParams.delete('internal');
          link.setAttribute('href', url.pathname + (url.search || ''));
        }
        btn.addEventListener('click', function(){
          isColab = !isColab;
          render();
        });
        render();
      }
    })();
  </script>
</body>
</html>
