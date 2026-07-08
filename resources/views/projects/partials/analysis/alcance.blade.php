@once
@push('styles')
<style>
  /* ==========================================================
     ALCANCE - CONTENEDOR AISLADO (VERSIÓN COMPACTA)
     Diseño ajustado exactamente a la referencia visual compacta.
     ========================================================== */

  #pjd-alcance-isolated-wrapper {
    display: block;
    width: 100%;
    position: relative;
  }

  #pjd-alcance-isolated-wrapper,
  #pjd-alcance-isolated-wrapper * {
    box-sizing: border-box;
  }

  #pjd-alcance-isolated-wrapper .pjd-pane[data-pane="alcance"] {
    width: 100%;
    padding: 12px 16px 20px;
    background: #ffffff;
    font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-shell {
    position: relative;
    width: min(100%, 1360px);
    margin: 8px auto 16px;
    padding: 16px 20px 20px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.02);
    overflow: hidden;
  }

  /* Estados globales (Expandir) */
  .pjd-wrap.is-alcance-expanded #pjd-alcance-isolated-wrapper .pjd-alcance-shell {
    width: calc(100% - 24px);
    max-width: none;
    margin-left: 12px;
    margin-right: 12px;
  }

  .pjd-wrap.is-alcance-expanded .pjd-body {
    grid-template-columns: 0 0 minmax(360px, 1fr) !important;
  }

  .pjd-wrap.is-alcance-expanded .pjd-left,
  .pjd-wrap.is-alcance-expanded .pjd-resizer {
    opacity: 0 !important;
    pointer-events: none !important;
    overflow: hidden !important;
    border: 0 !important;
  }

  /* =========================================
     HEADER (Métricas + Herramientas)
     ========================================= */
  #pjd-alcance-isolated-wrapper .pjd-alcance-header {
    display: flex;
    gap: 12px;
    align-items: stretch;
    margin-bottom: 16px;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-metrics {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
    flex: 1;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-metric {
    all: unset;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 68px; /* Reducido para ser más compacto */
    border: 1px solid #f3f4f6;
    border-radius: 8px;
    background: #ffffff;
    cursor: pointer;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
    transition: all 0.15s ease;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-metric:hover,
  #pjd-alcance-isolated-wrapper .pjd-alcance-metric.is-active {
    transform: translateY(-1px);
    border-color: #d1d5db;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-metric-value {
    color: #111827;
    font-size: 18px;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 2px;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-metric-label {
    color: #6b7280;
    font-size: 11px;
    font-weight: 500;
    margin-bottom: 2px;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-metric-icon svg {
    width: 14px;
    height: 14px;
  }

  /* Colores de Iconos en Métricas */
  #pjd-alcance-isolated-wrapper .is-blue { color: #3b82f6; }
  #pjd-alcance-isolated-wrapper .is-green { color: #10b981; }
  #pjd-alcance-isolated-wrapper .is-yellow { color: #eab308; }
  #pjd-alcance-isolated-wrapper .is-red { color: #ef4444; }
  #pjd-alcance-isolated-wrapper .is-purple { color: #6366f1; }

  /* Herramientas (Botones Laterales) */
  #pjd-alcance-isolated-wrapper .pjd-alcance-tools {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-tool {
    all: unset;
    width: 30px;
    height: 30px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: #ffffff;
    color: #6b7280;
    display: grid;
    place-items: center;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    transition: all 0.15s ease;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-tool:hover {
    border-color: #cbd5e1;
    color: #111827;
    background: #f8fafc;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-tool svg {
    width: 14px;
    height: 14px;
    fill: none !important;
    stroke: currentColor !important;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-icon-compress { display: none; }
  .pjd-wrap.is-alcance-expanded #pjd-alcance-isolated-wrapper .pjd-alcance-tool[data-alcance-expand] .pjd-alcance-icon-expand { display: none; }
  .pjd-wrap.is-alcance-expanded #pjd-alcance-isolated-wrapper .pjd-alcance-tool[data-alcance-expand] .pjd-alcance-icon-compress { display: block; }

  /* =========================================
     COMENTARIOS
     ========================================= */
  #pjd-alcance-isolated-wrapper .pjd-alcance-comments {
    margin-bottom: 16px;
    border: 1px solid #e9d5ff;
    border-radius: 8px;
    background: #fdfcff;
    overflow: hidden;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-comments-head {
    width: 100%;
    min-height: 42px;
    padding: 0 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    user-select: none;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-comments-title {
    margin: 0;
    color: #7c3aed;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-comments-title svg {
    width: 16px;
    height: 16px;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-chev {
    color: #7c3aed;
    display: flex;
    transition: transform 0.2s ease;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-comments.is-open .pjd-alcance-chev {
    transform: rotate(180deg);
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-comments-body {
    display: none;
    padding: 0 16px 16px;
    color: #5b21b6;
    font-size: 13px;
    line-height: 1.5;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-comments.is-open .pjd-alcance-comments-body {
    display: block;
  }

  /* =========================================
     BARRA DE BÚSQUEDA
     ========================================= */
  #pjd-alcance-isolated-wrapper .pjd-alcance-search-zone {
    margin-bottom: 20px;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-search {
    position: relative;
    width: min(100%, 650px);
    display: block;
    margin-bottom: 8px;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-search svg {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 16px;
    height: 16px;
    color: #9ca3af;
    pointer-events: none;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-search input {
    width: 100%;
    height: 38px;
    padding: 0 14px 0 38px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: #ffffff;
    color: #111827;
    font-family: inherit;
    font-size: 13px;
    outline: none;
    transition: all 0.15s ease;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-search input::placeholder {
    color: #9ca3af;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-search input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px #eff6ff;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-counter {
    color: #6b7280;
    font-size: 12px;
    font-weight: 400;
  }

  /* =========================================
     LISTA DE PARTIDAS
     ========================================= */
  #pjd-alcance-isolated-wrapper .pjd-alcance-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-item {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
    overflow: hidden;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-item:hover {
    border-color: #cbd5e1;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-item-head {
    padding: 12px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-title {
    margin: 0;
    color: #111827;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.3;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-ai {
    all: unset;
    color: #9333ea;
    display: grid;
    place-items: center;
    cursor: pointer;
    transition: transform 0.15s ease;
  }
  
  #pjd-alcance-isolated-wrapper .pjd-alcance-ai:hover { transform: scale(1.1); }
  #pjd-alcance-isolated-wrapper .pjd-alcance-ai svg { width: 16px; height: 16px; }

  /* Estilos del Select (Compacto) */
  #pjd-alcance-isolated-wrapper .pjd-alcance-select {
    min-width: 100px;
    height: 30px;
    padding: 0 24px 0 10px;
    border-radius: 6px;
    font-family: inherit;
    font-size: 11px;
    font-weight: 700;
    outline: none;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2310b981' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    background-size: 14px;
    transition: all 0.15s ease;
  }

  /* Variante: MATCH */
  #pjd-alcance-isolated-wrapper .pjd-alcance-select {
    background-color: #f0fdf4;
    border: 1px solid #a7f3d0;
    color: #16a34a;
  }

  /* Variante: DUDA */
  #pjd-alcance-isolated-wrapper .pjd-alcance-select.is-duda {
    background-color: #fefce8;
    border-color: #fde047;
    color: #ca8a04;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23ca8a04' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
  }

  /* Variante: SIN INT. */
  #pjd-alcance-isolated-wrapper .pjd-alcance-select.is-sin-int {
    background-color: #fef2f2;
    border-color: #fecaca;
    color: #dc2626;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23dc2626' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-muted-label {
    color: #6b7280;
    font-size: 13px;
    font-weight: 500;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-ghost {
    all: unset;
    color: #9ca3af;
    display: grid;
    place-items: center;
    cursor: pointer;
    transition: color 0.15s ease;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-ghost:hover { color: #4b5563; }
  #pjd-alcance-isolated-wrapper .pjd-alcance-ghost.is-danger:hover { color: #ef4444; }
  #pjd-alcance-isolated-wrapper .pjd-alcance-ghost svg { width: 16px; height: 16px; }

  #pjd-alcance-isolated-wrapper .pjd-alcance-item-chev {
    transition: transform 0.2s ease;
  }
  #pjd-alcance-isolated-wrapper .pjd-alcance-item.is-open .pjd-alcance-item-chev {
    transform: rotate(180deg);
  }

  /* Cuerpo de la Partida (Desplegable) */
  #pjd-alcance-isolated-wrapper .pjd-alcance-item-body {
    display: none;
    padding: 0 16px 16px;
    border-top: 1px solid #f3f4f6;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-item.is-open .pjd-alcance-item-body {
    display: grid;
    gap: 12px;
    margin-top: 12px;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 12px;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-box-title {
    margin: 0 0 6px;
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-box-text {
    margin: 0;
    color: #334155;
    font-size: 13px;
    line-height: 1.5;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-empty {
    padding: 24px;
    border: 1px dashed #cbd5e1;
    border-radius: 8px;
    color: #64748b;
    text-align: center;
    font-size: 14px;
  }

  #pjd-alcance-isolated-wrapper .pjd-alcance-item.is-hidden { display: none; }

  /* Responsive */
  @media (max-width: 1024px) {
    #pjd-alcance-isolated-wrapper .pjd-alcance-metrics {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  @media (max-width: 760px) {
    #pjd-alcance-isolated-wrapper .pjd-pane[data-pane="alcance"] { padding: 10px; }
    #pjd-alcance-isolated-wrapper .pjd-alcance-shell { padding: 14px; }
    #pjd-alcance-isolated-wrapper .pjd-alcance-header { flex-direction: column; }
    #pjd-alcance-isolated-wrapper .pjd-alcance-metrics { grid-template-columns: repeat(2, 1fr); }
    #pjd-alcance-isolated-wrapper .pjd-alcance-tools { flex-direction: row; justify-content: flex-end; }
    #pjd-alcance-isolated-wrapper .pjd-alcance-item-head { flex-direction: column; align-items: flex-start; gap: 10px; }
    #pjd-alcance-isolated-wrapper .pjd-alcance-actions { width: 100%; justify-content: space-between; }
  }
</style>
@endpush
@endonce

<!-- CONTENEDOR MAESTRO AISLADO -->
<div id="pjd-alcance-isolated-wrapper">
  <div class="pjd-pane" data-pane="alcance">
    @php
      $alcanceClean = function ($value) {
        if (is_null($value)) return null;

        if (is_array($value) || is_object($value)) {
          $value = collect((array) $value)->filter()->implode(' ');
        }

        $value = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)));
        return $value !== '' ? $value : null;
      };

      $alcanceStatus = function ($value) use ($alcanceClean) {
        $raw = mb_strtoupper($alcanceClean($value) ?: '', 'UTF-8');
        $raw = str_replace(['Á','É','Í','Ó','Ú'], ['A','E','I','O','U'], $raw);

        if (str_contains($raw, 'SIN') || str_contains($raw, 'ROJO') || str_contains($raw, 'NO MATCH') || str_contains($raw, 'NO APLICA')) {
          return 'SIN INT.';
        }

        if (str_contains($raw, 'DUDA') || str_contains($raw, 'AMARILLO') || str_contains($raw, 'REVISION') || str_contains($raw, 'REVISAR')) {
          return 'DUDA';
        }

        return 'MATCH';
      };

      $alcanceSourceRows = collect(data_get($sd, 'alcance.partidas', []));

      if ($alcanceSourceRows->isEmpty()) {
        $alcanceSourceRows = collect(data_get($sd, 'partidas', $partidas ?? []));
      }

      if ($alcanceSourceRows->isEmpty()) {
        $alcanceSourceRows = collect(data_get($sd, 'alcance.items', []));
      }

      // Estructura de ejemplo si no hay datos para igualar la imagen
      if ($alcanceSourceRows->isEmpty()) {
        $alcanceSourceRows = collect([
          ['numero' => '1', 'titulo' => 'Adquisicion de Articulos de Oficina y Papeleria', 'status' => 'MATCH', 'dictamen' => 'Cumple con lo solicitado.', 'descripcion' => 'Materiales de oficina para uso interno.']
        ]);
      }

      $alcanceRows = $alcanceSourceRows->map(function ($row, $index) use ($alcanceClean, $alcanceStatus) {
        $row = is_array($row) ? $row : (array) $row;

        $numero = $row['numero']
          ?? $row['partida']
          ?? $row['no_partida']
          ?? $row['item']
          ?? ($index + 1);

        $titulo = $alcanceClean(
          $row['titulo']
          ?? $row['nombre']
          ?? $row['concepto']
          ?? $row['descripcion_corta']
          ?? $row['resumen']
          ?? null
        );

        $descripcion = $alcanceClean(
          $row['descripcion']
          ?? $row['descripcion_larga']
          ?? $row['objeto']
          ?? $row['detalle']
          ?? $row['texto']
          ?? null
        );

        $titulo = $titulo ?: ($descripcion ? mb_substr($descripcion, 0, 92, 'UTF-8') : 'Partida sin descripción');

        $dictamen = $alcanceClean(
          $row['dictamen_comercial']
          ?? $row['dictamen']
          ?? $row['analisis']
          ?? $row['comentario']
          ?? $row['observaciones']
          ?? null
        );

        $status = $alcanceStatus(
          $row['estatus']
          ?? $row['status']
          ?? $row['match']
          ?? $row['interes']
          ?? $row['nivel_interes']
          ?? $row['semaforo']
          ?? 'MATCH'
        );

        return [
          'numero' => $numero,
          'titulo' => $titulo,
          'descripcion' => $descripcion ?: 'Sin descripción detectada.',
          'dictamen' => $dictamen ?: 'Pendiente de dictamen comercial. Revisa manualmente la capacidad técnica, operativa y financiera antes de confirmar participación.',
          'status' => $status,
        ];
      })->values();

      $totalPartidas = $alcanceRows->count();
      $interestCount = $alcanceRows->where('status', 'MATCH')->count();
      $doubtCount = $alcanceRows->where('status', 'DUDA')->count();
      $noInterestCount = $alcanceRows->where('status', 'SIN INT.')->count();
      $generalLevel = $interestCount > 0 ? 'ALTO' : ($doubtCount > 0 ? 'MEDIO' : 'BAJO');

      $commentsText = $alcanceClean(data_get($sd, 'alcance.comentarios'))
        ?: $alcanceClean(data_get($sd, 'alcance.comentario_general'))
        ?: $alcanceClean(data_get($sd, 'comentarios_alcance'))
        ?: 'Revisión general del alcance comercial. Valida las partidas con MATCH, revisa las marcadas como DUDA y descarta las partidas SIN INT. antes de avanzar con la propuesta.';
    @endphp

    <div class="pjd-alcance-shell">
      
      <!-- HEADER METRICS & TOOLS -->
      <div class="pjd-alcance-header">
        <div class="pjd-alcance-metrics">
          
          <button type="button" class="pjd-alcance-metric is-active" data-alcance-filter="all">
            <span class="pjd-alcance-metric-value">{{ $totalPartidas }}</span>
            <span class="pjd-alcance-metric-label">Totales</span>
            <span class="pjd-alcance-metric-icon is-blue">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="8" y1="6" x2="21" y2="6"></line>
                <line x1="8" y1="12" x2="21" y2="12"></line>
                <line x1="8" y1="18" x2="21" y2="18"></line>
                <polyline points="3 6 4 7 6 5"></polyline>
                <polyline points="3 12 4 13 6 11"></polyline>
                <polyline points="3 18 4 19 6 17"></polyline>
              </svg>
            </span>
          </button>

          <button type="button" class="pjd-alcance-metric" data-alcance-filter="MATCH">
            <span class="pjd-alcance-metric-value">{{ $interestCount }}</span>
            <span class="pjd-alcance-metric-label">Interés</span>
            <span class="pjd-alcance-metric-icon is-green">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <circle cx="12" cy="12" r="6"></circle>
                <circle cx="12" cy="12" r="2"></circle>
              </svg>
            </span>
          </button>

          <button type="button" class="pjd-alcance-metric" data-alcance-filter="DUDA">
            <span class="pjd-alcance-metric-value">{{ $doubtCount }}</span>
            <span class="pjd-alcance-metric-label">Duda</span>
            <span class="pjd-alcance-metric-icon is-yellow">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
              </svg>
            </span>
          </button>

          <button type="button" class="pjd-alcance-metric" data-alcance-filter="SIN INT.">
            <span class="pjd-alcance-metric-value">{{ $noInterestCount }}</span>
            <span class="pjd-alcance-metric-label">Sin Int.</span>
            <span class="pjd-alcance-metric-icon is-red">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
              </svg>
            </span>
          </button>

          <button type="button" class="pjd-alcance-metric" data-alcance-filter="all">
            <span class="pjd-alcance-metric-value">{{ $generalLevel }}</span>
            <span class="pjd-alcance-metric-label">General</span>
            <span class="pjd-alcance-metric-icon is-purple">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <polyline points="9 12 12 15 16 9"></polyline>
              </svg>
            </span>
          </button>

        </div>

        <div class="pjd-alcance-tools" aria-label="Acciones de alcance">
          <button type="button" class="pjd-alcance-tool" title="Descargar alcance" aria-label="Descargar alcance" onclick="window.print()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
          </button>
          <button type="button" class="pjd-alcance-tool" data-alcance-expand id="pjdAlcanceExpandBtn" title="Estirar vista" aria-label="Estirar vista" aria-expanded="false">
            <svg class="pjd-alcance-icon-expand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"></path><path d="M9 21H3v-6"></path><path d="M21 3l-7 7"></path><path d="M3 21l7-7"></path></svg>
            <svg class="pjd-alcance-icon-compress" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3v6H3"></path><path d="M15 21v-6h6"></path><path d="M3 9l7-7"></path><path d="M21 15l-7 7"></path></svg>
          </button>
        </div>
      </div>

      <!-- COMENTARIOS -->
      <section class="pjd-alcance-comments">
        <div class="pjd-alcance-comments-head" data-alcance-comments-toggle role="button" tabindex="0" aria-expanded="false">
          <h2 class="pjd-alcance-comments-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="16" y1="13" x2="8" y2="13"></line>
              <line x1="16" y1="17" x2="8" y2="17"></line>
              <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            Comentarios
          </h2>
          <span class="pjd-alcance-chev"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"></path></svg></span>
        </div>
        <div class="pjd-alcance-comments-body">
          <p>{{ $commentsText }}</p>
        </div>
      </section>

      <!-- BÚSQUEDA -->
      <div class="pjd-alcance-search-zone">
        <label class="pjd-alcance-search" for="pjdAlcanceSearch">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          <input id="pjdAlcanceSearch" type="search" placeholder="Buscar por partida, numero o descripcion..." autocomplete="off">
        </label>
        <div class="pjd-alcance-counter" id="pjdAlcanceCounter">Mostrando {{ $totalPartidas }} partida{{ $totalPartidas === 1 ? '' : 's' }} de {{ $totalPartidas }}.</div>
      </div>

      <!-- LISTA DE PARTIDAS -->
      <div class="pjd-alcance-list" id="pjdAlcanceList">
        @forelse($alcanceRows as $row)
          @php
            $selectClass = $row['status'] === 'DUDA' ? 'is-duda' : ($row['status'] === 'SIN INT.' ? 'is-sin-int' : '');
            $searchText = mb_strtolower($row['numero'].' '.$row['titulo'].' '.$row['descripcion'].' '.$row['dictamen'].' '.$row['status'], 'UTF-8');
          @endphp
          <article class="pjd-alcance-item" data-alcance-card data-status="{{ $row['status'] }}" data-search="{{ e($searchText) }}">
            <div class="pjd-alcance-item-head">
              <h3 class="pjd-alcance-title">Partida {{ $row['numero'] }}: {{ $row['titulo'] }}</h3>

              <div class="pjd-alcance-actions">
                <button type="button" class="pjd-alcance-ai" title="Analizar con IA" aria-label="Analizar con IA">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.7 5.1L19 10l-5.3 1.9L12 17l-1.7-5.1L5 10l5.3-1.9L12 3Z"></path><path d="M19 4v4"></path><path d="M17 6h4"></path><path d="M5 16v4"></path><path d="M3 18h4"></path></svg>
                </button>

                <select class="pjd-alcance-select {{ $selectClass }}" aria-label="Nivel de interés">
                  <option value="MATCH" {{ $row['status'] === 'MATCH' ? 'selected' : '' }}>MATCH</option>
                  <option value="DUDA" {{ $row['status'] === 'DUDA' ? 'selected' : '' }}>DUDA</option>
                  <option value="SIN INT." {{ $row['status'] === 'SIN INT.' ? 'selected' : '' }}>SIN INT.</option>
                </select>

                <span class="pjd-alcance-muted-label">Partida</span>

                <button type="button" class="pjd-alcance-ghost is-danger" data-alcance-delete title="Eliminar visual" aria-label="Eliminar visual">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </button>

                <button type="button" class="pjd-alcance-ghost" data-alcance-toggle title="Expandir/Contraer partida" aria-label="Expandir/Contraer partida" aria-expanded="false">
                  <span class="pjd-alcance-item-chev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"></path></svg></span>
                </button>
              </div>
            </div>

            <div class="pjd-alcance-item-body">
              <div class="pjd-alcance-box">
                <h4 class="pjd-alcance-box-title">Dictamen comercial</h4>
                <p class="pjd-alcance-box-text">{{ $row['dictamen'] }}</p>
              </div>

              <div class="pjd-alcance-box">
                <h4 class="pjd-alcance-box-title">Descripción</h4>
                <p class="pjd-alcance-box-text">{{ $row['descripcion'] }}</p>
              </div>
            </div>
          </article>
        @empty
          <div class="pjd-alcance-empty">No hay partidas de alcance detectadas.</div>
        @endforelse
      </div>
    </div>
  </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const root = document.getElementById('pjd-alcance-isolated-wrapper');
  if (!root) return;

  const wrap = document.querySelector('.pjd-wrap');
  const expandBtn = root.querySelector('#pjdAlcanceExpandBtn');
  const comments = root.querySelector('.pjd-alcance-comments');
  const commentsHead = root.querySelector('[data-alcance-comments-toggle]');
  const searchInput = root.querySelector('#pjdAlcanceSearch');
  const counter = root.querySelector('#pjdAlcanceCounter');
  const cards = Array.from(root.querySelectorAll('[data-alcance-card]'));
  const metricButtons = Array.from(root.querySelectorAll('[data-alcance-filter]'));
  let activeFilter = 'all';

  function normalizeText(value) {
    return String(value || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');
  }

  function updateCounter(visible) {
    if (!counter) return;
    const total = cards.length;
    counter.textContent = 'Mostrando ' + visible + ' partida' + (visible === 1 ? '' : 's') + ' de ' + total + '.';
  }

  function applyFilters() {
    const query = normalizeText(searchInput ? searchInput.value : '');
    let visible = 0;

    cards.forEach(function (card) {
      const status = card.getAttribute('data-status') || '';
      const text = normalizeText(card.getAttribute('data-search') || '');
      const matchesStatus = activeFilter === 'all' || status === activeFilter;
      const matchesQuery = query === '' || text.includes(query);
      const show = matchesStatus && matchesQuery;

      card.classList.toggle('is-hidden', !show);
      if (show) visible += 1;
    });

    updateCounter(visible);
  }

  if (expandBtn && wrap) {
    expandBtn.addEventListener('click', function () {
      const expanded = wrap.classList.toggle('is-alcance-expanded');
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

  metricButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      activeFilter = button.getAttribute('data-alcance-filter') || 'all';
      metricButtons.forEach(function (btn) { btn.classList.remove('is-active'); });
      button.classList.add('is-active');
      applyFilters();
    });
  });

  if (searchInput) {
    searchInput.addEventListener('input', applyFilters);
  }

  root.querySelectorAll('[data-alcance-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
      const card = button.closest('[data-alcance-card]');
      if (!card) return;
      const isOpen = card.classList.toggle('is-open');
      button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
  });

  root.querySelectorAll('[data-alcance-delete]').forEach(function (button) {
    button.addEventListener('click', function () {
      const card = button.closest('[data-alcance-card]');
      if (!card) return;
      card.classList.add('is-hidden');
      updateCounter(cards.filter(function (item) { return !item.classList.contains('is-hidden'); }).length);
    });
  });

  root.querySelectorAll('.pjd-alcance-select').forEach(function (select) {
    select.addEventListener('change', function () {
      select.classList.remove('is-duda', 'is-sin-int');
      if (select.value === 'DUDA') {
        select.classList.add('is-duda');
      } else if (select.value === 'SIN INT.') {
        select.classList.add('is-sin-int');
      }

      const card = select.closest('[data-alcance-card]');
      if (card) {
        card.setAttribute('data-status', select.value);
        let currentSearch = card.getAttribute('data-search') || '';
        currentSearch += ' ' + select.value.toLowerCase();
        card.setAttribute('data-search', currentSearch);
      }
      
      applyFilters();
    });
  });

  applyFilters();
});
</script>
@endpush
@endonce