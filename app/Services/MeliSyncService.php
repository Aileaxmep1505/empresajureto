<?php
// app/Services/MeliSyncService.php
namespace App\Services;

use App\Models\CatalogItem;
use App\Services\MeliHttp;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class MeliSyncService
{
    /** Siempre usa el host real; el “sandbox” se simula con usuarios de test */
    private function api(string $path): string
    {
        return 'https://api.mercadolibre.com/' . ltrim($path, '/');
    }

    /** Publica o actualiza en ML y marca campos en DB */
    public function sync(CatalogItem $item): array
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
            $ltResp = $http->get(
                $this->api("users/{$userId}/available_listing_types"),
                ['site_id' => 'MLM']
            );
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
            // placeholder pública para evitar rechazo por falta de imagen
            $pics[] = ['source' => 'https://http2.mlstatic.com/storage/developers-site-cms-admin/openapi/319102622313-testimage.jpeg'];
        }

        // 4) atributos mínimos (BRAND, MODEL)
        $attributes = [];
        $brand = trim((string) ($item->brand_name ?? 'Genérica'));
        $model = trim((string) ($item->model_name ?? $item->sku ?? 'Modelo Único'));
        $attributes[] = ['id' => 'BRAND', 'value_name' => $brand];
        $attributes[] = ['id' => 'MODEL', 'value_name' => $model];

        // 👇 Autocompletar los atributos requeridos por la categoría (ej. FAN_TYPE)
        $attributes = $this->fillRequiredCategoryAttributes($http, $categoryId, $attributes);

        // 5) payload común
        $price = (float) ($item->sale_price ?? $item->price ?? 0);
        if ($price < 5) $price = 5.00;
        $qty = max(1, (int) 1);

        $payload = [
            'title'              => mb_strimwidth($item->name, 0, 60),
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

        // 6) crear o actualizar
        if (!empty($item->meli_item_id)) {
            $resp = $http->put($this->api("items/{$item->meli_item_id}"), $payload);
        } else {
            $resp = $http->post($this->api('items'), $payload);
        }

        $j = (array) $resp->json();
        if ($resp->failed() || empty($j['id'])) {
            $item->update([
                'meli_status'           => 'error',
                'meli_last_error'       => substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 2000),
                'meli_category_id'      => $categoryId,
                'meli_listing_type_id'  => $listingType,
            ]);
            Log::warning('ML publish error', ['catalog_item_id' => $item->id, 'resp' => $j]);
            return ['ok' => false, 'json' => $j];
        }

        // 7) éxito
        $item->update([
            'meli_item_id'          => $j['id'],
            'meli_status'           => $j['status'] ?? 'active',
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

        $item->update(['meli_status' => 'paused', 'meli_synced_at' => now(), 'meli_last_error' => null]);
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
}
