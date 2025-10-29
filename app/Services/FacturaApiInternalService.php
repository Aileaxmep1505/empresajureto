<?php

namespace App\Services;

use App\Models\Venta;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FacturaApiInternalService
{
    protected Client $http;
    protected string $base;
    protected string $disk;
    protected array $cfg;

    public function __construct()
    {
        $this->cfg  = config('services.facturaapi_internal', []);
        $this->base = rtrim((string) ($this->cfg['base_uri'] ?? ''), '/');
        $this->disk = (string) ($this->cfg['disk'] ?? 'public');

        $token = $this->cfg['key'] ?? null;
        if (blank($token)) {
            throw new \RuntimeException('Falta FACTURAAPI_INT_KEY en .env/config.');
        }

        $this->http = new Client([
            'base_uri'    => $this->base . '/',
            'timeout'     => 45,
            'headers'     => [
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
            'http_errors' => false,
        ]);
    }

    /**
     * Crea/timbra la factura (CFDI v2) para una venta interna.
     * Descarga y guarda PDF/XML si es posible.
     */
    public function facturarVenta(Venta $venta): array
    {
        $venta->loadMissing('cliente', 'items.producto');
        $cli = $venta->cliente;

        // --- RFC / régimen receptor ---
        $rfc = strtoupper(trim((string) ($cli->rfc ?? '')));
        $isGeneric = ($rfc === '' || str_starts_with($rfc, 'XAXX') || str_starts_with($rfc, 'XEXX'));
        $taxSystem = ($cli->regimen ?? $cli->tax_system ?? null) ?: ($isGeneric ? '616' : (string) ($this->cfg['regimen_default'] ?? '601'));

        $zip = preg_replace('/\D+/', '', (string) ($cli->cp ?? $cli->postal_code ?? ''));
        if ($zip === '') $zip = (string) ($this->cfg['lugar_expedicion'] ?? '64000');

        $usoCfdi = $isGeneric ? 'S01' : (string) ($this->cfg['uso'] ?? 'G03');

        // --- Customer ---
        $customer = [
            'legal_name' => trim((string) ($cli->razon_social ?? $cli->nombre ?? $cli->name ?? 'PUBLICO EN GENERAL')),
            'tax_id'     => $isGeneric ? 'XAXX010101000' : $rfc,
            'tax_system' => (string) $taxSystem,
            'address'    => ['zip' => $zip],
        ];
        if (!empty($cli->email)) $customer['email'] = $cli->email;

        // --- Items (ajusta tax_included según tu política interna) ---
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
                // Si manejas precios con IVA incluido: descomenta
                // 'tax_included' => true,
            ];
            if ($iva > 0) {
                $product['taxes'] = [[
                    'type' => 'IVA',
                    'rate' => round($iva / 100, 6),
                ]];
            }

            $item = [
                'product'  => $product,
                'quantity' => $qty,
            ];
            if ($disc > 0) $item['discount'] = $disc;

            $items[] = $item;
        }

        // --- Payload v2 ---
        $payload = [
            'type'           => (string) ($this->cfg['tipo']   ?? 'I'),
            'series'         => $venta->serie ?? (string) ($this->cfg['serie']  ?? 'A'),
            'currency'       => $venta->moneda ?: (string) ($this->cfg['moneda'] ?? 'MXN'),
            'use'            => $usoCfdi,
            'payment_method' => (string) ($this->cfg['metodo'] ?? 'PPD'),
            'payment_form'   => (string) ($this->cfg['forma']  ?? '99'),
            // 'branch'      => 'BRANCH_ID', // si usas sucursal específica en Facturapi
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
            'folio'            => Arr::get($body, 'folio_number'),
            'factura_id'       => Arr::get($body, 'id'),
            'factura_uuid'     => Arr::get($body, 'uuid'),
            'factura_pdf_url'  => Arr::get($body, 'links.pdf'),
            'factura_xml_url'  => Arr::get($body, 'links.xml'),
            'timbrada_en'      => now(),
            'estado'           => 'facturada',
        ]);

        // Si no trajo links públicos, intenta descargarlos y guardarlos
        if (empty($venta->factura_pdf_url) || empty($venta->factura_xml_url)) {
            $this->guardarArchivos($venta);
        }

        return $body;
    }

    /** Envía la factura por correo usando el servicio de Facturapi (v2: POST /invoices/{id}/email) */
    public function enviarPorCorreo(string $invoiceId): void
    {
        try {
            $resp = $this->http->post("invoices/{$invoiceId}/email");
            if ($resp->getStatusCode() >= 400) {
                $j = json_decode((string)$resp->getBody(), true);
                $msg = $j['message'] ?? $j['error']['message'] ?? 'No se pudo enviar por correo.';
                Log::warning('Facturapi INTERNAL email fail: '.$msg, ['invoice_id'=>$invoiceId]);
            }
        } catch (\Throwable $e) {
            Log::warning('Facturapi INTERNAL email exception: '.$e->getMessage(), ['invoice_id'=>$invoiceId]);
        }
    }

    /** Descarga PDF/XML y guarda en storage, actualiza URLs públicas en la venta */
    public function guardarArchivos(Venta $venta): void
    {
        if (!$venta->factura_id && !$venta->factura_uuid) return;

        $uuid = $venta->factura_uuid ?: $venta->id;
        $dir  = "facturas/{$uuid}";
        $changed = false;

        // PDF
        if (empty($venta->factura_pdf_url)) {
            $pdfBytes = $this->descargarArchivo($venta->factura_id, 'pdf');
            if ($pdfBytes !== null) {
                $pdfPath = "{$dir}/{$uuid}.pdf";
                Storage::disk($this->disk)->put($pdfPath, $pdfBytes, 'public');
                $venta->factura_pdf_url = Storage::disk($this->disk)->url($pdfPath);
                $changed = true;
            } else {
                $link = $this->intentarLinkRemoto($venta->factura_id, 'pdf');
                if ($link) { $venta->factura_pdf_url = $link; $changed = true; }
            }
        }

        // XML
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

    protected function intentarLinkRemoto(string $invoiceId, string $type): ?string
    {
        $res  = $this->http->get("invoices/{$invoiceId}");
        if ($res->getStatusCode() >= 400) return null;

        $json = json_decode((string) $res->getBody(), true);
        $links = Arr::get($json, 'links', []);
        if (!is_array($links)) return null;

        return $type === 'pdf' ? ($links['pdf'] ?? null) : ($links['xml'] ?? null);
    }

    protected function descargarArchivo(string $invoiceId, string $type): ?string
    {
        $candidates = [
            "invoices/{$invoiceId}/files/{$type}", // v2
            "invoices/{ $invoiceId }/{$type}",     // compat
        ];

        foreach ($candidates as $url) {
            try {
                $res = $this->http->get($url);
            } catch (\Throwable $e) {
                continue;
            }
            if ($res->getStatusCode() >= 400) continue;

            $ct = strtolower($res->getHeaderLine('Content-Type') ?? '');
            $body = (string) $res->getBody();

            if (str_contains($ct, 'application/json')) {
                $json = json_decode($body, true);
                $link = $json['link'] ?? $json['url'] ?? null;
                if ($link) {
                    try {
                        $bin = $this->http->get($link);
                        if ($bin->getStatusCode() < 400) return (string) $bin->getBody();
                    } catch (\Throwable $e) { /* noop */ }
                }
                continue;
            }
            if ($body !== '') return $body;
        }

        return null;
    }
}
