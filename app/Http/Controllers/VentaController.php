<?php

namespace App\Http\Controllers;

use App\Models\Venta;

class VentaController extends Controller
{
    public function index()
    {
        $ventas = Venta::with('cliente')->latest()->paginate(12);
        return view('ventas.index', compact('ventas'));
    }

    public function show(Venta $venta)
    {
        $venta->load('cliente','items.producto','plazos','cotizacion');
        return view('ventas.show', compact('venta'));
    }
}
