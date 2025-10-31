<?php

require_once __DIR__ . '/../bootstrap.php';

use Core\Response;

$status = $_GET['status'] ?? 'all';

$orders = [
    [
        'id' => 101,
        'table' => 'A1',
        'status' => 'Beklemede',
        'total' => 245.50,
        'items' => [
            ['name' => 'Latte', 'qty' => 2, 'price' => 45.5],
            ['name' => 'Cheesecake', 'qty' => 1, 'price' => 55.0],
        ],
        'created_at' => '2024-05-28 18:22:00',
    ],
    [
        'id' => 102,
        'table' => 'B3',
        'status' => 'Hazırlanıyor',
        'total' => 132.00,
        'items' => [
            ['name' => 'Mocha', 'qty' => 1, 'price' => 32.0],
            ['name' => 'Tiramisu', 'qty' => 2, 'price' => 50.0],
        ],
        'created_at' => '2024-05-28 18:30:00',
    ],
];

if ($status !== 'all') {
    $orders = array_values(array_filter($orders, static fn($order) => $order['status'] === $status));
}

Response::json([
    'orders' => $orders,
]);
