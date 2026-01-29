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
     * Formulario de creación
     */
    public function create()
    {
        $clients  = Client::orderBy('nombre')->where('estatus', true)->get();
        $products = Product::orderBy('name')->where('active', true)->get();

        return view('manual_invoices.create', compact('clients', 'products'));
    }

    /**
     * Guardar borrador de factura + items (LOCAL)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'type'      => ['required', 'in:I,E,P'],
            'notes'     => ['nullable', 'string'],

            'pay_currency'   => ['required', 'string', 'size:3'],
            'exchange_rate'  => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:PUE,PPD'],
            'payment_form'   => ['required', 'string', 'size:2'],
            'cfdi_use'       => ['required', 'string', 'max:5'],
            'exportation'    => ['required', 'string', 'size:2'],

            'items'               => ['required', 'array', 'min:1'],
            'items.*.product_id'  => ['nullable', 'exists:products,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity'    => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'  => ['required', 'numeric', 'min:0'],
            'items.*.discount'    => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate'    => ['nullable', 'numeric', 'min:0'],
            'items.*.unit'        => ['nullable', 'string', 'max:20'],
            'items.*.unit_code'   => ['nullable', 'string', 'max:10'],
            'items.*.product_key' => ['nullable', 'string', 'max:10'],
        ]);

        $client = Client::findOrFail($data['client_id']);

        DB::beginTransaction();

        try {
            $subtotal      = 0;
            $discountTotal = 0;
            $taxTotal      = 0;

            $invoice = new ManualInvoice();
            $invoice->client_id = $client->id;
            $invoice->type      = $data['type'];
            $invoice->status    = 'draft';
            $invoice->notes     = $data['notes'] ?? null;

            $invoice->currency       = $data['pay_currency'];
            $invoice->exchange_rate  = $data['exchange_rate'] ?? null;
            $invoice->payment_method = $data['payment_method'];
            $invoice->payment_form   = $data['payment_form'];
            $invoice->cfdi_use       = $data['cfdi_use'];
            $invoice->exportation    = $data['exportation'];

            $invoice->receiver_name  = $client->nombre;
            $invoice->receiver_rfc   = $client->rfc;
            $invoice->receiver_email = $client->email;

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
            'client_id' => ['required', 'exists:clients,id'],
            'type'      => ['required', 'in:I,E,P'],
            'notes'     => ['nullable', 'string'],

            'pay_currency'   => ['required', 'string', 'size:3'],
            'exchange_rate'  => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:PUE,PPD'],
            'payment_form'   => ['required', 'string', 'size:2'],
            'cfdi_use'       => ['required', 'string', 'max:5'],
            'exportation'    => ['required', 'string', 'size:2'],

            'items'               => ['required', 'array', 'min:1'],
            'items.*.id'          => ['nullable', 'integer', 'exists:manual_invoice_items,id'],
            'items.*.product_id'  => ['nullable', 'exists:products,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity'    => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'  => ['required', 'numeric', 'min:0'],
            'items.*.discount'    => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate'    => ['nullable', 'numeric', 'min:0'],
            'items.*.unit'        => ['nullable', 'string', 'max:20'],
            'items.*.unit_code'   => ['nullable', 'string', 'max:10'],
            'items.*.product_key' => ['nullable', 'string', 'max:10'],
        ]);

        $client = Client::findOrFail($data['client_id']);

        DB::beginTransaction();

        try {
            $manualInvoice->client_id = $client->id;
            $manualInvoice->type      = $data['type'];
            $manualInvoice->notes     = $data['notes'] ?? null;

            $manualInvoice->currency       = $data['pay_currency'];
            $manualInvoice->exchange_rate  = $data['exchange_rate'] ?? null;
            $manualInvoice->payment_method = $data['payment_method'];
            $manualInvoice->payment_form   = $data['payment_form'];
            $manualInvoice->cfdi_use       = $data['cfdi_use'];
            $manualInvoice->exportation    = $data['exportation'];

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

                $item->quantity   = $qty;
                $item->unit_price = $price;
                $item->discount   = $discount;
                $item->subtotal   = $lineSubtotal;
                $item->tax        = $lineTax;
                $item->total      = $lineTotal;
                $item->tax_rate   = $taxRate;

                $item->save();
                $existingIds[] = $item->id;
            }

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
     * ✅ Prefactura: genera/actualiza borrador en Facturapi y descarga el PDF BORRADOR.
     *
     * Facturapi permite crear borradores usando status=draft. :contentReference[oaicite:3]{index=3}
     * Y descargar PDF con GET /invoices/{id}/pdf. :contentReference[oaicite:4]{index=4}
     */
    public function downloadDraftPdf(ManualInvoice $manualInvoice)
    {
        if ($manualInvoice->status !== 'draft') {
            return back()->with('error', 'Sólo puedes ver prefactura cuando está en borrador.');
        }

        $manualInvoice->loadMissing(['client', 'items']);
        if ($manualInvoice->items->isEmpty()) {
            return back()->with('error', 'La factura no tiene conceptos.');
        }

        try {
            $draftId = $this->ensureFacturapiDraft($manualInvoice);

            $cfg     = $this->facturapiCfg();
            $baseUri = rtrim($cfg['base_uri'], '/');
            $apiKey  = $cfg['key'];

            $res = Http::withToken($apiKey)
                ->accept('application/pdf')
                ->get($baseUri . '/invoices/' . $draftId . '/pdf');

            if (!$res->successful()) {
                Log::error('Facturapi error descargar prefactura PDF', [
                    'manual_invoice_id' => $manualInvoice->id,
                    'draft_id'          => $draftId,
                    'status'            => $res->status(),
                    'body'              => $res->body(),
                ]);

                return back()->with('error', 'No se pudo descargar la prefactura desde Facturapi.');
            }

            $fileName = 'PREFactura-' . $manualInvoice->id . '.pdf';

            return response($res->body(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $fileName . '"');
        } catch (\Throwable $e) {
            Log::error('Excepción al generar prefactura', [
                'manual_invoice_id' => $manualInvoice->id,
                'msg'               => $e->getMessage(),
            ]);
            return back()->with('error', 'Error al generar prefactura: ' . $e->getMessage());
        }
    }

    /**
     * ✅ Timbrar con Facturapi:
     * - Si ya existe borrador en Facturapi: lo timbra con POST /invoices/{id}/stamp :contentReference[oaicite:5]{index=5}
     * - Si no existe: crea borrador y luego timbra
     */
    public function stamp(ManualInvoice $manualInvoice)
    {
        if ($manualInvoice->status !== 'draft') {
            return redirect()
                ->route('manual_invoices.index')
                ->with('error', 'Sólo puedes timbrar facturas en borrador.');
        }

        $manualInvoice->loadMissing(['client', 'items']);
        $client = $manualInvoice->client;

        if (!$client || !$client->rfc || !$client->nombre) {
            return redirect()->back()->with('error', 'El cliente debe tener nombre y RFC para timbrar.');
        }

        if ($manualInvoice->items->isEmpty()) {
            return redirect()->back()->with('error', 'La factura no tiene conceptos para timbrar.');
        }

        try {
            $draftId = $this->ensureFacturapiDraft($manualInvoice);

            $cfg     = $this->facturapiCfg();
            $baseUri = rtrim($cfg['base_uri'], '/');
            $apiKey  = $cfg['key'];

            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->post($baseUri . '/invoices/' . $draftId . '/stamp');

            if (!$response->successful()) {
                $body   = $response->body();
                $json   = json_decode($body, true);
                $apiMsg = $json['message'] ?? null;

                Log::error('Facturapi error timbrar (desde borrador)', [
                    'manual_invoice_id' => $manualInvoice->id,
                    'draft_id'          => $draftId,
                    'status'            => $response->status(),
                    'body'              => $body,
                ]);

                $msg = 'Facturapi respondió con un error al timbrar.';
                if ($apiMsg) $msg .= ' Detalle: ' . $apiMsg;

                return redirect()->back()->with('error', $msg);
            }

            $data = $response->json();

            // ya timbrado
            $manualInvoice->facturapi_id        = $data['id'] ?? $draftId;
            $manualInvoice->facturapi_uuid      = $data['uuid'] ?? null;
            $manualInvoice->verification_url    = $data['verification_url'] ?? null;
            $manualInvoice->facturapi_status    = $data['status'] ?? null;
            $manualInvoice->cancellation_status = $data['cancellation']['status'] ?? null;
            $manualInvoice->stamped_at          = now();
            $manualInvoice->status              = 'valid';

            // este borrador ya se convirtió en CFDI válido
            $manualInvoice->facturapi_draft_id = null;

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

            return redirect()->back()->with('error', 'Ocurrió un error al timbrar: ' . $e->getMessage());
        }
    }

    /**
     * Descargar PDF desde Facturapi (ya timbrada).
     */
    public function downloadPdf(ManualInvoice $manualInvoice)
    {
        if (!$manualInvoice->facturapi_id) {
            return back()->with('error', 'Esta factura no tiene un ID de Facturapi para descargar PDF.');
        }

        $cfg     = $this->facturapiCfg();
        $baseUri = rtrim($cfg['base_uri'], '/');
        $apiKey  = $cfg['key'];

        $response = Http::withToken($apiKey)
            ->accept('application/pdf')
            ->get($baseUri . '/invoices/' . $manualInvoice->facturapi_id . '/pdf');

        if (!$response->successful()) {
            return back()->with('error', 'No se pudo descargar el PDF desde Facturapi.');
        }

        $fileName = 'CFDI-' . ($manualInvoice->facturapi_uuid ?: $manualInvoice->id) . '.pdf';

        return response($response->body(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    /**
     * Descargar XML desde Facturapi (ya timbrada).
     */
    public function downloadXml(ManualInvoice $manualInvoice)
    {
        if (!$manualInvoice->facturapi_id) {
            return back()->with('error', 'Esta factura no tiene un ID de Facturapi para descargar XML.');
        }

        $cfg     = $this->facturapiCfg();
        $baseUri = rtrim($cfg['base_uri'], '/');
        $apiKey  = $cfg['key'];

        $response = Http::withToken($apiKey)
            ->accept('application/xml')
            ->get($baseUri . '/invoices/' . $manualInvoice->facturapi_id . '/xml');

        if (!$response->successful()) {
            return back()->with('error', 'No se pudo descargar el XML desde Facturapi.');
        }

        $fileName = 'CFDI-' . ($manualInvoice->facturapi_uuid ?: $manualInvoice->id) . '.xml';

        return response($response->body(), 200)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    // ============================================================
    // Helpers Facturapi
    // ============================================================

    private function facturapiCfg(): array
    {
        $cfg = config('services.facturaapi_internal');

        $key  = $cfg['key'] ?? null;
        $base = $cfg['base_uri'] ?? null;

        if (!$key) {
            throw new \RuntimeException('No hay FACTURAAPI_INT_KEY configurada (services.facturaapi_internal.key).');
        }

        if (!$base) {
            $base = 'https://www.facturapi.io/v2';
        }

        return [
            'key'      => $key,
            'base_uri' => rtrim($base, '/'),
        ];
    }

    /**
     * Crea/actualiza el borrador en Facturapi y devuelve su ID.
     * - Crear borrador: POST /invoices con status=draft :contentReference[oaicite:6]{index=6}
     * - Editar borrador: PUT /invoices/{id} (solo draft) :contentReference[oaicite:7]{index=7}
     */
    private function ensureFacturapiDraft(ManualInvoice $manualInvoice): string
    {
        $manualInvoice->loadMissing(['client', 'items']);
        $client = $manualInvoice->client;

        $cfg     = $this->facturapiCfg();
        $baseUri = $cfg['base_uri'];
        $apiKey  = $cfg['key'];

        $itemsPayload = [];
        foreach ($manualInvoice->items as $item) {
            $itemsPayload[] = [
                'quantity' => (float) $item->quantity,
                'discount' => (float) $item->discount,
                'product'  => [
                    'description' => $item->description,
                    'product_key' => $item->product_key ?: '01010101',
                    'price'       => (float) $item->unit_price,
                    'unit_key'    => $item->unit_code ?: 'H87',
                    'unit_name'   => $item->unit ?: 'Pieza',
                ],
            ];
        }

        $uso = $manualInvoice->cfdi_use;

        if (!$uso) {
            $regimen = $client->regimen_fiscal;

            $regimenGastos = ['601','603','612','620','621','622','623','624','626'];

            if (in_array($regimen, $regimenGastos, true)) {
                $uso = 'G03';
            } elseif (in_array($regimen, ['605','606','607','608','610','611','614','615','616'], true)) {
                $uso = 'S01';
            } else {
                $uso = 'G03';
            }
        }

        $payload = [
            'status' => 'draft',
            'type'   => $manualInvoice->type ?: 'I',

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
            'use'            => $uso,
            'payment_form'   => $manualInvoice->payment_form ?? '99',
            'payment_method' => $manualInvoice->payment_method ?? 'PUE',
            'currency'       => $manualInvoice->currency ?: 'MXN',
            'export'         => $manualInvoice->exportation ?? '01',
        ];

        if (($manualInvoice->currency ?: 'MXN') !== 'MXN') {
            $payload['exchange'] = (float) ($manualInvoice->exchange_rate ?: 1);
        } elseif (!empty($manualInvoice->exchange_rate)) {
            $payload['exchange'] = (float) $manualInvoice->exchange_rate;
        }

        if (!empty($manualInvoice->serie)) {
            $payload['series'] = $manualInvoice->serie;
        }
        if (!empty($manualInvoice->folio)) {
            $payload['folio_number'] = $manualInvoice->folio;
        }

        // si ya existe borrador en Facturapi -> actualizar
        if (!empty($manualInvoice->facturapi_draft_id)) {
            $draftId = $manualInvoice->facturapi_draft_id;

            $res = Http::withToken($apiKey)
                ->acceptJson()
                ->put($baseUri . '/invoices/' . $draftId, $payload);

            if (!$res->successful()) {
                Log::error('Facturapi error actualizar borrador', [
                    'manual_invoice_id' => $manualInvoice->id,
                    'draft_id'          => $draftId,
                    'status'            => $res->status(),
                    'body'              => $res->body(),
                ]);

                // si el borrador ya no existe o algo raro, intenta recrearlo
                $manualInvoice->facturapi_draft_id = null;
                $manualInvoice->save();
            } else {
                return $draftId;
            }
        }

        // crear borrador nuevo
        $res = Http::withToken($apiKey)
            ->acceptJson()
            ->post($baseUri . '/invoices', $payload);

        if (!$res->successful()) {
            Log::error('Facturapi error crear borrador', [
                'manual_invoice_id' => $manualInvoice->id,
                'status'            => $res->status(),
                'body'              => $res->body(),
            ]);

            $json = json_decode($res->body(), true);
            $apiMsg = $json['message'] ?? 'Error desconocido';
            throw new \RuntimeException('No se pudo crear borrador en Facturapi: ' . $apiMsg);
        }

        $data = $res->json();
        $draftId = $data['id'] ?? null;

        if (!$draftId) {
            throw new \RuntimeException('Facturapi no devolvió ID al crear borrador.');
        }

        $manualInvoice->facturapi_draft_id = $draftId;
        $manualInvoice->save();

        return $draftId;
    }
}
