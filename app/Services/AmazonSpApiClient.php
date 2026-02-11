<?php
// app/Services/AmazonSpApiClient.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmazonSpApiClient
{
    /**
     * Obtiene access_token de LWA usando refresh_token.
     */
    public function getLwaAccessToken(): string
    {
        $clientId     = config('services.amazon_spapi.lwa_client_id');
        $clientSecret = config('services.amazon_spapi.lwa_client_secret');
        $refreshToken = config('services.amazon_spapi.lwa_refresh_token');

        if (!$clientId || !$clientSecret || !$refreshToken) {
            throw new \RuntimeException('Faltan credenciales LWA (client_id/client_secret/refresh_token) en config/services.php');
        }

        $resp = Http::asForm()
            ->timeout(30)
            ->post('https://api.amazon.com/auth/o2/token', [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
            ]);

        if (!$resp->ok()) {
            Log::warning('Amazon LWA token error', [
                'status' => $resp->status(),
                'body'   => $resp->body(),
            ]);
            throw new \RuntimeException('No se pudo obtener access_token de LWA.');
        }

        $token = $resp->json('access_token');
        if (!$token) {
            throw new \RuntimeException('LWA no devolvió access_token.');
        }

        return $token;
    }

    /**
     * Llama STS AssumeRole para obtener credenciales temporales.
     */
    public function assumeRole(): array
    {
        $accessKey = config('services.amazon_spapi.aws_access_key');
        $secretKey = config('services.amazon_spapi.aws_secret_key');
        $region    = config('services.amazon_spapi.aws_region', 'us-east-1');
        $roleArn   = config('services.amazon_spapi.role_arn');

        if (!$accessKey || !$secretKey || !$roleArn) {
            throw new \RuntimeException('Faltan credenciales AWS o role_arn en config/services.php (amazon_spapi).');
        }

        $host = 'sts.amazonaws.com';
        $uri  = '/';
        $qs   = http_build_query([
            'Action'          => 'AssumeRole',
            'RoleArn'         => $roleArn,
            'RoleSessionName' => 'jureto-spapi-'.date('YmdHis'),
            'Version'         => '2011-06-15',
        ]);

        $url = "https://{$host}{$uri}?{$qs}";

        $amzDate = gmdate('Ymd\THis\Z');
        $date    = gmdate('Ymd');
        $service = 'sts';

        $headers = [
            'host'       => $host,
            'x-amz-date' => $amzDate,
        ];

        $signed = $this->signV4(
            method: 'GET',
            uri: $uri,
            queryString: $qs,
            headers: $headers,
            payload: '',
            accessKey: $accessKey,
            secretKey: $secretKey,
            region: $region,
            service: $service
        );

        $resp = Http::timeout(30)
            ->withHeaders($signed['headers'])
            ->get($url);

        if (!$resp->ok()) {
            Log::warning('AWS STS AssumeRole error', [
                'status' => $resp->status(),
                'body'   => $resp->body(),
            ]);
            throw new \RuntimeException('No se pudo hacer AssumeRole en STS.');
        }

        // STS regresa XML -> parse básico
        $xml = @simplexml_load_string($resp->body());
        if (!$xml) {
            throw new \RuntimeException('No se pudo parsear XML de STS.');
        }

        $ns = $xml->getNamespaces(true);
        // navegación segura
        $result = $xml->children($ns[''])->AssumeRoleResult ?? null;
        if (!$result) $result = $xml->AssumeRoleResult ?? null;

        $creds = $result->Credentials ?? null;
        if (!$creds) {
            // intenta ruta alternativa
            $creds = $xml->AssumeRoleResult->Credentials ?? null;
        }
        if (!$creds) {
            throw new \RuntimeException('STS no devolvió Credentials.');
        }

        $tmpAccess = (string)($creds->AccessKeyId ?? '');
        $tmpSecret = (string)($creds->SecretAccessKey ?? '');
        $tmpToken  = (string)($creds->SessionToken ?? '');

        if (!$tmpAccess || !$tmpSecret || !$tmpToken) {
            throw new \RuntimeException('Credenciales temporales incompletas desde STS.');
        }

        return [
            'access_key' => $tmpAccess,
            'secret_key' => $tmpSecret,
            'token'      => $tmpToken,
        ];
    }

    /**
     * Request firmado a SP-API.
     * Retorna ['ok'=>bool,'status'=>int,'json'=>array|null,'body'=>string]
     */
    public function request(string $method, string $path, array $query = [], ?array $jsonBody = null): array
    {
        $endpoint = rtrim(config('services.amazon_spapi.endpoint'), '/');
        $region   = config('services.amazon_spapi.aws_region', 'us-east-1');

        if (!$endpoint) throw new \RuntimeException('Falta amazon_spapi.endpoint en config/services.php');

        $lwaAccessToken = $this->getLwaAccessToken();
        $sts            = $this->assumeRole();

        $url = $endpoint.$path;
        if (!empty($query)) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($query);
        }

        $host = parse_url($endpoint, PHP_URL_HOST);
        $uri  = $path;
        $qs   = !empty($query) ? http_build_query($query) : '';

        $amzDate = gmdate('Ymd\THis\Z');
        $service = 'execute-api';

        $payload = $jsonBody ? json_encode($jsonBody, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : '';
        if ($payload === false) $payload = '';

        $headers = [
            'host'                   => $host,
            'x-amz-date'             => $amzDate,
            'x-amz-security-token'   => $sts['token'],
            'content-type'           => 'application/json',
            // SP-API:
            'x-amz-access-token'     => $lwaAccessToken,
        ];

        $signed = $this->signV4(
            method: strtoupper($method),
            uri: $uri,
            queryString: $qs,
            headers: $headers,
            payload: $payload,
            accessKey: $sts['access_key'],
            secretKey: $sts['secret_key'],
            region: $region,
            service: $service
        );

        $http = Http::timeout(60)->withHeaders($signed['headers']);

        $resp = match (strtoupper($method)) {
            'POST'   => $http->withBody($payload, 'application/json')->post($url),
            'PUT'    => $http->withBody($payload, 'application/json')->put($url),
            'PATCH'  => $http->withBody($payload, 'application/json')->patch($url),
            'DELETE' => $http->delete($url),
            default  => $http->get($url),
        };

        $body = (string)$resp->body();
        $json = null;

        // intenta json
        try { $json = $resp->json(); } catch (\Throwable $e) {}

        return [
            'ok'     => $resp->ok(),
            'status' => $resp->status(),
            'json'   => is_array($json) ? $json : null,
            'body'   => $body,
        ];
    }

    /* ===========================
     *  Firma SigV4 (helper)
     * =========================== */

    private function signV4(
        string $method,
        string $uri,
        string $queryString,
        array $headers,
        string $payload,
        string $accessKey,
        string $secretKey,
        string $region,
        string $service
    ): array {
        $algorithm = 'AWS4-HMAC-SHA256';
        $amzDate   = $headers['x-amz-date'] ?? gmdate('Ymd\THis\Z');
        $date      = substr($amzDate, 0, 8);

        // Canonical headers
        $canonHeaders = [];
        foreach ($headers as $k => $v) {
            $lk = strtolower(trim($k));
            $vv = preg_replace('/\s+/', ' ', trim((string)$v));
            $canonHeaders[$lk] = $vv;
        }
        ksort($canonHeaders);

        $canonicalHeadersStr = '';
        $signedHeadersArr    = [];
        foreach ($canonHeaders as $k => $v) {
            $canonicalHeadersStr .= $k.':'.$v."\n";
            $signedHeadersArr[] = $k;
        }
        $signedHeaders = implode(';', $signedHeadersArr);

        $payloadHash = hash('sha256', $payload);

        $canonicalRequest =
            strtoupper($method)."\n".
            $this->normalizeUri($uri)."\n".
            $queryString."\n".
            $canonicalHeadersStr."\n".
            $signedHeaders."\n".
            $payloadHash;

        $credentialScope = $date.'/'.$region.'/'.$service.'/aws4_request';

        $stringToSign =
            $algorithm."\n".
            $amzDate."\n".
            $credentialScope."\n".
            hash('sha256', $canonicalRequest);

        $signingKey = $this->getSignatureKey($secretKey, $date, $region, $service);
        $signature  = hash_hmac('sha256', $stringToSign, $signingKey);

        $authorization =
            $algorithm.' '.
            'Credential='.$accessKey.'/'.$credentialScope.', '.
            'SignedHeaders='.$signedHeaders.', '.
            'Signature='.$signature;

        $outHeaders = [];
        foreach ($canonHeaders as $k => $v) {
            $outHeaders[$k] = $v;
        }
        $outHeaders['authorization'] = $authorization;

        // regresa headers en el formato original que Http espera
        $finalHeaders = [];
        foreach ($outHeaders as $k => $v) {
            $finalHeaders[$this->headerCase($k)] = $v;
        }

        return [
            'headers' => $finalHeaders,
        ];
    }

    private function getSignatureKey(string $key, string $date, string $region, string $service): string
    {
        $kDate    = hash_hmac('sha256', $date, 'AWS4'.$key, true);
        $kRegion  = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        return hash_hmac('sha256', 'aws4_request', $kService, true);
    }

    private function normalizeUri(string $uri): string
    {
        if ($uri === '') return '/';
        if ($uri[0] !== '/') $uri = '/'.$uri;
        // no urlencode agresivo; SP-API suele venir ya normal.
        return $uri;
    }

    private function headerCase(string $k): string
    {
        // deja algunos comunes bonitos
        $map = [
            'host' => 'Host',
            'x-amz-date' => 'X-Amz-Date',
            'x-amz-security-token' => 'X-Amz-Security-Token',
            'x-amz-access-token' => 'x-amz-access-token',
            'content-type' => 'Content-Type',
            'authorization' => 'Authorization',
        ];
        $lk = strtolower($k);
        return $map[$lk] ?? $k;
    }
}
