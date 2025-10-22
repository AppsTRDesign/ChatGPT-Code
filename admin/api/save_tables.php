<?php

require_once __DIR__ . '/../../lib/MenuService.php';

require_admin_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$service = new MenuService();
$tables = $service->saveTables($payload);
json_response(['success' => true, 'tables' => $tables]);
