<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('admin');

header('Content-Type: application/json; charset=utf-8');

$db = Helpers::db();
$sql = 'SELECT up.id, up.status, up.payment_method, up.note, up.created_at, up.activated_at, up.expires_at, up.limit_snapshot,
               up.last_error, up.last_error_at,
               u.username, u.email, p.name AS package_name, p.price, p.monthly_limit
        FROM user_packages up
        JOIN users u ON u.id = up.user_id
        JOIN packages p ON p.id = up.package_id
        ORDER BY up.created_at DESC';
$rows = $db->query($sql)->fetchAll();

echo json_encode([
    'total' => count($rows),
    'rows' => $rows,
]);
