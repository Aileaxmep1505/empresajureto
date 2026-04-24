<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PropuestaComercial;
use App\Models\PropuestaComercialItem;
use App\Models\PropuestaComercialMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PropuestaComercialMatchController extends Controller
{
    public function suggest(PropuestaComercialItem $item)
    {
        DB::transaction(function () use ($item) {
            PropuestaComercialMatch::where('propuesta_comercial_item_id', $item->id)->delete();

            $queryText = trim((string) $item->descripcion_original);
            $unidad = trim((string) ($item->unidad_solicitada ?? ''));

            $tokens = collect(preg_split('/\s+/', Str::lower($queryText)))
                ->filter(fn ($v) => $v !== null && $v !== '' && mb_strlen($v) >= 3)
                ->unique()
                ->values();

            $products = Product::query()
                ->where(function ($q) use ($tokens, $queryText) {
                    foreach ($tokens as $token) {
                        $q->orWhere('name', 'like', "%{$token}%")
                          ->orWhere('sku', 'like', "%{$token}%")
                          ->orWhere('brand', 'like', "%{$token}%")
                          ->orWhere('category', 'like', "%{$token}%")
                          ->orWhere('tags', 'like', "%{$token}%")
                          ->orWhere('description', 'like', "%{$token}%");
                    }

                    $q->orWhere('name', 'like', '%' . $queryText . '%');
                })
                ->limit(100)
                ->get()
                ->map(function ($product) use ($queryText, $unidad) {
                    $haystack = Str::lower(trim(implode(' ', array_filter([
                        $product->name,
                        $product->brand,
                        $product->category,
                        $product->tags,
                        $product->description,
                        $product->sku,
                    ]))));

                    similar_text(Str::lower($queryText), $haystack, $percent);

                    $unidadCoincide = false;
                    if ($unidad !== '') {
                        $unidadCoincide =
                            str_contains($haystack, Str::lower($unidad)) ||
                            str_contains(Str::lower((string) $product->description), Str::lower($unidad));
                    }

                    $score = (float) $percent;

                    if ($unidadCoincide) {
                        $score += 10;
                    }

                    if (str_contains($haystack, Str::lower($queryText))) {
                        $score += 5;
                    }

                    return [
                        'product' => $product,
                        'score' => round(min($score, 100), 2),
                        'unidad_coincide' => $unidadCoincide,
                    ];
                })
                ->sortByDesc('score')
                ->take(3)
                ->values();

            foreach ($products as $index => $row) {
                PropuestaComercialMatch::create([
                    'propuesta_comercial_item_id' => $item->id,
                    'product_id' => $row['product']->id,
                    'rank' => $index + 1,
                    'score' => $row['score'],
                    'unidad_coincide' => $row['unidad_coincide'],
                    'seleccionado' => false,
                    'motivo' => $row['unidad_coincide']
                        ? 'Coincidencia por texto y unidad'
                        : 'Coincidencia por texto aproximado',
                    'meta' => [
                        'product_name' => $row['product']->name,
                        'sku' => $row['product']->sku,
                    ],
                ]);
            }

            $best = PropuestaComercialMatch::where('propuesta_comercial_item_id', $item->id)
                ->orderByDesc('score')
                ->first();

            if ($best) {
                PropuestaComercialMatch::where('propuesta_comercial_item_id', $item->id)
                    ->update(['seleccionado' => false]);

                $best->update(['seleccionado' => true]);

                $item->update([
                    'producto_seleccionado_id' => $best->product_id,
                    'match_score' => $best->score,
                    'status' => 'matched',
                ]);

                $this->updateParentStatus($item);
            }
        });

        return back()->with('status', 'Se generaron sugerencias para el renglón.');
    }

    public function suggestAll(PropuestaComercial $propuestaComercial)
    {
        $items = $propuestaComercial->items()->get();

        foreach ($items as $item) {
            $this->suggestItemInternal($item);
        }

        $propuestaComercial->refresh();

        return back()->with('status', 'Se generaron sugerencias para todos los renglones.');
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
            $item->update([
                'cantidad_cotizada' => $data['cantidad_cotizada'],
                'costo_unitario' => $data['costo_unitario'],
                'precio_unitario' => round($precioUnitario, 2),
                'subtotal' => round($subtotal, 2),
                'status' => 'priced',
            ]);

            $propuesta = $item->propuesta()->first();

            if ($propuesta) {
                $subtotalGeneral = (float) $propuesta->items()->sum('subtotal');
                $descuentoTotal = round($subtotalGeneral * ((float) $propuesta->porcentaje_descuento / 100), 2);
                $base = max($subtotalGeneral - $descuentoTotal, 0);
                $impuestoTotal = round($base * ((float) $propuesta->porcentaje_impuesto / 100), 2);
                $total = round($base + $impuestoTotal, 2);

                $propuesta->update([
                    'subtotal' => round($subtotalGeneral, 2),
                    'descuento_total' => $descuentoTotal,
                    'impuesto_total' => $impuestoTotal,
                    'total' => $total,
                    'status' => 'priced',
                ]);
            }
        });

        return back()->with('status', 'Precio aplicado correctamente.');
    }

    protected function suggestItemInternal(PropuestaComercialItem $item): void
    {
        PropuestaComercialMatch::where('propuesta_comercial_item_id', $item->id)->delete();

        $queryText = trim((string) $item->descripcion_original);
        $unidad = trim((string) ($item->unidad_solicitada ?? ''));

        $tokens = collect(preg_split('/\s+/', Str::lower($queryText)))
            ->filter(fn ($v) => $v !== null && $v !== '' && mb_strlen($v) >= 3)
            ->unique()
            ->values();

        $products = Product::query()
            ->where(function ($q) use ($tokens, $queryText) {
                foreach ($tokens as $token) {
                    $q->orWhere('name', 'like', "%{$token}%")
                      ->orWhere('sku', 'like', "%{$token}%")
                      ->orWhere('brand', 'like', "%{$token}%")
                      ->orWhere('category', 'like', "%{$token}%")
                      ->orWhere('tags', 'like', "%{$token}%")
                      ->orWhere('description', 'like', "%{$token}%");
                }

                $q->orWhere('name', 'like', '%' . $queryText . '%');
            })
            ->limit(100)
            ->get()
            ->map(function ($product) use ($queryText, $unidad) {
                $haystack = Str::lower(trim(implode(' ', array_filter([
                    $product->name,
                    $product->brand,
                    $product->category,
                    $product->tags,
                    $product->description,
                    $product->sku,
                ]))));

                similar_text(Str::lower($queryText), $haystack, $percent);

                $unidadCoincide = false;
                if ($unidad !== '') {
                    $unidadCoincide =
                        str_contains($haystack, Str::lower($unidad)) ||
                        str_contains(Str::lower((string) $product->description), Str::lower($unidad));
                }

                $score = (float) $percent;

                if ($unidadCoincide) {
                    $score += 10;
                }

                if (str_contains($haystack, Str::lower($queryText))) {
                    $score += 5;
                }

                return [
                    'product' => $product,
                    'score' => round(min($score, 100), 2),
                    'unidad_coincide' => $unidadCoincide,
                ];
            })
            ->sortByDesc('score')
            ->take(3)
            ->values();

        foreach ($products as $index => $row) {
            PropuestaComercialMatch::create([
                'propuesta_comercial_item_id' => $item->id,
                'product_id' => $row['product']->id,
                'rank' => $index + 1,
                'score' => $row['score'],
                'unidad_coincide' => $row['unidad_coincide'],
                'seleccionado' => false,
                'motivo' => $row['unidad_coincide']
                    ? 'Coincidencia por texto y unidad'
                    : 'Coincidencia por texto aproximado',
                'meta' => [
                    'product_name' => $row['product']->name,
                    'sku' => $row['product']->sku,
                ],
            ]);
        }

        $best = PropuestaComercialMatch::where('propuesta_comercial_item_id', $item->id)
            ->orderByDesc('score')
            ->first();

        if ($best) {
            PropuestaComercialMatch::where('propuesta_comercial_item_id', $item->id)
                ->update(['seleccionado' => false]);

            $best->update(['seleccionado' => true]);

            $item->update([
                'producto_seleccionado_id' => $best->product_id,
                'match_score' => $best->score,
                'status' => 'matched',
            ]);

            $this->updateParentStatus($item);
        }
    }

    protected function updateParentStatus(PropuestaComercialItem $item): void
    {
        $propuesta = $item->propuesta()->first();

        if (!$propuesta) {
            return;
        }

        if ($propuesta->items()->where('status', 'priced')->exists()) {
            $propuesta->update(['status' => 'priced']);
            return;
        }

        if ($propuesta->items()->where('status', 'matched')->exists()) {
            $propuesta->update(['status' => 'matched']);
        }
    }
}