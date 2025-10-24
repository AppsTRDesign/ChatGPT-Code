<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;
use App\NotificationService;

Helpers::requireAjax();
Auth::requireRole('admin');

header('Content-Type: application/json; charset=utf-8');

$range = $_GET['range'] ?? 'weekly';
$notificationId = isset($_GET['notification_id']) && $_GET['notification_id'] !== ''
    ? (int) $_GET['notification_id']
    : null;
$mode = $_GET['mode'] ?? 'table';
$mode = $mode === 'export' ? 'export' : 'table';

$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'clicked';
$order = strtoupper($_GET['order'] ?? 'DESC');

if ($mode === 'export') {
    $result = NotificationService::breakdownData($range, $notificationId ?: null, [
        'search' => $search,
        'sort' => $sort,
        'order' => $order,
        'limit' => 0,
    ]);

    echo json_encode([
        'rows' => $result['rows'],
        'summary' => $result['summary'],
    ]);
    return;
}

$limit = max(1, (int) ($_GET['limit'] ?? 10));
$offset = max(0, (int) ($_GET['offset'] ?? 0));

$result = NotificationService::breakdownData($range, $notificationId ?: null, [
    'search' => $search,
    'sort' => $sort,
    'order' => $order,
    'limit' => $limit,
    'offset' => $offset,
]);

echo json_encode([
    'total' => $result['total'],
    'totalNotFiltered' => $result['total'],
    'rows' => $result['rows'],
    'summary' => $result['summary'],
]);
