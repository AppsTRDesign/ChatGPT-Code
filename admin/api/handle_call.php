<?php

require_once __DIR__ . '/../../lib/OrderService.php';

require_admin_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$callId = $payload['callId'] ?? '';

if (!$callId) {
    json_response(['error' => 'Eksik bilgi'], 422);
}

$service = new OrderService();
$call = $service->markCallHandled($callId);
if (!$call) {
    json_response(['error' => 'Çağrı bulunamadı'], 404);
}

json_response(['success' => true, 'call' => $call]);
