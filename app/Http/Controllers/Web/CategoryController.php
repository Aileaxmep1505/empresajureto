<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\CatalogItem;
use Illuminate\Support\Facades\Schema;

class CategoryController extends Controller
{
    /**
     * Mostrar productos por categoría (/categoria/{slug})
     */
    public function show(Request $request, Category $category)
    {
        // Filtros básicos
        $q       = trim((string)$request->get('q', ''));
        $orderBy = (string)$request->get('orden', 'relevancia'); // relevancia|precio_asc|precio_desc|nuevo
        $min     = $request->filled('min') ? (float)$request->get('min') : null;
        $max     = $request->filled('max') ? (float)$request->get('max') : null;

        $items = CatalogItem::published()
            ->where('category_id', $category->id);

        if ($q !== '') {
            $items->where(function ($qq) use ($q) {
                $qq->where('name', 'like', "%{$q}%")
                   ->orWhere('sku', 'like', "%{$q}%")
                   ->orWhere('excerpt', 'like', "%{$q}%");
            });
        }

        if (!is_null($min)) $items->where('price', '>=', $min);
        if (!is_null($max)) $items->where('price', '<=', $max);

        // Ordenamiento reutilizable
        switch ($orderBy) {
            case 'precio_asc':
                $items->orderBy('price', 'asc');
                break;
            case 'precio_desc':
                $items->orderBy('price', 'desc');
                break;
            case 'nuevo':
                $items->orderByDesc('published_at')->orderByDesc('id');
                break;
            default: // relevancia (por ahora = publicados/recientes/nombre)
                $items->ordered();
        }

        $items = $items->paginate(24)->withQueryString();

        // Para el menú "Principales"
        $primary = Category::primary()->get();

        return view('web.categorias.show', [
            'category' => $category,
            'items'    => $items,
            'primary'  => $primary,
            'q'        => $q,
            'orderBy'  => $orderBy,
            'min'      => $min,
            'max'      => $max,
        ]);
    }
}
