<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CatalogAiExtractService
{
    public function extractFromImages(array $absolutePaths): array
    {
        $apiKey = env('OPENAI_API_KEY');
        if (!$apiKey) {
            throw new \RuntimeException("Falta OPENAI_API_KEY en .env");
        }

        $model = env('OPENAI_MODEL', 'gpt-4o');

        $content = [
            [
                "type" => "text",
                "text" =>
"Eres un extractor de facturas/remisiones para inventario.
Extrae:
- supplier_name, folio, invoice_date, currency, subtotal, tax, total
- items: sku, description, quantity, unit, unit_price, line_total, brand, model, confidence (0-1)
Devuelve SOLO JSON con el esquema (strict)."
            ]
        ];

        foreach ($absolutePaths as $p) {
            if (!is_file($p)) continue;

            $b64  = base64_encode(file_get_contents($p));
            $mime = $this->guessMime($p);

            $content[] = [
                "type" => "image_url",
                "image_url" => [
                    "url" => "data:{$mime};base64,{$b64}"
                ]
            ];
        }

        // ====== SCHEMA CORREGIDO ======
        $schema = [
            "type" => "object",
            "additionalProperties" => false,
            "properties" => [
                "supplier_name" => ["type" => "string"],
                "folio"         => ["type" => "string"],
                "invoice_date"  => ["type" => "string"],
                "currency"      => ["type" => "string"],
                "subtotal"      => ["type" => "number"],
                "tax"           => ["type" => "number"],
                "total"         => ["type" => "number"],
                "items"         => [
                    "type"  => "array",
                    "items" => [
                        "type"                 => "object",
                        "additionalProperties" => false,
                        "properties" => [
                            "sku"        => ["type" => "string"],
                            "description"=> ["type" => "string"],
                            "quantity"   => ["type" => "number"],
                            "unit"       => ["type" => "string"],
                            "unit_price" => ["type" => "number"],
                            "line_total" => ["type" => "number"],
                            "brand"      => ["type" => "string"],
                            "model"      => ["type" => "string"],
                            "confidence" => ["type" => "number"]
                        ],
                        // 🔴 REQUIRED DEBE CONTENER TODAS LAS KEYS DE PROPERTIES
                        "required" => [
                            "sku",
                            "description",
                            "quantity",
                            "unit",
                            "unit_price",
                            "line_total",
                            "brand",
                            "model",
                            "confidence",
                        ],
                    ]
                ]
            ],
            // 🔴 IGUAL AQUÍ: TODAS LAS KEYS DE PROPERTIES
            "required" => [
                "supplier_name",
                "folio",
                "invoice_date",
                "currency",
                "subtotal",
                "tax",
                "total",
                "items",
            ]
        ];

        $payload = [
            "model" => $model,
            "messages" => [
                ["role" => "user", "content" => $content]
            ],
            "response_format" => [
                "type" => "json_schema",
                "json_schema" => [
                    "name"   => "invoice_extract",
                    "schema" => $schema,
                    "strict" => true
                ]
            ],
            "temperature" => 0
        ];

        $resp = Http::withToken($apiKey)
            ->timeout(180)
            ->post("https://api.openai.com/v1/chat/completions", $payload);

        if ($resp->failed()) {
            throw new \RuntimeException("OpenAI error: ".$resp->body());
        }

        $text = $resp->json('choices.0.message.content');
        $json = json_decode($text, true);

        if (!is_array($json)) {
            throw new \RuntimeException("Respuesta IA no es JSON válido: ".$text);
        }

        return $json;
    }

    private function guessMime(string $p): string
    {
        $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
        return match ($ext) {
            'png'  => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg'
        };
    }
}
