
@extends('layouts.app')
@section('content_class', 'content--flush')
@section('content')
@php
  $company = [
    'name' => 'JURETO S.A. DE C.V.',
    'address' => 'BERNARDO VARA 25, COL. PILARES, C.P. 52179, METEPEC, ESTADO DE MEXICO.',
    'phone' => '5541937243, 8135515784',
    'email' => 'RTORT@JURETO.COM.MX',
    'rfc' => 'JUR2002196K4',
    'representative' => 'JUAN RENE TORT RODRIGUEZ',
    'representative_role' => 'REPRESENTANTE LEGAL DE JURETO SA DE CV',
  ];

  $logoFile = public_path('images/logo-mail.png');
  $logoExt = strtolower(pathinfo($logoFile, PATHINFO_EXTENSION));
  $logoMime = match ($logoExt) {
      'jpg', 'jpeg' => 'jpeg',
      'svg' => 'svg+xml',
      default => $logoExt ?: 'png',
  };
  $logoSrc = file_exists($logoFile)
      ? 'data:image/' . $logoMime . ';base64,' . base64_encode(file_get_contents($logoFile))
      : asset('images/logo-mail.png');

  $quoteDate = $createdAt instanceof \Carbon\CarbonInterface ? $createdAt : \Carbon\Carbon::parse($createdAt);
  $months = [
    1 => 'ENERO',
    2 => 'FEBRERO',
    3 => 'MARZO',
    4 => 'ABRIL',
    5 => 'MAYO',
    6 => 'JUNIO',
    7 => 'JULIO',
    8 => 'AGOSTO',
    9 => 'SEPTIEMBRE',
    10 => 'OCTUBRE',
    11 => 'NOVIEMBRE',
    12 => 'DICIEMBRE',
  ];

  $legalDateText = 'METEPEC ESTADO DE MEXICO A ' . $quoteDate->format('d') . ' DE ' . ($months[(int) $quoteDate->format('n')] ?? '') . ' DEL ' . $quoteDate->format('Y');

  // Datos completos para exportación a Excel
  $propuestaComercial->loadMissing([
      'items.matches.product',
      'items.productoSeleccionado',
  ]);

  $excelItemsPayload = $propuestaComercial->items
      ->sortBy('sort')
      ->values()
      ->map(function ($item, $index) {
          $selectedMatch = $item->matches->firstWhere('seleccionado', true);
          $selectedProduct = $item->productoSeleccionado ?: optional($selectedMatch)->product;

          $displayNumber = data_get($item->meta, 'partida_number')
              ?: data_get($item->meta, 'numero_partida')
              ?: data_get($item->meta, 'partida')
              ?: ($item->partida_number ?? null)
              ?: ($item->numero_partida ?? null)
              ?: ($item->partida ?? null)
              ?: ($item->sort ?? ($index + 1));

          $brand = data_get($item->meta, 'external_supplier')
              ?: data_get($item->meta, 'brand')
              ?: data_get($item->meta, 'marca')
              ?: data_get($selectedProduct, 'brand')
              ?: data_get($selectedProduct, 'marca')
              ?: '';

          $model = data_get($item->meta, 'modelo')
              ?: data_get($item->meta, 'model')
              ?: data_get($selectedProduct, 'model')
              ?: data_get($selectedProduct, 'modelo')
              ?: data_get($selectedProduct, 'model_name')
              ?: '';

          $reference = data_get($item->meta, 'catalog_product_name_manual')
              ?: data_get($item->meta, 'manual_catalog_product_name')
              ?: data_get($selectedProduct, 'name')
              ?: data_get($item->meta, 'external_supplier')
              ?: '';

          $sku = data_get($selectedProduct, 'sku') ?: data_get($item->meta, 'sku') ?: '';
          $qty = (float) ($item->cantidad_cotizada ?: $item->cantidad_maxima ?: $item->cantidad_minima ?: 0);
          $cost = (float) ($item->costo_unitario ?: 0);
          $price = (float) ($item->precio_unitario ?: 0);
          $subtotal = (float) ($item->subtotal ?: ($price > 0 && $qty > 0 ? $price * $qty : 0));
          $uiStatus = data_get($item->meta, 'ui_status', 'pending');

          $status = match ($uiStatus) {
              'accepted_item' => 'Aceptado',
              'manual_review' => 'Revisión',
              'rejected_item' => 'Rechazado',
              default => ($selectedProduct || $item->matches->count() ? 'Similar' : 'No encontrado'),
          };

          return [
              'number' => $displayNumber,
              'quantity' => $qty,
              'unit' => $item->unidad_solicitada ?: '',
              'description' => $item->descripcion_original ?: '',
              'reference' => $reference,
              'sku' => $sku,
              'brand' => $brand,
              'model' => $model,
              'cost' => $cost,
              'price' => $price,
              'subtotal' => $subtotal,
              'status' => $status,
          ];
      })
      ->all();

  $integerPart = (int) floor((float) $total);
  $cents = (int) round((((float) $total) - $integerPart) * 100);
  if ($cents === 100) {
    $integerPart++;
    $cents = 0;
  }

  $currencyWord = $integerPart === 1 ? 'PESO' : 'PESOS';
  $totalInWords = number_format($total, 2) . ' ' . $currencyWord . ' ' . str_pad($cents, 2, '0', STR_PAD_LEFT) . '/100 M.N.';

  if (class_exists(\NumberFormatter::class)) {
    $formatter = new \NumberFormatter('es_MX', \NumberFormatter::SPELLOUT);
    $words = trim((string) $formatter->format($integerPart));
    if ($words !== '') {
      $totalInWords = mb_strtoupper($words, 'UTF-8') . ' ' . $currencyWord . ' ' . str_pad($cents, 2, '0', STR_PAD_LEFT) . '/100 M.N.';
    }
  }
@endphp
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  .client-quote-page {
    --bg: #f9fafb;
    --card: #ffffff;
    --ink: #1f1f1f;
    --muted: #6b7280;
    --line: #e5e7eb;
    --blue: #007aff;
    --blue-soft: #e6f0ff;
    --success: #15803d;
    --success-soft: #e6ffe6;

    min-height: 100vh;
    background: var(--bg);
    font-family: 'Quicksand', sans-serif;
    color: var(--ink);
    padding: 24px 0 70px;
  }

  .client-quote-page,
  .client-quote-page * {
    box-sizing: border-box;
  }

  .client-quote-page .wrap {
    width: min(1180px, calc(100vw - 32px));
    margin: 0 auto;
  }

  .client-quote-page .page-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 22px;
  }

  .client-quote-page .back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #111;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
  }

  .client-quote-page .back-link:hover {
    color: var(--blue);
  }

  .client-quote-page .actions {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
    justify-content: flex-end;
  }

  .client-quote-page .actions-divider {
    width: 1px;
    height: 32px;
    background-color: var(--line);
    margin: 0 4px;
  }

  /* BOTONES DE TEXTO ESTÁNDAR */
  .client-quote-page .btn {
    min-height: 42px;
    border-radius: 8px;
    border: 1px solid var(--line);
    background: #fff;
    color: #111;
    padding: 0 16px;
    font-family: 'Quicksand', sans-serif;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: .2s ease;
  }

  .client-quote-page .btn:hover {
    transform: translateY(-1px);
    background: #f9fafb;
    border-color: #d1d5db;
  }

  .client-quote-page .btn:active {
    transform: scale(.98);
  }

  .client-quote-page .btn-primary {
    background: var(--blue);
    border-color: var(--blue);
    color: #fff;
    box-shadow: 0 10px 24px rgba(0,122,255,.15);
  }

  .client-quote-page .btn-primary:hover {
    background: #0066d6;
    border-color: #0066d6;
    color: #fff;
    box-shadow: 0 14px 28px rgba(0,122,255,.22);
  }

  /* BOTONES CUADRADOS (ICONOS) */
  .client-quote-page .btn-icon {
    position: relative;
    width: 42px;
    height: 42px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    border: 1px solid var(--line);
    background: #fff;
    color: var(--muted);
    transition: all 0.2s ease;
    cursor: pointer;
    text-decoration: none;
  }

  .client-quote-page .btn-icon svg {
    width: 20px;
    height: 20px;
    transition: opacity 0.2s;
  }

  .client-quote-page .btn-icon:hover:not(.is-loading) {
    transform: translateY(-1px);
  }

  .client-quote-page .btn-icon:active:not(.is-loading) {
    transform: scale(.95);
  }

  /* Variantes de Iconos */
  .client-quote-page .btn-icon.mail { color: #4b5563; }
  .client-quote-page .btn-icon.mail:hover:not(.is-loading) { border-color: #6b7280; background: #f3f4f6; color: #111; }
  
  .client-quote-page .btn-icon.excel { color: #15803d; }
  .client-quote-page .btn-icon.excel:hover:not(.is-loading) { border-color: #15803d; background: #f0fdf4; }
  
  .client-quote-page .btn-icon.pdf { color: #dc2626; }
  .client-quote-page .btn-icon.pdf:hover:not(.is-loading) { border-color: #dc2626; background: #fef2f2; }

  /* --- ANIMACIÓN DE CARGA (SPINNER MINIMALISTA) --- */
  @keyframes spin-minimal {
    to { transform: rotate(360deg); }
  }

  .client-quote-page .btn-icon.is-loading {
    pointer-events: none;
    opacity: 0.8;
  }

  .client-quote-page .btn-icon.is-loading svg {
    opacity: 0;
  }

  .client-quote-page .btn-icon.is-loading::after {
    content: "";
    position: absolute;
    width: 18px;
    height: 18px;
    border: 2px solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spin-minimal 0.7s linear infinite;
  }

  /* TOOLTIPS CSS PURO */
  [data-tooltip] {
    position: relative;
  }

  [data-tooltip]::before {
    content: attr(data-tooltip);
    position: absolute;
    bottom: calc(100% + 8px);
    left: 50%;
    transform: translateX(-50%);
    background-color: #111827;
    color: #fff;
    padding: 6px 12px;
    border-radius: 8px;
    font-family: 'Quicksand', sans-serif;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s ease;
    pointer-events: none;
    z-index: 100;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  }

  [data-tooltip]:hover::before {
    opacity: 1;
    visibility: visible;
  }

  .client-quote-page .alert-success {
    background: var(--success-soft);
    color: var(--success);
    border: 1px solid rgba(21,128,61,.18);
    border-radius: 14px;
    padding: 14px 16px;
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 18px;
  }

  .client-quote-page .document-card {
    width: min(1040px, 100%);
    margin: 0 auto;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 18px;
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
    padding: 50px 52px 58px;
  }

  .client-quote-page .document-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 28px; border-bottom: 1px solid var(--line); padding-bottom: 24px; margin-bottom: 28px; }
  .client-quote-page .brand { display: flex; align-items: flex-start; gap: 14px; }
  .client-quote-page .brand-logo { width: 74px; height: auto; display: block; object-fit: contain; flex: 0 0 auto; }
  .client-quote-page .brand-name { color: #111; font-size: 18px; font-weight: 700; margin-bottom: 4px; }
  .client-quote-page .brand-info, .client-quote-page .quote-info, .client-quote-page .client-info { color: var(--muted); font-size: 11px; line-height: 1.6; font-weight: 600; }
  .client-quote-page .quote-box { text-align: right; }
  .client-quote-page .quote-label { color: var(--muted); font-size: 10px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; margin-bottom: 7px; }
  .client-quote-page .quote-folio { color: var(--blue); font-size: 18px; font-weight: 700; margin-bottom: 8px; }
  .client-quote-page .section-label { color: var(--muted); font-size: 10px; font-weight: 700; letter-spacing: .13em; text-transform: uppercase; margin-bottom: 10px; }
  .client-quote-page .client-name { color: #111; font-size: 15px; font-weight: 700; margin-bottom: 4px; }
  .client-quote-page .client-block { margin-bottom: 34px; }

  /* ===== TABLA ===== */
  .client-quote-page table { width: 100%; border-collapse: collapse; margin-top: 18px; }
  .client-quote-page th { text-align: left; color: #687082; font-size: 12px; font-weight: 700; padding: 13px 12px; border-bottom: 1px solid var(--line); white-space: nowrap; }
  .client-quote-page td { color: #111; font-size: 12.5px; font-weight: 600; padding: 16px 12px; border-bottom: 1px solid var(--line); vertical-align: top; line-height: 1.55; }

  .client-quote-page th.col-num,
  .client-quote-page td.col-num { width: 48px; }

  .client-quote-page th.col-quantity,
  .client-quote-page td.col-quantity {
    width: 84px;
    min-width: 84px;
    text-align: right;
    white-space: nowrap;
  }

  .client-quote-page th.col-unit,
  .client-quote-page td.col-unit {
    width: 92px;
    min-width: 92px;
    padding-left: 18px;
    white-space: nowrap;
  }

  .client-quote-page th.col-desc,
  .client-quote-page td.col-desc {
    min-width: 300px;
  }

  .client-quote-page th.col-brand,
  .client-quote-page td.col-brand,
  .client-quote-page th.col-model,
  .client-quote-page td.col-model {
    width: 130px;
    min-width: 110px;
    white-space: normal;
    word-break: break-word;
  }
  .client-quote-page td.col-brand,
  .client-quote-page td.col-model { color: #374151; }

  .client-quote-page th.col-price,
  .client-quote-page td.col-price,
  .client-quote-page th.col-subtotal,
  .client-quote-page td.col-subtotal {
    width: 110px;
    min-width: 100px;
    text-align: right;
    white-space: nowrap;
  }

  .client-quote-page .text-right { text-align: right; }
  .client-quote-page .totals { width: min(340px, 100%); margin-left: auto; margin-top: 26px; }
  .client-quote-page .total-row { display: grid; grid-template-columns: 1fr auto; gap: 16px; padding: 7px 0; color: var(--muted); font-size: 13px; font-weight: 600; }
  .client-quote-page .total-row strong { color: #111; }
  .client-quote-page .total-row.final { border-top: 1px solid var(--line); margin-top: 6px; padding-top: 13px; color: #111; font-size: 17px; font-weight: 700; }
  .client-quote-page .total-row.final strong { color: var(--blue); font-size: 17px; }
  .client-quote-page .legal-section { margin-top: 34px; border-top: 1px solid var(--line); padding-top: 24px; color: #111; page-break-inside: avoid; }
  .client-quote-page .amount-in-words { font-size: 12px; line-height: 1.5; margin-bottom: 28px; text-transform: uppercase; }
  .client-quote-page .legal-title { font-size: 14px; font-weight: 700; text-transform: uppercase; text-decoration: underline; margin: 0 0 16px; }
  .client-quote-page .legal-intro { font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 18px; line-height: 1.5; }
  .client-quote-page .legal-list { margin: 0; padding-left: 0; list-style: none; font-size: 12px; line-height: 1.45; text-transform: uppercase; }
  .client-quote-page .legal-list li { margin-bottom: 8px; }
  .client-quote-page .legal-date { margin-top: 28px; text-align: right; font-size: 12px; font-weight: 700; text-transform: uppercase; text-decoration: underline; }
  .client-quote-page .signature-block { margin-top: 64px; text-align: center; page-break-inside: avoid; }
  .client-quote-page .signature-title { font-size: 14px; font-weight: 700; text-transform: uppercase; margin-bottom: 46px; }
  .client-quote-page .signature-space { width: min(78%, 320px); margin: 0 auto 10px; border-bottom: 1px solid #111; height: 48px; }
  .client-quote-page .signature-name, .client-quote-page .signature-role { font-size: 14px; font-weight: 700; text-transform: uppercase; line-height: 1.3; }
  .client-quote-page .modal-backdrop { position: fixed; inset: 0; z-index: 9999; display: none; align-items: center; justify-content: center; padding: 20px; background: rgba(0,0,0,.18); backdrop-filter: blur(6px); }
  .client-quote-page .modal-backdrop.show { display: flex; }
  .client-quote-page .modal { width: min(520px, 100%); background: #fff; border: 1px solid var(--line); border-radius: 12px; box-shadow: 0 24px 80px rgba(0,0,0,.12); padding: 22px; }
  .client-quote-page .modal-title { margin: 0; color: #111; font-size: 20px; font-weight: 700; }
  .client-quote-page .modal-subtitle { color: var(--muted); font-size: 13px; margin: 7px 0 18px; }
  .client-quote-page .field { display: grid; gap: 7px; margin-bottom: 13px; }
  .client-quote-page .field label { color: var(--muted); font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; }
  .client-quote-page .input, .client-quote-page .textarea { width: 100%; border: 1px solid var(--line); border-radius: 12px; background: #fff; color: #111; font-family: 'Quicksand', sans-serif; font-size: 14px; font-weight: 600; outline: none; transition: .2s ease; }
  .client-quote-page .input { height: 42px; padding: 0 13px; }
  .client-quote-page .textarea { min-height: 110px; padding: 12px 13px; resize: vertical; }
  .client-quote-page .input:focus, .client-quote-page .textarea:focus { border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-soft); }

  @media print {
    .client-quote-page { background: #fff; padding: 0; }
    .client-quote-page .page-actions, .client-quote-page .actions, .client-quote-page .back-link, .client-quote-page .modal-backdrop { display: none !important; }
    @page { size: letter landscape; margin: 10mm; }
    .client-quote-page .wrap { width: 100%; max-width: none; }
    .client-quote-page .document-card { border: 0; box-shadow: none; width: 100%; max-width: none; margin: 0; padding: 0; }
  }

  @media (max-width: 1000px) {
    .client-quote-page .document-card { padding: 38px 28px 46px; }
    .client-quote-page .table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .client-quote-page table { min-width: 880px; }
  }

  @media (max-width: 900px) {
    .client-quote-page .page-actions { align-items: flex-start; flex-direction: column; }
    .client-quote-page .actions { justify-content: flex-start; }
    .client-quote-page .document-card { width: 100%; }
  }

  @media (max-width: 640px) {
    .client-quote-page { padding: 24px 0 46px; }
    .client-quote-page .wrap { width: calc(100vw - 24px); }
    .client-quote-page .document-head { flex-direction: column; }
    .client-quote-page .quote-box { text-align: left; }
    .client-quote-page .btn { width: 100%; }
    .client-quote-page .brand { flex-direction: column; align-items: flex-start; }
    .client-quote-page .legal-date { text-align: left; }
    .client-quote-page .actions-divider { display: none; }
  }
</style>

<div class="client-quote-page">
  <div class="wrap">
    <div class="page-actions">
      <a href="{{ route('propuestas-comerciales.show', $propuestaComercial) }}" class="back-link">
        ← Regresar
      </a>

      <div class="actions">
        <div data-tooltip="Enviar por correo">
          <button type="button" class="btn-icon mail" onclick="openEmailModal()">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
              <polyline points="22,6 12,13 2,6"></polyline>
            </svg>
          </button>
        </div>

        <div data-tooltip="Exportar a Excel">
          <button type="button" class="btn-icon excel" onclick="exportQuoteExcel(this)">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="9" y1="15" x2="15" y2="15"></line>
              <path d="M10 13l4 4M14 13l-4 4"></path>
            </svg>
          </button>
        </div>

        <div data-tooltip="Descargar PDF">
          <a href="{{ route('propuestas-comerciales.cliente.pdf', $propuestaComercial) }}" class="btn-icon pdf" onclick="handlePdfDownload(this)">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <path d="M9 15v-4h2a2 2 0 0 1 0 4H9z"></path>
              <path d="M14 15v-4h2"></path>
              <path d="M14 13h1"></path>
            </svg>
          </a>
        </div>

        <div class="actions-divider"></div>

        <a href="{{ route('propuestas-comerciales.adjudicacion.create', $propuestaComercial) }}" class="btn btn-primary">
          Generar adjudicación
        </a>
      </div>
    </div>

    @if(session('status'))
      <div class="alert-success">{{ session('status') }}</div>
    @endif

    <div class="document-card">
      <div class="document-head">
        <div class="brand">
          <img src="{{ $logoSrc }}" alt="Logo Jureto" class="brand-logo">
          <div>
            <div class="brand-name">{{ $company['name'] }}</div>
            <div class="brand-info">
              {{ $company['address'] }}<br>
              {{ $company['phone'] }} · {{ $company['email'] }}<br>
              RFC: {{ $company['rfc'] }}
            </div>
          </div>
        </div>

        <div class="quote-box">
          <div class="quote-label">Cotización</div>
          <div class="quote-folio">{{ $folio }}</div>
          <div class="quote-info">
            {{ $quoteDate->format('d/m/Y') }}<br>
            Vigencia: 15 días
          </div>
        </div>
      </div>

      <div class="client-block">
        <div class="section-label">Datos del cliente</div>
        <div class="client-name">{{ $client['name'] }}</div>
        <div class="client-info">
          {{ $client['attention'] }}<br>
          @if($client['email']) {{ $client['email'] }}<br> @endif
          @if($client['phone']) {{ $client['phone'] }}<br> @endif
          @if($client['address']) {{ $client['address'] }}<br> @endif
          @if($client['rfc']) RFC: {{ $client['rfc'] }} @endif
        </div>
      </div>

      <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th class="col-num">#</th>
              <th class="col-quantity">Cantidad</th>
              <th class="col-unit">Unidad</th>
              <th class="col-desc">Descripción</th>
              <th class="col-brand">Marca</th>
              <th class="col-model">Modelo</th>
              <th class="col-price">P. Unitario</th>
              <th class="col-subtotal">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            @foreach($excelItemsPayload as $item)
              <tr>
                <td class="col-num">{{ $item['number'] }}</td>
                <td class="col-quantity">{{ number_format($item['quantity'], 0) }}</td>
                <td class="col-unit">{{ $item['unit'] }}</td>
                <td class="col-desc">{{ $item['description'] }}</td>
                <td class="col-brand">{{ $item['brand'] !== '' ? $item['brand'] : '—' }}</td>
                <td class="col-model">{{ $item['model'] !== '' ? $item['model'] : '—' }}</td>
                <td class="col-price">${{ number_format($item['price'], 2) }}</td>
                <td class="col-subtotal"><strong>${{ number_format($item['subtotal'], 2) }}</strong></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="totals">
        <div class="total-row">
          <span>Subtotal</span>
          <strong>${{ number_format($subtotal, 2) }}</strong>
        </div>

        @if($discount > 0)
          <div class="total-row">
            <span>Descuento</span>
            <strong>-${{ number_format($discount, 2) }}</strong>
          </div>
        @endif

        <div class="total-row">
          <span>IVA ({{ number_format($taxPercent, 0) }}%)</span>
          <strong>${{ number_format($tax, 2) }}</strong>
        </div>

        <div class="total-row final">
          <span>Total</span>
          <strong>${{ number_format($total, 2) }}</strong>
        </div>
      </div>

      <div class="legal-section">
        <div class="amount-in-words">
          (TOTAL CON LETRA: {{ $totalInWords }})
        </div>

        <h3 class="legal-title">CONDICIONES DE LA PROPUESTA ECONÓMICA:</h3>

        <div class="legal-intro">
          EN CASO DE RESULTAR ADJUDICADOS, QUEDA ENTENDIDO Y ACEPTADO LO SIGUIENTE:
        </div>

        <ol class="legal-list">
          <li>1.- LA VIGENCIA DE LOS PRECIOS PROPUESTOS SERÁ POR EL TIEMPO QUE DURE EL PROCEDIMIENTO DE INVITACIÓN A PARTIR DE LA PRESENTACIÓN DE LA PROPUESTA Y HASTA CONCLUIR LA ENTREGA TOTAL DE LOS BIENES EN TIEMPO Y FORMA.</li>
          <li>2.- LOS PRECIOS SERÁN FIJOS E INCONDICIONADOS DURANTE LA VIGENCIA DEL CONTRATO QUE DE RESULTAR GANADOR ME SEA ASIGNADO.</li>
          <li>3.- LOS GASTOS POR CONCEPTO DE TRASLADOS, FLETES, MANIOBRAS DE CARGA, DESCARGA, ACARREO, SEGUROS, U OTROS CONCEPTOS POR LA ENTREGA DE LOS BIENES, ASÍ COMO LA SOLVENTACIÓN DE LAS OBSERVACIONES REALIZADAS AL MOMENTO DE LA ENTREGA DE LOS BIENES, SERÁN A CARGO ÚNICA Y EXCLUSIVAMENTE DE NOSOTROS, RAZÓN POR LA CUAL NO EXIGIREMOS AL COLEGIO CONDICIONES ADICIONALES A LAS PROPUESTAS.</li>
        </ol>

        <div class="legal-date">{{ $legalDateText }}</div>

        <div class="signature-block">
          <div class="signature-title">BAJO PROTESTA DE DECIR VERDAD</div>
          <div class="signature-space"></div>
          <div class="signature-name">{{ $company['representative'] }}</div>
          <div class="signature-role">{{ $company['representative_role'] }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal-backdrop" id="emailModal">
    <div class="modal">
      <h2 class="modal-title">Enviar cotización por correo</h2>
      <p class="modal-subtitle">Se enviará la cotización en PDF como archivo adjunto.</p>

      <form method="POST" action="{{ route('propuestas-comerciales.cliente.email', $propuestaComercial) }}">
        @csrf

        <div class="field">
          <label>Correo del cliente</label>
          <input class="input" type="email" name="email" value="{{ $client['email'] }}" required>
        </div>

        <div class="field">
          <label>Asunto</label>
          <input class="input" name="subject" value="Cotización {{ $folio }}">
        </div>

        <div class="field">
          <label>Mensaje</label>
          <textarea class="textarea" name="message">Hola, adjuntamos la cotización solicitada. Quedamos atentos a tus comentarios.</textarea>
        </div>

        <div style="display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap; margin-top:16px;">
          <button class="btn" type="button" onclick="closeEmailModal()">Cancelar</button>
          <button class="btn btn-primary" type="submit">Enviar cotización</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  const excelItemsPayload = @json($excelItemsPayload);
  const excelFolio = @json($folio);
  const excelTitle = @json($propuestaComercial->titulo ?: 'Cotización');
  const excelClientName = @json($client['name'] ?? '');

  function excelEscape(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function excelNumberOrBlank(value, decimals = 2) {
    const number = Number(value || 0);
    if (!Number.isFinite(number) || number <= 0) return '';
    return number.toFixed(decimals);
  }

  function excelQuantityOrBlank(value) {
    const number = Number(value || 0);
    if (!Number.isFinite(number) || number <= 0) return '';
    return Number.isInteger(number) ? String(number) : String(number);
  }

  function excelSafeFileName(value) {
    return String(value || 'cotizacion')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-zA-Z0-9_-]+/g, '_')
      .replace(/_+/g, '_')
      .replace(/^_|_$/g, '') || 'cotizacion';
  }

  function downloadExcelHtml(html, fileName) {
    const blob = new Blob(['\ufeff' + html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = fileName;
    link.style.display = 'none';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    setTimeout(() => URL.revokeObjectURL(url), 1000);
  }

  function buildQuoteExcelHtml() {
    const generatedAt = new Date().toLocaleString('es-MX');
    const rows = Array.isArray(excelItemsPayload) ? excelItemsPayload : [];

    const rowsHtml = rows.map(item => `
      <tr>
        <td class="center">${excelEscape(item.number)}</td>
        <td class="right">${excelEscape(excelQuantityOrBlank(item.quantity))}</td>
        <td>${excelEscape(item.unit || '')}</td>
        <td>${excelEscape(item.description || '')}</td>
        <td>${excelEscape(item.reference || '')}</td>
        <td>${excelEscape(item.sku || '')}</td>
        <td>${excelEscape(item.brand || '')}</td>
        <td>${excelEscape(item.model || '')}</td>
        <td class="right">${excelEscape(excelNumberOrBlank(item.cost))}</td>
        <td class="right">${excelEscape(excelNumberOrBlank(item.price))}</td>
        <td class="right">${excelEscape(excelNumberOrBlank(item.subtotal))}</td>
        <td>${excelEscape(item.status || '')}</td>
      </tr>
    `).join('');

    return `
      <!doctype html>
      <html lang="es" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
      <head>
        <meta charset="UTF-8">
        <meta http-equiv="Content-Type" content="application/vnd.ms-excel; charset=UTF-8">
        <style>
          @page { size: landscape; margin: .35in; }
          body { font-family: Arial, Helvetica, sans-serif; color: #111111; }
          h1 { font-size: 18px; margin: 0 0 4px; }
          .meta { color: #555555; font-size: 12px; margin-bottom: 14px; }
          table { border-collapse: collapse; width: 100%; table-layout: fixed; }
          th { background: #f3f4f6; color: #111111; font-weight: 700; border: 1px solid #d9d9d9; padding: 8px; font-size: 11px; text-align: left; vertical-align: top; }
          td { border: 1px solid #e5e7eb; padding: 7px; font-size: 11px; vertical-align: top; mso-number-format:"\\@"; }
          .right { text-align: right; }
          .center { text-align: center; }
        </style>
      </head>
      <body>
        <h1>${excelEscape(excelTitle)}</h1>
        <div class="meta">
          Folio: ${excelEscape(excelFolio)} · Cliente: ${excelEscape(excelClientName)} · Generado: ${excelEscape(generatedAt)}
        </div>
        <table>
          <thead>
            <tr>
              <th style="width:55px;">#</th>
              <th style="width:80px;">Cantidad</th>
              <th style="width:90px;">Unidad</th>
              <th style="width:330px;">Producto solicitado</th>
              <th style="width:260px;">Producto / referencia</th>
              <th style="width:120px;">SKU</th>
              <th style="width:150px;">Marca</th>
              <th style="width:150px;">Modelo</th>
              <th style="width:110px;">Costo unitario</th>
              <th style="width:110px;">Precio unitario</th>
              <th style="width:120px;">Subtotal</th>
              <th style="width:120px;">Estado</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml || '<tr><td colspan="12">Sin partidas para exportar.</td></tr>'}
          </tbody>
        </table>
      </body>
      </html>
    `;
  }

  // --- LÓGICA DE ANIMACIÓN EXCEL ---
  function exportQuoteExcel(btn) {
    if (btn) btn.classList.add('is-loading');
    
    setTimeout(() => {
      try {
        const html = buildQuoteExcelHtml();
        downloadExcelHtml(html, `${excelSafeFileName(excelFolio)}_cotizacion_horizontal.xls`);
      } finally {
        if (btn) btn.classList.remove('is-loading');
      }
    }, 150);
  }

  // --- LÓGICA DE ANIMACIÓN PDF ---
  function handlePdfDownload(btn) {
    if (!btn) return;
    
    btn.classList.add('is-loading');
    
    setTimeout(() => {
      btn.classList.remove('is-loading');
    }, 3000);
  }

  function openEmailModal() {
    document.getElementById('emailModal').classList.add('show');
  }

  function closeEmailModal() {
    document.getElementById('emailModal').classList.remove('show');
  }
</script>
</script>
@endsection
