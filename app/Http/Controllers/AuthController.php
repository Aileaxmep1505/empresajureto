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
    /** Minutos de vigencia del cÃ³digo OTP */
    private int $otpTtlMinutes = 15;

    /** Reintentos permitidos por cÃ³digo */
    private int $otpMaxAttempts = 5;

    /** ===== VISTAS ===== */
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function dashboard()
    {
        return view('dashboard');
    }

    /** ===== LOGIN ÃšNICO ===== */
    public function login(Request $request)
    {
        $rules = [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
        $messages = [
            'required' => 'El campo :attribute es obligatorio.',
            'email'    => 'El campo :attribute debe ser un correo vÃ¡lido.',
        ];
        $attributes = ['email' => 'correo', 'password' => 'contraseÃ±a'];

        $credentials = $request->validate($rules, $messages, $attributes);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Credenciales invÃ¡lidas'])->withInput();
        }

        $request->session()->regenerate();

        /** @var \App\Models\User $user */
        $user = $request->user();

        // 1) OTP para TODOS antes de seguir (si aÃºn no verifica email)
        if (!$user->hasVerifiedEmail()) {
            if (!$this->otpIsActive($user)) {
                $this->sendOtp($user);
            }
            return redirect()
                ->route('verification.code.show')
                ->with('status', 'Te enviamos un cÃ³digo de verificaciÃ³n a tu correo.');
        }

        // 2) Ramas por tipo de usuario
        if (method_exists($user, 'hasRole') && $user->hasRole('cliente_web')) {
            return redirect()->intended(route('customer.welcome'));
        }

        // Personal interno: requiere aprobaciÃ³n admin
        if (!method_exists($user, 'isApproved') || !$user->isApproved()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Tu cuenta estÃ¡ pendiente de aprobaciÃ³n por un administrador.']);
        }

        return redirect()->intended(route('dashboard'));
    }

    /** ===== REGISTRO ÃšNICO ===== */
    public function register(Request $request)
    {
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
            'email'      => 'El campo :attribute debe ser un correo vÃ¡lido.',
            'max.string' => 'El campo :attribute no debe exceder :max caracteres.',
            'min.string' => 'El campo :attribute debe tener al menos :min caracteres.',
            'unique'     => 'El :attribute ya estÃ¡ registrado.',
            'confirmed'  => 'La confirmaciÃ³n de :attribute no coincide.',
        ];
        $attributes = [
            'name' => 'nombre',
            'email' => 'correo',
            'password' => 'contraseÃ±a',
            'password_confirmation' => 'confirmaciÃ³n de contraseÃ±a',
        ];

        $data = $request->validate($rules, $messages, $attributes);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'status'   => $isInternal ? 'pending' : 'approved',
        ]);

        if (method_exists($user, 'assignRole')) {
            if ($isInternal) {
                $user->assignRole('user');
            } else {
                $user->assignRole('cliente_web');
            }
        }

        Auth::login($user);

        $this->sendOtp($user);

        return redirect()
            ->route('verification.code.show')
            ->with('status', $isInternal
                ? 'Te enviamos un cÃ³digo. Tras verificar, un admin aprobarÃ¡ tu acceso interno.'
                : 'Te enviamos un cÃ³digo para verificar tu cuenta de cliente.');
    }

    /** ===== LOGOUT ===== */
    public function logout(Request $request)
    {
        $cart = $request->session()->get('cart', []);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if (!empty($cart)) {
            $request->session()->put('cart', $cart);
        }

        return redirect()->route('login');
    }

    /** ====== OTP ====== */

    /** Form para capturar cÃ³digo */
    public function verifyNotice()
    {
        return view('auth.verify-code');
    }

    /** Verifica el cÃ³digo */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => ['required','digits:6'],
        ], [
            'code.required' => 'Ingresa tu cÃ³digo.',
            'code.digits'   => 'El cÃ³digo debe tener 6 dÃ­gitos.',
        ]);

        /** @var User $user */
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login')->withErrors(['email' => 'Inicia sesiÃ³n para verificar tu correo.']);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->postVerifyRedirect($user);
        }

        if (!$this->otpIsActive($user)) {
            return back()->withErrors(['code' => 'Tu cÃ³digo expirÃ³. Pide uno nuevo.']);
        }

        if ((int) $user->email_verification_attempts >= $this->otpMaxAttempts) {
            return back()->withErrors(['code' => 'Demasiados intentos. Solicita un nuevo cÃ³digo.']);
        }

        $plain = (string) $request->input('code');
        if (!Hash::check($plain, (string) $user->email_verification_code_hash)) {
            $user->increment('email_verification_attempts');
            return back()->withErrors(['code' => 'CÃ³digo incorrecto.']);
        }

        $user->forceFill([
            'email_verified_at'               => now(),
            'email_verification_code_hash'    => null,
            'email_verification_expires_at'   => null,
            'email_verification_code_sent_at' => null,
            'email_verification_attempts'     => 0,
        ])->save();

        return $this->postVerifyRedirect($user);
    }

    /** ReenvÃ­a OTP */
    public function resendVerificationCode(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login')->withErrors(['email' => 'Inicia sesiÃ³n para solicitar un cÃ³digo.']);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->postVerifyRedirect($user);
        }

        // throttle simple: 1 cada 60s
        if ($user->email_verification_code_sent_at && now()->diffInSeconds($user->email_verification_code_sent_at) < 60) {
            $remaining = 60 - now()->diffInSeconds($user->email_verification_code_sent_at);
            return back()->withErrors(['code' => "Espera {$remaining}s para solicitar otro cÃ³digo."]);
        }

        $this->sendOtp($user, true);

        return back()->with('status', 'Te enviamos un nuevo cÃ³digo a tu correo.');
    }

    /** ===== Helpers ===== */

    private function postVerifyRedirect(User $user)
    {
        if (method_exists($user, 'hasRole') && $user->hasRole('cliente_web')) {
            return redirect()->route('customer.welcome')->with('status', 'Correo verificado. Â¡Bienvenido!');
        }

        // Personal interno: aÃºn requiere aprobaciÃ³n
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'Correo verificado. Tu cuenta estÃ¡ pendiente de aprobaciÃ³n por un administrador.');
    }

    private function sendOtp(User $user, bool $isResend = false): void
    {
        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'email_verification_code_hash'    => Hash::make($code),
            'email_verification_expires_at'   => now()->addMinutes($this->otpTtlMinutes),
            'email_verification_code_sent_at' => now(),
            'email_verification_attempts'     => 0,
        ])->save();

        try {
            Mail::to($user->email)->send(new VerificationCodeMail($code, $this->otpTtlMinutes));
        } catch (\Throwable $e) {
            Log::error('Error enviando OTP por correo: '.$e->getMessage());
        }

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
