<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;

class OsrmClient
{
    protected Client $http;
    protected string $base;
    protected string $profile;
    protected int $retries;

    public function __construct(?Client $http = null)
    {
        $this->base    = rtrim(config('services.osrm.base', env('OSRM_BASE_URL', 'http://localhost:5000')), '/');
        $this->profile = trim(config('services.osrm.profile', env('OSRM_PROFILE', 'driving')));
        $timeout       = (int) config('services.osrm.timeout', (int) env('OSRM_TIMEOUT', 15));
        $this->retries = (int) config('services.osrm.retries', (int) env('OSRM_RETRIES', 1));

        // 👇 CLAVE: http_errors=false para NO tirar excepción en 4xx (OSRM manda JSON útil)
        $this->http = $http ?: new Client([
            'timeout'         => max(5, $timeout),
            'connect_timeout' => 5,
            'http_errors'     => false,
        ]);
    }

    /* ==================== PÚBLICO (RAW RESPONSES) ==================== */

    public function trip(array $coords, array $options = []): array
    {
        $coordStr = $this->coordsToQuery($coords);
        $query = array_merge([
            'source'      => 'first',
            'roundtrip'   => 'false',
            'destination' => 'last',
            'overview'    => 'full',
            'geometries'  => 'geojson',
            'annotations' => 'duration,distance',
        ], $options);

        return $this->get("/trip/v1/{$this->profile}/{$coordStr}", $query);
    }

    public function route(array $coords, array $options = []): array
    {
        $coordStr = $this->coordsToQuery($coords);
        $query = array_merge([
            'overview'     => 'full',
            'geometries'   => 'geojson',
            'alternatives' => 'true',
            'steps'        => 'false',
            'annotations'  => 'duration,distance',
        ], $options);

        return $this->get("/route/v1/{$this->profile}/{$coordStr}", $query);
    }

    public function table(array $coords, array $options = []): array
    {
        $coordStr = $this->coordsToQuery($coords);
        $query = array_merge([
            'annotations' => 'duration,distance',
        ], $options);

        return $this->get("/table/v1/{$this->profile}/{$coordStr}", $query);
    }

    public function nearest(array $coord, array $options = []): array
    {
        $c = $this->oneToLngLat($coord);
        $coordStr = "{$c['lng']},{$c['lat']}";
        return $this->get("/nearest/v1/{$this->profile}/{$coordStr}", $options);
    }

    public function health(): array
    {
        try {
            // 👇 usa un punto “normal” (CDMX) para evitar 0,0
            $resp = $this->nearest(['lat' => 19.4326, 'lng' => -99.1332]);
            return [
                'ok' => isset($resp['code']),
                'server' => $this->base,
                'code' => $resp['code'] ?? null,
                'message' => $resp['message'] ?? null,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'server' => $this->base, 'error' => $e->getMessage()];
        }
    }

    /* ==================== PRIVADO ==================== */

    protected function get(string $path, array $query = []): array
    {
        $url = "{$this->base}{$path}";
        $attempts = max(1, 1 + $this->retries);

        $lastEx = null;

        for ($i = 0; $i < $attempts; $i++) {
            try {
                $resp = $this->http->get($url, [
                    'query'   => $query,
                    'headers' => ['Accept' => 'application/json'],
                ]);

                $status = (int) $resp->getStatusCode();
                $body   = (string) $resp->getBody();

                $json = json_decode($body, true);

                // Si OSRM respondió JSON (aunque sea 400), devuélvelo tal cual + meta útil
                if (is_array($json)) {
                    // Adjunta meta (sin romper estructura OSRM)
                    $json['_meta'] = [
                        'status' => $status,
                        'url'    => $url,
                        'query'  => $query,
                    ];
                    return $json;
                }

                // No JSON
                return [
                    'code'    => 'BadJSON',
                    'message' => 'Respuesta no es JSON válida',
                    'raw'     => mb_substr($body, 0, 1200),
                    '_meta'   => [
                        'status' => $status,
                        'url'    => $url,
                        'query'  => $query,
                    ],
                ];

            } catch (GuzzleException $e) {
                $lastEx = $e;

                $msg = $e->getMessage();
                $isRetryable =
                    str_contains($msg, 'timed out') ||
                    str_contains($msg, 'cURL error 28') ||
                    $this->isServerErrorMessage($msg);

                if (!$isRetryable || $i === $attempts - 1) {
                    return [
                        'code'    => 'HttpError',
                        'message' => $e->getMessage(),
                        '_meta'   => [
                            'url'   => $url,
                            'query' => $query,
                        ],
                    ];
                }

                usleep(200000);
            } catch (\Throwable $e) {
                return [
                    'code'    => 'ClientError',
                    'message' => $e->getMessage(),
                    '_meta'   => [
                        'url'   => $url,
                        'query' => $query,
                    ],
                ];
            }
        }

        return [
            'code'    => 'ClientError',
            'message' => $lastEx?->getMessage() ?? 'Unknown error',
            '_meta'   => [
                'url'   => $url,
                'query' => $query,
            ],
        ];
    }

    protected function isServerErrorMessage(string $m): bool
    {
        return str_contains($m, '500') || str_contains($m, '502') || str_contains($m, '503') || str_contains($m, '504');
    }

    protected function coordsToQuery(array $coords): string
    {
        $norm = [];
        foreach ($coords as $c) {
            $p = $this->oneToLngLat($c);
            $this->assertValidLatLng($p['lat'], $p['lng']);
            // OSRM: lng,lat
            $norm[] = "{$p['lng']},{$p['lat']}";
        }
        return implode(';', $norm);
    }

    protected function oneToLngLat($c): array
    {
        if (is_string($c) && str_contains($c, ',')) {
            [$a, $b] = array_map('trim', explode(',', $c, 2));
            $lng = (float) $a;
            $lat = (float) $b;
            return ['lat' => $lat, 'lng' => $lng];
        }

        if (is_array($c) && Arr::has($c, ['lat','lng'])) {
            return ['lat' => (float) $c['lat'], 'lng' => (float) $c['lng']];
        }
        if (is_array($c) && Arr::has($c, ['latitude','longitude'])) {
            return ['lat' => (float) $c['latitude'], 'lng' => (float) $c['longitude']];
        }

        if (is_array($c) && isset($c[0], $c[1])) {
            $a = (float) $c[0];
            $b = (float) $c[1];

            // Si a parece lat (|a|<=90) y b parece lng (|b|<=180), asumimos [lat,lng]
            if (abs($a) <= 90 && abs($b) <= 180) {
                return ['lat' => $a, 'lng' => $b];
            }

            // Si a parece lng (|a|<=180) y b parece lat (|b|<=90), asumimos [lng,lat]
            if (abs($a) <= 180 && abs($b) <= 90) {
                return ['lat' => $b, 'lng' => $a];
            }

            // fallback
            return ['lat' => $b, 'lng' => $a];
        }

        throw new \InvalidArgumentException('Coordenada inválida');
    }

    protected function assertValidLatLng(float $lat, float $lng): void
    {
        if (!is_finite($lat) || !is_finite($lng)) {
            throw new \InvalidArgumentException('Coordenadas no finitas');
        }
        if (abs($lat) > 90 || abs($lng) > 180) {
            throw new \InvalidArgumentException("Coordenadas fuera de rango: lat={$lat}, lng={$lng}");
        }
        if (abs($lat) < 0.0000001 && abs($lng) < 0.0000001) {
            throw new \InvalidArgumentException('Coordenadas inválidas (0,0)');
        }
    }
}
