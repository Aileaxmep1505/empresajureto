<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\HelpTicket;
use App\Models\HelpMessage;
use App\Models\Order;
use App\Models\ShippingAddress;
use App\Services\AiService;

class HelpCenterController extends Controller
{
    public function __construct(private AiService $ai) {}

    public function create()
    {
        return view('web.ayuda.create', [
            'ticket'  => null,
            'tickets' => $this->recentTickets(),
        ]);
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

        // IA con historial + contexto real del cliente
        $customerContext = $this->customerContext();
        $automationText = $this->handleTrackingAutomation($r->message) ?: $this->handleCommerceAutomation($r->message);
$aiText = $automationText ?: $this->ai->helpdeskReply($r->message, $ticket, $history, $customerContext);

        // Parseo (opcional) de bloque AI_META
        [$aiBody, $aiMeta] = $this->splitBodyAndMeta($aiText);

        $mAi = HelpMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_type' => 'ai',
            'sender_id'   => null,
            'body'        => $aiBody,
            'meta'        => $aiMeta, // <- si el modelo devolviÃƒÂ³ AI_META, lo guardamos
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

        return view('web.ayuda.create', [
            'ticket'  => $ticket,
            'tickets' => $this->recentTickets(),
        ]);
    }


    public function destroy(HelpTicket $ticket)
    {
        $this->ensureCanAccess($ticket);

        HelpMessage::where('ticket_id', $ticket->id)->delete();
        $ticket->delete();

        return redirect()->route('help.create');
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

        // IA con historial + contexto real del cliente
        $customerContext = $this->customerContext();
        $automationText = $this->handleTrackingAutomation($r->message) ?: $this->handleCommerceAutomation($r->message);
$aiText = $automationText ?: $this->ai->helpdeskReply($r->message, $ticket, $history, $customerContext);

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
            'body'        => "Tu caso fue escalado a un asesor humano. Te contactaremos aquÃƒÂ­ mismo en cuanto tome el caso.",
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



    private function customerContext(): array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        $address = $user->defaultShippingAddress()->first()
            ?: ShippingAddress::where('user_id', $user->id)
                ->orderByDesc('is_default')
                ->latest()
                ->first();

        $lastOrder = Order::with(['items', 'payments'])
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        $recentOrders = Order::where('user_id', $user->id)
            ->latest()
            ->limit(3)
            ->get(['id', 'status', 'total', 'currency', 'shipping_code', 'shipping_service', 'shipping_eta', 'shipment_status', 'created_at'])
            ->map(fn($order) => [
                'id' => $order->id,
                'status' => $order->status,
                'total' => $order->total,
                'currency' => $order->currency,
                'shipping_code' => $order->shipping_code,
                'shipping_service' => $order->shipping_service,
                'shipping_eta' => $order->shipping_eta,
                'shipment_status' => $order->shipment_status,
                'date' => optional($order->created_at)->format('d/m/Y H:i'),
            ])
            ->values()
            ->all();
    $cartRows = collect(session('cart', []))->map(function ($row) {
        $qty = max(1, (int)($row['qty'] ?? 1));
        $price = round((float)($row['price'] ?? 0), 2);

        return [
            'id' => $row['id'] ?? null,
            'name' => $row['name'] ?? 'Producto',
            'sku' => $row['sku'] ?? null,
            'qty' => $qty,
            'price' => $price,
            'amount' => round($price * $qty, 2),
            'image' => $row['image'] ?? null,
        ];
    })->values()->all();

    $cartTotal = collect($cartRows)->sum('amount');

    $favoriteRows = [];

    try {
        if (method_exists($user, 'favorites')) {
            $favoriteRows = $user->favorites()
                ->limit(30)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id ?? null,
                        'name' => $item->name ?? $item->title ?? 'Producto',
                        'sku' => $item->sku ?? null,
                        'price' => round((float)($item->sale_price ?? $item->price ?? 0), 2),
                        'slug' => $item->slug ?? null,
                        'image' => $item->image_url ?? $item->photo_1 ?? null,
                    ];
                })
                ->values()
                ->all();
        }
    } catch (\Throwable $e) {
        \Log::warning('No se pudieron cargar favoritos para IA', [
            'user_id' => $user->id ?? null,
            'message' => $e->getMessage(),
        ]);
    }


        return [
            'id' => $user->id,
            'name' => $user->name,
            'cart' => [
                'count' => collect($cartRows)->sum('qty'),
                'subtotal' => round($cartTotal, 2),
                'items' => $cartRows,
            ],
            'favorites' => [
                'count' => count($favoriteRows),
                'items' => $favoriteRows,
            ],
            'first_name' => trim(explode(' ', trim((string) $user->name))[0] ?? ''),
            'email' => $user->email,
            'phone' => $user->phone,

            'shipping_address' => $address ? [
                'contact_name' => $address->contact_name,
                'phone' => $address->phone,
                'street' => $address->street,
                'ext_number' => $address->ext_number,
                'int_number' => $address->int_number,
                'colony' => $address->colony,
                'cp' => $address->postal_code,
                'city' => $address->municipality,
                'state' => $address->state,
                'references' => $address->references,
            ] : null,

            'last_order' => $lastOrder ? [
                'id' => $lastOrder->id,
                'status' => $lastOrder->status,
                'subtotal' => $lastOrder->subtotal,
                'shipping_amount' => $lastOrder->shipping_amount,
                'tax' => $lastOrder->tax,
                'total' => $lastOrder->total,
                'currency' => $lastOrder->currency,
                'shipping_code' => $lastOrder->shipping_code,
                'shipping_name' => $lastOrder->shipping_name,
                'shipping_service' => $lastOrder->shipping_service,
                'shipping_eta' => $lastOrder->shipping_eta,
                'shipment_status' => $lastOrder->shipment_status,
                'created_at' => optional($lastOrder->created_at)->format('d/m/Y H:i'),

                'items' => $lastOrder->items->map(fn($item) => [
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'qty' => $item->qty,
                    'price' => $item->price,
                    'total' => $item->total,
                ])->values()->all(),

                'payments' => $lastOrder->payments->map(fn($payment) => [
                    'provider' => $payment->provider,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                ])->values()->all(),
            ] : null,

            'recent_orders' => $recentOrders,
        ];
    }

private function handleTrackingAutomation(string $message): ?string
{
    $user = \Auth::user();
    if (!$user) return null;

    $text = mb_strtolower(trim($message));

    $isTracking = str_contains($text, 'seguimiento')
        || str_contains($text, 'rastreo')
        || str_contains($text, 'guia')
        || str_contains($text, 'guía')
        || str_contains($text, 'paqueteria')
        || str_contains($text, 'paquetería')
        || str_contains($text, 'envio')
        || str_contains($text, 'envío')
        || str_contains($text, 'donde va')
        || str_contains($text, 'dónde va')
        || str_contains($text, 'linea del tiempo')
        || str_contains($text, 'línea del tiempo')
        || str_contains($text, 'timeline')
        || str_contains($text, 'paquete');

    if (!$isTracking) return null;

    $orderId = null;
    if (preg_match('/#?\s*(\d+)/', $text, $m)) {
        $orderId = (int) $m[1];
    }

    $query = \App\Models\Order::with(['items'])->where('user_id', $user->id);

    if ($orderId) {
        $query->where('id', $orderId);
    }

    $order = $query->latest()->first();

    if (!$order) {
        return 'No encontré un pedido asociado a tu cuenta para revisar el seguimiento. ¿Me compartes el número de pedido?';
    }

    $carrier = $order->shipping_name ?: 'Paquetería no registrada';
    $service = $order->shipping_service ?: 'Servicio no registrado';
    $guide   = $order->shipping_code ?: null;
    $eta     = $order->shipping_eta ?: null;
    $status  = $order->shipment_status ?: $order->status ?: 'Sin estatus registrado';

    $timeline = $this->buildTrackingTimeline($order);

    $msg = "Claro. Este es el seguimiento de tu pedido #{$order->id}:\n\n";
    $msg .= "- Paquetería: {$carrier}\n";
    $msg .= "- Servicio: {$service}\n";
    $msg .= "- Estatus: {$status}\n";

    if ($guide) {
        $msg .= "- Guía / código de rastreo: {$guide}\n";
    } else {
        $msg .= "- Guía / código de rastreo: aún no registrado en el sistema\n";
    }

    if ($eta) {
        $msg .= "- Entrega estimada: {$eta}\n";
    }

    if (!empty($order->shipping_tracking_url)) {
        $msg .= "- Link de rastreo: {$order->shipping_tracking_url}\n";
    }

    $msg .= "\n\nLínea del tiempo del paquete:\n";

    foreach ($timeline as $i => $event) {
        $num = $i + 1;
        $title = $event['title'] ?? 'Evento';
        $date = $event['date'] ?? 'Sin fecha';
        $eventStatus = $event['status'] ?? 'pending';

        $icon = match ($eventStatus) {
            'done' => '✅',
            'current' => '🚚',
            default => '⏳',
        };

        $msg .= "{$num}. {$icon} {$title} — {$date}\n";
    }

    if ($order->relationLoaded('items') && $order->items->count()) {
        $msg .= "\nProductos del pedido:\n";
        $msg .= $order->items->map(function ($item) {
            return '- ' . ($item->name ?? 'Producto') . ' x' . (int)($item->qty ?? 1);
        })->implode("\n");
    }

    if (env('ENVIA_TRACKING_TEST_MODE', true)) {
        $msg .= "\n\nNota: este seguimiento está en modo prueba. Cuando se active el modo real, esta misma línea del tiempo se alimentará con eventos reales de la paquetería.";
    } else {
        $msg .= "\n\nNota: este seguimiento usa información real registrada por la paquetería o proveedor logístico.";
    }

    return $msg;
}

private function buildTrackingTimeline($order): array
{
    if (env('ENVIA_TRACKING_TEST_MODE', true)) {
        return $this->buildTestTrackingTimeline($order);
    }

    return $this->buildRealTrackingTimeline($order);
}

private function buildTestTrackingTimeline($order): array
{
    $createdAt = optional($order->created_at)->format('d/m/Y H:i') ?: 'Fecha no registrada';
    $paidAt = optional($order->paid_at ?? null)->format('d/m/Y H:i');

    $isPaid = in_array((string)($order->status ?? ''), ['paid', 'pagado', 'paid_test'], true) || !empty($paidAt);
    $hasGuide = !empty($order->shipping_code);

    return [
        [
            'title' => 'Pedido creado',
            'date' => $createdAt,
            'status' => 'done',
        ],
        [
            'title' => 'Pago confirmado',
            'date' => $isPaid ? ($paidAt ?: 'Confirmado en sistema') : 'Pendiente de confirmar',
            'status' => $isPaid ? 'done' : 'pending',
        ],
        [
            'title' => $hasGuide ? 'Guía asignada' : 'Guía pendiente',
            'date' => $hasGuide ? $order->shipping_code : 'Pendiente',
            'status' => $hasGuide ? 'done' : 'pending',
        ],
        [
            'title' => $hasGuide ? 'En tránsito' : 'En preparación',
            'date' => $hasGuide ? 'Pendiente de actualización por paquetería' : 'Esperando generación o carga de guía',
            'status' => $hasGuide ? 'current' : 'pending',
        ],
        [
            'title' => 'Entrega estimada',
            'date' => $order->shipping_eta ?: 'Pendiente de confirmar',
            'status' => 'pending',
        ],
    ];
}

private function buildRealTrackingTimeline($order): array
{
    /*
     * MODO REAL:
     * Aqui despues conectamos Envia.com / Paquetexpress / DHL / FedEx.
     * La salida debe conservar este mismo formato:
     *
     * [
     *   ['title' => 'Recolectado', 'date' => '...', 'status' => 'done'],
     *   ['title' => 'En tránsito', 'date' => '...', 'status' => 'current'],
     * ]
     *
     * Asi no se cambia la IA ni la vista, solo la fuente de datos.
     */

    $events = [];

    $meta = $order->shipping_meta ?? null;

    if (is_string($meta)) {
        $decoded = json_decode($meta, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $meta = $decoded;
        }
    }

    if (is_array($meta)) {
        $rawEvents = $meta['events'] ?? $meta['tracking_events'] ?? [];

        if (is_array($rawEvents) && count($rawEvents) > 0) {
            foreach ($rawEvents as $event) {
                $events[] = [
                    'title' => $event['title'] ?? $event['status'] ?? $event['description'] ?? 'Movimiento de paquetería',
                    'date' => $event['date'] ?? $event['datetime'] ?? $event['created_at'] ?? 'Sin fecha',
                    'status' => $event['timeline_status'] ?? $event['status_type'] ?? 'done',
                ];
            }
        }
    }

    if (!empty($events)) {
        return $events;
    }

    // Si ya esta en modo real pero todavia no hay eventos guardados,
    // regresamos una linea base con datos reales del pedido.
    return [
        [
            'title' => 'Pedido creado',
            'date' => optional($order->created_at)->format('d/m/Y H:i') ?: 'Fecha no registrada',
            'status' => 'done',
        ],
        [
            'title' => !empty($order->shipping_code) ? 'Guía registrada' : 'Guía no registrada',
            'date' => !empty($order->shipping_code) ? $order->shipping_code : 'Pendiente',
            'status' => !empty($order->shipping_code) ? 'done' : 'pending',
        ],
        [
            'title' => $order->shipment_status ?: 'Sin movimientos registrados',
            'date' => $order->shipping_eta ?: 'Pendiente de actualización',
            'status' => !empty($order->shipment_status) ? 'current' : 'pending',
        ],
    ];
}

private function handleCommerceAutomation(string $message): ?string
{
    $user = \Auth::user();
    if (!$user) return null;

    $text = mb_strtolower(trim($message));

    $mentionsCart = str_contains($text, 'carrito');
    $mentionsFav = str_contains($text, 'favorito') || str_contains($text, 'favoritos') || str_contains($text, 'guardados') || str_contains($text, 'preferidos');

    $mentionsProducts = str_contains($text, 'producto')
        || str_contains($text, 'productos')
        || str_contains($text, 'catÃƒÂ¡logo')
        || str_contains($text, 'catalogo')
        || str_contains($text, 'tienda')
        || str_contains($text, 'almacÃƒÂ©n')
        || str_contains($text, 'almacen')
        || str_contains($text, 'existencia')
        || str_contains($text, 'existencias')
        || str_contains($text, 'stock')
        || str_contains($text, 'disponible')
        || str_contains($text, 'disponibles');

    $mentionsOffers = str_contains($text, 'oferta')
        || str_contains($text, 'ofertas')
        || str_contains($text, 'descuento')
        || str_contains($text, 'descuentos')
        || str_contains($text, 'promociÃƒÂ³n')
        || str_contains($text, 'promocion')
        || str_contains($text, 'promociones');

    $mentionsServices = str_contains($text, 'servicio')
        || str_contains($text, 'servicios');

    $isGeneralStoreQuestion = !$mentionsCart && !$mentionsFav && ($mentionsProducts || $mentionsOffers || $mentionsServices);

    if ($isGeneralStoreQuestion) {
        session(['ai.commerce_context' => 'store']);

        if ($mentionsOffers) {
            return $this->aiOffersSummary();
        }

        if ($mentionsServices) {
            return $this->aiServicesSummary();
        }

        if (str_contains($text, 'existencia') || str_contains($text, 'existencias') || str_contains($text, 'stock') || str_contains($text, 'disponible') || str_contains($text, 'disponibles') || str_contains($text, 'almacÃƒÂ©n') || str_contains($text, 'almacen')) {
            return $this->aiCatalogStockSummary();
        }

        return $this->aiCatalogSummary();
    }

    $hasImplicitCommerceAction = str_contains($text, 'agrega')
        || str_contains($text, 'agregar')
        || str_contains($text, 'aÃƒÂ±ade')
        || str_contains($text, 'anade')
        || str_contains($text, 'pon ')
        || str_contains($text, 'mete ')
        || str_contains($text, 'guarda')
        || str_contains($text, 'quita')
        || str_contains($text, 'quitar')
        || str_contains($text, 'elimina')
        || str_contains($text, 'eliminar')
        || str_contains($text, 'borra')
        || str_contains($text, 'borrar');

    $lastCommerceContext = session('ai.commerce_context');

    if (!$mentionsCart && !$mentionsFav && $lastCommerceContext === 'cart') {
        $mentionsCart = true;
    }

    if (!$mentionsCart && !$mentionsFav && $lastCommerceContext === 'favorites') {
        $mentionsFav = true;
    }

    if (!$mentionsCart && !$mentionsFav && !$hasImplicitCommerceAction) return null;

    if ($mentionsCart) {
        session(['ai.commerce_context' => 'cart']);
    }

    if ($mentionsFav) {
        session(['ai.commerce_context' => 'favorites']);
    }

    $wantsAdd = str_contains($text, 'agrega') || str_contains($text, 'agregar') || str_contains($text, 'aÃƒÂ±ade') || str_contains($text, 'anade') || str_contains($text, 'pon ') || str_contains($text, 'mete ') || str_contains($text, 'guarda');
    $wantsRemove = str_contains($text, 'quita') || str_contains($text, 'quitar') || str_contains($text, 'elimina') || str_contains($text, 'eliminar') || str_contains($text, 'borra') || str_contains($text, 'borrar');
    $wantsView = str_contains($text, 'quÃƒÂ© hay') || str_contains($text, 'que hay') || str_contains($text, 'ver') || str_contains($text, 'muestra') || str_contains($text, 'tengo') || str_contains($text, 'lista');
    $wantsMove = str_contains($text, 'mueve') || str_contains($text, 'mover') || str_contains($text, 'pasa') || str_contains($text, 'pasar');
    $wantsClear = str_contains($text, 'vaciar') || str_contains($text, 'vacÃƒÂ­a') || str_contains($text, 'vacia') || str_contains($text, 'elimina todos') || str_contains($text, 'borra todos');
    $allFavs = str_contains($text, 'todos mis favoritos') || str_contains($text, 'todos los favoritos') || str_contains($text, 'todo lo de favoritos');

    if ($mentionsCart && $wantsView && !$wantsAdd && !$wantsRemove && !$wantsClear) {
        return $this->aiCartSummary();
    }

    if ($mentionsFav && $wantsView && !$wantsAdd && !$wantsRemove && !$wantsMove && !$wantsClear) {
        return $this->aiFavoritesSummary($user);
    }

    if ($mentionsFav && $mentionsCart && $wantsAdd && $allFavs) {
        $items = $this->aiFavoriteItems($user);
        if ($items->isEmpty()) return 'No encontrÃƒÂ© productos guardados en tus favoritos.';

        foreach ($items as $item) {
            $this->aiPutItemInCart($item, 1);
        }

        return 'Listo, agreguÃƒÂ© todos tus favoritos al carrito. ' . $this->aiCartSummary();
    }

    if ($mentionsFav && $mentionsCart && ($wantsAdd || $wantsMove)) {
        return $this->aiAddFavoriteToCart($user, $text, $wantsMove);
    }

    if ($mentionsCart && $wantsAdd) {
        $multiAdd = $this->aiAddMultipleProductsToCart($text);
        if ($multiAdd !== null) return $multiAdd;

        return $this->aiAddProductToCart($text);
    }

    if ($wantsRemove && !$mentionsFav) {
        session(['ai.commerce_context' => 'cart']);
        return $this->aiDecreaseProductsFromCart($text);
    }

    if ($mentionsCart && $wantsRemove) {
        return $this->aiRemoveProductFromCart($text);
    }

    if ($mentionsFav && $wantsAdd && !$mentionsCart) {
        return $this->aiAddProductToFavorites($user, $text);
    }

    if ($mentionsFav && $wantsRemove && !$mentionsCart) {
        session(['ai.commerce_context' => 'favorites']);
        return $this->aiRemoveProductFromFavorites($user, $text);
    }

    if ($mentionsFav) {
        return $this->aiSearchFavorites($user, $text);
    }

    return null;
}


private function aiCatalogSummary(): string
{
    $query = \App\Models\CatalogItem::query();

    if (\Schema::hasColumn('catalog_items', 'status')) {
        $allowedStatuses = ['published', 'active', 'activo', '1'];
        $statusCount = (clone $query)->whereIn('status', $allowedStatuses)->count();

        if ($statusCount > 0) {
            $query->whereIn('status', $allowedStatuses);
        }
    }

    if (\Schema::hasColumn('catalog_items', 'is_active')) {
        $query->where('is_active', 1);
    }

    if (\Schema::hasColumn('catalog_items', 'active')) {
        $query->where('active', 1);
    }

    $items = $query->orderBy('name')->limit(20)->get();

    if ($items->isEmpty()) {
        return 'Por el momento no encontrÃƒÂ© productos publicados en la tienda.';
    }

    $list = $items->map(function ($item) {
        return '- ' . $item->name . ' Ã¢â‚¬â€ $' . number_format((float)($item->sale_price ?? $item->price ?? 0), 2) . ' MXN';
    })->implode("\n");

    return "Estos son algunos productos disponibles en la tienda:\n\n" . $list . "\n\nPuedo ayudarte a agregar cualquiera al carrito o guardarlo en favoritos.";
}

private function aiCatalogStockSummary(): string
{
    $query = \App\Models\CatalogItem::query();

    if (\Schema::hasColumn('catalog_items', 'status')) {
        $allowedStatuses = ['published', 'active', 'activo', '1'];
        $statusCount = (clone $query)->whereIn('status', $allowedStatuses)->count();

        if ($statusCount > 0) {
            $query->whereIn('status', $allowedStatuses);
        }
    }

    if (\Schema::hasColumn('catalog_items', 'is_active')) {
        $query->where('is_active', 1);
    }

    if (\Schema::hasColumn('catalog_items', 'active')) {
        $query->where('active', 1);
    }

    if (\Schema::hasColumn('catalog_items', 'stock')) {
        $stockCount = (clone $query)->where('stock', '>', 0)->count();

        if ($stockCount > 0) {
            $query->where('stock', '>', 0);
        }
    }

    $items = $query->orderBy('name')->limit(20)->get();

    if ($items->isEmpty()) {
        return 'Por el momento no encontrÃƒÂ© productos con existencia disponible.';
    }

    $hasStock = \Schema::hasColumn('catalog_items', 'stock');

    $list = $items->map(function ($item) use ($hasStock) {
        $stockText = $hasStock ? (' Ã¢â‚¬â€ stock: ' . (int)($item->stock ?? 0)) : '';
        return '- ' . $item->name . ' Ã¢â‚¬â€ $' . number_format((float)($item->sale_price ?? $item->price ?? 0), 2) . ' MXN' . $stockText;
    })->implode("\n");

    return "Estos productos aparecen disponibles en catÃƒÂ¡logo/almacÃƒÂ©n:\n\n" . $list . "\n\nPuedo ayudarte a agregar alguno al carrito o guardarlo en favoritos.";
}

private function aiOffersSummary(): string
{
    $query = \App\Models\CatalogItem::query();

    if (\Schema::hasColumn('catalog_items', 'status')) {
        $allowedStatuses = ['published', 'active', 'activo', '1'];
        $statusCount = (clone $query)->whereIn('status', $allowedStatuses)->count();

        if ($statusCount > 0) {
            $query->whereIn('status', $allowedStatuses);
        }
    }

    if (\Schema::hasColumn('catalog_items', 'is_active')) {
        $query->where('is_active', 1);
    }

    if (\Schema::hasColumn('catalog_items', 'active')) {
        $query->where('active', 1);
    }

    if (\Schema::hasColumn('catalog_items', 'sale_price') && \Schema::hasColumn('catalog_items', 'price')) {
        $offerCount = (clone $query)
            ->whereNotNull('sale_price')
            ->whereColumn('sale_price', '<', 'price')
            ->count();

        if ($offerCount > 0) {
            $query->whereNotNull('sale_price')
                  ->whereColumn('sale_price', '<', 'price');
        }
    }

    $items = $query->orderBy('name')->limit(20)->get();

    if ($items->isEmpty()) {
        return 'Por el momento no encontrÃƒÂ© ofertas activas.';
    }

    $list = $items->map(function ($item) {
        $price = number_format((float)($item->price ?? 0), 2);
        $sale = number_format((float)($item->sale_price ?? $item->price ?? 0), 2);
        return '- ' . $item->name . ' Ã¢â‚¬â€ antes $' . $price . ', ahora $' . $sale . ' MXN';
    })->implode("\n");

    return "Estas son algunas ofertas disponibles:\n\n" . $list . "\n\nPuedo ayudarte a agregar alguna al carrito o guardarla en favoritos.";
}

private function aiServicesSummary(): string
{
    return "Estos son los servicios que ofrecemos:\n\n" .
        "- AsesorÃƒÂ­a en equipamiento de oficina.\n" .
        "- Mantenimiento bÃƒÂ¡sico de equipos.\n" .
        "- Impresoras y redes locales.\n" .
        "- Tienda para instituciones educativas.\n" .
        "- Venta por mayoreo.\n\n" .
        "TambiÃƒÂ©n puedo ayudarte a buscar productos, revisar ofertas, agregar al carrito o consultar tus favoritos.";
}

private function aiFavoriteItems($user)
{
    $ids = \DB::table('favorites')
        ->where('user_id', $user->id)
        ->pluck('catalog_item_id')
        ->filter()
        ->values()
        ->all();

    if (empty($ids)) return collect();

    return \App\Models\CatalogItem::whereIn('id', $ids)->get()->values();
}

private function aiFavoritesSummary($user): string
{
    $items = $this->aiFavoriteItems($user);

    if ($items->isEmpty()) {
        return 'No tienes productos guardados en favoritos.';
    }

    $list = $items->map(function ($item) {
        return '- ' . $item->name . ' Ã¢â‚¬â€ $' . number_format((float)($item->sale_price ?? $item->price ?? 0), 2) . ' MXN';
    })->implode("\n");

    return "Tus favoritos son:\n\n" . $list;
}

private function aiSearchFavorites($user, string $text): string
{
    $query = $this->aiCleanProductQuery($text);
    $items = $this->aiFavoriteItems($user);

    if ($items->isEmpty()) return 'No tienes productos guardados en favoritos.';
    if ($query === '') return $this->aiFavoritesSummary($user);

    $matches = $items->filter(function ($item) use ($query) {
        return str_contains(mb_strtolower((string)$item->name), $query)
            || str_contains(mb_strtolower((string)($item->sku ?? '')), $query);
    })->values();

    if ($matches->isEmpty()) return 'No encontrÃƒÂ© "' . $query . '" dentro de tus favoritos.';

    $list = $matches->map(function ($item) {
        return '- ' . $item->name . ' Ã¢â‚¬â€ $' . number_format((float)($item->sale_price ?? $item->price ?? 0), 2) . ' MXN';
    })->implode("\n");

    return "SÃƒÂ­, encontrÃƒÂ© esto en tus favoritos:\n\n" . $list;
}

private function aiAddFavoriteToCart($user, string $text, bool $move = false): string
{
    $query = $this->aiCleanProductQuery($text);
    $qty = $this->aiParseQuantity($text);
    $items = $this->aiFavoriteItems($user);

    if ($items->isEmpty()) return 'No tienes productos guardados en favoritos.';

    $matches = $items->filter(function ($item) use ($query) {
        return $query !== '' && (
            str_contains(mb_strtolower((string)$item->name), $query)
            || str_contains(mb_strtolower((string)($item->sku ?? '')), $query)
        );
    })->values();

    if ($matches->isEmpty()) return 'No encontrÃƒÂ© "' . $query . '" dentro de tus favoritos.';

    if ($matches->count() > 1) {
        $list = $matches->take(5)->map(function ($item, $i) {
            return ($i + 1) . '. ' . $item->name . ' Ã¢â‚¬â€ $' . number_format((float)($item->sale_price ?? $item->price ?? 0), 2) . ' MXN';
        })->implode("\n");

        return "EncontrÃƒÂ© varios favoritos parecidos. Ã‚Â¿CuÃƒÂ¡l quieres agregar?\n\n" . $list;
    }

    $item = $matches->first();
    $this->aiPutItemInCart($item, $qty);

    if ($move) {
        \DB::table('favorites')->where('user_id', $user->id)->where('catalog_item_id', $item->id)->delete();
        return 'Listo, movÃƒÂ­ "' . $item->name . '" de favoritos al carrito. ' . $this->aiCartSummary();
    }

    return 'Listo, agreguÃƒÂ© ' . $qty . ' pieza(s) de "' . $item->name . '" al carrito. El producto sigue en favoritos. ' . $this->aiCartSummary();
}

private function aiAddMultipleProductsToCart(string $text): ?string
{
    $normalized = mb_strtolower(' ' . trim($text) . ' ');
    $normalized = str_replace([' dde ', ' dd ', ' de de '], ' de ', $normalized);
    $normalized = preg_replace('/\s+/u', ' ', $normalized);

    preg_match_all('/(?:^|\s|,| y | e )(\d+)\s*(?:piezas?|pieza|pz|unidades?|unidad|de)?\s+(.+?)(?=(?:\s+(?:y|e)\s+\d+\s)|(?:,\s*\d+\s)|$)/u', $normalized, $matches, PREG_SET_ORDER);

    if (count($matches) < 2) {
        return null;
    }

    $added = [];

    foreach ($matches as $m) {
        $qty = max(1, min(9999, (int)$m[1]));
        $query = trim($m[2] ?? '');
        $query = $this->aiCleanProductQuery($query);
        $query = trim(str_replace([' y ', ' e '], ' ', ' ' . $query . ' '));

        if ($query === '') {
            continue;
        }

        $words = collect(preg_split('/\s+/u', mb_strtolower($query)))
            ->filter(fn($w) => mb_strlen($w) > 1)
            ->values();

        $catalogQuery = \App\Models\CatalogItem::query();

        foreach ($words as $word) {
            $catalogQuery->where(function ($q) use ($word) {
                $q->where('name', 'like', '%' . $word . '%')
                  ->orWhere('sku', 'like', '%' . $word . '%');
            });
        }

        $item = $catalogQuery->first();

        if (!$item) {
            return 'No encontrÃƒÂ© un producto que coincida con "' . $query . '".';
        }

        $this->aiPutItemInCart($item, $qty);
        $added[] = '- ' . $qty . ' pieza(s) de ' . $item->name;
    }

    if (empty($added)) {
        return null;
    }

    return "Listo, agreguÃƒÂ© al carrito:\n\n" . implode("\n", $added) . "\n\n" . $this->aiCartSummary();
}
private function aiAddProductToCart(string $text): string
{
    $query = $this->aiCleanProductQuery($text);
    $qty = $this->aiParseQuantity($text);

    if ($query === '') {
        return 'Dime quÃƒÂ© producto quieres agregar al carrito.';
    }

    $words = collect(preg_split('/\s+/u', mb_strtolower($query)))
        ->map(fn($w) => trim($w))
        ->filter(fn($w) => mb_strlen($w) > 1)
        ->values();

    $matches = \App\Models\CatalogItem::query()
        ->whereIn('status', ['published', 1, '1'])
        ->where(function ($q) use ($query, $words) {
            $q->where('name', 'like', '%' . $query . '%')
              ->orWhere('sku', 'like', '%' . $query . '%');

            if ($words->count() > 0) {
                $q->orWhere(function ($qq) use ($words) {
                    foreach ($words as $word) {
                        $qq->where('name', 'like', '%' . $word . '%');
                    }
                });
            }
        })
        ->orderByRaw("CASE WHEN name LIKE ? THEN 0 ELSE 1 END", ['%' . $query . '%'])
        ->limit(5)
        ->get();

    if ($matches->isEmpty()) {
        return 'No encontrÃƒÂ© un producto que coincida con "' . $query . '".';
    }

    if ($matches->count() > 1) {
        $names = $matches->take(5)->map(fn($p) => '- ' . $p->name)->implode("\n");
        return "EncontrÃƒÂ© varios productos parecidos. Ã‚Â¿CuÃƒÂ¡l quieres agregar?\n\n" . $names;
    }

    $item = $matches->first();
    $this->aiPutItemInCart($item, $qty);

    return 'Listo, agreguÃƒÂ© ' . $qty . ' pieza(s) de "' . $item->name . '" al carrito. ' . $this->aiCartSummary();
}


private function aiDecreaseProductsFromCart(string $text): string
{
    $cart = session('cart', []);

    if (empty($cart)) {
        return 'Tu carrito ya estÃƒÂ¡ vacÃƒÂ­o.';
    }

    $text = mb_strtolower(trim($text));
    $text = str_replace(['del carrito', 'de carrito', 'mi carrito', 'carrito'], ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);

    $ops = [];

    preg_match_all('/(\d+)\s*(?:piezas?|unidades?|paquetes?)?\s*(?:de\s+)?(.+?)(?=\s+(?:y|e)\s+\d+\s|$)/u', $text, $matches, PREG_SET_ORDER);

    foreach ($matches as $m) {
        $qty = max(1, (int)($m[1] ?? 1));
        $query = $this->aiCleanProductQuery($m[2] ?? '');

        $query = trim(str_replace([' y ', ' e '], ' ', ' ' . $query . ' '));

        if ($query !== '') {
            $ops[] = [
                'qty' => $qty,
                'query' => $query,
            ];
        }
    }

    if (empty($ops)) {
        $query = $this->aiCleanProductQuery($text);

        if ($query === '') {
            return 'Dime quÃƒÂ© producto quieres quitar del carrito.';
        }

        $ops[] = [
            'qty' => null,
            'query' => $query,
        ];
    }

    $messages = [];

    foreach ($ops as $op) {
        $foundKey = null;
        $foundRow = null;

        foreach ($cart as $key => $row) {
            $name = mb_strtolower((string)($row['name'] ?? ''));
            $query = mb_strtolower((string)$op['query']);

            if (str_contains($name, $query) || str_contains($query, $name)) {
                $foundKey = $key;
                $foundRow = $row;
                break;
            }

            foreach (explode(' ', $query) as $part) {
                $part = trim($part);
                if (mb_strlen($part) >= 3 && str_contains($name, $part)) {
                    $foundKey = $key;
                    $foundRow = $row;
                    break 2;
                }
            }
        }

        if ($foundKey === null) {
            $messages[] = 'No encontrÃƒÂ© "' . $op['query'] . '" en tu carrito.';
            continue;
        }

        $currentQty = max(1, (int)($foundRow['qty'] ?? 1));
        $removeQty = $op['qty'] ?? $currentQty;
        $newQty = $currentQty - $removeQty;

        if ($newQty <= 0) {
            unset($cart[$foundKey]);
            $messages[] = 'QuitÃƒÂ© "' . ($foundRow['name'] ?? 'Producto') . '" del carrito.';
        } else {
            $cart[$foundKey]['qty'] = $newQty;
            $messages[] = 'ActualicÃƒÂ© "' . ($foundRow['name'] ?? 'Producto') . '" de ' . $currentQty . ' a ' . $newQty . ' pieza(s).';
        }
    }

    session(['cart' => $cart]);

    return implode("\n", $messages) . "\n\n" . $this->aiCartSummary();
}

private function aiRemoveProductFromCart(string $text): string
{
    $query = $this->aiCleanProductQuery($text);
    $cart = session('cart', []);

    if (empty($cart)) return 'Tu carrito ya estÃƒÂ¡ vacÃƒÂ­o.';

    foreach ($cart as $key => $row) {
        if ($query !== '' && str_contains(mb_strtolower((string)($row['name'] ?? '')), $query)) {
            unset($cart[$key]);
            session(['cart' => $cart]);
            return 'Listo, quitÃƒÂ© "' . ($row['name'] ?? 'Producto') . '" del carrito. ' . $this->aiCartSummary();
        }
    }

    return 'No encontrÃƒÂ© "' . $query . '" en tu carrito.';
}

private function aiAddProductToFavorites($user, string $text): string
{
    $query = $this->aiCleanProductQuery($text);

    if ($query === '') return 'Dime quÃƒÂ© producto quieres guardar en favoritos.';

    $matches = \App\Models\CatalogItem::where('name', 'like', '%' . $query . '%')
        ->orWhere('sku', 'like', '%' . $query . '%')
        ->limit(5)
        ->get();

    if ($matches->isEmpty()) return 'No encontrÃƒÂ© un producto que coincida con "' . $query . '".';

    if ($matches->count() > 1) {
        $list = $matches->map(function ($item, $i) {
            return ($i + 1) . '. ' . $item->name . ' Ã¢â‚¬â€ $' . number_format((float)($item->sale_price ?? $item->price ?? 0), 2) . ' MXN';
        })->implode("\n");

        return "EncontrÃƒÂ© varios productos parecidos. Ã‚Â¿CuÃƒÂ¡l quieres guardar en favoritos?\n\n" . $list;
    }

    $item = $matches->first();

    \DB::table('favorites')->updateOrInsert(
        ['user_id' => $user->id, 'catalog_item_id' => $item->id],
        ['updated_at' => now(), 'created_at' => now()]
    );

    return 'Listo, guardÃƒÂ© "' . $item->name . '" en tus favoritos.';
}

private function aiRemoveProductFromFavorites($user, string $text): string
{
    $query = $this->aiCleanProductQuery($text);
    $items = $this->aiFavoriteItems($user);

    if ($items->isEmpty()) return 'No tienes productos guardados en favoritos.';

    $matches = $items->filter(function ($item) use ($query) {
        return $query !== '' && str_contains(mb_strtolower((string)$item->name), $query);
    })->values();

    if ($matches->isEmpty()) return 'No encontrÃƒÂ© "' . $query . '" dentro de tus favoritos.';

    if ($matches->count() > 1) {
        $list = $matches->take(5)->map(function ($item, $i) {
            return ($i + 1) . '. ' . $item->name;
        })->implode("\n");

        return "EncontrÃƒÂ© varios favoritos parecidos. Ã‚Â¿CuÃƒÂ¡l quieres quitar?\n\n" . $list;
    }

    $item = $matches->first();

    \DB::table('favorites')->where('user_id', $user->id)->where('catalog_item_id', $item->id)->delete();

    return 'Listo, quitÃƒÂ© "' . $item->name . '" de tus favoritos.';
}

private function aiPutItemInCart($item, int $qtyToAdd = 1): void
{
    $cart = session('cart', []);
    $id = $item->id;
    $price = round((float)($item->sale_price ?? $item->price ?? 0), 2);

    $image = $item->image_url ?? $item->photo_1 ?? $item->photo ?? null;

    if (isset($cart[$id])) {
        $cart[$id]['qty'] = ((int)($cart[$id]['qty'] ?? 0)) + $qtyToAdd;
    } else {
        $cart[$id] = [
            'id' => $item->id,
            'slug' => $item->slug ?? null,
            'name' => $item->name,
            'price' => $price,
            'qty' => $qtyToAdd,
            'image' => $image,
            'sku' => $item->sku ?? null,
        ];
    }

    session(['cart' => $cart]);
}

private function aiCartSummary(): string
{
    $cart = collect(session('cart', []));

    if ($cart->isEmpty()) return 'Tu carrito estÃƒÂ¡ vacÃƒÂ­o.';

    $count = $cart->sum(fn($row) => (int)($row['qty'] ?? 1));
    $subtotal = $cart->sum(fn($row) => ((float)($row['price'] ?? 0)) * ((int)($row['qty'] ?? 1)));

    $items = $cart->map(fn($row) => '- ' . ($row['name'] ?? 'Producto') . ' x' . ((int)($row['qty'] ?? 1)) . ' Ã¢â‚¬â€ $' . number_format(((float)($row['price'] ?? 0)) * ((int)($row['qty'] ?? 1)), 2) . ' MXN')->implode("\n");

    return "Tu carrito tiene {$count} producto(s):\n\n{$items}\n\nSubtotal aproximado: $" . number_format($subtotal, 2) . ' MXN.';
}

private function aiParseQuantity(string $text): int
{
    if (preg_match('/\b(\d+)\b/u', $text, $m)) {
        return max(1, min(9999, (int)$m[1]));
    }

    return 1;
}

private function aiCleanProductQuery(string $text): string
{
    $remove = [
        'puedes', 'podrias', 'podrÃƒÂ­as', 'por favor', 'favor',
        'agrega', 'agregar', 'aÃƒÂ±ade', 'anade', 'pon', 'mete',
        'guarda', 'guardar', 'quita', 'quitar', 'elimina', 'eliminar',
        'borra', 'borrar', 'mueve', 'mover', 'pasa', 'pasar',
        'a mi carrito', 'al carrito', 'en mi carrito', 'del carrito', 'carrito',
        'de mis favoritos', 'de mi favorito', 'de favoritos', 'mis favoritos',
        'a favoritos', 'en favoritos', 'favoritos', 'favorito',
        'guardados', 'preferidos',
        'mÃƒÂ¡s de', 'mas de', 'mÃƒÂ¡s', 'mas', 'otros', 'otras', 'otro', 'otra', 'sÃƒÂºmale', 'sumale', 'aumenta', 'incrementa', 'de',
        'mis', 'mi', 'producto', 'productos', 'paquetes', 'paquete', 'piezas', 'pieza',
        'tengo', 'hay', 'ver', 'muestra', 'enseÃƒÂ±a', 'cuales', 'cuÃƒÂ¡les',
        '?', 'Ã‚Â¿'
    ];

    usort($remove, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));

    foreach ($remove as $word) {
        $text = str_replace($word, ' ', $text);
    }

    $text = preg_replace('/\b\d+\b/u', ' ', $text);

    return trim(preg_replace('/\s+/', ' ', $text));
}

    private function recentTickets()
    {
        if (!Auth::check()) {
            return collect();
        }

        $tickets = HelpTicket::where('user_id', Auth::id())
            ->orderByDesc('last_activity_at')
            ->orderByDesc('created_at')
            ->limit(12)
            ->get(['id', 'subject', 'status', 'last_activity_at', 'created_at']);

        $firstMessages = HelpMessage::whereIn('ticket_id', $tickets->pluck('id'))
            ->where('sender_type', 'user')
            ->orderBy('created_at')
            ->get(['ticket_id', 'body'])
            ->groupBy('ticket_id')
            ->map(fn($items) => $items->first()?->body);

        return $tickets->map(function ($ticket) use ($firstMessages) {
            $ticket->chat_title = $firstMessages[$ticket->id] ?? $ticket->subject ?? 'Consulta de ayuda';
            return $ticket;
        });
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
        $customerContext = $this->customerContext();
        return $this->ai->helpdeskReply($prompt, $ticket, $history, $customerContext);
    }
}
