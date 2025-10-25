<?php

namespace App\Core;

abstract class Controller
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    protected function view(string $template, array $data = []): string
    {
        $templatePath = __DIR__ . '/../Views/' . $template . '.php';
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("View {$template} not found");
        }

        extract($data);
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }
}
