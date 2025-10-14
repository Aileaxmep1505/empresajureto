@extends('layouts.web')
@section('title','Inicio')
@section('content')
  <section class="hero">
    <div>
      <h1>Equipo médico y soluciones profesionales</h1>
      <p>Plataforma moderna para cotizar y comprar con confianza. Atención personalizada y soporte técnico.</p>
      <div style="margin-top:16px;">
        <a href="{{ route('web.ventas.index') }}" class="btn">Ver ventas</a>
        <a href="{{ route('web.contacto') }}" class="btn-line" style="margin-left:8px;">Contáctanos</a>
      </div>
    </div>
  </section>

  <div class="grid">
    <div class="col-4"><div class="card">Envíos nacionales</div></div>
    <div class="col-4"><div class="card">Garantía y soporte</div></div>
    <div class="col-4"><div class="card">Pagos seguros</div></div>
  </div>
@endsection
