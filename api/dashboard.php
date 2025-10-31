<?php

require_once __DIR__ . '/../bootstrap.php';

use Core\Database;
use Core\Response;

$restaurantId = 1;
$period = $_GET['period'] ?? 'weekly';
$db = Database::connection();

$summary = [
    'total_orders' => (int)$db->query("SELECT COUNT(*) FROM orders WHERE restaurant_id = {$restaurantId}")->fetchColumn(),
    'revenue' => (float)$db->query("SELECT IFNULL(SUM(total), 0) FROM orders WHERE restaurant_id = {$restaurantId} AND status IN ('Ödeme Alındı', 'Tamamlandı')")->fetchColumn(),
    'active_tables' => (int)$db->query("SELECT COUNT(*) FROM tables WHERE restaurant_id = {$restaurantId} AND status = 'occupied'")->fetchColumn(),
    'waiter_calls' => (int)$db->query("SELECT COUNT(*) FROM waiter_calls WHERE restaurant_id = {$restaurantId} AND status != 'completed'")->fetchColumn(),
];

$charts = buildChartData($db, $restaurantId, $period);

Response::json([
    'summary' => $summary,
    'charts' => $charts,
]);

function buildChartData(PDO $db, int $restaurantId, string $period): array
{
    $period = in_array($period, ['daily', 'weekly', 'monthly', 'yearly'], true) ? $period : 'weekly';

    switch ($period) {
        case 'daily':
            $select = "DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')";
            $format = 'H:i';
            $start = (new DateTimeImmutable('-23 hours'))->format('Y-m-d H:00:00');
            break;
        case 'monthly':
            $select = "DATE(created_at)";
            $format = 'd.m';
            $start = (new DateTimeImmutable('-29 days'))->format('Y-m-d 00:00:00');
            break;
        case 'yearly':
            $select = "DATE_FORMAT(created_at, '%Y-%m-01')";
            $format = 'm.Y';
            $start = (new DateTimeImmutable('first day of -11 months'))->format('Y-m-01 00:00:00');
            break;
        case 'weekly':
        default:
            $select = "DATE(created_at)";
            $format = 'd.m';
            $start = (new DateTimeImmutable('-6 days'))->format('Y-m-d 00:00:00');
            break;
    }

    $query = $db->prepare("SELECT {$select} AS bucket,
        SUM(CASE WHEN status IN ('Ödeme Alındı', 'Tamamlandı') THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN status IN ('Beklemede','Hazırlanıyor') THEN 1 ELSE 0 END) AS pending
        FROM orders
        WHERE restaurant_id = :restaurant AND created_at >= :start
        GROUP BY bucket
        ORDER BY bucket ASC");
    $query->execute([
        ':restaurant' => $restaurantId,
        ':start' => $start,
    ]);

    $rows = $query->fetchAll() ?: [];

    $labels = [];
    $completed = [];
    $pending = [];

    foreach ($rows as $row) {
        $bucket = $row['bucket'];
        if ($period === 'yearly') {
            $date = DateTimeImmutable::createFromFormat('Y-m-d', $bucket) ?: new DateTimeImmutable($bucket . '-01');
        } else {
            $date = new DateTimeImmutable($bucket);
        }
        $labels[] = $date->format($format);
        $completed[] = (int)($row['completed'] ?? 0);
        $pending[] = (int)($row['pending'] ?? 0);
    }

    if (!$labels) {
        $labels = ['Veri Yok'];
        $completed = [0];
        $pending = [0];
    }

    return [
        'labels' => $labels,
        'datasets' => [
            [
                'label' => 'Tamamlanan Sipariş',
                'backgroundColor' => '#0f9d58',
                'data' => $completed,
            ],
            [
                'label' => 'Bekleyen Sipariş',
                'backgroundColor' => '#fbbc05',
                'data' => $pending,
            ],
        ],
    ];
}
