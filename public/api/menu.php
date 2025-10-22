<?php

require_once __DIR__ . '/../../lib/MenuService.php';

$service = new MenuService();
json_response([
    'menu' => $service->getMenu(),
    'tables' => $service->getTables(),
]);
