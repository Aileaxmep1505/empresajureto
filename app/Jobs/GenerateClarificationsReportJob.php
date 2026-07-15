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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 480;
    public int $tries = 1;
    public bool $failOnTimeout = true;

    public function __construct(
        public int $projectId,
        public ?int $userId,
        public string $title,
        public array $options,
        public string $cacheKey,
    ) {
        $this->onQueue('reports');
    }

    public function handle(ProjectBoardController $controller): void
    {
        Cache::put($this->cacheKey, [
            'status' => 'processing',
            'message' => 'El trabajador inició el análisis de bases y documentos...',
            'progress' => 20,
            'template_name' => $this->options['template_filename'] ?? null,
        ], now()->addHours(2));

        $controller->processClarificationsReportAsync(
            $this->projectId,
            $this->userId,
            $this->title,
            $this->options,
            $this->cacheKey
        );
    }

    public function failed(?Throwable $exception): void
    {
        $message = $exception?->getMessage() ?: 'La generación fue detenida por tiempo límite o por un error interno.';

        Log::error('GenerateClarificationsReportJob failed', [
            'project_id' => $this->projectId,
            'error' => $message,
        ]);

        Cache::put($this->cacheKey, [
            'status' => 'failed',
            'message' => $message,
            'progress' => 100,
            'template_name' => $this->options['template_filename'] ?? null,
        ], now()->addHours(2));
    }
}
