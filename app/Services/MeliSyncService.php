<?php

namespace App\Services;

use App\Models\CatalogItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class MeliSyncService
{
    /** Siempre usa el host real; el “sandbox” se simula con usuarios de prueba */
    private function api(string $path): string
    {
        return 'https://api.mercadolibre.com/' . ltrim($path, '/');
    }

    /**
     * Construye family_name requerido por algunos flujos (User Products / validaciones nuevas).
     * Reglas:
     * - Debe ir en el ROOT del body (no en attributes).
     * - No puede ir vacío.
     * - Recomendable <= 60 chars.
     */
    private function buildFamilyName(CatalogItem $item): string
    {
        $name  = trim((string) ($item->name ?? ''));
        $brand = trim((string) ($item->brand_name ?? ''));
        $model = trim((string) ($item->model_name ?? ''));

        // Evitar duplicados si ya vienen incluidos en name
        $parts = [];
        if ($name !== '')  $parts[] = $name;

        $nameLower = mb_strtolower($name);
        if ($brand !== '' && !str_contains($nameLower, mb_strtolower($brand))) {
            $parts[] = $brand;
        }
        if ($model !== '' && !str_contains($nameLower, mb_strtolower($model))) {
            $parts[] = $model;
        }

        $family = trim(preg_replace('/\s+/', ' ', implode(' ', $parts)));

        // Fallbacks duros: jamás vacío
        if ($family === '') {
            $family = $name !== '' ? $name : 'Producto';
        }

        // Limitar tamaño (ML suele ser estricto)
        $family = mb_substr($family, 0, 60);

        return $family;
    }

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

                    // Estados que NO se pueden actualizar: tratar como "muerto"
                    if (in_array($remoteStatus, ['closed', 'canceled', 'cancelled', 'deleted'], true)) {
                        $item->update([
                            'meli_status'     => $remoteStatus,
                            'meli_last_error' => "La publicación anterior en Mercado Libre ({$item->meli_item_id}) está en estado '{$remoteStatus}' y ya no se puede modificar. Se creará una nueva publicación al volver a sincronizar.",
                        ]);

                        // Limpiamos solo en el objeto actual para que esta sync haga POST
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
            $categoryId = $pred[0]['category_id'] ?? 'MLM3530'; // fallback genérico “Otros”
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
            if (count($pics) >= 6) {
                break;
            }
        }
        if (empty($pics)) {
            // Placeholder pública para evitar rechazo por falta de imagen
            $pics[] = ['source' => 'https://http2.mlstatic.com/storage/developers-site-cms-admin/openapi/319102622313-testimage.jpeg'];
        }

        // 4) atributos mínimos (BRAND, MODEL, GTIN)
        $attributes = [];
        $brand      = trim((string) ($item->brand_name ?? 'Genérica'));
        $model      = trim((string) ($item->model_name ?? $item->sku ?? 'Modelo Único'));

        $attributes[] = ['id' => 'BRAND', 'value_name' => $brand];
        $attributes[] = ['id' => 'MODEL', 'value_name' => $model];

        // GTIN si lo tiene el producto
        $gtin = trim((string) ($item->meli_gtin ?? ''));
        if ($gtin !== '') {
            $attributes[] = ['id' => 'GTIN', 'value_name' => $gtin];
        }

        // Autocompletar los atributos requeridos por la categoría
        $attributes = $this->fillRequiredCategoryAttributes($http, $categoryId, $attributes);

        // 5) payload común
        $price = (float) ($item->sale_price ?? $item->price ?? 0);
        if ($price < 5) {
            $price = 5.00;
        }

        // OJO: estabas forzando qty=1 siempre; lo dejo igual para no romper tu lógica.
        // Si quieres, cámbialo a max(1, (int)($item->stock ?? 1))
        $qty = max(1, (int) 1);

        $payload = [
            'title'              => $this->buildMeliTitle($item),
            'family_name'        => $this->buildFamilyName($item), // ✅ FIX: requerido por ML en algunos flujos
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

        // Log para debugging (incluye family_name)
        Log::info('ML publish payload', [
            'catalog_item_id' => $item->id,
            'meli_item_id'    => $item->meli_item_id,
            'title'           => $payload['title'],
            'family_name'     => $payload['family_name'] ?? null,
            'category_id'     => $payload['category_id'] ?? null,
        ]);

        // Validación local de título para evitar el error 3705
        $title = $payload['title'] ?? '';
        if (mb_strlen($title) < 25 || str_word_count($title) < 4) {
            $msg = "Mercado Libre rechazó la publicación. El título del producto es demasiado corto o genérico. " .
                "Agrega marca, modelo y características importantes (ej. \"Lapicero bolígrafo azul Bic 0.7mm\"). " .
                "Título actual: \"{$title}\"";

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
            // UPDATE: no enviar campos no-modificables
            $update = $payload;
            unset(
                $update['listing_type_id'],
                $update['category_id'],
                $update['buying_mode'],
                $update['currency_id'],
                $update['condition'],
                $update['description'] // descripción se actualiza con endpoint dedicado
            );

            // Nota: mantenemos family_name también en update.
            // Si ML lo ignora, no pasa nada; si lo exige, ya está.
            if (!empty($options['activate'])) {
                $update['status'] = 'active';
            }

            $resp = $http->put($this->api("items/{$item->meli_item_id}"), $update);
            $j    = (array) $resp->json();

            // picture_download_pending
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
            // CREATE
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

        // Manejo de errores
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

        // Éxito → reflejar en DB
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

            $item->update([
                'meli_status'     => 'error',
                'meli_last_error' => $friendly,
            ]);
            return ['ok' => false, 'json' => $j, 'message' => $friendly];
        }

        $item->update([
            'meli_status'     => 'paused',
            'meli_synced_at'  => now(),
            'meli_last_error' => null,
        ]);

        return ['ok' => true, 'json' => $j];
    }

    /** Activar en ML (helper para botones en interfaz) */
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

            $item->update([
                'meli_status'     => 'error',
                'meli_last_error' => $friendly,
            ]);
            return ['ok' => false, 'json' => $j, 'message' => $friendly];
        }

        $item->update([
            'meli_status'     => 'active',
            'meli_synced_at'  => now(),
            'meli_last_error' => null,
        ]);

        return ['ok' => true, 'json' => $j];
    }

    /* =====================
     *  Helpers de contenido
     * ===================== */

    private function plainText(CatalogItem $i): string
    {
        $base = $i->excerpt ?: strip_tags((string) $i->description);
        $base = trim($base) ?: "{$i->name}.\n\nVendido por JURETO. Factura disponible. Garantía estándar.\n";

        // ML recomienda <= 5000 chars, sin HTML
        return mb_substr(preg_replace('/\s+/', ' ', $base), 0, 4800);
    }

    /**
     * Construye un título “bonito” para Mercado Libre
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

        if (!empty($item->excerpt)) {
            $extra = trim($item->excerpt);
            if (mb_strlen($extra) > 30) {
                $extra = mb_substr($extra, 0, 30);
            }
            $parts[] = $extra;
        }

        $title = trim(preg_replace('/\s+/', ' ', implode(' ', $parts)));

        if (mb_strlen($title) < 20 && !empty($item->name)) {
            $brand = $item->brand_name ?: 'Genérica';
            $model = $item->model_name ?: ($item->sku ?: 'Modelo Único');
            $title = "{$item->name} {$brand} {$model}";
        }

        if (mb_strlen($title) > 60) {
            $title = mb_substr($title, 0, 60);
        }

        return $title;
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
                $attributesPayload[] = ['id' => $attrId, 'value_name' => 'Genérico'];
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

            // Detección especial de GTIN requerido
            if ($msg && stripos($msg, 'gtin') !== false && stripos($msg, 'required') !== false) {
                $lines[] = 'Esta categoría exige el código de barras (GTIN) del producto. Captura el GTIN en el campo "GTIN / Código de barras" y vuelve a intentar publicar.';
                continue;
            }

            // Detección especial de family_name requerido (por si vuelve)
            if ($msg && stripos($msg, 'family_name') !== false) {
                $lines[] = 'Esta cuenta/categoría exige "family_name". El sistema lo genera automáticamente; si persiste, revisa que el servicio esté enviando el campo en el body.';
                continue;
            }

            switch ($code) {
                case 'item.title.minimum_length':
                    $lines[] = 'El título del producto es demasiado corto o genérico. Agrega marca, modelo y características importantes (por ejemplo: "Lapicero bolígrafo azul Bic 0.7mm").';
                    break;

                case 'item.price.invalid':
                    $min = null;
                    if ($msg && preg_match('/minimum of price (\d+)/', $msg, $m)) {
                        $min = $m[1];
                    }
                    if ($min) {
                        $lines[] = "El precio es menor al mínimo permitido para esta categoría. Debe ser al menos {$min} MXN.";
                    } else {
                        $lines[] = 'El precio es inválido para esa categoría. Aumenta el precio para cumplir el mínimo requerido.';
                    }
                    break;

                case 'field_not_updatable':
                case 'item.price.not_modifiable':
                case 'item.attributes.not_modifiable':
                    $lines[] = 'Esta publicación en Mercado Libre está en un estado donde ya no se puede modificar (por ejemplo: cerrada o finalizada). Debes crear una nueva publicación.';
                    break;

                default:
                    if ($msg) {
                        $lines[] = $msg;
                    }
                    break;
            }
        }

        if (empty($lines)) {
            if (!empty($j['message'])) {
                $lines[] = $j['message'];
            } else {
                $lines[] = 'Revisa título, precio, categoría, código de barras (GTIN) y stock del producto.';
            }
        }

        $lines = array_unique($lines);

        return $base . ' ' . implode(' ', $lines);
    }
}
