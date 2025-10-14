<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Producto; // asumiendo que ya existe
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request) {
        $q = $request->get('q');
        $productos = Producto::query()
            ->when($q, fn($qq) => $qq->where('name','like',"%{$q}%"))
            ->orderBy('created_at','desc')
            ->paginate(12)
            ->withQueryString();

        return view('web.ventas.index', compact('productos','q'));
    }

    public function show($id) {
        $producto = Producto::findOrFail($id);
        return view('web.ventas.show', compact('producto'));
    }
}
