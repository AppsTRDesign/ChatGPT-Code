<?php

require_once __DIR__ . '/../../lib/OrderService.php';

require_admin_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$orderId = $payload['orderId'] ?? '';
$status = $payload['status'] ?? '';

if (!$orderId || !$status) {
    json_response(['error' => 'Eksik bilgi'], 422);
}

$service = new OrderService();
$order = $service->updateStatus($orderId, $status);
if (!$order) {
    json_response(['error' => 'Sipariş bulunamadı'], 404);
}

json_response(['success' => true, 'order' => $order]);
