{{-- resources/views/products/import.blade.php --}}
@extends('layouts.app')
@section('title','Importar productos')

@section('content')
<div class="max-w-3xl mx-auto p-6 bg-white rounded-xl shadow mt-6">
  <h1 class="text-2xl font-bold mb-2">Importar productos desde Excel / CSV</h1>
  <p class="text-sm text-slate-600 mb-4">
    Acepta archivos <b>.xlsx, .xls, .csv</b> con encabezados. Puedes pegar URLs de Google Drive en la columna
    <b>Imagen URL</b> (se convierten automáticamente para verse en &lt;img&gt;).
  </p>

  {{-- Mensaje de éxito --}}
  @if(session('status'))
    <div class="p-3 mb-4 rounded bg-green-50 text-green-800">{{ session('status') }}</div>
  @endif

  {{-- Errores de validación globales --}}
  @if ($errors->any())
    <div class="p-3 mb-4 rounded bg-red-50 text-red-700">
      <strong>Errores:</strong>
      <ul class="mt-2 list-disc pl-5">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Excepción controlada en import (si la hay) --}}
  @if (session('exception'))
    <div class="p-3 mb-4 rounded bg-red-50 text-red-700">
      <strong>Error de importación:</strong> {{ session('exception') }}
    </div>
  @endif

  {{-- Filas que fallaron (SkipsOnFailure) --}}
  @php $fails = session('failures', []); @endphp
  @if (!empty($fails))
    <div class="p-3 mb-4 rounded bg-amber-50 text-amber-800">
      <strong>Filas con errores (no importadas):</strong>
      <div class="mt-2 overflow-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-slate-600">
              <th class="py-1 pr-3">Fila</th>
              <th class="py-1 pr-3">Campo</th>
              <th class="py-1 pr-3">Errores</th>
              <th class="py-1">Valores</th>
            </tr>
          </thead>
          <tbody>
          @foreach($fails as $f)
            <tr class="border-t">
              <td class="py-1 pr-3">{{ $f->row() }}</td>
              {{-- attribute() es string; evitar implode --}}
              <td class="py-1 pr-3">{{ $f->attribute() }}</td>
              <td class="py-1 pr-3">
                <ul class="list-disc pl-5">
                  @foreach($f->errors() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
              </td>
              <td class="py-1">
                <code class="text-xs">{{ json_encode($f->values()) }}</code>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif

  <form method="POST" action="{{ route('products.import.store') }}" enctype="multipart/form-data" class="space-y-4">
    @csrf

    <div>
      <label class="block font-medium">Archivo (.xlsx, .xls, .csv)</label>
      <input type="file" name="file" required class="mt-1" accept=".xlsx,.xls,.csv">
      <p class="text-xs text-slate-500 mt-1">Tamaño máximo: 50&nbsp;MB</p>
      @error('file') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
    </div>

    <div class="flex flex-col gap-2">
      <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="download_images" value="1" {{ old('download_images') ? 'checked' : '' }}>
        <span>Descargar imágenes y guardarlas en storage (convierte enlaces de Google Drive automáticamente)</span>
      </label>
      <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="queue" value="1" {{ old('queue') ? 'checked' : '' }}>
        <span>Procesar en segundo plano (queue)</span>
      </label>
    </div>

    <div class="flex items-center gap-3">
      <button class="px-4 py-2 bg-blue-600 text-white rounded">Importar</button>
      {{-- (Opcional) Enlace a plantilla, si tienes la ruta --}}
      {{-- <a href="{{ route('products.import.template') }}" class="text-blue-600 underline text-sm">Descargar plantilla</a> --}}
    </div>

    <details class="mt-6">
      <summary class="cursor-pointer font-medium">Formato esperado (encabezados)</summary>
      <ul class="list-disc pl-5 text-sm mt-2 grid grid-cols-2 gap-y-1">
        <li>SKU PROVEEDOR</li><li>SKU/Código del Producto</li>
        <li>Nombre del Producto</li><li>Categoría</li>
        <li>Descripción del producto</li><li>Precio licitación</li>
        <li>Precio</li><li>Precio Mercado</li>
        <li>Costo JURETO</li><li>Marca</li>
        <li>Peso(Kg)</li><li>Dimensiones (Centimetros)</li>
        <li>Color</li><li>Material</li>
        <li>Imagen URL</li><li>Unidad</li>
        <li>Piezas por Unidad</li><li>Estado</li>
        <li>Etiquetas</li><li>Notas Adicionales</li>
      </ul>
    </details>
  </form>
</div>
@endsection
