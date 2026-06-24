{{-- resources/views/projects/search.blade.php --}}
@extends('layouts.app')

@section('content_class', 'content--flush')
@section('title', 'Buscador de oportunidades')

@push('styles')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  :root {
    --bg: #f9fafb;
    --card: #ffffff;
    --ink: #374151;
    --ink-dark: #111827;
    --ink-muted: #6b7280;
    --line: #e5e7eb;
    --blue: #2563eb;
    --blue-hover: #1d4ed8;
    --blue-soft: #eff6ff;
    --success: #16a34a;
    --danger: #dc2626;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    --shadow-dropdown: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --ease: cubic-bezier(0.4, 0, 0.2, 1);
  }

  body {
    font-family: 'Quicksand', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: var(--bg);
    color: var(--ink);
  }

  /* La clase .pj-page (que viene del sidebar) manejará el ancho y el margin-left, 
     aquí solo definimos los paddings internos de la página del buscador */
  .jo-page {
    min-height: calc(100vh - 64px);
    padding: 24px 32px 48px;
    background: var(--bg);
  }

  .jo-shell {
    width: 100%;
    max-width: 1600px;
    margin: 0 auto;
  }

  .jo-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
  }

  /* --- FILTROS --- */
  .jo-filter-card {
    padding: 24px;
    margin-bottom: 24px;
  }

  .jo-filter-actions-top {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
  }

  .jo-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(220px, 1fr));
    gap: 20px 24px;
  }

  .jo-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 0;
    transition: opacity 0.2s;
  }

  .jo-label {
    color: var(--ink-muted);
    font-size: 0.85rem;
    font-weight: 600;
  }

  .jo-control {
    width: 100%;
    height: 42px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #ffffff;
    color: var(--ink-dark);
    font-family: inherit;
    font-size: 0.95rem;
    font-weight: 500;
    outline: none;
    padding: 0 14px;
    transition: border-color .15s var(--ease), box-shadow .15s var(--ease);
  }

  .jo-control::placeholder {
    color: #9ca3af;
    font-weight: 500;
  }

  .jo-control:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .jo-select {
    appearance: auto;
    cursor: pointer;
  }

  .jo-filter-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 24px;
  }

  .jo-filter-meta {
    color: var(--ink-muted);
    font-size: 0.85rem;
    font-weight: 600;
  }

  .jo-actions {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  /* --- BOTONES --- */
  .jo-btn {
    height: 38px;
    border-radius: 8px;
    border: 1px solid var(--line);
    background: #ffffff;
    color: var(--ink);
    font-family: inherit;
    font-size: 0.9rem;
    font-weight: 600;
    padding: 0 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    cursor: pointer;
    white-space: nowrap;
    transition: all .15s var(--ease);
  }

  .jo-btn:hover {
    background: var(--bg);
  }

  .jo-btn svg {
    width: 16px;
    height: 16px;
  }

  .jo-btn.is-primary {
    border-color: var(--blue);
    background: var(--blue);
    color: #ffffff;
    min-width: 120px;
  }

  .jo-btn.is-primary:hover {
    background: var(--blue-hover);
    border-color: var(--blue-hover);
  }

  .jo-btn.is-ghost-blue {
    border: none;
    background: transparent;
    color: var(--blue);
  }

  .jo-btn.is-ghost-blue:hover {
    background: var(--blue-soft);
  }

  .jo-btn.is-ask-monico {
    border-color: transparent;
    color: var(--blue);
    background: transparent;
  }
  
  .jo-btn.is-ask-monico:hover {
    background: var(--blue-soft);
  }

  /* --- DROPDOWN FILTROS --- */
  .jo-dropdown-wrapper {
    position: relative;
    display: inline-block;
  }

  .jo-btn.is-active-dropdown {
    border-color: var(--blue);
    color: var(--blue);
  }

  .jo-dropdown-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 240px;
    background: #ffffff;
    border: 1px solid var(--line);
    border-radius: 12px;
    box-shadow: var(--shadow-dropdown);
    padding: 16px;
    z-index: 50;
    display: none;
    flex-direction: column;
    gap: 12px;
  }

  .jo-dropdown-menu.is-open {
    display: flex;
  }

  .jo-dropdown-title {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--ink-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
  }

  /* Custom Checkbox */
  .jo-checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--ink-dark);
    user-select: none;
  }

  .jo-checkbox-label input[type="checkbox"] {
    display: none; /* Ocultar el nativo */
  }

  .jo-checkbox-custom {
    width: 18px;
    height: 18px;
    border-radius: 4px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
  }

  .jo-checkbox-label input[type="checkbox"]:checked + .jo-checkbox-custom {
    background: var(--blue);
    border-color: var(--blue);
  }

  .jo-checkbox-custom svg {
    width: 12px;
    height: 12px;
    stroke: #ffffff;
    stroke-width: 3;
    stroke-linecap: round;
    stroke-linejoin: round;
    fill: none;
    opacity: 0;
    transform: scale(0.5);
    transition: all 0.2s;
  }

  .jo-checkbox-label input[type="checkbox"]:checked + .jo-checkbox-custom svg {
    opacity: 1;
    transform: scale(1);
  }

  /* --- STATS --- */
  .jo-stats {
    display: grid;
    grid-template-columns: repeat(5, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
  }

  .jo-stat {
    padding: 20px 24px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .jo-stat-label {
    margin: 0 0 10px;
    color: var(--ink-muted);
    font-size: 0.85rem;
    font-weight: 600;
  }

  .jo-stat-value {
    margin: 0;
    color: var(--ink-dark);
    font-size: 1.85rem;
    line-height: 1;
    font-weight: 700;
  }

  /* --- MAIN LAYOUT --- */
  .jo-main-grid {
    display: grid;
    grid-template-columns: minmax(680px, 1fr) 380px;
    gap: 24px;
    align-items: start;
  }

  .jo-panel {
    overflow: hidden;
  }

  .jo-panel-head {
    min-height: 64px;
    padding: 0 24px;
    border-bottom: 1px solid var(--line);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .jo-panel-title {
    margin: 0;
    color: var(--ink-dark);
    font-size: 1rem;
    font-weight: 700;
  }

  /* --- TABLA --- */
  .jo-results-scroll {
    max-height: 600px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
  }

  .jo-table {
    width: 100%;
    border-collapse: collapse;
    background: #ffffff;
  }

  .jo-table th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #fbfbfc;
    color: var(--ink-muted);
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 16px 24px;
    border-bottom: 1px solid var(--line);
    white-space: nowrap;
    text-transform: capitalize;
  }

  .jo-table td {
    padding: 20px 24px;
    border-bottom: 1px solid var(--line);
    vertical-align: top;
    color: var(--ink);
    font-size: 0.85rem;
    font-weight: 600;
    line-height: 1.5;
  }

  .jo-table tr:hover td {
    background: #f8fafc;
  }

  .jo-proc-title {
    max-width: 380px;
    color: var(--ink-dark);
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 4px;
  }

  .jo-proc-code {
    color: #9ca3af;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 8px;
  }

  .jo-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--blue);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
  }

  .jo-status::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
  }

  .jo-dependency {
    min-width: 200px;
    max-width: 280px;
    color: var(--ink);
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
  }

  .jo-money {
    text-align: right;
    color: var(--ink-dark);
    font-weight: 700;
    white-space: nowrap;
  }

  .jo-sort {
    margin-left: 4px;
    color: #cbd5e1;
    font-size: 0.85rem;
    font-weight: 700;
  }

  /* --- PAGINACIÓN --- */
  .jo-table-foot {
    min-height: 64px;
    padding: 0 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-top: 1px solid var(--line);
    color: var(--ink-muted);
    font-size: 0.85rem;
    font-weight: 600;
    background: #ffffff;
  }

  .jo-pager {
    display: flex;
    align-items: center;
    gap: 16px;
  }

  .jo-page-btn {
    height: 32px;
    border-radius: 6px;
    border: 1px solid var(--line);
    background: #ffffff;
    color: var(--ink-dark);
    font-size: 0.85rem;
    font-weight: 600;
    padding: 0 12px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background .15s;
  }

  .jo-page-btn:hover { background: var(--bg); }
  .jo-page-btn.is-disabled { opacity: 0.5; pointer-events: none; }

  /* --- SIDEBAR DERECHO (TOP DEPENDENCIAS) --- */
  .jo-side-list {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-height: 600px;
    overflow-y: auto;
  }

  .jo-top-item {
    padding: 16px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
  }

  .jo-top-name {
    margin: 0 0 6px;
    color: var(--ink-dark);
    font-size: 0.85rem;
    line-height: 1.4;
    font-weight: 700;
    text-transform: uppercase;
  }

  .jo-top-meta {
    margin: 0;
    color: var(--ink-muted);
    font-size: 0.8rem;
    font-weight: 600;
  }

  /* --- NOTIFICACIONES --- */
  .jo-notifications {
    margin-top: 24px;
    display: flex;
    flex-direction: column;
  }

  .jo-note-head {
    padding: 20px 24px;
    border-bottom: 1px solid var(--line);
  }

  .jo-note-title {
    margin: 0 0 4px;
    color: var(--ink-dark);
    font-size: 0.95rem;
    font-weight: 700;
  }

  .jo-note-text {
    margin: 0 0 4px;
    color: var(--ink);
    font-size: 0.85rem;
    font-weight: 500;
  }

  .jo-note-sub {
    margin: 0;
    color: #9ca3af;
    font-size: 0.75rem;
    font-weight: 500;
  }

  .jo-opportunity {
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
  }

  /* --- BOTÓN FLOTANTE CHAT --- */
  .jo-chat-float {
    position: fixed;
    right: 32px;
    bottom: 32px;
    z-index: 80;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: 0;
    background: #0f172a;
    color: #ffffff;
    display: grid;
    place-items: center;
    box-shadow: 0 10px 25px rgba(15,23,42,.2);
    cursor: pointer;
    transition: transform .15s var(--ease);
  }

  .jo-chat-float:hover { transform: scale(1.05); }
  .jo-chat-float svg { width: 24px; height: 24px; }

  @media (max-width: 1200px) {
    .jo-stats { grid-template-columns: repeat(3, minmax(180px, 1fr)); }
    .jo-main-grid { grid-template-columns: 1fr; }
  }

  @media (max-width: 768px) {
    .jo-page { padding: 16px; }
    .jo-grid { grid-template-columns: 1fr; }
    .jo-stats { grid-template-columns: 1fr; }
    .jo-opportunity { flex-direction: column; align-items: flex-start; }
  }
</style>
@endpush

@section('content')

@include('projects.partials.control-sidebar')

<div class="pj-page jo-page">
  <div class="jo-shell">
    
    @php
      use Illuminate\Support\Str;
      use Illuminate\Support\Carbon;

      $sourceProcedures = $procedures ?? $opportunities ?? $results ?? collect();
      $isPaginator = is_object($sourceProcedures) && method_exists($sourceProcedures, 'total');
      $paginator = $isPaginator ? $sourceProcedures : null;
      $items = $paginator ? collect($paginator->items()) : collect($sourceProcedures);

      $totalProcedures = (int) data_get($stats ?? [], 'total_procedures', data_get($stats ?? [], 'total', $paginator ? $paginator->total() : $items->count()));
      $awardedProcedures = (int) data_get($stats ?? [], 'awarded_procedures', data_get($stats ?? [], 'awarded', $items->filter(fn($item) => Str::contains(Str::lower(data_get($item, 'status', data_get($item, 'estatus', ''))), 'adjudic'))->count()));
      $activeProcedures = (int) data_get($stats ?? [], 'active_procedures', data_get($stats ?? [], 'active', $items->filter(fn($item) => Str::contains(Str::lower(data_get($item, 'status', data_get($item, 'estatus', ''))), ['vigente', 'publicado', 'activo']))->count()));
      $totalAmount = (float) data_get($stats ?? [], 'total_amount', data_get($stats ?? [], 'amount', $items->sum(fn($item) => (float) data_get($item, 'amount', data_get($item, 'importe_asociado', 0)))));
      $distinctDependencies = (int) data_get($stats ?? [], 'distinct_dependencies', $items->pluck('dependency')->filter(fn($value) => filled($value) && $value !== '—')->unique()->count());

      // Opciones exactas para Estatus
      $statusOptions = [
          'ADJUDICADO',
          'ADJUDICADO PARCIAL',
          'CANCELADO',
          'DESIERTO',
          'EN ACLARACIONES',
          'EN APERTURA',
          'EN ATENCIÓN DE PREGUNTAS',
          'EN DECISIÓN DE FALLO',
          'EN EVALUACIÓN',
          'EN OSD',
          'EN REPREGUNTAS',
          'PENDIENTE DE APERTURA',
          'SUSPENDIDO',
          'VIGENTE'
      ];

      $dependencyOptions = collect($dependencyOptions ?? $dependencies ?? $items->pluck('dependency')->filter()->unique()->values())->filter()->values();
      $stateOptions = collect($stateOptions ?? $states ?? $items->pluck('state')->filter()->unique()->values())->filter()->values();
      $topDependenciesData = collect($topDependencies ?? $top_dependencies ?? []);

      if ($topDependenciesData->isEmpty()) {
          $topDependenciesData = $items
              ->filter(fn($item) => filled(data_get($item, 'dependency')) && data_get($item, 'dependency') !== '—')
              ->groupBy(fn($item) => data_get($item, 'dependency'))
              ->map(fn($rows, $name) => [
                  'name' => $name,
                  'count' => $rows->count(),
                  'amount' => $rows->sum(fn($item) => (float) data_get($item, 'amount', data_get($item, 'importe_asociado', 0))),
              ])
              ->sortByDesc('amount')
              ->take(8)
              ->values();
      }

      $fromValue = request('from', request('date_from', '')); 
      $toValue = request('to', request('date_to', '')); 
      
      $currentPage = $paginator ? $paginator->currentPage() : 1;
      $lastPage = $paginator ? $paginator->lastPage() : 1;
      $firstItem = $paginator ? ($paginator->firstItem() ?? 0) : ($items->count() ? 1 : 0);
      $lastItem = $paginator ? ($paginator->lastItem() ?? 0) : $items->count();
      $totalLabel = number_format($totalProcedures);

      $money = fn($value) => filled($value) ? '$' . number_format((float) $value, 2) : '—';
      $dateLabel = function ($value) {
          if (blank($value)) return '—';
          try { return Carbon::parse($value)->locale('es')->translatedFormat('d M Y'); }
          catch (Throwable $e) { return (string) $value; }
      };
    @endphp

    <form method="GET" action="{{ Route::has('projects.search') ? route('projects.search') : url()->current() }}" class="jo-card jo-filter-card">
      <div class="jo-filter-actions-top">
        
        <div class="jo-dropdown-wrapper">
          <button type="button" class="jo-btn" id="joFilterBtn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M7 12h10M10 17h4"/></svg>
            Filtros ▾
          </button>
          
          <div class="jo-dropdown-menu" id="joFilterMenu">
            <div class="jo-dropdown-title">FILTROS DISPONIBLES</div>
            
            <label class="jo-checkbox-label">
              <input type="checkbox" checked data-filter-toggle="busqueda">
              <div class="jo-checkbox-custom"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
              Búsqueda
            </label>
            
            <label class="jo-checkbox-label">
              <input type="checkbox" checked data-filter-toggle="estatus">
              <div class="jo-checkbox-custom"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
              Estatus
            </label>
            
            <label class="jo-checkbox-label">
              <input type="checkbox" checked data-filter-toggle="dependencia">
              <div class="jo-checkbox-custom"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
              Dependencia
            </label>

            <label class="jo-checkbox-label">
              <input type="checkbox" checked data-filter-toggle="entidad">
              <div class="jo-checkbox-custom"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
              Entidad federativa
            </label>

            <label class="jo-checkbox-label">
              <input type="checkbox" checked data-filter-toggle="fecha_inicial">
              <div class="jo-checkbox-custom"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
              Fecha inicial
            </label>

            <label class="jo-checkbox-label">
              <input type="checkbox" checked data-filter-toggle="fecha_final">
              <div class="jo-checkbox-custom"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
              Fecha final
            </label>
          </div>
        </div>

        <a href="{{ Route::has('projects.search') ? route('projects.search') : url()->current() }}" class="jo-btn is-ghost-blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
          Limpiar
        </a>
      </div>

      <div class="jo-grid">
        <label class="jo-field" data-filter-target="busqueda">
          <span class="jo-label">Búsqueda</span>
          <input class="jo-control" type="text" name="q" value="{{ request('q') }}" placeholder="Términos relacionados con la licitación">
        </label>

        <label class="jo-field" data-filter-target="estatus">
          <span class="jo-label">Estatus</span>
          <select class="jo-control jo-select" name="status">
            <option value="">Todos</option>
            @foreach($statusOptions as $option)
              <option value="{{ $option }}" @selected((string) request('status') === (string) $option)>{{ $option }}</option>
            @endforeach
          </select>
        </label>

        <label class="jo-field" data-filter-target="dependencia">
          <span class="jo-label">Dependencia</span>
          <select class="jo-control jo-select" name="dependency">
            <option value="">Todas</option>
            @foreach($dependencyOptions as $option)
              @php $optionValue = is_array($option) ? ($option['value'] ?? $option['label'] ?? '') : $option; @endphp
              <option value="{{ $optionValue }}" @selected((string) request('dependency') === (string) $optionValue)>{{ is_array($option) ? ($option['label'] ?? $optionValue) : $optionValue }}</option>
            @endforeach
          </select>
        </label>

        <label class="jo-field" data-filter-target="entidad">
          <span class="jo-label">Entidad federativa</span>
          <select class="jo-control jo-select" name="state">
            <option value="">Todas</option>
            @foreach($stateOptions as $option)
              @php $optionValue = is_array($option) ? ($option['value'] ?? $option['label'] ?? '') : $option; @endphp
              <option value="{{ $optionValue }}" @selected((string) request('state') === (string) $optionValue)>{{ is_array($option) ? ($option['label'] ?? $optionValue) : $optionValue }}</option>
            @endforeach
          </select>
        </label>

        <label class="jo-field" data-filter-target="fecha_inicial">
          <span class="jo-label">Fecha inicial</span>
          <input class="jo-control" type="text" name="from" value="{{ $fromValue }}" placeholder="dd/mm/aaaa">
        </label>

        <label class="jo-field" data-filter-target="fecha_final">
          <span class="jo-label">Fecha final</span>
          <input class="jo-control" type="text" name="to" value="{{ $toValue }}" placeholder="dd/mm/aaaa">
        </label>
      </div>

      <div class="jo-filter-foot">
        <div class="jo-filter-meta">{{ number_format(collect($statusOptions)->filter()->count()) }} estatus disponibles</div>
        <div class="jo-actions">
          <button type="button" class="jo-btn is-ask-monico" id="joAskMonico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15Z"/></svg>
            ask monico
          </button>
          <button type="submit" class="jo-btn is-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            Buscar
          </button>
        </div>
      </div>
    </form>

    <div class="jo-stats">
      <div class="jo-card jo-stat"><p class="jo-stat-label">Total de procedimientos</p><p class="jo-stat-value">{{ number_format($totalProcedures) }}</p></div>
      <div class="jo-card jo-stat"><p class="jo-stat-label">Procedimientos adjudicados</p><p class="jo-stat-value">{{ number_format($awardedProcedures) }}</p></div>
      <div class="jo-card jo-stat"><p class="jo-stat-label">Procedimientos vigentes</p><p class="jo-stat-value">{{ number_format($activeProcedures) }}</p></div>
      <div class="jo-card jo-stat"><p class="jo-stat-label">Importe total asociado</p><p class="jo-stat-value">${{ number_format($totalAmount, 0) }}</p></div>
      <div class="jo-card jo-stat"><p class="jo-stat-label">Dependencias distintas</p><p class="jo-stat-value">{{ number_format($distinctDependencies) }}</p></div>
    </div>

    <div class="jo-main-grid">
      <section class="jo-card jo-panel">
        <div class="jo-panel-head">
          <h2 class="jo-panel-title">Resultados ({{ number_format($totalProcedures) }})</h2>
          <button type="button" class="jo-btn">Columnas (4) ▾</button>
        </div>

        <div class="jo-results-scroll">
          <table class="jo-table">
            <thead>
              <tr>
                <th>Procedimiento <span class="jo-sort">↑↓</span></th>
                <th>Dependencia <span class="jo-sort">↑↓</span></th>
                <th>Entidad<br>Federativa <span class="jo-sort">↑↓</span></th>
                <th>Fecha<br>Publicación <span class="jo-sort">↑↓</span></th>
                <th style="text-align:right;">Importe<br>asociado <span class="jo-sort">↑↓</span></th>
              </tr>
            </thead>
            <tbody>
              @forelse($items as $item)
                @php
                  $title = data_get($item, 'title', data_get($item, 'name', data_get($item, 'procedure', 'Sin título')));
                  $code = data_get($item, 'code', data_get($item, 'procedure_number', data_get($item, 'folio', '')));
                  $status = Str::upper(data_get($item, 'status', data_get($item, 'estatus', 'Sin estatus')));
                  $dependency = data_get($item, 'dependency', data_get($item, 'dependencia', '—'));
                  $state = Str::upper(data_get($item, 'state', data_get($item, 'entidad_federativa', '—')));
                  $published = data_get($item, 'published_at', data_get($item, 'fecha_publicacion', data_get($item, 'publication_date', null)));
                  $amount = data_get($item, 'amount', data_get($item, 'importe_asociado', null));
                @endphp
                <tr>
                  <td>
                    <div class="jo-proc-title">{{ $title }}</div>
                    @if($code)<div class="jo-proc-code">{{ $code }}</div>@endif
                    <span class="jo-status">{{ $status }}</span>
                  </td>
                  <td><div class="jo-dependency">{{ $dependency }}</div></td>
                  <td>{{ $state }}</td>
                  <td>{{ $dateLabel($published) }}</td>
                  <td class="jo-money">{{ $money($amount) }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" style="padding:34px 24px;color:var(--ink-muted);font-weight:700;text-align:center;">
                    No se encontraron procedimientos con los filtros actuales.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="jo-table-foot">
          <span>Mostrando {{ number_format($firstItem) }}–{{ number_format($lastItem) }} de {{ $totalLabel }} licitaciones</span>
          <div class="jo-pager">
            @if($paginator && $paginator->previousPageUrl())
              <a class="jo-page-btn" href="{{ $paginator->previousPageUrl() }}">Anterior</a>
            @else
              <span class="jo-page-btn is-disabled">Anterior</span>
            @endif
            <span>Página {{ number_format($currentPage) }} de {{ number_format($lastPage) }}</span>
            @if($paginator && $paginator->nextPageUrl())
              <a class="jo-page-btn" href="{{ $paginator->nextPageUrl() }}">Siguiente</a>
            @else
              <span class="jo-page-btn is-disabled">Siguiente</span>
            @endif
          </div>
        </div>
      </section>

      <aside class="jo-card jo-panel">
        <div class="jo-panel-head">
          <h2 class="jo-panel-title">Top dependencias por monto</h2>
        </div>
        <div class="jo-side-list">
          @forelse($topDependenciesData as $dep)
            @php
              $depName = data_get($dep, 'name', data_get($dep, 'dependency', 'Dependencia'));
              $depCount = (int) data_get($dep, 'procedures_count', data_get($dep, 'count', 0));
              $depAmount = (float) data_get($dep, 'amount', data_get($dep, 'total_amount', 0));
            @endphp
            <div class="jo-top-item">
              <p class="jo-top-name">{{ $depName }}</p>
              <p class="jo-top-meta">{{ number_format($depCount) }} procedimientos | ${{ number_format($depAmount, 0) }}</p>
            </div>
          @empty
            <div class="jo-top-item">
              <p class="jo-top-name">Sin dependencias disponibles</p>
              <p class="jo-top-meta">No hay registros reales para mostrar todavía.</p>
            </div>
          @endforelse
        </div>
      </aside>
    </div>

    <section class="jo-card jo-notifications">
      <div class="jo-note-head">
        <p class="jo-note-title">Centro de notificaciones</p>
        <p class="jo-note-text">Te avisamos cuando detectemos cambios en licitaciones que incluyan tus palabras clave.</p>
        <p class="jo-note-sub">Este panel se actualiza automáticamente cada minuto.</p>
      </div>
      <div class="jo-opportunity">
        <div>
          <p class="jo-note-title">Búsqueda de oportunidades</p>
          <p class="jo-note-text">Activa un seguimiento inteligente para recibir licitaciones que coincidan con tus palabras clave.</p>
        </div>
        <button type="button" class="jo-btn is-primary">Configurar palabras clave</button>
      </div>
    </section>
  </div>
</div>

<button type="button" class="jo-chat-float" aria-label="Abrir chat">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
  </svg>
</button>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    
    // --- LÓGICA DEL DROPDOWN DE FILTROS ---
    const filterBtn = document.getElementById('joFilterBtn');
    const filterMenu = document.getElementById('joFilterMenu');
    const checkboxes = document.querySelectorAll('[data-filter-toggle]');

    // Abrir/Cerrar Dropdown
    filterBtn.addEventListener('click', (e) => {
      e.stopPropagation(); // Evitar que el click cierre inmediatamente
      filterMenu.classList.toggle('is-open');
      filterBtn.classList.toggle('is-active-dropdown');
    });

    // Cerrar al dar click fuera
    document.addEventListener('click', (e) => {
      if (!filterBtn.contains(e.target) && !filterMenu.contains(e.target)) {
        filterMenu.classList.remove('is-open');
        filterBtn.classList.remove('is-active-dropdown');
      }
    });

    // Funcionalidad de Checkboxes para mostrar/ocultar campos
    checkboxes.forEach(checkbox => {
      checkbox.addEventListener('change', (e) => {
        const targetName = e.target.getAttribute('data-filter-toggle');
        const fieldBlock = document.querySelector(`[data-filter-target="${targetName}"]`);
        
        if(fieldBlock) {
          if(e.target.checked) {
            fieldBlock.style.display = 'flex';
          } else {
            fieldBlock.style.display = 'none';
          }
        }
      });
    });

  });
</script>
@endpush

@endsection