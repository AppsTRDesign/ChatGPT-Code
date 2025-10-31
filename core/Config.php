<?php

namespace Core;

class Config
{
    private static array $config = [];

    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            throw new \RuntimeException('Config file not found: ' . $path);
        }

        self::$config = require $path;
    }

    public static function get(string $key, $default = null)
    {
        return self::$config[$key] ?? $default;
    }

    public static function all(): array
    {
        return self::$config;
    }
}
