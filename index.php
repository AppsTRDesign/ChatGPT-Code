<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\ClientController;
use App\Controllers\DashboardController;

$route = trim($_GET['route'] ?? ($_SERVER['PATH_INFO'] ?? '/'), '/');

$protectedRoutes = ['admin/dashboard', 'admin/segments', 'admin/campaigns'];

$requiresAuth = in_array($route, $protectedRoutes, true) || str_starts_with($route, 'admin/') && $route !== 'admin/login';

if ($requiresAuth && !isset($_SESSION['user_id'])) {
    redirect('admin/login');
}

switch ($route) {
    case '':
        (new ClientController())->landing();
        break;
    case 'client/register':
        (new ClientController())->registerSubscriber();
        break;
    case 'admin/login':
        if (is_post()) {
            (new AuthController())->login();
        } else {
            (new AuthController())->showLogin();
        }
        break;
    case 'admin/logout':
        (new AuthController())->logout();
        break;
    case 'admin/dashboard':
        (new DashboardController())->index();
        break;
    case 'admin/segments/create':
        (new DashboardController())->createSegment();
        break;
    case 'admin/campaigns/create':
        (new DashboardController())->createCampaign();
        break;
    default:
        http_response_code(404);
        view('client/404', ['title' => 'Sayfa Bulunamadı']);
}
