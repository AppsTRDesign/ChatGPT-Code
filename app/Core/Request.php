<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public function path(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?? '/');

        if (str_starts_with($path, '/index.php/')) {
            $path = substr($path, strlen('/index.php'));
        } elseif ($path === '/index.php') {
            $path = '/';
        }

        return rtrim($path ?: '/', '/') ?: '/';
    }

    public function json(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
}
