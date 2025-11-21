<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\CotizacionProducto;
use Illuminate\Support\Facades\DB;

class CotizacionService
{
    /** Crea cotización con items usando COSTO + utilidad_global */
    public function crear(array $data): Cotizacion
    {
        return DB::transaction(function() use ($data){
            $cot = new Cotizacion();
            $cot->cliente_id      = $data['cliente_id'];
            $cot->notas           = $data['notas'] ?? null;
            $cot->descuento       = $data['descuento'] ?? 0;
            $cot->envio           = $data['envio'] ?? 0;
            $cot->validez_dias    = (int)($data['validez_dias'] ?? 15);
            $cot->utilidad_global = (float)($data['utilidad_global'] ?? 0);
            $cot->setValidez();
            $cot->save();

            $items = collect($data['items'])->map(function($it) use ($cot){
                $cost = (float)$it['cost'];
                $qty  = (float)$it['cantidad'];
                $desc = (float)($it['descuento'] ?? 0);
                $ivaP = (float)($it['iva_porcentaje'] ?? 16);

                $precioUnit = round($cost * (1 + ($cot->utilidad_global/100)), 2);
                $base       = max(0, ($precioUnit * $qty) - $desc);
                $ivaMonto   = round($base * ($ivaP/100), 2);
                $totalFila  = round($base + $ivaMonto, 2);

                return new CotizacionProducto([
                    'producto_id'     => $it['producto_id'],
                    'descripcion'     => $it['descripcion'] ?? null,
                    'cantidad'        => $qty,
                    'cost'            => $cost,
                    'precio_unitario' => $precioUnit,
                    'descuento'       => $desc,
                    'iva_porcentaje'  => $ivaP,
                    'importe_sin_iva' => round($base, 2),
                    'iva_monto'       => $ivaMonto,
                    'importe_total'   => $totalFila,
                    'importe'         => $totalFila,
                ]);
            });

            $cot->items()->saveMany($items);

            $cot->load('items');
            $cot->recalcularTotales();
            $cot->save();

            return $cot;
        });
    }

    /** Actualiza cotización y reemplaza items */
    public function actualizar(Cotizacion $cotizacion, array $data): Cotizacion
    {
        DB::transaction(function() use ($cotizacion,$data){
            $cotizacion->update([
                'cliente_id'      => $data['cliente_id'],
                'notas'           => $data['notas'] ?? null,
                'descuento'       => $data['descuento'] ?? 0,
                'envio'           => $data['envio'] ?? 0,
                'validez_dias'    => (int)($data['validez_dias'] ?? 15),
                'utilidad_global' => (float)($data['utilidad_global'] ?? $cotizacion->utilidad_global ?? 0),
            ]);
            $cotizacion->setValidez();

            $cotizacion->items()->delete();

            $items = collect($data['items'])->map(function($it) use ($cotizacion){
                $cost = (float)$it['cost'];
                $qty  = (float)$it['cantidad'];
                $desc = (float)($it['descuento'] ?? 0);
                $ivaP = (float)($it['iva_porcentaje'] ?? 16);

                $precioUnit = round($cost * (1 + ($cotizacion->utilidad_global/100)), 2);
                $base       = max(0, ($precioUnit * $qty) - $desc);
                $ivaMonto   = round($base * ($ivaP/100), 2);
                $totalFila  = round($base + $ivaMonto, 2);

                return new CotizacionProducto([
                    'producto_id'     => $it['producto_id'],
                    'descripcion'     => $it['descripcion'] ?? null,
                    'cantidad'        => $qty,
                    'cost'            => $cost,
                    'precio_unitario' => $precioUnit,
                    'descuento'       => $desc,
                    'iva_porcentaje'  => $ivaP,
                    'importe_sin_iva' => round($base, 2),
                    'iva_monto'       => $ivaMonto,
                    'importe_total'   => $totalFila,
                    'importe'         => $totalFila,
                ]);
            });

            $cotizacion->items()->saveMany($items);

            $cotizacion->load('items');
            $cotizacion->recalcularTotales();
            $cotizacion->save();
        });

        return $cotizacion->fresh();
    }
}
