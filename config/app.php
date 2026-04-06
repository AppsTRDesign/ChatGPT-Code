<?php

declare(strict_types=1);

$socketPort = (int) env('SOCKET_PORT', '3001');
if ($socketPort < 1 || $socketPort > 65535) {
    $socketPort = 3001;
}
$socketUrl = trim((string) env('SOCKET_URL', ''));
if ($socketUrl === '') {
    $socketUrl = 'http://127.0.0.1:' . $socketPort;
}

return [
    'app_name' => env('APP_NAME', 'Noa Political Wars'),
    'app_env' => env('APP_ENV', 'production'),
    'app_debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'app_url' => env('APP_URL', 'http://localhost'),
    'socket_port' => $socketPort,
    'socket_url' => $socketUrl,
    'session_name' => env('SESSION_NAME', 'noasoft_game_session'),
    'session_secure' => filter_var(env('SESSION_SECURE', 'false'), FILTER_VALIDATE_BOOL),
];
