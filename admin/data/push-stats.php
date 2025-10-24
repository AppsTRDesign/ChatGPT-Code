<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;
use App\Settings;

Auth::requireRole('admin');
Helpers::requireAjax();

header('Content-Type: application/json; charset=utf-8');

if (!Settings::onesignalEnabled() || !Helpers::tableExists('web_push_events')) {
    echo json_encode([
        'rows' => [],
        'summary' => [
            'queued' => 0,
            'sent' => 0,
            'delivered' => 0,
            'opened' => 0,
            'clicked' => 0,
        ],
        'countries' => [],
        'platforms' => [],
    ]);
    exit;
}

$db = Helpers::db();

$range = $_GET['range'] ?? '30';
$range = (int) $range;
if ($range <= 0 || $range > 365) {
    $range = 30;
}

$sql = "SELECT DATE(created_at) AS label,
        SUM(CASE WHEN event_type = 'queued' THEN count ELSE 0 END) AS queued,
        SUM(CASE WHEN event_type = 'sent' THEN count ELSE 0 END) AS sent,
        SUM(CASE WHEN event_type = 'delivered' THEN count ELSE 0 END) AS delivered,
        SUM(CASE WHEN event_type = 'opened' THEN count ELSE 0 END) AS opened,
        SUM(CASE WHEN event_type = 'clicked' THEN count ELSE 0 END) AS clicked
    FROM web_push_events
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) DESC";

$stmt = $db->prepare($sql);
$stmt->bindValue(':days', $range, \PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$summary = [
    'queued' => 0,
    'sent' => 0,
    'delivered' => 0,
    'opened' => 0,
    'clicked' => 0,
];
foreach ($rows as $row) {
    $summary['queued'] += (int) $row['queued'];
    $summary['sent'] += (int) $row['sent'];
    $summary['delivered'] += (int) $row['delivered'];
    $summary['opened'] += (int) $row['opened'];
    $summary['clicked'] += (int) $row['clicked'];
}

$countrySql = "SELECT country, SUM(count) AS total
    FROM web_push_events
    WHERE country IS NOT NULL AND country <> ''
    GROUP BY country
    ORDER BY total DESC
    LIMIT 10";
$countryRows = $db->query($countrySql)->fetchAll(\PDO::FETCH_ASSOC) ?: [];

$platformSql = "SELECT platform, SUM(count) AS total
    FROM web_push_events
    WHERE platform IS NOT NULL AND platform <> ''
    GROUP BY platform
    ORDER BY total DESC
    LIMIT 10";
$platformRows = $db->query($platformSql)->fetchAll(\PDO::FETCH_ASSOC) ?: [];

echo json_encode([
    'rows' => $rows,
    'summary' => $summary,
    'countries' => $countryRows,
    'platforms' => $platformRows,
]);
