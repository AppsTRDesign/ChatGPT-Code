<?php

require_once __DIR__ . '/../bootstrap.php';

use Core\Response;

$summary = [
    'total_orders' => 128,
    'revenue' => 45230.75,
    'active_tables' => 9,
    'waiter_calls' => 2,
];

$charts = [
    'labels' => ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'],
    'datasets' => [
        [
            'label' => 'Tamamlanan Sipariş',
            'backgroundColor' => '#0f9d58',
            'data' => [25, 32, 28, 30, 45, 52, 38],
        ],
        [
            'label' => 'Bekleyen Sipariş',
            'backgroundColor' => '#fbbc05',
            'data' => [5, 3, 4, 6, 5, 8, 7],
        ],
    ],
];

Response::json([
    'summary' => $summary,
    'charts' => $charts,
]);
