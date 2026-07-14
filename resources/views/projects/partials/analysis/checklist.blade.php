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
              'formato' => $item->format,
              'categoria' => $item->category,
              'aplicabilidad' => $item->applicability,
              'obligatorio' => $item->mandatory ? 'Sí' : 'No',
              'cumplimiento' => $item->compliance_status ?? 'Pendiente',
              'status' => $item->review_status ?? 'Pendiente',
          ];
      })->all();
  }

  $checklistData = collect($rawChecklist)->filter(fn($item) => is_array($item))->map(function ($item, $index) use ($clText) {
      return [
          'id' => $item['id'] ?? ($index + 1),
          'requisito' => $clText($item['requisito'] ?? $item['requirement'] ?? $item['item'] ?? null, 'Requisito sin nombre'),
          'formato' => $clText($item['formato'] ?? $item['format'] ?? 'No aplica', 'No aplica'),
          'categoria' => $clText($item['categoria'] ?? $item['category'] ?? 'Legal-Administrativo', 'Legal-Administrativo'),
          'aplicabilidad' => $clText($item['aplicabilidad'] ?? $item['applicability'] ?? 'Único', 'Único'),
          'obligatorio' => $clText($item['obligatorio'] ?? $item['mandatory'] ?? 'Sí', 'Sí'),
          'cumplimiento' => $clText($item['cumplimiento'] ?? $item['compliance'] ?? 'Pendiente', 'Pendiente'),
          'status' => $clText($item['status'] ?? $item['review_status'] ?? 'Pendiente', 'Pendiente'),
      ];
  })->all();
@endphp

<style>
:root {
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
   CONTADORES SUPERIORES
   ======================================= */
.pjd-cl-stats-container {
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
}
/* GRID ESTRICTO para eliminar scrollbars */
.pjd-cl-stats-row {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 12px;
  flex: 1;
}
.pjd-cl-stat-card {
  min-width: 0; /* Permite que el grid encoja las tarjetas si es necesario */
  border: 1px solid var(--pjd-border);
  border-radius: 8px;
  padding: 16px 8px;
  display: flex;
  flex-direction: column;
  background: #fff;
}
.pjd-cl-stat-card.is-active {
  border-color: var(--pjd-primary);
  box-shadow: 0 0 0 1px var(--pjd-primary);
}
.pjd-cl-stat-num { font-size: 20px; font-weight: 600; color: var(--pjd-text-main); text-align: center; margin-bottom: 4px; line-height: 1; }
.is-active .pjd-cl-stat-num { color: var(--pjd-primary); }
.pjd-cl-stat-label { 
  font-size: 12px; color: var(--pjd-text-muted); text-align: center; 
  margin-bottom: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

.pjd-cl-stat-footer { display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: auto; }
.pjd-cl-stat-bar-bg { width: 32px; height: 4px; background: #f3f4f6; border-radius: 2px; overflow: hidden; }
.pjd-cl-stat-bar-fill { height: 100%; border-radius: 2px; }
.pjd-cl-stat-pct { font-size: 11px; font-weight: 500; color: var(--pjd-text-muted); }
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

.pjd-cl-top-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  justify-content: flex-start;
  flex-shrink: 0;
}
.pjd-cl-icon-btn {
  width: 38px; height: 38px; border: 1px solid var(--pjd-border); background: #fff;
  border-radius: 8px; display: flex; align-items: center; justify-content: center;
  color: var(--pjd-text-muted); cursor: pointer; padding: 0; margin: 0;
}
.pjd-cl-icon-btn:hover { background: var(--pjd-bg-hover); color: var(--pjd-text-main); }
.pjd-cl-icon-btn svg { width: 16px; height: 16px; }

/* =======================================
   TOOLBAR (REPRODUCIDO EXACTAMENTE)
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
   TABLA (ESTILO EXACTO)
   ======================================= */
.pjd-cl-table-container { 
  border: 1px solid var(--pjd-border); border-radius: 8px; overflow-x: auto; 
}
.pjd-cl-table { 
  width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; margin: 0; table-layout: auto;
}
.pjd-cl-table th {
  background: #ffffff; padding: 12px 16px; border-bottom: 1px solid var(--pjd-border);
  border-right: 1px solid var(--pjd-border); color: #374151; font-weight: 600; white-space: normal; /* Corregido scroll infinito */
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
.pjd-cl-detail-content { padding: 24px; background: #f9fafb; border-left: 4px solid var(--pjd-primary); }

/* =======================================
   MODAL DE AYUDA (CLASIFICACIONES)
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
</style>

<div class="pjd-cl-wrapper">
  
  <div class="pjd-cl-stats-container">
    <div class="pjd-cl-stats-row">
      <div class="pjd-cl-stat-card">
        <div class="pjd-cl-stat-num">0</div>
        <div class="pjd-cl-stat-label">Sin revisar</div>
        <div class="pjd-cl-stat-footer">
          <div class="pjd-cl-stat-bar-bg"><div class="pjd-cl-stat-bar-fill" style="width:0%;"></div></div>
          <span class="pjd-cl-stat-pct">0%</span>
        </div>
      </div>
      <div class="pjd-cl-stat-card stat-pendiente">
        <div class="pjd-cl-stat-num">31</div>
        <div class="pjd-cl-stat-label">Pendiente</div>
        <div class="pjd-cl-stat-footer">
          <div class="pjd-cl-stat-bar-bg"><div class="pjd-cl-stat-bar-fill" style="width:62%;"></div></div>
          <span class="pjd-cl-stat-pct">62%</span>
        </div>
      </div>
      <div class="pjd-cl-stat-card stat-revisar">
        <div class="pjd-cl-stat-num">1</div>
        <div class="pjd-cl-stat-label">Revisar</div>
        <div class="pjd-cl-stat-footer">
          <div class="pjd-cl-stat-bar-bg"><div class="pjd-cl-stat-bar-fill" style="width:2%;"></div></div>
          <span class="pjd-cl-stat-pct">2%</span>
        </div>
      </div>
      <div class="pjd-cl-stat-card stat-cumple">
        <div class="pjd-cl-stat-num">0</div>
        <div class="pjd-cl-stat-label">Cumple</div>
        <div class="pjd-cl-stat-footer">
          <div class="pjd-cl-stat-bar-bg"><div class="pjd-cl-stat-bar-fill" style="width:0%;"></div></div>
          <span class="pjd-cl-stat-pct">0%</span>
        </div>
      </div>
      <div class="pjd-cl-stat-card stat-nocumple">
        <div class="pjd-cl-stat-num">18</div>
        <div class="pjd-cl-stat-label">No cumple</div>
        <div class="pjd-cl-stat-footer">
          <div class="pjd-cl-stat-bar-bg"><div class="pjd-cl-stat-bar-fill" style="width:36%;"></div></div>
          <span class="pjd-cl-stat-pct">36%</span>
        </div>
      </div>
      <div class="pjd-cl-stat-card stat-total is-active">
        <div class="pjd-cl-stat-num">50</div>
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
      <button type="button" class="pjd-cl-icon-btn" aria-label="Expandir">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
      </button>
    </div>
  </div>

  <div class="pjd-cl-toolbar-row">
    <button type="button" class="pjd-cl-help-btn pjd-js-open-help" aria-label="Ayuda">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    </button>
    
    <div class="pjd-cl-search-container">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" placeholder="Buscar por requisito, formato o des...">
    </div>

    <button type="button" class="pjd-cl-btn is-blue">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
        <path d="M5 3v4"/><path d="M3 5h4"/>
      </svg>
      Reanalisis
    </button>
    
    <button type="button" class="pjd-cl-btn">
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
          <th style="width: 48px; text-align: center;"><input type="checkbox" class="pjd-cl-checkbox"></th>
          
          <th style="width: 48px; text-align: center;">
            <svg class="th-grip" style="margin: 0 auto; display: block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
          </th>
          
          <th>
            <div class="th-inner">
              <div class="th-left">
                <svg class="th-grip" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                Requisito 
              </div>
              <svg class="th-sort" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 15l5 5 5-5"/><path d="M7 9l5-5 5 5"/></svg>
            </div>
          </th>

          <th>
            <div class="th-inner">
              <div class="th-left">
                <svg class="th-grip" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                Formato
              </div>
              <svg class="th-sort" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 15l5 5 5-5"/><path d="M7 9l5-5 5 5"/></svg>
            </div>
          </th>

          <th>
            <div class="th-inner">
              <div class="th-left">
                <svg class="th-grip" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                Categoría
              </div>
            </div>
          </th>
        </tr>
      </thead>
      <tbody>
        @forelse($checklistData as $item)
          <tr>
            <td style="text-align: center;"><input type="checkbox" class="pjd-cl-checkbox"></td>
            <td></td> <td style="display: flex; align-items: center;">
              <button type="button" class="pjd-cl-chevron-btn pjd-js-toggle-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
              </button>
              {{ Str::limit($item['requisito'], 60) }}
            </td>
            <td class="pjd-text-gray" style="text-align: center;">{{ $item['formato'] === 'No aplica' ? '-' : $item['formato'] }}</td>
            <td class="pjd-text-gray">{{ Str::limit($item['categoria'], 25) }}</td>
          </tr>
          <tr class="pjd-cl-detail-row">
            <td colspan="5" style="padding:0;">
              <div class="pjd-cl-detail-content">
                <strong>Detalle Completo:</strong><br>
                Requisito: {{ $item['requisito'] }}<br>
                Aplicación: {{ $item['aplicabilidad'] }} | Obligatorio: {{ $item['obligatorio'] }}
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" style="padding: 40px 20px; text-align: center; color: var(--pjd-text-muted);">Sin registros.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
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

<script>
document.addEventListener("DOMContentLoaded", function() {
  
  // 1. Expandir Filas (Chevron)
  document.querySelectorAll('.pjd-js-toggle-row').forEach(btn => {
    btn.addEventListener('click', function() {
      const tr = this.closest('tr');
      const detailRow = tr.nextElementSibling;
      this.classList.toggle('open');
      if (detailRow && detailRow.classList.contains('pjd-cl-detail-row')) {
        detailRow.classList.toggle('open');
      }
    });
  });

  // 2. Dropdowns de la Toolbar
  document.querySelectorAll('.pjd-js-toggle-menu').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      document.querySelectorAll('.pjd-cl-dropdown-menu.show').forEach(menu => {
        if (menu !== this.nextElementSibling) menu.classList.remove('show');
      });
      const menu = this.nextElementSibling;
      if (menu && menu.classList.contains('pjd-cl-dropdown-menu')) {
        menu.classList.toggle('show');
      }
    });
  });

  // Evitar que clics dentro del menú lo cierren
  document.querySelectorAll('.pjd-cl-dropdown-menu').forEach(menu => {
    menu.addEventListener('click', function(e) { e.stopPropagation(); });
  });

  // Cerrar dropdowns al hacer clic fuera
  document.addEventListener('click', function() {
    document.querySelectorAll('.pjd-cl-dropdown-menu.show').forEach(menu => {
      menu.classList.remove('show');
    });
  });

  // 3. Modal de Ayuda
  const helpModal = document.querySelector('.pjd-js-help-modal');
  const btnOpenHelp = document.querySelector('.pjd-js-open-help');
  const btnCloseHelp = document.querySelector('.pjd-js-close-help');

  if (btnOpenHelp && helpModal) {
    btnOpenHelp.addEventListener('click', () => helpModal.classList.add('show'));
  }
  if (btnCloseHelp && helpModal) {
    btnCloseHelp.addEventListener('click', () => helpModal.classList.remove('show'));
  }
  if (helpModal) {
    helpModal.addEventListener('click', function(e) {
      if (e.target === this) {
        this.classList.remove('show');
      }
    });
  }

});
</script>