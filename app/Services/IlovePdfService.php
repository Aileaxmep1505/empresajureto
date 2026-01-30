<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IlovePdfService
{
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

    /**
     * Pide token al auth server (2 horas de validez).
     */
    public function getToken(): string
    {
        $public = $this->publicKey();
        if ($public === '') {
            throw new \RuntimeException('ILOVEPDF_PUBLIC_KEY no configurada.');
        }

        $resp = Http::timeout($this->timeout())
            ->asForm()
            ->post('https://api.ilovepdf.com/v1/auth', [
                'public_key' => $public,
            ]);

        if (!$resp->ok() || !is_array($resp->json()) || empty($resp->json()['token'])) {
            Log::warning('iLovePDF auth failed', ['status' => $resp->status(), 'body' => $resp->body()]);
            throw new \RuntimeException('No se pudo autenticar con iLovePDF.');
        }

        return (string) $resp->json()['token'];
    }

    /**
     * Start task: devuelve ['server' => 'apiXX.ilovepdf.com', 'task' => '...']
     */
    public function startTask(string $tool): array
    {
        $token = $this->getToken();
        $region = $this->region();

        $url = "https://api.ilovepdf.com/v1/start/{$tool}/{$region}";

        $resp = Http::timeout($this->timeout())
            ->withToken($token)
            ->get($url);

        if (!$resp->ok() || empty($resp->json()['server']) || empty($resp->json()['task'])) {
            Log::warning('iLovePDF start failed', ['status' => $resp->status(), 'body' => $resp->body()]);
            throw new \RuntimeException("No se pudo iniciar task iLovePDF ({$tool}).");
        }

        return [
            'token'  => $token,
            'server' => (string) $resp->json()['server'],
            'task'   => (string) $resp->json()['task'],
        ];
    }

    /**
     * Upload local file
     * Retorna server_filename
     */
    public function uploadFile(string $server, string $token, string $task, string $pdfFullPath): string
    {
        if (!file_exists($pdfFullPath)) {
            throw new \RuntimeException('Archivo no existe para subir a iLovePDF.');
        }

        $url = "https://{$server}/v1/upload";

        $resp = Http::timeout($this->timeout())
            ->withToken($token)
            ->attach('file', file_get_contents($pdfFullPath), basename($pdfFullPath))
            ->post($url, [
                'task' => $task,
            ]);

        if (!$resp->ok() || empty($resp->json()['server_filename'])) {
            Log::warning('iLovePDF upload failed', ['status' => $resp->status(), 'body' => $resp->body()]);
            throw new \RuntimeException('No se pudo subir el archivo a iLovePDF.');
        }

        return (string) $resp->json()['server_filename'];
    }

    /**
     * Process tool
     */
    public function process(string $server, string $token, array $payload): array
    {
        $url = "https://{$server}/v1/process";

        $resp = Http::timeout($this->timeout())
            ->withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $payload);

        if (!$resp->ok()) {
            Log::warning('iLovePDF process failed', ['status' => $resp->status(), 'body' => $resp->body()]);
            throw new \RuntimeException('No se pudo procesar en iLovePDF: '.$resp->body());
        }

        return (array) $resp->json();
    }

    /**
     * Download output (binary)
     */
    public function download(string $server, string $token, string $task): string
    {
        $url = "https://{$server}/v1/download/{$task}";

        $resp = Http::timeout($this->timeout())
            ->withToken($token)
            ->get($url);

        if (!$resp->ok()) {
            Log::warning('iLovePDF download failed', ['status' => $resp->status(), 'body' => $resp->body()]);
            throw new \RuntimeException('No se pudo descargar resultado de iLovePDF.');
        }

        return (string) $resp->body();
    }

    /**
     * REPARA un PDF y regresa el BINARIO del PDF reparado.
     * (Tool: repair)
     */
    public function repairPdfBinary(string $pdfFullPath): string
    {
        $task = $this->startTask('repair');

        $serverFilename = $this->uploadFile($task['server'], $task['token'], $task['task'], $pdfFullPath);

        $this->process($task['server'], $task['token'], [
            'task' => $task['task'],
            'tool' => 'repair',
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
            'ranges'      => $ranges,          // ej: "10-20"
            'merge_after' => $mergeAfter,      // si mandas varios rangos, los une
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

        // Opcional: idiomas para OCR (si no se pasan, usa los defaults del proyecto)
        if (!empty($languages)) {
            $payload['ocr_languages'] = array_values($languages);
        }

        $this->process($task['server'], $task['token'], $payload);

        return $this->download($task['server'], $task['token'], $task['task']);
    }
}
