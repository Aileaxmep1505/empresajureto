<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private function getCart(): array
    {
        return session()->get('cart', []); // [id => ['id'=>, 'name'=>, 'price'=>, 'qty'=>, 'image'=>, 'slug'=>]]
    }

    private function saveCart(array $cart): void
    {
        session(['cart' => $cart]);
    }

    private function unitPrice(CatalogItem $item): float
    {
        return (float)($item->sale_price ?? $item->price ?? 0);
    }

    private function totals(array $cart): array
    {
        $subtotal = 0;
        foreach ($cart as $row) {
            $subtotal += ((float)$row['price']) * ((int)$row['qty']);
        }
        $ivaRate  = 0.16; // ajusta si no aplicas IVA
        $iva      = round($subtotal * $ivaRate, 2);
        $total    = round($subtotal + $iva, 2);

        return [
            'count'    => array_sum(array_column($cart, 'qty')),
            'subtotal' => round($subtotal, 2),
            'iva'      => $iva,
            'total'    => $total,
        ];
    }

    public function index()
    {
        $cart   = $this->getCart();
        $totals = $this->totals($cart);
        return view('web.cart.index', compact('cart', 'totals'));
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'catalog_item_id' => ['required','integer','exists:catalog_items,id'],
            'qty'             => ['nullable','integer','min:1','max:999'],
        ]);

        $item = CatalogItem::published()->findOrFail($data['catalog_item_id']);

        $cart = $this->getCart();

        if (isset($cart[$item->id])) {
            $cart[$item->id]['qty'] += ($data['qty'] ?? 1);
        } else {
            $cart[$item->id] = [
                'id'    => $item->id,
                'slug'  => $item->slug,
                'name'  => $item->name,
                'price' => $this->unitPrice($item),
                'qty'   => (int)($data['qty'] ?? 1),
                'image' => $item->image_url,
                'sku'   => $item->sku,
            ];
        }

        $this->saveCart($cart);
        $totals = $this->totals($cart);

        if ($request->wantsJson()) {
            return response()->json(['ok'=>true,'cart'=>$cart,'totals'=>$totals]);
        }
        return back()->with('ok', 'Producto agregado al carrito.');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'catalog_item_id' => ['required','integer'],
            'qty'             => ['required','integer','min:1','max:999'],
        ]);

        $cart = $this->getCart();
        if (!isset($cart[$data['catalog_item_id']])) {
            return response()->json(['ok'=>false,'msg'=>'Item no existe en carrito'], 404);
        }
        $cart[$data['catalog_item_id']]['qty'] = (int)$data['qty'];
        $this->saveCart($cart);
        $totals = $this->totals($cart);

        return response()->json(['ok'=>true,'cart'=>$cart,'totals'=>$totals]);
    }

    public function remove(Request $request)
    {
        $data = $request->validate([
            'catalog_item_id' => ['required','integer'],
        ]);

        $cart = $this->getCart();
        unset($cart[$data['catalog_item_id']]);
        $this->saveCart($cart);
        $totals = $this->totals($cart);

        if ($request->wantsJson()) {
            return response()->json(['ok'=>true,'cart'=>$cart,'totals'=>$totals]);
        }
        return back()->with('ok', 'Producto eliminado del carrito.');
    }

    public function clear(Request $request)
    {
        $this->saveCart([]);
        if ($request->wantsJson()) {
            return response()->json(['ok'=>true,'cart'=>[],'totals'=>['count'=>0,'subtotal'=>0,'iva'=>0,'total'=>0]]);
        }
        return back()->with('ok', 'Carrito vaciado.');
    }

    public function checkoutPreview()
    {
        $cart   = $this->getCart();
        $totals = $this->totals($cart);
        if ($totals['count'] < 1) {
            return redirect()->route('web.cart.index')->with('ok','Tu carrito está vacío.');
        }
        // Aquí podrías pedir datos de envío/facturación o redirigir a un “checkout”
        return view('web.cart.checkout', compact('cart','totals'));
    }
}
