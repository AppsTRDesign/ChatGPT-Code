<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function add(string $method, string $path, callable $handler, array $middlewares = []): void
    {
        $this->routes[] = compact('method', 'path', 'handler', 'middlewares');
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method() || $route['path'] !== $request->path()) {
                continue;
            }

            foreach ($route['middlewares'] as $middleware) {
                $result = $middleware($request);
                if ($result === false) {
                    return;
                }
            }

            $route['handler']($request);
            return;
        }

        Response::json(['error' => 'Route not found'], 404);
    }
}
