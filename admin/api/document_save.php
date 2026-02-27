<?php
require_once __DIR__ . '/_init.php';

$id = (int)($_POST['document_id'] ?? 0);
$lang = trim((string)($_POST['lang_code'] ?? DEFAULT_LANG));
$title = trim((string)($_POST['title'] ?? ''));
if ($title === '') {
    json_response(false, 'Belge adı gerekli');
}

$pdo = db();
$uploadDir = __DIR__ . '/../../uploads/documents';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$filePath = null;
$fileName = null;
$mimeType = null;
if (!empty($_FILES['document_file']['tmp_name']) && is_uploaded_file($_FILES['document_file']['tmp_name'])) {
    $ext = strtolower(pathinfo((string)$_FILES['document_file']['name'], PATHINFO_EXTENSION) ?: 'bin');
    $safe = preg_replace('/[^a-z0-9]+/i', '-', pathinfo((string)$_FILES['document_file']['name'], PATHINFO_FILENAME) ?: 'document') ?: 'document';
    $newName = $safe . '-' . time() . '.' . $ext;
    $abs = $uploadDir . '/' . $newName;
    if (!move_uploaded_file($_FILES['document_file']['tmp_name'], $abs)) {
        json_response(false, 'Belge yüklenemedi');
    }
    $filePath = '/uploads/documents/' . $newName;
    $fileName = (string)$_FILES['document_file']['name'];
    $mimeType = (string)($_FILES['document_file']['type'] ?? 'application/octet-stream');
}

$pdo->beginTransaction();
try {
    if ($id > 0) {
        $old = $pdo->prepare('SELECT file_path FROM documents WHERE id = :id LIMIT 1');
        $old->execute(['id' => $id]);
        $oldPath = (string)($old->fetchColumn() ?: '');

        if ($filePath !== null) {
            $stmt = $pdo->prepare('UPDATE documents SET file_path=:p,file_name=:n,mime_type=:m,updated_at=NOW() WHERE id=:id');
            $stmt->execute(['p' => $filePath, 'n' => $fileName, 'm' => $mimeType, 'id' => $id]);
            if ($oldPath !== '' && str_starts_with($oldPath, '/uploads/documents/')) {
                $oldAbs = __DIR__ . '/../../' . ltrim($oldPath, '/');
                if (is_file($oldAbs)) {
                    @unlink($oldAbs);
                }
            }
        } else {
            $pdo->prepare('UPDATE documents SET updated_at=NOW() WHERE id=:id')->execute(['id' => $id]);
        }
    } else {
        if ($filePath === null) {
            throw new RuntimeException('Yeni belge için dosya yüklemelisiniz');
        }
        $stmt = $pdo->prepare('INSERT INTO documents(file_name,file_path,mime_type,created_at,updated_at) VALUES(:n,:p,:m,NOW(),NOW())');
        $stmt->execute(['n' => $fileName, 'p' => $filePath, 'm' => $mimeType]);
        $id = (int)$pdo->lastInsertId();
    }

    $tr = $pdo->prepare('INSERT INTO document_translations(document_id,lang_code,title) VALUES(:id,:lang,:title)
        ON DUPLICATE KEY UPDATE title=VALUES(title)');
    $tr->execute(['id' => $id, 'lang' => $lang, 'title' => $title]);

    $pdo->commit();
    json_response(true, 'Belge kaydedildi');
} catch (Throwable $e) {
    $pdo->rollBack();
    json_response(false, $e->getMessage());
}
