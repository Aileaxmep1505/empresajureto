@extends('layouts.app')

@section('title', $category->exists ? 'Editar categoría' : 'Nueva categoría')

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

  .cat-form-page {
    min-height: 100vh;
    padding: 32px;
    background: var(--bg);
    color: var(--ink);
    font-family: 'Quicksand', sans-serif;
  }

  .cat-form-shell {
    max-width: 880px;
    margin: 0 auto;
  }

  .cat-form-title {
    margin: 0;
    color: #111111;
    font-size: 30px;
    font-weight: 700;
    letter-spacing: -0.03em;
  }

  .cat-form-subtitle {
    margin: 8px 0 24px;
    color: var(--muted);
    font-size: 15px;
    font-weight: 500;
  }

  .cat-form-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    padding: 28px;
  }

  .cat-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
  }

  .cat-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .cat-field.full {
    grid-column: 1 / -1;
  }

  .cat-label {
    color: #111111;
    font-size: 14px;
    font-weight: 700;
  }

  .cat-input,
  .cat-select {
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

  .cat-input:focus,
  .cat-select:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .cat-help {
    color: var(--muted);
    font-size: 13px;
    line-height: 1.45;
  }

  .cat-error {
    color: var(--danger);
    font-size: 13px;
    font-weight: 700;
  }

  .cat-check-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding-top: 8px;
  }

  .cat-check-row input {
    width: 18px;
    height: 18px;
    accent-color: var(--blue);
  }

  .cat-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 26px;
  }

  .cat-btn-primary,
  .cat-btn-ghost {
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

  .cat-btn-primary {
    background: var(--blue);
    color: #ffffff;
  }

  .cat-btn-primary:hover {
    color: #ffffff;
    filter: brightness(0.97);
  }

  .cat-btn-ghost {
    background: transparent;
    color: #555555;
  }

  .cat-btn-ghost:hover {
    background: #f9fafb;
    color: #111111;
  }

  .cat-btn-primary:active,
  .cat-btn-ghost:active {
    transform: scale(0.98);
  }

  @media (max-width: 768px) {
    .cat-form-page {
      padding: 20px;
    }

    .cat-form-card {
      padding: 20px;
    }

    .cat-grid {
      grid-template-columns: 1fr;
    }

    .cat-actions {
      flex-direction: column-reverse;
      align-items: stretch;
    }
  }
</style>

<div class="cat-form-page">
  <div class="cat-form-shell">

    <h1 class="cat-form-title">
      {{ $category->exists ? 'Editar categoría' : 'Nueva categoría' }}
    </h1>

    <p class="cat-form-subtitle">
      Administra la categoría que se usará en el catálogo, filtros y header.
    </p>

    <form
      class="cat-form-card"
      method="POST"
      action="{{ $category->exists ? route('admin.category-products.update', $category) : route('admin.category-products.store') }}"
    >
      @csrf

      @if($category->exists)
        @method('PUT')
      @endif

      <div class="cat-grid">

        <div class="cat-field full">
          <label class="cat-label" for="name">Nombre de la categoría</label>
          <input
            type="text"
            id="name"
            name="name"
            class="cat-input"
            value="{{ old('name', $category->name) }}"
            placeholder="Ej. Papelería"
            required
          >

          @error('name')
            <div class="cat-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="cat-field">
          <label class="cat-label" for="slug">Slug</label>
          <input
            type="text"
            id="slug"
            name="slug"
            class="cat-input"
            value="{{ old('slug', $category->slug) }}"
            placeholder="papeleria"
          >

          <div class="cat-help">
            Puedes dejarlo vacío y se genera automáticamente.
          </div>

          @error('slug')
            <div class="cat-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="cat-field">
          <label class="cat-label" for="sort_order">Orden</label>
          <input
            type="number"
            min="0"
            id="sort_order"
            name="sort_order"
            class="cat-input"
            value="{{ old('sort_order', $category->sort_order ?? 0) }}"
          >

          @error('sort_order')
            <div class="cat-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="cat-field full">
          <label class="cat-label" for="parent_id">Categoría padre</label>
          <select id="parent_id" name="parent_id" class="cat-select">
            <option value="">Sin categoría padre / Principal</option>

            @foreach($parentCategories as $parent)
              <option
                value="{{ $parent->id }}"
                @selected((string) old('parent_id', $category->parent_id) === (string) $parent->id)
              >
                {{ $parent->full_path ?: $parent->name }}
              </option>
            @endforeach
          </select>

          <div class="cat-help">
            Úsala si quieres crear subcategorías.
          </div>

          @error('parent_id')
            <div class="cat-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="cat-field full">
          <label class="cat-check-row">
            <input
              type="checkbox"
              name="is_active"
              value="1"
              @checked(old('is_active', $category->is_active))
            >

            <span class="cat-label">Categoría activa</span>
          </label>

          <div class="cat-help">
            Si está desactivada, no aparecerá en el header ni en filtros públicos.
          </div>
        </div>

      </div>

      <div class="cat-actions">
        <a href="{{ route('admin.category-products.index') }}" class="cat-btn-ghost">
          Cancelar
        </a>

        <button type="submit" class="cat-btn-primary">
          {{ $category->exists ? 'Guardar cambios' : 'Crear categoría' }}
        </button>
      </div>
    </form>

  </div>
</div>

@endsection