<?php 

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail; // <- IMPORTANTE
use Illuminate\Validation\Rule;
use Stripe\StripeClient;

use App\Models\CatalogItem;
use App\Models\BillingProfile;
use App\Models\ShippingAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;

use App\Services\FacturapiWebClient;
use App\Services\EnviaComClient;

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
            'profiles'        => $profiles,
            'subtotal'        => $subtotal,
            'shipping'        => session('checkout.shipping', null),
            'address'         => $this->currentAddress(),
            'usoCfdiOptions'  => $this->usoCfdiOptions(),
            'regimenOptions'  => $this->regimenOptions(),
        ]);
    }

    /** POST (AJAX): validar RFC – Paso 1 del modal. */
    public function invoiceValidateRFC(Request $request)
    {
        $rfc = strtoupper(trim((string)$request->input('rfc')));
        $pattern = '/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i';
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
        $profile->uso_cfdi     = $data['uso_cfdi'];
        $profile->regimen      = $data['regimen'];
        $profile->contacto     = $data['contacto'] ?? null;
        $profile->telefono     = $data['telefono'] ?? null;
        $profile->email        = $data['email'] ?? null;
        $profile->direccion    = $data['direccion'] ?? null;
        $profile->zip          = preg_replace('/\D+/', '', (string) $data['cp']);
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
        $data = $request->validate(['id' => ['required','integer']]);

        $addr = ShippingAddress::where('user_id', Auth::id())->find($data['id']);
        if (!$addr) {
            return response()->json(['ok'=>false,'error'=>'Dirección no encontrada'], 404);
        }

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

        $token = config('services.copomex.token');
        if ($token) {
            try {
                $r = Http::timeout(8)->get(
                    'https://api.copomex.com/query/info_cp/' . $cp,
                    ['token' => $token, 'type' => 'simplified']
                );
                if ($r->successful() && ($data = $r->json())) {
                    return response()->json([
                        'cp'           => $cp,
                        'state'        => $data['response']['estado'] ?? null,
                        'municipality' => $data['response']['municipio'] ?? null,
                        'colonies'     => $data['response']['asentamiento'] ?? [],
                    ]);
                }
            } catch (\Throwable $e) { /* ignore */ }
        }

        return response()->json(['cp' => $cp, 'state' => null, 'municipality' => null, 'colonies' => []]);
    }

    /* ===========================================================
     * PASO 3: Envío
     * =========================================================== */
    public function shipping(Request $req)
    {
        $cart = $this->getCartRows();
        if (empty($cart)) {
            return redirect()->route('web.cart.index')->with('ok', 'Tu carrito está vacío.');
        }

        $subtotal  = array_reduce($cart, fn($c, $r) => $c + (($r['price'] ?? 0) * ($r['qty'] ?? 1)), 0);
        $threshold = $this->threshold;

        $address = null;
        if ($id = session('checkout.address_id')) {
            $address = ShippingAddress::where('user_id', Auth::id())->find($id);
        }

        if (!$address) {
            return redirect()
                ->route('checkout.start')
                ->withErrors(['address' => 'Selecciona o agrega una dirección de envío antes de cotizar paqueterías.']);
        }

        $carriers = $this->quoteEnviaCarriersForCheckout($address, $cart, $subtotal);

        if (empty($carriers) && config('services.envia.debug')) {
            Log::warning('Envia.com checkout sin tarifas', [
                'debug' => session('shipping.envia_debug'),
                'address' => $address?->toArray(),
            ]);
        }

        session(['shipping.options' => $carriers]);
        session(['shipping.options_norm' => $carriers]);

        if ($subtotal >= $threshold && $threshold > 0 && !empty($carriers)) {
            $paidOptions = array_values(array_filter($carriers, fn($o) => (float)($o['price'] ?? 0) > 0));

            if (!empty($paidOptions)) {
                usort($paidOptions, fn($a, $b) => (float)$a['price'] <=> (float)$b['price']);
                $best = $paidOptions[0];

                session(['checkout.shipping' => [
                    'code'         => $best['code'],
                    'id'           => $best['id'] ?? $best['code'],
                    'provider'     => 'envia.com',
                    'name'         => $best['name'] ?? ($best['carrier'] ?? 'Paquetería'),
                    'carrier'      => $best['carrier'] ?? ($best['name'] ?? 'Paquetería'),
                    'carrier_key'  => $best['carrier_key'] ?? $this->carrierKey($best['carrier'] ?? $best['name'] ?? ''),
                    'service'      => $best['service'] ?? null,
                    'eta'          => $best['eta'] ?? null,
                    'logo_url'     => $best['logo_url'] ?? $this->carrierLogoUrl($best['carrier'] ?? $best['name'] ?? ''),
                    'price'        => 0.0,
                    'store_pays'   => true,
                    'carrier_cost' => (float)($best['price'] ?? 0),
                    'auto_applied' => true,
                    'raw'          => $best['raw'] ?? null,
                ]]);

                return redirect()
                    ->route('checkout.payment')
                    ->with('ok', 'Envío gratis aplicado. La tienda cubrirá la paquetería más económica disponible.');
            }
        }

        $selected = session('checkout.shipping', [
            'code' => null,
            'price' => 0.0,
            'name' => null,
            'eta' => null,
            'service' => null,
            'logo_url' => null,
        ]);

        return view('checkout.shipping', compact('cart', 'subtotal', 'address', 'carriers', 'selected'));
    }

    public function shippingSelect(Request $req)
    {
        /*
         * FIX DEFINITIVO:
         * Guarda la paquetería seleccionada en checkout.shipping.
         * También soporta formularios normales y AJAX/fetch.
         */
        $code = $req->input('code')
            ?: $req->input('shipping_code')
            ?: $req->input('selected_shipping')
            ?: $req->input('shipping');

        if (!$code) {
            if ($req->ajax() || $req->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'ok' => false,
                    'message' => 'Selecciona una opción de envío.',
                ], 422);
            }

            return back()->withErrors([
                'shipping' => 'Selecciona una opción de envío.',
            ]);
        }

        $options = session('shipping.options_norm')
            ?: session('shipping.options')
            ?: data_get(session('shipping'), 'options_norm')
            ?: data_get(session('shipping'), 'options')
            ?: [];

        $opt = collect($options)->first(function ($option) use ($code) {
            return (string)($option['code'] ?? '') === (string)$code
                || (string)($option['id'] ?? '') === (string)$code;
        });

        if (!$opt) {
            Log::warning('Checkout shipping option no encontrada', [
                'code' => $code,
                'options_count' => is_countable($options) ? count($options) : 0,
                'options_codes' => collect($options)->pluck('code')->values()->all(),
                'legacy_shipping' => session('shipping'),
            ]);

            if ($req->ajax() || $req->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'ok' => false,
                    'message' => 'Opción de envío no válida o expirada. Regresa a envío y vuelve a cotizar.',
                ], 422);
            }

            return back()->withErrors([
                'code' => 'Opción de envío no válida o expirada. Regresa a envío y vuelve a cotizar.',
            ]);
        }

        $opt = (array) $opt;

        $cart = $this->getCartRows();
        $subtotal = array_reduce($cart, function ($carry, $row) {
            return $carry + (((float)($row['price'] ?? 0)) * max(1, (int)($row['qty'] ?? 1)));
        }, 0.0);

        $threshold = $this->threshold;

        $carrierName = $opt['carrier'] ?? $opt['name'] ?? 'Paquetería';
        $carrierKey  = $opt['carrier_key'] ?? $this->carrierKey($carrierName);
        $carrierCost = (float)($opt['price'] ?? 0);

        $payload = [
            'code'                => $opt['code'] ?? $code,
            'id'                  => $opt['id'] ?? ($opt['code'] ?? $code),
            'provider'            => $opt['provider'] ?? 'envia.com',
            'name'                => $opt['name'] ?? $carrierName,
            'carrier'             => $carrierName,
            'carrier_key'         => $carrierKey,
            'service'             => $opt['service'] ?? null,
            'service_label'       => $opt['service_label'] ?? $opt['service_description'] ?? ($opt['service'] ?? null),
            'service_description' => $opt['service_description'] ?? $opt['service_label'] ?? ($opt['service'] ?? null),
            'eta'                 => $opt['eta'] ?? null,
            'logo_url'            => $opt['logo_url'] ?? $this->carrierLogoUrl($carrierName),
            'raw'                 => $opt['raw'] ?? null,
            'currency'            => $opt['currency'] ?? 'MXN',
        ];

        if ($subtotal >= $threshold && $threshold > 0) {
            $selectedShipping = array_merge($payload, [
                'price'        => 0.0,
                'store_pays'   => true,
                'carrier_cost' => $carrierCost,
                'auto_applied' => false,
            ]);
        } else {
            $selectedShipping = array_merge($payload, [
                'price'        => $carrierCost,
                'store_pays'   => false,
                'carrier_cost' => $carrierCost,
                'auto_applied' => false,
            ]);
        }

        /*
         * IMPORTANTE:
         * No dejamos el seleccionado solamente en shipping, porque shipping también se usa
         * como contenedor de options/options_norm/envia_debug.
         */
        session([
            'checkout.shipping' => $selectedShipping,
            'shipping_selected' => $selectedShipping,
            'checkout.shipping_code' => $selectedShipping['code'] ?? $code,
        ]);

        session()->save();

        Log::info('Checkout shipping selected and saved', [
            'code' => $selectedShipping['code'] ?? null,
            'carrier' => $selectedShipping['carrier'] ?? null,
            'service' => $selectedShipping['service'] ?? null,
            'service_label' => $selectedShipping['service_label'] ?? null,
            'price' => $selectedShipping['price'] ?? null,
            'carrier_cost' => $selectedShipping['carrier_cost'] ?? null,
            'store_pays' => $selectedShipping['store_pays'] ?? false,
            'session_has_checkout_shipping' => session()->has('checkout.shipping'),
        ]);

        if ($req->ajax() || $req->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'ok' => true,
                'redirect' => route('checkout.payment'),
                'shipping' => $selectedShipping,
            ]);
        }

        return redirect()->route('checkout.payment');
    }

    private function quoteEnviaCarriersForCheckout(ShippingAddress $address, array $cart, float $subtotal): array
    {
        /** @var EnviaComClient $envia */
        $envia = app(EnviaComClient::class);

        $origin = $this->enviaOriginAddress();
        $destination = $this->enviaDestinationAddress($address);
        $packages = [$this->enviaPackageFromCart($cart, $subtotal)];

        $rates = [];
        $debug = [
            'attempted_carriers' => [],
            'failed_carriers' => [],
        ];

        foreach ($this->enviaCarriersToQuote() as $carrier) {
            $debug['attempted_carriers'][] = $carrier;

            try {
                $payload = $envia->quote($origin, $destination, $packages, [
                    'type' => 1,
                    'carrier' => $carrier,
                ]);

                foreach ($envia->normalizeRates($payload) as $rate) {
                    if (empty($rate['carrier'])) {
                        $rate['carrier'] = $carrier;
                    }

                    $rates[] = $rate;
                }
            } catch (\Throwable $e) {
                $debug['failed_carriers'][$carrier] = $e->getMessage();

                Log::info('Envia.com checkout carrier sin tarifa', [
                    'carrier' => $carrier,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if (empty($rates)) {
            try {
                $payload = $envia->quote($origin, $destination, $packages, [
                    'type' => 1,
                    'carrier' => 'estafeta',
                ]);

                foreach ($envia->normalizeRates($payload) as $rate) {
                    if (empty($rate['carrier'])) {
                        $rate['carrier'] = 'estafeta';
                    }

                    $rates[] = $rate;
                }
            } catch (\Throwable $e) {
                $debug['fallback_estafeta'] = $e->getMessage();
            }
        }

        if (config('services.envia.debug')) {
            session(['shipping.envia_debug' => $debug]);
            Log::info('Envia.com checkout debug', $debug);
        }

        return collect($rates)
            ->filter(fn($rate) => filled($rate['carrier'] ?? null) && filled($rate['service'] ?? null))
            // IMPORTANTE: para e-commerce automático solo mostramos servicios domicilio a domicilio.
            // Servicios "Ocurre" / branch-to-home como Paquetexpress ground_od piden sucursal y fallan en sandbox.
            ->filter(fn($rate) => $this->isEnviaDoorToDoorRate($rate))
            ->unique(function ($rate) {
                return $this->carrierKey((string)($rate['carrier'] ?? '')) . '|' .
                    \Illuminate\Support\Str::slug((string)($rate['service'] ?? '')) . '|' .
                    number_format((float)($rate['total_price'] ?? 0), 2, '.', '');
            })
            ->map(fn($rate) => $this->enviaRateToCheckoutOption($rate))
            ->sortBy('price')
            ->values()
            ->toArray();
    }


    /**
     * Filtra servicios que requieren sucursal/origen tipo "Ocurre".
     * Para checkout automático conviene solo domicilio a domicilio.
     */
    private function isEnviaDoorToDoorRate(array $rate): bool
    {
        $dropOff = (int)($rate['drop_off'] ?? data_get($rate, 'raw.dropOff', 0));
        $service = strtolower((string)($rate['service'] ?? ''));
        $description = strtolower((string)($rate['service_description'] ?? data_get($rate, 'raw.serviceDescription', '')));

        if ($dropOff !== 0) {
            return false;
        }

        if (str_contains($description, 'ocurre') || str_contains($service, '_od') || str_contains($service, '_do')) {
            return false;
        }

        return true;
    }

    private function enviaRateToCheckoutOption(array $rate): array
    {
        $carrier = (string)($rate['carrier'] ?? 'Paquetería');
        $serviceCode = (string)($rate['service'] ?? 'Servicio');
        $serviceLabel = (string)($rate['service_description'] ?? $serviceCode);
        $carrierKey = $this->carrierKey($carrier);
        $price = (float)($rate['total_price'] ?? 0);
        $code = $carrierKey . '-' . \Illuminate\Support\Str::slug($serviceCode) . '-' . substr(md5(json_encode($rate)), 0, 8);

        return [
            'id'                  => $rate['id'] ?? $code,
            'code'                => $code,
            'provider'            => 'envia.com',
            'carrier'             => $carrier,
            'carrier_key'         => $carrierKey,
            'name'                => $carrier,
            // Código técnico para generar guía.
            'service'             => $serviceCode,
            // Texto visual para mostrar al cliente.
            'service_label'       => $serviceLabel,
            'service_description' => $serviceLabel,
            'eta'                 => $this->normalizeEta($rate['delivery_estimate'] ?? null),
            'price'               => $price,
            'currency'            => (string)($rate['currency'] ?? 'MXN'),
            'logo_url'            => $this->carrierLogoUrl($carrier),
            'drop_off'            => (int)($rate['drop_off'] ?? data_get($rate, 'raw.dropOff', 0)),
            'raw'                 => $rate['raw'] ?? $rate,
        ];
    }

    private function enviaCarriersToQuote(): array
    {
        $configured = array_values(array_filter(array_map(
            fn($name) => trim((string)$name),
            explode(',', (string)env('ENVIA_FORCE_CARRIERS', ''))
        )));

        if (!empty($configured)) {
            return $configured;
        }

        return [
            'dhl',
            'fedex',
            'estafeta',
            'ups',
            'redpack',
            'paquetexpress',
            'sendex',
            'carssa',
            'ivoy',
            '99minutos',
            'jtexpress',
            'ampm',
            'scm',
            'quiken',
        ];
    }

    private function enviaOriginAddress(): array
    {
        return [
            'name'       => config('services.envia.origin.name', 'Jureto'),
            'company'    => config('services.envia.origin.company', 'Jureto'),
            'email'      => config('services.envia.origin.email', 'ventas@jureto.com.mx'),
            'phone'      => config('services.envia.origin.phone', '7220000000'),
            'street'     => config('services.envia.origin.street', 'Calle origen'),
            'number'     => config('services.envia.origin.number', 'S/N'),
            'district'   => config('services.envia.origin.district', 'Centro'),
            'city'       => config('services.envia.origin.city', 'Toluca'),
            'state'      => $this->enviaStateCode((string)config('services.envia.origin.state', 'EM')),
            'country'    => config('services.envia.origin.country', 'MX'),
            'postalCode' => config('services.envia.origin.postal_code', '50000'),
            'reference'  => config('services.envia.origin.reference', 'Bodega Jureto'),
        ];
    }

    private function enviaDestinationAddress(ShippingAddress $address): array
    {
        return [
            'name'       => (string)($address->contact_name ?: Auth::user()?->name ?: 'Cliente'),
            'company'    => '',
            'email'      => (string)(Auth::user()?->email ?? 'cliente@test.com'),
            'phone'      => (string)($address->phone ?: '5555555555'),
            'street'     => (string)($address->street ?: 'Domicilio de entrega'),
            'number'     => (string)($address->ext_number ?: 'S/N'),
            'district'   => (string)($address->colony ?: 'Centro'),
            'city'       => (string)($address->municipality ?: $address->city ?: 'Toluca'),
            'state'      => $this->enviaStateCode((string)($address->state ?: 'EM')),
            'country'    => 'MX',
            'postalCode' => (string)$address->postal_code,
            'reference'  => '',
        ];
    }

    private function enviaPackageFromCart(array $cart, float $subtotal): array
    {
        $weight = (float)config('services.envia.default_package.weight', env('ENVIA_PACKAGE_WEIGHT', 1));
        $length = (float)config('services.envia.default_package.length', env('ENVIA_PACKAGE_LENGTH', 30));
        $width  = (float)config('services.envia.default_package.width', env('ENVIA_PACKAGE_WIDTH', 25));
        $height = (float)config('services.envia.default_package.height', env('ENVIA_PACKAGE_HEIGHT', 20));

        return [
            'content'       => 'Productos Jureto',
            'amount'        => 1,
            'type'          => 'box',
            'weight'        => max(0.01, $weight),
            'insurance'     => 0,
            'declaredValue' => max(0, $subtotal),
            'weightUnit'    => 'KG',
            'lengthUnit'    => 'CM',
            'dimensions'    => [
                'length' => max(1, $length),
                'width'  => max(1, $width),
                'height' => max(1, $height),
            ],
        ];
    }

    private function enviaStateCode(string $state): string
    {
        $raw = trim($state);

        if (strlen($raw) === 2) {
            return strtoupper($raw);
        }

        $key = \Illuminate\Support\Str::slug(\Illuminate\Support\Str::ascii($raw));

        $map = [
            'aguascalientes' => 'AG',
            'baja-california' => 'BC',
            'baja-california-sur' => 'BS',
            'campeche' => 'CM',
            'chiapas' => 'CS',
            'chihuahua' => 'CH',
            'ciudad-de-mexico' => 'CX',
            'cdmx' => 'CX',
            'coahuila' => 'CO',
            'coahuila-de-zaragoza' => 'CO',
            'colima' => 'CL',
            'durango' => 'DG',
            'guanajuato' => 'GT',
            'guerrero' => 'GR',
            'hidalgo' => 'HG',
            'jalisco' => 'JA',
            'estado-de-mexico' => 'EM',
            'mexico' => 'EM',
            'edomex' => 'EM',
            'michoacan' => 'MI',
            'michoacan-de-ocampo' => 'MI',
            'morelos' => 'MO',
            'nayarit' => 'NA',
            'nuevo-leon' => 'NL',
            'oaxaca' => 'OA',
            'puebla' => 'PU',
            'queretaro' => 'QT',
            'quintana-roo' => 'QR',
            'san-luis-potosi' => 'SL',
            'sinaloa' => 'SI',
            'sonora' => 'SO',
            'tabasco' => 'TB',
            'tamaulipas' => 'TM',
            'tlaxcala' => 'TL',
            'veracruz' => 'VE',
            'veracruz-de-ignacio-de-la-llave' => 'VE',
            'yucatan' => 'YU',
            'zacatecas' => 'ZA',
        ];

        return $map[$key] ?? strtoupper($raw);
    }

    private function carrierKey(string $carrier): string
    {
        $key = \Illuminate\Support\Str::slug(\Illuminate\Support\Str::ascii($carrier));

        return match ($key) {
            'federal-express' => 'fedex',
            'mexico-redpack', 'redpack-mexico' => 'redpack',
            'paquete-express' => 'paquetexpress',
            '99-minutos', 'noventa-y-nueve-minutos', '99minutos' => '99minutos',
            'j-t-express', 'jtexpress' => 'jtexpress',
            default => $key ?: 'generic',
        };
    }

    private function carrierLogoUrl(string $carrier): string
    {
        $key = $this->carrierKey($carrier);

        foreach (['svg', 'png', 'webp', 'jpg'] as $ext) {
            $relative = "images/carriers/{$key}.{$ext}";
            if (file_exists(public_path($relative))) {
                return asset($relative);
            }
        }

        $domains = [
            'dhl'            => 'dhl.com',
            'fedex'          => 'fedex.com',
            'estafeta'       => 'estafeta.com',
            'ups'            => 'ups.com',
            'redpack'        => 'redpack.com.mx',
            'paquetexpress'  => 'paquetexpress.com.mx',
            'sendex'         => 'sendex.mx',
            'ivoy'           => 'ivoy.mx',
            'carssa'         => 'carssa.com.mx',
            '99minutos'      => '99minutos.com',
            'jtexpress'      => 'jtexpress.mx',
            'ampm'           => 'ampm.com.mx',
            'scm'            => 'scm.com.mx',
            'quiken'         => 'quiken.mx',
        ];

        return isset($domains[$key])
            ? 'https://logo.clearbit.com/' . $domains[$key]
            : asset('images/carriers/generic-shipping.svg');
    }

    private function normalizeEta($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            $days = (int)$value;
            return $days === 1 ? '1 día hábil' : "{$days} días hábiles";
        }

        return (string)$value;
    }

    /* ===========================================================
     * PASO 4: Pago (UI)
     * =========================================================== */
    public function payment(Request $req)
    {
        $cart = $this->getCartRows();

        if (empty($cart)) {
            return redirect()
                ->route('web.cart.index')
                ->with('ok', 'Tu carrito está vacío.');
        }

        /*
         * Respaldo importante:
         * Stripe regresa después del pago y a veces el carrito ya no está disponible.
         */
        session([
            'checkout.cart_snapshot' => $cart,
            'checkout.cart_snapshot_at' => now()->toDateTimeString(),
        ]);

        $subtotal = array_reduce($cart, function ($carry, $row) {
            return $carry + (((float)($row['price'] ?? 0)) * max(1, (int)($row['qty'] ?? 1)));
        }, 0.0);

        $subtotal = round((float)$subtotal, 2);

        /*
         * 1) Intentamos leer el envío seleccionado normal.
         */
        $shipping = session('checkout.shipping');

        /*
         * 2) Compatibilidad con el nuevo respaldo shipping_selected.
         */
        if (empty($shipping) || !is_array($shipping) || empty($shipping['code'])) {
            $selected = session('shipping_selected');

            if (is_array($selected) && !empty($selected['code'])) {
                $shipping = $selected;
            }
        }

        /*
         * 3) Compatibilidad con versiones viejas donde session('shipping') era directamente
         * la paquetería seleccionada.
         */
        if (empty($shipping) || !is_array($shipping) || empty($shipping['code'])) {
            $legacyShipping = session('shipping');

            if (is_array($legacyShipping) && !empty($legacyShipping['code'])) {
                $shipping = $legacyShipping;
            }
        }

        /*
         * 4) FIX FUERTE:
         * Tu log muestra checkout_shipping null, pero legacy_shipping trae:
         * legacy_shipping.options
         * legacy_shipping.options_norm
         *
         * Entonces recuperamos automáticamente la tarifa válida más barata.
         */
        if (empty($shipping) || !is_array($shipping) || empty($shipping['code'])) {
            $options = session('shipping.options_norm')
                ?: session('shipping.options')
                ?: data_get(session('shipping'), 'options_norm')
                ?: data_get(session('shipping'), 'options')
                ?: [];

            $options = collect($options)
                ->filter(function ($option) {
                    return !empty($option['code'])
                        && isset($option['price'])
                        && (float)$option['price'] >= 0;
                })
                ->sortBy(function ($option) {
                    return (float)($option['price'] ?? 999999);
                })
                ->values()
                ->all();

            if (!empty($options)) {
                $shipping = (array) $options[0];

                $shipping['price'] = (float)($shipping['price'] ?? 0);
                $shipping['carrier'] = $shipping['carrier'] ?? $shipping['name'] ?? 'Paquetería';
                $shipping['name'] = $shipping['name'] ?? $shipping['carrier'];
                $shipping['carrier_key'] = $shipping['carrier_key'] ?? $this->carrierKey((string)($shipping['carrier'] ?? ''));
                $shipping['service'] = $shipping['service'] ?? null;
                $shipping['service_label'] = $shipping['service_label']
                    ?? $shipping['service_description']
                    ?? $shipping['service']
                    ?? 'Envío seleccionado';
                $shipping['store_pays'] = false;
                $shipping['carrier_cost'] = (float)($shipping['carrier_cost'] ?? $shipping['price'] ?? 0);
                $shipping['auto_applied'] = false;

                session([
                    'checkout.shipping' => $shipping,
                    'shipping_selected' => $shipping,
                    'checkout.shipping_code' => $shipping['code'] ?? null,
                ]);

                session()->save();

                Log::info('Checkout payment recuperó envío desde opciones anidadas', [
                    'code' => $shipping['code'] ?? null,
                    'carrier' => $shipping['carrier'] ?? null,
                    'service' => $shipping['service'] ?? null,
                    'service_label' => $shipping['service_label'] ?? null,
                    'price' => $shipping['price'] ?? null,
                ]);
            }
        }

        if (empty($shipping) || !is_array($shipping) || empty($shipping['code'])) {
            Log::warning('Checkout payment sin envío seleccionado', [
                'checkout_shipping' => session('checkout.shipping'),
                'shipping_selected' => session('shipping_selected'),
                'legacy_shipping' => session('shipping'),
            ]);

            return redirect()
                ->route('checkout.shipping')
                ->withErrors([
                    'shipping' => 'Selecciona una opción de envío antes de continuar al pago.',
                ]);
        }

        $shipping = (array) $shipping;

        $shipping['price'] = (float)($shipping['price'] ?? 0);
        $shipping['carrier'] = $shipping['carrier'] ?? $shipping['name'] ?? 'Paquetería';
        $shipping['name'] = $shipping['name'] ?? $shipping['carrier'];
        $shipping['service_label'] = $shipping['service_label']
            ?? $shipping['service_description']
            ?? $shipping['service']
            ?? 'Envío seleccionado';

        session([
            'checkout.shipping' => $shipping,
            'shipping_selected' => $shipping,
        ]);

        session()->save();

        $total = round($subtotal + (float)($shipping['price'] ?? 0), 2);

        $address = null;
        if ($id = session('checkout.address_id')) {
            $address = ShippingAddress::where('user_id', Auth::id())->find($id);
        }

        return view('checkout.payment', compact('cart', 'subtotal', 'shipping', 'total', 'address'));
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

            // Respaldo importante para que success pueda guardar partidas aunque el carrito se pierda.
            session([
                'checkout.cart_snapshot' => $cart,
                'checkout.cart_snapshot_at' => now()->toDateTimeString(),
            ]);

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
     * SUCCESS: confirma pago, guarda ORDEN+PARTIDAS+PAGO, timbra y envía correos
     * =========================================================== */
    public function success(Request $req)
    {
        $sessionId = (string) $req->query('session_id');
        $invoice   = null;
        $order     = null; // <- para pasar a la vista

        if (!$sessionId) {
            return view('checkout.success', compact('sessionId', 'invoice', 'order'))
                ->with('error', 'Falta session_id de Stripe.');
        }

        try {
            // 1) Confirmar pago en Stripe
            $session = $this->stripe->checkout->sessions->retrieve($sessionId);
            $paid    = ($session->payment_status ?? null) === 'paid';

            // Datos base
            $user    = Auth::user();
            $uid     = $user?->id;
            $emailU  = $user?->email;
            $nameU   = $user?->name;

            $cart = $this->getCartRows();

            // Si al regresar de Stripe el carrito ya no está, usamos el respaldo guardado antes de pagar.
            if (empty($cart)) {
                $cart = (array) session('checkout.cart_snapshot', []);
            }

            $subtotal = array_reduce($cart, fn($c,$r)=> $c + (($r['price'] ?? 0) * max(1,(int)($r['qty'] ?? 1))), 0.0);

            // Dirección
            $addressArr = session('checkout.address');
            if (!$addressArr && ($aid = session('checkout.address_id'))) {
                if ($addr = \App\Models\ShippingAddress::where('user_id', $uid)->find($aid)) {
                    $addressArr = [
                        'id'               => $addr->id,
                        'street'           => $addr->street,
                        'ext_number'       => $addr->ext_number,
                        'int_number'       => $addr->int_number,
                        'colony'           => $addr->colony,
                        'postal_code'      => $addr->postal_code,
                        'municipality'     => $addr->municipality,
                        'state'            => $addr->state,
                        'between_street_1' => $addr->between_street_1,
                        'between_street_2' => $addr->between_street_2,
                        'references'       => $addr->references,
                        'contact_name'     => $addr->contact_name,
                        'phone'            => $addr->phone,
                    ];
                }
            }

            // Envío
            $shipping   = (array) session('checkout.shipping', []);
            $storePays  = (bool) ($shipping['store_pays'] ?? false);
            $shipAmount = $storePays ? 0.0 : (float) ($shipping['price'] ?? 0.0);
            $total      = round($subtotal + $shipAmount, 2);

            // Facturación
            $profile = null;
            if ($pid = session('checkout.billing_profile_id')) {
                $profile = BillingProfile::where('user_id', $uid)->find($pid);
            }

            // Para la orden
            $customerName  = $profile?->razon_social ?: ($nameU ?: 'CLIENTE');
            $customerEmail = $profile?->email        ?: $emailU;

            // 2) Crear/actualizar ORDER (idempotente por session)
            $orderData = [
                'user_id'               => $uid,
                'customer_name'         => $customerName,
                'customer_email'        => $customerEmail,
                'subtotal'              => round($subtotal, 2),
                'shipping_amount'       => round($shipAmount, 2),
                'total'                 => $total,
                'currency'              => 'MXN',
                'status'                => $paid ? 'pagado' : 'pendiente',

                'address_json'          => $addressArr,
                'shipping_code'         => $shipping['code']    ?? null,
                'shipping_name'         => $shipping['name']    ?? ($shipping['carrier'] ?? null),
                'shipping_service'      => $shipping['service_label'] ?? ($shipping['service'] ?? null),
                'shipping_eta'          => $shipping['eta']     ?? null,
                'shipping_store_pays'   => $storePays,
                'shipping_carrier_cost' => (float)($shipping['carrier_cost'] ?? 0),

                'stripe_session_id'     => $sessionId,
                'stripe_payment_intent' => $session->payment_intent ?? null,
            ];
            if ($profile) $orderData['billing_profile_id'] = $profile->id;

            /** @var \App\Models\Order $order */
            $order = Order::updateOrCreate(
                ['stripe_session_id' => $sessionId],
                $orderData
            );

            // 3) Guardar items
            // IMPORTANTE: se inserta con DB y solo columnas existentes para evitar errores
            // como Unknown column unit_price / total en order_items.
            if (!empty($cart)) {
                try {
                    if (method_exists($order, 'items')) {
                        $order->items()->delete();
                    } elseif (Schema::hasTable('order_items')) {
                        DB::table('order_items')->where('order_id', $order->id)->delete();
                    }
                } catch (\Throwable $e) {
                    if (Schema::hasTable('order_items')) {
                        DB::table('order_items')->where('order_id', $order->id)->delete();
                    }
                }

                foreach ($cart as $row) {
                    $qty   = max(1, (int)($row['qty'] ?? 1));
                    $price = (float)($row['price'] ?? 0);

                    $this->createOrderItemSafe($order, [
                        'catalog_item_id' => $row['id'] ?? ($row['catalog_item_id'] ?? null),
                        'product_id'      => $row['product_id'] ?? ($row['id'] ?? null),
                        'name'            => $row['name'] ?? 'Producto',
                        'sku'             => $row['sku'] ?? null,
                        'price'           => round($price, 2),
                        'qty'             => $qty,
                        'amount'          => round($price * $qty, 2),
                        'currency'        => 'MXN',
                        'tax_rate'        => 0.16,
                        'discount'        => 0,
                        'meta'            => ['image' => $row['image'] ?? null],
                    ]);
                }
            }

            // Guardar información extendida de envío en la orden si existen las columnas.
            $this->persistOrderShippingFields($order, $shipping, $shipAmount, $storePays);

            // 4) Registrar pago si está "paid"
            if ($paid) {
                $amountPaid = $session->amount_total ? ((int)$session->amount_total) / 100 : $total;
                OrderPayment::create([
                    'order_id' => $order->id,
                    'amount'   => round((float)$amountPaid, 2),
                    'currency' => 'MXN',
                    'method'   => 'card',
                    'provider' => 'stripe',
                    'status'   => 'paid',
                    'raw'      => $session->toArray(),
                ]);
                $order->markPaid();

                // Forzamos etiqueta en español para el portal.
                if (Schema::hasColumn('orders', 'status')) {
                    DB::table('orders')->where('id', $order->id)->update([
                        'status' => 'pagado',
                        'updated_at' => now(),
                    ]);
                    $order = $order->fresh();
                }

                // Crear guía/envío en Envia después de pago confirmado.
                $this->createEnviaShipmentForOrder($order, $shipping, $addressArr);
                $order = $order->fresh();
            }

            // 5) Timbrado (si aplica)
            if ($paid && (bool) session('checkout.invoice_required', false)) {
                $isGeneric = !$profile || empty($profile->rfc);
                $rfc       = $isGeneric ? 'XAXX010101000' : strtoupper(trim((string) $profile->rfc));
                $taxSystem = $this->normalizeTaxSystemForRfc($rfc, $isGeneric ? '616' : ($profile->regimen ?? null));
                $rawName   = $isGeneric ? 'PUBLICO EN GENERAL' : ($profile->razon_social ?: ($user->name ?? 'CLIENTE'));
                $legalName = $this->satLegalName($rawName, $rfc);

                $customer = [
                    'legal_name' => $legalName,
                    'tax_id'     => $rfc,
                    'tax_system' => $taxSystem,
                    'address'    => ['zip' => $isGeneric ? '64000' : preg_replace('/\D+/', '', (string)($profile->zip ?? '64000'))],
                ];
                $email = $profile?->email ?: ($user->email ?? null);
                if (!empty($email)) $customer['email'] = $email;

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

                if ($shipAmount > 0 && !$storePays) {
                    $items[] = [
                        'product'  => [
                            'description'  => 'Envío',
                            'product_key'  => '78101800',
                            'unit_key'     => 'E48',
                            'price'        => round($shipAmount, 2),
                            'tax_included' => true,
                            'taxes'        => [[ 'type'=>'IVA', 'rate'=>0.16 ]],
                        ],
                        'quantity' => 1,
                    ];
                }

                $opts = [
                    'payment_method' => config('services.facturaapi_web.metodo', 'PUE'),
                    'payment_form'   => config('services.facturaapi_web.forma',  '04'),
                    'use'            => $isGeneric ? 'S01' : (string)($profile->uso_cfdi ?? config('services.facturaapi_web.uso', 'G03')),
                    'series'         => (string) config('services.facturaapi_web.series', 'F'),
                ];
                if ($rfc === 'XAXX010101000') {
                    $opts['global'] = [
                        'periodicity' => 'month',
                        'months'      => [date('m')],
                        'year'        => (int) date('Y'),
                    ];
                }

                $invoice = $this->facturapi->createInvoice($customer, $items, $opts);
                if (!empty($invoice['id'])) {
                    $this->facturapi->sendInvoiceEmail($invoice['id']);
                    session(['checkout.invoice' => $invoice]);
                    Session::flash('ok', '¡Pago recibido! Tu factura fue timbrada y enviada a tu correo.');
                }
            } else {
                Session::flash('ok', 'Pago confirmado.');
            }

            // 6) ENVIAR CORREOS (cliente y admin)
            try {
                $order->load('items');
                $vars = [
                    'order'   => $order,
                    'invoice' => $invoice,
                    'app'     => config('app.name'),
                ];

                // Cliente (si hay correo)
                if (!empty($order->customer_email)) {
                    Mail::send('emails.orders.receipt', $vars, function ($m) use ($order) {
                        $m->to($order->customer_email, $order->customer_name)
                          ->subject('Tu compra #'.$order->id.' — '.config('app.name'));
                    });
                }

                // Admin
                $adminEmail = 'alex.perea1212@gmail.com';
                Mail::send('emails.orders.receipt', $vars + ['isAdmin' => true], function ($m) use ($order, $adminEmail) {
                    $m->to($adminEmail)
                      ->subject('Nueva venta #'.$order->id.' — '.config('app.name'));
                });
            } catch (\Throwable $mailE) {
                Log::warning('Order mail error: '.$mailE->getMessage(), ['order_id' => $order->id ?? null]);
            }

            // 7) Limpiar sesión
            Session::forget([
                'cart',
                'checkout.address_id',
                'checkout.address',
                'checkout.billing_profile_id',
                'checkout.invoice_required',
                'checkout.shipping',
                'checkout.cart_snapshot',
                'checkout.cart_snapshot_at',
                'shipping.options',
                'shipping.options_norm',
            ]);

        } catch (\Throwable $e) {
            Log::error('Checkout success error: '.$e->getMessage(), ['session_id'=>$sessionId]);
            Session::flash('error',
                'Pago realizado. Hubo un problema al registrar la orden o generar la factura. '.
                'Si necesitas el CFDI de este pago, contáctanos.');
        }

        // IMPORTANTÍSIMO: pasar $order a la vista para que muestre partidas y totales.
        return view('checkout.success', compact('sessionId', 'invoice', 'order', 'cart'));
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
    private function pmRegimens(): array { return ['601','603','620','622','623','624','626']; }
    private function pfRegimens(): array { return ['605','606','607','608','610','612','614','615','616','621','625','626']; }

    /**
     * Convierte la selección de envío (guardada en sesión) en shipping_options para Stripe Checkout.
     */
    private function buildStripeShippingOptions(): array
    {
        $s = session('checkout.shipping');
        if (!$s || !array_key_exists('price', (array)$s)) {
            return [];
        }

        $amount = (int) round(max(0, (float) ($s['price'] ?? 0)) * 100);

        $display = ($amount === 0)
            ? 'Envío gratis'
            : trim(($s['name'] ?? 'Envío') . (isset($s['service']) && $s['service'] ? ' — ' . $s['service'] : ''));

        $rate = [
            'shipping_rate_data' => [
                'type'         => 'fixed_amount',
                'fixed_amount' => ['amount' => $amount, 'currency' => 'mxn'],
                'display_name' => $display,
            ],
        ];

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
     */
    private function normalizeTaxSystemForRfc(string $rfc, ?string $regimen): string {
        $rfc = strtoupper(trim($rfc));
        if (in_array($rfc, ['XAXX010101000','XEXX010101000'], true)) {
            return '616';
        }
        $len = strlen($rfc);
        $regimen = $regimen ? (string)$regimen : '';
        if ($len === 12) { return in_array($regimen, $this->pmRegimens(), true) ? $regimen : '601'; }
        if ($len === 13) { return in_array($regimen, $this->pfRegimens(), true) ? $regimen : '612'; }
        return '612';
    }

    /**
     * Normaliza la razón social SOLO para timbrar (no toca BD)
     */
    private function satLegalName(string $rawName, string $taxId): string
    {
        $taxId = strtoupper(trim($taxId));
        if (in_array($taxId, ['XAXX010101000', 'XEXX010101000'], true)) {
            return 'PUBLICO EN GENERAL';
        }

        $u = mb_strtoupper(trim($rawName), 'UTF-8');

        $map = [
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ä'=>'A','Ë'=>'E','Ï'=>'I','Ö'=>'O','Ü'=>'U',
            'á'=>'A','é'=>'E','í'=>'I','ó'=>'O','ú'=>'U','ä'=>'A','ë'=>'E','ï'=>'I','ö'=>'O','ü'=>'U'
        ];
        $u = strtr($u, $map);
        $u = preg_replace('/[^A-Z0-9Ñ&\s]/u', ' ', $u);

        $patterns = [
            '~\bS\.?\s*A\.?\s*P\.?\s*I\.?B?\.?\s*(DE)?\s*C\.?\s*V\.?\b~u',
            '~\bS\.?\s*DE\s*R\.?\s*L\.?\s*(DE)?\s*C\.?\s*V\.?\b~u',
            '~\bS\.?\s*DE\s*R\.?\s*L\.?\b~u',
            '~\bS\.?\s*A\.?\s*(DE)?\s*C\.?\s*V\.?\b~u',
            '~\bS\.?\s*A\.?\b~u','~\bA\.?\s*C\.?\b~u','~\bS\.?\s*C\.?\b~u',
            '~\bS\.?\s*EN\s*C\.?\b~u','~\bS\.?\s*EN\s*N\.?\s*C\.?\b~u',
        ];
        $u = preg_replace($patterns, ' ', $u);
        $u = preg_replace('/\s{2,}/', ' ', $u);
        $u = trim($u);

        return $u !== '' ? $u : 'PUBLICO EN GENERAL';
    }
    /**
     * Guarda datos extendidos del envío seleccionado en la orden, sin depender de $fillable.
     */
    private function persistOrderShippingFields(Order $order, array $shipping, float $shipAmount, bool $storePays): void
    {
        try {
            if (!Schema::hasTable('orders')) {
                return;
            }

            $update = [];

            $put = function (string $column, mixed $value) use (&$update) {
                if ($value !== null && Schema::hasColumn('orders', $column)) {
                    $update[$column] = $value;
                }
            };

            $put('shipping_provider', $shipping['provider'] ?? 'envia.com');
            $put('shipping_amount', round($shipAmount, 2));
            $put('shipping_code', $shipping['code'] ?? null);
            $put('shipping_rate_code', $shipping['selected_id'] ?? $shipping['code'] ?? null);
            $put('shipping_name', $shipping['name'] ?? ($shipping['carrier'] ?? null));
            $put('shipping_carrier', $shipping['carrier'] ?? null);
            $put('shipping_service', $shipping['service'] ?? null);
            $put('shipping_eta', $shipping['eta'] ?? null);
            $put('shipping_logo_url', $shipping['logo_url'] ?? null);
            $put('shipping_store_pays', $storePays);
            $put('shipping_carrier_cost', (float)($shipping['carrier_cost'] ?? $shipAmount));
            $put('shipping_raw', is_string($shipping['raw'] ?? null) ? $shipping['raw'] : json_encode($shipping['raw'] ?? $shipping, JSON_UNESCAPED_UNICODE));
            $put('updated_at', now());

            if (!empty($update)) {
                DB::table('orders')->where('id', $order->id)->update($update);
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudieron guardar campos extendidos de envío en orden', [
                'order_id' => $order->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Crea la guía/envío en Envia.com después de que Stripe confirmó el pago.
     * La cotización solo muestra precios; aquí ya se genera el pedido en Envia.
     */
    private function createEnviaShipmentForOrder(Order $order, array $shipping, ?array $addressArr = null): void
    {
        try {
            if (!class_exists(EnviaComClient::class)) {
                Log::warning('EnviaComClient no existe. No se puede crear guía.', ['order_id' => $order->id ?? null]);
                return;
            }

            if (!$this->orderCanReceiveEnviaShipment($order)) {
                return;
            }

            $carrier = strtolower(trim((string)($shipping['carrier_key'] ?? $shipping['carrier'] ?? $order->shipping_carrier ?? '')));
            if ($carrier === '') {
                Log::warning('Sin carrier para crear guía Envia.', ['order_id' => $order->id ?? null, 'shipping' => $shipping]);
                return;
            }

            $service = $this->resolveEnviaServiceCode(
                (string)($shipping['carrier_key'] ?? $shipping['carrier'] ?? $order->shipping_carrier ?? ''),
                (string)($shipping['service'] ?? $order->shipping_service ?? ''),
                $shipping['raw'] ?? ($order->shipping_raw ?? null)
            );

            $origin = $this->enviaOriginAddress();
            $destination = $this->enviaDestinationFromOrder($order, $addressArr);
            $packages = [$this->enviaPackageFromOrder($order)];

            if (empty($destination['postalCode'])) {
                Log::warning('Destino sin código postal. No se crea guía Envia.', [
                    'order_id' => $order->id ?? null,
                    'destination' => $destination,
                ]);
                return;
            }

            /** @var EnviaComClient $envia */
            $envia = app(EnviaComClient::class);

            if (!method_exists($envia, 'generate')) {
                Log::warning('EnviaComClient no tiene método generate().', ['order_id' => $order->id ?? null]);
                return;
            }

            $shipment = [
                'type' => 1,
                'carrier' => $carrier,
                'service' => $service,
                'reference' => 'ORDER-' . $order->id,
                'comments' => 'Pedido JURETO #' . $order->id,
            ];

            $payload = $envia->generate($origin, $destination, $packages, $shipment);

            $normalized = [];
            if (method_exists($envia, 'normalizeGeneratedShipment')) {
                $normalized = (array) $envia->normalizeGeneratedShipment($payload);
            }

            $trackingNumber = $normalized['tracking_number']
                ?? $normalized['trackingNumber']
                ?? data_get($payload, 'data.0.trackingNumber')
                ?? data_get($payload, 'data.trackingNumber')
                ?? data_get($payload, 'trackingNumber')
                ?? data_get($payload, 'shipment.trackingNumber')
                ?? data_get($payload, 'data.0.tracking_number')
                ?? data_get($payload, 'data.tracking_number');

            $trackingUrl = $normalized['tracking_url']
                ?? $normalized['trackingUrl']
                ?? data_get($payload, 'data.0.trackingUrl')
                ?? data_get($payload, 'data.trackingUrl')
                ?? data_get($payload, 'trackingUrl')
                ?? data_get($payload, 'data.0.tracking_url')
                ?? data_get($payload, 'data.tracking_url');

            $labelUrl = $normalized['label_url']
                ?? $normalized['labelUrl']
                ?? data_get($payload, 'data.0.label')
                ?? data_get($payload, 'data.label')
                ?? data_get($payload, 'label')
                ?? data_get($payload, 'data.0.labelUrl')
                ?? data_get($payload, 'data.labelUrl')
                ?? data_get($payload, 'labelUrl');

            $update = [];
            $put = function (string $column, mixed $value) use (&$update) {
                if ($value !== null && Schema::hasColumn('orders', $column)) {
                    $update[$column] = $value;
                }
            };

            $put('shipping_status', $trackingNumber ? 'creado' : 'pendiente');
            $put('tracking_number', $trackingNumber);
            $put('shipping_tracking_number', $trackingNumber);
            $put('guide_number', $trackingNumber);
            $put('guia', $trackingNumber);
            $put('tracking_url', $trackingUrl);
            $put('shipping_tracking_url', $trackingUrl);
            $put('label_url', $labelUrl);
            $put('shipping_label_url', $labelUrl);
            $put('envia_payload', json_encode($payload, JSON_UNESCAPED_UNICODE));
            $put('updated_at', now());

            if (!empty($update)) {
                DB::table('orders')->where('id', $order->id)->update($update);
            }

            Log::info('Guía Envia creada o solicitada', [
                'order_id' => $order->id,
                'carrier' => $carrier,
                'service' => $service,
                'tracking_number' => $trackingNumber,
                'label_url' => $labelUrl,
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo crear guía Envia después del pago', [
                'order_id' => $order->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function orderCanReceiveEnviaShipment(Order $order): bool
    {
        foreach (['tracking_number', 'shipping_tracking_number', 'guide_number', 'guia'] as $field) {
            if (!empty($order->{$field})) {
                return false;
            }
        }

        return true;
    }

    private function enviaDestinationFromOrder(Order $order, ?array $addressArr = null): array
    {
        $address = $addressArr ?: (array)($order->address_json ?? []);

        return [
            'name' => $order->customer_name ?? $address['contact_name'] ?? auth()->user()?->name ?? 'Cliente',
            'company' => $order->customer_name ?? $address['contact_name'] ?? 'Cliente',
            'email' => $order->customer_email ?? auth()->user()?->email ?? 'cliente@jureto.com.mx',
            'phone' => $address['phone'] ?? $order->customer_phone ?? '7220000000',
            'street' => $address['street'] ?? '',
            'number' => $address['ext_number'] ?? $address['number'] ?? 'S/N',
            'district' => $address['colony'] ?? $address['district'] ?? '',
            'city' => $address['municipality'] ?? $address['city'] ?? '',
            'state' => $this->enviaStateCode($address['state'] ?? ''),
            'country' => 'MX',
            'postalCode' => $address['postal_code'] ?? $address['zip'] ?? '',
            'reference' => trim(($address['references'] ?? '') . ' ' . ($address['between_street_1'] ?? '') . ' ' . ($address['between_street_2'] ?? '')),
        ];
    }

    private function enviaPackageFromOrder(Order $order): array
    {
        return [
            'content' => 'Productos JURETO',
            'amount' => 1,
            'type' => 'box',
            'weight' => (float) env('ENVIA_PACKAGE_WEIGHT', 1),
            'insurance' => 0,
            'declaredValue' => (float)($order->total ?? 0),
            'weightUnit' => 'KG',
            'lengthUnit' => 'CM',
            'dimensions' => [
                'length' => (float) env('ENVIA_PACKAGE_LENGTH', 30),
                'width' => (float) env('ENVIA_PACKAGE_WIDTH', 25),
                'height' => (float) env('ENVIA_PACKAGE_HEIGHT', 20),
            ],
        ];
    }

    /**
     * Inserta una partida de pedido usando solo columnas que existen en order_items.
     * Evita errores como: Unknown column 'unit_price' o 'total'.
     */
    private function createOrderItemSafe(Order $order, array $data): void
    {
        if (!Schema::hasTable('order_items')) {
            return;
        }

        $insert = ['order_id' => $order->id];

        $this->putIfColumnTable($insert, 'catalog_item_id', $data['catalog_item_id'] ?? null, 'order_items');
        $this->putIfColumnTable($insert, 'product_id', $data['product_id'] ?? null, 'order_items');
        $this->putIfColumnTable($insert, 'name', $data['name'] ?? 'Producto', 'order_items');
        $this->putIfColumnTable($insert, 'product_name', $data['name'] ?? 'Producto', 'order_items');
        $this->putIfColumnTable($insert, 'item_name', $data['name'] ?? 'Producto', 'order_items');
        $this->putIfColumnTable($insert, 'title', $data['name'] ?? 'Producto', 'order_items');
        $this->putIfColumnTable($insert, 'description', $data['name'] ?? 'Producto', 'order_items');
        $this->putIfColumnTable($insert, 'sku', $data['sku'] ?? null, 'order_items');
        $this->putIfColumnTable($insert, 'qty', $data['qty'] ?? 1, 'order_items');
        $this->putIfColumnTable($insert, 'quantity', $data['qty'] ?? 1, 'order_items');
        $this->putIfColumnTable($insert, 'cantidad', $data['qty'] ?? 1, 'order_items');
        $this->putIfColumnTable($insert, 'price', $data['price'] ?? 0, 'order_items');
        $this->putIfColumnTable($insert, 'unit_price', $data['price'] ?? 0, 'order_items');
        $this->putIfColumnTable($insert, 'unit_amount', $data['price'] ?? 0, 'order_items');
        $this->putIfColumnTable($insert, 'precio', $data['price'] ?? 0, 'order_items');
        $this->putIfColumnTable($insert, 'amount', $data['amount'] ?? 0, 'order_items');
        $this->putIfColumnTable($insert, 'total', $data['amount'] ?? 0, 'order_items');
        $this->putIfColumnTable($insert, 'line_total', $data['amount'] ?? 0, 'order_items');
        $this->putIfColumnTable($insert, 'subtotal', $data['amount'] ?? 0, 'order_items');
        $this->putIfColumnTable($insert, 'image', data_get($data, 'meta.image'), 'order_items');
        $this->putIfColumnTable($insert, 'image_url', data_get($data, 'meta.image'), 'order_items');
        $this->putIfColumnTable($insert, 'thumbnail', data_get($data, 'meta.image'), 'order_items');
        $this->putIfColumnTable($insert, 'currency', $data['currency'] ?? 'MXN', 'order_items');
        $this->putIfColumnTable($insert, 'tax_rate', $data['tax_rate'] ?? null, 'order_items');
        $this->putIfColumnTable($insert, 'discount', $data['discount'] ?? 0, 'order_items');

        $meta = $data['meta'] ?? [];
        $this->putIfColumnTable($insert, 'meta', is_string($meta) ? $meta : json_encode($meta, JSON_UNESCAPED_UNICODE), 'order_items');

        $this->putIfColumnTable($insert, 'created_at', now(), 'order_items');
        $this->putIfColumnTable($insert, 'updated_at', now(), 'order_items');

        DB::table('order_items')->insert($insert);
    }

    private function putIfColumnTable(array &$row, string $column, mixed $value, string $table): void
    {
        if ($value !== null && Schema::hasColumn($table, $column)) {
            $row[$column] = $value;
        }
    }

    /**
     * Envia generate necesita el código del servicio, no la descripción.
     * Ejemplo correcto: paquetexpress + ground_od
     * Ejemplo incorrecto: paquetexpress + "Paquetexpress Ocurre - domicilio"
     */
    private function resolveEnviaServiceCode(string $carrier, string $service, mixed $raw = null): string
    {
        $carrierKey = strtolower(trim($carrier));
        $serviceText = trim($service);

        $rawArr = null;

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode(html_entity_decode($raw), true);
            $rawArr = is_array($decoded) ? $decoded : null;
        } elseif (is_array($raw)) {
            $rawArr = $raw;
        }

        $rawService = data_get($rawArr, 'service');
        if (is_string($rawService) && $rawService !== '') {
            return $rawService;
        }

        if ($serviceText !== '' && preg_match('/^[a-z0-9_\\-]+$/i', $serviceText) && !str_contains($serviceText, ' ')) {
            return strtolower($serviceText);
        }

        $s = strtolower($serviceText);

        return match ($carrierKey) {
            'ups' => str_contains($s, 'saver') ? 'saver' : strtolower($serviceText),

            'paquetexpress' => match (true) {
                str_contains($s, 'ocurre - domicilio') => 'ground_od',
                str_contains($s, 'domicilio - ocurre') => 'ground_do',
                str_contains($s, 'ocurre') && str_contains($s, 'domicilio') => 'ground_od',
                str_contains($s, 'terrestre') || str_contains($s, 'ground') => 'ground',
                default => 'ground',
            },

            'estafeta' => match (true) {
                str_contains($s, 'siguiente') || str_contains($s, 'express') => 'express',
                str_contains($s, 'metropolitano') || str_contains($s, 'local') => 'local',
                str_contains($s, 'terrestre') || str_contains($s, 'ground') => 'ground',
                default => 'ground',
            },

            'fedex' => str_contains($s, 'ground') || str_contains($s, 'econ') ? 'ground' : strtolower($serviceText),

            'dhl' => match (true) {
                str_contains($s, 'economy') || str_contains($s, 'ground') => 'ground_od',
                default => strtolower($serviceText),
            },

            'scm' => str_contains($s, 'ground') ? 'ground' : strtolower($serviceText),

            default => strtolower(str_replace(' ', '_', $serviceText)),
        };
    }

    /**
     * Extrae código de sucursal desde la tarifa cruda de Envia.
     * Necesario para servicios como Paquetexpress ground_od.
     */
    private function resolveEnviaBranchCode(mixed $raw = null): ?string
    {
        $rawArr = null;

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode(html_entity_decode($raw), true);
            $rawArr = is_array($decoded) ? $decoded : null;
        } elseif (is_array($raw)) {
            $rawArr = $raw;
        }

        if (!$rawArr) {
            $envBranch = env('ENVIA_ORIGIN_BRANCH_CODE');
            return $envBranch ? (string) $envBranch : null;
        }

        $direct = data_get($rawArr, 'originBranchCode')
            ?? data_get($rawArr, 'origin_branch_code')
            ?? data_get($rawArr, 'branchCode')
            ?? data_get($rawArr, 'branch_code');

        if ($direct) {
            return (string) $direct;
        }

        $branches = data_get($rawArr, 'branches', []);

        if (is_array($branches) && !empty($branches)) {
            $first = $branches[0];

            $code = data_get($first, 'branch_code')
                ?? data_get($first, 'branchCode')
                ?? data_get($first, 'branch_id')
                ?? data_get($first, 'branchId');

            if ($code) {
                return (string) $code;
            }
        }

        $envBranch = env('ENVIA_ORIGIN_BRANCH_CODE');

        return $envBranch ? (string) $envBranch : null;
    }

}
