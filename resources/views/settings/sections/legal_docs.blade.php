<div class="settings-main">
  <div class="settings-main-head"><div class="settings-head-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg></div><div><h3>Documentos legales</h3><p>2 subsecciones</p></div></div>
  <div class="settings-form-scroll">
    <section class="settings-card">
      <div class="settings-card-header"><h4>Documentos legales</h4><span class="settings-badge">Recomendado</span></div>
      <p class="settings-card-desc">Carga documentos base para validar tu expediente legal.</p>
      <div class="legal-doc-list">
        @foreach($documentDefinitions['legal_docs'] as $key => $definition)
          @include('settings.partials.document-card', ['section'=>'legal_docs','key'=>$key,'definition'=>$definition])
        @endforeach
      </div>
    </section>
    <section class="settings-card">
      <div class="settings-card-header"><h4>Representantes legales</h4><span class="settings-badge">Requerido</span></div>
      @foreach($representatives as $representative)
        <div class="legal-representative-card"><div class="legal-representative-head"><div><p class="legal-representative-name">{{ $representative->name }} <span style="color:#777;font-weight:500;">· {{ $representative->position }}</span></p></div><form method="POST" action="{{ route('settings.representatives.destroy',$representative) }}">@csrf @method('DELETE')<button class="settings-btn settings-btn-danger-text">🗑 Eliminar</button></form></div></div>
      @endforeach
      <form method="POST" action="{{ route('settings.representatives.store') }}" enctype="multipart/form-data" class="legal-representative-card">@csrf
        <div class="settings-grid-2"><div class="settings-field"><label>Nombre *</label><input name="name" required></div><div class="settings-field"><label>Cargo *</label><input name="position" required></div><div class="settings-field"><label>Identificación oficial (PDF)</label><input type="file" name="identification_file" accept="application/pdf"></div><div class="settings-field"><label>Poder legal (PDF)</label><input type="file" name="power_file" accept="application/pdf"></div></div>
        <div class="settings-card-actions" style="margin-top:18px;"><button class="settings-btn settings-btn-primary">+ Agregar representante</button></div>
      </form>
    </section>
  </div>
</div>
