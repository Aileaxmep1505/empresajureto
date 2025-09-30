<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Mostrar formulario "Olvidé mi contraseña"
     */
    public function showForgot()
    {
        return view('auth.forgot');
    }

    /**
     * Enviar enlace de restablecimiento al correo
     */
    public function sendResetLink(Request $request)
    {
        // Validación en español
        $rules = ['email' => ['required', 'email']];
        $messages = [
            'required' => 'El campo :attribute es obligatorio.',
            'email'    => 'El campo :attribute debe ser un correo válido.',
        ];
        $attributes = ['email' => 'correo'];

        $data = $request->validate($rules, $messages, $attributes);

        // Envía el enlace (no revela si el correo existe por seguridad)
        $status = Password::sendResetLink(['email' => $data['email']]);

        // Mensajes amigables en español
        $map = [
            Password::RESET_LINK_SENT => 'Te enviamos el enlace para restablecer tu contraseña. Revisa tu correo.',
            Password::INVALID_USER    => 'Si el correo existe, recibirás un enlace para restablecer la contraseña.',
            // Fallback genérico
            'default'                 => 'Si el correo existe, recibirás un enlace para restablecer la contraseña.',
        ];

        return back()->with('status', $map[$status] ?? $map['default']);
    }

    /**
     * Mostrar formulario "Nueva contraseña" (desde el enlace del correo)
     */
    public function showReset(string $token)
    {
        return view('auth.reset', ['token' => $token]);
    }

    /**
     * Guardar nueva contraseña
     */
    public function reset(Request $request)
    {
        // Validación en español
        $rules = [
            'token'                 => ['required'],
            'email'                 => ['required', 'email'],
            'password'              => ['required', 'confirmed', 'min:8'],
        ];
        $messages = [
            'required'       => 'El campo :attribute es obligatorio.',
            'email'          => 'El campo :attribute debe ser un correo válido.',
            'confirmed'      => 'La confirmación de :attribute no coincide.',
            'password.min'   => 'La contraseña debe tener al menos :min caracteres.',
        ];
        $attributes = [
            'token'                 => 'token',
            'email'                 => 'correo',
            'password'              => 'contraseña',
            'password_confirmation' => 'confirmación de contraseña',
        ];

        $request->validate($rules, $messages, $attributes);

        // Intenta restablecer
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password'       => Hash::make($request->input('password')),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        // Mensajes amigables en español (constante corregida)
        $map = [
            Password::PASSWORD_RESET    => 'Tu contraseña ha sido restablecida correctamente. Ya puedes iniciar sesión.',
            Password::INVALID_TOKEN     => 'El enlace para restablecer la contraseña no es válido o ha expirado.',
            Password::INVALID_USER      => 'No pudimos encontrar un usuario con ese correo.',
            Password::RESET_THROTTLED   => 'Has intentado demasiadas veces. Inténtalo de nuevo más tarde.',
            // Fallback genérico
            'default'                   => 'No fue posible restablecer la contraseña. Inténtalo nuevamente.',
        ];

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', $map[$status]);
        }

        // Devuelve con errores bajo el campo email
        $errorMessage = $map[$status] ?? $map['default'];
        return back()->withErrors(['email' => $errorMessage])->withInput();
    }
}
