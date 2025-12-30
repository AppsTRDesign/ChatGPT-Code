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

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 10);
$perPage = min(100, max(5, $perPage));
$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = [];
$params = [];
if ($status !== '' && in_array($status, ['pending','approved','rejected'], true)) {
    $where[] = 'ur.status = :status';
    $params[':status'] = $status;
}
if ($q !== '') {
    $where[] = '(p.name LIKE :q OR ur.author_name LIKE :q OR ur.email LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM user_reviews ur JOIN places p ON p.id = ur.place_id {$whereSql}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$offset = ($page - 1) * $perPage;
$dataSql = "SELECT ur.*, p.name AS place_name FROM user_reviews ur JOIN places p ON p.id = ur.place_id {$whereSql} ORDER BY ur.created_at DESC LIMIT :limit OFFSET :offset";
$dataStmt = $pdo->prepare($dataSql);
foreach ($params as $k => $v) {
    $dataStmt->bindValue($k, $v);
}
$dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();

$items = [];
foreach ($dataStmt as $row) {
    $items[] = [
        'id' => (int)$row['id'],
        'place' => $row['place_name'],
        'author' => $row['author_name'],
        'rating' => (int)$row['rating'],
        'text' => $row['review_text'],
        'status' => $row['status'],
    ];
}

$pages = max(1, (int)ceil($total / $perPage));
admin_json([
    'items' => $items,
    'meta' => [
        'page' => $page,
        'pages' => $pages,
        'total' => $total,
        'per_page' => $perPage,
    ],
]);
