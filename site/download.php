<?php
require __DIR__ . '/includes/functions.php';

$token = $_GET['token'] ?? '';
$file = resolve_download_token($token);
if (!$file) {
    http_response_code(404);
    echo 'Dosya bulunamadı veya token geçersiz.';
    exit;
}

$filename = basename($file);
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($file));
readfile($file);
exit;
