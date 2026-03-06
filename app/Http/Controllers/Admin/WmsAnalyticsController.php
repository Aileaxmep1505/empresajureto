<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\PickWave;
use App\Models\WmsMovement;
use App\Models\WmsMovementLine;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class WmsAnalyticsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [ new Middleware('auth') ];
    }

    public function index(Request $r)
    {
        $period = (int) $r->get('period', 30);
        if (!in_array($period, [7, 30, 90], true)) {
            $period = 30;
        }

        $warehouseId = (int) $r->get('warehouse_id', 0);

        $cutoff = now()->subDays($period)->startOfDay();

        // =========================
        // Base queries
        // =========================
        $productsQ = CatalogItem::query();
        $locationsQ = Location::query();

        if ($warehouseId > 0) {
            $locationIds = Location::query()
                ->where('warehouse_id', $warehouseId)
                ->pluck('id');

            $locationsQ->where('warehouse_id', $warehouseId);
        } else {
            $locationIds = Location::query()->pluck('id');
        }

        $productStockCol = Schema::hasColumn('catalog_items', 'stock') ? 'stock' : null;
        $productMinCol   = Schema::hasColumn('catalog_items', 'min_stock') ? 'min_stock' : null;
        $productCatCol   = Schema::hasColumn('catalog_items', 'category') ? 'category' : null;

        // =========================
        // KPIs
        // =========================
        $totalStock = 0;
        if ($productStockCol) {
            $totalStock = (int) CatalogItem::query()->sum('stock');
        }

        $wmsLinesQ = WmsMovementLine::query()
            ->whereHas('movement', function ($q) use ($cutoff, $warehouseId) {
                $q->whereDate('created_at', '>=', $cutoff);
                if ($warehouseId > 0) {
                    $q->where('warehouse_id', $warehouseId);
                }
            })
            ->with('movement:id,type,warehouse_id,created_at');

        $wmsLines = $wmsLinesQ->get();

        $totalEntries = (int) $wmsLines
            ->filter(fn ($x) => optional($x->movement)->type === 'in')
            ->sum('qty');

        $totalExits = (int) $wmsLines
            ->filter(fn ($x) => optional($x->movement)->type === 'out')
            ->sum('qty');

        $lowStockCount = 0;
        if ($productStockCol && $productMinCol) {
            $lowStockCount = (int) CatalogItem::query()
                ->whereNotNull('min_stock')
                ->whereColumn('stock', '<=', 'min_stock')
                ->count();
        }

        // Usamos PickWave como “órdenes/operaciones pendientes” en esta fase
        $pendingOrders = (int) PickWave::query()
            ->whereIn('status', [0, 1])
            ->count();

        $completedOrders = (int) PickWave::query()
            ->where('status', 2)
            ->count();

        $totalLocations = (int) $locationsQ->count();

        $usedLocations = (int) Inventory::query()
            ->where('qty', '>', 0)
            ->when($warehouseId > 0, fn ($q) =>
                $q->whereIn('location_id', $locationIds)
            )
            ->distinct('location_id')
            ->count('location_id');

        $occupancyRate = $totalLocations > 0
            ? (int) round(($usedLocations / $totalLocations) * 100)
            : 0;

        // =========================
        // Stock por categoría
        // =========================
        $categoryMap = [];

        $products = CatalogItem::query()->get([
            'id',
            'name',
            'sku',
            ...(Schema::hasColumn('catalog_items', 'meli_gtin') ? ['meli_gtin'] : []),
            ...(Schema::hasColumn('catalog_items', 'stock') ? ['stock'] : []),
            ...(Schema::hasColumn('catalog_items', 'min_stock') ? ['min_stock'] : []),
            ...(Schema::hasColumn('catalog_items', 'category') ? ['category'] : []),
        ]);

        foreach ($products as $p) {
            $cat = $productCatCol ? trim((string)($p->category ?? '')) : '';
            $cat = $cat !== '' ? $cat : 'Sin categoría';
            $categoryMap[$cat] = ($categoryMap[$cat] ?? 0) + (int)($p->stock ?? 0);
        }

        $categoryChartData = collect($categoryMap)
            ->map(fn ($value, $name) => [
                'name' => $name,
                'value' => (int) $value,
            ])
            ->sortByDesc('value')
            ->values()
            ->all();

        // =========================
        // Tendencia 7 días
        // =========================
        $trendData = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $dayStr = $day->toDateString();

            $entries = (int) WmsMovementLine::query()
                ->whereHas('movement', function ($q) use ($dayStr, $warehouseId) {
                    $q->whereDate('created_at', $dayStr)
                        ->where('type', 'in');

                    if ($warehouseId > 0) {
                        $q->where('warehouse_id', $warehouseId);
                    }
                })
                ->sum('qty');

            $exits = (int) WmsMovementLine::query()
                ->whereHas('movement', function ($q) use ($dayStr, $warehouseId) {
                    $q->whereDate('created_at', $dayStr)
                        ->where('type', 'out');

                    if ($warehouseId > 0) {
                        $q->where('warehouse_id', $warehouseId);
                    }
                })
                ->sum('qty');

            $trendData[] = [
                'day' => $this->dayShortEs($day),
                'entradas' => $entries,
                'salidas' => $exits,
            ];
        }

        // =========================
        // Top productos por stock
        // =========================
        $topProducts = $products
            ->sortByDesc(fn ($p) => (int)($p->stock ?? 0))
            ->take(8)
            ->map(function ($p) {
                $name = (string)($p->name ?? '—');
                return [
                    'name'  => mb_strlen($name) > 18 ? mb_substr($name, 0, 18).'...' : $name,
                    'stock' => (int)($p->stock ?? 0),
                ];
            })
            ->values()
            ->all();

        // =========================
        // Movimientos por tipo
        // =========================
        $entryCount = (int) WmsMovement::query()
            ->when($warehouseId > 0, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->whereDate('created_at', '>=', $cutoff)
            ->where('type', 'in')
            ->count();

        $exitCount = (int) WmsMovement::query()
            ->when($warehouseId > 0, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->whereDate('created_at', '>=', $cutoff)
            ->where('type', 'out')
            ->count();

        $transferCount = (int) InventoryMovement::query()
            ->whereDate('created_at', '>=', $cutoff)
            ->where('type', 'transfer')
            ->when($warehouseId > 0, fn ($q) =>
                $q->where(function ($qq) use ($locationIds) {
                    $qq->whereIn('from_location_id', $locationIds)
                       ->orWhereIn('to_location_id', $locationIds);
                })
            )
            ->count();

        $adjustCount = (int) InventoryMovement::query()
            ->whereDate('created_at', '>=', $cutoff)
            ->whereIn('type', ['adjust', 'cycle_count'])
            ->when($warehouseId > 0, fn ($q) =>
                $q->where(function ($qq) use ($locationIds) {
                    $qq->whereIn('from_location_id', $locationIds)
                       ->orWhereIn('to_location_id', $locationIds);
                })
            )
            ->count();

        $movTypeData = [
            ['name' => 'Entradas',       'cantidad' => $entryCount],
            ['name' => 'Salidas',        'cantidad' => $exitCount],
            ['name' => 'Transferencias', 'cantidad' => $transferCount],
            ['name' => 'Ajustes',        'cantidad' => $adjustCount],
        ];

        // =========================
        // Stock bajo table
        // =========================
        $lowStockProducts = collect();

        if ($productStockCol && $productMinCol) {
            $lowStockProducts = CatalogItem::query()
                ->whereNotNull('min_stock')
                ->whereColumn('stock', '<=', 'min_stock')
                ->orderBy('stock')
                ->limit(20)
                ->get([
                    'id',
                    'name',
                    'sku',
                    'stock',
                    'min_stock',
                ])
                ->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'sku' => $p->sku,
                        'stock' => (int)($p->stock ?? 0),
                        'min_stock' => (int)($p->min_stock ?? 0),
                        'deficit' => max(0, (int)($p->min_stock ?? 0) - (int)($p->stock ?? 0)),
                    ];
                })
                ->values();
        }

        $kpis = [
            [
                'title' => 'Total Stock',
                'value' => number_format($totalStock),
                'subtitle' => 'unidades en almacén',
                'icon' => 'package',
                'color' => 'blue',
            ],
            [
                'title' => 'Entradas',
                'value' => number_format($totalEntries),
                'subtitle' => "últimos {$period} días",
                'icon' => 'trend-down',
                'color' => 'green',
            ],
            [
                'title' => 'Salidas',
                'value' => number_format($totalExits),
                'subtitle' => "últimos {$period} días",
                'icon' => 'trend-up',
                'color' => 'purple',
            ],
            [
                'title' => 'Stock Bajo',
                'value' => number_format($lowStockCount),
                'subtitle' => 'productos con alerta',
                'icon' => 'alert',
                'color' => 'amber',
            ],
            [
                'title' => 'Órdenes Pendientes',
                'value' => number_format($pendingOrders),
                'subtitle' => number_format($completedOrders).' completadas',
                'icon' => 'clipboard',
                'color' => 'rose',
            ],
            [
                'title' => 'Ocupación Almacén',
                'value' => $occupancyRate.'%',
                'subtitle' => number_format($usedLocations).' / '.number_format($totalLocations).' ubicaciones',
                'icon' => 'swap',
                'color' => 'cyan',
            ],
        ];

        return view('admin.wms.analytics', [
            'period'            => $period,
            'warehouseId'       => $warehouseId,
            'kpis'              => $kpis,
            'trendData'         => $trendData,
            'categoryChartData' => $categoryChartData,
            'topProducts'       => $topProducts,
            'movTypeData'       => $movTypeData,
            'lowStockCount'     => $lowStockCount,
            'lowStockProducts'  => $lowStockProducts,
        ]);
    }

    private function dayShortEs(Carbon $date): string
    {
        $map = [
            'Mon' => 'lun',
            'Tue' => 'mar',
            'Wed' => 'mié',
            'Thu' => 'jue',
            'Fri' => 'vie',
            'Sat' => 'sáb',
            'Sun' => 'dom',
        ];

        return $map[$date->format('D')] ?? mb_strtolower($date->format('D'));
    }
}