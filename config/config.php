<?php

declare(strict_types=1);

if (defined('APP_BOOTSTRAPPED')) {
    return;
}

define('APP_BOOTSTRAPPED', true);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/../includes/' . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

    if (file_exists($path)) {
        require_once $path;
    }
});

const APP_NAME = 'NoaSoft QR Menu';
const BASE_URL = 'https://qrmenu.noasoft.org';

const DB_HOST = 'localhost';
const DB_NAME = 'qrmenu';
const DB_USER = 'qrmenu_user';
const DB_PASS = 'change_me';
const DB_CHARSET = 'utf8mb4';

if (!function_exists('get_db')) {
    function get_db(): PDO
    {
        static $pdo = null;
        if ($pdo === null) {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        }
        return $pdo;
    }
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
}

function asset(string $path): string
{
    $base = rtrim(BASE_URL, '/');
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}
