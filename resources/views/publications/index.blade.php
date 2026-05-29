@extends('layouts.app')

@section('title', 'Rem y Fac')
@section('content_class', 'content--flush')
@section('content')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

@php
  use Carbon\Carbon;
  Carbon::setLocale('es');

  $getMeta = function($p){
    $ext = strtolower($p->extension ?: 'file');
    if ($ext === 'pdf') return ['icon'=>'PDF', 'color'=>'#ef4444', 'bg'=>'#fef2f2'];
    if (in_array($ext, ['xls','xlsx','csv'], true)) return ['icon'=>'XLS', 'color'=>'#10b981', 'bg'=>'#ecfdf5'];
    if (in_array($ext, ['doc','docx'], true)) return ['icon'=>'DOC', 'color'=>'#3b82f6', 'bg'=>'#eff6ff'];
    if (in_array($ext, ['jpg','jpeg','png'], true)) return ['icon'=>'IMG', 'color'=>'#f59e0b', 'bg'=>'#fffbeb'];
    return ['icon'=>'FILE','color'=>'#64748b', 'bg'=>'#f8fafc'];
  };

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

  $supCompraRows = ($topSuppliersCompra ?? collect())->map(fn($s) => ['supplier_name'=>$s->supplier_name,'total_amount'=>(float)$s->total_amount])->values()->all();
  $supVentaRows  = ($topSuppliersVenta ?? collect())->map(fn($s) => ['supplier_name'=>$s->supplier_name,'total_amount'=>(float)$s->total_amount])->values()->all();

  // Datos para estadísticas (se relee en cada AJAX)
  $pubsData = [
    'totalSpentCompra'    => $totalSpentCompra ?? 0,
    'totalSpentVenta'     => $totalSpentVenta ?? 0,
    'chartLabels'         => $chartLabels ?? [],
    'monthlyCompra'       => $monthlyCompra ?? [],
    'monthlyVenta'        => $monthlyVenta ?? [],
    'dailyLabels'         => $dailyLabels ?? [],
    'dailyCompra'         => $dailyCompra ?? [],
    'dailyVenta'          => $dailyVenta ?? [],
    'prodChartDataCompra' => $prodChartDataCompra ?? [],
    'prodChartDataVenta'  => $prodChartDataVenta ?? [],
    'tableRows'           => $tableRows,
    'supCompraRows'       => $supCompraRows,
    'supVentaRows'        => $supVentaRows,
  ];
@endphp

<div class="container-fluid py-5" id="pubsBase">
  <style>
    #pubsBase{ --ink:#0f172a; --muted:rgba(15,23,42,.62); --line:rgba(15,23,42,.10); --shadow2: 0 10px 30px rgba(2,6,23,.07); }
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

    #pubsBase .subNav { display: inline-flex; gap: 4px; flex-wrap: wrap; background: rgba(241, 245, 249, 0.6); border: 1px solid rgba(255, 255, 255, 0.8); padding: 5px; border-radius: 999px; box-shadow: inset 0 2px 4px rgba(15, 23, 42, 0.02); margin: 0 0 18px 0; width: fit-content; }
    #pubsBase .subBtn { border: none; background: transparent; color: rgba(15, 23, 42, 0.55); font-weight: 600; font-size: 13px; padding: 8px 18px; border-radius: 999px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
    #pubsBase .subBtn:hover { color: rgba(15, 23, 42, 0.9); }
    #pubsBase .subBtn.active { background: #ffffff; color: #0f172a; font-weight: 700; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08); }

    #pubsBase .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; box-shadow: inset 0 0 0 1px rgba(0,0,0,0.1); }
    #pubsBase .dot.all { background: #cbd5e1; }
    #pubsBase .dot.mint { background: #10b981; }
    #pubsBase .dot.blue { background: #3b82f6; }

    .tb-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 12px; margin-bottom: 24px; }
    .dd-wrap { position: relative; }
    .tb-btn { display: inline-flex; align-items: center; gap: 8px; height: 38px; padding: 0 16px; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px; color: #6b7280; font-size: 14px; font-weight: 500; cursor: pointer; transition: 0.2s; text-decoration: none; }
    .tb-btn:hover { background: #f9fafb; color: #374151; }
    .dd-wrap.active .tb-btn { border-color: #9ca3af; background: #f3f4f6; }
    .tb-btn svg { width: 18px; height: 18px; stroke-width: 2; }
    .tb-btn.icon-only { padding: 0 10px; }
    .tb-btn-pdf { color: #e11d48; border-color: #fecdd3; background: #fff1f2; }
    .tb-btn-pdf:hover { background: #ffe4e6; color: #be123c; border-color: #fda4af; }
    .tb-btn .spin { width:16px; height:16px; border:2px solid rgba(59,130,246,.3); border-top-color:#3b82f6; border-radius:50%; animation:tbspin .7s linear infinite; display:none; }
    .tb-toolbar.is-loading .dd-btn .spin { display:inline-block; }
    @keyframes tbspin { to { transform: rotate(360deg); } }

    .dd-menu { position: absolute; top: calc(100% + 8px); right: 0; background: #ffffff; border-radius: 12px; box-shadow: 0 10px 40px rgba(2,6,23,0.12), 0 0 0 1px rgba(15,23,42,0.06); width: 340px; z-index: 100; opacity: 0; visibility: hidden; transform: translateY(-8px); transition: all .2s cubic-bezier(.22,1,.36,1); padding: 18px; }
    .dd-wrap.active .dd-menu { opacity: 1; visibility: visible; transform: none; }
    .dd-section-title { font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px; margin-top: 18px; }
    .dd-section-title:first-child { margin-top: 0; }
    .dd-search-box { position: relative; margin-bottom: 8px; }
    .dd-search-box svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; width: 16px; height: 16px; stroke-width: 2; }
    .dd-search { width: 100%; height: 40px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px 0 36px; font-size: 14px; outline: none; transition: .2s; color:#374151; }
    .dd-search::placeholder { color: #9ca3af; }
    .dd-search:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
    .dd-list { max-height: 180px; overflow-y: auto; padding-right: 4px; display: flex; flex-direction: column; gap: 2px; }
    .dd-list::-webkit-scrollbar { width: 4px; }
    .dd-list::-webkit-scrollbar-track { background: transparent; }
    .dd-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .dd-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 8px; cursor: pointer; border-radius: 6px; transition: .15s; border-bottom: 1px solid #f3f4f6; }
    .dd-item:last-child { border-bottom: none; }
    .dd-item:hover { background: #f9fafb; }
    .dd-item span { font-size: 14px; color: #374151; font-weight: 400; }
    .dd-item input[type="checkbox"] { width: 18px; height: 18px; accent-color: #2563eb; cursor: pointer; border: 1px solid #d1d5db; border-radius: 4px; margin: 0; }
    .flex-dates { display: flex; gap: 10px; align-items: center; }
    .flex-dates input[type="date"] { width: 100%; height: 40px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 14px; outline: none; background: #fff; cursor: pointer; color: #374151; }
    .flex-dates input[type="date"]:focus { border-color: #3b82f6; }

    #pubsBase.is-loading #docArea { opacity: .45; transition: opacity .15s; pointer-events: none; }

    #pubsBase .docFilterBar{ display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin: 0 0 18px 0; }
    #pubsBase .docFilterTitle{ color:var(--muted); font-size:12px; font-weight:900; letter-spacing:.08em; text-transform:uppercase; }
    #pubsBase .docEmptyState{ padding:40px; text-align:center; color:var(--muted); font-weight:900; background: rgba(255,255,255,.65); border:1px dashed rgba(15,23,42,.14); border-radius:18px; }
    #pubsBase .chip{ font-size:10px; font-weight:900; padding:2px 8px; border-radius:999px; border:1px solid rgba(15,23,42,.08); background: rgba(255,255,255,.7); color: rgba(15,23,42,.65); }
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
    #pubsBase .mutedBox{ background: rgba(248,250,252,.65); border:1px dashed rgba(15,23,42,.14); border-radius: 16px; padding: 14px 16px; color: rgba(15,23,42,.65); font-weight: 900; font-size: 12px; margin-top:14px; }

    #pubsBase .idxPager { margin-top: 30px; display: flex; justify-content: center; }
    #pubsBase .idxPager [role="navigation"] { display: flex; flex-direction: column; align-items: center; gap: 12px; width: 100%; }
    #pubsBase .idxPager p.text-sm { color: var(--muted); font-size: 12px; font-weight: 600; letter-spacing: 0.5px; margin: 0; }
    #pubsBase .idxPager p.text-sm span { font-weight: 900; color: var(--ink); }
    #pubsBase .idxPager [role="navigation"] > div:first-of-type { display: none; }
    #pubsBase .idxPager .relative.z-0.inline-flex { display: inline-flex; align-items: center; gap: 4px; background: transparent; box-shadow: none; border: none; }
    #pubsBase .idxPager .relative.z-0.inline-flex > * > span,
    #pubsBase .idxPager .relative.z-0.inline-flex > a { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; padding: 0; font-size: 13px; font-weight: 700; color: var(--muted); background: transparent; border: none; border-radius: 50%; text-decoration: none; transition: all 0.2s ease; }
    #pubsBase .idxPager .relative.z-0.inline-flex > a:hover { background: rgba(15,23,42, 0.04); color: var(--ink); transform: translateY(-1px); }
    #pubsBase .idxPager .relative.z-0.inline-flex [aria-current="page"] > span { background: var(--ink); color: #ffffff; box-shadow: 0 4px 12px rgba(15,23,42,0.2); }
    #pubsBase .idxPager .relative.z-0.inline-flex [aria-disabled="true"] > span { color: rgba(15,23,42, 0.15); background: transparent; cursor: not-allowed; }
    #pubsBase .idxPager svg { width: 16px; height: 16px; stroke-width: 2.5; }
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

    {{-- ===== BARRA DE FILTROS (AJAX, sin recargar) ===== --}}
    <form method="GET" action="{{ route('publications.index') }}" id="filterForm" class="tb-toolbar" onsubmit="return false;">
      <div class="dd-wrap">
        <button type="button" class="tb-btn dd-btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
          Filtros
          <span class="spin"></span>
        </button>
        <div class="dd-menu">
          <div class="dd-section-title">RANGO DE FECHAS</div>
          <div class="flex-dates">
            <input type="date" name="from" value="{{ is_array($filters['from'] ?? null) ? '' : ($filters['from'] ?? '') }}">
            <input type="date" name="to" value="{{ is_array($filters['to'] ?? null) ? '' : ($filters['to'] ?? '') }}">
          </div>

          <div class="dd-section-title">TIPO DE DOCUMENTO</div>
          <div class="dd-list" style="max-height: none;">
            <label class="dd-item">
              <span>Compras</span>
              <input type="checkbox" name="cat[]" value="compra" {{ in_array('compra', (array)($filters['cat'] ?? [])) ? 'checked' : '' }}>
            </label>
            <label class="dd-item">
              <span>Ventas</span>
              <input type="checkbox" name="cat[]" value="venta" {{ in_array('venta', (array)($filters['cat'] ?? [])) ? 'checked' : '' }}>
            </label>
          </div>

          <div class="dd-section-title">PROVEEDOR / ASIGNADO</div>
          <div class="dd-search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" class="dd-search" placeholder="Buscar proveedor..." onkeyup="filterDDList(this)">
          </div>
          <div class="dd-list" style="max-height: 140px;">
            @foreach(($supplierOptions ?? []) as $s)
              <label class="dd-item">
                <span>{{ $s }}</span>
                <input type="checkbox" name="supplier[]" value="{{ $s }}" {{ in_array($s, (array)($filters['supplier'] ?? [])) ? 'checked' : '' }}>
              </label>
            @endforeach
          </div>

          <div class="dd-section-title">PRODUCTO / CONCEPTO</div>
          <div class="dd-search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" name="product" class="dd-search" placeholder="Buscar producto..." value="{{ is_array($filters['product'] ?? null) ? '' : ($filters['product'] ?? '') }}">
          </div>
        </div>
      </div>

      <button type="button" class="tb-btn icon-only" title="Limpiar todos los filtros" onclick="clearFilters()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M13 3H2l8 9.46V19l4 2v-8.54l.25-.3" /><line x1="17" y1="8" x2="22" y2="3" /><line x1="22" y1="8" x2="17" y2="3" /></svg>
      </button>

      <a id="pdfLink" data-base="{{ route('publications.report.pdf') }}" href="{{ route('publications.report.pdf', request()->query()) }}" class="tb-btn tb-btn-pdf" target="_blank" rel="noopener">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/></svg>
        PDF
      </a>
    </form>

    @if(session('ok'))
      <div class="alert alert-success" style="border-radius:12px; border:none; background:rgba(16,185,129,0.1); color:#065f46; margin-bottom: 20px;">
        {{ session('ok') }}
      </div>
    @endif

    <div class="tabNav">
      <button type="button" class="tabBtn active" onclick="switchTab('pubs')" id="btn-pubs">Mis Documentos</button>
      <button type="button" class="tabBtn" onclick="switchTab('stats')" id="btn-stats">Estadísticas</button>
    </div>

    {{-- TAB 1 --}}
    <div id="tab-pubs-content" class="idxWrap">
      <div class="docFilterBar">
        <div class="docFilterTitle">Filtrar documentos</div>
        <div class="subNav" style="margin:0;">
          <button type="button" class="subBtn active" id="doc-filter-all" onclick="setDocFilter('all')"><span class="dot all"></span> Todos</button>
          <button type="button" class="subBtn" id="doc-filter-compra" onclick="setDocFilter('compra')"><span class="dot mint"></span> Compras</button>
          <button type="button" class="subBtn" id="doc-filter-venta" onclick="setDocFilter('venta')"><span class="dot blue"></span> Ventas</button>
        </div>
      </div>

      {{-- ===== ÁREA QUE SE REEMPLAZA POR AJAX ===== --}}
      <div id="docArea">
        @php $pinnedCount = ($pinned ?? collect())->count(); @endphp

        @if($pinnedCount)
          <h6 id="pinnedTitle" style="font-size:12px; font-weight:900; color:var(--muted); margin-bottom:12px; letter-spacing:1px; text-transform:uppercase;">Fijados</h6>
          <div class="grid" id="pinnedGrid" style="margin-bottom: 24px;">
            @foreach($pinned as $p)
              @php $meta = $getMeta($p); @endphp
              <a href="{{ route('publications.show', $p) }}" class="fileCard docFilterItem" data-doc-type="{{ strtolower($p->category ?? $p->type ?? $p->document_type ?? '') }}">
                <div class="pin-tag">FIJADO</div>
                <div class="fc-top"><span class="fc-badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['color'] }}">{{ $meta['icon'] }}</span></div>
                <div class="fc-body">
                  @if($p->is_image ?? false)<img src="{{ $p->url }}" class="fc-img-preview" alt="preview">
                  @else<div class="fc-icon-box" style="background:{{ $meta['color'] }}">{{ $meta['icon'] }}</div>@endif
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

        <h6 id="latestTitle" style="font-size:12px; font-weight:900; color:var(--muted); margin-bottom:12px; letter-spacing:1px; text-transform:uppercase;">Recientes</h6>

        <div class="grid" id="latestGrid">
          @forelse($latest as $p)
            @php $meta = $getMeta($p); @endphp
            <a href="{{ route('publications.show', $p) }}" class="fileCard docFilterItem" data-doc-type="{{ strtolower($p->category ?? $p->type ?? $p->document_type ?? '') }}">
              <div class="fc-top"><span class="fc-badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['color'] }}">{{ $meta['icon'] }}</span></div>
              <div class="fc-body">
                @if($p->is_image ?? false)<img src="{{ $p->url }}" class="fc-img-preview" style="height:140px; width:92%; border-radius:12px;" alt="preview">
                @else<div class="fc-icon-box" style="background:{{ $meta['color'] }}; width:65px; height:65px;">{{ $meta['icon'] }}</div>@endif
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
            <div style="grid-column: 1/-1; padding:40px; text-align:center; color:var(--muted); font-weight:900;">No hay documentos.</div>
          @endforelse
        </div>

        <div id="docFilterEmpty" class="docEmptyState d-none">No hay documentos para este filtro en esta página.</div>

        @if(method_exists($latest, 'firstItem') && $latest->total())
          <div class="idxPager">{{ $latest->onEachSide(1)->links() }}</div>
        @endif
      </div>
    </div>

    {{-- TAB 2 (ESTADÍSTICAS) --}}
    <div id="tab-stats-content" class="d-none">
      <div class="subNav">
        <button type="button" class="subBtn active" id="sub-compare" onclick="setStatsMode('compare')"><span class="dot all"></span> Comparativo</button>
        <button type="button" class="subBtn" id="sub-compra" onclick="setStatsMode('compra')"><span class="dot mint"></span> Compras</button>
        <button type="button" class="subBtn" id="sub-venta" onclick="setStatsMode('venta')"><span class="dot blue"></span> Ventas</button>
      </div>

      <div class="dashGrid">
        <div class="statCard">
          <h3 id="kpiTitle" style="font-size:12px; text-transform:uppercase; color:var(--muted); font-weight:900; letter-spacing:.08em;">Comparativo General</h3>
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

      <h3 id="tableTitle" style="font-size:16px; font-weight:900; color:var(--ink); margin: 8px 0 15px 0;">Desglose Reciente (Compras + Ventas)</h3>
      <div class="table-responsive">
        <table class="table-clean">
          <thead><tr><th>Tipo</th><th>Fecha</th><th>Concepto / Producto</th><th>Proveedor</th><th>Precio</th><th>Cant</th><th>Total</th></tr></thead>
          <tbody id="rowsTbody"></tbody>
        </table>
      </div>

      <h3 style="font-size:15px; font-weight:900; color:var(--ink); margin:30px 0 14px;">Top Proveedores</h3>
      <div id="suppliersWrap" style="display:flex; gap:15px; flex-wrap:wrap;"></div>
    </div>
  </div>

  {{-- ===== DATOS DE ESTADÍSTICAS (se relee en cada AJAX) ===== --}}
  <script type="application/json" id="pubsData">{!! json_encode($pubsData, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) !!}</script>

<script>
  /* ====== Utilidades ====== */
  function toNum(v){ if(v==null) return 0; if(typeof v==='number') return isFinite(v)?v:0; var s=String(v).trim().replace(/[^0-9.\-]/g,''); var n=Number(s); return isFinite(n)?n:0; }
  function money(n){ n=Number(n||0); return '$'+n.toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2}); }
  function esc(s){ s=String(s==null?'':s); return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }
  function fmtDate(d){ if(!d) return '-'; try{ var dt=new Date(String(d).replace(' ','T')); if(isNaN(dt.getTime())) return String(d); return dt.toLocaleDateString('es-MX'); }catch(e){ return String(d); } }

  function readPubsData(){ try{ return JSON.parse(document.getElementById('pubsData').textContent || '{}'); }catch(e){ return {}; } }
  function buildDATA(raw){
    raw = raw || {};
    var d = {
      totals:   { compra: raw.totalSpentCompra||0, venta: raw.totalSpentVenta||0, all:0 },
      monthly:  { labels: raw.chartLabels||[], compra: raw.monthlyCompra||[], venta: raw.monthlyVenta||[] },
      daily:    { labels: raw.dailyLabels||[], compra: raw.dailyCompra||[], venta: raw.dailyVenta||[] },
      products: { compra: raw.prodChartDataCompra||[], venta: raw.prodChartDataVenta||[] },
      table:    raw.tableRows||[],
      suppliers:{ compra: raw.supCompraRows||[], venta: raw.supVentaRows||[] }
    };
    d.totals.all = toNum(d.totals.compra) + toNum(d.totals.venta);
    return d;
  }
  var DATA = buildDATA(readPubsData());

  /* ====== Estado ====== */
  var docFilterMode = 'all';
  var statsMode = 'compare';
  var chartMonthly = null, chartDaily = null, chartProducts = null;
  var chartsInitialized = false;
  var pubsAbort = null;
  var filterDebounce = null;

  /* ====== Dropdown filtros (se enlaza UNA vez) ====== */
  document.addEventListener('click', function(e){
    var btn = e.target.closest('.dd-btn');
    if(btn){
      var wrap = btn.closest('.dd-wrap');
      var active = wrap.classList.contains('active');
      document.querySelectorAll('.dd-wrap').forEach(function(w){ w.classList.remove('active'); });
      if(!active) wrap.classList.add('active');
      return;
    }
    if(!e.target.closest('.dd-wrap')) document.querySelectorAll('.dd-wrap').forEach(function(w){ w.classList.remove('active'); });
  });

  function filterDDList(input){
    var term = input.value.toLowerCase();
    var items = input.closest('.dd-menu').querySelectorAll('.dd-item');
    items.forEach(function(item){ var t = item.querySelector('span').textContent.toLowerCase(); item.style.display = t.includes(term) ? 'flex' : 'none'; });
  }

  /* ====== AJAX: filtros en tiempo real ====== */
  function scheduleApply(immediate){
    clearTimeout(filterDebounce);
    if(immediate){ applyFilters(); return; }
    filterDebounce = setTimeout(applyFilters, 350);
  }

  function buildParams(){
    var form = document.getElementById('filterForm');
    return new URLSearchParams(new FormData(form)).toString();
  }

  function updatePdfLink(qs){
    var a = document.getElementById('pdfLink');
    if(a) a.href = a.dataset.base + (qs ? ('?' + qs) : '');
  }

  function destroyCharts(){
    [chartMonthly, chartDaily, chartProducts].forEach(function(c){ try{ if(c) c.destroy(); }catch(e){} });
    chartMonthly = chartDaily = chartProducts = null;
  }

  async function applyFilters(){
    var form = document.getElementById('filterForm');
    var qs = buildParams();
    var url = form.getAttribute('action') + (qs ? ('?' + qs) : '');

    updatePdfLink(qs);

    if(pubsAbort) pubsAbort.abort();
    pubsAbort = new AbortController();

    var base = document.getElementById('pubsBase');
    var toolbar = document.getElementById('filterForm');
    base.classList.add('is-loading');
    toolbar.classList.add('is-loading');

    try{
      var res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: pubsAbort.signal });
      var html = await res.text();
      var doc = new DOMParser().parseFromString(html, 'text/html');

      var freshDocs = doc.querySelector('#docArea');
      var curDocs = document.querySelector('#docArea');
      if(freshDocs && curDocs) curDocs.innerHTML = freshDocs.innerHTML;

      var freshData = doc.querySelector('#pubsData');
      if(freshData){ DATA = buildDATA(JSON.parse(freshData.textContent || '{}')); }

      destroyCharts();
      chartsInitialized = false;
      setDocFilter(docFilterMode);

      if(!document.getElementById('tab-stats-content').classList.contains('d-none')){
        ensureChartsVisibleInit();
      } else {
        setStatsMode(statsMode, false);
      }

      window.history.replaceState({}, '', url);
    }catch(e){
      if(e.name !== 'AbortError') console.error('Filtro AJAX falló', e);
    }finally{
      base.classList.remove('is-loading');
      toolbar.classList.remove('is-loading');
    }
  }

  function clearFilters(){
    var form = document.getElementById('filterForm');
    form.querySelectorAll('input').forEach(function(i){ if(i.type==='checkbox') i.checked=false; else i.value=''; });
    applyFilters();
  }

  /* ====== Filtro visual compra/venta (cliente) ====== */
  function normalizeDocType(v){ v=String(v||'').toLowerCase().trim(); if(v==='compras'||v==='purchase'||v==='purchases') return 'compra'; if(v==='ventas'||v==='sale'||v==='sales') return 'venta'; return v; }
  function setActiveDocFilter(mode){ ['all','compra','venta'].forEach(function(m){ var el=document.getElementById('doc-filter-'+m); if(!el) return; if(m===mode) el.classList.add('active'); else el.classList.remove('active'); }); }
  function setDocFilter(mode){
    docFilterMode = mode || 'all';
    setActiveDocFilter(docFilterMode);
    var items = document.querySelectorAll('#pubsBase .docFilterItem');
    var visibleTotal=0, visiblePinned=0, visibleLatest=0;
    items.forEach(function(item){
      var type = normalizeDocType(item.getAttribute('data-doc-type'));
      var show = (docFilterMode==='all') || (type===docFilterMode);
      if(show){ item.classList.remove('d-none'); visibleTotal++; if(item.closest('#pinnedGrid')) visiblePinned++; if(item.closest('#latestGrid')) visibleLatest++; }
      else item.classList.add('d-none');
    });
    var pinnedTitle=document.getElementById('pinnedTitle'), latestTitle=document.getElementById('latestTitle'), empty=document.getElementById('docFilterEmpty'), pager=document.querySelector('.idxPager');
    if(pinnedTitle) pinnedTitle.classList.toggle('d-none', visiblePinned===0 && docFilterMode!=='all');
    if(latestTitle) latestTitle.classList.toggle('d-none', visibleLatest===0 && docFilterMode!=='all');
    if(empty) empty.classList.toggle('d-none', visibleTotal!==0);
    if(pager) pager.style.display = (docFilterMode==='all') ? 'flex' : 'none';
  }

  /* ====== Estadísticas ====== */
  function seriesFrom(labels, raw){
    labels = Array.isArray(labels) ? labels : [];
    if(Array.isArray(raw) && (raw.length===0 || typeof raw[0]==='number' || typeof raw[0]==='string')) return labels.map(function(_,i){ return toNum(raw[i]); });
    if(raw && typeof raw==='object' && !Array.isArray(raw)) return labels.map(function(l){ return toNum(raw[l]); });
    if(Array.isArray(raw) && raw.length && typeof raw[0]==='object'){ var map={}; raw.forEach(function(r){ var k=r.month||r.month_id||r.label||r.x||r.day||r.date; var val=r.total||r.y||r.value||r.amount; if(k!=null) map[String(k)]=toNum(val); }); return labels.map(function(l){ return toNum(map[String(l)]); }); }
    return labels.map(function(){ return 0; });
  }

  function switchTab(tab){
    document.querySelectorAll('.tabBtn').forEach(function(b){ b.classList.remove('active'); });
    document.getElementById('tab-pubs-content').classList.add('d-none');
    document.getElementById('tab-stats-content').classList.add('d-none');
    document.getElementById('btn-'+tab).classList.add('active');
    document.getElementById('tab-'+tab+'-content').classList.remove('d-none');
    if(tab==='stats') ensureChartsVisibleInit();
  }

  function ensureChartsVisibleInit(){
    if(typeof ApexCharts==='undefined'){ console.error('ApexCharts no cargó'); return; }
    if(!chartsInitialized){ chartsInitialized=true; setStatsMode(statsMode, true); return; }
    setStatsMode(statsMode, false);
    setTimeout(function(){ try{ if(chartMonthly) chartMonthly.resize(); }catch(e){} try{ if(chartDaily) chartDaily.resize(); }catch(e){} try{ if(chartProducts) chartProducts.resize(); }catch(e){} }, 50);
  }

  function setActiveSub(mode){ ['compare','compra','venta'].forEach(function(m){ var el=document.getElementById('sub-'+m); if(!el) return; if(m===mode) el.classList.add('active'); else el.classList.remove('active'); }); }

  function setKpi(mode){
    var compra=toNum(DATA.totals.compra), venta=toNum(DATA.totals.venta), all=toNum(DATA.totals.all);
    document.getElementById('kpiCompra').textContent = money(compra);
    document.getElementById('kpiVenta').textContent  = money(venta);
    if(mode==='compra'){ document.getElementById('kpiTitle').textContent='Total Histórico (Compras)'; document.getElementById('kpiBig').textContent=money(compra); }
    else if(mode==='venta'){ document.getElementById('kpiTitle').textContent='Total Histórico (Ventas)'; document.getElementById('kpiBig').textContent=money(venta); }
    else { document.getElementById('kpiTitle').textContent='Comparativo General'; document.getElementById('kpiBig').textContent=money(all); }
  }

  function setInsight(mode){
    var compra=toNum(DATA.totals.compra), venta=toNum(DATA.totals.venta), gap=compra-venta, recovery=(compra>0)?(venta/compra)*100:0;
    var chipEl=document.getElementById('insChip'), l1=document.getElementById('insLine1'), l2=document.getElementById('insLine2');
    if(!chipEl||!l1||!l2) return;
    if(mode==='compra'){ chipEl.className='chip mint'; chipEl.textContent='compras'; l1.textContent='Compras acumuladas: '+money(compra)+'.'; l2.textContent='Meta de ventas para recuperar: '+money(compra)+'.'; return; }
    if(mode==='venta'){ chipEl.className='chip blue'; chipEl.textContent='ventas'; l1.textContent='Ventas acumuladas: '+money(venta)+'.'; l2.textContent='Equivalen al '+recovery.toFixed(1)+'% de compras.'; return; }
    if(gap>0.01){ chipEl.className='chip'; chipEl.textContent='gap'; l1.textContent='Te faltan '+money(gap)+' en ventas para igualar compras.'; l2.textContent='Recuperación: '+recovery.toFixed(1)+'%.'; }
    else if(gap<-0.01){ chipEl.className='chip blue'; chipEl.textContent='arriba'; l1.textContent='Vas arriba por '+money(Math.abs(gap))+'.'; l2.textContent='Relación: '+recovery.toFixed(1)+'%.'; }
    else { chipEl.className='chip mint'; chipEl.textContent='equilibrio'; l1.textContent='Balance equilibrado.'; l2.textContent='Mantén el ritmo.'; }
  }

  function baseY(){ return { yaxis:{ labels:{ formatter:function(v){ return "$"+Number(v||0).toLocaleString('es-MX'); }, style:{ colors:'#0f172a', fontWeight:900 } } }, tooltip:{ y:{ formatter:function(v){ return "$"+Number(v||0).toLocaleString('es-MX'); } } } }; }
  function barDataLabels(){ return { dataLabels:{ enabled:true, offsetY:-8, style:{ fontSize:'11px', fontWeight:900, colors:['#0f172a'] }, background:{enabled:false}, dropShadow:{enabled:false} } }; }
  function xaxisDates(labels){ return { xaxis:{ categories:labels, labels:{ rotate:-45, rotateAlways:true, hideOverlappingLabels:true, trim:true, style:{ colors:'#0f172a', fontWeight:900 } } } }; }

  function buildMonthlyOptions(mode){
    var labels=DATA.monthly.labels||[], compra=seriesFrom(labels,DATA.monthly.compra), venta=seriesFrom(labels,DATA.monthly.venta);
    var common=Object.assign({ chart:{type:'area',height:250,toolbar:{show:false},fontFamily:'inherit'}, fill:{type:'gradient',gradient:{shadeIntensity:1,opacityFrom:0.35,opacityTo:0.04,stops:[0,100]}}, stroke:{curve:'smooth',width:2} }, baseY(), xaxisDates(labels));
    if(mode==='compare') return Object.assign({ series:[{name:'Compras',data:compra},{name:'Ventas',data:venta}], colors:['#10b981','#3b82f6'] }, common);
    return Object.assign({ series:[{name:(mode==='compra'?'Compras':'Ventas'), data:(mode==='compra'?compra:venta)}], colors:[(mode==='compra'?'#10b981':'#3b82f6')] }, common);
  }
  function buildDailyOptions(mode){
    var labels=DATA.daily.labels||[], compra=seriesFrom(labels,DATA.daily.compra), venta=seriesFrom(labels,DATA.daily.venta);
    var common=Object.assign({ chart:{type:'bar',height:250,toolbar:{show:false},fontFamily:'inherit'}, plotOptions:{bar:{borderRadius:4,columnWidth:'55%',dataLabels:{position:'top'}}} }, baseY(), barDataLabels(), xaxisDates(labels));
    if(mode==='compare') return Object.assign({ series:[{name:'Compras',data:compra},{name:'Ventas',data:venta}], colors:['#10b981','#3b82f6'] }, common);
    return Object.assign({ series:[{name:(mode==='compra'?'Compras':'Ventas'), data:(mode==='compra'?compra:venta)}], colors:[(mode==='compra'?'#10b981':'#3b82f6')] }, common);
  }
  function buildProductsOptions(mode){
    if(mode==='compare') mode='compra';
    var data=(mode==='venta')?(DATA.products.venta||[]):(DATA.products.compra||[]);
    var color=(mode==='venta')?'#3b82f6':'#10b981';
    return { series:[{name:(mode==='venta')?'Total Vendido':'Total Comprado',data:data}], chart:{type:'bar',height:320,toolbar:{show:false},fontFamily:'inherit'}, colors:[color], plotOptions:{bar:{horizontal:true,borderRadius:4,barHeight:'70%'}}, xaxis:{labels:{formatter:function(v){return "$"+Number(v||0).toLocaleString('es-MX');},style:{colors:'#0f172a',fontWeight:900}}}, yaxis:{labels:{maxWidth:220,style:{fontSize:'11px',fontWeight:900,colors:'#0f172a'}}}, tooltip:{y:{formatter:function(v){return "$"+Number(v||0).toLocaleString('es-MX');}}}, dataLabels:{enabled:false} };
  }
  function anyNonZero(arr){ if(!Array.isArray(arr)) return false; for(var i=0;i<arr.length;i++){ if(toNum(arr[i])!==0) return true; } return false; }

  function renderOrUpdateCharts(mode, forceRender){
    var elM=document.querySelector("#chartMonthly"), elD=document.querySelector("#chartDaily"), elP=document.querySelector("#chartProducts");
    var labelsM=DATA.monthly.labels||[], labelsD=DATA.daily.labels||[];
    var mCompra=seriesFrom(labelsM,DATA.monthly.compra), mVenta=seriesFrom(labelsM,DATA.monthly.venta);
    var dCompra=seriesFrom(labelsD,DATA.daily.compra), dVenta=seriesFrom(labelsD,DATA.daily.venta);

    if(!labelsM.length || (!anyNonZero(mCompra) && !anyNonZero(mVenta))){ elM.innerHTML='<div style="text-align:center; padding:50px; color:#94a3b8; font-weight:900;">Sin datos</div>'; }
    else { var o1=buildMonthlyOptions(mode); if(chartMonthly) chartMonthly.updateOptions(o1,true,true); else if(forceRender){ chartMonthly=new ApexCharts(elM,o1); chartMonthly.render(); } }

    if(!labelsD.length || (!anyNonZero(dCompra) && !anyNonZero(dVenta))){ elD.innerHTML='<div style="text-align:center; padding:50px; color:#94a3b8; font-weight:900;">Sin datos</div>'; }
    else { var o2=buildDailyOptions(mode); if(chartDaily) chartDaily.updateOptions(o2,true,true); else if(forceRender){ chartDaily=new ApexCharts(elD,o2); chartDaily.render(); } }

    var prodArr=(mode==='venta')?(DATA.products.venta||[]):(DATA.products.compra||[]);
    if(!Array.isArray(prodArr)||!prodArr.length){ elP.innerHTML='<div style="text-align:center; padding:50px; color:#94a3b8; font-weight:900;">Sin datos</div>'; }
    else { var o3=buildProductsOptions(mode); if(chartProducts) chartProducts.updateOptions(o3,true,true); else if(forceRender){ chartProducts=new ApexCharts(elP,o3); chartProducts.render(); } }
  }

  function renderTable(mode){
    var tbody=document.getElementById('rowsTbody'), title=document.getElementById('tableTitle');
    if(!tbody) return;
    var rows=Array.isArray(DATA.table)?DATA.table.slice():[];
    if(mode==='compra'){ title.textContent='Desglose Reciente (Compras)'; rows=rows.filter(function(r){return String(r.category||'')==='compra';}); }
    else if(mode==='venta'){ title.textContent='Desglose Reciente (Ventas)'; rows=rows.filter(function(r){return String(r.category||'')==='venta';}); }
    else { title.textContent='Desglose Reciente (Compras + Ventas)'; }
    if(!rows.length){ tbody.innerHTML='<tr><td colspan="7" style="text-align:center; padding:20px; color:#94a3b8;">No hay registros.</td></tr>'; return; }
    tbody.innerHTML=rows.slice(0,120).map(function(r){
      var chip=(String(r.category||'')==='venta')?'<span class="chip blue">venta</span>':'<span class="chip mint">compra</span>';
      return '<tr><td>'+chip+'</td><td>'+esc(fmtDate(r.document_datetime))+'</td><td style="font-weight:900;">'+esc((r.item_name||r.item_raw||'-')).slice(0,80)+'</td><td>'+esc(r.supplier_name||'-').slice(0,28)+'</td><td>'+money(r.unit_price)+'</td><td>'+Number(r.qty||0).toLocaleString('es-MX')+'</td><td style="font-weight:900;">'+money(r.line_total)+'</td></tr>';
    }).join('');
  }

  function renderSuppliers(mode){
    var wrap=document.getElementById('suppliersWrap');
    if(!wrap) return;
    function card(title,chipClass,chipText,arr){
      var html='<div class="statCard" style="padding:14px 16px; flex:1; min-width:260px;"><div style="display:flex; justify-content:space-between; align-items:center;"><div style="font-weight:900; color:var(--ink);">'+title+'</div><span class="chip '+chipClass+'">'+chipText+'</span></div><div style="margin-top:10px; display:flex; flex-direction:column; gap:10px;">';
      if(!arr||!arr.length){ html+='<div style="color:#94a3b8; font-weight:900; padding:6px 0;">Sin datos</div>'; }
      else { arr.slice(0,5).forEach(function(s){ html+='<div style="display:flex; justify-content:space-between; gap:10px; align-items:center;"><div style="font-weight:900; color:var(--ink); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">'+esc(s.supplier_name||'-')+'</div><div style="font-weight:900; color:rgba(15,23,42,.75);">'+money(s.total_amount)+'</div></div>'; }); }
      return html+'</div></div>';
    }
    if(mode==='compare'){ wrap.innerHTML=card('Top Proveedores (Compras)','mint','compra',DATA.suppliers.compra||[])+card('Top Clientes (Ventas)','blue','venta',DATA.suppliers.venta||[]); return; }
    wrap.innerHTML=card('Top Proveedores ('+(mode==='venta'?'Ventas':'Compras')+')',(mode==='venta'?'blue':'mint'),mode,(mode==='venta'?DATA.suppliers.venta:DATA.suppliers.compra));
  }

  function setStatsMode(mode, forceRender){
    statsMode=mode; setActiveSub(mode); setKpi(mode); setInsight(mode);
    if(!document.getElementById('tab-stats-content').classList.contains('d-none')){ renderOrUpdateCharts(mode, !!forceRender); }
    renderTable(mode); renderSuppliers(mode);
  }

  /* ====== Init ====== */
  document.addEventListener('DOMContentLoaded', function(){
    setDocFilter('all');
    setStatsMode('compare', false);

    var form = document.getElementById('filterForm');
    form.addEventListener('change', function(e){
      if(!e.target.name) return;
      if(e.target.type === 'checkbox') scheduleApply(true);
      else scheduleApply(false);
    });
    form.addEventListener('input', function(e){
      if(e.target.name === 'product') scheduleApply(false);
    });
  });
</script>

</div>
@endsection