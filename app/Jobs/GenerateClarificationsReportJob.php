<?php

namespace App\Jobs;

use App\Http\Controllers\Projects\ProjectBoardController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateClarificationsReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Tiempo máximo del Job.
     *
     * Debe ser menor que DB_QUEUE_RETRY_AFTER.
     */
    public int $timeout = 900;

    /**
     * Número máximo de intentos.
     */
    public int $tries = 3;

    /**
     * Marca el Job como fallido cuando supera el timeout.
     */
    public bool $failOnTimeout = true;

    /**
     * Espera entre reintentos.
     */
    public array $backoff = [30, 90, 180];

    public function __construct(
        public int $projectId,
        public ?int $userId,
        public string $title,
        public array $options,
        public string $cacheKey,
    ) {
        $this->onQueue('reports');
    }

    /**
     * Ejecuta la generación del reporte.
     */
    public function handle(ProjectBoardController $controller): void
    {
        Log::info('GenerateClarificationsReportJob iniciado.', [
            'project_id' => $this->projectId,
            'user_id' => $this->userId,
            'cache_key' => $this->cacheKey,
            'attempt' => $this->attempts(),
            'queue' => 'reports',
        ]);

        Cache::put($this->cacheKey, [
            'status' => 'processing',
            'message' => 'El trabajador inició el análisis de bases y documentos...',
            'progress' => 20,
            'html' => $this->options['initial_html'] ?? null,
            'template_name' => $this->options['template_filename'] ?? null,
            'attempt' => $this->attempts(),
            'updated_at' => now()->toDateTimeString(),
        ], now()->addHours(2));

        try {
            $controller->processClarificationsReportAsync(
                $this->projectId,
                $this->userId,
                $this->title,
                $this->options,
                $this->cacheKey
            );

            Log::info('GenerateClarificationsReportJob completado.', [
                'project_id' => $this->projectId,
                'cache_key' => $this->cacheKey,
                'attempt' => $this->attempts(),
            ]);
        } catch (Throwable $exception) {
            Log::error('GenerateClarificationsReportJob encontró un error.', [
                'project_id' => $this->projectId,
                'cache_key' => $this->cacheKey,
                'attempt' => $this->attempts(),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            Cache::put($this->cacheKey, [
                'status' => 'retrying',
                'message' => 'Ocurrió un error temporal. Se intentará nuevamente.',
                'progress' => 20,
                'html' => $this->options['initial_html'] ?? null,
                'template_name' => $this->options['template_filename'] ?? null,
                'attempt' => $this->attempts(),
                'error' => $exception->getMessage(),
                'updated_at' => now()->toDateTimeString(),
            ], now()->addHours(2));

            throw $exception;
        }
    }

    /**
     * Se ejecuta cuando el Job agotó todos sus intentos.
     */
    public function failed(?Throwable $exception): void
    {
        $message = $exception?->getMessage()
            ?: 'La generación fue detenida por tiempo límite o por un error interno.';

        Log::error('GenerateClarificationsReportJob falló definitivamente.', [
            'project_id' => $this->projectId,
            'user_id' => $this->userId,
            'cache_key' => $this->cacheKey,
            'error' => $message,
        ]);

        Cache::put($this->cacheKey, [
            'status' => 'failed',
            'message' => $message,
            'progress' => 100,
            'html' => $this->options['initial_html'] ?? null,
            'template_name' => $this->options['template_filename'] ?? null,
            'updated_at' => now()->toDateTimeString(),
        ], now()->addHours(2));
    }
}