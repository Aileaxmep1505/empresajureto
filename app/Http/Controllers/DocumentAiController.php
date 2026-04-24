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
    public function start(Request $request): JsonResponse
    {
        Log::info('DocumentAiController@start - inicio', [
            'has_file' => $request->hasFile('file'),
            'licitacion_pdf_id' => $request->input('licitacion_pdf_id'),
            'pages_per_chunk' => $request->input('pages_per_chunk'),
        ]);

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:51200'],
            'licitacion_pdf_id' => ['required', 'integer'],
            'pages_per_chunk' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $pagesPerChunk = (int) ($request->input('pages_per_chunk', 5));
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

        try {
            $pythonBin = config('services.python_ai.bin');
            $pythonScript = config('services.python_ai.script');

            if (!$pythonBin || !file_exists($pythonBin)) {
                throw new \RuntimeException('No existe PYTHON_BIN: ' . $pythonBin);
            }

            if (!$pythonScript || !file_exists($pythonScript)) {
                throw new \RuntimeException('No existe PYTHON_SCRIPT: ' . $pythonScript);
            }

            if (!file_exists($fullPdfPath)) {
                throw new \RuntimeException('No existe el PDF: ' . $fullPdfPath);
            }

            $pythonProjectPath = dirname(dirname($pythonScript));

            Log::info('DocumentAiController@start - ejecutando python', [
                'run_id' => $run->id,
                'python_bin' => $pythonBin,
                'python_script' => $pythonScript,
                'python_project_path' => $pythonProjectPath,
                'pdf' => $fullPdfPath,
            ]);

            $process = new Process([
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
            ], $pythonProjectPath);

            $process->setEnv([
                'PYTHONPATH' => $pythonProjectPath,
                'HOME' => getenv('HOME') ?: '/home/u106036310',
            ]);

            $process->setTimeout(1200);
            $process->setIdleTimeout(1200);
            $process->run();

            $stdout = trim($process->getOutput());
            $stderr = trim($process->getErrorOutput());

            Log::info('DocumentAiController@start - python terminado', [
                'run_id' => $run->id,
                'successful' => $process->isSuccessful(),
                'exit_code' => $process->getExitCode(),
                'stdout_length' => strlen($stdout),
                'stdout_preview' => mb_substr($stdout, 0, 1000),
                'stderr' => $stderr,
            ]);

            if (!$process->isSuccessful()) {
                throw new \RuntimeException(
                    'Python falló. Exit code: ' . $process->getExitCode() . ' Error: ' . $stderr
                );
            }

            $decoded = json_decode($stdout, true);

            if (!is_array($decoded)) {
                Log::warning('DocumentAiController@start - stdout no es JSON válido', [
                    'run_id' => $run->id,
                    'stdout_preview' => mb_substr($stdout, 0, 2000),
                    'stderr' => $stderr,
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

            $run->refresh();

            return response()->json([
                'ok' => true,
                'document_ai_run_id' => $run->id,
                'status' => $run->status,
                'path' => $path,
                'run' => [
                    'id' => $run->id,
                    'status' => $run->status,
                    'filename' => $run->filename,
                    'pages_per_chunk' => $run->pages_per_chunk,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('DocumentAiController@start - error', [
                'run_id' => $run->id ?? null,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            if (isset($run)) {
                $run->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'ok' => false,
                'message' => 'Error procesando documento: ' . $e->getMessage(),
                'document_ai_run_id' => $run->id ?? null,
            ], 500);
        }
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
}