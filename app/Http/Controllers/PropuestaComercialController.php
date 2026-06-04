<?php

namespace App\Http\Controllers;

use App\Models\DocumentAiRun;
use App\Models\Product;
use App\Models\PropuestaComercial;
use App\Models\PropuestaComercialItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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


    private function asMetaArray($meta): array
    {
        if (is_array($meta)) {
            return $meta;
        }

        if (is_object($meta)) {
            return json_decode(json_encode($meta), true) ?: [];
        }

        if (is_string($meta) && trim($meta) !== '') {
            $decoded = json_decode($meta, true);
            return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function normalizeCatalogPhotoUrl($value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = str_replace('\\', '/', $value);

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, 'data:image')) {
            return $value;
        }

        if (str_starts_with($value, '/')) {
            return $value;
        }

        $clean = ltrim($value, '/');
        $clean = preg_replace('#^public/#', '', $clean);
        $clean = preg_replace('#^storage/app/public/#', '', $clean);
        $clean = preg_replace('#^app/public/#', '', $clean);

        $candidates = array_values(array_unique(array_filter([
            $clean,
            'catalog_items/' . basename($clean),
            'products/' . basename($clean),
            'images/' . basename($clean),
            'uploads/' . basename($clean),
        ])));

        foreach ($candidates as $candidate) {
            if (Storage::disk('public')->exists($candidate)) {
                return Storage::url($candidate);
            }
        }

        foreach ($candidates as $candidate) {
            if (file_exists(public_path($candidate))) {
                return asset($candidate);
            }

            if (file_exists(public_path('storage/' . $candidate))) {
                return asset('storage/' . $candidate);
            }
        }

        if (str_starts_with($clean, 'storage/')) {
            return asset($clean);
        }

        return asset('storage/' . $clean);
    }

    private function splitSearchWords(string $text): array
    {
        $stopWords = [
            'para', 'con', 'del', 'de', 'la', 'el', 'los', 'las', 'una', 'uno', 'unos', 'unas',
            'color', 'caja', 'pieza', 'piezas', 'paquete', 'paquetes', 'marca', 'presentacion', 'presentación',
            'solicitado', 'solicitada', 'tipo', 'modelo', 'medida', 'mediano', 'grande', 'chico', 'azul', 'rojo', 'negro', 'blanco'
        ];

        return collect(preg_split('/[^\pL\pN]+/u', mb_strtolower($text)))
            ->map(fn ($word) => trim($word))
            ->filter(fn ($word) => mb_strlen($word) >= 2 && !in_array($word, $stopWords, true))
            ->unique()
            ->take(12)
            ->values()
            ->all();
    }

    private function catalogColumns(): array
    {
        return Schema::hasTable('catalog_items') ? Schema::getColumnListing('catalog_items') : [];
    }

    private function pickCatalogValue($row, array $columns, array $possible)
    {
        foreach ($possible as $column) {
            if (in_array($column, $columns, true) && isset($row->{$column}) && $row->{$column} !== null && $row->{$column} !== '') {
                return $row->{$column};
            }
        }

        return null;
    }

    private function productColumns(): array
    {
        return Schema::hasTable('products') ? Schema::getColumnListing('products') : [];
    }

    private function pickProductValue($row, array $columns, array $possible)
    {
        foreach ($possible as $column) {
            if (in_array($column, $columns, true) && isset($row->{$column}) && $row->{$column} !== null && $row->{$column} !== '') {
                return $row->{$column};
            }
        }

        return null;
    }

    private function normalizeSearchText(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'u', 'n'], $text);
        $text = preg_replace('/[^\pL\pN]+/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }

    private function searchProductRows(string $queryText, int $limit = 60)
    {
        if (!Schema::hasTable('products')) {
            return collect();
        }

        $columns = $this->productColumns();
        $words = $this->splitSearchWords($queryText);
        $fullQuery = trim($queryText);
        $normalizedFullQuery = $this->normalizeSearchText($fullQuery);

        $searchable = array_values(array_intersect([
            'name', 'product_name', 'nombre', 'title', 'titulo',
            'sku', 'supplier_sku', 'codigo', 'code', 'clave',
            'brand', 'brand_name', 'marca',
            'model', 'modelo', 'model_name',
            'category', 'categoria', 'category_name', 'category_key',
            'color', 'unit', 'unidad', 'unit_measure',
            'description', 'descripcion', 'excerpt', 'short_description', 'notes', 'notas'
        ], $columns));

        $base = DB::table('products');

        if (in_array('deleted_at', $columns, true)) {
            $base->whereNull('deleted_at');
        }

        if ($fullQuery !== '' && !empty($searchable)) {
            $base->where(function ($sub) use ($searchable, $words, $fullQuery) {
                foreach ($searchable as $column) {
                    $sub->orWhere($column, 'like', '%' . $fullQuery . '%');
                }

                foreach ($words as $word) {
                    foreach ($searchable as $column) {
                        $sub->orWhere($column, 'like', '%' . $word . '%');
                    }
                }
            });
        }

        $rows = $base->limit(500)->get();

        // Fallback: si MySQL no encontró por LIKE, traemos una muestra para rankear en PHP.
        // No mezclamos catalog_items aquí: búsqueda manual debe usar SOLO products.
        if ($rows->isEmpty() && $fullQuery !== '') {
            $fallback = DB::table('products');

            if (in_array('deleted_at', $columns, true)) {
                $fallback->whereNull('deleted_at');
            }

            $rows = $fallback->limit(800)->get();
        }

        return $rows
            ->map(function ($row) use ($columns, $queryText, $words, $normalizedFullQuery) {
                return $this->productRowToCandidate($row, $columns, $queryText, $words, $normalizedFullQuery);
            })
            ->filter(fn ($candidate) => (float) $candidate['similarity_pct'] > 0)
            ->sortByDesc('similarity_pct')
            ->take($limit)
            ->values();
    }

    private function productRowToCandidate($row, array $columns, string $searchText, ?array $searchWords = null, ?string $normalizedFullQuery = null): array
    {
        $name = (string) ($this->pickProductValue($row, $columns, ['name', 'product_name', 'nombre', 'titulo', 'title']) ?: 'Producto sin nombre');
        $sku = (string) ($this->pickProductValue($row, $columns, ['sku', 'supplier_sku', 'codigo', 'code', 'clave']) ?: '');
        $brand = (string) ($this->pickProductValue($row, $columns, ['brand', 'brand_name', 'marca']) ?: '');
        $model = (string) ($this->pickProductValue($row, $columns, ['model', 'modelo', 'model_name']) ?: '');
        $category = (string) ($this->pickProductValue($row, $columns, ['category', 'categoria', 'category_name', 'category_key']) ?: '');
        $unit = (string) ($this->pickProductValue($row, $columns, ['unit', 'unidad', 'unit_measure', 'unidad_solicitada']) ?: 'pieza');
        $color = (string) ($this->pickProductValue($row, $columns, ['color', 'colour']) ?: '');
        $description = (string) ($this->pickProductValue($row, $columns, ['description', 'descripcion', 'excerpt', 'short_description', 'notes', 'notas']) ?: '');
        $stock = (float) ($this->pickProductValue($row, $columns, ['stock', 'existencia', 'qty', 'available']) ?: 0);
        $cost = (float) ($this->pickProductValue($row, $columns, ['cost', 'costo', 'purchase_price', 'precio_compra']) ?: 0);
        $price = (float) ($this->pickProductValue($row, $columns, ['price', 'precio', 'sale_price', 'precio_venta']) ?: 0);

        $normalizedFullQuery = $normalizedFullQuery ?? $this->normalizeSearchText($searchText);
        $searchWords = $searchWords ?? $this->splitSearchWords($searchText);

        $nameText = $this->normalizeSearchText($name);
        $skuText = $this->normalizeSearchText($sku);
        $brandText = $this->normalizeSearchText($brand);
        $modelText = $this->normalizeSearchText($model);
        $categoryText = $this->normalizeSearchText($category);
        $colorText = $this->normalizeSearchText($color);
        $descriptionText = $this->normalizeSearchText($description);
        $haystack = trim($nameText . ' ' . $skuText . ' ' . $brandText . ' ' . $modelText . ' ' . $categoryText . ' ' . $colorText . ' ' . $descriptionText);

        $score = 0.0;

        if ($normalizedFullQuery !== '') {
            if ($nameText === $normalizedFullQuery || $skuText === $normalizedFullQuery) {
                $score = max($score, 100);
            }

            if (str_contains($nameText, $normalizedFullQuery)) {
                $score = max($score, 96);
            }

            if (str_contains($haystack, $normalizedFullQuery)) {
                $score = max($score, 88);
            }
        }

        if (!empty($searchWords)) {
            $words = collect($searchWords)
                ->map(fn ($word) => $this->normalizeSearchText($word))
                ->filter(fn ($word) => $word !== '')
                ->unique()
                ->values()
                ->all();

            $nameHits = collect($words)->filter(fn ($word) => str_contains($nameText, $word))->count();
            $haystackHits = collect($words)->filter(fn ($word) => str_contains($haystack, $word))->count();
            $totalWords = max(count($words), 1);

            $nameTokenScore = ($nameHits / $totalWords) * 92;
            $generalTokenScore = ($haystackHits / $totalWords) * 76;

            $score = max($score, $nameTokenScore, $generalTokenScore);

            if ($nameHits >= 2) {
                $score += 8;
            }

            if ($brandText !== '' && collect($words)->contains(fn ($word) => str_contains($brandText, $word))) {
                $score += 4;
            }
        }

        similar_text($normalizedFullQuery, $haystack, $similarPct);
        $score = max($score, min((float) $similarPct, 55));
        $score = min(round($score, 2), 100);

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

            if (is_string($value) && mb_strlen($value) > 140) {
                $value = mb_substr($value, 0, 140) . '...';
            }

            $details[] = [
                'label' => str_replace('_', ' ', mb_strtoupper($column)),
                'value' => (string) $value,
            ];
        }

        return [
            'id' => $row->id ?? null,
            'source_table' => 'products',
            'name' => $name,
            'sku' => $sku,
            'brand' => $brand,
            'model' => $model,
            'category' => $category,
            'unit' => $unit,
            'color' => $color,
            'description' => $description,
            // Sin imágenes en búsqueda manual. Las imágenes quedan solo para Muestras / stock.
            'image_url' => null,
            'photo_urls' => [],
            'similarity_pct' => $score,
            'stock' => $stock,
            'cost' => $cost,
            'price' => $price,
            'details' => $details,
        ];
    }

    private function searchCatalogRows(string $queryText, int $limit = 60)
    {
        if (!Schema::hasTable('catalog_items')) {
            return collect();
        }

        $columns = $this->catalogColumns();
        $words = $this->splitSearchWords($queryText);
        $fullQuery = trim($queryText);

        $searchable = array_values(array_intersect([
            'name', 'slug', 'sku', 'amazon_sku', 'amazon_asin', 'meli_item_id', 'meli_gtin',
            'brand_name', 'model_name', 'category_key', 'excerpt', 'description',
            'unit_measure', 'content_unit_measure'
        ], $columns));

        $base = DB::table('catalog_items');

        if (in_array('deleted_at', $columns, true)) {
            $base->whereNull('deleted_at');
        }

        if ($fullQuery !== '' && !empty($searchable)) {
            $base->where(function ($sub) use ($searchable, $words, $fullQuery) {
                foreach ($searchable as $column) {
                    $sub->orWhere($column, 'like', '%' . $fullQuery . '%');
                }

                foreach ($words as $word) {
                    foreach ($searchable as $column) {
                        $sub->orWhere($column, 'like', '%' . $word . '%');
                    }
                }
            });
        }

        $rows = $base->limit(250)->get();

        if ($rows->isEmpty() && $fullQuery !== '') {
            $fallback = DB::table('catalog_items');

            if (in_array('deleted_at', $columns, true)) {
                $fallback->whereNull('deleted_at');
            }

            $rows = $fallback->limit(400)->get();
        }

        return $rows
            ->map(function ($row) use ($columns, $queryText) {
                return $this->catalogRowToCandidate($row, $columns, $queryText);
            })
            ->sortByDesc('similarity_pct')
            ->take($limit)
            ->values();
    }

    private function catalogRowToCandidate($row, array $columns, string $searchText, float $neededQty = 0): array
    {
        $name = (string) ($this->pickCatalogValue($row, $columns, ['name', 'product_name', 'nombre', 'titulo', 'title']) ?: 'Producto sin nombre');
        $sku = (string) ($this->pickCatalogValue($row, $columns, ['sku', 'amazon_sku', 'codigo', 'code', 'clave', 'meli_item_id']) ?: '');
        $brand = (string) ($this->pickCatalogValue($row, $columns, ['brand_name', 'brand', 'marca']) ?: '');
        $model = (string) ($this->pickCatalogValue($row, $columns, ['model_name', 'model', 'modelo', 'amazon_asin']) ?: '');
        $category = (string) ($this->pickCatalogValue($row, $columns, ['category_key', 'category', 'categoria']) ?: '');
        $unit = (string) ($this->pickCatalogValue($row, $columns, ['unit_measure', 'unit', 'unidad']) ?: 'pieza');
        $description = (string) ($this->pickCatalogValue($row, $columns, ['excerpt', 'description', 'descripcion']) ?: '');
        $stock = (float) ($this->pickCatalogValue($row, $columns, ['stock', 'net_available', 'available', 'existencia', 'qty']) ?: 0);
        $reserved = (float) ($this->pickCatalogValue($row, $columns, ['reserved', 'apartado', 'reservado', 'committed']) ?: 0);
        $cost = (float) ($this->pickCatalogValue($row, $columns, ['cost', 'costo', 'purchase_price', 'precio_compra']) ?: 0);
        $price = (float) ($this->pickCatalogValue($row, $columns, ['sale_price', 'price', 'precio', 'precio_venta']) ?: 0);

        $rawPhotos = [];
        foreach (['photo_1', 'photo_2', 'photo_3'] as $photoColumn) {
            if (in_array($photoColumn, $columns, true) && !empty($row->{$photoColumn})) {
                $rawPhotos[] = $row->{$photoColumn};
            }
        }

        foreach (['image_url', 'imagen_url', 'photo_url', 'thumbnail_url', 'picture_url'] as $photoColumn) {
            if (in_array($photoColumn, $columns, true) && !empty($row->{$photoColumn})) {
                $rawPhotos[] = $row->{$photoColumn};
            }
        }

        $photoUrls = array_values(array_unique(array_filter(array_map(fn ($photo) => $this->normalizeCatalogPhotoUrl($photo), $rawPhotos))));
        $imageUrl = $photoUrls[0] ?? null;

        $haystack = trim($name . ' ' . $sku . ' ' . $brand . ' ' . $model . ' ' . $category . ' ' . $description);
        similar_text(mb_strtolower($searchText), mb_strtolower($haystack), $pct);

        $searchWords = $this->splitSearchWords($searchText);
        if (!empty($searchWords)) {
            $lowerHaystack = mb_strtolower($haystack);
            $hits = collect($searchWords)->filter(fn ($word) => str_contains($lowerHaystack, $word))->count();
            $tokenScore = ($hits / max(count($searchWords), 1)) * 100;
            $pct = max($pct, $tokenScore);
        }

        $netAvailable = max($stock - $reserved, 0);
        $toBuy = max($neededQty - $netAvailable, 0);

        $hidden = ['id', 'created_at', 'updated_at', 'deleted_at', 'photo_1', 'photo_2', 'photo_3'];
        $details = [];
        foreach ($columns as $column) {
            if (in_array($column, $hidden, true)) {
                continue;
            }

            $value = $row->{$column} ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            if (is_string($value) && mb_strlen($value) > 140) {
                $value = mb_substr($value, 0, 140) . '...';
            }

            $details[] = [
                'label' => str_replace('_', ' ', mb_strtoupper($column)),
                'value' => (string) $value,
            ];
        }

        return [
            'id' => $row->id ?? null,
            'source_table' => 'catalog_items',
            'name' => $name,
            'sku' => $sku,
            'brand' => $brand,
            'model' => $model,
            'category' => $category,
            'unit' => $unit,
            'unit_measure' => $row->unit_measure ?? $unit,
            'content_quantity' => $row->content_quantity ?? null,
            'content_unit_measure' => $row->content_unit_measure ?? null,
            'description' => $description,
            'image_url' => $imageUrl,
            'photo_urls' => $photoUrls,
            'photo_1' => $photoUrls[0] ?? null,
            'photo_2' => $photoUrls[1] ?? null,
            'photo_3' => $photoUrls[2] ?? null,
            'similarity_pct' => round($pct, 2),
            'stock' => $stock,
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
            'public_url' => isset($row->slug) && $row->slug ? url('/catalogo/' . $row->slug) : null,
        ];
    }

    private function itemPayload(PropuestaComercialItem $item): array
    {
        $item->loadMissing('matches.product', 'externalMatches', 'productoSeleccionado', 'aclaracionPreguntas');

        $meta = $this->asMetaArray($item->meta ?? []);
        $uiStatus = data_get($meta, 'ui_status', 'pending');
        $humanAccepted = $uiStatus === 'accepted_item';
        $selectedMatch = $humanAccepted ? $item->matches->firstWhere('seleccionado', true) : null;
        $bestMatch = $item->matches->sortByDesc('score')->first();
        $score = (float) ($item->match_score ?: optional($selectedMatch ?: $bestMatch)->score);

        if ($humanAccepted && ($item->productoSeleccionado || $selectedMatch)) {
            $statusKey = 'exact';
        } elseif ($item->productoSeleccionado || $item->matches->count() || data_get($meta, 'catalog_product_name_manual')) {
            $statusKey = 'similar';
        } else {
            $statusKey = 'not_found';
        }

        return [
            'id' => $item->id,
            'sort' => (int) $item->sort,
            'descripcion_original' => $item->descripcion_original,
            'unidad_solicitada' => $item->unidad_solicitada,
            'cantidad_minima' => (float) $item->cantidad_minima,
            'cantidad_maxima' => (float) $item->cantidad_maxima,
            'cantidad_cotizada' => (float) ($item->cantidad_cotizada ?: 1),
            'costo_unitario' => (float) $item->costo_unitario,
            'precio_unitario' => (float) $item->precio_unitario,
            'subtotal' => (float) $item->subtotal,
            'match_score' => $score,
            'status_key' => $statusKey,
            'ui_status' => $uiStatus,
            'item_margin_pct' => (float) data_get($meta, 'item_margin_pct', 25),
            'manual_external_supplier' => data_get($meta, 'external_supplier'),
            'manual_external_link' => data_get($meta, 'external_link'),
            'modelo' => data_get($meta, 'modelo'),
            'catalog_product_name_manual' => data_get($meta, 'catalog_product_name_manual'),
            'tech_sheet_id' => data_get($meta, 'tech_sheet_id'),
            'tech_sheet_name' => data_get($meta, 'tech_sheet_name'),
            'clarification_questions' => $item->relationLoaded('aclaracionPreguntas')
                ? $item->aclaracionPreguntas->sortBy('sort')->values()->all()
                : data_get($meta, 'clarification_questions', []),
            'producto_seleccionado' => ($humanAccepted && $item->productoSeleccionado) ? [
                'id' => $item->productoSeleccionado->id,
                'name' => $item->productoSeleccionado->name,
                'sku' => $item->productoSeleccionado->sku,
                'brand' => data_get($meta, 'external_supplier') ?: $item->productoSeleccionado->brand,
                'model' => data_get($meta, 'modelo') ?: ($item->productoSeleccionado->model ?? $item->productoSeleccionado->modelo ?? $item->productoSeleccionado->model_name ?? null),
                'stock' => $item->productoSeleccionado->stock ?? 0,
                'cost' => (float) ($item->productoSeleccionado->cost ?? $item->productoSeleccionado->costo ?? 0),
                'price' => (float) ($item->productoSeleccionado->price ?? $item->productoSeleccionado->precio ?? 0),
            ] : null,
            'matches' => $item->matches->sortBy('rank')->values()->map(function ($match) use ($humanAccepted) {
                $p = $match->product;
                return [
                    'id' => $match->id,
                    'rank' => $match->rank,
                    'score' => (float) $match->score,
                    'seleccionado' => $humanAccepted && (bool) $match->seleccionado,
                    'product' => $p ? [
                        'id' => $p->id,
                        'name' => $p->name,
                        'sku' => $p->sku,
                        'brand' => $p->brand,
                        'model' => $p->model ?? $p->modelo ?? $p->model_name ?? null,
                        'stock' => $p->stock ?? 0,
                        'cost' => (float) ($p->cost ?? $p->costo ?? $p->purchase_price ?? 0),
                        'price' => (float) ($p->price ?? $p->precio ?? $p->sale_price ?? 0),
                    ] : null,
                ];
            })->all(),
            'external_matches' => $item->externalMatches->sortBy('rank')->values()->map(function ($external) {
                return [
                    'id' => $external->id,
                    'source' => $external->source,
                    'title' => $external->title,
                    'seller' => $external->seller,
                    'price' => (float) $external->price,
                    'url' => $external->url,
                    'score' => (float) $external->score,
                ];
            })->all(),
        ];
    }

    private function summaryPayload(PropuestaComercial $propuestaComercial): array
    {
        $propuestaComercial->load('items.matches');

        $items = $propuestaComercial->items;
        $subtotalSale = (float) $items->sum('subtotal');
        $subtotalCost = (float) $items->sum(fn ($i) => ((float) $i->costo_unitario) * ((float) ($i->cantidad_cotizada ?: 0)));
        $profit = $subtotalSale - $subtotalCost;
        $margin = $subtotalCost > 0 ? round(($profit / $subtotalCost) * 100) : 0;

        $payloads = $items->map(fn ($item) => $this->itemPayload($item));

        return [
            'exact' => $payloads->where('status_key', 'exact')->count(),
            'similar' => $payloads->where('status_key', 'similar')->count(),
            'not_found' => $payloads->where('status_key', 'not_found')->count(),
            'subtotal_sale' => $subtotalSale,
            'subtotal_cost' => $subtotalCost,
            'profit' => $profit,
            'margin' => $margin,
            'total_items' => $items->count(),
        ];
    }

    public function ajaxManualSearch(Request $request, PropuestaComercial $propuestaComercial)
    {
        $q = trim((string) $request->query('q', ''));
        $internet = (bool) $request->boolean('internet');

        if ($internet) {
            return response()->json([
                'ok' => true,
                'internet' => [],
                'products' => [],
            ]);
        }

        // IMPORTANTE:
        // La busqueda manual debe buscar SOLO en la tabla `products`.
        // `catalog_items` queda reservado para Muestras / stock.
        $products = $this->searchProductRows($q, 50)
            ->map(function ($candidate) {
                return [
                    'id' => $candidate['id'],
                    'source_table' => 'products',
                    'name' => $candidate['name'],
                    'sku' => $candidate['sku'],
                    'brand' => $candidate['brand'],
                    'model' => $candidate['model'] ?? '',
                    'category' => $candidate['category'],
                    'unit' => $candidate['unit'],
                    'color' => $candidate['color'] ?? '',
                    'stock' => $candidate['stock'],
                    'cost' => $candidate['cost'],
                    'price' => $candidate['price'],
                    'similarity_pct' => $candidate['similarity_pct'],
                    'description' => $candidate['description'] ?? '',
                    'details' => $candidate['details'] ?? [],

                    // No mandar imagenes a busqueda manual.
                    // Las imagenes solo se usan en Muestras / stock.
                    'image_url' => null,
                    'photo_urls' => [],
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'products' => $products,
            'internet' => [],
        ]);
    }

    public function ajaxDeselectItem(PropuestaComercialItem $item)
    {
        $propuestaComercial = PropuestaComercial::findOrFail($item->propuesta_comercial_id);

        DB::transaction(function () use ($item) {
            if (method_exists($item, 'matches')) {
                $item->matches()->update(['seleccionado' => false]);
            }

            $meta = $this->asMetaArray($item->meta ?? []);
            $meta['ui_status'] = 'manual_review';

            $item->producto_seleccionado_id = null;
            $item->match_score = null;
            $item->meta = $meta;
            $item->save();
        });

        $this->recalculateTotals($propuestaComercial);

        return response()->json([
            'ok' => true,
            'message' => 'Producto deseleccionado correctamente.',
            'item' => $this->itemPayload($item->fresh()),
            'summary' => $this->summaryPayload($propuestaComercial->fresh()),
        ]);
    }

    public function ajaxUpdateItem(Request $request, PropuestaComercialItem $item)
    {
        $data = $request->validate([
            'descripcion_original' => ['nullable', 'string'],
            'cantidad_cotizada' => ['nullable', 'numeric', 'min:0'],
            'unidad_solicitada' => ['nullable', 'string', 'max:50'],
            'costo_unitario' => ['nullable', 'numeric', 'min:0'],
            'porcentaje_utilidad' => ['nullable', 'numeric', 'min:0'],
            'external_supplier' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'external_link' => ['nullable', 'string', 'max:2048'],
            'modelo' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'catalog_product_name' => ['nullable', 'string', 'max:500'],
        ]);

        $meta = $this->asMetaArray($item->meta ?? []);

        if (array_key_exists('descripcion_original', $data)) {
            $item->descripcion_original = trim((string) $data['descripcion_original']);
        }

        if (array_key_exists('cantidad_cotizada', $data)) {
            $item->cantidad_cotizada = (float) $data['cantidad_cotizada'];
        }

        if (array_key_exists('unidad_solicitada', $data)) {
            $item->unidad_solicitada = trim((string) $data['unidad_solicitada']) ?: 'PIEZA';
        }

        if (array_key_exists('costo_unitario', $data)) {
            $item->costo_unitario = (float) $data['costo_unitario'];
        }

        $margin = array_key_exists('porcentaje_utilidad', $data)
            ? (float) $data['porcentaje_utilidad']
            : (float) data_get($meta, 'item_margin_pct', 25);

        $meta['item_margin_pct'] = $margin;

        if (array_key_exists('brand', $data)) {
            $meta['external_supplier'] = trim((string) $data['brand']);
        } elseif (array_key_exists('external_supplier', $data)) {
            $meta['external_supplier'] = trim((string) $data['external_supplier']);
        }

        if (array_key_exists('external_link', $data)) {
            $meta['external_link'] = trim((string) $data['external_link']);
        }

        if (array_key_exists('model', $data)) {
            $meta['modelo'] = trim((string) $data['model']);
        } elseif (array_key_exists('modelo', $data)) {
            $meta['modelo'] = trim((string) $data['modelo']);
        }

        if (array_key_exists('catalog_product_name', $data)) {
            $meta['catalog_product_name_manual'] = trim((string) $data['catalog_product_name']);
        }

        $qty = (float) ($item->cantidad_cotizada ?: $item->cantidad_maxima ?: $item->cantidad_minima ?: 1);
        $cost = (float) ($item->costo_unitario ?: 0);
        $price = $cost > 0 ? round($cost * (1 + ($margin / 100)), 2) : 0;

        $item->precio_unitario = $price;
        $item->subtotal = round($price * $qty, 2);
        $item->meta = $meta;
        $item->status = $price > 0 ? 'priced' : $item->status;
        $item->save();

        $propuestaComercial = PropuestaComercial::findOrFail($item->propuesta_comercial_id);
        $this->recalculateTotals($propuestaComercial);

        return response()->json([
            'ok' => true,
            'item' => $this->itemPayload($item->fresh()),
            'summary' => $this->summaryPayload($propuestaComercial->fresh()),
        ]);
    }

    public function ajaxSamplesItem(PropuestaComercialItem $item)
    {
        $neededQty = (float) ($item->cantidad_cotizada ?: $item->cantidad_maxima ?: $item->cantidad_minima ?: 1);
        $searchText = trim((string) $item->descripcion_original);
        $candidates = $this->searchCatalogRows($searchText, 30)
            ->map(function ($candidate) use ($neededQty) {
                $candidate['needed_qty'] = $neededQty;
                $candidate['to_buy'] = max($neededQty - (float) ($candidate['net_available'] ?? 0), 0);
                return $candidate;
            })
            ->values()
            ->all();

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