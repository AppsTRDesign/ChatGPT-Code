<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('admin');
Helpers::requireAjax();

header('Content-Type: application/json; charset=utf-8');

$db = Helpers::db();
$sql = 'SELECT pn.id, pn.amount, pn.status, pn.note, pn.created_at, pn.reviewed_at, u.username, u.email, up.id AS user_package_id,
               p.name AS package_name
        FROM payment_notifications pn
        JOIN users u ON u.id = pn.user_id
        LEFT JOIN user_packages up ON up.id = pn.user_package_id
        LEFT JOIN packages p ON p.id = up.package_id
        ORDER BY pn.created_at DESC';
$rows = $db->query($sql)->fetchAll();

echo json_encode([
    'total' => count($rows),
    'rows' => $rows,
]);
