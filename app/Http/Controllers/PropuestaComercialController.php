<?php

namespace App\Http\Controllers;

use App\Models\DocumentAiRun;
use App\Models\Product;
use App\Models\PropuestaComercial;
use App\Models\PropuestaComercialItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class PropuestaComercialController extends Controller
{
    private const UNIDAD_MAX_LEN = 50;

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

    private function normalizarDescripcionUnidad(?string $descripcion, ?string $unidad): array
    {
        $descripcion = trim((string) $descripcion);
        $unidad = trim((string) $unidad);

        /*
         * La IA ya interpreta la unidad con sentido común.
         * Aquí NO intentamos corregirla con listas duras.
         *
         * Ejemplos que deben respetarse:
         * - PAQUETE CON 100 PIEZAS
         * - CAJA CON 12 PIEZAS
         * - BOLSA CON 50 PIEZAS
         * - SERVICIO
         * - PIEZA
         */
        if ($descripcion === '') {
            $descripcion = 'Sin descripción';
        }

        if ($unidad === '') {
            $unidad = 'PIEZA';
        }

        return [
            $descripcion,
            mb_substr($unidad, 0, 150),
        ];
    }

    private function quitarAcentosBasico(string $texto): string
    {
        return strtr($texto, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
        ]);
    }

    private function descripcionEsBasura(?string $descripcion): bool
    {
        $descripcion = trim((string) $descripcion);

        if ($descripcion === '') {
            return true;
        }

        $descripcionCheck = mb_strtolower($descripcion);
        $descripcionCheck = preg_replace('/\s+/u', ' ', $descripcionCheck);
        $descripcionCheck = trim($descripcionCheck);

        $descripcionCheckSinAcentos = mb_strtolower($this->quitarAcentosBasico($descripcionCheck));
        $descripcionCheckSinAcentos = preg_replace('/\s+/u', ' ', $descripcionCheckSinAcentos);
        $descripcionCheckSinAcentos = trim($descripcionCheckSinAcentos);

        $invalidDescriptions = [
            'fecha',
            'horario',
            'domicilio',
            'lugar',
            'nombre',
            'firma',
            'firmas',
            'partida',
            'no.',
            'no',
            'numero',
            'número',
            'descripcion',
            'descripción',
            'concepto',
            'cantidad',
            'unidad',
            'precio',
            'precio unitario',
            'costo unitario',
            'costo unitario antes de iva',
            'importe',
            'subtotal',
            'total',
            'iva',
            'anexo',
            'clave',
            'rfc',
            'telefono',
            'teléfono',
            'correo',
            'email',
            'si',
            'sí',
            'no aplica',
            'n/a',
            'na',
            'hoja',
            'pagina',
            'página',
            'servicio',
            'producto',
            'bien',
            'bienes',
        ];

        if (
            in_array($descripcionCheck, $invalidDescriptions, true)
            || in_array($descripcionCheckSinAcentos, $invalidDescriptions, true)
        ) {
            return true;
        }

        if (preg_match('/^\d+\s+de\s+\d+$/i', $descripcionCheck)) {
            return true;
        }

        if (preg_match('/^hoja\s+\d+/i', $descripcionCheck)) {
            return true;
        }

        if (preg_match('/^p[aá]gina\s+\d+/i', $descripcionCheck)) {
            return true;
        }

        if (preg_match('/^[\d\s.,\/\-:]+$/', $descripcionCheck)) {
            return true;
        }

        if (preg_match('/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}$/', $descripcionCheck)) {
            return true;
        }

        if (preg_match('/^\d{1,2}:\d{2}/', $descripcionCheck)) {
            return true;
        }

        if (preg_match('/\b\d{1,2}:\d{2}\s*a\s*\d{1,2}:\d{2}\b/i', $descripcionCheck)) {
            return true;
        }

        $palabrasInstitucionales = [
            'convocante',
            'dependencia',
            'licitacion',
            'licitación',
            'procedimiento',
            'junta de aclaraciones',
            'presentacion de propuestas',
            'presentación de propuestas',
            'apertura de propuestas',
            'acto de fallo',
            'fallo',
            'contrato',
            'domicilio',
            'direccion',
            'dirección',
            'servidor publico',
            'servidor público',
            'area contratante',
            'área contratante',
            'unidad compradora',
            'compranet',
            'bases',
            'convocatoria',
            'aclaraciones',
            'representante legal',
            'razon social',
            'razón social',
        ];

        foreach ($palabrasInstitucionales as $palabra) {
            if (
                str_contains($descripcionCheck, $palabra)
                || str_contains($descripcionCheckSinAcentos, $this->quitarAcentosBasico($palabra))
            ) {
                if (mb_strlen($descripcionCheck) < 160) {
                    return true;
                }
            }
        }

        $letras = preg_match_all('/[\pL]/u', $descripcionCheck);

        if ($letras < 8) {
            return true;
        }

        $palabras = preg_split('/[^\pL\pN]+/u', $descripcionCheck, -1, PREG_SPLIT_NO_EMPTY);

        if (count($palabras) < 2) {
            return true;
        }

        return false;
    }

    private function filaTieneDescripcionValida(array $row): bool
    {
        $descripcionRaw = $row['descripcion']
            ?? $row['description']
            ?? $row['producto']
            ?? $row['product']
            ?? $row['nombre']
            ?? null;

        return !$this->descripcionEsBasura($descripcionRaw);
    }


    private function parsePartidaSubpartida($partidaRaw, $subpartidaRaw, int $sort): array
    {
        $partidaNumero = null;
        $subpartidaNumero = null;

        $partidaText = trim((string) $partidaRaw);
        $subpartidaText = trim((string) $subpartidaRaw);

        if (preg_match('/^(\d+)\.(\d+)$/', $partidaText, $m)) {
            $partidaNumero = (int) $m[1];
            $subpartidaNumero = (int) $m[2];
        } elseif (is_numeric($partidaRaw)) {
            $partidaNumero = (int) $partidaRaw;
        }

        if ($subpartidaNumero === null && $subpartidaText !== '') {
            if (preg_match('/^(\d+)\.(\d+)$/', $subpartidaText, $m)) {
                if ($partidaNumero === null) {
                    $partidaNumero = (int) $m[1];
                }

                $subpartidaNumero = (int) $m[2];
            } elseif (is_numeric($subpartidaRaw)) {
                $subpartidaNumero = (int) $subpartidaRaw;
            }
        }

        if ($partidaNumero === null) {
            $partidaNumero = $sort;
        }

        return [$partidaNumero, $subpartidaNumero];
    }

    private function cantidadCotizadaPreferida(array $row)
    {
        /*
         * Regla de negocio:
         * Si existe cantidad mínima, SIEMPRE se cotiza con cantidad mínima.
         * Si no existe, se usa cantidad_cotizada de la IA.
         * Si no existe, se usa cantidad normal.
         * Si no existe, se usa cantidad máxima.
         * Si nada existe, 1.
         */
        $cantidadMinima = $row['cantidad_minima'] ?? $row['min_quantity'] ?? null;
        $cantidadIa = $row['cantidad_cotizada'] ?? null;
        $cantidadNormal = $row['cantidad'] ?? $row['quantity'] ?? null;
        $cantidadMaxima = $row['cantidad_maxima'] ?? $row['max_quantity'] ?? null;

        if (is_numeric($cantidadMinima)) {
            return $cantidadMinima;
        }

        if (is_numeric($cantidadIa)) {
            return $cantidadIa;
        }

        if (is_numeric($cantidadNormal)) {
            return $cantidadNormal;
        }

        if (is_numeric($cantidadMaxima)) {
            return $cantidadMaxima;
        }

        return 1;
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

        Log::info('Creando propuesta comercial desde DocumentAiRun', [
            'document_ai_run_id' => $data['document_ai_run_id'],
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
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $items = collect($items)
            ->filter(fn ($row) => is_array($row))
            ->filter(fn ($row) => $this->filaTieneDescripcionValida($row))
            ->values()
            ->all();

        if (empty($items)) {
            $message = 'El análisis solo devolvió encabezados, fechas, páginas o datos que no son productos. Revisa el extractor de partidas del PDF.';

            Log::warning('DocumentAiRun sin partidas válidas después del filtro anti-basura', [
                'document_ai_run_id' => $run->id,
                'licitacion_pdf_id' => $run->licitacion_pdf_id,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $propuesta = null;

        DB::transaction(function () use ($data, $run, $structured, $items, &$propuesta) {
            $propuesta = PropuestaComercial::create([
                'licitacion_pdf_id' => $run->licitacion_pdf_id,
                'document_ai_run_id' => $run->id,

                'titulo' => $data['titulo']
                    ?: ($structured['objeto'] ?? $structured['titulo'] ?? ('Propuesta comercial #' . $run->id)),

                'folio' => $data['folio']
                    ?: ($structured['numero_procedimiento'] ?? $structured['folio'] ?? null),

                'cliente' => $data['cliente']
                    ?: ($structured['dependencia'] ?? $structured['cliente'] ?? $structured['razon_social'] ?? null),

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
                $partidaRaw = $row['partida'] ?? $row['partida_numero'] ?? null;
                $subpartidaRaw = $row['subpartida'] ?? $row['subpartida_numero'] ?? null;

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

                if ($this->descripcionEsBasura($descripcion)) {
                    Log::warning('Partida omitida por descripción basura después de normalizar', [
                        'document_ai_run_id' => $run->id,
                        'descripcion_raw' => $descripcionRaw,
                        'descripcion_normalizada' => $descripcion,
                        'row' => $row,
                    ]);

                    continue;
                }

                $sort++;

                $cantidadMinima = $row['cantidad_minima'] ?? $row['min_quantity'] ?? null;
                $cantidadMaxima = $row['cantidad_maxima'] ?? $row['max_quantity'] ?? null;
                $cantidadCotizada = $this->cantidadCotizadaPreferida($row);

                [$partidaNumero, $subpartidaNumero] = $this->parsePartidaSubpartida($partidaRaw, $subpartidaRaw, $sort);

                PropuestaComercialItem::create([
                    'propuesta_comercial_id' => $propuesta->id,
                    'sort' => $sort,
                    'partida_numero' => $partidaNumero,
                    'subpartida_numero' => $subpartidaNumero,

                    'descripcion_original' => $descripcion,
                    'unidad_solicitada' => $unidad,

                    'cantidad_minima' => is_numeric($cantidadMinima) ? $cantidadMinima : null,
                    'cantidad_maxima' => is_numeric($cantidadMaxima) ? $cantidadMaxima : null,
                    'cantidad_cotizada' => is_numeric($cantidadCotizada) ? $cantidadCotizada : 1,

                    'producto_seleccionado_id' => null,
                    'match_score' => null,

                    'costo_unitario' => null,
                    'precio_unitario' => null,
                    'subtotal' => 0,

                    'status' => 'pending',

                    'meta' => [
                        'presentar_muestra' => $row['presentar_muestra'] ?? null,
                        'subpartida_label' => $subpartidaRaw,
                        'created_from_run_id' => $run->id,
                        'raw' => $row,
                        'campos_corregidos' => (
                            mb_strtoupper(trim((string) $descripcionRaw)) !== mb_strtoupper($descripcion)
                        ),
                    ],
                ]);
            }

            if ($sort === 0) {
                throw new \RuntimeException('No se creó ninguna partida válida después de filtrar el análisis.');
            }

            $meta = $propuesta->meta;
            $meta['items_count'] = $sort;
            $propuesta->meta = $meta;
            $propuesta->save();
        });

        $redirectUrl = route('propuestas-comerciales.show', ['propuestaComercial' => $propuesta->id]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'propuesta_id' => $propuesta->id,
                'redirect_url' => $redirectUrl,
                'message' => 'Propuesta comercial creada correctamente con partidas completas.',
            ]);
        }

        return redirect()->to($redirectUrl)
            ->with('status', 'Propuesta comercial creada correctamente con partidas completas.');
    }

    public function show(PropuestaComercial $propuestaComercial)
    {
        $propuestaComercial->load([
            'items.matches.product',
            'items.externalMatches',
            'items.productoSeleccionado',
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
                    $partida->update(['sort' => $index + 1]);
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
        $fullQuery = trim($queryText);
        $normalizedFullQuery = $this->normalizeSearchText($fullQuery);
        $words = $this->splitSearchWords($queryText);

        $searchable = array_values(array_intersect([
            'name', 'product_name', 'nombre', 'title', 'titulo',
            'sku', 'supplier_sku', 'codigo', 'code', 'clave',
            'brand', 'brand_name', 'marca', 'marca_producto', 'manufacturer', 'fabricante',
            'model', 'modelo', 'model_name', 'modelo_producto',
            'category', 'categoria', 'category_name', 'category_key', 'family', 'familia',
            'color', 'unit', 'unidad', 'unit_measure',
            'description', 'descripcion', 'excerpt', 'short_description', 'notes', 'notas'
        ], $columns));

        $nameColumns = array_values(array_intersect([
            'name', 'product_name', 'nombre', 'title', 'titulo', 'description', 'descripcion'
        ], $columns));

        $idRows = collect();

        $baseQuery = function () use ($columns) {
            $query = DB::table('products');
            if (in_array('deleted_at', $columns, true)) {
                $query->whereNull('deleted_at');
            }
            return $query;
        };

        if ($fullQuery !== '' && !empty($searchable)) {
            $exact = $baseQuery();
            $exact->where(function ($sub) use ($searchable, $fullQuery) {
                foreach ($searchable as $column) {
                    $sub->orWhere($column, 'like', '%' . $fullQuery . '%');
                }
            });
            $idRows = $idRows->merge($exact->limit(120)->get());
        }

        if (!empty($words) && !empty($nameColumns)) {
            $allWords = $baseQuery();
            $allWords->where(function ($query) use ($words, $nameColumns) {
                foreach ($words as $word) {
                    $query->where(function ($sub) use ($word, $nameColumns) {
                        foreach ($nameColumns as $column) {
                            $sub->orWhere($column, 'like', '%' . $word . '%');
                        }
                    });
                }
            });
            $idRows = $idRows->merge($allWords->limit(180)->get());
        }

        if (!empty($words) && !empty($searchable)) {
            $broad = $baseQuery();
            $broad->where(function ($sub) use ($searchable, $words) {
                foreach ($words as $word) {
                    foreach ($searchable as $column) {
                        $sub->orWhere($column, 'like', '%' . $word . '%');
                    }
                }
            });
            $idRows = $idRows->merge($broad->limit(500)->get());
        }

        if ($idRows->isEmpty() && $fullQuery !== '') {
            $idRows = $baseQuery()->limit(800)->get();
        }

        return $idRows
            ->unique(fn ($row) => (string) ($row->id ?? spl_object_id($row)))
            ->map(function ($row) use ($columns, $queryText, $words, $normalizedFullQuery) {
                return $this->productRowToCandidate($row, $columns, $queryText, $words, $normalizedFullQuery);
            })
            ->filter(fn ($candidate) => (float) $candidate['similarity_pct'] >= 30)
            ->sortByDesc('similarity_pct')
            ->take($limit)
            ->values();
    }

    private function productRowToCandidate($row, array $columns, string $searchText, ?array $searchWords = null, ?string $normalizedFullQuery = null): array
    {
        $name = (string) ($this->pickProductValue($row, $columns, ['name', 'product_name', 'nombre', 'titulo', 'title']) ?: 'Producto sin nombre');
        $sku = (string) ($this->pickProductValue($row, $columns, ['sku', 'supplier_sku', 'codigo', 'code', 'clave']) ?: '');
        $brand = (string) ($this->pickProductValue($row, $columns, ['brand', 'brand_name', 'marca', 'marca_producto', 'manufacturer', 'fabricante']) ?: '');
        $model = (string) ($this->pickProductValue($row, $columns, ['model', 'modelo', 'model_name', 'modelo_producto']) ?: '');
        $category = (string) ($this->pickProductValue($row, $columns, ['category', 'categoria', 'category_name', 'category_key', 'family', 'familia']) ?: '');
        $unit = (string) ($this->pickProductValue($row, $columns, ['unit', 'unidad', 'unit_measure', 'unidad_solicitada']) ?: 'pieza');
        $color = (string) ($this->pickProductValue($row, $columns, ['color', 'colour']) ?: '');
        $description = (string) ($this->pickProductValue($row, $columns, ['description', 'descripcion', 'excerpt', 'short_description', 'notes', 'notas']) ?: '');
        $stock = (float) ($this->pickProductValue($row, $columns, ['stock', 'existencia', 'qty', 'available']) ?: 0);
        $cost = (float) ($this->pickProductValue($row, $columns, ['cost', 'costo', 'purchase_price', 'precio_compra']) ?: 0);
        $price = (float) ($this->pickProductValue($row, $columns, ['price', 'precio', 'sale_price', 'precio_venta']) ?: 0);

        $normalizedFullQuery = $normalizedFullQuery ?? $this->normalizeSearchText($searchText);
        $searchWords = $searchWords ?? $this->splitSearchWords($searchText);
        $words = collect($searchWords)
            ->map(fn ($word) => $this->normalizeSearchText($word))
            ->filter(fn ($word) => $word !== '')
            ->unique()
            ->values()
            ->all();

        $nameText = $this->normalizeSearchText($name);
        $skuText = $this->normalizeSearchText($sku);
        $brandText = $this->normalizeSearchText($brand);
        $modelText = $this->normalizeSearchText($model);
        $categoryText = $this->normalizeSearchText($category);
        $colorText = $this->normalizeSearchText($color);
        $descriptionText = $this->normalizeSearchText($description);
        $primaryText = trim($nameText . ' ' . $skuText . ' ' . $brandText . ' ' . $modelText . ' ' . $categoryText . ' ' . $colorText);
        $haystack = trim($primaryText . ' ' . $descriptionText);

        $score = 0.0;
        $totalWords = max(count($words), 1);
        $primaryHits = collect($words)->filter(fn ($word) => str_contains($primaryText, $word))->count();
        $haystackHits = collect($words)->filter(fn ($word) => str_contains($haystack, $word))->count();
        $missingWords = collect($words)->filter(fn ($word) => !str_contains($haystack, $word))->values()->all();

        if ($normalizedFullQuery !== '') {
            if ($nameText === $normalizedFullQuery || $skuText === $normalizedFullQuery) {
                $score = max($score, 100);
            }
            if (str_contains($nameText, $normalizedFullQuery) || str_contains($skuText, $normalizedFullQuery)) {
                $score = max($score, 98);
            }
            if (str_contains($primaryText, $normalizedFullQuery)) {
                $score = max($score, 94);
            }
            if (str_contains($haystack, $normalizedFullQuery)) {
                $score = max($score, 88);
            }
        }

        if (!empty($words)) {
            $primaryRatio = $primaryHits / $totalWords;
            $haystackRatio = $haystackHits / $totalWords;
            $score = max($score, $primaryRatio * 95, $haystackRatio * 70);

            if ($primaryHits === $totalWords && $totalWords >= 2) {
                $score = max($score, 93);
            } elseif ($haystackHits === $totalWords && $totalWords >= 2) {
                $score = max($score, 82);
            }

            if ($brandText !== '' && collect($words)->contains(fn ($word) => str_contains($brandText, $word))) {
                $score += 5;
            }

            if ($totalWords >= 2 && !empty($missingWords)) {
                $score = min($score, 24);
            }

            if ($totalWords >= 3 && $primaryHits < 2) {
                $score = min($score, 35);
            }
        }

        if ($normalizedFullQuery !== '' && $score < 80) {
            similar_text($normalizedFullQuery, $primaryText, $similarPrimaryPct);
            similar_text($normalizedFullQuery, $haystack, $similarAllPct);
            $score = max($score, min((float) $similarPrimaryPct, 70), min((float) $similarAllPct, 45));
        }

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

        $qty = (float) ($item->cantidad_cotizada ?: $item->cantidad_minima ?: $item->cantidad_maxima ?: 1);
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
        $neededQty = (float) ($item->cantidad_cotizada ?: $item->cantidad_minima ?: $item->cantidad_maxima ?: 1);
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


    public function exportClarificationsWord(PropuestaComercial $propuestaComercial)
    {
        $propuestaComercial->loadMissing(['items.aclaracionPreguntas']);

        $folio = $propuestaComercial->folio
            ?: ('TEOA' . str_pad((string) $propuestaComercial->id, 8, '0', STR_PAD_LEFT));

        $title = $propuestaComercial->titulo
            ?: ('COT-' . strtoupper(substr(md5($propuestaComercial->id . $propuestaComercial->created_at), 0, 8)));

        $generatedAt = now()->format('d/m/Y H:i');

        $questions = $propuestaComercial->items
            ->sortBy('sort')
            ->values()
            ->flatMap(function ($item, $itemIndex) {
                return $item->aclaracionPreguntas
                    ->sortBy('sort')
                    ->values()
                    ->map(function ($question) use ($item, $itemIndex) {
                        return [
                            'partida' => $item->sort ?: ($itemIndex + 1),
                            'producto' => $item->descripcion_original,
                            'pregunta' => $question->pregunta_generada ?: $question->texto_usuario,
                        ];
                    });
            })
            ->values();

        $e = fn ($value) => e((string) ($value ?? ''));

        $rows = $questions->map(function ($q, $index) use ($e) {
            return '<tr>'
                . '<td class="center tiny-cell">' . ($index + 1) . '</td>'
                . '<td class="center tiny-cell">' . $e($q['partida']) . '</td>'
                . '<td class="product-cell">' . nl2br($e($q['producto'])) . '</td>'
                . '<td class="question-cell">' . nl2br($e($q['pregunta'])) . '</td>'
                . '</tr>';
        })->implode('');

        if ($rows === '') {
            $rows = '<tr><td colspan="4" class="empty">No hay preguntas guardadas para junta de aclaraciones.</td></tr>';
        }

        $html = '<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:w="urn:schemas-microsoft-com:office:word"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta charset="UTF-8">
<title>' . $e($title) . '</title>

<style>
    @page WordSection1 {
        size: 11in 8.5in;
        mso-page-orientation: landscape;
        margin: .35in;
    }

    div.WordSection1 {
        page: WordSection1;
    }

    body {
        font-family: Arial, Helvetica, sans-serif;
        color: #333333;
        margin: 0;
        font-size: 9pt;
        line-height: 1.25;
    }

    h1 {
        color: #111111;
        font-size: 18pt;
        margin: 0 0 5pt;
        font-weight: 700;
    }

    .meta {
        color: #666666;
        font-size: 9pt;
        margin-bottom: 14pt;
        line-height: 1.35;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 8.5pt;
    }

    col.col-num {
        width: 4%;
    }

    col.col-partida {
        width: 6%;
    }

    col.col-producto {
        width: 32%;
    }

    col.col-pregunta {
        width: 58%;
    }

    th {
        background: #f9fafb;
        color: #111111;
        font-weight: 700;
        border: 1px solid #d9d9d9;
        padding: 5pt;
        text-align: left;
        vertical-align: top;
    }

    td {
        border: 1px solid #ebebeb;
        padding: 5pt;
        vertical-align: top;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .center {
        text-align: center;
    }

    .tiny-cell {
        font-size: 8pt;
        white-space: nowrap;
    }

    .product-cell {
        font-size: 8.5pt;
        line-height: 1.25;
    }

    .question-cell {
        font-size: 8.5pt;
        line-height: 1.25;
    }

    .empty {
        text-align: center;
        color: #888888;
        padding: 18pt;
    }
</style>
</head>

<body>
<div class="WordSection1">
    <h1>Junta de aclaraciones</h1>

    <div class="meta">
        <strong>' . $e($title) . '</strong><br>
        Folio: ' . $e($folio) . '<br>
        Generado: ' . $e($generatedAt) . '
    </div>

    <table>
        <colgroup>
            <col class="col-num">
            <col class="col-partida">
            <col class="col-producto">
            <col class="col-pregunta">
        </colgroup>

        <thead>
            <tr>
                <th class="center tiny-cell">#</th>
                <th class="center tiny-cell">Partida</th>
                <th>Producto solicitado</th>
                <th>Pregunta</th>
            </tr>
        </thead>

        <tbody>' . $rows . '</tbody>
    </table>
</div>
</body>
</html>';

        $safeFolio = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $folio ?: 'junta_aclaraciones');

        return response($html, 200, [
            'Content-Type' => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $safeFolio . '_junta_aclaraciones.doc"',
        ]);
    }

    public function exportBrandsPdf(PropuestaComercial $propuestaComercial)
    {
        $propuestaComercial->loadMissing(['items.matches.product', 'items.productoSeleccionado']);

        $folio = $propuestaComercial->folio ?: ('TEOA' . str_pad((string) $propuestaComercial->id, 8, '0', STR_PAD_LEFT));
        $title = $propuestaComercial->titulo ?: ('COT-' . strtoupper(substr(md5($propuestaComercial->id . $propuestaComercial->created_at), 0, 8)));
        $generatedAt = now()->format('d/m/Y H:i');

        $groups = $propuestaComercial->items
            ->sortBy('sort')
            ->values()
            ->map(function ($item, $index) {
                $meta = is_array($item->meta) ? $item->meta : (json_decode((string) $item->meta, true) ?: []);
                $selectedMatch = $item->matches->firstWhere('seleccionado', true);
                $selectedProduct = $item->productoSeleccionado ?: optional($selectedMatch)->product;
                $brand = trim((string) (data_get($meta, 'external_supplier') ?: optional($selectedProduct)->brand ?: 'SIN MARCA'));
                $qty = (float) ($item->cantidad_cotizada ?: $item->cantidad_maxima ?: $item->cantidad_minima ?: 1);
                $price = (float) ($item->precio_unitario ?: 0);
                $cost = (float) ($item->costo_unitario ?: 0);
                $subtotal = (float) ($item->subtotal ?: ($price * $qty));

                return [
                    'brand' => mb_strtoupper($brand ?: 'SIN MARCA'),
                    'number' => $item->sort ?: ($index + 1),
                    'requested' => $item->descripcion_original,
                    'product_name' => optional($selectedProduct)->name ?: data_get($meta, 'catalog_product_name_manual') ?: '',
                    'unit' => $item->unidad_solicitada ?: 'pz',
                    'qty' => $qty,
                    'cost' => $cost,
                    'price' => $price,
                    'subtotal' => $subtotal,
                    'status' => data_get($meta, 'ui_status') === 'accepted_item' ? 'Aceptado' : ($price > 0 ? 'Revisión' : 'No encontrado'),
                ];
            })
            ->groupBy('brand')
            ->sortKeys();

        $e = fn ($value) => e((string) ($value ?? ''));
        $money = fn ($value) => (float) $value > 0 ? '$' . number_format((float) $value, 2) : '';

        $grandTotal = 0;
        $totalItems = 0;

        $sections = $groups->map(function ($rows, $brand) use ($e, $money, &$grandTotal, &$totalItems) {
            $brandTotal = $rows->sum('subtotal');
            $grandTotal += $brandTotal;
            $totalItems += $rows->count();

            $body = $rows->map(function ($row) use ($e, $money) {
                return '<tr>'
                    . '<td class="center">' . $e($row['number']) . '</td>'
                    . '<td>' . $e($row['requested']) . '</td>'
                    . '<td>' . $e($row['product_name'] ?: '—') . '</td>'
                    . '<td class="center">' . $e($row['unit']) . '</td>'
                    . '<td class="right">' . number_format((float) $row['qty'], 2) . '</td>'
                    . '<td class="right">' . $money($row['cost']) . '</td>'
                    . '<td class="right">' . $money($row['price']) . '</td>'
                    . '<td class="right"><strong>' . $money($row['subtotal']) . '</strong></td>'
                    . '<td class="center">' . $e($row['status']) . '</td>'
                    . '</tr>';
            })->implode('');

            return '<section class="brand-section"><div class="brand-head"><div><h2>' . $e($brand) . '</h2><div class="brand-meta">' . $rows->count() . ' partida(s)</div></div><div class="brand-total">Total marca: <strong>' . $money($brandTotal) . '</strong></div></div><table><thead><tr><th class="center">#</th><th>Producto solicitado</th><th>Producto / referencia</th><th class="center">Unidad</th><th class="right">Cantidad</th><th class="right">Costo</th><th class="right">Precio</th><th class="right">Subtotal</th><th class="center">Estado</th></tr></thead><tbody>' . $body . '</tbody></table></section>';
        })->implode('');

        $html = '<!doctype html><html lang="es"><head><meta charset="UTF-8"><title>' . $e($folio) . ' - partidas por marca</title><style>
            @page { size: letter landscape; margin: 10mm; }
            * { box-sizing: border-box; }
            body { font-family: Arial, Helvetica, sans-serif; color: #111; margin: 0; font-size: 10px; }
            .header { display: table; width: 100%; border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 14px; }
            .header-left { display: table-cell; width: 70%; vertical-align: top; }
            .summary { display: table-cell; text-align: right; line-height: 1.55; white-space: nowrap; vertical-align: top; }
            h1 { font-size: 18px; margin: 0 0 5px; }
            h2 { font-size: 14px; margin: 0 0 3px; text-transform: uppercase; }
            .meta { color: #555; line-height: 1.45; }
            .brand-section { page-break-inside: avoid; margin-bottom: 18px; }
            .brand-head { display: table; width: 100%; background: #f3f4f6; border: 1px solid #d9d9d9; padding: 8px 10px; }
            .brand-head > div { display: table-cell; vertical-align: bottom; }
            .brand-total { text-align: right; font-size: 11px; white-space: nowrap; }
            .brand-meta { color: #666; font-size: 9px; }
            table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 8px; }
            th, td { border: 1px solid #d9d9d9; padding: 5px 6px; vertical-align: top; word-wrap: break-word; }
            th { background: #fafafa; color: #111; font-weight: 700; font-size: 9px; }
            td { font-size: 9px; }
            .center { text-align: center; }
            .right { text-align: right; }
            tr:nth-child(even) td { background: #fcfcfc; }
            .footer { margin-top: 14px; border-top: 1px solid #d9d9d9; padding-top: 8px; color: #555; font-size: 9px; }
        </style></head><body><div class="header"><div class="header-left"><h1>Partidas agrupadas por marca</h1><div class="meta"><strong>' . $e($title) . '</strong><br>Folio: ' . $e($folio) . '<br>Generado: ' . $e($generatedAt) . '</div></div><div class="summary">Marcas: <strong>' . $groups->count() . '</strong><br>Partidas: <strong>' . $totalItems . '</strong><br>Total general: <strong>' . $money($grandTotal) . '</strong></div></div>' . ($sections ?: '<p>No hay partidas para agrupar.</p>') . '<div class="footer">Este reporte se genera desde el controlador y no depende del HTML/JavaScript de la vista.</div></body></html>';

        $safeFolio = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $folio ?: 'partidas_por_marca');

        return Pdf::loadHTML($html)
            ->setPaper('letter', 'landscape')
            ->download($safeFolio . '_partidas_por_marca.pdf');
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
    private function buildClienteExportHtml(PropuestaComercial $propuestaComercial): string
{
    $propuestaComercial->loadMissing([
        'items.matches.product',
        'items.productoSeleccionado',
    ]);

    $folio = $propuestaComercial->folio
        ?: ('TEOA' . str_pad((string) $propuestaComercial->id, 8, '0', STR_PAD_LEFT));

    $title = $propuestaComercial->titulo
        ?: ('Propuesta comercial #' . $propuestaComercial->id);

    $fecha = now()->format('d/m/Y');
    $vigencia = data_get($propuestaComercial->meta, 'vigencia_dias', 15);

    $subtotal = (float) $propuestaComercial->subtotal;
    $descuento = (float) $propuestaComercial->descuento_total;
    $iva = (float) $propuestaComercial->impuesto_total;
    $total = (float) $propuestaComercial->total;

    $money = fn ($value) => '$' . number_format((float) $value, 2);
    $e = fn ($value) => e((string) ($value ?? ''));

    $itemsRows = $propuestaComercial->items
        ->sortBy('sort')
        ->values()
        ->map(function ($item, $index) use ($money, $e) {
            $meta = is_array($item->meta)
                ? $item->meta
                : (json_decode((string) $item->meta, true) ?: []);

            $selectedMatch = $item->matches->firstWhere('seleccionado', true);
            $selectedProduct = $item->productoSeleccionado ?: optional($selectedMatch)->product;

            $descripcion = optional($selectedProduct)->name
                ?: data_get($meta, 'catalog_product_name_manual')
                ?: $item->descripcion_original;

            $marca = data_get($meta, 'external_supplier')
                ?: optional($selectedProduct)->brand
                ?: '';

            $modelo = data_get($meta, 'modelo')
                ?: optional($selectedProduct)->model
                ?: optional($selectedProduct)->modelo
                ?: '';

            $cantidad = (float) ($item->cantidad_cotizada ?: $item->cantidad_minima ?: $item->cantidad_maxima ?: 1);
            $unidad = $item->unidad_solicitada ?: 'PZA';
            $precio = (float) $item->precio_unitario;
            $importe = (float) ($item->subtotal ?: ($precio * $cantidad));

            return '
                <tr>
                    <td class="td-center">' . ($index + 1) . '</td>
                    <td>
                        <strong>' . $e($descripcion) . '</strong>
                        <div class="item-muted">' . $e($item->descripcion_original) . '</div>
                        ' . ($marca ? '<div class="item-muted">Marca: ' . $e($marca) . '</div>' : '') . '
                        ' . ($modelo ? '<div class="item-muted">Modelo: ' . $e($modelo) . '</div>' : '') . '
                    </td>
                    <td class="td-center">' . $e($unidad) . '</td>
                    <td class="td-center">' . number_format($cantidad, 2) . '</td>
                    <td class="td-right">' . $money($precio) . '</td>
                    <td class="td-right"><strong>' . $money($importe) . '</strong></td>
                </tr>
            ';
        })
        ->implode('');

    if ($itemsRows === '') {
        $itemsRows = '
            <tr>
                <td colspan="6" class="empty">No hay partidas registradas.</td>
            </tr>
        ';
    }

    return '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>' . $e($title) . '</title>
<style>
    @page {
        size: letter;
        margin: 16mm 14mm;
    }

    * {
        box-sizing: border-box;
    }

    body {
        font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
        color: #333333;
        font-size: 11px;
        line-height: 1.35;
        margin: 0;
        background: #ffffff;
    }

    .header {
        width: 100%;
        border-bottom: 1px solid #e5e5e5;
        padding-bottom: 18px;
        margin-bottom: 22px;
    }

    .header-table {
        width: 100%;
        border-collapse: collapse;
    }

    .header-left {
        width: 58%;
        vertical-align: top;
    }

    .header-right {
        width: 42%;
        vertical-align: top;
        text-align: right;
    }

    .company-row {
        display: table;
        width: 100%;
    }

    .logo-box {
        display: table-cell;
        width: 72px;
        vertical-align: top;
    }

    .logo-text {
        color: #007aff;
        font-weight: 700;
        font-size: 12px;
        line-height: 1;
        border: 1px solid #e6f0ff;
        border-radius: 8px;
        padding: 8px 6px;
        text-align: center;
        width: 58px;
    }

    .company-info {
        display: table-cell;
        vertical-align: top;
        padding-left: 6px;
    }

    .company-name {
        color: #111111;
        font-size: 17px;
        font-weight: 700;
        margin-bottom: 6px;
        letter-spacing: .02em;
    }

    .company-data {
        color: #475569;
        font-size: 9.5px;
        line-height: 1.55;
        text-transform: uppercase;
    }

    .quote-kicker {
        color: #64748b;
        font-size: 9px;
        font-weight: 700;
        letter-spacing: .14em;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .quote-title {
        color: #007aff;
        font-size: 17px;
        font-weight: 700;
        line-height: 1.25;
        margin-bottom: 8px;
    }

    .quote-date {
        color: #64748b;
        font-size: 10px;
        line-height: 1.55;
    }

    table.items {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        margin-top: 10px;
    }

    table.items th {
        background: #f9fafb;
        color: #111111;
        border: 1px solid #ebebeb;
        padding: 8px 7px;
        font-size: 9.5px;
        font-weight: 700;
        text-align: left;
        vertical-align: middle;
    }

    table.items td {
        border: 1px solid #ebebeb;
        padding: 8px 7px;
        vertical-align: top;
        font-size: 9.5px;
        word-wrap: break-word;
    }

    .td-center {
        text-align: center;
    }

    .td-right {
        text-align: right;
    }

    .item-muted {
        color: #64748b;
        font-size: 8.5px;
        margin-top: 3px;
    }

    .empty {
        text-align: center;
        color: #888888;
        padding: 20px;
    }

    .totals-wrap {
        width: 100%;
        margin-top: 20px;
    }

    .totals {
        width: 280px;
        margin-left: auto;
        border-collapse: collapse;
    }

    .totals td {
        padding: 7px 8px;
        border-bottom: 1px solid #ebebeb;
        font-size: 10px;
    }

    .totals .label {
        color: #64748b;
        text-align: left;
    }

    .totals .value {
        color: #111111;
        text-align: right;
        font-weight: 700;
    }

    .total-final td {
        border-bottom: 0;
        background: #f9fafb;
        font-size: 12px;
        font-weight: 700;
    }

    .footer-note {
        margin-top: 24px;
        padding-top: 12px;
        border-top: 1px solid #ebebeb;
        color: #64748b;
        font-size: 9px;
        line-height: 1.45;
    }
</style>
</head>
<body>

<div class="header">
    <table class="header-table">
        <tr>
            <td class="header-left">
                <div class="company-row">
                    <div class="logo-box">
                        <div class="logo-text">JURETO</div>
                    </div>

                    <div class="company-info">
                        <div class="company-name">JURETO S.A. DE C.V.</div>
                        <div class="company-data">
                            BERNARDO YARA 25, COL. PILARES, C.P. 52179, METEPEC, ESTADO DE MEXICO.<br>
                            5541937243, 8135515784 · RTORT@JURETO.COM.MX<br>
                            RFC: JUR2002196K4
                        </div>
                    </div>
                </div>
            </td>

            <td class="header-right">
                <div class="quote-kicker">Cotización</div>
                <div class="quote-title">' . $e($title) . '</div>
                <div class="quote-date">
                    ' . $e($fecha) . '<br>
                    Vigencia: ' . $e($vigencia) . ' días
                </div>
            </td>
        </tr>
    </table>
</div>

<table class="items">
    <colgroup>
        <col style="width: 5%;">
        <col style="width: 45%;">
        <col style="width: 10%;">
        <col style="width: 10%;">
        <col style="width: 15%;">
        <col style="width: 15%;">
    </colgroup>
    <thead>
        <tr>
            <th class="td-center">#</th>
            <th>Descripción</th>
            <th class="td-center">Unidad</th>
            <th class="td-center">Cantidad</th>
            <th class="td-right">Precio unitario</th>
            <th class="td-right">Importe</th>
        </tr>
    </thead>
    <tbody>
        ' . $itemsRows . '
    </tbody>
</table>

<div class="totals-wrap">
    <table class="totals">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">' . $money($subtotal) . '</td>
        </tr>
        <tr>
            <td class="label">Descuento</td>
            <td class="value">' . $money($descuento) . '</td>
        </tr>
        <tr>
            <td class="label">IVA</td>
            <td class="value">' . $money($iva) . '</td>
        </tr>
        <tr class="total-final">
            <td class="label">Total</td>
            <td class="value">' . $money($total) . '</td>
        </tr>
    </table>
</div>

<div class="footer-note">
    Precios expresados en moneda nacional. La presente cotización está sujeta a disponibilidad, validación técnica y vigencia indicada.
</div>

</body>
</html>';
}

public function clientePdf(PropuestaComercial $propuestaComercial)
{
    $html = $this->buildClienteExportHtml($propuestaComercial);

    $folio = $propuestaComercial->folio
        ?: ('TEOA' . str_pad((string) $propuestaComercial->id, 8, '0', STR_PAD_LEFT));

    $safeFolio = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $folio ?: 'cotizacion');

    return Pdf::loadHTML($html)
        ->setPaper('letter', 'portrait')
        ->download($safeFolio . '_cotizacion_cliente.pdf');
}

public function clienteWord(PropuestaComercial $propuestaComercial)
{
    $html = $this->buildClienteExportHtml($propuestaComercial);

    $folio = $propuestaComercial->folio
        ?: ('TEOA' . str_pad((string) $propuestaComercial->id, 8, '0', STR_PAD_LEFT));

    $safeFolio = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $folio ?: 'cotizacion');

    return response($html, 200, [
        'Content-Type' => 'application/msword; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="' . $safeFolio . '_cotizacion_cliente.doc"',
    ]);
}
}