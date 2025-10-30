<?php
class Localization
{
    private static array $translations = [];

    public static function load(string $lang = 'en'): void
    {
        $path = __DIR__ . '/../../public/lang/' . $lang . '.json';
        if (!file_exists($path)) {
            $path = __DIR__ . '/../../public/lang/en.json';
        }
        self::$translations = json_decode(file_get_contents($path), true) ?? [];
    }

    public static function trans(string $key): string
    {
        return self::$translations[$key] ?? $key;
    }
}
