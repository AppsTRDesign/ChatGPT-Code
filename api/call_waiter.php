<?php
require_once __DIR__ . '/../lib/MenuService.php';
require_once __DIR__ . '/../lib/helpers.php';

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$tableToken = $payload['table_token'] ?? '';

if (!$tableToken) {
    json_response(['error' => 'Masa bilgisi eksik.'], 422);
}

$menuService = new MenuService();
$table = $menuService->findTableByToken($tableToken);
if (!$table) {
    json_response(['error' => 'Masa bulunamadı.'], 404);
}

$stmt = db()->prepare('INSERT INTO waiter_calls (table_id, status, created_at, updated_at) VALUES (?, ?, NOW(), NOW())');
$stmt->execute([$table['id'], 'pending']);
json_response(['success' => true]);
