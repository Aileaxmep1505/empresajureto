<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PropuestaComercial;
use App\Models\PropuestaComercialItem;
use App\Models\PropuestaComercialMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PropuestaComercialMatchController extends Controller
{
    // =========================================================
    //  PUNTOS DE ENTRADA PÚBLICOS
    // =========================================================

    public function suggest(PropuestaComercialItem $item)
    {
        DB::transaction(fn () => $this->generateMatchesForItem($item));

        return back()->with('status', 'Se generaron sugerencias inteligentes para el renglón.');
    }

    public function suggestAll(PropuestaComercial $propuestaComercial)
    {
        foreach ($propuestaComercial->items()->get() as $item) {
            DB::transaction(fn () => $this->generateMatchesForItem($item));
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
                'match_score'              => $match->score,
                'status'                   => 'matched',
            ]);

            $this->updateParentStatus($item);
        });

        return back()->with('status', 'Producto seleccionado correctamente.');
    }

    public function price(Request $request, PropuestaComercialItem $item)
    {
        $data = $request->validate([
            'cantidad_cotizada'    => ['required', 'numeric', 'min:0.01'],
            'costo_unitario'       => ['required', 'numeric', 'min:0'],
            'porcentaje_utilidad'  => ['required', 'numeric', 'min:0'],
        ]);

        $precioUnitario = (float) $data['costo_unitario'] * (1 + ((float) $data['porcentaje_utilidad'] / 100));
        $subtotal       = $precioUnitario * (float) $data['cantidad_cotizada'];

        DB::transaction(function () use ($item, $data, $precioUnitario, $subtotal) {
            $item->update([
                'cantidad_cotizada' => $data['cantidad_cotizada'],
                'costo_unitario'    => $data['costo_unitario'],
                'precio_unitario'   => round($precioUnitario, 2),
                'subtotal'          => round($subtotal, 2),
                'status'            => 'priced',
            ]);

            $propuesta = $item->propuesta()->first();

            if ($propuesta) {
                $subtotalGeneral = (float) $propuesta->items()->sum('subtotal');
                $descuentoTotal  = round($subtotalGeneral * ((float) $propuesta->porcentaje_descuento / 100), 2);
                $base            = max($subtotalGeneral - $descuentoTotal, 0);
                $impuestoTotal   = round($base * ((float) $propuesta->porcentaje_impuesto / 100), 2);
                $total           = round($base + $impuestoTotal, 2);

                $propuesta->update([
                    'subtotal'       => round($subtotalGeneral, 2),
                    'descuento_total' => $descuentoTotal,
                    'impuesto_total' => $impuestoTotal,
                    'total'          => $total,
                    'status'         => 'priced',
                ]);
            }
        });

        return back()->with('status', 'Precio aplicado correctamente.');
    }

    // =========================================================
    //  CORE: GENERAR MATCHES PARA UN ÍTEM
    // =========================================================

    protected function suggestItemInternal(PropuestaComercialItem $item): void
    {
        $this->generateMatchesForItem($item);
    }

    /**
     * Pipeline:
     *  1. Búsqueda SQL amplia  →  candidatos crudos (≤ 300)
     *  2. Scoring léxico       →  pre-filtrado (score ≥ 35)
     *  3. Juez semántico IA    →  validación real del tipo de producto
     *  4. Persistencia         →  guarda solo los aprobados por IA (top 3)
     */
    protected function generateMatchesForItem(PropuestaComercialItem $item): void
    {
        PropuestaComercialMatch::where('propuesta_comercial_item_id', $item->id)->delete();

        $queryTextOriginal = trim((string) $item->descripcion_original);
        $unidadOriginal    = trim((string) ($item->unidad_solicitada ?? ''));

        if ($queryTextOriginal === '') {
            $this->resetItem($item);
            return;
        }

        $queryNormalized = $this->normalizeText($queryTextOriginal);
        $unidadNormalized = $this->normalizeText($unidadOriginal);

        $tokens     = $this->extractSearchTokens($queryNormalized);
        $coreTokens = $this->extractCoreTokens($tokens);

        if ($tokens->isEmpty()) {
            $this->resetItem($item);
            return;
        }

        // ── PASO 1: candidatos por SQL ──────────────────────────────────────
        $candidateProducts = Product::query()
            ->where(function ($q) use ($tokens, $coreTokens, $queryTextOriginal) {
                foreach ($tokens as $token) {
                    $q->orWhere('name',        'like', "%{$token}%")
                      ->orWhere('sku',         'like', "%{$token}%")
                      ->orWhere('brand',       'like', "%{$token}%")
                      ->orWhere('category',    'like', "%{$token}%")
                      ->orWhere('tags',        'like', "%{$token}%")
                      ->orWhere('description', 'like', "%{$token}%");
                }
                foreach ($coreTokens as $token) {
                    $q->orWhere('name',     'like', "%{$token}%")
                      ->orWhere('category', 'like', "%{$token}%")
                      ->orWhere('tags',     'like', "%{$token}%");
                }
                $q->orWhere('name', 'like', '%' . $queryTextOriginal . '%');
            })
            ->limit(300)
            ->get();

        // ── PASO 2: scoring léxico (pre-filtro rápido) ──────────────────────
        $preFiltered = $candidateProducts
            ->map(fn ($p) => $this->scoreProduct($p, $queryNormalized, $unidadNormalized, $tokens, $coreTokens))
            ->filter(fn ($row) => $row['score'] >= 35 && $row['important_matches'] > 0)
            ->sortByDesc('score')
            ->take(15)          // enviamos hasta 15 al juez IA
            ->values();

        if ($preFiltered->isEmpty()) {
            $this->resetItem($item);
            return;
        }

        // ── PASO 3: juez semántico con IA ───────────────────────────────────
        $aiValidated = $this->aiSemanticJudge($item, $preFiltered);

        if (empty($aiValidated)) {
            $this->resetItem($item);
            return;
        }

        // ── PASO 4: persistir los aprobados (top 3) ──────────────────────────
        $approved = collect($aiValidated)->sortByDesc('ai_score')->take(3)->values();

        foreach ($approved as $index => $row) {
            PropuestaComercialMatch::create([
                'propuesta_comercial_item_id' => $item->id,
                'product_id'       => $row['product']->id,
                'rank'             => $index + 1,
                'score'            => $row['ai_score'],       // score definitivo de la IA
                'unidad_coincide'  => $row['unidad_coincide'],
                'seleccionado'     => false,
                'motivo'           => $row['ai_razon'],
                'meta'             => [
                    'product_name'   => $row['product']->name,
                    'sku'            => $row['product']->sku,
                    'lexico_score'   => $row['score'],         // score léxico original
                    'matched_tokens' => $row['matched_tokens'],
                    'missing_tokens' => $row['missing_tokens'],
                ],
            ]);
        }

        // Auto-seleccionar si el mejor supera 45
        $best = PropuestaComercialMatch::where('propuesta_comercial_item_id', $item->id)
            ->orderByDesc('score')
            ->first();

        if ($best && (float) $best->score >= 45) {
            PropuestaComercialMatch::where('propuesta_comercial_item_id', $item->id)
                ->update(['seleccionado' => false]);
            $best->update(['seleccionado' => true]);

            $item->update([
                'producto_seleccionado_id' => $best->product_id,
                'match_score'              => $best->score,
                'status'                   => 'matched',
            ]);
        } else {
            $this->resetItem($item);
        }

        $this->updateParentStatus($item);
    }

    // =========================================================
    //  JUEZ SEMÁNTICO (CLAUDE API)
    // =========================================================

    /**
     * Envía los candidatos pre-filtrados a Claude y obtiene qué productos
     * realmente son del mismo tipo/familia que el ítem solicitado.
     *
     * Retorna la colección original enriquecida con 'ai_score' y 'ai_razon',
     * solo para los productos que la IA aprueba (ai_score >= 50).
     */
    protected function aiSemanticJudge(PropuestaComercialItem $item, \Illuminate\Support\Collection $candidates): array
    {
        // Preparar listado para el prompt
        $productList = $candidates->map(fn ($row, $idx) => [
            'idx'         => $idx,
            'id'          => $row['product']->id,
            'nombre'      => $row['product']->name,
            'sku'         => $row['product']->sku,
            'categoria'   => $row['product']->category ?? '',
            'marca'       => $row['product']->brand ?? '',
            'descripcion' => Str::limit((string) $row['product']->description, 120),
        ])->values()->toArray();

        $productJson = json_encode($productList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $systemPrompt = <<<SYSTEM
Eres un experto en licitaciones y catálogos de productos para el gobierno de México.
Tu única tarea es determinar si cada producto candidato es del mismo tipo que el artículo solicitado.

REGLAS ESTRICTAS:
1. Solo aprueba productos que sean EXACTAMENTE el mismo tipo de artículo (misma categoría funcional).
2. Coincidencias numéricas (medidas, pesos, cantidades) NO son suficientes para aprobar.
3. Ignora el score léxico previo; razona desde el nombre y descripción del producto.
4. Responde ÚNICAMENTE con JSON válido. Sin texto extra, sin markdown.
SYSTEM;

        $userPrompt = <<<USER
ARTÍCULO SOLICITADO:
Descripción: {$item->descripcion_original}
Unidad: {$item->unidad_solicitada}

PRODUCTOS CANDIDATOS:
{$productJson}

Devuelve un objeto JSON con este formato exacto:
{
  "resultados": [
    {
      "idx": 0,
      "product_id": 123,
      "aprobado": true,
      "score": 90,
      "razon": "Es el mismo tipo de producto porque..."
    }
  ]
}

- "aprobado": true solo si el producto ES del mismo tipo funcional que el artículo solicitado.
- "score": 0-100 indicando qué tan bien coincide (0 si no aplica).
- Incluye TODOS los candidatos en "resultados", incluso los que no apruebes (aprobado: false, score: 0).
USER;

        try {
            $response = Http::withHeaders([
                'x-api-key'         => config('services.anthropic.key'),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
            ->timeout(30)
            ->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5-20251001',  // rápido y económico para este paso
                'max_tokens' => 1500,
                'system'     => $systemPrompt,
                'messages'   => [
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);

            if (! $response->successful()) {
                Log::warning('PropuestaComercialMatch: Claude API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                // Fallback: devolver los candidatos tal cual con su score léxico
                return $this->fallbackToLexical($candidates);
            }

            $raw = $response->json('content.0.text', '{}');

            // Limpiar posibles fences de markdown que Claude a veces agrega
            $clean = preg_replace('/```(?:json)?|```/', '', $raw);
            $result = json_decode(trim($clean), true);

            if (! is_array($result) || ! isset($result['resultados'])) {
                Log::warning('PropuestaComercialMatch: respuesta IA malformada', ['raw' => $raw]);
                return $this->fallbackToLexical($candidates);
            }

            // Cruzar resultados de IA con la colección original
            $approved = [];
            $aiMap    = collect($result['resultados'])->keyBy('idx');

            foreach ($candidates as $idx => $row) {
                $aiResult = $aiMap->get($idx);

                if (! $aiResult) {
                    continue;
                }

                if (! ($aiResult['aprobado'] ?? false) || (int) ($aiResult['score'] ?? 0) < 50) {
                    continue;
                }

                $approved[] = array_merge($row, [
                    'ai_score' => (int) ($aiResult['score'] ?? 0),
                    'ai_razon' => $aiResult['razon'] ?? 'Aprobado por IA',
                ]);
            }

            return $approved;

        } catch (\Throwable $e) {
            Log::error('PropuestaComercialMatch: excepción llamando a Claude', [
                'error' => $e->getMessage(),
            ]);
            return $this->fallbackToLexical($candidates);
        }
    }

    /**
     * Fallback si la IA no responde: usa score léxico pero con umbral alto (≥ 60)
     * para evitar falsos positivos.
     */
    protected function fallbackToLexical(\Illuminate\Support\Collection $candidates): array
    {
        return $candidates
            ->filter(fn ($row) => $row['score'] >= 60)
            ->map(fn ($row) => array_merge($row, [
                'ai_score' => (int) $row['score'],
                'ai_razon' => 'Coincidencia léxica (IA no disponible)',
            ]))
            ->values()
            ->all();
    }

    // =========================================================
    //  SCORING LÉXICO (pre-filtro, sin cambios relevantes)
    // =========================================================

    protected function scoreProduct(Product $product, string $queryNormalized, string $unidadNormalized, $tokens, $coreTokens): array
    {
        $name        = $this->normalizeText((string) $product->name);
        $brand       = $this->normalizeText((string) $product->brand);
        $category    = $this->normalizeText((string) $product->category);
        $tags        = $this->normalizeText((string) $product->tags);
        $description = $this->normalizeText((string) $product->description);
        $sku         = $this->normalizeText((string) $product->sku);

        $haystack = trim(implode(' ', array_filter([$name, $brand, $category, $tags, $description, $sku])));
        $strongHaystack = trim(implode(' ', array_filter([$name, $category, $tags])));

        $score          = 0;
        $matchedTokens  = [];
        $missingTokens  = [];

        foreach ($tokens as $token) {
            // Ignorar tokens puramente numéricos: evita el bug "125 gramos == 125 señales"
            if (is_numeric($token)) {
                continue;
            }

            $tokenScore = 0;
            if ($this->containsWord($name,        $token)) { $tokenScore += 22; }
            if ($this->containsWord($category,    $token)) { $tokenScore += 18; }
            if ($this->containsWord($tags,        $token)) { $tokenScore += 16; }
            if ($this->containsWord($brand,       $token)) { $tokenScore += 8;  }
            if ($this->containsWord($description, $token)) { $tokenScore += 7;  }
            if ($this->containsWord($sku,         $token)) { $tokenScore += 12; }

            if ($tokenScore > 0) {
                $matchedTokens[] = $token;
                $score += min($tokenScore, 26);
            } else {
                $missingTokens[] = $token;
            }
        }

        $importantMatches = 0;
        foreach ($coreTokens as $token) {
            if (is_numeric($token)) { continue; }
            if ($this->containsWord($strongHaystack, $token) || $this->containsWord($haystack, $token)) {
                $importantMatches++;
                $score += 18;
            }
        }

        if ($this->containsPhrase($name,     $queryNormalized)) { $score += 35; }
        elseif ($this->containsPhrase($haystack, $queryNormalized)) { $score += 22; }

        similar_text($queryNormalized, $name,     $nameSimilarity);
        similar_text($queryNormalized, $haystack, $globalSimilarity);
        $score += min((float) $nameSimilarity   * 0.35, 18);
        $score += min((float) $globalSimilarity * 0.12, 8);

        $unidadCoincide = false;
        if ($unidadNormalized !== '') {
            $unidadCoincide = $this->containsWord($haystack, $unidadNormalized)
                           || $this->containsWord($description, $unidadNormalized);
            if ($unidadCoincide) { $score += 8; }
        }

        $nonNumericTokens = $tokens->filter(fn ($t) => ! is_numeric($t));
        $coverage = $nonNumericTokens->count() > 0
            ? count($matchedTokens) / max($nonNumericTokens->count(), 1)
            : 0;

        if ($coverage >= 0.80)      { $score += 18; }
        elseif ($coverage >= 0.60)  { $score += 10; }
        elseif ($coverage < 0.35)   { $score -= 30; }

        if ($coreTokens->filter(fn ($t) => ! is_numeric($t))->count() > 0 && $importantMatches === 0) {
            $score -= 80;
        }

        if ($this->looksLikeWrongFamily($coreTokens, $haystack)) {
            $score -= 60;
        }

        $score  = round(max(min($score, 100), 0), 2);
        $motivo = 'Pre-filtro léxico';

        return [
            'product'          => $product,
            'score'            => $score,
            'unidad_coincide'  => $unidadCoincide,
            'important_matches' => $importantMatches,
            'matched_tokens'   => array_values(array_unique($matchedTokens)),
            'missing_tokens'   => array_values(array_unique($missingTokens)),
            'motivo'           => $motivo,
        ];
    }

    // =========================================================
    //  HELPERS
    // =========================================================

    protected function resetItem(PropuestaComercialItem $item): void
    {
        $item->update([
            'producto_seleccionado_id' => null,
            'match_score'              => null,
            'status'                   => 'pending',
        ]);
        $this->updateParentStatus($item);
    }

    protected function normalizeText(string $text): string
    {
        $text = Str::lower($text);
        $text = str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'], $text);
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
        $descriptive = collect([
            'blanco','blanca','negro','negra','azul','rojo','roja','verde',
            'amarillo','amarilla','gris','cafe','transparente','natural',
            'carta','oficio','doble','adhesivo','adhesiva',
        ]);

        $core = $tokens->reject(fn ($token) => $descriptive->contains($token))->values();
        return $core->isNotEmpty() ? $core : $tokens;
    }

    protected function singularizeToken(string $token): string
    {
        if (mb_strlen($token) <= 4)         { return $token; }
        if (Str::endsWith($token, 'es'))    { return mb_substr($token, 0, -2); }
        if (Str::endsWith($token, 's'))     { return mb_substr($token, 0, -1); }
        return $token;
    }

    protected function containsWord(string $haystack, string $needle): bool
    {
        $haystack = ' ' . $haystack . ' ';
        $needle   = preg_quote($needle, '/');
        return (bool) preg_match('/\b' . $needle . '\b/u', $haystack);
    }

    protected function containsPhrase(string $haystack, string $phrase): bool
    {
        return $phrase !== '' && str_contains($haystack, $phrase);
    }

    protected function looksLikeWrongFamily($coreTokens, string $haystack): bool
    {
        if ($coreTokens->isEmpty()) { return false; }

        $families = [
            'cartulina'  => ['lapiz','pluma','boligrafo','marcador','pegamento','tijera','folder'],
            'opalina'    => ['lapiz','pluma','boligrafo','marcador','pegamento','tijera','folder'],
            'hoja'       => ['lapiz','pluma','boligrafo','marcador','pegamento','tijera'],
            'papel'      => ['lapiz','pluma','boligrafo','marcador','pegamento','tijera'],
            'lapiz'      => ['cartulina','opalina','hoja','papel'],
            'pluma'      => ['cartulina','opalina','hoja','papel'],
            'boligrafo'  => ['cartulina','opalina','hoja','papel'],
            'silicon'    => ['banderita','block','señal','folder','engrapadora','pluma'],
            'banderita'  => ['silicon','pegamento','cinta','marcador','pluma'],
            'block'      => ['silicon','pegamento','cinta'],
        ];

        foreach ($coreTokens as $token) {
            if (! array_key_exists($token, $families)) { continue; }
            foreach ($families[$token] as $badFamily) {
                if ($this->containsWord($haystack, $badFamily)) { return true; }
            }
        }

        return false;
    }

    protected function updateParentStatus(PropuestaComercialItem $item): void
    {
        $propuesta = $item->propuesta()->first();
        if (! $propuesta) { return; }

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