<?php

namespace App\Http\Controllers;

use App\Models\DocumentAiRun;
use App\Models\Product;
use App\Models\PropuestaComercial;
use App\Models\PropuestaComercialItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropuestaComercialController extends Controller
{
    /**
     * Largo máximo para "unidad_solicitada" (debe coincidir con tu VARCHAR de BD).
     * Ajusta este número al tamaño real de tu columna.
     */
    private const UNIDAD_MAX_LEN = 50;

    /**
     * Unidades de medida comunes (palabra única / muy corta).
     * Ayuda a detectar cuando la IA cruzó descripción ↔ unidad.
     */
    private const UNIDADES_COMUNES = [
        'PIEZA', 'PIEZAS', 'PZA', 'PZ', 'PZS',
        'CAJA', 'CAJAS',
        'KG', 'KGS', 'KILOGRAMO', 'KILOGRAMOS', 'GR', 'GRAMO', 'GRAMOS',
        'LT', 'LTS', 'LITRO', 'LITROS', 'ML', 'MILILITRO',
        'M', 'MT', 'METRO', 'METROS', 'CM', 'MM',
        'M2', 'M3',
        'PAQUETE', 'PAQUETES', 'PAQ',
        'SERVICIO', 'SERVICIOS',
        'UNIDAD', 'UNIDADES', 'UND', 'UN',
        'ROLLO', 'ROLLOS',
        'TONELADA', 'TONELADAS', 'TON',
        'JUEGO', 'JUEGOS', 'JGO',
        'DOCENA', 'DOCENAS', 'DOC',
        'PAR', 'PARES',
        'LOTE', 'LOTES',
        'FRASCO', 'FRASCOS',
        'BOLSA', 'BOLSAS',
        'BOTELLA', 'BOTELLAS',
        'GALON', 'GALÓN', 'GALONES',
    ];

    public function index(Request $request)
    {
        $query = PropuestaComercial::query()->with('items');

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

        $allPropuestasComerciales = PropuestaComercial::query()
            ->with('items')
            ->latest()
            ->get();

        return view('propuestas_comerciales.index', compact(
            'propuestas',
            'allPropuestasComerciales'
        ));
    }

    public function create()
    {
        return view('propuestas_comerciales.create');
    }

    public function destroy(PropuestaComercial $propuestaComercial)
    {
        DB::transaction(function () use ($propuestaComercial) {
            $propuestaComercial->items()->delete();
            $propuestaComercial->delete();
        });

        return redirect()
            ->route('propuestas-comerciales.index')
            ->with('status', 'Cotización eliminada correctamente.');
    }

    /**
     * Normaliza descripción y unidad para evitar crashes por:
     * - IA que cruza los campos.
     * - IA que devuelve descripción larga como unidad.
     * - Columnas truncadas en BD.
     *
     * Devuelve [descripcion, unidad].
     */
    private function normalizarDescripcionUnidad(?string $descripcion, ?string $unidad): array
    {
        $descripcion = trim((string) $descripcion);
        $unidad = trim((string) $unidad);

        $descLen = mb_strlen($descripcion);
        $uniLen = mb_strlen($unidad);

        $unidadPareceDescripcion =
            $uniLen > self::UNIDAD_MAX_LEN
            || (
                in_array(mb_strtoupper($descripcion), self::UNIDADES_COMUNES, true)
                && $uniLen > $descLen
            );

        if ($unidadPareceDescripcion) {
            [$descripcion, $unidad] = [$unidad, $descripcion];
        }

        $unidad = mb_substr($unidad, 0, self::UNIDAD_MAX_LEN);

        if ($unidad === '') {
            $unidad = 'PIEZA';
        }

        if ($descripcion === '') {
            $descripcion = 'Sin descripción';
        }

        return [$descripcion, $unidad];
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

        $items = $itemsResult['items']
            ?? $structured['items']
            ?? $structured['partidas']
            ?? [];

        if (empty($items)) {
            $message = 'Este análisis no tiene partidas válidas. Verifica que el PDF ya terminó de procesarse correctamente.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->with('error', $message);
        }

        $propuesta = null;

        DB::transaction(function () use ($data, $run, $structured, $items, &$propuesta) {
            $propuesta = PropuestaComercial::create([
                'licitacion_pdf_id' => $run->licitacion_pdf_id,
                'document_ai_run_id' => $run->id,

                'titulo' => $data['titulo']
                    ?: (
                        $structured['objeto']
                        ?? $structured['titulo']
                        ?? ('Propuesta comercial #' . $run->id)
                    ),

                'folio' => $data['folio']
                    ?: (
                        $structured['numero_procedimiento']
                        ?? $structured['folio']
                        ?? null
                    ),

                'cliente' => $data['cliente']
                    ?: (
                        $structured['dependencia']
                        ?? $structured['cliente']
                        ?? $structured['razon_social']
                        ?? null
                    ),

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
                    'created_from_run_id' => $run->id,
                ],
            ]);

            $sort = 0;

            foreach ($items as $row) {
                $sort++;

                $descripcionRaw = $row['descripcion']
                    ?? $row['description']
                    ?? $row['producto']
                    ?? $row['product']
                    ?? $row['nombre']
                    ?? 'Sin descripción';

                $unidadRaw = $row['unidad']
                    ?? $row['unit']
                    ?? $row['unidad_solicitada']
                    ?? null;

                [$descripcion, $unidad] = $this->normalizarDescripcionUnidad($descripcionRaw, $unidadRaw);

                $cantidadMinima = $row['cantidad_minima']
                    ?? $row['min_quantity']
                    ?? $row['cantidad']
                    ?? null;

                $cantidadMaxima = $row['cantidad_maxima']
                    ?? $row['max_quantity']
                    ?? $row['cantidad']
                    ?? null;

                $cantidadCotizada = $row['cantidad_cotizada']
                    ?? $cantidadMaxima
                    ?? $cantidadMinima
                    ?? 1;

                PropuestaComercialItem::create([
                    'propuesta_comercial_id' => $propuesta->id,
                    'sort' => $sort,
                    'partida_numero' => $row['partida'] ?? $row['partida_numero'] ?? $sort,
                    'subpartida_numero' => $row['subpartida'] ?? $row['subpartida_numero'] ?? null,

                    'descripcion_original' => $descripcion,
                    'unidad_solicitada' => $unidad,

                    'cantidad_minima' => $cantidadMinima,
                    'cantidad_maxima' => $cantidadMaxima,
                    'cantidad_cotizada' => $cantidadCotizada,

                    'producto_seleccionado_id' => null,
                    'match_score' => null,

                    'costo_unitario' => null,
                    'precio_unitario' => null,
                    'subtotal' => 0,

                    'status' => 'pending',

                    'meta' => [
                        'presentar_muestra' => $row['presentar_muestra'] ?? null,
                        'created_from_run_id' => $run->id,
                        'raw' => $row,
                        'campos_corregidos' => (
                            mb_strtoupper(trim((string) $descripcionRaw)) !== mb_strtoupper($descripcion)
                        ),
                    ],
                ]);
            }
        });

        $redirectUrl = route('propuestas-comerciales.show', [
            'propuestaComercial' => $propuesta->id,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'propuesta_id' => $propuesta->id,
                'redirect_url' => $redirectUrl,
                'message' => 'Propuesta comercial creada correctamente con partidas completas.',
            ]);
        }

        return redirect()
            ->to($redirectUrl)
            ->with('status', 'Propuesta comercial creada correctamente con partidas completas.');
    }

    public function show(PropuestaComercial $propuestaComercial)
    {
        $propuestaComercial->load([
            'items.matches.product',
            'items.externalMatches',
            'items.productoSeleccionado',

            // Relaciones para preguntas de junta de aclaraciones
            'items.aclaracionPreguntas',
            'aclaracionPreguntas.item',

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


    public function ajaxDeleteItem(PropuestaComercialItem $item)
    {
        $propuestaComercial = PropuestaComercial::findOrFail($item->propuesta_comercial_id);

        DB::transaction(function () use ($item, $propuestaComercial) {
            if (method_exists($item, 'matches')) {
                $item->matches()->delete();
            }

            if (method_exists($item, 'externalMatches')) {
                $item->externalMatches()->delete();
            }

            if (method_exists($item, 'aclaracionPreguntas')) {
                $item->aclaracionPreguntas()->delete();
            }

            $item->delete();

            $propuestaComercial->items()
                ->orderBy('sort')
                ->orderBy('id')
                ->get()
                ->values()
                ->each(function ($partida, $index) {
                    $partida->update([
                        'sort' => $index + 1,
                    ]);
                });

            $this->recalculateTotals($propuestaComercial->fresh());
        });

        return response()->json([
            'ok' => true,
            'message' => 'Partida eliminada correctamente.',
        ]);
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