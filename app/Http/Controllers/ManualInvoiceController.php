<?php

namespace App\Http\Controllers;

use App\Models\ManualInvoice;
use App\Models\ManualInvoiceItem;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ManualInvoiceController extends Controller
{
    /**
     * Listado de facturas (borradores + timbradas)
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $q      = trim((string) $request->get('q', ''));

        $query = ManualInvoice::with('client')
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->whereHas('client', function ($qc) use ($q) {
                        $qc->where('nombre', 'like', "%{$q}%")
                           ->orWhere('rfc', 'like', "%{$q}%");
                    })
                    ->orWhere('serie', 'like', "%{$q}%")
                    ->orWhere('folio', 'like', "%{$q}%");
            });
        }

        $invoices = $query->paginate(20)->withQueryString();

        return view('manual_invoices.index', compact('invoices', 'status', 'q'));
    }

    /**
     * Formulario de creación:
     *   - seleccionar cliente
     *   - agregar productos (items)
     */
    public function create()
    {
        $clients = Client::orderBy('nombre')->where('estatus', true)->get();
        $products = Product::orderBy('name')->where('active', true)->get();

        return view('manual_invoices.create', compact('clients', 'products'));
    }

    /**
     * Guardar borrador de factura + items
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'type'      => ['required', 'in:I,E,P'],
            'notes'     => ['nullable', 'string'],

            // items: array de conceptos
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.product_id'    => ['nullable', 'exists:products,id'],
            'items.*.description'   => ['required', 'string', 'max:255'],
            'items.*.quantity'      => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'    => ['required', 'numeric', 'min:0'],
            'items.*.discount'      => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate'      => ['nullable', 'numeric', 'min:0'],
            'items.*.unit'          => ['nullable', 'string', 'max:20'],
            'items.*.unit_code'     => ['nullable', 'string', 'max:10'],
            'items.*.product_key'   => ['nullable', 'string', 'max:10'],
        ]);

        $client = Client::findOrFail($data['client_id']);

        DB::beginTransaction();

        try {
            // Calculamos totales a partir de los items
            $subtotal      = 0;
            $discountTotal = 0;
            $taxTotal      = 0;

            $invoice = new ManualInvoice();
            $invoice->client_id       = $client->id;
            $invoice->type            = $data['type'];
            $invoice->status          = 'draft';
            $invoice->notes           = $data['notes'] ?? null;
            $invoice->currency        = 'MXN';

            // Snapshot del cliente
            $invoice->receiver_name   = $client->nombre;
            $invoice->receiver_rfc    = $client->rfc;
            $invoice->receiver_email  = $client->email;

            $invoice->save();

            foreach ($data['items'] as $row) {
                $qty      = (float) $row['quantity'];
                $price    = (float) $row['unit_price'];
                $discount = isset($row['discount']) ? (float) $row['discount'] : 0;
                $taxRate  = isset($row['tax_rate']) ? (float) $row['tax_rate'] : 0;

                $lineSubtotal = max($qty * $price - $discount, 0);
                $lineTax      = $lineSubtotal * ($taxRate / 100);
                $lineTotal    = $lineSubtotal + $lineTax;

                $subtotal      += $lineSubtotal;
                $discountTotal += $discount;
                $taxTotal      += $lineTax;

                $product = null;
                if (!empty($row['product_id'])) {
                    $product = Product::find($row['product_id']);
                }

                ManualInvoiceItem::create([
                    'manual_invoice_id' => $invoice->id,
                    'product_id'        => $row['product_id'] ?? null,
                    'description'       => $row['description'],
                    'sku'               => $product->sku ?? null,
                    'unit'              => $row['unit'] ?? ($product->unit ?? null),
                    'unit_code'         => $row['unit_code'] ?? null,
                    'product_key'       => $row['product_key'] ?? ($product->clave_sat ?? null),
                    'quantity'          => $qty,
                    'unit_price'        => $price,
                    'discount'          => $discount,
                    'subtotal'          => $lineSubtotal,
                    'tax'               => $lineTax,
                    'total'             => $lineTotal,
                    'tax_rate'          => $taxRate,
                ]);
            }

            $invoice->subtotal       = $subtotal;
            $invoice->discount_total = $discountTotal;
            $invoice->tax_total      = $taxTotal;
            $invoice->total          = $subtotal + $taxTotal;
            $invoice->save();

            DB::commit();

            return redirect()
                ->route('manual_invoices.index', ['status' => 'draft'])
                ->with('status', 'Factura guardada como borrador.');
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(ManualInvoice $manualInvoice)
    {
        $manualInvoice->load(['client', 'items.product']);

        return view('manual_invoices.show', [
            'invoice' => $manualInvoice,
        ]);
    }

    public function edit(ManualInvoice $manualInvoice)
    {
        if ($manualInvoice->status !== 'draft') {
            abort(403, 'Sólo puedes editar facturas en borrador.');
        }

        $manualInvoice->load('items.product');

        $clients  = Client::orderBy('nombre')->where('estatus', true)->get();
        $products = Product::orderBy('name')->where('active', true)->get();

        return view('manual_invoices.edit', [
            'invoice'  => $manualInvoice,
            'clients'  => $clients,
            'products' => $products,
        ]);
    }

    public function update(Request $request, ManualInvoice $manualInvoice)
    {
        if ($manualInvoice->status !== 'draft') {
            abort(403, 'Sólo puedes editar facturas en borrador.');
        }

        $data = $request->validate([
            'client_id'             => ['required', 'exists:clients,id'],
            'type'                  => ['required', 'in:I,E,P'],
            'notes'                 => ['nullable', 'string'],

            'items'                 => ['required', 'array', 'min:1'],
            'items.*.id'            => ['nullable', 'integer', 'exists:manual_invoice_items,id'],
            'items.*.product_id'    => ['nullable', 'exists:products,id'],
            'items.*.description'   => ['required', 'string', 'max:255'],
            'items.*.quantity'      => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'    => ['required', 'numeric', 'min:0'],
            'items.*.discount'      => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate'      => ['nullable', 'numeric', 'min:0'],
            'items.*.unit'          => ['nullable', 'string', 'max:20'],
            'items.*.unit_code'     => ['nullable', 'string', 'max:10'],
            'items.*.product_key'   => ['nullable', 'string', 'max:10'],
        ]);

        $client = Client::findOrFail($data['client_id']);

        DB::beginTransaction();

        try {
            $manualInvoice->client_id      = $client->id;
            $manualInvoice->type           = $data['type'];
            $manualInvoice->notes          = $data['notes'] ?? null;
            $manualInvoice->receiver_name  = $client->nombre;
            $manualInvoice->receiver_rfc   = $client->rfc;
            $manualInvoice->receiver_email = $client->email;
            $manualInvoice->save();

            $subtotal      = 0;
            $discountTotal = 0;
            $taxTotal      = 0;

            $existingIds = [];

            foreach ($data['items'] as $row) {
                $qty      = (float) $row['quantity'];
                $price    = (float) $row['unit_price'];
                $discount = isset($row['discount']) ? (float) $row['discount'] : 0;
                $taxRate  = isset($row['tax_rate']) ? (float) $row['tax_rate'] : 0;

                $lineSubtotal = max($qty * $price - $discount, 0);
                $lineTax      = $lineSubtotal * ($taxRate / 100);
                $lineTotal    = $lineSubtotal + $lineTax;

                $subtotal      += $lineSubtotal;
                $discountTotal += $discount;
                $taxTotal      += $lineTax;

                $product = null;
                if (!empty($row['product_id'])) {
                    $product = Product::find($row['product_id']);
                }

                if (!empty($row['id'])) {
                    $item = ManualInvoiceItem::where('manual_invoice_id', $manualInvoice->id)
                        ->where('id', $row['id'])
                        ->firstOrFail();
                } else {
                    $item = new ManualInvoiceItem();
                    $item->manual_invoice_id = $manualInvoice->id;
                }

                $item->product_id  = $row['product_id'] ?? null;
                $item->description = $row['description'];
                $item->sku         = $product->sku ?? $item->sku;
                $item->unit        = $row['unit'] ?? ($product->unit ?? $item->unit);
                $item->unit_code   = $row['unit_code'] ?? $item->unit_code;
                $item->product_key = $row['product_key'] ?? ($product->clave_sat ?? $item->product_key);
                $item->quantity    = $qty;
                $item->unit_price  = $price;
                $item->discount    = $discount;
                $item->subtotal    = $lineSubtotal;
                $item->tax         = $lineTax;
                $item->total       = $lineTotal;
                $item->tax_rate    = $taxRate;

                $item->save();

                $existingIds[] = $item->id;
            }

            // Borra conceptos que ya no vienen en el form
            $manualInvoice->items()
                ->whereNotIn('id', $existingIds)
                ->delete();

            $manualInvoice->subtotal       = $subtotal;
            $manualInvoice->discount_total = $discountTotal;
            $manualInvoice->tax_total      = $taxTotal;
            $manualInvoice->total          = $subtotal + $taxTotal;
            $manualInvoice->save();

            DB::commit();

            return redirect()
                ->route('manual_invoices.index', ['status' => 'draft'])
                ->with('status', 'Factura actualizada.');
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy(ManualInvoice $manualInvoice)
    {
        if ($manualInvoice->status !== 'draft') {
            abort(403, 'Sólo puedes eliminar facturas en borrador.');
        }

        $manualInvoice->delete();

        return redirect()
            ->route('manual_invoices.index', ['status' => 'draft'])
            ->with('status', 'Factura eliminada.');
    }

    /**
     * Timbrar con Facturapi (desde borrador).
     */
    public function stamp(ManualInvoice $manualInvoice)
    {
        // Sólo borrador
        if ($manualInvoice->status !== 'draft') {
            return redirect()
                ->route('manual_invoices.index')
                ->with('error', 'Sólo puedes timbrar facturas en borrador.');
        }

        // Cargamos cliente + items
        $manualInvoice->loadMissing(['client', 'items']);
        $client = $manualInvoice->client;

        if (!$client || !$client->rfc || !$client->nombre) {
            return redirect()
                ->back()
                ->with('error', 'El cliente debe tener nombre y RFC para timbrar.');
        }

        if ($manualInvoice->items->isEmpty()) {
            return redirect()
                ->back()
                ->with('error', 'La factura no tiene conceptos para timbrar.');
        }

        // === API KEY y BASE URI usando tus variables ===
        $apiKey = env('FACTURAPI_KEY')
            ?: env('FACTURAAPI_KEY')
            ?: env('FACTURAAPI_WEB_KEY');

        if (!$apiKey) {
            return redirect()
                ->back()
                ->with('error', 'No hay FACTURAPI_KEY / FACTURAAPI_KEY configurada en el .env');
        }

        $baseUri = env('FACTURAPI_BASE_URI')
            ?: env('FACTURAAPI_BASE_URI')
            ?: 'https://www.facturapi.io/v2';

        // ===== Construimos payload de Facturapi =====
        $itemsPayload = [];
        foreach ($manualInvoice->items as $item) {
            $itemsPayload[] = [
                'quantity' => (float) $item->quantity,
                'discount' => (float) $item->discount,
                'product'  => [
                    'description' => $item->description,
                    'product_key' => $item->product_key ?: '01010101',
                    'price'       => (float) $item->unit_price,
                    'unit_key'    => $item->unit_code ?: 'H87', // PZA
                    'unit_name'   => $item->unit ?: 'Pieza',
                ],
            ];
        }

        /**
         * ==== Determinar UsoCFDI compatible con régimen fiscal ====
         * - Si la factura ya tiene 'cfdi_uso', lo respetamos.
         * - Si no, elegimos uno automático según el régimen (cliente->regimen_fiscal).
         */
        $uso = $manualInvoice->cfdi_uso;
        if (!$uso) {
            $regimen = $client->regimen_fiscal;

            // Regímenes donde G03 (Gastos en general) suele ser válido
            $regimenGastos = [
                '601', // General de Ley PM
                '603', // PM sin fines de lucro
                '612', // PF con actividad empresarial y profesional
                '620', // Sociedades cooperativas de producción
                '621', // Incorporación Fiscal
                '622', // Actividades agrícolas, ganaderas, silvícolas y pesqueras
                '623', // Opcional para grupos de sociedades
                '624', // Coordinados
                '626', // RESICO
            ];

            if (in_array($regimen, $regimenGastos, true)) {
                $uso = 'G03';
            } elseif (in_array($regimen, ['605','606','607','608','610','611','614','615','616'], true)) {
                // Regímenes de sueldos, demás ingresos, premios, sin obligaciones, etc.
                $uso = 'S01'; // Sin efectos fiscales
            } else {
                // Fallback genérico
                $uso = 'G03';
            }
        }

        // Datos mínimos del receptor
        $payload = [
            'customer' => [
                'legal_name' => $client->razon_social ?: $client->nombre,
                'tax_id'     => $client->rfc,
                'email'      => $client->email,
                'tax_system' => $client->regimen_fiscal ?? '601',
                'address'    => [
                    'zip' => $client->cp ?: '00000',
                ],
            ],
            'items'          => $itemsPayload,
            'use'            => $uso,                                    // UsoCFDI ya calculado
            'payment_form'   => $manualInvoice->payment_form ?? '03',    // transferencia
            'payment_method' => $manualInvoice->payment_method ?? 'PUE',
            'currency'       => $manualInvoice->currency ?: 'MXN',
        ];

        // Opcional: si quieres llevar serie/folio desde tu sistema
        if (!empty($manualInvoice->serie)) {
            $payload['series'] = $manualInvoice->serie;
        }
        if (!empty($manualInvoice->folio)) {
            $payload['folio_number'] = $manualInvoice->folio;
        }

        try {
            $response = Http::withBasicAuth($apiKey, '')
                ->post(rtrim($baseUri, '/') . '/invoices', $payload);

            if (!$response->successful()) {
                $body   = $response->body();
                $json   = json_decode($body, true);
                $apiMsg = $json['message'] ?? null;
                $status = $response->status();

                Log::error('Facturapi error timbrar', [
                    'manual_invoice_id' => $manualInvoice->id,
                    'status'            => $status,
                    'body'              => $body,
                ]);

                $msg = 'Facturapi respondió con un error al timbrar.';
                if ($apiMsg) {
                    $msg .= ' Detalle: '.$apiMsg;
                }

                return redirect()
                    ->back()
                    ->with('error', $msg);
            }

            $data = $response->json();

            // Guardamos datos de timbrado
            $manualInvoice->facturapi_id        = $data['id'] ?? null;
            $manualInvoice->facturapi_uuid      = $data['uuid'] ?? null;
            $manualInvoice->verification_url    = $data['verification_url'] ?? null;
            $manualInvoice->facturapi_status    = $data['status'] ?? null;
            $manualInvoice->cancellation_status = $data['cancellation']['status'] ?? null;
            $manualInvoice->stamped_at          = now();
            $manualInvoice->status              = 'valid';

            // Si facturapi devolvió serie/folio, los sincronizamos
            if (!empty($data['series'])) {
                $manualInvoice->serie = $data['series'];
            }
            if (!empty($data['folio_number'])) {
                $manualInvoice->folio = $data['folio_number'];
            }

            $manualInvoice->save();

            return redirect()
                ->route('manual_invoices.index', ['status' => 'valid'])
                ->with('status', 'Factura timbrada correctamente.');
        } catch (\Throwable $e) {
            Log::error('Excepción al timbrar con Facturapi', [
                'manual_invoice_id' => $manualInvoice->id,
                'msg'               => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al timbrar: '.$e->getMessage());
        }
    }
     /**
     * Descargar PDF desde Facturapi.
     */
    public function downloadPdf(ManualInvoice $manualInvoice)
    {
        if (!$manualInvoice->facturapi_id) {
            return back()->with('error', 'Esta factura no tiene un ID de Facturapi para descargar PDF.');
        }

        $apiKey = env('FACTURAPI_KEY')
            ?: env('FACTURAAPI_KEY')
            ?: env('FACTURAAPI_WEB_KEY');

        if (!$apiKey) {
            return back()->with('error', 'No hay FACTURAPI_KEY configurada.');
        }

        $baseUri = env('FACTURAPI_BASE_URI')
            ?: env('FACTURAAPI_BASE_URI')
            ?: 'https://www.facturapi.io/v2';

        $response = Http::withBasicAuth($apiKey, '')
            ->get(rtrim($baseUri, '/') . '/invoices/'.$manualInvoice->facturapi_id.'/pdf');

        if (!$response->successful()) {
            return back()->with('error', 'No se pudo descargar el PDF desde Facturapi.');
        }

        $fileName = 'CFDI-'.$manualInvoice->facturapi_uuid.'.pdf';

        return response($response->body(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$fileName.'"');
    }

    /**
     * Descargar XML desde Facturapi.
     */
    public function downloadXml(ManualInvoice $manualInvoice)
    {
        if (!$manualInvoice->facturapi_id) {
            return back()->with('error', 'Esta factura no tiene un ID de Facturapi para descargar XML.');
        }

        $apiKey = env('FACTURAPI_KEY')
            ?: env('FACTURAAPI_KEY')
            ?: env('FACTURAAPI_WEB_KEY');

        if (!$apiKey) {
            return back()->with('error', 'No hay FACTURAPI_KEY configurada.');
        }

        $baseUri = env('FACTURAPI_BASE_URI')
            ?: env('FACTURAAPI_BASE_URI')
            ?: 'https://www.facturapi.io/v2';

        $response = Http::withBasicAuth($apiKey, '')
            ->get(rtrim($baseUri, '/') . '/invoices/'.$manualInvoice->facturapi_id.'/xml');

        if (!$response->successful()) {
            return back()->with('error', 'No se pudo descargar el XML desde Facturapi.');
        }

        $fileName = 'CFDI-'.$manualInvoice->facturapi_uuid.'.xml';

        return response($response->body(), 200)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', 'attachment; filename="'.$fileName.'"');
    }
}
