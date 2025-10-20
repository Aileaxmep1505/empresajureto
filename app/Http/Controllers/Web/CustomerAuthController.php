<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerAuthController extends Controller
{
    public function showLogin()
    {
        return view('web.auth.login');
    }

    public function showRegister()
    {
        return view('web.auth.register');
    }

    public function login(Request $request)
    {
        $creds = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::guard('customer')->attempt($creds, $request->boolean('remember'))) {
            $request->session()->regenerate();
            // Llevar SIEMPRE a la pantalla de bienvenida del cliente
            return redirect()->route('customer.welcome');
        }

        return back()
            ->withErrors(['email' => 'Credenciales inválidas'])
            ->onlyInput('email');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'nombre'    => 'required|string|max:100',
            'apellido'  => 'nullable|string|max:100',
            'email'     => 'required|email|unique:customers,email',
            'password'  => 'required|string|min:6|confirmed',
        ]);

        $customer = Customer::create([
            'nombre'   => $data['nombre'],
            'apellido' => $data['apellido'] ?? null,
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        // Tras registro, ir a la bienvenida
        return redirect()->route('customer.welcome');
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('web.home');
    }

    // Pantalla de bienvenida del cliente (saludo + resumen)
    public function welcome()
    {
        $customer = Auth::guard('customer')->user();
        $cart = session('cart', []); // si manejas carrito en sesión

        return view('web.customer.welcome', compact('customer', 'cart'));
    }
}
