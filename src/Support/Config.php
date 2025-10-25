<?php

declare(strict_types=1);

namespace App\Support;

final class Config
{
    private static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        [$file, $item] = self::parseKey($key);
        if (!isset(self::$cache[$file])) {
            $path = __DIR__ . '/../../config/' . $file . '.php';
            self::$cache[$file] = file_exists($path) ? (array) require $path : [];
        }

        if ($item === null) {
            return self::$cache[$file] ?? $default;
        }

        return self::$cache[$file][$item] ?? $default;
    }

    private static function parseKey(string $key): array
    {
        if (!str_contains($key, '.')) {
            return [$key, null];
        }

        return explode('.', $key, 2);
    }

    public static function all(string $file): array
    {
        if (!isset(self::$cache[$file])) {
            $path = __DIR__ . '/../../config/' . $file . '.php';
            self::$cache[$file] = file_exists($path) ? (array) require $path : [];
        }

        return self::$cache[$file];
    }
}
