<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\VentaController;

/*
|--------------------------------------------------------------------------
| Invitados
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    // Registro
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');

    // Olvidé/Restablecer contraseña
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Autenticados
|--------------------------------------------------------------------------
*/
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
| Verificación de correo (Laravel)
| - Aviso para verificar
| - Confirmación desde el enlace del email
| - Reenviar enlace de verificación
*/
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [AuthController::class, 'verifyNotice'])->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
        ->middleware(['throttle:6,1'])
        ->name('verification.send');
});

/*
|--------------------------------------------------------------------------
| Dashboard (protegido)
| Requiere: autenticado + correo verificado + aprobado por admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Administración (solo admin)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth', 'verified', 'approved', 'role:admin'])->group(function () {
    Route::get('/users', [UserManagementController::class, 'index'])->name('admin.users.index');
    Route::post('/users/{user}/approve', [UserManagementController::class, 'approve'])->name('admin.users.approve');
    Route::post('/users/{user}/revoke', [UserManagementController::class, 'revoke'])->name('admin.users.revoke');
    Route::post('/users/{user}/role', [UserManagementController::class, 'assignRole'])->name('admin.users.role.assign');
    Route::delete('/users/{user}/role/{role}', [UserManagementController::class, 'removeRole'])->name('admin.users.role.remove');
});

Route::middleware(['auth','approved'])->group(function () {
    Route::resource('products', ProductController::class)->parameters(['products' => 'product']);
});
Route::middleware(['auth','approved'])->group(function () {
    Route::resource('products', ProductController::class)->parameters(['products'=>'product']);
    Route::get('products-export/pdf', [ProductController::class,'exportPdf'])->name('products.export.pdf');
});
Route::middleware(['auth','approved'])->group(function () {
    Route::resource('providers', ProviderController::class)
        ->names('providers'); // index, create, store, edit, update, destroy, show (no lo usamos)
});


Route::middleware(['auth','approved'])->group(function () {
    Route::resource('clients', ClientController::class)->names('clients');
});

Route::resource('cotizaciones', CotizacionController::class);

Route::post('cotizaciones/{cotizacion}/aprobar', [CotizacionController::class,'aprobar'])->name('cotizaciones.aprobar');
Route::post('cotizaciones/{cotizacion}/rechazar', [CotizacionController::class,'rechazar'])->name('cotizaciones.rechazar');
Route::get('cotizaciones/{cotizacion}/pdf', [CotizacionController::class,'pdf'])->name('cotizaciones.pdf');
Route::post('cotizaciones/{cotizacion}/convertir-venta', [CotizacionController::class,'convertirAVenta'])->name('cotizaciones.convertir');
Route::resource('ventas', VentaController::class)->only(['index','show']);

// routes/web.php
Route::post('/cotizaciones/ai-parse', [CotizacionController::class, 'aiParse'])
    ->name('cotizaciones.ai_parse');
Route::get('/cotizaciones/auto', [\App\Http\Controllers\CotizacionController::class, 'autoForm'])->name('cotizaciones.auto.form');
Route::post('/cotizaciones/auto', [\App\Http\Controllers\CotizacionController::class, 'autoCreate'])->name('cotizaciones.auto.create');
