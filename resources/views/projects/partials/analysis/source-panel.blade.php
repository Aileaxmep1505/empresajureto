@props([
  'payload' => null,
  'citaTexto' => null,
  'fuente' => null,
  'pagina' => null,
  'docUrl' => null,
  'emptyMessage' => 'No hay cita textual guardada para este dato. Ya se intentó buscar por clave y por coincidencia del texto en structured_data.citas. Si sigue apareciendo así, el backend/IA no guardó evidencia específica para este valor.',
])

<div class="pjd-source-panel" hidden>
  <div class="pjd-source-card {{ $payload ? '' : 'is-empty' }}">
    <button type="button" class="pjd-source-close" aria-label="Cerrar fuente">✕</button>
    <div class="pjd-source-title">{{ $payload ? 'Cita del documento' : 'Fuente no registrada' }}</div>
    <div class="pjd-source-quote">{{ $citaTexto ?: $emptyMessage }}</div>
    <div class="pjd-source-meta">
      <strong>Fuente:</strong>
      <span>{{ $fuente ?: 'Sin archivo fuente registrado' }}</span>
      @if($pagina)<span> · Página {{ $pagina }}</span>@endif
    </div>
    <div class="pjd-source-actions">
      @if($payload)<button type="button" class="pjd-source-btn js-open-cita" data-cita="{{ $payload }}">Ver cita</button>@endif
      @if($docUrl)<a href="{{ $docUrl }}" target="_blank" class="pjd-source-btn is-ghost">Ver documento</a>@endif
    </div>
  </div>
</div>
