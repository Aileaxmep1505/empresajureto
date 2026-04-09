@extends('layouts.app')

@section('title', 'Proyectos')
<link rel="stylesheet" href="{{ asset('css/proyecto.css') }}?v={{ time() }}">

@php
    $currentView = request('view', 'cards');

    $openColumns = collect(explode(',', (string) request('open', '1')))
        ->map(fn ($id) => (int) trim($id))
        ->filter(fn ($id) => $id > 0)
        ->values()
        ->all();

    $statusMap = [
        'Análisis de Bases' => 'Vigente',
        'Revisión' => 'Vigente',
        'Participa' => 'Vigente',
        'No participa' => 'Vigente',
        'Ganado' => 'Vigente',
        'Perdido' => 'Vigente',
        'Desierta' => 'Vigente',
    ];

    $assignedNames = [
        'S' => 'Samantha Michelle',
        'G' => 'Geovanni Emmanuel',
        'A' => 'Jose Alfredo',
        'J' => 'Juan Rene',
        'M' => 'Samantha Michelle',
        'R' => 'Geovanni Emmanuel',
        'L' => 'Juan Rene',
    ];

    $toneStyles = [
        'blue' => ['bg' => '#dde6f6', 'text' => '#2563eb', 'dot' => '#2563eb'],
        'orange' => ['bg' => '#f4eadf', 'text' => '#ef8c35', 'dot' => '#f59e0b'],
        'green' => ['bg' => '#dfece6', 'text' => '#1f9d55', 'dot' => '#22c55e'],
        'red' => ['bg' => '#f6e3e3', 'text' => '#ef4444', 'dot' => '#ef4444'],
        'purple' => ['bg' => '#ece6f6', 'text' => '#7c5cf5', 'dot' => '#8b5cf6'],
        'gray' => ['bg' => '#ececec', 'text' => '#6b7280', 'dot' => '#6b7280'],
        'rose' => ['bg' => '#f3e7e7', 'text' => '#b91c1c', 'dot' => '#b91c1c'],
    ];
@endphp

@section('content')
<div class="pj-page">
    <div class="pj-toolbar">
        <div class="pj-toolbar-left">
            <div class="pj-title">Proyectos</div>

            <form class="pj-search-wrap" method="GET" action="{{ route('projects.index') }}">
                <input type="hidden" name="view" value="{{ $currentView }}">
                <div class="pj-search-box">
                    <svg viewBox="0 0 24 24" fill="none" class="pj-search-icon">
                        <path d="M21 21L16.65 16.65M10.8 18.6a7.8 7.8 0 1 0 0-15.6 7.8 7.8 0 0 0 0 15.6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    <input type="text" name="q" placeholder="Buscar por nombre, asignado, tag..." value="{{ request('q') }}">
                </div>
                <button type="submit" class="pj-btn pj-btn-primary">Buscar</button>
            </form>

            <button type="button" class="pj-btn pj-btn-light pj-btn-create" id="openProjectModal">
                <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                Agregar Nuevo Proyecto
            </button>

            <a href="{{ route('projects.index', array_merge(request()->except('view'), ['view' => 'cards'])) }}"
               class="pj-btn {{ $currentView === 'cards' ? 'pj-btn-primary is-active' : 'pj-btn-light' }}">
                <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                    <path d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z" stroke="currentColor" stroke-width="1.6"/>
                </svg>
                Tarjetas
            </a>

            <a href="{{ route('projects.index', array_merge(request()->except('view'), ['view' => 'list'])) }}"
               class="pj-btn {{ $currentView === 'list' ? 'pj-btn-primary is-active' : 'pj-btn-light' }}">
                <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                    <path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                Lista
            </a>

            <button type="button" class="pj-btn pj-btn-light pj-btn-icon-only" title="Fijar">
                <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                    <path d="M8 4h8v3l-2 2v4l2 2v2H8v-2l2-2V9L8 7V4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>

        <div class="pj-toolbar-right">
            <div class="pj-pop-wrap">
                <button type="button" class="pj-btn pj-btn-light js-toggle-pop" data-pop="sort-pop">
                    <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                        <path d="M8 4v16M8 4l-3 3M8 4l3 3M16 20V4m0 16l-3-3m3 3l3-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Ordenar
                </button>

                <div class="pj-popover pj-sort-pop" id="sort-pop">
                    <div class="pj-pop-head">
                        <span>FILTRO</span>
                        <button type="button" class="pj-link-btn js-clear-sort">Limpiar</button>
                    </div>

                    <div class="pj-form-group">
                        <select class="pj-select">
                            <option>Manual</option>
                            <option>Nombre</option>
                            <option>Fecha de inicio</option>
                            <option>Prioridad</option>
                            <option>Asignado</option>
                        </select>
                    </div>

                    <div class="pj-form-group">
                        <select class="pj-select">
                            <option>Asc</option>
                            <option>Desc</option>
                        </select>
                    </div>

                    <div class="pj-pop-divider"></div>

                    <div class="pj-pop-footer">
                        <div class="pj-pop-count">Filtro: 0</div>
                        <div class="pj-pop-actions">
                            <button type="button" class="pj-btn pj-btn-light js-close-pop">Cancelar</button>
                            <button type="button" class="pj-btn pj-btn-primary js-close-pop">Aplicar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pj-pop-wrap">
                <button type="button" class="pj-btn pj-btn-light js-toggle-pop" data-pop="filter-pop">
                    <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                        <path d="M4 5h16l-6 7v6l-4-2v-4L4 5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    </svg>
                    Filtros
                </button>

                <div class="pj-popover pj-filter-pop" id="filter-pop">
                    <div class="pj-filter-scroll">
                        <div class="pj-filter-section">
                            <div class="pj-filter-title">Asignado</div>
                            <div class="pj-check-list">
                                @foreach(['Jose Alfredo', 'Juan Rene', 'Geovanni Emmanuel', 'Samantha Michelle'] as $name)
                                    <label class="pj-check-row">
                                        <span>{{ $name }}</span>
                                        <input type="checkbox">
                                        <span class="pj-square"></span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="pj-filter-section">
                            <div class="pj-filter-title">Preferencias</div>
                            <label class="pj-check-row">
                                <span>Solo favoritos</span>
                                <input type="checkbox">
                                <span class="pj-square"></span>
                            </label>
                        </div>

                        <div class="pj-filter-section">
                            <div class="pj-filter-title">Prioridad</div>
                            <div class="pj-check-list">
                                <label class="pj-check-row">
                                    <span class="pj-pri-label"><i class="pj-pri-dot is-red"></i> Alta</span>
                                    <input type="checkbox">
                                    <span class="pj-square"></span>
                                </label>
                                <label class="pj-check-row">
                                    <span class="pj-pri-label"><i class="pj-pri-dot is-orange"></i> Media</span>
                                    <input type="checkbox">
                                    <span class="pj-square"></span>
                                </label>
                                <label class="pj-check-row">
                                    <span class="pj-pri-label"><i class="pj-pri-dot is-green"></i> Baja</span>
                                    <input type="checkbox">
                                    <span class="pj-square"></span>
                                </label>
                                <label class="pj-check-row">
                                    <span class="pj-pri-label"><i class="pj-pri-dot is-gray"></i> Normal</span>
                                    <input type="checkbox">
                                    <span class="pj-square"></span>
                                </label>
                            </div>
                        </div>

                        <div class="pj-filter-section">
                            <div class="pj-filter-title">Rango de fechas</div>
                            <div class="pj-date-grid">
                                <div class="pj-date-input">
                                    <input type="text" placeholder="dd/mm/aaaa">
                                    <svg viewBox="0 0 24 24" fill="none" class="pj-date-icon">
                                        <path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <div class="pj-date-input">
                                    <input type="text" placeholder="dd/mm/aaaa">
                                    <svg viewBox="0 0 24 24" fill="none" class="pj-date-icon">
                                        <path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="pj-btn pj-btn-light">
                <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                    <path d="M4 7h16l-5 6v5l-6-3v-2L4 7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                </svg>
                Etiquetas
            </button>

            <button type="button" class="pj-btn pj-btn-primary pj-btn-icon-only" title="Carpetas">
                <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                    <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                </svg>
            </button>

            <button type="button" class="pj-btn pj-btn-light pj-btn-icon-only" title="Eliminar">
                <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                    <path d="M4 7h16M9 7V5h6v2m-8 0 1 12h8l1-12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <button type="button" class="pj-btn pj-btn-light pj-btn-icon-only" title="Más filtros">
                <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                    <path d="M4 5h16l-6 7v6l-4-2v-4L4 5Zm14-1v3m-2-1.5h4" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="pj-view-transition">
        @if($currentView === 'list')
            <div class="pj-list-wrap">
                <div class="pj-list-head">
                    <div class="pj-col pj-col-project">
                        Proyecto
                        <svg viewBox="0 0 24 24" fill="none" class="pj-sort-mini">
                            <path d="M8 5v14m0 0-3-3m3 3 3-3M16 19V5m0 0-3 3m3-3 3 3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="pj-col pj-col-label">Etiqueta</div>
                    <div class="pj-col pj-col-status">Estado</div>
                    <div class="pj-col pj-col-priority">Prioridad</div>
                    <div class="pj-col pj-col-date">Fecha de inicio</div>
                    <div class="pj-col pj-col-assigned">Asignado</div>
                    <div class="pj-col pj-col-star">
                        <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                            <path d="M12 3.8l2.57 5.2 5.74.83-4.15 4.05.98 5.72L12 16.88 6.86 19.6l.98-5.72L3.69 9.83l5.74-.83L12 3.8Z" stroke="currentColor" stroke-width="1.6"/>
                        </svg>
                    </div>
                    <div class="pj-col pj-col-options">Opciones</div>
                </div>

                <div class="pj-list-body">
                    @foreach($columns as $column)
                        @php
                            $tone = $toneStyles[$column['color']] ?? $toneStyles['gray'];
                            $isExpanded = in_array($column['id'], $openColumns, true);
                        @endphp

                        <div class="pj-group" data-group-id="{{ $column['id'] }}">
                            <div class="pj-group-row" style="background: {{ $tone['bg'] }};">
                                <div class="pj-col pj-col-project">
                                    <label class="pj-group-check pj-group-check-master">
                                        <input type="checkbox" class="js-select-column" data-column-id="{{ $column['id'] }}">
                                        <span></span>
                                    </label>

                                    <button type="button" class="pj-group-arrow js-toggle-group" data-id="{{ $column['id'] }}" aria-expanded="{{ $isExpanded ? 'true' : 'false' }}">
                                        <svg viewBox="0 0 24 24" fill="none" class="pj-icon pj-group-chevron">
                                            <path d="M10 8l4 4-4 4" stroke="{{ $tone['text'] }}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>

                                    <span class="pj-group-title">{{ $column['name'] }}</span>
                                    <span class="pj-group-count">({{ $column['count'] }})</span>

                                    @if($column['name'] === 'Análisis de Bases')
                                        <button type="button" class="pj-inline-add">+</button>
                                    @endif
                                </div>

                                <div class="pj-col pj-col-label"></div>
                                <div class="pj-col pj-col-status"></div>
                                <div class="pj-col pj-col-priority"></div>
                                <div class="pj-col pj-col-date"></div>
                                <div class="pj-col pj-col-assigned"></div>
                                <div class="pj-col pj-col-star"></div>
                                <div class="pj-col pj-col-options">
                                    <button type="button" class="pj-dots-btn js-open-project-menu">
                                        <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                                            <path d="M5 12h.01M12 12h.01M19 12h.01" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="pj-group-children {{ $isExpanded ? 'is-open' : '' }}">
                                @if($column['count'] > 0)
                                    @foreach($column['projects'] as $index => $project)
                                        @php
                                            $assignedName = $assignedNames[$project['assigned']] ?? $project['assigned'];
                                            $priority = $project['priority'] ?? 'Normal';
                                            $status = $statusMap[$column['name']] ?? 'Vigente';
                                            $dotColor = $tone['dot'];
                                            $label = $project['labels'][0] ?? null;
                                            $projectId = $column['id'].'-'.$index;
                                        @endphp

                                        <div class="pj-item-row js-project-row"
                                             draggable="true"
                                             data-column-id="{{ $column['id'] }}"
                                             data-project-id="{{ $projectId }}"
                                             data-project-name="{{ $project['name'] }}">
                                            <div class="pj-col pj-col-project">
                                                <label class="pj-row-check">
                                                    <input type="checkbox"
                                                           class="js-project-check"
                                                           data-column-id="{{ $column['id'] }}"
                                                           data-project-id="{{ $projectId }}"
                                                           data-project-name="{{ $project['name'] }}">
                                                    <span></span>
                                                </label>

                                                <span class="pj-item-dot" style="background: {{ $index === 3 ? '#22c55e' : ($index === 4 ? '#f59e0b' : $dotColor) }};"></span>

                                                <button type="button" class="pj-drag-btn js-drag-handle" title="Mover">
                                                    <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                                                        <path d="M9 5h.01M9 12h.01M9 19h.01M15 5h.01M15 12h.01M15 19h.01" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/>
                                                    </svg>
                                                </button>

                                                <div class="pj-item-title">{{ $project['name'] }}</div>
                                            </div>

                                            <div class="pj-col pj-col-label">
                                                <div class="pj-label-cell">
                                                    <div class="pj-label-list js-label-list">
                                                        @if($label)
                                                            <div class="pj-label-pill js-label-pill" data-color="#fee2e2" data-border="#fecaca" data-text="#ef4444">
                                                                <span class="pj-label-pill-text">{{ $label }}</span>
                                                                <button type="button" class="pj-label-pill-menu js-open-tag-menu" aria-label="Opciones etiqueta">
                                                                    <svg viewBox="0 0 24 24" fill="none">
                                                                        <path d="M5 12h.01M12 12h.01M19 12h.01" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/>
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <button type="button"
                                                            class="pj-tag-add js-open-label-pop"
                                                            data-project-id="{{ $projectId }}"
                                                            data-project-name="{{ $project['name'] }}">
                                                        + Agregar
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="pj-col pj-col-status">
                                                <span class="pj-status-pill">{{ $status }}</span>
                                            </div>

                                            <div class="pj-col pj-col-priority">
                                                <span class="pj-priority is-normal">{{ $priority }}</span>
                                            </div>

                                            <div class="pj-col pj-col-date">{{ $project['start_date'] }}</div>
                                            <div class="pj-col pj-col-assigned">{{ $assignedName }}</div>

                                            <div class="pj-col pj-col-star">
                                                <button type="button" class="pj-star-btn">
                                                    <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                                                        <path d="M12 3.8l2.57 5.2 5.74.83-4.15 4.05.98 5.72L12 16.88 6.86 19.6l.98-5.72L3.69 9.83l5.74-.83L12 3.8Z" stroke="currentColor" stroke-width="1.6" fill="{{ !empty($project['starred']) ? 'currentColor' : 'none' }}"/>
                                                    </svg>
                                                </button>
                                            </div>

                                            <div class="pj-col pj-col-options">
                                                <button type="button" class="pj-dots-btn js-open-project-menu">
                                                    <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                                                        <path d="M5 12h.01M12 12h.01M19 12h.01" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="pj-board" id="projectBoard">
                @foreach($columns as $column)
                    @php
                        $isOpen = in_array($column['id'], $openColumns, true);
                        $toneClass = 'tone-' . $column['color'];
                    @endphp

                    <div class="pj-column {{ $toneClass }} {{ $isOpen ? 'is-open' : 'is-collapsed' }}"
                         data-column-id="{{ $column['id'] }}">

                        <button type="button" class="pj-column-collapsed-btn js-open-column" data-id="{{ $column['id'] }}">
                            <div class="pj-collapsed-title">{{ $column['name'] }}</div>
                            <div class="pj-collapsed-count">({{ $column['count'] }})</div>
                        </button>

                        <div class="pj-column-open">
                            <div class="pj-column-header">
                                <div class="pj-column-header-left">
                                    <label class="pj-group-check pj-group-check-master">
                                        <input type="checkbox" class="js-select-column" data-column-id="{{ $column['id'] }}">
                                        <span></span>
                                    </label>

                                    <h3 class="pj-column-title">{{ $column['name'] }}</h3>
                                    <span class="pj-column-count">({{ $column['count'] }})</span>
                                </div>

                                <div class="pj-column-header-actions">
                                    <button type="button" class="pj-icon-btn js-close-column" title="Cerrar">
                                        <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                                            <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>

                                    <button type="button" class="pj-icon-btn" title="Agregar">
                                        <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                                            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        </svg>
                                    </button>

                                    <button type="button" class="pj-icon-btn js-open-project-menu" title="Más">
                                        <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                                            <path d="M5 12h.01M12 12h.01M19 12h.01" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="pj-column-body">
                                @if($column['count'] === 0)
                                    <div class="pj-empty">
                                        <div class="pj-empty-box">Sin proyectos</div>
                                    </div>
                                @else
                                    <div class="pj-cards">
                                        @foreach($column['projects'] as $index => $project)
                                            @php
                                                $label = $project['labels'][0] ?? null;
                                                $projectId = $column['id'].'-card-'.$index;
                                            @endphp

                                            <div class="pj-card js-project-row"
                                                 draggable="true"
                                                 data-column-id="{{ $column['id'] }}"
                                                 data-project-id="{{ $projectId }}"
                                                 data-project-name="{{ $project['name'] }}">
                                                <div class="pj-card-top">
                                                    <div class="pj-card-main">
                                                        <label class="pj-check">
                                                            <input type="checkbox"
                                                                   class="js-project-check"
                                                                   data-column-id="{{ $column['id'] }}"
                                                                   data-project-id="{{ $projectId }}"
                                                                   data-project-name="{{ $project['name'] }}">
                                                            <span></span>
                                                        </label>

                                                        <span class="pj-dot"></span>

                                                        <div class="pj-card-title">
                                                            {{ $project['name'] }}
                                                        </div>
                                                    </div>

                                                    <div class="pj-card-actions">
                                                        <button type="button" class="pj-icon-btn" title="Favorito">
                                                            <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                                                                <path d="M12 3.8l2.57 5.2 5.74.83-4.15 4.05.98 5.72L12 16.88 6.86 19.6l.98-5.72L3.69 9.83l5.74-.83L12 3.8Z" stroke="currentColor" stroke-width="1.6" fill="{{ !empty($project['starred']) ? 'currentColor' : 'none' }}"/>
                                                            </svg>
                                                        </button>

                                                        <button type="button" class="pj-icon-btn js-open-project-menu" title="Más">
                                                            <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                                                                <path d="M12 5h.01M12 12h.01M12 19h.01" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="pj-divider"></div>

                                                <div class="pj-card-meta">
                                                    <div class="pj-meta-row pj-meta-row-labels">
                                                        <div class="pj-meta-label">Etiquetas</div>
                                                        <div class="pj-label-list js-label-list">
                                                            @if($label)
                                                                <div class="pj-label-pill js-label-pill" data-color="#fee2e2" data-border="#fecaca" data-text="#ef4444">
                                                                    <span class="pj-label-pill-text">{{ $label }}</span>
                                                                    <button type="button" class="pj-label-pill-menu js-open-tag-menu" aria-label="Opciones etiqueta">
                                                                        <svg viewBox="0 0 24 24" fill="none">
                                                                            <path d="M5 12h.01M12 12h.01M19 12h.01" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/>
                                                                        </svg>
                                                                    </button>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <button type="button"
                                                                class="pj-tag-add js-open-label-pop"
                                                                data-project-id="{{ $projectId }}"
                                                                data-project-name="{{ $project['name'] }}">
                                                            + Agregar
                                                        </button>
                                                    </div>

                                                    <div class="pj-meta-row">
                                                        <div class="pj-meta-label">Prioridad:</div>
                                                        <span class="pj-priority
                                                            @if(($project['priority'] ?? 'Normal') === 'Alta') is-high
                                                            @elseif(($project['priority'] ?? 'Normal') === 'Baja') is-low
                                                            @else is-normal @endif">
                                                            {{ $project['priority'] ?? 'Normal' }}
                                                        </span>
                                                    </div>

                                                    <div class="pj-meta-row">
                                                        <div class="pj-meta-label">Fecha de inicio:</div>
                                                        <div class="pj-meta-value">{{ $project['start_date'] }}</div>
                                                    </div>

                                                    <div class="pj-meta-row">
                                                        <div class="pj-meta-label">Asignado:</div>
                                                        <div class="pj-avatar">{{ $project['assigned'] }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="pj-bulkbar" id="pjBulkbar" aria-hidden="true">
    <div class="pj-bulkbar-inner">
        <div class="pj-bulkbar-count">
            <span id="pjSelectedCount">0</span> Proyectos seleccionados
            <button type="button" class="pj-bulkbar-clear" id="pjClearSelection" aria-label="Limpiar selección">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <div class="pj-bulkbar-divider"></div>

        <div class="pj-bulkbar-actions">
            <button type="button" class="pj-bulk-action" id="pjBulkLabelsBtn">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M20 10.5 13.5 4H6a2 2 0 0 0-2 2v7.5L10.5 20a2.12 2.12 0 0 0 3 0l6.5-6.5a2.12 2.12 0 0 0 0-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                    <circle cx="8.5" cy="8.5" r="1" fill="currentColor"/>
                </svg>
                Etiquetas
            </button>

            <button type="button" class="pj-bulk-action">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 17.75 6.26 20.77l1.1-6.4L2.7 9.83l6.43-.93L12 3.08l2.87 5.82 6.43.93-4.66 4.54 1.1 6.4L12 17.75Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                </svg>
                Favoritos
            </button>

            <button type="button" class="pj-bulk-action">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M7 7h10v10H7z" stroke="currentColor" stroke-width="1.7"/>
                </svg>
                Color
            </button>

            <button type="button" class="pj-bulk-action">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Mover a etapa
            </button>

            <button type="button" class="pj-bulk-action">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                </svg>
                Prioridad
            </button>

            <button type="button" class="pj-bulk-action">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 11A4 4 0 1 0 9.5 3a4 4 0 0 0 0 8Zm11.5 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Asignar
            </button>

            <button type="button" class="pj-bulk-action">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M3 7h18M8 7V5h8v2m-9 0 1 12h8l1-12" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Archivar
            </button>

            <button type="button" class="pj-bulk-action is-danger">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Eliminar
            </button>
        </div>
    </div>
</div>

<div class="pj-label-popover" id="pjLabelPopover" aria-hidden="true">
    <div class="pj-label-popover-card">
        <div class="pj-label-search">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M21 21L16.65 16.65M10.8 18.6a7.8 7.8 0 1 0 0-15.6 7.8 7.8 0 0 0 0 15.6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            <input type="text" id="pjLabelSearchInput" placeholder="Buscar etiqueta">
        </div>

        <button type="button" class="pj-label-create" id="pjCreateLabelBtn">
            <span>+</span>
            Crear "<strong id="pjCreateLabelText">Etiqueta</strong>"
        </button>

        <div class="pj-label-options" id="pjLabelOptions"></div>
    </div>
</div>

<div class="pj-tag-menu" id="pjTagMenu" aria-hidden="true">
    <div class="pj-tag-menu-card">
        <div class="pj-tag-menu-head">
            <span>Color de etiqueta</span>
            <button type="button" class="pj-tag-menu-close" id="pjCloseTagMenu">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <div class="pj-color-grid" id="pjColorGrid">
            <button type="button" class="pj-color-dot" data-bg="#dbeafe" data-border="#93c5fd" data-text="#2563eb" style="background:#3b82f6"></button>
            <button type="button" class="pj-color-dot" data-bg="#d1fae5" data-border="#86efac" data-text="#059669" style="background:#10b981"></button>
            <button type="button" class="pj-color-dot" data-bg="#fef3c7" data-border="#fde68a" data-text="#ca8a04" style="background:#f59e0b"></button>
            <button type="button" class="pj-color-dot" data-bg="#fee2e2" data-border="#fecaca" data-text="#ef4444" style="background:#ef4444"></button>
            <button type="button" class="pj-color-dot" data-bg="#ede9fe" data-border="#c4b5fd" data-text="#7c3aed" style="background:#8b5cf6"></button>
            <button type="button" class="pj-color-dot" data-bg="#fce7f3" data-border="#f9a8d4" data-text="#db2777" style="background:#ec4899"></button>
            <button type="button" class="pj-color-dot" data-bg="#cffafe" data-border="#67e8f9" data-text="#0891b2" style="background:#06b6d4"></button>
            <button type="button" class="pj-color-dot" data-bg="#ecfccb" data-border="#bef264" data-text="#65a30d" style="background:#84cc16"></button>
            <button type="button" class="pj-color-dot" data-bg="#ffedd5" data-border="#fdba74" data-text="#ea580c" style="background:#f97316"></button>
            <button type="button" class="pj-color-dot" data-bg="#e0e7ff" data-border="#a5b4fc" data-text="#4f46e5" style="background:#6366f1"></button>
            <button type="button" class="pj-color-dot" data-bg="#ccfbf1" data-border="#5eead4" data-text="#0f766e" style="background:#14b8a6"></button>
            <button type="button" class="pj-color-dot" data-bg="#f3e8ff" data-border="#d8b4fe" data-text="#9333ea" style="background:#a855f7"></button>
        </div>

        <div class="pj-tag-menu-actions">
            <button type="button" class="pj-tag-menu-action is-danger" id="pjDeleteTagBtn">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Eliminar
            </button>
        </div>
    </div>
</div>

<div class="pj-project-menu" id="pjProjectMenu" aria-hidden="true">
    <div class="pj-project-menu-card">
        <button type="button" class="pj-project-menu-item">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M4 20h4l10.5-10.5a2.12 2.12 0 0 0-3-3L5 17v3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
            </svg>
            Cambiar nombre
        </button>

        <button type="button" class="pj-project-menu-item">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 11A4 4 0 1 0 9.5 3a4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Asignar usuario
        </button>

        <button type="button" class="pj-project-menu-item">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Mover a otra etapa
        </button>

        <button type="button" class="pj-project-menu-item">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M12 17.75 6.26 20.77l1.1-6.4L2.7 9.83l6.43-.93L12 3.08l2.87 5.82 6.43.93-4.66 4.54 1.1 6.4L12 17.75Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
            </svg>
            Quitar de favoritos
        </button>

        <button type="button" class="pj-project-menu-item">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M20 10.5 13.5 4H6a2 2 0 0 0-2 2v7.5L10.5 20a2.12 2.12 0 0 0 3 0l6.5-6.5a2.12 2.12 0 0 0 0-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                <circle cx="8.5" cy="8.5" r="1" fill="currentColor"/>
            </svg>
            Editar etiquetas
        </button>

        <button type="button" class="pj-project-menu-item">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
            </svg>
            Editar prioridad
        </button>

        <button type="button" class="pj-project-menu-item">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M10 13a5 5 0 0 0 7.07 0l1.41-1.41a5 5 0 0 0-7.07-7.07L10.7 5.23M14 11a5 5 0 0 0-7.07 0L5.52 12.4a5 5 0 0 0 7.07 7.07l.71-.7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Copiar link
        </button>

        <button type="button" class="pj-project-menu-item">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M12 3a9 9 0 0 0-9 9c0 3.84 2.4 7.12 5.78 8.43M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                <circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="1.7"/>
                <path d="M18.5 5.5l-2 2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
            </svg>
            Cambiar color
        </button>

        <div class="pj-project-menu-divider"></div>

        <button type="button" class="pj-project-menu-item">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M3 7h18M8 7V5h8v2m-9 0 1 12h8l1-12" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Archivar
        </button>

        <button type="button" class="pj-project-menu-item is-danger">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Eliminar
        </button>
    </div>
</div>

<div class="pj-modal-backdrop" id="projectModalBackdrop">
    <div class="pj-modal" id="projectModal" role="dialog" aria-modal="true" aria-labelledby="projectModalTitle">
        <div class="pj-modal-head">
            <div>
                <h2 class="pj-modal-title" id="projectModalTitle">Nuevo proyecto</h2>
                <p class="pj-modal-subtitle">La organización empieza aquí: asigna un nombre a tu licitación y comencemos.</p>
            </div>

            <button type="button" class="pj-modal-close" id="closeProjectModal" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" class="pj-modal-close-icon">
                    <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <div class="pj-modal-body">
            <form class="pj-modal-form" id="projectCreateForm" method="POST" action="#" enctype="multipart/form-data">
                @csrf

                <div class="pj-modal-section">
                    <input type="text" class="pj-input pj-input-main" name="name" placeholder="Mi proyecto">
                </div>

                <div class="pj-modal-row-top">
                    <div class="pj-inline-field">
                        <label class="pj-inline-label">Fecha inicio</label>
                        <div class="pj-date-inline">
                            <input type="date" class="pj-inline-input" name="start_date" value="{{ now()->format('Y-m-d') }}">
                            <svg viewBox="0 0 24 24" fill="none" class="pj-inline-icon">
                                <path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>

                    <div class="pj-inline-field pj-inline-field-color">
                        <label class="pj-inline-label">Color</label>
                        <input type="color" name="color" class="pj-color-input" value="#2563eb">
                    </div>

                    <div class="pj-inline-field pj-inline-field-fav">
                        <label class="pj-favorite-toggle">
                            <input type="checkbox" name="favorite">
                            <span class="pj-favorite-box">
                                <svg viewBox="0 0 24 24" fill="none" class="pj-favorite-star">
                                    <path d="M12 3.8l2.57 5.2 5.74.83-4.15 4.05.98 5.72L12 16.88 6.86 19.6l.98-5.72L3.69 9.83l5.74-.83L12 3.8Z" stroke="currentColor" stroke-width="1.6"/>
                                </svg>
                            </span>
                            <span>Favorito</span>
                        </label>
                    </div>
                </div>

                <div class="pj-upload-box" id="projectDropzone">
                    <input type="file" name="documents[]" id="projectDocuments" class="pj-file-input" multiple accept=".pdf,.doc,.docx">

                    <div class="pj-upload-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" class="pj-upload-icon">
                            <path d="M7 16a4 4 0 0 1-.3-7.99A5.5 5.5 0 0 1 17 6.5a4.5 4.5 0 0 1 1 8.89M12 21V10m0 0-4 4m4-4 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>

                    <div class="pj-upload-title">
                        Carga aquí los documentos de la <span>Licitación</span>
                    </div>
                    <div class="pj-upload-subtitle">
                        Arrastra tus documentos aquí o haz clic para seleccionarlos
                    </div>
                    <div class="pj-upload-note">
                        Puedes subir máximo <strong>9 archivos</strong> en formato .docx o .pdf. Los archivos .xlsx no están permitidos.
                    </div>
                </div>

                <div class="pj-selected-files">
                    <div id="projectSelectedFiles" class="pj-selected-list">
                        <div class="pj-selected-empty">No hay archivos seleccionados</div>
                    </div>
                </div>

                <div class="pj-create-no-docs">
                    <label class="pj-checkbox-line">
                        <input type="checkbox" name="without_documents" id="withoutDocuments">
                        <span class="pj-checkbox-box"></span>
                        <span>Crear proyecto sin documentos</span>
                        <span class="pj-help-dot">?</span>
                    </label>
                </div>

                <div class="pj-modal-actions">
                    <button type="submit" class="pj-btn pj-btn-primary pj-btn-submit">Comenzar</button>
                    <button type="button" class="pj-btn pj-btn-ghost" id="cancelProjectModal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const board = document.getElementById('projectBoard');

    if (board) {
        board.addEventListener('click', function (e) {
            const openBtn = e.target.closest('.js-open-column');
            const closeBtn = e.target.closest('.js-close-column');

            if (openBtn) {
                const column = openBtn.closest('.pj-column');
                if (!column) return;

                column.classList.remove('is-collapsed');
                column.classList.add('is-open');
                syncOpenedColumnsInUrl();
                return;
            }

            if (closeBtn) {
                const column = closeBtn.closest('.pj-column');
                if (!column) return;

                column.classList.remove('is-open');
                column.classList.add('is-collapsed');
                syncOpenedColumnsInUrl();
            }
        });

        function syncOpenedColumnsInUrl() {
            const openIds = Array.from(document.querySelectorAll('.pj-column.is-open'))
                .map(col => col.getAttribute('data-column-id'))
                .filter(Boolean);

            const url = new URL(window.location.href);

            if (openIds.length) {
                url.searchParams.set('open', openIds.join(','));
            } else {
                url.searchParams.delete('open');
            }

            history.replaceState({}, '', url.toString());
        }
    }

    document.querySelectorAll('.js-toggle-group').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const group = document.querySelector(`.pj-group[data-group-id="${id}"]`);
            const children = group ? group.querySelector('.pj-group-children') : null;
            if (!children) return;

            const currentlyOpen = children.classList.contains('is-open');

            if (currentlyOpen) {
                children.classList.remove('is-open');
                this.setAttribute('aria-expanded', 'false');
            } else {
                children.classList.add('is-open');
                this.setAttribute('aria-expanded', 'true');
            }
        });
    });

    const popButtons = document.querySelectorAll('.js-toggle-pop');
    popButtons.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const targetId = this.dataset.pop;
            const target = document.getElementById(targetId);

            document.querySelectorAll('.pj-popover').forEach(pop => {
                if (pop !== target) pop.classList.remove('is-open');
            });

            if (target) target.classList.toggle('is-open');
        });
    });

    document.querySelectorAll('.js-close-pop').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.pj-popover').forEach(pop => pop.classList.remove('is-open'));
        });
    });

    document.querySelectorAll('.js-clear-sort').forEach(btn => {
        btn.addEventListener('click', function () {
            const pop = this.closest('.pj-popover');
            if (!pop) return;
            pop.querySelectorAll('select').forEach(select => {
                select.selectedIndex = 0;
            });
        });
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.pj-pop-wrap')) {
            document.querySelectorAll('.pj-popover').forEach(pop => pop.classList.remove('is-open'));
        }
    });

    const modalBackdrop = document.getElementById('projectModalBackdrop');
    const openProjectModal = document.getElementById('openProjectModal');
    const closeModalsBtns = document.querySelectorAll('#closeProjectModal, #cancelProjectModal');

    function openModal() {
        if (!modalBackdrop) return;
        modalBackdrop.classList.add('is-open');
        document.body.classList.add('pj-modal-open');
    }

    function closeModal() {
        if (!modalBackdrop) return;
        modalBackdrop.classList.remove('is-open');
        setTimeout(() => {
            document.body.classList.remove('pj-modal-open');
        }, 220);
    }

    if (openProjectModal) openProjectModal.addEventListener('click', openModal);
    closeModalsBtns.forEach(btn => btn?.addEventListener('click', closeModal));

    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', function (e) {
            if (e.target === modalBackdrop) closeModal();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modalBackdrop?.classList.contains('is-open')) {
            closeModal();
        }
    });

    const form = document.getElementById('projectCreateForm');
    const inputFiles = document.getElementById('projectDocuments');
    const selectedFilesContainer = document.getElementById('projectSelectedFiles');
    const withoutDocuments = document.getElementById('withoutDocuments');
    const dropzone = document.getElementById('projectDropzone');

    let projectFiles = [];

    function renderSelectedFiles() {
        if (!selectedFilesContainer) return;

        if (!projectFiles.length) {
            selectedFilesContainer.innerHTML = '<div class="pj-selected-empty">No hay archivos seleccionados</div>';
            return;
        }

        selectedFilesContainer.innerHTML = projectFiles.map((file, index) => `
            <div class="pj-file-row">
                <div class="pj-file-name">${file.name}</div>
                <button type="button" class="pj-file-remove" data-index="${index}">Quitar</button>
            </div>
        `).join('');
    }

    function syncInputFiles() {
        if (!inputFiles) return;
        const dt = new DataTransfer();
        projectFiles.forEach(file => dt.items.add(file));
        inputFiles.files = dt.files;
    }

    function addFiles(fileList) {
        if (!fileList || !fileList.length) return;

        const allowed = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        const allowedExtensions = ['pdf', 'doc', 'docx'];

        Array.from(fileList).forEach(file => {
            const ext = (file.name.split('.').pop() || '').toLowerCase();
            const mimeOk = allowed.includes(file.type) || !file.type;
            const extOk = allowedExtensions.includes(ext);

            if (!mimeOk && !extOk) return;
            if (projectFiles.length >= 9) return;

            const exists = projectFiles.some(f => f.name === file.name && f.size === file.size && f.lastModified === file.lastModified);
            if (!exists) {
                projectFiles.push(file);
            }
        });

        syncInputFiles();
        renderSelectedFiles();
    }

    if (inputFiles) {
        inputFiles.addEventListener('change', function (e) {
            addFiles(e.target.files);
        });
    }

    if (dropzone) {
        dropzone.addEventListener('dragover', function (e) {
            e.preventDefault();
            dropzone.classList.add('is-dragover');
        });

        dropzone.addEventListener('dragleave', function () {
            dropzone.classList.remove('is-dragover');
        });

        dropzone.addEventListener('drop', function (e) {
            e.preventDefault();
            dropzone.classList.remove('is-dragover');
            addFiles(e.dataTransfer.files);
        });
    }

    if (selectedFilesContainer) {
        selectedFilesContainer.addEventListener('click', function (e) {
            const btn = e.target.closest('.pj-file-remove');
            if (!btn) return;

            const index = parseInt(btn.dataset.index, 10);
            projectFiles.splice(index, 1);
            syncInputFiles();
            renderSelectedFiles();
        });
    }

    if (withoutDocuments) {
        withoutDocuments.addEventListener('change', function () {
            if (this.checked) {
                if (inputFiles) inputFiles.disabled = true;
                if (dropzone) dropzone.style.opacity = '.55';
            } else {
                if (inputFiles) inputFiles.disabled = false;
                if (dropzone) dropzone.style.opacity = '1';
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
        });
    }

    renderSelectedFiles();

    const bulkbar = document.getElementById('pjBulkbar');
    const selectedCountEl = document.getElementById('pjSelectedCount');
    const clearSelectionBtn = document.getElementById('pjClearSelection');
    const projectChecks = Array.from(document.querySelectorAll('.js-project-check'));
    const columnChecks = Array.from(document.querySelectorAll('.js-select-column'));
    const projectRows = Array.from(document.querySelectorAll('.js-project-row'));

    function getCheckedProjects() {
        return projectChecks.filter(ch => ch.checked);
    }

    function updateRowStates() {
        projectRows.forEach(row => {
            const projectId = row.dataset.projectId;
            const checkbox = document.querySelector(`.js-project-check[data-project-id="${projectId}"]`);
            if (!checkbox) return;
            row.classList.toggle('is-selected', checkbox.checked);
        });
    }

    function updateColumnStates() {
        columnChecks.forEach(master => {
            const columnId = master.dataset.columnId;
            const children = projectChecks.filter(ch => ch.dataset.columnId === columnId);
            const checkedChildren = children.filter(ch => ch.checked);

            if (!children.length) {
                master.checked = false;
                master.indeterminate = false;
                return;
            }

            if (checkedChildren.length === 0) {
                master.checked = false;
                master.indeterminate = false;
            } else if (checkedChildren.length === children.length) {
                master.checked = true;
                master.indeterminate = false;
            } else {
                master.checked = false;
                master.indeterminate = true;
            }
        });
    }

    function updateBulkbar() {
        const checked = getCheckedProjects();
        const total = checked.length;

        selectedCountEl.textContent = total;

        if (total > 0) {
            bulkbar.classList.add('is-open');
            bulkbar.setAttribute('aria-hidden', 'false');
        } else {
            bulkbar.classList.remove('is-open');
            bulkbar.setAttribute('aria-hidden', 'true');
        }

        updateRowStates();
        updateColumnStates();
    }

    projectChecks.forEach(ch => ch.addEventListener('change', updateBulkbar));

    columnChecks.forEach(master => {
        master.addEventListener('change', function () {
            const columnId = this.dataset.columnId;
            const children = projectChecks.filter(ch => ch.dataset.columnId === columnId);
            children.forEach(ch => ch.checked = this.checked);
            updateBulkbar();
        });
    });

    if (clearSelectionBtn) {
        clearSelectionBtn.addEventListener('click', function () {
            projectChecks.forEach(ch => ch.checked = false);
            columnChecks.forEach(ch => {
                ch.checked = false;
                ch.indeterminate = false;
            });
            updateBulkbar();
        });
    }

    updateBulkbar();

    const labelPopover = document.getElementById('pjLabelPopover');
    const labelSearchInput = document.getElementById('pjLabelSearchInput');
    const createLabelBtn = document.getElementById('pjCreateLabelBtn');
    const createLabelText = document.getElementById('pjCreateLabelText');
    const labelOptions = document.getElementById('pjLabelOptions');
    const bulkLabelsBtn = document.getElementById('pjBulkLabelsBtn');

    let activeLabelTarget = null;
    let labelMode = 'single';

    let availableLabels = [
        { name: 'papeleria' },
        { name: '*PRUEBA*' },
        { name: 'urgente' },
        { name: 'licitación' },
        { name: 'base' }
    ];

    function createLabelPill(label, colorSet = {bg:'#fee2e2', border:'#fecaca', text:'#ef4444'}) {
        const wrap = document.createElement('div');
        wrap.className = 'pj-label-pill js-label-pill';
        wrap.dataset.color = colorSet.bg;
        wrap.dataset.border = colorSet.border;
        wrap.dataset.text = colorSet.text;
        wrap.style.background = colorSet.bg;
        wrap.style.borderColor = colorSet.border;
        wrap.style.color = colorSet.text;
        wrap.innerHTML = `
            <span class="pj-label-pill-text">${label}</span>
            <button type="button" class="pj-label-pill-menu js-open-tag-menu" aria-label="Opciones etiqueta">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M5 12h.01M12 12h.01M19 12h.01" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/>
                </svg>
            </button>
        `;
        return wrap;
    }

    function renderLabelOptions(filter = '') {
        const q = (filter || '').trim().toLowerCase();
        const filtered = availableLabels.filter(item => item.name.toLowerCase().includes(q));

        labelOptions.innerHTML = filtered.map(item => `
            <button type="button" class="pj-label-option" data-label="${item.name}">
                ${item.name}
            </button>
        `).join('');
    }

    function placeFloating(el, anchor, offsetY = 8) {
        if (!el || !anchor) return;
        const rect = anchor.getBoundingClientRect();
        el.style.top = `${rect.bottom + window.scrollY + offsetY}px`;
        el.style.left = `${rect.left + window.scrollX}px`;
    }

    function openLabelPopover(anchor, mode = 'single') {
        activeLabelTarget = anchor;
        labelMode = mode;
        placeFloating(labelPopover, anchor, 8);
        labelPopover.classList.add('is-open');
        labelPopover.setAttribute('aria-hidden', 'false');
        labelSearchInput.value = '';
        createLabelText.textContent = 'Etiqueta';
        renderLabelOptions('');
        setTimeout(() => labelSearchInput.focus(), 40);
    }

    function closeLabelPopover() {
        activeLabelTarget = null;
        labelPopover.classList.remove('is-open');
        labelPopover.setAttribute('aria-hidden', 'true');
    }

    function applyLabelToTarget(label) {
        if (labelMode === 'bulk') {
            const checked = getCheckedProjects();
            checked.forEach(ch => {
                const row = document.querySelector(`.js-project-row[data-project-id="${ch.dataset.projectId}"]`);
                if (!row) return;
                const list = row.querySelector('.js-label-list');
                if (!list) return;

                const already = Array.from(list.querySelectorAll('.pj-label-pill-text'))
                    .some(el => el.textContent.trim().toLowerCase() === label.toLowerCase());

                if (!already) {
                    list.appendChild(createLabelPill(label));
                }
            });
            return;
        }

        if (!activeLabelTarget) return;

        const row = activeLabelTarget.closest('.js-project-row');
        if (!row) return;

        const list = row.querySelector('.js-label-list');
        if (!list) return;

        const already = Array.from(list.querySelectorAll('.pj-label-pill-text'))
            .some(el => el.textContent.trim().toLowerCase() === label.toLowerCase());

        if (!already) {
            list.appendChild(createLabelPill(label));
        }
    }

    document.querySelectorAll('.js-open-label-pop').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            openLabelPopover(this, 'single');
        });
    });

    if (bulkLabelsBtn) {
        bulkLabelsBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (!getCheckedProjects().length) return;
            openLabelPopover(this, 'bulk');
        });
    }

    if (labelSearchInput) {
        labelSearchInput.addEventListener('input', function () {
            const value = this.value.trim();
            createLabelText.textContent = value || 'Etiqueta';
            renderLabelOptions(value);
        });
    }

    if (createLabelBtn) {
        createLabelBtn.addEventListener('click', function () {
            const value = (labelSearchInput.value || '').trim();
            if (!value) return;

            const exists = availableLabels.some(item => item.name.toLowerCase() === value.toLowerCase());
            if (!exists) {
                availableLabels.unshift({ name: value });
            }

            applyLabelToTarget(value);
            closeLabelPopover();
        });
    }

    if (labelOptions) {
        labelOptions.addEventListener('click', function (e) {
            const option = e.target.closest('.pj-label-option');
            if (!option) return;
            const label = option.dataset.label;
            if (!label) return;

            applyLabelToTarget(label);
            closeLabelPopover();
        });
    }

    const tagMenu = document.getElementById('pjTagMenu');
    const closeTagMenuBtn = document.getElementById('pjCloseTagMenu');
    const deleteTagBtn = document.getElementById('pjDeleteTagBtn');
    let activeTagPill = null;
    let activeTagAnchor = null;

    function openTagMenu(anchor, pill) {
        activeTagPill = pill;
        activeTagAnchor = anchor;
        placeFloating(tagMenu, anchor, 10);
        tagMenu.classList.add('is-open');
        tagMenu.setAttribute('aria-hidden', 'false');
    }

    function closeTagMenu() {
        activeTagPill = null;
        activeTagAnchor = null;
        tagMenu.classList.remove('is-open');
        tagMenu.setAttribute('aria-hidden', 'true');
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.js-open-tag-menu');
        if (btn) {
            e.stopPropagation();
            const pill = btn.closest('.js-label-pill');
            if (!pill) return;
            openTagMenu(btn, pill);
        }
    });

    if (closeTagMenuBtn) {
        closeTagMenuBtn.addEventListener('click', closeTagMenu);
    }

    document.querySelectorAll('.pj-color-dot').forEach(dot => {
        dot.addEventListener('click', function () {
            if (!activeTagPill) return;
            const bg = this.dataset.bg;
            const border = this.dataset.border;
            const text = this.dataset.text;

            activeTagPill.dataset.color = bg;
            activeTagPill.dataset.border = border;
            activeTagPill.dataset.text = text;

            activeTagPill.style.background = bg;
            activeTagPill.style.borderColor = border;
            activeTagPill.style.color = text;
        });
    });

    if (deleteTagBtn) {
        deleteTagBtn.addEventListener('click', function () {
            if (activeTagPill) {
                activeTagPill.remove();
            }
            closeTagMenu();
        });
    }

    const projectMenu = document.getElementById('pjProjectMenu');
    let activeProjectAnchor = null;

    function openProjectMenu(anchor) {
        activeProjectAnchor = anchor;
        placeFloating(projectMenu, anchor, 8);
        projectMenu.classList.add('is-open');
        projectMenu.setAttribute('aria-hidden', 'false');
    }

    function closeProjectMenu() {
        activeProjectAnchor = null;
        projectMenu.classList.remove('is-open');
        projectMenu.setAttribute('aria-hidden', 'true');
    }

    document.querySelectorAll('.js-open-project-menu').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            openProjectMenu(this);
        });
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.js-open-label-pop') &&
            !e.target.closest('#pjLabelPopover') &&
            !e.target.closest('#pjBulkLabelsBtn')) {
            closeLabelPopover();
        }

        if (!e.target.closest('.js-open-tag-menu') &&
            !e.target.closest('#pjTagMenu')) {
            closeTagMenu();
        }

        if (!e.target.closest('.js-open-project-menu') &&
            !e.target.closest('#pjProjectMenu')) {
            closeProjectMenu();
        }
    });

    window.addEventListener('resize', function () {
        if (activeLabelTarget && labelPopover.classList.contains('is-open')) {
            placeFloating(labelPopover, activeLabelTarget, 8);
        }
        if (activeTagAnchor && tagMenu.classList.contains('is-open')) {
            placeFloating(tagMenu, activeTagAnchor, 10);
        }
        if (activeProjectAnchor && projectMenu.classList.contains('is-open')) {
            placeFloating(projectMenu, activeProjectAnchor, 8);
        }
    });

    window.addEventListener('scroll', function () {
        if (activeLabelTarget && labelPopover.classList.contains('is-open')) {
            placeFloating(labelPopover, activeLabelTarget, 8);
        }
        if (activeTagAnchor && tagMenu.classList.contains('is-open')) {
            placeFloating(tagMenu, activeTagAnchor, 10);
        }
        if (activeProjectAnchor && projectMenu.classList.contains('is-open')) {
            placeFloating(projectMenu, activeProjectAnchor, 8);
        }
    }, true);

    let draggingEl = null;

    function handleDragStart(e) {
        draggingEl = this;
        this.classList.add('is-dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', this.dataset.projectId || '');
    }

    function handleDragEnd() {
        this.classList.remove('is-dragging');
        document.querySelectorAll('.pj-drop-target').forEach(el => el.classList.remove('pj-drop-target'));
    }

    function handleDragOver(e) {
        e.preventDefault();
        if (!draggingEl || draggingEl === this) return;
        this.classList.add('pj-drop-target');
        e.dataTransfer.dropEffect = 'move';
    }

    function handleDragLeave() {
        this.classList.remove('pj-drop-target');
    }

    function handleDrop(e) {
        e.preventDefault();
        this.classList.remove('pj-drop-target');
        if (!draggingEl || draggingEl === this) return;

        const currentParent = this.parentNode;
        if (!currentParent) return;

        const draggingRect = draggingEl.getBoundingClientRect();
        const targetRect = this.getBoundingClientRect();
        const shouldInsertAfter = (e.clientY - targetRect.top) > (targetRect.height / 2);

        if (shouldInsertAfter) {
            if (this.nextSibling) {
                currentParent.insertBefore(draggingEl, this.nextSibling);
            } else {
                currentParent.appendChild(draggingEl);
            }
        } else {
            currentParent.insertBefore(draggingEl, this);
        }
    }

    projectRows.forEach(row => {
        row.addEventListener('dragstart', handleDragStart);
        row.addEventListener('dragend', handleDragEnd);
        row.addEventListener('dragover', handleDragOver);
        row.addEventListener('dragleave', handleDragLeave);
        row.addEventListener('drop', handleDrop);
    });
});
</script>
@endsection