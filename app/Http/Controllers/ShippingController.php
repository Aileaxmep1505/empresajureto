<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Services\SkydropxProClient;
use App\Models\ShippingAddress;

class ShippingController extends Controller
{
    protected float $threshold;
    protected SkydropxProClient $sdk;

    public function __construct(SkydropxProClient $sdk)
    {
        $this->sdk       = $sdk;
        $this->threshold = (float) env('FREE_SHIPPING_THRESHOLD', 5000);
    }

    /**
     * Cotiza opciones de envío a partir de address_id (o payload "to")
     * y dimensiones de paquete. Devuelve opciones para que el cliente elija.
     *
     * Espera JSON:
     * {
     *   "subtotal": number,
     *   "address_id": 123,                         // preferido
     *   // o bien:
     *   "to": {
     *     "postal_code": "06100",
     *     "state": "Ciudad de México",
     *     "municipality": "Cuauhtémoc",
     *     "colony": "Roma Norte",
     *     "street": "Av. Test 456",
     *     "contact_name": "Cliente Demo",
     *     "phone": "5555555555"
     *   },
     *   "package": { "weight_kg": 1.2, "length_cm": 20, "width_cm": 20, "height_cm": 10 },
     *   "carriers": ["dhl","estafeta"] // opcional
     * }
     */
    public function options(Request $req)
    {
        $subtotal   = (float) $req->input('subtotal', 0);
        $carriersIn = array_map(fn($n) => mb_strtolower(trim((string) $n)), (array) $req->input('carriers', []));
        $hasFree    = $subtotal >= $this->threshold;

        // 1) Resuelve dirección (prioriza address_id del usuario autenticado)
        $to = null;
        if ($id = $req->input('address_id')) {
            $addr = ShippingAddress::query()
                ->where('user_id', Auth::id())
                ->whereKey($id)
                ->firstOrFail();

            $to = [
                'postal_code'  => (string) $addr->postal_code,
                'state'        => (string) $addr->state,
                'municipality' => (string) $addr->municipality,
                'colony'       => (string) $addr->colony,
                'street'       => trim($addr->street . ' ' . ($addr->ext_number ? "Ext. {$addr->ext_number}" : '') . ($addr->int_number ? " Int. {$addr->int_number}" : '')),
                'contact_name' => (string) ($addr->contact_name ?: 'Cliente'),
                'phone'        => (string) ($addr->phone ?: '5555555555'),
            ];
        } else {
            // Permite cotizar con payload "to" directo si aún no guardan address
            $to = (array) $req->input('to', []);
        }

        // Validación mínima
        $zipTo = isset($to['postal_code']) ? preg_replace('/\D/', '', (string) $to['postal_code']) : null;
        if (!$zipTo || strlen($zipTo) !== 5) {
            return response()->json(['ok' => false, 'error' => 'Falta código postal destino (5 dígitos).'], 422);
        }

        // 2) Normaliza paquete (kg/cm, PRO permite decimales)
        $p = (array) $req->input('package', []);
        $parcel = [
            'weight' => (float) max(0.01, (float) ($p['weight_kg'] ?? 1)),
            'length' => (float) max(1, (float) ($p['length_cm'] ?? 20)),
            'width'  => (float) max(1, (float) ($p['width_cm']  ?? 20)),
            'height' => (float) max(1, (float) ($p['height_cm'] ?? 10)),

            // FROM (puedes ajustar defaults en tu servicio si deseas)
            'from_area_level1' => 'Estado de México',
            'from_area_level2' => 'Metepec',
            'from_area_level3' => 'Centro',

            // TO (mapeo desde ShippingAddress o payload)
            'to_area_level1' => (string) ($to['state']        ?? 'Ciudad de México'),
            'to_area_level2' => (string) ($to['municipality'] ?? 'Cuauhtémoc'),
            'to_area_level3' => (string) ($to['colony']       ?? 'Roma Norte'),
            'to_line1'       => (string) ($to['street']       ?? 'Domicilio de entrega'),
            'to_name'        => (string) ($to['contact_name'] ?? 'Cliente'),
            'to_phone'       => (string) ($to['phone']        ?? '5555555555'),
        ];

        // 3) Cotiza en PRO (crea quotation + polling hasta is_completed:true)
        try {
            $waitSeconds = 8;
            $res = $this->sdk->quoteBest($zipTo, $parcel, null, $waitSeconds);
            if (!($res['ok'] ?? false)) {
                return response()->json([
                    'ok'              => false,
                    'error'           => 'Error al cotizar envíos',
                    'provider_status' => $res['status'] ?? null,
                    'provider_json'   => $res['json']   ?? null,
                ], 502);
            }

            // 4) Normalizados y ordenados por precio ascendente
            $rates = $res['normalized_rates'] ?? [];

            // 5) Filtro por carriers (si lo envían)
            if (!empty($carriersIn)) {
                $rates = array_values(array_filter($rates, function ($r) use ($carriersIn) {
                    return in_array(mb_strtolower((string) ($r['carrier'] ?? '')), $carriersIn, true);
                }));
            }

            // 6) Construye opciones para el frontend (el cliente elige)
            $options = array_map(function ($r) {
                $days = $r['days'] ?? null;
                // Intenta enriquecer servicio si viene vacío leyendo _raw.provider_service_name (si lo dejaste en tu normalizador)
                $service = $r['service'] ?? (data_get($r, '_raw.provider_service_name') ?: 'Servicio');

                return [
                    'id'       => (string) ($r['id'] ?? md5(json_encode($r))),
                    'carrier'  => (string) ($r['carrier'] ?? 'Paquetería'),
                    'service'  => (string) $service,
                    'price'    => (float)  ($r['price']   ?? 0),
                    'currency' => (string) ($r['currency'] ?? 'MXN'),
                    'eta'      => !is_null($days) ? ($days == 1 ? '1 día' : ($days . ' días')) : null,
                ];
            }, $rates);

            // 7) Si hay envío gratis por umbral, lo incluimos como primera opción,
            //    pero NO seleccionamos nada en sesión. El cliente decide.
            if ($hasFree) {
                array_unshift($options, [
                    'id'       => 'FREE',
                    'carrier'  => 'Jureto',
                    'service'  => 'Envío gratis',
                    'price'    => 0.0,
                    'currency' => 'MXN',
                    'eta'      => null,
                ]);
            }

            return response()->json([
                'ok'               => true,
                'free_shipping'    => $hasFree,
                'free_threshold'   => $this->threshold,
                'is_completed'     => (bool) ($res['json']['is_completed'] ?? true),
                'count'            => count($options),
                'options'          => $options,
            ]);
        } catch (\Throwable $e) {
            Log::error('Skydropx PRO quotation exception', [
                'msg'    => $e->getMessage(),
                'file'   => $e->getFile(),
                'line'   => $e->getLine(),
                'zipTo'  => $zipTo,
                'parcel' => $parcel,
            ]);
            return response()->json(['ok' => false, 'error' => 'Error consultando Skydropx PRO'], 500);
        }
    }

    /**
     * Guarda la selección de envío en sesión (o en tu orden).
     * Espera: option_id, option_label, price, currency (opcional), raw (opcional)
     */
    public function select(Request $req)
    {
        $id       = $req->input('option_id');
        $label    = $req->input('option_label');
        $price    = (float) $req->input('price', 0);
        $currency = (string) $req->input('currency', 'MXN');

        if (!$id) {
            return response()->json(['ok' => false, 'error' => 'Falta option_id'], 422);
        }

        Session::put('shipping', [
            'selected_id' => (string) $id,
            'price'       => $price,
            'currency'    => $currency,
            'label'       => (string) ($label ?? ''),
            'raw'         => $req->input('raw'),
        ]);

        return response()->json(['ok' => true, 'shipping' => Session::get('shipping')]);
    }
}
