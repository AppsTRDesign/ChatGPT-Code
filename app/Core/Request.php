<?php

namespace App\Core;

class Request
{
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        return rtrim($path, '/') ?: '/';
    }

    public function input(string $key, $default = null)
    {
        if (array_key_exists($key, $_POST)) {
            return $_POST[$key];
        }

        if (array_key_exists($key, $_GET)) {
            return $_GET[$key];
        }

        $json = $this->json();
        if (array_key_exists($key, $json)) {
            return $json[$key];
        }

        return $default;
    }

    public function all(): array
    {
        $json = $this->json();
        return array_merge($_GET, $_POST, $json);
    }

    public function json(): array
    {
        $payload = file_get_contents('php://input');
        $decoded = json_decode($payload, true);
        return is_array($decoded) ? $decoded : [];
    }
}
