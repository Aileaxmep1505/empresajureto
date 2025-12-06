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
            'slug'        => ['nullable', 'string', 'max:255'], // 👈 sin unique
            'sku'         => ['nullable', 'string', 'max:120'],
            'price'       => ['required', 'numeric', 'min:0'],
            'sale_price'  => ['nullable', 'numeric', 'min:0'],
            'stock'       => ['nullable', 'integer', 'min:0'],
            'status'      => ['required', 'integer', 'in:0,1,2'], // 0=borrador 1=publicado 2=oculto
            'image_url'   => ['nullable', 'string', 'max:2048'],
            'images'      => ['nullable', 'array'],
            'images.*'    => ['nullable', 'url'],
            'is_featured' => ['nullable', 'boolean'],
            'brand_id'    => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'brand_name'  => ['nullable', 'string', 'max:120'],
            'model_name'  => ['nullable', 'string', 'max:120'],
            'meli_gtin'   => ['nullable', 'string', 'max:50'],
            'excerpt'     => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'published_at'=> ['nullable', 'date'],
        ]);

        // 🔹 Slug base: slug enviado o nombre
        $baseSlug = isset($data['slug']) && trim($data['slug']) !== ''
            ? Str::slug($data['slug'])
            : Str::slug($data['name']);

        if ($baseSlug === '') {
            $baseSlug = Str::slug(Str::random(8));
        }

        // 🔹 Asegurar slug ÚNICO (slug, slug-1, slug-2, ...)
        $slug = $baseSlug;
        $i = 1;
        while (CatalogItem::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . ($i++);
        }
        $data['slug'] = $slug;

        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $data['stock']       = $data['stock'] ?? 0;

        $item = CatalogItem::create($data);

        // Sincronización con Mercado Libre (no rompe la UI si falla)
        $this->dispatchMeliSync($item);

        // 👉 Para peticiones AJAX (fetch desde la vista)
        if ($request->wantsJson()) {
            return response()->json([
                'ok'   => true,
                'item' => $item,
                'msg'  => 'Producto web creado.',
            ]);
        }

        // 👉 Flujo normal: regresar a create para seguir capturando
        return redirect()
            ->route('admin.catalog.create')
            ->with('ok', 'Producto web creado. Puedes seguir capturando más productos.');
    }

    public function edit(CatalogItem $catalogItem)
    {
        return view('admin.catalog.edit', ['item' => $catalogItem]);
    }

    public function update(Request $request, CatalogItem $catalogItem)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255'], // 👈 sin unique
            'sku'         => ['nullable', 'string', 'max:120'],
            'price'       => ['required', 'numeric', 'min:0'],
            'sale_price'  => ['nullable', 'numeric', 'min:0'],
            'stock'       => ['nullable', 'integer', 'min:0'],
            'status'      => ['required', 'integer', 'in:0,1,2'],
            'image_url'   => ['nullable', 'string', 'max:2048'],
            'images'      => ['nullable', 'array'],
            'images.*'    => ['nullable', 'url'],
            'is_featured' => ['nullable', 'boolean'],
            'brand_id'    => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'brand_name'  => ['nullable', 'string', 'max:120'],
            'model_name'  => ['nullable', 'string', 'max:120'],
            'meli_gtin'   => ['nullable', 'string', 'max:50'],
            'excerpt'     => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'published_at'=> ['nullable', 'date'],
        ]);

        // 🔹 Slug base igual que en store
        $baseSlug = isset($data['slug']) && trim($data['slug']) !== ''
            ? Str::slug($data['slug'])
            : Str::slug($data['name']);

        if ($baseSlug === '') {
            $baseSlug = Str::slug(Str::random(8));
        }

        // 🔹 Slug único ignorando el propio registro
        $slug = $baseSlug;
        $i = 1;
        while (
            CatalogItem::where('slug', $slug)
                ->where('id', '!=', $catalogItem->id)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . ($i++);
        }
        $data['slug']        = $slug;
        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $data['stock']       = $data['stock'] ?? 0;

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
     |  => soporta múltiples productos (items[])
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

        // Modelo rápido (ajustable)
        $modelId = 'gpt-4.1-mini';

        if (!$apiKey) {
            Log::warning('AI catalog error: missing OpenAI API key');
            return response()->json([
                'error' => 'Falta configurar la API key de OpenAI en el servidor.',
            ], 500);
        }

        // 1) Subir archivos a /v1/files
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
                        $file->getClientOriginalName()
                    )
                    ->post($baseUrl.'/v1/files', [
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

        // 2) Llamar a /v1/responses pidiendo varios productos
        $systemPrompt = <<<TXT
Eres un asistente experto en catálogo de productos, papelería, equipo médico y comercio electrónico (México).

A partir de los archivos (PDF o imágenes) que te envío (facturas, remisiones, listados):
- Ignora datos de la tienda, RFC, direcciones, totales, impuestos y notas generales.
- Identifica TODOS los renglones que describan productos (conceptos de venta).
- Para cada producto, genera un objeto con esta estructura EXACTA:

{
  "name": "Nombre completo del producto",
  "slug": "slug-sugerido-en-kebab-case",
  "description": "Descripción larga en español, ordenada y con frases cortas.",
  "excerpt": "Resumen corto en una o dos frases.",
  "price": 0,
  "brand_name": "",
  "model_name": "",
  "meli_gtin": "",
  "quantity": 0
}

La RESPUESTA FINAL debe ser EXCLUSIVAMENTE un JSON con esta forma:

{
  "items": [
    { ...producto_1... },
    { ...producto_2... },
    { ...producto_3... }
  ]
}

Reglas:
- Responde ÚNICAMENTE ese JSON y nada más (sin texto adicional).
- "name": debe ser claro: tipo de producto + marca + modelo + medida o presentación si aplica.
- "slug": en kebab-case, basado en el nombre (sin tildes, sin símbolos, solo letras, números y guiones).
- "price": en MXN, numérico (sin símbolo $). Usa el precio unitario si aparece; si no hay, usa 0.
- "brand_name": marca comercial que ve el cliente (Bic, Azor, Steris, Olympus, etc.). Si no aparece, cadena vacía.
- "model_name": modelo o referencia del producto (por ejemplo 1488, Vision Pro, etc.). Si no aparece, cadena vacía.
- "meli_gtin": código de barras EAN/UPC si lo detectas completo (solo dígitos); si no, cadena vacía.
- "quantity": número de piezas/unidades compradas según el renglón (por ejemplo, si dice 3 cajas, quantity = 3). Si no se ve claro, usa 1.
- Si solo se ve un producto, devuelve un array con un solo elemento en "items".
- No inventes datos que claramente no aparezcan.
TXT;

        $userText = "Analiza los archivos adjuntos (PDFs/imágenes) y genera SOLO el JSON con items[], uno por producto encontrado.";

        try {
            $response = Http::withToken($apiKey)
                ->timeout(config('services.openai.timeout', 60))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($baseUrl.'/v1/responses', [
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
                    'max_output_tokens' => 2048,
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

            // Extraer texto del output
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

            // Normalizar a lista de items
            $items = [];

            if (isset($data['items']) && is_array($data['items'])) {
                $items = $data['items'];
            } elseif (is_array($data) && array_is_list($data)) {
                $items = $data;
            } else {
                $items = [$data];
            }

            $normalizedItems = [];

            foreach ($items as $row) {
                if (!is_array($row)) {
                    continue;
                }

                // Normalizar precio
                $price = $row['price'] ?? null;
                if (is_string($price)) {
                    $clean = preg_replace('/[^0-9.,]/', '', $price);
                    $clean = str_replace(',', '.', $clean);
                    $price = is_numeric($clean) ? (float) $clean : null;
                }

                // 🔹 Normalizar cantidad → stock sugerido
                $qty = $row['quantity'] ?? ($row['qty'] ?? ($row['cantidad'] ?? ($row['stock'] ?? null)));
                if (is_string($qty)) {
                    $cleanQty = preg_replace('/[^0-9]/', '', $qty);
                    $qty = is_numeric($cleanQty) ? (int) $cleanQty : null;
                }
                if ($qty !== null) {
                    $qty = max(0, (int) $qty);
                }

                $normalizedItems[] = [
                    'name'        => $row['name']        ?? null,
                    'slug'        => $row['slug']        ?? null,
                    'description' => $row['description'] ?? null,
                    'excerpt'     => $row['excerpt']     ?? null,
                    'price'       => $price,
                    'brand_name'  => $row['brand_name']  ?? null,
                    'model_name'  => $row['model_name']  ?? null,
                    'meli_gtin'   => $row['meli_gtin']   ?? null,
                    'stock'       => $qty,
                ];
            }

            if (empty($normalizedItems)) {
                return response()->json([
                    'error' => 'La IA no devolvió productos reconocibles.',
                ], 500);
            }

            $first = $normalizedItems[0];

            return response()->json([
                'suggestions' => $first,
                'items'       => $normalizedItems,
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
            // no romper la interfaz
        }
    }
}
