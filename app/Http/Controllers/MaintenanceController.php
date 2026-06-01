<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use App\Models\InventoryItem;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function index()
    {
        $maintenances = Maintenance::with('item')
            ->orderByDesc('maintenance_date')
            ->orderByDesc('id')
            ->get();

        $items = InventoryItem::orderBy('name')->get();

        $counts = [
            'programado' => $maintenances->where('status', 'programado')->count(),
            'en_proceso' => $maintenances->where('status', 'en_proceso')->count(),
            'completado' => $maintenances->where('status', 'completado')->count(),
            'cancelado'  => $maintenances->where('status', 'cancelado')->count(),
        ];

        return view('maintenance.index', compact('maintenances', 'items', 'counts'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        Maintenance::create($data);

        return redirect()->route('maintenance.index')->with('ok', 'Mantenimiento registrado.');
    }

    public function update(Request $request, Maintenance $maintenance)
    {
        $data = $this->validateData($request);

        $maintenance->update($data);

        return redirect()->route('maintenance.index')->with('ok', 'Mantenimiento actualizado.');
    }

    public function complete(Maintenance $maintenance)
    {
        $maintenance->update(['status' => 'completado']);

        return redirect()->route('maintenance.index')->with('ok', 'Mantenimiento marcado como completado.');
    }

    public function destroy(Maintenance $maintenance)
    {
        $maintenance->delete();

        return back()->with('ok', 'Mantenimiento eliminado.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'type' => 'required|in:preventivo,correctivo',
            'status' => 'required|in:programado,en_proceso,completado,cancelado',
            'technician' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'maintenance_date' => 'required|date',
            'next_maintenance_date' => 'nullable|date',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
    }
}