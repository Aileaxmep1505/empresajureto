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

                $numero = $this->extractItemNumber($row);

                PropuestaComercialItem::create([
                    'propuesta_comercial_id' => $propuesta->id,
                    'sort' => $sort,
                    'partida_numero' => $numero,
                    'subpartida_numero' => $row['subpartida'] ?? null,
                    'descripcion_original' => $row['descripcion'] ?? $row['nombre'] ?? $row['concepto'] ?? 'Sin descripción',
                    'unidad_solicitada' => $row['unidad'] ?? null,
                    'cantidad_minima' => $row['cantidad_minima'] ?? null,
                    'cantidad_maxima' => $row['cantidad_maxima'] ?? null,
                    'cantidad_cotizada' => $row['cantidad_maxima'] ?? $row['cantidad'] ?? $row['cantidad_minima'] ?? 1,
                    'producto_seleccionado_id' => null,
                    'match_score' => null,
                    'costo_unitario' => null,
                    'precio_unitario' => null,
                    'subtotal' => 0,
                    'status' => 'pending',
                    'meta' => [
                        'presentar_muestra' => $row['presentar_muestra'] ?? null,
                        'numero_original' => $row['numero'] ?? null,
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
            'items' => fn ($q) => $q->orderBy('sort'),
            'items.matches.product',
            'items.productoSeleccionado',
            'aiRun',
        ]);

        return view('propuestas_comerciales.show', compact('propuestaComercial'));
    }

    public function recoverMissing(PropuestaComercial $propuestaComercial)
    {
        $propuestaComercial->load(['items', 'aiRun']);

        if (!$propuestaComercial->aiRun) {
            return back()->with('error', 'Esta propuesta no tiene un AI Run asociado.');
        }

        $run = $propuestaComercial->aiRun;
        $itemsResult = is_array($run->items_json) ? $run->items_json : [];
        $sourceItems = $itemsResult['items'] ?? [];

        if (empty($sourceItems)) {
            return back()->with('error', 'El AI Run no tiene partidas para recuperar.');
        }

        $existingNumbers = $propuestaComercial->items
            ->pluck('partida_numero')
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (empty($existingNumbers)) {
            return back()->with('error', 'No hay partidas actuales para comparar.');
        }

        $min = min($existingNumbers);
        $max = max($existingNumbers);
        $expected = range($min, $max);
        $missing = array_values(array_diff($expected, $existingNumbers));

        if (empty($missing)) {
            return back()->with('status', 'No se detectaron partidas faltantes.');
        }

        $sourceByNumber = [];

        foreach ($sourceItems as $row) {
            $number = $this->extractItemNumber($row);

            if (!$number) {
                continue;
            }

            $number = (int) $number;
            $description = $row['descripcion'] ?? $row['nombre'] ?? $row['concepto'] ?? null;

            if (!$description) {
                continue;
            }

            if (!isset($sourceByNumber[$number])) {
                $sourceByNumber[$number] = $row;
                continue;
            }

            $oldDescription = $sourceByNumber[$number]['descripcion'] ?? $sourceByNumber[$number]['nombre'] ?? '';
            if (strlen((string) $description) > strlen((string) $oldDescription)) {
                $sourceByNumber[$number] = $row;
            }
        }

        $created = 0;
        $notFound = [];

        DB::transaction(function () use ($propuestaComercial, $missing, $sourceByNumber, &$created, &$notFound) {
            foreach ($missing as $number) {
                if (!isset($sourceByNumber[$number])) {
                    $notFound[] = $number;
                    continue;
                }

                $row = $sourceByNumber[$number];

                $alreadyExists = PropuestaComercialItem::where('propuesta_comercial_id', $propuestaComercial->id)
                    ->where('partida_numero', $number)
                    ->exists();

                if ($alreadyExists) {
                    continue;
                }

                PropuestaComercialItem::create([
                    'propuesta_comercial_id' => $propuestaComercial->id,
                    'sort' => $number,
                    'partida_numero' => $number,
                    'subpartida_numero' => $row['subpartida'] ?? null,
                    'descripcion_original' => $row['descripcion'] ?? $row['nombre'] ?? $row['concepto'] ?? 'Sin descripción',
                    'unidad_solicitada' => $row['unidad'] ?? null,
                    'cantidad_minima' => $row['cantidad_minima'] ?? null,
                    'cantidad_maxima' => $row['cantidad_maxima'] ?? null,
                    'cantidad_cotizada' => $row['cantidad_maxima'] ?? $row['cantidad'] ?? $row['cantidad_minima'] ?? 1,
                    'producto_seleccionado_id' => null,
                    'match_score' => null,
                    'costo_unitario' => null,
                    'precio_unitario' => null,
                    'subtotal' => 0,
                    'status' => 'pending',
                    'meta' => [
                        'recuperada' => true,
                        'presentar_muestra' => $row['presentar_muestra'] ?? null,
                        'numero_original' => $row['numero'] ?? null,
                    ],
                ]);

                $created++;
            }

            $items = PropuestaComercialItem::where('propuesta_comercial_id', $propuestaComercial->id)
                ->orderByRaw('CAST(partida_numero AS UNSIGNED) ASC')
                ->orderBy('id')
                ->get();

            $sort = 1;

            foreach ($items as $item) {
                $item->update(['sort' => $sort]);
                $sort++;
            }
        });

        $message = "Se recuperaron {$created} partidas faltantes.";

        if (!empty($notFound)) {
            $message .= ' No se encontraron en el JSON actual: ' . implode(', ', $notFound) . '.';
        }

        return back()->with($created > 0 ? 'status' : 'error', $message);
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

    protected function extractItemNumber(array $row): ?int
    {
        $value = $row['partida']
            ?? $row['numero']
            ?? $row['num_prog']
            ?? $row['subpartida']
            ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        $clean = preg_replace('/[^\d]/', '', (string) $value);

        return $clean !== '' ? (int) $clean : null;
    }

    protected function getMissingNumbers(PropuestaComercial $propuestaComercial): array
    {
        $numbers = $propuestaComercial->items
            ->pluck('partida_numero')
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (count($numbers) < 2) {
            return [];
        }

        return array_values(array_diff(range(min($numbers), max($numbers)), $numbers));
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