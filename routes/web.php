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
use App\Http\Controllers\PartContableController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\LicitacionWizardController;
use App\Http\Controllers\LicitacionPreguntaController;
use App\Http\Controllers\LicitacionChecklistController;
use App\Http\Controllers\LicitacionExportController;
use App\Http\Controllers\LicitacionFileController;
use App\Http\Controllers\ManualInvoiceController;
use App\Http\Controllers\Mobile\CatalogAiIntakePublicController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\CompanyController;

// 🔹 NUEVOS CONTROLADORES ADMIN LICITACIONES (PDF + PROPUESTAS)
use App\Http\Controllers\Admin\LicitacionPdfController;
use App\Http\Controllers\Admin\LicitacionPropuestaController;

// 🔹 MODELOS
use App\Models\LicitacionPdf;

use App\Http\Controllers\DebugOpenAiController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\LicitacionPdfAiController;
use App\Http\Controllers\Admin\WmsController;
use App\Http\Controllers\Admin\WmsSearchController;
use App\Http\Controllers\Admin\WmsPickingController;
use App\Http\Controllers\Admin\WmsMoveController;
use App\Http\Controllers\Tickets\TicketWorkController;
use App\Http\Controllers\PublicationController;
use App\Http\Controllers\Logistics\RouteSupervisorController;


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
Route::get('/buscar',         [SearchController::class, 'index'])->name('search.index');
Route::get('/buscar/suggest', [SearchController::class, 'suggest'])->name('search.suggest');

/* Páginas estáticas */
Route::get('/sobre-nosotros', [HomeController::class, 'about'])->name('about');
Route::view('/terminos-y-condiciones',           'web.politicas.terminos')->name('policy.terms');
Route::view('/aviso-de-privacidad',              'web.politicas.privacidad')->name('policy.privacy');
Route::view('/envios-devoluciones-cancelaciones','web.politicas.envios')->name('policy.shipping');
Route::view('/formas-de-pago',                   'web.politicas.pagos')->name('policy.payments');
Route::view('/preguntas-frecuentes',             'web.politicas.faq')->name('policy.faq');
Route::view('/formas-de-envio',                  'web.politicas.envios-skydropx')->name('policy.shipping.methods');

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
    Route::get('/checkout/cp',        [CheckoutController::class, 'cpLookup'])->middleware('throttle:20,1')->name('checkout.cp');
    Route::get('/checkout/cp-lookup', [CheckoutController::class, 'cpLookup'])->middleware('throttle:20,1')->name('checkout.cp.lookup');

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
    Route::get('/checkout/shipping',          [CheckoutController::class, 'shipping'])->name('checkout.shipping');
    Route::post('/checkout/shipping/select',  [CheckoutController::class, 'shippingSelect'])->name('checkout.shipping.select');

    /* Paso 4: Pago (UI) */
    Route::get('/checkout/payment', [CheckoutController::class, 'payment'])->name('checkout.payment');
});

/* Stripe: crear sesiones (buy now / cart) */
Route::post('/checkout/item/{item}', [CheckoutController::class, 'checkoutItem'])
    ->whereNumber('item')->name('checkout.item');
Route::post('/checkout/cart', [CheckoutController::class, 'checkoutCart'])->name('checkout.cart');

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
/* Alias estilo carrito */
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

    Route::get('/mi-cuenta',                    [CustomerAreaController::class, 'profile'])->name('customer.profile');
    Route::post('/mi-cuenta/reordenar/{order}', [CustomerAreaController::class, 'reorder'])->name('customer.orders.reorder');

    /* Favoritos */
    Route::get('/favoritos',                 [FavoriteController::class, 'index'])->name('favoritos.index');
    Route::post('/favoritos/toggle/{item}',  [FavoriteController::class, 'toggle'])->name('favoritos.toggle');
    Route::delete('/favoritos/{item}',       [FavoriteController::class, 'destroy'])->name('favoritos.destroy');

    /* Comentarios */
    Route::prefix('comentarios')->name('comments.')->group(function () {
        Route::get('/',                 [CommentController::class, 'index'])->name('index');
        Route::post('/',                [CommentController::class, 'store'])->name('store');
        Route::post('/{comment}/reply', [CommentController::class, 'reply'])->name('reply');
    });
});

/*
|--------------------------------------------------------------------------
| NOTIFICACIONES (panel)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/notifications/feed', [NotificationController::class, 'index'])
        ->name('notifications.feed');

    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])
        ->name('notifications.read-all');

    Route::post('/notifications/{notification}/read', [NotificationController::class, 'readOne'])
        ->name('notifications.read-one');
});

/*
|--------------------------------------------------------------------------
| HELP CENTER (usuario) + ADMIN HELP DESK
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/ayuda',                     [HelpCenterController::class,'create'])->name('help.create');
    Route::post('/ayuda/start',              [HelpCenterController::class,'start'])->middleware('throttle:12,1')->name('help.start');
    Route::get('/ayuda/t/{ticket}',          [HelpCenterController::class,'show'])->name('help.show');
    Route::post('/ayuda/t/{ticket}/message', [HelpCenterController::class,'message'])->middleware('throttle:30,1')->name('help.message');
    Route::post('/ayuda/t/{ticket}/escalar', [HelpCenterController::class,'escalar'])->middleware('throttle:6,1')->name('help.escalar');
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
    Route::get('/perfil',          [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/perfil/foto',     [ProfileController::class, 'updatePhoto'])->name('profile.update.photo');
    Route::put('/perfil/password', [ProfileController::class, 'updatePassword'])->name('profile.update.password');

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

    Route::patch('catalog/{catalogItem}/toggle', [CatalogItemController::class, 'toggleStatus'])
        ->name('admin.catalog.toggle');

    /*
    |--------------------------------------------------------------------------
    | LICITACIONES – PDFs de requisición
    |--------------------------------------------------------------------------
    | URL base:   /admin/licitacion-pdfs
    | Nombres:    admin.licitacion-pdfs.index, admin.licitacion-pdfs.show, etc.
    */
    Route::resource('licitacion-pdfs', LicitacionPdfController::class)
        ->parameters(['licitacion-pdfs' => 'licitacionPdf'])
        ->names('admin.licitacion-pdfs');

    // Acción extra: recortar rango de páginas (usada en el blade)
    Route::post('licitacion-pdfs/{licitacionPdf}/split', [LicitacionPdfController::class, 'split'])
        ->name('admin.licitacion-pdfs.split');

    Route::get('licitacion-pdfs/{licitacionPdf}/preview', [LicitacionPdfController::class, 'preview'])
        ->name('admin.licitacion-pdfs.preview');

    Route::get('licitacion-pdfs/{licitacionPdf}/splits/{index}/{format}', [LicitacionPdfController::class, 'downloadSplit'])
        ->whereIn('format', ['pdf', 'word', 'excel'])
        ->name('admin.licitacion-pdfs.splits.download');

    // 🔹 NUEVA RUTA: desde el recorte de PDF hacia la vista "Nueva propuesta económica"
// 🔹 RUTA: desde el recorte de PDF hacia la vista "Nueva propuesta económica"
Route::get('licitacion-pdfs/{licitacionPdf}/propuesta', function (LicitacionPdf $licitacionPdf) {
    $params = [];

    if ($licitacionPdf->licitacion_id) {
        $params['licitacion_id'] = $licitacionPdf->licitacion_id;
    }

    if ($licitacionPdf->requisicion_id) {
        $params['requisicion_id'] = $licitacionPdf->requisicion_id;
    }

    // 👉 Muy importante: mandamos el PDF original que tiene los splits
    $params['licitacion_pdf_id'] = $licitacionPdf->id;

    return redirect()->route('admin.licitacion-propuestas.create', $params);
})->name('admin.licitacion-pdfs.propuesta');

    /*
    |--------------------------------------------------------------------------
    | LICITACIONES – Propuestas económicas comparativas
    |--------------------------------------------------------------------------
    | URL base:   /admin/licitacion-propuestas
    | Nombres:    admin.licitacion-propuestas.index, etc.
    */
    Route::resource('licitacion-propuestas', LicitacionPropuestaController::class)
        ->parameters(['licitacion-propuestas' => 'licitacionPropuesta'])
        ->names('admin.licitacion-propuestas');
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

/* Categorías web */
Route::get('/categoria/{category:slug}', [CategoryController::class, 'show'])
    ->name('web.categorias.show');

/*
|--------------------------------------------------------------------------
| ÁREA DE CLIENTE (prefijo /mi-cuenta)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('mi-cuenta')->name('customer.')->group(function () {
    Route::get('/', [CustomerAreaController::class, 'profile'])->name('profile');
    Route::post('/pedidos/{order}/repetir', [CustomerAreaController::class, 'reorder'])->name('orders.reorder');
    Route::get('/pedidos/{order}/rastreo', [CustomerAreaController::class, 'tracking'])->name('orders.tracking');

    // Opcional: solo si agregas shipping_label_url a orders
    Route::get('/pedidos/{order}/guia', [CustomerAreaController::class, 'label'])->name('orders.label');
});

/*
|--------------------------------------------------------------------------
| LOGÍSTICA / RUTAS
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| LOGÍSTICA / RUTAS (CHOFER + SUPERVISOR) ✅ TODO EN web.php (SIN DUPLICADOS)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    /* =========================
     |  SUPERVISOR / LOGÍSTICA (panel)
     ========================= */
    Route::get('/logi/routes',              [\App\Http\Controllers\Logistics\RoutePlanController::class, 'index'])->name('routes.index');
    Route::get('/logi/routes/create',       [\App\Http\Controllers\Logistics\RoutePlanController::class, 'create'])->name('routes.create');
    Route::post('/logi/routes',             [\App\Http\Controllers\Logistics\RoutePlanController::class, 'store'])->name('routes.store');
    Route::get('/logi/routes/{routePlan}',  [\App\Http\Controllers\Logistics\RoutePlanController::class, 'show'])->name('routes.show');

    // (opcional) demo
    Route::get('/admin/rutas/demo', [\App\Http\Controllers\Logistics\RoutePlanController::class, 'demo'])->name('routing.demo');

    /* =========================
     |  CHOFER (mis rutas)
     ========================= */
    Route::get('/driver/routes/{routePlan}', [\App\Http\Controllers\Logistics\RoutePlanController::class, 'driver'])
        ->name('driver.routes.show');

    /* =========================
     |  API internas (pero en web.php) — ESTAS SON LAS QUE USA TU BLADE
     |  OJO: no repitas nombres (name) en otro lado.
     ========================= */
    Route::post('/api/routes/{routePlan}/start', [\App\Http\Controllers\Logistics\RoutePlanController::class, 'start'])
        ->name('api.routes.start');

    Route::post('/api/routes/{routePlan}/compute', [\App\Http\Controllers\Logistics\RoutePlanController::class, 'compute'])
        ->name('api.routes.compute');

    Route::post('/api/routes/{routePlan}/recompute', [\App\Http\Controllers\Logistics\RoutePlanController::class, 'recompute'])
        ->name('api.routes.recompute');

    // ✅ Debe coincidir con tu Blade:
    // URL_DONE_BASE = /api/routes/{routePlan}/stops  y luego  /{stop}/done
    Route::post('/api/routes/{routePlan}/stops/{stop}/done', [\App\Http\Controllers\Logistics\RoutePlanController::class, 'markStopDone'])
        ->name('api.routes.stops.done');

    /* =========================
     |  GPS (ubicación chofer)
     ========================= */
    Route::post('/api/driver/location', [\App\Http\Controllers\Logistics\RoutePlanController::class, 'saveDriverLocation'])
        ->name('api.driver.location.save');

    Route::get('/api/driver/location/last', [\App\Http\Controllers\Logistics\RoutePlanController::class, 'getDriverLocation'])
        ->name('api.driver.location.last');

    // (opcional) si tienes endpoint “get”
    Route::get('/api/driver/location', [\App\Http\Controllers\Logistics\RoutePlanController::class, 'getDriverLocation'])
        ->name('api.driver.location.get');

    /* =========================
    |  SUPERVISOR REALTIME / POLL
    ========================= */
    Route::get('/api/routes/{routePlan}/live', [\App\Http\Controllers\Logistics\RoutePlanController::class, 'live'])
        ->name('api.routes.live');

    /**
     * ✅ VISTA (HTML Blade)
     * OJO: NO le pongas "api." al name porque es una vista, no un endpoint JSON.
     * Así cuando entres a /supervisor/routes/{id} ya NO verás el JSON.
     */
    Route::get('/supervisor/routes/{routePlan}', [\App\Http\Controllers\Logistics\RouteSupervisorController::class, 'show'])
        ->name('supervisor.routes.show');

    /**
     * ✅ ENDPOINT JSON (POLL)
     * Este sí es JSON para tu fetch.
     */
    Route::get('/supervisor/routes/{routePlan}/poll', [\App\Http\Controllers\Logistics\RouteSupervisorController::class, 'poll'])
        ->name('api.supervisor.routes.poll');

    /* =========================
     |  CLIENT LOG (para tu DEBUG en JS) ✅ en web.php también
     ========================= */
    Route::post('/api/client-log', function (\Illuminate\Http\Request $r) {
        $data = $r->validate([
            'scope'   => ['nullable','string','max:80'],
            'level'   => ['nullable','string','max:20'],
            'message' => ['required','string','max:1000'],
            'meta'    => ['nullable','array'],
        ]);

        $scope = $data['scope'] ?? 'client';
        $level = strtolower($data['level'] ?? 'info');
        $msg   = "[CLIENT_LOG][$scope] ".$data['message'];

        $ctx = [
            'user_id' => auth()->id(),
            'meta'    => $data['meta'] ?? [],
            'ip'      => $r->ip(),
            'ua'      => substr((string)$r->userAgent(), 0, 240),
        ];

        if ($level === 'error') \Log::error($msg, $ctx);
        elseif ($level === 'warning' || $level === 'warn') \Log::warning($msg, $ctx);
        else \Log::info($msg, $ctx);

        return response()->json(['ok'=>true]);
    })->name('api.client-log');

});


Route::get('cotizaciones/buscar-productos', [CotizacionController::class, 'buscarProductos'])->name('cotizaciones.buscar_productos');

/*
|--------------------------------------------------------------------------
| TICKETS (licitaciones) + IA checklist
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // ===== Tickets CRUD básico =====
    Route::get('/tickets',                 [TicketController::class,'index'])->name('tickets.index');
    Route::get('/tickets/create',          [TicketController::class,'create'])->name('tickets.create');
    Route::post('/tickets',                [TicketController::class,'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}',        [TicketController::class,'show'])->name('tickets.show')->whereNumber('ticket');

    // 👇 NUEVA: vista de trabajo para el usuario asignado (checklist / flujo)
    Route::get('/tickets/{ticket}/work',   [TicketController::class,'work'])
        ->name('tickets.work')
        ->whereNumber('ticket');

    Route::put('/tickets/{ticket}',        [TicketController::class,'update'])->name('tickets.update')->whereNumber('ticket');
    Route::post('/tickets/{ticket}/close', [TicketController::class,'close'])->name('tickets.close')->whereNumber('ticket');

    // ===== Dashboard =====
    Route::get('/tickets-dashboard', [DashboardController::class,'index'])->name('tickets.dashboard');

    // ===== Tiempo real por AJAX (poll + acciones de etapa) =====
    Route::get ('/tickets/{ticket}/poll',                    [TicketController::class,'poll'])->name('tickets.poll')->whereNumber('ticket');
    Route::post('/tickets/{ticket}/stages/{stage}/start',    [TicketController::class,'ajaxStartStage'])->name('tickets.ajax.stage.start')->whereNumber('ticket')->whereNumber('stage');
    Route::post('/tickets/{ticket}/stages/{stage}/complete', [TicketController::class,'ajaxCompleteStage'])->name('tickets.ajax.stage.complete')->whereNumber('ticket')->whereNumber('stage');
    Route::post('/tickets/{ticket}/stages/{stage}/evidence', [TicketController::class,'ajaxUploadEvidence'])->name('tickets.ajax.stage.evidence')->whereNumber('ticket')->whereNumber('stage');

    // ===== Etapas =====
    Route::post  ('/tickets/{ticket}/stages',         [TicketStageController::class,'store'])->name('tickets.stages.store')->whereNumber('ticket');
    Route::put   ('/tickets/{ticket}/stages/{stage}', [TicketStageController::class,'update'])->name('tickets.stages.update')->whereNumber('ticket')->whereNumber('stage');
    Route::delete('/tickets/{ticket}/stages/{stage}', [TicketController::class,'destroyStage'])->name('tickets.stages.destroy')->whereNumber('ticket')->whereNumber('stage');

    // ===== Comentarios =====
    Route::post('/tickets/{ticket}/comments', [TicketCommentController::class,'store'])->name('tickets.comments.store')->whereNumber('ticket');

    // ===== Documentos =====
    Route::post  ('/tickets/{ticket}/documents',               [TicketDocumentController::class,'store'])->name('tickets.documents.store')->whereNumber('ticket');
    Route::get   ('/tickets/{ticket}/documents/{doc}/download',[TicketDocumentController::class,'download'])->name('tickets.documents.download')->whereNumber('ticket')->whereNumber('doc');
    Route::delete('/tickets/{ticket}/documents/{doc}',         [TicketController::class,'destroyDocument'])->name('tickets.documents.destroy')->whereNumber('ticket')->whereNumber('doc');

    // ===== Checklists (CRUD) =====
    Route::post  ('/tickets/{ticket}/checklists',          [TicketChecklistController::class,'store'])->name('tickets.checklists.store')->whereNumber('ticket');
    Route::put   ('/checklists/{checklist}',               [TicketChecklistController::class,'update'])->name('checklists.update')->whereNumber('checklist');
    Route::delete('/checklists/{checklist}',               [TicketChecklistController::class,'destroy'])->name('checklists.destroy')->whereNumber('checklist');

    // Ítems de checklist
    Route::post  ('/checklists/{checklist}/items',         [TicketChecklistController::class,'addItem'])->name('checklists.items.add')->whereNumber('checklist');
    Route::put   ('/checklist-items/{item}',               [TicketChecklistController::class,'updateItem'])->name('checklists.items.update')->whereNumber('item');
    Route::delete('/checklist-items/{item}',               [TicketChecklistController::class,'destroyItem'])->name('checklists.items.destroy')->whereNumber('item');
    Route::post  ('/checklists/{checklist}/items/reorder', [TicketChecklistController::class,'reorderItems'])->name('checklists.items.reorder')->whereNumber('checklist');
    Route::post  ('/checklists/{checklist}/toggle-all',    [TicketChecklistController::class,'toggleAll'])->name('checklists.items.toggleAll')->whereNumber('checklist');

    // ===== Exports =====
    Route::get('/checklists/{checklist}/export/pdf',  [TicketChecklistController::class,'exportPdf'])->name('checklists.export.pdf')->whereNumber('checklist');
    Route::get('/checklists/{checklist}/export/word', [TicketChecklistController::class,'exportWord'])->name('checklists.export.word')->whereNumber('checklist');

    // ===== IA (100% OpenAI) =====
    Route::post('/tickets/{ticket}/stages/{stage}/ai/suggest', [TicketChecklistController::class,'suggestFromPrompt'])->name('tickets.ai.suggest')->whereNumber('ticket')->whereNumber('stage');
    Route::post('/tickets/{ticket}/checklists/ai',             [TicketChecklistController::class,'createFromAi'])->name('tickets.ai.create')->whereNumber('ticket');
});

/*
|--------------------------------------------------------------------------
| MAILBOX (todas bajo /mail)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])
    ->prefix('mail')
    ->name('mail.')
    ->group(function () {

        // Lista principal
        Route::get('/', [MailboxController::class, 'index'])->name('index');

        // Listar carpeta específica
        Route::get('/folder/{folder}', [MailboxController::class, 'folder'])
            ->where('folder', '.*')
            ->name('folder');

        // Ver mensaje en página independiente
        Route::get('/show/{folder}/{uid}', [MailboxController::class, 'show'])
            ->where('folder', '.*')
            ->whereNumber('uid')
            ->name('show');

        // Descargar adjunto
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
            ->name('toggleFlag');

        Route::post('/mark-read/{folder}/{uid}', [MailboxController::class, 'markRead'])
            ->where('folder', '.*')
            ->whereNumber('uid')
            ->name('markRead');

        // === API en tiempo real (todas bajo /mail/api/...) ===
        Route::get('/api/messages', [MailboxController::class, 'apiMessages'])->name('api.messages');
        Route::get('/api/counts',   [MailboxController::class, 'apiCounts'])->name('api.counts');
        Route::get('/api/wait',     [MailboxController::class, 'apiWait'])->name('api.wait');

        // Acciones rápidas por mensaje
        Route::post('/move/{folder}/{uid}',   [MailboxController::class, 'move'])
            ->where('folder','.*')->whereNumber('uid')->name('move');

        Route::post('/delete/{folder}/{uid}', [MailboxController::class, 'delete'])
            ->where('folder','.*')->whereNumber('uid')->name('delete');
    });

/*
|--------------------------------------------------------------------------
| AGENDA
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Vista calendario
    Route::get('/agenda', [AgendaEventController::class,'calendar'])->name('agenda.calendar');
   Route::get('/agenda/users', [AgendaEventController::class, 'users'])->name('agenda.users');
    // Feed JSON
    Route::get('/agenda/feed', [AgendaEventController::class,'feed'])->name('agenda.feed');

    // CRUD AJAX
    Route::post('/agenda',             [AgendaEventController::class,'store'])->name('agenda.store');
    Route::get('/agenda/{agenda}',     [AgendaEventController::class,'show'])->name('agenda.show');
    Route::put('/agenda/{agenda}',     [AgendaEventController::class,'update'])->name('agenda.update');
    Route::delete('/agenda/{agenda}',  [AgendaEventController::class,'destroy'])->name('agenda.destroy');

    // Drag/resize
    Route::put('/agenda/{agenda}/move', [AgendaEventController::class,'move'])->name('agenda.move');
});

/*
|--------------------------------------------------------------------------
| MERCADO LIBRE
|--------------------------------------------------------------------------
*/
// OAuth + webhook
Route::get('/meli/connect',     [MeliController::class, 'connect'])->name('meli.connect');
Route::get('/meli/callback',    [MeliController::class, 'callback'])->name('meli.callback');
Route::post('/meli/notifications', [MeliController::class, 'notifications'])->name('meli.notifications');

// Rutas Mercado Libre por producto (panel admin)
Route::middleware('auth')
    ->prefix('admin/catalog')
    ->name('admin.catalog.')
    ->group(function () {
        Route::post('{catalogItem}/meli/publish',  [CatalogItemController::class,'meliPublish'])->name('meli.publish');
        Route::post('{catalogItem}/meli/pause',    [CatalogItemController::class,'meliPause'])->name('meli.pause');
        Route::post('{catalogItem}/meli/activate', [CatalogItemController::class,'meliActivate'])->name('meli.activate');
        Route::get ('{catalogItem}/meli/view',     [CatalogItemController::class,'meliView'])->name('meli.view');
    });

/*
|--------------------------------------------------------------------------
| BLOG / POSTS
|--------------------------------------------------------------------------
*/
Route::get('posts',                 [PostController::class, 'index'])->name('posts.index');
Route::get('posts/create',          [PostController::class, 'create'])->name('posts.create');
Route::post('posts',                [PostController::class, 'store'])->name('posts.store');
Route::get('posts/{post}',          [PostController::class, 'show'])->name('posts.show');
Route::post('posts/{post}/comment', [PostController::class, 'storeComment'])->name('posts.comment');

/*
|--------------------------------------------------------------------------
| PARTIDA CONTABLE
|--------------------------------------------------------------------------
*/
Route::middleware(['web','auth'])->group(function () {
    Route::get('part-contable',                          [PartContableController::class, 'index'])->name('partcontable.index');
    Route::get('part-contable/{company:slug}',           [PartContableController::class, 'showCompany'])->name('partcontable.company');

    Route::get('part-contable/{company:slug}/documents/create', [PartContableController::class, 'createDocument'])->name('partcontable.documents.create');
    Route::post('part-contable/{company:slug}/documents',       [PartContableController::class, 'storeDocument'])->name('partcontable.documents.store');

    Route::get   ('partcontable/documents/{document}/raw',      [PartContableController::class, 'raw'])->name('partcontable.documents.raw');
    Route::get   ('partcontable/documents/{document}/preview',  [PartContableController::class, 'preview'])->name('partcontable.documents.preview');
    Route::get   ('partcontable/documents/{document}/download', [PartContableController::class, 'download'])->name('partcontable.documents.download');
    Route::delete('partcontable/documents/{document}',          [PartContableController::class, 'destroy'])->name('partcontable.documents.destroy');
});

/*
|--------------------------------------------------------------------------
| WIZARD LICITACIONES (clásico)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // (opcional) Empresas
    Route::get('/companies/create', [CompanyController::class, 'create'])
        ->name('companies.create');

    // Listado y detalle
    Route::get('/licitaciones',              [LicitacionWizardController::class, 'index'])->name('licitaciones.index');
    Route::get('/licitaciones/{licitacion}', [LicitacionWizardController::class, 'show'])->name('licitaciones.show');

    // Paso 1
    Route::get('/licitaciones/create/step-1', [LicitacionWizardController::class, 'createStep1'])->name('licitaciones.create.step1');
    Route::post('/licitaciones/store/step-1', [LicitacionWizardController::class, 'storeStep1'])->name('licitaciones.store.step1');

    // Paso 2
    Route::get('/licitaciones/{licitacion}/step-2',  [LicitacionWizardController::class, 'editStep2'])->name('licitaciones.edit.step2');
    Route::post('/licitaciones/{licitacion}/step-2', [LicitacionWizardController::class, 'updateStep2'])->name('licitaciones.update.step2');

    // Paso 3
    Route::get('/licitaciones/{licitacion}/step-3',  [LicitacionWizardController::class, 'editStep3'])->name('licitaciones.edit.step3');
    Route::post('/licitaciones/{licitacion}/step-3', [LicitacionWizardController::class, 'updateStep3'])->name('licitaciones.update.step3');

    // Paso 5
    Route::get('/licitaciones/{licitacion}/step-5',  [LicitacionWizardController::class, 'editStep5'])->name('licitaciones.edit.step5');
    Route::post('/licitaciones/{licitacion}/step-5', [LicitacionWizardController::class, 'updateStep5'])->name('licitaciones.update.step5');

    // Paso 6
    Route::get('/licitaciones/{licitacion}/step-6',  [LicitacionWizardController::class, 'editStep6'])->name('licitaciones.edit.step6');
    Route::post('/licitaciones/{licitacion}/step-6', [LicitacionWizardController::class, 'updateStep6'])->name('licitaciones.update.step6');

    // Paso 7
    Route::get('/licitaciones/{licitacion}/step-7',  [LicitacionWizardController::class, 'editStep7'])->name('licitaciones.edit.step7');
    Route::post('/licitaciones/{licitacion}/step-7', [LicitacionWizardController::class, 'updateStep7'])->name('licitaciones.update.step7');

    // Paso 8
    Route::get('/licitaciones/{licitacion}/step-8',  [LicitacionWizardController::class, 'editStep8'])->name('licitaciones.edit.step8');
    Route::post('/licitaciones/{licitacion}/step-8', [LicitacionWizardController::class, 'updateStep8'])->name('licitaciones.update.step8');

    // Paso 9
    Route::get('/licitaciones/{licitacion}/step-9',  [LicitacionWizardController::class, 'editStep9'])->name('licitaciones.edit.step9');
    Route::post('/licitaciones/{licitacion}/step-9', [LicitacionWizardController::class, 'updateStep9'])->name('licitaciones.update.step9');

    // PREGUNTAS DE LICITACIÓN (Paso 4 lógico)
    Route::get ('/licitaciones/{licitacion}/preguntas',            [LicitacionPreguntaController::class, 'index'])->name('licitaciones.preguntas.index');
    Route::post('/licitaciones/{licitacion}/preguntas',            [LicitacionPreguntaController::class, 'store'])->name('licitaciones.preguntas.store');
    Route::get ('/licitaciones/{licitacion}/preguntas/export-pdf', [LicitacionExportController::class, 'exportPreguntasPdf'])->name('licitaciones.preguntas.exportPdf');
    Route::get ('/licitaciones/{licitacion}/preguntas/export-word',[LicitacionExportController::class, 'exportPreguntasWord'])->name('licitaciones.preguntas.exportWord');

    // Resumen / contabilidad PDFs
    Route::get('licitaciones/{licitacion}/resumen-pdf',       [LicitacionWizardController::class, 'resumenPdf'])->name('licitaciones.resumen.pdf');
    Route::get('/licitaciones/{licitacion}/contabilidad/pdf', [LicitacionWizardController::class, 'contabilidadPdf'])->name('licitaciones.contabilidad.pdf');

    // CHECKLISTS Y CONTABILIDAD (Pasos 10, 11, 12)
    // Paso 10: checklist compras
    Route::get('/licitaciones/{licitacion}/checklist-compras',        [LicitacionChecklistController::class, 'editCompras'])->name('licitaciones.checklist.compras.edit');
    Route::post('/licitaciones/{licitacion}/checklist-compras',       [LicitacionChecklistController::class, 'storeCompras'])->name('licitaciones.checklist.compras.store');
    Route::patch('/licitaciones/{licitacion}/checklist-compras/{item}', [LicitacionChecklistController::class, 'updateCompras'])->name('licitaciones.checklist.compras.update');

    // Paso 11: checklist facturación
    Route::get('/licitaciones/{licitacion}/checklist-facturacion',  [LicitacionChecklistController::class, 'editFacturacion'])->name('licitaciones.checklist.facturacion.edit');
    Route::post('/licitaciones/{licitacion}/checklist-facturacion', [LicitacionChecklistController::class, 'storeFacturacion'])->name('licitaciones.checklist.facturacion.store');

    // Paso 12: contabilidad
    Route::get('/licitaciones/{licitacion}/contabilidad',  [LicitacionChecklistController::class, 'editContabilidad'])->name('licitaciones.contabilidad.edit');
    Route::post('/licitaciones/{licitacion}/contabilidad', [LicitacionChecklistController::class, 'storeContabilidad'])->name('licitaciones.contabilidad.store');
});

/*
|--------------------------------------------------------------------------
| LICITACIONES AI
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])
    ->prefix('licitaciones-ai')
    ->name('licitaciones-ai.')
    ->group(function () {

        // LISTA / CREAR / SUBIR
        Route::get('/',      [LicitacionFileController::class, 'index'])->name('index');
        Route::get('/crear', [LicitacionFileController::class, 'create'])->name('create');
        Route::post('/',     [LicitacionFileController::class, 'store'])->name('store');

        // TABLA GLOBAL
        Route::get('/tabla-global',             [LicitacionFileController::class, 'tablaGlobal'])->name('tabla-global');
        Route::get('/tabla-global-excel',       [LicitacionFileController::class, 'exportarExcelGlobal'])->name('tabla-global.excel');
        Route::post('/tabla-global-regenerar',  [LicitacionFileController::class, 'regenerarTablaGlobal'])->name('tabla-global.regenerar');
        Route::post('/tabla-global/{itemGlobal}', [LicitacionFileController::class, 'actualizarMarcaModelo'])->name('tabla-global.update');

        // ITEMS ORIGINALES (AGREGAR / EDITAR / ELIMINAR)
        Route::post('/{licitacionFile}/items',  [LicitacionFileController::class, 'storeItemOriginal'])->name('items.store');
        Route::post('/items/{itemOriginal}',    [LicitacionFileController::class, 'actualizarItemOriginal'])->name('items.update');
        Route::delete('/items/{itemOriginal}',  [LicitacionFileController::class, 'destroyItemOriginal'])->name('items.destroy');

        // EXCEL INDIVIDUAL / SHOW / DELETE LICITACION
        Route::get   ('/{licitacionFile}/excel', [LicitacionFileController::class, 'exportarExcel'])->name('excel');
        Route::get   ('/{licitacionFile}',       [LicitacionFileController::class, 'show'])->name('show');
        Route::delete('/{licitacionFile}',       [LicitacionFileController::class, 'destroy'])->name('destroy');
    });

/*
|--------------------------------------------------------------------------
| PÚBLICO CELULAR (sin auth) – captura AI
|--------------------------------------------------------------------------
*/
Route::get('/i/{token}',         [CatalogAiIntakePublicController::class, 'capture'])->name('intake.mobile');
Route::post('/i/{token}/upload', [CatalogAiIntakePublicController::class, 'upload'])->name('intake.upload');
Route::get('/i/{token}/status',  [CatalogAiIntakePublicController::class, 'status'])->name('intake.status');

/*
|--------------------------------------------------------------------------
| ADMIN – IA para captura de catálogo
|--------------------------------------------------------------------------
*/
Route::middleware(['auth','role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // IA para captura de factura/remisión
        Route::post('/catalog/ai/start', [CatalogItemController::class, 'aiStart'])
            ->name('catalog.ai.start');

        Route::get('/catalog/ai/{intake}/status', [CatalogItemController::class, 'aiStatus'])
            ->name('catalog.ai.status');
    });

/*
|--------------------------------------------------------------------------
| PRODUCTOS – CLAVE SAT masivo
|--------------------------------------------------------------------------
*/
Route::post('/products/bulk-clave-sat',       [ProductController::class, 'bulkClaveSat'])->name('products.bulk-clave-sat');
Route::post('/products/ai-suggest-clave-sat', [ProductController::class, 'aiSuggestClaveSat'])->name('products.ai-suggest-clave-sat');

/*
|--------------------------------------------------------------------------
| FACTURAS MANUALES (ManualInvoice)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::resource('facturas', ManualInvoiceController::class)
        ->parameters(['facturas' => 'manualInvoice'])
        ->names('manual_invoices');

    // Acción extra para timbrar (desde borrador)
    Route::post('facturas/{manualInvoice}/timbrar', [ManualInvoiceController::class, 'stamp'])
        ->name('manual_invoices.stamp');
});

// Descargas por slug "manual-invoices/..."
Route::get('manual-invoices/{manualInvoice}/pdf', [ManualInvoiceController::class, 'downloadPdf'])
    ->name('manual_invoices.download_pdf');

Route::get('manual-invoices/{manualInvoice}/xml', [ManualInvoiceController::class, 'downloadXml'])
    ->name('manual_invoices.download_xml');

/*
|--------------------------------------------------------------------------
| IA desde upload de catálogo
|--------------------------------------------------------------------------
*/
Route::post('/admin/catalog/ai-from-upload', [CatalogItemController::class, 'aiFromUpload'])
    ->name('admin.catalog.ai-from-upload');

/*
|--------------------------------------------------------------------------
| CRON AGENDA
|--------------------------------------------------------------------------
*/
Route::get('/cron/agenda-run/{token}', [CronController::class, 'runAgenda'])
    ->name('cron.agenda.run');

// (repetido tickets.work fuera del grupo grande, lo dejo tal como lo tenías)
Route::get('/tickets/{ticket}/work', [TicketController::class, 'work'])
    ->name('tickets.work');


/*
|--------------------------------------------------------------------------
| EXPORTACIONES PRODUCTOS
|--------------------------------------------------------------------------
*/
Route::get('/products/export/pdf',   [ProductController::class, 'exportPdf'])->name('products.export.pdf');
Route::get('/products/export/excel', [ProductController::class, 'exportExcel'])->name('products.export.excel');


// 👇 agrega debajo:
Route::post('licitacion-propuestas/{licitacionPropuesta}/splits/{splitIndex}/process', 
    [LicitacionPropuestaController::class, 'processSplit'])
    ->name('admin.licitacion-propuestas.splits.process');

Route::post('licitacion-propuestas/{licitacionPropuesta}/merge',
    [LicitacionPropuestaController::class, 'merge'])
    ->name('admin.licitacion-propuestas.merge');

Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/products/search', [LicitacionPropuestaController::class, 'searchProducts'])
            ->name('products.search');

        Route::post('/licitacion-propuesta-items/{item}/apply-product', [LicitacionPropuestaController::class, 'applyProductAjax'])
            ->name('licitacion-propuesta-items.apply-product');
    });



    Route::get('/debug/openai/models', [DebugOpenAiController::class, 'models']);
Route::get('/debug/openai/ticker', [DebugOpenAiController::class, 'ticker']);


Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/orders', [AdminOrderController::class,'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class,'show'])->name('orders.show');

    Route::post('/orders/{order}/skydropx/quote', [AdminOrderController::class,'skydropxQuote'])->name('orders.skydropx.quote');
    Route::post('/orders/{order}/skydropx/buy',   [AdminOrderController::class,'skydropxBuy'])->name('orders.skydropx.buy');
});
// routes/web.php
Route::middleware(['auth'])->group(function () {
  Route::get('/mi-cuenta/pedidos/{order}', [\App\Http\Controllers\Customer\CustomerOrdersController::class, 'show'])
    ->name('customer.orders.show');
});
Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // ... aquí seguramente ya tienes otras rutas ...

        // Propuestas (resource o lo que tengas)
        // Route::resource('licitacion-propuestas', LicitacionPropuestaController::class);

        // 🔽 Descarga PDF
        Route::get(
            'licitacion-propuestas/{licitacionPropuesta}/export/pdf',
            [LicitacionPropuestaController::class, 'exportPdf']
        )->name('licitacion-propuestas.export.pdf');

        // 🔽 Descarga Word
        Route::get(
            'licitacion-propuestas/{licitacionPropuesta}/export/word',
            [LicitacionPropuestaController::class, 'exportWord']
        )->name('licitacion-propuestas.export.word');
        // ================= RUTAS NUEVAS PARA RENGLONES (ITEMS) =================

// Crear renglón (botón "Agregar renglón" -> modal NUEVO)
Route::post(
    'licitacion-propuestas/{licitacionPropuesta}/items',
    [\App\Http\Controllers\Admin\LicitacionPropuestaController::class, 'storeItem']
)->name('licitacion-propuestas.items.store');

// Actualizar renglón (modal "Editar renglón", manda _method=PUT)
Route::put(
    'licitacion-propuesta-items/{item}',
    [\App\Http\Controllers\Admin\LicitacionPropuestaController::class, 'updateItem']
)->name('licitacion-propuesta-items.update');

// Eliminar renglón (botón "Eliminar")
Route::delete(
    'licitacion-propuesta-items/{item}',
    [\App\Http\Controllers\Admin\LicitacionPropuestaController::class, 'destroyItem']
)->name('licitacion-propuesta-items.destroy');

// AJAX: actualizar % de utilidad por renglón
Route::post(
    'licitacion-propuesta-items/{item}/update-utility',
    [\App\Http\Controllers\Admin\LicitacionPropuestaController::class, 'updateItemUtilityAjax']
)->name('licitacion-propuesta-items.update-utility');

// AJAX: aplicar producto al renglón (picker / sugerencias)
Route::post(
    'licitacion-propuesta-items/{item}/apply-product',
    [\App\Http\Controllers\Admin\LicitacionPropuestaController::class, 'applyProductAjax']
)->name('licitacion-propuesta-items.apply-product');

    });

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('licitacion-pdfs/{licitacionPdf}/ai', [LicitacionPdfAiController::class, 'show'])
        ->name('admin.licitacion-pdfs.ai.show');

    Route::post('licitacion-pdfs/{licitacionPdf}/ai/message', [LicitacionPdfAiController::class, 'message'])
        ->name('admin.licitacion-pdfs.ai.message');

    Route::post('licitacion-pdfs/{licitacionPdf}/ai/notes/pdf', [LicitacionPdfAiController::class, 'notesPdf'])
        ->name('admin.licitacion-pdfs.ai.notes.pdf');

    // ✅ viewer con highlight
    Route::get('licitacion-pdfs/{licitacionPdf}/ai/viewer', [LicitacionPdfAiController::class, 'viewer'])
        ->name('admin.licitacion-pdfs.ai.viewer');
});



Route::middleware(['auth'])->prefix('admin/wms')->name('admin.wms.')->group(function () {

    /* =========================
     |  VISTAS (BLADES)
     ========================= */
    Route::get('/', fn () => view('admin.wms.home'))->name('home');

    // Buscador UI
    Route::get('/search', fn () => view('admin.wms.search'))->name('search.view');

    /* =========================
     |  API: BUSCAR + LLEVAME + SCANNER (✅ SOLO UNA VEZ)
     ========================= */
    Route::get('/search/products', [WmsSearchController::class, 'products'])->name('search.products');
    Route::get('/nav', [WmsSearchController::class, 'nav'])->name('nav');

    // ✅ Escaneo ubicación (acepta raw/id/code)
    Route::get('/locations/scan', [WmsSearchController::class, 'locationScan'])->name('locations.scan');

    // ✅ Escaneo producto (acepta raw/id/sku/gtin)
    Route::get('/products/scan', [WmsSearchController::class, 'productScan'])->name('products.scan');

    /* =========================
     |  PICKING
     ========================= */

    // ✅ Alias para compatibilidad con el home.blade.php (DEBE IR ANTES de /pick/{wave})
    Route::get('/pick/entry', function () {
        $warehouseId = (int) (\App\Models\Warehouse::query()->value('id') ?? 1);
        $waves = \App\Models\PickWave::query()->orderByDesc('id')->limit(25)->get();
        return view('admin.wms.pick_waves', compact('waves', 'warehouseId'));
    })->name('pick.entry');

    // Waves UI (lista + crear)
    Route::get('/pick', function () {
        $warehouseId = (int) (\App\Models\Warehouse::query()->value('id') ?? 1);
        $waves = \App\Models\PickWave::query()->orderByDesc('id')->limit(25)->get();
        return view('admin.wms.pick_waves', compact('waves', 'warehouseId'));
    })->name('pick.waves.page');

    // Picking UI (wave)
    Route::get('/pick/{wave}', function (\App\Models\PickWave $wave) {
        return view('admin.wms.pick_show', compact('wave'));
    })->whereNumber('wave')->name('pick.show');

    /* =========================
     |  QR: PÁGINA + IMPRESIÓN
     ========================= */

    // Página HTML por QR (Ubicación)
    Route::get('/locations/{location}/page', function (\App\Models\Location $location) {
        $rows = \App\Models\Inventory::where('location_id', $location->id)
            ->with('item:id,name,sku,price,meli_gtin')
            ->orderByDesc('qty')
            ->get();

        return view('admin.wms.location_show', compact('location', 'rows'));
    })->name('location.page');

    // Imprimir 1 QR
    Route::get('/qr/print/{location}', function (\App\Models\Location $location) {
        $locations = collect([$location]);
        $qrMap = [];

        if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            $url = route('admin.wms.location.page', ['location' => $location->id]);
            $qrMap[$location->id] = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(220)->margin(1)->generate($url);
        }

        return view('admin.wms.qr_print', compact('locations', 'qrMap'));
    })->name('qr.print.one');

    // Imprimir lote de QRs (opcional: ?type=stand o ?s=A-03)
    Route::get('/qr/print', function (\Illuminate\Http\Request $r) {
        $q = \App\Models\Location::query()->orderBy('code');

        if ($r->filled('type')) $q->where('type', $r->get('type'));
        if ($r->filled('s')) {
            $s = trim((string) $r->get('s'));
            $q->where('code', 'like', "%{$s}%");
        }

        $locations = $q->limit(60)->get();
        $qrMap = [];

        if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            foreach ($locations as $loc) {
                $url = route('admin.wms.location.page', ['location' => $loc->id]);
                $qrMap[$loc->id] = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(220)->margin(1)->generate($url);
            }
        }

        return view('admin.wms.qr_print', compact('locations', 'qrMap'));
    })->name('qr.print.batch');

    /* =========================
     |  VISTAS: LAYOUT + HEATMAP
     ========================= */

    // Editor de Layout
    Route::get('/layout', function (\Illuminate\Http\Request $r) {
        $warehouses = \App\Models\Warehouse::query()->orderBy('id')->get();
        $warehouseId = (int) ($r->get('warehouse_id') ?? ($warehouses->first()->id ?? 1));
        $warehouse = \App\Models\Warehouse::query()->find($warehouseId);

        return view('admin.wms.layout', compact('warehouse', 'warehouseId', 'warehouses'));
    })->name('layout.editor');

    // Heatmap (calor)
    Route::get('/heatmap', function (\Illuminate\Http\Request $r) {
        $warehouses = \App\Models\Warehouse::query()->orderBy('id')->get();
        $warehouseId = (int) ($r->get('warehouse_id') ?? ($warehouses->first()->id ?? 1));
        $warehouse = \App\Models\Warehouse::query()->find($warehouseId);

        $locations = \App\Models\Location::query()
            ->where('warehouse_id', $warehouseId)
            ->orderBy('code')
            ->get();

        return view('admin.wms.heatmap', compact('warehouse','warehouseId','warehouses','locations'));
    })->name('heatmap.view');

    /* =========================
     |  API: LAYOUT
     ========================= */

    // GET /admin/wms/layout/data?warehouse_id=...
    Route::get('/layout/data', function (\Illuminate\Http\Request $r) {
        $data = $r->validate([
            'warehouse_id' => ['required','integer','exists:warehouses,id'],
        ]);

        $locations = \App\Models\Location::query()
            ->where('warehouse_id', (int)$data['warehouse_id'])
            ->orderBy('aisle')
            ->orderBy('section')
            ->orderBy('stand')
            ->orderBy('rack')
            ->orderBy('level')
            ->orderBy('bin')
            ->get()
            ->map(function ($l) {
                return [
                    'id' => $l->id,
                    'warehouse_id' => $l->warehouse_id,
                    'parent_id' => $l->parent_id,
                    'type' => $l->type,
                    'code' => $l->code,
                    'aisle' => $l->aisle,
                    'section' => $l->section,
                    'stand' => $l->stand,
                    'rack' => $l->rack,
                    'level' => $l->level,
                    'bin' => $l->bin,
                    'name' => $l->name,
                    'meta' => $l->meta ?? [],
                ];
            })
            ->values();

        return response()->json([
            'ok' => true,
            'locations' => $locations,
        ]);
    })->name('layout.data');

    // POST /admin/wms/layout/cell (crear/actualizar 1 ubicación)
    Route::post('/layout/cell', function (\Illuminate\Http\Request $r) {
        $payload = $r->validate([
            'id' => ['nullable','integer','exists:locations,id'],
            'warehouse_id' => ['required','integer','exists:warehouses,id'],

            'type' => ['nullable','string','max:40'],
            'code' => ['required','string','max:80'],
            'name' => ['nullable','string','max:255'],

            'aisle' => ['nullable','string','max:40'],
            'section' => ['nullable','string','max:40'],
            'stand' => ['nullable','string','max:40'],
            'rack' => ['nullable','string','max:40'],
            'level' => ['nullable','string','max:40'],
            'bin' => ['nullable','string','max:40'],

            'meta' => ['nullable','array'],
            'meta.x' => ['nullable','integer','min:0'],
            'meta.y' => ['nullable','integer','min:0'],
            'meta.w' => ['nullable','integer','min:1'],
            'meta.h' => ['nullable','integer','min:1'],
            'meta.notes' => ['nullable','string','max:2000'],
        ]);

        $id = $payload['id'] ?? null;

        $attrs = [
            'warehouse_id' => (int)$payload['warehouse_id'],
            'type' => $payload['type'] ?? 'bin',
            'code' => $payload['code'],
            'name' => $payload['name'] ?? null,

            'aisle' => $payload['aisle'] ?? null,
            'section' => $payload['section'] ?? null,
            'stand' => $payload['stand'] ?? null,
            'rack' => $payload['rack'] ?? null,
            'level' => $payload['level'] ?? null,
            'bin' => $payload['bin'] ?? null,

            'meta' => $payload['meta'] ?? [],
        ];

        if ($id) {
            $loc = \App\Models\Location::query()->findOrFail($id);
            $loc->update($attrs);
        } else {
            $loc = \App\Models\Location::query()->create($attrs);
        }

        return response()->json([
            'ok' => true,
            'location' => [
                'id' => $loc->id,
                'warehouse_id' => $loc->warehouse_id,
                'type' => $loc->type,
                'code' => $loc->code,
                'aisle' => $loc->aisle,
                'section' => $loc->section,
                'stand' => $loc->stand,
                'rack' => $loc->rack,
                'level' => $loc->level,
                'bin' => $loc->bin,
                'name' => $loc->name,
                'meta' => $loc->meta ?? [],
            ],
        ]);
    })->name('layout.cell');

    // DELETE /admin/wms/layout/delete (BORRAR 1 ubicación)
    Route::post('/layout/delete', function (\Illuminate\Http\Request $r) {
        $data = $r->validate([
            'warehouse_id' => ['required','integer','exists:warehouses,id'],
            'id' => ['required','integer','exists:locations,id'],
        ]);

        $whId = (int)$data['warehouse_id'];
        $id   = (int)$data['id'];

        $loc = \App\Models\Location::query()
            ->where('warehouse_id', $whId)
            ->where('id', $id)
            ->first();

        if (!$loc) {
            return response()->json(['ok'=>false,'error'=>'No encontrado en esa bodega.'], 404);
        }

        $loc->delete();

        return response()->json(['ok'=>true]);
    })->name('layout.delete');

    // POST /admin/wms/layout/generate-rack (generar en lote)
    Route::post('/layout/generate-rack', function (\Illuminate\Http\Request $r) {
        $p = $r->validate([
            'warehouse_id' => ['required','integer','exists:warehouses,id'],

            'prefix' => ['required','string','max:10'], // pasillo
            'stand' => ['nullable','string','max:10'],

            'rack_count' => ['required','integer','min:1','max:200'],
            'levels' => ['required','integer','min:1','max:10'],
            'bins' => ['required','integer','min:1','max:10'],

            'start_x' => ['required','integer','min:0'],
            'start_y' => ['required','integer','min:0'],
            'cell_w' => ['required','integer','min:1'],
            'cell_h' => ['required','integer','min:1'],
            'gap_x' => ['required','integer','min:0'],
            'gap_y' => ['required','integer','min:0'],

            'direction' => ['required','in:right,down'],
        ]);

        $whId = (int)$p['warehouse_id'];
        $prefix = strtoupper(trim((string)$p['prefix']));
        $stand = $p['stand'] !== null ? trim((string)$p['stand']) : null;

        $rackCount = (int)$p['rack_count'];
        $levels = (int)$p['levels'];
        $bins = (int)$p['bins'];

        $startX = (int)$p['start_x'];
        $startY = (int)$p['start_y'];
        $cellW = (int)$p['cell_w'];
        $cellH = (int)$p['cell_h'];
        $gapX = (int)$p['gap_x'];
        $gapY = (int)$p['gap_y'];
        $direction = $p['direction'];

        $binInnerGap = 1;
        $rackSpanX = ($bins * $cellW) + (($bins - 1) * $binInnerGap);
        $rackSpanY = ($levels * $cellH) + (($levels - 1) * $gapY);

        $created = 0;

        for ($rIdx = 1; $rIdx <= $rackCount; $rIdx++) {
            $rackNo = str_pad((string)$rIdx, 2, '0', STR_PAD_LEFT);

            if ($direction === 'right') {
                $rackBaseX = $startX + (($rIdx - 1) * ($rackSpanX + $gapX));
                $rackBaseY = $startY;
            } else {
                $rackBaseX = $startX;
                $rackBaseY = $startY + (($rIdx - 1) * ($rackSpanY + $gapY));
            }

            for ($lvl = 1; $lvl <= $levels; $lvl++) {
                $lvlNo = str_pad((string)$lvl, 2, '0', STR_PAD_LEFT);

                for ($b = 1; $b <= $bins; $b++) {
                    $binNo = str_pad((string)$b, 2, '0', STR_PAD_LEFT);

                    $code = $prefix;
                    if ($stand !== null && $stand !== '') $code .= '-S'.$stand;
                    $code .= '-R'.$rackNo.'-L'.$lvlNo.'-B'.$binNo;

                    $x = $rackBaseX + (($b - 1) * ($cellW + $binInnerGap));
                    $y = $rackBaseY + (($lvl - 1) * ($cellH + $gapY));

                    $loc = \App\Models\Location::query()
                        ->where('warehouse_id', $whId)
                        ->where('code', $code)
                        ->first();

                    $data = [
                        'warehouse_id' => $whId,
                        'type' => 'bin',
                        'code' => $code,
                        'aisle' => $prefix,
                        'stand' => $stand,
                        'rack' => $rackNo,
                        'level' => $lvlNo,
                        'bin' => $binNo,
                        'meta' => [
                            'x' => $x,
                            'y' => $y,
                            'w' => $cellW,
                            'h' => $cellH,
                        ],
                    ];

                    if ($loc) {
                        $loc->update($data);
                    } else {
                        \App\Models\Location::query()->create($data);
                        $created++;
                    }
                }
            }
        }

        return response()->json([
            'ok' => true,
            'created' => $created,
        ]);
    })->name('layout.generate-rack');

    /* =========================
     |  API: HEATMAP
     ========================= */
    Route::get('/heatmap/data', function (\Illuminate\Http\Request $r) {

        $data = $r->validate([
            'warehouse_id' => ['required','integer','exists:warehouses,id'],
            'metric' => ['nullable','in:inv_qty,primary_stock'],
        ]);

        $warehouseId = (int)$data['warehouse_id'];
        $metric = $data['metric'] ?? 'inv_qty';

        $metaArr = function ($meta) {
            if (is_array($meta)) return $meta;
            if (is_object($meta)) return (array)$meta;
            if (is_string($meta) && trim($meta) !== '') {
                $d = json_decode($meta, true);
                return is_array($d) ? $d : [];
            }
            return [];
        };

        $locations = \App\Models\Location::query()
            ->where('warehouse_id', $warehouseId)
            ->orderBy('code')
            ->get(['id','code','meta']);

        $ids = $locations->pluck('id')->all();

        $qtyByLoc = \App\Models\Inventory::query()
            ->selectRaw('location_id, SUM(qty) as sum_qty')
            ->whereIn('location_id', $ids)
            ->groupBy('location_id')
            ->pluck('sum_qty', 'location_id');

        $cells = $locations->map(function ($l) use ($metaArr, $qtyByLoc) {
            $m = $metaArr($l->meta);
            if (!array_key_exists('x', $m) || !array_key_exists('y', $m)) return null;

            return [
                'id' => (int)$l->id,
                'code' => (string)$l->code,
                'x' => (int)($m['x'] ?? 0),
                'y' => (int)($m['y'] ?? 0),
                'w' => (int)($m['w'] ?? 1),
                'h' => (int)($m['h'] ?? 1),
                'value' => (int)($qtyByLoc[$l->id] ?? 0),
            ];
        })->filter()->values();

        $max = (int)($cells->max('value') ?? 0);

        return response()->json([
            'ok' => true,
            'metric' => $metric,
            'max' => $max,
            'cells' => $cells,
        ]);

    })->name('heatmap.data');

    /* =========================
     |  API WMS BASE
     ========================= */
    Route::get('warehouses', [WmsController::class, 'warehousesIndex'])->name('warehouses.index');
    Route::post('warehouses', [WmsController::class, 'warehousesStore'])->name('warehouses.store');

    Route::get('locations', function (\Illuminate\Http\Request $r) {
        return view('admin.wms.locations_index');
    })->name('locations.index');

    Route::get('locations/data', [WmsController::class, 'locationsIndex'])->name('locations.data');
    Route::post('locations', [WmsController::class, 'locationsStore'])->name('locations.store');

    // ⚠️ OJO: aquí YA NO repetimos locations/scan (ya está arriba con WmsSearchController)
    Route::put('locations/{location}', [WmsController::class, 'locationsUpdate'])->name('locations.update');
    Route::delete('locations/{location}', [WmsController::class, 'locationsDestroy'])->name('locations.destroy');
    Route::get('locations/{location}', [WmsController::class, 'locationShow'])->name('locations.show');

    Route::post('inventory/adjust', [WmsController::class, 'inventoryAdjust'])->name('inventory.adjust');
    Route::post('inventory/transfer', [WmsController::class, 'inventoryTransfer'])->name('inventory.transfer');

    Route::post('items/{catalogItem}/primary-location', [WmsController::class, 'setPrimaryLocation'])->name('items.primary-location');

    /* =========================
     |  API: PICKING
     ========================= */
    Route::post('pick/waves', [WmsPickingController::class, 'createWave'])->name('pick.waves.create');
    Route::post('pick/waves/{wave}/start', [WmsPickingController::class, 'startWave'])->name('pick.waves.start');
    Route::get('pick/waves/{wave}/next', [WmsPickingController::class, 'next'])->name('pick.waves.next');
    Route::post('pick/waves/{wave}/scan-location', [WmsPickingController::class, 'scanLocation'])->name('pick.waves.scan-location');
    Route::post('pick/waves/{wave}/scan-item', [WmsPickingController::class, 'scanItem'])->name('pick.waves.scan-item');
    Route::post('pick/waves/{wave}/finish', [WmsPickingController::class, 'finish'])->name('pick.waves.finish');

    /* =========================
     |  MOVIMIENTOS
     ========================= */
    Route::get('/move', [WmsMoveController::class, 'view'])->name('move.view');
    Route::get('/move/products', [WmsMoveController::class, 'products'])->name('move.products');
    Route::post('/move/commit', [WmsMoveController::class, 'commit'])->name('move.commit');

    Route::get('/movements', [WmsMoveController::class, 'movementsView'])->name('movements.view');
    Route::get('/movements/data', [WmsMoveController::class, 'movementsData'])->name('movements.data');

    Route::get('movements/{movement}/pdf', [WmsMoveController::class, 'movementPdf'])->name('movements.pdf');

});

Route::get('/cron/schedule-run/{token}', function (string $token) {
    $expected = (string) config('app.scheduler_cron_token');

    abort_unless($expected !== '' && hash_equals($expected, (string)$token), 403);

    Artisan::call('schedule:run');

    return response()->json([
        'ok'     => true,
        'ts'     => now()->toIso8601String(),
        'output' => Artisan::output(),
    ]);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/tickets/{ticket}/work', [TicketWorkController::class, 'show'])
        ->name('tickets.work');
});

Route::get('admin/licitacion-propuestas/{licitacionPropuesta}/export-excel', [LicitacionPropuestaController::class, 'exportExcel'])
  ->name('admin.licitacion-propuestas.export.excel');

Route::get('/publicaciones', [PublicationController::class, 'index'])->name('publications.index');
Route::get('/publicaciones/{publication}', [PublicationController::class, 'show'])->name('publications.show');
Route::get('/publicaciones/{publication}/descargar', [PublicationController::class, 'download'])->name('publications.download');

// Si quieres que solo admin suba/elimine:
Route::middleware(['auth'])->group(function () {
    Route::get('/publicaciones-subir', [PublicationController::class, 'create'])->name('publications.create');
    Route::post('/publicaciones', [PublicationController::class, 'store'])->name('publications.store');
    Route::delete('/publicaciones/{publication}', [PublicationController::class, 'destroy'])->name('publications.destroy');
});


Route::post('/licitacion-pdfs/{licitacionPdf}/pages-count', [\App\Http\Controllers\Admin\LicitacionPdfController::class, 'updatePagesCount'])
    ->name('admin.licitacion-pdfs.pagesCount');

Route::middleware(['auth'])->group(function () {

    // Create
    Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');

    // Store
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');

    // ✅ Evita el 404 si alguien cae a /companies
    //    y además si por cualquier motivo terminas ahí, te manda a /part-contable
    Route::get('/companies', fn () => redirect('/part-contable'))->name('companies.index');
});