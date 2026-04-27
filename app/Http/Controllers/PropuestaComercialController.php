<?php

namespace App\Http\Controllers;

use App\Models\DocumentAiRun;
use App\Models\PropuestaComercial;
use App\Models\PropuestaComercialItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropuestaComercialController extends Controller
{
    public function index(Request $request)
    {
        $query = PropuestaComercial::query();

        if ($request->filled('q')) {
            $q = trim((string) $request->q);

            $query->where(function ($sub) use ($q) {
                $sub->where('titulo', 'like', "%{$q}%")
                    ->orWhere('cliente', 'like', "%{$q}%")
                    ->orWhere('folio', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $propuestas = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('propuestas_comerciales.index', compact('propuestas'));
    }

    public function create()
    {
        return view('propuestas_comerciales.create');
    }

    public function storeFromRunManual(Request $request)
    {
        $data = $request->validate([
            'document_ai_run_id' => ['required', 'integer', 'exists:document_ai_runs,id'],
            'titulo' => ['nullable', 'string', 'max:255'],
            'cliente' => ['nullable', 'string', 'max:255'],
            'folio' => ['nullable', 'string', 'max:255'],
            'porcentaje_utilidad' => ['nullable', 'numeric', 'min:0'],
            'porcentaje_descuento' => ['nullable', 'numeric', 'min:0'],
            'porcentaje_impuesto' => ['nullable', 'numeric', 'min:0'],
        ]);

        $run = DocumentAiRun::findOrFail($data['document_ai_run_id']);
        $structured = is_array($run->structured_json) ? $run->structured_json : [];
        $itemsResult = is_array($run->items_json) ? $run->items_json : [];
        $items = $itemsResult['items'] ?? [];

        if (empty($items)) {
            return back()->with('error', 'Este análisis no tiene items_json válido o no se extrajeron partidas.');
        }

        $propuesta = null;

        DB::transaction(function () use ($data, $run, $structured, $items, &$propuesta) {
            $propuesta = PropuestaComercial::create([
                'licitacion_pdf_id' => $run->licitacion_pdf_id,
                'document_ai_run_id' => $run->id,
                'titulo' => $data['titulo'] ?: ($structured['objeto'] ?? ('Propuesta comercial #' . $run->id)),
                'folio' => $data['folio'] ?: ($structured['numero_procedimiento'] ?? null),
                'cliente' => $data['cliente'] ?: ($structured['dependencia'] ?? null),
                'porcentaje_utilidad' => $data['porcentaje_utilidad'] ?? 0,
                'porcentaje_descuento' => $data['porcentaje_descuento'] ?? 0,
                'porcentaje_impuesto' => $data['porcentaje_impuesto'] ?? 16,
                'subtotal' => 0,
                'descuento_total' => 0,
                'impuesto_total' => 0,
                'total' => 0,
                'status' => 'draft',
                'meta' => [
                    'tipo_procedimiento' => $structured['tipo_procedimiento'] ?? null,
                    'moneda' => $structured['moneda'] ?? null,
                    'anexos' => $structured['anexos'] ?? [],
                    'fechas_clave' => $structured['fechas_clave'] ?? [],
                    'penalizaciones' => $structured['penalizaciones'] ?? [],
                    'resumen' => $structured['resumen'] ?? null,
                    'fuentes' => $structured['fuentes'] ?? [],
                    'items_count' => count($items),
                ],
            ]);

            $sort = 0;

            foreach ($items as $row) {
                $sort++;

                PropuestaComercialItem::create([
                    'propuesta_comercial_id' => $propuesta->id,
                    'sort' => $sort,
                    'partida_numero' => $row['partida'] ?? 1,
                    'subpartida_numero' => $row['subpartida'] ?? null,
                    'descripcion_original' => $row['descripcion'] ?? 'Sin descripción',
                    'unidad_solicitada' => $row['unidad'] ?? null,
                    'cantidad_minima' => $row['cantidad_minima'] ?? null,
                    'cantidad_maxima' => $row['cantidad_maxima'] ?? null,
                    'cantidad_cotizada' => $row['cantidad_maxima'] ?? $row['cantidad_minima'] ?? 1,
                    'producto_seleccionado_id' => null,
                    'match_score' => null,
                    'costo_unitario' => null,
                    'precio_unitario' => null,
                    'subtotal' => 0,
                    'status' => 'pending',
                    'meta' => [
                        'presentar_muestra' => $row['presentar_muestra'] ?? null,
                    ],
                ]);
            }
        });

        return redirect()
            ->route('propuestas-comerciales.show', $propuesta)
            ->with('status', 'Propuesta comercial creada correctamente con partidas completas.');
    }

public function show(PropuestaComercial $propuestaComercial)
{
    $propuestaComercial->load([
        'items.matches.product',
        'items.externalMatches',
        'items.productoSeleccionado',
        'aiRun',
    ]);

    return view('propuestas_comerciales.show', compact('propuestaComercial'));
}
    public function updatePricing(Request $request, PropuestaComercial $propuestaComercial)
    {
        $data = $request->validate([
            'porcentaje_utilidad' => ['required', 'numeric', 'min:0'],
            'porcentaje_descuento' => ['nullable', 'numeric', 'min:0'],
            'porcentaje_impuesto' => ['nullable', 'numeric', 'min:0'],
        ]);

        $propuestaComercial->update([
            'porcentaje_utilidad' => $data['porcentaje_utilidad'],
            'porcentaje_descuento' => $data['porcentaje_descuento'] ?? 0,
            'porcentaje_impuesto' => $data['porcentaje_impuesto'] ?? 16,
        ]);

        $this->recalculateTotals($propuestaComercial);

        return back()->with('status', 'Parámetros de precios actualizados.');
    }

    protected function recalculateTotals(PropuestaComercial $propuestaComercial): void
    {
        $propuestaComercial->loadMissing('items');

        $subtotal = (float) $propuestaComercial->items->sum(function ($item) {
            return (float) $item->subtotal;
        });

        $descuentoTotal = round($subtotal * ((float) $propuestaComercial->porcentaje_descuento / 100), 2);
        $base = max($subtotal - $descuentoTotal, 0);
        $impuestoTotal = round($base * ((float) $propuestaComercial->porcentaje_impuesto / 100), 2);
        $total = round($base + $impuestoTotal, 2);

        $status = $propuestaComercial->items->contains(fn ($item) => $item->status === 'priced')
            ? 'priced'
            : $propuestaComercial->status;

        $propuestaComercial->update([
            'subtotal' => round($subtotal, 2),
            'descuento_total' => $descuentoTotal,
            'impuesto_total' => $impuestoTotal,
            'total' => $total,
            'status' => $status,
        ]);
    }
}