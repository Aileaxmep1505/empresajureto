<?php

return [
    // Proveedor actual: 'openai' o 'none'
    'provider' => env('ROUTE_AI_PROVIDER', 'none'),

    // Modelo recomendado económico y capaz:
    'model'    => env('ROUTE_AI_MODEL', 'gpt-4o-mini'),

    // Clave del proveedor
    'api_key'  => env('ROUTE_AI_API_KEY', env('OPENAI_API_KEY', null)),
];
