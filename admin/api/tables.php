<?php
require_once __DIR__ . '/../../lib/helpers.php';
$admin = require_auth();

$pdo = db();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $tables = $pdo->query('SELECT id, name, token, status FROM tables ORDER BY id')->fetchAll();
        foreach ($tables as &$table) {
            $table['id'] = (int)$table['id'];
        }
        json_response(['tables' => $tables]);
        break;
    case 'POST':
        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        $tables = $payload['tables'] ?? [];
        $pdo->beginTransaction();
        try {
            $pdo->exec('TRUNCATE TABLE tables');
            $insert = $pdo->prepare('INSERT INTO tables (name, token, status, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
            foreach ($tables as $table) {
                $token = $table['token'] ?? '';
                if (!$token) {
                    $token = bin2hex(random_bytes(4));
                }
                $insert->execute([
                    $table['name'] ?? 'Masa',
                    $token,
                    $table['status'] ?? 'available',
                ]);
            }
            $pdo->commit();
            json_response(['success' => true]);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            json_response(['error' => 'Masalar kaydedilemedi: ' . $e->getMessage()], 500);
        }
        break;
    default:
        http_response_code(405);
        break;
}
