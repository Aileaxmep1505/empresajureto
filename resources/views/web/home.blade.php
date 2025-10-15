{{-- resources/views/web/home.blade.php (ejemplo) --}}
@extends('layouts.web') 
@section('title','Inicio')

@section('content')
  {{-- ======= Estilos locales (solo para esta vista) ======= --}}
  <style>
    :root{
      --ink:#0e1726; --muted:#6b7280; --bg:#f7fafc;
      --brand:#6ea8fe; --brand-ink:#1d4ed8; --accent:#9ae6b4;
      --surface:#ffffff; --line:#e8eef6; --chip:#eef2ff; --chip-ink:#3730a3;
      --shadow:0 12px 30px rgba(13, 23, 38, .06);
      --danger:#ef4444; --success:#16a34a; --warn:#eab308;
    }

    .container{max-width:1100px;margin-inline:auto;padding-inline:16px}

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

    /* Botones base */
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
    .col-3{ grid-column: span 3 / span 3; }
    @media (max-width: 1100px){ .col-3{ grid-column: span 4 / span 4; } }
    @media (max-width: 900px){ .col-4{ grid-column: span 12 / span 12; } }
    @media (max-width: 780px){ .col-3{ grid-column: span 6 / span 6; } }
    @media (max-width: 520px){ .col-3{ grid-column: span 12 / span 12; } }

    /* Tarjetas de valores */
    .feature{ background: var(--surface); border:1px solid var(--line); border-radius:16px; padding:18px;
      box-shadow: var(--shadow); display:flex; align-items:center; gap:12px; min-height:88px; }
    .feature__chip{ padding:6px 10px; border-radius:999px; background:var(--chip); color:var(--chip-ink);
      font-weight:700; font-size:12px; letter-spacing:.02em; }
    .feature__title{ color:var(--ink); font-weight:800; }
    .feature__desc{ color:var(--muted); margin:2px 0 0; font-size:.95rem; }

    /* ====== SLIDER INFINITO DE MARCAS ====== */
    .full-bleed{ width:100vw; margin-left:calc(50% - 50vw); margin-right:calc(50% - 50vw); }
    .brands-marquee{ position:relative; width:100vw; overflow:hidden; padding:14px 0; background:transparent; }
    .brands-track{ display:flex; align-items:center; width:max-content; gap:clamp(36px, 6vw, 72px);
      animation: brands-scroll 30s linear infinite; }
    @keyframes brands-scroll { from{transform:translateX(0)} to{transform:translateX(-50%)} }
    .brands-marquee:hover .brands-track,
    .brands-marquee:focus-within .brands-track { animation-play-state: paused; }
    @media (prefers-reduced-motion: reduce){ .brands-track{ animation:none; } }
    .brand-item{ display:flex; align-items:center; justify-content:center;
      min-width:clamp(90px, 10vw, 140px); height:clamp(36px, 4.2vw, 50px);
      opacity:.85; filter:grayscale(100%) contrast(105%); transition:opacity .2s, filter .2s, transform .2s; }
    .brand-item:hover{ opacity:1; filter:grayscale(0%); transform:translateY(-1px); }
    .brand-item img{ max-height:100%; max-width:140px; object-fit:contain; display:block; }

    /* ===== PRODUCTO: Card estilo "Paul Atreides" ===== */
    .product-card{
      --bg:#fff; --title:#fff; --title-hover:#000; --text:#666;
      --btn:#eee; --btn-hover:#ddd;
      background:var(--bg); border-radius:24px; padding:8px; height:30rem; width:100%;
      position:relative; overflow:clip; box-shadow:var(--shadow); display:block;
      font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      outline:none;
    }
    .product-card.dark{ --bg:#222; --title:#fff; --title-hover:#fff; --text:#ccc; --btn:#555; --btn-hover:#444; }

    .product-card::before{
      content:""; position:absolute; width:calc(100% - 1rem); height:30%;
      bottom:.5rem; left:.5rem; border-radius:0 0 24px 24px;
      mask:linear-gradient(#0000, #000f 80%); -webkit-mask:linear-gradient(#0000, #000f 80%);
      backdrop-filter: blur(16px); translate:0 0; transition: translate .25s;
    }

    .product-card > .pc-img{
      width:100%; aspect-ratio:2/3; object-fit:cover; object-position:50% 5%;
      border-radius:20px; display:block; transition: aspect-ratio .25s, object-position .5s;
      background:#f6f8fc;
    }

    .pc-badges{ position:absolute; top:12px; left:12px; display:flex; gap:8px; z-index:2; }
    .pc-badge{
      font-weight:800; font-size:12px; padding:6px 10px; border-radius:999px;
      backdrop-filter:saturate(1.6) blur(4px);
      border:1px solid rgba(255,255,255,.6); box-shadow:0 2px 10px rgba(0,0,0,.05);
    }
    .pc-badge--new{ background:rgba(110,168,254,.18); color:#1d4ed8; }
    .pc-badge--sale{ background:rgba(22,163,74,.18); color:#166534; }
    .pc-badge--off{ background:rgba(234,179,8,.18); color:#854d0e; }

    .product-card > section{ margin:1rem; height:calc(33.333% - 1rem); display:flex; flex-direction:column; }
    .product-card h3{
      margin:0 0 1rem 0; font-size:1.15rem; line-height:1.2; font-weight:900;
      color:var(--title); translate:0 -200%; opacity:1; transition: color .5s, margin .25s, opacity 1s, translate .25s;
      display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
    }
    .product-card p{
      font-size:.95rem; line-height:1.35; color:var(--text); margin:0;
      translate:0 100%; opacity:0; transition: margin .25s, opacity 1s .2s, translate .25s .2s;
    }

    .pc-foot{ flex:1; display:flex; justify-content:space-between; align-items:flex-end; gap:8px;
      translate:0 100%; opacity:0; transition: translate .25s .2s, opacity 1s; }
    .pc-tag{ align-self:center; color:var(--title-hover); font-weight:800; font-size:.9rem; }

    .pc-btn{
      border:1px solid transparent; border-radius:20px 20px 24px 20px; font-weight:800;
      font-size:1rem; padding:1rem 1.5rem 1rem 2.75rem; background:var(--btn);
      transition: background .33s; outline-offset:2px; position:relative; color:var(--title-hover);
    }
    .pc-btn::before, .pc-btn::after{
      content:""; width:.85rem; height:.1rem; background:currentColor; position:absolute; top:50%; left:1.33rem; border-radius:1rem;
    }
    .pc-btn::after{ rotate:90deg; transition: rotate .15s; }
    .pc-btn.is-added::after{ rotate:0deg; }
    .pc-btn:hover{ background:var(--btn-hover); }

    .product-card:hover::before,
    .product-card:focus-within::before{ translate:0 100%; }
    .product-card:hover > .pc-img,
    .product-card:focus-within > .pc-img{
      aspect-ratio:1/1; object-position:50% 10%; transition: aspect-ratio .25s, object-position .25s;
    }
    .product-card:hover h3,
    .product-card:hover p,
    .product-card:focus-within h3,
    .product-card:focus-within p{
      translate:0 0; margin-bottom:.5rem; opacity:1;
    }
    .product-card:hover h3,
    .product-card:focus-within h3{ color:var(--title-hover); }
    .product-card:hover .pc-foot,
    .product-card:focus-within .pc-foot{
      translate:0 0; opacity:1; transition: translate .25s .25s, opacity .5s .25s;
    }

    .pc-price{ font-weight:900; }
    .pc-price--old{ color:var(--muted); text-decoration:line-through; font-weight:700; margin-left:6px; font-size:.9rem; }
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
    @includeFirst(['landing.render','panel.landing.render'], ['section'=>$section])
  @endforeach

  {{-- ======= Productos del catálogo (Destacados + Novedades) ======= --}}
  @php
    $featured = \App\Models\CatalogItem::published()->featured()->ordered()->take(8)->get();
    $latest   = \App\Models\CatalogItem::published()->ordered()->take(12)->get();

    $isNew = function($item){
      try{
        return $item->published_at && \Illuminate\Support\Carbon::parse($item->published_at)->gte(now()->subDays(30));
      }catch(\Throwable $e){ return false; }
    };
    $discountPct = function($item){
      if(is_null($item->sale_price) || !$item->price || $item->sale_price >= $item->price) return null;
      $pct = round(100 - (($item->sale_price / $item->price) * 100));
      return max(1, $pct);
    };
  @endphp

  @if($featured->count())
    <div class="container" style="margin-top:24px;">
      <div style="display:flex;justify-content:space-between;align-items:end;gap:10px;flex-wrap:wrap;">
        <h2 style="margin:0;font-weight:800;color:var(--ink);">Destacados</h2>
        <a class="btn btn-ghost" href="{{ route('web.catalog.index') }}">Ver catálogo</a>
      </div>

      <div class="grid" style="margin-top:12px;">
        @foreach($featured as $p)
          @php $off = $discountPct($p); @endphp
          <div class="col-3">
            <article class="product-card" tabindex="0">
              {{-- Badges --}}
              <div class="pc-badges">
                @if($isNew($p)) <span class="pc-badge pc-badge--new">Nuevo</span> @endif
                @if(!is_null($p->sale_price)) <span class="pc-badge pc-badge--sale">Oferta</span> @endif
                @if($off) <span class="pc-badge pc-badge--off">-{{ $off }}%</span> @endif
              </div>

              {{-- Imagen --}}
              <a href="{{ route('web.catalog.show', $p) }}" aria-label="Ver {{ $p->name }}">
                <img class="pc-img"
                     src="{{ $p->image_url ?: asset('images/placeholder.png') }}"
                     alt="{{ $p->name }}"
                     loading="lazy"
                     onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
              </a>

              {{-- Cuerpo --}}
              <section>
                <h3 title="{{ $p->name }}">
                  <a href="{{ route('web.catalog.show', $p) }}" style="color:inherit; text-decoration:none;">{{ $p->name }}</a>
                </h3>

                <p>
                  @if(!is_null($p->sale_price))
                    <span class="pc-price" style="color:var(--success)">
                      ${{ number_format($p->sale_price,2) }}
                    </span>
                    <span class="pc-price--old">
                      ${{ number_format($p->price,2) }}
                    </span>
                  @else
                    <span class="pc-price">${{ number_format($p->price,2) }}</span>
                  @endif
                </p>

                <div class="pc-foot">
                  <div class="pc-tag">SKU: {{ $p->sku ?: '—' }}</div>
                  <form action="{{ route('web.cart.add') }}" method="POST"
                        onsubmit="this.querySelector('.pc-btn').classList.add('is-added')">
                    @csrf
                    <input type="hidden" name="catalog_item_id" value="{{ $p->id }}">
                    <button type="submit" class="pc-btn" aria-label="Añadir {{ $p->name }} al carrito">
                      Añadir
                    </button>
                  </form>
                </div>
              </section>
            </article>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  @if($latest->count())
    <div class="container" style="margin-top:26px;">
      <h2 style="margin:0 0 12px;font-weight:800;color:var(--ink);">Novedades</h2>
      <div class="grid">
        @foreach($latest as $p)
          @php $off = $discountPct($p); @endphp
          <div class="col-3">
            <article class="product-card" tabindex="0">
              {{-- Badges --}}
              <div class="pc-badges">
                @if($isNew($p)) <span class="pc-badge pc-badge--new">Nuevo</span> @endif
                @if(!is_null($p->sale_price)) <span class="pc-badge pc-badge--sale">Oferta</span> @endif
                @if($off) <span class="pc-badge pc-badge--off">-{{ $off }}%</span> @endif
              </div>

              {{-- Imagen --}}
              <a href="{{ route('web.catalog.show', $p) }}" aria-label="Ver {{ $p->name }}">
                <img class="pc-img"
                     src="{{ $p->image_url ?: asset('images/placeholder.png') }}"
                     alt="{{ $p->name }}"
                     loading="lazy"
                     onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
              </a>

              {{-- Cuerpo --}}
              <section>
                <h3 title="{{ $p->name }}">
                  <a href="{{ route('web.catalog.show', $p) }}" style="color:inherit; text-decoration:none;">{{ $p->name }}</a>
                </h3>

                <p>
                  @if(!is_null($p->sale_price))
                    <span class="pc-price" style="color:var(--success)">
                      ${{ number_format($p->sale_price,2) }}
                    </span>
                    <span class="pc-price--old">
                      ${{ number_format($p->price,2) }}
                    </span>
                  @else
                    <span class="pc-price">${{ number_format($p->price,2) }}</span>
                  @endif
                </p>

                <div class="pc-foot">
                  <div class="pc-tag">SKU: {{ $p->sku ?: '—' }}</div>
                  <form action="{{ route('web.cart.add') }}" method="POST"
                        onsubmit="this.querySelector('.pc-btn').classList.add('is-added')">
                    @csrf
                    <input type="hidden" name="catalog_item_id" value="{{ $p->id }}">
                    <button type="submit" class="pc-btn" aria-label="Añadir {{ $p->name }} al carrito">
                      Añadir
                    </button>
                  </form>
                </div>
              </section>
            </article>
          </div>
        @endforeach
      </div>
    </div>
  @endif

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
        @foreach($brands as $logo)
          <div class="brand-item">
            <img src="{{ $logo }}" alt="Logotipo de marca" loading="lazy">
          </div>
        @endforeach
        @foreach($brands as $logo)
          <div class="brand-item" aria-hidden="true">
            <img src="{{ $logo }}" alt="" loading="lazy">
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ======= 3 valores rápidos ======= --}}
  <div class="container">
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
  </div>

  {{-- ======= JS mínimo (feedback al añadir) ======= --}}
  <script>
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.pc-btn');
      if(!btn) return;
      setTimeout(() => btn.classList.add('is-added'), 150);
    });
  </script>
@endsection
