<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;

Helpers::requireAjax();
Auth::requireRole('admin');

header('Content-Type: application/json; charset=utf-8');

$range = $_GET['range'] ?? 'daily';
$range = in_array($range, ['daily', 'weekly', 'monthly', 'yearly'], true) ? $range : 'daily';

$now = new DateTimeImmutable('now');
$start = $now;
$steps = 24;
$bucketFormat = '%Y-%m-%d %H:00:00';
$keyFormat = 'Y-m-d H:00:00';
$labelFormat = 'H:i';
$increment = '+1 hour';

switch ($range) {
    case 'weekly':
        $steps = 7;
        $start = $now->setTime(0, 0)->modify('-6 days');
        $bucketFormat = '%Y-%m-%d';
        $keyFormat = 'Y-m-d';
        $labelFormat = 'd.m';
        $increment = '+1 day';
        break;
    case 'monthly':
        $steps = 30;
        $start = $now->setTime(0, 0)->modify('-29 days');
        $bucketFormat = '%Y-%m-%d';
        $keyFormat = 'Y-m-d';
        $labelFormat = 'd.m';
        $increment = '+1 day';
        break;
    case 'yearly':
        $steps = 12;
        $start = $now->modify('first day of this month')->setTime(0, 0)->modify('-11 months');
        $bucketFormat = '%Y-%m-01';
        $keyFormat = 'Y-m-01';
        $labelFormat = 'm.Y';
        $increment = '+1 month';
        break;
    default:
        $steps = 24;
        $start = $now->setTime((int) $now->format('H'), 0)->modify('-23 hours');
        $bucketFormat = '%Y-%m-%d %H:00:00';
        $keyFormat = 'Y-m-d H:00:00';
        $labelFormat = 'H:i';
        $increment = '+1 hour';
        break;
}

$db = Helpers::db();

$sql = "SELECT DATE_FORMAT(last_seen, '$bucketFormat') AS bucket, COUNT(*) AS total
        FROM session_activity
        WHERE last_seen >= :start
        GROUP BY bucket";

$stmt = $db->prepare($sql);
$stmt->execute(['start' => $start->format('Y-m-d H:i:s')]);

$raw = [];
foreach ($stmt->fetchAll() as $row) {
    $raw[$row['bucket']] = (int) $row['total'];
}

$rows = [];
$cursor = $start;
for ($i = 0; $i < $steps; $i++) {
    $bucketKey = $cursor->format($keyFormat);
    $label = $cursor->format($labelFormat);
    $rows[] = [
        'label' => $label,
        'total' => $raw[$bucketKey] ?? 0,
    ];
    $cursor = $cursor->modify($increment);
}

echo json_encode(['rows' => array_reverse($rows)]);
