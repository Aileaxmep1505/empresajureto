<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PickWave;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WmsMovement;
use App\Models\WmsMovementLine;
use App\Models\WmsShipment;
use App\Models\WmsShipmentLine;
use App\Models\WmsShipmentScan;
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

        return view('admin.wms.virtual-pickups.show', [
            'task' => $task,
            'pickWave' => $pickWave,
            'virtualItems' => $task['virtual_items'],
            'physicalItems' => $task['physical_items'],
            'matches' => $task['matches'],
            'selectedLineId' => $lineId,
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
            'lines.*.virtual_flow_mode' => ['nullable', 'string', 'max:80'],
            'lines.*.staging_location_code' => ['nullable', 'string', 'max:120'],
        ]);

        $action = strtolower((string) ($data['action'] ?? 'checklist'));
        $changedLineIds = [];

        DB::transaction(function () use ($pickWave, $data, $action, &$changedLineIds) {
            $locked = PickWave::query()->whereKey($pickWave->id)->lockForUpdate()->firstOrFail();
            $bag = $this->pickWaveBag($locked);
            $items = is_array($bag['items'] ?? null) ? $bag['items'] : [];

            if (empty($items)) {
                throw ValidationException::withMessages([
                    'items' => 'Esta tarea no tiene líneas para actualizar.',
                ]);
            }

            $taskInfo = $this->taskHeader($locked, $bag);
            $hasActiveShipment = !empty($taskInfo['has_active_shipment']);
            $linesById = collect($data['lines'] ?? [])->keyBy(fn ($line) => (string) ($line['line_id'] ?? ''));
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
                $postedMode = strtolower(trim((string) ($posted['virtual_flow_mode'] ?? $item['virtual_flow_mode'] ?? '')));

                if ($hasActiveShipment) {
                    $postedMode = 'direct_to_delivery';
                } elseif (!in_array($postedMode, ['direct_to_delivery', 'staging_before_shipping'], true)) {
                    $postedMode = (string) ($item['virtual_flow_mode'] ?? 'staging_before_shipping');
                }

                if ($action === 'stage') {
                    if ($hasActiveShipment) {
                        $status = 'collected';
                        $qty = max($currentCollected, $qty, $required);
                        $postedMode = 'direct_to_delivery';
                    } else {
                        if ($currentCollected <= 0 && $qty <= 0) {
                            continue;
                        }

                        $qty = max($currentCollected, $qty);
                        $status = 'staged';
                        $postedMode = 'staging_before_shipping';
                        $item['quantity_staged'] = $qty;
                        $item['staged'] = $qty >= $required;
                        $item['pickup_staged_at'] = now()->toDateTimeString();
                        $item['pickup_staged_by'] = auth()->id();
                        $item['staging_location_code'] = trim((string) ($posted['staging_location_code'] ?? $item['staging_location_code'] ?? 'PICKING')) ?: 'PICKING';
                    }
                } else {
                    if ($status === '' || !in_array($status, ['collected', 'partial', 'not_collected', 'pending', 'staged'], true)) {
                        $status = $qty >= $required ? 'collected' : ($qty > 0 ? 'partial' : 'not_collected');
                    }

                    if ($status === 'not_collected' || $status === 'pending') {
                        $qty = 0;
                    }

                    if ($status === 'collected') {
                        $qty = $required;
                    }

                    if ($status === 'staged') {
                        $qty = max($qty, $required);
                        $postedMode = 'staging_before_shipping';
                        $item['quantity_staged'] = $qty;
                        $item['staged'] = $qty >= $required;
                        $item['pickup_staged_at'] = $item['pickup_staged_at'] ?? now()->toDateTimeString();
                        $item['pickup_staged_by'] = $item['pickup_staged_by'] ?? auth()->id();
                        $item['staging_location_code'] = trim((string) ($posted['staging_location_code'] ?? $item['staging_location_code'] ?? 'PICKING')) ?: 'PICKING';
                    }

                    if ($qty > 0) {
                        $item['pickup_collected_at'] = $item['pickup_collected_at'] ?? now()->toDateTimeString();
                        $item['pickup_collected_by'] = $item['pickup_collected_by'] ?? auth()->id();
                    }
                }

                $item['source_type'] = 'virtual';
                $item['is_virtual'] = true;
                $item['requires_pickup'] = true;
                $item['location_code'] = 'RECOLECTAR';
                $item['pickup_status'] = $status;
                $item['virtual_flow_mode'] = $postedMode;
                $item['quantity_collected'] = $qty;
                $item['virtual_sold_quantity'] = $qty;
                $item['sold_quantity'] = $qty;
                $item['quantity_picked'] = $qty;
                $item['picked'] = $qty >= $required;
                $item['virtual_sold'] = true;
                $item['sold_for_order'] = $taskInfo['order_number'];
                $item['sold_for_picking'] = $taskInfo['task_number'];
                $item['do_not_inventory'] = true;
                $item['pickup_checklist_note'] = trim((string) ($posted['note'] ?? $item['pickup_checklist_note'] ?? ''));
                $item['pickup_notes'] = trim((string) ($item['pickup_notes'] ?? ''));
                $item['collector_name'] = trim((string) ($data['collector_name'] ?? $item['collector_name'] ?? ''));
                $item['fulfillment_group_id'] = $item['fulfillment_group_id'] ?? $lineId;

                if ($postedMode === 'direct_to_delivery' && $status === 'collected' && $qty >= $required) {
                    $item['virtual_auto_loaded_to_shipment'] = true;
                    $item['virtual_requires_shipping_scan'] = false;
                    $item['virtual_loaded_at'] = now()->toDateTimeString();
                } elseif ($postedMode === 'staging_before_shipping') {
                    $item['virtual_auto_loaded_to_shipment'] = false;
                    $item['virtual_requires_shipping_scan'] = true;
                }

                $items[$index] = $item;
                $changed[] = $item;
                $changedLineIds[] = $lineId;

                $this->auditVirtualLine($locked, $taskInfo, $item, $status, $qty, [
                    'collector_name' => $data['collector_name'] ?? '',
                    'general_notes' => $data['general_notes'] ?? '',
                    'line_note' => $posted['note'] ?? '',
                    'action' => $action,
                    'virtual_flow_mode' => $postedMode,
                ]);

                if ($postedMode === 'direct_to_delivery' && $status === 'collected' && $qty >= $required) {
                    $this->autoLoadVirtualLineIntoActiveShipment($locked, $taskInfo, $item);
                }
            }

            if (empty($changed)) {
                throw ValidationException::withMessages([
                    'lines' => 'No encontré líneas virtuales para actualizar.',
                ]);
            }

            $bag['items'] = array_values($items);
            $bag['virtual_pickup_last_saved_at'] = now()->toDateTimeString();
            $bag['virtual_pickup_last_saved_by'] = auth()->id();
            $bag['virtual_pickup_collector_name'] = trim((string) ($data['collector_name'] ?? ''));
            $bag['virtual_pickup_notes'] = trim((string) ($data['general_notes'] ?? ''));

            $virtualState = $this->virtualPickupCompletionState($items);
            $bag['virtual_pickup_status'] = $virtualState['status'];
            $bag['virtual_pickup_required_qty'] = $virtualState['required_qty'];
            $bag['virtual_pickup_collected_qty'] = $virtualState['collected_qty'];
            $bag['virtual_pickup_pending_qty'] = $virtualState['pending_qty'];

            if ($virtualState['status'] === 'completed') {
                $bag['virtual_pickup_completed_at'] = $bag['virtual_pickup_completed_at'] ?? now()->toDateTimeString();
                $bag['virtual_pickup_completed_by'] = $bag['virtual_pickup_completed_by'] ?? auth()->id();
            } else {
                $bag['virtual_pickup_completed_at'] = null;
                $bag['virtual_pickup_completed_by'] = null;
            }

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
                ? 'Producto virtual actualizado correctamente.'
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
            $virtualItems = collect($payload['virtual_items'] ?? []);

            if ($virtualItems->isEmpty()) {
                continue;
            }

            $items = collect();

            foreach ($virtualItems as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $status = $this->computedStatus($item, $payload);

                if ($statusFilter !== '' && $status !== $statusFilter) {
                    continue;
                }

                $items->push([
                    'line_id' => $this->lineId($item),
                    'fulfillment_group_id' => $this->fulfillmentGroupId($item),
                    'product_name' => (string) ($item['product_name'] ?? $item['name'] ?? 'Producto virtual'),
                    'sku' => (string) ($item['product_sku'] ?? $item['sku'] ?? ''),
                    'qty' => max(1, (int) ($item['quantity_required'] ?? $item['qty'] ?? 1)),
                    'quantity_collected' => max(0, (int) ($item['quantity_collected'] ?? $item['quantity_picked'] ?? 0)),
                    'virtual_sold_quantity' => max(0, (int) ($item['virtual_sold_quantity'] ?? $item['sold_quantity'] ?? $item['quantity_collected'] ?? $item['quantity_picked'] ?? 0)),
                    'origin' => (string) ($item['pickup_origin_name'] ?? 'Origen externo'),
                    'notes' => (string) ($item['pickup_notes'] ?? $item['pickup_checklist_note'] ?? ''),
                    'location_code' => (string) ($item['location_code'] ?? 'RECOLECTAR'),
                    'staging_location_code' => (string) ($item['staging_location_code'] ?? 'PICKING'),
                    'virtual_flow_mode' => (string) ($item['virtual_flow_mode'] ?? ''),
                    'virtual_sold' => true,
                    'sold_for_order' => $payload['order_number'],
                    'sold_for_picking' => $payload['task_number'],
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

            $mainStatus = $items
                ->sortBy(fn ($item) => $statusOrder[$item['computed_status']] ?? 99)
                ->first()['computed_status'] ?? 'pending';

            $row = [
                'pick_wave_id' => (int) $task->id,
                'task_number' => $payload['task_number'],
                'order_number' => $payload['order_number'],
                'warehouse_name' => $payload['warehouse_name'],
                'created_at' => $this->dateString($task->created_at),
                'shipment_state' => $payload['shipment_state'],
                'has_active_shipment' => $payload['has_active_shipment'],
                'shipment_number' => $payload['shipment_number'],
                'shipment_status' => $payload['shipment_status'],
                'items' => $items->values()->all(),
                'items_count' => (int) $items->count(),
                'qty' => (int) $items->sum('qty'),
                'quantity_collected' => (int) $items->sum('quantity_collected'),
                'computed_status' => $mainStatus,
                'status_label' => $this->statusLabel($mainStatus),
                'status_class' => $this->statusClass($mainStatus),
                'show_url' => route('admin.wms.virtual-pickups.show', $task),
                'pdf_url' => route('admin.wms.virtual-pickups.pdf', $task),
            ];

            if ($q !== '') {
                $blob = mb_strtolower(implode(' ', [
                    $row['task_number'],
                    $row['order_number'],
                    $row['warehouse_name'],
                    $row['status_label'],
                    $row['shipment_number'],
                    $items->pluck('product_name')->implode(' '),
                    $items->pluck('sku')->implode(' '),
                    $items->pluck('origin')->implode(' '),
                    $items->pluck('fulfillment_group_id')->implode(' '),
                ]));

                if (!str_contains($blob, $q)) {
                    continue;
                }
            }

            $rows->push($row);
        }

        return $rows->sortBy(fn ($row) => match ($row['computed_status']) {
            'pending' => 1,
            'partial' => 2,
            'not_collected' => 3,
            'collected' => 4,
            'staged' => 5,
            default => 9,
        })->values();
    }

    private function taskPayload(PickWave $task, ?string $onlyLineId = null): array
    {
        $bag = $this->pickWaveBag($task);
        $header = $this->taskHeader($task, $bag);
        $items = collect(is_array($bag['items'] ?? null) ? $bag['items'] : [])
            ->filter(fn ($item) => is_array($item))
            ->map(function ($item) use ($header) {
                $item['line_id'] = $this->lineId($item);
                $item['fulfillment_group_id'] = $this->fulfillmentGroupId($item);

                if ($this->isVirtualItem($item)) {
                    $item['source_type'] = 'virtual';
                    $item['is_virtual'] = true;
                    $item['requires_pickup'] = true;
                    $item['virtual_sold'] = true;
                    $item['sold_for_order'] = $header['order_number'];
                    $item['sold_for_picking'] = $header['task_number'];
                    $item['do_not_inventory'] = true;

                    if (!array_key_exists('virtual_flow_mode', $item) || trim((string) $item['virtual_flow_mode']) === '') {
                        $item['virtual_flow_mode'] = $header['has_active_shipment'] ? 'direct_to_delivery' : 'staging_before_shipping';
                    }
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
        $shipmentState = $this->shipmentStateForPickWave($task);
        $hasActiveShipment = !empty($shipmentState['id']);

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
            'virtual_pickup_status' => (string) ($bag['virtual_pickup_status'] ?? 'pending'),
            'virtual_pickup_completed_at' => $this->dateString($bag['virtual_pickup_completed_at'] ?? null),
            'virtual_pickup_required_qty' => (int) ($bag['virtual_pickup_required_qty'] ?? 0),
            'virtual_pickup_collected_qty' => (int) ($bag['virtual_pickup_collected_qty'] ?? 0),
            'virtual_pickup_pending_qty' => (int) ($bag['virtual_pickup_pending_qty'] ?? 0),
            'shipment_state' => $shipmentState,
            'has_active_shipment' => $hasActiveShipment,
            'shipment_number' => (string) ($shipmentState['shipment_number'] ?? ''),
            'shipment_status' => (string) ($shipmentState['status'] ?? ''),
            'shipment_scanner_url' => (string) ($shipmentState['scanner_url'] ?? ''),
            'created_at' => $this->dateString($task->created_at),
        ];
    }

    private function shipmentStateForPickWave(PickWave $task): array
    {
        if (!Schema::hasTable('wms_shipments')) {
            return [
                'id' => null,
                'shipment_number' => '',
                'status' => '',
                'scanner_url' => '',
            ];
        }

        $shipment = WmsShipment::query()
            ->where('pick_wave_id', $task->id)
            ->whereNotIn('status', ['cancelled'])
            ->latest('id')
            ->first();

        if (!$shipment) {
            return [
                'id' => null,
                'shipment_number' => '',
                'status' => '',
                'scanner_url' => '',
            ];
        }

        return [
            'id' => (int) $shipment->id,
            'shipment_number' => (string) ($shipment->shipment_number ?? ''),
            'status' => (string) ($shipment->status ?? ''),
            'scanner_url' => url('/admin/wms/shipping/' . $shipment->id . '/scanner'),
        ];
    }

    private function activeShipmentForPickWave(PickWave $task): ?WmsShipment
    {
        if (!Schema::hasTable('wms_shipments')) {
            return null;
        }

        return WmsShipment::query()
            ->where('pick_wave_id', $task->id)
            ->whereNotIn('status', ['cancelled'])
            ->latest('id')
            ->first();
    }

    private function autoLoadVirtualLineIntoActiveShipment(PickWave $task, array $taskInfo, array $item): void
    {
        if (!Schema::hasTable('wms_shipments') || !Schema::hasTable('wms_shipment_lines')) {
            return;
        }

        $shipment = $this->activeShipmentForPickWave($task);

        if (!$shipment || in_array((string) $shipment->status, ['cancelled', 'dispatched'], true)) {
            return;
        }

        $lineId = $this->lineId($item);
        $required = max(1, (int) ($item['quantity_required'] ?? 1));
        $qty = max($required, (int) ($item['quantity_collected'] ?? $item['quantity_picked'] ?? $required));

        $shipmentLine = WmsShipmentLine::query()
            ->where('shipment_id', $shipment->id)
            ->where('pick_line_id', $lineId)
            ->first();

        $meta = [
            'source_type' => 'virtual',
            'is_virtual' => true,
            'requires_pickup' => true,
            'virtual_flow_mode' => 'direct_to_delivery',
            'virtual_auto_loaded_to_shipment' => true,
            'virtual_requires_shipping_scan' => false,
            'virtual_sold' => true,
            'do_not_inventory' => true,
            'sold_for_order' => $taskInfo['order_number'],
            'sold_for_picking' => $taskInfo['task_number'],
            'pickup_status' => (string) ($item['pickup_status'] ?? 'collected'),
            'pickup_collected_at' => $item['pickup_collected_at'] ?? now()->toDateTimeString(),
            'pickup_origin_name' => (string) ($item['pickup_origin_name'] ?? ''),
            'pickup_notes' => (string) ($item['pickup_notes'] ?? ''),
            'product_barcode' => strtoupper((string) ($item['product_barcode'] ?? '')),
            'product_code' => strtoupper((string) ($item['product_code'] ?? '')),
            'brand' => (string) ($item['brand'] ?? $item['brand_name'] ?? ''),
            'model' => (string) ($item['model'] ?? $item['model_name'] ?? ''),
            'description' => (string) ($item['description'] ?? ''),
            'source_item' => $item,
        ];

        if (!$shipmentLine) {
            $shipmentLine = WmsShipmentLine::query()->create($this->filterColumns('wms_shipment_lines', [
                'shipment_id' => $shipment->id,
                'pick_line_id' => $lineId,
                'product_id' => !empty($item['product_id']) ? (int) $item['product_id'] : null,
                'product_name' => (string) ($item['product_name'] ?? 'Producto virtual'),
                'product_sku' => strtoupper((string) ($item['product_sku'] ?? '')),
                'batch_code' => null,
                'location_code' => 'RECOLECTAR',
                'staging_location_code' => 'ENTREGA DIRECTA',
                'is_fastflow' => false,
                'phase' => max(1, (int) ($item['delivery_phase'] ?? $item['phase'] ?? 1)),
                'expected_qty' => $required,
                'loaded_qty' => $qty,
                'missing_qty' => 0,
                'extra_qty' => max(0, $qty - $required),
                'expected_boxes' => 0,
                'loaded_boxes' => 0,
                'missing_boxes' => 0,
                'status' => 'complete',
                'reason_code' => null,
                'reason_note' => null,
                'expected_boxes_json' => [],
                'loaded_boxes_json' => [],
                'expected_allocations_json' => [],
                'loaded_allocations_json' => [[
                    'label' => 'AUTO-VIRTUAL-' . $lineId,
                    'pieces' => $qty,
                    'scanned_at' => now()->toDateTimeString(),
                    'user_id' => auth()->id(),
                    'type' => 'virtual_direct_to_delivery',
                ]],
                'meta' => $meta,
            ]));
        } else {
            $existingMeta = is_array($shipmentLine->meta) ? $shipmentLine->meta : [];
            $shipmentLine->expected_qty = max((int) $shipmentLine->expected_qty, $required);
            $shipmentLine->loaded_qty = max((int) $shipmentLine->loaded_qty, $qty);
            $shipmentLine->missing_qty = max(0, (int) $shipmentLine->expected_qty - (int) $shipmentLine->loaded_qty);
            $shipmentLine->extra_qty = max(0, (int) $shipmentLine->loaded_qty - (int) $shipmentLine->expected_qty);
            $shipmentLine->status = ((int) $shipmentLine->missing_qty <= 0) ? 'complete' : 'partial';
            $shipmentLine->staging_location_code = $shipmentLine->staging_location_code ?: 'ENTREGA DIRECTA';
            $shipmentLine->meta = array_merge($existingMeta, $meta);
            $shipmentLine->save();
        }

        if (Schema::hasTable('wms_shipment_scans')) {
            $alreadyLogged = WmsShipmentScan::query()
                ->where('shipment_id', $shipment->id)
                ->where('shipment_line_id', $shipmentLine->id)
                ->where('scan_type', 'virtual_auto')
                ->exists();

            if (!$alreadyLogged) {
                WmsShipmentScan::query()->create($this->filterColumns('wms_shipment_scans', [
                    'shipment_id' => $shipment->id,
                    'shipment_line_id' => $shipmentLine->id,
                    'scan_type' => 'virtual_auto',
                    'scan_value' => 'AUTO-VIRTUAL-' . strtoupper((string) ($item['product_sku'] ?? $lineId)),
                    'qty' => $qty,
                    'box_label' => null,
                    'result' => 'accepted',
                    'message' => 'Producto virtual cargado automáticamente por entrega directa.',
                    'payload' => [
                        'pick_wave_id' => $task->id,
                        'pick_line_id' => $lineId,
                        'virtual_flow_mode' => 'direct_to_delivery',
                    ],
                    'user_id' => auth()->id(),
                ]));
            }
        }

        $this->syncShipmentMetrics($shipment->id);
    }

    private function syncShipmentMetrics(int $shipmentId): void
    {
        if (!Schema::hasTable('wms_shipments') || !Schema::hasTable('wms_shipment_lines')) {
            return;
        }

        $shipment = WmsShipment::query()->find($shipmentId);

        if (!$shipment) {
            return;
        }

        $lines = WmsShipmentLine::query()->where('shipment_id', $shipmentId)->get();

        $shipment->expected_lines = (int) $lines->count();
        $shipment->scanned_lines = (int) $lines->filter(function ($line) {
            return ((int) ($line->loaded_qty ?? 0) > 0) || ((int) ($line->loaded_boxes ?? 0) > 0);
        })->count();
        $shipment->expected_qty = (int) $lines->sum('expected_qty');
        $shipment->loaded_qty = (int) $lines->sum('loaded_qty');
        $shipment->missing_qty = (int) $lines->sum('missing_qty');
        $shipment->extra_qty = (int) $lines->sum('extra_qty');
        $shipment->expected_boxes = (int) $lines->sum('expected_boxes');
        $shipment->loaded_boxes = (int) $lines->sum('loaded_boxes');
        $shipment->missing_boxes = (int) $lines->sum('missing_boxes');

        if ((int) $shipment->loaded_qty > 0 && $shipment->status === 'draft') {
            $shipment->status = 'loading';
        }

        $shipment->updated_by = auth()->id();
        $shipment->save();
    }

    private function virtualPickupCompletionState(array $items): array
    {
        $virtualItems = collect($items)
            ->filter(fn ($item) => is_array($item) && $this->isVirtualItem($item))
            ->values();

        $requiredQty = 0;
        $collectedQty = 0;
        $hasProgress = false;
        $allComplete = $virtualItems->isNotEmpty();

        foreach ($virtualItems as $item) {
            $required = max(1, (int) ($item['quantity_required'] ?? $item['qty'] ?? 1));
            $collected = max(0, (int) ($item['quantity_collected'] ?? $item['quantity_picked'] ?? 0));
            $status = strtolower(trim((string) ($item['pickup_status'] ?? 'pending')));

            $requiredQty += $required;
            $collectedQty += min($required, $collected);

            if ($collected > 0 || in_array($status, ['collected', 'staged', 'ready_to_ship', 'partial', 'not_collected'], true)) {
                $hasProgress = true;
            }

            $lineComplete = in_array($status, ['collected', 'staged', 'ready_to_ship'], true)
                && $collected >= $required;

            if (!$lineComplete) {
                $allComplete = false;
            }
        }

        $pendingQty = max(0, $requiredQty - $collectedQty);

        return [
            'status' => $allComplete ? 'completed' : ($hasProgress ? 'in_progress' : 'pending'),
            'required_qty' => $requiredQty,
            'collected_qty' => $collectedQty,
            'pending_qty' => $pendingQty,
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
                'virtual_flow_mode' => $extra['virtual_flow_mode'] ?? ($item['virtual_flow_mode'] ?? ''),
                'quantity_collected' => $qty,
                'sold_for_order' => $taskInfo['order_number'],
                'sold_for_picking' => $taskInfo['task_number'],
                'do_not_inventory' => true,
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
                'virtual_flow_mode' => $extra['virtual_flow_mode'] ?? ($item['virtual_flow_mode'] ?? ''),
                'virtual_sold' => true,
                'sold_for_order' => $taskInfo['order_number'],
                'sold_for_picking' => $taskInfo['task_number'],
                'do_not_inventory' => true,
                'fulfillment_group_id' => $this->fulfillmentGroupId($item),
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
            'not_collected' => (int) $rows->where('computed_status', 'not_collected')->count(),
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
            'staged' => 'Dejado en staging/picking',
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
            || filter_var(($item['requires_pickup'] ?? false), FILTER_VALIDATE_BOOLEAN)
            || strtoupper(trim((string) ($item['location_code'] ?? ''))) === 'RECOLECTAR';
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
}
