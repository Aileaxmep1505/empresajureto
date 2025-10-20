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
        // Vista única de login para todos (clientes y personal)
        return view('auth.login');
    }

    public function showRegister()
    {
        // Registro SOLO de personal interno (empleados/admin)
        return view('auth.register');
    }

    public function login(Request $request)
    {
        $rules = [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
        $messages = [
            'required'     => 'El campo :attribute es obligatorio.',
            'email'        => 'El campo :attribute debe ser un correo válido.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
        ];
        $attributes = ['email' => 'correo', 'password' => 'contraseña'];

        $credentials = $request->validate($rules, $messages, $attributes);

        // ÚNICO guard: web
        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Credenciales inválidas'])->withInput();
        }

        $request->session()->regenerate();
        $user = $request->user();

        // === Un solo login: redirección por rol ===
        if (method_exists($user, 'hasRole') && $user->hasRole('cliente_web')) {
            // Los “clientes” entran con el mismo guard `web`
            // Respeta intended (por ejemplo si venían de /checkout/start)
            return redirect()->intended(route('customer.welcome'));
        }

        // Personal interno: verificación y aprobación
        if (!$user->hasVerifiedEmail()) {
            try { $user->sendEmailVerificationNotification(); } catch (\Throwable $e) {}
            return redirect()->route('verification.notice')
                ->with('status', 'Debes verificar tu correo. Te reenviamos el enlace.');
        }

        if (!method_exists($user, 'isApproved') || !$user->isApproved()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Tu cuenta está pendiente de aprobación por un administrador.']);
        }

        // OK → dashboard interno (o intended)
        return redirect()->intended(route('dashboard'));
    }

    public function register(Request $request)
    {
        // Registro SOLO de personal interno (NO clientes)
        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ];
        $messages = [
            'required'   => 'El campo :attribute es obligatorio.',
            'email'      => 'El campo :attribute debe ser un correo válido.',
            'max.string' => 'El campo :attribute no debe exceder :max caracteres.',
            'min.string' => 'El campo :attribute debe tener al menos :min caracteres.',
            'unique'     => 'El :attribute ya está registrado.',
            'confirmed'  => 'La confirmación de :attribute no coincide.',
        ];
        $attributes = [
            'name' => 'nombre', 'email' => 'correo',
            'password' => 'contraseña', 'password_confirmation' => 'confirmación de contraseña',
        ];

        $data = $request->validate($rules, $messages, $attributes);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'status'   => 'pending', // interno pendiente de aprobación
        ]);

        // if (method_exists($user, 'assignRole')) { $user->assignRole('empleado'); }

        Auth::login($user);
        event(new Registered($user));

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

    // ====== Verificación de email ======
    public function verifyNotice()
    {
        return view('auth.verify-email');
    }

    public function verifyEmail(EmailVerificationRequest $request)
    {
        $request->fulfill();
        return redirect()->route('login')
            ->with('status', 'Correo verificado. Ahora espera la aprobación del administrador.');
    }

    public function resendVerification(Request $request)
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }
        $request->user()?->sendEmailVerificationNotification();
        return back()->with('status', 'Te enviamos un nuevo enlace de verificación.');
    }
}
