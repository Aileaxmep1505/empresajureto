<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class ProductController extends Controller
{
      private function applySearch($query, string $q)
    {
        $q = trim($q);
        if ($q === '') return $query;

        return $query->where(function($qq) use ($q){
            $qq->where('name', 'like', "%{$q}%")
               ->orWhere('sku', 'like', "%{$q}%")
               ->orWhere('brand', 'like', "%{$q}%")
               ->orWhere('category', 'like', "%{$q}%")
               ->orWhere('tags', 'like', "%{$q}%");
        });
    }

    public function index(Request $request)
    {
        $q = (string) $request->get('q', '');
        $products = $this->applySearch(Product::query(), $q)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('products.index-table', compact('products','q'));
    }

    public function exportPdf(Request $request)
    {
        $q = (string) $request->get('q','');

        $items = $this->applySearch(Product::query(), $q)
            ->orderBy('name')
            ->get();

        $pdf = Pdf::loadView('products.pdf', [
            'items' => $items,
            'q'     => $q,
            'now'   => now(),
        ])->setPaper('a4', 'landscape'); // tabla ancha

        return $pdf->download('productos.pdf');
    }

    public function create()
    {
        return view('products.form', ['product' => new Product(), 'mode' => 'create']);
    }

    public function store(Request $request)
    {
        // Todo opcional, así que validamos tipos suaves
        $data = $request->validate([
            'name'            => ['nullable','string','max:255'],
            'sku'             => ['nullable','string','max:255'],
            'supplier_sku'    => ['nullable','string','max:255'],
            'unit'            => ['nullable','string','max:100'],
            'weight'          => ['nullable','numeric'],
            'cost'            => ['nullable','numeric'],
            'price'           => ['nullable','numeric'],
            'market_price'    => ['nullable','numeric'],
            'bid_price'       => ['nullable','numeric'],
            'dimensions'      => ['nullable','string','max:255'],
            'color'           => ['nullable','string','max:255'],
            'pieces_per_unit' => ['nullable','integer','min:0'],
            'active'          => ['nullable','boolean'],
            'brand'           => ['nullable','string','max:255'],
            'category'        => ['nullable','string','max:255'],
            'material'        => ['nullable','string','max:255'],
            'description'     => ['nullable','string'],
            'notes'           => ['nullable','string'],
            'tags'            => ['nullable','string','max:255'],
            'image'           => ['nullable','image','max:4096'], // 4MB
        ],[],[
            'name'=>'nombre','supplier_sku'=>'SKU proveedor','market_price'=>'precio de mercado',
            'bid_price'=>'precio de licitación','pieces_per_unit'=>'piezas por unidad'
        ]);

        $data['active'] = (bool) $request->boolean('active');

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products','public');
        }

        $product = Product::create($data);

        return redirect()->route('products.edit',$product)
            ->with('status','Producto creado');
    }

    public function edit(Product $product)
    {
        return view('products.form', ['product' => $product, 'mode' => 'edit']);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name'            => ['nullable','string','max:255'],
            'sku'             => ['nullable','string','max:255'],
            'supplier_sku'    => ['nullable','string','max:255'],
            'unit'            => ['nullable','string','max:100'],
            'weight'          => ['nullable','numeric'],
            'cost'            => ['nullable','numeric'],
            'price'           => ['nullable','numeric'],
            'market_price'    => ['nullable','numeric'],
            'bid_price'       => ['nullable','numeric'],
            'dimensions'      => ['nullable','string','max:255'],
            'color'           => ['nullable','string','max:255'],
            'pieces_per_unit' => ['nullable','integer','min:0'],
            'active'          => ['nullable','boolean'],
            'brand'           => ['nullable','string','max:255'],
            'category'        => ['nullable','string','max:255'],
            'material'        => ['nullable','string','max:255'],
            'description'     => ['nullable','string'],
            'notes'           => ['nullable','string'],
            'tags'            => ['nullable','string','max:255'],
            'image'           => ['nullable','image','max:4096'],
        ],[],[
            'name'=>'nombre','supplier_sku'=>'SKU proveedor','market_price'=>'precio de mercado',
            'bid_price'=>'precio de licitación','pieces_per_unit'=>'piezas por unidad'
        ]);

        $data['active'] = (bool) $request->boolean('active');

        if ($request->hasFile('image')) {
            if ($product->image_path) Storage::disk('public')->delete($product->image_path);
            $data['image_path'] = $request->file('image')->store('products','public');
        }

        $product->update($data);

        return back()->with('status','Producto actualizado');
    }

    public function destroy(Product $product)
    {
        if ($product->image_path) Storage::disk('public')->delete($product->image_path);
        $product->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok'=>true]);
        }
        return redirect()->route('products.index')->with('status','Producto eliminado');
    }

}
