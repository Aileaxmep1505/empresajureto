@extends('layouts.app')

@section('title','Acceso NIP · Documentación de altas')

@section('content')
<div class="pin-wrap">
  <div class="pin-card">
    <div class="pin-logo">
      <span class="pin-logo-circle">
        <svg viewBox="0 0 24 24">
          <rect x="3" y="4" width="18" height="16" rx="2" />
          <path d="M3 9h18" />
          <path d="M9 13h2" />
        </svg>
      </span>
    </div>

    <h1 class="pin-title">NIP de documentación confidencial</h1>
    <p class="pin-subtitle">
      Para acceder al módulo de <strong>documentación de altas</strong> ingresa tu NIP.
    </p>

    <form id="pinForm" action="{{ route('secure.alta-docs.check-pin') }}" method="POST" autocomplete="off">
      @csrf

      <label class="pin-label">Introduce tu NIP de 6 dígitos</label>

      <div class="pin-dots" aria-label="Entrada de NIP de 6 dígitos">
        @for($i = 0; $i < 6; $i++)
          <input
            type="text"
            class="pin-dot-input"
            maxlength="1"
            inputmode="numeric"
            autocomplete="off"
            aria-label="Dígito {{ $i + 1 }}"
          >
        @endfor
      </div>

      <input type="hidden" name="pin" id="pinHidden">

      <p class="pin-hint">
        Solo números. Cada dígito se muestra brevemente y luego se enmascara con <strong>*</strong>.
        Al completar los 6 dígitos se envía automáticamente.
      </p>

      <a href="{{ url('/') }}" class="pin-link">Volver al panel</a>
    </form>
  </div>
</div>

<div id="toast-root" class="toast-root"></div>
@endsection

@push('styles')
<style>
  .pin-wrap{
    min-height:calc(100vh - 80px);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:24px 12px;
    background:
      radial-gradient(circle at top left,#e0f2fe 0,#f8fafc 35%,#ffffff 100%);
  }
  .pin-card{
    width:100%;
    max-width:420px;
    background:#ffffff;
    border-radius:24px;
    border:1px solid #e2e8f0;
    box-shadow:
      0 22px 60px rgba(15,23,42,.14),
      0 0 0 1px rgba(148,163,184,.12);
    padding:24px 22px 20px;
  }
  .pin-logo{
    display:flex;
    justify-content:center;
    margin-bottom:12px;
  }
  .pin-logo-circle{
    width:56px;height:56px;
    border-radius:999px;
    background:
      radial-gradient(circle at 30% 20%,#bfdbfe,#1d4ed8);
    display:flex;align-items:center;justify-content:center;
    box-shadow:
      0 22px 50px rgba(37,99,235,.55),
      inset 0 0 0 1px rgba(255,255,255,.3);
  }
  .pin-logo-circle svg{
    width:26px;height:26px;
    stroke:#eff6ff;stroke-width:1.8;fill:none;
    stroke-linecap:round;stroke-linejoin:round;
  }
  .pin-title{
    margin:4px 0 4px;
    text-align:center;
    font-size:1.1rem;
    font-weight:800;
    letter-spacing:.02em;
    color:#0f172a;
  }
  .pin-subtitle{
    margin:0 0 16px;
    text-align:center;
    font-size:.86rem;
    color:#64748b;
  }

  .pin-label{
    display:block;
    font-size:.85rem;
    font-weight:700;
    color:#0f172a;
    margin-bottom:6px;
  }

  .pin-dots{
    display:flex;
    justify-content:space-between;
    gap:10px;
    margin-bottom:10px;
  }

  /* ====== NUEVO: Rectángulos parados + zoom ====== */
  .pin-dot-input{
    width:44px;        /* más angosto */
    height:64px;       /* más alto (rectángulo vertical) */
    border-radius:14px;
    border:1px solid #cbd5f5;
    background: linear-gradient(180deg,#f8fafc 0%, #ffffff 70%);
    text-align:center;
    font-size:1.55rem;
    font-weight:800;
    letter-spacing:.04em;
    color:#0f172a;

    transition:
      border-color .15s ease,
      box-shadow .15s ease,
      background .15s ease,
      transform .12s ease;

    box-shadow:
      0 8px 18px rgba(15,23,42,.08),
      inset 0 0 0 1px rgba(255,255,255,.9);

    transform: translateZ(0);
    will-change: transform;
  }

  /* zoom al pasar */
  .pin-dot-input:hover{
    transform: scale(1.07);
    border-color:#93c5fd;
    box-shadow:
      0 0 0 1px rgba(191,219,254,.9),
      0 16px 34px rgba(37,99,235,.18),
      inset 0 0 0 1px rgba(255,255,255,.95);
  }

  /* zoom fuerte al enfocar */
  .pin-dot-input:focus{
    outline:none;
    background: linear-gradient(180deg,#eff6ff 0%, #ffffff 70%);
    border-color:#60a5fa;
    box-shadow:
      0 0 0 2px rgba(191,219,254,.95),
      0 18px 40px rgba(37,99,235,.22),
      inset 0 0 0 1px rgba(255,255,255,.95);
    transform: scale(1.12);
  }

  /* input "activo" (el que toca llenar) */
  .pin-dot-input.is-active{
    border-color:#60a5fa;
    background: linear-gradient(180deg,#eff6ff 0%, #ffffff 70%);
    box-shadow:
      0 0 0 2px rgba(191,219,254,.95),
      0 18px 40px rgba(37,99,235,.18),
      inset 0 0 0 1px rgba(255,255,255,.95);
    transform: scale(1.12);
  }

  /* mini “pop” cuando se escribe */
  .pin-dot-input.is-pop{
    transform: scale(1.18);
  }

  .pin-hint{
    margin:4px 0 14px;
    font-size:.78rem;
    color:#6b7280;
  }

  .pin-link{
    display:block;
    margin-top:4px;
    text-align:center;
    font-size:.8rem;
    color:#94a3b8;
    text-decoration:none;
  }
  .pin-link:hover{
    color:#1d4ed8;
    text-decoration:underline;
  }

  @media (max-width:480px){
    .pin-card{
      padding:20px 16px 18px;
      border-radius:20px;
    }
    .pin-dot-input{
      width:40px;
      height:58px;
      font-size:1.35rem;
      border-radius:13px;
    }
  }

  /* ============ Toast simple ============ */
  .toast-root{
    position:fixed;
    top:16px;
    right:16px;
    z-index:9999;
    display:flex;
    flex-direction:column;
    gap:8px;
    pointer-events:none;
  }
  .toast{
    min-width:230px;
    max-width:320px;
    background:#0f172a;
    color:#e5e7eb;
    border-radius:999px;
    padding:8px 14px;
    font-size:.8rem;
    display:flex;
    align-items:center;
    gap:8px;
    box-shadow:0 14px 28px rgba(15,23,42,.4);
    opacity:0;
    transform:translateY(-6px);
    animation:toast-in .2s ease-out forwards;
    pointer-events:auto;
  }
  .toast--error{ background:#991b1b; }
  .toast-icon{
    width:18px;height:18px;
    border-radius:999px;
    background:rgba(15,23,42,.25);
    display:flex;align-items:center;justify-content:center;
    font-size:.95rem;
  }
  .toast-msg{ flex:1; line-height:1.3; }
  @keyframes toast-in{
    from{ opacity:0; transform:translateY(-6px); }
    to{ opacity:1; transform:translateY(0); }
  }
  @keyframes toast-out{
    from{ opacity:1; transform:translateY(0); }
    to{ opacity:0; transform:translateY(-6px); }
  }
</style>
@endpush

@push('scripts')
<script>
  function showToast(message, type = 'error', timeout = 3500){
    const root = document.getElementById('toast-root');
    if (!root || !message) return;

    const toast = document.createElement('div');
    toast.className = 'toast ' + (type === 'error' ? 'toast--error' : '');
    toast.innerHTML = `
      <div class="toast-icon">${type === 'error' ? '!' : 'i'}</div>
      <div class="toast-msg">${message}</div>
    `;
    root.appendChild(toast);

    setTimeout(() => {
      toast.style.animation = 'toast-out .2s ease-in forwards';
      setTimeout(() => toast.remove(), 220);
    }, timeout);
  }

  document.addEventListener('DOMContentLoaded', function () {
    @if(session('error'))
      showToast(@json(session('error')), 'error');
    @endif

    @if($errors->any())
      showToast(@json(implode(' ', $errors->all())), 'error');
    @endif
  });
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const inputs = Array.from(document.querySelectorAll('.pin-dot-input'));
  const hidden = document.getElementById('pinHidden');
  const form   = document.getElementById('pinForm');

  if (!inputs.length || !hidden || !form) return;

  form.setAttribute('novalidate','novalidate');

  const MASK_DELAY = 150;
  let pinValue     = '';
  let isSubmitting = false;

  function setActive(){
    inputs.forEach(i => i.classList.remove('is-active'));
    const idx = Math.min(pinValue.length, 5);
    if (inputs[idx]) inputs[idx].classList.add('is-active');
  }

  function syncUI(){
    for (let i = 0; i < 6; i++) {
      const inp = inputs[i];
      const ch  = pinValue[i] || '';
      inp.value = ch ? '*' : '';
    }
    setActive();
  }

  function focusByLength(){
    const idx = Math.min(pinValue.length, 5);
    inputs[idx].focus();
    inputs[idx].select();
    setActive();
  }

  function trySubmit(){
    if (isSubmitting) return;
    if (pinValue.length !== 6) return;

    isSubmitting = true;
    hidden.value = pinValue;

    setTimeout(() => {
      if (typeof form.requestSubmit === 'function') form.requestSubmit();
      else form.submit();
    }, 100);
  }

  inputs.forEach(inp => {
    inp.addEventListener('click', function (e) {
      e.preventDefault();
      focusByLength();
    });

    inp.addEventListener('focus', function(){
      setActive();
    });

    inp.addEventListener('keydown', function (e) {
      if (e.key === 'Backspace') {
        e.preventDefault();
        if (pinValue.length > 0) {
          pinValue = pinValue.slice(0, -1);
          syncUI();
          focusByLength();
        }
        return;
      }

      if (e.key === 'Enter') {
        e.preventDefault();
        trySubmit();
        return;
      }

      if (e.key.length === 1 && !/[0-9]/.test(e.key)) {
        e.preventDefault();
      }
    });

    inp.addEventListener('input', function () {
      let val = inp.value.replace(/[^0-9]/g, '');
      if (!val) {
        inp.value = '';
        return;
      }

      const digit = val.slice(-1);

      if (pinValue.length >= 6) {
        syncUI();
        trySubmit();
        return;
      }

      pinValue += digit;
      const idx = pinValue.length - 1;

      syncUI();

      // mostrar breve el dígito + pop
      const box = inputs[idx];
      box.value = digit;
      box.classList.add('is-pop');
      setTimeout(() => box.classList.remove('is-pop'), 120);

      setTimeout(() => {
        if (pinValue[idx] === digit) box.value = '*';
      }, MASK_DELAY);

      hidden.value = pinValue;

      if (pinValue.length < 6) focusByLength();
      trySubmit();
    });
  });

  // Foco inicial + activo
  inputs[0].focus();
  setActive();
});
</script>
@endpush
