<form method="POST" action="{{ route('settings.profile.update') }}" class="settings-main">
  @csrf
  @method('PUT')

  <div class="settings-main-head">
    <div class="settings-head-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
    </div>
    <div>
      <h3>Mi perfil</h3>
      <p>3 subsecciones</p>
    </div>
  </div>

  <div class="settings-form-scroll">
    <section class="settings-card">
      <span class="settings-card-status is-solid-green"></span>
      <div class="settings-card-header">
        <h4>Información personal</h4>
        <span class="settings-badge">Requerido</span>
      </div>
      <p class="settings-card-desc">Mantén actualizados los datos con los que te identificas dentro de monico.</p>

      <div class="settings-grid-2">
        <div class="settings-field">
          <label>Nombre <span class="settings-required">*</span> <span class="settings-info">i</span></label>
          <input type="text" name="name" value="{{ old('name', $firstName) }}" required>
        </div>

        <div class="settings-field">
          <label>Apellidos <span class="settings-required">*</span> <span class="settings-info">i</span></label>
          <input type="text" name="last_name" value="{{ old('last_name', $lastName) }}" required>
        </div>
      </div>
    </section>

    <section class="settings-card">
      <span class="settings-card-status is-solid-green"></span>
      <div class="settings-card-header">
        <h4>Datos de contacto</h4>
        <span class="settings-badge">Requerido</span>
      </div>
      <p class="settings-card-desc">Usaremos estos datos para comunicaciones relacionadas con tu cuenta.</p>

      <div class="settings-grid-2">
        <div class="settings-field">
          <label>Correo electrónico <span class="settings-required">*</span> <span class="settings-info">i</span></label>
          <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>
        </div>

        <div class="settings-field">
          <label>WhatsApp <span style="color:#777;font-weight:400;">(opcional)</span> <span class="settings-info">i</span></label>
          <input type="text" name="whatsapp" value="{{ old('whatsapp', $profile->whatsapp ?? '') }}" placeholder="+52">
        </div>
      </div>
    </section>
  </div>

  <div class="settings-main-footer">
    <span class="settings-footer-note"></span>
    <div class="settings-main-footer-actions">
      <button type="reset" class="settings-btn settings-btn-ghost">Descartar</button>
      <button type="submit" class="settings-btn settings-btn-blue-soft">Guardar cambios</button>
    </div>
  </div>
</form>
