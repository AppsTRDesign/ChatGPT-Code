<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('admin');

header('Content-Type: application/json; charset=utf-8');

$db = Helpers::db();
$days = 14;
$startDate = (new DateTimeImmutable('today'))->modify('-' . ($days - 1) . ' days');

$range = [];
for ($i = 0; $i < $days; $i++) {
    $date = $startDate->modify('+' . $i . ' days');
    $range[] = [
        'key' => $date->format('Y-m-d'),
        'label' => $date->format('d.m'),
    ];
}

$usageStmt = $db->prepare('SELECT DATE(created_at) AS day, COUNT(*) AS total FROM api_usage_logs WHERE status = "success" AND created_at >= :start GROUP BY day');
$usageStmt->execute(['start' => $startDate->format('Y-m-d 00:00:00')]);
$usageRows = $usageStmt->fetchAll();
$usageMap = [];
foreach ($usageRows as $row) {
    $usageMap[$row['day']] = (int) $row['total'];
}

$registrationStmt = $db->prepare('SELECT DATE(created_at) AS day, COUNT(*) AS total FROM users WHERE role = "client" AND created_at >= :start GROUP BY day');
$registrationStmt->execute(['start' => $startDate->format('Y-m-d 00:00:00')]);
$registrationRows = $registrationStmt->fetchAll();
$registrationMap = [];
foreach ($registrationRows as $row) {
    $registrationMap[$row['day']] = (int) $row['total'];
}

$revenueStmt = $db->prepare('SELECT DATE(activated_at) AS day, SUM(p.price) AS total FROM user_packages up JOIN packages p ON p.id = up.package_id WHERE up.status = "active" AND up.activated_at IS NOT NULL AND up.activated_at >= :start GROUP BY day');
$revenueStmt->execute(['start' => $startDate->format('Y-m-d 00:00:00')]);
$revenueRows = $revenueStmt->fetchAll();
$revenueMap = [];
foreach ($revenueRows as $row) {
    $revenueMap[$row['day']] = (float) $row['total'];
}

$usage = [];
$registrations = [];
$revenue = [];
foreach ($range as $day) {
    $key = $day['key'];
    $usage[] = [
        'label' => $day['label'],
        'date' => $key,
        'total' => $usageMap[$key] ?? 0,
    ];
    $registrations[] = [
        'label' => $day['label'],
        'date' => $key,
        'total' => $registrationMap[$key] ?? 0,
    ];
    $revenue[] = [
        'label' => $day['label'],
        'date' => $key,
        'total' => round($revenueMap[$key] ?? 0, 2),
    ];
}

$totalRevenueStmt = $db->query('SELECT SUM(p.price) FROM user_packages up JOIN packages p ON p.id = up.package_id WHERE up.status = "active"');
$totalRevenue = (float) ($totalRevenueStmt->fetchColumn() ?: 0);
$pending = (int) ($db->query('SELECT COUNT(*) FROM user_packages WHERE status IN ("pending","awaiting_payment","payment_missing")')->fetchColumn() ?: 0);
$failed = (int) ($db->query('SELECT COUNT(*) FROM user_packages WHERE status = "failed"')->fetchColumn() ?: 0);
$newClientsStmt = $db->query('SELECT COUNT(*) FROM users WHERE role = "client" AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
$newClients = (int) ($newClientsStmt->fetchColumn() ?: 0);

$response = [
    'usage' => $usage,
    'registrations' => $registrations,
    'revenue' => $revenue,
    'summary' => [
        'totalRevenue' => $totalRevenue,
        'pendingPurchases' => $pending,
        'failedPurchases' => $failed,
        'newClients7' => $newClients,
    ],
];

echo json_encode($response);
