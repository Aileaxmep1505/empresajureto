@php
  // Personaliza tu logo
  $logo = 'https://ai.jureto.com.mx/images/logo-mail.png';

  // Paleta
  $brandInk = '#14206a';
  $muted    = '#667085';
  $ink      = '#0f172a';
  $line     = '#e6e8ef';
  $bg       = '#f6f7fb';

  $cli = $venta->cliente ?? null;

  // Preheader (texto previo que muchos clientes muestran junto al asunto)
  $pre = 'Adjuntamos factura (PDF/XML) y PDF de la venta — ' .
         'Folio: ' . ($venta->folio ?? '') . ' · Total: $' .
         number_format((float)($venta->total ?? 0), 2) . ' ' . ($venta->moneda ?? 'MXN');
@endphp
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Documentos de su compra</title>
  <meta name="color-scheme" content="light">
  <meta name="supported-color-schemes" content="light">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>a{ text-decoration:none }</style>
</head>
<body style="margin:0;background:{{ $bg }};font-family:Arial,Helvetica,sans-serif;">
  <!-- Preheader oculto -->
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">{{ $pre }}</div>

  <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:{{ $bg }};">
    <tr>
      <td align="center" style="padding:32px 12px;">
        <table width="620" cellpadding="0" cellspacing="0" role="presentation" style="max-width:620px;">

          <!-- Masthead -->
          <tr>
            <td align="center" style="padding:0 0 16px;">
              <table role="presentation" cellpadding="0" cellspacing="0" style="border:1px solid {{ $line }};border-radius:14px;background:#fff;">
                <tr>
                  <td style="padding:10px 14px;">
                    <table role="presentation" cellpadding="0" cellspacing="0">
                      <tr>
                        <td valign="middle" style="padding-right:10px;">
                          <img
                            src="{{ $logo }}"
                            alt="{{ config('app.name') }}"
                            width="120"
                            style="display:block;height:36px;width:auto;line-height:100%;border:0;outline:none;text-decoration:none;border-radius:8px;"
                          >
                        </td>
                        <td valign="middle">
                          <span style="font-weight:700;font-size:18px;color:{{ $brandInk }};letter-spacing:.2px;white-space:nowrap;">
                            {{ config('app.name') }}
                          </span>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Tarjeta principal -->
          <tr>
            <td style="background:#fff;border:1px solid {{ $line }};border-radius:18px;box-shadow:0 28px 70px rgba(18,38,63,.10);overflow:hidden;">
              <!-- Hero -->
              <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:
                radial-gradient(650px 120px at 10% 0%, rgba(126,162,255,.15), transparent 60%),
                radial-gradient(650px 120px at 90% 0%, rgba(20,32,106,.08), transparent 60%),#fff;">
                <tr>
                  <td align="center" style="padding:26px 26px 12px;">
                    <div style="display:inline-block;padding:8px 12px;border:1px solid {{ $line }};border-radius:999px;color:{{ $brandInk }};font-size:12px;background:#f9fbff;">
                      Documentos adjuntos
                    </div>
                    <h1 style="margin:14px 0 6px;color:{{ $ink }};font-size:22px;line-height:1.25;">
                      ¡Gracias por su compra!
                    </h1>
                    <p style="margin:0 auto 4px;max-width:520px;color:{{ $muted }};line-height:1.65;">
                      Adjuntamos la <strong>factura (PDF/XML)</strong> y el <strong>PDF de la venta</strong>.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Contenido -->
              <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                  <td style="padding:20px 26px 6px;">
                    <!-- Datos del cliente y venta -->
                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                      <tr>
                        <td valign="top" style="padding-right:10px;">
                          @if($cli && ($cli->name ?? $cli->nombre ?? null))
                            <p style="margin:0 0 6px 0;color:{{ $ink }}"><strong>Cliente:</strong> {{ $cli->name ?? $cli->nombre }}</p>
                          @endif
                          @if(!empty($cli?->email))
                            <p style="margin:0 0 6px 0;color:{{ $muted }}">Email: {{ $cli->email }}</p>
                          @endif
                          @if(!empty($cli?->telefono))
                            <p style="margin:0 0 6px 0;color:{{ $muted }}">Tel: {{ $cli->telefono }}</p>
                          @endif
                        </td>

                        <!-- Resumen a la derecha -->
                        <td valign="top" align="right" style="min-width:220px;">
                          <table role="presentation" cellpadding="0" cellspacing="0" style="min-width:220px;border:1px dashed {{ $line }};border-radius:12px;background:#fbfcff;">
                            <tr>
                              <td style="padding:10px 14px;">
                                <table width="100%" role="presentation" cellpadding="0" cellspacing="0" style="font-size:14px;color:{{ $ink }};">
                                  <tr>
                                    <td style="padding:4px 0;color:#334155;">Folio</td>
                                    <td align="right" style="padding:4px 0;font-weight:700;">{{ $venta->folio }}</td>
                                  </tr>
                                  <tr>
                                    <td style="padding:4px 0;color:#334155;">Subtotal</td>
                                    <td align="right" style="padding:4px 0;">${{ number_format((float)($venta->subtotal ?? 0),2) }}</td>
                                  </tr>
                                  <tr>
                                    <td style="padding:4px 0;color:#334155;">IVA</td>
                                    <td align="right" style="padding:4px 0;">${{ number_format((float)($venta->iva ?? 0),2) }}</td>
                                  </tr>
                                  @php $descG = (float)($venta->descuento ?? 0); @endphp
                                  @if($descG > 0)
                                    <tr>
                                      <td style="padding:4px 0;color:#334155;">Descuento</td>
                                      <td align="right" style="padding:4px 0;">- ${{ number_format($descG,2) }}</td>
                                    </tr>
                                  @endif
                                  @php $env = (float)($venta->envio ?? 0); @endphp
                                  @if($env > 0)
                                    <tr>
                                      <td style="padding:4px 0;color:#334155;">Envío</td>
                                      <td align="right" style="padding:4px 0;">${{ number_format($env,2) }}</td>
                                    </tr>
                                  @endif
                                  <tr>
                                    <td style="padding:8px 0;border-top:1px solid {{ $line }};font-weight:700;">TOTAL</td>
                                    <td align="right" style="padding:8px 0;border-top:1px solid {{ $line }};font-weight:700;">
                                      ${{ number_format((float)($venta->total ?? 0),2) }} {{ $venta->moneda ?? 'MXN' }}
                                    </td>
                                  </tr>
                                </table>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>

                    <!-- Mensaje personalizado -->
                    @if(!empty($mensaje))
                      <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin:18px 0 8px;">
                        <tr>
                          <td style="padding:12px 14px;border:1px solid {{ $line }};border-radius:12px;background:#ffffff;color:{{ $ink }};">
                            <div style="color:{{ $muted }};font-size:13px;margin-bottom:6px;">Mensaje</div>
                            <div style="white-space:pre-line;line-height:1.6;">{{ $mensaje }}</div>
                          </td>
                        </tr>
                      </table>
                    @endif

                    <!-- Aviso de adjuntos -->
                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin:12px 0 6px;">
                      <tr>
                        <td style="padding:10px 12px;border:1px dashed {{ $line }};border-radius:12px;background:#fbfcff;color:{{ $muted }};font-size:13px;">
                          Se incluyen como adjuntos: <strong>Factura (PDF/XML)</strong> y <strong>PDF de la venta</strong>.
                        </td>
                      </tr>
                    </table>

                    <p style="margin:16px 0 0 0;color:{{ $ink }}">Saludos,<br><strong>{{ config('app.name') }}</strong></p>
                  </td>
                </tr>
              </table>

              <!-- Nota dentro de la tarjeta -->
              <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                  <td align="center" style="color:#8a93a6;font-size:12px;padding:8px 26px 20px;">
                    Este mensaje fue enviado automáticamente. Si tienes dudas, responde a este correo.
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer global -->
          <tr>
            <td align="center" style="color:#8a93a6;font-size:12px;padding:16px 6px;">
              © {{ date('Y') }} {{ config('app.name') }} — Todos los derechos reservados.
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
