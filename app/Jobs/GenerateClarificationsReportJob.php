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

    /**
     * El documento se genera por bloques; se permite hasta 30 minutos.
     */
    public int $timeout = 1800;
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
        $current = Cache::get($this->cacheKey, []);

        Cache::put($this->cacheKey, array_merge(
            is_array($current) ? $current : [],
            [
                'status' => 'processing',
                'message' => 'El trabajador inició el análisis progresivo...',
                'progress' => max(12, (int) ($current['progress'] ?? 0)),
                'report_type' => 'clarifications',
                'report_title' => $this->title,
                'template_name' => $this->options['template_filename'] ?? null,
            ]
        ), now()->addHours(3));

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
        $message = $exception?->getMessage()
            ?: 'La generación fue detenida por tiempo límite o por un error interno.';

        Log::error('GenerateClarificationsReportJob failed', [
            'project_id' => $this->projectId,
            'error' => $message,
        ]);

        $current = Cache::get($this->cacheKey, []);

        Cache::put($this->cacheKey, [
            'status' => 'failed',
            'message' => $message,
            'progress' => (int) ($current['progress'] ?? 100),
            'html' => $current['html'] ?? null,
            'report_type' => 'clarifications',
            'report_title' => $this->title,
            'completed_sections' => $current['completed_sections'] ?? [],
            'template_name' => $this->options['template_filename'] ?? null,
        ], now()->addHours(3));
    }
}
