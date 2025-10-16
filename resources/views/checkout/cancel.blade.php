@extends('layouts.web')
@section('title','Pago cancelado')
@section('content')
<div class="wrap" style="max-width:720px;padding:24px 16px">
  <div class="card" style="padding:24px;text-align:center">
    <h1 style="margin:0 0 8px;font-weight:800">Pago cancelado</h1>
    <p class="muted">Puedes intentar nuevamente o elegir otro método de pago.</p>
    <a class="btn" href="{{ route('web.cart.index') }}" style="margin-top:12px">Volver al carrito</a>
  </div>
</div>
@endsection
