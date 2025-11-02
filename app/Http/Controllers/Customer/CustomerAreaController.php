<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\BillingProfile;

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
            ->where('customer_email', $user->email)
            ->with('items')
            ->latest('created_at');

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

        // Tu Blade está en resources/views/web/customer/perfil.blade.php
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
     * Reordenar: vuelve a agregar al carrito (en sesión) los ítems de un pedido.
     * POST /mi-cuenta/pedidos/{order}/repetir
     */
    public function reorder(Order $order)
    {
        // Seguridad básica: solo puede reordenar si el email del pedido coincide con el del usuario
        if (strcasecmp((string) $order->customer_email, (string) Auth::user()->email) !== 0) {
            abort(403, 'No autorizado.');
        }

        // Asegura que los items estén cargados
        $order->loadMissing('items');

        $cart = session('cart', []);
        foreach ($order->items as $it) {
            $pid = (string) ($it->product_id ?? $it->id);

            $unit = (float) ($it->price ?? $it->unit_price ?? 0);
            $qty  = (int) ($it->qty ?? 1);

            $cart[$pid] = [
                'product_id' => $pid,
                'name'       => $it->name,
                'price'      => $unit,
                'qty'        => ($cart[$pid]['qty'] ?? 0) + $qty,
                // Usa la imagen del item si existe
                'image'      => $cart[$pid]['image'] ?? ($it->image_url ?? null),
            ];
        }

        session(['cart' => $cart]);

        return back()->with('ok', 'Productos agregados al carrito.');
    }

    /**
     * Seguimiento (rastreo) del envío de un pedido del cliente autenticado.
     * GET /mi-cuenta/pedidos/{order}/rastreo
     * Devuelve JSON normalizado: carrier, service, code, eta, status, progress, events[]
     */
    public function tracking(Request $request, Order $order)
    {
        if (strcasecmp((string) $order->customer_email, (string) Auth::user()->email) !== 0) {
            abort(403, 'No autorizado.');
        }

        // Mapeo directo a tus columnas
        $carrier = $order->shipping_name;        // ej. "Estafeta", "FedEx", "SkydropX"
        $service = $order->shipping_service;     // ej. "Express", "Económico"
        $code    = $order->shipping_code;        // número de guía
        $eta     = $order->shipping_eta;         // fecha estimada si la guardas
        $status  = $order->status;               // "paid", "processing", "shipped", "delivered", "cancelado", etc.

        // Línea de tiempo básica solo con created_at + estado textual
        $events = [];
        $push = function ($label, $at = null, $loc = null, $details = null) use (&$events) {
            $events[] = [
                'time'    => $at ? ($at instanceof \DateTimeInterface ? $at->toIso8601String() : (string)$at) : null,
                'status'  => $label,
                'location'=> $loc,
                'details' => $details,
            ];
        };

        $push('Pedido creado', $order->created_at);

        // Deducción por estatus (sin timestamps específicos)
        $st = strtolower((string)$status);
        if (in_array($st, ['paid','pagado'])) {
            $push('Pago confirmado', null, null, 'Pago recibido');
        }
        if (in_array($st, ['processing','procesando','preparando'])) {
            $push('Preparando pedido');
        }
        if (in_array($st, ['shipped','enviado','in_transit'])) {
            $push('Enviado', null, $carrier, $service);
        }
        if (in_array($st, ['out_for_delivery','en_reparto'])) {
            $push('En reparto', null, $carrier);
        }
        if (in_array($st, ['delivered','entregado'])) {
            $push('Entregado', null, 'Destino');
        }
        if (in_array($st, ['cancelled','canceled','cancelado'])) {
            $push('Cancelado');
        }

        // Ordena por fecha (los sin fecha quedan al final)
        usort($events, function ($a, $b) {
            return strcmp((string)($b['time'] ?? ''), (string)($a['time'] ?? ''));
        });

        // Progreso estimado por estatus
        $map = [
            'pending' => 10, 'pendiente' => 10,
            'paid' => 30, 'pagado' => 30,
            'processing' => 50, 'procesando' => 50, 'preparando' => 50,
            'shipped' => 80, 'enviado' => 80, 'in_transit' => 75,
            'out_for_delivery' => 90, 'en_reparto' => 90,
            'delivered' => 100, 'entregado' => 100,
            'cancelled' => 0, 'canceled' => 0, 'cancelado' => 0,
        ];
        $progress = $map[$st] ?? 0;

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
     * (Opcional: requiere columna shipping_label_url en orders)
     */
    public function label(Request $request, Order $order)
    {
        if (strcasecmp((string) $order->customer_email, (string) Auth::user()->email) !== 0) {
            abort(403, 'No autorizado.');
        }

        $labelUrl = $order->shipping_label_url ?? null;
        if ($labelUrl) {
            return redirect()->away($labelUrl);
        }
        abort(404, 'Guía no disponible.');
    }
}
