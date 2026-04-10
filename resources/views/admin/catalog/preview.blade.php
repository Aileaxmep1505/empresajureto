<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>{{ $item->name }} | Jureto</title>
  <meta name="description" content="{{ trim($item->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($item->description ?? ''), 140)) }}">

  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  @php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    // Fotos: image_url + photo_1..3
    $rawPhotos = [
      $item->image_url,
      $item->photo_1,
      $item->photo_2,
      $item->photo_3,
    ];

    $photoUrls = collect($rawPhotos)
      ->filter(fn($p) => !empty($p))
      ->map(function($p){
        $p = (string)$p;
        return Str::startsWith($p, ['http://','https://'])
          ? $p
          : Storage::url($p);
      })
      ->unique()
      ->values();

    $cover = $photoUrls[0] ?? null;

    $brand = trim((string)($item->brand_name ?? ''));
    $model = trim((string)($item->model_name ?? ''));
    $gtin  = trim((string)($item->meli_gtin ?? ''));
    $sku   = trim((string)($item->sku ?? ''));
    $stock = (int)($item->stock ?? 0);

    $statusLabel = $stock > 0 ? 'Disponible' : 'Sin stock';
    if ($item->status === 0) $statusLabel = 'Borrador';
    if ($item->status === 2) $statusLabel = 'Oculto';

    $statusTone = $stock > 0 && $item->status === 1 ? 'ok' : 'warn';

    $minStock = $item->min_stock ?? null;
    $maxStock = $item->max_stock ?? null;

    // Valor que se usará tanto para QR como para código de barras
    $codeValue = $gtin !== ''
      ? $gtin
      : ($sku !== '' ? $sku : str_pad((string)$item->id, 8, '0', STR_PAD_LEFT));

    // Rutas
    $qrSvgUrl          = route('catalog.qr', $item);
    $barcodeSvgUrl     = route('catalog.barcode', $item);
    $labelPdfQr        = route('catalog.qr.label', $item);
    $labelPdfBarcode   = route('catalog.barcode.label', $item);
    $publicUrl         = route('catalog.preview', $item);
  @endphp

  <style>
    :root{
      --ink: #333333;
      --muted: #888888;
      --line: #ebebeb;
      --bg: #f9fafb;
      --card: #ffffff;
      --blue: #007aff;
      --blue-soft: #e6f0ff;
      --ok: #15803d;
      --ok-bg: #e6ffe6;
      --warn: #ff4a4a;
      --warn-bg: #ffebeb;
      --radius-lg: 16px;
      --radius-sm: 8px;
      --shadow-soft: 0 4px 12px rgba(0,0,0,0.04);
      --shadow-hover: 0 10px 25px rgba(0,0,0,0.08);
    }

    *{ box-sizing:border-box; }

    body.app{
      margin:0;
      font-family:"Quicksand", system-ui, -apple-system, sans-serif;
      background: var(--bg);
      color:var(--ink);
    }

    .ps-wrap{
      max-width:1100px;
      margin:30px auto 60px;
      padding:0 20px;
      animation:ps-fade-in .55s ease-out;
    }

    .ps-header{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:16px;
      margin-bottom:24px;
    }

    .ps-brand-lockup{
      display:flex;
      align-items:center;
      gap:16px;
    }

    .ps-logo{
      width:220px;
      height:60px;
      border-radius:12px;
  
      display:flex;
      align-items:center;
      justify-content:center;
      
      box-shadow: var(--shadow-soft);
      overflow:hidden;
      padding:10px;
    }
    .ps-logo img{
      width:100%;
      height:100%;
      object-fit:contain;
      display:block;
    }

    .ps-brand-text{
      display:flex;
      flex-direction:column;
      gap:2px;
    }
    .ps-brand-text span:first-child{
      font-size:11px;
      font-weight: 700;
      text-transform:uppercase;
      letter-spacing:1px;
      color:var(--muted);
    }
    .ps-brand-text span:last-child{
      font-size:16px;
      font-weight: 600;
      color: var(--ink);
    }

    .ps-status-badge{
      padding:6px 12px;
      border-radius:999px;
      font-size:13px;
      font-weight: 600;
      display:inline-flex;
      align-items:center;
      gap:6px;
      white-space:nowrap;
    }
    .ps-status-badge span{
      display:inline-block;
      width:6px;
      height:6px;
      border-radius:999px;
    }
    .ps-status-ok{
      background:var(--ok-bg);
      color:var(--ok);
    }
    .ps-status-ok span{ background:var(--ok); }
    
    .ps-status-warn{
      background:var(--warn-bg);
      color:var(--warn);
    }
    .ps-status-warn span{ background:var(--warn); }

    .ps-main{
      display:grid;
      grid-template-columns: 1.1fr 1fr;
      gap:40px;
      align-items:flex-start;
    }

    /* ===== Izquierda: galería ===== */
    .ps-left{
      display:flex;
      flex-direction:column;
      gap:16px;
    }

    .ps-hero{
      position:relative;
      border-radius:var(--radius-lg);
      overflow:hidden;
      background:var(--card);
      border: 1px solid var(--line);
      aspect-ratio: 1 / 1;
      display:flex;
      align-items:center;
      justify-content:center;
      transform-origin:center;
      animation:ps-pop-in .6s cubic-bezier(.19,1,.22,1);
    }

    .ps-hero img{
      width:100%;
      height:100%;
      object-fit:contain;
      padding: 20px;
      display:block;
      transition:transform .35s ease-out, opacity .18s ease-out;
    }

    .ps-hero:hover img{
      transform:scale(1.04);
    }

    .ps-hero-empty{
      color:var(--muted);
      font-size:14px;
      font-weight: 500;
    }

    .ps-thumb-row{
      display:flex;
      gap:12px;
      overflow-x:auto;
      justify-content: center;
      padding-bottom: 4px;
    }
    .ps-thumb-row::-webkit-scrollbar{ display:none; }

    .ps-thumb{
      flex:0 0 64px;
      height:64px;
      border-radius:var(--radius-sm);
      border:1px solid transparent;
      overflow:hidden;
      cursor:pointer;
      background:var(--card);
      position:relative;
      transition:all .2s ease;
    }
    .ps-thumb img{
      width:100%;
      height:100%;
      object-fit:cover;
      display:block;
      transition:transform .25s ease;
    }
    .ps-thumb:hover{
      border-color:#ccc;
    }
    .ps-thumb-active{
      border-color:var(--blue);
    }
    .ps-thumb-active::after{
      content:'';
      position:absolute;
      inset:0;
      border-radius:inherit;
      box-shadow:0 0 0 1px var(--blue) inset;
      pointer-events:none;
    }

    .ps-thumb-counter{
      font-size:12px;
      color:var(--muted);
      text-align: center;
      font-weight: 500;
    }

    /* ===== Derecha: info ===== */
    .ps-right{
      display:flex;
      flex-direction:column;
      gap:24px;
      padding-top: 10px;
    }

    .ps-subtitle{
      font-size:12px;
      font-weight: 700;
      letter-spacing:1px;
      text-transform:uppercase;
      color:var(--muted);
      margin-bottom:6px;
    }

    .ps-title{
      margin:0;
      font-size:24px;
      font-weight: 600;
      line-height:1.3;
      color: #111;
    }

    .ps-model-line{
      margin-top:8px;
      font-size:14px;
      color:var(--muted);
      font-weight: 500;
    }

    .ps-chips{
      margin-top:16px;
      display:flex;
      flex-wrap:wrap;
      gap:8px;
    }

    .ps-chip{
      font-size:12px;
      font-weight: 600;
      padding:4px 10px;
      border-radius:6px;
      background: var(--blue-soft);
      color: var(--blue);
      display:inline-flex;
      align-items:center;
    }

    .ps-section-title{
      font-size:14px;
      font-weight: 700;
      color:var(--ink);
      margin-bottom:12px;
    }

    /* ===== PICKING COMO EN LA CAPTURA (4 CARDS) ===== */
    .ps-picking-grid{
      display:grid;
      grid-template-columns:repeat(2, minmax(0,1fr));
      gap:16px;
    }
    .ps-pick-card{
      background:var(--card);
      border-radius:12px;
      border:1px solid var(--line);
      padding:16px;
      box-shadow:var(--shadow-soft);
      display:flex;
      flex-direction:column;
      gap:6px;
      transition: transform .2s ease, box-shadow .2s ease;
    }
    .ps-pick-card:hover{
      transform: translateY(-2px);
      box-shadow: var(--shadow-hover);
    }
    .ps-pick-head{
      display:flex;
      align-items:center;
      gap:8px;
      font-size:11px;
      font-weight: 700;
      text-transform:uppercase;
      letter-spacing:1px;
      color:var(--muted);
      margin-bottom:4px;
    }
    .ps-pick-ico{
      width:24px;
      height:24px;
      border-radius:50%;
      background:var(--blue-soft);
      display:flex;
      align-items:center;
      justify-content:center;
      flex-shrink:0;
    }
    .ps-pick-ico svg{
      width:14px;
      height:14px;
      stroke:var(--blue);
    }
    .ps-pick-main{
      font-size:16px;
      font-weight: 600;
      color: var(--ink);
    }
    .ps-pick-sub{
      font-size:12px;
      color:var(--muted);
      font-weight: 500;
    }

    /* ===== CARD: FICHA RÁPIDA (sin precio) ===== */
    .ps-specs-card{
      background:var(--card);
      border-radius:12px;
      border:1px solid var(--line);
      padding:0 16px;
      box-shadow:var(--shadow-soft);
    }

    .ps-spec-row{
      display:flex;
      justify-content:space-between;
      align-items:center;
      padding:12px 0;
      border-bottom:1px solid var(--line);
      font-size:14px;
    }
    .ps-spec-row:last-child{
      border-bottom:0;
    }
    .ps-spec-label{
      color:var(--muted);
      font-weight: 500;
    }
    .ps-spec-value{
      text-align:right;
      font-weight: 600;
      color: var(--ink);
      max-width:260px;
    }

    .ps-footer{
      font-size:13px;
      font-weight: 500;
      color:var(--muted);
      display:flex;
      justify-content:space-between;
      flex-wrap:wrap;
      gap:10px;
      margin-top: auto;
    }

    /* ===== CARD: QR / BARRAS ABAJO ===== */
    .ps-qr-card-wrap{
      margin-top:40px;
      display:flex;
      justify-content:center;
      padding-top: 40px;
      border-top: 1px solid var(--line);
    }
    .ps-qr-card{
      background:var(--card);
      border-radius:16px;
      border:1px solid var(--line);
      box-shadow:var(--shadow-soft);
      padding:24px;
      max-width:380px;
      width:100%;
      text-align:center;
    }
    .ps-qr-code-box{
      width:160px;
      height:160px;
      border-radius:12px;
      margin:0 auto 16px;
      background:#ffffff;
      border:1px solid var(--line);
      display:flex;
      align-items:center;
      justify-content:center;
      padding:10px;
    }
    .ps-qr-code-box img{
      width:100%;
      height:100%;
      object-fit:contain;
      display:block;
    }
    .ps-qr-code-text{
      font-size:16px;
      font-weight: 700;
      letter-spacing:1px;
      color:var(--ink);
      margin-top:4px;
    }
    .ps-qr-code-id{
      font-size:13px;
      font-weight: 500;
      color:var(--muted);
      margin-top:4px;
    }
    .ps-qr-actions{
      margin-top:20px;
      display:flex;
      flex-wrap:wrap;
      justify-content:center;
      gap:10px;
    }
    .ps-btn-outline{
      border-radius:999px;
      padding:8px 16px;
      font-size:13px;
      font-weight: 600;
      border:1px solid var(--blue);
      background:#fff;
      color:var(--blue);
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      transition:all .2s ease;
    }
    .ps-btn-outline:hover{
      background:var(--blue-soft);
    }
    .ps-btn-solid{
      border-radius:999px;
      padding:8px 16px;
      font-size:13px;
      font-weight: 600;
      border:1px solid var(--blue);
      background:var(--blue);
      color:#fff;
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      transition:all .2s ease;
      box-shadow: 0 4px 12px rgba(0, 122, 255, 0.2);
    }
    .ps-btn-solid:hover{
      background:#0062cc;
      box-shadow: 0 6px 16px rgba(0, 122, 255, 0.3);
    }

    /* ===== Responsive ===== */
    @media (max-width: 860px){
      .ps-main{ grid-template-columns:1fr; }
    }

    @media (max-width: 640px){
      .ps-wrap{ margin-top:20px; }
      .ps-header{ flex-direction:column; align-items:flex-start; gap: 20px; }
      .ps-picking-grid{ grid-template-columns:1fr; }
      .ps-logo{ width: 180px; height: 50px; }
    }

    @keyframes ps-fade-in{
      from{ opacity:0; transform:translateY(10px); }
      to{ opacity:1; transform:translateY(0); }
    }
    @keyframes ps-pop-in{
      from{ opacity:0; transform:scale(.98); }
      to{ opacity:1; transform:scale(1); }
    }

    @media print{
      body.app{ background:#fff; }
      .ps-wrap{ margin:0; max-width:none; padding: 0; }
      .ps-header, .ps-qr-actions, .ps-thumb-row, .ps-thumb-counter { display: none !important; }
      .ps-hero { border: none; }
    }
  </style>
</head>
<body class="app">
  <div class="ps-wrap">
    <header class="ps-header">
      <div class="ps-brand-lockup">
        <div class="ps-logo">
          <img src="{{ asset('images/logo-mail.png') }}" alt="Jureto">
        </div>
        <div class="ps-brand-text">
          <span>Catálogo</span>
          <span>JURETO S.A. DE C.V.</span>
        </div>
      </div>

      <div>
        <span class="ps-status-badge {{ $statusTone === 'ok' ? 'ps-status-ok' : 'ps-status-warn' }}">
          <span></span>{{ $statusLabel }}
        </span>
      </div>
    </header>

    <main class="ps-main">
      {{-- IZQUIERDA: imagen + thumbs --}}
      <section class="ps-left">
        <div class="ps-hero">
          @if($cover)
            <img id="psHeroImg" src="{{ $cover }}" alt="{{ $item->name }}">
          @else
            <div class="ps-hero-empty">Sin imagen disponible</div>
          @endif
        </div>

        @if($photoUrls->count() > 0)
          <div class="ps-thumb-row" id="psThumbRow">
            @foreach($photoUrls as $idx => $u)
              <button
                type="button"
                class="ps-thumb {{ $idx === 0 ? 'ps-thumb-active' : '' }}"
                data-src="{{ $u }}"
              >
                <img src="{{ $u }}" alt="Foto {{ $idx+1 }}">
              </button>
            @endforeach
          </div>
          <div class="ps-thumb-counter">
            Imagen <span id="psThumbIndex">1</span> de {{ $photoUrls->count() }}
          </div>
        @endif
      </section>

      {{-- DERECHA: info, picking y ficha rápida --}}
      <section class="ps-right">
        {{-- Título --}}
        <div class="ps-title-block">
          <div class="ps-subtitle">
            {{ $brand !== '' ? $brand : 'PRODUCTO JURETO' }}
          </div>
          <h1 class="ps-title">{{ $item->name }}</h1>

          @if($model !== '')
            <div class="ps-model-line">Modelo: {{ $model }}</div>
          @elseif($sku !== '')
            <div class="ps-model-line">SKU: {{ $sku }}</div>
          @endif

          <div class="ps-chips">
            @if($sku !== '')
              <div class="ps-chip">SKU {{ $sku }}</div>
            @endif
            <div class="ps-chip">ID #{{ $item->id }}</div>
            @if($gtin !== '')
              <div class="ps-chip">GTIN {{ $gtin }}</div>
            @endif
          </div>
        </div>

        {{-- INFORMACIÓN DE PICKING (4 cards) --}}
        <section>
          <div class="ps-section-title">Información de picking</div>
          <div class="ps-picking-grid">
            {{-- Ubicación --}}
            <article class="ps-pick-card">
              <div class="ps-pick-head">
                <div class="ps-pick-ico">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 21s-6-5.1-6-10a6 6 0 0 1 12 0c0 4.9-6 10-6 10z"/>
                    <circle cx="12" cy="11" r="2.5"/>
                  </svg>
                </div>
                <span>Ubicación</span>
              </div>
              <div class="ps-pick-main">
                @if(!empty($item->primary_location_id))
                  {{ $item->primary_location_id }}
                @else
                  Almacén principal
                @endif
              </div>
              <div class="ps-pick-sub">
                Ubicación principal de picking.
              </div>
            </article>

            {{-- Stock actual --}}
            <article class="ps-pick-card">
              <div class="ps-pick-head">
                <div class="ps-pick-ico">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="10" width="4" height="10" rx="1"/>
                    <rect x="10" y="6" width="4" height="14" rx="1"/>
                    <rect x="17" y="3" width="4" height="17" rx="1"/>
                  </svg>
                </div>
                <span>Stock actual</span>
              </div>
              <div class="ps-pick-main">
                {{ $stock }} unidades
              </div>
              <div class="ps-pick-sub">
                Min: {{ $minStock ?? '—' }} | Máx: {{ $maxStock ?? '—' }}
              </div>
            </article>

            {{-- Unid. por caja --}}
            <article class="ps-pick-card">
              <div class="ps-pick-head">
                <div class="ps-pick-ico">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                    <line x1="12" y1="22.08" x2="12" y2="12"/>
                  </svg>
                </div>
                <span>Unid. por caja</span>
              </div>
              <div class="ps-pick-main">
                {{ $item->units_per_box ?? '—' }}
              </div>
              <div class="ps-pick-sub">
                Piezas por empaque.
              </div>
            </article>

            {{-- Código de barras --}}
            <article class="ps-pick-card">
              <div class="ps-pick-head">
                <div class="ps-pick-ico">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4v16M7 4v16M10 4v16M14 4v16M17 4v16M20 4v16"/>
                  </svg>
                </div>
              <span>Código de barras</span>
              </div>
              <div class="ps-pick-main">
                {{ $codeValue }}
              </div>
              <div class="ps-pick-sub">
                Para escáner de recepción.
              </div>
            </article>
          </div>
        </section>

        {{-- FICHA RÁPIDA SIN PRECIO --}}
        <section>
          <div class="ps-section-title">Ficha técnica</div>
          <div class="ps-specs-card">
            <div class="ps-spec-row">
              <div class="ps-spec-label">Marca</div>
              <div class="ps-spec-value">{{ $brand !== '' ? $brand : '—' }}</div>
            </div>

            <div class="ps-spec-row">
              <div class="ps-spec-label">Modelo</div>
              <div class="ps-spec-value">{{ $model !== '' ? $model : '—' }}</div>
            </div>

            <div class="ps-spec-row">
              <div class="ps-spec-label">SKU</div>
              <div class="ps-spec-value">{{ $sku !== '' ? $sku : '—' }}</div>
            </div>

            <div class="ps-spec-row">
              <div class="ps-spec-label">GTIN</div>
              <div class="ps-spec-value">{{ $gtin !== '' ? $gtin : '—' }}</div>
            </div>

            @if(trim((string)$item->excerpt) !== '')
              <div class="ps-spec-row">
                <div class="ps-spec-label">Resumen</div>
                <div class="ps-spec-value">{{ $item->excerpt }}</div>
              </div>
            @endif
          </div>
        </section>

        <footer class="ps-footer">
          <div>{{ config('app.name') }} · Catálogo digital</div>
          <div>{{ now()->format('Y-m-d H:i') }}</div>
        </footer>
      </section>
    </main>

    {{-- CARD QR / CÓDIGO DE BARRAS ABAJO, CENTRADA --}}
    <div class="ps-qr-card-wrap">
      <div class="ps-qr-card">
        <div class="ps-qr-code-box">
          {{-- QR por defecto --}}
          <img id="psQrImg" src="{{ $qrSvgUrl }}" alt="QR del producto">
          {{-- Código de barras (oculto al inicio) --}}
          <img id="psBarImg" src="{{ $barcodeSvgUrl }}" alt="Código de barras" style="display:none;">
        </div>
        <div class="ps-qr-code-text">
          {{ $codeValue }}
        </div>
        <div class="ps-qr-code-id">
          {{ $item->slug }} · ID {{ $item->id }}
        </div>

        <div class="ps-qr-actions">
          {{-- Botón para cambiar entre QR y código de barras --}}
          <a href="#" class="ps-btn-outline" id="psToggleCodeBtn">
            Ver código de barras
          </a>

          {{-- Botón de impresión (cambia 2x2 / 2x1 según modo) --}}
          <a
            class="ps-btn-solid"
            id="psPrintLabelBtn"
            href="{{ $labelPdfQr }}"
            target="_blank"
            rel="noopener"
            data-qr-label="{{ $labelPdfQr }}"
            data-bar-label="{{ $labelPdfBarcode }}"
          >
            Imprimir etiqueta 2x2"
          </a>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Galería de imágenes
    (function(){
      const hero = document.getElementById('psHeroImg');
      const thumbButtons = document.querySelectorAll('.ps-thumb');
      const indexLabel = document.getElementById('psThumbIndex');

      if(!hero || !thumbButtons.length) return;

      thumbButtons.forEach((btn, idx) => {
        btn.addEventListener('click', () => {
          const src = btn.dataset.src;
          if(src && hero.src !== src){
            hero.style.opacity = '0';
            setTimeout(()=> {
              hero.src = src;
              hero.style.opacity = '1';
            }, 120);
          }
          thumbButtons.forEach(b => b.classList.remove('ps-thumb-active'));
          btn.classList.add('ps-thumb-active');
          if(indexLabel) indexLabel.textContent = idx + 1;
        });
      });
    })();

    // Toggle QR <-> Código de barras + cambio de botón de impresión
    (function(){
      const qrImg   = document.getElementById('psQrImg');
      const barImg  = document.getElementById('psBarImg');
      const toggle  = document.getElementById('psToggleCodeBtn');
      const printBt = document.getElementById('psPrintLabelBtn');

      if(!qrImg || !barImg || !toggle || !printBt) return;

      const qrLabelUrl  = printBt.dataset.qrLabel;
      const barLabelUrl = printBt.dataset.barLabel;

      let mode = 'qr'; // qr | bar

      function renderMode(){
        if(mode === 'qr'){
          qrImg.style.display  = 'block';
          barImg.style.display = 'none';
          toggle.textContent   = 'Ver código de barras';
          printBt.textContent  = 'Imprimir etiqueta 2x2"';
          printBt.href         = qrLabelUrl;
        }else{
          qrImg.style.display  = 'none';
          barImg.style.display = 'block';
          toggle.textContent   = 'Ver código QR';
          printBt.textContent  = 'Imprimir etiqueta 2x1"';
          printBt.href         = barLabelUrl;
        }
      }

      toggle.addEventListener('click', function(e){
        e.preventDefault();
        mode = (mode === 'qr') ? 'bar' : 'qr';
        renderMode();
      });

      renderMode();
    })();
  </script>
</body>
</html>