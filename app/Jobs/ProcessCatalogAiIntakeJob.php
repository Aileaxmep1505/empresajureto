<?php

namespace App\Jobs;

use App\Models\CatalogAiIntake;
use App\Services\CatalogAiExtractService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCatalogAiIntakeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180; // 3 min

    public function __construct(public int $intakeId) {}

    public function handle(CatalogAiExtractService $svc): void
    {
        $intake = CatalogAiIntake::with('files')->find($this->intakeId);
        if (!$intake) return;

        $intake->status = 2; // processing
        $intake->save();

        try {
            $paths = $intake->files
                ->sortBy('page_no')
                ->map(fn($f) => storage_path("app/{$f->disk}/{$f->path}"))
                ->all();

            if (!count($paths)) {
                throw new \RuntimeException("No hay imágenes para procesar.");
            }

            $json = $svc->extractFromImages($paths);

            $intake->extracted = $json;
            $intake->status = 3; // ready
            $intake->processed_at = now();
            $intake->save();

        } catch (\Throwable $e) {
            Log::error("AI intake failed #{$intake->id}: ".$e->getMessage());

            $intake->status = 9; // failed
            $intake->meta = array_merge($intake->meta ?? [], [
                'error' => $e->getMessage(),
            ]);
            $intake->save();
        }
    }
}
