<?php

declare(strict_types=1);

namespace App\Support;

class View
{
    public static function render(string $template, array $data = [], ?string $layout = null): string
    {
        $viewFile = __DIR__ . '/../../resources/views/' . $template . '.php';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View {$template} not found");
        }

        extract($data);
        ob_start();
        include $viewFile;
        $content = (string) ob_get_clean();

        if ($layout === null) {
            return $content;
        }

        $layoutFile = __DIR__ . '/../../resources/views/layouts/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            throw new \RuntimeException("Layout {$layout} not found");
        }

        ob_start();
        include $layoutFile;
        return (string) ob_get_clean();
    }
}
