<?php
declare(strict_types=1);

namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Support\Session;

final class Router
{
    private array $routes = [];

    public function get(string $path, array $action, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $action, $middleware);
    }

    public function post(string $path, array $action, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $action, $middleware);
    }

    public function delete(string $path, array $action, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $action, $middleware);
    }

    public function addRoute(string $method, string $path, array $action, array $middleware = []): void
    {
        $this->routes[$method][$this->normalize($path)] = [
            'action' => $action,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $normalized = $this->normalize($uri);

        if (!isset($this->routes[$method])) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }

        foreach ($this->routes[$method] as $path => $route) {
            $pattern = preg_replace('#\{([^/]+)\}#', '([^/]+)', $path);
            if (preg_match('#^' . $pattern . '$#', $normalized, $matches)) {
                array_shift($matches);
                if (!$this->runMiddleware($route['middleware'])) {
                    return;
                }
                [$controller, $methodName] = $route['action'];
                $instance = new $controller();
                $instance->$methodName(...$matches);
                return;
            }
        }

        http_response_code(404);
        echo 'Not Found';
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }

    private function runMiddleware(array $middleware): bool
    {
        foreach ($middleware as $name) {
            switch ($name) {
                case 'auth':
                    $instance = new AuthMiddleware();
                    break;
                case 'guest':
                    $instance = new GuestMiddleware();
                    break;
                default:
                    $instance = null;
                    break;
            }

            if ($instance && !$instance->handle()) {
                return false;
            }
        }

        return true;
    }
}
