<?php

declare(strict_types=1);

$config = require __DIR__ . '/bootstrap.php';

use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\LoginController;
use App\Controllers\Api\GameController;
use App\Controllers\HomeController;
use App\Core\Router;

$router = new Router();

$homeController = new HomeController($config);
$apiController = new GameController($config);
$adminController = new DashboardController($config);
$loginController = new LoginController($config);

$router->get('/', [$homeController, 'index']);
$router->post('/action/train', [$homeController, 'trainArmy']);
$router->post('/action/collect', [$homeController, 'collectTaxes']);

$router->get('/api/state', [$apiController, 'state']);
$router->post('/api/action/train', [$apiController, 'train']);
$router->post('/api/action/collect', [$apiController, 'collect']);

$router->get('/admin', [$adminController, 'index']);
$router->post('/admin/settings', [$adminController, 'settings']);
$router->get('/admin/login', [$loginController, 'show']);
$router->post('/admin/login', [$loginController, 'login']);
$router->post('/admin/logout', [$loginController, 'logout']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
