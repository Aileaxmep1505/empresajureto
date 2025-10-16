@extends('layouts.web')
@section('title','Pago exitoso')
@section('content')
<div class="wrap" style="max-width:720px;padding:24px 16px">
  <div class="card" style="padding:24px;text-align:center">
    <h1 style="margin:0 0 8px;font-weight:800">¡Pago recibido! 🎉</h1>
    <p class="muted">Tu número de sesión Stripe es:</p>
    <code style="display:inline-block;background:#f1f5f9;padding:6px 10px;border-radius:8px">{{ $sessionId }}</code>
    <p class="muted" style="margin-top:10px">Te enviaremos la confirmación por correo.</p>
    <a class="btn" href="{{ route('web.catalog.index') }}" style="margin-top:12px">Seguir comprando</a>
  </div>
</div>
@endsection
