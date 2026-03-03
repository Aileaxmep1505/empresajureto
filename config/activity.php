<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rutas que cuentan como "pantallas" (screen views)
    |--------------------------------------------------------------------------
    | Key = routeName
    | Value = nombre humano (lo que verás en bitácora)
    */
    'screens' => [
        // Documentación de altas
        'alta.docs.index' => 'Documentación de altas · Listado',
        'alta.docs.show'  => 'Documentación de altas · Detalle',
        'secure.alta-docs.pin.show' => 'Documentación de altas · NIP',

        // Tickets
        'tickets.index'   => 'Tickets · Lista',
        'tickets.create'  => 'Tickets · Nuevo',
        'tickets.show'    => 'Tickets · Detalle',
        'tickets.work'    => 'Tickets · Trabajo',

        // Cotizaciones
        'cotizaciones.index'  => 'Cotizaciones · Lista',
        'cotizaciones.create' => 'Cotizaciones · Nueva',
        'cotizaciones.show'   => 'Cotizaciones · Detalle',

        // Part contable
        'partcontable.index' => 'Partida contable · Inicio',
        'partcontable.activity.all' => 'Bitácora · General',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ruido a ignorar completamente
    |--------------------------------------------------------------------------
    */
    'ignore_paths' => [
        'notifications/feed',
        'livewire',
        '_debugbar',
        'telescope',
        'storage',
        'build',
        'assets',
        'css',
        'js',
        'images',
    ],

    'ignore_route_names' => [
        'notifications.feed',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dedup para screen_view (evita repetir lo mismo)
    |--------------------------------------------------------------------------
    */
    'screen_dedup_seconds' => 30,

    /*
    |--------------------------------------------------------------------------
    | Sanitización de meta
    |--------------------------------------------------------------------------
    */
    'sensitive_keys' => [
        'password','token','access_token','refresh_token','authorization','cookie',
        'csrf_token','x-csrf-token','nip','pin','rfc','ine','curp','secret','api_key',
        'file','documento','archivo','base64','content',
    ],

    'max_value_length' => 500,
    'max_meta_keys'    => 40,
];