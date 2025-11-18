@extends('layouts.app')
@section('title','Nuevo producto web')

@section('content')
<div class="wrap" style="max-width:1100px;margin-inline:auto;">
  <div class="head" style="display:flex;justify-content:space-between;align-items:center;margin:14px 0 10px;">
    <div>
      <h1 style="font-weight:800;margin:0;">Nuevo producto (Catálogo web)</h1>
      <p style="margin:4px 0 0;font-size:.9rem;color:#6b7280;">
        Completa la información del producto. Usaremos estos datos para tu sitio web y para crear publicaciones en Mercado Libre.
      </p>
    </div>
    <a class="btn btn-ghost" href="{{ route('admin.catalog.index') }}">← Volver</a>
  </div>

  @if($errors->any())
    <div class="alert" style="padding:10px 12px;border:1px solid #e8eef6;border-radius:12px;background:#fff4f4;color:#991b1b;margin-bottom:12px;font-size:.9rem;">
      <strong>Revisa estos puntos antes de guardar:</strong>
      <ul style="margin:6px 0 0 18px;">
        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <form class="card" style="background:#fff;border:1px solid #e8eef6;border-radius:16px;box-shadow:0 12px 30px rgba(13,23,38,.06);padding:16px;"
        action="{{ route('admin.catalog.store') }}" method="POST">
    @include('admin.catalog._form', ['item' => null])
  </form>
</div>
@endsection
