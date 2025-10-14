@extends('layouts.web')
@section('title','Contacto')
@section('content')
  <h2 style="color:var(--ink); margin-bottom:12px;">Contacto</h2>
  <form class="card" method="POST" action="{{ route('web.contacto.send') }}">
    @csrf
    <div style="display:grid; gap:12px;">
      <div>
        <label>Nombre</label>
        <input name="nombre" value="{{ old('nombre') }}" class="form-control" style="width:100%;padding:10px;border-radius:12px;border:1px solid #ddd">
        @error('nombre')<small style="color:#b00020">{{ $message }}</small>@enderror
      </div>
      <div>
        <label>Email</label>
        <input name="email" value="{{ old('email') }}" type="email" class="form-control" style="width:100%;padding:10px;border-radius:12px;border:1px solid #ddd">
        @error('email')<small style="color:#b00020">{{ $message }}</small>@enderror
      </div>
      <div>
        <label>Mensaje</label>
        <textarea name="mensaje" rows="5" class="form-control" style="width:100%;padding:10px;border-radius:12px;border:1px solid #ddd">{{ old('mensaje') }}</textarea>
        @error('mensaje')<small style="color:#b00020">{{ $message }}</small>@enderror
      </div>
      <div><button class="btn">Enviar</button></div>
    </div>
  </form>
@endsection
