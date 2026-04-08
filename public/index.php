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
    '/' => 'map.php',
    '/login' => 'login.php',
    '/register' => 'register.php',
    '/dashboard' => 'dashboard.php',
    '/profile' => 'profile.php',
    '/map' => 'map.php',
    '/rankings' => 'rankings.php',
];

if (preg_match('#^/country/(\d+)$#', $uriPath, $m)) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/country.php';
    exit;
}
if (preg_match('#^/region/(\d+)$#', $uriPath, $m)) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/region.php';
    exit;
}
if (preg_match('#^/player/(\d+)$#', $uriPath, $m)) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/player.php';
    exit;
}
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
i18nRoutes($router);
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
    $router->add('GET', '/api/map/region-detail', fn($req) => $controller->regionDetail($req));
    $router->add('GET', '/api/map/country-detail', fn($req) => $controller->countryDetail($req));
}

function playerRoutes(Router $router, AuthMiddleware $auth): void
{
    $controller = new PlayerController();
    $router->add('GET', '/api/player/me', fn($req) => $controller->me($req), [fn($req) => $auth->handle($req)]);
    $router->add('POST', '/api/player/travel', fn($req) => $controller->travel($req), [fn($req) => $auth->handle($req)]);
    $router->add('GET', '/api/player/travel-history', fn($req) => $controller->travelHistory($req), [fn($req) => $auth->handle($req)]);
    $router->add('POST', '/api/player/cancel-travel', fn($req) => $controller->cancelTravel($req), [fn($req) => $auth->handle($req)]);
    $router->add('POST', '/api/player/buy-energy', fn($req) => $controller->buyEnergy($req), [fn($req) => $auth->handle($req)]);
    $router->add('POST', '/api/player/change-nation', fn($req) => $controller->changeNation($req), [fn($req) => $auth->handle($req)]);
    $router->add('POST', '/api/player/start-stat', fn($req) => $controller->startStat($req), [fn($req) => $auth->handle($req)]);
    $router->add('POST', '/api/player/stop-stat', fn($req) => $controller->stopStat($req), [fn($req) => $auth->handle($req)]);
    $router->add('GET', '/api/player/stat-preview', fn($req) => $controller->statPreview($req), [fn($req) => $auth->handle($req)]);
    $router->add('GET', '/api/player/profile-detail', fn($req) => $controller->profileDetail($req));
    $router->add('GET', '/api/player/notifications', fn($req) => $controller->notifications($req), [fn($req) => $auth->handle($req)]);
    $router->add('POST', '/api/player/notifications/read', fn($req) => $controller->readNotifications($req), [fn($req) => $auth->handle($req)]);
    // Backward-compatible aliases
    $router->add('GET', '/api/player/notification', fn($req) => $controller->notifications($req), [fn($req) => $auth->handle($req)]);
    $router->add('POST', '/api/player/notification/read', fn($req) => $controller->readNotifications($req), [fn($req) => $auth->handle($req)]);
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

function i18nRoutes(Router $router): void
{
    $router->add('GET', '/api/i18n', function (): void {
        $lang = app_lang();
        $_SESSION['lang'] = $lang;
        \App\Core\Response::json([
            'lang' => $lang,
            'data' => app_i18n_map($lang),
        ]);
    });
}
