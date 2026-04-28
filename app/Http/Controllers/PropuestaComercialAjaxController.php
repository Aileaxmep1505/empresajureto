<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PropuestaComercial;
use App\Models\PropuestaComercialItem;
use App\Services\ExternalProductReferenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropuestaComercialAjaxController extends Controller
{
    public function updateItem(Request $request, PropuestaComercialItem $item)
    {
        $data = $request->validate([
            'descripcion_original' => ['nullable', 'string'],
            'unidad_solicitada' => ['nullable', 'string', 'max:80'],
            'cantidad_cotizada' => ['nullable', 'numeric', 'min:0'],
            'cantidad_minima' => ['nullable', 'numeric', 'min:0'],
            'cantidad_maxima' => ['nullable', 'numeric', 'min:0'],
            'costo_unitario' => ['nullable', 'numeric', 'min:0'],
            'porcentaje_utilidad' => ['nullable', 'numeric', 'min:0'],
            'external_supplier' => ['nullable', 'string', 'max:255'],
            'external_link' => ['nullable', 'string'],
            'catalog_product_name' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($item, $data) {
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
                'meta' => $meta,
                'status' => $item->producto_seleccionado_id ? 'priced' : $item->status,
            ]);

            $this->recalculateProposalTotals($item->propuesta);
        });

        $item->refresh()->load([
            'matches.product',
            'externalMatches',
            'productoSeleccionado',
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

        $products = Product::query()
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('brand', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%")
                    ->orWhere('tags', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            })
            ->limit(20)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'brand' => $p->brand,
                    'stock' => $p->stock ?? 0,
                    'cost' => (float) ($p->cost ?? $p->costo ?? 0),
                    'price' => (float) ($p->price ?? $p->precio ?? 0),
                ];
            })
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

        $propuestaComercial->refresh()->load([
            'items.matches.product',
            'items.externalMatches',
            'items.productoSeleccionado',
        ]);

        return response()->json([
            'ok' => true,
            'items' => $propuestaComercial->items->sortBy('sort')->values()->map(fn ($item) => $this->serializeItem($item)),
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

        $propuestaComercial->refresh()->load([
            'items.matches.product',
            'items.externalMatches',
            'items.productoSeleccionado',
        ]);

        return response()->json([
            'ok' => true,
            'items' => $propuestaComercial->items->sortBy('sort')->values()->map(fn ($item) => $this->serializeItem($item)),
            'summary' => $this->summary($propuestaComercial),
        ]);
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
                        'cost' => (float) ($p->cost ?? $p->costo ?? 0),
                        'price' => (float) ($p->price ?? $p->precio ?? 0),
                    ] : null,
                ];
            }),
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
            }),
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
}