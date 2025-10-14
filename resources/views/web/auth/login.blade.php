@extends('layouts.web')
@section('title','Entrar')
@section('content')
  <h2 style="color:var(--ink); margin-bottom:12px;">Entrar</h2>
  <form class="card" method="POST" action="{{ route('customer.login.post') }}">
    @csrf
    <div style="display:grid; gap:12px;">
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
      <label style="display:flex; gap:8px; align-items:center;">
        <input type="checkbox" name="remember"> Recordarme
      </label>
      <button class="btn">Entrar</button>
    </div>
  </form>
@endsection
