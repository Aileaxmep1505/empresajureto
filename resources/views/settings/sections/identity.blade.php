<form method="POST" action="{{ route('settings.identity.update') }}" class="settings-main">
  @csrf
  @method('PUT')

  <div class="settings-main-head">
    <div class="settings-head-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="4" y="3" width="16" height="18" rx="2"></rect>
        <path d="M9 21v-6h6v6"></path>
      </svg>
    </div>

    <div>
      <h3>Identidad</h3>
      <p>4 subsecciones</p>
    </div>
  </div>

  <div class="settings-form-scroll">
    <section class="settings-card">
      <span class="settings-card-status is-empty"></span>
      <div class="settings-card-header">
        <h4>País de operación</h4>
        <span class="settings-badge">Requerido</span>
      </div>
      <p class="settings-card-desc">Selecciona el país donde está registrada legalmente la organización.</p>
      <div class="settings-field">
        <label>País <span class="settings-required">*</span> <span class="settings-info">i</span></label>
        <select name="country" required>
          <option value="">Selecciona un país</option>
          <option value="MX" @selected(old('country', $organization->country) === 'MX')>México</option>
          <option value="US" @selected(old('country', $organization->country) === 'US')>Estados Unidos</option>
          <option value="CA" @selected(old('country', $organization->country) === 'CA')>Canadá</option>
        </select>
      </div>
    </section>

    <section class="settings-card">
      <span class="settings-card-status is-empty"></span>
      <div class="settings-card-header">
        <h4>Identidad legal</h4>
        <span class="settings-badge">Requerido</span>
      </div>
      <p class="settings-card-desc">Datos legales que anclan documentos, reportes y permisos a tu empresa.</p>

      <div class="settings-grid-2">
        <div class="settings-field">
          <label>Tipo de organización <span class="settings-required">*</span> <span class="settings-info">i</span></label>
          <select name="organization_type" required>
            <option value="">Selecciona un tipo</option>
            <option value="sa_cv" @selected(old('organization_type', $organization->organization_type) === 'sa_cv')>S.A. de C.V.</option>
            <option value="s_de_rl" @selected(old('organization_type', $organization->organization_type) === 's_de_rl')>S. de R.L.</option>
            <option value="persona_fisica" @selected(old('organization_type', $organization->organization_type) === 'persona_fisica')>Persona física</option>
          </select>
        </div>

        <div class="settings-field">
          <label>Formato de identidad <span class="settings-required">*</span> <span class="settings-info">i</span></label>
          <input type="text" name="tax_id" value="{{ old('tax_id', $organization->tax_id) }}" placeholder="Ej. ABC010203XYZ" required>
        </div>

        <div class="settings-field">
          <label>Razón social <span class="settings-required">*</span> <span class="settings-info">i</span></label>
          <input type="text" name="legal_name" value="{{ old('legal_name', $organization->legal_name) }}" required>
        </div>

        <div class="settings-field">
          <label>Nombre comercial <span class="settings-info">i</span></label>
          <input type="text" name="trade_name" value="{{ old('trade_name', $organization->trade_name) }}">
        </div>
      </div>
    </section>

    <section class="settings-card">
      <div class="settings-card-header"><h4>Contacto institucional</h4></div>
      <p class="settings-card-desc">Canal institucional para notificaciones y comunicaciones de la empresa.</p>

      <div class="settings-grid-full">
        <div class="settings-field">
          <label>Correo institucional <span class="settings-info">i</span></label>
          <input type="email" name="institutional_email" value="{{ old('institutional_email', $organization->institutional_email) }}" placeholder="contacto@empresa.com">
        </div>

        <div class="settings-grid-2">
          <div class="settings-field">
            <label>Teléfono <span class="settings-info">i</span></label>
            <input type="text" name="institutional_phone" value="{{ old('institutional_phone', $organization->institutional_phone) }}" placeholder="+52 55 1234 5678">
          </div>

          <div class="settings-field">
            <label>Sitio web <span class="settings-info">i</span></label>
            <input type="url" name="website" value="{{ old('website', $organization->website) }}" placeholder="https://empresa.com">
          </div>
        </div>
      </div>
    </section>

    <section class="settings-card">
      <div class="settings-card-header">
        <h4>Dirección legal</h4>
        <span class="settings-badge">Requerido</span>
      </div>
      <p class="settings-card-desc">Domicilio legal o fiscal utilizado para validaciones documentales.</p>

      <div class="settings-grid-3">
        <div class="settings-field">
          <label>País <span class="settings-required">*</span> <span class="settings-info">i</span></label>
          <select name="legal_country" required>
            <option value="">Selecciona un país</option>
            <option value="MX" @selected(old('country', $organization->country) === 'MX')>México</option>
            <option value="US">Estados Unidos</option>
          </select>
        </div>

        <div class="settings-field">
          <label>Estado <span class="settings-required">*</span> <span class="settings-info">i</span></label>
          <select name="legal_state" required>
            <option value="">Ej. Ciudad de México</option>
            <option value="CDMX">Ciudad de México</option>
            <option value="JAL">Jalisco</option>
            <option value="NL">Nuevo León</option>
            <option value="COAH">Coahuila</option>
          </select>
        </div>

        <div class="settings-field">
          <label>Código postal <span class="settings-required">*</span> <span class="settings-info">i</span></label>
          <input type="text" name="postal_code" value="{{ old('postal_code', $organization->postal_code) }}" placeholder="01000" required>
        </div>
      </div>

      <div style="height:18px;"></div>

      <div class="settings-grid-full">
        <div class="settings-field">
          <label>Ciudad <span class="settings-required">*</span> <span class="settings-info">i</span></label>
          <input type="text" name="city" value="{{ old('city', $organization->city) }}" placeholder="Ciudad de México" required>
        </div>

        <div class="settings-field">
          <label>Dirección <span class="settings-required">*</span> <span class="settings-info">i</span></label>
          <textarea name="legal_address" placeholder="Av. Insurgentes Sur 123" required>{{ old('legal_address', $organization->legal_address) }}</textarea>
        </div>
      </div>
    </section>
  </div>

  <div class="settings-main-footer">
    <span class="settings-footer-note">Todo sincronizado con la identidad guardada</span>
    <div class="settings-main-footer-actions">
      <button type="reset" class="settings-btn settings-btn-ghost">Descartar</button>
      <button type="submit" class="settings-btn settings-btn-blue-soft">Guardar cambios</button>
    </div>
  </div>
</form>
