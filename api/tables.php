<?php

require_once __DIR__ . '/../bootstrap.php';

use Core\Response;

$tables = [
    [
        'id' => 1,
        'name' => 'A1',
        'status' => 'occupied',
        'qr_url' => 'https://example.com/menu?table=A1',
    ],
    [
        'id' => 2,
        'name' => 'A2',
        'status' => 'available',
        'qr_url' => 'https://example.com/menu?table=A2',
    ],
];

Response::json([
    'tables' => $tables,
]);
