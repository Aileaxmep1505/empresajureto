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
@endphp
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    * { box-sizing: border-box; }
    @page { margin: 28px 34px; }
    body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; font-size: 10px; margin: 0; }
    .head-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #0f172a; padding-bottom: 6px; }
    .head-table td { vertical-align: top; padding: 0; }
    .logo { width: 64px; height: auto; }
    .co-name { font-size: 13px; font-weight: bold; color: #0f172a; }
    .co-info { color: #6b7280; font-size: 8.5px; line-height: 1.5; }
    .folio-box { text-align: right; }
    .folio-label { font-size: 8px; letter-spacing: 1px; color: #6b7280; text-transform: uppercase; }
    .folio { font-size: 15px; font-weight: bold; color: #007aff; }
    h1 { font-size: 17px; color: #0f172a; margin: 16px 0 2px; }
    .meta { color: #6b7280; font-size: 9px; margin-bottom: 8px; }
    h2 { font-size: 12px; color: #0f172a; margin: 18px 0 6px; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; }

    .stats { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-top: 8px; }
    .stats td { width: 20%; border: 1px solid #e5e7eb; border-radius: 6px; padding: 9px 4px; text-align: center; }
    .stats .v { font-size: 14px; font-weight: bold; color: #0f172a; }
    .stats .l { font-size: 7.5px; color: #6b7280; text-transform: uppercase; padding-top: 2px; }

    p.diag { font-size: 10.5px; line-height: 1.55; text-align: justify; }

    table.data { width: 100%; border-collapse: collapse; margin-top: 4px; }
    table.data th, table.data td { border: 1px solid #e5e7eb; padding: 5px 6px; vertical-align: top; text-align: left; }
    table.data th { background: #f3f4f6; font-size: 8.5px; color: #111; }
    table.data td { font-size: 9px; }
    td.r { text-align: right; } td.c { text-align: center; }
    .sub { color: #6b7280; font-size: 8px; line-height: 1.4; margin-top: 2px; }
    .up { color: #dc2626; font-weight: bold; } .down { color: #16a34a; font-weight: bold; }

    ol.recos { padding-left: 16px; margin: 4px 0; }
    ol.recos li { font-size: 10px; line-height: 1.5; margin-bottom: 6px; }

    .foot { margin-top: 22px; border-top: 1px solid #e5e7eb; padding-top: 6px; color: #9ca3af; font-size: 8px; }
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
      <td class="folio-box" style="width: 130px;">
        <div class="folio-label">Análisis de licitación</div>
        <div class="folio">{{ $folio }}</div>
      </td>
    </tr>
  </table>

  <h1>Análisis de resultados</h1>
  <div class="meta">
    <strong>{{ $propuestaComercial->titulo ?: 'Propuesta comercial' }}</strong>
    @if($propuestaComercial->cliente) · Cliente: {{ $propuestaComercial->cliente }} @endif
    · Generado: {{ $generadoEn }}
  </div>

  <table class="stats">
    <tr>
      <td><div class="v" style="color:#16a34a;">{{ $resumen['ganadas'] }}</div><div class="l">Ganadas</div></td>
      <td><div class="v" style="color:#dc2626;">{{ $resumen['perdidas'] }}</div><div class="l">Perdidas</div></td>
      <td><div class="v">{{ number_format($resumen['tasaExito'], 2) }}%</div><div class="l">Tasa de éxito</div></td>
      <td><div class="v">{{ $money($resumen['subtotalGanadas']) }}</div><div class="l">Subtotal ganado</div></td>
      <td><div class="v">{{ $money($resumen['montoPerdido']) }}</div><div class="l">No ganado</div></td>
    </tr>
  </table>

  <h2>Diagnóstico general</h2>
  <p class="diag">{{ $diagnostico }}</p>

  <h2>Partidas ganadas (venta)</h2>
  <table class="data">
    <thead>
      <tr>
        <th style="width:28px;">#</th>
        <th>Descripción</th>
        <th style="width:70px;" class="c">Cantidad</th>
        <th style="width:75px;" class="r">P. Unit.</th>
        <th style="width:85px;" class="r">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      @forelse($ganadas as $g)
        <tr>
          <td class="c">{{ $g['num'] }}</td>
          <td>{{ $g['desc'] }}</td>
          <td class="c">{{ rtrim(rtrim(number_format($g['qty'], 2), '0'), '.') }} {{ $g['unit'] }}</td>
          <td class="r">{{ $money($g['offered']) }}</td>
          <td class="r">{{ $money($g['subtotal']) }}</td>
        </tr>
      @empty
        <tr><td colspan="5" class="c">No hay partidas ganadas.</td></tr>
      @endforelse
    </tbody>
  </table>

  <h2>Partidas no ganadas (antecedente)</h2>
  <table class="data">
    <thead>
      <tr>
        <th style="width:28px;">#</th>
        <th>Descripción / motivo / análisis</th>
        <th style="width:65px;" class="r">Tu precio</th>
        <th style="width:65px;" class="r">Ganador</th>
        <th style="width:70px;" class="r">Diferencia</th>
        <th style="width:95px;">Licitante ganador</th>
      </tr>
    </thead>
    <tbody>
      @forelse($perdidas as $p)
        <tr>
          <td class="c">{{ $p['num'] }}</td>
          <td>
            {{ $p['desc'] }}
            @if(!empty($p['motivo']))<div class="sub"><strong>Motivo:</strong> {{ $p['motivo'] }}</div>@endif
            @if(!empty($p['analisis']))<div class="sub">{{ $p['analisis'] }}</div>@endif
          </td>
          <td class="r">{{ $money($p['offered']) }}</td>
          <td class="r">{{ ($p['ganador'] ?? 0) > 0 ? $money($p['ganador']) : '—' }}</td>
          <td class="r">
            @if($p['dif'] !== null)
              <span class="{{ $p['dif'] > 0 ? 'up' : 'down' }}">
                {{ $p['dif'] > 0 ? '+' : '' }}{{ $money($p['dif']) }}<br>({{ number_format($p['difPct'], 2) }}%)
              </span>
            @else — @endif
          </td>
          <td>{{ $p['proveedor'] ?: '—' }}</td>
        </tr>
      @empty
        <tr><td colspan="6" class="c">No hay partidas perdidas.</td></tr>
      @endforelse
    </tbody>
  </table>

  <h2>Cómo solucionarlo y ganar la próxima vez</h2>
  <ol class="recos">
    @foreach($recomendaciones as $reco)
      <li>{{ $reco }}</li>
    @endforeach
  </ol>

  <div class="foot">
    Documento generado automáticamente como antecedente interno de {{ $company['name'] }}.
    Las partidas perdidas no afectan la venta; se conservan para análisis de competencia.
  </div>

</body>
</html>