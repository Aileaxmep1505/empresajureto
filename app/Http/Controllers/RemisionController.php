<?php

namespace App\Http\Controllers;

use App\Models\Adjudicacion;
use App\Models\Remision;
use App\Models\RemisionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class RemisionController extends Controller
{
    public function store(Request $request, Adjudicacion $adjudicacion)
    {
        $adjudicacion->load('items');
        $remision = null;

        DB::transaction(function () use ($adjudicacion, $request, &$remision) {
            $remision = Remision::create([
                'adjudicacion_id' => $adjudicacion->id,
                'fecha' => $request->date('fecha') ?: now(),
                'status' => 'borrador',
                'recibe_nombre' => $request->input('recibe_nombre'),
                'observaciones' => $request->input('observaciones'),
            ]);

            $remision->update(['folio' => 'REM-' . str_pad((string) $remision->id, 6, '0', STR_PAD_LEFT)]);

            $sort = 0;
            foreach ($adjudicacion->items as $it) {
                $sort++;
                RemisionItem::create([
                    'remision_id' => $remision->id,
                    'adjudicacion_item_id' => $it->id,
                    'sort' => $sort,
                    'descripcion' => $it->descripcion,
                    'unidad' => $it->unidad,
                    'cantidad' => $it->cantidad,
                    'precio_unitario' => $it->precio_unitario,
                    'subtotal' => $it->subtotal,
                ]);
            }
        });

        if (in_array($adjudicacion->status, ['borrador', 'confirmada'], true)) {
            $adjudicacion->update(['status' => 'remisionada']);
        }

        return redirect()
            ->route('remisiones.show', $remision)
            ->with('status', 'Remisión creada con los renglones de la adjudicación.');
    }

    public function show(Remision $remision)
    {
        $remision->load(['items', 'adjudicacion.client', 'adjudicacion.propuesta']);

        return view('remisiones.show', compact('remision'));
    }

    public function update(Request $request, Remision $remision)
    {
        $data = $request->validate([
            'folio' => ['nullable', 'string', 'max:255'],
            'fecha' => ['nullable', 'date'],
            'status' => ['nullable', 'in:borrador,emitida,entregada,cancelada'],
            'recibe_nombre' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.descripcion' => ['nullable', 'string'],
            'items.*.unidad' => ['nullable', 'string', 'max:50'],
            'items.*.cantidad' => ['nullable', 'numeric', 'min:0'],
            'items.*.precio_unitario' => ['nullable', 'numeric', 'min:0'],
        ]);

        $remision->update([
            'folio' => $data['folio'] ?? $remision->folio,
            'fecha' => $data['fecha'] ?? $remision->fecha,
            'status' => $data['status'] ?? $remision->status,
            'recibe_nombre' => $data['recibe_nombre'] ?? $remision->recibe_nombre,
            'observaciones' => $data['observaciones'] ?? $remision->observaciones,
        ]);

        if (!empty($data['items'])) {
            foreach ($data['items'] as $row) {
                if (empty($row['id'])) {
                    continue;
                }

                $ri = RemisionItem::where('remision_id', $remision->id)->find($row['id']);
                if (!$ri) {
                    continue;
                }

                $cant = isset($row['cantidad']) ? (float) $row['cantidad'] : (float) $ri->cantidad;
                $precio = isset($row['precio_unitario']) ? (float) $row['precio_unitario'] : (float) $ri->precio_unitario;

                $ri->update([
                    'descripcion' => $row['descripcion'] ?? $ri->descripcion,
                    'unidad' => $row['unidad'] ?? $ri->unidad,
                    'cantidad' => $cant,
                    'precio_unitario' => $precio,
                    'subtotal' => round($cant * $precio, 2),
                ]);
            }
        }

        return back()->with('status', 'Remisión actualizada.');
    }

    public function destroy(Remision $remision)
    {
        $adjId = $remision->adjudicacion_id;
        $remision->delete();

        return redirect()
            ->route('adjudicaciones.show', $adjId)
            ->with('status', 'Remisión eliminada.');
    }

    public function pdf(Remision $remision)
    {
        $remision->load(['items', 'adjudicacion.client', 'adjudicacion.propuesta']);

        $pdf = Pdf::loadView('remisiones.pdf', compact('remision'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream('Remision-' . ($remision->folio ?: $remision->id) . '.pdf');
    }
}