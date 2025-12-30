<?php
require_once __DIR__ . '/../auth.php';
admin_require_auth();
$pdo = admin_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if (!in_array($action, ['approve','reject'], true)) {
        admin_json(['message' => 'Geçersiz işlem'], 400);
    }
    $newStatus = $action === 'approve' ? 'approved' : 'rejected';
    $stmt = $pdo->prepare('UPDATE user_reviews SET status = :st WHERE id = :id');
    $stmt->execute([':st' => $newStatus, ':id' => $id]);
    admin_json(['message' => 'Güncellendi']);
}

$stmt = $pdo->query("SELECT ur.*, p.name AS place_name FROM user_reviews ur JOIN places p ON p.id = ur.place_id ORDER BY ur.created_at DESC LIMIT 200");
$items = [];
foreach ($stmt as $row) {
    $items[] = [
        'id' => (int)$row['id'],
        'place' => $row['place_name'],
        'author' => $row['author_name'],
        'rating' => (int)$row['rating'],
        'text' => $row['review_text'],
        'status' => $row['status'],
    ];
}
admin_json(['items' => $items]);
