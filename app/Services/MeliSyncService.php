<?php
// app/Services/MeliSyncService.php
namespace App\Services;

use App\Models\CatalogItem;
use App\Services\MeliHttp;
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
     * Publica o actualiza en ML y marca campos en DB.
     * $options:
     *  - 'activate' => bool   Fuerza activar (status=active) si es posible
     *  - 'update_description' => bool  Actualiza la descripción usando el endpoint específico
     *  - 'ensure_picture' => bool      Reemplaza imágenes con una estable si hay sub_status picture_download_pending
     */
    public function sync(CatalogItem $item, array $options = []): array
    {
        $http = MeliHttp::withFreshToken();

        // 0) Estado de la cuenta (para shipping)
        $meResp = $http->get($this->api('users/me'));
        $me = $meResp->ok() ? (array) $meResp->json() : [];
        $env = Arr::get($me, 'status.mercadoenvios', 'not_accepted'); // accepted|not_accepted|mandatory
        $shippingMode = ($env === 'accepted' || $env === 'mandatory') ? 'me2' : 'custom';

        // 1) listing_type_id permitido
        $userId = Arr::get($me, 'id');
        $listings = [];
        if ($userId) {
            $ltResp = $http->get($this->api("users/{$userId}/available_listing_types"), ['site_id' => 'MLM']);
            $listings = $ltResp->ok() ? (array) $ltResp->json() : [];
        }
        $listingType = $item->meli_listing_type_id ?: ($listings[0]['id'] ?? 'gold_special');

        // 2) category_id (usa el guardado o predice por título)
        $categoryId = $item->meli_category_id;
        if (!$categoryId) {
            $predResp = $http->get($this->api('sites/MLM/domain_discovery/search'), ['q' => $item->name]);
            $pred = $predResp->ok() ? (array) $predResp->json() : [];
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
            if (count($pics) >= 6) break;
        }
        if (empty($pics)) {
            // Placeholder pública para evitar rechazo por falta de imagen
            $pics[] = ['source' => 'https://http2.mlstatic.com/storage/developers-site-cms-admin/openapi/319102622313-testimage.jpeg'];
        }

        // 4) atributos mínimos (BRAND, MODEL)
        $attributes = [];
        $brand = trim((string) ($item->brand_name ?? 'Genérica'));
        $model = trim((string) ($item->model_name ?? $item->sku ?? 'Modelo Único'));
        $attributes[] = ['id' => 'BRAND', 'value_name' => $brand];
        $attributes[] = ['id' => 'MODEL', 'value_name' => $model];

        // Autocompletar los atributos requeridos por la categoría (ej. FAN_TYPE)
        $attributes = $this->fillRequiredCategoryAttributes($http, $categoryId, $attributes);

        // 5) payload común
        $price = (float) ($item->sale_price ?? $item->price ?? 0);
        if ($price < 5) $price = 5.00;
        $qty = max(1, (int) 1);

        $payload = [
            // IMPORTANTE: título enriquecido para evitar item.title.minimum_length
            'title'              => $this->buildMeliTitle($item),
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

        // 6) crear o actualizar (UPDATE SEGURO)
        if (!empty($item->meli_item_id)) {
            // UPDATE: no enviar campos no-modificables
            $update = $payload;
            unset(
                $update['listing_type_id'],
                $update['category_id'],
                $update['buying_mode'],
                $update['currency_id'],
                $update['condition'],
                $update['description']  // descripción se actualiza con endpoint dedicado
            );

            // Activar si se pidió
            if (!empty($options['activate'])) {
                $update['status'] = 'active';
            }

            $resp = $http->put($this->api("items/{$item->meli_item_id}"), $update);
            $j = (array) $resp->json();

            // Si hay sub_status picture_download_pending y pidieron asegurar imagen, reemplazamos y reintento de activar
            if ($resp->ok() && !empty($options['ensure_picture'])) {
                $check = $http->get($this->api("items/{$item->meli_item_id}"))->json();
                $sub = $check['sub_status'] ?? [];
                if (in_array('picture_download_pending', (array) $sub, true)) {
                    $http->put($this->api("items/{$item->meli_item_id}"), [
                        'pictures' => [
                            ['source' => 'https://http2.mlstatic.com/storage/developers-site-cms-admin/openapi/319102622313-testimage.jpeg']
                        ],
                    ]);
                    if (!empty($options['activate'])) {
                        $http->put($this->api("items/{$item->meli_item_id}"), ['status' => 'active']);
                    }
                }
            }

            // Descripción por endpoint dedicado (si se pidió)
            if (!empty($options['update_description'])) {
                $this->upsertDescription($http, $item->meli_item_id, $this->plainText($item));
            }

        } else {
            // CREATE: enviar payload completo
            $resp = $http->post($this->api('items'), $payload);
            $j = (array) $resp->json();

            // Si se pidió activar inmediatamente (y ML lo permite)
            if ($resp->ok() && !empty($j['id']) && !empty($options['activate'])) {
                $http->put($this->api("items/{$j['id']}"), ['status' => 'active']);
            }
            // Si se pidió asegurar imagen
            if ($resp->ok() && !empty($j['id']) && !empty($options['ensure_picture'])) {
                $check = $http->get($this->api("items/{$j['id']}"))->json();
                $sub = $check['sub_status'] ?? [];
                if (in_array('picture_download_pending', (array) $sub, true)) {
                    $http->put($this->api("items/{$j['id']}"), [
                        'pictures' => [
                            ['source' => 'https://http2.mlstatic.com/storage/developers-site-cms-admin/openapi/319102622313-testimage.jpeg']
                        ],
                    ]);
                    if (!empty($options['activate'])) {
                        $http->put($this->api("items/{$j['id']}"), ['status' => 'active']);
                    }
                }
            }
            // Descripción por endpoint dedicado (si se pidió)
            if ($resp->ok() && !empty($j['id']) && !empty($options['update_description'])) {
                $this->upsertDescription($http, $j['id'], $this->plainText($item));
            }
        }

        // Manejo de errores
        if ($resp->failed()) {
            $item->update([
                'meli_status'           => 'error',
                'meli_last_error'       => substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 2000),
                'meli_category_id'      => $categoryId,
                'meli_listing_type_id'  => $listingType,
            ]);
            Log::warning('ML publish error', ['catalog_item_id' => $item->id, 'resp' => $j]);
            return ['ok' => false, 'json' => $j];
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
            return ['ok' => true, 'json' => ['msg' => 'sin meli_item_id']];
        }

        $http = MeliHttp::withFreshToken();
        $resp = $http->put($this->api("items/{$item->meli_item_id}"), ['status' => 'paused']);
        $j = (array) $resp->json();

        if ($resp->failed()) {
            $item->update([
                'meli_status'     => 'error',
                'meli_last_error' => substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 2000),
            ]);
            return ['ok' => false, 'json' => $j];
        }

        $item->update([
            'meli_status'    => 'paused',
            'meli_synced_at' => now(),
            'meli_last_error'=> null
        ]);
        return ['ok' => true, 'json' => $j];
    }

    /** Activar en ML (helper para botones en interfaz) */
    public function activate(CatalogItem $item): array
    {
        if (empty($item->meli_item_id)) {
            return ['ok' => false, 'json' => ['message' => 'sin meli_item_id']];
        }
        $http = MeliHttp::withFreshToken();
        $resp = $http->put($this->api("items/{$item->meli_item_id}"), ['status' => 'active']);
        $j = (array) $resp->json();

        if ($resp->failed()) {
            $item->update(['meli_status' => 'error', 'meli_last_error' => substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 2000)]);
            return ['ok' => false, 'json' => $j];
        }

        $item->update(['meli_status' => 'active', 'meli_synced_at' => now(), 'meli_last_error' => null]);
        return ['ok' => true, 'json' => $j];
    }

    /** ===== Helpers ===== */

    private function plainText(CatalogItem $i): string
    {
        $base = $i->excerpt ?: strip_tags((string) $i->description);
        $base = trim($base) ?: "{$i->name}.\n\nVendido por JURETO. Factura disponible. Garantía estándar.\n";
        // ML recomienda <= 5000 chars, sin HTML
        return mb_substr(preg_replace('/\s+/', ' ', $base), 0, 4800);
    }

    /**
     * Construye un título “bonito” para Mercado Libre
     * incluyendo nombre, marca, modelo/SKU y algún extra corto.
     */
    private function buildMeliTitle(CatalogItem $item): string
    {
        $parts = [];

        // Parte base: nombre del producto
        if (!empty($item->name)) {
            $parts[] = trim($item->name);
        }

        // Marca
        if (!empty($item->brand_name)) {
            $parts[] = trim($item->brand_name);
        }

        // Modelo o SKU
        if (!empty($item->model_name)) {
            $parts[] = trim($item->model_name);
        } elseif (!empty($item->sku)) {
            $parts[] = trim($item->sku);
        }

        // Algún extra corto (ej. "Caja con 12 piezas" en excerpt)
        if (!empty($item->excerpt)) {
            $extra = trim($item->excerpt);
            if (mb_strlen($extra) > 30) {
                $extra = mb_substr($extra, 0, 30);
            }
            $parts[] = $extra;
        }

        $title = trim(preg_replace('/\s+/', ' ', implode(' ', $parts)));

        // Si quedó muy corto, rellenamos con marca/modelo de forma fija
        if (mb_strlen($title) < 20 && !empty($item->name)) {
            $brand = $item->brand_name ?: 'Genérica';
            $model = $item->model_name ?: ($item->sku ?: 'Modelo Único');
            $title = "{$item->name} {$brand} {$model}";
        }

        // Recomendado ~60 caracteres
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
     * - $attributesPayload: lista de atributos que ya pusiste (id/value_*).
     * - Devuelve la lista final con faltantes incluidos usando value_id si hay catálogo,
     *   o 'value_name' => 'Genérico' si no tiene catálogo.
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
            $attrId = $def['id'] ?? null;
            $isRequired = !empty($def['tags']['required']);
            if (!$isRequired || !$attrId || isset($present[$attrId])) {
                continue;
            }

            // Si tiene catálogo de valores, usamos el primero
            $val = null;
            if (!empty($def['values']) && is_array($def['values'])) {
                $val = $def['values'][0] ?? null;
            }

            if ($val && !empty($val['id'])) {
                $attributesPayload[] = ['id' => $attrId, 'value_id' => $val['id']];
            } else {
                // Fallback por nombre si no hay catálogo o no trae values
                $attributesPayload[] = ['id' => $attrId, 'value_name' => 'Genérico'];
            }
        }

        return $attributesPayload;
    }

    /**
     * Crear/actualizar descripción por el endpoint correcto.
     * Algunos vendedores requieren POST la primera vez y PUT después; aquí probamos PUT y si falla, POST.
     */
    private function upsertDescription($http, string $itemId, string $plainText): void
    {
        try {
            $put = $http->put($this->api("items/{$itemId}/description"), ['plain_text' => $plainText]);
            if ($put->failed()) {
                $post = $http->post($this->api("items/{$itemId}/description"), ['plain_text' => $plainText]);
                if ($post->failed()) {
                    Log::warning('ML description upsert failed', ['id' => $itemId, 'put' => $put->json(), 'post' => $post->json()]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('ML description upsert exception: '.$e->getMessage(), ['item' => $itemId]);
        }
    }
}
