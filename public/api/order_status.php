<?php

require_once __DIR__ . '/../../lib/OrderService.php';

$tableToken = $_GET['tableToken'] ?? '';

if (!$tableToken) {
    json_response(['error' => 'Masa bilgisi eksik'], 422);
}

$service = new OrderService();
$orders = $service->getOrders($tableToken);
json_response(['orders' => $orders]);
