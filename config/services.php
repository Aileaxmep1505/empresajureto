<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | Aquí guardamos credenciales de servicios de terceros (Mailgun, Postmark,
    | AWS, FacturAPI, etc.). Usa variables de entorno para no exponer claves.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // OpenAI (si lo usas)
    'openai' => [
        'key'   => env('OPENAI_API_KEY'),
        'base'  => env('OPENAI_API_BASE', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-5'),
    ],

    // Slack (notificaciones)
    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // ===== FacturAPI (CFDI 4.0) =====
    'facturapi' => [
        // Coloca tu clave en .env: FACTURAPI_KEY=sk_test_xxx (o sk_live_xxx en producción)
        'key' => env('FACTURAPI_KEY'),
    ],

];
