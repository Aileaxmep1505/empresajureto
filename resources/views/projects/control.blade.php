@extends('layouts.app')

@section('title', 'Centro de Control')

@push('styles')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  :root {
    --bg: #f9fafb;
    --card: #ffffff;
    --ink: #333333;
    --muted: #888888;
    --line: #ebebeb;
    --blue: #007aff;
    --blue-soft: #e6f0ff;
    --success: #15803d;
    --success-soft: #e6ffe6;
    --danger: #ff4a4a;
    --danger-soft: #ffebeb;
  }

  .cc-page {
    min-height: 100vh;
    background: var(--bg);
    color: var(--ink);
    font-family: 'Quicksand', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    padding: 28px 28px 44px;
  }

  .cc-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 24px;
  }

  .cc-title-row {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
  }

  .cc-title {
    margin: 0;
    color: #111;
    font-size: clamp(2rem, 3vw, 3rem);
    line-height: 1;
    font-weight: 700;
    letter-spacing: -.04em;
  }

  .cc-time {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #666;
    font-size: .98rem;
    font-weight: 600;
  }

  .cc-subtitle {
    margin: 8px 0 0;
    color: #666;
    font-size: 1rem;
    font-weight: 600;
  }

  .cc-btn {
    min-height: 44px;
    padding: 0 16px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: var(--card);
    color: #111;
    font-family: inherit;
    font-size: .92rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    text-decoration: none;
    cursor: pointer;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease;
  }

  .cc-btn:hover {
    background: #f9fafb;
    box-shadow: 0 4px 12px rgba(0,0,0,.03);
  }

  .cc-btn:active {
    transform: scale(.98);
  }

  .cc-icon {
    width: 20px;
    height: 20px;
  }

  .cc-panel,
  .cc-card,
  .cc-chart,
  .cc-shortcut-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
  }

  .cc-panel {
    padding: 22px;
    margin-bottom: 28px;
  }

  .cc-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 18px;
  }

  .cc-panel-title {
    margin: 0;
    color: #111;
    font-size: 1.25rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .cc-filter-grid {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
  }

  .cc-field {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .cc-label {
    color: #333;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .cc-select {
    width: 220px;
    height: 48px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #fff;
    color: #111;
    padding: 0 14px;
    font-family: inherit;
    font-size: .95rem;
    font-weight: 600;
    outline: none;
  }

  .cc-select:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .cc-labels {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  .cc-tag {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    height: 30px;
    padding: 0 12px;
    border: 1px solid var(--line);
    border-radius: 999px;
    background: #fff;
    color: #111;
    font-size: .86rem;
    font-weight: 700;
    text-decoration: none;
    transition: background .16s ease, border-color .16s ease, color .16s ease;
  }

  .cc-tag:hover,
  .cc-tag.is-active {
    border-color: var(--blue);
    background: var(--blue-soft);
    color: var(--blue);
  }

  .cc-total {
    margin: 0 0 22px;
    color: #111;
    font-size: 1.45rem;
    font-weight: 700;
  }

  .cc-total strong {
    color: var(--blue);
  }

  .cc-stage-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(150px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
  }

  .cc-card {
    min-height: 132px;
    padding: 24px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    transition: transform .16s ease, box-shadow .16s ease;
  }

  .cc-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 22px rgba(0,0,0,.04);
  }

  .cc-card-label {
    color: #666;
    font-size: .98rem;
    font-weight: 600;
    margin-bottom: 14px;
    line-height: 1.25;
  }

  .cc-card-value {
    color: #050816;
    font-size: 2.1rem;
    font-weight: 700;
    line-height: 1;
  }

  .cc-card-icon {
    width: 58px;
    height: 58px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: var(--blue-soft);
    color: var(--blue);
    flex: 0 0 auto;
  }

  .cc-card-icon.is-orange { background: #fff7ed; color: #f59e0b; }
  .cc-card-icon.is-green { background: #e6ffe6; color: #10b981; }
  .cc-card-icon.is-red { background: #ffebeb; color: #ff4a4a; }
  .cc-card-icon.is-purple { background: #f1ebff; color: #8b5cf6; }
  .cc-card-icon.is-gray { background: #f1f5f9; color: #667085; }
  .cc-card-icon.is-rose { background: #fff1f2; color: #b91c1c; }

  .cc-main-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 24px;
    margin-bottom: 30px;
  }

  .cc-chart {
    padding: 26px;
    min-height: 380px;
  }

  .cc-chart-title {
    margin: 0 0 20px;
    color: #111;
    font-size: 1.45rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .cc-canvas-wrap {
    height: 290px;
    position: relative;
  }

  .cc-progress-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 34px;
  }

  .cc-progress-card {
    padding: 26px;
    text-align: center;
  }

  .cc-progress-icon {
    width: 76px;
    height: 76px;
    border-radius: 999px;
    background: #f9fafb;
    display: grid;
    place-items: center;
    margin: 0 auto 18px;
    color: var(--blue);
  }

  .cc-progress-title {
    margin: 0 0 14px;
    color: #111;
    font-size: 1.15rem;
    font-weight: 700;
  }

  .cc-progress-number {
    margin: 0 0 4px;
    color: var(--blue);
    font-size: 2.2rem;
    line-height: 1;
    font-weight: 700;
  }

  .cc-progress-sub {
    margin: 0 0 16px;
    color: #666;
    font-weight: 600;
  }

  .cc-progress-track {
    width: 100%;
    height: 16px;
    border-radius: 999px;
    background: #e5e7eb;
    overflow: hidden;
  }

  .cc-progress-bar {
    height: 100%;
    border-radius: inherit;
    background: var(--blue);
  }

  .cc-shortcuts-title {
    margin: 0;
    color: #111;
    font-size: 1.7rem;
    font-weight: 700;
  }

  .cc-shortcuts-subtitle {
    margin: 6px 0 20px;
    color: #666;
    font-weight: 600;
  }

  .cc-shortcuts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 24px;
  }

  .cc-shortcut-card {
    overflow: hidden;
  }

  .cc-shortcut-head {
    padding: 24px 28px;
    border-bottom: 1px solid var(--line);
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .cc-shortcut-title {
    margin: 0;
    color: #111;
    font-size: 1.35rem;
    font-weight: 700;
  }

  .cc-scroll-list {
    max-height: 470px;
    overflow: auto;
    padding: 20px 24px;
    display: grid;
    gap: 16px;
  }

  .cc-mini-card {
    border: 1px solid var(--line);
    border-radius: 14px;
    background: #fff;
    padding: 18px;
    display: flex;
    gap: 16px;
    align-items: center;
    text-decoration: none;
    color: inherit;
    transition: transform .16s ease, box-shadow .16s ease;
  }

  .cc-mini-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 22px rgba(0,0,0,.04);
  }

  .cc-mini-icon,
  .cc-avatar {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    background: var(--blue-soft);
    color: var(--blue);
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    font-weight: 700;
  }

  .cc-mini-body {
    min-width: 0;
    flex: 1;
  }

  .cc-mini-title {
    margin: 0 0 5px;
    color: #111;
    font-weight: 700;
    font-size: 1rem;
  }

  .cc-mini-meta {
    margin: 0;
    color: #666;
    font-weight: 600;
    font-size: .9rem;
  }

  .cc-priority {
    display: inline-flex;
    margin-top: 10px;
    padding: 6px 11px;
    border-radius: 999px;
    background: #eef2f7;
    color: #667085;
    font-size: .82rem;
    font-weight: 700;
  }

  .cc-date-box {
    text-align: center;
    color: var(--blue);
    min-width: 58px;
    font-weight: 700;
  }

  .cc-date-day {
    font-size: 1.6rem;
    line-height: 1;
  }

  .cc-date-month {
    color: #666;
    font-size: .78rem;
    text-transform: uppercase;
  }

  .cc-note {
    align-items: flex-start;
  }

  .cc-note-body {
    color: #333;
    font-weight: 600;
    line-height: 1.45;
    margin: 8px 0 12px;
  }

  .cc-empty {
    padding: 36px 18px;
    text-align: center;
    color: #888;
    font-weight: 600;
  }

  @media (max-width: 1500px) {
    .cc-stage-grid { grid-template-columns: repeat(4, 1fr); }
    .cc-progress-grid { grid-template-columns: repeat(2, 1fr); }
    .cc-shortcuts-grid { grid-template-columns: 1fr; }
  }

  @media (max-width: 980px) {
    .cc-page { padding: 20px 16px 34px; }
    .cc-header, .cc-filter-grid { align-items: stretch; flex-direction: column; }
    .cc-field, .cc-select { width: 100%; }
    .cc-stage-grid, .cc-main-grid, .cc-progress-grid { grid-template-columns: 1fr; }
  }
</style>
@endpush

@section('content')
<div class="cc-page">
    <header class="cc-header">
        <div>
            <div class="cc-title-row">
                <h1 class="cc-title">Centro de Control</h1>
                <span class="cc-time">
                    <svg class="cc-icon" viewBox="0 0 24 24" fill="none"><path d="M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    {{ now()->translatedFormat('l, d \d\e F \d\e Y, g:i a') }}
                </span>
            </div>
            <p class="cc-subtitle">Análisis en tiempo real de proyectos de licitación</p>
        </div>

        <button type="button" class="cc-btn" onclick="window.print()">
            <svg class="cc-icon" viewBox="0 0 24 24" fill="none"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Exportar
            <svg class="cc-icon" viewBox="0 0 24 24" fill="none"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </header>

    <form class="cc-panel" method="GET" action="{{ route('projects.control') }}">
        <div class="cc-panel-head">
            <h2 class="cc-panel-title">
                <svg class="cc-icon" viewBox="0 0 24 24" fill="none"><path d="M4 5h16l-6 7v6l-4-2v-4L4 5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                Periodo y etiquetas
            </h2>
            <button type="submit" class="cc-btn">Aplicar filtros</button>
        </div>

        <div class="cc-filter-grid">
            <label class="cc-field">
                <span class="cc-label">
                    <svg class="cc-icon" viewBox="0 0 24 24" fill="none"><path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    Periodo:
                </span>
                <select name="period" class="cc-select" onchange="this.form.submit()">
                    <option value="all" @selected($period === 'all')>Todos</option>
                    <option value="30" @selected($period === '30')>Últimos 30 días</option>
                    <option value="90" @selected($period === '90')>Últimos 90 días</option>
                    <option value="year" @selected($period === 'year')>Este año</option>
                </select>
            </label>

            <label class="cc-field">
                <span class="cc-label">
                    <svg class="cc-icon" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    Asignado
                </span>
                <select name="assignee" class="cc-select" onchange="this.form.submit()">
                    <option value="all" @selected($assigneeId === 'all')>Todos</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string) $assigneeId === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </label>

            <div class="cc-field">
                <span class="cc-label">
                    <svg class="cc-icon" viewBox="0 0 24 24" fill="none"><path d="M20 10.5 12.5 3H5a2 2 0 0 0-2 2v7.5l7.5 7.5a2.12 2.12 0 0 0 3 0l6.5-6.5a2.12 2.12 0 0 0 0-3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                    Etiquetas:
                </span>
                <div class="cc-labels">
                    @forelse($labels as $item)
                        <a class="cc-tag {{ $label === $item['name'] ? 'is-active' : '' }}"
                           href="{{ route('projects.control', array_filter(['period' => $period, 'assignee' => $assigneeId, 'label' => $item['name']])) }}">
                            + {{ $item['name'] }}
                        </a>
                    @empty
                        <span class="cc-tag">Sin etiquetas</span>
                    @endforelse

                    @if($label !== '')
                        <a class="cc-tag is-active" href="{{ route('projects.control', array_filter(['period' => $period, 'assignee' => $assigneeId])) }}">Limpiar etiqueta</a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    <h2 class="cc-total">Total de Proyectos: <strong>{{ $totalProjects }}</strong></h2>

    <section class="cc-stage-grid">
        @foreach($stageCards as $card)
            @php
                $iconClass = match($card['color']) {
                    'orange' => 'is-orange',
                    'green' => 'is-green',
                    'red' => 'is-red',
                    'purple' => 'is-purple',
                    'gray' => 'is-gray',
                    'rose' => 'is-rose',
                    default => '',
                };
            @endphp
            <article class="cc-card">
                <div>
                    <div class="cc-card-label">{{ $card['name'] }}</div>
                    <div class="cc-card-value">{{ $card['count'] }}</div>
                </div>
                <div class="cc-card-icon {{ $iconClass }}">
                    <svg class="cc-icon" viewBox="0 0 24 24" fill="none"><path d="M8 12h8M8 16h5M7 3h7l4 4v14H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
            </article>
        @endforeach
    </section>

    <section class="cc-main-grid">
        <article class="cc-chart">
            <h3 class="cc-chart-title">
                <svg class="cc-icon" viewBox="0 0 24 24" fill="none"><path d="M4 13h3l2-7 4 12 3-8 2 3h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Evolución por Etapas
            </h3>
            <div class="cc-canvas-wrap"><canvas id="ccEvolutionChart"></canvas></div>
        </article>

        <article class="cc-chart">
            <h3 class="cc-chart-title">
                <svg class="cc-icon" viewBox="0 0 24 24" fill="none"><path d="M5 20V9M12 20V4M19 20v-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                Distribución Actual
            </h3>
            <div class="cc-canvas-wrap"><canvas id="ccDistributionChart"></canvas></div>
        </article>
    </section>

    <section class="cc-progress-grid">
        @foreach($stageCards->take(4) as $card)
            <article class="cc-progress-card cc-card">
                <div style="width:100%;">
                    <div class="cc-progress-icon">
                        <svg class="cc-icon" viewBox="0 0 24 24" fill="none"><path d="M8 12h8M8 16h5M7 3h7l4 4v14H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <h3 class="cc-progress-title">{{ $card['name'] }}</h3>
                    <p class="cc-progress-number">{{ $card['count'] }}</p>
                    <p class="cc-progress-sub">{{ $card['percentage'] }}% del total</p>
                    <div class="cc-progress-track">
                        <div class="cc-progress-bar" style="width: {{ $card['percentage'] }}%;"></div>
                    </div>
                </div>
            </article>
        @endforeach
    </section>

    <section>
        <h2 class="cc-shortcuts-title">Accesos Directos</h2>
        <p class="cc-shortcuts-subtitle">Vista rápida de proyectos, eventos y notas</p>

        <div class="cc-shortcuts-grid">
            <article class="cc-shortcut-card">
                <div class="cc-shortcut-head">
                    <svg class="cc-icon" viewBox="0 0 24 24" fill="none"><path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M4 8h16v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                    <h3 class="cc-shortcut-title">Proyectos Recientes</h3>
                </div>
                <div class="cc-scroll-list">
                    @forelse($recentProjects as $project)
                        <a class="cc-mini-card" href="{{ $project['slug'] ? route('projects.show', $project['slug']) : '#' }}">
                            <div class="cc-mini-icon">
                                <svg class="cc-icon" viewBox="0 0 24 24" fill="none"><path d="M9 6V4h6v2M4 8h16v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                            </div>
                            <div class="cc-mini-body">
                                <p class="cc-mini-title">{{ $project['name'] }} @if($project['favorite']) ★ @endif</p>
                                <p class="cc-mini-meta">{{ $project['assignee'] ?: 'Sin asignar' }} · {{ $project['date'] }}</p>
                                <span class="cc-priority">{{ ucfirst($project['priority']) }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="cc-empty">Sin proyectos recientes</div>
                    @endforelse
                </div>
            </article>

            <article class="cc-shortcut-card">
                <div class="cc-shortcut-head">
                    <svg class="cc-icon" viewBox="0 0 24 24" fill="none"><path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    <h3 class="cc-shortcut-title">Eventos Recientes</h3>
                </div>
                <div class="cc-scroll-list">
                    @forelse($upcomingEvents as $event)
                        <div class="cc-mini-card">
                            <div class="cc-date-box">
                                <div class="cc-date-day">{{ $event['day'] }}</div>
                                <div class="cc-date-month">{{ $event['month'] }}</div>
                            </div>
                            <div class="cc-mini-body">
                                <p class="cc-mini-title">{{ $event['title'] }}</p>
                                <p class="cc-mini-meta">{{ $event['project'] }} · {{ $event['date'] }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="cc-empty">Sin eventos próximos</div>
                    @endforelse
                </div>
            </article>

            <article class="cc-shortcut-card">
                <div class="cc-shortcut-head">
                    <svg class="cc-icon" viewBox="0 0 24 24" fill="none"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                    <h3 class="cc-shortcut-title">Notas Recientes</h3>
                </div>
                <div class="cc-scroll-list">
                    @forelse($notes as $note)
                        <a class="cc-mini-card cc-note" href="{{ $note['slug'] ? route('projects.show', $note['slug']) : '#' }}">
                            <div class="cc-avatar">{{ $note['initials'] }}</div>
                            <div class="cc-mini-body">
                                <p class="cc-mini-title">{{ $note['author'] }}</p>
                                <p class="cc-mini-meta">{{ $note['date'] }}</p>
                                <p class="cc-note-body">{{ Str::limit($note['body'], 160) }}</p>
                                <p class="cc-mini-meta">{{ $note['project'] }}</p>
                            </div>
                        </a>
                    @empty
                        <div class="cc-empty">Sin notas recientes</div>
                    @endforelse
                </div>
            </article>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const colors = {
        blue: '#2563eb',
        orange: '#f59e0b',
        green: '#22c55e',
        red: '#ef4444',
        purple: '#8b5cf6',
        gray: '#64748b',
        rose: '#991b1b',
    };

    const labels = @json($distributionLabels);
    const values = @json($distributionValues);
    const monthLabels = @json($monthLabels);
    const series = @json($evolutionSeries);

    function setupCanvas(canvas) {
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.parentElement.getBoundingClientRect();
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        canvas.style.width = rect.width + 'px';
        canvas.style.height = rect.height + 'px';
        const ctx = canvas.getContext('2d');
        ctx.scale(dpr, dpr);
        return { ctx, width: rect.width, height: rect.height };
    }

    function drawGrid(ctx, width, height, padding, max) {
        ctx.clearRect(0, 0, width, height);
        ctx.strokeStyle = '#e5e7eb';
        ctx.lineWidth = 1;
        ctx.fillStyle = '#666';
        ctx.font = '12px Quicksand';

        for (let i = 0; i <= 5; i++) {
            const y = padding.top + ((height - padding.top - padding.bottom) / 5) * i;
            const value = Math.round(max - (max / 5) * i);
            ctx.beginPath();
            ctx.moveTo(padding.left, y);
            ctx.lineTo(width - padding.right, y);
            ctx.stroke();
            ctx.fillText(String(value), 12, y + 4);
        }
    }

    function drawEvolution() {
        const canvas = document.getElementById('ccEvolutionChart');
        if (!canvas) return;

        const { ctx, width, height } = setupCanvas(canvas);
        const padding = { top: 20, right: 24, bottom: 50, left: 54 };
        const maxValue = Math.max(1, ...series.flatMap(item => item.data));
        const plotW = width - padding.left - padding.right;
        const plotH = height - padding.top - padding.bottom;

        drawGrid(ctx, width, height, padding, Math.ceil(maxValue));

        ctx.fillStyle = '#666';
        ctx.font = '12px Quicksand';
        monthLabels.forEach((label, index) => {
            const x = padding.left + (plotW / Math.max(monthLabels.length - 1, 1)) * index;
            ctx.fillText(label, x - 18, height - 22);
        });

        series.forEach(item => {
            const color = colors[item.color] || colors.blue;
            ctx.strokeStyle = color;
            ctx.lineWidth = 3;
            ctx.beginPath();

            item.data.forEach((value, index) => {
                const x = padding.left + (plotW / Math.max(item.data.length - 1, 1)) * index;
                const y = padding.top + plotH - ((Number(value) || 0) / Math.max(maxValue, 1)) * plotH;

                if (index === 0) ctx.moveTo(x, y);
                else ctx.lineTo(x, y);
            });

            ctx.stroke();

            item.data.forEach((value, index) => {
                const x = padding.left + (plotW / Math.max(item.data.length - 1, 1)) * index;
                const y = padding.top + plotH - ((Number(value) || 0) / Math.max(maxValue, 1)) * plotH;
                ctx.fillStyle = '#fff';
                ctx.beginPath();
                ctx.arc(x, y, 4, 0, Math.PI * 2);
                ctx.fill();
                ctx.strokeStyle = color;
                ctx.lineWidth = 2;
                ctx.stroke();
            });
        });
    }

    function drawDistribution() {
        const canvas = document.getElementById('ccDistributionChart');
        if (!canvas) return;

        const { ctx, width, height } = setupCanvas(canvas);
        const padding = { top: 20, right: 24, bottom: 68, left: 54 };
        const maxValue = Math.max(1, ...values);
        const plotW = width - padding.left - padding.right;
        const plotH = height - padding.top - padding.bottom;
        const barW = plotW / Math.max(values.length, 1) * .58;

        drawGrid(ctx, width, height, padding, Math.ceil(maxValue));

        values.forEach((value, index) => {
            const color = Object.values(colors)[index] || colors.blue;
            const x = padding.left + (plotW / values.length) * index + ((plotW / values.length) - barW) / 2;
            const h = ((Number(value) || 0) / Math.max(maxValue, 1)) * plotH;
            const y = padding.top + plotH - h;

            ctx.fillStyle = color;
            ctx.fillRect(x, y, barW, h);

            ctx.save();
            ctx.translate(x + 6, height - 18);
            ctx.rotate(-Math.PI / 10);
            ctx.fillStyle = '#666';
            ctx.font = '12px Quicksand';
            ctx.fillText(labels[index] || '', 0, 0);
            ctx.restore();
        });
    }

    function drawAll() {
        drawEvolution();
        drawDistribution();
    }

    window.addEventListener('resize', drawAll);
    drawAll();
})();
</script>
@endpush
