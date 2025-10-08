<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Venta #{{ $venta->folio }}</title>
  <style>
    *{ box-sizing:border-box; }
    html,body{ margin:0; padding:0; }
    body{ font-family: DejaVu Sans, Helvetica, Arial, sans-serif; color:#0f172a; padding:24px; font-size:12px; }

    h1{ font-size:20px; margin:0 0 6px; }
    h2{ font-size:13px; margin:0 0 6px; color:#475569; text-transform:uppercase; letter-spacing:.06em; }

    .muted{ color:#64748b; font-size:11px; }
    .badge{ display:inline-block; padding:2px 8px; border:1px solid #e5e7eb; border-radius:999px; font-size:11px; color:#475569; }
    .brand{ font-weight:800; letter-spacing:.02em; }

    .card{ border:1px solid #e5e7eb; border-radius:12px; padding:12px; }
    .spacer{ height:10px; }

    /* Tablas */
    table{ width:100%; border-collapse:collapse; }
    th,td{ padding:8px 10px; border-bottom:1px solid #e5e7eb; font-size:12px; }
    thead th{ text-align:left; color:#334155; background:#f8fafc; }
    tfoot td{ border:none; }

    .tr{ text-align:right; }
    .total{ font-weight:700; font-size:14px; }

    /* Layout en tablas (más compatible con Dompdf) */
    .tbl-header{ width:100%; margin-bottom:12px; }
    .tbl-two{ width:100%; table-layout:fixed; margin-bottom:12px; border-spacing:0; border-collapse:separate; }
    .tbl-two td{ vertical-align:top; }

    .logo{ height:48px; }
  </style>
</head>
<body>

  {{-- Encabezado --}}
  <table class="tbl-header">
    <tr>
      <td>
        <div class="brand">VENTA</div>
        <h1>#{{ $venta->folio }}</h1>
        @php $fechaBase = $venta->fecha ?: $venta->created_at; @endphp
        <div class="muted">Fecha: {{ $fechaBase ? \Illuminate\Support\Carbon::parse($fechaBase)->format('d/m/Y') : '—' }}</div>
        @if(!empty($venta->factura_uuid))
          <div class="muted">UUID: {{ $venta->factura_uuid }}</div>
        @endif
      </td>
      <td class="tr" style="width:200px;">
        @if(function_exists('public_path') && file_exists(public_path('logo.png')))
          <img src="{{ public_path('logo.png') }}" class="logo" alt="Logo">
        @else
          <span class="badge">Tu logo</span>
        @endif
      </td>
    </tr>
  </table>

  {{-- Emisor / Cliente --}}
  <table class="tbl-two" cellspacing="0" cellpadding="0">
    <tr>
      <td style="padding-right:8px;">
        <div class="card">
          <h2>Emisor</h2>
          <div>{{ config('app.name', 'Tu Empresa') }}</div>
          <div class="muted">RFC: {{ config('facturaapi.emisor_rfc', '—') }}</div>
          <div class="muted">Lugar expedición: {{ config('facturaapi.lugar_expedicion', config('facturaapi.lugar_exp', '—')) }}</div>
        </div>
      </td>
      <td style="padding-left:8px;">
        <div class="card">
          <h2>Cliente</h2>
          <div>
            {{ optional($venta->cliente)->razon_social
                ?? optional($venta->cliente)->nombre
                ?? optional($venta->cliente)->name
                ?? ('ID '.$venta->cliente_id) }}
          </div>
          <div class="muted">RFC: {{ optional($venta->cliente)->rfc ?? '—' }}</div>
          @if(!empty(optional($venta->cliente)->email))
            <div class="muted">{{ optional($venta->cliente)->email }}</div>
          @endif
        </div>
      </td>
    </tr>
  </table>

  {{-- Conceptos --}}
  <table>
    <thead>
      <tr>
        <th style="width:8%">Cant</th>
        <th>Descripción</th>
        <th style="width:14%" class="tr">P. Unit</th>
        <th style="width:12%" class="tr">Desc.</th>
        <th style="width:10%" class="tr">IVA</th>
        <th style="width:16%" class="tr">Importe</th>
      </tr>
    </thead>
    <tbody>
      @foreach($venta->items as $it)
        @php
          $cant   = (float)($it->cantidad ?? 0);
          $pu     = (float)($it->precio_unitario ?? 0);
          $desc   = (float)($it->descuento ?? 0);
          $ivaPct = (float)($it->iva_porcentaje ?? 0);
          $base   = max(0, round($cant * $pu - $desc, 2));
          $iva    = round($base * ($ivaPct/100), 2);
          $imp    = round($base + $iva, 2);
        @endphp
        <tr>
          <td>{{ number_format($cant, 2) }}</td>
          <td>{{ $it->descripcion }}</td>
          <td class="tr">${{ number_format($pu, 2) }}</td>
          <td class="tr">${{ number_format($desc, 2) }}</td>
          <td class="tr">{{ number_format($ivaPct, 2) }}%</td>
          <td class="tr">${{ number_format($imp, 2) }}</td>
        </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr><td colspan="6" class="spacer"></td></tr>
      <tr>
        <td colspan="5" class="tr">Subtotal</td>
        <td class="tr">${{ number_format((float)$venta->subtotal, 2) }}</td>
      </tr>
      <tr>
        <td colspan="5" class="tr">Descuento</td>
        <td class="tr">-${{ number_format((float)$venta->descuento, 2) }}</td>
      </tr>
      <tr>
        <td colspan="5" class="tr">Envío</td>
        <td class="tr">${{ number_format((float)$venta->envio, 2) }}</td>
      </tr>
      <tr>
        <td colspan="5" class="tr">IVA</td>
        <td class="tr">${{ number_format((float)$venta->iva, 2) }}</td>
      </tr>
      <tr>
        <td colspan="5" class="tr total">TOTAL ({{ $venta->moneda ?? 'MXN' }})</td>
        <td class="tr total">${{ number_format((float)$venta->total, 2) }}</td>
      </tr>
    </tfoot>
  </table>

  @if(!empty($venta->notas))
    <div class="card" style="margin-top:12px">
      <h2>Notas</h2>
      <div style="font-size:12px; color:#334155;">{{ $venta->notas }}</div>
    </div>
  @endif

</body>
</html>
