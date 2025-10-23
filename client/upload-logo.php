<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
header('Content-Type: application/json');

if (!Auth::user()) {
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim']);
    exit;
}

if (!isset($_FILES['file'])) {
    echo json_encode(['status' => 'error', 'message' => 'Dosya alınamadı']);
    exit;
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Yükleme sırasında hata oluştu']);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['png', 'jpg', 'jpeg', 'svg'];
if (!in_array($ext, $allowed, true)) {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz dosya formatı']);
    exit;
}

$filename = uniqid('logo_', true) . '.' . $ext;
$destination = __DIR__ . '/../uploads/logos/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode(['status' => 'error', 'message' => 'Dosya taşınamadı']);
    exit;
}

echo json_encode(['status' => 'success', 'path' => '/uploads/logos/' . $filename]);
