<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\ShippingAddress;
use App\Services\EnviaComClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class ShippingController extends Controller
{
    protected float $threshold;
    protected EnviaComClient $envia;

    public function __construct(EnviaComClient $envia)
    {
        $this->envia = $envia;
        $this->threshold = (float) env('FREE_SHIPPING_THRESHOLD', 5000);
    }

    /**
     * Cotiza opciones de envío usando Envia.com.
     *
     * Modo normal:
     * - Hace una cotización general.
     *
     * Modo forzado:
     * - Si ENVIA_QUOTE_ALL_CARRIERS=true, intenta cotizar carrier por carrier
     *   para sacar más opciones cuando Envia no devuelve todo en una sola llamada.
     */
    public function options(Request $req)
    {
        $subtotal = (float) $req->input('subtotal', $this->cartSubtotal());
        $hasFree = $subtotal >= $this->threshold;

        $to = $this->resolveToAddress($req);
        $zipTo = isset($to['postal_code']) ? preg_replace('/\D/', '', (string) $to['postal_code']) : null;

        if (!$zipTo || strlen($zipTo) !== 5) {
            return response()->json([
                'ok' => false,
                'error' => 'Falta código postal destino de 5 dígitos.',
            ], 422);
        }

        $origin = $this->originAddress();
        $destination = $this->destinationAddress($to);
        $package = $this->resolvePackage($req);

        try {
            $debug = [
                'strategy' => config('services.envia.quote_all_carriers') ? 'loop_carriers' : 'single_rate',
                'attempted_carriers' => [],
                'failed_carriers' => [],
            ];

            if (config('services.envia.quote_all_carriers')) {
                $rates = $this->quoteAllCarriers($origin, $destination, [$package], $req, $debug);
            } else {
                $payload = $this->envia->quote($origin, $destination, [$package], $this->shipmentPayload($req));
                $rates = $this->envia->normalizeRates($payload);
                $debug['single_raw'] = config('services.envia.debug') ? $payload : null;
            }

            /*
             * Solo filtra si explícitamente mandas carriers desde el frontend.
             * Si quieres todas las tarifas, NO envíes carriers.
             */
            $carriersIn = array_values(array_filter(array_map(
                fn ($name) => Str::slug((string) $name),
                (array) $req->input('carriers', [])
            )));

            if (!empty($carriersIn)) {
                $rates = array_values(array_filter($rates, function ($rate) use ($carriersIn) {
                    return in_array(Str::slug((string) ($rate['carrier'] ?? '')), $carriersIn, true);
                }));
            }

            $options = collect($rates)
                ->unique(fn ($r) => Str::slug((string)($r['carrier'] ?? '')) . '|' . Str::slug((string)($r['service'] ?? '')) . '|' . number_format((float)($r['total_price'] ?? 0), 2, '.', ''))
                ->map(fn ($rate) => $this->rateToOption($rate))
                ->sortBy('price')
                ->values()
                ->toArray();

            if ($hasFree) {
                array_unshift($options, [
                    'id' => 'FREE',
                    'provider' => 'jureto',
                    'carrier' => 'Jureto',
                    'carrier_key' => 'jureto',
                    'name' => 'Jureto',
                    'service' => 'Envío gratis',
                    'price' => 0.0,
                    'currency' => 'MXN',
                    'eta' => null,
                    'logo_url' => $this->carrierLogoUrl('Jureto'),
                    'raw' => null,
                ]);
            }

            return response()->json([
                'ok' => true,
                'provider' => 'envia.com',
                'mode' => config('services.envia.mode', 'sandbox'),
                'free_shipping' => $hasFree,
                'free_threshold' => $this->threshold,
                'count' => count($options),
                'options' => $options,
                'debug' => config('services.envia.debug') ? $debug : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Envia.com quotation exception', [
                'msg' => $e->getMessage(),
                'zipTo' => $zipTo,
                'package' => $package,
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Error consultando Envia.com.',
                'detail' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Intenta cotizar carrier por carrier.
     * Esto ayuda cuando /ship/rate/ sin carrier solo devuelve pocas opciones.
     */
    private function quoteAllCarriers(array $origin, array $destination, array $packages, Request $req, array &$debug): array
    {
        $carriers = $this->carriersToQuote($req);
        $allRates = [];

        foreach ($carriers as $carrier) {
            $debug['attempted_carriers'][] = $carrier;

            try {
                $payload = $this->envia->quote($origin, $destination, $packages, [
                    'type' => 1,
                    'carrier' => $carrier,
                ]);

                $rates = $this->envia->normalizeRates($payload);

                foreach ($rates as $rate) {
                    if (empty($rate['carrier'])) {
                        $rate['carrier'] = $carrier;
                    }

                    $allRates[] = $rate;
                }
            } catch (\Throwable $e) {
                $debug['failed_carriers'][$carrier] = $e->getMessage();

                Log::info('Envia.com carrier without rate', [
                    'carrier' => $carrier,
                    'message' => $e->getMessage(),
                ]);

                continue;
            }
        }

        /*
         * Si el loop no encontró nada, hacemos fallback a cotización general.
         */
        if (empty($allRates)) {
            $payload = $this->envia->quote($origin, $destination, $packages, $this->shipmentPayload($req));
            $debug['fallback_raw'] = config('services.envia.debug') ? $payload : null;
            return $this->envia->normalizeRates($payload);
        }

        return $allRates;
    }

    private function carriersToQuote(Request $req): array
    {
        $requested = array_values(array_filter(array_map(
            fn ($name) => trim((string) $name),
            (array) $req->input('carriers', [])
        )));

        if (!empty($requested)) {
            return $requested;
        }

        $configured = array_values(array_filter(array_map(
            fn ($name) => trim((string) $name),
            explode(',', (string) env('ENVIA_FORCE_CARRIERS', ''))
        )));

        if (!empty($configured)) {
            return $configured;
        }

        try {
            $payload = $this->envia->carriers();
            $apiCarriers = collect($this->envia->normalizeCarriers($payload))
                ->pluck('name')
                ->filter()
                ->map(fn ($name) => trim((string) $name))
                ->unique()
                ->values()
                ->toArray();

            if (!empty($apiCarriers)) {
                return $apiCarriers;
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo consultar lista de carriers Envia.com', [
                'msg' => $e->getMessage(),
            ]);
        }

        /*
         * Fallback amplio. Si alguno no está activo o no da cobertura,
         * Envia responderá error y simplemente se ignora.
         */
        return [
            'dhl',
            'fedex',
            'estafeta',
            'ups',
            'redpack',
            'paquetexpress',
            'sendex',
            'carssa',
            'ivoy',
            '99minutos',
            'jtexpress',
            'ampm',
            'scm',
            'quiken',
        ];
    }

    public function carriers(Request $req)
    {
        try {
            $payload = $this->envia->carriers();

            return response()->json([
                'ok' => true,
                'items' => $this->envia->normalizeCarriers($payload),
                'raw' => config('services.envia.debug') ? $payload : null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'No se pudieron consultar las paqueterías de la cuenta.',
                'detail' => config('app.debug') ? $e->getMessage() : null,
            ], 422);
        }
    }

    public function select(Request $req)
    {
        $id = (string) $req->input('option_id', $req->input('code', ''));
        $price = (float) $req->input('price', 0);
        $currency = (string) $req->input('currency', 'MXN');

        if (!$id) {
            return response()->json([
                'ok' => false,
                'error' => 'Falta option_id.',
            ], 422);
        }

        $carrier = (string) $req->input('carrier', $req->input('name', ''));
        $carrierKey = (string) $req->input('carrier_key', $this->carrierKey($carrier));

        $shipping = [
            'provider' => (string) $req->input('provider', 'envia.com'),
            'selected_id' => $id,
            'carrier' => $carrier,
            'carrier_key' => $carrierKey,
            'service' => (string) $req->input('service', ''),
            'price' => $price,
            'currency' => $currency,
            'label' => trim((string) $req->input('option_label', $carrier)),
            'logo_url' => (string) $req->input('logo_url', $this->carrierLogoUrl($carrier)),
            'raw' => $req->input('raw'),
        ];

        Session::put('shipping', $shipping);

        return response()->json([
            'ok' => true,
            'shipping' => $shipping,
        ]);
    }

    public function generateGuide(Request $req)
    {
        $data = $req->validate([
            'order_id' => ['nullable'],
            'address_id' => ['nullable'],
            'to' => ['nullable', 'array'],
            'package' => ['nullable', 'array'],
            'carrier' => ['nullable', 'string'],
            'service' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
        ]);

        $selected = Session::get('shipping', []);
        $carrier = (string) ($data['carrier'] ?? $selected['carrier'] ?? '');
        $service = (string) ($data['service'] ?? $selected['service'] ?? '');

        if (!$carrier || !$service || ($selected['selected_id'] ?? null) === 'FREE') {
            return response()->json([
                'ok' => false,
                'error' => 'No hay una paquetería válida seleccionada para generar guía.',
            ], 422);
        }

        $to = $this->resolveToAddress($req);
        $package = $this->resolvePackage($req);

        try {
            $payload = $this->envia->generate(
                $this->originAddress(),
                $this->destinationAddress($to),
                [$package],
                [
                    'type' => 1,
                    'carrier' => $carrier,
                    'service' => $service,
                ]
            );

            $normalized = $this->envia->normalizeGeneratedShipment($payload);

            $shipment = Shipment::create([
                'user_id' => Auth::id(),
                'order_id' => $data['order_id'] ?? null,
                'provider' => 'envia.com',
                'mode' => config('services.envia.mode', 'sandbox'),
                'carrier' => $normalized['carrier'] ?: $carrier,
                'carrier_key' => $this->carrierKey($normalized['carrier'] ?: $carrier),
                'service' => $normalized['service'] ?: $service,
                'tracking_number' => $normalized['tracking_number'],
                'tracking_url' => $normalized['tracking_url'],
                'label_url' => $normalized['label_url'],
                'status' => $normalized['status'] ?: 'created',
                'status_label' => $this->statusLabel($normalized['status'] ?: 'created'),
                'price' => (float) ($selected['price'] ?? 0),
                'currency' => (string) ($selected['currency'] ?? 'MXN'),
                'destination' => $this->destinationAddress($to),
                'raw_response' => $payload,
            ]);

            $this->saveShipmentIntoOrderIfPossible($shipment);
            $this->emailGuideToCustomer($shipment, $data['email'] ?? null);

            return response()->json([
                'ok' => true,
                'shipment' => $shipment,
            ]);
        } catch (\Throwable $e) {
            Log::error('Envia.com generate guide exception', [
                'msg' => $e->getMessage(),
                'carrier' => $carrier,
                'service' => $service,
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'No se pudo generar la guía con Envia.com.',
                'detail' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function refreshStatus(Request $req, Shipment $shipment)
    {
        $this->authorizeShipment($shipment);

        if (!$shipment->tracking_number) {
            return response()->json([
                'ok' => false,
                'error' => 'Este envío no tiene número de rastreo.',
            ], 422);
        }

        try {
            $payload = $this->envia->track($shipment->tracking_number, $shipment->carrier);
            $status = $this->envia->normalizeTrackingStatus($payload);

            $shipment->update([
                'status' => $status['status'] ?? $shipment->status,
                'status_label' => $status['status_label'] ?? $shipment->status_label,
                'tracking_url' => $status['tracking_url'] ?? $shipment->tracking_url,
                'last_tracking_event' => $status['last_event'] ?? null,
                'last_tracked_at' => now(),
                'tracking_raw' => $payload,
            ]);

            return response()->json([
                'ok' => true,
                'shipment' => $shipment->fresh(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => 'No se pudo actualizar el estatus.',
                'detail' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function myShipments()
    {
        $shipments = Shipment::query()
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(20);

        return view('customer.shipments.index', compact('shipments'));
    }

    public function showShipment(Shipment $shipment)
    {
        $this->authorizeShipment($shipment);

        return view('customer.shipments.show', compact('shipment'));
    }

    private function rateToOption(array $rate): array
    {
        $carrier = (string) ($rate['carrier'] ?? 'Paquetería');
        $service = (string) ($rate['service_description'] ?? $rate['service'] ?? 'Servicio');
        $carrierKey = $this->carrierKey($carrier);

        return [
            'id' => (string) ($rate['id'] ?? md5(json_encode($rate))),
            'provider' => 'envia.com',
            'carrier' => $carrier,
            'carrier_key' => $carrierKey,
            'name' => $carrier,
            'service' => $service,
            'price' => (float) ($rate['total_price'] ?? 0),
            'currency' => (string) ($rate['currency'] ?? 'MXN'),
            'eta' => $rate['delivery_estimate'] ?? null,
            'logo_url' => $this->carrierLogoUrl($carrier),
            'raw' => $rate['raw'] ?? $rate,
        ];
    }

    private function resolveToAddress(Request $req): array
    {
        if ($id = $req->input('address_id')) {
            $addr = ShippingAddress::query()
                ->where('user_id', Auth::id())
                ->whereKey($id)
                ->firstOrFail();

            return [
                'postal_code' => (string) $addr->postal_code,
                'state' => (string) $addr->state,
                'municipality' => (string) $addr->municipality,
                'colony' => (string) $addr->colony,
                'street' => trim($addr->street . ' ' . ($addr->ext_number ? "Ext. {$addr->ext_number}" : '') . ($addr->int_number ? " Int. {$addr->int_number}" : '')),
                'contact_name' => (string) ($addr->contact_name ?: Auth::user()?->name ?: 'Cliente'),
                'phone' => (string) ($addr->phone ?: '5555555555'),
                'email' => (string) (Auth::user()?->email ?? ''),
            ];
        }

        return (array) $req->input('to', []);
    }

    private function originAddress(): array
    {
        return [
            'name' => config('services.envia.origin.name'),
            'company' => config('services.envia.origin.company'),
            'email' => config('services.envia.origin.email'),
            'phone' => config('services.envia.origin.phone'),
            'street' => config('services.envia.origin.street'),
            'number' => config('services.envia.origin.number'),
            'district' => config('services.envia.origin.district'),
            'city' => config('services.envia.origin.city'),
            'state' => config('services.envia.origin.state'),
            'country' => config('services.envia.origin.country', 'MX'),
            'postalCode' => config('services.envia.origin.postal_code'),
            'reference' => config('services.envia.origin.reference'),
        ];
    }

    private function destinationAddress(array $to): array
    {
        return [
            'name' => (string) ($to['contact_name'] ?? $to['name'] ?? Auth::user()?->name ?? 'Cliente'),
            'company' => (string) ($to['company'] ?? ''),
            'email' => (string) ($to['email'] ?? Auth::user()?->email ?? ''),
            'phone' => (string) ($to['phone'] ?? '5555555555'),
            'street' => (string) ($to['street'] ?? 'Domicilio de entrega'),
            'number' => (string) ($to['number'] ?? 'S/N'),
            'district' => (string) ($to['colony'] ?? $to['district'] ?? ''),
            'city' => (string) ($to['municipality'] ?? $to['city'] ?? ''),
            'state' => (string) ($to['state'] ?? ''),
            'country' => (string) ($to['country'] ?? 'MX'),
            'postalCode' => (string) ($to['postal_code'] ?? $to['postalCode'] ?? ''),
            'reference' => (string) ($to['reference'] ?? ''),
        ];
    }

    private function resolvePackage(Request $req): array
    {
        $p = (array) $req->input('package', []);

        return [
            'content' => 'Productos Jureto',
            'amount' => 1,
            'type' => 'box',
            'weight' => (float) max(0.01, (float) ($p['weight_kg'] ?? $p['weight'] ?? config('services.envia.default_package.weight', 1))),
            'declaredValue' => (float) max(0, (float) ($p['declared_value'] ?? $p['declaredValue'] ?? $this->cartSubtotal())),
            'weightUnit' => 'KG',
            'lengthUnit' => 'CM',
            'dimensions' => [
                'length' => (float) max(1, (float) ($p['length_cm'] ?? $p['length'] ?? config('services.envia.default_package.length', 30))),
                'width' => (float) max(1, (float) ($p['width_cm'] ?? $p['width'] ?? config('services.envia.default_package.width', 25))),
                'height' => (float) max(1, (float) ($p['height_cm'] ?? $p['height'] ?? config('services.envia.default_package.height', 20))),
            ],
        ];
    }

    private function shipmentPayload(Request $req): array
    {
        $carrier = trim((string) $req->input('carrier', ''));
        $service = trim((string) $req->input('service', ''));

        return array_filter([
            'type' => 1,
            'carrier' => $carrier ?: null,
            'service' => $service ?: null,
        ]);
    }

    private function cartSubtotal(): float
    {
        return collect((array) session('cart', []))
            ->sum(fn ($item) => ((float) ($item['price'] ?? 0)) * ((int) ($item['qty'] ?? 1)));
    }

    private function carrierKey(string $carrier): string
    {
        $key = Str::slug(Str::ascii($carrier));

        return match ($key) {
            'federal-express' => 'fedex',
            'mexico-redpack', 'redpack-mexico' => 'redpack',
            'paquete-express' => 'paquetexpress',
            '99-minutos', 'noventa-y-nueve-minutos', '99minutos' => '99minutos',
            'j-t-express', 'jtexpress' => 'jtexpress',
            default => $key ?: 'generic',
        };
    }

    private function carrierLogoUrl(string $carrier): string
    {
        $key = $this->carrierKey($carrier);

        foreach (['svg', 'png', 'webp', 'jpg'] as $ext) {
            $relative = "images/carriers/{$key}.{$ext}";
            if (file_exists(public_path($relative))) {
                return asset($relative);
            }
        }

        $domains = [
            'dhl' => 'dhl.com',
            'fedex' => 'fedex.com',
            'estafeta' => 'estafeta.com',
            'ups' => 'ups.com',
            'redpack' => 'redpack.com.mx',
            'paquetexpress' => 'paquetexpress.com.mx',
            'sendex' => 'sendex.mx',
            'ivoy' => 'ivoy.mx',
            'carssa' => 'carssa.com.mx',
            '99minutos' => '99minutos.com',
            'jtexpress' => 'jtexpress.mx',
            'ampm' => 'ampm.com.mx',
            'scm' => 'scm.com.mx',
            'quiken' => 'quiken.mx',
            'jureto' => request()->getSchemeAndHttpHost(),
        ];

        return isset($domains[$key])
            ? 'https://logo.clearbit.com/' . $domains[$key]
            : asset('images/carriers/generic-shipping.svg');
    }

    private function emailGuideToCustomer(Shipment $shipment, ?string $email = null): void
    {
        $to = $email ?: Auth::user()?->email ?: data_get($shipment->destination, 'email');

        if (!$to) {
            return;
        }

        try {
            Mail::send('emails.shipment-guide', ['shipment' => $shipment], function ($message) use ($to, $shipment) {
                $message->to($to)
                    ->subject('Tu guía de envío JURETO ' . ($shipment->tracking_number ? '#' . $shipment->tracking_number : ''));
            });
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar correo de guía', [
                'shipment_id' => $shipment->id,
                'email' => $to,
                'msg' => $e->getMessage(),
            ]);
        }
    }

    private function saveShipmentIntoOrderIfPossible(Shipment $shipment): void
    {
        if (!$shipment->order_id || !Schema::hasTable('orders')) {
            return;
        }

        try {
            $updates = [];

            foreach ([
                'shipping_provider' => 'envia.com',
                'shipping_carrier' => $shipment->carrier,
                'shipping_service' => $shipment->service,
                'tracking_number' => $shipment->tracking_number,
                'tracking_url' => $shipment->tracking_url,
                'label_url' => $shipment->label_url,
                'shipping_status' => $shipment->status,
            ] as $column => $value) {
                if (Schema::hasColumn('orders', $column)) {
                    $updates[$column] = $value;
                }
            }

            if (!empty($updates)) {
                \DB::table('orders')->where('id', $shipment->order_id)->update($updates);
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo guardar shipment en orders', [
                'shipment_id' => $shipment->id,
                'order_id' => $shipment->order_id,
                'msg' => $e->getMessage(),
            ]);
        }
    }

    private function authorizeShipment(Shipment $shipment): void
    {
        abort_unless(Auth::check() && (int) $shipment->user_id === (int) Auth::id(), 403);
    }

    private function statusLabel(?string $status): string
    {
        $key = Str::lower((string) $status);

        return match (true) {
            str_contains($key, 'deliver') || str_contains($key, 'entreg') => 'Entregado',
            str_contains($key, 'transit') || str_contains($key, 'camino') => 'En camino',
            str_contains($key, 'cancel') => 'Cancelado',
            str_contains($key, 'exception') || str_contains($key, 'incid') => 'Incidencia',
            default => 'Guía generada',
        };
    }
}
