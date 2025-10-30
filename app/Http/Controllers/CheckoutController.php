<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Stripe\StripeClient;
use App\Models\CatalogItem;
use App\Models\BillingProfile;
use App\Models\ShippingAddress;
use App\Services\FacturapiWebClient;

class CheckoutController extends Controller
{
    private StripeClient $stripe;
    private FacturapiWebClient $facturapi;
    protected float $threshold;

    public function __construct(FacturapiWebClient $facturapi)
    {
        $secret = config('services.stripe.secret');
        if (blank($secret)) {
            throw new \RuntimeException('Falta STRIPE_SECRET en el .env o en config/services.php');
        }
        $this->stripe    = new StripeClient($secret);
        $this->facturapi = $facturapi;
        $this->threshold = (float) env('FREE_SHIPPING_THRESHOLD', 5000);
    }

    /* ===========================================================
     * PASO 1: “Mi pedido”
     * =========================================================== */
    public function start(Request $request)
    {
        $cart = $this->getCartRows();
        if (empty($cart)) {
            return redirect()->route('web.cart.index')->with('ok', 'Tu carrito está vacío.');
        }

        $subtotal = array_reduce($cart, fn($c,$r)=> $c + (($r['price'] ?? 0) * ($r['qty'] ?? 1)), 0);
        $totals = [
            'subtotal' => $subtotal,
            'envio'    => 0,
            'total'    => $subtotal,
            'count'    => array_sum(array_map(fn($r)=>(int)($r['qty'] ?? 0), $cart)),
        ];

        $user = Auth::user();

        // Dirección seleccionada (si ya existe en sesión)
        $address = null;
        if ($id = session('checkout.address_id')) {
            $address = ShippingAddress::where('user_id', $user->id)->find($id);
        }

        // Listado de direcciones guardadas
        $addresses = ShippingAddress::where('user_id', $user->id)
            ->orderByDesc('is_default')->orderByDesc('id')->get();

        // Pref de factura
        $hasBilling  = $user->billingProfiles()->exists();
        $invoicePref = $hasBilling ? true : null;
        if (session()->has('checkout.invoice_required')) {
            $invoicePref = (bool) session('checkout.invoice_required');
        }

        return view('checkout.start', compact('cart', 'totals', 'user', 'address', 'addresses', 'invoicePref'));
    }

    /* ===========================================================
     * PASO 2: FACTURACIÓN — Listado + Modal (RFC -> Formulario)
     * =========================================================== */

    /** Página/step con listado (la UI abre el modal en 2 pasos). */
    public function invoice()
    {
        $user = Auth::user();

        $profiles = BillingProfile::where('user_id', $user->id)
            ->orderByDesc('is_default')->orderByDesc('id')->get();

        $cart = $this->getCartRows();
        $subtotal = array_reduce($cart, fn($c,$r)=> $c + ($r['price']*$r['qty']), 0);

        return view('checkout.invoice_select', [
            'profiles'        => $profiles,                   // <-- listado para elegir o agregar otro
            'subtotal'        => $subtotal,
            'shipping'        => session('checkout.shipping', null),
            'address'         => $this->currentAddress(),
            'usoCfdiOptions'  => $this->usoCfdiOptions(),     // code => label
            'regimenOptions'  => $this->regimenOptions(),     // code => label
        ]);
    }

    /** POST (AJAX): validar RFC – Paso 1 del modal. */
    public function invoiceValidateRFC(Request $request)
    {
        $rfc = strtoupper(trim((string)$request->input('rfc')));
        $pattern = '/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i'; // 12 PM / 13 PF (permite genéricos vía step 2)
        if (!preg_match($pattern, $rfc) && !in_array($rfc, ['XAXX010101000','XEXX010101000'], true)) {
            return response()->json(['ok'=>false,'message'=>'RFC inválido. Verifica tu constancia.'], 422);
        }
        $tipo = strlen($rfc) === 12 ? 'PM' : 'PF';
        return response()->json(['ok'=>true,'rfc'=>$rfc,'tipo'=>$tipo]);
    }

    /** POST (AJAX o clásico): guardar perfil – Paso 2 del modal. */
    public function invoiceStore(Request $request)
    {
        $user = Auth::user();

        // Acepta cp/zip y normaliza
        if ($request->filled('zip') && !$request->filled('cp')) {
            $request->merge(['cp' => $request->input('zip')]);
        }

        $data = $request->validate([
            'rfc'       => ['required','string','max:13','regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i'],
            'razon'     => ['required','string','max:190'], // se guarda tal cual lo escribió el usuario
            'uso_cfdi'  => ['required', Rule::in(array_keys($this->usoCfdiOptions()))],
            'regimen'   => ['required', Rule::in(array_keys($this->regimenOptions()))],
            'contacto'  => ['nullable','string','max:120'],
            'telefono'  => ['nullable','string','max:30'],
            'email'     => ['nullable','email','max:190'],
            'direccion' => ['nullable','string','max:190'],
            'cp'        => ['required','string','max:10'],
            'colonia'   => ['nullable','string','max:120'],
            'estado'    => ['nullable','string','max:120'],
        ], [], [
            'razon'   => 'razón social',
            'cp'      => 'código postal',
            'regimen' => 'régimen fiscal',
            'uso_cfdi'=> 'uso de CFDI',
        ]);

        $profile               = new BillingProfile();
        $profile->user_id      = $user->id;
        $profile->rfc          = strtoupper($data['rfc']);
        $profile->razon_social = $data['razon']; // sin cambios
        $profile->uso_cfdi     = $data['uso_cfdi'];  // CÓDIGO
        $profile->regimen      = $data['regimen'];   // CÓDIGO
        $profile->contacto     = $data['contacto'] ?? null;
        $profile->telefono     = $data['telefono'] ?? null;
        $profile->email        = $data['email'] ?? null;
        $profile->direccion    = $data['direccion'] ?? null;
        $profile->zip          = preg_replace('/\D+/', '', (string) $data['cp']); // solo dígitos
        $profile->colonia      = $data['colonia'] ?? null;
        $profile->estado       = $data['estado'] ?? null;
        $profile->metodo_pago  = 'Tarjeta';
        $profile->is_default   = !BillingProfile::where('user_id',$user->id)->exists();
        $profile->save();

        session([
            'checkout.billing_profile_id' => $profile->id,
            'checkout.invoice_required'   => true
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'ok'       => true,
                'profile'  => $profile,
                'redirect' => route('checkout.shipping'),
            ]);
        }

        return redirect()->route('checkout.shipping')->with('ok', 'Datos de facturación guardados.');
    }

    /** POST: seleccionar perfil existente y seguir al envío. */
    public function invoiceSelect(Request $request)
    {
        $request->validate(['id'=>'required|integer']);
        $profile = BillingProfile::where('user_id', Auth::id())->findOrFail($request->id);

        session(['checkout.billing_profile_id' => $profile->id,
                 'checkout.invoice_required'   => true]);

        return redirect()->route('checkout.shipping');
    }

    /** DELETE: eliminar perfil del usuario. */
    public function invoiceDelete(Request $request)
    {
        $request->validate(['id'=>'required|integer']);
        BillingProfile::where('user_id', Auth::id())->where('id', $request->id)->delete();

        if (session('checkout.billing_profile_id') == $request->id) {
            session()->forget('checkout.billing_profile_id');
        }
        return back()->with('ok','Perfil eliminado.');
    }

    /** Saltar facturación solo para ESTA compra (bandera de sesión). */
    public function invoiceSkip(Request $request)
    {
        session(['checkout.invoice_required' => false]);
        return response()->json(['ok' => true]);
    }

    /* ===========================================================
     * DIRECCIÓN: guardar desde el modal (AJAX)
     * =========================================================== */
    public function addressStore(Request $req)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['error'=>'No autenticado'], 401);

        // Normaliza claves del front a las de BD
        $map = [
            'nombre_recibe' => 'contact_name',
            'telefono'      => 'phone',
            'calle'         => 'street',
            'num_ext'       => 'ext_number',
            'num_int'       => 'int_number',
            'colonia'       => 'colony',
            'cp'            => 'postal_code',
            'estado'        => 'state',
            'municipio'     => 'municipality',
        ];
        foreach ($map as $from => $to) {
            if ($req->filled($from) && !$req->filled($to)) {
                $req->merge([$to => $req->input($from)]);
            }
        }
        if ($req->filled('entre_calles') && !$req->filled('between_street_1') && !$req->filled('between_street_2')) {
            $parts = preg_split('/\s+y\s+|,|\/|\|/i', $req->input('entre_calles'));
            $req->merge([
                'between_street_1' => trim($parts[0] ?? ''),
                'between_street_2' => trim($parts[1] ?? ''),
            ]);
        }

        $data = $req->validate([
            'contact_name'     => 'nullable|string|max:120',
            'phone'            => 'nullable|string|max:30',
            'street'           => 'required|string|max:180',
            'ext_number'       => 'required|string|max:30',
            'int_number'       => 'nullable|string|max:30',
            'colony'           => 'required|string|max:120',
            'postal_code'      => 'required|string|max:10',
            'state'            => 'required|string|max:120',
            'municipality'     => 'required|string|max:120',
            'between_street_1' => 'nullable|string|max:180',
            'between_street_2' => 'nullable|string|max:180',
            'references'       => 'nullable|string|max:1200',
        ]);

        $isFirst = !$user->shippingAddresses()->exists();
        $data['is_default'] = $isFirst;
        $data['user_id']    = $user->id;

        $addr = ShippingAddress::create($data);

        session(['checkout.address_id' => $addr->id]);

        return response()->json(['ok' => true, 'addr' => $addr]);
    }

    /* ===========================================================
     * Seleccionar dirección guardada (AJAX)
     * =========================================================== */
    public function addressSelect(Request $request)
    {
        $data = $request->validate([
            'id' => ['required','integer'],
        ]);

        $addr = ShippingAddress::where('user_id', Auth::id())->find($data['id']);
        if (!$addr) {
            return response()->json(['ok'=>false,'error'=>'Dirección no encontrada'], 404);
        }

        // Persistir selección
        session([
            'checkout.address_id' => $addr->id,
            'checkout.address'    => [
                'id'               => $addr->id,
                'street'           => $addr->street ?? $addr->calle,
                'ext_number'       => $addr->ext_number ?? $addr->num_ext,
                'int_number'       => $addr->int_number ?? $addr->num_int,
                'colony'           => $addr->colony ?? $addr->colonia,
                'postal_code'      => $addr->postal_code ?? $addr->cp,
                'municipality'     => $addr->municipality ?? $addr->municipio,
                'state'            => $addr->state ?? $addr->estado,
                'between_street_1' => $addr->between_street_1 ?? null,
                'between_street_2' => $addr->between_street_2 ?? null,
                'references'       => $addr->references ?? $addr->referencias,
                'contact_name'     => $addr->contact_name ?? $addr->nombre_recibe,
                'phone'            => $addr->phone ?? $addr->telefono,
            ],
        ]);

        // Si cambió la dirección, descartamos selección de envío previa
        session()->forget('checkout.shipping');

        return response()->json(['ok'=>true]);
    }

    /* ===========================================================
     * CP lookup (Copomex si tienes token)
     * =========================================================== */
    public function cpLookup(Request $req)
    {
        $cp = trim((string)$req->query('cp'));
        if ($cp === '' || !preg_match('/^\d{5}$/', $cp)) {
            return response()->json(['error' => 'CP inválido'], 422);
        }

        $token = config('services.copomex.token'); // COPOMEX_TOKEN
        if ($token) {
            try {
                $r = Http::timeout(8)->get(
                    'https://api.copomex.com/query/info_cp/' . $cp,
                    ['token' => $token, 'type' => 'simplified']
                );

                if ($r->successful() && ($data = $r->json())) {
                    return response()->json([
                        'cp'           => $cp,
                        'state'        => $data['response'][ 'estado' ] ?? null,
                        'municipality' => $data['response'][ 'municipio' ] ?? null,
                        'colonies'     => $data['response'][ 'asentamiento' ] ?? [],
                    ]);
                }
            } catch (\Throwable $e) {
                // fallback
            }
        }

        return response()->json([
            'cp' => $cp, 'state' => null, 'municipality' => null, 'colonies' => [],
        ]);
    }

    /* ===========================================================
     * PASO 3: Envío
     * =========================================================== */
    public function shipping(Request $req)
    {
        $cart = $this->getCartRows();
        if (empty($cart)) {
            return redirect()->route('web.cart.index')->with('ok','Tu carrito está vacío.');
        }

        $subtotal  = array_reduce($cart, fn($c,$r)=> $c + ($r['price']*$r['qty']), 0);
        $threshold = $this->threshold;

        // Dirección actual (si hay)
        $address = null;
        if ($id = session('checkout.address_id')) {
            $address = ShippingAddress::where('user_id', Auth::id())->find($id);
        }

        // 1) Todas las opciones normalizadas dejadas por el cotizador (Skydropx) en sesión
        // Estructura esperada por opción:
        // ['code'=>'dhl:express', 'carrier'=>'DHL', 'name'=>'DHL Express', 'service'=>'Express', 'eta'=>'2-4 días', 'price'=>289.99]
        $all = session('shipping.options', []);

        // Fallback de emergencia para no dejar la vista vacía
        if (empty($all)) {
            $all = [
                ['code'=>'dhl-express',     'name'=>'DHL',     'service'=>'Express',  'eta'=>'2 a 5 días hábiles', 'price'=>329.57],
                ['code'=>'fedex-ground',    'name'=>'FedEx',   'service'=>'Ground',   'eta'=>'2 a 4 días hábiles', 'price'=>289.99],
                ['code'=>'estafeta-econo',  'name'=>'Estafeta','service'=>'Económico','eta'=>'3 a 6 días hábiles', 'price'=>249.00],
            ];
        }

        // 2) Mostrar al cliente: excluir $0 (esas son para "store_pays" cuando hay umbral)
        $carriers = array_values(array_filter($all, fn($o) => (float)($o['price'] ?? 0) > 0));

        // Guarda la versión mostrada para validar después el POST
        session(['shipping.options_norm' => $carriers]);

        // 3) Si supera el umbral, autoselecciona la más barata y manda directo a pago (store pays)
        if ($subtotal >= $threshold && !empty($carriers)) {
            usort($carriers, fn($a,$b) => (float)$a['price'] <=> (float)$b['price']);
            $best = $carriers[0];

            session(['checkout.shipping' => [
                'code'        => $best['code'],
                'name'        => $best['name']    ?? ($best['carrier'] ?? 'Paquetería'),
                'service'     => $best['service'] ?? null,
                'eta'         => $best['eta']     ?? null,
                'price'       => 0.0, // el cliente no paga
                'store_pays'  => true,
                'carrier_cost'=> (float)($best['price'] ?? 0),
                'auto_applied'=> true,
            ]]);

            return redirect()
                ->route('checkout.payment')
                ->with('ok', 'Envío gratis aplicado (cubierto por la tienda con la paquetería más económica).');
        }

        // 4) Si no supera el umbral, mostrar para que el cliente elija y pague su envío
        $selected = session('checkout.shipping', [
            'code'=>null,'price'=>0.0,'name'=>null,'eta'=>null,'service'=>null
        ]);

        return view('checkout.shipping', compact('cart','subtotal','address','carriers','selected'));
    }

    public function shippingSelect(Request $req)
    {
        $data = $req->validate([
            'code'    => 'required|string',
            'price'   => 'nullable', // ignorado, se recalcula
            'name'    => 'nullable',
            'service' => 'nullable',
            'eta'     => 'nullable',
        ]);

        // Buscar la opción válida entre las que se mostraron
        $norm = collect(session('shipping.options_norm', []));
        $opt  = $norm->firstWhere('code', $data['code']);

        // Si no está en las mostradas, intentar en todas (por si hubo refresco)
        if (!$opt) {
            $all = collect(session('shipping.options', []));
            $opt = $all->firstWhere('code', $data['code']);
        }

        if (!$opt) {
            return back()->withErrors(['code'=>'Opción de envío no válida o expirada.']);
        }

        // Recalcular umbral
        $cart = $this->getCartRows();
        $subtotal  = array_reduce($cart, fn($c,$r)=> $c + ($r['price']*$r['qty']), 0);
        $threshold = $this->threshold;

        if ($subtotal >= $threshold) {
            // Cliente no paga: price 0, pero guardamos el costo real para administración
            session(['checkout.shipping' => [
                'code'        => $opt['code'],
                'name'        => $opt['name']    ?? ($opt['carrier'] ?? 'Paquetería'),
                'service'     => $opt['service'] ?? null,
                'eta'         => $opt['eta']     ?? null,
                'price'       => 0.0,
                'store_pays'  => true,
                'carrier_cost'=> (float)($opt['price'] ?? 0),
                'auto_applied'=> false, // aquí fue elección del cliente
            ]]);
        } else {
            // Cliente sí paga el precio validado del cotizador
            session(['checkout.shipping' => [
                'code'    => $opt['code'],
                'name'    => $opt['name']    ?? ($opt['carrier'] ?? 'Paquetería'),
                'service' => $opt['service'] ?? null,
                'eta'     => $opt['eta']     ?? null,
                'price'   => (float)($opt['price'] ?? 0),
            ]]);
        }

        return $req->wantsJson()
            ? response()->json(['ok'=>true])
            : redirect()->route('checkout.payment');
    }

    /* ===========================================================
     * PASO 4: Pago (UI)
     * =========================================================== */
    public function payment(Request $req)
    {
        $cart = $this->getCartRows();
        if (empty($cart)) return redirect()->route('web.cart.index')->with('ok','Tu carrito está vacío.');

        $subtotal = array_reduce($cart, fn($c,$r)=> $c + ($r['price']*$r['qty']), 0);

        $shipping = session('checkout.shipping', ['price'=>0,'name'=>null,'code'=>null,'eta'=>null,'service'=>null]);
        $total    = $subtotal + (float)($shipping['price'] ?? 0);

        $address = null;
        if ($id = session('checkout.address_id')) {
            $address = ShippingAddress::where('user_id', Auth::id())->find($id);
        }

        return view('checkout.payment', compact('cart','subtotal','shipping','total','address'));
    }

    /* ===========================================================
     * Stripe: Buy now / Carrito (con envío en Stripe)
     * =========================================================== */
    public function checkoutItem(Request $req, $item)
    {
        try {
            $model = CatalogItem::query()->findOrFail($item);
            $qty   = max(1, (int)$req->input('qty', 1));
            $price = $model->sale_price ?? $model->price ?? 0;
            if ($price <= 0) return response()->json(['error' => 'Precio inválido.'], 400);

            $email = Auth::user()->email ?? null;
            $uid   = Auth::id();

            $successUrl = config('services.stripe.success_url') ?: route('checkout.success', ['session_id' => '{CHECKOUT_SESSION_ID}']);
            $cancelUrl  = config('services.stripe.cancel_url')  ?: route('checkout.cancel');

            // Construye la(s) opción(es) de envío para Stripe desde la sesión
            $shippingOptions = $this->buildStripeShippingOptions();

            $params = [
                'mode'                   => 'payment',
                'payment_method_types'   => ['card'],
                'locale'                 => 'es-419',
                'customer_email'         => $email,
                'success_url'            => $successUrl,
                'cancel_url'             => $cancelUrl,
                'metadata'               => [
                    'type'            => 'buy_now',
                    'catalog_item_id' => (string)$model->id,
                    'user_id'         => (string)($uid ?? ''),
                    'qty'             => (string)$qty,
                ],
                'line_items' => [[
                    'quantity'   => $qty,
                    'price_data' => [
                        'currency'    => 'mxn',
                        'unit_amount' => (int)round($price * 100),
                        'product_data'=> [
                            'name'        => $model->name,
                            'description' => 'SKU: ' . ($model->sku ?? '—'),
                            'images'      => array_filter([$model->image_url]),
                        ],
                    ],
                ]],
                'shipping_address_collection' => ['allowed_countries' => ['MX']],
                'allow_promotion_codes'       => true,
                'automatic_tax'               => ['enabled' => false],
            ];

            if (!empty($shippingOptions)) {
                $params['shipping_options'] = $shippingOptions;
            }

            $session = $this->stripe->checkout->sessions->create($params);

            return response()->json(['url' => $session->url], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Producto no encontrado.'], 404);
        } catch (\Throwable $e) {
            Log::error('Stripe checkoutItem error: ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['error' => 'No se pudo crear la sesión de pago.'], 500);
        }
    }

    public function checkoutCart(Request $req)
    {
        try {
            $cart = $this->getCartRows();
            if (empty($cart)) return response()->json(['error' => 'Tu carrito está vacío.'], 400);

            $lineItems = [];
            $metadataItems = [];
            foreach ($cart as $row) {
                $qty   = max(1, (int)($row['qty'] ?? 1));
                $price = (float)($row['price'] ?? 0);
                if ($price <= 0) continue;

                $lineItems[] = [
                    'quantity'   => $qty,
                    'price_data' => [
                        'currency'    => 'mxn',
                        'unit_amount' => (int)round($price * 100),
                        'product_data'=> [
                            'name'        => $row['name'] ?? 'Producto',
                            'description' => 'SKU: ' . ($row['sku'] ?? '—'),
                            'images'      => array_filter([$row['image'] ?? null]),
                        ],
                    ],
                ];

                $metadataItems[] = ($row['id'] ?? 'X') . 'x' . $qty;
            }

            $email = Auth::user()->email ?? null;
            $uid   = Auth::id();

            $successUrl = config('services.stripe.success_url') ?: route('checkout.success', ['session_id' => '{CHECKOUT_SESSION_ID}']);
            $cancelUrl  = config('services.stripe.cancel_url')  ?: route('checkout.cancel');

            // Construye la(s) opción(es) de envío para Stripe desde la sesión
            $shippingOptions = $this->buildStripeShippingOptions();

            $params = [
                'mode'                        => 'payment',
                'payment_method_types'        => ['card'],
                'locale'                      => 'es-419',
                'customer_email'              => $email,
                'success_url'                 => $successUrl,
                'cancel_url'                  => $cancelUrl,
                'metadata'                    => [
                    'type'    => 'cart',
                    'user_id' => (string)($uid ?? ''),
                    'items'   => implode(',', $metadataItems),
                ],
                'line_items'                  => $lineItems,
                'allow_promotion_codes'       => true,
                'shipping_address_collection' => ['allowed_countries' => ['MX']],
                'automatic_tax'               => ['enabled' => false],
            ];

            if (!empty($shippingOptions)) {
                $params['shipping_options'] = $shippingOptions;
            }

            $session = $this->stripe->checkout->sessions->create($params);

            return response()->json(['url' => $session->url], 200);
        } catch (\Throwable $e) {
            Log::error('Stripe checkoutCart error: ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['error' => 'No se pudo crear la sesión de pago del carrito.'], 500);
        }
    }

    /* ===========================================================
     * SUCCESS: confirmar pago, timbrar CFDI y limpiar sesión
     * =========================================================== */
    public function success(Request $req)
    {
        $sessionId = (string) $req->query('session_id');
        $invoice   = null;

        if ($sessionId) {
            try {
                // 1) Confirmar pago en Stripe
                $session = $this->stripe->checkout->sessions->retrieve($sessionId);
                $paid    = ($session->payment_status ?? null) === 'paid';

                if ($paid && (bool) session('checkout.invoice_required', false)) {
                    // 2) Armar datos del receptor + partidas
                    $user    = Auth::user();
                    $profile = null;
                    if ($pid = session('checkout.billing_profile_id')) {
                        $profile = BillingProfile::where('user_id', $user->id)->find($pid);
                    }

                    // === Cliente / Receptor (CFDI v4)
                    $isGeneric = !$profile || empty($profile->rfc);
                    $rfc       = $isGeneric ? 'XAXX010101000' : strtoupper(trim((string) $profile->rfc));

                    // Respetamos el régimen del usuario si es válido para su tipo; si no, normalizamos.
                    $taxSystem = $this->normalizeTaxSystemForRfc($rfc, $isGeneric ? '616' : ($profile->regimen ?? null));

                    // Nombre: exacto de BD, pero normalizado SOLO para timbrar.
                    $rawName   = $isGeneric
                        ? 'PUBLICO EN GENERAL'
                        : ($profile->razon_social ?: ($user->name ?? 'CLIENTE'));
                    $legalName = $this->satLegalName($rawName, $rfc);

                    $customer = [
                        'legal_name' => $legalName,
                        'tax_id'     => $rfc,
                        'tax_system' => $taxSystem,
                        'address'    => ['zip' => $isGeneric
                            ? '64000'
                            : preg_replace('/\D+/', '', (string)($profile->zip ?? '64000'))],
                    ];
                    $email = $profile?->email ?: ($user->email ?? null);
                    if (!empty($email)) {
                        $customer['email'] = $email;
                    }

                    // === Items (precios con IVA incluido 16%)
                    $cart  = $this->getCartRows();
                    $items = [];
                    foreach ($cart as $row) {
                        $cid   = $row['id'] ?? null;
                        $qty   = max(1, (float)($row['qty'] ?? 1));
                        $price = round((float)($row['price'] ?? 0), 2);

                        $product_key = '01010101';
                        $unit_key    = 'H87';
                        $desc        = (string)($row['name'] ?? 'Producto');

                        if ($cid && ($p = CatalogItem::find($cid))) {
                            $product_key = $p->clave_prod_serv ?: $product_key;
                            $unit_key    = $p->clave_unidad   ?: $unit_key;
                            $desc        = $p->name ?: $desc;
                        }

                        $items[] = [
                            'product'  => [
                                'description'  => $desc,
                                'product_key'  => $product_key,
                                'unit_key'     => $unit_key,
                                'price'        => $price,
                                'tax_included' => true,
                                'taxes'        => [[ 'type'=>'IVA', 'rate'=>0.16 ]],
                            ],
                            'quantity' => $qty,
                        ];
                    }

                    // Envío (solo si lo pagó el cliente)
                    $shipping = session('checkout.shipping', []);
                    $shipPrice = (float)($shipping['price'] ?? 0);
                    $storePays = (bool)($shipping['store_pays'] ?? false);
                    if ($shipPrice > 0 && !$storePays) {
                        $items[] = [
                            'product'  => [
                                'description'  => 'Envío',
                                'product_key'  => '78101800', // Mensajería
                                'unit_key'     => 'E48',
                                'price'        => round($shipPrice, 2),
                                'tax_included' => true,
                                'taxes'        => [[ 'type'=>'IVA', 'rate'=>0.16 ]],
                            ],
                            'quantity' => 1,
                        ];
                    }

                    // === Opciones de timbrado
                    $opts = [
                        'payment_method' => config('services.facturaapi_web.metodo', 'PUE'),
                        'payment_form'   => config('services.facturaapi_web.forma',  '04'), // 04=TC, 28=TD, 03=Transfer
                        'use'            => $isGeneric
                            ? 'S01'
                            : (string)($profile->uso_cfdi ?? config('services.facturaapi_web.uso', 'G03')),
                        'series'         => (string) config('services.facturaapi_web.series', 'F'),
                    ];

                    // Información Global obligatoria si es Público en General (XAXX…)
                    if ($rfc === 'XAXX010101000') {
                        $opts['global'] = [
                            'periodicity' => 'month',
                            'months'      => [date('m')],
                            'year'        => (int) date('Y'),
                        ];
                    }

                    // 3) Timbrar + enviar por correo
                    $invoice = $this->facturapi->createInvoice($customer, $items, $opts);
                    if (!empty($invoice['id'])) {
                        $this->facturapi->sendInvoiceEmail($invoice['id']);
                        // Guarda datos mínimos para la vista
                        session(['checkout.invoice' => $invoice]);
                        Session::flash('ok', '¡Pago recibido! Tu factura fue timbrada y enviada a tu correo.');
                    }

                    // 4) Limpieza
                    Session::forget([
                        'cart',
                        'checkout.address_id',
                        'checkout.address',
                        'checkout.billing_profile_id',
                        'checkout.invoice_required',
                        'checkout.shipping',
                        'shipping.options',
                        'shipping.options_norm',
                    ]);
                } else {
                    Session::flash('ok', 'Pago confirmado.');
                }
            } catch (\Throwable $e) {
                Log::error('Checkout success error: '.$e->getMessage(), ['session_id'=>$sessionId]);
                Session::flash('error',
                    'Pago realizado. Hubo un problema al generar/enviar la factura (validación SAT). '.
                    'Si necesitas el CFDI de este pago, contáctanos.');
            }
        }

        return view('checkout.success', compact('sessionId', 'invoice'));
    }

    public function cancel()
    {
        return redirect()->route('web.cart.index')->with('ok', 'Pago cancelado.');
    }

    /* ===========================================================
     * DESCARGAS/RE-ENVÍO DE CFDI (para la página de éxito)
     * =========================================================== */
    public function invoicePdf(Request $req, string $invoiceId)
    {
        try {
            $pdf = $this->facturapi->downloadPdf($invoiceId);
            if (!$pdf) abort(404, 'No disponible.');
            $disposition = $req->boolean('dl') ? 'attachment' : 'inline';
            return response($pdf, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => $disposition . '; filename="factura-'.$invoiceId.'.pdf"',
            ]);
        } catch (\Throwable $e) {
            Log::warning('invoicePdf error: '.$e->getMessage(), ['invoice_id'=>$invoiceId]);
            abort(404, 'No disponible.');
        }
    }

    public function invoiceXml(string $invoiceId)
    {
        try {
            $xml = $this->facturapi->downloadXml($invoiceId);
            if (!$xml) abort(404, 'No disponible.');
            return response($xml, 200, [
                'Content-Type'        => 'application/xml; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="factura-'.$invoiceId.'.xml"',
            ]);
        } catch (\Throwable $e) {
            Log::warning('invoiceXml error: '.$e->getMessage(), ['invoice_id'=>$invoiceId]);
            abort(404, 'No disponible.');
        }
    }

    public function invoiceResendEmail(string $invoiceId)
    {
        try {
            $this->facturapi->sendInvoiceEmail($invoiceId);
            return back()->with('ok', 'Factura re-enviada por correo.');
        } catch (\Throwable $e) {
            Log::warning('invoiceResendEmail error: '.$e->getMessage(), ['invoice_id'=>$invoiceId]);
            return back()->with('error', 'No se pudo re-enviar la factura por correo.');
        }
    }

    /* ===========================================================
     * Helpers
     * =========================================================== */
    private function currentAddress()
    {
        $id = session('checkout.address_id');
        return $id ? ShippingAddress::where('user_id', Auth::id())->find($id) : null;
    }

    private function usoCfdiOptions(): array
    {
        return [
            'G01'=>'Adquisición de mercancías',
            'G02'=>'Devoluciones, descuentos o bonificaciones',
            'G03'=>'Gastos en general',
            'I01'=>'Construcciones',
            'I02'=>'Mobiliario y equipo de oficina por inversiones',
            'I03'=>'Equipo de transporte',
            'I04'=>'Equipo de computo y accesorios',
            'I05'=>'Dados, troqueles, moldes, matrices y herramental',
            'I06'=>'Comunicaciones telefónicas',
            'I07'=>'Comunicaciones satelitales',
            'I08'=>'Otra maquinaria y equipo',
            'D01'=>'Honorarios médicos, dentales y gastos hospitalarios',
            'D02'=>'Gastos médicos por incapacidad o discapacidad',
            'D03'=>'Gastos funerales',
            'D04'=>'Donativos',
            'D05'=>'Intereses reales por créditos hipotecarios (casa habitación)',
            'D06'=>'Aportaciones voluntarias al SAR',
            'D07'=>'Primas por seguros de gastos médicos',
            'D08'=>'Gastos de transportación escolar obligatoria',
            'D09'=>'Depósitos para el ahorro, planes de pensiones',
            'D10'=>'Pagos por servicios educativos (colegiaturas)',
            'CP01'=>'Pagos',
            'CN01'=>'Nómina',
            'S01' =>'Sin efectos fiscales',
        ];
    }

    private function regimenOptions(): array
    {
        return [
            '601'=>'General de Ley Personas Morales',
            '603'=>'Personas Morales con Fines no Lucrativos',
            '606'=>'Arrendamiento',
            '612'=>'Personas Físicas con Actividades Empresariales y Profesionales',
            '620'=>'Sociedades Cooperativas de Producción que optan por diferir sus ingresos',
            '621'=>'Incorporación Fiscal',
            '622'=>'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',
            '623'=>'Opcional para Grupos de Sociedades',
            '624'=>'Coordinados',
            '625'=>'Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas',
            '626'=>'Régimen Simplificado de Confianza',
        ];
    }

    private function getCartRows(): array
    {
        $raw = session('cart', []);

        if (is_array($raw) && isset($raw[0]) && is_array($raw[0]) && array_key_exists('price', $raw[0])) {
            return $raw;
        }

        $rows = [];
        foreach ((array)$raw as $row) {
            $qty = (int)($row['qty'] ?? 1);

            if (isset($row['item']) && is_object($row['item'])) {
                /** @var CatalogItem $p */
                $p = $row['item'];
                $rows[] = [
                    'id'    => $p->id,
                    'name'  => $p->name,
                    'sku'   => $p->sku ?? null,
                    'price' => (float)($p->sale_price ?? $p->price ?? 0),
                    'qty'   => max(1, $qty),
                    'image' => $p->image_url ?? null,
                ];
                continue;
            }

            if (isset($row['item']) && is_numeric($row['item'])) {
                $p = CatalogItem::find($row['item']);
                if ($p) {
                    $rows[] = [
                        'id'    => $p->id,
                        'name'  => $p->name,
                        'sku'   => $p->sku ?? null,
                        'price' => (float)($p->sale_price ?? $p->price ?? 0),
                        'qty'   => max(1, $qty),
                        'image' => $p->image_url ?? null,
                    ];
                }
                continue;
            }

            if (isset($row['id']) || isset($row['price'])) {
                $rows[] = [
                    'id'    => $row['id']    ?? null,
                    'name'  => $row['name']  ?? 'Producto',
                    'sku'   => $row['sku']   ?? null,
                    'price' => (float)($row['price'] ?? 0),
                    'qty'   => max(1, (int)($row['qty'] ?? 1)),
                    'image' => $row['image'] ?? null,
                ];
            }
        }

        return $rows;
    }

    // ===== Helpers de régimen fiscal vs tipo de RFC =====
    private function pmRegimens(): array {
        // Regímenes típicos de PM
        return ['601','603','620','622','623','624','626'];
    }

    private function pfRegimens(): array {
        // Regímenes típicos de PF
        return ['605','606','607','608','610','612','614','615','616','621','625','626'];
    }

    /**
     * Convierte la selección de envío (guardada en sesión) en shipping_options para Stripe Checkout.
     * Incluye el campo obligatorio "type" => "fixed_amount".
     */
    private function buildStripeShippingOptions(): array
    {
        $s = session('checkout.shipping');
        if (!$s || !array_key_exists('price', (array)$s)) {
            return [];
        }

        // Monto que pagará el cliente (centavos MXN)
        $amount = (int) round(max(0, (float) ($s['price'] ?? 0)) * 100);

        // Texto que verá el cliente en Stripe
        $display = ($amount === 0)
            ? 'Envío gratis'
            : trim(($s['name'] ?? 'Envío') . (isset($s['service']) && $s['service'] ? ' — ' . $s['service'] : ''));

        $rate = [
            'shipping_rate_data' => [
                'type'         => 'fixed_amount', // <-- OBLIGATORIO
                'fixed_amount' => ['amount' => $amount, 'currency' => 'mxn'],
                'display_name' => $display,
            ],
        ];

        // (Opcional) Estimación de entrega: intenta parsear "2 a 5 días"
        if (!empty($s['eta']) && preg_match('/(\d+).*?(\d+)/', (string) $s['eta'], $m)) {
            $min = max(1, (int) $m[1]);
            $max = max($min, (int) ($m[2] ?? $m[1]));
            $rate['shipping_rate_data']['delivery_estimate'] = [
                'minimum' => ['unit' => 'business_day', 'value' => $min],
                'maximum' => ['unit' => 'business_day', 'value' => $max],
            ];
        }

        return [$rate];
    }

    /**
     * Corrige/normaliza el tax_system (régimen) con base en el RFC.
     * - 12 chars => PM (default 601)
     * - 13 chars => PF (default 612)
     * - Genéricos XAXX/XEXX => 616
     * Respeta el valor del usuario si es compatible; solo ajusta si es inválido.
     */
    private function normalizeTaxSystemForRfc(string $rfc, ?string $regimen): string {
        $rfc = strtoupper(trim($rfc));
        // Genéricos
        if (in_array($rfc, ['XAXX010101000','XEXX010101000'], true)) {
            return '616';
        }
        $len = strlen($rfc);
        $regimen = $regimen ? (string)$regimen : '';
        if ($len === 12) { // PM
            return in_array($regimen, $this->pmRegimens(), true) ? $regimen : '601';
        }
        if ($len === 13) { // PF
            return in_array($regimen, $this->pfRegimens(), true) ? $regimen : '612';
        }
        // Si el RFC no cuadra, por seguridad manda PF 612
        return '612';
    }

    /**
     * Normaliza la razón social SOLO para timbrar (no toca BD):
     * - Mayúsculas, sin acentos ni caracteres raros (conserva Ñ y &)
     * - Quita denominaciones sociales comunes (S.A. de C.V., S. de R.L., etc.)
     * - Elimina dobles espacios
     * - Si RFC genérico, fuerza "PUBLICO EN GENERAL"
     */
    private function satLegalName(string $rawName, string $taxId): string
    {
        $taxId = strtoupper(trim($taxId));
        if (in_array($taxId, ['XAXX010101000', 'XEXX010101000'], true)) {
            return 'PUBLICO EN GENERAL';
        }

        $u = mb_strtoupper(trim($rawName), 'UTF-8');

        // Quitar acentos (conservando Ñ)
        $map = [
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ä'=>'A','Ë'=>'E','Ï'=>'I','Ö'=>'O','Ü'=>'U',
            'á'=>'A','é'=>'E','í'=>'I','ó'=>'O','ú'=>'U','ä'=>'A','ë'=>'E','ï'=>'I','ö'=>'O','ü'=>'U'
        ];
        $u = strtr($u, $map);
        // Mantener letras, números, espacios, Ñ y &
        $u = preg_replace('/[^A-Z0-9Ñ&\s]/u', ' ', $u);

        // Quitar denominaciones sociales comunes
        $patterns = [
            '~\bS\.?\s*A\.?\s*P\.?\s*I\.?B?\.?\s*(DE)?\s*C\.?\s*V\.?\b~u',
            '~\bS\.?\s*DE\s*R\.?\s*L\.?\s*(DE)?\s*C\.?\s*V\.?\b~u',
            '~\bS\.?\s*DE\s*R\.?\s*L\.?\b~u',
            '~\bS\.?\s*A\.?\s*(DE)?\s*C\.?\s*V\.?\b~u',
            '~\bS\.?\s*A\.?\b~u',
            '~\bA\.?\s*C\.?\b~u',
            '~\bS\.?\s*C\.?\b~u',
            '~\bS\.?\s*EN\s*C\.?\b~u',
            '~\bS\.?\s*EN\s*N\.?\s*C\.?\b~u',
        ];
        $u = preg_replace($patterns, ' ', $u);

        // Compactar espacios
        $u = preg_replace('/\s{2,}/', ' ', $u);
        $u = trim($u);

        return $u !== '' ? $u : 'PUBLICO EN GENERAL';
    }
}
