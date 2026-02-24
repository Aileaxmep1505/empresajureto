@extends('layouts.app')

@section('title', 'Rem y Fac')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

@php
  use Carbon\Carbon;

  // ✅ Fechas en español (meses + diffForHumans)
  Carbon::setLocale('es');

  // ✅ Ultra-safe: sin match()
  $getMeta = function($p){
    $ext = strtolower($p->extension ?: 'file');

    if ($ext === 'pdf') return ['icon'=>'PDF', 'color'=>'#ef4444', 'bg'=>'#fef2f2'];
    if (in_array($ext, ['xls','xlsx','csv'], true)) return ['icon'=>'XLS', 'color'=>'#10b981', 'bg'=>'#ecfdf5'];
    if (in_array($ext, ['doc','docx'], true)) return ['icon'=>'DOC', 'color'=>'#3b82f6', 'bg'=>'#eff6ff'];
    if (in_array($ext, ['jpg','jpeg','png'], true)) return ['icon'=>'IMG', 'color'=>'#f59e0b', 'bg'=>'#fffbeb'];

    return ['icon'=>'FILE','color'=>'#64748b', 'bg'=>'#f8fafc'];
  };

  // ✅ No closures dentro de @json()
  $tableRows = ($allPurchases ?? collect())->map(function($i){
    return [
      'category'          => $i->category,
      'document_datetime' => $i->document_datetime,
      'supplier_name'     => $i->supplier_name,
      'item_name'         => $i->item_name,
      'item_raw'          => $i->item_raw,
      'unit_price'        => (float) $i->unit_price,
      'qty'               => (float) $i->qty,
      'line_total'        => (float) $i->line_total,
    ];
  })->values()->all();

  $supCompraRows = ($topSuppliersCompra ?? collect())->map(function($s){
    return [
      'supplier_name' => $s->supplier_name,
      'total_amount'  => (float) $s->total_amount,
    ];
  })->values()->all();

  $supVentaRows = ($topSuppliersVenta ?? collect())->map(function($s){
    return [
      'supplier_name' => $s->supplier_name,
      'total_amount'  => (float) $s->total_amount,
    ];
  })->values()->all();
@endphp

<div class="container-fluid py-5" id="pubsBase">
  <style>
    #pubsBase{
      --ink:#0f172a; --muted:rgba(15,23,42,.62); --line:rgba(15,23,42,.10);
      --shadow2: 0 10px 30px rgba(2,6,23,.07);
    }
    #pubsBase .bg{
      border-radius: 28px; padding: 30px; border: 1px solid rgba(15,23,42,.06);
      background: radial-gradient(1200px 520px at 50% -10%, rgba(56,189,248,.35), transparent 55%),
                  radial-gradient(900px 420px at 20% 0%, rgba(59,130,246,.18), transparent 55%),
                  radial-gradient(900px 420px at 85% 10%, rgba(16,185,129,.12), transparent 55%),
                  linear-gradient(180deg, rgba(255,255,255,.85), rgba(255,255,255,.55));
      box-shadow: 0 20px 80px rgba(2,6,23,.06); min-height: 85vh;
    }
    #pubsBase .hero{ display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; gap:12px; flex-wrap:wrap; }
    #pubsBase .hero h1{ font-size:24px; font-weight:700; color:var(--ink); margin:0; }
    #pubsBase .hero p{ margin:6px 0 0 0; color:var(--muted); }

    #pubsBase .tabNav { display:flex; gap:5px; border-bottom:2px solid rgba(15,23,42,.08); margin-bottom:18px; }
    #pubsBase .tabBtn { background:transparent; border:none; font-size:14px; font-weight:700; color:var(--muted); padding:12px 20px; cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-2px; transition:.2s; border-radius:10px 10px 0 0; }
    #pubsBase .tabBtn.active { color:#3b82f6; border-bottom-color:#3b82f6; }

    #pubsBase .subNav{
      display:flex; gap:6px; flex-wrap:wrap;
      background: rgba(255,255,255,.7);
      border:1px solid rgba(15,23,42,.08);
      padding:6px; border-radius:14px;
      box-shadow: 0 10px 30px rgba(2,6,23,.05);
      margin: 0 0 18px 0;
      width: fit-content;
    }
    #pubsBase .subBtn{
      border:none; background:transparent; color:rgba(15,23,42,.65);
      font-weight:900; font-size:12px; padding:8px 12px;
      border-radius:12px; cursor:pointer; transition:.15s;
      display:inline-flex; align-items:center; gap:8px;
    }
    #pubsBase .subBtn:hover{ background: rgba(59,130,246,.08); color:#1d4ed8; }
    #pubsBase .subBtn.active{ background: rgba(59,130,246,.12); color:#1d4ed8; box-shadow: 0 6px 18px rgba(59,130,246,.18); }

    #pubsBase .chip{
      font-size:10px; font-weight:900; padding:2px 8px; border-radius:999px;
      border:1px solid rgba(15,23,42,.08); background: rgba(255,255,255,.7); color: rgba(15,23,42,.65);
    }
    #pubsBase .chip.blue{ background: rgba(59,130,246,.10); color:#1d4ed8; border-color: rgba(59,130,246,.22); }
    #pubsBase .chip.mint{ background: rgba(16,185,129,.10); color:#047857; border-color: rgba(16,185,129,.22); }

    #pubsBase .grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:20px; }
    #pubsBase a.fileCard { text-decoration:none; display:flex; flex-direction:column; background: rgba(255,255,255,0.8); border: 1px solid var(--line); border-radius:16px; overflow:hidden; transition:.2s; position:relative; height:100%; box-shadow: var(--shadow2); backdrop-filter: blur(10px); }
    #pubsBase a.fileCard:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(0,0,0,0.08); border-color: #cbd5e1; background: rgba(255,255,255,0.95); }
    #pubsBase .fc-top { padding:12px; display:flex; justify-content:space-between; align-items:center; }
    #pubsBase .fc-badge { font-size:10px; font-weight:900; padding:4px 8px; border-radius:6px; letter-spacing:.5px; }
    #pubsBase .fc-body { flex:1; display:flex; align-items:center; justify-content:center; padding:10px 0; }
    #pubsBase .fc-icon-box { width:60px; height:60px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:900; color:white; box-shadow: 0 8px 15px -5px rgba(0,0,0,0.2); }
    #pubsBase .fc-img-preview { width:100%; height:140px; object-fit:cover; }
    #pubsBase .fc-foot { padding:12px; background: rgba(248,250,252, 0.6); border-top: 1px solid var(--line); }
    #pubsBase .fc-title { font-size:14px; font-weight:800; color:var(--ink); margin-bottom:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:block; }
    #pubsBase .fc-date { font-size:11px; color:var(--muted); display:block; }
    #pubsBase .pin-tag { position:absolute; top:10px; right:10px; background:#fef3c7; color:#d97706; font-size:10px; padding:2px 6px; border-radius:4px; font-weight:900; z-index:2; }

    .btn-upload { background:#3b82f6; color:white; padding:10px 20px; border-radius:12px; text-decoration:none; font-size:13px; font-weight:800; display:inline-flex; align-items:center; gap:8px; transition:.2s; box-shadow: 0 4px 15px rgba(59,130,246, 0.3); }
    .btn-upload:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(59,130,246, 0.4); color:white; }

    .dashGrid { display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px; margin-bottom:18px; }
    .statCard { background: rgba(255,255,255,0.95); border:1px solid var(--line); border-radius:18px; padding:24px; box-shadow: var(--shadow2); }

    .table-responsive { overflow-x:auto; background: rgba(255,255,255,0.95); border-radius:18px; border: 1px solid var(--line); box-shadow: var(--shadow2); }
    .table-clean { width:100%; border-collapse:collapse; min-width: 760px; }
    .table-clean th { text-align:left; padding:15px 20px; background:#f8fafc; color:var(--muted); font-size:11px; font-weight:900; text-transform:uppercase; letter-spacing:.5px; border-bottom: 1px solid var(--line); }
    .table-clean td { padding:14px 20px; border-bottom: 1px solid var(--line); color:var(--ink); font-size:13px; }
    .table-clean tr:last-child td { border-bottom:none; }
    .table-clean tr:hover { background:#f1f5f9; }

    .d-none { display:none !important; }

    #pubsBase .kpiBig{ font-size:36px; font-weight:900; color:var(--ink); margin-top:5px; }
    #pubsBase .kpiSmall{ margin-top:10px; color:var(--muted); font-weight:900; font-size:12px; display:flex; gap:10px; flex-wrap:wrap; }
    #pubsBase .kpiSmall span{ display:inline-flex; align-items:center; gap:8px; }
    #pubsBase .mutedBox{
      background: rgba(248,250,252,.65);
      border:1px dashed rgba(15,23,42,.14);
      border-radius: 16px;
      padding: 14px 16px;
      color: rgba(15,23,42,.65);
      font-weight: 900;
      font-size: 12px;
      margin-top:14px;
    }

    /* =========================
       ✅ SOLO INDEX: paginación PRO (sin Previous/Next) y sin tooltip "Previous/Next"
    ========================= */
    #pubsBase .idxPager{
      margin-top: 18px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      flex-wrap:wrap;
      padding: 12px 14px;
      border-radius: 18px;
      border: 1px solid rgba(15,23,42,.08);
      background: rgba(255,255,255,.70);
      box-shadow: 0 10px 30px rgba(2,6,23,.05);
    }
    #pubsBase .idxPager .idxInfo{
      font-weight:900;
      color: rgba(15,23,42,.55);
      font-size: 12px;
      letter-spacing: .01em;
      padding: 4px 2px;
      white-space: nowrap;
    }
    #pubsBase .idxPager nav[role="navigation"]{ margin:0 !important; }

    /* ✅ Oculta SOLO el bloque móvil (texto "Previous / Next") */
    #pubsBase .idxPager nav[role="navigation"] > div:first-child{
      display:none !important;
    }
    /* ✅ Mantiene el bloque desktop (flechas < > + números) */
    #pubsBase .idxPager nav[role="navigation"] > div:last-child{
      display:flex !important;
      align-items:center !important;
      justify-content:flex-end !important;
      gap:10px !important;
      width:auto !important;
    }

    /* Oculta el "Showing ..." default */
    #pubsBase .idxPager nav[role="navigation"] p.text-sm{
      display:none !important;
    }

    /* Contenedor de botones */
    #pubsBase .idxPager nav[role="navigation"] span.relative.z-0{
      display:inline-flex !important;
      gap:10px !important;
      align-items:center !important;
      padding: 0 !important;
      background: transparent !important;
      box-shadow: none !important;
    }

    /* Botones */
    #pubsBase .idxPager nav[role="navigation"] a,
    #pubsBase .idxPager nav[role="navigation"] span[aria-current="page"] span{
      display:inline-flex !important;
      align-items:center !important;
      justify-content:center !important;
      min-width: 42px !important;
      height: 40px !important;
      padding: 0 14px !important;
      border-radius: 14px !important;
      font-weight: 900 !important;
      font-size: 13px !important;
      text-decoration: none !important;
      border: 1px solid rgba(15,23,42,.08) !important;
      background: rgba(255,255,255,.75) !important;
      color: rgba(15,23,42,.78) !important;
      box-shadow: 0 10px 22px rgba(2,6,23,.06) !important;
      transition: .15s !important;
    }
    #pubsBase .idxPager nav[role="navigation"] a:hover{
      transform: translateY(-1px) !important;
      background: rgba(255,255,255,.95) !important;
      border-color: rgba(15,23,42,.12) !important;
    }

    /* Activo verde pastel */
    #pubsBase .idxPager nav[role="navigation"] span[aria-current="page"] span{
      background: rgba(16,185,129,.14) !important;
      color: #047857 !important;
      border-color: rgba(16,185,129,.22) !important;
      box-shadow: 0 14px 28px rgba(16,185,129,.14) !important;
    }

    /* Disabled */
    #pubsBase .idxPager nav[role="navigation"] span[aria-disabled="true"] span{
      opacity: .45 !important;
      cursor: not-allowed !important;
      transform:none !important;
      box-shadow:none !important;
      background: rgba(255,255,255,.55) !important;
    }

    /* ✅ Quita el tooltip "Previous/Next" en flechas (muchos navegadores lo sacan del title) */
    #pubsBase .idxPager nav[role="navigation"] a[rel="prev"],
    #pubsBase .idxPager nav[role="navigation"] a[rel="next"]{
      title: none !important; /* (no todos lo respetan, pero ayuda) */
    }

    /* ✅ Evita que exista title en hover usando CSS (y adicionalmente lo limpiamos por JS abajo) */
    #pubsBase .idxPager nav[role="navigation"] a[rel="prev"] span,
    #pubsBase .idxPager nav[role="navigation"] a[rel="next"] span{
      font-size:0 !important; /* por si trae texto oculto */
    }

    @media (max-width: 640px){
      #pubsBase .idxPager{ padding: 10px 10px; }
      #pubsBase .idxPager .idxInfo{ width: 100%; }
      #pubsBase .idxPager nav[role="navigation"] > div:last-child{ width:100% !important; justify-content:flex-start !important; }
      #pubsBase .idxPager nav[role="navigation"] a,
      #pubsBase .idxPager nav[role="navigation"] span[aria-current="page"] span{
        min-width: 40px !important;
        height: 38px !important;
        border-radius: 13px !important;
      }
    }
  </style>

  <div class="bg">
    <div class="hero">
      <div>
        <h1>Gestor de Documentos</h1>
        <p>Comparativo de compras vs ventas.</p>
      </div>
      @auth
        <a class="btn-upload" href="{{ route('publications.create') }}">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"></path></svg>
          Subir Nuevo
        </a>
      @endauth
    </div>

    @if(session('ok'))
      <div class="alert alert-success" style="border-radius:12px; border:none; background:rgba(16,185,129,0.1); color:#065f46;">
        {{ session('ok') }}
      </div>
    @endif

    <div class="tabNav">
      <button type="button" class="tabBtn active" onclick="switchTab('pubs')" id="btn-pubs">Mis Documentos</button>
      <button type="button" class="tabBtn" onclick="switchTab('stats')" id="btn-stats">Estadísticas</button>
    </div>

    {{-- TAB 1 --}}
    <div id="tab-pubs-content" class="idxWrap">
      @php
        $pinnedCount = ($pinned ?? collect())->count();
      @endphp

      @if($pinnedCount)
        <h6 style="font-size:12px; font-weight:900; color:var(--muted); margin-bottom:12px; letter-spacing:1px; text-transform:uppercase;">
          Fijados
        </h6>

        <div class="grid" style="margin-bottom: 24px;">
          @foreach($pinned as $p)
            @php $meta = $getMeta($p); @endphp
            <a href="{{ route('publications.show', $p) }}" class="fileCard">
              <div class="pin-tag">FIJADO</div>
              <div class="fc-top">
                <span class="fc-badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['color'] }}">{{ $meta['icon'] }}</span>
              </div>
              <div class="fc-body">
                @if($p->is_image ?? false)
                  <img src="{{ $p->url }}" class="fc-img-preview" alt="preview">
                @else
                  <div class="fc-icon-box" style="background:{{ $meta['color'] }}">{{ $meta['icon'] }}</div>
                @endif
              </div>
              <div class="fc-foot">
                <span class="fc-title" title="{{ $p->title }}">{{ $p->title }}</span>
                <div style="display:flex; justify-content:space-between; margin-top:4px;">
                  <span class="fc-date">{{ optional($p->created_at)->translatedFormat('d M, Y') }}</span>
                  <span class="fc-date" style="font-weight:900;">{{ $p->nice_size ?? '' }}</span>
                </div>
              </div>
            </a>
          @endforeach
        </div>
      @endif

      <h6 style="font-size:12px; font-weight:900; color:var(--muted); margin-bottom:12px; letter-spacing:1px; text-transform:uppercase;">
        Recientes
      </h6>

      <div class="grid">
        @forelse($latest as $p)
          @php $meta = $getMeta($p); @endphp
          <a href="{{ route('publications.show', $p) }}" class="fileCard">
            <div class="fc-top">
              <span class="fc-badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['color'] }}">{{ $meta['icon'] }}</span>
            </div>
            <div class="fc-body">
              @if($p->is_image ?? false)
                <img src="{{ $p->url }}" class="fc-img-preview" style="height:140px; width:92%; border-radius:12px;" alt="preview">
              @else
                <div class="fc-icon-box" style="background:{{ $meta['color'] }}; width:65px; height:65px;">{{ $meta['icon'] }}</div>
              @endif
            </div>
            <div class="fc-foot">
              <span class="fc-title" title="{{ $p->title }}">{{ $p->title }}</span>
              <div style="display:flex; justify-content:space-between; margin-top:4px;">
                <span class="fc-date">{{ optional($p->created_at)->locale('es')->diffForHumans() }}</span>
                <span class="fc-date" style="font-weight:900;">{{ $p->nice_size ?? '' }}</span>
              </div>
            </div>
          </a>
        @empty
          <div style="grid-column: 1/-1; padding:40px; text-align:center; color:var(--muted); font-weight:900;">
            No hay documentos subidos.
          </div>
        @endforelse
      </div>

      {{-- ✅ Paginación: sin "Previous/Next" y sin tooltip "Previous/Next" en flechas --}}
      @if(method_exists($latest, 'firstItem') && $latest->total())
        <div class="idxPager">
          <div class="idxInfo">
            Mostrando {{ $latest->firstItem() }}–{{ $latest->lastItem() }} de {{ $latest->total() }} registros
          </div>
          <div class="idxLinks">
            {{ $latest->onEachSide(1)->links() }}
          </div>
        </div>
      @endif
    </div>

    {{-- TAB 2 (NO TOCADO) --}}
    <div id="tab-stats-content" class="d-none">
      <div class="subNav">
        <button type="button" class="subBtn active" id="sub-compare" onclick="setStatsMode('compare')">
          Comparativo <span class="chip">2 series</span>
        </button>
        <button type="button" class="subBtn" id="sub-compra" onclick="setStatsMode('compra')">
          Compras <span class="chip mint">compra</span>
        </button>
        <button type="button" class="subBtn" id="sub-venta" onclick="setStatsMode('venta')">
          Ventas <span class="chip blue">venta</span>
        </button>
      </div>

      <div class="dashGrid">
        <div class="statCard">
          <h3 id="kpiTitle" style="font-size:12px; text-transform:uppercase; color:var(--muted); font-weight:900; letter-spacing:.08em;">
            Comparativo General
          </h3>
          <div class="kpiBig" id="kpiBig">$0.00</div>
          <div class="kpiSmall">
            <span><span class="chip mint">Compras</span> <b id="kpiCompra">$0.00</b></span>
            <span><span class="chip blue">Ventas</span> <b id="kpiVenta">$0.00</b></span>
          </div>

          <div id="kpiInsight" class="mutedBox" style="display:flex; flex-direction:column; gap:10px;">
            <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
              <div style="font-weight:900; color:rgba(15,23,42,.78);" id="insTitle">Insight</div>
              <span class="chip" id="insChip">balance</span>
            </div>
            <div style="display:flex; flex-direction:column; gap:6px;">
              <div style="color:rgba(15,23,42,.72); font-weight:900;" id="insLine1">—</div>
              <div style="color:rgba(15,23,42,.62); font-weight:900; font-size:11px;" id="insLine2">—</div>
            </div>
          </div>
        </div>

        <div class="statCard" style="grid-column: span 2;">
          <h3 style="font-size:15px; font-weight:900; color:var(--ink); margin-bottom:20px;">Tendencia Mensual</h3>
          <div id="chartMonthly" style="width:100%; min-height:250px;"></div>
        </div>
      </div>

      <div class="dashGrid">
        <div class="statCard" style="grid-column: span 2;">
          <h3 style="font-size:15px; font-weight:900; color:var(--ink); margin-bottom:20px;">Movimiento Diario (Últimos 30 días)</h3>
          <div id="chartDaily" style="width:100%; min-height:250px;"></div>
        </div>
        <div class="statCard">
          <h3 style="font-size:15px; font-weight:900; color:var(--ink); margin-bottom:20px;">Top 10 Productos</h3>
          <div id="chartProducts" style="width:100%; min-height:250px;"></div>
        </div>
      </div>

      <h3 id="tableTitle" style="font-size:16px; font-weight:900; color:var(--ink); margin: 8px 0 15px 0;">
        Desglose Reciente (Compras + Ventas)
      </h3>

      <div class="table-responsive">
        <table class="table-clean">
          <thead>
            <tr>
              <th>Tipo</th>
              <th>Fecha</th>
              <th>Concepto / Producto</th>
              <th>Proveedor</th>
              <th>Precio</th>
              <th>Cant</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody id="rowsTbody"></tbody>
        </table>
      </div>

      <h3 style="font-size:15px; font-weight:900; color:var(--ink); margin:30px 0 14px;">Top Proveedores</h3>
      <div id="suppliersWrap" style="display:flex; gap:15px; flex-wrap:wrap;"></div>
    </div>
  </div>

<script>
  // ✅ Limpia tooltip/textos "Previous/Next" en flechas (lo ponen como title/aria-label)
  document.addEventListener('DOMContentLoaded', function(){
    try{
      var nav = document.querySelector('#pubsBase .idxPager nav[role="navigation"]');
      if(nav){
        nav.querySelectorAll('a[rel="prev"], a[rel="next"]').forEach(function(a){
          a.removeAttribute('title');
          a.setAttribute('aria-label', 'Página');
        });
      }
    }catch(e){}
  });

  // ✅ Data desde backend
  var DATA = {
    totals: {
      compra: @json($totalSpentCompra ?? 0),
      venta:  @json($totalSpentVenta ?? 0),
      all:    @json($totalSpent ?? 0)
    },
    monthly: {
      labels: @json($chartLabels ?? []),
      compra: @json($monthlyCompra ?? []),
      venta:  @json($monthlyVenta ?? [])
    },
    daily: {
      labels: @json($dailyLabels ?? []),
      compra: @json($dailyCompra ?? []),
      venta:  @json($dailyVenta ?? [])
    },
    products: {
      compra: @json($prodChartDataCompra ?? []),
      venta:  @json($prodChartDataVenta ?? [])
    },
    table: @json($tableRows ?? []),
    suppliers: {
      compra: @json($supCompraRows ?? []),
      venta:  @json($supVentaRows ?? [])
    }
  };

  function money(n){
    n = Number(n || 0);
    return '$' + n.toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
  }

  function fmtDate(d){
    if(!d) return '-';
    try{
      var str = String(d).replace(' ', 'T');
      var dt = new Date(str);
      if(isNaN(dt.getTime())) return String(d);
      return dt.toLocaleDateString('es-MX');
    }catch(e){
      return String(d);
    }
  }

  function esc(s){
    s = String(s == null ? '' : s);
    return s
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;')
      .replace(/'/g,'&#039;');
  }

  function toNum(v){
    if(v == null) return 0;
    if(typeof v === 'number') return isFinite(v) ? v : 0;
    var s = String(v).trim();
    s = s.replace(/[^0-9.\-]/g,'');
    var n = Number(s);
    return isFinite(n) ? n : 0;
  }

  DATA.totals.all = toNum(DATA.totals.compra) + toNum(DATA.totals.venta);

  function seriesFrom(labels, raw){
    labels = Array.isArray(labels) ? labels : [];

    if(Array.isArray(raw) && (raw.length === 0 || typeof raw[0] === 'number' || typeof raw[0] === 'string')){
      return labels.map(function(_, i){ return toNum(raw[i]); });
    }

    if(raw && typeof raw === 'object' && !Array.isArray(raw)){
      return labels.map(function(l){ return toNum(raw[l]); });
    }

    if(Array.isArray(raw) && raw.length && typeof raw[0] === 'object'){
      var map = {};
      raw.forEach(function(r){
        var k = r.month || r.month_id || r.label || r.x || r.day || r.date;
        var val = r.total || r.y || r.value || r.amount;
        if(k != null) map[String(k)] = toNum(val);
      });
      return labels.map(function(l){ return toNum(map[String(l)]); });
    }

    return labels.map(function(){ return 0; });
  }

  var statsMode = 'compare';
  var chartMonthly = null, chartDaily = null, chartProducts = null;
  var chartsInitialized = false;

  function switchTab(tab) {
    document.querySelectorAll('.tabBtn').forEach(function(b){ b.classList.remove('active'); });
    document.getElementById('tab-pubs-content').classList.add('d-none');
    document.getElementById('tab-stats-content').classList.add('d-none');

    document.getElementById('btn-' + tab).classList.add('active');
    document.getElementById('tab-' + tab + '-content').classList.remove('d-none');

    if(tab === 'stats') ensureChartsVisibleInit();
  }

  function ensureChartsVisibleInit(){
    if(typeof ApexCharts === 'undefined'){
      console.error('ApexCharts no cargó');
      document.getElementById('chartMonthly').innerHTML =
        '<div style="text-align:center; padding:50px; color:#ef4444; font-weight:900;">ApexCharts no cargó</div>';
      return;
    }

    if(!chartsInitialized){
      chartsInitialized = true;
      setStatsMode(statsMode, true);
      return;
    }

    setStatsMode(statsMode, false);
    setTimeout(function(){
      try{ if(chartMonthly) chartMonthly.resize(); }catch(e){}
      try{ if(chartDaily) chartDaily.resize(); }catch(e){}
      try{ if(chartProducts) chartProducts.resize(); }catch(e){}
    }, 50);
  }

  function setActiveSub(mode){
    ['compare','compra','venta'].forEach(function(m){
      var el = document.getElementById('sub-' + m);
      if(!el) return;
      if(m === mode) el.classList.add('active'); else el.classList.remove('active');
    });
  }

  function setKpi(mode){
    var compra = toNum(DATA.totals.compra);
    var venta  = toNum(DATA.totals.venta);
    var all    = toNum(DATA.totals.all);

    document.getElementById('kpiCompra').textContent = money(compra);
    document.getElementById('kpiVenta').textContent  = money(venta);

    if(mode === 'compra'){
      document.getElementById('kpiTitle').textContent = 'Total Histórico (Compras)';
      document.getElementById('kpiBig').textContent   = money(compra);
    } else if(mode === 'venta'){
      document.getElementById('kpiTitle').textContent = 'Total Histórico (Ventas)';
      document.getElementById('kpiBig').textContent   = money(venta);
    } else {
      document.getElementById('kpiTitle').textContent = 'Comparativo General';
      document.getElementById('kpiBig').textContent   = money(all);
    }
  }

  function setInsight(mode){
    var compra = toNum(DATA.totals.compra);
    var venta  = toNum(DATA.totals.venta);

    var gap = compra - venta;
    var recovery = (compra > 0) ? (venta/compra)*100 : 0;

    var chipEl = document.getElementById('insChip');
    var l1 = document.getElementById('insLine1');
    var l2 = document.getElementById('insLine2');

    if(!chipEl || !l1 || !l2) return;

    if(mode === 'compra'){
      chipEl.className = 'chip mint';
      chipEl.textContent = 'compras';
      l1.textContent = 'Compras acumuladas: ' + money(compra) + '.';
      l2.textContent = 'Meta de ventas para recuperar: ' + money(compra) + '.';
      return;
    }

    if(mode === 'venta'){
      chipEl.className = 'chip blue';
      chipEl.textContent = 'ventas';
      l1.textContent = 'Ventas acumuladas: ' + money(venta) + '.';
      l2.textContent = 'Equivalen al ' + recovery.toFixed(1) + '% de compras (ventas / compras).';
      return;
    }

    if(gap > 0.01){
      chipEl.className = 'chip';
      chipEl.textContent = 'gap';
      l1.textContent = 'Te faltan ' + money(gap) + ' en ventas para igualar compras.';
      l2.textContent = 'Recuperación: ' + recovery.toFixed(1) + '% (ventas / compras).';
    } else if(gap < -0.01){
      chipEl.className = 'chip blue';
      chipEl.textContent = 'arriba';
      l1.textContent = 'Vas arriba por ' + money(Math.abs(gap)) + ' (ventas sobre compras).';
      l2.textContent = 'Relación: ' + recovery.toFixed(1) + '% (ventas / compras).';
    } else {
      chipEl.className = 'chip mint';
      chipEl.textContent = 'equilibrio';
      l1.textContent = 'Balance equilibrado: ventas ≈ compras.';
      l2.textContent = 'Mantén el ritmo para sostener el equilibrio.';
    }
  }

  function baseY(){
    return {
      yaxis: {
        labels: {
          formatter: function(val){ return "$" + Number(val||0).toLocaleString('es-MX'); },
          style: { colors: '#0f172a', fontWeight: 900 }
        }
      },
      tooltip: { y: { formatter: function(val){ return "$" + Number(val||0).toLocaleString('es-MX'); } } }
    };
  }

  function barDataLabels(){
    return {
      dataLabels: {
        enabled: true,
        offsetY: -8,
        style: { fontSize: '11px', fontWeight: 900, colors: ['#0f172a'] },
        background: { enabled: false },
        dropShadow: { enabled: false }
      }
    };
  }

  function xaxisDates(labels){
    return {
      xaxis: {
        categories: labels,
        labels: {
          rotate: -45,
          rotateAlways: true,
          hideOverlappingLabels: true,
          trim: true,
          style: { colors: '#0f172a', fontWeight: 900 }
        }
      }
    };
  }

  function buildMonthlyOptions(mode){
    var labels = Array.isArray(DATA.monthly.labels) ? DATA.monthly.labels : [];
    var compra = seriesFrom(labels, DATA.monthly.compra);
    var venta  = seriesFrom(labels, DATA.monthly.venta);

    var common = Object.assign({
      chart: { type: 'area', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
      fill: { type: 'gradient', gradient: { shadeIntensity:1, opacityFrom:0.35, opacityTo:0.04, stops:[0,100]} },
      stroke: { curve: 'smooth', width: 2 }
    }, baseY(), xaxisDates(labels));

    if(mode === 'compare'){
      return Object.assign({
        series: [
          { name: 'Compras', data: compra },
          { name: 'Ventas',  data: venta  }
        ],
        colors: ['#10b981', '#3b82f6']
      }, common);
    }

    var name = (mode === 'compra') ? 'Compras' : 'Ventas';
    var data = (mode === 'compra') ? compra : venta;
    var color = (mode === 'compra') ? '#10b981' : '#3b82f6';

    return Object.assign({
      series: [{ name: name, data: data }],
      colors: [color]
    }, common);
  }

  function buildDailyOptions(mode){
    var labels = Array.isArray(DATA.daily.labels) ? DATA.daily.labels : [];
    var compra = seriesFrom(labels, DATA.daily.compra);
    var venta  = seriesFrom(labels, DATA.daily.venta);

    var common = Object.assign({
      chart: { type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
      plotOptions: { bar: { borderRadius: 4, columnWidth: '55%', dataLabels: { position: 'top' } } }
    }, baseY(), barDataLabels(), xaxisDates(labels));

    if(mode === 'compare'){
      return Object.assign({
        series: [
          { name: 'Compras', data: compra },
          { name: 'Ventas',  data: venta  }
        ],
        colors: ['#10b981', '#3b82f6']
      }, common);
    }

    var name = (mode === 'compra') ? 'Compras' : 'Ventas';
    var data = (mode === 'compra') ? compra : venta;
    var color = (mode === 'compra') ? '#10b981' : '#3b82f6';

    return Object.assign({
      series: [{ name: name, data: data }],
      colors: [color]
    }, common);
  }

  function buildProductsOptions(mode){
    if(mode === 'compare') mode = 'compra';

    var data = (mode === 'venta') ? (DATA.products.venta || []) : (DATA.products.compra || []);
    var color = (mode === 'venta') ? '#3b82f6' : '#10b981';

    return {
      series: [{ name: (mode === 'venta') ? 'Total Vendido' : 'Total Comprado', data: data }],
      chart: { type: 'bar', height: 320, toolbar: { show: false }, fontFamily: 'inherit' },
      colors: [color],
      plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '70%' } },
      xaxis: {
        labels: {
          formatter: function(val){ return "$" + Number(val||0).toLocaleString('es-MX'); },
          style: { colors:'#0f172a', fontWeight:900 }
        }
      },
      yaxis: { labels: { maxWidth: 220, style: { fontSize: '11px', fontWeight: 900, colors:'#0f172a' } } },
      tooltip: { y: { formatter: function(val){ return "$" + Number(val||0).toLocaleString('es-MX'); } } },
      dataLabels: { enabled: false }
    };
  }

  function anyNonZero(arr){
    if(!Array.isArray(arr)) return false;
    for(var i=0;i<arr.length;i++){ if(toNum(arr[i]) !== 0) return true; }
    return false;
  }

  function renderOrUpdateCharts(mode, forceRender){
    var elM = document.querySelector("#chartMonthly");
    var elD = document.querySelector("#chartDaily");
    var elP = document.querySelector("#chartProducts");

    var labelsM = Array.isArray(DATA.monthly.labels) ? DATA.monthly.labels : [];
    var labelsD = Array.isArray(DATA.daily.labels) ? DATA.daily.labels : [];

    var mCompra = seriesFrom(labelsM, DATA.monthly.compra);
    var mVenta  = seriesFrom(labelsM, DATA.monthly.venta);
    var dCompra = seriesFrom(labelsD, DATA.daily.compra);
    var dVenta  = seriesFrom(labelsD, DATA.daily.venta);

    if(!labelsM.length || (!anyNonZero(mCompra) && !anyNonZero(mVenta))){
      elM.innerHTML = '<div style="text-align:center; padding:50px; color:#94a3b8; font-weight:900;">Sin datos</div>';
    } else {
      var o1 = buildMonthlyOptions(mode);
      if(chartMonthly) chartMonthly.updateOptions(o1, true, true);
      else if(forceRender){ chartMonthly = new ApexCharts(elM, o1); chartMonthly.render(); }
    }

    if(!labelsD.length || (!anyNonZero(dCompra) && !anyNonZero(dVenta))){
      elD.innerHTML = '<div style="text-align:center; padding:50px; color:#94a3b8; font-weight:900;">Sin datos</div>';
    } else {
      var o2 = buildDailyOptions(mode);
      if(chartDaily) chartDaily.updateOptions(o2, true, true);
      else if(forceRender){ chartDaily = new ApexCharts(elD, o2); chartDaily.render(); }
    }

    var prodArr = (mode === 'venta') ? (DATA.products.venta || []) : (DATA.products.compra || []);
    if(!Array.isArray(prodArr) || !prodArr.length){
      elP.innerHTML = '<div style="text-align:center; padding:50px; color:#94a3b8; font-weight:900;">Sin datos</div>';
    } else {
      var o3 = buildProductsOptions(mode);
      if(chartProducts) chartProducts.updateOptions(o3, true, true);
      else if(forceRender){ chartProducts = new ApexCharts(elP, o3); chartProducts.render(); }
    }

    setTimeout(function(){
      try{ if(chartMonthly) chartMonthly.resize(); }catch(e){}
      try{ if(chartDaily) chartDaily.resize(); }catch(e){}
      try{ if(chartProducts) chartProducts.resize(); }catch(e){}
    }, 60);
  }

  function renderTable(mode){
    var tbody = document.getElementById('rowsTbody');
    var title = document.getElementById('tableTitle');
    if(!tbody) return;

    var rows = Array.isArray(DATA.table) ? DATA.table.slice() : [];

    if(mode === 'compra'){
      title.textContent = 'Desglose Reciente (Compras)';
      rows = rows.filter(function(r){ return String(r.category || '') === 'compra'; });
    } else if(mode === 'venta'){
      title.textContent = 'Desglose Reciente (Ventas)';
      rows = rows.filter(function(r){ return String(r.category || '') === 'venta'; });
    } else {
      title.textContent = 'Desglose Reciente (Compras + Ventas)';
    }

    if(!rows.length){
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:20px; color:#94a3b8;">No hay registros.</td></tr>';
      return;
    }

    tbody.innerHTML = rows.slice(0, 120).map(function(r){
      var cat = String(r.category || '');
      var chip = (cat === 'venta')
        ? '<span class="chip blue">venta</span>'
        : '<span class="chip mint">compra</span>';

      var name = (r.item_name || r.item_raw || '-');
      return ''
        + '<tr>'
        + '<td>' + chip + '</td>'
        + '<td>' + esc(fmtDate(r.document_datetime)) + '</td>'
        + '<td style="font-weight:900;">' + esc(name).slice(0, 80) + '</td>'
        + '<td>' + esc(r.supplier_name || '-').slice(0, 28) + '</td>'
        + '<td>' + money(r.unit_price) + '</td>'
        + '<td>' + Number(r.qty || 0).toLocaleString('es-MX') + '</td>'
        + '<td style="font-weight:900;">' + money(r.line_total) + '</td>'
        + '</tr>';
    }).join('');
  }

  function renderSuppliers(mode){
    var wrap = document.getElementById('suppliersWrap');
    if(!wrap) return;

    function card(title, chipClass, chipText, arr){
      var html = ''
        + '<div class="statCard" style="padding:14px 16px; flex:1; min-width:260px;">'
        + '<div style="display:flex; justify-content:space-between; align-items:center;">'
        + '<div style="font-weight:900; color:var(--ink);">' + title + '</div>'
        + '<span class="chip ' + chipClass + '">' + chipText + '</span>'
        + '</div>'
        + '<div style="margin-top:10px; display:flex; flex-direction:column; gap:10px;">';

      if(!arr || !arr.length){
        html += '<div style="color:#94a3b8; font-weight:900; padding:6px 0;">Sin datos</div>';
      } else {
        arr.slice(0,5).forEach(function(s){
          html += ''
            + '<div style="display:flex; justify-content:space-between; gap:10px; align-items:center;">'
            + '<div style="font-weight:900; color:var(--ink); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' + esc(s.supplier_name || '-') + '</div>'
            + '<div style="font-weight:900; color:rgba(15,23,42,.75);">' + money(s.total_amount) + '</div>'
            + '</div>';
        });
      }

      html += '</div></div>';
      return html;
    }

    if(mode === 'compare'){
      wrap.innerHTML =
        card('Top Proveedores (Compras)', 'mint', 'compra', DATA.suppliers.compra || []) +
        card('Top Clientes (Ventas)',  'blue', 'venta',  DATA.suppliers.venta  || []);
      return;
    }

    var arr = (mode === 'venta') ? (DATA.suppliers.venta || []) : (DATA.suppliers.compra || []);
    wrap.innerHTML = card('Top Proveedores (' + (mode === 'venta' ? 'Ventas' : 'Compras') + ')', (mode === 'venta' ? 'blue' : 'mint'), mode, arr);
  }

  function setStatsMode(mode, forceRender){
    statsMode = mode;
    setActiveSub(mode);
    setKpi(mode);
    setInsight(mode);

    var statsVisible = !document.getElementById('tab-stats-content').classList.contains('d-none');
    if(statsVisible){
      renderOrUpdateCharts(mode, !!forceRender);
    }

    renderTable(mode);
    renderSuppliers(mode);
  }

  document.addEventListener('DOMContentLoaded', function(){
    setStatsMode('compare', false);
  });
</script>

</div>
@endsection