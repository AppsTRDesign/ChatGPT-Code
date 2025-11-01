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

$orders = [];

if (!empty($sessions)) {
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
} else {
    $sql = "SELECT o.id, o.status, o.total, DATE_FORMAT(o.created_at, '%d.%m.%Y %H:%i') AS created_at
        FROM orders o
        WHERE o.restaurant_id = :restaurant AND o.table_id = :table
        ORDER BY o.created_at DESC LIMIT 10";
    $statement = $db->prepare($sql);
    $statement->execute([
        ':restaurant' => $restaurantId,
        ':table' => $tableId,
    ]);

    $orders = array_map(static function ($order) use ($db, $currency) {
        $itemsStatement = $db->prepare('SELECT p.name, oi.quantity, pv.name AS variant_name FROM order_items oi INNER JOIN products p ON p.id = oi.product_id LEFT JOIN product_variants pv ON pv.id = oi.variant_id WHERE oi.order_id = ?');
        $itemsStatement->execute([$order['id']]);
        $order['items'] = $itemsStatement->fetchAll() ?: [];
        $order['total'] = (float)$order['total'];
        $order['total_formatted'] = number_format($order['total'], 2, ',', '.') . ' ' . $currency;
        if ($order['status'] === 'Tamamlandı') {
            $order['status'] = 'Ödeme Alındı';
        }
        return $order;
    }, $statement->fetchAll() ?: []);
}

Response::json([
    'orders' => $orders,
]);
