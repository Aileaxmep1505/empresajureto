@extends('layouts.app')
@section('title','Parte contable - Empresas')

@section('content')
<div class="pc-wrap container">
  <h1 class="pc-title">Parte contable</h1>

  @php
    // Images for some slots (only some cards will show images)
    $images = [
      0 => "https://i.pinimg.com/736x/6d/95/6c/6d956cee37967a3fdd91bb665ce254c6.jpg", // papelería
      2 => "https://i.pinimg.com/736x/b1/f7/82/b1f782666685cd106c979ffc60cda93e.jpg", // toners
      4 => "https://sisdetek.com/wp-content/uploads/2024/04/TINTAS-PARA-IMPRESORA.jpeg", // papelería 2
    ];
    function hasImg($i,$imgs){ return isset($imgs[$i]); }
    // Light gradients for cards WITHOUT image
    $lightGradients = [
      "linear-gradient(135deg,#fff8e6 0%, #fff1cc 100%)",
      "linear-gradient(135deg,#f3fff6 0%, #eaffef 100%)",
      "linear-gradient(135deg,#f6f8ff 0%, #eef4ff 100%)",
    ];
  @endphp

  <div class="grid-features" role="list">

    {{-- slot 1 / cloud --}}
    <div class="bento-card cloud company-slot" role="listitem" tabindex="0"
         style="{{ hasImg(0,$images) ? '' : 'background: '.$lightGradients[0] }};">
      @if(hasImg(0,$images))
        <div class="bento-card-surface" style="background-image:url('{{ $images[0] }}');"></div>
      @endif

      @if($companies->get(0))
        <a href="{{ route('partcontable.company', $companies->get(0)->slug) }}" class="pc-bento-link">
          <div class="bento-card-description">
            <div class="bento-meta">
              <div class="bento-avatar">{{ strtoupper(substr($companies->get(0)->name,0,1)) }}</div>
              <div class="bento-title-wrap">
                <h2>{{ $companies->get(0)->name }}</h2>
                <p class="bento-sub">Declaraciones, acuses y pagos</p>
              </div>
            </div>
            <p>Accede a declaración anual, mensual, acuses y pagos.</p>
          </div>
        </a>
      @else
        <div class="bento-card-description empty">
          <h2>Agregar empresa</h2>
          <p>Slot principal (agrega una empresa aquí).</p>
          <a href="{{ route('companies.create') }}" class="bento-add">+ Nueva empresa</a>
        </div>
      @endif
    </div>

    {{-- slot 2 / logo (wide) --}}
    <div class="bento-card logo company-slot" role="listitem" tabindex="0"
         style="{{ hasImg(1,$images) ? '' : 'background: '.$lightGradients[1] }};">
      @if(hasImg(1,$images))
        <div class="bento-card-surface" style="background-image:url('{{ $images[1] }}');"></div>
      @endif

      @if($companies->get(1))
        <a href="{{ route('partcontable.company', $companies->get(1)->slug) }}" class="pc-bento-link">
          <div class="bento-card-description">
            <div class="bento-meta">
              <div class="bento-avatar small">{{ strtoupper(substr($companies->get(1)->name,0,1)) }}</div>
              <div class="bento-title-wrap">
                <h2>{{ $companies->get(1)->name }}</h2>
                <p class="bento-sub">Ver documentos y cumplimiento</p>
              </div>
            </div>
            <p>Estado de cumplimiento y documentos recientes.</p>
          </div>
        </a>
      @else
        <div class="bento-card-description empty">
          <h2>Espacio libre</h2>
          <p>Agrega otra compañía aquí.</p>
          <a href="{{ route('companies.create') }}" class="bento-add">+ Nueva empresa</a>
        </div>
      @endif
    </div>

    {{-- slot 3 / device --}}
    <div class="bento-card device company-slot" role="listitem" tabindex="0"
         style="{{ hasImg(2,$images) ? '' : 'background: '.$lightGradients[2] }};">
      @if(hasImg(2,$images))
        <div class="bento-card-surface" style="background-image:url('{{ $images[2] }}'); opacity:0.32;"></div>
      @endif

      @if($companies->get(2))
        <a href="{{ route('partcontable.company', $companies->get(2)->slug) }}" class="pc-bento-link">
          <div class="bento-card-description">
            <h3>{{ $companies->get(2)->name }}</h3>
            <p class="bento-sub">Acceso rápido</p>
            <div class="bento-mini">Declaración Mensual • Estados financieros</div>
          </div>
        </a>
      @else
        <div class="bento-card-description empty">
          <h3>Vacío</h3>
          <p>Asigna una empresa.</p>
          <a href="{{ route('companies.create') }}" class="bento-add">+ Nueva empresa</a>
        </div>
      @endif
    </div>

    {{-- slot 4 / inbox --}}
    <div class="bento-card inbox company-slot" role="listitem" tabindex="0"
         style="{{ hasImg(3,$images) ? '' : 'background: '.$lightGradients[0] }};">
      @if(hasImg(3,$images))
        <div class="bento-card-surface" style="background-image:url('{{ $images[3] }}');"></div>
      @endif

      @if($companies->get(3))
        <a href="{{ route('partcontable.company', $companies->get(3)->slug) }}" class="pc-bento-link">
          <div class="bento-card-description">
            <h3>{{ $companies->get(3)->name }}</h3>
            <p class="bento-sub">Acuses y comprobantes</p>
            <div class="bento-mini">Acuses • Descargas</div>
          </div>
        </a>
      @else
        <div class="bento-card-description empty">
          <h3>Vacío</h3>
          <p>Asigna una empresa.</p>
          <a href="{{ route('companies.create') }}" class="bento-add">+ Nueva empresa</a>
        </div>
      @endif
    </div>

    {{-- slot 5 / device-2 --}}
    <div class="bento-card device-2 company-slot" role="listitem" tabindex="0"
         style="{{ hasImg(4,$images) ? '' : 'background: '.$lightGradients[1] }};">
      @if(hasImg(4,$images))
        <div class="bento-card-surface" style="background-image:url('{{ $images[4] }}'); opacity:0.28;"></div>
      @endif

      @if($companies->get(4))
        <a href="{{ route('partcontable.company', $companies->get(4)->slug) }}" class="pc-bento-link">
          <div class="bento-card-description">
            <h3>{{ $companies->get(4)->name }}</h3>
            <p class="bento-sub">Pagos y movimientos</p>
            <div class="bento-mini">Pagos • Comprobantes</div>
          </div>
        </a>
      @else
        <div class="bento-card-description empty">
          <h3>Vacío</h3>
          <p>Asigna una empresa.</p>
          <a href="{{ route('companies.create') }}" class="bento-add">+ Nueva empresa</a>
        </div>
      @endif
    </div>

    {{-- slot 6 / ai-gen --}}
    <div class="bento-card ai-gen company-slot" role="listitem" tabindex="0"
         style="{{ hasImg(5,$images) ? '' : 'background: '.$lightGradients[2] }};">
      @if(hasImg(5,$images))
        <div class="bento-card-surface" style="background-image:url('{{ $images[5] }}'); opacity:0.22;"></div>
      @endif

      @if($companies->get(5))
        <a href="{{ route('partcontable.company', $companies->get(5)->slug) }}" class="pc-bento-link">
          <div class="bento-card-description">
            <h2>{{ $companies->get(5)->name }}</h2>
            <p class="bento-sub">Resumen y alertas</p>
            <div class="bento-cta">Ver cumplimiento mensual</div>
          </div>
        </a>
      @else
        <div class="bento-card-description empty">
          <h2>Vacío</h2>
          <p>Asigna una empresa.</p>
          <a href="{{ route('companies.create') }}" class="bento-add">+ Nueva empresa</a>
        </div>
      @endif
    </div>

  </div> <!-- grid-features -->

  <div class="credit" aria-hidden="true" style="margin-top:18px; text-align:center;">
    <small>Interfaz tipo Bento — empresas distribuidas en slots</small>
  </div>
</div>
@endsection

<!-- ===== Styles & JS inline (single-file) ===== -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">

<style>
/* === Typography + base colors (from your snippet) === */
body { background-color: #eef0f6; font-family: "Inter", sans-serif; }
a { --link-color: #4dd9d6; text-decoration: none; color: var(--link-color); font-weight: bold; }
p { color: #4b556b; }
h1,h2,h3 { letter-spacing: -2px; color: #3a3e61; }

/* Container & grid (your original rules) */
.container { display: flex; flex-direction: column; margin: 3rem auto; width: 100%; max-width: 1200px; }
.grid-features {
  grid-column-gap: 2rem;
  grid-row-gap: 2rem;
  grid-template-rows: 1fr;
  grid-template-columns: 4fr 3fr 3fr;
  grid-auto-columns: 1fr;
  display: grid;
}

/* Bento base card + your styling, improved for image clipping */
.bento-card {
  border-radius: 2.5rem;
  flex-direction: column;
  align-items: flex-start;
  min-height: 15rem;
  padding: 2rem;
  display: flex;
  position: relative;
  background-color: #fff;
  color: #060633;
  box-shadow: 0 20px 30px -10px rgb(16 16 39 / 7%);
  text-wrap: balance;
  overflow: hidden; /* IMPORTANT: ensure background layer is clipped to radius */
}

/* placements (keep your original rules) */
.bento-card.cloud {
  grid-area: 1 / 1 / 3 / 2;
  padding: 3rem;
}
.bento-card.logo {
  grid-area: 1 / 2 / 2 / 4;
  /* we may override BG when no image (light gradient) */
}
.bento-card.logo h2 { color: #38a5a2; }
.bento-card.inbox { background-image: linear-gradient(-225deg, #ffffff 0%, #dacceb 100%); }
.bento-card.inbox h3 { color: #8b5cca; }
.bento-card.device { background-size: 100%; background-repeat: no-repeat; background-position: center; }
.bento-card.device-2 { background-size: 100%; background-repeat: no-repeat; background-position: center; }
.bento-card.ai-gen { grid-area: 3 / 2 / 4 / 4; }
.bento-card.ai-gen h2 { font-size: 4rem; text-wrap: balance; }

/* Card content */
.bento-card-description { display: flex; flex-direction: column; gap: 1rem; z-index: 2; position: relative; }
.bento-card h2 { font-weight: 700; font-size: 3.8rem; line-height: 0.9; margin:0; }
.bento-card h3 { font-weight: 600; font-size: 2.6rem; margin:0; }
.bento-card p { font-weight: 500; font-size: 1.2rem; line-height: 1.3; margin:0; }

/* Image surface layer (FIXED: cover + clipped to border-radius) */
.bento-card-surface {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  background-repeat: no-repeat;
  background-size: cover;            /* crucial to crop correctly */
  background-position: center center;
  opacity: 0.12;
  pointer-events: none;
  transition: opacity .28s ease, transform .5s ease;
  z-index: 1;
  border-radius: inherit;            /* ensure corners match parent */
  -webkit-backface-visibility: hidden;
  backface-visibility: hidden;
  will-change: opacity, transform;
}
.bento-card:hover .bento-card-surface { opacity: 0.18; transform: scale(1.05); }

/* meta & avatar */
.bento-meta { display:flex; align-items:center; gap:12px; z-index:2; position:relative; }
.bento-avatar { width:56px; height:56px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-weight:700; color:#fff; background: linear-gradient(135deg,#6b9cff,#7ee7c6); box-shadow: 0 6px 18px rgba(25,45,80,0.08); flex-shrink:0; font-size:20px; }
.bento-avatar.small { width:44px; height:44px; font-size:16px; border-radius:10px; }
.bento-title-wrap h2 { margin:0; font-size:20px; line-height:1; }
.bento-title-wrap p { margin:0; color:#475569; font-size:13px; }

/* small extras */
.bento-sub { color:#475569; font-size:13px; margin-top:4px; }
.bento-mini { font-size:12px; color:#64748b; background:rgba(0,0,0,0.03); padding:6px 8px; border-radius:8px; display:inline-block; margin-top:10px; }
.bento-cta { color:#0b1220; font-weight:600; margin-top:8px; }
.bento-add { display:inline-block; margin-top:10px; padding:8px 12px; background:#10b981; color:#fff; border-radius:10px; text-decoration:none; }

/* clickable block */
.pc-bento-link { color:inherit; text-decoration:none; display:block; height:100%; z-index:2; position:relative; }

/* hover lift */
.bento-card { transition: transform .26s, box-shadow .26s; }
.bento-card:hover { transform: translateY(-8px); box-shadow: 0 30px 50px rgba(16,24,40,.12); }

/* focus accessibility */
.company-slot:focus { outline:none; box-shadow: 0 12px 30px rgba(59,130,246,0.12); transform: translateY(-6px) scale(1.02); }

/* responsive stacking */
@media (max-width: 980px) {
  .grid-features { grid-template-columns: 1fr 1fr; grid-auto-rows: auto; }
  .bento-card.cloud { grid-column: 1 / -1; grid-row: 1 / 2; }
  .bento-card.logo { grid-column: 1 / -1; grid-row: 2 / 3; }
  .bento-card.ai-gen { grid-column: 1 / -1; }
}
@media (max-width: 640px) {
  .grid-features { grid-template-columns: 1fr; gap:14px; }
  .bento-card { padding:18px; border-radius:16px; }
  .bento-avatar { width:46px; height:46px; font-size:18px; }
  .pc-title { font-size:22px; }
  .bento-card-surface { display:none; } /* hide bg images on tiny for clarity */
}

/* credit */
.credit { margin-top: 3rem; text-align: center; }
.credit a { color: #fff; background-color: var(--link-color); padding: 0.6rem 1rem; border-radius: 8px; text-shadow: 1px 1px 1px rgba(0,0,0,0.3); }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
  'use strict';
  // Micro-interactions for company slots
  document.querySelectorAll('.company-slot').forEach(function(el){
    el.addEventListener('pointerdown', function(){ el.style.transition='transform 120ms'; el.style.transform='translateY(-2px) scale(.995)'; });
    el.addEventListener('pointerup', function(){ el.style.transform=''; el.style.transition=''; });
    el.addEventListener('pointerleave', function(){ el.style.transform=''; el.style.transition=''; });
    el.addEventListener('keydown', function(ev){ if(ev.key==='Enter'||ev.key===' '){ const link=el.querySelector('.pc-bento-link'); if(link) link.click(); }});
  });
})();
</script>
