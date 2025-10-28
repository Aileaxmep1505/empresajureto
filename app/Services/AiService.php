<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;

/**
 * Servicio para responder como asesor humano de Jureto (papelería)
 * con prompting + historial. Incluye:
 * - Fallback de modelos y reintentos (429/5xx).
 * - Inyección de datos del cliente para saludar por nombre y pedir solo lo faltante.
 * - Limpieza del bloque <AI_META> para que el usuario NUNCA lo vea.
 * - Mensaje humano dinámico cuando solo venga AI_META (recabar máximo 3 faltantes).
 */
class AiService
{
    /** @var string Modelo principal */
    private string $primaryModel;

    /** @var string[] Modelos de respaldo */
    private array $fallbackModels;

    /** @var \OpenAI\Client|null SDK oficial (openai-php/client) si está disponible */
    private ?\OpenAI\Client $sdk = null;

    /** @var HttpClient|null Cliente HTTP de respaldo */
    private ?HttpClient $http = null;

    /** @var array Config de services.openai */
    private array $cfg;

    public function __construct()
    {
        $this->cfg = config('services.openai', []);

        // Soporta claves legacy y nuevas
        $this->primaryModel   = $this->cfg['primary'] ?? ($this->cfg['model'] ?? env('OPENAI_MODEL', 'gpt-5'));
        $this->fallbackModels = $this->cfg['fallbacks'] ?? ['gpt-4o', 'gpt-4o-mini'];

        $apiKey = $this->cfg['api_key'] ?? env('OPENAI_API_KEY');

        // ===== SDK (si está instalado) =====
        if (class_exists(\OpenAI::class) && $apiKey) {
            // Base URI para SDK DEBE incluir /v1
            $baseUri = rtrim($this->cfg['base_uri'] ?? 'https://api.openai.com/v1', '/');

            $factory = \OpenAI::factory()
                ->withApiKey($apiKey)
                ->withBaseUri($baseUri);

            if (!empty($this->cfg['org_id'])) {
                $factory = $factory->withOrganization($this->cfg['org_id']);
            }
            if (!empty($this->cfg['project_id'])) {
                $factory = $factory->withProject($this->cfg['project_id']);
            }

            $this->sdk = $factory->make();
        }

        // ===== HTTP (Guzzle) de respaldo =====
        // base_url sin /v1; construiremos las rutas según corresponda
        $baseUrl = rtrim($this->cfg['base_url'] ?? 'https://api.openai.com', '/');

        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ];
        if (!empty($this->cfg['org_id'])) {
            $headers['OpenAI-Organization'] = $this->cfg['org_id'];
        }
        if (!empty($this->cfg['project_id'])) {
            $headers['OpenAI-Project'] = $this->cfg['project_id'];
        }

        $this->http = new HttpClient([
            'base_uri'        => $baseUrl, // sin /v1
            'timeout'         => (int) ($this->cfg['timeout'] ?? 30),
            'connect_timeout' => (int) ($this->cfg['connect_timeout'] ?? 10),
            'headers'         => $headers,
        ]);
    }

    /**
     * Genera una respuesta como asesor de Jureto en español.
     *
     * @param string $lastUserMsg Último mensaje del usuario
     * @param mixed  $ticket      HelpTicket (subject/category)
     * @param array  $history     [['role'=>'user'|'assistant','content'=>string], ...]
     * @param array  $customer    Datos conocidos del cliente (opcional)
     * @return string
     */
    public function helpdeskReply(string $lastUserMsg, $ticket, array $history, array $customer = []): string
    {
        $customer = $this->normalizeCustomer($customer);
        $intent   = $this->intentFromTicket($ticket, $lastUserMsg);
        $expected = $this->expectedFieldsForIntent($intent);
        [$known, $missing] = $this->splitKnownMissing($customer, $expected);

        $system   = $this->buildSystemPrompt($ticket, $customer, $intent, $expected, $known, $missing);
        $fewShots = $this->fewShotExamples();

        $messages = array_merge(
            [['role' => 'system', 'content' => $system]],
            $fewShots,
            $this->clampHistory($history, 18),
            [['role' => 'user', 'content' => $lastUserMsg]]
        );

        $opts = [
            'temperature' => 0.4,
            'top_p'       => 0.9,
            'max_tokens'  => 700,
        ];

        $text = $this->callChat($messages, $opts);
        $text = trim((string) $text);

        // 1) Siempre limpia AI_META para que el usuario NUNCA lo vea
        $clean = $this->stripMeta($text);

        // 2) Si tras limpiar quedó vacío (la IA solo devolvió AI_META), generamos un mensaje humano pidiendo <=3 faltantes
        if ($clean === '') {
            $clean = $this->composeFollowup($customer, $intent, $missing, $known);
        }

        // 3) Si aún así está vacío, muestra un fallback amable
        if ($clean === '') {
            $first = $customer['first_name'] ?: '¡Hola!';
            $clean = "{$first}, gracias por contactarnos. ¿Podrías compartir un poco más de detalle para ayudarte mejor? 😊";
        }

        return $clean;
    }

    /**
     * Quita cualquier rastro del bloque <AI_META> ... </AI_META> (con o sin fences).
     */
    private function stripMeta(string $text): string
    {
        if ($text === '') return '';
        // Remueve code fences que envuelvan AI_META
        $text = preg_replace('/```(?:json|txt|markdown)?\s*<AI_META>.*?<\/AI_META>\s*```/si', '', $text);
        // Remueve AI_META directo
        $text = preg_replace('/<AI_META>.*?<\/AI_META>/si', '', $text);
        // Limpia espacios
        $text = trim($text);
        // Evita que la IA deje "NOTA:" o aclaraciones del sistema
        $text = preg_replace('/^\s*\((?:NO|No|no).*\)\s*$/u', '', $text);
        return trim($text ?? '');
    }

    /**
     * Genera un mensaje humano corto pidiendo hasta 3 faltantes, con saludo por nombre y acción según intento.
     */
    private function composeFollowup(array $customer, string $intent, array $missing, array $known): string
    {
        $first = $customer['first_name'] ?: ($customer['name'] ?: 'Hola');
        $accion = $this->intentActionDesc($intent); // ej: "generar tu factura", "validar tu pago"
        $toAsk = $this->pickTopMissing($missing, $intent, 3);

        if (empty($toAsk)) return '';

        $bullets = array_map(fn($f) => '- ' . $this->humanLabel($f), $toAsk);

        return "Hola, {$first}. Para {$accion}, ¿me apoyas con:\n" . implode("\n", $bullets);
    }

    /**
     * Etiquetas legibles para los campos.
     */
    private function humanLabel(string $field): string
    {
        $map = [
            'name' => 'tu nombre completo',
            'email' => 'tu correo electrónico',
            'phone' => 'tu teléfono',
            'pedido' => 'el # de pedido',
            'rfc' => 'tu RFC',
            'razon_social' => 'la Razón Social',
            'uso_cfdi' => 'el Uso de CFDI (p. ej. G03)',
            'correo_factura' => 'el correo donde enviamos XML/PDF',
            'cp' => 'tu código postal',
            'ciudad' => 'tu ciudad',
            'estado' => 'tu estado',
            'productos' => 'los productos (SKU/nombre)',
            'cantidades' => 'las cantidades',
            'detalle_falta' => 'qué producto faltó',
            'evidencia' => 'una foto/video como evidencia',
            'prefiere' => 'si prefieres cambio o reembolso',
            'fecha_recepcion' => 'la fecha de recepción',
            'estado_empaque' => 'el estado del empaque',
            'marca' => 'la marca del equipo',
            'modelo' => 'el modelo del equipo',
            'descripcion_fallo' => 'una descripción del fallo',
            'cuando_ocurre' => 'cuándo ocurre el fallo',
            'metodo' => 'el método de pago',
            'monto' => 'el monto pagado',
            'fecha_hora' => 'la fecha y hora del pago',
            'comprobante' => 'el comprobante/captura del pago',
            'producto_id' => 'el ID del producto',
            'problema_cuenta' => 'el problema con tu cuenta',
            'factura' => 'si requieres factura CFDI',
        ];
        return $map[$field] ?? $field;
    }

    /**
     * Elige los faltantes a preguntar, priorizando el orden de expectedFields para ese intento.
     */
    private function pickTopMissing(array $missing, string $intent, int $limit = 3): array
    {
        $expected = $this->expectedFieldsForIntent($intent);
        $ordered = array_values(array_intersect($expected, $missing));
        return array_slice($ordered ?: $missing, 0, max(1, $limit));
    }

    /**
     * Descripción corta de acción por intento.
     */
    private function intentActionDesc(string $intent): string
    {
        return match ($intent) {
            'facturacion' => 'generar tu factura',
            'pago'        => 'validar tu pago',
            'envio'       => 'revisar el estado de tu envío',
            'pedido'      => 'revisar tu pedido',
            'cotizacion'  => 'preparar tu cotización',
            'garantia'    => 'gestionar tu garantía',
            'devolucion'  => 'gestionar tu devolución',
            'soporte'     => 'ayudarte con el soporte',
            'favoritos'   => 'gestionar tus favoritos',
            'cuenta'      => 'ayudarte con tu cuenta',
            default       => 'ayudarte mejor',
        };
    }

    /**
     * Normaliza arreglo del cliente y deriva first_name si no viene.
     */
    private function normalizeCustomer(array $c): array
    {
        $name  = trim((string) ($c['name'] ?? ''));
        $first = trim((string) ($c['first_name'] ?? ''));
        if ($first === '' && $name !== '') {
            $parts = preg_split('/\s+/', $name);
            $first = $parts[0] ?? $name;
            // Si viene con prefijos (Ing., Lic.), limpia un poco
            $first = preg_replace('/^(Ing\.?|Lic\.?|Sr\.?|Sra\.?|Srta\.?)\s*/iu', '', $first);
        }

        // Aplanar direcciones para facilitar chequeo de faltantes
        $billing  = $c['billing_address']  ?? [];
        $shipping = $c['shipping_address'] ?? [];

        return [
            'id'              => $c['id']            ?? null,
            'name'            => $name               ?: null,
            'first_name'      => $first              ?: null,
            'email'           => $c['email']         ?? null,
            'phone'           => $c['phone']         ?? null,
            'rfc'             => Str::upper((string) ($c['rfc'] ?? '')) ?: null,
            'razon_social'    => $c['razon_social']  ?? null,
            'uso_cfdi'        => $c['uso_cfdi']      ?? null,
            'billing_address' => [
                'street' => $billing['street'] ?? null,
                'city'   => $billing['city']   ?? null,
                'state'  => $billing['state']  ?? null,
                'cp'     => $billing['cp']     ?? null,
            ],
            'shipping_address'=> [
                'street' => $shipping['street'] ?? null,
                'city'   => $shipping['city']   ?? null,
                'state'  => $shipping['state']  ?? null,
                'cp'     => $shipping['cp']     ?? null,
            ],
            'last_order'      => [
                'id'   => Arr::get($c, 'last_order.id'),
                'date' => Arr::get($c, 'last_order.date'),
            ],
            'preferences'     => $c['preferences'] ?? [],
        ];
    }

    /**
     * Infere el intento (categoría) desde el ticket y/o mensaje del usuario.
     */
    private function intentFromTicket($ticket, string $msg): string
    {
        $cat = Str::lower((string) ($ticket->category ?? ''));
        $txt = Str::lower($msg);

        $pairs = [
            'facturacion' => ['factura', 'cfdi', 'xml', 'pdf', 'rfc', 'uso de cfdi', 'uso cfdi'],
            'pedido'      => ['pedido', 'orden', 'fol', 'no me llegó', 'faltó', 'falta'],
            'envio'       => ['envío', 'guía', 'rastreo', 'paquetería', 'dhl', 'fedex', 'skydropx'],
            'cotizacion'  => ['cotiza', 'cotización', 'precio', 'cuánto', 'costo'],
            'garantia'    => ['garantía', 'garantia'],
            'devolucion'  => ['devolución', 'devolucion', 'reembolso', 'cambio'],
            'soporte'     => ['soporte', 'falla', 'no funciona', 'defecto'],
            'pago'        => ['pago', 'transferencia', 'comprobante', 'deposito', 'tarjeta'],
            'favoritos'   => ['favoritos', 'wishlist', 'lista de deseos'],
            'cuenta'      => ['cuenta', 'acceso', 'contraseña', 'login'],
        ];

        foreach (array_keys($pairs) as $k) {
            if (Str::contains($cat, $k)) return $k;
        }
        foreach ($pairs as $intent => $keywords) {
            foreach ($keywords as $kw) {
                if (Str::contains($txt, $kw)) return $intent;
            }
        }

        return 'otro';
    }

    /**
     * Campos esperados según intento.
     */
    private function expectedFieldsForIntent(string $intent): array
    {
        $base = ['name', 'email', 'phone'];

        $map = [
            'facturacion' => array_merge($base, ['pedido', 'rfc', 'razon_social', 'uso_cfdi', 'correo_factura']),
            'pedido'      => array_merge($base, ['pedido', 'detalle_falta', 'evidencia']),
            'envio'       => array_merge($base, ['pedido', 'cp', 'ciudad', 'estado']),
            'cotizacion'  => array_merge($base, ['productos', 'cantidades', 'cp', 'factura']),
            'garantia'    => array_merge($base, ['pedido', 'fecha_recepcion', 'estado_empaque', 'motivo', 'evidencia', 'prefiere']),
            'devolucion'  => array_merge($base, ['pedido', 'motivo', 'estado_empaque', 'evidencia', 'prefiere']),
            'soporte'     => array_merge($base, ['marca', 'modelo', 'descripcion_fallo', 'cuando_ocurre', 'evidencia']),
            'pago'        => array_merge($base, ['pedido', 'metodo', 'monto', 'fecha_hora', 'comprobante']),
            'favoritos'   => array_merge($base, ['producto_id']),
            'cuenta'      => array_merge($base, ['problema_cuenta']),
            'otro'        => $base,
        ];

        return $map[$intent] ?? $base;
    }

    /**
     * Separa campos conocidos y faltantes contra el set esperado.
     */
    private function splitKnownMissing(array $customer, array $expected): array
    {
        $known = [];
        $missing = [];

        $resolver = [
            'name'          => fn() => $customer['name'] ?? null,
            'email'         => fn() => $customer['email'] ?? null,
            'phone'         => fn() => $customer['phone'] ?? null,
            'rfc'           => fn() => $customer['rfc'] ?? null,
            'razon_social'  => fn() => $customer['razon_social'] ?? null,
            'uso_cfdi'      => fn() => $customer['uso_cfdi'] ?? null,
            'cp'            => fn() => $customer['shipping_address']['cp'] ?? $customer['billing_address']['cp'] ?? null,
            'ciudad'        => fn() => $customer['shipping_address']['city'] ?? $customer['billing_address']['city'] ?? null,
            'estado'        => fn() => $customer['shipping_address']['state'] ?? $customer['billing_address']['state'] ?? null,
            // Campos que no suelen estar en perfil:
            'pedido'            => fn() => $customer['last_order']['id'] ?? null,
            'correo_factura'    => fn() => $customer['email'] ?? null,
            'productos'         => fn() => null,
            'cantidades'        => fn() => null,
            'detalle_falta'     => fn() => null,
            'evidencia'         => fn() => null,
            'prefiere'          => fn() => null, // cambio o reembolso
            'fecha_recepcion'   => fn() => null,
            'estado_empaque'    => fn() => null,
            'marca'             => fn() => null,
            'modelo'            => fn() => null,
            'descripcion_fallo' => fn() => null,
            'cuando_ocurre'     => fn() => null,
            'metodo'            => fn() => null,
            'monto'             => fn() => null,
            'fecha_hora'        => fn() => null,
            'comprobante'       => fn() => null,
            'producto_id'       => fn() => null,
            'problema_cuenta'   => fn() => null,
            'factura'           => fn() => ($customer['preferences']['factura'] ?? null) ? 'sí' : null,
        ];

        foreach ($expected as $field) {
            $val = isset($resolver[$field]) ? $resolver[$field]() : null;
            if ($val !== null && $val !== '') {
                $known[$field] = $val;
            } else {
                $missing[] = $field;
            }
        }

        return [$known, $missing];
    }

    /**
     * Prompt de sistema con identidad/voz/proceso y guía de recabado de datos.
     * Inyecta: cliente conocido, intento, campos esperados y faltantes.
     */
    private function buildSystemPrompt($ticket, array $customer, string $intent, array $expected, array $known, array $missing): string
    {
        $subject  = $ticket?->subject ?? '';
        $category = $ticket?->category ?? '';

        $first = $customer['first_name'] ?: ($customer['name'] ?: 'cliente');

        $customerCtx = [
            'id'          => $customer['id'],
            'name'        => $customer['name'],
            'email'       => $customer['email'],
            'phone'       => $customer['phone'],
            'rfc'         => $customer['rfc'],
            'razon'       => $customer['razon_social'],
            'uso_cfdi'    => $customer['uso_cfdi'],
            'billing'     => $customer['billing_address'],
            'shipping'    => $customer['shipping_address'],
            'last_order'  => $customer['last_order'],
            'preferences' => $customer['preferences'],
        ];

        $knownJson    = json_encode($known, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $missingJson  = json_encode($missing, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $expectedJson = json_encode($expected, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $custJson     = json_encode($customerCtx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
Eres un asesor humano de **Jureto**, una empresa mexicana dedicada a la **venta de artículos de papelería** y relacionados (consumibles, oficina, escolares, impresión, etc.).
Tu objetivo: **resolver el caso del cliente** y, si faltan datos, **recabarlos con preguntas concretas** y amables. Responde **siempre en español**.

### Identidad y voz
- Saluda por su nombre: **{$first}**.
- Tono: cercano, profesional, claro. Párrafos cortos y bullets cuando ayuden.
- No inventes información: si no hay dato, **pídelo**.

### Contexto del ticket
- Subject: "{$subject}"
- Category: "{$category}"
- Intento inferido: **{$intent}**

### Cliente conocido (solo contexto, no lo listes completo si no es necesario)
{$custJson}

### Campos esperados para este intento
{$expectedJson}

### Campos ya conocidos (puedes usarlos sin volver a pedirlos)
{$knownJson}

### Campos faltantes (pregunta solo por estos)
{$missingJson}

### Reglas
1) Si faltan datos importantes, puedes responder **únicamente con el bloque <AI_META>** (sin texto visible).
2) **Nunca** muestres ni expliques el contenido de <AI_META> al usuario final (el sistema lo filtra).
3) Pide **máximo 3 datos** por turno, priorizando los más críticos para avanzar.
4) Si el cliente ya dio el # de pedido, **úsalo**; si no, pídelo cuando sea pertinente (pedido/envío/factura).
5) Propón **siguientes pasos** claros (qué harás tú y qué hará el cliente).

### Formato de salida
- Redacta como humano en español.
- **Al final**, si identificaste campos estructurados o faltantes, añade el bloque:
<AI_META>
{
  "intento": "{$intent}",
  "known": {$knownJson},
  "faltantes": {$missingJson}
}
</AI_META>
(NO agregues texto fuera del JSON dentro del bloque AI_META.)
PROMPT;
    }

    /**
     * Ejemplos breves para guiar tono/flujo.
     */
    private function fewShotExamples(): array
    {
        return [
            [
                'role' => 'user',
                'content' => "Quiero factura, mi pedido no me llegó completo."
            ],
            [
                'role' => 'assistant',
                'content' =>
                    "¡Hola! Gracias por escribirnos.\n\n" .
                    "Para **facturar** y **revisar tu pedido**, ayúdame por favor con:\n" .
                    "- # de pedido\n- RFC y Uso de CFDI (p. ej. G03)\n- Correo donde enviamos XML/PDF\n\n" .
                    "Si puedes, una foto del paquete recibido para validar el faltante. Con esto lo reviso y te confirmo el ajuste."
            ],
            [
                'role' => 'user',
                'content' => "Necesito cotización de 50 cuadernos profesionales y 30 paquetes de plumas, CP 44100."
            ],
            [
                'role' => 'assistant',
                'content' =>
                    "Perfecto. Para afinar la **cotización**:\n" .
                    "- ¿Cuadernos A4 u oficio? ¿Alguna marca preferida?\n" .
                    "- Plumas: ¿azul/negro/rojo o surtido? ¿Punta fina o media?\n" .
                    "¿Requieres factura CFDI?\n\n" .
                    "Con eso preparo total con envío a 44100 y te lo comparto."
            ],
        ];
    }

    /**
     * Mantiene el historial razonable (últimos N turnos).
     */
    private function clampHistory(array $history, int $maxTurns): array
    {
        if (count($history) <= $maxTurns) return $history;
        return array_slice($history, -$maxTurns);
    }

    /**
     * Llamado al modelo con fallback y reintentos.
     *
     * @param array $messages Mensajes Chat (system/user/assistant)
     * @param array $opts     temperature, top_p, max_tokens, stop, etc.
     * @return string
     */
    private function callChat(array $messages, array $opts = []): string
    {
        $models = array_values(array_unique(array_filter([
            $this->primaryModel,
            ...$this->fallbackModels,
        ])));

        $maxTotal        = (int) ($this->cfg['max_total_attempts'] ?? 6);
        $retriesPerModel = (int) ($this->cfg['max_retries_per_model'] ?? 2);
        $baseDelayMs     = (int) ($this->cfg['retry_base_delay_ms'] ?? 400);

        $attemptsTotal = 0;
        $lastError     = null;

        foreach ($models as $model) {
            for ($i = 0; $i <= $retriesPerModel; $i++) {
                if ($attemptsTotal >= $maxTotal) break 2;
                $attemptsTotal++;

                // ===== 1) SDK =====
                if ($this->sdk) {
                    try {
                        $payload = array_merge($opts, [
                            'model'    => $model,
                            'messages' => $messages,
                        ]);

                        // Variaciones de cliente (algunas versiones usan completions anidado)
                        try {
                            $resp = $this->sdk->chat()->create($payload);
                        } catch (\Throwable $inner) {
                            $resp = $this->sdk->chat()->completions()->create($payload);
                        }

                        $respArr = json_decode(json_encode($resp), true);
                        $text    = Arr::get($respArr, 'choices.0.message.content', '');

                        if ($text !== '') {
                            if ($model !== $this->primaryModel) {
                                Log::info('[AiService] Usando fallback SDK', ['model' => $model]);
                            }
                            return $text;
                        }

                        $lastError = 'Respuesta vacía del SDK';
                    } catch (\OpenAI\Exceptions\ErrorException $e) {
                        $status = $e->getCode();
                        $msg    = $e->getMessage();
                        $lastError = $msg;
                        Log::warning('[AiService][SDK] ErrorException', ['model' => $model, 'code' => $status, 'msg' => $msg]);

                        if (in_array($status, [403, 404], true)) break;

                        if ($status === 429 || ($status >= 500 && $status <= 599)) {
                            if ($i < $retriesPerModel) {
                                $delayMs = (int) ($baseDelayMs * (2 ** $i) + random_int(0, 120));
                                usleep($delayMs * 1000);
                                continue;
                            }
                            break;
                        }

                        break;
                    } catch (\Throwable $t) {
                        $lastError = $t->getMessage();
                        Log::error('[AiService][SDK] Excepción', ['model' => $model, 'msg' => $lastError]);
                        break;
                    }
                }

                // ===== 2) HTTP (Guzzle) =====
                try {
                    $payload = array_merge($opts, [
                        'model'    => $model,
                        'messages' => $messages,
                    ]);

                    $path = '/v1/chat/completions';
                    $res  = $this->http->post($path, ['json' => $payload]);
                    $code = $res->getStatusCode();

                    if ($code >= 200 && $code < 300) {
                        $json = json_decode((string) $res->getBody(), true);
                        $text = Arr::get($json, 'choices.0.message.content', '');
                        if ($text !== '') {
                            if ($model !== $this->primaryModel) {
                                Log::info('[AiService] Usando fallback HTTP', ['model' => $model]);
                            }
                            return $text;
                        }
                        $lastError = 'Respuesta vacía del API';
                    } else {
                        $lastError = "HTTP {$code}";
                        Log::warning('[AiService][HTTP] Código no-2xx', ['model' => $model, 'code' => $code]);
                    }
                } catch (RequestException $e) {
                    $resp   = $e->getResponse();
                    $status = $resp ? $resp->getStatusCode() : null;
                    $body   = $resp ? (string) $resp->getBody() : null;

                    $apiMsg = null;
                    if ($body) {
                        $err = json_decode($body, true);
                        $apiMsg = $err['error']['message'] ?? null;
                    }
                    $lastError = $apiMsg ?: $e->getMessage();

                    Log::error('[AiService][HTTP] RequestException', [
                        'model'  => $model,
                        'status' => $status,
                        'msg'    => $lastError,
                    ]);

                    if (in_array($status, [403, 404], true)) break;

                    if ($status === 429 || ($status !== null && $status >= 500)) {
                        if ($i < $retriesPerModel) {
                            $delayMs = (int) ($baseDelayMs * (2 ** $i) + random_int(0, 120));
                            usleep($delayMs * 1000);
                            continue;
                        }
                        break;
                    }

                    break;
                } catch (\Throwable $t) {
                    $lastError = $t->getMessage();
                    Log::critical('[AiService][HTTP] Excepción', ['model' => $model, 'msg' => $lastError]);
                    break;
                }
            }
        }

        Log::error('[AiService] Sin respuesta tras fallbacks', ['error' => $lastError]);
        return '';
    }
}
