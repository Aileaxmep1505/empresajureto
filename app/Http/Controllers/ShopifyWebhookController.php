<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use App\Models\Order;
use App\Services\ShopifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShopifyWebhookController extends Controller
{
    /**
     * Webhook recomendado: orders/paid
     *
     * Flujo:
     * Shopify vende y descuenta su inventario interno.
     * Shopify manda este webhook.
     * JURETO crea la orden.
     * JURETO descuenta el stock local.
     *
     * IMPORTANTE:
     * Aquí NO volvemos a mandar stock a Shopify para evitar doble movimiento,
     * porque Shopify ya descontó su propio inventario al concretarse la venta.
     */
    public function ordersPaid(Request $request): JsonResponse
    {
        if (!$this->isValidShopifyWebhook($request)) {
            Log::warning('Shopify webhook rechazado: firma inválida', [
                'topic' => $request->header('X-Shopify-Topic'),
                'shop' => $request->header('X-Shopify-Shop-Domain'),
                'webhook_id' => $request->header('X-Shopify-Webhook-Id'),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Invalid Shopify webhook signature.',
            ], 401);
        }

        $payload = $request->json()->all();

        if (empty($payload)) {
            $payload = $request->all();
        }

        try {
            $result = DB::transaction(function () use ($payload) {
                $shopifyOrderId = $payload['id'] ?? null;

                if (!$shopifyOrderId) {
                    Log::warning('Shopify webhook sin order id', [
                        'payload_keys' => array_keys($payload),
                    ]);

                    return [
                        'created' => false,
                        'message' => 'Webhook sin order id.',
                    ];
                }

                $externalId = 'shopify_' . $shopifyOrderId;

                /**
                 * Usamos stripe_session_id como llave externa porque tu modelo Order ya lo tiene.
                 * Esto evita duplicar órdenes si Shopify reintenta el webhook.
                 */
                $existing = Order::where('stripe_session_id', $externalId)->first();

                if ($existing) {
                    return [
                        'created' => false,
                        'message' => 'Orden ya existía en JURETO.',
                        'order_id' => $existing->id,
                    ];
                }

                $customerName = $this->customerNameFromPayload($payload);
                $customerEmail = $payload['email']
                    ?? $payload['customer']['email']
                    ?? null;

                $shippingAddress = $payload['shipping_address'] ?? null;
                $billingAddress = $payload['billing_address'] ?? null;

                $order = Order::create([
                    'user_id' => null,
                    'billing_profile_id' => null,

                    'customer_name' => $customerName,
                    'customer_email' => $customerEmail,

                    'subtotal' => $this->money($payload['subtotal_price'] ?? 0),
                    'shipping_amount' => $this->money($payload['total_shipping_price_set']['shop_money']['amount'] ?? 0),
                    'tax' => $this->money($payload['total_tax'] ?? 0),
                    'total' => $this->money($payload['total_price'] ?? 0),
                    'currency' => $payload['currency'] ?? 'MXN',

                    'status' => 'paid',
                    'address_json' => $shippingAddress ?: $billingAddress,

                    /**
                     * Llave externa Shopify.
                     */
                    'stripe_session_id' => $externalId,
                ]);

                $createdItems = 0;
                $missingItems = [];

                foreach (($payload['line_items'] ?? []) as $line) {
                    $quantity = (int) ($line['quantity'] ?? 0);

                    if ($quantity <= 0) {
                        continue;
                    }

                    $sku = trim((string) ($line['sku'] ?? ''));

                    /**
                     * Preferimos SKU porque es la llave maestra entre JURETO y Shopify.
                     * Si por alguna razón no viene SKU, intentamos usar variant_id.
                     */
                    $shopifyVariantId = $line['variant_id'] ?? null;
                    $variantGid = $shopifyVariantId
                        ? 'gid://shopify/ProductVariant/' . $shopifyVariantId
                        : null;

                    $itemQuery = CatalogItem::query();

                    if ($sku !== '') {
                        $itemQuery->where('sku', $sku);
                    } elseif ($variantGid) {
                        $itemQuery->where('shopify_variant_id', $variantGid);
                    } else {
                        Log::warning('Línea Shopify sin SKU ni variant_id', [
                            'shopify_order_id' => $shopifyOrderId,
                            'line' => $line,
                        ]);

                        continue;
                    }

                    $item = $itemQuery->lockForUpdate()->first();

                    if (!$item) {
                        $missingItems[] = [
                            'sku' => $sku ?: null,
                            'variant_id' => $shopifyVariantId,
                            'name' => $line['name'] ?? null,
                            'quantity' => $quantity,
                        ];

                        Log::warning('Producto Shopify no encontrado en JURETO', [
                            'shopify_order_id' => $shopifyOrderId,
                            'sku' => $sku,
                            'variant_id' => $shopifyVariantId,
                            'variant_gid' => $variantGid,
                            'line_name' => $line['name'] ?? null,
                        ]);

                        continue;
                    }

                    $stockBefore = (int) $item->stock;
                    $stockAfter = max(0, $stockBefore - $quantity);

                    $item->forceFill([
                        'stock' => $stockAfter,
                    ])->save();

                    $unitPrice = $this->money($line['price'] ?? 0);
                    $lineTotal = $unitPrice * $quantity;

                    $order->items()->create([
                        'catalog_item_id' => $item->id,
                        'name' => $line['name'] ?? $item->name,
                        'sku' => $sku ?: $item->sku,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total' => $lineTotal,
                    ]);

                    $createdItems++;

                    Log::info('Stock JURETO descontado por venta Shopify', [
                        'shopify_order_id' => $shopifyOrderId,
                        'order_id' => $order->id,
                        'catalog_item_id' => $item->id,
                        'sku' => $item->sku,
                        'quantity' => $quantity,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                    ]);
                }

                return [
                    'created' => true,
                    'message' => 'Orden Shopify registrada en JURETO.',
                    'order_id' => $order->id,
                    'created_items' => $createdItems,
                    'missing_items' => $missingItems,
                ];
            });

            return response()->json([
                'ok' => true,
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error procesando webhook Shopify orders/paid', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'shopify_order_id' => $payload['id'] ?? null,
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Error procesando webhook Shopify.',
            ], 500);
        }
    }

    /**
     * Opcional: si registras orders/create en vez de orders/paid,
     * puedes apuntar el webhook a este método.
     */
    public function ordersCreate(Request $request): JsonResponse
    {
        return $this->ordersPaid($request);
    }

    /**
     * Método para sincronizar manualmente un producto desde botón o ruta web.
     *
     * Esto sirve para:
     * - Crear producto en Shopify si no existe.
     * - Actualizar producto si ya existe.
     * - Actualizar precio, SKU e inventario.
     */
    public function syncCatalogItem(CatalogItem $item, ShopifyService $shopify)
    {
        try {
            if ($item->is_sample) {
                return back()->with('error', 'Las muestras no se sincronizan con Shopify.');
            }

            if (!$item->sku) {
                return back()->with('error', 'El producto necesita SKU para sincronizarse con Shopify.');
            }

            $shopify->syncCatalogItem($item);

            return back()->with('ok', 'Producto sincronizado correctamente con Shopify.');
        } catch (\Throwable $e) {
            Log::error('Error sincronizando producto con Shopify desde botón', [
                'catalog_item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error Shopify: ' . $e->getMessage());
        }
    }

    private function isValidShopifyWebhook(Request $request): bool
    {
        $secret = (string) config('services.shopify.webhook_secret');

        if ($secret === '') {
            Log::warning('SHOPIFY_WEBHOOK_SECRET no está configurado.');
            return false;
        }

        $hmac = $request->header('X-Shopify-Hmac-Sha256');

        if (!$hmac) {
            return false;
        }

        $calculated = base64_encode(hash_hmac(
            'sha256',
            $request->getContent(),
            $secret,
            true
        ));

        return hash_equals($hmac, $calculated);
    }

    private function customerNameFromPayload(array $payload): ?string
    {
        $firstName = trim((string) ($payload['customer']['first_name'] ?? ''));
        $lastName = trim((string) ($payload['customer']['last_name'] ?? ''));

        $fullName = trim($firstName . ' ' . $lastName);

        if ($fullName !== '') {
            return $fullName;
        }

        $shippingName = trim((string) ($payload['shipping_address']['name'] ?? ''));

        if ($shippingName !== '') {
            return $shippingName;
        }

        $billingName = trim((string) ($payload['billing_address']['name'] ?? ''));

        if ($billingName !== '') {
            return $billingName;
        }

        return 'Cliente Shopify';
    }

    private function money(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return round((float) $value, 2);
    }
}