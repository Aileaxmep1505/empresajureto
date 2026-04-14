<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CartController extends Controller
{
    private function getCart(): array
    {
        return session()->get('cart', []);
    }

    private function saveCart(array $cart): void
    {
        session(['cart' => $cart]);
    }

    private function unitPrice(CatalogItem $item): float
    {
        return (float)($item->sale_price ?? $item->price ?? 0);
    }

    /**
     * URL pública de la imagen principal (photo_1 preferida).
     * Requiere: php artisan storage:link
     */
    private function primaryImageUrl(CatalogItem $item): ?string
    {
        $candidates = [$item->photo_1 ?? null, $item->photo_2 ?? null, $item->photo_3 ?? null];

        foreach ($candidates as $path) {
            if (!$path || !is_string($path) || trim($path) === '') continue;

            $path = trim($path);

            // si ya es URL absoluta
            if (Str::startsWith($path, ['http://', 'https://'])) return $path;

            // si viene como "storage/..."
            if (Str::startsWith($path, 'storage/')) return asset($path);

            // si viene como "catalog/photos/..." (guardado en disk public)
            return Storage::url($path); // => /storage/catalog/photos/...
        }

        return null;
    }

    /**
     * Totales del carrito.
     * IMPORTANTE: NO se agrega IVA porque ya viene incluido en los precios.
     */
    private function totals(array $cart): array
    {
        $subtotal = 0.0;
        foreach ($cart as $row) {
            $subtotal += ((float)$row['price']) * ((int)$row['qty']);
        }

        $subtotal = round($subtotal, 2);
        $iva = 0.0;
        $total = $subtotal;

        return [
            'count'    => array_sum(array_column($cart, 'qty')),
            'subtotal' => $subtotal,
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

        $qtyToAdd = (int)($data['qty'] ?? 1);

        if (isset($cart[$item->id])) {
            $cart[$item->id]['qty'] += $qtyToAdd;

            // (opcional) refresca imagen si antes estaba vacía
            if (empty($cart[$item->id]['image'])) {
                $cart[$item->id]['image'] = $this->primaryImageUrl($item);
            }
        } else {
            $cart[$item->id] = [
                'id'    => $item->id,
                'slug'  => $item->slug,
                'name'  => $item->name,
                'price' => $this->unitPrice($item),
                'qty'   => $qtyToAdd,
                // ✅ FOTO PRINCIPAL REAL
                'image' => $this->primaryImageUrl($item),
                'sku'   => $item->sku,
            ];
        }

        $this->saveCart($cart);
        $totals = $this->totals($cart);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'cart' => $cart, 'totals' => $totals]);
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

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok'=>true,'cart'=>$cart,'totals'=>$totals]);
        }
        return back()->with('ok', 'Producto eliminado del carrito.');
    }

    public function clear(Request $request)
    {
        $this->saveCart([]);
        $json = ['ok'=>true,'cart'=>[],'totals'=>['count'=>0,'subtotal'=>0,'iva'=>0,'total'=>0]];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($json);
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
        return view('web.cart.checkout', compact('cart','totals'));
    }
}