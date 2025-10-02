<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer.name'  => ['required','string','max:120'],
            'customer.email' => ['required','email','max:160'],
            'customer.phone' => ['nullable','string','max:40'],
            'customer.address' => ['nullable','string','max:240'],

            'items'          => ['required','array','min:1'],
            'items.*.id'     => ['required','integer','exists:products,id'],
            'items.*.qty'    => ['required','integer','min:1'],
        ]);

        $order = DB::transaction(function () use ($data) {
            $itemsData = [];
            $subtotal  = 0;

            foreach ($data['items'] as $it) {
                $p = Product::lockForUpdate()->find($it['id']); // lock opcional
                $qty = (int) $it['qty'];

                $line = [
                    'product_id' => $p->id,
                    'name'       => $p->name,
                    'sku'        => $p->sku,
                    'price'      => (float) $p->price,
                    'qty'        => $qty,
                    'amount'     => (float) $p->price * $qty,
                ];
                $subtotal += $line['amount'];
                $itemsData[] = $line;
            }

            $shipping = 0;
            $tax      = round($subtotal * 0.16, 2); // IVA 16% (ajusta o calcula en BE real)
            $total    = $subtotal + $shipping + $tax;

            $order = new Order();
            $order->customer_name    = $data['customer']['name'];
            $order->customer_email   = $data['customer']['email'];
            $order->customer_phone   = $data['customer']['phone'] ?? null;
            $order->customer_address = $data['customer']['address'] ?? null;

            $order->currency = 'MXN';
            $order->subtotal = $subtotal;
            $order->shipping = $shipping;
            $order->tax      = $tax;
            $order->total    = $total;
            $order->status   = 'pending';
            $order->save();

            foreach ($itemsData as $line) {
                $oi = new OrderItem();
                $oi->order_id   = $order->id;
                $oi->product_id = $line['product_id'];
                $oi->name       = $line['name'];
                $oi->sku        = $line['sku'];
                $oi->price      = $line['price'];
                $oi->qty        = $line['qty'];
                $oi->amount     = $line['amount'];
                $oi->save();
            }

            return $order->fresh(['items']);
        });

        return response()->json([
            'ok'    => true,
            'order' => [
                'id'       => $order->id,
                'status'   => $order->status,
                'subtotal' => $order->subtotal,
                'tax'      => $order->tax,
                'shipping' => $order->shipping,
                'total'    => $order->total,
            ]
        ], 201);
    }
}
