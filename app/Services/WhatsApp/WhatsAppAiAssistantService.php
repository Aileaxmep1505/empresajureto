<?php

namespace App\Services\WhatsApp;

use App\Models\Ticket;
use App\Models\User;
use App\Models\WaConversation;
use App\Models\WaHandoff;
use App\Models\WaMessage;
use App\Services\OpenAI\OpenAIResponsesService;
use Illuminate\Support\Str;

class WhatsAppAiAssistantService
{
    public function handleInbound(WaConversation $conversation, ?User $user, WaMessage $incomingMessage): void
    {
        $text = trim((string) $incomingMessage->text);
        $wa = app(WhatsAppService::class);

        if ($text === '') {
            $wa->sendText($conversation->phone, 'Recibí tu mensaje, pero no pude leer el contenido.', $conversation);
            return;
        }

        if (!$user) {
            $wa->sendButtons($conversation->phone, 'Tu número no está vinculado al sistema. ¿Qué deseas hacer?', [
                ['id' => 'human', 'title' => 'Hablar con asesor'],
                ['id' => 'help', 'title' => 'Más ayuda'],
            ], $conversation);
            return;
        }

        if ($this->wantsHuman($text)) {
            $this->handoffToHuman($conversation, 'Solicitado por el usuario');
            $wa->sendText($conversation->phone, 'Listo, te canalicé con un asesor. En breve te atenderán desde el sistema.', $conversation);
            return;
        }

        if ($this->asksPendingTickets($text)) {
            $this->replyPendingTickets($conversation, $user);
            return;
        }

        if ($folio = $this->extractTicketFolio($text)) {
            $this->replyTicketStatus($conversation, $user, $folio);
            return;
        }

        $this->replyWithAi($conversation, $user, $text);
    }

    protected function wantsHuman(string $text): bool
    {
        $text = mb_strtolower($text);

        return str_contains($text, 'asesor')
            || str_contains($text, 'humano')
            || str_contains($text, 'agente')
            || str_contains($text, 'ejecutivo')
            || str_contains($text, 'persona');
    }

    protected function asksPendingTickets(string $text): bool
    {
        $text = mb_strtolower($text);

        return str_contains($text, 'pendiente')
            || str_contains($text, 'pendientes')
            || str_contains($text, 'mis tickets')
            || str_contains($text, 'tickets abiertos')
            || str_contains($text, 'tengo tickets');
    }

    protected function extractTicketFolio(string $text): ?string
    {
        preg_match('/TKT-\d{4}-\d{4,}/i', $text, $matches);
        return $matches[0] ?? null;
    }

    protected function replyPendingTickets(WaConversation $conversation, User $user): void
    {
        $tickets = Ticket::query()
            ->where('assignee_id', $user->id)
            ->whereNotIn('status', ['completado', 'cancelado'])
            ->latest()
            ->limit(5)
            ->get(['folio', 'title', 'status', 'priority']);

        $wa = app(WhatsAppService::class);

        if ($tickets->isEmpty()) {
            $wa->sendText($conversation->phone, 'No tienes tickets pendientes asignados.', $conversation);
            return;
        }

        $lines = ['Tienes estos tickets pendientes asignados:'];

        foreach ($tickets as $t) {
            $lines[] = '• '.$t->folio.' - '.Str::limit($t->title, 45).' ('.$t->status.')';
        }

        $lines[] = '';
        $lines[] = 'También puedes escribir el folio, por ejemplo: TKT-2026-0001';

        $wa->sendText($conversation->phone, implode("\n", $lines), $conversation);
    }

    protected function replyTicketStatus(WaConversation $conversation, User $user, string $folio): void
    {
        $ticket = Ticket::query()
            ->where('folio', $folio)
            ->where(function ($q) use ($user) {
                $q->where('assignee_id', $user->id)
                  ->orWhere('created_by', $user->id);
            })
            ->first(['folio', 'title', 'status', 'priority', 'area']);

        $wa = app(WhatsAppService::class);

        if (!$ticket) {
            $wa->sendText($conversation->phone, 'No encontré el ticket '.$folio.' relacionado contigo.', $conversation);
            return;
        }

        $wa->sendText(
            $conversation->phone,
            "Estado de {$ticket->folio}:\n"
            ."Título: {$ticket->title}\n"
            ."Estado: {$ticket->status}\n"
            ."Prioridad: {$ticket->priority}\n"
            ."Área: {$ticket->area}",
            $conversation
        );
    }

    protected function handoffToHuman(WaConversation $conversation, string $reason): void
    {
        $conversation->update([
            'status' => 'human',
        ]);

        WaHandoff::create([
            'conversation_id' => $conversation->id,
            'reason' => $reason,
            'status' => 'pending',
        ]);
    }

    protected function replyWithAi(WaConversation $conversation, User $user, string $text): void
    {
        $openai = app(OpenAIResponsesService::class);
        $wa = app(WhatsAppService::class);

        $pendingCount = Ticket::query()
            ->where('assignee_id', $user->id)
            ->whereNotIn('status', ['completado', 'cancelado'])
            ->count();

        $system = <<<PROMPT
Eres un asistente interno de WhatsApp para Jureto.
Responde en español.
Sé claro, útil y breve.
No inventes información.
Ayudas a usuarios sobre tickets, seguimiento y atención humana.

Datos reales:
- Usuario: {$user->name}
- Tickets pendientes asignados: {$pendingCount}

Si el usuario pide algo operativo no soportado, dile que puede escribir "asesor".
PROMPT;

        $result = $openai->ask($system, $text);

        if (!$result['ok']) {
            $wa->sendText(
                $conversation->phone,
                'Puedo ayudarte con tickets pendientes, estado de tickets y canalizarte con un asesor. Escribe: pendientes, TKT-2026-0001 o asesor.',
                $conversation
            );
            return;
        }

        $reply = trim((string) ($result['text'] ?? ''));

        if ($reply === '') {
            $reply = 'Puedo ayudarte con tus tickets. Escribe "pendientes", un folio como TKT-2026-0001 o "asesor".';
        }

        $wa->sendText($conversation->phone, $reply, $conversation);
    }
}