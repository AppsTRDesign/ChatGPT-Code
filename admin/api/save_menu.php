<?php

require_once __DIR__ . '/../../lib/MenuService.php';

require_admin_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$service = new MenuService();
$menu = $service->saveMenu($payload);
json_response(['success' => true, 'menu' => $menu]);
