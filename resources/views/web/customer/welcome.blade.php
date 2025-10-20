@extends('layouts.web')
@section('title','Mi pedido')

@section('content')
@php
  $nombre  = $customer->nombre ?? $customer->name ?? '';
  $apellido= $customer->apellido ?? '';
  $cart    = is_array($cart) ? $cart : [];
  $subtotal = 0;
  foreach ($cart as $r) { $subtotal += ($r['price'] ?? 0) * ($r['qty'] ?? 1); }
@endphp

<style>
  .stepper{display:flex;gap:22px;align-items:center;margin:8px 0 18px}
  .step{display:flex;align-items:center;gap:10px;font-weight:800;color:#334155}
  .dot{width:28px;height:28px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;border:2px solid #1d4ed8}
  .dot.active{background:#1d4ed8;color:#fff}
  .panel{display:grid;grid-template-columns:2fr 1fr;gap:16px}
  @media(max-width: 980px){ .panel{grid-template-columns:1fr} }
  .card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px}
  .summary-row{display:flex;justify-content:space-between;margin:6px 0;font-weight:700}
  .btn{display:inline-flex;align-items:center;justify-content:center;border-radius:10px;padding:10px 14px;font-weight:800;text-decoration:none;border:1px solid #dbe2ea}
  .btn-primary{background:#1d4ed8;color:#fff;border-color:#1d4ed8}
  .muted{color:#6b7280}
</style>

<div class="card" style="margin-bottom:10px;">
  <h1 style="margin:0 0 6px;font-weight:900">¡Hola, {{ trim($nombre.' '.$apellido) ?: 'cliente' }}!</h1>
  <div class="muted">Revisa tu entrega y continúa tu compra.</div>
</div>

<div class="stepper">
  <div class="step"><span class="dot active">1</span> Entrega</div>
  <div class="step"><span class="dot">2</span> Factura</div>
  <div class="step"><span class="dot">3</span> Envío</div>
  <div class="step"><span class="dot">4</span> Pago</div>
</div>

<div class="panel">
  <div class="card">
    <h3 style="margin:0 0 10px;font-weight:900">Dirección de entrega</h3>
    <div class="muted" style="margin-bottom:10px;">Agrega una dirección para tu entrega.</div>
    <a href="{{ url('/mi-direccion') }}" class="btn">Cambiar Dirección</a>

    <hr style="border:0;border-top:1px solid #eee;margin:16px 0">

    <h3 style="margin:0 0 10px;font-weight:900">Resumen de tu pedido</h3>
    @forelse($cart as $row)
      <div style="display:grid;grid-template-columns:auto 1fr auto;gap:12px;align-items:center;padding:8px 0;border-bottom:1px solid #f1f5f9">
        <img src="{{ $row['image'] ?? asset('images/placeholder.png') }}" alt="" style="width:64px;height:64px;object-fit:cover;border-radius:10px;border:1px solid #e5e7eb">
        <div>
          <div style="font-weight:800">{{ $row['name'] ?? 'Producto' }}</div>
          <div class="muted">Cantidad: {{ $row['qty'] ?? 1 }}</div>
        </div>
        <div style="font-weight:800">
          ${{ number_format(($row['price'] ?? 0) * ($row['qty'] ?? 1),2) }}
        </div>
      </div>
    @empty
      <div class="muted">Tu carrito está vacío.</div>
    @endforelse
  </div>

  <aside class="card">
    <div class="summary-row"><span>Subtotal</span><span>${{ number_format($subtotal,2) }}</span></div>
    <div class="summary-row"><span>Envío</span><span>Calculado adelante</span></div>
    <div class="muted" style="margin-top:6px;">Precios incluyen IVA</div>

    <hr style="border:0;border-top:1px solid #eee;margin:16px 0">

    <a class="btn btn-primary" href="{{ route('checkout.start') }}">Continuar</a>
  </aside>
</div>
@endsection
