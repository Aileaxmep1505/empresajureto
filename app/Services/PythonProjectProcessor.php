<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PythonProjectProcessor
{
    protected string $pythonBin;
    protected string $scriptPath;
    protected int $timeoutSeconds;

    public function __construct()
    {
        $this->pythonBin   = (string) env('PYTHON_BIN', '/usr/bin/python3');
        $this->scriptPath  = (string) env('AZURE_LICITACION_PDF_EXTRACT_SCRIPT', '');
        $this->timeoutSeconds = (int) env('PYTHONAI_TIMEOUT', 900);
    }

    /**
     * Procesa N archivos con Azure + OpenAI vía el script Python.
     *
     * @param string[] $absoluteFilePaths
     * @return array
     */
    public function run(array $absoluteFilePaths): array
    {
        if (!$this->scriptPath) {
            throw new \Exception("Falta AZURE_LICITACION_PDF_EXTRACT_SCRIPT en .env");
        }

        if (!file_exists($this->scriptPath)) {
            throw new \Exception("No existe el script Python: {$this->scriptPath}");
        }

        if (empty($absoluteFilePaths)) {
            throw new \Exception("No se recibieron archivos para procesar.");
        }

        $command = array_merge(
            [$this->pythonBin, $this->scriptPath],
            $absoluteFilePaths
        );

        Log::info('PythonProjectProcessor: ejecutando', [
            'cmd'         => $command,
            'files_count' => count($absoluteFilePaths),
        ]);

        $process = new Process($command);
        $process->setTimeout($this->timeoutSeconds);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            Log::error('PythonProjectProcessor falló', [
                'exit_code' => $process->getExitCode(),
                'stderr'    => $process->getErrorOutput(),
                'stdout'    => $process->getOutput(),
            ]);

            $stderr = $process->getErrorOutput();
            $stdout = $process->getOutput();

            // Si Python devolvió JSON de error en stdout, úsalo
            $errPayload = json_decode($stdout, true);
            if (is_array($errPayload) && isset($errPayload['error'])) {
                throw new \Exception('Python: ' . $errPayload['error']);
            }

            throw new \Exception('Python falló: ' . ($stderr ?: 'sin detalle'));
        }

        $stdout = trim($process->getOutput());
        $payload = json_decode($stdout, true);

        // intentar parsear última línea JSON si el output trae basura antes
        if (!is_array($payload)) {
            $lines = array_reverse(array_filter(explode("\n", $stdout)));
            foreach ($lines as $line) {
                $try = json_decode(trim($line), true);
                if (is_array($try)) {
                    $payload = $try;
                    break;
                }
            }
        }

        if (!is_array($payload)) {
            throw new \Exception('Python no devolvió JSON parseable: ' . mb_substr($stdout, 0, 500));
        }

        if (empty($payload['ok'])) {
            throw new \Exception('Python reportó error: ' . ($payload['error'] ?? 'desconocido'));
        }

        return $payload;
    }
}