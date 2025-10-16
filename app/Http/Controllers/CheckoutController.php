<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class CheckoutController extends Controller
{
    private StripeClient $stripe;

    public function __construct()
    {
        $secret = config('services.stripe.secret');

        if (blank($secret)) {
            // Evita que Stripe falle silenciosamente si no hay secreto configurado
            throw new \RuntimeException('Falta STRIPE_SECRET en el .env o en config/services.php');
        }

        $this->stripe = new StripeClient($secret);
    }

    /**
     * Crear sesión de Stripe Checkout para "Comprar ahora" (un solo producto).
     * POST /checkout/item/{item}
     */
   // Antes:
// public function checkoutItem(Request $req, \App\Models\CatalogItem $item)

// Después:
public function checkoutItem(Request $req, $item)
{
    try {
        $model = \App\Models\CatalogItem::query()->findOrFail($item); // <- evita 404 automático
        $qty   = max(1, (int) $req->input('qty', 1));
        $price = $model->sale_price ?? $model->price ?? 0;

        if ($price <= 0) {
            return response()->json(['error' => 'Precio inválido para este producto.'], 400);
        }

        $successUrl = config('services.stripe.success_url')
            ?: route('checkout.success', ['session_id' => '{CHECKOUT_SESSION_ID}']);
        $cancelUrl  = config('services.stripe.cancel_url')
            ?: route('checkout.cancel');

        $session = $this->stripe->checkout->sessions->create([
            'mode'                 => 'payment',
            'payment_method_types' => ['card'],
            'locale'               => 'es-419',
            'customer_email'       => Auth::user()->email ?? null,
            'success_url'          => $successUrl,
            'cancel_url'           => $cancelUrl,
            'metadata'             => [
                'type'            => 'buy_now',
                'catalog_item_id' => (string) $model->id,
                'user_id'         => Auth::id() ?: '',
                'qty'             => (string) $qty,
            ],
            'line_items' => [[
                'quantity'   => $qty,
                'price_data' => [
                    'currency'     => 'mxn',
                    'unit_amount'  => (int) round($price * 100),
                    'product_data' => [
                        'name'        => $model->name,
                        'description' => 'SKU: ' . ($model->sku ?? '—'),
                        'images'      => array_filter([$model->image_url]),
                    ],
                ],
            ]],
            'shipping_address_collection' => ['allowed_countries' => ['MX']],
            'allow_promotion_codes'       => true,
            'automatic_tax'               => ['enabled' => false],
        ]);

        return response()->json(['url' => $session->url], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['error' => 'Producto no encontrado.'], 404);
    } catch (\Throwable $e) {
        \Log::error('Stripe checkoutItem error: '.$e->getMessage(), ['file'=>$e->getFile(),'line'=>$e->getLine()]);
        return response()->json(['error' => 'No se pudo crear la sesión de pago.'], 500);
    }
}


    /**
     * Crear sesión de Stripe Checkout para pagar el carrito completo.
     * POST /checkout/cart
     *
     * Espera que el carrito esté en sesión como un arreglo:
     *   [['item' => CatalogItem|id, 'qty' => 2], ...]
     */
    public function checkoutCart(Request $req)
    {
        try {
            $cart = collect(session('cart', []));
            if ($cart->isEmpty()) {
                return response()->json(['error' => 'Tu carrito está vacío.'], 400);
            }

            $lineItems     = [];
            $metadataItems = [];

            foreach ($cart as $row) {
                $product = is_object($row['item'])
                    ? $row['item']
                    : \App\Models\CatalogItem::find($row['item']);

                if (!$product) {
                    continue;
                }

                $qty   = max(1, (int) ($row['qty'] ?? 1));
                $price = $product->sale_price ?? $product->price ?? 0;
                if ($price <= 0) {
                    continue;
                }

                $lineItems[] = [
                    'quantity'   => $qty,
                    'price_data' => [
                        'currency'     => 'mxn',
                        'unit_amount'  => (int) round($price * 100),
                        'product_data' => [
                            'name'        => $product->name,
                            'description' => 'SKU: ' . ($product->sku ?? '—'),
                            'images'      => array_filter([$product->image_url]),
                        ],
                    ],
                ];

                $metadataItems[] = $product->id . 'x' . $qty;
            }

            if (empty($lineItems)) {
                return response()->json(['error' => 'No hay artículos válidos en el carrito.'], 400);
            }

            // URLs
            $successUrl = config('services.stripe.success_url');
            $cancelUrl  = config('services.stripe.cancel_url');

            if (blank($successUrl)) {
                $successUrl = route('checkout.success', ['session_id' => '{CHECKOUT_SESSION_ID}']);
            }
            if (blank($cancelUrl)) {
                $cancelUrl = route('checkout.cancel');
            }

            $session = $this->stripe->checkout->sessions->create([
                'mode'                 => 'payment',
                'payment_method_types' => ['card'],
                'locale'               => 'es-419',
                'customer_email'       => Auth::user()->email ?? null,
                'success_url'          => $successUrl,
                'cancel_url'           => $cancelUrl,
                'metadata'             => [
                    'type'    => 'cart',
                    'user_id' => Auth::id() ?: '',
                    'items'   => implode(',', $metadataItems),
                ],
                'line_items'                  => $lineItems,
                'allow_promotion_codes'       => true,
                'shipping_address_collection' => ['allowed_countries' => ['MX']],
            ]);

            return response()->json(['url' => $session->url], 200);

        } catch (\Throwable $e) {
            Log::error('Stripe checkoutCart error: ' . $e->getMessage(), [
                'file' => $e->getFile(), 'line' => $e->getLine(),
            ]);
            return response()->json(['error' => 'No se pudo crear la sesión de pago del carrito.'], 500);
        }
    }

    /**
     * Página de éxito (opcionalmente muestra el session_id)
     * GET /checkout/success
     */
    public function success(Request $req)
    {
        $sessionId = $req->query('session_id');
        return view('checkout.success', compact('sessionId'));
    }

    /**
     * Página de cancelación
     * GET /checkout/cancel
     */
    public function cancel()
    {
        return view('checkout.cancel');
    }

    /**
     * Webhook de Stripe para confirmar pagos
     * POST /stripe/webhook
     * Recuerda EXCEPTUAR esta ruta del CSRF (en VerifyCsrfToken o usando Route::post sin middleware CSRF).
     */
    public function webhook(Request $req)
    {
        $payload   = $req->getContent();
        $sigHeader = $req->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        if (blank($secret)) {
            // Si no configuraste webhook_secret, devuelve 200 para evitar reintentos infinitos de Stripe
            Log::warning('Stripe webhook recibido sin WEBHOOK_SECRET configurado.');
            return response('ok', 200);
        }

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook signature error: ' . $e->getMessage());
            return response('Invalid', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            /** @var \Stripe\Checkout\Session $session */
            $session = $event->data->object;

            // TODO: crea/actualiza tu Order:
            // - buscar/crear orden por $session->id
            // - usar $session->metadata (type, catalog_item_id/items, user_id, qty)
            // - marcar como 'paid'
            // - guardar amount_total, customer_email, shipping_details, etc.
        }

        return response('ok', 200);
    }
}
