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
            $this->generateMatchesForItem($item);
        });

        return back()->with('status', 'Se generaron sugerencias inteligentes para el renglón.');
    }

    public function suggestAll(PropuestaComercial $propuestaComercial)
    {
        $items = $propuestaComercial->items()->get();

        foreach ($items as $item) {
            DB::transaction(function () use ($item) {
                $this->generateMatchesForItem($item);
            });
        }

        $propuestaComercial->refresh();

        return back()->with('status', 'Se generaron sugerencias inteligentes para todos los renglones.');
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
        $this->generateMatchesForItem($item);
    }

    protected function generateMatchesForItem(PropuestaComercialItem $item): void
    {
        PropuestaComercialMatch::where('propuesta_comercial_item_id', $item->id)->delete();

        $queryTextOriginal = trim((string) $item->descripcion_original);
        $unidadOriginal = trim((string) ($item->unidad_solicitada ?? ''));

        if ($queryTextOriginal === '') {
            $item->update([
                'producto_seleccionado_id' => null,
                'match_score' => null,
                'status' => 'pending',
            ]);

            $this->updateParentStatus($item);
            return;
        }

        $queryNormalized = $this->normalizeText($queryTextOriginal);
        $unidadNormalized = $this->normalizeText($unidadOriginal);

        $tokens = $this->extractSearchTokens($queryNormalized);
        $coreTokens = $this->extractCoreTokens($tokens);

        if ($tokens->isEmpty()) {
            $item->update([
                'producto_seleccionado_id' => null,
                'match_score' => null,
                'status' => 'pending',
            ]);

            $this->updateParentStatus($item);
            return;
        }

        $candidateProducts = Product::query()
            ->where(function ($q) use ($tokens, $coreTokens, $queryTextOriginal) {
                foreach ($tokens as $token) {
                    $q->orWhere('name', 'like', "%{$token}%")
                        ->orWhere('sku', 'like', "%{$token}%")
                        ->orWhere('brand', 'like', "%{$token}%")
                        ->orWhere('category', 'like', "%{$token}%")
                        ->orWhere('tags', 'like', "%{$token}%")
                        ->orWhere('description', 'like', "%{$token}%");
                }

                foreach ($coreTokens as $token) {
                    $q->orWhere('name', 'like', "%{$token}%")
                        ->orWhere('category', 'like', "%{$token}%")
                        ->orWhere('tags', 'like', "%{$token}%");
                }

                $q->orWhere('name', 'like', '%' . $queryTextOriginal . '%');
            })
            ->limit(300)
            ->get();

        $matches = $candidateProducts
            ->map(function ($product) use ($queryNormalized, $unidadNormalized, $tokens, $coreTokens) {
                return $this->scoreProduct($product, $queryNormalized, $unidadNormalized, $tokens, $coreTokens);
            })
            ->filter(function ($row) {
                return $row['score'] >= 35 && $row['important_matches'] > 0;
            })
            ->sortByDesc('score')
            ->take(3)
            ->values();

        foreach ($matches as $index => $row) {
            PropuestaComercialMatch::create([
                'propuesta_comercial_item_id' => $item->id,
                'product_id' => $row['product']->id,
                'rank' => $index + 1,
                'score' => $row['score'],
                'unidad_coincide' => $row['unidad_coincide'],
                'seleccionado' => false,
                'motivo' => $row['motivo'],
                'meta' => [
                    'product_name' => $row['product']->name,
                    'sku' => $row['product']->sku,
                    'matched_tokens' => $row['matched_tokens'],
                    'missing_tokens' => $row['missing_tokens'],
                ],
            ]);
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
            $item->update([
                'producto_seleccionado_id' => null,
                'match_score' => null,
                'status' => 'pending',
            ]);
        }

        $this->updateParentStatus($item);
    }

    protected function scoreProduct(Product $product, string $queryNormalized, string $unidadNormalized, $tokens, $coreTokens): array
    {
        $name = $this->normalizeText((string) $product->name);
        $brand = $this->normalizeText((string) $product->brand);
        $category = $this->normalizeText((string) $product->category);
        $tags = $this->normalizeText((string) $product->tags);
        $description = $this->normalizeText((string) $product->description);
        $sku = $this->normalizeText((string) $product->sku);

        $haystack = trim(implode(' ', array_filter([
            $name,
            $brand,
            $category,
            $tags,
            $description,
            $sku,
        ])));

        $strongHaystack = trim(implode(' ', array_filter([
            $name,
            $category,
            $tags,
        ])));

        $score = 0;
        $matchedTokens = [];
        $missingTokens = [];

        foreach ($tokens as $token) {
            $tokenScore = 0;

            if ($this->containsWord($name, $token)) {
                $tokenScore += 22;
            }

            if ($this->containsWord($category, $token)) {
                $tokenScore += 18;
            }

            if ($this->containsWord($tags, $token)) {
                $tokenScore += 16;
            }

            if ($this->containsWord($brand, $token)) {
                $tokenScore += 8;
            }

            if ($this->containsWord($description, $token)) {
                $tokenScore += 7;
            }

            if ($this->containsWord($sku, $token)) {
                $tokenScore += 12;
            }

            if ($tokenScore > 0) {
                $matchedTokens[] = $token;
                $score += min($tokenScore, 26);
            } else {
                $missingTokens[] = $token;
            }
        }

        $importantMatches = 0;

        foreach ($coreTokens as $token) {
            if ($this->containsWord($strongHaystack, $token) || $this->containsWord($haystack, $token)) {
                $importantMatches++;
                $score += 18;
            }
        }

        if ($this->containsPhrase($name, $queryNormalized)) {
            $score += 35;
        } elseif ($this->containsPhrase($haystack, $queryNormalized)) {
            $score += 22;
        }

        similar_text($queryNormalized, $name, $nameSimilarity);
        similar_text($queryNormalized, $haystack, $globalSimilarity);

        $score += min((float) $nameSimilarity * 0.35, 18);
        $score += min((float) $globalSimilarity * 0.12, 8);

        $unidadCoincide = false;

        if ($unidadNormalized !== '') {
            $unidadCoincide =
                $this->containsWord($haystack, $unidadNormalized) ||
                $this->containsWord($description, $unidadNormalized);

            if ($unidadCoincide) {
                $score += 8;
            }
        }

        $coverage = $tokens->count() > 0
            ? count($matchedTokens) / max($tokens->count(), 1)
            : 0;

        if ($coverage >= 0.80) {
            $score += 18;
        } elseif ($coverage >= 0.60) {
            $score += 10;
        } elseif ($coverage < 0.35) {
            $score -= 30;
        }

        if ($coreTokens->count() > 0 && $importantMatches === 0) {
            $score -= 80;
        }

        if ($this->looksLikeWrongFamily($coreTokens, $haystack)) {
            $score -= 60;
        }

        $score = round(max(min($score, 100), 0), 2);

        $motivo = 'Coincidencia inteligente por descripción';

        if ($score >= 85) {
            $motivo = 'Coincidencia exacta o casi exacta';
        } elseif ($score >= 65) {
            $motivo = 'Coincidencia alta por producto, categoría y palabras clave';
        } elseif ($unidadCoincide) {
            $motivo = 'Coincidencia por texto y unidad';
        }

        return [
            'product' => $product,
            'score' => $score,
            'unidad_coincide' => $unidadCoincide,
            'important_matches' => $importantMatches,
            'matched_tokens' => array_values(array_unique($matchedTokens)),
            'missing_tokens' => array_values(array_unique($missingTokens)),
            'motivo' => $motivo,
        ];
    }

    protected function normalizeText(string $text): string
    {
        $text = Str::lower($text);

        $search = ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'];
        $replace = ['a', 'e', 'i', 'o', 'u', 'u', 'n'];

        $text = str_replace($search, $replace, $text);
        $text = preg_replace('/[^a-z0-9\s]+/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }

    protected function extractSearchTokens(string $text)
    {
        $stopWords = collect([
            'con', 'sin', 'para', 'por', 'del', 'las', 'los', 'una', 'uno', 'unos', 'unas',
            'pieza', 'piezas', 'pza', 'pzas', 'caja', 'cajas', 'paquete', 'paquetes',
            'metro', 'metros', 'bolsa', 'bolsas', 'juego', 'juegos', 'color', 'tipo',
            'medida', 'medidas', 'marca', 'modelo', 'material', 'producto', 'productos',
            'solicitado', 'solicitada', 'suministro', 'servicio', 'incluye', 'incluido',
            'tamano', 'tamaño', 'grande', 'chico', 'mediano',
        ]);

        return collect(preg_split('/\s+/', $text))
            ->map(fn ($token) => trim((string) $token))
            ->filter(fn ($token) => $token !== '' && mb_strlen($token) >= 3)
            ->reject(fn ($token) => $stopWords->contains($token))
            ->map(fn ($token) => $this->singularizeToken($token))
            ->unique()
            ->values();
    }

    protected function extractCoreTokens($tokens)
    {
        $descriptiveColorsAndCommon = collect([
            'blanco', 'blanca', 'negro', 'negra', 'azul', 'rojo', 'roja', 'verde',
            'amarillo', 'amarilla', 'gris', 'cafe', 'transparente', 'natural',
            'carta', 'oficio', 'doble', 'adhesivo', 'adhesiva',
        ]);

        $core = $tokens
            ->reject(fn ($token) => $descriptiveColorsAndCommon->contains($token))
            ->values();

        return $core->isNotEmpty() ? $core : $tokens;
    }

    protected function singularizeToken(string $token): string
    {
        if (mb_strlen($token) <= 4) {
            return $token;
        }

        if (Str::endsWith($token, 'es')) {
            return mb_substr($token, 0, -2);
        }

        if (Str::endsWith($token, 's')) {
            return mb_substr($token, 0, -1);
        }

        return $token;
    }

    protected function containsWord(string $haystack, string $needle): bool
    {
        $haystack = ' ' . $haystack . ' ';
        $needle = preg_quote($needle, '/');

        return (bool) preg_match('/\b' . $needle . '\b/u', $haystack);
    }

    protected function containsPhrase(string $haystack, string $phrase): bool
    {
        if ($phrase === '') {
            return false;
        }

        return str_contains($haystack, $phrase);
    }

    protected function looksLikeWrongFamily($coreTokens, string $haystack): bool
    {
        if ($coreTokens->isEmpty()) {
            return false;
        }

        $productFamilies = [
            'cartulina' => ['lapiz', 'pluma', 'boligrafo', 'marcador', 'pegamento', 'tijera', 'folder'],
            'opalina' => ['lapiz', 'pluma', 'boligrafo', 'marcador', 'pegamento', 'tijera', 'folder'],
            'hoja' => ['lapiz', 'pluma', 'boligrafo', 'marcador', 'pegamento', 'tijera'],
            'papel' => ['lapiz', 'pluma', 'boligrafo', 'marcador', 'pegamento', 'tijera'],
            'lapiz' => ['cartulina', 'opalina', 'hoja', 'papel'],
            'pluma' => ['cartulina', 'opalina', 'hoja', 'papel'],
            'boligrafo' => ['cartulina', 'opalina', 'hoja', 'papel'],
        ];

        foreach ($coreTokens as $token) {
            if (!array_key_exists($token, $productFamilies)) {
                continue;
            }

            foreach ($productFamilies[$token] as $badFamily) {
                if ($this->containsWord($haystack, $badFamily)) {
                    return true;
                }
            }
        }

        return false;
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
            return;
        }

        $propuesta->update(['status' => 'draft']);
    }
}