<?php

namespace App\Http\Controllers;

use App\Models\WaConversation;
use App\Models\WaMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\WhatsAppCloud;
use Illuminate\Support\Str;

class WhatsAppWebhookController extends Controller
{
    /** ✅ Verificación del webhook (GET) */
    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('whatsapp.webhook_verify_token')) {
            return response((string) $challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /** ✅ Recepción de eventos (POST) */
    public function handle(Request $request, WhatsAppCloud $wa)
    {
        $payload = $request->all();

        // ✅ filtra SOLO el phone_number_id de Jureto
        $phoneId = data_get($payload, 'entry.0.changes.0.value.metadata.phone_number_id');
        if ($phoneId !== config('whatsapp.phone_number_id')) {
            Log::warning('WA ignorado (no es Jureto)', ['phone_number_id' => $phoneId]);
            return response()->json(['ok' => true]);
        }

        // 1) Mensajes entrantes
        $messages = (array) data_get($payload, 'entry.0.changes.0.value.messages', []);
        $contacts = (array) data_get($payload, 'entry.0.changes.0.value.contacts', []);

        $contactNameByWaId = [];
        foreach ($contacts as $c) {
            $waId = (string) ($c['wa_id'] ?? '');
            $name = (string) data_get($c, 'profile.name', '');
            if ($waId) $contactNameByWaId[$waId] = $name ?: null;
        }

        foreach ($messages as $m) {
            $from = (string) ($m['from'] ?? '');
            $type = (string) ($m['type'] ?? 'unknown');
            $wamid = (string) ($m['id'] ?? '');

            if (!$from) continue;

            $conv = WaConversation::firstOrCreate(
                ['wa_id' => $from],
                ['name' => $contactNameByWaId[$from] ?? null]
            );

            // actualiza nombre si ahora sí lo trae
            if (!$conv->name && !empty($contactNameByWaId[$from])) {
                $conv->name = $contactNameByWaId[$from];
            }

            $body = null;
            $payloadStore = null;

            if ($type === 'text') {
                $body = (string) data_get($m, 'text.body', '');
            } else {
                // guarda todo por si son imágenes/interactive/etc.
                $payloadStore = $m;
                $body = '[' . strtoupper($type) . ']';
            }

            // evita duplicados por wa_message_id
            $exists = WaMessage::where('conversation_id', $conv->id)
                ->where('wa_message_id', $wamid)
                ->exists();

            if (!$exists) {
                WaMessage::create([
                    'conversation_id' => $conv->id,
                    'direction' => 'in',
                    'wa_message_id' => $wamid ?: null,
                    'from_wa_id' => $from,
                    'to_wa_id' => config('whatsapp.phone_number_id'),
                    'type' => $type,
                    'body' => $body,
                    'payload' => $payloadStore,
                    'status' => null,
                    'sent_at' => now(),
                ]);

                $conv->unread_count = (int)$conv->unread_count + 1;
            }

            $conv->last_message_preview = Str::limit((string)$body, 200);
            $conv->last_message_at = now();
            $conv->save();

            // (Opcional) marcar como leído en WhatsApp cuando lo recibes:
            // if ($wamid) { try { $wa->markAsRead($wamid); } catch (\Throwable $e) {} }
        }

        // 2) Status updates (sent/delivered/read/failed) para outbound
        $statuses = (array) data_get($payload, 'entry.0.changes.0.value.statuses', []);
        foreach ($statuses as $s) {
            $wamid = (string) ($s['id'] ?? '');
            $status = (string) ($s['status'] ?? '');
            $ts = (int) ($s['timestamp'] ?? 0);

            if (!$wamid || !$status) continue;

            $msg = WaMessage::where('wa_message_id', $wamid)->first();
            if (!$msg) continue;

            $msg->status = $status;
            if ($ts > 0) {
                $dt = \Carbon\Carbon::createFromTimestamp($ts);
                if ($status === 'sent') $msg->sent_at = $dt;
                if ($status === 'delivered') $msg->delivered_at = $dt;
                if ($status === 'read') $msg->read_at = $dt;
            }
            $msg->save();
        }

        return response()->json(['ok' => true]);
    }
}