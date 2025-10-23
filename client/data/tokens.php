<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\TokenManager;

Auth::requireRole('client');
header('Content-Type: application/json; charset=UTF-8');

$user = Auth::user();
$list = TokenManager::list((int) $user['id']);

$rows = array_map(static function (array $token) {
    return [
        'id' => (int) $token['id'],
        'label' => $token['label'],
        'token' => $token['token'],
        'status' => $token['revoked_at'] ? 'revoked' : 'active',
        'revoked_at' => $token['revoked_at'],
        'created_at' => $token['created_at'],
    ];
}, $list);

echo json_encode([
    'rows' => $rows,
]);
