<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Stripe\StripeClient;
use App\Models\CatalogItem;
use App\Models\BillingProfile;
use App\Models\ShippingAddress;

class CheckoutController extends Controller
{
    private StripeClient $stripe;

    public function __construct()
    {
        $secret = config('services.stripe.secret');
        if (blank($secret)) {
            throw new \RuntimeException('Falta STRIPE_SECRET en el .env o en config/services.php');
        }
        $this->stripe = new StripeClient($secret);
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

        // Si ya tiene algún perfil, asumimos que quiere factura (a menos que lo haya omitido en esta orden)
        $hasBilling  = $user->billingProfiles()->exists();
        $invoicePref = $hasBilling ? true : null;
        if (session()->has('checkout.invoice_required')) {
            $invoicePref = (bool) session('checkout.invoice_required');
        }

        return view('checkout.start', compact('cart', 'totals', 'user', 'address', 'invoicePref'));
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
            'profiles'        => $profiles,
            'subtotal'        => $subtotal,
            'shipping'        => session('checkout.shipping', null),
            'address'         => $this->currentAddress(),
            'usoCfdiOptions'  => $this->usoCfdiOptions(),   // code => label
            'regimenOptions'  => $this->regimenOptions(),   // code => label
        ]);
    }

    /** POST (AJAX): validar RFC – Paso 1 del modal. */
    public function invoiceValidateRFC(Request $request)
    {
        $rfc = strtoupper(trim((string)$request->input('rfc')));
        $pattern = '/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i'; // 12 PM / 13 PF
        if (!preg_match($pattern, $rfc)) {
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
            'razon'     => ['required','string','max:190'],
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
        $profile->razon_social = $data['razon'];
        $profile->uso_cfdi     = $data['uso_cfdi'];  // CÓDIGO
        $profile->regimen      = $data['regimen'];   // CÓDIGO
        $profile->contacto     = $data['contacto'] ?? null;
        $profile->telefono     = $data['telefono'] ?? null;
        $profile->email        = $data['email'] ?? null;
        $profile->direccion    = $data['direccion'] ?? null;
        $profile->zip          = $data['cp'];
        $profile->colonia      = $data['colonia'] ?? null;
        $profile->estado       = $data['estado'] ?? null;
        $profile->metodo_pago  = 'Tarjeta';
        $profile->is_default   = !BillingProfile::where('user_id',$user->id)->exists();
        $profile->save();

        session(['checkout.billing_profile_id' => $profile->id,
                 'checkout.invoice_required'   => true]);

        // Si la petición espera JSON (fetch), devolvemos redirect para que el front decida
        if ($request->wantsJson()) {
            return response()->json([
                'ok'       => true,
                'profile'  => $profile,
                'redirect' => route('checkout.shipping'),
            ]);
        }

        // Fallback clásico (sin AJAX)
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
        if (empty($cart)) return redirect()->route('web.cart.index')->with('ok','Tu carrito está vacío.');

        $subtotal = array_reduce($cart, fn($c,$r)=> $c + ($r['price']*$r['qty']), 0);

        $address = null;
        if ($id = session('checkout.address_id')) {
            $address = ShippingAddress::where('user_id', Auth::id())->find($id);
        }

        $carriers = [
            ['code'=>'dhl',     'name'=>'DHL Express', 'eta'=>'2 a 5 días hábiles', 'price'=>329.57],
            ['code'=>'fedex',   'name'=>'FedEx',       'eta'=>'2 a 4 días hábiles', 'price'=>289.99],
            ['code'=>'estafeta','name'=>'Estafeta',    'eta'=>'3 a 6 días hábiles', 'price'=>249.00],
        ];
        $selected = session('checkout.shipping', ['code'=>null,'price'=>0.0]);

        return view('checkout.shipping', compact('cart','subtotal','address','carriers','selected'));
    }

    public function shippingSelect(Request $req)
    {
        $data = $req->validate([
            'code'  => 'required|string',
            'price' => 'required|numeric|min:0',
            'name'  => 'required|string',
            'eta'   => 'nullable|string',
        ]);

        session(['checkout.shipping' => $data]);

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

        $shipping = session('checkout.shipping', ['price'=>0,'name'=>null,'code'=>null,'eta'=>null]);
        $total    = $subtotal + (float)($shipping['price'] ?? 0);

        $address = null;
        if ($id = session('checkout.address_id')) {
            $address = ShippingAddress::where('user_id', Auth::id())->find($id);
        }

        return view('checkout.payment', compact('cart','subtotal','shipping','total','address'));
    }

    /* ===========================================================
     * Stripe: Buy now / Carrito
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

            $session = $this->stripe->checkout->sessions->create([
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
            ]);

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

            $session = $this->stripe->checkout->sessions->create([
                'mode'                         => 'payment',
                'payment_method_types'         => ['card'],
                'locale'                       => 'es-419',
                'customer_email'               => $email,
                'success_url'                  => $successUrl,
                'cancel_url'                   => $cancelUrl,
                'metadata'                     => [
                    'type'    => 'cart',
                    'user_id' => (string)($uid ?? ''),
                    'items'   => implode(',', $metadataItems),
                ],
                'line_items'                   => $lineItems,
                'allow_promotion_codes'        => true,
                'shipping_address_collection'  => ['allowed_countries' => ['MX']],
            ]);

            return response()->json(['url' => $session->url], 200);
        } catch (\Throwable $e) {
            Log::error('Stripe checkoutCart error: ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['error' => 'No se pudo crear la sesión de pago del carrito.'], 500);
        }
    }

    public function success(Request $req)
    {
        $sessionId = $req->query('session_id');
        return view('checkout.success', compact('sessionId'));
    }

    public function cancel()
    {
        return redirect()->route('web.cart.index')->with('ok', 'Pago cancelado.');
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
        // Códigos SAT => etiqueta (incluye los solicitados)
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
}
            