<?php
declare(strict_types=1);

namespace App\Core;

final class Config
{
    private static array $items = [];

    public static function loadDefaults(array $data): void
    {
        self::$items = array_replace_recursive(self::$items, $data);
    }

    public static function loadFile(?string $path = null): void
    {
        $path ??= base_path('config.php');
        if (!file_exists($path)) {
            return;
        }

        $config = require $path;
        if (!is_array($config)) {
            throw new \RuntimeException('config.php dosyası geçerli bir dizi döndürmelidir.');
        }

        self::$items = array_replace_recursive(self::$items, $config);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $array =& self::$items;

        foreach ($segments as $segment) {
            if (!isset($array[$segment]) || !is_array($array[$segment])) {
                $array[$segment] = [];
            }
            $array =& $array[$segment];
        }

        $array = $value;
    }

    public static function all(): array
    {
        return self::$items;
    }
}
