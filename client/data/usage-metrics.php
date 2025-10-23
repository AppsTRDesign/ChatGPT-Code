<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\UsageLogger;

Auth::requireRole('client');
header('Content-Type: application/json; charset=UTF-8');

$user = Auth::user();
$rows = UsageLogger::statsForUser((int) $user['id']);

$metrics = [];
$total = 0;
$peak = 0;
$latest = null;

foreach ($rows as $row) {
    $count = (int) $row['total'];
    $total += $count;
    if ($count > $peak) {
        $peak = $count;
    }
    if ($latest === null) {
        $latest = $count;
    }
    $label = $row['date'];
    $timestamp = strtotime($label);
    $formatted = $timestamp ? date('d.m.Y', $timestamp) : $label;
    $metrics[] = [
        'label' => $formatted,
        'total' => $count,
        'date' => $row['date'],
    ];
}

$average = $rows ? (int) round($total / count($rows)) : 0;

echo json_encode([
    'rows' => $metrics,
    'summary' => [
        'total' => $total,
        'peak' => $peak,
        'average' => $average,
        'latest' => $latest,
    ],
]);
