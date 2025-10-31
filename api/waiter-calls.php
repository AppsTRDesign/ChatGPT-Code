<?php

require_once __DIR__ . '/../bootstrap.php';

use Core\Response;

$waiterCalls = [
    [
        'id' => 1,
        'table' => 'A1',
        'status' => 'waiting',
        'created_at' => '2024-05-28 18:10:00',
    ],
    [
        'id' => 2,
        'table' => 'C2',
        'status' => 'on_the_way',
        'created_at' => '2024-05-28 18:15:00',
    ],
];

Response::json([
    'calls' => $waiterCalls,
]);
