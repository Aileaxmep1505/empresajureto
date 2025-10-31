@extends('layouts.web')
@section('title','Verifica tu correo')

@section('content')
<style>
  .vc{--ink:#0e1726;--muted:#64748b;--line:#e8eef6;--surface:#fff;--bg:#f6f8fc;--brand:#a3d5ff;--radius:16px;--shadow:0 16px 40px rgba(2,8,23,.08)}
  .vc .wrap{max-width:440px;margin:clamp(64px,8vw,110px) auto;padding:0 16px}
  .vc .card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);padding:22px}
  .vc h2{color:var(--ink);margin:0 0 4px;font-size:clamp(20px,3.8vw,28px)}
  .vc p{color:var(--muted);margin:0 0 14px;line-height:1.5}
  .vc .code-input{width:100%;font-size:22px;letter-spacing:10px;text-align:center;padding:14px 12px;border:1px solid var(--line);border-radius:12px;outline:none}
  .vc .code-input:focus{border-color:#b6d9ff;box-shadow:0 0 0 3px rgba(163,213,255,.35)}
  .vc .actions{display:flex;gap:10px;align-items:center;margin-top:14px}
  .vc .btn{appearance:none;border:none;background:var(--brand);color:#0b1220;border-radius:12px;padding:12px 14px;font-weight:700;cursor:pointer}
  .vc .btn:hover{background:#d7eaff}
  .vc .btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink)}
  .vc small.err{color:#b00020;display:block;margin-top:6px}
  .vc .status{background:#eef6ff;border:1px solid #dbeafe;color:#0b1220;border-radius:12px;padding:10px 12px;margin-bottom:12px}
</style>

<div class="vc">
  <div class="wrap">
    <div class="card">
      <h2>Verifica tu correo</h2>
      <p>Te enviamos un código de 6 dígitos a tu email. Ingrésalo para completar la verificación.</p>

      @if (session('status'))
        <div class="status">{{ session('status') }}</div>
      @endif

      <form method="POST" action="{{ route('verification.code.verify') }}">
        @csrf
        <input class="code-input" name="code" inputmode="numeric" pattern="\d{6}" maxlength="6"
               placeholder="••••••" autocomplete="one-time-code"
               oninput="this.value=this.value.replace(/[^0-9]/g,'')" autofocus>
        @error('code') <small class="err">{{ $message }}</small> @enderror

        <div class="actions">
          <button class="btn" type="submit">Verificar</button>
          <button class="btn btn-ghost" type="submit" formaction="{{ route('verification.code.resend') }}">Reenviar código</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
