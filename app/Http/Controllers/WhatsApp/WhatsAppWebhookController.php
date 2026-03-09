<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\WhatsAppInboundService;
use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    public function handle(Request $request, WhatsAppInboundService $inbound)
    {
        if ($request->isMethod('get')) {
            $verifyToken = config('whatsapp.webhook_verify_token');

            if (
                $request->get('hub_mode') === 'subscribe' &&
                $request->get('hub_verify_token') === $verifyToken
            ) {
                return response($request->get('hub_challenge'), 200);
            }

            return response('Token inválido', 403);
        }

        $inbound->process($request->all());

        return response()->json(['ok' => true]);
    }
}