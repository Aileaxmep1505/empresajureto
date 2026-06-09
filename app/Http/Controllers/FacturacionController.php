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

    public function form(PropuestaResultado $resultado)
    {
        $ganadas = $this->ganadas($resultado);
        $folio = 'FAC-' . str_pad((string) $resultado->id, 6, '0', STR_PAD_LEFT);
        $cliente = $resultado->cliente ?: optional($resultado->propuesta)->cliente ?: 'PUBLICO EN GENERAL';
        $ivaPct = (float) (optional($resultado->propuesta)->porcentaje_impuesto ?: 16);

        return view('propuestas_comerciales.facturar', compact('resultado', 'ganadas', 'folio', 'cliente', 'ivaPct'));
    }

    /** Solo arma las filas + clave de unidad (rápido). La ClaveProdServ se resuelve por AJAX. */
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
            $cacheKey = $this->cacheKeyProd($descripcion);

            $rows[] = [
                'num' => $item->partida_numero ?: ($index + 1),
                'desc' => $descripcion,
                'unidad' => $unidad,
                'cantidad' => $qty,
                'precio' => $precio,
                'importe' => round($qty * $precio, 2),
                // Si ya está en caché, la mostramos; si no, vacío y la llena el AJAX.
                'clave_prodserv' => (string) (Cache::get($cacheKey) ?: ''),
                'clave_unidad' => $this->claveUnidad($unidad),
            ];
        }

        return $rows;
    }

    /** AJAX: resuelve la ClaveProdServ de un LOTE pequeño de descripciones. */
    public function resolverProdservAjax(Request $request, PropuestaResultado $resultado)
    {
        $data = $request->validate([
            'descripciones' => ['required', 'array', 'min:1', 'max:12'],
            'descripciones.*' => ['required', 'string'],
        ]);

        $map = $this->resolverProdservLote($data['descripciones']);

        $claves = array_map(
            fn ($d) => $map[$d] ?? self::CLAVE_PRODSERV_DEFAULT,
            $data['descripciones']
        );

        return response()->json(['ok' => true, 'claves' => $claves]);
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

    /** Pipeline: IA genera sinónimos → busca candidatos reales → IA elige. */
    private function resolverProdservLote(array $descripciones): array
    {
        $result = [];
        $pendientesDesc = [];

        foreach (array_values(array_unique($descripciones)) as $desc) {
            $cached = Cache::get($this->cacheKeyProd($desc));
            if ($cached) {
                $result[$desc] = $cached;
                continue;
            }
            $pendientesDesc[] = $desc;
        }

        if (empty($pendientesDesc)) {
            return $result;
        }

        $terminos = $this->generarTerminosOpenAI($pendientesDesc);

        $candPorDesc = [];
        foreach ($pendientesDesc as $desc) {
            $cands = $this->candidatosCatalogo($desc, $terminos[$desc] ?? [], 12);
            if (empty($cands)) {
                $result[$desc] = self::CLAVE_PRODSERV_DEFAULT;
            } else {
                $candPorDesc[$desc] = $cands;
            }
        }

        if (!empty($candPorDesc)) {
            $elegidas = $this->elegirLoteOpenAI($candPorDesc);

            foreach ($candPorDesc as $desc => $cands) {
                $validas = array_column($cands, 'clave');
                $clave = $elegidas[$desc] ?? null;

                if (!$clave || !in_array($clave, $validas, true)) {
                    $clave = $validas[0] ?? self::CLAVE_PRODSERV_DEFAULT;
                }

                if ($clave !== self::CLAVE_PRODSERV_DEFAULT) {
                    Cache::put($this->cacheKeyProd($desc), $clave, now()->addDays(30));
                }

                $result[$desc] = $clave;
            }
        }

        return $result;
    }

    private function cacheKeyProd(string $desc): string
    {
        return 'prodserv:' . md5(mb_strtolower(trim($desc)));
    }

    private function generarTerminosOpenAI(array $descs): array
    {
        $apiKey = env('OPENAI_API_KEY');
        if (!$apiKey) {
            return [];
        }

        $lista = '';
        foreach (array_values($descs) as $i => $d) {
            $n = $i + 1;
            $lista .= "{$n}. {$d}\n";
        }

        try {
            $resp = Http::withToken($apiKey)
                ->timeout(45)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => env('OPENAI_MODEL', 'gpt-4o'),
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Para CADA producto, da de 3 a 6 PALABRAS CLAVE en español (sustantivos genéricos y SINÓNIMOS) con las que el catálogo de Productos y Servicios del SAT podría nombrarlo. '
                                . 'Piensa en cómo lo clasifica el SAT, no en la marca ni el material. '
                                . 'Ejemplos: "arillo de plástico para engargolar" -> ["arillo","espiral","encuadernacion","engargolado"]; '
                                . '"folder" -> ["folder","carpeta","archivo"]; "bolígrafo" -> ["boligrafo","pluma","lapicero"]. '
                                . 'Responde SOLO JSON: {"items":[{"n":1,"terminos":["...","..."]}]}, con el mismo n de la lista.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Productos:\n" . $lista,
                        ],
                    ],
                ]);

            if ($resp->ok()) {
                $json = json_decode((string) $resp->json('choices.0.message.content'), true);
                $items = $json['items'] ?? [];
                $descsArr = array_values($descs);
                $out = [];

                if (is_array($items)) {
                    foreach ($items as $pos => $it) {
                        $n = (int) ($it['n'] ?? ($pos + 1));
                        $desc = $descsArr[$n - 1] ?? null;
                        if ($desc !== null && !empty($it['terminos']) && is_array($it['terminos'])) {
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
                    }
                }
                return $out;
            }

            Log::error('OpenAI términos falló', ['status' => $resp->status(), 'body' => $resp->body()]);
        } catch (\Throwable $e) {
            Log::error('OpenAI términos excepción', ['msg' => $e->getMessage()]);
        }

        return [];
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

    private function elegirLoteOpenAI(array $pendientes): array
    {
        $descs = array_keys($pendientes);
        $apiKey = env('OPENAI_API_KEY');

        if (!$apiKey) {
            $out = [];
            foreach ($pendientes as $desc => $cands) {
                $out[$desc] = $cands[0]['clave'] ?? self::CLAVE_PRODSERV_DEFAULT;
            }
            return $out;
        }

        $bloques = '';
        $i = 0;
        foreach ($pendientes as $desc => $cands) {
            $i++;
            $bloques .= "Producto {$i}: {$desc}\nCandidatos:\n";
            foreach ($cands as $c) {
                $bloques .= "  - {$c['clave']}: {$c['descripcion']}\n";
            }
            $bloques .= "\n";
        }

        try {
            $resp = Http::withToken($apiKey)
                ->timeout(45)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => env('OPENAI_MODEL', 'gpt-4o'),
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Eres experto en el catálogo de Productos y Servicios del SAT (CFDI 4.0). '
                                . 'Para CADA producto, identifica el objeto real (un arillo/espiral para engargolar es material de ENCUADERNACIÓN; una aguja es material de costura; un bolígrafo es instrumento de escritura) '
                                . 'y elige la clave que mejor le corresponde SOLO entre SUS PROPIOS candidatos. Ignora material, color, marca y adjetivos. '
                                . 'Responde SOLO un JSON: {"items":[{"n":1,"clave_prodserv":"########"}]}, con "n" = número del producto. '
                                . 'La clave DEBE ser una de las candidatas de ESE producto.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Lista de productos con sus candidatos (respeta el número):\n\n" . $bloques,
                        ],
                    ],
                ]);

            if ($resp->ok()) {
                $content = $resp->json('choices.0.message.content');
                $json = json_decode((string) $content, true);
                $items = $json['items'] ?? [];

                $out = [];
                if (is_array($items)) {
                    foreach ($items as $pos => $it) {
                        $n = (int) ($it['n'] ?? ($pos + 1));
                        $desc = $descs[$n - 1] ?? null;
                        if ($desc !== null) {
                            $out[$desc] = (string) ($it['clave_prodserv'] ?? '');
                        }
                    }
                }
                return $out;
            }

            Log::error('OpenAI elegir lote falló', ['status' => $resp->status(), 'body' => $resp->body()]);
        } catch (\Throwable $e) {
            Log::error('OpenAI elegir lote excepción', ['msg' => $e->getMessage()]);
        }

        $out = [];
        foreach ($pendientes as $desc => $cands) {
            $out[$desc] = $cands[0]['clave'] ?? self::CLAVE_PRODSERV_DEFAULT;
        }
        return $out;
    }

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

        $desc = $data['descripcion'];
        $unidad = $data['unidad'] ?: 'Pieza';

        Cache::forget($this->cacheKeyProd($desc));
        $map = $this->resolverProdservLote([$desc]);

        return response()->json([
            'ok' => true,
            'clave_prodserv' => $map[$desc] ?? self::CLAVE_PRODSERV_DEFAULT,
            'clave_unidad' => $this->claveUnidad($unidad),
        ]);
    }
}