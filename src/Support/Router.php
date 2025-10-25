<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use InvalidArgumentException;

class Router
{
    private array $routes = [];
    private string $basePath = '';
    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function match(array $methods, string $path, callable $handler): void
    {
        foreach ($methods as $method) {
            $this->addRoute(strtoupper($method), $path, $handler);
        }
    }

    public function group(string $prefix, callable $callback, bool $guard = false): void
    {
        $previousBase = $this->basePath;
        $this->basePath .= $prefix;

        $callback($this);

        $this->basePath = $previousBase;

        if ($guard) {
            foreach ($this->routes as &$route) {
                if (str_starts_with($route['path'], $this->basePath . $prefix)) {
                    $route['guard'] = true;
                }
            }
        }
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $path) {
                if (($route['guard'] ?? false) && !$this->session->isAuthenticated()) {
                    header('Location: /login');
                    return;
                }

                $handler = $route['handler'];
                $controller = is_array($handler) ? new $handler[0]($this->session) : null;
                $callable = $controller ? [$controller, $handler[1]] : $handler;
                echo call_user_func($callable);
                return;
            }
        }

        http_response_code(404);
        echo View::render('errors/404', ['title' => 'Sayfa bulunamadı', 'layout' => 'public']);
    }

    private function addRoute(string $method, string $path, callable $handler): void
    {
        $path = $this->basePath . $path;
        if (!str_starts_with($path, '/')) {
            throw new InvalidArgumentException('Route path must start with /');
        }

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'guard' => false,
        ];
    }
}
