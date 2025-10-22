<?php

require_once __DIR__ . '/DataStore.php';

function config(): array
{
    static $config;
    if (!$config) {
        $config = require __DIR__ . '/../config.php';
    }
    return $config;
}

function datastore(string $name, array $default = []): DataStore
{
    $path = config()['storage_path'] . '/' . $name . '.json';
    return new DataStore($path, $default);
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function require_admin_auth(): void
{
    session_start();
    $password = config()['admin_password'];
    if (($_SESSION['admin_authenticated'] ?? false) !== true) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payload = $_POST ?: json_decode(file_get_contents('php://input'), true) ?? [];
            if (($payload['password'] ?? '') === $password) {
                $_SESSION['admin_authenticated'] = true;
                json_response(['success' => true]);
            }
        }
        json_response(['error' => 'Unauthorized'], 401);
    }
}

function sanitize(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function cache_path(string $key): string
{
    return config()['cache_path'] . '/' . md5($key) . '.cache';
}

function cache_get(string $key): ?string
{
    $path = cache_path($key);
    if (file_exists($path) && (time() - filemtime($path)) < config()['cache_ttl']) {
        return file_get_contents($path) ?: null;
    }
    return null;
}

function cache_put(string $key, string $value): void
{
    $path = cache_path($key);
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }
    file_put_contents($path, $value);
}

function cache_clear(): void
{
    $path = config()['cache_path'];
    if (!is_dir($path)) {
        return;
    }
    foreach (glob($path . '/*.cache') as $file) {
        @unlink($file);
    }
}

