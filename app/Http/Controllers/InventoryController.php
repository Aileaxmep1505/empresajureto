<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use App\Models\InventoryAssignment;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class InventoryController extends Controller
{
    public function index()
    {
        $items = InventoryItem::with('category')
            ->orderBy('name')
            ->get();

        $assignments = InventoryAssignment::query()
            ->with('item.category')
            ->leftJoin('users', 'inventory_assignments.user_id', '=', 'users.id')
            ->select('inventory_assignments.*', 'users.name as assigned_user_name')
            ->orderByDesc('assigned_at')
            ->get();

        $activeAssignments = $assignments->filter(function ($assignment) {
            $status = strtolower((string) ($assignment->status ?? 'activa'));
            return in_array($status, ['activa', 'activo', 'active']);
        });

        $assignedItemIds = $activeAssignments->pluck('inventory_item_id')->filter()->unique();

        $fixedAssets = $items->filter(fn($i) => $i->type === 'activo_fijo');
        $consumibles = $items->filter(fn($i) => $i->type === 'consumible');

        $totalAssets = $fixedAssets->count();
        $assignedAssets = $fixedAssets->filter(fn($i) => $assignedItemIds->contains($i->id))->count();
        $activeAssignmentsCount = $activeAssignments->count();
        $consumiblesCount = $consumibles->count();

        $lowStockCount = $items->filter(fn($i) => (int)$i->stock <= (int)$i->stock_min)->count();

        $byCategory = InventoryCategory::withCount('items')
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'name'  => $c->name,
                'count' => (int) $c->items_count,
            ])
            ->values();

        $assetStatusChart = [
            'Disponible'    => 0,
            'Asignado'      => 0,
            'En reparación' => 0,
            'De baja'       => 0,
        ];

        foreach ($fixedAssets as $item) {
            $rawStatus = strtolower((string) ($item->asset_status ?? ''));

            if ($rawStatus === '') {
                $rawStatus = $assignedItemIds->contains($item->id) ? 'asignado' : 'disponible';
            }

            if (in_array($rawStatus, ['en reparacion', 'en_reparacion', 'reparacion'])) {
                $assetStatusChart['En reparación']++;
            } elseif (in_array($rawStatus, ['dado_de_baja', 'de baja', 'baja'])) {
                $assetStatusChart['De baja']++;
            } elseif (in_array($rawStatus, ['asignado', 'assigned'])) {
                $assetStatusChart['Asignado']++;
            } else {
                $assetStatusChart['Disponible']++;
            }
        }

        $inventoryAlerts = $items->filter(function ($i) {
                return $i->type === 'consumible' && (int)$i->stock <= (int)$i->stock_min;
            })
            ->sortBy(fn($i) => ((int)$i->stock - (int)$i->stock_min))
            ->take(6)
            ->values();

        $recentAssignments = $assignments->take(8)->values();

        return view('inventory.index', compact(
            'totalAssets',
            'assignedAssets',
            'activeAssignmentsCount',
            'consumiblesCount',
            'lowStockCount',
            'byCategory',
            'assetStatusChart',
            'inventoryAlerts',
            'recentAssignments'
        ));
    }

    public function create()
    {
        $categories = InventoryCategory::orderBy('name')->get();
        return view('inventory.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'inventory_category_id' => 'required|exists:inventory_categories,id',
            'type' => 'required|in:activo_fijo,consumible',
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
            'stock' => 'required|integer|min:0',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',

            'asset_status' => 'nullable|in:disponible,asignado,en_reparacion,dado_de_baja',
            'condition' => 'nullable|in:nuevo,bueno,regular,malo',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',

            'stock_min' => 'nullable|integer|min:0',
            'stock_max' => 'nullable|integer|min:0',

            'photo' => 'nullable|image|max:4096',

            'internal_code' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'warranty_until' => 'nullable|date',
            'processor' => 'nullable|string|max:255',
            'ram' => 'nullable|string|max:255',
            'storage' => 'nullable|string|max:255',
            'operating_system' => 'nullable|string|max:255',
            'mac_address' => 'nullable|string|max:255',
        ]);

        if ($data['type'] === 'activo_fijo') {
            $data['stock_min'] = 0;
            $data['stock_max'] = 0;

            if (empty($data['asset_status'])) {
                $data['asset_status'] = 'disponible';
            }
        }

        if ($data['type'] === 'consumible') {
            $data['asset_status'] = null;
            $data['condition'] = null;
            $data['brand'] = null;
            $data['model'] = null;
            $data['serial_number'] = null;
            $data['stock_min'] = (int)($data['stock_min'] ?? 0);
            $data['stock_max'] = (int)($data['stock_max'] ?? 0);
        }

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('inventory', 'public');
        }

        InventoryItem::create($data);

        return redirect()->route('assets.board')->with('ok', 'Artículo creado.');
    }

    public function edit(InventoryItem $item)
    {
        $categories = InventoryCategory::orderBy('name')->get();
        return view('inventory.edit', compact('item', 'categories'));
    }

    public function update(Request $request, InventoryItem $item)
    {
        $data = $request->validate([
            'inventory_category_id' => 'required|exists:inventory_categories,id',
            'type' => 'required|in:activo_fijo,consumible',
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
            'stock' => 'required|integer|min:0',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',

            'asset_status' => 'nullable|in:disponible,asignado,en_reparacion,dado_de_baja',
            'condition' => 'nullable|in:nuevo,bueno,regular,malo',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',

            'stock_min' => 'nullable|integer|min:0',
            'stock_max' => 'nullable|integer|min:0',

            'photo' => 'nullable|image|max:4096',
            'remove_photo' => 'nullable|boolean',

            'internal_code' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'warranty_until' => 'nullable|date',
            'processor' => 'nullable|string|max:255',
            'ram' => 'nullable|string|max:255',
            'storage' => 'nullable|string|max:255',
            'operating_system' => 'nullable|string|max:255',
            'mac_address' => 'nullable|string|max:255',
        ]);

        if ($data['type'] === 'activo_fijo') {
            $data['stock_min'] = 0;
            $data['stock_max'] = 0;

            if (empty($data['asset_status'])) {
                $data['asset_status'] = 'disponible';
            }
        }

        if ($data['type'] === 'consumible') {
            $data['asset_status'] = null;
            $data['condition'] = null;
            $data['brand'] = null;
            $data['model'] = null;
            $data['serial_number'] = null;
            $data['stock_min'] = (int)($data['stock_min'] ?? 0);
            $data['stock_max'] = (int)($data['stock_max'] ?? 0);
        }

        if ($request->boolean('remove_photo') && $item->photo) {
            Storage::disk('public')->delete($item->photo);
            $data['photo'] = null;
        }

        if ($request->hasFile('photo')) {
            if ($item->photo) {
                Storage::disk('public')->delete($item->photo);
            }

            $data['photo'] = $request->file('photo')->store('inventory', 'public');
        }

        $item->update($data);

        return redirect()->route('assets.board')->with('ok', 'Artículo actualizado.');
    }

    public function destroy(InventoryItem $item)
    {
        if ($item->photo) {
            Storage::disk('public')->delete($item->photo);
        }

        $item->delete();

        return back()->with('ok', 'Artículo eliminado.');
    }

    public function assign(Request $request)
    {
        $data = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required|integer|min:1',
            'signature' => 'required|string',
        ]);

        $item = InventoryItem::findOrFail($data['inventory_item_id']);

        if ($item->stock < $data['quantity']) {
            return back()->with('bad', 'No hay stock suficiente.');
        }

        $item->decrement('stock', $data['quantity']);

        if ($item->type === 'activo_fijo') {
            $item->update([
                'asset_status' => 'asignado',
            ]);
        }

        InventoryAssignment::create([
            'inventory_item_id' => $item->id,
            'user_id' => $data['user_id'],
            'quantity' => $data['quantity'],
            'signature' => $data['signature'],
            'assigned_at' => now(),
        ]);

        return back()->with('ok', 'Asignación registrada con firma.');
    }

    public function board()
    {
        $items = InventoryItem::with(['category', 'maintenances'])
            ->orderByDesc('id')
            ->get();

        $fixedAssets = $items->where('type', 'activo_fijo')->values();
        $consumables = $items->where('type', 'consumible')->values();

        $categories = InventoryCategory::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        // Última asignación (con relaciones) por artículo, para el drawer.
        $assignmentsByItem = InventoryAssignment::with(['user', 'deliveredBy', 'receivedBy'])
            ->whereIn('inventory_item_id', $items->pluck('id'))
            ->orderByDesc('assigned_at')
            ->get()
            ->groupBy('inventory_item_id')
            ->map(fn($group) => $group->first());

        return view('inventory.board', [
            'items' => $items,
            'fixedAssets' => $fixedAssets,
            'consumables' => $consumables,
            'categories' => $categories,
            'users' => $users,
            'fixedCount' => $fixedAssets->count(),
            'consumableCount' => $consumables->count(),
            'assignmentsByItem' => $assignmentsByItem,
        ]);
    }

    public function save(Request $request)
    {
        $id = $request->input('item_id');
        $item = $id ? InventoryItem::findOrFail($id) : null;

        $data = $request->validate([
            'inventory_category_id' => 'required|exists:inventory_categories,id',
            'type' => 'required|in:activo_fijo,consumible',
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
            'stock' => 'required|integer|min:0',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'asset_status' => 'nullable|in:disponible,asignado,en_reparacion,dado_de_baja',
            'condition' => 'nullable|in:nuevo,bueno,regular,malo',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255|unique:inventory_items,serial_number' . ($id ? ',' . $id : ''),
            'internal_code' => 'nullable|string|max:255|unique:inventory_items,internal_code' . ($id ? ',' . $id : ''),
            'stock_min' => 'nullable|integer|min:0',
            'stock_max' => 'nullable|integer|min:0',
            'photo' => 'nullable|image|max:4096',
            'remove_photo' => 'nullable|boolean',
            'department' => 'nullable|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'warranty_until' => 'nullable|date',
            'processor' => 'nullable|string|max:255',
            'ram' => 'nullable|string|max:255',
            'storage' => 'nullable|string|max:255',
            'operating_system' => 'nullable|string|max:255',
            'mac_address' => 'nullable|string|max:255',
        ], [], ['serial_number' => 'número de serie', 'internal_code' => 'código interno']);

        if ($data['type'] === 'activo_fijo') {
            $data['stock_min'] = 0;
            $data['stock_max'] = 0;
            if (empty($data['asset_status'])) $data['asset_status'] = 'disponible';
        } else {
            $data['asset_status'] = null;
            $data['condition'] = null;
            $data['brand'] = null;
            $data['model'] = null;
            $data['serial_number'] = null;
            $data['stock_min'] = (int)($data['stock_min'] ?? 0);
            $data['stock_max'] = (int)($data['stock_max'] ?? 0);
        }

        if ($item && $request->boolean('remove_photo') && $item->photo) {
            Storage::disk('public')->delete($item->photo);
            $data['photo'] = null;
        }
        if ($request->hasFile('photo')) {
            if ($item && $item->photo) Storage::disk('public')->delete($item->photo);
            $data['photo'] = $request->file('photo')->store('inventory', 'public');
        }
        unset($data['remove_photo']);

        if ($item) {
            $item->update($data);
            $msg = 'Artículo actualizado.';
        } else {
            InventoryItem::create($data);
            $msg = 'Artículo creado.';
        }

        return redirect()->route('assets.board')->with('ok', $msg);
    }

    public function stockMove(Request $request, InventoryItem $item)
    {
        $data = $request->validate([
            'movement_type' => 'required|in:entrada,salida',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($data['movement_type'] === 'salida' && (int)$item->stock < (int)$data['quantity']) {
            return back()->with('bad', 'Stock insuficiente para la salida.');
        }

        $data['movement_type'] === 'entrada'
            ? $item->increment('stock', $data['quantity'])
            : $item->decrement('stock', $data['quantity']);

        StockMovement::create([
            'inventory_item_id' => $item->id,
            'user_id' => auth()->id(),
            'movement_type' => $data['movement_type'],
            'quantity' => $data['quantity'],
            'reason' => $data['reason'] ?? null,
        ]);

        return redirect()->route('assets.board')->with('ok', 'Movimiento de stock registrado.');
    }

    public function userPdf($userId)
    {
        $user = User::findOrFail($userId);

        $assignments = InventoryAssignment::with('item.category')
            ->where('user_id', $userId)
            ->orderByDesc('assigned_at')
            ->get();

        $pdf = Pdf::loadView('inventory.pdf_user', compact('user', 'assignments'))
            ->setPaper('letter', 'portrait');

        return $pdf->download('resguardo_' . $user->name . '.pdf');
    }
}