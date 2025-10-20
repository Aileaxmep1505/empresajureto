<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function show()
    {
        return view('web.contacto');
    }

    public function send(Request $request)
    {
        // --- Validación (mensajes en español) ---
        $messages = [
            'nombre.required'   => 'El nombre es obligatorio.',
            'nombre.string'     => 'El nombre no es válido.',
            'nombre.max'        => 'El nombre no debe superar 100 caracteres.',

            'email.required'    => 'El correo electrónico es obligatorio.',
            'email.email'       => 'El correo electrónico no tiene un formato válido.',
            'email.regex'       => 'El correo debe incluir un dominio válido (por ejemplo: .com, .mx, .org).',

            'telefono.required' => 'El número de teléfono es obligatorio.',
            'telefono.regex'    => 'El número de teléfono debe contener exactamente 10 dígitos.',

            'mensaje.required'  => 'El mensaje es obligatorio.',
            'mensaje.string'    => 'El mensaje no es válido.',
            'mensaje.min'       => 'El mensaje debe tener al menos 10 caracteres.',
        ];

        $data = $request->validate([
            'nombre'   => 'required|string|max:100',
            // email con @ y TLD (.com, .mx, etc.)
            'email'    => ['required','email','regex:/^[^@\s]+@[^@\s]+\.[A-Za-z]{2,}$/'],
            // exactamente 10 dígitos
            'telefono' => ['required','regex:/^\d{10}$/'],
            'mensaje'  => 'required|string|min:10',
        ], $messages);

        // --- Destinatario ---
        // Usa MAIL_TO_ADDRESS; si no existe, cae en MAIL_FROM_ADDRESS
        $to = config('mail.to.address') ?: config('mail.from.address');

        if (empty($to)) {
            // Si no hay destinatario configurado, devolvemos error legible
            return back()
                ->withErrors(['email' => 'No hay destinatario configurado (MAIL_TO_ADDRESS o MAIL_FROM_ADDRESS). Configúralo en el archivo .env.'])
                ->withInput();
        }

        // --- Cuerpo del correo ---
        $body = "Mensaje de contacto:\n"
              . "Nombre: {$data['nombre']}\n"
              . "Correo: {$data['email']}\n"
              . "Teléfono: {$data['telefono']}\n\n"
              . "{$data['mensaje']}";

        try {
            Mail::raw($body, function ($m) use ($data, $to) {
                // FROM debe coincidir con el buzón autenticado (MAIL_FROM_ADDRESS)
                $m->from(config('mail.from.address'), config('mail.from.name'));

                $m->to($to)
                  ->subject('Nuevo mensaje de contacto')
                  ->replyTo($data['email'], $data['nombre']);

                // Opcional: enviar copia al usuario si activas esta bandera en .env
                // MAIL_SEND_COPY_TO_USER=true
                if (config('mail.send_copy_to_user')) {
                    $m->cc($data['email']);
                }
            });
        } catch (\Throwable $e) {
            // Log para depurar en storage/logs/laravel.log
            Log::error('Error enviando correo de contacto', [
                'to'        => $to,
                'from'      => config('mail.from.address'),
                'user_name' => $data['nombre'],
                'user_mail' => $data['email'],
                'error'     => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['email' => 'No se pudo enviar el correo. Revisa la configuración SMTP y los logs.'])
                ->withInput();
        }

        return back()->with('ok', '¡Gracias! Te contactaremos pronto.');
    }
}
