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
        | Modelos
        |--------------------------------------------------------------------------
        */

        // Modelo principal (razonamiento de alto nivel)
        'primary' => env('OPENAI_PRIMARY_MODEL', 'gpt-5.5'),

        // Fallbacks automáticos (gpt-4.1/gpt-4o aceptan temperature y max_tokens sin quejarse)
        'fallbacks' => array_values(array_filter(array_map(
            'trim',
            explode(',', env(
                'OPENAI_FALLBACK_MODELS',
                'gpt-5.4,gpt-4.1,gpt-4o'
            ))
        ))),

        // Compatibilidad legacy
        'model' => env('OPENAI_PRIMARY_MODEL', 'gpt-5.5'),

        // Modelo rápido para reparación / forzado de JSON inválido
        'json_repair_model' => env('OPENAI_JSON_REPAIR_MODEL', 'gpt-5.4-mini'),

        // Modelo para matching de catálogo (rápido + sentido común)
        'match_model' => env('OPENAI_MATCH_MODEL', 'gpt-5.4-mini'),

        /*
        |--------------------------------------------------------------------------
        | Timeouts y Reintentos
        |--------------------------------------------------------------------------
        */
        'timeout' => (int) env('OPENAI_TIMEOUT', 300),
        'connect_timeout' => (int) env('OPENAI_CONNECT_TIMEOUT', 30),
        'max_retries_per_model' => (int) env('OPENAI_RETRIES_PER_MODEL', 2),
        'retry_base_delay_ms' => (int) env('OPENAI_RETRY_BASE_MS', 400),
        'max_total_attempts' => (int) env('OPENAI_MAX_TOTAL_ATTEMPTS', 6),

        /*
        |--------------------------------------------------------------------------
        | Embeddings
        |--------------------------------------------------------------------------
        */
        'embed_model' => env('OPENAI_EMBED_MODEL', 'text-embedding-3-large'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Python AI
    |--------------------------------------------------------------------------
    |
    | IMPORTANTE:
    | Tu DocumentAiController usa:
    | config('services.python_ai.bin')
    | config('services.python_ai.script')
    |
    */
    'python_ai' => [
        'bin' => env('PYTHON_BIN', '/usr/bin/python3'),
        'script' => env('PYTHON_SCRIPT'),
        'azure_purchase_pdf_extract_script' => env('AZURE_PURCHASE_PDF_EXTRACT_SCRIPT'),
        'azure_licitacion_pdf_extract_script' => env('AZURE_LICITACION_PDF_EXTRACT_SCRIPT'),
         'acta_script' => env('PYTHON_AI_ACTA_SCRIPT', base_path('python/acta_fallo_cli.py')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Azure Document Intelligence
    |--------------------------------------------------------------------------
    */
    'azure_document_intelligence' => [
        'endpoint' => env('AZURE_DOCUMENT_INTELLIGENCE_ENDPOINT'),
        'key' => env('AZURE_DOCUMENT_INTELLIGENCE_KEY'),
        'api_version' => env('AZURE_DOCUMENT_INTELLIGENCE_API_VERSION', '2024-11-30'),
        'timeout' => (int) env('AZURE_DOCUMENT_INTELLIGENCE_TIMEOUT', 300),
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
    | Facturapi - Sitio web
    |--------------------------------------------------------------------------
    */
    'facturaapi_web' => [
        'key'      => env('FACTURAAPI_WEB_KEY'),
        'base_uri' => rtrim(env('FACTURAAPI_WEB_BASE_URI', 'https://www.facturapi.io/v2'), '/'),

        'serie'  => env('FACT_WEB_SERIE', 'F'),
        'metodo' => env('FACT_WEB_METODO_PAGO', 'PUE'),
        'forma'  => env('FACT_WEB_FORMA_PAGO', '04'),
        'uso'    => env('FACT_WEB_USO_CFDI', 'G03'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Facturapi - Sistema interno
    |--------------------------------------------------------------------------
    */
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
    | Facturapi - Alias legacy
    |--------------------------------------------------------------------------
    */
    'facturapi' => [
        'key' => env('FACTURAPI_KEY')
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

    /*
    |--------------------------------------------------------------------------
    | Copomex
    |--------------------------------------------------------------------------
    */
    'copomex' => [
        'token' => env('COPOMEX_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OSRM
    |--------------------------------------------------------------------------
    */
    'osrm' => [
        'base' => env('OSRM_BASE_URL', 'http://localhost:5000'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Traffic
    |--------------------------------------------------------------------------
    */
    'traffic' => [
        'provider' => env('TRAFFIC_PROVIDER', null),
        'api_key'  => env('TRAFFIC_API_KEY', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI legacy
    |--------------------------------------------------------------------------
    */
    'ai' => [
        'enabled' => filter_var(env('AI_ENABLED', false), FILTER_VALIDATE_BOOL),
        'model'   => env('AI_MODEL', 'gpt-5.5'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mercado Libre
    |--------------------------------------------------------------------------
    */
    'meli' => [
        'client_id'     => env('MELI_CLIENT_ID'),
        'client_secret' => env('MELI_CLIENT_SECRET'),
        'redirect'      => env('MELI_REDIRECT'),
        'sandbox'       => filter_var(env('MELI_SANDBOX', true), FILTER_VALIDATE_BOOL),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp
    |--------------------------------------------------------------------------
    */
    'whatsapp' => [
        'version'         => env('WHATSAPP_API_VERSION', 'v21.0'),
        'phone_id'        => env('WHATSAPP_PHONE_NUMBER_ID'),
        'token'           => env('WHATSAPP_ACCESS_TOKEN'),
        'template_agenda' => env('WHATSAPP_TEMPLATE_AGENDA', 'agenda_recordatorio'),
    ],

    /*
    |--------------------------------------------------------------------------
    | iLovePDF
    |--------------------------------------------------------------------------
    */
    'ilovepdf' => [
        'public_key' => env('ILOVEPDF_PUBLIC_KEY'),
        'secret_key' => env('ILOVEPDF_SECRET_KEY'),
        'region'     => env('ILOVEPDF_REGION', 'us'),
        'timeout'    => (int) env('ILOVEPDF_TIMEOUT', 180),
    ],

    /*
    |--------------------------------------------------------------------------
    | Amazon SP-API
    |--------------------------------------------------------------------------
    */
    'amazon_spapi' => [
        'lwa_client_id'     => env('AMAZON_LWA_CLIENT_ID'),
        'lwa_client_secret' => env('AMAZON_LWA_CLIENT_SECRET'),
        'lwa_refresh_token' => env('AMAZON_LWA_REFRESH_TOKEN'),

        'aws_access_key'    => env('AWS_ACCESS_KEY_ID'),
        'aws_secret_key'    => env('AWS_SECRET_ACCESS_KEY'),
        'aws_region'        => env('AWS_DEFAULT_REGION', 'us-east-1'),

        'role_arn'          => env('AMAZON_SPAPI_ROLE_ARN', env('SPAPI_ROLE_ARN')),
        'seller_id'         => env('SPAPI_SELLER_ID'),

        'endpoint'          => env('SPAPI_ENDPOINT', 'https://sellingpartnerapi-na.amazon.com'),
        'marketplace_id'    => env('SPAPI_MARKETPLACE_ID', 'A1AM78C64UM0Y8'),
    ],
'shopify' => [
    'shop' => env('SHOPIFY_SHOP'),
    'token' => env('SHOPIFY_ADMIN_TOKEN'),
    'version' => env('SHOPIFY_API_VERSION', '2026-04'),

    'client_id' => env('SHOPIFY_CLIENT_ID'),
    'client_secret' => env('SHOPIFY_CLIENT_SECRET'),

    'location_id' => env('SHOPIFY_LOCATION_ID'),
    'webhook_secret' => env('SHOPIFY_WEBHOOK_SECRET'),
],


'envia' => [
    'mode' => env('ENVIA_MODE', 'sandbox'),
    'token' => env('ENVIA_API_TOKEN'),
    'base_url' => env('ENVIA_BASE_URL', env('ENVIA_MODE', 'sandbox') === 'production'
        ? 'https://api.envia.com'
        : 'https://api-test.envia.com'
    ),
    'queries_url' => env('ENVIA_QUERIES_URL', env('ENVIA_MODE', 'sandbox') === 'production'
        ? 'https://queries.envia.com'
        : 'https://queries-test.envia.com'
    ),
    'debug' => env('ENVIA_DEBUG', false),

    /*
     * Si está true, el controlador intenta cotizar carrier por carrier.
     * Esto puede mostrar más opciones cuando Envia no regresa todo en una sola llamada.
     */
    'quote_all_carriers' => env('ENVIA_QUOTE_ALL_CARRIERS', true),

    'origin' => [
        'name' => env('ENVIA_ORIGIN_NAME', 'Jureto'),
        'company' => env('ENVIA_ORIGIN_COMPANY', 'Jureto'),
        'email' => env('ENVIA_ORIGIN_EMAIL', 'ventas@jureto.com.mx'),
        'phone' => env('ENVIA_ORIGIN_PHONE', '7220000000'),
        'street' => env('ENVIA_ORIGIN_STREET', 'Calle origen'),
        'number' => env('ENVIA_ORIGIN_NUMBER', 'S/N'),
        'district' => env('ENVIA_ORIGIN_DISTRICT', 'Centro'),
        'city' => env('ENVIA_ORIGIN_CITY', 'Toluca'),
        'state' => env('ENVIA_ORIGIN_STATE', 'EM'),
        'country' => env('ENVIA_ORIGIN_COUNTRY', 'MX'),
        'postal_code' => env('ENVIA_ORIGIN_POSTAL_CODE', '50000'),
        'reference' => env('ENVIA_ORIGIN_REFERENCE', ''),
    ],

    'default_package' => [
        'weight' => env('ENVIA_PACKAGE_WEIGHT', 1),
        'length' => env('ENVIA_PACKAGE_LENGTH', 30),
        'width' => env('ENVIA_PACKAGE_WIDTH', 25),
        'height' => env('ENVIA_PACKAGE_HEIGHT', 20),
    ],
],

];