<?php

declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';
if (!admin_auth()) {
    json_response(false, 'Yetkisiz');
}

$sql = "SELECT d.id,d.file_path,d.mime_type,d.file_name,d.created_at,
COALESCE(dt.title, d.file_name) AS title
FROM documents d
LEFT JOIN document_translations dt ON dt.document_id = d.id AND dt.lang_code = 'en'
ORDER BY d.id DESC";
$rows = db()->query($sql)->fetchAll();
json_response(true, 'ok', $rows);
