<?php

namespace App\Services;

use App\Models\CatalogItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MeliSyncService
{
    /** Siempre usa el host real; el “sandbox” se simula con usuarios de prueba */
    private function api(string $path): string
    {
        return 'https://api.mercadolibre.com/' . ltrim($path, '/');
    }

    /* ==========================================================
     *  FAMILY NAME (requerido por algunos flujos/validaciones)
     * ========================================================== */
    private function buildFamilyName(CatalogItem $item): string
    {
        $name  = trim((string) ($item->name ?? ''));
        $brand = trim((string) ($item->brand_name ?? ''));
        $model = trim((string) ($item->model_name ?? ''));

        $parts = [];
        if ($name !== '') $parts[] = $name;

        $nameLower = mb_strtolower($name);
        if ($brand !== '' && !str_contains($nameLower, mb_strtolower($brand))) $parts[] = $brand;
        if ($model !== '' && !str_contains($nameLower, mb_strtolower($model))) $parts[] = $model;

        $family = trim(preg_replace('/\s+/', ' ', implode(' ', $parts)));
        if ($family === '') $family = $name !== '' ? $name : 'Producto';

        return mb_substr($family, 0, 60);
    }

    /* ==========================================================
     *  SYNC PRINCIPAL
     * ========================================================== */
    public function sync(CatalogItem $item, array $options = []): array
    {
        $http = MeliHttp::withFreshToken();

        // 0) Estado de cuenta para shipping
        $meResp = $http->get($this->api('users/me'));
        $me     = $meResp->ok() ? (array) $meResp->json() : [];
        $env    = Arr::get($me, 'status.mercadoenvios', 'not_accepted'); // accepted|not_accepted|mandatory
        $shippingMode = ($env === 'accepted' || $env === 'mandatory') ? 'me2' : 'custom';

        // 0.b) Si ya existe publicación, verificar si está cerrada/cancelada
        if (!empty($item->meli_item_id)) {
            try {
                $remoteResp = $http->get($this->api("items/{$item->meli_item_id}"));
                if ($remoteResp->ok()) {
                    $remote       = (array) $remoteResp->json();
                    $remoteStatus = $remote['status'] ?? null;

                    if (in_array($remoteStatus, ['closed', 'canceled', 'cancelled', 'deleted'], true)) {
                        $item->update([
                            'meli_status'     => $remoteStatus,
                            'meli_last_error' => "La publicación anterior ({$item->meli_item_id}) está en estado '{$remoteStatus}' y ya no se puede modificar. Se creará una nueva publicación.",
                        ]);
                        $item->meli_item_id = null; // fuerza POST nuevo
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('ML check remote item failed', [
                    'catalog_item_id' => $item->id,
                    'meli_item_id'    => $item->meli_item_id,
                    'error'           => $e->getMessage(),
                ]);
            }
        }

        // 1) listing_type permitido
        $userId   = Arr::get($me, 'id');
        $listings = [];
        if ($userId) {
            $ltResp   = $http->get($this->api("users/{$userId}/available_listing_types"), ['site_id' => 'MLM']);
            $listings = $ltResp->ok() ? (array) $ltResp->json() : [];
        }
        $listingType = $item->meli_listing_type_id ?: ($listings[0]['id'] ?? 'gold_special');

        // 2) category_id (usa guardada o predice)
        $categoryId = $item->meli_category_id;
        if (!$categoryId) {
            $predResp = $http->get($this->api('sites/MLM/domain_discovery/search'), ['q' => $item->name]);
            $pred     = $predResp->ok() ? (array) $predResp->json() : [];
            $categoryId = $pred[0]['category_id'] ?? 'MLM3530';
        }

        // 3) pictures (mínimo 1)
        $pics = $this->buildPictures($item);

        // 4) attributes mínimos + autocomplete requeridos por categoría
        $attributes = $this->buildBaseAttributes($item);
        $attributes = $this->fillRequiredCategoryAttributes($http, $categoryId, $attributes);

        // 5) precio y qty
        $price = (float) ($item->sale_price ?? $item->price ?? 0);
        if ($price < 5) $price = 5.00;

        // Si quieres: max(1, (int)($item->stock ?? 1))
        $qty = max(1, (int) 1);

        // 6) payload “normal” (marketplace tradicional)
        $payload = [
            'title'              => $this->buildMeliTitle($item),
            'family_name'        => $this->buildFamilyName($item),
            'category_id'        => $categoryId,
            'price'              => $price,
            'currency_id'        => 'MXN',
            'available_quantity' => $qty,
            'buying_mode'        => 'buy_it_now',
            'listing_type_id'    => $listingType,
            'condition'          => 'new',
            'description'        => ['plain_text' => $this->plainText($item)],
            'pictures'           => $pics,
            'attributes'         => $attributes,
            'shipping'           => $shippingMode === 'me2'
                ? ['mode' => 'me2', 'local_pick_up' => true, 'free_shipping' => false]
                : ['mode' => 'custom'],
        ];

        Log::info('ML publish payload', [
            'catalog_item_id' => $item->id,
            'meli_item_id'    => $item->meli_item_id,
            'title'           => $payload['title'] ?? null,
            'family_name'     => $payload['family_name'] ?? null,
            'category_id'     => $categoryId,
        ]);

        // 7) Validación previa con ML (Listing Validator)
        $validate = $this->validateListing($http, $payload);
        if (!$validate['ok']) {
            Log::warning('ML validate details', [
                'catalog_item_id' => $item->id,
                'status'          => $validate['status'],
                'json'            => $validate['json'],
            ]);

            // Caso específico: title inválido => intentar flujo catálogo
            if ($this->isTitleInvalidForCall($validate['json'])) {
                $catalogAttempt = $this->tryCatalogListingCreate(
                    $http,
                    $item,
                    $categoryId,
                    $listingType,
                    $shippingMode,
                    $price,
                    $qty,
                    $pics
                );

                if ($catalogAttempt['ok']) {
                    $j = $catalogAttempt['json'] ?? [];

                    $item->update([
                        'meli_item_id'         => $j['id'] ?? $item->meli_item_id,
                        'meli_status'          => $j['status'] ?? 'active',
                        'meli_category_id'     => $categoryId,
                        'meli_listing_type_id' => $listingType,
                        'meli_synced_at'       => now(),
                        'meli_last_error'      => null,
                    ]);

                    return ['ok' => true, 'json' => $j];
                }

                // Si catálogo no pudo, guarda error + sugerencias OpenAI
                $friendly = $this->humanMeliError((array)($catalogAttempt['json'] ?? []));
                $aiHelp   = $this->openAiFixSuggestions($payload, (array)$validate['json'], $categoryId);

                $finalMsg = trim($friendly . "\n\n" . ($aiHelp ? ("Sugerencias automáticas:\n" . $aiHelp) : ''));

                $item->update([
                    'meli_status'          => 'error',
                    'meli_last_error'      => $finalMsg,
                    'meli_category_id'     => $categoryId,
                    'meli_listing_type_id' => $listingType,
                ]);

                return ['ok' => false, 'json' => $validate['json'], 'message' => $finalMsg];
            }

            // No es el caso title inválido: devolver mensaje + sugerencias
            $friendly = $this->humanMeliError((array)$validate['json']);
            $aiHelp   = $this->openAiFixSuggestions($payload, (array)$validate['json'], $categoryId);

            $finalMsg = trim($friendly . "\n\n" . ($aiHelp ? ("Sugerencias automáticas:\n" . $aiHelp) : ''));

            $item->update([
                'meli_status'          => 'error',
                'meli_last_error'      => $finalMsg,
                'meli_category_id'     => $categoryId,
                'meli_listing_type_id' => $listingType,
            ]);

            return ['ok' => false, 'json' => $validate['json'], 'message' => $finalMsg];
        }

        // 8) Crear/Actualizar real
        if (!empty($item->meli_item_id)) {
            $update = $payload;
            unset(
                $update['listing_type_id'],
                $update['category_id'],
                $update['buying_mode'],
                $update['currency_id'],
                $update['condition'],
                $update['description'] // descripción se hace en endpoint dedicado
            );

            if (!empty($options['activate'])) {
                $update['status'] = 'active';
            }

            $resp = $http->put($this->api("items/{$item->meli_item_id}"), $update);
            $j    = (array) $resp->json();

            if ($resp->ok() && !empty($options['update_description'])) {
                $this->upsertDescription($http, $item->meli_item_id, $this->plainText($item));
            }
        } else {
            $resp = $http->post($this->api('items'), $payload);
            $j    = (array) $resp->json();

            if ($resp->ok() && !empty($j['id']) && !empty($options['activate'])) {
                $http->put($this->api("items/{$j['id']}"), ['status' => 'active']);
            }

            if ($resp->ok() && !empty($j['id']) && !empty($options['update_description'])) {
                $this->upsertDescription($http, $j['id'], $this->plainText($item));
            }
        }

        // 9) Manejo de errores
        if ($resp->failed()) {
            $friendly = $this->humanMeliError($j);
            $aiHelp   = $this->openAiFixSuggestions($payload, $j, $categoryId);
            $finalMsg = trim($friendly . "\n\n" . ($aiHelp ? ("Sugerencias automáticas:\n" . $aiHelp) : ''));

            $item->update([
                'meli_status'          => 'error',
                'meli_last_error'      => $finalMsg,
                'meli_category_id'     => $categoryId,
                'meli_listing_type_id' => $listingType,
            ]);

            Log::warning('ML publish error', ['catalog_item_id' => $item->id, 'resp' => $j]);
            return ['ok' => false, 'json' => $j, 'message' => $finalMsg];
        }

        // 10) Éxito
        $item->update([
            'meli_item_id'          => $j['id'] ?? $item->meli_item_id,
            'meli_status'           => $j['status'] ?? $item->meli_status ?? 'active',
            'meli_category_id'      => $categoryId,
            'meli_listing_type_id'  => $listingType,
            'meli_synced_at'        => now(),
            'meli_last_error'       => null,
        ]);

        return ['ok' => true, 'json' => $j];
    }

    /* ==========================================================
     *  PAUSE / ACTIVATE
     * ========================================================== */
    public function pause(CatalogItem $item): array
    {
        if (empty($item->meli_item_id)) {
            $msg = 'Este producto aún no tiene publicación en Mercado Libre.';
            return ['ok' => false, 'json' => ['message' => $msg], 'message' => $msg];
        }

        $http = MeliHttp::withFreshToken();
        $resp = $http->put($this->api("items/{$item->meli_item_id}"), ['status' => 'paused']);
        $j    = (array) $resp->json();

        if ($resp->failed()) {
            $friendly = $this->humanMeliError($j);
            $item->update(['meli_status' => 'error', 'meli_last_error' => $friendly]);
            return ['ok' => false, 'json' => $j, 'message' => $friendly];
        }

        $item->update(['meli_status' => 'paused', 'meli_synced_at' => now(), 'meli_last_error' => null]);
        return ['ok' => true, 'json' => $j];
    }

    public function activate(CatalogItem $item): array
    {
        if (empty($item->meli_item_id)) {
            $msg = 'Este producto aún no tiene publicación en Mercado Libre.';
            return ['ok' => false, 'json' => ['message' => $msg], 'message' => $msg];
        }

        $http = MeliHttp::withFreshToken();
        $resp = $http->put($this->api("items/{$item->meli_item_id}"), ['status' => 'active']);
        $j    = (array) $resp->json();

        if ($resp->failed()) {
            $friendly = $this->humanMeliError($j);
            $item->update(['meli_status' => 'error', 'meli_last_error' => $friendly]);
            return ['ok' => false, 'json' => $j, 'message' => $friendly];
        }

        $item->update(['meli_status' => 'active', 'meli_synced_at' => now(), 'meli_last_error' => null]);
        return ['ok' => true, 'json' => $j];
    }

    /* ==========================================================
     *  VALIDATE
     * ========================================================== */
    private function validateListing($http, array $payload): array
    {
        try {
            // Listing validator: valida antes de publicar
            $resp = $http->post($this->api('items/validate'), $payload);
            if ($resp->ok()) {
                return ['ok' => true, 'status' => $resp->status(), 'json' => (array)$resp->json()];
            }
            return ['ok' => false, 'status' => $resp->status(), 'json' => (array)$resp->json()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => 0, 'json' => ['message' => 'validate_exception', 'error' => $e->getMessage()]];
        }
    }

    private function isTitleInvalidForCall(array $j): bool
    {
        $err = (string) ($j['error'] ?? '');
        $msg = (string) ($j['message'] ?? '');
        if (stripos($err, 'The fields [title] are invalid for requested call') !== false) return true;
        if (stripos($msg, 'body.invalid_fields') !== false && stripos($err, '[title]') !== false) return true;
        return false;
    }

    /* ==========================================================
     *  CATALOGO (fallback automático)
     *  - Busca catalog_product_id y publica con catalog_listing=true
     * ========================================================== */
    private function tryCatalogListingCreate(
        $http,
        CatalogItem $item,
        string $categoryId,
        string $listingType,
        string $shippingMode,
        float $price,
        int $qty,
        array $pics
    ): array {
        // Buscar sugerencias de catálogo (status=active) :contentReference[oaicite:2]{index=2}
        $catalogProductId = $this->findCatalogProductId($http, $item, $categoryId);

        if (!$catalogProductId) {
            return [
                'ok' => false,
                'json' => [
                    'message' => 'catalog_not_found',
                    'error'   => 'No se encontró catalog_product_id activo para este producto en esa categoría.',
                    'status'  => 400,
                ],
            ];
        }

        // Payload de catálogo: NO mandes title (ML controla ficha/título) :contentReference[oaicite:3]{index=3}
        $payloadCatalog = [
            'catalog_product_id'  => $catalogProductId,
            'catalog_listing'     => true,
            'category_id'         => $categoryId,
            'price'               => $price,
            'currency_id'         => 'MXN',
            'available_quantity'  => $qty,
            'buying_mode'         => 'buy_it_now',
            'listing_type_id'     => $listingType,
            'condition'           => 'new',
            'pictures'            => $pics,
            'description'         => ['plain_text' => $this->plainText($item)],
            'shipping'            => $shippingMode === 'me2'
                ? ['mode' => 'me2', 'local_pick_up' => true, 'free_shipping' => false]
                : ['mode' => 'custom'],
        ];

        Log::info('ML catalog payload (fallback)', [
            'catalog_item_id'     => $item->id,
            'category_id'         => $categoryId,
            'catalog_product_id'  => $catalogProductId,
        ]);

        $resp = $http->post($this->api('items'), $payloadCatalog);
        $j    = (array) $resp->json();

        if ($resp->failed()) {
            Log::warning('ML catalog publish error', ['catalog_item_id' => $item->id, 'resp' => $j]);
            return ['ok' => false, 'json' => $j];
        }

        return ['ok' => true, 'json' => $j];
    }

    private function findCatalogProductId($http, CatalogItem $item, string $categoryId): ?string
    {
        try {
            // /products/search?status=active&site_id=MLM&q=...&category=... :contentReference[oaicite:4]{index=4}
            $resp = $http->get($this->api('products/search'), [
                'status'  => 'active',
                'site_id' => 'MLM',
                'q'       => (string)($item->name ?? ''),
                'category'=> $categoryId,
                'limit'   => 5,
            ]);

            if (!$resp->ok()) return null;

            $j = (array) $resp->json();

            // Respuestas típicas traen "results" como lista.
            $results = $j['results'] ?? [];
            if (is_array($results) && !empty($results)) {
                $first = $results[0] ?? null;
                if (is_array($first) && !empty($first['id'])) {
                    return (string) $first['id'];
                }
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning('findCatalogProductId exception', [
                'catalog_item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /* ==========================================================
     *  OPENAI: sugerencias de corrección
     * ========================================================== */
    private function openAiFixSuggestions(array $payload, array $mlError, string $categoryId): ?string
    {
        $apiKey  = config('services.openai.api_key') ?: config('services.openai.key');
        $baseUrl = rtrim(config('services.openai.base_url', 'https://api.openai.com'), '/');
        if (!$apiKey) return null;

        $modelId = 'gpt-4.1-mini';

        $system = <<<TXT
Eres un especialista en integraciones Mercado Libre (Items API, validaciones, catálogo) para México (MLM).
Te daré:
- payload JSON que intentamos publicar
- error JSON devuelto por Mercado Libre (validate o publish)
- category_id
Tu tarea:
1) Explica en español, en viñetas cortas, qué significa el error.
2) Da pasos concretos para corregirlo.
3) Indica exactamente qué campos del formulario debe llenar el usuario (si aplica) y con ejemplos.
4) Si suena a Catálogo, explica el flujo: /products/search => catalog_product_id => POST /items con catalog_listing=true.
Reglas:
- NO inventes datos.
- NO pegues código largo.
- Máximo 10 viñetas.
TXT;

        $inputObj = [
            'category_id' => $categoryId,
            'payload'     => $payload,
            'ml_error'    => $mlError,
        ];

        try {
            $resp = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(45)
                ->post($baseUrl . '/v1/responses', [
                    'model'        => $modelId,
                    'instructions' => $system,
                    'input'        => [
                        [
                            'role'    => 'user',
                            'content' => [
                                ['type' => 'input_text', 'text' => json_encode($inputObj, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)],
                            ],
                        ],
                    ],
                    'max_output_tokens' => 500,
                    'temperature'       => 0.2,
                ]);

            if (!$resp->ok()) {
                Log::warning('OpenAI suggest failed', ['status' => $resp->status(), 'body' => $resp->body()]);
                return null;
            }

            $j = (array) $resp->json();

            $rawText = '';
            if (isset($j['output']) && is_array($j['output'])) {
                foreach ($j['output'] as $outItem) {
                    if (($outItem['type'] ?? null) === 'message' && isset($outItem['content'])) {
                        foreach ($outItem['content'] as $c) {
                            if (($c['type'] ?? null) === 'output_text' && isset($c['text'])) {
                                $rawText .= $c['text'];
                            }
                        }
                    }
                }
            }

            $rawText = trim((string)$rawText);
            return $rawText !== '' ? $rawText : null;
        } catch (\Throwable $e) {
            Log::warning('OpenAI suggest exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /* ==========================================================
     *  HELPERS: pictures / attributes / text / title
     * ========================================================== */
    private function buildPictures(CatalogItem $item): array
    {
        $pics = [];

        if (method_exists($item, 'mainPicture') && $item->mainPicture()) {
            $pics[] = ['source' => $item->mainPicture()];
        } elseif (!empty($item->image_url)) {
            $pics[] = ['source' => $item->image_url];
        }

        foreach (($item->images ?? []) as $u) {
            if ($u && (!isset($pics[0]['source']) || $u !== $pics[0]['source'])) {
                $pics[] = ['source' => $u];
            }
            if (count($pics) >= 6) break;
        }

        if (empty($pics)) {
            $pics[] = ['source' => 'https://http2.mlstatic.com/storage/developers-site-cms-admin/openapi/319102622313-testimage.jpeg'];
        }

        return $pics;
    }

    private function buildBaseAttributes(CatalogItem $item): array
    {
        $attributes = [];

        $brand = trim((string) ($item->brand_name ?? 'Genérica'));
        $model = trim((string) ($item->model_name ?? $item->sku ?? 'Modelo Único'));

        $attributes[] = ['id' => 'BRAND', 'value_name' => $brand];
        $attributes[] = ['id' => 'MODEL', 'value_name' => $model];

        $gtin = trim((string) ($item->meli_gtin ?? ''));
        if ($gtin !== '') {
            $attributes[] = ['id' => 'GTIN', 'value_name' => $gtin];
        }

        return $attributes;
    }

    private function plainText(CatalogItem $i): string
    {
        $base = $i->excerpt ?: strip_tags((string) $i->description);
        $base = trim($base) ?: "{$i->name}.\n\nVendido por JURETO. Factura disponible. Garantía estándar.\n";
        return mb_substr(preg_replace('/\s+/', ' ', $base), 0, 4800);
    }

    private function buildMeliTitle(CatalogItem $item): string
    {
        $parts = [];

        if (!empty($item->name)) $parts[] = trim($item->name);
        if (!empty($item->brand_name)) $parts[] = trim($item->brand_name);

        if (!empty($item->model_name)) $parts[] = trim($item->model_name);
        elseif (!empty($item->sku)) $parts[] = trim($item->sku);

        $title = trim(preg_replace('/\s+/', ' ', implode(' ', $parts)));

        if (mb_strlen($title) > 60) $title = mb_substr($title, 0, 60);
        return $title;
    }

    private function fetchCategoryAttributes($http, string $categoryId): array
    {
        $resp = $http->get($this->api("categories/{$categoryId}/attributes"));
        return $resp->ok() ? (array) $resp->json() : [];
    }

    private function fillRequiredCategoryAttributes($http, string $categoryId, array $attributesPayload): array
    {
        $present = [];
        foreach ($attributesPayload as $a) {
            if (!empty($a['id'])) $present[$a['id']] = true;
        }

        $defs = $this->fetchCategoryAttributes($http, $categoryId);

        foreach ($defs as $def) {
            $attrId     = $def['id'] ?? null;
            $isRequired = !empty($def['tags']['required']);

            if (!$isRequired || !$attrId || isset($present[$attrId])) continue;

            $val = null;
            if (!empty($def['values']) && is_array($def['values'])) {
                $val = $def['values'][0] ?? null;
            }

            if ($val && !empty($val['id'])) {
                $attributesPayload[] = ['id' => $attrId, 'value_id' => $val['id']];
            } else {
                $attributesPayload[] = ['id' => $attrId, 'value_name' => 'Genérico'];
            }
        }

        return $attributesPayload;
    }

    private function upsertDescription($http, string $itemId, string $plainText): void
    {
        try {
            $put = $http->put($this->api("items/{$itemId}/description"), ['plain_text' => $plainText]);
            if ($put->failed()) {
                $post = $http->post($this->api("items/{$itemId}/description"), ['plain_text' => $plainText]);
                if ($post->failed()) {
                    Log::warning('ML description upsert failed', [
                        'id'   => $itemId,
                        'put'  => $put->json(),
                        'post' => $post->json(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('ML description upsert exception: ' . $e->getMessage(), ['item' => $itemId]);
        }
    }

    private function humanMeliError(array $j): string
    {
        $causes = $j['cause'] ?? [];
        $lines  = [];

        $base = 'Mercado Libre rechazó la publicación.';

        foreach ($causes as $c) {
            $msg  = $c['message'] ?? '';
            if ($msg) $lines[] = $msg;
        }

        if (empty($lines)) {
            if (!empty($j['error'])) $lines[] = (string)$j['error'];
            elseif (!empty($j['message'])) $lines[] = (string)$j['message'];
            else $lines[] = 'Revisa categoría, stock, precio, fotos y atributos requeridos.';
        }

        $lines = array_unique($lines);
        return $base . ' ' . implode(' ', $lines);
    }
}
