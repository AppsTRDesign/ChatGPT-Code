<?php

require_once __DIR__ . '/../../lib/OrderService.php';

require_admin_auth();

$service = new OrderService();
$orders = $service->getOrders();
json_response([
    'orders' => $orders,
    'stats' => $service->getStats(),
]);
