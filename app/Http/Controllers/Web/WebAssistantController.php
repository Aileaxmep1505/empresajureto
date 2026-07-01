<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\WebAssistantConversation;
use App\Models\WebAssistantMessage;
use App\Models\WebAssistantReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WebAssistantController extends Controller
{
    private int $inactiveMinutes = 120;

    public function conversations(Request $request)
    {
        $guestId = $this->guestId($request);
        $user = Auth::user();

        $conversations = $this->actorConversationsQuery($user?->id, $guestId)
            ->withCount('messages')
            ->orderByDesc('last_activity_at')
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get();

        $active = $conversations->first(fn ($conversation) => $this->isConversationActive($conversation));

        return response()->json([
            'ok' => true,
            'active_conversation_id' => $active?->id,
            'items' => $conversations->map(fn ($conversation) => $this->conversationPayload($conversation))->values(),
        ]);
    }

    public function show(Request $request, WebAssistantConversation $conversation)
    {
        $this->abortUnlessConversationBelongsToActor($request, $conversation);

        $conversation->load(['messages' => fn ($q) => $q->orderBy('id')]);

        return response()->json([
            'ok' => true,
            'conversation' => $this->conversationPayload($conversation),
            'messages' => $conversation->messages->map(fn ($message) => $this->messagePayload($message))->values(),
        ]);
    }

    public function createConversation(Request $request)
    {
        $conversation = $this->createNewConversation($request);

        return response()->json([
            'ok' => true,
            'conversation' => $this->conversationPayload($conversation),
            'messages' => [],
        ]);
    }

    public function destroy(Request $request, WebAssistantConversation $conversation)
    {
        $this->abortUnlessConversationBelongsToActor($request, $conversation);
        $conversation->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Conversación eliminada correctamente.',
        ]);
    }

    public function chat(Request $request)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:3000'],
            'conversation_id' => ['nullable', 'integer', 'exists:web_assistant_conversations,id'],
        ]);

        $message = trim((string) $data['message']);
        $conversation = $this->resolveConversationForMessage($request, $data['conversation_id'] ?? null, $message);

        $userMessage = WebAssistantMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $message,
            'meta' => [
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 300, ''),
            ],
        ]);

        if ($conversation->messages()->where('role', 'user')->count() === 1) {
            $conversation->title = $this->makeTitle($message);
        }

        $conversation->last_activity_at = now();
        $conversation->status = 'active';
        $conversation->save();

        $actionResult = $this->handleOperationalAction($request, $conversation, $message);

        if ($actionResult && array_key_exists('reply', $actionResult)) {
            $reply = $actionResult['reply'];
        } else {
            $reply = $this->replyWithAi($request, $conversation, $message);
        }

        $assistantMessage = null;

        if (is_string($reply) && trim($reply) !== '') {
            $assistantMessage = WebAssistantMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $reply,
                'meta' => [
                    'model' => env('OPENAI_ASSISTANT_MODEL', 'gpt-5.4-mini'),
                    'action' => $actionResult['action'] ?? null,
                ],
            ]);
        }

        $conversation->last_activity_at = now();
        $conversation->save();

        return response()->json([
            'ok' => true,
            'conversation' => $this->conversationPayload($conversation->fresh()->loadCount('messages')),
            'conversation_id' => $conversation->id,
            'user_message' => $this->messagePayload($userMessage),
            'assistant_message' => $assistantMessage ? $this->messagePayload($assistantMessage) : null,
            'reply' => $reply,
            'cart_count' => $this->cartCount(),
            'cart_url' => route('web.cart.index'),
            'action' => $actionResult['action'] ?? null,
            'handoff' => $actionResult['handoff'] ?? false,
            'support_status' => $conversation->support_status ?? null,
            'report' => $actionResult['report'] ?? null,
        ]);
    }

    private function handleOperationalAction(Request $request, WebAssistantConversation $conversation, string $message): ?array
    {
        $clean = $this->cleanText($message);

        if ($this->isAdvisorIntent($clean)) {
            return $this->requestAdvisorHandoff($request, $conversation, $message);
        }

        if ($this->conversationIsWithAdvisor($conversation)) {
            if (Schema::hasColumn('web_assistant_conversations', 'advisor_unread_count')) {
                $conversation->advisor_unread_count = (int) ($conversation->advisor_unread_count ?? 0) + 1;
            }

            if (Schema::hasColumn('web_assistant_conversations', 'last_customer_message_at')) {
                $conversation->last_customer_message_at = now();
            }

            if (Schema::hasColumn('web_assistant_conversations', 'last_activity_at')) {
                $conversation->last_activity_at = now();
            }

            $conversation->save();

            return [
                'action' => 'message_sent_to_advisor',
                'handoff' => true,
                'reply' => '<p>Tu mensaje fue enviado al asesor. En cuanto responda, lo verás aquí en esta misma conversación.</p>',
            ];
        }

        if ($this->isCartIntent($clean, $conversation)) {
            return $this->handleCartIntent($conversation, $message);
        }

        if ($this->isProductLinkIntent($clean)) {
            return $this->handleProductLinkIntent($conversation);
        }

        if ($this->isOrderTrackingIntent($clean)) {
            return $this->handleOrderTrackingIntent($request, $message);
        }

        if ($this->isOrderSummaryIntent($clean)) {
            return $this->handleOrderSummaryIntent($request, $message);
        }

        if ($this->isSupportReportIntent($clean)) {
            return $this->handleSupportReportIntent($request, $conversation, $message);
        }

        if ($this->isProductDiscoveryIntent($clean)) {
            return $this->handleProductDiscoveryIntent($conversation, $message);
        }

        $direct = $this->directReplyIfApplies($request, $message);

        if ($direct) {
            return [
                'action' => 'direct_reply',
                'reply' => $direct,
            ];
        }

        return null;
    }

    private function isAdvisorIntent(string $clean): bool
    {
        return preg_match('/\b(asesor|asesora|humano|persona|ejecutivo|agente|representante|atencion humana|atencion personalizada|hablar con alguien|hablar con una persona|quiero hablar|contactar asesor|soporte humano)\b/', $clean) === 1;
    }

    private function conversationIsWithAdvisor(WebAssistantConversation $conversation): bool
    {
        $status = (string) (
            $conversation->support_status
            ?? $conversation->handoff_status
            ?? 'bot'
        );

        return in_array($status, ['waiting', 'assigned', 'active'], true);
    }

    private function requestAdvisorHandoff(Request $request, WebAssistantConversation $conversation, string $message): array
    {
        $currentStatus = (string) (
            $conversation->support_status
            ?? $conversation->handoff_status
            ?? 'bot'
        );

        $alreadyRequested = in_array($currentStatus, ['waiting', 'assigned', 'active'], true);

        if (!$alreadyRequested) {
            if (Schema::hasColumn('web_assistant_conversations', 'support_status')) {
                $conversation->support_status = 'waiting';
            }

            if (Schema::hasColumn('web_assistant_conversations', 'handoff_status')) {
                $conversation->handoff_status = 'waiting';
            }

            if (Schema::hasColumn('web_assistant_conversations', 'support_requested_at')) {
                $conversation->support_requested_at = now();
            }

            if (Schema::hasColumn('web_assistant_conversations', 'handoff_requested_at')) {
                $conversation->handoff_requested_at = now();
            }

            if (Schema::hasColumn('web_assistant_conversations', 'advisor_unread_count')) {
                $conversation->advisor_unread_count = (int) ($conversation->advisor_unread_count ?? 0) + 1;
            }

            $metaColumn = Schema::hasColumn('web_assistant_conversations', 'meta') ? 'meta' : (Schema::hasColumn('web_assistant_conversations', 'metadata') ? 'metadata' : null);

            if ($metaColumn) {
                $conversation->{$metaColumn} = array_merge((array) ($conversation->{$metaColumn} ?? []), [
                    'handoff_reason' => Str::limit(strip_tags($message), 500, ''),
                    'handoff_source' => 'web_drawer',
                    'handoff_ip' => $request->ip(),
                ]);
            }

            if (Schema::hasColumn('web_assistant_conversations', 'last_customer_message_at')) {
                $conversation->last_customer_message_at = now();
            }

            if (Schema::hasColumn('web_assistant_conversations', 'last_activity_at')) {
                $conversation->last_activity_at = now();
            }

            $conversation->save();
        }

        $freshStatus = (string) (
            $conversation->support_status
            ?? $conversation->handoff_status
            ?? 'waiting'
        );

        $statusText = in_array($freshStatus, ['assigned', 'active'], true)
            ? 'Ya tienes un asesor asignado. Tu mensaje quedó en esta conversación para que pueda responderte.'
            : 'Listo, solicité apoyo de un asesor. Un asesor podrá responderte desde el panel interno en esta misma conversación.';

        return [
            'action' => 'advisor_requested',
            'handoff' => true,
            'reply' => '<p>' . e($statusText) . '</p><p>Mientras tanto, déjame tu <strong>folio de pedido</strong>, <strong>correo</strong>, <strong>guía</strong> o una breve descripción del problema para que el asesor tenga contexto.</p>',
        ];
    }

    private function isCartIntent(string $clean, WebAssistantConversation $conversation): bool
    {
        if (preg_match('/\b(agrega|agregar|agregalo|agregalos|anade|anadir|pon|mete|metelo|metelos|carrito|comprar|compralo|comprarlos)\b/', $clean)) {
            return true;
        }

        if (preg_match('/\b(si|sí|confirmo|correcto|adelante|ok|va)\b/', $clean)) {
            $lastAssistant = $this->lastAssistantText($conversation);
            $assistantClean = $this->cleanText($lastAssistant);

            return str_contains($assistantClean, 'agregar')
                || str_contains($assistantClean, 'carrito')
                || str_contains($assistantClean, 'sku');
        }

        return false;
    }

    private function isConfirmationText(string $clean): bool
    {
        return preg_match('/\b(si|sí|confirmo|correcto|adelante|ok|va|de acuerdo|agregalo|agregalos|agrega|añadelo|añadelos)\b/', $clean) === 1;
    }

    private function handleCartIntent(WebAssistantConversation $conversation, string $message): array
    {
        $source = trim($message . ' ' . $this->lastAssistantText($conversation));
        $qty = $this->extractQuantity($source);
        $product = $this->findProductForAction($message) ?: $this->findProductForAction($source);

        if (!$product && $this->isConfirmationText($this->cleanText($message))) {
            $product = $this->lastProductCandidate($conversation);
            $qty = $this->lastProductCandidateQty($conversation) ?: $qty;
        }

        if (!$product) {
            return [
                'action' => 'cart_missing_product',
                'reply' => '<p>Claro. Para agregarlo al carrito necesito el <strong>SKU</strong> o el <strong>nombre exacto del producto</strong> y la cantidad.</p><p>Ejemplo: <strong>agrega 3 piezas del SKU ABC123</strong>.</p>',
            ];
        }

        $stock = $product->stock;

        if ($stock !== null && (int) $stock <= 0) {
            return [
                'action' => 'cart_out_of_stock',
                'reply' => '<p>El producto <strong>' . e($product->name) . '</strong> aparece sin stock disponible. Puedo ayudarte a buscar una alternativa similar o levantar una solicitud de cotización.</p>',
            ];
        }

        if ($stock !== null && $qty > (int) $stock) {
            $qty = max(1, (int) $stock);
        }

        $this->addProductToCart($product, $qty);

        $price = $this->productPrice($product);
        $subtotal = $price * $qty;

        return [
            'action' => 'cart_added',
            'reply' => '<p>Listo, agregué al carrito:</p><ul><li><strong>' . e($product->name) . '</strong></li><li>Cantidad: <strong>' . $qty . '</strong></li><li>SKU: <strong>' . e($product->sku ?: 'Sin SKU') . '</strong></li><li>Subtotal estimado: <strong>$' . number_format($subtotal, 2) . '</strong></li></ul><p>Puedes revisar tu carrito o seguir agregando más productos.</p>',
        ];
    }

    private function isProductLinkIntent(string $clean): bool
    {
        return preg_match('/\b(enlace|link|liga|url|ver producto|abrelo|abrir producto|dame el enlace|mandame el enlace|pasame el enlace)\b/', $clean) === 1;
    }

    private function handleProductLinkIntent(WebAssistantConversation $conversation): ?array
    {
        $product = $this->lastProductCandidate($conversation);

        if (!$product) {
            $product = $this->findProductForAction($this->lastAssistantText($conversation));
        }

        if (!$product) {
            return [
                'action' => 'product_link_missing',
                'reply' => '<p>No tengo identificado el producto exacto para darte el enlace. Escríbeme el <strong>nombre</strong> o el <strong>SKU</strong> y te lo paso.</p>',
            ];
        }

        $url = route('web.catalog.show', $product);

        return [
            'action' => 'product_link',
            'reply' => '<p>Claro, aquí tienes el enlace del producto:</p><p><a href="' . e($url) . '">Ver ' . e($product->name) . '</a></p>',
        ];
    }

    private function isProductDiscoveryIntent(string $clean): bool
    {
        if (preg_match('/\b(busco|buscar|tienes|tendras|hay|existe|vendes|manejas|precio|stock|disponible|disponibles|cotizar|cotizacion|producto|productos)\b/', $clean)) {
            return true;
        }

        return preg_match('/\b(marcatextos|lapices|lapiz|plumas|pluma|hojas|folder|folders|carpetas|toner|cartucho|silla|escritorio|papeleria|oficina)\b/', $clean) === 1;
    }

    private function handleProductDiscoveryIntent(WebAssistantConversation $conversation, string $message): ?array
    {
        $products = $this->searchProducts($message);

        if (empty($products)) {
            return null;
        }

        $first = $products[0] ?? null;

        if ($first && !empty($first['id'])) {
            $this->rememberProductCandidate($conversation, (int) $first['id'], $this->extractQuantity($message));
        }

        $items = collect($products)->take(4)->map(function ($product) {
            $price = isset($product['precio']) ? '$' . number_format((float) $product['precio'], 2) : 'Precio por confirmar';
            $stock = array_key_exists('stock', $product) && $product['stock'] !== null ? (int) $product['stock'] . ' pzas' : 'Stock por confirmar';
            $url = $product['url'] ?? null;
            $name = $product['nombre'] ?? 'Producto';

            $link = $url
                ? '<br><a href="' . e($url) . '">Ver ' . e($name) . '</a>'
                : '';

            return '<li><strong>' . e($name) . '</strong><br>SKU: <strong>' . e($product['sku'] ?? 'Sin SKU') . '</strong><br>Precio: <strong>' . e($price) . '</strong> · Stock: <strong>' . e($stock) . '</strong>' . $link . '</li>';
        })->implode('');

        return [
            'action' => 'product_discovery',
            'reply' => '<p>Encontré estas opciones relacionadas:</p><ul>' . $items . '</ul><p>Si quieres agregar la primera opción al carrito, dime <strong>sí, agrégalo</strong> o indícame cantidad y SKU.</p>',
        ];
    }

    private function rememberProductCandidate(WebAssistantConversation $conversation, int $productId, int $qty = 1): void
    {
        $metaColumn = Schema::hasColumn('web_assistant_conversations', 'meta') ? 'meta' : (Schema::hasColumn('web_assistant_conversations', 'metadata') ? 'metadata' : null);

        if (!$metaColumn) {
            return;
        }

        $meta = (array) ($conversation->{$metaColumn} ?? []);
        $meta['last_product_candidate_id'] = $productId;
        $meta['last_product_candidate_qty'] = max(1, $qty);
        $meta['last_product_candidate_at'] = now()->toIso8601String();

        $conversation->{$metaColumn} = $meta;
        $conversation->save();
    }

    private function lastProductCandidate(WebAssistantConversation $conversation): ?CatalogItem
    {
        $meta = (array) ($conversation->meta ?? $conversation->metadata ?? []);
        $id = $meta['last_product_candidate_id'] ?? null;

        if (!$id || !class_exists(CatalogItem::class)) {
            return null;
        }

        try {
            return CatalogItem::published()->whereKey($id)->first();
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    private function lastProductCandidateQty(WebAssistantConversation $conversation): int
    {
        $meta = (array) ($conversation->meta ?? $conversation->metadata ?? []);

        return max(1, (int) ($meta['last_product_candidate_qty'] ?? 1));
    }


    private function isOrderTrackingIntent(string $clean): bool
    {
        if (!preg_match('/\b(pedido|orden|compra|folio)\b/', $clean)) {
            return false;
        }

        if ($this->extractExplicitOrderNumber($clean)) {
            return true;
        }

        return preg_match('/\b(cuando llega|cuando va a llegar|llegar|llega|entrega|entregado|rastreo|rastrear|seguimiento|guia|guía|paqueteria|paquetería|envio|envío|donde esta|dónde está)\b/', $clean) === 1;
    }

    private function handleOrderTrackingIntent(Request $request, string $message): array
    {
        $explicitOrder = $this->extractExplicitOrderNumber($message);

        if (!$explicitOrder) {
            return [
                'action' => 'order_tracking_needs_folio',
                'reply' => '<p>Claro, puedo revisar el seguimiento de tu pedido. Escríbeme el <strong>número de pedido</strong>, por ejemplo: <strong>pedido 1</strong> o <strong>¿cuándo llega mi pedido 1?</strong>.</p>',
            ];
        }

        $order = $this->findSpecificOrderForActor($request, $explicitOrder);

        if (!$order) {
            return [
                'action' => 'order_tracking_not_found',
                'reply' => '<p>No encontré el <strong>pedido #' . e((string) $explicitOrder) . '</strong> asociado a tu cuenta.</p><p>Revisa el número de pedido o dime el correo usado en la compra.</p>',
            ];
        }

        $status = $order['estatus'] ?: 'Sin estatus';
        $folio = $order['folio'] ?: ('Pedido #' . $order['id']);
        $total = is_numeric($order['total']) ? '$' . number_format((float) $order['total'], 2) . ' MXN' : 'No disponible';
        $fecha = $order['fecha'] ? Carbon::parse($order['fecha'])->format('d/m/Y H:i') : 'No disponible';
        $carrier = $order['paqueteria'] ?: 'Pendiente';
        $service = $order['servicio'] ?: 'Pendiente';
        $guia = $order['guia'] ?: null;
        $trackingUrl = $order['tracking_url'] ?? null;
        $labelUrl = $order['label_url'] ?? null;
        $shippingStatus = $order['shipping_status'] ?: null;

        $html = '<p>Revisé tu <strong>' . e($folio) . '</strong>:</p>';
        $html .= '<ul>';
        $html .= '<li>Estatus: <strong>' . e($this->humanOrderStatus($status)) . '</strong></li>';
        $html .= '<li>Total: <strong>' . e($total) . '</strong></li>';
        $html .= '<li>Fecha: <strong>' . e($fecha) . '</strong></li>';
        $html .= '<li>Paquetería: <strong>' . e($carrier) . '</strong></li>';
        $html .= '<li>Servicio: <strong>' . e($service) . '</strong></li>';

        if ($shippingStatus) {
            $html .= '<li>Estado de envío: <strong>' . e($this->humanOrderStatus($shippingStatus)) . '</strong></li>';
        }

        if ($guia) {
            $html .= '<li>Guía: <strong>' . e($guia) . '</strong></li>';
        } else {
            $html .= '<li>Guía: <strong>Pendiente</strong></li>';
        }

        if (!empty($order['items'])) {
            $html .= '<li>Productos: <strong>' . e(collect($order['items'])->take(4)->map(function ($item) {
                return ($item['cantidad'] ?? 1) . ' x ' . ($item['nombre'] ?? 'Producto');
            })->implode(', ')) . '</strong></li>';
        }

        $html .= '</ul>';

        if ($guia && $trackingUrl) {
            $html .= '<p>Ya tiene guía generada. Puedes rastrearlo aquí:<br><a href="' . e($trackingUrl) . '" target="_blank" rel="noopener">Ver seguimiento</a></p>';
        } elseif ($guia) {
            $html .= '<p>Ya tiene guía generada. La paquetería actualizará el rastreo cuando reciba el paquete.</p>';
        } else {
            $html .= '<p>Tu pedido está registrado, pero todavía no tiene guía generada. Si ya pasó mucho tiempo, puedo ayudarte a levantar un reporte de seguimiento.</p>';
        }

        if ($labelUrl) {
            $html .= '<p><a href="' . e($labelUrl) . '" target="_blank" rel="noopener">Ver etiqueta</a></p>';
        }

        return [
            'action' => 'order_tracking',
            'reply' => $html,
        ];
    }

    private function extractExplicitOrderNumber(string $message): ?string
    {
        $text = $this->cleanText($message);

        if (preg_match('/\b(?:pedido|orden|compra|folio)\s*#?\s*([a-z0-9\-]{1,40})\b/i', $text, $match)) {
            return strtoupper(trim($match[1]));
        }

        if (preg_match('/#\s*([0-9]{1,20})\b/', $message, $match)) {
            return trim($match[1]);
        }

        return null;
    }

    private function findSpecificOrderForActor(Request $request, string $folio): ?array
    {
        $user = Auth::user();
        $possibleEmail = $this->extractPossibleEmail($request->input('message', '')) ?: $user?->email;
        $possibleTables = ['orders', 'web_orders', 'customer_orders', 'shop_orders', 'customer_sales', 'sales'];

        foreach ($possibleTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $query = DB::table($table);
            $hasFolioColumn = false;

            $query->where(function ($q) use ($table, $folio, &$hasFolioColumn) {
                foreach (['id', 'folio', 'order_number', 'number', 'reference', 'referencia', 'uuid', 'paypal_order_id', 'stripe_session_id'] as $field) {
                    if (Schema::hasColumn($table, $field)) {
                        $q->orWhere($field, $folio);
                        $hasFolioColumn = true;
                    }
                }
            });

            if (!$hasFolioColumn) {
                continue;
            }

            $order = $query->first();

            if (!$order) {
                continue;
            }

            if (!$this->orderBelongsToActor($table, $order, $user, $possibleEmail)) {
                continue;
            }

            return $this->orderPayload($table, $order);
        }

        return null;
    }

    private function orderBelongsToActor(string $table, object $order, $user, ?string $email): bool
    {
        if (!$user && !$email) {
            return false;
        }

        if ($user) {
            foreach (['user_id', 'customer_id', 'client_id', 'cliente_id', 'buyer_id', 'created_by', 'account_id'] as $field) {
                if (Schema::hasColumn($table, $field) && isset($order->{$field}) && (int) $order->{$field} === (int) $user->id) {
                    return true;
                }
            }
        }

        if ($email) {
            foreach (['email', 'customer_email', 'billing_email', 'shipping_email', 'client_email', 'correo', 'correo_cliente'] as $field) {
                if (Schema::hasColumn($table, $field) && isset($order->{$field}) && strtolower((string) $order->{$field}) === strtolower((string) $email)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function humanOrderStatus(?string $status): string
    {
        $key = $this->cleanText((string) $status);

        return match ($key) {
            'paid', 'pagado' => 'pagado',
            'pending', 'pendiente' => 'pendiente',
            'processing', 'procesando' => 'en proceso',
            'completed', 'completado' => 'completado',
            'cancelled', 'canceled', 'cancelado' => 'cancelado',
            'creado', 'created' => 'guía creada',
            'transit', 'en transito', 'en tránsito' => 'en tránsito',
            'delivered', 'entregado' => 'entregado',
            default => $status ?: 'procesando',
        };
    }

    private function isOrderSummaryIntent(string $clean): bool
    {
        return preg_match('/\b(mis compras|que compre|que he comprado|compras he realizado|mis pedidos|mis ordenes|lo que compre|cancelado|cancelados|canceladas|entregado|pendiente|pagado|pedido|pedidos)\b/', $clean) === 1
            && !preg_match('/\b(reporte|problema|atrasado|no llega|no ha llegado|guia|paquete|envio)\b/', $clean);
    }

    private function handleOrderSummaryIntent(Request $request, string $message): array
    {
        $orders = $this->findOrdersForActor($request, $message, 10);

        if (empty($orders)) {
            return [
                'action' => 'orders_not_found',
                'reply' => Auth::check()
                    ? '<p>No encontré compras asociadas a tu usuario actual.</p><p>Estoy revisando con tu sesión iniciada, pero no encontré pedidos vinculados a tu <strong>usuario, correo o cliente</strong>. Si tu compra se hizo con otro correo, compárteme el <strong>folio de pedido</strong> o el <strong>correo usado en la compra</strong>.</p>'
                    : '<p>No encontré compras con los datos disponibles.</p><p>Para revisar tus pedidos necesito que inicies sesión o me compartas tu <strong>folio de pedido</strong> o el <strong>correo usado en la compra</strong>.</p>',
            ];
        }

        $html = '<p>Encontré esta información de tus compras:</p><ul>';

        foreach ($orders as $order) {
            $status = $order['estatus'] ?: 'Sin estatus';
            $folio = $order['folio'] ?: ('Pedido #' . $order['id']);
            $total = is_numeric($order['total']) ? '$' . number_format((float) $order['total'], 2) : 'No disponible';
            $html .= '<li><strong>' . e($folio) . '</strong><br>Estatus: <strong>' . e($status) . '</strong><br>Total: <strong>' . e($total) . '</strong>';

            if (!empty($order['fecha'])) {
                $html .= '<br>Fecha: ' . e((string) $order['fecha']);
            }

            if (!empty($order['guia'])) {
                $html .= '<br>Guía: <strong>' . e($order['guia']) . '</strong>';
            }

            if (!empty($order['items'])) {
                $html .= '<br>Productos: ' . e(collect($order['items'])->take(4)->map(function ($item) {
                    return ($item['cantidad'] ?? 1) . ' x ' . ($item['nombre'] ?? 'Producto');
                })->implode(', '));
            }

            $html .= '</li>';
        }

        $html .= '</ul><p>Si algún pedido tiene problema de envío, dime el <strong>folio</strong> y te genero un reporte de seguimiento.</p>';

        return [
            'action' => 'orders_summary',
            'reply' => $html,
        ];
    }

    private function isSupportReportIntent(string $clean): bool
    {
        return preg_match('/\b(reporte|levanta reporte|generar reporte|folio de reporte|problema|paquete|envio|envio atrasado|atrasado|no llega|no ha llegado|guia|guía|cancelacion|cancelar|devolucion|garantia|garantía|reclamo|incidencia)\b/', $clean) === 1;
    }

    private function handleSupportReportIntent(Request $request, WebAssistantConversation $conversation, string $message): array
    {
        $orders = $this->findOrdersForActor($request, $message, 3);
        $order = $orders[0] ?? null;
        $type = $this->detectReportType($message);
        $email = $this->extractPossibleEmail($message) ?: Auth::user()?->email;

        if (!$order && !$this->extractPossibleFolio($message) && !$email && !Auth::check()) {
            return [
                'action' => 'report_missing_data',
                'reply' => '<p>Te ayudo a levantar el reporte. Para generarlo necesito uno de estos datos:</p><ul><li><strong>Folio del pedido</strong></li><li><strong>Correo usado en la compra</strong></li><li><strong>Número de guía</strong></li></ul><p>También dime brevemente qué pasó: envío atrasado, paquete no recibido, cancelación, devolución o garantía.</p>',
            ];
        }

        $folio = $this->generateReportFolio();
        $reportPayload = [
            'conversation_id' => $conversation->id,
            'user_id' => Auth::id(),
            'guest_id' => Auth::check() ? null : $this->guestId($request),
            'folio' => $folio,
            'type' => $type,
            'status' => 'open',
            'order_table' => $order['table'] ?? null,
            'order_id' => $order['id'] ?? null,
            'order_folio' => $order['folio'] ?? $this->extractPossibleFolio($message),
            'customer_email' => $email,
            'summary' => Str::limit(strip_tags($message), 1000, ''),
            'meta' => [
                'source' => 'web_assistant',
                'order' => $order,
                'message' => $message,
            ],
        ];

        if (class_exists(WebAssistantReport::class) && Schema::hasTable('web_assistant_reports')) {
            $report = WebAssistantReport::create($reportPayload);
            $folio = $report->folio;
        }

        $orderText = $order
            ? '<li>Pedido relacionado: <strong>' . e($order['folio'] ?: ('#' . $order['id'])) . '</strong></li>'
            : '<li>Pedido relacionado: <strong>pendiente de confirmar</strong></li>';

        return [
            'action' => 'report_created',
            'report' => ['folio' => $folio, 'type' => $type],
            'reply' => '<p>Ya generé tu folio de reporte:</p><ul><li>Folio: <strong>' . e($folio) . '</strong></li><li>Tipo: <strong>' . e($this->reportTypeLabel($type)) . '</strong></li>' . $orderText . '<li>Estatus: <strong>Abierto</strong></li></ul><p>El siguiente paso es validar el pedido, guía o evidencia del caso. Si tienes captura, guía o comprobante, compártelo con el área de atención para acelerar el seguimiento.</p>',
        ];
    }

    private function replyWithAi(Request $request, WebAssistantConversation $conversation, string $message): string
    {
        $apiKey = config('services.openai.key') ?: env('OPENAI_API_KEY');
        $baseUrl = rtrim(env('OPENAI_BASE_URL', 'https://api.openai.com'), '/');
        $model = env('OPENAI_ASSISTANT_MODEL', 'gpt-5.4-mini');

        if (!$apiKey) {
            return 'Todavía no está configurada la inteligencia artificial. Agrega <strong>OPENAI_API_KEY</strong> en tu archivo .env.';
        }

        $context = $this->buildJuretoContext($request, $message);
        $recentMessages = $this->recentConversationMessages($conversation);

        try {
            $reply = $this->callResponsesApi($apiKey, $baseUrl, $model, $context, $recentMessages, $message);

            if (!$reply) {
                $reply = $this->callChatCompletionsApi($apiKey, $baseUrl, $model, $context, $recentMessages, $message);
            }

            if (!$reply) {
                return 'No pude obtener una respuesta válida del asistente. Revisa que el modelo configurado tenga acceso con tu API key.';
            }

            return $this->cleanAssistantReply($reply);
        } catch (\Throwable $e) {
            report($e);
            return 'Ocurrió un problema al procesar tu mensaje. Revisa los logs de Laravel y vuelve a intentar.';
        }
    }

    private function callResponsesApi(string $apiKey, string $baseUrl, string $model, array $context, array $recentMessages, string $currentMessage): ?string
    {
        $history = $this->historyAsPlainText($recentMessages);

        $response = Http::withToken($apiKey)
            ->timeout(90)
            ->acceptJson()
            ->asJson()
            ->post($baseUrl . '/v1/responses', [
                'model' => $model,
                'instructions' => $this->systemPrompt() . "\n\nCONTEXTO DISPONIBLE DE JURETO:\n" . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'input' => "Historial reciente solo como referencia, puede estar desordenado o incompleto:\n" . $history . "\n\nMENSAJE ACTUAL DEL USUARIO, este es el unico mensaje que debes contestar ahora:\n" . $currentMessage . "\n\nNo reinicies la conversacion. No contestes mensajes viejos. Responde exclusivamente al mensaje actual. Si el usuario pide acciones reales como agregar al carrito o generar reporte, confirma que ya se hizo solamente si el contexto indica una accion_ejecutada.",
                'max_output_tokens' => 900,
            ]);

        if (!$response->successful()) {
            report(new \RuntimeException('OpenAI Responses API error: ' . $response->status() . ' ' . $response->body()));
            return null;
        }

        $json = $response->json();
        return data_get($json, 'output_text') ?: $this->extractResponsesText($json);
    }

    private function callChatCompletionsApi(string $apiKey, string $baseUrl, string $model, array $context, array $recentMessages, string $currentMessage): ?string
    {
        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ['role' => 'system', 'content' => "CONTEXTO DISPONIBLE DE JURETO:\n" . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)],
            ['role' => 'system', 'content' => "Historial solo como referencia, no lo repitas:\n" . $this->historyAsPlainText($recentMessages)],
            ['role' => 'user', 'content' => 'MENSAJE ACTUAL DEL USUARIO. Responde solamente esto, no repitas historial anterior: ' . $currentMessage],
        ];

        $response = Http::withToken($apiKey)
            ->timeout(90)
            ->acceptJson()
            ->asJson()
            ->post($baseUrl . '/v1/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => 900,
            ]);

        if (!$response->successful()) {
            report(new \RuntimeException('OpenAI Chat Completions API error: ' . $response->status() . ' ' . $response->body()));
            return null;
        }

        return data_get($response->json(), 'choices.0.message.content');
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
Eres el asistente comercial y de soporte de Jureto, una tienda y comercializadora de papelería, cómputo, oficina, tecnología, muebles, servicios y productos para empresas, gobierno y clientes finales.

Prioridad principal:
- Responde siempre el mensaje más reciente del usuario.
- No repitas una respuesta anterior si la pregunta nueva cambia de tema.
- No digas que agregaste productos al carrito, cancelaste algo o generaste reportes si no aparece en el contexto como accion_ejecutada.
- Si el usuario pide agregar al carrito, revisar compras, cancelar, reportar envío atrasado o generar folio, explícale el siguiente paso solo con base en el contexto disponible.

Tu trabajo en Jureto:
1. Ayudar a encontrar productos.
2. Revisar compras realizadas, pedidos cancelados, pendientes, pagados o entregados si hay datos disponibles.
3. Apoyar con problemas de envío, paquetes atrasados, guías, garantías, devoluciones y cancelaciones.
4. Orientar como asesor de ventas y soporte.
5. Pedir folio, correo, guía o evidencia cuando falte información.

Reglas de formato:
- Responde en español mexicano.
- No uses Markdown ni asteriscos.
- Usa HTML limpio: <strong>, <ul>, <ol>, <li>, <br>, <p>.
- No uses scripts, estilos inline ni clases.
- Mantén respuestas cortas, útiles y accionables.
PROMPT;
    }

    private function directReplyIfApplies(Request $request, string $message): ?string
    {
        $clean = $this->cleanText($message);
        $timezone = config('app.timezone', 'America/Mexico_City');
        $today = now()->timezone($timezone);

        if (preg_match('/\b(que dia es hoy|a que dia estamos|fecha de hoy|hoy que dia es)\b/', $clean)) {
            return '<p>Hoy es <strong>' . e($today->isoFormat('dddd D [de] MMMM [de] YYYY')) . '</strong>.</p>';
        }

        if (preg_match('/\b(que hora es|hora actual|a que hora estamos)\b/', $clean)) {
            return '<p>La hora actual es <strong>' . e($today->format('h:i a')) . '</strong>.</p>';
        }

        if (preg_match('/\b(hola|buenos dias|buenas tardes|buenas noches)\b/', $clean)) {
            return '<p>Hola, ¿en qué te ayudo hoy? Puedo apoyarte con <strong>productos</strong>, <strong>pedidos</strong>, <strong>envíos atrasados</strong>, <strong>cancelaciones</strong>, <strong>garantías</strong> o una <strong>cotización</strong>.</p>';
        }

        if (preg_match('/\b(ayuda|que puedes hacer|como me ayudas)\b/', $clean)) {
            return '<p>Puedo ayudarte con:</p><ul><li><strong>Productos:</strong> buscar y agregar al carrito.</li><li><strong>Pedidos:</strong> revisar compras, cancelaciones y estatus.</li><li><strong>Soporte:</strong> generar folios por envío atrasado, paquete no recibido, garantía o devolución.</li><li><strong>Cotizaciones:</strong> preparar datos para compra empresarial.</li></ul>';
        }

        return null;
    }

    private function buildJuretoContext(Request $request, string $message): array
    {
        $user = Auth::user();
        $timezone = config('app.timezone', 'America/Mexico_City');

        return [
            'fecha_actual' => now()->timezone($timezone)->isoFormat('dddd D [de] MMMM [de] YYYY, h:mm a'),
            'timezone' => $timezone,
            'usuario' => $user ? [
                'id' => $user->id,
                'nombre' => $user->name ?? null,
                'email' => $user->email ?? null,
                'sesion_iniciada' => true,
            ] : ['sesion_iniciada' => false],
            'carrito' => $this->getCartContext(),
            'pedidos_usuario' => [
                'disponible' => true,
                'pedidos' => $this->findOrdersForActor($request, $message, 8),
            ],
            'productos_relacionados' => $this->searchProducts($message),
            'reportes_recientes' => $this->recentReports($request),
            'politicas_y_links' => [
                ['tema' => 'Envíos, devoluciones y cancelaciones', 'url' => url('/envios-devoluciones-cancelaciones')],
                ['tema' => 'Formas de pago', 'url' => url('/formas-de-pago')],
                ['tema' => 'Formas de envío', 'url' => url('/formas-de-envio')],
                ['tema' => 'Garantías y devoluciones', 'url' => url('/garantias-y-devoluciones')],
                ['tema' => 'Contacto', 'url' => url('/contacto')],
            ],
        ];
    }

    private function addProductToCart(CatalogItem $product, int $qty): void
    {
        $cart = session('cart', []);
        $key = (string) $product->id;
        $price = $this->productPrice($product);

        if (isset($cart[$key]) && is_array($cart[$key])) {
            $cart[$key]['qty'] = max(1, (int) ($cart[$key]['qty'] ?? 0)) + $qty;
            $cart[$key]['price'] = $price;
            $cart[$key]['id'] = $product->id;
            $cart[$key]['catalog_item_id'] = $product->id;
            $cart[$key]['slug'] = $product->slug ?? ($cart[$key]['slug'] ?? null);
        } else {
            $cart[$key] = [
                'id' => $product->id,
                'catalog_item_id' => $product->id,
                'slug' => $product->slug ?? null,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $price,
                'qty' => $qty,
                'image' => $product->image_url ?? null,
            ];
        }

        session()->put('cart', $cart);
        session()->save();
    }

    private function productPrice(CatalogItem $product): float
    {
        $sale = (float) ($product->sale_price ?? 0);
        return $sale > 0 ? $sale : (float) ($product->price ?? 0);
    }

    private function cartCount(): int
    {
        return collect((array) session('cart', []))->sum(fn ($row) => (int) ($row['qty'] ?? 0));
    }

    private function getCartContext(): array
    {
        $cart = session('cart', []);
        $items = [];
        $total = 0;

        foreach ((array) $cart as $row) {
            $qty = (int) ($row['qty'] ?? 0);
            $price = (float) ($row['price'] ?? 0);
            $items[] = [
                'nombre' => $row['name'] ?? 'Producto',
                'sku' => $row['sku'] ?? null,
                'cantidad' => $qty,
                'precio' => $price,
                'subtotal' => $qty * $price,
            ];
            $total += $qty * $price;
        }

        return [
            'tiene_productos' => count($items) > 0,
            'productos' => $items,
            'total_estimado' => $total,
        ];
    }

    private function findProductForAction(string $text): ?CatalogItem
    {
        if (!class_exists(CatalogItem::class)) {
            return null;
        }

        $sku = $this->extractSku($text);

        try {
            if ($sku) {
                $product = CatalogItem::published()
                    ->where(function ($q) use ($sku) {
                        $q->where('sku', $sku)->orWhere('sku', 'like', '%' . $sku . '%');
                    })
                    ->first();

                if ($product) {
                    return $product;
                }
            }

            $terms = $this->actionSearchTerms($text);

            if ($terms->isEmpty()) {
                return null;
            }

            return CatalogItem::published()
                ->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->orWhere('name', 'like', '%' . $term . '%')
                            ->orWhere('sku', 'like', '%' . $term . '%')
                            ->orWhere('excerpt', 'like', '%' . $term . '%');
                    }
                })
                ->orderByDesc('stock')
                ->first();
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    private function searchProducts(string $message): array
    {
        if (!class_exists(CatalogItem::class)) {
            return [];
        }

        $terms = $this->actionSearchTerms($message)->take(6);

        if ($terms->isEmpty()) {
            return [];
        }

        try {
            return CatalogItem::published()
                ->with(['category', 'categoryProduct'])
                ->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->orWhere('name', 'like', "%{$term}%")
                            ->orWhere('sku', 'like', "%{$term}%")
                            ->orWhere('excerpt', 'like', "%{$term}%");
                    }
                })
                ->limit(8)
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'nombre' => $product->name,
                        'sku' => $product->sku,
                        'precio' => $this->productPrice($product),
                        'stock' => $product->stock ?? null,
                        'categoria' => $product->categoryProduct?->full_path
                            ?? $product->categoryProduct?->name
                            ?? $product->category?->name
                            ?? null,
                        'url' => route('web.catalog.show', $product),
                    ];
                })
                ->values()
                ->toArray();
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    private function findOrdersForActor(Request $request, string $message, int $limit = 10): array
    {
        $user = Auth::user();
        $possibleFolio = $this->extractPossibleFolio($message);
        $possibleEmail = $this->extractPossibleEmail($message);
        $emails = collect([$possibleEmail, $user?->email])->filter()->unique()->values();
        $possibleTables = ['orders', 'web_orders', 'customer_orders', 'shop_orders', 'customer_sales', 'sales'];
        $results = [];

        foreach ($possibleTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $query = DB::table($table);
            $hasFilter = false;

            $query->where(function ($q) use ($table, $user, $emails, $possibleFolio, &$hasFilter) {
                if ($user) {
                    foreach (['user_id', 'customer_id', 'client_id', 'cliente_id', 'buyer_id', 'created_by', 'account_id'] as $field) {
                        if (Schema::hasColumn($table, $field)) {
                            $q->orWhere($field, $user->id);
                            $hasFilter = true;
                        }
                    }

                    foreach (['email', 'customer_email', 'billing_email', 'shipping_email', 'client_email', 'correo', 'correo_cliente'] as $field) {
                        if ($emails->isNotEmpty() && Schema::hasColumn($table, $field)) {
                            foreach ($emails as $email) {
                                $q->orWhere($field, $email);
                            }
                            $hasFilter = true;
                        }
                    }

                    foreach (['customer_name', 'client_name', 'name', 'nombre_cliente'] as $field) {
                        if ($user->name && Schema::hasColumn($table, $field)) {
                            $q->orWhere($field, 'like', '%' . $user->name . '%');
                            $hasFilter = true;
                        }
                    }
                }

                if ($possibleFolio) {
                    foreach (['folio', 'order_number', 'number', 'id', 'reference', 'referencia', 'uuid'] as $field) {
                        if (Schema::hasColumn($table, $field)) {
                            $q->orWhere($field, $possibleFolio);
                            $hasFilter = true;
                        }
                    }
                }
            });

            if (!$hasFilter) {
                continue;
            }

            if (Schema::hasColumn($table, 'created_at')) {
                $query->orderByDesc('created_at');
            } elseif (Schema::hasColumn($table, 'id')) {
                $query->orderByDesc('id');
            }

            foreach ($query->limit($limit)->get() as $order) {
                $results[] = $this->orderPayload($table, $order);
            }

            if (count($results) >= $limit) {
                break;
            }
        }

        return array_slice($results, 0, $limit);
    }

    private function orderPayload(string $table, object $order): array
    {
        $id = $order->id ?? null;
        $folio = $order->folio ?? $order->order_number ?? $order->number ?? null;

        return [
            'table' => $table,
            'id' => $id,
            'folio' => $folio,
            'estatus' => $order->status ?? $order->estado ?? $order->payment_status ?? null,
            'total' => $order->total ?? $order->grand_total ?? $order->amount ?? null,
            'fecha' => $order->created_at ?? null,
            'guia' => $order->tracking_number ?? $order->shipping_tracking_number ?? $order->guide_number ?? $order->tracking ?? $order->guia ?? null,
            'tracking_url' => $order->tracking_url ?? $order->shipping_tracking_url ?? null,
            'label_url' => $order->label_url ?? $order->shipping_label_url ?? null,
            'shipping_status' => $order->shipping_status ?? null,
            'paqueteria' => $order->shipping_carrier ?? $order->shipping_name ?? $order->carrier ?? $order->paqueteria ?? null,
            'servicio' => $order->shipping_service ?? $order->service ?? null,
            'email' => $order->email ?? $order->customer_email ?? null,
            'items' => $this->orderItemsPayload($id),
        ];
    }

    private function orderItemsPayload($orderId): array
    {
        if (!$orderId || !Schema::hasTable('order_items')) {
            return [];
        }

        try {
            $query = DB::table('order_items')->where('order_id', $orderId);

            return $query->limit(12)->get()->map(function ($item) {
                return [
                    'nombre' => $item->name ?? $item->product_name ?? $item->description ?? 'Producto',
                    'sku' => $item->sku ?? null,
                    'cantidad' => $item->qty ?? $item->quantity ?? 1,
                    'precio' => $item->price ?? $item->unit_price ?? null,
                ];
            })->values()->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function recentReports(Request $request): array
    {
        if (!class_exists(WebAssistantReport::class) || !Schema::hasTable('web_assistant_reports')) {
            return [];
        }

        $user = Auth::user();
        $guestId = $this->guestId($request);

        return WebAssistantReport::query()
            ->where(function ($q) use ($user, $guestId) {
                if ($user) {
                    $q->where('user_id', $user->id);
                } else {
                    $q->whereNull('user_id')->where('guest_id', $guestId);
                }
            })
            ->orderByDesc('id')
            ->limit(5)
            ->get(['folio', 'type', 'status', 'order_folio', 'created_at'])
            ->map(fn ($report) => [
                'folio' => $report->folio,
                'tipo' => $report->type,
                'estatus' => $report->status,
                'pedido' => $report->order_folio,
                'fecha' => optional($report->created_at)->format('Y-m-d H:i'),
            ])
            ->values()
            ->toArray();
    }

    private function recentConversationMessages(WebAssistantConversation $conversation): array
    {
        return $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderByDesc('id')
            ->limit(16)
            ->get()
            ->reverse()
            ->map(fn ($item) => [
                'role' => $item->role === 'assistant' ? 'assistant' : 'user',
                'content' => (string) $item->content,
            ])
            ->values()
            ->toArray();
    }

    private function historyAsPlainText(array $recentMessages): string
    {
        if (empty($recentMessages)) {
            return 'Sin historial previo.';
        }

        return collect($recentMessages)->map(function ($message) {
            $speaker = $message['role'] === 'assistant' ? 'Asistente' : 'Usuario';
            return $speaker . ': ' . strip_tags((string) $message['content']);
        })->implode("\n");
    }

    private function resolveConversationForMessage(Request $request, ?int $conversationId, string $firstMessage): WebAssistantConversation
    {
        $guestId = $this->guestId($request);
        $user = Auth::user();

        if ($conversationId) {
            $conversation = WebAssistantConversation::find($conversationId);

            if ($conversation && $this->conversationBelongsToActor($request, $conversation) && $this->isConversationActive($conversation)) {
                return $conversation;
            }
        }

        $active = $this->actorConversationsQuery($user?->id, $guestId)
            ->where('last_activity_at', '>=', now()->subMinutes($this->inactiveMinutes))
            ->orderByDesc('last_activity_at')
            ->first();

        return $active ?: $this->createNewConversation($request, $firstMessage);
    }

    private function createNewConversation(Request $request, string $titleSeed = ''): WebAssistantConversation
    {
        $user = Auth::user();

        return WebAssistantConversation::create([
            'user_id' => $user?->id,
            'guest_id' => $user ? null : $this->guestId($request),
            'title' => $this->makeTitle($titleSeed ?: 'Nueva conversación'),
            'status' => 'active',
            'support_status' => 'bot',
            'last_activity_at' => now(),
            'meta' => ['source' => 'web_drawer'],
        ]);
    }

    private function actorConversationsQuery(?int $userId, string $guestId)
    {
        return WebAssistantConversation::query()
            ->where(function ($q) use ($userId, $guestId) {
                if ($userId) {
                    $q->where('user_id', $userId);
                } else {
                    $q->whereNull('user_id')->where('guest_id', $guestId);
                }
            });
    }

    private function abortUnlessConversationBelongsToActor(Request $request, WebAssistantConversation $conversation): void
    {
        abort_unless($this->conversationBelongsToActor($request, $conversation), 404);
    }

    private function conversationBelongsToActor(Request $request, WebAssistantConversation $conversation): bool
    {
        $user = Auth::user();

        if ($user) {
            return (int) $conversation->user_id === (int) $user->id;
        }

        return blank($conversation->user_id) && (string) $conversation->guest_id === (string) $this->guestId($request);
    }

    private function isConversationActive(WebAssistantConversation $conversation): bool
    {
        $last = $conversation->last_activity_at ?: $conversation->updated_at ?: $conversation->created_at;
        return $last ? Carbon::parse($last)->greaterThanOrEqualTo(now()->subMinutes($this->inactiveMinutes)) : false;
    }

    private function conversationPayload(WebAssistantConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'title' => $conversation->title ?: 'Nueva conversación',
            'status' => $this->isConversationActive($conversation) ? 'Activo' : 'Inactivo',
            'last_activity_at' => optional($conversation->last_activity_at)->toIso8601String(),
            'updated_at' => optional($conversation->updated_at)->toIso8601String(),
            'time' => optional($conversation->last_activity_at ?: $conversation->updated_at)->format('h:i a'),
            'messages_count' => $conversation->messages_count ?? $conversation->messages()->count(),
            'support_status' => $conversation->support_status ?? 'bot',
            'support_requested_at' => optional($conversation->support_requested_at ?? null)->toIso8601String(),
            'advisor_id' => $conversation->advisor_id ?? null,
            'customer_unread_count' => (int) ($conversation->customer_unread_count ?? 0),
            'advisor_unread_count' => (int) ($conversation->advisor_unread_count ?? 0),
        ];
    }

    private function messagePayload(WebAssistantMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'created_at' => optional($message->created_at)->toIso8601String(),
            'time' => optional($message->created_at)->format('h:i a'),
        ];
    }

    private function guestId(Request $request): string
    {
        if (!$request->session()->has('web_assistant_guest_id')) {
            $request->session()->put('web_assistant_guest_id', (string) Str::uuid());
        }

        return (string) $request->session()->get('web_assistant_guest_id');
    }

    private function makeTitle(string $text): string
    {
        $clean = trim(strip_tags($text));
        return $clean === '' ? 'Nueva conversación' : Str::limit($clean, 55, '...');
    }

    private function lastAssistantText(WebAssistantConversation $conversation): string
    {
        return (string) optional($conversation->messages()->where('role', 'assistant')->orderByDesc('id')->first())->content;
    }

    private function extractQuantity(string $text): int
    {
        if (preg_match('/\b(\d{1,4})\s*(piezas|pieza|pz|pzs|unidades|unidad|u)?\b/i', $text, $matches)) {
            return max(1, (int) $matches[1]);
        }

        return 1;
    }

    private function extractSku(string $text): ?string
    {
        if (preg_match('/\bsku\s*[:#-]?\s*([a-zA-Z0-9._-]{3,80})\b/i', $text, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    private function extractPossibleFolio(string $message): ?string
    {
        if (preg_match('/\b\d{5,}\b/', $message, $matches)) {
            return $matches[0];
        }

        return null;
    }

    private function extractPossibleEmail(string $message): ?string
    {
        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $message, $matches)) {
            return strtolower($matches[0]);
        }

        return null;
    }

    private function actionSearchTerms(string $text)
    {
        return collect(preg_split('/\s+/', mb_strtolower(strip_tags($text))))
            ->map(fn ($term) => trim($term, " \t\n\r\0\x0B.,;:()[]{}¿?¡!\"'"))
            ->filter(fn ($term) => mb_strlen($term) >= 3)
            ->reject(fn ($term) => in_array(Str::ascii($term), [
                'que','para','con','los','las','una','uno','del','por','como','quiero','busco','tienes','producto','productos',
                'pedido','pedidos','compras','realizado','realizadas','promocion','promociones','hola','ayuda','quien','cuando','donde',
                'cual','cuanto','cuantos','hoy','agrega','agregar','agregalos','carrito','piezas','pieza','stock','disponible','favor',
                'claro','confirmame','confirmo','siguiente','paso','sku','del','los','estaba','tengo','con gusto'
            ], true))
            ->unique()
            ->values();
    }

    private function cleanText(string $message): string
    {
        return Str::of($message)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9@.\s-]/', ' ')
            ->squish()
            ->toString();
    }

    private function generateReportFolio(): string
    {
        do {
            $folio = 'RPT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
        } while (Schema::hasTable('web_assistant_reports') && WebAssistantReport::where('folio', $folio)->exists());

        return $folio;
    }

    private function detectReportType(string $message): string
    {
        $clean = $this->cleanText($message);

        if (str_contains($clean, 'cancel')) return 'cancelacion';
        if (str_contains($clean, 'devol')) return 'devolucion';
        if (str_contains($clean, 'garantia')) return 'garantia';
        if (str_contains($clean, 'atras') || str_contains($clean, 'no llega') || str_contains($clean, 'paquete') || str_contains($clean, 'envio')) return 'envio';

        return 'general';
    }

    private function reportTypeLabel(string $type): string
    {
        return match ($type) {
            'cancelacion' => 'Cancelación',
            'devolucion' => 'Devolución',
            'garantia' => 'Garantía',
            'envio' => 'Problema de envío',
            default => 'Soporte general',
        };
    }

    private function extractResponsesText(array $json): ?string
    {
        $chunks = [];

        foreach ((array) data_get($json, 'output', []) as $output) {
            foreach ((array) data_get($output, 'content', []) as $content) {
                $text = data_get($content, 'text') ?: data_get($content, 'value') ?: data_get($content, 'content');

                if (is_string($text) && trim($text) !== '') {
                    $chunks[] = $text;
                }
            }
        }

        return count($chunks) ? implode("\n", $chunks) : null;
    }

    private function cleanAssistantReply(string $reply): string
    {
        $reply = trim($reply);
        $reply = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $reply);
        $reply = preg_replace('/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/s', '<strong>$1</strong>', $reply);
        $reply = strip_tags($reply, '<strong><b><em><i><ul><ol><li><br><p><span><a>');
        $reply = preg_replace_callback('/<([a-z0-9]+)([^>]*)>/i', function ($matches) {
            $tag = strtolower($matches[1]);

            if ($tag !== 'a') {
                return '<' . $tag . '>';
            }

            $attrs = $matches[2] ?? '';

            if (preg_match('/href\s*=\s*([\"\'])(.*?)\1/i', $attrs, $hrefMatch)) {
                $href = $hrefMatch[2];

                if (Str::startsWith($href, ['http://', 'https://', '/'])) {
                    return '<a href="' . e($href) . '">';
                }
            }

            return '<a>';
        }, $reply);

        return $reply ?: 'No recibí una respuesta válida. Intenta nuevamente.';
    }
}
