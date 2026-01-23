@extends('layouts.app')

@section('title','Nueva ficha técnica')

@section('content')
<div class="container my-4">
  <style>
    .ts-card{
      background:#ffffff;
      border-radius:16px;
      box-shadow:0 14px 40px rgba(15,23,42,.12);
      padding:24px 22px;
      max-width:900px;
      margin:0 auto;
    }
    .ts-label{ font-weight:600; font-size:.9rem; color:#111827; }
    .ts-hint{ font-size:.8rem; color:#6b7280; }
    .ts-input, .ts-textarea{
      width:100%; border-radius:10px; border:1px solid #e5e7eb;
      padding:9px 11px; font-size:.9rem;
    }
    .ts-input:focus, .ts-textarea:focus{
      outline:none; border-color:#4f46e5; box-shadow:0 0 0 1px #4f46e533;
    }
    .ts-title{ font-size:1.2rem; font-weight:700; margin-bottom:12px; }
  </style>

  <div class="ts-card">
    <h1 class="ts-title">Generar ficha técnica con IA</h1>
    <p class="ts-hint">Captura los datos básicos del equipo y la IA completará la descripción formal, características y especificaciones.</p>

    @if ($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form action="{{ route('tech-sheets.store') }}" method="POST" enctype="multipart/form-data" class="mt-3">
      @csrf

      <div class="mb-3">
        <label class="ts-label">Nombre del producto *</label>
        <input type="text" name="product_name" class="ts-input" value="{{ old('product_name') }}" required>
      </div>

      <div class="mb-3">
        <label class="ts-label">Descripción rápida (en tus palabras)</label>
        <textarea name="user_description" rows="3" class="ts-textarea" placeholder="Ej. Cargador universal para pilas AA, AAA, C, D y 9V...">{{ old('user_description') }}</textarea>
      </div>

      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="ts-label">Marca</label>
          <input type="text" name="brand" class="ts-input" value="{{ old('brand') }}">
        </div>
        <div class="col-md-4 mb-3">
          <label class="ts-label">Modelo</label>
          <input type="text" name="model" class="ts-input" value="{{ old('model') }}">
        </div>
        <div class="col-md-4 mb-3">
          <label class="ts-label">Referencia / código</label>
          <input type="text" name="reference" class="ts-input" value="{{ old('reference') }}">
        </div>
      </div>

      <div class="mb-3">
        <label class="ts-label">Identificación corta del producto</label>
        <input type="text" name="identification" class="ts-input"
          placeholder="Ej. Cargador universal para pilas CRG-500"
          value="{{ old('identification') }}">
      </div>

      <div class="mb-3">
        <label class="ts-label">Imagen del producto</label>
        <input type="file" name="image" class="form-control">
        <div class="ts-hint mt-1">Se usará sólo en la ficha (no se envía a la IA).</div>
      </div>

      <div class="d-flex justify-content-end mt-4">
        <button type="submit" class="btn btn-primary">
          Generar ficha con IA
        </button>
      </div>
    </form>
  </div>
</div>
@endsection
