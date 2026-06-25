<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopifyOrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->get('search', ''));
        $status = trim((string) $request->get('status', ''));
        $dateFrom = trim((string) $request->get('date_from', ''));
        $dateTo = trim((string) $request->get('date_to', ''));

        $ordersQuery = Order::query()
            ->with(['items'])
            ->where('stripe_session_id', 'like', 'shopify_%')
            ->latest();

        if ($search !== '') {
            $ordersQuery->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', '%' . $search . '%')
                    ->orWhere('customer_email', 'like', '%' . $search . '%')
                    ->orWhere('stripe_session_id', 'like', '%' . $search . '%');
            });
        }

        if ($status !== '') {
            $ordersQuery->where('status', $status);
        }

        if ($dateFrom !== '') {
            $ordersQuery->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $ordersQuery->whereDate('created_at', '<=', $dateTo);
        }

        $orders = $ordersQuery->paginate(20)->withQueryString();

        $summaryBase = Order::query()
            ->where('stripe_session_id', 'like', 'shopify_%');

        $totalOrders = (clone $summaryBase)->count();
        $paidOrders = (clone $summaryBase)->where('status', 'paid')->count();
        $totalRevenue = (clone $summaryBase)->sum('total');

        $todayOrders = (clone $summaryBase)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return view('admin.shopify.orders.index', compact(
            'orders',
            'search',
            'status',
            'dateFrom',
            'dateTo',
            'totalOrders',
            'paidOrders',
            'totalRevenue',
            'todayOrders'
        ));
    }

    public function show(Order $order): View
    {
        abort_unless(str_starts_with((string) $order->stripe_session_id, 'shopify_'), 404);

        $order->load(['items']);

        return view('admin.shopify.orders.show', compact('order'));
    }
}