<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\UsageLogger;

Auth::requireRole('client');
header('Content-Type: application/json; charset=UTF-8');

$user = Auth::user();
$rows = UsageLogger::statsForUser((int) $user['id']);

$data = array_map(static function (array $row) {
    return [
        'date' => $row['date'],
        'total' => (int) $row['total'],
    ];
}, $rows);

echo json_encode([
    'rows' => $data,
]);
