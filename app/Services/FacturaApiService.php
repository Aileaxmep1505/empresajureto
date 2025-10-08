<?php

namespace App\Services;

use App\Models\Venta;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class FacturaApiService
{
    protected Client $http;
    protected string $base;
    protected string $disk;

    public function __construct()
    {
        $this->base = rtrim((string) config('services.facturaapi.base_uri'), '/');
        $this->disk = (string) config('services.facturaapi.disk', 'public');

        $this->http = new Client([
            'base_uri'    => $this->base . '/',
            'timeout'     => 45,
            'headers'     => [
                'Authorization' => 'Bearer ' . config('services.facturaapi.token'),
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
            'http_errors' => false,
        ]);
    }

    /**
     * Crea/timbra la factura en Facturapi v2 y guarda PDF/XML localmente si es posible.
     */
    public function facturarVenta(Venta $venta): array
    {
        $venta->loadMissing('cliente', 'items.producto');
        $cli = $venta->cliente;

        // --- RFC y régimen receptor ---
        $rfc = strtoupper(trim((string) ($cli->rfc ?? '')));
        $isGeneric = ($rfc === '' || str_starts_with($rfc, 'XAXX') || str_starts_with($rfc, 'XEXX'));
        $taxSystem = ($cli->regimen ?? $cli->tax_system ?? null) ?: ($isGeneric ? '616' : (string) config('services.facturaapi.regimen', '601'));

        $zip = preg_replace('/\D+/', '', (string) ($cli->cp ?? $cli->postal_code ?? ''));
        if ($zip === '') $zip = '64000';

        $usoCfdi = $isGeneric ? 'S01' : (string) config('services.facturaapi.uso', 'G03');

        // --- Customer (v2) ---
        $customer = [
            'legal_name' => trim((string) ($cli->razon_social ?? $cli->nombre ?? $cli->name ?? 'PUBLICO EN GENERAL')),
            'tax_id'     => $isGeneric ? 'XAXX010101000' : $rfc,
            'tax_system' => (string) $taxSystem,
            'address'    => ['zip' => $zip],
        ];
        if (!empty($cli->email)) $customer['email'] = $cli->email;

        // --- Items con impuestos en product.taxes ---
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
                // si manejas precios con IVA incluido explícitamente:
                // 'tax_included' => true,
            ];

            if ($iva > 0) {
                $product['taxes'] = [[
                    'type' => 'IVA',
                    'rate' => round($iva / 100, 6), // 0.16
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
            'payment_method' => (string) config('services.facturaapi.metodo', 'PPD'),
            'payment_form'   => (string) config('services.facturaapi.forma',  '99'),
            // 'branch'      => 'BRANCH_ID', // si ocupas sucursal específica
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

        // Guardar datos mínimos
        $venta->update([
            'serie'            => Arr::get($body, 'series', $payload['series']),
            'folio'            => Arr::get($body, 'folio_number'), // <- v2 usa folio_number
            'factura_id'       => Arr::get($body, 'id'),
            'factura_uuid'     => Arr::get($body, 'uuid'),
            'factura_pdf_url'  => Arr::get($body, 'links.pdf'), // si viniera
            'factura_xml_url'  => Arr::get($body, 'links.xml'), // si viniera
            'timbrada_en'      => now(),
            'estado'           => 'facturada',
        ]);

        // Si no trajo links, intentamos descargarlos y guardarlos localmente
        if (empty($venta->factura_pdf_url) || empty($venta->factura_xml_url)) {
            $this->guardarArchivos($venta); // ignora silenciosamente si falla
        }

        return $body;
    }

    /**
     * Descarga PDF/XML y los guarda en storage/public, actualizando URLs públicas.
     * Útil también para facturas antiguas que no guardaron links.
     */
    public function guardarArchivos(Venta $venta): void
    {
        if (!$venta->factura_id && !$venta->factura_uuid) return;

        $uuid = $venta->factura_uuid ?: $venta->id;
        $dir  = "facturas/{$uuid}";
        $changed = false;

        // --- PDF ---
        if (empty($venta->factura_pdf_url)) {
            $pdfBytes = $this->descargarArchivo($venta->factura_id, 'pdf');
            if ($pdfBytes !== null) {
                $pdfPath = "{$dir}/{$uuid}.pdf";
                Storage::disk($this->disk)->put($pdfPath, $pdfBytes, 'public');
                $venta->factura_pdf_url = Storage::disk($this->disk)->url($pdfPath);
                $changed = true;
            } else {
                // último recurso: intenta leer link remoto si la API lo muestra en GET /invoices/{id}
                $link = $this->intentarLinkRemoto($venta->factura_id, 'pdf');
                if ($link) { $venta->factura_pdf_url = $link; $changed = true; }
            }
        }

        // --- XML ---
        if (empty($venta->factura_xml_url)) {
            $xmlBytes = $this->descargarArchivo($venta->factura_id, 'xml');
            if ($xmlBytes !== null) {
                $xmlPath = "{$dir}/{$uuid}.xml";
                Storage::disk($this->disk)->put($xmlPath, $xmlBytes, 'public');
                $venta->factura_xml_url = Storage::disk($this->disk)->url($xmlPath);
                $changed = true;
            } else {
                $link = $this->intentarLinkRemoto($venta->factura_id, 'xml');
                if ($link) { $venta->factura_xml_url = $link; $changed = true; }
            }
        }

        if ($changed) $venta->save();
    }

    /**
     * Intenta obtener un link remoto desde GET /invoices/{id}.
     */
    protected function intentarLinkRemoto(string $invoiceId, string $type): ?string
    {
        $res  = $this->http->get("invoices/{$invoiceId}");
        if ($res->getStatusCode() >= 400) return null;

        $json = json_decode((string) $res->getBody(), true);
        if (!is_array($json)) return null;

        $links = Arr::get($json, 'links', []);
        if (!is_array($links)) return null;

        return $type === 'pdf' ? ($links['pdf'] ?? null) : ($links['xml'] ?? null);
    }

    /**
     * Descarga bytes del archivo de Facturapi v2.
     * Intenta varios endpoints conocidos.
     * @return string|null Bytes o null si no se pudo.
     */
    protected function descargarArchivo(string $invoiceId, string $type): ?string
    {
        $candidates = [
            "invoices/{$invoiceId}/files/{$type}", // v2
            "invoices/{$invoiceId}/{$type}",       // compat
        ];

        foreach ($candidates as $url) {
            $res = $this->http->get($url);
            $code = $res->getStatusCode();
            if ($code >= 400) continue;

            $ct = strtolower($res->getHeaderLine('Content-Type') ?? '');
            $body = (string) $res->getBody();

            // Si regresó JSON, tal vez trae { link: "..." } y no bytes
            if (str_contains($ct, 'application/json')) {
                $json = json_decode($body, true);
                $link = $json['link'] ?? $json['url'] ?? null;
                if ($link) {
                    // intenta bajar ese link directo
                    try {
                        $bin = $this->http->get($link);
                        if ($bin->getStatusCode() < 400) {
                            return (string) $bin->getBody();
                        }
                    } catch (\Throwable $e) {
                        // ignora y sigue intentando
                    }
                }
                continue;
            }

            // Si no es JSON, asumimos bytes del archivo
            if ($body !== '') return $body;
        }

        return null;
    }
}
