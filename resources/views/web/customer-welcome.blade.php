@extends('layouts.web')
@section('title','Bienvenido')

@section('content')
<div style="max-width:900px;margin:60px auto;padding:0 16px;color:#0e1726">
  <h1>¡Bienvenido!</h1>
  <p>Tu correo fue verificado y ya puedes comprar con tu cuenta de cliente.</p>
  <a href="{{ url('/') }}" style="display:inline-block;margin-top:12px;padding:10px 14px;border:1px solid #e8eef6;border-radius:12px;text-decoration:none;">Ir a la tienda</a>
</div>
@endsection
