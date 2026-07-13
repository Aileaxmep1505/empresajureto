<form method="POST" action="{{ route('settings.bond.update') }}" class="settings-main special-cases-module" id="specialCasesForm">
  @csrf
  @method('PUT')

  <style>
    .special-cases-module,
    .special-cases-module * { box-sizing: border-box; }

    .special-cases-module {
      --sp-card: #ffffff;
      --sp-line: #ebebeb;
      --sp-blue: #007aff;
      --sp-blue-soft: #e6f0ff;
      font-family: 'Quicksand', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }

    .special-cases-module .sp-scroll {
      flex: 1;
      min-height: 0;
      max-height: calc(100vh - 350px);
      overflow-y: auto;
      padding: 30px 28px;
      background: #ffffff;
    }

    .special-cases-module .sp-option-card {
      min-height: 126px;
      padding: 28px 30px;
      margin-bottom: 28px;
      border: 1px solid var(--sp-line);
      border-radius: 12px;
      background: var(--sp-card);
      box-shadow: 0 4px 12px rgba(0,0,0,.02);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 24px;
      position: relative;
      transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease;
    }

    .special-cases-module .sp-option-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 18px rgba(0,0,0,.04);
    }

    .special-cases-module .sp-option-card.is-enabled {
      border-color: #b9d7ff;
      box-shadow: 0 0 0 3px rgba(0,122,255,.06);
    }

    .special-cases-module .sp-option-content {
      min-width: 0;
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .special-cases-module .sp-option-icon {
      width: 42px;
      height: 42px;
      flex: 0 0 42px;
      border-radius: 9px;
      background: #f7f7f8;
      color: #777777;
      display: grid;
      place-items: center;
    }

    .special-cases-module .sp-option-icon svg {
      width: 20px;
      height: 20px;
    }

    .special-cases-module .sp-title-row {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 9px;
    }

    .special-cases-module .sp-option-title {
      margin: 0;
      color: #222222;
      font-size: 16px;
      line-height: 1.25;
      font-weight: 700;
    }

    .special-cases-module .sp-badge {
      min-height: 24px;
      padding: 0 12px;
      border-radius: 999px;
      background: #f3f4f6;
      color: #444444;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      white-space: nowrap;
    }

    .special-cases-module .sp-option-description {
      margin: 0;
      color: #777777;
      font-size: 14px;
      line-height: 1.45;
      font-weight: 500;
    }

    .special-cases-module .sp-info-wrap {
      position: relative;
      display: inline-flex;
    }

    .special-cases-module .sp-info-button {
      width: 24px;
      height: 24px;
      padding: 0;
      border: 0;
      border-radius: 50%;
      background: #f8f8f8;
      color: #555555;
      display: inline-grid;
      place-items: center;
      cursor: pointer;
    }

    .special-cases-module .sp-info-button:hover,
    .special-cases-module .sp-info-button.is-active {
      background: var(--sp-blue-soft);
      color: var(--sp-blue);
    }

    .special-cases-module .sp-info-button svg {
      width: 17px;
      height: 17px;
    }

    .special-cases-module .sp-tooltip {
      width: 350px;
      padding: 20px 22px;
      position: absolute;
      top: 34px;
      left: -46px;
      z-index: 40;
      border: 1px solid var(--sp-line);
      border-radius: 10px;
      background: #ffffff;
      color: #444444;
      box-shadow: 0 10px 30px rgba(0,0,0,.12);
      font-size: 13px;
      line-height: 1.65;
      font-weight: 500;
      opacity: 0;
      visibility: hidden;
      transform: translateY(-5px);
      transition: opacity .16s ease, visibility .16s ease, transform .16s ease;
    }

    .special-cases-module .sp-tooltip.is-open {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .special-cases-module .sp-switch {
      width: 52px;
      height: 30px;
      flex: 0 0 52px;
      position: relative;
      display: inline-block;
    }

    .special-cases-module .sp-switch input {
      width: 1px;
      height: 1px;
      position: absolute;
      opacity: 0;
      pointer-events: none;
    }

    .special-cases-module .sp-switch-track {
      position: absolute;
      inset: 0;
      border-radius: 999px;
      background: #e4e5e7;
      cursor: pointer;
      transition: background .18s ease;
    }

    .special-cases-module .sp-switch-track::before {
      content: "";
      width: 24px;
      height: 24px;
      position: absolute;
      top: 3px;
      left: 3px;
      border-radius: 50%;
      background: #ffffff;
      box-shadow: 0 3px 10px rgba(0,0,0,.12);
      transition: transform .18s ease;
    }

    .special-cases-module .sp-switch input:checked + .sp-switch-track {
      background: var(--sp-blue);
    }

    .special-cases-module .sp-switch input:checked + .sp-switch-track::before {
      transform: translateX(22px);
    }

    .special-cases-module .sp-extra-panel {
      display: none;
      margin: -12px 0 28px;
      padding: 24px 30px;
      border: 1px solid #d7e8ff;
      border-radius: 12px;
      background: #fbfdff;
    }

    .special-cases-module .sp-extra-panel.is-visible {
      display: block;
    }

    .special-cases-module .sp-panel-title {
      margin: 0 0 6px;
      color: #111111;
      font-size: 15px;
      font-weight: 700;
    }

    .special-cases-module .sp-panel-description {
      margin: 0 0 20px;
      color: #777777;
      font-size: 13px;
      line-height: 1.45;
      font-weight: 500;
    }

    .special-cases-module .sp-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
    }

    .special-cases-module .sp-field.sp-full {
      grid-column: 1 / -1;
    }

    .special-cases-module .sp-field label {
      display: block;
      margin: 0 0 8px;
      color: #222222;
      font-size: 13px;
      font-weight: 700;
    }

    .special-cases-module .sp-field input,
    .special-cases-module .sp-field select,
    .special-cases-module .sp-field textarea {
      width: 100%;
      border: 1px solid var(--sp-line);
      border-radius: 8px;
      background: #ffffff;
      color: #333333;
      outline: none;
      font-family: inherit;
      font-size: 13px;
      font-weight: 500;
    }

    .special-cases-module .sp-field input,
    .special-cases-module .sp-field select {
      height: 42px;
      padding: 0 13px;
    }

    .special-cases-module .sp-field textarea {
      min-height: 88px;
      padding: 13px;
      line-height: 1.45;
      resize: vertical;
    }

    .special-cases-module .sp-field input:focus,
    .special-cases-module .sp-field select:focus,
    .special-cases-module .sp-field textarea:focus {
      border-color: var(--sp-blue);
      box-shadow: 0 0 0 3px var(--sp-blue-soft);
    }

    .special-cases-module .sp-required {
      color: #ff4a4a;
    }

    @media (max-width: 760px) {
      .special-cases-module .sp-scroll {
        max-height: none;
        padding: 20px 16px;
      }

      .special-cases-module .sp-option-card {
        min-height: auto;
        padding: 20px 18px;
      }

      .special-cases-module .sp-grid {
        grid-template-columns: 1fr;
      }

      .special-cases-module .sp-field.sp-full {
        grid-column: auto;
      }
    }
  </style>

  <div class="settings-main-head">
    <div class="settings-head-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
        <path d="m9 12 2 2 4-4"></path>
      </svg>
    </div>

    <div>
      <h3>Casos especiales</h3>
      <p>2 subsecciones</p>
    </div>
  </div>

  <div class="sp-scroll">
    <article class="sp-option-card" id="solidaryDebtorCard">
      <div class="sp-option-content">
        <div class="sp-option-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
          </svg>
        </div>

        <div>
          <div class="sp-title-row">
            <h4 class="sp-option-title">Obligado solidario</h4>
            <span class="sp-badge">Opcional</span>

            <span class="sp-info-wrap">
              <button type="button" class="sp-info-button" data-tooltip-button="solidaryDebtorTooltip" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="9"></circle>
                  <path d="M12 11v5"></path>
                  <path d="M12 8h.01"></path>
                </svg>
              </button>

              <span class="sp-tooltip" id="solidaryDebtorTooltip">
                Si otra empresa actuará como obligado solidario, registra aquí sus datos básicos.
                El expediente completo se gestionará en una siguiente iteración.
              </span>
            </span>
          </div>

          <p class="sp-option-description">
            Registra empresas que asumirán responsabilidad solidaria en la solicitud.
          </p>
        </div>
      </div>

      <label class="sp-switch">
        <input type="checkbox" name="has_solidary_debtor" id="solidaryDebtorSwitch" value="1" @checked(old('has_solidary_debtor', $bondSetting->has_solidary_debtor))>
        <span class="sp-switch-track"></span>
      </label>
    </article>

    <section class="sp-extra-panel" id="solidaryDebtorPanel">
      <h4 class="sp-panel-title">Datos básicos del obligado solidario</h4>
      <p class="sp-panel-description">Registra la información principal de la empresa que asumirá la responsabilidad solidaria.</p>

      <div class="sp-grid">
        <div class="sp-field">
          <label for="solidary_business_name">Razón social <span class="sp-required">*</span></label>
          <input type="text" id="solidary_business_name" name="solidary_business_name" value="{{ old('solidary_business_name', $bondSetting->solidary_business_name) }}" placeholder="Nombre legal de la empresa">
        </div>

        <div class="sp-field">
          <label for="solidary_tax_id">RFC <span class="sp-required">*</span></label>
          <input type="text" id="solidary_tax_id" name="solidary_tax_id" value="{{ old('solidary_tax_id', $bondSetting->solidary_tax_id) }}" placeholder="Ej. ABC010203XYZ">
        </div>

        <div class="sp-field">
          <label for="solidary_representative">Representante legal</label>
          <input type="text" id="solidary_representative" name="solidary_representative" value="{{ old('solidary_representative', $bondSetting->solidary_representative) }}" placeholder="Nombre completo">
        </div>

        <div class="sp-field">
          <label for="solidary_phone">Teléfono</label>
          <input type="text" id="solidary_phone" name="solidary_phone" value="{{ old('solidary_phone', $bondSetting->solidary_phone) }}" placeholder="+52 55 1234 5678">
        </div>
      </div>
    </section>

    <article class="sp-option-card" id="realEstateCard">
      <div class="sp-option-content">
        <div class="sp-option-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m3 11 9-8 9 8"></path>
            <path d="M5 10v10h14V10"></path>
            <path d="M9 20v-6h6v6"></path>
          </svg>
        </div>

        <div>
          <div class="sp-title-row">
            <h4 class="sp-option-title">Garantía con bienes inmuebles</h4>
            <span class="sp-badge">Opcional</span>
          </div>

          <p class="sp-option-description">
            Registra los inmuebles que respaldan la fianza. Cada inmueble requiere su propio paquete documental.
          </p>
        </div>
      </div>

      <label class="sp-switch">
        <input type="checkbox" name="has_real_estate_guarantee" id="realEstateSwitch" value="1" @checked(old('has_real_estate_guarantee', $bondSetting->has_real_estate_guarantee))>
        <span class="sp-switch-track"></span>
      </label>
    </article>

    <section class="sp-extra-panel" id="realEstatePanel">
      <h4 class="sp-panel-title">Datos básicos del inmueble</h4>
      <p class="sp-panel-description">Captura la información inicial de la propiedad que respaldará la fianza.</p>

      <div class="sp-grid">
        <div class="sp-field">
          <label for="property_type">Tipo de inmueble <span class="sp-required">*</span></label>
          <select id="property_type" name="property_type">
            <option value="">Selecciona una opción</option>
            <option value="house" {{ old('property_type') === 'house' ? 'selected' : '' }}>Casa</option>
            <option value="commercial" {{ old('property_type') === 'commercial' ? 'selected' : '' }}>Local comercial</option>
            <option value="land" {{ old('property_type') === 'land' ? 'selected' : '' }}>Terreno</option>
            <option value="warehouse" {{ old('property_type') === 'warehouse' ? 'selected' : '' }}>Bodega</option>
            <option value="other" {{ old('property_type') === 'other' ? 'selected' : '' }}>Otro</option>
          </select>
        </div>

        <div class="sp-field">
          <label for="property_value">Valor estimado</label>
          <input type="number" id="property_value" name="property_value" value="{{ old('property_value', $bondSetting->property_value) }}" min="0" step="0.01" placeholder="$0.00">
        </div>

        <div class="sp-field sp-full">
          <label for="property_address">Dirección del inmueble <span class="sp-required">*</span></label>
          <textarea id="property_address" name="property_address" placeholder="Calle, número, colonia, municipio, estado y código postal">{{ old('property_address', $bondSetting->property_address) }}</textarea>
        </div>
      </div>
    </section>
  <div class="settings-card-actions" style="margin-top:18px"><button class="settings-btn settings-btn-primary">Guardar casos especiales</button></div>
</div>
</form>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const module = document.querySelector('.special-cases-module');
    if (!module) return;

    const tooltipButtons = module.querySelectorAll('[data-tooltip-button]');

    function closeTooltips(exceptId = null) {
      tooltipButtons.forEach(function (button) {
        const tooltipId = button.getAttribute('data-tooltip-button');
        const tooltip = document.getElementById(tooltipId);

        if (!tooltip || tooltipId === exceptId) return;

        tooltip.classList.remove('is-open');
        button.classList.remove('is-active');
        button.setAttribute('aria-expanded', 'false');
      });
    }

    tooltipButtons.forEach(function (button) {
      button.addEventListener('click', function (event) {
        event.stopPropagation();

        const tooltipId = button.getAttribute('data-tooltip-button');
        const tooltip = document.getElementById(tooltipId);
        if (!tooltip) return;

        const willOpen = !tooltip.classList.contains('is-open');
        closeTooltips(tooltipId);
        tooltip.classList.toggle('is-open', willOpen);
        button.classList.toggle('is-active', willOpen);
        button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      });
    });

    document.addEventListener('click', function (event) {
      if (!event.target.closest('.sp-info-wrap')) closeTooltips();
    });

    function configureSwitch(switchId, panelId, cardId) {
      const input = document.getElementById(switchId);
      const panel = document.getElementById(panelId);
      const card = document.getElementById(cardId);
      if (!input || !panel || !card) return;

      function updateState() {
        const enabled = input.checked;
        panel.classList.toggle('is-visible', enabled);
        card.classList.toggle('is-enabled', enabled);

        panel.querySelectorAll('input, select, textarea').forEach(function (field) {
          field.disabled = !enabled;
        });
      }

      input.addEventListener('change', updateState);
      updateState();
    }

    configureSwitch('solidaryDebtorSwitch', 'solidaryDebtorPanel', 'solidaryDebtorCard');
    configureSwitch('realEstateSwitch', 'realEstatePanel', 'realEstateCard');
  });
</script>
