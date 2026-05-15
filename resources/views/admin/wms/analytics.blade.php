@extends('layouts.app')

@section('title', 'WMS · Dashboard')

@section('content')
@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Collection;
    use Illuminate\Support\Facades\Route;

    $period = (int) ($period ?? request('period', 7) ?? 7);
    if (!in_array($period, [7, 30, 90], true)) {
        $period = 7;
    }

    $routeFirst = function (array $names, $fallback = '#') {
        foreach ($names as $name) {
            if (Route::has($name)) {
                return route($name);
            }
        }

        return $fallback;
    };

    $toArrayList = function ($value): array {
        if ($value instanceof Collection) {
            return $value->values()->all();
        }

        if (is_array($value)) {
            return array_values($value);
        }

        if (is_iterable($value)) {
            return collect($value)->values()->all();
        }

        return [];
    };

    $toInt = function ($value, int $default = 0): int {
        if (is_numeric($value)) {
            return (int) $value;
        }

        if (is_string($value)) {
            $clean = preg_replace('/[^\d\-]/', '', $value);

            return is_numeric($clean) ? (int) $clean : $default;
        }

        return $default;
    };

    $pickFirstNumeric = function (array $candidates, int $default = 0) use ($toInt): int {
        foreach ($candidates as $candidate) {
            if ($candidate !== null && $candidate !== '') {
                if (is_numeric($candidate)) {
                    return (int) $candidate;
                }

                if (is_string($candidate) && preg_match('/-?\d+/', $candidate)) {
                    return $toInt($candidate, $default);
                }
            }
        }

        return $default;
    };

    $recentMovements = $toArrayList($recentMovements ?? $movements ?? $latestMovements ?? $recentInventoryMovements ?? []);
    $lowStockProducts = $toArrayList($lowStockProducts ?? $criticalStockProducts ?? $alertsStock ?? []);
    $pendingPicking = $toArrayList($pendingPicking ?? $pickings ?? $pendingPickings ?? $pickWaveList ?? $pickWaves ?? []);
    $fastFlowItems = $toArrayList($fastFlowItems ?? $fastFlowBatchesList ?? $fastFlowList ?? $fastflowItems ?? $fastFlowBatchesData ?? []);
    $trendData = $toArrayList($trendData ?? $chartData ?? $movementTrend ?? []);

    /*
    |--------------------------------------------------------------------------
    | Catalog Items
    |--------------------------------------------------------------------------
    | Este dashboard ya no se enfoca en "productos" genéricos.
    | El KPI principal toma catalog_items como fuente real.
    */
    $catalogItemsCount = $pickFirstNumeric([
        $catalogItemsCount ?? null,
        $totalCatalogItems ?? null,
        $itemsCatalogCount ?? null,
        $productsCount ?? null,
        $totalProducts ?? null,
        isset($catalogItems) ? (is_countable($catalogItems) ? count($catalogItems) : null) : null,
        isset($products) ? (is_countable($products) ? count($products) : null) : null,
    ]);

    $catalogItemsUnits = $pickFirstNumeric([
        $catalogItemsUnits ?? null,
        $totalCatalogItemsUnits ?? null,
        $catalogStockUnits ?? null,
        $productsUnits ?? null,
        $totalUnits ?? null,
        $stockUnits ?? null,
        $inventoryUnits ?? null,
        $totalStock ?? null,
    ]);

    $locationsCount = $pickFirstNumeric([
        $locationsCount ?? null,
        $totalLocations ?? null,
        isset($locations) ? (is_countable($locations) ? count($locations) : null) : null,
    ]);

    $availableLocations = $pickFirstNumeric([
        $availableLocations ?? null,
        $locationsAvailable ?? null,
        $freeLocations ?? null,
        $emptyLocations ?? null,
    ]);

    $todayMovementsCount = $pickFirstNumeric([
        $todayMovementsCount ?? null,
        $movementsToday ?? null,
        $movements_count_today ?? null,
        $todayMovementCount ?? null,
    ]);

    $providersCount = $pickFirstNumeric([
        $providersCount ?? null,
        $totalProviders ?? null,
        isset($providers) ? (is_countable($providers) ? count($providers) : null) : null,
    ]);

    $clientsCount = $pickFirstNumeric([
        $clientsCount ?? null,
        $totalClients ?? null,
        isset($clients) ? (is_countable($clients) ? count($clients) : null) : null,
    ]);

    $lowStockCount = $pickFirstNumeric([
        $lowStockCount ?? null,
        $criticalStockCount ?? null,
        count($lowStockProducts),
    ]);

    $pendingPickingCount = $pickFirstNumeric([
        $pendingPickingCount ?? null,
        $pickingsPending ?? null,
        $pickingPending ?? null,
        $pendingPickingsCount ?? null,
        $pendingOrders ?? null,
        count($pendingPicking),
    ]);

    $fastFlowCount = $pickFirstNumeric([
        $fastFlowCount ?? null,
        $fastflowPending ?? null,
        $fastFlowPending ?? null,
        $fastFlowBatches ?? null,
        $fastFlowBatchesCount ?? null,
        count($fastFlowItems),
    ]);

    if (!$todayMovementsCount && count($recentMovements)) {
        $today = now()->toDateString();

        $todayMovementsCount = collect($recentMovements)->filter(function ($m) use ($today) {
            $date = data_get($m, 'created_at', data_get($m, 'date', data_get($m, 'fecha')));

            if (!$date) {
                return false;
            }

            try {
                return Carbon::parse($date)->toDateString() === $today;
            } catch (\Throwable $e) {
                return false;
            }
        })->count();
    }

    if (!$lowStockCount && count($lowStockProducts)) {
        $lowStockCount = count($lowStockProducts);
    }

    if (!$pendingPickingCount && count($pendingPicking)) {
        $pendingPickingCount = count($pendingPicking);
    }

    if (!$fastFlowCount && count($fastFlowItems)) {
        $fastFlowCount = count($fastFlowItems);
    }

    $catalogItemsUrl = $catalogItemsUrl ?? $routeFirst([
        'admin.catalog-items.index',
        'catalog-items.index',
        'admin.wms.catalog-items.index',
        'admin.wms.products.index',
        'wms.products.index',
        'products.index',
    ]);

    $locationsUrl = $locationsUrl ?? $routeFirst([
        'admin.wms.locations.index',
        'admin.wms.locations.view',
        'wms.locations.index',
        'locations.index',
    ]);

    $movementsUrl = $movementsUrl ?? $routeFirst([
        'admin.wms.audit',
        'admin.wms.movements.view',
    ]);

    $providersUrl = $providersUrl ?? $routeFirst([
        'admin.wms.providers.index',
        'wms.providers.index',
        'providers.index',
    ]);

    $clientsUrl = $clientsUrl ?? $routeFirst([
        'admin.wms.clients.index',
        'wms.clients.index',
        'clients.index',
    ]);

    $pickingUrl = $pickingUrl ?? $routeFirst([
        'admin.wms.picking.v2',
        'admin.wms.picking.index',
    ]);

    $fastFlowUrl = $fastFlowUrl ?? $routeFirst([
        'admin.wms.fastflow.index',
        'admin.wms.fast-flow.index',
    ]);

    $analyticsV2Url = Route::has('admin.wms.analytics.v2')
        ? route('admin.wms.analytics.v2')
        : '#';

    $cards = [
        [
            'title' => 'CATALOG ITEMS',
            'value' => number_format($catalogItemsCount),
            'sub'   => number_format($catalogItemsUnits) . ' unidades',
            'color' => 'blue',
            'icon'  => 'box',
            'url'   => $catalogItemsUrl,
        ],
        [
            'title' => 'UBICACIONES',
            'value' => number_format($locationsCount),
            'sub'   => number_format($availableLocations) . ' disponibles',
            'color' => 'green',
            'icon'  => 'pin',
            'url'   => $locationsUrl,
        ],
        [
            'title' => 'MOVIMIENTOS HOY',
            'value' => number_format($todayMovementsCount),
            'sub'   => 'actividad del día',
            'color' => 'purple',
            'icon'  => 'trend',
            'url'   => $movementsUrl,
        ],
        [
            'title' => 'PICKING',
            'value' => number_format($pendingPickingCount),
            'sub'   => 'olas pendientes',
            'color' => 'amber',
            'icon'  => 'pick',
            'url'   => $pickingUrl,
        ],
        [
            'title' => 'PROVEEDORES',
            'value' => number_format($providersCount),
            'sub'   => 'activos',
            'color' => 'cyan',
            'icon'  => 'truck',
            'url'   => $providersUrl,
        ],
        [
            'title' => 'CLIENTES',
            'value' => number_format($clientsCount),
            'sub'   => 'activos',
            'color' => 'rose',
            'icon'  => 'users',
            'url'   => $clientsUrl,
        ],
        [
            'title' => 'FAST FLOW',
            'value' => number_format($fastFlowCount),
            'sub'   => 'lotes activos',
            'color' => 'teal',
            'icon'  => 'fastflow',
            'url'   => $fastFlowUrl,
        ],
        [
            'title' => 'ALERTAS STOCK',
            'value' => number_format($lowStockCount),
            'sub'   => 'catalog items bajo mínimo',
            'color' => 'red',
            'icon'  => 'alert',
            'url'   => $analyticsV2Url,
        ],
    ];

    $featuredAlert = $lowStockProducts[0] ?? null;
    $featuredPicking = $pendingPicking[0] ?? null;
    $featuredFastFlow = $fastFlowItems[0] ?? null;

    $todayLabel = now()->format('d M Y');

    $alertStock = (int) data_get($featuredAlert, 'stock', data_get($featuredAlert, 'current_stock', 0));
    $alertMin = (int) data_get($featuredAlert, 'min_stock', data_get($featuredAlert, 'stock_min', data_get($featuredAlert, 'minimum_stock', 0)));
    $alertMax = (int) data_get($featuredAlert, 'max_stock', data_get($featuredAlert, 'stock_max', 0));
    $alertDeficit = (int) data_get($featuredAlert, 'deficit', max(0, $alertMin - $alertStock));
@endphp

<div class="wmsdash-wrap">
    <div class="wmsdash-head">
        <div>
            <h1 class="wmsdash-title">Dashboard</h1>
            <div class="wmsdash-sub">Vista general del almacén · {{ $todayLabel }}</div>
        </div>

        <div class="wmsdash-actions">
            <a href="{{ Route::has('admin.wms.home') ? route('admin.wms.home') : '#' }}" class="wmsdash-btn wmsdash-btn-ghost">← WMS</a>

            <a href="{{ $analyticsV2Url }}" class="wmsdash-btn wmsdash-btn-primary">
                Analytics v2
            </a>

            <form method="GET" action="{{ url()->current() }}">
                <select name="period" class="wmsdash-select" onchange="this.form.submit()">
                    <option value="7" @selected($period === 7)>Últimos 7 días</option>
                    <option value="30" @selected($period === 30)>Últimos 30 días</option>
                    <option value="90" @selected($period === 90)>Últimos 90 días</option>
                </select>
            </form>
        </div>
    </div>

    <div class="wmsdash-stats">
        @foreach($cards as $card)
            @if(!empty($card['url']) && $card['url'] !== '#')
                <a href="{{ $card['url'] }}" class="wmsdash-stat-shell is-link">
            @else
                <div class="wmsdash-stat-shell">
            @endif

                <div class="wmsdash-stat">
                    <div class="wmsdash-stat-top">
                        <div class="wmsdash-stat-label">{{ $card['title'] }}</div>

                        <div class="wmsdash-icon {{ $card['color'] }}">
                            @switch($card['icon'])
                                @case('box')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                        <path d="M12 3 4 7.5 12 12l8-4.5L12 3Z"/>
                                        <path d="M4 7.5V16.5L12 21l8-4.5V7.5"/>
                                        <path d="M12 12v9"/>
                                    </svg>
                                @break

                                @case('pin')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                        <path d="M12 21s6-5.6 6-11a6 6 0 1 0-12 0c0 5.4 6 11 6 11Z"/>
                                        <circle cx="12" cy="10" r="2.2"/>
                                    </svg>
                                @break

                                @case('trend')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                        <path d="M4 15l5-5 4 4 7-8"/>
                                        <path d="M16 6h4v4"/>
                                    </svg>
                                @break

                                @case('pick')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                        <path d="M4 7h16"/>
                                        <path d="M7 7v10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V7"/>
                                        <path d="M9 11h6"/>
                                        <path d="M9 15h4"/>
                                    </svg>
                                @break

                                @case('truck')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                        <path d="M3 7h11v8H3z"/>
                                        <path d="M14 10h3l3 3v2h-6z"/>
                                        <circle cx="7" cy="17" r="2"/>
                                        <circle cx="17" cy="17" r="2"/>
                                    </svg>
                                @break

                                @case('users')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/>
                                        <circle cx="9.5" cy="8" r="3"/>
                                        <path d="M20 21v-2a3.5 3.5 0 0 0-2.5-3.35"/>
                                        <path d="M15.5 5.2a3 3 0 0 1 0 5.6"/>
                                    </svg>
                                @break

                                @case('fastflow')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                        <path d="M3 12h10"/>
                                        <path d="M9 8l4 4-4 4"/>
                                        <path d="M14 7h7"/>
                                        <path d="M14 17h7"/>
                                    </svg>
                                @break

                                @case('alert')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                        <path d="M12 9v4"/>
                                        <path d="M12 17h.01"/>
                                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                                    </svg>
                                @break
                            @endswitch
                        </div>
                    </div>

                    <div class="wmsdash-stat-value">{{ $card['value'] }}</div>
                    <div class="wmsdash-stat-sub">{{ $card['sub'] }}</div>
                </div>

            @if(!empty($card['url']) && $card['url'] !== '#')
                </a>
            @else
                </div>
            @endif
        @endforeach
    </div>

    <div class="wmsdash-main">
        <div class="wmsdash-panel wmsdash-panel-lg">
            <div class="wmsdash-panel-head">
                <div class="wmsdash-panel-title">Movimientos Recientes</div>
                <a href="{{ $movementsUrl }}" class="wmsdash-link">Ver todos</a>
            </div>

            @if(count($recentMovements))
                <div class="wmsdash-table-wrap">
                    <table class="wmsdash-table">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Catalog item</th>
                                <th>SKU</th>
                                <th class="ta-right">Cantidad</th>
                                <th>Usuario</th>
                                <th class="ta-right">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentMovements as $m)
                                @php
                                    $typeRaw = strtolower((string) data_get($m, 'type', data_get($m, 'movement_type', data_get($m, 'kind', 'movimiento'))));
                                    $qty = (int) data_get($m, 'qty', data_get($m, 'quantity', data_get($m, 'cantidad', 0)));
                                    $date = data_get($m, 'created_at', data_get($m, 'date', data_get($m, 'fecha')));
                                    $productName = data_get($m, 'product_name', data_get($m, 'name', data_get($m, 'product.name', data_get($m, 'catalog_item.name', '—'))));
                                    $sku = data_get($m, 'sku', data_get($m, 'product.sku', data_get($m, 'catalog_item.sku', '—')));
                                    $userName = data_get($m, 'user_name', data_get($m, 'user', data_get($m, 'user.name', data_get($m, 'createdBy.name', '—'))));

                                    $typeBadge = 'neutral';

                                    if (
                                        str_contains($typeRaw, 'entrada') ||
                                        str_contains($typeRaw, 'entry') ||
                                        $typeRaw === 'in'
                                    ) {
                                        $typeBadge = 'in';
                                    } elseif (
                                        str_contains($typeRaw, 'salida') ||
                                        str_contains($typeRaw, 'exit') ||
                                        $typeRaw === 'out'
                                    ) {
                                        $typeBadge = 'out';
                                    }

                                    $dateFormatted = '—';

                                    try {
                                        $dateFormatted = $date ? Carbon::parse($date)->format('d/m/Y H:i') : '—';
                                    } catch (\Throwable $e) {
                                        $dateFormatted = '—';
                                    }
                                @endphp

                                <tr>
                                    <td>
                                        <span class="wm-badge {{ $typeBadge }}">
                                            {{ ucfirst($typeRaw ?: 'movimiento') }}
                                        </span>
                                    </td>
                                    <td>{{ $productName ?: '—' }}</td>
                                    <td class="mono">{{ $sku ?: '—' }}</td>
                                    <td class="ta-right"><strong>{{ number_format($qty) }}</strong></td>
                                    <td>{{ $userName ?: '—' }}</td>
                                    <td class="ta-right">{{ $dateFormatted }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="wmsdash-empty">
                    <div class="wmsdash-empty-text">No hay movimientos recientes</div>
                </div>
            @endif
        </div>

        <div class="wmsdash-side">
            <div class="wmsdash-panel">
                <div class="wmsdash-panel-head">
                    <div class="wmsdash-panel-title">Actividad {{ $period }} Días</div>
                </div>

                <div class="wmsdash-chart-wrap">
                    <div id="wmsActivityChart"></div>
                </div>
            </div>

            <div class="wmsdash-panel wmsdash-alert-panel">
                <div class="wmsdash-mini-head">
                    <div class="wmsdash-mini-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                            <path d="M12 9v4"/>
                            <path d="M12 17h.01"/>
                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                        </svg>
                        Alertas de stock
                    </div>

                    <span class="mini-count">{{ $lowStockCount }}</span>
                </div>

                @if($featuredAlert)
                    <div class="mini-card-body">
                        <div class="mini-main">{{ data_get($featuredAlert, 'name', data_get($featuredAlert, 'product_name', '—')) }}</div>
                        <div class="mini-sub">{{ data_get($featuredAlert, 'sku', data_get($featuredAlert, 'product.sku', '—')) }}</div>

                        <div class="alert-metrics">
                            <div class="alert-metric">
                                <span class="alert-metric-label">Actual</span>
                                <span class="alert-metric-value">{{ number_format($alertStock) }}</span>
                            </div>

                            <div class="alert-metric">
                                <span class="alert-metric-label">Mínimo</span>
                                <span class="alert-metric-value">{{ number_format($alertMin) }}</span>
                            </div>

                            <div class="alert-metric">
                                <span class="alert-metric-label">Máximo</span>
                                <span class="alert-metric-value">{{ $alertMax > 0 ? number_format($alertMax) : '—' }}</span>
                            </div>

                            <div class="alert-metric deficit">
                                <span class="alert-metric-label">Déficit</span>
                                <span class="alert-metric-value">{{ number_format($alertDeficit) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mini-foot alert-foot">
                        <span class="pill pill-danger">
                            {{ number_format($alertStock) }}/{{ number_format($alertMin) }}
                        </span>

                        <span class="pill pill-soft-danger">
                            Faltan {{ number_format($alertDeficit) }}
                        </span>
                    </div>
                @else
                    <div class="mini-empty">Sin alertas de stock por ahora.</div>
                @endif
            </div>

            <div class="wmsdash-panel">
                <div class="wmsdash-panel-head">
                    <div class="wmsdash-panel-title">Picking y Fast Flow</div>

                    <div class="wmsdash-links-row">
                        @if($pickingUrl && $pickingUrl !== '#')
                            <a href="{{ $pickingUrl }}" class="wmsdash-link">Picking</a>
                        @endif

                        @if($fastFlowUrl && $fastFlowUrl !== '#')
                            <a href="{{ $fastFlowUrl }}" class="wmsdash-link">Fast Flow</a>
                        @endif
                    </div>
                </div>

                <div class="ops-stack">
                    <div class="ops-item">
                        <div class="ops-item-top">
                            <div class="ops-item-title">Picking</div>
                            <span class="ops-count picking">{{ number_format($pendingPickingCount) }}</span>
                        </div>

                        @if($featuredPicking)
                            @php
                                $pickCode = data_get($featuredPicking, 'batch_code',
                                    data_get($featuredPicking, 'wave_code',
                                    data_get($featuredPicking, 'code',
                                    data_get($featuredPicking, 'task_number',
                                    data_get($featuredPicking, 'folio', '—')))));

                                $pickSub = data_get($featuredPicking, 'product_name',
                                    data_get($featuredPicking, 'customer_name',
                                    data_get($featuredPicking, 'assigned_to',
                                    data_get($featuredPicking, 'notes',
                                    data_get($featuredPicking, 'status', 'Pendiente')))));

                                $pickStatus = strtolower((string) data_get($featuredPicking, 'status', 'pendiente'));
                            @endphp

                            <div class="mini-card-body ops-mini-body">
                                <div class="mini-main">{{ $pickCode }}</div>
                                <div class="mini-sub">{{ $pickSub }}</div>
                            </div>

                            <div class="mini-foot ops-mini-foot">
                                <span class="pill pill-pick">{{ $pickStatus }}</span>
                            </div>
                        @else
                            <div class="mini-empty ops-empty">Sin picking pendiente.</div>
                        @endif
                    </div>

                    <div class="ops-item">
                        <div class="ops-item-top">
                            <div class="ops-item-title">Fast Flow</div>
                            <span class="ops-count fastflow">{{ number_format($fastFlowCount) }}</span>
                        </div>

                        @if($featuredFastFlow)
                            @php
                                $ffCode = data_get($featuredFastFlow, 'batch_code',
                                    data_get($featuredFastFlow, 'code',
                                    data_get($featuredFastFlow, 'lot_code',
                                    data_get($featuredFastFlow, 'folio', '—'))));

                                $ffSub = data_get($featuredFastFlow, 'product_name',
                                    data_get($featuredFastFlow, 'name',
                                    data_get($featuredFastFlow, 'status', 'Activo')));

                                $ffStatus = strtolower((string) data_get($featuredFastFlow, 'status', 'activo'));
                            @endphp

                            <div class="mini-card-body ops-mini-body">
                                <div class="mini-main">{{ $ffCode }}</div>
                                <div class="mini-sub">{{ $ffSub }}</div>
                            </div>

                            <div class="mini-foot ops-mini-foot">
                                <span class="pill pill-fastflow">{{ $ffStatus }}</span>
                            </div>
                        @else
                            <div class="mini-empty ops-empty">Sin lotes de fast flow activos.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

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

        --wms-bg: #f9fafb;
        --wms-card: #ffffff;
        --wms-ink: #111111;
        --wms-text: #333333;
        --wms-muted: #888888;
        --wms-line: #ebebeb;
        --wms-line-soft: #f1f1f1;
        --wms-shadow: 0 4px 12px rgba(0,0,0,0.02);

        --wms-blue: #007aff;
        --wms-green: #15803d;
        --wms-purple: #7c3aed;
        --wms-amber: #f59e0b;
        --wms-cyan: #06b6d4;
        --wms-rose: #f43f5e;
        --wms-red: #ff4a4a;
        --wms-teal: #14b8a6;
    }

    body {
        background: radial-gradient(circle at top left, #eef4ff 0, #f9fafb 360px, #f9fafb 100%);
        font-family: 'Quicksand', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
        color: var(--ink);
    }

    .wmsdash-wrap {
        max-width: 1280px;
        margin: 0 auto;
        padding: 28px 18px 42px;
    }

    .wmsdash-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        flex-wrap: wrap;
        margin-bottom: 28px;
    }

    .wmsdash-title {
        margin: 0;
        font-size: clamp(44px, 5vw, 72px);
        line-height: .95;
        font-weight: 700;
        letter-spacing: -0.06em;
        color: #111111;
    }

    .wmsdash-sub {
        margin-top: 14px;
        color: #667085;
        font-size: clamp(20px, 2.2vw, 30px);
        font-weight: 500;
        letter-spacing: -0.03em;
    }

    .wmsdash-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .wmsdash-btn {
        border-radius: 999px;
        padding: 12px 16px;
        font-weight: 700;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        transition: transform .2s ease, background .2s ease, color .2s ease, box-shadow .2s ease, border-color .2s ease;
    }

    .wmsdash-btn:hover {
        transform: translateY(-1px);
    }

    .wmsdash-btn:active {
        transform: scale(.98);
    }

    .wmsdash-btn-ghost {
        background: transparent;
        color: #555555;
        border: 1px solid var(--line);
    }

    .wmsdash-btn-ghost:hover {
        background: #f9fafb;
    }

    .wmsdash-btn-primary {
        background: var(--blue);
        color: #ffffff;
        border: 1px solid var(--blue);
        box-shadow: 0 10px 24px rgba(0,122,255,.18);
    }

    .wmsdash-btn-primary:hover {
        background: #006ee6;
    }

    .wmsdash-select {
        min-width: 180px;
        min-height: 46px;
        border-radius: 12px;
        border: 1px solid var(--line);
        background: var(--card);
        color: var(--ink);
        padding: 10px 14px;
        font-family: 'Quicksand', system-ui, sans-serif;
        font-weight: 600;
        box-shadow: var(--wms-shadow);
        outline: none;
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .wmsdash-select:focus {
        border-color: var(--blue);
        box-shadow: 0 0 0 3px var(--blue-soft);
    }

    .wmsdash-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 18px;
        margin-bottom: 22px;
    }

    .wmsdash-stat-shell {
        display: block;
        text-decoration: none;
        color: inherit;
        border-radius: 24px;
    }

    .wmsdash-stat-shell.is-link {
        cursor: pointer;
    }

    .wmsdash-stat {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 24px;
        box-shadow: var(--wms-shadow);
        padding: 28px;
        min-height: 200px;
        transition: transform .22s ease, box-shadow .22s ease, background .22s ease, border-color .22s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .wmsdash-stat-shell:hover .wmsdash-stat {
        transform: translateY(-2px);
        box-shadow: 0 14px 34px rgba(0,0,0,0.06);
        border-color: #dfe7f5;
    }

    .wmsdash-stat-shell:active .wmsdash-stat {
        transform: scale(.99);
    }

    .wmsdash-stat-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    .wmsdash-stat-label {
        color: #98a2b3;
        font-size: 18px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .wmsdash-icon {
        width: 58px;
        height: 58px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        flex: 0 0 58px;
        box-shadow: 0 18px 34px rgba(0,122,255,0.16);
        transition: transform .22s ease, box-shadow .22s ease, background .22s ease, color .22s ease;
    }

    .wmsdash-stat-shell:hover .wmsdash-icon {
        transform: scale(1.05);
    }

    .wmsdash-icon svg {
        width: 28px;
        height: 28px;
    }

    .wmsdash-icon.blue { background: linear-gradient(135deg, #5a8dff, #2563eb); }
    .wmsdash-icon.green { background: linear-gradient(135deg, #34d399, #15803d); }
    .wmsdash-icon.purple { background: linear-gradient(135deg, #a78bfa, #7c3aed); }
    .wmsdash-icon.amber { background: linear-gradient(135deg, #fbbf24, #f59e0b); }
    .wmsdash-icon.cyan { background: linear-gradient(135deg, #22d3ee, #0891b2); }
    .wmsdash-icon.rose { background: linear-gradient(135deg, #fb7185, #e11d48); }
    .wmsdash-icon.teal { background: linear-gradient(135deg, #2dd4bf, #0f766e); }
    .wmsdash-icon.red { background: linear-gradient(135deg, #fb7185, #ff4a4a); }

    .wmsdash-stat-value {
        margin-top: 38px;
        font-size: 54px;
        line-height: .9;
        font-weight: 700;
        color: #111827;
        letter-spacing: -0.07em;
    }

    .wmsdash-stat-sub {
        margin-top: 14px;
        color: #667085;
        font-size: 20px;
        font-weight: 500;
        letter-spacing: -0.04em;
    }

    .wmsdash-main {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(300px, .95fr);
        gap: 18px;
        align-items: start;
    }

    .wmsdash-side {
        display: grid;
        gap: 18px;
    }

    .wmsdash-panel {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 20px;
        box-shadow: var(--wms-shadow);
        overflow: hidden;
    }

    .wmsdash-panel-lg {
        min-height: 408px;
    }

    .wmsdash-panel-head {
        padding: 22px 24px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .wmsdash-panel-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #111111;
    }

    .wmsdash-link {
        color: var(--blue);
        font-size: .9rem;
        text-decoration: none;
        font-weight: 700;
    }

    .wmsdash-link:hover {
        text-decoration: underline;
    }

    .wmsdash-links-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .wmsdash-table-wrap {
        padding: 0 14px 14px;
        overflow: auto;
    }

    .wmsdash-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .92rem;
    }

    .wmsdash-table th,
    .wmsdash-table td {
        padding: 12px 10px;
        border-bottom: 1px solid var(--wms-line-soft);
        vertical-align: middle;
    }

    .wmsdash-table th {
        text-align: left;
        color: #98a2b3;
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .wmsdash-table tbody tr:hover {
        background: #fbfcfe;
    }

    .ta-right {
        text-align: right;
    }

    .mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }

    .wm-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 88px;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 700;
        border: 1px solid transparent;
    }

    .wm-badge.in {
        background: var(--success-soft);
        color: var(--success);
        border-color: #c8f7d4;
    }

    .wm-badge.out {
        background: var(--danger-soft);
        color: var(--danger);
        border-color: #ffd2d2;
    }

    .wm-badge.neutral {
        background: #f9fafb;
        color: #555555;
        border-color: var(--line);
    }

    .wmsdash-empty {
        min-height: 300px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 26px;
    }

    .wmsdash-empty-text {
        color: var(--muted);
        font-size: 1.05rem;
        font-weight: 600;
    }

    .wmsdash-chart-wrap {
        padding: 2px 10px 10px;
    }

    #wmsActivityChart {
        min-height: 160px;
    }

    .wmsdash-alert-panel {
        border-left: 3px solid #fbbf24;
    }

    .wmsdash-mini-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 20px 22px 12px;
    }

    .wmsdash-mini-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
        color: #111111;
    }

    .wmsdash-mini-title svg {
        width: 16px;
        height: 16px;
        color: #f59e0b;
    }

    .mini-count {
        min-width: 24px;
        height: 24px;
        padding: 0 8px;
        border-radius: 999px;
        background: #fff7d6;
        color: #a16207;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .78rem;
        font-weight: 700;
    }

    .mini-card-body {
        padding: 0 22px 8px;
    }

    .mini-main {
        font-size: 1rem;
        font-weight: 700;
        color: #111111;
        line-height: 1.2;
    }

    .mini-sub {
        margin-top: 4px;
        color: var(--muted);
        font-size: .85rem;
        font-weight: 600;
    }

    .mini-foot {
        padding: 0 22px 20px;
        display: flex;
        justify-content: flex-end;
    }

    .mini-empty {
        padding: 0 22px 20px;
        color: var(--muted);
        font-size: .9rem;
        font-weight: 600;
    }

    .pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 700;
        text-transform: lowercase;
    }

    .pill-danger {
        background: var(--danger-soft);
        color: var(--danger);
        border: 1px solid #ffd2d2;
    }

    .pill-soft-danger {
        background: #fff6f6;
        color: var(--danger);
        border: 1px solid #ffd2d2;
    }

    .pill-pick {
        background: #fff7d6;
        color: #a16207;
        border: 1px solid #fde68a;
    }

    .pill-fastflow {
        background: #dffcf6;
        color: #0f766e;
        border: 1px solid #aaf4e5;
    }

    .alert-metrics {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        margin-top: 12px;
    }

    .alert-metric {
        border: 1px solid var(--line);
        background: #fbfcfe;
        border-radius: 12px;
        padding: 10px 12px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .alert-metric.deficit {
        background: var(--danger-soft);
        border-color: #ffd2d2;
    }

    .alert-metric-label {
        font-size: .72rem;
        color: #98a2b3;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .alert-metric-value {
        font-size: 1rem;
        font-weight: 700;
        color: #111111;
    }

    .alert-foot {
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }

    .ops-stack {
        display: grid;
        gap: 12px;
        padding: 0 18px 18px;
    }

    .ops-item {
        border: 1px solid var(--line);
        border-radius: 14px;
        background: #fbfcfe;
        overflow: hidden;
    }

    .ops-item-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 14px 16px 10px;
    }

    .ops-item-title {
        font-size: .95rem;
        font-weight: 700;
        color: #111111;
    }

    .ops-count {
        min-width: 30px;
        height: 30px;
        padding: 0 10px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .82rem;
        font-weight: 700;
    }

    .ops-count.picking {
        background: #fff7d6;
        color: #a16207;
    }

    .ops-count.fastflow {
        background: #dffcf6;
        color: #0f766e;
    }

    .ops-mini-body {
        padding-top: 0;
    }

    .ops-mini-foot {
        padding-top: 0;
        padding-bottom: 14px;
    }

    .ops-empty {
        padding-top: 0;
    }

    @media (max-width: 1100px) {
        .wmsdash-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .wmsdash-main {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 680px) {
        .wmsdash-wrap {
            padding: 24px 12px 36px;
        }

        .wmsdash-title {
            font-size: 44px;
        }

        .wmsdash-sub {
            font-size: 20px;
        }

        .wmsdash-stats {
            grid-template-columns: 1fr;
        }

        .wmsdash-actions {
            width: 100%;
        }

        .wmsdash-actions form,
        .wmsdash-select {
            width: 100%;
        }

        .wmsdash-stat {
            min-height: 180px;
            padding: 26px;
        }

        .wmsdash-stat-value {
            font-size: 52px;
        }

        .wmsdash-stat-sub {
            font-size: 19px;
        }

        .wmsdash-panel-head {
            padding: 18px 18px 12px;
        }

        .wmsdash-table-wrap {
            padding: 0 8px 10px;
        }

        .alert-metrics {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
(function () {
    const rawTrend = @json($trendData ?? []);
    const period = @json($period ?? 7);

    const fallbackLabels = (function () {
        const labels = [];
        const formatter = new Intl.DateTimeFormat('es-MX', { weekday: 'short' });

        for (let i = period - 1; i >= 0; i--) {
            const d = new Date();
            d.setDate(d.getDate() - i);
            labels.push(formatter.format(d).replace('.', ''));
        }

        return labels;
    })();

    const labels = Array.isArray(rawTrend) && rawTrend.length
        ? rawTrend.map(item => item.label || item.day || item.date || '')
        : fallbackLabels;

    const totals = Array.isArray(rawTrend) && rawTrend.length
        ? rawTrend.map(item => {
            if (typeof item.total !== 'undefined') return Number(item.total || 0);

            const entradas = Number(item.entradas || item.entries || item.in || 0);
            const salidas = Number(item.salidas || item.exits || item.out || 0);

            return entradas + salidas;
        })
        : fallbackLabels.map(() => 0);

    const el = document.querySelector('#wmsActivityChart');

    if (!el) return;

    new ApexCharts(el, {
        chart: {
            type: 'line',
            height: 170,
            toolbar: { show: false },
            zoom: { enabled: false },
            fontFamily: 'Quicksand, system-ui, sans-serif'
        },
        series: [{
            name: 'Movimientos',
            data: totals
        }],
        xaxis: {
            categories: labels,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                style: {
                    colors: '#888888',
                    fontSize: '11px',
                    fontWeight: 600
                }
            }
        },
        yaxis: {
            min: 0,
            forceNiceScale: true,
            labels: {
                style: {
                    colors: '#888888',
                    fontSize: '11px',
                    fontWeight: 600
                }
            }
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        colors: ['#007aff'],
        markers: {
            size: 0,
            hover: { size: 4 }
        },
        grid: {
            borderColor: '#ebebeb',
            strokeDashArray: 4
        },
        dataLabels: { enabled: false },
        tooltip: {
            y: {
                formatter: function (val) {
                    return Number(val || 0).toLocaleString('es-MX');
                }
            }
        },
        legend: { show: false }
    }).render();
})();
</script>
@endpush