<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;

/**
 * Cliente OSRM mejorado con:
 * - Normalización robusta de coordenadas
 * - Timeouts y reintentos simples
 * - Endpoints: trip, route, table, nearest, health
 * - Helpers de resumen para UI: summarizeTrip, summarizeRoute
 *
 * Env:
 *   OSRM_BASE_URL=http://localhost:5000
 *   OSRM_PROFILE=driving           (driving|car|foot|bike, etc. según build)
 *   OSRM_TIMEOUT=15                (segundos)
 *   OSRM_RETRIES=1                 (reintentos ante 5xx/timeouts)
 */
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

        $this->http = $http ?: new Client([
            'timeout'         => max(5, $timeout),
            'connect_timeout' => 5,
        ]);
    }

    /* ==================== PÚBLICO (RAW RESPONSES) ==================== */

    /** TRIP: resuelve orden óptimo (TSP/VRP simple). */
    public function trip(array $coords, array $options = []): array
    {
        $coordStr = $this->coordsToQuery($coords); // lng,lat;lng,lat...
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

    /** ROUTE: ruta en el orden dado (con alternativas si están habilitadas en tu build). */
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

    /** TABLE: matriz de tiempo/distancia entre puntos (útil para heurísticas propias). */
    public function table(array $coords, array $options = []): array
    {
        $coordStr = $this->coordsToQuery($coords);
        $query = array_merge([
            'annotations' => 'duration,distance',
        ], $options);

        return $this->get("/table/v1/{$this->profile}/{$coordStr}", $query);
    }

    /** NEAREST: punto de red más cercano a una coordenada (snap to road). */
    public function nearest(array $coord, array $options = []): array
    {
        $c = $this->oneToLngLat($coord);
        $coordStr = "{$c['lng']},{$c['lat']}";
        return $this->get("/nearest/v1/{$this->profile}/{$coordStr}", $options);
    }

    /** HEALTH: ping básico al servidor OSRM. */
    public function health(): array
    {
        // / does not exist as JSON; usamos /nearest con coords dummy (0,0) para ver respuesta.
        try {
            $resp = $this->nearest(['lat' => 0, 'lng' => 0]);
            $ok = isset($resp['code']) ? true : false;
            return ['ok' => $ok, 'server' => $this->base, 'code' => $resp['code'] ?? null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'server' => $this->base, 'error' => $e->getMessage()];
        }
    }

    /* ==================== PÚBLICO (SUMMARIZERS PARA UI) ==================== */

    /**
     * Resumen listo para UI a partir de un /trip:
     * - Devuelve ordered indices (waypoint_indices), geometry, legs, totales.
     */
    public function summarizeTrip(array $tripResponse): array
    {
        if (!isset($tripResponse['code']) || $tripResponse['code'] !== 'Ok') {
            return [
                'ok'      => false,
                'code'    => $tripResponse['code'] ?? 'Unknown',
                'message' => Arr::get($tripResponse, 'message', 'OSRM trip error'),
            ];
        }

        $route      = $tripResponse['trips'][0] ?? null;
        $wayIndices = $tripResponse['waypoints'] ?? [];
        $wpIdx      = $route['waypoint_indices'] ?? $this->extractWaypointIndices($wayIndices);

        $legs = [];
        foreach (($route['legs'] ?? []) as $leg) {
            $legs[] = [
                'from'      => null, // se completa afuera si quieres
                'to'        => null,
                'distance'  => (int) round($leg['distance'] ?? 0),
                'duration'  => (int) round($leg['duration'] ?? 0),
                'adj_delay' => 0,
                'adj_duration' => (int) round($leg['duration'] ?? 0),
            ];
        }

        return [
            'ok'               => true,
            'code'             => 'Ok',
            'geometry'         => $route['geometry'] ?? null,
            'legs'             => $legs,
            'total_m'          => (int) round($route['distance'] ?? 0),
            'total_sec'        => (int) round($route['duration'] ?? 0),
            'waypoint_indices' => $wpIdx,
        ];
    }

    /**
     * Resumen listo para UI a partir de un /route:
     * - Toma la ruta principal y alternativas (si existen).
     */
    public function summarizeRoute(array $routeResponse): array
    {
        if (!isset($routeResponse['code']) || $routeResponse['code'] !== 'Ok') {
            return [
                'ok'      => false,
                'code'    => $routeResponse['code'] ?? 'Unknown',
                'message' => Arr::get($routeResponse, 'message', 'OSRM route error'),
            ];
        }

        $out = ['ok' => true, 'code' => 'Ok', 'routes' => []];

        foreach (($routeResponse['routes'] ?? []) as $r) {
            $legs = [];
            foreach (($r['legs'] ?? []) as $leg) {
                $legs[] = [
                    'from'      => null,
                    'to'        => null,
                    'distance'  => (int) round($leg['distance'] ?? 0),
                    'duration'  => (int) round($leg['duration'] ?? 0),
                    'adj_delay' => 0,
                    'adj_duration' => (int) round($leg['duration'] ?? 0),
                ];
            }

            $out['routes'][] = [
                'geometry'  => $r['geometry'] ?? null,
                'legs'      => $legs,
                'total_m'   => (int) round($r['distance'] ?? 0),
                'total_sec' => (int) round($r['duration'] ?? 0),
            ];
        }

        return $out;
    }

    /* ==================== PRIVADO ==================== */

    /** GET con JSON parse, manejo de 5xx/timeout y reintentos básicos. */
    protected function get(string $path, array $query = []): array
    {
        $url = "{$this->base}{$path}";
        $attempts = max(1, 1 + $this->retries);

        $lastEx = null;
        for ($i = 0; $i < $attempts; $i++) {
            try {
                $resp = $this->http->get($url, ['query' => $query, 'headers' => ['Accept' => 'application/json']]);
                $body = (string) $resp->getBody();

                /** @var array|null $json */
                $json = json_decode($body, true);
                if (!is_array($json)) {
                    return [
                        'code'    => 'BadJSON',
                        'message' => 'Respuesta no es JSON válida',
                        'raw'     => mb_substr($body, 0, 500),
                    ];
                }
                return $json;
            } catch (GuzzleException $e) {
                $lastEx = $e;
                // Reintenta sólo ante timeouts/5xx
                $msg = $e->getMessage();
                $isRetryable = str_contains($msg, 'timed out') || str_contains($msg, 'cURL error 28') || $this->isServerError($e);
                if (!$isRetryable || $i === $attempts - 1) {
                    return [
                        'code'    => 'HttpError',
                        'message' => $e->getMessage(),
                    ];
                }
                // pequeño backoff
                usleep(200000); // 200ms
            } catch (\Throwable $e) {
                return [
                    'code'    => 'ClientError',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'code'    => 'ClientError',
            'message' => $lastEx?->getMessage() ?? 'Unknown error',
        ];
    }

    protected function isServerError(GuzzleException $e): bool
    {
        // Guzzle no siempre expone status code fácil; inspeccionamos mensaje
        $m = $e->getMessage();
        return str_contains($m, '500') || str_contains($m, '502') || str_contains($m, '503') || str_contains($m, '504');
    }

    /** Acepta distintos formatos y devuelve "lng,lat;lng,lat" */
    protected function coordsToQuery(array $coords): string
    {
        $norm = [];
        foreach ($coords as $c) {
            $p = $this->oneToLngLat($c);
            $norm[] = "{$p['lng']},{$p['lat']}";
        }
        return implode(';', $norm);
    }

    /**
     * Normaliza una coordenada a ['lat'=>float,'lng'=>float].
     * Acepta:
     *  - ['lat'=>..,'lng'=>..]
     *  - ['latitude'=>..,'longitude'=>..]
     *  - [lat,lng] ó [lng,lat] (autodetección si valores lucen como México: lat 14..33, lng -118..-86 aprox.)
     *  - "lng,lat" string
     */
    protected function oneToLngLat($c): array
    {
        if (is_string($c) && str_contains($c, ',')) {
            [$a, $b] = array_map('trim', explode(',', $c, 2));
            $lng = (float) $a; $lat = (float) $b;
            return ['lat' => $lat, 'lng' => $lng];
        }

        if (is_array($c) && Arr::has($c, ['lat','lng'])) {
            return ['lat' => (float) $c['lat'], 'lng' => (float) $c['lng']];
        }
        if (is_array($c) && Arr::has($c, ['latitude','longitude'])) {
            return ['lat' => (float) $c['latitude'], 'lng' => (float) $c['longitude']];
        }

        // numéricos indexados
        if (is_array($c) && isset($c[0], $c[1])) {
            $a = (float) $c[0];
            $b = (float) $c[1];
            // Heurística: lat típicamente  -90..90, lng -180..180; en MX lat ~14-33, lng ~-118..-86
            $isLatFirst = ($a >= -90 && $a <= 90) && ($b >= -180 && $b <= 180);
            if ($isLatFirst) {
                return ['lat' => $a, 'lng' => $b];
            }
            return ['lat' => $b, 'lng' => $a];
        }

        throw new \InvalidArgumentException('Coordenada inválida');
    }

    /** Intenta obtener waypoint_indices desde waypoints si la ruta no los trae. */
    protected function extractWaypointIndices(array $waypoints): array
    {
        $out = [];
        foreach ($waypoints as $w) {
            if (isset($w['waypoint_index'])) $out[] = $w['waypoint_index'];
        }
        return $out;
    }
}
