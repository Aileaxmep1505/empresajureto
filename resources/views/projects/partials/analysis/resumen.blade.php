<div class="pjd-pane" data-pane="resumen">
        <div class="pjd-card is-open">
          <div class="pjd-card-head js-card-toggle">
            <h3>Resumen Ejecutivo <span class="sparkle"></span></h3>
            <div class="pjd-card-chev">▾</div>
          </div>
          <div class="pjd-card-body">
            @forelse($resumenEjec as $idx => $qa)
              @php
                $resumenKey = "resumen_ejecutivo.{$idx}";
                $respuestaResumen = $qa['respuesta'] ?? null;
                $preguntaResumen = $qa['pregunta'] ?? null;
                $payload = $citaPayload($citas, $resumenKey, $respuestaResumen, $preguntaResumen);
                $citaInfo = $resolverCita($citas, $resumenKey, $respuestaResumen, $preguntaResumen);
                $fuente = is_array($citaInfo) ? ($citaInfo['fuente'] ?? null) : null;
                $pagina = is_array($citaInfo) ? ($citaInfo['pagina'] ?? null) : null;
                $citaTexto = is_array($citaInfo) ? ($citaInfo['cita'] ?? null) : null;
                $docUrl = $fuente ? optional($project->documents->firstWhere('filename', $fuente))->url : null;
              @endphp
              <div class="pjd-qa {{ $payload ? 'has-cita' : 'has-no-cita' }}" @if($payload) data-cita="{{ $payload }}" @endif>
                <div class="pjd-qa-q">{{ $qa['pregunta'] ?? '' }}</div>
                <div class="pjd-qa-a">{{ $qa['respuesta'] ?? 'No se encontró información' }}</div>
                @if($payload)<div class="pjd-cita-badge"><svg class="pjd-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg> Ver fuente</div>@endif
                <div class="pjd-source-panel" hidden>
                  <div class="pjd-source-card {{ $payload ? '' : 'is-empty' }}">
                    <button type="button" class="pjd-source-close" aria-label="Cerrar fuente">✕</button>
                    <div class="pjd-source-title">{{ $payload ? 'Cita del documento' : 'Fuente no registrada' }}</div>
                    <div class="pjd-source-quote">{{ $citaTexto ?: 'No hay cita textual guardada para esta respuesta. Ya se intentó buscar por clave y por coincidencia del texto en structured_data.citas. Si sigue apareciendo así, el backend/IA no guardó evidencia específica para esta respuesta.' }}</div>
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
              </div>
            @empty
              <p style="color:var(--muted);font-size:.9rem;padding:8px;">Sin información disponible.</p>
            @endforelse
          </div>
        </div>
      </div>
