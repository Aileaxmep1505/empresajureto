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
    //  CORE: PIPELINE DE MATCHING
    // =========================================================

    protected function suggestItemInternal(PropuestaComercialItem $item): void
    {
        $this->generateMatchesForItem($item);
    }

    /**
     * PIPELINE:
     *
     *  CAPA 1 — SQL amplio
     *      Trae hasta 300 candidatos usando tokens no numéricos del ítem.
     *      El objetivo es tener un net amplio para no perder productos válidos.
     *
     *  CAPA 2 — Score léxico ligero
     *      Solo descarta candidatos con score < 25 O sin ningún token núcleo.
     *      El umbral es bajo a propósito: la IA decidirá, no nosotros.
     *      Máximo 20 candidatos enviados a la IA.
     *
     *  CAPA 3 — IA (OpenAI) — juez semántico
     *      Actúa como cotizador experto. Sin reglas hardcodeadas.
     *      Aprueba/rechaza con sentido común y devuelve score 0-100.
     *      Solo pasan candidatos con score IA ≥ 50.
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
        $tokens     = $this->extractTokens($queryNorm);          // todos los tokens útiles
        $coreTokens = $this->extractCoreTokens($tokens);         // los más descriptivos

        if ($tokens->isEmpty()) {
            $this->resetItem($item);
            return;
        }

        // ── CAPA 1: SQL con tokens no numéricos ──────────────────────────────
        $candidates = Product::query()
            ->where(function ($q) use ($tokens, $coreTokens, $descripcion) {
                foreach ($tokens->filter(fn ($t) => ! is_numeric($t)) as $token) {
                    $q->orWhere('name',        'like', "%{$token}%")
                      ->orWhere('sku',         'like', "%{$token}%")
                      ->orWhere('brand',       'like', "%{$token}%")
                      ->orWhere('category',    'like', "%{$token}%")
                      ->orWhere('tags',        'like', "%{$token}%")
                      ->orWhere('description', 'like', "%{$token}%");
                }
                foreach ($coreTokens->filter(fn ($t) => ! is_numeric($t)) as $token) {
                    $q->orWhere('name',     'like', "%{$token}%")
                      ->orWhere('category', 'like', "%{$token}%")
                      ->orWhere('tags',     'like', "%{$token}%");
                }
                // búsqueda literal por si el nombre completo coincide
                $q->orWhere('name', 'like', '%' . $descripcion . '%');
            })
            ->limit(300)
            ->get();

        if ($candidates->isEmpty()) {
            $this->resetItem($item);
            return;
        }

        // ── CAPA 2: Score léxico ligero (pre-filtro mínimo) ──────────────────
        $unidadNorm = $this->normalizeText($unidad);

        $preFiltered = $candidates
            ->map(fn ($p) => $this->scoreProduct($p, $queryNorm, $unidadNorm, $tokens, $coreTokens))
            ->filter(fn ($row) => $row['score'] >= 25 && $row['core_matches'] > 0)
            ->sortByDesc('score')
            ->take(20)          // la IA recibe máximo 20 candidatos
            ->values()
            ->all();

        if (empty($preFiltered)) {
            $this->resetItem($item);
            return;
        }

        // ── CAPA 3: Validación semántica con IA ──────────────────────────────
        $aiApproved = $this->aiService->validateCandidates(
            descripcionOriginal: $descripcion,
            unidadSolicitada:    $unidad,
            candidates:          $preFiltered,
        );

        if (empty($aiApproved)) {
            $this->resetItem($item);
            return;
        }

        // ── PERSISTIR top 3 aprobados ────────────────────────────────────────
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
                    'matched_tokens' => $row['matched_tokens'],
                    'missing_tokens' => $row['missing_tokens'],
                ],
            ]);
        }

        // ── Auto-seleccionar si el mejor supera umbral ────────────────────────
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
    //  SCORE LÉXICO (pre-filtro ligero — NO toma decisiones finales)
    // =========================================================

    /**
     * Scoring minimalista. Solo sirve para descartar candidatos
     * claramente irrelevantes antes de enviarlo a la IA.
     * NO contiene familias hardcodeadas.
     */
    protected function scoreProduct(
        Product $product,
        string  $queryNorm,
        string  $unidadNorm,
        $tokens,
        $coreTokens
    ): array {
        $name        = $this->normalizeText((string) ($product->name        ?? ''));
        $category    = $this->normalizeText((string) ($product->category    ?? ''));
        $tags        = $this->normalizeText((string) ($product->tags        ?? ''));
        $brand       = $this->normalizeText((string) ($product->brand       ?? ''));
        $description = $this->normalizeText((string) ($product->description ?? ''));
        $sku         = $this->normalizeText((string) ($product->sku         ?? ''));

        $haystack = trim(implode(' ', array_filter([$name, $brand, $category, $tags, $description, $sku])));
        $strong   = trim(implode(' ', array_filter([$name, $category, $tags])));

        $score = 0; $matched = []; $missing = [];

        // Tokens: los números no suman
        foreach ($tokens->filter(fn ($t) => ! is_numeric($t)) as $token) {
            $ts = 0;
            if ($this->wordIn($name,        $token)) { $ts += 20; }
            if ($this->wordIn($category,    $token)) { $ts += 15; }
            if ($this->wordIn($tags,        $token)) { $ts += 14; }
            if ($this->wordIn($brand,       $token)) { $ts += 8;  }
            if ($this->wordIn($description, $token)) { $ts += 6;  }
            if ($this->wordIn($sku,         $token)) { $ts += 10; }

            if ($ts > 0) { $matched[] = $token; $score += min($ts, 24); }
            else         { $missing[]  = $token; }
        }

        // Tokens núcleo: peso extra
        $coreMatches = 0;
        foreach ($coreTokens->filter(fn ($t) => ! is_numeric($t)) as $token) {
            if ($this->wordIn($strong, $token) || $this->wordIn($haystack, $token)) {
                $coreMatches++; $score += 15;
            }
        }

        // Phrase match
        if ($this->phraseIn($name,     $queryNorm)) { $score += 30; }
        elseif ($this->phraseIn($haystack, $queryNorm)) { $score += 18; }

        // Similitud general
        similar_text($queryNorm, $name,     $ns);
        similar_text($queryNorm, $haystack, $gs);
        $score += min((float) $ns * 0.30, 15);
        $score += min((float) $gs * 0.10, 7);

        // Unidad
        $unidadCoincide = false;
        if ($unidadNorm !== '') {
            $unidadCoincide = $this->wordIn($haystack, $unidadNorm);
            if ($unidadCoincide) { $score += 6; }
        }

        // Cobertura de tokens
        $nonNum = $tokens->filter(fn ($t) => ! is_numeric($t));
        $cov    = $nonNum->count() > 0 ? count($matched) / $nonNum->count() : 0;
        if ($cov >= 0.75)     { $score += 15; }
        elseif ($cov >= 0.50) { $score += 8; }
        elseif ($cov < 0.25)  { $score -= 20; }

        // Sin ningún token núcleo → probablemente irrelevante
        if ($coreTokens->filter(fn ($t) => ! is_numeric($t))->count() > 0 && $coreMatches === 0) {
            $score -= 50;
        }

        return [
            'product'        => $product,
            'score'          => round(max(min($score, 100), 0), 2),
            'core_matches'   => $coreMatches,
            'unidad_coincide' => $unidadCoincide,
            'matched_tokens' => array_values(array_unique($matched)),
            'missing_tokens' => array_values(array_unique($missing)),
            'motivo'         => 'Pre-filtro léxico',
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

    protected function extractTokens(string $text)
    {
        // Palabras vacías generales — solo las que nunca aportan información de producto
        $stop = collect([
            'con','sin','para','por','del','las','los','una','uno','unos','unas',
            'este','esta','estos','estas','que','como','cuando','donde','cual',
            'pieza','piezas','pza','pzas','caja','cajas','paquete','paquetes',
            'metro','metros','bolsa','bolsas','juego','juegos',
            'solicitado','solicitada','suministro','servicio','incluye','incluido',
        ]);

        return collect(preg_split('/\s+/', $text))
            ->map(fn ($t) => trim((string) $t))
            ->filter(fn ($t) => $t !== '' && mb_strlen($t) >= 3)
            ->reject(fn ($t) => $stop->contains($t))
            ->map(fn ($t) => $this->singularize($t))
            ->unique()
            ->values();
    }

    protected function extractCoreTokens($tokens)
    {
        // Solo excluimos colores y adjetivos genéricos de presentación
        // El resto (incluyendo "bicolor", "metalico", "toxic", etc.) SÍ es core
        $nonCore = collect([
            'blanco','blanca','negro','negra','azul','rojo','roja','verde',
            'amarillo','amarilla','gris','cafe','transparente','natural',
            'carta','oficio','doble',
        ]);

        $core = $tokens->reject(fn ($t) => $nonCore->contains($t))->values();
        return $core->isNotEmpty() ? $core : $tokens;
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