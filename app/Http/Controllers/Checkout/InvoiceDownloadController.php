<?php
// app/Http/Controllers/Checkout/InvoiceDownloadController.php
namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Services\FacturapiWebClient;

class InvoiceDownloadController extends Controller
{
    public function pdf(string $id, FacturapiWebClient $svc)
    {
        try {
            $binary = $svc->downloadPdf($id);
        } catch (\Throwable $e) {
            abort(404, 'No pudimos descargar el PDF de la factura.');
        }
        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="factura_'.$id.'.pdf"',
        ]);
    }

    public function xml(string $id, FacturapiWebClient $svc)
    {
        try {
            $xml = $svc->downloadXml($id);
        } catch (\Throwable $e) {
            abort(404, 'No pudimos descargar el XML de la factura.');
        }
        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="factura_'.$id.'.xml"',
        ]);
    }
}
