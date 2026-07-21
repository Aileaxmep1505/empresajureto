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
     * Prueba básica sin ejecutar la cola.
     *
     * URL:
     * https://TU-DOMINIO.com/cron/queue/ping?token=TU_TOKEN
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
     * Ejecuta un trabajo de reports o default.
     *
     * URL:
     * https://TU-DOMINIO.com/cron/queue/run?token=TU_TOKEN
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
        | Lock para evitar workers simultáneos
        |--------------------------------------------------------------------------
        */
        $lock = Cache::lock('cron-queue-worker-lock', 1900);

        if (!$lock->get()) {
            $this->directLog('Worker omitido: existe otro proceso activo.', [
                'request_id' => $requestId,
            ]);

            return response()->json([
                'ok' => true,
                'status' => 'busy',
                'message' => 'Ya existe un trabajador procesando la cola.',
                'request_id' => $requestId,
                'diagnostic' => $this->buildDiagnostic(),
            ]);
        }

        try {
            @set_time_limit(1850);
            @ini_set('max_execution_time', '1850');
            ignore_user_abort(true);

            $pendingBefore = $this->pendingJobsByQueue();
            $failedBefore = $this->failedJobsCount();

            $this->directLog('Worker iniciando.', [
                'request_id' => $requestId,
                'queue_connection' => config('queue.default'),
                'queues' => 'reports,default',
                'pending_before' => $pendingBefore,
                'failed_before' => $failedBefore,
            ]);

            Log::info('Cron externo: iniciando worker.', [
                'request_id' => $requestId,
                'queues' => 'reports,default',
            ]);

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

            $artisanOutput = trim((string) Artisan::output());
            $pendingAfter = $this->pendingJobsByQueue();
            $failedAfter = $this->failedJobsCount();

            $this->directLog('Worker finalizado.', [
                'request_id' => $requestId,
                'exit_code' => $exitCode,
                'artisan_output' => $artisanOutput,
                'pending_after' => $pendingAfter,
                'failed_after' => $failedAfter,
            ]);

            return response()->json([
                'ok' => $exitCode === 0,
                'status' => $exitCode === 0 ? 'completed' : 'error',
                'message' => $exitCode === 0
                    ? 'El worker terminó correctamente.'
                    : 'El worker terminó con error.',
                'request_id' => $requestId,
                'exit_code' => $exitCode,
                'artisan_output' => $artisanOutput ?: null,
                'pending_before' => $pendingBefore,
                'pending_after' => $pendingAfter,
                'failed_before' => $failedBefore,
                'failed_after' => $failedAfter,
                'diagnostic' => $this->buildDiagnostic(),
            ], $exitCode === 0 ? 200 : 500);
        } catch (Throwable $exception) {
            $this->directLog('Excepción ejecutando el worker.', [
                'request_id' => $requestId,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            Log::error('Cron externo: excepción.', [
                'request_id' => $requestId,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return response()->json([
                'ok' => false,
                'status' => 'exception',
                'request_id' => $requestId,
                'message' => $exception->getMessage(),
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
     * Estado general.
     *
     * URL:
     * https://TU-DOMINIO.com/cron/queue/health?token=TU_TOKEN
     */
    public function health(Request $request): JsonResponse
    {
        $this->validateCronToken($request);

        $diagnostic = $this->buildDiagnostic();

        $this->directLog('HEALTH consultado.', $diagnostic);

        return response()->json([
            'ok' => true,
            'status' => 'healthy',
            'message' => 'El controlador y el token funcionan.',
            'diagnostic' => $diagnostic,
        ]);
    }

    /**
     * Lista trabajos pendientes.
     *
     * URL:
     * https://TU-DOMINIO.com/cron/queue/status?token=TU_TOKEN
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
                    ->limit(30)
                    ->get()
                    ->map(fn ($job) => [
                        'id' => $job->id,
                        'queue' => $job->queue,
                        'attempts' => $job->attempts,
                        'reserved_at' => $job->reserved_at,
                        'available_at' => $job->available_at,
                        'created_at' => $job->created_at,
                    ])
                    ->values()
                    ->all();
            }
        } catch (Throwable $exception) {
            $this->directLog('Error consultando trabajos.', [
                'message' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'ok' => true,
            'jobs' => $jobs,
            'diagnostic' => $this->buildDiagnostic(),
        ]);
    }
private function validateCronToken(Request $request): void
{
    $configuredToken = trim(
        (string) config('services.cron_queue.token')
    );

    /*
    |--------------------------------------------------------------------------
    | Acepta token por encabezado o por query string
    |--------------------------------------------------------------------------
    | Cron-job.org puede enviarlo como encabezado:
    | X-JURETO-TOKEN: token
    |
    | Para pruebas manuales en navegador:
    | ?token=token
    */
    $receivedToken = trim(
        (string) (
            $request->header('X-JURETO-TOKEN')
            ?: $request->query('token')
        )
    );

    if ($configuredToken === '') {
        abort(500, 'CRON_QUEUE_TOKEN no está configurado.');
    }

    if (
        $receivedToken === ''
        || !hash_equals($configuredToken, $receivedToken)
    ) {
        abort(403, 'Token de cron inválido.');
    }
}

    private function buildDiagnostic(): array
    {
        $storagePath = storage_path('logs');
        $directLogPath = storage_path('logs/cron-queue.log');

        return [
            'application_environment' => app()->environment(),
            'application_url' => config('app.url'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),

            'queue_connection' => config('queue.default'),
            'cache_store' => config('cache.default'),
            'log_channel' => config('logging.default'),

            'database_connection' => config('database.default'),
            'database_ok' => $this->databaseWorks(),

            'jobs_table_exists' => $this->tableExists('jobs'),
            'failed_jobs_table_exists' => $this->tableExists('failed_jobs'),

            'pending_jobs' => $this->pendingJobsCount(),
            'pending_by_queue' => $this->pendingJobsByQueue(),
            'failed_jobs' => $this->failedJobsCount(),

            'storage_logs_path' => $storagePath,
            'storage_logs_exists' => is_dir($storagePath),
            'storage_logs_writable' => is_writable($storagePath),

            'direct_log_path' => $directLogPath,
            'direct_log_exists' => is_file($directLogPath),
            'direct_log_writable' => is_file($directLogPath)
                ? is_writable($directLogPath)
                : is_writable($storagePath),

            'timestamp' => now()->toDateTimeString(),
        ];
    }

    private function directLog(string $message, array $context = []): void
    {
        try {
            $directory = storage_path('logs');

            if (!is_dir($directory)) {
                File::makeDirectory($directory, 0775, true);
            }

            $line = sprintf(
                "[%s] %s %s%s",
                now()->format('Y-m-d H:i:s'),
                $message,
                empty($context)
                    ? ''
                    : json_encode(
                        $context,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                PHP_EOL
            );

            File::append(storage_path('logs/cron-queue.log'), $line);
        } catch (Throwable $exception) {
            // No lanzamos otra excepción para evitar romper el endpoint.
        }
    }

    private function databaseWorks(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (Throwable $exception) {
            return false;
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (Throwable $exception) {
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
        } catch (Throwable $exception) {
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
        } catch (Throwable $exception) {
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
        } catch (Throwable $exception) {
            return null;
        }
    }
    public function unlock(Request $request): JsonResponse
{
    $this->validateCronToken($request);

    try {
        Cache::forget('cron-queue-worker-lock');

        return response()->json([
            'ok' => true,
            'status' => 'unlocked',
            'message' => 'El bloqueo del worker fue eliminado.',
            'timestamp' => now()->toDateTimeString(),
        ]);
    } catch (Throwable $exception) {
        return response()->json([
            'ok' => false,
            'status' => 'error',
            'message' => $exception->getMessage(),
        ], 500);
    }
}
}