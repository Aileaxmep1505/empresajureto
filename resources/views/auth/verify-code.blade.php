@extends('layouts.web')
@section('title','Verifica tu correo')

@section('content')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  .vc{
    --ink:#1f2b49;
    --muted:#5c6885;
    --line:#dfe5ee;
    --surface:#ffffff;
    --blue:#1677f2;
    --blue-strong:#0f73f1;
    --field:#e9eff8;
    --field-border:#d6deeb;
    --success:#15803d;
    --danger:#d92020;
    --radius:24px;
    --shadow:0 24px 60px rgba(2,8,23,.10);
    font-family:'Quicksand',sans-serif;
  }
  .vc .wrap{max-width:460px;margin:clamp(56px,8vw,104px) auto;padding:0 16px}
  .vc .card{
    background:var(--surface);
    border:1px solid var(--line);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    padding:clamp(24px,4vw,34px);
    text-align:center;
  }
  .vc .vc-icon{
    width:60px;height:60px;margin:0 auto 16px;
    display:grid;place-items:center;border-radius:18px;
    background:rgba(22,119,242,.10);
    border:1px solid rgba(22,119,242,.16);
    color:var(--blue);
  }
  .vc .vc-icon svg{width:28px;height:28px}
  .vc h2{
    color:var(--ink);margin:0 0 8px;font-weight:700;
    font-size:clamp(22px,3.8vw,28px);letter-spacing:-0.02em;
  }
  .vc p{color:var(--muted);margin:0 0 20px;line-height:1.55;font-size:14.5px;font-weight:500}

  .vc .status{
    background:#e7f0ff;border:1px solid #cfe0ff;color:#14528f;
    border-radius:14px;padding:11px 14px;margin-bottom:16px;
    font-size:14px;font-weight:700;text-align:left;line-height:1.45;
  }

  .vc .otp{display:flex;gap:9px;justify-content:center;margin:4px 0 6px}
  .vc .otp-box{
    width:100%;max-width:52px;height:60px;
    text-align:center;font-family:'Quicksand',sans-serif;
    font-size:24px;font-weight:700;color:var(--ink);
    background:var(--field);border:1px solid var(--field-border);
    border-radius:14px;outline:none;
    transition:border-color .18s ease, box-shadow .18s ease, background .18s ease;
  }
  .vc .otp-box:focus{
    border-color:var(--blue);background:#e6eefc;
    box-shadow:0 0 0 3px rgba(22,119,242,.14);
  }
  .vc .otp-box.filled{border-color:rgba(22,119,242,.45)}
  .vc .otp.invalid .otp-box{border-color:var(--danger);background:#fff8f8}

  .vc small.err{
    display:inline-flex;align-items:center;margin:10px auto 0;
    padding:6px 10px;border-radius:999px;
    background:#ffebeb;color:var(--danger);
    font-size:13px;font-weight:700;line-height:1.3;
  }

  .vc .actions{display:flex;gap:10px;align-items:center;margin-top:18px}
  .vc .btn{
    flex:1;appearance:none;border:0;cursor:pointer;
    min-height:52px;border-radius:999px;
    font-family:'Quicksand',sans-serif;font-size:15.5px;font-weight:700;
    display:inline-flex;align-items:center;justify-content:center;
    transition:transform .18s ease, box-shadow .18s ease, background .18s ease, color .18s ease;
  }
  .vc .btn-primary{
    background:var(--blue-strong);color:#fff;
    box-shadow:0 10px 24px rgba(22,119,242,.18);
  }
  .vc .btn-primary:hover{background:#0d6ce4;transform:translateY(-1px);box-shadow:0 14px 28px rgba(22,119,242,.22)}
  .vc .btn-primary:active{transform:scale(.98)}
  .vc .btn-ghost{
    background:#fff;border:1px solid var(--field-border);color:var(--ink);
  }
  .vc .btn-ghost:hover{border-color:var(--blue);color:var(--blue)}
  .vc .btn-ghost:active{transform:scale(.98)}

  .vc .back{
    display:inline-block;margin-top:18px;
    color:var(--muted);font-size:14px;font-weight:700;text-decoration:none;
  }
  .vc .back:hover{color:var(--blue);text-decoration:underline}

  @media (max-width:420px){
    .vc .otp{gap:7px}
    .vc .otp-box{height:54px;font-size:21px}
    .vc .actions{flex-direction:column}
    .vc .btn{width:100%}
  }
</style>

<div class="vc">
  <div class="wrap">
    <div class="card">
      <div class="vc-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="5" width="18" height="14" rx="2"/>
          <path d="m3 7 9 6 9-6"/>
        </svg>
      </div>

      <h2>Verifica tu correo</h2>
      <p>Te enviamos un código de 6 dígitos a tu email. Ingrésalo para completar la verificación.</p>

      @if (session('status'))
        <div class="status">{{ session('status') }}</div>
      @endif

      <form method="POST" action="{{ route('verification.code.verify') }}" id="vcForm">
        @csrf

        {{-- Valor real que se envía al backend --}}
        <input type="hidden" name="code" id="vc_code" value="{{ old('code') }}">

        {{-- Casillas visuales (no se envían; alimentan el input oculto) --}}
        <div class="otp @error('code') invalid @enderror" role="group" aria-label="Código de verificación de 6 dígitos">
          <input class="otp-box" inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="Dígito 1" autofocus>
          <input class="otp-box" inputmode="numeric" maxlength="1" aria-label="Dígito 2">
          <input class="otp-box" inputmode="numeric" maxlength="1" aria-label="Dígito 3">
          <input class="otp-box" inputmode="numeric" maxlength="1" aria-label="Dígito 4">
          <input class="otp-box" inputmode="numeric" maxlength="1" aria-label="Dígito 5">
          <input class="otp-box" inputmode="numeric" maxlength="1" aria-label="Dígito 6">
        </div>

        @error('code') <small class="err">{{ $message }}</small> @enderror

        <div class="actions">
          <button class="btn btn-primary" type="submit">Verificar</button>
          <button class="btn btn-ghost" type="submit" formaction="{{ route('verification.code.resend') }}" formnovalidate>Reenviar código</button>
        </div>
      </form>

      <a class="back" href="{{ route('login') }}">Volver al login</a>
    </div>
  </div>
</div>

<script>
  (function(){
    const root   = document.querySelector('.vc');
    if (!root) return;
    const boxes  = Array.prototype.slice.call(root.querySelectorAll('.otp-box'));
    const hidden = root.querySelector('#vc_code');
    if (!boxes.length || !hidden) return;

    function sync(){
      hidden.value = boxes.map(function(b){ return b.value; }).join('');
      boxes.forEach(function(b){ b.classList.toggle('filled', b.value !== ''); });
    }

    boxes.forEach(function(box, i){
      box.addEventListener('input', function(){
        box.value = box.value.replace(/[^0-9]/g, '').slice(0, 1);
        if (box.value && i < boxes.length - 1) boxes[i + 1].focus();
        sync();
      });

      box.addEventListener('keydown', function(e){
        if (e.key === 'Backspace' && !box.value && i > 0){
          boxes[i - 1].focus();
        }
        if (e.key === 'ArrowLeft'  && i > 0) boxes[i - 1].focus();
        if (e.key === 'ArrowRight' && i < boxes.length - 1) boxes[i + 1].focus();
      });

      box.addEventListener('paste', function(e){
        e.preventDefault();
        const data = (e.clipboardData || window.clipboardData).getData('text') || '';
        const digits = data.replace(/[^0-9]/g, '').slice(0, 6).split('');
        digits.forEach(function(d, idx){ if (boxes[idx]) boxes[idx].value = d; });
        const next = Math.min(digits.length, boxes.length - 1);
        boxes[next].focus();
        sync();
      });
    });

    // Prefill si el backend regresó old('code') tras un error
    if (hidden.value){
      hidden.value.slice(0, 6).split('').forEach(function(d, i){
        if (boxes[i]) boxes[i].value = d;
      });
      sync();
    }
  })();
</script>
@endsection