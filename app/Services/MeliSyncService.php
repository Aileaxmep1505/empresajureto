<?php
// app/Services/MeliSyncService.php
namespace App\Services;

use App\Models\CatalogItem;
use App\Services\MeliHttp;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class MeliSyncService
{
    /** Construye el endpoint según SANDBOX o PRODUCCIÓN */
    private function api(string $path): string
    {
        $base = config('services.meli.sandbox')
            ? 'https://api.mercadolibre.com.sandbox/'
            : 'https://api.mercadolibre.com/';
        return rtrim($base, '/').'/'.ltrim($path, '/');
    }

    /** Publica o actualiza en ML y marca campos en DB */
    public function sync(CatalogItem $item): array
    {
        $http = MeliHttp::withFreshToken();

        // 0) Estado de la cuenta (para shipping)
        $meResp = $http->get($this->api('users/me'));
        $me = $meResp->ok() ? $meResp->json() : [];
        $env = Arr::get($me, 'status.mercadoenvios', 'not_accepted'); // accepted|not_accepted|mandatory
        $shippingMode = ($env === 'accepted' || $env === 'mandatory') ? 'me2' : 'custom';

        // 1) listing_type_id permitido
        $userId = Arr::get($me, 'id');
        $listings = [];
        if ($userId) {
            $ltResp = $http->get($this->api("users/{$userId}/available_listing_types"), ['site_id'=>'MLM']);
            $listings = $ltResp->ok() ? $ltResp->json() : [];
        }
        $listingType = $item->meli_listing_type_id ?: ($listings[0]['id'] ?? 'gold_special');

        // 2) category_id (usa el guardado o predice por título)
        $categoryId = $item->meli_category_id;
        if (!$categoryId) {
            $predResp = $http->get($this->api('sites/MLM/domain_discovery/search'), ['q'=>$item->name]);
            $pred = $predResp->ok() ? $predResp->json() : [];
            $categoryId = $pred[0]['category_id'] ?? 'MLM3530'; // fallback genérico “Otros”
        }

        // 3) pictures: portada + extras
        $pics = [];
        if ($item->mainPicture()) $pics[] = ['source' => $item->mainPicture()];
        foreach (($item->images ?? []) as $u) {
            if ($u && $u !== $item->mainPicture()) $pics[] = ['source'=>$u];
            if (count($pics) >= 6) break;
        }
        if (empty($pics)) {
            // placeholder pública para evitar rechazo por falta de imagen
            $pics[] = ['source'=>'https://http2.mlstatic.com/storage/developers-site-cms-admin/openapi/319102622313-testimage.jpeg'];
        }

        // 4) atributos mínimos requeridos (defaults)
        $attributes = [];
        $brand = trim((string)($item->brand_name ?? 'Genérica'));
        $model = trim((string)($item->model_name ?? $item->sku ?? 'Modelo Único'));
        $attributes[] = ['id'=>'BRAND','value_name'=>$brand];
        $attributes[] = ['id'=>'MODEL','value_name'=>$model];

        // 5) payload común
        $price = (float)($item->sale_price ?? $item->price ?? 0);
        if ($price < 5) $price = 5.00; // mínimos prácticos
        $qty = max(1, (int)1);

        $payload = [
            'title'              => mb_strimwidth($item->name, 0, 60), // mejor tasa de aceptación
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
                ? ['mode'=>'me2','local_pick_up'=>true,'free_shipping'=>false]
                : ['mode'=>'custom'],
        ];

        // 6) crear o actualizar
        if ($item->meli_item_id) {
            $resp = $http->put($this->api("items/{$item->meli_item_id}"), $payload);
        } else {
            $resp = $http->post($this->api('items'), $payload);
        }

        $j = $resp->json();
        if ($resp->failed() || empty($j['id'])) {
            $item->update([
                'meli_status' => 'error',
                'meli_last_error' => substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 2000),
                'meli_category_id' => $categoryId,
                'meli_listing_type_id' => $listingType,
            ]);
            Log::warning('ML publish error', ['catalog_item_id'=>$item->id, 'resp'=>$j, 'sandbox'=>config('services.meli.sandbox')]);
            return ['ok'=>false, 'json'=>$j];
        }

        // 7) éxito
        $item->update([
            'meli_item_id' => $j['id'],
            'meli_status'  => $j['status'] ?? 'active',
            'meli_category_id' => $categoryId,
            'meli_listing_type_id' => $listingType,
            'meli_synced_at' => now(),
            'meli_last_error' => null,
        ]);

        return ['ok'=>true, 'json'=>$j];
    }

    /** Pausar en ML */
    public function pause(CatalogItem $item): array
    {
        if (!$item->meli_item_id) return ['ok'=>true,'json'=>['msg'=>'sin meli_item_id']];
        $http = MeliHttp::withFreshToken();
        $resp = $http->put($this->api("items/{$item->meli_item_id}"), ['status'=>'paused']);
        $j = $resp->json();
        if ($resp->failed()) {
            $item->update(['meli_status'=>'error','meli_last_error'=>substr(json_encode($j,JSON_UNESCAPED_UNICODE),0,2000)]);
            return ['ok'=>false,'json'=>$j];
        }
        $item->update(['meli_status'=>'paused','meli_synced_at'=>now(),'meli_last_error'=>null]);
        return ['ok'=>true,'json'=>$j];
    }

    private function plainText(CatalogItem $i): string
    {
        $base = $i->excerpt ?: strip_tags((string)$i->description);
        $base = trim($base) ?: "{$i->name}.\n\nVendido por JURETO. Factura disponible. Garantía estándar.\n";
        // ML recomienda <= 5000 chars, sin HTML
        return mb_substr(preg_replace('/\s+/', ' ', $base), 0, 4800);
    }
}
