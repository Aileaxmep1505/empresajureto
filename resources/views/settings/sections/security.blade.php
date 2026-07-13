<form method="POST" action="{{ route('settings.password.update') }}" class="settings-main">
  @csrf
  @method('PUT')

  <div class="settings-main-head">
    <div class="settings-head-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
    </div>
    <div>
      <h3>Seguridad</h3>
      <p>2 subsecciones</p>
    </div>
  </div>

  <div class="settings-form-scroll">
    <section class="settings-card">
      <span class="settings-card-status is-solid-green"></span>
      <div class="settings-card-header">
        <h4>Contraseña</h4>
        <span class="settings-badge">Recomendado</span>
      </div>
      <p class="settings-card-desc">Cambia tu contraseña cuando quieras reforzar la seguridad de tu cuenta.</p>

      <div class="settings-grid-full">
        <div class="settings-field">
          <label>Contraseña actual <span class="settings-required">*</span> <span class="settings-info">i</span></label>
          <input type="password" name="current_password" placeholder="Ingresa tu contraseña actual">
        </div>

        <div class="settings-grid-2">
          <div class="settings-field">
            <label>Nueva contraseña <span class="settings-required">*</span> <span class="settings-info">i</span></label>
            <input type="password" name="password" placeholder="Mínimo 8 caracteres">
          </div>

          <div class="settings-field">
            <label>Confirmar nueva contraseña <span class="settings-required">*</span> <span class="settings-info">i</span></label>
            <input type="password" name="password_confirmation" placeholder="Repite la nueva contraseña">
          </div>
        </div>

        <p class="settings-help">Usa al menos 8 caracteres con mayúsculas, minúsculas y números. Recomendamos añadir un símbolo para mayor seguridad.</p>
      </div>

      <div class="settings-divider"></div>

      <div class="settings-card-actions">
        <button type="reset" class="settings-btn settings-btn-ghost">Descartar</button>
        <button type="submit" class="settings-btn settings-btn-blue-soft">Actualizar contraseña</button>
      </div>
    </section>
  </div>
</form>
