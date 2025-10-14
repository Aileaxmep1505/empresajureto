@extends('layouts.web')
@section('title', $producto->name)
@section('content')
  <div class="grid">
    <div class="col-6">
      <div class="card">
        <img src="{{ $producto->image_src ?? asset('images/placeholder.png') }}" alt="{{ $producto->name }}" style="width:100%; height:380px; object-fit:cover; border-radius:12px;">
      </div>
    </div>
    <div class="col-6">
      <div class="card">
        <h2 style="margin:0 0 8px; color:var(--ink);">{{ $producto->name }}</h2>
        <p style="opacity:.85;">{{ $producto->description }}</p>
        <h3 style="margin:12px 0;">${{ number_format($producto->price ?? 0, 2) }}</h3>

        @auth('customer')
          <form action="#" method="POST" onsubmit="alert('Demo: aquí iría agregar al carrito'); return false;">
            @csrf
            <button class="btn">Agregar al carrito</button>
          </form>
        @else
          <a class="btn" href="{{ route('customer.login') }}">Inicia sesión para comprar</a>
        @endauth
      </div>
    </div>
  </div>
@endsection
