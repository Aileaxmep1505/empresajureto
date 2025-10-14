@extends('layouts.web')
@section('title','Crear cuenta')
@section('content')
  <h2 style="color:var(--ink); margin-bottom:12px;">Crear cuenta</h2>
  <form class="card" method="POST" action="{{ route('customer.register.post') }}">
    @csrf
    <div style="display:grid; gap:12px;">
      <div>
        <label>Nombre</label>
        <input name="nombre" value="{{ old('nombre') }}" style="width:100%;padding:10px;border-radius:12px;border:1px solid #ddd">
        @error('nombre')<small style="color:#b00020">{{ $message }}</small>@enderror
      </div>
      <div>
        <label>Apellido</label>
        <input name="apellido" value="{{ old('apellido') }}" style="width:100%;padding:10px;border-radius:12px;border:1px solid #ddd">
        @error('apellido')<small style="color:#b00020">{{ $message }}</small>@enderror
      </div>
      <div>
        <label>Email</label>
        <input name="email" value="{{ old('email') }}" type="email" style="width:100%;padding:10px;border-radius:12px;border:1px solid #ddd">
        @error('email')<small style="color:#b00020">{{ $message }}</small>@enderror
      </div>
      <div>
        <label>Contraseña</label>
        <input name="password" type="password" style="width:100%;padding:10px;border-radius:12px;border:1px solid #ddd">
        @error('password')<small style="color:#b00020">{{ $message }}</small>@enderror
      </div>
      <div>
        <label>Confirmar contraseña</label>
        <input name="password_confirmation" type="password" style="width:100%;padding:10px;border-radius:12px;border:1px solid #ddd">
      </div>
      <button class="btn">Crear cuenta</button>
    </div>
  </form>
@endsection
