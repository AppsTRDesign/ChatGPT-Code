<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;
use App\Subscription;

Auth::requireRole('client');
Helpers::requireAjax();
header('Content-Type: application/json; charset=UTF-8');

$user = Auth::user();
$db = Helpers::db();
$stmt = $db->prepare('SELECT up.*, p.name FROM user_packages up JOIN packages p ON p.id = up.package_id WHERE up.user_id = :user_id ORDER BY up.created_at DESC');
$stmt->execute(['user_id' => $user['id']]);
$rows = $stmt->fetchAll();

$data = array_map(static function (array $row) {
    return [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'status' => $row['status'],
        'status_label' => Subscription::statusLabel((string) $row['status']),
        'payment_method' => $row['payment_method'],
        'created_at' => $row['created_at'],
    ];
}, $rows);

echo json_encode([
    'total' => count($data),
    'rows' => $data,
]);
