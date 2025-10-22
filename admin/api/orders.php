<?php
require_once __DIR__ . '/../../lib/OrderService.php';
require_auth();

$service = new OrderService();
$status = $_GET['status'] ?? null;
$orders = $service->getOrders(['status' => $status]);
json_response(['orders' => $orders]);
