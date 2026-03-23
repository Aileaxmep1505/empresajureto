<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\Warehouse;
use App\Models\WmsQuickBox;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class WmsFastFlowController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('auth')];
    }

    public function index()
    {
        $recentBoxes = WmsQuickBox::query()
            ->with([
                'item:id,name,sku',
                'warehouse:id,name,code',
                'location:id,code,name',
            ])
            ->orderByDesc('id')
            ->limit(60)
            ->get();

        $recentBatches = WmsQuickBox::query()
            ->with([
                'item:id,name,sku',
                'warehouse:id,name,code',
            ])
            ->orderByDesc('id')
            ->limit(300)
            ->get()
            ->groupBy('batch_code')
            ->map(function ($rows) {
                $first = $rows->sortBy('box_number')->first();

                return [
                    'batch_code'      => $first->batch_code,
                    'warehouse_name'  => optional($first->warehouse)->name,
                    'product_name'    => optional($first->item)->name,
                    'sku'             => optional($first->item)->sku,
                    'boxes_count'     => $rows->count(),
                    'units_per_box'   => (int) $first->units_per_box,
                    'total_units'     => $rows->sum(fn ($r) => (int) ($r->units_per_box ?? 0)),
                    'available_boxes' => $rows->whereIn('status', ['available', 'partial'])->count(),
                    'available_units' => $rows->whereIn('status', ['available', 'partial'])->sum(fn ($r) => (int) ($r->current_units ?? 0)),
                    'shipped_boxes'   => $rows->where('status', 'shipped')->count(),
                    'received_at'     => $first->received_at,
                    'created_at'      => $first->created_at,
                ];
            })
            ->values();

        $availableBoxes = (int) WmsQuickBox::query()
            ->whereIn('status', ['available', 'partial'])
            ->count();

        $availableUnits = (int) WmsQuickBox::query()
            ->whereIn('status', ['available', 'partial'])
            ->sum('current_units');

        $inboundToday = (int) WmsQuickBox::query()
            ->whereDate('received_at', now()->toDateString())
            ->count();

        $outboundToday = (int) InventoryMovement::query()
            ->whereIn('type', ['fast_out', 'fast_out_partial'])
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return view('admin.wms.fast-flow', [
            'recentBoxes'    => $recentBoxes,
            'recentBatches'  => $recentBatches,
            'availableBoxes' => $availableBoxes,
            'availableUnits' => $availableUnits,
            'inboundToday'   => $inboundToday,
            'outboundToday'  => $outboundToday,
            'lastBatchCode'  => session('fast_flow_batch_code'),
        ]);
    }

    public function create()
    {
        $defaultWarehouse = $this->getMainWarehouse();

        $productColumns = ['id', 'name', 'sku'];

        if (Schema::hasColumn('catalog_items', 'stock')) {
            $productColumns[] = 'stock';
        }
        if (Schema::hasColumn('catalog_items', 'brand_name')) {
            $productColumns[] = 'brand_name';
        }
        if (Schema::hasColumn('catalog_items', 'model_name')) {
            $productColumns[] = 'model_name';
        }

        $products = CatalogItem::query()
            ->orderBy('name')
            ->limit(3000)
            ->get($productColumns);

        return view('admin.wms.fast-flow-create', [
            'products'         => $products,
            'defaultWarehouse' => $defaultWarehouse,
            'lastBatchCode'    => session('fast_flow_batch_code'),
        ]);
    }

    public function storeInbound(Request $request)
    {
        $data = $request->validate([
            'catalog_item_id' => ['required', 'exists:catalog_items,id'],
            'boxes_count'     => ['required', 'integer', 'min:1', 'max:5000'],
            'units_per_box'   => ['required', 'integer', 'min:1', 'max:100000'],
            'reference'       => ['nullable', 'string', 'max:120'],
            'notes'           => ['nullable', 'string', 'max:3000'],
        ]);

        $userId = auth()->id();
        $warehouse = $this->getMainWarehouse();

        $warehouseId = (int) $warehouse->id;
        $catalogItemId = (int) $data['catalog_item_id'];
        $boxesCount = (int) $data['boxes_count'];
        $unitsPerBox = (int) $data['units_per_box'];
        $totalUnits = $boxesCount * $unitsPerBox;
        $reference = trim((string) ($data['reference'] ?? ''));
        $notes = trim((string) ($data['notes'] ?? ''));

        $batchCode = DB::transaction(function () use (
            $userId,
            $warehouseId,
            $catalogItemId,
            $boxesCount,
            $unitsPerBox,
            $totalUnits,
            $reference,
            $notes
        ) {
            $location = $this->getOrCreateFastFlowLocation($warehouseId);
            $batchCode = $this->nextBatchCode();

            for ($i = 1; $i <= $boxesCount; $i++) {
                WmsQuickBox::create([
                    'warehouse_id'    => $warehouseId,
                    'location_id'     => $location->id,
                    'catalog_item_id' => $catalogItemId,
                    'batch_code'      => $batchCode,
                    'label_code'      => $batchCode . '-C' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                    'box_number'      => $i,
                    'boxes_in_batch'  => $boxesCount,
                    'units_per_box'   => $unitsPerBox,
                    'current_units'   => $unitsPerBox,
                    'status'          => 'available',
                    'received_at'     => now(),
                    'received_by'     => $userId,
                    'reference'       => $reference !== '' ? $reference : null,
                    'notes'           => $notes !== '' ? $notes : null,
                    'meta'            => [
                        'flow' => 'fast_flow',
                        'kind' => 'inbound',
                    ],
                ]);
            }

            $this->applyInventoryDelta(
                $location->id,
                $catalogItemId,
                $totalUnits,
                $userId
            );

            InventoryMovement::create([
                'type'             => 'fast_in',
                'catalog_item_id'  => $catalogItemId,
                'from_location_id' => null,
                'to_location_id'   => $location->id,
                'qty'              => $totalUnits,
                'user_id'          => $userId,
                'notes'            => $notes !== '' ? $notes : 'Entrada rápida',
                'meta'             => [
                    'batch_code'    => $batchCode,
                    'boxes_count'   => $boxesCount,
                    'units_per_box' => $unitsPerBox,
                    'reference'     => $reference,
                ],
            ]);

            return $batchCode;
        });

        return redirect()
            ->route('admin.wms.fastflow.index')
            ->with('ok', 'Entrada rápida registrada. Se generaron ' . $boxesCount . ' etiquetas.')
            ->with('fast_flow_batch_code', $batchCode);
    }

    public function show(string $batchCode)
    {
        $boxes = WmsQuickBox::query()
            ->with([
                'item:id,name,sku',
                'warehouse:id,name,code',
                'location:id,code,name',
            ])
            ->where('batch_code', $batchCode)
            ->orderBy('box_number')
            ->get();

        if ($boxes->isEmpty()) {
            abort(404);
        }

        $first = $boxes->first();

        $productName = optional($first->item)->name ?? 'Producto';
        $sku = optional($first->item)->sku ?? '—';
        $warehouseName = optional($first->warehouse)->name ?? 'Principal';
        $warehouseCode = optional($first->warehouse)->code ?? '';
        $unitsPerBox = (int) ($first->units_per_box ?? 0);
        $totalBoxes = (int) $boxes->count();
        $totalUnits = (int) $boxes->sum(fn ($box) => (int) ($box->units_per_box ?? 0));
        $remainingUnits = (int) $boxes
            ->whereIn('status', ['available', 'partial'])
            ->sum(fn ($box) => (int) ($box->current_units ?? 0));

        $boxesInStock = (int) $boxes->where('status', 'available')->count();
        $partialBoxes = (int) $boxes->where('status', 'partial')->count();
        $shippedBoxes = (int) $boxes->where('status', 'shipped')->count();
        $activeBoxes = (int) $boxes->whereIn('status', ['available', 'partial'])->count();

        $status = $activeBoxes > 0 ? 'active' : 'completed';

        $dateSource = $first->received_at ?? $first->created_at ?? now();
        try {
            $dateObj = \Illuminate\Support\Carbon::parse($dateSource);
            $dateLabel = $dateObj->format('d M Y H:i');
        } catch (\Throwable $e) {
            $dateLabel = (string) $dateSource;
        }

        return view('admin.wms.fast-flow-show', [
            'batchCode'      => $batchCode,
            'productName'    => $productName,
            'sku'            => $sku,
            'warehouseName'  => $warehouseName,
            'warehouseCode'  => $warehouseCode,
            'unitsPerBox'    => $unitsPerBox,
            'totalBoxes'     => $totalBoxes,
            'totalUnits'     => $totalUnits,
            'remainingUnits' => $remainingUnits,
            'boxesInStock'   => $boxesInStock,
            'partialBoxes'   => $partialBoxes,
            'shippedBoxes'   => $shippedBoxes,
            'activeBoxes'    => $activeBoxes,
            'status'         => $status,
            'dateLabel'      => $dateLabel,
            'boxes'          => $boxes,
        ]);
    }

    public function printLabels(string $batchCode)
    {
        $boxes = WmsQuickBox::query()
            ->with([
                'item:id,name,sku',
                'warehouse:id,name,code',
                'location:id,code,name',
            ])
            ->where('batch_code', $batchCode)
            ->orderBy('box_number')
            ->get();

        if ($boxes->isEmpty()) {
            abort(404);
        }

        $boxes = $boxes->map(function ($box) {
            $box->qr_svg = $this->makeQrSvgData($box->label_code);
            return $box;
        });

        return Pdf::loadView('admin.wms.fast-flow-labels', [
            'boxes' => $boxes,
        ])
        ->setPaper($this->labelPaper10x10())
        ->download('fast-flow-labels-' . $batchCode . '.pdf');
    }

    public function printSingleLabel(string $labelCode)
    {
        $box = WmsQuickBox::query()
            ->with([
                'item:id,name,sku',
                'warehouse:id,name,code',
                'location:id,code,name',
            ])
            ->where('label_code', $labelCode)
            ->first();

        if (!$box) {
            abort(404);
        }

        $box->qr_svg = $this->makeQrSvgData($box->label_code);

        return Pdf::loadView('admin.wms.fast-flow-labels', [
            'boxes' => collect([$box]),
        ])
        ->setPaper($this->labelPaper10x10())
        ->download('label-' . $labelCode . '.pdf');
    }

    protected function getMainWarehouse(): Warehouse
    {
        $warehouse = Warehouse::query()
            ->where(function ($q) {
                $q->whereRaw('LOWER(name) = ?', ['principal'])
                  ->orWhereRaw('LOWER(code) = ?', ['principal']);
            })
            ->orderBy('id')
            ->first();

        if (!$warehouse) {
            $warehouse = Warehouse::query()
                ->orderBy('id')
                ->first();
        }

        if (!$warehouse) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Debes crear primero el almacén principal.',
            ]);
        }

        return $warehouse;
    }

    protected function getOrCreateFastFlowLocation(int $warehouseId): Location
    {
        $code = 'FAST-' . $warehouseId;

        $location = Location::query()
            ->where('warehouse_id', $warehouseId)
            ->where(function ($q) use ($code) {
                $q->where('type', 'fast_flow')
                  ->orWhere('code', $code);
            })
            ->first();

        if ($location) {
            return $location;
        }

        return Location::create([
            'warehouse_id' => $warehouseId,
            'parent_id'    => null,
            'type'         => 'fast_flow',
            'code'         => $code,
            'aisle'        => 'FAST',
            'section'      => '01',
            'stand'        => null,
            'rack'         => null,
            'level'        => null,
            'bin'          => null,
            'name'         => 'Tránsito rápido',
            'meta'         => [
                'system'      => true,
                'cross_dock'  => true,
                'description' => 'Ubicación de entrada/salida rápida',
            ],
        ]);
    }

    protected function applyInventoryDelta(int $locationId, int $catalogItemId, int $deltaQty, ?int $userId = null): void
    {
        $row = Inventory::query()
            ->where('location_id', $locationId)
            ->where('catalog_item_id', $catalogItemId)
            ->lockForUpdate()
            ->first();

        if (!$row) {
            if ($deltaQty < 0) {
                throw ValidationException::withMessages([
                    'label_code' => 'No existe inventario suficiente en esa ubicación.',
                ]);
            }

            $row = new Inventory();
            $row->location_id = $locationId;
            $row->catalog_item_id = $catalogItemId;
            $row->qty = 0;
            $row->min_qty = 0;
        }

        $newQty = (int) $row->qty + $deltaQty;

        if ($newQty < 0) {
            throw ValidationException::withMessages([
                'label_code' => 'La salida dejaría el inventario en negativo.',
            ]);
        }

        $row->qty = $newQty;

        if (Schema::hasColumn('inventories', 'updated_by')) {
            $row->updated_by = $userId;
        }

        $row->save();

        if (Schema::hasColumn('catalog_items', 'stock')) {
            $item = CatalogItem::query()
                ->whereKey($catalogItemId)
                ->lockForUpdate()
                ->first();

            if ($item) {
                $itemNewStock = (int) ($item->stock ?? 0) + $deltaQty;

                if ($itemNewStock < 0) {
                    throw ValidationException::withMessages([
                        'label_code' => 'La salida dejaría el stock general en negativo.',
                    ]);
                }

                $item->stock = $itemNewStock;
                $item->save();
            }
        }
    }

    protected function nextBatchCode(): string
    {
        $prefix = 'FF-' . now()->format('ymd');

        $last = WmsQuickBox::query()
            ->where('batch_code', 'like', $prefix . '-%')
            ->orderByDesc('batch_code')
            ->value('batch_code');

        $next = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return $prefix . '-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    protected function makeQrSvgData(string $text): string
    {
        $svg = QrCode::format('svg')
            ->size(220)
            ->margin(1)
            ->generate($text);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

protected function labelPaper10x10(): array
{
    $cmToPt = 28.3464567;
    $size = 10 * $cmToPt;

    return [0, 0, $size, $size];
}
}