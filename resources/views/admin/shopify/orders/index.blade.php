@extends('layouts.app')

@section('title', 'Ventas Shopify')
@section('titulo', 'Ventas Shopify')

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

  .shopify-page {
    max-width: 1240px;
    margin: 0 auto;
    padding: 32px 24px 56px;
  }

  .shopify-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 28px;
    flex-wrap: wrap;
  }

  .shopify-title h1 {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
    color: #111111;
    letter-spacing: -0.03em;
  }

  .shopify-title p {
    margin: 8px 0 0;
    color: var(--muted);
    font-size: 0.98rem;
    line-height: 1.6;
  }

  .shopify-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--success-soft);
    color: var(--success);
    border-radius: 999px;
    padding: 10px 16px;
    font-size: 0.9rem;
    font-weight: 700;
    border: 1px solid rgba(21, 128, 61, 0.12);
  }

  .shopify-pill-dot {
    width: 8px;
    height: 8px;
    background: var(--success);
    border-radius: 999px;
  }

  .summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 22px;
  }

  .summary-card,
  .filter-card,
  .orders-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    transition: all 0.2s ease;
  }

  .summary-card:hover,
  .orders-card:hover,
  .filter-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.04);
  }

  .summary-card {
    padding: 22px;
  }

  .summary-label {
    color: var(--muted);
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 8px;
  }

  .summary-value {
    font-size: 1.45rem;
    font-weight: 700;
    color: #111111;
  }

  .summary-value.blue {
    color: var(--blue);
  }

  .filter-card {
    padding: 20px;
    margin-bottom: 22px;
  }

  .filter-form {
    display: grid;
    grid-template-columns: 1.4fr 0.8fr 0.8fr 0.8fr auto;
    gap: 12px;
    align-items: end;
  }

  .form-group label {
    display: block;
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 7px;
  }

  .form-input,
  .form-select {
    width: 100%;
    height: 44px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #ffffff;
    color: var(--ink);
    padding: 0 13px;
    font-family: inherit;
    font-size: 0.95rem;
    font-weight: 600;
    outline: none;
    transition: all 0.2s ease;
  }

  .form-input:focus,
  .form-select:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .btn-primary,
  .btn-ghost,
  .btn-outline {
    min-height: 44px;
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
    gap: 8px;
    transition: all 0.2s ease;
    white-space: nowrap;
  }

  .btn-primary {
    background: var(--blue);
    color: #ffffff;
    border: none;
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

  .btn-primary:hover,
  .btn-outline:hover,
  .btn-ghost:hover {
    transform: translateY(-1px);
  }

  .btn-ghost:hover {
    background: #f9fafb;
  }

  .btn-primary:active,
  .btn-outline:active,
  .btn-ghost:active {
    transform: scale(0.98);
  }

  .orders-card {
    overflow: hidden;
  }

  .orders-toolbar {
    padding: 20px 22px;
    border-bottom: 1px solid var(--line);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
  }

  .orders-toolbar h2 {
    margin: 0;
    font-size: 1.15rem;
    color: #111111;
    font-weight: 700;
  }

  .table-wrap {
    overflow-x: auto;
  }

  .orders-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.94rem;
  }

  .orders-table th {
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

  .orders-table td {
    padding: 16px 18px;
    border-bottom: 1px solid var(--line);
    vertical-align: middle;
    color: var(--ink);
    font-weight: 600;
    white-space: nowrap;
  }

  .orders-table tr:last-child td {
    border-bottom: none;
  }

  .orders-table tr:hover td {
    background: #fcfcfd;
  }

  .order-id {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .order-id strong {
    color: #111111;
    font-weight: 700;
  }

  .order-id span {
    color: var(--muted);
    font-size: 0.8rem;
  }

  .customer {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .customer strong {
    color: #111111;
  }

  .customer span {
    color: var(--muted);
    font-size: 0.82rem;
  }

  .badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    padding: 6px 10px;
    font-size: 0.78rem;
    font-weight: 700;
  }

  .badge-success {
    background: var(--success-soft);
    color: var(--success);
  }

  .badge-info {
    background: var(--blue-soft);
    color: var(--blue);
  }

  .badge-danger {
    background: var(--danger-soft);
    color: var(--danger);
  }

  .items-preview {
    color: var(--muted);
    font-size: 0.84rem;
    max-width: 260px;
    white-space: normal;
    line-height: 1.45;
  }

  .amount {
    color: #111111;
    font-weight: 700;
  }

  .empty-state {
    padding: 60px 24px;
    text-align: center;
  }

  .empty-state h3 {
    margin: 0 0 8px;
    color: #111111;
    font-size: 1.25rem;
    font-weight: 700;
  }

  .empty-state p {
    margin: 0;
    color: var(--muted);
    font-size: 0.95rem;
  }

  .pagination-wrap {
    padding: 18px 22px;
    border-top: 1px solid var(--line);
  }

  @media (max-width: 980px) {
    .summary-grid {
      grid-template-columns: repeat(2, 1fr);
    }

    .filter-form {
      grid-template-columns: 1fr 1fr;
    }
  }

  @media (max-width: 640px) {
    .shopify-page {
      padding: 24px 16px 42px;
    }

    .summary-grid,
    .filter-form {
      grid-template-columns: 1fr;
    }

    .shopify-header {
      align-items: flex-start;
    }
  }
</style>

<div class="shopify-page">
  <div class="shopify-header">
    <div class="shopify-title">
      <h1>Ventas Shopify</h1>
      <p>Pedidos recibidos desde Shopify por webhook. Aquí puedes revisar ventas, productos vendidos y descuentos de stock local.</p>
    </div>

    <span class="shopify-pill">
      <span class="shopify-pill-dot"></span>
      Webhook conectado
    </span>
  </div>

  <div class="summary-grid">
    <div class="summary-card">
      <div class="summary-label">Pedidos Shopify</div>
      <div class="summary-value">{{ number_format($totalOrders) }}</div>
    </div>

    <div class="summary-card">
      <div class="summary-label">Pagados</div>
      <div class="summary-value">{{ number_format($paidOrders) }}</div>
    </div>

    <div class="summary-card">
      <div class="summary-label">Ventas totales</div>
      <div class="summary-value blue">${{ number_format((float) $totalRevenue, 2) }}</div>
    </div>

    <div class="summary-card">
      <div class="summary-label">Pedidos hoy</div>
      <div class="summary-value">{{ number_format($todayOrders) }}</div>
    </div>
  </div>

  <div class="filter-card">
    <form method="GET" action="{{ route('admin.shopify.orders.index') }}" class="filter-form">
      <div class="form-group">
        <label>Buscar</label>
        <input
          type="text"
          name="search"
          class="form-input"
          placeholder="Cliente, email o ID Shopify"
          value="{{ $search }}"
        >
      </div>

      <div class="form-group">
        <label>Estado</label>
        <select name="status" class="form-select">
          <option value="">Todos</option>
          <option value="paid" @selected($status === 'paid')>Pagado</option>
          <option value="pending" @selected($status === 'pending')>Pendiente</option>
          <option value="cancelled" @selected($status === 'cancelled')>Cancelado</option>
        </select>
      </div>

      <div class="form-group">
        <label>Desde</label>
        <input type="date" name="date_from" class="form-input" value="{{ $dateFrom }}">
      </div>

      <div class="form-group">
        <label>Hasta</label>
        <input type="date" name="date_to" class="form-input" value="{{ $dateTo }}">
      </div>

      <div style="display:flex; gap:8px;">
        <button type="submit" class="btn-primary">Filtrar</button>
        <a href="{{ route('admin.shopify.orders.index') }}" class="btn-ghost">Limpiar</a>
      </div>
    </form>
  </div>

  <div class="orders-card">
    <div class="orders-toolbar">
      <h2>Pedidos recibidos</h2>
      <a href="{{ route('admin.catalog.index') }}" class="btn-outline">Ir al catálogo</a>
    </div>

    @if($orders->count())
      <div class="table-wrap">
        <table class="orders-table">
          <thead>
            <tr>
              <th>Pedido</th>
              <th>Cliente</th>
              <th>Productos</th>
              <th>Estado</th>
              <th>Total</th>
              <th>Fecha</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @foreach($orders as $order)
              @php
                $shopifyId = str_replace('shopify_', '', (string) $order->stripe_session_id);
                $itemsCount = $order->items?->count() ?? 0;
                $preview = $order->items?->take(3)->map(function ($item) {
                    return ($item->quantity ?? 0) . 'x ' . ($item->name ?? 'Producto');
                })->implode(', ');
              @endphp

              <tr>
                <td>
                  <div class="order-id">
                    <strong>#{{ $order->id }}</strong>
                    <span>Shopify: {{ $shopifyId }}</span>
                  </div>
                </td>

                <td>
                  <div class="customer">
                    <strong>{{ $order->customer_name ?: 'Cliente Shopify' }}</strong>
                    <span>{{ $order->customer_email ?: 'Sin correo' }}</span>
                  </div>
                </td>

                <td>
                  <div class="items-preview">
                    @if($itemsCount > 0)
                      {{ $preview }}
                      @if($itemsCount > 3)
                        y {{ $itemsCount - 3 }} más
                      @endif
                    @else
                      Sin productos vinculados
                    @endif
                  </div>
                </td>

                <td>
                  @if($order->status === 'paid')
                    <span class="badge badge-success">Pagado</span>
                  @elseif($order->status === 'cancelled')
                    <span class="badge badge-danger">Cancelado</span>
                  @else
                    <span class="badge badge-info">{{ ucfirst($order->status ?? 'Pendiente') }}</span>
                  @endif
                </td>

                <td>
                  <span class="amount">
                    ${{ number_format((float) $order->total, 2) }} {{ $order->currency ?? 'MXN' }}
                  </span>
                </td>

                <td>
                  {{ optional($order->created_at)->format('d/m/Y H:i') }}
                </td>

                <td style="text-align:right;">
                  <a href="{{ route('admin.shopify.orders.show', $order) }}" class="btn-ghost">
                    Ver detalle
                  </a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="pagination-wrap">
        {{ $orders->links() }}
      </div>
    @else
      <div class="empty-state">
        <h3>Aún no hay ventas Shopify</h3>
        <p>Cuando un pedido pagado llegue por webhook, aparecerá aquí automáticamente.</p>
      </div>
    @endif
  </div>
</div>
@endsection