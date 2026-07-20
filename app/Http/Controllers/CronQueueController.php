<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CronQueueController extends Controller
{
    /**
     * Ejecuta un trabajo pendiente de las colas reports o default.
     *
     * URL:
     * /cron/queue/run?token=TU_TOKEN
     */
    public function run(Request $request): JsonResponse
    {
        $this->validateCronToken($request);

        /*
        |--------------------------------------------------------------------------
        | Bloqueo contra ejecuciones simultáneas
        |--------------------------------------------------------------------------
        | El trabajo de Junta de Aclaraciones puede tardar varios minutos.
        | Este bloqueo evita que cron-job.org levante otro worker mientras
        | el anterior sigue activo.
        */
        $lockSeconds = 1900;
        $lock = Cache::lock('cron-queue-worker-lock', $lockSeconds);

        if (!$lock->get()) {
            Log::info('Cron externo: ejecución omitida porque ya existe un worker activo.');

            return response()->json([
                'ok' => true,
                'status' => 'busy',
                'message' => 'Ya existe un trabajador procesando la cola.',
                'queue_connection' => config('queue.default'),
                'queues' => ['reports', 'default'],
                'timestamp' => now()->toDateTimeString(),
            ]);
        }

        try {
            /*
            |--------------------------------------------------------------------------
            | Permitir procesos largos
            |--------------------------------------------------------------------------
            */
            @set_time_limit(1850);
            @ini_set('max_execution_time', '1850');
            ignore_user_abort(true);

            $pendingBefore = $this->pendingJobsCount();
            $failedBefore = $this->failedJobsCount();

            Log::info('Cron externo: iniciando worker de cola.', [
                'connection' => config('queue.default'),
                'queues' => 'reports,default',
                'pending_before' => $pendingBefore,
                'failed_before' => $failedBefore,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Procesar un único Job
            |--------------------------------------------------------------------------
            | La Junta de Aclaraciones se envía a la cola "reports".
            | Por eso reports debe ir antes que default.
            */
            $exitCode = Artisan::call('queue:work', [
                'connection' => config('queue.default'),
                '--queue' => 'reports,default',
                '--once' => true,
                '--tries' => 1,
                '--timeout' => 1800,
                '--sleep' => 1,
                '--memory' => 512,
                '--no-interaction' => true,
            ]);

            $output = trim((string) Artisan::output());

            $pendingAfter = $this->pendingJobsCount();
            $failedAfter = $this->failedJobsCount();

            Log::info('Cron externo: worker finalizado.', [
                'exit_code' => $exitCode,
                'output' => $output,
                'pending_after' => $pendingAfter,
                'failed_after' => $failedAfter,
            ]);

            return response()->json([
                'ok' => $exitCode === 0,
                'status' => $exitCode === 0 ? 'completed' : 'error',
                'exit_code' => $exitCode,
                'message' => $exitCode === 0
                    ? 'La cola fue revisada correctamente.'
                    : 'El worker terminó con un código de error.',
                'queue_connection' => config('queue.default'),
                'queues' => ['reports', 'default'],
                'pending_before' => $pendingBefore,
                'pending_after' => $pendingAfter,
                'failed_before' => $failedBefore,
                'failed_after' => $failedAfter,
                'output' => $output !== '' ? $output : null,
                'timestamp' => now()->toDateTimeString(),
            ], $exitCode === 0 ? 200 : 500);
        } catch (Throwable $exception) {
            Log::error('Cron externo: error procesando la cola.', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json([
                'ok' => false,
                'status' => 'exception',
                'message' => $exception->getMessage(),
                'queue_connection' => config('queue.default'),
                'queues' => ['reports', 'default'],
                'timestamp' => now()->toDateTimeString(),
            ], 500);
        } finally {
            try {
                $lock->release();
            } catch (Throwable $exception) {
                Log::warning('Cron externo: no fue posible liberar el lock.', [
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * Comprueba que la ruta y el token funcionan.
     *
     * URL:
     * /cron/queue/health?token=TU_TOKEN
     */
    public function health(Request $request): JsonResponse
    {
        $this->validateCronToken($request);

        return response()->json([
            'ok' => true,
            'status' => 'healthy',
            'queue_connection' => config('queue.default'),
            'cache_store' => config('cache.default'),
            'queues' => ['reports', 'default'],
            'pending_jobs' => $this->pendingJobsCount(),
            'failed_jobs' => $this->failedJobsCount(),
            'worker_locked' => Cache::has('cron-queue-worker-lock'),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Devuelve información de los trabajos pendientes.
     *
     * URL:
     * /cron/queue/status?token=TU_TOKEN
     */
    public function status(Request $request): JsonResponse
    {
        $this->validateCronToken($request);

        $jobs = [];

        try {
            if (config('queue.default') === 'database') {
                $jobs = DB::table('jobs')
                    ->select([
                        'id',
                        'queue',
                        'attempts',
                        'reserved_at',
                        'available_at',
                        'created_at',
                    ])
                    ->orderBy('id')
                    ->limit(20)
                    ->get()
                    ->map(function ($job) {
                        return [
                            'id' => $job->id,
                            'queue' => $job->queue,
                            'attempts' => $job->attempts,
                            'reserved_at' => $job->reserved_at,
                            'available_at' => $job->available_at,
                            'created_at' => $job->created_at,
                        ];
                    })
                    ->values()
                    ->all();
            }
        } catch (Throwable $exception) {
            Log::warning('Cron externo: no fue posible consultar la tabla jobs.', [
                'message' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'ok' => true,
            'queue_connection' => config('queue.default'),
            'cache_store' => config('cache.default'),
            'queues' => ['reports', 'default'],
            'pending_jobs' => $this->pendingJobsCount(),
            'failed_jobs' => $this->failedJobsCount(),
            'jobs' => $jobs,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Valida el token configurado en services.php.
     */
    private function validateCronToken(Request $request): void
    {
        $configuredToken = trim((string) config('services.cron_queue.token'));
        $receivedToken = trim((string) $request->query('token'));

        if (
            $configuredToken === ''
            || $receivedToken === ''
            || !hash_equals($configuredToken, $receivedToken)
        ) {
            abort(403, 'Token de cron inválido.');
        }
    }

    /**
     * Cuenta trabajos pendientes cuando la conexión es database.
     */
    private function pendingJobsCount(): ?int
    {
        try {
            if (config('queue.default') !== 'database') {
                return null;
            }

            return DB::table('jobs')->count();
        } catch (Throwable $exception) {
            Log::warning('Cron externo: no fue posible contar trabajos pendientes.', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Cuenta trabajos fallidos.
     */
    private function failedJobsCount(): ?int
    {
        try {
            return DB::table('failed_jobs')->count();
        } catch (Throwable $exception) {
            return null;
        }
    }
}