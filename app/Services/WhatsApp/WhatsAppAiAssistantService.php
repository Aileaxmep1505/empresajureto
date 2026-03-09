<?php

namespace App\Services\WhatsApp;

use App\Models\AgendaEvent;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WaConversation;
use App\Models\WaHandoff;
use App\Models\WaMessage;
use App\Services\OpenAI\OpenAIResponsesService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WhatsAppAiAssistantService
{
    public function handleInbound(WaConversation $conversation, ?User $user, WaMessage $incomingMessage): void
    {
        $text = trim((string) $incomingMessage->text);
        $textLower = mb_strtolower($text);
        $wa = app(WhatsAppService::class);

        if ($text === '') {
            $wa->sendText(
                $conversation->phone,
                'Recibí tu mensaje, pero no pude leer el contenido. Puedes escribir algo como: pendientes, cuál urge más, TKT-2026-0001 o asesor.',
                $conversation
            );
            return;
        }

        if (!$user) {
            $this->replyForUnknownUser($conversation, $text);
            return;
        }

        if ($this->wantsHuman($textLower)) {
            $this->handoffToHuman($conversation, 'Solicitado por el usuario');
            $wa->sendText(
                $conversation->phone,
                'Listo, ya te canalicé con un asesor humano.',
                $conversation
            );
            return;
        }

        $isInternal = $this->isInternalUser($user);

        if (!$isInternal) {
            $this->handleExternalUser($conversation, $user, $text, $textLower);
            return;
        }

        // =============================
        // Usuarios internos
        // =============================

        if ($this->isGreeting($textLower)) {
            $wa->sendText(
                $conversation->phone,
                'Hola '.$this->firstName($user->name).'. Puedo ayudarte con tickets, prioridad, agenda y dudas generales del sistema. Ejemplos: pendientes, cuál urge más, qué tengo hoy, detalle TKT-2026-0013, asesor.',
                $conversation
            );
            return;
        }

        // Follow-up contextual antes de ir a la IA genérica
        if ($this->isContextualFollowUp($textLower)) {
            if ($this->replyContextualFollowUp($conversation, $user, $incomingMessage, $text, $textLower)) {
                return;
            }
        }

        if ($this->asksPendingTickets($textLower)) {
            $this->replyPendingTickets($conversation, $user);
            return;
        }

        if ($this->asksMostUrgent($textLower)) {
            $this->replyMostUrgentTicket($conversation, $user);
            return;
        }

        if ($this->asksTodayAgenda($textLower)) {
            $this->replyTodayAgenda($conversation, $user);
            return;
        }

        if ($folio = $this->extractTicketFolio($text)) {
            if ($this->asksTicketDeepDetail($textLower)) {
                $this->replyTicketFullDetail($conversation, $user, $folio);
                return;
            }

            $this->replyTicketStatus($conversation, $user, $folio);
            return;
        }

        if ($this->looksLikeTicketQuestionWithoutFolio($textLower)) {
            $this->replyNeedFolioForTicketQuestion($conversation);
            return;
        }

        $this->replyWithAi($conversation, $user, $text, $incomingMessage);
    }

    protected function replyForUnknownUser(WaConversation $conversation, string $text): void
    {
        $wa = app(WhatsAppService::class);

        $wa->sendButtons(
            $conversation->phone,
            'Tu número no está vinculado al sistema. Puedo darte información general o canalizarte con un asesor.',
            [
                ['id' => 'human', 'title' => 'Hablar con asesor'],
                ['id' => 'info', 'title' => 'Info general'],
            ],
            $conversation
        );
    }

    protected function handleExternalUser(WaConversation $conversation, User $user, string $text, string $textLower): void
    {
        $wa = app(WhatsAppService::class);

        if ($this->asksGeneralCompanyInfo($textLower)) {
            $wa->sendText(
                $conversation->phone,
                'Jureto es una empresa comercializadora que distribuye a todo el país y ofrece insumos de papelería, oficina, tecnología, limpieza, construcción y otros productos. Si necesitas apoyo comercial, seguimiento o atención, puedo canalizarte con un asesor.',
                $conversation
            );
            return;
        }

        if ($folio = $this->extractTicketFolio($text)) {
            $ticket = $this->findVisibleTicketForExternalUser($user, $folio);

            if (!$ticket) {
                $wa->sendText(
                    $conversation->phone,
                    'No encontré ese ticket relacionado contigo.',
                    $conversation
                );
                return;
            }

            $dueText = $ticket->due_at ? $ticket->due_at->format('d/m/Y h:i A') : 'Sin fecha límite';

            $wa->sendText(
                $conversation->phone,
                "Detalle de {$ticket->folio}:\n"
                ."Título: {$ticket->title}\n"
                ."Estado: {$ticket->status}\n"
                ."Prioridad: {$ticket->priority}\n"
                ."Vence: {$dueText}",
                $conversation
            );
            return;
        }

        if ($this->asksPendingTickets($textLower)) {
            $tickets = Ticket::query()
                ->where(function ($q) use ($user) {
                    if (Schema::hasColumn('tickets', 'created_by')) {
                        $q->where('created_by', $user->id);
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->whereNotIn('status', ['completado', 'cancelado'])
                ->latest()
                ->limit(5)
                ->get(['folio', 'title', 'status', 'priority']);

            if ($tickets->isEmpty()) {
                $wa->sendText(
                    $conversation->phone,
                    'No encontré tickets abiertos relacionados contigo.',
                    $conversation
                );
                return;
            }

            $lines = ['Estos son tus tickets abiertos:'];
            foreach ($tickets as $t) {
                $lines[] = '• '.$t->folio.' - '.Str::limit($t->title, 45).' ('.$t->status.')';
            }

            $wa->sendText($conversation->phone, implode("\n", $lines), $conversation);
            return;
        }

        if ($this->wantsHuman($textLower)) {
            $this->handoffToHuman($conversation, 'Cliente solicitó asesor');
            $wa->sendText(
                $conversation->phone,
                'Listo, te canalicé con un asesor.',
                $conversation
            );
            return;
        }

        $wa->sendText(
            $conversation->phone,
            'Puedo ayudarte con información general, revisar un ticket tuyo por folio o canalizarte con un asesor.',
            $conversation
        );
    }

    protected function replyPendingTickets(WaConversation $conversation, User $user): void
    {
        $wa = app(WhatsAppService::class);

        $tickets = Ticket::query()
            ->where('assignee_id', $user->id)
            ->whereNotIn('status', ['completado', 'cancelado'])
            ->orderByRaw("
                CASE priority
                    WHEN 'critica' THEN 1
                    WHEN 'alta' THEN 2
                    WHEN 'media' THEN 3
                    WHEN 'baja' THEN 4
                    WHEN 'mejora' THEN 5
                    ELSE 6
                END
            ")
            ->orderByRaw("
                CASE status
                    WHEN 'bloqueado' THEN 1
                    WHEN 'pendiente' THEN 2
                    WHEN 'reabierto' THEN 3
                    WHEN 'progreso' THEN 4
                    WHEN 'revision' THEN 5
                    WHEN 'pruebas' THEN 6
                    WHEN 'por_revisar' THEN 7
                    ELSE 8
                END
            ")
            ->limit(7)
            ->get(['folio', 'title', 'status', 'priority', 'due_at']);

        if ($tickets->isEmpty()) {
            $wa->sendText(
                $conversation->phone,
                'No tienes tickets pendientes asignados.',
                $conversation
            );
            return;
        }

        $lines = ['Tienes estos tickets pendientes asignados:'];

        foreach ($tickets as $t) {
            $due = $t->due_at ? ' · vence '.$t->due_at->format('d/m') : '';
            $lines[] = '• '.$t->folio.' - '.Str::limit($t->title, 42).' ('.$t->priority.', '.$t->status.$due.')';
        }

        $lines[] = '';
        $lines[] = 'También puedes preguntar: cuál urge más, qué tengo hoy o detalle TKT-2026-0001';

        $wa->sendText($conversation->phone, implode("\n", $lines), $conversation);
    }

    protected function replyMostUrgentTicket(WaConversation $conversation, User $user): void
    {
        $wa = app(WhatsAppService::class);

        $tickets = Ticket::query()
            ->where('assignee_id', $user->id)
            ->whereNotIn('status', ['completado', 'cancelado'])
            ->get(['id', 'folio', 'title', 'priority', 'status', 'due_at', 'area']);

        if ($tickets->isEmpty()) {
            $wa->sendText(
                $conversation->phone,
                'No tienes tickets pendientes asignados.',
                $conversation
            );
            return;
        }

        $best = $tickets->sortBy(function ($t) {
            return [
                $this->priorityRank((string) $t->priority),
                $this->statusRank((string) $t->status),
                $t->due_at ? $t->due_at->timestamp : PHP_INT_MAX,
                -1 * ((int) $t->id),
            ];
        })->first();

        $dueText = $best->due_at ? $best->due_at->format('d/m/Y h:i A') : 'Sin fecha límite';

        $wa->sendText(
            $conversation->phone,
            "El que más urge ahorita es {$best->folio}.\n"
            ."Título: {$best->title}\n"
            ."Prioridad: {$best->priority}\n"
            ."Estado: {$best->status}\n"
            ."Vence: {$dueText}\n"
            ."Si quieres, también te digo por qué lo considero el más urgente.",
            $conversation
        );
    }

    protected function replyWhyMostUrgent(WaConversation $conversation, User $user): void
    {
        $wa = app(WhatsAppService::class);

        $tickets = Ticket::query()
            ->where('assignee_id', $user->id)
            ->whereNotIn('status', ['completado', 'cancelado'])
            ->get(['id', 'folio', 'title', 'priority', 'status', 'due_at', 'area']);

        if ($tickets->isEmpty()) {
            $wa->sendText(
                $conversation->phone,
                'No tienes tickets pendientes asignados.',
                $conversation
            );
            return;
        }

        $best = $tickets->sortBy(function ($t) {
            return [
                $this->priorityRank((string) $t->priority),
                $this->statusRank((string) $t->status),
                $t->due_at ? $t->due_at->timestamp : PHP_INT_MAX,
                -1 * ((int) $t->id),
            ];
        })->first();

        $reasons = [];

        if ((string) $best->status === 'bloqueado') {
            $reasons[] = 'está en estado bloqueado';
        }

        if (in_array((string) $best->priority, ['critica', 'alta', 'media'], true)) {
            $reasons[] = 'tiene prioridad '.$best->priority;
        }

        if ($best->due_at) {
            $reasons[] = 'tiene fecha límite '.$best->due_at->format('d/m/Y h:i A');
        } else {
            $reasons[] = 'aunque no tiene fecha límite, queda por encima de otros por prioridad y estado';
        }

        $reasonText = implode(', ', $reasons);

        $wa->sendText(
            $conversation->phone,
            "Lo considero el más urgente porque {$reasonText}.\n"
            ."Ticket: {$best->folio}\n"
            ."Título: {$best->title}",
            $conversation
        );
    }

    protected function replyTicketStatus(WaConversation $conversation, User $user, string $folio): void
    {
        $wa = app(WhatsAppService::class);

        $ticket = $this->findVisibleTicketForInternalUser($user, $folio);

        if (!$ticket) {
            $wa->sendText(
                $conversation->phone,
                "No encontré el ticket {$folio} relacionado contigo.",
                $conversation
            );
            return;
        }

        $dueText = $ticket->due_at ? $ticket->due_at->format('d/m/Y h:i A') : 'Sin fecha límite';

        $wa->sendText(
            $conversation->phone,
            "Estado de {$ticket->folio}:\n"
            ."Título: {$ticket->title}\n"
            ."Estado: {$ticket->status}\n"
            ."Prioridad: {$ticket->priority}\n"
            ."Área: {$ticket->area}\n"
            ."Vence: {$dueText}",
            $conversation
        );
    }

    protected function replyTicketFullDetail(WaConversation $conversation, User $user, string $folio): void
    {
        $wa = app(WhatsAppService::class);

        $ticket = $this->findVisibleTicketForInternalUser($user, $folio);

        if (!$ticket) {
            $wa->sendText(
                $conversation->phone,
                "No encontré el ticket {$folio} relacionado contigo.",
                $conversation
            );
            return;
        }

        $dueText = $ticket->due_at ? $ticket->due_at->format('d/m/Y h:i A') : 'Sin fecha límite';
        $description = trim((string) ($ticket->description ?? ''));
        $description = $description !== '' ? Str::limit($description, 350) : 'Sin descripción registrada';

        $suggestion = $this->buildSimpleActionHint($ticket);

        $wa->sendText(
            $conversation->phone,
            "Detalle de {$ticket->folio}:\n"
            ."Título: {$ticket->title}\n"
            ."Estado: {$ticket->status}\n"
            ."Prioridad: {$ticket->priority}\n"
            ."Área: {$ticket->area}\n"
            ."Vence: {$dueText}\n"
            ."Descripción: {$description}\n"
            ."Qué hacer: {$suggestion}",
            $conversation
        );
    }

    protected function replyTodayAgenda(WaConversation $conversation, User $user): void
    {
        $wa = app(WhatsAppService::class);

        if (!class_exists(AgendaEvent::class)) {
            $wa->sendText(
                $conversation->phone,
                'La agenda no está disponible en este momento.',
                $conversation
            );
            return;
        }

        $start = now()->startOfDay();
        $end = now()->endOfDay();

        $query = AgendaEvent::query();

        if (Schema::hasColumn('agenda_events', 'user_id')) {
            $query->where('user_id', $user->id);
        } elseif (Schema::hasColumn('agenda_events', 'created_by')) {
            $query->where('created_by', $user->id);
        } else {
            $wa->sendText(
                $conversation->phone,
                'No encontré una relación directa de agenda con tu usuario.',
                $conversation
            );
            return;
        }

        if (Schema::hasColumn('agenda_events', 'start_at')) {
            $query->whereBetween('start_at', [$start, $end]);
        }

        $events = $query->orderBy('start_at')->limit(6)->get(['title', 'start_at']);

        if ($events->isEmpty()) {
            $wa->sendText(
                $conversation->phone,
                'No tienes eventos programados para hoy.',
                $conversation
            );
            return;
        }

        $lines = ['Esto tienes hoy:'];
        foreach ($events as $ev) {
            $hour = $ev->start_at ? $ev->start_at->format('h:i A') : 'Sin hora';
            $lines[] = '• '.$hour.' - '.Str::limit((string) $ev->title, 55);
        }

        $wa->sendText($conversation->phone, implode("\n", $lines), $conversation);
    }

    protected function replyNeedFolioForTicketQuestion(WaConversation $conversation): void
    {
        app(WhatsAppService::class)->sendText(
            $conversation->phone,
            'Para decirte de qué trata, qué tienes que hacer o cuándo vence, mándame el folio. Ejemplo: detalle TKT-2026-0013',
            $conversation
        );
    }

    protected function replyWithAi(WaConversation $conversation, User $user, string $text, ?WaMessage $incomingMessage = null): void
    {
        $openai = app(OpenAIResponsesService::class);
        $wa = app(WhatsAppService::class);

        $pendingCount = Ticket::query()
            ->where('assignee_id', $user->id)
            ->whereNotIn('status', ['completado', 'cancelado'])
            ->count();

        $todayAgendaCount = 0;
        if (class_exists(AgendaEvent::class) && Schema::hasTable('agenda_events')) {
            $query = AgendaEvent::query();

            if (Schema::hasColumn('agenda_events', 'user_id')) {
                $query->where('user_id', $user->id);
            } elseif (Schema::hasColumn('agenda_events', 'created_by')) {
                $query->where('created_by', $user->id);
            }

            if (Schema::hasColumn('agenda_events', 'start_at')) {
                $query->whereBetween('start_at', [now()->startOfDay(), now()->endOfDay()]);
            }

            $todayAgendaCount = $query->count();
        }

        $lastAssistantMessage = $this->getLastAssistantMessageText($conversation, $incomingMessage);
        $conversationHistory = $this->buildConversationHistory($conversation, $incomingMessage, 12);
        $companyKnowledge = $this->companyKnowledgeBlock();

        $system = <<<PROMPT
Eres el asistente de WhatsApp de Jureto para usuarios internos.

Responde en español, natural, útil, breve y con contexto.
NO respondas como bot genérico.
NO ignores lo que ya se habló en la conversación.
SI el usuario manda un seguimiento como "sí", "por qué", "y luego", "explícame", "de cuál hablas", debes interpretar ese mensaje usando el contexto del historial reciente.
NO inventes datos internos específicos que no te hayan dado o que no estén en el contexto.
Sí puedes orientar sobre tickets, agenda, prioridades, uso del sistema y datos generales de Jureto.

Contexto del usuario:
- Nombre: {$user->name}
- Tickets pendientes asignados: {$pendingCount}
- Eventos de hoy: {$todayAgendaCount}

Última respuesta enviada por el asistente:
{$lastAssistantMessage}

Historial reciente:
{$conversationHistory}

Conocimiento base de Jureto:
{$companyKnowledge}

Reglas de comportamiento:
- Si el usuario pregunta por prioridades, trabajo, organización o sistema, responde útilmente.
- Si necesita un dato exacto de un ticket y no dio folio, pídelo breve.
- Si el usuario hace una repregunta corta, apóyate en la última respuesta y en el historial.
- Si el usuario pregunta qué es Jureto o a qué se dedica, responde usando el conocimiento base.
- Si pide algo operativo no disponible, ofrece canalizar con asesor humano.
- Mantén tono profesional, claro, humano y nada robótico.
- Evita repetir siempre la misma frase final.
PROMPT;

        $result = $openai->ask($system, $text);

        if (!$result['ok']) {
            $wa->sendText(
                $conversation->phone,
                'Puedo ayudarte con tickets, agenda, prioridad de trabajo y dudas del sistema. Prueba con: pendientes, cuál urge más, qué tengo hoy o detalle TKT-2026-0001.',
                $conversation
            );
            return;
        }

        $reply = trim((string) ($result['text'] ?? ''));

        if ($reply === '') {
            $reply = 'Puedo ayudarte con tickets, agenda y dudas del sistema. Prueba con: pendientes, cuál urge más, qué tengo hoy o detalle TKT-2026-0001.';
        }

        $wa->sendText($conversation->phone, $reply, $conversation);
    }

    protected function getLastAssistantMessageText(WaConversation $conversation, ?WaMessage $incomingMessage = null): string
    {
        $query = WaMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('direction', 'outbound')
            ->orderByDesc('id');

        $last = $query->first();

        return $last?->text
            ? Str::limit(trim((string) $last->text), 500)
            : 'Sin respuesta previa registrada';
    }

    protected function buildConversationHistory(WaConversation $conversation, ?WaMessage $incomingMessage = null, int $limit = 12): string
    {
        $messages = WaMessage::query()
            ->where('conversation_id', $conversation->id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'direction', 'text'])
            ->reverse()
            ->values();

        if ($messages->isEmpty()) {
            return 'Sin historial reciente';
        }

        $lines = [];

        foreach ($messages as $msg) {
            $text = trim((string) $msg->text);

            if ($text === '') {
                continue;
            }

            $speaker = $msg->direction === 'inbound' ? 'Usuario' : 'Asistente';
            $lines[] = $speaker.': '.Str::limit(preg_replace('/\s+/', ' ', $text), 500);
        }

        return !empty($lines) ? implode("\n", $lines) : 'Sin historial reciente';
    }

    protected function companyKnowledgeBlock(): string
    {
        return <<<TXT
Jureto es una empresa comercializadora enfocada en distribución a nivel nacional.
Maneja insumos y productos de distintas categorías.
Entre sus líneas de oferta están: papelería y artículos de oficina/escolares, tecnología y cómputo, construcción, material eléctrico y electrónico, limpieza, vestuario/uniformes y artículos deportivos.
También maneja compra-venta, comisión, consignación, distribución, exportación e importación.
TXT;
    }

    protected function replyContextualFollowUp(
        WaConversation $conversation,
        User $user,
        WaMessage $incomingMessage,
        string $text,
        string $textLower
    ): bool {
        $lastOutbound = WaMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('direction', 'outbound')
            ->orderByDesc('id')
            ->first(['id', 'text']);

        if (!$lastOutbound || trim((string) $lastOutbound->text) === '') {
            return false;
        }

        $lastText = mb_strtolower((string) $lastOutbound->text);

        if ($this->asksWhyFollowUp($textLower) && str_contains($lastText, 'si quieres, también te digo por qué lo considero el más urgente')) {
            $this->replyWhyMostUrgent($conversation, $user);
            return true;
        }

        if (
            $this->asksMoreDetailFollowUp($textLower) &&
            preg_match('/tkt-\d{4}-\d{4,}/i', (string) $lastOutbound->text, $m)
        ) {
            $this->replyTicketFullDetail($conversation, $user, strtoupper($m[0]));
            return true;
        }

        if (
            $this->asksDueDateFollowUp($textLower) &&
            preg_match('/tkt-\d{4}-\d{4,}/i', (string) $lastOutbound->text, $m)
        ) {
            $this->replyTicketStatus($conversation, $user, strtoupper($m[0]));
            return true;
        }

        return false;
    }

    protected function isContextualFollowUp(string $text): bool
    {
        return $this->asksWhyFollowUp($text)
            || $this->asksMoreDetailFollowUp($text)
            || $this->asksDueDateFollowUp($text)
            || in_array($text, ['si', 'sí', 'ok', 'va', 'dale', 'aja', 'ajá'], true)
            || str_starts_with($text, 'y ')
            || str_starts_with($text, 'entonces');
    }

    protected function asksWhyFollowUp(string $text): bool
    {
        return str_contains($text, 'por que')
            || str_contains($text, 'por qué')
            || str_contains($text, 'porque')
            || str_contains($text, 'si porque')
            || str_contains($text, 'sí por qué')
            || str_contains($text, 'y por que')
            || str_contains($text, 'y por qué');
    }

    protected function asksMoreDetailFollowUp(string $text): bool
    {
        return str_contains($text, 'de que trata')
            || str_contains($text, 'de qué trata')
            || str_contains($text, 'explicame')
            || str_contains($text, 'explícame')
            || str_contains($text, 'mas detalle')
            || str_contains($text, 'más detalle')
            || str_contains($text, 'que tengo que hacer')
            || str_contains($text, 'qué tengo que hacer');
    }

    protected function asksDueDateFollowUp(string $text): bool
    {
        return str_contains($text, 'cuando vence')
            || str_contains($text, 'cuándo vence')
            || str_contains($text, 'para cuando')
            || str_contains($text, 'para cuándo')
            || str_contains($text, 'fecha limite')
            || str_contains($text, 'fecha límite');
    }

    protected function findVisibleTicketForInternalUser(User $user, string $folio): ?Ticket
    {
        return Ticket::query()
            ->where('folio', $folio)
            ->where(function ($q) use ($user) {
                $q->where('assignee_id', $user->id);

                if (Schema::hasColumn('tickets', 'created_by')) {
                    $q->orWhere('created_by', $user->id);
                }
            })
            ->first(['id', 'folio', 'title', 'description', 'status', 'priority', 'area', 'due_at']);
    }

    protected function findVisibleTicketForExternalUser(User $user, string $folio): ?Ticket
    {
        $query = Ticket::query()->where('folio', $folio);

        if (Schema::hasColumn('tickets', 'created_by')) {
            $query->where('created_by', $user->id);
        } else {
            return null;
        }

        return $query->first(['id', 'folio', 'title', 'status', 'priority', 'due_at']);
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

    protected function isInternalUser(User $user): bool
    {
        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole('cliente_web')) {
                return false;
            }

            $internalRoles = [
                'admin',
                'administrador',
                'sistemas',
                'ventas',
                'compras',
                'almacen',
                'logistica',
                'licitaciones',
                'mercadotecnia',
                'administracion',
                'mantenimiento',
                'contabilidad',
                'direccion',
                'calidad',
                'rh',
                'soporte',
                'empleado',
                'colaborador',
            ];

            foreach ($internalRoles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
        }

        if (property_exists($user, 'status') && (string) $user->status === 'approved') {
            return true;
        }

        return false;
    }

    protected function asksPendingTickets(string $text): bool
    {
        return str_contains($text, 'pendiente')
            || str_contains($text, 'pendientes')
            || str_contains($text, 'mis tickets')
            || str_contains($text, 'tickets abiertos')
            || str_contains($text, 'tengo tickets');
    }

    protected function asksMostUrgent(string $text): bool
    {
        return str_contains($text, 'cual urge')
            || str_contains($text, 'qué urge')
            || str_contains($text, 'que urge')
            || str_contains($text, 'cual de los tickets urge')
            || str_contains($text, 'cuál de los tickets urge')
            || str_contains($text, 'cual urge mas')
            || str_contains($text, 'cuál urge más')
            || str_contains($text, 'que ticket urge')
            || str_contains($text, 'qué ticket urge')
            || str_contains($text, 'prioridad mas alta')
            || str_contains($text, 'prioridad más alta');
    }

    protected function asksTodayAgenda(string $text): bool
    {
        return str_contains($text, 'que tengo hoy')
            || str_contains($text, 'qué tengo hoy')
            || str_contains($text, 'agenda de hoy')
            || str_contains($text, 'eventos de hoy')
            || str_contains($text, 'hoy que tengo')
            || str_contains($text, 'hoy qué tengo');
    }

    protected function asksTicketDeepDetail(string $text): bool
    {
        return str_contains($text, 'sobre que trata')
            || str_contains($text, 'sobre qué trata')
            || str_contains($text, 'que tengo que hacer')
            || str_contains($text, 'qué tengo que hacer')
            || str_contains($text, 'cuando vence')
            || str_contains($text, 'cuándo vence')
            || str_contains($text, 'detalle')
            || str_contains($text, 'explicame')
            || str_contains($text, 'explícame');
    }

    protected function looksLikeTicketQuestionWithoutFolio(string $text): bool
    {
        return (
            str_contains($text, 'que tengo que hacer')
            || str_contains($text, 'qué tengo que hacer')
            || str_contains($text, 'sobre que trata')
            || str_contains($text, 'sobre qué trata')
            || str_contains($text, 'cuando vence')
            || str_contains($text, 'cuándo vence')
        ) && !$this->extractTicketFolio($text);
    }

    protected function asksGeneralCompanyInfo(string $text): bool
    {
        return str_contains($text, 'que es jureto')
            || str_contains($text, 'qué es jureto')
            || str_contains($text, 'sobre jureto')
            || str_contains($text, 'informacion de la empresa')
            || str_contains($text, 'información de la empresa')
            || str_contains($text, 'a que se dedica')
            || str_contains($text, 'a qué se dedica');
    }

    protected function wantsHuman(string $text): bool
    {
        return str_contains($text, 'asesor')
            || str_contains($text, 'humano')
            || str_contains($text, 'agente')
            || str_contains($text, 'ejecutivo')
            || str_contains($text, 'persona');
    }

    protected function isGreeting(string $text): bool
    {
        return in_array($text, ['hola', 'buenas', 'buen día', 'buen dia', 'hey', 'holi'], true);
    }

    protected function extractTicketFolio(string $text): ?string
    {
        preg_match('/TKT-\d{4}-\d{4,}/i', $text, $matches);
        return isset($matches[0]) ? strtoupper($matches[0]) : null;
    }

    protected function priorityRank(string $priority): int
    {
        return match ($priority) {
            'critica' => 1,
            'alta' => 2,
            'media' => 3,
            'baja' => 4,
            'mejora' => 5,
            default => 6,
        };
    }

    protected function statusRank(string $status): int
    {
        return match ($status) {
            'bloqueado' => 1,
            'pendiente' => 2,
            'reabierto' => 3,
            'progreso' => 4,
            'revision' => 5,
            'pruebas' => 6,
            'por_revisar' => 7,
            default => 8,
        };
    }

    protected function buildSimpleActionHint(Ticket $ticket): string
    {
        $title = mb_strtolower((string) $ticket->title);
        $area = mb_strtolower((string) $ticket->area);

        if (str_contains($title, 'usuario') || str_contains($title, 'crear usuario')) {
            return 'Revisa la solicitud, valida los datos del usuario, crea el acceso y confirma pruebas de ingreso.';
        }

        if (str_contains($title, 'pagina') || str_contains($title, 'página') || str_contains($title, 'web')) {
            return 'Revisa requerimientos, contenido, estructura, diseño y fecha objetivo antes de desarrollarlo.';
        }

        if ($area === 'sistemas') {
            return 'Revisa requerimiento, define alcance, ejecuta cambios, valida pruebas y documenta resultado.';
        }

        if ($area === 'licitaciones') {
            return 'Revisa bases, requisitos, documentos, fechas límite y valida entregables antes de enviar.';
        }

        if ($area === 'almacen' || $area === 'almacén') {
            return 'Revisa existencias, ubicación, surtido, validación física y evidencia del movimiento.';
        }

        if ($area === 'compras') {
            return 'Valida requerimiento, cotizaciones, proveedor, tiempos de entrega y autorización.';
        }

        return 'Revisa la descripción, confirma alcance, ejecuta el trabajo y deja evidencia o avance según corresponda.';
    }

    protected function firstName(?string $fullName): string
    {
        $fullName = trim((string) $fullName);

        if ($fullName === '') {
            return 'Usuario';
        }

        $parts = preg_split('/\s+/', $fullName) ?: [];
        return trim((string) ($parts[0] ?? 'Usuario'));
    }
}