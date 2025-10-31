<?php

require_once __DIR__ . '/../bootstrap.php';

use Core\Database;
use Core\Response;

$restaurantId = 1;
$db = Database::connection();

$startParam = $_GET['start'] ?? null;
$endParam = $_GET['end'] ?? null;

$startDate = $startParam ? new DateTimeImmutable($startParam . ' 00:00:00') : new DateTimeImmutable('-6 days 00:00:00');
$endDate = $endParam ? new DateTimeImmutable($endParam . ' 23:59:59') : new DateTimeImmutable('now');

if ($startDate > $endDate) {
    $tmp = $startDate;
    $startDate = $endDate;
    $endDate = $tmp;
}

$statement = $db->prepare("SELECT DATE(created_at) AS period,
        COUNT(*) AS orders,
        SUM(CASE WHEN status = 'Tamamlandı' THEN total ELSE 0 END) AS completed_revenue,
        SUM(CASE WHEN status IN ('Beklemede','Hazırlanıyor') THEN total ELSE 0 END) AS pending_revenue
    FROM orders
    WHERE restaurant_id = :restaurant AND created_at BETWEEN :start AND :end
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) DESC");
$statement->execute([
    ':restaurant' => $restaurantId,
    ':start' => $startDate->format('Y-m-d H:i:s'),
    ':end' => $endDate->format('Y-m-d H:i:s'),
]);

$currency = $db->query("SELECT currency FROM restaurants WHERE id = {$restaurantId}")->fetchColumn() ?: 'TRY';

$reports = array_map(static function ($row) use ($currency) {
    $row['period'] = (new DateTimeImmutable($row['period']))->format('d.m.Y');
    $row['completed_revenue'] = number_format((float)$row['completed_revenue'], 2, ',', '.') . ' ' . $currency;
    $row['pending_revenue'] = number_format((float)$row['pending_revenue'], 2, ',', '.') . ' ' . $currency;
    return $row;
}, $statement->fetchAll() ?: []);

Response::json([
    'reports' => $reports,
]);
