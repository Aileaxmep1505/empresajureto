{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
  <div style="background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:16px; box-shadow:var(--shadow)">
    <h3 style="margin:0 0 8px 0">¡Hola, {{ auth()->user()->name }}!</h3>
    <p style="color:var(--muted)">Bienvenido a tu panel.</p>
  </div>
@endsection
