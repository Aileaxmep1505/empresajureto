<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
   public function index(Request $request)
{
    $q = CatalogItem::published();

    // Búsqueda
    if ($s = trim((string)$request->get('s',''))) {
        $q->where(function($qq) use ($s){
            $qq->where('name','like',"%{$s}%")
               ->orWhere('sku','like',"%{$s}%");
        });
    }

    // Categoría
    if ($cat = $request->get('category')) {
        $q->where('category_id', $cat);
    }

    // Disponibilidad (En stock)
    if ($request->boolean('stock')) {
        $q->where('stock', '>', 0);
    }

    // Precio (usa precio final: sale_price si existe, si no price)
    $priceExpr = "COALESCE(NULLIF(sale_price,0), price)";
    switch ($request->get('price')) {
        case 'lt500':     $q->whereRaw("$priceExpr < 500"); break;
        case '500-2000':  $q->whereRaw("$priceExpr BETWEEN 500 AND 2000"); break;
        case '2000-5000': $q->whereRaw("$priceExpr BETWEEN 2000 AND 5000"); break;
        case 'gt5000':    $q->whereRaw("$priceExpr > 5000"); break;
    }

    // Orden
    $order = $request->get('order','latest');
    if ($order === 'price_asc')      $q->orderBy('sale_price','asc')->orderBy('price','asc');
    elseif ($order === 'price_desc') $q->orderBy('sale_price','desc')->orderBy('price','desc');
    else                             $q->ordered();

    $items = $q->paginate(12)->withQueryString();

    // Categorías para el panel de filtros
    $categories = \App\Models\Category::orderBy('name')->get();

    return view('web.catalog.index', compact('items','categories'));
}

    public function show(CatalogItem $catalogItem)
    {
        abort_unless($catalogItem->status === 1, 404);
        $item = $catalogItem;
        return view('web.catalog.show', compact('item'));
    }
    
}
