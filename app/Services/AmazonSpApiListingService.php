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
     * Upsert listing por SKU usando Listings Items API.
     * Requiere: SPAPI_SELLER_ID y SPAPI_MARKETPLACE_ID.
     *
     * OJO: Amazon necesita ASIN/productType/atributos según categoría.
     * Aquí dejamos un "mínimo" para probar conectividad y flujo.
     */
    public function upsertBySku(CatalogItem $item, array $opts = []): array
    {
        $sellerId      = config('services.amazon_spapi.seller_id');
        $marketplaceId = config('services.amazon_spapi.marketplace_id');

        if (!$sellerId) {
            return [
                'ok' => false,
                'message' => 'Falta SPAPI_SELLER_ID (seller_id) en config/services.php',
            ];
        }

        if (!$marketplaceId) {
            return [
                'ok' => false,
                'message' => 'Falta SPAPI_MARKETPLACE_ID (marketplace_id) en config/services.php',
            ];
        }

        if (!$item->sku) {
            return [
                'ok' => false,
                'message' => 'Este producto no tiene SKU. Amazon requiere SKU para crear/actualizar listing.',
            ];
        }

        // Publicar en Amazon NO es como ML (title libre). Requiere productType y attributes.
        // Para un primer paso, te dejo un body "placeholder" que normalmente fallará con
        // error de validación si no está completo PERO te confirma que auth/firma funcionan.
        // Luego lo ajustamos por productType/ASIN.
        $body = [
            'productType'   => $opts['productType'] ?? 'PRODUCT', // placeholder
            'requirements'  => $opts['requirements'] ?? 'LISTING',
            'attributes'    => [
                'item_name' => [
                    [
                        'value' => (string)$item->name,
                        'language_tag' => 'es_MX',
                    ]
                ],
            ],
        ];

        // si quieres "desactivar" listing (pausa)
        if (array_key_exists('status', $opts)) {
            // algunos productTypes requieren atributo específico; esto es genérico
            // (lo ajustamos después a tu categoría real)
            $body['attributes']['fulfillment_availability'] = [[
                'fulfillment_channel_code' => 'DEFAULT',
                'quantity' => max(0, (int)($item->stock ?? 0)),
            ]];
            $body['attributes']['purchasable_offer'] = [[
                'currency' => 'MXN',
                'our_price' => [[ 'schedule' => [[ 'value_with_tax' => (float)$item->price ]] ]],
            ]];
        }

        $path = "/listings/2021-08-01/items/{$sellerId}/{$item->sku}";
        $query = [
            'marketplaceIds' => $marketplaceId,
        ];

        $resp = $this->client->request('PUT', $path, $query, $body);

        if (!$resp['ok']) {
            Log::warning('Amazon SP-API upsert error', [
                'catalog_item_id' => $item->id,
                'sku' => $item->sku,
                'status' => $resp['status'],
                'json' => $resp['json'],
                'body' => $resp['body'],
            ]);

            $msg = $resp['json']['message'] ?? 'No se pudo crear/actualizar listing en Amazon.';
            return [
                'ok' => false,
                'status' => $resp['status'],
                'json' => $resp['json'],
                'message' => $msg,
            ];
        }

        return [
            'ok' => true,
            'status' => $resp['status'],
            'json' => $resp['json'],
            'message' => 'Solicitud enviada a Amazon (Listings Items).',
        ];
    }

    /**
     * Para "ver" un listing por SKU (GET).
     */
    public function getBySku(CatalogItem $item): array
    {
        $sellerId      = config('services.amazon_spapi.seller_id');
        $marketplaceId = config('services.amazon_spapi.marketplace_id');

        if (!$sellerId || !$marketplaceId) {
            return ['ok'=>false,'message'=>'Faltan seller_id/marketplace_id en config/services.php'];
        }
        if (!$item->sku) return ['ok'=>false,'message'=>'Falta SKU.'];

        $path = "/listings/2021-08-01/items/{$sellerId}/{$item->sku}";
        $query = [
            'marketplaceIds' => $marketplaceId,
        ];

        $resp = $this->client->request('GET', $path, $query, null);

        return [
            'ok' => $resp['ok'],
            'status' => $resp['status'],
            'json' => $resp['json'],
            'body' => $resp['body'],
            'message' => $resp['ok'] ? 'Ok' : ($resp['json']['message'] ?? 'Error consultando listing'),
        ];
    }
}
