<?php

namespace App;

use PDO;

class Settings
{
    private static ?array $cache = null;

    private static function load(): void
    {
        if (self::$cache !== null) {
            return;
        }

        $stmt = Helpers::db()->query('SELECT `key`, value FROM settings');
        $data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        self::$cache = $data;
    }

    public static function all(): array
    {
        self::load();
        return self::$cache ?? [];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();
        return self::$cache[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $db = Helpers::db();
        $stmt = $db->prepare('INSERT INTO settings (`key`, value, updated_at) VALUES (:key, :value, NOW()) ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()');
        $stmt->execute([
            'key' => $key,
            'value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value,
        ]);
        self::$cache[$key] = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
    }

    public static function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            self::set((string) $key, $value);
        }
    }

    public static function remove(string $key): void
    {
        $stmt = Helpers::db()->prepare('DELETE FROM settings WHERE `key` = :key');
        $stmt->execute(['key' => $key]);
        unset(self::$cache[$key]);
    }

    public static function siteName(): string
    {
        return trim((string) self::get('site_name', APP_NAME));
    }

    public static function siteTagline(): string
    {
        return trim((string) self::get('site_tagline', 'QR Kod ve API Yönetim Platformu'));
    }

    public static function metaDescription(): string
    {
        return trim((string) self::get('meta_description', 'NoaSoft QR Menu ile kurumsal QR kod üretimi ve API yönetimini tek panelde toplayın.'));
    }

    public static function metaKeywords(): string
    {
        return trim((string) self::get('meta_keywords', 'qr kod, api, iyzico, dropzone, logo, renk, token'));
    }

    public static function headerHtml(): string
    {
        return (string) self::get('header_html', '');
    }

    public static function footerHtml(): string
    {
        return (string) self::get('footer_html', '');
    }

    public static function logoPath(): ?string
    {
        $path = trim((string) self::get('site_logo', ''));
        return $path !== '' ? $path : null;
    }

    public static function faviconPath(): ?string
    {
        $path = trim((string) self::get('site_favicon', ''));
        return $path !== '' ? $path : null;
    }

    public static function logoUrl(): ?string
    {
        $path = self::logoPath();
        if (!$path) {
            return null;
        }

        return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    }

    public static function faviconUrl(): ?string
    {
        $path = self::faviconPath();
        if (!$path) {
            return null;
        }

        return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    }
}
