@once
@push('styles')
<style>
  .pjd-topbar .pjd-tab,
  .pjd-topbar .pjd-view-doc {
    font-size: 12px !important;
  }

  .pjd-topbar .pjd-tab svg,
  .pjd-topbar .pjd-view-doc svg {
    width: 15px;
    height: 15px;
  }

  /*
  |--------------------------------------------------------------------------
  | Evita que el contenido del topbar baje a otra línea
  |--------------------------------------------------------------------------
  | No modifica colores, tamaños, posiciones ni diseño original.
  */

  .pjd-topbar {
    flex-wrap: nowrap !important;
    overflow: hidden !important;
  }

  .pjd-topbar .pjd-back {
    flex: 0 0 auto !important;
  }

  .pjd-topbar .pjd-title {
    min-width: 0;
    flex: 0 1 auto;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .pjd-topbar .pjd-status-pill {
    flex: 0 0 auto;
  }

  .pjd-topbar .pjd-tabs {
    min-width: 0;
    flex: 1 1 auto;

    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    align-items: center;

    white-space: nowrap;
    overflow: hidden !important;
  }

  .pjd-topbar .pjd-tab,
  .pjd-topbar .pjd-view-doc {
    flex: 0 0 auto !important;
    white-space: nowrap !important;
  }

  /*
  |--------------------------------------------------------------------------
  | Cuando Studio está abierto
  |--------------------------------------------------------------------------
  | Solo desaparece "Ver documento".
  | Las demás pestañas mantienen su posición y diseño.
  */

  body.pjd-studio-open .pjd-topbar .pjd-view-doc {
    display: none !important;
  }

  /*
  |--------------------------------------------------------------------------
  | Cuando Studio está contraído
  |--------------------------------------------------------------------------
  | Vuelve a aparecer "Ver documento".
  */

  body.pjd-studio-collapsed .pjd-topbar .pjd-view-doc {
    display: inline-flex !important;
  }
</style>
@endpush
@endonce

<div class="pjd-topbar">
  <a
    href="{{ route('projects.index') }}"
    class="pjd-back"
    title="Volver"
  >
    ←
  </a>

  <div class="pjd-title">
    {{ $project->name }}

    <span class="pjd-status-pill {{ $statusClass }}">
      {{ $statusLabel }}
    </span>
  </div>

  <div class="pjd-tabs" id="pjdTabs">
    <button
      type="button"
      class="pjd-tab"
      data-tab="inicio"
    >
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path d="M3 11l9-8 9 8"></path>
        <path d="M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9"></path>
      </svg>

      Inicio
    </button>

    <button
      type="button"
      class="pjd-tab is-active"
      data-tab="ficha"
    >
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
        <path d="M14 2v6h6"></path>
      </svg>

      Ficha
    </button>

    <button
      type="button"
      class="pjd-tab"
      data-tab="eventos"
    >
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
        <path d="M16 2v4"></path>
        <path d="M8 2v4"></path>
        <path d="M3 10h18"></path>
      </svg>

      Eventos
    </button>

    <button
      type="button"
      class="pjd-tab"
      data-tab="matriz"
    >
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path d="M9 11l3 3L22 4"></path>
        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
      </svg>

      Matriz de cumplimiento
    </button>

    <button
      type="button"
      class="pjd-tab"
      data-tab="financiero"
    >
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <circle cx="12" cy="12" r="9"></circle>
        <path d="M12 7v10"></path>
        <path d="M16 9.5H10a2 2 0 0 0 0 4h4a2 2 0 0 1 0 4H8"></path>
      </svg>

      Financiero
    </button>

    <button
      type="button"
      class="pjd-tab"
      data-tab="alcance"
    >
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path d="M20.5 7.5 12 16 8 12l8.5-8.5a2.12 2.12 0 0 1 3 3Z"></path>
        <path d="M14 5 19 10"></path>
        <path d="M4 20l4-1 10.5-10.5"></path>
      </svg>

      Alcance
    </button>

    <button
      type="button"
      class="pjd-tab"
      data-tab="observaciones"
    >
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path>
        <path d="M8 9h8M8 13h5"></path>
      </svg>

      Observaciones
    </button>

    <button
      type="button"
      class="pjd-tab"
      data-tab="documentos"
    >
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
      </svg>

      Documentos ({{ $project->documents->count() }})
    </button>

    <button
      type="button"
      class="pjd-tab"
      data-tab="checklist"
    >
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path d="M9 11l3 3L22 4"></path>
        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
      </svg>

      Checklist
    </button>

    @if($project->documents->isNotEmpty())
      <a
        href="{{ Storage::disk('public')->url($project->documents->first()->file_path) }}"
        target="_blank"
        rel="noopener noreferrer"
        class="pjd-view-doc"
      >
        <svg
          width="16"
          height="16"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.8"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
          <circle cx="12" cy="12" r="3"></circle>
        </svg>

        Ver documento
      </a>
    @endif
  </div>
</div>