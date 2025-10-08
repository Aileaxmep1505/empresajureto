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

 'facturaapi' => [
    'token'     => env('FACTURAAPI_KEY'),
    'base_uri'  => rtrim(env('FACTURAAPI_BASE_URI', 'https://www.facturapi.io/v2'), '/'),
    'auto'      => (bool) env('FACTURAAPI_AUTO', false),

    // Defaults CFDI
    'serie'             => env('FACT_SERIE', 'A'),
    'tipo_comprobante'  => env('FACT_TIPO_COMPROBANTE', 'I'),
    'moneda'            => env('FACT_MONEDA', 'MXN'),
    'lugar_expedicion'  => env('FACT_LUGAR_EXP', '64000'),
    'metodo_pago'       => env('FACT_METODO_PAGO', 'PPD'),
    'forma_pago'        => env('FACT_FORMA_PAGO', '99'),
    'uso_cfdi'          => env('FACT_USO_CFDI', 'G03'),
],


];
