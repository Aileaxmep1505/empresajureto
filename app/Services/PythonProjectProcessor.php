<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PythonProjectProcessor
{
    protected string $pythonBin;
    protected string $scriptPath;
    protected int $timeoutSeconds;
    protected int $idleTimeoutSeconds;

    public function __construct()
    {
        $this->pythonBin = (string) env('PYTHON_BIN', '/usr/bin/python3');

        /*
         |--------------------------------------------------------------------------
         | Script Python
         |--------------------------------------------------------------------------
         |
         | Respeta tu variable actual:
         | AZURE_LICITACION_PDF_EXTRACT_SCRIPT
         |
         | Si no existe, intenta usar:
         | scripts/process_licitacion.py
         |
         */
        $this->scriptPath = (string) env(
            'AZURE_LICITACION_PDF_EXTRACT_SCRIPT',
            base_path('scripts/process_licitacion.py')
        );

        $this->timeoutSeconds = (int) env('PYTHONAI_TIMEOUT', 900);
        $this->idleTimeoutSeconds = (int) env('PYTHONAI_IDLE_TIMEOUT', 900);
    }

    /**
     * Método que está llamando tu controller:
     *
     * $processor->process($project, $paths);
     *
     * Devuelve la estructura normalizada para que el controller pueda hacer:
     *
     * $project->structured_data = $result['structured_data'] ?? null;
     * $project->checklist = data_get($result, 'structured_data.checklist_sugerido', []);
     */
    public function process(Project $project, array $absoluteFilePaths): array
    {
        $payload = $this->run($absoluteFilePaths, [
            'project_id' => $project->id,
            'project_name' => $project->name,
        ]);

        $structured = $payload['structured'] ?? null;

        if (!is_array($structured)) {
            throw new \Exception('Python no devolvió el campo structured como arreglo.');
        }

        return [
            'structured_data' => $structured,
            'documents' => $payload['documents'] ?? [],
            'raw_text_combined' => $payload['raw_text_combined'] ?? null,
            'raw' => $payload,
        ];
    }

    /**
     * Procesa N archivos con Azure + OpenAI vía el script Python.
     *
     * Este método conserva compatibilidad con tu clase actual:
     *
     * $processor->run($paths);
     *
     * @param string[] $absoluteFilePaths
     * @param array $context
     * @return array
     */
    public function run(array $absoluteFilePaths, array $context = []): array
    {
        $this->validateBeforeRun($absoluteFilePaths);

        $command = array_merge(
            [$this->pythonBin, $this->scriptPath],
            $absoluteFilePaths
        );

        Log::info('PythonProjectProcessor: ejecutando', [
            'cmd' => $command,
            'files_count' => count($absoluteFilePaths),
            'context' => $context,
        ]);

        $process = new Process($command);

        $process->setTimeout($this->timeoutSeconds);
        $process->setIdleTimeout($this->idleTimeoutSeconds);

        /*
         |--------------------------------------------------------------------------
         | Variables de entorno para Python
         |--------------------------------------------------------------------------
         |
         | En algunos hostings, el proceso Symfony no hereda bien todas las variables.
         | Por eso se las pasamos explícitamente.
         |
         */
        $process->setEnv([
            'PATH' => env('PATH', getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin'),

            'OPENAI_API_KEY' => env('OPENAI_API_KEY'),
            'OPENAI_PRIMARY_MODEL' => env('OPENAI_PRIMARY_MODEL', 'gpt-5.4'),
            'OPENAI_MODEL' => env('OPENAI_MODEL', env('OPENAI_PRIMARY_MODEL', 'gpt-5.4')),
            'OPENAI_FALLBACK_MODELS' => env(
                'OPENAI_FALLBACK_MODELS',
                'gpt-5.5,gpt-5,gpt-4.1,gpt-4o'
            ),

            'AZURE_DOCUMENT_INTELLIGENCE_ENDPOINT' => env('AZURE_DOCUMENT_INTELLIGENCE_ENDPOINT'),
            'AZURE_DOCUMENT_INTELLIGENCE_KEY' => env('AZURE_DOCUMENT_INTELLIGENCE_KEY'),
            'AZURE_DOCUMENT_INTELLIGENCE_API_VERSION' => env(
                'AZURE_DOCUMENT_INTELLIGENCE_API_VERSION',
                '2024-11-30'
            ),
        ]);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            $this->logFailedProcess($process, $context);

            $stderr = trim($process->getErrorOutput());
            $stdout = trim($process->getOutput());

            $errPayload = $this->decodeJsonFromOutput($stdout);

            if (is_array($errPayload)) {
                $message = $errPayload['error']
                    ?? $errPayload['message']
                    ?? 'Python devolvió error sin mensaje.';

                throw new \Exception('Python: ' . $message);
            }

            throw new \Exception('Python falló: ' . ($stderr ?: $stdout ?: 'sin detalle'));
        }

        $stdout = trim($process->getOutput());
        $stderr = trim($process->getErrorOutput());

        if ($stderr !== '') {
            Log::info('PythonProjectProcessor STDERR', [
                'stderr' => $stderr,
                'context' => $context,
            ]);
        }

        if ($stdout === '') {
            throw new \Exception('Python no devolvió salida.');
        }

        $payload = $this->decodeJsonFromOutput($stdout);

        if (!is_array($payload)) {
            Log::error('PythonProjectProcessor: JSON no parseable', [
                'stdout' => mb_substr($stdout, 0, 2000),
                'context' => $context,
            ]);

            throw new \Exception('Python no devolvió JSON parseable: ' . mb_substr($stdout, 0, 500));
        }

        if (empty($payload['ok'])) {
            Log::error('PythonProjectProcessor: ok=false', [
                'payload' => $payload,
                'context' => $context,
            ]);

            throw new \Exception('Python reportó error: ' . ($payload['error'] ?? $payload['message'] ?? 'desconocido'));
        }

        if (!isset($payload['structured']) || !is_array($payload['structured'])) {
            Log::error('PythonProjectProcessor: falta structured', [
                'payload_keys' => array_keys($payload),
                'context' => $context,
            ]);

            throw new \Exception('Python respondió ok=true, pero no devolvió structured.');
        }

        return $payload;
    }

    /**
     * Alias por si en otra parte usas handle().
     */
    public function handle(Project $project, array $absoluteFilePaths): array
    {
        return $this->process($project, $absoluteFilePaths);
    }

    protected function validateBeforeRun(array $absoluteFilePaths): void
    {
        if (!$this->scriptPath) {
            throw new \Exception('Falta AZURE_LICITACION_PDF_EXTRACT_SCRIPT en .env');
        }

        if (!file_exists($this->scriptPath)) {
            throw new \Exception("No existe el script Python: {$this->scriptPath}");
        }

        if (empty($absoluteFilePaths)) {
            throw new \Exception('No se recibieron archivos para procesar.');
        }

        foreach ($absoluteFilePaths as $path) {
            if (!is_string($path) || trim($path) === '') {
                throw new \Exception('Una ruta de archivo viene vacía.');
            }

            if (!file_exists($path)) {
                throw new \Exception("No existe el archivo para procesar: {$path}");
            }

            if (!is_readable($path)) {
                throw new \Exception("El archivo no es legible por PHP/Python: {$path}");
            }
        }

        if (!file_exists($this->pythonBin)) {
            Log::warning('PythonProjectProcessor: PYTHON_BIN no existe como ruta absoluta, se intentará ejecutar de todos modos', [
                'python_bin' => $this->pythonBin,
            ]);
        }
    }

    protected function decodeJsonFromOutput(string $stdout): ?array
    {
        $stdout = trim($stdout);

        if ($stdout === '') {
            return null;
        }

        $payload = json_decode($stdout, true);

        if (is_array($payload)) {
            return $payload;
        }

        /*
         |--------------------------------------------------------------------------
         | Fallback: última línea JSON
         |--------------------------------------------------------------------------
         |
         | Por si el script imprime logs antes del JSON.
         |
         */
        $lines = array_reverse(array_filter(explode("\n", $stdout)));

        foreach ($lines as $line) {
            $try = json_decode(trim($line), true);

            if (is_array($try)) {
                return $try;
            }
        }

        /*
         |--------------------------------------------------------------------------
         | Fallback: buscar primer bloque JSON grande
         |--------------------------------------------------------------------------
         */
        if (preg_match('/\{.*\}/s', $stdout, $match)) {
            $try = json_decode($match[0], true);

            if (is_array($try)) {
                return $try;
            }
        }

        return null;
    }

    protected function logFailedProcess(Process $process, array $context = []): void
    {
        Log::error('PythonProjectProcessor falló', [
            'exit_code' => $process->getExitCode(),
            'stderr' => mb_substr($process->getErrorOutput(), 0, 4000),
            'stdout' => mb_substr($process->getOutput(), 0, 4000),
            'context' => $context,
        ]);
    }
}