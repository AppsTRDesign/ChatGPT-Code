<?php
class Router
{
    private array $routes = [];

    public function add(string $method, string $path, callable $handler, array $middlewares = []): void
    {
        $this->routes[strtoupper($method)][$path] = ['handler' => $handler, 'middlewares' => $middlewares];
    }

    public function dispatch(string $method, string $path)
    {
        $method = strtoupper($method);
        $path = rtrim($path, '/') ?: '/';

        if (!isset($this->routes[$method][$path])) {
            http_response_code(404);
            echo json_encode(['error' => 'Route not found']);
            return;
        }

        $route = $this->routes[$method][$path];
        $handler = $route['handler'];

        foreach ($route['middlewares'] as $middleware) {
            $instance = new $middleware();
            $response = $instance->handle();
            if ($response !== true) {
                echo json_encode($response);
                return;
            }
        }

        return call_user_func($handler);
    }
}
