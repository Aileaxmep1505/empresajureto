<?php
// app/Services/MeliHttp.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\MercadolibreAccount;
use Carbon\Carbon;

class MeliHttp
{
    /**
     * Retorna un PendingRequest listo para llamar a la API de Mercado Libre:
     * - Authorization: Bearer <token>
     * - Accept: application/json
     * - Content-Type: application/json (asJson)
     *
     * Nota: SOLO el refresh token se hace con asForm().
     */
    public static function withFreshToken()
    {
        $acc = MercadolibreAccount::firstOrFail();

        // Si el token aún es válido, usarlo
        if ($acc->access_token_expires_at && $acc->access_token_expires_at->isFuture()) {
            return Http::withToken($acc->access_token)
                ->acceptJson()
                ->asJson()
                ->timeout(35);
        }

        // Renovar token (OAuth) -> asForm requerido por ML
        $resp = Http::asForm()
            ->acceptJson()
            ->timeout(35)
            ->post('https://api.mercadolibre.com/oauth/token', [
                'grant_type'    => 'refresh_token',
                'client_id'     => config('services.meli.client_id'),
                'client_secret' => config('services.meli.client_secret'),
                'refresh_token' => $acc->refresh_token,
            ]);

        $j = (array) $resp->json();

        // Si falla el refresh, lanzamos excepción para que se vea en logs
        if (!$resp->ok() || empty($j['access_token'])) {
            throw new \RuntimeException('No se pudo refrescar el access_token de Mercado Libre: ' . $resp->body());
        }

        $acc->update([
            'access_token'            => $j['access_token'],
            'refresh_token'           => $j['refresh_token'] ?? $acc->refresh_token,
            'access_token_expires_at' => Carbon::now()->addSeconds((int) ($j['expires_in'] ?? 3500)),
        ]);

        // Request listo para API ML
        return Http::withToken($acc->access_token)
            ->acceptJson()
            ->asJson()
            ->timeout(35);
    }
}
