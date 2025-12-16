<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

use App\Models\Order;
use App\Models\BillingProfile;
use App\Services\SkydropxProClient;

class CustomerAreaController extends Controller
{
    /**
     * Perfil del cliente con tabs: resumen, pedidos, datos, facturación, facturas, direcciones.
     * GET /mi-cuenta
     */
    public function profile(Request $request)
    {
        $user = Auth::user();

        // Filtro opcional por mes (yyyy-mm)
        $ym = (string) $request->string('ym')->toString(); // ej. "2025-10"

        $ordersQuery = Order::query()
            ->with('items')
            ->latest('created_at');

        // ✅ Mejor: si guardas user_id en orders, usa eso
        if (!empty($user->id)) {
            $ordersQuery->where('user_id', $user->id);
        } else {
            // fallback por email
            $ordersQuery->where('customer_email', $user->email);
        }

        if (preg_match('/^\d{4}\-\d{2}$/', $ym)) {
            [$y, $m] = explode('-', $ym);
            $ordersQuery
                ->whereYear('created_at', (int) $y)
                ->whereMonth('created_at', (int) $m);
        }

        $orders = $ordersQuery->get();

        // Perfiles de facturación del usuario
        $billingProfiles = BillingProfile::where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->get();

        // (Opcional) Facturas si existe la tabla/modelo
        $invoices = collect();
        if (Schema::hasTable('invoices')) {
            $invoiceModel = '\\App\\Models\\Invoice';
            if (class_exists($invoiceModel)) {
                $invoices = $invoiceModel::where('user_id', $user->id)
                    ->latest('created_at')
                    ->get();
            }
        }

        // KPIs rápidos
        $stats = [
            'orders_total'  => $orders->count(),
            'orders_open'   => $orders->filter(fn ($o) => strtolower((string) $o->status) !== 'cancelado')->count(),
            'orders_cancel' => $orders->filter(fn ($o) => strtolower((string) $o->status) === 'cancelado')->count(),
            'spent_total'   => $orders->sum('total'),
        ];

        // Tab activo (?tab=)
        $activeTab = in_array($request->get('tab'), ['resumen','pedidos','datos','facturacion','facturas','direcciones'], true)
            ? $request->get('tab')
            : 'resumen';

        return view('web.customer.perfil', compact(
            'user',
            'orders',
            'billingProfiles',
            'invoices',
            'stats',
            'ym',
            'activeTab'
        ));
    }

    /**
     * ✅ DETALLE DEL PEDIDO (CLIENTE)
     * GET /mi-cuenta/pedidos/{order}
     */
    public function orderShow(Request $request, Order $order)
    {
        if (!$this->ownsOrder($order)) {
            abort(403, 'No autorizado.');
        }

        $order->loadMissing(['items','payments']);

        // Normaliza datos para UI
        $shipping = [
            'name'       => $order->shipping_name,
            'service'    => $order->shipping_service,
            'eta'        => $order->shipping_eta,
            'code'       => $order->shipping_code,
            'amount'     => (float)($order->shipping_amount ?? 0),
            'store_pays' => (bool)($order->shipping_store_pays ?? false),
        ];

        // Progreso por estatus
        $st = strtolower((string)($order->shipment_status ?: $order->status));
        $progress = $this->progressFromStatus($st);

        // Timeline básica
        $timeline = [];
        $push = function ($label, $time = null, $desc = null) use (&$timeline) {
            $timeline[] = ['label'=>$label,'time'=>$time,'desc'=>$desc];
        };

        $push('Pedido creado', $order->created_at, 'Recibimos tu pedido.');

        if (in_array($st, ['paid','pagado'])) {
            $push('Pago confirmado', null, 'Pago recibido por Stripe.');
        }

        if (in_array($st, ['processing','procesando','preparando'])) {
            $push('Preparando pedido', null, 'Estamos empacando tus productos.');
        }

        if (in_array($st, ['quoted'])) {
            $push('Cotización de envío lista', null, 'Tu envío fue cotizado en SkydropX.');
        }

        if (in_array($st, ['labeled'])) {
            $push('Guía generada', null, $shipping['code'] ? ('Guía: '.$shipping['code']) : 'Guía lista.');
        }

        if (in_array($st, ['shipped','enviado','in_transit','out_for_delivery','en_reparto','delivered','entregado'])) {
            $push('Enviado', null, $shipping['code'] ? ('Guía: '.$shipping['code']) : 'Tu pedido ya salió.');
        }

        if (in_array($st, ['delivered','entregado'])) {
            $push('Entregado', null, 'Pedido entregado.');
        }

        if (in_array($st, ['cancelled','canceled','cancelado'])) {
            $push('Cancelado', null, 'Pedido cancelado.');
        }

        return view('web.customer.order_show', compact('order','shipping','progress','timeline'));
    }

    /**
     * Reordenar: vuelve a agregar al carrito (en sesión) los ítems de un pedido.
     * POST /mi-cuenta/pedidos/{order}/repetir
     */
    public function reorder(Order $order)
    {
        if (!$this->ownsOrder($order)) {
            abort(403, 'No autorizado.');
        }

        $order->loadMissing('items');

        $cart = session('cart', []);
        foreach ($order->items as $it) {
            $pid = (string) ($it->product_id ?? $it->catalog_item_id ?? $it->id);

            $unit = (float) ($it->price ?? $it->unit_price ?? 0);
            $qty  = (int) ($it->qty ?? 1);

            $cart[$pid] = [
                'product_id' => $pid,
                'name'       => $it->name,
                'price'      => $unit,
                'qty'        => ($cart[$pid]['qty'] ?? 0) + $qty,
                'image'      => $cart[$pid]['image'] ?? ($it->image_url ?? data_get($it->meta,'image')),
            ];
        }

        session(['cart' => $cart]);

        return back()->with('ok', 'Productos agregados al carrito.');
    }

    /**
     * Seguimiento real con Skydropx si hay guía; si no, fallback a timeline simple.
     * GET /mi-cuenta/pedidos/{order}/rastreo
     */
    public function tracking(Request $request, Order $order, SkydropxProClient $skydropx)
    {
        if (!$this->ownsOrder($order)) {
            abort(403, 'No autorizado.');
        }

        $carrier = $order->shipping_name;
        $service = $order->shipping_service;
        $code    = $order->shipping_code;
        $eta     = $order->shipping_eta;

        // ✅ 1) Si hay guía, intenta tracking real en Skydropx
        // OJO: para que esto funcione debes implementar trackingByCode() en SkydropxProClient
        if (!empty($code) && method_exists($skydropx, 'trackingByCode')) {
            try {
                $res = $skydropx->trackingByCode($code);

                if (($res['ok'] ?? false) && is_array($res['json'])) {
                    $json = $res['json'];
                    $data = data_get($json, 'data', $json);

                    $status = (string) (
                        data_get($data, 'status') ??
                        data_get($data, 'tracking.status') ??
                        data_get($data, 'shipment.status') ??
                        $order->shipment_status ??
                        $order->status ??
                        'unknown'
                    );

                    $eventsRaw = data_get($data, 'events')
                        ?? data_get($data, 'tracking.events')
                        ?? data_get($data, 'history')
                        ?? [];

                    $events = [];
                    if (is_array($eventsRaw)) {
                        foreach ($eventsRaw as $ev) {
                            $events[] = [
                                'time'     => data_get($ev,'time')
                                    ?? data_get($ev,'date')
                                    ?? data_get($ev,'created_at')
                                    ?? data_get($ev,'timestamp'),
                                'status'   => (string) (data_get($ev,'status') ?? data_get($ev,'description') ?? 'Evento'),
                                'location' => (string) (data_get($ev,'location') ?? data_get($ev,'place') ?? ''),
                                'details'  => (string) (data_get($ev,'details') ?? data_get($ev,'message') ?? ''),
                            ];
                        }
                    }

                    // Ordenar desc
                    usort($events, fn($a,$b)=> strcmp((string)($b['time'] ?? ''), (string)($a['time'] ?? '')));

                    $progress = $this->progressFromStatus($status);

                    return response()->json([
                        'ok'       => true,
                        'carrier'  => $carrier,
                        'service'  => $service,
                        'code'     => $code,
                        'eta'      => $eta,
                        'status'   => $status,
                        'progress' => $progress,
                        'events'   => $events,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Customer tracking Skydropx failed: '.$e->getMessage(), [
                    'order_id' => $order->id,
                    'code'     => $code,
                ]);
                // cae a fallback
            }
        }

        // ✅ 2) Fallback
        [$status, $events, $progress] = $this->fallbackTimeline($order);

        return response()->json([
            'ok'       => true,
            'carrier'  => $carrier,
            'service'  => $service,
            'code'     => $code,
            'eta'      => $eta,
            'status'   => $status,
            'progress' => $progress,
            'events'   => $events,
        ]);
    }

    /**
     * Abre/descarga la guía del envío si tienes la URL guardada en el pedido.
     * GET /mi-cuenta/pedidos/{order}/guia
     */
    public function label(Request $request, Order $order)
    {
        if (!$this->ownsOrder($order)) {
            abort(403, 'No autorizado.');
        }

        $labelUrl = $order->shipping_label_url ?? null;
        if ($labelUrl) {
            return redirect()->away($labelUrl);
        }

        abort(404, 'Guía no disponible.');
    }

    /* ===================== Helpers ===================== */

    private function ownsOrder(Order $order): bool
    {
        $user = Auth::user();

        // Si tu order guarda user_id, úsalo
        if (!empty($order->user_id) && !empty($user->id)) {
            return (int)$order->user_id === (int)$user->id;
        }

        // fallback por email
        return strcasecmp((string)$order->customer_email, (string)$user->email) === 0;
    }

    private function fallbackTimeline(Order $order): array
    {
        $status = $order->shipment_status ?: $order->status;

        $events = [];
        $push = function ($label, $at = null, $loc = null, $details = null) use (&$events) {
            $events[] = [
                'time'     => $at ? ($at instanceof \DateTimeInterface ? $at->toIso8601String() : (string)$at) : null,
                'status'   => $label,
                'location' => $loc,
                'details'  => $details,
            ];
        };

        $push('Pedido creado', $order->created_at);

        $st = strtolower((string)$status);

        if (in_array($st, ['paid','pagado'])) $push('Pago confirmado', null, null, 'Pago recibido');
        if (in_array($st, ['processing','procesando','preparando'])) $push('Preparando pedido');
        if (in_array($st, ['quoted'])) $push('Cotización de envío lista');
        if (in_array($st, ['labeled'])) $push('Guía generada', null, $order->shipping_name, $order->shipping_code ? 'Guía: '.$order->shipping_code : null);
        if (in_array($st, ['shipped','enviado','in_transit'])) $push('En tránsito', null, $order->shipping_name, $order->shipping_service);
        if (in_array($st, ['out_for_delivery','en_reparto'])) $push('En reparto', null, $order->shipping_name);
        if (in_array($st, ['delivered','entregado'])) $push('Entregado', null, 'Destino');
        if (in_array($st, ['cancelled','canceled','cancelado'])) $push('Cancelado');

        usort($events, fn($a,$b)=> strcmp((string)($b['time'] ?? ''), (string)($a['time'] ?? '')));

        $progress = $this->progressFromStatus($st);

        return [$status, $events, $progress];
    }

    private function progressFromStatus(string $st): int
    {
        $st = strtolower(trim($st));

        $map = [
            'pending' => 10, 'pendiente' => 10,

            'paid' => 30, 'pagado' => 30,

            'processing' => 50, 'procesando' => 50, 'preparando' => 50,

            'quoted' => 55,
            'labeled' => 65,

            'shipped' => 80, 'enviado' => 80, 'in_transit' => 75,
            'out_for_delivery' => 90, 'en_reparto' => 90,

            'delivered' => 100, 'entregado' => 100,

            'cancelled' => 0, 'canceled' => 0, 'cancelado' => 0,
        ];

        return $map[$st] ?? 0;
    }
}
