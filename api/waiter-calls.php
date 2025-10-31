<?php

require_once __DIR__ . '/../bootstrap.php';

use Core\Database;
use Core\Response;

$restaurantId = 1;
$db = Database::connection();

$sql = "SELECT w.id, t.name AS table_name, w.status, DATE_FORMAT(w.created_at, '%d.%m.%Y %H:%i') AS created_at
        FROM waiter_calls w
        INNER JOIN tables t ON t.id = w.table_id
        WHERE w.restaurant_id = :restaurant
        ORDER BY w.created_at DESC LIMIT 200";
$statement = $db->prepare($sql);
$statement->execute([':restaurant' => $restaurantId]);

$waiterCalls = array_map(static function ($call) {
    $labels = [
        'waiting' => 'Beklemede',
        'on_the_way' => 'Yolda',
        'completed' => 'Tamamlandı',
    ];
    $call['table'] = $call['table_name'];
    $call['status_label'] = $labels[$call['status']] ?? $call['status'];
    unset($call['table_name']);
    return $call;
}, $statement->fetchAll() ?: []);

Response::json([
    'calls' => $waiterCalls,
]);
