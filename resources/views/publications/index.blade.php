@extends('layouts.app')

@section('title', 'Rem y Fac')

@section('content')
{{-- Cargar ApexCharts --}}
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

@php
  $getMeta = function($p){
    $ext = strtolower($p->extension ?: 'file');
    return match($ext){
      'pdf'   => ['icon'=>'PDF', 'color'=>'#ef4444', 'bg'=>'#fef2f2'],
      'xls','xlsx','csv' => ['icon'=>'XLS', 'color'=>'#10b981', 'bg'=>'#ecfdf5'],
      'doc','docx'       => ['icon'=>'DOC', 'color'=>'#3b82f6', 'bg'=>'#eff6ff'],
      'jpg','jpeg','png' => ['icon'=>'IMG', 'color'=>'#f59e0b', 'bg'=>'#fffbeb'],
      default            => ['icon'=>'FILE','color'=>'#64748b', 'bg'=>'#f8fafc'],
    };
  };
@endphp

<div class="container-fluid py-5" id="pubsBase">
  <style>
    #pubsBase{
      --ink:#0f172a; --muted:rgba(15,23,42,.62); --line:rgba(15,23,42,.10);
      --bg-page: #f8fafc; --radius: 18px; --shadow2: 0 10px 30px rgba(2,6,23,.07);
    }
    /* Fondo Degradado Original */
    #pubsBase .bg{
      border-radius: 28px; padding: 30px; border: 1px solid rgba(15,23,42,.06);
      background: radial-gradient(1200px 520px at 50% -10%, rgba(56,189,248,.35), transparent 55%),
                  radial-gradient(900px 420px at 20% 0%, rgba(59,130,246,.18), transparent 55%),
                  radial-gradient(900px 420px at 85% 10%, rgba(16,185,129,.12), transparent 55%),
                  linear-gradient(180deg, rgba(255,255,255,.85), rgba(255,255,255,.55));
      box-shadow: 0 20px 80px rgba(2,6,23,.06); min-height: 85vh;
    }
    #pubsBase .hero{ display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; }
    #pubsBase .hero h1{ font-size:24px; font-weight:700; color:var(--ink); margin:0; }
    
    #pubsBase .tabNav { display: flex; gap: 5px; border-bottom: 2px solid rgba(15,23,42,.08); margin-bottom: 30px; }
    #pubsBase .tabBtn { background: transparent; border: none; font-size: 14px; font-weight: 600; color: var(--muted); padding: 12px 20px; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; }
    #pubsBase .tabBtn.active { color: #3b82f6; border-bottom-color: #3b82f6; }

    #pubsBase .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
    #pubsBase a.fileCard { text-decoration: none; display: flex; flex-direction: column; background: rgba(255,255,255,0.8); border: 1px solid var(--line); border-radius: 16px; overflow: hidden; transition: all 0.2s ease; position: relative; height: 100%; box-shadow: var(--shadow2); backdrop-filter: blur(10px); }
    #pubsBase a.fileCard:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(0,0,0,0.08); border-color: #cbd5e1; background: rgba(255,255,255,0.95); }
    #pubsBase .fc-top { padding: 12px; display:flex; justify-content:space-between; align-items:center; }
    #pubsBase .fc-badge { font-size: 10px; font-weight: 700; padding: 4px 8px; border-radius: 6px; letter-spacing: 0.5px; }
    #pubsBase .fc-body { flex: 1; display:flex; align-items:center; justify-content:center; padding: 10px 0; }
    #pubsBase .fc-icon-box { width: 60px; height: 60px; border-radius: 12px; display:flex; align-items:center; justify-content:center; font-size: 20px; font-weight: 700; color: white; box-shadow: 0 8px 15px -5px rgba(0,0,0,0.2); }
    #pubsBase .fc-img-preview { width: 100%; height: 140px; object-fit: cover; }
    #pubsBase .fc-foot { padding: 12px; background: rgba(248,250,252, 0.6); border-top: 1px solid var(--line); }
    #pubsBase .fc-title { font-size: 14px; font-weight: 600; color: var(--ink); margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display:block; }
    #pubsBase .fc-date { font-size: 11px; color: var(--muted); display:block; }
    #pubsBase .pin-tag { position: absolute; top: 10px; right: 10px; background: #fef3c7; color: #d97706; font-size: 10px; padding: 2px 6px; border-radius: 4px; font-weight: 600; z-index: 2; }
    
    .btn-upload { background: #3b82f6; color: white; padding: 10px 20px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 600; display:inline-flex; align-items:center; gap:8px; transition: all .2s; box-shadow: 0 4px 15px rgba(59,130,246, 0.3); }
    .btn-upload:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(59,130,246, 0.4); color:white; }

    .dashGrid { display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .statCard { background: rgba(255,255,255,0.95); border:1px solid var(--line); border-radius: 18px; padding: 24px; box-shadow: var(--shadow2); }
    
    .table-responsive { overflow-x: auto; background: rgba(255,255,255,0.95); border-radius: 18px; border: 1px solid var(--line); box-shadow: var(--shadow2); }
    .table-clean { width: 100%; border-collapse: collapse; min-width: 600px; }
    .table-clean th { text-align: left; padding: 15px 20px; background: #f8fafc; color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--line); }
    .table-clean td { padding: 14px 20px; border-bottom: 1px solid var(--line); color: var(--ink); font-size: 13px; }
    .table-clean tr:last-child td { border-bottom: none; }
    .table-clean tr:hover { background: #f1f5f9; }
    .d-none { display: none !important; }
  </style>

  <div class="bg">
    <div class="hero">
      <div>
        <h1>Gestor de Documentos</h1>
        <p>Visualiza tus documentos y analiza tus gastos.</p>
      </div>
      @auth
        <a class="btn-upload" href="{{ route('publications.create') }}">
           <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"></path></svg>
           Subir Nuevo
        </a>
      @endauth
    </div>

    @if(session('ok'))
      <div class="alert alert-success" style="border-radius:12px; border:none; background:rgba(16,185,129,0.1); color:#065f46;">{{ session('ok') }}</div>
    @endif

    <div class="tabNav">
        <button type="button" class="tabBtn active" onclick="switchTab('pubs')" id="btn-pubs">Mis Documentos</button>
        <button type="button" class="tabBtn" onclick="switchTab('stats')" id="btn-stats">Estadísticas</button>
    </div>

    {{-- PESTAÑA 1: DOCUMENTOS (GRID MOSAICO) --}}
    <div id="tab-pubs-content">
        @if($pinned->count())
            <h6 style="font-size:12px; font-weight:700; color:var(--muted); margin-bottom:15px; letter-spacing:1px; text-transform:uppercase;">Fijados</h6>
            <div class="grid" style="margin-bottom: 30px;">
                @foreach($pinned as $p)
                    @php $meta = $getMeta($p); @endphp
                    <a href="{{ route('publications.show', $p) }}" class="fileCard">
                        <div class="pin-tag">FIJADO</div>
                        <div class="fc-top">
                            <span class="fc-badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['color'] }}">{{ $meta['icon'] }}</span>
                        </div>
                        <div class="fc-body">
                            @if($p->is_image)
                                <img src="{{ $p->url }}" class="fc-img-preview" alt="preview">
                            @else
                                <div class="fc-icon-box" style="background:{{ $meta['color'] }}">{{ $meta['icon'] }}</div>
                            @endif
                        </div>
                        <div class="fc-foot">
                            <span class="fc-title">{{ $p->title }}</span>
                            <div style="display:flex; justify-content:space-between; margin-top:4px;">
                                 <span class="fc-date">{{ $p->created_at->format('d M, Y') }}</span>
                                 <span class="fc-date" style="font-weight:500;">{{ $p->nice_size }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        <h6 style="font-size:12px; font-weight:700; color:var(--muted); margin-bottom:15px; letter-spacing:1px; text-transform:uppercase;">Recientes</h6>
        <div class="grid">
            @forelse($latest as $p)
                @php $meta = $getMeta($p); @endphp
                <a href="{{ route('publications.show', $p) }}" class="fileCard">
                    <div class="fc-top">
                        <span class="fc-badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['color'] }}">{{ $meta['icon'] }}</span>
                    </div>
                    <div class="fc-body">
                        @if($p->is_image)
                            <img src="{{ $p->url }}" class="fc-img-preview" style="height:100px; width:90%; border-radius:10px;" alt="preview">
                        @else
                            <div class="fc-icon-box" style="background:{{ $meta['color'] }}; width:65px; height:65px;">{{ $meta['icon'] }}</div>
                        @endif
                    </div>
                    <div class="fc-foot">
                        <span class="fc-title" title="{{ $p->title }}">{{ $p->title }}</span>
                        <div style="display:flex; justify-content:space-between; margin-top:4px;">
                             <span class="fc-date">{{ $p->created_at->diffForHumans() }}</span>
                             <span class="fc-date" style="font-weight:500;">{{ $p->nice_size }}</span>
                        </div>
                    </div>
                </a>
            @empty
                <div style="grid-column: 1/-1; padding:40px; text-align:center; color:var(--muted);">No hay documentos subidos.</div>
            @endforelse
        </div>
        <div class="mt-5">{{ $latest->links() }}</div>
    </div>

    {{-- PESTAÑA 2: ESTADÍSTICAS --}}
    <div id="tab-stats-content" class="d-none">
        
        {{-- Fila 1: KPI y Mensual --}}
        <div class="dashGrid">
            <div class="statCard">
                <h3 style="font-size:12px; text-transform:uppercase; color:var(--muted); font-weight:700;">Gasto Histórico Total</h3>
                <div style="font-size:36px; font-weight:800; color:var(--ink); margin-top:5px;">${{ number_format($totalSpent ?? 0, 2) }}</div>
            </div>
            <div class="statCard" style="grid-column: span 2;">
                 <h3 style="font-size:15px; font-weight:700; color:var(--ink); margin-bottom:20px;">Tendencia Mensual</h3>
                 <div id="chartMonthly" style="width:100%; min-height:250px;"></div>
            </div>
        </div>

        {{-- Fila 2: Diario y Productos --}}
        <div class="dashGrid">
            <div class="statCard" style="grid-column: span 2;">
                 <h3 style="font-size:15px; font-weight:700; color:var(--ink); margin-bottom:20px;">Gasto Diario (Últimos 30 días)</h3>
                 <div id="chartDaily" style="width:100%; min-height:250px;"></div>
            </div>
            <div class="statCard">
                 <h3 style="font-size:15px; font-weight:700; color:var(--ink); margin-bottom:20px;">Top 10 Productos</h3>
                 <div id="chartProducts" style="width:100%; min-height:250px;"></div>
            </div>
        </div>

        {{-- Tabla General --}}
        <h3 style="font-size:16px; font-weight:700; color:var(--ink); margin-bottom:15px;">Desglose de Compras Recientes</h3>
        <div class="table-responsive">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Concepto / Producto</th>
                        <th>Proveedor</th>
                        <th>Precio</th>
                        <th>Cant</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($allPurchases))
                        @forelse($allPurchases as $item)
                        <tr>
                            <td>{{ $item->document_datetime ? \Carbon\Carbon::parse($item->document_datetime)->format('d/m/Y') : '-' }}</td>
                            <td style="font-weight:600;">{{ Str::limit($item->item_name ?: $item->item_raw, 40) }}</td>
                            <td>{{ Str::limit($item->supplier_name, 25) ?: '-' }}</td>
                            <td>${{ number_format($item->unit_price, 2) }}</td>
                            <td>{{ floatval($item->qty) }}</td>
                            <td style="font-weight:700;">${{ number_format($item->line_total, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" style="text-align:center; padding:20px;">No hay registros.</td></tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>

        {{-- Top Proveedores --}}
        @if(isset($topSuppliers) && count($topSuppliers) > 0)
            <h3 style="font-size:15px; font-weight:700; color:var(--ink); margin:30px 0 20px;">Top Proveedores</h3>
            <div style="display:flex; gap:15px; flex-wrap:wrap;">
                @foreach($topSuppliers as $sup)
                    <div class="statCard" style="padding:15px 20px; display:flex; align-items:center; gap:15px; flex:1; min-width:200px;">
                        <div style="width:40px; height:40px; background:#eff6ff; color:#3b82f6; border-radius:10px; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:16px;">
                            {{ substr($sup->supplier_name, 0, 1) }}
                        </div>
                        <div>
                            <div style="font-weight:700; font-size:14px; color:var(--ink);">{{ $sup->supplier_name }}</div>
                            <div style="font-size:13px; color:var(--muted); font-weight:600;">${{ number_format($sup->total_amount, 2) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
  </div>

  <script>
    function switchTab(tab) {
        document.querySelectorAll('.tabBtn').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-pubs-content').classList.add('d-none');
        document.getElementById('tab-stats-content').classList.add('d-none');
        document.getElementById('btn-' + tab).classList.add('active');
        document.getElementById('tab-' + tab + '-content').classList.remove('d-none');
        if(tab === 'stats') window.dispatchEvent(new Event('resize'));
    }

    document.addEventListener('DOMContentLoaded', function () {
        
        // 1. GRAFICA MENSUAL (AREA)
        @if(isset($chartValues) && count($chartValues) > 0)
            new ApexCharts(document.querySelector("#chartMonthly"), {
                series: [{ name: 'Gasto', data: @json($chartValues) }],
                chart: { type: 'area', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
                colors: ['#3b82f6'],
                fill: { type: 'gradient', gradient: { shadeIntensity:1, opacityFrom:0.5, opacityTo:0.05, stops:[0,100]} },
                stroke: { curve: 'smooth', width: 2 },
                xaxis: { categories: @json($chartLabels) },
                yaxis: { labels: { formatter: (val) => "$" + val.toLocaleString() } },
                tooltip: { y: { formatter: function (val) { return "$" + val.toLocaleString(); } } } // Tooltip activo
            }).render();
        @endif

        // 2. GRAFICA DIARIA (BARRAS)
        @if(isset($dailyValues) && count($dailyValues) > 0)
            new ApexCharts(document.querySelector("#chartDaily"), {
                series: [{ name: 'Gasto Día', data: @json($dailyValues) }],
                chart: { type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
                colors: ['#10b981'],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
                xaxis: { categories: @json($dailyLabels) },
                yaxis: { labels: { formatter: (val) => "$" + val.toLocaleString() } },
                tooltip: { y: { formatter: function (val) { return "$" + val.toLocaleString(); } } } // Tooltip activo
            }).render();
        @else
             document.querySelector("#chartDaily").innerHTML = '<div style="text-align:center; padding:50px; color:#94a3b8;">Sin datos</div>';
        @endif

        // 3. GRAFICA PRODUCTOS (HORIZONTAL)
        @if(isset($prodChartData) && count($prodChartData) > 0)
            new ApexCharts(document.querySelector("#chartProducts"), {
                series: [{ 
                    name: 'Total Gastado', 
                    data: @json($prodChartData) // Data mapeada {x: 'Nombre', y: 100}
                }],
                chart: { type: 'bar', height: 350, toolbar: { show: false }, fontFamily: 'inherit' },
                colors: ['#f59e0b'],
                plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '70%' } },
                xaxis: { labels: { formatter: (val) => "$" + val.toLocaleString() } },
                yaxis: { 
                    labels: { 
                        maxWidth: 200, 
                        style: { fontSize: '11px', fontWeight: 600 } 
                    } 
                },
                tooltip: { y: { formatter: function (val) { return "$" + val.toLocaleString(); } } } // Tooltip activo
            }).render();
        @else
             document.querySelector("#chartProducts").innerHTML = '<div style="text-align:center; padding:50px; color:#94a3b8;">Sin datos</div>';
        @endif
    });
  </script>
</div>
@endsection