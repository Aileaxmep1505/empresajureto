<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
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
        $ym = $request->string('ym')->toString(); // ej. "2025-10"

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

            $cart[$pid] = [
                'product_id' => $pid,
                'name'       => $it->name,
                'price'      => (float) $it->price,
                'qty'        => ($cart[$pid]['qty'] ?? 0) + (int) $it->qty,
                // Mantén la imagen que ya hubiera en el carrito; si luego quieres, aquí podrías mapear a tu modelo Product
                'image'      => $cart[$pid]['image'] ?? null,
            ];
        }

        session(['cart' => $cart]);

        return back()->with('ok', 'Productos agregados al carrito.');
    }
}
