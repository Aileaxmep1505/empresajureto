<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\CotizacionProducto;
use App\Models\Client;
use App\Models\Product;
use App\Services\Support\TextNormalizeTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;
use Symfony\Component\Process\Process;

class CotizacionAiPdfService
{
    use TextNormalizeTrait;

    public function __construct(
        protected ProductCatalogService $products,
        protected ClientService $clients
    ) {}

    // ---- Timeouts centralizados ----
    private function aiTimeout(): int      { return (int) env('PDF_AI_TIMEOUT', 600); }
    private function ocrTimeout(): int     { return (int) env('PDF_OCR_TIMEOUT', 540); }
    private function openAiTimeout(): int  { return (int) env('OPENAI_TIMEOUT', 300); }
    private function openAiRetries(): int  { return max(1, (int) env('OPENAI_RETRIES', 2)); }

    /** true si se acabó el tiempo suave */
    private function timeUp(float $deadline): bool
    {
        return microtime(true) >= ($deadline - 0.5);
    }

    public function aiParse(Request $r)
    {
        $logStepFile = storage_path('logs/ai_parse_steps.log');
        @file_put_contents($logStepFile, '['.date('c')."] ---- aiParse INICIO ----\n", FILE_APPEND);

        register_shutdown_function(function () use ($logStepFile) {
            $e = error_get_last();
            if ($e && in_array($e['type'] ?? 0, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                @file_put_contents(storage_path('logs/ai_parse_fatal.log'),
                    '['.date('c')."] {$e['message']} in {$e['file']}:{$e['line']}\n", FILE_APPEND);
                @file_put_contents($logStepFile,
                    '['.date('c')."] FATAL: {$e['message']} in {$e['file']}:{$e['line']}\n", FILE_APPEND);
            }
        });

        @ini_set('log_errors', '1');
        @ini_set('error_log', storage_path('logs/php_runtime.log'));
        @error_reporting(E_ALL);

        $softSeconds = (int) env('AI_PARSE_SOFT_SECONDS', 540);
        $startedAt   = microtime(true);
        $deadline    = $startedAt + max(60, $softSeconds);
        DB::disableQueryLog();
        @set_time_limit($softSeconds + 60);
        ini_set('max_execution_time', (string)($softSeconds + 60));

        $r->validate([
            'pdf'   => ['required','file','mimes:pdf','max:20480'],
            'pages' => ['nullable','string'],
        ]);

        if (!config('services.openai.api_key')) {
            return response()->json(['ok'=>false,'error'=>'OPENAI_API_KEY no configurado en .env'], 422);
        }

        try {
            [$pages, $wasOcr] = $this->extractPdfPagesText($r->file('pdf')->getRealPath());

            if (empty($pages) || count(array_filter($pages, fn($t)=>trim($t) !== '')) === 0) {
                return response()->json([
                    'ok' => false,
                    'error' => 'No se pudo extraer texto del PDF. Parece escaneado. Activa OCR externo (OCR_SPACE_API_KEY) o sube un PDF con texto digital.'
                ], 422);
            }

            $forced = $this->parsePagesParam((string)$r->input('pages',''), count($pages));

            $pageSummaries = [];
            foreach ($pages as $i=>$txt) {
                $t = trim(preg_replace('~\s+~u', ' ', $txt));
                $pageSummaries[] = [
                    'index'   => $i+1,
                    'preview' => mb_substr($t, 0, 1000),
                    'length'  => mb_strlen($t),
                ];
            }

            $reason = null; $relevant = [];
            if ($forced) {
                $relevant = $forced;
                $reason   = 'Páginas forzadas por el usuario.';
            } else {
                if ($this->timeUp($deadline)) {
                    $relevant = range(1, min(count($pages), 20));
                    $reason   = 'Tiempo limitado: selección heurística de primeras páginas.';
                } else {
                    $findJson = $this->callOpenAIJson(json_encode([
                        'task' => 'find_relevant_pages_for_tender',
                        'instruction' => 'Eres estricto. Devuelve sólo JSON.',
                        'document_type_hint' => 'anexo técnico, listado de insumos, bases de licitación, cotización requerida',
                        'pages' => $pageSummaries,
                        'want' => ['items', 'terms', 'client', 'delivery', 'deadlines', 'payment', 'object'],
                        'notes' => 'Ignora carátulas, firmas y anexos legales repetidos. Prioriza tablas/listados.'
                    ], JSON_UNESCAPED_UNICODE));
                    $find = $this->safeJson($findJson);
                    $relevant = array_values(array_unique(array_filter($find['relevant_pages'] ?? [], fn($n)=>is_int($n)&&$n>=1&&$n<=count($pages))));
                    if (!$relevant) { $relevant = range(1, min(count($pages), 25)); }
                    $reason = $find['reasoning'] ?? null;
                }
            }

            $joined = [];
            foreach ($relevant as $pn) {
                $txt = trim($pages[$pn-1] ?? '');
                if ($txt !== '') $joined[] = "=== PAGINA {$pn} ===\n".mb_substr($txt, 0, 15000);
            }
            $corpus = mb_substr(implode("\n\n", $joined), 0, 80000);

            // ----- extraer contacto -----
            $extractClient = function (string $raw): array {
                $emailRe = '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i';
                $telLbl  = '(?:cel|cel\.?|tel|tel\.?|telefono|teléfono|celular|whats|whatsapp|contacto)';
                $telRe   = '/'.$telLbl.'[^0-9+]*([+]?[\d][\d\s\-\(\)\.]{7,})/iu';
                $attRe   = '/\b(?:att|atn|atención|atte)\.?\s*[:\-]?\s*(.+)$/iu';

                $email = null; $phone = null; $att = null;
                if (preg_match($emailRe, $raw, $m)) { $email = trim($m[0]); }
                if (preg_match($telRe, $raw, $m)) {
                    $phone = preg_replace('/\D+/', '', $m[1]);
                } else {
                    if (preg_match('/\+?\d[\d\-\s\(\)\.]{7,}/', $raw, $m)) {
                        $digits = preg_replace('/\D+/', '', $m[0]);
                        if (strlen($digits) >= 8) $phone = $digits;
                    }
                }
                if (preg_match($attRe, $raw, $m)) { $att = trim($m[1]); }
                if (!$att && preg_match('/contacto\s*[:\-]\s*([^\n\r]+)/iu', $raw, $m)) {
                    $candidate = trim($m[1]);
                    if (!preg_match($emailRe, $candidate)) $att = $candidate;
                }

                return ['att'=>$att, 'email'=>$email, 'phone'=>$phone];
            };

            $stripContactLines = function (string $raw): string {
                $emailRe = '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i';
                $lbl = '(?:att|atn|atención|atte|cel|cel\.?|tel|tel\.?|telefono|teléfono|celular|whats|whatsapp|contacto|email|correo|correo empresarial)';
                $lines = preg_split('/\R/u', $raw) ?: [];
                $keep = [];
                foreach ($lines as $L) {
                    $Ltrim = trim($L);
                    if ($Ltrim === '') continue;
                    if (preg_match('/^'.$lbl.'\b/iu', $Ltrim)) continue;
                    if (preg_match($emailRe, $Ltrim)) continue;
                    if (preg_match('/\+?\d[\d\-\s\(\)\.]{7,}/', $Ltrim)) continue;
                    $keep[] = $L;
                }
                return implode("\n", $keep);
            };

            $clientFromText = $extractClient($corpus);
            $corpusNoContact = $stripContactLines($corpus);

            // ----- local extractor -----
            $localExtract = function (string $raw) {
                $txt   = preg_replace("/[ \t]+/u", " ", $raw);
                $txt   = preg_replace("/\r\n|\r/u", "\n", $txt);
                $lines = array_values(array_filter(array_map('trim', explode("\n", $txt)), fn($l)=>$l!==""));

                $isHdr = fn($L)=>preg_match("/^\s*(PRODUCTOS?|DESCRIPCION|DESCRIPCIÓN|CANTIDAD|CANT\.)\s*$/iu", $L);

                $buf = [];
                $current = "";
                foreach ($lines as $L) {
                    if ($isHdr($L)) continue;

                    if (preg_match("/\b\d+(?:[.,]\d+)?\s*$/u", $L)) {
                        $current = $current ? ($current.' '.$L) : $L;
                        $buf[] = trim($current);
                        $current = "";
                    } else {
                        $current = $current ? ($current.' '.$L) : $L;
                    }
                }
                if ($current !== "") $buf[] = trim($current);

                $rows = [];
                foreach ($buf as $rowLine) {
                    if (preg_match("/^(?P<desc>.+?)\s+(?P<cant>\d+(?:[.,]\d+)?)\s*$/u", $rowLine, $m)) {
                        $desc = trim($m['desc']);
                        if (preg_match('/@/',$desc) || preg_match('/\+?\d[\d\-\s\(\)\.]{7,}/',$desc)) continue;

                        $rows[] = [
                            'nombre'      => $desc,
                            'descripcion' => $desc,
                            'cantidad'    => (float) str_replace(',', '.', $m['cant']),
                            'unidad'      => 'PIEZA'
                        ];
                    } elseif (preg_match("/^(?P<cant>\d+(?:[.,]\d+)?)\s+(?P<desc>.+)$/u", $rowLine, $m)) {
                        $desc = trim($m['desc']);
                        if (preg_match('/@/',$desc) || preg_match('/\+?\d[\d\-\s\(\)\.]{7,}/',$desc)) continue;

                        $rows[] = [
                            'nombre'      => $desc,
                            'descripcion' => $desc,
                            'cantidad'    => (float) str_replace(',', '.', $m['cant']),
                            'unidad'      => 'PIEZA'
                        ];
                    } else {
                        $desc = preg_replace('/\s{2,}/u',' ', $rowLine);
                        if ($desc !== '' &&
                            !preg_match('/@/', $desc) &&
                            !preg_match('/\+?\d[\d\-\s\(\)\.]{7,}/', $desc)) {
                            $rows[] = [
                                'nombre'      => $desc,
                                'descripcion' => $desc,
                                'cantidad'    => 1,
                                'unidad'      => 'PIEZA'
                            ];
                        }
                    }
                }
                return $rows;
            };

            // --- extracción IA
            $parsed = [];
            if ($this->timeUp($deadline)) {
                $parsed['items'] = $this->fallbackListExtractor($corpusNoContact);
                $parsed['pages_overview'] = $this->buildPagesOverviewFallback($relevant, $pages);
            } else {
                $extractJson = $this->callOpenAIJson(<<<PR
Devuelve SOLO JSON con:
{
 "cliente_nombre": string|null,
 "cliente_email": string|null,
 "cliente_telefono": string|null,
 "licitacion": {
   "titulo_u_objeto": string|null,
   "procedimiento": string|null,
   "dependencia_o_unidad": string|null,
   "lote_o_partidas": number|null,
   "lugar_entrega": string|null,
   "fechas_clave": {
     "publicacion": string|null,
     "aclaraciones": string|null,
     "presentacion": string|null,
     "fallo": string|null,
     "vigencia_cotizacion_dias": number|null
   },
   "condiciones_pago": string|null,
   "moneda": "MXN"|"USD"|string|null
 },
 "resumen_general": string,
 "pages_overview": [ {"page": number, "bullets": [string, ...]} ],
 "items": [ { "nombre": string, "descripcion": string|null, "cantidad": number|null, "unidad": string|null } ]
}
Reglas: NO tomes precios del PDF. Cantidad=1 si falta. Si es escuela o dependencia mexicana, menciónalo.
TEXTO (sin líneas de contacto):
{$corpusNoContact}
PR);
                $parsed = $this->safeJson($extractJson) ?: [];
                if (empty($parsed['items'])) $parsed['items'] = $this->fallbackListExtractor($corpusNoContact);
                if (empty($parsed['pages_overview']) || !is_array($parsed['pages_overview'])) {
                    $parsed['pages_overview'] = $this->buildPagesOverviewFallback($relevant, $pages);
                }
            }

            // fusión con local
            $localItems = $localExtract($corpusNoContact);

            $byKey = [];
            $norm = fn($s)=> preg_replace('~\s+~u',' ', mb_strtolower(trim((string)$s)));
            foreach (($parsed['items'] ?? []) as $it) {
                $k = $norm(($it['descripcion'] ?? '') ?: ($it['nombre'] ?? ''));
                if ($k==='') continue;
                $byKey[$k] = [
                    'nombre'      => $it['nombre'] ?? $it['descripcion'] ?? '',
                    'descripcion' => $it['descripcion'] ?? $it['nombre'] ?? '',
                    'cantidad'    => max(1,(float)($it['cantidad'] ?? 1)),
                    'unidad'      => $it['unidad'] ?? 'PIEZA',
                ];
            }
            foreach ($localItems as $it) {
                $k = $norm($it['descripcion']);
                if (!isset($byKey[$k])) {
                    $byKey[$k] = $it;
                } else {
                    if (($byKey[$k]['cantidad'] ?? 1) < ($it['cantidad'] ?? 1)) {
                        $byKey[$k]['cantidad'] = $it['cantidad'];
                    }
                }
            }
            $parsed['items'] = array_values($byKey);

            // pool (compat)
            $this->products->getProductPool();

            // mapear items al catálogo
            $mapped = [];
            $pendientes = [];
            $totalItems = count($parsed['items'] ?? []);
            $procesados = 0;
            $timedOut   = false;

            foreach (($parsed['items'] ?? []) as $row) {
                if ($this->timeUp($deadline)) { $timedOut = true; break; }

                $row['descripcion'] = trim(($row['descripcion'] ?? '').' '.($row['nombre'] ?? '')) ?: ($row['nombre'] ?? null);
                $alts = $this->products->topCandidatesForRow($row, 6);
                $qty  = max(1,(float)($row['cantidad'] ?? 1));

                if (!$alts) {
                    $pendientes[] = [
                        'raw' => [
                            'nombre' => $row['nombre'] ?? null,
                            'descripcion' => $row['descripcion'] ?? null,
                            'cantidad' => $qty,
                            'unidad' => $row['unidad'] ?? null,
                        ],
                        'candidatos' => [],
                        'debug_score' => null,
                    ];
                } else {
                    $best = $alts[0];
                    if (($best['score'] ?? 0) < 0.08) {
                        $pendientes[] = [
                            'raw' => [
                                'nombre' => $row['nombre'] ?? null,
                                'descripcion' => $row['descripcion'] ?? null,
                                'cantidad' => $qty,
                                'unidad' => $row['unidad'] ?? null,
                            ],
                            'candidatos' => $alts,
                            'debug_score' => $best['score'] ?? 0,
                        ];
                    } else {
                        $mapped[] = [
                            'producto_id'     => $best['id'],
                            'descripcion'     => $best['display'],
                            'cantidad'        => $qty,
                            'precio_unitario' => (float)$best['price'],
                            'descuento'       => 0,
                            'iva_porcentaje'  => 16,
                            'alternativas'    => array_slice($alts,0,3),
                        ];
                    }
                }
                $procesados++;
            }

            if ($timedOut && $procesados < $totalItems) {
                foreach (array_slice($parsed['items'], $procesados) as $row) {
                    $pendientes[] = [
                        'raw' => [
                            'nombre' => $row['nombre'] ?? null,
                            'descripcion' => $row['descripcion'] ?? null,
                            'cantidad' => max(1,(float)($row['cantidad'] ?? 1)),
                            'unidad' => $row['unidad'] ?? null,
                        ],
                        'candidatos' => [],
                        'debug_score' => null,
                        'status' => 'timeout',
                    ];
                }
            }

            // cliente
            $clienteNombre = $parsed['cliente_nombre'] ?? ($parsed['licitacion']['dependencia_o_unidad'] ?? ($clientFromText['att'] ?? null));
            $clienteEmail  = $parsed['cliente_email']  ?? ($clientFromText['email'] ?? null);
            $clienteTel    = $parsed['cliente_telefono'] ?? ($clientFromText['phone'] ?? null);

            $issuerGuess = $this->detectIssuerKind($clienteNombre, implode("\n", $pages));
            $clienteId   = $this->clients->createOrGetClientId(
                $clienteNombre,
                $clienteEmail,
                $clienteTel,
                $issuerGuess['kind'] ?? null
            );

            $summary = $this->buildTenderSummary(
                $parsed['licitacion'] ?? [],
                $parsed['resumen_general'] ?? null,
                $issuerGuess
            );

            $usedSec = microtime(true) - $startedAt;

            return response()->json([
                'ok'                 => true,
                'partial'            => $timedOut,
                'processed_items'    => $procesados,
                'total_items'        => $totalItems,
                'seconds_used'       => round($usedSec, 2),

                'ocr_used'           => $wasOcr,
                'ai_reason'          => $reason ?? null,
                'relevant_pages'     => $relevant,

                'cliente_id'         => $clienteId,
                'cliente_match_name' => $this->clients->displayClient($clienteId),
                'cliente_ai'         => [
                    'nombre'   => $clienteNombre,
                    'email'    => $clienteEmail,
                    'telefono' => $clienteTel,
                ],
                'issuer_kind'        => $issuerGuess['kind'] ?? null,
                'issuer_flags'       => $issuerGuess['flags'] ?? [],

                'summary'            => $summary,
                'pages_overview'     => $parsed['pages_overview'] ?? [],

                'moneda'             => $parsed['licitacion']['moneda'] ?? 'MXN',
                'notas'              => $parsed['resumen_general'] ?? null,
                'validez_dias'       => $parsed['licitacion']['fechas_clave']['vigencia_cotizacion_dias'] ?? 15,
                'envio_sugerido'     => 0,

                'items'              => $mapped,
                'pendientes_ai'      => $pendientes,
            ]);
        } catch (\Throwable $e) {
            Log::error('AI_PARSE_PDF', ['msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()]);

            if (str_contains($e->getMessage(),'Smalot\\PdfParser\\Parser')) {
                return response()->json(['ok'=>false,'error'=>'Falta smalot/pdfparser. composer require smalot/pdfparser'],500);
            }
            if (preg_match('~(SSL|cURL|Connection|timed out|resolve host|certificate)~i', $e->getMessage())) {
                return response()->json(['ok'=>false,'error'=>'No fue posible contactar a los servicios externos (OCR/OpenAI). Revisa conectividad/SSL del servidor.'], 422);
            }
            return response()->json(['ok'=>false,'error'=>$e->getMessage()],500);
        }
    }

    /** Crea cotización completa desde PDF */
    public function aiCreate(Request $r)
    {
        $BUDGET = $this->aiTimeout();
        @set_time_limit($BUDGET + 30);
        ini_set('max_execution_time', (string)($BUDGET + 30));
        ignore_user_abort(true);

        $r->validate([
            'pdf'   => ['required','file','mimes:pdf','max:20480'],
            'envio' => ['required','numeric','min:0'],
            'pages' => ['nullable','string'],
        ]);

        $subReq = Request::create('', 'POST', ['pages'=>(string)$r->input('pages','')]);
        $subReq->files->set('pdf', $r->file('pdf'));
        $res = $this->aiParse($subReq);
        $payload = $res->getData(true);

        if (empty($payload['ok'])) {
            return response()->json(['ok'=>false,'error'=>$payload['error'] ?? 'Error al analizar PDF'], 422);
        }

        $clienteId = (int)($payload['cliente_id'] ?? 0);
        if (!$clienteId || !Client::find($clienteId)) {
            return response()->json(['ok'=>false,'error'=>'No se pudo crear/recuperar el cliente.'], 422);
        }

        $items = [];
        foreach ($payload['items'] as $row) {
            if (empty($row['producto_id'])) continue;
            $p = Product::find($row['producto_id']);
            if (!$p) continue;

            $precioDb = (float)($p->price ?? $p->precio ?? 0);
            $qty = max(1,(float)($row['cantidad'] ?? 1));

            $items[] = [
                'producto_id'     => $p->id,
                'descripcion'     => $row['descripcion'] ?? ($p->nombre ?? $p->name ?? ''),
                'cantidad'        => $qty,
                'precio_unitario' => $precioDb,
                'descuento'       => 0,
                'iva_porcentaje'  => 16,
            ];
        }
        if (empty($items)) {
            return response()->json(['ok'=>false,'error'=>'No se pudo empatar ningún producto de tu catálogo.'], 422);
        }

        $cot = DB::transaction(function() use ($r, $clienteId, $payload, $items) {
            $cot = new Cotizacion();
            $cot->cliente_id   = $clienteId;
            $cot->notas        = $payload['notas'] ?? null;
            $cot->descuento    = 0;
            $cot->envio        = (float)$r->input('envio', 0);
            $cot->validez_dias = (int)($payload['validez_dias'] ?? 15);
            $cot->setValidez();
            $cot->save();

            $models = collect($items)->map(fn($it)=> new CotizacionProducto($it));
            $cot->items()->saveMany($models);
            $cot->load('items');
            $cot->recalcularTotales();
            $cot->save();

            return $cot;
        });

        return response()->json([
            'ok' => true,
            'cotizacion_id' => $cot->id,
            'folio' => $cot->folio,
            'redirect' => route('cotizaciones.show', $cot),
        ]);
    }

    // =================== PDF / OCR ===================

    private function extractPdfPagesText(string $path): array
    {
        if (!is_file($path)) throw new \RuntimeException("PDF no encontrado: $path");
        if (!class_exists(PdfParser::class)) throw new \RuntimeException("Falta smalot/pdfparser");

        try {
            $parser = new PdfParser();
            $pdf    = $parser->parseFile($path);
            $pages  = $pdf->getPages();
            $texts  = array_map(fn($p)=>$p->getText() ?? '', $pages);

            $hasText = array_reduce($texts, fn($c,$t)=> $c || (trim($t) !== ''), false);
            if ($hasText) {
                return [$texts, false];
            }
        } catch (\Throwable $e) {
            Log::info('Smalot parse falló, posible PDF escaneado: '.$e->getMessage());
        }

        $ocrKey = env('OCR_SPACE_API_KEY');
        if (!$ocrKey || trim($ocrKey) === '') $ocrKey = 'K82192623888957';

        if ($ocrKey) {
            try {
                [$textFromApi, $wasOk] = $this->ocrSpace($path, $ocrKey);
                if ($wasOk && trim($textFromApi) !== '') {
                    return [[ $textFromApi ], true];
                }
            } catch (\Throwable $e) {
                Log::error('OCR.space error', ['msg'=>$e->getMessage()]);
            }
        }

        return [[], false];
    }

    private function ocrSpace(string $pdfPath, string $apiKey): array
    {
        $stream = fopen($pdfPath, 'rb');
        if ($stream === false) {
            throw new \RuntimeException("No se pudo abrir el PDF para OCR: $pdfPath");
        }

        try {
            $response = Http::timeout(max(60, (int) env('PDF_OCR_TIMEOUT', 120)))
                ->attach('file', $stream, basename($pdfPath))
                ->asMultipart()
                ->post('https://api.ocr.space/parse/image', [
                    'apikey'             => $apiKey,
                    'language'           => 'spa',
                    'isOverlayRequired'  => 'false',
                    'OCREngine'          => '2',
                ]);

            @file_put_contents(
                storage_path('logs/ocr_space.log'),
                '['.date('c')."] status={$response->status()} len=".strlen($response->body())." body=".substr($response->body(),0,600)."\n",
                FILE_APPEND
            );

            if ($response->failed()) {
                throw new \RuntimeException('OCR HTTP '.$response->status().': '.substr($response->body(),0,400));
            }

            $obj = $response->json();
            if (!is_array($obj) || empty($obj['ParsedResults'][0]['ParsedText'])) {
                return ['', false];
            }
            return [ (string) $obj['ParsedResults'][0]['ParsedText'], true ];
        } finally {
            fclose($stream);
        }
    }

    // =================== OpenAI ===================

    private function callOpenAIJson(string $prompt, ?int $timeout = null): ?string
    {
        $key = config('services.openai.api_key'); if(!$key) return null;

        $payload = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role'=>'system','content'=>'Responde estrictamente con JSON válido.'],
                ['role'=>'user','content'=>$prompt],
            ],
            'temperature'=>0.1,
            'response_format' => ['type'=>'json_object'],
        ];

        $tries = $this->openAiRetries();
        $timeout = $timeout ?? $this->openAiTimeout();

        $lastBody = null; $lastCode = null; $lastErr = null;

        for ($i=0; $i<$tries; $i++) {
            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer '.$key,
                    'Accept-Encoding: gzip',
                ],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 20,
            ]);
            $res = curl_exec($ch);
            if ($res === false){
                $lastErr = curl_error($ch);
                curl_close($ch);
                usleep((300 + 500*$i) * 1000);
                continue;
            }
            $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
            $lastBody = $res; $lastCode = $code;

            if ($code < 300) {
                $obj = json_decode($res,true);
                return $obj['choices'][0]['message']['content'] ?? null;
            }

            if (in_array($code, [429,500,502,503,504], true)) {
                usleep((600 + 800*$i) * 1000);
                continue;
            } else {
                Log::error('OpenAI HTTP', ['status'=>$code,'body'=>$res]);
                break;
            }
        }

        Log::error('OpenAI CURL', ['err'=>$lastErr, 'status'=>$lastCode, 'body'=>$lastBody]);
        return null;
    }

    private function safeJson(?string $raw): array
    {
        if (!$raw) return [];
        $raw = trim($raw);
        $raw = preg_replace('~^```(?:json)?\s*|\s*```$~m','', $raw);
        $j = json_decode($raw,true);
        return is_array($j) ? $j : [];
    }

    private function parsePagesParam(string $s, int $max): array
    {
        $s = trim($s); if ($s==='') return [];
        $out=[];
        foreach (explode(',', $s) as $part) {
            $part = trim($part);
            if (preg_match('~^(\d+)-(\d+)$~', $part, $m)) {
                $a = max(1, (int)$m[1]); $b = min($max, (int)$m[2]);
                if ($a <= $b) $out = array_merge($out, range($a,$b));
            } elseif (ctype_digit($part)) {
                $n = (int)$part; if ($n>=1 && $n<=$max) $out[]=$n;
            }
        }
        return array_values(array_unique($out));
    }

    // =================== Fallbacks y heurística ===================

    private function fallbackListExtractor(string $corpus): array
    {
        $lines = preg_split('~\R~u',$corpus) ?: [];
        $items=[];
        $units='(PZA|PZAS?|PIEZA|PIEZAS|CAJA(?:/\d+\s*PZ)?|BOLSA|MTS?|CM|M|JGO|JUEGO|PAQ|PAQUETE|ROLLO|BLISTER|KIT)';
        $rx1='~^\s*\d{1,3}\s+[A-Z0-9\-]{3,}\s+(.+?)\s+'.$units.'\b~iu';
        $rx2='~^\s*\d{1,3}\s+(.+?)\s+'.$units.'\b~iu';
        $rx3='~^\s*(?:-?\s*)?([A-ZÁÉÍÓÚÜÑ0-9][^,]{6,}?)\s+'.$units.'\b~iu';

        foreach ($lines as $ln){
            $ln = trim(preg_replace('~\s+~u',' ', $ln));
            if ($ln==='') continue;
            $nombre=null; $unidad=null;
            if (preg_match($rx1,$ln,$m)) { $nombre=trim($m[1]); $unidad=strtoupper(trim($m[2])); }
            elseif (preg_match($rx2,$ln,$m)) { $nombre=trim($m[1]); $unidad=strtoupper(trim($m[2])); }
            elseif (preg_match($rx3,$ln,$m)) { $nombre=trim($m[1]); $unidad=strtoupper(trim($m[2])); }

            if ($nombre){
                $items[] = [
                    'nombre'=>$nombre,
                    'descripcion'=>null,
                    'cantidad'=>1,
                    'unidad'=>$unidad ?? 'PZA',
                ];
            }
        }
        $seen=[]; $out=[];
        foreach ($items as $it){ $k=mb_strtolower($it['nombre']); if(isset($seen[$k])) continue; $seen[$k]=1; $out[]=$it; }
        return array_slice($out, 0, 400);
    }

    private function buildTenderSummary(array $lic, ?string $freeSummary, array $issuerGuess): array
    {
        $fc = $lic['fechas_clave'] ?? [];
        $out = [
            'titulo_u_objeto' => $lic['titulo_u_objeto'] ?? null,
            'procedimiento'   => $lic['procedimiento']   ?? null,
            'dependencia'     => $lic['dependencia_o_unidad'] ?? null,
            'lote_o_partidas' => $lic['lote_o_partidas'] ?? null,
            'lugar_entrega'   => $lic['lugar_entrega']   ?? null,
            'condiciones_pago'=> $lic['condiciones_pago']?? null,
            'moneda'          => $lic['moneda']          ?? null,
            'fechas_clave'    => [
                'publicacion'  => $fc['publicacion']  ?? null,
                'aclaraciones' => $fc['aclaraciones'] ?? null,
                'presentacion' => $fc['presentacion'] ?? null,
                'fallo'        => $fc['fallo']        ?? null,
                'vigencia_cotizacion_dias' => $fc['vigencia_cotizacion_dias'] ?? null,
            ],
            'issuer_detected' => [
                'kind'  => $issuerGuess['kind']  ?? null,
                'flags' => $issuerGuess['flags'] ?? [],
            ],
            'resumen_texto'   => $freeSummary,
        ];
        if (!$out['dependencia'] && !empty($issuerGuess['name'])) {
            $out['dependencia'] = $issuerGuess['name'];
        }
        return $out;
    }

    private function detectIssuerKind(?string $nameFromAi, string $fullText): array
    {
        $name = $nameFromAi ? trim($nameFromAi) : null;
        $txt  = mb_strtolower($fullText);

        $flags = [];
        $kind  = 'empresa';

        $govHints = ['ayuntamiento','secretaria','secretaría','dirección','coordinación','sistema dif','imss','issste','conalep','conacyt','pemex','cfe','universidad','uach','uanl','ipn','unam','h. ayuntamiento','gobierno'];
        foreach ($govHints as $h) { if (str_contains($txt, $h)) { $flags[]='gov_hint:'.$h; } }
        if (preg_match('~\b(gob\.mx|\.gob\.mx)\b~u', $txt)) $flags[]='domain_gob_mx';

        $eduHints = ['escuela','secundaria','primaria','bachillerato','preparatoria','universidad','instituto','tecnológico','jardín de niños','kínder','colegio'];
        foreach ($eduHints as $h) { if (str_contains($txt, $h)) { $flags[]='edu_hint:'.$h; } }
        if (preg_match('~\b(edu\.mx|\.edu\.mx)\b~u', $txt)) $flags[]='domain_edu_mx';

        if (array_filter($flags, fn($f)=>str_starts_with($f,'edu_hint') || $f==='domain_edu_mx')) {
            $kind = 'escuela';
        } elseif (array_filter($flags, fn($f)=>str_starts_with($f,'gov_hint') || $f==='domain_gob_mx')) {
            $kind = 'dependencia_gobierno_mx';
        }

        if (!$name) {
            if (preg_match('~(?:ayuntamiento|secretar[íi]a|universidad|colegio|instituto|tecnol[óo]gico)[^,\n]{0,120}~iu', $fullText, $m)) {
                $name = trim($m[0]);
            }
        }

        return ['kind'=>$kind, 'flags'=>$flags, 'name'=>$name];
    }

    private function buildPagesOverviewFallback(array $relevant, array $pages): array
    {
        $out = [];
        $kw = ['entrega','pago','vigencia','presentación','presentacion','fallo','aclaraciones','lugar','domicilio','contacto','correo','tel','cantidad','unidad','partida','lote','garant','plazo','envío','envio','orden','requisición','requisicion'];
        foreach ($relevant as $pn) {
            $txt = $pages[$pn-1] ?? '';
            $txt = preg_replace('~\s+~u',' ', $txt);
            $chunks = preg_split('~(?<=[\.\:\;\n])\s+~u', $txt) ?: [];
            $bul = [];
            foreach ($chunks as $c) {
                $low = mb_strtolower($c);
                foreach ($kw as $k) {
                    if (str_contains($low, $k)) { $bul[] = trim($c); break; }
                }
                if (count($bul)>=6) break;
            }
            if (!$bul) {
                $bul = array_values(array_filter(array_map('trim', array_slice($chunks,0,3))));
            }
            if ($bul) $out[] = ['page'=>$pn, 'bullets'=>$bul];
        }
        return $out;
    }
}
