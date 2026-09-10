<?php

return [
    'mode' => env('APPY_MODE'),
    // Package-local context is ignored by git and created lazily for development installs.
    'runtime_path' => env('APPYHP_RUNTIME_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . '.appyhp'),

    'ai' => [
        'provider' => env('APPY_AI_PROVIDER', 'openai'),
        'base_url' => env('APPY_AI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('APPY_AI_MODEL', ''),
        'api_key' => env('APPY_AI_KEY', ''),
        'live' => true,
        'debounce_ms' => 1200,
        'timeout' => 120,
        'max_output_tokens' => 8192,
        'source_roots' => ['app', 'routes', 'database', 'resources', 'config', 'tests', 'modules', 'Modules'],
    ],
];
