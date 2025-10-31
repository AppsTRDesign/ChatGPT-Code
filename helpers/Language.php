<?php

namespace Helpers;

class Language
{
    private static array $translations = [];

    public static function load(string $locale): void
    {
        $path = LANG_PATH . '/' . $locale . '.json';
        if (!file_exists($path)) {
            throw new \RuntimeException('Language file not found: ' . $path);
        }

        $content = file_get_contents($path);
        self::$translations = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    public static function get(string $key, $default = null)
    {
        $segments = explode('.', $key);
        $value = self::$translations;
        foreach ($segments as $segment) {
            if (!isset($value[$segment])) {
                return $default ?? $key;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public static function available(): array
    {
        $files = glob(LANG_PATH . '/*.json');
        return array_map(static function ($file) {
            return basename($file, '.json');
        }, $files);
    }
}
