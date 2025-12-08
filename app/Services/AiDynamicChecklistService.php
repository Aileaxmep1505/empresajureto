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

        // Año actual (solo contexto, no queremos que lo invente en los puntos)
        $year = now()->year;

        $sys = <<<SYS
Eres un asistente que genera checklists operativos VERIFICABLES para equipos
que trabajan en licitaciones públicas en México.

Responde EXCLUSIVAMENTE un JSON válido con ESTA ESTRUCTURA EXACTA:
{
  "title": "string",
  "instructions": "string",
  "items": [{"text":"string"}, ...]
}

Reglas:
- Entre 8 y 12 items máximo.
- Cada "text" debe ser una ACCIÓN breve y MEDIBLE que empiece con un verbo en imperativo
  (ej. "Verificar que...", "Confirmar que...", "Registrar...", "Comparar...").
- Usa siempre español claro, profesional y conciso.
- Adapta los puntos a la FASE de la licitación que se indique en el contexto
  (por ejemplo: análisis de bases, preguntas/aclaraciones, cotización, muestras, entrega, etc.).
- No mezcles tareas de otras fases: concéntrate en la fase indicada.
- NO inventes años concretos (2023, 2022, etc.) salvo que aparezcan
  EXPLÍCITAMENTE en el texto del usuario.
  Si necesitas hablar de vigencia, usa expresiones como:
  "vigentes", "actualizados", "del año en curso" o "última versión".
- NO inventes folios ni números de licitación ni de ticket
  (por ejemplo "TKT-2025-0001" u otros).
  Si necesitas referirte a la licitación, usa:
  "esta licitación", "el procedimiento" o términos genéricos.
- Aunque recibas el folio del ticket en el contexto, NO lo repitas dentro de los puntos.
- Nada fuera del JSON. Nada de comentarios, texto extra ni explicaciones.
SYS;

        $usr = $this->buildUserPrompt($prompt, $context, $year);

        // Para corregir casos donde la IA menciona 2023 sin que el usuario lo haya pedido
        $promptHas2023 = str_contains($prompt, '2023');

        $lastErr = null;
        $attempts = 0;

        foreach ($this->models as $model) {
            $tries = 0;
            while ($tries <= $this->retriesPerModel && $attempts < $this->maxTotalAttempts) {
                $attempts++;
                $tries++;
                try {
                    $json = $this->callChatCompletions($model, $sys, $usr);

                    // Validación mínima + saneo de texto
                    $title        = (string) data_get($json, 'title', '');
                    $instructions = (string) data_get($json, 'instructions', '');

                    $items = collect((array) data_get($json, 'items', []))
                        ->map(fn($it) => ['text' => trim((string) data_get($it, 'text', ''))])
                        // Saneamos folios inventados y años 2023 raros
                        ->map(function ($it) use ($promptHas2023) {
                            $t = $it['text'];

                            if ($t === '') {
                                return $it;
                            }

                            // 1) Quitar folios inventados tipo TKT-2025-0001
                            $t = preg_replace('/TKT-\d{4}-\d{4}/i', 'esta licitación', $t);

                            // 2) Si el usuario NUNCA mencionó 2023, pero la IA lo mete, lo corregimos
                            if (!$promptHas2023 && str_contains($t, '2023')) {
                                // Caso típico: "actualizados a 2023"
                                $t = preg_replace(
                                    '/actualizad[oa]s?\s+a\s+2023\b/i',
                                    'actualizados al año en curso',
                                    $t
                                );

                                // Si aún quedó un "2023" suelto, lo volvemos genérico
                                if (str_contains($t, '2023')) {
                                    $t = preg_replace('/\b2023\b/', 'año en curso', $t);
                                }
                            }

                            $it['text'] = trim($t);
                            return $it;
                        })
                        ->filter(fn($it) => $it['text'] !== '')
                        ->values()
                        ->all();

                    if ($title === '' || count($items) < 8 || count($items) > 12) {
                        throw new RuntimeException('La IA no devolvió 8–12 acciones medibles.');
                    }

                    return compact('title', 'instructions', 'items');
                } catch (\Throwable $e) {
                    $lastErr = $e;

                    // 403/404: cambiar de modelo de inmediato
                    if ($e instanceof RuntimeException && str_contains($e->getMessage(), 'HTTP 403')) break;
                    if ($e instanceof RuntimeException && str_contains($e->getMessage(), 'HTTP 404')) break;

                    // Espera exponencial para 429/5xx
                    usleep(($this->retryBaseMs * 1000) * $tries);
                }
            }
            // pasar al siguiente modelo
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
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user',   'content' => $user],
                ],
                'temperature'      => 0.2,
                'response_format'  => ['type' => 'json_object'],
            ]);

        if (!$resp->ok()) {
            $body = $resp->json() ?: ['error' => ['message' => $resp->body()]];
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

    /**
     * Construye el prompt de usuario con contexto de licitación pública.
     *
     * Espera cosas como:
     * - $context['ticket']['folio']
     * - $context['ticket']['type']
     * - $context['ticket']['priority']
     * - $context['ticket']['client']
     * - $context['ticket']['licitacion_phase']
     * - $context['stage']['name']
     */
    protected function buildUserPrompt(string $prompt, array $ctx, int $year): string
    {
        $parts = [];

        if ($folio = data_get($ctx, 'ticket.folio')) {
            $parts[] = "Ticket interno (solo contexto, NO repetir en los puntos): {$folio}";
        }
        if ($etapa = data_get($ctx, 'stage.name')) {
            $parts[] = "Etapa del flujo interno: {$etapa}";
        }
        if ($tipo = data_get($ctx, 'ticket.type')) {
            $parts[] = "Tipo interno de ticket: {$tipo}";
        }
        if ($prio = data_get($ctx, 'ticket.priority')) {
            $parts[] = "Prioridad interna: {$prio}";
        }
        if ($cli = data_get($ctx, 'ticket.client')) {
            $parts[] = "Cliente o institución: {$cli}";
        }

        // Fase de licitación pública (clave interna, ej. analisis_bases, cotizacion, etc.)
        if ($phase = data_get($ctx, 'ticket.licitacion_phase')) {
            $map = [
                'analisis_bases' => 'Análisis de bases y documentos de la licitación',
                'preguntas'      => 'Preguntas y aclaraciones con la convocante',
                'cotizacion'     => 'Preparación y revisión de la cotización',
                'muestras'       => 'Preparación y entrega de muestras',
                'ir_por_pedido'  => 'Gestión para ir por el pedido / contrato',
                'entrega'        => 'Entrega de bienes o servicios',
                'seguimiento'    => 'Seguimiento posterior y cierre operativo',
            ];
            $faseTexto = $map[$phase] ?? $phase;
            $parts[] = "Fase de licitación pública: {$faseTexto}. "
                     . "TODOS los puntos deben enfocarse en esta fase.";
        }

        $parts[] = "Año de trabajo actual: {$year}. "
                 . "En los puntos, evita mencionar el año como número salvo que el usuario lo haya escrito.";

        $ctxStr = '';
        if (!empty($parts)) {
            $ctxStr = "Contexto de la licitación:\n- " . implode("\n- ", $parts) . "\n\n";
        }

        return $ctxStr
            . "Genera un checklist VERIFICABLE (8–12 puntos) específicamente para:\n"
            . "\"{$prompt}\"";
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
