<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\MercadolibreAccount;

class ProcessMeliNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payload;

    // Retries y tiempo (ajusta a tu gusto)
    public $tries = 5;
    public $backoff = [5, 30, 60, 120, 300]; // seg

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle(): void
    {
        $topic    = $this->payload['topic']    ?? null;
        $resource = $this->payload['resource'] ?? null;

        if (!$topic || !$resource) {
            Log::warning('ProcessMeliNotification: payload sin topic/resource', $this->payload);
            return;
        }

        $account = MercadolibreAccount::first();
        if (!$account) {
            Log::warning('ProcessMeliNotification: no hay cuenta ML configurada');
            return;
        }

        // 1) Traer el recurso completo desde la API para tener datos frescos
        $url  = 'https://api.mercadolibre.com' . $resource;
        $resp = Http::withToken($account->access_token)->get($url);

        if ($resp->failed()) {
            Log::warning('ProcessMeliNotification: fetch failed', [
                'topic' => $topic,
                'resource' => $resource,
                'status' => $resp->status(),
                'body' => $resp->body(),
            ]);
            $this->release(30);
            return;
        }

        $data = $resp->json();

        // 2) Enrutar según topic
        switch ($topic) {
            case 'items':
                $this->handleItem($data);
                break;

            case 'orders':
                $this->handleOrder($data);
                break;

            case 'shipments':
                $this->handleShipment($data);
                break;

            case 'messages':
            case 'post_sale':
                $this->handleMessage($data);
                break;

            case 'prices':
            case 'catalog':
            case 'promotions':
                $this->handlePriceLike($topic, $data);
                break;

            default:
                Log::info('ProcessMeliNotification: topic no manejado', [
                    'topic' => $topic,
                    'preview' => array_slice((array)$data, 0, 5, true),
                ]);
        }
    }

    protected function handleItem(array $item): void
    {
        // Ejemplo mínimo: sincronizar título, precio y estado
        // TODO: adapta a tu esquema real (ej: Product)
        Log::info('ML items sync', [
            'id' => $item['id'] ?? null,
            'title' => $item['title'] ?? null,
            'price' => $item['price'] ?? null,
            'status' => $item['status'] ?? null,
        ]);

        // Aquí podrías:
        // - buscar Product por meli_item_id y actualizar price/stock/title/status
        // - si no existe, crear registro espejo
    }

    protected function handleOrder(array $order): void
    {
        // Ejemplo: crear/actualizar pedido interno
        Log::info('ML order sync', [
            'id' => $order['id'] ?? null,
            'status' => $order['status'] ?? null,
            'total_amount' => $order['total_amount'] ?? null,
        ]);

        // TODO:
        // - mapear comprador, items, totales, shipping, pagos
        // - upsert en tus tablas `orders`, `order_items`
    }

    protected function handleShipment(array $shipment): void
    {
        Log::info('ML shipment sync', [
            'id' => $shipment['id'] ?? null,
            'status' => $shipment['status'] ?? null,
            'tracking' => $shipment['tracking_number'] ?? null,
        ]);

        // TODO:
        // - ubicar la orden y actualizar estado/logística
    }

    protected function handleMessage(array $thread): void
    {
        Log::info('ML message sync', [
            'id' => $thread['id'] ?? null,
            'message_count' => $thread['messages_count'] ?? null,
        ]);

        // TODO:
        // - guardar últimos mensajes en tu inbox post-venta
        // - opcional: disparar alerta interna
    }

    protected function handlePriceLike(string $topic, array $data): void
    {
        Log::info("ML {$topic} sync", ['preview' => array_slice((array)$data, 0, 5, true)]);
        // TODO: actualizar precios/catálogo/promos locales
    }
}
