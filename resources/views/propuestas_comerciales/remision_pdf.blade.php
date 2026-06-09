@php
  $company = [
    'name' => 'JURETO S.A. DE C.V.',
    'address' => 'BERNARDO VARA 25, COL. PILARES, C.P. 52179, METEPEC, ESTADO DE MEXICO.',
    'phone' => '5541937243, 8135515784',
    'email' => 'RTORT@JURETO.COM.MX',
    'rfc' => 'JUR2002196K4',
  ];

  $logoFile = public_path('images/logo-mail.png');
  $logoExt = strtolower(pathinfo($logoFile, PATHINFO_EXTENSION));
  $logoMime = match ($logoExt) { 'jpg','jpeg' => 'jpeg', 'svg' => 'svg+xml', default => $logoExt ?: 'png' };
  $logoSrc = file_exists($logoFile)
      ? 'data:image/' . $logoMime . ';base64,' . base64_encode(file_get_contents($logoFile))
      : null;

  $money = fn ($n) => '$' . number_format((float) $n, 2);
  $cliente = $resultado->cliente ?: optional($resultado->propuesta)->cliente ?: 'Cliente';
@endphp
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    * { box-sizing: border-box; }
    @page { margin: 30px 36px; }
    body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; font-size: 10px; margin: 0; }
    .head-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #0f172a; padding-bottom: 6px; }
    .head-table td { vertical-align: top; padding: 0; }
    .logo { width: 64px; height: auto; }
    .co-name { font-size: 13px; font-weight: bold; color: #0f172a; }
    .co-info { color: #6b7280; font-size: 8.5px; line-height: 1.5; }
    .doc-box { text-align: right; }
    .doc-label { font-size: 9px; letter-spacing: 1px; color: #6b7280; text-transform: uppercase; }
    .doc-folio { font-size: 16px; font-weight: bold; color: #0f172a; }
    .doc-date { font-size: 9px; color: #6b7280; margin-top: 3px; }
    h1 { font-size: 16px; color: #0f172a; margin: 16px 0 6px; }
    .cliente-box { border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 12px; margin-bottom: 12px; }
    .cliente-box .l { font-size: 8px; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; }
    .cliente-box .v { font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 2px; }

    table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.data th, table.data td { border: 1px solid #e5e7eb; padding: 6px 7px; vertical-align: top; text-align: left; }
    table.data th { background: #f3f4f6; font-size: 8.5px; color: #111; }
    table.data td { font-size: 9px; }
    td.r { text-align: right; } td.c { text-align: center; }

    .totales { width: 240px; margin-left: auto; margin-top: 10px; }
    .totales td { padding: 4px 6px; font-size: 10px; }
    .totales .tot-final td { border-top: 1px solid #0f172a; font-weight: bold; font-size: 12px; }

    .firma { margin-top: 60px; width: 60%; }
    .firma .line { border-top: 1px solid #0f172a; padding-top: 5px; text-align: center; font-size: 9px; color: #374151; }

    .foot { margin-top: 26px; border-top: 1px solid #e5e7eb; padding-top: 6px; color: #9ca3af; font-size: 8px; }
  </style>
</head>
<body>

  <table class="head-table">
    <tr>
      <td style="width: 70px;">
        @if($logoSrc)<img src="{{ $logoSrc }}" class="logo" alt="Logo">@endif
      </td>
      <td>
        <div class="co-name">{{ $company['name'] }}</div>
        <div class="co-info">
          {{ $company['address'] }}<br>
          {{ $company['phone'] }} · {{ $company['email'] }}<br>
          RFC: {{ $company['rfc'] }}
        </div>
      </td>
      <td class="doc-box" style="width: 150px;">
        <div class="doc-label">Remisión</div>
        <div class="doc-folio">{{ $folio }}</div>
        <div class="doc-date">Fecha: {{ $generadoEn }}</div>
      </td>
    </tr>
  </table>

  <h1>Nota de remisión (entrega)</h1>

  <div class="cliente-box">
    <div class="l">Cliente</div>
    <div class="v">{{ $cliente }}</div>
  </div>

  <table class="data">
    <thead>
      <tr>
        <th style="width:28px;" class="c">#</th>
        <th style="width:60px;" class="c">Cantidad</th>
        <th style="width:55px;" class="c">Unidad</th>
        <th>Descripción</th>
        <th style="width:75px;" class="r">P. Unit.</th>
        <th style="width:85px;" class="r">Importe</th>
      </tr>
    </thead>
    <tbody>
      @forelse($ganadas as $g)
        <tr>
          <td class="c">{{ $g['num'] }}</td>
          <td class="c">{{ rtrim(rtrim(number_format($g['qty'], 2), '0'), '.') }}</td>
          <td class="c">{{ $g['unit'] }}</td>
          <td>{{ $g['desc'] }}</td>
          <td class="r">{{ $money($g['offered']) }}</td>
          <td class="r">{{ $money($g['subtotal']) }}</td>
        </tr>
      @empty
        <tr><td colspan="6" class="c">No hay partidas ganadas para remisionar.</td></tr>
      @endforelse
    </tbody>
  </table>

  <table class="totales">
    <tr><td>Subtotal</td><td class="r">{{ $money($subtotal) }}</td></tr>
    <tr><td>IVA ({{ number_format($ivaPct, 0) }}%)</td><td class="r">{{ $money($iva) }}</td></tr>
    <tr class="tot-final"><td>Total</td><td class="r">{{ $money($total) }}</td></tr>
  </table>

  <div class="firma">
    <div class="line">Recibí de conformidad (nombre y firma)</div>
  </div>

  <div class="foot">
    Documento de remisión generado por {{ $company['name'] }} a partir de las partidas adjudicadas (ganadas). No es factura.
  </div>

</body>
</html>