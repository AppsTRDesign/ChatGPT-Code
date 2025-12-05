<?php
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json; charset=utf-8');
try {
    $pdo = get_pdo();
$stmt = $pdo->query("SELECT business_type AS name, category_slug AS slug, COUNT(*) AS total FROM places WHERE business_type IS NOT NULL AND business_type != '' GROUP BY category_slug, business_type ORDER BY total DESC");
    echo json_encode(['data' => $stmt->fetchAll()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}
