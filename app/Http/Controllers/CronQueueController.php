<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

class CronQueueController extends Controller
{
    /**
     * Comprueba que la ruta, Laravel, la base de datos,
     * la caché y el token funcionan correctamente.
     */
    public function ping(Request $request): JsonResponse
    {
        $this->validateCronToken($request);

        $diagnostic = $this->buildDiagnostic();

        $this->directLog('PING recibido correctamente.', $diagnostic);

        return response()->json([
            'ok' => true,
            'status' => 'ping_ok',
            'message' => 'La ruta del cron está funcionando.',
            'diagnostic' => $diagnostic,
        ]);
    }

    /**
     * Estado general de la cola.
     */
    public function health(Request $request): JsonResponse
    {
        $this->validateCronToken($request);

        $diagnostic = $this->buildDiagnostic();

        $this->directLog('HEALTH consultado.', $diagnostic);

        return response()->json([
            'ok' => true,
            'status' => 'healthy',
            'message' => 'El controlador de la cola está disponible.',
            'diagnostic' => $diagnostic,
        ]);
    }

    /**
     * Muestra los trabajos pendientes y fallidos.
     */
    public function status(Request $request): JsonResponse
    {
        $this->validateCronToken($request);

        return response()->json([
            'ok' => true,
            'status' => 'status_ok',
            'pending_jobs' => $this->pendingJobsCount(),
            'pending_by_queue' => $this->pendingJobsByQueue(),
            'failed_jobs' => $this->failedJobsCount(),
            'jobs' => $this->pendingJobsList(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Ejecuta un solo trabajo de la cola.
     *
     * La cola reports se atiende antes que default.
     */
    public function run(Request $request): JsonResponse
    {
        $this->validateCronToken($request);

        $requestId = uniqid('cron_', true);

        $this->directLog('Solicitud RUN recibida.', [
            'request_id' => $requestId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Evita ejecuciones simultáneas
        |--------------------------------------------------------------------------
        |
        | El bloqueo dura 16 minutos. El Job tiene timeout de 900 segundos
        | y la conexión database tiene retry_after de 1000 segundos.
        |
        */
        $lock = Cache::lock('cron-queue-worker-lock', 960);

        if (!$lock->get()) {
            $this->directLog('Worker omitido: existe otro proceso activo.', [
                'request_id' => $requestId,
            ]);

            return response()->json([
                'ok' => true,
                'status' => 'busy',
                'message' => 'Ya existe un trabajador procesando la cola.',
                'request_id' => $requestId,
                'pending_jobs' => $this->pendingJobsCount(),
                'pending_by_queue' => $this->pendingJobsByQueue(),
                'failed_jobs' => $this->failedJobsCount(),
                'timestamp' => now()->toDateTimeString(),
            ]);
        }

        try {
            /*
            |--------------------------------------------------------------------------
            | Permitir ejecución larga
            |--------------------------------------------------------------------------
            */
            @set_time_limit(940);
            @ini_set('max_execution_time', '940');
            ignore_user_abort(true);

            $pendingBefore = $this->pendingJobsByQueue();
            $failedBefore = $this->failedJobsCount();

            $this->directLog('Worker iniciando.', [
                'request_id' => $requestId,
                'connection' => config('queue.default'),
                'queues' => 'reports,default',
                'pending_before' => $pendingBefore,
                'failed_before' => $failedBefore,
            ]);

            Log::info('Cron queue worker iniciado.', [
                'request_id' => $requestId,
                'queues' => 'reports,default',
                'pending_before' => $pendingBefore,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Ejecutar un solo Job
            |--------------------------------------------------------------------------
            |
            | --tries=3 coincide con GenerateClarificationsReportJob.
            | --timeout=900 es menor que DB_QUEUE_RETRY_AFTER=1000.
            |
            */
            $exitCode = Artisan::call('queue:work', [
                'connection' => config('queue.default'),
                '--queue' => 'reports,default',
                '--once' => true,
                '--tries' => 3,
                '--timeout' => 900,
                '--sleep' => 1,
                '--memory' => 512,
                '--no-interaction' => true,
            ]);

            $artisanOutput = trim((string) Artisan::output());

            $pendingAfter = $this->pendingJobsByQueue();
            $failedAfter = $this->failedJobsCount();

            /*
            |--------------------------------------------------------------------------
            | queue:work puede devolver exit code 0 aunque el Job haya fallado
            |--------------------------------------------------------------------------
            */
            $failedDelta = max(
                0,
                (int) $failedAfter - (int) $failedBefore
            );

            $outputIndicatesFailure =
                str_contains(mb_strtoupper($artisanOutput, 'UTF-8'), 'FAIL')
                || str_contains(mb_strtoupper($artisanOutput, 'UTF-8'), 'FAILED');

            $jobFailed = $failedDelta > 0 || $outputIndicatesFailure;

            $success = $exitCode === 0 && !$jobFailed;

            $this->directLog(
                $success ? 'Worker finalizado correctamente.' : 'Worker terminó con fallo.',
                [
                    'request_id' => $requestId,
                    'exit_code' => $exitCode,
                    'artisan_output' => $artisanOutput,
                    'pending_after' => $pendingAfter,
                    'failed_after' => $failedAfter,
                    'failed_delta' => $failedDelta,
                ]
            );

            Log::info('Cron queue worker finalizado.', [
                'request_id' => $requestId,
                'success' => $success,
                'exit_code' => $exitCode,
                'pending_after' => $pendingAfter,
                'failed_after' => $failedAfter,
            ]);

            return response()->json([
                'ok' => $success,
                'status' => $success ? 'completed' : 'failed',
                'message' => $success
                    ? 'El worker terminó correctamente.'
                    : 'El worker ejecutó el trabajo, pero el Job falló.',
                'request_id' => $requestId,
                'exit_code' => $exitCode,
                'artisan_output' => $artisanOutput ?: null,
                'pending_before' => $pendingBefore,
                'pending_after' => $pendingAfter,
                'failed_before' => $failedBefore,
                'failed_after' => $failedAfter,
                'failed_delta' => $failedDelta,
                'diagnostic' => $this->buildDiagnostic(),
            ], $success ? 200 : 500);
        } catch (Throwable $exception) {
            $this->directLog('Excepción ejecutando el worker.', [
                'request_id' => $requestId,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            Log::error('Cron queue worker lanzó una excepción.', [
                'request_id' => $requestId,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return response()->json([
                'ok' => false,
                'status' => 'exception',
                'message' => $exception->getMessage(),
                'request_id' => $requestId,
                'exception' => get_class($exception),
                'file' => basename($exception->getFile()),
                'line' => $exception->getLine(),
                'diagnostic' => $this->buildDiagnostic(),
            ], 500);
        } finally {
            try {
                $lock->release();

                $this->directLog('Lock liberado.', [
                    'request_id' => $requestId,
                ]);
            } catch (Throwable $exception) {
                $this->directLog('No fue posible liberar el lock.', [
                    'request_id' => $requestId,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * Libera un lock atorado y restablece trabajos reservados.
     */
    public function unlock(Request $request): JsonResponse
    {
        $this->validateCronToken($request);

        try {
            Cache::lock('cron-queue-worker-lock')->forceRelease();

            $releasedJobs = 0;

            if (
                config('queue.default') === 'database'
                && $this->tableExists('jobs')
            ) {
                $releasedJobs = DB::table('jobs')
                    ->whereNotNull('reserved_at')
                    ->update([
                        'reserved_at' => null,
                        'attempts' => 0,
                    ]);
            }

            $this->directLog('Worker desbloqueado manualmente.', [
                'released_jobs' => $releasedJobs,
            ]);

            return response()->json([
                'ok' => true,
                'status' => 'unlocked',
                'message' => 'El bloqueo del worker fue eliminado.',
                'released_jobs' => $releasedJobs,
                'pending_jobs' => $this->pendingJobsCount(),
                'pending_by_queue' => $this->pendingJobsByQueue(),
                'failed_jobs' => $this->failedJobsCount(),
                'timestamp' => now()->toDateTimeString(),
            ]);
        } catch (Throwable $exception) {
            $this->directLog('Error desbloqueando el worker.', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Valida el token por encabezado o query string.
     *
     * Encabezado:
     * X-JURETO-TOKEN: token
     *
     * Query:
     * ?token=token
     */
    private function validateCronToken(Request $request): void
    {
        $configuredToken = trim(
            (string) config('services.cron_queue.token')
        );

        $receivedToken = trim(
            (string) (
                $request->header('X-JURETO-TOKEN')
                ?: $request->query('token')
            )
        );

        if ($configuredToken === '') {
            $this->directLog('CRON_QUEUE_TOKEN no está configurado.');

            abort(
                500,
                'CRON_QUEUE_TOKEN no está configurado en el servidor.'
            );
        }

        if (
            $receivedToken === ''
            || !hash_equals($configuredToken, $receivedToken)
        ) {
            $this->directLog('Intento con token inválido.', [
                'ip' => $request->ip(),
                'received_token_length' => strlen($receivedToken),
            ]);

            abort(403, 'Token de cron inválido.');
        }
    }

    /**
     * Genera diagnóstico general.
     */
    private function buildDiagnostic(): array
    {
        $storageLogsPath = storage_path('logs');
        $directLogPath = storage_path('logs/cron-queue.log');

        return [
            'application_environment' => app()->environment(),
            'application_url' => config('app.url'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),

            'queue_connection' => config('queue.default'),
            'database_retry_after' => config(
                'queue.connections.database.retry_after'
            ),
            'cache_store' => config('cache.default'),
            'log_channel' => config('logging.default'),

            'database_connection' => config('database.default'),
            'database_ok' => $this->databaseWorks(),

            'jobs_table_exists' => $this->tableExists('jobs'),
            'failed_jobs_table_exists' => $this->tableExists('failed_jobs'),

            'pending_jobs' => $this->pendingJobsCount(),
            'pending_by_queue' => $this->pendingJobsByQueue(),
            'failed_jobs' => $this->failedJobsCount(),

            'storage_logs_path' => $storageLogsPath,
            'storage_logs_exists' => is_dir($storageLogsPath),
            'storage_logs_writable' => is_writable($storageLogsPath),

            'direct_log_path' => $directLogPath,
            'direct_log_exists' => is_file($directLogPath),
            'direct_log_writable' => is_file($directLogPath)
                ? is_writable($directLogPath)
                : is_writable($storageLogsPath),

            'timestamp' => now()->toDateTimeString(),
        ];
    }

    /**
     * Escribe en storage/logs/cron-queue.log.
     */
    private function directLog(string $message, array $context = []): void
    {
        try {
            $directory = storage_path('logs');

            if (!is_dir($directory)) {
                File::makeDirectory($directory, 0775, true);
            }

            $contextText = empty($context)
                ? ''
                : ' ' . json_encode(
                    $context,
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_INVALID_UTF8_SUBSTITUTE
                );

            $line = sprintf(
                "[%s] %s%s%s",
                now()->format('Y-m-d H:i:s'),
                $message,
                $contextText,
                PHP_EOL
            );

            File::append(
                storage_path('logs/cron-queue.log'),
                $line
            );
        } catch (Throwable) {
            // No se lanza otra excepción para no romper el endpoint.
        }
    }

    private function databaseWorks(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    private function pendingJobsCount(): ?int
    {
        try {
            if (
                config('queue.default') !== 'database'
                || !$this->tableExists('jobs')
            ) {
                return null;
            }

            return DB::table('jobs')->count();
        } catch (Throwable) {
            return null;
        }
    }

    private function pendingJobsByQueue(): array
    {
        try {
            if (
                config('queue.default') !== 'database'
                || !$this->tableExists('jobs')
            ) {
                return [];
            }

            return DB::table('jobs')
                ->select('queue', DB::raw('COUNT(*) as total'))
                ->groupBy('queue')
                ->pluck('total', 'queue')
                ->map(fn ($total) => (int) $total)
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function failedJobsCount(): ?int
    {
        try {
            if (!$this->tableExists('failed_jobs')) {
                return null;
            }

            return DB::table('failed_jobs')->count();
        } catch (Throwable) {
            return null;
        }
    }

    private function pendingJobsList(): array
    {
        try {
            if (
                config('queue.default') !== 'database'
                || !$this->tableExists('jobs')
            ) {
                return [];
            }

            return DB::table('jobs')
                ->select([
                    'id',
                    'queue',
                    'attempts',
                    'reserved_at',
                    'available_at',
                    'created_at',
                ])
                ->orderBy('id')
                ->limit(30)
                ->get()
                ->map(fn ($job) => [
                    'id' => $job->id,
                    'queue' => $job->queue,
                    'attempts' => (int) $job->attempts,
                    'reserved_at' => $job->reserved_at,
                    'available_at' => $job->available_at,
                    'created_at' => $job->created_at,
                ])
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }
}