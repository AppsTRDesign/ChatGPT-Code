<?php
require_once __DIR__ . '/../auth.php';
admin_require_auth();
$pdo = admin_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM place_claim_requests WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $claim = $stmt->fetch();
    if (!$claim) {
        admin_json(['message' => 'Kayıt bulunamadı'], 404);
    }
    if ($claim['status'] !== 'pending') {
        admin_json(['message' => 'Taleple işlem yapılamaz'], 400);
    }
    if ($action === 'approve') {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE place_claim_requests SET status='approved', verified_at=NOW() WHERE id=:id")
            ->execute([':id' => $id]);
        $pdo->prepare('UPDATE places SET claimed_by=:user_id, claimed_at=NOW() WHERE id=:place_id')
            ->execute([':user_id' => $claim['user_id'], ':place_id' => $claim['place_id']]);
        $pdo->prepare("UPDATE users SET role='business_owner' WHERE id=:uid")
            ->execute([':uid' => $claim['user_id']]);
        $pdo->commit();
        admin_json(['message' => 'Talep onaylandı']);
    }
    if ($action === 'reject') {
        $pdo->prepare("UPDATE place_claim_requests SET status='rejected', verified_at=NOW() WHERE id=:id")
            ->execute([':id' => $id]);
        admin_json(['message' => 'Talep reddedildi']);
    }
    admin_json(['message' => 'Geçersiz işlem'], 400);
}

$stmt = $pdo->query('SELECT pcr.*, places.name AS place_name, users.name AS user_name FROM place_claim_requests pcr JOIN places ON places.id=pcr.place_id JOIN users ON users.id=pcr.user_id ORDER BY pcr.created_at DESC');
$items = [];
foreach ($stmt as $row) {
    $items[] = [
        'id' => (int)$row['id'],
        'place' => $row['place_name'],
        'user' => $row['user_name'],
        'status' => $row['status'],
        'approval_method' => $row['approval_method'],
        'created_at' => $row['created_at'],
    ];
}
admin_json(['items' => $items]);
