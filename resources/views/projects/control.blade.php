@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

  :root {
    --bg: #f8f9fc;
    --card: #ffffff;
    --ink: #111827;
    --muted: #6b7280;
    --line: #e5e7eb;
    --blue: #1a73e8;
    --blue-soft: #e8f0fe;
    --success: #10b981;
    --warning: #d97706;
    --danger: #dc2626;
    --purple: #8b5cf6;
    --gray-dark: #4b5563;
  }

  /* --- Base --- */
  .cc-page {
    min-height: 100vh;
    background: var(--bg);
    color: var(--ink);
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    padding: 32px;
  }

  .cc-icon { width: 18px; height: 18px; }
  .cc-icon-sm { width: 16px; height: 16px; }

  /* --- Header --- */
  .cc-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24px;
  }

  .cc-title-row {
    display: flex;
    align-items: baseline;
    gap: 16px;
  }

  .cc-title {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
    letter-spacing: -0.02em;
  }

  .cc-time {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--muted);
    font-size: 0.875rem;
    text-transform: none;
  }

  .cc-subtitle {
    margin: 4px 0 0;
    color: var(--muted);
    font-size: 0.875rem;
  }

  .cc-btn {
    height: 38px;
    padding: 0 14px;
    border: 1px solid var(--line);
    border-radius: 6px;
    background: var(--card);
    color: #374151;
    font-family: inherit;
    font-size: 0.875rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    transition: background 0.15s ease;
    text-decoration: none !important;
  }

  .cc-btn:hover { background: #f9fafb; }

  /* --- Paneles y Tarjetas Base --- */
  .cc-panel, .cc-card, .cc-chart, .cc-mini-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 8px;
  }

  /* --- Filtros --- */
  .cc-panel { margin-bottom: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
  
  .cc-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid var(--line);
  }

  .cc-panel-title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .cc-filter-grid {
    display: flex;
    align-items: center;
    padding: 16px 20px;
    gap: 20px;
  }

  .cc-filter-divider { width: 1px; height: 24px; background-color: var(--line); }

  .cc-field { display: flex; align-items: center; gap: 12px; }

  .cc-label {
    color: #4b5563;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .cc-select {
    height: 34px;
    border: 1px solid var(--line);
    border-radius: 6px;
    background: #fff;
    padding: 0 32px 0 12px;
    font-family: inherit;
    font-size: 0.875rem;
    outline: none;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    background-size: 14px;
  }

  .cc-select:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 2px var(--blue-soft);
  }

  .cc-labels { display: flex; align-items: center; gap: 8px; }

  .cc-tag {
    display: inline-flex;
    align-items: center;
    height: 26px;
    padding: 0 10px;
    border: 1px solid var(--line);
    border-radius: 999px;
    color: #374151;
    font-size: 0.75rem;
    font-weight: 600;
    text-decoration: none;
  }

  .cc-tag:hover, .cc-tag.is-active { background: #f3f4f6; }
  .cc-tag.is-muted { color: var(--muted); background: #fff; border-style: dashed; pointer-events: none; }

  /* --- Tarjetas Pequeñas (Top) --- */
  .cc-total {
    margin: 0 0 20px;
    font-size: 1.25rem;
    font-weight: 600;
  }

  .cc-total strong { color: var(--blue); }

  .cc-stage-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 24px;
  }

  .cc-card {
    padding: 20px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
  }

  .cc-card-info { display: flex; flex-direction: column; gap: 8px; }
  .cc-card-label { color: var(--muted); font-size: 0.81rem; font-weight: 500; }
  .cc-card-value { font-size: 1.75rem; font-weight: 700; line-height: 1; }
  
  .cc-card-icon {
    width: 40px; height: 40px;
    border-radius: 8px;
    display: grid; place-items: center;
  }
  .cc-card-icon svg { width: 20px; height: 20px; }

  .bg-blue { background: #eef2ff; color: var(--blue); }
  .bg-yellow { background: #fef3c7; color: var(--warning); }
  .bg-cyan { background: #e0f2fe; color: #0284c7; }
  .bg-red { background: #fee2e2; color: var(--danger); }
  .bg-purple { background: #f3e8ff; color: var(--purple); }
  .bg-gray { background: #f3f4f6; color: var(--gray-dark); }
  .bg-darkred { background: #fce7f3; color: #be185d; }

  /* --- Gráficos --- */
  .cc-main-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 32px;
  }

  .cc-chart { padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
  .cc-chart-title { margin: 0 0 20px; font-size: 1.4rem; font-weight: 700; display: flex; align-items: center; gap: 8px; letter-spacing: -0.01em;}
  .cc-canvas-wrap { height: 320px; position: relative; width: 100%; }

  .cc-chart-legend {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 14px 20px;
    margin-top: 14px;
    padding: 0 8px;
  }

  .cc-legend-item {
    display: inline-flex;
    align-items: center;
    font-size: 0.85rem;
    font-weight: 500;
    white-space: nowrap;
  }

  .cc-chart-tooltip {
    position: absolute;
    z-index: 20;
    left: 0;
    top: 0;
    min-width: 150px;
    max-width: 240px;
    padding: 9px 10px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: rgba(255,255,255,.98);
    color: var(--ink);
    box-shadow: 0 12px 28px rgba(15,23,42,.12);
    opacity: 0;
    pointer-events: none;
    transform: translate(-50%, -115%);
    transition-property: opacity, transform;
    transition-duration: 140ms;
    transition-timing-function: cubic-bezier(0.23, 1, 0.32, 1);
  }

  .cc-chart-tooltip.is-visible {
    opacity: 1;
    transform: translate(-50%, -125%);
  }

  .cc-tip-title {
    color: var(--ink);
    font-size: 0.8rem;
    font-weight: 700;
    margin-bottom: 3px;
  }

  .cc-tip-meta {
    color: var(--muted);
    font-size: 0.72rem;
    font-weight: 600;
  }

  /* --- Tarjetas de Progreso Detalladas --- */
  .cc-progress-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 40px;
  }

  .cc-progress-card {
    padding: 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
  }

  .cc-progress-icon-wrap { margin-bottom: 12px; }
  .cc-progress-icon-wrap svg { width: 28px; height: 28px; }
  .cc-progress-title { margin: 0 0 12px; font-size: 1rem; font-weight: 600; }
  .cc-progress-number { margin: 0 0 4px; font-size: 2.2rem; font-weight: 700; line-height: 1; }
  .cc-progress-sub { margin: 0 0 16px; font-size: 0.8rem; color: var(--muted); }
  .cc-progress-track { width: 100%; height: 10px; background: #f3f4f6; border-radius: 99px; overflow: hidden; }
  .cc-progress-bar { height: 100%; border-radius: 99px; }

  /* --- Accesos Directos --- */
  .cc-shortcuts-title { margin: 0 0 4px; font-size: 1.5rem; font-weight: 700; }
  .cc-shortcuts-subtitle { margin: 0 0 24px; font-size: 0.875rem; color: var(--muted); }

  .cc-shortcuts-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
  .cc-shortcut-head { display: flex; align-items: center; gap: 8px; font-size: 1.1rem; font-weight: 600; margin-bottom: 16px; }

  .cc-shortcut-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-height: 450px;
    overflow-y: auto;
    padding-right: 8px;
  }

  .cc-shortcut-list::-webkit-scrollbar { width: 6px; }
  .cc-shortcut-list::-webkit-scrollbar-track { background: transparent; }
  .cc-shortcut-list::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }

  .cc-mini-card {
    padding: 16px;
    display: flex;
    gap: 16px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    transition: transform 0.15s ease;
    text-decoration: none;
    color: inherit;
  }
  
  .cc-mini-card:hover { transform: translateY(-2px); }

  .cc-icon-box {
    width: 40px; height: 40px; border-radius: 8px; background: #eef2ff; color: var(--blue);
    display: grid; place-items: center; flex-shrink: 0;
  }
  .cc-mini-title { font-size: 0.95rem; font-weight: 600; margin: 0 0 6px; display: flex; justify-content: space-between;}
  .cc-mini-meta { font-size: 0.8rem; color: var(--muted); display: flex; align-items: center; gap: 4px; margin-bottom: 10px;}
  .cc-badge-dark { background: var(--gray-dark); color: #fff; padding: 2px 10px; border-radius: 99px; font-size: 0.75rem; font-weight: 500; }
  
  .cc-date-box { text-align: center; width: 44px; flex-shrink: 0; }
  .cc-date-day { font-size: 1.5rem; font-weight: 700; color: var(--blue); line-height: 1; }
  .cc-date-month { font-size: 0.75rem; font-weight: 600; color: var(--muted); text-transform: uppercase; margin-top: 4px; }
  .cc-badge-outline { border: 1px solid var(--line); color: var(--ink); padding: 2px 8px; border-radius: 99px; font-size: 0.75rem; font-weight: 600; background: #fff; }

  .cc-avatar {
    width: 40px; height: 40px; border-radius: 50%; background: #eef2ff; color: var(--blue);
    display: grid; place-items: center; font-weight: 600; font-size: 0.9rem; flex-shrink: 0;
  }
  .cc-note-body { font-size: 0.875rem; color: #374151; margin: 8px 0 12px; line-height: 1.5; }
  .cc-link { color: var(--blue); text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 4px; font-size: 0.8rem;}
  .cc-link:hover { text-decoration: underline; }

  @media (max-width: 1200px) {
    .cc-stage-grid { grid-template-columns: repeat(4, 1fr); }
    .cc-progress-grid { grid-template-columns: repeat(2, 1fr); }
    .cc-shortcuts-grid { grid-template-columns: 1fr; }
  }
  @media (max-width: 900px) {
    .cc-filter-grid { flex-direction: column; align-items: flex-start; }
    .cc-filter-divider { display: none; }
    .cc-stage-grid { grid-template-columns: repeat(2, 1fr); }
    .cc-main-grid { grid-template-columns: 1fr; }
    .cc-progress-grid { grid-template-columns: 1fr; }
  }
</style>
@endpush

@section('content')
<div class="cc-page">
    <header class="cc-header">
        <div>
            <div class="cc-title-row">
                <h1 class="cc-title">Dashboard</h1>
                <span class="cc-time">
                    <svg class="cc-icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    {{ $currentDateLabel ?? ucfirst(now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY, h:mm a')) }}
                </span>
            </div>
            <p class="cc-subtitle">Análisis en tiempo real de proyectos de licitación</p>
        </div>

        <a class="cc-btn" href="{{ route('projects.control.pdf', request()->query()) }}">
            <svg class="cc-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Descargar PDF
        </a>
    </header>

    <form class="cc-panel" method="GET" action="{{ route('projects.control') }}">
        <div class="cc-panel-head">
            <h2 class="cc-panel-title">
                <svg class="cc-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                Periodo y etiquetas
            </h2>
            <svg class="cc-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
        </div>

        <div class="cc-filter-grid">
            <label class="cc-field">
                <span class="cc-label">
                    <svg class="cc-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    Período:
                </span>
                <select name="period" class="cc-select" onchange="this.form.submit()">
                    <option value="all" @selected($period === 'all')>Todos</option>
                    <option value="last_month" @selected(in_array($period, ['last_month', '30'], true))>Último mes</option>
                    <option value="previous_month" @selected($period === 'previous_month')>Mes anterior</option>
                    <option value="bimestre" @selected(in_array($period, ['bimestre', '60'], true))>Bimestre</option>
                    <option value="trimestre" @selected(in_array($period, ['trimestre', '90'], true))>Trimestre</option>
                    <option value="semestre" @selected($period === 'semestre')>Semestre</option>
                    <option value="year" @selected($period === 'year')>Año</option>
                </select>
            </label>

            <div class="cc-filter-divider"></div>

            <label class="cc-field">
                <span class="cc-label">
                    <svg class="cc-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    Asignado
                </span>
                <select name="assignee" class="cc-select" onchange="this.form.submit()">
                    <option value="all" @selected($assigneeId === 'all')>Todos</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string) $assigneeId === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </label>

            <div class="cc-filter-divider"></div>

            <div class="cc-field">
                <span class="cc-label">
                    <svg class="cc-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                    Etiquetas:
                </span>
                <div class="cc-labels">
                    @if($label !== '')
                        <a class="cc-tag" href="{{ route('projects.control', array_filter(['period' => $period, 'assignee' => $assigneeId])) }}">
                            Todas
                        </a>
                    @endif

                    @forelse($labels as $item)
                        <a class="cc-tag {{ $label === $item['name'] ? 'is-active' : '' }}" href="{{ route('projects.control', array_filter(['period' => $period, 'assignee' => $assigneeId, 'label' => $item['name']])) }}">
                            {{ $item['name'] }} <span>{{ $item['count'] ?? 0 }}</span>
                        </a>
                    @empty
                        <span class="cc-tag is-muted">Sin etiquetas creadas</span>
                    @endforelse
                </div>
            </div>
        </div>
    </form>

    <h2 class="cc-total">Total de Proyectos: <strong>{{ $totalProjects ?? 26 }}</strong></h2>

    <section class="cc-stage-grid">
        @foreach($stageCards as $index => $card)
            @php
                $bgClass = match($index) {
                    0 => 'bg-blue', 1 => 'bg-yellow', 2 => 'bg-cyan', 3 => 'bg-red',
                    4 => 'bg-purple', 5 => 'bg-gray', 6 => 'bg-darkred', default => 'bg-blue',
                };
                $svgIcon = match($index) {
                    0 => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>',
                    1 => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>',
                    2 => '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline>',
                    3 => '<polyline points="22 17 13.5 8.5 8.5 13.5 2 7"></polyline><polyline points="16 17 22 17 22 11"></polyline>',
                    4 => '<path d="M8 21h8m-4-4v4m-7-4h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z"></path>',
                    5 => '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>',
                    6 => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="9" y1="15" x2="15" y2="9"></line><line x1="9" y1="9" x2="15" y2="15"></line>',
                    default => '<circle cx="12" cy="12" r="10"></circle>',
                };
            @endphp
            <article class="cc-card">
                <div class="cc-card-info">
                    <div class="cc-card-label">{{ $card['name'] }}</div>
                    <div class="cc-card-value">{{ $card['count'] }}</div>
                </div>
                <div class="cc-card-icon {{ $bgClass }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $svgIcon !!}</svg>
                </div>
            </article>
        @endforeach
    </section>

    <section class="cc-main-grid">
        <article class="cc-chart">
            <h3 class="cc-chart-title">
                <svg class="cc-icon" style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                Evolución por Etapas
            </h3>
            <div class="cc-canvas-wrap"><canvas id="ccEvolutionChart"></canvas><div class="cc-chart-tooltip" id="ccEvolutionTooltip"></div></div>
            <div class="cc-chart-legend" id="ccEvolutionLegend" aria-label="Leyenda de evolución"></div>
        </article>

        <article class="cc-chart">
            <h3 class="cc-chart-title">
                <svg class="cc-icon" style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="18" y="3" width="4" height="18"></rect><rect x="10" y="8" width="4" height="13"></rect><rect x="2" y="13" width="4" height="8"></rect></svg>
                Distribución Actual
            </h3>
            <div class="cc-canvas-wrap"><canvas id="ccDistributionChart"></canvas><div class="cc-chart-tooltip" id="ccDistributionTooltip"></div></div>
        </article>
    </section>

    <section class="cc-progress-grid">
        @foreach($stageCards as $index => $card)
            @php
                $colorHex = match($index) {
                    0 => '#2563eb', 1 => '#f59e0b', 2 => '#10b981', 3 => '#ef4444',
                    4 => '#8b5cf6', 5 => '#6b7280', 6 => '#991b1b', default => '#2563eb',
                };
                $svgIcon = match($index) {
                    0 => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>',
                    1 => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>',
                    2 => '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline>',
                    3 => '<polyline points="22 17 13.5 8.5 8.5 13.5 2 7"></polyline><polyline points="16 17 22 17 22 11"></polyline>',
                    4 => '<path d="M8 21h8m-4-4v4m-7-4h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z"></path>',
                    5 => '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>',
                    6 => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="9" y1="15" x2="15" y2="9"></line><line x1="9" y1="9" x2="15" y2="15"></line>',
                    default => '<circle cx="12" cy="12" r="10"></circle>',
                };
            @endphp
            <article class="cc-progress-card cc-card">
                <div class="cc-progress-icon-wrap" style="color: {{ $colorHex }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $svgIcon !!}</svg>
                </div>
                <h3 class="cc-progress-title">{{ $card['name'] }}</h3>
                <p class="cc-progress-number" style="color: {{ $colorHex }}">{{ $card['count'] }}</p>
                <p class="cc-progress-sub">{{ $card['percentage'] ?? rand(0, 100) }}% del total</p>
                
                <div class="cc-progress-track">
                    <div class="cc-progress-bar" style="width: {{ $card['percentage'] ?? rand(10, 90) }}%; background-color: {{ $colorHex }}"></div>
                </div>
            </article>
        @endforeach
    </section>

    <section>
        <h2 class="cc-shortcuts-title">Accesos Directos</h2>
        <p class="cc-shortcuts-subtitle">Vista rápida de proyectos, eventos y notas</p>

        <div class="cc-shortcuts-grid">
            <div>
                <div class="cc-shortcut-head">
                    <svg class="cc-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                    Proyectos Recientes
                </div>
                <div class="cc-shortcut-list">
                    @forelse($recentProjects ?? [] as $project)
                        <a href="#" class="cc-mini-card">
                            <div class="cc-icon-box">
                                <svg class="cc-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                            </div>
                            <div style="flex: 1;">
                                <h4 class="cc-mini-title">
                                    {{ $project['name'] ?? 'Proyecto sin nombre' }}
                                    @if($project['favorite'] ?? false)
                                        <svg class="cc-icon-sm" style="color:var(--muted)" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                    @endif
                                </h4>
                                <div class="cc-mini-meta">
                                    <svg class="cc-icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                    {{ $project['assignee'] ?? 'Sin asignar' }} &bull; {{ $project['date'] ?? 'Sin fecha' }}
                                </div>
                                <div style="font-size: 0.8rem; color: var(--muted); display:flex; align-items:center; gap:6px;">
                                    Prioridad: <span class="cc-badge-dark">{{ $project['priority'] ?? 'Normal' }}</span>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div style="padding: 20px; text-align:center; color: var(--muted);">Sin proyectos</div>
                    @endforelse
                </div>
            </div>

            <div>
                <div class="cc-shortcut-head">
                    <svg class="cc-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    Eventos Recientes
                </div>
                <div class="cc-shortcut-list">
                    @forelse($upcomingEvents ?? [] as $event)
                        <div class="cc-mini-card">
                            <div class="cc-date-box">
                                <div class="cc-date-day">{{ $event['day'] ?? '—' }}</div>
                                <div class="cc-date-month">{{ $event['month'] ?? '' }}</div>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 0.95rem;">{{ $event['title'] ?? 'Evento' }}</div>
                                <div style="font-size: 0.8rem; color: var(--muted); margin: 4px 0 8px;">{{ $event['project'] ?? 'Sin proyecto' }}</div>
                                <div style="display:flex; align-items:center; gap:8px; font-size: 0.75rem; color: var(--muted);">
                                    @if(!empty($event['due']))
                                        <span class="cc-badge-outline">Próximo</span> {{ $event['due'] }}
                                    @else
                                        <span class="cc-badge-outline" style="color: var(--muted); border-color: var(--line);">Sin fecha</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div style="padding: 20px; text-align:center; color: var(--muted);">Sin eventos</div>
                    @endforelse
                </div>
            </div>

            <div>
                <div class="cc-shortcut-head">
                    <svg class="cc-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                    Notas Recientes
                </div>
                <div class="cc-shortcut-list">
                    @forelse($notes ?? [] as $note)
                        <div class="cc-mini-card" style="align-items: flex-start;">
                            <div class="cc-avatar">{{ $note['initials'] ?? 'N' }}</div>
                            <div style="flex: 1;">
                                <div style="display:flex; justify-content:space-between; align-items:baseline;">
                                    <span style="font-weight: 700; font-size: 0.85rem;">{{ $note['author'] ?? 'Usuario' }}</span>
                                    <span style="font-size: 0.75rem; color: var(--muted);">{{ $note['time'] ?? '' }}</span>
                                </div>
                                <p class="cc-note-body">{!! str_replace('@', '<span style="color:var(--blue); font-weight:600;">@</span>', $note['body'] ?? '') !!}</p>
                                <a href="#" class="cc-link">
                                    <svg class="cc-icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                    {{ $note['project'] ?? 'Sin proyecto' }}
                                </a>
                            </div>
                        </div>
                    @empty
                        <div style="padding: 20px; text-align:center; color: var(--muted);">Sin notas</div>
                    @endforelse
                </div>
            </div>

        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const colorMap = {
        blue: '#2563eb',
        orange: '#f59e0b',
        green: '#10b981',
        red: '#ef4444',
        purple: '#8b5cf6',
        gray: '#6b7280',
        darkred: '#991b1b',
    };

    const labels = @json($distributionLabels ?? []);
    const values = @json($distributionValues ?? []);
    const monthLabels = @json($monthLabels ?? []);
    const series = @json($evolutionSeries ?? []);

    function colorFor(value, fallback = '#2563eb') {
        return colorMap[value] || value || fallback;
    }

    function hexToRgba(hex, alpha) {
        let c;
        if(/^#([A-Fa-f0-9]{3}){1,2}$/.test(hex)){
            c= hex.substring(1).split('');
            if(c.length== 3){
                c= [c[0], c[0], c[1], c[1], c[2], c[2]];
            }
            c= '0x'+c.join('');
            return 'rgba('+[(c>>16)&255, (c>>8)&255, c&255].join(',')+','+alpha+')';
        }
        return `rgba(0,0,0,${alpha})`;
    }

    function setupCanvas(canvas) {
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.parentElement.getBoundingClientRect();
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        canvas.style.width = rect.width + 'px';
        canvas.style.height = rect.height + 'px';
        const ctx = canvas.getContext('2d');
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        return { ctx, width: rect.width, height: rect.height };
    }

    function drawGrid(ctx, width, height, padding, max) {
        ctx.clearRect(0, 0, width, height);
        ctx.strokeStyle = '#e5e7eb';
        ctx.lineWidth = 1;
        ctx.fillStyle = '#6b7280';
        ctx.font = '12px Inter, sans-serif';
        ctx.textAlign = 'right';
        ctx.textBaseline = 'middle';

        let steps = 5;
        if (max <= 5) steps = 10;
        else if (max <= 10) steps = max;

        for (let i = 0; i <= steps; i++) {
            const y = padding.top + ((height - padding.top - padding.bottom) / steps) * i;
            const val = max - (max / steps) * i;
            
            const valueText = (max <= 5) ? val.toFixed(1).replace('.', ',') : Math.round(val).toString();
            
            ctx.beginPath();
            ctx.moveTo(padding.left, y);
            ctx.lineTo(width - padding.right, y);
            ctx.stroke();
            ctx.fillText(valueText, padding.left - 10, y);
        }
    }

    function renderTextLegend(containerId, items) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = items.map(item => `
            <span class="cc-legend-item" style="color:${item.color};">
                ${escapeHtml(item.label)}
            </span>
        `).join('');
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[char]));
    }

    function showTooltip(canvas, tooltip, x, y, html) {
        if (!tooltip) return;
        tooltip.innerHTML = html;
        const rect = canvas.parentElement.getBoundingClientRect();
        const left = Math.max(80, Math.min(x, rect.width - 80));
        const top = Math.max(48, y);
        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
        tooltip.classList.add('is-visible');
    }

    function hideTooltip(tooltip) {
        tooltip?.classList.remove('is-visible');
    }

    function pointerPosition(event, canvas) {
        const rect = canvas.getBoundingClientRect();
        return { x: event.clientX - rect.left, y: event.clientY - rect.top };
    }

    function bindEvolutionTooltip(canvas) {
        const tooltip = document.getElementById('ccEvolutionTooltip');
        canvas.addEventListener('mousemove', event => {
            const p = pointerPosition(event, canvas);
            const points = canvas.__points || [];
            let nearest = null; let distance = Infinity;

            points.forEach(point => {
                const d = Math.hypot(point.x - p.x, point.y - p.y);
                if (d < distance) { distance = d; nearest = point; }
            });

            if (!nearest || distance > 18) { hideTooltip(tooltip); return; }
            canvas.style.cursor = 'pointer';
            showTooltip(canvas, tooltip, nearest.x, nearest.y, `
                <div class="cc-tip-title">${escapeHtml(nearest.label)}</div>
                <div class="cc-tip-meta">${escapeHtml(nearest.month)} · ${nearest.value} proyecto${Number(nearest.value) === 1 ? '' : 's'}</div>
            `);
        });
        canvas.addEventListener('mouseleave', () => { canvas.style.cursor = ''; hideTooltip(tooltip); });
    }

    function bindDistributionTooltip(canvas) {
        const tooltip = document.getElementById('ccDistributionTooltip');
        canvas.addEventListener('mousemove', event => {
            const p = pointerPosition(event, canvas);
            const bars = canvas.__bars || [];
            const active = bars.find(bar => p.x >= bar.x && p.x <= bar.x + bar.w && p.y >= Math.min(bar.y, bar.baseY) - 8 && p.y <= bar.baseY + 8);

            if (!active) { hideTooltip(tooltip); canvas.style.cursor = ''; return; }
            canvas.style.cursor = 'pointer';
            showTooltip(canvas, tooltip, active.x + active.w / 2, active.y, `
                <div class="cc-tip-title">${escapeHtml(active.label)}</div>
                <div class="cc-tip-meta">${active.value} proyecto${Number(active.value) === 1 ? '' : 's'} en esta etapa</div>
            `);
        });
        canvas.addEventListener('mouseleave', () => { canvas.style.cursor = ''; hideTooltip(tooltip); });
    }

    function drawEvolution() {
        const canvas = document.getElementById('ccEvolutionChart');
        if (!canvas) return;
        const { ctx, width, height } = setupCanvas(canvas);
        const padding = { top: 20, right: 12, bottom: 36, left: 35 };
        
        const allValues = series.flatMap(item => item.data || []).map(Number).filter(Number.isFinite);
        const maxValue = Math.max(5, Math.ceil(Math.max(...allValues))); 

        const plotW = width - padding.left - padding.right;
        const plotH = height - padding.top - padding.bottom;
        const points = [];

        drawGrid(ctx, width, height, padding, maxValue);

        ctx.fillStyle = '#6b7280';
        ctx.font = '12px Inter, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';
        monthLabels.forEach((label, index) => {
            const x = padding.left + (plotW / Math.max(monthLabels.length - 1, 1)) * index;
            ctx.fillText(label, x, padding.top + plotH + 12);
        });

        series.forEach((item, seriesIndex) => {
            const color = colorFor(item.color, seriesIndex === 0 ? colorMap.blue : colorMap.gray);
            const data = item.data || [];

            ctx.beginPath();
            let firstX, lastX;
            data.forEach((rawValue, index) => {
                const value = Number(rawValue) || 0;
                const x = padding.left + (plotW / Math.max(data.length - 1, 1)) * index;
                const y = padding.top + plotH - (value / maxValue) * plotH;

                if (index === 0) {
                    ctx.moveTo(x, y);
                    firstX = x;
                } else {
                    const prevValue = Number(data[index - 1]) || 0;
                    const prevX = padding.left + (plotW / Math.max(data.length - 1, 1)) * (index - 1);
                    const prevY = padding.top + plotH - (prevValue / maxValue) * plotH;
                    const cp1x = prevX + (x - prevX) / 2;
                    ctx.bezierCurveTo(cp1x, prevY, cp1x, y, x, y);
                }
                lastX = x;
            });
            ctx.lineTo(lastX, padding.top + plotH);
            ctx.lineTo(firstX, padding.top + plotH);
            ctx.closePath();
            ctx.fillStyle = hexToRgba(color, 0.1); 
            ctx.fill();

            ctx.beginPath();
            data.forEach((rawValue, index) => {
                const value = Number(rawValue) || 0;
                const x = padding.left + (plotW / Math.max(data.length - 1, 1)) * index;
                const y = padding.top + plotH - (value / maxValue) * plotH;

                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
                    const prevValue = Number(data[index - 1]) || 0;
                    const prevX = padding.left + (plotW / Math.max(data.length - 1, 1)) * (index - 1);
                    const prevY = padding.top + plotH - (prevValue / maxValue) * plotH;
                    const cp1x = prevX + (x - prevX) / 2;
                    ctx.bezierCurveTo(cp1x, prevY, cp1x, y, x, y);
                }
            });
            ctx.strokeStyle = color;
            ctx.lineWidth = 2.5;
            ctx.stroke();

            data.forEach((rawValue, index) => {
                const value = Number(rawValue) || 0;
                const x = padding.left + (plotW / Math.max(data.length - 1, 1)) * index;
                const y = padding.top + plotH - (value / maxValue) * plotH;

                ctx.fillStyle = '#fff';
                ctx.beginPath();
                ctx.arc(x, y, 4, 0, Math.PI * 2);
                ctx.fill();
                ctx.strokeStyle = color;
                ctx.lineWidth = 2;
                ctx.stroke();

                points.push({
                    x, y, value,
                    label: item.label || 'Etapa',
                    month: monthLabels[index] || '',
                    color,
                });
            });
        });

        canvas.__points = points;
        if (!canvas.__tooltipBound) {
            canvas.__tooltipBound = true;
            bindEvolutionTooltip(canvas);
        }

        renderTextLegend('ccEvolutionLegend', series.map((item, index) => ({
            label: item.label || labels[index] || 'Etapa',
            color: colorFor(item.color, index === 0 ? colorMap.blue : colorMap.gray),
        })));
    }

    function drawDistribution() {
        const canvas = document.getElementById('ccDistributionChart');
        if (!canvas) return;
        const { ctx, width, height } = setupCanvas(canvas);
        const padding = { top: 20, right: 12, bottom: 48, left: 35 };
        
        const maxValue = Math.max(5, Math.ceil(Math.max(...values.map(Number)))); 

        const plotW = width - padding.left - padding.right;
        const plotH = height - padding.top - padding.bottom;
        const slotW = plotW / Math.max(values.length, 1);
        const barW = slotW * 0.70; 
        const bars = [];

        drawGrid(ctx, width, height, padding, maxValue);

        values.forEach((rawValue, index) => {
            const value = Number(rawValue) || 0;
            const stage = series[index] || {};
            const color = colorFor(stage.color, index === 0 ? colorMap.blue : colorMap.gray);
            const x = padding.left + slotW * index + (slotW - barW) / 2;
            const h = (value / Math.max(maxValue, 1)) * plotH;
            const y = padding.top + plotH - h;

            ctx.fillStyle = color;
            ctx.fillRect(x, y, barW, h);

            ctx.save();
            ctx.translate(x + barW / 2, padding.top + plotH + 18);
            ctx.rotate(-0.25); 
            ctx.fillStyle = '#4b5563';
            ctx.font = '13px Inter, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'top';
            ctx.fillText(labels[index] || '', 0, 0);
            ctx.restore();

            bars.push({
                x, y, w: barW, h,
                baseY: padding.top + plotH,
                value,
                label: labels[index] || 'Etapa',
                color,
            });
        });

        canvas.__bars = bars;
        if (!canvas.__tooltipBound) {
            canvas.__tooltipBound = true;
            bindDistributionTooltip(canvas);
        }
    }

    function drawAll() {
        drawEvolution();
        drawDistribution();
    }

    window.addEventListener('resize', drawAll);
    setTimeout(drawAll, 100);
})();
</script>
@endpush