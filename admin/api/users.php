<?php
require_once __DIR__ . '/../auth.php';
admin_require_auth();
$pdo = admin_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $role = $_POST['role'] ?? '';
    $status = $_POST['status'] ?? '';
    if (!in_array($role, ['admin','business_owner','user'], true) || !in_array($status, ['active','pending','banned'], true)) {
        admin_json(['message' => 'Geçersiz değer'], 400);
    }
    $stmt = $pdo->prepare('UPDATE users SET role=:role, status=:status WHERE id=:id');
    $stmt->execute([':role' => $role, ':status' => $status, ':id' => $id]);
    admin_json(['message' => 'Kullanıcı güncellendi']);
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 10);
$perPage = min(100, max(5, $perPage));
$q = trim($_GET['q'] ?? '');

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(name LIKE :q OR email LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users {$whereSql}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$offset = ($page - 1) * $perPage;
$dataSql = "SELECT id,name,email,role,status FROM users {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$dataStmt = $pdo->prepare($dataSql);
foreach ($params as $k => $v) {
    $dataStmt->bindValue($k, $v);
}
$dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();
$items = $dataStmt->fetchAll();

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
