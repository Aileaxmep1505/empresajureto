@php
  $doc = $documents->get($section.'.'.$key);
  $definition = $definition ?? [];
@endphp
<div class="legal-doc-item">
  <div class="legal-doc-main">
    <div class="legal-doc-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg></div>
    <div>
      <div class="legal-doc-title-row">
        <h6 class="legal-doc-title">{{ $definition['name'] }}</h6>
        @if($definition['required'] ?? false)<span class="settings-badge">Requerido</span>@endif
        @if($doc)<span class="legal-pill is-ok">✓ Completado</span><span class="legal-pill is-version">v{{ $doc->version }}</span>@else<span class="legal-pill is-pending">Pendiente</span>@endif
      </div>
      <p class="legal-doc-desc">{{ $definition['description'] ?? '' }}</p>
      @if($doc)
        <p class="legal-file-name">{{ $doc->original_name }}</p>
        <p class="legal-file-meta">{{ number_format($doc->size_bytes / 1024, 1) }} KB · Actualizado {{ $doc->updated_at->format('d/m/Y') }} · v{{ $doc->version }}</p>
      @else
        <p class="legal-empty-file">No has cargado este documento</p>
      @endif
      <p class="legal-file-accepts">Acepta PDF · máx {{ $definition['max_mb'] ?? 20 }} MB</p>
    </div>
  </div>
  <div class="legal-doc-actions">
    <form method="POST" action="{{ route('settings.documents.upload', [$section, $key]) }}" enctype="multipart/form-data">
      @csrf
      <input type="file" name="file" id="file-{{ $section }}-{{ $key }}" accept="application/pdf" hidden required onchange="this.form.submit()">
      <button type="button" class="settings-btn settings-btn-primary" onclick="document.getElementById('file-{{ $section }}-{{ $key }}').click()">{{ $doc ? '↻ Reemplazar' : '⬆ Subir archivo' }}</button>
    </form>
    @if($doc)
      <form method="POST" action="{{ route('settings.documents.destroy', $doc) }}" onsubmit="return confirm('¿Eliminar este documento?')">@csrf @method('DELETE')<button class="settings-btn settings-btn-danger-text">🗑 Eliminar</button></form>
    @endif
  </div>
</div>
