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
@import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

:root {
  --bg: #f9fafb;
  --card: #ffffff;
  --ink: #333333;
  --title: #111111;
  --muted: #888888;
  --line: #ebebeb;
  --blue: #007aff;
  --blue-soft: #e6f0ff;
  --success: #15803d;
  --success-soft: #e6ffe6;
  --danger: #ff4a4a;
  --danger-soft: #ffebeb;

  /* Alias para conservar tus iconos/estilos inline existentes sin tocar lógica */
  --accent: var(--blue);
  --accent-2: var(--blue);
  --soft-blue: var(--blue-soft);
  --soft-blue-2: var(--blue-soft);
  --soft-blue-ink: var(--blue);
  --soft-red: var(--danger-soft);
  --soft-red-ink: var(--danger);
  --card-border: var(--line);
  --radius: 10px;
  --radius-sm: 7px;
  --control-h: 36px;
  --ease: cubic-bezier(.22,1,.36,1);
}

* { box-sizing: border-box; }

html,
body {
  overflow-x: hidden;
}

body {
  margin: 0;
  background: var(--bg);
  color: var(--ink);
  font-family: 'Quicksand', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  font-weight: 500;
  -webkit-font-smoothing: antialiased;
  text-rendering: geometricPrecision;
}

input,
button,
select,
textarea,
.badge,
.alert,
.btn,
.form-control,
.modal,
.modal-title,
.swal2-popup.custom-swal {
  font-family: 'Quicksand', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

button,
a,
input,
select,
textarea {
  -webkit-tap-highlight-color: transparent;
}

.container {
  width: 100%;
  max-width: 1280px;
  margin: 0 auto;
  padding: 18px 18px 32px;
}

/* Utilidades mínimas para no depender visualmente de Bootstrap */
.d-flex { display: flex !important; }
.justify-content-between { justify-content: space-between !important; }
.justify-content-end { justify-content: flex-end !important; }
.align-items-center { align-items: center !important; }
.flex-wrap { flex-wrap: wrap !important; }
.gap-2 { gap: 10px !important; }
.text-muted { color: var(--muted) !important; }
.text-danger { color: var(--danger) !important; }
.text-center { text-align: center !important; }
.text-end { text-align: right !important; }
.d-block { display: block !important; }
.mt-2 { margin-top: 8px !important; }
.mt-3 { margin-top: 14px !important; }
.me-2 { margin-right: 8px !important; }
.py-2 { padding-top: 10px !important; padding-bottom: 10px !important; }
.pt-3 { padding-top: 18px !important; }
.w-100 { width: 100% !important; }
.fw-bold { font-weight: 700 !important; }
.fw-black { font-weight: 700 !important; }

/* Header */
.inv-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  padding: 4px 2px 16px;
}

.inv-title {
  margin: 0;
  color: var(--title);
  font-size: clamp(1.25rem, 1.8vw, 1.62rem);
  line-height: 1.08;
  font-weight: 700;
  letter-spacing: -0.035em;
}

.inv-sub {
  margin-top: 5px;
  color: var(--muted);
  font-size: .82rem;
  line-height: 1.45;
  font-weight: 600;
}

/* Layout */
.inv-grid {
  display: grid !important;
  grid-template-columns: minmax(260px, 305px) minmax(0, 1fr);
  gap: 8px;
  align-items: start;
}

.inv-aside,
.inv-main {
  width: auto !important;
  max-width: none !important;
  flex: none !important;
  min-width: 0;
}

@media (min-width: 992px) {
  .inv-aside {
    position: sticky;
    top: 90px;
    align-self: start;
  }
}

.inv-aside .modern-card,
.inv-main .modern-card {
  margin-bottom: 12px !important;
}

.inv-aside .modern-card:last-child,
.inv-main .modern-card:last-child {
  margin-bottom: 0 !important;
}

/* Cards */
.modern-card {
  position: relative;
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius);
  box-shadow: 0 2px 8px rgba(0,0,0,0.018);
  overflow: visible;
  z-index: 1;
  transition: transform .18s var(--ease), box-shadow .18s var(--ease), border-color .18s var(--ease);
}

.modern-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 18px rgba(0,0,0,0.04);
  border-color: #e4e4e4;
}

.modern-card:focus-within {
  z-index: 60;
  border-color: #dedede;
}

.modern-card .card-header.modern-header {
  display: flex;
  align-items: center;
  min-height: 44px;
  padding: 12px 16px !important;
  border-bottom: 1px solid var(--line);
  background: var(--card);
  color: var(--title);
  font-size: .78rem;
  font-weight: 700;
  letter-spacing: -0.015em;
  border-radius: var(--radius) var(--radius) 0 0;
}

.modern-card .card-header.modern-header i {
  color: var(--blue) !important;
}

.modern-card .card-body {
  padding: 16px !important;
}

/* Inputs / Selects */
.form-control,
.modern-input,
.modern-select,
.modern-textarea {
  width: 100%;
  min-height: var(--control-h);
  border-radius: var(--radius-sm);
  border: 1px solid var(--line);
  background: #ffffff;
  color: var(--ink);
  font-size: .8rem;
  font-weight: 600;
  line-height: 1.35;
  padding: 8px 10px;
  outline: none;
  box-shadow: none;
  transition: border-color .16s var(--ease), box-shadow .16s var(--ease), background-color .16s var(--ease), transform .12s var(--ease);
}

.form-control::placeholder,
.modern-input::placeholder,
.modern-textarea::placeholder {
  color: #a6a6a6;
  font-weight: 600;
}

.form-control:focus,
.modern-input:focus,
.modern-select:focus,
.modern-textarea:focus {
  border-color: var(--blue);
  box-shadow: 0 0 0 3px var(--blue-soft);
  background: #ffffff;
}

.form-control[readonly],
.modern-textarea[readonly] {
  color: var(--muted);
  background: #fbfbfc;
  cursor: default;
}

.modern-textarea,
textarea.form-control {
  min-height: 78px;
  resize: vertical;
}

.modern-select {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  padding-right: 34px;
  background-color: #ffffff;
  background-image:
    linear-gradient(45deg, transparent 50%, var(--muted) 50%),
    linear-gradient(135deg, var(--muted) 50%, transparent 50%);
  background-position:
    calc(100% - 17px) calc(50% - 3px),
    calc(100% - 11px) calc(50% - 3px);
  background-size: 6px 6px, 6px 6px;
  background-repeat: no-repeat;
}

.modern-select::-ms-expand { display: none; }

#search-client-inv,
#buscarProductoInv {
  height: 38px !important;
  font-size: .86rem !important;
  border-radius: var(--radius-sm) !important;
  padding: 8px 10px !important;
}

.inv-aside .dropdown,
.inv-main .dropdown {
  position: relative;
  width: 100% !important;
}

/* Dropdown */
.modern-dropdown,
.dropdown-menu.modern-dropdown {
  display: none;
  position: fixed !important;
  transform: none !important;
  inset: auto auto auto auto;
  z-index: 99999 !important;
  width: 100%;
  max-height: 245px;
  overflow-y: auto;
  overflow-x: hidden;
  margin: 6px 0 0;
  padding: 5px;
  list-style: none;
  background: #ffffff;
  border: 1px solid var(--line);
  border-radius: 8px;
  box-shadow: 0 10px 28px rgba(0,0,0,0.065);
}

.dropdown-divider {
  height: 1px;
  margin: 8px 4px;
  border: 0;
  background: var(--line);
}

.dd-item,
.dd-link {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  min-height: 38px;
  margin: 1px 0;
  padding: 7px 8px;
  border-radius: 7px;
  border: 1px solid transparent;
  background: transparent;
  color: var(--ink);
  text-align: left;
  text-decoration: none;
  cursor: pointer;
  transition: background-color .16s var(--ease), border-color .16s var(--ease), transform .12s var(--ease);
}

.dd-item:hover,
.dd-link:hover {
  background: #f9fafb;
  border-color: var(--line);
  transform: translateY(-1px);
}

.dd-title {
  color: var(--title);
  font-size: .76rem;
  line-height: 1.25;
  font-weight: 700;
  letter-spacing: -0.01em;
}

.dd-sub {
  margin-top: 2px;
  color: var(--muted);
  font-size: .70rem;
  line-height: 1.25;
  font-weight: 600;
}

.dd-right {
  display: flex;
  align-items: center;
  gap: 7px;
  flex: 0 0 auto;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.dd-pill,
.badge,
.iva-badge,
.line-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  font-size: .72rem;
  font-weight: 700;
  white-space: nowrap;
}

.dd-pill {
  padding: 4px 8px;
  background: var(--blue-soft);
  color: var(--blue);
  border: 1px solid #d9e8ff;
}

.dd-link .fa-plus {
  color: var(--blue) !important;
}

/* Tips */
.tip {
  margin-top: 8px;
  padding: 9px 10px;
  border-radius: 8px;
  background: #f9fafb;
  border: 1px solid var(--line);
  color: var(--muted);
  font-size: .8rem;
  line-height: 1.45;
  font-weight: 600;
}

/* Tabla / conceptos sin scroll horizontal */
.table-responsive {
  width: 100%;
  overflow: visible;
  border: 0;
  border-radius: 0;
  background: #ffffff;
}

.modern-table {
  width: 100%;
  min-width: 0;
  margin: 0;
  border-collapse: collapse;
  border-spacing: 0;
  table-layout: fixed;
  color: var(--ink);
  font-size: .78rem;
}

.modern-table thead th {
  position: static;
  top: auto;
  z-index: auto;
  padding: 10px 8px 12px;
  background: #ffffff;
  color: var(--title);
  border: 0;
  border-bottom: 1px solid var(--line);
  font-size: .82rem;
  font-weight: 700;
  letter-spacing: -0.01em;
  text-transform: none;
  white-space: nowrap;
}

.modern-table tbody td {
  padding: 12px 8px;
  vertical-align: middle;
  border: 0;
  border-bottom: 1px solid var(--line);
  color: var(--ink);
  overflow: hidden;
}

.modern-table tbody tr:last-child td {
  border-bottom: 0;
}

.modern-table tbody tr {
  background: #ffffff;
  transition: background-color .16s var(--ease);
}

.modern-table tbody tr:hover {
  background: #fcfcfd;
}

.modern-table input[type='number'],
.modern-table input[type='text'],
.modern-table textarea.form-control,
.modern-table .form-control {
  width: 100%;
  min-height: 32px;
  height: 32px;
  padding: 6px 8px;
  border-radius: 7px;
  border: 1px solid var(--line);
  background: #ffffff;
  color: var(--ink);
  box-shadow: none;
  font-size: .78rem;
  font-weight: 600;
  transition: border-color .16s var(--ease), box-shadow .16s var(--ease);
}

.modern-table textarea.form-control {
  height: auto;
  min-height: 42px;
  resize: vertical;
  line-height: 1.32;
}

.modern-table input:focus,
.modern-table textarea:focus,
.modern-table .form-control:focus {
  outline: none;
  border-color: var(--blue);
  box-shadow: 0 0 0 3px var(--blue-soft);
}

/* Producto dentro de tabla */
.prod-cell { min-width: 0; }

.prod-name-btn {
  width: 100%;
  display: flex;
  align-items: flex-start;
  gap: 7px;
  padding: 4px 0;
  border-radius: 6px;
  border: 1px solid transparent;
  background: transparent;
  color: var(--ink);
  text-align: left;
  cursor: pointer;
  transition: background-color .16s var(--ease), border-color .16s var(--ease), transform .12s var(--ease), box-shadow .16s var(--ease);
}

.prod-name-btn i {
  color: var(--blue) !important;
}

.prod-name-btn:hover {
  background: #f9fafb;
  border-color: transparent;
  transform: none;
  box-shadow: none;
}

.prod-name-btn:active {
  transform: scale(.98);
}

.prod-text {
  display: block;
  min-width: 0;
  flex: 1;
}

.prod-sku {
  display: block;
  color: var(--title);
  font-weight: 700;
  line-height: 1.2;
  word-break: break-word;
}

.prod-name,
.prod-hint {
  display: block;
  margin-top: 3px;
  color: var(--muted);
  font-size: .74rem;
  font-weight: 600;
  line-height: 1.25;
  word-break: break-word;
}

/* Badges */
.iva-badge {
  min-width: 42px;
  min-height: 28px;
  padding: 5px 7px;
  background: var(--blue-soft);
  color: var(--blue);
  border: 1px solid #d9e8ff;
}

.line-pill {
  min-width: 0;
  width: 100%;
  min-height: 28px;
  justify-content: flex-end;
  padding: 5px 8px;
  background: var(--success-soft);
  color: var(--success);
  border: 1px solid #d8f5d8;
  text-align: right;
}

/* Botones */
.btn,
.btn-soft,
.btn-soft-danger,
.seg button {
  border-radius: 8px !important;
  border: 0;
  text-decoration: none;
  cursor: pointer;
  user-select: none;
  transition: background-color .16s var(--ease), color .16s var(--ease), border-color .16s var(--ease), transform .12s var(--ease), box-shadow .16s var(--ease);
}

.btn-soft {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 8px !important;
  min-height: 36px;
  padding: 8px 12px !important;
  border: 1px solid var(--blue) !important;
  background: #ffffff !important;
  color: var(--blue) !important;
  font-size: .82rem !important;
  font-weight: 700 !important;
  box-shadow: none;
}

.btn-soft:hover {
  background: var(--blue-soft) !important;
  transform: translateY(-1px);
}

.btn-soft:active,
.btn-soft-danger:active,
.seg button:active {
  transform: scale(.98);
}

#btnSaveInv {
  min-width: 160px;
  border: 0 !important;
  background: var(--blue) !important;
  color: #ffffff !important;
  box-shadow: 0 8px 18px rgba(0,122,255,.16);
}

#btnSaveInv:hover {
  background: #006ee6 !important;
  box-shadow: 0 12px 24px rgba(0,122,255,.18);
}

.btn-soft-danger,
.icon-btn-danger {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  width: 34px;
  height: 34px;
  padding: 0 !important;
  border: 1px solid var(--danger-soft) !important;
  background: var(--danger-soft) !important;
  color: var(--danger) !important;
  font-weight: 700 !important;
}

.btn-soft-danger:hover,
.icon-btn-danger:hover {
  background: #ffe1e1 !important;
  transform: translateY(-1px);
}

/* Pago / Resumen */
.pay-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
}

.pay-row {
  margin-bottom: 10px;
}

.pay-label {
  margin-bottom: 6px;
  color: var(--title);
  font-size: .8rem;
  font-weight: 700;
  letter-spacing: -0.01em;
}

.seg {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.seg button {
  min-height: 34px;
  padding: 7px 11px;
  border: 1px solid var(--line);
  background: transparent;
  color: #555555;
  font-size: .8rem;
  font-weight: 700;
}

.seg button:hover {
  background: #f9fafb;
  transform: translateY(-1px);
}

.seg button.is-active {
  border-color: var(--blue);
  background: var(--blue);
  color: #ffffff;
  box-shadow: 0 8px 18px rgba(0,122,255,.14);
}

/* Totales */
#sum_sub,
#sum_disc,
#sum_tax,
#sum_total {
  color: var(--title);
  font-weight: 700;
}

hr {
  border: 0;
  border-top: 1px solid var(--line) !important;
}

/* Estados / errores */
.alert {
  border-radius: 9px;
  border: 1px solid var(--line);
  background: #ffffff;
  color: var(--ink);
}

/* Responsive */
@media (max-width: 991.98px) {
  .container {
    padding: 16px 12px 28px;
  }

  .inv-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .inv-topbar {
    align-items: flex-start;
    flex-direction: column;
    padding-bottom: 12px;
  }

  .pay-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 575.98px) {
  .modern-card .card-header.modern-header,
  .modern-card .card-body {
    padding-left: 12px !important;
    padding-right: 12px !important;
  }

  .inv-title {
    font-size: 1.25rem;
  }

  .btn-soft,
  #btnSaveInv {
    width: 100%;
  }

  .d-flex.justify-content-end {
    width: 100%;
  }
}


/* Ajuste SaaS compacto */
label, .form-label {
  margin-bottom: 6px;
  color: var(--title);
  font-size: .78rem;
  font-weight: 700;
  letter-spacing: -0.005em;
}

.small, small {
  font-size: .72rem;
  line-height: 1.35;
}

.modern-card .card-header.modern-header i,
.btn-soft i,
.dd-link i {
  font-size: .88em;
}

.modern-card {
  isolation: isolate;
}

.modern-card:hover {
  transform: translateY(-1px);
}

.form-control:hover,
.modern-input:hover,
.modern-select:hover,
.modern-textarea:hover {
  border-color: #dedede;
}

.modern-table thead th:first-child { border-top-left-radius: 10px; }
.modern-table thead th:last-child { border-top-right-radius: 10px; }

.inv-main .modern-card .card-body > .d-flex,
.inv-aside .modern-card .card-body > .d-flex {
  gap: 8px !important;
}

/* Conceptos: versión compacta SaaS sin barra horizontal */
#itemsTable .prod-name-btn {
  min-height: 42px;
}

#itemsTable .prod-sku,
#itemsTable .prod-name,
#itemsTable .prod-hint {
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
}

#itemsTable .prod-sku {
  white-space: nowrap;
}

#itemsTable .prod-name,
#itemsTable .prod-hint {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
}

#itemsTable .icon-btn-danger {
  width: 32px;
  height: 32px;
  min-width: 32px;
  padding: 0 !important;
}

@media (max-width: 1180px) {
  .modern-table { font-size: .76rem; }
  .modern-table thead th { padding: 8px 6px; font-size: .66rem; }
  .modern-table tbody td { padding: 8px 6px; }
  .modern-table input[type='number'],
  .modern-table input[type='text'],
  .modern-table textarea.form-control,
  .modern-table .form-control {
    padding-left: 7px;
    padding-right: 7px;
    font-size: .75rem;
  }
  .iva-badge { min-width: 38px; padding-left: 6px; padding-right: 6px; }
  .line-pill { padding-left: 6px; padding-right: 6px; font-size: .72rem; }
}

@media (max-width: 920px) {
  .table-responsive {
    border: 0;
    background: transparent;
  }

  .modern-table,
  .modern-table thead,
  .modern-table tbody,
  .modern-table tr,
  .modern-table th,
  .modern-table td {
    display: block;
    width: 100% !important;
  }

  .modern-table thead { display: none; }

  .modern-table tbody tr {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
    gap: 10px;
    margin-bottom: 12px;
    padding: 12px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
  }

  .modern-table tbody td {
    padding: 0;
    border-top: 0;
    overflow: visible;
  }

  .modern-table tbody td:nth-child(1),
  .modern-table tbody td:nth-child(2) {
    grid-column: 1 / -1;
  }

  .modern-table tbody td::before {
    display: block;
    margin-bottom: 5px;
    color: var(--muted);
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .012em;
  }

  .modern-table tbody td:nth-child(1)::before { content: 'Producto'; }
  .modern-table tbody td:nth-child(2)::before { content: 'Descripción'; }
  .modern-table tbody td:nth-child(3)::before { content: 'Cant.'; }
  .modern-table tbody td:nth-child(4)::before { content: 'P. unit.'; }
  .modern-table tbody td:nth-child(5)::before { content: 'Desc.'; }
  .modern-table tbody td:nth-child(6)::before { content: 'IVA'; }
  .modern-table tbody td:nth-child(7)::before { content: 'Total'; }
  .modern-table tbody td:nth-child(8)::before { content: 'Acción'; }

  .modern-table tbody td.text-center,
  .modern-table tbody td.text-end {
    text-align: left !important;
  }

  .line-pill {
    justify-content: flex-start;
  }
}

@media (max-width: 560px) {
  .modern-table tbody tr {
    grid-template-columns: 1fr;
  }
}



/* ==========================================================
   TABLA DE CONCEPTOS - estilo SaaS limpio, sin scroll horizontal
   ========================================================== */
#itemsTable {
  border-collapse: collapse;
  border-spacing: 0;
  table-layout: fixed;
  font-size: .92rem;
}

#itemsTable thead th {
  position: static;
  padding: 14px 16px;
  background: #ffffff;
  color: var(--title);
  border-bottom: 1px solid #dfe3e8;
  font-size: .9rem;
  font-weight: 700;
  letter-spacing: 0;
  text-transform: none;
}

#itemsTable tbody td {
  padding: 15px 16px;
  vertical-align: middle;
  border-top: 0;
  border-bottom: 1px solid #e5e7eb;
  color: var(--title);
  overflow: visible;
}

#itemsTable tbody tr:last-child td {
  border-bottom: 0;
}

#itemsTable tbody tr:hover {
  background: #fbfcfd;
}

#itemsTable .prod-name-btn {
  min-height: 0;
  padding: 0;
  border: 0;
  border-radius: 8px;
  background: transparent;
  box-shadow: none;
}

#itemsTable .prod-name-btn:hover {
  background: #f9fafb;
  box-shadow: none;
  transform: none;
}

#itemsTable .prod-name-btn i {
  display: none;
}

#itemsTable .prod-text {
  min-width: 0;
}

#itemsTable .prod-sku {
  margin: 0;
  color: var(--title);
  font-size: .95rem;
  font-weight: 700;
  line-height: 1.25;
  white-space: normal;
}

#itemsTable .prod-name,
#itemsTable .prod-hint {
  margin-top: 3px;
  color: var(--muted);
  font-size: .78rem;
  font-weight: 600;
  line-height: 1.25;
  -webkit-line-clamp: 2;
}

#itemsTable textarea.form-control {
  min-height: 40px;
  height: 40px;
  padding: 8px 0;
  border: 0;
  border-radius: 0;
  background: transparent;
  color: var(--title);
  font-size: .88rem;
  font-weight: 600;
  resize: vertical;
}

#itemsTable textarea.form-control:focus {
  padding: 8px 10px;
  border: 1px solid var(--blue);
  border-radius: 8px;
  background: #ffffff;
}

#itemsTable input[type='number'] {
  height: 38px;
  min-height: 38px;
  padding: 7px 10px;
  border: 1px solid #dfe3e8;
  border-radius: 8px;
  background: #ffffff;
  color: var(--title);
  font-size: .88rem;
  font-weight: 700;
}

#itemsTable .js-qty {
  max-width: 92px;
}

#itemsTable .line-pill {
  width: auto;
  min-height: 0;
  justify-content: flex-end;
  padding: 0;
  border: 0;
  border-radius: 0;
  background: transparent;
  color: var(--title);
  font-size: .92rem;
  font-weight: 700;
}

#itemsTable .icon-btn-danger {
  width: 30px;
  height: 30px;
  min-width: 30px;
  border-radius: 8px !important;
}

#itemsTable .iva-badge,
#itemsTable .js-tax-badge {
  display: none !important;
}

@media (max-width: 1040px) {
  #itemsTable thead th,
  #itemsTable tbody td {
    padding-left: 10px;
    padding-right: 10px;
  }

  #itemsTable { font-size: .84rem; }
  #itemsTable .prod-sku { font-size: .86rem; }
  #itemsTable textarea.form-control,
  #itemsTable input[type='number'],
  #itemsTable .line-pill { font-size: .82rem; }
}

@media (max-width: 920px) {
  #itemsTable tbody tr {
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 12px;
  }

  #itemsTable tbody td:nth-child(1)::before { content: 'Producto'; }
  #itemsTable tbody td:nth-child(2)::before { content: 'Descripción'; }
  #itemsTable tbody td:nth-child(3)::before { content: 'Cantidad'; }
  #itemsTable tbody td:nth-child(4)::before { content: 'P. unit.'; }
  #itemsTable tbody td:nth-child(5)::before { content: 'Desc.'; }
  #itemsTable tbody td:nth-child(6)::before { content: 'Subtotal'; }
  #itemsTable tbody td:nth-child(7)::before { content: 'Acción'; }
}

</style>

<div class="container">

  <div class="inv-topbar">
    <div>
      <h2 class="inv-title">{{ $isEdit ? 'Editar factura' : 'Nueva factura' }}</h2>
      <div class="inv-sub">Captura rápida · selecciona cliente, agrega productos y guarda borrador</div>
    </div>

    <a href="{{ route('manual_invoices.index') }}" class="btn-soft">
      <i class="fa-solid fa-arrow-left"></i> Volver
    </a>
  </div>

  <form id="form-invoice"
        method="POST"
        action="{{ $isEdit ? route('manual_invoices.update', $invoice) : route('manual_invoices.store') }}">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="inv-grid">

      {{-- ======================= IZQUIERDA ======================= --}}
      <div class="inv-aside">

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
      <div class="inv-main">

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
                placeholder="Escribe mínimo 2 letras o SKU para buscar..."
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
                    <th style="width:28%;">Producto</th>
                    <th style="width:25%;">Descripción</th>
                    <th style="width:9%;">Cantidad</th>
                    <th style="width:11%;">P. unit.</th>
                    <th style="width:9%;">Desc.</th>
                    <th style="width:13%;">Subtotal</th>
                    <th style="width:5%;">Acción</th>
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

                      <td class="text-end">
                        {{-- IVA automático: se conserva oculto para enviarlo, sin mostrar columna --}}
                        <input type="hidden" class="js-tax" name="items[{{ $i }}][tax_rate]" value="{{ $rowTax }}">
                        <span class="line-pill js-line-total">$0.00</span>
                      </td>

                      <td class="text-center">
                        <button type="button" class="btn-soft-danger icon-btn-danger js-remove" title="Quitar">
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
            <div style="font-weight:700;font-size:1rem;margin-bottom:14px;color:var(--title);">
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

            {{-- Totales: el IVA se calcula automático y no se muestra como columna --}}
            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--line)">
              <div class="text-muted fw-bold">Subtotal</div>
              <div class="fw-black">$<span id="sum_sub">0.00</span></div>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--line)">
              <div class="text-muted fw-bold">Descuento</div>
              <div class="fw-black">$<span id="sum_disc">0.00</span></div>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--line)">
              <div class="text-muted fw-bold">Impuestos (IVA)</div>
              <div class="fw-black">$<span id="sum_tax">0.00</span></div>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-3">
              <div style="font-weight:700;font-size:1.05rem;color:var(--title);">Total</div>
              <div style="font-weight:700;font-size:1.18rem;color:var(--title);">$<span id="sum_total">0.00</span></div>
            </div>

            <hr style="border-color:var(--line);margin:16px 0 14px">

            <div class="d-flex justify-content-end gap-2 flex-wrap">
              <button type="submit" class="btn-soft" id="btnSaveInv">
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

    const estimatedHeight = Math.min(245, vh - 20);
    if (top + 180 > vh - 10) {
      const up = r.top - gap - estimatedHeight;
      if (up > 10) top = up;
    }

    menu.style.left = left + 'px';
    menu.style.top = Math.max(10, Math.min(top, vh - 60)) + 'px';
    menu.style.width = width + 'px';
    menu.style.maxHeight = Math.min(245, Math.max(160, vh - (parseFloat(menu.style.top) || top) - 16)) + 'px';
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

    // Evita que el dropdown abra enorme con todos los productos al solo enfocar.
    // Escribe mínimo 2 caracteres para mostrar coincidencias.
    if (q.length < 2) {
      prodMenu.querySelectorAll('.js-prod-li').forEach(li => li.style.display = 'none');
      closeDropdown(prodMenu);
      return;
    }

    let shown = 0;
    prodMenu.querySelectorAll('.js-prod-li').forEach(li => {
      const s = li.dataset.search || norm(li.textContent);
      const hasRealText = s.replace(/[—\s-]/g, '').length > 0;
      const match = hasRealText && s.includes(q);
      li.style.display = match ? '' : 'none';
      if (match) shown++;
    });

    if (shown > 0) openDropdown(prodInput, prodMenu);
    else closeDropdown(prodMenu);
  }

  prodInput.addEventListener('focus', filterProducts);
  prodInput.addEventListener('click', filterProducts);
  prodInput.addEventListener('input', filterProducts);
  closeOnOutside(prodInput, prodMenu);
  attachReposition(prodInput, prodMenu);

  /* =========================
     TABLA ITEMS (IVA automático oculto)
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

      // IVA auto: siempre toma tax_rate del hidden .js-tax
      let t = parseFloat(tr.querySelector('.js-tax')?.value || '16') || 16;

      // seguridad
      if (t < 0) t = 0;
      if (t > 99) t = 99;

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

      <td class="text-end">
        <input type="hidden" class="js-tax" name="items[${idx}][tax_rate]" value="${taxRate}">
        <span class="line-pill js-line-total">$0.00</span>
      </td>

      <td class="text-center">
        <button type="button" class="btn-soft-danger icon-btn-danger js-remove" title="Quitar">
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
      prodInput.select();
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
