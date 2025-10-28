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
        $this->scope    = $cfg['scope'] !== '' ? (string)$cfg['scope'] : null;
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

            // Intento B: credenciales en el body (algunas configuraciones lo piden)
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
            ->timeout(15);
    }

    /** GET /carriers */
    public function carriers(): array
    {
        $res = $this->http()->get('carriers');
        return $this->decode($res);
    }

    /** POST /quotations */
public function quote(string $zipTo, array $parcel, ?array $carriers = null, int $waitSeconds = 8): array
{
    // Direcciones: ajusta defaults si quieres (áreas, nombres, etc.)
    $from = [
        'country_code' => 'MX',
        'postal_code'  => (string) config('services.skydropx_pro.origin_cp', '52060'),
        'area_level1'  => $parcel['from_area_level1'] ?? 'Estado de México',
        'area_level2'  => $parcel['from_area_level2'] ?? 'Metepec',
        'area_level3'  => $parcel['from_area_level3'] ?? 'Centro',
        'line1'        => $parcel['from_line1']       ?? 'Calle Demo 123',
        'name'         => $parcel['from_name']        ?? 'Jureto',
        'phone'        => $parcel['from_phone']       ?? '5555555555',
        // 'email'      => $parcel['from_email']       ?? null, // opcional
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
        // 'email'      => $parcel['to_email']        ?? null, // opcional
    ];

    $pkg = [
        'weight'         => (float) ($parcel['weight'] ?? 1),
        'length'         => (float) ($parcel['length'] ?? 10),
        'width'          => (float) ($parcel['width']  ?? 10),
        'height'         => (float) ($parcel['height'] ?? 10),
        'weight_unit'    => 'kg',
        'dimension_unit' => 'cm',
    ];

    // PRO requiere envoltura "quotation" + "parcel" (singular)
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

    // 1) Crear cotización
    $created = $this->http()->post('quotations', $body);
    if ($created->failed()) {
        return $this->decode($created); // 400/422, etc.
    }

    $createdJson = $created->json();
    $id = data_get($createdJson, 'id'); // la API de sb-pro regresa id en la raíz
    if (!$id) {
        // En caso de variante que regrese bajo data.id (por si cambia):
        $id = data_get($createdJson, 'data.id');
    }
    if (!$id) {
        return $this->decode($created); // devolvemos tal cual por transparencia
    }

    // 2) Poll (GET /quotations/{id}) hasta is_completed = true o timeout
    $deadline = microtime(true) + max(1, $waitSeconds);
    $lastGet  = null;

    do {
        $lastGet = $this->http()->get("quotations/{$id}");
        if ($lastGet->ok() && (data_get($lastGet->json(), 'is_completed') === true)) {
            break;
        }
        usleep(400_000); // 400ms entre intentos
    } while (microtime(true) < $deadline);

    return $this->decode($lastGet ?? $created);
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
