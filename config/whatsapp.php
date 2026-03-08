<?php

return [
    'enabled' => env('WHATSAPP_NOTIFICATIONS_ENABLED', true),

    'token'   => env('WHATSAPP_ACCESS_TOKEN', ''),
    'version' => env('WHATSAPP_API_VERSION', 'v21.0'),

    'waba_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID', ''),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', ''),

    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', ''),

    'default_lang' => env('WHATSAPP_DEFAULT_LANG', 'es_MX'),

    'templates' => [
        'ticket_created' => env('WHATSAPP_TEMPLATE_TICKET_CREATED', 'ticket_created_v1'),
        'ticket_status'  => env('WHATSAPP_TEMPLATE_TICKET_STATUS', 'ticket_status_update_v1'),
        'ticket_comment' => env('WHATSAPP_TEMPLATE_TICKET_COMMENT', 'ticket_comment_v1'),
        'agenda_reminder'  => env('WHATSAPP_TEMPLATE_AGENDA_REMINDER', 'agenda_reminder_v1'),
    ],
];