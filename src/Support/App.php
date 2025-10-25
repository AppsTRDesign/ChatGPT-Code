<?php

declare(strict_types=1);

namespace App\Support;

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\ClientController;
use App\Controllers\ApiController;

class App
{
    private Router $router;
    private Session $session;

    public function __construct()
    {
        $this->session = new Session();
        $this->router = new Router($this->session);
        $this->registerRoutes();
    }

    public function run(): void
    {
        $this->router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
    }

    private function registerRoutes(): void
    {
        $this->router->get('/', [AuthController::class, 'showLanding']);
        $this->router->match(['GET', 'POST'], '/login', [AuthController::class, 'login']);
        $this->router->get('/logout', [AuthController::class, 'logout']);
        $this->router->match(['GET', 'POST'], '/register', [AuthController::class, 'register']);
        $this->router->match(['GET', 'POST'], '/forgot-password', [AuthController::class, 'forgotPassword']);
        $this->router->match(['GET', 'POST'], '/resend-activation', [AuthController::class, 'resendActivation']);

        $this->router->group('/admin', function (Router $router) {
            $router->get('/dashboard', [AdminController::class, 'dashboard']);
            $router->get('/members', [AdminController::class, 'members']);
            $router->get('/packages', [AdminController::class, 'packages']);
            $router->get('/settings', [AdminController::class, 'settings']);
            $router->get('/purchases', [AdminController::class, 'purchases']);
            $router->get('/notifications', [AdminController::class, 'notifications']);
            $router->get('/api-usage', [AdminController::class, 'apiUsage']);
        }, true);

        $this->router->group('/app', function (Router $router) {
            $router->get('/dashboard', [ClientController::class, 'dashboard']);
            $router->match(['GET', 'POST'], '/notifications/new', [ClientController::class, 'newNotification']);
            $router->get('/notifications', [ClientController::class, 'notifications']);
            $router->get('/profile', [ClientController::class, 'profile']);
            $router->get('/packages', [ClientController::class, 'packages']);
            $router->get('/tokens', [ClientController::class, 'tokens']);
            $router->get('/sites', [ClientController::class, 'sites']);
            $router->get('/api-usage', [ClientController::class, 'apiUsage']);
            $router->get('/api-guide', [ClientController::class, 'apiGuide']);
            $router->get('/support', [ClientController::class, 'support']);
        }, true);

        $this->router->group('/api', function (Router $router) {
            $router->match(['GET', 'POST'], '/notifications', [ApiController::class, 'notifications']);
            $router->get('/stats', [ApiController::class, 'stats']);
            $router->post('/tokens', [ApiController::class, 'tokens']);
        });
    }
}
