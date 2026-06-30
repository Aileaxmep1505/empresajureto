<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnviaComService
{
    public function quote($address, array $cart, float $subtotal = 0): array
    {
        if (!filter_var(env('ENVIA_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
            return [];
        }

        $token = trim((string) env('ENVIA_API_TOKEN', ''));
        if ($token === '') {
            Log::warning('Envia.com sin token configurado');
            return [];
        }

        if (!$address) {
            Log::warning('Envia.com sin direccion destino');
            return [];
        }

        $zipTo = preg_replace('/\D+/', '', (string) ($address->postal_code ?? ''));
        if (strlen($zipTo) !== 5) {
            Log::warning('Envia.com sin CP destino valido', [
                'postal_code' => $address->postal_code ?? null,
            ]);
            return [];
        }

        $base = rtrim((string) env('ENVIA_API_BASE', 'https://api.envia.com'), '/');

        $carriers = array_filter(array_map('trim', explode(',', (string) env('ENVIA_CARRIERS', env('ENVIA_CARRIER', 'dhl')))));
        if (empty($carriers)) {
            $carriers = ['dhl'];
        }

        $allRates = [];

        foreach ($carriers as $carrier) {
            $carrier = strtolower($carrier);

            $payload = $this->payload($address, $cart);
            $payload['shipment']['carrier'] = $carrier;
            unset($payload['shipment']['service']);

            try {
                $response = Http::withToken($token)
                    ->acceptJson()
                    ->asJson()
                    ->timeout(30)
                    ->post($base . '/ship/rate/', $payload);

                $json = $response->json() ?? [];
                $normalized = $response->successful() ? $this->normalizeRates($json) : [];

                Log::info('ENVIA RATES DEBUG', [
                    'carrier' => $carrier,
                    'status' => $response->status(),
                    'raw_data_count' => is_array($json['data'] ?? null) ? count($json['data']) : null,
                    'normalized_count' => count($normalized),
                    'normalized_names' => array_map(fn($r) => ($r['name'] ?? '') . ' ' . ($r['service'] ?? '') . ' $' . ($r['price'] ?? ''), $normalized),
                    'error' => $json['error'] ?? null,
                    'meta' => $json['meta'] ?? null,
                ]);

                $allRates = array_merge($allRates, $normalized);
            } catch (\Throwable $e) {
                Log::warning('Envia.com quote exception', [
                    'carrier' => $carrier,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return collect($allRates)
            ->filter(fn($r) => (float)($r['price'] ?? 0) > 0)
            ->sortBy('price')
            ->values()
            ->all();
    }

    private function payload($address, array $cart): array
    {
        $weight = $this->cartWeight($cart);

        return [
            'origin' => [
                'name' => env('APP_NAME', 'Jureto'),
                'company' => env('APP_NAME', 'Jureto'),
                'email' => env('MAIL_FROM_ADDRESS', 'ventas@example.com'),
                'phone' => env('ORIGIN_PHONE', '5555555555'),
                'street' => env('ORIGIN_STREET', 'Domicilio origen'),
                'number' => env('ORIGIN_EXT_NUMBER', '1'),
                'district' => env('ORIGIN_COLONY', 'Centro'),
                'city' => env('ORIGIN_MUNICIPALITY', 'Toluca'),
                'state' => $this->stateCode(env('ORIGIN_STATE', 'MX')),
                'country' => 'MX',
                'postalCode' => env('ENVIA_SHIPPER_POSTAL_CODE', env('ORIGIN_POSTAL_CODE', '50000')),
            ],
            'destination' => [
                'name' => $address->contact_name ?: 'Cliente',
                'company' => $address->contact_name ?: 'Cliente',
                'email' => optional(auth()->user())->email ?: env('MAIL_FROM_ADDRESS', 'cliente@example.com'),
                'phone' => $address->phone ?: '5555555555',
                'street' => $address->street ?: 'Domicilio destino',
                'number' => $address->ext_number ?: 'S/N',
                'district' => $address->colony ?: 'Centro',
                'city' => $address->municipality ?: 'Municipio',
                'state' => $this->stateCode($address->state ?: 'MX'),
                'country' => 'MX',
                'postalCode' => $address->postal_code,
            ],
            'packages' => [
                [
                    'content' => 'Productos de papeleria/oficina',
                    'amount' => 1,
                    'type' => 'box',
                    'weight' => $weight,
                    'insurance' => 0,
                    'declaredValue' => max(1, round($this->cartValue($cart), 2)),
                    'weightUnit' => 'KG',
                    'lengthUnit' => 'CM',
                    'dimensions' => [
                        'length' => (float) env('ENVIA_DEFAULT_LENGTH', 30),
                        'width' => (float) env('ENVIA_DEFAULT_WIDTH', 25),
                        'height' => (float) env('ENVIA_DEFAULT_HEIGHT', 15),
                    ],
                ],
            ],
            'shipment' => [
                'carrier' => env('ENVIA_CARRIER', 'dhl'),
                'type' => 1,
            ],
            'settings' => [
                'currency' => 'MXN',
            ],
        ];
    }

    private function stateCode(?string $state): string
    {
        $value = trim((string) $state);
        $key = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $value));

        $map = [
            'estado de mexico' => 'MX',
            'mexico' => 'MX',
            'nuevo leon' => 'NL',
            'nuevo león' => 'NL',
            'ciudad de mexico' => 'CX',
            'cdmx' => 'CX',
            'jalisco' => 'JA',
            'puebla' => 'PU',
            'queretaro' => 'QT',
            'veracruz' => 'VE',
            'yucatan' => 'YU',
            'guanajuato' => 'GT',
            'hidalgo' => 'HG',
            'morelos' => 'MO',
            'oaxaca' => 'OA',
            'sinaloa' => 'SI',
            'sonora' => 'SO',
            'tamaulipas' => 'TM',
            'tlaxcala' => 'TL',
        ];

        if (strlen($value) <= 3) {
            return strtoupper($value);
        }

        return $map[$key] ?? strtoupper(substr($key, 0, 2));
    }
    private function normalizeRates(array $json): array
    {
        $rates = $json['data'] ?? $json['rates'] ?? $json['quotations'] ?? [];

        if (!is_array($rates)) {
            return [];
        }

        if (isset($rates['carrier']) || isset($rates['service']) || isset($rates['totalPrice'])) {
            $rates = [$rates];
        }

        return collect($rates)
            ->filter(fn($r) => is_array($r))
            ->map(function ($r) {
                $carrier = $r['carrier']
                    ?? $r['carrierName']
                    ?? $r['provider']
                    ?? $r['company']
                    ?? 'Paqueteria';

                $service = $r['service']
                    ?? $r['serviceName']
                    ?? $r['serviceDescription']
                    ?? $r['deliveryType']
                    ?? 'Servicio';

                $price = $r['totalPrice']
                    ?? $r['total']
                    ?? $r['price']
                    ?? $r['amount']
                    ?? $r['shipmentCost']
                    ?? 0;

                $price = is_numeric($price) ? (float) $price : 0;

                if ($price > 1000) {
                    $price = $price / 100;
                }

                $eta = $r['deliveryEstimate']
                    ?? $r['eta']
                    ?? data_get($r, 'deliveryDate.dateDifference');

                if (is_numeric($eta)) {
                    $eta = ((int) $eta) . ' día(s)';
                } else {
                    $eta = (string) $eta;
                    $eta = str_replace(
                        ['dÃƒÂ­a', 'dÃ­a', 'dÃƒÂ­as', 'dÃ­as', 'dias'],
                        ['día', 'día', 'días', 'días', 'días'],
                        $eta
                    );
                }

                return [
                    'code' => (string) ($r['id'] ?? md5(json_encode($r))),
                    'rate_id' => (string) ($r['id'] ?? ''),
                    'name' => (string) $carrier,
                    'carrier' => (string) $carrier,
                    'service' => (string) $service,
                    'eta' => $eta ?: 'Entrega estimada',
                    'price' => (float) $price,
                    'currency' => (string) ($r['currency'] ?? 'MXN'),
                    '_raw' => $r,
                ];
            })
            ->filter(fn($r) => (float) ($r['price'] ?? 0) > 0)
            ->sortBy('price')
            ->values()
            ->all();
    }

    private function cartWeight(array $cart): float
    {
        $weight = 0;

        foreach ($cart as $row) {
            $qty = max(1, (int) ($row['qty'] ?? 1));
            $itemWeight = (float) ($row['weight'] ?? env('ENVIA_DEFAULT_WEIGHT', 1));
            $weight += $itemWeight * $qty;
        }

        return max(0.1, round($weight, 2));
    }

    private function cartValue(array $cart): float
    {
        $value = 0;

        foreach ($cart as $row) {
            $qty = max(1, (int) ($row['qty'] ?? 1));
            $value += ((float) ($row['price'] ?? 0)) * $qty;
        }

        return $value;
    }
}
