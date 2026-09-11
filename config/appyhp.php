<?php

return [
    'mode' => env('APPY_MODE'),
    // Studio can read and modify source files. Keep it local unless access is
    // deliberately opened to a trusted address and protected by host middleware.
    'allowed_ips' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('APPYHP_ALLOWED_IPS', '127.0.0.1,::1'))
    ))),
    'access_token' => env('APPYHP_ACCESS_TOKEN', ''),
    'middleware' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('APPYHP_MIDDLEWARE', ''))
    ))),
    // Package-local context is ignored by git and created lazily for development installs.
    'runtime_path' => env('APPYHP_RUNTIME_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . '.appyhp'),

    'ai' => [
        'provider' => env('APPY_AI_PROVIDER', 'openai'),
        'base_url' => env('APPY_AI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('APPY_AI_MODEL', ''),
        'api_key' => env('APPY_AI_KEY', ''),
        'live' => false,
        'debounce_ms' => 1200,
        'timeout' => 120,
        'max_output_tokens' => 8192,
        'source_roots' => ['app', 'routes', 'database', 'resources', 'config', 'tests', 'modules', 'Modules'],
    ],
];
