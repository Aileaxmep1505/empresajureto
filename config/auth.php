<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | Define el guard por defecto y el broker de contraseñas. Tu sistema
    | interno seguirá usando el guard "web" y la web pública podrá usar
    | el guard "customer" sin conflictos de sesión.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Aquí definimos los guards. "web" es tu sistema interno (usuarios staff)
    | y "customer" es para clientes de la web pública. Ambos usan sesión,
    | pero cada uno con su propio provider y cookies separadas.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',      // interno
        ],

        'customer' => [
            'driver'   => 'session',
            'provider' => 'customers',  // público (sitio 100% Laravel)
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | Providers que indican cómo obtener los usuarios. "users" apunta a tu
    | modelo App\Models\User (interno) y "customers" a App\Models\Customer
    | (público). Puedes sobreescribir via variables de entorno si gustas.
    |
    | Supported: "eloquent", "database"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => env('AUTH_MODEL', App\Models\User::class),
        ],

        'customers' => [
            'driver' => 'eloquent',
            'model'  => env('AUTH_CUSTOMER_MODEL', App\Models\Customer::class),
        ],

        // Ejemplo con database driver (no usado por defecto):
        // 'users' => [
        //     'driver' => 'database',
        //     'table'  => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | Opciones para restablecimiento de contraseñas. Puedes tener brokers
    | separados para "users" y "customers". Por defecto ambos usan la misma
    | tabla "password_reset_tokens". Ajusta "expire" y "throttle" a tu gusto.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],

        'customers' => [
            'provider' => 'customers',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Segundos antes de que caduque la confirmación de contraseña (3 horas).
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
