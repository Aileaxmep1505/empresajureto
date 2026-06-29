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
    max-width: 1180px;
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

  .hp-products-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin: 12px 0 14px;
  }

  .hp-products-search-wrap {
    flex: 1;
    position: relative;
  }

  .hp-products-search {
    width: 100%;
    background: #ffffff;
    color: var(--ink);
    border: 1px solid var(--line);
    border-radius: 999px;
    padding: 13px 16px 13px 42px;
    font-family: 'Quicksand', sans-serif;
    font-size: 15px;
    font-weight: 600;
    outline: none;
    transition: 0.2s ease;
  }

  .hp-products-search:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .hp-products-search-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted);
    font-size: 15px;
    pointer-events: none;
  }

  .hp-products-actions {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .hp-mini-btn {
    min-height: 40px;
    padding: 0 15px;
    border-radius: 999px;
    border: 1px solid var(--line);
    background: #ffffff;
    color: #555555;
    font-family: 'Quicksand', sans-serif;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.2s ease;
    white-space: nowrap;
  }

  .hp-mini-btn:hover {
    background: #f9fafb;
    color: #111111;
  }

  .hp-mini-btn:active {
    transform: scale(0.98);
  }

  .hp-selected-counter {
    display: inline-flex;
    align-items: center;
    min-height: 32px;
    padding: 0 12px;
    border-radius: 999px;
    background: var(--blue-soft);
    color: var(--blue);
    font-size: 13px;
    font-weight: 700;
  }

  .hp-products-box {
    max-height: 620px;
    overflow: auto;
    border: 1px solid var(--line);
    border-radius: 16px;
    background: #ffffff;
    padding: 16px;
  }

  .hp-products-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
  }

  .hp-product-card {
    position: relative;
    display: flex;
    flex-direction: column;
    min-height: 100%;
    border: 1px solid var(--line);
    border-radius: 16px;
    background: #ffffff;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    user-select: none;
  }

  .hp-product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(0,0,0,0.04);
  }

  .hp-product-card.is-selected {
    border-color: var(--blue);
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    box-shadow: 0 0 0 3px var(--blue-soft), 0 8px 22px rgba(0,122,255,0.08);
  }

  .hp-product-checkbox {
    position: absolute;
    top: 12px;
    left: 12px;
    width: 22px;
    height: 22px;
    z-index: 3;
    accent-color: var(--blue);
    cursor: pointer;
  }

  .hp-product-selected-pill {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 3;
    display: none;
    align-items: center;
    justify-content: center;
    min-height: 24px;
    padding: 0 9px;
    border-radius: 999px;
    background: var(--blue);
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
  }

  .hp-product-card.is-selected .hp-product-selected-pill {
    display: inline-flex;
  }

  .hp-product-img-box {
    width: 100%;
    aspect-ratio: 1 / 0.78;
    background: #f9fafb;
    border-bottom: 1px solid var(--line);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
  }

  .hp-product-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 12px;
    display: block;
  }

  .hp-product-img-placeholder {
    width: 62px;
    height: 62px;
    border-radius: 18px;
    background: #ffffff;
    border: 1px solid var(--line);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--muted);
    font-size: 22px;
    font-weight: 700;
  }

  .hp-product-body {
    padding: 13px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
  }

  .hp-product-name {
    color: #111111;
    font-size: 14px;
    font-weight: 700;
    line-height: 1.25;
    min-height: 36px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .hp-product-meta {
    color: var(--muted);
    font-size: 12px;
    font-weight: 500;
    line-height: 1.35;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .hp-product-footer {
    margin-top: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
  }

  .hp-product-price {
    color: #111111;
    font-size: 15px;
    font-weight: 700;
    white-space: nowrap;
  }

  .hp-product-stock {
    display: inline-flex;
    align-items: center;
    padding: 5px 8px;
    border-radius: 999px;
    background: var(--success-soft);
    color: var(--success);
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
  }

  .hp-product-stock.out {
    background: var(--danger-soft);
    color: var(--danger);
  }

  .hp-product-card.hp-hidden-by-search {
    display: none;
  }

  .hp-products-empty {
    display: none;
    text-align: center;
    padding: 38px 20px;
    color: var(--muted);
    font-weight: 600;
  }

  .hp-products-empty.is-visible {
    display: block;
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
    border: 0;
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

  @media (max-width: 1100px) {
    .hp-products-grid {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }
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

    .hp-products-toolbar {
      flex-direction: column;
      align-items: stretch;
    }

    .hp-products-actions {
      justify-content: space-between;
      flex-wrap: wrap;
    }

    .hp-products-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }

    .hp-actions {
      flex-direction: column-reverse;
      align-items: stretch;
    }
  }

  @media (max-width: 480px) {
    .hp-products-grid {
      grid-template-columns: 1fr;
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
            Busca y selecciona los productos que quieres mostrar en esta fila. Puedes dar clic en toda la tarjeta para marcarla.
          </div>

          <div class="hp-products-toolbar">
            <div class="hp-products-search-wrap">
              <span class="hp-products-search-icon">⌕</span>
              <input
                type="search"
                id="productSearch"
                class="hp-products-search"
                placeholder="Buscar por nombre, SKU o categoría..."
                autocomplete="off"
              >
            </div>

            <div class="hp-products-actions">
              <span class="hp-selected-counter" id="selectedCounter">0 seleccionados</span>

              <button type="button" class="hp-mini-btn" id="selectVisibleBtn">
                Seleccionar visibles
              </button>

              <button type="button" class="hp-mini-btn" id="clearSelectionBtn">
                Limpiar
              </button>
            </div>
          </div>

          <div class="hp-products-box">
            <div class="hp-products-grid" id="productsGrid">
              @foreach($products as $product)
                @php
                  $selectedIds = collect(old('products', $selectedProducts));

                  $productImage = null;

                  if (!empty($product->image_url)) {
                      $productImage = $product->image_url;
                  } elseif (!empty($product->primary_image_url)) {
                      $productImage = $product->primary_image_url;
                  } elseif (!empty($product->image_path)) {
                      $productImage = asset('storage/' . $product->image_path);
                  } elseif (!empty($product->main_image)) {
                      $productImage = asset('storage/' . $product->main_image);
                  } elseif (!empty($product->thumbnail)) {
                      $productImage = asset('storage/' . $product->thumbnail);
                  }

                  $price = $product->sale_price ?: $product->price;
                  $isSelected = $selectedIds->contains($product->id);

                  $searchText = trim(implode(' ', [
                      $product->name,
                      $product->sku,
                      optional($product->categoryProduct)->full_path,
                      optional($product->categoryProduct)->name,
                  ]));
                @endphp

                <label
                  class="hp-product-card {{ $isSelected ? 'is-selected' : '' }}"
                  data-product-card
                  data-search="{{ Str::lower($searchText) }}"
                >
                  <input
                    type="checkbox"
                    name="products[]"
                    value="{{ $product->id }}"
                    class="hp-product-checkbox"
                    @checked($isSelected)
                  >

                  <span class="hp-product-selected-pill">
                    Seleccionado
                  </span>

                  <div class="hp-product-img-box">
                    @if($productImage)
                      <img
                        src="{{ $productImage }}"
                        alt="{{ $product->name }}"
                        class="hp-product-img"
                        loading="lazy"
                      >
                    @else
                      <div class="hp-product-img-placeholder">
                        {{ Str::upper(Str::substr($product->name, 0, 1)) }}
                      </div>
                    @endif
                  </div>

                  <div class="hp-product-body">
                    <div class="hp-product-name">
                      {{ $product->name }}
                    </div>

                    <div class="hp-product-meta">
                      SKU: {{ $product->sku ?? 'Sin SKU' }}
                      @if($product->categoryProduct)
                        · {{ $product->categoryProduct->full_path ?? $product->categoryProduct->name }}
                      @endif
                    </div>

                    <div class="hp-product-footer">
                      <div class="hp-product-price">
                        ${{ number_format((float) $price, 2) }}
                      </div>

                      @if((float) $product->stock > 0)
                        <span class="hp-product-stock">
                          Stock {{ (float) $product->stock }}
                        </span>
                      @else
                        <span class="hp-product-stock out">
                          Sin stock
                        </span>
                      @endif
                    </div>
                  </div>
                </label>
              @endforeach
            </div>

            <div class="hp-products-empty" id="productsEmpty">
              No encontramos productos con esa búsqueda.
            </div>
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

    const productSearch = document.getElementById('productSearch');
    const productsGrid = document.getElementById('productsGrid');
    const productsEmpty = document.getElementById('productsEmpty');
    const selectedCounter = document.getElementById('selectedCounter');
    const selectVisibleBtn = document.getElementById('selectVisibleBtn');
    const clearSelectionBtn = document.getElementById('clearSelectionBtn');

    function normalizeText(value) {
      return String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
    }

    function getProductCards() {
      return Array.from(document.querySelectorAll('[data-product-card]'));
    }

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

    function syncCardState(card) {
      const checkbox = card.querySelector('input[type="checkbox"]');

      if (!checkbox) return;

      if (checkbox.checked) {
        card.classList.add('is-selected');
      } else {
        card.classList.remove('is-selected');
      }
    }

    function updateSelectedCounter() {
      const selected = getProductCards().filter(function (card) {
        const checkbox = card.querySelector('input[type="checkbox"]');
        return checkbox && checkbox.checked;
      }).length;

      selectedCounter.textContent = selected === 1
        ? '1 seleccionado'
        : selected + ' seleccionados';
    }

    function filterProducts() {
      const term = normalizeText(productSearch.value);
      let visibleCount = 0;

      getProductCards().forEach(function (card) {
        const haystack = normalizeText(card.dataset.search);

        if (!term || haystack.includes(term)) {
          card.classList.remove('hp-hidden-by-search');
          visibleCount++;
        } else {
          card.classList.add('hp-hidden-by-search');
        }
      });

      if (visibleCount === 0) {
        productsEmpty.classList.add('is-visible');
      } else {
        productsEmpty.classList.remove('is-visible');
      }
    }

    sourceType.addEventListener('change', syncFields);
    syncFields();

    getProductCards().forEach(function (card) {
      const checkbox = card.querySelector('input[type="checkbox"]');

      syncCardState(card);

      checkbox.addEventListener('change', function () {
        syncCardState(card);
        updateSelectedCounter();
      });
    });

    productSearch.addEventListener('input', filterProducts);

    selectVisibleBtn.addEventListener('click', function () {
      getProductCards().forEach(function (card) {
        if (card.classList.contains('hp-hidden-by-search')) return;

        const checkbox = card.querySelector('input[type="checkbox"]');

        if (checkbox) {
          checkbox.checked = true;
          syncCardState(card);
        }
      });

      updateSelectedCounter();
    });

    clearSelectionBtn.addEventListener('click', function () {
      getProductCards().forEach(function (card) {
        const checkbox = card.querySelector('input[type="checkbox"]');

        if (checkbox) {
          checkbox.checked = false;
          syncCardState(card);
        }
      });

      updateSelectedCounter();
    });

    filterProducts();
    updateSelectedCounter();
  });
</script>

@endsection