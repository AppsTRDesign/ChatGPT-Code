<?php

require_once __DIR__ . '/../../lib/OrderService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$tableToken = $payload['tableToken'] ?? '';

if (!$tableToken) {
    json_response(['error' => 'Masa bilgisi eksik'], 422);
}

try {
    $service = new OrderService();
    $call = $service->callWaiter($tableToken);
    json_response(['success' => true, 'call' => $call]);
} catch (RuntimeException $e) {
    json_response(['error' => $e->getMessage()], 404);
}
