<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\PickWave;
use App\Models\Provider;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WmsMovement;
use App\Models\WmsMovementLine;
use App\Models\WmsQuickBox;
use App\Models\WmsReception;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class WmsAnalyticsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('auth')];
    }

    public function index(Request $request)
    {
        return view('admin.wms.analytics', $this->buildAnalyticsData($request));
    }

    public function indexV2(Request $request)
    {
        return view('admin.wms.analytics-v2', $this->buildAnalyticsData($request));
    }

    public function data(Request $request)
    {
        return response()->json([
            'ok' => true,
            'data' => $this->buildAnalyticsData($request),
        ]);
    }

    public function activityData(Request $request)
    {
        $period = (int) $request->get('period', 30);
        if (!in_array($period, [7, 30, 90, 180, 365], true)) {
            $period = 30;
        }

        $warehouseId = (int) $request->get('warehouse_id', 0);
        $from = now()->subDays($period)->startOfDay()->toDateString();
        $to = now()->endOfDay()->toDateString();
        $q = trim((string) $request->get('q', ''));
        $limit = min(500, max(20, (int) $request->get('limit', 120)));

        $rows = $this->buildUnifiedAuditTimeline($warehouseId, $from, $to, $q)
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values()
            ->map(function ($row) {
                unset($row['timestamp']);
                return $row;
            })
            ->all();

        return response()->json([
            'ok' => true,
            'rows' => $rows,
        ]);
    }

    public function audit(Request $request)
    {
        $data = $this->buildAnalyticsData($request);
        $rows = $this->filteredAuditRowsFromRequest($request);

        $data['warehouses'] = Warehouse::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $data['auditRows'] = $rows
            ->map(function ($row) {
                unset($row['timestamp']);
                return $row;
            })
            ->values()
            ->all();

        $data['auditSummary'] = [
            'total' => (int) $rows->count(),
            'entries' => (int) $rows->filter(fn ($r) => ($r['group'] ?? '') === 'entry')->count(),
            'exits' => (int) $rows->filter(fn ($r) => ($r['group'] ?? '') === 'exit')->count(),
            'transfers' => (int) $rows->filter(fn ($r) => ($r['group'] ?? '') === 'transfer')->count(),
            'adjustments' => (int) $rows->filter(fn ($r) => ($r['group'] ?? '') === 'adjustment')->count(),
            'pickings' => (int) $rows->filter(fn ($r) => ($r['group'] ?? '') === 'picking')->count(),
            'total_qty' => (int) $rows->sum(fn ($r) => (int) ($r['qty'] ?? 0)),
        ];

        $data['filters'] = [
            'warehouse_id' => (int) $request->get('warehouse_id', 0),
            'period' => (int) $request->get('period', 30),
            'q' => trim((string) $request->get('q', '')),
            'group' => trim((string) $request->get('group', '')),
            'source' => trim((string) $request->get('source', '')),
            'type' => trim((string) $request->get('type', '')),
        ];

        return view('admin.wms.audit', $data);
    }

    public function auditAi(Request $request)
    {
        $request->validate([
            'warehouse_id' => ['nullable', 'integer'],
            'period' => ['nullable', 'integer', 'min:1', 'max:365'],
            'q' => ['nullable', 'string', 'max:200'],
            'group' => ['nullable', 'string', 'max:60'],
            'source' => ['nullable', 'string', 'max:60'],
            'type' => ['nullable', 'string', 'max:80'],
            'prompt' => ['required', 'string', 'min:3', 'max:8000'],
        ]);

        $question = trim((string) $request->input('prompt', ''));
        $rows = $this->filteredAuditRowsFromRequest($request);
        $analytics = $this->buildAnalyticsData($request);

        if ($rows->isEmpty()) {
            return response()->json([
                'ok' => false,
                'error' => 'No hay movimientos para analizar con los filtros actuales.',
            ], 422);
        }

        $apiKey = (string) config('services.openai.api_key');
        $baseUrl = rtrim((string) config('services.openai.base_url', 'https://api.openai.com'), '/');
        $models = array_values(array_filter(array_merge(
            [(string) config('services.openai.primary', 'gpt-5-2025-08-07')],
            (array) config('services.openai.fallbacks', [])
        )));

        if ($apiKey === '') {
            return response()->json([
                'ok' => false,
                'error' => 'Falta configurar OPENAI_API_KEY.',
            ], 422);
        }

        $context = $this->buildAuditAiContext($request, $rows, $analytics);

        $systemPrompt = <<<TXT
Eres Nexus AI, un analista experto en WMS, inventarios, picking, fast flow, auditoría operativa y trazabilidad.

Tu tarea es responder EXACTAMENTE la pregunta del usuario con base ÚNICAMENTE en los datos entregados.
No debes contestar con un formato ejecutivo genérico si la pregunta es puntual.
No debes inventar datos.
No debes asumir hechos no presentes en el contexto.
Si no hay evidencia suficiente, dilo claramente.

Reglas críticas:
1. Primero responde la pregunta textual del usuario en "direct_answer".
2. Si el usuario pregunta por:
   - un usuario: da el nombre o ranking exacto según el contexto
   - productos: da productos, cantidades y fechas
   - rangos de tiempo: respeta exactamente el rango solicitado
   - picking: usa primero el contexto de tareas/olas de picking
   - fast flow: usa lotes/cajas/unidades fast flow
3. Solo agrega riesgos o acciones si realmente aportan a la pregunta.
4. Si el contexto no alcanza para responder con certeza, indícalo en "direct_answer" y en "follow_up_data_needed".
5. Devuelve ÚNICAMENTE JSON válido con esta estructura exacta:

{
  "headline": "Título corto",
  "direct_answer": "Respuesta directa y clara a la pregunta",
  "summary_points": [
    "Punto 1",
    "Punto 2"
  ],
  "evidence": [
    {
      "label": "Etiqueta",
      "value": "Valor"
    }
  ],
  "tables": [
    {
      "title": "Título de tabla",
      "columns": ["Columna 1", "Columna 2"],
      "rows": [
        ["valor 1", "valor 2"]
      ]
    }
  ],
  "actions": [
    {
      "title": "Acción",
      "priority": "alta",
      "detail": "Detalle"
    }
  ],
  "follow_up_data_needed": [
    "Dato faltante 1"
  ],
  "score": 0,
  "risk_level": "informativo",
  "pdf_title": "Reporte IA WMS"
}

Valores permitidos:
- priority: alta, media, baja
- risk_level: alto, medio, bajo, informativo
- score: entero de 0 a 100

No uses markdown.
No escribas texto fuera del JSON.
TXT;

        $payloadText = json_encode([
            'user_question' => $question,
            'filters' => [
                'warehouse_id' => (int) $request->get('warehouse_id', 0),
                'period' => (int) $request->get('period', 30),
                'q' => trim((string) $request->get('q', '')),
                'group' => trim((string) $request->get('group', '')),
                'source' => trim((string) $request->get('source', '')),
                'type' => trim((string) $request->get('type', '')),
            ],
            'context' => $context,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $headers = [];
        if (config('services.openai.org_id')) {
            $headers['OpenAI-Organization'] = config('services.openai.org_id');
        }
        if (config('services.openai.project_id')) {
            $headers['OpenAI-Project'] = config('services.openai.project_id');
        }

        $lastError = 'No se pudo obtener respuesta de la IA.';
        $final = null;

        foreach ($models as $model) {
            $response = Http::withToken($apiKey)
                ->timeout((int) config('services.openai.timeout', 300))
                ->withHeaders(array_merge($headers, [
                    'Content-Type' => 'application/json',
                ]))
                ->post($baseUrl . '/v1/responses', [
                    'model' => $model,
                    'input' => [[
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'input_text',
                                'text' => $systemPrompt,
                            ],
                            [
                                'type' => 'input_text',
                                'text' => $payloadText,
                            ],
                        ],
                    ]],
                    'max_output_tokens' => 4500,
                ]);

            if (!$response->ok()) {
                $lastError = 'La IA devolvió error con el modelo ' . $model . '.';
                continue;
            }

            $json = $response->json();
            $rawText = $this->extractOpenAiText($json);

            if (!$rawText) {
                $lastError = 'La IA no devolvió texto utilizable.';
                continue;
            }

            $parsed = $this->decodeAuditAiJson($rawText);

            if (!is_array($parsed)) {
                $lastError = 'La IA no devolvió JSON válido.';
                continue;
            }

            $final = $this->normalizeAuditAiPayload($parsed, $rawText);
            $final['used_model'] = $model;
            break;
        }

        if (!$final) {
            return response()->json([
                'ok' => false,
                'error' => $lastError,
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'data' => $final,
        ]);
    }

    public function auditPdf(Request $request)
    {
        $request->validate([
            'warehouse_id' => ['nullable', 'integer'],
            'period' => ['nullable', 'integer', 'min:1', 'max:365'],
            'q' => ['nullable', 'string', 'max:200'],
            'group' => ['nullable', 'string', 'max:60'],
            'source' => ['nullable', 'string', 'max:60'],
            'type' => ['nullable', 'string', 'max:80'],
            'ai_payload' => ['nullable', 'string'],
            'ai_text' => ['nullable', 'string'],
        ]);

        $analytics = $this->buildAnalyticsData($request);
        $rows = $this->filteredAuditRowsFromRequest($request)
            ->take(600)
            ->map(function ($row) {
                unset($row['timestamp']);
                return $row;
            })
            ->values()
            ->all();

        $aiPayload = $this->arrayMeta($request->input('ai_payload'));
        $aiText = trim((string) $request->input('ai_text', ''));

        if (!is_array($aiPayload) || empty($aiPayload)) {
            $aiPayload = $this->normalizeAuditAiPayload([], $aiText);
        }

        $pdf = Pdf::loadView('admin.wms.audit_pdf', [
            'generatedAt' => now(),
            'filters' => [
                'warehouse_id' => (int) $request->get('warehouse_id', 0),
                'period' => (int) $request->get('period', 30),
                'q' => trim((string) $request->get('q', '')),
                'group' => trim((string) $request->get('group', '')),
                'source' => trim((string) $request->get('source', '')),
                'type' => trim((string) $request->get('type', '')),
            ],
            'analytics' => $analytics,
            'rows' => $rows,
            'aiReport' => $aiPayload,
            'aiText' => $aiText,
        ])->setPaper('a4', 'landscape');

        $name = 'auditoria-wms-' . now()->format('Ymd-His') . '.pdf';

        return $pdf->download($name);
    }

    private function buildAnalyticsData(Request $request): array
    {
        $period = (int) $request->get('period', 30);
        if (!in_array($period, [7, 30, 90, 180, 365], true)) {
            $period = 30;
        }

        $warehouseId = (int) $request->get('warehouse_id', 0);
        $cutoff = now()->subDays($period)->startOfDay();
        $today = now()->toDateString();

        $hasCatalogStock = Schema::hasColumn('catalog_items', 'stock');
        $hasCatalogMinStock = Schema::hasColumn('catalog_items', 'stock_min');
        $hasCatalogMaxStock = Schema::hasColumn('catalog_items', 'stock_max');
        $hasCatalogCategory = Schema::hasColumn('catalog_items', 'category');
        $hasCatalogMeliGtin = Schema::hasColumn('catalog_items', 'meli_gtin');
        $hasInventoryQty = Schema::hasColumn('inventories', 'qty');
        $hasLocationWarehouse = Schema::hasColumn('locations', 'warehouse_id');
        $hasQuickBoxesTable = Schema::hasTable('wms_quick_boxes');

        $locationIds = $this->warehouseLocationIds($warehouseId, $hasLocationWarehouse);

        $catalogSelect = ['id', 'name', 'sku'];
        if ($hasCatalogMeliGtin) {
            $catalogSelect[] = 'meli_gtin';
        }
        if ($hasCatalogStock) {
            $catalogSelect[] = 'stock';
        }
        if ($hasCatalogMinStock) {
            $catalogSelect[] = 'stock_min';
        }
        if ($hasCatalogMaxStock) {
            $catalogSelect[] = 'stock_max';
        }
        if ($hasCatalogCategory) {
            $catalogSelect[] = 'category';
        }

        $products = CatalogItem::query()->get($catalogSelect);
        $productsCount = (int) CatalogItem::query()->count();
        $providersCount = (int) Provider::query()->count();
        $clientsCount = (int) Client::query()->count();

        $locationsQuery = Location::query();
        if ($warehouseId > 0 && $hasLocationWarehouse) {
            $locationsQuery->where('warehouse_id', $warehouseId);
        }

        $totalLocations = (int) $locationsQuery->count();

        $usedLocations = 0;
        if ($hasInventoryQty) {
            $usedLocations = (int) Inventory::query()
                ->where('qty', '>', 0)
                ->when($warehouseId > 0 && $locationIds->isNotEmpty(), fn ($q) => $q->whereIn('location_id', $locationIds))
                ->distinct('location_id')
                ->count('location_id');
        }

        $availableLocations = max(0, $totalLocations - $usedLocations);
        $occupancyRate = $totalLocations > 0
            ? (int) round(($usedLocations / $totalLocations) * 100)
            : 0;

        $warehouseStockByItem = collect();
        if ($warehouseId > 0 && $locationIds->isNotEmpty() && $hasInventoryQty) {
            $warehouseStockByItem = Inventory::query()
                ->selectRaw('catalog_item_id, SUM(qty) as total_qty')
                ->whereIn('location_id', $locationIds)
                ->groupBy('catalog_item_id')
                ->pluck('total_qty', 'catalog_item_id');
        }

        $totalStock = 0;
        if ($warehouseId > 0 && $warehouseStockByItem->isNotEmpty()) {
            $totalStock = (int) $warehouseStockByItem->sum();
        } elseif ($hasCatalogStock) {
            $totalStock = (int) CatalogItem::query()->sum('stock');
        } elseif ($hasInventoryQty) {
            $totalStock = (int) Inventory::query()->sum('qty');
        }

        $lowStockCount = 0;
        $lowStockProducts = collect();

        if ($hasCatalogMinStock) {
            if ($warehouseId > 0 && $warehouseStockByItem->isNotEmpty()) {
                $lowStockProducts = $products
                    ->map(function ($product) use ($warehouseStockByItem, $hasCatalogMaxStock) {
                        $stock = (int) ($warehouseStockByItem[$product->id] ?? 0);
                        $minStock = (int) ($product->stock_min ?? 0);
                        $maxStock = $hasCatalogMaxStock ? (int) ($product->stock_max ?? 0) : 0;

                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'sku' => $product->sku,
                            'stock' => $stock,
                            'min_stock' => $minStock,
                            'max_stock' => $maxStock,
                            'deficit' => max(0, $minStock - $stock),
                        ];
                    })
                    ->filter(fn ($row) => $row['min_stock'] > 0 && $row['stock'] <= $row['min_stock'])
                    ->sortBy('stock')
                    ->take(20)
                    ->values();

                $lowStockCount = (int) $lowStockProducts->count();
            } elseif ($hasCatalogStock) {
                $lowStockCount = (int) CatalogItem::query()
                    ->whereNotNull('stock_min')
                    ->whereColumn('stock', '<=', 'stock_min')
                    ->count();

                $selectCols = ['id', 'name', 'sku', 'stock', 'stock_min'];
                if ($hasCatalogMaxStock) {
                    $selectCols[] = 'stock_max';
                }

                $lowStockProducts = CatalogItem::query()
                    ->whereNotNull('stock_min')
                    ->whereColumn('stock', '<=', 'stock_min')
                    ->orderBy('stock')
                    ->limit(20)
                    ->get($selectCols)
                    ->map(function ($product) use ($hasCatalogMaxStock) {
                        $stock = (int) ($product->stock ?? 0);
                        $minStock = (int) ($product->stock_min ?? 0);
                        $maxStock = $hasCatalogMaxStock ? (int) ($product->stock_max ?? 0) : 0;

                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'sku' => $product->sku,
                            'stock' => $stock,
                            'min_stock' => $minStock,
                            'max_stock' => $maxStock,
                            'deficit' => max(0, $minStock - $stock),
                        ];
                    })
                    ->values();
            }
        }

        $pickingsSummary = $this->pickingsSummary($warehouseId);
        $pendingOrders = (int) $pickingsSummary['pending'];
        $completedOrders = (int) $pickingsSummary['completed'];

        $categoryChartData = collect();
        foreach ($products as $product) {
            $category = $hasCatalogCategory ? trim((string) ($product->category ?? '')) : '';
            $category = $category !== '' ? $category : 'Sin categoría';

            $stock = $warehouseId > 0 && $warehouseStockByItem->isNotEmpty()
                ? (int) ($warehouseStockByItem[$product->id] ?? 0)
                : (int) ($product->stock ?? 0);

            $categoryChartData[$category] = ($categoryChartData[$category] ?? 0) + $stock;
        }

        $categoryChartData = collect($categoryChartData)
            ->map(fn ($value, $name) => ['name' => $name, 'value' => (int) $value])
            ->sortByDesc('value')
            ->values()
            ->all();

        $timeline = $this->buildUnifiedAuditTimeline(
            $warehouseId,
            $cutoff->toDateString(),
            now()->endOfDay()->toDateString(),
            ''
        )->sortByDesc('timestamp')->values();

        $movementRows = $timeline
            ->filter(fn ($row) => (int) ($row['qty'] ?? 0) > 0)
            ->values();

        $totalEntries = (int) $movementRows
            ->filter(fn ($row) => ($row['group'] ?? '') === 'entry')
            ->sum('qty');

        $totalExits = (int) $movementRows
            ->filter(fn ($row) => ($row['group'] ?? '') === 'exit')
            ->sum('qty');

        $transferQty = (int) $movementRows
            ->filter(fn ($row) => ($row['group'] ?? '') === 'transfer')
            ->sum('qty');

        $adjustQty = (int) $movementRows
            ->filter(fn ($row) => ($row['group'] ?? '') === 'adjustment')
            ->sum('qty');

        $entryCount = (int) $movementRows
            ->filter(fn ($row) => ($row['group'] ?? '') === 'entry')
            ->count();

        $exitCount = (int) $movementRows
            ->filter(fn ($row) => ($row['group'] ?? '') === 'exit')
            ->count();

        $transferCount = (int) $movementRows
            ->filter(fn ($row) => ($row['group'] ?? '') === 'transfer')
            ->count();

        $adjustCount = (int) $movementRows
            ->filter(fn ($row) => ($row['group'] ?? '') === 'adjustment')
            ->count();

        $movTypeData = [
            ['name' => 'Entradas', 'cantidad' => $entryCount],
            ['name' => 'Salidas', 'cantidad' => $exitCount],
            ['name' => 'Transferencias', 'cantidad' => $transferCount],
            ['name' => 'Ajustes', 'cantidad' => $adjustCount],
        ];

        $trendData = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $dayString = $day->toDateString();

            $dayRows = $movementRows->filter(function ($row) use ($dayString) {
                return !empty($row['when']) && str_starts_with((string) $row['when'], $dayString);
            });

            $entries = (int) $dayRows
                ->filter(fn ($row) => ($row['group'] ?? '') === 'entry')
                ->sum('qty');

            $exits = (int) $dayRows
                ->filter(fn ($row) => ($row['group'] ?? '') === 'exit')
                ->sum('qty');

            $trendData[] = [
                'day' => $this->dayShortEs($day),
                'entradas' => $entries,
                'salidas' => $exits,
                'total' => $entries + $exits,
            ];
        }

        $topProducts = $products
            ->map(function ($product) use ($warehouseId, $warehouseStockByItem) {
                $stock = $warehouseId > 0 && $warehouseStockByItem->isNotEmpty()
                    ? (int) ($warehouseStockByItem[$product->id] ?? 0)
                    : (int) ($product->stock ?? 0);

                $name = (string) ($product->name ?? '—');

                return [
                    'name' => mb_strlen($name) > 18 ? mb_substr($name, 0, 18) . '...' : $name,
                    'stock' => $stock,
                ];
            })
            ->sortByDesc('stock')
            ->take(8)
            ->values()
            ->all();

        $topMovedProducts = $movementRows
            ->filter(fn ($row) => !empty($row['item_id']))
            ->groupBy('item_id')
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'item_id' => (int) ($first['item_id'] ?? 0),
                    'name' => (string) ($first['name'] ?? 'Producto'),
                    'sku' => (string) ($first['sku'] ?? ''),
                    'qty' => (int) $rows->sum('qty'),
                    'movements' => (int) $rows->count(),
                ];
            })
            ->sortByDesc('qty')
            ->take(12)
            ->values()
            ->all();

        $recentMovements = $movementRows
            ->take(80)
            ->map(function ($row) {
                unset($row['timestamp']);
                return $row;
            })
            ->values()
            ->all();

        $auditTimeline = $timeline
            ->take(120)
            ->map(function ($row) {
                unset($row['timestamp']);
                return $row;
            })
            ->values()
            ->all();

        $activityBySource = $timeline
            ->groupBy('source')
            ->map(fn ($rows, $name) => [
                'name' => $name,
                'count' => (int) $rows->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();

        $activityByGroup = $timeline
            ->groupBy('group')
            ->map(fn ($rows, $name) => [
                'name' => $name,
                'count' => (int) $rows->count(),
                'qty' => (int) $rows->sum('qty'),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();

        $todayMovementsCount = (int) $movementRows
            ->filter(fn ($row) => !empty($row['when']) && str_starts_with((string) $row['when'], $today))
            ->count();

        $fastFlowCount = 0;
        $fastFlowItems = collect();
        $fastFlowAvailableUnits = 0;
        $fastFlowInboundToday = 0;
        $fastFlowOutboundToday = 0;
        $fastFlowActiveBoxes = 0;

        if ($hasQuickBoxesTable) {
            $hasQuickBoxWarehouse = Schema::hasColumn('wms_quick_boxes', 'warehouse_id');
            $hasQuickBoxStatus = Schema::hasColumn('wms_quick_boxes', 'status');
            $hasQuickBoxCurrentUnits = Schema::hasColumn('wms_quick_boxes', 'current_units');
            $hasQuickBoxReservedUnits = Schema::hasColumn('wms_quick_boxes', 'reserved_units');
            $hasQuickBoxBatchCode = Schema::hasColumn('wms_quick_boxes', 'batch_code');
            $hasQuickBoxReceivedAt = Schema::hasColumn('wms_quick_boxes', 'received_at');
            $hasQuickBoxBoxNumber = Schema::hasColumn('wms_quick_boxes', 'box_number');

            $quickBoxRows = WmsQuickBox::query()
                ->when($warehouseId > 0 && $hasQuickBoxWarehouse, fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->orderByDesc('id')
                ->limit(500)
                ->get();

            $activeRows = $quickBoxRows->filter(function ($row) use ($hasQuickBoxStatus) {
                if (!$hasQuickBoxStatus) {
                    return true;
                }

                return in_array((string) ($row->status ?? ''), ['available', 'partial'], true);
            })->values();

            $fastFlowActiveBoxes = (int) $activeRows->count();

            $fastFlowAvailableUnits = (int) $activeRows->sum(function ($row) use ($hasQuickBoxCurrentUnits, $hasQuickBoxReservedUnits) {
                $current = $hasQuickBoxCurrentUnits ? (int) ($row->current_units ?? 0) : 0;
                $reserved = $hasQuickBoxReservedUnits ? (int) ($row->reserved_units ?? 0) : 0;
                return max(0, $current - $reserved);
            });

            if ($hasQuickBoxBatchCode) {
                $fastFlowCount = (int) $activeRows
                    ->pluck('batch_code')
                    ->filter(fn ($v) => filled($v))
                    ->unique()
                    ->count();
            } else {
                $fastFlowCount = (int) $fastFlowActiveBoxes;
            }

            if ($hasQuickBoxReceivedAt) {
                $fastFlowInboundToday = (int) $quickBoxRows
                    ->filter(fn ($row) => !empty($row->received_at) && Carbon::parse($row->received_at)->toDateString() === $today)
                    ->count();
            } else {
                $fastFlowInboundToday = (int) $movementRows
                    ->filter(fn ($row) => !empty($row['when']) && str_starts_with((string) $row['when'], $today))
                    ->filter(fn ($row) => str_starts_with((string) ($row['type'] ?? ''), 'fast_in'))
                    ->sum('qty');
            }

            $fastFlowOutboundToday = (int) $movementRows
                ->filter(fn ($row) => !empty($row['when']) && str_starts_with((string) $row['when'], $today))
                ->filter(function ($row) {
                    $type = strtolower((string) ($row['type'] ?? ''));
                    return in_array($type, ['fast_out', 'fast_out_partial'], true);
                })
                ->sum('qty');

            $itemIds = $quickBoxRows->pluck('catalog_item_id')->filter()->unique()->values()->all();
            $warehouseIds = $quickBoxRows->pluck('warehouse_id')->filter()->unique()->values()->all();

            $itemMap = empty($itemIds)
                ? collect()
                : CatalogItem::query()->whereIn('id', $itemIds)->get(['id', 'name', 'sku'])->keyBy('id');

            $warehouseMap = empty($warehouseIds)
                ? collect()
                : Warehouse::query()->whereIn('id', $warehouseIds)->get(['id', 'name', 'code'])->keyBy('id');

            if ($hasQuickBoxBatchCode) {
                $fastFlowItems = $quickBoxRows
                    ->groupBy('batch_code')
                    ->map(function ($rows, $batchCode) use ($itemMap, $warehouseMap, $hasQuickBoxBoxNumber, $hasQuickBoxCurrentUnits, $hasQuickBoxStatus, $hasQuickBoxReservedUnits) {
                        $first = $hasQuickBoxBoxNumber
                            ? $rows->sortBy('box_number')->first()
                            : $rows->first();

                        $item = $itemMap[(int) ($first->catalog_item_id ?? 0)] ?? null;
                        $warehouse = $warehouseMap[(int) ($first->warehouse_id ?? 0)] ?? null;

                        $availableRows = $rows->filter(function ($row) use ($hasQuickBoxStatus) {
                            if (!$hasQuickBoxStatus) {
                                return true;
                            }
                            return in_array((string) ($row->status ?? ''), ['available', 'partial'], true);
                        });

                        return [
                            'batch_code' => (string) ($batchCode ?: '—'),
                            'product_name' => optional($item)->name,
                            'sku' => optional($item)->sku,
                            'warehouse_name' => optional($warehouse)->name,
                            'boxes_count' => (int) $rows->count(),
                            'available_boxes' => (int) $availableRows->count(),
                            'available_units' => (int) $availableRows->sum(function ($row) use ($hasQuickBoxCurrentUnits, $hasQuickBoxReservedUnits) {
                                $current = $hasQuickBoxCurrentUnits ? (int) ($row->current_units ?? 0) : 0;
                                $reserved = $hasQuickBoxReservedUnits ? (int) ($row->reserved_units ?? 0) : 0;
                                return max(0, $current - $reserved);
                            }),
                            'status' => $availableRows->count() > 0 ? 'activo' : 'cerrado',
                            'received_at' => $first->received_at ?? $first->created_at,
                            'created_at' => $first->created_at,
                        ];
                    })
                    ->sortByDesc(function ($row) {
                        return $row['received_at'] ? Carbon::parse($row['received_at'])->timestamp : 0;
                    })
                    ->take(12)
                    ->values();
            } else {
                $fastFlowItems = $quickBoxRows
                    ->map(function ($row) use ($itemMap, $warehouseMap, $hasQuickBoxCurrentUnits) {
                        $item = $itemMap[(int) ($row->catalog_item_id ?? 0)] ?? null;
                        $warehouse = $warehouseMap[(int) ($row->warehouse_id ?? 0)] ?? null;

                        return [
                            'batch_code' => (string) ($row->label_code ?? $row->id),
                            'product_name' => optional($item)->name,
                            'sku' => optional($item)->sku,
                            'warehouse_name' => optional($warehouse)->name,
                            'boxes_count' => 1,
                            'available_boxes' => 1,
                            'available_units' => $hasQuickBoxCurrentUnits ? (int) ($row->current_units ?? 0) : 0,
                            'status' => (string) ($row->status ?? 'activo'),
                            'received_at' => $row->received_at ?? $row->created_at,
                            'created_at' => $row->created_at,
                        ];
                    })
                    ->take(12)
                    ->values();
            }
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
                'subtitle' => number_format($completedOrders) . ' completadas',
                'icon' => 'clipboard',
                'color' => 'rose',
            ],
            [
                'title' => 'Ocupación Almacén',
                'value' => $occupancyRate . '%',
                'subtitle' => number_format($usedLocations) . ' / ' . number_format($totalLocations) . ' ubicaciones',
                'icon' => 'swap',
                'color' => 'cyan',
            ],
            [
                'title' => 'Fast Flow',
                'value' => number_format($fastFlowCount),
                'subtitle' => 'lotes activos',
                'icon' => 'package',
                'color' => 'blue',
            ],
        ];

        return [
            'period' => $period,
            'warehouseId' => $warehouseId,

            'kpis' => $kpis,
            'trendData' => $trendData,
            'categoryChartData' => $categoryChartData,
            'topProducts' => $topProducts,
            'topMovedProducts' => $topMovedProducts,
            'movTypeData' => $movTypeData,
            'activityBySource' => $activityBySource,
            'activityByGroup' => $activityByGroup,
            'recentMovements' => $recentMovements,
            'auditTimeline' => $auditTimeline,
            'lowStockCount' => $lowStockCount,
            'lowStockProducts' => $lowStockProducts instanceof Collection ? $lowStockProducts->values()->all() : $lowStockProducts,

            'productsCount' => $productsCount,
            'productsUnits' => $totalStock,

            'locationsCount' => $totalLocations,
            'availableLocations' => $availableLocations,

            'todayMovementsCount' => $todayMovementsCount,

            'providersCount' => $providersCount,
            'clientsCount' => $clientsCount,

            'pendingPickingCount' => $pendingOrders,
            'inProgressPickingCount' => (int) ($pickingsSummary['in_progress'] ?? 0),
            'completedPickingCount' => (int) ($pickingsSummary['completed'] ?? 0),
            'fastFlowCount' => $fastFlowCount,
            'fastFlowActiveBoxes' => $fastFlowActiveBoxes,
            'fastFlowItems' => $fastFlowItems instanceof Collection ? $fastFlowItems->values()->all() : $fastFlowItems,
            'fastFlowAvailableUnits' => $fastFlowAvailableUnits,
            'fastFlowInboundToday' => $fastFlowInboundToday,
            'fastFlowOutboundToday' => $fastFlowOutboundToday,

            'auditEventsCount' => (int) $timeline->count(),
            'movementEventsCount' => (int) $movementRows->count(),
            'entryCount' => $entryCount,
            'exitCount' => $exitCount,
            'transferCount' => $transferCount,
            'adjustCount' => $adjustCount,
            'transferQty' => $transferQty,
            'adjustQty' => $adjustQty,

            'totalStock' => $totalStock,
            'totalEntries' => $totalEntries,
            'totalExits' => $totalExits,
            'pendingOrders' => $pendingOrders,
            'completedOrders' => $completedOrders,
            'totalLocations' => $totalLocations,
            'usedLocations' => $usedLocations,
            'occupancyRate' => $occupancyRate,
        ];
    }

    public function dashboard(Request $request)
    {
        $data = $this->buildAnalyticsData($request);

        $warehouseId = (int) ($data['warehouseId'] ?? 0);

        $warehouseName = 'Almacén principal';
        if ($warehouseId > 0) {
            $warehouse = Warehouse::query()->find($warehouseId, ['id', 'name', 'code']);
            if ($warehouse) {
                $warehouseName = (string) ($warehouse->name ?: $warehouse->code ?: 'Almacén principal');
            }
        } else {
            $warehouse = Warehouse::query()->orderBy('name')->first(['id', 'name', 'code']);
            if ($warehouse) {
                $warehouseName = (string) ($warehouse->name ?: $warehouse->code ?: 'Almacén principal');
            }
        }

        return view('admin.wms.home', array_merge($data, [
            'warehouseName' => $warehouseName,
            'shipmentCount' => 0,
            'draftShipmentCount' => 0,
            'loadingShipmentCount' => 0,
            'partialShipmentCount' => 0,
            'dispatchedShipmentCount' => 0,
        ]));
    }

    private function buildUnifiedAuditTimeline(int $warehouseId, ?string $from = null, ?string $to = null, string $q = ''): Collection
    {
        $rows = collect()
            ->concat($this->manualMovementRows($warehouseId, $from, $to))
            ->concat($this->inventoryMovementRows($warehouseId, $from, $to))
            ->concat($this->receptionEntryRows($warehouseId, $from, $to))
            ->concat($this->pickWaveAuditRows($warehouseId, $from, $to))
            ->values();

        if ($q !== '') {
            $rows = $this->applyAuditQueryFilter($rows, $q);
        }

        return $rows->values();
    }

    private function receptionEntryRows(int $warehouseId, ?string $from, ?string $to): Collection
    {
        if (!Schema::hasTable('wms_receptions') || !Schema::hasTable('wms_reception_lines')) {
            return collect();
        }

        $hasReceptionStatus = Schema::hasColumn('wms_receptions', 'status');
        $hasReceptionDate = Schema::hasColumn('wms_receptions', 'reception_date');
        $hasCreatedBy = Schema::hasColumn('wms_receptions', 'created_by');

        $receptionLineReceptionFk = $this->firstExistingColumn('wms_reception_lines', [
            'wms_reception_id',
            'reception_id',
        ]);

        if (!$receptionLineReceptionFk) {
            return collect();
        }

        $lineLocationColumn = $this->firstExistingColumn('wms_reception_lines', ['location_id']);
        $lineQtyColumn = $this->firstExistingColumn('wms_reception_lines', ['quantity', 'qty']);
        $lineItemColumn = $this->firstExistingColumn('wms_reception_lines', ['catalog_item_id']);
        $lineSkuColumn = $this->firstExistingColumn('wms_reception_lines', ['sku']);
        $lineNameColumn = $this->firstExistingColumn('wms_reception_lines', ['name']);
        $lineDescColumn = $this->firstExistingColumn('wms_reception_lines', ['description']);

        if (!$lineQtyColumn) {
            return collect();
        }

        $query = WmsReception::query()
            ->when($hasReceptionStatus, fn ($q) => $q->where('status', 'firmado'))
            ->when($from, function ($q) use ($hasReceptionDate, $from) {
                $col = $hasReceptionDate ? 'reception_date' : 'created_at';
                $q->whereDate($col, '>=', $from);
            })
            ->when($to, function ($q) use ($hasReceptionDate, $to) {
                $col = $hasReceptionDate ? 'reception_date' : 'created_at';
                $q->whereDate($col, '<=', $to);
            })
            ->orderByDesc('id')
            ->limit(1000);

        $receptions = $query->get();

        if ($receptions->isEmpty()) {
            return collect();
        }

        $receptionIds = $receptions->pluck('id')->all();
        $receptionMap = $receptions->keyBy('id');

        $lines = DB::table('wms_reception_lines')
            ->whereIn($receptionLineReceptionFk, $receptionIds)
            ->orderByDesc('id')
            ->limit(5000)
            ->get();

        if ($lines->isEmpty()) {
            return collect();
        }

        $locationIds = $lineLocationColumn ? collect($lines)->pluck($lineLocationColumn)->filter()->unique()->values()->all() : [];
        $itemIds = $lineItemColumn ? collect($lines)->pluck($lineItemColumn)->filter()->unique()->values()->all() : [];
        $userIds = $hasCreatedBy ? $receptions->pluck('created_by')->filter()->unique()->values()->all() : [];

        $locationMap = empty($locationIds)
            ? collect()
            : Location::query()->whereIn('id', $locationIds)->get(['id', 'code', 'warehouse_id'])->keyBy('id');

        if ($warehouseId > 0 && $locationMap->isNotEmpty()) {
            $allowedLocationIds = $locationMap
                ->filter(fn ($loc) => (int) ($loc->warehouse_id ?? 0) === $warehouseId)
                ->keys()
                ->map(fn ($id) => (int) $id)
                ->all();
        } else {
            $allowedLocationIds = [];
        }

        $itemMap = empty($itemIds)
            ? collect()
            : CatalogItem::query()->whereIn('id', $itemIds)->get($this->catalogMapColumns())->keyBy('id');

        $userMap = empty($userIds)
            ? collect()
            : User::query()->whereIn('id', $userIds)->get(['id', 'name'])->keyBy('id');

        return collect($lines)
            ->map(function ($line) use (
                $receptionMap,
                $locationMap,
                $itemMap,
                $userMap,
                $receptionLineReceptionFk,
                $lineLocationColumn,
                $lineQtyColumn,
                $lineItemColumn,
                $lineSkuColumn,
                $lineNameColumn,
                $lineDescColumn,
                $warehouseId,
                $allowedLocationIds,
                $hasCreatedBy
            ) {
                $receptionId = (int) ($line->{$receptionLineReceptionFk} ?? 0);
                $reception = $receptionMap[$receptionId] ?? null;

                if (!$reception) {
                    return null;
                }

                $locationId = $lineLocationColumn ? (int) ($line->{$lineLocationColumn} ?? 0) : 0;

                if ($warehouseId > 0 && !empty($allowedLocationIds) && !in_array($locationId, $allowedLocationIds, true)) {
                    return null;
                }

                $location = $locationMap[$locationId] ?? null;
                $itemId = $lineItemColumn ? (int) ($line->{$lineItemColumn} ?? 0) : 0;
                $item = $itemMap[$itemId] ?? null;
                $user = $hasCreatedBy ? ($userMap[(int) ($reception->created_by ?? 0)] ?? null) : null;

                $when = $reception->reception_date ?? $reception->created_at;
                if (!$when instanceof Carbon && !empty($when)) {
                    $when = Carbon::parse($when);
                }

                return [
                    'event_id' => 'reception-line-' . ($line->id ?? uniqid()),
                    'source' => 'wms_receptions',
                    'when' => $when ? $when->format('Y-m-d H:i:s') : null,
                    'timestamp' => $when ? $when->timestamp : 0,
                    'group' => 'entry',
                    'type' => 'reception_signed',
                    'warehouse_id' => (int) (optional($location)->warehouse_id ?? 0),
                    'user_id' => $reception->created_by ?? null,
                    'user_name' => optional($user)->name ?: (string) ($reception->receiver_name ?? $reception->deliverer_name ?? ''),
                    'item_id' => $itemId,
                    'name' => optional($item)->name ?: (string) ($lineNameColumn ? ($line->{$lineNameColumn} ?? '') : ($lineDescColumn ? ($line->{$lineDescColumn} ?? '') : 'Producto')),
                    'sku' => optional($item)->sku ?: (string) ($lineSkuColumn ? ($line->{$lineSkuColumn} ?? '') : ''),
                    'gtin' => optional($item)->meli_gtin,
                    'qty' => (int) ($line->{$lineQtyColumn} ?? 0),
                    'from_location' => null,
                    'to_location' => optional($location)->code,
                    'location' => optional($location)->code,
                    'stock_before' => 0,
                    'stock_after' => 0,
                    'inv_before' => 0,
                    'inv_after' => 0,
                    'note' => 'Entrada por recepción firmada',
                    'reference' => (string) ($reception->folio ?? ('REC-' . $reception->id)),
                    'meta' => [
                        'reception_id' => (int) $reception->id,
                        'reception_line_id' => (int) ($line->id ?? 0),
                        'status' => (string) ($reception->status ?? ''),
                    ],
                ];
            })
            ->filter()
            ->values();
    }

    private function manualMovementRows(int $warehouseId, ?string $from, ?string $to): Collection
    {
        if (!Schema::hasTable('wms_movements') || !Schema::hasTable('wms_movement_lines')) {
            return collect();
        }

        $lineQtyColumn = $this->wmsMovementLineQtyColumn();
        if (!$lineQtyColumn) {
            return collect();
        }

        $movementsQuery = WmsMovement::query()
            ->when($warehouseId > 0 && Schema::hasColumn('wms_movements', 'warehouse_id'), fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->orderByDesc('id')
            ->limit(2000);

        $movements = $movementsQuery->get();
        if ($movements->isEmpty()) {
            return collect();
        }

        $movementIds = $movements->pluck('id')->all();
        $movementMap = $movements->keyBy('id');

        $lines = WmsMovementLine::query()
            ->whereIn('movement_id', $movementIds)
            ->orderByDesc('id')
            ->limit(4000)
            ->get();

        if ($lines->isEmpty()) {
            return collect();
        }

        $itemIds = $lines->pluck('catalog_item_id')->filter()->unique()->values()->all();
        $locationIds = $lines->pluck('location_id')->filter()->unique()->values()->all();
        $userIds = $movements->pluck('user_id')->filter()->unique()->values()->all();

        $itemMap = empty($itemIds)
            ? collect()
            : CatalogItem::query()->whereIn('id', $itemIds)->get($this->catalogMapColumns())->keyBy('id');

        $locationMap = empty($locationIds)
            ? collect()
            : Location::query()->whereIn('id', $locationIds)->get(['id', 'code', 'warehouse_id'])->keyBy('id');

        $userMap = empty($userIds)
            ? collect()
            : User::query()->whereIn('id', $userIds)->get(['id', 'name'])->keyBy('id');

        return $lines->map(function ($line) use ($movementMap, $itemMap, $locationMap, $userMap, $lineQtyColumn) {
            $movement = $movementMap[$line->movement_id] ?? null;
            if (!$movement) {
                return null;
            }

            $item = $itemMap[(int) ($line->catalog_item_id ?? 0)] ?? null;
            $location = $locationMap[(int) ($line->location_id ?? 0)] ?? null;
            $user = $userMap[(int) ($movement->user_id ?? 0)] ?? null;
            $type = strtolower((string) ($movement->type ?? 'manual'));
            $when = $movement->created_at;
            $qty = (int) ($line->{$lineQtyColumn} ?? 0);

            return [
                'event_id' => 'wms-line-' . $line->id,
                'source' => 'wms_movements',
                'when' => $when ? $when->format('Y-m-d H:i:s') : null,
                'timestamp' => $when ? $when->timestamp : 0,
                'group' => $this->normalizeMovementGroup($type),
                'type' => $type,
                'warehouse_id' => (int) ($movement->warehouse_id ?? 0),
                'user_id' => $movement->user_id,
                'user_name' => optional($user)->name,
                'item_id' => (int) ($line->catalog_item_id ?? 0),
                'name' => optional($item)->name,
                'sku' => optional($item)->sku,
                'gtin' => optional($item)->meli_gtin,
                'qty' => $qty,
                'from_location' => in_array($type, ['out', 'exit', 'salida'], true) ? optional($location)->code : null,
                'to_location' => in_array($type, ['in', 'entry', 'entrada'], true) ? optional($location)->code : null,
                'location' => optional($location)->code,
                'stock_before' => (int) ($line->stock_before ?? 0),
                'stock_after' => (int) ($line->stock_after ?? 0),
                'inv_before' => (int) ($line->inv_before ?? 0),
                'inv_after' => (int) ($line->inv_after ?? 0),
                'note' => (string) ($movement->note ?? ''),
                'reference' => 'WMS-' . $movement->id,
                'meta' => [
                    'movement_id' => $movement->id,
                    'movement_line_id' => $line->id,
                ],
            ];
        })->filter()->values();
    }

    private function inventoryMovementRows(int $warehouseId, ?string $from, ?string $to): Collection
    {
        if (!Schema::hasTable('inventory_movements')) {
            return collect();
        }

        $qtyColumn = $this->inventoryMovementQtyColumn();
        if (!$qtyColumn) {
            return collect();
        }

        $locationIds = $this->warehouseLocationIds($warehouseId, Schema::hasColumn('locations', 'warehouse_id'));

        $query = InventoryMovement::query()
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->when($warehouseId > 0 && $locationIds->isNotEmpty(), function ($q) use ($locationIds) {
                $q->where(function ($qq) use ($locationIds) {
                    if (Schema::hasColumn('inventory_movements', 'from_location_id')) {
                        $qq->orWhereIn('from_location_id', $locationIds);
                    }
                    if (Schema::hasColumn('inventory_movements', 'to_location_id')) {
                        $qq->orWhereIn('to_location_id', $locationIds);
                    }
                });
            })
            ->orderByDesc('id')
            ->limit(5000);

        $movements = $query->get();
        if ($movements->isEmpty()) {
            return collect();
        }

        $itemIds = $movements->pluck('catalog_item_id')->filter()->unique()->values()->all();
        $userIds = $movements->pluck('user_id')->filter()->unique()->values()->all();
        $locIds = $movements->pluck('from_location_id')
            ->merge($movements->pluck('to_location_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $itemMap = empty($itemIds)
            ? collect()
            : CatalogItem::query()->whereIn('id', $itemIds)->get($this->catalogMapColumns())->keyBy('id');

        $locationMap = empty($locIds)
            ? collect()
            : Location::query()->whereIn('id', $locIds)->get(['id', 'code', 'warehouse_id'])->keyBy('id');

        $userMap = empty($userIds)
            ? collect()
            : User::query()->whereIn('id', $userIds)->get(['id', 'name'])->keyBy('id');

        return $movements->map(function ($mov) use ($itemMap, $locationMap, $userMap, $qtyColumn) {
            $meta = $this->arrayMeta($mov->meta ?? null);

            if (($meta['source'] ?? null) === 'wms_movements' || !empty($meta['wms_movement_id'])) {
                return null;
            }

            $item = $itemMap[(int) ($mov->catalog_item_id ?? 0)] ?? null;
            $fromLocation = $locationMap[(int) ($mov->from_location_id ?? 0)] ?? null;
            $toLocation = $locationMap[(int) ($mov->to_location_id ?? 0)] ?? null;
            $user = $userMap[(int) ($mov->user_id ?? 0)] ?? null;
            $type = strtolower((string) ($mov->type ?? 'movement'));
            $when = $mov->created_at;

            return [
                'event_id' => 'inv-mov-' . $mov->id,
                'source' => 'inventory_movements',
                'when' => $when ? $when->format('Y-m-d H:i:s') : null,
                'timestamp' => $when ? $when->timestamp : 0,
                'group' => $this->normalizeMovementGroup($type),
                'type' => $type,
                'warehouse_id' => (int) (
                    $meta['warehouse_id']
                    ?? optional($toLocation)->warehouse_id
                    ?? optional($fromLocation)->warehouse_id
                    ?? 0
                ),
                'user_id' => $mov->user_id,
                'user_name' => optional($user)->name,
                'item_id' => (int) ($mov->catalog_item_id ?? 0),
                'name' => optional($item)->name,
                'sku' => optional($item)->sku,
                'gtin' => optional($item)->meli_gtin,
                'qty' => (int) ($mov->{$qtyColumn} ?? 0),
                'from_location' => optional($fromLocation)->code,
                'to_location' => optional($toLocation)->code,
                'location' => optional($toLocation)->code ?: optional($fromLocation)->code,
                'stock_before' => (int) ($meta['before_stock'] ?? 0),
                'stock_after' => (int) ($meta['after_stock'] ?? 0),
                'inv_before' => (int) ($meta['before'] ?? 0),
                'inv_after' => (int) ($meta['after'] ?? 0),
                'note' => (string) ($mov->notes ?? ''),
                'reference' => (string) ($meta['batch_code'] ?? $meta['task_number'] ?? ('INV-' . $mov->id)),
                'meta' => $meta,
            ];
        })->filter()->values();
    }

    private function pickWaveAuditRows(int $warehouseId, ?string $from, ?string $to): Collection
    {
        if (!Schema::hasTable('pick_waves')) {
            return collect();
        }

        $cols = $this->pickWaveColumns();

        $query = PickWave::query()
            ->when($warehouseId > 0 && $cols['warehouse_id'], fn ($q) => $q->where($cols['warehouse_id'], $warehouseId))
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->orderByDesc('id')
            ->limit(1000);

        $tasks = $query->get();
        if ($tasks->isEmpty()) {
            return collect();
        }

        $rows = collect();

        foreach ($tasks as $task) {
            $bag = $this->pickWaveBag($task);

            $taskNumber = (string) (
                $bag['task_number']
                ?? ($cols['task_number'] ? ($task->{$cols['task_number']} ?? '') : '')
                ?? ('PICK-' . $task->id)
            );

            $orderNumber = (string) (
                $bag['order_number']
                ?? ($cols['order_number'] ? ($task->{$cols['order_number']} ?? '') : '')
                ?? ''
            );

            $status = $this->normalizePickStatus(
                $bag['status']
                ?? ($cols['status'] ? ($task->{$cols['status']} ?? null) : null)
            );

            $items = collect(is_array($bag['items'] ?? null) ? $bag['items'] : []);
            $requestedQty = (int) $items->sum(function ($item) {
                return (int) ($item['quantity_required'] ?? $item['qty'] ?? 0);
            });

            $assignedUserId = (int) (
                $bag['assigned_user_id']
                ?? ($cols['assigned_user_id'] ? ($task->{$cols['assigned_user_id']} ?? 0) : 0)
            );

            $assignedTo = trim((string) (
                $bag['assigned_to']
                ?? ($cols['assigned_to'] ? ($task->{$cols['assigned_to']} ?? '') : '')
            ));

            if ($assignedTo === '' && $assignedUserId > 0) {
                $assignedTo = (string) optional(User::query()->find($assignedUserId, ['id', 'name']))->name;
            }

            $metaBase = [
                'pick_wave_id' => $task->id,
                'task_number' => $taskNumber,
                'order_number' => $orderNumber,
                'status' => $status,
                'items_count' => (int) $items->count(),
                'requested_qty' => $requestedQty,
                'stock_reserved' => (bool) ($bag['stock_reserved'] ?? false),
                'stock_consumed' => (bool) ($bag['stock_consumed'] ?? false),
                'assigned_user_id' => $assignedUserId,
                'assigned_to' => $assignedTo,
            ];

            if ($task->created_at) {
                $rows->push([
                    'event_id' => 'pick-created-' . $task->id,
                    'source' => 'pick_waves',
                    'when' => $task->created_at->format('Y-m-d H:i:s'),
                    'timestamp' => $task->created_at->timestamp,
                    'group' => 'picking',
                    'type' => 'pick_created',
                    'warehouse_id' => (int) ($bag['warehouse_id'] ?? ($cols['warehouse_id'] ? ($task->{$cols['warehouse_id']} ?? 0) : 0)),
                    'user_id' => $assignedUserId ?: null,
                    'user_name' => $assignedTo !== '' ? $assignedTo : null,
                    'item_id' => null,
                    'name' => 'Tarea de picking',
                    'sku' => null,
                    'gtin' => null,
                    'qty' => $requestedQty,
                    'from_location' => null,
                    'to_location' => null,
                    'location' => null,
                    'stock_before' => 0,
                    'stock_after' => 0,
                    'inv_before' => 0,
                    'inv_after' => 0,
                    'note' => 'Tarea creada',
                    'reference' => $taskNumber,
                    'meta' => $metaBase,
                ]);
            }

            $startedAt = $this->safeCarbon($bag['started_at'] ?? ($cols['started_at'] ? ($task->{$cols['started_at']} ?? null) : null));
            if ($startedAt) {
                $rows->push([
                    'event_id' => 'pick-started-' . $task->id,
                    'source' => 'pick_waves',
                    'when' => $startedAt->format('Y-m-d H:i:s'),
                    'timestamp' => $startedAt->timestamp,
                    'group' => 'picking',
                    'type' => 'pick_started',
                    'warehouse_id' => (int) ($bag['warehouse_id'] ?? ($cols['warehouse_id'] ? ($task->{$cols['warehouse_id']} ?? 0) : 0)),
                    'user_id' => $assignedUserId ?: null,
                    'user_name' => $assignedTo !== '' ? $assignedTo : null,
                    'item_id' => null,
                    'name' => 'Tarea de picking',
                    'sku' => null,
                    'gtin' => null,
                    'qty' => $requestedQty,
                    'from_location' => null,
                    'to_location' => null,
                    'location' => null,
                    'stock_before' => 0,
                    'stock_after' => 0,
                    'inv_before' => 0,
                    'inv_after' => 0,
                    'note' => 'Tarea iniciada',
                    'reference' => $taskNumber,
                    'meta' => $metaBase,
                ]);
            }

            $completedAt = $this->safeCarbon($bag['completed_at'] ?? ($cols['completed_at'] ? ($task->{$cols['completed_at']} ?? null) : null));
            if ($completedAt) {
                $rows->push([
                    'event_id' => 'pick-completed-' . $task->id,
                    'source' => 'pick_waves',
                    'when' => $completedAt->format('Y-m-d H:i:s'),
                    'timestamp' => $completedAt->timestamp,
                    'group' => 'picking',
                    'type' => 'pick_completed',
                    'warehouse_id' => (int) ($bag['warehouse_id'] ?? ($cols['warehouse_id'] ? ($task->{$cols['warehouse_id']} ?? 0) : 0)),
                    'user_id' => $assignedUserId ?: null,
                    'user_name' => $assignedTo !== '' ? $assignedTo : null,
                    'item_id' => null,
                    'name' => 'Tarea de picking',
                    'sku' => null,
                    'gtin' => null,
                    'qty' => $requestedQty,
                    'from_location' => null,
                    'to_location' => null,
                    'location' => null,
                    'stock_before' => 0,
                    'stock_after' => 0,
                    'inv_before' => 0,
                    'inv_after' => 0,
                    'note' => 'Tarea completada',
                    'reference' => $taskNumber,
                    'meta' => $metaBase,
                ]);
            }
        }

        return $rows->values();
    }

    private function pickingsSummary(int $warehouseId): array
    {
        if (!Schema::hasTable('pick_waves')) {
            return [
                'pending' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'cancelled' => 0,
                'total' => 0,
            ];
        }

        $cols = $this->pickWaveColumns();

        $tasks = PickWave::query()
            ->when($warehouseId > 0 && $cols['warehouse_id'], fn ($q) => $q->where($cols['warehouse_id'], $warehouseId))
            ->limit(5000)
            ->get();

        $summary = [
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'total' => 0,
        ];

        foreach ($tasks as $task) {
            $bag = $this->pickWaveBag($task);
            $status = $this->normalizePickStatus(
                $bag['status']
                ?? ($cols['status'] ? ($task->{$cols['status']} ?? null) : null)
            );

            if (!array_key_exists($status, $summary)) {
                $status = 'pending';
            }

            $summary[$status]++;
            $summary['total']++;
        }

        return $summary;
    }

    private function filteredAuditRowsFromRequest(Request $request): Collection
    {
        $period = (int) $request->get('period', 30);
        if (!in_array($period, [7, 30, 90, 180, 365], true)) {
            $period = 30;
        }

        $warehouseId = (int) $request->get('warehouse_id', 0);
        $q = trim((string) $request->get('q', ''));
        $group = trim((string) $request->get('group', ''));
        $source = trim((string) $request->get('source', ''));
        $type = trim((string) $request->get('type', ''));

        $from = now()->subDays($period)->startOfDay()->toDateString();
        $to = now()->endOfDay()->toDateString();

        $rows = $this->buildUnifiedAuditTimeline($warehouseId, $from, $to, $q);

        if ($group !== '') {
            $rows = $rows->filter(fn ($row) => strtolower((string) ($row['group'] ?? '')) === strtolower($group));
        }

        if ($source !== '') {
            $rows = $rows->filter(fn ($row) => strtolower((string) ($row['source'] ?? '')) === strtolower($source));
        }

        if ($type !== '') {
            $rows = $rows->filter(fn ($row) => strtolower((string) ($row['type'] ?? '')) === strtolower($type));
        }

        return $rows
            ->sortByDesc('timestamp')
            ->values();
    }

    private function buildAuditAiContext(Request $request, Collection $rows, array $analytics): array
    {
        $todayStart = now()->copy()->startOfDay();
        $tomorrowStart = now()->copy()->addDay()->startOfDay();
        $yesterdayStart = now()->copy()->subDay()->startOfDay();

        $exitRows = $rows->filter(fn ($row) => ($row['group'] ?? '') === 'exit')->values();
        $entryRows = $rows->filter(fn ($row) => ($row['group'] ?? '') === 'entry')->values();

        $recentRows = $rows->take(300)->map(function ($row) {
            return [
                'when' => $row['when'] ?? null,
                'group' => $row['group'] ?? null,
                'type' => $row['type'] ?? null,
                'source' => $row['source'] ?? null,
                'product' => $row['name'] ?? null,
                'sku' => $row['sku'] ?? null,
                'qty' => (int) ($row['qty'] ?? 0),
                'from_location' => $row['from_location'] ?? null,
                'to_location' => $row['to_location'] ?? null,
                'location' => $row['location'] ?? null,
                'user_name' => $row['user_name'] ?? null,
                'reference' => $row['reference'] ?? null,
                'note' => $row['note'] ?? null,
            ];
        })->values()->all();

        return [
            'analytics_snapshot' => [
                'total_stock' => (int) ($analytics['totalStock'] ?? 0),
                'total_entries' => (int) ($analytics['totalEntries'] ?? 0),
                'total_exits' => (int) ($analytics['totalExits'] ?? 0),
                'low_stock_count' => (int) ($analytics['lowStockCount'] ?? 0),
                'pending_picking_count' => (int) ($analytics['pendingPickingCount'] ?? 0),
                'in_progress_picking_count' => (int) ($analytics['inProgressPickingCount'] ?? 0),
                'completed_picking_count' => (int) ($analytics['completedPickingCount'] ?? 0),
                'fast_flow_count' => (int) ($analytics['fastFlowCount'] ?? 0),
                'fast_flow_available_units' => (int) ($analytics['fastFlowAvailableUnits'] ?? 0),
                'audit_events_count' => (int) ($analytics['auditEventsCount'] ?? 0),
            ],
            'top_picking_assignees' => $this->buildTopPickingAssignees($request),
            'movement_users_ranking' => $this->buildMovementUsersRanking($rows),
            'top_exit_products_period' => $this->summarizeProductsFromRows($exitRows, 25),
            'top_entry_products_period' => $this->summarizeProductsFromRows($entryRows, 25),
            'exit_products_today' => $this->summarizeProductsFromRows(
                $this->filterRowsBetween($exitRows, $todayStart, $tomorrowStart),
                25
            ),
            'exit_products_yesterday' => $this->summarizeProductsFromRows(
                $this->filterRowsBetween($exitRows, $yesterdayStart, $todayStart),
                25
            ),
            'exit_products_between_yesterday_and_today' => $this->summarizeProductsFromRows(
                $this->filterRowsBetween($exitRows, $yesterdayStart, $tomorrowStart),
                40
            ),
            'entry_products_between_yesterday_and_today' => $this->summarizeProductsFromRows(
                $this->filterRowsBetween($entryRows, $yesterdayStart, $tomorrowStart),
                40
            ),
            'low_stock_products' => collect($analytics['lowStockProducts'] ?? [])->take(20)->values()->all(),
            'fast_flow_items' => collect($analytics['fastFlowItems'] ?? [])->take(20)->values()->all(),
            'recent_rows' => $recentRows,
        ];
    }

    private function buildTopPickingAssignees(Request $request): array
    {
        if (!Schema::hasTable('pick_waves')) {
            return [];
        }

        $period = (int) $request->get('period', 30);
        if (!in_array($period, [7, 30, 90, 180, 365], true)) {
            $period = 30;
        }

        $warehouseId = (int) $request->get('warehouse_id', 0);
        $from = now()->subDays($period)->startOfDay()->toDateString();
        $to = now()->endOfDay()->toDateString();

        $cols = $this->pickWaveColumns();

        $tasks = PickWave::query()
            ->when($warehouseId > 0 && $cols['warehouse_id'], fn ($q) => $q->where($cols['warehouse_id'], $warehouseId))
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->orderByDesc('created_at')
            ->limit(3000)
            ->get();

        if ($tasks->isEmpty()) {
            return [];
        }

        $raw = [];
        $userIds = [];

        foreach ($tasks as $task) {
            $bag = $this->pickWaveBag($task);

            $assignedUserId = (int) (
                $bag['assigned_user_id']
                ?? ($cols['assigned_user_id'] ? ($task->{$cols['assigned_user_id']} ?? 0) : 0)
            );

            $assignedTo = trim((string) (
                $bag['assigned_to']
                ?? ($cols['assigned_to'] ? ($task->{$cols['assigned_to']} ?? '') : '')
            ));

            if ($assignedUserId > 0) {
                $userIds[] = $assignedUserId;
            }

            $items = collect(is_array($bag['items'] ?? null) ? $bag['items'] : []);
            $requestedQty = (int) $items->sum(function ($item) {
                return (int) ($item['quantity_required'] ?? $item['qty'] ?? 0);
            });

            $status = $this->normalizePickStatus(
                $bag['status']
                ?? ($cols['status'] ? ($task->{$cols['status']} ?? null) : null)
            );

            $raw[] = [
                'task_id' => $task->id,
                'task_number' => (string) (
                    $bag['task_number']
                    ?? ($cols['task_number'] ? ($task->{$cols['task_number']} ?? '') : '')
                    ?? ('PICK-' . $task->id)
                ),
                'assigned_user_id' => $assignedUserId,
                'assigned_to' => $assignedTo,
                'requested_qty' => $requestedQty,
                'status' => $status,
                'created_at' => optional($task->created_at)?->format('Y-m-d H:i:s'),
            ];
        }

        $userMap = empty($userIds)
            ? collect()
            : User::query()
                ->whereIn('id', array_values(array_unique($userIds)))
                ->get(['id', 'name'])
                ->keyBy('id');

        $grouped = [];

        foreach ($raw as $row) {
            $name = trim((string) $row['assigned_to']);

            if ($name === '' && (int) $row['assigned_user_id'] > 0) {
                $name = (string) optional($userMap[(int) $row['assigned_user_id']] ?? null)->name;
            }

            if ($name === '') {
                $name = 'Sin asignar';
            }

            if (!isset($grouped[$name])) {
                $grouped[$name] = [
                    'user_name' => $name,
                    'tasks_count' => 0,
                    'requested_qty' => 0,
                    'pending_count' => 0,
                    'in_progress_count' => 0,
                    'completed_count' => 0,
                    'task_numbers' => [],
                    'last_task_at' => null,
                ];
            }

            $grouped[$name]['tasks_count']++;
            $grouped[$name]['requested_qty'] += (int) $row['requested_qty'];
            $grouped[$name]['task_numbers'][] = $row['task_number'];

            if ($row['status'] === 'pending') {
                $grouped[$name]['pending_count']++;
            } elseif ($row['status'] === 'in_progress') {
                $grouped[$name]['in_progress_count']++;
            } elseif ($row['status'] === 'completed') {
                $grouped[$name]['completed_count']++;
            }

            $lastTaskAt = $grouped[$name]['last_task_at'];
            if (!$lastTaskAt || (($row['created_at'] ?? '') > $lastTaskAt)) {
                $grouped[$name]['last_task_at'] = $row['created_at'];
            }
        }

        return collect($grouped)
            ->map(function ($row) {
                $row['task_numbers'] = array_values(array_slice(array_unique($row['task_numbers']), 0, 8));
                return $row;
            })
            ->sort(function ($a, $b) {
                if ((int) $a['requested_qty'] === (int) $b['requested_qty']) {
                    return (int) $b['tasks_count'] <=> (int) $a['tasks_count'];
                }
                return (int) $b['requested_qty'] <=> (int) $a['requested_qty'];
            })
            ->values()
            ->take(20)
            ->all();
    }

    private function buildMovementUsersRanking(Collection $rows): array
    {
        return $rows
            ->filter(fn ($row) => filled($row['user_name'] ?? null))
            ->groupBy(fn ($row) => trim((string) ($row['user_name'] ?? 'Sin usuario')))
            ->map(function ($group, $name) {
                return [
                    'user_name' => $name,
                    'events_count' => (int) $group->count(),
                    'total_qty' => (int) $group->sum(fn ($row) => (int) ($row['qty'] ?? 0)),
                    'entry_events' => (int) $group->filter(fn ($row) => ($row['group'] ?? '') === 'entry')->count(),
                    'exit_events' => (int) $group->filter(fn ($row) => ($row['group'] ?? '') === 'exit')->count(),
                    'transfer_events' => (int) $group->filter(fn ($row) => ($row['group'] ?? '') === 'transfer')->count(),
                    'adjustment_events' => (int) $group->filter(fn ($row) => ($row['group'] ?? '') === 'adjustment')->count(),
                    'last_event_at' => (string) ($group->pluck('when')->filter()->first() ?? ''),
                ];
            })
            ->sort(function ($a, $b) {
                if ((int) $a['events_count'] === (int) $b['events_count']) {
                    return (int) $b['total_qty'] <=> (int) $a['total_qty'];
                }
                return (int) $b['events_count'] <=> (int) $a['events_count'];
            })
            ->values()
            ->take(20)
            ->all();
    }

    private function filterRowsBetween(Collection $rows, Carbon $from, Carbon $toExclusive): Collection
    {
        return $rows
            ->filter(function ($row) use ($from, $toExclusive) {
                $when = $this->safeCarbon($row['when'] ?? null);
                return $when && $when->gte($from) && $when->lt($toExclusive);
            })
            ->values();
    }

    private function summarizeProductsFromRows(Collection $rows, int $limit = 25): array
    {
        return $rows
            ->filter(function ($row) {
                return filled($row['name'] ?? null) || filled($row['sku'] ?? null);
            })
            ->groupBy(function ($row) {
                $id = (int) ($row['item_id'] ?? 0);
                $name = trim((string) ($row['name'] ?? 'Producto'));
                $sku = trim((string) ($row['sku'] ?? ''));
                return $id > 0 ? 'id:' . $id : mb_strtolower($name . '|' . $sku);
            })
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'product_name' => (string) ($first['name'] ?? 'Producto'),
                    'sku' => (string) ($first['sku'] ?? ''),
                    'qty' => (int) $group->sum(fn ($row) => (int) ($row['qty'] ?? 0)),
                    'movements' => (int) $group->count(),
                    'last_when' => (string) ($group->pluck('when')->filter()->first() ?? ''),
                    'references' => $group->pluck('reference')->filter()->take(6)->values()->all(),
                ];
            })
            ->sort(function ($a, $b) {
                if ((int) $a['qty'] === (int) $b['qty']) {
                    return (int) $b['movements'] <=> (int) $a['movements'];
                }
                return (int) $b['qty'] <=> (int) $a['qty'];
            })
            ->values()
            ->take($limit)
            ->all();
    }

    private function applyAuditQueryFilter(Collection $rows, string $q): Collection
    {
        $q = mb_strtolower(trim($q));
        if ($q === '') {
            return $rows;
        }

        return $rows->filter(function ($row) use ($q) {
            $blob = mb_strtolower(implode(' ', array_filter([
                (string) ($row['when'] ?? ''),
                (string) ($row['group'] ?? ''),
                (string) ($row['type'] ?? ''),
                (string) ($row['source'] ?? ''),
                (string) ($row['name'] ?? ''),
                (string) ($row['sku'] ?? ''),
                (string) ($row['gtin'] ?? ''),
                (string) ($row['from_location'] ?? ''),
                (string) ($row['to_location'] ?? ''),
                (string) ($row['location'] ?? ''),
                (string) ($row['user_name'] ?? ''),
                (string) ($row['reference'] ?? ''),
                (string) ($row['note'] ?? ''),
            ])));

            if (str_contains($blob, $q)) {
                return true;
            }

            if (is_numeric($q) && (int) ($row['item_id'] ?? 0) === (int) $q) {
                return true;
            }

            return false;
        })->values();
    }

    private function pickWaveColumns(): array
    {
        return [
            'json' => $this->firstExistingColumn('pick_waves', ['meta', 'data', 'payload', 'extra']),
            'items' => $this->firstExistingColumn('pick_waves', ['items', 'items_json']),
            'deliveries' => $this->firstExistingColumn('pick_waves', ['deliveries', 'deliveries_json']),
            'task_number' => $this->firstExistingColumn('pick_waves', ['task_number', 'code']),
            'order_number' => $this->firstExistingColumn('pick_waves', ['order_number', 'reference', 'order_ref']),
            'status' => $this->firstExistingColumn('pick_waves', ['status']),
            'started_at' => $this->firstExistingColumn('pick_waves', ['started_at']),
            'completed_at' => $this->firstExistingColumn('pick_waves', ['completed_at', 'finished_at']),
            'warehouse_id' => $this->firstExistingColumn('pick_waves', ['warehouse_id']),
            'assigned_to' => $this->firstExistingColumn('pick_waves', ['assigned_to', 'assignee_name', 'operator_name']),
            'assigned_user_id' => $this->firstExistingColumn('pick_waves', ['assigned_user_id']),
        ];
    }

    private function pickWaveBag(PickWave $task): array
    {
        $cols = $this->pickWaveColumns();
        $bag = [];

        if ($cols['json']) {
            $decoded = $this->decodePossibleJsonValue($task->{$cols['json']} ?? null);
            if (is_array($decoded)) {
                $bag = $decoded;
            }
        }

        if ($cols['items'] && empty($bag['items'])) {
            $decodedItems = $this->decodePossibleJsonValue($task->{$cols['items']} ?? null);
            if (is_array($decodedItems)) {
                $bag['items'] = $decodedItems;
            }
        }

        if ($cols['deliveries'] && empty($bag['deliveries'])) {
            $decodedDeliveries = $this->decodePossibleJsonValue($task->{$cols['deliveries']} ?? null);
            if (is_array($decodedDeliveries)) {
                $bag['deliveries'] = $decodedDeliveries;
            }
        }

        return $bag;
    }

    private function warehouseLocationIds(int $warehouseId, bool $hasLocationWarehouse = true): Collection
    {
        if ($warehouseId <= 0 || !$hasLocationWarehouse) {
            return collect();
        }

        return Location::query()
            ->where('warehouse_id', $warehouseId)
            ->pluck('id');
    }

    private function catalogMapColumns(): array
    {
        $cols = ['id', 'name', 'sku'];

        if (Schema::hasColumn('catalog_items', 'meli_gtin')) {
            $cols[] = 'meli_gtin';
        }

        return $cols;
    }

    private function wmsMovementLineQtyColumn(): ?string
    {
        if (Schema::hasColumn('wms_movement_lines', 'qty')) {
            return 'qty';
        }

        if (Schema::hasColumn('wms_movement_lines', 'quantity')) {
            return 'quantity';
        }

        return null;
    }

    private function inventoryMovementQtyColumn(): ?string
    {
        if (Schema::hasColumn('inventory_movements', 'qty')) {
            return 'qty';
        }

        if (Schema::hasColumn('inventory_movements', 'quantity')) {
            return 'quantity';
        }

        return null;
    }

    private function firstExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function normalizeMovementGroup(?string $type): string
    {
        $type = strtolower(trim((string) $type));

        return match (true) {
            in_array($type, ['in', 'entry', 'entrada', 'entradas', 'fast_in', 'fast_in_batch', 'manual_in', 'reception_signed'], true) => 'entry',
            in_array($type, ['out', 'exit', 'salida', 'salidas', 'fast_out', 'fast_out_partial', 'manual_out', 'pick_out', 'pick_complete', 'picking_out'], true) => 'exit',
            in_array($type, ['transfer', 'transferencia', 'traspaso'], true) => 'transfer',
            in_array($type, ['adjust', 'ajuste', 'inventory_adjustment', 'cycle_count', 'conteo'], true) => 'adjustment',
            str_contains($type, 'pick') => 'picking',
            default => 'other',
        };
    }

    private function normalizePickStatus($value): string
    {
        if ($value === null || $value === '') {
            return 'pending';
        }

        if (is_numeric($value)) {
            return match ((int) $value) {
                0 => 'pending',
                1 => 'in_progress',
                2 => 'completed',
                3, 9 => 'cancelled',
                default => 'pending',
            };
        }

        $value = strtolower(trim((string) $value));

        return match ($value) {
            'pending', 'in_progress', 'completed', 'cancelled' => $value,
            'open', 'draft', 'assigned', 'processing' => 'pending',
            'done', 'closed', 'finished' => 'completed',
            default => 'pending',
        };
    }

    private function decodePossibleJsonValue($value)
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }

    private function arrayMeta($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function safeCarbon($value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function extractOpenAiText(array $json): ?string
    {
        $rawText = '';

        if (isset($json['output']) && is_array($json['output'])) {
            foreach ($json['output'] as $outItem) {
                if (($outItem['type'] ?? null) === 'message' && isset($outItem['content'])) {
                    foreach ($outItem['content'] as $content) {
                        if (($content['type'] ?? null) === 'output_text' && isset($content['text'])) {
                            $rawText .= $content['text'];
                        }
                    }
                }
            }
        }

        if ($rawText !== '') {
            return trim($rawText);
        }

        return $json['output'][0]['content'][0]['text'] ?? null;
    }

    private function decodeAuditAiJson(string $rawText): ?array
    {
        $rawText = trim($rawText);

        $decoded = json_decode($rawText, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $rawText, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function normalizeAuditAiPayload(array $data, string $rawText = ''): array
    {
        $summaryPoints = collect($data['summary_points'] ?? [])
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->values()
            ->all();

        $evidence = collect($data['evidence'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->map(function ($row) {
                return [
                    'label' => trim((string) ($row['label'] ?? 'Dato')),
                    'value' => trim((string) ($row['value'] ?? '')),
                ];
            })
            ->filter(fn ($row) => $row['value'] !== '')
            ->values()
            ->all();

        $tables = collect($data['tables'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->map(function ($row) {
                $columns = collect($row['columns'] ?? [])
                    ->map(fn ($v) => trim((string) $v))
                    ->filter()
                    ->values()
                    ->all();

                $rows = collect($row['rows'] ?? [])
                    ->filter(fn ($tableRow) => is_array($tableRow))
                    ->map(function ($tableRow) {
                        return collect($tableRow)
                            ->map(fn ($v) => trim((string) $v))
                            ->values()
                            ->all();
                    })
                    ->values()
                    ->all();

                return [
                    'title' => trim((string) ($row['title'] ?? 'Tabla')),
                    'columns' => $columns,
                    'rows' => $rows,
                ];
            })
            ->filter(fn ($row) => !empty($row['columns']) && !empty($row['rows']))
            ->values()
            ->all();

        $actions = collect($data['actions'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->map(function ($row) {
                $priority = strtolower(trim((string) ($row['priority'] ?? 'media')));
                if (!in_array($priority, ['alta', 'media', 'baja'], true)) {
                    $priority = 'media';
                }

                return [
                    'title' => trim((string) ($row['title'] ?? 'Acción')),
                    'priority' => $priority,
                    'detail' => trim((string) ($row['detail'] ?? '')),
                ];
            })
            ->values()
            ->all();

        $followUp = collect($data['follow_up_data_needed'] ?? [])
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->values()
            ->all();

        $riskLevel = strtolower(trim((string) ($data['risk_level'] ?? 'informativo')));
        if (!in_array($riskLevel, ['alto', 'medio', 'bajo', 'informativo'], true)) {
            $riskLevel = 'informativo';
        }

        return [
            'headline' => trim((string) ($data['headline'] ?? 'Resultado de auditoría WMS')),
            'direct_answer' => trim((string) ($data['direct_answer'] ?? $rawText)),
            'summary_points' => $summaryPoints,
            'evidence' => $evidence,
            'tables' => $tables,
            'actions' => $actions,
            'follow_up_data_needed' => $followUp,
            'score' => max(0, min(100, (int) ($data['score'] ?? 0))),
            'risk_level' => $riskLevel,
            'pdf_title' => trim((string) ($data['pdf_title'] ?? 'Reporte IA WMS')),
            'raw_text' => $rawText,
        ];
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