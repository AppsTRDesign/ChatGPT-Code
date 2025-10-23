<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('admin');

header('Content-Type: application/json; charset=utf-8');

$range = $_GET['range'] ?? 'daily';
$allowed = ['daily', 'weekly', 'monthly', 'yearly'];
if (!in_array($range, $allowed, true)) {
    $range = 'daily';
}

$db = Helpers::db();

switch ($range) {
    case 'weekly':
        $groupSelect = 'YEARWEEK(created_at, 1) AS grp';
        $labelSelect = "CONCAT(DATE_FORMAT(MIN(created_at), '%d.%m'), ' - ', DATE_FORMAT(MAX(created_at), '%d.%m')) AS label";
        $order = 'YEARWEEK(created_at, 1) DESC';
        break;
    case 'monthly':
        $groupSelect = 'DATE_FORMAT(created_at, "%Y-%m") AS grp';
        $labelSelect = "DATE_FORMAT(created_at, '%m/%Y') AS label";
        $order = 'DATE_FORMAT(created_at, "%Y-%m") DESC';
        break;
    case 'yearly':
        $groupSelect = 'YEAR(created_at) AS grp';
        $labelSelect = 'YEAR(created_at) AS label';
        $order = 'YEAR(created_at) DESC';
        break;
    default:
        $groupSelect = 'DATE(created_at) AS grp';
        $labelSelect = "DATE_FORMAT(created_at, '%d.%m.%Y') AS label";
        $order = 'DATE(created_at) DESC';
        break;
}

$sql = "SELECT {$groupSelect}, {$labelSelect}, COUNT(*) AS total
        FROM api_usage_logs
        WHERE status = 'success'
        GROUP BY grp
        ORDER BY {$order}
        LIMIT 60";

$rows = $db->query($sql)->fetchAll();

echo json_encode(['range' => $range, 'rows' => $rows]);
