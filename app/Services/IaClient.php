<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IaClient
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $primaryModel;
    protected array $fallbackModels;

    public function __construct()
    {
        $this->apiKey         = (string) config('openai.api_key');
        $this->baseUrl        = rtrim((string) config('openai.base_url', 'https://api.openai.com'), '/');
        $this->primaryModel   = (string) config('openai.primary_model', 'gpt-4.1-mini');
        $this->fallbackModels = config('openai.fallback_models', []);

        if ($this->apiKey === '') {
            throw new \RuntimeException('Falta configurar OPENAI_API_KEY en el archivo .env');
        }
    }

    /**
     * Llama al endpoint /v1/chat/completions y devuelve un JSON decodificado.
     *
     * @param  array       $messages  Mensajes tipo Chat (role: system/user/assistant)
     * @param  string|null $model     Modelo opcional (si no, usa el primary + fallbacks)
     * @param  array       $extra     Parámetros extra (max_tokens, temperature, etc.)
     * @return array
     *
     * @throws \RuntimeException si no se obtuvo JSON válido después de probar todos los modelos.
     */
    public function chatJson(array $messages, ?string $model = null, array $extra = []): array
    {
        $modelsToTry = [];

        if ($model !== null) {
            $modelsToTry[] = $model;
        } else {
            $modelsToTry[] = $this->primaryModel;
            $modelsToTry   = array_merge($modelsToTry, $this->fallbackModels);
        }

        // Filtra vacíos
        $modelsToTry = array_values(array_filter($modelsToTry));

        if (empty($modelsToTry)) {
            throw new \RuntimeException('No hay modelos configurados para IA.');
        }

        $lastError = null;

        foreach ($modelsToTry as $useModel) {
            try {
                $payload = array_merge([
                    'model'           => $useModel,
                    'messages'        => $messages,
                    'temperature'     => 0,
                    // JSON mode – ver docs de OpenAI
                    'response_format' => ['type' => 'json_object'],
                ], $extra);

                $response = Http::withToken($this->apiKey)
                    ->baseUrl($this->baseUrl)
                    ->post('/v1/chat/completions', $payload);

                if (! $response->successful()) {
                    Log::warning('IaClient chatJson – respuesta no exitosa', [
                        'model'  => $useModel,
                        'status' => $response->status(),
                        'body'   => $response->body(),
                    ]);
                    $lastError = new \RuntimeException('IA respondió con código ' . $response->status());
                    continue;
                }

                $json = $response->json();

                // Tomamos el contenido principal del mensaje
                $content = Arr::get($json, 'choices.0.message.content');

                if (! is_string($content) || trim($content) === '') {
                    Log::warning('IaClient chatJson – contenido vacío', [
                        'model' => $useModel,
                        'json'  => $json,
                    ]);
                    $lastError = new \RuntimeException('IA devolvió contenido vacío.');
                    continue;
                }

                $decoded = json_decode($content, true);

                if (! is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
                    Log::warning('IaClient chatJson – no se pudo decodificar JSON', [
                        'model'   => $useModel,
                        'content' => $content,
                        'error'   => json_last_error_msg(),
                    ]);
                    $lastError = new \RuntimeException('IA devolvió un JSON inválido.');
                    continue;
                }

                return $decoded;
            } catch (\Throwable $e) {
                Log::error('IaClient chatJson – excepción lanzada', [
                    'model'     => $useModel,
                    'exception' => $e->getMessage(),
                ]);
                $lastError = $e;
                continue;
            }
        }

        throw new \RuntimeException(
            'No fue posible obtener una respuesta JSON válida de la IA.' .
            ($lastError ? ' Último error: ' . $lastError->getMessage() : '')
        );
    }
}
