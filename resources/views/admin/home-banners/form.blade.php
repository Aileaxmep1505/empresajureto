@extends('layouts.app')

@section('title', $banner->exists ? 'Editar banner' : 'Nuevo banner')

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

  .banner-form-page {
    font-family: 'Quicksand', sans-serif;
    background: var(--bg);
    color: var(--ink);
    padding: 32px;
    min-height: 100vh;
  }

  .banner-form-shell {
    max-width: 920px;
    margin: 0 auto;
  }

  .banner-form-header {
    margin-bottom: 24px;
  }

  .banner-form-title {
    margin: 0;
    color: #111111;
    font-size: 28px;
    font-weight: 700;
    letter-spacing: -0.03em;
  }

  .banner-form-subtitle {
    margin: 6px 0 0;
    color: var(--muted);
    font-size: 15px;
    font-weight: 500;
  }

  .banner-form-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    padding: 28px;
  }

  .form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .form-group.full {
    grid-column: 1 / -1;
  }

  .form-label {
    color: #111111;
    font-size: 14px;
    font-weight: 700;
  }

  .form-control,
  .form-select,
  .form-textarea {
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

  .form-textarea {
    min-height: 110px;
    resize: vertical;
  }

  .form-control:focus,
  .form-select:focus,
  .form-textarea:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .form-help {
    color: var(--muted);
    font-size: 13px;
    line-height: 1.4;
  }

  .form-error {
    color: var(--danger);
    font-size: 13px;
    font-weight: 700;
  }

  .checkbox-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding-top: 8px;
  }

  .checkbox-row input {
    width: 18px;
    height: 18px;
    accent-color: var(--blue);
  }

  .preview-image {
    width: 100%;
    max-width: 360px;
    height: 160px;
    object-fit: cover;
    border-radius: 14px;
    border: 1px solid var(--line);
    background: #f9fafb;
  }

  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 26px;
  }

  .btn-primary,
  .btn-outline,
  .btn-ghost {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 42px;
    padding: 0 20px;
    border-radius: 999px;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: 0.2s ease;
    font-family: 'Quicksand', sans-serif;
  }

  .btn-primary {
    background: var(--blue);
    color: #ffffff;
    border: 0;
  }

  .btn-primary:hover {
    filter: brightness(0.97);
    color: #ffffff;
  }

  .btn-outline {
    background: #ffffff;
    color: var(--blue);
    border: 1px solid var(--blue);
  }

  .btn-outline:hover {
    background: var(--blue-soft);
  }

  .btn-ghost {
    background: transparent;
    color: #555555;
    border: 0;
  }

  .btn-ghost:hover {
    background: #f9fafb;
    color: #111111;
  }

  .btn-primary:active,
  .btn-outline:active,
  .btn-ghost:active {
    transform: scale(0.98);
  }

  @media (max-width: 768px) {
    .banner-form-page {
      padding: 20px;
    }

    .banner-form-card {
      padding: 20px;
    }

    .form-grid {
      grid-template-columns: 1fr;
    }

    .form-actions {
      flex-direction: column-reverse;
      align-items: stretch;
    }
  }
</style>

<div class="banner-form-page">
  <div class="banner-form-shell">

    <div class="banner-form-header">
      <h1 class="banner-form-title">
        {{ $banner->exists ? 'Editar banner' : 'Nuevo banner' }}
      </h1>

      <p class="banner-form-subtitle">
        Configura el contenido que aparecerá en la página de inicio.
      </p>
    </div>

    <form
      class="banner-form-card"
      action="{{ $banner->exists ? route('admin.home-banners.update', $banner) : route('admin.home-banners.store') }}"
      method="POST"
      enctype="multipart/form-data"
    >
      @csrf

      @if($banner->exists)
        @method('PUT')
      @endif

      <div class="form-grid">

        <div class="form-group full">
          <label class="form-label" for="title">Título</label>
          <input
            type="text"
            id="title"
            name="title"
            class="form-control"
            value="{{ old('title', $banner->title) }}"
            placeholder="Ej. Compra en nuestra Papelería"
            required
          >

          @error('title')
            <div class="form-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group full">
          <label class="form-label" for="description">Descripción</label>
          <textarea
            id="description"
            name="description"
            class="form-textarea"
            placeholder="Ej. Descubre miles de productos y compra a precios de mayoreo."
          >{{ old('description', $banner->description) }}</textarea>

          @error('description')
            <div class="form-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group">
          <label class="form-label" for="button_text">Texto del botón</label>
          <input
            type="text"
            id="button_text"
            name="button_text"
            class="form-control"
            value="{{ old('button_text', $banner->button_text) }}"
            placeholder="Ej. Ver catálogo"
          >

          @error('button_text')
            <div class="form-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group">
          <label class="form-label" for="button_url">URL del botón</label>
          <input
            type="text"
            id="button_url"
            name="button_url"
            class="form-control"
            value="{{ old('button_url', $banner->button_url) }}"
            placeholder="/catalogo?s=Escolares"
          >

          <div class="form-help">
            Puedes poner una ruta interna como <strong>/catalogo</strong> o una URL completa.
          </div>

          @error('button_url')
            <div class="form-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group">
          <label class="form-label" for="position">Posición</label>
          <select id="position" name="position" class="form-select" required>
            <option value="main" @selected(old('position', $banner->position) === 'main')>
              Principal / Slider
            </option>

            <option value="side" @selected(old('position', $banner->position) === 'side')>
              Lateral
            </option>
          </select>

          @error('position')
            <div class="form-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group">
          <label class="form-label" for="sort_order">Orden</label>
          <input
            type="number"
            id="sort_order"
            name="sort_order"
            class="form-control"
            min="0"
            value="{{ old('sort_order', $banner->sort_order ?? 0) }}"
          >

          @error('sort_order')
            <div class="form-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group">
          <label class="form-label" for="background_color">Color de fondo</label>
          <input
            type="text"
            id="background_color"
            name="background_color"
            class="form-control"
            value="{{ old('background_color', $banner->background_color ?? '#ffffff') }}"
            placeholder="#ffffff"
          >

          @error('background_color')
            <div class="form-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group">
          <label class="form-label" for="image">Imagen del banner</label>
          <input
            type="file"
            id="image"
            name="image"
            class="form-control"
            accept="image/png,image/jpeg,image/webp"
          >

          <div class="form-help">
            Recomendado: imagen horizontal en JPG, PNG o WEBP. Peso máximo: 4MB.
          </div>

          @error('image')
            <div class="form-error">{{ $message }}</div>
          @enderror
        </div>

        @if($banner->image_path)
          <div class="form-group full">
            <label class="form-label">Imagen actual</label>
            <img
              src="{{ asset('storage/' . $banner->image_path) }}"
              alt="{{ $banner->title }}"
              class="preview-image"
            >
          </div>
        @endif

        <div class="form-group full">
          <label class="checkbox-row">
            <input
              type="checkbox"
              name="is_active"
              value="1"
              @checked(old('is_active', $banner->is_active))
            >

            <span class="form-label">Publicar banner</span>
          </label>
        </div>

      </div>

      <div class="form-actions">
        <a href="{{ route('admin.home-banners.index') }}" class="btn-ghost">
          Cancelar
        </a>

        <button type="submit" class="btn-primary">
          {{ $banner->exists ? 'Guardar cambios' : 'Publicar banner' }}
        </button>
      </div>
    </form>

  </div>
</div>

@endsection