<?php

use App\Services\Container;

function view(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $viewFile = __DIR__ . '/Views/' . $template . '.php';
    if (!is_file($viewFile)) {
        http_response_code(404);
        echo 'View not found';
        return;
    }
    require $viewFile;
}

function redirect(string $path): never
{
    $base = Container::config('base_url');
    header('Location: ' . rtrim($base, '/') . '/' . ltrim($path, '/'));
    exit;
}

function asset(string $path): string
{
    $base = Container::config('base_url');
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}
