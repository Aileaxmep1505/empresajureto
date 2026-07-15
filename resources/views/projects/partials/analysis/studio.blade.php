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

  /* Mientras un reporte está abierto, Studio puede alternar entre panel y barra. */
  body.pjd-report-editor-open #pjdStudioRight {
    display: block !important;
  }

  body.pjd-report-editor-open.pjd-studio-collapsed #pjdStudioRight {
    width: 64px !important;
  }

  body.pjd-report-editor-open.pjd-studio-collapsed .pjd-body {
    padding-right: 64px !important;
  }

  body.pjd-report-editor-open.pjd-studio-collapsed #pjdStudioRight .pjd-studio-panel {
    opacity: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;
  }

  body.pjd-report-editor-open.pjd-studio-collapsed #pjdStudioRight .pjd-studio-rail {
    display: flex !important;
    opacity: 1 !important;
    visibility: visible !important;
    pointer-events: auto !important;
  }

  body.pjd-report-editor-open.pjd-studio-open #pjdStudioRight {
    width: 360px !important;
  }

  body.pjd-report-editor-open.pjd-studio-open .pjd-body {
    padding-right: 360px !important;
  }

  body.pjd-report-editor-open.pjd-studio-open #pjdStudioRight .pjd-studio-panel {
    opacity: 1 !important;
    visibility: visible !important;
    pointer-events: auto !important;
  }

  body.pjd-report-editor-open.pjd-studio-open #pjdStudioRight .pjd-studio-rail {
    display: none !important;
  }

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

  /* ==========================================================
     ESTILOS COMPARTIDOS MODALES
     ========================================================== */
  .pjd-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 10080;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(17, 24, 39, .45);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    font-family: 'Quicksand', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }
  .pjd-modal-backdrop.is-open { display: flex; }
  body.pjd-modal-open { overflow: hidden !important; }

  /* ==========================================================
     MODAL CREAR REPORTE
     ========================================================== */
  #pjdReportModal .pjd-report-modal-card {
    width: min(780px, 100%);
    max-height: calc(100vh - 48px);
    overflow: auto;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 28px 70px rgba(15, 23, 42, .22);
  }

  #pjdReportModal .pjd-report-modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 24px;
    border-bottom: 1px solid #f3f4f6;
  }

  #pjdReportModal .pjd-report-modal-title-wrap { 
    display: flex; 
    align-items: center; 
    gap: 12px; 
  }
  
  #pjdReportModal .pjd-report-modal-icon { 
    width: 36px; 
    height: 36px; 
    border-radius: 8px; 
    display: grid; 
    place-items: center; 
    background: #f4edff; 
    color: #7c3aed; 
  }
  
  #pjdReportModal .pjd-report-modal-icon svg { width: 18px; height: 18px; }
  #pjdReportModal .pjd-report-modal-title { margin: 0; color: #111827; font-size: 16px; font-weight: 700; line-height: 1.2; }
  #pjdReportModal .pjd-report-modal-subtitle { margin: 2px 0 0; color: #6b7280; font-size: 12px; font-weight: 500; }
  #pjdReportModal .pjd-report-modal-close { all: unset; width: 28px; height: 28px; display: grid; place-items: center; border-radius: 6px; color: #6b7280; cursor: pointer; }
  #pjdReportModal .pjd-report-modal-close:hover { background: #f3f4f6; color: #111827; }

  #pjdReportModal .pjd-report-modal-body { padding: 24px; }
  
  #pjdReportModal .pjd-report-modal-topline { 
    display: flex; 
    align-items: flex-end; 
    justify-content: space-between; 
    gap: 16px; 
    margin-bottom: 20px; 
  }
  
  #pjdReportModal .pjd-report-modal-kicker { 
    color: #6b7280; 
    font-size: 11px; 
    font-weight: 700; 
    letter-spacing: .05em; 
    text-transform: uppercase; 
  }
  
  #pjdReportModal .pjd-report-modal-copy { 
    margin: 4px 0 0; 
    color: #6b7280; 
    font-size: 13px; 
  }

  #pjdReportModal .pjd-report-run-all { 
    all: unset; 
    height: 36px; 
    padding: 0 16px; 
    border-radius: 8px; 
    display: inline-flex; 
    align-items: center; 
    justify-content: center; 
    gap: 6px; 
    background: #2563eb; 
    color: #ffffff; 
    font-size: 13px; 
    font-weight: 600; 
    cursor: pointer; 
    white-space: nowrap; 
    transition: background .15s;
  }
  
  #pjdReportModal .pjd-report-run-all:hover { background: #1d4ed8; }
  #pjdReportModal .pjd-report-run-all:disabled { opacity: .6; cursor: wait; }
  #pjdReportModal .pjd-report-run-all svg { width: 14px; height: 14px; fill: currentColor; }

  #pjdReportModal .pjd-report-grid { 
    display: grid; 
    grid-template-columns: repeat(2, minmax(0, 1fr)); 
    gap: 16px; 
    margin-bottom: 16px; 
  }
  
  #pjdReportModal .pjd-report-card { 
    all: unset;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    padding: 20px; 
    border: 1px solid #e5e7eb; 
    border-radius: 10px; 
    background: #ffffff; 
    cursor: pointer; 
    transition: border-color .15s, background .15s; 
  }
  
  #pjdReportModal .pjd-report-card.is-generated { 
    border-color: #a7f3d0; 
    background: #f0fdf4; 
  }

  #pjdReportModal .pjd-report-card-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    width: 100%;
  }
  
  #pjdReportModal .pjd-report-card-icon { 
    width: 32px; 
    height: 32px; 
    display: grid; 
    place-items: center; 
    border-radius: 6px; 
    background: #f4edff; 
    color: #7c3aed; 
  }
  
  #pjdReportModal .pjd-report-card-icon svg { width: 16px; height: 16px; }

  #pjdReportModal .pjd-report-card-badge { 
    display: none; 
    align-items: center; 
    gap: 4px; 
    padding: 2px 8px; 
    border: 1px solid #10b981; 
    border-radius: 999px; 
    background: transparent; 
    color: #047857; 
    font-size: 10px; 
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
  }
  
  #pjdReportModal .pjd-report-card.is-generated .pjd-report-card-badge { display: inline-flex; }
  #pjdReportModal .pjd-report-card-badge svg { width: 12px; height: 12px; }

  #pjdReportModal .pjd-report-card-title { 
    margin: 16px 0 0; 
    color: #111827; 
    font-size: 15px; 
    font-weight: 700; 
  }
  
  #pjdReportModal .pjd-report-card-text { 
    margin: 4px 0 0; 
    color: #6b7280; 
    font-size: 13px; 
    line-height: 1.4; 
  }

  #pjdReportModal .pjd-report-summary-row { 
    display: flex; 
    justify-content: space-between;
    align-items: center; 
    padding: 16px 20px; 
    border: 1px solid #e5e7eb; 
    border-radius: 10px; 
    background: #ffffff; 
  }
  
  #pjdReportModal .pjd-report-summary-row.is-generated {
    border-color: #a7f3d0; 
    background: #f0fdf4; 
  }

  #pjdReportModal .pjd-report-summary-main { 
    display: flex; 
    align-items: flex-start; 
    gap: 12px; 
  }
  
  #pjdReportModal .pjd-report-summary-main > svg { 
    width: 20px; 
    height: 20px; 
    color: #7c3aed; 
    margin-top: 2px;
  }

  #pjdReportModal .pjd-report-summary-title-line { 
    display: flex; 
    align-items: center; 
    gap: 10px; 
  }
  
  #pjdReportModal .pjd-report-summary-title { 
    color: #111827; 
    font-size: 14px; 
    font-weight: 700; 
  }
  
  #pjdReportModal .pjd-report-summary-text { 
    margin: 2px 0 0; 
    color: #6b7280; 
    font-size: 13px; 
  }

  #pjdReportModal .pjd-report-summary-action { 
    all: unset; 
    height: 32px; 
    padding: 0 14px; 
    display: inline-flex; 
    align-items: center; 
    gap: 6px; 
    border: 1px solid #2563eb; 
    border-radius: 6px; 
    background: #ffffff; 
    color: #2563eb; 
    font-size: 13px; 
    font-weight: 600; 
    cursor: pointer; 
    transition: background .15s;
  }
  
  #pjdReportModal .pjd-report-summary-action:hover { background: #eff6ff; }
  #pjdReportModal .pjd-report-summary-action:disabled { opacity: .6; cursor: wait; }
  #pjdReportModal .pjd-report-summary-action svg { width: 14px; height: 14px; fill: none; stroke: currentColor; }

  #pjdReportModal .pjd-report-loading { opacity: .65; pointer-events: none; }
  #pjdReportModal .pjd-report-summary-row.is-regenerating {
    border-color: #93c5fd;
    background: #eff6ff;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .08);
  }

  #pjdReportModal .pjd-report-summary-row.is-regenerated {
    animation: pjdReportRegenerated 1.1s ease;
  }

  #pjdReportModal .pjd-report-summary-status {
    margin-top: 7px;
    color: #047857;
    font-size: 12px;
    font-weight: 700;
  }

  #pjdReportModal .pjd-report-summary-status[hidden] {
    display: none !important;
  }

  #pjdReportModal .pjd-report-summary-action.is-loading svg {
    animation: pjdReportSpin .8s linear infinite;
  }

  @keyframes pjdReportSpin {
    to { transform: rotate(360deg); }
  }

  @keyframes pjdReportRegenerated {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, .30); }
    55% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
    100% { box-shadow: none; }
  }

  #pjdReportModal .pjd-report-modal-toast { display: none; margin-top: 14px; padding: 10px 12px; border-radius: 8px; border: 1px solid #d1fae5; background: #ecfdf5; color: #047857; font-size: 12px; font-weight: 600; }
  #pjdReportModal .pjd-report-modal-toast.is-visible { display: block; }
  #pjdReportModal .pjd-report-modal-toast.is-error { border-color: #fecaca; background: #fef2f2; color: #b91c1c; }

  @media (max-width: 760px) {
    #pjdReportModal .pjd-report-modal-head, #pjdReportModal .pjd-report-modal-body { padding-left: 16px; padding-right: 16px; }
    #pjdReportModal .pjd-report-modal-topline, #pjdReportModal .pjd-report-summary-row { flex-direction: column; align-items: flex-start; gap: 12px; }
    #pjdReportModal .pjd-report-grid { grid-template-columns: 1fr; }
    #pjdReportModal .pjd-report-run-all, #pjdReportModal .pjd-report-summary-action { width: 100%; justify-content: center; }
  }


  /* ==========================================================
     MODAL ESTRATEGIA JA
     ========================================================== */
  #pjdEstrategiaModal .pjd-estrategia-modal-card { width: min(720px, 100%); max-height: calc(100vh - 48px); overflow: auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 28px 70px rgba(15, 23, 42, .22); display: flex; flex-direction: column; }
  #pjdEstrategiaModal .pjd-estrategia-modal-head { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 20px 24px; border-bottom: 1px solid #e5e7eb; }
  #pjdEstrategiaModal .pjd-estrategia-modal-title-wrap { display: flex; align-items: center; gap: 14px; min-width: 0; }
  #pjdEstrategiaModal .pjd-estrategia-modal-icon { width: 38px; height: 38px; border-radius: 10px; display: grid; place-items: center; flex: 0 0 auto; background: #fff5e6; color: #c45a00; }
  #pjdEstrategiaModal .pjd-estrategia-modal-icon svg { width: 20px; height: 20px; }
  #pjdEstrategiaModal .pjd-estrategia-modal-title { margin: 0; color: #111827; font-size: 16px; font-weight: 700; line-height: 1.2; }
  #pjdEstrategiaModal .pjd-estrategia-modal-subtitle { margin: 2px 0 0; color: #6b7280; font-size: 12px; font-weight: 500; }
  #pjdEstrategiaModal .pjd-estrategia-modal-close { all: unset; width: 32px; height: 32px; display: grid; place-items: center; border-radius: 8px; color: #6b7280; cursor: pointer; box-sizing: border-box; }
  #pjdEstrategiaModal .pjd-estrategia-modal-close:hover { background: #f3f4f6; color: #111827; }
  #pjdEstrategiaModal .pjd-estrategia-modal-body { padding: 20px 24px; display: flex; flex-direction: column; gap: 16px; }
  #pjdEstrategiaModal .pjd-estrategia-section { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; }
  #pjdEstrategiaModal .pjd-estrategia-section-header { display: flex; align-items: center; gap: 8px; color: #111827; font-size: 14px; font-weight: 700; margin-bottom: 4px; }
  #pjdEstrategiaModal .pjd-estrategia-section-header svg { width: 16px; height: 16px; color: #6b7280; }
  #pjdEstrategiaModal .pjd-estrategia-desc { margin: 0 0 14px 24px; color: #6b7280; font-size: 13px; }
  #pjdEstrategiaModal .pjd-estrategia-options { display: flex; gap: 20px; margin-left: 24px; flex-wrap: wrap; }
  #pjdEstrategiaModal .pjd-radio-label, #pjdEstrategiaModal .pjd-check-label { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #111827; font-weight: 500; cursor: pointer; }
  #pjdEstrategiaModal input[type="radio"], #pjdEstrategiaModal input[type="checkbox"] { width: 16px; height: 16px; accent-color: #2563eb; cursor: pointer; margin: 0; }
  #pjdEstrategiaModal .pjd-estrategia-textarea { width: calc(100% - 24px); margin-left: 24px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-family: inherit; font-size: 13px; color: #111827; resize: vertical; min-height: 80px; outline: none; transition: border-color .15s, box-shadow .15s; }
  #pjdEstrategiaModal .pjd-estrategia-textarea:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
  #pjdEstrategiaModal .pjd-ja-template-upload {
    display: none;
    margin: 14px 0 0 24px;
  }

  #pjdEstrategiaModal .pjd-ja-template-upload.is-visible {
    display: block;
  }

  #pjdEstrategiaModal .pjd-ja-template-drop {
    width: calc(100% - 24px);
    min-height: 128px;
    padding: 18px;
    border: 1px dashed #d7dbe2;
    border-radius: 10px;
    background: #fbfcfe;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    cursor: pointer;
  }

  #pjdEstrategiaModal .pjd-ja-template-drop:hover {
    border-color: #2563eb;
    background: #f8fbff;
  }

  #pjdEstrategiaModal .pjd-ja-template-drop input {
    display: none;
  }

  #pjdEstrategiaModal .pjd-ja-template-file {
    display: grid;
    justify-items: center;
    gap: 6px;
    color: #6b7280;
    font-size: 12px;
  }

  #pjdEstrategiaModal .pjd-ja-template-file strong {
    max-width: 100%;
    color: #111827;
    font-size: 13px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  #pjdEstrategiaModal .pjd-ja-template-progress {
    display: none;
    margin: 12px 24px 0;
    padding: 10px 12px;
    border: 1px solid #cfe0ff;
    border-radius: 8px;
    background: #f5f8ff;
    color: #1d4ed8;
    font-size: 12px;
    font-weight: 600;
  }

  #pjdEstrategiaModal .pjd-ja-template-progress.is-visible {
    display: block;
  }
  #pjdEstrategiaModal .pjd-estrategia-modal-footer { display: flex; align-items: center; justify-content: flex-end; gap: 12px; padding: 16px 24px; border-top: 1px solid #e5e7eb; background: #ffffff; border-radius: 0 0 12px 12px; }
  #pjdEstrategiaModal .pjd-btn-cancel, #pjdEstrategiaModal .pjd-btn-primary { all: unset; padding: 8px 16px; font-size: 14px; font-weight: 600; border-radius: 8px; cursor: pointer; transition: background .15s; }
  #pjdEstrategiaModal .pjd-btn-cancel { color: #111827; }
  #pjdEstrategiaModal .pjd-btn-cancel:hover { background: #f3f4f6; }
  #pjdEstrategiaModal .pjd-btn-primary { background: #2563eb; color: #ffffff; }
  #pjdEstrategiaModal .pjd-btn-primary:hover { background: #1d4ed8; }

  /* ==========================================================
     MODAL ARMADO DE PROPUESTA
     ========================================================== */
  #pjdArmadoModal .pjd-armado-modal-card { width: min(840px, 100%); max-height: calc(100vh - 48px); display: flex; flex-direction: column; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 28px 70px rgba(15, 23, 42, .22); }
  #pjdArmadoModal .pjd-armado-modal-head { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 20px 24px; border-bottom: 1px solid #e5e7eb; }
  #pjdArmadoModal .pjd-armado-modal-title-wrap { display: flex; align-items: center; gap: 14px; min-width: 0; }
  #pjdArmadoModal .pjd-armado-modal-icon { width: 38px; height: 38px; border-radius: 10px; display: grid; place-items: center; flex: 0 0 auto; background: #e7f7ef; color: #10b981; }
  #pjdArmadoModal .pjd-armado-modal-icon svg { width: 20px; height: 20px; }
  #pjdArmadoModal .pjd-armado-modal-title { margin: 0; color: #111827; font-size: 16px; font-weight: 700; line-height: 1.2; }
  #pjdArmadoModal .pjd-armado-modal-subtitle { margin: 2px 0 0; color: #6b7280; font-size: 12px; font-weight: 500; }
  #pjdArmadoModal .pjd-armado-modal-close { all: unset; width: 32px; height: 32px; display: grid; place-items: center; border-radius: 8px; color: #6b7280; cursor: pointer; box-sizing: border-box; }
  #pjdArmadoModal .pjd-armado-modal-close:hover { background: #f3f4f6; color: #111827; }
  #pjdArmadoModal .pjd-armado-modal-body { padding: 24px; display: flex; flex-direction: column; gap: 20px; overflow-y: auto; }
  .pjd-armado-rep-box { display: flex; align-items: center; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 16px; gap: 12px; }
  .pjd-armado-rep-label { font-weight: 700; font-size: 14px; color: #111827; white-space: nowrap; }
  .pjd-armado-rep-input { all: unset; flex: 1; font-size: 14px; color: #4b5563; width: 100%; text-transform: uppercase; }
  .pjd-armado-step { border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
  .pjd-armado-step-inner { padding: 18px 20px; }
  .pjd-armado-step-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
  .pjd-armado-step-title { display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 700; color: #111827; }
  .pjd-armado-step-title svg { width: 18px; height: 18px; color: #10b981; }
  .pjd-armado-badge-check { display: inline-flex; align-items: center; padding: 4px 10px; border: 1px solid #86efac; background: #f0fdf4; color: #047857; border-radius: 999px; font-size: 12px; font-weight: 700; }
  .pjd-armado-step-desc { margin: 0 0 16px 0; font-size: 13px; color: #6b7280; }
  .pjd-armado-step-actions { display: flex; justify-content: flex-end; gap: 10px; }
  .pjd-btn-outline { all: unset; display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; font-weight: 600; color: #374151; cursor: pointer; background: #ffffff; transition: background .15s; }
  .pjd-btn-outline:hover { background: #f9fafb; }
  .pjd-btn-outline svg { width: 16px; height: 16px; color: #4b5563; }
  .pjd-armado-header-p2 { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 12px; }
  .pjd-armado-p2-info h4 { margin: 0 0 4px 0; font-size: 15px; font-weight: 700; color: #111827; }
  .pjd-armado-p2-info p { margin: 0; font-size: 13px; color: #6b7280; line-height: 1.4; }
  .pjd-armado-p2-controls { display: flex; align-items: center; gap: 12px; }
  .pjd-armado-todo-label { display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: #374151; cursor: pointer; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; }
  .pjd-armado-todo-label input { width: 14px; height: 14px; accent-color: #2563eb; cursor: pointer; margin: 0; }
  .pjd-btn-blue { all: unset; display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #7ca1f3; color: #ffffff; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; transition: background .15s; }
  .pjd-btn-blue:hover { background: #608df0; }
  .pjd-btn-blue svg { width: 14px; height: 14px; }
  .pjd-armado-doc-list { border-top: 1px solid #e5e7eb; max-height: 280px; overflow-y: auto; }
  .pjd-doc-item { display: flex; align-items: flex-start; padding: 14px 20px; border-bottom: 1px solid #f3f4f6; gap: 14px; cursor: pointer; transition: background .15s; }
  .pjd-doc-item:hover { background: #f9fafb; }
  .pjd-doc-item:last-child { border-bottom: none; }
  .pjd-doc-item input[type="checkbox"] { margin-top: 4px; width: 16px; height: 16px; accent-color: #2563eb; cursor: pointer; }
  .pjd-doc-content { flex: 1; min-width: 0; }
  .pjd-doc-title-row { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
  .pjd-doc-title { font-size: 14px; font-weight: 600; color: #111827; }
  .pjd-doc-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border: 1px solid #e5e7eb; border-radius: 999px; font-size: 11px; font-weight: 600; color: #6b7280; background: #ffffff; }
  .pjd-doc-badge.is-generando { color: #2563eb; border-color: #bfdbfe; background: #eff6ff; }
  .pjd-doc-badge.is-generando svg { width: 12px; height: 12px; animation: spin 1s linear infinite; }
  @keyframes spin { 100% { transform: rotate(360deg); } }
  .pjd-doc-sub { font-size: 12px; color: #9ca3af; }

  /* ==========================================================
     SUB-MODAL "ENCABEZADO GUARDADO"
     ========================================================== */
  #pjdEncabezadoModal { z-index: 10090; }
  #pjdEncabezadoModal .pjd-encabezado-card { width: min(600px, 100%); background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 28px 70px rgba(15, 23, 42, .3); }
  #pjdEncabezadoModal .pjd-encabezado-head { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; border-bottom: 1px solid #e5e7eb; }
  #pjdEncabezadoModal .pjd-encabezado-title { margin: 0; font-size: 16px; font-weight: 700; color: #111827; }
  #pjdEncabezadoModal .pjd-encabezado-close { all: unset; font-size: 18px; color: #6b7280; cursor: pointer; display: grid; place-items: center; width: 24px; height: 24px; }
  #pjdEncabezadoModal .pjd-encabezado-close:hover { color: #111827; }
  #pjdEncabezadoModal .pjd-encabezado-body { padding: 24px; }
  .pjd-encabezado-table-wrap { border: 1px solid #d1d5db; border-radius: 8px; display: flex; overflow: hidden; }
  .pjd-encabezado-col-left { flex: 1; padding: 16px; border-right: 1px solid #d1d5db; font-size: 13px; font-weight: 700; color: #111827; line-height: 1.4; }
  .pjd-encabezado-col-right { flex: 1; display: flex; flex-direction: column; }
  .pjd-encabezado-row { display: flex; border-bottom: 1px solid #e5e7eb; font-size: 13px; }
  .pjd-encabezado-row:last-child { border-bottom: none; }
  .pjd-encabezado-label { width: 80px; padding: 8px 12px; color: #6b7280; border-right: 1px solid #e5e7eb; }
  .pjd-encabezado-value { flex: 1; padding: 8px 12px; color: #111827; }



  /* ==========================================================
     EDITOR DE REPORTES TIPO WORD COMO COMPONENTE INTERNO
     ========================================================== */
  .pjd-right { position: relative; }

  #pjdReportEditorModal,
  #pjdReportEditorModal * { box-sizing: border-box; }

  #pjdReportEditorModal {
    display: none;
    width: 100%;
    height: calc(100vh - 72px);
    min-height: 0;
    padding: 16px 20px 18px;
    overflow: hidden;
    background: #ffffff;
    color: #1f2937;
    font-family: 'Quicksand', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  #pjdReportEditorModal.is-open,
  #pjdReportEditorModal.is-active { display: block; }

  #pjdReportEditorModal .pjd-word-modal-card {
    position: relative;
    width: min(100%, 1360px);
    height: 100%;
    min-height: 0;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    box-shadow: 0 10px 26px rgba(15, 23, 42, .05);
  }

  .pjd-right.pjd-report-editor-active > .pjd-pane:not(#pjdReportEditorModal) {
    display: none !important;
  }

  body.pjd-report-editor-open { overflow: hidden !important; }

  #pjdReportEditorModal .pjd-word-topbar {
    flex: 0 0 auto;
    min-height: 56px;
    padding: 0 14px;
    display: grid;
    grid-template-columns: minmax(110px, .8fr) minmax(180px, 1.2fr) auto;
    align-items: center;
    gap: 10px;
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
    overflow: hidden;
  }

  #pjdReportEditorModal .pjd-word-page-info,
  #pjdReportEditorModal .pjd-word-title-wrap,
  #pjdReportEditorModal .pjd-word-actions {
    display: flex;
    align-items: center;
    gap: 7px;
    min-width: 0;
  }

  #pjdReportEditorModal .pjd-word-page-info { color: #111827; font-size: 13px; font-weight: 600; white-space: nowrap; }
  #pjdReportEditorModal .pjd-word-page-info svg { width: 17px; height: 17px; color: #374151; flex: 0 0 auto; }
  #pjdReportEditorModal .pjd-word-title-wrap { min-width: 0; justify-content: center; }
  #pjdReportEditorModal .pjd-word-title { width: 100%; max-width: 100%; overflow: hidden; color: #111827; font-size: 16px; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; text-align: center; }
  #pjdReportEditorModal .pjd-word-actions { justify-content: flex-end; flex-wrap: nowrap; overflow: hidden; }

  #pjdReportEditorModal .pjd-word-icon-btn {
    all: unset;
    width: 32px;
    height: 32px;
    flex: 0 0 32px;
    display: grid;
    place-items: center;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #ffffff;
    color: #4b5563;
    cursor: pointer;
    box-sizing: border-box;
    transition: background .15s, border-color .15s, color .15s, transform .12s;
  }

  #pjdReportEditorModal .pjd-word-icon-btn:hover { background: #f9fafb; border-color: #d1d5db; color: #111827; }
  #pjdReportEditorModal .pjd-word-icon-btn:active { transform: scale(.97); }
  #pjdReportEditorModal .pjd-word-icon-btn.is-primary { border-color: #2563eb; background: #2563eb; color: #ffffff; }
  #pjdReportEditorModal .pjd-word-icon-btn.is-primary:hover { background: #1d4ed8; border-color: #1d4ed8; }
  #pjdReportEditorModal .pjd-word-icon-btn.is-saving { opacity: .6; pointer-events: none; }
  #pjdReportEditorModal .pjd-word-icon-btn svg { width: 16px; height: 16px; }

  @media (max-width: 980px) {
    #pjdReportEditorModal .pjd-word-topbar {
      grid-template-columns: auto minmax(120px, 1fr) auto;
      padding: 0 10px;
      gap: 8px;
    }

    #pjdReportEditorModal .pjd-word-page-info span {
      display: none;
    }

    #pjdReportEditorModal .pjd-word-title {
      font-size: 15px;
    }

    #pjdReportEditorModal .pjd-word-icon-btn {
      width: 30px;
      height: 30px;
      flex-basis: 30px;
    }
  }

  #pjdReportEditorModal .pjd-word-toolbar {
    flex: 0 0 auto;
    padding: 10px 22px 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
  }

  #pjdReportEditorModal .pjd-word-toolbar-group { display: inline-flex; align-items: center; gap: 4px; padding-right: 8px; margin-right: 2px; border-right: 1px solid #e5e7eb; }
  #pjdReportEditorModal .pjd-word-toolbar-group:last-child { border-right: 0; }

  #pjdReportEditorModal .pjd-word-tool-btn {
    all: unset;
    min-width: 32px;
    height: 32px;
    padding: 0 7px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 7px;
    color: #4b5563;
    cursor: pointer;
    box-sizing: border-box;
    font-size: 14px;
    font-weight: 600;
  }

  #pjdReportEditorModal .pjd-word-tool-btn:hover { background: #f3f4f6; color: #111827; }
  #pjdReportEditorModal .pjd-word-tool-btn svg { width: 17px; height: 17px; }

  #pjdReportEditorModal .pjd-word-select {
    height: 34px;
    min-width: 118px;
    padding: 0 30px 0 10px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #ffffff;
    color: #374151;
    outline: none;
    font: inherit;
    font-size: 13px;
  }

  #pjdReportEditorModal .pjd-word-select.is-small { min-width: 76px; }
  #pjdReportEditorModal .pjd-word-select:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, .10); }
  #pjdReportEditorModal .pjd-word-color { width: 30px; height: 30px; padding: 3px; border: 1px solid #e5e7eb; border-radius: 7px; background: #ffffff; cursor: pointer; }

  #pjdReportEditorModal .pjd-word-statusbar {
    flex: 0 0 auto;
    min-height: 34px;
    padding: 0 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    color: #6b7280;
    font-size: 11px;
    font-weight: 600;
  }

  #pjdReportEditorModal .pjd-word-workspace {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    overflow-x: auto;
    overscroll-behavior: contain;
    scrollbar-gutter: stable;
    padding: 28px 22px 54px;
    background: #f3f4f6;
  }

  #pjdReportEditorModal .pjd-word-workspace::-webkit-scrollbar {
    width: 10px;
    height: 10px;
  }

  #pjdReportEditorModal .pjd-word-workspace::-webkit-scrollbar-track {
    background: #eef1f5;
  }

  #pjdReportEditorModal .pjd-word-workspace::-webkit-scrollbar-thumb {
    background: #a7adb8;
    border: 2px solid #eef1f5;
    border-radius: 999px;
  }

  #pjdReportEditorModal .pjd-word-workspace::-webkit-scrollbar-thumb:hover {
    background: #7c8492;
  }
  #pjdReportEditorModal .pjd-word-page { width: min(900px, calc(100% - 20px)); min-height: 1050px; margin: 0 auto; padding: 70px 68px 88px; border: 1px solid #e5e7eb; background: #ffffff; box-shadow: 0 8px 28px rgba(15, 23, 42, .10); }
  #pjdReportEditorModal .pjd-word-editor { min-height: 900px; color: #26364d; outline: none; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.45; }
  #pjdReportEditorModal .pjd-word-editor:focus { outline: none; }
  #pjdReportEditorModal .pjd-word-editor h1,
  #pjdReportEditorModal .pjd-word-editor h2,
  #pjdReportEditorModal .pjd-word-editor h3 { color: #1264e8; font-weight: 500; line-height: 1.2; }
  #pjdReportEditorModal .pjd-word-editor h1 { margin: 24px 0 22px; font-size: 30px; }
  #pjdReportEditorModal .pjd-word-editor h2 { margin: 24px 0 14px; font-size: 24px; }
  #pjdReportEditorModal .pjd-word-editor h3 { margin: 20px 0 10px; font-size: 19px; }
  #pjdReportEditorModal .pjd-word-editor p { margin: 0 0 12px; }
  #pjdReportEditorModal .pjd-word-editor ul,
  #pjdReportEditorModal .pjd-word-editor ol { margin: 8px 0 16px 24px; }
  #pjdReportEditorModal .pjd-word-editor li { margin-bottom: 6px; }
  #pjdReportEditorModal .pjd-word-editor table { width: 100%; border-collapse: collapse; }
  #pjdReportEditorModal .pjd-word-editor th,
  #pjdReportEditorModal .pjd-word-editor td { padding: 8px; border: 1px solid #d1d5db; }
  #pjdReportEditorModal .pjd-word-editor img { max-width: 100%; height: auto; }

  #pjdReportEditorModal .pjd-word-toast {
    position: fixed;
    right: 24px;
    bottom: 24px;
    z-index: 10140;
    display: none;
    max-width: 360px;
    padding: 11px 14px;
    border: 1px solid #bbf7d0;
    border-radius: 9px;
    background: #f0fdf4;
    color: #15803d;
    box-shadow: 0 14px 34px rgba(15, 23, 42, .15);
    font-size: 12px;
    font-weight: 700;
  }

  #pjdReportEditorModal .pjd-word-toast.is-visible { display: block; }
  #pjdReportEditorModal .pjd-word-toast.is-error { border-color: #fecaca; background: #fef2f2; color: #b91c1c; }

  @media (max-width: 900px) {
    #pjdReportEditorModal { padding: 12px; }
    #pjdReportEditorModal .pjd-word-modal-card { height: 100%; min-height: 0; margin: 0 auto; border-radius: 12px; }
    #pjdReportEditorModal .pjd-word-topbar { grid-template-columns: 1fr; padding: 12px 14px; gap: 10px; }
    #pjdReportEditorModal .pjd-word-page-info,
    #pjdReportEditorModal .pjd-word-title-wrap,
    #pjdReportEditorModal .pjd-word-actions { justify-content: center; }
    #pjdReportEditorModal .pjd-word-toolbar,
    #pjdReportEditorModal .pjd-word-statusbar { padding-left: 12px; padding-right: 12px; }
    #pjdReportEditorModal .pjd-word-workspace { padding: 10px; }
    #pjdReportEditorModal .pjd-word-page { width: 100%; min-height: 0; padding: 30px 20px 42px; box-shadow: none; }
    #pjdReportEditorModal .pjd-word-editor { min-height: 650px; }
  }

  @media print {
    body * {
      visibility: hidden !important;
    }

    #pjdReportEditorModal,
    #pjdReportEditorModal * {
      visibility: visible !important;
    }

    #pjdReportEditorModal {
      position: static !important;
      display: block !important;
      background: #ffffff !important;
    }

    #pjdReportEditorModal .pjd-word-topbar,
    #pjdReportEditorModal .pjd-word-toolbar,
    #pjdReportEditorModal .pjd-word-statusbar {
      display: none !important;
    }

    #pjdReportEditorModal .pjd-word-workspace {
      padding: 0 !important;
      overflow: visible !important;
      background: #ffffff !important;
    }

    #pjdReportEditorModal .pjd-word-page {
      width: 100% !important;
      min-height: 0 !important;
      padding: 20mm !important;
      border: 0 !important;
      box-shadow: none !important;
    }
  }


  #pjdStudioRight .pjd-studio-rail-plus {
    position: absolute;
    right: -2px;
    bottom: -3px;
    font-size: 27px;
    line-height: 1;
    font-weight: 400;
    color: currentColor;
    text-shadow: 0 0 0 #fff, 0 0 4px #fff;
    pointer-events: none;
  }

  #pjdReportEditorModal .pjd-word-icon-btn.is-edit {
    border-color: #6ee7b7;
    background: #ecfdf5;
    color: #047857;
  }

  #pjdReportEditorModal .pjd-word-icon-btn.is-edit.is-off {
    border-color: #e5e7eb;
    background: #ffffff;
    color: #6b7280;
  }

  #pjdReportEditorModal .pjd-word-compress-icon {
    display: none;
  }

  #pjdReportEditorModal.is-expanded .pjd-word-expand-icon {
    display: none;
  }

  #pjdReportEditorModal.is-expanded .pjd-word-compress-icon {
    display: block;
  }

  .pjd-wrap.is-report-expanded .pjd-body {
    grid-template-columns: 0 0 minmax(360px, 1fr) !important;
  }

  .pjd-wrap.is-report-expanded .pjd-left,
  .pjd-wrap.is-report-expanded .pjd-resizer {
    opacity: 0 !important;
    pointer-events: none !important;
    overflow: hidden !important;
    border: 0 !important;
  }

  .pjd-wrap.is-report-expanded #pjdReportEditorModal {
    width: 100% !important;
    max-width: none !important;
  }

  #pjdReportEditorModal .pjd-word-editor[contenteditable="false"] {
    cursor: default;
    background: #ffffff;
  }

</style>
@endpush
@endonce

@php
  $pjdGeneratedReports = data_get(
      is_array($project->structured_data ?? null) ? $project->structured_data : [],
      'generated_reports',
      []
  );

  $pjdGeneratedReports = is_array($pjdGeneratedReports) ? $pjdGeneratedReports : [];
  $pjdHasFinanceReport = !empty($pjdGeneratedReports['finance']['html'] ?? null);
  $pjdHasLogisticsReport = !empty($pjdGeneratedReports['logistics']['html'] ?? null);
  $pjdHasAnalysisReport = !empty($pjdGeneratedReports['analysis']['html'] ?? null)
      || !empty($project->report_content);

  $pjdHasJuntaDocument = !empty(data_get($project->structured_data, 'generated_reports.clarifications.html'))
      || !empty(data_get($project->structured_data, 'junta_aclaraciones.documento'))
      || !empty(data_get($project->structured_data, 'junta_aclaraciones.html'))
      || !empty(data_get($project->structured_data, 'generated_documents.junta_aclaraciones.html'));

  $pjdHasJuntaStrategy = !empty(data_get($project->structured_data, 'junta_aclaraciones.estrategia'))
      || !empty(data_get($project->structured_data, 'junta_aclaraciones.strategy'))
      || !empty(data_get($project->structured_data, 'generated_documents.estrategia_ja.html'));

  $pjdReportDate = optional($project->updated_at)->format('d/m/Y') ?: now()->format('d/m/Y');

  // Lista Documentos Armado Modal
  $listaDocumentos = [
      ['title' => 'Nuevo requisito', 'badge' => 'Pendiente', 'sub' => 'Pendiente'],
      ['title' => 'Escrito de facultades suficientes', 'badge' => 'Pendiente', 'sub' => 'Anexo 2 / Legal-Administrativo / Pendiente'],
      ['title' => 'Escrito de no impedimento (Art. 71 y 90)', 'badge' => 'Pendiente', 'sub' => 'Anexo 4 o Anexo 5 / Legal-Administrativo / Pendiente'],
      ['title' => 'Declaración de Integridad', 'badge' => 'Pendiente', 'sub' => 'Anexo 26 / Legal-Administrativo / Pendiente'],
      ['title' => 'Estratificación MiPymes', 'badge' => 'Pendiente', 'sub' => 'Anexo 24 / Legal-Administrativo / Pendiente'],
      ['title' => 'Carta de Nacionalidad Mexicana', 'badge' => 'Pendiente', 'sub' => 'Anexo 19 / Legal-Administrativo / Pendiente'],
      ['title' => 'Formato de pago por transferencia bancaria', 'badge' => 'Pendiente', 'sub' => 'Anexo 10 / Legal-Administrativo / Pendiente'],
      ['title' => 'Consentimiento de notificaciones electrónicas', 'badge' => 'Pendiente', 'sub' => 'Anexo 6 / Legal-Administrativo / Pendiente'],
      ['title' => 'Escrito sobre información confidencial', 'badge' => 'Pendiente', 'sub' => 'Anexo 18 / Legal-Administrativo / Pendiente'],
      ['title' => 'Carta de aceptación por virus informático', 'badge' => 'Pendiente', 'sub' => 'Anexo 16 / Legal-Administrativo / Pendiente'],
      ['title' => 'Declaración de aceptación de cláusulas de la Convocatoria', 'badge' => 'Pendiente', 'sub' => 'Anexo 22 / Legal-Administrativo / Pendiente'],
      ['title' => 'Escrito de conocimiento de la LFCE', 'badge' => 'Pendiente', 'sub' => 'Anexo 25 / Legal-Administrativo / Pendiente'],
      ['title' => 'Carta compromiso de registro en PROCURA', 'badge' => 'Pendiente', 'sub' => 'No aplica / Legal-Administrativo / Pendiente'],
      ['title' => 'Carta manifiesto de interés', 'badge' => 'Pendiente', 'sub' => 'Anexo 3 / Legal-Administrativo / Pendiente'],
      ['title' => 'Carta de aceptación del modelo de contrato', 'badge' => 'Pendiente', 'sub' => 'Anexo 28 / Legal-Administrativo / Pendiente'],
      ['title' => 'Carta de opinión de cumplimiento pública', 'badge' => 'Pendiente', 'sub' => 'Anexo 29 / Legal-Administrativo / Pendiente'],
      ['title' => 'Manifiesto de vínculos con servidores públicos', 'badge' => 'Pendiente', 'sub' => 'No aplica / Legal-Administrativo / Pendiente'],
      ['title' => 'Manifiesto de no ventaja indebida', 'badge' => 'Pendiente', 'sub' => 'No aplica / Legal-Administrativo / Pendiente'],
      ['title' => 'Manifiesto de no subcontratación', 'badge' => 'Pendiente', 'sub' => 'No aplica / Legal-Administrativo / Pendiente'],
      ['title' => 'Listado de verificación para recibir proposiciones (Anexo 15)', 'badge' => 'Pendiente', 'sub' => 'Anexo 15 / Legal-Administrativo / Pendiente'],
      ['title' => 'Escrito para la presentación de Opiniones de Cumplimiento (Fiscal, Seguridad Social e INFONAVIT) (Anexo 21)', 'badge' => 'Pendiente', 'sub' => 'Anexo 21 / Legal-Administrativo / Pendiente'],
      ['title' => 'Escrito de Solicitud de aclaración de convocatoria', 'badge' => 'Pendiente', 'sub' => 'Anexo 23 / Legal-Administrativo / Pendiente'],
      ['title' => 'Convenio de proposición conjunta', 'badge' => 'Pendiente', 'sub' => 'No aplica / Legal-Administrativo / Pendiente'],
      ['title' => 'Proposición Económica (Anexo 7)', 'badge' => 'Pendiente', 'sub' => 'Anexo 7 / Legal-Administrativo / Pendiente'],
      ['title' => 'Escrito libre de manifestaciones bajo protesta de decir verdad (Residentes en el extranjero)', 'badge' => 'Pendiente', 'sub' => 'No aplica / Legal-Administrativo / Pendiente'],
      ['title' => 'Carta de manifestación de contenido nacional y origen de los bienes', 'badge' => 'Pendiente', 'sub' => 'No aplica / Técnico / Pendiente'],
      ['title' => 'Descripción técnica de los bienes', 'badge' => 'Pendiente', 'sub' => 'No aplica / Técnico / Pendiente'],
      ['title' => 'Esquema estructural de la organización', 'badge' => 'Pendiente', 'sub' => 'No aplica / Técnico / Pendiente'],
      ['title' => 'Escrito de cumplimiento de normas (NOM y NMX)', 'badge' => 'Pendiente', 'sub' => 'No aplica / Técnico / Pendiente'],
      ['title' => 'Propuesta Económica', 'badge' => 'Pendiente', 'sub' => 'Anexo 7 / Otro / Pendiente'],
      ['title' => 'Proposición Económica', 'badge' => 'Pendiente', 'sub' => 'Anexo 7 / Otro / Pendiente']
  ];
@endphp

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
        <button type="button" class="pjd-studio-action-card is-purple" data-open-report-modal>
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

        <button type="button" class="pjd-studio-action-card is-orange" data-open-estrategia-modal>
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

        <button type="button" class="pjd-studio-action-card is-green" data-open-armado-modal>
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

      <div class="pjd-studio-list" id="pjdStudioGeneratedList">
        @if($pjdHasJuntaDocument)
          <button
            type="button"
            class="pjd-studio-item"
            data-open-generated-report="clarifications"
            data-studio-generated-item="clarifications"
          >
            <div class="pjd-studio-icon-box is-orange">
              <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8Z"></path>
                <path d="M8 9h8"></path>
                <path d="M8 13h5"></path>
              </svg>
            </div>

            <div class="pjd-studio-item-content">
              <span class="pjd-studio-item-title">Junta de Aclaraciones</span>
              <span class="pjd-studio-item-sub">Preguntas estratégicas generadas · {{ $pjdReportDate }}</span>
            </div>
          </button>
        @endif

        @if($pjdHasJuntaStrategy)
          <button
            type="button"
            class="pjd-studio-item"
            data-open-estrategia-modal
            data-studio-generated-item="strategy"
          >
            <div class="pjd-studio-icon-box is-orange">
              <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8Z"></path>
                <path d="M8 9h8"></path>
                <path d="M8 13h5"></path>
              </svg>
            </div>

            <div class="pjd-studio-item-content">
              <span class="pjd-studio-item-title">Estrategia de Junta de Aclaraciones</span>
              <span class="pjd-studio-item-sub">Estrategia táctica por pregunta · {{ $pjdReportDate }}</span>
            </div>
          </button>
        @endif

        @if($pjdHasAnalysisReport)
          <button
            type="button"
            class="pjd-studio-item"
            data-open-generated-report="analysis"
            data-studio-generated-item="analysis"
          >
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
              <span class="pjd-studio-item-sub">Documento ejecutivo con identidad visual · {{ $pjdReportDate }}</span>
            </div>
          </button>
        @endif

        @if($pjdHasFinanceReport)
          <button
            type="button"
            class="pjd-studio-item"
            data-open-generated-report="finance"
            data-studio-generated-item="finance"
          >
            <div class="pjd-studio-icon-box is-purple">
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
            </div>

            <div class="pjd-studio-item-content">
              <span class="pjd-studio-item-title">Reporte de Finanzas</span>
              <span class="pjd-studio-item-sub">Reporte ejecutivo con identidad visual · {{ $pjdReportDate }}</span>
            </div>
          </button>
        @endif

        @if($pjdHasLogisticsReport)
          <button
            type="button"
            class="pjd-studio-item"
            data-open-generated-report="logistics"
            data-studio-generated-item="logistics"
          >
            <div class="pjd-studio-icon-box is-purple">
              <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <path d="M8 13h8"></path>
                <path d="M8 17h5"></path>
              </svg>
            </div>

            <div class="pjd-studio-item-content">
              <span class="pjd-studio-item-title">Reporte de Logística</span>
              <span class="pjd-studio-item-sub">Reporte ejecutivo con identidad visual · {{ $pjdReportDate }}</span>
            </div>
          </button>
        @endif
      </div>
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
        <button
          type="button"
          class="pjd-studio-rail-btn is-purple"
          data-open-report-modal
          title="Hacer Reporte"
          aria-label="Hacer Reporte"
        >
          <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <path d="M9 13h6"></path>
            <path d="M9 17h4"></path>
          </svg>
          <span class="pjd-studio-rail-plus" aria-hidden="true">+</span>
        </button>

        <button
          type="button"
          class="pjd-studio-rail-btn is-orange"
          data-open-estrategia-modal
          title="Estrategia JA"
          aria-label="Estrategia JA"
        >
          <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8Z"></path>
            <path d="M8 9h8"></path>
            <path d="M8 13h5"></path>
          </svg>
          <span class="pjd-studio-rail-plus" aria-hidden="true">+</span>
        </button>

        <button
          type="button"
          class="pjd-studio-rail-btn is-green"
          data-open-armado-modal
          title="Redactar Documentos"
          aria-label="Redactar Documentos"
        >
          <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <path d="m9 15 2 2 4-4"></path>
          </svg>
          <span class="pjd-studio-rail-plus" aria-hidden="true">+</span>
        </button>

        <div class="pjd-studio-rail-sep" aria-hidden="true"></div>

        <div id="pjdStudioGeneratedRail" style="display:contents;">
          @if($pjdHasAnalysisReport)
            <button
              type="button"
              class="pjd-studio-rail-btn is-purple"
              data-open-generated-report="analysis"
              data-studio-generated-rail="analysis"
              title="Resumen Ejecutivo"
              aria-label="Abrir Resumen Ejecutivo"
            >
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
          @endif

          @if($pjdHasFinanceReport)
            <button
              type="button"
              class="pjd-studio-rail-btn is-purple"
              data-open-generated-report="finance"
              data-studio-generated-rail="finance"
              title="Reporte de Finanzas"
              aria-label="Abrir Reporte de Finanzas"
            >
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
          @endif

          @if($pjdHasLogisticsReport)
            <button
              type="button"
              class="pjd-studio-rail-btn is-purple"
              data-open-generated-report="logistics"
              data-studio-generated-rail="logistics"
              title="Reporte de Logística"
              aria-label="Abrir Reporte de Logística"
            >
              <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <path d="M8 13h8"></path>
                <path d="M8 17h5"></path>
              </svg>
            </button>
          @endif
        </div>
      </div>
      </div>
      </div>
    </div>
  </div>
</aside>


<div id="pjdReportModal" class="pjd-modal-backdrop" aria-hidden="true">
  <div class="pjd-report-modal-card" role="dialog" aria-modal="true" aria-labelledby="pjdReportModalTitle">
    
    <div class="pjd-report-modal-head">
      <div class="pjd-report-modal-title-wrap">
        <div class="pjd-report-modal-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
            <polyline points="14 2 14 8 20 8"/>
            <path d="M8 13h8"/>
            <path d="M8 17h6"/>
          </svg>
        </div>
        <div>
          <h3 class="pjd-report-modal-title" id="pjdReportModalTitle">Crear reporte</h3>
          <p class="pjd-report-modal-subtitle">Genera documentos ejecutivos para el proyecto activo.</p>
        </div>
      </div>
      <button type="button" class="pjd-report-modal-close" data-close-report-modal aria-label="Cerrar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"></path></svg>
      </button>
    </div>

    <div class="pjd-report-modal-body">
      
      <div class="pjd-report-modal-topline">
        <div>
          <div class="pjd-report-modal-kicker">Reportes ejecutivos</div>
          <p class="pjd-report-modal-copy">Ejecuta un reporte puntual o genera todos los documentos activos.</p>
        </div>
        <button type="button" class="pjd-report-run-all" data-report-run-all>
          <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          Ejecutar todos
        </button>
      </div>

      <div class="pjd-report-grid">
        
        <button type="button" class="pjd-report-card {{ $pjdHasFinanceReport ? 'is-generated' : '' }}" data-report-type="finance" data-report-title="Reporte Financiero de la Licitación">
          <div class="pjd-report-card-top">
            <span class="pjd-report-card-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                <polyline points="14 2 14 8 20 8"/>
                <path d="M12 11v6"/><path d="M9 14h6"/>
              </svg>
            </span>
            <span class="pjd-report-card-badge">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
              Generado
            </span>
          </div>
          <h4 class="pjd-report-card-title">Reporte de Finanzas</h4>
          <p class="pjd-report-card-text">Condiciones de pago, facturación, garantías y riesgos económicos.</p>
        </button>

        <button type="button" class="pjd-report-card {{ $pjdHasLogisticsReport ? 'is-generated' : '' }}" data-report-type="logistics" data-report-title="Reporte Logístico de la Licitación">
          <div class="pjd-report-card-top">
            <span class="pjd-report-card-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                <polyline points="14 2 14 8 20 8"/>
                <path d="M8 13h8"/><path d="M8 17h5"/>
              </svg>
            </span>
            <span class="pjd-report-card-badge">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
              Generado
            </span>
          </div>
          <h4 class="pjd-report-card-title">Reporte de Logística</h4>
          <p class="pjd-report-card-text">Entregas, plazos, transporte, almacenaje y coordinación operativa.</p>
        </button>

      </div>

      <div class="pjd-report-summary-row {{ $pjdHasAnalysisReport ? 'is-generated' : '' }}" data-report-summary-row>
        <div class="pjd-report-summary-main">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
            <polyline points="14 2 14 8 20 8"/>
            <path d="M9 13h6"/><path d="M9 17h4"/>
          </svg>

          <div>
            <div class="pjd-report-summary-title-line">
              <span class="pjd-report-summary-title">Resumen Ejecutivo – Análisis de Bases</span>
              
              <span class="pjd-report-card-badge" style="{{ $pjdHasAnalysisReport ? 'display:inline-flex;position:static;' : 'display:none;' }}" data-analysis-badge>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                Generado
              </span>
            </div>
            <p class="pjd-report-summary-text">Reporte generado a partir de la información del análisis de bases.</p>
            <div
              class="pjd-report-summary-status"
              data-analysis-status
              {{ $pjdHasAnalysisReport ? '' : 'hidden' }}
            >
              {{ $pjdHasAnalysisReport ? 'Reporte disponible. Presiona Regenerar para actualizarlo.' : '' }}
            </div>
          </div>
        </div>

        <button type="button" class="pjd-report-summary-action" data-report-type="analysis" data-report-title="Reporte de análisis de bases">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          {{ $pjdHasAnalysisReport ? 'Regenerar' : 'Generar' }}
        </button>
      </div>

      <div class="pjd-report-modal-toast" data-report-toast></div>
    </div>
  </div>
</div>

<div id="pjdEstrategiaModal" class="pjd-modal-backdrop" aria-hidden="true">
  <div class="pjd-estrategia-modal-card" role="dialog" aria-modal="true" aria-labelledby="pjdEstrategiaModalTitle">
    
    <div class="pjd-estrategia-modal-head">
      <div class="pjd-estrategia-modal-title-wrap">
        <div class="pjd-estrategia-modal-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8Z"></path>
            <path d="M8 9h8"></path>
            <path d="M8 13h5"></path>
          </svg>
        </div>
        <div>
          <h3 class="pjd-estrategia-modal-title" id="pjdEstrategiaModalTitle">Estrategia JA</h3>
          <p class="pjd-estrategia-modal-subtitle">Configura formato, riesgos e instrucciones para la Junta de Aclaraciones.</p>
        </div>
      </div>
      <button type="button" class="pjd-estrategia-modal-close" data-close-estrategia-modal aria-label="Cerrar">×</button>
    </div>

    <div class="pjd-estrategia-modal-body">
      
      <div class="pjd-estrategia-section">
        <div class="pjd-estrategia-section-header">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
            <line x1="10" y1="9" x2="8" y2="9"></line>
          </svg>
          <span>1. Formato específico</span>
        </div>
        <p class="pjd-estrategia-desc">Existe un formato específico para preparar la junta de aclaraciones?</p>
        <div class="pjd-estrategia-options">
          <label class="pjd-radio-label">
            <input type="radio" name="formato_ja" value="si"> Si, adjuntar formato
          </label>
          <label class="pjd-radio-label">
            <input type="radio" name="formato_ja" value="no" checked> No, usar estandar
          </label>
        </div>


        <div class="pjd-ja-template-upload" data-ja-template-upload>
          <label class="pjd-ja-template-drop">
            <input
              type="file"
              data-ja-template-file
              accept=".doc,.docx,.pdf,.xls,.xlsx"
            >

            <span class="pjd-ja-template-file">
              <strong data-ja-template-name>Seleccionar formato</strong>
              <span>Word, Excel o PDF · máximo 25 MB</span>
              <span>Las preguntas respetarán los encabezados y el orden del archivo.</span>
            </span>
          </label>
        </div>

        <div class="pjd-ja-template-progress" data-ja-progress></div>
      </div>

      <div class="pjd-estrategia-section">
        <div class="pjd-estrategia-section-header">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
          <span>2. Nivel de Riesgo a Atacar</span>
        </div>
        <p class="pjd-estrategia-desc">Selecciona los niveles de riesgo que deseas abordar en las aclaraciones.</p>
        <div class="pjd-estrategia-options">
          <label class="pjd-check-label">
            <input type="checkbox" name="riesgo_alto" checked> Riesgo Alto
          </label>
          <label class="pjd-check-label">
            <input type="checkbox" name="riesgo_medio" checked> Riesgo Medio
          </label>
          <label class="pjd-check-label">
            <input type="checkbox" name="riesgo_no_cumple" checked> No cumple
          </label>
        </div>
      </div>

      <div class="pjd-estrategia-section">
        <div class="pjd-estrategia-section-header">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
          </svg>
          <span>3. Instrucciones específicas</span>
        </div>
        <p class="pjd-estrategia-desc">Instrucciones adicionales para el agente (tono, formato de la pregunta, artículos a citar, etc.).</p>
        <textarea class="pjd-estrategia-textarea" placeholder="Ej: Redacta las preguntas de forma contundente y cita siempre la Ley de Adquisiciones..."></textarea>
      </div>

    </div>

    <div class="pjd-estrategia-modal-footer">
      <button type="button" class="pjd-btn-cancel" data-close-estrategia-modal>Cancelar</button>
      <button
        type="button"
        class="pjd-btn-primary"
        data-generate-clarifications
      >
        Generar Junta de Aclaraciones
      </button>
    </div>

  </div>
</div>


<div id="pjdArmadoModal" class="pjd-modal-backdrop" aria-hidden="true">
  <div class="pjd-armado-modal-card" role="dialog" aria-modal="true" aria-labelledby="pjdArmadoModalTitle">
    
    <div class="pjd-armado-modal-head">
      <div class="pjd-armado-modal-title-wrap">
        <div class="pjd-armado-modal-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
            <polyline points="10 9 9 9 8 9"></polyline>
          </svg>
        </div>
        <div>
          <h3 class="pjd-armado-modal-title" id="pjdArmadoModalTitle">Armado de Propuesta</h3>
          <p class="pjd-armado-modal-subtitle">Encabezado, seleccion de documentos y generacion por actor.</p>
        </div>
      </div>
      <button type="button" class="pjd-armado-modal-close" data-close-armado-modal aria-label="Cerrar">×</button>
    </div>

    <div class="pjd-armado-modal-body">
      
      <div class="pjd-armado-rep-box">
        <span class="pjd-armado-rep-label">Representante legal:</span>
        <input type="text" class="pjd-armado-rep-input" value="JUAN RENÉ TORT RODRÍGUEZ · DIRECTOR GENERAL" readonly>
      </div>

      <div class="pjd-armado-step">
        <div class="pjd-armado-step-inner">
          <div class="pjd-armado-step-header">
            <div class="pjd-armado-step-title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
              Paso 1. Encabezado
            </div>
            <span class="pjd-armado-badge-check">Check</span>
          </div>
          <p class="pjd-armado-step-desc">El encabezado ya esta guardado para este proyecto.</p>
          <div class="pjd-armado-step-actions">
            <button type="button" class="pjd-btn-outline" data-open-encabezado-modal>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
              Revisar
            </button>
            <button type="button" class="pjd-btn-outline">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
              Regenerar
            </button>
          </div>
        </div>
      </div>

      <div class="pjd-armado-step">
        <div class="pjd-armado-step-inner">
          <div class="pjd-armado-header-p2">
            <div class="pjd-armado-p2-info">
              <h4>Paso 2. Automatizacion de documentos</h4>
              <p>{{ count($listaDocumentos) }} documentos pendientes disponibles para redactar.<br>Solo se muestran documentos con cumplimiento Pendiente.</p>
            </div>
            <div class="pjd-armado-p2-controls">
              <label class="pjd-armado-todo-label">
                <input type="checkbox" id="pjdCheckAllDocs"> Todo
              </label>
              <button type="button" class="pjd-btn-blue">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M5 3l14 9-14 9V3z"></path></svg>
                Generar
              </button>
            </div>
          </div>
        </div>
        
        <div class="pjd-armado-doc-list">
          @foreach($listaDocumentos as $doc)
          <label class="pjd-doc-item">
            <input type="checkbox" class="pjd-doc-checkbox">
            <div class="pjd-doc-content">
              <div class="pjd-doc-title-row">
                <span class="pjd-doc-title">{{ $doc['title'] }}</span>
                <span class="pjd-doc-badge">
                  {{ $doc['badge'] }}
                </span>
              </div>
              <span class="pjd-doc-sub">{{ $doc['sub'] }}</span>
            </div>
          </label>
          @endforeach
        </div>
      </div>

    </div>
  </div>
</div>

<div id="pjdEncabezadoModal" class="pjd-modal-backdrop" aria-hidden="true">
  <div class="pjd-encabezado-card" role="dialog" aria-modal="true">
    <div class="pjd-encabezado-head">
      <h4 class="pjd-encabezado-title">Encabezado guardado</h4>
      <button type="button" class="pjd-encabezado-close" data-close-encabezado-modal>×</button>
    </div>
    <div class="pjd-encabezado-body">
      <div class="pjd-encabezado-table-wrap">
        <div class="pjd-encabezado-col-left">
          Caminos y Puentes Federales de Ingresos y Servicios Conexos<br>
          Unidad Regional Saltillo<br>
          Subgerencia de Administración
        </div>
        <div class="pjd-encabezado-col-right">
          <div class="pjd-encabezado-row">
            <div class="pjd-encabezado-label">Nombre</div>
            <div class="pjd-encabezado-value">monico replegal</div>
          </div>
          <div class="pjd-encabezado-row">
            <div class="pjd-encabezado-label">Telefono</div>
            <div class="pjd-encabezado-value"></div>
          </div>
          <div class="pjd-encabezado-row">
            <div class="pjd-encabezado-label">Celular</div>
            <div class="pjd-encabezado-value"></div>
          </div>
          <div class="pjd-encabezado-row">
            <div class="pjd-encabezado-label">E-mail</div>
            <div class="pjd-encabezado-value"></div>
          </div>
          <div class="pjd-encabezado-row">
            <div class="pjd-encabezado-label">Fecha</div>
            <div class="pjd-encabezado-value">Saltillo, Coahuila de Zaragoza, a [Día] de [Mes] del [Año]</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>



<div id="pjdReportEditorModal" class="pjd-pane pjd-word-pane" data-pane="report-editor" aria-hidden="true">
  <div class="pjd-word-modal-card" aria-labelledby="pjdWordReportTitle">
  <div class="pjd-word-topbar">
    <div class="pjd-word-page-info">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <rect x="4" y="3" width="16" height="18" rx="2"/>
        <path d="M9 3v18"/>
      </svg>
      <span>Página 1</span>
    </div>

    <div class="pjd-word-title-wrap">
      <div class="pjd-word-title" id="pjdWordReportTitle">
        Reporte
      </div>
    </div>

    <div class="pjd-word-actions">
      <button type="button" class="pjd-word-icon-btn is-edit" id="pjdWordEdit" title="Activar edición" aria-pressed="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M12 20h9"/>
          <path d="M16.5 3.5a2.12 2.12 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/>
        </svg>
      </button>

      <button type="button" class="pjd-word-icon-btn" id="pjdWordDownload" title="Descargar Word">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M12 3v12"/>
          <path d="m7 10 5 5 5-5"/>
          <path d="M5 21h14"/>
        </svg>
      </button>

      <button type="button" class="pjd-word-icon-btn is-primary" id="pjdWordSave" title="Guardar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/>
          <path d="M17 21v-8H7v8"/>
          <path d="M7 3v5h8"/>
        </svg>
      </button>

      <button type="button" class="pjd-word-icon-btn" id="pjdWordExpand" title="Hacer grande" aria-expanded="false">
        <svg class="pjd-word-expand-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M8 3H3v5"/>
          <path d="M3 3l6 6"/>
          <path d="M16 3h5v5"/>
          <path d="m21 3-6 6"/>
          <path d="M8 21H3v-5"/>
          <path d="m3 21 6-6"/>
          <path d="M16 21h5v-5"/>
          <path d="m21 21-6-6"/>
        </svg>
        <svg class="pjd-word-compress-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M9 3v6H3"/>
          <path d="M15 21v-6h6"/>
          <path d="M3 9l7-7"/>
          <path d="M21 15l-7 7"/>
        </svg>
      </button>

      <button type="button" class="pjd-word-icon-btn" id="pjdWordClose" title="Cerrar editor">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M18 6 6 18"/>
          <path d="m6 6 12 12"/>
        </svg>
      </button>
    </div>
  </div>

  <div class="pjd-word-toolbar" id="pjdWordToolbar">
    <div class="pjd-word-toolbar-group">
      <button type="button" class="pjd-word-tool-btn" data-word-cmd="undo" title="Deshacer">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 0 12h-2"/></svg>
      </button>
      <button type="button" class="pjd-word-tool-btn" data-word-cmd="redo" title="Rehacer">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m15 14 5-5-5-5"/><path d="M20 9H10a6 6 0 0 0 0 12h2"/></svg>
      </button>
    </div>

    <div class="pjd-word-toolbar-group">
      <select class="pjd-word-select" data-word-block title="Estilo">
        <option value="P">Párrafo</option>
        <option value="H1">Título 1</option>
        <option value="H2">Título 2</option>
        <option value="H3">Título 3</option>
        <option value="BLOCKQUOTE">Cita</option>
      </select>

      <select class="pjd-word-select" data-word-font title="Fuente">
        <option value="Arial" selected>Arial</option>
        <option value="Quicksand">Quicksand</option>
        <option value="Georgia">Georgia</option>
        <option value="Times New Roman">Times New Roman</option>
        <option value="Courier New">Courier New</option>
      </select>

      <select class="pjd-word-select is-small" data-word-size title="Tamaño">
        <option value="2">12</option>
        <option value="3" selected>16</option>
        <option value="4">18</option>
        <option value="5">24</option>
        <option value="6">32</option>
        <option value="7">48</option>
      </select>
    </div>

    <div class="pjd-word-toolbar-group">
      <button type="button" class="pjd-word-tool-btn" data-word-cmd="bold" title="Negrita"><strong>B</strong></button>
      <button type="button" class="pjd-word-tool-btn" data-word-cmd="italic" title="Cursiva"><em>I</em></button>
      <button type="button" class="pjd-word-tool-btn" data-word-cmd="underline" title="Subrayado"><u>U</u></button>
      <button type="button" class="pjd-word-tool-btn" data-word-cmd="strikeThrough" title="Tachado"><s>S</s></button>
      <input type="color" class="pjd-word-color" data-word-color="foreColor" value="#26364d" title="Color de texto">
      <input type="color" class="pjd-word-color" data-word-color="hiliteColor" value="#e6f0ff" title="Resaltado">
    </div>

    <div class="pjd-word-toolbar-group">
      <button type="button" class="pjd-word-tool-btn" data-word-cmd="justifyLeft" title="Alinear a la izquierda">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 6h14M4 10h10M4 14h14M4 18h10"/></svg>
      </button>
      <button type="button" class="pjd-word-tool-btn" data-word-cmd="justifyCenter" title="Centrar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 6h14M8 10h8M5 14h14M8 18h8"/></svg>
      </button>
      <button type="button" class="pjd-word-tool-btn" data-word-cmd="justifyRight" title="Alinear a la derecha">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 6h14M10 10h10M6 14h14M10 18h10"/></svg>
      </button>
      <button type="button" class="pjd-word-tool-btn" data-word-cmd="justifyFull" title="Justificar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
      </button>
    </div>

    <div class="pjd-word-toolbar-group">
      <button type="button" class="pjd-word-tool-btn" data-word-cmd="insertUnorderedList" title="Lista con viñetas">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M8 6h12M8 12h12M8 18h12"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/></svg>
      </button>
      <button type="button" class="pjd-word-tool-btn" data-word-cmd="insertOrderedList" title="Lista numerada">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M10 6h10M10 12h10M10 18h10"/><path d="M4 6h1v4M3.8 10h2.4M4 14a1 1 0 1 1 2 0c0 .6-.8 1.1-2 2h2"/></svg>
      </button>
      <button type="button" class="pjd-word-tool-btn" data-word-cmd="outdent" title="Disminuir sangría">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 6H10M20 12H10M20 18H10"/><path d="m4 12 4-4v8z"/></svg>
      </button>
      <button type="button" class="pjd-word-tool-btn" data-word-cmd="indent" title="Aumentar sangría">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 6H10M20 12H10M20 18H10"/><path d="m8 12-4-4v8z"/></svg>
      </button>
    </div>

    <div class="pjd-word-toolbar-group">
      <button type="button" class="pjd-word-tool-btn" data-word-action="link" title="Insertar enlace">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M10 13a5 5 0 0 0 7.1 0l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1"/><path d="M14 11a5 5 0 0 0-7.1 0l-2 2A5 5 0 0 0 12 20.1l1.1-1.1"/></svg>
      </button>
      <button type="button" class="pjd-word-tool-btn" data-word-cmd="unlink" title="Quitar enlace">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M15 7h2a5 5 0 0 1 0 10h-2"/><path d="M9 17H7A5 5 0 0 1 7 7h2"/><path d="M3 3l18 18"/></svg>
      </button>
      <button type="button" class="pjd-word-tool-btn" data-word-action="image" title="Insertar imagen">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
      </button>
      <button type="button" class="pjd-word-tool-btn" data-word-action="table" title="Insertar tabla">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M9 4v16M15 4v16"/></svg>
      </button>
      <button type="button" class="pjd-word-tool-btn" data-word-cmd="insertHorizontalRule" title="Separador">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14"/></svg>
      </button>
      <button type="button" class="pjd-word-tool-btn" data-word-cmd="removeFormat" title="Limpiar formato">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 7h10M10 7 6 19M14 19H6M13 13l6 6M19 13l-6 6"/></svg>
      </button>
    </div>
  </div>

  <div class="pjd-word-statusbar">
    <span id="pjdWordStatus">Guardado automático activo</span>
    <span id="pjdWordCount">0 palabras</span>
  </div>

  <div class="pjd-word-workspace">
    <div class="pjd-word-page">
      <div
        id="pjdWordEditor"
        class="pjd-word-editor"
        contenteditable="true"
        spellcheck="true"
      ></div>
    </div>
  </div>

  <div class="pjd-word-toast" id="pjdWordToast"></div>
  </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const body = document.body;
  const studio = document.getElementById('pjdStudioRight');
  const modalReporte = document.getElementById('pjdReportModal');
  const modalEstrategia = document.getElementById('pjdEstrategiaModal');
  const modalArmado = document.getElementById('pjdArmadoModal'); 
  const modalEncabezado = document.getElementById('pjdEncabezadoModal'); 

  if (!studio) return;

  const setStudio = function (open) {
    body.classList.toggle('pjd-studio-open', open);
    body.classList.toggle('pjd-studio-collapsed', !open);
    const rail = studio.querySelector('.pjd-studio-rail');
    if (rail) rail.setAttribute('aria-hidden', open ? 'true' : 'false');
    try { localStorage.setItem('pjdStudioOpen', open ? '1' : '0'); } catch (e) {}
  };

  let initialOpen = true;
  try { if (localStorage.getItem('pjdStudioOpen') === '0') initialOpen = false; } catch (e) {}
  setStudio(initialOpen);

  studio.querySelectorAll('[data-studio-collapse]').forEach(function (btn) {
    btn.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      setStudio(false);
    });
  });

  studio.querySelectorAll('[data-studio-open]').forEach(function (btn) {
    btn.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      setStudio(true);
    });
  });

  // Función genérica para modales
  const openModal = (modal) => {
    if(!modal) return;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    body.classList.add('pjd-modal-open');
  };
  const closeModal = (modal) => {
    if(!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    // Solo quitar clase del body si no hay otros modales abiertos
    if (!document.querySelectorAll('.pjd-modal-backdrop.is-open').length) {
      body.classList.remove('pjd-modal-open');
    }
  };
  const setupModal = (modal, openSelector, closeSelector) => {
    if(!modal) return;
    document.querySelectorAll(openSelector).forEach(btn => btn.addEventListener('click', () => openModal(modal)));
    modal.querySelectorAll(closeSelector).forEach(btn => btn.addEventListener('click', () => closeModal(modal)));
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(modal); });
  };

  // Inicializar Modales
  setupModal(modalReporte, '[data-open-report-modal]', '[data-close-report-modal]');
  setupModal(modalEstrategia, '[data-open-estrategia-modal]', '[data-close-estrategia-modal]');
  setupModal(modalArmado, '[data-open-armado-modal]', '[data-close-armado-modal]');
  setupModal(modalEncabezado, '[data-open-encabezado-modal]', '[data-close-encabezado-modal]');

  // Lógica Checkbox "Todo" en Armado Modal
  if (modalArmado) {
    const checkAll = modalArmado.querySelector('#pjdCheckAllDocs');
    const docCheckboxes = modalArmado.querySelectorAll('.pjd-doc-checkbox');
    
    if (checkAll && docCheckboxes) {
      checkAll.addEventListener('change', function() {
        docCheckboxes.forEach(chk => chk.checked = checkAll.checked);
      });

      docCheckboxes.forEach(chk => {
        chk.addEventListener('change', function() {
          const allChecked = Array.from(docCheckboxes).every(c => c.checked);
          const someChecked = Array.from(docCheckboxes).some(c => c.checked);
          checkAll.checked = allChecked;
          checkAll.indeterminate = someChecked && !allChecked;
        });
      });
    }
  }

  // Generación de Junta de Aclaraciones con editor Word
  if (modalEstrategia) {
    const generateClarificationsButton = modalEstrategia.querySelector('[data-generate-clarifications]');

    const formatRadios = Array.from(modalEstrategia.querySelectorAll('input[name="formato_ja"]'));
    const templateUpload = modalEstrategia.querySelector('[data-ja-template-upload]');
    const templateInput = modalEstrategia.querySelector('[data-ja-template-file]');
    const templateName = modalEstrategia.querySelector('[data-ja-template-name]');
    const progressBox = modalEstrategia.querySelector('[data-ja-progress]');

    const syncTemplateVisibility = function () {
      const selected = modalEstrategia.querySelector('input[name="formato_ja"]:checked')?.value || 'no';
      templateUpload?.classList.toggle('is-visible', selected === 'si');
    };

    formatRadios.forEach(function (radio) {
      radio.addEventListener('change', syncTemplateVisibility);
    });

    templateInput?.addEventListener('change', function () {
      const file = templateInput.files?.[0];
      if (templateName) {
        templateName.textContent = file ? file.name : 'Seleccionar formato';
      }
    });

    syncTemplateVisibility();

    if (generateClarificationsButton) {
      let clarificationsPollingTimer = null;

      const stopClarificationsPolling = function () {
        if (clarificationsPollingTimer) {
          clearTimeout(clarificationsPollingTimer);
          clarificationsPollingTimer = null;
        }
      };

      const pollClarificationsStatus = async function (reportUrl, csrfToken, jobId, originalHtml) {
        try {
          const statusForm = new FormData();
          statusForm.append('_token', csrfToken);
          statusForm.append('action', 'clarifications_status');
          statusForm.append('job_id', jobId);

          const response = await fetch(reportUrl, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: statusForm
          });

          const raw = await response.text();
          let data = {};

          try {
            data = raw ? JSON.parse(raw) : {};
          } catch (parseError) {
            throw new Error('El servidor devolvió una respuesta inválida (' + response.status + ').');
          }

          if (!response.ok || data.ok === false) {
            throw new Error(data.message || data.error || 'No se pudo consultar el avance.');
          }

          if (progressBox) {
            progressBox.textContent = (data.message || 'Procesando...') + ' ' + Number(data.progress || 0) + '%';
            progressBox.classList.add('is-visible');
          }

          if (data.status === 'completed' && data.html) {
            stopClarificationsPolling();

            generatedReports.clarifications = {
              title: data.report_title || 'Junta de Aclaraciones - Preguntas Estratégicas',
              html: data.html,
              template_name: data.template_name || null
            };

            if (typeof ensureStudioReportItem === 'function') {
              ensureStudioReportItem('clarifications', generatedReports.clarifications.title);
            }

            generateClarificationsButton.disabled = false;
            generateClarificationsButton.innerHTML = originalHtml;
            closeModal(modalEstrategia);

            if (typeof openWordEditor === 'function') {
              openWordEditor('clarifications', generatedReports.clarifications.title, data.html);
            }

            return;
          }

          if (data.status === 'failed') {
            stopClarificationsPolling();
            generateClarificationsButton.disabled = false;
            generateClarificationsButton.innerHTML = originalHtml;

            if (progressBox) {
              progressBox.textContent = data.message || 'La generación no pudo completarse.';
              progressBox.classList.add('is-visible');
            }

            return;
          }

          clarificationsPollingTimer = setTimeout(function () {
            pollClarificationsStatus(reportUrl, csrfToken, jobId, originalHtml);
          }, 2500);
        } catch (error) {
          stopClarificationsPolling();
          generateClarificationsButton.disabled = false;
          generateClarificationsButton.innerHTML = originalHtml;

          if (progressBox) {
            progressBox.textContent = error.message || 'No se pudo consultar el proceso.';
            progressBox.classList.add('is-visible');
          }
        }
      };

      generateClarificationsButton.addEventListener('click', async function () {
        stopClarificationsPolling();

        const formatMode = modalEstrategia.querySelector('input[name="formato_ja"]:checked')?.value || 'no';
        const riskLevels = [];

        if (modalEstrategia.querySelector('input[name="riesgo_alto"]')?.checked) riskLevels.push('alto');
        if (modalEstrategia.querySelector('input[name="riesgo_medio"]')?.checked) riskLevels.push('medio');
        if (modalEstrategia.querySelector('input[name="riesgo_no_cumple"]')?.checked) riskLevels.push('no_cumple');

        const instructions = modalEstrategia.querySelector('.pjd-estrategia-textarea')?.value.trim() || '';
        const templateFile = templateInput?.files?.[0] || null;
        const originalHtml = generateClarificationsButton.innerHTML;

        if (formatMode === 'si' && !templateFile) {
          if (progressBox) {
            progressBox.textContent = 'Selecciona el archivo Word, Excel o PDF que se usará como formato.';
            progressBox.classList.add('is-visible');
          }
          templateInput?.click();
          return;
        }

        generateClarificationsButton.disabled = true;
        generateClarificationsButton.textContent = 'Subiendo archivo e iniciando análisis...';

        if (progressBox) {
          progressBox.textContent = 'Subiendo el formato y preparando el proceso...';
          progressBox.classList.add('is-visible');
        }

        try {
          const reportUrl = @json(route('projects.report', $project));
          const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || @json(csrf_token());
          const formData = new FormData();

          formData.append('_token', csrfToken);
          formData.append('action', 'generate');
          formData.append('report_type', 'clarifications');
          formData.append('report_title', 'Junta de Aclaraciones - Preguntas Estratégicas');
          formData.append('format_mode', formatMode === 'si' ? 'formato_especifico' : 'estandar');
          formData.append('instructions', instructions);

          if (templateFile) formData.append('template_file', templateFile, templateFile.name);
          riskLevels.forEach(function (risk) { formData.append('risk_levels[]', risk); });

          const response = await fetch(reportUrl, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: formData
          });

          const raw = await response.text();
          let data = {};

          try {
            data = raw ? JSON.parse(raw) : {};
          } catch (parseError) {
            throw new Error('El servidor devolvió una respuesta inválida (' + response.status + ').');
          }

          if (!response.ok || data.ok === false || !data.queued || !data.job_id) {
            throw new Error(data.message || data.error || 'No se pudo iniciar la generación.');
          }

          if (progressBox) progressBox.textContent = data.message || 'La generación comenzó en segundo plano.';
          generateClarificationsButton.textContent = 'Analizando bases...';

          pollClarificationsStatus(reportUrl, csrfToken, data.job_id, originalHtml);
        } catch (error) {
          generateClarificationsButton.disabled = false;
          generateClarificationsButton.innerHTML = originalHtml;

          if (progressBox) {
            progressBox.textContent = error.message || 'No se pudo iniciar la Junta de Aclaraciones.';
            progressBox.classList.add('is-visible');
          }
        }
      });
    }
  }

  // Report Generation Logic 
  if (modalReporte) {
    const reportUrl = @json(route('projects.report', $project));
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || @json(csrf_token());
    const toast = modalReporte.querySelector('[data-report-toast]');
    const cards = Array.from(modalReporte.querySelectorAll('[data-report-type]'));
    const runAllButton = modalReporte.querySelector('[data-report-run-all]');

    const showToast = function (message, error = false) {
      if (!toast) return;
      toast.textContent = message;
      toast.classList.toggle('is-error', error);
      toast.classList.add('is-visible');
      clearTimeout(toast._timer);
      toast._timer = setTimeout(() => toast.classList.remove('is-visible'), 3600);
    };

    const updateGeneratedState = function (type, button) {
      if (type === 'analysis') {
        const badge = modalReporte.querySelector('[data-analysis-badge]');
        if (badge) {
          badge.style.display = 'inline-flex';
          badge.style.position = 'static';
        }
        if (button) button.lastChild.textContent = ' Regenerar';
        return;
      }
      const card = modalReporte.querySelector('[data-report-type="' + CSS.escape(type) + '"]');
      if (card) card.classList.add('is-generated');
    };

    const generateReport = async function (type, title, button) {
      const originalHtml = button ? button.innerHTML : '';
      const analysisRow = modalReporte.querySelector('[data-report-summary-row]');
      const analysisStatus = modalReporte.querySelector('[data-analysis-status]');

      if (button) {
        button.disabled = true;
        button.classList.add('pjd-report-loading', 'is-loading');

        if (type === 'analysis') {
          button.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9" stroke-opacity=".25"></circle><path d="M21 12a9 9 0 0 1-9 9"></path></svg> Regenerando...';
        }
      }

      if (type === 'analysis' && analysisRow) {
        analysisRow.classList.add('is-regenerating');
        analysisRow.classList.remove('is-regenerated');
      }

      if (type === 'analysis' && analysisStatus) {
        analysisStatus.hidden = false;
        analysisStatus.textContent = 'Regenerando el reporte con la información más reciente...';
      }

      try {
        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('action', 'generate');
        formData.append('report_type', type);
        formData.append('report_title', title || '');

        const response = await fetch(reportUrl, {
          method: 'POST',
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
          body: formData
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.ok === false || !data.html) throw new Error(data.message || data.error || 'Error.');

        updateGeneratedState(type, button);

        generatedReports[type] = {
          title: title || data.report_title || 'Reporte',
          html: data.html
        };

        ensureStudioReportItem(type, generatedReports[type].title);
        ensureStudioRailItem(type);

        const regeneratedAt = new Date();
        const regeneratedLabel = regeneratedAt.toLocaleDateString('es-MX')
          + ' ' + regeneratedAt.toLocaleTimeString('es-MX', {
            hour: '2-digit',
            minute: '2-digit'
          });

        if (type === 'analysis' && analysisStatus) {
          analysisStatus.hidden = false;
          analysisStatus.textContent = 'Regenerado correctamente: ' + regeneratedLabel;
        }

        if (type === 'analysis' && analysisRow) {
          analysisRow.classList.remove('is-regenerating');
          analysisRow.classList.add('is-regenerated');
        }

        if (type === 'analysis' && button) {
          button.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="m9 12 2 2 4-4"></path></svg> Regenerado';
        }

        showToast('Reporte regenerado y guardado correctamente.');

        window.setTimeout(function () {
          closeModal(modalReporte);
          openWordEditor(
            type,
            title || data.report_title || generatedReports[type].title,
            data.html
          );
        }, type === 'analysis' ? 900 : 150);

        return data;
      } catch (error) {
        if (type === 'analysis' && analysisRow) {
          analysisRow.classList.remove('is-regenerating');
        }

        if (type === 'analysis' && analysisStatus) {
          analysisStatus.hidden = false;
          analysisStatus.textContent = 'No se pudo regenerar el reporte. Revisa el error e inténtalo nuevamente.';
        }

        showToast(error.message || 'No se pudo generar el reporte.', true);
        throw error;
      } finally {
        if (button) {
          button.disabled = false;
          button.classList.remove('pjd-report-loading', 'is-loading');

          if (type !== 'analysis') {
            button.innerHTML = originalHtml;
          } else {
            window.setTimeout(function () {
              button.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg> Regenerar';
            }, 1200);
          }
        }
      }
    };

    cards.forEach(button => {
      button.addEventListener('click', async () => {
        const type = button.dataset.reportType;
        const title = button.dataset.reportTitle || '';
        if (type) try { await generateReport(type, title, button); } catch (e) {}
      });
    });

    if (runAllButton) {
      runAllButton.addEventListener('click', async () => {
        const originalHtml = runAllButton.innerHTML;
        runAllButton.disabled = true;
        runAllButton.textContent = 'Generando...';
        try {
          const sequence = [
            { type: 'finance', title: 'Reporte Financiero' },
            { type: 'logistics', title: 'Reporte Logístico' },
            { type: 'analysis', title: 'Reporte Análisis' }
          ];
          for (const report of sequence) {
            const button = modalReporte.querySelector('[data-report-type="' + report.type + '"]');
            await generateReport(report.type, report.title, button);
          }
          showToast('Todos los reportes generados.');
        } catch (e) {
          showToast('No se generaron todos.', true);
        } finally {
          runAllButton.disabled = false;
          runAllButton.innerHTML = originalHtml;
        }
      });
    }
  }

  // Cerrar con Escape
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      // Priorizar el cierre del sub-modal si está abierto
      if (modalEncabezado && modalEncabezado.classList.contains('is-open')) {
        closeModal(modalEncabezado);
      } else {
        if (modalReporte && modalReporte.classList.contains('is-open')) closeModal(modalReporte);
        if (modalEstrategia && modalEstrategia.classList.contains('is-open')) closeModal(modalEstrategia);
        if (modalArmado && modalArmado.classList.contains('is-open')) closeModal(modalArmado);
      }
    }
  });

});


  /* ============================================================
     EDITOR DE REPORTES TIPO WORD
     ============================================================ */
  const body = document.body;
  const reportUrl = @json(route('projects.report', $project));
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || @json(csrf_token());
  const modalReporte = document.getElementById('pjdReportModal');

  const openModal = function (modal) {
    if (!modal) return;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('pjd-modal-open');
  };

  const showToast = function (message, error = false) {
    const toast = modalReporte?.querySelector('[data-report-toast]');

    if (!toast) {
      window.alert(message);
      return;
    }

    toast.textContent = message;
    toast.classList.toggle('is-error', error);
    toast.classList.add('is-visible');

    clearTimeout(toast._timer);
    toast._timer = setTimeout(function () {
      toast.classList.remove('is-visible');
    }, 3200);
  };

  const wordModal = document.getElementById('pjdReportEditorModal');
  const wordEditor = document.getElementById('pjdWordEditor');
  const wordTitle = document.getElementById('pjdWordReportTitle');
  const wordStatus = document.getElementById('pjdWordStatus');
  const wordCount = document.getElementById('pjdWordCount');
  const wordEditButton = document.getElementById('pjdWordEdit');
  const wordSaveButton = document.getElementById('pjdWordSave');
  const wordDownloadButton = document.getElementById('pjdWordDownload');
  const wordExpandButton = document.getElementById('pjdWordExpand');
  const wordCloseButton = document.getElementById('pjdWordClose');
  const wordToolbar = document.getElementById('pjdWordToolbar');
  const wordToast = document.getElementById('pjdWordToast');
  const pjdRightPanel = document.querySelector('.pjd-right');
  let pjdPreviousActivePane = null;
  let pjdStudioWasCollapsedBeforeReport = null;

  const studioGeneratedList = document.getElementById('pjdStudioGeneratedList');
  const studioGeneratedRail = document.getElementById('pjdStudioGeneratedRail');

  const formatStudioDate = function () {
    return new Intl.DateTimeFormat('es-MX').format(new Date());
  };

  const bindGeneratedItem = function (button) {
    if (!button || button.dataset.bound === '1') {
      return;
    }

    button.dataset.bound = '1';

    button.addEventListener('click', function () {
      const type = button.dataset.openGeneratedReport;
      const report = generatedReports[type] || {};

      if (!type) {
        return;
      }

      if (!report.html) {
        showToast('Primero genera este reporte desde Crear reporte.', true);
        openModal(modalReporte);
        return;
      }

      openWordEditor(type, report.title, report.html);
    });
  };

  const ensureStudioRailItem = function (type) {
    if (!studioGeneratedRail) {
      return;
    }

    let item = studioGeneratedRail.querySelector(
      '[data-studio-generated-rail="' + CSS.escape(type) + '"]'
    );

    if (item) {
      bindGeneratedItem(item);
      return;
    }

    const config = {
      analysis: {
        title: 'Resumen Ejecutivo',
        icon: '<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M12 11l1 1 3-3"></path>',
        badge: '<div class="pjd-badge-icon is-star"><svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg></div>'
      },
      finance: {
        title: 'Reporte de Finanzas',
        icon: '<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><circle cx="12" cy="12" r="2.5"></circle><path d="m14 14 2.5 2.5"></path><path d="m16.5 11 2.5-2.5"></path>',
        badge: '<div class="pjd-badge-icon is-dollar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="22"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></div>'
      },
      logistics: {
        title: 'Reporte de Logística',
        icon: '<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M8 13h8"></path><path d="M8 17h5"></path>',
        badge: ''
      }
    };

    const current = config[type];

    if (!current) {
      return;
    }

    item = document.createElement('button');
    item.type = 'button';
    item.className = 'pjd-studio-rail-btn is-purple';
    item.dataset.openGeneratedReport = type;
    item.dataset.studioGeneratedRail = type;
    item.title = current.title;
    item.setAttribute('aria-label', 'Abrir ' + current.title);
    item.innerHTML = '<svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + current.icon + '</svg>' + current.badge;

    studioGeneratedRail.appendChild(item);
    bindGeneratedItem(item);
  };

  const ensureStudioReportItem = function (type, title) {
    if (!studioGeneratedList) {
      return;
    }

    let item = studioGeneratedList.querySelector(
      '[data-studio-generated-item="' + CSS.escape(type) + '"]'
    );

    if (item) {
      bindGeneratedItem(item);
      return;
    }

    const config = {
      analysis: {
        title: 'Resumen Ejecutivo',
        subtitle: 'Documento ejecutivo con identidad visual',
        badge: 'star'
      },
      finance: {
        title: 'Reporte de Finanzas',
        subtitle: 'Reporte ejecutivo con identidad visual',
        badge: 'dollar'
      },
      logistics: {
        title: 'Reporte de Logística',
        subtitle: 'Reporte ejecutivo con identidad visual',
        badge: ''
      },
      clarifications: {
        title: 'Junta de Aclaraciones',
        subtitle: 'Preguntas estratégicas listas para revisión',
        badge: 'clarifications'
      }
    };

    const current = config[type];

    if (!current) {
      return;
    }

    item = document.createElement('button');
    item.type = 'button';
    item.className = 'pjd-studio-item';
    item.dataset.openGeneratedReport = type;
    item.dataset.studioGeneratedItem = type;

    const badgeHtml = current.badge === 'star'
      ? '<div class="pjd-badge-icon is-star"><svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg></div>'
      : (
          current.badge === 'dollar'
            ? '<div class="pjd-badge-icon is-dollar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="2" x2="12" y2="22"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></div>'
            : (
                current.badge === 'clarifications'
                  ? '<div class="pjd-badge-icon is-star"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8Z"></path><path d="M8 9h8"></path><path d="M8 13h5"></path></svg></div>'
                  : ''
              )
        );

    item.innerHTML =
      '<div class="pjd-studio-icon-box is-purple">'
      + '<svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
      + '<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>'
      + '<polyline points="14 2 14 8 20 8"></polyline>'
      + '</svg>'
      + badgeHtml
      + '</div>'
      + '<div class="pjd-studio-item-content">'
      + '<span class="pjd-studio-item-title">' + current.title + '</span>'
      + '<span class="pjd-studio-item-sub">' + current.subtitle + ' · ' + formatStudioDate() + '</span>'
      + '</div>';

    studioGeneratedList.appendChild(item);
    bindGeneratedItem(item);
  };

  const generatedReports = {
    analysis: {
      title: 'Resumen Ejecutivo – Análisis de Bases',
      html: @json($pjdGeneratedReports['analysis']['html'] ?? $project->report_content ?? '')
    },
    finance: {
      title: 'Reporte de Finanzas',
      html: @json($pjdGeneratedReports['finance']['html'] ?? '')
    },
    logistics: {
      title: 'Reporte de Logística',
      html: @json($pjdGeneratedReports['logistics']['html'] ?? '')
    },
    clarifications: {
      title: 'Junta de Aclaraciones - Preguntas Estratégicas',
      html: @json($pjdGeneratedReports['clarifications']['html'] ?? data_get($project->structured_data, 'junta_aclaraciones.html', ''))
    }
  };

  Object.keys(generatedReports).forEach(function (type) {
    if (generatedReports[type] && generatedReports[type].html) {
      ensureStudioRailItem(type);
    }
  });

  let pjdStudioCollapsedBeforeEditor = null;
  if (wordModal && pjdRightPanel && wordModal.parentElement !== pjdRightPanel) {
    pjdRightPanel.appendChild(wordModal);
  }

  let activeReportType = 'analysis';
  let activeReportTitle = 'Reporte';
  let wordSaveTimer = null;
  let wordIsSaving = false;
  let wordIsDirty = false;

  const showWordToast = function (message, error = false) {
    if (!wordToast) return;

    wordToast.textContent = message;
    wordToast.classList.toggle('is-error', error);
    wordToast.classList.add('is-visible');

    clearTimeout(wordToast._timer);
    wordToast._timer = setTimeout(function () {
      wordToast.classList.remove('is-visible');
    }, 3200);
  };

  const updateWordCount = function () {
    if (!wordEditor || !wordCount) return;

    const text = wordEditor.innerText.replace(/\s+/g, ' ').trim();
    const words = text ? text.split(' ').length : 0;
    wordCount.textContent = words + (words === 1 ? ' palabra' : ' palabras');
  };

  const openWordEditor = function (type, title, html) {
    if (!wordModal || !wordEditor || !pjdRightPanel) return;

    activeReportType = type || 'analysis';
    activeReportTitle = title || generatedReports[activeReportType]?.title || 'Reporte';

    wordTitle.textContent = activeReportTitle;
    wordEditor.innerHTML = html || generatedReports[activeReportType]?.html || '<h1>' + activeReportTitle + '</h1><p>El reporte todavía no tiene contenido.</p>';

    pjdPreviousActivePane = pjdRightPanel.querySelector('.pjd-pane.is-active:not(#pjdReportEditorModal)');

    pjdRightPanel.querySelectorAll('.pjd-pane.is-active').forEach(function (pane) {
      if (pane !== wordModal) {
        pane.classList.remove('is-active');
      }
    });

    pjdStudioWasCollapsedBeforeReport = body.classList.contains('pjd-studio-collapsed');
    body.classList.add('pjd-studio-collapsed');
    body.classList.remove('pjd-studio-open');

    pjdRightPanel.classList.add('pjd-report-editor-active');
    wordModal.classList.add('is-open', 'is-active');
    wordModal.setAttribute('aria-hidden', 'false');
    body.classList.add('pjd-report-editor-open');

    wordIsDirty = false;
    if (wordStatus) wordStatus.textContent = 'Guardado automático activo';

    updateWordCount();

    setTimeout(function () {
      wordEditor.focus();
      wordModal.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 60);
  };

  const closeWordEditor = async function () {
    if (!wordModal || !pjdRightPanel) return;

    if (wordIsDirty) {
      try {
        await saveWordReport(false);
      } catch (error) {
        if (!window.confirm('No se pudo guardar el último cambio. ¿Cerrar de todas formas?')) {
          return;
        }
      }
    }

    wordModal.classList.remove('is-open', 'is-active');
    wordModal.setAttribute('aria-hidden', 'true');
    pjdRightPanel.classList.remove('pjd-report-editor-active');
    body.classList.remove('pjd-report-editor-open');

    if (pjdStudioWasCollapsedBeforeReport === false) {
      body.classList.remove('pjd-studio-collapsed');
      body.classList.add('pjd-studio-open');
    } else {
      body.classList.add('pjd-studio-collapsed');
      body.classList.remove('pjd-studio-open');
    }

    if (pjdPreviousActivePane && document.body.contains(pjdPreviousActivePane)) {
      pjdPreviousActivePane.classList.add('is-active');
    } else {
      pjdRightPanel.querySelector('.pjd-pane[data-pane="ficha"]')?.classList.add('is-active');
    }

    pjdPreviousActivePane = null;
    pjdStudioWasCollapsedBeforeReport = null;
  };

  const leaveWordEditorForNavigation = function () {
    if (!wordModal || !pjdRightPanel || !wordModal.classList.contains('is-open')) {
      return;
    }

    if (wordIsDirty) {
      saveWordReport(false).catch(function () {});
    }

    wordModal.classList.remove('is-open', 'is-active');
    wordModal.setAttribute('aria-hidden', 'true');
    pjdRightPanel.classList.remove('pjd-report-editor-active');
    body.classList.remove('pjd-report-editor-open');

    pjdPreviousActivePane = null;
    pjdStudioWasCollapsedBeforeReport = null;
  };

  const saveWordReport = async function (showMessage = true) {
    if (!wordEditor || wordIsSaving) return;

    wordIsSaving = true;
    wordIsDirty = false;

    if (wordStatus) wordStatus.textContent = 'Guardando...';
    if (wordSaveButton) wordSaveButton.classList.add('is-saving');

    try {
      const formData = new FormData();
      formData.append('_token', csrfToken);
      formData.append('action', 'save');
      formData.append('report_type', activeReportType);
      formData.append('report_title', activeReportTitle);
      formData.append('report_content', wordEditor.innerHTML);

      const response = await fetch(reportUrl, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: formData
      });

      const data = await response.json().catch(function () {
        return {};
      });

      if (!response.ok || data.ok === false) {
        throw new Error(data.message || data.error || 'No se pudo guardar el reporte.');
      }

      generatedReports[activeReportType] = {
        title: activeReportTitle,
        html: wordEditor.innerHTML
      };

      if (wordStatus) {
        wordStatus.textContent = 'Guardado ' + (data.saved_at || new Date().toLocaleTimeString());
      }

      if (showMessage) {
        showWordToast('Reporte guardado correctamente.');
      }

      window.dispatchEvent(new CustomEvent('pjd:report-saved', {
        detail: {
          type: activeReportType,
          title: activeReportTitle,
          html: wordEditor.innerHTML
        }
      }));
    } catch (error) {
      wordIsDirty = true;
      if (wordStatus) wordStatus.textContent = 'Error al guardar';
      showWordToast(error.message || 'No se pudo guardar el reporte.', true);
      throw error;
    } finally {
      wordIsSaving = false;
      if (wordSaveButton) wordSaveButton.classList.remove('is-saving');
    }
  };

  const scheduleWordSave = function () {
    wordIsDirty = true;

    if (wordStatus) {
      wordStatus.textContent = 'Cambios pendientes...';
    }

    clearTimeout(wordSaveTimer);
    wordSaveTimer = setTimeout(function () {
      saveWordReport(false).catch(function () {});
    }, 1200);
  };

  const downloadWordReport = function () {
    if (!wordEditor) return;

    const safeTitle = (activeReportTitle || 'reporte')
      .replace(/[\\/:*?"<>|]+/g, '')
      .trim()
      .replace(/\s+/g, '_');

    const documentHtml = [
      '<!DOCTYPE html>',
      '<html>',
      '<head>',
      '<meta charset="utf-8">',
      '<title>' + activeReportTitle + '</title>',
      '<style>',
      'body{font-family:Arial,sans-serif;color:#26364d;line-height:1.45;margin:40px;}',
      'h1,h2,h3{color:#1264e8;font-weight:500;}',
      'table{width:100%;border-collapse:collapse;}',
      'th,td{border:1px solid #d1d5db;padding:8px;}',
      'img{max-width:100%;height:auto;}',
      '</style>',
      '</head>',
      '<body>',
      wordEditor.innerHTML,
      '</body>',
      '</html>'
    ].join('');

    const blob = new Blob(['\ufeff', documentHtml], {
      type: 'application/msword'
    });

    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');

    anchor.href = url;
    anchor.download = (safeTitle || 'reporte') + '.doc';
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();

    URL.revokeObjectURL(url);
    showWordToast('Documento Word descargado.');
  };

  if (wordEditor) {
    wordEditor.addEventListener('input', function () {
      updateWordCount();
      scheduleWordSave();
    });

    wordEditor.addEventListener('blur', function () {
      if (wordIsDirty) {
        saveWordReport(false).catch(function () {});
      }
    });
  }

  if (wordToolbar && wordEditor) {
    wordToolbar.addEventListener('mousedown', function (event) {
      if (event.target.closest('button')) {
        event.preventDefault();
      }
    });

    wordToolbar.addEventListener('click', function (event) {
      const commandButton = event.target.closest('[data-word-cmd]');
      const actionButton = event.target.closest('[data-word-action]');

      if (commandButton) {
        wordEditor.focus();
        document.execCommand(commandButton.dataset.wordCmd, false, null);
        scheduleWordSave();
        return;
      }

      if (actionButton) {
        const action = actionButton.dataset.wordAction;
        wordEditor.focus();

        if (action === 'link') {
          const url = window.prompt('Pega la URL del enlace:');
          if (url) document.execCommand('createLink', false, url);
        }

        if (action === 'image') {
          const url = window.prompt('Pega la URL de la imagen:');
          if (url) document.execCommand('insertImage', false, url);
        }

        if (action === 'table') {
          const rows = Math.max(1, parseInt(window.prompt('Número de filas:', '3') || '3', 10));
          const columns = Math.max(1, parseInt(window.prompt('Número de columnas:', '3') || '3', 10));
          let html = '<table><tbody>';

          for (let row = 0; row < rows; row++) {
            html += '<tr>';
            for (let column = 0; column < columns; column++) {
              html += '<td>&nbsp;</td>';
            }
            html += '</tr>';
          }

          html += '</tbody></table><p><br></p>';
          document.execCommand('insertHTML', false, html);
        }

        scheduleWordSave();
      }
    });

    wordToolbar.querySelectorAll('[data-word-block]').forEach(function (select) {
      select.addEventListener('change', function () {
        wordEditor.focus();
        document.execCommand('formatBlock', false, select.value);
        scheduleWordSave();
      });
    });

    wordToolbar.querySelectorAll('[data-word-font]').forEach(function (select) {
      select.addEventListener('change', function () {
        wordEditor.focus();
        document.execCommand('fontName', false, select.value);
        scheduleWordSave();
      });
    });

    wordToolbar.querySelectorAll('[data-word-size]').forEach(function (select) {
      select.addEventListener('change', function () {
        wordEditor.focus();
        document.execCommand('fontSize', false, select.value);
        scheduleWordSave();
      });
    });

    wordToolbar.querySelectorAll('[data-word-color]').forEach(function (input) {
      input.addEventListener('input', function () {
        wordEditor.focus();
        document.execCommand(input.dataset.wordColor, false, input.value);
        scheduleWordSave();
      });
    });
  }

  wordSaveButton?.addEventListener('click', function () {
    saveWordReport(true).catch(function () {});
  });

  wordDownloadButton?.addEventListener('click', downloadWordReport);

  wordEditButton?.addEventListener('click', function () {
    if (!wordEditor) return;

    const editing = wordEditor.getAttribute('contenteditable') !== 'false';
    const nextEditing = !editing;

    wordEditor.setAttribute('contenteditable', nextEditing ? 'true' : 'false');
    wordEditButton.classList.toggle('is-off', !nextEditing);
    wordEditButton.setAttribute('aria-pressed', nextEditing ? 'true' : 'false');
    wordEditButton.setAttribute('title', nextEditing ? 'Desactivar edición' : 'Activar edición');

    if (nextEditing) {
      wordEditor.focus();
    }
  });

  wordExpandButton?.addEventListener('click', function () {
    const wrap = document.querySelector('.pjd-wrap');
    const expanded = wordModal.classList.toggle('is-expanded');

    wrap?.classList.toggle('is-report-expanded', expanded);
    wordExpandButton.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    wordExpandButton.setAttribute('title', expanded ? 'Contraer vista' : 'Hacer grande');

    window.dispatchEvent(new Event('resize'));
  });

  wordCloseButton?.addEventListener('click', closeWordEditor);

  /* El editor ya no funciona como modal con backdrop;
     por eso no se cierra al hacer clic fuera. */

  document.querySelectorAll('[data-open-generated-report]').forEach(function (button) {
    bindGeneratedItem(button);
  });

  /* Al cambiar a otra sección del análisis, cerrar el editor y dejar que
     el controlador general de pestañas active el componente seleccionado. */
  document.addEventListener('click', function (event) {
    if (!wordModal || !wordModal.classList.contains('is-open')) {
      return;
    }

    const navigationButton = event.target.closest(
      '.pjd-tab, [data-tab], [data-pane-target], [data-target-pane], [data-analysis-tab]'
    );

    if (!navigationButton || navigationButton.closest('#pjdReportEditorModal')) {
      return;
    }

    leaveWordEditorForNavigation();
  }, true);

  window.addEventListener('beforeunload', function (event) {
    if (!wordIsDirty) return;

    event.preventDefault();
    event.returnValue = '';
  });

  document.addEventListener('keydown', function (event) {
    if (
      event.key === 'Escape'
      && wordModal
      && wordModal.classList.contains('is-open')
    ) {
      event.preventDefault();
      closeWordEditor();
    }

    if (
      (event.ctrlKey || event.metaKey)
      && event.key.toLowerCase() === 's'
      && wordModal
      && wordModal.classList.contains('is-open')
    ) {
      event.preventDefault();
      saveWordReport(true).catch(function () {});
    }
  });

  window.pjdOpenReportEditor = openWordEditor;

</script>
@endpush
@endonce