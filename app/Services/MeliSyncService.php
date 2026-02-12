<?php

namespace App\Services;

use App\Models\CatalogItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MeliSyncService
{
    /** Siempre usa el host real; el “sandbox” se simula con usuarios de prueba */
    private function api(string $path): string
    {
        return 'https://api.mercadolibre.com/' . ltrim($path, '/');
    }

    /** Convierte una URL relativa (/storage/...) a absoluta con APP_URL */
    private function absUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') return $url;

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        $base = rtrim(config('app.url') ?: '', '/');
        if ($base === '') return $url;

        return $base . '/' . ltrim($url, '/');
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

        // Opción: permitir fallback a catálogo (por defecto NO, porque cambia marca/modelo)
        $allowCatalogFallback = (bool)($options['allow_catalog_fallback'] ?? false);

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

        // ✅ stock real (ML exige >= 1 para publicar)
        $qty = (int)($item->stock ?? 0);
        $qty = max(1, $qty);

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

        // Si pidieron activar en create/update
        if (!empty($options['activate'])) {
            $payload['status'] = 'active';
        }

        Log::info('ML publish payload', [
            'catalog_item_id' => $item->id,
            'meli_item_id'    => $item->meli_item_id,
            'title'           => $payload['title'] ?? null,
            'family_name'     => $payload['family_name'] ?? null,
            'category_id'     => $categoryId,
            'brand'           => $item->brand_name ?? null,
            'model'           => $item->model_name ?? null,
            'gtin'            => $item->meli_gtin ?? null,
            'qty'             => $qty,
        ]);

        // 7) Validación previa con ML
        $validate = $this->validateListing($http, $payload);
        if (!$validate['ok']) {
            Log::warning('ML validate details', [
                'catalog_item_id' => $item->id,
                'status'          => $validate['status'],
                'json'            => $validate['json'],
            ]);

            // Caso específico: title inválido => ML muchas veces fuerza catálogo
            if ($this->isTitleInvalidForCall((array)$validate['json'])) {
                // ✅ NO forzar catálogo automáticamente porque te cambia BRAND/MODEL
                if (!$allowCatalogFallback) {
                    $friendly = $this->humanMeliError((array)$validate['json']);
                    $extra = "⚠️ ML está pidiendo flujo de catálogo. Si publicas como catálogo, ML puede cambiar Marca/Modelo por los del catálogo.\n".
                             "Solución recomendada: ajustar categoría (domain_discovery) o completar atributos obligatorios para publicar como listing normal.\n".
                             "Si AÚN así quieres catálogo, llama sync() con allow_catalog_fallback=true.";

                    $finalMsg = trim($friendly . "\n\n" . $extra);

                    $item->update([
                        'meli_status'          => 'error',
                        'meli_last_error'      => $finalMsg,
                        'meli_category_id'     => $categoryId,
                        'meli_listing_type_id' => $listingType,
                    ]);

                    return ['ok' => false, 'json' => $validate['json'], 'message' => $finalMsg];
                }

                // ✅ Si lo permites, intenta catálogo
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

                $friendly = $this->humanMeliError((array)($catalogAttempt['json'] ?? []));
                $item->update([
                    'meli_status'          => 'error',
                    'meli_last_error'      => $friendly,
                    'meli_category_id'     => $categoryId,
                    'meli_listing_type_id' => $listingType,
                ]);

                return ['ok' => false, 'json' => $catalogAttempt['json'], 'message' => $friendly];
            }

            // No es el caso title inválido
            $friendly = $this->humanMeliError((array)$validate['json']);
            $item->update([
                'meli_status'          => 'error',
                'meli_last_error'      => $friendly,
                'meli_category_id'     => $categoryId,
                'meli_listing_type_id' => $listingType,
            ]);

            return ['ok' => false, 'json' => $validate['json'], 'message' => $friendly];
        }

        // 8) Crear/Actualizar real
        if (!empty($item->meli_item_id)) {
            $update = $payload;

            // Update: ML no deja cambiar algunos campos en PUT
            unset(
                $update['listing_type_id'],
                $update['category_id'],
                $update['buying_mode'],
                $update['currency_id'],
                $update['condition'],
                $update['description'] // descripción se hace en endpoint dedicado
            );

            // Activar si lo pidieron
            if (!empty($options['activate'])) {
                $update['status'] = 'active';
            }

            $resp = $http->put($this->api("items/{$item->meli_item_id}"), $update);
            $j    = (array) $resp->json();

            if ($resp->ok() && !empty($options['update_description'])) {
                $this->upsertDescription($http, $item->meli_item_id, $this->plainText($item));
            }
        } else {
            // Create
            $resp = $http->post($this->api('items'), $payload);
            $j    = (array) $resp->json();

            // Si por alguna razón no incluyó status en payload, hacemos activate
            if ($resp->ok() && !empty($j['id']) && !empty($options['activate']) && empty($payload['status'])) {
                $http->put($this->api("items/{$j['id']}"), ['status' => 'active']);
            }

            if ($resp->ok() && !empty($j['id']) && !empty($options['update_description'])) {
                $this->upsertDescription($http, $j['id'], $this->plainText($item));
            }
        }

        // 9) Manejo de errores
        if ($resp->failed()) {
            $friendly = $this->humanMeliError($j);

            $item->update([
                'meli_status'          => 'error',
                'meli_last_error'      => $friendly,
                'meli_category_id'     => $categoryId,
                'meli_listing_type_id' => $listingType,
            ]);

            Log::warning('ML publish error', ['catalog_item_id' => $item->id, 'resp' => $j]);
            return ['ok' => false, 'json' => $j, 'message' => $friendly];
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
     *  CATALOGO (fallback opcional)
     *  - OJO: catálogo puede cambiar Marca/Modelo
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
            $q = trim((string)($item->name ?? ''));
            // Ayuda un poco: incluye marca/modelo en búsqueda
            $brand = trim((string)($item->brand_name ?? ''));
            $model = trim((string)($item->model_name ?? ''));
            $mix = trim(preg_replace('/\s+/', ' ', $q . ' ' . $brand . ' ' . $model));

            $resp = $http->get($this->api('products/search'), [
                'status'   => 'active',
                'site_id'  => 'MLM',
                'q'        => $mix ?: $q,
                'category' => $categoryId,
                'limit'    => 5,
            ]);

            if (!$resp->ok()) return null;

            $j = (array) $resp->json();
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
     *  HELPERS: pictures / attributes / text / title
     * ========================================================== */

    private function buildPictures(CatalogItem $item): array
    {
        $pics = [];

        // ✅ Usa tus fotos guardadas en storage/public
        foreach (['photo_1','photo_2','photo_3'] as $col) {
            $path = $item->{$col} ?? null;
            if ($path) {
                $url = Storage::disk('public')->url($path);   // /storage/...
                $pics[] = ['source' => $this->absUrl($url)];
            }
        }

        // fallback legacy (si tienes image_url)
        if (empty($pics) && !empty($item->image_url)) {
            $pics[] = ['source' => $this->absUrl((string)$item->image_url)];
        }

        // fallback ultra último
        if (empty($pics)) {
            $pics[] = ['source' => 'https://http2.mlstatic.com/storage/developers-site-cms-admin/openapi/319102622313-testimage.jpeg'];
        }

        // ML acepta hasta 6
        return array_slice($pics, 0, 6);
    }

    private function buildBaseAttributes(CatalogItem $item): array
    {
        $attributes = [];

        // ✅ No uses genéricos agresivos: manda lo que escribió el usuario
        $brand = trim((string) ($item->brand_name ?? ''));
        $model = trim((string) ($item->model_name ?? ''));

        if ($brand === '') $brand = 'Genérica';
        if ($model === '') $model = trim((string)($item->sku ?? 'Modelo Único')) ?: 'Modelo Único';

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

        // ✅ Evita duplicar marca si ya viene en name
        $nameLower = mb_strtolower((string)$item->name);
        if (!empty($item->brand_name)) {
            $b = trim((string)$item->brand_name);
            if ($b !== '' && !str_contains($nameLower, mb_strtolower($b))) $parts[] = $b;
        }

        if (!empty($item->model_name)) $parts[] = trim($item->model_name);

        $title = trim(preg_replace('/\s+/', ' ', implode(' ', $parts)));
        if ($title === '') $title = 'Producto';

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

            // ✅ No metas "Genérico" a atributos críticos tipo BRAND/MODEL/GTIN
            if (in_array($attrId, ['BRAND','MODEL','GTIN'], true)) continue;

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
