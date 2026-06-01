<?php

namespace App\Http\Controllers;

use App\Models\InventoryAssignment;
use App\Models\InventoryItem;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InventoryAssignmentController extends Controller
{
    public function index()
    {
        $assignments = InventoryAssignment::with(['item.category', 'user'])
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->get();

        $items = InventoryItem::with('category')
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();

        $users = User::orderBy('name')->get();

        $activeCount = $assignments->where('status', 'activa')->count();

        return view('inventory.assignments.index', compact(
            'assignments',
            'items',
            'users',
            'activeCount'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'signature' => 'required|string',
        ]);

        $item = InventoryItem::findOrFail($data['inventory_item_id']);
        $user = User::findOrFail($data['user_id']);

        if ((int)$item->stock < (int)$data['quantity']) {
            return back()->with('bad', 'No hay stock suficiente para asignar.');
        }

        $item->decrement('stock', (int)$data['quantity']);

        if ($item->type === 'activo_fijo') {
            $item->update([
                'asset_status' => 'asignado',
            ]);
        }

        $assignment = InventoryAssignment::create([
            'inventory_item_id' => $item->id,
            'user_id' => $user->id,
            'quantity' => $data['quantity'],
            'notes' => $data['notes'] ?? null,
            'signature' => $data['signature'],
            'folio' => strtoupper(Str::random(8)),
            'status' => 'activa',
            'assigned_at' => now(),
        ]);

        return redirect()->route('assets.assignments.pdf', $assignment->id);
    }

    public function returnAsset(Request $request, InventoryAssignment $assignment)
    {
        $data = $request->validate([
            'return_reason' => 'required|string',
            'return_details' => 'required|string',
            'return_condition' => 'required|in:excelente,bueno,regular,malo,dañado',
        ]);

        if ($assignment->status !== 'activa') {
            return back()->with('bad', 'Esta asignación ya fue devuelta.');
        }

        $item = InventoryItem::findOrFail($assignment->inventory_item_id);

        $item->increment('stock', (int)$assignment->quantity);

        if ($item->type === 'activo_fijo') {
            $item->update([
                'asset_status' => 'disponible',
                'condition' => $data['return_condition'] === 'dañado' ? 'malo' : ($item->condition ?? 'bueno'),
            ]);
        }

        $assignment->update([
            'status' => 'devuelta',
            'return_reason' => $data['return_reason'],
            'return_details' => $data['return_details'],
            'return_condition' => $data['return_condition'],
            'returned_at' => now(),
        ]);

        return redirect()->route('assets.assignments.index')->with('ok', 'Activo devuelto correctamente.');
    }

    public function pdf(InventoryAssignment $assignment)
    {
        $assignment->load(['item.category', 'user']);

        $pdf = Pdf::loadView('inventory.assignments.pdf', compact('assignment'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream('carta_responsiva_'.$assignment->folio.'.pdf');
    }
}