{{-- resources/views/web/customer/orders/show.blade.php --}}
@extends('layouts.web')

@section('title', 'Detalle del pedido')

@section('content')
@php
  $items = collect($items ?? []);
  $fmt = fn($n) => '$' . number_format((float) $n, 2) . ' MXN';

  $itemsSubtotal = (float) $items->sum(function ($item) {
      $qty = (int) ($item->qty ?? $item->quantity ?? 1);
      $price = (float) ($item->price ?? $item->unit_price ?? 0);
      $amount = (float) ($item->amount ?? $item->total ?? 0);
      return $amount > 0 ? $amount : ($price * max(1, $qty));
  });

  $subtotal = (float) ($order->subtotal ?? 0);
  $subtotal = $itemsSubtotal > 0 ? $itemsSubtotal : $subtotal;

  $shipping = (float) ($order->shipping_amount ?? 0);
  $total = (float) ($order->total ?? ($subtotal + $shipping));

  if ($total <= $subtotal && $shipping > 0) {
      $total = $subtotal + $shipping;
  }

  $rawStatus = strtolower((string) ($order->status ?? 'procesando'));

  $refundedAmount = (float) (
      $order->refunded_amount
      ?? $order->stripe_refunded_amount
      ?? $order->refund_amount
      ?? 0
  );

  $hasRefundDate = !empty($order->refunded_at)
      || !empty($order->stripe_refunded_at)
      || !empty($order->refund_at);

  $isRefunded = in_array($rawStatus, [
          'reembolsado',
          'reembolso_parcial',
          'refunded',
          'partial_refund',
          'refund',
          'devuelto',
          'reembolso',
          'reembolsada'
      ], true)
      || $hasRefundDate
      || $refundedAmount > 0;

  $isPartialRefund = in_array($rawStatus, [
          'reembolso_parcial',
          'partial_refund',
          'partially_refunded'
      ], true)
      || (
          $isRefunded
          && $refundedAmount > 0
          && (float)($order->total ?? 0) > 0
          && $refundedAmount < (float)($order->total ?? 0)
      );

  $statusLabel = $isRefunded
      ? ($isPartialRefund ? 'Reembolso parcial' : 'Reembolsado')
      : match ($rawStatus) {
          'paid', 'pagado' => 'Pagado',
          'pending', 'pendiente' => 'Pendiente',
          'processing', 'procesando' => 'Procesando',
          'completed', 'completado' => 'Completado',
          'cancelled', 'canceled', 'cancelado' => 'Cancelado',
          'failed', 'fallido' => 'Fallido',
          default => ucfirst($order->status ?? 'Procesando'),
      };

  $statusBadgeClass = $isRefunded
      ? 'bad'
      : (in_array($rawStatus, ['cancelado', 'cancelled', 'canceled', 'failed', 'fallido'], true)
          ? 'bad'
          : (in_array($rawStatus, ['pagado', 'paid', 'completado', 'completed', 'entregado'], true)
              ? 'ok'
              : 'info'));

  $address = (array) ($order->address_json ?? []);
  $addressText = trim(
      ($address['street'] ?? $order->customer_address ?? '') . ' ' .
      ($address['ext_number'] ?? '') . ' ' .
      ($address['colony'] ?? '') . ', ' .
      ($address['municipality'] ?? '') . ', ' .
      ($address['state'] ?? '') . ' CP ' .
      ($address['postal_code'] ?? '')
  );

  $trackingNumber = $order->tracking_number
      ?? $order->shipping_tracking_number
      ?? $order->guide_number
      ?? $order->guia
      ?? null;

  $trackingUrl = $order->tracking_url ?? $order->shipping_tracking_url ?? null;
  $labelUrl = $order->label_url ?? $order->shipping_label_url ?? null;

  $shipName = $order->shipping_name ?? $order->shipping_carrier ?? 'Pendiente';
@endphp

<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">

<style>
  :root {
    --bg: #f9fafb;
    --card: #ffffff;
    --ink: #111111;
    --text: #333333;
    --muted: #888888;
    --line: #ebebeb;
    --blue: #007aff;
    --blue-soft: #e6f0ff;
    --success: #15803d;
    --success-soft: #e6ffe6;
    --danger: #ff4a4a;
    --danger-soft: #ffebeb;
  }

  .order-page {
    max-width: 1100px;
    margin: 0 auto;
    padding: 34px 20px;
    font-family: "Quicksand", system-ui, -apple-system, sans-serif;
    color: var(--text);
  }

  .order-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 22px;
  }

  .order-title {
    margin: 0;
    color: var(--ink);
    font-size: clamp(26px, 4vw, 38px);
    font-weight: 700;
    letter-spacing: -.04em;
  }

  .order-sub {
    margin-top: 8px;
    color: var(--muted);
    font-weight: 600;
  }

  .order-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) minmax(310px, .9fr);
    gap: 18px;
  }

  @media(max-width: 900px) {
    .order-head { flex-direction: column; }
    .order-grid { grid-template-columns: 1fr; }
  }

  .card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
    overflow: hidden;
  }

  .card-h {
    padding: 20px;
    border-bottom: 1px solid var(--line);
  }

  .card-h h2 {
    margin: 0;
    color: var(--ink);
    font-size: 18px;
    font-weight: 700;
  }

  .card-b { padding: 20px; }

  .badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 7px 12px;
    font-size: 13px;
    font-weight: 700;
  }

  .badge.ok { background: var(--success-soft); color: var(--success); }
  .badge.bad { background: var(--danger-soft); color: var(--danger); }
  .badge.info { background: var(--blue-soft); color: var(--blue); }

  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    padding: 10px 16px;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    border: 0;
    cursor: pointer;
    font-family: inherit;
    transition: transform .16s ease, background .16s ease;
  }

  .btn:active { transform: scale(.98); }
  .btn-primary { background: var(--blue); color: #fff; }
  .btn-outline { background: #fff; color: var(--blue); border: 1px solid var(--blue); }
  .btn-ghost { background: transparent; color: #555; border: 1px solid var(--line); }
  .btn-ghost:hover { background: #f9fafb; }

  .actions { display: flex; gap: 10px; flex-wrap: wrap; }

  .table { width: 100%; border-collapse: collapse; }

  .table th {
    text-align: left;
    color: var(--muted);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .04em;
    padding: 12px 8px;
    border-bottom: 1px solid var(--line);
  }

  .table td {
    padding: 14px 8px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: top;
    font-weight: 600;
  }

  .summary-row {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    margin: 12px 0;
    font-weight: 600;
  }

  .summary-row.total {
    border-top: 1px solid var(--line);
    padding-top: 14px;
    color: var(--ink);
    font-size: 18px;
    font-weight: 700;
  }

  .info-box {
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 14px;
    background: #fff;
    margin-bottom: 12px;
  }

  .info-label {
    color: var(--muted);
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 5px;
  }

  .info-value {
    color: var(--ink);
    font-weight: 700;
    line-height: 1.5;
  }
</style>

<div class="order-page">
  <div class="order-head">
    <div>
      <h1 class="order-title">Pedido #{{ str_pad((string) $order->id, 6, '0', STR_PAD_LEFT) }}</h1>
      <div class="order-sub">
        Realizado el {{ $order->created_at?->format('d/m/Y H:i') ?? '—' }}
      </div>
    </div>

    <div class="actions">
      <a href="{{ url('/mi-cuenta') }}" class="btn btn-ghost">Volver a mi cuenta</a>

      @if(\Illuminate\Support\Facades\Route::has('customer.orders.syncEnvia'))
        <form action="{{ route('customer.orders.syncEnvia', $order) }}" method="post">
          @csrf
          <button class="btn btn-outline" type="submit">Sincronizar Envia</button>
        </form>
      @endif

      @if(\Illuminate\Support\Facades\Route::has('customer.orders.reorder') && $items->count() > 0)
        <form action="{{ route('customer.orders.reorder', $order) }}" method="post">
          @csrf
          <button class="btn btn-outline" type="submit">Comprar de nuevo</button>
        </form>
      @endif
    </div>
  </div>

  <div class="order-grid">
    <section class="card">
      <div class="card-h">
        <h2>Artículos</h2>
      </div>

      <div class="card-b">
        <table class="table">
          <thead>
            <tr>
              <th>Producto</th>
              <th style="text-align:center;">Cant.</th>
              <th style="text-align:right;">Precio</th>
              <th style="text-align:right;">Importe</th>
            </tr>
          </thead>
          <tbody>
            @forelse($items as $item)
              @php
                $qty = (int) ($item->qty ?? $item->quantity ?? 1);
                $price = (float) ($item->price ?? $item->unit_price ?? 0);
                $amount = (float) ($item->amount ?? $item->total ?? ($price * $qty));
              @endphp
              <tr>
                <td>
                  <strong style="color:var(--ink);">{{ $item->name ?? $item->product_name ?? 'Producto' }}</strong>
                  @if(!empty($item->sku))
                    <div style="color:var(--muted);font-size:13px;margin-top:4px;">SKU: {{ $item->sku }}</div>
                  @endif
                </td>
                <td style="text-align:center;">{{ $qty }}</td>
                <td style="text-align:right;">{{ $fmt($price) }}</td>
                <td style="text-align:right;"><strong>{{ $fmt($amount) }}</strong></td>
              </tr>
            @empty
              <tr>
                <td colspan="4" style="color:var(--muted);">Sin artículos registrados.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    <aside>
      <div class="card" style="margin-bottom:18px;">
        <div class="card-h">
          <h2>Resumen</h2>
        </div>

        <div class="card-b">
          <div style="margin-bottom:16px;">
            <span class="badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>

            @if($isRefunded && $refundedAmount > 0)
              <div style="margin-top:10px;color:var(--muted);font-weight:700;font-size:13px;">
                Reembolso: {{ $fmt($refundedAmount) }}
              </div>
            @endif
          </div>

          <div class="summary-row">
            <span>Subtotal</span>
            <strong>{{ $fmt($subtotal) }}</strong>
          </div>

          <div class="summary-row">
            <span>Envío</span>
            <strong>{{ $shipping > 0 ? $fmt($shipping) : 'GRATIS' }}</strong>
          </div>

          <div class="summary-row total">
            <span>Total</span>
            <span>{{ $fmt($total) }}</span>
          </div>

          @if($isRefunded && $refundedAmount > 0)
            <div class="summary-row" style="color:var(--danger);">
              <span>{{ $isPartialRefund ? 'Reembolso aplicado' : 'Total reembolsado' }}</span>
              <strong>{{ $fmt($refundedAmount) }}</strong>
            </div>
          @endif
        </div>
      </div>

      @if($isRefunded)
        <div class="card" style="margin-bottom:18px;">
          <div class="card-h">
            <h2>Reembolso</h2>
          </div>
          <div class="card-b">
            <div class="info-box">
              <div class="info-label">Estatus</div>
              <div class="info-value">{{ $statusLabel }}</div>
            </div>

            @if($refundedAmount > 0)
              <div class="info-box">
                <div class="info-label">Monto</div>
                <div class="info-value">{{ $fmt($refundedAmount) }}</div>
              </div>
            @endif

            @if(!empty($order->refunded_at))
              <div class="info-box">
                <div class="info-label">Fecha</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($order->refunded_at)->format('d/m/Y H:i') }}</div>
              </div>
            @endif
          </div>
        </div>
      @endif

      <div class="card" style="margin-bottom:18px;">
        <div class="card-h">
          <h2>Envío</h2>
        </div>

        <div class="card-b">
          <div class="info-box">
            <div class="info-label">Paquetería</div>
            <div class="info-value">
              {{ $shipName }}
              @if(!empty($order->shipping_service))
                <br><span style="color:var(--muted);font-weight:600;">{{ $order->shipping_service }}</span>
              @endif
            </div>
          </div>

          <div class="info-box">
            <div class="info-label">Guía</div>
            <div class="info-value">{{ $trackingNumber ?: 'Pendiente' }}</div>
          </div>

          @if(!empty($addressText))
            <div class="info-box">
              <div class="info-label">Dirección</div>
              <div class="info-value">{{ $addressText }}</div>
            </div>
          @endif

          <div class="actions">
            @if($isRefunded)
              <span class="badge bad">Pedido reembolsado</span>
            @else
              @if($trackingUrl)
                <a href="{{ $trackingUrl }}" target="_blank" rel="noopener" class="btn btn-primary">Rastrear</a>
              @endif

              @if($labelUrl)
                <a href="{{ $labelUrl }}" target="_blank" rel="noopener" class="btn btn-outline">Descargar guía</a>
              @endif
            @endif
          </div>
        </div>
      </div>
    </aside>
  </div>
</div>
@endsection
