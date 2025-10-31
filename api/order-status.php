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
    return $order;
}, $statement->fetchAll() ?: []);

Response::json([
    'orders' => $orders,
]);
