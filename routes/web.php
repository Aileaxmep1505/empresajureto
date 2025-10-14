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
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\ContactController;
use App\Http\Controllers\Web\ShopController;
use App\Http\Controllers\Web\CustomerAuthController;
use App\Http\Controllers\Panel\LandingSectionController;


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
| Verificación de correo
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
| Dashboard (auth + verified + approved)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
   
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

/*
|--------------------------------------------------------------------------
| Rutas protegidas de la app (auth + approved)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'approved'])->group(function () {

    // -------- Productos: rutas específicas ANTES del resource --------
    Route::get('products/import', [ProductController::class, 'importForm'])->name('products.import.form');
    Route::post('products/import', [ProductController::class, 'importStore'])->name('products.import.store');

    Route::get('products/export/pdf', [ProductController::class, 'exportPdf'])->name('products.export.pdf');

    // Resource con restricción numérica para evitar colisión con /products/import
    Route::resource('products', ProductController::class)
        ->parameters(['products' => 'product'])
        ->whereNumber('product');

    // -------- Proveedores --------
    Route::resource('providers', ProviderController::class)->names('providers');

    // -------- Clientes --------
    Route::resource('clients', ClientController::class)->names('clients');

    // -------- Cotizaciones --------
    Route::resource('cotizaciones', CotizacionController::class);
    Route::post('cotizaciones/{cotizacion}/aprobar', [CotizacionController::class,'aprobar'])->name('cotizaciones.aprobar');
    Route::post('cotizaciones/{cotizacion}/rechazar', [CotizacionController::class,'rechazar'])->name('cotizaciones.rechazar');
    Route::get('cotizaciones/{cotizacion}/pdf', [CotizacionController::class,'pdf'])->name('cotizaciones.pdf');
    Route::post('cotizaciones/{cotizacion}/convertir-venta', [CotizacionController::class,'convertirAVenta'])->name('cotizaciones.convertir');

    // IA / auto-cotización
    Route::post('/cotizaciones/ai-parse', [CotizacionController::class, 'aiParse'])
        ->name('cotizaciones.ai_parse');

    // Guardar/crear desde la salida de AI — usa el método existente aiCreate
    Route::post('/cotizaciones/ai-store', [CotizacionController::class, 'aiCreate'])
        ->name('cotizaciones.ai_store');

    // Alias opcional compatible (por si tu front lo usa así)
    Route::post('/cotizaciones/store-from-ai', [CotizacionController::class, 'aiCreate'])
        ->name('cotizaciones.store_from_ai');

    // Form y creación automática
    Route::get('/cotizaciones/auto', [CotizacionController::class, 'autoForm'])->name('cotizaciones.auto.form');
    Route::post('/cotizaciones/auto', [CotizacionController::class, 'autoCreate'])->name('cotizaciones.auto.create');

    // Buscador de productos (AJAX)
    Route::get('/cotizaciones/buscar-productos', [CotizacionController::class,'buscarProductos'])
        ->name('cotizaciones.buscar_productos');

    // -------- Ventas (solo index/show) --------
    Route::resource('ventas', VentaController::class)->only(['index','show']);

    // -------- Perfil --------
    Route::get('/perfil', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/perfil/foto', [ProfileController::class, 'updatePhoto'])->name('profile.update.photo');
    Route::put('/perfil/password', [ProfileController::class, 'updatePassword'])->name('profile.update.password');
});

/*
|--------------------------------------------------------------------------
| Media público (storage/app/public)
|--------------------------------------------------------------------------
*/
Route::get('/media/{path}', [MediaController::class, 'show'])
    ->where('path', '.*')
    ->name('media.show');

/*
|--------------------------------------------------------------------------
| Notificaciones del usuario autenticado
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/me/notifications', [NotificationController::class, 'index'])
        ->name('me.notifications.index');
    Route::post('/me/notifications/read-all', [NotificationController::class, 'readAll'])
        ->name('me.notifications.readAll');
});

Route::get('/diag/http', function () {
    $out = [];

    try {
        $r1 = Http::timeout(15)->head('https://api.ocr.space/parse/image');
        $out['ocr_head'] = ['ok' => $r1->successful() || $r1->clientError() || $r1->serverError(), 'status' => $r1->status()];
    } catch (\Throwable $e) {
        $out['ocr_head'] = ['ok' => false, 'error' => $e->getMessage()];
    }

    try {
        $r2 = Http::timeout(15)->withHeaders(['Authorization' => 'Bearer x'])->get('https://api.openai.com/v1/models');
        $out['openai_models'] = ['ok' => $r2->status() !== 0, 'status' => $r2->status()];
    } catch (\Throwable $e) {
        $out['openai_models'] = ['ok' => false, 'error' => $e->getMessage()];
    }

    try {
        $path = storage_path('logs/http_diag.log');
        file_put_contents($path, '['.date('c')."] diag ok\n", FILE_APPEND);
        $out['write_logs'] = ['ok' => true, 'path' => $path];
    } catch (\Throwable $e) {
        $out['write_logs'] = ['ok' => false, 'error' => $e->getMessage()];
    }

    return response()->json($out);
});
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');
    Route::get('/ventas/{venta}/pdf', [\App\Http\Controllers\VentaController::class, 'pdf'])
    ->name('ventas.pdf');

   Route::post('/ventas/{venta}/email', [VentaController::class, 'enviarPorCorreo'])
    ->name('ventas.email')
    ->middleware('auth'); 

/* ======= Web pública ======= */
Route::get('/', [HomeController::class, 'index'])->name('web.home');

Route::get('/contacto', [ContactController::class, 'show'])->name('web.contacto');
Route::post('/contacto', [ContactController::class, 'send'])->name('web.contacto.send');

Route::get('/ventas', [ShopController::class, 'index'])->name('web.ventas.index');
Route::get('/ventas/{id}', [ShopController::class, 'show'])->name('web.ventas.show');

/* Auth cliente (público) */
Route::middleware('guest.customer')->group(function () {
    Route::get('/cliente/login', [CustomerAuthController::class, 'showLogin'])->name('customer.login');
    Route::post('/cliente/login', [CustomerAuthController::class, 'login'])->name('customer.login.post');

    Route::get('/cliente/register', [CustomerAuthController::class, 'showRegister'])->name('customer.register');
    Route::post('/cliente/register', [CustomerAuthController::class, 'register'])->name('customer.register.post');
});

Route::post('/cliente/logout', [CustomerAuthController::class, 'logout'])
    ->middleware('auth.customer')
    ->name('customer.logout');

/* Ejemplo de rutas que requieren login de cliente (no chocan con tu interno) */
Route::middleware('auth.customer')->group(function () {
    // carrito, checkout, pedidos, perfil, etc.
    // Route::get('/carrito', ...)->name('web.carrito');
});

Route::middleware(['auth']) // tu guard interno
    ->prefix('panel/landing')
    ->name('panel.landing.')
    ->group(function () {
        Route::get('/', [LandingSectionController::class, 'index'])->name('index');
        Route::get('/create', [LandingSectionController::class, 'create'])->name('create');
        Route::post('/', [LandingSectionController::class, 'store'])->name('store');
        Route::get('/{section}/edit', [LandingSectionController::class, 'edit'])->name('edit');
        Route::put('/{section}', [LandingSectionController::class, 'update'])->name('update');
        Route::delete('/{section}', [LandingSectionController::class, 'destroy'])->name('destroy');

        Route::post('/{section}/reorder', [LandingSectionController::class, 'reorder'])->name('reorder'); // AJAX
        Route::post('/{section}/toggle',  [LandingSectionController::class, 'toggle'])->name('toggle');
    });