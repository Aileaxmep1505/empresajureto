@once
@push('styles')
<style>
  /* ==========================================================
     EVENTOS COMPONENTE LOCAL
     Mismo estilo base que Ficha. Todo vive en este partial.
     ========================================================== */

  .pjd-pane[data-pane="eventos"] {
    padding: 16px 20px 24px;
    background: #ffffff;
  }

  .pjd-events-shell {
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

  .pjd-wrap.is-eventos-expanded .pjd-events-shell {
    width: calc(100% - 28px);
    max-width: none;
    margin-left: 14px;
    margin-right: 14px;
  }

  .pjd-wrap.is-eventos-expanded .pjd-body {
    grid-template-columns: 0 0 minmax(360px, 1fr) !important;
  }

  .pjd-wrap.is-eventos-expanded .pjd-left,
  .pjd-wrap.is-eventos-expanded .pjd-resizer {
    opacity: 0 !important;
    pointer-events: none !important;
    overflow: hidden !important;
    border: 0 !important;
  }

  /* ===== METRICAS SUPERIORES ===== */
  .pjd-events-scorebar {
    width: calc(100% - 76px);
    display: flex;
    align-items: stretch;
    justify-content: flex-start;
    gap: 8px;
    padding: 0 0 18px;
    border-bottom: 1px solid rgba(15, 23, 42, .045);
    background: #ffffff;
  }

  .pjd-events-score {
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

  .pjd-events-score:hover,
  .pjd-events-score.is-active {
    border-color: #007aff;
    background: #f4f8ff;
    box-shadow: 0 8px 20px rgba(0, 122, 255, .12);
    transform: translateY(-1px);
  }

  .pjd-events-score-value {
    color: #06112a;
    font-size: 16px;
    line-height: 1;
    font-weight: 700;
    letter-spacing: -.02em;
    text-transform: uppercase;
  }

  .pjd-events-score-label {
    color: #64748b;
    font-size: 11px;
    line-height: 1.1;
  }

  .pjd-events-score-icon,
  .pjd-events-score-icon svg {
    width: 18px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .pjd-events-score-icon { margin-top: 3px; flex: 0 0 auto; }
  .pjd-events-score-icon.is-blue { color: #007aff; }
  .pjd-events-score-icon.is-red { color: #ef4444; }
  .pjd-events-score-icon.is-yellow { color: #eab308; }
  .pjd-events-score-icon.is-green { color: #22c55e; }
  .pjd-events-score-icon.is-orange { color: #f59e0b; }

  /* ===== BOTONES DERECHA ===== */
  .pjd-events-tools {
    position: absolute;
    top: 22px;
    right: 24px;
    width: 46px;
    display: grid;
    gap: 8px;
    z-index: 20;
  }

  .pjd-events-tool {
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

  .pjd-events-tool:hover,
  .pjd-events-tool.is-active {
    transform: translateY(-1px);
    border-color: #cfe0ff;
    color: #007aff;
    background: #f8fbff;
  }

  .pjd-events-tool:active { transform: scale(.98); }
  .pjd-events-tool svg { width: 19px; height: 19px; display: block; }
  .pjd-events-tool .pjd-events-icon-compress { display: none; }
  .pjd-wrap.is-eventos-expanded .pjd-events-tool[data-events-expand] .pjd-events-icon-expand { display: none; }
  .pjd-wrap.is-eventos-expanded .pjd-events-tool[data-events-expand] .pjd-events-icon-compress { display: block; }

  /* ===== COMENTARIOS ===== */
  .pjd-events-comments {
    width: 100%;
    margin-top: 20px;
    border: 1px solid #d6c1ff;
    background: #f5efff;
    border-radius: 8px;
    box-shadow: 0 3px 0 rgba(124, 58, 237, .10);
    overflow: hidden;
  }

  .pjd-events-comments-head {
    width: 100%;
    height: 48px;
    padding: 0 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    cursor: pointer;
    user-select: none;
    border-bottom: 1px solid #d6c1ff;
  }

  .pjd-events-comments-title {
    margin: 0;
    color: #6d28d9;
    font-size: 1rem;
    line-height: 1.15;
    font-weight: 700;
    letter-spacing: .035em;
    text-transform: uppercase;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .pjd-events-comments-title svg { width: 17px; height: 17px; }

  .pjd-events-chev {
    width: 20px;
    height: 20px;
    color: #6d28d9;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform .18s ease;
  }

  .pjd-events-comments.is-open .pjd-events-chev,
  .pjd-events-card.is-open .pjd-events-card-chev { transform: rotate(180deg); }

  .pjd-events-comments-body {
    display: none;
    padding: 18px 24px 22px;
    color: #5b21b6;
    font-size: 14px;
    line-height: 20px;
    font-weight: 500;
  }

  .pjd-events-comments.is-open .pjd-events-comments-body { display: block; }
  .pjd-events-comments-body p { margin: 0; }
  .pjd-events-comments-body strong { color: #6d28d9; font-weight: 800; }

  /* ===== ACORDEONES ===== */
  .pjd-events-stack {
    width: 100%;
    display: grid;
    gap: 22px;
    margin-top: 24px;
  }

  .pjd-events-card {
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 10px 26px rgba(15, 23, 42, .04);
    overflow: hidden;
  }

  .pjd-events-card-head {
    width: 100%;
    height: 50px;
    padding: 0 28px;
    border-bottom: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    cursor: pointer;
    user-select: none;
    background: #ffffff;
    transition: background .18s ease;
  }

  .pjd-events-card-head:hover { background: #fbfcfe; }

  .pjd-events-card-title {
    margin: 0;
    color: #06112a;
    font-size: 1rem;
    line-height: 1.2;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 10px;
  }

  .pjd-events-sparkle {
    width: 22px;
    height: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #8b35f6;
    transform: rotate(-12deg);
    flex: 0 0 auto;
  }

  .pjd-events-sparkle svg { width: 22px; height: 22px; display: block; }

  .pjd-events-card-chev {
    width: 22px;
    height: 22px;
    color: #5f6673;
    transition: transform .18s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
  }

  .pjd-events-card-chev svg { width: 18px; height: 18px; }

  .pjd-events-card.is-open .pjd-events-card-head {
    border-bottom: 1px solid rgba(15, 23, 42, .035);
  }

  .pjd-events-card-body {
    display: none;
    padding: 0 24px 22px;
    border-top: 0;
    background: #ffffff;
  }

  .pjd-events-card.is-open .pjd-events-card-body { display: block; }

  .pjd-events-list {
    display: block;
    padding: 0;
    background: #ffffff;
  }

  .pjd-events-row {
    width: 100%;
    display: grid;
    grid-template-columns: minmax(0,1fr) 28px;
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
    transition: background .18s ease, outline .18s ease;
  }

  .pjd-events-row:first-child { border-top: 0; }

  .pjd-events-row:hover {
    background: #f5f8ff;
    border-radius: 10px;
    outline: 1px solid #d9e8ff;
    outline-offset: -1px;
    padding-left: 12px;
    padding-right: 12px;
  }

  .pjd-events-row-main { width: 100%; min-width: 0; }

  .pjd-events-row-title {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin: 0 0 7px;
  }

  .pjd-events-row h4 {
    margin: 0;
    color: #737373;
    font-size: 14px;
    line-height: 20px;
    font-weight: 500;
    letter-spacing: .035em;
    text-transform: uppercase;
    white-space: pre-wrap;
    word-break: break-word;
  }

  .pjd-events-row p {
    margin: 0;
    color: #020817;
    font-size: 14px;
    line-height: 20px;
    font-weight: 500;
    letter-spacing: 0;
    white-space: pre-wrap;
  }

  .pjd-events-risk-select {
    width: auto;
    height: 28px;
    padding: 0 30px 0 10px;
    border: 1px solid #e5e7eb;
    border-radius: 7px;
    background: #f6f6f7;
    color: #737373;
    font-size: 11px;
    line-height: 1;
    font-weight: 700;
    letter-spacing: .02em;
    text-transform: uppercase;
    outline: none;
    cursor: pointer;
  }

  .pjd-events-risk-select.is-medium {
    border-color: #fde68a;
    background: #fffbea;
    color: #8a5a00;
  }

  .pjd-events-risk-select:focus {
    border-color: #007aff;
    box-shadow: 0 0 0 3px #e6f0ff;
  }

  .pjd-events-trash {
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

  .pjd-events-trash svg { width: 17px; height: 17px; }
  .pjd-events-trash:hover { background: #fff1f1; color: #ff4a4a; }
  .pjd-events-trash:active { transform: scale(.96); }

  .pjd-events-empty {
    width: 100%;
    padding: 14px 0;
    color: #737373;
    font-size: 14px;
    line-height: 20px;
    font-weight: 500;
  }

  /* Misma escala visual de Ficha: tipografia compacta, separador suave y cuerpo limpio */
  .pjd-events-list .pjd-events-row + .pjd-events-row {
    border-top-color: rgba(15, 23, 42, .045);
  }

  .pjd-events-card.is-open .pjd-events-card-body,
  .pjd-events-comments.is-open .pjd-events-comments-body {
    animation: none;
  }

  @media (max-width: 1180px) {
    .pjd-events-scorebar { width: 100%; flex-wrap: wrap; }
    .pjd-events-tools { position: static; width: 100%; display: flex; justify-content: flex-end; margin-top: 10px; }
  }

  @media (max-width: 700px) {
    .pjd-pane[data-pane="eventos"] { padding: 12px; }
    .pjd-events-shell { width: 100%; margin: 12px auto 20px; padding: 14px; border-radius: 16px; }
    .pjd-events-scorebar { width: 100%; flex-direction: column; gap: 10px; }
    .pjd-events-score { width: 100%; flex-basis: auto; height: 86px; }
    .pjd-events-comments-head { height: auto; padding: 14px 16px; }
    .pjd-events-comments-body { padding: 20px 16px; }
    .pjd-events-card-head { height: 66px; padding: 0 18px; }
    .pjd-events-card-title { font-size: 1.02rem; }
    .pjd-events-card-body { padding: 0 16px 18px; }
    .pjd-events-row { grid-template-columns: minmax(0,1fr) 26px; gap: 10px; padding: 14px 4px; }
  }
</style>
@endpush
@endonce

<div class="pjd-pane" data-pane="eventos">
  @php
    $eventClean = function ($value) {
      if (is_null($value)) return null;
      if (is_array($value) || is_object($value)) {
        $value = collect((array) $value)->filter()->implode(' ');
      }
      $value = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)));
      return $value !== '' ? $value : null;
    };

    $commentsText = $eventClean(data_get($sd, 'eventos.comentarios')) ?: 'Tras la evaluación de los plazos de ejecución y vigencias de la licitación, se concluye un dictamen NULO debido a que actualmente no existen reglas de tolerancia operativa configuradas en el perfil de la empresa para realizar el cruce de viabilidad. Se identificaron diversos plazos operativos, como los 40 días naturales para la entrega de bienes, 60 días naturales de vigencia del contrato y plazos administrativos ajustados como los 2 días hábiles para la entrega de documentación tras el fallo. Al carecer de parámetros internos, el impacto práctico recae enteramente en la capacidad de las áreas operativas para confirmar si estos tiempos son manejables. Se recomienda revisar manualmente estos plazos con las áreas de operaciones y finanzas para tomar una decisión final sobre la participación.';

    $vigenciasRows = collect(data_get($sd, 'eventos.vigencias', []));
    if ($vigenciasRows->isEmpty()) {
      $vigenciasRows = collect([
        ['label' => 'Vigencia del contrato', 'risk' => 'MEDIO', 'value' => '60 días naturales a partir del día siguiente a la notificación del fallo'],
        ['label' => 'Vigencia de precios', 'risk' => 'NULO', 'value' => 'Hasta el cumplimiento total del contrato'],
        ['label' => 'Garantía para responder de defectos y vicios ocultos', 'risk' => 'NULO', 'value' => '12 meses'],
        ['label' => 'Vigencia de las proposiciones (técnica y económica)', 'risk' => 'NULO', 'value' => '90 días naturales a partir de la presentación de la misma'],
        ['label' => 'Opinión de cumplimiento de obligaciones fiscales en materia de seguridad social', 'risk' => 'NULO', 'value' => 'No mayor a 30 días'],
        ['label' => 'Opinión del cumplimiento de obligaciones fiscales (SAT)', 'risk' => 'NULO', 'value' => 'Vigente (Resolución Miscelánea Fiscal para 2026)'],
        ['label' => 'Vigencia de la contratación (ejercicio fiscal)', 'risk' => 'NULO', 'value' => 'Ejercicio fiscal 2026'],
      ]);
    }

    $plazosRows = collect(data_get($sd, 'eventos.plazos_ejecucion', []));
    if ($plazosRows->isEmpty()) {
      $plazosRows = collect([
        ['label' => 'Entrega de bienes', 'risk' => 'NULO', 'value' => '40 días naturales naturales - A partir del día siguiente a la notificación del fallo'],
        ['label' => 'Pago por los bienes entregados', 'risk' => 'NULO', 'value' => '17 días hábiles hábiles - A partir de la entrega de la documentación soporte completa y correcta (CFDI)'],
        ['label' => 'Presentación de Fianza de Cumplimiento', 'risk' => 'NULO', 'value' => '10 días naturales naturales - Siguientes a la firma del contrato'],
        ['label' => 'Notificación de monto de Pena Convencional por parte de CAPUFE', 'risk' => 'NULO', 'value' => '15 días hábiles hábiles - Posterior al atraso en el cumplimiento de la obligación de que se trate'],
        ['label' => 'Sustitución de bienes defectuosos o que no cumplan especificaciones', 'risk' => 'NULO', 'value' => '3 días hábiles hábiles - A partir de la notificación del incumplimiento'],
        ['label' => 'Devolución de comprobantes fiscales con errores', 'risk' => 'NULO', 'value' => '3 días hábiles hábiles - Siguientes al de su recepción'],
        ['label' => 'Cancelación y reexpedición de CFDI por trámite de pago', 'risk' => 'NULO', 'value' => '24 horas no especificado - A partir de la obligación de notificar la cancelación'],
        ['label' => 'Entrega de documentación para formalización de contrato', 'risk' => 'NULO', 'value' => '2 días hábiles hábiles - A partir de la emisión del fallo'],
        ['label' => 'Suspensión de entrega de bienes (causal de rescisión)', 'risk' => 'NULO', 'value' => 'mayor a 3 días naturales naturales - Periodo de suspensión injustificada'],
        ['label' => 'Plazo para exponer defensa ante procedimiento de rescisión', 'risk' => 'NULO', 'value' => '5 días hábiles hábiles - A partir de la notificación por escrito del incumplimiento'],
        ['label' => 'Determinación de rescisión del contrato por la convocante', 'risk' => 'NULO', 'value' => '10 días hábiles hábiles - Transcurrido el término para la exposición de pruebas del proveedor'],
      ]);
    }

    $allRows = $vigenciasRows->merge($plazosRows);
    $detectedEvents = $allRows->count();
    $highRisk = $allRows->filter(fn($r) => strtoupper((string)($r['risk'] ?? '')) === 'ALTO')->count();
    $mediumRisk = $allRows->filter(fn($r) => strtoupper((string)($r['risk'] ?? '')) === 'MEDIO')->count();
    $lowRisk = $allRows->filter(fn($r) => in_array(strtoupper((string)($r['risk'] ?? 'NULO')), ['BAJO', 'NULO', 'BAJO/NULO'], true))->count();
    $generalRisk = $highRisk > 0 ? 'ALTO' : ($mediumRisk > 0 ? 'MEDIO' : 'NULO');
  @endphp

  <div class="pjd-events-shell">
    <div class="pjd-events-scorebar">
      <button type="button" class="pjd-events-score is-active">
        <span class="pjd-events-score-value">{{ $detectedEvents }}</span>
        <span class="pjd-events-score-label">Conceptos</span>
        <span class="pjd-events-score-icon is-blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 13h8M8 17h5"></path></svg>
        </span>
      </button>

      <button type="button" class="pjd-events-score">
        <span class="pjd-events-score-value">{{ $highRisk }}</span>
        <span class="pjd-events-score-label">Riesgo alto</span>
        <span class="pjd-events-score-icon is-red">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.3 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.3a2 2 0 0 0-3.4 0Z"></path><path d="M12 9v4M12 17h.01"></path></svg>
        </span>
      </button>

      <button type="button" class="pjd-events-score">
        <span class="pjd-events-score-value">{{ $mediumRisk }}</span>
        <span class="pjd-events-score-label">Riesgo medio</span>
        <span class="pjd-events-score-icon is-yellow">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5M12 16h.01"></path></svg>
        </span>
      </button>

      <button type="button" class="pjd-events-score">
        <span class="pjd-events-score-value">{{ $lowRisk }}</span>
        <span class="pjd-events-score-label">Riesgo bajo/nulo</span>
        <span class="pjd-events-score-icon is-green">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>
        </span>
      </button>

      <button type="button" class="pjd-events-score">
        <span class="pjd-events-score-value">{{ $generalRisk }}</span>
        <span class="pjd-events-score-label">Riesgo general</span>
        <span class="pjd-events-score-icon is-orange">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="M7 16v-3M12 16V8M17 16v-5"></path></svg>
        </span>
      </button>
    </div>

    <div class="pjd-events-tools" aria-label="Acciones de eventos">
      <button type="button" class="pjd-events-tool" title="Descargar eventos">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M7 10l5 5 5-5"></path><path d="M12 15V3"></path></svg>
      </button>
      <button type="button" class="pjd-events-tool" data-events-expand id="pjdEventsExpandBtn" title="Estirar vista" aria-label="Estirar vista" aria-expanded="false">
        <svg class="pjd-events-icon-expand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"></path><path d="M9 21H3v-6"></path><path d="M21 3l-7 7"></path><path d="M3 21l7-7"></path></svg>
        <svg class="pjd-events-icon-compress" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3v6H3"></path><path d="M15 21v-6h6"></path><path d="M3 9l7-7"></path><path d="M21 15l-7 7"></path></svg>
      </button>
    </div>

    <section class="pjd-events-comments is-open">
      <div class="pjd-events-comments-head" data-events-comments-toggle role="button" tabindex="0" aria-expanded="true">
        <h2 class="pjd-events-comments-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 13h8M8 17h5"></path></svg>
          Comentarios
        </h2>
        <span class="pjd-events-chev"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"></path></svg></span>
      </div>
      <div class="pjd-events-comments-body">
        <p>{!! preg_replace('/(dictamen NULO|40 días naturales|60 días naturales|2 días hábiles|recomienda)/u', '<strong>$1</strong>', e($commentsText)) !!}</p>
      </div>
    </section>

    <div class="pjd-events-stack">
      <section class="pjd-events-card is-open">
        <div class="pjd-events-card-head js-events-card-toggle" role="button" tabindex="0" aria-expanded="true">
          <h3 class="pjd-events-card-title">
            Vigencias
            <span class="pjd-events-sparkle" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.7 5.1L19 10l-5.3 1.9L12 17l-1.7-5.1L5 10l5.3-1.9L12 3Z"></path><path d="M19 4v4"></path><path d="M17 6h4"></path><path d="M5 16v4"></path><path d="M3 18h4"></path></svg></span>
          </h3>
          <span class="pjd-events-card-chev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"></path></svg></span>
        </div>
        <div class="pjd-events-card-body">
          <div class="pjd-events-list">
            @forelse($vigenciasRows as $row)
              @php $risk = strtoupper((string)($row['risk'] ?? 'NULO')); @endphp
              <article class="pjd-events-row">
                <div class="pjd-events-row-main">
                  <div class="pjd-events-row-title">
                    <h4>{{ mb_strtoupper($row['label'] ?? 'Sin nombre') }}</h4>
                    <select class="pjd-events-risk-select {{ $risk === 'MEDIO' ? 'is-medium' : '' }}" aria-label="Nivel de riesgo">
                      <option value="NULO" {{ $risk === 'NULO' ? 'selected' : '' }}>NULO</option>
                      <option value="BAJO" {{ $risk === 'BAJO' ? 'selected' : '' }}>BAJO</option>
                      <option value="MEDIO" {{ $risk === 'MEDIO' ? 'selected' : '' }}>MEDIO</option>
                      <option value="ALTO" {{ $risk === 'ALTO' ? 'selected' : '' }}>ALTO</option>
                    </select>
                  </div>
                  <p>{{ $row['value'] ?? 'Sin dato' }}</p>
                </div>
                <button type="button" class="pjd-events-trash" title="Eliminar visual" aria-label="Eliminar evento"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6M14 11v6"></path></svg></button>
              </article>
            @empty
              <div class="pjd-events-empty">No hay vigencias detectadas.</div>
            @endforelse
          </div>
        </div>
      </section>

      <section class="pjd-events-card is-open">
        <div class="pjd-events-card-head js-events-card-toggle" role="button" tabindex="0" aria-expanded="true">
          <h3 class="pjd-events-card-title">
            Plazos de ejecución
            <span class="pjd-events-sparkle" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.7 5.1L19 10l-5.3 1.9L12 17l-1.7-5.1L5 10l5.3-1.9L12 3Z"></path><path d="M19 4v4"></path><path d="M17 6h4"></path><path d="M5 16v4"></path><path d="M3 18h4"></path></svg></span>
          </h3>
          <span class="pjd-events-card-chev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"></path></svg></span>
        </div>
        <div class="pjd-events-card-body">
          <div class="pjd-events-list">
            @forelse($plazosRows as $row)
              @php $risk = strtoupper((string)($row['risk'] ?? 'NULO')); @endphp
              <article class="pjd-events-row">
                <div class="pjd-events-row-main">
                  <div class="pjd-events-row-title">
                    <h4>{{ mb_strtoupper($row['label'] ?? 'Sin nombre') }}</h4>
                    <select class="pjd-events-risk-select {{ $risk === 'MEDIO' ? 'is-medium' : '' }}" aria-label="Nivel de riesgo">
                      <option value="NULO" {{ $risk === 'NULO' ? 'selected' : '' }}>NULO</option>
                      <option value="BAJO" {{ $risk === 'BAJO' ? 'selected' : '' }}>BAJO</option>
                      <option value="MEDIO" {{ $risk === 'MEDIO' ? 'selected' : '' }}>MEDIO</option>
                      <option value="ALTO" {{ $risk === 'ALTO' ? 'selected' : '' }}>ALTO</option>
                    </select>
                  </div>
                  <p>{{ $row['value'] ?? 'Sin dato' }}</p>
                </div>
                <button type="button" class="pjd-events-trash" title="Eliminar visual" aria-label="Eliminar evento"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6M14 11v6"></path></svg></button>
              </article>
            @empty
              <div class="pjd-events-empty">No hay plazos de ejecución detectados.</div>
            @endforelse
          </div>
        </div>
      </section>
    </div>
  </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const wrap = document.querySelector('.pjd-wrap');
  const expandBtn = document.getElementById('pjdEventsExpandBtn');
  const comments = document.querySelector('.pjd-events-comments');
  const commentsHead = document.querySelector('[data-events-comments-toggle]');

  if (expandBtn && wrap) {
    expandBtn.addEventListener('click', function () {
      const expanded = wrap.classList.toggle('is-eventos-expanded');
      expandBtn.classList.toggle('is-active', expanded);
      expandBtn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      expandBtn.setAttribute('title', expanded ? 'Contraer vista' : 'Estirar vista');
      expandBtn.setAttribute('aria-label', expanded ? 'Contraer vista' : 'Estirar vista');
      window.dispatchEvent(new Event('resize'));
    });
  }

  if (comments && commentsHead) {
    const toggleComments = function () {
      const isOpen = comments.classList.toggle('is-open');
      commentsHead.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    };

    commentsHead.addEventListener('click', toggleComments);
    commentsHead.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      event.preventDefault();
      toggleComments();
    });
  }

  document.querySelectorAll('.js-events-card-toggle').forEach(function (head) {
    const toggleCard = function () {
      const card = head.closest('.pjd-events-card');
      if (!card) return;
      const isOpen = card.classList.toggle('is-open');
      head.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    };

    head.addEventListener('click', toggleCard);
    head.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      event.preventDefault();
      toggleCard();
    });
  });
});
</script>
@endpush
@endonce
