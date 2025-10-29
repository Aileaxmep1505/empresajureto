<?php
// app/Services/FacturapiWebClient.php
namespace App\Services;

use Facturapi\Facturapi;
use Illuminate\Support\Facades\Log;

class FacturapiWebClient
{
    private Facturapi $api;
    private array $cfg;

    public function __construct()
    {
        $this->cfg = config('services.facturaapi_web', []);
        $key = $this->cfg['key'] ?? null;
        if (blank($key)) {
            throw new \RuntimeException('Falta FACTURAAPI_WEB_KEY en .env/config.');
        }

        // ✅ Constructor correcto: (api_key, api_version)
        // No pases arrays como 2º parámetro.
        $this->api = new Facturapi($key, 'v2');
    }

    public function createInvoice(array $customer, array $items, array $opts = []): array
    {
        $payload = [
            'customer'       => $customer,
            'items'          => $items,
            'payment_form'   => $opts['payment_form']   ?? ($this->cfg['forma'] ?? '04'),
            'payment_method' => $opts['payment_method'] ?? ($this->cfg['metodo'] ?? 'PUE'),
            'currency'       => 'MXN',
            'use'            => $opts['use']            ?? ($this->cfg['uso'] ?? 'G03'),
            'series'         => $opts['series']         ?? ($this->cfg['series'] ?? 'F'),
        ];

        $invoice = $this->api->Invoices->create($payload);
        return json_decode(json_encode($invoice), true);
    }

    public function sendInvoiceEmail(string $invoiceId): void
    {
        try {
            $this->api->Invoices->send_by_email($invoiceId);
        } catch (\Throwable $e) {
            Log::warning('Facturapi WEB send_by_email failed: '.$e->getMessage(), ['invoice_id'=>$invoiceId]);
        }
    }

    public function downloadPdf(string $invoiceId): string
    {
        return $this->api->Invoices->download_pdf($invoiceId);
    }

    public function downloadXml(string $invoiceId): string
    {
        return $this->api->Invoices->download_xml($invoiceId);
    }

    public function retrieve(string $invoiceId): array
    {
        $inv = $this->api->Invoices->retrieve($invoiceId);
        return json_decode(json_encode($inv), true);
    }
}
