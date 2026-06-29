<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Compatibilidad para rutas viejas tipo /categoria/{slug}.
     * Ahora todo se muestra en el mismo index del catálogo:
     * /catalogo?category=ID
     */
    public function show(Request $request, Category $category)
    {
        $params = [
            'category' => $category->id,
        ];

        if ($request->filled('q')) {
            $params['s'] = $request->get('q');
        }

        if ($request->filled('s')) {
            $params['s'] = $request->get('s');
        }

        if ($request->filled('orden')) {
            $orden = $request->get('orden');

            $params['order'] = match ($orden) {
                'precio_asc' => 'price_asc',
                'precio_desc' => 'price_desc',
                default => 'relevante',
            };
        }

        if ($request->filled('order')) {
            $params['order'] = $request->get('order');
        }

        if ($request->filled('price')) {
            $params['price'] = $request->get('price');
        }

        if ($request->boolean('stock')) {
            $params['stock'] = 1;
        }

        return redirect()->route('web.catalog.index', $params);
    }
}
