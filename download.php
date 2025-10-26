<?php
require_once __DIR__ . '/config.php';

function streamFile(string $path, string $filename, string $mime, bool $inline = false): void
{
    if (!is_file($path)) {
        http_response_code(404);
        exit('Dosya bulunamadı.');
    }
    $disposition = $inline ? 'inline' : 'attachment';
    $encoded = rawurlencode($filename);
    $safeName = addcslashes($filename, "\"\\");
    header('Content-Type: ' . $mime);
    header("Content-Disposition: {$disposition}; filename=\"{$safeName}\"; filename*=UTF-8''{$encoded}");
    header('Content-Length: ' . filesize($path));
    header('X-Accel-Buffering: no');
    readfile($path);
    exit;
}

$token = $_GET['token'] ?? null;
$archiveToken = $_GET['archive'] ?? null;
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$preview = isset($_GET['preview']);

if ($archiveToken) {
    $archives = $_SESSION['archives'] ?? [];
    $archive = $archives[$archiveToken] ?? null;
    if (!$archive) {
        http_response_code(404);
        exit('Arşiv bulunamadı.');
    }
    $user = current_user();
    if (!$user || (int) $user['id'] !== (int) $archive['user_id']) {
        http_response_code(403);
        exit('Yetkisiz erişim.');
    }
    $path = $archive['path'];
    if (!is_file($path)) {
        http_response_code(404);
        exit('Arşiv dosyası silinmiş.');
    }
    unset($_SESSION['archives'][$archiveToken]);
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . addcslashes($archive['name'], "\"\\") . '"');
    header('Content-Length: ' . filesize($path));
    header('X-Accel-Buffering: no');
    readfile($path);
    @unlink($path);
    exit;
}

if ($token) {
    $file = fetch_shared_file($pdo, $token);
    if (!$file || !is_share_active($file)) {
        http_response_code(403);
        exit('Paylaşım süresi dolmuş.');
    }
    $path = __DIR__ . '/uploads/' . $file['stored_name'];
    streamFile($path, $file['filename'], $file['type'], $preview);
}

if ($id > 0) {
    $file = fetch_file($pdo, $id);
    if (!$file) {
        http_response_code(404);
        exit('Dosya bulunamadı.');
    }
    $user = current_user();
    $isOwner = $user && (int) $file['user_id'] === (int) $user['id'];
    if (!$isOwner && !is_admin() && !(int) $file['is_public']) {
        http_response_code(403);
        exit('Dosya erişimine izin verilmiyor.');
    }
    $path = __DIR__ . '/uploads/' . $file['stored_name'];
    streamFile($path, $file['filename'], $file['type'], $preview);
}

http_response_code(404);
exit('Geçersiz istek.');
