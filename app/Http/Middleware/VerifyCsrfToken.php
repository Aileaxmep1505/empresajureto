<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Si se debe enviar la cookie XSRF-TOKEN en la respuesta.
     *
     * Mantén esto en true para aplicaciones web (SPA/Blade) que lean la cookie.
     *
     * @var bool
     */
    protected $addHttpCookie = true;

    /**
     * URIs que deben excluirse de la verificación CSRF.
     *
     * Puedes usar rutas exactas o patrones con comodines.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Webhook de Stripe (POST)
        'webhooks/stripe',
         'meli/notifications',      // webhook ML

        // Si prefieres excluir todos tus webhooks:
        // 'webhooks/*',
    ];
}
