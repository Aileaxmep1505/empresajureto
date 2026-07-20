@once
@push('styles')
<style>
  /* ==========================================================
     FICHA COMPONENTE LOCAL
     Todo lo visual de Ficha vive aqui. No depende de styles.blade.php.
     Usa HEIGHT y WIDTH directos para mover tamanos.
     ========================================================== */

  .pjd-pane[data-pane="ficha"] {
    padding: 16px 20px 24px;
     background: #ffffff;
  }
.pjd-right {
     background: #ffffff;
  }
  .pjd-fx-shell.pjd-fx-workspace {
    position: relative;
    width: min(100%, 1360px); /* WIDTH GENERAL DEL CONTENEDOR */
    margin: 18px auto 28px;
    padding: 22px 24px 28px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    box-shadow: 0 10px 26px rgba(15, 23, 42, .05);
    overflow: hidden;
  }

  .pjd-wrap.is-ficha-expanded .pjd-fx-shell.pjd-fx-workspace {
    width: calc(100% - 28px); /* WIDTH CUANDO DAS CLIC EN ESTIRAR */
    max-width: none;
    margin-left: 14px;
    margin-right: 14px;
  }

  .pjd-wrap.is-ficha-expanded .pjd-body {
    grid-template-columns: 0 0 minmax(360px, 1fr) !important;
  }

  .pjd-wrap.is-ficha-expanded .pjd-left,
  .pjd-wrap.is-ficha-expanded .pjd-resizer {
    opacity: 0 !important;
    pointer-events: none !important;
    overflow: hidden !important;
    border: 0 !important;
  }

  /* ===== METRICAS SUPERIORES ===== */
  .pjd-fx-scorebar {
    width: calc(100% - 76px); /* WIDTH DE METRICAS, deja espacio real para botones */
    display: flex; /* MAS JUNTO QUE GRID: evita que Cumplimiento choque con botones */
    align-items: stretch;
    justify-content: flex-start;
    gap: 8px; /* AQUI JUNTAS O SEPARAS LAS CARDS */
    padding: 0 0 18px;
    border-bottom: 1px solid #eef1f5;
    background: #ffffff;
  }

  .pjd-fx-score {
    width: 112px; /* WIDTH DE CADA CARD DE METRICA */
    height: 68px; /* HEIGHT DE CADA CARD DE METRICA */
    flex: 0 0 112px;
    padding: 8px 8px;
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

  .pjd-fx-score:hover,
  .pjd-fx-score.is-active {
    border-color: #007aff;
    background: #f4f8ff;
    box-shadow: 0 8px 20px rgba(0, 122, 255, .12);
    transform: translateY(-1px);
  }

  .pjd-fx-score-value {
    color: #06112a;
    font-size: 16px;
    line-height: 1;
    font-weight: 700;
    letter-spacing: -.02em;
    text-transform: uppercase;
  }

  .pjd-fx-score-label {
    color: #64748b;
    font-size: 11px;
    line-height: 1.1;
   
  }

  .pjd-fx-score-icon {
    width: 18px;
    height: 18px;
    margin-top: 3px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
  }

  .pjd-fx-score-icon svg {
    width: 18px;
    height: 18px;
    display: block;
  }

  .pjd-fx-score-icon.is-yellow { color: #eab308; }
  .pjd-fx-score-icon.is-red { color: #ef4444; }
  .pjd-fx-score-icon.is-green { color: #22c55e; }
  .pjd-fx-score-icon.is-orange { color: #f59e0b; }

  /* ===== BOTONES DERECHA ===== */
  .pjd-fx-tools {
    position: absolute;
    top: 22px;
    right: 24px;
    width: 46px; /* WIDTH COLUMNA BOTONES */
    display: grid;
    gap: 8px;
    z-index: 20;
  }

  .pjd-fx-tool {
    width: 36px; /* WIDTH BOTON */
    height: 31.5px; /* HEIGHT BOTON */
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

  .pjd-fx-tool:hover,
  .pjd-fx-tool.is-active {
    transform: translateY(-1px);
    border-color: #cfe0ff;
    color: #007aff;
    background: #f8fbff;
  }

  .pjd-fx-tool:active { transform: scale(.98); }
  .pjd-fx-tool svg { width: 19px; height: 19px; display: block; }
  .pjd-fx-tool .pjd-fx-icon-compress { display: none; }
  .pjd-wrap.is-ficha-expanded .pjd-fx-tool[data-fx-expand] .pjd-fx-icon-expand { display: none; }
  .pjd-wrap.is-ficha-expanded .pjd-fx-tool[data-fx-expand] .pjd-fx-icon-compress { display: block; }

  /* ===== RESUMEN EJECUTIVO ===== */
  .pjd-fx-executive {
    width: 100%;
    margin-top: 20px;
    border: 1px solid #d6c1ff;
    background: #f5efff;
    border-radius: 8px;
    box-shadow: 0 3px 0 rgba(124, 58, 237, .10);
    overflow: hidden;
  }

  .pjd-fx-executive-head {
    width: 100%;
    height: 64px; /* HEIGHT RESUMEN EJECUTIVO CERRADO */
    padding: 0 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    cursor: pointer;
    user-select: none;
    border-bottom: 1px solid transparent;
    transition: background .18s ease, border-color .18s ease;
  }

  .pjd-fx-executive-head:hover { background: rgba(255,255,255,.22); }
  .pjd-fx-executive.is-open .pjd-fx-executive-head { border-bottom-color: #d6c1ff; }

  .pjd-fx-hero-title {
    margin: 0;
    color: #6d28d9;
    font-size: 1.12rem;
    line-height: 1.15;
    font-weight: 700;
    letter-spacing: -.02em;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .pjd-fx-hero-title svg {
    width: 17px;
    height: 17px;
    color: #8b5cf6;
    flex-shrink: 0;
    transition: transform .18s ease;
    transform: rotate(0deg);
  }

  .pjd-fx-executive.is-open .pjd-fx-hero-title svg { transform: rotate(180deg); }

  .pjd-fx-hero-pills {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
  }

  .pjd-fx-pill {
    width: auto;
    height: 30px; /* HEIGHT PILLS */
    padding: 0 16px;
    border-radius: 999px;
    border: 1px solid #ffb5b5;
    background: #fff4f4;
    color: #a31515;
    font-size: .78rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
  }

  .pjd-fx-executive-body {
    display: none;
    padding: 18px 24px 22px;
  }

  .pjd-fx-executive.is-open .pjd-fx-executive-body { display: block; }

  .pjd-fx-exec-section {
    padding: 0 0 16px;
    margin: 0 0 16px;
    border-top: 1px solid rgba(15, 23, 42, 0.06);
  }

  .pjd-fx-exec-section.is-last {
    border-bottom: 0;
    margin-bottom: 0;
    padding-bottom: 0;
  }

  .pjd-fx-exec-section h3 {
    margin: 0 0 8px;
    color: #8b5cf6;
    font-size: .9rem;
    line-height: 1.25;
    font-weight: 700;
    letter-spacing: -.01em;
  }

  .pjd-fx-exec-section p {
    margin: 0;
    color: #344155;
    font-size: .9rem;
    line-height: 1.55;
    font-weight: 500;
  }

  .pjd-fx-note {
    margin-top: 10px;
    padding: 0 0 0 12px;
    border-left: 3px solid #d8c7ff;
  }

  .pjd-fx-note.is-soft,
  .pjd-fx-note.is-danger {
    padding: 9px 12px;
    background: rgba(255,255,255,.38);
  }

  .pjd-fx-note.is-danger {
    background: #fff7f7;
    border-left-color: #ffb5b5;
  }

  .pjd-fx-note span {
    display: block;
    margin-bottom: 5px;
    color: #9b6df4;
    font-size: .74rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .035em;
  }

  .pjd-fx-note.is-danger p { color: #a31515; }

  /* ===== ACORDEONES FICHA / HITOS ===== */
  .pjd-fx-stack {
    width: 100%;
    display: grid;
    gap: 22px;
    margin-top: 24px;
  }

  .pjd-fx-card,
  .pjd-fx-card.is-hitos {
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 10px 26px rgba(15, 23, 42, .04);
    overflow: hidden;
     margin-bottom: 5px;
  }

  .pjd-fx-card-head,
  .pjd-fx-card.is-hitos .pjd-fx-card-head {
    width: 100%;
    height: 50px; /* HEIGHT FICHA DE RESUMEN / HITOS CERRADOS */
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

  .pjd-fx-card-head:hover,
  .pjd-fx-card.is-hitos .pjd-fx-card-head:hover { background: #fbfcfe; }

  .pjd-fx-card-title,
  .pjd-fx-card.is-hitos .pjd-fx-card-title {
    margin: 0;
    color: #06112a;
    font-size: 1rem;
    line-height: 1.2;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 10px;
  }

  .pjd-fx-chev {
    width: 22px;
    height: 22px;
    color: #5f6673;
    transition: transform .18s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
  }

  .pjd-fx-chev svg { width: 18px; height: 18px; }
  .pjd-fx-card.is-open .pjd-fx-chev { transform: rotate(180deg); }

  .pjd-fx-card-body,
  .pjd-fx-card.is-hitos .pjd-fx-card-body {
    display: none;
    padding: 0 24px 22px;
     border-top: 1px solid #eef1f5;
    background: #ffffff;
  }

  .pjd-fx-card.is-open .pjd-fx-card-body { display: block; }

  .pjd-fx-sparkle {
    width: 22px;
    height: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #8b35f6;
    transform: rotate(-12deg);
    flex: 0 0 auto;
  }

  .pjd-fx-sparkle svg {
    width: 22px;
    height: 22px;
    display: block;
  }

  /* ===== LISTA Q/A E HITOS ===== */
  .pjd-fx-qa-list,
  .pjd-fx-hitos-list {
    display: block;
    padding: 0;
    background: #ffffff;
  }

  .pjd-fx-qa,
  .pjd-fx-hito {
    width: 100%;
    height: auto;
    display: grid;
    grid-template-columns: minmax(0,1fr) 28px;
    gap: 14px;
    align-items: start;
    padding: 16px 8px 16px 0;
    margin: 0;
    border: 0;
   
    border-radius: 0;
    background: #ffffff;
    box-shadow: none;
    transform: none;
    transition: background .18s ease, outline .18s ease;
  }

  .pjd-fx-qa:first-child,
  .pjd-fx-hito:first-child {  border-top: 1px solid #eef1f5; }

  .pjd-fx-qa:hover,
  .pjd-fx-hito:hover {
    background: #f5f8ff;
    border-radius: 10px;
    outline: 1px solid #d9e8ff;
    outline-offset: -1px;
    padding-left: 12px;
    padding-right: 12px;
  }

  .pjd-fx-qa-main,
  .pjd-fx-hito-main {
    width: 100%;
    min-width: 0;
  }

  .pjd-fx-qa-main h4,
  .pjd-fx-hito-title {
    margin: 0 0 7px;
    color: #737373;
    font-size: 14px;
    line-height: 20px;
    font-weight: 500;
    letter-spacing: .035em;
    text-transform: uppercase;
    white-space: pre-wrap;
    word-break: break-word;
  }

  .pjd-fx-qa-main p,
  .pjd-fx-hito-date {
    margin: 0;
    color: #020817;
    font-size: 14px;
    line-height: 20px;
    font-weight: 500;
    letter-spacing: 0;
    white-space: pre-wrap;
  }

  .pjd-fx-trash,
  .pjd-fx-hito-trash {
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

  .pjd-fx-trash svg,
  .pjd-fx-hito-trash svg {
    width: 17px;
    height: 17px;
  }

  .pjd-fx-trash:hover,
  .pjd-fx-hito-trash:hover {
    background: #fff1f1;
    color: #ff4a4a;
  }

  .pjd-fx-trash:active,
  .pjd-fx-hito-trash:active { transform: scale(.96); }

  .pjd-fx-cita {
    width: fit-content;
    height: 24px;
    margin-top: 8px;
    padding: 0 9px;
    border-radius: 999px;
    border: 1px solid #cfe0ff;
    background: #e6f0ff;
    color: #007aff;
    font-size: 11px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .pjd-fx-cita svg { width: 14px; height: 14px; }

  .pjd-fx-qa.is-source-open,
  .pjd-fx-hito.is-source-open { grid-template-columns: minmax(0, 1fr) 28px; }

  .pjd-fx-qa .pjd-source-panel,
  .pjd-fx-hito .pjd-source-panel {
    grid-column: 1 / -1;
    padding: 10px 0 0;
  }

  .pjd-ai-notice {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid #e6e9ee;
    border-radius: 12px;
    background: #f6f8fb;
    color: #5b6470;
    padding: 12px 14px;
    margin: 10px 0 12px;
    font-size: .86rem;
    font-weight: 600;
  }

  .pjd-svg {
    width: 16px;
    height: 16px;
    display: block;
    flex-shrink: 0;
  }



  /* ===== DISEÑO AL ABRIR UNA PREGUNTA / HITO: CITA TIPO REFERENCIA ===== */
  .pjd-fx-qa.is-source-open,
  .pjd-fx-hito.is-source-open {
    padding: 16px 14px !important;
    border: 1px solid #cfe0ff !important;
    border-radius: 10px !important;
    background: #f5f8ff !important;
    outline: none !important;
  }

  .pjd-fx-qa.is-source-open + .pjd-fx-qa,
  .pjd-fx-hito.is-source-open + .pjd-fx-hito {
    border-top-color: transparent;
  }

  .pjd-fx-qa .pjd-source-panel[hidden],
  .pjd-fx-hito .pjd-source-panel[hidden] {
    display: none !important;
  }

  .pjd-fx-qa.is-source-open .pjd-source-panel:not([hidden]),
  .pjd-fx-hito.is-source-open .pjd-source-panel:not([hidden]) {
    display: block !important;
    width: 100%;
    margin-top: 18px;
  }

  .pjd-fx-qa .pjd-source-card,
  .pjd-fx-hito .pjd-source-card {
    position: relative;
    padding: 0;
    border: 0 !important;
    border-radius: 0;
    background: transparent !important;
    box-shadow: none !important;
  }

  .pjd-fx-qa .pjd-source-card::before,
  .pjd-fx-hito .pjd-source-card::before {
    display: none !important;
  }

  .pjd-fx-qa .pjd-source-close,
  .pjd-fx-hito .pjd-source-close {
    display: none !important;
  }

  .pjd-fx-qa .pjd-source-title,
  .pjd-fx-hito .pjd-source-title {
    margin: 0 0 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(15, 23, 42, .025);
    color: #737373;
    font-size: 14px;
    line-height: 20px;
    font-weight: 700;
    letter-spacing: .035em;
    text-transform: uppercase;
  }

  .pjd-fx-qa .pjd-source-title::before,
  .pjd-fx-hito .pjd-source-title::before {
    display: none !important;
  }

  .pjd-fx-qa .pjd-source-quote,
  .pjd-fx-hito .pjd-source-quote {
    margin: 0 0 18px;
    padding: 0 0 0 22px;
    border: 0 !important;
    border-radius: 0;
    background: transparent !important;
    color: #020817;
    font-size: 14px;
    line-height: 1.55;
    font-weight: 500;
    font-style: italic;
    white-space: pre-wrap;
  }

  .pjd-fx-qa .pjd-source-quote::before,
  .pjd-fx-hito .pjd-source-quote::before {
    content: "•";
    position: absolute;
    left: 4px;
    color: #cfd4dc;
    font-size: 22px;
    line-height: 1;
    font-family: inherit;
    font-style: normal;
  }

  .pjd-fx-qa .pjd-source-meta,
  .pjd-fx-hito .pjd-source-meta {
    width: 100%;
    min-height: 54px;
    margin: 12px 0 16px;
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

  .pjd-fx-qa .pjd-source-meta strong,
  .pjd-fx-hito .pjd-source-meta strong {
    width: 100%;
    color: #737373;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .035em;
    text-transform: uppercase;
  }

  .pjd-fx-qa .pjd-source-meta span,
  .pjd-fx-hito .pjd-source-meta span {
    padding: 0;
    border: 0;
    background: transparent;
    color: #007aff;
    font-size: 14px;
    line-height: 20px;
    font-weight: 600;
    text-decoration: underline;
  }

  .pjd-fx-qa .pjd-source-actions,
  .pjd-fx-hito .pjd-source-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  .pjd-fx-qa .pjd-source-btn,
  .pjd-fx-hito .pjd-source-btn {
    height: 36px;
    padding: 0 14px;
    border-radius: 8px;
    border: 1px solid #b7d4ff;
    background: #ffffff;
    color: #007aff;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    box-shadow: none;
  }

  .pjd-fx-qa .pjd-source-btn:hover,
  .pjd-fx-hito .pjd-source-btn:hover {
    background: #e6f0ff;
    transform: translateY(-1px);
  }

  @media (max-width: 1180px) {
    .pjd-fx-scorebar {
      width: 100%;
      flex-wrap: wrap;
    }

    .pjd-fx-tools {
      position: static;
      width: 100%;
      display: flex;
      justify-content: flex-end;
      margin-top: 10px;
    }
  }

  @media (max-width: 700px) {
    .pjd-pane[data-pane="ficha"] { padding: 12px; }

    .pjd-fx-shell.pjd-fx-workspace {
      width: 100%;
      margin: 12px auto 20px;
      padding: 14px;
      border-radius: 16px;
    }

    .pjd-fx-scorebar { width: 100%; flex-direction: column; gap: 10px; }
    .pjd-fx-score { width: 100%; flex-basis: auto; height: 86px; }

    .pjd-fx-executive-head {
      height: auto;
      padding: 14px 16px;
      align-items: flex-start;
      flex-direction: column;
    }

    .pjd-fx-hero-pills { width: 100%; }
    .pjd-fx-pill { width: 100%; }
    .pjd-fx-executive-body { padding: 16px; }

    .pjd-fx-card-head,
    .pjd-fx-card.is-hitos .pjd-fx-card-head {
      height: 66px;
      padding: 0 18px;
    }

    .pjd-fx-card-title,
    .pjd-fx-card.is-hitos .pjd-fx-card-title { font-size: 1.02rem; }

    .pjd-fx-card-body,
    .pjd-fx-card.is-hitos .pjd-fx-card-body { padding: 0 16px 18px; }

    .pjd-fx-qa,
    .pjd-fx-hito {
      grid-template-columns: minmax(0,1fr) 26px;
      gap: 10px;
      padding: 14px 4px;
    }
  }
</style>
@endpush
@endonce

<div class="pjd-pane is-active" data-pane="ficha">
  @php
    $totalChecklistFx = count($checklist ?? []);

    $cumpleChecklistFx = collect($checklist ?? [])
        ->filter(function ($it) {
            if (!is_array($it)) {
                return false;
            }

            return ($it['cumplimiento'] ?? null) === 'Cumple'
                || ($it['status'] ?? null) === 'Aprobado';
        })
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Conversor seguro para valores generados por IA
    |--------------------------------------------------------------------------
    | Convierte texto, números, objetos y arreglos estructurados a una cadena.
    | Prioriza claves de contenido y excluye metadatos como fuente/página/cita.
    */
    $fxClean = null;

    $fxClean = function ($value) use (&$fxClean) {
        if (is_null($value)) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }

        if (is_scalar($value)) {
            $text = trim(
                preg_replace(
                    '/\s+/u',
                    ' ',
                    strip_tags((string) $value)
                )
            );

            return $text !== '' ? $text : null;
        }

        if ($value instanceof \Stringable) {
            $text = trim(
                preg_replace(
                    '/\s+/u',
                    ' ',
                    strip_tags((string) $value)
                )
            );

            return $text !== '' ? $text : null;
        }

        if (is_object($value)) {
            $value = (array) $value;
        }

        if (is_array($value)) {
            foreach ([
                'respuesta',
                'valor',
                'resultado',
                'nivel',
                'riesgo',
                'recomendacion',
                'fecha',
                'texto',
                'descripcion',
                'nombre',
                'titulo',
                'label',
                'content',
            ] as $key) {
                if (!array_key_exists($key, $value)) {
                    continue;
                }

                $candidate = $fxClean($value[$key]);

                if ($candidate !== null && $candidate !== '') {
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
                    'evidencia',
                    'metadata',
                ], true)) {
                    continue;
                }

                $part = $fxClean($item);

                if ($part !== null && $part !== '') {
                    $parts[] = $part;
                }
            }

            $text = trim(implode(' ', array_unique($parts)));

            return $text !== '' ? $text : null;
        }

        return null;
    };

    $fxUpper = function ($value, string $fallback) use ($fxClean): string {
        $clean = $fxClean($value) ?: $fallback;

        return mb_strtoupper($clean, 'UTF-8');
    };

    $cumplimientoFallback = $fxClean(
        data_get(
            $sd,
            'resumen.cumplimiento',
            data_get($sd, 'cumplimiento_porcentaje', 0)
        )
    );

    $cumplimientoFallback = is_numeric($cumplimientoFallback)
        ? (int) $cumplimientoFallback
        : 0;

    $cumplimientoFx = $totalChecklistFx > 0
        ? (int) round(($cumpleChecklistFx / $totalChecklistFx) * 100)
        : $cumplimientoFallback;

    $cumplimientoFx = max(0, min(100, $cumplimientoFx));

    $riesgoFx = $fxUpper(
        data_get(
            $sd,
            'dictamen.riesgo',
            data_get(
                $sd,
                'riesgo_general',
                data_get($sd, 'riesgo', 'ALTO')
            )
        ),
        'ALTO'
    );

    $recomendacionFx = $fxUpper(
        data_get(
            $sd,
            'dictamen.recomendacion',
            data_get($sd, 'recomendacion', 'NO')
        ),
        'NO'
    );

    $plazosFx = $fxUpper(
        data_get(
            $sd,
            'metricas.plazos',
            data_get($sd, 'riesgos.plazos', 'MEDIO')
        ),
        'MEDIO'
    );

    $matrizFx = $fxUpper(
        data_get(
            $sd,
            'metricas.matriz',
            data_get($sd, 'riesgos.matriz', 'ALTO')
        ),
        'ALTO'
    );

    $financieroFx = $fxUpper(
        data_get(
            $sd,
            'metricas.financiero',
            data_get($sd, 'riesgos.financiero', 'ALTO')
        ),
        'ALTO'
    );

    $interesFx = $fxUpper(
        data_get(
            $sd,
            'metricas.interes',
            data_get($sd, 'interes', 'ALTO')
        ),
        'ALTO'
    );

    $fxFirst = function (array $paths, $fallback = null) use ($sd, $fxClean) {
        foreach ($paths as $path) {
            $value = $fxClean(data_get($sd, $path));

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $fxClean($fallback);
    };

    $objetoFx = $fxClean(
        $ficha['objeto_licitacion']
            ?? $ficha['objeto']
            ?? null
    );

    $organismoFx = $fxClean(
        $ficha['organismo']
            ?? null
    );

    $tipoEventoFx = $fxClean(
        $ficha['tipo_evento']
            ?? null
    );

    $executiveObjectFx = $fxFirst([
      'resumen_ejecutivo.objeto_dictamen',
      'resumen_ejecutivo.objeto_y_dictamen',
      'dictamen.objeto_dictamen',
      'dictamen.objeto_y_alineacion',
      'analisis.objeto_dictamen',
    ], $objetoFx
      ? 'La convocante busca contratar ' . $objetoFx . '. El objeto general está claramente delimitado y debe validarse contra los requisitos técnicos, administrativos y documentales de las bases.'
      : 'Sin información ejecutiva detectada para el objeto de la licitación.');

    $portfolioFx = $fxFirst([
      'resumen_ejecutivo.alineacion_portafolio',
      'dictamen.alineacion_portafolio',
      'analisis.alineacion_portafolio',
    ], $objetoFx
      ? 'El proyecto debe compararse contra el portafolio disponible, capacidad operativa, tiempos de entrega, documentación corporativa y experiencia comprobable para determinar viabilidad real de participación.'
      : 'No se cuenta con información suficiente para determinar la alineación con portafolio.');

    $signalFx = $fxFirst([
      'resumen_ejecutivo.senal_viabilidad',
      'dictamen.senal_viabilidad',
      'analisis.senal_viabilidad',
      'dictamen.viabilidad',
    ], 'Se recomienda revisar requisitos críticos, fechas, cumplimiento documental, condiciones de pago y restricciones técnicas antes de avanzar a propuesta.');

    $recoTextFx = $fxFirst([
      'resumen_ejecutivo.recomendacion_participacion.recomendacion',
      'dictamen.recomendacion_participacion.recomendacion',
      'dictamen.recomendacion',
      'recomendacion',
    ], $recomendacionFx ?: 'NO');

    $justificacionFx = $fxFirst([
      'resumen_ejecutivo.recomendacion_participacion.justificacion',
      'dictamen.recomendacion_participacion.justificacion',
      'dictamen.justificacion',
      'justificacion',
    ], 'La recomendación se basa en la revisión de cumplimiento, riesgo documental, restricciones técnicas, plazos operativos y condiciones económicas detectadas en las bases.');

    $condicionesFx = $fxFirst([
      'resumen_ejecutivo.recomendacion_participacion.condiciones_reconsiderar',
      'dictamen.recomendacion_participacion.condiciones_reconsiderar',
      'dictamen.condiciones_reconsiderar',
      'condiciones_reconsiderar',
    ], 'Para reconsiderar, se deben completar faltantes documentales, confirmar viabilidad técnica y validar fechas, entregables, condiciones de pago y alcances con el área responsable.');

    $riskLevelFx = $fxFirst([
      'resumen_ejecutivo.riesgos_ejecutivos.nivel_riesgo_global',
      'dictamen.riesgos_ejecutivos.nivel_riesgo_global',
      'dictamen.riesgo',
      'riesgo_general',
      'riesgo',
    ], $riesgoFx ?: 'ALTO');

    $riskSummaryFx = $fxFirst([
      'resumen_ejecutivo.riesgos_ejecutivos.resumen',
      'dictamen.riesgos_ejecutivos.resumen',
      'dictamen.resumen_riesgo',
      'riesgos.resumen',
    ], 'El proyecto presenta riesgos que deben revisarse antes de participar: requisitos obligatorios, evidencia documental, plazos, alcances, restricciones técnicas y condiciones financieras.');

    $fichaRows = [
      ['key' => 'ficha.numero_licitacion', 'question' => '¿Cuál es el número de la licitación, solicitud o procedimiento?', 'val' => $fxClean($ficha['numero_licitacion'] ?? null)],
      ['key' => 'ficha.tipo_evento', 'question' => '¿Cuál es el tipo de procedimiento y su modalidad?', 'val' => $tipoEventoFx],
      ['key' => 'ficha.caracter_procedimiento', 'question' => '¿El procedimiento es nacional, internacional o está cubierto por tratados?', 'val' => $fxClean($ficha['caracter_procedimiento'] ?? null)],
      ['key' => 'ficha.organismo', 'question' => '¿Cuál es el organismo y el área convocante específica?', 'val' => $organismoFx],
      ['key' => 'ficha.objeto_licitacion', 'question' => '¿Cuál es el objeto exacto de la licitación?', 'val' => $objetoFx],
      ['key' => 'ficha.tipo_contrato', 'question' => '¿El contrato será abierto o cerrado y cómo se determinarán las cantidades?', 'val' => $fxClean($ficha['tipo_contrato'] ?? null)],
      ['key' => 'ficha.forma_adjudicacion', 'question' => '¿Cómo se realizará la adjudicación: total, por partida, lote o abastecimiento simultáneo?', 'val' => $fxClean($ficha['forma_adjudicacion'] ?? null)],
      ['key' => 'ficha.medio_participacion', 'question' => '¿Cuál es el medio de participación (electrónica, presencial o mixta)?', 'val' => $fxClean($ficha['medio_participacion'] ?? null)],
      ['key' => 'ficha.plataforma', 'question' => '¿En qué plataforma o sistema debe presentarse la propuesta?', 'val' => $fxClean($ficha['plataforma'] ?? null)],
      ['key' => 'ficha.lugar_entrega', 'question' => '¿En qué lugares, almacenes o instituciones se realizará la entrega?', 'val' => $fxClean($ficha['lugar_entrega'] ?? null)],
      ['key' => 'ficha.plazo_entrega', 'question' => '¿Cuál es el plazo máximo para entregar los bienes o iniciar el servicio?', 'val' => $fxClean($ficha['plazo_entrega'] ?? null)],
      ['key' => 'ficha.vigencia_contrato', 'question' => '¿Cuál es la vigencia o duración del contrato?', 'val' => $fxClean($ficha['vigencia_contrato'] ?? null)],
      ['key' => 'ficha.moneda_pago', 'question' => '¿En qué moneda se realizará el pago?', 'val' => $fxClean($ficha['moneda_pago'] ?? null)],
      ['key' => 'ficha.condiciones_pago', 'question' => '¿Cuáles son las condiciones, plazo y forma de pago?', 'val' => $fxClean($ficha['condiciones_pago'] ?? null)],
      ['key' => 'ficha.garantia_bienes', 'question' => '¿Qué periodo de garantía, vicios ocultos o reposición debe ofrecerse?', 'val' => $fxClean($ficha['garantia_bienes'] ?? null)],
      ['key' => 'ficha.contenido_nacional', 'question' => '¿Se exige un porcentaje de contenido nacional y cómo debe acreditarse?', 'val' => $fxClean($ficha['contenido_nacional'] ?? null)],
      ['key' => 'ficha.subcontratacion', 'question' => '¿Está permitida la subcontratación, asociación o propuesta conjunta?', 'val' => $fxClean($ficha['subcontratacion'] ?? null)],
    ];

    $fechaValue = function (array $keys, $fallback = null) use ($fechas, $sd, $fxClean) {
      foreach ($keys as $key) {
        $value = $fxClean(data_get($fechas, $key));

        if ($value !== null && $value !== '') {
            return $value;
        }

        $value = $fxClean(data_get($sd, 'fechas_clave.' . $key));

        if ($value !== null && $value !== '') {
            return $value;
        }

        $value = $fxClean(data_get($sd, 'hitos_licitacion.' . $key));

        if ($value !== null && $value !== '') {
            return $value;
        }
      }

      return $fxClean($fallback);
    };

    $fechasRows = [
      [
        'key' => 'fechas_clave.emision_oficio_requerimiento',
        'question' => 'Emisión de oficio de requerimiento global de necesidades',
        'val' => $fechaValue(['emision_oficio_requerimiento', 'oficio_requerimiento_global', 'requerimiento_global_necesidades']),
      ],
      [
        'key' => 'fechas_clave.emision_oficio_disponibilidad_presupuestaria',
        'question' => 'Emisión de oficio de disponibilidad presupuestaria',
        'val' => $fechaValue(['emision_oficio_disponibilidad_presupuestaria', 'disponibilidad_presupuestaria']),
      ],
      [
        'key' => 'fechas_clave.fecha_publicacion',
        'question' => 'Publicación de la convocatoria',
        'val' => $fechaValue(['fecha_publicacion', 'publicacion_convocatoria', 'publicacion']),
      ],
      [
        'key' => 'fechas_clave.fecha_limite_aclaraciones',
        'question' => 'Fecha límite para solicitudes de aclaración (preguntas)',
        'val' => $fechaValue(['fecha_limite_aclaraciones', 'limite_aclaraciones', 'fecha_limite_preguntas', 'preguntas']),
      ],
      [
        'key' => 'fechas_clave.junta_aclaraciones',
        'question' => 'Junta de aclaraciones',
        'val' => $fechaValue(['junta_aclaraciones', 'junta_de_aclaraciones']),
      ],
      [
        'key' => 'fechas_clave.presentacion_apertura',
        'question' => 'Presentación y apertura de proposiciones',
        'val' => $fechaValue(['presentacion_apertura', 'presentacion_y_apertura', 'apertura_proposiciones']),
      ],
      [
        'key' => 'fechas_clave.fallo',
        'question' => 'Fallo',
        'val' => $fechaValue(['fallo', 'fecha_fallo']),
      ],
      [
        'key' => 'fechas_clave.notificacion_adjudicacion',
        'question' => 'Notificación de adjudicación o fallo',
        'val' => $fechaValue(['notificacion_adjudicacion', 'notificacion_fallo', 'fecha_notificacion_adjudicacion']),
      ],
      [
        'key' => 'fechas_clave.entrega_documentos_cotejo',
        'question' => 'Entrega de documentos para cotejo y elaboración del contrato',
        'val' => $fechaValue(['entrega_documentos_cotejo', 'cotejo_documental', 'documentos_para_contrato']),
      ],
      [
        'key' => 'fechas_clave.correccion_errores_adjudicacion',
        'question' => 'Corrección de errores en la notificación de adjudicación',
        'val' => $fechaValue(['correccion_errores_adjudicacion', 'correccion_notificacion', 'plazo_correccion_fallo']),
      ],
      [
        'key' => 'fechas_clave.firma_contrato',
        'question' => 'Firma del contrato',
        'val' => $fechaValue(['firma_contrato', 'fecha_firma_contrato']),
      ],
      [
        'key' => 'fechas_clave.inicio_servicio',
        'question' => 'Inicio del servicio/obra/suministro',
        'val' => $fechaValue(['inicio_servicio', 'inicio_obra', 'inicio_suministro', 'inicio_del_servicio_obra_suministro']),
      ],
      [
        'key' => 'fechas_clave.vigencia_contrato',
        'question' => 'Vigencia del contrato',
        'val' => $fechaValue(['vigencia_contrato', 'vigencia']),
      ],
    ];

    /*
    |--------------------------------------------------------------------------
    | Eventos y fechas dinámicas detectadas por la IA
    |--------------------------------------------------------------------------
    | Además de los hitos principales de fechas_clave, esta vista incorpora
    | TODOS los registros encontrados en eventos.vigencias y
    | eventos.plazos_ejecucion. No existe un límite fijo de elementos.
    */
    $eventosDinamicosFx = collect([
        'vigencias' => data_get($sd, 'eventos.vigencias', []),
        'plazos_ejecucion' => data_get($sd, 'eventos.plazos_ejecucion', []),
    ])->flatMap(function ($items, $group) use ($fxClean) {
        if (is_object($items)) {
            $items = (array) $items;
        }

        if (!is_array($items)) {
            return [];
        }

        return collect($items)->map(function ($item, $index) use ($group, $fxClean) {
            if (is_object($item)) {
                $item = (array) $item;
            }

            if (!is_array($item)) {
                return null;
            }

            $label = $fxClean(
                $item['label']
                    ?? $item['titulo']
                    ?? $item['nombre']
                    ?? $item['evento']
                    ?? $item['tipo']
                    ?? null
            );

            $value = $fxClean(
                $item['value']
                    ?? $item['valor']
                    ?? $item['fecha']
                    ?? $item['plazo']
                    ?? $item['vigencia']
                    ?? $item['respuesta']
                    ?? null
            );

            if (!$label || !$value) {
                return null;
            }

            return [
                'key' => 'eventos.' . $group . '.' . $index,
                'question' => $label,
                'val' => $value,
                'risk' => $fxClean($item['risk'] ?? $item['riesgo'] ?? null),
                'fuente' => $fxClean($item['fuente'] ?? $item['source'] ?? null),
                'pagina' => $item['pagina'] ?? $item['page'] ?? null,
                'cita' => $fxClean($item['cita'] ?? $item['quote'] ?? $item['evidencia'] ?? null),
                'group' => $group,
            ];
        })->filter()->values();
    })->values();

    /*
     * Se conservan primero los hitos principales y después se agregan todos
     * los eventos dinámicos. Se eliminan duplicados por título + valor.
     */
    $fechasRows = collect($fechasRows)
        ->concat($eventosDinamicosFx)
        ->filter(fn ($row) => filled($row['question'] ?? null) && filled($row['val'] ?? null))
        ->unique(function ($row) {
            return mb_strtolower(trim((string) ($row['question'] ?? '')), 'UTF-8')
                . '|'
                . mb_strtolower(trim((string) ($row['val'] ?? '')), 'UTF-8');
        })
        ->values()
        ->all();

    $fechasSinDato = collect($fechasRows)
        ->every(fn ($row) => blank($row['val'] ?? null));

    /*
    |--------------------------------------------------------------------------
    | Preguntas dinámicas del resumen ejecutivo
    |--------------------------------------------------------------------------
    | Se muestran TODAS las preguntas que regrese el procesador Python.
    | Esto evita limitar la vista a los siete campos fijos de ficha.
    */
    $resumenRawFx = data_get($sd, 'resumen_ejecutivo.preguntas', data_get($sd, 'resumen_ejecutivo', []));

    if (is_object($resumenRawFx)) {
        $resumenRawFx = (array) $resumenRawFx;
    }

    $resumenRowsFx = collect(is_array($resumenRawFx) ? $resumenRawFx : [])
        ->map(function ($item, $index) use ($fxClean) {
            if (is_object($item)) {
                $item = (array) $item;
            }

            if (!is_array($item)) {
                return null;
            }

            $question = $fxClean(
                $item['pregunta']
                    ?? $item['question']
                    ?? $item['titulo']
                    ?? null
            );

            $answer = $fxClean(
                $item['respuesta']
                    ?? $item['answer']
                    ?? $item['valor']
                    ?? $item['resultado']
                    ?? null
            );

            if (!$question) {
                return null;
            }

            return [
                'key' => 'resumen_ejecutivo.' . $index,
                'question' => $question,
                'val' => $answer,
            ];
        })
        ->filter()
        ->values();
  @endphp

  <div class="pjd-fx-shell pjd-fx-workspace">
    <div class="pjd-fx-scorebar">
      <button type="button" class="pjd-fx-score is-active">
        <span class="pjd-fx-score-value">{{ $plazosFx ?: 'MEDIO' }}</span>
        <span class="pjd-fx-score-label">Plazos</span>
        <span class="pjd-fx-score-icon is-yellow">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        </span>
      </button>

      <button type="button" class="pjd-fx-score">
        <span class="pjd-fx-score-value">{{ $matrizFx ?: 'ALTO' }}</span>
        <span class="pjd-fx-score-label">Matriz</span>
        <span class="pjd-fx-score-icon is-red">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/></svg>
        </span>
      </button>

      <button type="button" class="pjd-fx-score">
        <span class="pjd-fx-score-value">{{ $financieroFx ?: 'ALTO' }}</span>
        <span class="pjd-fx-score-label">Financiero</span>
        <span class="pjd-fx-score-icon is-red">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg>
        </span>
      </button>

      <button type="button" class="pjd-fx-score">
        <span class="pjd-fx-score-value">{{ $interesFx ?: 'ALTO' }}</span>
        <span class="pjd-fx-score-label">Interés</span>
        <span class="pjd-fx-score-icon is-green">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/></svg>
        </span>
      </button>

      <button type="button" class="pjd-fx-score">
        <span class="pjd-fx-score-value">{{ $cumplimientoFx }}%</span>
        <span class="pjd-fx-score-label">Cumplimiento</span>
        <span class="pjd-fx-score-icon is-orange">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 4 14h7l-1 8 10-13h-7l1-7z"/></svg>
        </span>
      </button>
    </div>

    <div class="pjd-fx-tools" aria-label="Acciones de ficha">
      <a href="{{ route('projects.ficha.word', $project) }}" class="pjd-fx-tool" title="Descargar Word">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
      </a>
      <button type="button" class="pjd-fx-tool" data-fx-expand id="pjdFxExpandBtn" title="Estirar vista" aria-label="Estirar vista" aria-expanded="false">
        <svg class="pjd-fx-icon-expand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="M21 3l-7 7"/><path d="M3 21l7-7"/></svg>
        <svg class="pjd-fx-icon-compress" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3v6H3"/><path d="M15 21v-6h6"/><path d="M3 9l7-7"/><path d="M21 15l-7 7"/></svg>
      </button>
    </div>

    <section class="pjd-fx-executive">
      <div class="pjd-fx-executive-head" data-fx-executive-toggle role="button" tabindex="0" aria-expanded="false">
        <h2 class="pjd-fx-hero-title">
          Resumen Ejecutivo
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
        </h2>
        <div class="pjd-fx-hero-pills">
          <span class="pjd-fx-pill">Recomendación: {{ $recomendacionFx ?: 'NO' }}</span>
          <span class="pjd-fx-pill">Riesgo: {{ $riesgoFx ?: 'ALTO' }}</span>
        </div>
      </div>

      <div class="pjd-fx-executive-body">
        <section class="pjd-fx-exec-section">
          <h3>Objeto y Dictamen de Alineación</h3>
          <p>{!! nl2br(e($executiveObjectFx)) !!}</p>

          <div class="pjd-fx-note">
            <span>Alineación con portafolio</span>
            <p>{!! nl2br(e($portfolioFx)) !!}</p>
          </div>

          <div class="pjd-fx-note is-soft">
            <span>Señal de viabilidad</span>
            <p>{!! nl2br(e($signalFx)) !!}</p>
          </div>
        </section>

        <section class="pjd-fx-exec-section">
          <h3>Recomendación de Participación</h3>
          <div class="pjd-fx-note is-danger">
            <span>Recomendación</span>
            <p>{{ $recoTextFx ?: 'NO' }}</p>
          </div>
          <div class="pjd-fx-note">
            <span>Justificación</span>
            <p>{!! nl2br(e($justificacionFx)) !!}</p>
          </div>
          <div class="pjd-fx-note">
            <span>Condiciones para reconsiderar</span>
            <p>{!! nl2br(e($condicionesFx)) !!}</p>
          </div>
        </section>

        <section class="pjd-fx-exec-section is-last">
          <h3>Riesgos Ejecutivos</h3>
          <div class="pjd-fx-note is-danger">
            <span>Nivel de riesgo global</span>
            <p>{{ $riskLevelFx ?: 'ALTO' }}</p>
          </div>
          <div class="pjd-fx-note">
            <span>Resumen</span>
            <p>{!! nl2br(e($riskSummaryFx)) !!}</p>
          </div>
        </section>
      </div>
    </section>

    <div class="pjd-fx-stack">
      <div class="pjd-card pjd-fx-card">
        <div class="pjd-card-head pjd-fx-card-head js-card-toggle">
          <h3 class="pjd-fx-card-title">Ficha de Resumen <span class="pjd-fx-sparkle" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.7 5.1L19 10l-5.3 1.9L12 17l-1.7-5.1L5 10l5.3-1.9L12 3Z"/><path d="M19 4v4"/><path d="M17 6h4"/><path d="M5 16v4"/><path d="M3 18h4"/></svg></span></h3>
          <span class="pjd-card-chev pjd-fx-chev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></span>
        </div>

        <div class="pjd-card-body pjd-fx-card-body">
          <div class="pjd-fx-qa-list">
            @foreach($fichaRows as $row)
              @php
                $payload = $citaPayload($citas, $row['key'], $row['val'] ?? null, $row['question'] ?? null);
                $citaInfo = $resolverCita($citas, $row['key'], $row['val'] ?? null, $row['question'] ?? null);
                $fuente = is_array($citaInfo) ? ($citaInfo['fuente'] ?? null) : null;
                $pagina = is_array($citaInfo) ? ($citaInfo['pagina'] ?? null) : null;
                $citaTexto = is_array($citaInfo) ? ($citaInfo['cita'] ?? null) : null;
                $docUrl = $fuente ? optional($project->documents->firstWhere('filename', $fuente))->url : null;
              @endphp
              <article class="pjd-field pjd-fx-qa {{ $payload ? 'has-cita' : 'has-no-cita' }}" @if($payload) data-cita="{{ $payload }}" @endif>
                <div class="pjd-fx-qa-main">
                  <h4>{{ mb_strtoupper($row['question']) }}</h4>
                  <p>{{ $row['val'] ?: 'Sin dato' }}</p>
                  @if($payload)
                    <div class="pjd-fx-cita"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg> Ver fuente</div>
                  @endif
                </div>
                <button type="button" class="pjd-fx-trash" title="Eliminar visual">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                </button>
                @include('projects.partials.analysis.source-panel', [
                  'payload' => $payload,
                  'citaTexto' => $citaTexto,
                  'fuente' => $fuente,
                  'pagina' => $pagina,
                  'docUrl' => $docUrl,
                  'emptyMessage' => 'No hay cita textual guardada para este dato. Ya se intentó buscar por clave y por coincidencia del texto en structured_data.citas. Si sigue apareciendo así, el backend/IA no guardó evidencia específica para este valor.'
                ])
              </article>
            @endforeach
          </div>
        </div>
      </div>

      @if($resumenRowsFx->isNotEmpty())
        <div class="pjd-card pjd-fx-card">
          <div class="pjd-card-head pjd-fx-card-head js-card-toggle">
            <h3 class="pjd-fx-card-title">
              Preguntas clave del análisis
              <span class="pjd-fx-pill" style="height:24px;padding:0 10px;border-color:#cfe0ff;background:#e6f0ff;color:#007aff;">
                {{ $resumenRowsFx->count() }} preguntas
              </span>
            </h3>
            <span class="pjd-card-chev pjd-fx-chev">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </span>
          </div>

          <div class="pjd-card-body pjd-fx-card-body">
            <div class="pjd-fx-qa-list">
              @foreach($resumenRowsFx as $row)
                @php
                  $payload = $citaPayload($citas, $row['key'], $row['val'] ?? null, $row['question'] ?? null);
                  $citaInfo = $resolverCita($citas, $row['key'], $row['val'] ?? null, $row['question'] ?? null);
                  $fuente = is_array($citaInfo) ? ($citaInfo['fuente'] ?? null) : null;
                  $pagina = is_array($citaInfo) ? ($citaInfo['pagina'] ?? null) : null;
                  $citaTexto = is_array($citaInfo) ? ($citaInfo['cita'] ?? null) : null;
                  $docUrl = $fuente ? optional($project->documents->firstWhere('filename', $fuente))->url : null;
                @endphp

                <article class="pjd-field pjd-fx-qa {{ $payload ? 'has-cita' : 'has-no-cita' }}" @if($payload) data-cita="{{ $payload }}" @endif>
                  <div class="pjd-fx-qa-main">
                    <h4>{{ mb_strtoupper($row['question'], 'UTF-8') }}</h4>
                    <p>{{ $row['val'] ?: 'No se encontró información' }}</p>

                    @if($payload)
                      <div class="pjd-fx-cita">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                        Ver fuente
                      </div>
                    @endif
                  </div>

                  <button type="button" class="pjd-fx-trash" title="Eliminar visual" aria-label="Eliminar pregunta">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                  </button>

                  @include('projects.partials.analysis.source-panel', [
                    'payload' => $payload,
                    'citaTexto' => $citaTexto,
                    'fuente' => $fuente,
                    'pagina' => $pagina,
                    'docUrl' => $docUrl,
                    'emptyMessage' => 'No hay cita textual guardada para esta respuesta. Reanaliza el proyecto para generar la evidencia correspondiente.'
                  ])
                </article>
              @endforeach
            </div>
          </div>
        </div>
      @endif

      <div class="pjd-card pjd-fx-card is-hitos">
        <div class="pjd-card-head pjd-fx-card-head js-card-toggle">
          <h3 class="pjd-fx-card-title">Hitos de licitación <span class="pjd-fx-sparkle" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.7 5.1L19 10l-5.3 1.9L12 17l-1.7-5.1L5 10l5.3-1.9L12 3Z"/><path d="M19 4v4"/><path d="M17 6h4"/><path d="M5 16v4"/><path d="M3 18h4"/></svg></span></h3>
          <span class="pjd-card-chev pjd-fx-chev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></span>
        </div>

        <div class="pjd-card-body pjd-fx-card-body">
          @if($fechasSinDato)
            <div class="pjd-ai-notice">
              <svg class="pjd-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
              <span>Este documento aún no contiene fechas detectadas.</span>
            </div>
          @endif

          <div class="pjd-fx-hitos-list">
            @foreach($fechasRows as $row)
              @php
                $payload = $citaPayload($citas, $row['key'], $row['val'] ?? null, $row['question'] ?? null);
                $citaInfo = $resolverCita($citas, $row['key'], $row['val'] ?? null, $row['question'] ?? null);
                $fuente = $row['fuente'] ?? (is_array($citaInfo) ? ($citaInfo['fuente'] ?? null) : null);
                $pagina = $row['pagina'] ?? (is_array($citaInfo) ? ($citaInfo['pagina'] ?? null) : null);
                $citaTexto = $row['cita'] ?? (is_array($citaInfo) ? ($citaInfo['cita'] ?? null) : null);

                if (!$payload && ($citaTexto || $fuente || $pagina)) {
                  $payload = base64_encode(json_encode([
                    'cita' => $citaTexto,
                    'fuente' => $fuente,
                    'pagina' => $pagina,
                  ], JSON_UNESCAPED_UNICODE));
                }

                $docUrl = $fuente ? optional($project->documents->firstWhere('filename', $fuente))->url : null;
              @endphp

              <article class="pjd-field pjd-fx-hito {{ $payload ? 'has-cita' : 'has-no-cita' }}" @if($payload) data-cita="{{ $payload }}" @endif>
                <div class="pjd-fx-hito-main">
                  <h4 class="pjd-fx-hito-title">{{ mb_strtoupper($row['question']) }}</h4>
                  <p class="pjd-fx-hito-date">{{ $row['val'] ?: 'Sin dato' }}</p>
                  @if(!empty($row['risk']))
                    <span class="pjd-fx-cita" style="text-decoration:none;">Riesgo: {{ mb_strtoupper($row['risk'], 'UTF-8') }}</span>
                  @endif
                  @if($payload)
                    <div class="pjd-fx-cita"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg> Ver fuente</div>
                  @endif
                </div>

                <button type="button" class="pjd-fx-hito-trash" title="Eliminar visual" aria-label="Eliminar hito">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                </button>

                @include('projects.partials.analysis.source-panel', [
                  'payload' => $payload,
                  'citaTexto' => $citaTexto,
                  'fuente' => $fuente,
                  'pagina' => $pagina,
                  'docUrl' => $docUrl,
                  'emptyMessage' => 'No hay cita textual guardada para este dato. Ya se intentó buscar por clave y por coincidencia del texto en structured_data.citas. Si sigue apareciendo así, el backend/IA no guardó evidencia específica para este valor.'
                ])
              </article>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const executive = document.querySelector('.pjd-fx-executive');
  const executiveHead = document.querySelector('[data-fx-executive-toggle]');
  const expandBtn = document.getElementById('pjdFxExpandBtn');
  const wrap = document.querySelector('.pjd-wrap');

  if (executive && executiveHead) {
    const toggleExecutive = function () {
      const isOpen = executive.classList.toggle('is-open');
      executiveHead.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    };

    executiveHead.addEventListener('click', function (event) {
      if (event.target.closest('a, button, form')) return;
      toggleExecutive();
    });

    executiveHead.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      event.preventDefault();
      toggleExecutive();
    });
  }

  if (expandBtn && wrap) {
    expandBtn.addEventListener('click', function () {
      const expanded = wrap.classList.toggle('is-ficha-expanded');
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
