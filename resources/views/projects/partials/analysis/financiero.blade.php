@once
@push('styles')
<style>
  /* ==========================================================
     FINANCIERO - componente local
     Mismo diseño compacto que Ficha / Eventos / Matriz.
     ========================================================== */

  .pjd-pane[data-pane="financiero"] {
    padding: 16px 20px 24px;
    background: #ffffff;
  }

  .pjd-fin-shell {
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

  .pjd-wrap.is-financiero-expanded .pjd-fin-shell {
    width: calc(100% - 28px);
    max-width: none;
    margin-left: 14px;
    margin-right: 14px;
  }

  .pjd-wrap.is-financiero-expanded .pjd-body {
    grid-template-columns: 0 0 minmax(360px, 1fr) !important;
  }

  .pjd-wrap.is-financiero-expanded .pjd-left,
  .pjd-wrap.is-financiero-expanded .pjd-resizer {
    opacity: 0 !important;
    pointer-events: none !important;
    overflow: hidden !important;
    border: 0 !important;
  }

  .pjd-fin-scorebar {
    width: calc(100% - 76px);
    display: flex;
    align-items: stretch;
    justify-content: flex-start;
    gap: 8px;
    padding: 0 0 18px;
    border-bottom: 1px solid rgba(15, 23, 42, .045);
    background: #ffffff;
  }

  .pjd-fin-score {
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

  .pjd-fin-score:hover,
  .pjd-fin-score.is-active {
    border-color: #007aff;
    background: #f4f8ff;
    box-shadow: 0 8px 20px rgba(0, 122, 255, .12);
    transform: translateY(-1px);
  }

  .pjd-fin-score-value {
    color: #06112a;
    font-size: 16px;
    line-height: 1;
    font-weight: 700;
    letter-spacing: -.02em;
    text-transform: uppercase;
  }

  .pjd-fin-score-label {
    color: #64748b;
    font-size: 11px;
    line-height: 1.1;
  }

  .pjd-fin-score-icon {
    width: 18px;
    height: 18px;
    margin-top: 3px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .pjd-fin-score-icon svg {
    width: 18px;
    height: 18px;
    display: block;
  }

  .pjd-fin-score-icon.is-red { color: #ef4444; }
  .pjd-fin-score-icon.is-yellow { color: #eab308; }
  .pjd-fin-score-icon.is-green { color: #22c55e; }
  .pjd-fin-score-icon.is-orange { color: #f97316; }

  .pjd-fin-tools {
    position: absolute;
    top: 22px;
    right: 24px;
    width: 46px;
    display: grid;
    gap: 8px;
    z-index: 20;
  }

  .pjd-fin-tool {
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

  .pjd-fin-tool:hover,
  .pjd-fin-tool.is-active {
    transform: translateY(-1px);
    border-color: #cfe0ff;
    color: #007aff;
    background: #f8fbff;
  }

  .pjd-fin-tool svg { width: 19px; height: 19px; display: block; }
  .pjd-fin-tool .pjd-fin-icon-compress { display: none; }
  .pjd-wrap.is-financiero-expanded .pjd-fin-tool[data-fin-expand] .pjd-fin-icon-expand { display: none; }
  .pjd-wrap.is-financiero-expanded .pjd-fin-tool[data-fin-expand] .pjd-fin-icon-compress { display: block; }

  .pjd-fin-comments {
    width: 100%;
    margin-top: 20px;
    border: 1px solid #d6c1ff;
    background: #f5efff;
    border-radius: 8px;
    box-shadow: 0 3px 0 rgba(124, 58, 237, .10);
    overflow: hidden;
  }

  .pjd-fin-comments-head {
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

  .pjd-fin-comments-title {
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

  .pjd-fin-comments-title svg { width: 18px; height: 18px; }

  .pjd-fin-comments-body {
    display: none;
    padding: 16px 18px 18px;
    border-top: 1px solid #d6c1ff;
    color: #5b21b6;
    font-size: 14px;
    line-height: 1.65;
    font-weight: 500;
  }

  .pjd-fin-comments.is-open .pjd-fin-comments-body { display: block; }
  .pjd-fin-comments.is-open .pjd-fin-chev { transform: rotate(180deg); }

  .pjd-fin-stack {
    width: 100%;
    display: grid;
    gap: 22px;
    margin-top: 24px;
  }

  .pjd-fin-card {
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 10px 26px rgba(15, 23, 42, .04);
    overflow: hidden;
  }

  .pjd-fin-card-head {
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

  .pjd-fin-card-head:hover { background: #fbfcfe; }

  .pjd-fin-card-title {
    margin: 0;
    color: #06112a;
    font-size: 1rem;
    line-height: 1.2;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 10px;
  }

  .pjd-fin-sparkle {
    width: 22px;
    height: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #8b35f6;
    transform: rotate(-12deg);
    flex: 0 0 auto;
  }

  .pjd-fin-sparkle svg { width: 22px; height: 22px; display: block; }

  .pjd-fin-indicators {
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .pjd-fin-indicators svg { width: 17px; height: 17px; }
  .pjd-fin-indicators .is-red { color: #ef4444; }
  .pjd-fin-indicators .is-yellow { color: #eab308; }
  .pjd-fin-indicators .is-green { color: #22c55e; }

  .pjd-fin-chev {
    width: 22px;
    height: 22px;
    color: #5f6673;
    transition: transform .18s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
  }

  .pjd-fin-chev svg { width: 18px; height: 18px; }
  .pjd-fin-card.is-open .pjd-fin-chev { transform: rotate(180deg); }

  .pjd-fin-card-body {
    display: none;
    padding: 0 24px 22px;
    border-top: 1px solid rgba(15, 23, 42, .035);
    background: #ffffff;
  }

  .pjd-fin-card.is-open .pjd-fin-card-body { display: block; }

  .pjd-fin-list {
    display: block;
    padding: 0;
    background: #ffffff;
  }

  .pjd-fin-row {
    width: 100%;
    height: auto;
    display: grid;
    grid-template-columns: minmax(0,1fr) 124px 28px;
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
    text-align: left !important;
  }

  .pjd-fin-row:first-child { border-top: 0; }

  .pjd-fin-row:hover {
    background: #f5f8ff;
    border-radius: 10px;
    outline: 1px solid #d9e8ff;
    outline-offset: -1px;
    padding-left: 12px;
    padding-right: 12px;
  }

  .pjd-fin-row-main {
    width: 100%;
    min-width: 0;
    text-align: left !important;
  }

  .pjd-fin-row-title {
    margin: 0 0 7px;
    color: #737373;
    font-size: 14px;
    line-height: 20px;
    font-weight: 500;
    letter-spacing: .035em;
    text-transform: uppercase;
    white-space: pre-wrap;
    word-break: break-word;
    text-align: left !important;
  }

  .pjd-fin-row-text {
    margin: 0;
    color: #020817;
    font-size: 14px;
    line-height: 20px;
    font-weight: 500;
    letter-spacing: 0;
    white-space: pre-wrap;
    text-align: left !important;
  }

  .pjd-fin-risk {
    width: 124px;
    height: 32px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #f8fafc;
    color: #737373;
    font-size: 12px;
    font-weight: 700;
    padding: 0 12px;
    outline: none;
    justify-self: start;
  }

  .pjd-fin-risk.is-alto { color: #dc2626; border-color: #fecaca; background: #fff7f7; }
  .pjd-fin-risk.is-medio { color: #a16207; border-color: #fde68a; background: #fffbea; }
  .pjd-fin-risk.is-bajo { color: #15803d; border-color: #bbf7d0; background: #f0fff4; }
  .pjd-fin-risk.is-nulo { color: #737373; border-color: #e5e7eb; background: #f5f5f5; }

  .pjd-fin-trash {
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

  .pjd-fin-trash svg { width: 17px; height: 17px; }
  .pjd-fin-trash:hover { background: #fff1f1; color: #ff4a4a; }
  .pjd-fin-trash:active { transform: scale(.96); }

  .pjd-fin-source {
    display: none;
    grid-column: 1 / -1;
    width: 100%;
    margin-top: 14px;
    padding: 0;
  }

  .pjd-fin-row.is-source-open {
    padding: 16px 14px !important;
    border: 1px solid #cfe0ff !important;
    border-radius: 10px !important;
    background: #f5f8ff !important;
    outline: none !important;
  }

  .pjd-fin-row.is-source-open .pjd-fin-source { display: block; }

  .pjd-fin-source-box {
    width: 100%;
    margin: 16px 0 18px;
    padding: 14px 16px;
    border: 1px solid rgba(15, 23, 42, .06);
    border-radius: 10px;
    background: rgba(255,255,255,.46);
  }

  .pjd-fin-source-title {
    margin: 0 0 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(15, 23, 42, .06);
    color: #737373;
    font-size: 14px;
    line-height: 20px;
    font-weight: 700;
    letter-spacing: .035em;
    text-transform: uppercase;
  }

  .pjd-fin-source-text {
    margin: 0;
    color: #020817;
    font-size: 14px;
    line-height: 20px;
    font-weight: 500;
    white-space: pre-wrap;
    text-align: left !important;
  }

  .pjd-fin-cita-title {
    margin: 18px 0 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(15, 23, 42, .06);
    color: #737373;
    font-size: 14px;
    line-height: 20px;
    font-weight: 700;
    letter-spacing: .035em;
    text-transform: uppercase;
  }

  .pjd-fin-cita-list {
    margin: 0 0 16px;
    padding-left: 20px;
    color: #020817;
    font-size: 14px;
    line-height: 20px;
    font-style: italic;
  }

  .pjd-fin-cita-list li { margin: 0 0 8px; }

  .pjd-fin-source-meta {
    width: 100%;
    min-height: 54px;
    margin: 12px 0 0;
    padding: 12px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: rgba(255,255,255,.62);
    color: #737373;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    font-size: 13px;
    font-weight: 600;
  }

  .pjd-fin-source-meta strong {
    width: 100%;
    color: #737373;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .035em;
    text-transform: uppercase;
  }

  .pjd-fin-source-meta span {
    color: #007aff;
    font-size: 14px;
    line-height: 20px;
    font-weight: 600;
    text-decoration: underline;
  }

  @media (max-width: 1180px) {
    .pjd-fin-scorebar { width: 100%; flex-wrap: wrap; }
    .pjd-fin-tools { position: static; width: 100%; display: flex; justify-content: flex-end; margin-top: 10px; }
  }

  @media (max-width: 700px) {
    .pjd-pane[data-pane="financiero"] { padding: 12px; }
    .pjd-fin-shell { width: 100%; margin: 12px auto 20px; padding: 14px; border-radius: 16px; }
    .pjd-fin-scorebar { width: 100%; flex-direction: column; gap: 10px; }
    .pjd-fin-score { width: 100%; flex-basis: auto; height: 86px; }
    .pjd-fin-card-head { height: 66px; padding: 0 18px; }
    .pjd-fin-card-body { padding: 0 16px 18px; }
    .pjd-fin-row { grid-template-columns: minmax(0,1fr) 26px; gap: 10px; padding: 14px 4px; }
    .pjd-fin-risk { grid-column: 1 / -1; }
  }
</style>
@endpush
@endonce

<div class="pjd-pane" data-pane="financiero">
  @php
    $finSections = collect(data_get($sd, 'financiero.secciones', []))
      ->filter(fn ($section) => is_array($section))
      ->values()
      ->all()

    $finItems = collect($finSections)->flatMap(fn($section) => $section['items'])->values();
    $finAlto = $finItems->where('risk', 'ALTO')->count();
    $finMedio = $finItems->where('risk', 'MEDIO')->count();
    $finBajoNulo = $finItems->filter(fn($it) => in_array($it['risk'], ['BAJO', 'NULO'], true))->count();
    $finGeneralRisk = $finAlto > 0 ? 'ALTO' : ($finMedio > 0 ? 'MEDIO' : 'BAJO');

    $finComment = 'El análisis financiero integra '.$finItems->count().' conceptos. Se detecta '.$finAlto.' punto de riesgo alto, '.$finMedio.' punto de riesgo medio y '.$finBajoNulo.' puntos con riesgo bajo o nulo. La revisión prioritaria debe enfocarse en fianzas, capital de trabajo, plazos de pago, reposición de bienes y costo financiero antes de cerrar la propuesta económica.';

    $finRiskClass = function ($risk) {
      return match($risk) {
        'ALTO' => 'is-alto',
        'MEDIO' => 'is-medio',
        'BAJO' => 'is-bajo',
        default => 'is-nulo',
      };
    };

    $finSparkle = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.7 5.1L19 10l-5.3 1.9L12 17l-1.7-5.1L5 10l5.3-1.9L12 3Z"></path><path d="M19 4v4"></path><path d="M17 6h4"></path><path d="M5 16v4"></path><path d="M3 18h4"></path></svg>';
  @endphp

  <div class="pjd-fin-shell">
    <div class="pjd-fin-scorebar">
      <button type="button" class="pjd-fin-score is-active">
        <span class="pjd-fin-score-value">{{ $finItems->count() }}</span>
        <span class="pjd-fin-score-label">Conceptos</span>
        <span class="pjd-fin-score-icon is-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 13h8M8 17h5"></path></svg></span>
      </button>
      <button type="button" class="pjd-fin-score">
        <span class="pjd-fin-score-value">{{ $finAlto }}</span>
        <span class="pjd-fin-score-label">Riesgo alto</span>
        <span class="pjd-fin-score-icon is-red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 3.3 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.3a2 2 0 0 0-3.4 0Z"></path><path d="M12 9v4M12 17h.01"></path></svg></span>
      </button>
      <button type="button" class="pjd-fin-score">
        <span class="pjd-fin-score-value">{{ $finMedio }}</span>
        <span class="pjd-fin-score-label">Riesgo medio</span>
        <span class="pjd-fin-score-icon is-yellow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5M12 16h.01"></path></svg></span>
      </button>
      <button type="button" class="pjd-fin-score">
        <span class="pjd-fin-score-value">{{ $finBajoNulo }}</span>
        <span class="pjd-fin-score-label">Riesgo bajo/nulo</span>
        <span class="pjd-fin-score-icon is-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"></path></svg></span>
      </button>
      <button type="button" class="pjd-fin-score">
        <span class="pjd-fin-score-value">{{ $finGeneralRisk }}</span>
        <span class="pjd-fin-score-label">Riesgo general</span>
        <span class="pjd-fin-score-icon is-red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"></path><path d="M7 16v-3M12 16V8M17 16v-5"></path></svg></span>
      </button>
    </div>

    <div class="pjd-fin-tools" aria-label="Acciones financiero">
      <button type="button" class="pjd-fin-tool" title="Descargar financiero"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M7 10l5 5 5-5"></path><path d="M12 15V3"></path></svg></button>
      <button type="button" class="pjd-fin-tool" data-fin-expand id="pjdFinExpandBtn" title="Estirar vista" aria-label="Estirar vista" aria-expanded="false"><svg class="pjd-fin-icon-expand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"></path><path d="M9 21H3v-6"></path><path d="M21 3l-7 7"></path><path d="M3 21l7-7"></path></svg><svg class="pjd-fin-icon-compress" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3v6H3"></path><path d="M15 21v-6h6"></path><path d="M3 9l7-7"></path><path d="M21 15l-7 7"></path></svg></button>
    </div>

    <section class="pjd-fin-comments">
      <div class="pjd-fin-comments-head" data-fin-toggle role="button" tabindex="0" aria-expanded="false">
        <h2 class="pjd-fin-comments-title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 13h8M8 17h5"></path></svg>Comentarios</h2>
        <span class="pjd-fin-chev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"></path></svg></span>
      </div>
      <div class="pjd-fin-comments-body">{{ $finComment }}</div>
    </section>

    <div class="pjd-fin-stack">
      @foreach($finSections as $section)
        <div class="pjd-fin-card">
          <div class="pjd-fin-card-head" data-fin-toggle role="button" tabindex="0" aria-expanded="false">
            <h3 class="pjd-fin-card-title">
              {{ $section['title'] }}
              <span class="pjd-fin-sparkle" aria-hidden="true">{!! $finSparkle !!}</span>
              <span class="pjd-fin-indicators" aria-hidden="true">
                @foreach($section['icons'] as $ic)
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
            <span class="pjd-fin-chev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"></path></svg></span>
          </div>

          <div class="pjd-fin-card-body">
            <div class="pjd-fin-list">
              @foreach($section['items'] as $it)
                @php $riskClass = $finRiskClass($it['risk']); @endphp
                <article class="pjd-fin-row" data-fin-row>
                  <div class="pjd-fin-row-main">
                    <h4 class="pjd-fin-row-title">{{ mb_strtoupper($it['question']) }}</h4>
                    <p class="pjd-fin-row-text">{!! nl2br(e($it['answer'])) !!}</p>
                  </div>

                  <select class="pjd-fin-risk {{ $riskClass }}" aria-label="Riesgo">
                    <option @selected($it['risk'] === 'ALTO')>ALTO</option>
                    <option @selected($it['risk'] === 'MEDIO')>MEDIO</option>
                    <option @selected($it['risk'] === 'BAJO')>BAJO</option>
                    <option @selected($it['risk'] === 'NULO')>NULO</option>
                  </select>

                  <button type="button" class="pjd-fin-trash" title="Eliminar visual" aria-label="Eliminar concepto"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6M14 11v6"></path></svg></button>

                  <div class="pjd-fin-source">
                    <div class="pjd-fin-source-box">
                      <h5 class="pjd-fin-source-title">Justificación</h5>
                      <p class="pjd-fin-source-text">{!! nl2br(e($it['justificacion'])) !!}</p>
                    </div>

                    <h5 class="pjd-fin-cita-title">Cita del documento</h5>
                    <ul class="pjd-fin-cita-list">
                      @foreach(($it['citas'] ?? []) as $cita)
                        <li>{{ $cita }}</li>
                      @endforeach
                    </ul>

                    <div class="pjd-fin-source-meta">
                      <strong>Fuente original</strong>
                      <span>{{ $it['fuente'] ?? '-' }}</span>
                    </div>
                  </div>
                </article>
              @endforeach
            </div>
          </div>
        </div>
      @empty
        <div class="pjd-fin-empty">
          La IA todavía no ha generado información financiera para los documentos de este proyecto.
        </div>
      @endforelse
    </div>
  </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-pane="financiero"] [data-fin-toggle]').forEach(function (head) {
    const block = head.closest('.pjd-fin-card, .pjd-fin-comments');
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

  document.querySelectorAll('[data-pane="financiero"] [data-fin-row]').forEach(function (row) {
    row.addEventListener('click', function (event) {
      if (event.target.closest('a, button, select, form')) return;
      row.classList.toggle('is-source-open');
    });
  });

  const expandBtn = document.getElementById('pjdFinExpandBtn');
  const wrap = document.querySelector('.pjd-wrap');

  if (expandBtn && wrap) {
    expandBtn.addEventListener('click', function () {
      const expanded = wrap.classList.toggle('is-financiero-expanded');
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
