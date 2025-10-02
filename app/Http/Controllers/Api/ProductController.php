<?php

// app/Http/Controllers/ProductController.php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $r)
    {
        $q = Product::query();

        // búsqueda
        if ($s = $r->get('q')) {
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%$s%")
                  ->orWhere('sku', 'like', "%$s%")
                  ->orWhere('brand', 'like', "%$s%");
            });
        }

        // categoría (ajusta campo real: category_slug / category_id)
        if ($cat = $r->get('category')) {
            $q->where('category_slug', $cat);
        }

        // rango de precio
        if ($min = $r->get('min_price')) $q->where('price', '>=', (float)$min);
        if ($max = $r->get('max_price')) $q->where('price', '<=', (float)$max);

        // orden
        switch ($r->get('sort')) {
            case 'newest':     $q->latest(); break;
            case 'price_asc':  $q->orderBy('price'); break;
            case 'price_desc': $q->orderByDesc('price'); break;
            default: /* relevancia / por defecto */ break;
        }

        $per = (int) $r->get('per_page', 24);

        // IMPORTANTE: paginate() devuelve { data, links, meta }
        return $q->paginate($per);
    }
}
