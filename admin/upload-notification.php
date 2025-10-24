<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('admin');
header('Content-Type: application/json; charset=utf-8');

$csrfToken = $_POST['csrf_token'] ?? ($_GET['csrf_token'] ?? '');
if (!Helpers::validateCsrf($csrfToken)) {
    echo json_encode(['status' => 'error', 'message' => 'Oturum doğrulaması başarısız.']);
    exit;
}

if (!isset($_FILES['file'])) {
    echo json_encode(['status' => 'error', 'message' => 'Dosya alınamadı.']);
    exit;
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Yükleme sırasında hata oluştu.']);
    exit;
}

$allowed = ['png', 'jpg', 'jpeg', 'webp'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed, true)) {
    echo json_encode(['status' => 'error', 'message' => 'Desteklenmeyen dosya formatı.']);
    exit;
}

$directory = __DIR__ . '/../uploads/notifications';
if (!is_dir($directory)) {
    mkdir($directory, 0775, true);
}

$filename = sprintf('notification_%s.%s', bin2hex(random_bytes(6)), $ext);
$destination = $directory . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode(['status' => 'error', 'message' => 'Dosya taşınamadı.']);
    exit;
}

$relative = 'uploads/notifications/' . $filename;
$absolute = rtrim(BASE_URL, '/') . '/' . $relative;

echo json_encode([
    'status' => 'success',
    'relative' => '/' . $relative,
    'url' => $absolute,
]);
