<?php

namespace App\Jobs;

use App\Models\DocumentAiRun;
use App\Services\PythonAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchDocumentAiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public int $documentAiRunId,
        public string $storagePath,
        public string $filename,
        public int $pagesPerChunk = 5
    ) {
    }

    public function handle(PythonAiService $pythonAi): void
    {
        $run = DocumentAiRun::findOrFail($this->documentAiRunId);

        $payload = $pythonAi->analyzePdfAsync(
            storagePath: $this->storagePath,
            filename: $this->filename,
            pagesPerChunk: $this->pagesPerChunk
        );

        $run->update([
            'python_job_id' => $payload['job_id'],
            'status' => $payload['status'] ?? 'queued',
        ]);

        PollDocumentAiJob::dispatch($run->id)->delay(now()->addSeconds(10));
    }

    public function failed(\Throwable $e): void
    {
        $run = DocumentAiRun::find($this->documentAiRunId);
        if ($run) {
            $run->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }
    }
}