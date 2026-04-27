<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PropuestaComercial;
use App\Models\PropuestaComercialItem;
use App\Models\PropuestaComercialMatch;
use App\Services\AiMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PropuestaComercialMatchController extends Controller
{
    public function __construct(protected AiMatchingService $aiService) {}

    // =========================================================
    //  PUNTOS DE ENTRADA
    // =========================================================

    public function suggest(PropuestaComercialItem $item)
    {
        DB::transaction(fn () => $this->generateMatchesForItem($item));
        return back()->with('status', 'Sugerencias generadas correctamente.');
    }

    public function suggestAll(PropuestaComercial $propuestaComercial)
    {
        foreach ($propuestaComercial->items()->get() as $item) {
            DB::transaction(fn () => $this->generateMatchesForItem($item));
        }
        $propuestaComercial->refresh();
        return back()->with('status', 'Sugerencias generadas para todos los renglones.');
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
            'cantidad_cotizada'   => ['required', 'numeric', 'min:0.01'],
            'costo_unitario'      => ['required', 'numeric', 'min:0'],
            'porcentaje_utilidad' => ['required', 'numeric', 'min:0'],
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
                $descuento       = round($subtotalGeneral * ($propuesta->porcentaje_descuento / 100), 2);
                $base            = max($subtotalGeneral - $descuento, 0);
                $impuesto        = round($base * ($propuesta->porcentaje_impuesto / 100), 2);
                $propuesta->update([
                    'subtotal'        => round($subtotalGeneral, 2),
                    'descuento_total' => $descuento,
                    'impuesto_total'  => $impuesto,
                    'total'           => round($base + $impuesto, 2),
                    'status'          => 'priced',
                ]);
            }
        });

        return back()->with('status', 'Precio aplicado correctamente.');
    }

    // =========================================================
    //  CORE: PIPELINE SQL + IA
    // =========================================================

    protected function suggestItemInternal(PropuestaComercialItem $item): void
    {
        $this->generateMatchesForItem($item);
    }

    /**
     * PIPELINE:
     *
     *  CAPA 1 — SQL con red amplia
     *      Usa todas las palabras de la descripción en OR.
     *      Trae hasta 300 candidatos. No filtra por stop words.
     *      Solo excluye números puros (no identifican productos).
     *
     *  CAPA 2 — Ranking léxico (solo ordena, no descarta)
     *      Puntúa cada candidato por cuántas palabras comparte.
     *      Toma los 20 mejores para enviárselos a la IA.
     *      Umbral mínimo: score > 0 Y al menos 1 palabra núcleo coincide.
     *
     *  CAPA 3 — IA (gpt-4.1-nano) — único juez real
     *      Analiza tipo + cada característica vs cada candidato.
     *      Aprueba/rechaza con sentido común de cotizador experto.
     *      Sin reglas hardcodeadas. Sin familias manuales.
     */
    protected function generateMatchesForItem(PropuestaComercialItem $item): void
    {
        PropuestaComercialMatch::where('propuesta_comercial_item_id', $item->id)->delete();

        $descripcion = trim((string) $item->descripcion_original);
        $unidad      = trim((string) ($item->unidad_solicitada ?? ''));

        if ($descripcion === '') {
            $this->resetItem($item);
            return;
        }

        $queryNorm  = $this->normalizeText($descripcion);
        $allWords   = $this->extractWords($queryNorm);        // todas las palabras útiles
        $coreWords  = $allWords->take(5);                     // primeras 5: suelen ser el tipo de producto

        if ($allWords->isEmpty()) {
            $this->resetItem($item);
            return;
        }

        // ── CAPA 1: SQL red amplia ────────────────────────────────────────
        $candidates = Product::query()
            ->where(function ($q) use ($allWords, $coreWords, $descripcion) {
                // Core words buscan en campos más importantes (AND implícito por OR múltiple)
                foreach ($coreWords as $word) {
                    $q->orWhere('name',     'like', "%{$word}%")
                      ->orWhere('category', 'like', "%{$word}%")
                      ->orWhere('tags',     'like', "%{$word}%");
                }
                // Todas las palabras en todos los campos
                foreach ($allWords as $word) {
                    $q->orWhere('name',        'like', "%{$word}%")
                      ->orWhere('sku',         'like', "%{$word}%")
                      ->orWhere('brand',       'like', "%{$word}%")
                      ->orWhere('category',    'like', "%{$word}%")
                      ->orWhere('tags',        'like', "%{$word}%")
                      ->orWhere('description', 'like', "%{$word}%");
                }
                // Literal completo
                $q->orWhere('name', 'like', '%' . $descripcion . '%');
            })
            ->limit(300)
            ->get();

        if ($candidates->isEmpty()) {
            $this->resetItem($item);
            return;
        }

        // ── CAPA 2: Ranking léxico → top 20 ─────────────────────────────
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
            return;
        }

        // ── CAPA 3: IA valida cada candidato ─────────────────────────────
        $aiApproved = $this->aiService->validateCandidates(
            descripcionOriginal: $descripcion,
            unidadSolicitada:    $unidad,
            candidates:          $ranked,
        );

        if (empty($aiApproved)) {
            $this->resetItem($item);
            return;
        }

        // ── PERSISTIR top 3 aprobados ────────────────────────────────────
        $top3 = collect($aiApproved)->sortByDesc('ai_score')->take(3)->values();

        foreach ($top3 as $rank => $row) {
            PropuestaComercialMatch::create([
                'propuesta_comercial_item_id' => $item->id,
                'product_id'      => $row['product']->id,
                'rank'            => $rank + 1,
                'score'           => $row['ai_score'],
                'unidad_coincide' => $row['unidad_coincide'],
                'seleccionado'    => false,
                'motivo'          => $row['ai_razon'],
                'meta'            => [
                    'product_name'   => $row['product']->name,
                    'sku'            => $row['product']->sku,
                    'lexico_score'   => $row['score'],
                    'matched_tokens' => $row['matched_tokens'] ?? [],
                ],
            ]);
        }

        // ── Auto-seleccionar el mejor ─────────────────────────────────────
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
    //  EXTRACCIÓN DE PALABRAS
    // =========================================================

    /**
     * Extrae todas las palabras útiles de la descripción.
     * Solo excluye números puros (ej. "197", "492").
     * NO aplica stop words: color, unidad, material, todo vale.
     * La IA decide qué importa, no nosotros.
     */
    protected function extractWords(string $text)
    {
        return collect(preg_split('/\s+/', $text))
            ->map(fn ($t) => trim((string) $t))
            ->filter(fn ($t) => $t !== '' && mb_strlen($t) >= 3 && ! is_numeric($t))
            ->map(fn ($t) => $this->singularize($t))
            ->unique()
            ->values();
    }

    // =========================================================
    //  RANKING LÉXICO
    // =========================================================

    protected function rankProduct(
        Product $product,
        string  $queryNorm,
        $allWords,
        $coreWords,
        string  $unidadNorm
    ): array {
        $name        = $this->normalizeText((string) ($product->name        ?? ''));
        $category    = $this->normalizeText((string) ($product->category    ?? ''));
        $tags        = $this->normalizeText((string) ($product->tags        ?? ''));
        $brand       = $this->normalizeText((string) ($product->brand       ?? ''));
        $description = $this->normalizeText((string) ($product->description ?? ''));
        $sku         = $this->normalizeText((string) ($product->sku         ?? ''));

        $haystack = trim(implode(' ', array_filter([$name, $brand, $category, $tags, $description, $sku])));
        $strong   = trim(implode(' ', array_filter([$name, $category, $tags])));

        $score = 0; $matched = [];

        foreach ($allWords as $word) {
            $ws = 0;
            if ($this->wordIn($name,        $word)) { $ws += 20; }
            if ($this->wordIn($category,    $word)) { $ws += 15; }
            if ($this->wordIn($tags,        $word)) { $ws += 14; }
            if ($this->wordIn($brand,       $word)) { $ws += 8;  }
            if ($this->wordIn($description, $word)) { $ws += 6;  }
            if ($this->wordIn($sku,         $word)) { $ws += 10; }

            if ($ws > 0) { $matched[] = $word; $score += min($ws, 24); }
        }

        $coreMatches = 0;
        foreach ($coreWords as $word) {
            if ($this->wordIn($strong, $word) || $this->wordIn($haystack, $word)) {
                $coreMatches++; $score += 15;
            }
        }

        if ($this->phraseIn($name,     $queryNorm)) { $score += 40; }
        elseif ($this->phraseIn($haystack, $queryNorm)) { $score += 20; }

        similar_text($queryNorm, $name,     $ns);
        similar_text($queryNorm, $haystack, $gs);
        $score += min((float) $ns * 0.3, 15);
        $score += min((float) $gs * 0.1, 8);

        $unidadCoincide = $unidadNorm !== '' && $this->wordIn($haystack, $unidadNorm);
        if ($unidadCoincide) { $score += 5; }

        return [
            'product'         => $product,
            'score'           => round(max($score, 0), 2),
            'core_matches'    => $coreMatches,
            'unidad_coincide' => $unidadCoincide,
            'matched_tokens'  => array_values(array_unique($matched)),
            'missing_tokens'  => [],
            'motivo'          => 'Ranking léxico previo a IA',
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

    protected function singularize(string $token): string
    {
        if (mb_strlen($token) <= 4)      { return $token; }
        if (Str::endsWith($token, 'es')) { return mb_substr($token, 0, -2); }
        if (Str::endsWith($token, 's'))  { return mb_substr($token, 0, -1); }
        return $token;
    }

    protected function wordIn(string $haystack, string $needle): bool
    {
        if ($needle === '') { return false; }
        return (bool) preg_match('/\b' . preg_quote($needle, '/') . '\b/u', ' ' . $haystack . ' ');
    }

    protected function phraseIn(string $haystack, string $phrase): bool
    {
        return $phrase !== '' && str_contains($haystack, $phrase);
    }

    protected function updateParentStatus(PropuestaComercialItem $item): void
    {
        $propuesta = $item->propuesta()->first();
        if (! $propuesta) { return; }
        if ($propuesta->items()->where('status', 'priced')->exists()) {
            $propuesta->update(['status' => 'priced']);
        } elseif ($propuesta->items()->where('status', 'matched')->exists()) {
            $propuesta->update(['status' => 'matched']);
        } else {
            $propuesta->update(['status' => 'draft']);
        }
    }
}