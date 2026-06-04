<?php

namespace App\Http\Controllers;

use App\Models\DocumentAiRun;
use App\Models\Product;
use App\Models\PropuestaComercial;
use App\Models\PropuestaComercialItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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


    public function ajaxSamplesItem(PropuestaComercialItem $item)
    {
        $item->loadMissing('matches.product', 'productoSeleccionado');

        $neededQty = (float) ($item->cantidad_cotizada ?: $item->cantidad_maxima ?: $item->cantidad_minima ?: 1);
        $searchText = trim((string) $item->descripcion_original);
        $searchWords = collect(preg_split('/\s+/u', mb_strtolower($searchText)))
            ->filter(fn ($word) => mb_strlen($word) >= 3)
            ->values()
            ->all();

        $candidates = [];

        if (Schema::hasTable('catalog_items')) {
            $columns = Schema::getColumnListing('catalog_items');
            $nameColumns = ['name', 'product_name', 'nombre', 'titulo', 'title', 'descripcion', 'description'];
            $skuColumns = ['sku', 'codigo', 'code', 'clave', 'clave_producto', 'item_code'];
            $brandColumns = ['brand', 'marca', 'fabricante', 'manufacturer'];
            $modelColumns = ['model', 'modelo', 'reference', 'referencia'];
            $categoryColumns = ['category', 'categoria', 'familia', 'linea', 'subcategoria'];
            $unitColumns = ['unit', 'unidad', 'unidad_medida', 'uom'];
            $imageColumns = ['image_url', 'imagen_url', 'photo_url', 'foto_url', 'thumbnail_url', 'picture_url', 'image', 'imagen', 'photo', 'foto', 'thumbnail'];
            $stockColumns = ['net_available', 'available', 'stock', 'existencia', 'existencias', 'cantidad', 'qty', 'inventory'];
            $reservedColumns = ['reserved', 'apartado', 'reservado', 'committed', 'comprometido'];
            $costColumns = ['cost', 'costo', 'purchase_price', 'precio_compra', 'costo_unitario'];
            $priceColumns = ['price', 'precio', 'sale_price', 'precio_venta', 'precio_unitario'];
            $descriptionColumns = ['description', 'descripcion', 'long_description', 'descripcion_larga', 'notes', 'notas'];

            $pick = function ($row, array $possible) use ($columns) {
                foreach ($possible as $column) {
                    if (in_array($column, $columns, true) && isset($row->{$column}) && $row->{$column} !== null && $row->{$column} !== '') {
                        return $row->{$column};
                    }
                }

                return null;
            };

            $normalizeImage = function ($value) {
                $value = trim((string) $value);

                if ($value === '') {
                    return null;
                }

                if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/')) {
                    return $value;
                }

                if (str_starts_with($value, 'storage/')) {
                    return asset($value);
                }

                if (str_starts_with($value, 'catalog/') || str_starts_with($value, 'products/') || str_starts_with($value, 'images/')) {
                    return asset('storage/' . $value);
                }

                return asset('storage/' . ltrim($value, '/'));
            };

            $query = DB::table('catalog_items');

            $query->where(function ($sub) use ($searchWords, $columns, $nameColumns, $skuColumns, $brandColumns) {
                $searchable = array_values(array_intersect(array_merge($nameColumns, $skuColumns, $brandColumns), $columns));

                if (empty($searchable) || empty($searchWords)) {
                    return;
                }

                foreach ($searchWords as $word) {
                    foreach ($searchable as $column) {
                        $sub->orWhere($column, 'like', '%' . $word . '%');
                    }
                }
            });

            $rows = $query->limit(80)->get();

            if ($rows->isEmpty()) {
                $rows = DB::table('catalog_items')->limit(80)->get();
            }

            $candidates = $rows->map(function ($row) use ($columns, $pick, $normalizeImage, $nameColumns, $skuColumns, $brandColumns, $modelColumns, $categoryColumns, $unitColumns, $imageColumns, $stockColumns, $reservedColumns, $costColumns, $priceColumns, $descriptionColumns, $neededQty, $searchText) {
                $name = (string) ($pick($row, $nameColumns) ?: 'Producto sin nombre');
                $sku = (string) ($pick($row, $skuColumns) ?: '');
                $brand = (string) ($pick($row, $brandColumns) ?: '');
                $model = (string) ($pick($row, $modelColumns) ?: '');
                $category = (string) ($pick($row, $categoryColumns) ?: '');
                $unit = (string) ($pick($row, $unitColumns) ?: '');
                $imageUrl = $normalizeImage($pick($row, $imageColumns));
                $stock = (float) ($pick($row, $stockColumns) ?: 0);
                $reserved = (float) ($pick($row, $reservedColumns) ?: 0);
                $cost = (float) ($pick($row, $costColumns) ?: 0);
                $price = (float) ($pick($row, $priceColumns) ?: 0);
                $description = (string) ($pick($row, $descriptionColumns) ?: '');

                similar_text(mb_strtolower($searchText), mb_strtolower($name . ' ' . $sku . ' ' . $brand . ' ' . $model . ' ' . $description), $pct);

                $netAvailable = max($stock - $reserved, 0);
                $toBuy = max($neededQty - $netAvailable, 0);

                $hidden = ['id', 'created_at', 'updated_at', 'deleted_at'];
                $details = [];

                foreach ($columns as $column) {
                    if (in_array($column, $hidden, true)) {
                        continue;
                    }

                    $value = $row->{$column} ?? null;

                    if ($value === null || $value === '') {
                        continue;
                    }

                    if (is_string($value) && mb_strlen($value) > 90) {
                        $value = mb_substr($value, 0, 90) . '...';
                    }

                    $details[] = [
                        'label' => str_replace('_', ' ', mb_strtoupper($column)),
                        'value' => (string) $value,
                    ];
                }

                return [
                    'id' => $row->id ?? null,
                    'name' => $name,
                    'sku' => $sku,
                    'brand' => $brand,
                    'model' => $model,
                    'category' => $category,
                    'unit' => $unit,
                    'description' => $description,
                    'image_url' => $imageUrl,
                    'similarity_pct' => round($pct, 2),
                    'stock_field' => $stock,
                    'net_available' => $netAvailable,
                    'reserved' => $reserved,
                    'needed_qty' => $neededQty,
                    'to_buy' => $toBuy,
                    'cost' => $cost,
                    'price' => $price,
                    'locations' => [],
                    'location_summary' => '',
                    'details' => $details,
                ];
            })
                ->sortByDesc('similarity_pct')
                ->take(25)
                ->values()
                ->all();
        } else {
            $products = Product::query()
                ->where(function ($q) use ($searchWords) {
                    foreach ($searchWords as $word) {
                        $q->orWhere('name', 'like', '%' . $word . '%')
                            ->orWhere('sku', 'like', '%' . $word . '%')
                            ->orWhere('brand', 'like', '%' . $word . '%');
                    }
                })
                ->limit(25)
                ->get();

            $candidates = $products->map(function ($product) use ($neededQty, $searchText) {
                similar_text(mb_strtolower($searchText), mb_strtolower((string) $product->name), $pct);

                $stock = (float) ($product->stock ?? 0);
                $reserved = (float) ($product->reserved ?? 0);
                $netAvailable = max($stock - $reserved, 0);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'brand' => $product->brand,
                    'unit' => $product->unit ?? 'pieza',
                    'description' => $product->description ?? '',
                    'image_url' => $product->image_url ?? $product->photo_url ?? null,
                    'similarity_pct' => round($pct, 2),
                    'stock_field' => $stock,
                    'net_available' => $netAvailable,
                    'reserved' => $reserved,
                    'needed_qty' => $neededQty,
                    'to_buy' => max($neededQty - $netAvailable, 0),
                    'cost' => (float) ($product->cost ?? $product->costo ?? 0),
                    'price' => (float) ($product->price ?? $product->precio ?? 0),
                    'locations' => [],
                    'details' => [],
                ];
            })->sortByDesc('similarity_pct')->values()->all();
        }

        return response()->json([
            'ok' => true,
            'needed_qty' => $neededQty,
            'candidates' => $candidates,
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