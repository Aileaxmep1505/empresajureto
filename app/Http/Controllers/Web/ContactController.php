<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function show() {
        return view('web.contacto');
    }

    public function send(Request $request) {
        $data = $request->validate([
            'nombre'  => 'required|string|max:100',
            'email'   => 'required|email',
            'mensaje' => 'required|string|min:10',
        ]);

        // Envía correo (configura MAIL_ en .env)
        Mail::raw(
            "Mensaje de contacto:\nNombre: {$data['nombre']}\nEmail: {$data['email']}\n\n{$data['mensaje']}",
            function($m) use ($data) {
                $m->to(config('mail.from.address'))
                  ->subject('Nuevo mensaje de contacto');
            }
        );

        return back()->with('ok','¡Gracias! Te contactaremos pronto.');
    }
}
