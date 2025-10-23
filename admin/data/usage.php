<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('admin');

header('Content-Type: application/json; charset=utf-8');

$db = Helpers::db();
$sql = 'SELECT l.id, l.endpoint, l.status, l.note, l.created_at, u.username, u.email
        FROM api_usage_logs l
        JOIN users u ON u.id = l.user_id
        ORDER BY l.created_at DESC
        LIMIT 500';
$rows = $db->query($sql)->fetchAll();

echo json_encode([
    'total' => count($rows),
    'rows' => $rows,
]);
