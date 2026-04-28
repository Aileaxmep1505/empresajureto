<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PropuestaComercial;
use App\Models\PropuestaComercialItem;
use App\Models\PropuestaComercialMatch;
use App\Services\ExternalProductReferenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PropuestaComercialAjaxController extends Controller
{
    public function storeItem(Request $request, PropuestaComercial $propuestaComercial)
    {
        $data = $request->validate([
            'descripcion_original' => ['required', 'string', 'max:3000'],
            'unidad_solicitada' => ['nullable', 'string', 'max:100'],
            'cantidad_cotizada' => ['nullable', 'numeric', 'min:0.01'],
            'costo_unitario' => ['nullable', 'numeric', 'min:0'],
            'porcentaje_utilidad' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data, $propuestaComercial) {
            $cantidad = (float) ($data['cantidad_cotizada'] ?? 1);
            $costo = (float) ($data['costo_unitario'] ?? 0);
            $margen = (float) ($data['porcentaje_utilidad'] ?? $propuestaComercial->porcentaje_utilidad ?? 25);

            $precio = round($costo * (1 + ($margen / 100)), 2);
            $subtotal = round($precio * $cantidad, 2);

            $sort = ((int) $propuestaComercial->items()->max('sort')) + 1;

            $propuestaComercial->items()->create([
                'sort' => $sort,
                'partida_numero' => $sort,
                'descripcion_original' => $data['descripcion_original'],
                'unidad_solicitada' => $data['unidad_solicitada'] ?? 'pz',
                'cantidad_minima' => $cantidad,
                'cantidad_maxima' => $cantidad,
                'cantidad_cotizada' => $cantidad,
                'costo_unitario' => $costo,
                'precio_unitario' => $precio,
                'subtotal' => $subtotal,
                'status' => 'pending',
                'meta' => [
                    'ui_status' => 'pending',
                    'item_margin_pct' => $margen,
                    'created_manually' => true,
                ],
            ]);

            $this->recalculateProposalTotals($propuestaComercial);
        });

        return response()->json([
            'ok' => true,
            'items' => $this->serializedItems($propuestaComercial),
            'summary' => $this->summary($propuestaComercial),
        ]);
    }

    public function updateItem(Request $request, PropuestaComercialItem $item)
    {
        $data = $request->validate([
            'descripcion_original' => ['nullable', 'string', 'max:3000'],
            'unidad_solicitada' => ['nullable', 'string', 'max:100'],
            'cantidad_cotizada' => ['nullable', 'numeric', 'min:0'],
            'cantidad_minima' => ['nullable', 'numeric', 'min:0'],
            'cantidad_maxima' => ['nullable', 'numeric', 'min:0'],
            'costo_unitario' => ['nullable', 'numeric', 'min:0'],
            'porcentaje_utilidad' => ['nullable', 'numeric', 'min:0'],
            'external_supplier' => ['nullable', 'string', 'max:255'],
            'external_link' => ['nullable', 'string', 'max:2000'],
            'catalog_product_name' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($item, $data) {
            $item->loadMissing('propuesta');

            $cantidad = (float) ($data['cantidad_cotizada'] ?? $item->cantidad_cotizada ?? 1);
            $costo = (float) ($data['costo_unitario'] ?? $item->costo_unitario ?? 0);
            $margen = (float) ($data['porcentaje_utilidad'] ?? data_get($item->meta, 'item_margin_pct', optional($item->propuesta)->porcentaje_utilidad ?? 25));

            $precio = round($costo * (1 + ($margen / 100)), 2);
            $subtotal = round($precio * $cantidad, 2);

            $meta = is_array($item->meta) ? $item->meta : [];
            $meta['item_margin_pct'] = $margen;

            if (array_key_exists('external_supplier', $data)) {
                $meta['external_supplier'] = $data['external_supplier'];
            }

            if (array_key_exists('external_link', $data)) {
                $meta['external_link'] = $data['external_link'];
            }

            if (array_key_exists('catalog_product_name', $data)) {
                $meta['catalog_product_name_manual'] = $data['catalog_product_name'];
            }

            $item->update([
                'descripcion_original' => $data['descripcion_original'] ?? $item->descripcion_original,
                'unidad_solicitada' => $data['unidad_solicitada'] ?? $item->unidad_solicitada,
                'cantidad_cotizada' => $cantidad,
                'cantidad_minima' => $data['cantidad_minima'] ?? $item->cantidad_minima,
                'cantidad_maxima' => $data['cantidad_maxima'] ?? $item->cantidad_maxima,
                'costo_unitario' => $costo,
                'precio_unitario' => $precio,
                'subtotal' => $subtotal,
                'status' => $item->producto_seleccionado_id ? 'priced' : $item->status,
                'meta' => $meta,
            ]);

            $this->recalculateProposalTotals($item->propuesta);
        });

        $item->refresh()->load([
            'matches.product',
            'externalMatches',
            'productoSeleccionado',
            'propuesta',
        ]);

        return response()->json([
            'ok' => true,
            'item' => $this->serializeItem($item),
            'summary' => $this->summary($item->propuesta),
        ]);
    }

    public function updateStatus(Request $request, PropuestaComercialItem $item)
    {
        $data = $request->validate([
            'ui_status' => ['required', 'string', 'in:accepted_item,rejected_item,manual_review,pending'],
        ]);

        $meta = is_array($item->meta) ? $item->meta : [];
        $meta['ui_status'] = $data['ui_status'];

        $item->update(['meta' => $meta]);

        $item->refresh()->load([
            'matches.product',
            'externalMatches',
            'productoSeleccionado',
            'propuesta',
        ]);

        return response()->json([
            'ok' => true,
            'item' => $this->serializeItem($item),
            'summary' => $this->summary($item->propuesta),
        ]);
    }

    public function selectMatch(Request $request, PropuestaComercialItem $item, PropuestaComercialMatch $match)
    {
        if ((int) $match->propuesta_comercial_item_id !== (int) $item->id) {
            return response()->json([
                'ok' => false,
                'message' => 'La coincidencia no pertenece a esta partida.',
            ], 422);
        }

        DB::transaction(function () use ($item, $match) {
            PropuestaComercialMatch::where('propuesta_comercial_item_id', $item->id)
                ->update(['seleccionado' => false]);

            $match->update(['seleccionado' => true]);

            $product = $match->product;
            $item->loadMissing('propuesta');

            $cantidad = (float) ($item->cantidad_cotizada ?: 1);
            $costo = (float) ($product->cost ?? $product->costo ?? $item->costo_unitario ?? 0);
            $margen = (float) data_get($item->meta, 'item_margin_pct', optional($item->propuesta)->porcentaje_utilidad ?? 25);

            $precio = round($costo * (1 + ($margen / 100)), 2);
            $subtotal = round($precio * $cantidad, 2);

            $meta = is_array($item->meta) ? $item->meta : [];
            $meta['ui_status'] = 'accepted_item';
            $meta['item_margin_pct'] = $margen;

            $item->update([
                'producto_seleccionado_id' => $match->product_id,
                'match_score' => $match->score,
                'costo_unitario' => $costo,
                'precio_unitario' => $precio,
                'subtotal' => $subtotal,
                'status' => 'priced',
                'meta' => $meta,
            ]);

            $this->recalculateProposalTotals($item->propuesta);
        });

        $item->refresh()->load([
            'matches.product',
            'externalMatches',
            'productoSeleccionado',
            'propuesta',
        ]);

        return response()->json([
            'ok' => true,
            'item' => $this->serializeItem($item),
            'summary' => $this->summary($item->propuesta),
        ]);
    }

    public function manualSearch(Request $request, PropuestaComercial $propuestaComercial)
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:1'],
            'internet' => ['nullable', 'boolean'],
            'item_id' => ['nullable', 'integer'],
        ]);

        $q = trim($data['q']);
        $normalized = $this->normalizeSearchText($q);
        $tokens = $this->searchTokens($normalized);

        $table = (new Product())->getTable();

        $productsQuery = Product::query();

        $searchableColumns = collect([
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
        ])->filter(fn ($col) => Schema::hasColumn($table, $col))->values();

        if ($searchableColumns->isNotEmpty()) {
            $productsQuery->where(function ($query) use ($tokens, $q, $searchableColumns) {
                foreach ($tokens as $token) {
                    foreach ($searchableColumns as $column) {
                        $query->orWhere($column, 'like', "%{$token}%");
                    }
                }

                foreach ($searchableColumns as $column) {
                    $query->orWhere($column, 'like', "%{$q}%");
                }
            });
        }

        $products = $productsQuery
            ->limit(160)
            ->get()
            ->map(function ($p) use ($normalized, $tokens) {
                $score = $this->scoreManualProduct($p, $normalized, $tokens);

                return [
                    'id' => $p->id,
                    'name' => $p->name ?? $p->title ?? 'Producto sin nombre',
                    'sku' => $p->sku ?? $p->supplier_sku ?? null,
                    'brand' => $p->brand ?? null,
                    'category' => $p->category ?? null,
                    'color' => $p->color ?? null,
                    'unit' => $p->unit ?? $p->unidad ?? null,
                    'stock' => $p->stock ?? 0,
                    'cost' => (float) ($p->cost ?? $p->costo ?? $p->purchase_price ?? 0),
                    'price' => (float) ($p->price ?? $p->precio ?? $p->sale_price ?? 0),
                    'similarity_pct' => $score,
                ];
            })
            ->sortByDesc('similarity_pct')
            ->take(30)
            ->values();

        $internetResults = collect();

        if ($request->boolean('internet')) {
            $item = null;

            if (!empty($data['item_id'])) {
                $item = PropuestaComercialItem::find($data['item_id']);
            }

            $internetResults = collect(app(ExternalProductReferenceService::class)->searchTopN(
                descripcion: $q,
                unidad: $item?->unidad_solicitada ?? '',
                cantidadMinima: $item?->cantidad_minima,
                cantidadMaxima: $item?->cantidad_maxima,
                cantidadCotizada: $item?->cantidad_cotizada,
                limit: 7
            ))->values();
        }

        return response()->json([
            'ok' => true,
            'products' => $products,
            'internet' => $internetResults,
        ]);
    }

    public function reorderItems(Request $request, PropuestaComercial $propuestaComercial)
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*' => ['integer'],
        ]);

        DB::transaction(function () use ($data, $propuestaComercial) {
            foreach ($data['items'] as $index => $id) {
                PropuestaComercialItem::where('id', $id)
                    ->where('propuesta_comercial_id', $propuestaComercial->id)
                    ->update(['sort' => $index + 1]);
            }
        });

        return response()->json([
            'ok' => true,
            'items' => $this->serializedItems($propuestaComercial),
            'summary' => $this->summary($propuestaComercial),
        ]);
    }

    public function updateGlobalMargin(Request $request, PropuestaComercial $propuestaComercial)
    {
        $data = $request->validate([
            'porcentaje_utilidad' => ['required', 'numeric', 'min:0'],
            'apply_to_items' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data, $propuestaComercial) {
            $propuestaComercial->update([
                'porcentaje_utilidad' => $data['porcentaje_utilidad'],
            ]);

            if (!empty($data['apply_to_items'])) {
                $propuestaComercial->loadMissing('items');

                foreach ($propuestaComercial->items as $item) {
                    $cantidad = (float) ($item->cantidad_cotizada ?: 1);
                    $costo = (float) ($item->costo_unitario ?: 0);
                    $margen = (float) $data['porcentaje_utilidad'];

                    $precio = round($costo * (1 + ($margen / 100)), 2);
                    $subtotal = round($precio * $cantidad, 2);

                    $meta = is_array($item->meta) ? $item->meta : [];
                    $meta['item_margin_pct'] = $margen;

                    $item->update([
                        'precio_unitario' => $precio,
                        'subtotal' => $subtotal,
                        'meta' => $meta,
                    ]);
                }
            }

            $this->recalculateProposalTotals($propuestaComercial);
        });

        return response()->json([
            'ok' => true,
            'items' => $this->serializedItems($propuestaComercial),
            'summary' => $this->summary($propuestaComercial),
        ]);
    }

    protected function serializedItems(PropuestaComercial $propuesta): array
    {
        $propuesta->refresh()->load([
            'items.matches.product',
            'items.externalMatches',
            'items.productoSeleccionado',
        ]);

        return $propuesta->items
            ->sortBy('sort')
            ->values()
            ->map(fn ($item) => $this->serializeItem($item))
            ->all();
    }

    protected function recalculateProposalTotals(?PropuestaComercial $propuesta): void
    {
        if (!$propuesta) {
            return;
        }

        $propuesta->refresh();

        $subtotal = (float) $propuesta->items()->sum('subtotal');
        $descuento = round($subtotal * ((float) $propuesta->porcentaje_descuento / 100), 2);
        $base = max($subtotal - $descuento, 0);
        $impuesto = round($base * ((float) $propuesta->porcentaje_impuesto / 100), 2);

        $propuesta->update([
            'subtotal' => round($subtotal, 2),
            'descuento_total' => $descuento,
            'impuesto_total' => $impuesto,
            'total' => round($base + $impuesto, 2),
        ]);
    }

    protected function summary(?PropuestaComercial $propuesta): array
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
            $status = $this->statusKey($item);

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

    protected function serializeItem(PropuestaComercialItem $item): array
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
            'status_key' => $this->statusKey($item),
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

    protected function statusKey(PropuestaComercialItem $item): string
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

    protected function normalizeSearchText(string $text): string
    {
        $text = Str::lower($text);

        $text = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $text
        );

        $text = preg_replace('/[^a-z0-9\s]+/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }

    protected function searchTokens(string $text)
    {
        $stopWords = collect([
            'con', 'sin', 'para', 'por', 'del', 'las', 'los', 'una', 'uno', 'unos', 'unas',
            'pieza', 'piezas', 'pza', 'pzas', 'caja', 'cajas', 'paquete', 'paquetes',
            'presentacion', 'color', 'colores', 'tipo', 'producto', 'productos',
        ]);

        return collect(preg_split('/\s+/', $text))
            ->map(fn ($token) => trim((string) $token))
            ->filter(fn ($token) => $token !== '' && mb_strlen($token) >= 2)
            ->reject(fn ($token) => is_numeric($token))
            ->reject(fn ($token) => $stopWords->contains($token))
            ->unique()
            ->values();
    }

    protected function scoreManualProduct(Product $product, string $query, $tokens): float
    {
        $name = $this->normalizeSearchText((string) ($product->name ?? ''));
        $sku = $this->normalizeSearchText((string) ($product->sku ?? ''));
        $supplierSku = $this->normalizeSearchText((string) ($product->supplier_sku ?? ''));
        $brand = $this->normalizeSearchText((string) ($product->brand ?? ''));
        $category = $this->normalizeSearchText((string) ($product->category ?? ''));
        $tags = $this->normalizeSearchText((string) ($product->tags ?? ''));
        $description = $this->normalizeSearchText((string) ($product->description ?? ''));
        $color = $this->normalizeSearchText((string) ($product->color ?? ''));
        $unit = $this->normalizeSearchText((string) ($product->unit ?? $product->unidad ?? ''));

        $haystack = trim(implode(' ', array_filter([
            $name,
            $sku,
            $supplierSku,
            $brand,
            $category,
            $tags,
            $description,
            $color,
            $unit,
        ])));

        $score = 0;

        if ($query !== '' && str_contains($name, $query)) {
            $score += 60;
        }

        if ($query !== '' && (str_contains($sku, $query) || str_contains($supplierSku, $query))) {
            $score += 55;
        }

        if ($query !== '' && str_contains($brand, $query)) {
            $score += 30;
        }

        if ($query !== '' && str_contains($category, $query)) {
            $score += 25;
        }

        foreach ($tokens as $token) {
            if (str_contains($name, $token)) {
                $score += 25;
            }

            if (str_contains($sku, $token) || str_contains($supplierSku, $token)) {
                $score += 22;
            }

            if (str_contains($brand, $token)) {
                $score += 12;
            }

            if (str_contains($category, $token)) {
                $score += 12;
            }

            if (str_contains($tags, $token)) {
                $score += 10;
            }

            if (str_contains($description, $token)) {
                $score += 6;
            }

            if (str_contains($color, $token)) {
                $score += 6;
            }

            if (str_contains($unit, $token)) {
                $score += 4;
            }
        }

        similar_text($query, $name, $nameSimilarity);
        similar_text($query, $haystack, $globalSimilarity);

        $score += min($nameSimilarity * 0.35, 25);
        $score += min($globalSimilarity * 0.15, 12);

        return round(max(min($score, 100), 0), 2);
    }
}