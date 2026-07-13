<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class EnviaComClient
{
    private string $shippingBaseUrl;
    private string $queriesBaseUrl;
    private string $token;
    private bool $debug;

    public function __construct()
    {
        $mode = (string) config('services.envia.mode', env('ENVIA_MODE', 'sandbox'));

        $this->shippingBaseUrl = rtrim(
            (string) config(
                'services.envia.base_url',
                $mode === 'production'
                    ? 'https://api.envia.com'
                    : 'https://api-test.envia.com'
            ),
            '/'
        );

        $this->queriesBaseUrl = rtrim(
            (string) config(
                'services.envia.queries_url',
                $mode === 'production'
                    ? 'https://queries.envia.com'
                    : 'https://queries-test.envia.com'
            ),
            '/'
        );

        $this->token = (string) config('services.envia.token', env('ENVIA_API_TOKEN', ''));
        $this->debug = (bool) config('services.envia.debug', env('ENVIA_DEBUG', false));
    }

    /**
     * Cotización.
     */
    public function quote(array $origin, array $destination, array $packages, array $shipment = [], array $settings = []): array
    {
        $payload = [
            'origin' => $this->cleanAddress($origin),
            'destination' => $this->cleanAddress($destination),
            'packages' => $this->cleanPackages($packages),
            'shipment' => array_merge([
                'type' => 1,
            ], $shipment),
            'settings' => array_merge([
                'printFormat' => 'PDF',
                'printSize' => 'PAPER_4X6',
                'currency' => 'MXN',
            ], $settings),
        ];

        return $this->postShipping('/ship/rate/', $payload, 'quote');
    }

    /**
     * Generar envío / guía / etiqueta.
     *
     * IMPORTANTE:
     * Cotizar NO crea el envío en el panel de Envia.
     * Esta función es la que debe ejecutarse después del pago.
     */
    public function generate(array $origin, array $destination, array $packages, array $shipment, array $settings = []): array
    {
        $payload = [
            'origin' => $this->cleanAddress($origin),
            'destination' => $this->cleanAddress($destination),
            'packages' => $this->cleanPackages($packages),
            'shipment' => array_merge([
                'type' => 1,
            ], $shipment),
            'settings' => array_merge([
                'printFormat' => 'PDF',
                'printSize' => 'PAPER_4X6',
                'currency' => 'MXN',
            ], $settings),
        ];

        /*
         * Normalización extra:
         * Envia suele esperar carrier y service dentro de shipment.
         * Si por accidente llegan en nivel raíz del shipment original, se conservan.
         */
        if (empty($payload['shipment']['carrier']) && !empty($shipment['carrier'])) {
            $payload['shipment']['carrier'] = $shipment['carrier'];
        }

        if (empty($payload['shipment']['service']) && !empty($shipment['service'])) {
            $payload['shipment']['service'] = $shipment['service'];
        }

        /*
         * Servicio normalizado para generate.
         * Envia NO acepta descripciones como "Paquetexpress Ocurre - domicilio".
         * Debe recibir códigos como "ground_od".
         */
        if (!empty($payload['shipment']['carrier']) && !empty($payload['shipment']['service'])) {
            $payload['shipment']['service'] = $this->normalizeServiceForGenerate(
                (string) $payload['shipment']['carrier'],
                (string) $payload['shipment']['service']
            );
        }

        /*
         * Si se trata de un servicio de sucursal a domicilio, Envia puede exigir
         * originBranchCode. Si viene por ENVIA_ORIGIN_BRANCH_CODE o en el shipment,
         * lo mandamos en varias claves compatibles.
         */
        $branchCode = $payload['shipment']['originBranchCode']
            ?? $payload['shipment']['origin_branch_code']
            ?? $payload['shipment']['branchCode']
            ?? env('ENVIA_ORIGIN_BRANCH_CODE');

        if ($branchCode) {
            $payload['shipment']['originBranchCode'] = (string) $branchCode;
            $payload['shipment']['origin_branch_code'] = (string) $branchCode;
            $payload['shipment']['branchCode'] = (string) $branchCode;
        }

        /*
         * Primer intento: endpoint oficial usado por el flujo actual.
         */
        try {
            return $this->postShipping('/ship/generate/', $payload, 'generate');
        } catch (\Throwable $e) {
            Log::warning('Envia generate falló en /ship/generate/. Reintentando sin slash final.', [
                'error' => $e->getMessage(),
                'carrier' => $payload['shipment']['carrier'] ?? null,
                'service' => $payload['shipment']['service'] ?? null,
                'destination_cp' => $payload['destination']['postalCode'] ?? null,
            ]);

            /*
             * Algunos entornos/proxies son sensibles al slash final.
             */
            return $this->postShipping('/ship/generate', $payload, 'generate_retry_no_slash');
        }
    }

    /**
     * Tracking.
     */
    public function track(string $trackingNumber, ?string $carrier = null): array
    {
        return $this->postShipping('/ship/track/', array_filter([
            'trackingNumber' => $trackingNumber,
            'carrier' => $carrier,
        ]), 'track');
    }

    public function carriers(): array
    {
        return $this->getShipping('/ship/carriers/', 'carriers');
    }

    public function normalizeCarriers(array $payload): array
    {
        $data = $payload['data'] ?? $payload['carriers'] ?? $payload;

        if (!is_array($data)) {
            return [];
        }

        return collect($data)->map(function ($carrier) {
            if (is_string($carrier)) {
                return [
                    'id' => Str::slug($carrier),
                    'name' => $carrier,
                    'services' => [],
                    'raw' => $carrier,
                ];
            }

            return [
                'id' => $carrier['id'] ?? Str::slug((string) ($carrier['name'] ?? $carrier['carrier'] ?? 'carrier')),
                'name' => $carrier['name'] ?? $carrier['carrier'] ?? $carrier['description'] ?? 'Paquetería',
                'services' => $carrier['services'] ?? [],
                'raw' => $carrier,
            ];
        })->values()->toArray();
    }

    public function normalizeRates(array $payload): array
    {
        $data = $payload['data'] ?? $payload['rates'] ?? [];

        if (!is_array($data)) {
            return [];
        }

        return collect($data)->map(function ($rate) {
            return [
                'id' => $rate['id']
                    ?? $rate['rateId']
                    ?? $rate['rate_id']
                    ?? $rate['serviceId']
                    ?? null,

                'carrier' => $rate['carrier']
                    ?? $rate['carrierName']
                    ?? $rate['carrier_name']
                    ?? null,

                'service' => $rate['service']
                    ?? $rate['serviceCode']
                    ?? $rate['service_code']
                    ?? null,

                'service_description' => $rate['serviceDescription']
                    ?? $rate['service_description']
                    ?? $rate['serviceName']
                    ?? $rate['service_name']
                    ?? null,

                'drop_off' => (int) ($rate['dropOff'] ?? $rate['drop_off'] ?? 0),
                'branch_type' => $rate['branchType'] ?? $rate['branch_type'] ?? null,
                'branches' => $rate['branches'] ?? [],

                'delivery_estimate' => $rate['deliveryEstimate']
                    ?? $rate['delivery_estimate']
                    ?? $rate['delivery']
                    ?? null,

                'total_price' => (float) (
                    $rate['totalPrice']
                    ?? $rate['total_price']
                    ?? $rate['price']
                    ?? 0
                ),

                'currency' => $rate['currency'] ?? 'MXN',
                'raw' => $rate,
            ];
        })
        ->filter(fn ($rate) => filled($rate['carrier']) && filled($rate['service']))
        ->values()
        ->toArray();
    }

    public function normalizeGeneratedShipment(array $payload): array
    {
        $data = $payload['data'] ?? $payload['shipment'] ?? $payload;

        if (isset($data[0]) && is_array($data[0])) {
            $data = $data[0];
        }

        /*
         * Envia puede regresar label como URL, labelUrl, label_url, url,
         * o dentro de archivos/documentos según el carrier.
         */
        $labelUrl = $data['label']
            ?? $data['labelUrl']
            ?? $data['label_url']
            ?? $data['url']
            ?? data_get($data, 'files.label')
            ?? data_get($data, 'documents.label')
            ?? data_get($data, 'label.url')
            ?? null;

        $trackingNumber = $data['trackingNumber']
            ?? $data['tracking_number']
            ?? $data['trackNumber']
            ?? $data['guideNumber']
            ?? $data['shipmentNumber']
            ?? $data['number']
            ?? null;

        $trackingUrl = $data['trackingUrl']
            ?? $data['tracking_url']
            ?? $data['trackUrl']
            ?? data_get($data, 'tracking.url')
            ?? null;

        return [
            'shipment_id' => $data['id']
                ?? $data['shipmentId']
                ?? $data['shipment_id']
                ?? null,

            'carrier' => $data['carrier'] ?? null,
            'service' => $data['service'] ?? null,
            'tracking_number' => $trackingNumber,
            'tracking_url' => $trackingUrl,
            'label_url' => $labelUrl,
            'status' => $data['status'] ?? 'created',
            'raw' => $payload,
        ];
    }

    public function normalizeTrackingStatus(array $payload): array
    {
        $data = $payload['data'] ?? $payload;

        if (isset($data[0]) && is_array($data[0])) {
            $data = $data[0];
        }

        $lastEvent = $data['lastEvent'] ?? $data['last_event'] ?? $data['event'] ?? null;
        $status = $data['status'] ?? data_get($lastEvent, 'status') ?? data_get($lastEvent, 'description') ?? 'created';

        return [
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'tracking_url' => $data['trackingUrl'] ?? $data['tracking_url'] ?? null,
            'last_event' => $lastEvent,
            'raw' => $payload,
        ];
    }

    private function getShipping(string $path, string $context = 'get'): array
    {
        if (!$this->token) {
            throw new RuntimeException('ENVIA_API_TOKEN no está configurado.');
        }

        $url = $this->shippingBaseUrl . $path;

        if ($this->debug) {
            Log::info('Envia GET request', [
                'context' => $context,
                'url' => $url,
                'base_url' => $this->shippingBaseUrl,
            ]);
        }

        $response = Http::withToken($this->token)
            ->acceptJson()
            ->timeout(90)
            ->get($url);

        return $this->handleResponse($response, $context, $url);
    }

    private function postShipping(string $path, array $payload, string $context = 'post'): array
    {
        if (!$this->token) {
            throw new RuntimeException('ENVIA_API_TOKEN no está configurado.');
        }

        $url = $this->shippingBaseUrl . $path;

        if ($this->debug) {
            Log::info('Envia POST request', [
                'context' => $context,
                'url' => $url,
                'base_url' => $this->shippingBaseUrl,
                'payload' => $this->safePayloadForLog($payload),
            ]);
        }

        $response = Http::withToken($this->token)
            ->acceptJson()
            ->asJson()
            ->timeout(90)
            ->post($url, $payload);

        return $this->handleResponse($response, $context, $url, $payload);
    }

    private function handleResponse(Response $response, string $context, string $url, ?array $payload = null): array
    {
        $json = $response->json();

        if ($this->debug) {
            Log::info('Envia response', [
                'context' => $context,
                'url' => $url,
                'status' => $response->status(),
                'json' => $json,
                'body' => Str::limit($response->body(), 3000),
            ]);
        }

        if (!$response->successful()) {
            Log::warning('Envia.com HTTP error', [
                'context' => $context,
                'url' => $url,
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 3000),
                'payload' => $payload ? $this->safePayloadForLog($payload) : null,
            ]);

            throw new RuntimeException(
                'Envia.com error ' . $response->status() . ': ' . Str::limit($response->body(), 1500)
            );
        }

        if (!is_array($json)) {
            Log::warning('Envia.com respuesta no JSON', [
                'context' => $context,
                'url' => $url,
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 3000),
            ]);

            return [];
        }

        /*
         * Algunas respuestas llegan con meta de error aunque HTTP sea 200.
         */
        $hasError = false;
        $message = null;

        if (isset($json['error']) && $json['error']) {
            $hasError = true;
            $message = is_string($json['error']) ? $json['error'] : json_encode($json['error'], JSON_UNESCAPED_UNICODE);
        }

        if (isset($json['code']) && (int) $json['code'] >= 400) {
            $hasError = true;
            $message = (string) ($json['message'] ?? $json['description'] ?? json_encode($json, JSON_UNESCAPED_UNICODE));
        }

        if (isset($json['errors']) && !empty($json['errors'])) {
            $hasError = true;
            $message = json_encode($json['errors'], JSON_UNESCAPED_UNICODE);
        }

        if (isset($json['message']) && is_string($json['message']) && str_contains(Str::lower($json['message']), 'error')) {
            $hasError = true;
            $message = $json['message'];
        }

        if ($hasError) {
            Log::warning('Envia.com error lógico', [
                'context' => $context,
                'url' => $url,
                'message' => $message,
                'json' => $json,
                'payload' => $payload ? $this->safePayloadForLog($payload) : null,
            ]);

            throw new RuntimeException('Envia.com error lógico: ' . Str::limit((string) $message, 1500));
        }

        return $json;
    }

    private function cleanAddress(array $address): array
    {
        /*
         * Envia normalmente usa postalCode, no postal_code.
         * Dejamos ambos campos útiles normalizados por seguridad.
         */
        if (!isset($address['postalCode']) && isset($address['postal_code'])) {
            $address['postalCode'] = $address['postal_code'];
        }

        if (!isset($address['postalCode']) && isset($address['zip'])) {
            $address['postalCode'] = $address['zip'];
        }

        if (!isset($address['country'])) {
            $address['country'] = 'MX';
        }

        /*
         * Evita nulls en campos de dirección.
         */
        foreach ($address as $key => $value) {
            if ($value === null) {
                $address[$key] = '';
            }
        }

        return $address;
    }

    private function cleanPackages(array $packages): array
    {
        return collect($packages)->map(function ($package) {
            $package = (array) $package;

            if (!isset($package['amount'])) {
                $package['amount'] = 1;
            }

            if (!isset($package['type'])) {
                $package['type'] = 'box';
            }

            if (!isset($package['weight'])) {
                $package['weight'] = 1;
            }

            if (!isset($package['weightUnit'])) {
                $package['weightUnit'] = 'KG';
            }

            if (!isset($package['lengthUnit'])) {
                $package['lengthUnit'] = 'CM';
            }

            if (!isset($package['dimensions']) || !is_array($package['dimensions'])) {
                $package['dimensions'] = [
                    'length' => 30,
                    'width' => 25,
                    'height' => 20,
                ];
            }

            if (!isset($package['content'])) {
                $package['content'] = 'Productos';
            }

            return $package;
        })->values()->toArray();
    }

    private function safePayloadForLog(array $payload): array
    {
        $safe = $payload;

        /*
         * No hay token en payload, pero dejamos este método por si agregas campos sensibles después.
         */
        foreach (['token', 'password', 'secret'] as $key) {
            if (isset($safe[$key])) {
                $safe[$key] = '***';
            }
        }

        return $safe;
    }


    private function normalizeServiceForGenerate(string $carrier, string $service): string
    {
        $carrierKey = Str::lower(trim($carrier));
        $serviceText = trim($service);

        if ($serviceText === '') {
            return '';
        }

        if (preg_match('/^[a-z0-9_\-]+$/i', $serviceText) && !str_contains($serviceText, ' ')) {
            return Str::lower($serviceText);
        }

        $s = Str::lower($serviceText);

        return match ($carrierKey) {
            'ups' => str_contains($s, 'saver') ? 'saver' : Str::lower($serviceText),

            'paquetexpress' => match (true) {
                str_contains($s, 'ocurre - domicilio') => 'ground_od',
                str_contains($s, 'domicilio - ocurre') => 'ground_do',
                str_contains($s, 'ocurre') && str_contains($s, 'domicilio') => 'ground_od',
                str_contains($s, 'terrestre') || str_contains($s, 'ground') => 'ground',
                default => 'ground',
            },

            'estafeta' => match (true) {
                str_contains($s, 'siguiente') || str_contains($s, 'express') => 'express',
                str_contains($s, 'metropolitano') || str_contains($s, 'local') => 'local',
                str_contains($s, 'terrestre') || str_contains($s, 'ground') => 'ground',
                default => 'ground',
            },

            'fedex' => str_contains($s, 'ground') || str_contains($s, 'econ') ? 'ground' : Str::lower($serviceText),
            'dhl' => (str_contains($s, 'economy') || str_contains($s, 'ground')) ? 'ground_od' : Str::lower($serviceText),
            'scm' => str_contains($s, 'ground') ? 'ground' : Str::lower($serviceText),

            default => Str::lower(str_replace(' ', '_', $serviceText)),
        };
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
