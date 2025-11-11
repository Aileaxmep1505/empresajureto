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
use App\Http\Controllers\SearchController; 
use App\Http\Controllers\Web\CommentController;
use App\Http\Controllers\Web\FavoriteController;
use App\Http\Controllers\Customer\CustomerAreaController;
use App\Http\Controllers\Web\HelpCenterController;
use App\Http\Controllers\Admin\HelpDeskAdminController;
use App\Http\Controllers\SkydropxDebugController;
use App\Http\Controllers\Web\ServicioController;
use App\Http\Controllers\Checkout\InvoiceDownloadController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Logistics\RoutePlanController;


use App\Http\Controllers\Tickets\TicketController;
use App\Http\Controllers\Tickets\TicketStageController;
use App\Http\Controllers\Tickets\TicketCommentController;
use App\Http\Controllers\Tickets\TicketDocumentController;
use App\Http\Controllers\Tickets\TicketChecklistController;
use App\Http\Controllers\Mail\MailboxController;
use App\Http\Controllers\AgendaEventController;

use App\Http\Controllers\MeliController;
/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login'])->name('login.post');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register'])->name('register.post');

    Route::get('/forgot-password',        [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password',       [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password',        [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/* Verificación por código (OTP) */
Route::middleware('auth')->group(function () {
    Route::get('/email/verify-code',   [AuthController::class, 'verifyNotice'])->name('verification.code.show');
    Route::post('/email/verify-code',  [AuthController::class, 'verifyCode'])->middleware('throttle:10,1')->name('verification.code.verify');
    Route::post('/email/resend-code',  [AuthController::class, 'resendVerificationCode'])->middleware('throttle:3,1')->name('verification.code.resend');
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

/* Catálogo público */
Route::prefix('catalogo')->name('web.catalog.')->group(function () {
    Route::get('/',                   [CatalogController::class, 'index'])->name('index');
    Route::get('/{catalogItem:slug}', [CatalogController::class, 'show'])->name('show');
});

/* Búsqueda */
Route::get('/buscar',           [SearchController::class, 'index'])->name('search.index');
Route::get('/buscar/suggest',   [SearchController::class, 'suggest'])->name('search.suggest');

/* Páginas estáticas */
Route::get('/sobre-nosotros', [HomeController::class, 'about'])->name('about');
Route::view('/terminos-y-condiciones', 'web.politicas.terminos')->name('policy.terms');
Route::view('/aviso-de-privacidad',    'web.politicas.privacidad')->name('policy.privacy');
Route::view('/envios-devoluciones-cancelaciones', 'web.politicas.envios')->name('policy.shipping');
Route::view('/formas-de-pago', 'web.politicas.pagos')->name('policy.payments');
Route::view('/preguntas-frecuentes', 'web.politicas.faq')->name('policy.faq');
Route::view('/formas-de-envio', 'web.politicas.envios-skydropx')->name('policy.shipping.methods');
Route::get('/garantias-y-devoluciones', function () {
    return view()->first([
        'web.politicas.garantias',
        'web.garantias',
        'garantias',
        'web.politicas.garantias_y_devoluciones',
        'web.politicas.garantias-devoluciones',
    ]);
})->name('policy.returns');
Route::get('/servicios', [ServicioController::class, 'index'])->name('web.servicios');
Route::get('/ofertas', fn() => view('web.ofertas'))->name('web.ofertas');

/* Media público (storage/app/public) */
Route::get('/media/{path}', [MediaController::class, 'show'])->where('path', '.*')->name('media.show');

/*
|--------------------------------------------------------------------------
| CARRITO
|--------------------------------------------------------------------------
*/
Route::prefix('carrito')->name('web.cart.')->group(function () {
    Route::get('/',            [CartController::class, 'index'])->name('index');
    Route::post('/agregar',    [CartController::class, 'add'])->name('add');
    Route::get('/agregar',     fn() => redirect()->route('web.cart.index'))->name('add.get'); // evita 419
    Route::post('/actualizar', [CartController::class, 'update'])->name('update');
    Route::post('/quitar',     [CartController::class, 'remove'])->name('remove');
    Route::post('/vaciar',     [CartController::class, 'clear'])->name('clear');
    Route::get('/checkout',    [CartController::class, 'checkoutPreview'])->name('checkout');
});

/*
|--------------------------------------------------------------------------
| CHECKOUT (auth)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    /* Paso 1 */
    Route::get('/checkout/start', [CheckoutController::class, 'start'])->name('checkout.start');

    /* CP lookup (AJAX) + alias */
    Route::get('/checkout/cp',         [CheckoutController::class, 'cpLookup'])->middleware('throttle:20,1')->name('checkout.cp');
    Route::get('/checkout/cp-lookup',  [CheckoutController::class, 'cpLookup'])->middleware('throttle:20,1')->name('checkout.cp.lookup');

    /* Dirección (AJAX) */
    Route::post('/checkout/address',         [CheckoutController::class, 'addressStore'])->name('checkout.address.store');
    Route::post('/checkout/address/select',  [CheckoutController::class, 'addressSelect'])->name('checkout.address.select');

    /* Paso 2: Facturación (modal 2 pasos) */
    Route::get('/checkout/invoice',            [CheckoutController::class, 'invoice'])->name('checkout.invoice');
    Route::post('/checkout/invoice/validate',  [CheckoutController::class, 'invoiceValidateRFC'])->name('checkout.invoice.validate');
    Route::post('/checkout/invoice/store',     [CheckoutController::class, 'invoiceStore'])->name('checkout.invoice.store');
    Route::post('/checkout/invoice/select',    [CheckoutController::class, 'invoiceSelect'])->name('checkout.invoice.select');
    Route::delete('/checkout/invoice/delete',  [CheckoutController::class, 'invoiceDelete'])->name('checkout.invoice.delete');
    Route::post('/checkout/invoice/skip',      [CheckoutController::class, 'invoiceSkip'])->name('checkout.invoice.skip');

    /* Paso 3: Envío */
    Route::get('/checkout/shipping',            [CheckoutController::class, 'shipping'])->name('checkout.shipping');
    Route::post('/checkout/shipping/select',    [CheckoutController::class, 'shippingSelect'])->name('checkout.shipping.select');

    /* Paso 4: Pago (UI) */
    Route::get('/checkout/payment', [CheckoutController::class, 'payment'])->name('checkout.payment');
});

/* Stripe: crear sesiones (buy now / cart) */
Route::post('/checkout/item/{item}', [CheckoutController::class, 'checkoutItem'])
    ->whereNumber('item')->name('checkout.item');
Route::post('/checkout/cart',        [CheckoutController::class, 'checkoutCart'])->name('checkout.cart');

/* Resultados de Stripe */
Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/checkout/cancel',  [CheckoutController::class, 'cancel'])->name('checkout.cancel');

/* Descargas/reenvío de CFDI desde la página de éxito */
Route::get('/checkout/invoices/{id}/pdf',    [CheckoutController::class, 'invoicePdf'])->name('checkout.invoice.pdf');
Route::get('/checkout/invoices/{id}/xml',    [CheckoutController::class, 'invoiceXml'])->name('checkout.invoice.xml');
Route::post('/checkout/invoices/{id}/email', [CheckoutController::class, 'invoiceResendEmail'])->name('checkout.invoice.email');

/*
|--------------------------------------------------------------------------
| ENVÍOS (cotizador externo al checkout)
|--------------------------------------------------------------------------
*/
/* Alias estilo carrito (si los usa tu front) */
Route::post('/cart/shipping/options', [ShippingController::class, 'options'])->name('cart.shipping.options');
Route::post('/cart/shipping/select',  [ShippingController::class, 'select'])->name('cart.shipping.select');

/* Canonical */
Route::middleware(['web','auth'])->group(function () {
    Route::post('/shipping/options', [ShippingController::class, 'options'])->name('shipping.options');
    Route::post('/shipping/select',  [ShippingController::class, 'select'])->name('shipping.select');
});

/*
|--------------------------------------------------------------------------
| ÁREA DE CLIENTE (auth)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/customer/welcome', function () {
        $customer = Auth::user();
        $cart     = session('cart', []);
        return view('web.customer.welcome', compact('customer', 'cart'));
    })->name('customer.welcome');

    Route::get('/mi-cuenta',                         [CustomerAreaController::class, 'profile'])->name('customer.profile');
    Route::post('/mi-cuenta/reordenar/{order}',      [CustomerAreaController::class, 'reorder'])->name('customer.orders.reorder');

    /* Favoritos */
    Route::get('/favoritos',                   [FavoriteController::class, 'index'])->name('favoritos.index');
    Route::post('/favoritos/toggle/{item}',    [FavoriteController::class, 'toggle'])->name('favoritos.toggle');
    Route::delete('/favoritos/{item}',         [FavoriteController::class, 'destroy'])->name('favoritos.destroy');

    /* Comentarios */
    Route::prefix('comentarios')->name('comments.')->group(function () {
        Route::get('/',                    [CommentController::class, 'index'])->name('index');
        Route::post('/',                   [CommentController::class, 'store'])->name('store');
        Route::post('/{comment}/reply',    [CommentController::class, 'reply'])->name('reply');
    });

    /* Notificaciones */
    Route::get('/me/notifications',           [NotificationController::class, 'index'])->name('me.notifications.index');
    Route::post('/me/notifications/read-all', [NotificationController::class, 'readAll'])->name('me.notifications.readAll');
});

/*
|--------------------------------------------------------------------------
| HELP CENTER (usuario) + ADMIN HELP DESK
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/ayuda',                       [HelpCenterController::class,'create'])->name('help.create');
    Route::post('/ayuda/start',                [HelpCenterController::class,'start'])->middleware('throttle:12,1')->name('help.start');
    Route::get('/ayuda/t/{ticket}',            [HelpCenterController::class,'show'])->name('help.show');
    Route::post('/ayuda/t/{ticket}/message',   [HelpCenterController::class,'message'])->middleware('throttle:30,1')->name('help.message');
    Route::post('/ayuda/t/{ticket}/escalar',   [HelpCenterController::class,'escalar'])->middleware('throttle:6,1')->name('help.escalar');
});

Route::prefix('panel/ayuda')->name('admin.help.')
    ->middleware(['auth','role:admin|soporte|profesor'])
    ->group(function(){
        Route::get('/',                   [HelpDeskAdminController::class,'index'])->name('index');
        Route::get('/{ticket}',           [HelpDeskAdminController::class,'show'])->name('show');
        Route::post('/{ticket}/responder',[HelpDeskAdminController::class,'reply'])->name('reply');
        Route::post('/{ticket}/cerrar',   [HelpDeskAdminController::class,'close'])->name('close');
        Route::post('/sync-knowledge',    function () {
            Artisan::call('knowledge:sync', ['--rebuild' => true]);
            return back()->with('ok', 'Conocimiento reindexado.');
        })->name('sync');
    });

/*
|--------------------------------------------------------------------------
| PANEL INTERNO (auth + approved)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'approved'])->prefix('panel')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /* Ventas internas (solo lectura + PDF/email) */
    Route::resource('ventas', VentaController::class)->only(['index','show'])->names('ventas');
    Route::get('ventas/{venta}/pdf',    [VentaController::class, 'pdf'])->name('ventas.pdf');
    Route::post('ventas/{venta}/email', [VentaController::class, 'enviarPorCorreo'])->name('ventas.email');

    /* Productos internos */
    Route::get('products/import',     [ProductController::class, 'importForm'])->name('products.import.form');
    Route::post('products/import',    [ProductController::class, 'importStore'])->name('products.import.store');
    Route::get('products/export/pdf', [ProductController::class, 'exportPdf'])->name('products.export.pdf');
    Route::resource('products', ProductController::class)
        ->parameters(['products' => 'product'])
        ->whereNumber('product');

    /* Proveedores / Clientes */
    Route::resource('providers', ProviderController::class)->names('providers');
    Route::resource('clients',   ClientController::class)->names('clients');

    /* Cotizaciones + AI */
    Route::resource('cotizaciones', CotizacionController::class);
    Route::post('cotizaciones/{cotizacion}/aprobar',         [CotizacionController::class,'aprobar'])->name('cotizaciones.aprobar');
    Route::post('cotizaciones/{cotizacion}/rechazar',        [CotizacionController::class,'rechazar'])->name('cotizaciones.rechazar');
    Route::get('cotizaciones/{cotizacion}/pdf',              [CotizacionController::class,'pdf'])->name('cotizaciones.pdf');
    Route::post('cotizaciones/{cotizacion}/convertir-venta', [CotizacionController::class,'convertirAVenta'])->name('cotizaciones.convertir');
    Route::post('cotizaciones/ai-parse',      [CotizacionController::class, 'aiParse'])->name('cotizaciones.ai_parse');
    Route::post('cotizaciones/ai-store',      [CotizacionController::class, 'aiCreate'])->name('cotizaciones.ai_store');
    Route::post('cotizaciones/store-from-ai', [CotizacionController::class, 'aiCreate'])->name('cotizaciones.store_from_ai');
    Route::get('cotizaciones/auto',           [CotizacionController::class, 'autoForm'])->name('cotizaciones.auto.form');
    Route::post('cotizaciones/auto',          [CotizacionController::class, 'autoCreate'])->name('cotizaciones.auto.create');

    /* Perfil interno */
    Route::get('/perfil',           [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/perfil/foto',      [ProfileController::class, 'updatePhoto'])->name('profile.update.photo');
    Route::put('/perfil/password',  [ProfileController::class, 'updatePassword'])->name('profile.update.password');

    /* Landing del panel */
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

    Route::resource('catalog', CatalogItemController::class)
        ->parameters(['catalog' => 'catalogItem'])
        ->names('admin.catalog');
    Route::patch('catalog/{catalogItem}/toggle', [CatalogItemController::class, 'toggleStatus'])->name('admin.catalog.toggle');
});

/*
|--------------------------------------------------------------------------
| DIAGNÓSTICO / DEBUG
|--------------------------------------------------------------------------
*/
Route::get('/diag/http', function () {
    $out = [];
    try {
        $r1 = Http::timeout(15)->head('https://api.ocr.space/parse/image');
        $out['ocr_head'] = ['ok' => $r1->successful() || $r1->clientError() || $r1->serverError(), 'status' => $r1->status()];
    } catch (\Throwable $e) { $out['ocr_head'] = ['ok' => false, 'error' => $e->getMessage()]; }
    try {
        $r2 = Http::timeout(15)->withHeaders(['Authorization' => 'Bearer x'])->get('https://api.openai.com/v1/models');
        $out['openai_models'] = ['ok' => $r2->status() !== 0, 'status' => $r2->status()];
    } catch (\Throwable $e) { $out['openai_models'] = ['ok' => false, 'error' => $e->getMessage()]; }
    try {
        $path = storage_path('logs/http_diag.log');
        file_put_contents($path, '['.date('c')."] diag ok\n", FILE_APPEND);
        $out['write_logs'] = ['ok' => true, 'path' => $path];
    } catch (\Throwable $e) { $out['write_logs'] = ['ok' => false, 'error' => $e->getMessage()]; }
    return response()->json($out);
});

Route::get('/debug/skydropx/carriers', [SkydropxDebugController::class, 'carriers']);
Route::get('/debug/skydropx/quote',    [SkydropxDebugController::class, 'quote']);

/*
|--------------------------------------------------------------------------
| WEBHOOKS
|--------------------------------------------------------------------------
*/
Route::post('/webhooks/stripe', [StripeWebhookController::class,'handle'])->name('webhooks.stripe');

Route::get('/categoria/{category:slug}', [CategoryController::class, 'show'])
     ->name('web.categorias.show');

     Route::middleware(['auth'])->prefix('mi-cuenta')->name('customer.')->group(function () {
    Route::get('/', [CustomerAreaController::class, 'profile'])->name('profile');
    Route::post('/pedidos/{order}/repetir', [CustomerAreaController::class, 'reorder'])->name('orders.reorder');
    Route::get('/pedidos/{order}/rastreo', [CustomerAreaController::class, 'tracking'])->name('orders.tracking');

    // Opcional: solo si agregas shipping_label_url a orders
    Route::get('/pedidos/{order}/guia',    [CustomerAreaController::class, 'label'])->name('orders.label');
});
Route::get('/admin/rutas/demo', [RouteController::class, 'demo'])->name('routing.demo');


Route::middleware(['auth'])->group(function () {
    // Supervisor / Logística
    Route::get('/logi/routes', [RoutePlanController::class, 'index'])->name('routes.index');
    Route::get('/logi/routes/create', [RoutePlanController::class, 'create'])->name('routes.create');
    Route::post('/logi/routes', [RoutePlanController::class, 'store'])->name('routes.store');
    Route::get('/logi/routes/{routePlan}', [RoutePlanController::class, 'show'])->name('routes.show');

    // Vista del chofer (mis rutas)
    Route::get('/driver/routes/{routePlan}', [RoutePlanController::class, 'driver'])->name('driver.routes.show');

    // API internas (protegidas por auth)
    Route::post('/api/routes/{routePlan}/compute', [RoutePlanController::class, 'compute'])->name('api.routes.compute');
    Route::post('/api/routes/{routePlan}/recompute', [RoutePlanController::class, 'recompute'])->name('api.routes.recompute');
    Route::post('/api/routes/{routePlan}/stops/{stop}/done', [RoutePlanController::class, 'markStopDone'])->name('api.routes.stop.done');
    Route::post('/api/driver/location', [RoutePlanController::class, 'saveDriverLocation'])
        ->name('api.driver.location.save');

    // (opcional) para leer última ubicación
    Route::get('/api/driver/location', [RoutePlanController::class, 'getDriverLocation'])
        ->name('api.driver.location.get');
        Route::post('/api/driver/location/save', [RoutePlanController::class,'saveDriverLocation'])->name('api.driver.location.save');
Route::get ('/api/driver/location/last', [RoutePlanController::class,'getDriverLocation'])->name('api.driver.location.last');


});
Route::get('cotizaciones/buscar-productos', [CotizacionController::class, 'buscarProductos'])->name('cotizaciones.buscar_productos');


Route::middleware(['auth'])->group(function () {

    // ===== Tickets CRUD básico =====
    Route::get('/tickets',                       [TicketController::class,'index'])->name('tickets.index');
    Route::get('/tickets/create',                [TicketController::class,'create'])->name('tickets.create');
    Route::post('/tickets',                      [TicketController::class,'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}',              [TicketController::class,'show'])->name('tickets.show')->whereNumber('ticket');
    Route::put('/tickets/{ticket}',              [TicketController::class,'update'])->name('tickets.update')->whereNumber('ticket');
    Route::post('/tickets/{ticket}/close',       [TicketController::class,'close'])->name('tickets.close')->whereNumber('ticket');

    // ===== Dashboard (opcional) =====
    Route::get('/tickets-dashboard',             [DashboardController::class,'index'])->name('tickets.dashboard');

    // ===== Tiempo real por AJAX (poll + acciones de etapa) =====
    Route::get ('/tickets/{ticket}/poll',                      [TicketController::class,'poll'])->name('tickets.poll')->whereNumber('ticket');
    Route::post('/tickets/{ticket}/stages/{stage}/start',      [TicketController::class,'ajaxStartStage'])->name('tickets.ajax.stage.start')->whereNumber('ticket')->whereNumber('stage');
    Route::post('/tickets/{ticket}/stages/{stage}/complete',   [TicketController::class,'ajaxCompleteStage'])->name('tickets.ajax.stage.complete')->whereNumber('ticket')->whereNumber('stage');
    Route::post('/tickets/{ticket}/stages/{stage}/evidence',   [TicketController::class,'ajaxUploadEvidence'])->name('tickets.ajax.stage.evidence')->whereNumber('ticket')->whereNumber('stage');

    // ===== Etapas =====
    Route::post  ('/tickets/{ticket}/stages',                  [TicketStageController::class,'store'])->name('tickets.stages.store')->whereNumber('ticket');
    Route::put   ('/tickets/{ticket}/stages/{stage}',          [TicketStageController::class,'update'])->name('tickets.stages.update')->whereNumber('ticket')->whereNumber('stage');
    Route::delete('/tickets/{ticket}/stages/{stage}',          [TicketController::class,'destroyStage'])->name('tickets.stages.destroy')->whereNumber('ticket')->whereNumber('stage');

    // ===== Comentarios =====
    Route::post('/tickets/{ticket}/comments',                  [TicketCommentController::class,'store'])->name('tickets.comments.store')->whereNumber('ticket');

    // ===== Documentos =====
    Route::post  ('/tickets/{ticket}/documents',               [TicketDocumentController::class,'store'])->name('tickets.documents.store')->whereNumber('ticket');
    Route::get   ('/tickets/{ticket}/documents/{doc}/download',[TicketDocumentController::class,'download'])->name('tickets.documents.download')->whereNumber('ticket')->whereNumber('doc');
    Route::delete('/tickets/{ticket}/documents/{doc}',         [TicketController::class,'destroyDocument'])->name('tickets.documents.destroy')->whereNumber('ticket')->whereNumber('doc');

    // ===== Checklists (CRUD) =====
    Route::post  ('/tickets/{ticket}/checklists',              [TicketChecklistController::class,'store'])->name('tickets.checklists.store')->whereNumber('ticket');
    Route::put   ('/checklists/{checklist}',                   [TicketChecklistController::class,'update'])->name('checklists.update')->whereNumber('checklist');
    Route::delete('/checklists/{checklist}',                   [TicketChecklistController::class,'destroy'])->name('checklists.destroy')->whereNumber('checklist');

    // Ítems de checklist
    Route::post  ('/checklists/{checklist}/items',             [TicketChecklistController::class,'addItem'])->name('checklists.items.add')->whereNumber('checklist');
    Route::put   ('/checklist-items/{item}',                   [TicketChecklistController::class,'updateItem'])->name('checklists.items.update')->whereNumber('item');
    Route::delete('/checklist-items/{item}',                   [TicketChecklistController::class,'destroyItem'])->name('checklists.items.destroy')->whereNumber('item');
    Route::post  ('/checklists/{checklist}/items/reorder',     [TicketChecklistController::class,'reorderItems'])->name('checklists.items.reorder')->whereNumber('checklist');
    Route::post  ('/checklists/{checklist}/toggle-all',        [TicketChecklistController::class,'toggleAll'])->name('checklists.items.toggleAll')->whereNumber('checklist');

    // ===== Exports =====
    Route::get('/checklists/{checklist}/export/pdf',           [TicketChecklistController::class,'exportPdf'])->name('checklists.export.pdf')->whereNumber('checklist');
    Route::get('/checklists/{checklist}/export/word',          [TicketChecklistController::class,'exportWord'])->name('checklists.export.word')->whereNumber('checklist');

    // ===== IA (100% OpenAI) =====
    // Sugerir (devuelve JSON: title, instructions, items[])
    Route::post('/tickets/{ticket}/stages/{stage}/ai/suggest', [TicketChecklistController::class,'suggestFromPrompt'])->name('tickets.ai.suggest')->whereNumber('ticket')->whereNumber('stage');
    // Crear checklist desde IA
    Route::post('/tickets/{ticket}/checklists/ai',             [TicketChecklistController::class,'createFromAi'])->name('tickets.ai.create')->whereNumber('ticket');
});
// ====== MAILBOX (todas bajo /mail) ======
Route::middleware(['auth']) // quítalo si aún no usas auth
    ->prefix('mail')
    ->name('mail.')
    ->group(function () {

        // Lista (usa query ?folder=...&uid=... para el preview en el panel derecho)
        Route::get('/', [MailboxController::class, 'index'])->name('index');

        // Listar una carpeta específica (soporta nombres con "/" o ".")
        Route::get('/folder/{folder}', [MailboxController::class, 'folder'])
            ->where('folder', '.*')
            ->name('folder');

        // Ver un mensaje en página independiente (opcional si usas el panel derecho)
        Route::get('/show/{folder}/{uid}', [MailboxController::class, 'show'])
            ->where('folder', '.*')
            ->whereNumber('uid')
            ->name('show');

        // Descargar adjunto por "part"
        Route::get('/download/{folder}/{uid}/{part}', [MailboxController::class, 'downloadAttachment'])
            ->where('folder', '.*')
            ->whereNumber('uid')
            ->name('download');

        // Redactar / Enviar
        Route::get('/compose', [MailboxController::class, 'compose'])->name('compose');
        Route::post('/send',   [MailboxController::class, 'send'])->name('send');

        // Responder / Reenviar
        Route::post('/reply/{folder}/{uid}', [MailboxController::class, 'reply'])
            ->where('folder', '.*')
            ->whereNumber('uid')
            ->name('reply');

        Route::post('/forward/{folder}/{uid}', [MailboxController::class, 'forward'])
            ->where('folder', '.*')
            ->whereNumber('uid')
            ->name('forward');

        // Acciones: importante / leído
        Route::post('/toggle-flag/{folder}/{uid}', [MailboxController::class, 'toggleFlag'])
            ->where('folder', '.*')
            ->whereNumber('uid')
            ->name('toggleFlag');   // <-- este nombre es el que usa la vista

        Route::post('/mark-read/{folder}/{uid}', [MailboxController::class, 'markRead'])
            ->where('folder', '.*')
            ->whereNumber('uid')
            ->name('markRead');     // <-- este nombre es el que usa la vista
            // === API en tiempo real ===
// Lista de mensajes (soporta ?folder=INBOX&limit=80&after_uid=12345&q=texto)
Route::get('/api/messages', [MailboxController::class, 'apiMessages'])
    ->name('api.messages');

// Contadores por carpeta (INBOX, PRIORITY, SENT, etc.)
Route::get('/api/counts', [MailboxController::class, 'apiCounts'])
    ->name('api.counts');
// === Acciones rápidas por mensaje ===
Route::post('/move/{folder}/{uid}',   [MailboxController::class, 'move'])->where('folder','.*')->whereNumber('uid')->name('move');       // mover a ARCHIVE, SPAM, TRASH...
Route::post('/delete/{folder}/{uid}', [MailboxController::class, 'delete'])->where('folder','.*')->whereNumber('uid')->name('delete');   // eliminar (mover a TRASH o \Deleted)

// === Long-polling en tiempo (casi) real ===
// Espera hasta 25s a que lleguen nuevos correos (o devuelve antes si hay nuevos)
Route::get('/api/wait', [MailboxController::class, 'apiMessagesWait'])->name('api.wait');
// JSON rápido (lista)
Route::get('/mail/api/messages', [MailboxController::class, 'apiMessages'])->name('mail.api.messages');
// Long-polling "push-like"
Route::get('/mail/api/wait',     [MailboxController::class, 'apiWait'])->name('mail.api.wait');
// Contadores
Route::get('/mail/api/counts',   [MailboxController::class, 'apiCounts'])->name('mail.api.counts');

// Mover (archivar, etc.)
Route::post('/mail/move/{folder}/{uid}',   [MailboxController::class, 'move'])
    ->where('folder','.*')->whereNumber('uid')->name('mail.move');

// Eliminar (mueve a TRASH o purga si ya estás en TRASH)
Route::post('/mail/delete/{folder}/{uid}', [MailboxController::class, 'delete'])
    ->where('folder','.*')->whereNumber('uid')->name('mail.delete');
Route::post('/mail/send', [MailController::class, 'send'])->name('mail.send');
    });
Route::middleware(['auth'])->group(function () {
    // Vista calendario
    Route::get('/agenda', [AgendaEventController::class,'calendar'])->name('agenda.calendar');

    // Feed JSON
    Route::get('/agenda/feed', [AgendaEventController::class,'feed'])->name('agenda.feed');

    // CRUD AJAX
    Route::post('/agenda', [AgendaEventController::class,'store'])->name('agenda.store');
    Route::get('/agenda/{agenda}', [AgendaEventController::class,'show'])->name('agenda.show');
    Route::put('/agenda/{agenda}', [AgendaEventController::class,'update'])->name('agenda.update');
    Route::delete('/agenda/{agenda}', [AgendaEventController::class,'destroy'])->name('agenda.destroy');

    // Drag/resize
    Route::put('/agenda/{agenda}/move', [AgendaEventController::class,'move'])->name('agenda.move');
});


Route::get('/meli/callback', [MeliController::class, 'callback'])->name('meli.callback');        // https://ai.jureto.com.mx/meli/callback
Route::post('/meli/notifications', [MeliController::class, 'notifications'])->name('meli.notifications'); // https://ai.jureto.com.mx/meli/notifications