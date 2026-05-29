<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: DejaVu Sans, Arial, sans-serif; color:#222; font-size:12px; }
    h1 { font-size:18px; margin:0; }
    .muted { color:#666; }
    .head { display:flex; justify-content:space-between; margin-bottom:18px; }
    .box { border:1px solid #ddd; border-radius:6px; padding:10px; margin-bottom:14px; }
    table { width:100%; border-collapse:collapse; font-size:11px; }
    th, td { border:1px solid #ddd; padding:6px; text-align:left; vertical-align:top; }
    th { background:#f3f4f6; }
    .right { text-align:right; }
    .firma { margin-top:50px; text-align:center; }
    .firma-line { border-top:1px solid #333; width:240px; margin:0 auto; padding-top:6px; }
  </style>
</head>
<body>
  <div class="head">
    <div>
      <h1>JURETO S.A. DE C.V.</h1>
      <div class="muted">Remisión</div>
    </div>
    <div class="right">
      <div><strong>Folio:</strong> {{ $remision->folio ?: ('REM-'.$remision->id) }}</div>
      <div><strong>Fecha:</strong> {{ optional($remision->fecha)->format('d/m/Y') }}</div>
      <div><strong>Estatus:</strong> {{ ucfirst($remision->status) }}</div>
    </div>
  </div>

  <div class="box">
    <strong>Cliente:</strong>
    {{ optional($remision->adjudicacion->client)->razon_social
        ?: optional($remision->adjudicacion->client)->nombre
        ?: optional($remision->adjudicacion)->cliente_nombre ?: '—' }}<br>
    <strong>Adjudicación:</strong> {{ optional($remision->adjudicacion)->folio ?: ('ADJ-'.$remision->adjudicacion_id) }}<br>
    <strong>Recibe:</strong> {{ $remision->recibe_nombre ?: '—' }}
  </div>

  <table>
    <thead>
      <tr><th>#</th><th>Descripción</th><th>Unidad</th><th class="right">Cantidad</th><th class="right">Precio</th><th class="right">Importe</th></tr>
    </thead>
    <tbody>
      @php $tot = 0; @endphp
      @foreach($remision->items as $it)
        @php $imp = (float)$it->subtotal; $tot += $imp; @endphp
        <tr>
          <td>{{ $loop->iteration }}</td>
          <td>{{ $it->descripcion }}</td>
          <td>{{ $it->unidad }}</td>
          <td class="right">{{ rtrim(rtrim(number_format($it->cantidad,2),'0'),'.') }}</td>
          <td class="right">${{ number_format($it->precio_unitario,2) }}</td>
          <td class="right">${{ number_format($imp,2) }}</td>
        </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr><td colspan="5" class="right"><strong>Total</strong></td><td class="right"><strong>${{ number_format($tot,2) }}</strong></td></tr>
    </tfoot>
  </table>

  @if($remision->observaciones)
    <div class="box" style="margin-top:14px;"><strong>Observaciones:</strong> {{ $remision->observaciones }}</div>
  @endif

  <div class="firma">
    <div class="firma-line">Recibí de conformidad</div>
  </div>
</body>
</html> 