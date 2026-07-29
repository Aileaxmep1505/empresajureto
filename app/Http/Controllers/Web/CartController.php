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

    /** Agregar item al carrito (AJAX-friendly). Soporta presentaciones (pieza / caja). */
    public function add(Request $request)
    {
        $data = $request->validate([
            'catalog_item_id' => ['required','integer','exists:catalog_items,id'],
            'qty'             => ['nullable','integer','min:1','max:999'],
            'presentation'    => ['nullable','string','max:40'],
        ]);

        $item = CatalogItem::published()->findOrFail($data['catalog_item_id']);
        $cart = $this->getCart();

        $qtyToAdd = (int)($data['qty'] ?? 1);

        // Resolver la presentación (pieza / caja) SIEMPRE desde la BD:
        // nunca confiamos en el precio o factor que manda el navegador.
        $token = (string) ($data['presentation'] ?? 'base');
        $pres  = $item->resolveWebPresentation($token) ?? $item->resolveWebPresentation('base');
        $units = max(1, (int) ($pres['units'] ?? 1));
        $price = (float) ($pres['effective_price'] ?? ($item->sale_price ?? $item->price ?? 0));

        // Key compuesto: distingue "pieza" y "caja" del mismo producto en el carrito.
        $key = $item->id . ':' . ($pres['token'] ?? 'base');

        // NOTA: no se bloquea la venta por falta de stock aquí.
        // El checkout web no descuenta inventario y el surtido automático (WMS)
        // ya divide el faltante en línea "RECOLECTAR". Así se pueden vender cajas
        // aunque no haya piezas suficientes en el momento (backorder / recolección).

        if (isset($cart[$key])) {
            $cart[$key]['qty'] += $qtyToAdd;

            if (empty($cart[$key]['image'])) {
                $cart[$key]['image'] = $this->primaryImageUrl($item);
            }
        } else {
            $cart[$key] = [
                'key'                => $key,
                'id'                 => $item->id,
                'catalog_item_id'    => $item->id,
                'slug'               => $item->slug,
                'name'               => $item->name,
                'price'              => $price,
                'qty'                => $qtyToAdd,
                'image'              => $this->primaryImageUrl($item),
                'sku'                => $item->sku,

                // Presentación de venta
                'presentation'       => (string) ($pres['token'] ?? 'base'),
                'presentation_label' => (string) ($pres['label'] ?? 'Pieza'),
                'units'              => $units,
                'barcode'            => (string) ($pres['barcode'] ?? ''),
            ];
        }

        $this->saveCart($cart);
        $totals = $this->totals($cart);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'cart' => $cart, 'totals' => $totals]);
        }

        return back()->with('ok', 'Producto agregado al carrito.');
    }

    /**
     * Actualiza cantidad de un item (siempre JSON).
     * Acepta `cart_key` (nuevo, key compuesto por presentación) o
     * `catalog_item_id` (legacy, filas viejas keyed por id).
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'cart_key'        => ['nullable','string','max:80'],
            'catalog_item_id' => ['nullable','string','max:80'],
            'qty'             => ['required','integer','min:1','max:999'],
        ]);

        $key  = (string) ($data['cart_key'] ?? $data['catalog_item_id'] ?? '');
        $cart = $this->getCart();

        if ($key === '' || !isset($cart[$key])) {
            return response()->json(['ok'=>false,'msg'=>'Item no existe en carrito'], 404);
        }

        $cart[$key]['qty'] = (int)$data['qty'];
        $this->saveCart($cart);
        $totals = $this->totals($cart);

        return response()->json(['ok'=>true,'cart'=>$cart,'totals'=>$totals]);
    }

    /** Elimina un item del carrito (AJAX-friendly). */
    public function remove(Request $request)
    {
        $data = $request->validate([
            'cart_key'        => ['nullable','string','max:80'],
            'catalog_item_id' => ['nullable','string','max:80'],
        ]);

        $key  = (string) ($data['cart_key'] ?? $data['catalog_item_id'] ?? '');
        $cart = $this->getCart();
        unset($cart[$key]);
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