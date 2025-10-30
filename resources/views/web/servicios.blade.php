{{-- resources/views/web/servicios.blade.php --}}
@extends('layouts.web')
@section('title','Servicios')

@section('content')
@php
  $waPhone = preg_replace('/\D+/', '', env('WHATSAPP_PHONE','5215555555555'));

  if (!isset($features)) {
    $features = [
      [
        'title'   => 'Asesoría en equipamiento de oficina',
        'lead'    => 'Recomendamos cómputo, escritorios y periféricos según el tamaño y crecimiento.',
        'bullets' => ['Levantamiento y layout básico','Comparativas con TCO','Kits por área'],
        'service' => 'Asesoría en equipamiento',
        'img'     => 'https://i.pinimg.com/736x/a2/aa/63/a2aa637c64d380340051c4f421e150a6.jpg',
      ],
      [
        'title'   => 'Mantenimiento básico de equipos',
        'lead'    => 'Instalación de software, antivirus y limpieza interna.',
        'bullets' => ['Paquetes por hora o por lote','Hardening de antivirus','Pruebas de salud'],
        'service' => 'Mantenimiento básico',
        'img'     => 'https://i.pinimg.com/1200x/41/91/c2/4191c23dda39edd7119cfb70d191eb79.jpg',
      ],
      [
        'title'   => 'Impresoras y redes locales',
        'lead'    => 'Instalamos impresoras, Wi-Fi y redes LAN para oficinas o campus.',
        'bullets' => ['Colas de impresión','Segmentación y cobertura Wi-Fi','Capacitación'],
        'service' => 'Impresoras y redes',
        'img'     => 'https://i.pinimg.com/1200x/6e/1f/46/6e1f4653a312ca8aaab5e90275494cbd.jpg',
      ],
      [
        'title'   => 'Tienda para instituciones educativas',
        'lead'    => 'Convenios con listas escolares prearmadas y compras centralizadas.',
        'bullets' => ['Listas por grado/semestre','Códigos institucionales','Facturación consolidada'],
        'service' => 'Tienda institucional',
        'img'     => 'https://i.pinimg.com/1200x/94/ef/d0/94efd07f4d43ba5761724b2ed2716684.jpg',
      ],
      [
        'title'   => 'Venta por mayoreo',
        'lead'    => 'Precios preferenciales para compras grandes y reabastecimientos periódicos.',
        'bullets' => ['Descuentos por volumen','Logística por sucursal','Equivalentes y marcas alternas'],
        'service' => 'Mayoreo',
        'img'     => 'https://i.pinimg.com/736x/3c/c9/70/3cc9707a8460bb29a3835eb3ee21d87c.jpg',
      ],
    ];
  }

  try { $waBase = route('wa'); $waMode = 'route'; }
  catch (\Throwable $e) { $waBase = "https://wa.me/{$waPhone}"; $waMode = 'direct'; }
@endphp

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300;400;600" />

<style>
/* =================== NAMESPACE ENCAPSULADO =================== */
#svc{
  font-family:'Plus Jakarta Sans',system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;
  --ink:#0b1220; --muted:#6b7280; --line:#e7ecf3; --surface:#ffffff; --black:#0b0b0c;
}
#svc *{ box-sizing:border-box }

/* ====== HERO (full-bleed, sin sombras) ====== */
#svc .hero{ margin:0; padding:0 }
#svc .hero .cover{
  width:100vw; margin-left:calc(50% - 50vw); margin-top:-50px; margin-right:calc(50% - 50vw);
  border-radius:0 0 40px 40px; border:0; text-align:center;
  background: linear-gradient(180deg, #cfe9f3 0%, #f6fbff 30%, #ffffff 55%, #ffe1cc 100%);
  padding: clamp(40px, 9vw, 110px) 16px clamp(50px, 10vw, 130px);
}
#svc .hero .inner{ max-width:1000px; margin:0 auto }
#svc .hero h1{ margin:0 0 22px; font-weight:800; color:var(--ink); font-size:clamp(22px,2.8vw,28px); letter-spacing:-.02em }
#svc .prompt{ position:relative; margin:0 auto 22px; width:min(880px, 90vw); background:#fff; border:1px solid #e8e8ea; border-radius:28px }
#svc .prompt input{ width:100%; border:0; outline:0; background:transparent; border-radius:28px; padding: clamp(18px,2.2vw,26px) clamp(20px,2.8vw,32px); font-size: clamp(14px,1.4vw,18px); color:var(--ink) }
#svc .send{ position:absolute; right:10px; bottom:10px; width:44px; height:44px; border-radius:50%; background:#ff7a1a; color:#fff; border:0; display:grid; place-items:center; cursor:pointer }
#svc .send .material-symbols-outlined{ font-size:22px }
#svc .hero .subtitle{ margin:18px 0 14px; color:var(--ink); font-weight:700; font-size:clamp(14px,1.4vw,18px) }
#svc .hero .chips{ display:flex; gap:14px; flex-wrap:wrap; justify-content:center }
#svc .hero .chip{ display:inline-flex; align-items:center; gap:10px; padding:10px 16px; border-radius:999px; background:#fff; color:var(--ink); border:1px solid #ebedf0; text-decoration:none; font-weight:700; font-size:14px }

/* ====== SLABS 80% (uno y uno) ====== */
#svc .slab{
  position:relative; overflow:hidden; border-radius:36px; border:1px solid var(--line);
  background: var(--surface);
  width: min(1200px, 80vw);
  margin: clamp(28px,5vw,56px) auto 0;
  padding: clamp(28px,4vw,48px);
}
#svc .slab .bg{
  position:absolute; inset:0; pointer-events:none; border-radius:inherit; opacity:.9;
  background: radial-gradient(1200px 700px at 10% 0%, #d6e5ef 0%, transparent 60%),
              radial-gradient(1200px 700px at 0% 100%, #efd8cd 0%, transparent 60%);
}
#svc .slab.alt .bg{
  background: radial-gradient(1200px 700px at 90% 0%, #d6e5ef 0%, transparent 60%),
              radial-gradient(1200px 700px at 100% 100%, #efd8cd 0%, transparent 60%);
}
#svc .slab .grid{
  position:relative; z-index:1;
  display:grid; gap: clamp(24px, 4vw, 56px);
  grid-template-columns: 1.2fr 1fr; align-items:center;
}
#svc .slab.alt .grid{ grid-template-columns: 1fr 1.2fr }
@media (max-width:980px){
  #svc .slab .grid, #svc .slab.alt .grid{ grid-template-columns:1fr }
}

/* Columna visual (con burbuja dentro) */
#svc .media{ display:grid; place-items:center }
#svc .device{
  position:relative;
  width:100%; max-width:760px; aspect-ratio:16/10;
  border-radius:24px; background:rgba(255,255,255,.35);
  border:1px solid rgba(255,255,255,.65);
  backdrop-filter: blur(8px);
  padding: clamp(12px,1.6vw,18px);
  box-shadow: 0 30px 80px rgba(12,18,28,.10);
}
#svc .device img{
  width:100%; height:100%; object-fit:cover; border-radius:18px;
  background:#fff; border:1px solid #e6eaf2;
}

/* >>> Burbuja dentro de la foto (Desktop ON / Mobile OFF) <<< */
#svc .bubble{
  position:absolute; right:min(4%,28px); bottom:min(4%,28px);
  background:#fff; border-radius:18px; padding:12px 14px;
  border:1px solid #dfe5ef;
  box-shadow: 0 10px 30px rgba(12,18,28,.10), 0 2px 6px rgba(12,18,28,.05);
  max-width:min(520px, 90%);
}
#svc .bubble p{ margin:0 0 10px; color:#0b1220; font-weight:700 }
#svc .bubble .chips{ display:flex; gap:10px; flex-wrap:wrap }
#svc .bubble .chip{
  position:relative; padding:10px 14px; border-radius:999px;
  background:#fff; color:#1b2539; border:1.5px solid #e1e6f1; font-weight:700; font-size:13px;
}
#svc .bubble .chip::after{ content:""; position:absolute; inset:0; border-radius:inherit; box-shadow: inset 0 1px 0 rgba(255,255,255,.8) }
#svc .bubble .chip:hover{ background:#f7faff; border-color:#cfe1ff }

/* Columna copy */
#svc .copy h3{ font-size: clamp(32px,5vw,56px); line-height:1.02; margin:0 0 12px; color:var(--ink); font-weight:800; letter-spacing:-.02em }
#svc .copy p.lead{ margin:0 0 16px; color:var(--muted); font-size:clamp(15px,2.2vw,18px) }
#svc .copy ul{ margin:12px 0 22px 20px; color:var(--ink) }
#svc .copy li{ margin:6px 0; font-size:15px }

/* Botón */
#svc .btn{
  display:inline-flex; align-items:center; gap:10px; padding:14px 18px; border-radius:999px;
  background:var(--black); color:#fff; text-decoration:none; font-weight:800; letter-spacing:.2px; border:1px solid transparent;
}
#svc .btn:hover{ background:#fff; color:var(--ink); border-color:#e9eef3 }
#svc .btn .material-symbols-outlined{ font-size:20px }

/* Alternancia de orden (uno y uno) */
#svc .slab.alt .media{ order:2 }
#svc .slab.alt .copy{ order:1 }
@media (max-width:980px){
  #svc .slab.alt .media, #svc .slab.alt .copy{ order:unset }
}

/* ======================= FIXES SOLO MÓVIL ======================= */
@media (max-width: 640px){

  /* Prompt del héroe */
  #svc .prompt{ width:100%; overflow:visible; }
  #svc .prompt input{
    font-size:16px;       /* evita zoom iOS */
    padding-right:72px;   /* espacio para el botón */
  }
  #svc .send{
    right:6px; bottom:6px;
    width:40px; height:40px;
  }

  /* Slab más ancho y compacto en móvil */
  #svc .slab{
    width:min(1200px, 92vw);
    padding:18px;
    border-radius:24px;
  }
  #svc .slab .grid{ gap:18px; }

  /* Visual sin burbuja en móvil */
  #svc .device{
    aspect-ratio: 4 / 3;
    padding:10px;
  }
  #svc .device img{ display:block; }

  /* Ocultar burbuja/chips SOLO en móvil */
  #svc .bubble{ display:none !important; }

  /* Tipografía del copy en móvil */
  #svc .copy h3{ font-size: clamp(24px, 8vw, 30px); }
  #svc .copy p.lead{ font-size:14px; }
}
</style>

<div id="svc">
  {{-- HERO --}}
  <section class="hero" data-wa-mode="{{ $waMode }}" data-wa-base="{{ $waBase }}">
    <div class="cover">
      <div class="inner">
        <h1>Genera tu solicitud de servicio</h1>

        <div class="prompt">
          <input id="svc-hero-input" type="text" placeholder="Ej. Cotiza 15 laptops con Wi-Fi 6 y Office para secundaria…">
          <button class="send" id="svc-hero-send" aria-label="Enviar a WhatsApp">
            <span class="material-symbols-outlined">north_east</span>
          </button>
        </div>

        <div class="subtitle">¿No sabes por dónde empezar? Prueba con:</div>
        <div class="chips" id="svc-hero-chips">
          <a href="#" class="chip" data-txt="Asesoría: setup de 10 escritorios y 10 PC para administración">🧩 Asesoría en equipamiento</a>
          <a href="#" class="chip" data-txt="Mantenimiento para 25 computadoras este mes">🛠️ Mantenimiento</a>
          <a href="#" class="chip" data-txt="Configurar red Wi-Fi y 3 impresoras en oficina de 2 pisos">📶 Redes e impresoras</a>
          <a href="#" class="chip" data-txt="Convenio escolar: listas por grado y facturación consolidada">🏫 Tienda institucional</a>
          <a href="#" class="chip" data-txt="Cotiza mayoreo de consumibles y papelería para 3 sucursales">🏷️ Mayoreo</a>
        </div>
      </div>
    </div>
  </section>

  {{-- SLABS 80% (uno y uno) --}}
  @foreach ($features as $i => $f)
    @php
      $msg = "Hola, vengo de Servicios. Quiero: {$f['service']}";
      $cta = $waMode === 'route' ? ($waBase.'?s='.urlencode($msg)) : ($waBase.'?text='.urlencode($msg));
    @endphp
    <section class="slab {{ $i % 2 === 1 ? 'alt' : '' }}">
      <div class="bg"></div>
      <div class="grid">
        <div class="media">
          <div class="device">
            <img
              src="{{ $f['img'] }}"
              alt="Referencia - {{ $f['service'] }}"
              loading="lazy"
              referrerpolicy="no-referrer"
              onerror="this.onerror=null;this.src='https://placehold.co/1600x1000?text=Imagen+no+disponible';"
            >
            {{-- BURBUJA DENTRO DE LA FOTO (desktop sí, móvil no) --}}
            <div class="bubble">
              <p>Cuéntanos tu caso: <strong>{{ $f['service'] }}</strong></p>
              <div class="chips">
                <span class="chip">Tiempo estimado</span>
                <span class="chip">Alcance</span>
                <span class="chip">Garantía</span>
              </div>
            </div>
          </div>
        </div>
        <div class="copy">
          <h3>{{ $f['title'] }}</h3>
          <p class="lead">{{ $f['lead'] }}</p>
          @if(!empty($f['bullets']))
            <ul>
              @foreach ($f['bullets'] as $b) <li>{{ $b }}</li> @endforeach
            </ul>
          @endif
          <a class="btn" href="{{ $cta }}" target="_blank" rel="noopener">
            <span class="material-symbols-outlined">chat</span> WhatsApp
          </a>
        </div>
      </div>
    </section>
  @endforeach
</div>

<script>
(() => {
  const wrap  = document.querySelector('#svc .hero');
  const mode  = wrap?.dataset.waMode || 'direct';
  const base  = wrap?.dataset.waBase || '';
  const input = document.getElementById('svc-hero-input');
  const send  = document.getElementById('svc-hero-send');
  const chips = Array.from(document.querySelectorAll('#svc-hero-chips .chip'));
  const waUrl = (txt) => {
    const msg = (txt && txt.trim()) ? txt.trim() : 'Hola, vengo de la página de Servicios.';
    return mode === 'route' ? `${base}?s=${encodeURIComponent(msg)}` : `${base}?text=${encodeURIComponent(msg)}`;
  };
  chips.forEach(c => c.addEventListener('click', e => { e.preventDefault(); input.value = c.dataset.txt || ''; input.focus(); }));
  send.addEventListener('click', () => window.open(waUrl(input.value), '_blank', 'noopener'));
  input.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); send.click(); }});
})();
</script>
@endsection
