{{-- resources/views/accounting/vehicles/show.blade.php --}}
@extends('layouts.app')
@section('title', ($vehicle->brand ?? 'Vehículo') . ' ' . ($vehicle->model ?? ''))

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

@php
  use Illuminate\Support\Facades\Storage;

  $safe = fn($v) => $v ?? '—';

  $fmt = function($d){
    if(!$d) return null;
    try { return \Carbon\Carbon::parse($d)->locale('es')->translatedFormat('d M Y'); }
    catch(\Throwable $e){ return null; }
  };

  $getStatus = function($d){
    if(!$d) return ['text'=>'Sin fecha', 'class'=>''];
    $dt = \Carbon\Carbon::parse($d)->startOfDay();
    $today = \Carbon\Carbon::today();

    if($dt->lt($today)) return ['text'=>'Vencido', 'class'=>'status-bad'];
    if($dt->diffInDays($today) <= 30) return ['text'=>'Próximo', 'class'=>'status-soon'];
    return ['text'=>'Vigente', 'class'=>''];
  };

  // ✅ Imágenes reales (DB: image_left / image_right en disk public)
  $imgL = $vehicle->image_left ? Storage::url($vehicle->image_left) : null;
  $imgR = $vehicle->image_right ? Storage::url($vehicle->image_right) : null;
  $hero = $imgR ?: ($imgL ?: "https://via.placeholder.com/1000x1000/e5e7eb/a3a3a3?text=".urlencode($vehicle->plate ?? 'SIN PLACA'));

  // ✅ Docs desde relación documents (controller hace $vehicle->load('documents'))
  $docsByType = collect($vehicle->documents ?? [])->keyBy('type');

  $docsMap = [
    'tarjeta_circulacion' => 'Tarjeta de Circulación',
    'seguro'              => 'Póliza de Seguro',
    'tenencia'            => 'Tenencia',
    'verificacion'        => 'Verificación',
    'factura'             => 'Factura',
  ];
@endphp

<style>
  :root{
    --huawei-bg-left:#f9f9f9;
    --huawei-bg-right:#ffffff;
    --huawei-text:#000000;
    --huawei-gray:#7d7d7d;
    --radius:24px;
  }

  body{
    background:#fff;
    font-family:'Segoe UI', system-ui, -apple-system, sans-serif;
    margin:0;
    overflow-x:hidden;
  }

  .split-container{
    display:flex;
    flex-wrap:wrap;
    width:100%;
    min-height:100vh;
  }

  .visual-col{
    width:60%;
    height:100vh;
    position:sticky;
    top:0;
    background: radial-gradient(circle at 50% 50%, #ffffff 0%, #f0f0f0 100%);
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    overflow:hidden;
    order:1;
  }

  .main-image-wrapper{
    width:80%;
    height:70%;
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
    z-index:2;
    transition: transform .5s ease;
  }

  .main-image-wrapper img{
    max-width:100%;
    max-height:100%;
    object-fit:contain;
    filter: drop-shadow(0 20px 40px rgba(0,0,0,0.15));
    transition: opacity .25s ease;
  }

  .thumbs-floating{
    position:absolute;
    bottom:40px;
    display:flex;
    gap:15px;
    z-index:10;
    background: rgba(255, 255, 255, 0.5);
    backdrop-filter: blur(10px);
    padding:10px 20px;
    border-radius:99px;
    border:1px solid rgba(255,255,255,0.8);
  }

  .thumb-dot{
    width:50px;height:50px;border-radius:50%;
    overflow:hidden;cursor:pointer;
    border:2px solid transparent;
    transition: all .2s;
    background:#fff;
  }
  .thumb-dot img{ width:100%; height:100%; object-fit:cover; }
  .thumb-dot:hover{ transform: scale(1.1); }
  .thumb-dot.active{ border-color:#000; }

  .info-col{
    width:40%;
    background: var(--huawei-bg-right);
    padding: 4rem 3rem;
    display:flex;
    flex-direction:column;
    order:2;
    box-sizing:border-box;
  }

  .product-tag{
    font-size:.85rem;
    font-weight:700;
    color:#d32f2f;
    text-transform:uppercase;
    margin-bottom:.5rem;
    letter-spacing:.05em;
  }

  .product-title{
    font-size:3.5rem;
    font-weight:600;
    line-height:1.1;
    color:var(--huawei-text);
    margin-bottom:1rem;
    letter-spacing:-.02em;
  }

  .product-desc{
    font-size:1.1rem;
    color:var(--huawei-gray);
    line-height:1.6;
    margin-bottom: 2rem;
    font-weight:400;
  }

  .content-nav{
    display:flex;
    gap:30px;
    margin-bottom: 1.75rem;
    border-bottom:1px solid #eee;
    padding-bottom:10px;
  }
  .nav-item-custom{
    font-size:.95rem;
    color:#000;
    text-decoration:none;
    font-weight:600;
    position:relative;
    cursor:pointer;
    opacity:.5;
    transition: opacity .2s;
    user-select:none;
  }
  .nav-item-custom:hover{ opacity:1; }
  .nav-item-custom.active{ opacity:1; }
  .nav-item-custom.active::after{
    content:'';
    position:absolute;
    bottom:-11px;
    left:0;
    width:100%;
    height:2px;
    background:#000;
  }

  /* ✅ Tabs */
  .tab-panel{ display:none; }
  .tab-panel.active{ display:block; }

  .promo-box{
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 0;
    color: #1e3a8a;
  }
  .promo-item{
    display:flex;
    align-items:flex-start;
    gap:10px;
    margin-bottom:10px;
    font-size:.95rem;
  }
  .promo-item:last-child{ margin-bottom:0; }
  .promo-item i{ margin-top:3px; }
  .status-bad{ color:#b91c1c; font-weight:700; }
  .status-soon{ color:#b45309; font-weight:700; }

  .specs-grid{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem 1rem;
    margin: 0;
  }
  .spec-label{
    font-size:.85rem;
    color:var(--huawei-gray);
    margin-bottom:4px;
  }
  .spec-value{
    font-size:1.1rem;
    font-weight:600;
    color:#000;
  }

  .actions-area{
    margin-top: 0;
    padding-top: 0;
    border-top: 0;
  }

  .btn-action-primary{
    background:#000;
    color:#fff;
    padding:14px 30px;
    border-radius:99px;
    text-decoration:none;
    font-weight:600;
    display:inline-flex;
    align-items:center;
    gap:10px;
    transition: transform .2s;
    border:none;
  }
  .btn-action-primary:hover{
    background:#333;
    transform: translateY(-2px);
    color:#fff;
  }

  .doc-link{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding: 12px 0;
    border-bottom: 1px solid #f5f5f5;
    color:#333;
    text-decoration:none;
    transition: padding .2s;
  }
  .doc-link:hover{ padding-left:10px; color:#000; }

  @media (max-width: 992px){
    .split-container{ flex-direction: column; }
    .visual-col{ width:100%; height:60vh; position:relative; }
    .info-col{ width:100%; padding: 2rem; }
    .product-title{ font-size:2.5rem; }
  }
</style>

<div class="split-container">

  <div class="visual-col">
    <div class="main-image-wrapper">
      <img id="heroImage" src="{{ $hero }}" alt="Vehículo">
    </div>

    <div class="thumbs-floating">
      <div class="thumb-dot active" onclick="changeImage('{{ $hero }}', this)">
        <img src="{{ $hero }}" alt="Principal">
      </div>
      @if($imgL)
        <div class="thumb-dot" onclick="changeImage('{{ $imgL }}', this)">
          <img src="{{ $imgL }}" alt="Izq">
        </div>
      @endif
      @if($imgR)
        <div class="thumb-dot" onclick="changeImage('{{ $imgR }}', this)">
          <img src="{{ $imgR }}" alt="Der">
        </div>
      @endif
    </div>
  </div>

  <div class="info-col">
    <div class="d-flex justify-content-between align-items-start">
      <div class="product-tag">{{ $vehicle->year }} · {{ $vehicle->plate }}</div>
      <a href="{{ route('vehicles.index') }}" class="btn-close" aria-label="Close"></a>
    </div>

    <h1 class="product-title">{{ $vehicle->brand }} {{ $vehicle->model }}</h1>

    <p class="product-desc">
      {{ $vehicle->nickname ? 'Alias: '.$vehicle->nickname : 'Vehículo de la flotilla' }}.
      <br>
      Registrado con número de serie (VIN) {{ $vehicle->vin ?? '—' }}.
    </p>

    {{-- ✅ Tabs (ANTES no abrían porque no había JS + no había secciones que ocultar/mostrar) --}}
    <div class="content-nav">
      <div class="nav-item-custom active" data-tab="estado">Estado</div>
      <div class="nav-item-custom" data-tab="specs">Especificaciones</div>
      <div class="nav-item-custom" data-tab="docs">Documentos</div>
    </div>

    {{-- ===== TAB: ESTADO ===== --}}
    <div class="tab-panel active" data-panel="estado">
      <div class="promo-box">
        @php
          $fechas = [
            ['label'=>'Verificación',   'date'=>$vehicle->next_verification_due_at, 'icon'=>'bi-clipboard-check'],
            ['label'=>'Servicio',       'date'=>$vehicle->next_service_due_at,      'icon'=>'bi-tools'],
            ['label'=>'Tenencia',       'date'=>$vehicle->tenencia_due_at,          'icon'=>'bi-cash-coin'],
            ['label'=>'Tarjeta Circ.',  'date'=>$vehicle->circulation_card_due_at,  'icon'=>'bi-card-text'],
          ];
          $printed = 0;
        @endphp

        @foreach($fechas as $f)
          @php $st = $getStatus($f['date']); $dStr = $fmt($f['date']); @endphp
          @if($dStr)
            @php $printed++; @endphp
            <div class="promo-item">
              <i class="bi {{ $f['icon'] }}"></i>
              <div>
                <strong>{{ $f['label'] }}:</strong> {{ $dStr }}
                @if($st['class'])
                  <span class="{{ $st['class'] }}">({{ $st['text'] }})</span>
                @endif
              </div>
            </div>
          @endif
        @endforeach

        @if($printed === 0)
          <div class="promo-item"><i class="bi bi-check-circle"></i> Sin vencimientos pendientes configurados.</div>
        @endif
      </div>
    </div>

    {{-- ===== TAB: ESPECIFICACIONES ===== --}}
    <div class="tab-panel" data-panel="specs">
      <div class="specs-grid">
        <div>
          <div class="spec-label">Año</div>
          <div class="spec-value">{{ $safe($vehicle->year) }}</div>
        </div>
        <div>
          <div class="spec-label">Placa</div>
          <div class="spec-value">{{ $safe($vehicle->plate) }}</div>
        </div>
        <div>
          <div class="spec-label">Marca</div>
          <div class="spec-value">{{ $safe($vehicle->brand) }}</div>
        </div>
        <div>
          <div class="spec-label">Modelo</div>
          <div class="spec-value">{{ $safe($vehicle->model) }}</div>
        </div>
        <div>
          <div class="spec-label">Última Verificación</div>
          <div class="spec-value">{{ $fmt($vehicle->last_verification_at) ?? '—' }}</div>
        </div>
        <div>
          <div class="spec-label">Último Servicio</div>
          <div class="spec-value">{{ $fmt($vehicle->last_service_at) ?? '—' }}</div>
        </div>
        <div style="grid-column: span 2">
          <div class="spec-label">Notas</div>
          <div class="spec-value" style="font-size:.95rem; font-weight:400;">
            {{ $vehicle->notes ?? 'Sin notas adicionales.' }}
          </div>
        </div>
      </div>
    </div>

    {{-- ===== TAB: DOCUMENTOS ===== --}}
    <div class="tab-panel" data-panel="docs">
      <div class="actions-area">
        <h5 class="mb-3" style="font-weight:700;">Documentos</h5>

        <div class="mb-4">
          @php $docsShown = 0; @endphp
          @foreach($docsMap as $type => $label)
            @php
              $doc = $docsByType->get($type);
              $url = $doc? Storage::url($doc->path) : null;
            @endphp

            @if($url)
              @php $docsShown++; @endphp
              <a href="{{ $url }}" target="_blank" class="doc-link">
                <span><i class="bi bi-file-earmark-pdf me-2"></i> {{ $label }}</span>
                <i class="bi bi-download"></i>
              </a>
            @endif
          @endforeach

          @if($docsShown === 0)
            <div class="text-muted">No hay documentos cargados para esta unidad.</div>
          @endif
        </div>

        <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn-action-primary">
          Editar Vehículo <i class="bi bi-arrow-right"></i>
        </a>
      </div>
    </div>

  </div>
</div>

<script>
  function changeImage(url, el) {
    const img = document.getElementById('heroImage');
    img.style.opacity = 0;
    setTimeout(() => {
      img.src = url;
      img.style.opacity = 1;
    }, 180);

    document.querySelectorAll('.thumb-dot').forEach(d => d.classList.remove('active'));
    el.classList.add('active');
  }

  // ✅ Tabs: ahora sí abren Especificaciones/Documentos
  (function(){
    const tabs = Array.from(document.querySelectorAll('.nav-item-custom[data-tab]'));
    const panels = Array.from(document.querySelectorAll('.tab-panel[data-panel]'));

    function openTab(key){
      tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === key));
      panels.forEach(p => p.classList.toggle('active', p.dataset.panel === key));
    }

    tabs.forEach(t => {
      t.addEventListener('click', () => openTab(t.dataset.tab));
    });
  })();
</script>
@endsection
