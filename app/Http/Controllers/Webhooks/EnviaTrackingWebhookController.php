<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnviaTrackingWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $secret = env('ENVIA_WEBHOOK_SECRET');

        if ($secret) {
            $provided = $request->header('X-Webhook-Secret')
                ?: $request->header('X-ENVIA-WEBHOOK-SECRET')
                ?: $request->query('secret');

            if (!hash_equals((string) $secret, (string) $provided)) {
                Log::warning('[EnviaWebhook] Secreto invalido', [
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'ok' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }
        }

        $payload = $request->all();

        $trackingCode = data_get($payload, 'tracking_number')
            ?: data_get($payload, 'tracking_code')
            ?: data_get($payload, 'shipment.tracking_number')
            ?: data_get($payload, 'shipment.tracking_code')
            ?: data_get($payload, 'data.tracking_number')
            ?: data_get($payload, 'data.tracking_code')
            ?: data_get($payload, 'guide')
            ?: data_get($payload, 'guia');

        $orderId = data_get($payload, 'order_id')
            ?: data_get($payload, 'reference')
            ?: data_get($payload, 'external_reference')
            ?: data_get($payload, 'shipment.reference')
            ?: data_get($payload, 'data.reference');

        $order = null;

        if ($trackingCode) {
            $order = Order::where('shipping_code', $trackingCode)->latest()->first();
        }

        if (!$order && $orderId && is_numeric($orderId)) {
            $order = Order::where('id', (int) $orderId)->latest()->first();
        }

        if (!$order && !$trackingCode && !$orderId) {
            Log::warning('[EnviaWebhook] Sin guia ni pedido para localizar orden', [
                'payload' => $payload,
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'No tracking code or order reference found',
            ], 422);
        }

        if (!$order) {
            Log::warning('[EnviaWebhook] Pedido no encontrado', [
                'tracking_code' => $trackingCode,
                'order_id' => $orderId,
                'payload' => $payload,
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $eventTitle = data_get($payload, 'event')
            ?: data_get($payload, 'status')
            ?: data_get($payload, 'description')
            ?: data_get($payload, 'data.status')
            ?: data_get($payload, 'shipment.status')
            ?: 'Movimiento de paqueteria';

        $eventDate = data_get($payload, 'date')
            ?: data_get($payload, 'datetime')
            ?: data_get($payload, 'created_at')
            ?: data_get($payload, 'data.date')
            ?: data_get($payload, 'shipment.updated_at')
            ?: now()->format('d/m/Y H:i');

        $location = data_get($payload, 'location')
            ?: data_get($payload, 'city')
            ?: data_get($payload, 'data.location')
            ?: data_get($payload, 'shipment.location');

        $normalizedStatus = $this->timelineStatus((string) $eventTitle);

        $meta = $order->shipping_meta ?? [];

        if (is_string($meta)) {
            $decoded = json_decode($meta, true);
            $meta = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        if (!is_array($meta)) {
            $meta = [];
        }

        $events = $meta['events'] ?? $meta['tracking_events'] ?? [];

        if (!is_array($events)) {
            $events = [];
        }

        $events[] = [
            'title' => $eventTitle,
            'date' => $eventDate,
            'location' => $location,
            'status' => $normalizedStatus,
            'raw' => $payload,
        ];

        $meta['events'] = $events;
        $meta['tracking_events'] = $events;
        $meta['last_webhook_at'] = now()->toDateTimeString();
        $meta['last_tracking_payload'] = $payload;

        if ($trackingCode && empty($order->shipping_code)) {
            $order->shipping_code = $trackingCode;
        }

        $order->shipment_status = $eventTitle;
        $order->shipping_meta = $meta;

        $trackingUrl = data_get($payload, 'tracking_url')
            ?: data_get($payload, 'shipment.tracking_url')
            ?: data_get($payload, 'data.tracking_url');

        if ($trackingUrl && property_exists($order, 'shipping_tracking_url')) {
            $order->shipping_tracking_url = $trackingUrl;
        }

        $order->save();

        Log::info('[EnviaWebhook] Tracking actualizado', [
            'order_id' => $order->id,
            'shipping_code' => $order->shipping_code,
            'event' => $eventTitle,
        ]);

        return response()->json([
            'ok' => true,
            'order_id' => $order->id,
            'events_count' => count($events),
        ]);
    }

    private function timelineStatus(string $status): string
    {
        $text = mb_strtolower($status);

        if (
            str_contains($text, 'entregado') ||
            str_contains($text, 'delivered')
        ) {
            return 'done';
        }

        if (
            str_contains($text, 'transito') ||
            str_contains($text, 'trÃ¡nsito') ||
            str_contains($text, 'ruta') ||
            str_contains($text, 'reparto') ||
            str_contains($text, 'transit')
        ) {
            return 'current';
        }

        return 'done';
    }
}
