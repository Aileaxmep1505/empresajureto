{{-- resources/views/projects/partials/control-sidebar.blade.php --}}
@once
<style>
  /* Importamos una fuente gruesa y geométrica solo para el logo */
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@800;900&display=swap');

  :root {
    --cc-global-header-height: 64px;
    --cc-sidebar-w: 64px;
    --cc-sidebar-bg: #ffffff;
    --cc-sidebar-line: #e5e7eb;
    --cc-sidebar-ink: #374151;
    --cc-sidebar-ink-muted: #6b7280;
    --cc-sidebar-blue: #007aff;
    --cc-sidebar-blue-soft: #e6f0ff;
    --cc-sidebar-orange: #f97316;
    --cc-sidebar-hover: #f3f4f6;
    --cc-sidebar-ease: cubic-bezier(0.23, 1, 0.32, 1);
  }

  .cc-side-nav {
    position: fixed;
    top: var(--cc-global-header-height);
    left: 0;
    z-index: 70;
    width: var(--cc-sidebar-w);
    height: calc(100vh - var(--cc-global-header-height));
    background: var(--cc-sidebar-bg);
    border-right: 1px solid var(--cc-sidebar-line);
    display: flex;
    flex-direction: column;
    padding: 12px 0;
    box-shadow: 4px 0 18px rgba(15, 23, 42, .03);
    overflow-x: hidden;
    overflow-y: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    overscroll-behavior: contain;
    font-family: 'Quicksand', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    transition: width 250ms var(--cc-sidebar-ease);
  }

  .cc-side-nav::-webkit-scrollbar {
    width: 0 !important;
    height: 0 !important;
    display: none !important;
  }

  .cc-side-nav::before,
  .cc-side-nav::after {
    content: "";
    position: sticky;
    left: 0;
    right: 0;
    z-index: 2;
    height: 12px;
    flex: 0 0 12px;
    pointer-events: none;
  }

  .cc-side-nav::before {
    top: 0;
    margin-top: -12px;
    background: linear-gradient(180deg, rgba(255,255,255,.96), rgba(255,255,255,0));
  }

  .cc-side-nav::after {
    bottom: 0;
    margin-bottom: -12px;
    background: linear-gradient(0deg, rgba(255,255,255,.96), rgba(255,255,255,0));
  }

  .cc-sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 14px;
    margin-bottom: 16px;
    height: 32px;
  }

  .cc-logo-text {
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    font-size: 1.8rem;
    font-weight: 900;
    color: #171717;
    letter-spacing: -1.5px;
    margin: 0;
    line-height: 1;
    padding-bottom: 2px;
    transition: opacity 200ms var(--cc-sidebar-ease);
  }

  .cc-side-nav__toggle {
    width: 32px;
    height: 32px;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: var(--cc-sidebar-ink-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 180ms var(--cc-sidebar-ease), color 180ms var(--cc-sidebar-ease), transform 120ms var(--cc-sidebar-ease);
  }

  .cc-side-nav__toggle:active { transform: scale(.97); }
  .cc-side-nav__toggle:hover { background: var(--cc-sidebar-hover); color: var(--cc-sidebar-ink); }
  .cc-side-nav__toggle svg { width: 20px; height: 20px; flex-shrink: 0; }
  .icon-expand { display: none; }
  .icon-collapse { display: block; }

  .cc-side-nav__group {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 0 10px;
  }

  .cc-side-nav__separator {
    width: calc(100% - 32px);
    height: 1px;
    background: var(--cc-sidebar-line);
    margin: 12px 16px;
    transition: width 250ms var(--cc-sidebar-ease), margin 250ms var(--cc-sidebar-ease);
  }

  .cc-side-nav__link,
  .cc-folder__header,
  .cc-tree__link,
  .cc-fav__link {
    width: 100%;
    min-height: 34px;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: var(--cc-sidebar-ink);
    display: flex;
    align-items: center;
    padding: 0 12px;
    text-decoration: none;
    cursor: pointer;
    font-size: .875rem;
    font-weight: 600;
    position: relative;
    transition: background 180ms var(--cc-sidebar-ease), color 180ms var(--cc-sidebar-ease), transform 120ms var(--cc-sidebar-ease);
  }

  .cc-side-nav__link:active,
  .cc-folder__header:active,
  .cc-tree__link:active,
  .cc-fav__link:active { transform: scale(.98); }

  .cc-side-nav__link svg,
  .cc-folder__header svg:first-child,
  .cc-tree__link svg {
    width: 18px;
    height: 18px;
    margin-right: 12px;
    flex-shrink: 0;
    transition: margin 250ms var(--cc-sidebar-ease);
  }

  .cc-side-nav__link.is-active-gray {
    background: var(--cc-sidebar-hover);
    color: #111827;
    font-weight: 700;
  }

  .cc-tree__link.is-active-blue {
    background: var(--cc-sidebar-blue);
    color: #ffffff;
  }

  .cc-side-nav__section-title {
    font-size: .74rem;
    font-weight: 700;
    color: var(--cc-sidebar-ink-muted);
    padding: 0 12px;
    margin: 6px 0;
    white-space: nowrap;
    opacity: 1;
    text-transform: uppercase;
    letter-spacing: .05em;
    transition: opacity 200ms var(--cc-sidebar-ease);
  }

  .cc-folder__header { color: var(--cc-sidebar-ink); font-weight: 700; text-align: left; }
  .cc-folder__header svg:first-child { color: var(--cc-sidebar-orange); }
  
  /* Animación del chevron (flecha) del folder */
  .cc-folder__chevron { 
    width: 14px; 
    height: 14px; 
    margin-left: auto; 
    color: var(--cc-sidebar-ink-muted); 
    transition: transform 200ms var(--cc-sidebar-ease);
  }
  .cc-folder.is-closed .cc-folder__chevron {
    transform: rotate(-90deg); /* La flecha apunta a la derecha cuando se cierra */
  }

  /* Animación del despliegue del árbol del folder */
  .cc-folder__tree-wrapper {
    display: grid;
    grid-template-rows: 1fr;
    transition: grid-template-rows 250ms var(--cc-sidebar-ease);
  }
  .cc-folder.is-closed .cc-folder__tree-wrapper {
    grid-template-rows: 0fr;
  }
  .cc-folder__tree-wrapper-inner {
    overflow: hidden;
  }

  .cc-folder__tree {
    display: flex;
    flex-direction: column;
    margin-left: 20px;
    padding-left: 10px;
    border-left: 1px solid var(--cc-sidebar-line);
    gap: 2px;
    margin-top: 2px;
    transition: margin 250ms var(--cc-sidebar-ease), padding 250ms var(--cc-sidebar-ease), border-color 250ms var(--cc-sidebar-ease);
  }

  .cc-tree__link { font-size: .8125rem; min-height: 32px; padding: 0 10px; }
  .cc-tree__link svg { width: 16px; height: 16px; margin-right: 10px; }

  .cc-fav-list { display: flex; flex-direction: column; gap: 2px; list-style: none; margin: 0; padding: 0; }
  .cc-fav__link { font-size: .8125rem; font-weight: 600; min-height: 32px; padding: 0 12px; }
  .cc-fav__dot { width: 8px; height: 8px; border-radius: 50%; margin-right: 12px; flex-shrink: 0; transition: margin 250ms var(--cc-sidebar-ease); }

  .cc-truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; opacity: 1; transition: opacity 200ms var(--cc-sidebar-ease); }

  .cc-side-nav__tooltip { display: none !important; }

  .cc-floating-tooltip {
    position: fixed;
    z-index: 9999;
    left: 76px;
    top: 0;
    max-width: min(280px, calc(100vw - 96px));
    padding: 8px 11px;
    border-radius: 10px;
    background: #ffffff;
    color: #111111;
    border: 1px solid #ebebeb;
    font-size: .78rem;
    font-weight: 700;
    line-height: 1.15;
    white-space: nowrap;
    box-shadow: 0 12px 30px rgba(15, 23, 42, .12);
    opacity: 0;
    pointer-events: none;
    transform: translateY(-50%) translateX(-5px);
    transition: opacity 150ms var(--cc-sidebar-ease), transform 150ms var(--cc-sidebar-ease);
  }

  .cc-floating-tooltip.is-visible {
    opacity: 1;
    transform: translateY(-50%) translateX(0);
  }

  .cc-floating-tooltip::before {
    content: "";
    position: absolute;
    left: -5px;
    top: 50%;
    width: 10px;
    height: 10px;
    background: #ffffff;
    border-left: 1px solid #ebebeb;
    border-bottom: 1px solid #ebebeb;
    transform: translateY(-50%) rotate(45deg);
  }

  .cc-empty-mini { padding: 8px 12px; color: var(--cc-sidebar-ink-muted); font-size: .8rem; font-weight: 600; }

  .cc-side-nav.is-collapsed { width: 64px; align-items: center; }
  .cc-side-nav.is-collapsed .cc-sidebar-header { justify-content: center; padding: 0; width: 100%; }
  .cc-side-nav.is-collapsed .cc-logo-text { display: none; }
  .cc-side-nav.is-collapsed .icon-collapse { display: none; }
  .cc-side-nav.is-collapsed .icon-expand { display: block; }
  .cc-side-nav.is-collapsed .cc-side-nav__group { padding: 0; align-items: center; }
  .cc-side-nav.is-collapsed .cc-truncate,
  .cc-side-nav.is-collapsed .cc-side-nav__section-title,
  .cc-side-nav.is-collapsed .cc-folder__chevron { opacity: 0; width: 0; display: none; }
  .cc-side-nav.is-collapsed .cc-side-nav__link,
  .cc-side-nav.is-collapsed .cc-folder__header,
  .cc-side-nav.is-collapsed .cc-tree__link,
  .cc-side-nav.is-collapsed .cc-fav__link { width: 36px; height: 36px; padding: 0; justify-content: center; margin: 0 auto; }
  .cc-side-nav.is-collapsed .cc-side-nav__separator { width: 24px; margin: 10px 0; }
  .cc-side-nav.is-collapsed svg,
  .cc-side-nav.is-collapsed .cc-fav__dot { margin-right: 0 !important; }
  .cc-side-nav.is-collapsed .cc-folder__tree { margin-left: 0; padding-left: 0; border-left: none; }
  .cc-side-nav.is-collapsed .cc-side-nav__tooltip { display: block; }

  @media (hover:hover) and (pointer:fine) {
    .cc-side-nav__link:hover:not(.is-active-gray),
    .cc-folder__header:hover,
    .cc-tree__link:hover:not(.is-active-blue),
    .cc-fav__link:hover { background: var(--cc-sidebar-hover); }

    .cc-side-nav.is-collapsed .cc-side-nav__toggle:hover .cc-side-nav__tooltip,
    .cc-side-nav.is-collapsed .cc-side-nav__link:hover .cc-side-nav__tooltip,
    .cc-side-nav.is-collapsed .cc-folder__header:hover .cc-side-nav__tooltip,
    .cc-side-nav.is-collapsed .cc-tree__link:hover .cc-side-nav__tooltip,
    .cc-side-nav.is-collapsed .cc-fav__link:hover .cc-side-nav__tooltip {
      opacity: 1;
      transform: translateY(-50%) translateX(0);
    }
  }

  .pj-page,
  .jo-page { margin-left: var(--cc-sidebar-w) !important; width: calc(100% - var(--cc-sidebar-w)) !important; transition: margin 250ms var(--cc-sidebar-ease), width 250ms var(--cc-sidebar-ease); }
  .jo-page { padding-left: 24px !important; }
  .cc-page.has-control-sidebar { padding-left: calc(var(--cc-sidebar-w) + 24px) !important; transition: padding 250ms var(--cc-sidebar-ease); }
  .pjd-wrap.has-control-sidebar { padding-left: var(--cc-sidebar-w) !important; transition: padding 250ms var(--cc-sidebar-ease); }

  @media (max-width: 900px) {
    .cc-side-nav { display: none; }
    .pj-page, .jo-page { margin-left: 0 !important; width: 100% !important; }
    .jo-page { padding-left: 18px !important; }
    .cc-page.has-control-sidebar, .pjd-wrap.has-control-sidebar { padding-left: 0 !important; }
  }

  @media (prefers-reduced-motion: reduce) {
    .cc-side-nav,
    .cc-side-nav *,
    .pj-page,
    .jo-page,
    .cc-page.has-control-sidebar,
    .pjd-wrap.has-control-sidebar { transition-duration: 0ms !important; }
  }
</style>
@endonce

@php
    use App\Models\Project;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Schema;
    use Illuminate\Support\Str;

    $ccUser = Auth::user();
    $ccUserId = Auth::id();
    $ccCurrentProject = ($project ?? null) instanceof Project ? $project : null;

    $ccProjectBaseQuery = Project::query()->where('user_id', $ccUserId);
    if (Schema::hasColumn('projects', 'archived_at')) {
        $ccProjectBaseQuery->whereNull('archived_at');
    }

    if (!$ccCurrentProject) {
        $ccCurrentProject = (clone $ccProjectBaseQuery)
            ->when(Schema::hasColumn('projects', 'favorite'), fn ($q) => $q->orderByDesc('favorite'))
            ->latest('updated_at')
            ->first();
    }

    $ccProjectRouteParam = $ccCurrentProject ?: null;
    $ccSafeRoute = function (string $name, array $params = []) {
        return Route::has($name) ? route($name, $params) : '#';
    };

    $ccProjectUrl = function (string $routeName, ?string $hash = null) use ($ccSafeRoute, $ccProjectRouteParam) {
        if (!$ccProjectRouteParam) return '#';
        $url = $ccSafeRoute($routeName, ['project' => $ccProjectRouteParam]);
        return $hash && $url !== '#' ? $url . $hash : $url;
    };

    $ccProjectLabel = $ccCurrentProject?->name ?: 'Selecciona un proyecto';
    $ccProjectTooltip = $ccCurrentProject?->name ?: 'No hay proyecto activo';

    $ccFavoriteProjects = collect();
    if (Schema::hasColumn('projects', 'favorite')) {
        $ccFavoriteProjects = (clone $ccProjectBaseQuery)
            ->where('favorite', true)
            ->latest('updated_at')
            ->limit(5)
            ->get();
    }

    $ccRecentProjects = (clone $ccProjectBaseQuery)
        ->latest('updated_at')
        ->limit(5)
        ->get();

    $ccLabelColors = ['#2563eb', '#fb923c', '#22c55e', '#8b5cf6', '#ef4444', '#06b6d4', '#14b8a6'];
    $ccLabelMap = collect();

    if (Schema::hasColumn('projects', 'labels')) {
        (clone $ccProjectBaseQuery)->select('id', 'labels')->get()->each(function ($item) use (&$ccLabelMap) {
            $labels = $item->labels ?? [];
            if (is_string($labels)) {
                $decoded = json_decode($labels, true);
                $labels = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
            }
            if (!is_array($labels)) $labels = [];

            foreach ($labels as $label) {
                $label = trim((string) $label);
                if ($label === '') continue;
                $key = Str::lower($label);
                $current = $ccLabelMap->get($key, ['label' => $label, 'count' => 0]);
                $current['count']++;
                $ccLabelMap->put($key, $current);
            }
        });
    }

    $ccRealLabels = $ccLabelMap
        ->sortByDesc('count')
        ->take(5)
        ->values()
        ->map(function ($item, $index) use ($ccLabelColors, $ccSafeRoute) {
            return [
                'label' => $item['label'],
                'count' => $item['count'],
                'color' => $ccLabelColors[$index % count($ccLabelColors)],
                'route' => $ccSafeRoute('projects.index', ['label' => $item['label']]),
            ];
        });

    $ccFavorites = $ccFavoriteProjects->map(function ($fav, $index) use ($ccLabelColors, $ccSafeRoute) {
        return [
            'label' => $fav->name,
            'color' => $fav->color ?: $ccLabelColors[$index % count($ccLabelColors)],
            'route' => $ccSafeRoute('projects.show', ['project' => $fav]),
        ];
    });

    if ($ccFavorites->isEmpty()) {
        $ccFavorites = $ccRealLabels->map(fn ($label) => [
            'label' => $label['label'],
            'color' => $label['color'],
            'route' => $label['route'],
        ]);
    }

    $ccNavItems = [
        ['label' => 'Centro de Control', 'route' => $ccSafeRoute('projects.control'), 'active' => request()->routeIs('projects.control'), 'icon' => 'home'],
        ['label' => 'Proyectos', 'route' => $ccSafeRoute('projects.index'), 'active' => request()->routeIs('projects.index'), 'icon' => 'projects'],
        ['label' => 'Buscador', 'route' => $ccSafeRoute('projects.search'), 'active' => request()->routeIs('projects.search'), 'icon' => 'search'],
    ];

    $ccWorkingItems = [
        ['label' => 'Dashboard', 'route' => $ccProjectUrl('projects.show'), 'active' => request()->routeIs('projects.show'), 'icon' => 'layout'],
        ['label' => 'Análisis de Bases', 'route' => $ccProjectUrl('projects.analisis'), 'active' => request()->routeIs('projects.analisis') && !request()->has('tab'), 'icon' => 'file_search'],
        ['label' => 'Checklist', 'route' => $ccProjectUrl('projects.analisis', '#checklist'), 'active' => Str::contains(request()->fullUrl(), '#checklist'), 'icon' => 'clipboard'],
        ['label' => 'Armado de Propuesta', 'route' => $ccProjectUrl('projects.analisis', '#armado-propuesta'), 'active' => false, 'icon' => 'file_text'],
        ['label' => 'Documentos', 'route' => $ccProjectUrl('projects.analisis', '#documentos'), 'active' => false, 'icon' => 'folder'],
        ['label' => 'Reportes', 'route' => $ccProjectUrl('projects.reports'), 'active' => request()->routeIs('projects.reports'), 'icon' => 'briefcase'],
    ];

    $ccIcon = function (string $icon): string {
        return match ($icon) {
            'collapse_left' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke="currentColor" fill="none" stroke-width="2"/><line x1="9" y1="3" x2="9" y2="21" stroke="currentColor" stroke-width="2"/><polyline points="16 15 13 12 16 9" stroke="currentColor" stroke-width="2" fill="none"/>',
            'expand_right' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke="currentColor" fill="none" stroke-width="2"/><line x1="15" y1="3" x2="15" y2="21" stroke="currentColor" stroke-width="2"/><polyline points="8 9 11 12 8 15" stroke="currentColor" stroke-width="2" fill="none"/>',
            'home' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline>',
            'projects' => '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path><line x1="9" y1="14" x2="9" y2="14"></line><line x1="13" y1="14" x2="13" y2="14"></line><line x1="17" y1="14" x2="17" y2="14"></line>',
            'search' => '<circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>',
            'folder' => '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>',
            'file_search' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><circle cx="10" cy="13" r="2"></circle><line x1="11.41" y1="14.41" x2="13.5" y2="16.5"></line>',
            'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
            'file_text' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>',
            'layout' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line>',
            'clipboard' => '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect><line x1="9" y1="14" x2="15" y2="14"></line><line x1="9" y1="18" x2="15" y2="18"></line><line x1="9" y1="10" x2="10" y2="10"></line>',
            'briefcase' => '<rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>',
            default => '<circle cx="12" cy="12" r="9"/>',
        };
    };
@endphp

<aside id="ccSidebar" class="cc-side-nav is-collapsed" aria-label="Navegación del proyecto">
    <div class="cc-sidebar-header">
        <h1 class="cc-logo-text">sam</h1>
        <button type="button" class="cc-side-nav__toggle" data-cc-sidebar-toggle aria-label="Alternar menú">
            <svg class="icon-collapse" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">{!! $ccIcon('collapse_left') !!}</svg>
            <svg class="icon-expand" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">{!! $ccIcon('expand_right') !!}</svg>
            <span class="cc-side-nav__tooltip">Contraer menú</span>
        </button>
    </div>

    <nav class="cc-side-nav__group">
        @foreach($ccNavItems as $item)
            <a href="{{ $item['route'] }}" class="cc-side-nav__link {{ $item['active'] ? 'is-active-gray' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $ccIcon($item['icon']) !!}</svg>
                <span class="cc-truncate">{{ $item['label'] }}</span>
                <span class="cc-side-nav__tooltip">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="cc-side-nav__separator"></div>

    <div class="cc-side-nav__group">
        <h3 class="cc-side-nav__section-title">Trabajando en:</h3>
        
        <div class="cc-folder is-closed" id="workingFolder">
            <button type="button" class="cc-folder__header" onclick="document.getElementById('workingFolder').classList.toggle('is-closed')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $ccIcon('folder') !!}</svg>
                <span class="cc-truncate">{{ Str::limit($ccProjectLabel, 21) }}</span>
                <svg class="cc-folder__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                <span class="cc-side-nav__tooltip">{{ $ccProjectTooltip }}</span>
            </button>

            <div class="cc-folder__tree-wrapper">
                <div class="cc-folder__tree-wrapper-inner">
                    <div class="cc-folder__tree">
                        @foreach($ccWorkingItems as $subItem)
                            <a href="{{ $subItem['route'] }}" class="cc-tree__link {{ $subItem['active'] ? 'is-active-blue' : '' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $ccIcon($subItem['icon']) !!}</svg>
                                <span class="cc-truncate">{{ $subItem['label'] }}</span>
                                <span class="cc-side-nav__tooltip">{{ $subItem['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="cc-side-nav__separator"></div>

    <div class="cc-side-nav__group">
        <h3 class="cc-side-nav__section-title">Favoritos</h3>
        <ul class="cc-fav-list">
            @forelse($ccFavorites as $fav)
                <li>
                    <a href="{{ $fav['route'] }}" class="cc-fav__link">
                        <span class="cc-fav__dot" style="background-color: {{ $fav['color'] }}"></span>
                        <span class="cc-truncate">{{ Str::limit($fav['label'], 24) }}</span>
                        <span class="cc-side-nav__tooltip">{{ $fav['label'] }}</span>
                    </a>
                </li>
            @empty
                <li class="cc-empty-mini">Sin favoritos</li>
            @endforelse
        </ul>
    </div>

    <div class="cc-side-nav__separator"></div>

    <div class="cc-side-nav__group">
        <h3 class="cc-side-nav__section-title">Etiquetas</h3>
        <ul class="cc-fav-list">
            @forelse($ccRealLabels as $label)
                <li>
                    <a href="{{ $label['route'] }}" class="cc-fav__link">
                        <span class="cc-fav__dot" style="background-color: {{ $label['color'] }}"></span>
                        <span class="cc-truncate">{{ Str::limit($label['label'], 20) }}</span>
                        <span class="cc-side-nav__tooltip">{{ $label['label'] }} · {{ $label['count'] }} proyecto(s)</span>
                    </a>
                </li>
            @empty
                <li class="cc-empty-mini">Sin etiquetas creadas</li>
            @endforelse
        </ul>
    </div>
</aside>

@once
<script>
  /* cc-sidebar-force-collapsed-on-load: siempre inicia contraido al entrar a cualquier Blade */
  (function () {
    const sidebar = document.getElementById('ccSidebar');
    if (!sidebar) return;
    sidebar.classList.add('is-collapsed');
    document.documentElement.style.setProperty('--cc-sidebar-w', '64px');
  })();

  document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-cc-sidebar-toggle]');
    if (!button) return;

    const sidebar = document.getElementById('ccSidebar');
    if (!sidebar) return;

    sidebar.classList.toggle('is-collapsed');
    document.documentElement.style.setProperty('--cc-sidebar-w', sidebar.classList.contains('is-collapsed') ? '64px' : '260px');
    window.dispatchEvent(new Event('cc-sidebar-toggle'));
  });

  (function () {
    let tooltip = document.querySelector('.cc-floating-tooltip');

    function ensureTooltip() {
      if (tooltip) return tooltip;
      tooltip = document.createElement('div');
      tooltip.className = 'cc-floating-tooltip';
      tooltip.setAttribute('role', 'tooltip');
      document.body.appendChild(tooltip);
      return tooltip;
    }

    function getTooltipText(target) {
      return target?.querySelector('.cc-side-nav__tooltip')?.textContent?.trim() || target?.getAttribute('aria-label') || '';
    }

    function hideTooltip() {
      if (!tooltip) return;
      tooltip.classList.remove('is-visible');
    }

    function showTooltip(target) {
      const sidebar = document.getElementById('ccSidebar');
      if (!sidebar || !sidebar.classList.contains('is-collapsed')) {
        hideTooltip();
        return;
      }

      const text = getTooltipText(target);
      if (!text) return;

      const tip = ensureTooltip();
      const rect = target.getBoundingClientRect();
      const sidebarRect = sidebar.getBoundingClientRect();
      const top = rect.top + (rect.height / 2);
      const left = sidebarRect.right + 12;

      tip.textContent = text;
      tip.style.left = `${left}px`;
      tip.style.top = `${Math.min(Math.max(top, 22), window.innerHeight - 22)}px`;
      tip.classList.add('is-visible');
    }

    document.addEventListener('mouseover', function (event) {
      const target = event.target.closest('.cc-side-nav__toggle, .cc-side-nav__link, .cc-folder__header, .cc-tree__link, .cc-fav__link');
      if (!target) return;
      showTooltip(target);
    });

    document.addEventListener('focusin', function (event) {
      const target = event.target.closest('.cc-side-nav__toggle, .cc-side-nav__link, .cc-folder__header, .cc-tree__link, .cc-fav__link');
      if (!target) return;
      showTooltip(target);
    });

    document.addEventListener('mouseout', function (event) {
      if (event.target.closest('.cc-side-nav__toggle, .cc-side-nav__link, .cc-folder__header, .cc-tree__link, .cc-fav__link')) {
        hideTooltip();
      }
    });

    document.addEventListener('focusout', hideTooltip);
    window.addEventListener('resize', hideTooltip);
    window.addEventListener('scroll', hideTooltip, true);
    window.addEventListener('cc-sidebar-toggle', hideTooltip);
  })();
</script>
@endonce