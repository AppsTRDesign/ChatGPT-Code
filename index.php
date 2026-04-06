<?php

declare(strict_types=1);

$config = require __DIR__ . '/bootstrap.php';

use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\LoginController;
use App\Controllers\Api\GameController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\SystemController;
use App\Core\Router;

$router = new Router();

$homeController = new HomeController($config);
$apiController = new GameController($config);
$adminController = new DashboardController($config);
$loginController = new LoginController($config);
$authController = new AuthController($config);
$systemController = new SystemController();

$router->get('/', [$homeController, 'index']);
$router->post('/action/work', [$homeController, 'work']);
$router->post('/action/battle', [$homeController, 'battle']);
$router->post('/action/upgrade', [$homeController, 'upgrade']);

$router->get('/login', [$authController, 'showLogin']);
$router->post('/login', [$authController, 'login']);
$router->get('/register', [$authController, 'showRegister']);
$router->post('/register', [$authController, 'register']);
$router->get('/forgot-password', [$authController, 'showForgot']);
$router->post('/forgot-password', [$authController, 'forgot']);
$router->get('/reset-password', [$authController, 'showReset']);
$router->post('/reset-password', [$authController, 'reset']);
$router->post('/logout', [$authController, 'logout']);
$router->get('/health', [$systemController, 'health']);

$router->get('/api/state', [$apiController, 'state']);
$router->post('/api/action/work', [$apiController, 'work']);
$router->post('/api/action/battle', [$apiController, 'battle']);
$router->post('/api/action/upgrade', [$apiController, 'upgrade']);
$router->post('/api/market/create', [$apiController, 'marketCreate']);
$router->post('/api/market/buy', [$apiController, 'marketBuy']);
$router->post('/api/factory/create', [$apiController, 'factoryCreate']);
$router->post('/api/factory/produce', [$apiController, 'factoryProduce']);
$router->post('/api/war/start', [$apiController, 'warStart']);
$router->post('/api/war/attack', [$apiController, 'warAttack']);
$router->post('/api/party/create', [$apiController, 'partyCreate']);
$router->post('/api/party/join', [$apiController, 'partyJoin']);
$router->post('/api/party/leave', [$apiController, 'partyLeave']);
$router->post('/api/election/open', [$apiController, 'electionOpen']);
$router->post('/api/election/vote', [$apiController, 'electionVote']);
$router->post('/api/law/propose', [$apiController, 'lawPropose']);
$router->post('/api/law/vote', [$apiController, 'lawVote']);
$router->post('/api/gov/assign-role', [$apiController, 'governmentAssignRole']);
$router->post('/api/gov/action', [$apiController, 'governmentAction']);
$router->post('/api/travel/request-permit', [$apiController, 'travelRequestPermit']);
$router->post('/api/travel/permit-decision', [$apiController, 'travelPermitDecision']);
$router->post('/api/travel/move', [$apiController, 'travelMove']);
$router->post('/api/travel/request-citizenship', [$apiController, 'travelRequestCitizenship']);
$router->post('/api/travel/citizenship-decision', [$apiController, 'travelCitizenshipDecision']);

$router->get('/admin', [$adminController, 'index']);
$router->post('/admin/world/country', [$adminController, 'addCountry']);
$router->post('/admin/world/country/update', [$adminController, 'updateCountry']);
$router->post('/admin/world/city', [$adminController, 'addCity']);
$router->post('/admin/world/city/update', [$adminController, 'updateCity']);
$router->post('/admin/world/resource', [$adminController, 'addResourceDistribution']);
$router->post('/admin/world/resource/delete', [$adminController, 'deleteResourceDistribution']);
$router->post('/admin/world/map-layer', [$adminController, 'addMapLayer']);
$router->post('/admin/world/map-layer/delete', [$adminController, 'deleteMapLayer']);
$router->post('/admin/world/city-poi', [$adminController, 'addCityPoi']);
$router->post('/admin/world/city-poi/delete', [$adminController, 'deleteCityPoi']);
$router->get('/admin/world/export', [$adminController, 'exportWorldBuilder']);
$router->post('/admin/world/import', [$adminController, 'importWorldBuilder']);
$router->get('/admin/login', [$loginController, 'show']);
$router->post('/admin/login', [$loginController, 'login']);
$router->post('/admin/logout', [$loginController, 'logout']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
