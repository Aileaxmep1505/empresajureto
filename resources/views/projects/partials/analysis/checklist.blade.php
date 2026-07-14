@php
  /*
  |--------------------------------------------------------------------------
  | Fuente robusta del checklist
  |--------------------------------------------------------------------------
  */
  $clText = null;
  $clText = function ($value, $fallback = '') use (&$clText) {
      if (is_null($value)) return $fallback;
      if (is_bool($value)) return $value ? 'Sí' : 'No';
      if (is_scalar($value)) {
          $text = trim((string) $value);
          return $text !== '' ? $text : $fallback;
      }
      if (is_object($value)) $value = (array) $value;
      if (is_array($value)) {
          foreach (['respuesta', 'answer', 'valor', 'value', 'texto', 'descripcion', 'description', 'nombre', 'titulo', 'title', 'label', 'content'] as $key) {
              if (!array_key_exists($key, $value)) continue;
              $candidate = $clText($value[$key]);
              if ($candidate !== '') return $candidate;
          }
          $parts = [];
          foreach ($value as $item) {
              $candidate = $clText($item);
              if ($candidate !== '') $parts[] = $candidate;
          }
          $text = trim(implode(' ', array_unique($parts)));
          return $text !== '' ? $text : $fallback;
      }
      return $fallback;
  };

  $rawChecklist = isset($checklist) && is_array($checklist) && !empty($checklist) ? $checklist : [];

  if (empty($rawChecklist) && isset($project) && $project->relationLoaded('checklistItems') && $project->checklistItems->isNotEmpty()) {
      $rawChecklist = $project->checklistItems->map(function ($item) {
          return [
              'id' => $item->id,
              'requisito' => $item->requirement,
              'descripcion' => $item->description,
              'formato' => $item->format,
              'categoria' => $item->category,
              'aplicabilidad' => $item->applicability,
              'obligatorio' => $item->mandatory ? 'Sí' : 'No',
              'cumplimiento' => $item->compliance_status ?? 'Pendiente',
              'status' => $item->review_status ?? 'Pendiente',
              'prioridad' => $item->priority ?? 'Media',
              'fecha_limite' => optional($item->due_date)->format('Y-m-d'),
              'responsable_id' => $item->responsible_user_id,
              'responsable' => optional($item->responsible)->name ?? '',
              'revisor_id' => $item->reviewer_user_id,
              'revisor' => optional($item->reviewer)->name ?? '',
              'criterio_cumplimiento' => $item->compliance_criteria ?? '',
              'notas' => $item->relationLoaded('notes')
                  ? $item->notes->map(fn ($note) => [
                      'id' => $note->id,
                      'body' => $note->body,
                      'user_name' => optional($note->user)->name,
                  ])->values()->all()
                  : [],
              'adjuntos' => $item->relationLoaded('attachments')
                  ? $item->attachments->map(fn ($attachment) => [
                      'id' => $attachment->id,
                      'name' => $attachment->original_name,
                      'url' => $attachment->url,
                  ])->values()->all()
                  : [],
              'fuente' => $item->source_name,
              'pagina' => $item->source_page,
              'cita' => $item->source_quote,
          ];
      })->all();
  }

  $checklistData = collect($rawChecklist)->filter(fn($item) => is_array($item))->map(function ($item, $index) use ($clText) {
      return [
          'id' => $item['id'] ?? ($index + 1),
          'requisito' => $clText($item['requisito'] ?? $item['requirement'] ?? $item['item'] ?? null, 'Requisito sin nombre'),
          'descripcion' => $clText($item['descripcion'] ?? $item['description'] ?? ''),
          'formato' => $clText($item['formato'] ?? $item['format'] ?? 'No aplica', 'No aplica'),
          'categoria' => $clText($item['categoria'] ?? $item['category'] ?? 'Legal-Administrativo', 'Legal-Administrativo'),
          'aplicabilidad' => $clText($item['aplicabilidad'] ?? $item['applicability'] ?? 'Único', 'Único'),
          'obligatorio' => $clText($item['obligatorio'] ?? $item['mandatory'] ?? 'Sí', 'Sí'),
          'cumplimiento' => $clText($item['cumplimiento'] ?? $item['compliance'] ?? 'Pendiente', 'Pendiente'),
          'status' => $clText($item['status'] ?? $item['review_status'] ?? 'Pendiente', 'Pendiente'),
          'prioridad' => $clText($item['prioridad'] ?? $item['priority'] ?? 'Media', 'Media'),
          'fecha_limite' => $clText($item['fecha_limite'] ?? $item['due_date'] ?? ''),
          'responsable_id' => $item['responsable_id'] ?? $item['responsible_user_id'] ?? null,
          'responsable' => $clText($item['responsable'] ?? $item['responsible'] ?? ''),
          'revisor_id' => $item['revisor_id'] ?? $item['reviewer_user_id'] ?? null,
          'revisor' => $clText($item['revisor'] ?? $item['reviewer'] ?? ''),
          'criterio_cumplimiento' => $clText($item['criterio_cumplimiento'] ?? $item['compliance_criteria'] ?? ''),
          'notas' => is_array($item['notas'] ?? null) ? $item['notas'] : [],
          'adjuntos' => is_array($item['adjuntos'] ?? null) ? $item['adjuntos'] : [],
          'fuente' => $clText($item['fuente'] ?? $item['source'] ?? ''),
          'pagina' => $clText($item['pagina'] ?? $item['page'] ?? ''),
          'cita' => $clText($item['cita'] ?? $item['quote'] ?? ''),
      ];
  })->all();

  $checklistUsers = \App\Models\User::query()
      ->select(['id', 'name', 'email'])
      ->orderBy('name')
      ->get();

  $clTotal = count($checklistData);
  $clSinRevisar = collect($checklistData)->whereIn('cumplimiento', ['-', '', 'Pendiente'])->count();
  $clPendiente = collect($checklistData)->where('status', 'Pendiente')->count();
  $clRevisar = collect($checklistData)->where('status', 'En revisión')->count();
  $clCumple = collect($checklistData)->where('cumplimiento', 'Cumple')->count();
  $clNoCumple = collect($checklistData)->where('cumplimiento', 'No Cumple')->count();

  $clPct = function (int $value) use ($clTotal): int {
      return $clTotal > 0 ? (int) round(($value / $clTotal) * 100) : 0;
  };
@endphp

<style>
#pjdChecklistPane {
  --pjd-border: #e5e7eb;
  --pjd-text-main: #111827;
  --pjd-text-muted: #6b7280;
  --pjd-primary: #2563eb;
  --pjd-primary-hover: #1d4ed8;
  --pjd-bg-hover: #f9fafb;

  --pjd-c-pendiente: #f59e0b;
  --pjd-c-revisar: #3b82f6;
  --pjd-c-cumple: #22c55e;
  --pjd-c-nocumple: #ef4444;
}

/* Aislamiento estricto */
.pjd-cl-wrapper, .pjd-cl-wrapper * {
  box-sizing: border-box !important;
}

.pjd-cl-wrapper {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  color: var(--pjd-text-main);
  background: #ffffff;
  padding: 24px;
  border-radius: 12px;
  border: 1px solid var(--pjd-border);
  width: 100%;
}

/* =======================================
   CONTADORES SUPERIORES (COMPACTOS)
   ======================================= */
.pjd-cl-stats-container {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
  align-items: stretch;
}
.pjd-cl-stats-row {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 10px;
  flex: 1;
}
.pjd-cl-stat-card {
  min-width: 0;
  border: 1px solid var(--pjd-border);
  border-radius: 8px;
  padding: 10px 8px;
  display: flex;
  flex-direction: column;
  background: #fff;
}
.pjd-cl-stat-card.is-active {
  border-color: var(--pjd-primary);
  box-shadow: 0 0 0 1px var(--pjd-primary);
}
.pjd-cl-stat-num {
  font-size: 18px;
  font-weight: 600;
  color: var(--pjd-text-main);
  text-align: center;
  margin-bottom: 2px;
  line-height: 1;
}
.is-active .pjd-cl-stat-num { color: var(--pjd-primary); }
.pjd-cl-stat-label {
  font-size: 11px;
  line-height: 1.2;
  color: var(--pjd-text-muted);
  text-align: center;
  margin-bottom: 6px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

.pjd-cl-stat-footer { display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: auto; }
.pjd-cl-stat-bar-bg { width: 30px; height: 4px; background: #f3f4f6; border-radius: 2px; overflow: hidden; }
.pjd-cl-stat-bar-fill { height: 100%; border-radius: 2px; }
.pjd-cl-stat-pct { font-size: 11px; line-height: 1; font-weight: 500; color: var(--pjd-text-muted); }
.is-active .pjd-cl-stat-pct { color: var(--pjd-primary); }

/* Colores de las barras */
.stat-pendiente .pjd-cl-stat-bar-fill { background: var(--pjd-c-pendiente); }
.stat-pendiente .pjd-cl-stat-pct { color: var(--pjd-c-pendiente); }
.stat-revisar .pjd-cl-stat-bar-fill { background: var(--pjd-c-revisar); }
.stat-revisar .pjd-cl-stat-pct { color: var(--pjd-c-revisar); }
.stat-cumple .pjd-cl-stat-bar-fill { background: var(--pjd-c-cumple); }
.stat-nocumple .pjd-cl-stat-bar-fill { background: var(--pjd-c-nocumple); }
.stat-nocumple .pjd-cl-stat-pct { color: var(--pjd-c-nocumple); }
.stat-total .pjd-cl-stat-bar-fill { background: var(--pjd-primary); width: 100%; }

/* Botones laterales ajustados a la nueva altura */
.pjd-cl-top-actions {
  display: flex;
  flex-direction: column;
  gap: 6px;
  justify-content: space-between;
  flex-shrink: 0;
}
.pjd-cl-icon-btn {
  width: 34px; height: 34px; border: 1px solid var(--pjd-border); background: #fff;
  border-radius: 8px; display: flex; align-items: center; justify-content: center;
  color: var(--pjd-text-muted); cursor: pointer; padding: 0; margin: 0; flex: 1;
}
.pjd-cl-icon-btn:hover { background: var(--pjd-bg-hover); color: var(--pjd-text-main); }
.pjd-cl-icon-btn svg { width: 15px; height: 15px; }

/* =======================================
   TOOLBAR
   ======================================= */
.pjd-cl-toolbar-row {
  display: flex; gap: 12px; margin-bottom: 12px; align-items: center; flex-wrap: wrap;
}
.pjd-cl-help-btn {
  width: 42px; height: 42px; border: 1px solid var(--pjd-border); border-radius: 8px;
  background: #fff; color: var(--pjd-primary); display: flex; align-items: center;
  justify-content: center; cursor: pointer; padding: 0; flex-shrink: 0;
}
.pjd-cl-help-btn:hover { background: var(--pjd-bg-hover); }
.pjd-cl-help-btn svg { width: 20px; height: 20px; }

.pjd-cl-search-container {
  flex: 1; min-width: 250px; position: relative; display: flex; align-items: center;
}
.pjd-cl-search-container svg { position: absolute; left: 14px; width: 18px; height: 18px; color: #9ca3af; }
.pjd-cl-search-container input {
  width: 100%; height: 42px; padding: 0 16px 0 42px; border: 1px solid var(--pjd-border);
  border-radius: 8px; font-size: 14px; color: var(--pjd-text-main); outline: none; margin: 0;
}
.pjd-cl-search-container input:focus { border-color: var(--pjd-primary); box-shadow: 0 0 0 1px var(--pjd-primary); }

.pjd-cl-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 42px;
  padding: 0 16px; border: 1px solid var(--pjd-border); background: #fff; border-radius: 8px;
  font-size: 14px; font-weight: 500; color: var(--pjd-text-main); cursor: pointer; white-space: nowrap; flex-shrink: 0;
}
.pjd-cl-btn:hover { background: var(--pjd-bg-hover); }
.pjd-cl-btn svg { width: 16px; height: 16px; }

.pjd-cl-btn.is-blue { background: var(--pjd-primary); border-color: var(--pjd-primary); color: #fff; }
.pjd-cl-btn.is-blue:hover { background: var(--pjd-primary-hover); }

.pjd-cl-toolbar-row-2 {
  display: flex; gap: 12px; margin-bottom: 24px; align-items: center;
}

/* Dropdowns */
.pjd-cl-menu-wrapper { position: relative; display: inline-block; }
.pjd-cl-dropdown-menu {
  display: none; position: absolute; top: calc(100% + 8px); left: 0; background: #fff;
  border: 1px solid var(--pjd-border); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
  border-radius: 8px; padding: 12px 0; min-width: 180px; z-index: 50;
}
.pjd-cl-dropdown-menu.show { display: block; }
.pjd-col-option {
  display: flex; align-items: center; gap: 10px; padding: 8px 16px; font-size: 14px;
  color: var(--pjd-text-main); cursor: pointer; transition: background 0.15s; margin: 0;
}
.pjd-col-option:hover { background: var(--pjd-bg-hover); }
.pjd-col-option input[type="checkbox"] { width: 16px; height: 16px; margin: 0; cursor: pointer; accent-color: var(--pjd-primary); }

/* =======================================
   TABLA
   ======================================= */
.pjd-cl-table-container {
  border: 1px solid var(--pjd-border); border-radius: 8px; overflow-x: auto;
}
.pjd-cl-table {
  width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; margin: 0; table-layout: auto;
}
.pjd-cl-table th {
  background: #ffffff; padding: 12px 16px; border-bottom: 1px solid var(--pjd-border);
  border-right: 1px solid var(--pjd-border); color: #374151; font-weight: 600; white-space: normal;
}
.pjd-cl-table th:last-child { border-right: none; }
.pjd-cl-table th .th-inner { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.pjd-cl-table th .th-left { display: flex; align-items: center; gap: 12px; }
.pjd-cl-table th .th-grip { color: #d1d5db; width: 14px; height: 14px; flex-shrink: 0; }
.pjd-cl-table th .th-sort { color: #9ca3af; width: 16px; height: 16px; flex-shrink: 0; }

.pjd-cl-table td {
  padding: 14px 16px; border-bottom: 1px solid var(--pjd-border); vertical-align: middle;
}
.pjd-cl-table tbody tr:hover td { background: var(--pjd-bg-hover); }

/* Elementos de celda */
.pjd-cl-checkbox {
  width: 16px; height: 16px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer; margin: 0;
}
.pjd-cl-chevron-btn {
  background: none; border: none; color: var(--pjd-primary); cursor: pointer; padding: 0;
  display: inline-flex; align-items: center; margin-right: 12px; flex-shrink: 0;
}
.pjd-cl-chevron-btn svg { width: 16px; height: 16px; transition: transform 0.2s; }
.pjd-cl-chevron-btn.open svg { transform: rotate(90deg); }

.pjd-text-gray { color: var(--pjd-text-muted); }

/* Fila desplegable */
.pjd-cl-detail-row { display: none; }
.pjd-cl-detail-row.open { display: table-row; }
.pjd-cl-detail-content { padding: 24px 28px; background: #f9fafb; }

/* Contenido del panel de detalle */
.pjd-cl-detail-label {
  font-size: 15px; font-weight: 600; color: var(--pjd-text-main); margin: 0 0 10px;
}
.pjd-cl-detail-meta {
  font-size: 14px; color: var(--pjd-text-muted); line-height: 1.9; margin: 0;
}
.pjd-cl-detail-sep {
  border: none; border-top: 1px solid var(--pjd-border); margin: 20px 0;
}
.pjd-cl-detail-attrs {
  display: grid; grid-template-columns: 1fr 1fr; gap: 16px 32px; align-items: center;
}
.pjd-cl-detail-attr-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.pjd-cl-detail-attr-label {
  font-size: 14px; font-weight: 500; color: var(--pjd-text-main); white-space: nowrap;
}
.pjd-cl-priority-group { display: inline-flex; gap: 8px; }
.pjd-cl-priority-btn {
  padding: 6px 16px; border: 1px solid var(--pjd-border); background: #fff; border-radius: 8px;
  font-size: 13px; color: var(--pjd-text-muted); cursor: pointer;
}
.pjd-cl-priority-btn:hover { background: var(--pjd-bg-hover); }
.pjd-cl-priority-btn.is-active { background: var(--pjd-primary); border-color: var(--pjd-primary); color: #fff; }
.pjd-cl-detail-date, .pjd-cl-detail-select {
  padding: 8px 12px; border: 1px solid var(--pjd-border); border-radius: 8px;
  font-size: 14px; color: var(--pjd-text-main); background: #fff; outline: none;
}
.pjd-cl-detail-date:focus, .pjd-cl-detail-select:focus { border-color: var(--pjd-primary); box-shadow: 0 0 0 1px var(--pjd-primary); }
.pjd-cl-detail-select { min-width: 220px; }

@media (max-width: 720px) {
  .pjd-cl-detail-attrs { grid-template-columns: 1fr; }
}

/* =======================================
   MODAL DE AYUDA
   ======================================= */
.pjd-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(17, 24, 39, 0.5); z-index: 9999; align-items: center; justify-content: center; }
.pjd-modal-overlay.show { display: flex; }
.pjd-modal { background: #fff; width: 90%; max-width: 700px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); padding: 32px; position: relative; }
.pjd-modal-header { margin-bottom: 24px; padding-right: 24px; }
.pjd-modal-title-group { display: flex; gap: 12px; align-items: flex-start; }
.pjd-modal-title-icon { color: var(--pjd-primary); width: 24px; height: 24px; margin-top: 2px; flex-shrink: 0; }
.pjd-modal-title-group h3 { font-size: 18px; font-weight: 600; color: var(--pjd-text-main); margin: 0 0 4px 0; }
.pjd-modal-title-group p { font-size: 14px; color: var(--pjd-text-muted); margin: 0; }
.pjd-modal-close { position: absolute; top: 24px; right: 24px; background: none; border: none; color: var(--pjd-text-muted); cursor: pointer; padding: 4px; border-radius: 6px; }
.pjd-modal-close:hover { background: var(--pjd-bg-hover); color: var(--pjd-text-main); }
.pjd-modal-close svg { width: 20px; height: 20px; }

.pjd-modal-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
.pjd-status-card { border: 1px solid var(--pjd-border); border-top-width: 4px; border-radius: 12px; padding: 20px; }
.pjd-status-card p { font-size: 13.5px; color: var(--pjd-text-muted); line-height: 1.5; margin: 0; }
.pjd-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 100px; border: 1px solid var(--pjd-border); font-size: 13px; font-weight: 500; margin-bottom: 12px; }
.pjd-badge svg { width: 14px; height: 14px; }

.pjd-card-pendiente { border-top-color: var(--pjd-c-pendiente); }
.pjd-card-pendiente .pjd-badge { color: var(--pjd-c-pendiente); border-color: #fcd34d; }
.pjd-card-revisar { border-top-color: var(--pjd-c-revisar); }
.pjd-card-revisar .pjd-badge { color: var(--pjd-c-revisar); border-color: #bfdbfe; }
.pjd-card-cumple { border-top-color: var(--pjd-c-cumple); }
.pjd-card-cumple .pjd-badge { color: var(--pjd-c-cumple); border-color: #bbf7d0; }
.pjd-card-nocumple { border-top-color: var(--pjd-c-nocumple); }
.pjd-card-nocumple .pjd-badge { color: var(--pjd-c-nocumple); border-color: #fecaca; }

/* =======================================
   VISTA EXPANDIDA AISLADA
   ======================================= */
#pjdChecklistPane {
  width: 100%;
  min-width: 0;
}

#pjdChecklistPane.pjd-cl-is-expanded {
  position: fixed !important;
  inset: 0 !important;
  z-index: 9998 !important;
  display: block !important;
  width: 100vw !important;
  height: 100vh !important;
  max-width: none !important;
  padding: 18px !important;
  margin: 0 !important;
  overflow: auto !important;
  background: #f9fafb !important;
}

#pjdChecklistPane.pjd-cl-is-expanded .pjd-cl-wrapper {
  width: min(1600px, 100%) !important;
  min-height: calc(100vh - 36px);
  margin: 0 auto !important;
  border-radius: 12px;
}

#pjdChecklistPane.pjd-cl-is-expanded .pjd-cl-table-container {
  overflow: visible;
}

body.pjd-cl-body-locked {
  overflow: hidden !important;
}

#pjdChecklistPane [data-cl-expand-view] .pjd-cl-expand-icon,
#pjdChecklistPane [data-cl-expand-view] .pjd-cl-compress-icon {
  width: 15px;
  height: 15px;
}

#pjdChecklistPane [data-cl-expand-view] .pjd-cl-compress-icon {
  display: none;
}

#pjdChecklistPane.pjd-cl-is-expanded [data-cl-expand-view] .pjd-cl-expand-icon {
  display: none;
}

#pjdChecklistPane.pjd-cl-is-expanded [data-cl-expand-view] .pjd-cl-compress-icon {
  display: block;
}


/* ============================================================
   EXTENSIONES FUNCIONALES DEL MISMO DISEÑO
   ============================================================ */
#pjdChecklistPane .pjd-cl-table {
  min-width: 1780px;
}

#pjdChecklistPane .pjd-cl-table th,
#pjdChecklistPane .pjd-cl-table td {
  white-space: nowrap;
}

#pjdChecklistPane .pjd-cl-table th {
  position: relative;
}

#pjdChecklistPane .pjd-cl-table .pjd-cl-main-cell {
  min-width: 420px;
  max-width: 520px;
  white-space: normal;
}

#pjdChecklistPane .pjd-cl-table .pjd-cl-main-text {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

#pjdChecklistPane .pjd-cl-inline-select {
  min-width: 145px;
  height: 34px;
  padding: 0 30px 0 9px;
  border: 0;
  background-color: transparent;
  color: var(--pjd-text-main);
  font-size: 14px;
  outline: none;
  cursor: pointer;
}

#pjdChecklistPane .pjd-cl-inline-select:focus {
  background: #fff;
  border: 1px solid var(--pjd-primary);
  border-radius: 7px;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, .12);
}

#pjdChecklistPane .pjd-cl-status-select {
  font-weight: 500;
}

#pjdChecklistPane .pjd-cl-status-select.is-pending {
  color: #d97706;
}

#pjdChecklistPane .pjd-cl-status-select.is-review {
  color: #2563eb;
}

#pjdChecklistPane .pjd-cl-status-select.is-success {
  color: #16a34a;
}

#pjdChecklistPane .pjd-cl-status-select.is-danger {
  color: #ef4444;
}

#pjdChecklistPane .pjd-cl-responsible-select {
  min-width: 170px;
}

#pjdChecklistPane .pjd-cl-priority-select {
  min-width: 130px;
  color: var(--pjd-text-muted);
}

#pjdChecklistPane .pjd-cl-date-input {
  width: 138px;
  height: 34px;
  padding: 0 8px;
  border: 0;
  background: transparent;
  color: var(--pjd-text-main);
  font-size: 13px;
  outline: none;
}

#pjdChecklistPane .pjd-cl-date-input:focus {
  border: 1px solid var(--pjd-primary);
  border-radius: 7px;
  background: #fff;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, .12);
}

#pjdChecklistPane .pjd-cl-attachment-cell {
  text-align: center;
}

#pjdChecklistPane .pjd-cl-attachment-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-width: 56px;
  height: 32px;
  padding: 0 8px;
  border: 0;
  border-radius: 7px;
  background: transparent;
  color: var(--pjd-primary);
  cursor: pointer;
}

#pjdChecklistPane .pjd-cl-attachment-btn:hover {
  background: #eff6ff;
}

#pjdChecklistPane .pjd-cl-attachment-btn svg {
  width: 17px;
  height: 17px;
}

#pjdChecklistPane .pjd-cl-attachment-count {
  min-width: 24px;
  height: 24px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: #f3f4f6;
  color: #4b5563;
  font-size: 12px;
  font-weight: 600;
}

#pjdChecklistPane .pjd-cl-options-wrap {
  position: relative;
  display: inline-flex;
}

#pjdChecklistPane .pjd-cl-options-btn {
  width: 34px;
  height: 32px;
  border: 0;
  border-radius: 7px;
  background: transparent;
  color: var(--pjd-primary);
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
}

#pjdChecklistPane .pjd-cl-options-btn:hover {
  background: #eff6ff;
}

#pjdChecklistPane .pjd-cl-row-menu {
  position: fixed;
  z-index: 10020;
  display: none;
  width: 190px;
  padding: 8px;
  border: 1px solid var(--pjd-border);
  border-radius: 10px;
  background: #fff;
  box-shadow: 0 14px 34px rgba(15, 23, 42, .16);
}

#pjdChecklistPane .pjd-cl-row-menu.show {
  display: block;
}

#pjdChecklistPane .pjd-cl-row-menu button {
  width: 100%;
  min-height: 38px;
  padding: 0 10px;
  display: flex;
  align-items: center;
  gap: 9px;
  border: 0;
  border-radius: 7px;
  background: transparent;
  color: var(--pjd-text-main);
  font-size: 14px;
  text-align: left;
  cursor: pointer;
}

#pjdChecklistPane .pjd-cl-row-menu button:hover {
  background: var(--pjd-bg-hover);
}

#pjdChecklistPane .pjd-cl-row-menu button.is-danger {
  color: #ef4444;
}

#pjdChecklistPane .pjd-cl-row-menu svg {
  width: 17px;
  height: 17px;
  color: var(--pjd-primary);
}

#pjdChecklistPane .pjd-cl-row-menu .is-danger svg {
  color: #ef4444;
}

#pjdChecklistPane .pjd-cl-edit-modal {
  display: none;
  position: fixed;
  inset: 0;
  z-index: 10030;
  align-items: center;
  justify-content: center;
  padding: 22px;
  background: rgba(17, 24, 39, .38);
  backdrop-filter: blur(2px);
}

#pjdChecklistPane .pjd-cl-edit-modal.show {
  display: flex;
}

#pjdChecklistPane .pjd-cl-edit-card {
  width: min(640px, 100%);
  max-height: calc(100vh - 44px);
  overflow: auto;
  padding: 24px;
  border: 1px solid var(--pjd-border);
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 22px 55px rgba(15, 23, 42, .18);
}

#pjdChecklistPane .pjd-cl-edit-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 20px;
}

#pjdChecklistPane .pjd-cl-edit-title {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

#pjdChecklistPane .pjd-cl-edit-close {
  width: 34px;
  height: 34px;
  border: 0;
  border-radius: 7px;
  background: transparent;
  color: var(--pjd-text-muted);
  cursor: pointer;
  font-size: 20px;
}

#pjdChecklistPane .pjd-cl-edit-close:hover {
  background: var(--pjd-bg-hover);
}

#pjdChecklistPane .pjd-cl-edit-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

#pjdChecklistPane .pjd-cl-edit-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

#pjdChecklistPane .pjd-cl-edit-field.is-full {
  grid-column: 1 / -1;
}

#pjdChecklistPane .pjd-cl-edit-field label {
  font-size: 13px;
  font-weight: 600;
  color: #374151;
}

#pjdChecklistPane .pjd-cl-edit-field input,
#pjdChecklistPane .pjd-cl-edit-field textarea,
#pjdChecklistPane .pjd-cl-edit-field select {
  width: 100%;
  min-height: 40px;
  padding: 9px 11px;
  border: 1px solid var(--pjd-border);
  border-radius: 8px;
  background: #fff;
  color: var(--pjd-text-main);
  outline: none;
  font-size: 14px;
}

#pjdChecklistPane .pjd-cl-edit-field textarea {
  min-height: 100px;
  resize: vertical;
}

#pjdChecklistPane .pjd-cl-edit-field input:focus,
#pjdChecklistPane .pjd-cl-edit-field textarea:focus,
#pjdChecklistPane .pjd-cl-edit-field select:focus {
  border-color: var(--pjd-primary);
  box-shadow: 0 0 0 2px rgba(37, 99, 235, .12);
}

#pjdChecklistPane .pjd-cl-edit-actions {
  display: flex;
  justify-content: flex-end;
  gap: 9px;
  margin-top: 18px;
}

#pjdChecklistPane .pjd-cl-edit-actions button {
  min-height: 38px;
  padding: 0 15px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
}

#pjdChecklistPane .pjd-cl-edit-cancel {
  border: 1px solid var(--pjd-border);
  background: #fff;
  color: var(--pjd-text-main);
}

#pjdChecklistPane .pjd-cl-edit-save {
  border: 1px solid var(--pjd-primary);
  background: var(--pjd-primary);
  color: #fff;
}

@media (max-width: 720px) {
  #pjdChecklistPane .pjd-cl-edit-grid {
    grid-template-columns: 1fr;
  }

  #pjdChecklistPane .pjd-cl-edit-field.is-full {
    grid-column: auto;
  }
}


/* ============================================================
   DETALLE COMPLETO: NOTAS Y DOCUMENTOS ADJUNTOS
   ============================================================ */
#pjdChecklistPane .pjd-cl-detail-block {
  padding-top: 22px;
  margin-top: 22px;
  border-top: 1px solid var(--pjd-border);
}

#pjdChecklistPane .pjd-cl-detail-block-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 14px;
}

#pjdChecklistPane .pjd-cl-detail-block-title {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  color: var(--pjd-text-main);
  font-size: 15px;
  font-weight: 600;
}

#pjdChecklistPane .pjd-cl-detail-block-title svg,
#pjdChecklistPane .pjd-cl-detail-action svg {
  width: 18px;
  height: 18px;
  flex: 0 0 auto;
}

#pjdChecklistPane .pjd-cl-detail-action {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 6px 8px;
  border: 0;
  border-radius: 7px;
  background: transparent;
  color: var(--pjd-primary);
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
}

#pjdChecklistPane .pjd-cl-detail-action:hover {
  background: #eff6ff;
}

#pjdChecklistPane .pjd-cl-note-list,
#pjdChecklistPane .pjd-cl-attachment-list {
  display: grid;
  gap: 10px;
}

#pjdChecklistPane .pjd-cl-note-item,
#pjdChecklistPane .pjd-cl-attachment-empty {
  padding: 14px 16px;
  border-radius: 8px;
  background: #f8fafc;
  color: var(--pjd-text-muted);
  font-size: 14px;
  line-height: 1.55;
}

#pjdChecklistPane .pjd-cl-note-item {
  font-style: italic;
}

#pjdChecklistPane .pjd-cl-note-meta {
  display: block;
  margin-top: 7px;
  color: #9ca3af;
  font-size: 11px;
  font-style: normal;
}

#pjdChecklistPane .pjd-cl-note-editor {
  display: none;
  gap: 10px;
  margin-top: 10px;
}

#pjdChecklistPane .pjd-cl-note-editor.show {
  display: grid;
}

#pjdChecklistPane .pjd-cl-note-editor textarea {
  width: 100%;
  min-height: 90px;
  padding: 12px 14px;
  border: 1px solid var(--pjd-border);
  border-radius: 8px;
  background: #fff;
  color: var(--pjd-text-main);
  outline: none;
  resize: vertical;
  font-size: 14px;
}

#pjdChecklistPane .pjd-cl-note-editor textarea:focus {
  border-color: var(--pjd-primary);
  box-shadow: 0 0 0 2px rgba(37, 99, 235, .12);
}

#pjdChecklistPane .pjd-cl-note-editor-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

#pjdChecklistPane .pjd-cl-note-editor-actions button {
  min-height: 36px;
  padding: 0 13px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
}

#pjdChecklistPane .pjd-cl-note-cancel {
  border: 1px solid var(--pjd-border);
  background: #fff;
  color: var(--pjd-text-main);
}

#pjdChecklistPane .pjd-cl-note-save {
  border: 1px solid var(--pjd-primary);
  background: var(--pjd-primary);
  color: #fff;
}

#pjdChecklistPane .pjd-cl-attachment-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 12px 14px;
  border: 1px solid var(--pjd-border);
  border-radius: 8px;
  background: #fff;
}

#pjdChecklistPane .pjd-cl-attachment-info {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

#pjdChecklistPane .pjd-cl-attachment-info svg {
  width: 18px;
  height: 18px;
  flex: 0 0 auto;
  color: var(--pjd-primary);
}

#pjdChecklistPane .pjd-cl-attachment-name {
  min-width: 0;
  color: var(--pjd-text-main);
  font-size: 14px;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

#pjdChecklistPane .pjd-cl-attachment-open {
  color: var(--pjd-primary);
  font-size: 13px;
  text-decoration: none;
  white-space: nowrap;
}

#pjdChecklistPane .pjd-cl-attachment-open:hover {
  text-decoration: underline;
}

#pjdChecklistPane .pjd-cl-saving {
  opacity: .65;
  pointer-events: none;
}

</style>

<div class="pjd-pane" data-pane="checklist" id="pjdChecklistPane">
<div class="pjd-cl-wrapper">

  <div class="pjd-cl-stats-container">
    <div class="pjd-cl-stats-row">
      <div class="pjd-cl-stat-card">
        <div class="pjd-cl-stat-num">{{ $clSinRevisar }}</div>
        <div class="pjd-cl-stat-label">Sin revisar</div>
        <div class="pjd-cl-stat-footer">
          <div class="pjd-cl-stat-bar-bg"><div class="pjd-cl-stat-bar-fill" style="width:{{ $clPct($clSinRevisar) }}%;"></div></div>
          <span class="pjd-cl-stat-pct">{{ $clPct($clSinRevisar) }}%</span>
        </div>
      </div>
      <div class="pjd-cl-stat-card stat-pendiente">
        <div class="pjd-cl-stat-num">{{ $clPendiente }}</div>
        <div class="pjd-cl-stat-label">Pendiente</div>
        <div class="pjd-cl-stat-footer">
          <div class="pjd-cl-stat-bar-bg"><div class="pjd-cl-stat-bar-fill" style="width:{{ $clPct($clPendiente) }}%;"></div></div>
          <span class="pjd-cl-stat-pct">{{ $clPct($clPendiente) }}%</span>
        </div>
      </div>
      <div class="pjd-cl-stat-card stat-revisar">
        <div class="pjd-cl-stat-num">{{ $clRevisar }}</div>
        <div class="pjd-cl-stat-label">Revisar</div>
        <div class="pjd-cl-stat-footer">
          <div class="pjd-cl-stat-bar-bg"><div class="pjd-cl-stat-bar-fill" style="width:{{ $clPct($clRevisar) }}%;"></div></div>
          <span class="pjd-cl-stat-pct">{{ $clPct($clRevisar) }}%</span>
        </div>
      </div>
      <div class="pjd-cl-stat-card stat-cumple">
        <div class="pjd-cl-stat-num">{{ $clCumple }}</div>
        <div class="pjd-cl-stat-label">Cumple</div>
        <div class="pjd-cl-stat-footer">
          <div class="pjd-cl-stat-bar-bg"><div class="pjd-cl-stat-bar-fill" style="width:{{ $clPct($clCumple) }}%;"></div></div>
          <span class="pjd-cl-stat-pct">{{ $clPct($clCumple) }}%</span>
        </div>
      </div>
      <div class="pjd-cl-stat-card stat-nocumple">
        <div class="pjd-cl-stat-num">{{ $clNoCumple }}</div>
        <div class="pjd-cl-stat-label">No cumple</div>
        <div class="pjd-cl-stat-footer">
          <div class="pjd-cl-stat-bar-bg"><div class="pjd-cl-stat-bar-fill" style="width:{{ $clPct($clNoCumple) }}%;"></div></div>
          <span class="pjd-cl-stat-pct">{{ $clPct($clNoCumple) }}%</span>
        </div>
      </div>
      <div class="pjd-cl-stat-card stat-total is-active">
        <div class="pjd-cl-stat-num">{{ $clTotal }}</div>
        <div class="pjd-cl-stat-label">Total</div>
        <div class="pjd-cl-stat-footer">
          <div class="pjd-cl-stat-bar-bg"><div class="pjd-cl-stat-bar-fill" style="width:100%;"></div></div>
          <span class="pjd-cl-stat-pct">100%</span>
        </div>
      </div>
    </div>

    <div class="pjd-cl-top-actions">
      <button type="button" class="pjd-cl-icon-btn" aria-label="Descargar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      </button>
      <button type="button" class="pjd-cl-icon-btn" data-cl-expand-view aria-label="Expandir vista" title="Expandir vista" aria-expanded="false">
        <svg class="pjd-cl-expand-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
        <svg class="pjd-cl-compress-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="14" y1="10" x2="21" y2="3"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
      </button>
    </div>
  </div>

  <div class="pjd-cl-toolbar-row">
    <button type="button" class="pjd-cl-help-btn pjd-js-open-help" aria-label="Ayuda">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    </button>

    <div class="pjd-cl-search-container">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" id="pjdClSearch" placeholder="Buscar por requisito, formato o des...">
    </div>

    <button type="button" class="pjd-cl-btn is-blue pjd-js-reanalisis">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
        <path d="M5 3v4"/><path d="M3 5h4"/>
      </svg>
      Reanalisis
    </button>

    <button type="button" class="pjd-cl-btn pjd-js-nuevo">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Nuevo
    </button>
  </div>

  <div class="pjd-cl-toolbar-row-2">
    <button type="button" class="pjd-cl-btn pjd-js-toggle-menu">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
      Filtros
    </button>

    <div class="pjd-cl-menu-wrapper">
      <button type="button" class="pjd-cl-btn pjd-js-toggle-menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
        Columnas
      </button>
      <div class="pjd-cl-dropdown-menu">
        <label class="pjd-col-option"><input type="checkbox" checked> Requisito</label>
        <label class="pjd-col-option"><input type="checkbox" checked> Formato</label>
        <label class="pjd-col-option"><input type="checkbox" checked> Categoría</label>
        <label class="pjd-col-option"><input type="checkbox" checked> Aplicación</label>
        <label class="pjd-col-option"><input type="checkbox" checked> Obligatorio</label>
        <label class="pjd-col-option"><input type="checkbox" checked> Cumplimiento</label>
      </div>
    </div>
  </div>

  
  <div class="pjd-cl-table-container">
    <table class="pjd-cl-table">
      <thead>
        <tr>
          <th style="width:48px;text-align:center;">
            <input type="checkbox" class="pjd-cl-checkbox pjd-js-select-all">
          </th>
          <th style="width:48px;text-align:center;">
            <svg class="th-grip" style="margin:0 auto;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/>
              <circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/>
            </svg>
          </th>
          <th>
            <div class="th-inner"><div class="th-left">Requisito</div><span>⇅</span></div>
          </th>
          <th>
            <div class="th-inner"><div class="th-left">Formato</div><span>⇅</span></div>
          </th>
          <th>
            <div class="th-inner"><div class="th-left">Categoría</div><span>⇅</span></div>
          </th>
          <th>
            <div class="th-inner"><div class="th-left">Aplicación</div><span>⇅</span></div>
          </th>
          <th>
            <div class="th-inner"><div class="th-left">Obligatorio</div><span>⇅</span></div>
          </th>
          <th>
            <div class="th-inner"><div class="th-left">Cumplimiento</div><span>⇅</span></div>
          </th>
          <th>
            <div class="th-inner"><div class="th-left">Status</div><span>⇅</span></div>
          </th>
          <th>
            <div class="th-inner"><div class="th-left">Responsable</div><span>⇅</span></div>
          </th>
          <th>
            <div class="th-inner"><div class="th-left">Adjuntos</div><span>⇅</span></div>
          </th>
          <th>
            <div class="th-inner"><div class="th-left">Prioridad</div><span>⇅</span></div>
          </th>
          <th>
            <div class="th-inner"><div class="th-left">Fecha</div><span>⇅</span></div>
          </th>
          <th style="text-align:center;">Opciones</th>
        </tr>
      </thead>

      <tbody>
        @forelse($checklistData as $item)
          @php
            $cumplimientoClass = match($item['cumplimiento']) {
              'Cumple' => 'is-success',
              'No Cumple' => 'is-danger',
              'Parcial' => 'is-review',
              default => 'is-pending',
            };

            $statusClass = match($item['status']) {
              'Aprobado' => 'is-success',
              'En revisión' => 'is-review',
              default => 'is-pending',
            };

            $attachmentCount = count($item['adjuntos'] ?? []);
          @endphp

          <tr
            data-checklist-row="{{ $item['id'] }}"
            data-item='@json($item)'
          >
            <td style="text-align:center;">
              <input type="checkbox" class="pjd-cl-checkbox pjd-js-row-check">
            </td>

            <td style="text-align:center;">
              <button
                type="button"
                class="pjd-cl-chevron-btn pjd-js-toggle-row"
                data-item-id="{{ $item['id'] }}"
                aria-label="Abrir detalle"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="9 18 15 12 9 6"/>
                </svg>
              </button>
            </td>

            <td class="pjd-cl-main-cell">
              <span class="pjd-cl-main-text" title="{{ $item['requisito'] }}">
                {{ $item['requisito'] }}
              </span>
            </td>

            <td class="pjd-text-gray">
              {{ $item['formato'] === 'No aplica' ? 'No aplica' : $item['formato'] }}
            </td>

            <td class="pjd-text-gray" title="{{ $item['categoria'] }}">
              {{ Str::limit($item['categoria'], 24) }}
            </td>

            <td class="pjd-text-gray">
              {{ $item['aplicabilidad'] ?: 'Único' }}
            </td>

            <td style="text-align:center;color:#169447;font-weight:500;">
              {{ $item['obligatorio'] ?: 'Sí' }}
            </td>

            <td>
              <select
                class="pjd-cl-inline-select pjd-cl-status-select {{ $cumplimientoClass }} pjd-js-cumplimiento"
                data-item-id="{{ $item['id'] }}"
              >
                <option value="-" @selected(in_array($item['cumplimiento'], ['-', '', 'Pendiente'], true))>Sin revisar</option>
                <option value="Parcial" @selected($item['cumplimiento'] === 'Parcial')>Revisar</option>
                <option value="Cumple" @selected($item['cumplimiento'] === 'Cumple')>Cumple</option>
                <option value="No Cumple" @selected($item['cumplimiento'] === 'No Cumple')>No cumple</option>
              </select>
            </td>

            <td>
              <select
                class="pjd-cl-inline-select pjd-cl-status-select {{ $statusClass }} pjd-js-status"
                data-item-id="{{ $item['id'] }}"
              >
                <option value="Pendiente" @selected($item['status'] === 'Pendiente')>Pendiente</option>
                <option value="En revisión" @selected($item['status'] === 'En revisión')>En revisión</option>
                <option value="Aprobado" @selected($item['status'] === 'Aprobado')>Aprobado</option>
              </select>
            </td>

            <td>
              <select
                class="pjd-cl-inline-select pjd-cl-responsible-select pjd-js-responsable"
                data-item-id="{{ $item['id'] }}"
              >
                <option value="">Sin asignar</option>
                @foreach($checklistUsers as $user)
                  <option value="{{ $user->id }}" @selected((string) $item['responsable_id'] === (string) $user->id)>
                    {{ $user->name ?: $user->email }}
                  </option>
                @endforeach
              </select>
            </td>

            <td class="pjd-cl-attachment-cell">
              <button
                type="button"
                class="pjd-cl-attachment-btn pjd-js-attach"
                data-item-id="{{ $item['id'] }}"
                title="Adjuntar documento"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                  <path d="M14 2v6h6"/>
                  <path d="M12 18v-6"/>
                  <path d="m9 15 3-3 3 3"/>
                </svg>
                <span class="pjd-cl-attachment-count">{{ $attachmentCount }}</span>
              </button>
            </td>

            <td>
              <select
                class="pjd-cl-inline-select pjd-cl-priority-select pjd-js-priority"
                data-item-id="{{ $item['id'] }}"
              >
                <option value="Media" @selected(!in_array($item['prioridad'], ['Alta','Baja'], true))>Sin prioridad</option>
                <option value="Alta" @selected($item['prioridad'] === 'Alta')>Alta</option>
                <option value="Media" @selected($item['prioridad'] === 'Media')>Media</option>
                <option value="Baja" @selected($item['prioridad'] === 'Baja')>Baja</option>
              </select>
            </td>

            <td>
              <input
                type="date"
                class="pjd-cl-date-input pjd-js-date"
                data-item-id="{{ $item['id'] }}"
                value="{{ $item['fecha_limite'] }}"
              >
            </td>

            <td style="text-align:center;">
              <div class="pjd-cl-options-wrap">
                <button
                  type="button"
                  class="pjd-cl-options-btn pjd-js-options"
                  data-item-id="{{ $item['id'] }}"
                  aria-label="Opciones"
                >•••</button>
              </div>
            </td>
          </tr>

          <tr class="pjd-cl-detail-row" data-detail-id="{{ $item['id'] }}">
            <td colspan="14" style="padding:0;">
              <div class="pjd-cl-detail-content">
                <div class="pjd-cl-detail-label">Descripción:</div>

                <p class="pjd-cl-detail-meta">
                  {{ $item['descripcion'] ?: 'Sin descripción adicional.' }}<br>
                  <strong>Archivo:</strong> {{ $item['formato'] ?: 'No aplica' }}<br>
                  <strong>Fuente:</strong> {{ $item['fuente'] ?: 'No aplica' }}<br>
                  <strong>Página de extracción:</strong> {{ $item['pagina'] ?: 'No aplica' }}
                  @if($item['cita'])
                    <br><strong>Cita:</strong> {{ $item['cita'] }}
                  @endif
                </p>

                <hr class="pjd-cl-detail-sep">

                <div class="pjd-cl-detail-label">Atributos:</div>

                <div class="pjd-cl-detail-attrs">
                  <div class="pjd-cl-detail-attr-row">
                    <span class="pjd-cl-detail-attr-label">Prioridad:</span>
                    <div class="pjd-cl-priority-group">
                      <button type="button" class="pjd-cl-priority-btn {{ $item['prioridad'] === 'Alta' ? 'is-active' : '' }}" data-item-id="{{ $item['id'] }}" data-priority="Alta">Alta</button>
                      <button type="button" class="pjd-cl-priority-btn {{ $item['prioridad'] === 'Media' ? 'is-active' : '' }}" data-item-id="{{ $item['id'] }}" data-priority="Media">Media</button>
                      <button type="button" class="pjd-cl-priority-btn {{ $item['prioridad'] === 'Baja' ? 'is-active' : '' }}" data-item-id="{{ $item['id'] }}" data-priority="Baja">Baja</button>
                    </div>
                  </div>

                  <div class="pjd-cl-detail-attr-row" style="justify-content:flex-end;">
                    <span class="pjd-cl-detail-attr-label">Fecha límite:</span>
                    <input
                      type="date"
                      class="pjd-cl-detail-date pjd-js-date"
                      data-item-id="{{ $item['id'] }}"
                      value="{{ $item['fecha_limite'] }}"
                    >
                  </div>

                  <div class="pjd-cl-detail-attr-row">
                    <span class="pjd-cl-detail-attr-label">Responsable:</span>
                    <select class="pjd-cl-detail-select pjd-js-responsable" data-item-id="{{ $item['id'] }}">
                      <option value="">Sin asignar</option>
                      @foreach($checklistUsers as $user)
                        <option value="{{ $user->id }}" @selected((string) $item['responsable_id'] === (string) $user->id)>
                          {{ $user->name ?: $user->email }}
                        </option>
                      @endforeach
                    </select>
                  </div>

                  <div class="pjd-cl-detail-attr-row">
                    <span class="pjd-cl-detail-attr-label">Revisor:</span>
                    <select class="pjd-cl-detail-select pjd-js-revisor" data-item-id="{{ $item['id'] }}">
                      <option value="">Sin asignar</option>
                      @foreach($checklistUsers as $user)
                        <option value="{{ $user->id }}" @selected((string) $item['revisor_id'] === (string) $user->id)>
                          {{ $user->name ?: $user->email }}
                        </option>
                      @endforeach
                    </select>
                  </div>
                </div>


                <div class="pjd-cl-detail-block">
                  <div class="pjd-cl-detail-block-head">
                    <div class="pjd-cl-detail-block-title">
                      Notas
                    </div>

                    <button
                      type="button"
                      class="pjd-cl-detail-action pjd-js-note-edit"
                      data-item-id="{{ $item['id'] }}"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 20h9"/>
                        <path d="M16.5 3.5a2.12 2.12 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/>
                      </svg>
                      Editar
                    </button>
                  </div>

                  <div
                    class="pjd-cl-note-list"
                    data-note-list="{{ $item['id'] }}"
                  >
                    @forelse(($item['notas'] ?? []) as $nota)
                      <div class="pjd-cl-note-item">
                        {{ is_array($nota) ? ($nota['body'] ?? '') : $nota }}

                        @if(is_array($nota) && !empty($nota['user_name']))
                          <span class="pjd-cl-note-meta">
                            {{ $nota['user_name'] }}
                            @if(!empty($nota['created_at']))
                              · {{ $nota['created_at'] }}
                            @endif
                          </span>
                        @endif
                      </div>
                    @empty
                      <div class="pjd-cl-note-item is-empty">
                        No hay notas agregadas.
                      </div>
                    @endforelse
                  </div>

                  <div
                    class="pjd-cl-note-editor"
                    data-note-editor="{{ $item['id'] }}"
                  >
                    <textarea
                      placeholder="Escribe una nota para este requisito..."
                    ></textarea>

                    <div class="pjd-cl-note-editor-actions">
                      <button
                        type="button"
                        class="pjd-cl-note-cancel pjd-js-note-cancel"
                        data-item-id="{{ $item['id'] }}"
                      >
                        Cancelar
                      </button>

                      <button
                        type="button"
                        class="pjd-cl-note-save pjd-js-note-save"
                        data-item-id="{{ $item['id'] }}"
                      >
                        Guardar nota
                      </button>
                    </div>
                  </div>
                </div>

                <div class="pjd-cl-detail-block">
                  <div class="pjd-cl-detail-block-head">
                    <div class="pjd-cl-detail-block-title">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <path d="M14 2v6h6"/>
                      </svg>
                      Documentos Adjuntos
                    </div>

                    <button
                      type="button"
                      class="pjd-cl-detail-action pjd-js-attach"
                      data-item-id="{{ $item['id'] }}"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 3v12"/>
                        <path d="m7 8 5-5 5 5"/>
                        <path d="M5 21h14a2 2 0 0 0 2-2v-4"/>
                        <path d="M3 15v4a2 2 0 0 0 2 2"/>
                      </svg>
                      Adjuntar
                    </button>
                  </div>

                  <div
                    class="pjd-cl-attachment-list"
                    data-attachment-list="{{ $item['id'] }}"
                  >
                    @forelse(($item['adjuntos'] ?? []) as $adjunto)
                      <div class="pjd-cl-attachment-item">
                        <div class="pjd-cl-attachment-info">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <path d="M14 2v6h6"/>
                          </svg>

                          <span class="pjd-cl-attachment-name">
                            {{ is_array($adjunto) ? ($adjunto['name'] ?? 'Documento') : $adjunto }}
                          </span>
                        </div>

                        @if(is_array($adjunto) && !empty($adjunto['url']))
                          <a
                            href="{{ $adjunto['url'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="pjd-cl-attachment-open"
                          >
                            Abrir
                          </a>
                        @endif
                      </div>
                    @empty
                      <div class="pjd-cl-attachment-empty">
                        No hay documentos adjuntos.
                      </div>
                    @endforelse
                  </div>
                </div>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="14" style="padding:40px 20px;text-align:center;color:var(--pjd-text-muted);">
              Sin registros.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <input type="file" class="pjd-js-hidden-attachment" multiple hidden>

  <div class="pjd-cl-row-menu pjd-js-row-menu">
    <button type="button" data-row-action="edit">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 20h9"/>
        <path d="M16.5 3.5a2.12 2.12 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/>
      </svg>
      Editar
    </button>

    <button type="button" data-row-action="duplicate">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="9" y="9" width="13" height="13" rx="2"/>
        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
      </svg>
      Duplicar
    </button>

    <button type="button" class="is-danger" data-row-action="delete">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M3 6h18"/>
        <path d="M8 6V4h8v2"/>
        <path d="M19 6l-1 14H6L5 6"/>
      </svg>
      Eliminar
    </button>
  </div>

  <div class="pjd-cl-edit-modal pjd-js-edit-modal">
    <form class="pjd-cl-edit-card pjd-js-edit-form">
      <div class="pjd-cl-edit-header">
        <h3 class="pjd-cl-edit-title">Editar requisito</h3>
        <button type="button" class="pjd-cl-edit-close pjd-js-edit-close">×</button>
      </div>

      <input type="hidden" name="id">

      <div class="pjd-cl-edit-grid">
        <div class="pjd-cl-edit-field is-full">
          <label>Requisito</label>
          <input type="text" name="requisito" required>
        </div>

        <div class="pjd-cl-edit-field">
          <label>Formato</label>
          <input type="text" name="formato">
        </div>

        <div class="pjd-cl-edit-field">
          <label>Categoría</label>
          <input type="text" name="categoria">
        </div>

        <div class="pjd-cl-edit-field">
          <label>Aplicación</label>
          <input type="text" name="aplicabilidad">
        </div>

        <div class="pjd-cl-edit-field">
          <label>Obligatorio</label>
          <select name="obligatorio">
            <option value="Sí">Sí</option>
            <option value="No">No</option>
          </select>
        </div>

        <div class="pjd-cl-edit-field is-full">
          <label>Descripción</label>
          <textarea name="descripcion"></textarea>
        </div>

        <div class="pjd-cl-edit-field is-full">
          <label>Criterio de cumplimiento</label>
          <textarea name="criterio_cumplimiento"></textarea>
        </div>
      </div>

      <div class="pjd-cl-edit-actions">
        <button type="button" class="pjd-cl-edit-cancel pjd-js-edit-close">Cancelar</button>
        <button type="submit" class="pjd-cl-edit-save">Guardar cambios</button>
      </div>
    </form>
  </div>

<div class="pjd-modal-overlay pjd-js-help-modal">
  <div class="pjd-modal">
    <button type="button" class="pjd-modal-close pjd-js-close-help">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>

    <div class="pjd-modal-header">
      <div class="pjd-modal-title-group">
        <svg class="pjd-modal-title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
        <div>
          <h3>Clasificaciones de la checklist</h3>
          <p>Estas banderas indican la accion operativa que corresponde a cada documento.</p>
        </div>
      </div>
    </div>

    <div class="pjd-modal-grid">
      <div class="pjd-status-card pjd-card-pendiente">
        <div class="pjd-badge">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          Pendiente
        </div>
        <p>Documentos que requieren redaccion. No corresponden a archivos que la empresa deba localizar, solicitar o recabar.</p>
      </div>
      <div class="pjd-status-card pjd-card-revisar">
        <div class="pjd-badge">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          Revisar
        </div>
        <p>Documentos redactados que requieren revision, o documentos recabables que aun no estan cargados pero que la empresa si puede obtener.</p>
      </div>
      <div class="pjd-status-card pjd-card-cumple">
        <div class="pjd-badge">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="8 12 11 15 16 9"/></svg>
          Cumple
        </div>
        <p>Documentos ya cargados en la checklist, sea porque fueron recabados o porque Monico o el usuario los prepararon en version final.</p>
      </div>
      <div class="pjd-status-card pjd-card-nocumple">
        <div class="pjd-badge">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          No cumple
        </div>
        <p>Documentos que deben reclamarse o escalarse porque la empresa no cuenta con ellos.</p>
      </div>
    </div>
  </div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const pane = document.getElementById('pjdChecklistPane');

  if (!pane || pane.dataset.initialized === '1') {
    return;
  }

  pane.dataset.initialized = '1';

  const $ = (selector) => pane.querySelector(selector);
  const $$ = (selector) => Array.from(pane.querySelectorAll(selector));

  const checklistUrl = @json(route('projects.checklist', $project));
  const attachmentUrl = @json(route('projects.checklist.attach', $project));
  const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    || @json(csrf_token());

  let activeItemId = null;
  let saving = false;

  const notify = function (message, type = 'success') {
    let toast = document.querySelector('.pjd-cl-backend-toast');

    if (!toast) {
      toast = document.createElement('div');
      toast.className = 'pjd-cl-backend-toast';
      toast.style.cssText = [
        'position:fixed',
        'right:20px',
        'bottom:20px',
        'z-index:10100',
        'max-width:360px',
        'padding:11px 14px',
        'border-radius:8px',
        'border:1px solid #e5e7eb',
        'background:#fff',
        'box-shadow:0 12px 28px rgba(0,0,0,.14)',
        'font-size:13px',
        'font-weight:600'
      ].join(';');

      document.body.appendChild(toast);
    }

    toast.textContent = message;
    toast.style.color = type === 'error' ? '#b91c1c' : '#15803d';
    toast.style.borderColor = type === 'error' ? '#fecaca' : '#bbf7d0';
    toast.style.display = 'block';

    clearTimeout(toast._timer);

    toast._timer = setTimeout(function () {
      toast.style.display = 'none';
    }, 3000);
  };

  const requestJson = async function (payload) {
    if (saving) {
      throw new Error('Espera a que termine el guardado anterior.');
    }

    saving = true;
    pane.setAttribute('aria-busy', 'true');

    try {
      const response = await fetch(checklistUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
      });

      const data = await response.json().catch(function () {
        return {};
      });

      if (!response.ok || data.ok === false) {
        throw new Error(
          data.message
          || data.error
          || 'No se pudo guardar el cambio.'
        );
      }

      return data;
    } finally {
      saving = false;
      pane.removeAttribute('aria-busy');
    }
  };

  const getRow = function (id) {
    return pane.querySelector(
      '[data-checklist-row="' + CSS.escape(String(id)) + '"]'
    );
  };

  const getItem = function (id) {
    const row = getRow(id);

    if (!row) {
      return {};
    }

    try {
      return JSON.parse(row.dataset.item || '{}');
    } catch (error) {
      return {};
    }
  };


  const setItem = function (id, item) {
    const row = getRow(id);

    if (row) {
      row.dataset.item = JSON.stringify(item || {});
    }
  };

  const syncItemFromResponse = function (id, response, fallbackChanges = {}) {
    const current = getItem(id);
    const updated = response && response.item
      ? response.item
      : Object.assign({}, current, fallbackChanges);

    setItem(id, updated);

    return updated;
  };

  const syncCounters = function (payload) {
    if (!payload || !payload.counters) {
      return;
    }

    const counters = payload.counters;
    const mapping = {
      sin_revisar: 0,
      pendiente: 1,
      revision: 2,
      cumple: 3,
      no_cumple: 4,
      total: 5
    };

    const cards = $$('.pjd-cl-stat-card');

    Object.keys(mapping).forEach(function (key) {
      const card = cards[mapping[key]];

      if (!card || typeof counters[key] === 'undefined') {
        return;
      }

      const number = card.querySelector('.pjd-cl-stat-num');
      const fill = card.querySelector('.pjd-cl-stat-bar-fill');
      const pct = card.querySelector('.pjd-cl-stat-pct');
      const total = Number(counters.total || 0);
      const value = Number(counters[key] || 0);
      const percent = key === 'total'
        ? 100
        : (total > 0 ? Math.round((value / total) * 100) : 0);

      if (number) number.textContent = value;
      if (fill) fill.style.width = percent + '%';
      if (pct) pct.textContent = percent + '%';
    });
  };

  const updateItem = async function (id, changes) {
    const item = Object.assign({}, getItem(id), changes);

    if (!item.requisito) {
      item.requisito =
        getRow(id)?.querySelector('.pjd-cl-main-text')?.textContent.trim()
        || 'Requisito';
    }

    const response = await requestJson({
      action: 'update',
      id: id,
      item: item
    });

    syncItemFromResponse(id, response, changes);
    syncCounters(response.payload);

    return response;
  };

  const closeRowMenu = function () {
    const menu = $('.pjd-js-row-menu');

    if (menu) {
      menu.classList.remove('show');
    }

    activeItemId = null;
  };

  /* Expandir detalle */
  $$('.pjd-js-toggle-row').forEach(function (button) {
    button.addEventListener('click', function () {
      const row = button.closest('tr');
      const detail = row ? row.nextElementSibling : null;

      button.classList.toggle('open');

      if (detail && detail.classList.contains('pjd-cl-detail-row')) {
        detail.classList.toggle('open');
      }
    });
  });

  /* Seleccionar todo */
  const selectAll = $('.pjd-js-select-all');

  if (selectAll) {
    selectAll.addEventListener('change', function () {
      $$('.pjd-js-row-check').forEach(function (checkbox) {
        checkbox.checked = selectAll.checked;
      });
    });
  }

  /* Cambiar cumplimiento */
  $$('.pjd-js-cumplimiento').forEach(function (select) {
    select.addEventListener('change', async function () {
      try {
        await updateItem(select.dataset.itemId, {
          cumplimiento: select.value
        });

        const item = getItem(select.dataset.itemId);
        item.cumplimiento = select.value;
        setItem(select.dataset.itemId, item);

        select.classList.remove('is-pending', 'is-review', 'is-success', 'is-danger');

        if (select.value === 'Cumple') {
          select.classList.add('is-success');
        } else if (select.value === 'No Cumple') {
          select.classList.add('is-danger');
        } else if (select.value === 'Parcial') {
          select.classList.add('is-review');
        } else {
          select.classList.add('is-pending');
        }

        notify('Cumplimiento guardado.');
      } catch (error) {
        notify(error.message, 'error');
      }
    });
  });

  /* Cambiar status */
  $$('.pjd-js-status').forEach(function (select) {
    select.addEventListener('change', async function () {
      try {
        await updateItem(select.dataset.itemId, {
          status: select.value
        });

        const item = getItem(select.dataset.itemId);
        item.status = select.value;
        setItem(select.dataset.itemId, item);

        select.classList.remove('is-pending', 'is-review', 'is-success');

        if (select.value === 'Aprobado') {
          select.classList.add('is-success');
        } else if (select.value === 'En revisión') {
          select.classList.add('is-review');
        } else {
          select.classList.add('is-pending');
        }

        notify('Status guardado.');
      } catch (error) {
        notify(error.message, 'error');
      }
    });
  });

  /* Responsable */
  $$('.pjd-js-responsable').forEach(function (select) {
    select.addEventListener('change', async function () {
      const option = select.options[select.selectedIndex];

      try {
        await updateItem(select.dataset.itemId, {
          responsable_id: select.value || null,
          responsable: select.value
            ? option.textContent.trim()
            : ''
        });

        notify('Responsable guardado.');
      } catch (error) {
        notify(error.message, 'error');
      }
    });
  });

  /* Revisor */
  $$('.pjd-js-revisor').forEach(function (select) {
    select.addEventListener('change', async function () {
      const option = select.options[select.selectedIndex];

      try {
        await updateItem(select.dataset.itemId, {
          revisor_id: select.value || null,
          revisor: select.value
            ? option.textContent.trim()
            : ''
        });

        notify('Revisor guardado.');
      } catch (error) {
        notify(error.message, 'error');
      }
    });
  });

  /* Prioridad de tabla */
  $$('.pjd-js-priority').forEach(function (select) {
    select.addEventListener('change', async function () {
      try {
        await updateItem(select.dataset.itemId, {
          prioridad: select.value
        });

        notify('Prioridad guardada.');
      } catch (error) {
        notify(error.message, 'error');
      }
    });
  });

  /* Prioridad del detalle */
  $$('.pjd-cl-priority-group').forEach(function (group) {
    group.querySelectorAll('.pjd-cl-priority-btn').forEach(function (button) {
      button.addEventListener('click', async function () {
        const id = button.dataset.itemId;
        const priority = button.dataset.priority;

        group.querySelectorAll('.pjd-cl-priority-btn').forEach(function (item) {
          item.classList.remove('is-active');
        });

        button.classList.add('is-active');

        try {
          await updateItem(id, {
            prioridad: priority
          });

          const tableSelect = pane.querySelector(
            '.pjd-js-priority[data-item-id="'
            + CSS.escape(String(id))
            + '"]'
          );

          if (tableSelect) {
            tableSelect.value = priority;
          }

          notify('Prioridad guardada.');
        } catch (error) {
          notify(error.message, 'error');
        }
      });
    });
  });

  /* Fecha */
  $$('.pjd-js-date').forEach(function (input) {
    input.addEventListener('change', async function () {
      try {
        await updateItem(input.dataset.itemId, {
          fecha_limite: input.value || null
        });

        $$('.pjd-js-date[data-item-id="'
          + CSS.escape(String(input.dataset.itemId))
          + '"]').forEach(function (other) {
            other.value = input.value;
          });

        notify('Fecha guardada.');
      } catch (error) {
        notify(error.message, 'error');
      }
    });
  });

  /* Notas: abrir editor, cancelar y guardar sin recargar */
  $$('.pjd-js-note-edit').forEach(function (button) {
    button.addEventListener('click', function () {
      const id = button.dataset.itemId;
      const editor = pane.querySelector(
        '[data-note-editor="' + CSS.escape(String(id)) + '"]'
      );

      if (editor) {
        editor.classList.add('show');
        editor.querySelector('textarea')?.focus();
      }
    });
  });

  $$('.pjd-js-note-cancel').forEach(function (button) {
    button.addEventListener('click', function () {
      const id = button.dataset.itemId;
      const editor = pane.querySelector(
        '[data-note-editor="' + CSS.escape(String(id)) + '"]'
      );

      if (editor) {
        editor.classList.remove('show');
        const textarea = editor.querySelector('textarea');
        if (textarea) textarea.value = '';
      }
    });
  });

  $$('.pjd-js-note-save').forEach(function (button) {
    button.addEventListener('click', async function () {
      const id = button.dataset.itemId;
      const editor = pane.querySelector(
        '[data-note-editor="' + CSS.escape(String(id)) + '"]'
      );
      const textarea = editor?.querySelector('textarea');
      const body = textarea?.value.trim() || '';

      if (!body) {
        notify('Escribe una nota antes de guardar.', 'error');
        return;
      }

      button.classList.add('pjd-cl-saving');

      try {
        const response = await requestJson({
          action: 'note',
          id: id,
          body: body
        });

        const list = pane.querySelector(
          '[data-note-list="' + CSS.escape(String(id)) + '"]'
        );

        if (list) {
          list.querySelector('.is-empty')?.remove();

          const note = response.note || {};
          const item = document.createElement('div');
          item.className = 'pjd-cl-note-item';
          item.textContent = note.body || body;

          if (note.user_name || note.created_at) {
            const meta = document.createElement('span');
            meta.className = 'pjd-cl-note-meta';
            meta.textContent = [
              note.user_name || '',
              note.created_at || ''
            ].filter(Boolean).join(' · ');

            item.appendChild(meta);
          }

          list.appendChild(item);
        }

        syncItemFromResponse(id, response, {});
        syncCounters(response.payload);

        textarea.value = '';
        editor.classList.remove('show');

        notify('Nota guardada.');
      } catch (error) {
        notify(error.message, 'error');
      } finally {
        button.classList.remove('pjd-cl-saving');
      }
    });
  });


  /* Adjuntar archivos */
  const hiddenAttachment = $('.pjd-js-hidden-attachment');
  let attachmentItemId = null;

  $$('.pjd-js-attach').forEach(function (button) {
    button.addEventListener('click', function () {
      attachmentItemId = button.dataset.itemId;

      if (hiddenAttachment) {
        hiddenAttachment.value = '';
        hiddenAttachment.click();
      }
    });
  });

  if (hiddenAttachment) {
    hiddenAttachment.addEventListener('change', async function () {
      if (!attachmentItemId || !hiddenAttachment.files.length) {
        return;
      }

      const formData = new FormData();
      formData.append('id', attachmentItemId);

      Array.from(hiddenAttachment.files).forEach(function (file) {
        formData.append('files[]', file);
      });

      try {
        const response = await fetch(attachmentUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: formData
        });

        const data = await response.json().catch(function () {
          return {};
        });

        if (!response.ok || data.ok === false) {
          throw new Error(
            data.message
            || data.error
            || 'No se pudieron adjuntar los archivos.'
          );
        }

        const list = pane.querySelector(
          '[data-attachment-list="' + CSS.escape(String(attachmentItemId)) + '"]'
        );

        if (list) {
          list.querySelector('.pjd-cl-attachment-empty')?.remove();

          (data.attachments || []).forEach(function (attachment) {
            const row = document.createElement('div');
            row.className = 'pjd-cl-attachment-item';

            const info = document.createElement('div');
            info.className = 'pjd-cl-attachment-info';
            info.innerHTML =
              '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">'
              + '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>'
              + '<path d="M14 2v6h6"/>'
              + '</svg>';

            const name = document.createElement('span');
            name.className = 'pjd-cl-attachment-name';
            name.textContent = attachment.name || 'Documento';
            info.appendChild(name);

            row.appendChild(info);

            if (attachment.url) {
              const link = document.createElement('a');
              link.href = attachment.url;
              link.target = '_blank';
              link.rel = 'noopener noreferrer';
              link.className = 'pjd-cl-attachment-open';
              link.textContent = 'Abrir';
              row.appendChild(link);
            }

            list.appendChild(row);
          });
        }

        const countBadge = pane.querySelector(
          '.pjd-js-attach[data-item-id="'
          + CSS.escape(String(attachmentItemId))
          + '"] .pjd-cl-attachment-count'
        );

        if (countBadge) {
          countBadge.textContent = String(
            Number(countBadge.textContent || 0)
            + (data.attachments || []).length
          );
        }

        syncItemFromResponse(attachmentItemId, data, {});
        syncCounters(data.payload);

        notify('Documento adjuntado correctamente.');
      } catch (error) {
        notify(error.message, 'error');
      }
    });
  }

  /* Menú de opciones */
  const rowMenu = $('.pjd-js-row-menu');

  $$('.pjd-js-options').forEach(function (button) {
    button.addEventListener('click', function (event) {
      event.stopPropagation();

      activeItemId = button.dataset.itemId;

      if (!rowMenu) {
        return;
      }

      const rect = button.getBoundingClientRect();
      const menuWidth = 190;
      const left = Math.max(
        10,
        Math.min(
          window.innerWidth - menuWidth - 10,
          rect.right - menuWidth
        )
      );

      const estimatedHeight = 150;
      const top = rect.bottom + estimatedHeight > window.innerHeight
        ? Math.max(10, rect.top - estimatedHeight)
        : rect.bottom + 6;

      rowMenu.style.left = left + 'px';
      rowMenu.style.top = top + 'px';
      rowMenu.classList.add('show');
    });
  });

  document.addEventListener('click', function (event) {
    if (
      rowMenu
      && !rowMenu.contains(event.target)
      && !event.target.closest('.pjd-js-options')
    ) {
      closeRowMenu();
    }
  });

  /* Modal editar */
  const editModal = $('.pjd-js-edit-modal');
  const editForm = $('.pjd-js-edit-form');

  const closeEditModal = function () {
    if (editModal) {
      editModal.classList.remove('show');
    }
  };

  $$('.pjd-js-edit-close').forEach(function (button) {
    button.addEventListener('click', closeEditModal);
  });

  if (editModal) {
    editModal.addEventListener('click', function (event) {
      if (event.target === editModal) {
        closeEditModal();
      }
    });
  }

  const openEditModal = function (id) {
    if (!editModal || !editForm) {
      return;
    }

    const item = getItem(id);

    editForm.elements.id.value = id;
    editForm.elements.requisito.value = item.requisito || '';
    editForm.elements.formato.value = item.formato || 'No aplica';
    editForm.elements.categoria.value =
      item.categoria || 'Legal-Administrativo';
    editForm.elements.aplicabilidad.value =
      item.aplicabilidad || 'Único';
    editForm.elements.obligatorio.value =
      item.obligatorio || 'Sí';
    editForm.elements.descripcion.value =
      item.descripcion || '';
    editForm.elements.criterio_cumplimiento.value =
      item.criterio_cumplimiento || '';

    editModal.classList.add('show');
  };

  if (rowMenu) {
    rowMenu.querySelectorAll('[data-row-action]').forEach(function (button) {
      button.addEventListener('click', async function () {
        const action = button.dataset.rowAction;
        const id = activeItemId;

        closeRowMenu();

        if (!id) {
          return;
        }

        if (action === 'edit') {
          openEditModal(id);
          return;
        }

        if (action === 'duplicate') {
          try {
            await requestJson({
              action: 'duplicate',
              id: id
            });

            notify('Requisito duplicado. Aparecerá al volver a abrir esta vista.');
              } catch (error) {
            notify(error.message, 'error');
          }

          return;
        }

        if (action === 'delete') {
          const item = getItem(id);

          if (!window.confirm(
            '¿Eliminar el requisito "' + (item.requisito || '') + '"?'
          )) {
            return;
          }

          try {
            await requestJson({
              action: 'delete',
              id: id
            });

            const row = getRow(id);
            const detail = row?.nextElementSibling;

            row?.remove();

            if (detail && detail.classList.contains('pjd-cl-detail-row')) {
              detail.remove();
            }

            syncCounters((await Promise.resolve({ payload: null })).payload);
            notify('Requisito eliminado.');
              } catch (error) {
            notify(error.message, 'error');
          }
        }
      });
    });
  }

  if (editForm) {
    editForm.addEventListener('submit', async function (event) {
      event.preventDefault();

      const id = editForm.elements.id.value;
      const current = getItem(id);

      const item = Object.assign({}, current, {
        requisito: editForm.elements.requisito.value.trim(),
        formato: editForm.elements.formato.value.trim() || 'No aplica',
        categoria:
          editForm.elements.categoria.value.trim()
          || 'Legal-Administrativo',
        aplicabilidad:
          editForm.elements.aplicabilidad.value.trim()
          || 'Único',
        obligatorio: editForm.elements.obligatorio.value,
        descripcion: editForm.elements.descripcion.value.trim(),
        criterio_cumplimiento:
          editForm.elements.criterio_cumplimiento.value.trim()
      });

      try {
        await requestJson({
          action: 'update',
          id: id,
          item: item
        });

        closeEditModal();

        const row = getRow(id);
        const title = row?.querySelector('.pjd-cl-main-text');

        if (title) {
          title.textContent = item.requisito;
          title.setAttribute('title', item.requisito);
        }

        if (row) {
          const cells = row.querySelectorAll('td');

          if (cells[3]) cells[3].textContent = item.formato;
          if (cells[4]) cells[4].textContent = item.categoria;
          if (cells[5]) cells[5].textContent = item.aplicabilidad;
          if (cells[6]) cells[6].textContent = item.obligatorio;
        }

        setItem(id, item);
        notify('Requisito actualizado.');
      } catch (error) {
        notify(error.message, 'error');
      }
    });
  }

  /* Dropdowns de toolbar */
  $$('.pjd-js-toggle-menu').forEach(function (button) {
    button.addEventListener('click', function (event) {
      event.stopPropagation();

      const menu = button.nextElementSibling;

      $$('.pjd-cl-dropdown-menu.show').forEach(function (opened) {
        if (opened !== menu) {
          opened.classList.remove('show');
        }
      });

      if (menu && menu.classList.contains('pjd-cl-dropdown-menu')) {
        menu.classList.toggle('show');
      }
    });
  });

  $$('.pjd-cl-dropdown-menu').forEach(function (menu) {
    menu.addEventListener('click', function (event) {
      event.stopPropagation();
    });
  });

  /* Ayuda */
  const helpModal = $('.pjd-js-help-modal');
  const openHelp = $('.pjd-js-open-help');
  const closeHelp = $('.pjd-js-close-help');

  if (openHelp && helpModal) {
    openHelp.addEventListener('click', function () {
      helpModal.classList.add('show');
    });
  }

  if (closeHelp && helpModal) {
    closeHelp.addEventListener('click', function () {
      helpModal.classList.remove('show');
    });
  }

  if (helpModal) {
    helpModal.addEventListener('click', function (event) {
      if (event.target === helpModal) {
        helpModal.classList.remove('show');
      }
    });
  }

  /* Buscar */
  const search = $('#pjdClSearch');

  if (search) {
    search.addEventListener('input', function () {
      const query = search.value.toLowerCase().trim();

      $$('[data-checklist-row]').forEach(function (row) {
        const matches = row.innerText.toLowerCase().includes(query);
        const detail = row.nextElementSibling;

        row.style.display = matches ? '' : 'none';

        if (
          detail
          && detail.classList.contains('pjd-cl-detail-row')
          && !matches
        ) {
          detail.classList.remove('open');
        }
      });
    });
  }

  /* Nuevo */
  const newButton = $('.pjd-js-nuevo');

  if (newButton) {
    newButton.addEventListener('click', async function () {
      const requirement = window.prompt('Nombre del nuevo requisito:');

      if (!requirement || !requirement.trim()) {
        return;
      }

      try {
        await requestJson({
          action: 'create',
          item: {
            requisito: requirement.trim(),
            descripcion: '',
            criterio_cumplimiento: '',
            formato: 'No aplica',
            categoria: 'Legal-Administrativo',
            aplicabilidad: 'Único',
            obligatorio: 'Sí',
            cumplimiento: '-',
            status: 'Pendiente',
            prioridad: 'Media'
          }
        });

        notify('Requisito creado. Aparecerá al volver a abrir esta vista.');
      } catch (error) {
        notify(error.message, 'error');
      }
    });
  }

  /* Reanálisis */
  const reanalysisButton = $('.pjd-js-reanalisis');

  if (reanalysisButton) {
    reanalysisButton.addEventListener('click', async function () {
      if (!window.confirm(
        '¿Volver a analizar los documentos y actualizar el checklist?'
      )) {
        return;
      }

      const original = reanalysisButton.innerHTML;
      reanalysisButton.disabled = true;
      reanalysisButton.textContent = 'Analizando...';

      try {
        await requestJson({
          regenerate: true
        });

        notify('Checklist reanalizado correctamente.');
      } catch (error) {
        notify(error.message, 'error');
        reanalysisButton.disabled = false;
        reanalysisButton.innerHTML = original;
      }
    });
  }

  /* Descargar CSV */
  const downloadButton = $('[aria-label="Descargar"]');

  if (downloadButton) {
    downloadButton.addEventListener('click', function () {
      const rows = [[
        'Requisito',
        'Formato',
        'Categoría',
        'Aplicación',
        'Obligatorio',
        'Cumplimiento',
        'Status',
        'Responsable',
        'Prioridad',
        'Fecha'
      ]];

      $$('[data-checklist-row]').forEach(function (row) {
        const cells = row.querySelectorAll('td');

        rows.push([
          cells[2]?.innerText.trim() || '',
          cells[3]?.innerText.trim() || '',
          cells[4]?.innerText.trim() || '',
          cells[5]?.innerText.trim() || '',
          cells[6]?.innerText.trim() || '',
          row.querySelector('.pjd-js-cumplimiento')?.value || '',
          row.querySelector('.pjd-js-status')?.value || '',
          row.querySelector('.pjd-js-responsable option:checked')
            ?.textContent.trim() || '',
          row.querySelector('.pjd-js-priority')?.value || '',
          row.querySelector('.pjd-js-date')?.value || ''
        ]);
      });

      const csv = rows.map(function (row) {
        return row.map(function (cell) {
          return '"' + String(cell).replace(/"/g, '""') + '"';
        }).join(',');
      }).join('\n');

      const blob = new Blob(['\uFEFF' + csv], {
        type: 'text/csv;charset=utf-8;'
      });

      const url = URL.createObjectURL(blob);
      const anchor = document.createElement('a');

      anchor.href = url;
      anchor.download = 'checklist.csv';
      document.body.appendChild(anchor);
      anchor.click();
      anchor.remove();

      URL.revokeObjectURL(url);
    });
  }

  /* Expandir vista */
  const expandButton = $('[data-cl-expand-view]');

  const setExpanded = function (expanded) {
    pane.classList.toggle('pjd-cl-is-expanded', expanded);
    document.body.classList.toggle('pjd-cl-body-locked', expanded);

    if (expandButton) {
      expandButton.setAttribute(
        'aria-expanded',
        expanded ? 'true' : 'false'
      );

      expandButton.setAttribute(
        'title',
        expanded ? 'Contraer vista' : 'Expandir vista'
      );
    }

    window.dispatchEvent(new Event('resize'));
  };

  if (expandButton) {
    expandButton.addEventListener('click', function () {
      setExpanded(
        !pane.classList.contains('pjd-cl-is-expanded')
      );
    });
  }

  document.addEventListener('keydown', function (event) {
    if (
      event.key === 'Escape'
      && pane.classList.contains('pjd-cl-is-expanded')
    ) {
      setExpanded(false);
    }
  });
});
</script>