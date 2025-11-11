<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\MercadolibreAccount;
use App\Jobs\ProcessMeliNotification;

class MeliController extends Controller
{
    /**
     * 1) Redirección a la página de autorización de Mercado Libre (México).
     *    Asegúrate de que la Redirect URI en el DevCenter coincida con route('meli.callback').
     *    URL de autorización por país:
     *      - MX: https://auth.mercadolibre.com.mx/authorization
     *      - AR: https://auth.mercadolibre.com.ar/authorization
     *      - BR: https://auth.mercadolivre.com.br/authorization
     */
    public function connect()
    {
        $clientId    = config('services.meli.client_id');
        $redirectUri = route('meli.callback');

        if (!$clientId || !$redirectUri) {
            abort(500, 'Falta configurar MELI_CLIENT_ID o la ruta meli.callback.');
        }

        $authBase = 'https://auth.mercadolibre.com.mx/authorization';

        $query = http_build_query([
            'response_type' => 'code',
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            // Si en el DevCenter marcaste offline_access, inclúyelo:
            'scope'         => 'offline_access',
        ]);

        return redirect($authBase.'?'.$query);
    }

    /**
     * 2) Callback OAuth: recibe ?code= y lo intercambia por access_token + refresh_token.
     *    Guarda tokens y expiración en la tabla mercadolibre_accounts.
     */
    public function callback(Request $request)
    {
        $code = $request->get('code');
        if (!$code) {
            Log::warning('ML Callback sin code', ['query' => $request->query()]);
            return redirect('/')->with('error', 'Falta el parámetro "code" en el callback de Mercado Libre.');
        }

        $clientId     = config('services.meli.client_id');
        $clientSecret = config('services.meli.client_secret');
        $redirectUri  = route('meli.callback');

        if (!$clientId || !$clientSecret) {
            return redirect('/')->with('error', 'Falta configurar MELI_CLIENT_ID / MELI_CLIENT_SECRET.');
        }

        $resp = Http::asForm()->post('https://api.mercadolibre.com/oauth/token', [
            'grant_type'    => 'authorization_code',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
        ]);

        if ($resp->failed()) {
            Log::error('ML OAuth token error', ['status' => $resp->status(), 'body' => $resp->body()]);
            return redirect('/')->with('error', 'No se pudo obtener el access_token de Mercado Libre.');
        }

        $data = $resp->json();
        // Estructura típica: access_token, token_type, expires_in, scope, user_id, refresh_token, site_id
        MercadolibreAccount::updateOrCreate(
            ['meli_user_id' => $data['user_id'] ?? null],
            [
                'access_token'            => $data['access_token'],
                'refresh_token'           => $data['refresh_token'] ?? null,
                'access_token_expires_at' => Carbon::now()->addSeconds($data['expires_in'] ?? 3600),
                'site_id'                 => $data['site_id'] ?? null,
            ]
        );

        return redirect('/')->with('success', 'Cuenta de Mercado Libre vinculada correctamente ✅');
    }

    /**
     * 3) Webhook de notificaciones.
     *    Mercado Libre hará POST a esta URL con JSON: { id, resource, user_id, topic, application_id, sent, attempts }
     *    Responder rápido 200 y procesar en background (Job).
     */
    public function notifications(Request $request)
    {
        // IMPORTANTE: Exenta esta ruta del CSRF en app/Http/Middleware/VerifyCsrfToken.php
        // protected $except = ['meli/notifications'];

        $payload = $request->all();
        Log::info('ML Notification received', $payload);

        try {
            // Despacha a cola para procesar el recurso completo en background
            ProcessMeliNotification::dispatch($payload);
        } catch (\Throwable $e) {
            Log::error('ML Notification dispatch error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }

        return response()->json(['status' => 'ok']); // 200
    }
}
