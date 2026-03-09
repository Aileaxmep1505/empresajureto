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

        if ($text === '') {
            $this->sendTrackedText($conversation, 'Recibí tu mensaje, pero no pude leerlo bien. ¿Me lo mandas otra vez?', [
                'topic' => 'empty_message',
                'source' => 'rule',
            ]);
            return;
        }

        if (!$user) {
            $this->replyForUnknownUser($conversation);
            return;
        }

        if ($this->wantsHuman($text)) {
            $this->handoffToHuman($conversation, 'Solicitado por el usuario');
            $this->sendTrackedText($conversation, 'Listo, ya te canalicé con un asesor humano.', [
                'topic' => 'handoff',
                'source' => 'rule',
            ]);
            return;
        }

        if ($this->isGreeting($text)) {
            $this->sendTrackedText($conversation, 'Hola '.$this->firstName($user->name).'. ¿En qué te ayudo?', [
                'topic' => 'greeting',
                'source' => 'rule',
            ]);
            return;
        }

        // Folio explícito sigue siendo una regla dura útil.
        if ($folio = $this->extractTicketFolio($text)) {
            $this->replyTicketByExplicitFolio($conversation, $user, $folio, $text);
            return;
        }

        // Todo lo demás: IA como router + IA como redactor.
        $this->replyWithSemanticAssistant($conversation, $user, $text);
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

    protected function replyTicketByExplicitFolio(WaConversation $conversation, User $user, string $folio, string $text): void
    {
        $ticket = $this->findVisibleTicketForInternalUser($user, $folio);

        if (!$ticket) {
            $this->sendTrackedText(
                $conversation,
                "No encontré el ticket {$folio} relacionado contigo.",
                [
                    'topic' => 'ticket_lookup',
                    'intent' => 'ticket_query',
                    'ticket_folio' => $folio,
                    'source' => 'rule',
                ]
            );
            return;
        }

        $dueText = $ticket->due_at ? $ticket->due_at->format('d/m/Y h:i A') : 'Sin fecha límite';
        $desc = trim((string) ($ticket->description ?? ''));
        $desc = $desc !== '' ? Str::limit($desc, 300) : 'Sin descripción registrada';

        $mode = $this->looksLikeTicketDeepDetail($text) ? 'detail' : 'status';

        if ($mode === 'detail') {
            $reply = "Detalle de {$ticket->folio}:\n"
                ."Título: {$ticket->title}\n"
                ."Estado: {$ticket->status}\n"
                ."Prioridad: {$ticket->priority}\n"
                ."Área: {$ticket->area}\n"
                ."Vence: {$dueText}\n"
                ."Descripción: {$desc}\n"
                ."Qué hacer: ".$this->buildSimpleActionHint($ticket);

            $meta = [
                'topic' => 'ticket_detail',
                'intent' => 'ticket_query',
                'ticket_folio' => $ticket->folio,
                'ticket_area' => $ticket->area,
                'source' => 'rule',
            ];
        } else {
            $reply = "Estado de {$ticket->folio}:\n"
                ."Título: {$ticket->title}\n"
                ."Estado: {$ticket->status}\n"
                ."Prioridad: {$ticket->priority}\n"
                ."Área: {$ticket->area}\n"
                ."Vence: {$dueText}";

            $meta = [
                'topic' => 'ticket_status',
                'intent' => 'ticket_query',
                'ticket_folio' => $ticket->folio,
                'ticket_area' => $ticket->area,
                'source' => 'rule',
            ];
        }

        $this->sendTrackedText($conversation, $reply, $meta);
    }

  protected function replyWithSemanticAssistant(WaConversation $conversation, User $user, string $text): void
{
    $route = $this->routeIntentWithAi($conversation, $user, $text);

    if (!$route['ok']) {
        \Log::warning('whatsapp.ai.semantic.route_not_ok', [
            'message' => $text,
        ]);

        $this->sendTrackedText($conversation, 'Entiendo. Cuéntame un poco más y te ayudo mejor.', [
            'topic' => 'fallback',
            'source' => 'router_error',
        ]);
        return;
    }

    $intent = (string) ($route['intent'] ?? 'general_internal_assistant');

    \Log::info('whatsapp.ai.semantic.intent', [
        'message' => $text,
        'intent' => $intent,
        'route' => $route,
    ]);

    $context = $this->fetchDomainContext($intent, $conversation, $user, $route);

    \Log::info('whatsapp.ai.semantic.context', [
        'intent' => $intent,
        'context' => $context,
    ]);

    $reply = $this->composeReplyWithAi($conversation, $user, $text, $route, $context);

    if (!$reply['ok']) {
        \Log::warning('whatsapp.ai.semantic.compose_not_ok', [
            'message' => $text,
            'intent' => $intent,
            'route' => $route,
            'context' => $context,
        ]);

        $this->sendTrackedText($conversation, 'Entiendo. Cuéntame un poco más y te ayudo mejor.', [
            'topic' => 'fallback',
            'intent' => $intent,
            'source' => 'composer_error',
        ]);
        return;
    }

    $meta = [
        'topic' => $this->mapIntentToTopic($intent),
        'intent' => $intent,
        'source' => 'ai',
        'time_scope' => $route['time_scope'] ?? null,
        'user_scope' => $route['user_scope'] ?? null,
        'focus' => $route['focus'] ?? null,
        'ticket_folio' => $route['ticket_folio'] ?? ($context['ticket_folio'] ?? null),
        'ticket_area' => $route['area'] ?? ($context['ticket_area'] ?? null),
        'agenda_event_id' => $context['agenda_event_id'] ?? null,
        'item_id' => $context['item_id'] ?? null,
        'catalog_focus' => $context['catalog_focus'] ?? null,
        'response_id' => $reply['id'] ?? null,
        'router_response_id' => $route['response_id'] ?? null,
    ];

    $this->sendTrackedText($conversation, $reply['text'], $meta);
}

    protected function routeIntentWithAi(WaConversation $conversation, User $user, string $text): array
{
    $openai = app(OpenAIResponsesService::class);

    $last = $this->getLastStructuredContext($conversation);
    $history = $this->buildConversationHistoryText($conversation, 10);

    $instructions = <<<PROMPT
Eres el router semántico del asistente interno de Jureto.
No respondas como asistente.
Solo clasifica intención y extrae parámetros.

Interpreta correctamente preguntas libres como:
- cuándo será mi próxima junta
- qué tengo en agenda
- qué hay este mes
- qué anda bajo de inventario
- cómo va mercado libre
- qué ticket urge más
- y por qué
- de qué es la empresa

Usa también el historial reciente y el último contexto estructurado para entender seguimientos cortos como:
"si", "sí", "por qué", "y luego", "y ese", "cuándo", "cuál", "muéstrame más".

Reglas:
- Si habla de reuniones, agenda, eventos o programación personal => agenda_query
- Si habla de ticket específico o estado de tickets => ticket_query
- Si pregunta cuál urge más => ticket_priority
- Si habla de productos o inventario en general => catalog_query
- Si habla de bajo stock => catalog_low_stock
- Si habla de destacados => catalog_featured
- Si habla de Mercado Libre o Amazon => marketplace_summary
- Si pregunta por la empresa o a qué se dedica => company_info
- Si pide ayuda general => help
- Si pide asesor/humano => handoff_human
- Si no está claro, usa general_internal_assistant
PROMPT;

    $input = [
        'message' => $text,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
        ],
        'last_context' => $last,
        'history' => $history,
    ];

    $result = $openai->routeStructured($instructions, $input);

    if (!$result['ok']) {
        \Log::warning('whatsapp.ai.route.failed', [
            'message' => $text,
            'result' => $result,
        ]);

        return ['ok' => false];
    }

    $data = $result['data'];

    \Log::info('whatsapp.ai.route.ok', [
        'message' => $text,
        'route' => $data,
    ]);

    return [
        'ok' => true,
        'intent' => $data['intent'] ?? 'general_internal_assistant',
        'confidence' => (float) ($data['confidence'] ?? 0),
        'needs_db' => (bool) ($data['needs_db'] ?? true),
        'time_scope' => $data['time_scope'] ?? null,
        'user_scope' => $data['user_scope'] ?? 'self',
        'focus' => $data['focus'] ?? null,
        'ticket_folio' => $data['ticket_folio'] ?? null,
        'area' => $data['area'] ?? null,
        'limit' => max(1, min(12, (int) ($data['limit'] ?? 5))),
        'response_id' => null,
    ];
}
  protected function composeReplyWithAi(
    WaConversation $conversation,
    User $user,
    string $text,
    array $route,
    array $context
): array {
    $openai = app(OpenAIResponsesService::class);

    $lastContext = $this->getLastStructuredContext($conversation);
    $history = $this->buildConversationHistoryText($conversation, 10);
    $company = $this->companyKnowledgeBlock();

    $instructions = <<<PROMPT
Eres el asistente de WhatsApp de Jureto.
Responde en español.
Tu tono debe ser natural, claro, útil, humano y breve.
No suenes como bot.
No repitas siempre la misma frase.
No inventes datos que no estén en el contexto.
Responde directo a lo que el usuario quiso decir, aunque lo haya expresado de forma informal.

Reglas:
- Si el usuario pregunta por agenda, responde con agenda.
- Si pregunta por productos, responde con productos.
- Si pregunta por stock bajo, responde con stock bajo.
- Si pregunta por destacados, responde con destacados.
- Si pregunta por marketplaces, responde con Mercado Libre y Amazon.
- Si pregunta por tickets, responde con tickets.
- Si pregunta por la empresa, responde sobre la empresa.
- Si el usuario hace seguimiento corto, interpreta con historial y último contexto estructurado.
- Si faltan datos exactos, dilo natural.
PROMPT;

    $payload = json_encode([
        'user_message' => $text,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
        ],
        'route' => $route,
        'db_context' => $context,
        'company_context' => $company,
        'history' => $history,
        'last_context' => $lastContext,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $previousResponseId = null;
    if (!empty($lastContext['response_id']) && is_string($lastContext['response_id'])) {
        $previousResponseId = $lastContext['response_id'];
    }

    $result = $openai->ask($instructions, $payload, $previousResponseId);

    \Log::info('whatsapp.ai.compose.raw', [
        'ok' => $result['ok'] ?? false,
        'id' => $result['id'] ?? null,
        'text' => $result['text'] ?? null,
        'route' => $route,
    ]);

    if (!$result['ok']) {
        return ['ok' => false];
    }

    $textOut = trim((string) ($result['text'] ?? ''));

    if ($textOut === '') {
        \Log::warning('whatsapp.ai.compose.empty_text', [
            'route' => $route,
            'result' => $result,
        ]);

        return ['ok' => false];
    }

    return [
        'ok' => true,
        'id' => $result['id'] ?? null,
        'text' => $textOut,
    ];
}
    protected function fetchDomainContext(string $intent, WaConversation $conversation, User $user, array $route): array
    {
        return match ($intent) {
            'company_info' => $this->fetchCompanyContext(),
            'help' => $this->fetchHelpContext(),
            'agenda_query' => $this->fetchAgendaContext($user, $route),
            'ticket_query' => $this->fetchTicketContext($conversation, $user, $route),
            'ticket_priority' => $this->fetchTicketPriorityContext($user),
            'catalog_query' => $this->fetchCatalogContext($route),
            'catalog_low_stock' => $this->fetchLowStockContext($route),
            'catalog_featured' => $this->fetchFeaturedContext($route),
            'marketplace_summary' => $this->fetchMarketplaceSummaryContext(),
            'tickets_by_area' => $this->fetchTicketsByAreaContext(),
            'handoff_human' => ['handoff' => true],
            default => $this->fetchGeneralContext($user),
        };
    }

    protected function fetchCompanyContext(): array
    {
        return [
            'company' => [
                'summary' => 'Jureto es una empresa comercializadora y operativa enfocada en productos e insumos para distintas necesidades como papelería, oficina, tecnología, cómputo, limpieza, construcción y material eléctrico o electrónico.',
            ],
        ];
    }

    protected function fetchHelpContext(): array
    {
        return [
            'help' => [
                'areas' => [
                    'información general de la empresa',
                    'agenda y próximas reuniones',
                    'productos, stock y destacados',
                    'Mercado Libre y Amazon',
                    'tickets y prioridades',
                ],
            ],
        ];
    }

    protected function fetchAgendaContext(User $user, array $route): array
    {
        if (!class_exists(AgendaEvent::class) || !Schema::hasTable('agenda_events')) {
            return ['agenda' => ['available' => false]];
        }

        $query = $this->agendaQueryForUser($user);

        $timeScope = $route['time_scope'] ?? null;

        if (Schema::hasColumn('agenda_events', 'start_at')) {
            switch ($timeScope) {
                case 'today':
                    $query->whereBetween('start_at', [now()->startOfDay(), now()->endOfDay()]);
                    break;
                case 'this_week':
                    $query->whereBetween('start_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereBetween('start_at', [now()->startOfMonth(), now()->endOfMonth()]);
                    break;
                case 'next':
                default:
                    $query->where('start_at', '>=', now());
                    break;
            }
        }

        $limit = $route['limit'] ?? 5;

        $events = $query->orderBy('start_at')->limit($limit)->get([
            'id',
            'title',
            'description',
            'start_at',
            'repeat_rule',
            'remind_offset_minutes',
            'timezone',
        ]);

        return [
            'agenda' => [
                'available' => true,
                'time_scope' => $timeScope,
                'count' => $events->count(),
                'items' => $events->map(function ($e) {
                    return [
                        'id' => $e->id,
                        'title' => $e->title,
                        'description' => $e->description,
                        'start_at' => $e->start_at ? $e->start_at->format('Y-m-d H:i:s') : null,
                        'repeat_rule' => $e->repeat_rule,
                        'remind_offset_minutes' => $e->remind_offset_minutes,
                        'timezone' => $e->timezone,
                    ];
                })->values()->all(),
            ],
            'agenda_event_id' => optional($events->first())->id,
        ];
    }

    protected function fetchTicketContext(WaConversation $conversation, User $user, array $route): array
    {
        $folio = $route['ticket_folio'] ?? null;

        if (!$folio) {
            $last = $this->getLastStructuredContext($conversation);
            $folio = $last['ticket_folio'] ?? null;
        }

        if (!$folio) {
            $tickets = Ticket::query()
                ->where('assignee_id', $user->id)
                ->whereNotIn('status', ['completado', 'cancelado'])
                ->orderByDesc('id')
                ->limit($route['limit'] ?? 5)
                ->get(['id', 'folio', 'title', 'status', 'priority', 'area', 'due_at']);

            return [
                'tickets' => [
                    'mode' => 'list',
                    'count' => $tickets->count(),
                    'items' => $tickets->map(function ($t) {
                        return [
                            'id' => $t->id,
                            'folio' => $t->folio,
                            'title' => $t->title,
                            'status' => $t->status,
                            'priority' => $t->priority,
                            'area' => $t->area,
                            'due_at' => $t->due_at ? $t->due_at->format('Y-m-d H:i:s') : null,
                        ];
                    })->values()->all(),
                ],
            ];
        }

        $ticket = $this->findVisibleTicketForInternalUser($user, $folio);

        if (!$ticket) {
            return [
                'tickets' => [
                    'mode' => 'single',
                    'found' => false,
                    'folio' => $folio,
                ],
                'ticket_folio' => $folio,
            ];
        }

        return [
            'tickets' => [
                'mode' => 'single',
                'found' => true,
                'item' => [
                    'id' => $ticket->id,
                    'folio' => $ticket->folio,
                    'title' => $ticket->title,
                    'description' => $ticket->description,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'area' => $ticket->area,
                    'due_at' => $ticket->due_at ? $ticket->due_at->format('Y-m-d H:i:s') : null,
                ],
            ],
            'ticket_folio' => $ticket->folio,
            'ticket_area' => $ticket->area,
        ];
    }

    protected function fetchTicketPriorityContext(User $user): array
    {
        $tickets = Ticket::query()
            ->where('assignee_id', $user->id)
            ->whereNotIn('status', ['completado', 'cancelado'])
            ->get(['id', 'folio', 'title', 'priority', 'status', 'due_at', 'area']);

        if ($tickets->isEmpty()) {
            return [
                'ticket_priority' => [
                    'found' => false,
                ],
            ];
        }

        $best = $tickets->sortBy(function ($t) {
            return [
                $this->priorityRank((string) $t->priority),
                $this->statusRank((string) $t->status),
                $t->due_at ? $t->due_at->timestamp : PHP_INT_MAX,
                -1 * ((int) $t->id),
            ];
        })->first();

        return [
            'ticket_priority' => [
                'found' => true,
                'item' => [
                    'id' => $best->id,
                    'folio' => $best->folio,
                    'title' => $best->title,
                    'priority' => $best->priority,
                    'status' => $best->status,
                    'due_at' => $best->due_at ? $best->due_at->format('Y-m-d H:i:s') : null,
                    'area' => $best->area,
                ],
            ],
            'ticket_folio' => $best->folio,
            'ticket_area' => $best->area,
        ];
    }

    protected function fetchCatalogContext(array $route): array
    {
        if (!$this->catalogAvailable()) {
            return ['catalog' => ['available' => false]];
        }

        $limit = $route['limit'] ?? 5;

        $items = CatalogItem::query()
            ->orderByDesc('id')
            ->limit($limit)
            ->get([
                'id',
                'name',
                'sku',
                'price',
                'sale_price',
                'stock',
                'status',
                'is_featured',
                'category_key',
                'meli_item_id',
                'meli_status',
            ]);

        return [
            'catalog' => [
                'available' => true,
                'count' => $items->count(),
                'items' => $items->map(function ($i) {
                    return [
                        'id' => $i->id,
                        'name' => $i->name,
                        'sku' => $i->sku,
                        'price' => $i->price,
                        'sale_price' => $i->sale_price,
                        'stock' => $i->stock,
                        'status' => $i->status,
                        'is_featured' => (bool) $i->is_featured,
                        'category_key' => $i->category_key,
                        'meli_item_id' => $i->meli_item_id,
                        'meli_status' => $i->meli_status,
                    ];
                })->values()->all(),
            ],
            'item_id' => optional($items->first())->id,
        ];
    }

    protected function fetchLowStockContext(array $route): array
    {
        if (!$this->catalogAvailable()) {
            return ['catalog' => ['available' => false]];
        }

        $limit = $route['limit'] ?? 8;

        $items = CatalogItem::query()
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'name', 'sku', 'price', 'stock', 'status']);

        return [
            'catalog_low_stock' => [
                'count' => $items->count(),
                'items' => $items->map(function ($i) {
                    return [
                        'id' => $i->id,
                        'name' => $i->name,
                        'sku' => $i->sku,
                        'price' => $i->price,
                        'stock' => $i->stock,
                        'status' => $i->status,
                    ];
                })->values()->all(),
            ],
            'item_id' => optional($items->first())->id,
            'catalog_focus' => 'low_stock',
        ];
    }

    protected function fetchFeaturedContext(array $route): array
    {
        if (!$this->catalogAvailable()) {
            return ['catalog' => ['available' => false]];
        }

        $limit = $route['limit'] ?? 8;

        $items = CatalogItem::query()
            ->where('is_featured', true)
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'name', 'sku', 'price', 'stock', 'status']);

        return [
            'catalog_featured' => [
                'count' => $items->count(),
                'items' => $items->map(function ($i) {
                    return [
                        'id' => $i->id,
                        'name' => $i->name,
                        'sku' => $i->sku,
                        'price' => $i->price,
                        'stock' => $i->stock,
                        'status' => $i->status,
                    ];
                })->values()->all(),
            ],
            'item_id' => optional($items->first())->id,
            'catalog_focus' => 'featured',
        ];
    }

    protected function fetchMarketplaceSummaryContext(): array
    {
        if (!$this->catalogAvailable()) {
            return ['marketplaces' => ['available' => false]];
        }

        $total = CatalogItem::query()->count();
        $published = CatalogItem::query()->where('status', 1)->count();
        $mlWithId = Schema::hasColumn('catalog_items', 'meli_item_id')
            ? CatalogItem::query()->whereNotNull('meli_item_id')->count()
            : 0;
        $mlActive = Schema::hasColumn('catalog_items', 'meli_status')
            ? CatalogItem::query()->whereIn('meli_status', ['active', 'ACTIVO', 'Activo'])->count()
            : 0;
        $amazonSku = Schema::hasColumn('catalog_items', 'sku')
            ? CatalogItem::query()->whereNotNull('sku')->where('sku', '!=', '')->count()
            : 0;

        return [
            'marketplaces' => [
                'available' => true,
                'total_products' => $total,
                'published_catalog' => $published,
                'meli_with_id' => $mlWithId,
                'meli_active' => $mlActive,
                'amazon_eligible_sku' => $amazonSku,
            ],
            'catalog_focus' => 'marketplaces',
        ];
    }

    protected function fetchTicketsByAreaContext(): array
    {
        if (!Schema::hasTable('tickets') || !Schema::hasColumn('tickets', 'area')) {
            return ['tickets_by_area' => ['available' => false]];
        }

        $rows = Ticket::query()
            ->selectRaw('area, COUNT(*) as total')
            ->whereNotIn('status', ['completado', 'cancelado'])
            ->groupBy('area')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'tickets_by_area' => [
                'available' => true,
                'items' => $rows->map(function ($r) {
                    return [
                        'area' => $r->area ?: 'Sin área',
                        'total' => (int) $r->total,
                    ];
                })->values()->all(),
            ],
        ];
    }

    protected function fetchGeneralContext(User $user): array
    {
        return [
            'general' => [
                'company' => $this->companyKnowledgeBlock(),
                'today_agenda_count' => $this->countAgendaToday($user),
                'pending_tickets_count' => $this->countPendingTickets($user),
                'catalog_total' => $this->catalogAvailable() ? CatalogItem::query()->count() : null,
            ],
        ];
    }

    protected function countAgendaToday(User $user): int
    {
        if (!class_exists(AgendaEvent::class) || !Schema::hasTable('agenda_events')) {
            return 0;
        }

        $query = $this->agendaQueryForUser($user);

        if (Schema::hasColumn('agenda_events', 'start_at')) {
            $query->whereBetween('start_at', [now()->startOfDay(), now()->endOfDay()]);
        }

        return (int) $query->count();
    }

    protected function countPendingTickets(User $user): int
    {
        if (!Schema::hasTable('tickets')) {
            return 0;
        }

        return (int) Ticket::query()
            ->where('assignee_id', $user->id)
            ->whereNotIn('status', ['completado', 'cancelado'])
            ->count();
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

    protected function getLastStructuredContext(WaConversation $conversation): array
    {
        $msg = WaMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('direction', 'outbound')
            ->whereNotNull('meta')
            ->orderByDesc('id')
            ->first(['meta']);

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

    protected function buildConversationHistoryText(WaConversation $conversation, int $limit = 10): string
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

    protected function extractJsonObject(string $text): ?string
    {
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        if (str_starts_with($text, '{') && str_ends_with($text, '}')) {
            return $text;
        }

        if (preg_match('/\{.*\}/s', $text, $m)) {
            return $m[0] ?? null;
        }

        return null;
    }

    protected function mapIntentToTopic(string $intent): string
    {
        return match ($intent) {
            'company_info' => 'company_info',
            'help' => 'help',
            'agenda_query' => 'agenda',
            'ticket_query' => 'tickets',
            'ticket_priority' => 'ticket_priority',
            'catalog_query' => 'catalog',
            'catalog_low_stock' => 'catalog_low_stock',
            'catalog_featured' => 'catalog_featured',
            'marketplace_summary' => 'marketplace_summary',
            'tickets_by_area' => 'tickets_by_area',
            'handoff_human' => 'handoff',
            default => 'general',
        };
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

    protected function wantsHuman(string $text): bool
    {
        $t = mb_strtolower($text);

        return str_contains($t, 'asesor')
            || str_contains($t, 'humano')
            || str_contains($t, 'agente')
            || str_contains($t, 'ejecutivo')
            || str_contains($t, 'persona');
    }

    protected function isGreeting(string $text): bool
    {
        $t = mb_strtolower(trim($text));

        return in_array($t, ['hola', 'buenas', 'buen día', 'buen dia', 'hey', 'holi'], true);
    }

    protected function extractTicketFolio(string $text): ?string
    {
        preg_match('/TKT-\d{4}-\d{4,}/i', $text, $matches);
        return isset($matches[0]) ? strtoupper($matches[0]) : null;
    }

    protected function looksLikeTicketDeepDetail(string $text): bool
    {
        $t = mb_strtolower($text);

        return str_contains($t, 'detalle')
            || str_contains($t, 'sobre que trata')
            || str_contains($t, 'sobre qué trata')
            || str_contains($t, 'que tengo que hacer')
            || str_contains($t, 'qué tengo que hacer')
            || str_contains($t, 'cuando vence')
            || str_contains($t, 'cuándo vence')
            || str_contains($t, 'explicame')
            || str_contains($t, 'explícame');
    }

    protected function companyKnowledgeBlock(): string
    {
        return 'Jureto es una empresa comercializadora y operativa enfocada en productos e insumos para distintas necesidades, incluyendo papelería, artículos de oficina, tecnología, cómputo, limpieza, construcción y material eléctrico o electrónico.';
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