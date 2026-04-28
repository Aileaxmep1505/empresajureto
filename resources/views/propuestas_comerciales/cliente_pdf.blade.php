<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>{{ $folio }}</title>
  <style>
    @page {
      margin: 34px;
    }

    body {
      font-family: DejaVu Sans, sans-serif;
      color: #111111;
      font-size: 11px;
      margin: 0;
      padding: 0;
    }

    .document-card {
      width: 100%;
    }

    .document-head {
      width: 100%;
      border-bottom: 1px solid #ebebeb;
      padding-bottom: 28px;
      margin-bottom: 28px;
    }

    .brand-table {
      width: 100%;
      border-collapse: collapse;
    }

    .brand-icon {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      background: #007aff;
      color: #ffffff;
      text-align: center;
      line-height: 38px;
      font-size: 18px;
      font-weight: bold;
    }

    .brand-name {
      font-size: 18px;
      font-weight: bold;
      margin-bottom: 4px;
    }

    .muted {
      color: #888888;
      line-height: 1.55;
      font-size: 10px;
    }

    .quote-box {
      text-align: right;
    }

    .quote-label {
      color: #888888;
      font-size: 9px;
      font-weight: bold;
      letter-spacing: 1px;
      text-transform: uppercase;
      margin-bottom: 8px;
    }

    .quote-folio {
      color: #007aff;
      font-size: 17px;
      font-weight: bold;
      margin-bottom: 7px;
    }

    .section-label {
      color: #888888;
      font-size: 9px;
      font-weight: bold;
      letter-spacing: 1px;
      text-transform: uppercase;
      margin-bottom: 10px;
    }

    .client-block {
      margin-bottom: 34px;
    }

    .client-name {
      font-size: 13px;
      font-weight: bold;
      margin-bottom: 4px;
    }

    table.items {
      width: 100%;
      border-collapse: collapse;
      margin-top: 14px;
    }

    table.items th {
      color: #687082;
      font-size: 10px;
      text-align: left;
      padding: 10px 0;
      border-bottom: 1px solid #ebebeb;
    }

    table.items td {
      font-size: 10px;
      padding: 13px 0;
      border-bottom: 1px solid #ebebeb;
      vertical-align: top;
    }

    .right {
      text-align: right;
    }

    .totals {
      width: 300px;
      margin-left: auto;
      margin-top: 28px;
    }

    .totals-row {
      width: 100%;
      border-collapse: collapse;
    }

    .totals-row td {
      padding: 5px 0;
      font-size: 11px;
      color: #888888;
    }

    .totals-row td:last-child {
      text-align: right;
      color: #111111;
      font-weight: bold;
    }

    .final td {
      border-top: 1px solid #ebebeb;
      padding-top: 12px;
      font-size: 16px;
      color: #111111;
      font-weight: bold;
    }

    .final td:last-child {
      color: #007aff;
    }

    .footer-note {
      border-top: 1px solid #ebebeb;
      margin-top: 42px;
      padding-top: 18px;
      color: #888888;
      font-size: 10px;
      line-height: 1.7;
    }
  </style>
</head>
<body>
  <div class="document-card">
    <div class="document-head">
      <table class="brand-table">
        <tr>
          <td style="width:48px; vertical-align:top;">
            <div class="brand-icon">*</div>
          </td>

          <td style="vertical-align:top;">
            <div class="brand-name">{{ $company['name'] }}</div>
            <div class="muted">
              {{ $company['address'] }}<br>
              {{ $company['phone'] }} · {{ $company['email'] }}<br>
              RFC: {{ $company['rfc'] }}
            </div>
          </td>

          <td style="width:220px; vertical-align:top;" class="quote-box">
            <div class="quote-label">Cotización</div>
            <div class="quote-folio">{{ $folio }}</div>
            <div class="muted">
              {{ $createdAt->format('d/m/Y') }}<br>
              Vigencia: 15 días
            </div>
          </td>
        </tr>
      </table>
    </div>

    <div class="client-block">
      <div class="section-label">Datos del cliente</div>
      <div class="client-name">{{ $client['name'] }}</div>
      <div class="muted">
        {{ $client['attention'] }}<br>
        @if($client['email']) {{ $client['email'] }}<br> @endif
        @if($client['phone']) {{ $client['phone'] }}<br> @endif
        @if($client['address']) {{ $client['address'] }}<br> @endif
        @if($client['rfc']) RFC: {{ $client['rfc'] }} @endif
      </div>
    </div>

    <table class="items">
      <thead>
        <tr>
          <th style="width:40px;">#</th>
          <th style="width:70px;" class="right">Cantidad</th>
          <th style="width:70px;">Unidad</th>
          <th>Descripción</th>
          <th style="width:90px;" class="right">P. Unitario</th>
          <th style="width:90px;" class="right">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        @foreach($items as $item)
          <tr>
            <td>{{ $item['number'] }}</td>
            <td class="right">{{ number_format($item['quantity'], 0) }}</td>
            <td>{{ $item['unit'] }}</td>
            <td>{{ $item['description'] }}</td>
            <td class="right">${{ number_format($item['price'], 2) }}</td>
            <td class="right"><strong>${{ number_format($item['subtotal'], 2) }}</strong></td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <div class="totals">
      <table class="totals-row">
        <tr>
          <td>Subtotal</td>
          <td>${{ number_format($subtotal, 2) }}</td>
        </tr>

        @if($discount > 0)
          <tr>
            <td>Descuento</td>
            <td>-${{ number_format($discount, 2) }}</td>
          </tr>
        @endif

        <tr>
          <td>IVA ({{ number_format($taxPercent, 0) }}%)</td>
          <td>${{ number_format($tax, 2) }}</td>
        </tr>

        <tr class="final">
          <td>Total</td>
          <td>${{ number_format($total, 2) }}</td>
        </tr>
      </table>
    </div>

    <div class="footer-note">
      Esta cotización tiene una vigencia de 15 días naturales a partir de su fecha de emisión.
      Precios sujetos a disponibilidad y validación final. Gracias por considerar nuestra propuesta.
    </div>
  </div>
</body>
</html>