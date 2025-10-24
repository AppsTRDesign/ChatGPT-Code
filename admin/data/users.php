<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('admin');
Helpers::requireAjax();

header('Content-Type: application/json; charset=utf-8');

$db = Helpers::db();
$rows = $db->query('SELECT u.id, u.username, u.email, u.role, u.created_at,
       EXISTS(SELECT 1 FROM onesignal_subscriptions s WHERE s.user_id = u.id) AS has_player
    FROM users u
    ORDER BY created_at DESC')->fetchAll();
$currentId = (int) Auth::user()['id'];

$data = array_map(static function (array $row) use ($currentId) {
    return [
        'id' => (int) $row['id'],
        'username' => $row['username'],
        'email' => $row['email'],
        'role' => $row['role'],
        'role_label' => $row['role'] === 'admin' ? 'Admin' : 'Müşteri',
        'created_at' => $row['created_at'],
        'self' => ((int) $row['id'] === $currentId),
        'has_player' => !empty($row['has_player']),
    ];
}, $rows);

echo json_encode([
    'total' => count($data),
    'rows' => $data,
]);
