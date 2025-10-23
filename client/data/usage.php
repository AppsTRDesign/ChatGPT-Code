<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;
use App\UsageLogger;

Auth::requireRole('client');
Helpers::requireAjax();
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
    'total' => count($data),
    'rows' => $data,
]);
