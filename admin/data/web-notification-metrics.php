<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;
use App\NotificationService;

Helpers::requireAjax();
Auth::requireRole('admin');

header('Content-Type: application/json; charset=utf-8');

$range = $_GET['range'] ?? 'weekly';
$notificationId = isset($_GET['notification_id']) ? (int) $_GET['notification_id'] : null;

$rows = NotificationService::metrics($range, $notificationId ?: null);

echo json_encode([
    'rows' => $rows,
]);
