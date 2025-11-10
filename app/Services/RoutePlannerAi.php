<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class RoutePlannerAi
{
    private ?Client $http = null;
    private string $provider;
    private string $model;
    private ?string $apiKey;

    public function __construct()
    {
        $this->provider = config('route_ai.provider', 'none');
        $this->model    = config('route_ai.model', '');
        $this->apiKey   = config('route_ai.api_key');
        $this->http     = new Client(['timeout' => 25]);
    }

    /**
     * Devuelve:
     * [
     *   'routes' => [
     *      ['driver'=>['id'=>..,'name'=>'..'],'order'=>[stop_ids...]],
     *   ],
     *   'rationale' => '...'
     * ]
     * o NULL si falla => el controlador cae a fallback local.
     */
    public function suggestRoutes(array $drivers, array $stopsWithZone, array $opts = [], ?string $note = null): ?array
    {
        if ($this->provider !== 'openai' || !$this->apiKey || !$this->model) {
            return null;
        }

        $prompt = "Eres planificador de rutas urbano. Objetivo: repartir paradas entre choferes minimizando tiempo
y EVITANDO que dos choferes visiten la MISMA ZONA. (zone es un campo ya calculado).
Devuelve SOLO JSON válido con:
{
  \"routes\": [
    { \"driver\": {\"id\":1, \"name\":\"...\"}, \"order\": [stop_ids...] }
  ],
  \"rationale\": \"breve explicación\"
}
Reglas:
- Exclusividad de zonas: paradas con el mismo 'zone' deben quedar con el mismo chofer.
- Si hay origen común, ya viene reflejado en lat/lng de drivers.
- Balancea la carga sin romper exclusividad.

DRIVERS: ".json_encode($drivers)."
STOPS (con zone): ".json_encode($stopsWithZone)."
OPTS: ".json_encode($opts)."
".($note ? "NOTA: ".$note : "");

        $body = [
            'model' => $this->model,
            'messages' => [
                ['role'=>'system','content'=>'Responde únicamente con un bloque JSON válido.'],
                ['role'=>'user','content'=>$prompt],
            ],
            'temperature' => 0.1,
        ];

        try {
            $res = $this->http->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $body,
            ]);

            $json = json_decode((string)$res->getBody(), true);
            $text = $json['choices'][0]['message']['content'] ?? '';
            if (!$text) return null;

            $parsed = $this->extractJson($text);
            if (!is_array($parsed) || empty($parsed['routes'])) return null;

            return $parsed;
        } catch (RequestException $e) {
            // log opcional: \Log::warning('RoutePlannerAi error', ['msg'=>$e->getMessage()]);
            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Extrae el primer JSON válido de un texto */
    private function extractJson(string $text): ?array
    {
        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $text, $m)) {
            $j = json_decode($m[0], true);
            if (json_last_error() === JSON_ERROR_NONE) return $j;
        }
        return null;
    }
}
