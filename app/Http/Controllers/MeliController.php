<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Models\MercadolibreAccount; // crea este modelo si no lo tienes (tabla para tokens)

class MeliController extends Controller
{
    public function callback(Request $request)
    {
        // GET https://ai.jureto.com.mx/meli/callback?code=XXXX
        $code = $request->get('code');
        if (!$code) {
            return redirect('/')->with('error', 'Falta code en callback de Mercado Libre.');
        }

        $clientId     = config('services.meli.client_id');
        $clientSecret = config('services.meli.client_secret');
        $redirectUri  = route('meli.callback');

        $resp = Http::asForm()->post('https://api.mercadolibre.com/oauth/token', [
            'grant_type'    => 'authorization_code',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
        ]);

        if ($resp->failed()) {
            Log::error('ML OAuth token error', ['body' => $resp->body()]);
            return redirect('/')->with('error', 'No se pudo obtener el access_token de Mercado Libre.');
        }

        $data = $resp->json();
        // Estructura típica: access_token, token_type, expires_in, scope, user_id, refresh_token
        MercadolibreAccount::updateOrCreate(
            ['meli_user_id' => $data['user_id'] ?? null],
            [
                'access_token'            => $data['access_token'],
                'refresh_token'           => $data['refresh_token'] ?? null,
                'access_token_expires_at' => Carbon::now()->addSeconds($data['expires_in'] ?? 3600),
                'site_id'                 => $data['site_id'] ?? null,
            ]
        );

        return redirect('/')->with('success', 'Cuenta de Mercado Libre vinculada correctamente.');
    }

    public function notifications(Request $request)
    {
        // POST https://ai.jureto.com.mx/meli/notifications
        // Mercado Libre envía JSON con: { "id": "NN", "resource": "/orders/123", "user_id": 000, "topic": "orders", "application_id": "...", "sent": "...", "attempts": 1 }
        $payload = $request->all();

        // Log para depurar
        Log::info('ML Notification received', $payload);

        // Responder rápido 200 OK para evitar reintentos excesivos
        // (Puedes despachar un Job para procesar en background)
        try {
            $topic    = $payload['topic']    ?? null;
            $resource = $payload['resource'] ?? null;

            if ($topic && $resource) {
                // (Opcional) ejemplo: fetch del recurso para sincronizar
                $account = MercadolibreAccount::first(); // ajusta si manejas múltiples
                if ($account) {
                    $url  = 'https://api.mercadolibre.com' . $resource;
                    $resp = Http::withToken($account->access_token)->get($url);
                    if ($resp->ok()) {
                        $data = $resp->json();
                        // TODO: según $topic, actualiza tu DB:
                        // - items: sincroniza publicación
                        // - orders: crear/actualizar pedido interno
                        // - shipments: estado de envío
                        // - messages: inbox post-venta
                        Log::info('ML Resource fetched', ['topic' => $topic, 'resource' => $resource, 'sample' => array_slice($data, 0, 5)]);
                    } else {
                        Log::warning('ML Resource fetch failed', ['resource' => $resource, 'status' => $resp->status(), 'body' => $resp->body()]);
                    }
                } else {
                    Log::warning('ML Notification: no account configured to fetch resource.');
                }
            }
        } catch (\Throwable $e) {
            Log::error('ML Notification processing error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }

        return response()->json(['status' => 'ok']); // 200
    }
}
