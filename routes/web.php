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
use App\Http\Controllers\CatalogPublicController;
use App\Http\Controllers\TechSheetController;
use App\Http\Controllers\Admin\AltaDocsController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Tickets\TicketExecutiveController;
use App\Http\Controllers\Tickets\MyAssignmentsController;
use App\Http\Controllers\WhatsAppInboxController;
use App\Http\Controllers\Tickets\TicketReviewController;
use App\Http\Controllers\PartContable\ActivityController;
use App\Http\Controllers\ConfidentialDocsController;
use App\Http\Controllers\Admin\WmsAnalyticsController;
use App\Http\Controllers\WhatsApp\WhatsAppWebhookController;
use App\Http\Controllers\Admin\WaConversationController;
use App\Http\Controllers\Admin\WmsLayoutController;
use App\Http\Controllers\Admin\WmsFastFlowController;
use App\Http\Controllers\Admin\WmsShippingController;
use App\Http\Controllers\Accounting\CuentasDashboardController;
use App\Http\Controllers\Accounting\AlertsController;
use App\Http\Controllers\Accounting\ReceivableController;
use App\Http\Controllers\Accounting\PayableController;
use App\Http\Controllers\Accounting\MovementController;
use App\Http\Controllers\Accounting\ReportsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Projects\ProjectBoardController;
use App\Http\Controllers\Admin\CategoryProductController;
use App\Http\Controllers\Admin\WmsReceptionController;
use App\Http\Controllers\AzureTestController;

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
| TICKETS (sistema simple)
|--------------------------------------------------------------------------
*/


Route::middleware(['auth'])->group(function () {

    // =========================
    // Centro de Control (antes dashboard)
    // =========================
    Route::get('/tickets/centro-de-control', [TicketExecutiveController::class,'index'])
        ->name('tickets.executive');

    // =========================
    // Mis tickets (asignados a mí)
    // =========================
    Route::get('/my-tickets', [MyAssignmentsController::class,'index'])
        ->name('tickets.my');

    // =========================
    // Tickets CRUD
    // =========================
    Route::get   ('/tickets',            [TicketController::class,'index'])->name('tickets.index');
    Route::get   ('/tickets/create',     [TicketController::class,'create'])->name('tickets.create');
    Route::post  ('/tickets',            [TicketController::class,'store'])->name('tickets.store');

    // ✅ Vista detalle (solo lectura)
    Route::get   ('/tickets/{ticket}',   [TicketController::class,'show'])->name('tickets.show')->whereNumber('ticket');

    // ✅ Vista de trabajo para el asignado (workflow/timer)
    Route::get   ('/tickets/{ticket}/work', [TicketController::class,'work'])
        ->name('tickets.work')
        ->whereNumber('ticket');

    // ✅ Update (para cambiar status/priority/area/etc desde forms)
    Route::put   ('/tickets/{ticket}',   [TicketController::class,'update'])->name('tickets.update')->whereNumber('ticket');

    // =========================
    // Acciones rápidas
    // =========================
    Route::post('/tickets/{ticket}/complete', [TicketController::class,'complete'])
        ->name('tickets.complete')
        ->whereNumber('ticket');

    Route::post('/tickets/{ticket}/cancel',   [TicketController::class,'cancel'])
        ->name('tickets.cancel')
        ->whereNumber('ticket');

    // =========================
    // Comentarios
    // =========================
    Route::post('/tickets/{ticket}/comments', [TicketCommentController::class,'store'])
        ->name('tickets.comments.store')
        ->whereNumber('ticket');

    // =========================
    // Documentos
    // =========================
    Route::post('/tickets/{ticket}/documents', [TicketDocumentController::class,'store'])
        ->name('tickets.documents.store')
        ->whereNumber('ticket');

    Route::get('/tickets/{ticket}/documents/{doc}/download', [TicketDocumentController::class,'download'])
        ->name('tickets.documents.download')
        ->whereNumber('ticket')
        ->whereNumber('doc');

    Route::delete('/tickets/{ticket}/documents/{doc}', [TicketDocumentController::class,'destroy'])
        ->name('tickets.documents.destroy')
        ->whereNumber('ticket')
        ->whereNumber('doc');
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
    Route::get('/agenda/resumen', [AgendaEventController::class, 'summary'])->name('agenda.summary');

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


Route::middleware(['web', 'auth'])->prefix('admin')->group(function () {

    Route::get('/manual-invoices', [ManualInvoiceController::class, 'index'])
        ->name('manual_invoices.index');

    Route::get('/manual-invoices/create', [ManualInvoiceController::class, 'create'])
        ->name('manual_invoices.create');

    Route::post('/manual-invoices', [ManualInvoiceController::class, 'store'])
        ->name('manual_invoices.store');

    Route::get('/manual-invoices/{manualInvoice}', [ManualInvoiceController::class, 'show'])
        ->name('manual_invoices.show');

    Route::get('/manual-invoices/{manualInvoice}/edit', [ManualInvoiceController::class, 'edit'])
        ->name('manual_invoices.edit');

    Route::put('/manual-invoices/{manualInvoice}', [ManualInvoiceController::class, 'update'])
        ->name('manual_invoices.update');

    Route::delete('/manual-invoices/{manualInvoice}', [ManualInvoiceController::class, 'destroy'])
        ->name('manual_invoices.destroy');

    // ✅ Prefactura (PDF BORRADOR generado por Facturapi)
    Route::get('/manual-invoices/{manualInvoice}/draft-pdf', [ManualInvoiceController::class, 'downloadDraftPdf'])
        ->name('manual_invoices.downloadDraftPdf');

    // Timbrar (timbra el borrador si existe)
    Route::post('/manual-invoices/{manualInvoice}/stamp', [ManualInvoiceController::class, 'stamp'])
        ->name('manual_invoices.stamp');

    // PDF/XML ya timbrados
    Route::get('/manual-invoices/{manualInvoice}/pdf', [ManualInvoiceController::class, 'downloadPdf'])
        ->name('manual_invoices.downloadPdf');

    Route::get('/manual-invoices/{manualInvoice}/xml', [ManualInvoiceController::class, 'downloadXml'])
        ->name('manual_invoices.downloadXml');

});



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
Route::get('/cron/agenda-run', function (Request $request) {
    if (! $request->hasValidSignature()) {
        return response()->json([
            'ok' => false,
            'message' => 'Firma no válida.',
        ], 401);
    }

    try {
        Artisan::call('agenda:run', [
            '--limit'  => 200,
            '--window' => 5,
        ]);

        return response()->json([
            'ok'     => true,
            'output' => Artisan::output(),
            'time'   => now('America/Mexico_City')->format('Y-m-d H:i:s'),
        ]);
    } catch (\Throwable $e) {
        \Log::error('cron.agenda-run.error', [
            'message' => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
        ]);

        return response()->json([
            'ok'      => false,
            'message' => $e->getMessage(),
        ], 500);
    }
})->name('cron.agenda.run');

Route::get('/cron/agenda-url-test', function () {
    return URL::temporarySignedRoute(
        'cron.agenda.run',
        now()->addYears(5)
    );
});

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
    Route::get('/', [WmsAnalyticsController::class, 'dashboard'])->name('home');
    Route::get('/analytics', [WmsAnalyticsController::class, 'index'])->name('analytics');
});

Route::middleware(['auth'])->prefix('admin/wms')->name('admin.wms.')->group(function () {

    /* =========================
     |  VISTAS (BLADES)
     ========================= */
  

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

Route::get('/products/ajax-table', [ProductController::class, 'ajaxTable'])
  ->name('products.ajax-table');

// Ficha pública por slug
Route::get('/p/{catalogItem:slug}', [CatalogPublicController::class, 'preview'])
    ->name('catalog.preview');

// QR en SVG (para la ficha web)
Route::get('/p/{catalogItem:slug}/qr', [CatalogPublicController::class, 'qr'])
    ->name('catalog.qr');

// ✅ PDF etiqueta 2x2" con QR
Route::get('/p/{catalogItem}/qr-label', [CatalogPublicController::class, 'qrLabel'])
    ->name('catalog.qr.label');

// NUEVAS: código de barras + etiqueta 2x1"
Route::get('/p/{catalogItem}/barcode',        [CatalogPublicController::class, 'barcode'])->name('catalog.barcode');
Route::get('/p/{catalogItem}/barcode-label',  [CatalogPublicController::class, 'barcodeLabel'])->name('catalog.barcode.label');
Route::middleware([
        'auth',
        'approved',   // si lo usas
        'verified',   // si lo usas
        'role:admin', // tu EnsureRole('admin')
    ])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // ...tus demás rutas...

        Route::patch('catalog/{catalogItem}/stock', [CatalogItemController::class, 'updateStock'])
            ->name('catalog.stock.update');

        // Route::resource('catalog', CatalogItemController::class); // si ya lo tienes
    });

Route::middleware(['auth'])->group(function () {
    Route::get('tech-sheets',               [TechSheetController::class, 'index'])->name('tech-sheets.index');
    Route::get('tech-sheets/create',        [TechSheetController::class, 'create'])->name('tech-sheets.create');
    Route::post('tech-sheets',              [TechSheetController::class, 'store'])->name('tech-sheets.store');
    Route::get('tech-sheets/{sheet}',       [TechSheetController::class, 'show'])->name('tech-sheets.show');
    Route::get('tech-sheets/{sheet}/pdf',   [TechSheetController::class, 'pdf'])->name('tech-sheets.pdf');
    Route::get('tech-sheets/{sheet}/word',  [TechSheetController::class, 'word'])->name('tech-sheets.word');
});


Route::get('/admin/catalog/export/excel', [CatalogItemController::class, 'exportExcel'])
    ->name('admin.catalog.export.excel');

Route::get('/admin/catalog/export/pdf', [CatalogItemController::class, 'exportPdf'])
    ->name('admin.catalog.export.pdf');


Route::middleware(['auth'])->group(function () {

    // 1) Formulario de NIP
    Route::get('/documentacion-altas/pin', [AltaDocsController::class, 'showPinForm'])
        ->name('secure.alta-docs.pin');
    Route::get('/documentacion-altas/pin', [AltaDocsController::class, 'showPinForm'])
        ->name('secure.alta-docs.pin.show');
    // 2) Validar NIP
    Route::post('/documentacion-altas/pin', [AltaDocsController::class, 'checkPin'])
        ->name('secure.alta-docs.check-pin');

    // 3) Rutas protegidas por NIP
    Route::middleware('alta_docs_pin')->group(function () {

        // Listado + formulario de subida
        Route::get('/documentacion-altas', [AltaDocsController::class, 'index'])
            ->name('alta.docs.index');

        // 🔴 ESTA ES LA QUE TE FALTABA
        Route::post('/documentacion-altas', [AltaDocsController::class, 'store'])
            ->name('alta.docs.store');


// ✅ NUEVA: preview inline (para iframe/img/video)
Route::get('/secure/alta-docs/{doc}/preview', [AltaDocsController::class, 'preview'])
  ->name('alta.docs.preview');
  Route::get('/documentacion-altas/{doc}/descargar', [AltaDocsController::class, 'download'])
  ->name('alta.docs.download');

        // Eliminar
        Route::delete('/documentacion-altas/{doc}', [AltaDocsController::class, 'destroy'])
            ->name('alta.docs.destroy');
            // ✅ Preview inline (para iframe/img/video en la vista show)
Route::get('/secure/alta-docs/{doc}/preview', [AltaDocsController::class, 'preview'])
  ->name('alta.docs.preview');
    });

    // 4) Cerrar sesión de NIP
    Route::post('/documentacion-altas/logout', [AltaDocsController::class, 'logoutPin'])
        ->name('secure.alta-docs.logout');
    
// ✅ NUEVA: preview inline (para iframe/img/video)
Route::get('/secure/alta-docs/{doc}/preview', [AltaDocsController::class, 'preview'])
  ->name('alta.docs.preview');
});

// Vista pública de la ficha (sin auth)
Route::get('/ficha/{token}', [\App\Http\Controllers\TechSheetController::class, 'publicShow'])
    ->name('tech-sheets.public');

// QR PNG de la ficha pública
Route::get('/ficha/{token}/qr', [\App\Http\Controllers\TechSheetController::class, 'qr'])
    ->name('tech-sheets.qr');

    Route::middleware(['auth'])
  ->prefix('admin')
  ->name('admin.')
  ->group(function () {

    // ...tus rutas admin existentes...

    Route::get('/orders/export', [OrderController::class, 'export'])
      ->name('orders.export');
  });

  Route::get('tech-sheets/{sheet}/edit', [TechSheetController::class, 'edit'])
    ->name('tech-sheets.edit');

Route::put('tech-sheets/{sheet}', [TechSheetController::class, 'update'])
    ->name('tech-sheets.update');
Route::get('tech-sheets/{sheet}/pdf-generated', [\App\Http\Controllers\TechSheetController::class, 'pdfGenerated'])
  ->name('tech-sheets.pdf.generated');
Route::delete('tech-sheets/{sheet}/pdf/{type}', [\App\Http\Controllers\TechSheetController::class, 'deletePdf'])
  ->name('tech-sheets.pdf.delete');

// Si ya tienes resource sin destroy, al menos agrega:
Route::delete('/tech-sheets/{sheet}', [TechSheetController::class, 'destroy'])
    ->name('tech-sheets.destroy');
// CHECKLIST IA (PDF)
Route::get('/admin/licitacion-pdfs/{licitacionPdf}/checklist', [\App\Http\Controllers\Admin\LicitacionPdfAiController::class, 'checklist'])
  ->name('admin.licitacion-pdfs.ai.checklist');

Route::post('/admin/licitacion-pdfs/{licitacionPdf}/checklist/generate', [\App\Http\Controllers\Admin\LicitacionPdfAiController::class, 'generateChecklist'])
  ->name('admin.licitacion-pdfs.ai.checklist.generate');

Route::patch('/admin/licitacion-pdfs/checklist-items/{item}', [\App\Http\Controllers\Admin\LicitacionPdfAiController::class, 'updateChecklistItem'])
  ->name('admin.licitacion-pdfs.ai.checklist.item.update');
// CHAT IA PDF (vista)
Route::get('/admin/licitacion-pdfs/{licitacionPdf}/ai', [\App\Http\Controllers\Admin\LicitacionPdfAiController::class, 'show'])
  ->name('admin.licitacion-pdfs.ai.show');




Route::middleware(['auth'])->group(function () {

  // VISTAS (Blade) + JSON (mismo endpoint, depende de Accept header)
  Route::resource('vehicles', VehicleController::class);

  // Nómina (endpoints JSON + puedes hacer vista si quieres)
  Route::get('payroll/periods', [PayrollController::class, 'periods']);
  Route::post('payroll/periods', [PayrollController::class, 'createPeriod']);
  Route::get('payroll/periods/{period}', [PayrollController::class, 'periodDetail']);
  Route::post('payroll/entry', [PayrollController::class, 'upsertEntry']);

  // Evidencias
  Route::post('attachments', [AttachmentController::class, 'store']);
  Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy']);

});
Route::middleware(['auth'])->group(function () {
  

  // docs por vehículo
  Route::post('vehicles/{vehicle}/documents', [VehicleController::class, 'uploadDocuments'])->name('vehicles.documents.store');
  Route::delete('vehicles/{vehicle}/documents/{doc}', [VehicleController::class, 'deleteDocument'])->name('vehicles.documents.destroy');
});
Route::middleware(['auth'])->group(function () {

  Route::get('/expenses', [ExpenseController::class,'index'])->name('expenses.index');
  Route::get('/expenses/create', [ExpenseController::class,'create'])->name('expenses.create');
  Route::post('/expenses', [ExpenseController::class,'store'])->name('expenses.store');

  // APIs dashboard
  Route::get('/expenses/api/metrics', [ExpenseController::class,'apiMetrics'])->name('expenses.api.metrics');
  Route::get('/expenses/api/list',    [ExpenseController::class,'apiList'])->name('expenses.api.list');

  // Movimientos
  Route::post('/expenses/movements/allocation', [ExpenseController::class,'storeAllocation'])
    ->name('expenses.movement.allocation.store');

  Route::post('/expenses/movements/disbursement/direct', [ExpenseController::class,'storeDisbursementDirect'])
    ->name('expenses.movement.disbursement.direct');

  Route::post('/expenses/movements/disbursement/qr/start', [ExpenseController::class,'startDisbursementQr'])
    ->name('expenses.movement.disbursement.qr.start');

  Route::get('/expenses/movements/qr/{token}', [ExpenseController::class,'showMovementQrForm'])
    ->name('expenses.movements.qr.show');

  Route::post('/expenses/movements/qr/{token}', [ExpenseController::class,'ackMovementWithQr'])
    ->name('expenses.movements.qr.ack');

  Route::get('/expenses/movements/qr/status/{token}', [ExpenseController::class,'movementQrStatus']);

  Route::post('/expenses/movements/return', [ExpenseController::class,'storeReturn'])
    ->name('expenses.movement.return.store');
    Route::patch('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

});

Route::middleware(['auth'])->group(function () {

    // tu vista de perfil (si ya existe, ignora esto)
    Route::get('/panel/perfil', [ProfileController::class, 'show'])
        ->name('profile.show');

    // ✅ ruta que te falta
    Route::put('/panel/perfil/pin', [ProfileController::class, 'updatePin'])
        ->name('profile.pin.update');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/accounting/expenses/{expense}/pdf', [ExpenseController::class, 'pdfReceipt'])
        ->name('expenses.pdf');
});

Route::post('/catalog/{catalogItem}/amazon/publish', [CatalogItemController::class, 'amazonPublish'])->name('admin.catalog.amazon.publish');
Route::post('/catalog/{catalogItem}/amazon/pause',   [CatalogItemController::class, 'amazonPause'])->name('admin.catalog.amazon.pause');
Route::post('/catalog/{catalogItem}/amazon/activate',[CatalogItemController::class, 'amazonActivate'])->name('admin.catalog.amazon.activate');
Route::get ('/catalog/{catalogItem}/amazon/view',    [CatalogItemController::class, 'amazonView'])->name('admin.catalog.amazon.view');

// IA: analiza archivo (PDF/imagen) y devuelve items + resumen (no guarda)
Route::post('/publications/ai/extract', [PublicationController::class, 'aiExtractFromUpload'])
    ->name('publications.ai.extract');

// IA: opcional guardar resultado (después de analizar)
Route::post('/publications/ai/save', [PublicationController::class, 'aiSaveExtracted'])
    ->name('publications.ai.save');
    
    Route::get('/parte-contable', [PartContableController::class, 'index'])->name('partcontable.index');
Route::get('/parte-contable/{company:slug}', [PartContableController::class, 'showCompany'])->name('partcontable.company');

Route::post('/parte-contable/{company:slug}/unlock-pin', [PartContableController::class, 'unlockWithPin'])
  ->name('partcontable.unlock.pin');

Route::post('/parte-contable/{company:slug}/lock-pin', [PartContableController::class, 'lockPin'])
  ->name('partcontable.lock.pin');

Route::middleware(['auth'])->group(function () {
    // ✅ Bitácora global (todas las empresas)
    Route::get('/partcontable/actividad', [PartContableController::class, 'activityAll'])
        ->name('partcontable.activity.all');
});
Route::post('/partcontable/documents/{document}/ficticio', [PartContableController::class, 'uploadFicticio'])
  ->middleware(['web','auth'])
  ->name('partcontable.documents.ficticio.upload');


Route::get('/partcontable/documents/{document}/ficticio/download', [PartContableController::class, 'downloadFicticio'])
  ->name('partcontable.documents.ficticio.download');

Route::get('/secure/alta-docs/{doc}', [AltaDocsController::class, 'show'])->name('alta.docs.show');

Route::middleware(['auth'])->group(function () {
    Route::get('/whatsapp', [WhatsAppInboxController::class, 'index'])->name('whatsapp.index');
    Route::get('/whatsapp/{conversation}', [WhatsAppInboxController::class, 'show'])->name('whatsapp.show');
    Route::post('/whatsapp/{conversation}/send', [WhatsAppInboxController::class, 'send'])->name('whatsapp.send');
});

Route::get('/tickets/{ticket}/reporte-pdf', [TicketController::class, 'reportPdf'])
  ->name('tickets.reportPdf');

  Route::prefix('tickets')->name('tickets.')->group(function () {
    // ...
    Route::post('{ticket}/submit-review', [\App\Http\Controllers\Tickets\TicketController::class, 'submitForReview'])
        ->name('submitReview');

    Route::post('{ticket}/review-reject', [\App\Http\Controllers\Tickets\TicketController::class, 'reviewReject'])
        ->name('reviewReject');
     });   
Route::middleware(['auth'])->group(function () {

    // ✅ Aprobar por revisión (calificación)
    Route::post('/tickets/{ticket}/review/approve', [TicketReviewController::class, 'approve'])
        ->name('tickets.reviewApprove');

    // ✅ Reabrir por revisión (motivo + evidencias)
    Route::post('/tickets/{ticket}/review/force-reopen', [TicketReviewController::class, 'forceReopen'])
        ->name('tickets.forceReopen');

});

Route::middleware(['auth'])->group(function () {

    // ✅ PREVIEW IA para CREATE (NO hay ticket aún)
    Route::post('tickets/checklist/preview-ai', [TicketChecklistController::class, 'previewAi'])
        ->name('tickets.checklist.preview');

    // ✅ Rutas normales (con ticket ya creado)
    Route::prefix('tickets/{ticket}')->group(function () {
        Route::post('checklist/ai-generate', [TicketChecklistController::class, 'generateAi'])->name('tickets.checklist.ai');
        Route::post('checklist/opt-out',     [TicketChecklistController::class, 'optOut'])->name('tickets.checklist.optout');

        Route::post('checklist/items', [TicketChecklistController::class, 'addItem'])->name('tickets.checklist.items.add');
        Route::put('checklist/items/{item}', [TicketChecklistController::class, 'updateItem'])->name('tickets.checklist.items.update');
        Route::delete('checklist/items/{item}', [TicketChecklistController::class, 'deleteItem'])->name('tickets.checklist.items.delete');

        Route::post('checklist/items/{item}/done', [TicketChecklistController::class, 'toggleDone'])->name('tickets.checklist.items.done');
    });
});

Route::middleware(['auth'])->group(function () {
    Route::put('/tickets/{ticket}/checklist-items/{item}', [TicketChecklistController::class, 'toggle'])
        ->name('tickets.checklist-items.toggle');
});

Route::get('/partcontable/activity/all', [ActivityController::class, 'all'])
    ->name('partcontable.activity.all');

    Route::middleware(['auth'])->group(function () {

    // Vault por usuario (dueño)
    Route::get('/confidential/vault/{owner}', [ConfidentialDocsController::class, 'showVault'])
        ->name('confidential.vault');

    // PIN unlock/lock
    Route::post('/confidential/vault/{owner}/unlock', [ConfidentialDocsController::class, 'unlockWithPin'])
        ->name('confidential.vault.unlock');

    Route::post('/confidential/vault/{owner}/lock', [ConfidentialDocsController::class, 'lockPin'])
        ->name('confidential.vault.lock');
  // ✅ CREATE
    Route::get('/confidential/{owner}/create', [ConfidentialDocsController::class, 'create'])
        ->name('confidential.documents.create');

    Route::post('/confidential/{owner}/store', [ConfidentialDocsController::class, 'store'])
        ->name('confidential.documents.store');
    // CRUD docs
    Route::post('/confidential/vault/{owner}/documents', [ConfidentialDocsController::class, 'store'])
        ->name('confidential.documents.store');

    Route::get('/confidential/documents/{doc}/preview', [ConfidentialDocsController::class, 'preview'])
        ->name('confidential.documents.preview');

    Route::get('/confidential/documents/{doc}/download', [ConfidentialDocsController::class, 'download'])
        ->name('confidential.documents.download');

    Route::delete('/confidential/documents/{doc}', [ConfidentialDocsController::class, 'destroy'])
        ->name('confidential.documents.destroy');
});
Route::get('/publications/batch/{batchKey}', [\App\Http\Controllers\PublicationController::class, 'batch'])
  ->name('publications.batch')
  ->middleware('auth');
  
Route::middleware(['auth'])->prefix('admin/wms')->name('admin.wms.')->group(function () {
    Route::get('/analytics', [WmsAnalyticsController::class, 'index'])->name('analytics');
});

Route::match(['get', 'post'], '/webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle']);

Route::middleware(['auth'])->prefix('admin/whatsapp')->group(function () {
    Route::get('/conversations', [WaConversationController::class, 'index'])->name('admin.whatsapp.conversations');
    Route::get('/conversations/{conversation}', [WaConversationController::class, 'show'])->name('admin.whatsapp.conversations.show');
    Route::post('/conversations/{conversation}/take', [WaConversationController::class, 'take'])->name('admin.whatsapp.conversations.take');
    Route::post('/conversations/{conversation}/reply', [WaConversationController::class, 'reply'])->name('admin.whatsapp.conversations.reply');
    Route::post('/conversations/{conversation}/close', [WaConversationController::class, 'close'])->name('admin.whatsapp.conversations.close');
});
Route::prefix('admin/wms')->name('admin.wms.')->middleware('auth')->group(function () {
    Route::get('/analytics-v2', [WmsAnalyticsController::class, 'indexV2'])->name('analytics.v2');
});
Route::prefix('admin/wms')->name('admin.wms.')->middleware('auth')->group(function () {
    Route::get('/picking-v2', [WmsPickingController::class, 'indexV2'])->name('picking.v2');
    Route::get('/picking-scanner-v2', [WmsPickingController::class, 'scannerV2'])->name('picking.scanner.v2');

    Route::post('/picking-v2', [WmsPickingController::class, 'storeV2'])->name('picking.v2.store');
    Route::patch('/picking-v2/{pickWave}', [WmsPickingController::class, 'updateV2'])->name('picking.v2.update');
});

Route::prefix('admin/wms')->name('admin.wms.')->middleware('auth')->group(function () {
    Route::get('/picking-v2', [WmsPickingController::class, 'indexV2'])->name('picking.v2');
    Route::post('/picking-v2', [WmsPickingController::class, 'storeV2'])->name('picking.v2.store');
    Route::patch('/picking-v2/{pickWave}', [WmsPickingController::class, 'updateV2'])->name('picking.v2.update');

    Route::post('/picking-v2/ai-import', [WmsPickingController::class, 'aiImportV2'])->name('picking.v2.ai-import');
});

Route::prefix('admin/wms/fast-flow')
    ->middleware(['auth'])
    ->name('admin.wms.fastflow.')
    ->group(function () {
        Route::get('/', [WmsFastFlowController::class, 'index'])->name('index');
        Route::get('/create', [WmsFastFlowController::class, 'create'])->name('create');
        Route::post('/inbound', [WmsFastFlowController::class, 'storeInbound'])->name('inbound');
        Route::get('/labels/{batchCode}', [WmsFastFlowController::class, 'printLabels'])->name('labels');
        Route::get('/label/{labelCode}', [WmsFastFlowController::class, 'printSingleLabel'])->name('label');
        Route::get('/{batchCode}', [WmsFastFlowController::class, 'show'])->name('show');
});

Route::get('/admin/wms/products/find', function () {
    return view('admin.wms.product-finder');
})->name('admin.wms.products.find.view')->middleware('auth');

Route::get('/admin/wms/products/lookup', [WmsController::class, 'productLookup'])
    ->name('admin.wms.products.lookup')
    ->middleware('auth');


    Route::prefix('admin/wms')->name('admin.wms.')->middleware('auth')->group(function () {
    Route::get('/picking-v2', [WmsPickingController::class, 'indexV2'])->name('picking.v2');

    Route::get('/picking-v2/create', [WmsPickingController::class, 'createV2'])->name('picking.v2.create');
    Route::get('/picking-v2/{pickWave}/edit', [WmsPickingController::class, 'editV2'])->name('picking.v2.edit');

    Route::post('/picking-v2', [WmsPickingController::class, 'storeV2'])->name('picking.v2.store');
    Route::patch('/picking-v2/{pickWave}', [WmsPickingController::class, 'updateV2'])->name('picking.v2.update');

    Route::post('/picking-v2/ai-import', [WmsPickingController::class, 'aiImportV2'])->name('picking.v2.ai-import');
});

Route::prefix('admin/wms')->name('admin.wms.')->middleware('auth')->group(function () {
    Route::get('/audit', [\App\Http\Controllers\Admin\WmsAnalyticsController::class, 'audit'])->name('audit');
    Route::post('/audit/ai', [\App\Http\Controllers\Admin\WmsAnalyticsController::class, 'auditAi'])->name('audit.ai');
    Route::post('/audit/pdf', [\App\Http\Controllers\Admin\WmsAnalyticsController::class, 'auditPdf'])->name('audit.pdf');
});
Route::prefix('admin/wms')->name('admin.wms.')->middleware('auth')->group(function () {
    Route::get('/layout', [WmsLayoutController::class, 'editor'])->name('layout.editor');
    Route::get('/layout/data', [WmsLayoutController::class, 'data'])->name('layout.data');
    Route::get('/layout/available-options', [WmsLayoutController::class, 'availableOptions'])->name('layout.available-options');
    Route::get('/layout/suggest-slot', [WmsLayoutController::class, 'suggestSlot'])->name('layout.suggest-slot');

    Route::post('/layout/cell', [WmsLayoutController::class, 'upsertCell'])->name('layout.cell.upsert');
    Route::post('/layout/delete', [WmsLayoutController::class, 'deleteCell'])->name('layout.delete');
    Route::post('/layout/generate-rack', [WmsLayoutController::class, 'generateRack'])->name('layout.generate-rack');

    Route::get('/heatmap', [WmsLayoutController::class, 'heatmap'])->name('heatmap.view');
    Route::get('/heatmap/data', [WmsLayoutController::class, 'heatmapData'])->name('heatmap.data');
});
Route::prefix('admin/wms')->name('admin.wms.')->middleware('auth')->group(function () {
    Route::get('/shipping', [WmsShippingController::class, 'index'])->name('shipping.index');
    Route::post('/shipping/from-picking/{pickWave}', [WmsShippingController::class, 'storeFromPicking'])->name('shipping.storeFromPicking');

    Route::get('/shipping/{shipment}', [WmsShippingController::class, 'show'])->name('shipping.show');
    Route::get('/shipping/{shipment}/scanner', [WmsShippingController::class, 'scanner'])->name('shipping.scanner');

    Route::patch('/shipping/{shipment}/assignment', [WmsShippingController::class, 'assignment'])->name('shipping.assignment');

    Route::post('/shipping/{shipment}/scan', [WmsShippingController::class, 'scan'])->name('shipping.scan');
    Route::patch('/shipping/{shipment}/close', [WmsShippingController::class, 'close'])->name('shipping.close');
    Route::patch('/shipping/{shipment}/dispatch', [WmsShippingController::class, 'dispatch'])->name('shipping.dispatch');
    Route::patch('/shipping/{shipment}/cancel', [WmsShippingController::class, 'cancel'])->name('shipping.cancel');
});
Route::patch('/admin/wms/shipping/{shipment}/reopen', [\App\Http\Controllers\Admin\WmsShippingController::class, 'reopen'])
    ->name('admin.wms.shipping.reopen');

Route::middleware(['auth'])->prefix('accounting')->name('accounting.')->group(function () {

    // ✅ Dashboard (RENOMBRADO)
    Route::get('dashboard', [CuentasDashboardController::class, 'index'])->name('dashboard');

    // ✅ Alertas
    Route::get('alerts', [AlertsController::class, 'index'])->name('alerts');

    // ✅ CxC (Cuentas por cobrar)
    Route::get('receivables', [ReceivableController::class, 'index'])->name('receivables.index');
    Route::get('receivables/create', [ReceivableController::class, 'create'])->name('receivables.create');
    Route::post('receivables', [ReceivableController::class, 'store'])->name('receivables.store');
    Route::get('receivables/{receivable}', [ReceivableController::class, 'show'])->name('receivables.show');
    Route::get('receivables/{receivable}/edit', [ReceivableController::class, 'edit'])->name('receivables.edit');
    Route::put('receivables/{receivable}', [ReceivableController::class, 'update'])->name('receivables.update');
    Route::delete('receivables/{receivable}', [ReceivableController::class, 'destroy'])->name('receivables.destroy');

    // ✅ CxP (Cuentas por pagar)
    Route::get('payables', [PayableController::class, 'index'])->name('payables.index');
    Route::get('payables/create', [PayableController::class, 'create'])->name('payables.create');
    Route::post('payables', [PayableController::class, 'store'])->name('payables.store');
    Route::get('payables/{payable}', [PayableController::class, 'show'])->name('payables.show');
    Route::get('payables/{payable}/edit', [PayableController::class, 'edit'])->name('payables.edit');
    Route::put('payables/{payable}', [PayableController::class, 'update'])->name('payables.update');
    Route::delete('payables/{payable}', [PayableController::class, 'destroy'])->name('payables.destroy');

    // ✅ Movimientos (abonos/pagos)
    Route::post('movements', [MovementController::class, 'store'])->name('movements.store');
    Route::delete('movements/{movement}', [MovementController::class, 'destroy'])->name('movements.destroy');
});
Route::get('/accounting/reports', [ReportsController::class, 'index'])
    ->name('accounting.reports.index');

    Route::get('/cron/agenda-url-test', function () {
    return URL::temporarySignedRoute(
        'cron.agenda.run',
        now()->addYears(5)
    );
    });



Route::middleware(['auth'])->group(function () {
    Route::get('/projects', [ProjectBoardController::class, 'index'])->name('projects.index');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/admin/catalog/categories/store', [CategoryProductController::class, 'store'])
        ->name('admin.catalog.categories.store');

    Route::get('/admin/catalog', [CatalogItemController::class, 'index'])->name('admin.catalog.index');
    Route::get('/admin/catalog/create', [CatalogItemController::class, 'create'])->name('admin.catalog.create');
    Route::post('/admin/catalog', [CatalogItemController::class, 'store'])->name('admin.catalog.store');
    Route::get('/admin/catalog/{catalogItem}/edit', [CatalogItemController::class, 'edit'])->name('admin.catalog.edit');
    Route::put('/admin/catalog/{catalogItem}', [CatalogItemController::class, 'update'])->name('admin.catalog.update');
});

Route::middleware(['web','auth'])->prefix('admin')->group(function () {
  Route::get('category-products/roots', [CategoryProductController::class, 'roots']);
  Route::get('category-products/{category}/children', [CategoryProductController::class, 'children']);
  Route::get('category-products/{category}', [CategoryProductController::class, 'show']);
  Route::post('category-products', [CategoryProductController::class, 'store']);
});


Route::middleware(['auth'])->prefix('admin/wms/receptions')->group(function () {
    Route::get('/', [WmsReceptionController::class, 'index'])->name('admin.wms.receptions.index');

    Route::get('/create', [WmsReceptionController::class, 'create'])->name('admin.wms.receptions.create');
    Route::post('/', [WmsReceptionController::class, 'store'])->name('admin.wms.receptions.store');

    Route::get('/api/users', [WmsReceptionController::class, 'users'])->name('admin.wms.receptions.users');
    Route::get('/api/products', [WmsReceptionController::class, 'products'])->name('admin.wms.receptions.products');
    Route::post('/api/products/quick-store', [WmsReceptionController::class, 'quickStoreProduct'])->name('admin.wms.receptions.products.quick-store');

    Route::post('/signatures/start', [WmsReceptionController::class, 'startSignatures'])->name('admin.wms.receptions.signatures.start');
    Route::get('/{id}/signature-status', [WmsReceptionController::class, 'signatureStatus'])->name('admin.wms.receptions.signature-status');

    Route::get('/{id}/pdf', [WmsReceptionController::class, 'pdf'])->name('admin.wms.receptions.pdf');
    Route::get('/{id}/labels', [WmsReceptionController::class, 'labels'])->name('admin.wms.receptions.labels');

    Route::get('/{id}', [WmsReceptionController::class, 'show'])->name('admin.wms.receptions.show');
});

/*
|--------------------------------------------------------------------------
| Rutas públicas para QR / firma móvil
|--------------------------------------------------------------------------
*/
Route::get('/reception-sign/{token}', [WmsReceptionController::class, 'mobileSignature'])
    ->name('public.receptions.mobile');

Route::post('/reception-sign/{token}', [WmsReceptionController::class, 'saveMobileSignature'])
    ->name('public.receptions.mobile.save');

Route::get('/reception-sign/{token}/status', [WmsReceptionController::class, 'publicSignatureStatus'])
    ->name('public.receptions.mobile.status');

Route::get('/azure-test', [AzureTestController::class, 'test']);

use App\Http\Controllers\DocumentAiController;
use App\Http\Controllers\PropuestaComercialController;
use App\Http\Controllers\PropuestaComercialMatchController;
use App\Http\Controllers\PropuestaComercialExportController;
use App\Models\DocumentAiRun;

Route::get('/propuestas-comerciales', [PropuestaComercialController::class, 'index'])->name('propuestas-comerciales.index');
Route::get('/propuestas-comerciales/create', [PropuestaComercialController::class, 'create'])->name('propuestas-comerciales.create');
Route::post('/propuestas-comerciales/create-from-run', [PropuestaComercialController::class, 'storeFromRunManual'])->name('propuestas-comerciales.store-from-run-manual');
Route::post('/propuestas-comerciales/{propuestaComercial}/pricing', [PropuestaComercialController::class, 'updatePricing'])->name('propuestas-comerciales.update-pricing');
Route::post('/propuestas-comerciales/{propuestaComercial}/recover-missing', [PropuestaComercialController::class, 'recoverMissing'])->name('propuestas-comerciales.recover-missing');
Route::get('/propuestas-comerciales/{propuestaComercial}', [PropuestaComercialController::class, 'show'])->name('propuestas-comerciales.show');

Route::post('/document-ai/start', [DocumentAiController::class, 'start'])->name('document-ai.start');
Route::get('/document-ai/{run}', [DocumentAiController::class, 'show'])->name('document-ai.show');

Route::post('/propuesta-comercial-items/{item}/suggest', [PropuestaComercialMatchController::class, 'suggest'])->name('propuesta-comercial-items.suggest');
Route::post('/propuestas-comerciales/{propuestaComercial}/suggest-all', [PropuestaComercialMatchController::class, 'suggestAll'])->name('propuestas-comerciales.suggest-all');
Route::post('/propuesta-comercial-items/{item}/matches/{match}/select', [PropuestaComercialMatchController::class, 'select'])->name('propuesta-comercial-items.matches.select');
Route::post('/propuesta-comercial-items/{item}/price', [PropuestaComercialMatchController::class, 'price'])->name('propuesta-comercial-items.price');

Route::get('/propuestas-comerciales/{propuestaComercial}/export/word', [PropuestaComercialExportController::class, 'word'])->name('propuestas-comerciales.export.word');
Route::get('/propuestas-comerciales/{propuestaComercial}/export/excel', [PropuestaComercialExportController::class, 'excel'])->name('propuestas-comerciales.export.excel');

Route::get('/python-ai-test', function () {
    $pythonBin = config('services.python_ai.bin');
    $pythonScript = config('services.python_ai.script');

    return response()->json([
        'python_bin' => $pythonBin,
        'python_script' => $pythonScript,
        'bin_exists' => $pythonBin ? file_exists($pythonBin) : false,
        'script_exists' => $pythonScript ? file_exists($pythonScript) : false,
    ]);
});

Route::get('/document-ai-debug/{id}', function ($id) {
    $run = DocumentAiRun::find($id);

    if (!$run) {
        return response()->json([
            'ok' => false,
            'message' => 'Run no encontrado',
            'id' => $id,
        ], 404);
    }

    return response()->json([
        'ok' => true,
        'run' => [
            'id' => $run->id,
            'licitacion_pdf_id' => $run->licitacion_pdf_id,
            'python_job_id' => $run->python_job_id,
            'filename' => $run->filename,
            'pages_per_chunk' => $run->pages_per_chunk,
            'status' => $run->status,
            'error' => $run->error,
            'result_json' => $run->result_json,
            'structured_json' => $run->structured_json,
            'items_json' => $run->items_json,
            'created_at' => optional($run->created_at)?->toDateTimeString(),
            'updated_at' => optional($run->updated_at)?->toDateTimeString(),
        ],
    ]);
});