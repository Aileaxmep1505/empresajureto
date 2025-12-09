<?php

namespace App\Jobs;

use App\Models\LicitacionPropuesta;
use App\Models\LicitacionPropuestaItem;
use App\Models\Product;
use App\Services\LicitacionIaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MatchLicitacionPropuestaItems implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \App\Models\LicitacionPropuesta
     */
    public LicitacionPropuesta $propuesta;

    /**
     * Por defecto usamos 16% de IVA, ajusta si en tu sistema usas otra tasa.
     */
    protected float $ivaRate = 0.16;

    /**
     * Create a new job instance.
     */
    public function __construct(LicitacionPropuesta $propuesta)
    {
        $this->propuesta = $propuesta;
    }

    /**
     * Execute the job.
     *
     * @param  \App\Services\LicitacionIaService  $ia
     * @return void
     */
    public function handle(LicitacionIaService $ia): void
    {
        $propuesta = LicitacionPropuesta::with(['items.requestItem'])
            ->find($this->propuesta->id);

        if (! $propuesta) {
            Log::warning('MatchLicitacionPropuestaItems: propuesta no encontrada', [
                'id' => $this->propuesta->id,
            ]);
            return;
        }

        Log::info('MatchLicitacionPropuestaItems: iniciando match IA', [
            'propuesta_id' => $propuesta->id,
        ]);

        $subtotal = 0;

        foreach ($propuesta->items as $item) {
            /** @var LicitacionPropuestaItem $item */
            $requestItem = $item->requestItem;

            if (! $requestItem) {
                Log::warning('MatchLicitacionPropuestaItems: item sin requestItem', [
                    'propuesta_item_id' => $item->id,
                ]);
                continue;
            }

            // Buscar candidatos en products (ajusta campos según tu modelo)
            $searchTerm = $requestItem->descripcion ?: $requestItem->line_raw;

            $candidatos = Product::query()
                ->when($searchTerm, function ($q) use ($searchTerm) {
                    $q->where(function ($qq) use ($searchTerm) {
                        $qq->where('name', 'like', '%' . $searchTerm . '%')
                           ->orWhere('description', 'like', '%' . $searchTerm . '%');
                    });
                })
                ->limit(12)
                ->get();

            if ($candidatos->isEmpty()) {
                Log::info('MatchLicitacionPropuestaItems: sin candidatos para item', [
                    'propuesta_item_id' => $item->id,
                    'search'            => $searchTerm,
                ]);
                continue;
            }

            try {
                $match = $ia->suggestProductMatch($requestItem, $candidatos);
            } catch (\Throwable $e) {
                Log::error('MatchLicitacionPropuestaItems: error IA al sugerir producto', [
                    'propuesta_item_id' => $item->id,
                    'exception'         => $e->getMessage(),
                ]);
                continue;
            }

            $productId = $match['product_id']       ?? null;
            $score     = $match['match_score']      ?? null;
            $motivo    = $match['motivo_seleccion'] ?? null;

            // Si la IA no encontró nada razonable, seguimos al siguiente
            if (! $productId) {
                Log::info('MatchLicitacionPropuestaItems: IA no encontró match razonable', [
                    'propuesta_item_id' => $item->id,
                    'search'            => $searchTerm,
                    'match'             => $match,
                ]);
                continue;
            }

            $product = Product::find($productId);

            if (! $product) {
                Log::warning('MatchLicitacionPropuestaItems: product_id sugerido no existe', [
                    'propuesta_item_id' => $item->id,
                    'product_id'        => $productId,
                ]);
                continue;
            }

            // Si no hay cantidad_propuesta, usamos la de la requisición
            $cantidad = $item->cantidad_propuesta ?? $requestItem->cantidad ?? 1;

            // Aquí decides de dónde sacar el precio: price, price_public, etc.
            $precioUnit = $item->precio_unitario;

            if (! $precioUnit || $precioUnit <= 0) {
                // Ajusta al nombre de tu campo real
                $precioUnit = $product->price ?? $product->precio ?? 0;
            }

            $subtotalItem = (float) $cantidad * (float) $precioUnit;

            $item->update([
                'product_id'       => $product->id,
                'match_score'      => $score,
                'motivo_seleccion' => $motivo,
                'unidad_propuesta' => $item->unidad_propuesta ?: $requestItem->unidad,
                'cantidad_propuesta' => $cantidad,
                'precio_unitario'  => $precioUnit,
                'subtotal'         => $subtotalItem,
            ]);

            $subtotal += $subtotalItem;
        }

        // Actualizamos totales de la propuesta
        $iva   = $subtotal * $this->ivaRate;
        $total = $subtotal + $iva;

        $propuesta->update([
            'subtotal' => $subtotal,
            'iva'      => $iva,
            'total'    => $total,
            // opcional: cambiar status
            'status'   => 'revisar',
        ]);

        Log::info('MatchLicitacionPropuestaItems: match completado', [
            'propuesta_id' => $propuesta->id,
            'subtotal'     => $subtotal,
            'iva'          => $iva,
            'total'        => $total,
        ]);
    }
}
