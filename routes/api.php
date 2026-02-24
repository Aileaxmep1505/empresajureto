<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
// routes/api.php
use App\Http\Controllers\RouteController;
use App\Http\Controllers\WhatsAppWebhookController;

Route::get('/products', [ProductController::class, 'apiIndex']);
Route::get('/products/{product}', [ProductController::class, 'apiShow']);


Route::post('/plan-rutas', [RouteController::class, 'plan'])->name('api.plan.rutas');
Route::get('/whatsapp/webhook',  [WhatsAppWebhookController::class, 'verify']);
Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle']);