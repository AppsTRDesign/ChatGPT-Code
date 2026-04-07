<?php

declare(strict_types=1);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/../app/Helpers/env.php';
require_once __DIR__ . '/../app/Helpers/config.php';
require_once __DIR__ . '/../app/Helpers/i18n.php';
if (file_exists(__DIR__ . '/../vendor/autoload.php')) { require_once __DIR__ . '/../vendor/autoload.php'; }

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/../app/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

use App\Controllers\ActionController;
use App\Controllers\AuthController;
use App\Controllers\MapController;
use App\Controllers\PlayerController;
use App\Controllers\StatsController;
use App\Core\Request;
use App\Core\Router;
use App\Middlewares\AuthMiddleware;


$uriPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/');
$frontendRoutes = [
    '/' => 'map.html',
    '/login' => 'login.html',
    '/register' => 'register.html',
    '/dashboard' => 'dashboard.html',
    '/profile' => 'profile.html',
    '/map' => 'map.html',
];

if (isset($frontendRoutes[$uriPath])) {
    require __DIR__ . '/' . $frontendRoutes[$uriPath];
    exit;
}

$request = new Request();
$router = new Router();
$auth = new AuthMiddleware();

authRoutes($router);
mapRoutes($router);
playerRoutes($router, $auth);
actionRoutes($router, $auth);
statsRoutes($router);
$router->dispatch($request);

function authRoutes(Router $router): void
{
    $controller = new AuthController();
    $router->add('POST', '/api/auth/register', fn($req) => $controller->register($req));
    $router->add('POST', '/api/auth/login', fn($req) => $controller->login($req));
    $router->add('POST', '/api/auth/logout', fn($req) => $controller->logout($req));
}

function mapRoutes(Router $router): void
{
    $controller = new MapController();
    $router->add('GET', '/api/map/regions', fn($req) => $controller->regions($req));
    $router->add('GET', '/api/map/countries', fn($req) => $controller->countries($req));
}

function playerRoutes(Router $router, AuthMiddleware $auth): void
{
    $controller = new PlayerController();
    $router->add('GET', '/api/player/me', fn($req) => $controller->me($req), [fn($req) => $auth->handle($req)]);
    $router->add('POST', '/api/player/travel', fn($req) => $controller->travel($req), [fn($req) => $auth->handle($req)]);
    $router->add('GET', '/api/player/travel-history', fn($req) => $controller->travelHistory($req), [fn($req) => $auth->handle($req)]);
    $router->add('POST', '/api/player/cancel-travel', fn($req) => $controller->cancelTravel($req), [fn($req) => $auth->handle($req)]);
    $router->add('POST', '/api/player/buy-energy', fn($req) => $controller->buyEnergy($req), [fn($req) => $auth->handle($req)]);
}

function actionRoutes(Router $router, AuthMiddleware $auth): void
{
    $controller = new ActionController();
    $router->add('POST', '/api/region/action', fn($req) => $controller->regionAction($req), [fn($req) => $auth->handle($req)]);
}

function statsRoutes(Router $router): void
{
    $controller = new StatsController();
    $router->add('GET', '/api/stats/dashboard', fn($req) => $controller->overview($req));
}
