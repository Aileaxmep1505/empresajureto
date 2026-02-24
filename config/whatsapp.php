<?php

return [
    'token'   => env('WHATSAPP_ACCESS_TOKEN', ''),
    'version' => env('WHATSAPP_API_VERSION', 'v21.0'),

    'waba_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID', ''),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', ''),

    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', ''),
];