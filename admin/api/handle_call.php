<?php
require_once __DIR__ . '/../../lib/OrderService.php';
require_auth();

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$callId = (int)($payload['call_id'] ?? 0);
if (!$callId) {
    json_response(['error' => 'Çağrı bulunamadı.'], 422);
}

$service = new OrderService();
$service->acknowledgeWaiterCall($callId);
json_response(['success' => true]);
