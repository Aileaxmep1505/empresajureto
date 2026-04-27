<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de embeddings semánticos usando OpenAI text-embedding-3-large.
 *
 * Un embedding convierte texto en un vector numérico de alta dimensión.
 * Dos textos similares en significado tendrán vectores cercanos en el espacio.
 * Esto permite buscar productos por significado, no por palabras exactas.
 */
class EmbeddingService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;

    public function __construct()
    {
        $this->apiKey  = config('services.openai.api_key');
        $this->baseUrl = rtrim(config('services.openai.base_url', 'https://api.openai.com'), '/');
        $this->model   = config('services.openai.embed_model', 'text-embedding-3-large');
    }

    // =========================================================
    //  GENERAR EMBEDDING
    // =========================================================

    /**
     * Convierte un texto en vector numérico (embedding).
     * Llama a la API de OpenAI una vez por texto.
     */
    public function embed(string $text): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])
        ->timeout(30)
        ->post($this->baseUrl . '/v1/embeddings', [
            'model' => $this->model,
            'input' => $text,
        ]);

        if (! $response->successful()) {
            Log::error('[EmbeddingService] Error API', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('OpenAI Embeddings HTTP ' . $response->status());
        }

        $vector = $response->json('data.0.embedding');

        if (! is_array($vector) || empty($vector)) {
            throw new \RuntimeException('Embedding vacío recibido de OpenAI');
        }

        return $vector;
    }

    /**
     * Genera embeddings para múltiples textos en una sola llamada API.
     * Más eficiente que llamar embed() en bucle.
     * Máximo 2048 textos por llamada (límite OpenAI).
     */
    public function embedBatch(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])
        ->timeout(60)
        ->post($this->baseUrl . '/v1/embeddings', [
            'model' => $this->model,
            'input' => array_values($texts),
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI Embeddings batch HTTP ' . $response->status());
        }

        // Retorna en el mismo orden que el input
        return collect($response->json('data'))
            ->sortBy('index')
            ->pluck('embedding')
            ->all();
    }

    // =========================================================
    //  BÚSQUEDA POR SIMILITUD
    // =========================================================

    /**
     * Dado un embedding de consulta, encuentra los N productos más similares
     * en el catálogo comparando por similitud coseno.
     *
     * Carga los productos con embedding desde la DB y calcula la similitud
     * en PHP. Para catálogos grandes (>50k productos), considera pgvector.
     *
     * @param  array $queryVector   Embedding del ítem solicitado
     * @param  int   $topN          Cuántos candidatos retornar
     * @return \Illuminate\Support\Collection  Productos ordenados por similitud
     */
    public function findSimilarProducts(array $queryVector, int $topN = 20)
    {
        // Carga todos los productos con embedding generado
        // Solo los campos necesarios para el score (el resto lo carga la IA)
        $products = Product::whereNotNull('embedding')
            ->select(['id', 'name', 'sku', 'brand', 'category', 'tags',
                      'description', 'unit', 'material', 'color', 'embedding'])
            ->get();

        if ($products->isEmpty()) {
            return collect();
        }

        // Calcular similitud coseno con cada producto
        $results = $products
            ->map(function ($product) use ($queryVector) {
                $productVector = is_array($product->embedding)
                    ? $product->embedding
                    : json_decode($product->embedding, true);

                if (empty($productVector)) {
                    return null;
                }

                return [
                    'product'    => $product,
                    'similarity' => $this->cosineSimilarity($queryVector, $productVector),
                ];
            })
            ->filter()
            ->sortByDesc('similarity')
            ->take($topN)
            ->values();

        return $results;
    }

    // =========================================================
    //  TEXTO DEL PRODUCTO PARA EMBEDDING
    // =========================================================

    /**
     * Construye el texto que representa un producto para el embedding.
     * Incluye TODOS los campos relevantes para que el vector sea rico en información.
     */
    public function buildProductText(Product $product): string
    {
        $parts = array_filter([
            $product->name        ? "Producto: {$product->name}"             : null,
            $product->category    ? "Categoría: {$product->category}"        : null,
            $product->brand       ? "Marca: {$product->brand}"               : null,
            $product->tags        ? "Etiquetas: {$product->tags}"            : null,
            $product->description ? "Descripción: {$product->description}"   : null,
            $product->material    ? "Material: {$product->material}"         : null,
            $product->color       ? "Color: {$product->color}"               : null,
            $product->unit        ? "Unidad: {$product->unit}"               : null,
            $product->sku         ? "SKU: {$product->sku}"                   : null,
        ]);

        return implode('. ', $parts);
    }

    /**
     * Construye el texto de búsqueda para un ítem de licitación.
     * Agrega contexto para que el embedding busque como cotizador.
     */
    public function buildQueryText(string $descripcion, string $unidad): string
    {
        $text = "Artículo de licitación: {$descripcion}";
        if ($unidad) {
            $text .= ". Unidad: {$unidad}";
        }
        return $text;
    }

    // =========================================================
    //  MATEMÁTICAS DEL EMBEDDING
    // =========================================================

    /**
     * Similitud coseno entre dos vectores.
     * Retorna valor entre -1 (opuestos) y 1 (idénticos).
     * En la práctica, dos productos similares dan 0.80+.
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            return 0.0;
        }

        $dot      = 0.0;
        $normA    = 0.0;
        $normB    = 0.0;

        for ($i = 0, $len = count($a); $i < $len; $i++) {
            $dot   += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        $denominator = sqrt($normA) * sqrt($normB);

        return $denominator > 0 ? round($dot / $denominator, 6) : 0.0;
    }
}