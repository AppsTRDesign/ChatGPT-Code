<?php

require_once __DIR__ . '/../bootstrap.php';

use Core\Database;
use Core\Response;

$restaurantId = 1;
$status = $_GET['status'] ?? 'all';
$db = Database::connection();

$currency = $db->query("SELECT currency FROM restaurants WHERE id = {$restaurantId}")->fetchColumn() ?: 'TRY';

$sql = "SELECT o.id, t.name AS table_name, o.status, o.total, DATE_FORMAT(o.created_at, '%d.%m.%Y %H:%i') AS created_at
        FROM orders o
        INNER JOIN tables t ON t.id = o.table_id
        WHERE o.restaurant_id = :restaurant";
$params = [':restaurant' => $restaurantId];

if ($status !== 'all') {
    $sql .= ' AND o.status = :status';
    $params[':status'] = $status;
}

$sql .= ' ORDER BY o.created_at DESC LIMIT 200';

$statement = $db->prepare($sql);
$statement->execute($params);
$orders = array_map(static function ($order) use ($currency) {
    $order['table'] = $order['table_name'];
    $order['total_formatted'] = number_format((float)$order['total'], 2, ',', '.') . ' ' . $currency;
    unset($order['table_name']);
    return $order;
}, $statement->fetchAll() ?: []);

Response::json([
    'orders' => $orders,
]);
