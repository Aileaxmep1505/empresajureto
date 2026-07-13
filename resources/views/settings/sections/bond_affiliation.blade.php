<form method="POST" action="#" class="settings-main">
  @csrf

  <div class="settings-main-head">
    <div class="settings-head-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
        <path d="m9 12 2 2 4-4"></path>
      </svg>
    </div>

    <div>
      <h3>Documentación de afianzadora</h3>
      <p>1 subsección</p>
    </div>
  </div>

  <div class="settings-form-scroll">
    <section class="settings-card">
      <span class="settings-card-status is-gray-line"></span>

      <div class="settings-card-header">
        <h4>Documentación de afianzadora</h4>
        <span class="bond-pill is-required">Generado</span>
      </div>

      <p class="settings-card-desc">
        Documentos generados por Murguía Consultores a partir de tu expediente completo.
      </p>

      <div class="bond-alert">
        <div class="bond-alert-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="5" y="11" width="14" height="10" rx="2"></rect>
            <path d="M8 11V7a4 4 0 0 1 8 0v4"></path>
          </svg>
        </div>

        <p>
          El Contrato de Afianzamiento Múltiple se libera cuando completes las cuatro secciones anteriores:
          Legal, Fiscal, Financiera y Comprobantes. Lo entrega Murguía Consultores una vez recibida y validada tu documentación.
        </p>
      </div>

      <div class="bond-doc-card">
        <div class="bond-doc-main">
          <div class="bond-doc-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="16" y1="13" x2="8" y2="13"></line>
              <line x1="16" y1="17" x2="8" y2="17"></line>
            </svg>
          </div>

          <div>
            <div class="bond-doc-title-row">
              <h5>Contrato de Afianzamiento Múltiple</h5>

              <span class="bond-pill is-linked">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;">
                  <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                  <path d="M8 11V7a4 4 0 0 1 8 0v4"></path>
                </svg>
                Lo entrega Murguía
              </span>

              <span class="bond-pill is-pending">○ Pendiente</span>
              <span class="settings-info">i</span>
            </div>

            <p class="bond-doc-desc">
              Documento emitido por Murguía Consultores una vez completo el expediente.
            </p>

            <p class="bond-file-meta">
              Completa las secciones anteriores para habilitar este documento.
            </p>

            <p class="bond-file-accepts">
              Acepta PDF
            </p>
          </div>
        </div>

        <div class="bond-doc-actions">
          <button type="button" class="settings-btn settings-btn-blue-soft" disabled style="opacity:.9;cursor:not-allowed;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:17px;height:17px;">
              <rect x="5" y="11" width="14" height="10" rx="2"></rect>
              <path d="M8 11V7a4 4 0 0 1 8 0v4"></path>
            </svg>
            Pendiente de recepción
          </button>
        </div>
      </div>
    </section>
  </div>
</form>
