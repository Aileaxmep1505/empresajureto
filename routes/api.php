<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\ShopifyWebhookController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\WhatsAppWebhookController;

Route::get('/products', [ProductController::class, 'apiIndex']);
Route::get('/products/{product}', [ProductController::class, 'apiShow']);

Route::post('/plan-rutas', [RouteController::class, 'plan'])->name('api.plan.rutas');

Route::get('/whatsapp/webhook', function (Request $request) {
    $verifyToken = 'jureto_whatsapp_2026';

    $mode = $request->query('hub_mode');
    $token = $request->query('hub_verify_token');
    $challenge = $request->query('hub_challenge');

    if ($mode === 'subscribe' && $token === $verifyToken) {
        return response($challenge, 200);
    }

    return response('Forbidden', 403);
});

Route::post('/whatsapp/webhook', function (Request $request) {
    Log::info('WHATSAPP WEBHOOK', $request->all());

    return response('OK', 200);
});


Route::post('/webhooks/shopify/orders-paid', [ShopifyWebhookController::class, 'ordersPaid'])
    ->name('webhooks.shopify.orders-paid');




use App\Http\Controllers\CronQueueController;


Route::get('/queue/run', [CronQueueController::class, 'run'])
    ->name('api.queue.run');

Route::get('/queue/ping', [CronQueueController::class, 'ping'])
    ->name('api.queue.ping');

Route::get('/queue/status', [CronQueueController::class, 'status'])
    ->name('api.queue.status');

Route::get('/queue/unlock', [CronQueueController::class, 'unlock'])
    ->name('api.queue.unlock');