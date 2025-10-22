<?php
require_once __DIR__ . '/../lib/OrderService.php';
require_once __DIR__ . '/../lib/MenuService.php';

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$tableToken = $payload['table_token'] ?? '';
$items = $payload['items'] ?? [];
$note = $payload['note'] ?? null;

if (!$tableToken || !$items) {
    json_response(['error' => 'Masa veya ürün bilgileri eksik.'], 422);
}

$menuService = new MenuService();
$table = $menuService->findTableByToken($tableToken);
if (!$table) {
    json_response(['error' => 'Masa bulunamadı.'], 404);
}

$service = new OrderService();
try {
    $order = $service->createOrder($table['id'], array_map(function ($item) {
        return [
            'id' => (int)$item['id'],
            'quantity' => (int)$item['quantity'],
            'price' => (float)$item['price'],
        ];
    }, $items), $note);
    json_response(['success' => true, 'order' => $order]);
} catch (\Throwable $e) {
    json_response(['error' => 'Sipariş oluşturulamadı: ' . $e->getMessage()], 500);
}
