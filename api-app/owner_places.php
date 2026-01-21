<?php
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');
$userId = (int)($_GET['user_id'] ?? 0);
if ($userId <= 0) {
    json_response(['error' => 'missing_user'], 422);
}

$page = max(1, (int)($_GET['s'] ?? 1));
$limit = (int)($_GET['limit'] ?? 10);
$limit = max(1, min(50, $limit));

$pdo = get_pdo();

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM places WHERE claimed_by = ?');
$countStmt->execute([$userId]);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $limit));
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare(
    'SELECT id, name, business_image, formatted_address, city_name
     FROM places
     WHERE claimed_by = ?
     ORDER BY name ASC
     LIMIT ? OFFSET ?'
);
$stmt->bindValue(1, $userId, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();

$rows = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $rows[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'] ?? '',
        'business_image' => $row['business_image'] ?? null,
        'formatted_address' => $row['formatted_address'] ?? null,
        'city_name' => $row['city_name'] ?? null
    ];
}

json_response([
    'success' => true,
    'places' => $rows,
    'total' => $total,
    'total_pages' => $totalPages,
    'page' => $page
]);
