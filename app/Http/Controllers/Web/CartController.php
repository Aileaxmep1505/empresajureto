<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CartController extends Controller
{
    /**
     * Obtiene el carrito desde sesión.
     * Estructura: [id => ['id','name','price','qty','image','slug','sku']]
     */
    private function getCart(): array
    {
        return session()->get('cart', []);
    }

    /** Guarda el carrito en sesión. */
    private function saveCart(array $cart): void
    {
        session(['cart' => $cart]);
    }

    /** Precio unitario considerando oferta si existe. */
    private function unitPrice(CatalogItem $item): float
    {
        return (float)($item->sale_price ?? $item->price ?? 0);
    }

    /**
     * Convierte rutas tipo "catalog/photos/xxx.jpg" a URL pública.
     * IMPORTANTE: esto requiere `php artisan storage:link`
     *
     * Usamos asset('storage/...') porque funciona aunque tu app esté en subcarpeta.
     */
    private function toPublicImageUrl(?string $path): ?string
    {
        if (!$path || !is_string($path) || trim($path) === '') return null;

        $path = trim($path);

        // si ya es URL absoluta
        if (Str::startsWith($path, ['http://', 'https://'])) return $path;

        // si viene como "/storage/..."
        if (Str::startsWith($path, '/storage/')) return url($path);

        // si viene como "storage/..."
        if (Str::startsWith($path, 'storage/')) return asset($path);

        // tu caso común: "catalog/photos/..."
        if (Str::startsWith($path, 'catalog/')) {
            return asset('storage/' . ltrim($path, '/'));
        }

        // fallback general
        return asset('storage/' . ltrim($path, '/'));
    }

    /**
     * URL pública de la imagen principal (photo_1 preferida).
     */
    private function primaryImageUrl(CatalogItem $item): ?string
    {
        $candidates = [$item->photo_1 ?? null, $item->photo_2 ?? null, $item->photo_3 ?? null];

        foreach ($candidates as $path) {
            $url = $this->toPublicImageUrl($path);
            if ($url) return $url;
        }

        return null;
    }

    /**
     * Totales del carrito.
     * ✅ IMPORTANTE: NO se agrega IVA porque ya viene incluido en los precios.
     */
    private function totals(array $cart): array
    {
        $subtotal = 0.0;
        foreach ($cart as $row) {
            $subtotal += ((float)$row['price']) * ((int)$row['qty']);
        }

        $subtotal = round($subtotal, 2);

        return [
            'count'    => array_sum(array_column($cart, 'qty')),
            'subtotal' => $subtotal,
            'iva'      => 0.0,
            'total'    => $subtotal,
        ];
    }

    /** Vista del carrito. */
    public function index()
    {
        $cart   = $this->getCart();
        $totals = $this->totals($cart);
        return view('web.cart.index', compact('cart', 'totals'));
    }

    /** Agregar item al carrito (AJAX-friendly). */
    public function add(Request $request)
    {
        $data = $request->validate([
            'catalog_item_id' => ['required','integer','exists:catalog_items,id'],
            'qty'             => ['nullable','integer','min:1','max:999'],
        ]);

        $item = CatalogItem::published()->findOrFail($data['catalog_item_id']);
        $cart = $this->getCart();

        $qtyToAdd = (int)($data['qty'] ?? 1);

        // DEBUG (puedes quitarlo si ya no lo necesitas)
        Log::info('CART ADD DEBUG', [
            'item_id' => $item->id,
            'slug'    => $item->slug,
            'photo_1' => $item->photo_1,
            'photo_2' => $item->photo_2,
            'photo_3' => $item->photo_3,
        ]);

        if (isset($cart[$item->id])) {
            $cart[$item->id]['qty'] += $qtyToAdd;

            // refresca imagen si estaba vacía o venía como placeholder
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
                // ✅ FOTO PRINCIPAL (photo_1) -> URL pública
                'image' => $this->primaryImageUrl($item),
                'sku'   => $item->sku,
            ];
        }

        $this->saveCart($cart);
        $totals = $this->totals($cart);

        // DEBUG
        Log::info('CART ROW IMAGE SAVED', [
            'item_id' => $item->id,
            'saved_image' => $cart[$item->id]['image'] ?? null,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'cart' => $cart, 'totals' => $totals]);
        }

        return back()->with('ok', 'Producto agregado al carrito.');
    }

    /** Actualiza cantidad de un item (siempre JSON). */
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

    /** Elimina un item del carrito (AJAX-friendly). */
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

    /** Vacía el carrito (AJAX-friendly). */
    public function clear(Request $request)
    {
        $this->saveCart([]);
        $json = ['ok'=>true,'cart'=>[],'totals'=>['count'=>0,'subtotal'=>0,'iva'=>0,'total'=>0]];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($json);
        }
        return back()->with('ok', 'Carrito vaciado.');
    }

    /** Previsualización de checkout. */
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