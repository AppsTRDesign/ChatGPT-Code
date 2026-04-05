<?php

declare(strict_types=1);

error_reporting(E_ALL);

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = __DIR__;
        return $path ? $base . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : $base;
    }
}

if (!function_exists('env')) {
    function env(string $key, ?string $default = null): ?string
    {
        static $vars = null;
        if ($vars === null) {
            $vars = [];
            $envFile = base_path('.env');
            if (is_file($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
                foreach ($lines as $line) {
                    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
                        continue;
                    }
                    [$k, $v] = explode('=', $line, 2);
                    $vars[trim($k)] = trim($v);
                }
            }
        }

        return $_ENV[$key] ?? $_SERVER[$key] ?? $vars[$key] ?? $default;
    }
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = base_path('src/' . str_replace('\\', '/', $relative) . '.php');
    if (is_file($file)) {
        require_once $file;
    }
});

$config = require base_path('config/app.php');


ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');

session_name($config['session_name']);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $config['session_secure'],
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

if ($config['app_env'] !== 'production') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
}

return $config;
