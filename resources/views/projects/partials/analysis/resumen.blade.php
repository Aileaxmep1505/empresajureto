<div class="pjd-pane" data-pane="resumen">
  @php
    /*
    |--------------------------------------------------------------------------
    | Conversor seguro para valores del resumen ejecutivo
    |--------------------------------------------------------------------------
    | Evita errores cuando la IA devuelve arreglos u objetos en lugar de texto.
    */
    $resumenClean = null;

    $resumenClean = function ($value) use (&$resumenClean) {
      if (is_null($value)) {
        return '';
      }

      if (is_bool($value)) {
        return $value ? 'Sí' : 'No';
      }

      if (is_scalar($value)) {
        return trim(
          preg_replace(
            '/\s+/u',
            ' ',
            strip_tags((string) $value)
          )
        );
      }

      if (is_object($value) && method_exists($value, '__toString')) {
        return trim(
          preg_replace(
            '/\s+/u',
            ' ',
            strip_tags((string) $value)
          )
        );
      }

      if (is_object($value)) {
        $value = (array) $value;
      }

      if (is_array($value)) {
        foreach ([
          'respuesta',
          'answer',
          'valor',
          'value',
          'texto',
          'descripcion',
          'description',
          'pregunta',
          'question',
          'titulo',
          'title',
          'nombre',
          'label',
          'content',
        ] as $key) {
          if (!array_key_exists($key, $value)) {
            continue;
          }

          $candidate = $resumenClean($value[$key]);

          if ($candidate !== '') {
            return $candidate;
          }
        }

        $parts = [];

        foreach ($value as $key => $item) {
          if (in_array((string) $key, [
            'fuente',
            'pagina',
            'cita',
            'source',
            'page',
            'quote',
            'metadata',
          ], true)) {
            continue;
          }

          $part = $resumenClean($item);

          if ($part !== '') {
            $parts[] = $part;
          }
        }

        return trim(implode(' ', array_unique($parts)));
      }

      return '';
    };

    $resumenRows = collect(is_array($resumenEjec ?? null) ? $resumenEjec : [])
      ->map(function ($qa, $idx) use ($resumenClean) {
        if (!is_array($qa)) {
          return [
            'key' => "resumen_ejecutivo.{$idx}",
            'pregunta' => 'Resumen ejecutivo',
            'respuesta' => $resumenClean($qa),
          ];
        }

        return [
          'key' => "resumen_ejecutivo.{$idx}",
          'pregunta' => $resumenClean(
            $qa['pregunta']
              ?? $qa['question']
              ?? $qa['titulo']
              ?? $qa['title']
              ?? 'Resumen ejecutivo'
          ),
          'respuesta' => $resumenClean(
            $qa['respuesta']
              ?? $qa['answer']
              ?? $qa['valor']
              ?? $qa['value']
              ?? $qa['descripcion']
              ?? $qa
          ),
        ];
      })
      ->filter(function ($row) {
        return ($row['pregunta'] ?? '') !== ''
          || ($row['respuesta'] ?? '') !== '';
      })
      ->values();
  @endphp

  <div class="pjd-card is-open">
    <div class="pjd-card-head js-card-toggle">
      <h3>
        Resumen Ejecutivo
        <span class="sparkle"></span>
      </h3>

      <div class="pjd-card-chev">▾</div>
    </div>

    <div class="pjd-card-body">
      @forelse($resumenRows as $idx => $qa)
        @php
          $resumenKey = $qa['key'] ?? "resumen_ejecutivo.{$idx}";
          $preguntaResumen = $qa['pregunta'] ?? 'Resumen ejecutivo';
          $respuestaResumen = $qa['respuesta'] ?? 'No se encontró información';

          $payload = $citaPayload(
            $citas,
            $resumenKey,
            $respuestaResumen,
            $preguntaResumen
          );

          $citaInfo = $resolverCita(
            $citas,
            $resumenKey,
            $respuestaResumen,
            $preguntaResumen
          );

          $fuente = is_array($citaInfo)
            ? $resumenClean($citaInfo['fuente'] ?? null)
            : '';

          $pagina = is_array($citaInfo)
            ? $resumenClean($citaInfo['pagina'] ?? null)
            : '';

          $citaTexto = is_array($citaInfo)
            ? $resumenClean($citaInfo['cita'] ?? null)
            : '';

          $docUrl = $fuente !== ''
            ? optional(
                $project->documents->firstWhere('filename', $fuente)
              )->url
            : null;
        @endphp

        <div
          class="pjd-qa {{ $payload ? 'has-cita' : 'has-no-cita' }}"
          @if($payload)
            data-cita="{{ $payload }}"
          @endif
        >
          <div class="pjd-qa-q">
            {{ $preguntaResumen !== '' ? $preguntaResumen : 'Resumen ejecutivo' }}
          </div>

          <div class="pjd-qa-a">
            {{ $respuestaResumen !== '' ? $respuestaResumen : 'No se encontró información' }}
          </div>

          @if($payload)
            <div class="pjd-cita-badge">
              <svg
                class="pjd-svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <path d="M14 2v6h6"/>
              </svg>

              Ver fuente
            </div>
          @endif

          <div class="pjd-source-panel" hidden>
            <div class="pjd-source-card {{ $payload ? '' : 'is-empty' }}">
              <button
                type="button"
                class="pjd-source-close"
                aria-label="Cerrar fuente"
              >
                ✕
              </button>

              <div class="pjd-source-title">
                {{ $payload ? 'Cita del documento' : 'Fuente no registrada' }}
              </div>

              <div class="pjd-source-quote">
                {{
                  $citaTexto !== ''
                    ? $citaTexto
                    : 'No hay cita textual guardada para esta respuesta. Ya se intentó buscar por clave y por coincidencia del texto en structured_data.citas. Si sigue apareciendo así, el backend/IA no guardó evidencia específica para esta respuesta.'
                }}
              </div>

              <div class="pjd-source-meta">
                <strong>Fuente:</strong>

                <span>
                  {{
                    $fuente !== ''
                      ? $fuente
                      : 'Sin archivo fuente registrado'
                  }}
                </span>

                @if($pagina !== '')
                  <span>· Página {{ $pagina }}</span>
                @endif
              </div>

              <div class="pjd-source-actions">
                @if($payload)
                  <button
                    type="button"
                    class="pjd-source-btn js-open-cita"
                    data-cita="{{ $payload }}"
                  >
                    Ver cita
                  </button>
                @endif

                @if($docUrl)
                  <a
                    href="{{ $docUrl }}"
                    target="_blank"
                    class="pjd-source-btn is-ghost"
                    rel="noopener noreferrer"
                  >
                    Ver documento
                  </a>
                @endif
              </div>
            </div>
          </div>
        </div>
      @empty
        <p style="color:var(--muted);font-size:.9rem;padding:8px;">
          Sin información disponible.
        </p>
      @endforelse
    </div>
  </div>
</div>
