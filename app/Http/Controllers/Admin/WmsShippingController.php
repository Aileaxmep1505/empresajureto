<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PickWave;
use App\Models\WmsShipment;
use App\Models\WmsShipmentLine;
use App\Models\WmsShipmentScan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class WmsShippingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('auth')];
    }

    protected function logShipping(string $message, array $context = [], string $level = 'info'): void
    {
        Log::log($level, '[WMS SHIPPING] ' . $message, $context);
    }

    public function index(Request $request)
    {
        $base = WmsShipment::query()
            ->with([
                'pickWave:id',
                'warehouse:id,name,code',
                'operator:id,name',
            ])
            ->withCount('lines');

        if ($request->filled('status')) {
            $base->where('status', (string) $request->status);
        }

        if ($request->filled('s')) {
            $s = trim((string) $request->s);
            $base->where(function ($qq) use ($s) {
                $qq->where('shipment_number', 'like', "%{$s}%")
                    ->orWhere('order_number', 'like', "%{$s}%")
                    ->orWhere('task_number', 'like', "%{$s}%")
                    ->orWhere('vehicle_plate', 'like', "%{$s}%")
                    ->orWhere('driver_name', 'like', "%{$s}%")
                    ->orWhere('route_name', 'like', "%{$s}%");
            });
        }

        $shipments = (clone $base)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'shipments' => $shipments,
            ]);
        }

        $rows = (clone $base)->get([
            'id',
            'status',
            'expected_qty',
            'loaded_qty',
            'missing_qty',
            'expected_boxes',
            'loaded_boxes',
            'missing_boxes',
        ]);

        $stats = [
            'total'           => (int) $rows->count(),
            'draft'           => (int) $rows->where('status', 'draft')->count(),
            'loading'         => (int) $rows->where('status', 'loading')->count(),
            'loaded_complete' => (int) $rows->where('status', 'loaded_complete')->count(),
            'loaded_partial'  => (int) $rows->where('status', 'loaded_partial')->count(),
            'dispatched'      => (int) $rows->where('status', 'dispatched')->count(),
            'expected_qty'    => (int) $rows->sum('expected_qty'),
            'loaded_qty'      => (int) $rows->sum('loaded_qty'),
            'missing_qty'     => (int) $rows->sum('missing_qty'),
            'expected_boxes'  => (int) $rows->sum('expected_boxes'),
            'loaded_boxes'    => (int) $rows->sum('loaded_boxes'),
            'missing_boxes'   => (int) $rows->sum('missing_boxes'),
        ];

        return view('admin.wms.shipping-index', [
            'shipments' => $shipments,
            'stats'     => $stats,
            'filters'   => [
                'status' => (string) $request->get('status', ''),
                's'      => (string) $request->get('s', ''),
            ],
        ]);
    }

    public function storeFromPicking(Request $request, PickWave $pickWave)
    {
        $data = $request->validate([
            'vehicle_plate'    => ['nullable', 'string', 'max:40'],
            'vehicle_name'     => ['nullable', 'string', 'max:120'],
            'driver_name'      => ['nullable', 'string', 'max:120'],
            'driver_phone'     => ['nullable', 'string', 'max:60'],
            'route_name'       => ['nullable', 'string', 'max:120'],
            'operator_user_id' => ['nullable', 'exists:users,id'],
            'notes'            => ['nullable', 'string', 'max:3000'],
        ]);

        $existing = WmsShipment::query()
            ->where('pick_wave_id', $pickWave->id)
            ->whereNotIn('status', ['cancelled'])
            ->latest('id')
            ->first();

        if ($existing) {
            return response()->json([
                'ok' => false,
                'message' => 'Ya existe un embarque activo para esta tarea de picking.',
                'shipment' => $this->shipmentPayload($this->reloadShipment($existing->id)),
            ], 409);
        }

        $task = $this->normalizePickWave($pickWave);

        if (empty($task['items'])) {
            throw ValidationException::withMessages([
                'pickWave' => 'La tarea de picking no contiene líneas.',
            ]);
        }

        $linePayloads = [];
        foreach ($task['items'] as $item) {
            $line = $this->buildShipmentLinePayload($item);
            if ((int) $line['expected_qty'] <= 0 && (int) $line['expected_boxes'] <= 0) {
                continue;
            }
            $linePayloads[] = $line;
        }

        if (empty($linePayloads)) {
            throw ValidationException::withMessages([
                'pickWave' => 'La tarea no tiene cantidades preparadas para embarque.',
            ]);
        }

        $shipment = DB::transaction(function () use ($pickWave, $task, $linePayloads, $data) {
            $now = now();
            $userId = auth()->id();

            $shipment = WmsShipment::create([
                'pick_wave_id'      => $pickWave->id,
                'warehouse_id'      => $task['warehouse_id'] ?: null,
                'shipment_number'   => $this->nextShipmentNumber(),
                'order_number'      => $task['order_number'] ?: null,
                'task_number'       => $task['task_number'] ?: null,
                'vehicle_plate'     => trim((string) ($data['vehicle_plate'] ?? '')) ?: null,
                'vehicle_name'      => trim((string) ($data['vehicle_name'] ?? '')) ?: null,
                'driver_name'       => trim((string) ($data['driver_name'] ?? '')) ?: null,
                'driver_phone'      => trim((string) ($data['driver_phone'] ?? '')) ?: null,
                'route_name'        => trim((string) ($data['route_name'] ?? '')) ?: null,
                'operator_user_id'  => !empty($data['operator_user_id']) ? (int) $data['operator_user_id'] : auth()->id(),
                'status'            => 'draft',
                'notes'             => trim((string) ($data['notes'] ?? '')) ?: null,
                'meta'              => [
                    'source' => 'pick_wave',
                    'pick_wave_id' => $pickWave->id,
                    'pick_status' => $task['status'] ?? null,
                    'pick_snapshot' => $task,
                    'created_at_shipping' => $now->toDateTimeString(),
                ],
                'created_by'        => $userId,
                'updated_by'        => $userId,
            ]);

            foreach ($linePayloads as $payload) {
                $shipment->lines()->create($payload);
            }

            $this->syncShipmentMetrics($shipment->id);

            return $shipment;
        });

        return response()->json([
            'ok' => true,
            'message' => 'Embarque creado correctamente.',
            'shipment' => $this->shipmentPayload($this->reloadShipment($shipment->id)),
        ]);
    }

    public function show(Request $request, WmsShipment $shipment)
    {
        $payload = $this->shipmentPayload($this->reloadShipment($shipment->id));

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'shipment' => $payload,
            ]);
        }

        return view('admin.wms.shipping-show', [
            'shipment' => $payload,
        ]);
    }

    public function scanner(Request $request, WmsShipment $shipment)
    {
        $payload = $this->shipmentPayload($this->reloadShipment($shipment->id));

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'operator_name' => auth()->user()?->name ?? 'Operador',
                'shipment' => $payload,
            ]);
        }

        return view('admin.wms.shipping-scanner', [
            'operatorName' => auth()->user()?->name ?? 'Operador',
            'shipment'     => $payload,
        ]);
    }

    public function scan(Request $request, WmsShipment $shipment)
    {
        $data = $request->validate([
            'code'         => ['required', 'string', 'max:150'],
            'qty'          => ['nullable', 'integer', 'min:1', 'max:100000'],
            'pick_line_id' => ['nullable', 'string', 'max:80'],
            'product_id'   => ['nullable', 'integer'],
            'allow_extra'  => ['nullable', 'boolean'],
        ]);

        $shipment = $this->reloadShipment($shipment->id);

        if (in_array($shipment->status, ['cancelled', 'dispatched'], true)) {
            $this->createScanLog($shipment->id, null, 'state', (string) $data['code'], 0, null, 'rejected', 'El embarque no acepta más escaneos.', $data);

            return response()->json([
                'ok' => false,
                'message' => 'El embarque no acepta más escaneos.',
            ], 422);
        }

        $code = strtoupper(trim((string) $data['code']));
        $allowExtra = (bool) ($data['allow_extra'] ?? false);

        $labelLine = $this->matchLineForLabel($shipment, $code);

        if ($labelLine) {
            if ($this->shipmentHasLoadedLabel($shipment, $code)) {
                $this->createScanLog($shipment->id, $labelLine->id, 'label', $code, 0, $code, 'duplicate', 'La caja ya fue cargada.', $data);

                return response()->json([
                    'ok' => false,
                    'message' => 'La caja ya fue cargada.',
                    'shipment' => $this->shipmentPayload($shipment),
                ], 422);
            }

            $qty = $this->resolveLabelQty($labelLine, $code);

            if ($qty <= 0) {
                $this->createScanLog($shipment->id, $labelLine->id, 'label', $code, 0, $code, 'rejected', 'La caja no tiene cantidad asignada para embarque.', $data);

                return response()->json([
                    'ok' => false,
                    'message' => 'La caja no tiene cantidad asignada para embarque.',
                    'shipment' => $this->shipmentPayload($shipment),
                ], 422);
            }

            $projectedQty = (int) $labelLine->loaded_qty + $qty;
            if (!$allowExtra && $projectedQty > (int) $labelLine->expected_qty) {
                $this->createScanLog($shipment->id, $labelLine->id, 'label', $code, $qty, $code, 'rejected', 'La carga excede lo esperado para la línea.', $data);

                return response()->json([
                    'ok' => false,
                    'message' => 'La carga excede lo esperado para la línea.',
                    'shipment' => $this->shipmentPayload($shipment),
                ], 422);
            }

            DB::transaction(function () use ($shipment, $labelLine, $code, $qty, $data) {
                $shipmentDb = WmsShipment::query()->lockForUpdate()->findOrFail($shipment->id);
                $lineDb = WmsShipmentLine::query()->lockForUpdate()->findOrFail($labelLine->id);

                $this->startLoadingIfNeeded($shipmentDb);

                $loadedBoxes = $this->normalizeUpperStringList($lineDb->loaded_boxes_json ?? []);
                $loadedBoxes[] = $code;
                $loadedBoxes = $this->normalizeUpperStringList($loadedBoxes);

                $loadedAllocations = is_array($lineDb->loaded_allocations_json) ? $lineDb->loaded_allocations_json : [];
                $loadedAllocations[] = [
                    'label' => $code,
                    'pieces' => $qty,
                    'scanned_at' => now()->toDateTimeString(),
                    'user_id' => auth()->id(),
                    'type' => 'label',
                ];

                $lineDb->loaded_boxes_json = array_values($loadedBoxes);
                $lineDb->loaded_allocations_json = array_values($loadedAllocations);
                $lineDb->loaded_qty = (int) $lineDb->loaded_qty + $qty;

                $this->syncLineMetrics($lineDb);
                $this->createScanLog($shipmentDb->id, $lineDb->id, 'label', $code, $qty, $code, 'accepted', 'Caja cargada correctamente.', $data);
                $this->syncShipmentMetrics($shipmentDb->id);
            });

            $fresh = $this->reloadShipment($shipment->id);

            return response()->json([
                'ok' => true,
                'message' => 'Caja cargada correctamente.',
                'shipment' => $this->shipmentPayload($fresh),
            ]);
        }

        $productLine = $this->matchLineForProductCode(
            $shipment,
            $code,
            $data['pick_line_id'] ?? null,
            !empty($data['product_id']) ? (int) $data['product_id'] : null
        );

        if (!$productLine) {
            $this->createScanLog($shipment->id, null, 'unknown', $code, 0, null, 'rejected', 'El código no pertenece a este embarque.', $data);

            return response()->json([
                'ok' => false,
                'message' => 'El código no pertenece a este embarque.',
                'shipment' => $this->shipmentPayload($shipment),
            ], 422);
        }

        $qty = !empty($data['qty']) ? (int) $data['qty'] : 1;
        $projectedQty = (int) $productLine->loaded_qty + $qty;

        if (!$allowExtra && $projectedQty > (int) $productLine->expected_qty) {
            $this->createScanLog($shipment->id, $productLine->id, 'product', $code, $qty, null, 'rejected', 'La cantidad cargada excede lo esperado para la línea.', $data);

            return response()->json([
                'ok' => false,
                'message' => 'La cantidad cargada excede lo esperado para la línea.',
                'shipment' => $this->shipmentPayload($shipment),
            ], 422);
        }

        DB::transaction(function () use ($shipment, $productLine, $code, $qty, $data) {
            $shipmentDb = WmsShipment::query()->lockForUpdate()->findOrFail($shipment->id);
            $lineDb = WmsShipmentLine::query()->lockForUpdate()->findOrFail($productLine->id);

            $this->startLoadingIfNeeded($shipmentDb);

            $lineDb->loaded_qty = (int) $lineDb->loaded_qty + $qty;

            $meta = is_array($lineDb->meta) ? $lineDb->meta : [];
            $meta['manual_scans'] = is_array($meta['manual_scans'] ?? null) ? $meta['manual_scans'] : [];
            $meta['manual_scans'][] = [
                'code' => $code,
                'qty' => $qty,
                'scanned_at' => now()->toDateTimeString(),
                'user_id' => auth()->id(),
                'type' => 'product',
            ];
            $lineDb->meta = $meta;

            $this->syncLineMetrics($lineDb);
            $this->createScanLog($shipmentDb->id, $lineDb->id, 'product', $code, $qty, null, 'accepted', 'Producto cargado correctamente.', $data);
            $this->syncShipmentMetrics($shipmentDb->id);
        });

        $fresh = $this->reloadShipment($shipment->id);

        return response()->json([
            'ok' => true,
            'message' => 'Producto cargado correctamente.',
            'shipment' => $this->shipmentPayload($fresh),
        ]);
    }

    public function close(Request $request, WmsShipment $shipment)
    {
        $data = $request->validate([
            'signed_by_name' => ['required', 'string', 'max:120'],
            'signed_by_role' => ['required', 'string', 'max:120'],
            'signature_data' => ['required', 'string'],
            'notes'          => ['nullable', 'string', 'max:3000'],

            'line_reasons' => ['nullable', 'array'],
            'line_reasons.*.line_id' => ['nullable', 'integer'],
            'line_reasons.*.pick_line_id' => ['nullable', 'string', 'max:80'],
            'line_reasons.*.reason_code' => [
                'nullable',
                'string',
                'max:80',
                Rule::in([
                    'not_found_in_staging',
                    'damaged_box',
                    'incomplete_product',
                    'rescheduled',
                    'customer_partial_authorized',
                    'no_space_in_vehicle',
                    'quality_hold',
                    'picking_error',
                    'retained',
                    'other',
                ]),
            ],
            'line_reasons.*.reason_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $reasonBag = collect($data['line_reasons'] ?? [])
            ->map(function ($row) {
                return [
                    'line_id' => !empty($row['line_id']) ? (int) $row['line_id'] : null,
                    'pick_line_id' => trim((string) ($row['pick_line_id'] ?? '')) ?: null,
                    'reason_code' => trim((string) ($row['reason_code'] ?? '')) ?: null,
                    'reason_note' => trim((string) ($row['reason_note'] ?? '')) ?: null,
                ];
            })
            ->values();

        DB::transaction(function () use ($shipment, $data, $reasonBag) {
            $shipmentDb = WmsShipment::query()->lockForUpdate()->findOrFail($shipment->id);
            $lines = WmsShipmentLine::query()
                ->where('shipment_id', $shipmentDb->id)
                ->lockForUpdate()
                ->get();

            if (in_array($shipmentDb->status, ['cancelled', 'dispatched'], true)) {
                throw ValidationException::withMessages([
                    'shipment' => 'Este embarque no puede cerrarse.',
                ]);
            }

            foreach ($lines as $line) {
                $match = $reasonBag->first(function ($row) use ($line) {
                    if (!empty($row['line_id']) && (int) $row['line_id'] === (int) $line->id) {
                        return true;
                    }

                    if (!empty($row['pick_line_id']) && (string) $row['pick_line_id'] === (string) $line->pick_line_id) {
                        return true;
                    }

                    return false;
                });

                if ($match) {
                    $line->reason_code = $match['reason_code'] ?: null;
                    $line->reason_note = $match['reason_note'] ?: null;
                }

                $this->syncLineMetrics($line);

                $requiresReason = ((int) $line->missing_qty > 0 || (int) $line->missing_boxes > 0);
                if ($requiresReason && empty($line->reason_code)) {
                    throw ValidationException::withMessages([
                        'line_reasons' => 'Todas las líneas incompletas deben llevar motivo.',
                    ]);
                }

                if ($requiresReason && $line->reason_code === 'other' && trim((string) $line->reason_note) === '') {
                    throw ValidationException::withMessages([
                        'line_reasons' => 'Cuando el motivo es "other", debes capturar una nota.',
                    ]);
                }
            }

            $shipmentDb->signed_by_name = trim((string) $data['signed_by_name']);
            $shipmentDb->signed_by_role = trim((string) $data['signed_by_role']);
            $shipmentDb->signature_data = (string) $data['signature_data'];
            $shipmentDb->loading_completed_at = now();
            $shipmentDb->notes = trim((string) ($data['notes'] ?? '')) ?: $shipmentDb->notes;
            $shipmentDb->updated_by = auth()->id();

            $this->syncShipmentMetrics($shipmentDb->id);

            $shipmentDb->refresh();

            $shipmentDb->status = ((int) $shipmentDb->missing_qty <= 0 && (int) $shipmentDb->missing_boxes <= 0)
                ? 'loaded_complete'
                : 'loaded_partial';

            $meta = is_array($shipmentDb->meta) ? $shipmentDb->meta : [];
            $meta['closed_at'] = now()->toDateTimeString();
            $meta['closed_by'] = auth()->id();
            $shipmentDb->meta = $meta;

            $shipmentDb->save();
        });

        return response()->json([
            'ok' => true,
            'message' => 'Embarque cerrado correctamente.',
            'shipment' => $this->shipmentPayload($this->reloadShipment($shipment->id)),
        ]);
    }

    public function dispatch(Request $request, WmsShipment $shipment)
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $shipment = $this->reloadShipment($shipment->id);

        if (!in_array($shipment->status, ['loaded_complete', 'loaded_partial'], true)) {
            throw ValidationException::withMessages([
                'shipment' => 'Primero debes cerrar el embarque.',
            ]);
        }

        if (empty($shipment->signature_data)) {
            throw ValidationException::withMessages([
                'signature_data' => 'No puedes despachar sin firma.',
            ]);
        }

        $shipment->status = 'dispatched';
        $shipment->dispatched_at = now();
        $shipment->updated_by = auth()->id();

        if (!empty($data['notes'])) {
            $shipment->notes = trim((string) $data['notes']);
        }

        $meta = is_array($shipment->meta) ? $shipment->meta : [];
        $meta['dispatched_by'] = auth()->id();
        $meta['dispatched_at'] = now()->toDateTimeString();
        $shipment->meta = $meta;

        $shipment->save();

        return response()->json([
            'ok' => true,
            'message' => 'Unidad despachada correctamente.',
            'shipment' => $this->shipmentPayload($this->reloadShipment($shipment->id)),
        ]);
    }

    public function cancel(Request $request, WmsShipment $shipment)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $shipment = $this->reloadShipment($shipment->id);

        if ($shipment->status === 'dispatched') {
            throw ValidationException::withMessages([
                'shipment' => 'No puedes cancelar un embarque ya despachado.',
            ]);
        }

        $shipment->status = 'cancelled';
        $shipment->updated_by = auth()->id();

        $meta = is_array($shipment->meta) ? $shipment->meta : [];
        $meta['cancelled_at'] = now()->toDateTimeString();
        $meta['cancelled_by'] = auth()->id();
        $meta['cancel_reason'] = trim((string) ($data['reason'] ?? ''));
        $shipment->meta = $meta;

        $shipment->save();

        return response()->json([
            'ok' => true,
            'message' => 'Embarque cancelado.',
            'shipment' => $this->shipmentPayload($this->reloadShipment($shipment->id)),
        ]);
    }

    protected function buildShipmentLinePayload(array $item): array
    {
        $expectedAllocations = $this->normalizeBoxAllocations(
            $item['stage_box_allocations'] ?? $item['box_allocations'] ?? []
        );

        $expectedBoxLabels = array_column($expectedAllocations, 'label');

        if (empty($expectedBoxLabels)) {
            $expectedBoxLabels = $this->normalizeUpperStringList(
                $item['staged_boxes'] ?? $item['scanned_boxes'] ?? $item['box_labels'] ?? []
            );
        }

        $expectedQty = max(0, (int) ($item['quantity_staged'] ?? 0));
        if ($expectedQty <= 0) {
            $expectedQty = max(0, (int) ($item['quantity_picked'] ?? 0));
        }
        if ($expectedQty <= 0) {
            $expectedQty = max(0, (int) ($item['quantity_required'] ?? 0));
        }

        $allocationQty = collect($expectedAllocations)->sum(fn ($row) => max(0, (int) ($row['pieces'] ?? 0)));
        if ($allocationQty > $expectedQty) {
            $expectedQty = $allocationQty;
        }

        $phase = max(1, (int) ($item['delivery_phase'] ?? $item['phase'] ?? 1));

        return [
            'pick_line_id' => (string) ($item['line_id'] ?? (string) Str::uuid()),
            'product_id' => !empty($item['product_id']) ? (int) $item['product_id'] : null,
            'product_name' => (string) ($item['product_name'] ?? 'Producto'),
            'product_sku' => strtoupper((string) ($item['product_sku'] ?? '')),
            'batch_code' => strtoupper((string) ($item['batch_code'] ?? '')) ?: null,
            'location_code' => (string) ($item['location_code'] ?? ''),
            'staging_location_code' => (string) ($item['staging_location_code'] ?? ''),
            'is_fastflow' => (bool) ($item['is_fastflow'] ?? false),
            'phase' => $phase,

            'expected_qty' => $expectedQty,
            'loaded_qty' => 0,
            'missing_qty' => $expectedQty,
            'extra_qty' => 0,

            'expected_boxes' => count($expectedBoxLabels),
            'loaded_boxes' => 0,
            'missing_boxes' => count($expectedBoxLabels),

            'status' => 'pending',
            'reason_code' => null,
            'reason_note' => null,

            'expected_boxes_json' => array_values($expectedBoxLabels),
            'loaded_boxes_json' => [],
            'expected_allocations_json' => array_values($expectedAllocations),
            'loaded_allocations_json' => [],

            'meta' => [
                'product_barcode' => strtoupper((string) ($item['product_barcode'] ?? '')),
                'product_code' => strtoupper((string) ($item['product_code'] ?? '')),
                'brand' => (string) ($item['brand'] ?? $item['brand_name'] ?? ''),
                'model' => (string) ($item['model'] ?? $item['model_name'] ?? ''),
                'description' => (string) ($item['description'] ?? ''),
                'units_per_box' => max(0, (int) ($item['units_per_box'] ?? 0)),
                'source_item' => $item,
            ],
        ];
    }

    protected function startLoadingIfNeeded(WmsShipment $shipment): void
    {
        if ($shipment->status === 'draft') {
            $shipment->status = 'loading';
        }

        if (!$shipment->loading_started_at) {
            $shipment->loading_started_at = now();
        }

        $shipment->updated_by = auth()->id();
        $shipment->save();
    }

    protected function resolveLabelQty(WmsShipmentLine $line, string $label): int
    {
        $label = strtoupper(trim($label));

        foreach ((array) ($line->expected_allocations_json ?? []) as $row) {
            $rowLabel = strtoupper(trim((string) ($row['label'] ?? '')));
            if ($rowLabel === $label) {
                return max(0, (int) ($row['pieces'] ?? 0));
            }
        }

        $unitsPerBox = max(0, (int) data_get($line->meta, 'units_per_box', 0));
        if ($unitsPerBox > 0) {
            return $unitsPerBox;
        }

        $remaining = max(0, (int) $line->expected_qty - (int) $line->loaded_qty);

        return $remaining > 0 ? $remaining : 0;
    }

    protected function matchLineForLabel(WmsShipment $shipment, string $label): ?WmsShipmentLine
    {
        $label = strtoupper(trim($label));

        $lines = $shipment->lines
            ->sortBy(fn ($line) => sprintf('%08d-%08d', (int) $line->phase, (int) $line->id))
            ->values();

        foreach ($lines as $line) {
            $expectedBoxes = $this->normalizeUpperStringList($line->expected_boxes_json ?? []);
            if (in_array($label, $expectedBoxes, true)) {
                return $line;
            }

            foreach ((array) ($line->expected_allocations_json ?? []) as $row) {
                $rowLabel = strtoupper(trim((string) ($row['label'] ?? '')));
                if ($rowLabel === $label) {
                    return $line;
                }
            }
        }

        return null;
    }

    protected function matchLineForProductCode(WmsShipment $shipment, string $code, ?string $pickLineId = null, ?int $productId = null): ?WmsShipmentLine
    {
        $code = strtoupper(trim($code));

        $lines = $shipment->lines
            ->sortBy(fn ($line) => sprintf('%08d-%08d', (int) $line->phase, (int) $line->id))
            ->values();

        if ($pickLineId) {
            $match = $lines->first(function ($line) use ($pickLineId) {
                return (string) $line->pick_line_id === (string) $pickLineId
                    || (string) $line->id === (string) $pickLineId;
            });

            if ($match) {
                return $match;
            }
        }

        $filtered = $lines->filter(function ($line) use ($productId, $code) {
            $meta = is_array($line->meta) ? $line->meta : [];

            if ($productId && (int) $line->product_id === (int) $productId) {
                return true;
            }

            return in_array($code, array_filter([
                strtoupper((string) ($line->product_sku ?? '')),
                strtoupper((string) ($line->batch_code ?? '')),
                strtoupper((string) ($meta['product_barcode'] ?? '')),
                strtoupper((string) ($meta['product_code'] ?? '')),
            ]), true);
        });

        $pending = $filtered->first(function ($line) {
            return (int) $line->loaded_qty < (int) $line->expected_qty;
        });

        return $pending ?: $filtered->first();
    }

    protected function shipmentHasLoadedLabel(WmsShipment $shipment, string $label): bool
    {
        $label = strtoupper(trim($label));

        foreach ($shipment->lines as $line) {
            $loaded = $this->normalizeUpperStringList($line->loaded_boxes_json ?? []);
            if (in_array($label, $loaded, true)) {
                return true;
            }
        }

        return false;
    }

    protected function syncLineMetrics(WmsShipmentLine $line): void
    {
        $expectedQty = max(0, (int) $line->expected_qty);
        $loadedQty = max(0, (int) $line->loaded_qty);

        $expectedBoxes = max(0, count($this->normalizeUpperStringList($line->expected_boxes_json ?? [])));
        $loadedBoxes = max(0, count($this->normalizeUpperStringList($line->loaded_boxes_json ?? [])));

        $line->expected_boxes = $expectedBoxes;
        $line->loaded_boxes = $loadedBoxes;

        $line->missing_qty = max(0, $expectedQty - $loadedQty);
        $line->extra_qty = max(0, $loadedQty - $expectedQty);
        $line->missing_boxes = max(0, $expectedBoxes - $loadedBoxes);

        if ($loadedQty <= 0 && $loadedBoxes <= 0) {
            $line->status = 'pending';
        } elseif ($line->missing_qty <= 0 && $line->missing_boxes <= 0) {
            $line->status = 'complete';
        } else {
            $line->status = 'partial';
        }

        $line->save();
    }

    protected function syncShipmentMetrics(int $shipmentId): void
    {
        $shipment = WmsShipment::query()->findOrFail($shipmentId);

        $lines = WmsShipmentLine::query()
            ->where('shipment_id', $shipmentId)
            ->get();

        $shipment->expected_lines = (int) $lines->count();
        $shipment->scanned_lines = (int) $lines->filter(function ($line) {
            return ((int) $line->loaded_qty > 0) || ((int) $line->loaded_boxes > 0);
        })->count();

        $shipment->expected_qty = (int) $lines->sum('expected_qty');
        $shipment->loaded_qty = (int) $lines->sum('loaded_qty');
        $shipment->missing_qty = (int) $lines->sum('missing_qty');
        $shipment->extra_qty = (int) $lines->sum('extra_qty');

        $shipment->expected_boxes = (int) $lines->sum('expected_boxes');
        $shipment->loaded_boxes = (int) $lines->sum('loaded_boxes');
        $shipment->missing_boxes = (int) $lines->sum('missing_boxes');

        $shipment->updated_by = auth()->id();
        $shipment->save();
    }

    protected function createScanLog(
        int $shipmentId,
        ?int $lineId,
        string $scanType,
        string $scanValue,
        int $qty,
        ?string $boxLabel,
        string $result,
        string $message,
        array $payload = []
    ): void {
        WmsShipmentScan::create([
            'shipment_id' => $shipmentId,
            'shipment_line_id' => $lineId,
            'scan_type' => $scanType,
            'scan_value' => strtoupper(trim($scanValue)),
            'qty' => max(0, $qty),
            'box_label' => $boxLabel ? strtoupper(trim($boxLabel)) : null,
            'result' => $result,
            'message' => $message,
            'payload' => $payload,
            'user_id' => auth()->id(),
        ]);
    }

    protected function shipmentPayload(WmsShipment $shipment): array
    {
        $shipment->loadMissing([
            'warehouse:id,name,code',
            'operator:id,name',
            'lines',
            'scans' => function ($q) {
                $q->latest('id')->limit(100);
            },
        ]);

        $lines = $shipment->lines
            ->sortBy(fn ($line) => sprintf('%08d-%08d', (int) $line->phase, (int) $line->id))
            ->values()
            ->map(function (WmsShipmentLine $line) {
                return [
                    'id' => $line->id,
                    'pick_line_id' => $line->pick_line_id,
                    'product_id' => $line->product_id,
                    'product_name' => $line->product_name,
                    'product_sku' => $line->product_sku,
                    'batch_code' => $line->batch_code,
                    'location_code' => $line->location_code,
                    'staging_location_code' => $line->staging_location_code,
                    'is_fastflow' => (bool) $line->is_fastflow,
                    'phase' => (int) $line->phase,
                    'expected_qty' => (int) $line->expected_qty,
                    'loaded_qty' => (int) $line->loaded_qty,
                    'missing_qty' => (int) $line->missing_qty,
                    'extra_qty' => (int) $line->extra_qty,
                    'expected_boxes' => (int) $line->expected_boxes,
                    'loaded_boxes' => (int) $line->loaded_boxes,
                    'missing_boxes' => (int) $line->missing_boxes,
                    'status' => (string) $line->status,
                    'reason_code' => $line->reason_code,
                    'reason_note' => $line->reason_note,
                    'expected_boxes_json' => $line->expected_boxes_json ?? [],
                    'loaded_boxes_json' => $line->loaded_boxes_json ?? [],
                    'expected_allocations_json' => $line->expected_allocations_json ?? [],
                    'loaded_allocations_json' => $line->loaded_allocations_json ?? [],
                    'meta' => $line->meta ?? [],
                ];
            })
            ->all();

        $scans = $shipment->scans
            ->sortByDesc('id')
            ->values()
            ->map(function (WmsShipmentScan $scan) {
                return [
                    'id' => $scan->id,
                    'shipment_line_id' => $scan->shipment_line_id,
                    'scan_type' => $scan->scan_type,
                    'scan_value' => $scan->scan_value,
                    'qty' => (int) $scan->qty,
                    'box_label' => $scan->box_label,
                    'result' => $scan->result,
                    'message' => $scan->message,
                    'payload' => $scan->payload ?? [],
                    'user_id' => $scan->user_id,
                    'created_at' => optional($scan->created_at)?->toDateTimeString(),
                ];
            })
            ->all();

        return [
            'id' => $shipment->id,
            'pick_wave_id' => $shipment->pick_wave_id,
            'warehouse_id' => $shipment->warehouse_id,
            'warehouse_name' => $shipment->warehouse?->name,
            'warehouse_code' => $shipment->warehouse?->code,
            'shipment_number' => $shipment->shipment_number,
            'order_number' => $shipment->order_number,
            'task_number' => $shipment->task_number,
            'vehicle_plate' => $shipment->vehicle_plate,
            'vehicle_name' => $shipment->vehicle_name,
            'driver_name' => $shipment->driver_name,
            'driver_phone' => $shipment->driver_phone,
            'route_name' => $shipment->route_name,
            'operator_user_id' => $shipment->operator_user_id,
            'operator_name' => $shipment->operator?->name,
            'status' => $shipment->status,

            'expected_lines' => (int) $shipment->expected_lines,
            'scanned_lines' => (int) $shipment->scanned_lines,
            'expected_qty' => (int) $shipment->expected_qty,
            'loaded_qty' => (int) $shipment->loaded_qty,
            'missing_qty' => (int) $shipment->missing_qty,
            'extra_qty' => (int) $shipment->extra_qty,
            'expected_boxes' => (int) $shipment->expected_boxes,
            'loaded_boxes' => (int) $shipment->loaded_boxes,
            'missing_boxes' => (int) $shipment->missing_boxes,

            'loading_started_at' => optional($shipment->loading_started_at)?->toDateTimeString(),
            'loading_completed_at' => optional($shipment->loading_completed_at)?->toDateTimeString(),
            'dispatched_at' => optional($shipment->dispatched_at)?->toDateTimeString(),

            'signed_by_name' => $shipment->signed_by_name,
            'signed_by_role' => $shipment->signed_by_role,
            'signature_data' => $shipment->signature_data,
            'notes' => $shipment->notes,
            'meta' => $shipment->meta ?? [],

            'lines' => $lines,
            'scans' => $scans,
        ];
    }

    protected function reloadShipment(int $shipmentId): WmsShipment
    {
        return WmsShipment::query()
            ->with([
                'warehouse:id,name,code',
                'operator:id,name',
                'lines',
                'scans' => function ($q) {
                    $q->latest('id')->limit(100);
                },
            ])
            ->findOrFail($shipmentId);
    }

    protected function nextShipmentNumber(): string
    {
        $prefix = 'SHIP-';
        $max = 0;

        $rows = WmsShipment::query()
            ->orderByDesc('id')
            ->limit(500)
            ->get(['id', 'shipment_number']);

        foreach ($rows as $row) {
            $candidate = (string) ($row->shipment_number ?? '');
            if (preg_match('/(\d+)/', $candidate, $m)) {
                $max = max($max, (int) $m[1]);
            } else {
                $max = max($max, (int) $row->id);
            }
        }

        return $prefix . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }

    protected function normalizePickWave(PickWave $task): array
    {
        $cols = $this->pickWaveColumns();
        $bag = $this->pickWaveBag($task);

        $items = collect(is_array($bag['items'] ?? null) ? $bag['items'] : [])
            ->map(fn ($item) => $this->normalizeSingleItem(is_array($item) ? $item : []))
            ->values()
            ->all();

        return [
            'id' => $task->id,
            'warehouse_id' => (int) (
                $bag['warehouse_id']
                ?? ($cols['warehouse_id'] ? ($task->{$cols['warehouse_id']} ?? 0) : 0)
            ),
            'task_number' => (string) (
                $bag['task_number']
                ?? ($cols['task_number'] ? ($task->{$cols['task_number']} ?? null) : null)
                ?? ($cols['code'] ? ($task->{$cols['code']} ?? null) : null)
                ?? ('PICK-' . str_pad((string) $task->id, 3, '0', STR_PAD_LEFT))
            ),
            'order_number' => (string) (
                $bag['order_number']
                ?? ($cols['order_number'] ? ($task->{$cols['order_number']} ?? '') : '')
                ?? ($cols['reference'] ? ($task->{$cols['reference']} ?? '') : '')
            ),
            'assigned_user_id' => $bag['assigned_user_id']
                ?? ($cols['assigned_user_id'] ? ($task->{$cols['assigned_user_id']} ?? null) : null),
            'assigned_to' => (string) ($bag['assigned_to'] ?? ''),
            'priority' => (string) (
                $bag['priority']
                ?? ($cols['priority'] ? ($task->{$cols['priority']} ?? 'normal') : 'normal')
            ),
            'notes' => (string) (
                $bag['notes']
                ?? ($cols['notes'] ? ($task->{$cols['notes']} ?? '') : '')
            ),
            'status' => (string) (
                $bag['status']
                ?? ($cols['status'] ? $this->readStatusValue($task->{$cols['status']} ?? null) : 'pending')
            ),
            'deliveries' => is_array($bag['deliveries'] ?? null) ? $bag['deliveries'] : [],
            'items' => $items,
        ];
    }

    protected function normalizeSingleItem(array $item): array
    {
        $required = max(1, (int) ($item['quantity_required'] ?? $item['qty'] ?? 1));
        $picked = max(0, (int) ($item['quantity_picked'] ?? 0));
        $staged = max(0, (int) ($item['quantity_staged'] ?? 0));
        $phase = max(1, (int) ($item['delivery_phase'] ?? $item['phase'] ?? 1));

        $locationCode = strtoupper(trim((string) ($item['location_code'] ?? '')));
        $batchCode = strtoupper(trim((string) ($item['batch_code'] ?? '')));
        $isFastFlow = filter_var(($item['is_fastflow'] ?? false), FILTER_VALIDATE_BOOLEAN);

        if ($isFastFlow || $locationCode === 'FAST FLOW' || ($batchCode !== '' && str_starts_with($batchCode, 'FF-'))) {
            $isFastFlow = true;
            $locationCode = 'FAST FLOW';
        }

        return [
            'line_id' => (string) ($item['line_id'] ?? (string) Str::uuid()),
            'product_id' => $item['product_id'] ?? null,
            'product_sku' => strtoupper((string) ($item['product_sku'] ?? '')),
            'product_name' => (string) ($item['product_name'] ?? 'Producto'),
            'product_barcode' => strtoupper((string) ($item['product_barcode'] ?? '')),
            'product_code' => strtoupper((string) ($item['product_code'] ?? '')),
            'location_code' => $locationCode,
            'batch_code' => $batchCode,
            'quantity_required' => $required,
            'quantity_picked' => min($picked, $required),
            'quantity_staged' => min($staged, $required),
            'picked' => filter_var(($item['picked'] ?? ($picked >= $required)), FILTER_VALIDATE_BOOLEAN),
            'staged' => filter_var(($item['staged'] ?? ($staged >= $required)), FILTER_VALIDATE_BOOLEAN),
            'delivery_phase' => $phase,
            'phase' => $phase,
            'description' => (string) ($item['description'] ?? ''),
            'brand_name' => (string) ($item['brand'] ?? $item['brand_name'] ?? ''),
            'brand' => (string) ($item['brand'] ?? $item['brand_name'] ?? ''),
            'model_name' => (string) ($item['model'] ?? $item['model_name'] ?? ''),
            'model' => (string) ($item['model'] ?? $item['model_name'] ?? ''),
            'requested_quantity' => max(1, (int) ($item['requested_quantity'] ?? $required)),
            'available_stock' => max(0, (int) ($item['available_stock'] ?? 0)),
            'is_fastflow' => $isFastFlow,
            'staging_location_code' => (string) ($item['staging_location_code'] ?? ''),
            'collected_at' => $this->normalizeDateString($item['collected_at'] ?? null),
            'staged_at' => $this->normalizeDateString($item['staged_at'] ?? null),
            'units_per_box' => max(0, (int) ($item['units_per_box'] ?? 0)),
            'total_boxes' => max(0, (int) ($item['total_boxes'] ?? $item['boxes_count'] ?? 0)),
            'boxes_count' => max(0, (int) ($item['boxes_count'] ?? $item['total_boxes'] ?? 0)),
            'available_boxes_count' => max(0, (int) ($item['available_boxes_count'] ?? 0)),
            'total_pieces' => max(0, (int) ($item['total_pieces'] ?? 0)),
            'box_labels' => $this->normalizeUpperStringList($item['box_labels'] ?? []),
            'available_boxes' => $this->normalizeUpperStringList($item['available_boxes'] ?? []),
            'scanned_boxes' => $this->normalizeUpperStringList($item['scanned_boxes'] ?? []),
            'staged_boxes' => $this->normalizeUpperStringList($item['staged_boxes'] ?? []),
            'box_allocations' => $this->normalizeBoxAllocations($item['box_allocations'] ?? []),
            'stage_box_allocations' => $this->normalizeBoxAllocations($item['stage_box_allocations'] ?? []),
        ];
    }

    protected function normalizeUpperStringList($values): array
    {
        return collect((array) $values)
            ->map(fn ($v) => strtoupper(trim((string) $v)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function normalizeBoxAllocations($values): array
    {
        $merged = [];

        foreach ((array) $values as $row) {
            $label = '';
            $pieces = 0;

            if (is_string($row)) {
                $label = strtoupper(trim($row));
            } elseif (is_array($row)) {
                $label = strtoupper(trim((string) ($row['label'] ?? $row['box_label'] ?? $row['code'] ?? $row['box'] ?? '')));
                $pieces = max(0, (int) ($row['pieces'] ?? $row['qty'] ?? $row['quantity'] ?? 0));
            }

            if ($label === '') {
                continue;
            }

            $merged[$label] = ($merged[$label] ?? 0) + $pieces;
        }

        $final = [];
        foreach ($merged as $label => $pieces) {
            $final[] = [
                'label' => $label,
                'pieces' => (int) $pieces,
            ];
        }

        return array_values($final);
    }

    protected function normalizeDateString($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateTimeString();
        } catch (Throwable $e) {
            return null;
        }
    }

    protected function pickWaveBag(PickWave $task): array
    {
        $cols = $this->pickWaveColumns();
        $bag = [];

        if ($cols['json']) {
            $raw = $task->{$cols['json']};
            $decoded = $this->decodePossibleJsonValue($raw);
            if (is_array($decoded)) {
                $bag = $decoded;
            }
        }

        if ($cols['items'] && empty($bag['items'])) {
            $rawItems = $task->{$cols['items']};
            $decodedItems = $this->decodePossibleJsonValue($rawItems);
            if (is_array($decodedItems)) {
                $bag['items'] = $decodedItems;
            }
        }

        if ($cols['deliveries'] && empty($bag['deliveries'])) {
            $rawDeliveries = $task->{$cols['deliveries']};
            $decodedDeliveries = $this->decodePossibleJsonValue($rawDeliveries);
            if (is_array($decodedDeliveries)) {
                $bag['deliveries'] = $decodedDeliveries;
            }
        }

        return $bag;
    }

    protected function pickWaveColumns(): array
    {
        return [
            'json'             => $this->firstExistingColumn('pick_waves', ['meta', 'data', 'payload', 'extra']),
            'items'            => $this->firstExistingColumn('pick_waves', ['items', 'items_json']),
            'deliveries'       => $this->firstExistingColumn('pick_waves', ['deliveries', 'deliveries_json']),
            'code'             => $this->firstExistingColumn('pick_waves', ['code']),
            'task_number'      => $this->firstExistingColumn('pick_waves', ['task_number']),
            'reference'        => $this->firstExistingColumn('pick_waves', ['reference']),
            'order_number'     => $this->firstExistingColumn('pick_waves', ['order_number', 'order_ref']),
            'assigned_to'      => $this->firstExistingColumn('pick_waves', ['assigned_to', 'assignee_name', 'operator_name']),
            'assigned_user_id' => $this->firstExistingColumn('pick_waves', ['assigned_user_id']),
            'priority'         => $this->firstExistingColumn('pick_waves', ['priority']),
            'notes'            => $this->firstExistingColumn('pick_waves', ['notes', 'note', 'comments']),
            'status'           => $this->firstExistingColumn('pick_waves', ['status']),
            'warehouse_id'     => $this->firstExistingColumn('pick_waves', ['warehouse_id']),
        ];
    }

    protected function firstExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    protected function decodePossibleJsonValue($value)
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

    protected function readStatusValue($value): string
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
            default => 'pending',
        };
    }
}