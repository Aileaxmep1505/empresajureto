<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ShippingController extends Controller
{
    protected string $base;
    protected ?string $key;
    protected float $threshold;
    protected string $originPostal;

    public function __construct()
    {
        // v1 (usa DEMO o PROD desde .env/config/services)
        $this->base         = rtrim(config('services.skydropx.base', 'https://api.skydropx.com/v1'), '/');
        $this->key          = config('services.skydropx.key');
        $this->threshold    = (float) env('FREE_SHIPPING_THRESHOLD', 5000);
        $this->originPostal = (string) env('ORIGIN_POSTAL_CODE', '01000');
    }

    /**
     * Devuelve opciones de envío (gratis por umbral o cotiza Skydropx).
     * Espera JSON:
     * {
     *   "subtotal": number,
     *   "to": { "postal_code": "52140", "country_code": "MX" },
     *   "package": { "weight_kg": 1.2, "length_cm": 20, "width_cm": 20, "height_cm": 10 },
     *   "carriers": ["DHL","Fedex"] // opcional
     * }
     */
    public function options(Request $req)
    {
        $subtotal = (float) $req->input('subtotal', 0);

        // 1) Envío gratis por umbral
        if ($subtotal >= $this->threshold) {
            $option = [
                'id'      => 'company_free',
                'carrier' => 'Envío Gratis',
                'service' => 'A cargo de la empresa',
                'price'   => 0.0,
                'eta'     => 'Variable',
            ];

            Session::put('shipping', [
                'selected_id' => $option['id'],
                'price'       => 0.0,
                'label'       => "{$option['carrier']} — {$option['service']}",
                'raw'         => $option,
            ]);

            return response()->json([
                'free_shipping' => true,
                'options'       => [$option],
            ]);
        }

        // 2) Cotizar Skydropx
        $to       = (array) $req->input('to', []);
        $package  = (array) $req->input('package', []);
        $carriers = (array) $req->input('carriers', []);

        $zipTo = isset($to['postal_code']) ? preg_replace('/\D/','', (string) $to['postal_code']) : null;
        if (!$zipTo || strlen($zipTo) < 5) {
            return response()->json(['error' => 'Falta código postal destino (5 dígitos).'], 422);
        }

        $key = trim((string) ($this->key ?? ''));
        if ($key === '') {
            Log::warning('Skydropx: falta API KEY');
            return response()->json(['error' => 'Skydropx no está configurado'], 500);
        }

        // Normaliza paquete (enteros para v1)
        $weight = (int) max(1, ceil((float) ($package['weight_kg'] ?? 1)));
        $height = (int) max(1, ceil((float) ($package['height_cm'] ?? 10)));
        $width  = (int) max(1, ceil((float) ($package['width_cm']  ?? 20)));
        $length = (int) max(1, ceil((float) ($package['length_cm'] ?? 20)));

        $payload = [
            'zip_from' => preg_replace('/\D/','', $this->originPostal),
            'zip_to'   => $zipTo,
            'parcel'   => compact('weight','height','width','length'),
        ];
        if (!empty($carriers)) {
            $payload['carriers'] = array_map(fn($n) => ['name' => (string) $n], $carriers);
        }

        // Intentos inteligentes: (base, header)
        $bases = [
            rtrim($this->base, '/'),
            str_contains($this->base, 'api-demo.')
                ? 'https://api.skydropx.com/v1'
                : 'https://api-demo.skydropx.com/v1',
        ];
        $headersList = [
            // Token
            ['Authorization' => 'Token token=' . $key, 'Accept'=>'application/json', 'Content-Type'=>'application/json'],
            // Bearer
            ['Authorization' => 'Bearer ' . $key,      'Accept'=>'application/json', 'Content-Type'=>'application/json'],
        ];

        $lastResp = null; $usedBase = null; $usedHeaderKind = null;

        try {
            foreach ($bases as $b) {
                foreach ($headersList as $h) {
                    $url  = rtrim($b, '/') . '/quotations';
                    $resp = Http::withHeaders($h)->timeout(20)->post($url, $payload);
                    if ($resp->ok()) {
                        $usedBase = $b;
                        $usedHeaderKind = str_contains($h['Authorization'], 'Token token=') ? 'Token' : 'Bearer';
                        $data  = $resp->json();
                        $rates = $data['data'] ?? $data['rates'] ?? $data['quotations'] ?? $data;
                        if (!is_array($rates)) $rates = [];

                        $options = [];
                        foreach ($rates as $r) {
                            $carrier = $r['carrier'] ?? ($r['attributes']['carrier'] ?? ($r['provider'] ?? 'Paquetería'));
                            $service = $r['service'] ?? ($r['attributes']['service'] ?? ($r['service_level_name'] ?? 'Servicio'));
                            $priceRaw= $r['total_pricing'] ?? ($r['amount_local'] ?? ($r['price'] ?? 0));
                            $price   = is_string($priceRaw) ? (float) preg_replace('/[^\d\.]/', '', $priceRaw) : (float) $priceRaw;
                            $eta     = $r['days'] ?? ($r['estimated_delivery_time'] ?? null);
                            $id      = (string) ($r['id'] ?? ($r['rate_id'] ?? md5(json_encode($r))));

                            $options[] = [
                                'id'      => $id,
                                'carrier' => (string) $carrier,
                                'service' => (string) $service,
                                'price'   => (float) $price,
                                'eta'     => $eta ? (string) $eta : null,
                                'raw'     => $r,
                            ];
                        }
                        usort($options, fn($a,$b) => $a['price'] <=> $b['price']);

                        return response()->json([
                            'free_shipping' => false,
                            'options'       => $options,
                        ]);
                    }

                    // Guarda último resp para logueo si falla todo
                    $lastResp = $resp;
                }
            }

            // Si llegó aquí, todos los intentos fallaron
            $status = $lastResp?->status() ?? 0;
            $body   = $lastResp?->body() ?? null;

            Log::error('Skydropx quotations failed (todos los intentos)', [
                'status'    => $status,
                'body'      => $body,
                'sent'      => $payload,
                'base_tried'=> $bases,
                'key_hint'  => substr($key, 0, 4) . '...' . substr($key, -4),
            ]);

            // Mensaje claro para el frontend si es 401
            if ($status === 401) {
                $hint = str_contains($this->base, 'api-demo.')
                    ? 'Tu API Key parece NO ser de DEMO. Prueba usar https://api.skydropx.com/v1 o genera una key de DEMO.'
                    : 'Tu API Key parece NO ser de PRODUCCIÓN. Prueba usar https://api-demo.skydropx.com/v1 o genera una key de LIVE.';
                return response()->json(['error' => 'Credenciales inválidas para Skydropx. ' . $hint], 502);
            }

            return response()->json(['error' => 'No se pudo cotizar el envío'], 502);

        } catch (\Throwable $e) {
            Log::error('Skydropx exception', [
                'msg'   => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'base'  => $this->base,
            ]);
            return response()->json(['error' => 'Error consultando Skydropx'], 500);
        }
    }

    /**
     * Guarda la selección de envío en sesión (o en tu orden).
     * Espera: option_id, option_label, price, raw (opcional)
     */
    public function select(Request $req)
    {
        $id    = $req->input('option_id');
        $label = $req->input('option_label');
        $price = (float) $req->input('price', 0);

        if (!$id) {
            return response()->json(['error' => 'Falta option_id'], 422);
        }

        Session::put('shipping', [
            'selected_id' => (string) $id,
            'price'       => (float) $price,
            'label'       => (string) ($label ?? ''),
            'raw'         => $req->input('raw'),
        ]);

        return response()->json(['ok' => true, 'shipping' => Session::get('shipping')]);
    }
}
