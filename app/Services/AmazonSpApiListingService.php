<?php
// app/Services/AmazonSpApiListingService.php

namespace App\Services;

use App\Models\CatalogItem;
use Illuminate\Support\Facades\Log;

class AmazonSpApiListingService
{
    public function __construct(
        protected AmazonSpApiClient $client
    ) {}

    /**
     * Obtiene Seller SKU REAL para Amazon (amazon_sku).
     * NO usar sku interno como fallback (te da 404 NOT_FOUND).
     */
    private function sellerSku(CatalogItem $item): ?string
    {
        $sku = isset($item->amazon_sku) ? trim((string)$item->amazon_sku) : '';
        return $sku !== '' ? $sku : null;
    }

    private function sellerId(): ?string
    {
        $v = config('services.amazon_spapi.seller_id');
        $v = is_string($v) ? trim($v) : '';
        return $v !== '' ? $v : null;
    }

    private function marketplaceId(array $opts = []): ?string
    {
        $v = $opts['marketplace_id'] ?? config('services.amazon_spapi.marketplace_id');
        $v = is_string($v) ? trim($v) : '';
        return $v !== '' ? $v : null;
    }

    /**
     * Upsert listing por SKU usando Listings Items API.
     * IMPORTANTÍSIMO:
     * - El SKU debe ser el Seller SKU real de Amazon (amazon_sku).
     * - Amazon requiere productType y attributes válidos para que se publique.
     *   Este body es "mínimo" para prueba de conectividad y para listings básicos.
     */
    public function upsertBySku(CatalogItem $item, array $opts = []): array
    {
        $sellerId      = $this->sellerId();
        $marketplaceId = $this->marketplaceId($opts);
        $sellerSku     = $this->sellerSku($item);

        if (!$sellerId) {
            return [
                'ok' => false,
                'status' => null,
                'json' => null,
                'body' => null,
                'message' => 'Falta SPAPI_SELLER_ID (services.amazon_spapi.seller_id)',
            ];
        }

        if (!$marketplaceId) {
            return [
                'ok' => false,
                'status' => null,
                'json' => null,
                'body' => null,
                'message' => 'Falta SPAPI_MARKETPLACE_ID (services.amazon_spapi.marketplace_id)',
            ];
        }

        if (!$sellerSku) {
            return [
                'ok' => false,
                'status' => null,
                'json' => null,
                'body' => null,
                'message' => 'Falta amazon_sku (Seller SKU real de Amazon) en este producto.',
            ];
        }

        // ProductType: en realidad depende del catálogo/categoría.
        // Para pruebas, usa el que tú definas; si no, dejamos un placeholder.
        $productType = $opts['productType'] ?? $opts['product_type'] ?? 'PRODUCT';
        $requirements = $opts['requirements'] ?? 'LISTING';

        // Qty/price
        $qty   = max(0, (int)($item->stock ?? 0));
        $price = (float)($item->sale_price !== null ? $item->sale_price : $item->price);

        // Body mínimo (puede fallar si faltan atributos obligatorios del productType real,
        // pero al menos confirma que auth+firma+endpoint funcionan y te regresa errores de Amazon).
        $body = [
            'productType'  => $productType,
            'requirements' => $requirements,
            'attributes'   => [
                'item_name' => [[
                    'value'        => (string) $item->name,
                    'language_tag' => 'es_MX',
                ]],

                // Inventario / disponibilidad
                'fulfillment_availability' => [[
                    'fulfillment_channel_code' => 'DEFAULT',
                    'quantity'                 => $qty,
                ]],

                // Precio
                'purchasable_offer' => [[
                    'currency'  => 'MXN',
                    'our_price' => [[
                        'schedule' => [[
                            'value_with_tax' => $price,
                        ]],
                    ]],
                ]],
            ],
        ];

        $path = "/listings/2021-08-01/items/{$sellerId}/{$sellerSku}";
        $query = [
            'marketplaceIds' => $marketplaceId,
        ];

        $resp = $this->client->request('PUT', $path, $query, $body);

        if (!$resp['ok']) {
            Log::warning('Amazon SP-API upsert error', [
                'catalog_item_id' => $item->id,
                'amazon_sku'      => $sellerSku,
                'status'          => $resp['status'] ?? null,
                'json'            => $resp['json'] ?? null,
                'body'            => $resp['body'] ?? null,
            ]);

            // Amazon suele regresar errors[]; armamos mensaje amigable
            $amazonMsg = data_get($resp, 'json.errors.0.message')
                ?: data_get($resp, 'json.message')
                ?: 'No se pudo crear/actualizar listing en Amazon.';

            return [
                'ok'      => false,
                'status'  => $resp['status'] ?? null,
                'json'    => $resp['json'] ?? null,
                'body'    => $resp['body'] ?? null,
                'message' => $amazonMsg,
            ];
        }

        return [
            'ok'      => true,
            'status'  => $resp['status'] ?? null,
            'json'    => $resp['json'] ?? null,
            'body'    => $resp['body'] ?? null,
            'message' => 'Solicitud enviada a Amazon (Listings Items).',
        ];
    }

    /**
     * Ver un listing por Seller SKU (amazon_sku)
     */
    public function getBySku(CatalogItem $item, array $opts = []): array
    {
        $sellerId      = $this->sellerId();
        $marketplaceId = $this->marketplaceId($opts);
        $sellerSku     = $this->sellerSku($item);

        if (!$sellerId || !$marketplaceId) {
            return [
                'ok' => false,
                'status' => null,
                'json' => null,
                'body' => null,
                'message' => 'Faltan seller_id/marketplace_id en services.amazon_spapi',
            ];
        }

        if (!$sellerSku) {
            return [
                'ok' => false,
                'status' => null,
                'json' => null,
                'body' => null,
                'message' => 'Falta amazon_sku (Seller SKU real de Amazon).',
            ];
        }

        $path = "/listings/2021-08-01/items/{$sellerId}/{$sellerSku}";
        $query = [
            'marketplaceIds' => $marketplaceId,
        ];

        $resp = $this->client->request('GET', $path, $query, null);

        $msg = 'Ok';
        if (!$resp['ok']) {
            $msg = data_get($resp, 'json.errors.0.message')
                ?: data_get($resp, 'json.message')
                ?: 'Error consultando listing';
        }

        return [
            'ok'      => (bool)($resp['ok'] ?? false),
            'status'  => $resp['status'] ?? null,
            'json'    => $resp['json'] ?? null,
            'body'    => $resp['body'] ?? null,
            'message' => $msg,
        ];
    }
}
