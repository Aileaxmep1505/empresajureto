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

        $propuestas = $query->latest()->paginate(20)->withQueryString();

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
            return back()->with('error', 'Este análisis no tiene partidas válidas para crear la propuesta.');
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
                        'numero_original' => $row['numero'] ?? $numero,
                    ],
                ]);
            }
        });

        return redirect()
            ->route('propuestas-comerciales.show', $propuesta)
            ->with('status', 'Propuesta comercial creada correctamente.');
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

        $fullText = data_get($run->result_json, 'full_text', '');

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

        $missing = array_values(array_diff(
            range(min($existingNumbers), max($existingNumbers)),
            $existingNumbers
        ));

        if (empty($missing)) {
            return back()->with('status', 'No se detectaron partidas faltantes.');
        }

        $sourceByNumber = [];

        foreach ($sourceItems as $row) {
            $number = $this->extractItemNumber($row);

            if (!$number) {
                continue;
            }

            $description = $row['descripcion'] ?? $row['nombre'] ?? $row['concepto'] ?? null;

            if (!$description) {
                continue;
            }

            if (!isset($sourceByNumber[(int) $number])) {
                $sourceByNumber[(int) $number] = $row;
                continue;
            }

            $oldDescription = $sourceByNumber[(int) $number]['descripcion']
                ?? $sourceByNumber[(int) $number]['nombre']
                ?? '';

            if (strlen((string) $description) > strlen((string) $oldDescription)) {
                $sourceByNumber[(int) $number] = $row;
            }
        }

        $created = 0;
        $notFound = [];

        DB::transaction(function () use ($propuestaComercial, $missing, $sourceByNumber, $fullText, &$created, &$notFound) {
            foreach ($missing as $number) {
                $row = $sourceByNumber[(int) $number] ?? $this->recoverItemFromText($fullText, (int) $number);

                if (!$row) {
                    $notFound[] = $number;
                    continue;
                }

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
                        'recuperada_desde_texto' => !isset($sourceByNumber[(int) $number]),
                        'presentar_muestra' => $row['presentar_muestra'] ?? null,
                        'numero_original' => $row['numero'] ?? $number,
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
            $message .= ' No se pudieron reconstruir: ' . implode(', ', $notFound) . '.';
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

    protected function recoverItemFromText(?string $fullText, int $number): ?array
    {
        $text = trim((string) $fullText);

        if ($text === '') {
            return null;
        }

        $lines = preg_split('/\R/u', $text);
        $lines = collect($lines)
            ->map(fn ($line) => trim(preg_replace('/\s+/u', ' ', (string) $line)))
            ->filter()
            ->values()
            ->all();

        $startIndex = null;

        foreach ($lines as $index => $line) {
            if (preg_match('/^' . preg_quote((string) $number, '/') . '$/u', $line)) {
                $startIndex = $index;
                break;
            }
        }

        if ($startIndex === null) {
            return null;
        }

        $chunk = [];

        for ($i = $startIndex; $i < count($lines); $i++) {
            $line = $lines[$i];

            if ($i > $startIndex && preg_match('/^\d{1,5}$/u', $line)) {
                break;
            }

            $chunk[] = $line;

            if (count($chunk) >= 18) {
                break;
            }
        }

        if (count($chunk) < 2) {
            return null;
        }

        $cantidadMinima = null;
        $cantidadMaxima = null;
        $cantidad = null;
        $unidad = null;
        $descripcionParts = [];

        $unitWords = [
            'PIEZA', 'PIEZAS', 'PZA', 'PZAS', 'CAJA', 'PAQUETE', 'BOLSA',
            'ROLLO', 'SERVICIO', 'JUEGO', 'PAR', 'LOTE', 'BOTE', 'FRASCO',
            'HOJA', 'BLOCK', 'KG', 'KILO', 'LITRO', 'METRO'
        ];

        $numbers = [];

        foreach (array_slice($chunk, 1) as $part) {
            $clean = trim($part);

            if ($clean === '') {
                continue;
            }

            $numberValue = $this->normalizeRecoveredNumber($clean);

            if ($numberValue !== null && preg_match('/^[\d,\.\s]+$/u', $clean)) {
                $numbers[] = $numberValue;
                continue;
            }

            if ($unidad === null && in_array(mb_strtoupper($clean), $unitWords, true)) {
                $unidad = $clean;
                continue;
            }

            if (mb_strlen($clean) > 4) {
                $descripcionParts[] = $clean;
            }
        }

        if (count($numbers) >= 2) {
            $cantidadMinima = $numbers[0];
            $cantidadMaxima = $numbers[1];
        } elseif (count($numbers) === 1) {
            $cantidad = $numbers[0];
        }

        $descripcion = trim(implode(' ', $descripcionParts));

        if ($descripcion === '' || mb_strlen($descripcion) < 8) {
            return null;
        }

        return [
            'numero' => $number,
            'partida' => $number,
            'subpartida' => null,
            'descripcion' => $descripcion,
            'nombre' => $descripcion,
            'unidad' => $unidad,
            'cantidad' => $cantidad,
            'cantidad_minima' => $cantidadMinima,
            'cantidad_maxima' => $cantidadMaxima,
            'presentar_muestra' => null,
        ];
    }

    protected function normalizeRecoveredNumber($value): ?int
    {
        $clean = trim((string) $value);
        $clean = str_replace([',', ' '], '', $clean);

        if (!preg_match('/^\d+$/', $clean)) {
            return null;
        }

        return (int) $clean;
    }

    protected function recalculateTotals(PropuestaComercial $propuestaComercial): void
    {
        $propuestaComercial->loadMissing('items');

        $subtotal = (float) $propuestaComercial->items->sum(fn ($item) => (float) $item->subtotal);

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