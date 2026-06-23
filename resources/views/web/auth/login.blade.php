@extends('layouts.web')

@section('title','Entrar')

@section('content')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  :root {
    --bg: #f9fafb;
    --card: #ffffff;
    --ink: #333333;
    --title: #111111;
    --muted: #888888;
    --line: #ebebeb;
    --blue: #007aff;
    --blue-soft: #e6f0ff;
    --success: #15803d;
    --success-soft: #e6ffe6;
    --danger: #ff4a4a;
    --danger-soft: #ffebeb;
  }

  .auth-page {
    min-height: calc(100vh - 120px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 48px 18px;
    font-family: 'Quicksand', sans-serif;
  }

  .auth-shell {
    width: 100%;
    max-width: 440px;
  }

  .auth-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    padding: 34px;
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
  }

  .auth-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 26px rgba(0,0,0,0.04);
  }

  .auth-header {
    margin-bottom: 26px;
    text-align: left;
  }

  .auth-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    padding: 7px 12px;
    border-radius: 999px;
    background: var(--blue-soft);
    color: var(--blue);
    font-size: 13px;
    font-weight: 700;
    line-height: 1;
  }

  .auth-title {
    margin: 0 0 8px;
    color: var(--title);
    font-size: 30px;
    line-height: 1.15;
    font-weight: 700;
    letter-spacing: -0.03em;
  }

  .auth-subtitle {
    margin: 0;
    color: var(--muted);
    font-size: 15px;
    line-height: 1.6;
    font-weight: 500;
  }

  .auth-form {
    display: grid;
    gap: 18px;
  }

  .form-group {
    display: grid;
    gap: 8px;
  }

  .form-label {
    color: var(--ink);
    font-size: 14px;
    font-weight: 700;
  }

  .form-control {
    width: 100%;
    min-height: 48px;
    padding: 12px 14px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #ffffff;
    color: var(--ink);
    font-family: 'Quicksand', sans-serif;
    font-size: 15px;
    font-weight: 600;
    outline: none;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
    box-sizing: border-box;
  }

  .form-control::placeholder {
    color: #b8b8b8;
    font-weight: 500;
  }

  .form-control:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .form-error {
    display: inline-flex;
    width: fit-content;
    padding: 6px 10px;
    border-radius: 999px;
    background: var(--danger-soft);
    color: var(--danger);
    font-size: 13px;
    font-weight: 700;
    line-height: 1.3;
  }

  .remember-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-top: 2px;
  }

  .check-label {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: var(--ink);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    user-select: none;
  }

  .check-label input {
    width: 18px;
    height: 18px;
    accent-color: var(--blue);
    cursor: pointer;
  }

  .btn-primary {
    width: 100%;
    min-height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 0;
    border-radius: 999px;
    background: var(--blue);
    color: #ffffff;
    font-family: 'Quicksand', sans-serif;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
    box-shadow: 0 8px 18px rgba(0,122,255,0.14);
  }

  .btn-primary:hover {
    background: #006ee6;
    box-shadow: 0 10px 22px rgba(0,122,255,0.18);
    transform: translateY(-1px);
  }

  .btn-primary:active {
    transform: scale(0.98);
  }

  .auth-footer {
    margin-top: 18px;
    text-align: center;
    color: var(--muted);
    font-size: 14px;
    font-weight: 500;
  }

  .auth-footer a {
    color: var(--blue);
    font-weight: 700;
    text-decoration: none;
  }

  .auth-footer a:hover {
    text-decoration: underline;
  }

  @media (max-width: 520px) {
    .auth-page {
      padding: 30px 14px;
      align-items: flex-start;
    }

    .auth-card {
      padding: 26px 20px;
      border-radius: 14px;
    }

    .auth-title {
      font-size: 26px;
    }
  }
</style>

<div class="auth-page">
  <div class="auth-shell">
    <form class="auth-card" method="POST" action="{{ route('customer.login.post') }}">
      @csrf

      <div class="auth-header">
        <div class="auth-eyebrow">Acceso seguro</div>
        <h2 class="auth-title">Entrar</h2>
        <p class="auth-subtitle">
          Ingresa a tu cuenta para continuar con tu experiencia.
        </p>
      </div>

      <div class="auth-form">
        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input
            id="email"
            class="form-control"
            name="email"
            value="{{ old('email') }}"
            type="email"
            placeholder="tu@email.com"
            autocomplete="email"
          >
          @error('email')
            <small class="form-error">{{ $message }}</small>
          @enderror
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Contraseña</label>
          <input
            id="password"
            class="form-control"
            name="password"
            type="password"
            placeholder="Ingresa tu contraseña"
            autocomplete="current-password"
          >
          @error('password')
            <small class="form-error">{{ $message }}</small>
          @enderror
        </div>

        <div class="remember-row">
          <label class="check-label">
            <input type="checkbox" name="remember">
            <span>Recordarme</span>
          </label>
        </div>

        <button class="btn-primary" type="submit">
          Entrar
        </button>
      </div>
    </form>
  </div>
</div>
@endsection