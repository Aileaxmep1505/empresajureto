<?php

return [
    'default' => 'account_default',

    'accounts' => [
        'account_default' => [
            'host'          => env('IMAP_HOST', 'imap.hostinger.com'),
            'port'          => (int) env('IMAP_PORT', 993),
            'encryption'    => env('IMAP_ENCRYPTION', 'ssl'), // 'ssl' o 'tls'
            'validate_cert' => filter_var(env('IMAP_VALIDATE_CERT', true), FILTER_VALIDATE_BOOLEAN),
            'username'      => env('IMAP_USERNAME'),
            'password'      => env('IMAP_PASSWORD'),
            'authentication' => null,
            'protocol'      => 'imap'
        ],
    ],

    'options' => [
        'delimiter' => '/',
        'fetch' => \Webklex\PHPIMAP\IMAP::FT_PEEK, // no marcar leído al listar
        'fetch_body' => false,
        'fetch_attachment' => false,
        'soft_fail' => true,
        'decoder' => [
            'message' => 'utf-8',
            'attachment' => 'utf-8'
        ],
    ],
];
