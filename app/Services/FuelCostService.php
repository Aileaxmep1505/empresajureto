<?php

namespace App\Services;

class FuelCostService
{
    public function estimateMXN(int $meters, array $opts = []): array
    {
        $fuel = $opts['fuel'] ?? 'gasoline'; // gasoline|diesel
        $price = $fuel === 'diesel'
            ? (float) env('FUEL_PRICE_DIESEL_MXN', 27)
            : (float) env('FUEL_PRICE_MXN', 25.5);

        $kmPerL = $fuel === 'diesel'
            ? (float) env('VEHICLE_KM_PER_L_DIESEL', 9)
            : (float) env('VEHICLE_KM_PER_L', 11);

        $km = max(0, $meters) / 1000.0;
        $liters = $kmPerL > 0 ? $km / $kmPerL : 0;
        $mxn = $liters * $price;

        return [
            'km'     => round($km, 1),
            'liters' => round($liters, 2),
            'price'  => round($price, 2),
            'mxn'    => round($mxn, 2),
            'fuel'   => $fuel,
            'km_per_l'=> $kmPerL,
        ];
    }
}
