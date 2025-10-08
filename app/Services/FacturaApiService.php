<?php

namespace App\Services;

use App\Models\Venta;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;

class FacturaApiService
{
    protected Client $http;
    protected string $base;

    public function __construct()
    {
        $this->base = rtrim((string) config('services.facturaapi.base_uri'), '/');
        $this->http = new Client([
            'base_uri'    => $this->base . '/',
            'timeout'     => 30,
            'headers'     => [
                'Authorization' => 'Bearer ' . config('services.facturaapi.token'),
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
            'http_errors' => false,
        ]);
    }

    public function facturarVenta(Venta $venta): array
    {
        $venta->loadMissing('cliente', 'items.producto');
        $cli = $venta->cliente;

        // --- RFC y régimen del receptor ---
        $rfc = strtoupper(trim((string) ($cli->rfc ?? '')));
        $isGeneric = ($rfc === '' || str_starts_with($rfc, 'XAXX') || str_starts_with($rfc, 'XEXX'));

        // CFDI 4.0: para público en general debe ser 616
        $taxSystem =
            ($cli->regimen ?? $cli->tax_system ?? null)
            ?: ($isGeneric ? '616' : (string) config('services.facturaapi.regimen', '601'));

        // CP del receptor obligatorio en 4.0
        $zip = preg_replace('/\D+/', '', (string) ($cli->cp ?? $cli->postal_code ?? ''));
        if ($zip === '') {
            // fallback razonable (no es el lugar de expedición; es el CP del receptor)
            $zip = '64000';
        }

        // Uso CFDI
        $usoCfdi = $isGeneric ? 'S01' : (string) config('services.facturaapi.uso', 'G03');

        // --- Customer (v2) ---
        $customer = [
            'legal_name' => trim((string) ($cli->razon_social ?? $cli->nombre ?? $cli->name ?? 'PUBLICO EN GENERAL')),
            'tax_id'     => $isGeneric ? 'XAXX010101000' : $rfc,
            'tax_system' => (string) $taxSystem,
            'address'    => ['zip' => $zip],
        ];
        if (!empty($cli->email)) $customer['email'] = $cli->email;

        // --- Items: impuestos dentro de product.taxes ---
        $items = [];
        foreach ($venta->items as $it) {
            $qty   = (float) $it->cantidad;
            $price = round((float) $it->precio_unitario, 2);
            $disc  = round((float) ($it->descuento ?? 0), 2);
            $iva   = (float) ($it->iva_porcentaje ?? 0);

            $product_key = $it->producto->clave_prod_serv ?? '01010101';
            $unit_key    = $it->producto->clave_unidad   ?? 'H87';
            $descTxt     = trim((string) ($it->descripcion ?? $it->producto->name ?? 'Concepto'));

            $product = [
                'description' => $descTxt,
                'product_key' => $product_key,
                'unit_key'    => $unit_key,
                'price'       => $price,
            ];

            // Impuestos en v2 -> product.taxes
            if ($iva > 0) {
                $product['taxes'] = [[
                    'type' => 'IVA',
                    'rate' => round($iva / 100, 6), // p.ej. 0.16
                ]];
            }

            $item = [
                'product'  => $product,
                'quantity' => $qty,
            ];
            if ($disc > 0) {
                $item['discount'] = $disc;
            }

            $items[] = $item;
        }

        // --- Payload v2 (sin expedition_place) ---
        $payload = [
            'type'           => (string) config('services.facturaapi.tipo', 'I'),
            'series'         => $venta->serie ?? (string) config('services.facturaapi.serie', 'A'),
            'currency'       => $venta->moneda ?: (string) config('services.facturaapi.moneda', 'MXN'),
            'use'            => $usoCfdi,
            'payment_method' => (string) config('services.facturaapi.metodo', 'PPD'), // PPD/PUE
            'payment_form'   => (string) config('services.facturaapi.forma',  '99'),  // 99/01/etc
            // 'branch'      => 'BRANCH_ID', // <-- si quieres forzar sucursal, usa esto en lugar de expedition_place
            'customer'       => $customer,
            'items'          => $items,
        ];

        $res  = $this->http->post('invoices', ['json' => $payload]);
        $code = $res->getStatusCode();
        $body = json_decode((string) $res->getBody(), true) ?: [];

        if ($code >= 400) {
            $msg = Arr::get($body, 'message') ?: Arr::get($body, 'error.message') ?: 'Error timbrando la factura.';
            throw new \RuntimeException($msg);
        }

        $venta->update([
            'serie'            => Arr::get($body, 'series', $payload['series']),
            'folio'            => Arr::get($body, 'folio'),
            'factura_id'       => Arr::get($body, 'id'),
            'factura_uuid'     => Arr::get($body, 'uuid'),
            'factura_pdf_url'  => Arr::get($body, 'links.pdf'),
            'factura_xml_url'  => Arr::get($body, 'links.xml'),
            'timbrada_en'      => now(),
            'estado'           => 'facturada',
        ]);

        return $body;
    }
}
