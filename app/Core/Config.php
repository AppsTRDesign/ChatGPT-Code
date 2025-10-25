<?php

namespace App\Core;

class Config
{
    protected static array $config = [];

    public static function init(string $path): void
    {
        $files = glob(rtrim($path, '/') . '/*.php');
        foreach ($files as $file) {
            $key = basename($file, '.php');
            static::$config[$key] = require $file;
        }
    }

    public static function get(string $key, $default = null)
    {
        return static::$config[$key] ?? $default;
    }
}
