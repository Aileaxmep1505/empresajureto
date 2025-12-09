<?php

namespace App\Services;

use App\Models\LicitacionPdfPage;
use App\Models\LicitacionRequestItem;
use App\Models\Product;
use Illuminate\Support\Collection;

class LicitacionIaService
{
    public function __construct(
        protected IaClient $client
    ) {
    }

    /**
     * EXTRAER renglones de una página del PDF.
     *
     * Devuelve un arreglo tipo:
     * [
     *   [
     *     'line_raw'    => '200 BORRADORES PIZARRÓN BLANCO ...',
     *     'descripcion' => '200 BORRADORES PIZARRÓN BLANCO ...',
     *     'cantidad'    => 200,
     *     'unidad'      => 'PZA',
     *     'renglon'     => 1
     *   ],
     *   ...
     * ]
     */
    public function extractItemsFromPage(LicitacionPdfPage $page): array
    {
        $system = <<<SYS
Eres un asistente experto en licitaciones y requisiciones.

TU TAREA:
- Recibir el TEXTO COMPLETO de una página de un PDF de requisición.
- Identificar las LÍNEAS que representan renglones de productos/servicios.
- NO debes inventar ni modificar palabras.
- line_raw debe contener el texto EXACTO de la línea original.

DEVUELVE EXCLUSIVAMENTE un JSON con esta forma:

{
  "items": [
    {
      "line_raw": "texto original completo de la línea",
      "descripcion": "igual que line_raw o solo la parte descriptiva",
      "cantidad": 123.0,
      "unidad": "PZA",
      "renglon": 1
    }
  ]
}

REGLAS:
- Si no puedes detectar cantidad/unidad, pon null en esos campos.
- Nunca inventes datos.
- No añadas comentarios fuera del JSON.
SYS;

        $user = <<<USER
Texto de la página de la requisición:

{$page->raw_text}

Recuerda: responde SOLO el JSON con la lista "items".
USER;

        $response = $this->client->chatJson([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user',   'content' => $user],
        ]);

        $items = $response['items'] ?? [];

        if (! is_array($items)) {
            $items = [];
        }

        return $items;
    }

    /**
     * EXTRAER renglones de una página y GUARDARLOS en licitacion_request_items.
     *
     * Útil para correr desde un Job que procese cada página.
     *
     * @return LicitacionRequestItem[]
     */
    public function extractItemsFromPageAndPersist(
        LicitacionPdfPage $page,
        ?int $licitacionId = null,
        ?int $requisicionId = null
    ): array {
        $itemsData = $this->extractItemsFromPage($page);
        $created   = [];

        foreach ($itemsData as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $lineRaw  = $item['line_raw']    ?? null;
            $desc     = $item['descripcion'] ?? null;
            $cantidad = $item['cantidad']    ?? null;
            $unidad   = $item['unidad']      ?? null;
            $renglon  = $item['renglon']     ?? null;

            if (! $lineRaw || ! is_string($lineRaw)) {
                // Sin texto original, no tiene sentido guardar
                continue;
            }

            if ($desc === null) {
                $desc = $lineRaw;
            }

            if (! is_numeric($cantidad)) {
                $cantidad = null;
            }

            if ($renglon === null || ! is_numeric($renglon)) {
                $renglon = $index + 1;
            }

            $created[] = LicitacionRequestItem::create([
                'licitacion_id'          => $licitacionId,
                'requisicion_id'         => $requisicionId,
                'licitacion_pdf_page_id' => $page->id,
                'line_raw'               => $lineRaw,
                'descripcion'            => $desc,
                'cantidad'               => $cantidad,
                'unidad'                 => $unidad,
                'renglon'                => (int) $renglon,
                'status'                 => 'pending_match',
            ]);
        }

        return $created;
    }

    /**
     * SUGERIR el mejor producto de catálogo para un item de requisición,
     * recibiendo una lista de posibles candidatos desde tu BD.
     *
     * Devuelve un arreglo:
     * [
     *   'product_id'       => 123 | null,
     *   'match_score'      => 95  | null,
     *   'motivo_seleccion' => 'Coincide descripción, medida y uso'
     * ]
     */
    public function suggestProductMatch(
        LicitacionRequestItem $requestItem,
        Collection $candidateProducts
    ): array {
        if ($candidateProducts->isEmpty()) {
            return [
                'product_id'       => null,
                'match_score'      => 0,
                'motivo_seleccion' => 'No se proporcionaron candidatos',
            ];
        }

        // Armamos una lista de candidatos en texto
        $listText = $candidateProducts
            ->values()
            ->map(function (Product $p, int $index) {
                $n = $index + 1;

                return sprintf(
                    "%d) ID: %d | SKU: %s | Nombre: %s | Marca: %s | Unidad: %s | Descripción: %s",
                    $n,
                    $p->id,
                    $p->sku         ?? '-',
                    $p->name        ?? '-',
                    $p->brand       ?? '-',
                    $p->unit        ?? '-',
                    $p->description ?? '-'
                );
            })
            ->implode("\n");

        $system = <<<SYS
Eres un experto en catálogo de papelería, cómputo y suministros para licitaciones.

TU TAREA:
- Recibir un renglón de una requisición (texto original).
- Recibir una lista de productos de catálogo (ID, nombre, descripción, marca, unidad).
- Elegir EL PRODUCTO MÁS PARECIDO de la lista de candidatos.

CRITERIOS:
- Compara por descripción, medida, uso, tipo de producto y marca cuando sea relevante.
- Si ninguno es razonablemente parecido, devuelve product_id = null y match_score = 0.

FORMATO DE RESPUESTA:
Devuelve EXCLUSIVAMENTE un JSON con esta forma:

{
  "product_id": 123,
  "match_score": 95,
  "motivo_seleccion": "Explicación breve en español"
}

Reglas:
- match_score es un entero de 0 a 100.
- Si no hay buena coincidencia, product_id = null, match_score = 0 y explica por qué.
- No escribas nada fuera del JSON.
SYS;

        $user = <<<USER
Producto solicitado en la licitación (texto original):

{$requestItem->line_raw}

Lista de productos candidatos del catálogo:

{$listText}

Recuerda: responde SOLO el JSON con product_id, match_score y motivo_seleccion.
USER;

        $response = $this->client->chatJson([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user',   'content' => $user],
        ]);

        $productId = $response['product_id']       ?? null;
        $match     = $response['match_score']      ?? null;
        $motivo    = $response['motivo_seleccion'] ?? null;

        return [
            'product_id'       => is_numeric($productId) ? (int) $productId : null,
            'match_score'      => is_numeric($match) ? (int) $match : null,
            'motivo_seleccion' => is_string($motivo) ? $motivo : null,
        ];
    }
}
