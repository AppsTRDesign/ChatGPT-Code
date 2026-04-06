<?php

declare(strict_types=1);

return [
    'app_name' => env('APP_NAME', 'Noa Political Wars'),
    'app_env' => env('APP_ENV', 'production'),
    'app_debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'app_url' => env('APP_URL', 'http://localhost'),
    'socket_port' => (int) env('SOCKET_PORT', '3001'),
    'socket_url' => env('SOCKET_URL', 'http://127.0.0.1:3001'),
    'session_name' => env('SESSION_NAME', 'noasoft_game_session'),
    'session_secure' => filter_var(env('SESSION_SECURE', 'false'), FILTER_VALIDATE_BOOL),
];
