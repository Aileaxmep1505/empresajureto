<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    |
    | Normalmente basta con exponer CORS para tus endpoints de API y, si usas
    | Sanctum con cookies, el endpoint de CSRF.
    |
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        // 'login', 'logout', 'user' // agrega si expones auth por web
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Methods
    |--------------------------------------------------------------------------
    */

    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Usa la variable de entorno CORS_ALLOWED_ORIGINS separada por comas.
    | Ejemplos:
    |  - CORS_ALLOWED_ORIGINS=*
    |  - CORS_ALLOWED_ORIGINS=http://localhost:5173,http://127.0.0.1:5173,https://tu-front.com
    |
    | Nota: si supports_credentials=true, NO puedes usar "*" aquí; debes
    | listar dominios exactos con esquema y puerto.
    */

    'allowed_origins' => array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', '*')))),

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins Patterns
    |--------------------------------------------------------------------------
    |
    | Déjalo vacío en la mayoría de casos. Útil para subdominios dinámicos:
    |  'allowed_origins_patterns' => ['#^https?://.+\.tu-dominio\.com$#'],
    */

    'allowed_origins_patterns' => [],

    /*
    |--------------------------------------------------------------------------
    | Allowed Headers
    |--------------------------------------------------------------------------
    */

    'allowed_headers' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Exposed Headers
    |--------------------------------------------------------------------------
    */

    'exposed_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Max Age
    |--------------------------------------------------------------------------
    |
    | Tiempo (en segundos) que el navegador puede cachear la respuesta CORS
    | del preflight OPTIONS.
    */

    'max_age' => 0,

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    |
    | Ponlo en true SOLO si vas a usar cookies/sesión (Sanctum) desde el front.
    | Si lo activas, recuerda:
    |  - 'allowed_origins' NO puede ser "*"
    |  - Configura SANCTUM_STATEFUL_DOMAINS y SESSION_DOMAIN
    */

    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', false),

];
