<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\HelpTicket;
use App\Models\HelpMessage;
use App\Services\AiService;

class HelpCenterController extends Controller
{
    public function __construct(private AiService $ai) {}

    public function create()
    {
        return view('web.ayuda.create', ['ticket' => null]);
    }

    public function start(Request $r)
    {
        $r->validate([
            'subject'  => 'required|string|max:200',
            'category' => 'nullable|string|max:100',
            'message'  => 'required|string|min:2',
        ]);

        $ticket = HelpTicket::create([
            'user_id'          => Auth::id(),
            'subject'          => $r->subject,
            'category'         => $r->category ?: null,
            'priority'         => 'normal',
            'status'           => 'open',
            'last_activity_at' => now(),
            'resolved_by_id'   => null,
        ]);

        // Primer mensaje del usuario
        $mUser = HelpMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_type' => 'user',
            'sender_id'   => Auth::id(),
            'body'        => $r->message,
            'meta'        => null,
            'is_solution' => false,
        ]);

        // Historial inicial
        $history = [
            ['role' => 'user', 'content' => $mUser->body],
        ];

        // IA con historial
        $aiText = $this->ai->helpdeskReply($r->message, $ticket, $history);

        // Parseo (opcional) de bloque AI_META
        [$aiBody, $aiMeta] = $this->splitBodyAndMeta($aiText);

        $mAi = HelpMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_type' => 'ai',
            'sender_id'   => null,
            'body'        => $aiBody,
            'meta'        => $aiMeta, // <- si el modelo devolvió AI_META, lo guardamos
            'is_solution' => false,
        ]);

        $ticket->update(['last_activity_at' => now(), 'status' => 'waiting_user']);

        return response()->json([
            'ok'      => true,
            'ticket'  => ['id' => $ticket->id, 'status' => $ticket->status],
            'messages'=> [
                [
                    'type'       => 'user',
                    'body'       => $mUser->body,
                    'created_at' => $mUser->created_at->format('d/m/Y H:i'),
                    'is_solution'=> false
                ],
                [
                    'type'       => 'ai',
                    'body'       => $mAi->body,
                    'created_at' => $mAi->created_at->format('d/m/Y H:i'),
                    'is_solution'=> false
                ],
            ],
        ]);
    }

    public function show(HelpTicket $ticket)
    {
        $this->ensureCanAccess($ticket);
        $ticket->load(['messages' => fn($q) => $q->orderBy('created_at')]);
        return view('web.ayuda.create', ['ticket' => $ticket]);
    }

    public function message(Request $r, HelpTicket $ticket)
    {
        $this->ensureCanAccess($ticket);

        $r->validate(['message' => 'required|string|min:1']);

        // Mensaje del usuario
        $userMsg = HelpMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_type' => 'user',
            'sender_id'   => Auth::id(),
            'body'        => $r->message,
            'meta'        => null,
            'is_solution' => false,
        ]);

        // Cargar historial completo (solo user/assistant)
        $prev = HelpMessage::where('ticket_id', $ticket->id)
            ->orderBy('created_at')
            ->get(['sender_type','body']);

        $history = [];
        foreach ($prev as $m) {
            if ($m->sender_type === 'ai') {
                $history[] = ['role' => 'assistant', 'content' => $m->body];
            } elseif ($m->sender_type === 'user') {
                $history[] = ['role' => 'user', 'content' => $m->body];
            }
            // omitimos 'system' y 'agent'
        }

        // IA con historial
        $aiText = $this->ai->helpdeskReply($r->message, $ticket, $history);

        // Parseo (opcional) de bloque AI_META
        [$aiBody, $aiMeta] = $this->splitBodyAndMeta($aiText);

        $aiMsg = HelpMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_type' => 'ai',
            'sender_id'   => null,
            'body'        => $aiBody,
            'meta'        => $aiMeta, // <- guardamos meta estructurada
            'is_solution' => false,
        ]);

        $ticket->update(['last_activity_at' => now(), 'status' => 'waiting_user']);

        return response()->json([
            'ok'       => true,
            'status'   => $ticket->status,
            'appended' => [
                [
                    'type'       => 'user',
                    'body'       => $userMsg->body,
                    'created_at' => $userMsg->created_at->format('d/m/Y H:i'),
                    'is_solution'=> false
                ],
                [
                    'type'       => 'ai',
                    'body'       => $aiMsg->body,
                    'created_at' => $aiMsg->created_at->format('d/m/Y H:i'),
                    'is_solution'=> false
                ],
            ],
        ]);
    }

    public function escalar(Request $r, HelpTicket $ticket)
    {
        $this->ensureCanAccess($ticket);

        $ticket->update(['status' => 'escalated', 'last_activity_at' => now()]);

        $sys = HelpMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_type' => 'system',
            'sender_id'   => null,
            'body'        => "Tu caso fue escalado a un asesor humano. Te contactaremos aquí mismo en cuanto tome el caso.",
            'meta'        => ['escalated_by' => Auth::id()],
            'is_solution' => false,
        ]);

        return response()->json([
            'ok'       => true,
            'status'   => $ticket->status,
            'appended' => [[
                'type'       => 'system',
                'body'       => $sys->body,
                'created_at' => $sys->created_at->format('d/m/Y H:i'),
                'is_solution'=> false
            ]],
        ]);
    }

    private function ensureCanAccess(HelpTicket $ticket): void
    {
        $user    = Auth::user();
        $isOwner = $ticket->user_id === ($user?->id);
        $hasRole = method_exists($user, 'hasAnyRole') ? $user->hasAnyRole(['admin','soporte','profesor']) : false;
        abort_unless($isOwner || $hasRole, 403, 'No autorizado.');
    }

    // === Helper: separa el texto visible del bloque AI_META JSON (si existe) ===
    private function splitBodyAndMeta(?string $text): array
    {
        $text    = (string) $text;
        $pattern = '/```AI_META\s*(\{.*?\})\s*```/s';
        $metaArr = null;

        if (preg_match($pattern, $text, $m)) {
            $json    = trim($m[1] ?? '');
            $decoded = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $metaArr = $decoded;
                $text    = trim(str_replace($m[0], '', $text)); // quitamos el bloque del texto visible
            }
        }
        return [$text, $metaArr];
    }

    // Para Tinker / pruebas manuales
    public function debugAiReply(string $prompt, HelpTicket $ticket): string
    {
        $prev = HelpMessage::where('ticket_id', $ticket->id)
            ->orderBy('created_at')
            ->get(['sender_type','body']);

        $history = [];
        foreach ($prev as $m) {
            if ($m->sender_type === 'ai')      $history[] = ['role'=>'assistant','content'=>$m->body];
            elseif ($m->sender_type === 'user')$history[] = ['role'=>'user','content'=>$m->body];
        }
        return $this->ai->helpdeskReply($prompt, $ticket, $history);
    }
}
