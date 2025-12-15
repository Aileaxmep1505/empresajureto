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
    // ==========================
    // Credenciales
    // ==========================
    'api_key' => env('OPENAI_API_KEY'),

    // Base URL SIEMPRE sin /v1
    'base_url' => rtrim(env('OPENAI_BASE_URL', 'https://api.openai.com'), '/'),

    // Compatibilidad legacy (solo si algún código viejo lo usa)
    'base_uri' => rtrim(env('OPENAI_API_BASE', 'https://api.openai.com/v1'), '/'),

    // Opcionales (solo si usas múltiples orgs/proyectos)
    'org_id'     => env('OPENAI_ORG_ID'),
    'project_id' => env('OPENAI_PROJECT_ID'),

    // ==========================
    // Selección de modelos (GPT-5 ONLY ✅)
    // Confirmado por openai:check
    // ==========================
    // Modelo principal para análisis pesado / PDFs
    'primary' => env('OPENAI_PRIMARY_MODEL', 'gpt-5-2025-08-07'),

    // Fallback seguro (chat, estable)
    'fallbacks' => array_filter(array_map(
        'trim',
        explode(',', env('OPENAI_FALLBACK_MODELS', 'gpt-5-chat-latest'))
    )),

    // Legacy (para código viejo que lea services.openai.model)
    'model' => env('OPENAI_PRIMARY_MODEL', 'gpt-5-2025-08-07'),

    // Modelo barato/rápido para reparaciones de JSON
    'json_repair_model' => env('OPENAI_JSON_REPAIR_MODEL', 'gpt-5-mini'),

    // ==========================
    // Timeouts y reintentos
    // ==========================
    'timeout'               => (int) env('OPENAI_TIMEOUT', 300),
    'connect_timeout'       => (int) env('OPENAI_CONNECT_TIMEOUT', 30),
    'max_retries_per_model' => (int) env('OPENAI_RETRIES_PER_MODEL', 3),
    'retry_base_delay_ms'   => (int) env('OPENAI_RETRY_BASE_MS', 400),
    'max_total_attempts'    => (int) env('OPENAI_MAX_TOTAL_ATTEMPTS', 8),

    // ==========================
    // Embeddings (actual)
    // ==========================
    'embed_model' => env('OPENAI_EMBED_MODEL', 'text-embedding-3-large'),
],


    // Slack (notificaciones)
    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

   // === FACTURACIÓN: Sitio web (checkout) ===
    'facturaapi_web' => [
        'key'        => env('FACTURAAPI_WEB_KEY'),
        'base_uri'   => rtrim(env('FACTURAAPI_WEB_BASE_URI', 'https://www.facturapi.io/v2'), '/'),
        'series'     => env('FACT_WEB_SERIE', 'F'),
        'metodo'     => env('FACT_WEB_METODO_PAGO', 'PUE'), // payment_method
        'forma'      => env('FACT_WEB_FORMA_PAGO', '04'),   // payment_form
        'uso'        => env('FACT_WEB_USO_CFDI', 'G03'),    // use
    ],

    // === FACTURACIÓN: Sistema interno (backoffice) ===
    'facturaapi_internal' => [
        'key'              => env('FACTURAAPI_INT_KEY'),
        'base_uri'         => rtrim(env('FACTURAAPI_INT_BASE_URI', 'https://www.facturapi.io/v2'), '/'),
        'auto'             => (bool) env('FACT_INT_AUTO', false),
        'serie'            => env('FACT_INT_SERIE', 'A'),
        'tipo'             => env('FACT_INT_TIPO_COMPROBANTE', 'I'),
        'moneda'           => env('FACT_INT_MONEDA', 'MXN'),
        'lugar_expedicion' => env('FACT_INT_LUGAR_EXP', '64000'), // opcional (v2 usa "branch")
        'metodo'           => env('FACT_INT_METODO_PAGO', 'PPD'),
        'forma'            => env('FACT_INT_FORMA_PAGO', '99'),
        'uso'              => env('FACT_INT_USO_CFDI', 'G03'),
        'disk'             => env('FACTURAAPI_INT_DISK', 'public'),
        // Si te interesa default de "regimen" receptor cuando no venga en cliente:
        'regimen_default'  => '601',
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
'osrm' => [
    'base' => env('OSRM_BASE_URL', 'http://localhost:5000'),
],
'traffic' => [
    'provider' => env('TRAFFIC_PROVIDER', null), // 'google' | 'here' | null
    'api_key'  => env('TRAFFIC_API_KEY', null),
],
'ai' => [
    'enabled' => env('AI_ENABLED', false),
    'model'   => env('AI_MODEL', 'gpt-4o-mini'),
],
'meli' => [
    'client_id'     => env('MELI_CLIENT_ID'),
    'client_secret' => env('MELI_CLIENT_SECRET'),
    'redirect'      => env('MELI_REDIRECT'),
    'sandbox'       => (bool) env('MELI_SANDBOX', true),
],
'whatsapp' => [
    'version' => env('WHATSAPP_API_VERSION', 'v21.0'),
    'phone_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'token' => env('WHATSAPP_ACCESS_TOKEN'),
    'template_agenda' => env('WHATSAPP_TEMPLATE_AGENDA', 'agenda_recordatorio'),
],
'ilovepdf' => [
    'public_key' => env('ILOVEPDF_PUBLIC_KEY'),
    'secret_key' => env('ILOVEPDF_SECRET_KEY'),
],


];
