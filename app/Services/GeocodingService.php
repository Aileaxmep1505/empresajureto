<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeocodingService
{
    /**
     * Geocodifica una dirección con Nominatim (OpenStreetMap).
     * Devuelve ['lat'=>float,'lng'=>float,'display_name'=>string] o null.
     *
     * Nota: Nominatim pide User-Agent y rate limit. Usamos cache para no spamear.
     */
    public function geocode(string $query): ?array
    {
        $q = trim(preg_replace('/\s+/', ' ', $query));
        if ($q === '') return null;

        $key = 'geo:nominatim:' . sha1($q);

        return Cache::remember($key, now()->addDays(30), function () use ($q) {
            $res = Http::timeout(12)
                ->withHeaders([
                    // Cambia esto por tu dominio/correo real si quieres.
                    'User-Agent' => config('app.name', 'Laravel') . ' geocoder',
                    'Accept'     => 'application/json',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q'      => $q,
                    'format' => 'json',
                    'limit'  => 1,
                    'addressdetails' => 1,
                ]);

            if (!$res->ok()) return null;

            $json = $res->json();
            if (!is_array($json) || empty($json[0])) return null;

            $hit = $json[0];

            $lat = isset($hit['lat']) ? (float) $hit['lat'] : null;
            $lng = isset($hit['lon']) ? (float) $hit['lon'] : null;

            if (!$lat || !$lng) return null;

            return [
                'lat' => $lat,
                'lng' => $lng,
                'display_name' => (string)($hit['display_name'] ?? ''),
            ];
        });
    }

    public function buildQuery(array $parts): string
    {
        // Orden recomendado: calle, colonia, ciudad, estado, cp, México
        $clean = [];
        foreach ($parts as $p) {
            $p = trim((string)$p);
            if ($p !== '') $clean[] = $p;
        }
        // Ayuda a que ubique mejor en MX:
        if (!empty($clean) && !preg_grep('/mexico/i', $clean)) {
            $clean[] = 'México';
        }
        return implode(', ', $clean);
    }
}
