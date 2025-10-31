<?php

require_once __DIR__ . '/../bootstrap.php';

use Core\Response;

$period = $_GET['period'] ?? 'daily';

$report = [
    'period' => $period,
    'orders' => 45,
    'completed_revenue' => 15230.60,
    'pending_revenue' => 1230.50,
];

Response::json([
    'report' => $report,
]);
