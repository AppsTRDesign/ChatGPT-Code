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

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM user_reviews WHERE user_id = ?');
$countStmt->execute([$userId]);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $limit));
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare(
    'SELECT ur.*, p.name AS place_name, p.business_image
     FROM user_reviews ur
     LEFT JOIN places p ON p.id = ur.place_id
     WHERE ur.user_id = ?
     ORDER BY ur.created_at DESC
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
        'place_id' => (int)$row['place_id'],
        'place_name' => $row['place_name'] ?? '',
        'place_image' => $row['business_image'] ?? null,
        'rating' => (int)$row['rating'],
        'text' => $row['review_text'],
        'created_at' => $row['created_at'],
        'status' => $row['status']
    ];
}

json_response([
    'success' => true,
    'reviews' => $rows,
    'total' => $total,
    'total_pages' => $totalPages,
    'page' => $page
]);
