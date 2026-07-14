@php
  /*
  |--------------------------------------------------------------------------
  | Fuente robusta del checklist
  |--------------------------------------------------------------------------
  | Prioridad:
  | 1) Variable $checklist enviada por el controlador.
  | 2) Relación project_checklist_items.
  | 3) projects.checklist (legacy).
  | 4) structured_data.checklist_sugerido / checklist.
  */
  $clText = null;

  $clText = function ($value, $fallback = '') use (&$clText) {
      if (is_null($value)) {
          return $fallback;
      }

      if (is_bool($value)) {
          return $value ? 'Sí' : 'No';
      }

      if (is_scalar($value)) {
          $text = trim((string) $value);
          return $text !== '' ? $text : $fallback;
      }

      if (is_object($value)) {
          $value = (array) $value;
      }

      if (is_array($value)) {
          foreach ([
              'respuesta', 'answer', 'valor', 'value', 'texto', 'descripcion',
              'description', 'nombre', 'titulo', 'title', 'label', 'content'
          ] as $key) {
              if (!array_key_exists($key, $value)) {
                  continue;
              }

              $candidate = $clText($value[$key]);

              if ($candidate !== '') {
                  return $candidate;
              }
          }

          $parts = [];

          foreach ($value as $item) {
              $candidate = $clText($item);

              if ($candidate !== '') {
                  $parts[] = $candidate;
              }
          }

          $text = trim(implode(' ', array_unique($parts)));
          return $text !== '' ? $text : $fallback;
      }

      return $fallback;
  };

  $rawChecklist = isset($checklist) && is_array($checklist) && !empty($checklist)
      ? $checklist
      : [];

  if (empty($rawChecklist) && $project->relationLoaded('checklistItems') && $project->checklistItems->isNotEmpty()) {
      $rawChecklist = $project->checklistItems
          ->map(function ($item) {
              if (method_exists($item, 'toChecklistArray')) {
                  return $item->toChecklistArray();
              }

              $meta = is_array($item->metadata ?? null) ? $item->metadata : [];

              return [
                  'id' => $item->id,
                  'requisito' => $item->requirement,
                  'descripcion' => $item->description,
                  'criterio_cumplimiento' => $item->compliance_criteria,
                  'formato' => $item->format,
                  'categoria' => $item->category,
                  'aplicabilidad' => $item->applicability,
                  'obligatorio' => $item->mandatory ? 'Sí' : 'No',
                  'cumplimiento' => match ($item->compliance_status) {
                      'cumple' => 'Cumple',
                      'parcial' => 'Parcial',
                      'no_cumple' => 'No Cumple',
                      default => '-',
                  },
                  'status' => match ($item->review_status) {
                      'en_revision' => 'En revisión',
                      'aprobado' => 'Aprobado',
                      default => 'Pendiente',
                  },
                  'prioridad' => match ($item->priority) {
                      'alta' => 'Alta',
                      'baja' => 'Baja',
                      default => 'Media',
                  },
                  'fecha_limite' => optional($item->due_date)->format('Y-m-d'),
                  'responsable' => optional($item->responsible)->name ?: ($meta['responsable_text'] ?? ''),
                  'revisor' => optional($item->reviewer)->name ?: ($meta['revisor_text'] ?? ''),
                  'fuente' => $item->source_name,
                  'pagina' => $item->source_page,
                  'cita' => $item->source_quote,
                  'notas' => $item->relationLoaded('notes')
                      ? $item->notes->map(fn ($note) => [
                          'id' => $note->id,
                          'body' => $note->body,
                      ])->values()->all()
                      : [],
                  'adjuntos' => $item->relationLoaded('attachments')
                      ? $item->attachments->map(fn ($attachment) => [
                          'id' => $attachment->id,
                          'name' => $attachment->original_name,
                          'url' => $attachment->url,
                          'mime' => $attachment->mime_type,
                          'size' => $attachment->size,
                      ])->values()->all()
                      : [],
              ];
          })
          ->values()
          ->all();
  }

  if (empty($rawChecklist)) {
      $legacyChecklist = $project->checklist ?? null;

      if (!is_array($legacyChecklist) || empty($legacyChecklist)) {
          $structuredData = is_array($project->structured_data ?? null)
              ? $project->structured_data
              : [];

          $legacyChecklist = data_get($structuredData, 'checklist_sugerido')
              ?? data_get($structuredData, 'checklist')
              ?? data_get($structuredData, 'analisis.checklist_sugerido')
              ?? data_get($structuredData, 'analisis.checklist')
              ?? [];
      }

      $rawChecklist = is_array($legacyChecklist) ? $legacyChecklist : [];
  }

  $checklist = collect($rawChecklist)
      ->filter(fn ($item) => is_array($item))
      ->map(function ($item, $index) use ($clText) {
          $rawNotes = $item['notas'] ?? [];
          $rawAttachments = $item['adjuntos'] ?? $item['attachments'] ?? [];

          return [
              'id' => $item['id'] ?? $item['item_id'] ?? ($index + 1),
              'requisito' => $clText(
                  $item['requisito'] ?? $item['requirement'] ?? $item['item'] ?? $item['text'] ?? null,
                  'Requisito sin nombre'
              ),
              'descripcion' => $clText($item['descripcion'] ?? $item['description'] ?? ''),
              'criterio_cumplimiento' => $clText($item['criterio_cumplimiento'] ?? $item['compliance_criteria'] ?? ''),
              'formato' => $clText($item['formato'] ?? $item['format'] ?? 'No aplica', 'No aplica'),
              'categoria' => $clText($item['categoria'] ?? $item['category'] ?? 'Legal-Administrativo', 'Legal-Administrativo'),
              'aplicabilidad' => $clText($item['aplicabilidad'] ?? $item['applicability'] ?? 'Único', 'Único'),
              'obligatorio' => $clText($item['obligatorio'] ?? $item['mandatory'] ?? 'Sí', 'Sí'),
              'cumplimiento' => $clText($item['cumplimiento'] ?? $item['compliance'] ?? '-', '-'),
              'status' => $clText($item['status'] ?? $item['review_status'] ?? 'Pendiente', 'Pendiente'),
              'prioridad' => $clText($item['prioridad'] ?? $item['priority'] ?? 'Media', 'Media'),
              'fecha_limite' => $clText($item['fecha_limite'] ?? $item['due_date'] ?? ''),
              'responsable' => $clText($item['responsable'] ?? $item['responsible'] ?? ''),
              'revisor' => $clText($item['revisor'] ?? $item['reviewer'] ?? ''),
              'fuente' => $clText($item['fuente'] ?? $item['source'] ?? ''),
              'pagina' => $clText($item['pagina'] ?? $item['page'] ?? ''),
              'cita' => $clText($item['cita'] ?? $item['quote'] ?? ''),
              'notas' => is_array($rawNotes) ? $rawNotes : [],
              'adjuntos' => is_array($rawAttachments) ? $rawAttachments : [],
          ];
      })
      ->values()
      ->all();
@endphp

<div class="pjd-pane" data-pane="checklist">
        <div class="pjd-checklist-wrap">
          <div class="pjd-checklist-head">
            <div class="pjd-checklist-title-block">
              <h3 class="pjd-checklist-title">{{ $project->name }}</h3>
              <div class="pjd-checklist-title-actions">
                <button type="button" class="pjd-checklist-icon" aria-label="Editar nombre">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/></svg>
                </button>
                <button type="button" class="pjd-checklist-icon" aria-label="Favorito">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 17.27 6.18 3.73-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                </button>
              </div>
            </div>
            <div class="pjd-checklist-links">
              <button type="button" class="pjd-checklist-link" id="pjdClDownload" aria-haspopup="true" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/></svg>
                Descargar lista
              </button>
              <button type="button" class="pjd-checklist-link" id="pjdClExportBtn" aria-label="Exportar archivos">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 5 17 10"/><line x1="12" y1="5" x2="12" y2="17"/></svg>
                Exportar 0 archivos (0 B)
              </button>
            </div>
          </div>

          <div class="pjd-cl-summary">
            <div class="pjd-counters" id="pjdClCounters">
              <div class="pjd-counter"><div class="pjd-counter-top"><span class="pjd-counter-num" data-counter="sin_revisar">0</span><span class="pjd-counter-label">Sin revisar</span><span class="pjd-counter-pct" data-pct="sin_revisar">0%</span></div><div class="pjd-counter-bar"><div class="pjd-counter-bar-fill" data-bar="sin_revisar" style="width:0%"></div></div></div>
              <div class="pjd-counter is-nocumple"><div class="pjd-counter-top"><span class="pjd-counter-num" data-counter="no_cumple">0</span><span class="pjd-counter-label">No Cumple</span><span class="pjd-counter-pct" data-pct="no_cumple">0%</span></div><div class="pjd-counter-bar"><div class="pjd-counter-bar-fill" data-bar="no_cumple" style="width:0%"></div></div></div>
              <div class="pjd-counter is-parcial"><div class="pjd-counter-top"><span class="pjd-counter-num" data-counter="parcial">0</span><span class="pjd-counter-label">Parcial</span><span class="pjd-counter-pct" data-pct="parcial">0%</span></div><div class="pjd-counter-bar"><div class="pjd-counter-bar-fill" data-bar="parcial" style="width:0%"></div></div></div>
              <div class="pjd-counter is-cumple"><div class="pjd-counter-top"><span class="pjd-counter-num" data-counter="cumple">0</span><span class="pjd-counter-label">Cumple</span><span class="pjd-counter-pct" data-pct="cumple">0%</span></div><div class="pjd-counter-bar"><div class="pjd-counter-bar-fill" data-bar="cumple" style="width:0%"></div></div></div>
              <div class="pjd-counter is-pending"><div class="pjd-counter-top"><span class="pjd-counter-num" data-counter="pendiente">0</span><span class="pjd-counter-label">Pendiente</span><span class="pjd-counter-pct" data-pct="pendiente">0%</span></div><div class="pjd-counter-bar"><div class="pjd-counter-bar-fill" data-bar="pendiente" style="width:0%"></div></div></div>
              <div class="pjd-counter is-review"><div class="pjd-counter-top"><span class="pjd-counter-num" data-counter="revision">0</span><span class="pjd-counter-label">En revisión</span><span class="pjd-counter-pct" data-pct="revision">0%</span></div><div class="pjd-counter-bar"><div class="pjd-counter-bar-fill" data-bar="revision" style="width:0%"></div></div></div>
              <div class="pjd-counter is-approved"><div class="pjd-counter-top"><span class="pjd-counter-num" data-counter="aprobado">0</span><span class="pjd-counter-label">Aprobado</span><span class="pjd-counter-pct" data-pct="aprobado">0%</span></div><div class="pjd-counter-bar"><div class="pjd-counter-bar-fill" data-bar="aprobado" style="width:0%"></div></div></div>
              <div class="pjd-counter is-total"><div class="pjd-counter-top"><span class="pjd-counter-num" id="pjdClTotalNum">0</span><span class="pjd-counter-label">Total</span></div></div>
            </div>
          </div>

          <div class="pjd-cl-toolbar">
            <div class="pjd-cl-search">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
              <input type="text" id="pjdClSearch" placeholder="Buscar por requisito, formato o descripción...">
            </div>
            <div class="pjd-cl-actions">
              <button type="button" class="pjd-cl-btn is-primary" id="pjdClReanalisis">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v4"/><path d="M12 17v4"/><path d="M4.93 4.93l2.83 2.83"/><path d="M16.24 16.24l2.83 2.83"/><path d="M3 12h4"/><path d="M17 12h4"/><path d="M4.93 19.07l2.83-2.83"/><path d="M16.24 7.76l2.83-2.83"/></svg>
                Reanálisis
              </button>
              <div class="pjd-cl-menu-wrap"><button type="button" class="pjd-cl-btn" id="pjdClFiltersBtn" aria-label="Filtros" aria-haspopup="true" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Filtros
              </button></div>
              <div class="pjd-cl-menu-wrap"><button type="button" class="pjd-cl-btn" id="pjdClColumnsBtn" aria-label="Columnas" aria-haspopup="true" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
                Columnas <span class="pjd-cl-hidden-count" id="pjdClHiddenCount">0</span>
              </button></div>
            </div>
          </div>

          <div class="pjd-cl-menu" id="pjdClExportMenu" aria-hidden="true">
            <button type="button" class="pjd-cl-menu-option" data-export="csv"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-check"></span>Exportar CSV</span></button>
            <button type="button" class="pjd-cl-menu-option" data-export="pdf"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-check"></span>Exportar PDF</span></button>
          </div>

          <div class="pjd-cl-menu" id="pjdClDownloadMenu" data-variant="download" aria-hidden="true">
            <button type="button" class="pjd-cl-menu-option" data-download-list="excel"><span class="pjd-cl-menu-left">Descargar Excel</span></button>
            <button type="button" class="pjd-cl-menu-option" data-download-list="pdf"><span class="pjd-cl-menu-left">Descargar PDF</span></button>
          </div>

          <div class="pjd-cl-menu" id="pjdClFiltersMenu" aria-hidden="true">
            <div class="pjd-cl-menu-title">Cumplimiento</div>
            <button type="button" class="pjd-cl-menu-option is-active" data-filter-group="cumplimiento" data-filter-value="__all"><span class="pjd-cl-menu-left">Todos</span><span class="pjd-cl-menu-square"></span></button>
            <button type="button" class="pjd-cl-menu-option" data-filter-group="cumplimiento" data-filter-value="-"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-dot" style="background:#9ca3af"></span>Sin revisar (-)</span><span class="pjd-cl-menu-square"></span></button>
            <button type="button" class="pjd-cl-menu-option" data-filter-group="cumplimiento" data-filter-value="Cumple"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-dot" style="background:#22c55e"></span>Cumple</span><span class="pjd-cl-menu-square"></span></button>
            <button type="button" class="pjd-cl-menu-option" data-filter-group="cumplimiento" data-filter-value="Parcial"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-dot" style="background:#eab308"></span>Parcial</span><span class="pjd-cl-menu-square"></span></button>
            <button type="button" class="pjd-cl-menu-option" data-filter-group="cumplimiento" data-filter-value="No Cumple"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-dot" style="background:#ef4444"></span>No Cumple</span><span class="pjd-cl-menu-square"></span></button>
            <div class="pjd-cl-menu-sep"></div>
            <div class="pjd-cl-menu-title">Status</div>
            <button type="button" class="pjd-cl-menu-option is-active" data-filter-group="status" data-filter-value="__all"><span class="pjd-cl-menu-left">Todos</span><span class="pjd-cl-menu-square"></span></button>
            <button type="button" class="pjd-cl-menu-option" data-filter-group="status" data-filter-value="Pendiente"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-dot" style="background:#f59e0b"></span>Pendiente</span><span class="pjd-cl-menu-square"></span></button>
            <button type="button" class="pjd-cl-menu-option" data-filter-group="status" data-filter-value="En revisión"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-dot" style="background:#3b82f6"></span>En revisión</span><span class="pjd-cl-menu-square"></span></button>
            <button type="button" class="pjd-cl-menu-option" data-filter-group="status" data-filter-value="Aprobado"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-dot" style="background:#22c55e"></span>Aprobado</span><span class="pjd-cl-menu-square"></span></button>
            <div class="pjd-cl-menu-sep"></div>
            <div class="pjd-cl-menu-title">Prioridad</div>
            <button type="button" class="pjd-cl-menu-option is-active" data-filter-group="prioridad" data-filter-value="__all"><span class="pjd-cl-menu-left">Todas</span><span class="pjd-cl-menu-square"></span></button>
            <button type="button" class="pjd-cl-menu-option" data-filter-group="prioridad" data-filter-value="Alta"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-dot" style="background:#ef4444"></span>Alta</span><span class="pjd-cl-menu-square"></span></button>
            <button type="button" class="pjd-cl-menu-option" data-filter-group="prioridad" data-filter-value="Media"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-dot" style="background:#eab308"></span>Media</span><span class="pjd-cl-menu-square"></span></button>
            <button type="button" class="pjd-cl-menu-option" data-filter-group="prioridad" data-filter-value="Baja"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-dot" style="background:#64748b"></span>Baja</span><span class="pjd-cl-menu-square"></span></button>
            <div class="pjd-cl-menu-actions"><button type="button" class="pjd-cl-menu-mini" id="pjdClClearFilters">Limpiar</button><button type="button" class="pjd-cl-menu-mini" id="pjdClCloseFilters">Cerrar</button></div>
          </div>

          <div class="pjd-cl-menu" id="pjdClColumnsMenu" aria-hidden="true">
            <button type="button" class="pjd-cl-menu-option is-disabled" disabled><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-check"></span>Requisito</span></button>
            <button type="button" class="pjd-cl-menu-option is-active" data-column-toggle="formato"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-check"></span>Formato</span></button>
            <button type="button" class="pjd-cl-menu-option is-active" data-column-toggle="categoria"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-check"></span>Categoría</span></button>
            <button type="button" class="pjd-cl-menu-option is-active" data-column-toggle="aplicabilidad"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-check"></span>Aplicación</span></button>
            <button type="button" class="pjd-cl-menu-option is-active" data-column-toggle="obligatorio"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-check"></span>Obligatorio</span></button>
            <button type="button" class="pjd-cl-menu-option is-active" data-column-toggle="cumplimiento"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-check"></span>Cumplimiento</span></button>
            <button type="button" class="pjd-cl-menu-option is-active" data-column-toggle="status"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-check"></span>Status</span></button>
            <button type="button" class="pjd-cl-menu-option is-active" data-column-toggle="opciones"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-check"></span>Opciones</span></button>
            <div class="pjd-cl-menu-actions"><button type="button" class="pjd-cl-menu-mini" id="pjdClShowAllColumns">Mostrar todo</button><button type="button" class="pjd-cl-menu-mini" id="pjdClCloseColumns">Cerrar</button></div>
          </div>

          <div class="pjd-cl-table-wrap">
            <table class="pjd-cl-table" id="pjdClTable">
              <thead>
                <tr>
                  <th class="pjd-cl-check-head"><span class="pjd-cl-checkmark" aria-hidden="true"></span></th>
                  <th data-col="requisito"><span class="pjd-cl-th-main"><span class="pjd-cl-th-handle">⋮⋮</span><span>Requisito</span><span class="pjd-cl-th-sort">↕</span></span></th>
                  <th data-col="formato"><span class="pjd-cl-th-compact"><span class="pjd-cl-th-handle">⋮⋮</span><span>Formato</span><span class="pjd-cl-th-sort">↕</span></span></th>
                  <th data-col="categoria"><span class="pjd-cl-th-compact"><span class="pjd-cl-th-handle">⋮⋮</span><span>Categoría</span><span class="pjd-cl-th-sort">↕</span></span></th>
                  <th data-col="aplicabilidad"><span class="pjd-cl-th-compact"><span class="pjd-cl-th-handle">⋮⋮</span><span>Aplic.</span><span class="pjd-cl-th-sort">↕</span></span></th>
                  <th data-col="obligatorio"><span class="pjd-cl-th-compact"><span class="pjd-cl-th-handle">⋮⋮</span><span>Oblig.</span><span class="pjd-cl-th-sort">↕</span></span></th>
                  <th data-col="cumplimiento"><span class="pjd-cl-th-compact"><span class="pjd-cl-th-handle">⋮⋮</span><span>Cumpl.</span><span class="pjd-cl-th-sort">↕</span></span></th>
                  <th data-col="status"><span class="pjd-cl-th-compact"><span class="pjd-cl-th-handle">⋮⋮</span><span>Status</span><span class="pjd-cl-th-sort">↕</span></span></th>
                  <th data-col="opciones"><span class="pjd-cl-th-compact"><span class="pjd-cl-th-handle">⋮⋮</span><span>Opc.</span></span></th>
                </tr>
              </thead>
              <tbody id="pjdClBody">
                @forelse($checklist as $idx => $it)
                  @php
                    $clPayload = $checklistCitaPayload($it);
                    $docMatch = !empty($it['fuente']) ? $project->documents->firstWhere('filename', $it['fuente']) : null;
                    $docUrl = $docMatch ? $docMatch->url : null;
                  @endphp
                  <tr data-row="{{ $it['id'] }}" data-legacy-index="{{ $idx }}" data-cumplimiento="{{ $it['cumplimiento'] }}" data-status="{{ $it['status'] }}" data-prioridad="{{ $it['prioridad'] }}" data-requisito="{{ e($it['requisito']) }}" data-formato="{{ e($it['formato']) }}" data-descripcion="{{ e($it['descripcion']) }}" data-fecha-limite="{{ $it['fecha_limite'] ?? '' }}" data-responsable="{{ e($it['responsable'] ?? '') }}" data-revisor="{{ e($it['revisor'] ?? '') }}" data-notas="{{ e(collect($it['notas'] ?? [])->map(fn($n) => is_array($n) ? ($n['body'] ?? '') : $n)->filter()->implode("\n")) }}" data-adjuntos='@json($it["adjuntos"] ?? [])' @if($clPayload) data-cita="{{ $clPayload }}" @endif>
                    <td class="pjd-cl-check-cell"><button type="button" class="pjd-cl-row-toggle" data-toggle="{{ $it['id'] }}" title="Ver fuente y detalle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></button></td>
                    <td>
                      <div class="pjd-cl-requisito">
                        <svg class="pjd-cl-row-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="7" x2="19" y2="7"/><line x1="5" y1="12" x2="19" y2="12"/><line x1="5" y1="17" x2="14" y2="17"/></svg>
                        <span class="pjd-cl-requisito-text" title="{{ $it['requisito'] }}">{{ $it['requisito'] }}</span>
                      </div>
                    </td>
                    <td class="pjd-cl-cell-muted" data-col="formato">{{ $it['formato'] }}</td>
                    <td class="pjd-cl-cell-muted" data-col="categoria">{{ Str::limit($it['categoria'], 22) }}</td>
                    <td class="pjd-cl-cell-muted" data-col="aplicabilidad">{{ $it['aplicabilidad'] }}</td>
                    <td class="pjd-cl-cell-center pjd-cl-cell-success" data-col="obligatorio">{{ $it['obligatorio'] }}</td>
                    <td data-col="cumplimiento">
                      @php
                        $cumpClass = match($it['cumplimiento']) { 'Cumple'=>'is-cumple','Parcial'=>'is-parcial','No Cumple'=>'is-nocumple', default=>'' };
                        $cumpLabel = $it['cumplimiento'] ?: '-';
                      @endphp
                      <button type="button" class="pjd-cl-cumplimiento-btn" data-cumplimiento-toggle="{{ $it['id'] }}" title="Cambiar cumplimiento">
                        <span class="pjd-cl-cumple-dot {{ $cumpClass }}"></span>
                        <span class="pjd-cl-cumple-text {{ $cumpClass }}">{{ $cumpLabel }}</span>
                      </button>
                    </td>
                    <td data-col="status">
                      @php
                        $statClass = match($it['status']) { 'En revisión'=>'is-revision','Aprobado'=>'is-aprobado', default=>'is-pendiente' };
                        $statusValue = $it['status'] ?: 'Pendiente';
                      @endphp
                      <button type="button" class="pjd-cl-status {{ $statClass }}" data-status-toggle="{{ $it['id'] }}">
                        <span class="pjd-cl-status-icon">
                          @if($statusValue === 'Aprobado')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 12l2.5 2.5L16 9"/></svg>
                          @elseif($statusValue === 'En revisión')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                          @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                          @endif
                        </span>
                        <span class="pjd-cl-status-text">{{ $statusValue }}</span>
                      </button>
                    </td>
                    <td class="pjd-cl-cell-center" data-col="opciones"><button type="button" class="pjd-cl-options" data-options="{{ $it['id'] }}" title="Opciones"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg></button></td>
                  </tr>
                  <tr class="pjd-cl-detail-row" data-detail="{{ $it['id'] }}" style="display:none;">
                    <td colspan="9" style="padding:0">
                      <div class="pjd-cl-detail">
                        <div class="pjd-cl-detail-panel">
                          <div class="pjd-cl-detail-section">
                            <div class="pjd-cl-detail-label">Descripción:</div>
                            @if($it['descripcion'])
                              <p class="pjd-cl-detail-text pjd-cl-detail-description">{{ $it['descripcion'] }}</p>
                            @else
                              <p class="pjd-cl-detail-text is-muted pjd-cl-detail-description">Sin descripción adicional.</p>
                            @endif
                          </div>

                          <div class="pjd-cl-detail-section">
                            <div class="pjd-cl-detail-controls">
                              <div class="pjd-cl-detail-control-row">
                                <span class="pjd-cl-detail-label" style="margin:0;">Prioridad:</span>
                                <div class="pjd-cl-priority-group" data-priority-group="{{ $it['id'] }}">
                                  <button type="button" class="pjd-cl-priority-btn {{ ($it['prioridad'] ?? 'Media') === 'Alta' ? 'is-active' : '' }}" data-priority-set="Alta">Alta</button>
                                  <button type="button" class="pjd-cl-priority-btn {{ ($it['prioridad'] ?? 'Media') === 'Media' ? 'is-active' : '' }}" data-priority-set="Media">Media</button>
                                  <button type="button" class="pjd-cl-priority-btn {{ ($it['prioridad'] ?? 'Media') === 'Baja' ? 'is-active' : '' }}" data-priority-set="Baja">Baja</button>
                                </div>
                              </div>
                              <div class="pjd-cl-detail-control-row">
                                <span class="pjd-cl-detail-label" style="margin:0;">
                                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                  Fecha límite:
                                </span>
                                <input type="date" class="pjd-cl-detail-date" data-detail-date="{{ $it['id'] }}" value="{{ $it['fecha_limite'] ?? '' }}">
                              </div>
                              <div class="pjd-cl-detail-control-row">
                                <span class="pjd-cl-detail-label" style="margin:0;">
                                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                                  Responsable:
                                </span>
                                <select class="pjd-cl-detail-select" data-detail-responsable="{{ $it['id'] }}">
                                  <option>Sin asignar</option>
                                </select>
                              </div>
                              <div class="pjd-cl-detail-control-row">
                                <span class="pjd-cl-detail-label" style="margin:0;">
                                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                                  Revisor:
                                </span>
                                <select class="pjd-cl-detail-select" data-detail-revisor="{{ $it['id'] }}">
                                  <option>Sin asignar</option>
                                </select>
                              </div>
                            </div>
                          </div>

                          <div class="pjd-cl-detail-section">
                            <div class="pjd-cl-detail-control-row">
                              <span class="pjd-cl-detail-label" style="margin:0;">Notas:</span>
                              <button type="button" class="pjd-cl-detail-link" data-detail-note="{{ $it['id'] }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                Agregar
                              </button>
                            </div>
                            <p class="pjd-cl-detail-empty">No hay notas agregadas.</p>
                          </div>

                          <div class="pjd-cl-detail-section">
                            <div class="pjd-cl-detail-control-row">
                              <span class="pjd-cl-detail-label" style="margin:0;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                Documentos Adjuntos:
                              </span>
                              <button type="button" class="pjd-cl-detail-link" data-detail-attach="{{ $it['id'] }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05 12 20.49a6 6 0 0 1-8.49-8.49l9.44-9.44a4 4 0 0 1 5.66 5.66L9.17 17.66a2 2 0 0 1-2.83-2.83l8.49-8.49"/></svg>
                                Adjuntar
                              </button>
                            </div>
                            <p class="pjd-cl-detail-empty">No hay documentos adjuntos. Haz clic en "Adjuntar Documento" para agregar.</p>
                          </div>
                        </div>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="9" class="pjd-cl-no-results">Sin items en el checklist. Da clic en <strong>Reanálisis</strong> para generar uno.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <button type="button" class="pjd-cl-add" id="pjdClAddBtn"><span>＋</span>Agregar nuevo requisito</button>

          <form class="pjd-cl-add-form" id="pjdClAddForm" autocomplete="off">
            <h4 class="pjd-cl-add-title" id="pjdClAddTitle">Agregar nuevo requisito</h4>
            <div class="pjd-cl-add-grid">
              <div class="pjd-cl-add-field">
                <label for="pjdClNewReq">Requisito *</label>
                <input type="text" id="pjdClNewReq" placeholder="Nombre del requisito" required>
              </div>
              <div class="pjd-cl-add-field">
                <label for="pjdClNewFormato">Formato</label>
                <input type="text" id="pjdClNewFormato" placeholder="Ej: Anexo 1, Documento 2, etc.">
              </div>
              <div class="pjd-cl-add-field is-full">
                <label for="pjdClNewDesc">Descripción</label>
                <textarea id="pjdClNewDesc" placeholder="Descripción del requisito"></textarea>
              </div>
            </div>
            <div class="pjd-cl-add-actions">
              <button type="submit" class="pjd-cl-add-save" id="pjdClAddSave">Guardar</button>
              <button type="button" class="pjd-cl-add-cancel" id="pjdClAddCancel">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
