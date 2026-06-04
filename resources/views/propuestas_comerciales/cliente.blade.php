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
    width: min(1120px, calc(100vw - 32px));
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
    flex-wrap: wrap;
    justify-content: flex-end;
  }

  .client-quote-page .btn {
    min-height: 42px;
    border-radius: 13px;
    border: 1px solid var(--line);
    background: #fff;
    color: #111;
    padding: 0 17px;
    font-family: 'Quicksand', sans-serif;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: .22s ease;
  }

  .client-quote-page .btn:hover {
    transform: translateY(-1px);
    background: #f9fafb;
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
    background: var(--blue);
    box-shadow: 0 16px 34px rgba(0,122,255,.22);
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
    width: min(780px, 100%);
    margin: 0 auto;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 18px;
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
    padding: 46px 42px 54px;
  }

  .client-quote-page .document-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 28px;
    border-bottom: 1px solid var(--line);
    padding-bottom: 24px;
    margin-bottom: 28px;
  }

  .client-quote-page .brand {
    display: flex;
    align-items: flex-start;
    gap: 14px;
  }

  .client-quote-page .brand-logo {
    width: 74px;
    height: auto;
    display: block;
    object-fit: contain;
    flex: 0 0 auto;
  }

  .client-quote-page .brand-name {
    color: #111;
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 4px;
  }

  .client-quote-page .brand-info,
  .client-quote-page .quote-info,
  .client-quote-page .client-info {
    color: var(--muted);
    font-size: 10.5px;
    line-height: 1.6;
    font-weight: 600;
  }

  .client-quote-page .quote-box {
    text-align: right;
  }

  .client-quote-page .quote-label {
    color: var(--muted);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    margin-bottom: 7px;
  }

  .client-quote-page .quote-folio {
    color: var(--blue);
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 8px;
  }

  .client-quote-page .section-label {
    color: var(--muted);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .13em;
    text-transform: uppercase;
    margin-bottom: 10px;
  }

  .client-quote-page .client-name {
    color: #111;
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 4px;
  }

  .client-quote-page .client-block {
    margin-bottom: 34px;
  }

  .client-quote-page table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 16px;
  }

  .client-quote-page th {
    text-align: left;
    color: #687082;
    font-size: 11px;
    font-weight: 700;
    padding: 11px 0;
    border-bottom: 1px solid var(--line);
  }

  .client-quote-page td {
    color: #111;
    font-size: 11px;
    font-weight: 600;
    padding: 14px 0;
    border-bottom: 1px solid var(--line);
    vertical-align: top;
  }

  .client-quote-page .text-right {
    text-align: right;
  }

  .client-quote-page .totals {
    width: min(320px, 100%);
    margin-left: auto;
    margin-top: 26px;
  }

  .client-quote-page .total-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 16px;
    padding: 7px 0;
    color: var(--muted);
    font-size: 13px;
    font-weight: 600;
  }

  .client-quote-page .total-row strong {
    color: #111;
  }

  .client-quote-page .total-row.final {
    border-top: 1px solid var(--line);
    margin-top: 6px;
    padding-top: 13px;
    color: #111;
    font-size: 17px;
    font-weight: 700;
  }

  .client-quote-page .total-row.final strong {
    color: var(--blue);
    font-size: 17px;
  }

  .client-quote-page .legal-section {
    margin-top: 34px;
    border-top: 1px solid var(--line);
    padding-top: 24px;
    color: #111;
    page-break-inside: avoid;
  }

  .client-quote-page .amount-in-words {
    font-size: 12px;
    line-height: 1.5;
    margin-bottom: 28px;
    text-transform: uppercase;
  }

  .client-quote-page .legal-title {
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
    text-decoration: underline;
    margin: 0 0 16px;
  }

  .client-quote-page .legal-intro {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 18px;
    line-height: 1.5;
  }

  .client-quote-page .legal-list {
    margin: 0;
    padding-left: 0;
    list-style: none;
    font-size: 12px;
    line-height: 1.45;
    text-transform: uppercase;
  }

  .client-quote-page .legal-list li {
    margin-bottom: 8px;
  }

  .client-quote-page .legal-date {
    margin-top: 28px;
    text-align: right;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    text-decoration: underline;
  }

  .client-quote-page .signature-block {
    margin-top: 64px;
    text-align: center;
    page-break-inside: avoid;
  }

  .client-quote-page .signature-title {
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 46px;
  }

  .client-quote-page .signature-space {
    width: min(78%, 320px);
    margin: 0 auto 10px;
    border-bottom: 1px solid #111;
    height: 48px;
  }

  .client-quote-page .signature-name,
  .client-quote-page .signature-role {
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
    line-height: 1.3;
  }

  .client-quote-page .modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(0,0,0,.18);
    backdrop-filter: blur(6px);
  }

  .client-quote-page .modal-backdrop.show {
    display: flex;
  }

  .client-quote-page .modal {
    width: min(520px, 100%);
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 18px;
    box-shadow: 0 24px 80px rgba(0,0,0,.12);
    padding: 22px;
  }

  .client-quote-page .modal-title {
    margin: 0;
    color: #111;
    font-size: 20px;
    font-weight: 700;
  }

  .client-quote-page .modal-subtitle {
    color: var(--muted);
    font-size: 13px;
    margin: 7px 0 18px;
  }

  .client-quote-page .field {
    display: grid;
    gap: 7px;
    margin-bottom: 13px;
  }

  .client-quote-page .field label {
    color: var(--muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
  }

  .client-quote-page .input,
  .client-quote-page .textarea {
    width: 100%;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: #fff;
    color: #111;
    font-family: 'Quicksand', sans-serif;
    font-size: 14px;
    font-weight: 600;
    outline: none;
    transition: .2s ease;
  }

  .client-quote-page .input {
    height: 42px;
    padding: 0 13px;
  }

  .client-quote-page .textarea {
    min-height: 110px;
    padding: 12px 13px;
    resize: vertical;
  }

  .client-quote-page .input:focus,
  .client-quote-page .textarea:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  @media print {
    .client-quote-page {
      background: #fff;
      padding: 0;
    }

    .client-quote-page .page-actions,
    .client-quote-page .actions,
    .client-quote-page .back-link,
    .client-quote-page .modal-backdrop {
      display: none !important;
    }

    @page {
      size: letter;
      margin: 12mm;
    }

    .client-quote-page .wrap {
      width: 100%;
      max-width: none;
    }

    .client-quote-page .document-card {
      border: 0;
      box-shadow: none;
      width: 100%;
      max-width: 780px;
      margin: 0 auto;
      padding: 0;
    }
  }

  @media (max-width: 900px) {
    .client-quote-page .page-actions {
      align-items: flex-start;
      flex-direction: column;
    }

    .client-quote-page .actions {
      justify-content: flex-start;
    }

    .client-quote-page .document-card {
      width: 100%;
      padding: 34px 24px;
    }
  }

  @media (max-width: 640px) {
    .client-quote-page {
      padding: 24px 0 46px;
    }

    .client-quote-page .wrap {
      width: calc(100vw - 24px);
    }

    .client-quote-page .document-head {
      flex-direction: column;
    }

    .client-quote-page .quote-box {
      text-align: left;
    }

    .client-quote-page .btn {
      width: 100%;
    }

    .client-quote-page .brand {
      flex-direction: column;
      align-items: flex-start;
    }

    .client-quote-page table {
      min-width: 620px;
    }

    .client-quote-page .table-scroll {
      overflow-x: auto;
    }

    .client-quote-page .legal-date {
      text-align: left;
    }
  }
</style>

<div class="client-quote-page">
  <div class="wrap">
    <div class="page-actions">
      <a href="{{ route('propuestas-comerciales.show', $propuestaComercial) }}" class="back-link">
        ← Regresar
      </a>

      <div class="actions">
        <button type="button" class="btn" onclick="window.print()">▣ Imprimir</button>
        <button type="button" class="btn" onclick="openEmailModal()">✉ Enviar por correo</button>
        <a href="{{ route('propuestas-comerciales.cliente.pdf', $propuestaComercial) }}" class="btn btn-primary">
          ↓ Descargar PDF
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
              <th style="width:50px;">#</th>
              <th style="width:90px;" class="text-right">Cantidad</th>
              <th style="width:90px;">Unidad</th>
              <th>Descripción</th>
              <th style="width:120px;" class="text-right">P. Unitario</th>
              <th style="width:120px;" class="text-right">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            @foreach($items as $item)
              <tr>
                <td>{{ $item['number'] }}</td>
                <td class="text-right">{{ number_format($item['quantity'], 0) }}</td>
                <td>{{ $item['unit'] }}</td>
                <td>{{ $item['description'] }}</td>
                <td class="text-right">${{ number_format($item['price'], 2) }}</td>
                <td class="text-right"><strong>${{ number_format($item['subtotal'], 2) }}</strong></td>
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
  function openEmailModal() {
    document.getElementById('emailModal').classList.add('show');
  }

  function closeEmailModal() {
    document.getElementById('emailModal').classList.remove('show');
  }
</script>
@endsection
