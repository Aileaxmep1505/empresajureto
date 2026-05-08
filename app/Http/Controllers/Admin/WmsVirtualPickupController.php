<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PickWave;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WmsMovement;
use App\Models\WmsMovementLine;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WmsVirtualPickupBoardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('auth')];
    }

    public function index(Request $request)
    {
        $rows = $this->virtualRows($request);

        return view('admin.wms.virtual-pickups.index', [
            'rows' => $rows,
            'summary' => $this->summary($rows),
            'warehouses' => $this->warehouses(),
            'filters' => [
                'q' => trim((string) $request->get('q', '')),
                'status' => trim((string) $request->get('status', '')),
                'warehouse_id' => (int) $request->get('warehouse_id', 0),
            ],
        ]);
    }

    public function data(Request $request)
    {
        $rows = $this->virtualRows($request);

        return response()->json([
            'ok' => true,
            'rows' => $rows->values()->all(),
            'summary' => $this->summary($rows),
        ]);
    }

    public function show(Request $request, PickWave $pickWave)
    {
        $lineId = trim((string) $request->get('line_id', ''));
        $task = $this->taskPayload($pickWave, $lineId !== '' ? $lineId : null);
        $shipmentState = $this->shipmentStateForPickWave((int) $pickWave->id);

        $task['shipment_state'] = $shipmentState;
        $task['has_active_shipment'] = (bool) ($shipmentState['exists'] ?? false);
        $task['shipment_number'] = (string) ($shipmentState['shipment_number'] ?? '');
        $task['shipment_status'] = (string) ($shipmentState['status'] ?? '');

        return view('admin.wms.virtual-pickups.show', [
            'task' => $task,
            'pickWave' => $pickWave,
            'virtualItems' => $task['virtual_items'],
            'physicalItems' => $task['physical_items'],
            'matches' => $task['matches'],
            'selectedLineId' => $lineId,
            'shipmentState' => $shipmentState,
        ]);
    }

    public function saveChecklist(Request $request, PickWave $pickWave)
    {
        $data = $request->validate([
            'action' => ['nullable', 'string', 'max:40'],
            'collector_name' => ['nullable', 'string', 'max:180'],
            'general_notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.line_id' => ['required', 'string', 'max:180'],
            'lines.*.status' => ['nullable', 'string', 'max:40'],
            'lines.*.quantity_collected' => ['nullable', 'integer', 'min:0'],
            'lines.*.note' => ['nullable', 'string', 'max:1000'],
            'lines.*.virtual_flow_mode' => ['nullable', 'string', 'max:60'],
            'lines.*.staging_location_code' => ['nullable', 'string', 'max:120'],
            'lines.*.label_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $action = strtolower((string) ($data['action'] ?? 'checklist'));
        $changedLineIds = [];
        $now = now()->toDateTimeString();

        DB::transaction(function () use ($pickWave, $data, $action, &$changedLineIds, $now) {
            $locked = PickWave::query()->whereKey($pickWave->id)->lockForUpdate()->firstOrFail();
            $bag = $this->pickWaveBag($locked);
            $items = is_array($bag['items'] ?? null) ? $bag['items'] : [];

            if (empty($items)) {
                throw ValidationException::withMessages([
                    'items' => 'Esta tarea no tiene líneas para actualizar.',
                ]);
            }

            $linesById = collect($data['lines'] ?? [])->keyBy(fn ($line) => (string) ($line['line_id'] ?? ''));
            $taskInfo = $this->taskHeader($locked, $bag);
            $shipmentState = $this->shipmentStateForPickWave((int) $locked->id);
            $hasActiveShipment = (bool) ($shipmentState['exists'] ?? false);
            $changed = [];

            foreach ($items as $index => $item) {
                if (!is_array($item) || !$this->isVirtualItem($item)) {
                    continue;
                }

                $lineId = $this->lineId($item);
                $posted = $linesById->get($lineId);

                if (!$posted) {
                    continue;
                }

                $required = max(1, (int) ($item['quantity_required'] ?? $item['qty'] ?? 1));
                $currentCollected = max(0, (int) ($item['quantity_collected'] ?? $item['quantity_picked'] ?? 0));
                $qty = array_key_exists('quantity_collected', $posted)
                    ? max(0, min($required, (int) $posted['quantity_collected']))
                    : $currentCollected;

                $status = strtolower(trim((string) ($posted['status'] ?? '')));
                $flowMode = strtolower(trim((string) ($posted['virtual_flow_mode'] ?? $item['virtual_flow_mode'] ?? 'staging_before_shipping')));

                if (!in_array($flowMode, ['direct_to_delivery', 'staging_before_shipping'], true)) {
                    $flowMode = 'staging_before_shipping';
                }

                /**
                 * Nuevo flujo:
                 * - Si ya existe embarque activo para este picking, la recolección virtual
                 *   se trata como entrega directa y se carga automáticamente al embarque.
                 * - Si todavía no hay embarque, aquí solo se confirma recolección.
                 *   Si el producto vuelve a almacén, ese ingreso vendido se hace en Recepciones.
                 */
                if ($hasActiveShipment) {
                    $flowMode = 'direct_to_delivery';
                }

                $stagingLocation = strtoupper(trim((string) ($posted['staging_location_code'] ?? $item['staging_location_code'] ?? '')));
                if ($stagingLocation === '' && $flowMode === 'staging_before_shipping') {
                    $stagingLocation = 'PICKING';
                }

                if ($status === '' || !in_array($status, ['collected', 'partial', 'not_collected', 'pending', 'staged'], true)) {
                    $status = $qty >= $required ? 'collected' : ($qty > 0 ? 'partial' : 'not_collected');
                }

                if ($status === 'not_collected' || $status === 'pending') {
                    $qty = 0;
                }

                if ($status === 'collected' || $status === 'staged') {
                    $qty = $required;
                }

                if ($action === 'stage') {
                    throw ValidationException::withMessages([
                        'lines' => 'Este paso ahora se hace desde Recepciones. Si el producto vuelve a almacén, crea una recepción vendida / no inventariar e indica ahí dónde se dejó.',
                    ]);
                }

                if ($flowMode === 'direct_to_delivery' && $qty >= $required) {
                    $status = 'collected';
                    $item['virtual_auto_loaded_to_shipment'] = true;
                    $item['virtual_requires_shipping_scan'] = false;
                    $item['virtual_direct_to_delivery_at'] = $item['virtual_direct_to_delivery_at'] ?? $now;
                }

                if ($flowMode === 'staging_before_shipping' && $status === 'staged') {
                    $item['quantity_staged'] = $qty;
                    $item['staged'] = $qty >= $required;
                    $item['pickup_staged_at'] = $item['pickup_staged_at'] ?? $now;
                    $item['pickup_staged_by'] = $item['pickup_staged_by'] ?? auth()->id();
                    $item['virtual_auto_loaded_to_shipment'] = false;
                    $item['virtual_requires_shipping_scan'] = true;
                } elseif ($flowMode === 'staging_before_shipping') {
                    $item['virtual_auto_loaded_to_shipment'] = false;
                    $item['virtual_requires_shipping_scan'] = true;
                }

                if ($qty > 0) {
                    $item['pickup_collected_at'] = $item['pickup_collected_at'] ?? $now;
                    $item['pickup_collected_by'] = $item['pickup_collected_by'] ?? auth()->id();
                } else {
                    $item['pickup_collected_at'] = null;
                    $item['pickup_collected_by'] = null;
                    $item['quantity_staged'] = 0;
                    $item['staged'] = false;
                    $item['pickup_staged_at'] = null;
                    $item['pickup_staged_by'] = null;
                }

                $item['source_type'] = 'virtual';
                $item['is_virtual'] = true;
                $item['requires_pickup'] = true;
                $item['location_code'] = 'RECOLECTAR';
                $item['pickup_status'] = $status;
                $item['quantity_collected'] = $qty;
                $item['quantity_picked'] = $qty;
                $item['picked'] = $qty >= $required;
                $item['virtual_flow_mode'] = $flowMode;
                $item['staging_location_code'] = $flowMode === 'staging_before_shipping' ? $stagingLocation : '';
                $item['sold_for_order_number'] = $taskInfo['order_number'];
                $item['sold_for_pick_number'] = $taskInfo['task_number'];
                $item['sold_label'] = 'VENDIDO / NO INVENTARIAR';
                $item['pickup_checklist_note'] = trim((string) ($posted['note'] ?? $item['pickup_checklist_note'] ?? ''));
                $item['pickup_label_notes'] = trim((string) ($posted['label_notes'] ?? $item['pickup_label_notes'] ?? ''));
                $item['pickup_notes'] = trim((string) ($item['pickup_notes'] ?? ''));
                $item['collector_name'] = trim((string) ($data['collector_name'] ?? $item['collector_name'] ?? ''));
                $item['fulfillment_group_id'] = $item['fulfillment_group_id'] ?? $lineId;

                $items[$index] = $item;
                $changed[] = $item;
                $changedLineIds[] = $lineId;

                $this->auditVirtualLine($locked, $taskInfo, $item, $status, $qty, [
                    'collector_name' => $data['collector_name'] ?? '',
                    'general_notes' => $data['general_notes'] ?? '',
                    'line_note' => $posted['note'] ?? '',
                    'action' => $action,
                    'virtual_flow_mode' => $flowMode,
                    'staging_location_code' => $item['staging_location_code'] ?? '',
                    'requires_shipping_scan' => (bool) ($item['virtual_requires_shipping_scan'] ?? false),
                    'auto_loaded_to_shipment' => (bool) ($item['virtual_auto_loaded_to_shipment'] ?? false),
                ]);

                if ($hasActiveShipment && $flowMode === 'direct_to_delivery' && $qty >= $required) {
                    $this->autoLoadVirtualShipmentLine((int) $locked->id, $item, $qty);
                }
            }

            if (empty($changed)) {
                throw ValidationException::withMessages([
                    'lines' => 'No encontré líneas virtuales para actualizar.',
                ]);
            }

            $bag['items'] = array_values($items);
            $bag['virtual_pickup_last_saved_at'] = $now;
            $bag['virtual_pickup_last_saved_by'] = auth()->id();
            $bag['virtual_pickup_collector_name'] = trim((string) ($data['collector_name'] ?? ''));
            $bag['virtual_pickup_notes'] = trim((string) ($data['general_notes'] ?? ''));

            $this->savePickWaveBag($locked, $bag);
        });

        $redirectParams = ['pickWave' => $pickWave];
        $firstChangedLineId = (string) ($changedLineIds[0] ?? '');

        if ($firstChangedLineId !== '') {
            $redirectParams['line_id'] = $firstChangedLineId;
        }

        return redirect()
            ->route('admin.wms.virtual-pickups.show', $redirectParams)
            ->with('ok', $action === 'stage'
                ? 'Producto virtual marcado como dejado en staging para embarque.'
                : 'Checklist de recolección guardado correctamente.');
    }

    public function pdf(Request $request, PickWave $pickWave)
    {
        $lineId = trim((string) $request->get('line_id', ''));
        $task = $this->taskPayload($pickWave, $lineId !== '' ? $lineId : null);
        $view = 'admin.wms.virtual-pickups.pdf';
        $filename = 'recoleccion-virtual-' . Str::slug($task['task_number'] ?: ('pick-' . $pickWave->id)) . '.pdf';

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, [
                'task' => $task,
                'pickWave' => $pickWave,
                'virtualItems' => $task['virtual_items'],
                'physicalItems' => $task['physical_items'],
                'matches' => $task['matches'],
                'selectedLineId' => $lineId,
                'isPdf' => true,
            ])->setPaper('letter', 'portrait');

            return $pdf->download($filename);
        }

        return view($view, [
            'task' => $task,
            'pickWave' => $pickWave,
            'virtualItems' => $task['virtual_items'],
            'physicalItems' => $task['physical_items'],
            'matches' => $task['matches'],
            'selectedLineId' => $lineId,
            'isPdf' => false,
        ]);
    }

    private function virtualRows(Request $request): Collection
    {
        if (!Schema::hasTable('pick_waves')) {
            return collect();
        }

        $q = mb_strtolower(trim((string) $request->get('q', '')));
        $statusFilter = strtolower(trim((string) $request->get('status', '')));
        $warehouseId = (int) $request->get('warehouse_id', 0);
        $cols = $this->pickWaveColumns();

        $tasks = PickWave::query()
            ->when($warehouseId > 0 && $cols['warehouse_id'], fn ($query) => $query->where($cols['warehouse_id'], $warehouseId))
            ->orderByDesc('id')
            ->limit(1500)
            ->get();

        $rows = collect();

        foreach ($tasks as $task) {
            $payload = $this->taskPayload($task);
            $virtualItems = collect($payload['virtual_items'] ?? [])
                ->filter(fn ($item) => is_array($item))
                ->values();

            if ($virtualItems->isEmpty()) {
                continue;
            }

            $items = collect();

            foreach ($virtualItems as $item) {
                $status = $this->computedStatus($item, $payload);

                if ($statusFilter !== '' && $status !== $statusFilter) {
                    continue;
                }

                $qty = max(1, (int) ($item['quantity_required'] ?? $item['qty'] ?? 1));
                $quantityCollected = max(0, (int) ($item['quantity_collected'] ?? $item['quantity_picked'] ?? 0));
                $lineId = $this->lineId($item);
                $groupId = $this->fulfillmentGroupId($item);

                $items->push([
                    'line_id' => $lineId,
                    'fulfillment_group_id' => $groupId,
                    'product_name' => (string) ($item['product_name'] ?? $item['name'] ?? 'Producto virtual'),
                    'sku' => (string) ($item['product_sku'] ?? $item['sku'] ?? ''),
                    'qty' => $qty,
                    'quantity_collected' => $quantityCollected,
                    'origin' => (string) ($item['pickup_origin_name'] ?? 'Origen externo'),
                    'notes' => (string) ($item['pickup_notes'] ?? $item['pickup_checklist_note'] ?? ''),
                    'location_code' => (string) ($item['location_code'] ?? 'RECOLECTAR'),
                    'staging_location_code' => (string) ($item['staging_location_code'] ?? 'PICKING'),
                    'computed_status' => $status,
                    'status_label' => $this->statusLabel($status),
                    'status_class' => $this->statusClass($status),
                    'collected_at' => $this->dateString($item['pickup_collected_at'] ?? null),
                    'staged_at' => $this->dateString($item['pickup_staged_at'] ?? null),
                ]);
            }

            if ($items->isEmpty()) {
                continue;
            }

            $statusOrder = [
                'pending' => 1,
                'partial' => 2,
                'not_collected' => 3,
                'collected' => 4,
                'staged' => 5,
            ];

            $mainStatus = (string) ($items
                ->sortBy(fn ($item) => $statusOrder[$item['computed_status']] ?? 99)
                ->first()['computed_status'] ?? 'pending');

            $totalQty = (int) $items->sum('qty');
            $collectedQty = (int) $items->sum('quantity_collected');
            $firstItem = $items->first() ?: [];

            $row = [
                'pick_wave_id' => (int) $task->id,
                'task_number' => $payload['task_number'],
                'order_number' => $payload['order_number'],
                'warehouse_name' => $payload['warehouse_name'],
                'created_at' => $this->dateString($task->created_at),

                'items' => $items->values()->all(),
                'items_count' => (int) $items->count(),
                'qty' => $totalQty,
                'quantity_collected' => $collectedQty,

                'computed_status' => $mainStatus,
                'status_label' => $this->statusLabel($mainStatus),
                'status_class' => $this->statusClass($mainStatus),

                // Campos de compatibilidad para vistas/JS antiguos.
                'line_id' => '',
                'fulfillment_group_id' => $items->pluck('fulfillment_group_id')->filter()->unique()->values()->implode(', '),
                'product_name' => $items->count() === 1 ? (string) ($firstItem['product_name'] ?? 'Producto virtual') : $items->count() . ' productos virtuales',
                'sku' => $items->count() === 1 ? (string) ($firstItem['sku'] ?? '') : '',
                'origin' => $items->pluck('origin')->filter()->unique()->values()->take(2)->implode(' / ') ?: 'Origen externo',
                'notes' => '',
                'collected_at' => '',

                'show_url' => route('admin.wms.virtual-pickups.show', $task),
                'pdf_url' => route('admin.wms.virtual-pickups.pdf', $task),
            ];

            if ($q !== '') {
                $blob = mb_strtolower(implode(' ', [
                    $row['task_number'],
                    $row['order_number'],
                    $row['warehouse_name'],
                    $row['status_label'],
                    $row['fulfillment_group_id'],
                    $items->pluck('product_name')->implode(' '),
                    $items->pluck('sku')->implode(' '),
                    $items->pluck('origin')->implode(' '),
                ]));

                if (!str_contains($blob, $q)) {
                    continue;
                }
            }

            $rows->push($row);
        }

        return $rows
            ->sortBy(fn ($row) => match ($row['computed_status']) {
                'pending' => 1,
                'partial' => 2,
                'not_collected' => 3,
                'collected' => 4,
                'staged' => 5,
                default => 9,
            })
            ->values();
    }

    private function taskPayload(PickWave $task, ?string $onlyLineId = null): array
    {
        $bag = $this->pickWaveBag($task);
        $header = $this->taskHeader($task, $bag);
        $shipmentState = $this->shipmentStateForPickWave((int) $task->id);
        $hasActiveShipment = (bool) ($shipmentState['exists'] ?? false);
        $items = collect(is_array($bag['items'] ?? null) ? $bag['items'] : [])
            ->filter(fn ($item) => is_array($item))
            ->map(function ($item) use ($header, $hasActiveShipment) {
                $item['line_id'] = $this->lineId($item);
                $item['fulfillment_group_id'] = $this->fulfillmentGroupId($item);

                if ($this->isVirtualItem($item)) {
                    $item['source_type'] = 'virtual';
                    $item['is_virtual'] = true;
                    $item['requires_pickup'] = true;
                    $item['location_code'] = 'RECOLECTAR';
                    if ($hasActiveShipment) {
                        $item['virtual_flow_mode'] = 'direct_to_delivery';
                        $item['staging_location_code'] = '';
                    } else {
                        $item['virtual_flow_mode'] = $item['virtual_flow_mode'] ?? 'staging_before_shipping';
                        $item['staging_location_code'] = $item['staging_location_code'] ?? 'PICKING';
                    }
                    $item['sold_for_order_number'] = $item['sold_for_order_number'] ?? ($header['order_number'] ?? '');
                    $item['sold_for_pick_number'] = $item['sold_for_pick_number'] ?? ($header['task_number'] ?? '');
                    $item['sold_label'] = $item['sold_label'] ?? 'VENDIDO / NO INVENTARIAR';
                    $item['virtual_requires_shipping_scan'] = array_key_exists('virtual_requires_shipping_scan', $item)
                        ? (bool) $item['virtual_requires_shipping_scan']
                        : (($item['virtual_flow_mode'] ?? 'staging_before_shipping') !== 'direct_to_delivery');
                    $item['virtual_auto_loaded_to_shipment'] = array_key_exists('virtual_auto_loaded_to_shipment', $item)
                        ? (bool) $item['virtual_auto_loaded_to_shipment']
                        : (($item['virtual_flow_mode'] ?? '') === 'direct_to_delivery' && in_array(($item['pickup_status'] ?? ''), ['collected', 'staged'], true));
                }

                return $item;
            })
            ->values();

        $allVirtualItems = $items->filter(fn ($item) => $this->isVirtualItem($item))->values();
        $allPhysicalItems = $items->reject(fn ($item) => $this->isVirtualItem($item))->values();

        $virtualItems = $allVirtualItems;

        if ($onlyLineId !== null && trim($onlyLineId) !== '') {
            $lineId = trim($onlyLineId);
            $virtualItems = $allVirtualItems
                ->filter(fn ($item) => $this->lineId($item) === $lineId)
                ->values();
        }

        $visibleGroupIds = $virtualItems
            ->map(fn ($item) => $this->fulfillmentGroupId($item))
            ->filter()
            ->unique()
            ->values();

        $physicalItems = $allPhysicalItems
            ->filter(fn ($item) => $visibleGroupIds->contains($this->fulfillmentGroupId($item)))
            ->values();

        $matches = $visibleGroupIds->map(function ($group) use ($physicalItems, $virtualItems) {
            $matchedPhysical = $physicalItems
                ->filter(fn ($physical) => $this->fulfillmentGroupId($physical) === $group)
                ->values();

            $matchedVirtual = $virtualItems
                ->filter(fn ($virtual) => $this->fulfillmentGroupId($virtual) === $group)
                ->values();

            $base = $matchedVirtual->first() ?: $matchedPhysical->first() ?: [];
            $physicalQty = (int) $matchedPhysical->sum(fn ($item) => (int) ($item['quantity_required'] ?? $item['qty'] ?? 0));
            $virtualQty = (int) $matchedVirtual->sum(fn ($item) => (int) ($item['quantity_required'] ?? $item['qty'] ?? 0));

            return [
                'fulfillment_group_id' => (string) $group,
                'product_name' => (string) ($base['product_name'] ?? 'Producto'),
                'sku' => (string) ($base['product_sku'] ?? ''),
                'physical_qty' => $physicalQty,
                'virtual_qty' => $virtualQty,
                'total_qty' => $physicalQty + $virtualQty,
                'physical_items' => $matchedPhysical->values()->all(),
                'virtual_items' => $matchedVirtual->values()->all(),
                'virtual_item' => $matchedVirtual->first(),
            ];
        })->values();

        return array_merge($header, [
            'bag' => $bag,
            'items' => $items->all(),
            'virtual_items' => $virtualItems->all(),
            'physical_items' => $physicalItems->all(),
            'matches' => $matches->all(),
            'selected_line_id' => $onlyLineId,
            'virtual_total' => (int) $virtualItems->sum(fn ($item) => (int) ($item['quantity_required'] ?? $item['qty'] ?? 0)),
            'virtual_collected' => (int) $virtualItems->sum(fn ($item) => (int) ($item['quantity_collected'] ?? $item['quantity_picked'] ?? 0)),
            'physical_total' => (int) $physicalItems->sum(fn ($item) => (int) ($item['quantity_required'] ?? $item['qty'] ?? 0)),
        ]);
    }

    private function taskHeader(PickWave $task, array $bag): array
    {
        $cols = $this->pickWaveColumns();
        $warehouseId = (int) ($bag['warehouse_id'] ?? ($cols['warehouse_id'] ? ($task->{$cols['warehouse_id']} ?? 0) : 0));

        return [
            'id' => (int) $task->id,
            'warehouse_id' => $warehouseId,
            'warehouse_name' => $this->warehouseName($warehouseId),
            'task_number' => (string) ($bag['task_number'] ?? ($cols['task_number'] ? ($task->{$cols['task_number']} ?? '') : '') ?: ('PICK-' . $task->id)),
            'order_number' => (string) ($bag['order_number'] ?? ($cols['order_number'] ? ($task->{$cols['order_number']} ?? '') : '')),
            'assigned_to' => (string) ($bag['assigned_to'] ?? ''),
            'status' => (string) ($bag['status'] ?? 'pending'),
            'collector_name' => (string) ($bag['virtual_pickup_collector_name'] ?? ''),
            'virtual_pickup_notes' => (string) ($bag['virtual_pickup_notes'] ?? ''),
            'shipment_state' => $shipmentState,
            'has_active_shipment' => $hasActiveShipment,
            'shipment_number' => (string) ($shipmentState['shipment_number'] ?? ''),
            'shipment_status' => (string) ($shipmentState['status'] ?? ''),
            'created_at' => $this->dateString($task->created_at),
        ];
    }

    private function auditVirtualLine(PickWave $task, array $taskInfo, array $item, string $status, int $qty, array $extra = []): void
    {
        if (!Schema::hasTable('wms_movements') || !Schema::hasTable('wms_movement_lines')) {
            return;
        }

        $movementType = match ($status) {
            'staged' => 'virtual_pickup_staged',
            'partial' => 'virtual_pickup_partial',
            'not_collected' => 'virtual_pickup_not_collected',
            default => 'virtual_pickup_collected',
        };

        $movementData = $this->filterColumns('wms_movements', [
            'warehouse_id' => $taskInfo['warehouse_id'] ?: null,
            'user_id' => auth()->id(),
            'type' => $movementType,
            'note' => (string) ($extra['general_notes'] ?? ''),
            'reference' => $taskInfo['task_number'],
            'delivered_name' => (string) ($extra['collector_name'] ?? ''),
            'received_name' => auth()->user()?->name,
            'meta' => [
                'source' => 'virtual_pickup_checklist',
                'action' => $extra['action'] ?? 'checklist',
                'pick_wave_id' => $task->id,
                'task_number' => $taskInfo['task_number'],
                'order_number' => $taskInfo['order_number'],
                'line_id' => $this->lineId($item),
                'fulfillment_group_id' => $this->fulfillmentGroupId($item),
                'pickup_status' => $status,
                'quantity_collected' => $qty,
                'virtual_flow_mode' => (string) ($extra['virtual_flow_mode'] ?? $item['virtual_flow_mode'] ?? 'staging_before_shipping'),
                'staging_location_code' => (string) ($extra['staging_location_code'] ?? $item['staging_location_code'] ?? ''),
                'requires_shipping_scan' => (bool) ($extra['requires_shipping_scan'] ?? $item['virtual_requires_shipping_scan'] ?? false),
                'auto_loaded_to_shipment' => (bool) ($extra['auto_loaded_to_shipment'] ?? $item['virtual_auto_loaded_to_shipment'] ?? false),
                'sold_for_order_number' => (string) ($item['sold_for_order_number'] ?? $taskInfo['order_number'] ?? ''),
                'sold_for_pick_number' => (string) ($item['sold_for_pick_number'] ?? $taskInfo['task_number'] ?? ''),
                'sold_label' => (string) ($item['sold_label'] ?? 'VENDIDO / NO INVENTARIAR'),
                'does_not_touch_stock' => true,
            ],
        ]);

        $movement = WmsMovement::query()->create($movementData);

        WmsMovementLine::query()->create($this->filterColumns('wms_movement_lines', [
            'movement_id' => $movement->id,
            'line_uid' => $this->lineId($item),
            'catalog_item_id' => (int) ($item['product_id'] ?? $item['catalog_item_id'] ?? 0) ?: null,
            'location_id' => null,
            'qty' => $qty,
            'source_type' => 'virtual',
            'stock_before' => 0,
            'stock_after' => 0,
            'inv_before' => 0,
            'inv_after' => 0,
            'meta' => [
                'product_name' => (string) ($item['product_name'] ?? 'Producto virtual'),
                'sku' => (string) ($item['product_sku'] ?? ''),
                'quantity_required' => max(1, (int) ($item['quantity_required'] ?? 1)),
                'quantity_collected' => $qty,
                'pickup_status' => $status,
                'line_note' => (string) ($extra['line_note'] ?? ''),
                'source_type' => 'virtual',
                'is_virtual' => true,
                'requires_pickup' => true,
                'fulfillment_group_id' => $this->fulfillmentGroupId($item),
                'virtual_flow_mode' => (string) ($extra['virtual_flow_mode'] ?? $item['virtual_flow_mode'] ?? 'staging_before_shipping'),
                'staging_location_code' => (string) ($extra['staging_location_code'] ?? $item['staging_location_code'] ?? ''),
                'requires_shipping_scan' => (bool) ($extra['requires_shipping_scan'] ?? $item['virtual_requires_shipping_scan'] ?? false),
                'auto_loaded_to_shipment' => (bool) ($extra['auto_loaded_to_shipment'] ?? $item['virtual_auto_loaded_to_shipment'] ?? false),
                'sold_for_order_number' => (string) ($item['sold_for_order_number'] ?? $taskInfo['order_number'] ?? ''),
                'sold_for_pick_number' => (string) ($item['sold_for_pick_number'] ?? $taskInfo['task_number'] ?? ''),
                'sold_label' => (string) ($item['sold_label'] ?? 'VENDIDO / NO INVENTARIAR'),
                'does_not_touch_stock' => true,
                'source_item' => $item,
            ],
        ]));
    }

    private function savePickWaveBag(PickWave $task, array $bag): void
    {
        $cols = $this->pickWaveColumns();

        if ($cols['json']) {
            $task->{$cols['json']} = $this->assignColumnValue('pick_waves', $cols['json'], $bag);
        }

        if ($cols['items']) {
            $task->{$cols['items']} = $this->assignColumnValue('pick_waves', $cols['items'], $bag['items'] ?? []);
        }

        if ($cols['deliveries']) {
            $task->{$cols['deliveries']} = $this->assignColumnValue('pick_waves', $cols['deliveries'], $bag['deliveries'] ?? []);
        }

        $task->save();
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
            $decoded = $this->decodePossibleJsonValue($task->{$cols['items']} ?? null);
            if (is_array($decoded)) {
                $bag['items'] = $decoded;
            }
        }

        if ($cols['deliveries'] && empty($bag['deliveries'])) {
            $decoded = $this->decodePossibleJsonValue($task->{$cols['deliveries']} ?? null);
            if (is_array($decoded)) {
                $bag['deliveries'] = $decoded;
            }
        }

        return $bag;
    }

    private function pickWaveColumns(): array
    {
        return [
            'json' => $this->firstExistingColumn('pick_waves', ['meta', 'data', 'payload', 'extra']),
            'items' => $this->firstExistingColumn('pick_waves', ['items', 'items_json']),
            'deliveries' => $this->firstExistingColumn('pick_waves', ['deliveries', 'deliveries_json']),
            'task_number' => $this->firstExistingColumn('pick_waves', ['task_number', 'code']),
            'order_number' => $this->firstExistingColumn('pick_waves', ['order_number', 'reference', 'order_ref']),
            'warehouse_id' => $this->firstExistingColumn('pick_waves', ['warehouse_id']),
            'status' => $this->firstExistingColumn('pick_waves', ['status']),
        ];
    }

    private function summary(Collection $rows): array
    {
        return [
            'total' => (int) $rows->count(),
            'pending' => (int) $rows->where('computed_status', 'pending')->count(),
            'partial' => (int) $rows->where('computed_status', 'partial')->count(),
            'collected' => (int) $rows->where('computed_status', 'collected')->count(),
            'staged' => (int) $rows->where('computed_status', 'staged')->count(),
        ];
    }

    private function computedStatus(array $item, array $task = []): string
    {
        $status = strtolower(trim((string) ($item['pickup_status'] ?? 'pending')));
        $required = max(1, (int) ($item['quantity_required'] ?? 1));
        $collected = max(0, (int) ($item['quantity_collected'] ?? $item['quantity_picked'] ?? 0));
        $staged = max(0, (int) ($item['quantity_staged'] ?? 0));

        if ($status === 'staged' || $staged > 0) {
            return 'staged';
        }

        if ($status === 'not_collected') {
            return 'not_collected';
        }

        if ($status === 'partial' || ($collected > 0 && $collected < $required)) {
            return 'partial';
        }

        if ($status === 'collected' || $collected >= $required) {
            return 'collected';
        }

        return 'pending';
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Pendiente de recolectar',
            'partial' => 'Recolectado parcial',
            'collected' => 'Recolectado completo',
            'staged' => 'Listo en staging para embarque',
            'not_collected' => 'No recolectado',
            default => 'Pendiente',
        };
    }

    private function statusClass(string $status): string
    {
        return match ($status) {
            'collected', 'staged' => 'success',
            'pending', 'not_collected' => 'danger',
            default => 'info',
        };
    }

    private function isVirtualItem(array $item): bool
    {
        $source = strtolower(trim((string) ($item['source_type'] ?? '')));

        return $source === 'virtual'
            || filter_var(($item['is_virtual'] ?? false), FILTER_VALIDATE_BOOLEAN)
            || filter_var(($item['requires_pickup'] ?? false), FILTER_VALIDATE_BOOLEAN);
    }

    private function lineId(array $item): string
    {
        return (string) ($item['line_id'] ?? $item['uid'] ?? $item['id'] ?? md5(json_encode($item)));
    }

    private function fulfillmentGroupId(array $item): string
    {
        return (string) ($item['fulfillment_group_id'] ?? $item['match_id'] ?? $this->lineId($item));
    }

    private function warehouses(): Collection
    {
        if (!Schema::hasTable('warehouses')) {
            return collect();
        }

        return Warehouse::query()->orderBy('name')->get(['id', 'name', 'code']);
    }

    private function warehouseName(int $warehouseId): string
    {
        if ($warehouseId <= 0 || !Schema::hasTable('warehouses')) {
            return 'Sin almacén';
        }

        $warehouse = Warehouse::query()->find($warehouseId, ['id', 'name', 'code']);

        return $warehouse ? (string) ($warehouse->name ?: $warehouse->code ?: 'Almacén') : 'Sin almacén';
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

    private function filterColumns(string $table, array $data): array
    {
        return collect($data)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, (string) $column))
            ->all();
    }

    private function assignColumnValue(string $table, ?string $column, $value)
    {
        if (!$column) {
            return null;
        }

        $type = Schema::getColumnType($table, $column);

        if (in_array($type, ['json', 'jsonb'], true)) {
            return $value;
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $value;
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

    private function dateString($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function shipmentStateForPickWave(int $pickWaveId): array
    {
        if ($pickWaveId <= 0 || !Schema::hasTable('wms_shipments')) {
            return [
                'exists' => false,
                'shipment_id' => null,
                'shipment_number' => '',
                'status' => '',
                'scanner_url' => '',
            ];
        }

        $query = DB::table('wms_shipments')->where('pick_wave_id', $pickWaveId);

        if (Schema::hasColumn('wms_shipments', 'status')) {
            $query->whereNotIn('status', ['cancelled']);
        }

        $shipment = $query->orderByDesc('id')->first();

        if (!$shipment) {
            return [
                'exists' => false,
                'shipment_id' => null,
                'shipment_number' => '',
                'status' => '',
                'scanner_url' => '',
            ];
        }

        return [
            'exists' => true,
            'shipment_id' => (int) $shipment->id,
            'shipment_number' => (string) ($shipment->shipment_number ?? ('SHIP-' . $shipment->id)),
            'status' => (string) ($shipment->status ?? ''),
            'scanner_url' => url('/admin/wms/shipping/' . $shipment->id . '/scanner'),
        ];
    }

    private function autoLoadVirtualShipmentLine(int $pickWaveId, array $item, int $qty): void
    {
        if ($pickWaveId <= 0 || $qty <= 0 || !Schema::hasTable('wms_shipments') || !Schema::hasTable('wms_shipment_lines')) {
            return;
        }

        $state = $this->shipmentStateForPickWave($pickWaveId);

        if (empty($state['exists']) || empty($state['shipment_id'])) {
            return;
        }

        $shipmentId = (int) $state['shipment_id'];
        $lineId = $this->lineId($item);
        $productId = !empty($item['product_id']) ? (int) $item['product_id'] : null;
        $sku = strtoupper(trim((string) ($item['product_sku'] ?? '')));

        $lineQuery = DB::table('wms_shipment_lines')->where('shipment_id', $shipmentId);

        if (Schema::hasColumn('wms_shipment_lines', 'pick_line_id')) {
            $lineQuery->where('pick_line_id', $lineId);
        }

        $line = $lineQuery->first();

        if (!$line) {
            $fallback = DB::table('wms_shipment_lines')->where('shipment_id', $shipmentId);

            if ($productId && Schema::hasColumn('wms_shipment_lines', 'product_id')) {
                $fallback->where('product_id', $productId);
            } elseif ($sku !== '' && Schema::hasColumn('wms_shipment_lines', 'product_sku')) {
                $fallback->where('product_sku', $sku);
            } else {
                return;
            }

            $line = $fallback->orderBy('id')->first();
        }

        if (!$line) {
            return;
        }

        $expectedQty = max((int) ($line->expected_qty ?? 0), $qty);
        $loadedQty = max((int) ($line->loaded_qty ?? 0), $expectedQty);

        $meta = [];
        if (isset($line->meta)) {
            $decoded = $this->decodePossibleJsonValue($line->meta);
            $meta = is_array($decoded) ? $decoded : [];
        }

        $meta['virtual_flow_mode'] = 'direct_to_delivery';
        $meta['virtual_auto_loaded_to_shipment'] = true;
        $meta['virtual_requires_shipping_scan'] = false;
        $meta['auto_loaded_reason'] = 'virtual_pickup_completed_after_shipment';
        $meta['auto_loaded_at'] = now()->toDateTimeString();
        $meta['sold_label'] = 'VENDIDO / NO INVENTARIAR';

        $updates = [];
        foreach ([
            'expected_qty' => $expectedQty,
            'loaded_qty' => $loadedQty,
            'missing_qty' => 0,
            'extra_qty' => max(0, $loadedQty - $expectedQty),
            'status' => 'complete',
            'meta' => $meta,
        ] as $column => $value) {
            if (Schema::hasColumn('wms_shipment_lines', $column)) {
                $updates[$column] = $column === 'meta'
                    ? $this->assignColumnValue('wms_shipment_lines', 'meta', $value)
                    : $value;
            }
        }

        if (Schema::hasColumn('wms_shipment_lines', 'updated_at')) {
            $updates['updated_at'] = now();
        }

        if (!empty($updates)) {
            DB::table('wms_shipment_lines')->where('id', $line->id)->update($updates);
        }

        $this->syncShipmentMetricsLite($shipmentId);
    }

    private function syncShipmentMetricsLite(int $shipmentId): void
    {
        if (!Schema::hasTable('wms_shipments') || !Schema::hasTable('wms_shipment_lines')) {
            return;
        }

        $lines = DB::table('wms_shipment_lines')->where('shipment_id', $shipmentId)->get();

        if ($lines->isEmpty()) {
            return;
        }

        $updates = [];
        $map = [
            'expected_lines' => $lines->count(),
            'scanned_lines' => $lines->filter(fn ($line) => ((int) ($line->loaded_qty ?? 0)) > 0 || ((int) ($line->loaded_boxes ?? 0)) > 0)->count(),
            'expected_qty' => $lines->sum(fn ($line) => (int) ($line->expected_qty ?? 0)),
            'loaded_qty' => $lines->sum(fn ($line) => (int) ($line->loaded_qty ?? 0)),
            'missing_qty' => $lines->sum(fn ($line) => max(0, (int) ($line->missing_qty ?? 0))),
            'extra_qty' => $lines->sum(fn ($line) => max(0, (int) ($line->extra_qty ?? 0))),
            'expected_boxes' => $lines->sum(fn ($line) => (int) ($line->expected_boxes ?? 0)),
            'loaded_boxes' => $lines->sum(fn ($line) => (int) ($line->loaded_boxes ?? 0)),
            'missing_boxes' => $lines->sum(fn ($line) => max(0, (int) ($line->missing_boxes ?? 0))),
        ];

        foreach ($map as $column => $value) {
            if (Schema::hasColumn('wms_shipments', $column)) {
                $updates[$column] = $value;
            }
        }

        if (Schema::hasColumn('wms_shipments', 'updated_at')) {
            $updates['updated_at'] = now();
        }

        if (!empty($updates)) {
            DB::table('wms_shipments')->where('id', $shipmentId)->update($updates);
        }
    }

}
