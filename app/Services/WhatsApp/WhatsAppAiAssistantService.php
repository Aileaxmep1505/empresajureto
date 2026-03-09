<?php

namespace App\Services\WhatsApp;

use App\Models\AgendaEvent;
use App\Models\CatalogItem;
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
                'Recibí tu mensaje, pero no pude leerlo bien. ¿Me lo mandas otra vez?',
                $conversation
            );
            return;
        }

        if (!$user) {
            $this->replyForUnknownUser($conversation);
            return;
        }

        if ($this->wantsHuman($textLower)) {
            $this->handoffToHuman($conversation, 'Solicitado por el usuario');
            $this->sendTrackedText(
                $conversation,
                'Listo, ya te canalicé con un asesor humano.',
                [
                    'topic' => 'handoff',
                    'source' => 'rule',
                ]
            );
            return;
        }

        $isInternal = $this->isInternalUser($user);

        if (!$isInternal) {
            $this->handleExternalUser($conversation, $user, $text, $textLower, $incomingMessage);
            return;
        }

        if ($this->isGreeting($textLower)) {
            $this->sendTrackedText(
                $conversation,
                'Hola '.$this->firstName($user->name).'. ¿En qué te ayudo?',
                [
                    'topic' => 'greeting',
                    'source' => 'rule',
                ]
            );
            return;
        }

        if ($this->isContextualFollowUp($textLower)) {
            if ($this->replyContextualFollowUp($conversation, $user, $textLower)) {
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

        if ($this->asksLowStock($textLower)) {
            $this->replyLowStockProducts($conversation);
            return;
        }

        if ($this->asksFeaturedProducts($textLower)) {
            $this->replyFeaturedProducts($conversation);
            return;
        }

        if ($this->asksUpcomingMeetings($textLower)) {
            $this->replyUpcomingMeetings($conversation, $user);
            return;
        }

        if ($this->asksMarketplaceSummary($textLower)) {
            $this->replyMarketplaceSummary($conversation);
            return;
        }

        if ($this->asksTicketsByArea($textLower)) {
            $this->replyTicketsByArea($conversation, $user);
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

        $this->replyWithAi($conversation, $user, $text, $incomingMessage, true);
    }

    protected function replyForUnknownUser(WaConversation $conversation): void
    {
        app(WhatsAppService::class)->sendButtons(
            $conversation->phone,
            'Tu número no está vinculado al sistema. Puedo darte información general o canalizarte con un asesor.',
            [
                ['id' => 'human', 'title' => 'Hablar con asesor'],
                ['id' => 'info', 'title' => 'Info general'],
            ],
            $conversation
        );
    }

    protected function handleExternalUser(
        WaConversation $conversation,
        User $user,
        string $text,
        string $textLower,
        WaMessage $incomingMessage
    ): void {
        if ($folio = $this->extractTicketFolio($text)) {
            $ticket = $this->findVisibleTicketForExternalUser($user, $folio);

            if (!$ticket) {
                $this->sendTrackedText(
                    $conversation,
                    'No encontré ese ticket relacionado contigo.',
                    [
                        'topic' => 'ticket_lookup',
                        'ticket_folio' => $folio,
                        'source' => 'rule',
                    ]
                );
                return;
            }

            $dueText = $ticket->due_at ? $ticket->due_at->format('d/m/Y h:i A') : 'Sin fecha límite';

            $this->sendTrackedText(
                $conversation,
                "Detalle de {$ticket->folio}:\n"
                ."Título: {$ticket->title}\n"
                ."Estado: {$ticket->status}\n"
                ."Prioridad: {$ticket->priority}\n"
                ."Vence: {$dueText}",
                [
                    'topic' => 'ticket_detail',
                    'ticket_folio' => $ticket->folio,
                    'source' => 'rule',
                ]
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
                $this->sendTrackedText(
                    $conversation,
                    'No encontré tickets abiertos relacionados contigo.',
                    [
                        'topic' => 'tickets_pending',
                        'source' => 'rule',
                    ]
                );
                return;
            }

            $lines = ['Estos son tus tickets abiertos:'];
            foreach ($tickets as $t) {
                $lines[] = '• '.$t->folio.' - '.Str::limit($t->title, 45).' ('.$t->status.')';
            }

            $this->sendTrackedText(
                $conversation,
                implode("\n", $lines),
                [
                    'topic' => 'tickets_pending',
                    'source' => 'rule',
                ]
            );
            return;
        }

        $this->replyWithAi($conversation, $user, $text, $incomingMessage, false);
    }

    protected function replyPendingTickets(WaConversation $conversation, User $user): void
    {
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
            ->get(['folio', 'title', 'status', 'priority', 'due_at', 'area']);

        if ($tickets->isEmpty()) {
            $this->sendTrackedText(
                $conversation,
                'No tienes tickets pendientes asignados.',
                [
                    'topic' => 'tickets_pending',
                    'source' => 'rule',
                ]
            );
            return;
        }

        $lines = ['Tienes estos tickets pendientes asignados:'];

        foreach ($tickets as $t) {
            $due = $t->due_at ? ' · vence '.$t->due_at->format('d/m') : '';
            $lines[] = '• '.$t->folio.' - '.Str::limit($t->title, 42).' ('.$t->priority.', '.$t->status.$due.')';
        }

        $this->sendTrackedText(
            $conversation,
            implode("\n", $lines),
            [
                'topic' => 'tickets_pending',
                'source' => 'rule',
                'tickets_count' => $tickets->count(),
            ]
        );
    }

    protected function replyMostUrgentTicket(WaConversation $conversation, User $user): void
    {
        $tickets = Ticket::query()
            ->where('assignee_id', $user->id)
            ->whereNotIn('status', ['completado', 'cancelado'])
            ->get(['id', 'folio', 'title', 'priority', 'status', 'due_at', 'area']);

        if ($tickets->isEmpty()) {
            $this->sendTrackedText(
                $conversation,
                'No tienes tickets pendientes asignados.',
                [
                    'topic' => 'ticket_most_urgent',
                    'source' => 'rule',
                ]
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

        $this->sendTrackedText(
            $conversation,
            "El que más urge ahorita es {$best->folio}.\n"
            ."Título: {$best->title}\n"
            ."Prioridad: {$best->priority}\n"
            ."Estado: {$best->status}\n"
            ."Vence: {$dueText}\n"
            ."Si quieres, te digo por qué lo considero el más urgente.",
            [
                'topic' => 'ticket_most_urgent',
                'ticket_folio' => $best->folio,
                'ticket_area' => $best->area,
                'source' => 'rule',
            ]
        );
    }

    protected function replyWhyMostUrgent(WaConversation $conversation, User $user): void
    {
        $tickets = Ticket::query()
            ->where('assignee_id', $user->id)
            ->whereNotIn('status', ['completado', 'cancelado'])
            ->get(['id', 'folio', 'title', 'priority', 'status', 'due_at', 'area']);

        if ($tickets->isEmpty()) {
            $this->sendTrackedText(
                $conversation,
                'No tienes tickets pendientes asignados.',
                [
                    'topic' => 'ticket_most_urgent_reason',
                    'source' => 'rule',
                ]
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
            $reasons[] = 'está bloqueado';
        }

        if (in_array((string) $best->priority, ['critica', 'alta', 'media'], true)) {
            $reasons[] = 'tiene prioridad '.$best->priority;
        }

        if ($best->due_at) {
            $reasons[] = 'tiene fecha límite '.$best->due_at->format('d/m/Y h:i A');
        }

        if (empty($reasons)) {
            $reasons[] = 'queda por encima de otros por prioridad, estado y orden de atención';
        }

        $this->sendTrackedText(
            $conversation,
            "Lo considero el más urgente porque ".implode(', ', $reasons).".\n"
            ."Ticket: {$best->folio}\n"
            ."Título: {$best->title}",
            [
                'topic' => 'ticket_most_urgent_reason',
                'ticket_folio' => $best->folio,
                'ticket_area' => $best->area,
                'source' => 'rule',
            ]
        );
    }

    protected function replyTicketStatus(WaConversation $conversation, User $user, string $folio): void
    {
        $ticket = $this->findVisibleTicketForInternalUser($user, $folio);

        if (!$ticket) {
            $this->sendTrackedText(
                $conversation,
                "No encontré el ticket {$folio} relacionado contigo.",
                [
                    'topic' => 'ticket_lookup',
                    'ticket_folio' => $folio,
                    'source' => 'rule',
                ]
            );
            return;
        }

        $dueText = $ticket->due_at ? $ticket->due_at->format('d/m/Y h:i A') : 'Sin fecha límite';

        $this->sendTrackedText(
            $conversation,
            "Estado de {$ticket->folio}:\n"
            ."Título: {$ticket->title}\n"
            ."Estado: {$ticket->status}\n"
            ."Prioridad: {$ticket->priority}\n"
            ."Área: {$ticket->area}\n"
            ."Vence: {$dueText}",
            [
                'topic' => 'ticket_status',
                'ticket_folio' => $ticket->folio,
                'ticket_area' => $ticket->area,
                'source' => 'rule',
            ]
        );
    }

    protected function replyTicketFullDetail(WaConversation $conversation, User $user, string $folio): void
    {
        $ticket = $this->findVisibleTicketForInternalUser($user, $folio);

        if (!$ticket) {
            $this->sendTrackedText(
                $conversation,
                "No encontré el ticket {$folio} relacionado contigo.",
                [
                    'topic' => 'ticket_detail',
                    'ticket_folio' => $folio,
                    'source' => 'rule',
                ]
            );
            return;
        }

        $dueText = $ticket->due_at ? $ticket->due_at->format('d/m/Y h:i A') : 'Sin fecha límite';
        $description = trim((string) ($ticket->description ?? ''));
        $description = $description !== '' ? Str::limit($description, 350) : 'Sin descripción registrada';
        $suggestion = $this->buildSimpleActionHint($ticket);

        $this->sendTrackedText(
            $conversation,
            "Detalle de {$ticket->folio}:\n"
            ."Título: {$ticket->title}\n"
            ."Estado: {$ticket->status}\n"
            ."Prioridad: {$ticket->priority}\n"
            ."Área: {$ticket->area}\n"
            ."Vence: {$dueText}\n"
            ."Descripción: {$description}\n"
            ."Qué hacer: {$suggestion}",
            [
                'topic' => 'ticket_detail',
                'ticket_folio' => $ticket->folio,
                'ticket_area' => $ticket->area,
                'source' => 'rule',
            ]
        );
    }

    protected function replyTodayAgenda(WaConversation $conversation, User $user): void
    {
        if (!class_exists(AgendaEvent::class) || !Schema::hasTable('agenda_events')) {
            $this->sendTrackedText(
                $conversation,
                'La agenda no está disponible en este momento.',
                [
                    'topic' => 'agenda_today',
                    'source' => 'rule',
                ]
            );
            return;
        }

        $query = $this->agendaQueryForUser($user);

        if (Schema::hasColumn('agenda_events', 'start_at')) {
            $query->whereBetween('start_at', [now()->startOfDay(), now()->endOfDay()]);
        }

        $events = $query->orderBy('start_at')->limit(6)->get(['id', 'title', 'start_at']);

        if ($events->isEmpty()) {
            $this->sendTrackedText(
                $conversation,
                'No tienes eventos programados para hoy.',
                [
                    'topic' => 'agenda_today',
                    'source' => 'rule',
                ]
            );
            return;
        }

        $lines = ['Esto tienes hoy:'];
        foreach ($events as $ev) {
            $hour = $ev->start_at ? $ev->start_at->format('h:i A') : 'Sin hora';
            $lines[] = '• '.$hour.' - '.Str::limit((string) $ev->title, 55);
        }

        $this->sendTrackedText(
            $conversation,
            implode("\n", $lines),
            [
                'topic' => 'agenda_today',
                'agenda_event_id' => (int) $events->first()->id,
                'source' => 'rule',
            ]
        );
    }

    protected function replyUpcomingMeetings(WaConversation $conversation, User $user): void
    {
        if (!class_exists(AgendaEvent::class) || !Schema::hasTable('agenda_events')) {
            $this->sendTrackedText(
                $conversation,
                'La agenda no está disponible en este momento.',
                [
                    'topic' => 'agenda_upcoming',
                    'source' => 'rule',
                ]
            );
            return;
        }

        $query = $this->agendaQueryForUser($user);

        if (Schema::hasColumn('agenda_events', 'start_at')) {
            $query->where('start_at', '>=', now());
        }

        $events = $query->orderBy('start_at')->limit(5)->get(['id', 'title', 'start_at']);

        if ($events->isEmpty()) {
            $this->sendTrackedText(
                $conversation,
                'No veo próximas reuniones o eventos programados.',
                [
                    'topic' => 'agenda_upcoming',
                    'source' => 'rule',
                ]
            );
            return;
        }

        $lines = ['Estas son tus próximas reuniones o eventos:'];
        foreach ($events as $ev) {
            $when = $ev->start_at ? $ev->start_at->format('d/m h:i A') : 'Sin hora';
            $lines[] = '• '.$when.' - '.Str::limit((string) $ev->title, 60);
        }

        $this->sendTrackedText(
            $conversation,
            implode("\n", $lines),
            [
                'topic' => 'agenda_upcoming',
                'agenda_event_id' => (int) $events->first()->id,
                'source' => 'rule',
            ]
        );
    }

    protected function replyLowStockProducts(WaConversation $conversation): void
    {
        if (!$this->catalogAvailable()) {
            $this->sendTrackedText(
                $conversation,
                'El catálogo no está disponible en este momento.',
                [
                    'topic' => 'catalog_low_stock',
                    'source' => 'rule',
                ]
            );
            return;
        }

        $items = CatalogItem::query()
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->orderByDesc('id')
            ->limit(8)
            ->get(['id', 'name', 'sku', 'stock', 'status', 'meli_status']);

        if ($items->isEmpty()) {
            $this->sendTrackedText(
                $conversation,
                'No encontré productos con stock bajo en este momento.',
                [
                    'topic' => 'catalog_low_stock',
                    'source' => 'rule',
                ]
            );
            return;
        }

        $lines = ['Productos con poco stock:'];

        foreach ($items as $it) {
            $lines[] = '• '.Str::limit((string) $it->name, 48)
                .' | SKU: '.($it->sku ?: '—')
                .' | stock: '.(int) $it->stock;
        }

        $this->sendTrackedText(
            $conversation,
            implode("\n", $lines),
            [
                'topic' => 'catalog_low_stock',
                'catalog_focus' => 'low_stock',
                'source' => 'rule',
                'item_id' => (int) $items->first()->id,
            ]
        );
    }

    protected function replyFeaturedProducts(WaConversation $conversation): void
    {
        if (!$this->catalogAvailable()) {
            $this->sendTrackedText(
                $conversation,
                'El catálogo no está disponible en este momento.',
                [
                    'topic' => 'catalog_featured',
                    'source' => 'rule',
                ]
            );
            return;
        }

        $items = CatalogItem::query()
            ->where('is_featured', true)
            ->orderByDesc('id')
            ->limit(8)
            ->get(['id', 'name', 'sku', 'price', 'stock', 'status']);

        if ($items->isEmpty()) {
            $this->sendTrackedText(
                $conversation,
                'No encontré productos destacados en este momento.',
                [
                    'topic' => 'catalog_featured',
                    'source' => 'rule',
                ]
            );
            return;
        }

        $lines = ['Productos destacados:'];

        foreach ($items as $it) {
            $lines[] = '• '.Str::limit((string) $it->name, 48)
                .' | SKU: '.($it->sku ?: '—')
                .' | $'.number_format((float) $it->price, 2)
                .' | stock: '.(int) ($it->stock ?? 0);
        }

        $this->sendTrackedText(
            $conversation,
            implode("\n", $lines),
            [
                'topic' => 'catalog_featured',
                'catalog_focus' => 'featured',
                'source' => 'rule',
                'item_id' => (int) $items->first()->id,
            ]
        );
    }

    protected function replyMarketplaceSummary(WaConversation $conversation): void
    {
        if (!$this->catalogAvailable()) {
            $this->sendTrackedText(
                $conversation,
                'El catálogo no está disponible en este momento.',
                [
                    'topic' => 'marketplace_summary',
                    'source' => 'rule',
                ]
            );
            return;
        }

        $total = CatalogItem::query()->count();

        $mlWithId = Schema::hasColumn('catalog_items', 'meli_item_id')
            ? CatalogItem::query()->whereNotNull('meli_item_id')->count()
            : 0;

        $mlActive = Schema::hasColumn('catalog_items', 'meli_status')
            ? CatalogItem::query()->whereIn('meli_status', ['active', 'ACTIVO', 'Activo'])->count()
            : 0;

        $amazonSku = Schema::hasColumn('catalog_items', 'sku')
            ? CatalogItem::query()->whereNotNull('sku')->where('sku', '!=', '')->count()
            : 0;

        $published = Schema::hasColumn('catalog_items', 'status')
            ? CatalogItem::query()->where('status', 1)->count()
            : 0;

        $msg = "Resumen de marketplaces:\n"
            ."• Productos totales: {$total}\n"
            ."• Publicados en catálogo: {$published}\n"
            ."• Con publicación o ID de Mercado Libre: {$mlWithId}\n"
            ."• Activos en Mercado Libre: {$mlActive}\n"
            ."• Con SKU útil para Amazon: {$amazonSku}";

        $this->sendTrackedText(
            $conversation,
            $msg,
            [
                'topic' => 'marketplace_summary',
                'source' => 'rule',
                'catalog_focus' => 'marketplaces',
            ]
        );
    }

    protected function replyTicketsByArea(WaConversation $conversation, User $user): void
    {
        if (!Schema::hasTable('tickets') || !Schema::hasColumn('tickets', 'area')) {
            $this->sendTrackedText(
                $conversation,
                'No tengo disponible el resumen de tickets por área.',
                [
                    'topic' => 'tickets_by_area',
                    'source' => 'rule',
                ]
            );
            return;
        }

        $rows = Ticket::query()
            ->selectRaw('area, COUNT(*) as total')
            ->whereNotIn('status', ['completado', 'cancelado'])
            ->groupBy('area')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        if ($rows->isEmpty()) {
            $this->sendTrackedText(
                $conversation,
                'No encontré tickets abiertos por área en este momento.',
                [
                    'topic' => 'tickets_by_area',
                    'source' => 'rule',
                ]
            );
            return;
        }

        $lines = ['Tickets abiertos por área:'];
        foreach ($rows as $row) {
            $lines[] = '• '.($row->area ?: 'Sin área').' - '.(int) $row->total;
        }

        $this->sendTrackedText(
            $conversation,
            implode("\n", $lines),
            [
                'topic' => 'tickets_by_area',
                'source' => 'rule',
            ]
        );
    }

    protected function replyNeedFolioForTicketQuestion(WaConversation $conversation): void
    {
        $this->sendTrackedText(
            $conversation,
            'Para decirte de qué trata, qué tienes que hacer o cuándo vence, mándame el folio. Ejemplo: detalle TKT-2026-0013',
            [
                'topic' => 'ticket_need_folio',
                'source' => 'rule',
            ]
        );
    }

    protected function replyWithAi(
        WaConversation $conversation,
        User $user,
        string $text,
        ?WaMessage $incomingMessage = null,
        bool $internal = true
    ): void {
        $openai = app(OpenAIResponsesService::class);

        $system = $this->buildSystemPrompt($user, $conversation, $internal);
        $messages = $this->buildAiMessages($conversation, $text, 14);

        $result = $openai->askWithMessages($system, $messages);

        if (!$result['ok']) {
            $fallback = $internal
                ? 'Entiendo. Cuéntame un poco más y te ayudo mejor.'
                : 'Entiendo. Cuéntame un poco más y te ayudo mejor.';

            $this->sendTrackedText(
                $conversation,
                $fallback,
                [
                    'topic' => 'fallback',
                    'source' => 'ai_error',
                ]
            );
            return;
        }

        $reply = trim((string) ($result['text'] ?? ''));

        if ($reply === '') {
            $reply = 'Claro, dame un poco más de detalle y te ayudo.';
        }

        $topic = $this->inferTopicFromText($text);
        $lastContext = $this->getLastStructuredContext($conversation);

        $this->sendTrackedText(
            $conversation,
            $reply,
            [
                'topic' => $topic ?: ($lastContext['topic'] ?? 'general'),
                'source' => 'ai',
                'linked_topic' => $lastContext['topic'] ?? null,
                'ticket_folio' => $lastContext['ticket_folio'] ?? null,
                'ticket_area' => $lastContext['ticket_area'] ?? null,
                'catalog_focus' => $lastContext['catalog_focus'] ?? null,
                'agenda_event_id' => $lastContext['agenda_event_id'] ?? null,
                'item_id' => $lastContext['item_id'] ?? null,
            ]
        );
    }

  protected function buildSystemPrompt(User $user, WaConversation $conversation, bool $internal = true): string
{
    $companyBlock = $this->companyKnowledgeBlock();
    $agendaBlock = $this->agendaKnowledgeBlock($user);
    $catalogBlock = $this->catalogKnowledgeBlock();
    $marketplacesBlock = $this->marketplacesKnowledgeBlock();
    $ticketsBlock = $this->ticketsKnowledgeBlock($user);
    $historyBlock = $this->buildConversationHistoryText($conversation, 12);
    $lastContext = $this->getLastStructuredContext($conversation);

    $lastContextJson = json_encode($lastContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $userType = $internal ? 'interno' : 'externo';
    $userName = (string) $user->name;

    return <<<PROMPT
Eres el asistente de WhatsApp de Jureto.

Responde en español, natural, útil, humano y breve.
No hables como bot genérico.
No repitas siempre lo mismo.
Tu enfoque principal es ayudar en general sobre empresa, operación, productos, agenda, marketplaces, tickets y uso del sistema.
No lleves la conversación a tickets si no lo preguntaron.

Reglas:
- Usa el historial reciente para entender seguimientos cortos.
- Usa también el último contexto estructurado para interpretar mensajes como: "sí", "por qué", "y ese", "muéstrame más", "cuándo", "de cuál", "de qué trata".
- Si el usuario pregunta por la empresa, responde sobre la empresa.
- Si pregunta por productos, responde sobre catálogo, stock, destacados o publicaciones.
- Si pregunta por agenda, responde sobre eventos, reuniones, recordatorios o próximos eventos.
- Si pregunta por marketplaces, responde sobre Mercado Libre y Amazon.
- Si pregunta por tickets, responde sobre tickets.
- No inventes datos específicos que no estén en el contexto.
- Si hace una repregunta corta, intenta resolver usando historial + último contexto estructurado antes de pedir aclaración.
- Evita cerrar siempre con frases repetitivas como "puedo ayudarte con tickets".
- Si algo no existe o no está disponible, dilo claro.
- Si el usuario pide algo humano, puedes sugerir asesor.

Contexto del usuario:
- Nombre: {$userName}
- Tipo: {$userType}

Último contexto estructurado:
{$lastContextJson}

Contexto base empresa:
{$companyBlock}

Contexto agenda:
{$agendaBlock}

Contexto catálogo:
{$catalogBlock}

Contexto marketplaces:
{$marketplacesBlock}

Contexto tickets:
{$ticketsBlock}

Historial reciente:
{$historyBlock}
PROMPT;
}

    protected function buildAiMessages(WaConversation $conversation, string $latestUserText, int $limit = 14): array
    {
        $rows = WaMessage::query()
            ->where('conversation_id', $conversation->id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['direction', 'text'])
            ->reverse()
            ->values();

        $messages = [];

        foreach ($rows as $row) {
            $msgText = trim((string) $row->text);
            if ($msgText === '') {
                continue;
            }

            $messages[] = [
                'role' => $row->direction === 'outbound' ? 'assistant' : 'user',
                'text' => $msgText,
            ];
        }

        $last = end($messages);

        if (!$last || $last['role'] !== 'user' || trim((string) $last['text']) !== trim($latestUserText)) {
            $messages[] = [
                'role' => 'user',
                'text' => $latestUserText,
            ];
        }

        return $messages;
    }

    protected function buildConversationHistoryText(WaConversation $conversation, int $limit = 12): string
    {
        $rows = WaMessage::query()
            ->where('conversation_id', $conversation->id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['direction', 'text'])
            ->reverse()
            ->values();

        if ($rows->isEmpty()) {
            return 'Sin historial reciente.';
        }

        $lines = [];

        foreach ($rows as $row) {
            $text = trim((string) $row->text);
            if ($text === '') {
                continue;
            }

            $speaker = $row->direction === 'outbound' ? 'Asistente' : 'Usuario';
            $lines[] = $speaker.': '.Str::limit(preg_replace('/\s+/', ' ', $text), 250);
        }

        return !empty($lines) ? implode("\n", $lines) : 'Sin historial reciente.';
    }

    protected function getLastStructuredContext(WaConversation $conversation): array
    {
        $msg = WaMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('direction', 'outbound')
            ->whereNotNull('meta')
            ->orderByDesc('id')
            ->first(['meta', 'text']);

        if (!$msg) {
            return [];
        }

        $meta = $msg->meta;

        if (is_string($meta)) {
            $decoded = json_decode($meta, true);
            $meta = is_array($decoded) ? $decoded : [];
        }

        return is_array($meta) ? $meta : [];
    }

    protected function sendTrackedText(WaConversation $conversation, string $text, array $meta = []): array
    {
        $wa = app(WhatsAppService::class);

        $result = $wa->sendText($conversation->phone, $text, $conversation);

        if (!empty($meta)) {
            $msg = WaMessage::query()
                ->where('conversation_id', $conversation->id)
                ->where('direction', 'outbound')
                ->latest('id')
                ->first();

            if ($msg) {
                $currentMeta = $msg->meta;

                if (is_string($currentMeta)) {
                    $decoded = json_decode($currentMeta, true);
                    $currentMeta = is_array($decoded) ? $decoded : [];
                }

                if (!is_array($currentMeta)) {
                    $currentMeta = [];
                }

                $msg->meta = array_merge($currentMeta, $meta);
                $msg->save();
            }
        }

        return $result;
    }

    protected function companyKnowledgeBlock(): string
    {
        return <<<TXT
Jureto es una empresa comercializadora y operativa enfocada en productos e insumos para distintas necesidades.
Puede manejar líneas como papelería, oficina, tecnología, cómputo, limpieza, construcción, material eléctrico/electrónico y otras categorías relacionadas.
El asistente debe responder en general sobre la empresa y no asumir que todo se trata de tickets.
TXT;
    }

    protected function agendaKnowledgeBlock(User $user): string
    {
        if (!class_exists(AgendaEvent::class) || !Schema::hasTable('agenda_events')) {
            return 'La agenda no está disponible.';
        }

        $query = $this->agendaQueryForUser($user);

        $todayCount = (clone $query)
            ->when(Schema::hasColumn('agenda_events', 'start_at'), function ($q) {
                $q->whereBetween('start_at', [now()->startOfDay(), now()->endOfDay()]);
            })
            ->count();

        $nextEvents = (clone $query)
            ->when(Schema::hasColumn('agenda_events', 'start_at'), function ($q) {
                $q->where('start_at', '>=', now());
            })
            ->orderBy('start_at')
            ->limit(4)
            ->get(['title', 'start_at', 'repeat_rule', 'remind_offset_minutes']);

        $lines = [];
        foreach ($nextEvents as $ev) {
            $lines[] = '- '.($ev->start_at ? $ev->start_at->format('d/m h:i A') : 'Sin hora').' | '.$ev->title;
        }

        $preview = !empty($lines) ? implode("\n", $lines) : 'No hay próximos eventos visibles.';

        return <<<TXT
La agenda maneja eventos con título, descripción, fecha/hora, timezone, repeat_rule, remind_offset_minutes y destinatarios por usuarios.
Eventos de hoy del usuario: {$todayCount}
Próximos eventos:
{$preview}
TXT;
    }

    protected function catalogKnowledgeBlock(): string
    {
        if (!$this->catalogAvailable()) {
            return 'El catálogo no está disponible.';
        }

        $total = CatalogItem::query()->count();
        $published = Schema::hasColumn('catalog_items', 'status')
            ? CatalogItem::query()->where('status', 1)->count()
            : 0;

        $featured = Schema::hasColumn('catalog_items', 'is_featured')
            ? CatalogItem::query()->where('is_featured', true)->count()
            : 0;

        $lowStock = Schema::hasColumn('catalog_items', 'stock')
            ? CatalogItem::query()->where('stock', '<=', 5)->count()
            : 0;

        $sampleItems = CatalogItem::query()
            ->orderByDesc('id')
            ->limit(5)
            ->get(['name', 'sku', 'price', 'stock', 'category_key']);

        $lines = [];
        foreach ($sampleItems as $item) {
            $lines[] = '- '.$item->name
                .' | SKU: '.($item->sku ?: '—')
                .' | $'.number_format((float) $item->price, 2)
                .' | stock: '.(int) ($item->stock ?? 0)
                .' | categoría: '.($item->category_key ?: '—');
        }

        $preview = !empty($lines) ? implode("\n", $lines) : 'No hay productos de muestra.';

        return <<<TXT
El sistema maneja catálogo con nombre, slug, sku, precio, stock, destacado, category_key, brand_name, model_name, meli_gtin, descripción, fotos y sincronización.
Resumen:
- Total productos: {$total}
- Publicados: {$published}
- Destacados: {$featured}
- Stock bajo: {$lowStock}
Muestra:
{$preview}
TXT;
    }

    protected function marketplacesKnowledgeBlock(): string
    {
        if (!$this->catalogAvailable()) {
            return 'No hay contexto de marketplaces disponible.';
        }

        $mlWithId = Schema::hasColumn('catalog_items', 'meli_item_id')
            ? CatalogItem::query()->whereNotNull('meli_item_id')->count()
            : 0;

        $mlActive = Schema::hasColumn('catalog_items', 'meli_status')
            ? CatalogItem::query()->whereIn('meli_status', ['active', 'ACTIVO', 'Activo'])->count()
            : 0;

        $withSku = Schema::hasColumn('catalog_items', 'sku')
            ? CatalogItem::query()->whereNotNull('sku')->where('sku', '!=', '')->count()
            : 0;

        return <<<TXT
El catálogo se relaciona con marketplaces.
Resumen:
- Con ID/publicación de Mercado Libre: {$mlWithId}
- Activos en Mercado Libre: {$mlActive}
- Con SKU útil para Amazon: {$withSku}
TXT;
    }

    protected function ticketsKnowledgeBlock(User $user): string
    {
        if (!Schema::hasTable('tickets')) {
            return 'No hay módulo de tickets disponible.';
        }

        $pendingCount = Ticket::query()
            ->where('assignee_id', $user->id)
            ->whereNotIn('status', ['completado', 'cancelado'])
            ->count();

        $byArea = [];

        if (Schema::hasColumn('tickets', 'area')) {
            $rows = Ticket::query()
                ->selectRaw('area, COUNT(*) as total')
                ->whereNotIn('status', ['completado', 'cancelado'])
                ->groupBy('area')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            foreach ($rows as $row) {
                $byArea[] = '- '.($row->area ?: 'Sin área').': '.(int) $row->total;
            }
        }

        $areasText = !empty($byArea) ? implode("\n", $byArea) : 'Sin resumen por área disponible.';

        return <<<TXT
Los tickets existen, pero no deben dominar la conversación si no se preguntaron.
Pendientes asignados al usuario: {$pendingCount}
Tickets abiertos por área:
{$areasText}
TXT;
    }

    protected function replyContextualFollowUp(WaConversation $conversation, User $user, string $textLower): bool
    {
        $ctx = $this->getLastStructuredContext($conversation);

        if (empty($ctx)) {
            return false;
        }

        $topic = (string) ($ctx['topic'] ?? '');

        if ($this->asksWhyFollowUp($textLower) && in_array($topic, ['ticket_most_urgent', 'ticket_most_urgent_reason'], true)) {
            $this->replyWhyMostUrgent($conversation, $user);
            return true;
        }

        if ($this->asksMoreDetailFollowUp($textLower) && !empty($ctx['ticket_folio'])) {
            $this->replyTicketFullDetail($conversation, $user, (string) $ctx['ticket_folio']);
            return true;
        }

        if ($this->asksDueDateFollowUp($textLower) && !empty($ctx['ticket_folio'])) {
            $this->replyTicketStatus($conversation, $user, (string) $ctx['ticket_folio']);
            return true;
        }

        if (in_array($textLower, ['si', 'sí', 'ok', 'va', 'dale'], true) && $topic === 'ticket_most_urgent') {
            $this->replyWhyMostUrgent($conversation, $user);
            return true;
        }

        if (in_array($textLower, ['y esos', 'y esos cuales', 'cuales', 'cuáles', 'muestrame mas', 'muéstrame más'], true)) {
            if (($ctx['catalog_focus'] ?? null) === 'low_stock') {
                $this->replyLowStockProducts($conversation);
                return true;
            }

            if (($ctx['catalog_focus'] ?? null) === 'featured') {
                $this->replyFeaturedProducts($conversation);
                return true;
            }
        }

        if (in_array($textLower, ['y luego', 'despues', 'después', 'que sigue', 'qué sigue'], true) && str_starts_with($topic, 'agenda_')) {
            $this->replyUpcomingMeetings($conversation, $user);
            return true;
        }

        return false;
    }

    protected function agendaQueryForUser(User $user)
    {
        $query = AgendaEvent::query();

        if (Schema::hasColumn('agenda_events', 'user_id')) {
            $query->where('user_id', $user->id);
        } elseif (Schema::hasColumn('agenda_events', 'created_by')) {
            $query->where('created_by', $user->id);
        } elseif (Schema::hasColumn('agenda_events', 'user_ids')) {
            $query->whereJsonContains('user_ids', $user->id);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    protected function catalogAvailable(): bool
    {
        return class_exists(CatalogItem::class) && Schema::hasTable('catalog_items');
    }

    protected function inferTopicFromText(string $text): string
    {
        $t = mb_strtolower($text);

        if ($this->asksPendingTickets($t) || $this->asksMostUrgent($t) || $this->asksTicketDeepDetail($t) || $this->extractTicketFolio($t)) {
            return 'tickets';
        }

        if ($this->asksTodayAgenda($t) || $this->asksUpcomingMeetings($t)) {
            return 'agenda';
        }

        if ($this->asksLowStock($t)) {
            return 'catalog_low_stock';
        }

        if ($this->asksFeaturedProducts($t)) {
            return 'catalog_featured';
        }

        if ($this->asksMarketplaceSummary($t)) {
            return 'marketplace_summary';
        }

        if ($this->asksTicketsByArea($t)) {
            return 'tickets_by_area';
        }

        if (
            str_contains($t, 'empresa')
            || str_contains($t, 'jureto')
            || str_contains($t, 'a que se dedica')
            || str_contains($t, 'a qué se dedica')
        ) {
            return 'company_info';
        }

        if (
            str_contains($t, 'producto')
            || str_contains($t, 'productos')
            || str_contains($t, 'catalogo')
            || str_contains($t, 'catálogo')
            || str_contains($t, 'inventario')
            || str_contains($t, 'stock')
        ) {
            return 'catalog';
        }

        return 'general';
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
            || str_contains($text, 'porque');
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
            || str_contains($text, 'qué tengo que hacer')
            || str_contains($text, 'muestrame mas')
            || str_contains($text, 'muéstrame más');
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
            || str_contains($text, 'cuál urge')
            || str_contains($text, 'que urge')
            || str_contains($text, 'qué urge')
            || str_contains($text, 'cual urge mas')
            || str_contains($text, 'cuál urge más')
            || str_contains($text, 'que ticket urge')
            || str_contains($text, 'qué ticket urge');
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

    protected function asksLowStock(string $text): bool
    {
        return str_contains($text, 'poco stock')
            || str_contains($text, 'stock bajo')
            || str_contains($text, 'bajo inventario')
            || str_contains($text, 'productos con poco stock')
            || str_contains($text, 'productos con stock bajo');
    }

    protected function asksFeaturedProducts(string $text): bool
    {
        return str_contains($text, 'productos destacados')
            || str_contains($text, 'destacados')
            || str_contains($text, 'productos featured');
    }

    protected function asksUpcomingMeetings(string $text): bool
    {
        return str_contains($text, 'proximas reuniones')
            || str_contains($text, 'próximas reuniones')
            || str_contains($text, 'proximos eventos')
            || str_contains($text, 'próximos eventos')
            || str_contains($text, 'que sigue en mi agenda')
            || str_contains($text, 'qué sigue en mi agenda');
    }

    protected function asksMarketplaceSummary(string $text): bool
    {
        return str_contains($text, 'mercado libre')
            || str_contains($text, 'amazon')
            || str_contains($text, 'marketplace')
            || str_contains($text, 'marketplaces')
            || str_contains($text, 'resumen de ml')
            || str_contains($text, 'resumen de amazon')
            || str_contains($text, 'publicaciones activas');
    }

    protected function asksTicketsByArea(string $text): bool
    {
        return str_contains($text, 'tickets por area')
            || str_contains($text, 'tickets por área')
            || str_contains($text, 'tickets por departamento')
            || str_contains($text, 'resumen de tickets por area')
            || str_contains($text, 'resumen de tickets por área');
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

        return isset($user->status) && (string) $user->status === 'approved';
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