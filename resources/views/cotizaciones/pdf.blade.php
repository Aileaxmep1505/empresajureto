<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
@page { margin: 28mm 18mm 24mm 18mm; }
*{ font-family: DejaVu Sans, sans-serif; font-size: 12px; color:#1f2937 }
.h1{ font-size:20px; font-weight:700; letter-spacing:.3px; margin-bottom:6px }
.muted{ color:#6b7280 }
.header{ border-bottom:1px solid #e5e7eb; padding-bottom:8px; margin-bottom:14px }
.table{ width:100%; border-collapse:collapse; margin-top:10px }
.table th,.table td{ border-bottom:1px solid #e5e7eb; padding:8px 6px; text-align:left }
.right{ text-align:right }
.small{ font-size:11px; color:#6b7280 }
.totals{ margin-top:10px; width:40%; float:right }
.totals td{ padding:6px 6px }
.footer{ position:fixed; bottom:0; left:0; right:0; border-top:1px solid #e5e7eb; padding-top:6px; font-size:10px }
.badge{ display:inline-block; border:1px solid #e5e7eb; padding:2px 8px; border-radius:999px; font-size:11px }
</style>
</head>
<body>
  <div class="header">
    <div class="h1">COT-{{ $cotizacion->folio }}</div>
    <div class="small">Fecha: {{ $cotizacion->created_at->format('d/m/Y') }} &nbsp; | &nbsp; Vence: {{ $cotizacion->vence_el? $cotizacion->vence_el->format('d/m/Y'):'—' }} &nbsp; | &nbsp; Estado: <span class="badge">{{ strtoupper($cotizacion->estado) }}</span></div>
  </div>

  <table style="width:100%">
    <tr>
      <td>
        <strong>Cliente</strong><br>
        {{ $cotizacion->cliente->name ?? '—' }}<br>
        <span class="muted">RFC / ID: </span> — <br>
        <span class="muted">Contacto: </span> —
      </td>
      <td class="right">
        <strong>Moneda:</strong> {{ $cotizacion->moneda }}<br>
        <strong>Validez:</strong> {{ $cotizacion->validez_dias }} días
      </td>
    </tr>
  </table>

  <table class="table">
    <thead>
      <tr>
        <th>Descripción</th><th class="right">Cant.</th><th class="right">P. Unit.</th><th class="right">Desc.</th><th class="right">IVA%</th><th class="right">Importe</th>
      </tr>
    </thead>
    <tbody>
    @foreach($cotizacion->items as $it)
      <tr>
        <td>{{ $it->descripcion ?? ($it->producto->nombre ?? ('#'.$it->producto_id)) }}</td>
        <td class="right">{{ number_format($it->cantidad,2) }}</td>
        <td class="right">${{ number_format($it->precio_unitario,2) }}</td>
        <td class="right">${{ number_format($it->descuento,2) }}</td>
        <td class="right">{{ number_format($it->iva_porcentaje,2) }}%</td>
        <td class="right">${{ number_format($it->importe,2) }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>

  <table class="totals">
    <tr><td>Subtotal</td><td class="right">${{ number_format($cotizacion->subtotal,2) }}</td></tr>
    <tr><td>Descuento</td><td class="right">- ${{ number_format($cotizacion->descuento,2) }}</td></tr>
    <tr><td>Envío</td><td class="right">${{ number_format($cotizacion->envio,2) }}</td></tr>
    <tr><td>IVA</td><td class="right">${{ number_format($cotizacion->iva,2) }}</td></tr>
    <tr><td><strong>Total</strong></td><td class="right"><strong>${{ number_format($cotizacion->total,2) }} {{ $cotizacion->moneda }}</strong></td></tr>
  </table>

  @if($cotizacion->notas)
  <div style="clear:both; margin-top:18px">
    <strong>Notas:</strong>
    <div class="small">{!! nl2br(e($cotizacion->notas)) !!}</div>
  </div>
  @endif

  @if($cotizacion->plazos->count())
  <div style="clear:both; margin-top:18px">
    <strong>Plan de financiamiento</strong>
    <table class="table" style="margin-top:6px">
      <thead><tr><th>#</th><th>Vence</th><th class="right">Monto</th><th>Estado</th></tr></thead>
      <tbody>
      @foreach($cotizacion->plazos as $pz)
        <tr>
          <td>{{ $pz->numero }}</td>
          <td>{{ $pz->vence_el->format('d/m/Y') }}</td>
          <td class="right">${{ number_format($pz->monto,2) }}</td>
          <td>{{ $pz->pagado ? 'Pagado' : 'Pendiente' }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  @endif

  <div class="footer">
    Documento generado automáticamente. Formato minimalista institucional. No requiere firma autógrafa para su validez comercial.
  </div>
</body>
</html>
