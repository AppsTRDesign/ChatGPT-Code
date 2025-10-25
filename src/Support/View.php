<?php

declare(strict_types=1);

namespace App\Support;

class View
{
    public static function render(string $template, array $data = []): string
    {
        $viewFile = __DIR__ . '/../../resources/views/' . $template . '.php';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View {$template} not found");
        }

        extract($data);
        ob_start();
        include $viewFile;
        return (string) ob_get_clean();
    }
}
