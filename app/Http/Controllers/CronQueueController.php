<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class CronQueueController extends Controller
{
    public function run(Request $request): JsonResponse
    {
        $configuredToken = (string) config('services.cron_queue.token');
        $receivedToken = (string) $request->query('token');

        if (
            $configuredToken === ''
            || $receivedToken === ''
            || !hash_equals($configuredToken, $receivedToken)
        ) {
            abort(403, 'Token de cron inválido.');
        }

        /*
        |--------------------------------------------------------------------------
        | Evita ejecuciones simultáneas
        |--------------------------------------------------------------------------
        | Si cron-job.org llama otra vez mientras un análisis continúa activo,
        | la segunda petición no inicia otro worker.
        */
        $lock = Cache::lock('cron-queue-worker-lock', 700);

        if (!$lock->get()) {
            return response()->json([
                'ok' => true,
                'status' => 'busy',
                'message' => 'Ya existe un trabajador procesando la cola.',
            ]);
        }

        try {
            set_time_limit(650);
            ignore_user_abort(true);

            Log::info('Cron externo: iniciando worker de cola.');

            /*
            |--------------------------------------------------------------------------
            | Procesa un solo Job
            |--------------------------------------------------------------------------
            | Es más seguro para una llamada HTTP externa que mantener un worker
            | permanente abierto.
            */
            $exitCode = Artisan::call('queue:work', [
                'connection' => config('queue.default'),
                '--queue' => 'default',
                '--once' => true,
                '--tries' => 1,
                '--timeout' => 600,
                '--sleep' => 1,
                '--no-interaction' => true,
            ]);

            $output = trim(Artisan::output());

            Log::info('Cron externo: worker finalizado.', [
                'exit_code' => $exitCode,
                'output' => $output,
            ]);

            return response()->json([
                'ok' => $exitCode === 0,
                'status' => $exitCode === 0 ? 'completed' : 'error',
                'exit_code' => $exitCode,
                'message' => $exitCode === 0
                    ? 'La cola fue revisada correctamente.'
                    : 'El worker terminó con un código de error.',
            ], $exitCode === 0 ? 200 : 500);
        } catch (Throwable $exception) {
            Log::error('Cron externo: error procesando la cola.', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return response()->json([
                'ok' => false,
                'status' => 'exception',
                'message' => $exception->getMessage(),
            ], 500);
        } finally {
            optional($lock)->release();
        }
    }

    public function health(Request $request): JsonResponse
    {
        $configuredToken = (string) config('services.cron_queue.token');
        $receivedToken = (string) $request->query('token');

        if (
            $configuredToken === ''
            || $receivedToken === ''
            || !hash_equals($configuredToken, $receivedToken)
        ) {
            abort(403, 'Token de cron inválido.');
        }

        return response()->json([
            'ok' => true,
            'queue_connection' => config('queue.default'),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}