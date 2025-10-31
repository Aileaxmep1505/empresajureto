<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Mail\VerificationCodeMail;

class AuthController extends Controller
{
    /** Minutos de vigencia del código OTP */
    private int $otpTtlMinutes = 15;

    /** Reintentos permitidos por código */
    private int $otpMaxAttempts = 5;

    /** ===== VISTAS ===== */
    public function showLogin()
    {
        // Un solo login para TODOS
        return view('auth.login');
    }

    public function showRegister()
    {
        // Un solo formulario de registro (tu vista interna existente)
        // Por defecto el registro será de CLIENTE; con el botón del footer (hidden input)
        // se podrá alternar a "interno" sin cambiar de vista.
        return view('auth.register');
    }

    public function dashboard()
    {
        return view('dashboard');
    }

    /** ===== LOGIN ÚNICO ===== */
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

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Credenciales inválidas'])->withInput();
        }

        $request->session()->regenerate();
        /** @var \App\Models\User $user */
        $user = $request->user();

        // 1) Verificación por CÓDIGO (OTP) para TODOS antes de seguir
        if (!$user->hasVerifiedEmail()) {
            if (!$this->otpIsActive($user)) {
                $this->sendOtp($user);
            }
            return redirect()->route('verification.code.show')
                ->with('status', 'Te enviamos un código de verificación a tu correo.');
        }

        // 2) Ramas por tipo de usuario
        if (method_exists($user, 'hasRole') && $user->hasRole('cliente_web')) {
            // Clientes: NO requieren aprobación de admin
            return redirect()->intended(route('customer.welcome'));
        }

        // Personal interno: requiere aprobación del admin
        if (!method_exists($user, 'isApproved') || !$user->isApproved()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Tu cuenta está pendiente de aprobación por un administrador.']);
        }

        return redirect()->intended(route('dashboard'));
    }

    /** ===== REGISTRO ÚNICO (misma vista). Cliente por defecto; Interno con botón ===== */
    public function register(Request $request)
    {
        // Si en el form viene el hidden "registro_tipo=interno", tratamos como interno; si no, cliente.
        $isInternal = in_array(
            strtolower((string) $request->input('registro_tipo', 'cliente')),
            ['interno', 'internal', 'staff'],
            true
        );

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
            'status'   => $isInternal ? 'pending' : 'approved', // interno=pendiente; cliente=aprobado
        ]);

        // Roles por defecto
        if (method_exists($user, 'assignRole')) {
            if ($isInternal) {
                $user->assignRole('user');        // personal interno base
            } else {
                $user->assignRole('cliente_web');  // cliente web
            }
        }

        Auth::login($user);

        // Enviar CÓDIGO OTP por correo
        $this->sendOtp($user);

        return redirect()->route('verification.code.show')
            ->with('status', $isInternal
                ? 'Te enviamos un código. Tras verificar, un admin aprobará tu acceso interno.'
                : 'Te enviamos un código para verificar tu cuenta de cliente.');
    }

    /** ===== LOGOUT ===== */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    /** ====== Verificación por CÓDIGO (OTP) ====== */

    /** Muestra el formulario para capturar el código */
    public function verifyNotice()
    {
        return view('auth.verify-code');
    }

    /** Verifica el código ingresado */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => ['required','digits:6'],
        ], [
            'code.required' => 'Ingresa tu código.',
            'code.digits'   => 'El código debe tener 6 dígitos.',
        ]);

        /** @var User $user */
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login')->withErrors(['email' => 'Inicia sesión para verificar tu correo.']);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->postVerifyRedirect($user);
        }

        if (!$this->otpIsActive($user)) {
            return back()->withErrors(['code' => 'Tu código expiró. Pide uno nuevo.']);
        }

        // Límite de intentos
        if ((int) $user->email_verification_attempts >= $this->otpMaxAttempts) {
            return back()->withErrors(['code' => 'Demasiados intentos. Solicita un nuevo código.']);
        }

        // Hash::check contra el hash guardado
        $plain = (string) $request->input('code');
        if (!Hash::check($plain, (string) $user->email_verification_code_hash)) {
            $user->increment('email_verification_attempts');
            return back()->withErrors(['code' => 'Código incorrecto.']);
        }

        // OK → marcar email verificado y limpiar campos del OTP
        $user->forceFill([
            'email_verified_at'               => now(),
            'email_verification_code_hash'    => null,
            'email_verification_expires_at'   => null,
            'email_verification_code_sent_at' => null,
            'email_verification_attempts'     => 0,
        ])->save();

        return $this->postVerifyRedirect($user);
    }

    /** Reenvía un nuevo código, con throttle básico */
    public function resendVerificationCode(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login')->withErrors(['email' => 'Inicia sesión para solicitar un código.']);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->postVerifyRedirect($user);
        }

        // throttle simple: no más de 1 cada 60s
        if ($user->email_verification_code_sent_at && now()->diffInSeconds($user->email_verification_code_sent_at) < 60) {
            $remaining = 60 - now()->diffInSeconds($user->email_verification_code_sent_at);
            return back()->withErrors(['code' => "Espera {$remaining}s para solicitar otro código."]);
        }

        $this->sendOtp($user, true);

        return back()->with('status', 'Te enviamos un nuevo código a tu correo.');
    }

    /** ===== Helpers de OTP y redirecciones ===== */

    private function postVerifyRedirect(User $user)
    {
        // Tras verificar correo:
        if (method_exists($user, 'hasRole') && $user->hasRole('cliente_web')) {
            // Cliente web: queda dentro
            return redirect()->route('customer.welcome')
                ->with('status', 'Correo verificado. ¡Bienvenido!');
        }

        // Personal interno: aún requiere aprobación -> cerrar sesión y avisar
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('login')
            ->with('status', 'Correo verificado. Tu cuenta está pendiente de aprobación por un administrador.');
    }

    private function sendOtp(User $user, bool $isResend = false): void
    {
        // Genera código de 6 dígitos
        $code = (string) random_int(100000, 999999);

        // Guarda hash y expiración
        $user->forceFill([
            'email_verification_code_hash'    => Hash::make($code),
            'email_verification_expires_at'   => now()->addMinutes($this->otpTtlMinutes),
            'email_verification_code_sent_at' => now(),
            'email_verification_attempts'     => 0,
        ])->save();

        // Envía correo
        try {
            Mail::to($user->email)->send(new VerificationCodeMail($code, $this->otpTtlMinutes));
        } catch (\Throwable $e) {
            Log::error('Error enviando OTP por correo: '.$e->getMessage());
        }

        // DEBUG local (no en producción)
        if (config('app.env') !== 'production') {
            Log::info("OTP para {$user->email}: {$code}");
        }
    }

    private function otpIsActive(User $user): bool
    {
        return $user->email_verification_code_hash
            && $user->email_verification_expires_at
            && now()->lt(Carbon::parse($user->email_verification_expires_at));
    }
}
