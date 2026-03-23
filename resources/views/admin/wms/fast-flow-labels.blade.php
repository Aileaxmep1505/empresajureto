<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Etiquetas Fast Flow</title>
  <style>
    @page {
      size: 283.46pt 283.46pt; /* 10cm x 10cm */
      margin: 0;
    }

    html, body {
      margin: 0;
      padding: 0;
      width: 283.46pt;
      height: 283.46pt;
      font-family: DejaVu Sans, sans-serif;
      color: #0f172a;
      font-size: 10px;
    }

    * {
      box-sizing: border-box;
    }

    .page {
      width: 283.46pt;
      height: 283.46pt;
      position: relative;
      overflow: hidden;
    }

    .page-break {
      page-break-after: always;
    }

    .card {
      position: absolute;
      left: 12pt;
      top: 12pt;
      width: 259pt;
      height: 259pt;
      padding: 12pt;
      overflow: hidden;
      background: #fff;
      border: 0; /* SIN MARCO */
    }

    .header {
      position: relative;
      padding-right: 68pt;
      min-height: 58pt;
    }

    .brand {
      margin: 0 0 4pt 0;
      font-size: 21pt;
      line-height: 1;
      font-weight: 800;
      letter-spacing: .2pt;
    }

    .sub {
      margin: 0 0 10pt 0;
      font-size: 7.5pt;
      line-height: 1.15;
      color: #475569;
    }

    .batch {
      margin: 0 0 5pt 0;
      font-size: 8pt;
      line-height: 1.15;
      font-weight: 700;
    }

    .product {
      margin: 0 0 5pt 0;
      font-size: 13.5pt;
      line-height: 1.05;
      font-weight: 800;
      word-break: break-word;
    }

    .sku {
      margin: 0;
      font-size: 7.5pt;
      line-height: 1.1;
      color: #475569;
      word-break: break-word;
    }

    .qr-wrap {
      position: absolute;
      top: 0;
      right: 8pt;
      width: 56pt;
      height: 56pt;
      padding: 2pt;
      background: #fff;
      text-align: center;
    }

    .qr {
      width: 52pt;
      height: 52pt;
      display: block;
      margin: 0 auto;
    }

    .code-block {
      margin-top: 12pt;
    }

    .code {
      margin: 0 0 2pt 0;
      font-size: 10pt;
      line-height: 1.1;
      font-weight: 800;
      word-break: break-word;
      color: #0f172a;
    }

    .code-sub {
      margin: 0;
      font-size: 6.8pt;
      line-height: 1.1;
      color: #64748b;
    }

    .meta {
      width: 100%;
      border-collapse: collapse;
      margin-top: 12pt;
      table-layout: fixed;
    }

    .meta td {
      padding: 7pt 4pt;
      vertical-align: middle;
      line-height: 1.05;
      border: 0;
    }

    .meta-label {
      width: 42%;
      font-size: 7.2pt;
      color: #64748b;
    }

    .meta-value {
      width: 58%;
      font-size: 8.8pt;
      font-weight: 700;
      color: #0f172a;
      word-break: break-word;
    }
  </style>
</head>
<body>
  @foreach($boxes as $box)
    <div class="page {{ !$loop->last ? 'page-break' : '' }}">
      <div class="card">
        <div class="header">
          <div class="qr-wrap">
            <img src="{{ $box->qr_svg }}" class="qr" alt="QR">
          </div>

          <p class="brand">FAST FLOW</p>
          <p class="sub">Cross Dock · Tránsito Rápido</p>
          <p class="batch">Lote: {{ $box->batch_code }}</p>
          <p class="product">{{ optional($box->item)->name ?? 'Producto' }}</p>
          <p class="sku">SKU: {{ optional($box->item)->sku ?? '—' }}</p>
        </div>

        <div class="code-block">
          <p class="code">{{ $box->label_code }}</p>
          <p class="code-sub">Etiqueta individual de caja</p>
        </div>

        <table class="meta">
          <tr>
            <td class="meta-label">Caja</td>
            <td class="meta-value">#{{ (int) ($box->box_number ?? 0) }} de {{ (int) ($box->boxes_in_batch ?? 0) }}</td>
          </tr>
          <tr>
            <td class="meta-label">Piezas por caja</td>
            <td class="meta-value">{{ number_format((int) ($box->units_per_box ?? 0)) }}</td>
          </tr>
          <tr>
            <td class="meta-label">Piezas actuales</td>
            <td class="meta-value">{{ number_format((int) ($box->current_units ?? 0)) }}</td>
          </tr>
          <tr>
            <td class="meta-label">Referencia</td>
            <td class="meta-value">{{ $box->reference ?? '—' }}</td>
          </tr>
        </table>
      </div>
    </div>
  @endforeach
</body>
</html>