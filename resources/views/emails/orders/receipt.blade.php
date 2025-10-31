@php
  $o = $order;
  $isAdmin = $isAdmin ?? false;
  $fmt = fn($n) => '$'.number_format((float)$n, 2, '.', ',').' MXN';
@endphp
<!doctype html>
<html lang="es">
  <body style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif; background:#f6f8fb; padding:24px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:680px;margin:0 auto;background:#fff;border:1px solid #e8eef6;border-radius:12px;">
      <tr>
        <td style="padding:24px 24px 8px;">
          <h2 style="margin:0 0 4px;color:#0f172a;">
            {{ $isAdmin ? 'Nueva venta' : '¡Gracias por tu compra!' }}
          </h2>
          <p style="margin:0;color:#475569">
            {{ config('app.name') }} — Orden #{{ $o->id }} · {{ $o->created_at?->format('d/m/Y H:i') }}
          </p>
        </td>
      </tr>

      <tr><td style="padding:8px 24px 0;">
        <div style="margin:12px 0;padding:12px;border:1px solid #e8eef6;border-radius:10px;background:#fbfdff;">
          <strong>Cliente:</strong> {{ $o->customer_name ?? 'Cliente' }}<br>
          <strong>Correo:</strong> {{ $o->customer_email ?? '—' }}<br>
          <strong>Estado:</strong> {{ strtoupper($o->status) }}
        </div>
      </td></tr>

      @if($o->address_json)
      <tr><td style="padding:0 24px;">
        <div style="margin:12px 0;padding:12px;border:1px solid #e8eef6;border-radius:10px;background:#fbfdff;">
          <strong>Envío:</strong>
          {{ $o->shipping_name }} {{ $o->shipping_service ? '— '.$o->shipping_service : '' }}
          @if($o->shipping_eta) ({{ $o->shipping_eta }}) @endif
          <br>
          <strong>Dirección:</strong>
          {{ $o->address_json['street'] ?? '' }} {{ $o->address_json['ext_number'] ?? '' }}
          {{ $o->address_json['colony'] ?? '' }}, {{ $o->address_json['municipality'] ?? '' }},
          {{ $o->address_json['state'] ?? '' }}, CP {{ $o->address_json['postal_code'] ?? '' }}
        </div>
      </td></tr>
      @endif

      <tr><td style="padding:0 24px;">
        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:8px 0">
          <thead>
            <tr>
              <th align="left" style="border-bottom:1px solid #e8eef6;padding:8px;color:#334155;">Artículo</th>
              <th align="center" style="border-bottom:1px solid #e8eef6;padding:8px;color:#334155;">Cant</th>
              <th align="right" style="border-bottom:1px solid #e8eef6;padding:8px;color:#334155;">Precio</th>
              <th align="right" style="border-bottom:1px solid #e8eef6;padding:8px;color:#334155;">Importe</th>
            </tr>
          </thead>
          <tbody>
            @forelse($o->items as $it)
            <tr>
              <td style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $it->name }}</td>
              <td align="center" style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $it->qty }}</td>
              <td align="right" style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $fmt($it->price) }}</td>
              <td align="right" style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $fmt($it->amount) }}</td>
            </tr>
            @empty
            <tr><td colspan="4" style="padding:12px;color:#64748b;">Sin partidas.</td></tr>
            @endforelse
          </tbody>
        </table>
      </td></tr>

      <tr><td style="padding:0 24px 16px;">
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td align="right" style="padding:4px 8px;color:#475569;">Subtotal</td>
            <td align="right" style="padding:4px 8px;"><strong>{{ $fmt($o->subtotal) }}</strong></td>
          </tr>
          <tr>
            <td align="right" style="padding:4px 8px;color:#475569;">Envío</td>
            <td align="right" style="padding:4px 8px;"><strong>{{ $fmt($o->shipping_amount) }}</strong></td>
          </tr>
          <tr>
            <td align="right" style="padding:6px 8px;color:#0f172a;font-size:18px;">Total pagado</td>
            <td align="right" style="padding:6px 8px;color:#0f172a;font-size:18px;"><strong>{{ $fmt($o->total) }}</strong></td>
          </tr>
        </table>
      </td></tr>

      @if(!empty($invoice['id']))
      <tr><td style="padding:0 24px 16px;">
        <div style="margin-top:8px">
          <a href="{{ route('checkout.invoice.pdf', $invoice['id']) }}" style="display:inline-block;padding:10px 14px;border:1px solid #0f172a;border-radius:10px;text-decoration:none;color:#0f172a;">Descargar PDF</a>
          <a href="{{ route('checkout.invoice.xml', $invoice['id']) }}" style="display:inline-block;padding:10px 14px;border:1px solid #0f172a;border-radius:10px;text-decoration:none;color:#0f172a;margin-left:8px;">Descargar XML</a>
        </div>
      </td></tr>
      @endif

      <tr><td style="padding:8px 24px 24px;color:#64748b;">
        {{ $isAdmin ? 'Este correo es un aviso interno.' : 'Gracias por tu preferencia.' }}
      </td></tr>
    </table>
  </body>
</html>
