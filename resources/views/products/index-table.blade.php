@extends('layouts.app')
@section('title','Productos')
@section('header','Productos')

@push('styles')
<style>
:root{
  --btn-blue:#2563eb; --btn-blue-h:#1d4ed8; --btn-blue-soft:#e6efff;
  --btn-green:#059669; --btn-green-h:#047857; --btn-green-soft:#e6fff4;
  --btn-gray:#64748b; --btn-gray-h:#475569; --btn-gray-soft:#eef2f7;
  --btn-red:#ef4444; --btn-red-h:#dc2626; --btn-red-soft:#ffe9eb;

  --surface:#ffffff; --border:#e5e7eb; --muted:#6b7280;
}

.page{ max-width:1200px; margin:12px auto 24px; padding:0 14px }

.hero{
  display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
  background: radial-gradient(800px 120px at 10% 0%, rgba(59,130,246,.10), transparent 60%),
              radial-gradient(800px 120px at 100% 0%, rgba(14,165,233,.09), transparent 60%),
              var(--surface);
  border:1px solid var(--border); border-radius:18px; padding:12px 14px;
}
.hero h1{ margin:0; font-weight:800; letter-spacing:-.02em }
.subtle{ color:var(--muted) }
.actions-top{ display:flex; align-items:center; gap:8px; flex-wrap:wrap }
@media (max-width: 960px){ .actions-top{ display:none } }

/* === Pastel buttons */
.pbtn{ font-weight:800; border-radius:14px; padding:10px 14px; display:inline-flex; align-items:center; gap:8px; border:2px solid transparent; transition:.18s ease; text-decoration:none }
.pbtn svg{ pointer-events:none }
.pbtn-blue{ color:var(--btn-blue); background:var(--btn-blue-soft); border-color:#cfe0ff }
.pbtn-green{ color:var(--btn-green); background:var(--btn-green-soft); border-color:#cfeedd }
.pbtn-gray{ color:var(--btn-gray); background:var(--btn-gray-soft); border-color:#dbe1ea }
.pbtn-red{ color:var(--btn-red); background:var(--btn-red-soft); border-color:#ffd3d8 }

/* Icon buttons (acciones) */
.btn-icon{ width:36px; height:36px; display:inline-grid; place-items:center; padding:0; border-radius:12px; border:0; cursor:pointer; transition:.18s ease }
.btn-icon.blue{ background:var(--btn-blue); color:#fff }
.btn-icon.red { background:var(--btn-red); color:#fff }
.btn-icon.gray{ background:#334155; color:#fff }

/* Search */
.searchbar{
  display:flex; align-items:center; gap:8px; background:#fff; height:42px; border-radius:999px; padding:0 10px 0 12px;
  border:1px solid #cfe0ff; box-shadow: inset 0 1px 0 rgba(255,255,255,.9), 0 6px 14px rgba(29,78,216,.10);
  min-width:260px; max-width:min(70vw, 520px)
}
.sb-icon{ width:24px; display:grid; place-items:center; color:#94a3b8 }
.sb-input{ flex:1; border:0; outline:none; background:transparent }
.sb-clear{ border:0; background:transparent; color:#94a3b8; width:28px; height:28px; border-radius:50%; display:grid; place-items:center; cursor:pointer; visibility:hidden }
.sb-clear:hover{ background:#f1f5f9; color:#64748b }

/* Column picker (desktop) */
.colpick{ position:relative }
.colbtn{ font-weight:800; border-radius:14px; padding:10px 14px; display:inline-flex; align-items:center; gap:8px; color:var(--btn-gray); background:var(--btn-gray-soft); border:2px solid #dbe1ea; }
.coldrop{
  position:absolute; right:0; top:120%; background:#fff; border:1px solid var(--border); border-radius:12px; padding:10px; min-width:260px;
  box-shadow:0 12px 30px rgba(2,6,23,.12); display:none; z-index:40;
}
.coldrop label{ display:flex; align-items:center; gap:8px; padding:6px 6px; border-radius:8px; cursor:pointer }
.coldrop label:hover{ background:#f8fafc }
.coldrop .row{ display:grid; grid-template-columns:1fr 1fr; gap:6px }
.colpick.open .coldrop{ display:block }

/* Table */
.table-wrap{ margin-top:14px; background:var(--surface); border:1px solid var(--border); border-radius:16px; overflow:auto }
table{ width:100%; border-collapse:separate; border-spacing:0 }
thead th{
  background:#f7faff; color:#334155; text-align:left; font-weight:800;
  border-bottom:1px solid var(--border); padding:12px 12px; white-space:nowrap;
}
tbody td{ padding:10px 12px; border-bottom:1px solid var(--border); vertical-align:middle }
tbody tr:hover{ background:#f8fbff }
th.th-actions, td.t-actions{ position:sticky; right:0; background:var(--surface); z-index:3; border-left:1px solid var(--border) }

/* Badges */
.badge{ padding:.25rem .6rem; border-radius:999px; font-weight:800; font-size:.75rem; display:inline-block; color:#fff }
.b-on  { background:#16a34a }
.b-off { background:#991b1b }

.thumb{ width:58px; height:46px; object-fit:cover; border-radius:10px; background:#f1f5f9; border:1px solid var(--border) }
.col-hidden{ display:none !important }

/* Collapsible + chevron rotate (por defecto móvil) */
tr[data-collapsed="1"] td[data-col]:not([data-col="img"]):not([data-col="name"]):not([data-col="sku"]):not([data-col="price"]):not([data-col="active"]):not([data-col="brand"]):not([data-col="created"]){
  display:none;
}
.js-toggle svg{ transition: transform .18s ease }
tr[data-collapsed="0"] .js-toggle svg{ transform: rotate(180deg); }

.more-note{ margin-left:4px }

/* Mobile cards */
@media (max-width: 960px){
  .table-wrap{ border:0; background:transparent; overflow:visible }
  table, thead, tbody, th, td, tr { display:block }
  thead{ display:none }
  tbody tr{
    background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:12px; margin-bottom:12px;
  }
  tbody td{
    border:0; padding:6px 0;
    display:grid; grid-template-columns: 42% 1fr; column-gap:10px; align-items:center;
  }
  tbody td::before{ content: attr(data-label); font-weight:800; color:#334155; }
  td[data-empty="1"]{ display:none !important; }
  tr[data-collapsed="1"] td[data-col]:not([data-col="img"]):not([data-col="name"]):not([data-col="sku"]):not([data-col="price"]):not([data-col="active"]) { display:none }
  td.t-actions{ grid-template-columns: 1fr; }
  td.t-actions::before{ content:''; display:none }
}

/* ===== Bottom sheet (móvil) ===== */
.fab{
  position: fixed; right: 16px; bottom: 18px; z-index: 82;
  width:54px; height:54px; border-radius:999px; display:none; place-items:center;
  background: var(--btn-blue-soft); color:var(--btn-blue); border:2px solid #cfe0ff; box-shadow: 0 12px 28px rgba(29,78,216,.18);
}
@media (max-width:960px){ .fab{ display:grid } }

.bs-overlay{
  position:fixed; inset:0; background:rgba(15,23,42,.28); backdrop-filter: blur(6px);
  opacity:0; pointer-events:none; transition:.25s ease opacity; z-index:80;
}
.bs-sheet{
  position:fixed; left:0; right:0; bottom:0; background:#fff; border-radius:18px 18px 0 0;
  box-shadow:0 -18px 40px rgba(2,6,23,.18); padding:16px;
  z-index:81; transform: translateY(100%); transition: transform .32s cubic-bezier(.2,.9,.2,1);
  max-height: calc(100dvh - 8px);
  padding-bottom: max(16px, env(safe-area-inset-bottom));
  display:flex; flex-direction:column;
}
.bs-handle{ width:46px; height:5px; border-radius:999px; background:#e2e8f0; margin:0 auto 12px }
.bs-actions{ display:flex; flex-direction:column; gap:12px; overflow:auto; -webkit-overflow-scrolling:touch }

.bs-open .fab{ opacity:0; pointer-events:none; transform:translateY(8px); transition:.2s ease }
.bs-open .bs-overlay{ opacity:1; pointer-events:auto }
.bs-open .bs-sheet{ transform: translateY(0); }

/* Panel Columnas IN-SHEET */
.cols-panel{ border:1px solid var(--border); background:#f8fafc; border-radius:12px; padding:10px; display:none }
.cols-panel.open{ display:block }
.cols-grid{ display:grid; grid-template-columns:1fr 1fr; gap:8px }
.cols-actions{ display:flex; gap:8px; justify-content:flex-end; margin-top:10px }

/* ======= ESCRITORIO (>=961px) — scroll, sin colapsado y HOVER con sombra negra ======= */
@media (min-width: 961px){
  /* Scroll horizontal */
  .table-wrap{
    overflow-x: auto;
    overflow-y: hidden;
    display: block;
    border-radius: 16px;
    padding-bottom: 6px;
    -webkit-overflow-scrolling: touch;
    scrollbar-gutter: stable;
  }
  #prodTable{
    table-layout: auto;
    min-width: 1400px;
    width: max(100%, 1400px);
  }

  #prodTable thead th, #prodTable tbody td{ padding:12px 10px; }
  #prodTable thead th[data-col="img"]     { width: 82px;  }
  #prodTable thead th[data-col="name"]    { width: 26%;   }
  #prodTable thead th[data-col="sku"]     { width: 9%;    }
  #prodTable thead th[data-col="price"]   { width: 9%;    }
  #prodTable thead th[data-col="brand"]   { width: 11%;   }
  #prodTable thead th[data-col="active"]  { width: 7.5%;  text-align:left; }
  #prodTable thead th[data-col="created"] { width: 9.5%;  }

  /* Acciones fijo a la derecha */
  #prodTable thead .th-actions,
  #prodTable td.t-actions{
    position: sticky;
    right: 0;
    background: var(--surface);
    z-index: 4;
    box-shadow: -1px 0 0 var(--border) inset;
    width:148px; min-width:148px;
  }

  /* Mostrar SIEMPRE todo (sin colapsado) y sin flecha */
  #prodTable tr[data-collapsed] td[data-col]{ display: table-cell !important; }
  #prodTable tr[data-collapsed] td[data-col],
  #prodTable tr[data-collapsed] td[data-col] .subtle{ white-space: normal; }
  #prodTable .js-toggle, #prodTable .more-note{ display:none !important; }

  /* === Hover con sombra negra y sin borde (pastel buttons) === */
  .pbtn:hover,
  .pbtn-blue:hover,
  .pbtn-green:hover,
  .pbtn-gray:hover,
  .pbtn-red:hover{
    background:#fff;
    border-color: transparent !important;
    outline: none;
    box-shadow: 0 10px 22px rgba(0,0,0,.18); /* sombreado negro */
    transform: translateY(-1px);
  }

  /* === Hover con sombra negra en botones de ACCIONES === */
  .actions .btn-icon,
  td.t-actions .btn-icon{
    border: 0 !important;
    outline: none;
    box-shadow: none;
  }
  .actions .btn-icon:hover,
  td.t-actions .btn-icon:hover{
    border: 0 !important;
    outline: none !important;
    box-shadow: 0 8px 18px rgba(0,0,0,.22); /* sombreado negro */
    transform: translateY(-1px);
    filter: none; /* evitamos doble efecto con brightness */
  }

  /* Estética de la barra (opcional) */
  .table-wrap::-webkit-scrollbar{ height:10px }
  .table-wrap::-webkit-scrollbar-thumb{ background:#cfd8e3; border-radius:999px; border:2px solid #eef2f7 }
  .table-wrap::-webkit-scrollbar-track{ background:#f8fafc; border-radius:999px }
}
</style>
@endpush

@section('content')
<div class="page">

  <div class="hero">
    <div>
      <h1 class="h4">Productos</h1>
      <div class="subtle">Colapsables por defecto. Expande para ver todo.</div>
    </div>

    <div class="actions-top">
      <a href="{{ route('products.create') }}" class="pbtn pbtn-blue">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Nuevo
      </a>

      <form id="searchForm" class="searchbar" method="GET" action="{{ route('products.index') }}" onsubmit="return false;">
        <span class="sb-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        </span>
        <input id="liveSearch" class="sb-input" type="text" name="q" value="{{ $q ?? '' }}" placeholder="Buscar por nombre, SKU, marca, categoría, estado, etiquetas…" autocomplete="off">
        <button type="button" class="sb-clear" id="sbClear" aria-label="Limpiar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
      </form>

      <div class="colpick" id="colPicker">
        <button type="button" class="colbtn" id="colBtn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1f2937" stroke-width="2"><path d="M3 6h18M7 12h10M10 18h4"/></svg>
          Columnas
        </button>
        <div class="coldrop">
          <div class="row">
            @php
              $cols = [
                'img'=>'Imagen','name'=>'Nombre','sku'=>'SKU','ssku'=>'SKU Prov.','unit'=>'Unidad','weight'=>'Peso',
                'cost'=>'Costo','price'=>'Precio','market'=>'Mercado','bid'=>'Licitación','dim'=>'Dimensiones','color'=>'Color',
                'ppu'=>'Pzs/U','brand'=>'Marca','cat'=>'Categoría','mat'=>'Material','active'=>'Activo','tags'=>'Etiquetas','created'=>'Creado'
              ];
            @endphp
            @foreach($cols as $key=>$label)
              <label><input type="checkbox" class="js-colchk" data-col="{{ $key }}" checked> <span>{{ $label }}</span></label>
            @endforeach
          </div>
          <div style="display:flex; gap:6px; margin-top:8px">
            <button type="button" class="pbtn pbtn-gray" id="colClear">Ninguna</button>
            <button type="button" class="pbtn pbtn-green" id="colAll">Todas</button>
          </div>
        </div>
      </div>

      <a class="pbtn pbtn-gray" id="btnPdf" href="{{ route('products.export.pdf') }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 20h12M6 16h12M8 12h8M10 8h4M12 4v2"/></svg>
        PDF
      </a>
    </div>
  </div>

  <div class="table-wrap">
    <table id="prodTable">
      <thead>
        <tr>
          <th data-col="img">Imagen</th>
          <th data-col="name">Nombre</th>
          <th data-col="sku">SKU</th>
          <th data-col="price">Precio</th>
          <th data-col="brand">Marca</th>
          <th data-col="active">Activo</th>
          <th data-col="created">Creado</th>
          <th data-col="ssku">SKU Prov.</th>
          <th data-col="unit">Unidad</th>
          <th data-col="weight">Peso</th>
          <th data-col="cost">Costo</th>
          <th data-col="market">Mercado</th>
          <th data-col="bid">Licitación</th>
          <th data-col="dim">Dimensiones</th>
          <th data-col="color">Color</th>
          <th data-col="ppu">Pzs/U</th>
          <th data-col="cat">Categoría</th>
          <th data-col="mat">Material</th>
          <th data-col="tags">Etiquetas</th>
          <th class="th-actions">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach($products as $p)
          @php
            $state = $p->active ? 'activo' : 'inactivo';
            $bag = \Illuminate\Support\Str::of(trim("{$p->name} {$p->sku} {$p->brand} {$p->category} {$p->tags} {$state}"))->lower();
            $v = fn($x)=> (is_null($x) || $x==='') ? null : $x;
          @endphp
          <tr data-bag="{{ $bag }}" data-collapsed="1" class="js-row">
            <td data-col="img" data-label="Imagen" data-empty="{{ $v($p->image_path) ? 0:1 }}">
              @if($p->image_path)<img class="thumb" src="{{ asset('storage/'.$p->image_path) }}" alt="">@endif
            </td>
            <td data-col="name" data-label="Nombre" data-empty="{{ $v($p->name)?0:1 }}">
              <div style="font-weight:800">{{ $p->name }}</div>
              @if($p->description)
                <div class="subtle" style="font-size:.85rem" title="Descripción">{{ \Illuminate\Support\Str::limit($p->description, 60) }}</div>
              @endif
            </td>
            <td data-col="sku" data-label="SKU" data-empty="{{ $v($p->sku)?0:1 }}">{{ $p->sku }}</td>
            <td data-col="price" data-label="Precio" data-empty="{{ $v($p->price)?0:1 }}">${{ $p->price }}</td>
            <td data-col="brand" data-label="Marca" data-empty="{{ $v($p->brand)?0:1 }}">{{ $p->brand }}</td>
            <td data-col="active" data-label="Activo" data-empty="0">
              <span class="badge {{ $p->active ? 'b-on':'b-off' }}">{{ $p->active ? 'Sí':'No' }}</span>
            </td>
            <td data-col="created" data-label="Creado" data-empty="{{ $v(optional($p->created_at)->format('Y-m-d'))?0:1 }}">{{ optional($p->created_at)->format('Y-m-d') }}</td>

            <td data-col="ssku" data-label="SKU Prov." data-empty="{{ $v($p->supplier_sku)?0:1 }}">{{ $p->supplier_sku }}</td>
            <td data-col="unit" data-label="Unidad" data-empty="{{ $v($p->unit)?0:1 }}">{{ $p->unit }}</td>
            <td data-col="weight" data-label="Peso" data-empty="{{ $v($p->weight)?0:1 }}">{{ $p->weight }}</td>
            <td data-col="cost" data-label="Costo" data-empty="{{ $v($p->cost)?0:1 }}">${{ $p->cost }}</td>
            <td data-col="market" data-label="Mercado" data-empty="{{ $v($p->market_price)?0:1 }}">${{ $p->market_price }}</td>
            <td data-col="bid" data-label="Licitación" data-empty="{{ $v($p->bid_price)?0:1 }}">${{ $p->bid_price }}</td>
            <td data-col="dim" data-label="Dimensiones" data-empty="{{ $v($p->dimensions)?0:1 }}">{{ $p->dimensions }}</td>
            <td data-col="color" data-label="Color" data-empty="{{ $v($p->color)?0:1 }}">{{ $p->color }}</td>
            <td data-col="ppu" data-label="Pzs/U" data-empty="{{ $v($p->pieces_per_unit)?0:1 }}">{{ $p->pieces_per_unit }}</td>
            <td data-col="cat" data-label="Categoría" data-empty="{{ $v($p->category)?0:1 }}">{{ $p->category }}</td>
            <td data-col="mat" data-label="Material" data-empty="{{ $v($p->material)?0:1 }}">{{ $p->material }}</td>
            <td data-col="tags" data-label="Etiquetas" data-empty="{{ $v($p->tags)?0:1 }}">{{ $p->tags }}</td>

            <td class="t-actions" data-label="Acciones" data-empty="0">
              <div class="actions" style="display:flex;gap:8px;align-items:center">
                <a class="btn-icon blue" title="Editar" href="{{ route('products.edit',$p) }}">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                </a>
                <form method="POST" action="{{ route('products.destroy',$p) }}" class="d-inline js-del">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn-icon red" title="Eliminar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                  </button>
                </form>
                <button type="button" class="btn-icon gray js-toggle" title="Mostrar más">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <span class="more-note subtle" style="font-size:.8rem">más</span>
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div style="margin-top:14px">{{ $products->links() }}</div>
</div>

{{-- FAB redondo (solo icono) --}}
<button class="fab" id="fabOpen" aria-label="Acciones">
  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M12 5v14M5 12h14"/>
  </svg>
</button>

{{-- Bottom sheet móvil --}}
<div class="bs-overlay" id="bsOverlay"></div>
<div class="bs-sheet" id="bsSheet" aria-hidden="true">
  <div class="bs-handle" id="bsHandle"></div>

  <div class="bs-actions">
    <a href="{{ route('products.create') }}" class="pbtn pbtn-blue" id="bsNuevo">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
      Nuevo
    </a>

    <button type="button" class="pbtn pbtn-green" id="toggleCols">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M7 12h10M10 18h4"/></svg>
      Columnas
    </button>

    <div class="cols-panel" id="colsPanel">
      <div class="cols-grid" id="colsGrid">
        @foreach($cols as $key=>$label)
          <label style="display:flex;align-items:center;gap:8px">
            <input type="checkbox" class="mchk" data-col="{{ $key }}" checked>
            <span>{{ $label }}</span>
          </label>
        @endforeach
      </div>
      <div class="cols-actions">
        <button type="button" class="pbtn pbtn-gray" id="mNone">Ninguna</button>
        <button type="button" class="pbtn pbtn-green" id="mAll">Todas</button>
        <button type="button" class="pbtn pbtn-blue" id="mApply">Aplicar</button>
      </div>
    </div>

    <a class="pbtn pbtn-gray" id="btnPdfMobile" href="{{ route('products.export.pdf') }}">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 20h12M6 16h12M8 12h8M10 8h4M12 4v2"/></svg>
      PDF
    </a>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function(){
  // ===== Search live
  const input = document.getElementById('liveSearch');
  const clearBtn = document.getElementById('sbClear');
  const rows = Array.from(document.querySelectorAll('#prodTable tbody tr'));
  const norm = s => (s||'').toString().normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
  function filter(){
    const q = norm(input?.value);
    clearBtn.style.visibility = q ? 'visible':'hidden';
    rows.forEach(r=>{
      const bag = norm(r.dataset.bag || '');
      r.style.display = !q || bag.includes(q) ? '' : 'none';
    });
  }
  input?.addEventListener('input', filter);
  clearBtn?.addEventListener('click', ()=>{ input.value=''; filter(); input.focus(); });
  filter();

  // ===== Column visibility (desktop)
  const picker = document.getElementById('colPicker');
  const btn = document.getElementById('colBtn');
  const checks = Array.from(document.querySelectorAll('.js-colchk'));
  const ths = Array.from(document.querySelectorAll('#prodTable thead th[data-col]'));
  const tds = Array.from(document.querySelectorAll('#prodTable tbody td[data-col]'));
  function setColVisibility(col, visible){
    ths.filter(th => th.dataset.col === col).forEach(th => th.classList.toggle('col-hidden', !visible));
    tds.filter(td => td.dataset.col === col).forEach(td => td.classList.toggle('col-hidden', !visible));
  }
  checks.forEach(chk=>{
    setColVisibility(chk.dataset.col, chk.checked);
    chk.addEventListener('change', ()=> setColVisibility(chk.dataset.col, chk.checked));
  });
  btn?.addEventListener('click', ()=> picker.classList.toggle('open'));
  document.addEventListener('click', (e)=>{ if(!picker.contains(e.target)) picker.classList.remove('open'); });
  document.getElementById('colClear')?.addEventListener('click', ()=>{ checks.forEach(c=>{ c.checked=false; setColVisibility(c.dataset.col,false); });});
  document.getElementById('colAll')?.addEventListener('click', ()=>{ checks.forEach(c=>{ c.checked=true; setColVisibility(c.dataset.col,true); });});

  // ===== Collapsable rows + chevron rotate (para móvil; en desktop lo anulamos por CSS)
  function toggleRow(row){ row.setAttribute('data-collapsed', row.getAttribute('data-collapsed') === '1' ? '0' : '1'); }
  document.querySelectorAll('.js-toggle').forEach(b=>{
    b.addEventListener('click', (e)=>{ e.stopPropagation(); toggleRow(b.closest('.js-row')); });
  });
  document.querySelectorAll('.js-row').forEach(r=>{
    r.addEventListener('click', (e)=>{
      if (e.target.closest('a,button,form,input,select,textarea')) return;
      toggleRow(r);
    });
  });

  // ===== SweetAlert eliminar
  document.querySelectorAll('form.js-del').forEach(f=>{
    f.addEventListener('submit', function(e){
      e.preventDefault();
      Swal.fire({
        title:'¿Eliminar producto?',
        icon:'warning',
        showCancelButton:true,
        confirmButtonText:'Sí, eliminar',
        cancelButtonText:'Cancelar',
        customClass:{ popup:'swal-rounded', confirmButton:'swal-confirm', cancelButton:'swal-cancel' },
        buttonsStyling:false
      }).then(res=>{ if(res.isConfirmed) this.submit(); });
    });
  });

  // ===== PDF con columnas + filtro
  function getCurrentCols(){ return checks.filter(c=>c.checked).map(c=>c.dataset.col); }
  function goPdf(anchor){
    const q = input ? input.value.trim() : '';
    const cols = (getCurrentCols()).join(',');
    const url = new URL(anchor.href, window.location.origin);
    if(q.length) url.searchParams.set('q', q);
    if(cols.length) url.searchParams.set('cols', cols);
    window.location = url.toString();
  }
  document.getElementById('btnPdf')?.addEventListener('click', e => { e.preventDefault(); goPdf(e.currentTarget); });
  document.getElementById('btnPdfMobile')?.addEventListener('click', e => { e.preventDefault(); closeSheet(); goPdf(e.currentTarget); });

  // ===== Bottom sheet (móvil)
  const body   = document.body;
  const overlay= document.getElementById('bsOverlay');
  const sheet  = document.getElementById('bsSheet');
  const fab    = document.getElementById('fabOpen');
  const handle = document.getElementById('bsHandle');

  function isMobile(){ return window.matchMedia('(max-width:960px)').matches; }
  function openSheet(){ if(!isMobile()) return; body.classList.add('bs-open'); sheet.setAttribute('aria-hidden','false'); }
  function closeSheet(){ body.classList.remove('bs-open'); sheet.setAttribute('aria-hidden','true'); colsPanel.classList.remove('open'); }

  fab?.addEventListener('click', openSheet);
  overlay?.addEventListener('click', closeSheet);

  // — Swipe down para cerrar (móvil)
  let startY = 0, currentY = 0, dragging = false;
  const dragThreshold = 80;

  function onTouchStart(e){
    if(!isMobile()) return;
    dragging = true;
    startY = e.touches ? e.touches[0].clientY : e.clientY;
    sheet.style.transition = 'none';
  }
  function onTouchMove(e){
    if(!dragging) return;
    currentY = (e.touches ? e.touches[0].clientY : e.clientY) - startY;
    if(currentY > 0){ sheet.style.transform = `translateY(${currentY}px)`; }
  }
  function onTouchEnd(){
    if(!dragging) return;
    sheet.style.transition = '';
    if(currentY > dragThreshold){ closeSheet(); sheet.style.transform = ''; }
    else{ sheet.style.transform = ''; }
    dragging = false; startY = 0; currentY = 0;
  }

  handle.addEventListener('touchstart', onTouchStart, {passive:true});
  handle.addEventListener('touchmove',  onTouchMove,  {passive:true});
  handle.addEventListener('touchend',   onTouchEnd);
  sheet.addEventListener('touchstart',  (e)=>{ if(e.target===sheet) onTouchStart(e); }, {passive:true});
  sheet.addEventListener('touchmove',   (e)=>{ if(dragging) onTouchMove(e); }, {passive:true});
  sheet.addEventListener('touchend',    onTouchEnd);

  // ===== Columnas (móvil)
  const colsPanel = document.getElementById('colsPanel');
  const toggleColsBtn = document.getElementById('toggleCols');

  toggleColsBtn?.addEventListener('click', ()=>{
    colsPanel.classList.toggle('open');
    if(colsPanel.classList.contains('open')){
      colsPanel.scrollIntoView({behavior:'smooth', block:'nearest'});
    }
  });

  document.getElementById('mAll')?.addEventListener('click', ()=> colsPanel.querySelectorAll('.mchk').forEach(x=> x.checked = true));
  document.getElementById('mNone')?.addEventListener('click', ()=> colsPanel.querySelectorAll('.mchk').forEach(x=> x.checked = false));
  document.getElementById('mApply')?.addEventListener('click', ()=>{
    const mods = Array.from(colsPanel.querySelectorAll('.mchk'));
    mods.forEach(m=>{
      const desktopChk = checks.find(c=> c.dataset.col === m.dataset.col);
      if(desktopChk){ desktopChk.checked = m.checked; setColVisibility(desktopChk.dataset.col, desktopChk.checked); }
    });
    colsPanel.classList.remove('open');
  });

  // Cerrar al navegar desde acciones
  document.getElementById('bsNuevo')?.addEventListener('click', closeSheet);

})();
</script>

<style>
/* SweetAlert look */
.swal-rounded.swal2-popup{ border-radius:18px !important; padding:18px !important }
.swal-confirm{ border-radius:10px; padding:10px 14px; font-weight:800; background:var(--btn-red); color:#fff; }
.swal-confirm:hover{ background:var(--btn-red-h) }
.swal-cancel{ border-radius:10px; padding:10px 14px; font-weight:800; background:var(--btn-gray); color:#fff; }
.swal-cancel:hover{ background:var(--btn-gray-h) }
</style>
@endpush
