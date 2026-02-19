@extends('layouts.app')

@section('title','Acceso NIP · Documentación de altas')

@section('content')
@php
  $shouldShake = $errors->any() || session('warning') || session('error') ? 'true' : 'false';
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- Toast flotante centrado (como el otro) --}}
<div id="toast-container"  class="toast-wrapper"></div>

{{-- ✅ Layout tipo “glass + screenshot” (como el de arriba) --}}
<div class="pin-layout"style="margin-top:-16px;" data-fullscreen="1">
  <div class="pin-bg" aria-hidden="true"></div>

  <div class="pin-container">
    <div class="pin-card {{ $shouldShake === 'true' ? 'animate-shake' : '' }}">

      {{-- Header interno (minimal) --}}
      <div class="pin-header">
        <a href="{{ url('/') }}" class="btn-back">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
          <span>Volver</span>
        </a>

        <div class="badge-secure">
          <svg width="10" height="10" fill="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"/>
          </svg>
          TLS Secured
        </div>
      </div>

      {{-- Branding --}}
      <div class="pin-branding">
        <div class="brand-icon" aria-hidden="true">
          <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <rect x="3" y="4" width="18" height="16" rx="2"></rect>
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9h18"></path>
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h2"></path>
          </svg>
        </div>

        <h1 class="brand-title">Acceso NIP</h1>
        <p class="brand-subtitle">
          Ingresa tu NIP para acceder a <br><strong>Documentación de Altas</strong>
          
          
        </p>
      </div>

      {{-- FORM --}}
      <form id="pinForm" action="{{ route('secure.alta-docs.check-pin') }}" method="POST" autocomplete="off">
        @csrf

        <div class="pin-wrapper">
          {{-- Input real (fantasma) --}}
          <input
            id="realInput"
            name="pin"
            type="tel"
            inputmode="numeric"
            pattern="\d{6}"
            maxlength="6"
            autocomplete="one-time-code"
            class="ghost-input"
            required
            autofocus
          >

          {{-- Slots visuales --}}
          <div class="visual-slots" aria-hidden="true">
            @for($i=1;$i<=6;$i++)
              <div class="slot" data-slot="{{ $i }}"></div>
            @endfor
          </div>
        </div>

        <button type="submit" class="btn-aurora" id="btnSubmit">
          <span class="btn-text">Desbloquear</span>
          <span class="btn-loader">
            <svg class="spinner" viewBox="0 0 50 50">
              <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle>
            </svg>
          </span>
        </button>


        <div class="pin-footer">
          <a href="{{ url('/') }}" class="pin-link">Volver al panel</a>
        </div>
      </form>

    </div>
  </div>
</div>

<style>
:root{
  --bg-top:#f6c7b5;
  --bg-mid:#f3d6cf;
  --bg-base:#ffffff;

  --glass-bg: rgba(255,255,255,0.70);
  --glass-border: rgba(255,255,255,0.85);
  --glass-shadow: 0 18px 60px rgba(16,24,40,0.10);

  --text-dark:#0b1220;
  --text-gray:#667085;

  --primary:#111827;
  --primary-soft: rgba(17,24,39,0.10);

  --radius-card: 26px;
  --radius-slot: 14px;
  --radius-btn: 16px;

  /* Ajusta si tu header global es diferente */
  --app-header-h: 72px;
}

/* ✅ Pantalla completa SIN tapar el header global */
.pin-layout{
  position: fixed !important;
  left: 0 !important;
  right: 0 !important;
  bottom: 0 !important;
  top: calc(var(--app-header-h) + env(safe-area-inset-top, 0px)) !important;

  width: 100vw !important;
  height: calc(100dvh - (var(--app-header-h) + env(safe-area-inset-top, 0px))) !important;
  height: calc(100svh - (var(--app-header-h) + env(safe-area-inset-top, 0px))) !important;

  display:flex;
  align-items:center;
  justify-content:center;

  padding: 16px;
  overflow:hidden;
  isolation:isolate;
  z-index: 10 !important;

  font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
  overscroll-behavior:none;
}
/* Fondo screenshot (más notorio, azul corporativo elegante) */
.pin-bg{
  position:absolute;
  inset:0;
  z-index:0;
  pointer-events:none;
  background:
    radial-gradient(1200px 420px at 50% 0%,
      rgba(59,130,246,0.35) 0%,
      rgba(37,99,235,0.25) 35%,
      rgba(255,255,255,0.0) 72%),
    linear-gradient(180deg,
      #dbeafe 0%,
      #bfdbfe 24%,
      #e0f2fe 48%,
      #ffffff 78%);
  filter: saturate(1.08);
}
.pin-bg::after{
  content:"";
  position:absolute;
  inset:-20%;
  background:
    repeating-radial-gradient(circle at 20% 10%,
      rgba(0,0,0,0.02) 0 1px,
      rgba(0,0,0,0.00) 1px 6px);
  opacity:.12;
  transform: rotate(2deg);
}


/* Contenedor */
.pin-container{
  position:relative;
  z-index:2;
  width: min(420px, calc(100vw - 32px));
}

/* Card */
.pin-card{
  background: var(--glass-bg);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  border: 1px solid var(--glass-border);
  box-shadow: var(--glass-shadow);
  border-radius: var(--radius-card);
  padding: 28px;
}

/* Header interno */
.pin-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom: 22px;
}
.btn-back{
  display:flex;
  align-items:center;
  gap:8px;
  text-decoration:none;
  color: var(--text-gray);
  font-size: .92rem;
  font-weight: 500;
  transition: color .2s ease;
}
.btn-back:hover{ color: var(--text-dark); }

.badge-secure{
  display:flex;
  align-items:center;
  gap:7px;
  font-size:.72rem;
  font-weight:500;
  letter-spacing:.04em;
  text-transform:uppercase;
  color:#047857;
  background: rgba(4,120,87,0.10);
  padding: 6px 12px;
  border-radius: 999px;
  border: 1px solid rgba(4,120,87,0.15);
}

/* Branding */
.pin-branding{ text-align:center; margin-bottom: 22px; }
.brand-icon{
  width: 58px; height: 58px;
  margin: 0 auto 14px;
  border-radius: 18px;
  display:flex; align-items:center; justify-content:center;
  background: rgba(255,255,255,0.75);
  border: 1px solid rgba(17,24,39,0.06);
  box-shadow: 0 10px 22px rgba(16,24,40,0.08);
  color:#111827;
}
.brand-title{
  font-size: 1.45rem;
  font-weight: 600;
  color: var(--text-dark);
  margin: 0 0 8px;
  letter-spacing: -0.02em;
}
.brand-subtitle{
  font-size: .95rem;
  color: var(--text-gray);
  line-height: 1.5;
  margin: 0;
  font-weight: 400;
}
.brand-subtitle strong{
  color:#111827;
  font-weight: 500;
}

/* PIN Input system */
.pin-wrapper{
  position: relative;
  width: 100%;
  margin-bottom: 14px;
  height: 56px;
}
.ghost-input{
  position:absolute;
  inset:0;
  width:100%;
  height:100%;
  opacity:0;
  cursor:text;
  z-index:2;
}
.visual-slots{
  display:flex;
  justify-content:space-between;
  gap: 8px;
  width:100%;
  height:100%;
}
.slot{
  flex: 1;
  border-radius: var(--radius-slot);
  background: rgba(255,255,255,0.65);
  border: 1px solid rgba(17,24,39,0.10);
  display:flex;
  align-items:center;
  justify-content:center;
  position: relative;
  transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
  transform-origin: center;
}

/* slot activo (zoom notorio) */
.slot.active{
  border-color: rgba(17,24,39,0.35);
  box-shadow: 0 0 0 4px var(--primary-soft), 0 16px 34px rgba(17,24,39,0.10);
  transform: translateY(-2px) scale(1.10);
  z-index: 3;
}

/* punto */
.slot::after{
  content:'';
  width: 10px; height: 10px;
  background: #111827;
  border-radius: 999px;
  transform: scale(0);
  opacity: 0;
  transition: transform .18s ease, opacity .18s ease;
}
.slot.has-dot::after{
  transform: scale(1);
  opacity: 1;
}

/* Button */
.btn-aurora{
  width: 100%;
  border: none;
  padding: 14px 16px;
  border-radius: var(--radius-btn);
  cursor: pointer;
  font-size: 1rem;
  font-weight: 600;

  color: #fff;
  background: linear-gradient(180deg, #111827 0%, #0b1220 100%);
  box-shadow: 0 14px 30px rgba(17,24,39,0.22);
  transition: transform .18s ease, box-shadow .18s ease;
  display:flex;
  align-items:center;
  justify-content:center;
}
.btn-aurora:hover{
  transform: translateY(-2px);
  box-shadow: 0 18px 40px rgba(17,24,39,0.28);
}
.btn-aurora:active{ transform: scale(0.99); }
.btn-aurora:disabled{ opacity: .75; cursor: wait; transform:none; }

.btn-loader{ display:none; }
.spinner{ animation: rotate 1.6s linear infinite; width: 22px; height: 22px; }
.spinner .path{ stroke:#fff; stroke-linecap:round; animation: dash 1.35s ease-in-out infinite; }
@keyframes rotate{ 100%{ transform: rotate(360deg); } }
@keyframes dash{
  0%{ stroke-dasharray: 1,150; stroke-dashoffset: 0; }
  50%{ stroke-dasharray: 90,150; stroke-dashoffset: -35; }
  100%{ stroke-dasharray: 90,150; stroke-dashoffset: -124; }
}

/* Hint */
.pin-hint{
  margin: 10px 0 6px;
  font-size: .82rem;
  color: #6b7280;
  line-height: 1.4;
}

/* Footer link (solo texto) */
.pin-footer{ text-align:center; margin-top: 10px; }
.pin-footer .pin-link{
  color: var(--text-gray);
  font-size: .92rem;
  font-weight: 400;
  text-decoration: underline;
  text-decoration-color: rgba(102,112,133,0.35);
  text-underline-offset: 4px;
  background: transparent !important;
  border: none !important;
  padding: 0 !important;
  border-radius: 0 !important;
  transition: color .18s ease, text-decoration-color .18s ease;
}
.pin-footer .pin-link:hover{
  color: var(--text-dark);
  text-decoration-color: rgba(11,18,32,0.45);
}

/* Shake */
.animate-shake{ animation: shake .5s cubic-bezier(.36,.07,.19,.97) both; }
@keyframes shake{
  10%,90%{ transform: translate3d(-1px,0,0); }
  20%,80%{ transform: translate3d(2px,0,0); }
  30%,50%,70%{ transform: translate3d(-4px,0,0); }
  40%,60%{ transform: translate3d(4px,0,0); }
}

/* Toast (centrado) */
.toast-wrapper{
  position: fixed;
  top: calc(14px + env(safe-area-inset-top, 0px));
  left: 50%;
  transform: translateX(-50%);
  z-index: 9999;
  display:flex;
  flex-direction:column;
  gap:10px;
  align-items:center;
}
.toast-pill{
  background:#fff;
  padding:10px 18px;
  border-radius:999px;
  box-shadow: 0 16px 40px rgba(0,0,0,0.10);
  display:flex;
  align-items:center;
  gap:10px;
  font-size:.92rem;
  font-weight: 500;
  color:#111827;
  animation: toastIn .35s cubic-bezier(.2,.8,.2,1) forwards;
  border: 1px solid rgba(17,24,39,0.06);
}
.toast-dot{ width:8px; height:8px; border-radius:999px; }
.toast-error .toast-dot{ background:#ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,0.18); }
.toast-success .toast-dot{ background:#10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.18); }
.toast-warning .toast-dot{ background:#f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.18); }

@keyframes toastIn{
  from{ opacity:0; transform: translateY(-16px) scale(.96); }
  to{ opacity:1; transform: translateY(0) scale(1); }
}
@keyframes toastOut{
  to{ opacity:0; transform: translateY(-10px) scale(.97); }
}

/* Responsive */
@media (max-width:420px){
  .pin-card{ padding: 22px; border-radius: 22px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('realInput');
  const slots = document.querySelectorAll('.slot');
  const btn   = document.getElementById('btnSubmit');
  const btnText   = btn.querySelector('.btn-text');
  const btnLoader = btn.querySelector('.btn-loader');
  const form  = document.getElementById('pinForm');

  let isSubmitting = false;

  // click/tap en slots => focus
  slots.forEach(slot => {
    slot.addEventListener('pointerdown', () => { input.focus(); });
    slot.addEventListener('click', () => { input.focus(); });
  });

  const updateUI = () => {
    const val = input.value || '';

    slots.forEach((slot, i) => {
      slot.classList.remove('active', 'has-dot');
      if (i < val.length) slot.classList.add('has-dot');
      if (i === val.length && val.length < 6) slot.classList.add('active');
    });

    if (val.length === 6) doSubmit();
  };

  const doSubmit = () => {
    if (isSubmitting) return;
    isSubmitting = true;

    input.blur();
    input.setAttribute('readonly', true);

    btn.disabled = true;
    btnText.style.display = 'none';
    btnLoader.style.display = 'block';

    form.submit();
  };

  input.addEventListener('input', function(){
    this.value = (this.value || '').replace(/\D/g, '').slice(0,6);
    updateUI();
  });

  input.addEventListener('focus', updateUI);
  input.addEventListener('blur', () => slots.forEach(s => s.classList.remove('active')));

  setTimeout(() => input.focus(), 100);
  updateUI();

  // si vuelves con bfcache, resetea estado
  window.addEventListener('pageshow', (e) => {
    if (e.persisted) {
      isSubmitting = false;
      btn.disabled = false;
      input.removeAttribute('readonly');
      btnText.style.display = 'block';
      btnLoader.style.display = 'none';
      input.value = '';
      updateUI();
    }
  });

  // Toast helper
  window.toast = (msg, type = 'error') => {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const el = document.createElement('div');
    el.className = `toast-pill toast-${type}`;
    el.innerHTML = `<span class="toast-dot"></span><span>${msg}</span>`;
    container.appendChild(el);

    setTimeout(() => {
      el.style.animation = 'toastOut 0.35s forwards';
      setTimeout(() => el.remove(), 350);
    }, 3000);
  };

  @if($errors->any())
    toast(@json($errors->first()), 'error');
  @endif
  @if(session('warning'))
    toast(@json(session('warning')), 'warning');
  @endif
  @if(session('error'))
    toast(@json(session('error')), 'error');
  @endif
  @if(session('success'))
    toast(@json(session('success')), 'success');
  @endif
});
</script>
@endsection
