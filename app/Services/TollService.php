<?php

namespace App\Services;

class TollService
{
    /**
     * Estima si hay peajes y costo aproximado.
     * En modo stub: costo 0. Si conectas proveedor externo, coloca la lógica aquí.
     */
    public function estimate(array $osrmRoute): array
    {
        // Hook: si integras provider, analiza $osrmRoute['legs'][*]['annotation']['nodes'] o 'steps'
        // y devuelve costo real.
        return [
            'has_toll' => false,
            'estimated_mxn' => 0.0,
            'mode' => 'stub',
            'note' => 'Sin proveedor de peajes; usando estimación 0.',
        ];
    }

    public function label(bool $hasToll): string
    {
        return $hasToll ? 'con casetas' : 'libre';
    }
}
