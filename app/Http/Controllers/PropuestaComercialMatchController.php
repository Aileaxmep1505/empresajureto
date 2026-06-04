<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PropuestaComercial;
use App\Models\PropuestaComercialExternalMatch;
use App\Models\PropuestaComercialItem;
use App\Models\PropuestaComercialMatch;
use App\Services\AiMatchingService;
use App\Services\ExternalProductReferenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PropuestaComercialMatchController extends Controller
{
    public function __construct(
        protected AiMatchingService $aiService,
        protected ExternalProductReferenceService $externalReferenceService,
    ) {}

    public function suggest(PropuestaComercialItem $item)
    {
        $this->generateMatchesForItem($item);

        return back()->with('status', 'Sugerencias generadas correctamente.');
    }

    public function suggestAll(PropuestaComercial $propuestaComercial)
    {
        foreach ($propuestaComercial->items()->orderBy('sort')->get() as $item) {
            $this->generateMatchesForItem($item);
        }

        return back()->with('status', 'Sugerencias generadas para todos los renglones.');
    }

    public function suggestJson(PropuestaComercialItem $item)
    {
        $this->generateMatchesForItem($item);

        $item->refresh()->load([
            'matches.product',
            'externalMatches',
            'productoSeleccionado',
            'propuesta',
        ]);

        return response()->json([
            'ok' => true,
            'item' => $this->ajaxSerializeItem($item),
            'summary' => $this->ajaxSummary($item->propuesta),
        ]);
    }

    public function suggestAllJson(PropuestaComercial $propuestaComercial)
    {
        foreach ($propuestaComercial->items()->orderBy('sort')->get() as $item) {
            $this->generateMatchesForItem($item);
        }

        $propuestaComercial->refresh()->load([
            'items.matches.product',
            'items.externalMatches',
            'items.productoSeleccionado',
        ]);

        return response()->json([
            'ok' => true,
            'items' => $propuestaComercial->items->sortBy('sort')->values()->map(fn ($item) => $this->ajaxSerializeItem($item)),
            'summary' => $this->ajaxSummary($propuestaComercial),
        ]);
    }

    public function select(Request $request, PropuestaComercialItem $item, PropuestaComercialMatch $match)
    {
        DB::transaction(function () use ($item, $match) {
            PropuestaComercialMatch::where('propuesta_comercial_item_id', $item->id)
                ->update(['seleccionado' => false]);

            $match->update(['seleccionado' => true]);

            $item->update([
                'producto_seleccionado_id' => $match->product_id,
                'match_score' => $match->score,
                'status' => 'matched',
            ]);

            $this->updateParentStatus($item);
        });

        return back()->with('status', 'Producto seleccionado correctamente.');
    }

    public function price(Request $request, PropuestaComercialItem $item)
    {
        $data = $request->validate([
            'cantidad_cotizada' => ['required', 'numeric', 'min:0.01'],
            'costo_unitario' => ['required', 'numeric', 'min:0'],
            'porcentaje_utilidad' => ['required', 'numeric', 'min:0'],
        ]);

        $precioUnitario = (float) $data['costo_unitario'] * (1 + ((float) $data['porcentaje_utilidad'] / 100));
        $subtotal = $precioUnitario * (float) $data['cantidad_cotizada'];

        DB::transaction(function () use ($item, $data, $precioUnitario, $subtotal) {
            $meta = is_array($item->meta) ? $item->meta : [];
            $meta['item_margin_pct'] = (float) $data['porcentaje_utilidad'];

            $item->update([
                'cantidad_cotizada' => $data['cantidad_cotizada'],
                'costo_unitario' => $data['costo_unitario'],
                'precio_unitario' => round($precioUnitario, 2),
                'subtotal' => round($subtotal, 2),
                'status' => 'priced',
                'meta' => $meta,
            ]);

            $propuesta = $item->propuesta()->first();

            if ($propuesta) {
                $subtotalGeneral = (float) $propuesta->items()->sum('subtotal');
                $descuento = round($subtotalGeneral * ((float) $propuesta->porcentaje_descuento / 100), 2);
                $base = max($subtotalGeneral - $descuento, 0);
                $impuesto = round($base * ((float) $propuesta->porcentaje_impuesto / 100), 2);

                $propuesta->update([
                    'subtotal' => round($subtotalGeneral, 2),
                    'descuento_total' => $descuento,
                    'impuesto_total' => $impuesto,
                    'total' => round($base + $impuesto, 2),
                    'status' => 'priced',
                ]);
            }
        });

        return back()->with('status', 'Precio aplicado correctamente.');
    }

    protected function generateMatchesForItem(PropuestaComercialItem $item): void
    {
        PropuestaComercialMatch::where('propuesta_comercial_item_id', $item->id)->delete();
        PropuestaComercialExternalMatch::where('propuesta_comercial_item_id', $item->id)->delete();

        $descripcion = trim((string) $item->descripcion_original);
        $unidad = trim((string) ($item->unidad_solicitada ?? ''));

        if ($descripcion === '') {
            $this->resetItem($item);
            return;
        }

        $queryNorm = $this->normalizeText($descripcion);
        $queryTokens = $this->extractWords($queryNorm);

        if ($queryTokens->isEmpty()) {
            $queryTokens = collect(preg_split('/\s+/', $queryNorm))
                ->map(fn ($t) => trim((string) $t))
                ->filter(fn ($t) => $t !== '')
                ->unique()
                ->values();
        }

        if ($queryTokens->isEmpty()) {
            $this->resetItem($item);
            $this->generateExternalReferences($item, $descripcion, $unidad);
            return;
        }

        // ── EXPANSIÓN SEMÁNTICA POR IA ──
        // La IA entiende que "bote de basura" = "cesto de basura" = "papelera", etc.
        // Así el catálogo correcto SÍ llega a la búsqueda aunque tenga otro nombre.
        $expansion = $this->aiService->expandSearchTerms($descripcion, $unidad);

        $expansionTerms = collect($expansion['terminos'] ?? [])
            ->map(fn ($t) => $this->normalizeText((string) $t))
            ->filter(fn ($t) => $t !== '' && mb_strlen($t) >= 3)
            ->unique()
            ->values();

        $candidates = $this->findCatalogCandidates($queryNorm, $queryTokens, $expansionTerms);

        if ($candidates->isEmpty()) {
            $this->resetItem($item);
            $this->generateExternalReferences($item, $descripcion, $unidad);
            return;
        }

        $unidadNorm = $this->normalizeText($unidad);

        $ranked = $candidates
            ->map(fn ($p) => $this->rankProduct($p, $queryNorm, $queryTokens, $expansionTerms, $unidadNorm))
            ->filter(fn ($row) => $row['score'] > 0 && $row['relevant'])
            ->sortByDesc('score')
            ->take(25)
            ->values()
            ->all();

        if (empty($ranked)) {
            $this->resetItem($item);
            $this->generateExternalReferences($item, $descripcion, $unidad);
            return;
        }

        // La IA decide con sentido común (mismo tipo aunque cambie el nombre; rechaza tipos distintos).
        $aiApproved = $this->aiService->validateCandidates(
            descripcionOriginal: $descripcion,
            unidadSolicitada: $unidad,
            candidates: $ranked,
        );

        if (empty($aiApproved)) {
            $this->resetItem($item);
            $this->generateExternalReferences($item, $descripcion, $unidad);
            return;
        }

        $top3 = collect($aiApproved)
            ->sortByDesc('ai_score')
            ->take(3)
            ->values();

        foreach ($top3 as $rank => $row) {
            PropuestaComercialMatch::create([
                'propuesta_comercial_item_id' => $item->id,
                'product_id' => $row['product']->id,
                'rank' => $rank + 1,
                'score' => $row['ai_score'],
                'unidad_coincide' => $row['unidad_coincide'],
                'seleccionado' => false,
                'motivo' => $row['ai_razon'],
                'meta' => [
                    'product_name' => $row['product']->name,
                    'sku' => $row['product']->sku,
                    'lexico_score' => $row['score'] ?? null,
                    'coverage' => $row['coverage'] ?? null,
                    'matched_tokens' => $row['matched_tokens'] ?? [],
                    'missing_tokens' => $row['missing_tokens'] ?? [],
                    'expansion_terms' => $expansionTerms->all(),
                ],
            ]);
        }

        $localCount = PropuestaComercialMatch::where('propuesta_comercial_item_id', $item->id)->count();

        if ($localCount < 3) {
            $this->generateExternalReferences($item, $descripcion, $unidad);
        }

        $best = PropuestaComercialMatch::where('propuesta_comercial_item_id', $item->id)
            ->orderByDesc('score')
            ->first();

        if ($best && (float) $best->score >= 45) {
            PropuestaComercialMatch::where('propuesta_comercial_item_id', $item->id)
                ->update(['seleccionado' => false]);

            $best->update(['seleccionado' => true]);

            $item->update([
                'producto_seleccionado_id' => $best->product_id,
                'match_score' => $best->score,
                'status' => 'matched',
            ]);
        } else {
            $this->resetItem($item);
        }

        $this->updateParentStatus($item);
    }

    /**
     * Recuperación amplia: frase completa + bigramas + tokens + SINÓNIMOS de la IA,
     * sobre todas las columnas. Así el producto correcto llega aunque su nombre difiera.
     */
    protected function findCatalogCandidates(string $queryNorm, $queryTokens, $expansionTerms = null)
    {
        $expansionTerms = $expansionTerms ?? collect();

        $table = (new Product())->getTable();

        $columns = collect([
            'name',
            'sku',
            'supplier_sku',
            'brand',
            'category',
            'tags',
            'description',
            'color',
            'unit',
            'unidad',
            'model',
            'modelo',
        ])->filter(fn ($column) => Schema::hasColumn($table, $column))->values();

        if ($columns->isEmpty()) {
            return collect();
        }

        $tokens = $queryTokens->values()->all();

        $bigrams = [];
        for ($i = 0; $i + 2 <= count($tokens); $i++) {
            $bigrams[] = $tokens[$i] . ' ' . $tokens[$i + 1];
        }

        // Términos de la IA (pueden ser de una o varias palabras).
        $expansion = $expansionTerms->filter(fn ($t) => $t !== '')->unique()->values()->all();

        return Product::query()
            ->where(function ($q) use ($columns, $tokens, $bigrams, $expansion, $queryNorm) {
                foreach ($columns as $column) {
                    // Frase completa
                    $q->orWhere($column, 'like', '%' . $queryNorm . '%');

                    // Sinónimos / variantes de la IA (lo más importante para el recall semántico)
                    foreach ($expansion as $term) {
                        $q->orWhere($column, 'like', '%' . $term . '%');
                    }

                    // Bigramas
                    foreach ($bigrams as $bigram) {
                        $q->orWhere($column, 'like', '%' . $bigram . '%');
                    }

                    // Tokens individuales
                    foreach ($tokens as $token) {
                        $q->orWhere($column, 'like', '%' . $token . '%');
                    }
                }
            })
            ->limit(450)
            ->get();
    }

    protected function generateExternalReferences(PropuestaComercialItem $item, string $descripcion, string $unidad): void
    {
        PropuestaComercialExternalMatch::where('propuesta_comercial_item_id', $item->id)->delete();

        $externalResults = $this->externalReferenceService->searchTopN(
            descripcion: $descripcion,
            unidad: $unidad,
            cantidadMinima: $item->cantidad_minima,
            cantidadMaxima: $item->cantidad_maxima,
            cantidadCotizada: $item->cantidad_cotizada,
            limit: 7
        );

        foreach ($externalResults as $index => $external) {
            PropuestaComercialExternalMatch::create([
                'propuesta_comercial_item_id' => $item->id,
                'rank' => $index + 1,
                'source' => $external['source'] ?? 'Internet',
                'title' => $external['title'],
                'seller' => $external['seller'] ?? null,
                'price' => $external['price'] ?? null,
                'currency' => $external['currency'] ?? 'MXN',
                'url' => $external['url'],
                'score' => $external['score'] ?? 0,
                'meta' => [
                    'family' => $external['family'] ?? null,
                    'matched_tokens' => $external['matched_tokens'] ?? [],
                    'missing_tokens' => $external['missing_tokens'] ?? [],
                    'thumbnail' => $external['thumbnail'] ?? null,
                    'condition' => $external['condition'] ?? null,
                    'raw' => $external['raw'] ?? null,
                    'cantidad_minima' => $item->cantidad_minima,
                    'cantidad_maxima' => $item->cantidad_maxima,
                    'cantidad_cotizada' => $item->cantidad_cotizada,
                    'unidad_solicitada' => $unidad,
                ],
            ]);
        }
    }

    protected function extractWords(string $text)
    {
        $stopWords = collect([
            'con', 'sin', 'para', 'por', 'del', 'las', 'los', 'una', 'uno', 'unos', 'unas',
            'pieza', 'piezas', 'pza', 'pzas', 'caja', 'cajas', 'paquete', 'paquetes',
            'presentacion', 'presentaciones',
            'producto', 'productos', 'solicitado', 'solicitada', 'suministro',
            'incluye', 'incluido',
            'cm', 'mm', 'kg', 'gr', 'gramos',
        ]);

        return collect(preg_split('/\s+/', $text))
            ->map(fn ($t) => trim((string) $t))
            ->filter(fn ($t) => $t !== '' && mb_strlen($t) >= 3 && !is_numeric($t))
            ->reject(fn ($t) => $stopWords->contains($t))
            ->map(fn ($t) => $this->singularize($t))
            ->unique()
            ->values();
    }

    /**
     * Ranking sobre la descripción completa + sinónimos de la IA.
     * Un producto con nombre distinto pero del mismo tipo (vía sinónimo) sube en el ranking
     * para que llegue a la validación final de la IA.
     */
    protected function rankProduct(Product $product, string $queryNorm, $queryTokens, $expansionTerms, string $unidadNorm): array
    {
        $expansionTerms = $expansionTerms ?? collect();

        $name = $this->normalizeText((string) ($product->name ?? ''));
        $sku = $this->normalizeText((string) ($product->sku ?? ''));
        $supplierSku = $this->normalizeText((string) ($product->supplier_sku ?? ''));
        $category = $this->normalizeText((string) ($product->category ?? ''));
        $tags = $this->normalizeText((string) ($product->tags ?? ''));
        $brand = $this->normalizeText((string) ($product->brand ?? ''));
        $model = $this->normalizeText((string) ($product->model ?? $product->modelo ?? ''));
        $description = $this->normalizeText((string) ($product->description ?? ''));
        $color = $this->normalizeText((string) ($product->color ?? ''));
        $unit = $this->normalizeText((string) ($product->unit ?? $product->unidad ?? ''));

        $haystack = trim(implode(' ', array_filter([
            $name, $sku, $supplierSku, $brand, $model, $category, $tags, $description, $color, $unit,
        ])));

        $fieldWeights = [
            [$name, 24],
            [$sku, 18],
            [$supplierSku, 16],
            [$category, 16],
            [$tags, 15],
            [$brand, 12],
            [$model, 12],
            [$description, 10],
            [$color, 8],
            [$unit, 6],
        ];

        $score = 0.0;
        $matched = [];

        foreach ($queryTokens as $token) {
            $best = 0;

            foreach ($fieldWeights as [$field, $weight]) {
                if ($field === '') {
                    continue;
                }

                if ($this->wordIn($field, $token)) {
                    $best = max($best, $weight);
                } elseif (str_contains($field, $token)) {
                    $best = max($best, (int) round($weight * 0.5));
                }
            }

            if ($best > 0) {
                $matched[] = $token;
                $score += $best;
            }
        }

        $matched = array_values(array_unique($matched));
        $total = max($queryTokens->count(), 1);
        $matchedCount = count($matched);
        $coverage = $matchedCount / $total;

        $score += $coverage * 70;

        // ── Coincidencias por SINÓNIMO de la IA (mismo tipo, otro nombre) ──
        $expansionMatched = [];

        foreach ($expansionTerms as $term) {
            if ($term === '') {
                continue;
            }

            if (str_contains($term, ' ')) {
                if ($this->phraseIn($haystack, $term)) {
                    $expansionMatched[] = $term;
                    $score += 22; // frase sinónima en el texto del producto
                }
            } elseif ($this->wordIn($haystack, $term)) {
                $expansionMatched[] = $term;
                $score += 14; // palabra sinónima
            }
        }

        $expansionHit = count($expansionMatched) > 0;

        // Frases / n-gramas de la descripción original.
        $phraseHit = false;
        $tokensArr = $queryTokens->values()->all();

        for ($n = 3; $n >= 2; $n--) {
            for ($i = 0; $i + $n <= count($tokensArr); $i++) {
                $gram = trim(implode(' ', array_slice($tokensArr, $i, $n)));

                if ($gram === '') {
                    continue;
                }

                if ($this->phraseIn($name, $gram)) {
                    $score += $n * 14;
                    $phraseHit = true;
                } elseif ($this->phraseIn($haystack, $gram)) {
                    $score += $n * 8;
                    $phraseHit = true;
                }
            }
        }

        if ($this->phraseIn($name, $queryNorm)) {
            $score += 60;
            $phraseHit = true;
        } elseif ($this->phraseIn($haystack, $queryNorm)) {
            $score += 30;
            $phraseHit = true;
        }

        similar_text($queryNorm, $name, $simName);
        similar_text($queryNorm, $haystack, $simAll);

        $score += min((float) $simName * 0.25, 14);
        $score += min((float) $simAll * 0.18, 12);

        $unidadCoincide = $unidadNorm !== '' && $this->wordIn($haystack, $unidadNorm);

        if ($unidadCoincide) {
            $score += 5;
        }

        // Relevante si: hay frase, hay sinónimo, o buena cobertura de la descripción.
        $relevant = $phraseHit
            || $expansionHit
            || ($total <= 2 ? $matchedCount >= 1 : ($coverage >= 0.34 || $matchedCount >= 2));

        $missing = $queryTokens
            ->reject(fn ($t) => in_array($t, $matched, true))
            ->values()
            ->all();

        return [
            'product' => $product,
            'score' => round(max($score, 0), 2),
            'coverage' => round($coverage, 3),
            'matched_count' => $matchedCount,
            'core_matches' => $matchedCount,
            'phrase_hit' => $phraseHit,
            'expansion_hit' => $expansionHit,
            'expansion_matched' => $expansionMatched,
            'relevant' => $relevant,
            'unidad_coincide' => $unidadCoincide,
            'matched_tokens' => $matched,
            'missing_tokens' => $missing,
            'motivo' => 'Ranking por descripción + sinónimos (cobertura ' . round($coverage * 100) . '%)',
        ];
    }

    protected function resetItem(PropuestaComercialItem $item): void
    {
        $item->update([
            'producto_seleccionado_id' => null,
            'match_score' => null,
            'status' => 'pending',
        ]);

        $this->updateParentStatus($item);
    }

    protected function normalizeText(string $text): string
    {
        $text = Str::lower($text);
        $text = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'u', 'n'], $text);
        $text = str_replace(['wire-o', 'post-it'], ['wireo', 'postit'], $text);
        $text = preg_replace('/[^a-z0-9\s\/\.\-]+/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }

    protected function singularize(string $token): string
    {
        if (mb_strlen($token) <= 4) {
            return $token;
        }

        if (Str::endsWith($token, 'ces')) {
            return mb_substr($token, 0, -3) . 'z';
        }

        if (Str::endsWith($token, 'es')) {
            return mb_substr($token, 0, -2);
        }

        if (Str::endsWith($token, 's')) {
            return mb_substr($token, 0, -1);
        }

        return $token;
    }

    protected function wordIn(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return false;
        }

        return (bool) preg_match('/\b' . preg_quote($needle, '/') . '\b/u', ' ' . $haystack . ' ');
    }

    protected function phraseIn(string $haystack, string $phrase): bool
    {
        return $phrase !== '' && str_contains($haystack, $phrase);
    }

    protected function updateParentStatus(PropuestaComercialItem $item): void
    {
        $propuesta = $item->propuesta()->first();

        if (!$propuesta) {
            return;
        }

        if ($propuesta->items()->where('status', 'priced')->exists()) {
            $propuesta->update(['status' => 'priced']);
        } elseif ($propuesta->items()->where('status', 'matched')->exists()) {
            $propuesta->update(['status' => 'matched']);
        } else {
            $propuesta->update(['status' => 'draft']);
        }
    }

    protected function ajaxSummary(?PropuestaComercial $propuesta): array
    {
        if (!$propuesta) {
            return [];
        }

        $propuesta->refresh()->load([
            'items.matches.product',
            'items.externalMatches',
            'items.productoSeleccionado',
        ]);

        $items = $propuesta->items;

        $exact = 0;
        $similar = 0;
        $notFound = 0;

        foreach ($items as $item) {
            $status = $this->ajaxStatusKey($item);

            if ($status === 'exact') {
                $exact++;
            } elseif ($status === 'similar') {
                $similar++;
            } else {
                $notFound++;
            }
        }

        $subtotalSale = (float) $items->sum('subtotal');
        $subtotalCost = (float) $items->sum(fn ($i) => ((float) $i->costo_unitario) * ((float) ($i->cantidad_cotizada ?: 0)));
        $profit = $subtotalSale - $subtotalCost;
        $margin = $subtotalCost > 0 ? round(($profit / $subtotalCost) * 100) : 0;

        return [
            'exact' => $exact,
            'similar' => $similar,
            'not_found' => $notFound,
            'subtotal_sale' => $subtotalSale,
            'subtotal_cost' => $subtotalCost,
            'profit' => $profit,
            'margin' => $margin,
            'total_items' => $items->count(),
        ];
    }

    protected function ajaxSerializeItem(PropuestaComercialItem $item): array
    {
        $item->loadMissing([
            'matches.product',
            'externalMatches',
            'productoSeleccionado',
            'propuesta',
        ]);

        $selectedMatch = $item->matches->firstWhere('seleccionado', true) ?: $item->matches->sortByDesc('score')->first();

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
            'match_score' => (float) ($item->match_score ?: optional($selectedMatch)->score),
            'status_key' => $this->ajaxStatusKey($item),
            'ui_status' => data_get($item->meta, 'ui_status', 'pending'),
            'item_margin_pct' => (float) data_get($item->meta, 'item_margin_pct', optional($item->propuesta)->porcentaje_utilidad ?? 25),
            'manual_external_supplier' => data_get($item->meta, 'external_supplier'),
            'manual_external_link' => data_get($item->meta, 'external_link'),
            'manual_catalog_product_name' => data_get($item->meta, 'catalog_product_name_manual'),
            'producto_seleccionado' => $item->productoSeleccionado ? [
                'id' => $item->productoSeleccionado->id,
                'name' => $item->productoSeleccionado->name,
                'sku' => $item->productoSeleccionado->sku,
                'brand' => $item->productoSeleccionado->brand,
                'stock' => $item->productoSeleccionado->stock ?? 0,
            ] : null,
            'matches' => $item->matches->sortBy('rank')->values()->map(function ($match) {
                $p = $match->product;

                return [
                    'id' => $match->id,
                    'rank' => $match->rank,
                    'score' => (float) $match->score,
                    'seleccionado' => (bool) $match->seleccionado,
                    'unidad_coincide' => (bool) $match->unidad_coincide,
                    'motivo' => $match->motivo,
                    'product' => $p ? [
                        'id' => $p->id,
                        'name' => $p->name,
                        'sku' => $p->sku,
                        'brand' => $p->brand,
                        'stock' => $p->stock ?? 0,
                        'cost' => (float) ($p->cost ?? $p->costo ?? $p->purchase_price ?? 0),
                        'price' => (float) ($p->price ?? $p->precio ?? $p->sale_price ?? 0),
                    ] : null,
                ];
            })->all(),
            'external_matches' => $item->externalMatches->sortBy('rank')->values()->map(function ($external) {
                return [
                    'id' => $external->id,
                    'rank' => $external->rank,
                    'source' => $external->source,
                    'title' => $external->title,
                    'seller' => $external->seller,
                    'price' => (float) $external->price,
                    'currency' => $external->currency,
                    'url' => $external->url,
                    'score' => (float) $external->score,
                ];
            })->all(),
        ];
    }

    protected function ajaxStatusKey(PropuestaComercialItem $item): string
    {
        $selectedMatch = $item->matches->firstWhere('seleccionado', true) ?: $item->matches->sortByDesc('score')->first();
        $score = (float) ($item->match_score ?: optional($selectedMatch)->score);

        if ($item->producto_seleccionado_id && $score >= 85) {
            return 'exact';
        }

        if ($item->producto_seleccionado_id || $item->matches->count() > 0) {
            return 'similar';
        }

        return 'not_found';
    }
}