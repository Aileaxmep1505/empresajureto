@extends('layouts.app')

@section('title', 'Proyectos')

@php
    $currentView = request('view', 'cards');
    $openColumnId = isset($openColumnId) ? (int) $openColumnId : 1;

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
        'blue' => ['bg' => '#dfe7f7', 'text' => '#2358e6', 'dot' => '#2563eb'],
        'orange' => ['bg' => '#f3e8de', 'text' => '#ef8c35', 'dot' => '#f59e0b'],
        'green' => ['bg' => '#ddebe3', 'text' => '#22904a', 'dot' => '#22c55e'],
        'red' => ['bg' => '#f3e0e0', 'text' => '#eb4b4b', 'dot' => '#ef4444'],
        'purple' => ['bg' => '#ece5f7', 'text' => '#8458e8', 'dot' => '#8b5cf6'],
        'gray' => ['bg' => '#ececec', 'text' => '#5f6778', 'dot' => '#6b7280'],
        'rose' => ['bg' => '#f2e5e5', 'text' => '#a22828', 'dot' => '#b91c1c'],
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
                <div class="pj-col pj-col-priority">
                    Prioridad
                    <svg viewBox="0 0 24 24" fill="none" class="pj-sort-mini">
                        <path d="M8 5v14m0 0-3-3m3 3 3-3M16 19V5m0 0-3 3m3-3 3 3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="pj-col pj-col-date">
                    Fecha de inicio
                    <svg viewBox="0 0 24 24" fill="none" class="pj-sort-mini">
                        <path d="M8 5v14m0 0-3-3m3 3 3-3M16 19V5m0 0-3 3m3-3 3 3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="pj-col pj-col-assigned">
                    Asignado
                    <svg viewBox="0 0 24 24" fill="none" class="pj-sort-mini">
                        <path d="M8 5v14m0 0-3-3m3 3 3-3M16 19V5m0 0-3 3m3-3 3 3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
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
                        $isExpanded = $openColumnId === $column['id'];
                    @endphp

                    <div class="pj-group" data-group-id="{{ $column['id'] }}">
                        <div class="pj-group-row" style="background: {{ $tone['bg'] }};">
                            <div class="pj-col pj-col-project">
                                <label class="pj-group-check">
                                    <input type="checkbox">
                                    <span></span>
                                </label>

                                <button type="button" class="pj-group-arrow js-toggle-group" data-id="{{ $column['id'] }}" aria-expanded="{{ $isExpanded ? 'true' : 'false' }}">
                                    <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                                        @if($isExpanded)
                                            <path d="M8 15l4-4 4 4" stroke="{{ $tone['text'] }}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        @else
                                            <path d="M10 8l4 4-4 4" stroke="{{ $tone['text'] }}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        @endif
                                    </svg>
                                </button>

                                <span class="pj-group-title" style="color:#111827;">{{ $column['name'] }}</span>
                                <span class="pj-group-count" style="color:#64748b;">({{ $column['count'] }})</span>

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
                                <button type="button" class="pj-dots-btn">
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
                                    @endphp

                                    <div class="pj-item-row">
                                        <div class="pj-col pj-col-project">
                                            <span class="pj-item-dot" style="background: {{ $index === 3 ? '#22c55e' : ($index === 4 ? '#f59e0b' : $dotColor) }};"></span>

                                            <button type="button" class="pj-drag-btn">
                                                <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                                                    <path d="M9 5h.01M9 12h.01M9 19h.01M15 5h.01M15 12h.01M15 19h.01" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/>
                                                </svg>
                                            </button>

                                            <div class="pj-item-title">{{ $project['name'] }}</div>
                                        </div>

                                        <div class="pj-col pj-col-label">
                                            @if($label)
                                                <span class="pj-tag-pill">{{ $label }}</span>
                                            @else
                                                <button type="button" class="pj-tag-add">+ Agregar</button>
                                            @endif
                                        </div>

                                        <div class="pj-col pj-col-status">
                                            <span class="pj-status-pill">{{ $status }}</span>
                                        </div>

                                        <div class="pj-col pj-col-priority">
                                            <span class="pj-priority is-normal">{{ $priority }}</span>
                                        </div>

                                        <div class="pj-col pj-col-date">
                                            {{ $project['start_date'] }}
                                        </div>

                                        <div class="pj-col pj-col-assigned">
                                            {{ $assignedName }}
                                        </div>

                                        <div class="pj-col pj-col-star">
                                            <button type="button" class="pj-star-btn">
                                                <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                                                    <path d="M12 3.8l2.57 5.2 5.74.83-4.15 4.05.98 5.72L12 16.88 6.86 19.6l.98-5.72L3.69 9.83l5.74-.83L12 3.8Z" stroke="currentColor" stroke-width="1.6" fill="{{ !empty($project['starred']) ? 'currentColor' : 'none' }}"/>
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="pj-col pj-col-options">
                                            <button type="button" class="pj-dots-btn">
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
                    $isOpen = $openColumnId === $column['id'];
                    $toneClass = 'tone-' . $column['color'];
                @endphp

                <div class="pj-column {{ $toneClass }} {{ $isOpen ? 'is-open' : 'is-collapsed' }}"
                     data-column-id="{{ $column['id'] }}">

                    @if(!$isOpen)
                        <button type="button" class="pj-column-collapsed-btn js-open-column" data-id="{{ $column['id'] }}">
                            <div class="pj-collapsed-title">{{ $column['name'] }}</div>
                            <div class="pj-collapsed-count">({{ $column['count'] }})</div>
                        </button>
                    @else
                        <div class="pj-column-open">
                            <div class="pj-column-header">
                                <div class="pj-column-header-left">
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

                                    <button type="button" class="pj-icon-btn" title="Más">
                                        <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                                            <path d="M5 12h.01M12 12h.01M19 12h.01" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="pj-column-body">
                                @if($column['count'] === 0)
                                    <div class="pj-empty">
                                        <div class="pj-empty-box">
                                            Sin proyectos
                                        </div>
                                    </div>
                                @else
                                    <div class="pj-cards">
                                        @foreach($column['projects'] as $project)
                                            <div class="pj-card">
                                                <div class="pj-card-top">
                                                    <div class="pj-card-main">
                                                        <label class="pj-check">
                                                            <input type="checkbox">
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

                                                        <button type="button" class="pj-icon-btn" title="Más">
                                                            <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                                                                <path d="M12 5h.01M12 12h.01M12 19h.01" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="pj-divider"></div>

                                                <div class="pj-card-meta">
                                                    <div class="pj-meta-row">
                                                        <div class="pj-meta-label">Etiquetas</div>
                                                        <button type="button" class="pj-tag-add">+ Agregar</button>
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
                    @endif
                </div>
            @endforeach
        </div>
    @endif
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
                    <input type="text" class="pj-input" name="name" placeholder="Mi proyecto">
                </div>

                <div class="pj-modal-grid">
                    <div class="pj-modal-field">
                        <label class="pj-modal-label">Fecha inicio</label>
                        <div class="pj-input-icon-wrap">
                            <input type="date" class="pj-input" name="start_date" value="{{ now()->format('Y-m-d') }}">
                            <svg viewBox="0 0 24 24" fill="none" class="pj-input-icon">
                                <path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>

                    <div class="pj-modal-field">
                        <label class="pj-modal-label">Color</label>
                        <div class="pj-color-row">
                            <input type="color" name="color" class="pj-color-input" value="#2563eb">
                        </div>
                    </div>

                    <div class="pj-modal-field pj-favorite-wrap">
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
                        Puedes subir máximo 9 archivos en formato .docx o .pdf. Los archivos .xlsx no están permitidos.
                    </div>
                </div>

                <div class="pj-selected-files">
                    <div class="pj-selected-title">Archivos seleccionados</div>
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
                    <button type="submit" class="pj-btn pj-btn-primary">Comenzar</button>
                    <button type="button" class="pj-btn pj-btn-light" id="cancelProjectModal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .pj-page{
        padding:14px 16px 18px;
        background:#f6f7f9;
        min-height:calc(100vh - 64px);
    }

    .pj-toolbar{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:14px;
        padding:10px 12px;
        margin:-14px -16px 14px;
        background:#fff;
        border-bottom:1px solid #e8e8ec;
        box-shadow:0 2px 12px rgba(15,23,42,.05);
        position:sticky;
        top:0;
        z-index:30;
    }

    .pj-toolbar-left,
    .pj-toolbar-right{
        display:flex;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
    }

    .pj-title{
        font-size:18px;
        font-weight:700;
        color:#23262f;
        margin-right:4px;
    }

    .pj-search-wrap{
        display:flex;
        align-items:center;
        gap:10px;
    }

    .pj-search-box{
        position:relative;
        width:375px;
        max-width:100%;
    }

    .pj-search-box input{
        width:100%;
        height:42px;
        border-radius:14px;
        border:1px solid #d9dce3;
        background:#fff;
        padding:0 14px 0 40px;
        outline:none;
        font-size:14px;
        color:#23262f;
        transition:.2s ease;
    }

    .pj-search-box input:focus{
        border-color:#2a6df5;
        box-shadow:0 0 0 4px rgba(42,109,245,.10);
    }

    .pj-search-icon{
        width:18px;
        height:18px;
        position:absolute;
        top:50%;
        left:13px;
        transform:translateY(-50%);
        color:#80879a;
    }

    .pj-btn{
        height:42px;
        border-radius:14px;
        padding:0 16px;
        border:1px solid #d9dce3;
        background:#fff;
        color:#2b2f38;
        display:inline-flex;
        align-items:center;
        gap:8px;
        font-weight:700;
        font-size:14px;
        cursor:pointer;
        transition:.22s ease;
        text-decoration:none;
        position:relative;
    }

    .pj-btn:hover{
        transform:translateY(-1px);
        box-shadow:0 8px 20px rgba(15,23,42,.08);
        color:#2b2f38;
        text-decoration:none;
    }

    .pj-btn-primary{
        background:#2563eb;
        border-color:#2563eb;
        color:#fff !important;
    }

    .pj-btn-primary:hover{
        background:#1d4ed8;
        border-color:#1d4ed8;
        box-shadow:0 10px 22px rgba(37,99,235,.22);
        color:#fff !important;
    }

    .pj-btn-light{
        background:#fff;
    }

    .pj-btn.is-active{
        background:#2563eb;
        border-color:#2563eb;
        color:#fff !important;
    }

    .pj-btn-create{
        min-width:250px;
        justify-content:center;
    }

    .pj-btn-icon-only{
        width:42px;
        min-width:42px;
        justify-content:center;
        padding:0;
    }

    .pj-icon{
        width:17px;
        height:17px;
        flex:0 0 17px;
    }

    .pj-pop-wrap{
        position:relative;
    }

    .pj-popover{
        position:absolute;
        top:50px;
        right:0;
        background:#fff;
        border:1px solid #e3e6ee;
        border-radius:16px;
        box-shadow:0 16px 34px rgba(15,23,42,.14);
        z-index:50;
        display:none;
    }

    .pj-popover.is-open{
        display:block;
    }

    .pj-sort-pop{
        width:356px;
        padding:18px;
    }

    .pj-filter-pop{
        width:400px;
        padding:0;
        overflow:hidden;
    }

    .pj-pop-head{
        display:flex;
        align-items:center;
        justify-content:space-between;
        margin-bottom:16px;
    }

    .pj-pop-head span{
        font-size:13px;
        font-weight:800;
        color:#7a7f8e;
        letter-spacing:.04em;
    }

    .pj-link-btn{
        border:none;
        background:transparent;
        color:#8b8b8b;
        font-size:14px;
        cursor:pointer;
        padding:0;
    }

    .pj-form-group{
        margin-bottom:16px;
    }

    .pj-select{
        width:100%;
        height:44px;
        border-radius:10px;
        border:1px solid #d9dce3;
        background:#fff;
        padding:0 14px;
        font-size:14px;
        outline:none;
    }

    .pj-select:focus{
        border-color:#2a6df5;
        box-shadow:0 0 0 4px rgba(42,109,245,.08);
    }

    .pj-pop-divider{
        height:1px;
        background:#eceef3;
        margin:10px 0 16px;
    }

    .pj-pop-footer{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
    }

    .pj-pop-count{
        font-size:15px;
        color:#666f82;
    }

    .pj-pop-actions{
        display:flex;
        align-items:center;
        gap:10px;
    }

    .pj-filter-scroll{
        max-height:740px;
        overflow:auto;
        padding:18px 22px 18px;
    }

    .pj-filter-scroll::-webkit-scrollbar{
        width:10px;
    }

    .pj-filter-scroll::-webkit-scrollbar-thumb{
        background:#a3a3a3;
        border-radius:999px;
    }

    .pj-filter-section{
        padding:2px 0 18px;
        border-bottom:1px solid #e6e9ef;
        margin-bottom:18px;
    }

    .pj-filter-section:last-child{
        margin-bottom:0;
        border-bottom:none;
        padding-bottom:0;
    }

    .pj-filter-title{
        font-size:16px;
        font-weight:800;
        color:#111827;
        margin-bottom:14px;
    }

    .pj-check-list{
        display:flex;
        flex-direction:column;
        gap:0;
    }

    .pj-check-row{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:14px;
        min-height:46px;
        border-bottom:1px solid #eef1f5;
        color:#4b5563;
        font-size:15px;
        cursor:pointer;
    }

    .pj-check-row:last-child{
        border-bottom:none;
    }

    .pj-check-row input{
        display:none;
    }

    .pj-square{
        width:22px;
        height:22px;
        border-radius:6px;
        border:1.8px solid #2563eb;
        background:#fff;
        flex:0 0 22px;
        position:relative;
    }

    .pj-check-row input:checked + .pj-square{
        background:#2563eb;
    }

    .pj-check-row input:checked + .pj-square::after{
        content:'';
        position:absolute;
        top:4px;
        left:7px;
        width:5px;
        height:10px;
        border:solid #fff;
        border-width:0 2px 2px 0;
        transform:rotate(45deg);
    }

    .pj-pri-label{
        display:flex;
        align-items:center;
        gap:10px;
    }

    .pj-pri-dot{
        width:13px;
        height:13px;
        border-radius:999px;
        display:inline-block;
    }

    .pj-pri-dot.is-red{ background:#ef4444; }
    .pj-pri-dot.is-orange{ background:#f59e0b; }
    .pj-pri-dot.is-green{ background:#10b981; }
    .pj-pri-dot.is-gray{ background:#7c7f90; }

    .pj-date-grid{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:10px;
    }

    .pj-date-input{
        position:relative;
    }

    .pj-date-input input{
        width:100%;
        height:50px;
        border-radius:10px;
        border:1px solid #d9dce3;
        padding:0 40px 0 14px;
        font-size:14px;
        outline:none;
    }

    .pj-date-input input:focus{
        border-color:#2a6df5;
        box-shadow:0 0 0 4px rgba(42,109,245,.08);
    }

    .pj-date-icon{
        width:18px;
        height:18px;
        position:absolute;
        top:50%;
        right:12px;
        transform:translateY(-50%);
        color:#111827;
    }

    .pj-list-wrap{
        background:#fff;
        border:1px solid #e5e7eb;
        overflow:hidden;
    }

    .pj-list-head,
    .pj-group-row,
    .pj-item-row{
        display:grid;
        grid-template-columns: 2.5fr 1.5fr .75fr .95fr 1.25fr 1.2fr .5fr .65fr;
        align-items:center;
    }

    .pj-list-head{
        min-height:54px;
        background:#fff;
        border-bottom:1px solid #e6e8ef;
        color:#111827;
        font-size:15px;
        font-weight:800;
    }

    .pj-col{
        padding:0 14px;
        min-width:0;
    }

    .pj-list-head .pj-col{
        border-right:1px solid #e6e8ef;
    }

    .pj-list-head .pj-col:last-child{
        border-right:none;
    }

    .pj-col-project{
        display:flex;
        align-items:center;
        gap:10px;
    }

    .pj-col-star,
    .pj-col-options{
        display:flex;
        align-items:center;
        justify-content:center;
    }

    .pj-sort-mini{
        width:15px;
        height:15px;
        color:#737b8c;
        margin-left:2px;
    }

    .pj-group{
        border-bottom:1px solid #e7e9ef;
    }

    .pj-group-row{
        min-height:44px;
        border-bottom:1px solid rgba(0,0,0,.04);
    }

    .pj-group-check{
        position:relative;
        display:flex;
        align-items:center;
    }

    .pj-group-check input{
        display:none;
    }

    .pj-group-check span{
        width:18px;
        height:18px;
        border-radius:3px;
        border:1.5px solid #b9b9b9;
        background:#f8f8f8;
        display:block;
    }

    .pj-group-arrow{
        border:none;
        background:transparent;
        width:20px;
        height:20px;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:0;
        cursor:pointer;
    }

    .pj-group-title{
        font-size:16px;
        font-weight:800;
    }

    .pj-group-count{
        font-size:15px;
    }

    .pj-inline-add{
        border:none;
        background:transparent;
        color:#2563eb;
        font-size:24px;
        line-height:1;
        cursor:pointer;
        padding:0 0 2px;
        margin-left:6px;
    }

    .pj-group-children{
        display:none;
        background:#fff;
    }

    .pj-group-children.is-open{
        display:block;
    }

    .pj-item-row{
        min-height:46px;
        border-bottom:1px solid #eceef3;
        background:#fff;
        transition:.18s ease;
    }

    .pj-item-row:hover{
        background:#fafbfe;
    }

    .pj-item-dot{
        width:20px;
        height:20px;
        border-radius:999px;
        display:inline-block;
        flex:0 0 20px;
    }

    .pj-drag-btn,
    .pj-star-btn,
    .pj-dots-btn{
        width:28px;
        height:28px;
        border:none;
        background:transparent;
        border-radius:8px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        color:#7c7c7c;
        cursor:pointer;
        transition:.18s ease;
    }

    .pj-drag-btn:hover,
    .pj-star-btn:hover,
    .pj-dots-btn:hover{
        background:#f2f4f8;
        color:#111827;
    }

    .pj-item-title{
        font-size:15px;
        font-weight:700;
        color:#111827;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }

    .pj-tag-add{
        min-height:30px;
        padding:0 14px;
        border-radius:999px;
        border:1.4px dashed #c9ced8;
        background:#fff;
        color:#6b7280;
        font-size:13px;
        font-weight:600;
        cursor:pointer;
        transition:.2s ease;
    }

    .pj-tag-add:hover{
        background:#f8fafc;
        border-color:#aeb7c8;
    }

    .pj-tag-pill{
        min-height:30px;
        padding:0 14px;
        border-radius:999px;
        display:inline-flex;
        align-items:center;
        background:#dbe7ff;
        color:#2563eb;
        border:1px solid #98b4ff;
        font-size:13px;
        font-weight:700;
    }

    .pj-status-pill{
        min-height:30px;
        padding:0 14px;
        border-radius:999px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        background:#14b87a;
        color:#fff;
        font-size:13px;
        font-weight:800;
    }

    .pj-priority{
        min-height:30px;
        padding:0 14px;
        border-radius:999px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        font-size:13px;
        font-weight:800;
        color:#fff;
    }

    .pj-priority.is-normal{ background:#6f788b; }
    .pj-priority.is-high{ background:#d64b4b; }
    .pj-priority.is-low{ background:#32a067; }

    .pj-board{
        display:flex;
        align-items:stretch;
        gap:18px;
        overflow-x:auto;
        overflow-y:hidden;
        padding:10px 2px 6px;
        min-height:calc(100vh - 150px);
    }

    .pj-board::-webkit-scrollbar{
        height:10px;
    }

    .pj-board::-webkit-scrollbar-thumb{
        background:#cfd6e4;
        border-radius:999px;
    }

    .pj-column{
        flex:0 0 auto;
        transition:all .28s ease;
    }

    .pj-column.is-collapsed{
        width:58px;
    }

    .pj-column.is-open{
        width:365px;
    }

    .pj-column-collapsed-btn{
        width:58px;
        min-height:302px;
        border:none;
        border-radius:24px;
        padding:14px 10px;
        cursor:pointer;
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        gap:10px;
        box-shadow:0 8px 18px rgba(15,23,42,.06);
        transition:.25s ease;
        border:1px solid rgba(0,0,0,.05);
    }

    .pj-column-collapsed-btn:hover{
        transform:translateY(-2px);
        box-shadow:0 14px 28px rgba(15,23,42,.10);
    }

    .pj-collapsed-title{
        writing-mode:vertical-rl;
        text-orientation:mixed;
        font-size:15px;
        font-weight:800;
        letter-spacing:.1px;
    }

    .pj-collapsed-count{
        writing-mode:vertical-rl;
        text-orientation:mixed;
        font-size:13px;
        font-weight:700;
        opacity:.78;
    }

    .pj-column-open{
        background:#fff;
        border:1px solid #e4e7ee;
        border-radius:22px;
        overflow:hidden;
        min-height:620px;
        box-shadow:0 10px 28px rgba(15,23,42,.06);
    }

    .pj-column-header{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        padding:14px 14px;
    }

    .pj-column-header-left{
        display:flex;
        align-items:center;
        gap:8px;
        min-width:0;
    }

    .pj-column-title{
        margin:0;
        font-size:15px;
        font-weight:800;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }

    .pj-column-count{
        font-size:14px;
        font-weight:700;
        opacity:.8;
    }

    .pj-column-header-actions{
        display:flex;
        align-items:center;
        gap:4px;
    }

    .pj-icon-btn{
        width:30px;
        height:30px;
        border:none;
        background:transparent;
        border-radius:9px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        color:inherit;
        cursor:pointer;
        transition:.2s ease;
    }

    .pj-icon-btn:hover{
        background:rgba(255,255,255,.28);
    }

    .pj-column-body{
        padding:10px;
        max-height:calc(100vh - 210px);
        overflow:auto;
        background:#fff;
    }

    .pj-column-body::-webkit-scrollbar{
        width:9px;
    }

    .pj-column-body::-webkit-scrollbar-thumb{
        background:#d0d7e3;
        border-radius:999px;
    }

    .pj-empty{
        padding:2px;
    }

    .pj-empty-box{
        border:1.5px dashed #d8dbe3;
        border-radius:14px;
        min-height:78px;
        display:flex;
        align-items:center;
        justify-content:center;
        color:#6d7588;
        font-size:15px;
        background:#fbfbfc;
    }

    .pj-cards{
        display:flex;
        flex-direction:column;
        gap:12px;
    }

    .pj-card{
        background:#fff;
        border:1px solid #dcdfe6;
        border-radius:18px;
        padding:14px;
        box-shadow:0 6px 16px rgba(15,23,42,.05);
        transition:.22s ease;
    }

    .pj-card:hover{
        transform:translateY(-2px);
        box-shadow:0 14px 28px rgba(15,23,42,.08);
    }

    .pj-card-top{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:12px;
    }

    .pj-card-main{
        display:flex;
        align-items:flex-start;
        gap:10px;
        min-width:0;
        flex:1;
    }

    .pj-check{
        position:relative;
        margin-top:2px;
    }

    .pj-check input{
        position:absolute;
        opacity:0;
        pointer-events:none;
    }

    .pj-check span{
        width:16px;
        height:16px;
        border-radius:5px;
        border:1.6px solid #e0e2e8;
        display:block;
        background:#fff;
    }

    .pj-dot{
        width:18px;
        height:18px;
        border-radius:999px;
        background:#2563eb;
        flex:0 0 18px;
        margin-top:1px;
    }

    .pj-card-title{
        font-size:15px;
        line-height:1.35;
        font-weight:800;
        color:#111827;
        word-break:break-word;
    }

    .pj-card-actions{
        display:flex;
        align-items:center;
        gap:4px;
        color:#737b8c;
    }

    .pj-divider{
        height:1px;
        background:#e6e8ee;
        margin:12px 0 14px;
    }

    .pj-card-meta{
        display:flex;
        flex-direction:column;
        gap:10px;
    }

    .pj-meta-row{
        display:flex;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
    }

    .pj-meta-label{
        color:#7a6270;
        font-size:14px;
    }

    .pj-meta-value{
        color:#111827;
        font-size:14px;
        font-weight:700;
    }

    .pj-avatar{
        width:30px;
        height:30px;
        border-radius:999px;
        background:#f0efef;
        color:#61646d;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:14px;
        font-weight:800;
    }

    .tone-blue .pj-column-collapsed-btn,
    .tone-blue .pj-column-header{
        background:#dfe7f7;
        color:#2358e6;
    }

    .tone-orange .pj-column-collapsed-btn,
    .tone-orange .pj-column-header{
        background:#f3e8de;
        color:#ef8c35;
    }

    .tone-green .pj-column-collapsed-btn,
    .tone-green .pj-column-header{
        background:#ddebe3;
        color:#22904a;
    }

    .tone-red .pj-column-collapsed-btn,
    .tone-red .pj-column-header{
        background:#f3e0e0;
        color:#eb4b4b;
    }

    .tone-purple .pj-column-collapsed-btn,
    .tone-purple .pj-column-header{
        background:#ece5f7;
        color:#8458e8;
    }

    .tone-gray .pj-column-collapsed-btn,
    .tone-gray .pj-column-header{
        background:#ececec;
        color:#5f6778;
    }

    .tone-rose .pj-column-collapsed-btn,
    .tone-rose .pj-column-header{
        background:#f2e5e5;
        color:#a22828;
    }

    .pj-modal-backdrop{
        position:fixed;
        inset:0;
        background:rgba(17,24,39,.55);
        display:none;
        align-items:center;
        justify-content:center;
        z-index:2000;
        padding:24px;
    }

    .pj-modal-backdrop.is-open{
        display:flex;
    }

    .pj-modal{
        width:min(1120px, 100%);
        max-height:92vh;
        background:#fff;
        border-radius:14px;
        box-shadow:0 30px 80px rgba(0,0,0,.32);
        display:flex;
        flex-direction:column;
        overflow:hidden;
        animation: pjModalIn .18s ease;
    }

    @keyframes pjModalIn{
        from{
            opacity:0;
            transform:translateY(8px) scale(.985);
        }
        to{
            opacity:1;
            transform:translateY(0) scale(1);
        }
    }

    .pj-modal-head{
        padding:28px 30px 18px;
        border-bottom:1px solid #eceef3;
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:16px;
    }

    .pj-modal-title{
        margin:0 0 8px;
        font-size:20px;
        font-weight:800;
        color:#20242d;
    }

    .pj-modal-subtitle{
        margin:0;
        font-size:16px;
        color:#6b7280;
    }

    .pj-modal-close{
        width:36px;
        height:36px;
        border:none;
        background:transparent;
        border-radius:10px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        color:#5f6674;
        transition:.18s ease;
        flex:0 0 36px;
    }

    .pj-modal-close:hover{
        background:#f3f4f6;
        color:#111827;
    }

    .pj-modal-close-icon{
        width:21px;
        height:21px;
    }

    .pj-modal-body{
        overflow:auto;
        padding:26px 30px 28px;
    }

    .pj-modal-body::-webkit-scrollbar{
        width:10px;
    }

    .pj-modal-body::-webkit-scrollbar-thumb{
        background:#9ca3af;
        border-radius:999px;
    }

    .pj-modal-form{
        display:flex;
        flex-direction:column;
        gap:22px;
    }

    .pj-modal-section{
        display:block;
    }

    .pj-input{
        width:100%;
        height:50px;
        border:1px solid #d8dde6;
        border-radius:10px;
        padding:0 16px;
        font-size:15px;
        color:#111827;
        background:#fff;
        outline:none;
        transition:.2s ease;
    }

    .pj-input:focus{
        border-color:#2a6df5;
        box-shadow:0 0 0 4px rgba(42,109,245,.09);
    }

    .pj-modal-grid{
        display:grid;
        grid-template-columns: 320px 200px 1fr;
        gap:18px;
        align-items:end;
    }

    .pj-modal-field{
        display:flex;
        flex-direction:column;
        gap:8px;
    }

    .pj-modal-label{
        font-size:14px;
        font-weight:700;
        color:#616977;
    }

    .pj-input-icon-wrap{
        position:relative;
    }

    .pj-input-icon-wrap .pj-input{
        padding-right:44px;
    }

    .pj-input-icon{
        width:18px;
        height:18px;
        position:absolute;
        right:14px;
        top:50%;
        transform:translateY(-50%);
        color:#111827;
        pointer-events:none;
    }

    .pj-color-row{
        height:50px;
        display:flex;
        align-items:center;
    }

    .pj-color-input{
        width:40px;
        height:40px;
        border:none;
        padding:0;
        background:transparent;
        cursor:pointer;
        border-radius:12px;
        overflow:hidden;
    }

    .pj-color-input::-webkit-color-swatch-wrapper{
        padding:0;
    }

    .pj-color-input::-webkit-color-swatch{
        border:none;
        border-radius:12px;
    }

    .pj-favorite-wrap{
        align-items:flex-end;
        justify-content:center;
        min-height:50px;
    }

    .pj-favorite-toggle{
        display:inline-flex;
        align-items:center;
        gap:10px;
        font-size:15px;
        color:#666;
        cursor:pointer;
        user-select:none;
    }

    .pj-favorite-toggle input{
        display:none;
    }

    .pj-favorite-box{
        width:22px;
        height:22px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        color:#7b8190;
    }

    .pj-favorite-star{
        width:22px;
        height:22px;
    }

    .pj-favorite-toggle input:checked + .pj-favorite-box{
        color:#f59e0b;
    }

    .pj-upload-box{
        position:relative;
        border:2px dashed #d6d9df;
        border-radius:14px;
        padding:32px 22px 24px;
        text-align:center;
        background:#fff;
        cursor:pointer;
        transition:.2s ease;
    }

    .pj-upload-box:hover{
        border-color:#9db8ff;
        background:#fbfdff;
    }

    .pj-upload-box.is-dragover{
        border-color:#2563eb;
        background:#f7faff;
        box-shadow:0 0 0 4px rgba(37,99,235,.06) inset;
    }

    .pj-file-input{
        position:absolute;
        inset:0;
        opacity:0;
        cursor:pointer;
    }

    .pj-upload-icon-wrap{
        display:flex;
        justify-content:center;
        margin-bottom:12px;
    }

    .pj-upload-icon{
        width:40px;
        height:40px;
        color:#3b82f6;
    }

    .pj-upload-title{
        font-size:18px;
        font-weight:800;
        color:#272b34;
        margin-bottom:8px;
    }

    .pj-upload-title span{
        color:#2563eb;
    }

    .pj-upload-subtitle{
        font-size:15px;
        color:#6b7280;
        margin-bottom:8px;
    }

    .pj-upload-note{
        font-size:14px;
        color:#737b8c;
    }

    .pj-selected-files{
        display:flex;
        flex-direction:column;
        gap:10px;
    }

    .pj-selected-title{
        font-size:15px;
        font-weight:800;
        color:#20242d;
    }

    .pj-selected-list{
        display:flex;
        flex-direction:column;
        gap:10px;
    }

    .pj-selected-empty{
        border:1px solid #e5e7eb;
        border-radius:10px;
        min-height:46px;
        display:flex;
        align-items:center;
        padding:0 14px;
        color:#8b93a4;
        background:#fafafa;
    }

    .pj-file-row{
        min-height:46px;
        border:1px solid #e5e7eb;
        border-radius:10px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        padding:0 14px;
        background:#fff;
    }

    .pj-file-name{
        color:#303540;
        font-size:14px;
        overflow:hidden;
        white-space:nowrap;
        text-overflow:ellipsis;
    }

    .pj-file-remove{
        border:none;
        background:transparent;
        color:#8b8b8b;
        font-size:14px;
        cursor:pointer;
        padding:0;
        flex:0 0 auto;
    }

    .pj-file-remove:hover{
        color:#dc2626;
    }

    .pj-create-no-docs{
        border-top:1px solid #eceef3;
        padding-top:16px;
    }

    .pj-checkbox-line{
        display:inline-flex;
        align-items:center;
        gap:10px;
        color:#676f7e;
        font-size:14px;
        cursor:pointer;
        user-select:none;
    }

    .pj-checkbox-line input{
        display:none;
    }

    .pj-checkbox-box{
        width:20px;
        height:20px;
        border:1.7px solid #aeb5c3;
        border-radius:4px;
        background:#fff;
        display:inline-block;
        position:relative;
        flex:0 0 20px;
    }

    .pj-checkbox-line input:checked + .pj-checkbox-box{
        background:#2563eb;
        border-color:#2563eb;
    }

    .pj-checkbox-line input:checked + .pj-checkbox-box::after{
        content:'';
        position:absolute;
        top:3px;
        left:7px;
        width:5px;
        height:10px;
        border:solid #fff;
        border-width:0 2px 2px 0;
        transform:rotate(45deg);
    }

    .pj-help-dot{
        width:18px;
        height:18px;
        border-radius:999px;
        border:1px solid #d1d5db;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        font-size:12px;
        color:#8b8f98;
    }

    .pj-modal-actions{
        display:flex;
        justify-content:flex-end;
        gap:12px;
        padding-top:4px;
    }

    body.pj-modal-open{
        overflow:hidden;
    }

    @media (max-width: 1200px){
        .pj-list-wrap{
            overflow:auto;
        }

        .pj-list-head,
        .pj-group-row,
        .pj-item-row{
            min-width:1200px;
        }
    }

    @media (max-width: 991px){
        .pj-toolbar{
            align-items:flex-start;
            flex-direction:column;
        }

        .pj-search-box{
            width:100%;
        }

        .pj-search-wrap{
            width:100%;
        }

        .pj-column.is-open{
            width:330px;
        }

        .pj-column-open{
            min-height:560px;
        }

        .pj-filter-pop,
        .pj-sort-pop{
            right:auto;
            left:0;
        }

        .pj-modal{
            width:min(100%, 100%);
        }

        .pj-modal-grid{
            grid-template-columns:1fr;
        }

        .pj-favorite-wrap{
            justify-content:flex-start;
            align-items:flex-start;
        }
    }

    @media (max-width: 640px){
        .pj-page{
            padding:10px 10px 16px;
        }

        .pj-toolbar{
            margin:-10px -10px 12px;
            padding:10px;
        }

        .pj-toolbar-left,
        .pj-toolbar-right,
        .pj-search-wrap{
            width:100%;
        }

        .pj-btn-create,
        .pj-btn,
        .pj-search-box{
            width:100%;
        }

        .pj-btn-icon-only{
            width:42px !important;
            min-width:42px !important;
        }

        .pj-filter-pop{
            width:min(400px, calc(100vw - 20px));
        }

        .pj-sort-pop{
            width:min(356px, calc(100vw - 20px));
        }

        .pj-column.is-open{
            width:308px;
        }

        .pj-column.is-collapsed{
            width:48px;
        }

        .pj-column-collapsed-btn{
            width:48px;
            min-height:240px;
            border-radius:18px;
        }

        .pj-date-grid{
            grid-template-columns:1fr;
        }

        .pj-modal-backdrop{
            padding:12px;
        }

        .pj-modal-head{
            padding:20px 18px 14px;
        }

        .pj-modal-body{
            padding:18px;
        }

        .pj-upload-box{
            padding:22px 14px 18px;
        }

        .pj-modal-actions{
            flex-direction:column-reverse;
        }

        .pj-modal-actions .pj-btn{
            width:100%;
            justify-content:center;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const board = document.getElementById('projectBoard');

        if (board) {
            board.addEventListener('click', function (e) {
                const openBtn = e.target.closest('.js-open-column');
                if (openBtn) {
                    const id = openBtn.getAttribute('data-id');

                    document.querySelectorAll('.pj-column').forEach(col => {
                        const colId = col.getAttribute('data-column-id');
                        if (colId === id) {
                            col.classList.remove('is-collapsed');
                            col.classList.add('is-open');
                        } else {
                            col.classList.remove('is-open');
                            col.classList.add('is-collapsed');
                        }
                    });

                    const url = new URL(window.location.href);
                    url.searchParams.set('open', id);
                    history.replaceState({}, '', url.toString());
                }

                const closeBtn = e.target.closest('.js-close-column');
                if (closeBtn) {
                    const currentColumn = closeBtn.closest('.pj-column');
                    if (currentColumn) {
                        currentColumn.classList.remove('is-open');
                        currentColumn.classList.add('is-collapsed');
                    }

                    const url = new URL(window.location.href);
                    url.searchParams.delete('open');
                    history.replaceState({}, '', url.toString());
                }
            });
        }

        document.querySelectorAll('.js-toggle-group').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                const group = document.querySelector(`.pj-group[data-group-id="${id}"]`);
                const children = group ? group.querySelector('.pj-group-children') : null;

                if (!children) return;

                const currentlyOpen = children.classList.contains('is-open');

                document.querySelectorAll('.pj-group-children').forEach(el => el.classList.remove('is-open'));
                document.querySelectorAll('.js-toggle-group').forEach(el => {
                    el.setAttribute('aria-expanded', 'false');
                    const path = el.querySelector('path');
                    if (path) path.setAttribute('d', 'M10 8l4 4-4 4');
                });

                if (!currentlyOpen) {
                    children.classList.add('is-open');
                    this.setAttribute('aria-expanded', 'true');
                    const path = this.querySelector('path');
                    if (path) path.setAttribute('d', 'M8 15l4-4 4 4');
                }

                const url = new URL(window.location.href);
                if (!currentlyOpen) {
                    url.searchParams.set('open', id);
                } else {
                    url.searchParams.delete('open');
                }
                history.replaceState({}, '', url.toString());
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
        const modal = document.getElementById('projectModal');
        const openProjectModal = document.getElementById('openProjectModal');
        const closeProjectModal = document.getElementById('closeProjectModal');
        const cancelProjectModal = document.getElementById('cancelProjectModal');
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

        function openModal() {
            if (!modalBackdrop) return;
            modalBackdrop.classList.add('is-open');
            document.body.classList.add('pj-modal-open');
        }

        function closeModal() {
            if (!modalBackdrop) return;
            modalBackdrop.classList.remove('is-open');
            document.body.classList.remove('pj-modal-open');
        }

        if (openProjectModal) {
            openProjectModal.addEventListener('click', function () {
                openModal();
            });
        }

        if (closeProjectModal) {
            closeProjectModal.addEventListener('click', function () {
                closeModal();
            });
        }

        if (cancelProjectModal) {
            cancelProjectModal.addEventListener('click', function () {
                closeModal();
            });
        }

        if (modalBackdrop) {
            modalBackdrop.addEventListener('click', function (e) {
                if (e.target === modalBackdrop) {
                    closeModal();
                }
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modalBackdrop && modalBackdrop.classList.contains('is-open')) {
                closeModal();
            }
        });

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
    });
</script>
@endsection