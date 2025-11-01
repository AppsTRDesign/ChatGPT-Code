<?php

require_once __DIR__ . '/../bootstrap.php';

use Core\Database;
use Core\Response;

$restaurantId = 1;
$db = Database::connection();
$tableId = (int)($_GET['table_id'] ?? 0);

if ($tableId <= 0) {
    Response::json(['orders' => []]);
    exit;
}

$currency = $db->query("SELECT currency FROM restaurants WHERE id = {$restaurantId}")->fetchColumn() ?: 'TRY';

$sessionStatement = $db->prepare("SELECT order_id, status, payload, DATE_FORMAT(updated_at, '%d.%m.%Y %H:%i') AS updated_at FROM table_order_sessions WHERE restaurant_id = :restaurant AND table_id = :table ORDER BY updated_at DESC LIMIT 10");
$sessionStatement->execute([
    ':restaurant' => $restaurantId,
    ':table' => $tableId,
]);

$sessions = $sessionStatement->fetchAll() ?: [];

$orders = array_map(static function ($row) use ($currency) {
    $payload = json_decode($row['payload'] ?? '{}', true) ?: [];
    $total = isset($payload['total']) ? (float)$payload['total'] : 0.0;
    $items = array_map(static function ($item) {
        return [
            'name' => $item['name'] ?? '',
            'variant_name' => $item['variant_name'] ?? null,
            'quantity' => (int)($item['quantity'] ?? 0),
        ];
    }, $payload['items'] ?? []);

    return [
        'id' => (int)$row['order_id'],
        'status' => $row['status'],
        'created_at' => $row['updated_at'],
        'total' => $total,
        'total_formatted' => number_format($total, 2, ',', '.') . ' ' . $currency,
        'items' => $items,
    ];
}, $sessions);

Response::json([
    'orders' => $orders,
]);
