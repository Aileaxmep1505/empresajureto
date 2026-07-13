@php
  /** @var \App\Models\Order $order */
  $o = $order;
  $isAdmin = $isAdmin ?? false;

  $fmt = fn($n) => '$'.number_format((float)$n, 2, '.', ',').' MXN';

  $items = $o->relationLoaded('items') ? $o->items : $o->items()->get();

  $shippingAmount = (float)($o->shipping_amount ?? 0);
  $subtotal = (float)($o->subtotal ?? 0);
  $total = (float)($o->total ?? ($subtotal + $shippingAmount));

  $shippingName = $o->shipping_name
      ?? $o->shipping_carrier
      ?? 'Envío estándar';

  $shippingService = $o->shipping_service ?? null;
  $shippingEta = $o->shipping_eta ?? null;

  $address = (array)($o->address_json ?? []);
  $addressText = trim(
      ($address['street'] ?? $o->customer_address ?? '') . ' ' .
      ($address['ext_number'] ?? '') . ' ' .
      ($address['colony'] ?? '') . ', ' .
      ($address['municipality'] ?? '') . ', ' .
      ($address['state'] ?? '') . ' CP ' .
      ($address['postal_code'] ?? '')
  );

  $trackingNumber = $o->tracking_number ?? $o->shipping_tracking_number ?? null;
  $trackingUrl = $o->tracking_url ?? $o->shipping_tracking_url ?? null;
  $labelUrl = $o->label_url ?? $o->shipping_label_url ?? null;

  $invoice = (array)($invoice ?? []);
@endphp
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>{{ $isAdmin ? 'Nueva venta' : 'Confirmación de compra' }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;background:#f6f8fb;font-family:Arial,Helvetica,sans-serif;color:#333;">
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
    {{ $isAdmin ? 'Nueva venta pagada' : 'Gracias por tu compra' }} — Pedido #{{ str_pad((string)$o->id, 6, '0', STR_PAD_LEFT) }}
  </div>

  <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f6f8fb;padding:24px;">
    <tr>
      <td align="center">
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:720px;background:#ffffff;border:1px solid #e8eef6;border-radius:16px;overflow:hidden;">
          <tr>
            <td style="padding:28px 28px 16px;">
              <h2 style="margin:0;color:#0f172a;font-size:26px;line-height:1.2;">
                {{ $isAdmin ? 'Nueva venta pagada' : '¡Gracias por tu compra!' }}
              </h2>
              <p style="margin:8px 0 0;color:#64748b;font-size:15px;line-height:1.5;">
                {{ config('app.name') }} — Pedido #{{ str_pad((string)$o->id, 6, '0', STR_PAD_LEFT) }}
                @if($o->created_at)
                  · {{ $o->created_at->format('d/m/Y H:i') }}
                @endif
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding:0 28px 10px;">
              <div style="padding:16px;border:1px solid #e8eef6;border-radius:14px;background:#fbfdff;">
                <strong style="color:#0f172a;">Cliente:</strong> {{ $o->customer_name ?? $o->user?->name ?? 'Cliente' }}<br>
                <strong style="color:#0f172a;">Correo:</strong> {{ $o->customer_email ?? $o->user?->email ?? '—' }}<br>
                <strong style="color:#0f172a;">Estado:</strong> {{ strtoupper((string)($o->status ?? 'pagado')) }}
              </div>
            </td>
          </tr>

          <tr>
            <td style="padding:0 28px 10px;">
              <div style="padding:16px;border:1px solid #e8eef6;border-radius:14px;background:#fbfdff;">
                <strong style="color:#0f172a;">Envío:</strong>
                {{ $shippingName }}
                @if($shippingService) — {{ $shippingService }} @endif
                @if($shippingEta) ({{ $shippingEta }}) @endif
                <br>

                @if($shippingAmount > 0)
                  <strong style="color:#0f172a;">Costo de envío:</strong> {{ $fmt($shippingAmount) }}<br>
                @else
                  <strong style="color:#0f172a;">Costo de envío:</strong> GRATIS<br>
                @endif

                @if($trackingNumber)
                  <strong style="color:#0f172a;">Guía:</strong> {{ $trackingNumber }}<br>
                @endif

                @if(!empty($addressText))
                  <strong style="color:#0f172a;">Dirección:</strong> {{ $addressText }}
                @endif
              </div>
            </td>
          </tr>

          @if($trackingUrl || $labelUrl)
            <tr>
              <td style="padding:0 28px 10px;">
                @if($trackingUrl)
                  <a href="{{ $trackingUrl }}" style="display:inline-block;background:#007aff;color:#fff;text-decoration:none;padding:10px 14px;border-radius:999px;font-weight:bold;margin:4px 8px 4px 0;">
                    Rastrear pedido
                  </a>
                @endif

                @if($labelUrl)
                  <a href="{{ $labelUrl }}" style="display:inline-block;background:#ffffff;color:#007aff;text-decoration:none;padding:10px 14px;border:1px solid #007aff;border-radius:999px;font-weight:bold;margin:4px 8px 4px 0;">
                    Descargar guía
                  </a>
                @endif
              </td>
            </tr>
          @endif

          <tr>
            <td style="padding:0 28px;">
              <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;margin:12px 0;">
                <thead>
                  <tr>
                    <th align="left" style="border-bottom:1px solid #e8eef6;padding:10px 8px;color:#334155;font-size:13px;">Artículo</th>
                    <th align="center" style="border-bottom:1px solid #e8eef6;padding:10px 8px;color:#334155;font-size:13px;">Cant.</th>
                    <th align="right" style="border-bottom:1px solid #e8eef6;padding:10px 8px;color:#334155;font-size:13px;">Precio</th>
                    <th align="right" style="border-bottom:1px solid #e8eef6;padding:10px 8px;color:#334155;font-size:13px;">Importe</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($items as $it)
                    @php
                      $qty = (int)($it->qty ?? $it->quantity ?? 1);
                      $price = (float)($it->price ?? $it->unit_price ?? 0);
                      $amount = (float)($it->amount ?? ($price * $qty));
                    @endphp
                    <tr>
                      <td style="padding:10px 8px;border-bottom:1px solid #f1f5f9;color:#0f172a;">{{ $it->name ?? $it->product_name ?? 'Producto' }}</td>
                      <td align="center" style="padding:10px 8px;border-bottom:1px solid #f1f5f9;color:#475569;">{{ $qty }}</td>
                      <td align="right" style="padding:10px 8px;border-bottom:1px solid #f1f5f9;color:#475569;">{{ $fmt($price) }}</td>
                      <td align="right" style="padding:10px 8px;border-bottom:1px solid #f1f5f9;color:#0f172a;font-weight:bold;">{{ $fmt($amount) }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="4" style="padding:14px 8px;color:#64748b;">Sin partidas.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding:0 28px 20px;">
              <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                  <td align="right" style="padding:5px 8px;color:#64748b;">Subtotal</td>
                  <td align="right" style="padding:5px 8px;width:150px;color:#0f172a;"><strong>{{ $fmt($subtotal) }}</strong></td>
                </tr>
                <tr>
                  <td align="right" style="padding:5px 8px;color:#64748b;">Envío</td>
                  <td align="right" style="padding:5px 8px;color:#0f172a;"><strong>{{ $shippingAmount > 0 ? $fmt($shippingAmount) : 'GRATIS' }}</strong></td>
                </tr>
                <tr>
                  <td align="right" style="padding:8px 8px;color:#0f172a;font-size:18px;">Total pagado</td>
                  <td align="right" style="padding:8px 8px;color:#0f172a;font-size:18px;"><strong>{{ $fmt($total) }}</strong></td>
                </tr>
              </table>
            </td>
          </tr>

          @if(!empty($invoice['id']))
            <tr>
              <td style="padding:0 28px 20px;">
                <a href="{{ route('checkout.invoice.pdf', $invoice['id']) }}" style="display:inline-block;padding:10px 14px;border:1px solid #0f172a;border-radius:10px;text-decoration:none;color:#0f172a;margin:4px 8px 4px 0;">Descargar PDF</a>
                <a href="{{ route('checkout.invoice.xml', $invoice['id']) }}" style="display:inline-block;padding:10px 14px;border:1px solid #0f172a;border-radius:10px;text-decoration:none;color:#0f172a;margin:4px 8px 4px 0;">Descargar XML</a>
              </td>
            </tr>
          @endif

          <tr>
            <td style="padding:16px 28px 28px;color:#64748b;font-size:14px;line-height:1.5;">
              {{ $isAdmin ? 'Este correo es un aviso interno.' : 'Gracias por tu preferencia. Te avisaremos cuando tu pedido sea preparado y enviado.' }}
            </td>
          </tr>
        </table>

        <div style="max-width:720px;margin:14px auto 0;text-align:center;color:#94a3b8;font-size:12px;">
          © {{ date('Y') }} {{ config('app.name') }} — Todos los derechos reservados.
        </div>
      </td>
    </tr>
  </table>
</body>
</html>
