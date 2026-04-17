<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\User;
use App\Models\WmsReception;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class WmsReceptionController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));
        $date = trim((string) $request->get('date', ''));

        $baseQuery = WmsReception::query();

        if ($q !== '') {
            $baseQuery->where(function ($qq) use ($q) {
                $qq->where('folio', 'like', "%{$q}%")
                    ->orWhere('deliverer_name', 'like', "%{$q}%")
                    ->orWhere('receiver_name', 'like', "%{$q}%")
                    ->orWhere('observations', 'like', "%{$q}%");

                if (is_numeric($q)) {
                    $qq->orWhere('id', (int) $q);
                }
            });
        }

        if ($status !== '') {
            $baseQuery->where('status', $status);
        }

        if ($date !== '') {
            $baseQuery->whereDate('reception_date', $date);
        }

        $receptions = (clone $baseQuery)
            ->withCount('lines')
            ->withSum('lines', 'quantity')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $summaryRows = (clone $baseQuery)
            ->withSum('lines', 'quantity')
            ->get(['id', 'status']);

        $totalReceptions = $summaryRows->count();
        $pendingCount = $summaryRows->where('status', 'pendiente')->count();
        $signedCount = $summaryRows->where('status', 'firmado')->count();
        $totalUnits = (int) $summaryRows->sum(fn ($row) => (int) ($row->lines_sum_quantity ?? 0));

        return view('admin.wms.receptions.index', [
            'receptions'      => $receptions,
            'totalReceptions' => $totalReceptions,
            'pendingCount'    => $pendingCount,
            'signedCount'     => $signedCount,
            'totalUnits'      => $totalUnits,
        ]);
    }

    public function create()
    {
        return view('admin.wms.receptions.create', [
            'folio' => $this->generateFolio(),
            'locations' => Location::query()
                ->orderBy('code')
                ->get(['id', 'code']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'folio'             => ['required', 'string', 'max:120'],
            'deliverer_user_id' => ['nullable', 'exists:users,id'],
            'receiver_user_id'  => ['nullable', 'exists:users,id'],
            'observations'      => ['nullable', 'string'],

            'lines'                    => ['required', 'array', 'min:1'],
            'lines.*.catalog_item_id'  => ['required', 'exists:catalog_items,id'],
            'lines.*.location_id'      => ['nullable', 'integer', 'exists:locations,id'],
            'lines.*.sku'              => ['nullable', 'string', 'max:120'],
            'lines.*.description'      => ['required', 'string', 'max:1000'],
            'lines.*.quantity'         => ['required', 'integer', 'min:1'],
            'lines.*.lot'              => ['nullable', 'string', 'max:120'],
            'lines.*.condition'        => ['required', 'string', 'max:50'],
        ]);

        $deliverer = null;
        $receiver = null;

        if (!empty($data['deliverer_user_id'])) {
            $deliverer = User::query()->find($data['deliverer_user_id']);
        }

        if (!empty($data['receiver_user_id'])) {
            $receiver = User::query()->find($data['receiver_user_id']);
        }

        if (!$deliverer || !$receiver) {
            throw ValidationException::withMessages([
                'users' => 'Debes seleccionar quién entrega y quién recibe.',
            ]);
        }

        $lines = collect($data['lines'])
            ->map(function ($line) {
                return [
                    'catalog_item_id' => (int) $line['catalog_item_id'],
                    'location_id'     => !empty($line['location_id']) ? (int) $line['location_id'] : null,
                    'sku'             => trim((string) ($line['sku'] ?? '')) ?: null,
                    'description'     => trim((string) ($line['description'] ?? '')),
                    'quantity'        => max(1, (int) ($line['quantity'] ?? 1)),
                    'lot'             => trim((string) ($line['lot'] ?? '')) ?: null,
                    'condition'       => trim((string) ($line['condition'] ?? 'revision')) ?: 'revision',
                ];
            })
            ->filter(fn ($line) => $line['catalog_item_id'] > 0 && $line['description'] !== '')
            ->values();

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages([
                'lines' => 'Debes agregar al menos un producto válido.',
            ]);
        }

        $reception = DB::transaction(function () use ($data, $deliverer, $receiver, $lines) {
            $reception = WmsReception::query()->create([
                'folio'               => $data['folio'],
                'deliverer_user_id'   => $deliverer->id,
                'receiver_user_id'    => $receiver->id,
                'deliverer_name'      => $deliverer->name,
                'receiver_name'       => $receiver->name,
                'reception_date'      => now(),
                'observations'        => $data['observations'] ?? null,
                'status'              => 'pendiente',
                'signature_token'     => Str::random(48),
                'delivered_signature' => null,
                'received_signature'  => null,
                'created_by'          => auth()->id(),
            ]);

            $catalogItems = CatalogItem::query()
                ->whereIn('id', $lines->pluck('catalog_item_id')->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($lines as $line) {
                $item = $catalogItems[$line['catalog_item_id']] ?? null;

                if (!$item) {
                    throw ValidationException::withMessages([
                        'lines' => 'No se encontró uno de los productos seleccionados.',
                    ]);
                }

                $locationId = $this->resolveSuggestedLocationId(
                    $item,
                    $line['location_id'] ?? null,
                    1
                );

                if (!$locationId) {
                    throw ValidationException::withMessages([
                        'lines' => 'No se pudo determinar una ubicación para el producto: ' . $item->name,
                    ]);
                }

                $location = Location::query()->find($locationId);

                if (!$location) {
                    throw ValidationException::withMessages([
                        'lines' => 'La ubicación seleccionada no existe para el producto: ' . $item->name,
                    ]);
                }

                $reception->lines()->create([
                    'catalog_item_id' => $line['catalog_item_id'],
                    'location_id'     => $locationId,
                    'sku'             => $line['sku'] ?: ($item->sku ?? null),
                    'name'            => $item->name ?? null,
                    'description'     => $line['description'],
                    'quantity'        => $line['quantity'],
                    'lot'             => $line['lot'],
                    'condition'       => $line['condition'],
                    'is_new_product'  => false,
                ]);
            }

            return $reception;
        });

        return redirect()
            ->route('admin.wms.receptions.show', $reception->id)
            ->with('ok', 'Recepción creada correctamente. El stock se actualizará al completar la entrega con firmas.');
    }

    public function show(WmsReception $id)
    {
        $reception = $id->load([
            'lines.catalogItem:id,name,sku,meli_gtin,brand_name,model_name,description,stock,primary_location_id,photo_1,photo_2,photo_3',
            'lines.location:id,code',
            'delivererUser:id,name,email',
            'receiverUser:id,name,email',
        ]);

        return view('admin.wms.receptions.show', [
            'reception' => $reception,
            'qrSvg' => $this->makeQrSvgData(
                route('public.receptions.mobile', $reception->signature_token)
            ),
        ]);
    }

    public function users(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $users = User::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%{$q}%")
                       ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'email']);

        return response()->json([
            'ok' => true,
            'items' => $users->map(fn ($u) => [
                'id' => (int) $u->id,
                'name' => (string) $u->name,
                'email' => (string) ($u->email ?? ''),
            ])->values(),
        ]);
    }

    public function products(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $warehouseId = (int) ($request->get('warehouse_id', 1));
        $limit = min(30, max(5, (int) $request->get('limit', 20)));

        if ($q === '') {
            return response()->json([
                'ok' => true,
                'items' => [],
            ]);
        }

        $itemColumns = [
            'id',
            'name',
            'sku',
            'meli_gtin',
            'stock',
            'primary_location_id',
            'brand_name',
            'model_name',
            'description',
        ];

        if (Schema::hasColumn('catalog_items', 'photo_1')) $itemColumns[] = 'photo_1';
        if (Schema::hasColumn('catalog_items', 'photo_2')) $itemColumns[] = 'photo_2';
        if (Schema::hasColumn('catalog_items', 'photo_3')) $itemColumns[] = 'photo_3';

        $items = CatalogItem::query()
            ->select($itemColumns)
            ->where(function ($qq) use ($q) {
                $qq->where('name', 'like', "%{$q}%")
                   ->orWhere('sku', 'like', "%{$q}%")
                   ->orWhere('meli_gtin', 'like', "%{$q}%")
                   ->orWhere('brand_name', 'like', "%{$q}%")
                   ->orWhere('model_name', 'like', "%{$q}%");

                if (is_numeric($q)) {
                    $qq->orWhere('id', (int) $q);
                }
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();

        $warehouseLocations = Location::query()
            ->where('warehouse_id', $warehouseId)
            ->orderBy('code')
            ->get(['id', 'code']);

        $warehouseLocationIds = $warehouseLocations->pluck('id')->all();
        $warehouseLocationMap = $warehouseLocations->keyBy('id');

        $locIds = $items->pluck('primary_location_id')->filter()->unique()->values()->all();

        $primaryLocMap = Location::query()
            ->whereIn('id', $locIds)
            ->where('warehouse_id', $warehouseId)
            ->get(['id', 'code'])
            ->keyBy('id');

        $ids = $items->pluck('id')->all();

        $bestLocByItem = Inventory::query()
            ->selectRaw('catalog_item_id, location_id, SUM(qty) as qty_sum')
            ->whereIn('catalog_item_id', $ids)
            ->whereIn('location_id', $warehouseLocationIds)
            ->groupBy('catalog_item_id', 'location_id')
            ->get()
            ->groupBy('catalog_item_id')
            ->map(fn ($rows) => $rows->sortByDesc('qty_sum')->first());

        $locationsById = Location::query()
            ->whereIn(
                'id',
                $bestLocByItem->pluck('location_id')->filter()->unique()->values()->all()
            )
            ->get(['id', 'code'])
            ->keyBy('id');

        $out = $items->map(function ($it) use (
            $primaryLocMap,
            $bestLocByItem,
            $locationsById,
            $warehouseLocations,
            $warehouseLocationMap,
            $warehouseId
        ) {
            $rec = null;

            if ($it->primary_location_id && isset($primaryLocMap[$it->primary_location_id])) {
                $rec = [
                    'location_id' => (int) $it->primary_location_id,
                    'code' => (string) $primaryLocMap[$it->primary_location_id]->code,
                    'why' => 'primary',
                ];
            } else {
                $best = $bestLocByItem[$it->id] ?? null;
                if ($best && isset($locationsById[$best->location_id])) {
                    $rec = [
                        'location_id' => (int) $best->location_id,
                        'code' => (string) $locationsById[$best->location_id]->code,
                        'why' => 'most_qty',
                    ];
                } else {
                    $suggestedId = $this->resolveSuggestedLocationId($it, null, $warehouseId);
                    if ($suggestedId && isset($warehouseLocationMap[$suggestedId])) {
                        $rec = [
                            'location_id' => (int) $suggestedId,
                            'code' => (string) $warehouseLocationMap[$suggestedId]->code,
                            'why' => 'suggested_space',
                        ];
                    }
                }
            }

            $imageUrl = null;
            if (!empty($it->photo_1)) {
                $imageUrl = asset('storage/' . ltrim($it->photo_1, '/'));
            } elseif (!empty($it->photo_2)) {
                $imageUrl = asset('storage/' . ltrim($it->photo_2, '/'));
            } elseif (!empty($it->photo_3)) {
                $imageUrl = asset('storage/' . ltrim($it->photo_3, '/'));
            }

            return [
                'id' => (int) $it->id,
                'name' => (string) $it->name,
                'sku' => (string) ($it->sku ?? ''),
                'gtin' => (string) ($it->meli_gtin ?? ''),
                'meli_gtin' => (string) ($it->meli_gtin ?? ''),
                'stock' => (int) ($it->stock ?? 0),
                'brand_name' => (string) ($it->brand_name ?? ''),
                'model_name' => (string) ($it->model_name ?? ''),
                'description' => (string) ($it->description ?? ''),
                'recommended' => $rec,
                'all_locations' => $warehouseLocations->map(fn ($loc) => [
                    'id' => (int) $loc->id,
                    'code' => (string) $loc->code,
                ])->values(),
                'image_url' => $imageUrl,
            ];
        })->values();

        return response()->json([
            'ok' => true,
            'items' => $out,
        ]);
    }

    public function quickStoreProduct(Request $request)
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:255'],
            'brand_name' => ['nullable', 'string', 'max:120'],
            'model_name' => ['nullable', 'string', 'max:120'],
        ]);

        $existing = CatalogItem::query()
            ->where('sku', $data['sku'])
            ->first();

        $allLocations = Location::query()
            ->orderBy('code')
            ->get(['id', 'code']);

        if ($existing) {
            $recommendedId = $this->resolveSuggestedLocationId($existing, null, 1);
            $recommended = null;

            if ($recommendedId) {
                $location = Location::query()->find($recommendedId);
                if ($location) {
                    $recommended = [
                        'location_id' => (int) $location->id,
                        'code' => (string) $location->code,
                        'why' => 'suggested',
                    ];
                }
            }

            return response()->json([
                'ok' => true,
                'item' => [
                    'id' => $existing->id,
                    'name' => $existing->name,
                    'sku' => $existing->sku,
                    'brand_name' => $existing->brand_name,
                    'model_name' => $existing->model_name,
                    'description' => (string) ($existing->description ?? ''),
                    'stock' => (int) ($existing->stock ?? 0),
                    'recommended' => $recommended,
                    'all_locations' => $allLocations->map(fn ($loc) => [
                        'id' => (int) $loc->id,
                        'code' => (string) $loc->code,
                    ])->values(),
                ],
            ]);
        }

        $baseSlug = Str::slug($data['name']);
        if ($baseSlug === '') {
            $baseSlug = Str::slug(Str::random(8));
        }

        $slug = $baseSlug;
        $i = 1;
        while (CatalogItem::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i++;
        }

        $defaultLocationId = $this->resolveDefaultEmptyLocationId(1);
        if (!$defaultLocationId) {
            $defaultLocationId = Location::query()->orderBy('code')->value('id');
        }

        $item = CatalogItem::query()->create([
            'name'                => $data['name'],
            'slug'                => $slug,
            'sku'                 => $data['sku'],
            'price'               => 0,
            'stock'               => 0,
            'status'              => 0,
            'excerpt'             => null,
            'description'         => null,
            'brand_name'          => $data['brand_name'] ?? null,
            'model_name'          => $data['model_name'] ?? null,
            'is_featured'         => false,
            'primary_location_id' => $defaultLocationId,
        ]);

        $recommended = null;
        if ($defaultLocationId) {
            $loc = Location::query()->find($defaultLocationId);
            if ($loc) {
                $recommended = [
                    'location_id' => (int) $loc->id,
                    'code' => (string) $loc->code,
                    'why' => 'default_empty',
                ];
            }
        }

        return response()->json([
            'ok' => true,
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'brand_name' => $item->brand_name,
                'model_name' => $item->model_name,
                'description' => '',
                'stock' => (int) ($item->stock ?? 0),
                'recommended' => $recommended,
                'all_locations' => $allLocations->map(fn ($loc) => [
                    'id' => (int) $loc->id,
                    'code' => (string) $loc->code,
                ])->values(),
            ],
        ]);
    }

    public function startSignatures(Request $request)
    {
        $request->validate([
            'reception_id' => ['required', 'exists:wms_receptions,id'],
        ]);

        $reception = WmsReception::query()->findOrFail($request->get('reception_id'));

        if (!$reception->signature_token) {
            $reception->signature_token = Str::random(48);
            $reception->save();
        }

        return response()->json([
            'ok'           => true,
            'reception_id' => $reception->id,
            'token'        => $reception->signature_token,
            'mobile_url'   => route('public.receptions.mobile', $reception->signature_token),
        ]);
    }

    public function signatureStatus(WmsReception $id)
    {
        if (!empty($id->delivered_signature) && !empty($id->received_signature) && $id->status !== 'firmado') {
            $this->applyReceptionInventory($id);
        }

        $id->refresh();

        return response()->json([
            'ok' => true,
            'status' => $id->status,
            'delivered_signature' => $id->delivered_signature,
            'received_signature' => $id->received_signature,
        ]);
    }

    public function mobileSignature(string $token)
    {
        $reception = WmsReception::query()
            ->where('signature_token', $token)
            ->firstOrFail();

        return view('admin.wms.receptions.mobile-signature', [
            'reception' => $reception,
        ]);
    }

    public function saveMobileSignature(Request $request, string $token)
    {
        $data = $request->validate([
            'role'      => ['required', 'in:deliverer,receiver'],
            'signature' => ['required', 'string'],
        ]);

        $reception = WmsReception::query()
            ->where('signature_token', $token)
            ->firstOrFail();

        if ($data['role'] === 'deliverer') {
            $reception->delivered_signature = $data['signature'];
        } else {
            $reception->received_signature = $data['signature'];
        }

        $reception->save();

        if (!empty($reception->delivered_signature) && !empty($reception->received_signature) && $reception->status !== 'firmado') {
            $this->applyReceptionInventory($reception);
            $reception->refresh();
        }

        return response()->json([
            'ok' => true,
            'status' => $reception->status,
        ]);
    }

    public function publicSignatureStatus(string $token)
    {
        $reception = WmsReception::query()
            ->where('signature_token', $token)
            ->firstOrFail();

        if (!empty($reception->delivered_signature) && !empty($reception->received_signature) && $reception->status !== 'firmado') {
            $this->applyReceptionInventory($reception);
            $reception->refresh();
        }

        return response()->json([
            'ok' => true,
            'status' => $reception->status,
            'delivered_signature' => $reception->delivered_signature,
            'received_signature' => $reception->received_signature,
        ]);
    }

    public function pdf(WmsReception $id)
    {
        $reception = $id->load([
            'lines.catalogItem',
            'lines.location',
            'delivererUser:id,name,email',
            'receiverUser:id,name,email',
        ]);

        $html = view('admin.wms.receptions.pdf', compact('reception'))->render();

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->stream('recepcion-' . $reception->folio . '.pdf');
    }

    public function labels(WmsReception $id)
    {
        $reception = $id->load(['lines.catalogItem']);
        $html = view('admin.wms.receptions.labels', compact('reception'))->render();

        return Pdf::loadHTML($html)
            ->setPaper([0, 0, 56.69, 56.69])
            ->download('etiquetas-' . $reception->folio . '.pdf');
    }

    protected function generateFolio(): string
    {
        return 'REC-' . now()->format('Ymd-His');
    }

    protected function makeQrSvgData(string $text): string
    {
        $svg = QrCode::format('svg')
            ->size(220)
            ->margin(1)
            ->generate($text);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    protected function applyReceptionInventory(WmsReception $reception): void
    {
        DB::transaction(function () use ($reception) {
            $reception = WmsReception::query()
                ->whereKey($reception->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($reception->status === 'firmado') {
                return;
            }

            $lines = $reception->lines()
                ->lockForUpdate()
                ->get();

            $itemIds = $lines->pluck('catalog_item_id')->filter()->unique()->values()->all();

            $catalogItems = CatalogItem::query()
                ->whereIn('id', $itemIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($lines as $line) {
                $item = $catalogItems[$line->catalog_item_id] ?? null;

                if (!$item) {
                    throw ValidationException::withMessages([
                        'lines' => 'No se encontró uno de los productos de la recepción.',
                    ]);
                }

                $locationId = $this->resolveSuggestedLocationId(
                    $item,
                    $line->location_id,
                    1
                );

                if (!$locationId) {
                    throw ValidationException::withMessages([
                        'lines' => 'No se pudo determinar ubicación para: ' . $item->name,
                    ]);
                }

                $location = Location::query()->find($locationId);

                if (!$location) {
                    throw ValidationException::withMessages([
                        'lines' => 'La ubicación seleccionada ya no existe para: ' . $item->name,
                    ]);
                }

                if ((int) $line->location_id !== (int) $locationId) {
                    $line->location_id = $locationId;
                    $line->save();
                }

                $inventory = Inventory::query()
                    ->where('catalog_item_id', $line->catalog_item_id)
                    ->where('location_id', $locationId)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory) {
                    $inventory = Inventory::query()->create([
                        'catalog_item_id' => $line->catalog_item_id,
                        'location_id'     => $locationId,
                        'qty'             => 0,
                        'min_qty'         => 0,
                        'updated_by'      => auth()->id() ?: $reception->created_by,
                    ]);
                }

                $inventory->qty = (int) $inventory->qty + (int) $line->quantity;
                $inventory->updated_by = auth()->id() ?: $reception->created_by;
                $inventory->save();

                $item->stock = (int) ($item->stock ?? 0) + (int) $line->quantity;

                if (empty($item->primary_location_id)) {
                    $item->primary_location_id = $locationId;
                }

                $item->save();
            }

            $reception->status = 'firmado';
            $reception->save();
        });
    }

    protected function resolveSuggestedLocationId(CatalogItem $item, ?int $preferredLocationId = null, ?int $warehouseId = 1): ?int
    {
        if ($preferredLocationId) {
            $preferred = Location::query()
                ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->where('id', $preferredLocationId)
                ->value('id');

            if ($preferred) {
                return (int) $preferred;
            }
        }

        if (!empty($item->primary_location_id)) {
            $primary = Location::query()
                ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->where('id', $item->primary_location_id)
                ->value('id');

            if ($primary) {
                return (int) $primary;
            }
        }

        $warehouseLocationIdsQuery = Location::query()
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->select('id');

        $bestInventory = Inventory::query()
            ->selectRaw('location_id, SUM(qty) as qty_sum')
            ->where('catalog_item_id', $item->id)
            ->whereIn('location_id', $warehouseLocationIdsQuery)
            ->groupBy('location_id')
            ->orderByDesc('qty_sum')
            ->first();

        if ($bestInventory && !empty($bestInventory->location_id)) {
            return (int) $bestInventory->location_id;
        }

        $emptyLocationId = $this->resolveDefaultEmptyLocationId($warehouseId);
        if ($emptyLocationId) {
            return (int) $emptyLocationId;
        }

        $leastLoaded = Location::query()
            ->when($warehouseId, fn ($q) => $q->where('locations.warehouse_id', $warehouseId))
            ->leftJoin('inventories', 'inventories.location_id', '=', 'locations.id')
            ->selectRaw('locations.id, COALESCE(SUM(inventories.qty), 0) as total_qty')
            ->groupBy('locations.id')
            ->orderBy('total_qty')
            ->orderBy('locations.code')
            ->first();

        return $leastLoaded ? (int) $leastLoaded->id : null;
    }

    protected function resolveDefaultEmptyLocationId(?int $warehouseId = 1): ?int
    {
        $usedLocationIds = Inventory::query()
            ->select('location_id')
            ->distinct();

        $query = Location::query()
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->whereNotIn('id', $usedLocationIds)
            ->orderBy('code');

        return $query->value('id');
    }
}