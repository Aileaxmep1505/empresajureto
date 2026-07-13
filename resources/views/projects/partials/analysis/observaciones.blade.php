@once
@push('styles')
<style>
  /* ==========================================================
     OBSERVACIONES - componente local encapsulado
     Diseño compacto tipo Matriz / Alcance (Idéntico a Referencia)
     ========================================================== */

  #pjdObservacionesComponent,
  #pjdObservacionesComponent * {
    box-sizing: border-box;
  }

  #pjdObservacionesComponent {
    --obs-card: #ffffff;
    --obs-ink: #111827;
    --obs-text: #374151;
    --obs-muted: #6b7280;
    --obs-line: #e5e7eb;
    --obs-blue: #2563eb;
    --obs-blue-soft: #eff6ff;
    --obs-purple: #9333ea;
    --obs-danger: #ef4444;

    /* Variables para el estado expandido */
    --obs-row-bg-open: #f8fafc;
    --obs-row-border-open: #bfdbfe;

    width: 100%;
    padding: 16px 20px 24px;
    background: #ffffff;
    color: var(--obs-text);
    font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  #pjdObservacionesComponent .pjd-obs-shell {
    position: relative;
    width: min(100%, 1360px);
    margin: 18px auto 28px;
    padding: 0;
    overflow: hidden;
  }

  .pjd-wrap.is-observaciones-expanded #pjdObservacionesComponent .pjd-obs-shell {
    width: calc(100% - 28px);
    max-width: none;
    margin-left: 14px;
    margin-right: 14px;
  }

  .pjd-wrap.is-observaciones-expanded .pjd-body {
    grid-template-columns: 0 0 minmax(360px, 1fr) !important;
  }

  .pjd-wrap.is-observaciones-expanded .pjd-left,
  .pjd-wrap.is-observaciones-expanded .pjd-resizer {
    opacity: 0 !important;
    pointer-events: none !important;
    overflow: hidden !important;
    border: 0 !important;
  }

  /* Herramientas (Expandir/Contraer) */
  #pjdObservacionesComponent .pjd-obs-tools {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 20px;
    padding-right: 4px;
  }

  #pjdObservacionesComponent .pjd-obs-tool {
    all: unset;
    width: 36px;
    height: 32px;
    border: 1px solid var(--obs-line);
    border-radius: 8px;
    background: #ffffff;
    color: var(--obs-muted);
    display: grid;
    place-items: center;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,.02);
    transition: all .15s ease;
  }

  #pjdObservacionesComponent .pjd-obs-tool:hover {
    border-color: #cbd5e1;
    color: var(--obs-blue);
    background: #f8fafc;
  }

  #pjdObservacionesComponent .pjd-obs-tool:active { transform: scale(.97); }

  /* Corrección forzada para SVGs de herramientas */
  #pjdObservacionesComponent .pjd-obs-tool svg,
  #pjdObservacionesComponent .pjd-obs-tool svg path,
  #pjdObservacionesComponent .pjd-obs-tool svg polyline,
  #pjdObservacionesComponent .pjd-obs-tool svg line {
    fill: none !important;
    stroke: currentColor !important;
  }

  #pjdObservacionesComponent .pjd-obs-tool svg { width: 18px; height: 18px; display: block; }

  /* FIX: especificidad suficiente para ocultar el ícono de contraer por defecto */
  #pjdObservacionesComponent .pjd-obs-tool svg.pjd-obs-icon-compress { display: none; }

  .pjd-wrap.is-observaciones-expanded #pjdObservacionesComponent .pjd-obs-tool[data-obs-expand] .pjd-obs-icon-expand { display: none; }
  .pjd-wrap.is-observaciones-expanded #pjdObservacionesComponent .pjd-obs-tool[data-obs-expand] .pjd-obs-icon-compress { display: block; }

  /* Contenedor Principal de Tarjetas (envuelve todas las secciones) */
  #pjdObservacionesComponent .pjd-obs-stack {
    width: 100%;

    display: flex;
    flex-direction: column;
    gap: 16px;
    padding: 24px;
    border: 1px solid var(--obs-line);
    border-radius: 16px;
    background: #ffffff;
    box-shadow: 0 4px 16px rgba(0,0,0,.02);
    margin-top: -50px;
  }

  /* Tarjeta de Categoría */
  #pjdObservacionesComponent .pjd-obs-card {
    width: 100%;
    border: 1px solid var(--obs-line);
    border-radius: 12px;
    background: var(--obs-card);
    box-shadow: 0 4px 16px rgba(0,0,0,.02);
    overflow: hidden;
  }

  #pjdObservacionesComponent .pjd-obs-card-head {
    width: 100%;
    min-height: 64px;
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    cursor: pointer;
    user-select: none;
    background: #ffffff;
    transition: background .15s ease;
  }

  #pjdObservacionesComponent .pjd-obs-card-head:hover {
    background: #f8fafc;
  }

  #pjdObservacionesComponent .pjd-obs-card-title {
    margin: 0;
    color: var(--obs-ink);
    font-size: 16px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 10px;
  }

  #pjdObservacionesComponent .pjd-obs-sparkle {
    width: 20px;
    height: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--obs-purple);
    flex-shrink: 0;
  }

  #pjdObservacionesComponent .pjd-obs-sparkle svg {
    width: 18px;
    height: 18px;
    display: block;
  }

  #pjdObservacionesComponent .pjd-obs-chev {
    width: 24px;
    height: 24px;
    color: #9ca3af;
    transition: transform .2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  #pjdObservacionesComponent .pjd-obs-chev svg {
    width: 20px;
    height: 20px;
    display: block;
  }

  #pjdObservacionesComponent .pjd-obs-card.is-open .pjd-obs-chev {
    transform: rotate(180deg);
  }

  /* Cuerpo de la Tarjeta */
  #pjdObservacionesComponent .pjd-obs-card-body {
    display: none;
    padding: 0 20px 20px;
    background: #ffffff;
  }

  #pjdObservacionesComponent .pjd-obs-card.is-open .pjd-obs-card-body {
    display: block;
  }

  /* NUEVO: la lista ahora separa las filas con gap para que cada una sea su propia tarjeta */
  #pjdObservacionesComponent .pjd-obs-list {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 4px 0 0;
  }

  /* Fila / Elemento Individual — ahora cada fila tiene su propio contenedor */
  #pjdObservacionesComponent .pjd-obs-row {
    width: 100%;
    display: flex;
    gap: 16px;
    align-items: flex-start;
    padding: 18px 20px;
    margin: 0;
    border: 1px solid var(--obs-line);
    border-radius: 12px;
    background: #ffffff;
    text-align: left;
    transition: all .2s ease;
    cursor: pointer;
  }

  #pjdObservacionesComponent .pjd-obs-row:hover {
    border-color: #cbd5e1;
    background: #fbfcfe;
  }

  /* ESTADO EXPANDIDO DE LA FILA */
  #pjdObservacionesComponent .pjd-obs-row.is-open {
    padding: 20px 24px !important;
    border: 1px solid var(--obs-row-border-open) !important;
    border-radius: 12px !important;
    background: var(--obs-row-bg-open) !important;
    cursor: default;
  }

  #pjdObservacionesComponent .pjd-obs-row-main {
    flex: 1;
    min-width: 0;
  }

  /* Header interno de la fila (Título + Basurero) */
  #pjdObservacionesComponent .pjd-obs-row-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 8px;
  }

  #pjdObservacionesComponent .pjd-obs-row-title {
    margin: 0;
    color: var(--obs-muted);
    font-size: 13px;
    line-height: 1.4;
    font-weight: 600;
    letter-spacing: .03em;
    text-transform: uppercase;
  }

  #pjdObservacionesComponent .pjd-obs-trash {
    all: unset;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    color: #9ca3af;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all .15s ease;
    flex-shrink: 0;
  }

  #pjdObservacionesComponent .pjd-obs-trash svg {
    width: 16px;
    height: 16px;
    display: block;
  }

  #pjdObservacionesComponent .pjd-obs-trash:hover {
    background: #fee2e2;
    color: var(--obs-danger);
  }

  #pjdObservacionesComponent .pjd-obs-trash:active {
    transform: scale(.95);
  }

  #pjdObservacionesComponent .pjd-obs-row-text {
    margin: 0;
    color: var(--obs-ink);
    font-size: 15px;
    line-height: 1.5;
    font-weight: 400;
  }

  /* ==========================================================
     BLOQUE EXTRA (Solo visible cuando .is-open)
     ========================================================== */
  #pjdObservacionesComponent .pjd-obs-row-extra {
    display: none;
    margin-top: 24px;
    border-top: 1px solid #e2e8f0;
    padding-top: 24px;
  }

  #pjdObservacionesComponent .pjd-obs-row.is-open .pjd-obs-row-extra {
    display: block;
  }

  /* Cita del documento */
  #pjdObservacionesComponent .pjd-obs-quote-label {
    font-size: 12px;
    font-weight: 700;
    color: #94a3b8;
    margin-bottom: 10px;
    letter-spacing: 0.05em;
  }

  #pjdObservacionesComponent .pjd-obs-quote-text {
    position: relative;
    padding-left: 18px;
    font-size: 14px;
    font-style: italic;
    color: #334155;
    margin: 0 0 24px 0;
    line-height: 1.5;
  }

  #pjdObservacionesComponent .pjd-obs-quote-text::before {
    content: "";
    position: absolute;
    left: 0;
    top: 8px;
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #cbd5e1;
  }

  /* Fuente Original Box */
  #pjdObservacionesComponent .pjd-obs-source-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 18px;
    margin-bottom: 24px;
  }

  #pjdObservacionesComponent .pjd-obs-source-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    font-weight: 700;
    color: #94a3b8;
    margin-bottom: 10px;
    letter-spacing: 0.05em;
  }

  #pjdObservacionesComponent .pjd-obs-source-label svg {
    width: 14px;
    height: 14px;
    color: #94a3b8;
  }

  #pjdObservacionesComponent .pjd-obs-source-link {
    position: relative;
    padding-left: 14px;
    font-size: 14px;
  }

  #pjdObservacionesComponent .pjd-obs-source-link::before {
    content: "";
    position: absolute;
    left: 0;
    top: 9px;
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: var(--obs-blue);
  }

  #pjdObservacionesComponent .pjd-obs-source-link a {
    color: var(--obs-blue);
    text-decoration: underline;
    font-weight: 500;
  }

  #pjdObservacionesComponent .pjd-obs-source-link a:hover {
    text-decoration: none;
  }

  /* Botón de Detalles */
  #pjdObservacionesComponent .pjd-obs-btn-details {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border: 1px solid var(--obs-row-border-open);
    background: #ffffff;
    color: var(--obs-blue);
    border-radius: 999px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.15s ease;
  }

  #pjdObservacionesComponent .pjd-obs-btn-details:hover {
    background: var(--obs-blue-soft);
  }

  #pjdObservacionesComponent .pjd-obs-btn-details svg {
    width: 16px;
    height: 16px;
  }

  @media (max-width: 760px) {
    #pjdObservacionesComponent { padding: 12px; }
    #pjdObservacionesComponent .pjd-obs-stack { padding: 12px; }
    #pjdObservacionesComponent .pjd-obs-card-head { padding: 16px 18px; }
    #pjdObservacionesComponent .pjd-obs-card-body { padding: 0 16px 16px; }
    #pjdObservacionesComponent .pjd-obs-row { padding: 16px !important; }
    #pjdObservacionesComponent .pjd-obs-row-header { flex-wrap: wrap; gap: 8px; }
    #pjdObservacionesComponent .pjd-obs-trash { margin-left: auto; }
  }
</style>
@endpush
@endonce

<div class="pjd-pane" data-pane="observaciones">
  @php
    $obsSparkle = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.7 5.1L19 10l-5.3 1.9L12 17l-1.7-5.1L5 10l5.3-1.9L12 3Z"></path><path d="M19 4v4"></path><path d="M17 6h4"></path><path d="M5 16v4"></path><path d="M3 18h4"></path></svg>';

    // Datos ampliados con citas y fuentes simulando la respuesta de IA
    $obsSections = collect(data_get($sd, 'observaciones.secciones', []))
      ->filter(fn ($section) => is_array($section))
      ->values()
      ->all()
  @endphp

  <div id="pjdObservacionesComponent" class="pjd-obs-root">
    <div class="pjd-obs-shell">
      <div class="pjd-obs-tools" aria-label="Acciones de observaciones">
        <button type="button" class="pjd-obs-tool" data-obs-expand id="pjdObsExpandBtn" title="Estirar vista" aria-label="Estirar vista" aria-expanded="false">
          <svg class="pjd-obs-icon-expand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"></path><path d="M9 21H3v-6"></path><path d="M21 3l-7 7"></path><path d="M3 21l7-7"></path></svg>
          <svg class="pjd-obs-icon-compress" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3v6H3"></path><path d="M15 21v-6h6"></path><path d="M3 9l7-7"></path><path d="M21 15l-7 7"></path></svg>
        </button>
      </div>

      <div class="pjd-obs-stack">
        @forelse($obsSections as $sectionIndex => $section)
          {{-- Mantenemos la primera sección abierta por defecto para emular el diseño --}}
          <section class="pjd-obs-card {{ $sectionIndex === 0 ? 'is-open' : '' }}">
            <div class="pjd-obs-card-head" data-obs-toggle role="button" tabindex="0" aria-expanded="{{ $sectionIndex === 0 ? 'true' : 'false' }}">
              <h3 class="pjd-obs-card-title">
                {{ $section['title'] }}
                <span class="pjd-obs-sparkle" aria-hidden="true">{!! $obsSparkle !!}</span>
              </h3>
              <span class="pjd-obs-chev" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"></path></svg>
              </span>
            </div>

            <div class="pjd-obs-card-body">
              <div class="pjd-obs-list">
                @foreach($section['items'] as $itemIndex => $item)
                  {{-- Mantenemos el primer ítem abierto por defecto --}}
                  <article class="pjd-obs-row {{ ($sectionIndex === 0 && $itemIndex === 0) ? 'is-open' : '' }}">
                    <div class="pjd-obs-row-main">

                      {{-- Encabezado del Row (Título y Trash) --}}
                      <div class="pjd-obs-row-header">
                        <h4 class="pjd-obs-row-title">{{ mb_strtoupper($item['title']) }}</h4>
                        <button type="button" class="pjd-obs-trash" title="Eliminar visual" aria-label="Eliminar observación">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg>
                        </button>
                      </div>

                      {{-- Texto Principal --}}
                      <p class="pjd-obs-row-text">{{ $item['text'] }}</p>

                      {{-- Bloque Extra Expandible --}}
                      <div class="pjd-obs-row-extra">

                        @if(!empty($item['quote']))
                          <div class="pjd-obs-quote-label">CITA DEL DOCUMENTO</div>
                          <div class="pjd-obs-quote-text">{{ $item['quote'] }}</div>
                        @endif

                        @if(!empty($item['source']))
                          <div class="pjd-obs-source-box">
                            <div class="pjd-obs-source-label">
                              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                              </svg>
                              FUENTE ORIGINAL
                            </div>
                            <div class="pjd-obs-source-link">
                              <a href="#">{{ $item['source'] }}</a>
                            </div>
                          </div>
                        @endif

                        <button type="button" class="pjd-obs-btn-details">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"></path>
                            <path d="M8 12h.01"></path>
                            <path d="M12 12h.01"></path>
                            <path d="M16 12h.01"></path>
                          </svg>
                          Más detalles
                        </button>

                      </div>
                    </div>
                  </article>
                @endforeach
              </div>
            </div>
          </section>
        @empty
        <div class="pjd-obs-empty">
          No se detectaron observaciones sustentadas en los documentos cargados.
        </div>
      @endforelse
      </div>
    </div>
  </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const root = document.getElementById('pjdObservacionesComponent');
  if (!root) return;

  // Toggle de la Tarjeta (Sección)
  root.querySelectorAll('[data-obs-toggle]').forEach(function (head) {
    const card = head.closest('.pjd-obs-card');
    if (!card) return;

    const toggle = function () {
      const open = card.classList.toggle('is-open');
      head.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    head.addEventListener('click', function (event) {
      if (event.target.closest('a, button, select, form')) return;
      toggle();
    });

    head.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      event.preventDefault();
      toggle();
    });
  });

  // Toggle de cada Fila individual
  root.querySelectorAll('.pjd-obs-row').forEach(function (row) {
    row.addEventListener('click', function (event) {
      // Prevenir el clic si se hace sobre un botón o enlace interno
      if (event.target.closest('a, button, select, form')) return;

      // Si ya está abierto y le dan clic, se puede cerrar o mantener,
      // pero usualmente funciona como acordeón:
      const wasOpen = row.classList.contains('is-open');

      // Opcional: Cerrar todos los demás antes de abrir (comentar si quieres múltiples abiertos)
      const list = row.closest('.pjd-obs-list');
      if (list) {
        list.querySelectorAll('.pjd-obs-row.is-open').forEach(r => r.classList.remove('is-open'));
      }

      if (!wasOpen) {
        row.classList.add('is-open');
      }
    });
  });

  // Toggle de Expandir Vista Global
  const expandBtn = document.getElementById('pjdObsExpandBtn');
  const wrap = document.querySelector('.pjd-wrap');

  if (expandBtn && wrap) {
    expandBtn.addEventListener('click', function () {
      const expanded = wrap.classList.toggle('is-observaciones-expanded');
      expandBtn.classList.toggle('is-active', expanded);
      expandBtn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      expandBtn.setAttribute('title', expanded ? 'Contraer vista' : 'Estirar vista');
      expandBtn.setAttribute('aria-label', expanded ? 'Contraer vista' : 'Estirar vista');
      window.dispatchEvent(new Event('resize'));
    });
  }
});
</script>
@endpush
@endonce