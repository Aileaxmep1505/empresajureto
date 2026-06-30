<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\EnviaComClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Stripe\StripeClient;

class CustomerOrdersController extends Controller
{
    public function show(Order $order)
    {
        $this->authorizeCustomerOrder($order);

        /*
         * Este controlador no solo muestra.
         * También intenta reparar órdenes viejas que quedaron incompletas:
         * - sin partidas
         * - con estado "paid" en inglés
         * - sin costo/envío guardado
         * - sin guía de Envia
         */
        $this->repairOrderIfNeeded($order);

        $order = $order->fresh() ?: $order;
        $items = $this->orderItems($order);

        return view('web.customer.orders.show', [
            'order' => $order,
            'items' => $items,
        ]);
    }

    public function reorder(Order $order)
    {
        $this->authorizeCustomerOrder($order);

        $this->repairOrderIfNeeded($order);

        $items = $this->orderItems($order);
        $cart = (array) Session::get('cart', []);

        foreach ($items as $item) {
            $productId = $item->catalog_item_id ?? $item->product_id ?? null;
            $key = $productId ? 'p_' . $productId : 'order_item_' . ($item->id ?? uniqid());

            $qty = max(1, (int) ($item->qty ?? $item->quantity ?? 1));
            $price = (float) ($item->price ?? $item->unit_price ?? 0);

            if (isset($cart[$key])) {
                $cart[$key]['qty'] = max(1, (int) ($cart[$key]['qty'] ?? 0)) + $qty;
            } else {
                $cart[$key] = [
                    'id' => $productId,
                    'catalog_item_id' => $productId,
                    'name' => $item->name ?? $item->product_name ?? 'Producto',
                    'sku' => $item->sku ?? null,
                    'price' => $price,
                    'qty' => $qty,
                    'image' => $this->itemImage($item),
                ];
            }
        }

        Session::put('cart', $cart);

        return redirect()
            ->route('web.cart.index')
            ->with('ok', 'Productos agregados nuevamente al carrito.');
    }

    public function tracking(Order $order)
    {
        $this->authorizeCustomerOrder($order);

        $this->repairOrderIfNeeded($order);
        $order = $order->fresh() ?: $order;

        $trackingNumber = $order->tracking_number
            ?? $order->shipping_tracking_number
            ?? $order->guide_number
            ?? $order->guia
            ?? null;

        $trackingUrl = $order->tracking_url ?? $order->shipping_tracking_url ?? null;
        $status = $order->shipping_status ?? $order->status ?? 'procesando';

        $events = [[
            'title' => $trackingNumber ? 'Guía generada' : 'Pedido recibido',
            'description' => $trackingNumber
                ? 'Tu guía fue generada y está lista para seguimiento.'
                : 'Tu pedido fue registrado correctamente.',
            'date' => optional($order->created_at)->format('d/m/Y H:i'),
            'status' => $status,
        ]];

        if ($trackingNumber) {
            $events[] = [
                'title' => 'En espera de recolección',
                'description' => 'La paquetería actualizará el estatus cuando reciba el paquete.',
                'date' => optional($order->updated_at)->format('d/m/Y H:i'),
                'status' => 'creado',
            ];
        }

        return response()->json([
            'ok' => true,
            'order_id' => $order->id,
            'carrier' => $order->shipping_carrier ?? $order->shipping_name ?? 'Paquetería',
            'tracking_number' => $trackingNumber,
            'tracking_url' => $trackingUrl,
            'label_url' => $order->label_url ?? $order->shipping_label_url ?? null,
            'status' => $status,
            'events' => $events,
        ]);
    }

    public function label(Order $order)
    {
        $this->authorizeCustomerOrder($order);

        $this->repairOrderIfNeeded($order);
        $order = $order->fresh() ?: $order;

        $url = $order->label_url ?? $order->shipping_label_url ?? null;

        if (!$url) {
            return back()->withErrors([
                'label' => 'Este pedido todavía no tiene guía disponible.',
            ]);
        }

        return redirect()->away($url);
    }

    public function syncEnvia(Order $order)
    {
        $this->authorizeCustomerOrder($order);

        $this->repairOrderIfNeeded($order, true);

        return back()->with('ok', 'Pedido sincronizado. Revisa el estado de envío.');
    }

    private function repairOrderIfNeeded(Order $order, bool $forceEnvia = false): void
    {
        try {
            $this->normalizeStatus($order);
            $this->restoreItemsFromStripeIfMissing($order);
            $this->restoreShippingFromSessionOrStripe($order);

            $order = $order->fresh() ?: $order;

            if ($forceEnvia || $this->shouldCreateEnviaShipment($order)) {
                $this->createEnviaShipmentForOrder($order);
            }
        } catch (\Throwable $e) {
            Log::warning('CustomerOrdersController repair error', [
                'order_id' => $order->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function normalizeStatus(Order $order): void
    {
        if (!Schema::hasColumn('orders', 'status')) {
            return;
        }

        $status = strtolower((string) ($order->status ?? ''));

        $map = [
            'paid' => 'pagado',
            'pending' => 'pendiente',
            'processing' => 'procesando',
            'completed' => 'completado',
            'cancelled' => 'cancelado',
            'canceled' => 'cancelado',
        ];

        if (isset($map[$status])) {
            DB::table('orders')->where('id', $order->id)->update([
                'status' => $map[$status],
                'updated_at' => now(),
            ]);
        }
    }

    private function restoreItemsFromStripeIfMissing(Order $order): void
    {
        if (!Schema::hasTable('order_items')) {
            return;
        }

        if ($this->orderItems($order)->count() > 0) {
            return;
        }

        $sessionId = $order->stripe_session_id ?? null;

        if (!$sessionId) {
            return;
        }

        $secret = config('services.stripe.secret');

        if (blank($secret)) {
            return;
        }

        $stripe = new StripeClient($secret);

        $lineItems = $stripe->checkout->sessions->allLineItems($sessionId, [
            'limit' => 100,
            'expand' => ['data.price.product'],
        ]);

        foreach (($lineItems->data ?? []) as $line) {
            $qty = max(1, (int) ($line->quantity ?? 1));
            $amountSubtotal = isset($line->amount_subtotal) ? ((int) $line->amount_subtotal) / 100 : 0;
            $price = $qty > 0 ? $amountSubtotal / $qty : $amountSubtotal;

            $name = $line->description
                ?? data_get($line, 'price.product.name')
                ?? 'Producto';

            $sku = data_get($line, 'price.product.metadata.sku');

            $this->createOrderItemSafe($order, [
                'name' => $name,
                'sku' => $sku,
                'qty' => $qty,
                'price' => round((float) $price, 2),
                'amount' => round((float) $amountSubtotal, 2),
                'currency' => strtoupper((string) ($line->currency ?? 'MXN')),
                'meta' => [
                    'stripe_line_item' => $line->toArray(),
                ],
            ]);
        }
    }

    private function restoreShippingFromSessionOrStripe(Order $order): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        $shipping = (array) Session::get('checkout.shipping', []);

        if (empty($shipping)) {
            $shipping = (array) Session::get('shipping', []);
        }

        $sessionId = $order->stripe_session_id ?? null;
        $stripeSession = null;

        if ($sessionId && config('services.stripe.secret')) {
            try {
                $stripe = new StripeClient(config('services.stripe.secret'));
                $stripeSession = $stripe->checkout->sessions->retrieve($sessionId, [
                    'expand' => ['shipping_cost.shipping_rate'],
                ]);
            } catch (\Throwable $e) {
                Log::warning('No se pudo leer sesión Stripe para reparar envío', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $amountShippingStripe = 0.0;
        $shippingRateName = null;

        if ($stripeSession) {
            $amountShippingStripe = isset($stripeSession->total_details->amount_shipping)
                ? ((int) $stripeSession->total_details->amount_shipping) / 100
                : 0.0;

            $shippingRateName = data_get($stripeSession, 'shipping_cost.shipping_rate.display_name')
                ?? data_get($stripeSession, 'shipping_cost.shipping_rate.fixed_amount.currency')
                ?? null;
        }

        $shippingAmount = (float) ($shipping['price'] ?? 0);

        if ($shippingAmount <= 0 && $amountShippingStripe > 0) {
            $shippingAmount = $amountShippingStripe;
        }

        $itemsSubtotal = (float) $this->orderItems($order)->sum(function ($item) {
            $qty = (int) ($item->qty ?? $item->quantity ?? 1);
            $price = (float) ($item->price ?? $item->unit_price ?? 0);
            $amount = (float) ($item->amount ?? $item->total ?? 0);

            return $amount > 0 ? $amount : ($price * max(1, $qty));
        });

        $subtotal = $itemsSubtotal > 0 ? $itemsSubtotal : (float) ($order->subtotal ?? 0);

        $total = (float) ($order->total ?? 0);
        if ($total <= $subtotal && $shippingAmount > 0) {
            $total = $subtotal + $shippingAmount;
        }

        $update = [];

        $this->putIfColumn($update, 'subtotal', round($subtotal, 2));
        $this->putIfColumn($update, 'shipping_amount', round($shippingAmount, 2));
        $this->putIfColumn($update, 'total', round($total > 0 ? $total : ($subtotal + $shippingAmount), 2));

        $carrier = $shipping['carrier'] ?? $order->shipping_carrier ?? null;
        $name = $shipping['name'] ?? $order->shipping_name ?? $shippingRateName ?? null;
        $service = $shipping['service'] ?? $order->shipping_service ?? null;

        $this->putIfColumn($update, 'shipping_provider', $shipping['provider'] ?? 'envia.com');
        $this->putIfColumn($update, 'shipping_name', $name);
        $this->putIfColumn($update, 'shipping_carrier', $carrier);
        $this->putIfColumn($update, 'shipping_service', $service);
        $this->putIfColumn($update, 'shipping_eta', $shipping['eta'] ?? $order->shipping_eta ?? null);
        $this->putIfColumn($update, 'shipping_logo_url', $shipping['logo_url'] ?? $order->shipping_logo_url ?? null);
        $this->putIfColumn($update, 'shipping_rate_code', $shipping['selected_id'] ?? $shipping['code'] ?? $order->shipping_rate_code ?? null);
        $this->putIfColumn($update, 'shipping_raw', !empty($shipping) ? json_encode($shipping, JSON_UNESCAPED_UNICODE) : ($order->shipping_raw ?? null));
        $this->putIfColumn($update, 'updated_at', now());

        if (!empty($update)) {
            DB::table('orders')->where('id', $order->id)->update($update);
        }
    }

    private function shouldCreateEnviaShipment(Order $order): bool
    {
        $status = strtolower((string) ($order->status ?? ''));

        if (!in_array($status, ['pagado', 'paid', 'completado'], true)) {
            return false;
        }

        foreach (['tracking_number', 'shipping_tracking_number', 'guide_number', 'guia'] as $field) {
            if (!empty($order->{$field})) {
                return false;
            }
        }

        return !empty($order->shipping_carrier) || !empty(Session::get('checkout.shipping.carrier'));
    }

    private function createEnviaShipmentForOrder(Order $order): void
    {
        try {
            if (!class_exists(EnviaComClient::class)) {
                return;
            }

            $shippingSession = (array) Session::get('checkout.shipping', []);
            if (empty($shippingSession)) {
                $shippingSession = (array) Session::get('shipping', []);
            }

            $carrier = strtolower(trim((string) (
                $shippingSession['carrier_key']
                ?? $shippingSession['carrier']
                ?? $order->shipping_carrier
                ?? ''
            )));

            if ($carrier === '') {
                return;
            }

            $service = (string) (
                $shippingSession['service']
                ?? $order->shipping_service
                ?? ''
            );

            if (method_exists($this, 'resolveEnviaServiceCode')) {
                $service = $this->resolveEnviaServiceCode($carrier, $service, $shippingSession['raw'] ?? ($order->shipping_raw ?? null));
            }

            if ($this->isUnsupportedBranchServiceForAutomaticGenerate($carrier, $service)) {
                Log::warning('Servicio Paquetexpress Ocurre omitido para generación automática. Selecciona servicio domicilio a domicilio.', [
                    'order_id' => $order->id ?? null,
                    'carrier' => $carrier,
                    'service' => $service,
                ]);
                return;
            }

            /** @var EnviaComClient $envia */
            $envia = app(EnviaComClient::class);

            if (!method_exists($envia, 'generate')) {
                Log::warning('EnviaComClient no tiene método generate()', ['order_id' => $order->id]);
                return;
            }

            $origin = $this->enviaOriginAddress();
            $destination = $this->enviaDestinationFromOrder($order);
            $packages = [$this->enviaPackageFromOrder($order)];

            if (empty($destination['postalCode'])) {
                return;
            }

            $shipmentPayload = [
                'type' => 1,
                'carrier' => $carrier,
                'service' => $service,
                'reference' => 'ORDER-' . $order->id,
                'comments' => 'Pedido JURETO #' . $order->id,
            ];

            $originBranchCode = $this->resolveEnviaBranchCode($shippingSession['raw'] ?? ($order->shipping_raw ?? null));

            if ($originBranchCode) {
                $shipmentPayload['originBranchCode'] = $originBranchCode;
                $shipmentPayload['origin_branch_code'] = $originBranchCode;
                $shipmentPayload['branchCode'] = $originBranchCode;
            }

            $payload = $envia->generate($origin, $destination, $packages, $shipmentPayload);

            $trackingNumber = data_get($payload, 'data.0.trackingNumber')
                ?? data_get($payload, 'data.trackingNumber')
                ?? data_get($payload, 'trackingNumber')
                ?? data_get($payload, 'data.0.tracking_number')
                ?? data_get($payload, 'data.tracking_number');

            $trackingUrl = data_get($payload, 'data.0.trackingUrl')
                ?? data_get($payload, 'data.trackingUrl')
                ?? data_get($payload, 'trackingUrl')
                ?? data_get($payload, 'data.0.tracking_url')
                ?? data_get($payload, 'data.tracking_url');

            $labelUrl = data_get($payload, 'data.0.label')
                ?? data_get($payload, 'data.label')
                ?? data_get($payload, 'label')
                ?? data_get($payload, 'data.0.labelUrl')
                ?? data_get($payload, 'data.labelUrl')
                ?? data_get($payload, 'labelUrl');

            $update = [];
            $this->putIfColumn($update, 'shipping_status', $trackingNumber ? 'creado' : 'pendiente');
            $this->putIfColumn($update, 'tracking_number', $trackingNumber);
            $this->putIfColumn($update, 'shipping_tracking_number', $trackingNumber);
            $this->putIfColumn($update, 'guide_number', $trackingNumber);
            $this->putIfColumn($update, 'guia', $trackingNumber);
            $this->putIfColumn($update, 'tracking_url', $trackingUrl);
            $this->putIfColumn($update, 'shipping_tracking_url', $trackingUrl);
            $this->putIfColumn($update, 'label_url', $labelUrl);
            $this->putIfColumn($update, 'shipping_label_url', $labelUrl);
            $this->putIfColumn($update, 'envia_payload', json_encode($payload, JSON_UNESCAPED_UNICODE));
            $this->putIfColumn($update, 'updated_at', now());

            if (!empty($update)) {
                DB::table('orders')->where('id', $order->id)->update($update);
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo crear envío Envia desde mi cuenta', [
                'order_id' => $order->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function createOrderItemSafe(Order $order, array $data): void
    {
        if (!Schema::hasTable('order_items')) {
            return;
        }

        $insert = ['order_id' => $order->id];

        $this->putIfColumnTable($insert, 'catalog_item_id', $data['catalog_item_id'] ?? null, 'order_items');
        $this->putIfColumnTable($insert, 'product_id', $data['product_id'] ?? null, 'order_items');
        $this->putIfColumnTable($insert, 'name', $data['name'] ?? 'Producto', 'order_items');
        $this->putIfColumnTable($insert, 'sku', $data['sku'] ?? null, 'order_items');
        $this->putIfColumnTable($insert, 'qty', $data['qty'] ?? 1, 'order_items');
        $this->putIfColumnTable($insert, 'quantity', $data['qty'] ?? 1, 'order_items');
        $this->putIfColumnTable($insert, 'price', $data['price'] ?? 0, 'order_items');
        $this->putIfColumnTable($insert, 'unit_price', $data['price'] ?? 0, 'order_items');
        $this->putIfColumnTable($insert, 'amount', $data['amount'] ?? 0, 'order_items');
        $this->putIfColumnTable($insert, 'total', $data['amount'] ?? 0, 'order_items');
        $this->putIfColumnTable($insert, 'currency', $data['currency'] ?? 'MXN', 'order_items');
        $this->putIfColumnTable($insert, 'meta', json_encode($data['meta'] ?? [], JSON_UNESCAPED_UNICODE), 'order_items');
        $this->putIfColumnTable($insert, 'created_at', now(), 'order_items');
        $this->putIfColumnTable($insert, 'updated_at', now(), 'order_items');

        DB::table('order_items')->insert($insert);
    }

    private function orderItems(Order $order)
    {
        try {
            if (method_exists($order, 'items')) {
                $items = $order->items()->get();

                if ($items->count() > 0) {
                    return $items;
                }
            }
        } catch (\Throwable $e) {
            // fallback por tabla
        }

        if (Schema::hasTable('order_items')) {
            return DB::table('order_items')
                ->where('order_id', $order->id)
                ->orderBy('id')
                ->get();
        }

        return collect();
    }

    private function authorizeCustomerOrder(Order $order): void
    {
        abort_unless(Auth::check(), 403);

        $userId = Auth::id();
        $email = Auth::user()?->email;
        $belongsToUser = false;

        if (isset($order->user_id) && (int) $order->user_id === (int) $userId) {
            $belongsToUser = true;
        }

        if (!$belongsToUser && isset($order->customer_id) && (int) $order->customer_id === (int) $userId) {
            $belongsToUser = true;
        }

        if (!$belongsToUser && $email) {
            foreach (['customer_email', 'email', 'billing_email'] as $field) {
                if (isset($order->{$field}) && strtolower((string) $order->{$field}) === strtolower($email)) {
                    $belongsToUser = true;
                    break;
                }
            }
        }

        abort_unless($belongsToUser, 403);
    }

    private function itemImage($item): ?string
    {
        $meta = $item->meta ?? null;

        if (is_string($meta)) {
            $decoded = json_decode($meta, true);
            $meta = is_array($decoded) ? $decoded : [];
        }

        if (is_array($meta) && !empty($meta['image'])) {
            return $meta['image'];
        }

        return $item->image ?? $item->image_url ?? null;
    }

    private function putIfColumn(array &$update, string $column, mixed $value): void
    {
        if ($value !== null && Schema::hasColumn('orders', $column)) {
            $update[$column] = $value;
        }
    }

    private function putIfColumnTable(array &$insert, string $column, mixed $value, string $table): void
    {
        if ($value !== null && Schema::hasColumn($table, $column)) {
            $insert[$column] = $value;
        }
    }

    private function enviaOriginAddress(): array
    {
        $origin = (array) config('services.envia.origin', []);

        return [
            'name' => $origin['name'] ?? env('ENVIA_ORIGIN_NAME', 'Jureto'),
            'company' => $origin['company'] ?? env('ENVIA_ORIGIN_COMPANY', 'Jureto'),
            'email' => $origin['email'] ?? env('ENVIA_ORIGIN_EMAIL', 'ventas@jureto.com.mx'),
            'phone' => $origin['phone'] ?? env('ENVIA_ORIGIN_PHONE', '7220000000'),
            'street' => $origin['street'] ?? env('ENVIA_ORIGIN_STREET', ''),
            'number' => $origin['number'] ?? env('ENVIA_ORIGIN_NUMBER', 'S/N'),
            'district' => $origin['district'] ?? env('ENVIA_ORIGIN_DISTRICT', ''),
            'city' => $origin['city'] ?? env('ENVIA_ORIGIN_CITY', ''),
            'state' => $origin['state'] ?? env('ENVIA_ORIGIN_STATE', 'EM'),
            'country' => $origin['country'] ?? env('ENVIA_ORIGIN_COUNTRY', 'MX'),
            'postalCode' => $origin['postal_code'] ?? env('ENVIA_ORIGIN_POSTAL_CODE', ''),
            'reference' => $origin['reference'] ?? env('ENVIA_ORIGIN_REFERENCE', ''),
        ];
    }

    private function enviaDestinationFromOrder(Order $order): array
    {
        $address = (array) ($order->address_json ?? []);

        return [
            'name' => $order->customer_name ?? $address['contact_name'] ?? Auth::user()?->name ?? 'Cliente',
            'company' => $order->customer_name ?? 'Cliente',
            'email' => $order->customer_email ?? Auth::user()?->email ?? 'cliente@jureto.com.mx',
            'phone' => $address['phone'] ?? $order->customer_phone ?? '7220000000',
            'street' => $address['street'] ?? '',
            'number' => $address['ext_number'] ?? $address['number'] ?? 'S/N',
            'district' => $address['colony'] ?? '',
            'city' => $address['municipality'] ?? '',
            'state' => $this->enviaStateCode($address['state'] ?? ''),
            'country' => 'MX',
            'postalCode' => $address['postal_code'] ?? '',
            'reference' => trim(($address['references'] ?? '') . ' ' . ($address['between_street_1'] ?? '') . ' ' . ($address['between_street_2'] ?? '')),
        ];
    }

    private function enviaPackageFromOrder(Order $order): array
    {
        return [
            'content' => 'Productos JURETO',
            'amount' => 1,
            'type' => 'box',
            'weight' => (float) env('ENVIA_PACKAGE_WEIGHT', 1),
            'insurance' => 0,
            'declaredValue' => (float) ($order->total ?? 0),
            'weightUnit' => 'KG',
            'lengthUnit' => 'CM',
            'dimensions' => [
                'length' => (float) env('ENVIA_PACKAGE_LENGTH', 30),
                'width' => (float) env('ENVIA_PACKAGE_WIDTH', 25),
                'height' => (float) env('ENVIA_PACKAGE_HEIGHT', 20),
            ],
        ];
    }

    private function enviaStateCode(string $state): string
    {
        $key = mb_strtolower(trim($state));

        $map = [
            'aguascalientes' => 'AG',
            'baja california' => 'BC',
            'baja california sur' => 'BS',
            'campeche' => 'CM',
            'chiapas' => 'CS',
            'chihuahua' => 'CH',
            'ciudad de mexico' => 'CX',
            'ciudad de méxico' => 'CX',
            'cdmx' => 'CX',
            'coahuila' => 'CO',
            'colima' => 'CL',
            'durango' => 'DG',
            'estado de mexico' => 'EM',
            'estado de méxico' => 'EM',
            'mexico' => 'EM',
            'méxico' => 'EM',
            'guanajuato' => 'GT',
            'guerrero' => 'GR',
            'hidalgo' => 'HG',
            'jalisco' => 'JA',
            'michoacan' => 'MI',
            'michoacán' => 'MI',
            'morelos' => 'MO',
            'nayarit' => 'NA',
            'nuevo leon' => 'NL',
            'nuevo león' => 'NL',
            'oaxaca' => 'OA',
            'puebla' => 'PU',
            'queretaro' => 'QT',
            'querétaro' => 'QT',
            'quintana roo' => 'QR',
            'san luis potosi' => 'SL',
            'san luis potosí' => 'SL',
            'sinaloa' => 'SI',
            'sonora' => 'SO',
            'tabasco' => 'TB',
            'tamaulipas' => 'TM',
            'tlaxcala' => 'TL',
            'veracruz' => 'VE',
            'yucatan' => 'YU',
            'yucatán' => 'YU',
            'zacatecas' => 'ZA',
        ];

        return $map[$key] ?? strtoupper(substr($state, 0, 2));
    }
    /**
     * Extrae el código de sucursal origen para servicios tipo
     * Paquetexpress Ocurre - domicilio / ground_od.
     *
     * Envia lo pide con el error:
     * Origin branch code is required for branch to home service.
     */
    private function resolveEnviaBranchCode(mixed $raw = null): ?string
    {
        $rawArr = null;

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode(html_entity_decode($raw), true);
            $rawArr = is_array($decoded) ? $decoded : null;
        } elseif (is_array($raw)) {
            $rawArr = $raw;
        }

        if ($rawArr) {
            $direct = data_get($rawArr, 'originBranchCode')
                ?? data_get($rawArr, 'origin_branch_code')
                ?? data_get($rawArr, 'branchCode')
                ?? data_get($rawArr, 'branch_code');

            if ($direct) {
                return (string) $direct;
            }

            $branches = data_get($rawArr, 'branches', []);

            if (is_array($branches) && !empty($branches)) {
                $first = $branches[0];

                $code = data_get($first, 'branch_code')
                    ?? data_get($first, 'branchCode')
                    ?? data_get($first, 'branch_id')
                    ?? data_get($first, 'branchId');

                if ($code) {
                    return (string) $code;
                }
            }
        }

        $envBranch = env('ENVIA_ORIGIN_BRANCH_CODE');

        return $envBranch ? (string) $envBranch : null;
    }

    private function isUnsupportedBranchServiceForAutomaticGenerate(string $carrier, string $service): bool
    {
        $carrier = strtolower(trim($carrier));
        $service = strtolower(trim($service));

        return $carrier === 'paquetexpress' && in_array($service, ['ground_od', 'ground_do'], true);
    }

}
