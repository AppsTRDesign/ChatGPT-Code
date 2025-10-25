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
        $this->get('/admin/clients', [AdminController::class, 'clients']);
        $this->get('/admin/clients/data', [AdminController::class, 'clientsData']);
        $this->post('/admin/clients/store', [AdminController::class, 'storeClient']);
        $this->post('/admin/clients/update', [AdminController::class, 'updateClient']);
        $this->post('/admin/clients/delete', [AdminController::class, 'deleteClient']);

        $this->get('/admin/api-keys', [AdminController::class, 'apiKeys']);
        $this->get('/admin/api-keys/data', [AdminController::class, 'apiKeysData']);
        $this->post('/admin/api-keys/store', [AdminController::class, 'storeApiKey']);
        $this->post('/admin/api-keys/update', [AdminController::class, 'updateApiKey']);
        $this->post('/admin/api-keys/revoke', [AdminController::class, 'revokeApiKey']);

        $this->get('/admin/templates', [AdminController::class, 'templates']);
        $this->get('/admin/templates/data', [AdminController::class, 'templatesData']);
        $this->post('/admin/templates/store', [AdminController::class, 'storeTemplate']);
        $this->post('/admin/templates/update', [AdminController::class, 'updateTemplate']);
        $this->post('/admin/templates/delete', [AdminController::class, 'deleteTemplate']);

        $this->get('/admin/notifications', [AdminController::class, 'notifications']);
        $this->get('/admin/notifications/data', [AdminController::class, 'notificationsData']);
        $this->get('/admin/subscriptions', [AdminController::class, 'subscriptions']);
        $this->get('/admin/subscriptions/data', [AdminController::class, 'subscriptionsData']);
        $this->get('/admin/payments', [AdminController::class, 'payments']);
        $this->get('/admin/payments/data', [AdminController::class, 'paymentsData']);
        $this->get('/admin/reports', [AdminController::class, 'reports']);
        $this->get('/admin/reports/daily', [AdminController::class, 'reportDaily']);
        $this->get('/admin/reports/platforms', [AdminController::class, 'reportPlatforms']);
        $this->get('/admin/settings', [AdminController::class, 'settings']);
        $this->post('/admin/settings/save', [AdminController::class, 'saveSettings']);

        $this->get('/client', [ClientController::class, 'dashboard']);
        $this->get('/client/notifications', [ClientController::class, 'notifications']);
        $this->get('/client/notifications/data', [ClientController::class, 'notificationsData']);
        $this->post('/client/notifications/store', [ClientController::class, 'storeNotification']);
        $this->post('/client/notifications/update', [ClientController::class, 'updateNotification']);
        $this->post('/client/notifications/delete', [ClientController::class, 'deleteNotification']);

        $this->get('/client/templates', [ClientController::class, 'templates']);
        $this->get('/client/templates/data', [ClientController::class, 'templatesData']);
        $this->post('/client/templates/store', [ClientController::class, 'storeTemplate']);
        $this->post('/client/templates/update', [ClientController::class, 'updateTemplate']);
        $this->post('/client/templates/delete', [ClientController::class, 'deleteTemplate']);

        $this->get('/client/api-keys', [ClientController::class, 'apiKeys']);
        $this->get('/client/api-keys/data', [ClientController::class, 'apiKeysData']);
        $this->post('/client/api-keys/store', [ClientController::class, 'storeApiKey']);
        $this->post('/client/api-keys/update', [ClientController::class, 'updateApiKey']);

        $this->get('/client/subscriptions', [ClientController::class, 'subscriptions']);
        $this->get('/client/subscriptions/data', [ClientController::class, 'subscriptionsData']);

        $this->get('/client/reports', [ClientController::class, 'reports']);
        $this->get('/client/reports/engagement', [ClientController::class, 'reportEngagement']);
        $this->get('/client/reports/templates', [ClientController::class, 'reportTemplates']);

        $this->get('/client/billing', [ClientController::class, 'billing']);
        $this->post('/client/billing/checkout', [ClientController::class, 'createCheckout']);

        $this->group('/api', function () {
            $this->match('POST', '/notifications', [ApiController::class, 'dispatchNotification']);
            $this->match('POST', '/tokens', [ApiController::class, 'registerToken']);
            $this->match('POST', '/inbox', [ApiController::class, 'inbox']);
            $this->match('POST', '/receipts', [ApiController::class, 'receipt']);
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
