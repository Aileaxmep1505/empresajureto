<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;

class CatalogItemController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [ new Middleware('auth') ]; // agrega tus gates/policies si usas roles
    }

    public function index(Request $request)
    {
        $q = CatalogItem::query();

        $s = trim((string)$request->get('s', ''));
        if ($s !== '') {
            $q->where(function ($qq) use ($s) {
                $qq->where('name', 'like', "%{$s}%")
                   ->orWhere('sku', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $q->where('status', (int)$request->integer('status'));
        }

        if ($request->boolean('featured_only')) {
            $q->where('is_featured', true);
        }

        $items = $q->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.catalog.index', [
            'items' => $items,
            'filters' => [
                's' => $s,
                'status' => $request->get('status'),
                'featured_only' => $request->boolean('featured_only'),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.catalog.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required','string','max:255'],
            'slug'        => ['nullable','string','max:255','unique:catalog_items,slug'],
            'sku'         => ['nullable','string','max:120'],
            'price'       => ['required','numeric','min:0'],
            'sale_price'  => ['nullable','numeric','min:0'],
            'status'      => ['required','integer','in:0,1,2'],
            'image_url'   => ['nullable','string','max:2048'],
            'images'      => ['nullable','array'],
            'images.*'    => ['nullable','url'],
            'is_featured' => ['nullable','boolean'],
            'brand_id'    => ['nullable','integer'],
            'category_id' => ['nullable','integer'],
            'excerpt'     => ['nullable','string'],
            'description' => ['nullable','string'],
            'published_at'=> ['nullable','date'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        if (CatalogItem::where('slug', $data['slug'])->exists()) {
            $data['slug'] = Str::slug($data['name'].'-'.Str::random(4));
        }
        $data['is_featured'] = (bool)($data['is_featured'] ?? false);

        $item = CatalogItem::create($data);

        return redirect()
            ->route('admin.catalog.edit', $item->id)
            ->with('ok', 'Producto web creado correctamente.');
    }

    public function edit(CatalogItem $catalogItem)
    {
        return view('admin.catalog.edit', ['item' => $catalogItem]);
    }

    public function update(Request $request, CatalogItem $catalogItem)
    {
        $data = $request->validate([
            'name'        => ['required','string','max:255'],
            'slug'        => ['nullable','string','max:255','unique:catalog_items,slug,'.$catalogItem->id],
            'sku'         => ['nullable','string','max:120'],
            'price'       => ['required','numeric','min:0'],
            'sale_price'  => ['nullable','numeric','min:0'],
            'status'      => ['required','integer','in:0,1,2'],
            'image_url'   => ['nullable','string','max:2048'],
            'images'      => ['nullable','array'],
            'images.*'    => ['nullable','url'],
            'is_featured' => ['nullable','boolean'],
            'brand_id'    => ['nullable','integer'],
            'category_id' => ['nullable','integer'],
            'excerpt'     => ['nullable','string'],
            'description' => ['nullable','string'],
            'published_at'=> ['nullable','date'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_featured'] = (bool)($data['is_featured'] ?? false);

        $catalogItem->update($data);

        return back()->with('ok', 'Producto web actualizado.');
    }

    public function destroy(CatalogItem $catalogItem)
    {
        $catalogItem->delete();
        return redirect()->route('admin.catalog.index')->with('ok', 'Producto web eliminado.');
    }

    /** Publicar/Ocultar rápido */
    public function toggleStatus(CatalogItem $catalogItem)
    {
        $catalogItem->status = $catalogItem->status == 1 ? 2 : 1;
        if ($catalogItem->status == 1 && !$catalogItem->published_at) {
            $catalogItem->published_at = now();
        }
        $catalogItem->save();

        return back()->with('ok', 'Estado actualizado.');
    }
}
