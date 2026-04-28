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
        $allWords = $this->extractWords($queryNorm);
        $coreWords = $allWords->take(6);

        if ($allWords->isEmpty()) {
            $this->resetItem($item);
            $this->generateExternalReferences($item, $descripcion, $unidad);
            return;
        }

        $candidates = $this->findCatalogCandidates($descripcion, $allWords, $coreWords);

        if ($candidates->isEmpty()) {
            $this->resetItem($item);
            $this->generateExternalReferences($item, $descripcion, $unidad);
            return;
        }

        $unidadNorm = $this->normalizeText($unidad);

        $ranked = $candidates
            ->map(fn ($p) => $this->rankProduct($p, $queryNorm, $allWords, $coreWords, $unidadNorm))
            ->filter(fn ($row) => $row['score'] > 0 && $row['core_matches'] > 0)
            ->sortByDesc('score')
            ->take(20)
            ->values()
            ->all();

        if (empty($ranked)) {
            $this->resetItem($item);
            $this->generateExternalReferences($item, $descripcion, $unidad);
            return;
        }

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
                    'lexico_score' => $row['score'],
                    'matched_tokens' => $row['matched_tokens'] ?? [],
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

    protected function findCatalogCandidates(string $descripcion, $allWords, $coreWords)
    {
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

        return Product::query()
            ->where(function ($q) use ($columns, $allWords, $coreWords, $descripcion) {
                foreach ($coreWords as $word) {
                    foreach ($columns as $column) {
                        $q->orWhere($column, 'like', "%{$word}%");
                    }
                }

                foreach ($allWords as $word) {
                    foreach ($columns as $column) {
                        $q->orWhere($column, 'like', "%{$word}%");
                    }
                }

                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', '%' . $descripcion . '%');
                }
            })
            ->limit(350)
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
            'presentacion', 'presentaciones', 'color', 'colores', 'tipo', 'diseno', 'diseño',
            'estandar', 'standard', 'medida', 'medidas', 'marca', 'modelo', 'material',
            'producto', 'productos', 'solicitado', 'solicitada', 'suministro', 'servicio',
            'incluye', 'incluido', 'tamano', 'tamaño', 'grande', 'chico', 'mediano',
            'capacidad', 'compacta', 'ligera', 'rigida', 'rigidas', 'base', 'alta', 'baja',
            'rapido', 'rapida', 'cm', 'mm', 'kg', 'gr', 'gramos',
        ]);

        return collect(preg_split('/\s+/', $text))
            ->map(fn ($t) => trim((string) $t))
            ->filter(fn ($t) => $t !== '' && mb_strlen($t) >= 3 && !is_numeric($t))
            ->reject(fn ($t) => $stopWords->contains($t))
            ->map(fn ($t) => $this->singularize($t))
            ->unique()
            ->values();
    }

    protected function rankProduct(Product $product, string $queryNorm, $allWords, $coreWords, string $unidadNorm): array
    {
        $name = $this->normalizeText((string) ($product->name ?? ''));
        $sku = $this->normalizeText((string) ($product->sku ?? ''));
        $supplierSku = $this->normalizeText((string) ($product->supplier_sku ?? ''));
        $category = $this->normalizeText((string) ($product->category ?? ''));
        $tags = $this->normalizeText((string) ($product->tags ?? ''));
        $brand = $this->normalizeText((string) ($product->brand ?? ''));
        $description = $this->normalizeText((string) ($product->description ?? ''));
        $color = $this->normalizeText((string) ($product->color ?? ''));
        $unit = $this->normalizeText((string) ($product->unit ?? $product->unidad ?? ''));

        $haystack = trim(implode(' ', array_filter([$name, $sku, $supplierSku, $brand, $category, $tags, $description, $color, $unit])));
        $strong = trim(implode(' ', array_filter([$name, $category, $tags, $sku, $supplierSku])));

        $score = 0;
        $matched = [];

        foreach ($allWords as $word) {
            $ws = 0;

            if ($this->wordIn($name, $word)) $ws += 22;
            if ($this->wordIn($sku, $word)) $ws += 20;
            if ($this->wordIn($supplierSku, $word)) $ws += 20;
            if ($this->wordIn($category, $word)) $ws += 15;
            if ($this->wordIn($tags, $word)) $ws += 14;
            if ($this->wordIn($brand, $word)) $ws += 8;
            if ($this->wordIn($description, $word)) $ws += 6;
            if ($this->wordIn($color, $word)) $ws += 6;
            if ($this->wordIn($unit, $word)) $ws += 4;

            if ($ws > 0) {
                $matched[] = $word;
                $score += min($ws, 26);
            }
        }

        $coreMatches = 0;

        foreach ($coreWords as $word) {
            if ($this->wordIn($strong, $word) || $this->wordIn($haystack, $word)) {
                $coreMatches++;
                $score += 15;
            }
        }

        $firstTwo = $allWords->take(2)->implode(' ');

        if ($firstTwo !== '' && $this->phraseIn($name, $firstTwo)) {
            $score += 80;
        } elseif ($firstTwo !== '' && $this->phraseIn($strong, $firstTwo)) {
            $score += 50;
        }

        if ($this->phraseIn($name, $queryNorm)) {
            $score += 40;
        } elseif ($this->phraseIn($haystack, $queryNorm)) {
            $score += 20;
        }

        similar_text($queryNorm, $name, $ns);
        similar_text($queryNorm, $haystack, $gs);

        $score += min((float) $ns * 0.3, 15);
        $score += min((float) $gs * 0.1, 8);

        $unidadCoincide = $unidadNorm !== '' && $this->wordIn($haystack, $unidadNorm);

        if ($unidadCoincide) {
            $score += 5;
        }

        return [
            'product' => $product,
            'score' => round(max($score, 0), 2),
            'core_matches' => $coreMatches,
            'unidad_coincide' => $unidadCoincide,
            'matched_tokens' => array_values(array_unique($matched)),
            'missing_tokens' => [],
            'motivo' => 'Ranking léxico previo a IA',
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