<?php

namespace App\Jobs;

use App\Models\DocumentAiRun;
use App\Services\PythonAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PollDocumentAiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 20;

    public function __construct(public int $documentAiRunId)
    {
    }

    public function handle(PythonAiService $pythonAi): void
    {
        $run = DocumentAiRun::findOrFail($this->documentAiRunId);

        $statusPayload = $pythonAi->getJobStatus($run->python_job_id);
        $status = $statusPayload['status'] ?? 'queued';

        if (in_array($status, ['queued', 'processing'], true)) {
            $run->update([
                'status' => $status,
            ]);

            self::dispatch($run->id)->delay(now()->addSeconds(10));
            return;
        }

        if ($status === 'failed') {
            $run->update([
                'status' => 'failed',
                'error' => $statusPayload['error'] ?? 'Python AI job failed',
            ]);
            return;
        }

        if ($status === 'completed') {
            $resultPayload = $pythonAi->getJobResult($run->python_job_id);

            $structuredJson = null;
            $structuredError = null;
            $itemsJson = null;
            $itemsError = null;

            try {
                $structuredPayload = $pythonAi->getStructuredResult($run->python_job_id);
                $structuredJson = $structuredPayload['structured'] ?? null;
            } catch (\Throwable $e) {
                $structuredError = $e->getMessage();
            }

            try {
                $itemsPayload = $pythonAi->getItemsResult($run->python_job_id);
                $itemsJson = $itemsPayload['items_result'] ?? null;
            } catch (\Throwable $e) {
                $itemsError = $e->getMessage();
            }

            $finalError = null;

            if ($structuredError && $itemsError) {
                $finalError = $structuredError . ' | ' . $itemsError;
            } elseif ($structuredError) {
                $finalError = $structuredError;
            } elseif ($itemsError) {
                $finalError = $itemsError;
            }

            $run->update([
                'status' => 'completed',
                'result_json' => $resultPayload,
                'structured_json' => $structuredJson,
                'items_json' => $itemsJson,
                'error' => $finalError,
            ]);
        }
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