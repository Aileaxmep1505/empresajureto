<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\CatalogAiIntake;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
use App\Services\MeliSyncService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CatalogItemController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [ new Middleware('auth') ];
    }

    public function index(Request $request)
    {
        $q = CatalogItem::query();

        $s = trim((string) $request->get('s', ''));
        if ($s !== '') {
            $q->where(function ($qq) use ($s) {
                $qq->where('name', 'like', "%{$s}%")
                    ->orWhere('sku', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $q->where('status', (int) $request->integer('status'));
        }

        if ($request->boolean('featured_only')) {
            $q->where('is_featured', true);
        }

        $items = $q->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.catalog.index', [
            'items'   => $items,
            'filters' => [
                's'             => $s,
                'status'        => $request->get('status'),
                'featured_only' => $request->boolean('featured_only'),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.catalog.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', 'unique:catalog_items,slug'],
            'sku'         => ['nullable', 'string', 'max:120'],
            'price'       => ['required', 'numeric', 'min:0'],
            'sale_price'  => ['nullable', 'numeric', 'min:0'],
            'status'      => ['required', 'integer', 'in:0,1,2'], // 0=borrador 1=publicado 2=oculto
            'image_url'   => ['nullable', 'string', 'max:2048'],
            'images'      => ['nullable', 'array'],
            'images.*'    => ['nullable', 'url'],
            'is_featured' => ['nullable', 'boolean'],
            'brand_id'    => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'brand_name'  => ['nullable', 'string', 'max:120'], // usados por ML
            'model_name'  => ['nullable', 'string', 'max:120'],
            'meli_gtin'   => ['nullable', 'string', 'max:50'],  // GTIN / código de barras para ML
            'excerpt'     => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'published_at'=> ['nullable', 'date'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        if (CatalogItem::where('slug', $data['slug'])->exists()) {
            $data['slug'] = Str::slug($data['name'] . '-' . Str::random(4));
        }

        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);

        $item = CatalogItem::create($data);

        $this->dispatchMeliSync($item);

        return redirect()
            ->route('admin.catalog.edit', $item->id)
            ->with('ok', 'Producto web creado. Sincronización con Mercado Libre encolada.');
    }

    public function edit(CatalogItem $catalogItem)
    {
        return view('admin.catalog.edit', ['item' => $catalogItem]);
    }

    public function update(Request $request, CatalogItem $catalogItem)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', 'unique:catalog_items,slug,' . $catalogItem->id],
            'sku'         => ['nullable', 'string', 'max:120'],
            'price'       => ['required', 'numeric', 'min:0'],
            'sale_price'  => ['nullable', 'numeric', 'min:0'],
            'status'      => ['required', 'integer', 'in:0,1,2'],
            'image_url'   => ['nullable', 'string', 'max:2048'],
            'images'      => ['nullable', 'array'],
            'images.*'    => ['nullable', 'url'],
            'is_featured' => ['nullable', 'boolean'],
            'brand_id'    => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'brand_name'  => ['nullable', 'string', 'max:120'],
            'model_name'  => ['nullable', 'string', 'max:120'],
            'meli_gtin'   => ['nullable', 'string', 'max:50'], // GTIN / código de barras para ML
            'excerpt'     => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'published_at'=> ['nullable', 'date'],
        ]);

        $data['slug']        = $data['slug'] ?: Str::slug($data['name']);
        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);

        $catalogItem->update($data);

        $this->dispatchMeliSync($catalogItem);

        return back()->with('ok', 'Producto web actualizado. Sincronización con Mercado Libre encolada.');
    }

    public function destroy(CatalogItem $catalogItem)
    {
        $catalogItem->delete();
        $this->dispatchMeliSync($catalogItem);

        return redirect()
            ->route('admin.catalog.index')
            ->with('ok', 'Producto web eliminado.');
    }

    /** Publicar/Ocultar rápido */
    public function toggleStatus(CatalogItem $catalogItem)
    {
        $catalogItem->status = $catalogItem->status == 1 ? 2 : 1;
        if ($catalogItem->status == 1 && !$catalogItem->published_at) {
            $catalogItem->published_at = now();
        }
        $catalogItem->save();

        $this->dispatchMeliSync($catalogItem);

        return back()->with('ok', 'Estado actualizado. Sincronización con Mercado Libre encolada.');
    }

    /* =========================
     |  ACCIONES MERCADO LIBRE
     ==========================*/

    public function meliPublish(CatalogItem $catalogItem, MeliSyncService $svc)
    {
        $res = $svc->sync($catalogItem, [
            'activate'           => true,
            'update_description' => true,
            'ensure_picture'     => true,
        ]);

        if ($res['ok']) {
            $msg = 'Publicado/actualizado en Mercado Libre.';

            if ($catalogItem->meli_item_id || !empty($res['json']['id'] ?? null)) {
                $mlId = $res['json']['id'] ?? $catalogItem->meli_item_id;
                $mlSt = $res['json']['status'] ?? $catalogItem->meli_status ?? '—';
                $msg .= " Mercado Libre: ID: {$mlId} · Estado: {$mlSt}";
            }

            return back()->with('ok', $msg);
        }

        $friendly = $res['message'] ?? 'No se pudo publicar en Mercado Libre. Revisa los datos del producto.';
        return back()->with('ok', $friendly);
    }

    public function meliPause(CatalogItem $catalogItem, MeliSyncService $svc)
    {
        $res = $svc->pause($catalogItem);

        if ($res['ok']) {
            return back()->with('ok', 'Publicación pausada en Mercado Libre.');
        }

        $friendly = $res['message'] ?? 'No se pudo pausar la publicación en Mercado Libre.';
        return back()->with('ok', $friendly);
    }

    public function meliActivate(CatalogItem $catalogItem, MeliSyncService $svc)
    {
        $res = $svc->activate($catalogItem);

        if ($res['ok']) {
            return back()->with('ok', 'Publicación activada en Mercado Libre.');
        }

        $friendly = $res['message'] ?? 'No se pudo activar la publicación en Mercado Libre.';
        return back()->with('ok', $friendly);
    }

    public function meliView(CatalogItem $catalogItem)
    {
        if (!$catalogItem->meli_item_id) {
            return back()->with('ok', 'Este producto aún no tiene publicación en ML.');
        }

        $http = \App\Services\MeliHttp::withFreshToken();
        $resp = $http->get("https://api.mercadolibre.com/items/{$catalogItem->meli_item_id}");
        if ($resp->failed()) {
            return back()->with('ok', 'No se pudo obtener el permalink desde ML.');
        }

        $permalink = $resp->json('permalink');
        return $permalink
            ? redirect()->away($permalink)
            : back()->with('ok', 'Este ítem no tiene permalink disponible.');
    }

    /* =========================
     |  IA: Captura desde QR
     ==========================*/

    // POST /admin/catalog/ai/start
    public function aiStart(Request $r)
    {
        $intake = CatalogAiIntake::create([
            'token'      => Str::random(40),
            'created_by' => $r->user()->id,
            'status'     => 0,
            'source_type'=> $r->get('source_type','factura'),
            'notes'      => $r->get('notes'),
        ]);

        return response()->json([
            'ok'         => true,
            'intake_id'  => $intake->id,
            'token'      => $intake->token,
            'mobile_url' => route('intake.mobile', $intake->token),
        ]);
    }

    // GET /admin/catalog/ai/{intake}/status
    public function aiStatus(CatalogAiIntake $intake)
    {
        return response()->json([
            'status'    => $intake->status,
            'extracted' => $intake->extracted,
            'meta'      => $intake->meta,
        ]);
    }

    /* ===============================================
     |  IA: Captura desde archivos / imágenes / PDF
     |  POST /admin/catalog/ai-from-upload
     ================================================*/
       /* ===============================================
     |  IA: Captura desde archivos / imágenes / PDF
     |  POST /admin/catalog/ai-from-upload
     ================================================*/
    public function aiFromUpload(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|max:8192|mimes:jpg,jpeg,png,webp,pdf',
        ]);

        $files = $request->file('files', []);

        if (empty($files)) {
            return response()->json([
                'error' => 'No se recibieron archivos.',
            ], 422);
        }

        // === Config OpenAI ===
        $apiKey  = config('services.openai.api_key') ?: config('services.openai.key');
        $baseUrl = rtrim(config('services.openai.base_url', 'https://api.openai.com'), '/');

        // Modelo rápido (AJUSTA ESTO si tu proyecto no tiene este modelo)
        $modelId = 'gpt-4.1-mini';

        if (!$apiKey) {
            Log::warning('AI catalog error: missing OpenAI API key');
            return response()->json([
                'error' => 'Falta configurar la API key de OpenAI en el servidor.',
            ], 500);
        }

        // ==========================================
        // 1) Subir todos los archivos a /v1/files
        //    IMPORTANTE: usar attach() con filename
        // ==========================================
        $fileInputs = [];

        foreach ($files as $file) {
            if (!$file) {
                continue;
            }

            try {
                $uploadResponse = Http::withToken($apiKey)
                    ->attach(
                        'file',
                        file_get_contents($file->getRealPath()),
                        $file->getClientOriginalName() // aquí va el nombre con .pdf/.jpg/etc
                    )
                    ->post($baseUrl . '/v1/files', [
                        'purpose' => 'user_data',
                    ]);

                if (!$uploadResponse->ok()) {
                    Log::warning('AI catalog file upload error', [
                        'status' => $uploadResponse->status(),
                        'body'   => $uploadResponse->body(),
                    ]);

                    return response()->json([
                        'error' => 'Error subiendo archivo(s) a OpenAI.',
                    ], 500);
                }

                $fileId = $uploadResponse->json('id');
                if (!$fileId) {
                    Log::warning('AI catalog file upload without id', [
                        'body' => $uploadResponse->json(),
                    ]);

                    return response()->json([
                        'error' => 'OpenAI no regresó un ID de archivo.',
                    ], 500);
                }

                // Este objeto es lo que se manda como "input_file" al modelo
                $fileInputs[] = [
                    'type'    => 'input_file',
                    'file_id' => $fileId,
                ];
            } catch (\Throwable $e) {
                Log::error('AI catalog error uploading file', [
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'error' => 'Error al subir archivo(s) a OpenAI.',
                ], 500);
            }
        }

        if (empty($fileInputs)) {
            return response()->json([
                'error' => 'No se pudieron preparar los archivos para la IA.',
            ], 500);
        }

        // ==========================================
        // 2) Llamar a /v1/responses con input_file
        // ==========================================
        $systemPrompt = <<<TXT
Eres un asistente experto en catálogo de productos, comercio electrónico y Mercado Libre.

A partir de los archivos (PDF o imágenes) que te envío:
- Identifica el PRODUCTO principal (no la tienda).
- Responde con un JSON ESTRICTO con ESTA estructura (y solo esa):

{
  "name": "Nombre completo del producto",
  "slug": "slug-sugerido-en-kebab-case",
  "description": "Descripción larga en español, ordenada y con frases cortas.",
  "excerpt": "Resumen corto en una o dos frases.",
  "price": 0,
  "brand_name": "",
  "model_name": "",
  "meli_gtin": ""
}

Reglas:
- Responde ÚNICAMENTE con ese JSON y nada más.
- "price": en MXN, numérico (sin símbolos). Si no ves un precio claro, deja 0.
- "brand_name": marca comercial que ve el cliente (si no aparece, deja cadena vacía).
- "model_name": modelo si aparece; si no, cadena vacía.
- "meli_gtin": código de barras EAN/UPC si lo detectas completo; si no, cadena vacía.
TXT;

        $userText = "Analiza los archivos adjuntos (PDFs/imágenes) y genera SOLO el JSON del producto principal.";

        try {
            $response = Http::withToken($apiKey)
                ->timeout(config('services.openai.timeout', 60))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($baseUrl . '/v1/responses', [
                    'model'        => $modelId,
                    'instructions' => $systemPrompt,
                    'input'        => [
                        [
                            'role'    => 'user',
                            'content' => array_merge(
                                [
                                    [
                                        'type' => 'input_text',
                                        'text' => $userText,
                                    ],
                                ],
                                $fileInputs
                            ),
                        ],
                    ],
                    'max_output_tokens' => 1024,
                    'temperature'       => 0.1,
                ]);

            if (!$response->ok()) {
                Log::warning('AI catalog error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return response()->json([
                    'error' => 'La IA respondió con un error.',
                ], 500);
            }

            $json = $response->json();

            // ==========================================
            // Extraer el texto de salida del Response
            // ==========================================
            $rawText = null;

            if (isset($json['output']) && is_array($json['output'])) {
                foreach ($json['output'] as $outItem) {
                    if (($outItem['type'] ?? null) === 'message' && isset($outItem['content'])) {
                        foreach ($outItem['content'] as $c) {
                            if (($c['type'] ?? null) === 'output_text' && isset($c['text'])) {
                                $rawText .= $c['text'];
                            }
                        }
                    }
                }
            }

            // Fallback por si el formato cambia
            if (!$rawText && isset($json['output'][0]['content'][0]['text'])) {
                $rawText = $json['output'][0]['content'][0]['text'];
            }

            if (!$rawText) {
                Log::warning('AI catalog: no se pudo encontrar texto en la respuesta', ['json' => $json]);

                return response()->json([
                    'error' => 'No se pudo interpretar la respuesta de la IA.',
                ], 500);
            }

            $data = json_decode($rawText, true);
            if (!is_array($data)) {
                Log::warning('AI catalog: JSON inválido en salida de IA', [
                    'raw' => $rawText,
                ]);

                return response()->json([
                    'error' => 'La IA no devolvió un JSON válido.',
                ], 500);
            }

            // Normalizar un poco el price si viene como string con símbolos
            $price = $data['price'] ?? null;
            if (is_string($price)) {
                $clean = preg_replace('/[^0-9.,]/', '', $price);
                $clean = str_replace(',', '.', $clean);
                $price = is_numeric($clean) ? (float) $clean : null;
            }

            return response()->json([
                'suggestions' => [
                    'name'       => $data['name']        ?? null,
                    'slug'       => $data['slug']        ?? null,
                    'description'=> $data['description'] ?? null,
                    'excerpt'    => $data['excerpt']     ?? null,
                    'price'      => $price,
                    'brand_name' => $data['brand_name']  ?? null,
                    'model_name' => $data['model_name']  ?? null,
                    'meli_gtin'  => $data['meli_gtin']   ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error llamando a OpenAI (Files + Responses) para catálogo', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Ocurrió un error al contactar la IA.',
            ], 500);
        }
    }

    /** Dispara el sync con ML sin romper la UI si algo truena */
    private function dispatchMeliSync(CatalogItem $item): void
    {
        try {
            app(MeliSyncService::class)->sync($item, [
                'activate'           => false,
                'update_description' => false,
                'ensure_picture'     => false,
            ]);
        } catch (\Throwable $e) {
            // No romper flujo de interfaz
        }
    }
}
