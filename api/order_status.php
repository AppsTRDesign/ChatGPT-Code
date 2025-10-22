<?php
require_once __DIR__ . '/../lib/OrderService.php';
require_once __DIR__ . '/../lib/MenuService.php';

$tableToken = $_GET['table'] ?? '';
if (!$tableToken) {
    json_response(['error' => 'Masa bulunamadı.'], 404);
}

$menuService = new MenuService();
$table = $menuService->findTableByToken($tableToken);
if (!$table) {
    json_response(['error' => 'Masa bulunamadı.'], 404);
}

$service = new OrderService();
$status = $service->getOrderStatusForTable($table['id']);
json_response(['orders' => $status]);
