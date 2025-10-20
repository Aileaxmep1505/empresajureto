<?php

return [
    // Base oficial de OpenAI; puedes cambiar a proxy si quieres
    'base'   => env('SEARCHAI_BASE', 'https://api.openai.com/v1'),
    'key'    => env('SEARCHAI_API_KEY', ''),            // <--- NUEVA clave
    'model'  => env('SEARCHAI_MODEL', 'gpt-4o-mini'),   // modelo liviano para expansión
    'timeout'=> env('SEARCHAI_TIMEOUT', 12),
];
