<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\QrHistory;

Auth::requireRole('client');

$id = (int) ($_GET['id'] ?? 0);
$format = strtolower((string) ($_GET['format'] ?? 'png'));
$allowed = ['png', 'jpg', 'svg'];
if (!in_array($format, $allowed, true)) {
    $format = 'png';
}

if ($id <= 0) {
    http_response_code(404);
    exit('Kayıt bulunamadı');
}

$user = Auth::user();
$file = QrHistory::fileForDownload($id, $format, (int) $user['id']);
if (!$file) {
    http_response_code(404);
    exit('Dosya bulunamadı');
}

$path = $file['path'];
$mime = $file['mime'] ?? 'application/octet-stream';
$download = $file['download_name'] ?? ('qr-code-' . $id . '.' . $format);

header('Content-Type: ' . $mime);
$length = @filesize($path);
if ($length !== false) {
    header('Content-Length: ' . $length);
}
header('Content-Disposition: attachment; filename="' . basename($download) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
