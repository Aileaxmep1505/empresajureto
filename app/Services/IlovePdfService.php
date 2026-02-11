<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IlovePdfService
{
    // =========================
    // Config
    // =========================
    protected function publicKey(): string
    {
        return (string) config('services.ilovepdf.public_key');
    }

    protected function secretKey(): string
    {
        return (string) config('services.ilovepdf.secret_key');
    }

    protected function region(): string
    {
        return (string) config('services.ilovepdf.region', 'us');
    }

    protected function timeout(): int
    {
        return (int) config('services.ilovepdf.timeout', 180);
    }

    protected function connectTimeout(): int
    {
        return (int) config('services.ilovepdf.connect_timeout', 25);
    }

    protected function maxRetries(): int
    {
        return (int) config('services.ilovepdf.max_retries', 4);
    }

    protected function retryBaseDelayMs(): int
    {
        return (int) config('services.ilovepdf.retry_base_delay_ms', 700);
    }

    protected function tokenCacheKey(): string
    {
        // si manejas multi-tenant, agrega tenant_id aquí
        return 'ilovepdf:token:' . md5($this->publicKey());
    }

    protected function isRetryableStatus(int $status): bool
    {
        return in_array($status, [408, 425, 429, 500, 502, 503, 504], true);
    }

    /**
     * Wrapper HTTP robusto con retry/backoff y connectTimeout.
     * - Reintenta ante ConnectionException
     * - Reintenta ante 429/5xx/408/504 etc.
     */
    protected function request(string $method, string $url, array $options = [])
    {
        $timeout = max(20, $this->timeout());
        $connect = max(5, $this->connectTimeout());
        $max     = max(1, $this->maxRetries());
        $baseMs  = max(250, $this->retryBaseDelayMs());

        $attempt = 0;
        $lastResp = null;
        $lastEx = null;

        while ($attempt < $max) {
            $attempt++;

            try {
                $client = Http::timeout($timeout)->connectTimeout($connect);

                if (!empty($options['token'])) {
                    $client = $client->withToken($options['token']);
                }
                if (!empty($options['headers']) && is_array($options['headers'])) {
                    $client = $client->withHeaders($options['headers']);
                }
                if (!empty($options['asForm'])) {
                    $client = $client->asForm();
                }
                if (!empty($options['attach']) && is_array($options['attach'])) {
                    // attach: [['name'=>'file','contents'=>resource|string,'filename'=>'x.pdf']]
                    foreach ($options['attach'] as $a) {
                        $client = $client->attach(
                            $a['name'] ?? 'file',
                            $a['contents'] ?? '',
                            $a['filename'] ?? null
                        );
                    }
                }

                $payload = $options['payload'] ?? null;

                $resp = $payload === null
                    ? $client->{$method}($url)
                    : $client->{$method}($url, $payload);

                $lastResp = $resp;

                if ($resp->ok()) {
                    return $resp;
                }

                $status = (int) $resp->status();

                // 401/403: no reintentar a lo loco aquí (lo manejamos arriba si aplica)
                if (!$this->isRetryableStatus($status)) {
                    return $resp;
                }

                // backoff
                $sleepMs = (int) ($baseMs * $attempt);
                usleep($sleepMs * 1000);
                continue;
            } catch (ConnectionException $e) {
                $lastEx = $e;
                $sleepMs = (int) ($baseMs * $attempt);
                usleep($sleepMs * 1000);
                continue;
            }
        }

        if ($lastEx) {
            throw new \RuntimeException('iLovePDF: error de red/timeout: ' . $lastEx->getMessage());
        }

        return $lastResp ?? throw new \RuntimeException('iLovePDF: error desconocido llamando ' . $url);
    }

    // =========================
    // Token (con cache)
    // =========================

    /**
     * Pide token al auth server (validez aprox. 2 horas).
     * ✅ Cachea el token para NO pegarle al auth en cada request.
     */
    public function getToken(bool $forceRefresh = false): string
    {
        $public = $this->publicKey();
        if ($public === '') {
            throw new \RuntimeException('ILOVEPDF_PUBLIC_KEY no configurada.');
        }

        $cacheKey = $this->tokenCacheKey();

        if (!$forceRefresh) {
            $cached = Cache::get($cacheKey);
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        }

        $resp = $this->request('post', 'https://api.ilovepdf.com/v1/auth', [
            'asForm'  => true,
            'payload' => [
                'public_key' => $public,
            ],
        ]);

        if (!$resp->ok() || !is_array($resp->json()) || empty($resp->json()['token'])) {
            Log::warning('iLovePDF auth failed', [
                'status' => $resp->status(),
                'body'   => mb_substr((string) $resp->body(), 0, 2000),
            ]);
            throw new \RuntimeException('No se pudo autenticar con iLovePDF.');
        }

        $token = (string) $resp->json()['token'];

        // cache ~110 min (2h - colchón)
        Cache::put($cacheKey, $token, now()->addMinutes(110));

        return $token;
    }

    // =========================
    // Task / Upload / Process / Download
    // =========================

    /**
     * Start task: devuelve ['server' => 'apiXX.ilovepdf.com', 'task' => '...', 'token' => '...']
     */
    public function startTask(string $tool): array
    {
        $region = $this->region();

        // 1) intenta con token cacheado
        $token = $this->getToken(false);
        $url   = "https://api.ilovepdf.com/v1/start/{$tool}/{$region}";

        $resp = $this->request('get', $url, [
            'token' => $token,
        ]);

        // 401 -> refresca token 1 vez
        if ((int) $resp->status() === 401 || (int) $resp->status() === 403) {
            $token = $this->getToken(true);
            $resp  = $this->request('get', $url, ['token' => $token]);
        }

        if (!$resp->ok() || empty($resp->json('server')) || empty($resp->json('task'))) {
            Log::warning('iLovePDF start failed', [
                'tool'   => $tool,
                'status' => $resp->status(),
                'body'   => mb_substr((string) $resp->body(), 0, 2000),
            ]);
            throw new \RuntimeException("No se pudo iniciar task iLovePDF ({$tool}).");
        }

        return [
            'token'  => $token,
            'server' => (string) $resp->json('server'),
            'task'   => (string) $resp->json('task'),
        ];
    }

    /**
     * Upload local file
     * Retorna server_filename
     */
    public function uploadFile(string $server, string $token, string $task, string $pdfFullPath): string
    {
        if (!is_file($pdfFullPath)) {
            throw new \RuntimeException('Archivo no existe para subir a iLovePDF.');
        }

        $url = "https://{$server}/v1/upload";

        // ✅ evita file_get_contents gigante: usa stream
        $fh = fopen($pdfFullPath, 'r');
        if ($fh === false) {
            throw new \RuntimeException('No se pudo abrir el archivo para subir a iLovePDF.');
        }

        try {
            $resp = $this->request('post', $url, [
                'token'  => $token,
                'attach' => [[
                    'name'     => 'file',
                    'contents' => $fh,
                    'filename' => basename($pdfFullPath),
                ]],
                'payload' => [
                    'task' => $task,
                ],
            ]);
        } finally {
            try { fclose($fh); } catch (\Throwable $e) {}
        }

        if (!$resp->ok() || empty($resp->json('server_filename'))) {
            Log::warning('iLovePDF upload failed', [
                'status' => $resp->status(),
                'body'   => mb_substr((string) $resp->body(), 0, 2000),
            ]);
            throw new \RuntimeException('No se pudo subir el archivo a iLovePDF.');
        }

        return (string) $resp->json('server_filename');
    }

    /**
     * Process tool
     */
    public function process(string $server, string $token, array $payload): array
    {
        $url = "https://{$server}/v1/process";

        $resp = $this->request('post', $url, [
            'token'   => $token,
            'headers' => ['Content-Type' => 'application/json'],
            'payload' => $payload,
        ]);

        if (!$resp->ok()) {
            Log::warning('iLovePDF process failed', [
                'status' => $resp->status(),
                'body'   => mb_substr((string) $resp->body(), 0, 4000),
            ]);
            throw new \RuntimeException('No se pudo procesar en iLovePDF: ' . $resp->body());
        }

        return (array) $resp->json();
    }

    /**
     * Download output (binary)
     */
    public function download(string $server, string $token, string $task): string
    {
        $url = "https://{$server}/v1/download/{$task}";

        $resp = $this->request('get', $url, [
            'token' => $token,
        ]);

        if (!$resp->ok()) {
            Log::warning('iLovePDF download failed', [
                'status' => $resp->status(),
                'body'   => mb_substr((string) $resp->body(), 0, 2000),
            ]);
            throw new \RuntimeException('No se pudo descargar resultado de iLovePDF.');
        }

        return (string) $resp->body();
    }

    // =========================
    // High-level helpers
    // =========================

    /**
     * REPARA un PDF y regresa el BINARIO del PDF reparado.
     * (Tool: repair)
     */
    public function repairPdfBinary(string $pdfFullPath): string
    {
        $task = $this->startTask('repair');

        $serverFilename = $this->uploadFile($task['server'], $task['token'], $task['task'], $pdfFullPath);

        $this->process($task['server'], $task['token'], [
            'task'  => $task['task'],
            'tool'  => 'repair',
            'files' => [[
                'server_filename' => $serverFilename,
                'filename'        => basename($pdfFullPath),
            ]],
        ]);

        return $this->download($task['server'], $task['token'], $task['task']);
    }

    /**
     * SPLIT por rangos y regresa el BINARIO del resultado.
     * split_mode=ranges, ranges="10-20"
     */
    public function splitRangesBinary(string $pdfFullPath, string $ranges, bool $mergeAfter = true): string
    {
        $task = $this->startTask('split');

        $serverFilename = $this->uploadFile($task['server'], $task['token'], $task['task'], $pdfFullPath);

        $this->process($task['server'], $task['token'], [
            'task'        => $task['task'],
            'tool'        => 'split',
            'split_mode'  => 'ranges',
            'ranges'      => $ranges,
            'merge_after' => $mergeAfter,
            'files'       => [[
                'server_filename' => $serverFilename,
                'filename'        => basename($pdfFullPath),
            ]],
        ]);

        return $this->download($task['server'], $task['token'], $task['task']);
    }

    /**
     * ✅ OCR: convierte un PDF escaneado en PDF con texto seleccionable.
     * Usa la herramienta "pdfocr" y devuelve el BINARIO del PDF ya OCR.
     *
     * @param string $pdfFullPath Ruta absoluta al PDF original
     * @param array|null $languages Lista de idiomas, ej: ['spa','eng']
     */
    public function ocrPdfBinary(string $pdfFullPath, ?array $languages = null): string
    {
        $task = $this->startTask('pdfocr');

        $serverFilename = $this->uploadFile($task['server'], $task['token'], $task['task'], $pdfFullPath);

        $payload = [
            'task'  => $task['task'],
            'tool'  => 'pdfocr',
            'files' => [[
                'server_filename' => $serverFilename,
                'filename'        => basename($pdfFullPath),
            ]],
        ];

        if (!empty($languages)) {
            $payload['ocr_languages'] = array_values($languages);
        }

        $this->process($task['server'], $task['token'], $payload);

        return $this->download($task['server'], $task['token'], $task['task']);
    }
}
