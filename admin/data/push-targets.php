<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('admin');
Helpers::requireAjax();

header('Content-Type: application/json; charset=utf-8');

$db = Helpers::db();
$sql = "SELECT u.id, u.username, u.email, COUNT(s.id) AS player_count, MAX(s.last_active) AS last_active
        FROM users u
        JOIN onesignal_subscriptions s ON s.external_id = u.id
        WHERE u.role = 'client'
        GROUP BY u.id, u.username, u.email
        ORDER BY player_count DESC, last_active DESC";

$rows = $db->query($sql)->fetchAll();

echo json_encode([
    'total' => count($rows),
    'rows' => array_map(static function (array $row) {
        return [
            'id' => (int) $row['id'],
            'username' => $row['username'],
            'email' => $row['email'],
            'players' => (int) $row['player_count'],
            'last_active' => $row['last_active'],
        ];
    }, $rows),
]);
