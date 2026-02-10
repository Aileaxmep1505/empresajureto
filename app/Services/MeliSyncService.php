<?php

namespace App\Services;

use App\Models\CatalogItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MeliSyncService
{
    /** Siempre usa el host real; el “sandbox” se simula con usuarios de prueba */
    private function api(string $path): string
    {
        return 'https://api.mercadolibre.com/' . ltrim($path, '/');
    }

    /* =========================================================
     |  Helpers: Mercado Libre payload
     ========================================================= */

    /**
     * Construye family_name requerido por algunos flujos (User Products / validaciones nuevas).
     * - Debe ir en el ROOT del body.
     * - No puede ir vacío.
     * - Recomendable <= 60 chars.
     */
    private function buildFamilyName(CatalogItem $item): string
    {
        $name  = trim((string) ($item->name ?? ''));
        $brand = trim((string) ($item->brand_name ?? ''));
        $model = trim((string) ($item->model_name ?? ''));

        $parts = [];
        if ($name !== '') $parts[] = $name;

        $nameLower = mb_strtolower($name);
        if ($brand !== '' && !str_contains($nameLower, mb_strtolower($brand))) {
            $parts[] = $brand;
        }
        if ($model !== '' && !str_contains($nameLower, mb_strtolower($model))) {
            $parts[] = $model;
        }

        $family = trim(preg_replace('/\s+/', ' ', implode(' ', $parts)));
        if ($family === '') $family = $name !== '' ? $name : 'Producto';

        return mb_substr($family, 0, 60);
    }

    /**
     * Título para ML:
     * - NO incluye excerpt (mete ruido y puede invalidar).
     * - Sanitiza a ASCII y solo caracteres seguros.
     * - 25+ chars y 4+ palabras (tu regla).
     * - <= 60 chars (recomendado)
     */
    private function buildMeliTitle(CatalogItem $item): string
    {
        $parts = [];

        if (!empty($item->name)) {
            $parts[] = trim($item->name);
        }

        if (!empty($item->brand_name)) {
            $parts[] = trim($item->brand_name);
        }

        if (!empty($item->model_name)) {
            $parts[] = trim($item->model_name);
        } elseif (!empty($item->sku)) {
            $parts[] = trim($item->sku);
        }

        $title = trim(preg_replace('/\s+/', ' ', implode(' ', $parts)));

        if (mb_strlen($title) < 20 && !empty($item->name)) {
            $brand = $item->brand_name ?: 'Generica';
            $model = $item->model_name ?: ($item->sku ?: 'Modelo Unico');
            $title = "{$item->name} {$brand} {$model}";
        }

        // Sanitizar: a ASCII + solo letras/números/espacios
        $title = Str::ascii($title);
        $title = preg_replace('/[^A-Za-z0-9 ]+/', ' ', (string) $title);
        $title = trim(preg_replace('/\s+/', ' ', (string) $title));

        if (mb_strlen($title) > 60) {
            $title = mb_substr($title, 0, 60);
        }

        return $title;
    }

    private function plainText(CatalogItem $i): string
    {
        $base = $i->excerpt ?: strip_tags((string) $i->description);
        $base = trim($base) ?: "{$i->name}.\n\nVendido por JURETO. Factura disponible. Garantía estándar.\n";
        // <= 5000 chars, sin HTML
        return mb_substr(preg_replace('/\s+/', ' ', $base), 0, 4800);
    }

    /** Devuelve attributes completos de la categoría o [] */
    private function fetchCategoryAttributes($http, string $categoryId): array
    {
        $resp = $http->get($this->api("categories/{$categoryId}/attributes"));
        return $resp->ok() ? (array) $resp->json() : [];
    }

    /**
     * Completa atributos requeridos que falten.
     */
    private function fillRequiredCategoryAttributes($http, string $categoryId, array $attributesPayload): array
    {
        $present = [];
        foreach ($attributesPayload as $a) {
            if (!empty($a['id'])) {
                $present[$a['id']] = true;
            }
        }

        $defs = $this->fetchCategoryAttributes($http, $categoryId);
        foreach ($defs as $def) {
            $attrId     = $def['id'] ?? null;
            $isRequired = !empty($def['tags']['required']);

            if (!$isRequired || !$attrId || isset($present[$attrId])) {
                continue;
            }

            $val = null;
            if (!empty($def['values']) && is_array($def['values'])) {
                $val = $def['values'][0] ?? null;
            }

            if ($val && !empty($val['id'])) {
                $attributesPayload[] = ['id' => $attrId, 'value_id' => $val['id']];
            } else {
                $attributesPayload[] = ['id' => $attrId, 'value_name' => 'Generico'];
            }
        }

        return $attributesPayload;
    }

    /**
     * Crear/actualizar descripción por el endpoint correcto.
     */
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

    /* =========================================================
     |  OpenAI helper: convertir error ML en sugerencias
     ========================================================= */

    private function openaiKey(): ?string
    {
        $k = config('services.openai.api_key') ?: config('services.openai.key') ?: env('OPENAI_API_KEY');
        $k = is_string($k) ? trim($k) : null;
        return $k ?: null;
    }

    private function openaiBaseUrl(): string
    {
        return rtrim((string) (config('services.openai.base_url') ?: 'https://api.openai.com'), '/');
    }

    /**
     * Genera sugerencias concretas para arreglar la publicación.
     * Devuelve un texto listo para mostrar al usuario.
     */
    private function aiSuggestFix(
        array $mlErrorJson,
        ?array $validateJson,
        CatalogItem $item,
        array $payload
    ): ?string {
        $apiKey = $this->openaiKey();
        if (!$apiKey) return null;

        $baseUrl = $this->openaiBaseUrl();
        $modelId = 'gpt-4.1-mini';

        // Reducir payload para prompt (evitar meter imágenes enormes)
        $payloadSlim = $payload;
        if (isset($payloadSlim['pictures']) && is_array($payloadSlim['pictures'])) {
            $payloadSlim['pictures'] = array_slice($payloadSlim['pictures'], 0, 2);
        }
        if (isset($payloadSlim['attributes']) && is_array($payloadSlim['attributes'])) {
            $payloadSlim['attributes'] = array_slice($payloadSlim['attributes'], 0, 15);
        }
        if (isset($payloadSlim['description']['plain_text'])) {
            $payloadSlim['description']['plain_text'] = mb_substr((string) $payloadSlim['description']['plain_text'], 0, 250);
        }

        $system = <<<TXT
Eres un experto en integraciones con Mercado Libre (MX) y validación de publicaciones.
Tu tarea: dado un error de la API de ML (y opcionalmente el resultado de /items/validate), generar sugerencias accionables para corregir el body de la publicación.
Reglas:
- Responde en español, directo, sin emojis.
- Si falta un campo, indica EXACTAMENTE cuál y en qué parte del body va.
- Si el error sugiere catálogo (ej. no permite title), explica que se requiere catalog_product_id y cómo detectarlo.
- Da hasta 8 bullets.
- Si propones cambios, incluye ejemplos de valores.
TXT;

        $user = [
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'brand_name' => $item->brand_name,
                'model_name' => $item->model_name,
                'meli_gtin' => $item->meli_gtin,
                'meli_category_id' => $item->meli_category_id,
            ],
            'payload' => $payloadSlim,
            'ml_error' => $mlErrorJson,
            'ml_validate' => $validateJson,
        ];

        try {
            $resp = Http::withToken($apiKey)
                ->timeout(35)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($baseUrl . '/v1/responses', [
                    'model' => $modelId,
                    'instructions' => $system,
                    'input' => [
                        [
                            'role' => 'user',
                            'content' => [
                                ['type' => 'input_text', 'text' => "Analiza y sugiere correcciones.\n\n" . json_encode($user, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                            ],
                        ],
                    ],
                    'max_output_tokens' => 450,
                    'temperature' => 0.2,
                ]);

            if (!$resp->ok()) {
                Log::warning('OpenAI suggestFix failed', [
                    'status' => $resp->status(),
                    'body' => $resp->body(),
                ]);
                return null;
            }

            $j = $resp->json();

            // Extraer texto
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

            $rawText = trim($rawText);
            return $rawText !== '' ? $rawText : null;
        } catch (\Throwable $e) {
            Log::warning('OpenAI suggestFix exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Convierte la respuesta de error de ML en un mensaje entendible.
     */
    private function humanMeliError(array $j): string
    {
        $causes = $j['cause'] ?? [];
        $lines  = [];

        $base = 'Mercado Libre rechazó la publicación.';

        foreach ($causes as $c) {
            $code = $c['code'] ?? '';
            $msg  = $c['message'] ?? '';

            if ($msg && stripos($msg, 'gtin') !== false && stripos($msg, 'required') !== false) {
                $lines[] = 'Esta categoría exige el código de barras (GTIN). Captura el GTIN y vuelve a intentar.';
                continue;
            }

            if ($msg && stripos($msg, 'family_name') !== false) {
                $lines[] = 'Esta cuenta/categoría exige family_name. El sistema debe enviarlo en el body raíz.';
                continue;
            }

            switch ($code) {
                case 'item.title.minimum_length':
                    $lines[] = 'El título es muy corto o genérico. Agrega marca, modelo y característica (ej. "Lapicero azul Bic 0.7mm").';
                    break;

                case 'item.price.invalid':
                    $lines[] = 'El precio es inválido o menor al mínimo permitido para la categoría.';
                    break;

                case 'field_not_updatable':
                case 'item.price.not_modifiable':
                case 'item.attributes.not_modifiable':
                    $lines[] = 'La publicación ya no se puede modificar (cerrada/finalizada). Debes crear una nueva.';
                    break;

                default:
                    if ($msg) $lines[] = $msg;
                    break;
            }
        }

        if (empty($lines)) {
            if (!empty($j['message'])) {
                $lines[] = $j['message'];
            } else {
                $lines[] = 'Revisa título, precio, categoría, GTIN y stock.';
            }
        }

        $lines = array_unique($lines);
        return $base . ' ' . implode(' ', $lines);
    }

    /* =========================================================
     |  Sync principal
     ========================================================= */

    /**
     * Publica o actualiza en ML y marca campos en DB.
     * $options:
     *  - 'activate'           => bool   Fuerza activar (status=active) si es posible
     *  - 'update_description' => bool   Actualiza la descripción usando el endpoint específico
     *  - 'ensure_picture'     => bool   Reemplaza imágenes con una estable si hay sub_status picture_download_pending
     */
    public function sync(CatalogItem $item, array $options = []): array
    {
        $http = MeliHttp::withFreshToken();

        // 0) Estado de la cuenta (para shipping)
        $meResp = $http->get($this->api('users/me'));
        $me     = $meResp->ok() ? (array) $meResp->json() : [];
        $env    = Arr::get($me, 'status.mercadoenvios', 'not_accepted'); // accepted|not_accepted|mandatory
        $shippingMode = ($env === 'accepted' || $env === 'mandatory') ? 'me2' : 'custom';

        // 0.b) Si ya tengo meli_item_id, ver si el item remoto está CERRADO / CANCELADO
        if (!empty($item->meli_item_id)) {
            try {
                $remoteResp = $http->get($this->api("items/{$item->meli_item_id}"));
                if ($remoteResp->ok()) {
                    $remote       = (array) $remoteResp->json();
                    $remoteStatus = $remote['status'] ?? null;

                    if (in_array($remoteStatus, ['closed', 'canceled', 'cancelled', 'deleted'], true)) {
                        $item->update([
                            'meli_status'     => $remoteStatus,
                            'meli_last_error' => "La publicación anterior ({$item->meli_item_id}) está '{$remoteStatus}' y ya no se puede modificar. Se creará una nueva al sincronizar.",
                        ]);

                        $item->meli_item_id = null;
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

        // 1) listing_type_id permitido
        $userId   = Arr::get($me, 'id');
        $listings = [];
        if ($userId) {
            $ltResp   = $http->get($this->api("users/{$userId}/available_listing_types"), ['site_id' => 'MLM']);
            $listings = $ltResp->ok() ? (array) $ltResp->json() : [];
        }
        $listingType = $item->meli_listing_type_id ?: ($listings[0]['id'] ?? 'gold_special');

        // 2) category_id (usa el guardado o predice por título)
        $categoryId = $item->meli_category_id;
        if (!$categoryId) {
            $predResp = $http->get($this->api('sites/MLM/domain_discovery/search'), ['q' => $item->name]);
            $pred     = $predResp->ok() ? (array) $predResp->json() : [];
            $categoryId = $pred[0]['category_id'] ?? 'MLM3530';
        }

        // 3) pictures
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

        // 4) atributos mínimos (BRAND, MODEL, GTIN)
        $attributes = [];
        $brand      = trim((string) ($item->brand_name ?? 'Generica'));
        $model      = trim((string) ($item->model_name ?? $item->sku ?? 'Modelo Unico'));

        $attributes[] = ['id' => 'BRAND', 'value_name' => $brand];
        $attributes[] = ['id' => 'MODEL', 'value_name' => $model];

        $gtin = trim((string) ($item->meli_gtin ?? ''));
        if ($gtin !== '') {
            $attributes[] = ['id' => 'GTIN', 'value_name' => $gtin];
        }

        $attributes = $this->fillRequiredCategoryAttributes($http, $categoryId, $attributes);

        // 5) payload común
        $price = (float) ($item->sale_price ?? $item->price ?? 0);
        if ($price < 5) $price = 5.00;

        // Mantengo tu comportamiento actual (qty = 1) para no romper, pero aquí sería mejor usar stock real.
        $qty = max(1, (int) 1);

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
            'title'           => $payload['title'],
            'family_name'     => $payload['family_name'] ?? null,
            'category_id'     => $payload['category_id'] ?? null,
        ]);

        // Validación local (mínimos)
        $title = $payload['title'] ?? '';
        if (mb_strlen($title) < 25 || str_word_count($title) < 4) {
            $msg = "Mercado Libre rechazó la publicación. El título es muy corto o genérico. Agrega marca, modelo y característica. Título actual: \"{$title}\"";

            $item->update([
                'meli_status'          => 'error',
                'meli_last_error'      => $msg,
                'meli_category_id'     => $categoryId,
                'meli_listing_type_id' => $listingType,
            ]);

            return ['ok' => false, 'json' => ['message' => $msg], 'message' => $msg];
        }

        // 6) crear o actualizar
        if (!empty($item->meli_item_id)) {
            $update = $payload;
            unset(
                $update['listing_type_id'],
                $update['category_id'],
                $update['buying_mode'],
                $update['currency_id'],
                $update['condition'],
                $update['description']
            );

            if (!empty($options['activate'])) {
                $update['status'] = 'active';
            }

            $resp = $http->put($this->api("items/{$item->meli_item_id}"), $update);
            $j    = (array) $resp->json();

            if ($resp->ok() && !empty($options['ensure_picture'])) {
                $check = $http->get($this->api("items/{$item->meli_item_id}"))->json();
                $sub   = $check['sub_status'] ?? [];
                if (in_array('picture_download_pending', (array) $sub, true)) {
                    $http->put($this->api("items/{$item->meli_item_id}"), [
                        'pictures' => [
                            ['source' => 'https://http2.mlstatic.com/storage/developers-site-cms-admin/openapi/319102622313-testimage.jpeg'],
                        ],
                    ]);
                    if (!empty($options['activate'])) {
                        $http->put($this->api("items/{$item->meli_item_id}"), ['status' => 'active']);
                    }
                }
            }

            if ($resp->ok() && !empty($options['update_description'])) {
                $this->upsertDescription($http, $item->meli_item_id, $this->plainText($item));
            }
        } else {
            $resp = $http->post($this->api('items'), $payload);
            $j    = (array) $resp->json();

            if ($resp->ok() && !empty($j['id']) && !empty($options['activate'])) {
                $http->put($this->api("items/{$j['id']}"), ['status' => 'active']);
            }

            if ($resp->ok() && !empty($j['id']) && !empty($options['ensure_picture'])) {
                $check = $http->get($this->api("items/{$j['id']}"))->json();
                $sub   = $check['sub_status'] ?? [];
                if (in_array('picture_download_pending', (array) $sub, true)) {
                    $http->put($this->api("items/{$j['id']}"), [
                        'pictures' => [
                            ['source' => 'https://http2.mlstatic.com/storage/developers-site-cms-admin/openapi/319102622313-testimage.jpeg'],
                        ],
                    ]);
                    if (!empty($options['activate'])) {
                        $http->put($this->api("items/{$j['id']}"), ['status' => 'active']);
                    }
                }
            }

            if ($resp->ok() && !empty($j['id']) && !empty($options['update_description'])) {
                $this->upsertDescription($http, $j['id'], $this->plainText($item));
            }
        }

        // 7) Manejo de errores + validate + IA sugerencias
        if ($resp->failed()) {
            $validateJson = null;

            // Intentar /items/validate para obtener causas detalladas (muy útil)
            try {
                $val = $http->post($this->api('items/validate'), $payload);
                if ($val->ok()) {
                    $validateJson = (array) $val->json();
                } else {
                    $validateJson = (array) $val->json();
                }
                Log::warning('ML validate details', [
                    'catalog_item_id' => $item->id,
                    'status' => $val->status(),
                    'json' => $validateJson,
                ]);
            } catch (\Throwable $e) {
                Log::warning('ML validate call failed', [
                    'catalog_item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $friendly = $this->humanMeliError($j);

            // IA: sugerencias para resolver
            $ai = $this->aiSuggestFix($j, $validateJson, $item, $payload);
            if ($ai) {
                $friendly .= "\n\nSugerencias automáticas:\n" . $ai;
            }

            $item->update([
                'meli_status'          => 'error',
                'meli_last_error'      => $friendly,
                'meli_category_id'     => $categoryId,
                'meli_listing_type_id' => $listingType,
            ]);

            Log::warning('ML publish error', [
                'catalog_item_id' => $item->id,
                'resp' => $j,
            ]);

            return ['ok' => false, 'json' => $j, 'message' => $friendly];
        }

        // 8) Éxito → reflejar en DB
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

    /** Pausar en ML */
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

    /** Activar en ML */
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
}
