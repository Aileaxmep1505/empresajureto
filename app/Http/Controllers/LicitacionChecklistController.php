<?php

namespace App\Http\Controllers;

use App\Models\Licitacion;
use App\Models\LicitacionChecklistCompra;
use App\Models\LicitacionChecklistFacturacion;
use App\Models\LicitacionContabilidad;
use Illuminate\Http\Request;

class LicitacionChecklistController extends Controller
{
    /**
     * PASO 10: Checklist de compras
     */
    public function editCompras(Licitacion $licitacion)
    {
        $items = $licitacion->checklistCompras()->orderBy('id')->get();

        return view('licitaciones.checklist_compras', compact('licitacion', 'items'));
    }

    public function storeCompras(Request $request, Licitacion $licitacion)
    {
        $data = $request->validate([
            'descripcion_item' => 'required|string|max:255',
            'fecha_entregado'  => 'nullable|date',
            'entregado_por'    => 'nullable|string|max:255',
            'observaciones'    => 'nullable|string',
        ]);

        LicitacionChecklistCompra::create([
            'licitacion_id'  => $licitacion->id,
            'descripcion_item'=> $data['descripcion_item'],
            'completado'     => !empty($data['fecha_entregado']), // si ya trae fecha, lo marcamos completado
            'fecha_entregado'=> $data['fecha_entregado'] ?? null,
            'entregado_por'  => $data['entregado_por'] ?? null,
            'observaciones'  => $data['observaciones'] ?? null,
        ]);

        // Si quieres, aquí puedes mover current_step a 10
        if ($licitacion->current_step < 10) {
            $licitacion->update(['current_step' => 10]);
        }

        return back()->with('success', 'Ítem de checklist de compras agregado.');
    }

    public function updateCompras(Request $request, Licitacion $licitacion, LicitacionChecklistCompra $item)
    {
        // Garantizar que el item pertenece a la licitación
        abort_unless($item->licitacion_id === $licitacion->id, 404);

        $data = $request->validate([
            'completado'     => 'nullable|boolean',
            'fecha_entregado'=> 'nullable|date',
            'entregado_por'  => 'nullable|string|max:255',
            'observaciones'  => 'nullable|string',
        ]);

        $item->update([
            'completado'     => $data['completado'] ?? false,
            'fecha_entregado'=> $data['fecha_entregado'] ?? null,
            'entregado_por'  => $data['entregado_por'] ?? null,
            'observaciones'  => $data['observaciones'] ?? null,
        ]);

        return back()->with('success', 'Ítem de checklist de compras actualizado.');
    }

    /**
     * PASO 11: Checklist de facturación
     */
    public function editFacturacion(Licitacion $licitacion)
    {
        $facturacion = $licitacion->checklistFacturacion;

        return view('licitaciones.checklist_facturacion', compact('licitacion', 'facturacion'));
    }

    public function storeFacturacion(Request $request, Licitacion $licitacion)
    {
        $data = $request->validate([
            'tiene_factura'  => 'nullable|boolean',
            'fecha_factura'  => 'nullable|date',
            'monto_factura'  => 'nullable|numeric',
            'evidencia'      => 'nullable|file|mimes:pdf,jpg,jpeg,png',
        ]);

        $path = null;
        if ($request->hasFile('evidencia')) {
            $file = $request->file('evidencia');
            $path = $file->store('licitaciones/'.$licitacion->id.'/facturacion', 'public');
        }

        $facturacion = $licitacion->checklistFacturacion ?: new LicitacionChecklistFacturacion([
            'licitacion_id' => $licitacion->id,
        ]);

        $facturacion->tiene_factura = $data['tiene_factura'] ?? false;
        $facturacion->fecha_factura = $data['fecha_factura'] ?? null;
        $facturacion->monto_factura = $data['monto_factura'] ?? null;
        if ($path) {
            $facturacion->evidencia_path = $path;
        }
        $facturacion->save();

        // Avanzar current_step a 11
        if ($licitacion->current_step < 11) {
            $licitacion->update(['current_step' => 11]);
        }

        return redirect()->route('licitaciones.contabilidad.edit', $licitacion)
            ->with('success', 'Checklist de facturación guardado.');
    }

    /**
     * PASO 12: Contabilidad (inversión, gastos, total, etc.)
     */
    public function editContabilidad(Licitacion $licitacion)
    {
        $contabilidad = $licitacion->contabilidad;

        return view('licitaciones.contabilidad', compact('licitacion', 'contabilidad'));
    }

    public function storeContabilidad(Request $request, Licitacion $licitacion)
    {
        $data = $request->validate([
            'monto_inversion_estimado' => 'nullable|numeric',
            'costo_total'              => 'nullable|numeric',
            'detalle_costos'           => 'nullable|array',   // puede venir como array desde el form
            'notas'                    => 'nullable|string',
        ]);

        // Aquí podrías calcular utilidad (ej. costo_total - inversión) o dejarlo libre
        $utilidad = null;
        if (!empty($data['monto_inversion_estimado']) && !empty($data['costo_total'])) {
            $utilidad = $data['costo_total'] - $data['monto_inversion_estimado'];
        }

        $contabilidad = $licitacion->contabilidad ?: new LicitacionContabilidad([
            'licitacion_id' => $licitacion->id,
        ]);

        $contabilidad->monto_inversion_estimado = $data['monto_inversion_estimado'] ?? null;
        $contabilidad->costo_total              = $data['costo_total'] ?? null;
        $contabilidad->detalle_costos           = $data['detalle_costos'] ?? null;
        $contabilidad->utilidad_estimada        = $utilidad;
        $contabilidad->notas                    = $data['notas'] ?? null;
        $contabilidad->save();

        // Aquí ya podrías cerrar formalmente la licitación
        $licitacion->update([
            'current_step' => 12,
            'estatus'      => 'cerrado',
        ]);

        return redirect()->route('licitaciones.show', $licitacion)
            ->with('success', 'Contabilidad registrada y licitación cerrada.');
    }
}
