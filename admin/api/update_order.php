<?php
require_once __DIR__ . '/../../lib/OrderService.php';
$admin = require_auth();

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$orderId = (int)($payload['order_id'] ?? 0);
$status = $payload['status'] ?? '';
$note = $payload['note'] ?? null;

if (!$orderId || !$status) {
    json_response(['error' => 'Sipariş bilgileri eksik.'], 422);
}

$service = new OrderService();
$service->updateOrderStatus($orderId, $status);
$service->addOrderHistory($orderId, $status, $note);

json_response(['success' => true]);
