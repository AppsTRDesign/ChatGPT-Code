<?php
session_start();
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/utils/Router.php';
require __DIR__ . '/app/utils/Response.php';
require __DIR__ . '/app/utils/Validator.php';
require __DIR__ . '/app/utils/Security.php';
require __DIR__ . '/app/utils/FileUploader.php';
require __DIR__ . '/app/utils/SocketNotifier.php';
require __DIR__ . '/app/utils/Localization.php';
require __DIR__ . '/app/utils/DateHelper.php';
require __DIR__ . '/app/utils/ReportExporter.php';

$router = new Router();

$webRoutes = require __DIR__ . '/app/routes/web.php';
$apiRoutes = require __DIR__ . '/app/routes/api.php';

$webRoutes($router);
$apiRoutes($router);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$acceptsHtml = isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false;

if ($path === '/' || $path === '/index.php') {
    include __DIR__ . '/views/app.php';
    exit;
}

if ($path === '/admin' || $path === '/admin/' || ($acceptsHtml && str_starts_with($path, '/admin'))) {
    include __DIR__ . '/views/admin.php';
    exit;
}

if ($path === '/dashboard' || $path === '/dashboard/' || ($acceptsHtml && str_starts_with($path, '/dashboard'))) {
    include __DIR__ . '/views/dashboard.php';
    exit;
}

if (preg_match('#^/menu/([A-Za-z0-9-]+)/table/([A-Za-z0-9-]+)$#', $path, $matches)) {
    $slug = $matches[1];
    $tableSlug = $matches[2];
    $token = $_GET['token'] ?? '';
    $restaurantModel = new Restaurant();
    $restaurant = $restaurantModel->findBySlug($slug);
    $menuApiKey = '';
    $table = null;
    if ($restaurant) {
        $userModel = new User();
        $owner = $userModel->find($restaurant['user_id']);
        $menuApiKey = $owner['api_key'] ?? '';
        $tableModel = new RestaurantTable();
        $table = $tableModel->findBySlug($restaurant['id'], $tableSlug);
    }
    include __DIR__ . '/views/menu.php';
    exit;
}

if (preg_match('#^/menu/([A-Za-z0-9-]+)$#', $path, $matches)) {
    $slug = $matches[1];
    $token = $_GET['token'] ?? '';
    $restaurantModel = new Restaurant();
    $restaurant = $restaurantModel->findBySlug($slug);
    $menuApiKey = '';
    if ($restaurant) {
        $userModel = new User();
        $owner = $userModel->find($restaurant['user_id']);
        $menuApiKey = $owner['api_key'] ?? '';
    }
    $table = null;
    include __DIR__ . '/views/menu.php';
    exit;
}

header('Content-Type: application/json');
$router->dispatch($_SERVER['REQUEST_METHOD'], $path);
