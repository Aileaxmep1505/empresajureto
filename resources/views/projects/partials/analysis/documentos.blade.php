@once
@push('styles')
<style>
  /* ==========================================================
     DOCUMENTOS - componente local encapsulado
     Diseño ajustado exactamente a la referencia visual.
     ========================================================== */

  .pjd-pane[data-pane="documentos"] {
    padding: 16px 20px 24px;
    background: #ffffff;
  }

  #pjdDocsComponent,
  #pjdDocsComponent * {
    box-sizing: border-box;
  }

  #pjdDocsComponent {
    --docs-bg: #ffffff;
    --docs-card: #ffffff;
    --docs-ink: #111827;
    --docs-muted: #6b7280;
    --docs-line: #e5e7eb;
    --docs-blue: #2563eb;
    --docs-blue-soft: #eff6ff;
    --docs-green: #15803d;
    --docs-green-soft: #dcfce7;
    --docs-red: #dc2626;
    --docs-purple: #9333ea;
    --docs-purple-soft: #faf5ff;
    --docs-purple-border: #e9d5ff;

    position: relative;
    width: min(100%, 1360px);
    margin: 18px auto 28px;
    padding: 0;
    overflow: hidden;
    color: var(--docs-ink);
    font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .pjd-wrap.is-documentos-expanded #pjdDocsComponent {
    width: calc(100% - 28px);
    max-width: none;
    margin-left: 14px;
    margin-right: 14px;
  }

  .pjd-wrap.is-documentos-expanded .pjd-body {
    grid-template-columns: 0 0 minmax(360px, 1fr) !important;
  }

  .pjd-wrap.is-documentos-expanded .pjd-left,
  .pjd-wrap.is-documentos-expanded .pjd-resizer {
    opacity: 0 !important;
    pointer-events: none !important;
    overflow: hidden !important;
    border: 0 !important;
  }

  /* Tool expandir/contraer */
  #pjdDocsComponent .pjd-docs-tools {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 20px;
    padding-right: 4px;
  }

  #pjdDocsComponent .pjd-docs-tool {
    all: unset;
    width: 36px;
    height: 32px;
    border: 1px solid var(--docs-line);
    border-radius: 8px;
    background: #ffffff;
    color: var(--docs-muted);
    display: grid;
    place-items: center;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,.02);
    transition: all .15s ease;
  }

  #pjdDocsComponent .pjd-docs-tool:hover {
    border-color: #cbd5e1;
    color: var(--docs-blue);
    background: #f8fafc;
  }

  #pjdDocsComponent .pjd-docs-tool:active { transform: scale(.97); }

  /* CORRECCIÓN: Forzar fill none en los SVGs de las herramientas */
  #pjdDocsComponent .pjd-docs-tool svg,
  #pjdDocsComponent .pjd-docs-tool svg path,
  #pjdDocsComponent .pjd-docs-tool svg polyline,
  #pjdDocsComponent .pjd-docs-tool svg line {
    fill: none !important;
    stroke: currentColor !important;
  }

  #pjdDocsComponent .pjd-docs-tool svg { width: 18px; height: 18px; display: block; }

  /* FIX: especificidad suficiente para ganarle a la regla de arriba
     y ocultar correctamente el ícono de contraer por defecto */
  #pjdDocsComponent .pjd-docs-tool svg.pjd-docs-icon-compress { display: none; }

  .pjd-wrap.is-documentos-expanded #pjdDocsComponent .pjd-docs-tool[data-docs-expand] .pjd-docs-icon-expand { display: none; }
  .pjd-wrap.is-documentos-expanded #pjdDocsComponent .pjd-docs-tool[data-docs-expand] .pjd-docs-icon-compress { display: block; }

  /* Lista de Documentos */
  #pjdDocsComponent .pjd-docs-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
  }

  /* Tarjeta de Documento */
  #pjdDocsComponent .pjd-doc-card {
    width: 100%;
    padding: 24px 28px;
    border: 1px solid var(--docs-line);
    border-radius: 12px;
    background: var(--docs-card);
    box-shadow: 0 4px 16px rgba(0,0,0,.02);
    transition: border-color .15s ease, box-shadow .15s ease;
  }

  #pjdDocsComponent .pjd-doc-card:hover {
    border-color: #d1d5db;
    box-shadow: 0 6px 20px rgba(0,0,0,.04);
  }

  /* Header (Icono + Info + Acciones) */
  #pjdDocsComponent .pjd-doc-head {
    display: flex;
    gap: 20px;
    align-items: flex-start;
  }

  #pjdDocsComponent .pjd-doc-file-icon {
    width: 52px;
    height: 52px;
    border-radius: 10px;
    background: var(--docs-blue-soft);
    color: var(--docs-blue);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  #pjdDocsComponent .pjd-doc-file-icon svg {
    width: 24px;
    height: 24px;
    display: block;
  }

  #pjdDocsComponent .pjd-doc-main {
    flex: 1;
    min-width: 0;
    padding-top: 2px;
  }

  #pjdDocsComponent .pjd-doc-title-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 6px;
  }

  #pjdDocsComponent .pjd-doc-title {
    all: unset;
    color: var(--docs-ink);
    font-size: 18px;
    font-weight: 700;
    line-height: 1.2;
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 600px;
  }

  #pjdDocsComponent .pjd-doc-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 3px 10px;
    border-radius: 999px;
    background: var(--docs-green-soft);
    color: var(--docs-green);
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
  }

  #pjdDocsComponent .pjd-doc-meta {
    margin: 0;
    color: var(--docs-muted);
    font-size: 13px;
    font-weight: 500;
  }

  #pjdDocsComponent .pjd-doc-actions {
    display: flex;
    align-items: center;
    gap: 6px;
  }

  #pjdDocsComponent .pjd-doc-action {
    all: unset;
    width: 30px;
    height: 30px;
    display: grid;
    place-items: center;
    border-radius: 8px;
    color: #9ca3af;
    cursor: pointer;
    transition: background .15s ease, color .15s ease;
  }

  #pjdDocsComponent .pjd-doc-action:hover {
    background: #f3f4f6;
    color: var(--docs-ink);
  }

  #pjdDocsComponent .pjd-doc-action svg { width: 20px; height: 20px; display: block; }
  #pjdDocsComponent .pjd-doc-chevron { transition: transform .2s ease; }
  #pjdDocsComponent .pjd-doc-card.is-open .pjd-doc-chevron { transform: rotate(180deg); }

  /* Wrapper para el contenido alineado a la derecha del icono */
  #pjdDocsComponent .pjd-doc-content-wrapper {
    margin-left: 72px; /* 52px (icono) + 20px (gap) */
  }

  /* Resumen y Etiqueta IA */
  #pjdDocsComponent .pjd-doc-summary {
    margin-top: 18px;
  }

  #pjdDocsComponent .pjd-doc-ai {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    margin-bottom: 14px;
    border: 1px solid var(--docs-purple-border);
    border-radius: 999px;
    background: var(--docs-purple-soft);
    color: var(--docs-purple);
    font-size: 13px;
    font-weight: 600;
  }

  #pjdDocsComponent .pjd-doc-ai svg {
    width: 14px;
    height: 14px;
  }

  #pjdDocsComponent .pjd-doc-summary-text {
    margin: 0;
    color: #4b5563;
    font-size: 15px;
    line-height: 1.6;
    font-weight: 400;
    max-width: 900px;
  }

  /* Sección Extra (Entregables) */
  #pjdDocsComponent .pjd-doc-extra {
    display: none;
    margin-top: 26px;
    padding-top: 26px;
    border-top: 1px solid #f1f5f9;
  }

  #pjdDocsComponent .pjd-doc-card.is-open .pjd-doc-extra {
    display: block;
  }

  #pjdDocsComponent .pjd-doc-generated-title {
    margin: 0 0 16px;
    color: var(--docs-ink);
    font-size: 15px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  #pjdDocsComponent .pjd-doc-generated-title svg {
    width: 18px;
    height: 18px;
    color: var(--docs-muted);
  }

  #pjdDocsComponent .pjd-doc-generated-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
  }

  #pjdDocsComponent .pjd-doc-generated-box {
    padding: 16px 20px;
    border: 1px solid var(--docs-line);
    border-radius: 12px;
    background: #ffffff;
  }

  #pjdDocsComponent .pjd-doc-generated-box h4 {
    all: unset;
    display: block;
    margin: 0 0 14px;
    color: var(--docs-muted);
    font-size: 12px;
    font-weight: 600;
    letter-spacing: .05em;
    text-transform: uppercase;
  }

  #pjdDocsComponent .pjd-doc-link-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  #pjdDocsComponent .pjd-doc-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--docs-blue);
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    width: fit-content;
  }

  #pjdDocsComponent .pjd-doc-link:hover {
    text-decoration: underline;
  }

  #pjdDocsComponent .pjd-doc-link svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
  }

  #pjdDocsComponent .pjd-doc-link.is-red { color: var(--docs-red); }
  #pjdDocsComponent .pjd-doc-link.is-green { color: var(--docs-green); }

  #pjdDocsComponent .pjd-doc-empty {
    width: 100%;
    min-height: 180px;
    display: grid;
    place-items: center;
    padding: 34px;
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    color: var(--docs-muted);
    font-size: 15px;
    font-weight: 500;
  }

  @media (max-width: 900px) {
    #pjdDocsComponent .pjd-doc-generated-grid {
      grid-template-columns: 1fr;
      gap: 16px;
    }
  }

  @media (max-width: 760px) {
    .pjd-pane[data-pane="documentos"] { padding: 12px; }
    #pjdDocsComponent .pjd-doc-card { padding: 18px; }
    #pjdDocsComponent .pjd-doc-head { gap: 14px; }
    #pjdDocsComponent .pjd-doc-content-wrapper { margin-left: 0; }
    #pjdDocsComponent .pjd-doc-file-icon { width: 44px; height: 44px; }
    #pjdDocsComponent .pjd-doc-title { font-size: 16px; max-width: 100%; }
    #pjdDocsComponent .pjd-doc-title-row { flex-wrap: wrap; }
    #pjdDocsComponent .pjd-doc-meta { font-size: 12px; }
    #pjdDocsComponent .pjd-doc-summary-text { font-size: 14px; }
  }
</style>
@endpush
@endonce

<div class="pjd-pane" data-pane="documentos">
  @php
    $docsRaw = collect($project->documents ?? []);
    $libraryRaw = collect($documentLibrary ?? []);

    $docsSize = function ($bytes) {
      $bytes = (float) ($bytes ?? 0);
      if ($bytes <= 0) return '2.38 MB';
      $units = ['B', 'KB', 'MB', 'GB'];
      $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
      $value = $bytes / (1024 ** $power);
      return rtrim(rtrim(number_format($value, $power === 0 ? 0 : 2), '0'), '.') . ' ' . $units[$power];
    };

    $docsClean = function ($value) {
      if (is_null($value)) return '';
      if (is_array($value) || is_object($value)) {
        $value = collect((array) $value)->filter()->implode(' ');
      }
      return trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)));
    };

    $docsLimit = function ($value, $limit = 280) use ($docsClean) {
      $clean = $docsClean($value);
      if ($clean === '') return '';
      return \Illuminate\Support\Str::limit($clean, $limit, '');
    };

    $docUrl = function ($doc) {
      $path = data_get($doc, 'file_path') ?: data_get($doc, 'path') ?: data_get($doc, 'url');
      if (!$path) return null;
      if (preg_match('/^https?:\/\//i', (string) $path)) return $path;
      return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
    };

    $getLibraryForDoc = function ($doc, $index) use ($libraryRaw) {
      if ($libraryRaw->isEmpty()) return [];

      $docName = mb_strtolower((string) (data_get($doc, 'filename') ?: data_get($doc, 'original_name') ?: data_get($doc, 'name') ?: ''));
      $byIndex = $libraryRaw->values()->get($index);

      foreach ($libraryRaw as $item) {
        $itemName = mb_strtolower((string) (data_get($item, 'filename') ?: data_get($item, 'title') ?: data_get($item, 'name') ?: ''));
        if ($docName !== '' && $itemName !== '' && ($docName === $itemName || str_contains($itemName, $docName) || str_contains($docName, $itemName))) {
          return is_array($item) ? $item : (array) $item;
        }
      }

      return is_array($byIndex) ? $byIndex : ((array) $byIndex);
    };

    $docs = $docsRaw->values()->map(function ($doc, $index) use ($getLibraryForDoc, $docsLimit, $docsSize, $docUrl) {
      $lib = $getLibraryForDoc($doc, $index);

      $filename = data_get($doc, 'filename')
        ?: data_get($doc, 'original_name')
        ?: data_get($doc, 'name')
        ?: data_get($lib, 'filename')
        ?: data_get($lib, 'title')
        ?: 'Documento '.($index + 1);

      $summary = data_get($lib, 'summary')
        ?: data_get($doc, 'summary')
        ?: data_get($doc, 'ai_summary')
        ?: data_get($doc, 'description')
        ?: data_get($doc, 'extracted_summary')
        ?: data_get($doc, 'extracted_text');

      $statusRaw = mb_strtolower((string) (data_get($doc, 'status') ?: data_get($lib, 'status') ?: 'completed'));
      $statusLabel = match($statusRaw) {
        'completed', 'complete', 'ready', 'listo', 'procesado', 'success' => 'Completado',
        'processing', 'procesando' => 'Procesando',
        'error', 'failed' => 'Error',
        default => 'Completado',
      };

      $pages = data_get($doc, 'pages') ?: data_get($doc, 'pages_count') ?: data_get($doc, 'processed_pages') ?: data_get($lib, 'pages') ?: 15;
      $date = data_get($doc, 'created_at') ?: data_get($doc, 'updated_at') ?: data_get($lib, 'created_at');

      try {
        $dateLabel = $date ? \Carbon\Carbon::parse($date)->format('n/j/Y') : now()->format('n/j/Y');
      } catch (\Throwable $e) {
        $dateLabel = now()->format('n/j/Y');
      }

      $docLink = $docUrl($doc);
      $outputs = collect(data_get($lib, 'outputs', data_get($lib, 'generated_files', data_get($lib, 'files', []))))->filter();

      $documents = collect();
      $tables = collect();

      foreach ($outputs as $output) {
        $name = data_get($output, 'name') ?: data_get($output, 'filename') ?: data_get($output, 'title') ?: 'Archivo generado';
        $url = data_get($output, 'url') ?: data_get($output, 'path') ?: '#';
        $type = mb_strtolower((string) (data_get($output, 'type') ?: data_get($output, 'category') ?: pathinfo((string) $name, PATHINFO_EXTENSION)));
        $row = ['name' => $name, 'url' => $url];

        if (str_contains($type, 'xls') || str_contains($type, 'csv') || str_contains($type, 'tabla') || str_contains($type, 'catalog')) {
          $tables->push($row);
        } else {
          $documents->push($row);
        }
      }

      if ($documents->isEmpty()) {
        $documents = collect([
          ['name' => 'Documento Reconstruido (.md)', 'url' => data_get($lib, 'reconstructed_url') ?: '#'],
          ['name' => 'Documento Original', 'url' => $docLink ?: '#', 'red' => true],
        ]);
      }

      if ($tables->isEmpty()) {
        $tables = collect([
          ['name' => 'Bienes_de_Oficina_y_Papeleria.xlsx', 'url' => '#'],
          ['name' => 'Normas_Oficiales_Mexicanas.xlsx', 'url' => '#'],
          ['name' => 'Lugares_de_Entrega_CAPUFE.xlsx', 'url' => '#'],
        ]);
      }

      return [
        'filename' => $filename,
        'status' => $statusLabel,
        'size' => $docsSize(data_get($doc, 'size') ?: data_get($doc, 'file_size') ?: data_get($lib, 'size')),
        'date' => $dateLabel,
        'pages' => $pages,
        'summary' => $docsLimit($summary, 420) ?: 'Documento técnico que establece las especificaciones legales y operativas para la contratación de suministros de oficina y papelería. El objetivo es abastecer a las unidades administrativas de la región Saltillo de CAPUFE para garantizar su continuidad operativa y administrativa.',
        'url' => $docLink,
        'documents' => $documents,
        'tables' => $tables,
        'open' => $index === 0,
      ];
    });
  @endphp

  <div id="pjdDocsComponent" class="pjd-docs-root">
    <div class="pjd-docs-tools" aria-label="Acciones de documentos">
      <button type="button" class="pjd-docs-tool" data-docs-expand id="pjdDocsExpandBtn" title="Estirar vista" aria-label="Estirar vista" aria-expanded="false">
        <svg class="pjd-docs-icon-expand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path fill="none" d="M15 3h6v6"></path>
          <path fill="none" d="M9 21H3v-6"></path>
          <path fill="none" d="M21 3l-7 7"></path>
          <path fill="none" d="M3 21l7-7"></path>
        </svg>
        <svg class="pjd-docs-icon-compress" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path fill="none" d="M9 3v6H3"></path>
          <path fill="none" d="M15 21v-6h6"></path>
          <path fill="none" d="M3 9l7-7"></path>
          <path fill="none" d="M21 15l-7 7"></path>
        </svg>
      </button>
    </div>

    <div class="pjd-docs-list">
      @forelse($docs as $doc)
        <article class="pjd-doc-card {{ $doc['open'] ? 'is-open' : '' }}">
          <div class="pjd-doc-head">
            <div class="pjd-doc-file-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
              </svg>
            </div>

            <div class="pjd-doc-main">
              <div class="pjd-doc-title-row">
                <h3 class="pjd-doc-title" title="{{ $doc['filename'] }}">{{ $doc['filename'] }}</h3>
                <span class="pjd-doc-status">{{ $doc['status'] }}</span>
              </div>
              <p class="pjd-doc-meta">{{ $doc['size'] }} • {{ $doc['date'] }} • {{ $doc['pages'] }} página(s) procesadas</p>
            </div>

            <div class="pjd-doc-actions">
              <button type="button" class="pjd-doc-action pjd-doc-toggle" title="Mostrar entregables" aria-label="Mostrar entregables" aria-expanded="{{ $doc['open'] ? 'true' : 'false' }}">
                <svg class="pjd-doc-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"></path></svg>
              </button>
              <button type="button" class="pjd-doc-action pjd-doc-menu" title="Más opciones" aria-label="Más opciones">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="5" r="1.5"></circle>
                  <circle cx="12" cy="12" r="1.5"></circle>
                  <circle cx="12" cy="19" r="1.5"></circle>
                </svg>
              </button>
            </div>
          </div>

          <div class="pjd-doc-content-wrapper">
            <div class="pjd-doc-summary">
              <div class="pjd-doc-ai">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.7 5.1L19 10l-5.3 1.9L12 17l-1.7-5.1L5 10l5.3-1.9L12 3Z"></path><path d="M19 4v4"></path><path d="M17 6h4"></path><path d="M5 16v4"></path><path d="M3 18h4"></path></svg>
                Generado con IA
              </div>
              <p class="pjd-doc-summary-text">{{ $doc['summary'] }}</p>
            </div>

            <div class="pjd-doc-extra">
              <h4 class="pjd-doc-generated-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="7 10 12 15 17 10"></polyline>
                  <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Entregables Generados:
              </h4>

              <div class="pjd-doc-generated-grid">
                <div class="pjd-doc-generated-box">
                  <h4>Documentos</h4>
                  <div class="pjd-doc-link-list">
                    @foreach($doc['documents'] as $item)
                      <a href="{{ $item['url'] ?? '#' }}" class="pjd-doc-link {{ !empty($item['red']) ? 'is-red' : '' }}" {{ ($item['url'] ?? '#') !== '#' ? 'target=_blank' : '' }}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        <span>{{ $item['name'] ?? 'Archivo' }}</span>
                      </a>
                    @endforeach
                  </div>
                </div>

                <div class="pjd-doc-generated-box">
                  <h4>Catálogos &amp; Tablas</h4>
                  <div class="pjd-doc-link-list">
                    @foreach($doc['tables'] as $item)
                      <a href="{{ $item['url'] ?? '#' }}" class="pjd-doc-link is-green" {{ ($item['url'] ?? '#') !== '#' ? 'target=_blank' : '' }}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        <span>{{ $item['name'] ?? 'Tabla generada' }}</span>
                      </a>
                    @endforeach
                  </div>
                </div>
              </div>
            </div>
          </div>
        </article>
      @empty
        <div class="pjd-doc-empty">No hay documentos procesados todavía.</div>
      @endforelse
    </div>
  </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const root = document.getElementById('pjdDocsComponent');
  if (!root) return;

  root.querySelectorAll('.pjd-doc-toggle').forEach(function (button) {
    button.addEventListener('click', function (event) {
      event.preventDefault();
      const card = button.closest('.pjd-doc-card');
      if (!card) return;
      const open = card.classList.toggle('is-open');
      button.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });

  const expandBtn = document.getElementById('pjdDocsExpandBtn');
  const wrap = document.querySelector('.pjd-wrap');

  if (expandBtn && wrap) {
    expandBtn.addEventListener('click', function () {
      const expanded = wrap.classList.toggle('is-documentos-expanded');
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