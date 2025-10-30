<?php

declare(strict_types=1);

namespace App\Http;

use Psr\Http\Message\ResponseInterface as Response;

final class ViewRenderer
{
    public function render(Response $response, string $view, array $data = []): Response
    {
        $path = __DIR__ . '/../../resources/views/' . $view . '.php';
        if (!file_exists($path)) {
            throw new \RuntimeException("View {$view} not found");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $path;
        $html = ob_get_clean();

        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }
}
