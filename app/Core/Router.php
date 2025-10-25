<?php

namespace App\Core;

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\ClientController;
use App\Controllers\ApiController;

class Router
{
    protected array $routes = [];

    public function __construct()
    {
        $this->registerDefaultRoutes();
    }

    protected function registerDefaultRoutes(): void
    {
        $this->get('/', [ClientController::class, 'home']);
        $this->match('GET', '/login', [AuthController::class, 'showLogin']);
        $this->match('POST', '/login', [AuthController::class, 'login']);
        $this->match('POST', '/logout', [AuthController::class, 'logout']);

        $this->get('/admin', [AdminController::class, 'dashboard']);
        $this->get('/client', [ClientController::class, 'dashboard']);

        $this->group('/api', function () {
            $this->match('POST', '/notifications', [ApiController::class, 'dispatchNotification']);
            $this->match('POST', '/tokens', [ApiController::class, 'registerToken']);
        });
    }

    public function get(string $path, $handler): void
    {
        $this->match('GET', $path, $handler);
    }

    public function post(string $path, $handler): void
    {
        $this->match('POST', $path, $handler);
    }

    public function group(string $prefix, callable $callback): void
    {
        $previousRoutes = $this->routes;
        $callback->call($this);
        $newRoutes = array_diff_key($this->routes, $previousRoutes);

        foreach ($newRoutes as $routeKey => $definition) {
            [$method, $path] = explode('::', $routeKey, 2);
            $this->routes[sprintf('%s::%s', $method, $prefix . $path)] = $definition;
            unset($this->routes[$routeKey]);
        }
    }

    public function match(string $method, string $path, $handler): void
    {
        $this->routes[sprintf('%s::%s', strtoupper($method), rtrim($path, '/') ?: '/')] = $handler;
    }

    public function dispatch(): void
    {
        $request = new Request();
        $method = $request->method();
        $path = $request->path();

        $handler = $this->routes[sprintf('%s::%s', $method, $path)] ?? null;

        if (!$handler) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        if (is_array($handler)) {
            [$class, $action] = $handler;
            $controller = new $class($request);
            echo call_user_func([$controller, $action]);
            return;
        }

        echo call_user_func($handler, $request);
    }
}
