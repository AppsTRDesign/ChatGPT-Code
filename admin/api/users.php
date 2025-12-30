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

$stmt = $pdo->query('SELECT id,name,email,role,status FROM users ORDER BY created_at DESC LIMIT 200');
$items = $stmt->fetchAll();
admin_json(['items' => $items]);
