{{-- resources/views/manual_invoices/create.blade.php (o tu vista equivalente) --}}
@extends('layouts.app')

@section('title', isset($invoice) && $invoice->exists ? 'Editar Factura' : 'Nueva Factura')

@section('content')
@php
  use Illuminate\Support\Str;

  $isEdit = isset($invoice) && $invoice->exists;

  // ===================== ITEMS =====================
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
          'tax_rate'    => 16,   // ✅ IVA default
          'unit'        => '',
          'unit_code'   => '',
          'product_key' => '',
      ]];
  }

  // ===================== CLIENTE / TIPO =====================
  $currentClientId = old('client_id', $isEdit ? $invoice->client_id : null);
  $currentType     = old('type', $isEdit ? $invoice->type : 'I');

  $currentClientLabel = '';
  if (!empty($currentClientId)) {
      $cc = $clients->firstWhere('id', (int)$currentClientId);
      if ($cc) $currentClientLabel = trim(($cc->nombre ?? '').' — '.($cc->rfc ?? ''));
  }

  // ===================== INFO PAGO (defaults estilo SAT)
  $payCurrency   = old('pay_currency',   $isEdit ? ($invoice->pay_currency ?? 'MXN') : 'MXN');
  $exchangeRate  = old('exchange_rate',  $isEdit ? ($invoice->exchange_rate ?? 1) : 1);
  $paymentMethod = old('payment_method', $isEdit ? ($invoice->payment_method ?? 'PUE') : 'PUE'); // PUE/PPD
  $paymentForm   = old('payment_form',   $isEdit ? ($invoice->payment_form ?? '99') : '99');     // FormaPago
  $cfdiUse       = old('cfdi_use',       $isEdit ? ($invoice->cfdi_use ?? 'G03') : 'G03');       // UsoCFDI
  $exportation   = old('exportation',    $isEdit ? ($invoice->exportation ?? '01') : '01');      // Exportacion

  // Catálogos (compactos pero útiles)
  $monedas = [
    'MXN' => 'MXN – Mexican Peso',
    'USD' => 'USD – US Dollar',
    'EUR' => 'EUR – Euro',
  ];

  $formasPago = [
    '01'=>'01 – Efectivo',
    '02'=>'02 – Cheque nominativo',
    '03'=>'03 – Transferencia electrónica de fondos',
    '04'=>'04 – Tarjeta de crédito',
    '05'=>'05 – Monedero electrónico',
    '06'=>'06 – Dinero electrónico',
    '08'=>'08 – Vales de despensa',
    '12'=>'12 – Dación en pago',
    '13'=>'13 – Pago por subrogación',
    '14'=>'14 – Pago por consignación',
    '15'=>'15 – Condonación',
    '17'=>'17 – Compensación',
    '23'=>'23 – Novación',
    '24'=>'24 – Confusión',
    '25'=>'25 – Remisión de deuda',
    '26'=>'26 – Prescripción o caducidad',
    '27'=>'27 – A satisfacción del acreedor',
    '28'=>'28 – Tarjeta de débito',
    '29'=>'29 – Tarjeta de servicios',
    '30'=>'30 – Aplicación de anticipos',
    '31'=>'31 – Intermediario de pagos',
    '99'=>'99 – Por definir',
  ];

  $usosCfdi = [
    'G01'=>'G01 – Adquisición de mercancías',
    'G02'=>'G02 – Devoluciones, descuentos o bonificaciones',
    'G03'=>'G03 – Gastos en general',
    'I01'=>'I01 – Construcciones',
    'I02'=>'I02 – Mobiliario y equipo de oficina por inversiones',
    'I03'=>'I03 – Equipo de transporte',
    'I04'=>'I04 – Equipo de cómputo y accesorios',
    'I05'=>'I05 – Dados, troqueles, moldes, matrices y herramental',
    'I06'=>'I06 – Comunicaciones telefónicas',
    'I07'=>'I07 – Comunicaciones satelitales',
    'I08'=>'I08 – Otra maquinaria y equipo',
    'D01'=>'D01 – Honorarios médicos, dentales y gastos hospitalarios',
    'D02'=>'D02 – Gastos médicos por incapacidad o discapacidad',
    'D03'=>'D03 – Gastos funerales',
    'D04'=>'D04 – Donativos',
    'D05'=>'D05 – Intereses reales efectivamente pagados',
    'D06'=>'D06 – Aportaciones voluntarias al SAR',
    'D07'=>'D07 – Primas por seguros de gastos médicos',
    'D08'=>'D08 – Gastos de transportación escolar',
    'D09'=>'D09 – Depósitos en cuentas para el ahorro',
    'D10'=>'D10 – Pagos por servicios educativos',
    'S01'=>'S01 – Sin efectos fiscales',
    'CP01'=>'CP01 – Pagos',
    'CN01'=>'CN01 – Nómina',
  ];

  $exportaciones = [
    '01' => 'No aplica',
    '02' => 'Definitiva con clave A1',
    '03' => 'Temporal',
    '04' => 'Definitiva con clave distinta a A1 o cuando no existe enajenación en términos del CFF',
  ];
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

html, body{ overflow-x:hidden; }

body{
  background: linear-gradient(90deg, #d3b791,#ffffff);
  color:var(--ink);
  font-family:"Söhne","Circular Std","Poppins",system-ui,-apple-system,"Segoe UI","Helvetica Neue",Arial,sans-serif;
  -webkit-font-smoothing:antialiased;
}

input,button,select,textarea,.badge,.alert,.btn,.form-control,.modal,.modal-title,.swal2-popup.custom-swal{
  font-family:"Söhne","Circular Std","Poppins",system-ui,-apple-system,"Segoe UI","Helvetica Neue",Arial,sans-serif;
}

.container{
  max-width:1400px;
  padding-left:16px;
  padding-right:16px;
}

/* TOP BAR */
.inv-topbar{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  padding:10px 6px 2px;
}
.inv-title{ font-weight:950; letter-spacing:-.02em; margin:0; font-size:1.05rem; }
.inv-sub{ margin:2px 0 0; color:var(--muted); font-size:.88rem; font-weight:700; }

/* GRID */
.inv-grid{
  display:grid !important;
  grid-template-columns: 340px 1fr;
  gap:18px;
  align-items:start;
}
@media (max-width: 991.98px){
  .inv-grid{ grid-template-columns: 1fr; gap:16px; }
  .inv-topbar{ padding-top:12px; }
}

.inv-aside, .inv-main{ width:auto !important; max-width:none !important; flex:none !important; }

@media (min-width: 992px){
  .inv-aside{ position:sticky; top:90px; align-self:start; }
}

.inv-aside .modern-card{ margin-bottom: 14px !important; }
.inv-aside .modern-card:last-child{ margin-bottom: 0 !important; }
.inv-main .modern-card{ margin-bottom: 14px !important; }
.inv-main .modern-card:last-child{ margin-bottom: 0 !important; }

/* CARD GLASS */
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

.modern-card .card-header.modern-header{
  border-bottom:1px solid rgba(148,163,184,0.35);
  padding:14px 16px !important;
  font-weight:700;
  font-size:.95rem;
  color:var(--ink);
  background:linear-gradient(120deg, rgba(255,255,255,0.92), rgba(248,250,252,0.98));
}
.modern-card .card-body{ padding:16px !important; }

/* INPUTS */
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
  appearance:none; -webkit-appearance:none; -moz-appearance:none;
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

/* DROPDOWN */
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
  position:fixed !important;
  transform:none !important;
  inset:auto auto auto auto;
  display:none;
}
.modern-dropdown::before,
.dropdown-menu.modern-dropdown::before{
  content:"";
  position:absolute; inset:0;
  border-radius:inherit;
  pointer-events:none;
  background:
    radial-gradient(circle at 0 0, rgba(255,255,255,0.55) 0, transparent 60%),
    radial-gradient(circle at 100% 0, rgba(37,99,235,0.12) 0, transparent 60%);
  opacity:.75;
  mix-blend-mode:soft-light;
}
.modern-dropdown > *{ position:relative; z-index:1; }

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
.dd-title{ font-weight:800; color:#0b1220; font-size:.92rem; line-height:1.15; }
.dd-sub{ color:var(--muted); font-size:.82rem; line-height:1.2; margin-top:3px; }
.dd-right{ display:flex; align-items:center; gap:8px; flex:0 0 auto; }
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
.dd-link:hover{ background:rgba(255,255,255,0.55); border-color:rgba(37,99,235,0.25); }

/* TABLE */
.table-responsive{ overflow-x:auto; }
.modern-table{
  width:100%;
  border-collapse:separate;
  border-spacing:0;
  font-size:.86rem;
  margin-bottom:0;
  table-layout:fixed;
  min-width:1100px; /* ✅ evita “desaparecer” columnas */
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
  vertical-align:top;
  border-top:1px solid rgba(226,232,240,0.9);
  color:#0b1220;
  overflow:hidden;
}
.modern-table tbody tr:nth-child(odd){ background:rgba(255,255,255,0.78); }
.modern-table tbody tr:nth-child(even){ background:rgba(248,250,252,0.88); }

.modern-table input[type="number"],
.modern-table input[type="text"],
.modern-table textarea.form-control,
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
.modern-table textarea.form-control{
  height:auto;
  min-height:46px;
  resize:vertical;
  line-height:1.2;
}
.modern-table input:focus,
.modern-table textarea:focus,
.modern-table .form-control:focus{
  outline:none;
  border-color:rgba(37,99,235,.6);
  box-shadow:0 0 0 4px rgba(37,99,235,0.12), 0 10px 22px rgba(37,99,235,0.10);
  transform:translateY(-1px);
}

/* PRODUCTO: wrap + solo SKU en negrita */
.prod-cell{ min-width:0; }
.prod-name-btn{
  width:100%;
  display:flex;
  align-items:flex-start;
  gap:10px;
  text-align:left;
  border:none;
  background:rgba(234,242,255,0.70);
  border:1px solid rgba(37,99,235,.18);
  color:#0b1220;
  border-radius:16px;
  padding:10px 12px;
  cursor:pointer;
  transition:transform .12s var(--ease), box-shadow .12s var(--ease), background-color .12s var(--ease);
}
.prod-name-btn:hover{
  background:rgba(219,234,254,1);
  transform:translateY(-1px);
  box-shadow:0 12px 26px rgba(37,99,235,0.12);
}
.prod-text{ display:block; min-width:0; flex:1; }
.prod-sku{ display:block; font-weight:900; line-height:1.15; word-break:break-word; }
.prod-name{ display:block; font-weight:650; color:rgba(15,23,42,.85); line-height:1.15; margin-top:2px; word-break:break-word; }
.prod-hint{ display:block; font-weight:650; color:var(--muted); margin-top:2px; line-height:1.15; }

/* IVA badge (IVA se calcula automático) */
.iva-badge{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  min-width:56px;
  padding:8px 10px;
  border-radius:999px;
  border:1px solid rgba(37,99,235,.18);
  background:rgba(234,242,255,.9);
  color:rgba(37,99,235,.95);
  font-weight:900;
  height:38px;
}

/* Totales */
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

/* Buttons */
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

.tip{ margin-top:10px; color:#475569; font-size:.86rem; }

/* Inputs Cliente/Producto grandes */
.inv-aside .dropdown, .inv-main .dropdown{ width:100% !important; }
#search-client-inv, #buscarProductoInv{
  width:100% !important;
  height:48px !important;
  font-size:1rem !important;
  padding:.80rem .95rem !important;
  border-radius:16px !important;
}
#search-client-inv::placeholder, #buscarProductoInv::placeholder{ font-weight:700; }

/* =========================
   ✅ INFO PAGO en Resumen
   ========================= */
.pay-grid{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap:12px;
}
@media (max-width: 991.98px){
  .pay-grid{ grid-template-columns: 1fr; }
}
.pay-label{
  font-weight:900;
  font-size:.86rem;
  margin-bottom:6px;
  color:#0b1220;
}
.pay-row{ margin-bottom:10px; }

.seg{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
}
.seg button{
  border-radius:999px;
  border:1px solid rgba(37,99,235,.20);
  background:rgba(255,255,255,.75);
  padding:10px 14px;
  font-weight:900;
  color:#0b1220;
  transition:transform .12s var(--ease), box-shadow .12s var(--ease), background-color .12s var(--ease);
}
.seg button:hover{
  transform:translateY(-1px);
  box-shadow:0 12px 24px rgba(37,99,235,.12);
}
.seg button.is-active{
  background:rgba(37,99,235,.95);
  color:#fff;
  border-color:rgba(37,99,235,.30);
}
</style>

<div class="container" >

  <div class="inv-topbar">
    <div>
      <h2 class="inv-title">{{ $isEdit ? 'Editar factura' : 'Nueva factura' }}</h2>
      <div class="inv-sub">Captura rápida · selecciona cliente, agrega productos y guarda borrador</div>
    </div>

    <a href="{{ route('manual_invoices.index') }}" class="btn btn-soft">
      <i class="fa-solid fa-arrow-left"></i> Volver
    </a>
  </div>

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
                class="form-control modern-input"
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

        {{-- Notas internas (ya las tienes) --}}
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
                class="form-control modern-input"
                placeholder="Buscar producto por SKU o nombre..."
                autocomplete="off"
              >

              <ul class="dropdown-menu modern-dropdown w-100" id="dropdownProductosInv">
                @foreach($products as $p)
                  @php
                    $price  = $p->price ?? $p->market_price ?? $p->bid_price ?? 0;
                    $label  = trim(($p->sku ?? '').' — '.($p->name ?? ''));
                    $search = Str::of(($p->sku ?? '').' '.$label)->lower()->ascii();
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
                        <div class="dd-title">{{ strtoupper($p->sku ?? '') }} — {{ strtoupper(Str::limit($p->name ?? '', 58)) }}</div>
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
              Tip: selecciona un producto para agregarlo. Para cambiar uno existente, haz clic en el “Producto” dentro de la tabla.
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
                    <th style="width:34%;">Producto</th>
                    <th style="width:24%;">Descripción</th>
                    <th style="width:8%;">Cant.</th>
                    <th style="width:9%;">P. unit.</th>
                    <th style="width:8%;">Desc.</th>
                    <th style="width:6%;">IVA</th>
                    <th style="width:9%;">Total</th>
                    <th style="width:2%;">Acción</th>
                  </tr>
                </thead>
                <tbody id="itemsTbody">
                  @foreach($rows as $i => $row)
                    @php
                      $sku = null; $name = null;
                      if (!empty($row['product_id'])) {
                        $pp = $products->firstWhere('id', (int)$row['product_id']);
                        if ($pp) { $sku = strtoupper($pp->sku ?? ''); $name = ($pp->name ?? ''); }
                      }
                      $rowTax = isset($row['tax_rate']) ? (float)$row['tax_rate'] : 16;
                      if ($rowTax === 0.0) $rowTax = 16; // ✅ seguridad: IVA por default
                    @endphp
                    <tr data-idx="{{ $i }}">
                      @if(!empty($row['id']))
                        <input type="hidden" name="items[{{ $i }}][id]" value="{{ $row['id'] }}">
                      @endif

                      <td>
                        <div class="prod-cell">
                          <button type="button" class="prod-name-btn js-change-label" title="Cambiar / elegir producto">
                            <i class="fa-solid fa-magnifying-glass" style="color:var(--accent-2);margin-top:2px;"></i>
                            <span class="prod-text">
                              @if($sku)
                                <span class="prod-sku">{{ $sku }}</span>
                                <span class="prod-name">{{ $name }}</span>
                              @else
                                <span class="prod-sku">Selecciona producto</span>
                                <span class="prod-hint">Busca por SKU o nombre</span>
                              @endif
                            </span>
                          </button>
                        </div>

                        <input type="hidden" class="js-product-id" name="items[{{ $i }}][product_id]" value="{{ $row['product_id'] ?? '' }}">
                        <input type="hidden" name="items[{{ $i }}][unit]" value="{{ $row['unit'] ?? '' }}">
                        <input type="hidden" name="items[{{ $i }}][unit_code]" value="{{ $row['unit_code'] ?? '' }}">
                        <input type="hidden" name="items[{{ $i }}][product_key]" value="{{ $row['product_key'] ?? '' }}">
                      </td>

                      <td>
                        <textarea rows="2" class="form-control js-desc"
                                  name="items[{{ $i }}][description]"
                                  placeholder="Descripción" required>{{ $row['description'] ?? '' }}</textarea>
                      </td>

                      <td><input type="number" step="0.001" min="0.001" class="form-control js-qty" name="items[{{ $i }}][quantity]" value="{{ $row['quantity'] ?? 1 }}"></td>
                      <td><input type="number" step="0.01" min="0" class="form-control js-price" name="items[{{ $i }}][unit_price]" value="{{ $row['unit_price'] ?? 0 }}"></td>
                      <td><input type="number" step="0.01" min="0" class="form-control js-discount" name="items[{{ $i }}][discount]" value="{{ $row['discount'] ?? 0 }}"></td>

                      {{-- ✅ IVA calculado automático: oculto (para enviar) + badge visible --}}
                      <td class="text-center">
                        <input type="hidden" class="js-tax" name="items[{{ $i }}][tax_rate]" value="{{ $rowTax }}">
                        <span class="iva-badge"><span class="js-tax-badge">{{ rtrim(rtrim(number_format($rowTax,2,'.',''), '0'), '.') }}</span>%</span>
                      </td>

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

        {{-- Resumen + Información del pago --}}
        <div class="card modern-card w-100">
          <div class="card-header modern-header">
            <i class="fa-solid fa-receipt me-2" style="color:var(--accent-2)"></i> Resumen
          </div>
          <div class="card-body">

            {{-- ✅ Información del pago (como tu captura) --}}
            <div style="font-weight:950;font-size:.98rem;margin-bottom:10px;">
              Información del pago
            </div>

            <div class="pay-grid">
              <div class="pay-row">
                <div class="pay-label">Moneda de pago</div>
                <select name="pay_currency" class="form-control modern-select">
                  @foreach($monedas as $k => $v)
                    <option value="{{ $k }}" {{ $payCurrency === $k ? 'selected' : '' }}>{{ $v }}</option>
                  @endforeach
                </select>
              </div>

              <div class="pay-row">
                <div class="pay-label">Tipo de cambio</div>
                <input type="number" step="0.000001" min="0" class="form-control modern-input"
                       name="exchange_rate" value="{{ $exchangeRate }}">
              </div>
            </div>

            <div class="pay-row" style="margin-top:2px;">
              <div class="pay-label">Método de pago</div>

              <input type="hidden" name="payment_method" id="payment_method" value="{{ $paymentMethod }}">

              <div class="seg">
                <button type="button" class="js-paymethod {{ $paymentMethod === 'PUE' ? 'is-active' : '' }}" data-val="PUE">
                  De contado
                </button>
                <button type="button" class="js-paymethod {{ $paymentMethod === 'PPD' ? 'is-active' : '' }}" data-val="PPD">
                  Parcialidades o diferidos
                </button>
              </div>
            </div>

            <div class="pay-row">
              <div class="pay-label">Forma de pago</div>
              <select name="payment_form" class="form-control modern-select">
                @foreach($formasPago as $k => $v)
                  <option value="{{ $k }}" {{ $paymentForm === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
              </select>
            </div>

            <div class="pay-row">
              <div class="pay-label">Uso de CFDI</div>
              <select name="cfdi_use" class="form-control modern-select">
                @foreach($usosCfdi as $k => $v)
                  <option value="{{ $k }}" {{ $cfdiUse === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
              </select>
            </div>

            <div class="pay-row" style="margin-bottom:16px;">
              <div class="pay-label">Tipo de exportación</div>
              <select name="exportation" class="form-control modern-select">
                @foreach($exportaciones as $k => $v)
                  <option value="{{ $k }}" {{ $exportation === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
              </select>
            </div>

            {{-- Totales (IVA ya se calcula automático) --}}
            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px dashed rgba(148,163,184,.35)">
              <div class="text-muted fw-bold">Subtotal</div>
              <div class="fw-black">$<span id="sum_sub">0.00</span></div>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px dashed rgba(148,163,184,.35)">
              <div class="text-muted fw-bold">Descuento</div>
              <div class="fw-black">$<span id="sum_disc">0.00</span></div>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px dashed rgba(148,163,184,.35)">
              <div class="text-muted fw-bold">Impuestos (IVA)</div>
              <div class="fw-black">$<span id="sum_tax">0.00</span></div>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-3">
              <div style="font-weight:950;font-size:1.05rem;">Total</div>
              <div style="font-weight:950;font-size:1.15rem;">$<span id="sum_total">0.00</span></div>
            </div>

            <hr style="border-color:rgba(148,163,184,.25);margin:14px 0 12px">

            <div class="d-flex justify-content-end gap-2 flex-wrap">
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

  const escapeHtml = (str) => (str ?? '').toString()
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#039;');

  function buildProdHTML(label){
    const raw = (label || '').toString().trim();
    const parts = raw.split(' — ');
    const sku = (parts[0] || '').trim();
    const name = (parts.slice(1).join(' — ') || '').trim();

    if (!sku && !name) {
      return `
        <i class="fa-solid fa-magnifying-glass" style="color:var(--accent-2);margin-top:2px;"></i>
        <span class="prod-text">
          <span class="prod-sku">Selecciona producto</span>
          <span class="prod-hint">Busca por SKU o nombre</span>
        </span>
      `;
    }

    return `
      <i class="fa-solid fa-magnifying-glass" style="color:var(--accent-2);margin-top:2px;"></i>
      <span class="prod-text">
        <span class="prod-sku">${escapeHtml(sku || 'SKU')}</span>
        <span class="prod-name">${escapeHtml(name || '')}</span>
      </span>
    `;
  }

  /* =========================
     ✅ DROPDOWN: PORTAL AL BODY + FIXED REAL
     ========================= */
  function portalize(menu){
    if (!menu || menu.dataset.portalized === '1') return;

    const ph = document.createComment('dropdown-placeholder');
    menu.__placeholder = ph;

    if (menu.parentNode) menu.parentNode.insertBefore(ph, menu);
    document.body.appendChild(menu);

    menu.dataset.portalized = '1';
    menu.style.position = 'fixed';
    menu.style.zIndex = '99999';
  }

  function placeMenuUnderInput(input, menu){
    if (!input || !menu) return;

    const r = input.getBoundingClientRect();
    const gap = 8;

    const vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
    const vh = Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0);

    let left = Math.max(10, r.left);
    let width = Math.min(r.width, vw - left - 10);
    if (left + width > vw - 10) left = Math.max(10, vw - width - 10);

    let top = r.bottom + gap;

    const estimatedHeight = Math.min(340, vh - 20);
    if (top + 180 > vh - 10) {
      const up = r.top - gap - estimatedHeight;
      if (up > 10) top = up;
    }

    menu.style.left = left + 'px';
    menu.style.top = Math.max(10, Math.min(top, vh - 60)) + 'px';
    menu.style.width = width + 'px';
    menu.style.maxHeight = Math.max(180, vh - (parseFloat(menu.style.top) || top) - 16) + 'px';
  }

  function openDropdown(input, menu){
    if (!input || !menu) return;
    portalize(menu);
    placeMenuUnderInput(input, menu);
    menu.classList.add('show');
    menu.style.display = 'block';
  }

  function closeDropdown(menu){
    if (!menu) return;
    menu.classList.remove('show');
    menu.style.display = 'none';
  }

  function closeOnOutside(input, menu){
    document.addEventListener('mousedown', (e) => {
      if (!menu || menu.style.display === 'none') return;
      if (menu.contains(e.target) || input === e.target) return;
      closeDropdown(menu);
    });
  }

  function attachReposition(input, menu){
    const handler = () => {
      if (!menu || menu.style.display === 'none') return;
      placeMenuUnderInput(input, menu);
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
        <div style="font-weight:950;">${escapeHtml(nombre || 'CLIENTE')}</div>
        ${rfc ? `<div class="text-muted" style="font-weight:800;">RFC: ${escapeHtml(rfc)}</div>` : ``}
        <div class="text-muted">Tel: ${escapeHtml(tel || 'No registrado')}</div>
        <div class="text-muted">Email: ${escapeHtml(email || 'No registrado')}</div>
        <div class="text-muted">Dirección: ${escapeHtml(dir || 'No registrado')}</div>
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
  let activeRow = null;

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
     ✅ TABLA ITEMS (IVA automático)
     ========================= */
  const tbody   = $('itemsTbody');
  const sumSub  = $('sum_sub');
  const sumDisc = $('sum_disc');
  const sumTax  = $('sum_tax');
  const sumTot  = $('sum_total');

  function recalc(){
    let sub = 0, disc = 0, tax = 0, tot = 0;

    tbody.querySelectorAll('tr').forEach(tr => {
      const qty   = parseFloat(tr.querySelector('.js-qty')?.value || '0') || 0;
      const price = parseFloat(tr.querySelector('.js-price')?.value || '0') || 0;
      const d     = parseFloat(tr.querySelector('.js-discount')?.value || '0') || 0;

      // ✅ IVA auto: siempre toma tax_rate del hidden .js-tax
      let t = parseFloat(tr.querySelector('.js-tax')?.value || '16') || 16;

      // seguridad
      if (t < 0) t = 0;
      if (t > 99) t = 99;

      // badge
      const badge = tr.querySelector('.js-tax-badge');
      if (badge) badge.textContent = (Math.round(t * 100) / 100).toString().replace(/\.0+$/,'');

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
      tr.querySelectorAll('input[name^="items["], textarea[name^="items["]').forEach(inp => {
        inp.name = inp.name.replace(/items\[\d+]/, 'items['+idx+']');
      });
    });
  }

  function addRow(data = {}){
    const idx = tbody.querySelectorAll('tr').length;

    const tr = document.createElement('tr');
    tr.dataset.idx = idx;

    const label = data.label || '';
    const taxRate = (data.tax_rate ?? 16);

    tr.innerHTML = `
      <td>
        <div class="prod-cell">
          <button type="button" class="prod-name-btn js-change-label" title="Cambiar / elegir producto">
            ${buildProdHTML(label)}
          </button>
        </div>

        <input type="hidden" class="js-product-id" name="items[${idx}][product_id]" value="${escapeHtml(data.product_id || '')}">
        <input type="hidden" name="items[${idx}][unit]" value="${escapeHtml(data.unit || '')}">
        <input type="hidden" name="items[${idx}][unit_code]" value="${escapeHtml(data.unit_code || '')}">
        <input type="hidden" name="items[${idx}][product_key]" value="${escapeHtml(data.product_key || '')}">
      </td>

      <td>
        <textarea rows="2" class="form-control js-desc" name="items[${idx}][description]" placeholder="Descripción" required>${escapeHtml(data.description || '')}</textarea>
      </td>

      <td><input type="number" step="0.001" min="0.001" class="form-control js-qty" name="items[${idx}][quantity]" value="${data.quantity ?? 1}"></td>
      <td><input type="number" step="0.01" min="0" class="form-control js-price" name="items[${idx}][unit_price]" value="${data.unit_price ?? 0}"></td>
      <td><input type="number" step="0.01" min="0" class="form-control js-discount" name="items[${idx}][discount]" value="${data.discount ?? 0}"></td>

      <td class="text-center">
        <input type="hidden" class="js-tax" name="items[${idx}][tax_rate]" value="${taxRate}">
        <span class="iva-badge"><span class="js-tax-badge">${taxRate}</span>%</span>
      </td>

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
    const btn = tr.querySelector('.js-change-label');
    if (btn) btn.innerHTML = buildProdHTML(p.label || '');

    tr.querySelector('.js-product-id').value = p.id || '';

    const unit = tr.querySelector('input[name*="[unit]"]');
    const unitCode = tr.querySelector('input[name*="[unit_code]"]');
    const pkey = tr.querySelector('input[name*="[product_key]"]');

    if (unit) unit.value = p.unit || '';
    if (unitCode) unitCode.value = p.unit_code || '';
    if (pkey) pkey.value = p.product_key || '';

    const priceInp = tr.querySelector('.js-price');
    if (priceInp) priceInp.value = Number(p.price || 0).toFixed(2);

    // ✅ descripción: si está vacía, la rellenamos con el nombre para que se vea
    const desc = tr.querySelector('textarea[name*="[description]"]');
    if (desc && !desc.value.trim()) desc.value = p.label || '';

    // ✅ IVA automático: por defecto 16
    const tax = tr.querySelector('.js-tax');
    if (tax && (!tax.value || Number(tax.value) === 0)) tax.value = 16;

    recalc();
  }

  tbody.addEventListener('input', (e) => {
    if (e.target.matches('.js-qty,.js-price,.js-discount')) recalc();
  });

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

    if (e.target.closest('.js-change-label')) {
      activeRow = tr;
      prodInput.focus();
      filterProducts();
      return;
    }
  });

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

  /* =========================
     ✅ Método de pago (botones)
     ========================= */
  const pmHidden = $('payment_method');
  document.querySelectorAll('.js-paymethod').forEach(b => {
    b.addEventListener('click', () => {
      document.querySelectorAll('.js-paymethod').forEach(x => x.classList.remove('is-active'));
      b.classList.add('is-active');
      if (pmHidden) pmHidden.value = b.dataset.val || 'PUE';
    });
  });

  // init
  recalc();
  closeDropdown(clientMenu);
  closeDropdown(prodMenu);

  // si no hay filas (por alguna razón), crea una
  if (!tbody.querySelector('tr')) addRow();
})();
</script>
@endsection
