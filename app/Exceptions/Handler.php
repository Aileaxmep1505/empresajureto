<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Redirección cuando NO está autenticado.
     *
     * Por defecto, Laravel manda a route('login'). Aquí forzamos que si el guard
     * involucrado es 'customer' te lleve a route('customer.login') en vez del login interno.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // Si la petición espera JSON, responde 401 como siempre.
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // ¿El fallo fue en el guard 'customer'? -> login de clientes
        if (in_array('customer', $exception->guards(), true)) {
            return redirect()->guest(route('customer.login'));
        }

        // Cualquier otro caso -> login interno (guard web)
        return redirect()->guest(route('login'));
    }
}
