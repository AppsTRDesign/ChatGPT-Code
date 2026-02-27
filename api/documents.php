<?php

declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
$lang = current_lang();

$sql = "SELECT d.id,d.file_path,d.mime_type,d.file_name,
COALESCE(dt.title, dtt.title, d.file_name) AS title
FROM documents d
LEFT JOIN document_translations dt ON dt.document_id = d.id AND dt.lang_code = :lang
LEFT JOIN document_translations dtt ON dtt.document_id = d.id AND dtt.lang_code = :def
ORDER BY d.id DESC";
$stmt = db()->prepare($sql);
$stmt->execute(['lang' => $lang, 'def' => DEFAULT_LANG]);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
