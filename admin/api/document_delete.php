<?php
require_once __DIR__ . '/_init.php';
$id = (int)($_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT file_path FROM documents WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$path = (string)($stmt->fetchColumn() ?: '');

db()->prepare('DELETE FROM documents WHERE id=:id')->execute(['id' => $id]);
if ($path !== '' && str_starts_with($path, '/uploads/documents/')) {
    $abs = __DIR__ . '/../../' . ltrim($path, '/');
    if (is_file($abs)) {
        @unlink($abs);
    }
}
json_response(true, 'Belge silindi');
