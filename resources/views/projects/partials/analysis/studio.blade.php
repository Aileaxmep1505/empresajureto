@once
@push('styles')
<style>
  /* ==========================================================
     STUDIO DERECHO - Diseño Compacto y Preciso
     ========================================================== */

  #pjdStudioRight,
  #pjdStudioRight * {
    box-sizing: border-box;
  }

  #pjdStudioRight {
    --studio-bg: #ffffff;
    --studio-ink: #1f2937;
    --studio-muted: #6b7280;
    --studio-line: #e5e7eb;

    --studio-purple: #7c3aed;
    --studio-purple-soft: #f4edff;
    --studio-orange: #c45a00;
    --studio-orange-soft: #fff5e6;
    --studio-green: #00856f;
    --studio-green-soft: #e7f7ef;

    --studio-blue-badge: #2563eb;
    --studio-green-badge: #10b981;

    position: fixed;
    top: 113px; /* Ajuste para que no choque con el topbar */
    right: 0;
    bottom: 0;
    z-index: 45;
    width: 360px;
    background: var(--studio-bg);
    border-left: 1px solid var(--studio-line);
    box-shadow: -10px 0 28px rgba(15, 23, 42, .03);
    color: var(--studio-ink);
    font-family: 'Quicksand', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    transition: width .2s ease;
  }

  body.pjd-studio-open .pjd-body { padding-right: 360px; }
  body.pjd-studio-collapsed .pjd-body { padding-right: 64px; }
  body.pjd-studio-collapsed #pjdStudioRight { width: 64px; }

  #pjdStudioRight .pjd-studio-inner {
    width: 100%;
    height: 100%;
    overflow: hidden;
    background: #ffffff;
  }

  /* PANEL ABIERTO */
  #pjdStudioRight .pjd-studio-panel {
    width: 360px;
    height: 100%;
    padding: 20px 16px;
    overflow-y: auto;
    overflow-x: hidden;
    transition: opacity .15s;
  }

  body.pjd-studio-collapsed #pjdStudioRight .pjd-studio-panel {
    opacity: 0;
    pointer-events: none;
  }

  #pjdStudioRight .pjd-studio-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    padding: 0 4px;
  }

  #pjdStudioRight .pjd-studio-title {
    all: unset;
    display: block;
    color: var(--studio-ink);
    font-size: 18px;
    font-weight: 700;
    letter-spacing: -.01em;
  }

  /* Botones Toggle */
  #pjdStudioRight .pjd-studio-toggle,
  #pjdStudioRight .pjd-studio-rail-toggle {
    all: unset;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    color: #4b5563;
    display: grid;
    place-items: center;
    cursor: pointer;
    transition: background .15s;
  }

  #pjdStudioRight .pjd-studio-toggle:hover,
  #pjdStudioRight .pjd-studio-rail-toggle:hover {
    background: #f3f4f6;
    color: #111827;
  }

  #pjdStudioRight .pjd-studio-toggle svg,
  #pjdStudioRight .pjd-studio-rail-toggle svg {
    width: 20px;
    height: 20px;
  }

  /* Top 3 Actions */
  #pjdStudioRight .pjd-studio-actions {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-bottom: 24px;
  }

  #pjdStudioRight .pjd-studio-action-card {
    all: unset;
    position: relative;
    padding: 10px 8px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    box-sizing: border-box;
    transition: transform .15s, box-shadow .15s;
  }

  #pjdStudioRight .pjd-studio-action-card:hover {
    transform: translateY(-1px);
  }

  #pjdStudioRight .pjd-studio-action-card:active {
    transform: scale(.97);
  }

  #pjdStudioRight .pjd-studio-action-card.is-purple {
    background: var(--studio-purple-soft);
    color: var(--studio-purple);
  }

  #pjdStudioRight .pjd-studio-action-card.is-orange {
    background: var(--studio-orange-soft);
    color: var(--studio-orange);
  }

  #pjdStudioRight .pjd-studio-action-card.is-green {
    background: var(--studio-green-soft);
    color: var(--studio-green);
  }

  #pjdStudioRight .pjd-studio-action-icon {
    flex-shrink: 0;
    width: 22px;
    height: 22px;
    display: grid;
    place-items: center;
  }

  #pjdStudioRight .pjd-studio-action-icon svg {
    width: 20px;
    height: 20px;
  }

  #pjdStudioRight .pjd-studio-action-text {
    font-size: 11px;
    line-height: 1.15;
    font-weight: 700;
  }

  #pjdStudioRight .pjd-studio-action-chevron {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 10px;
    height: 10px;
    opacity: 0.4;
  }

  #pjdStudioRight .pjd-studio-divider {
    height: 1px;
    background: var(--studio-line);
    margin: 0 4px 20px;
  }

  /* Bottom List */
  #pjdStudioRight .pjd-studio-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  #pjdStudioRight .pjd-studio-item {
    all: unset;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 6px;
    border-radius: 10px;
    cursor: pointer;
    transition: background .15s;
    box-sizing: border-box;
  }

  #pjdStudioRight .pjd-studio-item:hover {
    background: #f9fafb;
  }

  /* Cajas de Íconos compartidas */
  #pjdStudioRight .pjd-studio-icon-box,
  #pjdStudioRight .pjd-studio-rail-btn {
    all: unset;
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    position: relative;
    flex-shrink: 0;
    cursor: pointer;
    box-sizing: border-box;
  }

  #pjdStudioRight .pjd-studio-rail-btn {
    transition: transform .1s;
  }

  #pjdStudioRight .pjd-studio-rail-btn:active {
    transform: scale(.92);
  }

  #pjdStudioRight .pjd-studio-icon-box.is-purple,
  #pjdStudioRight .pjd-studio-rail-btn.is-purple {
    background: var(--studio-purple-soft);
    color: var(--studio-purple);
  }

  #pjdStudioRight .pjd-studio-icon-box.is-orange,
  #pjdStudioRight .pjd-studio-rail-btn.is-orange {
    background: var(--studio-orange-soft);
    color: var(--studio-orange);
  }

  #pjdStudioRight .pjd-studio-icon-box.is-green,
  #pjdStudioRight .pjd-studio-rail-btn.is-green {
    background: var(--studio-green-soft);
    color: var(--studio-green);
  }

  #pjdStudioRight .pjd-studio-icon-box > svg.main-icon,
  #pjdStudioRight .pjd-studio-rail-btn > svg.main-icon {
    width: 22px;
    height: 22px;
    display: block;
  }

  /* Badges */
  #pjdStudioRight .pjd-badge-plus {
    position: absolute;
    right: -4px;
    bottom: -4px;
    width: 16px;
    height: 16px;
  }

  #pjdStudioRight .pjd-badge-icon {
    position: absolute;
    right: -4px;
    bottom: -4px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid #ffffff;
    display: grid;
    place-items: center;
    color: white;
  }

  #pjdStudioRight .pjd-badge-icon.is-star {
    background: var(--studio-blue-badge);
  }

  #pjdStudioRight .pjd-badge-icon.is-dollar {
    background: var(--studio-green-badge);
  }

  #pjdStudioRight .pjd-badge-icon svg {
    width: 8px;
    height: 8px;
  }

  /* Textos de la lista */
  #pjdStudioRight .pjd-studio-item-content {
    display: flex;
    flex-direction: column;
    gap: 2px;
    overflow: hidden;
  }

  #pjdStudioRight .pjd-studio-item-title {
    font-size: 14px;
    font-weight: 600;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  #pjdStudioRight .pjd-studio-item-sub {
    font-size: 11px;
    font-weight: 500;
    color: #6b7280;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* PANEL COLAPSADO */
  #pjdStudioRight .pjd-studio-rail {
    position: absolute;
    inset: 0;
    width: 64px;
    display: none;
    padding: 20px 0;
    background: #ffffff;
    flex-direction: column;
    align-items: center;
  }

  body.pjd-studio-collapsed #pjdStudioRight .pjd-studio-rail {
    display: flex;
  }

  #pjdStudioRight .pjd-studio-rail-toggle {
    margin-bottom: 24px;
  }

  #pjdStudioRight .pjd-studio-rail-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
    align-items: center;
  }

  #pjdStudioRight .pjd-studio-rail-sep {
    width: 24px;
    height: 1px;
    background: var(--studio-line);
    margin: 4px 0;
  }

  @media (max-width: 1280px) {
    body.pjd-studio-open .pjd-body,
    body.pjd-studio-collapsed .pjd-body {
      padding-right: 64px;
    }

    #pjdStudioRight {
      top: 84px;
      width: 64px;
    }

    #pjdStudioRight .pjd-studio-panel {
      opacity: 0;
      pointer-events: none;
    }

    #pjdStudioRight .pjd-studio-rail {
      display: flex;
    }
  }
</style>
@endpush
@endonce

<aside id="pjdStudioRight" aria-label="Studio">
  <div class="pjd-studio-inner">

    <div class="pjd-studio-panel">
      <div class="pjd-studio-head">
        <h2 class="pjd-studio-title">Studio</h2>

        <button type="button" class="pjd-studio-toggle" data-studio-collapse title="Cerrar Studio" aria-label="Cerrar Studio">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="18" height="18" x="3" y="3" rx="2"></rect>
            <path d="M10 8l4 4-4 4"></path>
          </svg>
        </button>
      </div>

      <div class="pjd-studio-actions">
        <button type="button" class="pjd-studio-action-card is-purple">
          <span class="pjd-studio-action-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <path d="M8 13h4"></path>
              <path d="M8 17h8"></path>
              <path d="M8 9h2"></path>
            </svg>
          </span>
          <span class="pjd-studio-action-text">Hacer<br>Reporte</span>
          <svg class="pjd-studio-action-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m9 18 4-4-4-4"></path>
          </svg>
        </button>

        <button type="button" class="pjd-studio-action-card is-orange">
          <span class="pjd-studio-action-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8Z"></path>
              <path d="M8 9h8"></path>
              <path d="M8 13h5"></path>
            </svg>
          </span>
          <span class="pjd-studio-action-text">Estrategia<br>JA</span>
          <svg class="pjd-studio-action-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m9 18 4-4-4-4"></path>
          </svg>
        </button>

        <button type="button" class="pjd-studio-action-card is-green">
          <span class="pjd-studio-action-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7z"></path>
              <path d="M14 2v5h5"></path>
              <path d="m9 14 2 2 4-5"></path>
              <path d="M9 18h6"></path>
            </svg>
          </span>
          <span class="pjd-studio-action-text">Redactar<br>Documentos</span>
          <svg class="pjd-studio-action-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m9 18 4-4-4-4"></path>
          </svg>
        </button>
      </div>

      <div class="pjd-studio-divider"></div>

      <div class="pjd-studio-list">
        <button type="button" class="pjd-studio-item">
          <div class="pjd-studio-icon-box is-orange">
            <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8Z"></path>
              <path d="M8 9h8"></path>
              <path d="M8 13h5"></path>
            </svg>
          </div>
          <div class="pjd-studio-item-content">
            <span class="pjd-studio-item-title">Junta de Aclaraciones</span>
            <span class="pjd-studio-item-sub">Documento JA generado • 8/7/2026</span>
          </div>
        </button>

        <button type="button" class="pjd-studio-item">
          <div class="pjd-studio-icon-box is-orange">
            <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8Z"></path>
              <path d="M8 9h8"></path>
              <path d="M8 13h5"></path>
            </svg>
          </div>
          <div class="pjd-studio-item-content">
            <span class="pjd-studio-item-title">Estrategia de Junta de Aclaraciones</span>
            <span class="pjd-studio-item-sub">Estrategia tactica por pregunta • 8/7/2026</span>
          </div>
        </button>

        <button type="button" class="pjd-studio-item">
          <div class="pjd-studio-icon-box is-purple">
            <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <path d="M12 11l1 1 3-3"></path>
            </svg>
            <div class="pjd-badge-icon is-star">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
              </svg>
            </div>
          </div>
          <div class="pjd-studio-item-content">
            <span class="pjd-studio-item-title">Resumen Ejecutivo</span>
            <span class="pjd-studio-item-sub">Documento ejecutivo con identidad visual • 8/...</span>
          </div>
        </button>

        <button type="button" class="pjd-studio-item">
          <div class="pjd-studio-icon-box is-purple">
            <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <circle cx="12" cy="12" r="2.5"></circle>
              <path d="m14 14 2.5 2.5"></path>
              <path d="m16.5 11 2.5-2.5"></path>
              <path d="M11 16.5 8.5 19"></path>
              <path d="M8.5 14 11 16.5"></path>
            </svg>
            <div class="pjd-badge-icon is-dollar">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="2" x2="12" y2="22"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
              </svg>
            </div>
          </div>
          <div class="pjd-studio-item-content">
            <span class="pjd-studio-item-title">Reporte de Finanzas</span>
            <span class="pjd-studio-item-sub">Reporte ejecutivo con identidad visual • 8/7/20...</span>
          </div>
        </button>

        <button type="button" class="pjd-studio-item">
          <div class="pjd-studio-icon-box is-purple">
            <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
            </svg>
          </div>
          <div class="pjd-studio-item-content">
            <span class="pjd-studio-item-title">Reporte de Resumen</span>
            <span class="pjd-studio-item-sub">Exportacion de resumen • 8/7/2026</span>
          </div>
        </button>
      </div>
    </div>

    <div class="pjd-studio-rail" aria-hidden="true">
      <button type="button" class="pjd-studio-rail-toggle" data-studio-open title="Abrir Studio" aria-label="Abrir Studio">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect width="18" height="18" x="3" y="3" rx="2"></rect>
          <path d="M14 8l-4 4 4 4"></path>
        </svg>
      </button>

      <div class="pjd-studio-rail-list">
        <button type="button" class="pjd-studio-rail-btn is-purple" data-studio-open>
          <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <path d="M8 13h4"></path>
            <path d="M8 17h8"></path>
            <path d="M8 9h2"></path>
          </svg>
          <svg class="pjd-badge-plus" viewBox="0 0 16 16">
            <path d="M8 3v10M3 8h10" stroke="white" stroke-width="4" stroke-linecap="round"></path>
            <path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
          </svg>
        </button>

        <button type="button" class="pjd-studio-rail-btn is-orange" data-studio-open>
          <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8Z"></path>
            <path d="M8 9h8"></path>
            <path d="M8 13h5"></path>
          </svg>
          <svg class="pjd-badge-plus" viewBox="0 0 16 16">
            <path d="M8 3v10M3 8h10" stroke="white" stroke-width="4" stroke-linecap="round"></path>
            <path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
          </svg>
        </button>

        <button type="button" class="pjd-studio-rail-btn is-green" data-studio-open>
          <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7z"></path>
            <path d="M14 2v5h5"></path>
            <path d="m9 14 2 2 4-5"></path>
            <path d="M9 18h6"></path>
          </svg>
          <svg class="pjd-badge-plus" viewBox="0 0 16 16">
            <path d="M8 3v10M3 8h10" stroke="white" stroke-width="4" stroke-linecap="round"></path>
            <path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
          </svg>
        </button>

        <span class="pjd-studio-rail-sep"></span>

        <button type="button" class="pjd-studio-rail-btn is-orange" data-studio-open>
          <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8Z"></path>
            <path d="M8 9h8"></path>
            <path d="M8 13h5"></path>
          </svg>
        </button>

        <button type="button" class="pjd-studio-rail-btn is-orange" data-studio-open>
          <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8Z"></path>
            <path d="M8 9h8"></path>
            <path d="M8 13h5"></path>
          </svg>
        </button>

        <button type="button" class="pjd-studio-rail-btn is-purple" data-studio-open>
          <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <path d="M12 11l1 1 3-3"></path>
          </svg>
          <div class="pjd-badge-icon is-star">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
            </svg>
          </div>
        </button>

        <button type="button" class="pjd-studio-rail-btn is-purple" data-studio-open>
          <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <circle cx="12" cy="12" r="2.5"></circle>
            <path d="m14 14 2.5 2.5"></path>
            <path d="m16.5 11 2.5-2.5"></path>
          </svg>
          <div class="pjd-badge-icon is-dollar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="2" x2="12" y2="22"></line>
              <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
          </div>
        </button>

        <button type="button" class="pjd-studio-rail-btn is-purple" data-studio-open>
          <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
          </svg>
        </button>
      </div>
    </div>
  </div>
</aside>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const body = document.body;
  const studio = document.getElementById('pjdStudioRight');
  if (!studio) return;

  const setStudio = function (open) {
    body.classList.toggle('pjd-studio-open', open);
    body.classList.toggle('pjd-studio-collapsed', !open);

    const rail = studio.querySelector('.pjd-studio-rail');
    if (rail) {
      rail.setAttribute('aria-hidden', open ? 'true' : 'false');
    }

    try {
      localStorage.setItem('pjdStudioOpen', open ? '1' : '0');
    } catch (e) {}
  };

  let initialOpen = true;

  try {
    const stored = localStorage.getItem('pjdStudioOpen');
    if (stored === '0') initialOpen = false;
  } catch (e) {}

  setStudio(initialOpen);

  studio.querySelectorAll('[data-studio-collapse]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      setStudio(false);
    });
  });

  studio.querySelectorAll('[data-studio-open]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      setStudio(true);
    });
  });
});
</script>
@endpush
@endonce