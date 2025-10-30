<?php

declare(strict_types=1);

use App\Controllers\Web\AuthController as WebAuthController;
use App\Controllers\Web\DashboardController;
use App\Controllers\Web\LicenseController as WebLicenseController;
use App\Middleware\AdminAuthMiddleware;
use App\Middleware\CsrfMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app): void {
    $container = $app->getContainer();
    $csrf = $container->get(CsrfMiddleware::class);
    $admin = $container->get(AdminAuthMiddleware::class);

    $app->group('', static function (RouteCollectorProxy $group) use ($container): void {
        $group->get('/login', [WebAuthController::class, 'showLogin'])->setName('login');
        $group->post('/login', [WebAuthController::class, 'login']);
    })->add($csrf);

    $app->post('/logout', [WebAuthController::class, 'logout'])->add($csrf);

    $app->group('', static function (RouteCollectorProxy $group): void {
        $group->get('/', [DashboardController::class, 'index']);
        $group->map(['GET', 'POST'], '/licenses/create', [WebLicenseController::class, 'create']);
        $group->get('/licenses', [WebLicenseController::class, 'index']);
    })->add($csrf)->add($admin);
};
