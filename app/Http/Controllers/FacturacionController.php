<?php

namespace App\Http\Controllers;

use App\Models\PropuestaResultado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FacturacionController extends Controller
{
    private const CLAVE_PRODSERV_DEFAULT = '01010101';
    private const CLAVE_UNIDAD_DEFAULT = 'H87';

    /** Catálogo corto de claves de unidad SAT que puede elegir la IA (enum) + su significado para el prompt. */
    private const UNIDADES_SAT = [
        'H87' => 'Pieza',
        'EA'  => 'Elemento / unidad',
        'XBX' => 'Caja',
        'XPK' => 'Paquete',
        'XBG' => 'Bolsa',
        'KGM' => 'Kilogramo',
        'GRM' => 'Gramo',
        'LTR' => 'Litro',
        'MLT' => 'Mililitro',
        'MTR' => 'Metro',
        'CMT' => 'Centímetro',
        'E48' => 'Servicio',
        'PR'  => 'Par',
        'SET' => 'Juego / kit / set',
        'RO'  => 'Rollo',
        'XBO' => 'Botella',
        'XFL' => 'Frasco',
        'DZN' => 'Docena',
        'TNE' => 'Tonelada',
        'HUR' => 'Hora',
        'DAY' => 'Día',
        'XKT' => 'Kit',
        'A9'  => 'Tarifa',
        'ACT' => 'Actividad',
    ];

    public function form(PropuestaResultado $resultado)
    {
        $ganadas = $this->ganadas($resultado);
        $folio = 'FAC-' . str_pad((string) $resultado->id, 6, '0', STR_PAD_LEFT);
        $cliente = $resultado->cliente ?: optional($resultado->propuesta)->cliente ?: 'PUBLICO EN GENERAL';
        $ivaPct = (float) (optional($resultado->propuesta)->porcentaje_impuesto ?: 16);

        return view('propuestas_comerciales.facturar', compact('resultado', 'ganadas', 'folio', 'cliente', 'ivaPct'));
    }

    /** Solo arma las filas + clave de unidad tentativa (rápida). La IA refina ambas claves vía AJAX. */
    private function ganadas(PropuestaResultado $resultado): array
    {
        $resultado->loadMissing(['propuesta.items', 'items']);

        $saved = $resultado->items->keyBy('propuesta_comercial_item_id');
        $propuestaItems = optional($resultado->propuesta)->items
            ? $resultado->propuesta->items->sortBy('sort')->values()
            : collect();

        $rows = [];
        foreach ($propuestaItems as $index => $item) {
            $s = $saved->get($item->id);
            if ($s && $s->resultado === 'perdida') {
                continue;
            }

            $qty = (float) ($item->cantidad_cotizada ?: $item->cantidad_maxima ?: $item->cantidad_minima ?: 1);
            $precio = (float) ((optional($s)->precio_ofertado) ?: $item->precio_unitario);
            $unidad = $item->unidad_solicitada ?: 'Pieza';
            $descripcion = $item->descripcion_original ?: 'Producto';

            $claveProd = (string) (Cache::get($this->cacheKeyProd($descripcion)) ?: '');
            $claveUni = $this->claveUnidad($unidad);

            $rows[] = [
                'num' => $item->partida_numero ?: ($index + 1),
                'desc' => $descripcion,
                'unidad' => $unidad,
                'cantidad' => $qty,
                'precio' => $precio,
                'importe' => round($qty * $precio, 2),
                // Si ya está en caché la mostramos; si no, vacío y la llena el AJAX con IA.
                'clave_prodserv' => $claveProd,
                'nombre_prodserv' => $claveProd ? $this->nombreProdserv($claveProd) : '',
                'clave_unidad' => $claveUni,
                'nombre_unidad' => $this->nombreUnidad($claveUni),
            ];
        }

        return $rows;
    }

    /** AJAX: resuelve ProdServ + Unidad de un LOTE pequeño de partidas con IA. */
    public function resolverProdservAjax(Request $request, PropuestaResultado $resultado)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:12'],
            'items.*.desc' => ['required', 'string'],
            'items.*.unidad' => ['nullable', 'string', 'max:50'],
            'force' => ['sometimes', 'boolean'],
        ]);

        return response()->json([
            'ok' => true,
            'items' => $this->resolverClavesLote($data['items'], (bool) ($data['force'] ?? false)),
        ]);
    }

    public function prueba(Request $request, PropuestaResultado $resultado)
    {
        $data = $request->validate([
            'tipo' => ['required', 'in:completo,partes'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.descripcion' => ['required', 'string'],
            'items.*.cantidad' => ['required', 'numeric', 'min:0.000001'],
            'items.*.precio' => ['required', 'numeric', 'min:0'],
            'items.*.unidad' => ['nullable', 'string', 'max:50'],
            'items.*.clave_prodserv' => ['required', 'string', 'max:20'],
            'items.*.clave_unidad' => ['required', 'string', 'max:10'],
        ]);

        $ivaPct = (float) (optional($resultado->propuesta)->porcentaje_impuesto ?: 16);
        $taxRate = round($ivaPct / 100, 6);

        $lineItems = [];
        $subtotal = 0;

        foreach ($data['items'] as $row) {
            $qty = (float) $row['cantidad'];
            $price = (float) $row['precio'];
            $importe = round($qty * $price, 2);
            $subtotal += $importe;

            $lineItems[] = [
                'quantity' => $qty,
                'product' => [
                    'description' => $row['descripcion'],
                    'product_key' => $row['clave_prodserv'],
                    'unit_key' => $row['clave_unidad'],
                    'unit_name' => $row['unidad'] ?: 'Pieza',
                    'price' => $price,
                    'tax_included' => false,
                    'taxes' => [['type' => 'IVA', 'rate' => $taxRate]],
                ],
            ];
        }

        $iva = round($subtotal * $taxRate, 2);
        $total = round($subtotal + $iva, 2);

        $payload = [
            'customer' => [
                'legal_name' => $resultado->cliente ?: optional($resultado->propuesta)->cliente ?: 'PUBLICO EN GENERAL',
            ],
            'items' => $lineItems,
            'payment_form' => '99',
            'payment_method' => 'PUE',
            'use' => 'G03',
            'type' => 'I',
            'folio_number' => $resultado->id,
        ];

        return response()->json([
            'ok' => true,
            'modo' => 'prueba',
            'tipo' => $data['tipo'],
            'resumen' => [
                'partidas' => count($lineItems),
                'subtotal' => round($subtotal, 2),
                'iva' => $iva,
                'iva_pct' => $ivaPct,
                'total' => $total,
            ],
            'facturapi_payload' => $payload,
        ]);
    }

    /**
     * Pipeline por lote: IA genera sinónimos → busca candidatos reales en catálogo →
     * IA elige ProdServ (de candidatos) + Unidad (de enum SAT) en UNA sola llamada.
     * Devuelve un arreglo alineado por índice con clave + nombre de cada una.
     */
    private function resolverClavesLote(array $items, bool $force = false): array
    {
        // Normaliza entrada
        $items = array_values(array_map(fn ($it) => [
            'desc' => trim((string) ($it['desc'] ?? '')),
            'unidad' => trim((string) ($it['unidad'] ?? '')) ?: 'Pieza',
        ], $items));

        // 1) ProdServ desde caché; lo que falte (o todo, si force) va a "pendientes"
        $prodservCache = [];
        $pendIdx = [];
        foreach ($items as $i => $it) {
            if ($force) {
                Cache::forget($this->cacheKeyProd($it['desc']));
                $pendIdx[] = $i;
                continue;
            }
            $cached = Cache::get($this->cacheKeyProd($it['desc']));
            if ($cached) {
                $prodservCache[$i] = $cached;
            } else {
                $pendIdx[] = $i;
            }
        }

        // 2) Términos (sinónimos) + candidatos del catálogo, solo para pendientes
        $candPorIdx = [];
        if (!empty($pendIdx)) {
            $descsPend = [];
            foreach ($pendIdx as $i) {
                $descsPend[] = $items[$i]['desc'];
            }
            $terminos = $this->generarTerminosOpenAI(array_values(array_unique($descsPend)));

            foreach ($pendIdx as $i) {
                $desc = $items[$i]['desc'];
                $cands = $this->candidatosCatalogo($desc, $terminos[$desc] ?? [], 12);
                if (!empty($cands)) {
                    $candPorIdx[$i] = $cands;
                }
            }
        }

        // 3) Una sola llamada: el modelo elige ProdServ (de candidatos) + Unidad (enum SAT) por partida.
        $ai = $this->elegirClavesOpenAI($items, $candPorIdx);

        // 4) Ensambla con validación + fallback + nombres oficiales
        $out = [];
        foreach ($items as $i => $it) {
            // --- ProdServ ---
            if (isset($prodservCache[$i])) {
                $ps = $prodservCache[$i];
            } else {
                $cands = $candPorIdx[$i] ?? [];
                $validas = array_column($cands, 'clave');
                $ps = $ai[$i]['clave_prodserv'] ?? null;

                if (!$ps || !in_array($ps, $validas, true)) {
                    $ps = $validas[0] ?? self::CLAVE_PRODSERV_DEFAULT;
                }
                if ($ps !== self::CLAVE_PRODSERV_DEFAULT) {
                    Cache::put($this->cacheKeyProd($it['desc']), $ps, now()->addDays(30));
                }
            }

            // --- Unidad ---
            $u = $ai[$i]['clave_unidad'] ?? null;
            if (!$u || !isset(self::UNIDADES_SAT[$u])) {
                $u = $this->claveUnidad($it['unidad']); // respaldo: mapa + sat_clave_unidad
            }

            $out[$i] = [
                'clave_prodserv' => $ps,
                'nombre_prodserv' => $this->nombreProdserv($ps),
                'clave_unidad' => $u,
                'nombre_unidad' => $this->nombreUnidad($u),
            ];
        }

        return array_values($out);
    }

    private function cacheKeyProd(string $desc): string
    {
        return 'prodserv:' . md5(mb_strtolower(trim($desc)));
    }

    /** Nombre oficial (descripción) de una ClaveProdServ del catálogo SAT. */
    private function nombreProdserv(?string $clave): string
    {
        $clave = trim((string) $clave);
        if ($clave === '' || !Schema::hasTable('sat_prodserv')) {
            return '';
        }
        return (string) (DB::table('sat_prodserv')->where('clave', $clave)->value('descripcion') ?? '');
    }

    /** Nombre oficial de una clave de unidad SAT (catálogo corto interno o BD). */
    private function nombreUnidad(?string $clave): string
    {
        $clave = trim((string) $clave);
        if ($clave === '') {
            return '';
        }
        if (isset(self::UNIDADES_SAT[$clave])) {
            return self::UNIDADES_SAT[$clave];
        }
        if (Schema::hasTable('sat_clave_unidad')) {
            $n = DB::table('sat_clave_unidad')->where('clave', $clave)->value('nombre');
            if ($n) {
                return (string) $n;
            }
        }
        return '';
    }

    /** Llamada genérica a OpenAI con Structured Outputs (json_schema strict). Devuelve array o null. */
    private function openaiChatJson(array $messages, array $schema, string $schemaName = 'respuesta'): ?array
    {
        $apiKey = env('OPENAI_API_KEY');
        if (!$apiKey) {
            return null;
        }

        $effort = env('OPENAI_REASONING_EFFORT', 'low'); // pon vacío en .env para desactivar

        $payload = [
            'model' => env('OPENAI_MODEL', 'gpt-5.4-mini'),
            'messages' => $messages,
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => $schemaName,
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],
        ];
        if (!empty($effort)) {
            $payload['reasoning_effort'] = $effort;
        }

        try {
            $resp = Http::withToken($apiKey)->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', $payload);

            if ($resp->ok()) {
                $content = (string) $resp->json('choices.0.message.content');
                $json = json_decode($content, true);
                return is_array($json) ? $json : null;
            }

            Log::error('OpenAI falló', ['status' => $resp->status(), 'body' => $resp->body()]);
        } catch (\Throwable $e) {
            Log::error('OpenAI excepción', ['msg' => $e->getMessage()]);
        }

        return null;
    }

    /** Para cada descripción, genera palabras clave/sinónimos para buscar en el catálogo SAT. */
    private function generarTerminosOpenAI(array $descs): array
    {
        if (empty($descs)) {
            return [];
        }

        $lista = '';
        foreach (array_values($descs) as $i => $d) {
            $n = $i + 1;
            $lista .= "{$n}. {$d}\n";
        }

        $schema = [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['items'],
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['n', 'terminos'],
                        'properties' => [
                            'n' => ['type' => 'integer'],
                            'terminos' => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                    ],
                ],
            ],
        ];

        $messages = [
            [
                'role' => 'system',
                'content' => 'Para CADA producto da de 3 a 6 PALABRAS CLAVE en español (sustantivos genéricos y SINÓNIMOS) '
                    . 'con las que el catálogo de Productos y Servicios del SAT podría nombrarlo. Piensa en cómo lo clasifica el SAT, '
                    . 'no en la marca ni el material. Ejemplos: "arillo de plástico para engargolar" -> ["arillo","espiral","encuadernacion","engargolado"]; '
                    . '"folder" -> ["folder","carpeta","archivo"]; "bolígrafo" -> ["boligrafo","pluma","lapicero"]. Conserva el mismo "n" de la lista.',
            ],
            ['role' => 'user', 'content' => "Productos:\n" . $lista],
        ];

        $json = $this->openaiChatJson($messages, $schema, 'terminos');
        if (!$json) {
            return [];
        }

        $descsArr = array_values($descs);
        $out = [];
        foreach (($json['items'] ?? []) as $pos => $it) {
            $n = (int) ($it['n'] ?? ($pos + 1));
            $desc = $descsArr[$n - 1] ?? null;
            if ($desc === null || empty($it['terminos']) || !is_array($it['terminos'])) {
                continue;
            }
            $terms = [];
            foreach ($it['terminos'] as $t) {
                $t = mb_strtolower(trim((string) $t));
                $t = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $t);
                if (mb_strlen($t) >= 3) {
                    $terms[] = $t;
                }
            }
            $out[$desc] = array_values(array_unique($terms));
        }

        return $out;
    }

    private function candidatosCatalogo(string $descripcion, array $extra = [], int $limit = 12): array
    {
        if (!Schema::hasTable('sat_prodserv')) {
            return [];
        }

        $words = $this->palabrasClave($descripcion);
        $extra = array_values(array_filter($extra, fn ($t) => mb_strlen($t) >= 3));
        $searchTerms = array_values(array_unique(array_merge($extra, $words)));
        if (empty($searchTerms)) {
            return [];
        }

        $rows = DB::table('sat_prodserv')
            ->select('clave', 'descripcion', 'palabras_similares')
            ->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $w) {
                    $q->orWhere('descripcion', 'like', '%' . $w . '%')
                      ->orWhere('palabras_similares', 'like', '%' . $w . '%');
                }
            })
            ->limit(800)
            ->get();

        $weights = [];
        foreach ($words as $idx => $w) {
            $weights[$w] = max($weights[$w] ?? 0, max(1, 4 - $idx));
        }
        foreach ($extra as $t) {
            $weights[$t] = max($weights[$t] ?? 0, 3);
        }

        $scored = [];
        foreach ($rows as $r) {
            $hay = mb_strtolower($r->descripcion . ' ' . ($r->palabras_similares ?? ''));
            $hay = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $hay);
            $score = 0;
            foreach ($weights as $term => $w) {
                if (str_contains($hay, $term)) {
                    $score += $w;
                }
            }
            if ($score > 0) {
                $scored[] = ['clave' => $r->clave, 'descripcion' => $r->descripcion, 'score' => $score];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_map(
            fn ($c) => ['clave' => $c['clave'], 'descripcion' => $c['descripcion']],
            array_slice($scored, 0, $limit)
        );
    }

    private function palabrasClave(string $texto): array
    {
        $texto = mb_strtolower($texto);
        $texto = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $texto);
        $palabras = preg_split('/[^a-z0-9]+/', $texto);
        $palabras = array_filter($palabras, fn ($w) => mb_strlen($w) >= 4);

        return array_slice(array_values(array_unique($palabras)), 0, 6);
    }

    /** Una sola llamada: por partida elige clave_prodserv (de SUS candidatos) y clave_unidad (de UNIDADES_SAT). */
    private function elegirClavesOpenAI(array $items, array $candPorIdx): array
    {
        $listaUnidades = '';
        foreach (self::UNIDADES_SAT as $k => $v) {
            $listaUnidades .= "  {$k} = {$v}\n";
        }

        $bloques = '';
        foreach ($items as $i => $it) {
            $n = $i + 1;
            $bloques .= "Producto {$n}\n";
            $bloques .= "  Descripción: {$it['desc']}\n";
            $bloques .= "  Unidad de venta (texto): {$it['unidad']}\n";
            $cands = $candPorIdx[$i] ?? [];
            if (!empty($cands)) {
                $bloques .= "  Candidatos ProdServ:\n";
                foreach ($cands as $c) {
                    $bloques .= "    - {$c['clave']}: {$c['descripcion']}\n";
                }
            } else {
                $bloques .= "  Candidatos ProdServ: (ninguno; deja clave_prodserv vacío)\n";
            }
            $bloques .= "\n";
        }

        $schema = [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['items'],
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['n', 'clave_prodserv', 'clave_unidad'],
                        'properties' => [
                            'n' => ['type' => 'integer'],
                            'clave_prodserv' => ['type' => 'string'],
                            'clave_unidad' => ['type' => 'string', 'enum' => array_keys(self::UNIDADES_SAT)],
                        ],
                    ],
                ],
            ],
        ];

        $messages = [
            [
                'role' => 'system',
                'content' => 'Eres experto en el catálogo de Productos y Servicios del SAT (CFDI 4.0). Para CADA producto haz DOS cosas:'
                    . "\n1) clave_prodserv: identifica el OBJETO real (un arillo/espiral para engargolar es material de ENCUADERNACIÓN; "
                    . 'una aguja es material de costura; un bolígrafo es instrumento de escritura) y elige la clave SOLO entre SUS PROPIOS candidatos. '
                    . 'Ignora material, color, marca y adjetivos. Si no hay candidatos, deja clave_prodserv como "".'
                    . "\n2) clave_unidad: elige la clave de unidad SAT que mejor corresponde al texto de la unidad de venta, "
                    . "SOLO de esta lista:\n" . $listaUnidades
                    . 'Devuelve "n" igual al número del producto. La clave_prodserv DEBE ser una de las candidatas de ESE producto.',
            ],
            ['role' => 'user', 'content' => "Partidas:\n\n" . $bloques],
        ];

        $json = $this->openaiChatJson($messages, $schema, 'claves');
        if (!$json) {
            return [];
        }

        $out = [];
        foreach (($json['items'] ?? []) as $pos => $it) {
            $n = (int) ($it['n'] ?? ($pos + 1));
            $i = $n - 1;
            if (!isset($items[$i])) {
                continue;
            }
            $out[$i] = [
                'clave_prodserv' => (string) ($it['clave_prodserv'] ?? ''),
                'clave_unidad' => (string) ($it['clave_unidad'] ?? ''),
            ];
        }

        return $out;
    }

    /** Respaldo determinístico para la clave de unidad (si la IA falla o no hay API key). */
    private function claveUnidad(?string $unidad): string
    {
        $raw = trim((string) $unidad);
        if ($raw === '') {
            return self::CLAVE_UNIDAD_DEFAULT;
        }

        $clean = mb_strtolower($raw);
        $clean = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $clean);

        $map = [
            'pieza' => 'H87', 'piezas' => 'H87', 'pza' => 'H87', 'pzas' => 'H87', 'pz' => 'H87', 'pzs' => 'H87',
            'caja' => 'XBX', 'cajas' => 'XBX',
            'paquete' => 'XPK', 'paquetes' => 'XPK', 'paq' => 'XPK',
            'bolsa' => 'XBG', 'bolsas' => 'XBG',
            'kilogramo' => 'KGM', 'kilogramos' => 'KGM', 'kg' => 'KGM', 'kgs' => 'KGM',
            'gramo' => 'GRM', 'gramos' => 'GRM', 'gr' => 'GRM',
            'litro' => 'LTR', 'litros' => 'LTR', 'lt' => 'LTR', 'lts' => 'LTR',
            'mililitro' => 'MLT', 'ml' => 'MLT',
            'metro' => 'MTR', 'metros' => 'MTR', 'mt' => 'MTR',
            'centimetro' => 'CMT', 'cm' => 'CMT',
            'servicio' => 'E48', 'servicios' => 'E48',
            'par' => 'PR', 'pares' => 'PR',
            'juego' => 'SET', 'juegos' => 'SET', 'kit' => 'SET', 'set' => 'SET',
            'rollo' => 'RO', 'rollos' => 'RO',
            'botella' => 'XBO', 'botellas' => 'XBO',
            'frasco' => 'XFL', 'frascos' => 'XFL',
            'docena' => 'DZN', 'docenas' => 'DZN', 'doc' => 'DZN',
            'tonelada' => 'TNE', 'toneladas' => 'TNE', 'ton' => 'TNE',
            'hora' => 'HUR', 'horas' => 'HUR', 'hr' => 'HUR',
            'dia' => 'DAY', 'dias' => 'DAY',
            'unidad' => 'H87', 'unidades' => 'H87', 'und' => 'H87', 'un' => 'H87',
        ];

        if (isset($map[$clean])) {
            return $map[$clean];
        }

        $tokens = preg_split('/[^a-z]+/', $clean);
        foreach ($tokens as $t) {
            if ($t !== '' && isset($map[$t])) {
                return $map[$t];
            }
        }

        if (Schema::hasTable('sat_clave_unidad')) {
            foreach ($tokens as $t) {
                if (mb_strlen($t) < 3) {
                    continue;
                }
                $row = DB::table('sat_clave_unidad')
                    ->where('nombre', 'like', '%' . $t . '%')
                    ->orderByRaw('CHAR_LENGTH(nombre) ASC')
                    ->first();
                if ($row) {
                    return $row->clave;
                }
            }
        }

        return self::CLAVE_UNIDAD_DEFAULT;
    }

    public function rebuscarClave(Request $request, PropuestaResultado $resultado)
    {
        $data = $request->validate([
            'descripcion' => ['required', 'string'],
            'unidad' => ['nullable', 'string', 'max:50'],
        ]);

        $res = $this->resolverClavesLote([[
            'desc' => $data['descripcion'],
            'unidad' => $data['unidad'] ?? 'Pieza',
        ]], true); // force = true

        $r = $res[0] ?? [];

        return response()->json([
            'ok' => true,
            'clave_prodserv' => $r['clave_prodserv'] ?? self::CLAVE_PRODSERV_DEFAULT,
            'nombre_prodserv' => $r['nombre_prodserv'] ?? '',
            'clave_unidad' => $r['clave_unidad'] ?? self::CLAVE_UNIDAD_DEFAULT,
            'nombre_unidad' => $r['nombre_unidad'] ?? '',
        ]);
    }

    /** AJAX: búsqueda manual de clave (modal). Devuelve {ok, results:[{clave, texto}]}. */
    public function buscarClave(Request $request, PropuestaResultado $resultado)
    {
        $data = $request->validate([
            'tipo' => ['required', 'in:prodserv,unidad'],
            'q' => ['required', 'string', 'min:1', 'max:120'],
        ]);

        $results = $data['tipo'] === 'unidad'
            ? $this->buscarUnidad($data['q'])
            : $this->buscarProdserv($data['q']);

        return response()->json(['ok' => true, 'results' => $results]);
    }

    private function buscarProdserv(string $q): array
    {
        if (!Schema::hasTable('sat_prodserv')) {
            return [];
        }

        $clean = mb_strtolower(trim($q));
        $clean = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $clean);
        $words = array_values(array_filter(
            preg_split('/[^a-z0-9]+/', $clean),
            fn ($w) => mb_strlen($w) >= 3
        ));
        if (empty($words)) {
            $words = [$clean];
        }

        $rows = DB::table('sat_prodserv')
            ->select('clave', 'descripcion')
            ->where(function ($qb) use ($words, $q) {
                $qb->where('clave', 'like', trim($q) . '%');
                foreach ($words as $w) {
                    $qb->orWhere('descripcion', 'like', '%' . $w . '%')
                       ->orWhere('palabras_similares', 'like', '%' . $w . '%');
                }
            })
            ->limit(40)
            ->get();

        return $rows->map(fn ($r) => ['clave' => $r->clave, 'texto' => $r->descripcion])->all();
    }

    private function buscarUnidad(string $q): array
    {
        $clean = mb_strtolower(trim($q));
        $clean = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $clean);

        $results = [];
        $seen = [];

        // 1) Catálogo corto interno (UNIDADES_SAT)
        foreach (self::UNIDADES_SAT as $clave => $nombre) {
            $h = mb_strtolower($nombre);
            $h = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $h);
            if ($clean === '' || str_contains($h, $clean) || str_contains(mb_strtolower($clave), $clean)) {
                $results[] = ['clave' => $clave, 'texto' => $nombre];
                $seen[$clave] = true;
            }
        }

        // 2) Catálogo completo SAT en BD (si existe la tabla)
        if (Schema::hasTable('sat_clave_unidad') && $clean !== '') {
            $rows = DB::table('sat_clave_unidad')
                ->select('clave', 'nombre')
                ->where('nombre', 'like', '%' . $clean . '%')
                ->orWhere('clave', 'like', trim($q) . '%')
                ->orderByRaw('CHAR_LENGTH(nombre) ASC')
                ->limit(40)
                ->get();

            foreach ($rows as $r) {
                if (!isset($seen[$r->clave])) {
                    $results[] = ['clave' => $r->clave, 'texto' => $r->nombre];
                    $seen[$r->clave] = true;
                }
            }
        }

        return array_slice($results, 0, 40);
    }
}