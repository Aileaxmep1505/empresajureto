<?php

namespace App\Support;

use App\Models\Order;
use App\Services\EnviaComClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Arr;

class OrderCheckoutFinalizer
{
    /**
     * Finaliza una orden pagada:
     * 1) Guarda partidas desde carrito si la orden quedó sin items.
     * 2) Guarda envío seleccionado desde session('checkout.shipping').
     * 3) Crea guía/envío en Envia.com si todavía no existe.
     *
     * Úsalo después de confirmar el pago.
     */
    public static function finalize(Order $order): Order
    {
        try {
            self::persistItemsFromCartIfMissing($order);
        } catch (\Throwable $e) {
            Log::warning('OrderCheckoutFinalizer: no se pudieron guardar partidas', [
                'order_id' => $order->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            self::persistShippingFromSession($order);
        } catch (\Throwable $e) {
            Log::warning('OrderCheckoutFinalizer: no se pudo guardar envío seleccionado', [
                'order_id' => $order->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            self::createEnviaShipmentIfNeeded($order);
        } catch (\Throwable $e) {
            Log::warning('OrderCheckoutFinalizer: no se pudo crear guía Envia', [
                'order_id' => $order->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        return $order->refresh();
    }

    private static function persistItemsFromCartIfMissing(Order $order): void
    {
        if (!Schema::hasTable('order_items')) {
            return;
        }

        $alreadyHasItems = DB::table('order_items')
            ->where('order_id', $order->id)
            ->exists();

        if ($alreadyHasItems) {
            return;
        }

        $cart = (array) Session::get('cart', []);

        if (empty($cart)) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($cart as $row) {
            if (!is_array($row)) {
                continue;
            }

            $qty = max(1, (int) ($row['qty'] ?? $row['quantity'] ?? 1));
            $price = (float) ($row['price'] ?? $row['unit_price'] ?? 0);
            $amount = $price * $qty;

            $insert = [
                'order_id' => $order->id,
            ];

            self::putIfColumn($insert, 'catalog_item_id', $row['catalog_item_id'] ?? $row['id'] ?? null, 'order_items');
            self::putIfColumn($insert, 'product_id', $row['product_id'] ?? $row['id'] ?? null, 'order_items');
            self::putIfColumn($insert, 'name', $row['name'] ?? 'Producto', 'order_items');
            self::putIfColumn($insert, 'sku', $row['sku'] ?? null, 'order_items');
            self::putIfColumn($insert, 'qty', $qty, 'order_items');
            self::putIfColumn($insert, 'quantity', $qty, 'order_items');
            self::putIfColumn($insert, 'price', $price, 'order_items');
            self::putIfColumn($insert, 'unit_price', $price, 'order_items');
            self::putIfColumn($insert, 'amount', $amount, 'order_items');
            self::putIfColumn($insert, 'total', $amount, 'order_items');
            self::putIfColumn($insert, 'meta', json_encode([
                'image' => $row['image'] ?? null,
                'slug' => $row['slug'] ?? null,
                'raw' => $row,
            ], JSON_UNESCAPED_UNICODE), 'order_items');

            self::putIfColumn($insert, 'created_at', $now, 'order_items');
            self::putIfColumn($insert, 'updated_at', $now, 'order_items');

            $rows[] = $insert;
        }

        if (!empty($rows)) {
            DB::table('order_items')->insert($rows);
        }
    }

    private static function persistShippingFromSession(Order $order): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        $shipping = (array) Session::get('checkout.shipping', []);

        if (empty($shipping)) {
            $shipping = (array) Session::get('shipping', []);
        }

        if (empty($shipping)) {
            return;
        }

        $shippingAmount = (float) ($shipping['price'] ?? 0);

        $itemsSubtotal = self::itemsSubtotal($order);
        $subtotal = $itemsSubtotal > 0 ? $itemsSubtotal : (float) ($order->subtotal ?? 0);
        $total = $subtotal + $shippingAmount;

        $update = [];

        self::putIfColumn($update, 'status', 'pagado', 'orders');
        self::putIfColumn($update, 'subtotal', $subtotal, 'orders');
        self::putIfColumn($update, 'shipping_amount', $shippingAmount, 'orders');
        self::putIfColumn($update, 'total', $total, 'orders');

        self::putIfColumn($update, 'shipping_provider', $shipping['provider'] ?? 'envia.com', 'orders');
        self::putIfColumn($update, 'shipping_name', $shipping['name'] ?? $shipping['carrier'] ?? $shipping['label'] ?? null, 'orders');
        self::putIfColumn($update, 'shipping_carrier', $shipping['carrier'] ?? null, 'orders');
        self::putIfColumn($update, 'shipping_service', $shipping['service'] ?? null, 'orders');
        self::putIfColumn($update, 'shipping_eta', $shipping['eta'] ?? null, 'orders');
        self::putIfColumn($update, 'shipping_logo_url', $shipping['logo_url'] ?? null, 'orders');
        self::putIfColumn($update, 'shipping_rate_code', $shipping['selected_id'] ?? $shipping['code'] ?? null, 'orders');
        self::putIfColumn($update, 'shipping_raw', $shipping['raw'] ?? null, 'orders');
        self::putIfColumn($update, 'updated_at', now(), 'orders');

        if (!empty($update)) {
            DB::table('orders')->where('id', $order->id)->update($update);
        }
    }

    private static function createEnviaShipmentIfNeeded(Order $order): void
    {
        if (!class_exists(EnviaComClient::class)) {
            return;
        }

        if (!Schema::hasTable('orders')) {
            return;
        }

        $order = $order->fresh();

        if (!$order) {
            return;
        }

        $hasTracking = self::hasAnyValue($order, [
            'tracking_number',
            'shipping_tracking_number',
            'guide_number',
            'guia',
        ]);

        if ($hasTracking) {
            return;
        }

        $shipping = (array) Session::get('checkout.shipping', []);

        if (empty($shipping)) {
            $shipping = (array) Session::get('shipping', []);
        }

        $carrier = strtolower((string) ($order->shipping_carrier ?? $shipping['carrier'] ?? ''));

        if ($carrier === '') {
            return;
        }

        $service = (string) ($order->shipping_service ?? $shipping['service'] ?? '');

        $address = self::destinationAddress($order);

        if (empty($address['postalCode'])) {
            Log::warning('OrderCheckoutFinalizer: sin código postal para crear guía Envia', [
                'order_id' => $order->id,
                'address' => $address,
            ]);
            return;
        }

        $origin = self::originAddress();
        $packages = [self::packageFromOrder($order)];

        /** @var EnviaComClient $envia */
        $envia = app(EnviaComClient::class);

        $shipment = [
            'type' => 1,
            'carrier' => $carrier,
            'service' => $service,
            'reference' => 'ORDER-' . $order->id,
            'comments' => 'Pedido JURETO #' . $order->id,
        ];

        $payload = $envia->generate($origin, $address, $packages, $shipment);

        $normalized = method_exists($envia, 'normalizeGeneratedShipment')
            ? $envia->normalizeGeneratedShipment($payload)
            : [];

        $trackingNumber = Arr::get($normalized, 'tracking_number')
            ?? Arr::get($normalized, 'trackingNumber')
            ?? Arr::get($payload, 'data.0.trackingNumber')
            ?? Arr::get($payload, 'data.trackingNumber')
            ?? Arr::get($payload, 'trackingNumber')
            ?? Arr::get($payload, 'shipment.trackingNumber');

        $trackingUrl = Arr::get($normalized, 'tracking_url')
            ?? Arr::get($normalized, 'trackingUrl')
            ?? Arr::get($payload, 'data.0.trackingUrl')
            ?? Arr::get($payload, 'data.trackingUrl')
            ?? Arr::get($payload, 'trackingUrl');

        $labelUrl = Arr::get($normalized, 'label_url')
            ?? Arr::get($normalized, 'labelUrl')
            ?? Arr::get($payload, 'data.0.label')
            ?? Arr::get($payload, 'data.0.labelUrl')
            ?? Arr::get($payload, 'data.label')
            ?? Arr::get($payload, 'data.labelUrl')
            ?? Arr::get($payload, 'label')
            ?? Arr::get($payload, 'labelUrl');

        $update = [];

        self::putIfColumn($update, 'shipping_status', 'creado', 'orders');
        self::putIfColumn($update, 'tracking_number', $trackingNumber, 'orders');
        self::putIfColumn($update, 'shipping_tracking_number', $trackingNumber, 'orders');
        self::putIfColumn($update, 'guide_number', $trackingNumber, 'orders');
        self::putIfColumn($update, 'guia', $trackingNumber, 'orders');
        self::putIfColumn($update, 'tracking_url', $trackingUrl, 'orders');
        self::putIfColumn($update, 'shipping_tracking_url', $trackingUrl, 'orders');
        self::putIfColumn($update, 'label_url', $labelUrl, 'orders');
        self::putIfColumn($update, 'shipping_label_url', $labelUrl, 'orders');
        self::putIfColumn($update, 'envia_payload', json_encode($payload, JSON_UNESCAPED_UNICODE), 'orders');
        self::putIfColumn($update, 'updated_at', now(), 'orders');

        if (!empty($update)) {
            DB::table('orders')->where('id', $order->id)->update($update);
        }
    }

    private static function originAddress(): array
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

    private static function destinationAddress(Order $order): array
    {
        $address = (array) ($order->address_json ?? []);

        if (empty($address)) {
            $address = (array) Session::get('checkout.address', []);
        }

        return [
            'name' => $order->customer_name ?? $address['name'] ?? auth()->user()?->name ?? 'Cliente',
            'company' => $address['company'] ?? $order->customer_name ?? 'Cliente',
            'email' => $order->customer_email ?? auth()->user()?->email ?? $address['email'] ?? 'cliente@jureto.com.mx',
            'phone' => $address['phone'] ?? $order->customer_phone ?? '7220000000',
            'street' => $address['street'] ?? '',
            'number' => $address['ext_number'] ?? $address['number'] ?? 'S/N',
            'district' => $address['colony'] ?? $address['district'] ?? '',
            'city' => $address['municipality'] ?? $address['city'] ?? '',
            'state' => self::stateCode($address['state'] ?? ''),
            'country' => 'MX',
            'postalCode' => $address['postal_code'] ?? $address['zip'] ?? '',
            'reference' => $address['references'] ?? '',
        ];
    }

    private static function packageFromOrder(Order $order): array
    {
        $weight = (float) env('ENVIA_PACKAGE_WEIGHT', 1);
        $length = (float) env('ENVIA_PACKAGE_LENGTH', 30);
        $width = (float) env('ENVIA_PACKAGE_WIDTH', 25);
        $height = (float) env('ENVIA_PACKAGE_HEIGHT', 20);

        return [
            'content' => 'Productos JURETO',
            'amount' => 1,
            'type' => 'box',
            'weight' => $weight,
            'insurance' => 0,
            'declaredValue' => (float) ($order->total ?? 0),
            'weightUnit' => 'KG',
            'lengthUnit' => 'CM',
            'dimensions' => [
                'length' => $length,
                'width' => $width,
                'height' => $height,
            ],
        ];
    }

    private static function itemsSubtotal(Order $order): float
    {
        if (!Schema::hasTable('order_items')) {
            return 0.0;
        }

        $rows = DB::table('order_items')->where('order_id', $order->id)->get();

        if ($rows->isEmpty()) {
            return 0.0;
        }

        return (float) $rows->sum(function ($row) {
            $qty = (int) ($row->qty ?? $row->quantity ?? 1);
            $price = (float) ($row->price ?? $row->unit_price ?? 0);
            $amount = (float) ($row->amount ?? $row->total ?? 0);

            return $amount > 0 ? $amount : ($price * max(1, $qty));
        });
    }

    private static function putIfColumn(array &$update, string $column, mixed $value, string $table): void
    {
        if ($value === null) {
            return;
        }

        if (Schema::hasColumn($table, $column)) {
            $update[$column] = $value;
        }
    }

    private static function hasAnyValue(Order $order, array $fields): bool
    {
        foreach ($fields as $field) {
            if (!empty($order->{$field})) {
                return true;
            }
        }

        return false;
    }

    private static function stateCode(string $state): string
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
}
