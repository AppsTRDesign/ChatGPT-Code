<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    private static string $basePath;

    public static function setBasePath(string $path): void
    {
        self::$basePath = rtrim($path, '/');
    }

    public static function render(string $template, array $data = []): void
    {
        $file = self::$basePath . '/' . str_replace('.', '/', $template) . '.php';
        if (!file_exists($file)) {
            http_response_code(500);
            echo 'View not found: ' . htmlspecialchars($template);
            return;
        }

        extract($data, EXTR_SKIP);
        include $file;
    }
}
