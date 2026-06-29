@extends('layouts.app')

@section('title', $section->exists ? 'Editar fila de productos' : 'Nueva fila de productos')

@section('content')

<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  :root {
    --bg: #f9fafb;
    --card: #ffffff;
    --ink: #333333;
    --muted: #888888;
    --line: #ebebeb;
    --blue: #007aff;
    --blue-soft: #e6f0ff;
    --success: #15803d;
    --success-soft: #e6ffe6;
    --danger: #ff4a4a;
    --danger-soft: #ffebeb;
  }

  .hp-form-page {
    min-height: 100vh;
    padding: 32px;
    background: var(--bg);
    color: var(--ink);
    font-family: 'Quicksand', sans-serif;
  }

  .hp-form-shell {
    max-width: 980px;
    margin: 0 auto;
  }

  .hp-form-title {
    margin: 0;
    color: #111111;
    font-size: 30px;
    font-weight: 700;
    letter-spacing: -0.03em;
  }

  .hp-form-subtitle {
    margin: 8px 0 24px;
    color: var(--muted);
    font-size: 15px;
    font-weight: 500;
  }

  .hp-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    padding: 28px;
  }

  .hp-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
  }

  .hp-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .hp-field.full {
    grid-column: 1 / -1;
  }

  .hp-label {
    color: #111111;
    font-size: 14px;
    font-weight: 700;
  }

  .hp-input,
  .hp-select,
  .hp-textarea {
    width: 100%;
    background: #ffffff;
    color: var(--ink);
    border: 1px solid var(--line);
    border-radius: 8px;
    padding: 12px 14px;
    font-family: 'Quicksand', sans-serif;
    font-size: 15px;
    font-weight: 500;
    outline: none;
    transition: 0.2s ease;
  }

  .hp-textarea {
    min-height: 96px;
    resize: vertical;
  }

  .hp-input:focus,
  .hp-select:focus,
  .hp-textarea:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .hp-help {
    color: var(--muted);
    font-size: 13px;
    line-height: 1.45;
  }

  .hp-error {
    color: var(--danger);
    font-size: 13px;
    font-weight: 700;
  }

  .hp-check-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding-top: 8px;
  }

  .hp-check-row input {
    width: 18px;
    height: 18px;
    accent-color: var(--blue);
  }

  .hp-products-box {
    max-height: 420px;
    overflow: auto;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: #ffffff;
  }

  .hp-product-option {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-bottom: 1px solid var(--line);
  }

  .hp-product-option:last-child {
    border-bottom: 0;
  }

  .hp-product-option input {
    width: 18px;
    height: 18px;
    accent-color: var(--blue);
  }

  .hp-product-name {
    font-weight: 700;
    color: #111111;
    font-size: 14px;
  }

  .hp-product-meta {
    margin-top: 3px;
    color: var(--muted);
    font-size: 12px;
  }

  .hp-product-price {
    font-weight: 700;
    color: #111111;
    white-space: nowrap;
  }

  .hp-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 26px;
  }

  .hp-btn-primary,
  .hp-btn-ghost {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 42px;
    padding: 0 20px;
    border-radius: 999px;
    font-family: 'Quicksand', sans-serif;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: 0.2s ease;
    border: 0;
  }

  .hp-btn-primary {
    background: var(--blue);
    color: #ffffff;
  }

  .hp-btn-primary:hover {
    color: #ffffff;
    filter: brightness(0.97);
  }

  .hp-btn-ghost {
    background: transparent;
    color: #555555;
  }

  .hp-btn-ghost:hover {
    background: #f9fafb;
    color: #111111;
  }

  .hp-btn-primary:active,
  .hp-btn-ghost:active {
    transform: scale(0.98);
  }

  .hp-hidden {
    display: none !important;
  }

  @media (max-width: 768px) {
    .hp-form-page {
      padding: 20px;
    }

    .hp-card {
      padding: 20px;
    }

    .hp-grid {
      grid-template-columns: 1fr;
    }

    .hp-actions {
      flex-direction: column-reverse;
      align-items: stretch;
    }
  }
</style>

<div class="hp-form-page">
  <div class="hp-form-shell">

    <h1 class="hp-form-title">
      {{ $section->exists ? 'Editar fila de productos' : 'Nueva fila de productos' }}
    </h1>

    <p class="hp-form-subtitle">
      Puedes crear filas por temporada, campañas o categorías completas del catálogo.
    </p>

    <form
      class="hp-card"
      method="POST"
      action="{{ $section->exists ? route('admin.home-product-sections.update', $section) : route('admin.home-product-sections.store') }}"
    >
      @csrf

      @if($section->exists)
        @method('PUT')
      @endif

      <div class="hp-grid">

        <div class="hp-field">
          <label class="hp-label" for="title">Título de la fila</label>
          <input
            id="title"
            name="title"
            class="hp-input"
            value="{{ old('title', $section->title) }}"
            placeholder="Ej. Mundial"
            required
          >

          @error('title')
            <div class="hp-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="hp-field">
          <label class="hp-label" for="slug">Slug</label>
          <input
            id="slug"
            name="slug"
            class="hp-input"
            value="{{ old('slug', $section->slug) }}"
            placeholder="mundial"
          >

          <div class="hp-help">Si lo dejas vacío, se genera automáticamente.</div>

          @error('slug')
            <div class="hp-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="hp-field full">
          <label class="hp-label" for="subtitle">Subtítulo opcional</label>
          <input
            id="subtitle"
            name="subtitle"
            class="hp-input"
            value="{{ old('subtitle', $section->subtitle) }}"
            placeholder="Ej. Productos seleccionados para esta temporada"
          >

          @error('subtitle')
            <div class="hp-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="hp-field">
          <label class="hp-label" for="source_type">Cómo se llenará la fila</label>
          <select id="source_type" name="source_type" class="hp-select" required>
            <option value="manual" @selected(old('source_type', $section->source_type) === 'manual')>
              Manual: elegir productos
            </option>

            <option value="category" @selected(old('source_type', $section->source_type) === 'category')>
              Automático: por categoría
            </option>
          </select>

          @error('source_type')
            <div class="hp-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="hp-field" id="categoryField">
          <label class="hp-label" for="category_product_id">Categoría</label>
          <select id="category_product_id" name="category_product_id" class="hp-select">
            <option value="">Selecciona una categoría</option>

            @foreach($categories as $category)
              <option
                value="{{ $category->id }}"
                @selected((string) old('category_product_id', $section->category_product_id) === (string) $category->id)
              >
                {{ $category->full_path ?? $category->name }}
              </option>
            @endforeach
          </select>

          <div class="hp-help">
            Solo se usa cuando eliges “Automático: por categoría”.
          </div>

          @error('category_product_id')
            <div class="hp-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="hp-field">
          <label class="hp-label" for="products_limit">Límite de productos</label>
          <input
            type="number"
            min="1"
            max="40"
            id="products_limit"
            name="products_limit"
            class="hp-input"
            value="{{ old('products_limit', $section->products_limit ?? 12) }}"
          >

          @error('products_limit')
            <div class="hp-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="hp-field">
          <label class="hp-label" for="sort_order">Orden de aparición</label>
          <input
            type="number"
            min="0"
            id="sort_order"
            name="sort_order"
            class="hp-input"
            value="{{ old('sort_order', $section->sort_order ?? 0) }}"
          >

          @error('sort_order')
            <div class="hp-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="hp-field">
          <label class="hp-label" for="starts_at">Fecha de inicio</label>
          <input
            type="date"
            id="starts_at"
            name="starts_at"
            class="hp-input"
            value="{{ old('starts_at', $section->starts_at ? $section->starts_at->format('Y-m-d') : '') }}"
          >

          @error('starts_at')
            <div class="hp-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="hp-field">
          <label class="hp-label" for="ends_at">Fecha final</label>
          <input
            type="date"
            id="ends_at"
            name="ends_at"
            class="hp-input"
            value="{{ old('ends_at', $section->ends_at ? $section->ends_at->format('Y-m-d') : '') }}"
          >

          @error('ends_at')
            <div class="hp-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="hp-field full">
          <label class="hp-check-row">
            <input
              type="checkbox"
              name="is_active"
              value="1"
              @checked(old('is_active', $section->is_active))
            >

            <span class="hp-label">Mostrar esta fila en el home</span>
          </label>
        </div>

        <div class="hp-field full" id="manualProductsField">
          <label class="hp-label">Productos participantes</label>

          <div class="hp-help">
            Marca los productos que quieres mostrar cuando la fila sea manual. Se muestran en el orden en que aparecen aquí.
          </div>

          <div class="hp-products-box">
            @foreach($products as $product)
              <label class="hp-product-option">
                <input
                  type="checkbox"
                  name="products[]"
                  value="{{ $product->id }}"
                  @checked(collect(old('products', $selectedProducts))->contains($product->id))
                >

                <span>
                  <span class="hp-product-name">
                    {{ $product->name }}
                  </span>

                  <span class="hp-product-meta">
                    SKU: {{ $product->sku ?? 'Sin SKU' }}
                    @if($product->categoryProduct)
                      · {{ $product->categoryProduct->full_path }}
                    @endif
                  </span>
                </span>

                <span class="hp-product-price">
                  ${{ number_format((float) ($product->sale_price ?: $product->price), 2) }}
                </span>
              </label>
            @endforeach
          </div>

          @error('products')
            <div class="hp-error">{{ $message }}</div>
          @enderror

          @error('products.*')
            <div class="hp-error">{{ $message }}</div>
          @enderror
        </div>

      </div>

      <div class="hp-actions">
        <a href="{{ route('admin.home-product-sections.index') }}" class="hp-btn-ghost">
          Cancelar
        </a>

        <button type="submit" class="hp-btn-primary">
          {{ $section->exists ? 'Guardar cambios' : 'Crear fila' }}
        </button>
      </div>
    </form>

  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const sourceType = document.getElementById('source_type');
    const categoryField = document.getElementById('categoryField');
    const manualProductsField = document.getElementById('manualProductsField');

    function syncFields() {
      const value = sourceType.value;

      if (value === 'category') {
        categoryField.classList.remove('hp-hidden');
        manualProductsField.classList.add('hp-hidden');
      } else {
        categoryField.classList.add('hp-hidden');
        manualProductsField.classList.remove('hp-hidden');
      }
    }

    sourceType.addEventListener('change', syncFields);
    syncFields();
  });
</script>

@endsection