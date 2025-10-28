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
    // === Credenciales ===
    'api_key' => env('OPENAI_API_KEY'),

    // Usa base_url SIN /v1 si tu servicio concatena el path (/v1/chat/completions)
    'base_url'  => rtrim(env('OPENAI_BASE_URL', 'https://api.openai.com'), '/'),

    // Compatibilidad con código antiguo que espera /v1 en la base
    'base_uri'  => rtrim(env('OPENAI_API_BASE', 'https://api.openai.com/v1'), '/'),

    // Opcionales (Organizations / Projects)
    'org_id'     => env('OPENAI_ORG_ID'),
    'project_id' => env('OPENAI_PROJECT_ID'),

    // === Selección de modelos con Fallback ===
    // El servicio usará 'primary' y 'fallbacks'. Se conserva 'model' por compatibilidad.
    'primary'   => env('OPENAI_PRIMARY_MODEL', env('OPENAI_MODEL', 'gpt-5')),
    'fallbacks' => array_filter(array_map('trim', explode(',', env('OPENAI_FALLBACK_MODELS', 'gpt-4o,gpt-4o-mini')))),
    'model'     => env('OPENAI_MODEL', 'gpt-5'), // legacy

    // === Timeouts y reintentos (para 429/5xx) ===
    'timeout'              => (int) env('OPENAI_TIMEOUT', 30),
    'connect_timeout'      => (int) env('OPENAI_CONNECT_TIMEOUT', 10),
    'max_retries_per_model'=> (int) env('OPENAI_RETRIES_PER_MODEL', 2),
    'retry_base_delay_ms'  => (int) env('OPENAI_RETRY_BASE_MS', 400),
    'max_total_attempts'   => (int) env('OPENAI_MAX_TOTAL_ATTEMPTS', 6),

    // === Embeddings ===
    'embed_model' => env('OPENAI_EMBED_MODEL', 'text-embedding-3-small'),
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
'skydropx_pro' => [
        'client_id'  => env('SKYDROPX_CLIENT_ID'),
        'secret'     => env('SKYDROPX_CLIENT_SECRET'),
        'token_url'  => env('SKYDROPX_PRO_TOKEN_URL'),
        'api_base'   => env('SKYDROPX_PRO_API_BASE', 'https://sb-pro.skydropx.com/api/v1'),
        'scope'      => env('SKYDROPX_SCOPE', ''),
        'origin_cp'  => env('ORIGIN_POSTAL_CODE', '52060'),
        'free_ship'  => (float) env('FREE_SHIPPING_THRESHOLD', 5000.00),
    ],
    'copomex' => [
    'token' => env('COPOMEX_TOKEN'), // opcional
],
];
