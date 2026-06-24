{{-- resources/views/projects/partials/control-sidebar.blade.php --}}
@php
    use Illuminate\Support\Facades\Route;

    $ccProject = $project ?? null;

    $ccReportsRoute = '#';
    if ($ccProject && Route::has('projects.reports')) {
        $ccReportsRoute = route('projects.reports', $ccProject);
    } elseif (Route::has('projects.index')) {
        $ccReportsRoute = route('projects.index', ['view' => 'reports']);
    }

    $ccNavItems = [
        [
            'label' => 'Centro de Control',
            'route' => Route::has('projects.control') ? route('projects.control') : '#',
            'active' => request()->routeIs('projects.control'),
            'icon' => 'home',
        ],
        [
            'label' => 'Calendario',
            'route' => '#',
            'active' => false,
            'icon' => 'calendar',
        ],
        [
            'label' => 'Proyectos',
            'route' => Route::has('projects.index') ? route('projects.index') : '#',
            'active' => request()->routeIs('projects.index') || request()->routeIs('projects.show') || request()->routeIs('projects.analisis'),
            'icon' => 'folder',
        ],
        [
            'label' => 'Buscar',
            'route' => Route::has('projects.index') ? route('projects.index', ['q' => request('q')]) : '#',
            'active' => false,
            'icon' => 'search',
        ],
    ];

    $ccNavExtraItems = [
        [
            'label' => 'Reportes',
            'route' => $ccReportsRoute,
            'active' => request()->routeIs('projects.reports') || request('view') === 'reports',
            'icon' => 'briefcase',
            'tone' => 'orange',
        ],
        [
            'label' => 'Favoritos',
            'route' => Route::has('projects.index') ? route('projects.index', ['favorite' => 1]) : '#',
            'active' => request('favorite') === '1',
            'icon' => 'star',
        ],
    ];

    $ccIcon = function (string $icon): string {
        return match ($icon) {
            'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/>',
            'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>',
            'folder' => '<path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H9l2 2h7.5A2.5 2.5 0 0 1 21 9.5v7A2.5 2.5 0 0 1 18.5 19h-13A2.5 2.5 0 0 1 3 16.5z"/><path d="M8 11v4M12 10v5M16 12v3"/>',
            'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
            'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M8 12v3M12 11v4M16 12v3"/>',
            'star' => '<path d="m12 3 2.7 5.47 6.03.88-4.36 4.25 1.03 6-5.4-2.84-5.4 2.84 1.03-6-4.36-4.25 6.03-.88z"/>',
            default => '<circle cx="12" cy="12" r="9"/>',
        };
    };
@endphp

<aside class="cc-side-nav" aria-label="Navegación del proyecto">
    <div class="cc-side-nav__group cc-side-nav__top">
        <button type="button" class="cc-side-nav__toggle" aria-label="Contraer menú">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 6h8v12H9z"/>
                <path d="m6 8 4 4-4 4"/>
            </svg>
            <span class="cc-side-nav__tooltip">Contraer</span>
        </button>
    </div>

    <nav class="cc-side-nav__group cc-side-nav__main">
        @foreach($ccNavItems as $item)
            <a href="{{ $item['route'] }}" class="cc-side-nav__link {{ $item['active'] ? 'is-active' : '' }}" aria-label="{{ $item['label'] }}" title="{{ $item['label'] }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                    {!! $ccIcon($item['icon']) !!}
                </svg>
                <span class="cc-side-nav__tooltip">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="cc-side-nav__separator"></div>

    <nav class="cc-side-nav__group">
        @foreach($ccNavExtraItems as $item)
            <a href="{{ $item['route'] }}" class="cc-side-nav__link {{ ($item['tone'] ?? '') === 'orange' ? 'is-orange' : '' }} {{ $item['active'] ? 'is-active' : '' }}" aria-label="{{ $item['label'] }}" title="{{ $item['label'] }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                    {!! $ccIcon($item['icon']) !!}
                </svg>
                <span class="cc-side-nav__tooltip">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
</aside>
