<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use RuntimeException;

class AiDynamicChecklistService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected ?string $projectId;
    protected ?string $orgId;

    /** Modelos: primary + fallbacks desde config/services.php */
    protected array $models;
    protected int $timeout;
    protected int $connectTimeout;
    protected int $retriesPerModel;
    protected int $retryBaseMs;
    protected int $maxTotalAttempts;

    public function __construct()
    {
        $cfg = config('services.openai');

        $this->apiKey          = (string) ($cfg['api_key'] ?? env('OPENAI_API_KEY', ''));
        $this->baseUrl         = rtrim((string) ($cfg['base_url'] ?? env('OPENAI_BASE_URL', 'https://api.openai.com')), '/');
        $this->projectId       = $cfg['project_id'] ?? env('OPENAI_PROJECT_ID');
        $this->orgId           = $cfg['org_id']     ?? env('OPENAI_ORG_ID');

        // Si OPENAI_MODEL_CHECKLIST está definido, úsalo como primary
        $primaryEnv = trim((string) env('OPENAI_MODEL_CHECKLIST', ''));
        $primary    = $primaryEnv !== '' ? $primaryEnv : ((string) ($cfg['primary'] ?? 'gpt-4o'));
        $fallbacks  = array_values(array_filter(array_map('trim', (array) ($cfg['fallbacks'] ?? []))));

        $this->models = array_values(array_unique(array_filter([$primary, ...$fallbacks])));

        $this->timeout          = (int) ($cfg['timeout'] ?? env('OPENAI_TIMEOUT', 45));
        $this->connectTimeout   = (int) ($cfg['connect_timeout'] ?? env('OPENAI_CONNECT_TIMEOUT', 10));
        $this->retriesPerModel  = (int) ($cfg['max_retries_per_model'] ?? env('OPENAI_RETRIES_PER_MODEL', 2));
        $this->retryBaseMs      = (int) ($cfg['retry_base_delay_ms'] ?? env('OPENAI_RETRY_BASE_MS', 400));
        $this->maxTotalAttempts = (int) ($cfg['max_total_attempts'] ?? env('OPENAI_MAX_TOTAL_ATTEMPTS', 6));
    }

    /**
     * Genera checklist 100% con OpenAI.
     * Devuelve: ['title'=>string,'instructions'=>string,'items'=>[['text'=>string],...]]
     */
    public function checklistFor(string $prompt, array $context = []): array
    {
        if (!$this->apiKey) {
            throw new RuntimeException('Falta OPENAI_API_KEY en .env');
        }

        $sys = <<<SYS
Eres un asistente que genera checklists operativos VERIFICABLES para equipos de trabajo.
Responde EXCLUSIVAMENTE un JSON válido con ESTA ESTRUCTURA EXACTA:
{
  "title": "string",
  "instructions": "string",
  "items": [{"text":"string"}, ...]
}
Reglas:
- 8 a 12 items máximo.
- Cada "text" debe ser una ACCIÓN breve y MEDIBLE que empiece con verbo en imperativo.
- Nada fuera del JSON. Nada de comentarios.
SYS;

        $usr = $this->buildUserPrompt($prompt, $context);

        $lastErr = null;
        $attempts = 0;

        foreach ($this->models as $model) {
            $tries = 0;
            while ($tries <= $this->retriesPerModel && $attempts < $this->maxTotalAttempts) {
                $attempts++; $tries++;
                try {
                    $json = $this->callChatCompletions($model, $sys, $usr);
                    // Validación mínima
                    $title = (string) data_get($json, 'title', '');
                    $instructions = (string) data_get($json, 'instructions', '');
                    $items = collect((array) data_get($json, 'items', []))
                        ->map(fn($it) => ['text' => trim((string) data_get($it, 'text', ''))])
                        ->filter(fn($it) => $it['text'] !== '')
                        ->values()->all();

                    if ($title === '' || count($items) < 8 || count($items) > 12) {
                        throw new RuntimeException('La IA no devolvió 8–12 acciones medibles.');
                    }

                    return compact('title','instructions','items');
                } catch (\Throwable $e) {
                    $lastErr = $e;
                    // 403/404: cambiar de modelo de inmediato
                    if ($e instanceof RuntimeException && str_contains($e->getMessage(), 'HTTP 403')) break;
                    if ($e instanceof RuntimeException && str_contains($e->getMessage(), 'HTTP 404')) break;

                    // Espera exponencial para 429/5xx
                    usleep(($this->retryBaseMs * 1000) * $tries);
                }
            }
            // probar siguiente modelo
        }

        // Llegamos aquí: todos fallaron
        $msg = $this->explainOpenAIError($lastErr);
        throw new RuntimeException($msg);
    }

    /* ==================== Internos ==================== */

    protected function callChatCompletions(string $model, string $system, string $user): array
    {
        $url = $this->baseUrl . '/v1/chat/completions';

        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ];
        if ($this->projectId) $headers['OpenAI-Project'] = $this->projectId;
        if ($this->orgId)     $headers['OpenAI-Organization'] = $this->orgId;

        $resp = Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->post($url, [
                'model' => $model,
                'messages' => [
                    ['role'=>'system', 'content'=>$system],
                    ['role'=>'user',   'content'=>$user],
                ],
                'temperature' => 0.2,
                'response_format' => ['type'=>'json_object'],
            ]);

        if (!$resp->ok()) {
            $body = $resp->json() ?: ['error'=>['message'=>$resp->body()]];
            $err  = Arr::get($body, 'error.message', 'Error OpenAI');
            throw new RuntimeException("OpenAI error: HTTP {$resp->status()} {$err}");
        }

        $content = (string) data_get($resp->json(), 'choices.0.message.content', '');
        $json = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($json)) {
            throw new RuntimeException('La IA no devolvió JSON válido.');
        }
        return $json;
    }

    protected function buildUserPrompt(string $prompt, array $ctx): string
    {
        $parts = [];
        if ($folio = data_get($ctx, 'ticket.folio'))     $parts[] = "Ticket: {$folio}";
        if ($etapa = data_get($ctx, 'stage.name'))       $parts[] = "Etapa: {$etapa}";
        if ($tipo = data_get($ctx, 'ticket.type'))       $parts[] = "Tipo: {$tipo}";
        if ($prio = data_get($ctx, 'ticket.priority'))   $parts[] = "Prioridad: {$prio}";
        if ($cli  = data_get($ctx, 'ticket.client'))     $parts[] = "Cliente: {$cli}";

        $ctxStr = empty($parts) ? '' : ("Contexto:\n- ".implode("\n- ", $parts)."\n\n");
        return $ctxStr . "Genera un checklist VERIFICABLE (8–12 puntos) para:\n\"{$prompt}\"";
    }

    protected function explainOpenAIError($e): string
    {
        $msg = $e ? (string) $e->getMessage() : 'Error desconocido';
        if (str_contains($msg, 'HTTP 403')) {
            return 'Tu Project no tiene acceso al modelo configurado. Habilítalo en OpenAI o cambia OPENAI_PRIMARY_MODEL a uno disponible.';
        }
        if (str_contains($msg, 'Rate limit') || str_contains($msg, '429')) {
            return 'Límite de velocidad alcanzado. Intenta de nuevo en unos segundos.';
        }
        return $msg;
    }
}
