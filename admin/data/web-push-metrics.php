<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;

Helpers::requireAjax();
Auth::requireRole('admin');

header('Content-Type: application/json; charset=utf-8');

$range = $_GET['range'] ?? 'weekly';
$range = in_array($range, ['daily', 'weekly', 'monthly', 'yearly'], true) ? $range : 'weekly';

$now = new DateTimeImmutable('now');
$start = $now;
$steps = 7;
$bucketFormat = '%Y-%m-%d';
$keyFormat = 'Y-m-d';
$labelFormat = 'd.m';
$increment = '+1 day';

switch ($range) {
    case 'daily':
        $steps = 24;
        $start = $now->setTime((int) $now->format('H'), 0)->modify('-23 hours');
        $bucketFormat = '%Y-%m-%d %H:00:00';
        $keyFormat = 'Y-m-d H:00:00';
        $labelFormat = 'H:i';
        $increment = '+1 hour';
        break;
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
}

$db = Helpers::db();

$sql = "SELECT DATE_FORMAT(created_at, '$bucketFormat') AS bucket, event_type, COUNT(*) AS total
        FROM web_push_events
        WHERE created_at >= :start
        GROUP BY bucket, event_type";

$stmt = $db->prepare($sql);
$stmt->execute(['start' => $start->format('Y-m-d H:i:s')]);

$raw = [];
foreach ($stmt->fetchAll() as $row) {
    $bucket = $row['bucket'];
    $event = $row['event_type'];
    $total = (int) $row['total'];
    if (!isset($raw[$bucket])) {
        $raw[$bucket] = ['delivered' => 0, 'viewed' => 0, 'clicked' => 0];
    }
    if (isset($raw[$bucket][$event])) {
        $raw[$bucket][$event] += $total;
    }
}

$rows = [];
$cursor = $start;
for ($i = 0; $i < $steps; $i++) {
    $bucketKey = $cursor->format($keyFormat);
    $label = $cursor->format($labelFormat);
    $data = $raw[$bucketKey] ?? ['delivered' => 0, 'viewed' => 0, 'clicked' => 0];
    $rows[] = [
        'label' => $label,
        'delivered' => (int) $data['delivered'],
        'viewed' => (int) $data['viewed'],
        'clicked' => (int) $data['clicked'],
    ];
    $cursor = $cursor->modify($increment);
}

$rows = array_reverse($rows);

echo json_encode(['rows' => $rows]);
