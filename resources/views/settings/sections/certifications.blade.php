<div class="settings-main">
  <div class="settings-main-head"><div class="settings-head-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg></div><div><h3>Certificaciones</h3><p>{{ $certifications->count() }} registradas</p></div></div>
  <div class="settings-form-scroll"><section class="settings-card"><div class="settings-card-header"><h4>Certificaciones</h4></div>
    @foreach($certifications as $certification)<div class="cert-card" style="margin-bottom:16px;"><div class="cert-card-head"><div><h5>{{ $certification->name }}</h5><p class="settings-help">{{ $certification->issuer }} · {{ $certification->folio }}</p></div><form method="POST" action="{{ route('settings.certifications.destroy',$certification) }}">@csrf @method('DELETE')<button class="settings-btn settings-btn-danger-text">🗑 Eliminar</button></form></div></div>@endforeach
    <form method="POST" action="{{ route('settings.certifications.store') }}" enctype="multipart/form-data" class="cert-card">@csrf
      <div class="settings-grid-2"><div class="settings-field"><label>Nombre *</label><input name="name" required></div><div class="settings-field"><label>Emisor</label><input name="issuer"></div></div><div style="height:18px"></div>
      <div class="settings-grid-3"><div class="settings-field"><label>Folio</label><input name="folio"></div><div class="settings-field"><label>Fecha de emisión</label><input type="date" name="issued_at"></div><div class="settings-field"><label>Fecha de vencimiento</label><input type="date" name="expires_at"></div></div>
      <div style="height:18px"></div><div class="settings-field"><label>Documento soporte (PDF)</label><input type="file" name="support_file" accept="application/pdf"></div><div class="settings-card-actions" style="margin-top:18px"><button class="settings-btn settings-btn-primary">+ Guardar certificación</button></div>
    </form>
  </section></div>
</div>
