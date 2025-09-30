<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function login(Request $request)
    {
        // Reglas, mensajes y nombres de atributos en español
        $rules = [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ];

        $messages = [
            'required'      => 'El campo :attribute es obligatorio.',
            'email'         => 'El campo :attribute debe ser un correo válido.',
            'password.min'  => 'La contraseña debe tener al menos :min caracteres.',
        ];

        $attributes = [
            'email'    => 'correo',
            'password' => 'contraseña',
        ];

        $credentials = $request->validate($rules, $messages, $attributes);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Credenciales inválidas'])
                ->withInput();
        }

        $request->session()->regenerate();
        $user = $request->user();

        // 1) Si NO ha verificado su correo: mantenerlo autenticado y llevarlo a la vista de verificación
        if (!$user->hasVerifiedEmail()) {
            try { $user->sendEmailVerificationNotification(); } catch (\Throwable $e) {}
            return redirect()
                ->route('verification.notice')
                ->with('status', 'Debes verificar tu correo. Te reenviamos el enlace.');
        }

        // 2) Si no está aprobado por admin: bloquear acceso (lo mantenemos logueado para que pueda ver la notificación)
        if (!method_exists($user, 'isApproved') || !$user->isApproved()) {
            return back()
                ->withErrors(['email' => 'Tu cuenta está pendiente de aprobación por un administrador.'])
                ->withInput();
        }

        return redirect()->intended(route('dashboard'));
    }

    public function register(Request $request)
    {
        // Reglas, mensajes y atributos en español
        $rules = [
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'confirmed', 'min:8'],
        ];

        $messages = [
            'required'                => 'El campo :attribute es obligatorio.',
            'email'                   => 'El campo :attribute debe ser un correo válido.',
            'max.string'              => 'El campo :attribute no debe exceder :max caracteres.',
            'min.string'              => 'El campo :attribute debe tener al menos :min caracteres.',
            'unique'                  => 'El :attribute ya está registrado.',
            'confirmed'               => 'La confirmación de :attribute no coincide.',
            'password.min'            => 'La contraseña debe tener al menos :min caracteres.',
        ];

        $attributes = [
            'name'                  => 'nombre',
            'email'                 => 'correo',
            'password'              => 'contraseña',
            'password_confirmation' => 'confirmación de contraseña',
        ];

        $data = $request->validate($rules, $messages, $attributes);

        // Crear usuario en estado pendiente (sin acceso hasta aprobación admin)
        $user = User::create([
            'name'       => $data['name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'status'     => 'pending',
        ]);

        // Rol por defecto (si usas Spatie)
        if (method_exists($user, 'assignRole')) {
            $user->assignRole('user');
        }

        // Autenticar para permitir visitar /email/verify
        Auth::login($user);

        // Disparar evento -> envía email de verificación
        event(new Registered($user));

        // Vista de “verifica tu correo”
        return redirect()->route('verification.notice')
            ->with('status', 'Te enviamos un enlace para verificar tu correo. Revisa tu bandeja de entrada.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function dashboard()
    {
        return view('dashboard');
    }

    // ====== Flujo de verificación de email (Laravel) ======

    // Vista "verifica tu correo"
    public function verifyNotice()
    {
        return view('auth.verify-email'); // crea esta vista con tu UI (ring)
    }

    // Endpoint que confirma el correo (enlace del email)
    public function verifyEmail(EmailVerificationRequest $request)
    {
        $request->fulfill(); // marca email_verified_at

        return redirect()->route('login')
            ->with('status', 'Correo verificado. Ahora espera la aprobación del administrador.');
    }

    // Reenviar enlace de verificación
    public function resendVerification(Request $request)
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->user()?->sendEmailVerificationNotification();

        return back()->with('status', 'Te enviamos un nuevo enlace de verificación.');
    }
}
