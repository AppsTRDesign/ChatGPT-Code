<?php
require_once __DIR__ . '/Database.php';

function config(string $key, $default = null)
{
    static $config;
    if (!$config) {
        $config = require __DIR__ . '/../config.php';
    }
    return $config[$key] ?? $default;
}

function db(): \PDO
{
    return Database::getInstance()->pdo();
}

function ensure_uploads_path(): string
{
    $path = config('uploads_path');
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
    return $path;
}

function ensure_font_path(string $fontName, string $sourceUrl): string
{
    $sanitizedName = preg_replace('/[^A-Za-z0-9_-]/', '', $fontName) ?: 'CustomFont';
    $cacheDirectory = config('font_cache_path', config('cache_path') . '/fonts');
    if (!is_dir($cacheDirectory)) {
        mkdir($cacheDirectory, 0777, true);
    }
    $target = rtrim($cacheDirectory, '/\\') . '/' . $sanitizedName . '.ttf';
    if (!file_exists($target)) {
        $context = stream_context_create([
            'http' => ['timeout' => 15],
            'https' => ['timeout' => 15],
        ]);
        $data = @file_get_contents($sourceUrl, false, $context);
        if ($data === false || strlen($data) === 0) {
            throw new RuntimeException('Yazı tipi indirilemedi. Kaynak URL: ' . $sourceUrl);
        }
        file_put_contents($target, $data, LOCK_EX);
    }
    return $target;
}

function ensure_invoice_font(): array
{
    $name = config('invoice_font_name', 'NotoSans');
    $path = ensure_font_path($name, config('invoice_font_url'));
    return [$path, $name];
}

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitize($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function cache_get(string $key)
{
    $path = config('cache_path');
    if (!is_dir($path)) {
        return null;
    }
    $file = $path . '/' . md5($key) . '.cache.php';
    if (!file_exists($file)) {
        return null;
    }
    $ttl = config('cache_ttl', 0);
    if ($ttl && filemtime($file) + $ttl < time()) {
        @unlink($file);
        return null;
    }
    return file_get_contents($file);
}

function cache_put(string $key, string $content): void
{
    $path = config('cache_path');
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
    $file = $path . '/' . md5($key) . '.cache.php';
    file_put_contents($file, $content);
}

function cache_forget(string $key): void
{
    $path = config('cache_path');
    $file = $path . '/' . md5($key) . '.cache.php';
    if (file_exists($file)) {
        @unlink($file);
    }
}

function cache_flush(): void
{
    $path = config('cache_path');
    if (!is_dir($path)) {
        return;
    }
    foreach (glob($path . '/*.cache.php') as $file) {
        @unlink($file);
    }
}

function require_auth(): array
{
    session_start();
    if (empty($_SESSION['admin'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Oturumunuz sona erdi. Lütfen tekrar giriş yapın.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    return $_SESSION['admin'];
}

function password_hash_if_needed(string $password): string
{
    if (password_get_info($password)['algo']) {
        return $password;
    }
    return password_hash($password, PASSWORD_DEFAULT);
}
