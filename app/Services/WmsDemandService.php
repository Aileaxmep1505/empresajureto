<?php

namespace App\Services;

use App\Models\PickWave;
use Illuminate\Support\Collection;

/**
 * Demanda pendiente de las olas de picking abiertas.
 *
 * Las olas guardan sus renglones en el JSON `items`; aquí se leen y se
 * devuelve, por producto, qué olas siguen necesitando unidades. Lo usan
 * cross-docking (para casar lo recibido con lo pedido) y reabastecimiento
 * (para subir la prioridad de lo que ya tiene pedidos esperando).
 */
class WmsDemandService
{
    /** Estados de ola que todavía piden mercancía: 0 pendiente, 1 en proceso. */
    private const ABIERTAS = [0, 1, '0', '1', 'pending', 'in_progress'];

    /**
     * @return Collection<int, Collection<int, array{wave_id:int, code:string, order_number:?string, line_id:?string, required:int, picked:int, pending:int, priority:mixed}>>
     *         indexada por catalog_item_id
     */
    public function pendientePorProducto(): Collection
    {
        $salida = [];

        $olas = PickWave::query()->whereIn('status', self::ABIERTAS)->orderBy('id')->get();

        foreach ($olas as $ola) {
            $items = is_array($ola->items) ? $ola->items : (json_decode((string) $ola->items, true) ?: []);

            foreach ($items as $linea) {
                if (! is_array($linea) || ! empty($linea['is_virtual'])) {
                    continue;
                }

                $itemId = (int) ($linea['product_id'] ?? 0);
                $required = (int) ($linea['quantity_required'] ?? $linea['requested_quantity'] ?? 0);
                $picked = (int) ($linea['quantity_picked'] ?? 0);
                $pending = $required - $picked;

                if ($itemId <= 0 || $pending <= 0) {
                    continue;
                }

                $salida[$itemId][] = [
                    'wave_id' => (int) $ola->id,
                    'code' => (string) ($ola->code ?: 'Ola #' . $ola->id),
                    'order_number' => $ola->order_number,
                    'line_id' => isset($linea['line_id']) ? (string) $linea['line_id'] : null,
                    'required' => $required,
                    'picked' => $picked,
                    'pending' => $pending,
                    'priority' => $ola->priority,
                ];
            }
        }

        return collect($salida)->map(fn ($filas) => collect($filas));
    }

    /** Unidades pendientes totales por producto: [catalog_item_id => unidades]. */
    public function unidadesPendientes(): array
    {
        return $this->pendientePorProducto()
            ->map(fn (Collection $filas) => (int) $filas->sum('pending'))
            ->all();
    }
}
