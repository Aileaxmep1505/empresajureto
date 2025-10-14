@extends('layouts.web')
@section('title','Inicio')

@section('content')
  {{-- ======= Estilos locales (solo para esta vista) ======= --}}
  <style>
    /* Paleta pastel minimal */
    :root{
      --ink:#0e1726; --muted:#6b7280; --bg:#f7fafc;
      --brand:#6ea8fe; --brand-ink:#1d4ed8; --accent:#9ae6b4;
      --surface:#ffffff; --line:#e8eef6; --chip:#eef2ff; --chip-ink:#3730a3;
      --shadow:0 12px 30px rgba(13, 23, 38, .06);
    }

    .hero{
      position: relative; padding: clamp(36px, 4vw, 64px) 0;
      border-radius: 20px; background: linear-gradient(180deg, #f5f9ff, #f9fffb);
      overflow: hidden; box-shadow: var(--shadow);
    }
    .hero:before{
      content:""; position:absolute; inset:-20% -10% auto auto;
      width:420px; height:420px; border-radius:50%;
      background: radial-gradient(closest-side, rgba(110,168,254,.18), transparent);
      filter: blur(6px);
    }
    .hero h1{ color: var(--ink); font-weight: 800; letter-spacing: -.02em;
      font-size: clamp(28px, 4vw, 46px); margin: 0 0 10px; }
    .hero p{ color: var(--muted); font-size: clamp(14px, 1.6vw, 18px); max-width: 720px; margin: 0; }

    /* Botones */
    .btn{ display:inline-flex; align-items:center; justify-content:center; gap:10px;
      border:0; cursor:pointer; text-decoration:none; font-weight:700; border-radius:14px; padding:12px 18px;
      transition: transform .12s ease, box-shadow .2s ease, background .2s ease, color .2s ease;
      box-shadow: 0 8px 18px rgba(29,78,216,.12); }
    .btn-primary{ background: var(--brand); color: #0b1220; }
    .btn-primary:hover{ background:#84b7ff; transform: translateY(-1px); }
    .btn-ghost{ background:#fff; color:var(--ink); border:1px solid var(--line); box-shadow:none; }
    .btn-ghost:hover{ background:#f8fafc; transform: translateY(-1px); }

    /* Grid utilitario */
    .grid{ display:grid; gap:16px; grid-template-columns: repeat(12, 1fr); }
    .col-4{ grid-column: span 4 / span 4; }
    @media (max-width: 900px){ .col-4{ grid-column: span 12 / span 12; } }

    /* Tarjetas de valores */
    .feature{ background: var(--surface); border:1px solid var(--line); border-radius:16px; padding:18px;
      box-shadow: var(--shadow); display:flex; align-items:center; gap:12px; min-height:88px; }
    .feature__chip{ padding:6px 10px; border-radius:999px; background:var(--chip); color:var(--chip-ink);
      font-weight:700; font-size:12px; letter-spacing:.02em; }
    .feature__title{ color:var(--ink); font-weight:800; }
    .feature__desc{ color:var(--muted); margin:2px 0 0; font-size:.95rem; }

    /* ====== SLIDER INFINITO DE MARCAS (FULL-BLEED) ====== */
    .full-bleed{           /* rompe el contenedor para ir borde a borde */
      width: 100vw;
      margin-left: calc(50% - 50vw);
      margin-right: calc(50% - 50vw);
    }
    .brands-marquee{
      position: relative; width: 100vw; overflow: hidden;
      padding: 14px 0; background: transparent;   /* sin borde/sombra/fondo */
    }
    .brands-track{
      display:flex; align-items:center; width:max-content;
      gap: clamp(36px, 6vw, 72px);
      animation: brands-scroll 30s linear infinite;
    }
    @keyframes brands-scroll {
      from { transform: translateX(0); }
      to   { transform: translateX(-50%); } /* recorre exactamente la mitad al duplicar */
    }
    .brands-marquee:hover .brands-track,
    .brands-marquee:focus-within .brands-track { animation-play-state: paused; }
    @media (prefers-reduced-motion: reduce){ .brands-track{ animation:none; } }

    .brand-item{
      display:flex; align-items:center; justify-content:center;
      min-width: clamp(90px, 10vw, 140px); height: clamp(36px, 4.2vw, 50px);
      opacity:.85; filter: grayscale(100%) contrast(105%);
      transition: opacity .2s ease, filter .2s ease, transform .2s ease;
    }
    .brand-item:hover{ opacity:1; filter: grayscale(0%); transform: translateY(-1px); }
    .brand-item img{ max-height:100%; max-width: 140px; object-fit:contain; display:block; }
  </style>

  {{-- ======= Hero ======= --}}
  <section class="hero card">
    <div class="container" style="position:relative; z-index:2;">
      <h1>Equipo médico y soluciones profesionales</h1>
      <p>Plataforma moderna para cotizar y comprar con confianza. Atención personalizada y soporte técnico.</p>
      <div style="margin-top:18px; display:flex; gap:10px; flex-wrap:wrap;">
        <a href="{{ route('web.ventas.index') }}" class="btn btn-primary">Ver ventas</a>
        <a href="{{ route('web.contacto') }}" class="btn btn-ghost">Contáctanos</a>
      </div>
    </div>
  </section>

  {{-- ======= Secciones administrables (LandingSection) ======= --}}
  @php
    $sections = \App\Models\LandingSection::with('items')
                ->where('is_active',true)->orderBy('sort_order')->get();
  @endphp
  @foreach($sections as $section)
    @include('landing.render',['section'=>$section])
  @endforeach

  {{-- ======= SLIDER INFINITO DE MARCAS (FULL-BLEED, SIN CONTENEDOR) ======= --}}
  @php
    $brands = [
      asset('images/brands/dell.svg'),
      asset('images/brands/hp.svg'),
      asset('images/brands/brother.svg'),
      asset('images/brands/xerox.svg'),
      asset('images/brands/philips.svg'),
      asset('images/brands/ge.svg'),
      asset('images/brands/sony.svg'),
      asset('images/brands/samsung.svg'),
    ];
  @endphp

  <section class="full-bleed" aria-label="Nuestras marcas">
    <div class="brands-marquee" role="region" aria-roledescription="carrusel" aria-label="Marcas" tabindex="0">
      <div class="brands-track">
        {{-- Bloque 1 --}}
        @foreach($brands as $logo)
          <div class="brand-item">
            <img src="{{ $logo }}" alt="Logotipo de marca" loading="lazy">
          </div>
        @endforeach
        {{-- Bloque 2 duplicado para loop perfecto --}}
        @foreach($brands as $logo)
          <div class="brand-item" aria-hidden="true">
            <img src="{{ $logo }}" alt="" loading="lazy">
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ======= 3 valores rápidos ======= --}}
  <div class="grid" style="margin-top: 18px;">
    <div class="col-4">
      <div class="feature">
        <div>
          <div class="feature__chip">Logística</div>
          <div class="feature__title">Envíos nacionales</div>
          <p class="feature__desc">Cobertura en todo México con aliados confiables.</p>
        </div>
      </div>
    </div>
    <div class="col-4">
      <div class="feature">
        <div>
          <div class="feature__chip">Garantía</div>
          <div class="feature__title">Soporte técnico</div>
          <p class="feature__desc">Acompañamiento posventa y pólizas disponibles.</p>
        </div>
      </div>
    </div>
    <div class="col-4">
      <div class="feature">
        <div>
          <div class="feature__chip">Pagos</div>
          <div class="feature__title">Pagos seguros</div>
          <p class="feature__desc">Opciones a meses y comprobantes al instante.</p>
        </div>
      </div>
    </div>
  </div>
@endsection
