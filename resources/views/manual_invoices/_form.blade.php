{{-- resources/views/manual_invoices/create.blade.php (o tu vista equivalente) --}}
@extends('layouts.app')

@section('title', isset($invoice) && $invoice->exists ? 'Editar Factura' : 'Nueva Factura')

@section('content')
@php
  use Illuminate\Support\Str;

  $isEdit = isset($invoice) && $invoice->exists;

  // items = old() -> modelo -> fila vacía
  $rows = old('items');

  if (!$rows && $isEdit && isset($invoice->items)) {
      $rows = $invoice->items->map(function($it){
          return [
              'id'          => $it->id,
              'product_id'  => $it->product_id,
              'description' => $it->description,
              'quantity'    => (float)$it->quantity,
              'unit_price'  => (float)$it->unit_price,
              'discount'    => (float)$it->discount,
              'tax_rate'    => (float)$it->tax_rate,
              'unit'        => $it->unit,
              'unit_code'   => $it->unit_code,
              'product_key' => $it->product_key,
          ];
      })->toArray();
  }

  if (!$rows || !count($rows)) {
      $rows = [[
          'id'          => null,
          'product_id'  => null,
          'description' => '',
          'quantity'    => 1,
          'unit_price'  => 0,
          'discount'    => 0,
          'tax_rate'    => 16,
          'unit'        => '',
          'unit_code'   => '',
          'product_key' => '',
      ]];
  }

  $currentClientId = old('client_id', $isEdit ? $invoice->client_id : null);
  $currentType     = old('type', $isEdit ? $invoice->type : 'I');

  $currentClientLabel = '';
  if (!empty($currentClientId)) {
      $cc = $clients->firstWhere('id', (int)$currentClientId);
      if ($cc) $currentClientLabel = trim(($cc->nombre ?? '').' — '.($cc->rfc ?? ''));
  }
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
:root{
  --ink:#0f172a;
  --muted:#64748b;
  --line:#e2e8f0;

  --accent:#4f46e5;
  --accent-2:#2563eb;

  --soft-blue:#eaf2ff;
  --soft-blue-2:#dbeafe;
  --soft-blue-ink:#2563eb;

  --soft-red:#ffecec;
  --soft-red-ink:#ef4444;

  --card-border:rgba(209,213,219,0.85);
  --radius:18px;
  --ease:cubic-bezier(.22,1,.36,1);
}

/* ✅ evita scroll horizontal global */
html, body{ overflow-x:hidden; }

/* 🎨 Fondo */
body{
  background: linear-gradient(90deg, #d3b791,#ffffff);
  color:var(--ink);
  font-family:"Söhne","Circular Std","Poppins",system-ui,-apple-system,"Segoe UI","Helvetica Neue",Arial,sans-serif;
  -webkit-font-smoothing:antialiased;
}

/* Tipografía global */
input,button,select,textarea,.badge,.alert,.btn,.form-control,.modal,.modal-title,.swal2-popup.custom-swal{
  font-family:"Söhne","Circular Std","Poppins",system-ui,-apple-system,"Segoe UI","Helvetica Neue",Arial,sans-serif;
}

/* ✅ container sin desbordes */
.container{
  max-width:1400px;
  padding-left:16px;
  padding-right:16px;
}

/* =========================
   ✅ LAYOUT 2 columnas (grid)
   ========================= */
.inv-grid{
  display:grid !important;
  grid-template-columns: 340px 1fr;
  gap:18px;
  align-items:start;
}
@media (max-width: 991.98px){
  .inv-grid{ grid-template-columns: 1fr; gap:16px; }
}

/* neutraliza bootstrap cols dentro de nuestro grid */
.inv-aside, .inv-main{
  width:auto !important;
  max-width:none !important;
  flex:none !important;
}

/* aside sticky */
@media (min-width: 992px){
  .inv-aside{
    position:sticky;
    top:90px;
    align-self:start;
  }
}

/* =========================
   ✅ ESPACIADO ENTRE CONTENEDORES
   ========================= */
.inv-aside .modern-card{ margin-bottom: 14px !important; }
.inv-aside .modern-card:last-child{ margin-bottom: 0 !important; }

.inv-main .modern-card{ margin-bottom: 14px !important; }
.inv-main .modern-card:last-child{ margin-bottom: 0 !important; }

@media (max-width: 991.98px){
  .inv-aside .modern-card,
  .inv-main .modern-card{ margin-bottom: 16px !important; }
}

/* =========================
   ✅ CARD GLASS
   ========================= */
.modern-card{
  position:relative;
  border-radius:var(--radius);
  border:1px solid var(--card-border);
  background-color:rgba(255,255,255,0.55);
  backdrop-filter:blur(18px) saturate(180%);
  -webkit-backdrop-filter:blur(18px) saturate(180%);
  box-shadow:0 22px 50px rgba(15,23,42,0.18);
  overflow:visible;
  z-index:1;
}
.modern-card::before{
  content:"";
  position:absolute; inset:0;
  pointer-events:none;
  border-radius:inherit;
  background:
    radial-gradient(circle at 0 0, rgba(255,255,255,0.70) 0, transparent 60%),
    radial-gradient(circle at 100% 0, rgba(79,70,229,0.18) 0, transparent 65%),
    radial-gradient(circle at 100% 100%, rgba(255,255,255,0.35) 0, transparent 60%);
  opacity:.35;
  mix-blend-mode:soft-light;
}
.modern-card > *{ position:relative; z-index:1; }
.modern-card:focus-within{ z-index:60; }

/* header / body con aire */
.modern-card .card-header.modern-header{
  border-bottom:1px solid rgba(148,163,184,0.35);
  padding:14px 16px !important;
  font-weight:700;
  font-size:.95rem;
  color:var(--ink);
  background:linear-gradient(120deg, rgba(255,255,255,0.92), rgba(248,250,252,0.98));
}
.modern-card .card-body{ padding:16px !important; }

/* =========================
   ✅ INPUTS
   ========================= */
.modern-input,.modern-select,.modern-textarea{
  border-radius:14px;
  border:1px solid rgba(148,163,184,0.65);
  font-size:.95rem;
  padding:.55rem .75rem;
  background:rgba(255,255,255,0.94);
  transition:border-color .18s var(--ease), box-shadow .18s var(--ease), background-color .18s var(--ease), transform .08s;
}
.modern-input:focus,.modern-select:focus,.modern-textarea:focus{
  border-color:rgba(37,99,235,.65);
  outline:none;
  box-shadow:0 0 0 4px rgba(37,99,235,0.12), 0 14px 30px rgba(37,99,235,0.10);
  background:#fff;
  transform:translateY(-1px);
}
.modern-select{
  appearance:none;
  -webkit-appearance:none;
  -moz-appearance:none;
  padding-right:2.2rem;
  background-image:
    linear-gradient(45deg, transparent 50%, rgba(100,116,139,.9) 50%),
    linear-gradient(135deg, rgba(100,116,139,.9) 50%, transparent 50%),
    linear-gradient(to right, transparent, transparent);
  background-position:
    calc(100% - 18px) calc(50% - 3px),
    calc(100% - 12px) calc(50% - 3px),
    0 0;
  background-size:6px 6px, 6px 6px, 100% 100%;
  background-repeat:no-repeat;
}
.modern-select::-ms-expand{ display:none; }

/* =========================
   ✅ DROPDOWN (CLIENTE/PRODUCTO)
   - fixed debajo del input
   - centrado (misma anchura que input)
   - encima de todo
   ========================= */
.modern-dropdown,
.dropdown-menu.modern-dropdown{
  border-radius:18px;
  padding:10px;
  border:1px solid rgba(255,255,255,0.85);
  background:linear-gradient(135deg, rgba(255,255,255,0.96), rgba(248,250,252,0.92));
  backdrop-filter: blur(26px) saturate(190%);
  -webkit-backdrop-filter: blur(26px) saturate(190%);
  box-shadow:0 30px 70px rgba(15,23,42,0.22);
  max-height:340px;
  overflow:auto;
  overflow-x:hidden;
  z-index:99999 !important;

  /* el JS lo posiciona fixed */
  position:fixed !important;
  transform:none !important;
  inset:auto auto auto auto;
}

.modern-dropdown::before,
.dropdown-menu.modern-dropdown::before{
  content:"";
  position:absolute;
  inset:0;
  border-radius:inherit;
  pointer-events:none;
  background:
    radial-gradient(circle at 0 0, rgba(255,255,255,0.55) 0, transparent 60%),
    radial-gradient(circle at 100% 0, rgba(37,99,235,0.12) 0, transparent 60%);
  opacity:.75;
  mix-blend-mode:soft-light;
}
.modern-dropdown > *{ position:relative; z-index:1; }

/* ✅ ITEM bonito (cliente/producto) */
.dd-item{
  border-radius:14px;
  padding:10px 12px;
  margin:6px 2px;
  border:1px solid rgba(148,163,184,0.18);
  background:rgba(255,255,255,0.55);
  transition:transform .12s var(--ease), background-color .12s var(--ease), box-shadow .12s var(--ease), border-color .12s var(--ease);
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  width:100%;
  text-align:left;
}
.dd-item:hover{
  background:rgba(234,242,255,0.85);
  border-color:rgba(37,99,235,0.22);
  transform:translateY(-1px);
  box-shadow:0 12px 26px rgba(37,99,235,0.10);
}
.dd-title{
  font-weight:800;
  color:#0b1220;
  font-size:.92rem;
  line-height:1.15;
}
.dd-sub{
  color:var(--muted);
  font-size:.82rem;
  line-height:1.2;
  margin-top:3px;
}
.dd-right{
  display:flex;
  align-items:center;
  gap:8px;
  flex:0 0 auto;
}
.dd-pill{
  font-weight:800;
  font-size:.80rem;
  border-radius:999px;
  padding:6px 10px;
  border:1px solid rgba(37,99,235,.18);
  background:rgba(234,242,255,0.9);
  color:var(--soft-blue-ink);
  white-space:nowrap;
}
.dd-link{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  width:100%;
  border-radius:14px;
  padding:10px 12px;
  margin:6px 2px;
  background:transparent;
  border:1px dashed rgba(148,163,184,0.25);
  color:#0b1220;
  text-decoration:none;
}
.dd-link:hover{
  background:rgba(255,255,255,0.55);
  border-color:rgba(37,99,235,0.25);
}

/* =========================
   ✅ TABLA sin scroll lateral feo
   ========================= */
.table-responsive{ overflow-x:auto; }
@media (min-width: 992px){
  .table-responsive{ overflow-x:hidden; } /* desktop: intentamos evitar scroll */
}

.modern-table{
  width:100%;
  border-collapse:separate;
  border-spacing:0;
  font-size:.86rem;
  margin-bottom:0;
}
.modern-table thead th{
  padding:.8rem .9rem;
  border-bottom:1px solid rgba(226,232,240,0.95);
  font-weight:800;
  color:#475569;
  background:linear-gradient(135deg, rgba(255,255,255,0.96), rgba(248,250,252,0.98));
  white-space:nowrap;
}
.modern-table thead th:first-child{ border-top-left-radius:16px; }
.modern-table thead th:last-child{ border-top-right-radius:16px; }

.modern-table tbody td{
  padding:.8rem .9rem;
  vertical-align:middle;
  border-top:1px solid rgba(226,232,240,0.9);
  color:#0b1220;
}
.modern-table tbody tr:nth-child(odd){ background:rgba(255,255,255,0.78); }
.modern-table tbody tr:nth-child(even){ background:rgba(248,250,252,0.88); }

.modern-table input[type="number"],
.modern-table input[type="text"],
.modern-table .form-control{
  width:100%;
  border-radius:14px;
  border:1px solid rgba(148,163,184,0.65);
  background:rgba(255,255,255,0.96);
  padding:.42rem .6rem;
  font-size:.85rem;
  height:38px;
  box-shadow:0 8px 20px rgba(15,23,42,0.06);
  transition:border-color .18s var(--ease), box-shadow .18s var(--ease), transform .06s;
}
.modern-table input:focus,
.modern-table .form-control:focus{
  outline:none;
  border-color:rgba(37,99,235,.6);
  box-shadow:0 0 0 4px rgba(37,99,235,0.12), 0 10px 22px rgba(37,99,235,0.10);
  transform:translateY(-1px);
}

/* ✅ producto: clic para cambiar + corta texto largo */
.prod-chip{
  display:flex;
  align-items:center;
  gap:10px;
}
.prod-change{
  width:34px;
  height:34px;
  border-radius:12px;
  border:1px solid rgba(37,99,235,.20);
  background:rgba(234,242,255,0.95);
  color:var(--soft-blue-ink);
  display:inline-flex;
  align-items:center;
  justify-content:center;
}
.prod-name-btn{
  background:transparent;
  border:none;
  padding:0;
  margin:0;
  text-align:left;
  cursor:pointer;
  color:#0b1220;
  font-weight:800;
  line-height:1.2;
  display:-webkit-box;
  -webkit-line-clamp: 4;
  -webkit-box-orient: vertical;
  overflow:hidden;
}
.prod-name-btn:hover{ color:var(--soft-blue-ink); text-decoration:underline; }

.line-pill{
  display:inline-block;
  min-width:110px;
  text-align:right;
  font-weight:900;
  padding:.45rem .75rem;
  border-radius:14px;
  border:1px solid rgba(148,163,184,0.45);
  background:rgba(255,255,255,0.92);
}

/* =========================
   ✅ BOTONES PASTEL (sin degradado)
   ========================= */
.btn-soft{
  border-radius:999px !important;
  padding:.60rem 1.05rem !important;
  font-weight:900 !important;
  border:1px solid rgba(37,99,235,.20) !important;
  background:rgba(234,242,255,0.95) !important;
  color:var(--soft-blue-ink) !important;
  display:inline-flex !important;
  align-items:center !important;
  gap:.5rem !important;
  transition:transform .12s var(--ease), box-shadow .12s var(--ease), background-color .12s var(--ease);
  box-shadow:0 10px 22px rgba(37,99,235,0.10);
}
.btn-soft:hover{
  background:rgba(219,234,254,1) !important;
  transform:translateY(-1px);
  box-shadow:0 14px 28px rgba(37,99,235,0.14);
}
.btn-soft:active{ transform:translateY(0); }

.btn-soft-danger{
  border-radius:999px !important;
  padding:.55rem .9rem !important;
  font-weight:900 !important;
  border:1px solid rgba(239,68,68,.18) !important;
  background:rgba(255,236,236,0.95) !important;
  color:var(--soft-red-ink) !important;
}
.btn-soft-danger:hover{ background:rgba(254,226,226,1) !important; }

.icon-btn-danger{
  width:40px; height:40px;
  border-radius:999px !important;
  display:inline-flex; align-items:center; justify-content:center;
}

/* Tip */
.tip{
  margin-top:10px;
  color:#475569;
  font-size:.86rem;
}

/* =========================
   ✅ SWEETALERT + IMAGE PREVIEW + MODAL (lo que tenías)
   ========================= */
.swal2-popup.custom-swal{
  border-radius:16px;
  font-size:15px;
  color:#444;
  background-color:#fdfcff;
  box-shadow:0 10px 30px rgba(0,0,0,0.08);
  padding:2rem;
}
.swal2-title.custom-title{
  font-size:22px;
  font-weight:600;
  color:#333;
  display:flex;
  align-items:center;
  gap:10px;
  justify-content:center;
}
.swal2-html-container.custom-html{
  text-align:left;
  line-height:1.8;
  color:#555;
  padding:.5rem 1rem;
}
.swal2-confirm.custom-btn{
  background-color:#a78bfa;
  color:#fff !important;
  font-weight:600;
  padding:.5rem 1.2rem;
  border-radius:12px;
  font-size:15px;
  box-shadow:0 4px 10px rgba(167,139,250,0.3);
  transition:all .2s ease-in-out;
}
.swal2-confirm.custom-btn:hover{ background-color:#8b5cf6; }
.swal-img-evidencia{
  width:100%;
  max-height:260px;
  object-fit:contain;
  border-radius:12px;
  margin-top:1rem;
  box-shadow:0 4px 14px rgba(0,0,0,0.1);
}
.image-container{
  width:150px;
  height:150px;
  border:2px dashed #ccc;
  border-radius:8px;
  display:flex;
  align-items:center;
  justify-content:center;
  overflow:hidden;
  cursor:pointer;
  background-color:#f9f9f9;
  transition:border-color .3s ease;
}
.image-container:hover{ border-color:#4a90e2; }
#preview-icon{
  max-width:100%;
  max-height:100%;
  object-fit:contain;
  border-radius:6px;
  transition:transform .3s ease;
}
#preview-text{
  color:#999;
  font-size:.9rem;
  text-align:center;
}
#formProducto input[type="text"],
#formProducto input[type="number"],
#formProducto input[type="file"],
#formProducto .form-control{
  border:1px solid #ccc;
  border-radius:6px;
  padding:8px 12px;
  font-size:1rem;
  transition:border-color .3s ease;
}
#formProducto input[type="text"]:focus,
#formProducto input[type="number"]:focus,
#formProducto input[type="file"]:focus,
#formProducto .form-control:focus{
  border-color:#4a90e2;
  outline:none;
  box-shadow:0 0 5px rgba(74,144,226,0.5);
}
#formProducto button.btn-primary{
  background-color:#4a90e2;
  border:none;
  border-radius:6px;
  padding:10px 20px;
  font-weight:600;
  color:#fff;
  transition:background-color .3s ease;
}
#formProducto button.btn-primary:hover{ background-color:#357ABD; }
</style>

<div class="container" style="margin-top:80px;">
  <form id="form-invoice"
        method="POST"
        action="{{ $isEdit ? route('manual_invoices.update', $invoice) : route('manual_invoices.store') }}">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row inv-grid">

      {{-- ======================= IZQUIERDA ======================= --}}
      <div class="col-md-3 mt-3 inv-aside">

        {{-- Cliente --}}
        <div class="card modern-card">
          <div class="card-header modern-header">
            <i class="fa-regular fa-user me-2" style="color:var(--accent-2)"></i> Cliente
          </div>
          <div class="card-body">
            <div class="dropdown">
              <input
                type="text"
                id="search-client-inv"
                class="form-control modern-input dropdown-toggle"
                data-bs-toggle="dropdown"
                placeholder="Buscar cliente..."
                autocomplete="off"
                value="{{ old('client_label', $currentClientLabel) }}"
              >
              <ul class="dropdown-menu modern-dropdown w-100" id="client-list-inv">
                <li>
                  <button type="button"
                          class="dd-item js-pick-client"
                          data-id="1"
                          data-label="Público en General"
                          data-nombre="PÚBLICO EN GENERAL"
                          data-rfc=""
                          data-telefono=""
                          data-email=""
                          data-comentarios="">
                    <div>
                      <div class="dd-title">PÚBLICO EN GENERAL</div>
                      <div class="dd-sub">Genérico</div>
                    </div>
                    <div class="dd-right">
                      <span class="dd-pill">ID 1</span>
                    </div>
                  </button>
                </li>

                <li>
                  <a class="dd-link"
                     href="{{ \Illuminate\Support\Facades\Route::has('clients.create') ? route('clients.create') : '#' }}"
                     target="_blank" rel="noopener"
                     style="{{ \Illuminate\Support\Facades\Route::has('clients.create') ? '' : 'pointer-events:none;opacity:.5' }}">
                    <span><i class="fa-solid fa-plus me-2" style="color:var(--accent-2)"></i> Crear nuevo cliente</span>
                    <span class="dd-pill">Abrir</span>
                  </a>
                </li>

                <li><hr class="dropdown-divider"></li>

                @foreach($clients as $c)
                  @php
                    $label  = trim(($c->nombre ?? '').' — '.($c->rfc ?? ''));
                    $search = Str::of($label)->lower()->ascii();
                  @endphp
                  <li class="js-client-li" data-search="{{ $search }}">
                    <button type="button"
                            class="dd-item js-pick-client"
                            data-id="{{ $c->id }}"
                            data-label="{{ $label }}"
                            data-nombre="{{ strtoupper($c->nombre ?? '') }}"
                            data-rfc="{{ $c->rfc ?? '' }}"
                            data-telefono="{{ $c->telefono ?? '' }}"
                            data-email="{{ $c->email ?? '' }}"
                            data-comentarios="{{ $c->comentarios ?? '' }}">
                      <div style="min-width:0;">
                        <div class="dd-title">{{ strtoupper($c->nombre ?? 'SIN NOMBRE') }}</div>
                        <div class="dd-sub">RFC: {{ $c->rfc ?? '—' }}</div>
                      </div>
                      <div class="dd-right">
                        <span class="dd-pill">ID {{ $c->id }}</span>
                      </div>
                    </button>
                  </li>
                @endforeach
              </ul>
            </div>

            <input type="hidden" name="client_id" id="client_id" value="{{ $currentClientId }}">
            @error('client_id')
              <small class="text-danger d-block mt-2">{{ $message }}</small>
            @enderror

            <div id="client-details-inv" class="mt-3"></div>
          </div>
        </div>

        {{-- Tipo --}}
        <div class="card modern-card">
          <div class="card-header modern-header">
            <i class="fa-solid fa-tag me-2" style="color:var(--accent-2)"></i> Tipo
          </div>
          <div class="card-body">
            <select name="type" class="form-control modern-select">
              <option value="I" {{ $currentType === 'I' ? 'selected' : '' }}>Ingreso</option>
              <option value="E" {{ $currentType === 'E' ? 'selected' : '' }}>Egreso</option>
              <option value="P" {{ $currentType === 'P' ? 'selected' : '' }}>Pago</option>
            </select>
          </div>
        </div>

        {{-- Notas --}}
        <div class="card modern-card">
          <div class="card-header modern-header">
            <i class="fa-regular fa-note-sticky me-2" style="color:var(--accent-2)"></i> Notas internas
          </div>
          <div class="card-body">
            <textarea name="notes" rows="4" class="form-control modern-textarea"
                      placeholder="Texto libre (no se envía al SAT)">{{ old('notes', $isEdit ? ($invoice->notes ?? '') : '') }}</textarea>
          </div>
        </div>

        {{-- Registrado por --}}
        <div class="card modern-card">
          <div class="card-header modern-header">
            <i class="fa-regular fa-id-badge me-2" style="color:var(--accent-2)"></i> Registrado por
          </div>
          <div class="card-body">
            @auth
              <input type="text" class="form-control modern-textarea" value="{{ auth()->user()->name }}" readonly>
            @else
              <input type="text" class="form-control modern-textarea" value="Desconocido" readonly>
            @endauth
          </div>
        </div>

      </div>

      {{-- ======================= DERECHA ======================= --}}
      <div class="col-md-9 inv-main">

        {{-- Productos --}}
        <div class="card modern-card mt-3">
          <div class="card-header modern-header">
            <i class="fa-solid fa-box me-2" style="color:var(--accent-2)"></i> Productos
          </div>
          <div class="card-body">
            <div class="dropdown">
              <input
                type="text"
                id="buscarProductoInv"
                class="form-control modern-input dropdown-toggle"
                data-bs-toggle="dropdown"
                placeholder="Buscar producto por SKU o nombre..."
                autocomplete="off"
              >

              <ul class="dropdown-menu modern-dropdown w-100" id="dropdownProductosInv">
                @foreach($products as $p)
                  @php
                    $price  = $p->price ?? $p->market_price ?? $p->bid_price ?? 0;
                    $label  = trim(($p->sku ?? '').' — '.Str::limit($p->name ?? '', 70));
                    $search = Str::of($label)->lower()->ascii();
                  @endphp
                  <li class="js-prod-li" data-search="{{ $search }}">
                    <button type="button"
                            class="dd-item js-pick-product"
                            data-id="{{ $p->id }}"
                            data-label="{{ $label }}"
                            data-price="{{ (float)$price }}"
                            data-unit="{{ $p->unit ?? '' }}"
                            data-unit_code="{{ $p->unit_code ?? '' }}"
                            data-product_key="{{ $p->clave_sat ?? '' }}">
                      <div style="min-width:0;">
                        <div class="dd-title">{{ strtoupper($p->sku ?? '') }} — {{ strtoupper(Str::limit($p->name ?? '', 42)) }}</div>
                        <div class="dd-sub">Clave SAT: {{ $p->clave_sat ?? '—' }}</div>
                      </div>
                      <div class="dd-right">
                        <span class="dd-pill">${{ number_format((float)$price, 2) }}</span>
                        @if(isset($p->stock))
                          <span class="dd-pill" style="border-color:rgba(100,116,139,.18);background:rgba(248,250,252,.95);color:#334155;">
                            {{ $p->stock }} uds
                          </span>
                        @endif
                      </div>
                    </button>
                  </li>
                @endforeach
              </ul>
            </div>

            <div class="tip">
              Tip: selecciona un producto para agregarlo. Para cambiar uno existente, haz clic en el nombre del producto en la tabla.
            </div>
          </div>
        </div>

        {{-- Conceptos --}}
        <div class="card modern-card">
          <div class="card-header modern-header">
            <i class="fa-solid fa-list-check me-2" style="color:var(--accent-2)"></i> Conceptos
          </div>

          <div class="card-body">
            <div class="table-responsive">
              <table class="table modern-table" id="itemsTable">
                <thead>
                  <tr>
                    <th style="width:26%;">Producto</th>
                    <th style="width:26%;">Descripción</th>
                    <th style="width:10%;">Cant.</th>
                    <th style="width:10%;">P. unit.</th>
                    <th style="width:10%;">Desc.</th>
                    <th style="width:8%;">IVA%</th>
                    <th style="width:8%;">Total</th>
                    <th style="width:2%;">Acción</th>
                  </tr>
                </thead>
                <tbody id="itemsTbody">
                  @foreach($rows as $i => $row)
                    @php
                      $pLabel = 'Manual';
                      if (!empty($row['product_id'])) {
                        $pp = $products->firstWhere('id', (int)$row['product_id']);
                        if ($pp) $pLabel = trim(($pp->sku ?? '').' — '.Str::limit($pp->name ?? '', 120));
                      }
                    @endphp
                    <tr data-idx="{{ $i }}">
                      @if(!empty($row['id']))
                        <input type="hidden" name="items[{{ $i }}][id]" value="{{ $row['id'] }}">
                      @endif

                      <td>
                        <div class="prod-chip">
                          <span class="prod-change" title="Cambiar producto"><i class="fa-solid fa-rotate"></i></span>
                          <button type="button" class="prod-name-btn js-change-label">{{ $pLabel }}</button>
                        </div>

                        <input type="hidden" class="js-product-id" name="items[{{ $i }}][product_id]" value="{{ $row['product_id'] ?? '' }}">
                        <input type="hidden" name="items[{{ $i }}][unit]" value="{{ $row['unit'] ?? '' }}">
                        <input type="hidden" name="items[{{ $i }}][unit_code]" value="{{ $row['unit_code'] ?? '' }}">
                        <input type="hidden" name="items[{{ $i }}][product_key]" value="{{ $row['product_key'] ?? '' }}">
                      </td>

                      <td>
                        <input type="text" class="form-control"
                               name="items[{{ $i }}][description]"
                               value="{{ $row['description'] ?? '' }}"
                               placeholder="Descripción" required>
                      </td>

                      <td><input type="number" step="0.001" min="0.001" class="form-control js-qty" name="items[{{ $i }}][quantity]" value="{{ $row['quantity'] ?? 1 }}"></td>
                      <td><input type="number" step="0.01" min="0" class="form-control js-price" name="items[{{ $i }}][unit_price]" value="{{ $row['unit_price'] ?? 0 }}"></td>
                      <td><input type="number" step="0.01" min="0" class="form-control js-discount" name="items[{{ $i }}][discount]" value="{{ $row['discount'] ?? 0 }}"></td>
                      <td><input type="number" step="0.01" min="0" class="form-control js-tax" name="items[{{ $i }}][tax_rate]" value="{{ $row['tax_rate'] ?? 16 }}"></td>

                      <td class="text-end">
                        <span class="line-pill js-line-total">$0.00</span>
                      </td>

                      <td class="text-center">
                        <button type="button" class="btn btn-soft-danger icon-btn-danger js-remove" title="Quitar">
                          <i class="fa-solid fa-xmark"></i>
                        </button>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="d-flex flex-column flex-md-row gap-3">
          {{-- Resumen --}}
          <div class="card modern-card w-100">
            <div class="card-header modern-header">
              <i class="fa-solid fa-receipt me-2" style="color:var(--accent-2)"></i> Resumen
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px dashed rgba(148,163,184,.35)">
                <div class="text-muted fw-bold">Subtotal</div>
                <div class="fw-black">$<span id="sum_sub">0.00</span></div>
              </div>
              <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px dashed rgba(148,163,184,.35)">
                <div class="text-muted fw-bold">Descuento</div>
                <div class="fw-black">$<span id="sum_disc">0.00</span></div>
              </div>
              <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px dashed rgba(148,163,184,.35)">
                <div class="text-muted fw-bold">Impuestos</div>
                <div class="fw-black">$<span id="sum_tax">0.00</span></div>
              </div>

              <div class="d-flex justify-content-between align-items-center pt-3">
                <div style="font-weight:950;font-size:1.05rem;">Total</div>
                <div style="font-weight:950;font-size:1.15rem;">$<span id="sum_total">0.00</span></div>
              </div>
            </div>
          </div>

          {{-- Acciones --}}
          <div class="card modern-card w-100">
            <div class="card-header modern-header">
              <i class="fa-solid fa-bolt me-2" style="color:var(--accent-2)"></i> Acciones
            </div>
            <div class="card-body d-flex justify-content-end gap-2 flex-wrap">
              <a href="{{ route('manual_invoices.index') }}" class="btn btn-soft">
                <i class="fa-solid fa-arrow-left"></i> Cancelar
              </a>
              <button type="submit" class="btn btn-soft" id="btnSaveInv">
                <i class="fa-solid fa-check"></i>
                <span class="btn-label">{{ $isEdit ? 'Guardar cambios' : 'Guardar borrador' }}</span>
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>
  </form>
</div>

<script>
(function(){
  const $  = (id) => document.getElementById(id);
  const norm = (s) => (s||'').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
  const money = (n) => (Number(n||0)).toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2});

  /* =========================
     ✅ DROPDOWN: fixed debajo del input + siempre encima
     ========================= */
  function placeMenuUnderInput(input, menu){
    if (!input || !menu) return;
    const r = input.getBoundingClientRect();
    const gap = 8;

    const vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
    const vh = Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0);

    // ancho igual al input (pero sin salir del viewport)
    let left = Math.max(10, r.left);
    let width = Math.min(r.width, vw - left - 10);
    // si el input está muy a la derecha, corrige left
    if (left + width > vw - 10) left = Math.max(10, vw - width - 10);

    const top = Math.min(r.bottom + gap, vh - 120);

    menu.style.left = left + 'px';
    menu.style.top = top + 'px';
    menu.style.width = width + 'px';
    menu.style.maxHeight = Math.max(180, vh - top - 16) + 'px';
  }

  function openDropdown(input, menu){
    placeMenuUnderInput(input, menu);
    menu.classList.add('show');
    menu.style.display = 'block';
  }
  function closeDropdown(menu){
    menu.classList.remove('show');
    menu.style.display = 'none';
  }
  function closeOnOutside(input, menu){
    document.addEventListener('click', (e) => {
      if (menu.style.display === 'none') return;
      if (menu.contains(e.target) || input === e.target) return;
      closeDropdown(menu);
    });
  }

  function attachReposition(input, menu){
    const handler = () => {
      if (menu.style.display !== 'none') placeMenuUnderInput(input, menu);
    };
    window.addEventListener('scroll', handler, true);
    window.addEventListener('resize', handler);
  }

  /* =========================
     ✅ CLIENTES
     ========================= */
  const clientInput   = $('search-client-inv');
  const clientMenu    = $('client-list-inv');
  const clientHidden  = $('client_id');
  const clientDetails = $('client-details-inv');

  function renderClient(btn){
    const nombre = (btn.dataset.nombre || '').toUpperCase();
    const rfc    = (btn.dataset.rfc || '');
    const tel    = (btn.dataset.telefono || '');
    const email  = (btn.dataset.email || '');
    const dir    = (btn.dataset.comentarios || '');

    clientDetails.innerHTML = `
      <div style="border:1px solid rgba(148,163,184,.35);border-radius:16px;background:rgba(255,255,255,.75);padding:12px;">
        <div style="font-weight:950;">${nombre || 'CLIENTE'}</div>
        ${rfc ? `<div class="text-muted" style="font-weight:800;">RFC: ${rfc}</div>` : ``}
        <div class="text-muted">Tel: ${tel || 'No registrado'}</div>
        <div class="text-muted">Email: ${email || 'No registrado'}</div>
        <div class="text-muted">Dirección: ${dir || 'No registrado'}</div>
      </div>
    `;
  }

  function filterClients(){
    const q = norm(clientInput.value);
    openDropdown(clientInput, clientMenu);

    clientMenu.querySelectorAll('.js-client-li').forEach(li => {
      const s = li.dataset.search || norm(li.textContent);
      li.style.display = (!q || s.includes(q)) ? '' : 'none';
    });
  }

  clientInput.addEventListener('focus', filterClients);
  clientInput.addEventListener('click', filterClients);
  clientInput.addEventListener('input', filterClients);
  closeOnOutside(clientInput, clientMenu);
  attachReposition(clientInput, clientMenu);

  clientMenu.addEventListener('click', (e) => {
    const btn = e.target.closest('.js-pick-client');
    if (!btn) return;
    e.preventDefault();

    clientHidden.value = btn.dataset.id || '';
    clientInput.value  = btn.dataset.label || btn.textContent.trim();
    renderClient(btn);
    closeDropdown(clientMenu);
  });

  if (clientHidden.value) {
    const btn = clientMenu.querySelector(`.js-pick-client[data-id="${clientHidden.value}"]`);
    if (btn) renderClient(btn);
  }

  /* =========================
     ✅ PRODUCTOS
     ========================= */
  const prodInput = $('buscarProductoInv');
  const prodMenu  = $('dropdownProductosInv');
  let activeRow = null; // fila a cambiar

  function filterProducts(){
    const q = norm(prodInput.value);
    openDropdown(prodInput, prodMenu);

    prodMenu.querySelectorAll('.js-prod-li').forEach(li => {
      const s = li.dataset.search || norm(li.textContent);
      li.style.display = (!q || s.includes(q)) ? '' : 'none';
    });
  }

  prodInput.addEventListener('focus', filterProducts);
  prodInput.addEventListener('click', filterProducts);
  prodInput.addEventListener('input', filterProducts);
  closeOnOutside(prodInput, prodMenu);
  attachReposition(prodInput, prodMenu);

  /* =========================
     ✅ TABLA ITEMS
     ========================= */
  const tbody   = $('itemsTbody');
  const sumSub  = $('sum_sub');
  const sumDisc = $('sum_disc');
  const sumTax  = $('sum_tax');
  const sumTot  = $('sum_total');

  function recalc(){
    let sub = 0, disc = 0, tax = 0, tot = 0;

    tbody.querySelectorAll('tr').forEach(tr => {
      const qty = parseFloat(tr.querySelector('.js-qty')?.value || '0') || 0;
      const price = parseFloat(tr.querySelector('.js-price')?.value || '0') || 0;
      const d = parseFloat(tr.querySelector('.js-discount')?.value || '0') || 0;
      const t = parseFloat(tr.querySelector('.js-tax')?.value || '0') || 0;

      const base = Math.max(qty * price - d, 0);
      const iva  = base * (t / 100);
      const line = base + iva;

      tr.querySelector('.js-line-total').textContent = '$' + money(line);

      sub += base;
      disc += d;
      tax += iva;
      tot += line;
    });

    sumSub.textContent  = money(sub);
    sumDisc.textContent = money(disc);
    sumTax.textContent  = money(tax);
    sumTot.textContent  = money(tot);
  }

  function renumber(){
    tbody.querySelectorAll('tr').forEach((tr, idx) => {
      tr.dataset.idx = idx;
      tr.querySelectorAll('input[name^="items["]').forEach(inp => {
        inp.name = inp.name.replace(/items\[\d+]/, 'items['+idx+']');
      });
    });
  }

  function addRow(data = {}){
    const idx = tbody.querySelectorAll('tr').length;

    const tr = document.createElement('tr');
    tr.dataset.idx = idx;

    const label = data.label || 'Manual';

    tr.innerHTML = `
      <td>
        <div class="prod-chip">
          <span class="prod-change" title="Cambiar producto"><i class="fa-solid fa-rotate"></i></span>
          <button type="button" class="prod-name-btn js-change-label">${label}</button>
        </div>

        <input type="hidden" class="js-product-id" name="items[${idx}][product_id]" value="${data.product_id || ''}">
        <input type="hidden" name="items[${idx}][unit]" value="${data.unit || ''}">
        <input type="hidden" name="items[${idx}][unit_code]" value="${data.unit_code || ''}">
        <input type="hidden" name="items[${idx}][product_key]" value="${data.product_key || ''}">
      </td>

      <td><input type="text" class="form-control" name="items[${idx}][description]" value="${(data.description||'').replace(/"/g,'&quot;')}" placeholder="Descripción" required></td>
      <td><input type="number" step="0.001" min="0.001" class="form-control js-qty" name="items[${idx}][quantity]" value="${data.quantity ?? 1}"></td>
      <td><input type="number" step="0.01" min="0" class="form-control js-price" name="items[${idx}][unit_price]" value="${data.unit_price ?? 0}"></td>
      <td><input type="number" step="0.01" min="0" class="form-control js-discount" name="items[${idx}][discount]" value="${data.discount ?? 0}"></td>
      <td><input type="number" step="0.01" min="0" class="form-control js-tax" name="items[${idx}][tax_rate]" value="${data.tax_rate ?? 16}"></td>
      <td class="text-end"><span class="line-pill js-line-total">$0.00</span></td>
      <td class="text-center">
        <button type="button" class="btn btn-soft-danger icon-btn-danger js-remove" title="Quitar">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </td>
    `;

    tbody.appendChild(tr);
    renumber();
    recalc();
  }

  function setRowProduct(tr, p){
    tr.querySelector('.js-change-label').textContent = p.label || 'Manual';
    tr.querySelector('.js-product-id').value = p.id || '';

    const unit = tr.querySelector('input[name*="[unit]"]');
    const unitCode = tr.querySelector('input[name*="[unit_code]"]');
    const pkey = tr.querySelector('input[name*="[product_key]"]');

    if (unit) unit.value = p.unit || '';
    if (unitCode) unitCode.value = p.unit_code || '';
    if (pkey) pkey.value = p.product_key || '';

    const priceInp = tr.querySelector('.js-price');
    if (priceInp) priceInp.value = Number(p.price || 0).toFixed(2);

    const desc = tr.querySelector('input[name*="[description]"]');
    if (desc && !desc.value.trim()) desc.value = p.label || '';

    recalc();
  }

  // recalc al escribir
  tbody.addEventListener('input', (e) => {
    if (e.target.matches('.js-qty,.js-price,.js-discount,.js-tax')) recalc();
  });

  // quitar fila / cambiar producto (click en nombre)
  tbody.addEventListener('click', (e) => {
    const tr = e.target.closest('tr');
    if (!tr) return;

    if (e.target.closest('.js-remove')) {
      tr.remove();
      if (!tbody.querySelector('tr')) addRow();
      renumber();
      recalc();
      return;
    }

    if (e.target.closest('.js-change-label') || e.target.closest('.prod-change')) {
      activeRow = tr;
      prodInput.focus();
      filterProducts();
      return;
    }
  });

  // seleccionar producto -> agrega fila o cambia fila activa
  prodMenu.addEventListener('click', (e) => {
    const btn = e.target.closest('.js-pick-product');
    if (!btn) return;

    e.preventDefault();

    const p = {
      id: btn.dataset.id || '',
      label: btn.dataset.label || btn.textContent.trim(),
      price: parseFloat(btn.dataset.price || '0') || 0,
      unit: btn.dataset.unit || '',
      unit_code: btn.dataset.unit_code || '',
      product_key: btn.dataset.product_key || ''
    };

    if (activeRow) {
      setRowProduct(activeRow, p);
      activeRow = null;
    } else {
      addRow({
        product_id: p.id,
        label: p.label,
        description: p.label,
        quantity: 1,
        unit_price: p.price,
        discount: 0,
        tax_rate: 16,
        unit: p.unit,
        unit_code: p.unit_code,
        product_key: p.product_key
      });
    }

    prodInput.value = '';
    closeDropdown(prodMenu);
  });

  // Validación cliente
  $('form-invoice')?.addEventListener('submit', (e) => {
    if (!clientHidden.value) {
      e.preventDefault();
      alert('Por favor selecciona un cliente.');
      clientInput.focus();
      filterClients();
    }
  });

  // init
  recalc();
  closeDropdown(clientMenu);
  closeDropdown(prodMenu);
})();
</script>
@endsection
