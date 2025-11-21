<?php

namespace App\Services;

use App\Models\{Cotizacion, Venta, VentaProducto};
use App\Services\FacturaApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class VentaConversionService
{
    public function __construct(protected FacturaApiService $facturaApi) {}

    public function convertir(Request $request, Cotizacion $cotizacion)
    {
        if (in_array((string) $cotizacion->estado, ['converted', 'cancelled'], true)) {
            return [false, null, 'Esta cotización no puede convertirse (estado actual: '.($cotizacion->estado ?? '—').').'];
        }

        $cotizacion->loadMissing('items.producto');
        if ($cotizacion->items->isEmpty()) {
            return [false, null, 'La cotización no tiene conceptos para convertir.'];
        }

        try {
            $venta = DB::transaction(function () use ($cotizacion) {
                $venta = new Venta();
                $venta->cliente_id    = $cotizacion->cliente_id;
                $venta->cotizacion_id = $cotizacion->id;
                $venta->moneda        = $cotizacion->moneda ?: 'MXN';
                $venta->notas         = $cotizacion->notas ?: null;

                $venta->utilidad_global = (float) ($cotizacion->utilidad_global ?? 0);
                $venta->descuento       = (float) ($cotizacion->descuento ?? 0);
                $venta->envio           = (float) ($cotizacion->envio ?? 0);
                $venta->estado          = 'emitida';

                if (array_key_exists('financiamiento_config', $cotizacion->getAttributes())) {
                    $venta->financiamiento_config = $cotizacion->financiamiento_config;
                }

                $venta->subtotal = 0;
                $venta->iva      = 0;
                $venta->total    = 0;
                $venta->save();

                $rows = [];
                $sumBase  = 0.0;
                $sumIva   = 0.0;
                $sumCosto = 0.0;

                $ventaProductosTable = (new VentaProducto())->getTable();
                $hasCostColumn = Schema::hasColumn($ventaProductosTable, 'cost');

                foreach ($cotizacion->items as $it) {
                    $cantidad  = max(0.01, (float) ($it->cantidad ?? 1));
                    $pu        = round((float) ($it->precio_unitario ?? $it->precio ?? 0), 2);
                    $descFila  = round((float) ($it->descuento ?? 0), 2);
                    $ivaPct    = round((float) ($it->iva_porcentaje ?? 0), 2);
                    $cost      = round((float) ($it->cost ?? 0), 2);

                    $base      = max(0, round($cantidad * $pu - $descFila, 2));
                    $ivaMonto  = round($base * ($ivaPct / 100), 2);
                    $importe   = round($base + $ivaMonto, 2);

                    $sumBase  += $base;
                    $sumIva   += $ivaMonto;
                    $sumCosto += ($cost * $cantidad);

                    $row = [
                        'venta_id'        => $venta->id,
                        'producto_id'     => $it->producto_id,
                        'descripcion'     => $it->descripcion ?? optional($it->producto)->name ?? 'Producto',
                        'cantidad'        => $cantidad,
                        'precio_unitario' => $pu,
                        'descuento'       => $descFila,
                        'iva_porcentaje'  => $ivaPct,
                        'importe'         => $importe,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];

                    if ($hasCostColumn) $row['cost'] = $cost;
                    if (Schema::hasColumn($ventaProductosTable, 'importe_sin_iva')) $row['importe_sin_iva'] = $base;
                    if (Schema::hasColumn($ventaProductosTable, 'iva_monto')) $row['iva_monto'] = $ivaMonto;

                    $rows[] = $row;
                }

                if ($rows) VentaProducto::insert($rows);

                $venta->subtotal = round($sumBase, 2);
                $venta->iva      = round($sumIva, 2);
                $venta->total    = max(0, round($venta->subtotal - $venta->descuento + $venta->envio + $venta->iva, 2));

                if (Schema::hasColumn($venta->getTable(), 'inversion_total')) {
                    $venta->inversion_total = round($sumCosto, 2);
                }
                if (Schema::hasColumn($venta->getTable(), 'ganancia_estimada')) {
                    $gan = $cotizacion->ganancia_estimada;
                    if (is_null($gan)) $gan = round($venta->subtotal - $sumCosto, 2);
                    $venta->ganancia_estimada = (float) $gan;
                }

                $venta->save();

                $cotizacion->estado = 'converted';
                if (Schema::hasColumn($cotizacion->getTable(), 'converted_at')) {
                    $cotizacion->converted_at = now();
                }
                if (Schema::hasColumn($cotizacion->getTable(), 'venta_id')) {
                    $cotizacion->venta_id = $venta->id;
                }
                $cotizacion->save();

                return $venta;
            });
        } catch (\Throwable $e) {
            report($e);
            return [false, null, 'No se pudo convertir la cotización: '.$e->getMessage()];
        }

        // timbrado opcional
        $mustInvoice = $request->boolean('facturar') || (bool) config('services.facturaapi.auto', false);
        if ($mustInvoice) {
            try {
                $this->facturaApi->facturarVenta($venta);
                $this->facturaApi->guardarArchivos($venta);

                Log::info('Venta facturada automáticamente al convertir cotización', [
                    'venta_id' => $venta->id,
                    'uuid' => $venta->factura_uuid,
                ]);

                return [true, $venta, null, 'facturada'];
            } catch (\Throwable $e) {
                report($e);
                Log::warning('Venta creada pero falló el timbrado automático', [
                    'venta_id' => $venta->id,
                    'error' => $e->getMessage(),
                ]);

                return [true, $venta, 'Venta creada, pero la facturación falló: '.$e->getMessage(), 'warn'];
            }
        }

        return [true, $venta, null, 'ok'];
    }
}
