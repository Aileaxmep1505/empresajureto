<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\EmbeddingService;
use Illuminate\Console\Command;

class GenerateProductEmbeddings extends Command
{
    protected $signature   = 'products:embed
                                {--force : Regenerar embeddings aunque ya existan}
                                {--chunk=100 : Productos por lote}';

    protected $description = 'Genera embeddings semánticos para todos los productos del catálogo';

    public function handle(EmbeddingService $embeddingService): int
    {
        $force = $this->option('force');
        $chunk = (int) $this->option('chunk');

        $query = Product::query();

        if (! $force) {
            $query->whereNull('embedding');
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info('Todos los productos ya tienen embedding. Usa --force para regenerar.');
            return self::SUCCESS;
        }

        $this->info("Generando embeddings para {$total} productos...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $errors = 0;

        $query->chunkById($chunk, function ($products) use ($embeddingService, $bar, &$errors) {
            foreach ($products as $product) {
                try {
                    $text = $embeddingService->buildProductText($product);
                    $vector = $embeddingService->embed($text);

                    $product->update([
                        'embedding'            => $vector,
                        'embedding_updated_at' => now(),
                    ]);
                } catch (\Throwable $e) {
                    $errors++;
                    $this->newLine();
                    $this->warn("Error en producto #{$product->id}: " . $e->getMessage());
                }

                $bar->advance();
                usleep(50000); // 50ms entre requests para no saturar la API
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Embeddings generados. Errores: {$errors}");

        return self::SUCCESS;
    }
}