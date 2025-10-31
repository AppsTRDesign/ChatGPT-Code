<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Services\MenuService;
use Core\Response;

$service = new MenuService(1);

Response::json([
    'categories' => $service->categories(),
    'products' => $service->products(),
]);
