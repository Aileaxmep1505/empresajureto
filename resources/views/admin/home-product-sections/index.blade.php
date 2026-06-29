@extends('layouts.app')

@section('title', 'Filas de productos')

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

  .hp-page {
    min-height: 100vh;
    padding: 32px;
    background: var(--bg);
    color: var(--ink);
    font-family: 'Quicksand', sans-serif;
  }

  .hp-shell {
    max-width: 1180px;
    margin: 0 auto;
  }

  .hp-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 24px;
  }

  .hp-title {
    margin: 0;
    color: #111111;
    font-size: 30px;
    font-weight: 700;
    letter-spacing: -0.03em;
  }

  .hp-subtitle {
    margin: 8px 0 0;
    color: var(--muted);
    font-size: 15px;
    font-weight: 500;
  }

  .hp-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    overflow: hidden;
  }

  .hp-table-wrap {
    width: 100%;
    overflow-x: auto;
  }

  .hp-table {
    width: 100%;
    border-collapse: collapse;
  }

  .hp-table th {
    text-align: left;
    padding: 16px;
    color: #111111;
    font-size: 13px;
    font-weight: 700;
    border-bottom: 1px solid var(--line);
    white-space: nowrap;
  }

  .hp-table td {
    padding: 16px;
    border-bottom: 1px solid var(--line);
    vertical-align: middle;
    font-size: 14px;
  }

  .hp-table tr:last-child td {
    border-bottom: 0;
  }

  .hp-name {
    font-weight: 700;
    color: #111111;
  }

  .hp-meta {
    margin-top: 4px;
    color: var(--muted);
    font-size: 13px;
  }

  .hp-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
  }

  .hp-badge-info {
    background: var(--blue-soft);
    color: var(--blue);
  }

  .hp-badge-success {
    background: var(--success-soft);
    color: var(--success);
  }

  .hp-badge-danger {
    background: var(--danger-soft);
    color: var(--danger);
  }

  .hp-actions {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .hp-btn-primary,
  .hp-btn-ghost,
  .hp-btn-danger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 38px;
    padding: 0 16px;
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

  .hp-btn-danger {
    background: var(--danger-soft);
    color: var(--danger);
  }

  .hp-btn-primary:active,
  .hp-btn-ghost:active,
  .hp-btn-danger:active {
    transform: scale(0.98);
  }

  .hp-alert {
    background: var(--success-soft);
    color: var(--success);
    border-radius: 12px;
    padding: 14px 16px;
    font-weight: 700;
    margin-bottom: 18px;
  }

  .hp-empty {
    text-align: center;
    padding: 50px 24px;
  }

  .hp-empty h3 {
    margin: 0 0 8px;
    color: #111111;
    font-size: 22px;
  }

  .hp-empty p {
    margin: 0 0 20px;
    color: var(--muted);
  }

  @media (max-width: 768px) {
    .hp-page {
      padding: 20px;
    }

    .hp-header {
      flex-direction: column;
    }
  }
</style>

<div class="hp-page">
  <div class="hp-shell">

    <div class="hp-header">
      <div>
        <h1 class="hp-title">Filas de productos</h1>
        <p class="hp-subtitle">
          Crea filas como Mundial, Hot Sale, Buen Fin o categorías del catálogo sin tocar código.
        </p>
      </div>

      <a href="{{ route('admin.home-product-sections.create') }}" class="hp-btn-primary">
        Nueva fila
      </a>
    </div>

    @if(session('success'))
      <div class="hp-alert">
        {{ session('success') }}
      </div>
    @endif

    <div class="hp-card">
      @if($sections->count())
        <div class="hp-table-wrap">
          <table class="hp-table">
            <thead>
              <tr>
                <th>Fila</th>
                <th>Tipo</th>
                <th>Categoría</th>
                <th>Productos</th>
                <th>Vigencia</th>
                <th>Orden</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>

            <tbody>
              @foreach($sections as $section)
                <tr>
                  <td>
                    <div class="hp-name">{{ $section->title }}</div>
                    <div class="hp-meta">{{ $section->slug }}</div>
                  </td>

                  <td>
                    <span class="hp-badge hp-badge-info">
                      {{ $section->source_type === 'category' ? 'Categoría' : 'Manual' }}
                    </span>
                  </td>

                  <td>
                    {{ $section->categoryProduct?->full_path ?? '—' }}
                  </td>

                  <td>
                    @if($section->source_type === 'category')
                      Hasta {{ $section->products_limit }}
                    @else
                      {{ $section->items_count }}
                    @endif
                  </td>

                  <td>
                    <div class="hp-meta">
                      Inicio: {{ $section->starts_at ? $section->starts_at->format('d/m/Y') : 'Siempre' }}
                    </div>
                    <div class="hp-meta">
                      Fin: {{ $section->ends_at ? $section->ends_at->format('d/m/Y') : 'Sin fin' }}
                    </div>
                  </td>

                  <td>{{ $section->sort_order }}</td>

                  <td>
                    @if($section->is_active)
                      <span class="hp-badge hp-badge-success">Activa</span>
                    @else
                      <span class="hp-badge hp-badge-danger">Oculta</span>
                    @endif
                  </td>

                  <td>
                    <div class="hp-actions">
                      <a href="{{ route('admin.home-product-sections.edit', $section) }}" class="hp-btn-ghost">
                        Editar
                      </a>

                      <form
                        method="POST"
                        action="{{ route('admin.home-product-sections.destroy', $section) }}"
                        onsubmit="return confirm('¿Seguro que deseas eliminar esta fila?')"
                      >
                        @csrf
                        @method('DELETE')

                        <button type="submit" class="hp-btn-danger">
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
        <div class="hp-empty">
          <h3>Aún no tienes filas creadas</h3>
          <p>Crea tu primera fila para mostrar productos en el inicio.</p>

          <a href="{{ route('admin.home-product-sections.create') }}" class="hp-btn-primary">
            Crear fila
          </a>
        </div>
      @endif
    </div>

    <div style="margin-top: 20px;">
      {{ $sections->links() }}
    </div>

  </div>
</div>

@endsection