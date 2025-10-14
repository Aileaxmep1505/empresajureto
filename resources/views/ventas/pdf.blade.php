<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Remisión {{ date('Y') }}-{{ $venta->id }}</title>
  <style>
    /* ===== Página (Dompdf) ===== */
    @page { size: letter; margin: 18mm 16mm 34mm 16mm; } /* top right bottom left */

    body{ margin:0; padding:0; font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size:12px; color:#0E1A2B; }
    *{ box-sizing:border-box; }

    :root{
      --ink:#0E1A2B; --muted:#6B7280; --line:#EAECEF; --soft:#F7F9FC;
      --brand:#1e73be; --brand-ink:#124C8A; --accent:#C62828; --sep:#EDF1F6;
    }

    .caps{ text-transform:uppercase; letter-spacing:.14em; }
    .strong{ font-weight:700; color:var(--ink); }
    .small{ font-size:11px; color:#5B6470; }

    /* ===== Header (compacto, sin aire) ===== */
    .doc-header{ position:fixed; top:0; left:0; right:0; height:64px; background:#fff; padding:8px 16mm 8px; }
    .hdr-table{ width:100%; border-collapse:collapse; }
    .logo{ height:38px; }
    .rem{ color:var(--accent); font-weight:800; font-size:12px; text-align:right; }
    .meta{ color:#4B5B6C; font-size:11px; }

    /* Separador sutil bajo header */
    .rule{ height:1px; background:var(--sep); margin:6px 0 0; }

    /* ===== Footer fijo (firma + divisor + datos) ===== */
    .doc-footer{ position:fixed; left:0; right:0; bottom:0; height:90px; background:#fff; padding:10px 16mm 8px; }
    .footer-grid{ width:100%; border-collapse:collapse; table-layout:fixed; }
    .foot-left{ width:62%; vertical-align:middle; }
    .foot-right{ width:36%; vertical-align:middle; }
    .foot-gap{ width:2%; }
    .sig-row{ width:100%; border-collapse:collapse; }
    .sig-img{ height:34px; display:block; }
    .sig-pad{ padding-right:12px; }
    .person-name{ font-weight:700; font-size:13px; color:#202B3A; line-height:1.1; }
    .person-role{ color:#7A8491; font-size:11px; line-height:1.1; white-space:nowrap; }
    .v-divider{ width:1px; height:46px; background:#C9D3DE; display:block; margin:0 auto; }
    .contact{ font-size:11px; color:#111827; line-height:1.5; }
    .contact b{ color:#4E5968; }

    /* ===== Contenido (reserva header/footer) ===== */
    .content{ padding:72px 0 110px; } /* 64px header + margen | 90px footer + margen */

    /* ===== Banda de datos (sin recuadro, como el ejemplo) ===== */
    .band{ margin:8px 0 10px; }
    .band-table{ width:100%; border-collapse:collapse; table-layout:fixed; }
    .band-left{ vertical-align:top; padding-right:10px; }
    .band-right{ vertical-align:top; padding-left:10px; text-align:right; }
    .row{ padding:2px 0; line-height:1.35; }
    .lbl{ font-weight:700; color:#1F2937; }
    .val{ color:#111827; }
    .upper{ text-transform:uppercase; }

    /* ===== Tabla de productos (limpia) ===== */
    table.items{ width:100%; border-collapse:collapse; }
    .items thead th{
      padding:8px 10px;
      background:var(--brand);
      color:#fff; text-align:left; font-weight:700; border-bottom:none;
    }
    .items td{ padding:8px 10px; border-bottom:1px solid var(--line); vertical-align:top; }
    .items tbody tr:last-child td{ border-bottom:1px solid var(--line); }
    .items .tr{ text-align:right; }
    .thumb{ width:50px; height:auto; }

    /* Totales */
    .totals{ width:100%; margin-top:8px; border-collapse:collapse; }
    .totals td{ padding:4px 10px; }
    .totals .lbl{ text-align:right; color:#374151; }
    .totals .val{ text-align:right; font-weight:700; }

    /* Paginación (opcional) */
    .page-num{ font-size:10px; color:#9aa3ad; text-align:right; }

    /* Largas tablas: mantener thead en cada salto */
    thead{ display:table-header-group; }
    tfoot{ display:table-row-group; }
    tr, .card{ page-break-inside:avoid; }
  </style>
</head>
<body>

  {{-- ===== Header ===== --}}
  <div class="doc-header">
    <table class="hdr-table">
      <tr>
        <td style="vertical-align:middle;">
          @php 
            $logo1 = public_path('images/logomedy.png');
            $logo2 = public_path('images/logo-mail.png');
            $logo = file_exists($logo1) ? $logo1 : (file_exists($logo2) ? $logo2 : null);
          @endphp
          @if($logo)
            <img src="{{ $logo }}" class="logo" alt="Logo">
          @else
            <span class="caps strong">{{ config('app.name','Tu Empresa') }}</span>
          @endif
        </td>
        <td style="vertical-align:middle; text-align:right;">
          <div class="rem">Remisión:<br> No.{{ date('Y') }}-{{ $venta->id }}</div>
        </td>
      </tr>
    </table>
    <div class="rule"></div>
  </div>

  {{-- ===== Contenido ===== --}}
  <div class="content">

    {{-- Banda de información (como tu ejemplo, sin cuadro) --}}
    @php
      $fechaBase = $venta->created_at ?? $venta->fecha ?? now();
      $clienteNombre = trim(($venta->cliente->nombre ?? '').' '.($venta->cliente->apellido ?? ''));
      if ($clienteNombre === '') $clienteNombre = $venta->cliente->razon_social ?? $venta->cliente->name ?? 'DESCONOCIDO';
      $clienteTel = $venta->cliente->telefono ?? $venta->cliente->phone ?? $venta->cliente->celular ?? 'DESCONOCIDO';
      $clienteDir = $venta->cliente->comentarios ?? $venta->cliente->direccion ?? $venta->cliente->domicilio ?? 'DESCONOCIDO';
      $lugar      = $venta->lugar ?? (config('facturaapi.lugar_expedicion') ?? '—');
      $rfcEmisor  = config('facturaapi.emisor_rfc', '—');
      $emisorNom  = config('app.razon_social') ?? config('app.name', '—');
      $regimen    = config('facturaapi.regimen_fiscal_descripcion') ?? config('facturaapi.regimen_fiscal') ?? '—';
    @endphp

    <div class="band">
      <table class="band-table">
        <tr>
          <td class="band-left">
            <div class="row"><span class="lbl">CLIENTE:</span> <span class="val upper">{{ mb_strtoupper($clienteNombre,'UTF-8') }}</span></div>
            <div class="row"><span class="lbl">TELÉFONO:</span> <span class="val">{{ $clienteTel }}</span></div>
            <div class="row"><span class="lbl">DIRECCIÓN:</span> <span class="val upper">{{ mb_strtoupper($clienteDir,'UTF-8') }}</span></div>
            <div class="row"><span class="lbl">LUGAR:</span> <span class="val upper">{{ mb_strtoupper($lugar,'UTF-8') }}</span></div>
          </td>
          <td class="band-right">
            <div class="row"><span class="lbl">Fecha:</span> <span class="val">{{ \Illuminate\Support\Carbon::parse($fechaBase)->format('d/m/Y H:i') }}</span></div>
            <div class="row"><span class="lbl">RFC Emisor:</span> <span class="val upper">{{ $rfcEmisor }}</span></div>
            <div class="row"><span class="lbl">EMISOR:</span> <span class="val">{{ $emisorNom }}</span></div>
            <div class="row"><span class="lbl">Régimen Fiscal:</span> <span class="val" style="font-style:italic;">{{ $regimen }}</span></div>
          </td>
        </tr>
      </table>
    </div>

    {{-- Tabla de productos (usa $venta->productos; si no existe, cae a $venta->items) --}}
    <table class="items">
      <thead>
        <tr>
          <th style="width:14%">Equipo</th>
          <th>Descripción</th>
          <th style="width:12%" class="tr">Cantidad</th>
          <th style="width:16%" class="tr">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        @php
          $rows = isset($venta->productos) && count($venta->productos) ? $venta->productos : $venta->items ?? [];
        @endphp

        @foreach($rows as $item)
          @php
            // Normalizamos campos entre productos/items
            $prod = $item->producto ?? null;
            $tipo = $prod->tipo_equipo ?? ($item->descripcion ?? '—');
            $modelo = $prod->modelo ?? ($item->modelo ?? '');
            $marca  = $prod->marca  ?? ($item->marca  ?? '');
            $serie  = $item->registro->numero_serie ?? $item->serie ?? null;
            $imgRel = $prod->imagen ?? null;

            $cantidad = $item->cantidad ?? 1;
            $subtotal = $item->subtotal ?? ($item->precio_unitario ?? 0) * $cantidad;

            $imgPath = $imgRel ? public_path('storage/'.$imgRel) : null;
          @endphp
          <tr>
            <td>
              @if($imgPath && file_exists($imgPath))
                <img src="{{ $imgPath }}" class="thumb" alt="Producto">
              @else
                <span class="small" style="color:#9aa3ad">Sin imagen</span>
              @endif
            </td>
            <td>
              <div class="strong upper">{{ mb_strtoupper($tipo,'UTF-8') }}</div>
              <div class="small">{{ mb_strtoupper(trim(($modelo ? $modelo.' | ' : '').$marca),'UTF-8') }}</div>
              @if($serie)
                <div class="small" style="color:#1a73e8;">Serie: {{ $serie }}</div>
              @else
                <div class="small" style="color:#9aa3ad;">Sin número de serie</div>
              @endif
            </td>
            <td class="tr">{{ number_format($cantidad, 0) }}</td>
            <td class="tr">${{ number_format((float)$subtotal, 2) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>

    {{-- Totales --}}
    <table class="totals">
      <tr>
        <td class="lbl" style="width:84%;">Subtotal:</td>
        <td class="val" style="width:16%;">${{ number_format((float)($venta->subtotal ?? 0), 2) }}</td>
      </tr>
      @if(($venta->descuento ?? 0) > 0)
      <tr>
        <td class="lbl">Descuento:</td>
        <td class="val">-${{ number_format((float)$venta->descuento, 2) }}</td>
      </tr>
      @endif
      @if(($venta->envio ?? 0) > 0)
      <tr>
        <td class="lbl">Envío:</td>
        <td class="val">${{ number_format((float)$venta->envio, 2) }}</td>
      </tr>
      @endif
      @if(($venta->iva ?? 0) > 0)
      <tr>
        <td class="lbl">IVA:</td>
        <td class="val">${{ number_format((float)$venta->iva, 2) }}</td>
      </tr>
      @endif
      <tr>
        <td class="lbl strong">Total:</td>
        <td class="val strong">${{ number_format((float)($venta->total ?? 0), 2) }}</td>
      </tr>
    </table>

    {{-- Nota --}}
    @if(!empty($venta->nota))
      <div style="margin-top:8px; font-size:12px; color:#2A3442; line-height:1.45;">
        <span class="strong">Nota:</span> {{ $venta->nota }}
      </div>
    @endif

    {{-- QR opcional --}}
    @if(!empty($qr))
      <div style="text-align:center; margin-top:10px;">
        <div class="small" style="color:#333; margin-bottom:6px;"><strong>Escanea este código QR para acceder a esta venta:</strong></div>
        <img src="data:image/png;base64,{{ $qr }}" alt="QR" style="width:100px; height:100px;">
      </div>
    @endif

  </div> <!-- /content -->

  {{-- ===== Footer ===== --}}
  <div class="doc-footer">
    <table class="footer-grid">
      <tr>
        <td class="foot-left">
          <table class="sig-row">
            <tr>
              <td class="sig-pad" style="width:150px;" valign="middle">
                @php $firma = public_path('images/firma.png'); @endphp
                @if(file_exists($firma))
                  <img src="{{ $firma }}" class="sig-img" alt="Firma">
                @else
                  <div style="height:34px;"></div>
                @endif
              </td>
              <td valign="middle" style="padding-right:18px;">
                <div class="person-name">Rene Tort</div>
                <div class="person-role">Gerente General</div>
              </td>
              <td valign="middle" style="width:1%;">
                <span class="v-divider"></span>
              </td>
            </tr>
          </table>
        </td>
        <td class="foot-right">
          <div class="contact" style="padding-left:18px;">
            <div><b>Tel:</b> +52 55 4193 7243</div>
            <div><b>Email:</b> rtort@jureto.com.mx</div>
            <div><b>Web:</b> jureto.com.mx</div>
            <div><b>Ubicación:</b> Estado de México C.P. 52060</div>
          </div>
        </td>
        <td class="foot-gap"></td>
      </tr>
    </table>
  </div>

  {{-- (Opcional) Paginación abajo-derecha — requiere enable_php=true --}}
  <script type="text/php">
    if (isset($pdf)) {
      $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
      $size = 9;
      $font = $fontMetrics->get_font("DejaVu Sans", "normal");
      $w = $pdf->get_width(); $h = $pdf->get_height();
      $tw = $fontMetrics->getTextWidth($text, $font, $size);
      $x = $w - (16 * 2.83465) - $tw;  // 16mm del borde derecho
      $y = $h - (6  * 2.83465);        // 6mm del borde inferior
      $pdf->page_text($x, $y, $text, $font, $size, array(0.38,0.42,0.49));
    }
  </script>
</body>
</html>
