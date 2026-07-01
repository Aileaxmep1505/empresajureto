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
use App\Services\SkydropxService;
use App\Services\EnviaComService;

use App\Models\CatalogItem;
use App\Models\BillingProfile;
use App\Models\ShippingAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;

use App\Services\FacturapiWebClient;

class CheckoutController extends Controller
{
    private StripeClient $stripe;
private FacturapiWebClient $facturapi;
private SkydropxService $skydropx;
private EnviaComService $envia;
protected float $threshold;

public function __construct(FacturapiWebClient $facturapi, SkydropxService $skydropx, EnviaComService $envia)
{
    $secret = config('services.stripe.secret');
    if (blank($secret)) {
        throw new \RuntimeException('Falta STRIPE_SECRET en el .env o en config/services.php');
    }

    $this->stripe    = new StripeClient($secret);
    $this->facturapi = $facturapi;
    $this->skydropx  = $skydropx;
    $this->envia     = $envia;
    $this->threshold = (float) env('FREE_SHIPPING_THRESHOLD', 5000);
}

    /* ===========================================================
     * PASO 1: ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œMi pedidoÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â
     * =========================================================== */
    public function start(Request $request)
    {
        $cart = $this->getCartRows();
        if (empty($cart)) {
            return redirect()->route('web.cart.index')->with('ok', 'Tu carrito estÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ vacÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o.');
        }

        $subtotal = array_reduce($cart, fn($c,$r)=> $c + (($r['price'] ?? 0) * ($r['qty'] ?? 1)), 0);
        $totals = [
            'subtotal' => $subtotal,
            'envio'    => 0,
            'total'    => $subtotal,
            'count'    => array_sum(array_map(fn($r)=>(int)($r['qty'] ?? 0), $cart)),
        ];

        $user = Auth::user();

        // DirecciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n seleccionada (si ya existe en sesiÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n)
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
     * PASO 2: FACTURACIÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“N ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â Listado + Modal (RFC -> Formulario)
     * =========================================================== */

    /** PÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡gina/step con listado (la UI abre el modal en 2 pasos). */
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

    /** POST (AJAX): validar RFC ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“ Paso 1 del modal. */
    public function invoiceValidateRFC(Request $request)
    {
        $rfc = strtoupper(trim((string)$request->input('rfc')));
        $pattern = '/^[A-ZÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¹Ã…â€œ&]{3,4}\d{6}[A-Z0-9]{3}$/i';
        if (!preg_match($pattern, $rfc) && !in_array($rfc, ['XAXX010101000','XEXX010101000'], true)) {
            return response()->json(['ok'=>false,'message'=>'RFC invÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡lido. Verifica tu constancia.'], 422);
        }
        $tipo = strlen($rfc) === 12 ? 'PM' : 'PF';
        return response()->json(['ok'=>true,'rfc'=>$rfc,'tipo'=>$tipo]);
    }

    /** POST (AJAX o clÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡sico): guardar perfil ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“ Paso 2 del modal. */
    public function invoiceStore(Request $request)
    {
        $user = Auth::user();

        if ($request->filled('zip') && !$request->filled('cp')) {
            $request->merge(['cp' => $request->input('zip')]);
        }

        $data = $request->validate([
            'rfc'       => ['required','string','max:13','regex:/^[A-ZÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¹Ã…â€œ&]{3,4}\d{6}[A-Z0-9]{3}$/i'],
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
            'razon'   => 'razÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n social',
            'cp'      => 'cÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³digo postal',
            'regimen' => 'rÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©gimen fiscal',
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

        return redirect()->route('checkout.shipping')->with('ok', 'Datos de facturaciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n guardados.');
    }

    /** POST: seleccionar perfil existente y seguir al envÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o. */
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

    /** Saltar facturaciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n solo para ESTA compra (bandera de sesiÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n). */
    public function invoiceSkip(Request $request)
    {
        session(['checkout.invoice_required' => false]);
        return response()->json(['ok' => true]);
    }

    /* ===========================================================
     * DIRECCIÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“N: guardar desde el modal (AJAX)
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
     * Seleccionar direcciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n guardada (AJAX)
     * =========================================================== */
    public function addressSelect(Request $request)
    {
        $data = $request->validate(['id' => ['required','integer']]);

        $addr = ShippingAddress::where('user_id', Auth::id())->find($data['id']);
        if (!$addr) {
            return response()->json(['ok'=>false,'error'=>'DirecciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n no encontrada'], 404);
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
            return response()->json(['error' => 'CP invÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡lido'], 422);
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
     * PASO 3: EnvÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o
     * =========================================================== */
   public function shipping(Request $req)
{
    $cart = $this->getCartRows();
    if (empty($cart)) {
        return redirect()->route('web.cart.index')->with('ok','Tu carrito estÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ vacÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o.');
    }

    $subtotal  = array_reduce($cart, fn($c,$r)=> $c + ($r['price']*$r['qty']), 0);
    $threshold = $this->threshold;

    $address = null;
    if ($id = session('checkout.address_id')) {
        $address = ShippingAddress::where('user_id', Auth::id())->find($id);
    }

    session()->forget(['shipping.options', 'shipping.options_norm', 'shipping.selected']);

    $all = [];

    if ($address) {
        try {
            $all = $this->envia->quote($address, $cart, $subtotal);
        } catch (\Throwable $e) {
            \Log::warning('Envia no disponible en checkout', [
                'message' => $e->getMessage(),
            ]);
            $all = [];
        }

        if (empty($all)) {
            try {
                $all = $this->skydropx->quote([
                    'postal_code'  => $address->postal_code,
                    'state'        => $address->state,
                    'municipality' => $address->municipality,
                    'colony'       => $address->colony,
                ], $cart);
            } catch (\Throwable $e) {
                \Log::warning('Skydropx no disponible en checkout', [
                    'message' => $e->getMessage(),
                ]);
                $all = [];
            }
        }

        session(['shipping.options' => $all]);
    }

    $carriers = array_values(array_filter($all, fn($o) => (float)($o['price'] ?? $o['amount'] ?? 0) > 0));

    $carriers = array_map(function ($o) {
        if (empty($o['code']) && !empty($o['id'])) {
            $o['code'] = $o['id'];
        }

        if (empty($o['eta']) && !empty($o['days'])) {
            $o['eta'] = $o['days'];
        }

        if (empty($o['price']) && !empty($o['amount'])) {
            $o['price'] = $o['amount'];
        }

        if (empty($o['name'])) {
            $o['name'] = $o['provider'] ?? $o['carrier'] ?? 'EnvÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o estÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ndar';
        }

        return $o;
    }, $carriers);

    session(['shipping.options_norm' => $carriers]);

    if (count($carriers) === 1) {
        $only = $carriers[0];

        session(['checkout.shipping' => [
            'code'    => $only['code'] ?? $only['id'] ?? 'manual_shipping',
            'name'    => $only['name'] ?? $only['provider'] ?? 'EnvÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o estÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ndar',
            'service' => $only['service'] ?? 'Tarifa por zona',
            'eta'     => $only['eta'] ?? $only['days'] ?? $only['delivery_days'] ?? null,
            'price'   => (float)($only['price'] ?? $only['amount'] ?? 0),
        ]]);
    }

    if ($subtotal >= $threshold && !empty($carriers)) {
        usort($carriers, fn($a,$b) => (float)$a['price'] <=> (float)$b['price']);
        $best = $carriers[0];

        session(['checkout.shipping' => [
            'code'        => $best['code'],
            'name'        => $best['name']    ?? ($best['carrier'] ?? 'PaqueterÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­a'),
            'service'     => $best['service'] ?? null,
            'eta'         => $best['eta']     ?? null,
            'price'       => 0.0,
            'store_pays'  => true,
            'carrier_cost'=> (float)($best['price'] ?? 0),
            'auto_applied'=> true,
        ]]);

        return redirect()
            ->route('checkout.payment')
            ->with('ok', 'EnvÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o gratis aplicado (cubierto por la tienda con la paqueterÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­a mÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡s econÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³mica).');
    }

    $selected = session('checkout.shipping', [
        'code'=>null,'price'=>0.0,'name'=>null,'eta'=>null,'service'=>null
    ]);

    return view('checkout.shipping', compact('cart','subtotal','address','carriers','selected'));
}
    public function shippingSelect(Request $req)
    {
        $data = $req->validate([
            'code'    => 'required|string',
            'price'   => 'nullable',
            'name'    => 'nullable',
            'service' => 'nullable',
            'eta'     => 'nullable',
        ]);

        $selectedCode = $data['code'];

        $norm = collect(session('shipping.options_norm', []));
        $opt  = $norm->first(function ($o) use ($selectedCode) {
            return ($o['code'] ?? null) === $selectedCode
                || ($o['id'] ?? null) === $selectedCode;
        });

        if (!$opt) {
            $all = collect(session('shipping.options', []));
            $opt = $all->first(function ($o) use ($selectedCode) {
                return ($o['code'] ?? null) === $selectedCode
                    || ($o['id'] ?? null) === $selectedCode;
            });
        }

        if (!$opt) {
            $postedPrice = (float) str_replace(',', '', (string) $req->input('price', 0));

            if ($selectedCode && $postedPrice > 0) {
                $opt = [
                    'code'    => $selectedCode,
                    'name'    => $req->input('name') ?: 'PaqueterÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­a',
                    'service' => $req->input('service') ?: null,
                    'eta'     => $req->input('eta') ?: null,
                    'price'   => $postedPrice,
                ];
            } else {
                return back()->withErrors(['code'=>'Selecciona una opciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n de envÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o antes de continuar.']);
            }
        }

        if (empty($opt['code']) && !empty($opt['id'])) {
            $opt['code'] = $opt['id'];
        }

        if (empty($opt['eta']) && !empty($opt['days'])) {
            $opt['eta'] = $opt['days'];
        }

        if (empty($opt['price']) && !empty($opt['amount'])) {
            $opt['price'] = $opt['amount'];
        }

        if (empty($opt['name'])) {
            $opt['name'] = $opt['provider'] ?? $opt['carrier'] ?? 'EnvÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o estÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ndar';
        }
        $cart = $this->getCartRows();
        $subtotal  = array_reduce($cart, fn($c,$r)=> $c + ($r['price']*$r['qty']), 0);
        $threshold = $this->threshold;

        if ($subtotal >= $threshold) {
            session(['checkout.shipping' => [
                'code'        => $opt['code'],
                'name'        => $opt['name']    ?? ($opt['carrier'] ?? 'PaqueterÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­a'),
                'service'     => $opt['service'] ?? null,
                'eta'         => $opt['eta']     ?? null,
                'price'       => 0.0,
                'store_pays'  => true,
                'carrier_cost'=> (float)($opt['price'] ?? 0),
                'auto_applied'=> false,
            ]]);
        } else {
            session(['checkout.shipping' => [
                'code'    => $opt['code'],
                'name'    => $opt['name']    ?? ($opt['carrier'] ?? 'PaqueterÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­a'),
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
        if (empty($cart)) return redirect()->route('web.cart.index')->with('ok','Tu carrito estÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ vacÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o.');

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
     * Stripe: Buy now / Carrito (con envÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o en Stripe)
     * =========================================================== */
    public function checkoutItem(Request $req, $item)
    {
        try {
            $model = CatalogItem::query()->findOrFail($item);
            $qty   = max(1, (int)$req->input('qty', 1));
            $price = $model->sale_price ?? $model->price ?? 0;
            if ($price <= 0) return response()->json(['error' => 'Precio invÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡lido.'], 400);

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
                            'description' => 'SKU: ' . ($model->sku ?? 'ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â'),
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
            return response()->json(['error' => 'No se pudo crear la sesiÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n de pago.'], 500);
        }
    }

    public function checkoutCart(Request $req)
    {
        try {
            $cart = $this->getCartRows();
            if (empty($cart)) return response()->json(['error' => 'Tu carrito estÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ vacÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o.'], 400);

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
                            'description' => 'SKU: ' . ($row['sku'] ?? 'ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â'),
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
            return response()->json(['error' => 'No se pudo crear la sesiÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n de pago del carrito.'], 500);
        }
    }

    /* ===========================================================
     * SUCCESS: confirma pago, guarda ORDEN+PARTIDAS+PAGO, timbra y envÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­a correos
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
            $cartForOrder = $this->getCartRows();

            Log::info('CHECKOUT SUCCESS DEBUG', [
                'session_id' => $sessionId,
                'payment_status' => $session->payment_status ?? null,
                'cart_before_clear' => session('cart', []),
            ]);


            // Datos base
            $user    = Auth::user();
            $uid     = $user?->id;
            $emailU  = $user?->email;
            $nameU   = $user?->name;

            $cart    = !empty($cartForOrder ?? []) ? $cartForOrder : $this->getCartRows();
            $subtotal = array_reduce($cart, fn($c,$r)=> $c + (($r['price'] ?? 0) * max(1,(int)($r['qty'] ?? 1))), 0.0);

            // DirecciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n
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

            // EnvÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o
            $shipping   = (array) session('checkout.shipping', []);
            $storePays  = (bool) ($shipping['store_pays'] ?? false);
            $shipAmount = $storePays ? 0.0 : (float) ($shipping['price'] ?? 0.0);
            $total      = round($subtotal + $shipAmount, 2);

            // FacturaciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n
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
                'status'                => $paid ? 'paid' : 'pending',

                'address_json'          => $addressArr,
                'shipping_code'         => $shipping['code']    ?? null,
                'shipping_name'         => $shipping['name']    ?? ($shipping['carrier'] ?? null),
                'shipping_service'      => $shipping['service'] ?? null,
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

            // FORCE_ORDER_ITEMS_FROM_STRIPE
            // Si el carrito de Laravel ya no existe al volver de Stripe, recuperar productos desde Stripe.
            if (empty($cart)) {
                $cart = (array) session('checkout.cart_snapshot', []);
            }

            if (empty($cart) && !empty($sessionId)) {
                try {
                    $lineItems = \Stripe\Checkout\Session::allLineItems($sessionId, ['limit' => 100]);
                    $cart = [];

                    foreach (($lineItems->data ?? []) as $li) {
                        $qty = max(1, (int)($li->quantity ?? 1));

                        $unitAmount = 0;
                        if (isset($li->price) && isset($li->price->unit_amount)) {
                            $unitAmount = ((int)$li->price->unit_amount) / 100;
                        } elseif (isset($li->amount_subtotal) && $qty > 0) {
                            $unitAmount = (((int)$li->amount_subtotal) / 100) / $qty;
                        }

                        $cart[] = [
                            'id'    => null,
                            'name'  => $li->description ?? 'Producto',
                            'sku'   => null,
                            'price' => round((float)$unitAmount, 2),
                            'qty'   => $qty,
                            'image' => null,
                        ];
                    }

                    \Log::info('ORDER ITEMS recuperados desde Stripe line_items', [
                        'session_id' => $sessionId,
                        'count' => count($cart),
                    ]);
                } catch (\Throwable $e) {
                    \Log::warning('No se pudieron recuperar line_items de Stripe para order_items', [
                        'session_id' => $sessionId,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            \Log::info('ORDER ITEMS antes de guardar', [
                'session_id' => $sessionId,
                'order_id' => $order->id ?? null,
                'cart_count' => count($cart),
            ]);
            // 3) Guardar items
            $order->items()->delete();
            foreach ($cart as $row) {
                $qty   = max(1, (int)($row['qty'] ?? 1));
                $price = (float)($row['price'] ?? 0);
                OrderItem::create([
                    'order_id'        => $order->id,
                    'catalog_item_id' => $row['id'] ?? null,
                    'name'            => $row['name'] ?? 'Producto',
                    'sku'             => $row['sku'] ?? null,
                    'price'           => round($price, 2),
                    'qty'             => $qty,
                    'amount'          => round($price * $qty, 2),
                    'currency'        => 'MXN',
                    'tax_rate'        => 0.16,
                    'discount'        => 0,
                    'image_url'       => $row['image'] ?? null,
                    'meta'            => json_encode(['image' => $row['image'] ?? null], JSON_UNESCAPED_UNICODE),
                ]);
            }

            // 4) Registrar pago si estÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ "paid"
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

                // clear_cart_after_paid_order
                Session::forget([
                    'cart',
                    'checkout.address_id',
                    'checkout.address',
                    'checkout.billing_profile_id',
                    'checkout.invoice_required',
                    'checkout.shipping',
                    'shipping.options',
                    'shipping.options_norm',
                    'shipping.selected',
                ]);

                session()->save();
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
                            'description'  => 'EnvÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o',
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
                    Session::flash('ok', 'ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡Pago recibido! Tu factura fue timbrada y enviada a tu correo.');
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
                          ->subject('Tu compra #'.$order->id.' ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â '.config('app.name'));
                    });
                }

                // Admin
                $adminEmail = 'alex.perea1212@gmail.com';
                Mail::send('emails.orders.receipt', $vars + ['isAdmin' => true], function ($m) use ($order, $adminEmail) {
                    $m->to($adminEmail)
                      ->subject('Nueva venta #'.$order->id.' ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â '.config('app.name'));
                });
            } catch (\Throwable $mailE) {
                Log::warning('Order mail error: '.$mailE->getMessage(), ['order_id' => $order->id ?? null]);
            }

            // 7) Limpiar sesiÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n
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

        } catch (\Throwable $e) {
            Log::error('Checkout success error: '.$e->getMessage(), ['session_id'=>$sessionId]);
            Session::flash('error',
                'Pago realizado. Hubo un problema al registrar la orden o generar la factura. '.
                'Si necesitas el CFDI de este pago, contÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ctanos.');
        }

        // IMPORTANTÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂSIMO: pasar $order a la vista para que muestre partidas y totales.
        return view('checkout.success', compact('sessionId', 'invoice', 'order'));
    }


    public function paypalCreate(Request $req)
    {
        \Log::info('PAYPAL DEBUG create entered', [
            'user_id' => auth()->id(),
            'cart' => session('cart'),
            'success_url' => env('PAYPAL_SUCCESS_URL'),
            'cancel_url' => env('PAYPAL_CANCEL_URL'),
        ]);

        try {
            $cart = $this->getCartRows();

            if (empty($cart)) {
                return response()->json(['error' => 'Tu carrito esta vacio.'], 400);
            }

            $subtotal = array_reduce($cart, fn($total, $row) => $total + ((float)($row['price'] ?? 0) * max(1, (int)($row['qty'] ?? 1))), 0);
            $shipping = session('checkout.shipping', ['price' => 0]);
            $shippingAmount = (float)($shipping['price'] ?? 0);
            $total = round($subtotal + $shippingAmount, 2);

            if ($total <= 0) {
                return response()->json(['error' => 'Total invalido para PayPal.'], 400);
            }

            session([
                'checkout.paypal_cart_snapshot' => $cart,
                'checkout.paypal_shipping_snapshot' => $shipping,
            ]);

            $token = $this->paypalAccessToken();
            $base = $this->paypalBaseUrl();

            $payload = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => 'cart_' . (auth()->id() ?? 'guest') . '_' . time(),
                    'amount' => [
                        'currency_code' => 'MXN',
                        'value' => number_format($total, 2, '.', ''),
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => 'MXN',
                                'value' => number_format(round($subtotal, 2), 2, '.', ''),
                            ],
                            'shipping' => [
                                'currency_code' => 'MXN',
                                'value' => number_format(round($shippingAmount, 2), 2, '.', ''),
                            ],
                        ],
                    ],
                ]],
                'application_context' => [
                    'brand_name' => config('app.name', 'Jureto'),
                    'locale' => 'es-MX',
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                    'return_url' => env('PAYPAL_SUCCESS_URL', route('checkout.paypal.success')),
                    'cancel_url' => env('PAYPAL_CANCEL_URL', route('checkout.paypal.cancel')),
                ],
            ];

            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->post($base . '/v2/checkout/orders', $payload);

            if (!$response->successful()) {
                \Log::error('PayPal create order error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json(['error' => 'No se pudo crear la orden de PayPal.'], 500);
            }

            $json = $response->json();
            $approveUrl = collect($json['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

            if (!$approveUrl) {
                return response()->json(['error' => 'PayPal no regreso URL de aprobacion.'], 500);
            }

            \Log::info('PAYPAL DEBUG create success', [
                'paypal_order_id' => $json['id'] ?? null,
                'approve_url' => $approveUrl,
            ]);

            session(['checkout.paypal_order_id' => $json['id'] ?? null]);

            return response()->json(['url' => $approveUrl]);
        } catch (\Throwable $e) {
            \Log::error('PayPal create checkout error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json(['error' => 'No se pudo iniciar PayPal.'], 500);
        }
    }

    public function paypalSuccess(Request $req)
    {
        \Log::info('PAYPAL DEBUG success entered', [
            'query' => $req->query(),
            'session_paypal_order_id' => session('checkout.paypal_order_id'),
            'cart_snapshot' => session('checkout.paypal_cart_snapshot'),
        ]);

        $paypalOrderId = (string) ($req->query('token') ?: session('checkout.paypal_order_id'));
        $invoice = null;
        $order = null;

        if (!$paypalOrderId) {
            return redirect()->route('web.cart.index')->with('ok', 'No se recibio la orden de PayPal.');
        }

        try {
            $token = $this->paypalAccessToken();
            $base = $this->paypalBaseUrl();

            $captureResponse = \Illuminate\Support\Facades\Http::withToken($token)
                ->acceptJson()
                ->withBody('{}', 'application/json')
                ->post($base . '/v2/checkout/orders/' . $paypalOrderId . '/capture');

            if (!$captureResponse->successful()) {
                \Log::error('PayPal capture error', [
                    'paypal_order_id' => $paypalOrderId,
                    'status' => $captureResponse->status(),
                    'body' => $captureResponse->body(),
                ]);

                return redirect()->route('checkout.payment')->with('error', 'PayPal no pudo confirmar el pago.');
            }

            $capture = $captureResponse->json();
            $status = $capture['status'] ?? null;

            \Log::info('PAYPAL DEBUG capture response', [
                'paypal_order_id' => $paypalOrderId,
                'status' => $status,
                'capture_id' => data_get($capture, 'purchase_units.0.payments.captures.0.id'),
            ]);

            if ($status !== 'COMPLETED') {
                return redirect()->route('checkout.payment')->with('error', 'El pago de PayPal no aparece completado.');
            }

            $cart = session('checkout.paypal_cart_snapshot', session('cart', []));
            $shipping = session('checkout.paypal_shipping_snapshot', session('checkout.shipping', ['price' => 0]));
            $subtotal = array_reduce($cart, fn($total, $row) => $total + ((float)($row['price'] ?? 0) * max(1, (int)($row['qty'] ?? 1))), 0);
            $shippingAmount = (float)($shipping['price'] ?? 0);
            $total = round($subtotal + $shippingAmount, 2);

            $captureId = data_get($capture, 'purchase_units.0.payments.captures.0.id');

            $orderData = [];
            $put = function ($column, $value) use (&$orderData) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('orders', $column)) {
                    $orderData[$column] = $value;
                }
            };

            $checkoutAddress = session('checkout.address', []);
            $user = auth()->user();

            $customerName = trim((string) (
                $checkoutAddress['name']
                ?? $checkoutAddress['full_name']
                ?? $checkoutAddress['customer_name']
                ?? $user?->name
                ?? 'Cliente PayPal'
            ));

            $customerEmail = trim((string) (
                $checkoutAddress['email']
                ?? $checkoutAddress['customer_email']
                ?? $user?->email
                ?? 'sandbox-paypal@jureto.local'
            ));

            $customerPhone = trim((string) (
                $checkoutAddress['phone']
                ?? $checkoutAddress['customer_phone']
                ?? $checkoutAddress['telefono']
                ?? ''
            ));

            $customerAddress = trim((string) (
                $checkoutAddress['address']
                ?? $checkoutAddress['street']
                ?? $checkoutAddress['calle']
                ?? ''
            ));

            $put('customer_name', $customerName ?: 'Cliente PayPal');
            $put('customer_email', $customerEmail ?: 'sandbox-paypal@jureto.local');
            $put('customer_phone', $customerPhone);
            $put('customer_address', $customerAddress);

            $put('user_id', auth()->id());
            $put('status', 'paid');
            $put('subtotal', round($subtotal, 2));
            $put('shipping_amount', round($shippingAmount, 2));
            $put('total', $total);
            $put('currency', 'MXN');
            $put('shipping_code', $shipping['code'] ?? null);
            $put('shipping_name', $shipping['name'] ?? null);
            $put('shipping_service', $shipping['service'] ?? null);
            $put('shipping_eta', $shipping['eta'] ?? null);
            $put('shipping_store_pays', $shipping['store_pays'] ?? false);
            $put('shipping_carrier_cost', (float)($shipping['carrier_cost'] ?? 0));

            if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'paypal_order_id')) {
                $order = \App\Models\Order::updateOrCreate(
                    ['paypal_order_id' => $paypalOrderId],
                    array_merge($orderData, ['paypal_capture_id' => $captureId])
                );
            } else {
                $order = \App\Models\Order::create($orderData);
            }

            if ($order) {
                $order->items()->delete();

                foreach ($cart as $row) {
                    $qty = max(1, (int)($row['qty'] ?? 1));
                    $price = (float)($row['price'] ?? 0);

                    \App\Models\OrderItem::create([
                        'order_id' => $order->id,
                        'catalog_item_id' => $row['id'] ?? null,
                        'name' => $row['name'] ?? 'Producto',
                        'sku' => $row['sku'] ?? null,
                        'qty' => $qty,
                        'unit_price' => $price,
                        'total' => round($price * $qty, 2),
                    ]);
                }

                \App\Models\OrderPayment::updateOrCreate(
                    ['order_id' => $order->id, 'provider' => 'paypal'],
                    [
                        'amount' => $total,
                        'currency' => 'MXN',
                        'method' => 'paypal',
                        'status' => 'paid',
                        'raw' => $capture,
                    ]
                );

                \Log::info('PAYPAL DEBUG order saved', [
                    'order_id' => $order->id ?? null,
                    'payment_exists' => \App\Models\OrderPayment::where('order_id', $order->id ?? 0)->where('provider', 'paypal')->exists(),
                ]);

                if (method_exists($order, 'markPaid')) {
                    $order->markPaid();
                }
            }

            \Illuminate\Support\Facades\Session::forget([
                'cart',
                'checkout.address_id',
                'checkout.address',
                'checkout.billing_profile_id',
                'checkout.invoice_required',
                'checkout.shipping',
                'checkout.paypal_cart_snapshot',
                'checkout.paypal_shipping_snapshot',
                'checkout.paypal_order_id',
            ]);

            $sessionId = $paypalOrderId;

            return view('checkout.success', compact('sessionId', 'invoice', 'order'));
        } catch (\Throwable $e) {
            \Log::error('PayPal success error: ' . $e->getMessage(), [
                'paypal_order_id' => $paypalOrderId,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()->route('checkout.payment')->with('error', 'Pago aprobado, pero hubo problema al registrar la orden.');
        }
    }

    public function paypalCancel()
    {
        return redirect()->route('web.cart.index')->with('ok', 'Pago con PayPal cancelado.');
    }

    private function paypalBaseUrl(): string
    {
        return env('PAYPAL_MODE', 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function paypalAccessToken(): string
    {
        $clientId = env('PAYPAL_CLIENT_ID');
        $secret = env('PAYPAL_CLIENT_SECRET');

        if (!$clientId || !$secret) {
            throw new \RuntimeException('Faltan credenciales PAYPAL_CLIENT_ID o PAYPAL_CLIENT_SECRET.');
        }

        $response = \Illuminate\Support\Facades\Http::withBasicAuth($clientId, $secret)
            ->asForm()
            ->post($this->paypalBaseUrl() . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('No se pudo obtener token PayPal: ' . $response->body());
        }

        return (string) $response->json('access_token');
    }

    public function cancel()
    {
        return redirect()->route('web.cart.index')->with('ok', 'Pago cancelado.');
    }

    /* ===========================================================
     * DESCARGAS/RE-ENVÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂO DE CFDI (para la pÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡gina de ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©xito)
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
            'G01'=>'AdquisiciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n de mercancÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­as',
            'G02'=>'Devoluciones, descuentos o bonificaciones',
            'G03'=>'Gastos en general',
            'I01'=>'Construcciones',
            'I02'=>'Mobiliario y equipo de oficina por inversiones',
            'I03'=>'Equipo de transporte',
            'I04'=>'Equipo de computo y accesorios',
            'I05'=>'Dados, troqueles, moldes, matrices y herramental',
            'I06'=>'Comunicaciones telefÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³nicas',
            'I07'=>'Comunicaciones satelitales',
            'I08'=>'Otra maquinaria y equipo',
            'D01'=>'Honorarios mÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©dicos, dentales y gastos hospitalarios',
            'D02'=>'Gastos mÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©dicos por incapacidad o discapacidad',
            'D03'=>'Gastos funerales',
            'D04'=>'Donativos',
            'D05'=>'Intereses reales por crÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©ditos hipotecarios (casa habitaciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n)',
            'D06'=>'Aportaciones voluntarias al SAR',
            'D07'=>'Primas por seguros de gastos mÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©dicos',
            'D08'=>'Gastos de transportaciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n escolar obligatoria',
            'D09'=>'DepÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³sitos para el ahorro, planes de pensiones',
            'D10'=>'Pagos por servicios educativos (colegiaturas)',
            'CP01'=>'Pagos',
            'CN01'=>'NÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³mina',
            'S01' =>'Sin efectos fiscales',
        ];
    }

    private function regimenOptions(): array
    {
        return [
            '601'=>'General de Ley Personas Morales',
            '603'=>'Personas Morales con Fines no Lucrativos',
            '606'=>'Arrendamiento',
            '612'=>'Personas FÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­sicas con Actividades Empresariales y Profesionales',
            '620'=>'Sociedades Cooperativas de ProducciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n que optan por diferir sus ingresos',
            '621'=>'IncorporaciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n Fiscal',
            '622'=>'Actividades AgrÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­colas, Ganaderas, SilvÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­colas y Pesqueras',
            '623'=>'Opcional para Grupos de Sociedades',
            '624'=>'Coordinados',
            '625'=>'RÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©gimen de las Actividades Empresariales con ingresos a travÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©s de Plataformas TecnolÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³gicas',
            '626'=>'RÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©gimen Simplificado de Confianza',
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

    // ===== Helpers de rÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©gimen fiscal vs tipo de RFC =====
    private function pmRegimens(): array { return ['601','603','620','622','623','624','626']; }
    private function pfRegimens(): array { return ['605','606','607','608','610','612','614','615','616','621','625','626']; }

    /**
     * Convierte la selecciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n de envÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o (guardada en sesiÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n) en shipping_options para Stripe Checkout.
     */
    private function buildStripeShippingOptions(): array
    {
        $s = session('checkout.shipping');
        if (!$s || !array_key_exists('price', (array)$s)) {
            return [];
        }

        $amount = (int) round(max(0, (float) ($s['price'] ?? 0)) * 100);

        $display = ($amount === 0)
            ? 'EnvÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o gratis'
            : trim(($s['name'] ?? 'EnvÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o') . (isset($s['service']) && $s['service'] ? ' ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ' . $s['service'] : ''));

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
     * Corrige/normaliza el tax_system (rÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©gimen) con base en el RFC.
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
     * Normaliza la razÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n social SOLO para timbrar (no toca BD)
     */
    private function satLegalName(string $rawName, string $taxId): string
    {
        $taxId = strtoupper(trim($taxId));
        if (in_array($taxId, ['XAXX010101000', 'XEXX010101000'], true)) {
            return 'PUBLICO EN GENERAL';
        }

        $u = mb_strtoupper(trim($rawName), 'UTF-8');

        $map = [
            'ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â'=>'A','ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â°'=>'E','ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â'=>'I','ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“'=>'O','ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡'=>'U','ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾'=>'A','ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¹'=>'E','ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â'=>'I','ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ'=>'O','ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â¦ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ'=>'U',
            'ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡'=>'A','ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©'=>'E','ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­'=>'I','ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³'=>'O','ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âº'=>'U','ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¤'=>'A','ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â«'=>'E','ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¯'=>'I','ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¶'=>'O','ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¼'=>'U'
        ];
        $u = strtr($u, $map);
        $u = preg_replace('/[^A-Z0-9ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¹Ã…â€œ&\s]/u', ' ', $u);

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

    private function fallbackShippingByPostalCode(?string $postalCode): array
    {
        $cp = preg_replace('/\D/', '', (string) $postalCode);

        if (strlen($cp) !== 5) {
            return [
                'amount' => 199,
                'days' => '3 a 7 dÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­as hÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡biles',
                'zone' => 'Nacional',
            ];
        }

        $n = (int) $cp;

        // Toluca, Metepec, San Mateo Atenco, Lerma, Zinacantepec y zona cercana
        if ($n >= 50000 && $n <= 52999) {
            return [
                'amount' => 129,
                'days' => '1 a 3 dÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­as hÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡biles',
                'zone' => 'Zona local',
            ];
        }

        // CDMX
        if ($n >= 1000 && $n <= 16999) {
            return [
                'amount' => 149,
                'days' => '2 a 4 dÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­as hÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡biles',
                'zone' => 'CDMX',
            ];
        }

        // Estado de MÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©xico extendido
        if ($n >= 53000 && $n <= 57999) {
            return [
                'amount' => 159,
                'days' => '2 a 5 dÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­as hÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡biles',
                'zone' => 'Estado de MÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©xico',
            ];
        }

        // Zona centro cercana
        if (($n >= 58000 && $n <= 62999) || ($n >= 76000 && $n <= 76999) || ($n >= 90000 && $n <= 90999)) {
            return [
                'amount' => 179,
                'days' => '3 a 6 dÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­as hÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡biles',
                'zone' => 'Zona centro',
            ];
        }

        // Resto nacional
        return [
            'amount' => 199,
            'days' => '3 a 7 dÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­as hÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡biles',
            'zone' => 'Nacional',
        ];
    }

    private function fallbackShippingOptionByPostalCode(?string $postalCode): array
    {
        $fallback = $this->fallbackShippingByPostalCode($postalCode);

        return [
            'id' => 'manual_cp_' . preg_replace('/\D/', '', (string) $postalCode),
            'provider' => 'EnvÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o estÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ndar',
            'name' => 'EnvÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o estÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ndar',
            'carrier' => 'manual',
            'service' => 'Tarifa por zona',
            'amount' => $fallback['amount'],
            'price' => $fallback['amount'],
            'days' => $fallback['days'],
            'delivery_days' => $fallback['days'],
            'zone' => $fallback['zone'],
            'fallback' => true,
        ];
    }
}
