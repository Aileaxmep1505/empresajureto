<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | Aquí guardamos credenciales de servicios de terceros (Mailgun, Postmark,
    | AWS, OpenAI, FacturAPI, etc.). Usa variables de entorno para no exponer
    | claves. Recuerda: en producción con config cache, consume SIEMPRE via config().
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

   /*
|--------------------------------------------------------------------------
| OpenAI
|--------------------------------------------------------------------------
*/
'openai' => [

    /*
    |--------------------------------------------------------------------------
    | Credenciales
    |--------------------------------------------------------------------------
    */
    'api_key' => env('OPENAI_API_KEY'),

    // Base URL SIEMPRE sin /v1
    'base_url' => rtrim(env('OPENAI_BASE_URL', 'https://api.openai.com'), '/'),

    // Compatibilidad legacy (solo si algún código viejo lo usa)
    'base_uri' => rtrim(env('OPENAI_API_BASE', 'https://api.openai.com/v1'), '/'),

    // Opcionales (solo si usas múltiples orgs/proyectos)
    'org_id'     => env('OPENAI_ORG_ID'),
    'project_id' => env('OPENAI_PROJECT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Modelos (Configuración MÁXIMA PRECISIÓN para extracción de documentos)
    |--------------------------------------------------------------------------
    */

    // 🥇 Modelo principal (máxima exactitud para PDFs/facturas)
    'primary' => env('OPENAI_PRIMARY_MODEL', 'gpt-5-2025-08-07'),

    // 🥈 Fallbacks automáticos (en orden de prioridad)
    'fallbacks' => array_values(array_filter(array_map(
        'trim',
        explode(',', env(
            'OPENAI_FALLBACK_MODELS',
            'gpt-4.1,gpt-5-chat-latest,gpt-4o'
        ))
    ))),

    // Compatibilidad legacy
    'model' => env('OPENAI_PRIMARY_MODEL', 'gpt-5-2025-08-07'),

    // Modelo barato para reparación de JSON inválido
    'json_repair_model' => env('OPENAI_JSON_REPAIR_MODEL', 'gpt-5-mini'),

    /*
    |--------------------------------------------------------------------------
    | Timeouts y Reintentos (importante para PDFs grandes/multi-hoja)
    |--------------------------------------------------------------------------
    */

    // Tiempo máximo total de espera (segundos)
    'timeout' => (int) env('OPENAI_TIMEOUT', 300),

    // Tiempo máximo de conexión
    'connect_timeout' => (int) env('OPENAI_CONNECT_TIMEOUT', 30),

    // Reintentos por modelo antes de pasar al siguiente fallback
    'max_retries_per_model' => (int) env('OPENAI_RETRIES_PER_MODEL', 3),

    // Delay base entre reintentos (ms)
    'retry_base_delay_ms' => (int) env('OPENAI_RETRY_BASE_MS', 400),

    // Máximo total de intentos combinando todos los modelos
    'max_total_attempts' => (int) env('OPENAI_MAX_TOTAL_ATTEMPTS', 8),

    /*
    |--------------------------------------------------------------------------
    | Embeddings
    |--------------------------------------------------------------------------
    */
    'embed_model' => env('OPENAI_EMBED_MODEL', 'text-embedding-3-large'),
],


    /*
    |--------------------------------------------------------------------------
    | Slack
    |--------------------------------------------------------------------------
    */
    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Facturapi (CFDI 4.0)
    |--------------------------------------------------------------------------
    */

    // === FACTURACIÓN: Sitio web (checkout) ===
    'facturaapi_web' => [
        'key'      => env('FACTURAAPI_WEB_KEY'),
        'base_uri' => rtrim(env('FACTURAAPI_WEB_BASE_URI', 'https://www.facturapi.io/v2'), '/'),

        'serie'  => env('FACT_WEB_SERIE', 'F'),
        'metodo' => env('FACT_WEB_METODO_PAGO', 'PUE'), // payment_method
        'forma'  => env('FACT_WEB_FORMA_PAGO', '04'),   // payment_form
        'uso'    => env('FACT_WEB_USO_CFDI', 'G03'),    // use
    ],

    // === FACTURACIÓN: Sistema interno (backoffice) ===
    'facturaapi_internal' => [
        'key'      => env('FACTURAAPI_INT_KEY'),
        'base_uri' => rtrim(env('FACTURAAPI_INT_BASE_URI', 'https://www.facturapi.io/v2'), '/'),

        'auto'             => filter_var(env('FACT_INT_AUTO', false), FILTER_VALIDATE_BOOL),
        'serie'            => env('FACT_INT_SERIE', 'A'),
        'tipo'             => env('FACT_INT_TIPO_COMPROBANTE', 'I'),
        'moneda'           => env('FACT_INT_MONEDA', 'MXN'),
        'lugar_expedicion' => env('FACT_INT_LUGAR_EXP', '64000'),
        'metodo'           => env('FACT_INT_METODO_PAGO', 'PPD'),
        'forma'            => env('FACT_INT_FORMA_PAGO', '99'),
        'uso'              => env('FACT_INT_USO_CFDI', 'G03'),

        'disk'             => env('FACTURAAPI_INT_DISK', 'public'),
        'regimen_default'  => env('FACT_INT_REGIMEN_DEFAULT', '601'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Facturapi - Alias legacy (para código viejo)
    |--------------------------------------------------------------------------
    |
    | Esto evita que reviente código que busca config('services.facturapi.key')
    | o variables FACTURAPI_KEY / FACTURAAPI_KEY.
    |
    | Por defecto lo amarramos al BACKOFFICE (INT).
    |
    */
    'facturapi' => [
        // Si existe una key legacy, úsala; si no, usa la interna.
        'key'      => env('FACTURAPI_KEY')
            ?: env('FACTURAAPI_KEY')
            ?: env('FACTURAAPI_INT_KEY'),

        'base_uri' => rtrim(
            env('FACTURAAPI_INT_BASE_URI', 'https://www.facturapi.io/v2'),
            '/'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe
    |--------------------------------------------------------------------------
    */
    'stripe' => [
        'key'            => env('STRIPE_KEY'),
        'secret'         => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', null),

        // NO usar url() aquí. Usa solo ENV:
        'success_url' => env('STRIPE_SUCCESS_URL'),
        'cancel_url'  => env('STRIPE_CANCEL_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Skydropx Pro
    |--------------------------------------------------------------------------
    */
    'skydropx_pro' => [
        'client_id' => env('SKYDROPX_CLIENT_ID'),
        'secret'    => env('SKYDROPX_CLIENT_SECRET'),
        'token_url' => env('SKYDROPX_PRO_TOKEN_URL'),
        'api_base'  => env('SKYDROPX_PRO_API_BASE', 'https://sb-pro.skydropx.com/api/v1'),
        'scope'     => env('SKYDROPX_SCOPE', ''),
        'origin_cp' => env('ORIGIN_POSTAL_CODE', '52060'),
        'free_ship' => (float) env('FREE_SHIPPING_THRESHOLD', 5000.00),
    ],

    'copomex' => [
        'token' => env('COPOMEX_TOKEN'),
    ],

    'osrm' => [
        'base' => env('OSRM_BASE_URL', 'http://localhost:5000'),
    ],

    'traffic' => [
        'provider' => env('TRAFFIC_PROVIDER', null),
        'api_key'  => env('TRAFFIC_API_KEY', null),
    ],

    'ai' => [
        'enabled' => filter_var(env('AI_ENABLED', false), FILTER_VALIDATE_BOOL),
        'model'   => env('AI_MODEL', 'gpt-4o-mini'),
    ],

    'meli' => [
        'client_id'     => env('MELI_CLIENT_ID'),
        'client_secret' => env('MELI_CLIENT_SECRET'),
        'redirect'      => env('MELI_REDIRECT'),
        'sandbox'       => filter_var(env('MELI_SANDBOX', true), FILTER_VALIDATE_BOOL),
    ],

    'whatsapp' => [
        'version'         => env('WHATSAPP_API_VERSION', 'v21.0'),
        'phone_id'        => env('WHATSAPP_PHONE_NUMBER_ID'),
        'token'           => env('WHATSAPP_ACCESS_TOKEN'),
        'template_agenda' => env('WHATSAPP_TEMPLATE_AGENDA', 'agenda_recordatorio'),
    ],

    'ilovepdf' => [
        'public_key' => env('ILOVEPDF_PUBLIC_KEY'),
        'secret_key' => env('ILOVEPDF_SECRET_KEY'),
        'region'     => env('ILOVEPDF_REGION', 'us'),
        'timeout'    => (int) env('ILOVEPDF_TIMEOUT', 180),
    ],
'amazon_spapi' => [
        // Login With Amazon (SP-API)
        'lwa_client_id'     => env('AMAZON_LWA_CLIENT_ID'),
        'lwa_client_secret' => env('AMAZON_LWA_CLIENT_SECRET'),
        'lwa_refresh_token' => env('AMAZON_LWA_REFRESH_TOKEN'),

        // AWS (firmado)
        'aws_access_key'    => env('AWS_ACCESS_KEY_ID'),
        'aws_secret_key'    => env('AWS_SECRET_ACCESS_KEY'),
        'aws_region'        => env('AWS_DEFAULT_REGION', 'us-east-1'),

        // IAM Role para asumir (STS AssumeRole)
        'role_arn'          => env('AMAZON_SPAPI_ROLE_ARN', env('SPAPI_ROLE_ARN')),

        // Seller / Merchant ID (lo que te faltaba)
        'seller_id'         => env('SPAPI_SELLER_ID'),

        // Endpoint + Marketplace
        'endpoint'          => env('SPAPI_ENDPOINT', 'https://sellingpartnerapi-na.amazon.com'),
        'marketplace_id'    => env('SPAPI_MARKETPLACE_ID', 'A1AM78C64UM0Y8'),
    ],

];
