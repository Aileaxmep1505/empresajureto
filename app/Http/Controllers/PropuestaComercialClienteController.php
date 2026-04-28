<?php

namespace App\Http\Controllers;

use App\Models\PropuestaComercial;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PropuestaComercialClienteController extends Controller
{
    public function show(PropuestaComercial $propuestaComercial)
    {
        $data = $this->buildQuoteData($propuestaComercial, false);

        return view('propuestas_comerciales.cliente', $data);
    }

    public function downloadPdf(PropuestaComercial $propuestaComercial)
    {
        $data = $this->buildQuoteData($propuestaComercial, true);

        $pdf = Pdf::loadView('propuestas_comerciales.cliente_pdf', $data)
            ->setPaper('letter', 'portrait');

        return $pdf->download($data['folio'] . '.pdf');
    }

    public function sendEmail(Request $request, PropuestaComercial $propuestaComercial)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $quoteData = $this->buildQuoteData($propuestaComercial, true);

        $pdf = Pdf::loadView('propuestas_comerciales.cliente_pdf', $quoteData)
            ->setPaper('letter', 'portrait');

        $subject = $data['subject'] ?: 'Cotización ' . $quoteData['folio'];
        $body = $data['message'] ?: 'Hola, adjuntamos la cotización solicitada. Quedamos atentos a tus comentarios.';

        Mail::send([], [], function ($message) use ($data, $subject, $body, $pdf, $quoteData) {
            $message->to($data['email'])
                ->subject($subject)
                ->text($body)
                ->attachData($pdf->output(), $quoteData['folio'] . '.pdf', [
                    'mime' => 'application/pdf',
                ]);
        });

        return back()->with('status', 'Cotización enviada correctamente a ' . $data['email']);
    }

    protected function buildQuoteData(PropuestaComercial $propuestaComercial, bool $pdfMode = false): array
    {
        $propuestaComercial->loadMissing([
            'items.productoSeleccionado',
            'items.matches.product',
            'items.externalMatches',
        ]);

        $folio = $propuestaComercial->titulo
            ?: $propuestaComercial->folio
            ?: ('COT-' . strtoupper(substr(md5($propuestaComercial->id . $propuestaComercial->created_at), 0, 8)));

        $clientName = $propuestaComercial->cliente_nombre
            ?? $propuestaComercial->cliente
            ?? $propuestaComercial->razon_social
            ?? $propuestaComercial->folio
            ?? 'Cliente';

        $clientAttention = $propuestaComercial->atencion
            ?? $propuestaComercial->contacto
            ?? $propuestaComercial->cliente_contacto
            ?? 'Atención';

        $clientEmail = $propuestaComercial->cliente_email
            ?? $propuestaComercial->email
            ?? $propuestaComercial->correo
            ?? null;

        $clientPhone = $propuestaComercial->cliente_telefono
            ?? $propuestaComercial->telefono
            ?? null;

        $clientAddress = $propuestaComercial->cliente_direccion
            ?? $propuestaComercial->direccion
            ?? null;

        $clientRfc = $propuestaComercial->cliente_rfc
            ?? $propuestaComercial->rfc
            ?? null;

        $items = $propuestaComercial->items
            ->sortBy('sort')
            ->values()
            ->map(function ($item, $index) {
                $product = $item->productoSeleccionado;
                $qty = (float) ($item->cantidad_cotizada ?: $item->cantidad_maxima ?: $item->cantidad_minima ?: 1);
                $unit = $item->unidad_solicitada ?: 'pz';
                $description = $item->descripcion_original ?: optional($product)->name ?: 'Producto';
                $price = (float) ($item->precio_unitario ?: optional($product)->price ?: optional($product)->precio ?: 0);
                $subtotal = (float) ($item->subtotal ?: ($qty * $price));

                return [
                    'number' => $index + 1,
                    'quantity' => $qty,
                    'unit' => $unit,
                    'description' => $description,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ];
            });

        $subtotal = (float) ($items->sum('subtotal') ?: $propuestaComercial->subtotal ?: 0);

        $taxPercent = (float) ($propuestaComercial->porcentaje_impuesto ?? 16);
        $tax = (float) ($propuestaComercial->impuesto_total ?? round($subtotal * ($taxPercent / 100), 2));

        $discount = (float) ($propuestaComercial->descuento_total ?? 0);
        $total = (float) ($propuestaComercial->total ?: ($subtotal - $discount + $tax));

        $createdAt = $propuestaComercial->created_at ?: now();

        $company = [
            'name' => env('QUOTE_COMPANY_NAME', config('app.name', 'JURETO Enterprise')),
            'address' => env('QUOTE_COMPANY_ADDRESS', 'Av. Reforma 123, Col. Centro, CDMX'),
            'phone' => env('QUOTE_COMPANY_PHONE', '+52 55 1234 5678'),
            'email' => env('QUOTE_COMPANY_EMAIL', 'ventas@jureto.com.mx'),
            'rfc' => env('QUOTE_COMPANY_RFC', 'RFC: XAXX010101000'),
        ];

        return [
            'pdfMode' => $pdfMode,
            'propuestaComercial' => $propuestaComercial,
            'folio' => $folio,
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'taxPercent' => $taxPercent,
            'total' => $total,
            'createdAt' => $createdAt,
            'validUntil' => $createdAt->copy()->addDays(15),
            'company' => $company,
            'client' => [
                'name' => $clientName,
                'attention' => $clientAttention,
                'email' => $clientEmail,
                'phone' => $clientPhone,
                'address' => $clientAddress,
                'rfc' => $clientRfc,
            ],
        ];
    }
}