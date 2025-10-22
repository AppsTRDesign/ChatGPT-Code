<?php

require_once __DIR__ . '/../../lib/OrderService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$tableToken = $payload['tableToken'] ?? '';
$items = $payload['items'] ?? [];

if (!$tableToken || empty($items)) {
    json_response(['error' => 'Eksik sipariş bilgisi'], 422);
}

try {
    $service = new OrderService();
    $order = $service->createOrder($tableToken, $items);
    json_response(['success' => true, 'order' => $order]);
} catch (RuntimeException $e) {
    json_response(['error' => $e->getMessage()], 404);
}
