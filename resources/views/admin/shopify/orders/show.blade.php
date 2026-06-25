@extends('layouts.app')

@section('title', 'Detalle venta Shopify')
@section('titulo', 'Detalle venta Shopify')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">

<style>
  :root {
    --bg: #f9fafb;
    --card: #ffffff;
    --ink: #333333;
    --muted: #888888;
    --line: #ebebeb;
    --blue: #007aff;
    --blue-soft: #e6f0ff;
    --success: #15803d;
    --success-soft: #e6ffe6;
    --danger: #ff4a4a;
    --danger-soft: #ffebeb;
  }

  body {
    background: var(--bg);
    font-family: "Quicksand", system-ui, sans-serif;
    color: var(--ink);
  }

  .detail-page {
    max-width: 1100px;
    margin: 0 auto;
    padding: 32px 24px 56px;
  }

  .detail-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 18px;
    flex-wrap: wrap;
    margin-bottom: 24px;
  }

  .detail-header h1 {
    margin: 0;
    color: #111111;
    font-size: 2rem;
    font-weight: 700;
    letter-spacing: -0.03em;
  }

  .detail-header p {
    margin: 8px 0 0;
    color: var(--muted);
    font-weight: 600;
  }

  .btn-ghost,
  .btn-outline {
    min-height: 42px;
    border-radius: 999px;
    padding: 0 18px;
    font-family: inherit;
    font-size: 0.92rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
  }

  .btn-ghost {
    background: transparent;
    color: #555555;
    border: none;
  }

  .btn-outline {
    background: #ffffff;
    color: var(--blue);
    border: 1px solid var(--blue);
  }

  .btn-ghost:hover,
  .btn-outline:hover {
    transform: translateY(-1px);
    background: #f9fafb;
  }

  .grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 20px;
    align-items: start;
  }

  .card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    transition: all 0.2s ease;
  }

  .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.04);
  }

  .card-header {
    padding: 20px 22px;
    border-bottom: 1px solid var(--line);
  }

  .card-header h2 {
    margin: 0;
    color: #111111;
    font-size: 1.14rem;
    font-weight: 700;
  }

  .card-body {
    padding: 22px;
  }

  .meta-grid {
    display: grid;
    gap: 16px;
  }

  .meta-item label {
    display: block;
    color: var(--muted);
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 6px;
  }

  .meta-item div {
    color: #111111;
    font-weight: 700;
    line-height: 1.45;
  }

  .badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    padding: 7px 11px;
    font-size: 0.78rem;
    font-weight: 700;
  }

  .badge-success {
    background: var(--success-soft);
    color: var(--success);
  }

  .table-wrap {
    overflow-x: auto;
  }

  .items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.94rem;
  }

  .items-table th {
    background: #f9fafb;
    color: var(--muted);
    text-align: left;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 15px 18px;
    border-bottom: 1px solid var(--line);
    white-space: nowrap;
  }

  .items-table td {
    padding: 16px 18px;
    border-bottom: 1px solid var(--line);
    vertical-align: middle;
    color: var(--ink);
    font-weight: 600;
  }

  .items-table tr:last-child td {
    border-bottom: none;
  }

  .product-name {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .product-name strong {
    color: #111111;
  }

  .product-name span {
    color: var(--muted);
    font-size: 0.82rem;
  }

  .totals {
    display: grid;
    gap: 12px;
  }

  .total-row {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    font-weight: 700;
    color: var(--ink);
  }

  .total-row span:first-child {
    color: var(--muted);
  }

  .total-row.final {
    border-top: 1px solid var(--line);
    padding-top: 14px;
    margin-top: 4px;
    color: #111111;
    font-size: 1.2rem;
  }

  .address-box {
    background: #f9fafb;
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 14px;
    color: var(--ink);
    font-size: 0.92rem;
    line-height: 1.55;
    font-weight: 600;
    white-space: pre-wrap;
  }

  @media (max-width: 900px) {
    .grid {
      grid-template-columns: 1fr;
    }
  }
</style>

@php
  $shopifyId = str_replace('shopify_', '', (string) $order->stripe_session_id);
  $address = is_array($order->address_json ?? null) ? $order->address_json : [];
@endphp

<div class="detail-page">
  <div class="detail-header">
    <div>
      <h1>Pedido #{{ $order->id }}</h1>
      <p>Shopify ID: {{ $shopifyId }}</p>
    </div>

    <a href="{{ route('admin.shopify.orders.index') }}" class="btn-outline">
      Volver a ventas
    </a>
  </div>

  <div class="grid">
    <div class="card">
      <div class="card-header">
        <h2>Productos vendidos</h2>
      </div>

      <div class="table-wrap">
        <table class="items-table">
          <thead>
            <tr>
              <th>Producto</th>
              <th>SKU</th>
              <th>Cantidad</th>
              <th>Precio</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            @forelse($order->items as $item)
              <tr>
                <td>
                  <div class="product-name">
                    <strong>{{ $item->name }}</strong>
                    <span>Catalog item: {{ $item->catalog_item_id ?: 'No vinculado' }}</span>
                  </div>
                </td>
                <td>{{ $item->sku ?: '—' }}</td>
                <td>{{ number_format((int) $item->quantity) }}</td>
                <td>${{ number_format((float) $item->unit_price, 2) }}</td>
                <td>${{ number_format((float) $item->total, 2) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="5" style="text-align:center; color:var(--muted); padding:40px;">
                  Este pedido entró por webhook, pero no tuvo productos vinculados con JURETO.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div style="display:grid; gap:20px;">
      <div class="card">
        <div class="card-header">
          <h2>Resumen</h2>
        </div>

        <div class="card-body">
          <div class="totals">
            <div class="total-row">
              <span>Subtotal</span>
              <strong>${{ number_format((float) $order->subtotal, 2) }}</strong>
            </div>

            <div class="total-row">
              <span>Envío</span>
              <strong>${{ number_format((float) $order->shipping_amount, 2) }}</strong>
            </div>

            <div class="total-row">
              <span>Impuestos</span>
              <strong>${{ number_format((float) $order->tax, 2) }}</strong>
            </div>

            <div class="total-row final">
              <span>Total</span>
              <strong>${{ number_format((float) $order->total, 2) }} {{ $order->currency ?? 'MXN' }}</strong>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2>Cliente</h2>
        </div>

        <div class="card-body">
          <div class="meta-grid">
            <div class="meta-item">
              <label>Nombre</label>
              <div>{{ $order->customer_name ?: 'Cliente Shopify' }}</div>
            </div>

            <div class="meta-item">
              <label>Correo</label>
              <div>{{ $order->customer_email ?: 'Sin correo' }}</div>
            </div>

            <div class="meta-item">
              <label>Estado</label>
              <div>
                <span class="badge badge-success">
                  {{ $order->status === 'paid' ? 'Pagado' : ucfirst($order->status ?? 'Pendiente') }}
                </span>
              </div>
            </div>

            <div class="meta-item">
              <label>Fecha</label>
              <div>{{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2>Dirección</h2>
        </div>

        <div class="card-body">
          @if(!empty($address))
            <div class="address-box">
{{ $address['name'] ?? '' }}
{{ $address['address1'] ?? '' }}
{{ $address['address2'] ?? '' }}
{{ $address['city'] ?? '' }} {{ $address['province'] ?? '' }}
{{ $address['country'] ?? '' }} {{ $address['zip'] ?? '' }}
Tel. {{ $address['phone'] ?? '—' }}
            </div>
          @else
            <div class="address-box">Sin dirección registrada.</div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection