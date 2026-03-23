<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Inventory;
use App\Models\PickWave;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WmsQuickBox;
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

class WmsPickingController extends Controller implements HasMiddleware
{
    protected array $fastFlowBatchCache = [];
    protected ?array $fastFlowProductCache = null;

    public static function middleware(): array
    {
        return [new Middleware('auth')];
    }

    protected function logPicking(string $message, array $context = [], string $level = 'info'): void
    {
        Log::log($level, '[WMS PICKING] ' . $message, $context);
    }

    protected function requestLogContext(Request $request): array
    {
        return [
            'method'           => $request->method(),
            'url'              => $request->fullUrl(),
            'task_number'      => $request->input('task_number'),
            'order_number'     => $request->input('order_number'),
            'assigned_user_id' => $request->input('assigned_user_id'),
            'priority'         => $request->input('priority'),
            'status'           => $request->input('status'),
            'total_phases'     => $request->input('total_phases'),
            'items_count'      => is_array($request->input('items')) ? count($request->input('items')) : 0,
            'deliveries_count' => is_array($request->input('deliveries')) ? count($request->input('deliveries')) : 0,
            'items'            => $request->input('items', []),
            'deliveries'       => $request->input('deliveries', []),
        ];
    }

    protected function itemLogContext(array $item): array
    {
        return [
            'line_id'               => data_get($item, 'line_id'),
            'product_id'            => data_get($item, 'product_id'),
            'product_name'          => data_get($item, 'product_name'),
            'product_sku'           => data_get($item, 'product_sku'),
            'location_code'         => data_get($item, 'location_code'),
            'batch_code'            => data_get($item, 'batch_code'),
            'quantity_required'     => data_get($item, 'quantity_required'),
            'delivery_phase'        => data_get($item, 'delivery_phase'),
            'phase'                 => data_get($item, 'phase'),
            'is_fastflow'           => data_get($item, 'is_fastflow'),
            'available_stock'       => data_get($item, 'available_stock'),
            'units_per_box'         => data_get($item, 'units_per_box'),
            'total_boxes'           => data_get($item, 'total_boxes'),
            'available_boxes_count' => data_get($item, 'available_boxes_count'),
        ];
    }

    public function indexV2()
    {
        return view('admin.wms.picking-v2', [
            'tasks'              => $this->tasksData(),
            'products'           => $this->productsData(),
            'users'              => $this->usersData(),
            'nextTaskNumber'     => $this->getNextTaskNumber(),
            'defaultWarehouseId' => $this->getDefaultWarehouseId(),
            'recentBatches'      => $this->recentBatchesData(),
        ]);
    }

    public function createV2()
    {
        return view('admin.wms.picking-v2-create', [
            'task'               => null,
            'products'           => $this->productsData(),
            'users'              => $this->usersData(),
            'nextTaskNumber'     => $this->getNextTaskNumber(),
            'defaultWarehouseId' => $this->getDefaultWarehouseId(),
            'recentBatches'      => $this->recentBatchesData(),
        ]);
    }

    public function editV2(PickWave $pickWave)
    {
        $normalizedTask = $this->normalizeTask($pickWave);

        return view('admin.wms.picking-v2-edit', [
            'task'               => $normalizedTask,
            'products'           => $this->productsData(),
            'users'              => $this->usersData(),
            'nextTaskNumber'     => $normalizedTask['task_number'],
            'defaultWarehouseId' => $this->getDefaultWarehouseId(),
            'recentBatches'      => $this->recentBatchesData(),
        ]);
    }

    public function storeV2(Request $request)
    {
        $this->logPicking('storeV2.start', $this->requestLogContext($request));

        try {
            $data = $this->validateForm($request);

            $this->logPicking('storeV2.validated', [
                'task_number'      => $data['task_number'] ?? null,
                'order_number'     => $data['order_number'] ?? null,
                'assigned_user_id' => $data['assigned_user_id'] ?? null,
                'priority'         => $data['priority'] ?? null,
                'total_phases'     => $data['total_phases'] ?? null,
                'items_count'      => count($data['items'] ?? []),
                'deliveries_count' => count($data['deliveries'] ?? []),
                'items'            => $data['items'] ?? [],
            ]);

            $warehouseId = $this->resolveWarehouseId(
                isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : null
            );

            $assignedUserId = !empty($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : null;
            $assignedTo = '';

            if ($assignedUserId) {
                $user = User::query()->find($assignedUserId);
                $assignedTo = (string) ($user->name ?? '');
            }

            $payload = [
                'warehouse_id'     => $warehouseId,
                'task_number'      => trim((string) ($data['task_number'] ?? '')) !== ''
                    ? trim((string) $data['task_number'])
                    : $this->getNextTaskNumber(),
                'order_number'     => trim((string) ($data['order_number'] ?? '')),
                'assigned_user_id' => $assignedUserId,
                'assigned_to'      => $assignedTo,
                'priority'         => $data['priority'] ?? 'normal',
                'notes'            => (string) ($data['notes'] ?? ''),
                'status'           => 'pending',
                'started_at'       => null,
                'completed_at'     => null,
                'total_phases'     => (int) ($data['total_phases'] ?? 1),
                'deliveries'       => $data['deliveries'] ?? [],
                'items'            => $data['items'] ?? [],
            ];

            $this->logPicking('storeV2.payload_ready', [
                'payload' => $payload,
            ]);

            $createdTaskId = null;

            DB::transaction(function () use ($payload, &$createdTaskId) {
                $this->logPicking('storeV2.transaction.begin', [
                    'task_number' => $payload['task_number'] ?? null,
                ]);

                $pickWave = new PickWave();
                $this->persistTask($pickWave, $payload);

                $createdTaskId = $pickWave->id;

                $this->logPicking('storeV2.persisted', [
                    'pick_wave_id' => $pickWave->id,
                    'task_number'  => $payload['task_number'] ?? null,
                ]);

                $this->reserveStockForTask($pickWave);

                $this->logPicking('storeV2.stock_reserved', [
                    'pick_wave_id' => $pickWave->id,
                    'task_number'  => $payload['task_number'] ?? null,
                ]);
            });

            $this->logPicking('storeV2.success', [
                'pick_wave_id' => $createdTaskId,
                'task_number'  => $payload['task_number'] ?? null,
            ]);

            return redirect()
                ->route('admin.wms.picking.v2')
                ->with('ok', 'Tarea de picking creada correctamente.');
        } catch (ValidationException $e) {
            $this->logPicking('storeV2.validation_exception', [
                'errors'  => $e->errors(),
                'request' => $this->requestLogContext($request),
            ], 'warning');

            throw $e;
        } catch (Throwable $e) {
            $this->logPicking('storeV2.exception', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
                'request' => $this->requestLogContext($request),
            ], 'error');

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'Error al guardar la tarea. Revisa storage/logs/laravel.log',
                ]);
        }
    }

    public function updateV2(Request $request, PickWave $pickWave)
    {
        $this->logPicking('updateV2.start', [
            'pick_wave_id' => $pickWave->id,
            'request'      => $this->requestLogContext($request),
        ]);

        try {
            $data = $this->validateForm($request, true);
            $current = $this->normalizeTask($pickWave);
            $currentBag = $this->pickWaveBag($pickWave);

            $oldStatus = (string) ($current['status'] ?? 'pending');
            $wasReserved = (bool) data_get($currentBag, 'stock_reserved', false);
            $wasConsumed = (bool) data_get($currentBag, 'stock_consumed', false);

            $this->logPicking('updateV2.state_before', [
                'pick_wave_id' => $pickWave->id,
                'old_status'   => $oldStatus,
                'was_reserved' => $wasReserved,
                'was_consumed' => $wasConsumed,
                'validated'    => $data,
            ]);

            if ($wasConsumed && $oldStatus === 'completed' && (($data['status'] ?? $oldStatus) !== 'completed')) {
                throw ValidationException::withMessages([
                    'status' => 'La tarea ya fue completada y ya descontó inventario. No se puede regresar automáticamente a otro estado.',
                ]);
            }

            $assignedUserId = array_key_exists('assigned_user_id', $data)
                ? (!empty($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : null)
                : ($current['assigned_user_id'] ?: null);

            $assignedTo = '';
            if ($assignedUserId) {
                $user = User::query()->find($assignedUserId);
                $assignedTo = (string) ($user->name ?? '');
            } elseif (!array_key_exists('assigned_user_id', $data)) {
                $assignedTo = (string) ($current['assigned_to'] ?? '');
            }

            $payload = [
                'warehouse_id'     => array_key_exists('warehouse_id', $data)
                    ? $this->resolveWarehouseId((int) $data['warehouse_id'])
                    : $current['warehouse_id'],
                'task_number'      => array_key_exists('task_number', $data) && trim((string) $data['task_number']) !== ''
                    ? trim((string) $data['task_number'])
                    : $current['task_number'],
                'order_number'     => array_key_exists('order_number', $data)
                    ? trim((string) $data['order_number'])
                    : $current['order_number'],
                'assigned_user_id' => $assignedUserId,
                'assigned_to'      => $assignedTo,
                'priority'         => $data['priority'] ?? $current['priority'],
                'notes'            => array_key_exists('notes', $data) ? (string) $data['notes'] : $current['notes'],
                'status'           => $data['status'] ?? $current['status'],
                'started_at'       => $data['started_at'] ?? $current['started_at'],
                'completed_at'     => $data['completed_at'] ?? $current['completed_at'],
                'total_phases'     => (int) ($data['total_phases'] ?? $current['total_phases']),
                'deliveries'       => $data['deliveries'] ?? $current['deliveries'],
                'items'            => $data['items'] ?? $current['items'],
            ];

            if ($payload['status'] === 'in_progress' && empty($payload['started_at'])) {
                $payload['started_at'] = now()->toDateTimeString();
            }

            if ($payload['status'] === 'completed' && empty($payload['completed_at'])) {
                $payload['completed_at'] = now()->toDateTimeString();
            }

            if ($payload['status'] !== 'completed') {
                $payload['completed_at'] = null;
            }

            $this->logPicking('updateV2.payload_ready', [
                'pick_wave_id' => $pickWave->id,
                'payload'      => $payload,
            ]);

            DB::transaction(function () use ($pickWave, $payload, $wasReserved, $wasConsumed) {
                if ($wasReserved) {
                    $this->logPicking('updateV2.release_reserved.before', [
                        'pick_wave_id' => $pickWave->id,
                    ]);

                    $this->releaseReservedStockForTask($pickWave);
                    $pickWave->refresh();

                    $this->logPicking('updateV2.release_reserved.after', [
                        'pick_wave_id' => $pickWave->id,
                    ]);
                }

                $this->persistTask($pickWave, $payload);
                $pickWave->refresh();

                $newStatus = (string) ($payload['status'] ?? 'pending');

                $this->logPicking('updateV2.after_persist', [
                    'pick_wave_id' => $pickWave->id,
                    'new_status'   => $newStatus,
                ]);

                if (in_array($newStatus, ['pending', 'in_progress'], true)) {
                    $this->reserveStockForTask($pickWave);

                    $this->logPicking('updateV2.reserve_after_update', [
                        'pick_wave_id' => $pickWave->id,
                        'new_status'   => $newStatus,
                    ]);
                } elseif ($newStatus === 'completed' && !$wasConsumed) {
                    $this->reserveStockForTask($pickWave);
                    $pickWave->refresh();

                    $this->logPicking('updateV2.reserve_before_consume', [
                        'pick_wave_id' => $pickWave->id,
                    ]);

                    $this->consumeReservedStockForTask($pickWave);

                    $this->logPicking('updateV2.consume_done', [
                        'pick_wave_id' => $pickWave->id,
                    ]);
                } else {
                    $this->setTaskReservationState($pickWave, false, $wasConsumed, []);

                    $this->logPicking('updateV2.set_reservation_state_only', [
                        'pick_wave_id' => $pickWave->id,
                        'reserved'     => false,
                        'consumed'     => $wasConsumed,
                    ]);
                }
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'ok'   => true,
                    'task' => $this->normalizeTask($pickWave->fresh()),
                ]);
            }

            return redirect()
                ->route('admin.wms.picking.v2.edit', $pickWave)
                ->with('ok', 'Tarea actualizada correctamente.');
        } catch (ValidationException $e) {
            $this->logPicking('updateV2.validation_exception', [
                'pick_wave_id' => $pickWave->id,
                'errors'       => $e->errors(),
                'request'      => $this->requestLogContext($request),
            ], 'warning');

            throw $e;
        } catch (Throwable $e) {
            $this->logPicking('updateV2.exception', [
                'pick_wave_id' => $pickWave->id,
                'message'      => $e->getMessage(),
                'file'         => $e->getFile(),
                'line'         => $e->getLine(),
                'trace'        => $e->getTraceAsString(),
                'request'      => $this->requestLogContext($request),
            ], 'error');

            if ($request->expectsJson()) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Error al actualizar la tarea. Revisa storage/logs/laravel.log',
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'Error al actualizar la tarea. Revisa storage/logs/laravel.log',
                ]);
        }
    }

    protected function validateForm(Request $request, bool $isUpdate = false): array
    {
        $this->logPicking('validateForm.start', [
            'is_update' => $isUpdate,
            'request'   => $this->requestLogContext($request),
        ]);

        $rules = [
            'warehouse_id'      => ['nullable', 'integer', 'exists:warehouses,id'],
            'task_number'       => [$isUpdate ? 'nullable' : 'required', 'string', 'max:120'],
            'order_number'      => ['nullable', 'string', 'max:120'],
            'assigned_user_id'  => [$isUpdate ? 'nullable' : 'required', 'integer', 'exists:users,id'],
            'priority'          => [$isUpdate ? 'nullable' : 'required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'total_phases'      => [$isUpdate ? 'nullable' : 'required', 'integer', 'min:1', 'max:12'],
            'notes'             => ['nullable', 'string', 'max:5000'],

            'deliveries'                 => [$isUpdate ? 'nullable' : 'required', 'array', 'min:1'],
            'deliveries.*.phase'         => ['required_with:deliveries', 'integer', 'min:1', 'max:12'],
            'deliveries.*.title'         => ['nullable', 'string', 'max:120'],
            'deliveries.*.scheduled_for' => ['nullable'],
            'deliveries.*.notes'         => ['nullable', 'string', 'max:1000'],

            'items'                               => [$isUpdate ? 'nullable' : 'required', 'array', 'min:1'],
            'items.*.line_id'                     => ['nullable', 'string', 'max:120'],
            'items.*.product_id'                  => ['nullable'],
            'items.*.product_name'                => ['nullable', 'string', 'max:255'],
            'items.*.product_sku'                 => ['nullable', 'string', 'max:255'],
            'items.*.location_code'               => ['nullable', 'string', 'max:120'],
            'items.*.batch_code'                  => ['nullable', 'string', 'max:120'],
            'items.*.quantity_required'           => ['nullable', 'integer', 'min:1'],
            'items.*.quantity_picked'             => ['nullable', 'integer', 'min:0'],
            'items.*.quantity_staged'             => ['nullable', 'integer', 'min:0'],
            'items.*.picked'                      => ['nullable'],
            'items.*.staged'                      => ['nullable'],
            'items.*.delivery_phase'              => ['nullable', 'integer', 'min:1', 'max:12'],
            'items.*.phase'                       => ['nullable', 'integer', 'min:1', 'max:12'],
            'items.*.description'                 => ['nullable', 'string', 'max:5000'],
            'items.*.brand'                       => ['nullable', 'string', 'max:255'],
            'items.*.model'                       => ['nullable', 'string', 'max:255'],
            'items.*.is_fastflow'                 => ['nullable'],
            'items.*.available_stock'             => ['nullable', 'integer', 'min:0'],
            'items.*.product_barcode'             => ['nullable', 'string', 'max:255'],
            'items.*.product_code'                => ['nullable', 'string', 'max:255'],
            'items.*.staging_location_code'       => ['nullable', 'string', 'max:120'],
            'items.*.collected_at'                => ['nullable'],
            'items.*.staged_at'                   => ['nullable'],
            'items.*.units_per_box'               => ['nullable', 'integer', 'min:0'],
            'items.*.total_boxes'                 => ['nullable', 'integer', 'min:0'],
            'items.*.boxes_count'                 => ['nullable', 'integer', 'min:0'],
            'items.*.available_boxes_count'       => ['nullable', 'integer', 'min:0'],
            'items.*.total_pieces'                => ['nullable', 'integer', 'min:0'],
            'items.*.box_labels'                  => ['nullable', 'array'],
            'items.*.box_labels.*'                => ['nullable', 'string', 'max:120'],
            'items.*.available_boxes'             => ['nullable', 'array'],
            'items.*.available_boxes.*'           => ['nullable', 'string', 'max:120'],
            'items.*.scanned_boxes'               => ['nullable', 'array'],
            'items.*.scanned_boxes.*'             => ['nullable', 'string', 'max:120'],
            'items.*.staged_boxes'                => ['nullable', 'array'],
            'items.*.staged_boxes.*'              => ['nullable', 'string', 'max:120'],
            'items.*.box_allocations'             => ['nullable', 'array'],
            'items.*.box_allocations.*.label'     => ['nullable', 'string', 'max:120'],
            'items.*.box_allocations.*.pieces'    => ['nullable', 'integer', 'min:0'],
            'items.*.stage_box_allocations'       => ['nullable', 'array'],
            'items.*.stage_box_allocations.*.label'  => ['nullable', 'string', 'max:120'],
            'items.*.stage_box_allocations.*.pieces' => ['nullable', 'integer', 'min:0'],

            'status'       => ['nullable', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
            'started_at'   => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
        ];

        $data = $request->validate($rules);

        $totalPhases = max(1, (int) ($data['total_phases'] ?? 1));

        if (array_key_exists('deliveries', $data)) {
            $data['deliveries'] = $this->normalizeDeliveries($data['deliveries'] ?? [], $totalPhases);
        }

        if (array_key_exists('items', $data)) {
            $data['items'] = $this->normalizeItems($data['items'] ?? [], $totalPhases);
        }

        if (!$isUpdate) {
            if (empty($data['items'])) {
                $this->logPicking('validateForm.error.no_items', [], 'warning');

                throw ValidationException::withMessages([
                    'items' => 'Debes agregar al menos un producto.',
                ]);
            }

            if (empty($data['deliveries'])) {
                $this->logPicking('validateForm.error.no_deliveries', [], 'warning');

                throw ValidationException::withMessages([
                    'deliveries' => 'Debes agregar al menos una entrega.',
                ]);
            }
        }

        $this->logPicking('validateForm.ok', [
            'is_update'        => $isUpdate,
            'total_phases'     => $totalPhases,
            'items_count'      => count($data['items'] ?? []),
            'deliveries_count' => count($data['deliveries'] ?? []),
            'items'            => $data['items'] ?? [],
        ]);

        return $data;
    }

    protected function normalizeItems(array $items, int $totalPhases): array
    {
        $final = [];

        foreach ($items as $row) {
            if (!is_array($row)) {
                continue;
            }

            $phase = (int) ($row['delivery_phase'] ?? $row['phase'] ?? 1);
            $phase = min(max(1, $phase), max(1, $totalPhases));

            $normalized = $this->normalizeSingleItem([
                'line_id'                  => $row['line_id'] ?? (string) Str::uuid(),
                'product_id'               => $row['product_id'] ?? null,
                'product_sku'              => $row['product_sku'] ?? '',
                'product_name'             => $row['product_name'] ?? '',
                'product_barcode'          => $row['product_barcode'] ?? '',
                'product_code'             => $row['product_code'] ?? '',
                'location_code'            => $row['location_code'] ?? '',
                'batch_code'               => $row['batch_code'] ?? '',
                'quantity_required'        => $row['quantity_required'] ?? 1,
                'quantity_picked'          => $row['quantity_picked'] ?? 0,
                'quantity_staged'          => $row['quantity_staged'] ?? 0,
                'picked'                   => $row['picked'] ?? false,
                'staged'                   => $row['staged'] ?? false,
                'delivery_phase'           => $phase,
                'phase'                    => $phase,
                'description'              => $row['description'] ?? '',
                'brand'                    => $row['brand'] ?? '',
                'model'                    => $row['model'] ?? '',
                'is_fastflow'              => $row['is_fastflow'] ?? false,
                'available_stock'          => $row['available_stock'] ?? 0,
                'staging_location_code'    => $row['staging_location_code'] ?? '',
                'collected_at'             => $row['collected_at'] ?? null,
                'staged_at'                => $row['staged_at'] ?? null,
                'units_per_box'            => $row['units_per_box'] ?? 0,
                'total_boxes'              => $row['total_boxes'] ?? ($row['boxes_count'] ?? 0),
                'boxes_count'              => $row['boxes_count'] ?? ($row['total_boxes'] ?? 0),
                'available_boxes_count'    => $row['available_boxes_count'] ?? 0,
                'total_pieces'             => $row['total_pieces'] ?? 0,
                'box_labels'               => $row['box_labels'] ?? [],
                'available_boxes'          => $row['available_boxes'] ?? [],
                'scanned_boxes'            => $row['scanned_boxes'] ?? [],
                'staged_boxes'             => $row['staged_boxes'] ?? [],
                'box_allocations'          => $row['box_allocations'] ?? [],
                'stage_box_allocations'    => $row['stage_box_allocations'] ?? [],
            ]);

            if ($normalized['product_name'] !== '' || $normalized['product_sku'] !== '') {
                $final[] = $normalized;
            }
        }

        return array_values($final);
    }

    protected function normalizeDeliveries(array $deliveries, int $totalPhases): array
    {
        $final = [];

        for ($phase = 1; $phase <= $totalPhases; $phase++) {
            $found = collect($deliveries)->first(function ($delivery) use ($phase) {
                return is_array($delivery) && (int) ($delivery['phase'] ?? 0) === $phase;
            });

            $final[] = [
                'phase'         => $phase,
                'title'         => trim((string) ($found['title'] ?? 'Entrega ' . $phase)) ?: 'Entrega ' . $phase,
                'scheduled_for' => $this->normalizeDateString($found['scheduled_for'] ?? null),
                'notes'         => (string) ($found['notes'] ?? ''),
            ];
        }

        return $final;
    }

    protected function tasksData(): array
    {
        return PickWave::query()
            ->orderByDesc('created_at')
            ->limit(200)
            ->get()
            ->map(fn ($task) => $this->normalizeTask($task))
            ->values()
            ->all();
    }

    protected function usersData(): array
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($u) => [
                'id'   => (int) $u->id,
                'name' => (string) $u->name,
            ])
            ->values()
            ->all();
    }

    protected function recentBatchesData(): array
    {
        if (!Schema::hasTable('wms_quick_boxes')) {
            return [];
        }

        $hasReservedUnits = Schema::hasColumn('wms_quick_boxes', 'reserved_units');

        return WmsQuickBox::query()
            ->with([
                'item:id,name,sku',
                'warehouse:id,name,code',
            ])
            ->orderByDesc('id')
            ->limit(300)
            ->get()
            ->groupBy('batch_code')
            ->map(function ($rows) use ($hasReservedUnits) {
                $first = $rows->sortBy('box_number')->first();

                $availableBoxes = 0;
                $availableUnits = 0;
                $reservedUnits  = 0;

                foreach ($rows as $row) {
                    $currentUnits = (int) ($row->current_units ?? 0);
                    $reserved = $hasReservedUnits ? (int) ($row->reserved_units ?? 0) : 0;
                    $freeUnits = max(0, $currentUnits - $reserved);

                    $reservedUnits += $reserved;

                    if (in_array((string) $row->status, ['available', 'partial'], true) && $freeUnits > 0) {
                        $availableBoxes++;
                        $availableUnits += $freeUnits;
                    }
                }

                return [
                    'batch_code'      => (string) ($first->batch_code ?? ''),
                    'product_name'    => (string) (optional($first->item)->name ?? 'Producto'),
                    'sku'             => (string) (optional($first->item)->sku ?? ''),
                    'warehouse_name'  => (string) (optional($first->warehouse)->name ?? '—'),
                    'boxes_count'     => (int) $rows->count(),
                    'units_per_box'   => (int) ($first->units_per_box ?? 0),
                    'total_units'     => (int) $rows->sum(fn ($r) => (int) ($r->current_units ?? 0)),
                    'available_boxes' => (int) $availableBoxes,
                    'available_units' => (int) $availableUnits,
                    'reserved_units'  => (int) $reservedUnits,
                ];
            })
            ->values()
            ->all();
    }

    protected function productsData(): array
    {
        $priceCol = $this->firstExistingColumn('catalog_items', ['price', 'sale_price', 'precio', 'unit_price']);
        $imageCol = $this->firstExistingColumn('catalog_items', ['image', 'image_url', 'photo', 'thumbnail']);
        $hasReservedQty = Schema::hasColumn('inventories', 'reserved_qty');
        $fastFlowByProduct = $this->fastFlowProductData();

        $itemColumns = ['id', 'name', 'sku'];

        if (Schema::hasColumn('catalog_items', 'stock')) {
            $itemColumns[] = 'stock';
        }
        if (Schema::hasColumn('catalog_items', 'barcode')) {
            $itemColumns[] = 'barcode';
        }
        if (Schema::hasColumn('catalog_items', 'code')) {
            $itemColumns[] = 'code';
        }
        if (Schema::hasColumn('catalog_items', 'brand_name')) {
            $itemColumns[] = 'brand_name';
        }
        if (Schema::hasColumn('catalog_items', 'brand')) {
            $itemColumns[] = 'brand';
        }
        if (Schema::hasColumn('catalog_items', 'model_name')) {
            $itemColumns[] = 'model_name';
        }
        if (Schema::hasColumn('catalog_items', 'model')) {
            $itemColumns[] = 'model';
        }
        if (Schema::hasColumn('catalog_items', 'excerpt')) {
            $itemColumns[] = 'excerpt';
        }
        if (Schema::hasColumn('catalog_items', 'description')) {
            $itemColumns[] = 'description';
        }
        if (Schema::hasColumn('catalog_items', 'location_code')) {
            $itemColumns[] = 'location_code';
        }
        if ($priceCol && !in_array($priceCol, $itemColumns, true)) {
            $itemColumns[] = $priceCol;
        }
        if ($imageCol && !in_array($imageCol, $itemColumns, true)) {
            $itemColumns[] = $imageCol;
        }

        $inventoryRows = Inventory::query()
            ->with(['item:id,name,sku', 'location:id,code,name,type'])
            ->get();

        $inventoryGrouped = $inventoryRows->groupBy('catalog_item_id');

        return CatalogItem::query()
            ->orderBy('name')
            ->limit(1500)
            ->get($itemColumns)
            ->map(function ($item) use ($inventoryGrouped, $priceCol, $imageCol, $hasReservedQty, $fastFlowByProduct) {
                $rows = collect($inventoryGrouped->get($item->id, collect()));

                $availableStock = (int) $rows->sum(function ($row) use ($hasReservedQty) {
                    $qty = (int) ($row->qty ?? 0);
                    $reserved = $hasReservedQty ? (int) ($row->reserved_qty ?? 0) : 0;
                    return max(0, $qty - $reserved);
                });

                if ($rows->isEmpty() && Schema::hasColumn('catalog_items', 'stock')) {
                    $availableStock = (int) ($item->stock ?? 0);
                }

                $bestLocation = $rows->sortByDesc(function ($row) use ($hasReservedQty) {
                    $qty = (int) ($row->qty ?? 0);
                    $reserved = $hasReservedQty ? (int) ($row->reserved_qty ?? 0) : 0;
                    return max(0, $qty - $reserved);
                })->first();

                $locations = $rows
                    ->map(function ($row) use ($hasReservedQty) {
                        $qty = (int) ($row->qty ?? 0);
                        $reserved = $hasReservedQty ? (int) ($row->reserved_qty ?? 0) : 0;

                        return [
                            'location_id'   => $row->location_id,
                            'location_code' => (string) optional($row->location)->code,
                            'qty'           => max(0, $qty - $reserved),
                        ];
                    })
                    ->filter(fn ($row) => !empty($row['location_code']))
                    ->values()
                    ->all();

                $brand = '';
                if (Schema::hasColumn('catalog_items', 'brand_name')) {
                    $brand = (string) ($item->brand_name ?? '');
                } elseif (Schema::hasColumn('catalog_items', 'brand')) {
                    $brand = (string) ($item->brand ?? '');
                }

                $model = '';
                if (Schema::hasColumn('catalog_items', 'model_name')) {
                    $model = (string) ($item->model_name ?? '');
                } elseif (Schema::hasColumn('catalog_items', 'model')) {
                    $model = (string) ($item->model ?? '');
                }

                $fallbackLocation = '';
                if (Schema::hasColumn('catalog_items', 'location_code')) {
                    $fallbackLocation = (string) ($item->location_code ?? '');
                }

                $price = 0;
                if ($priceCol && isset($item->{$priceCol})) {
                    $price = (float) ($item->{$priceCol} ?? 0);
                }

                $image = '';
                if ($imageCol && isset($item->{$imageCol})) {
                    $image = (string) ($item->{$imageCol} ?? '');
                }

                $ff = $fastFlowByProduct[(int) $item->id] ?? [
                    'units_per_box'         => 0,
                    'boxes_count'           => 0,
                    'available_boxes_count' => 0,
                    'available_stock'       => 0,
                ];

                return [
                    'id'                    => $item->id,
                    'name'                  => (string) ($item->name ?? '—'),
                    'sku'                   => strtoupper((string) ($item->sku ?? '')),
                    'barcode'               => Schema::hasColumn('catalog_items', 'barcode')
                        ? strtoupper((string) ($item->barcode ?? ''))
                        : '',
                    'code'                  => Schema::hasColumn('catalog_items', 'code')
                        ? strtoupper((string) ($item->code ?? ''))
                        : '',
                    'brand_name'            => $brand,
                    'brand'                 => $brand,
                    'model_name'            => $model,
                    'model'                 => $model,
                    'excerpt'               => Schema::hasColumn('catalog_items', 'excerpt')
                        ? (string) ($item->excerpt ?? '')
                        : '',
                    'description'           => Schema::hasColumn('catalog_items', 'description')
                        ? (string) ($item->description ?? '')
                        : '',
                    'price'                 => $price,
                    'image'                 => $image,
                    'image_url'             => $image,
                    'photo'                 => $image,
                    'thumbnail'             => $image,
                    'available_stock'       => max(0, $availableStock),
                    'stock'                 => max(0, $availableStock),
                    'default_location_code' => (string) (optional(optional($bestLocation)->location)->code ?: $fallbackLocation),
                    'location_code'         => (string) (optional(optional($bestLocation)->location)->code ?: $fallbackLocation),
                    'locations'             => $locations,
                    'is_fastflow'           => false,
                    'fastflow_units_per_box'=> (int) ($ff['units_per_box'] ?? 0),
                    'units_per_box'         => (int) ($ff['units_per_box'] ?? 0),
                    'boxes_count'           => (int) ($ff['boxes_count'] ?? 0),
                    'total_boxes'           => (int) ($ff['boxes_count'] ?? 0),
                    'available_boxes_count' => (int) ($ff['available_boxes_count'] ?? 0),
                    'fastflow_available_stock' => (int) ($ff['available_stock'] ?? 0),
                ];
            })
            ->filter(fn ($item) => !empty($item['sku']) || !empty($item['name']))
            ->values()
            ->all();
    }

    protected function getDefaultWarehouseId(): ?int
    {
        if (!Schema::hasColumn('pick_waves', 'warehouse_id')) {
            return null;
        }

        $warehouse = Warehouse::query()
            ->where(function ($q) {
                $q->whereRaw('LOWER(name) = ?', ['principal'])
                  ->orWhereRaw('LOWER(code) = ?', ['principal']);
            })
            ->orderBy('id')
            ->first();

        if (!$warehouse) {
            $warehouse = Warehouse::query()->orderBy('id')->first();
        }

        return $warehouse ? (int) $warehouse->id : null;
    }

    protected function resolveWarehouseId(?int $requestedId = null): ?int
    {
        if (!Schema::hasColumn('pick_waves', 'warehouse_id')) {
            return null;
        }

        if ($requestedId) {
            return $requestedId;
        }

        $defaultId = $this->getDefaultWarehouseId();

        if (!$defaultId) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Debes crear al menos un almacén antes de generar tareas de picking.',
            ]);
        }

        return (int) $defaultId;
    }

    protected function getNextTaskNumber(): string
    {
        $max = 0;

        $tasks = PickWave::query()
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        foreach ($tasks as $task) {
            $bag = $this->pickWaveBag($task);
            $columns = $this->pickWaveColumns();

            $candidate = (string) (
                $bag['task_number']
                ?? ($columns['task_number'] ? ($task->{$columns['task_number']} ?? '') : '')
                ?? ($columns['code'] ? ($task->{$columns['code']} ?? '') : '')
            );

            if (preg_match('/(\d+)/', $candidate, $m)) {
                $max = max($max, (int) $m[1]);
            } else {
                $max = max($max, (int) $task->id);
            }
        }

        return 'PICK-' . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }

    protected function normalizeTask(PickWave $task): array
    {
        $cols = $this->pickWaveColumns();
        $bag  = $this->pickWaveBag($task);

        $status = $bag['status']
            ?? ($cols['status'] ? $this->readStatusValue($task->{$cols['status']} ?? null) : 'pending');

        $items = collect(is_array($bag['items'] ?? null) ? $bag['items'] : [])
            ->map(fn ($item) => $this->normalizeSingleItem(is_array($item) ? $item : []))
            ->values()
            ->all();

        $totalPhases = max(1, (int) (
            $bag['total_phases']
            ?? ($cols['total_phases'] ? ($task->{$cols['total_phases']} ?? 1) : 1)
        ));

        $deliveries = $this->normalizeDeliveries(
            is_array($bag['deliveries'] ?? null) ? $bag['deliveries'] : [],
            $totalPhases
        );

        return [
            'id'               => $task->id,
            'warehouse_id'     => (int) (
                $bag['warehouse_id']
                ?? ($cols['warehouse_id'] ? ($task->{$cols['warehouse_id']} ?? 0) : 0)
            ),
            'task_number'      => (string) (
                $bag['task_number']
                ?? ($cols['task_number'] ? ($task->{$cols['task_number']} ?? null) : null)
                ?? ($cols['code'] ? ($task->{$cols['code']} ?? null) : null)
                ?? ('PICK-' . str_pad((string) $task->id, 3, '0', STR_PAD_LEFT))
            ),
            'order_number'     => (string) (
                $bag['order_number']
                ?? ($cols['order_number'] ? ($task->{$cols['order_number']} ?? '') : '')
                ?? ($cols['reference'] ? ($task->{$cols['reference']} ?? '') : '')
            ),
            'assigned_user_id' => $bag['assigned_user_id']
                ?? ($cols['assigned_user_id'] ? ($task->{$cols['assigned_user_id']} ?? null) : null),
            'assigned_to'      => (string) ($bag['assigned_to'] ?? ''),
            'priority'         => (string) (
                $bag['priority']
                ?? ($cols['priority'] ? ($task->{$cols['priority']} ?? 'normal') : 'normal')
            ),
            'notes'            => (string) (
                $bag['notes']
                ?? ($cols['notes'] ? ($task->{$cols['notes']} ?? '') : '')
            ),
            'status'           => (string) $status,
            'started_at'       => $this->normalizeDateString(
                $bag['started_at']
                ?? ($cols['started_at'] ? ($task->{$cols['started_at']} ?? null) : null)
            ),
            'completed_at'     => $this->normalizeDateString(
                $bag['completed_at']
                ?? ($cols['completed_at'] ? ($task->{$cols['completed_at']} ?? null) : null)
                ?? ($cols['finished_at'] ? ($task->{$cols['finished_at']} ?? null) : null)
            ),
            'total_phases'     => $totalPhases,
            'deliveries'       => $deliveries,
            'items'            => $items,
            'created_at'       => $this->normalizeDateString($task->created_at),
        ];
    }

    protected function normalizeSingleItem(array $item): array
    {
        $required = max(1, (int) ($item['quantity_required'] ?? $item['qty'] ?? 1));
        $picked   = max(0, (int) ($item['quantity_picked'] ?? 0));
        $staged   = max(0, (int) ($item['quantity_staged'] ?? 0));
        $brand    = (string) ($item['brand'] ?? $item['brand_name'] ?? '');
        $model    = (string) ($item['model'] ?? $item['model_name'] ?? '');
        $phase    = max(1, (int) ($item['delivery_phase'] ?? $item['phase'] ?? 1));

        $isFastFlow = filter_var(($item['is_fastflow'] ?? false), FILTER_VALIDATE_BOOLEAN);
        $locationCode = (string) ($item['location_code'] ?? '');
        $batchCode = strtoupper(trim((string) ($item['batch_code'] ?? '')));

        if ($isFastFlow || strtoupper($locationCode) === 'FAST FLOW' || ($batchCode !== '' && str_starts_with($batchCode, 'FF-'))) {
            $isFastFlow = true;
            $locationCode = 'FAST FLOW';
        }

        $normalized = [
            'line_id'               => (string) ($item['line_id'] ?? (string) Str::uuid()),
            'product_id'            => $item['product_id'] ?? null,
            'product_sku'           => strtoupper((string) ($item['product_sku'] ?? '')),
            'product_name'          => (string) ($item['product_name'] ?? 'Producto'),
            'product_barcode'       => strtoupper((string) ($item['product_barcode'] ?? '')),
            'product_code'          => strtoupper((string) ($item['product_code'] ?? '')),
            'location_code'         => $locationCode,
            'batch_code'            => $batchCode,
            'quantity_required'     => $required,
            'quantity_picked'       => min($picked, $required),
            'quantity_staged'       => min($staged, $required),
            'picked'                => filter_var(($item['picked'] ?? ($picked >= $required)), FILTER_VALIDATE_BOOLEAN),
            'staged'                => filter_var(($item['staged'] ?? ($staged >= $required)), FILTER_VALIDATE_BOOLEAN),
            'delivery_phase'        => $phase,
            'phase'                 => $phase,
            'description'           => (string) ($item['description'] ?? ''),
            'brand_name'            => $brand,
            'brand'                 => $brand,
            'model_name'            => $model,
            'model'                 => $model,
            'requested_quantity'    => max(1, (int) ($item['requested_quantity'] ?? $required)),
            'available_stock'       => max(0, (int) ($item['available_stock'] ?? 0)),
            'is_fastflow'           => $isFastFlow,
            'staging_location_code' => (string) ($item['staging_location_code'] ?? ''),
            'collected_at'          => $this->normalizeDateString($item['collected_at'] ?? null),
            'staged_at'             => $this->normalizeDateString($item['staged_at'] ?? null),
            'units_per_box'         => max(0, (int) ($item['units_per_box'] ?? 0)),
            'total_boxes'           => max(0, (int) ($item['total_boxes'] ?? $item['boxes_count'] ?? 0)),
            'boxes_count'           => max(0, (int) ($item['boxes_count'] ?? $item['total_boxes'] ?? 0)),
            'available_boxes_count' => max(0, (int) ($item['available_boxes_count'] ?? 0)),
            'total_pieces'          => max(0, (int) ($item['total_pieces'] ?? 0)),
            'box_labels'            => $this->normalizeUpperStringList($item['box_labels'] ?? []),
            'available_boxes'       => $this->normalizeUpperStringList($item['available_boxes'] ?? []),
            'scanned_boxes'         => $this->normalizeUpperStringList($item['scanned_boxes'] ?? []),
            'staged_boxes'          => $this->normalizeUpperStringList($item['staged_boxes'] ?? []),
            'box_allocations'       => $this->normalizeBoxAllocations($item['box_allocations'] ?? []),
            'stage_box_allocations' => $this->normalizeBoxAllocations($item['stage_box_allocations'] ?? []),
        ];

        if ($normalized['is_fastflow']) {
            $normalized = $this->hydrateFastFlowItem($normalized);
        }

        return $normalized;
    }

    protected function persistTask(PickWave $task, array $payload): void
    {
        $cols = $this->pickWaveColumns();
        $bag  = $this->pickWaveBag($task);

        $bag['warehouse_id']     = $payload['warehouse_id'] ?? null;
        $bag['task_number']      = $payload['task_number'];
        $bag['order_number']     = $payload['order_number'];
        $bag['assigned_user_id'] = $payload['assigned_user_id'] ?? null;
        $bag['assigned_to']      = $payload['assigned_to'];
        $bag['priority']         = $payload['priority'];
        $bag['notes']            = $payload['notes'];
        $bag['status']           = $payload['status'];
        $bag['started_at']       = $payload['started_at'];
        $bag['completed_at']     = $payload['completed_at'];
        $bag['total_phases']     = (int) $payload['total_phases'];
        $bag['deliveries']       = $payload['deliveries'];
        $bag['items']            = $payload['items'];

        if (!array_key_exists('stock_reserved', $bag)) {
            $bag['stock_reserved'] = false;
        }
        if (!array_key_exists('stock_consumed', $bag)) {
            $bag['stock_consumed'] = false;
        }
        if (!array_key_exists('reservation_allocations', $bag)) {
            $bag['reservation_allocations'] = [];
        }

        if ($cols['json']) {
            $task->{$cols['json']} = $this->assignColumnValue('pick_waves', $cols['json'], $bag);
        }

        if ($cols['items']) {
            $task->{$cols['items']} = $this->assignColumnValue('pick_waves', $cols['items'], $payload['items']);
        }

        if ($cols['deliveries']) {
            $task->{$cols['deliveries']} = $this->assignColumnValue('pick_waves', $cols['deliveries'], $payload['deliveries']);
        }

        if ($cols['code']) {
            $task->{$cols['code']} = $payload['task_number'];
        }

        if ($cols['task_number']) {
            $task->{$cols['task_number']} = $payload['task_number'];
        }

        if ($cols['reference']) {
            $task->{$cols['reference']} = $payload['order_number'];
        }

        if ($cols['order_number']) {
            $task->{$cols['order_number']} = $payload['order_number'];
        }

        if ($cols['assigned_user_id']) {
            $task->{$cols['assigned_user_id']} = $payload['assigned_user_id'];
        }

        if ($cols['assigned_to']) {
            if ($this->columnLooksString('pick_waves', $cols['assigned_to'])) {
                $task->{$cols['assigned_to']} = $payload['assigned_to'];
            } elseif ($payload['assigned_user_id'] !== null) {
                $task->{$cols['assigned_to']} = (int) $payload['assigned_user_id'];
            } else {
                $task->{$cols['assigned_to']} = null;
            }
        }

        if ($cols['priority']) {
            $task->{$cols['priority']} = $payload['priority'];
        }

        if ($cols['notes']) {
            $task->{$cols['notes']} = $payload['notes'];
        }

        if ($cols['status']) {
            if ($this->columnLooksString('pick_waves', $cols['status'])) {
                $task->{$cols['status']} = $payload['status'];
            } else {
                $task->{$cols['status']} = $this->statusStringToInt($payload['status']);
            }
        }

        if ($cols['warehouse_id']) {
            $task->{$cols['warehouse_id']} = $payload['warehouse_id'];
        }

        if ($cols['total_phases']) {
            $task->{$cols['total_phases']} = (int) $payload['total_phases'];
        }

        if ($cols['started_at']) {
            $task->{$cols['started_at']} = $payload['started_at'] ? Carbon::parse($payload['started_at']) : null;
        }

        if ($cols['completed_at']) {
            $task->{$cols['completed_at']} = $payload['completed_at'] ? Carbon::parse($payload['completed_at']) : null;
        }

        if ($cols['finished_at'] && !$cols['completed_at']) {
            $task->{$cols['finished_at']} = $payload['completed_at'] ? Carbon::parse($payload['completed_at']) : null;
        }

        $task->save();
    }

    protected function reserveStockForTask(PickWave $task): void
    {
        $bag = $this->pickWaveBag($task);

        $this->logPicking('reserveStockForTask.start', [
            'pick_wave_id'   => $task->id,
            'stock_reserved' => (bool) data_get($bag, 'stock_reserved', false),
            'stock_consumed' => (bool) data_get($bag, 'stock_consumed', false),
        ]);

        if ((bool) data_get($bag, 'stock_reserved', false) === true) {
            $this->logPicking('reserveStockForTask.skip.already_reserved', [
                'pick_wave_id' => $task->id,
            ]);
            return;
        }

        if ((bool) data_get($bag, 'stock_consumed', false) === true) {
            $this->logPicking('reserveStockForTask.skip.already_consumed', [
                'pick_wave_id' => $task->id,
            ]);
            return;
        }

        $taskData = $this->normalizeTask($task);
        $allocations = [];

        DB::transaction(function () use ($taskData, &$allocations, $task) {
            foreach ($taskData['items'] as $item) {
                $lineId = (string) data_get($item, 'line_id', (string) Str::uuid());

                $this->logPicking('reserveStockForTask.item', [
                    'pick_wave_id' => $task->id,
                    'item'         => $this->itemLogContext($item),
                ]);

                if ($this->isFastFlowItem($item)) {
                    $allocations[$lineId] = $this->reserveFastFlowItem($item);
                } else {
                    $allocations[$lineId] = $this->reserveInventoryItem($item);
                }
            }
        });

        $this->logPicking('reserveStockForTask.done', [
            'pick_wave_id' => $task->id,
            'allocations'  => $allocations,
        ]);

        $this->setTaskReservationState($task->fresh(), true, false, $allocations);
    }

    protected function releaseReservedStockForTask(PickWave $task): void
    {
        $bag = $this->pickWaveBag($task);
        $allocations = (array) data_get($bag, 'reservation_allocations', []);

        $this->logPicking('releaseReservedStockForTask.start', [
            'pick_wave_id' => $task->id,
            'allocations'  => $allocations,
        ]);

        if ((bool) data_get($bag, 'stock_reserved', false) === false || empty($allocations)) {
            $this->setTaskReservationState($task, false, (bool) data_get($bag, 'stock_consumed', false), []);
            return;
        }

        DB::transaction(function () use ($allocations) {
            foreach ($allocations as $allocation) {
                foreach ((array) data_get($allocation, 'entries', []) as $entry) {
                    $qty = (int) data_get($entry, 'qty', 0);
                    if ($qty <= 0) {
                        continue;
                    }

                    if ((string) data_get($entry, 'type') === 'fastflow') {
                        $boxId = data_get($entry, 'box_id');
                        $inventoryId = data_get($entry, 'inventory_id');

                        if ($boxId) {
                            $box = WmsQuickBox::query()->lockForUpdate()->find($boxId);
                            if ($box && Schema::hasColumn('wms_quick_boxes', 'reserved_units')) {
                                $box->reserved_units = max(0, (int) ($box->reserved_units ?? 0) - $qty);
                                $box->save();
                            }
                        }

                        if ($inventoryId && Schema::hasColumn('inventories', 'reserved_qty')) {
                            $inventory = Inventory::query()->lockForUpdate()->find($inventoryId);
                            if ($inventory) {
                                $inventory->reserved_qty = max(0, (int) ($inventory->reserved_qty ?? 0) - $qty);
                                $inventory->save();
                            }
                        }
                    } else {
                        $inventoryId = data_get($entry, 'inventory_id');

                        if ($inventoryId && Schema::hasColumn('inventories', 'reserved_qty')) {
                            $inventory = Inventory::query()->lockForUpdate()->find($inventoryId);
                            if ($inventory) {
                                $inventory->reserved_qty = max(0, (int) ($inventory->reserved_qty ?? 0) - $qty);
                                $inventory->save();
                            }
                        }
                    }
                }
            }
        });

        $this->logPicking('releaseReservedStockForTask.done', [
            'pick_wave_id' => $task->id,
        ]);

        $this->setTaskReservationState($task->fresh(), false, false, []);
    }

    protected function consumeReservedStockForTask(PickWave $task): void
    {
        $bag = $this->pickWaveBag($task);
        $allocations = (array) data_get($bag, 'reservation_allocations', []);

        $this->logPicking('consumeReservedStockForTask.start', [
            'pick_wave_id' => $task->id,
            'allocations'  => $allocations,
        ]);

        if ((bool) data_get($bag, 'stock_reserved', false) === false || empty($allocations)) {
            return;
        }

        DB::transaction(function () use ($allocations) {
            $catalogDeltas = [];

            foreach ($allocations as $allocation) {
                $productId = data_get($allocation, 'product_id');
                $lineConsumed = 0;

                foreach ((array) data_get($allocation, 'entries', []) as $entry) {
                    $qty = (int) data_get($entry, 'qty', 0);
                    if ($qty <= 0) {
                        continue;
                    }

                    if ((string) data_get($entry, 'type') === 'fastflow') {
                        $boxId = data_get($entry, 'box_id');
                        $inventoryId = data_get($entry, 'inventory_id');

                        $box = WmsQuickBox::query()->lockForUpdate()->find($boxId);
                        if (!$box) {
                            throw ValidationException::withMessages([
                                'items' => 'No se encontró una caja Fast Flow reservada para completar el picking.',
                            ]);
                        }

                        $currentUnits = (int) ($box->current_units ?? 0);
                        $reservedUnits = Schema::hasColumn('wms_quick_boxes', 'reserved_units')
                            ? (int) ($box->reserved_units ?? 0)
                            : 0;

                        if ($reservedUnits < $qty) {
                            throw ValidationException::withMessages([
                                'items' => 'La reserva Fast Flow ya no coincide con la cantidad requerida.',
                            ]);
                        }

                        if ($currentUnits < $qty) {
                            throw ValidationException::withMessages([
                                'items' => 'No hay suficientes unidades físicas en Fast Flow para completar el picking.',
                            ]);
                        }

                        $box->current_units = max(0, $currentUnits - $qty);
                        if (Schema::hasColumn('wms_quick_boxes', 'reserved_units')) {
                            $box->reserved_units = max(0, $reservedUnits - $qty);
                        }
                        $box->status = $this->resolveQuickBoxStatus($box);
                        $box->save();

                        if ($inventoryId) {
                            $inventory = Inventory::query()->lockForUpdate()->find($inventoryId);
                            if (!$inventory) {
                                throw ValidationException::withMessages([
                                    'items' => 'No se encontró el inventario Fast Flow asociado para completar el picking.',
                                ]);
                            }

                            $inventoryQty = (int) ($inventory->qty ?? 0);
                            $inventoryReserved = Schema::hasColumn('inventories', 'reserved_qty')
                                ? (int) ($inventory->reserved_qty ?? 0)
                                : 0;

                            if ($inventoryQty < $qty) {
                                throw ValidationException::withMessages([
                                    'items' => 'El inventario Fast Flow quedaría en negativo.',
                                ]);
                            }

                            if (Schema::hasColumn('inventories', 'reserved_qty') && $inventoryReserved < $qty) {
                                throw ValidationException::withMessages([
                                    'items' => 'La reserva del inventario Fast Flow ya no es válida.',
                                ]);
                            }

                            $inventory->qty = max(0, $inventoryQty - $qty);
                            if (Schema::hasColumn('inventories', 'reserved_qty')) {
                                $inventory->reserved_qty = max(0, $inventoryReserved - $qty);
                            }
                            $inventory->save();
                        }

                        $lineConsumed += $qty;
                    } else {
                        $inventoryId = data_get($entry, 'inventory_id');

                        $inventory = Inventory::query()->lockForUpdate()->find($inventoryId);
                        if (!$inventory) {
                            throw ValidationException::withMessages([
                                'items' => 'No se encontró el inventario reservado para completar el picking.',
                            ]);
                        }

                        $inventoryQty = (int) ($inventory->qty ?? 0);
                        $inventoryReserved = Schema::hasColumn('inventories', 'reserved_qty')
                            ? (int) ($inventory->reserved_qty ?? 0)
                            : 0;

                        if ($inventoryQty < $qty) {
                            throw ValidationException::withMessages([
                                'items' => 'El inventario quedaría en negativo al completar el picking.',
                            ]);
                        }

                        if (Schema::hasColumn('inventories', 'reserved_qty') && $inventoryReserved < $qty) {
                            throw ValidationException::withMessages([
                                'items' => 'La reserva del inventario ya no coincide con la cantidad requerida.',
                            ]);
                        }

                        $inventory->qty = max(0, $inventoryQty - $qty);
                        if (Schema::hasColumn('inventories', 'reserved_qty')) {
                            $inventory->reserved_qty = max(0, $inventoryReserved - $qty);
                        }
                        $inventory->save();

                        $lineConsumed += $qty;
                    }
                }

                if ($productId && $lineConsumed > 0) {
                    $catalogDeltas[$productId] = ($catalogDeltas[$productId] ?? 0) + $lineConsumed;
                }
            }

            foreach ($catalogDeltas as $productId => $qty) {
                $this->applyCatalogStockDelta((int) $productId, -1 * (int) $qty);
            }
        });

        $this->logPicking('consumeReservedStockForTask.done', [
            'pick_wave_id' => $task->id,
        ]);

        $this->setTaskReservationState($task->fresh(), false, true, []);
    }

    protected function reserveInventoryItem(array $item): array
    {
        $productId = data_get($item, 'product_id');
        $productName = (string) data_get($item, 'product_name', 'Producto');
        $locationCode = trim((string) data_get($item, 'location_code', ''));
        $required = max(1, (int) data_get($item, 'quantity_required', 1));

        $this->logPicking('reserveInventoryItem.start', [
            'item' => $this->itemLogContext($item),
        ]);

        if (!$productId) {
            $this->logPicking('reserveInventoryItem.error.no_product_id', [
                'item' => $this->itemLogContext($item),
            ], 'warning');

            throw ValidationException::withMessages([
                'items' => "El producto {$productName} no tiene product_id válido para reservar inventario.",
            ]);
        }

        $rows = Inventory::query()
            ->where('catalog_item_id', $productId)
            ->when($locationCode !== '', function ($q) use ($locationCode) {
                $q->whereHas('location', function ($loc) use ($locationCode) {
                    $loc->where('code', $locationCode);
                });
            })
            ->orderByDesc('qty')
            ->lockForUpdate()
            ->get();

        $this->logPicking('reserveInventoryItem.rows_found', [
            'product_id' => $productId,
            'rows_count' => $rows->count(),
            'rows'       => $rows->map(function ($row) {
                return [
                    'inventory_id' => $row->id,
                    'location_id'  => $row->location_id,
                    'qty'          => (int) ($row->qty ?? 0),
                    'reserved_qty' => Schema::hasColumn('inventories', 'reserved_qty')
                        ? (int) ($row->reserved_qty ?? 0)
                        : null,
                ];
            })->values()->all(),
        ]);

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => "No se encontró inventario para {$productName}.",
            ]);
        }

        $entries = [];
        $remaining = $required;

        foreach ($rows as $row) {
            $qty = (int) ($row->qty ?? 0);
            $reserved = Schema::hasColumn('inventories', 'reserved_qty')
                ? (int) ($row->reserved_qty ?? 0)
                : 0;

            $available = max(0, $qty - $reserved);
            if ($available <= 0) {
                continue;
            }

            $take = min($remaining, $available);
            if ($take <= 0) {
                continue;
            }

            if (Schema::hasColumn('inventories', 'reserved_qty')) {
                $row->reserved_qty = $reserved + $take;
                $row->save();
            }

            $entries[] = [
                'type'         => 'inventory',
                'inventory_id' => $row->id,
                'qty'          => $take,
            ];

            $remaining -= $take;

            $this->logPicking('reserveInventoryItem.row_reserved', [
                'inventory_id' => $row->id,
                'taken'        => $take,
                'remaining'    => $remaining,
            ]);

            if ($remaining <= 0) {
                break;
            }
        }

        if ($remaining > 0) {
            $this->logPicking('reserveInventoryItem.error.insufficient_stock', [
                'item'      => $this->itemLogContext($item),
                'remaining' => $remaining,
            ], 'warning');

            throw ValidationException::withMessages([
                'items' => "Stock insuficiente para {$productName}.",
            ]);
        }

        $result = [
            'kind'       => 'inventory',
            'product_id' => $productId,
            'entries'    => $entries,
        ];

        $this->logPicking('reserveInventoryItem.done', $result);

        return $result;
    }

    protected function reserveFastFlowItem(array $item): array
    {
        $this->logPicking('reserveFastFlowItem.start', [
            'item' => $this->itemLogContext($item),
        ]);

        if (!Schema::hasTable('wms_quick_boxes') || !Schema::hasColumn('wms_quick_boxes', 'reserved_units')) {
            $this->logPicking('reserveFastFlowItem.error.missing_reserved_units', [], 'warning');

            throw ValidationException::withMessages([
                'items' => 'Fast Flow requiere la columna reserved_units en wms_quick_boxes.',
            ]);
        }

        $productId = data_get($item, 'product_id');
        $productSku = trim((string) data_get($item, 'product_sku', ''));
        $productName = (string) data_get($item, 'product_name', 'Producto');
        $batchCode = trim((string) data_get($item, 'batch_code', ''));
        $required = max(1, (int) data_get($item, 'quantity_required', 1));

        $boxesQuery = WmsQuickBox::query()
            ->whereIn('status', ['available', 'partial'])
            ->orderBy('received_at')
            ->orderBy('box_number')
            ->lockForUpdate();

        if ($batchCode !== '') {
            $boxesQuery->where('batch_code', $batchCode);
        } elseif ($productId) {
            $boxesQuery->where('catalog_item_id', $productId);
        } elseif ($productSku !== '') {
            $boxesQuery->whereHas('item', function ($q) use ($productSku) {
                $q->where('sku', $productSku);
            });
        } else {
            $this->logPicking('reserveFastFlowItem.error.no_identifiers', [
                'item' => $this->itemLogContext($item),
            ], 'warning');

            throw ValidationException::withMessages([
                'items' => "No se puede reservar Fast Flow para {$productName} porque falta product_id, sku o batch_code.",
            ]);
        }

        $boxes = $boxesQuery->get();

        $this->logPicking('reserveFastFlowItem.boxes_found', [
            'product_id'  => $productId,
            'batch_code'  => $batchCode,
            'sku'         => $productSku,
            'boxes_count' => $boxes->count(),
            'boxes'       => $boxes->map(function ($box) {
                return [
                    'box_id'          => $box->id,
                    'batch_code'      => $box->batch_code,
                    'status'          => $box->status,
                    'current_units'   => (int) ($box->current_units ?? 0),
                    'reserved_units'  => (int) ($box->reserved_units ?? 0),
                    'location_id'     => $box->location_id,
                    'catalog_item_id' => $box->catalog_item_id,
                ];
            })->values()->all(),
        ]);

        if ($boxes->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => "No se encontraron cajas Fast Flow disponibles para {$productName}.",
            ]);
        }

        $entries = [];
        $remaining = $required;

        foreach ($boxes as $box) {
            $currentUnits = (int) ($box->current_units ?? 0);
            $reservedUnits = (int) ($box->reserved_units ?? 0);
            $availableUnits = max(0, $currentUnits - $reservedUnits);

            if ($availableUnits <= 0) {
                continue;
            }

            $take = min($remaining, $availableUnits);
            if ($take <= 0) {
                continue;
            }

            $inventory = Inventory::query()
                ->where('location_id', $box->location_id)
                ->where('catalog_item_id', $box->catalog_item_id)
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                $this->logPicking('reserveFastFlowItem.error.no_inventory_row', [
                    'box_id'          => $box->id,
                    'location_id'     => $box->location_id,
                    'catalog_item_id' => $box->catalog_item_id,
                ], 'warning');

                throw ValidationException::withMessages([
                    'items' => "No se encontró inventario Fast Flow para {$productName}.",
                ]);
            }

            $invQty = (int) ($inventory->qty ?? 0);
            $invReserved = Schema::hasColumn('inventories', 'reserved_qty')
                ? (int) ($inventory->reserved_qty ?? 0)
                : 0;
            $invAvailable = max(0, $invQty - $invReserved);

            if ($invAvailable < $take) {
                $this->logPicking('reserveFastFlowItem.error.inventory_not_enough', [
                    'inventory_id' => $inventory->id,
                    'inv_qty'      => $invQty,
                    'inv_reserved' => $invReserved,
                    'inv_available'=> $invAvailable,
                    'take'         => $take,
                ], 'warning');

                throw ValidationException::withMessages([
                    'items' => "El inventario Fast Flow no tiene suficiente disponibilidad para {$productName}.",
                ]);
            }

            $box->reserved_units = $reservedUnits + $take;
            $box->save();

            if (Schema::hasColumn('inventories', 'reserved_qty')) {
                $inventory->reserved_qty = $invReserved + $take;
                $inventory->save();
            }

            $entries[] = [
                'type'         => 'fastflow',
                'box_id'       => $box->id,
                'inventory_id' => $inventory->id,
                'qty'          => $take,
            ];

            $remaining -= $take;

            $this->logPicking('reserveFastFlowItem.box_reserved', [
                'box_id'       => $box->id,
                'inventory_id' => $inventory->id,
                'taken'        => $take,
                'remaining'    => $remaining,
            ]);

            if ($remaining <= 0) {
                break;
            }
        }

        if ($remaining > 0) {
            $this->logPicking('reserveFastFlowItem.error.insufficient_fastflow', [
                'item'      => $this->itemLogContext($item),
                'remaining' => $remaining,
            ], 'warning');

            throw ValidationException::withMessages([
                'items' => "Fast Flow no tiene suficiente stock libre para {$productName}.",
            ]);
        }

        $result = [
            'kind'       => 'fastflow',
            'product_id' => $productId,
            'entries'    => $entries,
        ];

        $this->logPicking('reserveFastFlowItem.done', $result);

        return $result;
    }

    protected function applyCatalogStockDelta(int $catalogItemId, int $deltaQty): void
    {
        if (!Schema::hasColumn('catalog_items', 'stock')) {
            return;
        }

        $item = CatalogItem::query()
            ->whereKey($catalogItemId)
            ->lockForUpdate()
            ->first();

        if (!$item) {
            return;
        }

        $newStock = (int) ($item->stock ?? 0) + $deltaQty;

        if ($newStock < 0) {
            throw ValidationException::withMessages([
                'items' => 'El stock general del catálogo quedaría en negativo.',
            ]);
        }

        $item->stock = $newStock;
        $item->save();
    }

    protected function resolveQuickBoxStatus(WmsQuickBox $box): string
    {
        $currentUnits = (int) ($box->current_units ?? 0);
        $unitsPerBox = (int) ($box->units_per_box ?? 0);

        if ($currentUnits <= 0) {
            return 'shipped';
        }

        if ($unitsPerBox > 0 && $currentUnits < $unitsPerBox) {
            return 'partial';
        }

        return 'available';
    }

    protected function isFastFlowItem(array $item): bool
    {
        $flag = filter_var(($item['is_fastflow'] ?? false), FILTER_VALIDATE_BOOLEAN);
        $locationCode = strtoupper(trim((string) ($item['location_code'] ?? '')));
        $batchCode = strtoupper(trim((string) ($item['batch_code'] ?? '')));

        return $flag || $locationCode === 'FAST FLOW' || ($batchCode !== '' && str_starts_with($batchCode, 'FF-'));
    }

    protected function setTaskReservationState(PickWave $task, bool $reserved, bool $consumed, array $allocations): void
    {
        $cols = $this->pickWaveColumns();
        $bag = $this->pickWaveBag($task);

        $bag['stock_reserved'] = $reserved;
        $bag['stock_consumed'] = $consumed;
        $bag['reservation_allocations'] = $allocations;

        if ($cols['json']) {
            $task->{$cols['json']} = $this->assignColumnValue('pick_waves', $cols['json'], $bag);
            $task->save();
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
            'started_at'       => $this->firstExistingColumn('pick_waves', ['started_at']),
            'completed_at'     => $this->firstExistingColumn('pick_waves', ['completed_at']),
            'finished_at'      => $this->firstExistingColumn('pick_waves', ['finished_at']),
            'warehouse_id'     => $this->firstExistingColumn('pick_waves', ['warehouse_id']),
            'total_phases'     => $this->firstExistingColumn('pick_waves', ['total_phases']),
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

    protected function columnLooksString(string $table, ?string $column): bool
    {
        if (!$column || !Schema::hasColumn($table, $column)) {
            return false;
        }

        $type = Schema::getColumnType($table, $column);

        return in_array($type, ['string', 'text', 'mediumText', 'longText'], true);
    }

    protected function columnLooksJsonLike(string $table, ?string $column): bool
    {
        if (!$column || !Schema::hasColumn($table, $column)) {
            return false;
        }

        $type = Schema::getColumnType($table, $column);

        return in_array($type, ['json', 'jsonb'], true);
    }

    protected function assignColumnValue(string $table, ?string $column, $value)
    {
        if (!$column) {
            return null;
        }

        if ($this->columnLooksJsonLike($table, $column)) {
            return $value;
        }

        if (is_array($value) || is_object($value)) {
            return $this->encodeJsonColumn($value);
        }

        return $value;
    }

    protected function encodeJsonColumn($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
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

    public function scannerV2(Request $request)
    {
        $taskId = (int) $request->get('task_id');

        $tasks = PickWave::query()
            ->get()
            ->map(fn ($task) => $this->normalizeTask($task))
            ->sortBy(function ($task) {
                return match ((string) ($task['status'] ?? 'pending')) {
                    'in_progress' => 1,
                    'pending'     => 2,
                    'completed'   => 3,
                    'cancelled'   => 4,
                    default       => 5,
                };
            })
            ->sortByDesc('id')
            ->values()
            ->all();

        $selectedTask = collect($tasks)->firstWhere('id', $taskId);

        $products = collect($this->productsData())
            ->map(function ($product) {
                return [
                    'id'                     => $product['id'] ?? null,
                    'name'                   => (string) ($product['name'] ?? 'Producto'),
                    'sku'                    => (string) ($product['sku'] ?? ''),
                    'barcode'                => (string) ($product['barcode'] ?? ''),
                    'code'                   => (string) ($product['code'] ?? ''),
                    'brand'                  => (string) ($product['brand'] ?? ''),
                    'model'                  => (string) ($product['model'] ?? ''),
                    'description'            => (string) ($product['description'] ?? ''),
                    'location_code'          => (string) ($product['location_code'] ?? ''),
                    'available_stock'        => (int) ($product['available_stock'] ?? 0),
                    'units_per_box'          => (int) ($product['units_per_box'] ?? 0),
                    'fastflow_units_per_box' => (int) ($product['fastflow_units_per_box'] ?? 0),
                    'boxes_count'            => (int) ($product['boxes_count'] ?? 0),
                    'total_boxes'            => (int) ($product['total_boxes'] ?? 0),
                    'available_boxes_count'  => (int) ($product['available_boxes_count'] ?? 0),
                ];
            })
            ->values()
            ->all();

        return view('admin.wms.picking-scanner-v2', [
            'tasks'          => $tasks,
            'products'       => $products,
            'selectedTask'   => $selectedTask,
            'selectedTaskId' => $taskId ?: null,
            'operatorName'   => auth()->user()?->name ?? 'Operador',
        ]);
    }

    protected function readStatusValue($value): string
    {
        if ($value === null || $value === '') {
            return 'pending';
        }

        if (is_numeric($value)) {
            return $this->statusIntToString((int) $value);
        }

        $value = strtolower(trim((string) $value));

        return match ($value) {
            'pending', 'in_progress', 'completed', 'cancelled' => $value,
            '0' => 'pending',
            '1' => 'in_progress',
            '2' => 'completed',
            '3', '9' => 'cancelled',
            default => 'pending',
        };
    }

    protected function statusStringToInt(string $status): int
    {
        return match ($status) {
            'pending'     => 0,
            'in_progress' => 1,
            'completed'   => 2,
            'cancelled'   => 3,
            default       => 0,
        };
    }

    protected function statusIntToString(int $status): string
    {
        return match ($status) {
            0       => 'pending',
            1       => 'in_progress',
            2       => 'completed',
            3, 9    => 'cancelled',
            default => 'pending',
        };
    }

    protected function normalizeDateString($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function normalizeUpperStringList($values): array
    {
        return collect(is_array($values) ? $values : [])
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
                'label'  => $label,
                'pieces' => (int) $pieces,
            ];
        }

        return array_values($final);
    }

    protected function fastFlowProductData(): array
    {
        if ($this->fastFlowProductCache !== null) {
            return $this->fastFlowProductCache;
        }

        if (!Schema::hasTable('wms_quick_boxes')) {
            $this->fastFlowProductCache = [];
            return $this->fastFlowProductCache;
        }

        $hasReservedUnits = Schema::hasColumn('wms_quick_boxes', 'reserved_units');

        $grouped = WmsQuickBox::query()
            ->whereNotNull('catalog_item_id')
            ->orderByDesc('id')
            ->get([
                'catalog_item_id',
                'units_per_box',
                'current_units',
                'reserved_units',
                'status',
            ])
            ->groupBy('catalog_item_id')
            ->map(function ($rows) use ($hasReservedUnits) {
                $first = $rows->first();

                $availableBoxesCount = 0;
                $availableStock = 0;

                foreach ($rows as $row) {
                    $currentUnits = (int) ($row->current_units ?? 0);
                    $reservedUnits = $hasReservedUnits ? (int) ($row->reserved_units ?? 0) : 0;
                    $freeUnits = max(0, $currentUnits - $reservedUnits);

                    if (in_array((string) $row->status, ['available', 'partial'], true) && $freeUnits > 0) {
                        $availableBoxesCount++;
                        $availableStock += $freeUnits;
                    }
                }

                return [
                    'units_per_box'         => (int) ($first->units_per_box ?? 0),
                    'boxes_count'           => (int) $rows->count(),
                    'available_boxes_count' => (int) $availableBoxesCount,
                    'available_stock'       => (int) $availableStock,
                ];
            })
            ->all();

        $this->fastFlowProductCache = $grouped;

        return $this->fastFlowProductCache;
    }

    protected function getFastFlowBatchSnapshot(string $batchCode): ?array
    {
        $batchCode = strtoupper(trim($batchCode));
        if ($batchCode === '' || !Schema::hasTable('wms_quick_boxes')) {
            return null;
        }

        if (array_key_exists($batchCode, $this->fastFlowBatchCache)) {
            return $this->fastFlowBatchCache[$batchCode];
        }

        $hasReservedUnits = Schema::hasColumn('wms_quick_boxes', 'reserved_units');

        $boxes = WmsQuickBox::query()
            ->with(['item:id,name,sku'])
            ->where('batch_code', $batchCode)
            ->orderBy('box_number')
            ->get();

        if ($boxes->isEmpty()) {
            $this->fastFlowBatchCache[$batchCode] = null;
            return null;
        }

        $first = $boxes->first();

        $availableBoxesCount = 0;
        $availableStock = 0;
        $availableLabels = [];

        foreach ($boxes as $box) {
            $currentUnits = (int) ($box->current_units ?? 0);
            $reservedUnits = $hasReservedUnits ? (int) ($box->reserved_units ?? 0) : 0;
            $freeUnits = max(0, $currentUnits - $reservedUnits);

            if (in_array((string) $box->status, ['available', 'partial'], true) && $freeUnits > 0) {
                $availableBoxesCount++;
                $availableStock += $freeUnits;
                $availableLabels[] = strtoupper((string) ($box->label_code ?? ''));
            }
        }

        $snapshot = [
            'batch_code'            => $batchCode,
            'catalog_item_id'       => $first->catalog_item_id ? (int) $first->catalog_item_id : null,
            'product_name'          => (string) (optional($first->item)->name ?? ''),
            'product_sku'           => strtoupper((string) (optional($first->item)->sku ?? '')),
            'units_per_box'         => (int) ($first->units_per_box ?? 0),
            'total_boxes'           => (int) $boxes->count(),
            'boxes_count'           => (int) $boxes->count(),
            'available_boxes_count' => (int) $availableBoxesCount,
            'available_stock'       => (int) $availableStock,
            'total_pieces'          => (int) $boxes->sum(fn ($box) => (int) ($box->current_units ?? 0)),
            'box_labels'            => $this->normalizeUpperStringList($boxes->pluck('label_code')->all()),
            'available_boxes'       => $this->normalizeUpperStringList($availableLabels),
            'location_code'         => 'FAST FLOW',
        ];

        $this->fastFlowBatchCache[$batchCode] = $snapshot;

        return $snapshot;
    }

    protected function hydrateFastFlowItem(array $item): array
    {
        $batchCode = strtoupper(trim((string) ($item['batch_code'] ?? '')));
        $item['is_fastflow'] = true;
        $item['location_code'] = 'FAST FLOW';
        $item['batch_code'] = $batchCode;

        if ($batchCode === '') {
            $item['units_per_box'] = max(0, (int) ($item['units_per_box'] ?? 0));
            $item['total_boxes'] = max(0, (int) ($item['total_boxes'] ?? $item['boxes_count'] ?? count($item['box_labels'] ?? [])));
            $item['boxes_count'] = $item['total_boxes'];
            $item['available_boxes_count'] = max(0, (int) ($item['available_boxes_count'] ?? count($item['available_boxes'] ?? [])));
            $item['available_stock'] = max(0, (int) ($item['available_stock'] ?? 0));
            $item['total_pieces'] = max(0, (int) ($item['total_pieces'] ?? 0));
            $item['box_labels'] = $this->normalizeUpperStringList($item['box_labels'] ?? []);
            $item['available_boxes'] = $this->normalizeUpperStringList($item['available_boxes'] ?? []);
            $item['scanned_boxes'] = $this->normalizeUpperStringList($item['scanned_boxes'] ?? []);
            $item['staged_boxes'] = $this->normalizeUpperStringList($item['staged_boxes'] ?? []);
            $item['box_allocations'] = $this->normalizeBoxAllocations($item['box_allocations'] ?? []);
            $item['stage_box_allocations'] = $this->normalizeBoxAllocations($item['stage_box_allocations'] ?? []);
            return $item;
        }

        $snapshot = $this->getFastFlowBatchSnapshot($batchCode);

        if (!$snapshot) {
            $item['units_per_box'] = max(0, (int) ($item['units_per_box'] ?? 0));
            $item['total_boxes'] = max(0, (int) ($item['total_boxes'] ?? $item['boxes_count'] ?? count($item['box_labels'] ?? [])));
            $item['boxes_count'] = $item['total_boxes'];
            $item['available_boxes_count'] = max(0, (int) ($item['available_boxes_count'] ?? count($item['available_boxes'] ?? [])));
            $item['available_stock'] = max(0, (int) ($item['available_stock'] ?? 0));
            $item['total_pieces'] = max(0, (int) ($item['total_pieces'] ?? 0));
            $item['box_labels'] = $this->normalizeUpperStringList($item['box_labels'] ?? []);
            $item['available_boxes'] = $this->normalizeUpperStringList($item['available_boxes'] ?? []);
            return $item;
        }

        if (empty($item['product_id']) && !empty($snapshot['catalog_item_id'])) {
            $item['product_id'] = (int) $snapshot['catalog_item_id'];
        }

        if (trim((string) ($item['product_sku'] ?? '')) === '') {
            $item['product_sku'] = (string) ($snapshot['product_sku'] ?? '');
        } else {
            $item['product_sku'] = strtoupper((string) $item['product_sku']);
        }

        if (trim((string) ($item['product_name'] ?? '')) === '' || (string) $item['product_name'] === 'Producto') {
            $item['product_name'] = (string) ($snapshot['product_name'] ?? 'Producto');
        }

        $item['units_per_box'] = max(
            (int) ($item['units_per_box'] ?? 0),
            (int) ($snapshot['units_per_box'] ?? 0)
        );

        $item['total_boxes'] = (int) ($snapshot['total_boxes'] ?? 0);
        $item['boxes_count'] = (int) ($snapshot['boxes_count'] ?? 0);
        $item['available_boxes_count'] = (int) ($snapshot['available_boxes_count'] ?? 0);
        $item['available_stock'] = (int) ($snapshot['available_stock'] ?? 0);
        $item['total_pieces'] = (int) ($snapshot['total_pieces'] ?? 0);
        $item['box_labels'] = $this->normalizeUpperStringList($snapshot['box_labels'] ?? []);
        $item['available_boxes'] = $this->normalizeUpperStringList($snapshot['available_boxes'] ?? []);
        $item['scanned_boxes'] = $this->normalizeUpperStringList($item['scanned_boxes'] ?? []);
        $item['staged_boxes'] = $this->normalizeUpperStringList($item['staged_boxes'] ?? []);
        $item['box_allocations'] = $this->normalizeBoxAllocations($item['box_allocations'] ?? []);
        $item['stage_box_allocations'] = $this->normalizeBoxAllocations($item['stage_box_allocations'] ?? []);

        return $item;
    }
}