<?php
declare(strict_types=1);

use App\Core\Config;

function base_path(string $path = ''): string
{
    $base = realpath(__DIR__ . '/..');
    return $path ? $base . '/' . ltrim($path, '/') : $base;
}

function resource_path(string $path = ''): string
{
    return base_path('resources/' . ltrim($path, '/'));
}

function storage_path(string $path = ''): string
{
    return base_path('storage/' . ltrim($path, '/'));
}

function database_path(string $path = ''): string
{
    return base_path('database/' . ltrim($path, '/'));
}

function asset(string $path): string
{
    $base = Config::get('app.base_url', '');
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function csrf_token(): string
{
    $token = $_SESSION['_csrf_token'] ?? bin2hex(random_bytes(32));
    $_SESSION['_csrf_token'] = $token;
    return $token;
}

function verify_csrf_token(?string $token): bool
{
    return isset($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], (string) $token);
}

function view(string $template, array $data = []): void
{
    App\Core\View::render($template, $data);
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function json(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_THROW_ON_ERROR);
    exit;
}

function old(string $key, string $default = ''): string
{
    return $_SESSION['old'][$key] ?? $default;
}

function set_old(array $input): void
{
    $_SESSION['old'] = $input;
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

function auth_user(): ?array
{
    return $_SESSION['auth'] ?? null;
}

function is_authenticated(): bool
{
    return isset($_SESSION['auth']);
}
