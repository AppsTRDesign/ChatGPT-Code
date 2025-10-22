<?php
require_once __DIR__ . '/../lib/MenuService.php';

header('Content-Type: application/json; charset=utf-8');
$service = new MenuService();
$menu = $service->getMenu();
echo json_encode($menu, JSON_UNESCAPED_UNICODE);
