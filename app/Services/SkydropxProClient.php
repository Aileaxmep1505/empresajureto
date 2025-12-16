<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class SkydropxProClient
{
    private string $clientId;
    private string $secret;
    private string $tokenUrl;
    private string $apiBase;
    private ?string $scope;

    public function __construct()
    {
        $cfg = config('services.skydropx_pro', []);
        $this->clientId = (string) ($cfg['client_id'] ?? '');
        $this->secret   = (string) ($cfg['secret'] ?? '');
        $this->tokenUrl = (string) ($cfg['token_url'] ?? '');
        $this->apiBase  = rtrim((string) ($cfg['api_base'] ?? ''), '/') . '/';
        $this->scope    = ($cfg['scope'] ?? '') !== '' ? (string)$cfg['scope'] : null;
    }

    /** Obtiene y cachea el access_token usando client_credentials */
    public function accessToken(): string
    {
        $cacheKey = 'skydropx_pro_token_' . sha1($this->clientId . '|' . $this->tokenUrl);

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            // Intento A: BasicAuth
            $resp = Http::asForm()
                ->withBasicAuth($this->clientId, $this->secret)
                ->post($this->tokenUrl, array_filter([
                    'grant_type' => 'client_credentials',
                    'scope'      => $this->scope,
                ]));

            // Intento B: credenciales en el body
            if ($resp->failed() || !data_get($resp->json(), 'access_token')) {
                $resp = Http::asForm()->post($this->tokenUrl, array_filter([
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->secret,
                    'scope'         => $this->scope,
                ]));
            }

            if ($resp->failed()) {
                throw new \RuntimeException('Skydropx PRO token error: HTTP ' . $resp->status() . ' ' . substr($resp->body(), 0, 400));
            }

            $token   = (string) data_get($resp->json(), 'access_token');
            $expires = (int)   data_get($resp->json(), 'expires_in', 3600);

            if (!$token) {
                throw new \RuntimeException('Skydropx PRO token vacío: ' . substr($resp->body(), 0, 400));
            }

            // Reajusta TTL con colchón de 60s
            $ttl = max($expires - 60, 60);
            Cache::put('skydropx_pro_token_' . sha1($this->clientId . '|' . $this->tokenUrl), $token, now()->addSeconds($ttl));

            return $token;
        });
    }

    /** Cliente HTTP con Bearer */
    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withToken($this->accessToken())
            ->acceptJson()
            ->baseUrl($this->apiBase)
            ->timeout(20);
    }

    /** GET /carriers */
    public function carriers(): array
    {
        $res = $this->http()->get('carriers');
        return $this->decode($res);
    }

    /** POST /quotations (PRO) con polling corto hasta is_completed */
    public function quote(string $zipTo, array $parcel, ?array $carriers = null, int $waitSeconds = 8): array
    {
        $from = [
            'country_code' => 'MX',
            'postal_code'  => (string) config('services.skydropx_pro.origin_cp', '52060'),
            'area_level1'  => $parcel['from_area_level1'] ?? 'Estado de México',
            'area_level2'  => $parcel['from_area_level2'] ?? 'Metepec',
            'area_level3'  => $parcel['from_area_level3'] ?? 'Centro',
            'line1'        => $parcel['from_line1']       ?? 'Calle Demo 123',
            'name'         => $parcel['from_name']        ?? 'Jureto',
            'phone'        => $parcel['from_phone']       ?? '5555555555',
        ];

        $to = [
            'country_code' => 'MX',
            'postal_code'  => $zipTo,
            'area_level1'  => $parcel['to_area_level1'] ?? 'Ciudad de México',
            'area_level2'  => $parcel['to_area_level2'] ?? 'Cuauhtémoc',
            'area_level3'  => $parcel['to_area_level3'] ?? 'Roma Norte',
            'line1'        => $parcel['to_line1']       ?? 'Av. Test 456',
            'name'         => $parcel['to_name']        ?? 'Cliente Demo',
            'phone'        => $parcel['to_phone']       ?? '5555555555',
        ];

        $pkg = [
            'weight'         => (float) ($parcel['weight'] ?? 1),
            'length'         => (float) ($parcel['length'] ?? 10),
            'width'          => (float) ($parcel['width']  ?? 10),
            'height'         => (float) ($parcel['height'] ?? 10),
            'weight_unit'    => 'kg',
            'dimension_unit' => 'cm',
        ];

        $body = [
            'quotation' => [
                'address_from' => array_filter($from, fn($v) => !is_null($v)),
                'address_to'   => array_filter($to,   fn($v) => !is_null($v)),
                'parcel'       => $pkg,
            ],
        ];
        if ($carriers) {
            $body['quotation']['carriers'] = array_values($carriers);
        }

        $created = $this->http()->post('quotations', $body);
        if ($created->failed()) {
            return $this->decode($created);
        }

        $createdJson = $created->json();
        $id = data_get($createdJson, 'id') ?: data_get($createdJson, 'data.id');
        if (!$id) {
            return $this->decode($created);
        }

        $deadline = microtime(true) + max(1, $waitSeconds);
        $lastGet  = null;

        do {
            $lastGet = $this->http()->get("quotations/{$id}");
            if ($lastGet->ok() && (data_get($lastGet->json(), 'is_completed') === true)) {
                break;
            }
            usleep(400_000);
        } while (microtime(true) < $deadline);

        return $this->decode($lastGet ?? $created);
    }

    public function quoteBest(string $zipTo, array $parcel, ?array $carriers = null, int $waitSeconds = 8): array
    {
        $res = $this->quote($zipTo, $parcel, $carriers, $waitSeconds);
        if (!($res['ok'] ?? false)) return [];

        $json  = $res['json'] ?? [];
        $rates = data_get($json, 'rates', []);
        if (!is_array($rates) || empty($rates)) {
            $rates = data_get($json, 'data.rates', []);
        }
        if (!is_array($rates)) $rates = [];

        $options = [];
        foreach ($rates as $r) {
            $success = data_get($r, 'success');
            if ($success === false) continue;

            $id = (string) (data_get($r, 'id') ?? data_get($r, 'rate_id') ?? md5(json_encode($r)));

            $carrier = (string) (
                data_get($r, 'provider_name') ??
                data_get($r, 'attributes.provider') ??
                data_get($r, 'carrier') ??
                'carrier'
            );

            $service = (string) (
                data_get($r, 'provider_service_name') ??
                data_get($r, 'service') ??
                data_get($r, 'service_level_name') ??
                data_get($r, 'attributes.service_level_name') ??
                data_get($r, 'servicelevel.name') ??
                'Servicio'
            );

            $days = data_get($r, 'days');
            if ($days === null) $days = data_get($r, 'attributes.delivery_days');

            $currency = (string) (
                data_get($r, 'currency_code') ??
                data_get($r, 'currency') ??
                data_get($r, 'attributes.currency') ??
                'MXN'
            );

            $priceRaw = data_get($r, 'total') ??
                        data_get($r, 'amount') ??
                        data_get($r, 'total_pricing') ??
                        data_get($r, 'amount_local') ??
                        data_get($r, 'price') ?? 0;

            $price = is_numeric($priceRaw)
                ? (float) $priceRaw
                : (float) preg_replace('/[^\d\.]/', '', (string)$priceRaw);

            if ($price <= 0) continue;

            $options[] = [
                'id'       => $id,
                'carrier'  => $carrier,
                'service'  => $service,
                'days'     => is_null($days) ? null : (int) $days,
                'currency' => $currency ?: 'MXN',
                'price'    => $price,
                '_raw'     => $r,
            ];
        }

        usort($options, fn($a, $b) => $a['price'] <=> $b['price']);
        return $options;
    }

    /* ============================================================
     * ✅ COMPRA DE GUÍA + TRACKING (ENDPOINTS A AJUSTAR SI TU DOC VARÍA)
     * ============================================================ */

    /** Comprar guía con quotation_id + rate_id */
    public function buyLabel(string $quotationId, string $rateId): array
    {
        // 🔧 Si tu doc dice otro endpoint, cambia SOLO "labels"
        $body = [
            'label' => [
                'quotation_id' => $quotationId,
                'rate_id'      => $rateId,
            ],
        ];

        $res = $this->http()->post('labels', $body);
        return $this->decode($res);
    }

    /** Tracking por número de guía */
    public function trackingByCode(string $code): array
    {
        // 🔧 Si tu doc dice otro endpoint, cambia SOLO esta ruta
        $res = $this->http()->get("trackings/{$code}");
        return $this->decode($res);
    }

    /** Util: decodifica respuesta */
    private function decode(Response $res): array
    {
        return [
            'ok'     => $res->ok(),
            'status' => $res->status(),
            'json'   => rescue(fn() => $res->json(), null, false),
            'raw'    => $res->body(),
        ];
    }
}
