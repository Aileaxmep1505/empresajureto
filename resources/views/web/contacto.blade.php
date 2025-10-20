@extends('layouts.web')
@section('title','Contacto')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

<style>
  :root{
    --ink:#0e1726; --muted:#6b7280; --surface:#ffffff;
    --shadow:0 25px 60px rgba(13,23,38,.15);
    --grad1:#2b33ff; --grad2:#7a16d6; /* degradado azul → morado */
  }

  .contact-wrap{max-width:1160px;margin:48px auto 28px;padding:16px}
  .contact-card{
    position:relative;background:var(--surface);border-radius:28px;box-shadow:var(--shadow);
    display:grid;grid-template-columns:1.1fr 0.9fr;overflow:hidden;
  }
  @media (max-width: 992px){ .contact-card{grid-template-columns:1fr} }

  /* ======= BLOQUES DEGRADADOS COMO EN TU FOTO 1 ======= */
  .ribbon-top,.ribbon-bottom{
    position:absolute;right:0;width:220px;height:120px;background:linear-gradient(45deg,var(--grad1),var(--grad2));
    z-index:1;
  }
  .ribbon-top{top:0;border-bottom-left-radius:28px}
  .ribbon-bottom{bottom:0;border-top-left-radius:28px}
  @media (max-width: 992px){ .ribbon-top,.ribbon-bottom{display:none} }

  /* ======= Columna izquierda (form) ======= */
  .left{padding:54px clamp(24px,4vw,72px) 54px clamp(24px,4vw,84px)}
  .title{font-size:46px;font-weight:800;color:#000;margin:0 0 10px}
  .lead{color:var(--muted);margin:0 0 28px;max-width:560px}
  .field{margin:22px 0}
  .input,.textarea{
    width:100%;border:none;border-bottom:1px solid #d8dde6;outline:none;padding:12px 0;font-size:16px;
    color:#111;background:transparent;transition:border-color .2s ease, background .2s ease;
  }
  .input:focus,.textarea:focus{border-bottom-color:#8aa2ff;background:#f2f6ff}
  .textarea{min-height:92px;resize:vertical}
  .invalid{border-bottom-color:#ef4444 !important;background:#fff6f6}
  .error{color:#b00020;font-size:12px;margin-top:8px}
  .btn-grad{
    margin-top:26px;width:260px;height:56px;border-radius:28px;border:2px solid #0b0b0b20;
    background:linear-gradient(90deg,var(--grad1),var(--grad2));color:#fff;font-weight:600;
    display:flex;align-items:center;justify-content:center;gap:10px;cursor:pointer;
    box-shadow:0 10px 24px rgba(43,51,255,.25);transition:transform .15s ease, box-shadow .15s ease;
  }
  .btn-grad:hover{transform:translateY(-2px);box-shadow:0 16px 36px rgba(43,51,255,.3)}

  /* ======= Columna derecha (Contact Info) EXACTA A LA FOTO 1 ======= */
  .right{
    position:relative; z-index:2; /* sobre las franjas degradadas */
    background:#2f2f2f; color:#fff;
    /* Esquinas MUY redondeadas del lado izquierdo como en tu screenshot */
    border-top-left-radius:60px; border-bottom-left-radius:60px;
    display:flex;flex-direction:column;gap:22px;min-height:100%;
    padding:54px 52px 38px;
  }
  @media (max-width: 992px){
    .right{border-radius:0}
  }
  .r-title{font-size:36px;font-weight:800;margin:0 0 14px}
  .info-row{display:flex;align-items:flex-start;gap:14px;color:#e5e7eb;line-height:1.45}
  .info-row i{font-size:20px;width:22px;margin-top:2px}
  .info-row a{color:#e5e7eb;text-decoration:none}
  .info-row a:hover{ text-decoration:underline }

  .social{margin-top:auto;display:flex;gap:16px;justify-content:flex-end}
  .social a{
    width:36px;height:36px;border-radius:12px;background:#ffffff18;display:grid;place-items:center;color:#fff;text-decoration:none;
    transition:background .15s ease, transform .15s ease;
  }
  .social a:hover{background:#ffffff30;transform:translateY(-2px)}

  /* ======= MAPA ABAJO (se mantiene) ======= */
  .map-wrap{max-width:1160px;margin:0 auto 48px;padding:0 16px}
  .map-card{border-radius:24px;overflow:hidden;box-shadow:var(--shadow)}
  .map-card iframe{width:100%;height:460px;border:0}
</style>

<div class="contact-wrap">
  <div class="contact-card">
    {{-- Franjas degradadas (arriba y abajo a la derecha) --}}
    <div class="ribbon-top"></div>
    <div class="ribbon-bottom"></div>

    {{-- Izquierda: Formulario --}}
    <div class="left">
      <h1 class="title">Contactanos</h1>
      <p class="lead">Feel Free to contact us any time. We will get back to you as soon as we can!</p>

      @if(session('ok'))
        <div style="background:#ecfdf5;color:#065f46;border-radius:12px;padding:10px 12px;margin-bottom:12px">
          <i class="fa-solid fa-circle-check"></i> {{ session('ok') }}
        </div>
      @endif
      @if ($errors->any())
        <div style="background:#fff6f6;color:#7a1f1f;border-radius:12px;padding:10px 12px;margin-bottom:12px">
          <i class="fa-solid fa-triangle-exclamation"></i> Please fix the highlighted fields.
        </div>
      @endif

      <form method="POST" action="{{ route('web.contacto.send') }}" novalidate>
        @csrf
        <div class="field">
          <input name="nombre" value="{{ old('nombre') }}" placeholder="Name"
                 class="input @error('nombre') invalid @enderror" required minlength="2" maxlength="80">
          @error('nombre')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
          <input type="email" name="email" value="{{ old('email') }}" placeholder="Email"
                 class="input @error('email') invalid @enderror" required>
          @error('email')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
          <textarea name="mensaje" placeholder="Message"
                    class="textarea @error('mensaje') invalid @enderror" maxlength="1500" required>{{ old('mensaje') }}</textarea>
          @error('mensaje')<div class="error">{{ $message }}</div>@enderror
        </div>

        <button class="btn-grad" type="submit"><span>Send</span></button>
      </form>
    </div>

    {{-- Derecha: Contact Info con las esquinas y espaciado correctos --}}
    <aside class="right">
      <h2 class="r-title">Información de contacto</h2>

      <div class="info-row">
        <i class="fa-solid fa-headset" aria-hidden="true"></i>
        <div><a href="tel:+525500000000">+52 55 4193 7243</a></div>
      </div>

      <div class="info-row">
        <i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i>
        <div><a href="mailto:hola@tu-dominio.com">rtort@jureto.com.mx</a></div>
      </div>

      <div class="info-row">
        <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
        <div>7CP5+34M San Jerónimo Chicahualco, Estado de México &amp; UAE</div>
      </div>

      <div class="social">
        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        <a href="#" aria-label="Twitter / X"><i class="fab fa-x-twitter"></i></a>
      </div>
    </aside>
  </div>
</div>

{{-- ======= MAPA (se deja abajo) ======= --}}
<div class="map-wrap">
  <div class="map-card">
    <iframe
      loading="lazy"
      referrerpolicy="no-referrer-when-downgrade"
      src="https://www.google.com/maps/embed?pb=!1m17!1m12!1m3!1d3765.9344777435253!2d-99.59477612478935!3d19.285215381962878!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1m1!2zMTnCsDE3JzA2LjgiTiA5OcKwMzUnMzEuOSJX!5e0!3m2!1ses!2smx!4v1760821468860!5m2!1ses!2smx"
      aria-label="Google Map - Ubicación">
    </iframe>
  </div>
</div>
@endsection
