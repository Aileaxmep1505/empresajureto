<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::get('/ping', fn () => response()->json(['ok' => true]));

Route::get('/products', [ProductController::class, 'apiIndex']);
Route::get('/products/{product}', [ProductController::class, 'apiShow'])->whereNumber('product');
