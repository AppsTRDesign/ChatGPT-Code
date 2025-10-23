<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('admin');

header('Content-Type: application/json; charset=utf-8');

$db = Helpers::db();
$stmt = $db->query('SELECT id, name, description, monthly_limit, duration_days, features, price, is_active, created_at, updated_at FROM packages ORDER BY created_at DESC');
$rows = $stmt->fetchAll();

$data = array_map(static function (array $row) {
    return [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'description' => $row['description'],
        'monthly_limit' => (int) $row['monthly_limit'],
        'duration_days' => (int) $row['duration_days'],
        'features' => $row['features'],
        'price' => (float) $row['price'],
        'is_active' => (int) $row['is_active'] === 1,
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'] ?? null,
    ];
}, $rows);

echo json_encode([
    'total' => count($data),
    'rows' => $data,
]);
