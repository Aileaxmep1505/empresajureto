@extends('layouts.app')

@section('title', 'Banners de inicio')

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

  .banner-admin-page {
    font-family: 'Quicksand', sans-serif;
    background: var(--bg);
    color: var(--ink);
    padding: 32px;
    min-height: 100vh;
  }

  .banner-admin-shell {
    max-width: 1180px;
    margin: 0 auto;
  }

  .banner-admin-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
  }

  .banner-admin-title {
    margin: 0;
    color: #111111;
    font-size: 28px;
    font-weight: 700;
    letter-spacing: -0.03em;
  }

  .banner-admin-subtitle {
    margin: 6px 0 0;
    color: var(--muted);
    font-size: 15px;
    font-weight: 500;
  }

  .banner-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    overflow: hidden;
  }

  .banner-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(0,0,0,0.04);
    transition: 0.2s ease;
  }

  .btn-primary-soft,
  .btn-ghost,
  .btn-danger-soft {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 38px;
    padding: 0 16px;
    border-radius: 999px;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: 0.2s ease;
    border: 0;
  }

  .btn-primary-soft {
    background: var(--blue);
    color: #ffffff;
  }

  .btn-primary-soft:hover {
    filter: brightness(0.97);
    color: #ffffff;
  }

  .btn-ghost {
    background: transparent;
    color: #555555;
  }

  .btn-ghost:hover {
    background: #f9fafb;
    color: #111111;
  }

  .btn-danger-soft {
    background: var(--danger-soft);
    color: var(--danger);
  }

  .btn-primary-soft:active,
  .btn-ghost:active,
  .btn-danger-soft:active {
    transform: scale(0.98);
  }

  .alert-success {
    background: var(--success-soft);
    color: var(--success);
    border-radius: 12px;
    padding: 14px 16px;
    font-weight: 700;
    margin-bottom: 18px;
  }

  .banner-table-wrap {
    width: 100%;
    overflow-x: auto;
  }

  .banner-table {
    width: 100%;
    border-collapse: collapse;
  }

  .banner-table th {
    text-align: left;
    padding: 16px;
    color: #111111;
    font-size: 13px;
    font-weight: 700;
    border-bottom: 1px solid var(--line);
    white-space: nowrap;
  }

  .banner-table td {
    padding: 16px;
    border-bottom: 1px solid var(--line);
    vertical-align: middle;
    font-size: 14px;
  }

  .banner-table tr:last-child td {
    border-bottom: 0;
  }

  .banner-thumb {
    width: 96px;
    height: 56px;
    border-radius: 12px;
    object-fit: cover;
    border: 1px solid var(--line);
    background: #f9fafb;
  }

  .banner-title-cell {
    font-weight: 700;
    color: #111111;
  }

  .banner-desc-cell {
    margin-top: 4px;
    color: var(--muted);
    font-size: 13px;
    max-width: 360px;
  }

  .badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
  }

  .badge-info {
    background: var(--blue-soft);
    color: var(--blue);
  }

  .badge-success {
    background: var(--success-soft);
    color: var(--success);
  }

  .badge-danger {
    background: var(--danger-soft);
    color: var(--danger);
  }

  .banner-actions {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .empty-state {
    padding: 48px 24px;
    text-align: center;
  }

  .empty-state h3 {
    color: #111111;
    font-size: 22px;
    margin: 0 0 8px;
  }

  .empty-state p {
    color: var(--muted);
    margin: 0 0 20px;
  }

  @media (max-width: 768px) {
    .banner-admin-page {
      padding: 20px;
    }

    .banner-admin-header {
      flex-direction: column;
      align-items: flex-start;
    }
  }
</style>

<div class="banner-admin-page">
  <div class="banner-admin-shell">

    <div class="banner-admin-header">
      <div>
        <h1 class="banner-admin-title">Banners de inicio</h1>
        <p class="banner-admin-subtitle">
          Publica, edita, ordena o desactiva los banners del home sin tocar código.
        </p>
      </div>

      <a href="{{ route('admin.home-banners.create') }}" class="btn-primary-soft">
        Nuevo banner
      </a>
    </div>

    @if(session('success'))
      <div class="alert-success">
        {{ session('success') }}
      </div>
    @endif

    <div class="banner-card">
      @if($banners->count())
        <div class="banner-table-wrap">
          <table class="banner-table">
            <thead>
              <tr>
                <th>Imagen</th>
                <th>Contenido</th>
                <th>Posición</th>
                <th>Orden</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>

            <tbody>
              @foreach($banners as $banner)
                <tr>
                  <td>
                    @if($banner->image_path)
                      <img
                        src="{{ asset('storage/' . $banner->image_path) }}"
                        alt="{{ $banner->title }}"
                        class="banner-thumb"
                      >
                    @else
                      <div class="banner-thumb"></div>
                    @endif
                  </td>

                  <td>
                    <div class="banner-title-cell">{{ $banner->title }}</div>

                    @if($banner->description)
                      <div class="banner-desc-cell">
                        {{ Str::limit($banner->description, 110) }}
                      </div>
                    @endif
                  </td>

                  <td>
                    <span class="badge badge-info">
                      {{ $banner->position === 'main' ? 'Principal' : 'Lateral' }}
                    </span>
                  </td>

                  <td>
                    {{ $banner->sort_order }}
                  </td>

                  <td>
                    @if($banner->is_active)
                      <span class="badge badge-success">Publicado</span>
                    @else
                      <span class="badge badge-danger">Oculto</span>
                    @endif
                  </td>

                  <td>
                    <div class="banner-actions">
                      <a href="{{ route('admin.home-banners.edit', $banner) }}" class="btn-ghost">
                        Editar
                      </a>

                      <form
                        action="{{ route('admin.home-banners.destroy', $banner) }}"
                        method="POST"
                        onsubmit="return confirm('¿Seguro que deseas eliminar este banner?')"
                      >
                        @csrf
                        @method('DELETE')

                        <button type="submit" class="btn-danger-soft">
                          Eliminar
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="empty-state">
          <h3>Aún no tienes banners publicados</h3>
          <p>Crea tu primer banner para mostrarlo en la página de inicio.</p>

          <a href="{{ route('admin.home-banners.create') }}" class="btn-primary-soft">
            Crear banner
          </a>
        </div>
      @endif
    </div>

    <div style="margin-top: 20px;">
      {{ $banners->links() }}
    </div>

  </div>
</div>

@endsection