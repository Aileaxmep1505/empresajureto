<?php

namespace App\Http\Controllers;

use App\Models\PropuestaComercial;
use App\Models\PropuestaComercialItem;
use App\Models\PropuestaComercialMatch;
use App\Services\AiMatchingService;
use App\Services\EmbeddingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PropuestaComercialMatchController extends Controller
{
    public function __construct(
        protected AiMatchingService $aiService,
        protected EmbeddingService  $embeddingService,
    ) {}

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
    //  CORE: PIPELINE CON EMBEDDINGS
    // =========================================================

    protected function suggestItemInternal(PropuestaComercialItem $item): void
    {
        $this->generateMatchesForItem($item);
    }

    /**
     * PIPELINE:
     *
     *  PASO 1 — Generar embedding del ítem
     *      Convierte la descripción de la licitación en un vector numérico
     *      usando OpenAI text-embedding-3-large. Este vector captura el
     *      SIGNIFICADO completo del artículo (tipo, características, color, etc.)
     *
     *  PASO 2 — Búsqueda por similitud semántica
     *      Compara el vector del ítem contra los vectores de TODOS los productos
     *      del catálogo usando similitud coseno. Retorna los 20 más cercanos.
     *      Sin SQL de keywords. Sin stop words. Sin reglas.
     *      La matemática encuentra lo que más se parece semánticamente.
     *
     *  PASO 3 — IA valida característica por característica
     *      Los 20 candidatos van al cotizador IA que analiza si cada uno
     *      es realmente cotizable y con qué score.
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

        // ── PASO 1: Embedding del ítem ────────────────────────────────────
        try {
            $queryText   = $this->embeddingService->buildQueryText($descripcion, $unidad);
            $queryVector = $this->embeddingService->embed($queryText);
        } catch (\Throwable $e) {
            Log::error('[MatchController] Error generando embedding del ítem', [
                'error' => $e->getMessage(),
                'item'  => $descripcion,
            ]);
            $this->resetItem($item);
            return;
        }

        // ── PASO 2: Búsqueda semántica en todo el catálogo ────────────────
        // Retorna los 20 productos más cercanos semánticamente
        $similar = $this->embeddingService->findSimilarProducts($queryVector, topN: 20);

        if ($similar->isEmpty()) {
            Log::warning('[MatchController] Sin candidatos por embedding para: ' . $descripcion);
            $this->resetItem($item);
            return;
        }

        // Preparar candidatos en el formato que espera AiMatchingService
        $unidadNorm = mb_strtolower(trim($unidad));
        $candidates = $similar->map(function ($row) use ($unidadNorm) {
            $p        = $row['product'];
            $haystack = mb_strtolower(
                ($p->name ?? '') . ' ' . ($p->description ?? '') . ' ' . ($p->unit ?? '')
            );

            return [
                'product'        => $p,
                'score'          => round((float) $row['similarity'] * 100, 2),
                'similarity'     => (float) $row['similarity'],
                'unidad_coincide'=> $unidadNorm !== '' && str_contains($haystack, $unidadNorm),
                'matched_tokens' => [],
                'missing_tokens' => [],
                'motivo'         => 'Similitud semántica por embedding',
            ];
        })->all();

        // ── PASO 3: Validación IA ─────────────────────────────────────────
        $aiApproved = $this->aiService->validateCandidates(
            descripcionOriginal: $descripcion,
            unidadSolicitada:    $unidad,
            candidates:          $candidates,
        );

        if (empty($aiApproved)) {
            $this->resetItem($item);
            return;
        }

        // ── PERSISTIR top 3 ──────────────────────────────────────────────
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
                    'product_name'        => $row['product']->name,
                    'sku'                 => $row['product']->sku,
                    'similitud_semantica' => round($row['similarity'] * 100, 1) . '%',
                    'coincidencias'       => $row['ai_coincidencias'] ?? [],
                    'diferencias'         => $row['ai_diferencias']   ?? [],
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
                'match_score'              => $best->score,
                'status'                   => 'matched',
            ]);
        } else {
            $this->resetItem($item);
        }

        $this->updateParentStatus($item);
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