@once
@push('styles')
<style>
  /* ==========================================================
     MATRIZ DE CUMPLIMIENTO - componente local
     Mismo sistema visual que Ficha/Eventos.
     ========================================================== */

  .pjd-pane[data-pane="matriz"] {
    padding: 16px 20px 24px;
    background: #ffffff;
  }

  .pjd-mx-shell {
    position: relative;
    width: min(100%, 1360px);
    margin: 18px auto 28px;
    padding: 22px 24px 28px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    box-shadow: 0 10px 26px rgba(15, 23, 42, .05);
    overflow: hidden;
  }

  .pjd-wrap.is-matriz-expanded .pjd-mx-shell {
    width: calc(100% - 28px);
    max-width: none;
    margin-left: 14px;
    margin-right: 14px;
  }

  .pjd-wrap.is-matriz-expanded .pjd-body {
    grid-template-columns: 0 0 minmax(360px, 1fr) !important;
  }

  .pjd-wrap.is-matriz-expanded .pjd-left,
  .pjd-wrap.is-matriz-expanded .pjd-resizer {
    opacity: 0 !important;
    pointer-events: none !important;
    overflow: hidden !important;
    border: 0 !important;
  }

  .pjd-mx-scorebar {
    width: calc(100% - 76px);
    display: flex;
    align-items: stretch;
    justify-content: flex-start;
    gap: 8px;
    padding: 0 0 18px;
    border-bottom: 1px solid rgba(15, 23, 42, .045);
    background: #ffffff;
  }

  .pjd-mx-score {
    width: 112px;
    height: 68px;
    flex: 0 0 112px;
    padding: 8px;
    border: 1px solid #edf0f4;
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(15, 23, 42, .04);
    color: #06112a;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    text-align: center;
    cursor: pointer;
    transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease, background .18s ease;
  }

  .pjd-mx-score:hover,
  .pjd-mx-score.is-active {
    border-color: #007aff;
    background: #f4f8ff;
    box-shadow: 0 8px 20px rgba(0, 122, 255, .12);
    transform: translateY(-1px);
  }

  .pjd-mx-score-value {
    color: #06112a;
    font-size: 16px;
    line-height: 1;
    font-weight: 700;
    letter-spacing: -.02em;
    text-transform: uppercase;
  }

  .pjd-mx-score-label {
    color: #64748b;
    font-size: 11px;
    line-height: 1.1;
  }

  .pjd-mx-score-icon {
    width: 18px;
    height: 18px;
    margin-top: 3px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .pjd-mx-score-icon svg {
    width: 18px;
    height: 18px;
    display: block;
  }

  .pjd-mx-score-icon.is-blue { color: #007aff; }
  .pjd-mx-score-icon.is-red { color: #ef4444; }
  .pjd-mx-score-icon.is-yellow { color: #eab308; }
  .pjd-mx-score-icon.is-green { color: #22c55e; }
  .pjd-mx-score-icon.is-orange { color: #f97316; }

  .pjd-mx-tools {
    position: absolute;
    top: 22px;
    right: 24px;
    width: 46px;
    display: grid;
    gap: 8px;
    z-index: 20;
  }

  .pjd-mx-tool {
    width: 36px;
    height: 31.5px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #ffffff;
    color: #6b7280;
    display: grid;
    place-items: center;
    cursor: pointer;
    text-decoration: none;
    box-shadow: 0 4px 10px rgba(15, 23, 42, .04);
    transition: transform .18s ease, border-color .18s ease, color .18s ease, background .18s ease;
  }

  .pjd-mx-tool:hover,
  .pjd-mx-tool.is-active {
    transform: translateY(-1px);
    border-color: #cfe0ff;
    color: #007aff;
    background: #f8fbff;
  }

  .pjd-mx-tool svg { width: 19px; height: 19px; display: block; }
  .pjd-mx-tool .pjd-mx-icon-compress { display: none; }
  .pjd-wrap.is-matriz-expanded .pjd-mx-tool[data-mx-expand] .pjd-mx-icon-expand { display: none; }
  .pjd-wrap.is-matriz-expanded .pjd-mx-tool[data-mx-expand] .pjd-mx-icon-compress { display: block; }

  .pjd-mx-comments {
    width: 100%;
    margin-top: 20px;
    border: 1px solid #d6c1ff;
    background: #f5efff;
    border-radius: 8px;
    box-shadow: 0 3px 0 rgba(124, 58, 237, .10);
    overflow: hidden;
  }

  .pjd-mx-comments-head {
    width: 100%;
    height: 50px;
    padding: 0 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    cursor: pointer;
    user-select: none;
    color: #6d28d9;
  }

  .pjd-mx-comments-title {
    margin: 0;
    color: #6d28d9;
    font-size: 13px;
    line-height: 1.2;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .pjd-mx-comments-title svg { width: 18px; height: 18px; }

  .pjd-mx-comments-body {
    display: none;
    padding: 16px 18px 18px;
    border-top: 1px solid #d6c1ff;
    color: #5b21b6;
    font-size: 14px;
    line-height: 1.65;
    font-weight: 500;
  }

  .pjd-mx-comments.is-open .pjd-mx-comments-body { display: block; }
  .pjd-mx-comments.is-open .pjd-mx-chev { transform: rotate(180deg); }

  .pjd-mx-stack {
    width: 100%;
    display: grid;
    gap: 22px;
    margin-top: 24px;
  }

  .pjd-mx-card {
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 10px 26px rgba(15, 23, 42, .04);
    overflow: hidden;
  }

  .pjd-mx-card-head {
    width: 100%;
    height: 50px;
    padding: 0 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    cursor: pointer;
    user-select: none;
    background: #ffffff;
    transition: background .18s ease;
  }

  .pjd-mx-card-head:hover { background: #fbfcfe; }

  .pjd-mx-card-title {
    margin: 0;
    color: #06112a;
    font-size: 1rem;
    line-height: 1.2;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 10px;
  }

  .pjd-mx-sparkle {
    width: 22px;
    height: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #8b35f6;
    transform: rotate(-12deg);
    flex: 0 0 auto;
  }

  .pjd-mx-sparkle svg { width: 22px; height: 22px; display: block; }

  .pjd-mx-indicators {
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .pjd-mx-indicators svg { width: 17px; height: 17px; }
  .pjd-mx-indicators .is-red { color: #ef4444; }
  .pjd-mx-indicators .is-yellow { color: #eab308; }
  .pjd-mx-indicators .is-green { color: #22c55e; }

  .pjd-mx-chev {
    width: 22px;
    height: 22px;
    color: #5f6673;
    transition: transform .18s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
  }

  .pjd-mx-chev svg { width: 18px; height: 18px; }
  .pjd-mx-card.is-open .pjd-mx-chev { transform: rotate(180deg); }

  .pjd-mx-card-body {
    display: none;
    padding: 0 24px 22px;
    border-top: 1px solid rgba(15, 23, 42, .035);
    background: #ffffff;
  }

  .pjd-mx-card.is-open .pjd-mx-card-body { display: block; }

  .pjd-mx-list {
    display: block;
    padding: 0;
    background: #ffffff;
  }

  .pjd-mx-row {
    width: 100%;
    height: auto;
    display: grid;
    grid-template-columns: minmax(0,1fr) auto 28px;
    gap: 14px;
    align-items: start;
    padding: 16px 8px 16px 0;
    margin: 0;
    border: 0;
    border-top: 1px solid rgba(15, 23, 42, .035);
    border-radius: 0;
    background: #ffffff;
    box-shadow: none;
    transform: none;
    text-align: left !important;
    transition: background .18s ease, outline .18s ease, border-color .18s ease;
  }

  .pjd-mx-row:first-child { border-top: 0; }

  .pjd-mx-row:hover {
    background: #fbfcff;
  }

  .pjd-mx-row.is-open {
    padding: 16px 14px !important;
    border: 1px solid #cfe0ff !important;
    border-radius: 10px !important;
    background: #f5f8ff !important;
    outline: none !important;
    margin: 10px 0;
  }

  .pjd-mx-row.is-open + .pjd-mx-row {
    border-top-color: transparent;
  }

  .pjd-mx-row-main {
    width: 100%;
    min-width: 0;
    text-align: left !important;
  }

  .pjd-mx-row-title {
    width: 100%;
    margin: 0 0 7px;
    color: #737373;
    font-size: 14px;
    line-height: 20px;
    font-weight: 500;
    letter-spacing: .035em;
    text-transform: uppercase;
    white-space: normal;
    word-break: normal;
    overflow-wrap: anywhere;
    display: block;
    text-align: left !important;
  }

  .pjd-mx-row-text {
    margin: 0;
    color: #020817;
    font-size: 14px;
    line-height: 20px;
    font-weight: 500;
    letter-spacing: 0;
    white-space: pre-wrap;
    text-align: left !important;
  }

  .pjd-mx-row-extra {
    display: none;
    grid-column: 1 / -1;
    margin-top: 16px;
  }

  .pjd-mx-row.is-open .pjd-mx-row-extra {
    display: block;
  }

  .pjd-mx-justificacion {
    width: 100%;
    margin: 0 0 18px;
    padding: 12px 14px;
    border: 1px solid rgba(15, 23, 42, .06);
    border-radius: 10px;
    background: rgba(255,255,255,.48);
  }

  .pjd-mx-extra-title {
    margin: 0 0 10px;
    padding-bottom: 8px;
    border-bottom: 1px solid rgba(15, 23, 42, .045);
    color: #737373;
    font-size: 14px;
    line-height: 20px;
    font-weight: 700;
    letter-spacing: .035em;
    text-transform: uppercase;
  }

  .pjd-mx-extra-text {
    margin: 0;
    color: #020817;
    font-size: 14px;
    line-height: 20px;
    font-weight: 500;
  }

  .pjd-mx-cita {
    width: 100%;
    margin: 0 0 18px;
  }

  .pjd-mx-cita-list {
    margin: 0;
    padding: 0 0 0 22px;
    color: #020817;
    font-size: 14px;
    line-height: 1.55;
    font-style: italic;
  }

  .pjd-mx-cita-list li {
    margin: 0 0 8px;
    padding-left: 4px;
  }

  .pjd-mx-cita-list li::marker {
    color: #cfd4dc;
  }

  .pjd-mx-source-box {
    width: 100%;
    min-height: 54px;
    margin: 12px 0 16px;
    padding: 12px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: rgba(255,255,255,.62);
    color: #737373;
  }

  .pjd-mx-source-box strong {
    display: block;
    margin-bottom: 8px;
    color: #737373;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .035em;
    text-transform: uppercase;
  }

  .pjd-mx-source-box a {
    color: #007aff;
    font-size: 14px;
    line-height: 20px;
    font-weight: 600;
    text-decoration: underline;
  }

  .pjd-mx-trash {
    width: 26px;
    height: 26px;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: #737373;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    opacity: .95;
    transition: background .16s ease, color .16s ease, transform .16s ease;
  }

  .pjd-mx-trash svg { width: 17px; height: 17px; }
  .pjd-mx-trash:hover { background: #fff1f1; color: #ff4a4a; }
  .pjd-mx-trash:active { transform: scale(.96); }

  @media (max-width: 1180px) {
    .pjd-mx-scorebar { width: 100%; flex-wrap: wrap; }
    .pjd-mx-tools { position: static; width: 100%; display: flex; justify-content: flex-end; margin-top: 10px; }
  }

  @media (max-width: 700px) {
    .pjd-pane[data-pane="matriz"] { padding: 12px; }
    .pjd-mx-shell { width: 100%; margin: 12px auto 20px; padding: 14px; border-radius: 16px; }
    .pjd-mx-scorebar { width: 100%; flex-direction: column; gap: 10px; }
    .pjd-mx-score { width: 100%; flex-basis: auto; height: 86px; }
    .pjd-mx-card-head { height: 66px; padding: 0 18px; }
    .pjd-mx-card-body { padding: 0 16px 18px; }
    .pjd-mx-row { grid-template-columns: minmax(0,1fr) 26px; gap: 10px; padding: 14px 4px; }
    .pjd-mx-risk { grid-column: 1 / 2; width: 86px; margin-top: 8px; }
    .pjd-mx-row-extra { grid-column: 1 / -1; }
  }
</style>
@endpush
@endonce

<div class="pjd-pane" data-pane="matriz">
  @php
    $mxClean = null;

    $mxClean = function ($value) use (&$mxClean) {
      if (is_null($value)) {
        return '';
      }

      if (is_bool($value)) {
        return $value ? 'Sí' : 'No';
      }

      if (is_scalar($value)) {
        return trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)));
      }

      if (is_object($value) && method_exists($value, '__toString')) {
        return trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)));
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
          'riesgo',
          'risk',
        ] as $key) {
          if (!array_key_exists($key, $value)) {
            continue;
          }

          $candidate = $mxClean($value[$key]);

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

          $part = $mxClean($item);

          if ($part !== '') {
            $parts[] = $part;
          }
        }

        return trim(implode(' ', array_unique($parts)));
      }

      return '';
    };

    $mxNormalizeRisk = function ($value) use ($mxClean) {
      $risk = mb_strtoupper($mxClean($value), 'UTF-8');

      return in_array($risk, ['ALTO', 'MEDIO', 'BAJO', 'NULO'], true)
        ? $risk
        : 'NULO';
    };

    $mxRawSections = data_get($sd, 'matriz.secciones', []);

    $mxSections = collect(is_array($mxRawSections) ? $mxRawSections : [])
      ->filter(fn ($section) => is_array($section))
      ->map(function ($section, $sectionIndex) use ($mxClean, $mxNormalizeRisk) {
        $rawItems = $section['items'] ?? [];

        $items = collect(is_array($rawItems) ? $rawItems : [])
          ->filter(fn ($item) => is_array($item))
          ->map(function ($item, $itemIndex) use ($mxClean, $mxNormalizeRisk) {
            return [
              'question' => $mxClean(
                $item['question']
                  ?? $item['pregunta']
                  ?? $item['requisito']
                  ?? ('Concepto ' . ($itemIndex + 1))
              ),
              'answer' => $mxClean(
                $item['answer']
                  ?? $item['respuesta']
                  ?? $item['descripcion']
                  ?? $item['value']
                  ?? 'Sin información'
              ),
              'risk' => $mxNormalizeRisk(
                $item['risk']
                  ?? $item['riesgo']
                  ?? 'NULO'
              ),
              'fuente' => $mxClean($item['fuente'] ?? $item['source'] ?? ''),
              'pagina' => $mxClean($item['pagina'] ?? $item['page'] ?? ''),
              'cita' => $mxClean($item['cita'] ?? $item['quote'] ?? ''),
            ];
          })
          ->values()
          ->all();

        $icons = collect($section['icons'] ?? [])
          ->map(fn ($icon) => mb_strtolower($mxClean($icon), 'UTF-8'))
          ->filter(fn ($icon) => in_array($icon, ['red', 'yellow', 'green'], true))
          ->values()
          ->all();

        if (empty($icons)) {
          $risks = collect($items)->pluck('risk');

          if ($risks->contains('ALTO')) {
            $icons[] = 'red';
          }

          if ($risks->contains('MEDIO')) {
            $icons[] = 'yellow';
          }

          if ($risks->contains(fn ($risk) => in_array($risk, ['BAJO', 'NULO'], true))) {
            $icons[] = 'green';
          }
        }

        return [
          'title' => $mxClean(
            $section['title']
              ?? $section['titulo']
              ?? ('Sección ' . ($sectionIndex + 1))
          ),
          'items' => $items,
          'icons' => array_values(array_unique($icons)),
        ];
      })
      ->filter(fn ($section) => !empty($section['items']))
      ->values()
      ->all();

    $mxItems = collect($mxSections)
      ->flatMap(fn ($section) => $section['items'] ?? [])
      ->values();

    $mxAlto = $mxItems->where('risk', 'ALTO')->count();
    $mxMedio = $mxItems->where('risk', 'MEDIO')->count();

    $mxBajoNulo = $mxItems
      ->filter(fn ($item) => in_array($item['risk'] ?? 'NULO', ['BAJO', 'NULO'], true))
      ->count();

    $mxGeneralRisk = $mxAlto > 0
      ? 'ALTO'
      : ($mxMedio > 0 ? 'MEDIO' : ($mxItems->isNotEmpty() ? 'BAJO' : 'NULO'));

    $mxComment = 'La matriz de cumplimiento integra '
      . $mxItems->count()
      . ' conceptos evaluables. Se detectan '
      . $mxAlto
      . ' puntos de riesgo alto, '
      . $mxMedio
      . ' puntos de riesgo medio y '
      . $mxBajoNulo
      . ' puntos con riesgo bajo o nulo. La recomendación es validar primero experiencia previa, contenido nacional, registros obligatorios, subcontratación, garantías, causales de desechamiento y documentación legal-financiera antes de avanzar con la propuesta.';

    $mxRiskClass = function ($risk) use ($mxNormalizeRisk) {
      return match($mxNormalizeRisk($risk)) {
        'ALTO' => 'is-alto',
        'MEDIO' => 'is-medio',
        'BAJO' => 'is-bajo',
        default => 'is-nulo',
      };
    };

    $mxSparkle = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.7 5.1L19 10l-5.3 1.9L12 17l-1.7-5.1L5 10l5.3-1.9L12 3Z"></path><path d="M19 4v4"></path><path d="M17 6h4"></path><path d="M5 16v4"></path><path d="M3 18h4"></path></svg>';
  @endphp

  <div class="pjd-mx-shell">
    <div class="pjd-mx-scorebar">
      <button type="button" class="pjd-mx-score is-active">
        <span class="pjd-mx-score-value">{{ $mxItems->count() }}</span>
        <span class="pjd-mx-score-label">Conceptos</span>
        <span class="pjd-mx-score-icon is-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 13h8M8 17h5"></path></svg></span>
      </button>
      <button type="button" class="pjd-mx-score">
        <span class="pjd-mx-score-value">{{ $mxAlto }}</span>
        <span class="pjd-mx-score-label">Riesgo alto</span>
        <span class="pjd-mx-score-icon is-red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 3.3 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.3a2 2 0 0 0-3.4 0Z"></path><path d="M12 9v4M12 17h.01"></path></svg></span>
      </button>
      <button type="button" class="pjd-mx-score">
        <span class="pjd-mx-score-value">{{ $mxMedio }}</span>
        <span class="pjd-mx-score-label">Riesgo medio</span>
        <span class="pjd-mx-score-icon is-yellow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5M12 16h.01"></path></svg></span>
      </button>
      <button type="button" class="pjd-mx-score">
        <span class="pjd-mx-score-value">{{ $mxBajoNulo }}</span>
        <span class="pjd-mx-score-label">Riesgo bajo/nulo</span>
        <span class="pjd-mx-score-icon is-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"></path></svg></span>
      </button>
      <button type="button" class="pjd-mx-score">
        <span class="pjd-mx-score-value">{{ $mxGeneralRisk }}</span>
        <span class="pjd-mx-score-label">Riesgo general</span>
        <span class="pjd-mx-score-icon is-red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"></path><path d="M7 16v-3M12 16V8M17 16v-5"></path></svg></span>
      </button>
    </div>

    <div class="pjd-mx-tools" aria-label="Acciones de matriz">
      <button type="button" class="pjd-mx-tool" title="Descargar matriz"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M7 10l5 5 5-5"></path><path d="M12 15V3"></path></svg></button>
      <button type="button" class="pjd-mx-tool" data-mx-expand id="pjdMxExpandBtn" title="Estirar vista" aria-label="Estirar vista" aria-expanded="false"><svg class="pjd-mx-icon-expand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"></path><path d="M9 21H3v-6"></path><path d="M21 3l-7 7"></path><path d="M3 21l7-7"></path></svg><svg class="pjd-mx-icon-compress" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3v6H3"></path><path d="M15 21v-6h6"></path><path d="M3 9l7-7"></path><path d="M21 15l-7 7"></path></svg></button>
    </div>

    <section class="pjd-mx-comments is-open">
      <div class="pjd-mx-comments-head" data-mx-toggle role="button" tabindex="0" aria-expanded="true">
        <h2 class="pjd-mx-comments-title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 13h8M8 17h5"></path></svg>Comentarios</h2>
        <span class="pjd-mx-chev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"></path></svg></span>
      </div>
      <div class="pjd-mx-comments-body">{{ $mxComment }}</div>
    </section>

    <div class="pjd-mx-stack">
      @forelse($mxSections as $section)
        <div class="pjd-mx-card">
          <div class="pjd-mx-card-head" data-mx-toggle role="button" tabindex="0" aria-expanded="false">
            <h3 class="pjd-mx-card-title">
              {{ $section['title'] ?? 'Sección sin título' }}
              <span class="pjd-mx-sparkle" aria-hidden="true">{!! $mxSparkle !!}</span>
              <span class="pjd-mx-indicators" aria-hidden="true">
                @foreach(($section['icons'] ?? []) as $ic)
                  @if($ic === 'red')
                    <svg class="is-red" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 3.3 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.3a2 2 0 0 0-3.4 0Z"></path><path d="M12 9v4M12 17h.01"></path></svg>
                  @elseif($ic === 'yellow')
                    <svg class="is-yellow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5M12 16h.01"></path></svg>
                  @else
                    <svg class="is-green" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"></path></svg>
                  @endif
                @endforeach
              </span>
            </h3>
            <span class="pjd-mx-chev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"></path></svg></span>
          </div>

          <div class="pjd-mx-card-body">
            <div class="pjd-mx-list">
              @foreach(($section['items'] ?? []) as $it)
                @php $riskClass = $mxRiskClass($it['risk'] ?? 'NULO'); @endphp
                <article class="pjd-mx-row">
                  <div class="pjd-mx-row-main">
                    <h4 class="pjd-mx-row-title">{{ mb_strtoupper($it['question'] ?? 'Sin pregunta', 'UTF-8') }}</h4>
                    <p class="pjd-mx-row-text">{!! nl2br(e($it['answer'] ?? 'Sin información')) !!}</p>
                  </div>

                  <select class="pjd-mx-risk {{ $riskClass }}" aria-label="Riesgo">
                    <option @selected(($it['risk'] ?? 'NULO') === 'ALTO')>ALTO</option>
                    <option @selected(($it['risk'] ?? 'NULO') === 'MEDIO')>MEDIO</option>
                    <option @selected(($it['risk'] ?? 'NULO') === 'BAJO')>BAJO</option>
                    <option @selected(($it['risk'] ?? 'NULO') === 'NULO')>NULO</option>
                  </select>

                  <button type="button" class="pjd-mx-trash" title="Eliminar visual" aria-label="Eliminar requisito"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6M14 11v6"></path></svg></button>

                  <div class="pjd-mx-row-extra">
                    <div class="pjd-mx-justificacion">
                      <h5 class="pjd-mx-extra-title">Justificación</h5>
                      <p class="pjd-mx-extra-text">La clasificación de riesgo se asigna de acuerdo con el impacto operativo, documental, financiero y técnico del requisito dentro de la propuesta.</p>
                    </div>

                    <div class="pjd-mx-cita">
                      <h5 class="pjd-mx-extra-title">Cita del documento</h5>
                      <ul class="pjd-mx-cita-list">
                        <li>{{ $it['cita'] ?: ($it['answer'] ?? 'Sin cita textual') }}</li>
                      </ul>
                    </div>

                    <div class="pjd-mx-source-box">
                      <strong>Fuente original</strong>
                      @if($project->documents->isNotEmpty())
                        <a href="{{ Storage::disk('public')->url($project->documents->first()->file_path) }}" target="_blank">{{ $project->documents->first()->filename ?? 'Documento base' }}</a>
                      @else
                        <span>Documento base</span>
                      @endif
                    </div>
                  </div>
                </article>
              @endforeach
            </div>
          </div>
        </div>
      @empty
        <div class="pjd-mx-empty">
          La IA todavía no ha generado la matriz de cumplimiento para los documentos de este proyecto.
        </div>
      @endforelse
    </div>
  </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-pane="matriz"] [data-mx-toggle]').forEach(function (head) {
    const block = head.closest('.pjd-mx-card, .pjd-mx-comments');
    if (!block) return;

    const toggle = function () {
      const open = block.classList.toggle('is-open');
      head.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    head.addEventListener('click', function (event) {
      if (event.target.closest('a, button, select, form')) return;
      toggle();
    });

    head.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      event.preventDefault();
      toggle();
    });
  });

  document.querySelectorAll('[data-pane="matriz"] .pjd-mx-row').forEach(function (row) {
    row.addEventListener('click', function (event) {
      if (event.target.closest('a, button, select, form')) return;
      const isOpen = row.classList.toggle('is-open');
      if (isOpen) {
        row.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      }
    });
  });

  const expandBtn = document.getElementById('pjdMxExpandBtn');
  const wrap = document.querySelector('.pjd-wrap');

  if (expandBtn && wrap) {
    expandBtn.addEventListener('click', function () {
      const expanded = wrap.classList.toggle('is-matriz-expanded');
      expandBtn.classList.toggle('is-active', expanded);
      expandBtn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      expandBtn.setAttribute('title', expanded ? 'Contraer vista' : 'Estirar vista');
      expandBtn.setAttribute('aria-label', expanded ? 'Contraer vista' : 'Estirar vista');
      window.dispatchEvent(new Event('resize'));
    });
  }
});
</script>
@endpush
@endonce
