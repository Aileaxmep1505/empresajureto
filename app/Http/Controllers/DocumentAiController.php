<?php

namespace App\Http\Controllers;

use App\Jobs\DispatchDocumentAiJob;
use App\Models\DocumentAiRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        $run = DocumentAiRun::create([
            'licitacion_pdf_id' => (int) $request->input('licitacion_pdf_id'),
            'python_job_id' => 'pending-' . uniqid(),
            'filename' => $request->file('file')->getClientOriginalName(),
            'pages_per_chunk' => $pagesPerChunk,
            'status' => 'queued',
            'error' => null,
            'result_json' => null,
            'structured_json' => null,
            'items_json' => null,
        ]);

        Log::info('DocumentAiController@start - run creado', [
            'run_id' => $run->id,
            'path' => $path,
            'filename' => $run->filename,
        ]);

        DispatchDocumentAiJob::dispatch(
            documentAiRunId: $run->id,
            storagePath: $path,
            filename: $run->filename,
            pagesPerChunk: $pagesPerChunk
        );

        return response()->json([
            'ok' => true,
            'document_ai_run_id' => $run->id,
            'status' => 'queued',
            'path' => $path,
        ]);
    }

    public function show(DocumentAiRun $run): JsonResponse
    {
        try {
            Log::info('DocumentAiController@show - inicio', [
                'run_id' => $run->id,
                'status' => $run->status,
                'has_result_json' => !empty($run->result_json),
                'has_structured_json' => !empty($run->structured_json),
                'has_items_json' => !empty($run->items_json),
            ]);

            $payload = [
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
            ];

            Log::info('DocumentAiController@show - respuesta lista', [
                'run_id' => $run->id,
                'items_count' => is_array($run->items_json) ? count(($run->items_json['items'] ?? [])) : 0,
            ]);

            return response()->json($payload);
        } catch (Throwable $e) {
            Log::error('DocumentAiController@show - error', [
                'run_id' => $run->id ?? null,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Error devolviendo el run: ' . $e->getMessage(),
            ], 500);
        }
    }
}