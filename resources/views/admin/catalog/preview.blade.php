<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>{{ $item->name }} | Jureto</title>
  <meta name="description" content="{{ trim($item->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($item->description ?? ''), 140)) }}">

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
    $barcodeSvgUrl     = route('catalog.barcode', $item);          // <- NUEVA
    $labelPdfQr        = route('catalog.qr.label', $item);
    $labelPdfBarcode   = route('catalog.barcode.label', $item);    // <- NUEVA
    $publicUrl         = route('catalog.preview', $item);
  @endphp

  <style>
    :root{
      --ink:#0f172a;
      --muted:#6b7280;
      --line:#e5e7eb;
      --bg:#f3f4f6;
      --card:#ffffff;
      --accent:#2563eb;
      --accent-soft:#eff6ff;
      --accent-strong:#1d4ed8;
      --ok-bg:#dcfce7;
      --ok-ink:#166534;
      --warn-bg:#fee2e2;
      --warn-ink:#991b1b;
      --radius-lg:18px;
      --radius-sm:12px;
      --shadow-soft:0 20px 60px rgba(15,23,42,.10);
      --shadow-chip:0 10px 25px rgba(15,23,42,.09);
    }

    *{ box-sizing:border-box; }

    body.app{
      margin:0;
      font-family:"Quicksand", system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, "Helvetica Neue", Arial;
      background:radial-gradient(circle at 0% 0%, #eef2ff 0, transparent 40%),
                 radial-gradient(circle at 100% 0%, #e0f2fe 0, transparent 42%),
                 var(--bg);
      color:var(--ink);
    }

    .ps-wrap{
      max-width:1180px;
      margin:24px auto 40px;
      padding:0 16px;
      animation:ps-fade-in .55s ease-out;
    }

    .ps-header{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:16px;
      margin-bottom:18px;
    }

    .ps-brand-lockup{
      display:flex;
      align-items:center;
      gap:14px;
    }

    /* LOGO MÁS GRANDE */
    .ps-logo{
      width:270px;
      height:70px;
      border-radius:22px;
      background:#fff;
      display:flex;
      align-items:center;
      justify-content:center;
      box-shadow:0 18px 40px rgba(15,23,42,.2);
      overflow:hidden;
      padding:6px;
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
      gap:3px;
    }
    .ps-brand-text span:first-child{
      font-size:.78rem;
      text-transform:uppercase;
      letter-spacing:.26em;
      color:var(--muted);
    }
    .ps-brand-text span:last-child{
      font-size:1.05rem;
    }

    .ps-status-badge{
      padding:7px 13px;
      border-radius:999px;
      font-size:.8rem;
      display:inline-flex;
      align-items:center;
      gap:6px;
      box-shadow:var(--shadow-chip);
      border:1px solid transparent;
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
      color:var(--ok-ink);
      border-color:#bbf7d0;
    }
    .ps-status-ok span{ background:#22c55e; }
    .ps-status-warn{
      background:var(--warn-bg);
      color:var(--warn-ink);
      border-color:#fecaca;
    }
    .ps-status-warn span{ background:#ef4444; }

    .ps-main{
      display:grid;
      grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);
      gap:28px;
      align-items:flex-start;
    }

    /* ===== Izquierda: galería ===== */
    .ps-left{
      display:flex;
      flex-direction:column;
      gap:10px;
    }

    .ps-hero{
      position:relative;
      border-radius:22px;
      overflow:hidden;
      background:#f3f4f6;
      min-height:320px;
      display:flex;
      align-items:center;
      justify-content:center;
      transform-origin:center;
      animation:ps-pop-in .6s cubic-bezier(.19,1,.22,1);
    }

    .ps-hero img{
      width:100%;
      height:100%;
      max-height:520px;
      object-fit:cover;
      display:block;
      transition:transform .35s ease-out, opacity .18s ease-out;
    }

    .ps-hero:hover img{
      transform:scale(1.03);
    }

    .ps-hero-empty{
      color:var(--muted);
      font-size:.9rem;
    }

    .ps-thumb-row{
      display:flex;
      gap:10px;
      margin-top:4px;
      overflow-x:auto;
      padding-bottom:4px;
    }
    .ps-thumb-row::-webkit-scrollbar{
      height:4px;
    }
    .ps-thumb-row::-webkit-scrollbar-thumb{
      background:#cbd5f5;
      border-radius:999px;
    }

    .ps-thumb{
      flex:0 0 80px;
      height:64px;
      border-radius:14px;
      border:1px solid var(--line);
      overflow:hidden;
      cursor:pointer;
      background:#f3f4f6;
      position:relative;
      transition:transform .15s ease, box-shadow .15s ease, border-color .15s ease, background .15s ease;
    }
    .ps-thumb img{
      width:100%;
      height:100%;
      object-fit:cover;
      display:block;
      transition:transform .25s ease;
    }
    .ps-thumb:hover{
      transform:translateY(-3px);
      box-shadow:0 14px 28px rgba(15,23,42,.2);
      border-color:#c7d2fe;
      background:#ffffff;
    }
    .ps-thumb:hover img{
      transform:scale(1.05);
    }
    .ps-thumb-active{
      border-color:#4f46e5;
      box-shadow:0 16px 40px rgba(79,70,229,.45);
    }
    .ps-thumb-active::after{
      content:'';
      position:absolute;
      inset:0;
      border-radius:inherit;
      box-shadow:0 0 0 2px rgba(129,140,248,.9) inset;
      pointer-events:none;
    }

    .ps-thumb-counter{
      font-size:.72rem;
      color:var(--muted);
      margin-top:4px;
      padding-left:2px;
    }

    /* ===== Derecha: info ===== */
    .ps-right{
      display:flex;
      flex-direction:column;
      gap:18px;
    }

    .ps-title-block{ }

    .ps-subtitle{
      font-size:.78rem;
      letter-spacing:.18em;
      text-transform:uppercase;
      color:var(--muted);
      margin-bottom:4px;
    }

    .ps-title{
      margin:0;
      font-size:1.35rem;
      line-height:1.25;
    }

    .ps-model-line{
      margin-top:6px;
      font-size:.9rem;
      color:var(--muted);
    }

    .ps-chips{
      margin-top:12px;
      display:flex;
      flex-wrap:wrap;
      gap:8px;
    }

    .ps-chip{
      font-size:.78rem;
      padding:6px 10px;
      border-radius:999px;
      border:1px solid var(--line);
      background:#f9fafb;
      color:#374151;
      display:inline-flex;
      align-items:center;
      gap:6px;
    }

    .ps-section-title{
      font-size:.8rem;
      letter-spacing:.18em;
      text-transform:uppercase;
      color:var(--muted);
      margin-bottom:8px;
    }

    /* ===== PICKING COMO EN LA CAPTURA (4 CARDS) ===== */
    .ps-picking-grid{
      display:grid;
      grid-template-columns:repeat(2, minmax(0,1fr));
      gap:14px;
    }
    .ps-pick-card{
      background:var(--card);
      border-radius:18px;
      border:1px solid var(--line);
      padding:12px 14px 10px;
      box-shadow:0 16px 40px rgba(15,23,42,.06);
      display:flex;
      flex-direction:column;
      gap:4px;
    }
    .ps-pick-head{
      display:flex;
      align-items:center;
      gap:8px;
      font-size:.78rem;
      text-transform:uppercase;
      letter-spacing:.18em;
      color:var(--muted);
      margin-bottom:4px;
    }
    .ps-pick-ico{
      width:22px;
      height:22px;
      border-radius:999px;
      background:var(--accent-soft);
      display:flex;
      align-items:center;
      justify-content:center;
      flex-shrink:0;
    }
    .ps-pick-ico svg{
      width:14px;
      height:14px;
      stroke:#4b5563;
    }
    .ps-pick-main{
      font-size:.95rem;
    }
    .ps-pick-sub{
      font-size:.8rem;
      color:var(--muted);
    }

    /* ===== CARD: FICHA RÁPIDA (sin precio) ===== */
    .ps-specs-card{
      background:var(--card);
      border-radius:var(--radius-lg);
      border:1px solid var(--line);
      padding:14px 16px 12px;
      box-shadow:var(--shadow-soft);
    }

    .ps-spec-row{
      display:flex;
      justify-content:space-between;
      align-items:center;
      padding:6px 0;
      border-bottom:1px dashed #e5e7eb;
      font-size:.9rem;
    }
    .ps-spec-row:last-child{
      border-bottom:0;
    }
    .ps-spec-label{
      color:var(--muted);
      font-size:.86rem;
    }
    .ps-spec-value{
      text-align:right;
      max-width:260px;
    }

    .ps-footer{
      margin-top:14px;
      font-size:.78rem;
      color:var(--muted);
      display:flex;
      justify-content:space-between;
      flex-wrap:wrap;
      gap:10px;
    }

    /* ===== CARD: QR / BARRAS ABAJO ===== */
    .ps-qr-card-wrap{
      margin-top:36px;
      display:flex;
      justify-content:center;
    }
    .ps-qr-card{
      background:var(--card);
      border-radius:20px;
      border:1px solid var(--line);
      box-shadow:var(--shadow-soft);
      padding:18px 22px 16px;
      max-width:360px;
      width:100%;
      text-align:center;
    }
    .ps-qr-code-box{
      width:150px;
      height:150px;
      border-radius:16px;
      margin:0 auto 10px;
      background:#ffffff;
      border:1px solid var(--line);
      display:flex;
      align-items:center;
      justify-content:center;
      padding:8px;
    }
    .ps-qr-code-box img{
      width:100%;
      height:100%;
      object-fit:contain;
      display:block;
    }
    .ps-qr-code-text{
      font-size:.9rem;
      letter-spacing:.18em;
      text-transform:uppercase;
      color:var(--muted);
      margin-top:4px;
    }
    .ps-qr-code-id{
      font-size:.78rem;
      color:var(--muted);
      margin-top:4px;
    }
    .ps-qr-actions{
      margin-top:10px;
      display:flex;
      flex-wrap:wrap;
      justify-content:center;
      gap:8px;
    }
    .ps-link-btn{
      border-radius:999px;
      padding:6px 10px;
      font-size:.78rem;
      border:1px solid var(--line);
      background:#fff;
      color:#111827;
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      gap:6px;
      box-shadow:0 8px 20px rgba(15,23,42,.08);
      transition:transform .14s ease, box-shadow .14s ease, background .14s ease;
    }
    .ps-link-btn:hover{
      transform:translateY(-1px);
      box-shadow:0 14px 30px rgba(15,23,42,.12);
      background:#f9fafb;
    }

    /* ===== Responsive ===== */
    @media (max-width: 960px){
      .ps-main{
        grid-template-columns:1fr;
      }
    }

    @media (max-width: 640px){
      .ps-wrap{
        margin-top:14px;
        padding:0 10px;
      }
      .ps-header{
        flex-direction:column;
        align-items:flex-start;
      }
      .ps-picking-grid{
        grid-template-columns:1fr;
      }
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
      body.app{
        background:#fff;
      }
      .ps-wrap{
        margin:0;
        max-width:none;
      }
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
            {{ $brand !== '' ? strtoupper($brand) : 'PRODUCTO JURETO' }}
          </div>
          <h1 class="ps-title">{{ $item->name }}</h1>

          @if($model !== '')
            <div class="ps-model-line">Modelo {{ $model }}</div>
          @elseif($sku !== '')
            <div class="ps-model-line">SKU {{ $sku }}</div>
          @endif

          <div class="ps-chips">
            @if($sku !== '')
              <div class="ps-chip">SKU&nbsp;{{ $sku }}</div>
            @endif

            <div class="ps-chip">ID&nbsp;#{{ $item->id }}</div>

            @if($gtin !== '')
              <div class="ps-chip">GTIN&nbsp;{{ $gtin }}</div>
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
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
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
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                    <rect x="3" y="10" width="4" height="10"/>
                    <rect x="10" y="6" width="4" height="14"/>
                    <rect x="17" y="3" width="4" height="17"/>
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
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                    <path d="M3 9l9-5 9 5-9 5-9-5z"/>
                    <path d="M3 15l9 5 9-5"/>
                    <path d="M3 9v6"/>
                    <path d="M21 9v6"/>
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
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                    <path d="M4 4v16M7 4v16M10 4v16M14 4v16M17 4v16M20 4v16"/>
                  </svg>
                </div>
              <span>Código de barras</span>
              </div>
              <div class="ps-pick-main">
                {{ $codeValue }}
              </div>
              <div class="ps-pick-sub">
                Para escáner de recepción y surtido.
              </div>
            </article>
          </div>
        </section>

        {{-- FICHA RÁPIDA SIN PRECIO --}}
        <section>
          <div class="ps-section-title">Ficha rápida</div>
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
          <a href="#" class="ps-link-btn" id="psToggleCodeBtn">
            Ver código de barras
          </a>

          {{-- Botón de impresión (cambia 2x2 / 2x1 según modo) --}}
          <a
            class="ps-link-btn"
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
