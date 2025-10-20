<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SearchAiClient
{
    public function expand(string $q): array
    {
        $q = trim($q);
        if ($q === '') return [];

        $apiKey  = config('searchai.key');
        $base    = rtrim(config('searchai.base'), '/');
        $model   = config('searchai.model');
        $timeout = (int) config('searchai.timeout', 12);

        // Si no hay API key, usa fallback local
        if (!$apiKey) {
            return $this->fallbackExpansion($q);
        }

        try {
            $prompt = "Devuelve una lista separada por comas con 8 a 12 términos cortos y relevantes "
                    . "en español para buscar en un e-commerce a partir de: \"$q\". Incluye sinónimos, "
                    . "variantes comunes, errores ortográficos frecuentes, materiales, subcategorías y marcas. "
                    . "Solo la lista separada por comas, sin texto adicional.";

            $res = Http::timeout($timeout)
                ->withToken($apiKey)
                ->post($base . '/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Eres un motor de expansión de consultas. Respondes solo con una lista CSV.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.2,
                ]);

            if (!$res->successful()) {
                return $this->fallbackExpansion($q);
            }

            $txt = (string) data_get($res->json(), 'choices.0.message.content', '');
            $terms = collect(explode(',', $txt))
                ->map(fn($s)=> trim(mb_strtolower($s)))
                ->filter()
                ->unique()
                ->values()
                ->all();

            // Mezclamos con fallback por robustez
            return collect($terms)->merge($this->fallbackExpansion($q))->unique()->take(20)->values()->all();
        } catch (\Throwable $e) {
            return $this->fallbackExpansion($q);
        }
    }

    public function suggest(string $seed): array
    {
        $seed = mb_strtolower(trim($seed));
        if ($seed === '') return [];
        $base = $this->fallbackExpansion($seed);

        return collect($base)
            ->sortByDesc(fn($t)=> str_starts_with($t, $seed))
            ->take(6)
            ->values()
            ->all();
    }

    private function fallbackExpansion(string $q): array
    {
        $q = mb_strtolower($q);
        $terms = [$q];

        $dict = [
            'lapiz' => ['lápiz','lapices','lápices','hb','#2','grafito','escolar','papelería','papermate','bic','triangular','madera'],
            'pluma' => ['bolígrafo','boligrafo','esfero','tinta','punta fina','gel','bic','papermate'],
            'impresora' => ['printer','multifuncional','tinta','láser','toner','cartucho','epson','hp','brother'],
            'computadora' => ['pc','laptop','notebook','portátil','oficina','hardware'],
        ];
        foreach ($dict as $k => $arr) {
            if (str_contains($q, $k)) $terms = array_merge($terms, $arr);
        }

        // Normalización acentos
        $noAcc = strtr($q, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u']);
        $terms[] = $noAcc;

        return collect($terms)->filter()->unique()->values()->all();
    }
}
