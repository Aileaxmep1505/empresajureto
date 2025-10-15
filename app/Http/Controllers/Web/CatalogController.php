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

        if ($s = trim((string)$request->get('s',''))) {
            $q->where(function($qq) use ($s){
                $qq->where('name','like',"%{$s}%")
                   ->orWhere('sku','like',"%{$s}%");
            });
        }

        $order = $request->get('order','latest');
        if ($order === 'price_asc')      $q->orderBy('sale_price','asc')->orderBy('price','asc');
        elseif ($order === 'price_desc') $q->orderBy('sale_price','desc')->orderBy('price','desc');
        else                             $q->ordered();

        $items = $q->paginate(12)->withQueryString();

        return view('web.catalog.index', compact('items'));
    }

    public function show(CatalogItem $catalogItem)
    {
        abort_unless($catalogItem->status === 1, 404);
        $item = $catalogItem;
        return view('web.catalog.show', compact('item'));
    }
}
