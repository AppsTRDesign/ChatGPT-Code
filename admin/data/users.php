<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('admin');
Helpers::requireAjax();

header('Content-Type: application/json; charset=utf-8');

$db = Helpers::db();
$rows = $db->query('SELECT u.id, u.username, u.email, u.role, u.created_at, u.is_approved, u.login_blocked, u.email_verified
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
        'is_approved' => (int) ($row['is_approved'] ?? 1) === 1,
        'login_blocked' => (int) ($row['login_blocked'] ?? 0) === 1,
        'email_verified' => (int) ($row['email_verified'] ?? 0) === 1,
        'status' => null,
    ];
}, $rows);

echo json_encode([
    'total' => count($data),
    'rows' => $data,
]);
