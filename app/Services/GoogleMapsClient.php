<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleMapsClient
{
    private function serverKey(): string
    {
        $key = (string) config('services.google_maps.server_key');

        if ($key === '') {
            throw new \RuntimeException('Falta GOOGLE_MAPS_SERVER_KEY en .env');
        }

        return $key;
    }

    public function nearestRoad(float $lat, float $lng): array
    {
        try {
            $response = Http::timeout(12)->get('https://roads.googleapis.com/v1/nearestRoads', [
                'points' => $lat . ',' . $lng,
                'key' => $this->serverKey(),
            ]);

            if (!$response->ok()) {
                Log::warning('Google Roads HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [null, null, null];
            }

            $json = $response->json();
            $point = $json['snappedPoints'][0]['location'] ?? null;

            if (!$point) {
                return [null, null, null];
            }

            return [
                isset($point['latitude']) ? (float) $point['latitude'] : null,
                isset($point['longitude']) ? (float) $point['longitude'] : null,
                $json['snappedPoints'][0]['placeId'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning('Google Roads nearestRoad exception', [
                'lat' => $lat,
                'lng' => $lng,
                'error' => $e->getMessage(),
            ]);

            return [null, null, null];
        }
    }

    public function computeRoute(array $origin, array $destination, array $waypoints = [], bool $avoidTolls = false, bool $roundTrip = false): array
    {
        $intermediates = [];

        foreach ($waypoints as $point) {
            if (!isset($point['lat'], $point['lng'])) {
                continue;
            }

            $intermediates[] = [
                'location' => [
                    'latLng' => [
                        'latitude' => (float) $point['lat'],
                        'longitude' => (float) $point['lng'],
                    ],
                ],
            ];
        }

        if ($roundTrip) {
            $finalDestination = $origin;
            $allIntermediates = $intermediates;

            if (isset($destination['lat'], $destination['lng'])) {
                $allIntermediates[] = [
                    'location' => [
                        'latLng' => [
                            'latitude' => (float) $destination['lat'],
                            'longitude' => (float) $destination['lng'],
                        ],
                    ],
                ];
            }

            $intermediates = $allIntermediates;
        } else {
            $finalDestination = $destination;
        }

        $body = [
            'origin' => [
                'location' => [
                    'latLng' => [
                        'latitude' => (float) $origin['lat'],
                        'longitude' => (float) $origin['lng'],
                    ],
                ],
            ],
            'destination' => [
                'location' => [
                    'latLng' => [
                        'latitude' => (float) $finalDestination['lat'],
                        'longitude' => (float) $finalDestination['lng'],
                    ],
                ],
            ],
            'travelMode' => 'DRIVE',
            'routingPreference' => 'TRAFFIC_AWARE',
            'computeAlternativeRoutes' => false,
            'languageCode' => 'es-MX',
            'units' => 'METRIC',
            'extraComputations' => [
                'TOLLS',
            ],
            'routeModifiers' => [
                'avoidTolls' => $avoidTolls,
                'avoidHighways' => false,
                'avoidFerries' => false,
            ],
        ];

        if (!empty($intermediates)) {
            $body['intermediates'] = $intermediates;
        }

        $fieldMask = implode(',', [
            'routes.duration',
            'routes.staticDuration',
            'routes.distanceMeters',
            'routes.description',
            'routes.polyline.encodedPolyline',
            'routes.travelAdvisory.tollInfo',
            'routes.legs.distanceMeters',
            'routes.legs.duration',
            'routes.legs.staticDuration',
            'routes.legs.polyline.encodedPolyline',
            'routes.legs.travelAdvisory.tollInfo',
        ]);

        try {
            $response = Http::timeout(25)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Goog-Api-Key' => $this->serverKey(),
                    'X-Goog-FieldMask' => $fieldMask,
                ])
                ->post('https://routes.googleapis.com/directions/v2:computeRoutes', $body);

            if (!$response->ok()) {
                Log::warning('Google Routes HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'ok' => false,
                    'message' => 'Google Routes API no respondió correctamente.',
                    'detail' => $response->json() ?: $response->body(),
                ];
            }

            $json = $response->json();
            $route = $json['routes'][0] ?? null;

            if (!$route) {
                return [
                    'ok' => false,
                    'message' => 'Google no devolvió una ruta válida.',
                    'detail' => $json,
                ];
            }

            return [
                'ok' => true,
                'route' => $this->normalizeRoute($route),
                'raw' => $json,
            ];
        } catch (\Throwable $e) {
            Log::warning('Google Routes exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Error consultando Google Routes API.',
                'detail' => $e->getMessage(),
            ];
        }
    }

    private function normalizeRoute(array $route): array
    {
        $distance = (int) ($route['distanceMeters'] ?? 0);
        $duration = $this->durationToSeconds($route['duration'] ?? '0s');
        $staticDuration = $this->durationToSeconds($route['staticDuration'] ?? '0s');

        $encoded = $route['polyline']['encodedPolyline'] ?? null;

        $tolls = $this->extractTollInfo($route['travelAdvisory']['tollInfo'] ?? null);

        $legs = [];

        foreach (($route['legs'] ?? []) as $leg) {
            $legs[] = [
                'distance_meters' => (int) ($leg['distanceMeters'] ?? 0),
                'duration_seconds' => $this->durationToSeconds($leg['duration'] ?? '0s'),
                'static_duration_seconds' => $this->durationToSeconds($leg['staticDuration'] ?? '0s'),
                'polyline' => $leg['polyline']['encodedPolyline'] ?? null,
                'tolls' => $this->extractTollInfo($leg['travelAdvisory']['tollInfo'] ?? null),
            ];
        }

        return [
            'distance_meters' => $distance,
            'distance_km' => round($distance / 1000, 2),
            'duration_seconds' => $duration,
            'duration_minutes' => round($duration / 60),
            'static_duration_seconds' => $staticDuration,
            'static_duration_minutes' => round($staticDuration / 60),
            'traffic_delay_seconds' => max(0, $duration - $staticDuration),
            'traffic_delay_minutes' => round(max(0, $duration - $staticDuration) / 60),
            'polyline' => $encoded,
            'coordinates' => $this->decodePolyline($encoded),
            'tolls' => $tolls,
            'legs' => $legs,
            'description' => $route['description'] ?? null,
        ];
    }

    private function extractTollInfo(?array $tollInfo): array
    {
        if (!$tollInfo) {
            return [
                'has_tolls' => false,
                'estimated_price' => null,
                'currency' => 'MXN',
                'formatted' => 'Sin casetas estimadas',
                'unknown_price' => false,
            ];
        }

        $prices = $tollInfo['estimatedPrice'] ?? [];

        if (empty($prices)) {
            return [
                'has_tolls' => true,
                'estimated_price' => null,
                'currency' => 'MXN',
                'formatted' => 'Casetas detectadas, precio no disponible',
                'unknown_price' => true,
            ];
        }

        $total = 0;
        $currency = 'MXN';

        foreach ($prices as $price) {
            $currency = $price['currencyCode'] ?? $currency;

            $units = (int) ($price['units'] ?? 0);
            $nanos = (int) ($price['nanos'] ?? 0);

            $total += $units + ($nanos / 1000000000);
        }

        return [
            'has_tolls' => $total > 0,
            'estimated_price' => round($total, 2),
            'currency' => $currency,
            'formatted' => $currency . ' $' . number_format($total, 2),
            'unknown_price' => false,
        ];
    }

    private function durationToSeconds($duration): int
    {
        $duration = (string) $duration;

        if (preg_match('/^([0-9.]+)s$/', $duration, $m)) {
            return (int) round((float) $m[1]);
        }

        return 0;
    }

    private function decodePolyline(?string $encoded): array
    {
        if (!$encoded) {
            return [];
        }

        $points = [];
        $index = 0;
        $lat = 0;
        $lng = 0;
        $length = strlen($encoded);

        while ($index < $length) {
            $result = 1;
            $shift = 0;

            do {
                $b = ord($encoded[$index++]) - 63 - 1;
                $result += $b << $shift;
                $shift += 5;
            } while ($b >= 0x1f && $index < $length);

            $lat += ($result & 1) ? ~($result >> 1) : ($result >> 1);

            $result = 1;
            $shift = 0;

            do {
                $b = ord($encoded[$index++]) - 63 - 1;
                $result += $b << $shift;
                $shift += 5;
            } while ($b >= 0x1f && $index < $length);

            $lng += ($result & 1) ? ~($result >> 1) : ($result >> 1);

            $points[] = [
                'lat' => $lat * 1e-5,
                'lng' => $lng * 1e-5,
            ];
        }

        return $points;
    }
}