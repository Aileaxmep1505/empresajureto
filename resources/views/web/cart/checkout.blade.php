@extends('layouts.web')
@section('title','Checkout')

@section('content')
<style>
  :root{--ink:#0e1726;--muted:#6b7280;--line:#e8eef6;--surface:#fff;--brand:#6ea8fe;--shadow:0 12px 30px rgba(13,23,38,.06)}
  .wrap{max-width:900px;margin-inline:auto}
  .card{background:var(--surface);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);padding:16px}
  .muted{color:var(--muted)}
  .btn{border:0;border-radius:12px;padding:12px 16px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:10px;text-decoration:none}
  .btn-primary{background:var(--brand);color:#0b1220}
  .btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink)}
</style>
<div class="wrap">
  <h1 style="margin:10px 0 14px;font-weight:800;">Confirmación</h1>
  <div class="card">
    <p class="muted">Resumen de compra:</p>
    <ul style="margin-left:18px;">
      @foreach($cart as $row)
        <li>{{ $row['qty'] }} × {{ $row['name'] }} — ${{ number_format($row['price']*$row['qty'],2) }}</li>
      @endforeach
    </ul>
    <hr style="border:none;border-top:1px solid var(--line);margin:12px 0">
    <p><strong>Subtotal:</strong> ${{ number_format($totals['subtotal'],2) }}</p>
    <p><strong>IVA (16%):</strong> ${{ number_format($totals['iva'],2) }}</p>
    <p style="font-size:1.2rem;"><strong>Total:</strong> ${{ number_format($totals['total'],2) }}</p>

    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;">
      <a class="btn btn-ghost" href="{{ route('web.cart.index') }}">← Volver al carrito</a>
      {{-- Aquí integrarías pasarela o datos de envío --}}
      <a class="btn btn-primary" href="{{ route('web.contacto') }}">Finalizar por WhatsApp/Correo</a>
    </div>
  </div>
</div>
@endsection
