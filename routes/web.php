<?php
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

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
use App\Http\Controllers\Web\CatalogController;

use App\Http\Controllers\Panel\LandingSectionController;
use App\Http\Controllers\Admin\CatalogItemController;
use App\Http\Controllers\Web\CartController;

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\SearchController; // <- tu controlador actual
use App\Http\Controllers\Web\CommentController;
use App\Http\Controllers\Web\FavoriteController;
use App\Http\Controllers\Customer\CustomerAreaController;
use App\Http\Controllers\Web\HelpCenterController;
use App\Http\Controllers\Admin\HelpDeskAdminController;
/*
|--------------------------------------------------------------------------
| AUTH (ÚNICO login con AuthController)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login'])->name('login.post');

    // Registro SOLO para personal interno (opcional)
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register'])->name('register.post');

    // Reset de contraseña (ajusta a tu implementación)
    Route::get('/forgot-password',        [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password',       [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password',        [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/email/verify',                 [AuthController::class, 'verifyNotice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}',     [AuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
        ->middleware(['throttle:6,1'])->name('verification.send');
});

/*
|--------------------------------------------------------------------------
| WEB PÚBLICA
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('web.home');

Route::get('/ventas',      [ShopController::class, 'index'])->name('web.ventas.index');
Route::get('/ventas/{id}', [ShopController::class, 'show'])->name('web.ventas.show');

Route::get('/contacto', [ContactController::class, 'show'])->name('web.contacto');
Route::post('/contacto', [ContactController::class, 'send'])->name('web.contacto.send');
/* ===== Catálogo público (CatalogItem) ===== */
Route::prefix('catalogo')->name('web.catalog.')->group(function () {
    Route::get('/',                   [CatalogController::class, 'index'])->name('index'); // listado
    Route::get('/{catalogItem:slug}', [CatalogController::class, 'show'])->name('show');   // detalle por slug
});

/*
|--------------------------------------------------------------------------
| BIENVENIDA DE “CLIENTE” (misma sesión web)
| - El AuthController te puede redirigir aquí según rol o lógica
|--------------------------------------------------------------------------
*/
Route::get('/customer/welcome', function () {
    $customer = Auth::user();
    $cart     = session('cart', []);
    return view('web.customer.welcome', compact('customer', 'cart'));
})->middleware('auth')->name('customer.welcome');

/*
|--------------------------------------------------------------------------
| PANEL INTERNO (auth + approved)  URLs: /panel/...
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'approved'])->prefix('panel')->group(function () {

    // Dashboard interno
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Ventas internas
    Route::resource('ventas', VentaController::class)
        ->only(['index','show'])
        ->names('ventas');

    Route::get('ventas/{venta}/pdf',   [VentaController::class, 'pdf'])->name('ventas.pdf');
    Route::post('ventas/{venta}/email',[VentaController::class, 'enviarPorCorreo'])->name('ventas.email');

    // Productos (uso interno)
    Route::get('products/import',     [ProductController::class, 'importForm'])->name('products.import.form');
    Route::post('products/import',    [ProductController::class, 'importStore'])->name('products.import.store');
    Route::get('products/export/pdf', [ProductController::class, 'exportPdf'])->name('products.export.pdf');
    Route::resource('products', ProductController::class)
        ->parameters(['products' => 'product'])
        ->whereNumber('product');

    // Proveedores / Clientes
    Route::resource('providers', ProviderController::class)->names('providers');
    Route::resource('clients',   ClientController::class)->names('clients');

    // Cotizaciones
    Route::resource('cotizaciones', CotizacionController::class);
    Route::post('cotizaciones/{cotizacion}/aprobar',         [CotizacionController::class,'aprobar'])->name('cotizaciones.aprobar');
    Route::post('cotizaciones/{cotizacion}/rechazar',        [CotizacionController::class,'rechazar'])->name('cotizaciones.rechazar');
    Route::get('cotizaciones/{cotizacion}/pdf',              [CotizacionController::class,'pdf'])->name('cotizaciones.pdf');
    Route::post('cotizaciones/{cotizacion}/convertir-venta', [CotizacionController::class,'convertirAVenta'])->name('cotizaciones.convertir');

    // IA / auto-cotización
    Route::post('cotizaciones/ai-parse',      [CotizacionController::class, 'aiParse'])->name('cotizaciones.ai_parse');
    Route::post('cotizaciones/ai-store',      [CotizacionController::class, 'aiCreate'])->name('cotizaciones.ai_store');
    Route::post('cotizaciones/store-from-ai', [CotizacionController::class, 'aiCreate'])->name('cotizaciones.store_from_ai');
    Route::get('cotizaciones/auto',           [CotizacionController::class, 'autoForm'])->name('cotizaciones.auto.form');
    Route::post('cotizaciones/auto',          [CotizacionController::class, 'autoCreate'])->name('cotizaciones.auto.create');

    // Perfil interno
    Route::get('/perfil',           [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/perfil/foto',      [ProfileController::class, 'updatePhoto'])->name('profile.update.photo');
    Route::put('/perfil/password',  [ProfileController::class, 'updatePassword'])->name('profile.update.password');

    // Landing del panel
    Route::prefix('landing')->name('panel.landing.')->group(function () {
        Route::get('/',               [LandingSectionController::class, 'index'])->name('index');
        Route::get('/create',         [LandingSectionController::class, 'create'])->name('create');
        Route::post('/',              [LandingSectionController::class, 'store'])->name('store');
        Route::get('/{section}/edit', [LandingSectionController::class, 'edit'])->name('edit');
        Route::put('/{section}',      [LandingSectionController::class, 'update'])->name('update');
        Route::delete('/{section}',   [LandingSectionController::class, 'destroy'])->name('destroy');
        Route::post('/{section}/reorder', [LandingSectionController::class, 'reorder'])->name('reorder');
        Route::post('/{section}/toggle',  [LandingSectionController::class, 'toggle'])->name('toggle');
    });
});

/*
|--------------------------------------------------------------------------
| ADMIN (role:admin)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth', 'verified', 'approved', 'role:admin'])->group(function () {
    Route::get('/users',                       [UserManagementController::class, 'index'])->name('admin.users.index');
    Route::post('/users/{user}/approve',       [UserManagementController::class, 'approve'])->name('admin.users.approve');
    Route::post('/users/{user}/revoke',        [UserManagementController::class, 'revoke'])->name('admin.users.revoke');
    Route::post('/users/{user}/role',          [UserManagementController::class, 'assignRole'])->name('admin.users.role.assign');
    Route::delete('/users/{user}/role/{role}', [UserManagementController::class, 'removeRole'])->name('admin.users.role.remove');

    // CRUD Catálogo web (CatalogItem)
    Route::resource('catalog', CatalogItemController::class)
        ->parameters(['catalog' => 'catalogItem'])
        ->names('admin.catalog');

    Route::patch('catalog/{catalogItem}/toggle', [CatalogItemController::class, 'toggleStatus'])
        ->name('admin.catalog.toggle');
});

/*
|--------------------------------------------------------------------------
| Notificaciones (interno)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/me/notifications',           [NotificationController::class, 'index'])->name('me.notifications.index');
    Route::post('/me/notifications/read-all', [NotificationController::class, 'readAll'])->name('me.notifications.readAll');
});

/*
|--------------------------------------------------------------------------
| Media público (storage/app/public)
|--------------------------------------------------------------------------
*/
Route::get('/media/{path}', [MediaController::class, 'show'])
    ->where('path', '.*')->name('media.show');

/*
|--------------------------------------------------------------------------
| Diagnóstico HTTP (opcional)
|--------------------------------------------------------------------------
*/
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

/*
|--------------------------------------------------------------------------
| Carrito
|--------------------------------------------------------------------------
*/
Route::prefix('carrito')->name('web.cart.')->group(function () {
    Route::get('/',            [CartController::class, 'index'])->name('index');
    Route::post('/agregar',    [CartController::class, 'add'])->name('add');

    // Si alguien entra por GET a /carrito/agregar, redirige al carrito (evita 419)
    Route::get('/agregar', function () {
        return redirect()->route('web.cart.index');
    })->name('add.get');

    Route::post('/actualizar', [CartController::class, 'update'])->name('update');
    Route::post('/quitar',     [CartController::class, 'remove'])->name('remove');
    Route::post('/vaciar',     [CartController::class, 'clear'])->name('clear');

    // (Opcional) preview de checkout
    Route::get('/checkout',    [CartController::class, 'checkoutPreview'])->name('checkout');
});

/*
|--------------------------------------------------------------------------
| Checkout
|--------------------------------------------------------------------------
| Alineado con la vista start.blade.php y tu CheckoutController
*/
Route::middleware('auth')->group(function () {
    // Paso 1
    Route::get('/checkout/start', [CheckoutController::class, 'start'])->name('checkout.start');

    // CP lookup (el front llama a route('checkout.cp'))
    Route::get('/checkout/cp', [CheckoutController::class, 'cpLookup'])
        ->middleware('throttle:20,1')
        ->name('checkout.cp');

    // Guardar dirección (AJAX)
    Route::post('/checkout/address', [CheckoutController::class, 'addressStore'])
        ->name('checkout.address.store');

    // Paso 3: Envío
    Route::get('/checkout/shipping', [CheckoutController::class, 'shipping'])->name('checkout.shipping');
    Route::post('/checkout/shipping/select', [CheckoutController::class, 'shippingSelect'])->name('checkout.shipping.select');

    // Paso 4: Pago
    Route::get('/checkout/payment', [CheckoutController::class, 'payment'])->name('checkout.payment');
});

// Stripe: “comprar ahora” y “carrito”
Route::post('/checkout/item/{item}', [CheckoutController::class, 'checkoutItem'])
    ->whereNumber('item')
    ->name('checkout.item');

Route::post('/checkout/cart', [CheckoutController::class, 'checkoutCart'])->name('checkout.cart');

Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/checkout/cancel',  [CheckoutController::class, 'cancel'])->name('checkout.cancel');

// Webhook de Stripe (EXCEPTÚA DEL CSRF en App\Http\Middleware\VerifyCsrfToken)
Route::post('/stripe/webhook', [CheckoutController::class, 'webhook'])->name('stripe.webhook');

/*
|--------------------------------------------------------------------------
| Envíos (cotizador genérico, si lo usas aparte del checkout)
|--------------------------------------------------------------------------
*/
Route::post('/cart/shipping/options', [ShippingController::class, 'options'])->name('cart.shipping.options');
Route::post('/cart/shipping/select',  [ShippingController::class, 'select'])->name('cart.shipping.select');
// Paso 2: Facturación (modal en 2 pasos)
Route::get('/checkout/invoice',            [CheckoutController::class, 'invoice'])->name('checkout.invoice');              // página/listado
Route::post('/checkout/invoice/validate',  [CheckoutController::class, 'invoiceValidateRFC'])->name('checkout.invoice.validate'); // AJAX: valida RFC
Route::post('/checkout/invoice/store',     [CheckoutController::class, 'invoiceStore'])->name('checkout.invoice.store');  // AJAX: guarda perfil
Route::post('/checkout/invoice/select',    [CheckoutController::class, 'invoiceSelect'])->name('checkout.invoice.select'); // usar perfil existente
Route::delete('/checkout/invoice/delete',  [CheckoutController::class, 'invoiceDelete'])->name('checkout.invoice.delete'); // borrar perfil
Route::post('/checkout/invoice/skip',      [CheckoutController::class, 'invoiceSkip'])->name('checkout.invoice.skip');     // omitir factura (solo sesión)

    // CP lookup del modal de dirección (ya lo tienes, asegúrate del name):
    Route::get('/checkout/cp-lookup', [CheckoutController::class, 'cpLookup'])->name('checkout.cp.lookup');

// Página de resultados de búsqueda
Route::get('/buscar', [SearchController::class, 'index'])->name('search.index');

// Endpoint de sugerencias (AJAX)
Route::get('/buscar/suggest', [SearchController::class, 'suggest'])->name('search.suggest');

Route::get('/sobre-nosotros', [HomeController::class, 'about'])->name('about');

// ===== Comentarios (solo usuarios logeados pueden publicar/responder) =====
// ===== Comentarios (solo usuarios logeados pueden publicar/responder) =====
Route::prefix('comentarios')->name('comments.')->group(function () {
    Route::get('/', [CommentController::class, 'index'])->name('index'); // lista
    Route::post('/', [CommentController::class, 'store'])->middleware('auth')->name('store'); // nuevo
    Route::post('/{comment}/reply', [CommentController::class, 'reply'])->middleware('auth')->name('reply'); // responder
});

Route::get('/garantias-y-devoluciones', function () {
    // probar varias rutas de vista típicas
    $candidatas = [
        'web.politicas.garantias',
        'web.garantias',
        'garantias',
        'web.politicas.garantias_y_devoluciones',
        'web.politicas.garantias-devoluciones',
    ];

    return view()->first($candidatas);
})->name('policy.returns');

Route::view('/terminos-y-condiciones', 'web.politicas.terminos')->name('policy.terms');
Route::view('/aviso-de-privacidad',  'web.politicas.privacidad')->name('policy.privacy');
Route::view('/envios-devoluciones-cancelaciones', 'web.politicas.envios')->name('policy.shipping');
Route::view('/formas-de-pago', 'web.politicas.pagos')->name('policy.payments');
// routes/web.php
Route::view('/preguntas-frecuentes', 'web.politicas.faq')->name('policy.faq');
Route::view('/formas-de-envio', 'web.politicas.envios-skydropx')->name('policy.shipping.methods');

Route::get('/ofertas', fn() => view('web.ofertas'))->name('web.ofertas');

Route::middleware('auth')->group(function () {
    Route::get('/favoritos', [FavoriteController::class, 'index'])->name('favoritos.index');
    Route::post('/favoritos/toggle/{item}', [FavoriteController::class, 'toggle'])->name('favoritos.toggle');
    Route::delete('/favoritos/{item}', [FavoriteController::class, 'destroy'])->name('favoritos.destroy');
});


Route::middleware('auth')->group(function () {
    Route::get('/mi-cuenta', [CustomerAreaController::class, 'profile'])
        ->name('customer.profile');

    Route::post('/mi-cuenta/reordenar/{order}', [CustomerAreaController::class, 'reorder'])
        ->name('customer.orders.reorder');
});

Route::middleware('auth')->group(function () {
  Route::get('/ayuda', [HelpCenterController::class,'create'])->name('help.create');

  // Crear ticket (AJAX). Throttle evita spam.
  Route::post('/ayuda/start', [HelpCenterController::class,'start'])
       ->middleware('throttle:12,1')
       ->name('help.start');

  Route::get('/ayuda/t/{ticket}', [HelpCenterController::class,'show'])
       ->name('help.show');

  // Enviar mensaje al hilo (AJAX). Límite más alto para conversación fluida.
  Route::post('/ayuda/t/{ticket}/message', [HelpCenterController::class,'message'])
       ->middleware('throttle:30,1')
       ->name('help.message');

  // Escalar a humano (AJAX). Límite más bajo.
  Route::post('/ayuda/t/{ticket}/escalar', [HelpCenterController::class,'escalar'])
       ->middleware('throttle:6,1')
       ->name('help.escalar');
});

Route::prefix('panel/ayuda')->name('admin.help.')
  ->middleware(['auth','role:admin|soporte|profesor'])
  ->group(function(){
    Route::get('/', [HelpDeskAdminController::class,'index'])->name('index');
    Route::get('/{ticket}', [HelpDeskAdminController::class,'show'])->name('show');
    Route::post('/{ticket}/responder', [HelpDeskAdminController::class,'reply'])->name('reply');
    Route::post('/{ticket}/cerrar', [HelpDeskAdminController::class,'close'])->name('close');

    // 🔄 Reindexar el índice RAG (usa tu comando knowledge:sync)
    Route::post('/sync-knowledge', function () {
      Artisan::call('knowledge:sync', ['--rebuild' => true]);
      return back()->with('ok', 'Conocimiento reindexado.');
    })->name('sync');
});
