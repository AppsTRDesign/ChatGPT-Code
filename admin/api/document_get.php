<?php

declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';
if (!admin_auth()) {
    json_response(false, 'Yetkisiz');
}

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM documents WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$row = $stmt->fetch();
if (!$row) {
    json_response(false, 'Belge bulunamadı');
}
json_response(true, 'ok', $row);
