<?php

namespace App\Http\Controllers;

use App\Models\DocumentAiRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

class DocumentAiController extends Controller
{
    public function start(Request $request)
    {
        Log::info('DocumentAiController@start - inicio', [
            'has_file' => $request->hasFile('file'),
            'licitacion_pdf_id' => $request->input('licitacion_pdf_id'),
            'pages_per_chunk' => $request->input('pages_per_chunk'),
        ]);

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:51200'],
            'licitacion_pdf_id' => ['required', 'integer'],
            'pages_per_chunk' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $defaultPagesPerChunk = (int) env('AZURE_PAGES_PER_CHUNK', 20);
        $pagesPerChunk = (int) ($request->input('pages_per_chunk') ?: $defaultPagesPerChunk);
        $pagesPerChunk = max(1, min($pagesPerChunk, 30));

        $path = $request->file('file')->store('licitaciones/ai', 'public');
        $fullPdfPath = storage_path('app/public/' . $path);

        $run = DocumentAiRun::create([
            'licitacion_pdf_id' => (int) $request->input('licitacion_pdf_id'),
            'python_job_id' => 'local-' . uniqid(),
            'filename' => $request->file('file')->getClientOriginalName(),
            'pages_per_chunk' => $pagesPerChunk,
            'status' => 'processing',
            'error' => null,
            'result_json' => null,
            'structured_json' => null,
            'items_json' => null,
        ]);

        $pythonBin = config('services.python_ai.bin');
        $pythonScript = config('services.python_ai.script');

        $configError = null;

        if (!$pythonBin || !file_exists($pythonBin)) {
            $configError = 'No existe PYTHON_BIN: ' . $pythonBin;
        } elseif (!$pythonScript || !file_exists($pythonScript)) {
            $configError = 'No existe PYTHON_SCRIPT: ' . $pythonScript;
        } elseif (!file_exists($fullPdfPath)) {
            $configError = 'No existe el PDF: ' . $fullPdfPath;
        }

        if ($configError) {
            Log::error('DocumentAiController@start - error de configuración', [
                'run_id' => $run->id,
                'message' => $configError,
            ]);

            $run->update(['status' => 'failed', 'error' => $configError]);

            return response()->json([
                'ok' => false,
                'message' => 'Error procesando documento: ' . $configError,
                'document_ai_run_id' => $run->id,
            ], 500);
        }

        $progressPath = storage_path('app/ai_progress/' . $run->id . '.json');
        @mkdir(dirname($progressPath), 0775, true);
        @file_put_contents($progressPath, json_encode([
            'pct' => 3,
            'etapa' => 'En cola',
            'detalle' => 'Preparando análisis rápido...',
        ], JSON_UNESCAPED_UNICODE));

        $response = response()->json([
            'ok' => true,
            'document_ai_run_id' => $run->id,
            'status' => 'processing',
            'path' => $path,
            'run' => [
                'id' => $run->id,
                'status' => 'processing',
                'filename' => $run->filename,
                'pages_per_chunk' => $run->pages_per_chunk,
            ],
        ]);

        $response->send();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        @ignore_user_abort(true);

        $processTimeout = (int) env('PYTHON_AI_TIMEOUT', 900);
        $processTimeout = max(300, min($processTimeout, 1800));
        @set_time_limit($processTimeout + 60);

        try {
            Log::info('DocumentAiController@start - ejecutando python (background)', [
                'run_id' => $run->id,
                'python_bin' => $pythonBin,
                'python_script' => $pythonScript,
                'pdf' => $fullPdfPath,
                'progress' => $progressPath,
                'timeout' => $processTimeout,
                'pages_per_chunk' => $pagesPerChunk,
            ]);

            $pythonRoot = dirname($pythonScript, 2);

            $childEnv = array_merge($_ENV, $_SERVER, [
                'AI_PROGRESS_FILE' => $progressPath,
                'PATH' => getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin',
                'HOME' => getenv('HOME') ?: dirname($pythonBin, 2),
                'LANG' => getenv('LANG') ?: 'en_US.UTF-8',
                'LC_ALL' => getenv('LC_ALL') ?: 'en_US.UTF-8',
                'PYTHONUNBUFFERED' => '1',
                'PYTHONIOENCODING' => 'utf-8',

                // Ajustes rápidos. Si ya los tienes en .env, estos defaults se pueden cambiar ahí.
                'AZURE_PAGES_PER_CHUNK' => (string) $pagesPerChunk,
                'AZURE_FORCE_SPLIT_PAGES' => (string) env('AZURE_FORCE_SPLIT_PAGES', 35),
                'AZURE_MAX_WORKERS' => (string) env('AZURE_MAX_WORKERS', 5),
                'AZURE_CACHE_ENABLED' => (string) env('AZURE_CACHE_ENABLED', 1),

                // La estructura general se hace local para evitar otra llamada grande a OpenAI.
                'OPENAI_STRUCTURE_WITH_AI' => (string) env('OPENAI_STRUCTURE_WITH_AI', 0),

                // Corrección de partidas: bloques más grandes + menos llamadas.
                'OPENAI_CORRECT_ITEMS' => (string) env('OPENAI_CORRECT_ITEMS', 1),
                'OPENAI_CORRECT_CHUNK_ITEMS' => (string) env('OPENAI_CORRECT_CHUNK_ITEMS', 120),
                'OPENAI_CORRECT_MAX_WORKERS' => (string) env('OPENAI_CORRECT_MAX_WORKERS', 4),
                'OPENAI_CORRECT_TIMEOUT' => (string) env('OPENAI_CORRECT_TIMEOUT', 45),
                'OPENAI_MAX_CANDIDATES_FOR_AI' => (string) env('OPENAI_MAX_CANDIDATES_FOR_AI', 240),
                'OPENAI_REASONING_EFFORT' => (string) env('OPENAI_REASONING_EFFORT', 'low'),
            ]);

            $process = new Process(
                [
                    $pythonBin,
                    $pythonScript,
                    '--file',
                    $fullPdfPath,
                    '--run-id',
                    (string) $run->id,
                    '--pages-per-chunk',
                    (string) $pagesPerChunk,
                    '--filename',
                    $run->filename,
                    '--progress-file',
                    $progressPath,
                ],
                is_dir($pythonRoot) ? $pythonRoot : null,
                $childEnv
            );

            $process->setTimeout($processTimeout);
            $process->setIdleTimeout($processTimeout);
            $process->run();

            $stdout = trim($process->getOutput());
            $stderr = trim($process->getErrorOutput());

            Log::info('DocumentAiController@start - python terminado', [
                'run_id' => $run->id,
                'successful' => $process->isSuccessful(),
                'exit_code' => $process->getExitCode(),
                'stdout_length' => strlen($stdout),
                'stderr' => mb_substr($stderr, 0, 4000),
            ]);

            if (!$process->isSuccessful()) {
                throw new \RuntimeException(
                    'Python falló. Exit code: ' . $process->getExitCode() . ' Error: ' . $stderr
                );
            }

            $decoded = json_decode($stdout, true);

            if (!is_array($decoded)) {
                Log::warning('DocumentAiController@start - stdout no es JSON puro', [
                    'run_id' => $run->id,
                    'stdout_preview' => mb_substr($stdout, 0, 1000),
                ]);

                throw new \RuntimeException('Python no devolvió JSON válido.');
            }

            $run->update([
                'status' => $decoded['status'] ?? 'completed',
                'error' => null,
                'result_json' => $decoded['result_json'] ?? $decoded['result'] ?? $decoded,
                'structured_json' => $decoded['structured_json'] ?? $decoded['structured'] ?? null,
                'items_json' => $decoded['items_json'] ?? $decoded['items'] ?? null,
            ]);

            @file_put_contents($progressPath, json_encode([
                'pct' => 100,
                'etapa' => 'Análisis completado',
                'detalle' => 'Generando cotización...',
            ], JSON_UNESCAPED_UNICODE));
        } catch (Throwable $e) {
            Log::error('DocumentAiController@start - error (background)', [
                'run_id' => $run->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $run->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            @file_put_contents($progressPath, json_encode([
                'pct' => 100,
                'etapa' => 'Error',
                'detalle' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE));
        }

        exit;
    }

    public function show(DocumentAiRun $run): JsonResponse
    {
        try {
            return response()->json([
                'ok' => true,
                'run' => [
                    'id' => $run->id,
                    'licitacion_pdf_id' => $run->licitacion_pdf_id,
                    'python_job_id' => $run->python_job_id,
                    'filename' => $run->filename,
                    'pages_per_chunk' => $run->pages_per_chunk,
                    'status' => $run->status,
                    'error' => $run->error,
                    'progress' => $this->leerProgreso($run->id),
                    'result_json' => $run->result_json,
                    'structured_json' => $run->structured_json,
                    'items_json' => $run->items_json,
                    'created_at' => optional($run->created_at)?->toDateTimeString(),
                    'updated_at' => optional($run->updated_at)?->toDateTimeString(),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('DocumentAiController@show - error', [
                'run_id' => $run->id ?? null,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Error devolviendo el run: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function leerProgreso($runId): ?array
    {
        $path = storage_path('app/ai_progress/' . $runId . '.json');

        if (!is_file($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : null;
    }
}
