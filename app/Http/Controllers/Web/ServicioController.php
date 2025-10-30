<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class ServicioController extends Controller
{
    public function index()
    {
        $waPhone = preg_replace('/\D+/', '', env('WHATSAPP_PHONE','5215555555555'));
        // No mandamos $features para que la vista use sus defaults con URLs externas
        return view('web.servicios', compact('waPhone'));
    }
}
