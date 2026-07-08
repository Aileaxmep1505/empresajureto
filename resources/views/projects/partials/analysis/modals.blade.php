<div class="pjd-cl-popover" id="pjdClCumpPop">
  <button data-set-cumplimiento="-"><span class="pjd-cl-choice-icon pjd-cl-choice-muted"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg></span> Sin revisar</button>
  <button data-set-cumplimiento="Cumple"><span class="pjd-cl-choice-icon pjd-cl-choice-success"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 12l2.6 2.6L16.5 9"/></svg></span> Cumple</button>
  <button data-set-cumplimiento="Parcial"><span class="pjd-cl-choice-icon pjd-cl-choice-warning"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 7v6"/><path d="M12 17h.01"/></svg></span> Parcial</button>
  <button data-set-cumplimiento="No Cumple"><span class="pjd-cl-choice-icon pjd-cl-choice-danger"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6"/><path d="M9 9l6 6"/></svg></span> No Cumple</button>
</div>

<div class="pjd-cl-popover" id="pjdClStatusPop">
  <button data-set-status="Pendiente"><span class="dot" style="background:var(--warning)"></span> Pendiente</button>
  <button data-set-status="En revisión"><span class="dot" style="background:var(--blue)"></span> En revisión</button>
  <button data-set-status="Aprobado"><span class="dot" style="background:var(--success)"></span> Aprobado</button>
</div>

<div class="pjd-cl-row-menu" id="pjdClRowMenu" aria-hidden="true">
  <button type="button" class="pjd-cl-row-action" data-row-action="edit">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
    Editar
  </button>
  <button type="button" class="pjd-cl-row-action" data-row-action="duplicate">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
    Duplicar
  </button>
  <button type="button" class="pjd-cl-row-action is-danger" data-row-action="delete">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
    Eliminar
  </button>
</div>

{{-- DRAWER LATERAL: VISTA PREVIA DEL PDF CON RESALTADO --}}
<div class="pjd-doc-drawer" id="pjdDocDrawer" aria-hidden="true">
  <div class="pjd-doc-drawer-backdrop" data-drawer-close></div>
  <div class="pjd-doc-drawer-panel">
    <div class="pjd-doc-drawer-head">
      <div class="pjd-doc-drawer-file" id="pjdDrawerFile">documento.pdf</div>
      <button type="button" class="pjd-doc-drawer-toolbtn is-active" id="pjdDrawerTranscript">Transcripción</button>
      <a href="#" target="_blank" class="pjd-doc-drawer-toolbtn" id="pjdDrawerOpen">Abrir</a>
      <button type="button" class="pjd-doc-drawer-close" data-drawer-close aria-label="Cerrar">✕</button>
    </div>
    <div class="pjd-doc-drawer-nav">
      <button type="button" id="pjdPdfPrev" title="Anterior">‹</button>
      <span class="pjd-doc-drawer-pageind" id="pjdPdfPageInd">1 / 1</span>
      <button type="button" id="pjdPdfNext" title="Siguiente">›</button>
    </div>
    <div class="pjd-pdf-scroll" id="pjdPdfScroll">
      <div class="pjd-pdf-container" id="pjdPdfContainer">
        <canvas id="pjdPdfCanvas"></canvas>
        <div class="pjd-pdf-highlights" id="pjdPdfHighlights"></div>
      </div>
      <div class="pjd-pdf-loading" id="pjdPdfLoading" style="display:none;">Cargando documento…</div>
    </div>
    <div class="pjd-doc-drawer-quote" id="pjdDrawerQuote">
      <div class="pjd-doc-drawer-quote-kicker">Transcripción de la cita</div>
      <div class="pjd-doc-drawer-quote-text" id="pjdDrawerQuoteText">—</div>
      <div class="pjd-doc-drawer-quote-meta" id="pjdDrawerQuoteMeta"></div>
    </div>
  </div>
</div>
