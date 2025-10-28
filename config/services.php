<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | Aquí guardamos credenciales de servicios de terceros (Mailgun, Postmark,
    | AWS, OpenAI, FacturAPI, etc.). Usa variables de entorno para no exponer
    | claves. Recuerda: en producción con config cache, lee SIEMPRE via config().
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

    // OpenAI
    'openai' => [
        // Usa 'api_key' para que puedas leer con: config('services.openai.api_key')
        'api_key'  => env('OPENAI_API_KEY'),
        'base_uri' => env('OPENAI_API_BASE', 'https://api.openai.com/v1'),
        'model'    => env('OPENAI_MODEL', 'gpt-5'),
        'timeout'  => (int) env('OPENAI_TIMEOUT', 300),
        'retries'  => (int) env('OPENAI_RETRIES', 2),
          'embed_model' => env('OPENAI_EMBED_MODEL','text-embedding-3-small'),
    ],

    // Slack (notificaciones)
    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // FacturaAPI (CFDI 4.0)
    'facturaapi' => [
        'token'    => env('FACTURAAPI_KEY'),
        'base_uri' => rtrim(env('FACTURAAPI_BASE_URI', 'https://www.facturapi.io/v2'), '/'),

        // Controla el timbrado automático al convertir cotización -> venta
        'auto' => (bool) env('FACTURAAPI_AUTO', false),

        // Defaults CFDI
        'serie'             => env('FACT_SERIE', 'A'),
        'tipo_comprobante'  => env('FACT_TIPO_COMPROBANTE', 'I'),
        'moneda'            => env('FACT_MONEDA', 'MXN'),
        'lugar_expedicion'  => env('FACT_LUGAR_EXP', '64000'),
        'metodo_pago'       => env('FACT_METODO_PAGO', 'PPD'),
        'forma_pago'        => env('FACT_FORMA_PAGO', '99'),
        'uso_cfdi'          => env('FACT_USO_CFDI', 'G03'),
    ],
'stripe' => [
        'key'            => env('STRIPE_KEY'),
        'secret'         => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', null),

        // NO usar url() aquí. Usa solo ENV:
        'success_url'    => env('STRIPE_SUCCESS_URL'),
        'cancel_url'     => env('STRIPE_CANCEL_URL'),
    ],
'skydropx' => [
        'base' => env('SKYDROPX_API_BASE', 'https://api.skydropx.com/v2'),
        'key' => env('SKYDROPX_API_KEY'),
    ],
    'copomex' => [
    'token' => env('COPOMEX_TOKEN'), // opcional
],
];
