<?php

namespace App\Services;

use App\Models\LicitacionPropuesta;
use App\Models\LicitacionPdf;
use App\Models\LicitacionPdfPage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class LicitacionIaService
{
    /**
     * Procesa un split (requisición) con IA
     */
    public function processSplitWithAi(LicitacionPropuesta $propuesta, int $splitIndex)
    {
        $pdf = $propuesta->licitacionPdf;

        if (!$pdf) {
            throw new \Exception("La propuesta no tiene PDF asignado.");
        }

        $splits = $pdf->splits;
        if (!isset($splits[$splitIndex])) {
            throw new \Exception("El split {$splitIndex} no existe.");
        }

        $split = $splits[$splitIndex];

        $from = $split['from'] ?? $split['from_page'] ?? null;
        $to   = $split['to']   ?? $split['to_page']   ?? null;

        if (!$from || !$to) {
            throw new \Exception("Split sin rangos de páginas.");
        }

        // ================================
        // 1. Traer páginas y su texto
        // ================================
        $pages = LicitacionPdfPage::where('licitacion_pdf_id', $pdf->id)
            ->whereBetween('page_number', [$from, $to])
            ->orderBy('page_number')
            ->get();

        if ($pages->isEmpty()) {
            throw new \Exception("No existen registros de páginas en BD para este PDF.");
        }

        $finalText = "";

        foreach ($pages as $page) {
            $text = trim($page->text ?? "");

            // ===============================================
            // 2. SI EL TEXTO ESTÁ VACÍO → HACER OCR CON IA
            // ===============================================
            if ($text === "") {
                try {
                    $imagePath = $this->generatePageImage($pdf, $page->page_number);
                    $text = $this->ocrWithAi($imagePath);

                    // Guardamos OCR en la BD para no repetirlo
                    $page->text = $text;
                    $page->save();

                } catch (\Throwable $e) {
                    Log::error("OCR-IA fallo en página {$page->page_number}", [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $finalText .= "\n\n" . $text;
        }

        if (trim($finalText) === "") {
            throw new \Exception("La IA no pudo extraer contenido útil del PDF.");
        }

        // ============================================
        // 3. Llamada a OpenAI para procesar el renglón
        // ============================================
        $items = $this->extractItemsWithAi($finalText);

        // ============================================
        // 4. Guardar items en BD
        // ============================================
        foreach ($items as $it) {
            $propuesta->items()->create([
                'request_item_id' => null,
                'product_id'      => null,
                'descripcion_raw' => $it['descripcion'] ?? '',
                'cantidad_propuesta' => $it['cantidad'] ?? null,
                'precio_unitario'    => $it['precio'] ?? null,
                'subtotal'           => isset($it['cantidad'], $it['precio'])
                    ? $it['cantidad'] * $it['precio']
                    : 0,
            ]);
        }
    }


    /**
     * Convierte una página de PDF a imagen PNG temporal
     */
    private function generatePageImage(LicitacionPdf $pdf, int $page)
    {
        $output = storage_path("app/tmp/pdf_page_{$pdf->id}_{$page}.png");

        // Uso de Imagick para extraer la página
        $imagick = new \Imagick();
        $imagick->setResolution(200, 200);
        $imagick->readImage($pdf->storage_path . "[{$page}]");
        $imagick->setImageFormat('png');
        $imagick->writeImage($output);

        return $output;
    }

    /**
     * OCR con IA usando OpenAI Vision
     */
    private function ocrWithAi(string $imagePath): string
    {
        $base64 = base64_encode(file_get_contents($imagePath));

        $resp = Http::withToken(env('OPENAI_API_KEY'))->post(
            env('OPENAI_BASE_URL') . "/v1/chat/completions",
            [
                "model" => env('OPENAI_PRIMARY_MODEL', 'gpt-4o-mini'),
                "messages" => [
                    [
                        "role" => "user",
                        "content" => [
                            [
                                "type" => "input_image",
                                "image_url" => "data:image/png;base64," . $base64
                            ],
                            [
                                "type" => "text",
                                "text" => "Extrae TODO el texto que veas en esta página. No omitas nada."
                            ]
                        ]
                    ]
                ]
            ]
        );

        if (!$resp->ok()) {
            return "";
        }

        return $resp->json()['choices'][0]['message']['content'] ?? "";
    }


    /**
     * Extrae renglones usando IA (texto → items)
     */
    private function extractItemsWithAi(string $text): array
    {
        $resp = Http::withToken(env('OPENAI_API_KEY'))->post(
            env('OPENAI_BASE_URL') . "/v1/chat/completions",
            [
                "model" => env('OPENAI_PRIMARY_MODEL', 'gpt-4o-mini'),
                "messages" => [
                    [
                        "role" => "user",
                        "content" => "Del siguiente texto, extrae renglones con formato JSON:
                        [
                          { descripcion: \"...\", cantidad: X, precio: Y }
                        ]
                        Si algún dato no aparece, déjalo null.
                        
                        Texto:
                        ----------------
                        {$text}
                        ----------------"
                    ]
                ]
            ]
        );

        if (!$resp->ok()) {
            return [];
        }

        $raw = $resp->json()['choices'][0]['message']['content'];

        try {
            return json_decode($raw, true) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
